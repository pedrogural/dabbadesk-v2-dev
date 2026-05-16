<?php

namespace App\Services\Finance;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderFinanceLookupService
{
    public function search(?string $search, string $filter = 'order_number'): Collection
    {
        $search = trim((string) $search);
        $filter = in_array($filter, ['order_number', 'customer', 'email', 'all'], true) ? $filter : 'order_number';

        if ($search === '') {
            return collect();
        }

        if ($filter !== 'order_number' && strlen($search) < 2) {
            return collect();
        }

        $settlementSubquery = DB::table('order_transactions')
            ->select('order_id', DB::raw('SUM(amount) as settled_total'))
            ->where('status', 'recorded')
            ->groupBy('order_id');

        return DB::table('orders')
            ->join('draft_orders', 'draft_orders.id', '=', 'orders.draft_order_id')
            ->join('customers', 'customers.id', '=', 'draft_orders.customer_id')
            ->leftJoinSub($settlementSubquery, 'settlement_totals', function ($join) {
                $join->on('settlement_totals.order_id', '=', 'orders.id');
            })
            ->select([
                'orders.id',
                'orders.order_number',
                'orders.status',
                'orders.grand_total',
                'orders.bill_to_name',
                'orders.bill_to_email',
                'orders.created_at',
                'customers.id as customer_id',
                'customers.first_name',
                'customers.last_name',
                'customers.company_name',
                DB::raw('COALESCE(settlement_totals.settled_total, 0) as settled_total'),
                DB::raw('GREATEST(orders.grand_total - COALESCE(settlement_totals.settled_total, 0), 0) as balance_due'),
            ])
            ->where(function ($query) use ($search, $filter) {
                if ($filter === 'order_number' || $filter === 'all') {
                    $query->orWhere('orders.order_number', 'like', "%{$search}%");
                }

                if ($filter === 'customer' || $filter === 'all') {
                    $query->orWhere('orders.bill_to_name', 'like', "%{$search}%")
                        ->orWhere('customers.first_name', 'like', "%{$search}%")
                        ->orWhere('customers.last_name', 'like', "%{$search}%")
                        ->orWhere('customers.company_name', 'like', "%{$search}%")
                        ->orWhere(DB::raw("CONCAT(customers.first_name, ' ', customers.last_name)"), 'like', "%{$search}%");
                }

                if ($filter === 'email' || $filter === 'all') {
                    $query->orWhere('orders.bill_to_email', 'like', "%{$search}%");
                }
            })
            ->orderByDesc('orders.created_at')
            ->limit(20)
            ->get();
    }

    public function findOrder(int $orderId): ?object
    {
        return DB::table('orders')
            ->join('draft_orders', 'draft_orders.id', '=', 'orders.draft_order_id')
            ->join('customers', 'customers.id', '=', 'draft_orders.customer_id')
            ->select([
                'orders.id',
                'orders.draft_order_id',
                'orders.source_draft_order_id',
                'orders.parent_order_id',
                'orders.order_number',
                'orders.status',
                'orders.order_type',
                'orders.subtotal',
                'orders.retailer_delivery_fee_total',
                'orders.dabba_fee_amount',
                'orders.grand_total',
                'orders.bill_to_name',
                'orders.bill_to_company',
                'orders.bill_to_email',
                'orders.bill_to_phone',
                'orders.bill_to_address_line1',
                'orders.bill_to_postcode',
                'orders.invoiced_at',
                'orders.sent_at',
                'orders.paid_at',
                'orders.purchased_at',
                'orders.completed_at',
                'orders.cancelled_at',
                'orders.cancel_reason',
                'orders.created_at',
                'orders.updated_at',
                'customers.id as customer_id',
                'customers.first_name',
                'customers.last_name',
                'customers.company_name',
            ])
            ->where('orders.id', $orderId)
            ->first();
    }

    public function summary(int $orderId): array
    {
        $order = DB::table('orders')
            ->select(['id', 'grand_total', 'status'])
            ->where('id', $orderId)
            ->first();

        if (! $order) {
            return [
                'order_total' => 0,
                'payments_used' => 0,
                'wallet_used' => 0,
                'refunds' => 0,
                'voids' => 0,
                'settled_total' => 0,
                'balance_due' => 0,
            ];
        }

        $paymentsUsed = (float) DB::table('order_transactions')
            ->where('order_id', $orderId)
            ->where('status', 'recorded')
            ->where('type', 'payment')
            ->sum('amount');

        $walletUsed = (float) DB::table('order_transactions')
            ->where('order_id', $orderId)
            ->where('status', 'recorded')
            ->where('type', 'credit_application')
            ->sum('amount');

        $refunds = (float) DB::table('order_transactions')
            ->where('order_id', $orderId)
            ->where('status', 'recorded')
            ->where('type', 'refund')
            ->sum('amount');

        $voids = (float) DB::table('order_transactions')
            ->where('order_id', $orderId)
            ->where('status', 'recorded')
            ->whereIn('type', [
                'payment_void',
                'credit_application_void',
                'refund_void',
            ])
            ->sum('amount');

        $settledTotal = (float) DB::table('order_transactions')
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
            ->sum('amount');

        $orderTotal = (float) $order->grand_total;
        $balanceDue = max(0, $orderTotal - $settledTotal);

        return [
            'order_total' => $orderTotal,
            'payments_used' => $paymentsUsed,
            'wallet_used' => $walletUsed,
            'refunds' => $refunds,
            'voids' => $voids,
            'settled_total' => $settledTotal,
            'balance_due' => $balanceDue,
        ];
    }

    public function timeline(int $orderId): Collection
    {
        return DB::table('order_transactions')
            ->leftJoin('payment_types', 'payment_types.id', '=', 'order_transactions.payment_type_id')
            ->select([
                'order_transactions.id',
                'order_transactions.type',
                'order_transactions.amount',
                'order_transactions.currency',
                'order_transactions.status',
                'order_transactions.received_at',
                'order_transactions.method',
                'order_transactions.channel',
                'order_transactions.provider',
                'order_transactions.reference',
                'order_transactions.note',
                'order_transactions.created_at',
                'payment_types.name as payment_type_name',
            ])
            ->where('order_transactions.order_id', $orderId)
            ->orderByDesc('order_transactions.created_at')
            ->limit(80)
            ->get()
            ->map(function ($event) {
                $event->plain_label = $this->plainLabel((string) $event->type);
                $event->plain_explanation = $this->plainExplanation((string) $event->type);
                return $event;
            });
    }

    public function walletApplications(int $orderId): Collection
    {
        return DB::table('credit_applications')
            ->join('customer_credits', 'customer_credits.id', '=', 'credit_applications.customer_credit_id')
            ->select([
                'credit_applications.id',
                'credit_applications.amount_applied',
                'credit_applications.currency',
                'credit_applications.applied_at',
                'credit_applications.created_at',
                'customer_credits.id as customer_credit_id',
                'customer_credits.source_type',
                'customer_credits.amount as credit_original_amount',
                'customer_credits.remaining_amount',
                'customer_credits.status as credit_status',
            ])
            ->where('credit_applications.order_id', $orderId)
            ->orderByDesc('credit_applications.created_at')
            ->limit(30)
            ->get();
    }

    public function warnings(int $orderId): array
    {
        $order = $this->findOrder($orderId);
        $summary = $this->summary($orderId);
        $warnings = [];

        if (! $order) {
            return ['Order not found.'];
        }

        if (($summary['settled_total'] ?? 0) > ($summary['order_total'] ?? 0) + 0.01) {
            $warnings[] = 'This order appears over-settled. More value has been applied than the order total.';
        }

        if (($summary['balance_due'] ?? 0) > 0.01 && in_array($order->status, ['paid', 'completed', 'purchased'], true)) {
            $warnings[] = 'This order status looks advanced, but the finance summary still shows money due.';
        }

        if (($summary['order_total'] ?? 0) <= 0) {
            $warnings[] = 'This order has no order total recorded.';
        }

        $transactionCount = DB::table('order_transactions')
            ->where('order_id', $orderId)
            ->count();

        if ($transactionCount === 0) {
            $warnings[] = 'No settlement transactions were found for this order.';
        }

        return $warnings;
    }

    private function plainLabel(string $type): string
    {
        return match ($type) {
            'payment' => 'Payment used on this order',
            'payment_void' => 'Payment use reversed',
            'credit_application' => 'Wallet balance used',
            'credit_application_void' => 'Wallet use reversed',
            'refund' => 'Refund reduced this order',
            'refund_void' => 'Refund was reversed',
            default => str_replace('_', ' ', ucfirst($type)),
        };
    }

    private function plainExplanation(string $type): string
    {
        return match ($type) {
            'payment' => 'Customer money was applied to reduce this order balance.',
            'payment_void' => 'A previous payment application was cancelled.',
            'credit_application' => 'Existing customer wallet balance was used on this order.',
            'credit_application_void' => 'A previous wallet use was cancelled.',
            'refund' => 'Value previously settled on this order was reduced.',
            'refund_void' => 'A previous refund effect was cancelled.',
            default => 'Order settlement event recorded.',
        };
    }
}