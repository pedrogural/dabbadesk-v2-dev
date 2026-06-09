<?php

namespace App\Http\Controllers;

use App\Services\Orders\OrdersReadOnlyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrdersController extends Controller
{
    public function index(Request $request, OrdersReadOnlyService $orders)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
            'mine' => $request->boolean('mine'),
            'show_history' => $request->boolean('show_history'),
            'user_id' => Auth::id(),
        ];

        return view('orders.index', [
            'filters' => $filters,
            'statusOptions' => $orders->statusOptions(),
            'orders' => $orders->search($filters),
        ]);
    }

    public function show(int $order, OrdersReadOnlyService $orders)
    {
        $orderProfile = $orders->find($order);

        abort_if(! $orderProfile, 404);

        return view('orders.show', [
            'order' => $orderProfile,
            'finance' => $orders->financeSummary($order),
            'retailerGroups' => $orders->itemsGroupedByRetailer($order),
            'purchases' => $orders->purchases($order),
            'arrivals' => $orders->arrivals($order),
            'notes' => $orders->notes($orderProfile),
            'progress' => $orders->progressSummary($order),
            'revisionHistory' => $orders->revisionHistory($orderProfile),
            'requestAttachments' => $orders->requestAttachments($orderProfile),
            'paymentTimeline' => $orders->paymentTimeline($order),
        ]);
    }

    public function storeNote(Request $request, int $order)
    {
        $orderRow = DB::table('orders')->where('id', $order)->first();

        abort_if(! $orderRow, 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:5000'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        DB::table('activity_logs')->insert([
            'subject_type' => 'order',
            'subject_id' => $order,
            'type' => 'note',
            'is_pinned' => $request->boolean('is_pinned'),
            'title' => 'Order note',
            'body' => trim($validated['body']),
            'occurred_at' => now(),
            'created_by_user_id' => Auth::id(),
            'updated_by_user_id' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Order note added.');
    }

    public function storePayment(Request $request, int $order)
    {
        $orderRow = DB::table('orders')
            ->join('draft_orders', 'draft_orders.id', '=', 'orders.draft_order_id')
            ->select([
                'orders.id',
                'orders.order_number',
                'orders.grand_total',
                'orders.status',
                'draft_orders.customer_id',
            ])
            ->where('orders.id', $order)
            ->first();

        abort_if(! $orderRow, 404);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'payment_type' => ['required', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
            'received_at' => ['nullable', 'date'],
        ]);

        $amountReceived = round((float) $validated['amount'], 2);
        $paymentTypeName = trim((string) $validated['payment_type']);
        $reference = trim((string) ($validated['reference'] ?? ''));
        $note = trim((string) ($validated['note'] ?? ''));
        $receivedAt = ! empty($validated['received_at']) ? Carbon::parse($validated['received_at']) : now();
        $userId = Auth::id();

        [$method, $channel, $provider] = $this->paymentMetadata($paymentTypeName);

        $currentSettled = $this->orderSettledTotal($order);
        $currentOrderTotal = round((float) ($orderRow->grand_total ?? 0), 2);
        $currentBalanceDue = max(0.0, round($currentOrderTotal - $currentSettled, 2));
        $wouldOverpay = $amountReceived > ($currentBalanceDue + 0.004);

        if ($wouldOverpay && ! $request->boolean('confirmed_overpayment')) {
            return redirect()
                ->route('orders.show', $order)
                ->withInput()
                ->withErrors(['payment' => 'Overpayment confirmation is required before this payment can be recorded.']);
        }

        $overpayment = 0.0;
        $amountAppliedToOrder = 0.0;

        DB::transaction(function () use ($orderRow, $order, $amountReceived, $paymentTypeName, $reference, $note, $receivedAt, $userId, $method, $channel, $provider, &$overpayment, &$amountAppliedToOrder) {
            $paymentTypeId = $this->ensurePaymentType($paymentTypeName);
            $alreadySettled = $this->orderSettledTotal($order);
            $orderTotal = round((float) ($orderRow->grand_total ?? 0), 2);
            $balanceDue = max(0.0, round($orderTotal - $alreadySettled, 2));
            $amountAppliedToOrder = min($amountReceived, $balanceDue);
            $overpayment = max(0.0, round($amountReceived - $amountAppliedToOrder, 2));

            $ledgerId = (int) DB::table('customer_ledger_entries')->insertGetId([
                'customer_id' => $orderRow->customer_id,
                'type' => 'payment_received',
                'amount' => $amountReceived,
                'currency' => 'GBP',
                'payment_type_id' => $paymentTypeId,
                'reference' => $reference !== '' ? $reference : null,
                'note' => $note !== '' ? $note : null,
                'source_type' => 'order',
                'source_id' => $order,
                'source_invoice_version_id' => null,
                'status' => 'recorded',
                'created_by_user_id' => $userId,
                'occurred_at' => $receivedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $orderTransactionId = null;

            if ($amountAppliedToOrder > 0) {
                $transactionNote = $note !== '' ? $note . ' | Ledger #' . $ledgerId : 'Ledger #' . $ledgerId;

                $orderTransactionId = (int) DB::table('order_transactions')->insertGetId([
                    'order_id' => $order,
                    'invoice_version_id' => null,
                    'payment_type_id' => $paymentTypeId,
                    'type' => 'payment',
                    'amount' => $amountAppliedToOrder,
                    'currency' => 'GBP',
                    'status' => 'recorded',
                    'received_at' => $receivedAt,
                    'method' => $method,
                    'channel' => $channel,
                    'provider' => $provider,
                    'reference' => $reference !== '' ? $reference : null,
                    'note' => $transactionNote,
                    'created_by_user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($overpayment > 0) {
                $creditId = DB::table('customer_credits')->insertGetId([
                    'customer_id' => $orderRow->customer_id,
                    'order_id' => $order,
                    'source_type' => 'payment_overpayment',
                    'source_id' => $ledgerId,
                    'source_invoice_version_id' => null,
                    'amount' => $overpayment,
                    'remaining_amount' => $overpayment,
                    'status' => 'open',
                    'notes' => 'Overpayment from Order #' . $orderRow->order_number . ($reference !== '' ? ' / Ref: ' . $reference : ''),
                    'currency' => 'GBP',
                    'created_by_user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('customer_ledger_entries')->insert([
                    'customer_id' => $orderRow->customer_id,
                    'type' => 'overpayment_to_wallet',
                    'amount' => $overpayment,
                    'currency' => 'GBP',
                    'payment_type_id' => $paymentTypeId,
                    'reference' => 'CC#' . $creditId,
                    'note' => 'Overpayment kept as customer wallet credit from Order #' . $orderRow->order_number . '. Payment ledger #' . $ledgerId . '.',
                    'source_type' => 'customer_credit',
                    'source_id' => $creditId,
                    'source_invoice_version_id' => null,
                    'status' => 'recorded',
                    'created_by_user_id' => $userId,
                    'occurred_at' => $receivedAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->refreshOrderPaymentStatus($order, $userId);

            $body = 'Payment recorded: £' . number_format($amountReceived, 2) . ' via ' . $paymentTypeName . '. Ledger #' . $ledgerId . '.';
            if ($amountAppliedToOrder !== $amountReceived) {
                $body .= ' £' . number_format($amountAppliedToOrder, 2) . ' applied to this order.';
            }
            if ($overpayment > 0) {
                $body .= ' £' . number_format($overpayment, 2) . ' moved to customer wallet as overpayment.';
            }
            if ($reference !== '') {
                $body .= ' Reference: ' . $reference . '.';
            }
            if ($orderTransactionId) {
                $body .= ' Order transaction #' . $orderTransactionId . '.';
            }

            DB::table('activity_logs')->insert([
                'subject_type' => 'order',
                'subject_id' => $order,
                'type' => 'payment_note',
                'is_pinned' => 0,
                'title' => 'Payment recorded',
                'body' => $body,
                'occurred_at' => $receivedAt,
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $message = 'Payment recorded.';
        if ($overpayment > 0) {
            $message .= ' £' . number_format($overpayment, 2) . ' moved to customer wallet as overpayment.';
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', $message);
    }

    public function voidPayment(Request $request, int $order, int $transaction)
    {
        $orderRow = DB::table('orders')
            ->join('draft_orders', 'draft_orders.id', '=', 'orders.draft_order_id')
            ->select([
                'orders.id',
                'orders.order_number',
                'orders.grand_total',
                'orders.status',
                'draft_orders.customer_id',
            ])
            ->where('orders.id', $order)
            ->first();

        abort_if(! $orderRow, 404);

        $validated = $request->validate([
            'reversal_reason' => ['required', 'string', 'max:80'],
            'reversal_note' => ['nullable', 'string', 'max:255'],
        ]);

        $reversalReason = trim((string) $validated['reversal_reason']);
        $reversalNote = trim((string) ($validated['reversal_note'] ?? ''));

        $payment = DB::table('order_transactions')
            ->where('id', $transaction)
            ->where('order_id', $order)
            ->where('type', 'payment')
            ->where('status', 'recorded')
            ->first();

        abort_if(! $payment, 404);

        $alreadyVoided = DB::table('order_transactions')
            ->where('order_id', $order)
            ->where('type', 'payment_void')
            ->where('reference', 'OT#' . $transaction)
            ->exists();

        if ($alreadyVoided) {
            return redirect()
                ->route('orders.show', $order)
                ->withErrors(['payment' => 'This payment has already been reversed.']);
        }

        $ledgerId = $this->ledgerIdFromTransaction($payment);
        $ledger = $ledgerId ? DB::table('customer_ledger_entries')->where('id', $ledgerId)->first() : null;
        $voidAmount = round((float) $payment->amount, 2);
        $receivedAmount = $ledger ? round((float) $ledger->amount, 2) : $voidAmount;

        try {
            DB::transaction(function () use ($orderRow, $order, $transaction, $payment, $ledgerId, $ledger, $voidAmount, $receivedAmount, $reversalReason, $reversalNote) {
            $userId = Auth::id();
            $voidedAt = now();

            if ($ledgerId) {
                $credits = DB::table('customer_credits')
                    ->where('customer_id', $orderRow->customer_id)
                    ->where('order_id', $order)
                    ->where('source_type', 'payment_overpayment')
                    ->where('source_id', $ledgerId)
                    ->get();

                foreach ($credits as $credit) {
                    $amount = round((float) $credit->amount, 2);
                    $remaining = round((float) $credit->remaining_amount, 2);

                    if (abs($amount - $remaining) > 0.004) {
                        throw new \RuntimeException('This payment created wallet credit that has already been used, so it cannot be undone automatically.');
                    }
                }

                foreach ($credits as $credit) {
                    DB::table('customer_credits')->where('id', $credit->id)->update([
                        'remaining_amount' => 0,
                        'status' => 'voided',
                        'notes' => trim((string) $credit->notes . ' | Voided with payment reversal for Order #' . $orderRow->order_number . ' | Reason: ' . $reversalReason),
                        'updated_at' => $voidedAt,
                    ]);

                    DB::table('customer_ledger_entries')->insert([
                        'customer_id' => $orderRow->customer_id,
                        'type' => 'overpayment_wallet_void',
                        'amount' => -1 * round((float) $credit->amount, 2),
                        'currency' => $credit->currency ?: 'GBP',
                        'payment_type_id' => $payment->payment_type_id,
                        'reference' => 'CC#' . $credit->id,
                        'note' => 'Wallet credit voided because payment was reversed for Order #' . $orderRow->order_number . '. Reason: ' . $reversalReason . ($reversalNote !== '' ? ' | ' . $reversalNote : ''),
                        'source_type' => 'customer_credit',
                        'source_id' => $credit->id,
                        'source_invoice_version_id' => null,
                        'status' => 'recorded',
                        'created_by_user_id' => $userId,
                        'occurred_at' => $voidedAt,
                        'created_at' => $voidedAt,
                        'updated_at' => $voidedAt,
                    ]);
                }
            }

            DB::table('order_transactions')->insert([
                'order_id' => $order,
                'invoice_version_id' => $payment->invoice_version_id,
                'payment_type_id' => $payment->payment_type_id,
                'type' => 'payment_void',
                'amount' => -1 * $voidAmount,
                'currency' => $payment->currency ?: 'GBP',
                'status' => 'recorded',
                'received_at' => $voidedAt,
                'method' => $payment->method,
                'channel' => $payment->channel,
                'provider' => $payment->provider,
                'reference' => 'OT#' . $transaction,
                'note' => 'Payment reversal for transaction #' . $transaction . '. Reason: ' . $reversalReason . ($reversalNote !== '' ? ' | ' . $reversalNote : ''),
                'created_by_user_id' => $userId,
                'created_at' => $voidedAt,
                'updated_at' => $voidedAt,
            ]);

            DB::table('customer_ledger_entries')->insert([
                'customer_id' => $orderRow->customer_id,
                'type' => 'payment_void',
                'amount' => -1 * $receivedAmount,
                'currency' => $ledger->currency ?? $payment->currency ?? 'GBP',
                'payment_type_id' => $payment->payment_type_id,
                'reference' => 'OT#' . $transaction,
                'note' => 'Payment reversed for Order #' . $orderRow->order_number . '. Reason: ' . $reversalReason . ($reversalNote !== '' ? ' | ' . $reversalNote : '') . '. Original ' . ($ledgerId ? 'ledger #' . $ledgerId : 'order transaction #' . $transaction) . '.',
                'source_type' => 'order',
                'source_id' => $order,
                'source_invoice_version_id' => null,
                'status' => 'recorded',
                'created_by_user_id' => $userId,
                'occurred_at' => $voidedAt,
                'created_at' => $voidedAt,
                'updated_at' => $voidedAt,
            ]);

            $this->refreshOrderPaymentStatus($order, $userId);

            DB::table('activity_logs')->insert([
                'subject_type' => 'order',
                'subject_id' => $order,
                'type' => 'payment_note',
                'is_pinned' => 0,
                'title' => 'Payment reversed',
                'body' => 'Payment transaction #' . $transaction . ' was reversed. Reason: ' . $reversalReason . '. £' . number_format($voidAmount, 2) . ' removed from order settlement.' . ($receivedAmount > $voidAmount ? ' Original received amount was £' . number_format($receivedAmount, 2) . '; related overpayment wallet credit was also reversed if unused.' : '') . ($reversalNote !== '' ? ' Note: ' . $reversalNote : ''),
                'occurred_at' => $voidedAt,
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => $voidedAt,
                'updated_at' => $voidedAt,
            ]);
            });
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('orders.show', $order)
                ->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Payment reversed.');
    }

    private function ensurePaymentType(string $name): ?int
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        $existing = DB::table('payment_types')->where('name', $name)->first(['id']);

        if ($existing) {
            return (int) $existing->id;
        }

        return (int) DB::table('payment_types')->insertGetId([
            'name' => $name,
            'is_active' => 1,
            'sort_order' => $this->paymentTypeSortOrder($name),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function paymentTypeSortOrder(string $name): int
    {
        return match ($name) {
            'Online Payment Link (Card)' => 10,
            'Card (Office)' => 20,
            'Bank Transfer (BACS)' => 30,
            'Cash' => 40,
            'PayPal' => 50,
            'Customer Wallet' => 60,
            'Adjustment / Correction' => 70,
            default => 90,
        };
    }

    private function paymentMetadata(string $paymentTypeName): array
    {
        return match ($paymentTypeName) {
            'Online Payment Link (Card)' => ['card', 'payment_link', 'Online payment link'],
            'Card (Office)' => ['card', 'office_terminal', 'Office card terminal'],
            'Bank Transfer (BACS)' => ['bank_transfer', 'manual', 'BACS'],
            'Cash' => ['cash', 'office', 'Cash'],
            'PayPal' => ['paypal', 'manual', 'PayPal'],
            'Customer Wallet' => ['wallet', 'internal', 'DabbaDesk'],
            'Adjustment / Correction' => ['adjustment', 'internal', 'DabbaDesk'],
            default => ['other', 'manual', 'Other'],
        };
    }

    private function ledgerIdFromTransaction(object $transaction): ?int
    {
        $note = (string) ($transaction->note ?? '');

        if (preg_match('/Ledger #(\d+)/', $note, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function orderSettledTotal(int $orderId): float
    {
        return round((float) DB::table('order_transactions')
            ->where('order_id', $orderId)
            ->where('status', 'recorded')
            ->whereIn('type', [
                'payment',
                'credit_application',
                'payment_void',
                'credit_application_void',
                'refund',
                'refund_void',
            ])
            ->sum('amount'), 2);
    }

    private function refreshOrderPaymentStatus(int $orderId, ?int $userId): void
    {
        $order = DB::table('orders')->where('id', $orderId)->first(['id', 'grand_total', 'status', 'invoiced_at']);

        if (! $order) {
            return;
        }

        $settled = $this->orderSettledTotal($orderId);
        $total = round((float) ($order->grand_total ?? 0), 2);
        $status = (string) ($order->status ?? 'created');

        $updates = [
            'updated_by_user_id' => $userId,
            'updated_at' => now(),
        ];

        if ($total > 0 && $settled + 0.01 >= $total) {
            if (in_array($status, ['created', 'ready', 'invoiced', 'partially_paid'], true)) {
                $updates['status'] = 'paid';
            }
            $updates['paid_at'] = now();
        } elseif ($settled > 0) {
            if (in_array($status, ['created', 'ready', 'invoiced', 'paid'], true)) {
                $updates['status'] = 'partially_paid';
            }
            $updates['paid_at'] = null;
        } else {
            if (in_array($status, ['paid', 'partially_paid'], true)) {
                $updates['status'] = ! empty($order->invoiced_at) ? 'invoiced' : 'created';
            }
            $updates['paid_at'] = null;
        }

        DB::table('orders')->where('id', $orderId)->update($updates);
    }
}
