<?php

namespace App\Services\Finance;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MoneyDeskSummaryService
{
    public function getStats(): array
    {
        return [
            'payments_received' => (float) DB::table('customer_ledger_entries')
                ->where('type', 'payment_received')
                ->where('status', 'recorded')
                ->sum('amount'),

            'orders_settled' => (float) DB::table('order_transactions')
                ->where('status', 'recorded')
                ->whereIn('type', [
                    'payment',
                    'credit_application',
                    'payment_void',
                    'credit_application_void',
                    'refund',
                    'refund_void',
                ])
                ->sum('amount'),

            'wallet_balance' => (float) DB::table('customer_credits')
                ->where('status', 'open')
                ->sum('remaining_amount'),

            'refunds' => (float) DB::table('customer_ledger_entries')
                ->whereIn('type', [
                    'refund_paid_out',
                    'refund_to_wallet',
                ])
                ->where('status', 'recorded')
                ->sum('amount'),
        ];
    }

    public function recentLedgerEntries(): Collection
    {
        return DB::table('customer_ledger_entries')
            ->leftJoin('customers', 'customers.id', '=', 'customer_ledger_entries.customer_id')
            ->select([
                'customer_ledger_entries.id',
                'customer_ledger_entries.type',
                'customer_ledger_entries.amount',
                'customer_ledger_entries.currency',
                'customer_ledger_entries.status',
                'customer_ledger_entries.occurred_at',
                'customer_ledger_entries.reference',
                'customers.first_name',
                'customers.last_name',
            ])
            ->orderByDesc('customer_ledger_entries.occurred_at')
            ->limit(10)
            ->get();
    }

    public function recentOrderTransactions(): Collection
    {
        return DB::table('order_transactions')
            ->leftJoin('orders', 'orders.id', '=', 'order_transactions.order_id')
            ->select([
                'order_transactions.id',
                'order_transactions.order_id',
                'order_transactions.type',
                'order_transactions.amount',
                'order_transactions.currency',
                'order_transactions.status',
                'order_transactions.created_at',
                'order_transactions.reference',
                'orders.order_number',
            ])
            ->orderByDesc('order_transactions.created_at')
            ->limit(10)
            ->get();
    }

    public function openWalletCredits(): Collection
    {
        return DB::table('customer_credits')
            ->leftJoin('customers', 'customers.id', '=', 'customer_credits.customer_id')
            ->select([
                'customer_credits.id',
                'customer_credits.customer_id',
                'customer_credits.source_type',
                'customer_credits.amount',
                'customer_credits.remaining_amount',
                'customer_credits.currency',
                'customer_credits.status',
                'customer_credits.created_at',
                'customers.first_name',
                'customers.last_name',
            ])
            ->where('customer_credits.status', 'open')
            ->where('customer_credits.remaining_amount', '>', 0)
            ->orderByDesc('customer_credits.created_at')
            ->limit(10)
            ->get();
    }

    public function customerSearch(?string $search, string $filter = 'all'): Collection
    {
        $search = trim((string) $search);
        $filter = in_array($filter, ['all', 'name', 'email', 'phone', 'id'], true) ? $filter : 'all';

        if ($search === '') {
            return collect();
        }

        if ($filter !== 'id' && strlen($search) < 2) {
            return collect();
        }

        $walletSubquery = DB::table('customer_credits')
            ->select('customer_id', DB::raw('SUM(remaining_amount) as wallet_balance'))
            ->where('status', 'open')
            ->groupBy('customer_id');

        $paymentSubquery = DB::table('customer_ledger_entries')
            ->select('customer_id', DB::raw('SUM(amount) as payments_received'))
            ->where('type', 'payment_received')
            ->where('status', 'recorded')
            ->groupBy('customer_id');

        return DB::table('customers')
            ->leftJoinSub($walletSubquery, 'wallet_totals', function ($join) {
                $join->on('wallet_totals.customer_id', '=', 'customers.id');
            })
            ->leftJoinSub($paymentSubquery, 'payment_totals', function ($join) {
                $join->on('payment_totals.customer_id', '=', 'customers.id');
            })
            ->select([
                'customers.id',
                'customers.first_name',
                'customers.last_name',
                'customers.company_name',
                DB::raw('COALESCE(wallet_totals.wallet_balance, 0) as wallet_balance'),
                DB::raw('COALESCE(payment_totals.payments_received, 0) as payments_received'),
                DB::raw("(
                    SELECT emails.email
                    FROM customer_emails
                    INNER JOIN emails ON emails.id = customer_emails.email_id
                    WHERE customer_emails.customer_id = customers.id
                    AND customer_emails.is_active = 1
                    AND emails.is_active = 1
                    ORDER BY customer_emails.is_primary DESC, customer_emails.id ASC
                    LIMIT 1
                ) as primary_email"),
                DB::raw("(
                    SELECT phones.phone
                    FROM customer_phones
                    INNER JOIN phones ON phones.id = customer_phones.phone_id
                    WHERE customer_phones.customer_id = customers.id
                    AND customer_phones.is_active = 1
                    AND phones.is_active = 1
                    ORDER BY customer_phones.is_primary DESC, customer_phones.id ASC
                    LIMIT 1
                ) as primary_phone"),
            ])
            ->where(function ($query) use ($search, $filter) {
                if ($filter === 'id') {
                    $query->where('customers.id', $search);
                    return;
                }

                if ($filter === 'name' || $filter === 'all') {
                    $query->orWhere('customers.first_name', 'like', "%{$search}%")
                        ->orWhere('customers.last_name', 'like', "%{$search}%")
                        ->orWhere('customers.company_name', 'like', "%{$search}%")
                        ->orWhere(DB::raw("CONCAT(customers.first_name, ' ', customers.last_name)"), 'like', "%{$search}%");
                }

                if ($filter === 'email' || $filter === 'all') {
                    $query->orWhereExists(function ($emailQuery) use ($search) {
                        $emailQuery
                            ->select(DB::raw(1))
                            ->from('customer_emails')
                            ->join('emails', 'emails.id', '=', 'customer_emails.email_id')
                            ->whereColumn('customer_emails.customer_id', 'customers.id')
                            ->where('customer_emails.is_active', 1)
                            ->where('emails.is_active', 1)
                            ->where('emails.email', 'like', "%{$search}%");
                    });
                }

                if ($filter === 'phone' || $filter === 'all') {
                    $query->orWhereExists(function ($phoneQuery) use ($search) {
                        $phoneQuery
                            ->select(DB::raw(1))
                            ->from('customer_phones')
                            ->join('phones', 'phones.id', '=', 'customer_phones.phone_id')
                            ->whereColumn('customer_phones.customer_id', 'customers.id')
                            ->where('customer_phones.is_active', 1)
                            ->where('phones.is_active', 1)
                            ->where('phones.phone', 'like', "%{$search}%");
                    });
                }

                if ($filter === 'all' && is_numeric($search)) {
                    $query->orWhere('customers.id', $search);
                }
            })
            ->orderBy('customers.first_name')
            ->orderBy('customers.last_name')
            ->limit(20)
            ->get();
    }

    public function customerProfile(int $customerId): ?object
    {
        return DB::table('customers')
            ->select([
                'customers.id',
                'customers.first_name',
                'customers.last_name',
                'customers.company_name',
                'customers.created_at',
                DB::raw("(
                    SELECT emails.email
                    FROM customer_emails
                    INNER JOIN emails ON emails.id = customer_emails.email_id
                    WHERE customer_emails.customer_id = customers.id
                    AND customer_emails.is_active = 1
                    AND emails.is_active = 1
                    ORDER BY customer_emails.is_primary DESC, customer_emails.id ASC
                    LIMIT 1
                ) as primary_email"),
                DB::raw("(
                    SELECT phones.phone
                    FROM customer_phones
                    INNER JOIN phones ON phones.id = customer_phones.phone_id
                    WHERE customer_phones.customer_id = customers.id
                    AND customer_phones.is_active = 1
                    AND phones.is_active = 1
                    ORDER BY customer_phones.is_primary DESC, customer_phones.id ASC
                    LIMIT 1
                ) as primary_phone"),
            ])
            ->where('customers.id', $customerId)
            ->first();
    }

    public function customerFinanceSummary(int $customerId): array
    {
        $paymentsReceived = (float) DB::table('customer_ledger_entries')
            ->where('customer_id', $customerId)
            ->where('type', 'payment_received')
            ->where('status', 'recorded')
            ->sum('amount');

        $refundsPaidOut = (float) DB::table('customer_ledger_entries')
            ->where('customer_id', $customerId)
            ->where('type', 'refund_paid_out')
            ->where('status', 'recorded')
            ->sum('amount');

        $refundsToWallet = (float) DB::table('customer_ledger_entries')
            ->where('customer_id', $customerId)
            ->where('type', 'refund_to_wallet')
            ->where('status', 'recorded')
            ->sum('amount');

        $walletAvailable = (float) DB::table('customer_credits')
            ->where('customer_id', $customerId)
            ->where('status', 'open')
            ->sum('remaining_amount');

        $walletCreated = (float) DB::table('customer_credits')
            ->where('customer_id', $customerId)
            ->sum('amount');

        $walletUsed = (float) DB::table('credit_applications')
            ->join('customer_credits', 'customer_credits.id', '=', 'credit_applications.customer_credit_id')
            ->where('customer_credits.customer_id', $customerId)
            ->sum('credit_applications.amount_applied');

        $orderSettled = (float) DB::table('order_transactions')
            ->join('orders', 'orders.id', '=', 'order_transactions.order_id')
            ->join('draft_orders', 'draft_orders.id', '=', 'orders.draft_order_id')
            ->where('draft_orders.customer_id', $customerId)
            ->where('order_transactions.status', 'recorded')
            ->whereIn('order_transactions.type', [
                'payment',
                'credit_application',
                'payment_void',
                'credit_application_void',
                'refund',
                'refund_void',
            ])
            ->sum('order_transactions.amount');

        return [
            'payments_received' => $paymentsReceived,
            'refunds_paid_out' => $refundsPaidOut,
            'refunds_to_wallet' => $refundsToWallet,
            'wallet_available' => $walletAvailable,
            'wallet_created' => $walletCreated,
            'wallet_used' => $walletUsed,
            'order_settled' => $orderSettled,
        ];
    }

    public function customerFinanceTimeline(int $customerId): Collection
    {
        $ledger = DB::table('customer_ledger_entries')
            ->select([
                DB::raw("'ledger' as source"),
                'id',
                'type',
                'amount',
                'currency',
                'reference',
                'note',
                'occurred_at as event_at',
                DB::raw('NULL as order_id'),
                DB::raw('NULL as order_number'),
            ])
            ->where('customer_id', $customerId)
            ->where('status', 'recorded');

        $orderTransactions = DB::table('order_transactions')
            ->join('orders', 'orders.id', '=', 'order_transactions.order_id')
            ->join('draft_orders', 'draft_orders.id', '=', 'orders.draft_order_id')
            ->select([
                DB::raw("'order' as source"),
                'order_transactions.id',
                'order_transactions.type',
                'order_transactions.amount',
                'order_transactions.currency',
                'order_transactions.reference',
                DB::raw('NULL as note'),
                'order_transactions.created_at as event_at',
                'orders.id as order_id',
                'orders.order_number',
            ])
            ->where('draft_orders.customer_id', $customerId)
            ->where('order_transactions.status', 'recorded');

        $wallet = DB::table('customer_credits')
            ->select([
                DB::raw("'wallet' as source"),
                'id',
                'source_type as type',
                'amount',
                'currency',
                DB::raw('NULL as reference'),
                'notes as note',
                'created_at as event_at',
                'order_id',
                DB::raw('NULL as order_number'),
            ])
            ->where('customer_id', $customerId);

        return $ledger
            ->unionAll($orderTransactions)
            ->unionAll($wallet)
            ->orderByDesc('event_at')
            ->limit(80)
            ->get()
            ->map(function ($event) {
                $event->plain_label = $this->plainFinancialLabel((string) $event->source, (string) $event->type);
                $event->plain_explanation = $this->plainFinancialExplanation((string) $event->source, (string) $event->type);
                return $event;
            });
    }

    public function customerOrderFinance(int $customerId): Collection
    {
        $settlementSubquery = DB::table('order_transactions')
            ->select('order_id', DB::raw('SUM(amount) as settled_total'))
            ->where('status', 'recorded')
            ->groupBy('order_id');

        return DB::table('orders')
            ->join('draft_orders', 'draft_orders.id', '=', 'orders.draft_order_id')
            ->leftJoinSub($settlementSubquery, 'settlement_totals', function ($join) {
                $join->on('settlement_totals.order_id', '=', 'orders.id');
            })
            ->select([
                'orders.id',
                'orders.order_number',
                'orders.status',
                'orders.grand_total',
                DB::raw("'GBP' as currency"),
                'orders.created_at',
                DB::raw('COALESCE(settlement_totals.settled_total, 0) as settled_total'),
                DB::raw('GREATEST(orders.grand_total - COALESCE(settlement_totals.settled_total, 0), 0) as balance_due'),
            ])
            ->where('draft_orders.customer_id', $customerId)
            ->orderByDesc('orders.created_at')
            ->limit(30)
            ->get();
    }

    public function customerWalletCredits(int $customerId): Collection
    {
        return DB::table('customer_credits')
            ->select([
                'id',
                'source_type',
                'amount',
                'remaining_amount',
                'currency',
                'status',
                'notes',
                'created_at',
            ])
            ->where('customer_id', $customerId)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();
    }

    private function plainFinancialLabel(string $source, string $type): string
    {
        return match ($source . ':' . $type) {
            'ledger:payment_received' => 'Customer paid money',
            'ledger:payment_void' => 'Payment was reversed',
            'ledger:refund_paid_out' => 'Refund paid back to customer',
            'ledger:refund_to_wallet' => 'Refund added to wallet',
            'order:payment' => 'Payment used on an order',
            'order:payment_void' => 'Payment use was reversed',
            'order:credit_application' => 'Wallet balance used on an order',
            'order:credit_application_void' => 'Wallet use was reversed',
            'order:refund' => 'Order value refunded',
            'order:refund_void' => 'Refund was reversed',
            default => $source === 'wallet'
                ? 'Wallet balance created'
                : str_replace('_', ' ', ucfirst($type)),
        };
    }

    private function plainFinancialExplanation(string $source, string $type): string
    {
        return match ($source . ':' . $type) {
            'ledger:payment_received' => 'Real money came into Dabba Direct.',
            'ledger:payment_void' => 'A previous real-money payment entry was cancelled.',
            'ledger:refund_paid_out' => 'Money was sent back outside the wallet.',
            'ledger:refund_to_wallet' => 'Refund value was kept as reusable customer balance.',
            'order:payment' => 'Part or all of a payment was used to settle an order.',
            'order:payment_void' => 'A previous payment settlement was cancelled.',
            'order:credit_application' => 'Existing wallet balance was used to reduce an order balance.',
            'order:credit_application_void' => 'A previous wallet use was cancelled.',
            'order:refund' => 'The settled amount on an order was reduced.',
            'order:refund_void' => 'A previous refund effect was cancelled.',
            default => $source === 'wallet'
                ? 'Money is now available to reuse or refund.'
                : 'Financial event recorded.',
        };
    }
}