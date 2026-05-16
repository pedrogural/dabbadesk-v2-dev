<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardSummaryService
{
    public function today(): array
    {
        $todayStart = Carbon::today();
        $todayEnd = Carbon::tomorrow();

        return [
            'orders_created_today' => (int) DB::table('orders')
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->count(),

            'payments_received_today' => (float) DB::table('customer_ledger_entries')
                ->where('type', 'payment_received')
                ->where('status', 'recorded')
                ->whereBetween('occurred_at', [$todayStart, $todayEnd])
                ->sum('amount'),

            'new_wallet_today' => (float) DB::table('customer_credits')
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->sum('amount'),

            'refunds_today' => (float) DB::table('customer_ledger_entries')
                ->whereIn('type', ['refund_paid_out', 'refund_to_wallet'])
                ->where('status', 'recorded')
                ->whereBetween('occurred_at', [$todayStart, $todayEnd])
                ->sum('amount'),
        ];
    }

    public function operations(): array
    {
        return [
            'orders_waiting_payment' => (int) DB::table('orders')
                ->whereIn('status', ['ready', 'invoiced', 'partially_paid'])
                ->count(),

            'orders_waiting_purchase' => (int) DB::table('orders')
                ->whereIn('status', ['paid', 'waiting_purchase'])
                ->count(),

            'orders_in_purchase' => (int) DB::table('orders')
                ->whereIn('status', ['purchasing', 'purchased'])
                ->count(),

            'orders_ready_for_collection' => (int) DB::table('purchase_arrival_assignments')
                ->whereNull('undone_at')
                ->whereIn('status', ['ready_for_collection', 'for_delivery'])
                ->count(),

            'arrivals_today' => (int) DB::table('purchase_arrival_assignments')
                ->whereNull('undone_at')
                ->whereDate('matched_at', Carbon::today())
                ->count(),
        ];
    }

    public function finance(): array
    {
        return [
            'wallet_liability' => (float) DB::table('customer_credits')
                ->where('status', 'open')
                ->sum('remaining_amount'),

            'payments_received_total' => (float) DB::table('customer_ledger_entries')
                ->where('type', 'payment_received')
                ->where('status', 'recorded')
                ->sum('amount'),

            'refunds_total' => (float) DB::table('customer_ledger_entries')
                ->whereIn('type', ['refund_paid_out', 'refund_to_wallet'])
                ->where('status', 'recorded')
                ->sum('amount'),

            'open_wallet_count' => (int) DB::table('customer_credits')
                ->where('status', 'open')
                ->where('remaining_amount', '>', 0)
                ->count(),
        ];
    }

    public function alerts(): array
    {
        $overSettledOrders = $this->overSettledOrdersCount();
        $paidButDueOrders = $this->paidButDueOrdersCount();
        $ordersWithNoTransactions = $this->ordersWithNoTransactionsCount();
        $walletProblems = $this->walletProblemsCount();
        $refundProblems = $this->refundProblemsCount();
        $looseLedgerEntries = $this->orphanLedgerEntriesCount();

        return [
            'finance_anomalies' => $overSettledOrders
                + $paidButDueOrders
                + $ordersWithNoTransactions
                + $walletProblems
                + $refundProblems
                + $looseLedgerEntries,

            'over_settled_orders' => $overSettledOrders,
            'paid_but_due_orders' => $paidButDueOrders,
            'orders_with_no_transactions' => $ordersWithNoTransactions,
            'wallet_problems' => $walletProblems,
            'refund_problems' => $refundProblems,
            'loose_ledger_entries' => $looseLedgerEntries,
        ];
    }

    public function recentOrders(): Collection
    {
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
                'orders.created_at',
                'customers.first_name',
                'customers.last_name',
                DB::raw('COALESCE(settlement_totals.settled_total, 0) as settled_total'),
                DB::raw('GREATEST(orders.grand_total - COALESCE(settlement_totals.settled_total, 0), 0) as balance_due'),
            ])
            ->orderByDesc('orders.created_at')
            ->limit(8)
            ->get();
    }

    public function recentPayments(): Collection
    {
        return DB::table('customer_ledger_entries')
            ->join('customers', 'customers.id', '=', 'customer_ledger_entries.customer_id')
            ->select([
                'customer_ledger_entries.id',
                'customer_ledger_entries.customer_id',
                'customer_ledger_entries.type',
                'customer_ledger_entries.amount',
                'customer_ledger_entries.currency',
                'customer_ledger_entries.reference',
                'customer_ledger_entries.occurred_at',
                'customers.first_name',
                'customers.last_name',
                'customers.company_name',
            ])
            ->where('customer_ledger_entries.status', 'recorded')
            ->whereIn('customer_ledger_entries.type', [
                'payment_received',
                'refund_paid_out',
                'refund_to_wallet',
            ])
            ->orderByDesc('customer_ledger_entries.occurred_at')
            ->limit(8)
            ->get();
    }

    private function settlementSubquery()
    {
        return DB::table('order_transactions')
            ->select('order_id', DB::raw('SUM(amount) as settled_total'))
            ->where('status', 'recorded')
            ->whereIn('type', [
                'payment',
                'credit_application',
                'payment_void',
                'credit_application_void',
                'refund',
                'refund_void',
            ])
            ->groupBy('order_id');
    }

    private function overSettledOrdersCount(): int
    {
        $settlementSubquery = $this->settlementSubquery();

        return (int) DB::table('orders')
            ->leftJoinSub($settlementSubquery, 'settlement_totals', function ($join) {
                $join->on('settlement_totals.order_id', '=', 'orders.id');
            })
            ->whereRaw('COALESCE(settlement_totals.settled_total, 0) > orders.grand_total + 0.01')
            ->count();
    }

    private function paidButDueOrdersCount(): int
    {
        $settlementSubquery = $this->settlementSubquery();

        return (int) DB::table('orders')
            ->leftJoinSub($settlementSubquery, 'settlement_totals', function ($join) {
                $join->on('settlement_totals.order_id', '=', 'orders.id');
            })
            ->whereIn('orders.status', ['paid', 'purchased', 'completed'])
            ->whereRaw('orders.grand_total - COALESCE(settlement_totals.settled_total, 0) > 0.01')
            ->count();
    }

    private function ordersWithNoTransactionsCount(): int
    {
        return (int) DB::table('orders')
            ->whereIn('orders.status', ['paid', 'purchased', 'completed'])
            ->whereNotExists(function ($query) {
                $query
                    ->select(DB::raw(1))
                    ->from('order_transactions')
                    ->whereColumn('order_transactions.order_id', 'orders.id');
            })
            ->count();
    }

    private function walletProblemsCount(): int
    {
        return (int) DB::table('customer_credits')
            ->where(function ($query) {
                $query
                    ->where('remaining_amount', '<', -0.01)
                    ->orWhereRaw('remaining_amount > amount + 0.01')
                    ->orWhere(function ($subQuery) {
                        $subQuery
                            ->where('status', 'open')
                            ->where('remaining_amount', '<=', 0);
                    });
            })
            ->count();
    }

    private function refundProblemsCount(): int
    {
        $paymentSubquery = DB::table('order_transactions')
            ->select('order_id', DB::raw('SUM(amount) as payments_and_wallet_used'))
            ->where('status', 'recorded')
            ->whereIn('type', ['payment', 'credit_application'])
            ->groupBy('order_id');

        $refundSubquery = DB::table('order_transactions')
            ->select('order_id', DB::raw('ABS(SUM(amount)) as refunds_total'))
            ->where('status', 'recorded')
            ->where('type', 'refund')
            ->groupBy('order_id');

        return (int) DB::table('orders')
            ->joinSub($refundSubquery, 'refund_totals', function ($join) {
                $join->on('refund_totals.order_id', '=', 'orders.id');
            })
            ->leftJoinSub($paymentSubquery, 'payment_totals', function ($join) {
                $join->on('payment_totals.order_id', '=', 'orders.id');
            })
            ->whereRaw('COALESCE(refund_totals.refunds_total, 0) > COALESCE(payment_totals.payments_and_wallet_used, 0) + 0.01')
            ->count();
    }

    private function orphanLedgerEntriesCount(): int
    {
        return (int) DB::table('customer_ledger_entries')
            ->where('status', 'recorded')
            ->whereIn('type', ['payment_received', 'refund_paid_out'])
            ->whereNull('source_id')
            ->whereNull('source_type')
            ->count();
    }
}