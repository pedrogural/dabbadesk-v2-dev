<?php

namespace App\Services\Finance;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialAnomalyService
{
    public function summary(): array
    {
        $overSettled = $this->overSettledOrders()->count();
        $paidButDue = $this->paidButDueOrders()->count();
        $noTransactions = $this->ordersWithNoTransactions()->count();
        $walletProblems = $this->walletProblems()->count();
        $refundProblems = $this->refundProblems()->count();
        $orphanLedger = $this->orphanLedgerEntries()->count();

        return [
            'total' => $overSettled + $paidButDue + $noTransactions + $walletProblems + $refundProblems + $orphanLedger,
            'over_settled_orders' => $overSettled,
            'paid_but_due_orders' => $paidButDue,
            'orders_with_no_transactions' => $noTransactions,
            'wallet_problems' => $walletProblems,
            'refund_problems' => $refundProblems,
            'orphan_ledger_entries' => $orphanLedger,
        ];
    }

    public function overSettledOrders(): Collection
    {
        $settlementSubquery = $this->settlementSubquery();

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
                'customers.id as customer_id',
                'customers.first_name',
                'customers.last_name',
                DB::raw('COALESCE(settlement_totals.settled_total, 0) as settled_total'),
                DB::raw('(COALESCE(settlement_totals.settled_total, 0) - orders.grand_total) as difference_amount'),
            ])
            ->whereRaw('COALESCE(settlement_totals.settled_total, 0) > orders.grand_total + 0.01')
            ->orderByDesc('difference_amount')
            ->limit(50)
            ->get();
    }

    public function paidButDueOrders(): Collection
    {
        $settlementSubquery = $this->settlementSubquery();

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
                'customers.id as customer_id',
                'customers.first_name',
                'customers.last_name',
                DB::raw('COALESCE(settlement_totals.settled_total, 0) as settled_total'),
                DB::raw('GREATEST(orders.grand_total - COALESCE(settlement_totals.settled_total, 0), 0) as balance_due'),
            ])
            ->whereIn('orders.status', ['paid', 'purchased', 'completed'])
            ->whereRaw('orders.grand_total - COALESCE(settlement_totals.settled_total, 0) > 0.01')
            ->orderByDesc('balance_due')
            ->limit(50)
            ->get();
    }

    public function ordersWithNoTransactions(): Collection
    {
        return DB::table('orders')
            ->join('draft_orders', 'draft_orders.id', '=', 'orders.draft_order_id')
            ->join('customers', 'customers.id', '=', 'draft_orders.customer_id')
            ->leftJoin('order_transactions', 'order_transactions.order_id', '=', 'orders.id')
            ->select([
                'orders.id',
                'orders.order_number',
                'orders.status',
                'orders.grand_total',
                'orders.bill_to_name',
                'orders.created_at',
                'customers.id as customer_id',
                'customers.first_name',
                'customers.last_name',
                DB::raw('COUNT(order_transactions.id) as transaction_count'),
            ])
            ->whereIn('orders.status', ['paid', 'purchased', 'completed'])
            ->groupBy([
                'orders.id',
                'orders.order_number',
                'orders.status',
                'orders.grand_total',
                'orders.bill_to_name',
                'orders.created_at',
                'customers.id',
                'customers.first_name',
                'customers.last_name',
            ])
            ->havingRaw('COUNT(order_transactions.id) = 0')
            ->orderByDesc('orders.created_at')
            ->limit(50)
            ->get();
    }

    public function walletProblems(): Collection
    {
        return DB::table('customer_credits')
            ->join('customers', 'customers.id', '=', 'customer_credits.customer_id')
            ->select([
                'customer_credits.id',
                'customer_credits.customer_id',
                'customer_credits.source_type',
                'customer_credits.amount',
                'customer_credits.remaining_amount',
                'customer_credits.status',
                'customer_credits.currency',
                'customer_credits.created_at',
                'customers.first_name',
                'customers.last_name',
                'customers.company_name',
                DB::raw("
                    CASE
                        WHEN customer_credits.remaining_amount < -0.01 THEN 'Wallet balance is negative'
                        WHEN customer_credits.remaining_amount > customer_credits.amount + 0.01 THEN 'Wallet balance is greater than original credit'
                        WHEN customer_credits.status = 'open' AND customer_credits.remaining_amount <= 0 THEN 'Wallet is open but has no balance left'
                        ELSE 'Wallet credit needs review'
                    END as plain_reason
                "),
            ])
            ->where(function ($query) {
                $query
                    ->where('customer_credits.remaining_amount', '<', -0.01)
                    ->orWhereRaw('customer_credits.remaining_amount > customer_credits.amount + 0.01')
                    ->orWhere(function ($subQuery) {
                        $subQuery
                            ->where('customer_credits.status', 'open')
                            ->where('customer_credits.remaining_amount', '<=', 0);
                    });
            })
            ->orderByDesc('customer_credits.created_at')
            ->limit(50)
            ->get();
    }

    public function refundProblems(): Collection
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

        return DB::table('orders')
            ->join('draft_orders', 'draft_orders.id', '=', 'orders.draft_order_id')
            ->join('customers', 'customers.id', '=', 'draft_orders.customer_id')
            ->joinSub($refundSubquery, 'refund_totals', function ($join) {
                $join->on('refund_totals.order_id', '=', 'orders.id');
            })
            ->leftJoinSub($paymentSubquery, 'payment_totals', function ($join) {
                $join->on('payment_totals.order_id', '=', 'orders.id');
            })
            ->select([
                'orders.id',
                'orders.order_number',
                'orders.status',
                'orders.grand_total',
                'orders.bill_to_name',
                'orders.created_at',
                'customers.id as customer_id',
                'customers.first_name',
                'customers.last_name',
                DB::raw('COALESCE(payment_totals.payments_and_wallet_used, 0) as payments_and_wallet_used'),
                DB::raw('COALESCE(refund_totals.refunds_total, 0) as refunds_total'),
                DB::raw("(COALESCE(refund_totals.refunds_total, 0) - COALESCE(payment_totals.payments_and_wallet_used, 0)) as difference_amount"),
            ])
            ->whereRaw('COALESCE(refund_totals.refunds_total, 0) > COALESCE(payment_totals.payments_and_wallet_used, 0) + 0.01')
            ->orderByDesc('difference_amount')
            ->limit(50)
            ->get();
    }

    public function orphanLedgerEntries(): Collection
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
                'customer_ledger_entries.source_type',
                'customer_ledger_entries.source_id',
                'customer_ledger_entries.occurred_at',
                'customers.first_name',
                'customers.last_name',
                'customers.company_name',
            ])
            ->where('customer_ledger_entries.status', 'recorded')
            ->whereIn('customer_ledger_entries.type', ['payment_received', 'refund_paid_out'])
            ->whereNull('customer_ledger_entries.source_id')
            ->whereNull('customer_ledger_entries.source_type')
            ->orderByDesc('customer_ledger_entries.occurred_at')
            ->limit(50)
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
}