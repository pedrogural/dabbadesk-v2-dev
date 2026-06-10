<?php

namespace App\Services\Orders;

use App\Support\Purchasing\PurchaseProgressSummary;
use App\Support\Purchasing\PurchaseWorkbenchQuery;
use App\Support\Search\SmartSearch;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrdersReadOnlyService
{
    public function __construct(
        private readonly PurchaseWorkbenchQuery $purchaseWorkbenchQuery,
        private readonly PurchaseProgressSummary $purchaseProgressSummary,
    ) {
    }

    public function statusOptions(): Collection
    {
        $legacyStatuses = DB::table('orders')
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->reject(fn ($status) => in_array((string) $status, ['paid', 'purchased', 'arrived', 'customer_self_purchase'], true))
            ->values();

        return collect([
            'paid',
            'unpaid',
            'purchased',
            'arrived',
            'customer_self_purchase',
        ])->merge($legacyStatuses)->unique()->values();
    }

    public function search(array $filters): LengthAwarePaginator
    {
        $queryText = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $workflow = trim((string) ($filters['workflow'] ?? 'action_required'));
        $mineOnly = ! empty($filters['mine']);
        $showHistory = ! empty($filters['show_history']);
        $userId = (int) ($filters['user_id'] ?? 0);

        $settlementSubquery = DB::table('order_transactions')
            ->select('order_id', DB::raw('SUM(amount) as settled_total'))
            ->where('status', 'recorded')
            ->groupBy('order_id');

        $itemSubquery = DB::table('order_items')
            ->select('order_id', DB::raw('COUNT(*) as item_count'), DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('order_id');

        $purchaseByRootSubquery = DB::table('order_item_purchases')
            ->select('root_item_id', DB::raw('SUM(qty) as purchased_qty'))
            ->whereIn('status', ['purchased', 'ordered', 'received'])
            ->whereNull('cancelled_at')
            ->whereNotNull('root_item_id')
            ->groupBy('root_item_id');

        $purchaseSubquery = DB::table('order_items')
            ->leftJoinSub($purchaseByRootSubquery, 'purchase_by_root', function ($join) {
                $join->on('purchase_by_root.root_item_id', '=', DB::raw('COALESCE(order_items.root_item_id, order_items.id)'));
            })
            ->select('order_items.order_id', DB::raw('SUM(LEAST(order_items.quantity, COALESCE(purchase_by_root.purchased_qty, 0))) as purchased_qty'))
            ->groupBy('order_items.order_id');

        $arrivalByRootSubquery = DB::table('purchase_arrival_assignments')
            ->select('root_item_id', DB::raw('SUM(qty) as arrived_qty'))
            ->whereNull('undone_at')
            ->whereNotNull('root_item_id')
            ->groupBy('root_item_id');

        $arrivalSubquery = DB::table('order_items')
            ->leftJoinSub($arrivalByRootSubquery, 'arrival_by_root', function ($join) {
                $join->on('arrival_by_root.root_item_id', '=', DB::raw('COALESCE(order_items.root_item_id, order_items.id)'));
            })
            ->select('order_items.order_id', DB::raw('SUM(LEAST(order_items.quantity, COALESCE(arrival_by_root.arrived_qty, 0))) as arrived_qty'))
            ->groupBy('order_items.order_id');

        return DB::table('orders')
            ->join('draft_orders', 'draft_orders.id', '=', 'orders.draft_order_id')
            ->join('customers', 'customers.id', '=', 'draft_orders.customer_id')
            ->leftJoin('users as created_user', 'created_user.id', '=', 'orders.created_by_user_id')
            ->leftJoin('users as updated_user', 'updated_user.id', '=', 'orders.updated_by_user_id')
            ->leftJoinSub($settlementSubquery, 'settlement_totals', function ($join) {
                $join->on('settlement_totals.order_id', '=', 'orders.id');
            })
            ->leftJoinSub($itemSubquery, 'item_totals', function ($join) {
                $join->on('item_totals.order_id', '=', 'orders.id');
            })
            ->leftJoinSub($purchaseSubquery, 'purchase_totals', function ($join) {
                $join->on('purchase_totals.order_id', '=', 'orders.id');
            })
            ->leftJoinSub($arrivalSubquery, 'arrival_totals', function ($join) {
                $join->on('arrival_totals.order_id', '=', 'orders.id');
            })
            ->select([
                'orders.id',
                'orders.draft_order_id',
                'orders.order_number',
                'orders.status',
                'orders.purchase_mode',
                'orders.grand_total',
                'orders.bill_to_name',
                'orders.bill_to_email',
                'orders.created_at',
                'orders.created_by_user_id',
                'orders.updated_by_user_id',
                'orders.paid_at',
                'orders.purchased_at',
                'orders.completed_at',
                'orders.parent_order_id',
                'orders.cancel_reason',
                'customers.id as customer_id',
                'customers.first_name',
                'customers.last_name',
                'customers.company_name',
                'created_user.name as created_by_name',
                'updated_user.name as updated_by_name',
                DB::raw('COALESCE(item_totals.item_count, 0) as item_count'),
                DB::raw('COALESCE(item_totals.total_qty, 0) as total_qty'),
                DB::raw('COALESCE(purchase_totals.purchased_qty, 0) as purchased_qty'),
                DB::raw('COALESCE(arrival_totals.arrived_qty, 0) as arrived_qty'),
                DB::raw('COALESCE(settlement_totals.settled_total, 0) as settled_total'),
                DB::raw('GREATEST(orders.grand_total - COALESCE(settlement_totals.settled_total, 0), 0) as balance_due'),
                DB::raw("(SELECT COUNT(*) FROM orders as revision_orders WHERE revision_orders.order_number = orders.order_number AND revision_orders.id <= orders.id) as revision_number"),
                DB::raw("(SELECT COUNT(*) FROM orders as revision_orders WHERE revision_orders.order_number = orders.order_number) as revision_total"),
                DB::raw("CASE WHEN orders.status = 'superseded' OR orders.cancel_reason = 'superseded' OR EXISTS (SELECT 1 FROM orders newer_orders WHERE newer_orders.order_number = orders.order_number AND newer_orders.id > orders.id AND newer_orders.status != 'superseded' AND (newer_orders.cancel_reason IS NULL OR newer_orders.cancel_reason != 'superseded')) THEN 'superseded' WHEN (SELECT COUNT(*) FROM orders revision_orders WHERE revision_orders.order_number = orders.order_number) > 1 THEN 'current_revision' ELSE 'current' END as revision_state"),
            ])
            ->when(! $showHistory, function ($query) {
                $query
                    ->where('orders.status', '!=', 'superseded')
                    ->where(function ($activeQuery) {
                        $activeQuery->whereNull('orders.cancel_reason')->orWhere('orders.cancel_reason', '!=', 'superseded');
                    })
                    ->whereNotExists(function ($newerQuery) {
                        $newerQuery
                            ->select(DB::raw(1))
                            ->from('orders as newer_orders')
                            ->whereColumn('newer_orders.order_number', 'orders.order_number')
                            ->whereColumn('newer_orders.id', '>', 'orders.id')
                            ->where('newer_orders.status', '!=', 'superseded')
                            ->where(function ($newerActive) {
                                $newerActive->whereNull('newer_orders.cancel_reason')->orWhere('newer_orders.cancel_reason', '!=', 'superseded');
                            });
                    });
            })
            ->when($status !== '', function ($query) use ($status) {
                match ($status) {
                    'paid' => $query->whereRaw('GREATEST(orders.grand_total - COALESCE(settlement_totals.settled_total, 0), 0) <= 0.004'),
                    'unpaid' => $query->whereRaw('GREATEST(orders.grand_total - COALESCE(settlement_totals.settled_total, 0), 0) > 0.004'),
                    'purchased' => $query
                        ->whereRaw('COALESCE(item_totals.total_qty, 0) > 0')
                        ->whereRaw('COALESCE(purchase_totals.purchased_qty, 0) >= COALESCE(item_totals.total_qty, 0)'),
                    'arrived' => $query
                        ->whereRaw('COALESCE(item_totals.total_qty, 0) > 0')
                        ->whereRaw('COALESCE(arrival_totals.arrived_qty, 0) >= COALESCE(item_totals.total_qty, 0)'),
                    'customer_self_purchase' => $query->where('orders.purchase_mode', 'customer_self_purchase'),
                    default => $query->where('orders.status', $status),
                };
            })
            ->when($status === '' && $workflow !== '', function ($query) use ($workflow) {
                $this->applyWorkflowFilter($query, $workflow);
            })
            ->when($mineOnly && $userId > 0, function ($query) use ($userId) {
                $query->where('orders.created_by_user_id', $userId);
            })
            ->when($queryText !== '', function ($query) use ($queryText) {
                SmartSearch::apply($query, $queryText, function ($subQuery, SmartSearch $search) {
                    $like = $search->phraseLike();

                    $subQuery
                        ->where('orders.order_number', 'like', $like)
                        ->orWhere('orders.bill_to_name', 'like', $like)
                        ->orWhere('orders.bill_to_email', 'like', $like)
                        ->orWhere('orders.bill_to_phone', 'like', $like)
                        ->orWhere('customers.first_name', 'like', $like)
                        ->orWhere('customers.last_name', 'like', $like)
                        ->orWhere('customers.company_name', 'like', $like)
                        ->orWhereRaw("CONCAT_WS(' ', customers.first_name, customers.last_name) like ?", [$like])
                        ->orWhereRaw("CONCAT_WS(' ', customers.last_name, customers.first_name) like ?", [$like]);

                    $search->orWhereAllTokensAcross($subQuery, [
                        'orders.bill_to_name',
                        'orders.bill_to_email',
                        'customers.first_name',
                        'customers.last_name',
                        'customers.company_name',
                    ]);

                    if ($search->digits !== '') {
                        $digitsLike = $search->digitsLike();
                        $subQuery->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(orders.bill_to_phone, '+', ''), ' ', ''), '-', ''), '(', ''), ')', '') like ?", [$digitsLike]);
                    }

                    $subQuery
                        ->orWhereExists(function ($itemQuery) use ($like, $search) {
                            $itemQuery
                                ->select(DB::raw(1))
                                ->from('order_items')
                                ->whereColumn('order_items.order_id', 'orders.id')
                                ->where(function ($itemSearch) use ($like, $search) {
                                    $itemSearch
                                        ->where('order_items.item_name', 'like', $like)
                                        ->orWhere('order_items.product_code', 'like', $like)
                                        ->orWhere('order_items.retailer_order_reference', 'like', $like)
                                        ->orWhere('order_items.tracking_reference', 'like', $like);

                                    $search->orWhereAllTokensAcross($itemSearch, [
                                        'order_items.item_name',
                                        'order_items.product_code',
                                        'order_items.retailer_order_reference',
                                        'order_items.tracking_reference',
                                    ]);
                                });
                        })
                        ->orWhereExists(function ($purchaseQuery) use ($like) {
                            $purchaseQuery
                                ->select(DB::raw(1))
                                ->from('order_items as purchase_lookup_items')
                                ->whereColumn('purchase_lookup_items.order_id', 'orders.id')
                                ->whereExists(function ($purchaseMatch) use ($like) {
                                    $purchaseMatch
                                        ->select(DB::raw(1))
                                        ->from('order_item_purchases')
                                        ->whereRaw('order_item_purchases.root_item_id = COALESCE(purchase_lookup_items.root_item_id, purchase_lookup_items.id)')
                                        ->where(function ($purchaseSearch) use ($like) {
                                            $purchaseSearch
                                                ->where('order_item_purchases.retailer_order_reference', 'like', $like)
                                                ->orWhere('order_item_purchases.marketplace_seller', 'like', $like);
                                        });
                                });
                        });
                });
            })
            ->orderByDesc('orders.created_at')
            ->paginate(20)
            ->withQueryString();
    }

    public function workflowTabs(array $filters): Collection
    {
        $activeWorkflow = trim((string) ($filters['workflow'] ?? 'action_required')) ?: 'action_required';

        return collect([
            [
                'key' => 'action_required',
                'label' => 'Action Required',
                'hint' => 'Unpaid or needs attention',
                'count' => null,
                'active' => $activeWorkflow === 'action_required',
            ],
            [
                'key' => 'ready_to_buy',
                'label' => 'Ready To Buy',
                'hint' => 'Paid, not purchased',
                'count' => null,
                'active' => $activeWorkflow === 'ready_to_buy',
            ],
            [
                'key' => 'purchasing',
                'label' => 'Purchasing',
                'hint' => 'Part purchased',
                'count' => null,
                'active' => $activeWorkflow === 'purchasing',
            ],
            [
                'key' => 'incoming',
                'label' => 'Incoming',
                'hint' => 'Purchased, awaiting arrival',
                'count' => null,
                'active' => $activeWorkflow === 'incoming',
            ],
            [
                'key' => 'complete',
                'label' => 'Complete',
                'hint' => 'Arrived or completed',
                'count' => null,
                'active' => $activeWorkflow === 'complete',
            ],
        ]);
    }

    private function applyWorkflowFilter($query, string $workflow): void
    {
        match ($workflow) {
            'ready_to_buy' => $query
                ->whereRaw('GREATEST(orders.grand_total - COALESCE(settlement_totals.settled_total, 0), 0) <= 0.004')
                ->where(function ($readyQuery) {
                    $readyQuery
                        ->whereRaw('COALESCE(item_totals.total_qty, 0) = 0')
                        ->orWhereRaw('COALESCE(purchase_totals.purchased_qty, 0) = 0');
                })
                ->whereNotIn('orders.status', ['completed', 'cancelled', 'canceled']),
            'purchasing' => $query
                ->whereRaw('COALESCE(item_totals.total_qty, 0) > 0')
                ->whereRaw('COALESCE(purchase_totals.purchased_qty, 0) > 0')
                ->whereRaw('COALESCE(purchase_totals.purchased_qty, 0) < COALESCE(item_totals.total_qty, 0)')
                ->whereNotIn('orders.status', ['completed', 'cancelled', 'canceled']),
            'incoming' => $query
                ->whereRaw('COALESCE(item_totals.total_qty, 0) > 0')
                ->whereRaw('COALESCE(purchase_totals.purchased_qty, 0) >= COALESCE(item_totals.total_qty, 0)')
                ->whereRaw('COALESCE(arrival_totals.arrived_qty, 0) < COALESCE(item_totals.total_qty, 0)')
                ->whereNotIn('orders.status', ['completed', 'cancelled', 'canceled']),
            'complete' => $query
                ->where(function ($completeQuery) {
                    $completeQuery
                        ->whereIn('orders.status', ['completed', 'complete'])
                        ->orWhere(function ($arrivedQuery) {
                            $arrivedQuery
                                ->whereRaw('COALESCE(item_totals.total_qty, 0) > 0')
                                ->whereRaw('COALESCE(arrival_totals.arrived_qty, 0) >= COALESCE(item_totals.total_qty, 0)');
                        });
                }),
            default => $query
                ->where(function ($actionQuery) {
                    $actionQuery
                        ->whereRaw('GREATEST(orders.grand_total - COALESCE(settlement_totals.settled_total, 0), 0) > 0.004')
                        ->orWhereIn('orders.status', ['unpaid', 'draft', 'pending', 'on_hold', 'review_required'])
                        ->orWhere(function ($cancelQuery) {
                            $cancelQuery
                                ->whereIn('orders.status', ['cancelled', 'canceled'])
                                ->whereNull('orders.completed_at');
                        });
                }),
        };
    }


    public function find(int $orderId): ?object
    {
        return DB::table('orders')
            ->join('draft_orders', 'draft_orders.id', '=', 'orders.draft_order_id')
            ->join('customers', 'customers.id', '=', 'draft_orders.customer_id')
            ->leftJoin('order_requests', 'order_requests.id', '=', 'draft_orders.order_request_id')
            ->leftJoin('countries as bill_country', 'bill_country.id', '=', 'orders.bill_to_country_id')
            ->select([
                'orders.id',
                'orders.draft_order_id',
                'orders.source_draft_order_id',
                'draft_orders.order_request_id',
                'draft_orders.draft_number',
                'order_requests.request_ref as order_request_ref',
                'orders.parent_order_id',
                DB::raw("(SELECT COUNT(*) FROM orders as revision_orders WHERE revision_orders.order_number = orders.order_number AND revision_orders.id <= orders.id) as revision_number"),
                DB::raw("(SELECT COUNT(*) FROM orders as revision_orders WHERE revision_orders.order_number = orders.order_number) as revision_total"),
                DB::raw("CASE WHEN orders.status = 'superseded' OR orders.cancel_reason = 'superseded' OR EXISTS (SELECT 1 FROM orders newer_orders WHERE newer_orders.order_number = orders.order_number AND newer_orders.id > orders.id AND newer_orders.status != 'superseded' AND (newer_orders.cancel_reason IS NULL OR newer_orders.cancel_reason != 'superseded')) THEN 'superseded' WHEN (SELECT COUNT(*) FROM orders revision_orders WHERE revision_orders.order_number = orders.order_number) > 1 THEN 'current_revision' ELSE 'current' END as revision_state"),
                'orders.order_type',
                'orders.purchase_mode',
                'orders.order_number',
                'orders.draft_order_id',
                'orders.status',
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
                'orders.bill_to_country_id',
                'bill_country.name as bill_to_country_name',
                'bill_country.phone_code as bill_to_country_phone_code',
                DB::raw("(SELECT pc.phone_code FROM customer_phones cpx JOIN phones px ON px.id = cpx.phone_id LEFT JOIN countries pc ON pc.id = px.country_id WHERE cpx.customer_id = customers.id AND cpx.is_active = 1 ORDER BY cpx.is_primary DESC, cpx.id ASC LIMIT 1) as customer_phone_country_code"),
                'orders.invoiced_at',
                'orders.sent_at',
                'orders.paid_at',
                'orders.purchased_at',
                'orders.shipped_at',
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


    public function requestAttachments(object $order): Collection
    {
        $orderRequestId = (int) ($order->order_request_id ?? 0);

        if ($orderRequestId <= 0) {
            return collect();
        }

        return DB::table('order_request_attachments')
            ->where('order_request_id', $orderRequestId)
            ->orderBy('id')
            ->get();
    }

    public function revisionHistory(object $order): Collection
    {
        return DB::table('orders')
            ->select([
                'orders.id',
                'orders.draft_order_id',
                'orders.order_number',
                'orders.status',
                'orders.purchase_mode',
                'orders.grand_total',
                'orders.created_at',
                'orders.cancel_reason',
                DB::raw("(SELECT COUNT(*) FROM orders as revision_orders WHERE revision_orders.order_number = orders.order_number AND revision_orders.id <= orders.id) as revision_number"),
                DB::raw("(SELECT COUNT(*) FROM orders as revision_orders WHERE revision_orders.order_number = orders.order_number) as revision_total"),
                DB::raw("CASE WHEN orders.status = 'superseded' OR orders.cancel_reason = 'superseded' OR EXISTS (SELECT 1 FROM orders newer_orders WHERE newer_orders.order_number = orders.order_number AND newer_orders.id > orders.id AND newer_orders.status != 'superseded' AND (newer_orders.cancel_reason IS NULL OR newer_orders.cancel_reason != 'superseded')) THEN 'superseded' WHEN (SELECT COUNT(*) FROM orders revision_orders WHERE revision_orders.order_number = orders.order_number) > 1 THEN 'current_revision' ELSE 'current' END as revision_state"),
                DB::raw("(SELECT body FROM activity_logs WHERE activity_logs.subject_type = 'order' AND activity_logs.subject_id = orders.id AND activity_logs.type = 'system_note' ORDER BY activity_logs.id ASC LIMIT 1) as revision_note"),
            ])
            ->where('orders.order_number', $order->order_number)
            ->orderBy('orders.id')
            ->get();
    }

    public function financeSummary(int $orderId): array
    {
        $order = DB::table('orders')
            ->join('draft_orders', 'draft_orders.id', '=', 'orders.draft_order_id')
            ->select([
                'orders.id',
                'orders.grand_total',
                'draft_orders.customer_id',
            ])
            ->where('orders.id', $orderId)
            ->first();

        if (! $order) {
            return [
                'order_total' => 0,
                'settled_total' => 0,
                'balance_due' => 0,
                'payments_used' => 0,
                'wallet_used' => 0,
                'refunds' => 0,
                'wallet_credit_from_overpayments' => 0,
                'wallet_credit_from_revisions' => 0,
                'wallet_attention_total' => 0,
                'wallet_attention_sources' => [],
                'wallet_available' => 0,
            ];
        }

        $paymentsUsed = (float) DB::table('order_transactions')
            ->where('order_id', $orderId)
            ->where('status', 'recorded')
            ->whereIn('type', ['payment', 'payment_void'])
            ->sum('amount');

        $walletUsed = (float) DB::table('order_transactions')
            ->where('order_id', $orderId)
            ->where('status', 'recorded')
            ->whereIn('type', ['credit_application', 'credit_application_void'])
            ->sum('amount');

        $refunds = (float) DB::table('order_transactions')
            ->where('order_id', $orderId)
            ->where('status', 'recorded')
            ->whereIn('type', ['refund', 'refund_void'])
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

        $walletCreditFromOverpayments = (float) DB::table('customer_credits')
            ->where('order_id', $orderId)
            ->whereIn('source_type', ['overpayment', 'payment_overpayment'])
            ->whereIn('status', ['open', 'part_used'])
            ->sum('remaining_amount');

        $walletCreditFromRevisions = (float) DB::table('customer_credits')
            ->where('order_id', $orderId)
            ->whereIn('source_type', ['superseded_order_balance', 'order_revision_credit', 'revision_overpayment'])
            ->whereIn('status', ['open', 'part_used'])
            ->sum('remaining_amount');

        $walletAttentionSources = [];
        if ($walletCreditFromRevisions > 0.004) {
            $walletAttentionSources[] = 'order revision / superseded order balance';
        }
        if ($walletCreditFromOverpayments > 0.004) {
            $walletAttentionSources[] = 'overpayment';
        }
        $walletAttentionTotal = $walletCreditFromOverpayments + $walletCreditFromRevisions;

        $walletAvailable = (float) DB::table('customer_credits')
            ->where('customer_id', $order->customer_id)
            ->whereIn('status', ['open', 'part_used'])
            ->sum('remaining_amount');

        $orderTotal = (float) $order->grand_total;

        return [
            'order_total' => $orderTotal,
            'settled_total' => $settledTotal,
            'balance_due' => max(0, $orderTotal - $settledTotal),
            'payments_used' => $paymentsUsed,
            'wallet_used' => $walletUsed,
            'refunds' => $refunds,
            'wallet_credit_from_overpayments' => $walletCreditFromOverpayments,
            'wallet_credit_from_revisions' => $walletCreditFromRevisions,
            'wallet_attention_total' => $walletAttentionTotal,
            'wallet_attention_sources' => $walletAttentionSources,
            'wallet_available' => $walletAvailable,
        ];
    }

    public function paymentTimeline(int $orderId): Collection
    {
        $transactionRows = DB::table('order_transactions')
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
                DB::raw("'order_transaction' as source_table"),
                DB::raw("EXISTS (SELECT 1 FROM order_transactions voids WHERE voids.order_id = order_transactions.order_id AND voids.type = 'payment_void' AND voids.reference = CONCAT('OT#', order_transactions.id) AND voids.status = 'recorded') as has_void"),
            ])
            ->where('order_transactions.order_id', $orderId)
            ->get();

        $ledgerIdsAlreadyShown = $transactionRows
            ->filter(fn ($row) => ($row->type ?? '') === 'payment')
            ->map(function ($row) {
                if (preg_match('/Ledger #(\d+)/', (string) ($row->note ?? ''), $matches)) {
                    return (int) $matches[1];
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();

        $ledgerRows = DB::table('customer_ledger_entries')
            ->leftJoin('payment_types', 'payment_types.id', '=', 'customer_ledger_entries.payment_type_id')
            ->select([
                'customer_ledger_entries.id',
                DB::raw("'ledger_payment' as type"),
                'customer_ledger_entries.amount',
                'customer_ledger_entries.currency',
                'customer_ledger_entries.status',
                'customer_ledger_entries.occurred_at as received_at',
                DB::raw("NULL as method"),
                DB::raw("NULL as channel"),
                DB::raw("NULL as provider"),
                'customer_ledger_entries.reference',
                'customer_ledger_entries.note',
                'customer_ledger_entries.created_at',
                'payment_types.name as payment_type_name',
                DB::raw("'customer_ledger_entry' as source_table"),
                DB::raw("EXISTS (SELECT 1 FROM customer_ledger_entries voids WHERE voids.customer_id = customer_ledger_entries.customer_id AND voids.type = 'payment_void' AND voids.reference = CONCAT('LE#', customer_ledger_entries.id) AND voids.status = 'recorded') as has_void"),
            ])
            ->where('customer_ledger_entries.source_type', 'order')
            ->where('customer_ledger_entries.source_id', $orderId)
            ->where('customer_ledger_entries.type', 'payment_received')
            ->when(! empty($ledgerIdsAlreadyShown), fn ($query) => $query->whereNotIn('customer_ledger_entries.id', $ledgerIdsAlreadyShown))
            ->get();

        return $transactionRows
            ->merge($ledgerRows)
            ->sortByDesc(fn ($row) => (string) ($row->created_at ?? $row->received_at ?? ''))
            ->values();
    }


    public function invoiceWorkspace(int $orderId): array
    {
        $invoice = DB::table('invoices')
            ->where('order_id', $orderId)
            ->orderByDesc('id')
            ->first();

        if (! $invoice) {
            return [
                'invoice' => null,
                'latest_version' => null,
                'versions' => collect(),
            ];
        }

        $versions = DB::table('invoice_versions')
            ->leftJoin('users as issued_user', 'issued_user.id', '=', 'invoice_versions.issued_by_user_id')
            ->select([
                'invoice_versions.*',
                'issued_user.name as issued_by_name',
            ])
            ->where('invoice_versions.order_id', $orderId)
            ->orderByDesc('invoice_versions.version')
            ->get();

        return [
            'invoice' => $invoice,
            'latest_version' => $versions->first(),
            'versions' => $versions,
        ];
    }

    public function progressSummary(int $orderId): array
    {
        return $this->purchaseProgressSummary->forOrder($orderId);
    }

    public function itemsGroupedByRetailer(int $orderId): Collection
    {
        return $this->purchaseWorkbenchQuery->retailerGroupsForOrder($orderId);
    }

    public function items(int $orderId): Collection
    {
        return $this->purchaseWorkbenchQuery->itemsForOrder($orderId);
    }

    public function purchases(int $orderId): Collection
    {
        return $this->purchaseWorkbenchQuery->purchasesForOrder($orderId);
    }

    public function arrivals(int $orderId): Collection
    {
        $rootItemIds = $this->rootItemIdsForOrder($orderId);

        if ($rootItemIds->isEmpty()) {
            return collect();
        }

        return DB::table('purchase_arrival_assignments')
            ->join('order_items', 'order_items.id', '=', 'purchase_arrival_assignments.order_item_id')
            ->join('order_item_purchases', 'order_item_purchases.id', '=', 'purchase_arrival_assignments.order_item_purchase_id')
            ->select([
                'purchase_arrival_assignments.id',
                'purchase_arrival_assignments.order_item_id',
                'order_items.item_name',
                'purchase_arrival_assignments.qty',
                'purchase_arrival_assignments.status',
                'purchase_arrival_assignments.matched_at',
                'purchase_arrival_assignments.status_updated_at',
                'purchase_arrival_assignments.notes',
                'purchase_arrival_assignments.undone_at',
                'order_item_purchases.retailer_order_reference',
                'order_item_purchases.requires_marking_attention',
                DB::raw("(SELECT MIN(COALESCE(al.occurred_at, al.created_at)) FROM activity_logs al WHERE al.subject_type = 'purchase_arrival_assignment' AND al.subject_id = purchase_arrival_assignments.id AND al.deleted_at IS NULL AND (al.body LIKE '%to ready_for_collection%' OR al.body LIKE '%to for_delivery%')) as informed_at"),
                DB::raw("(SELECT MAX(COALESCE(al.occurred_at, al.created_at)) FROM activity_logs al WHERE al.subject_type = 'purchase_arrival_assignment' AND al.subject_id = purchase_arrival_assignments.id AND al.deleted_at IS NULL AND (al.body LIKE '%to collected%' OR al.body LIKE '%to delivered%')) as completed_at_from_logs"),
            ])
            ->whereIn('purchase_arrival_assignments.root_item_id', $rootItemIds->all())
            ->whereNull('purchase_arrival_assignments.undone_at')
            ->orderByDesc('purchase_arrival_assignments.matched_at')
            ->limit(80)
            ->get()
            ->map(function ($arrival) {
                $status = (string) ($arrival->status ?? '');

                if (empty($arrival->informed_at) && in_array($status, ['ready_for_collection', 'for_delivery'], true)) {
                    $arrival->informed_at = $arrival->status_updated_at ?: $arrival->matched_at;
                }

                $arrival->completed_at = $arrival->completed_at_from_logs;
                if (empty($arrival->completed_at) && in_array($status, ['collected', 'delivered'], true)) {
                    $arrival->completed_at = $arrival->status_updated_at ?: $arrival->matched_at;
                }

                $arrival->completion_label = $status === 'delivered' ? 'Delivered' : (in_array($status, ['collected', 'ready_for_collection', 'for_delivery'], true) ? 'Collected' : 'Completed');

                return $arrival;
            });
    }

    public function notes(object $order): Collection
    {
        return DB::table('activity_logs')
            ->select([
                'id',
                'subject_type',
                'subject_id',
                'type',
                'title',
                'body',
                'occurred_at',
                'created_at',
                'is_pinned',
            ])
            ->whereNull('deleted_at')
            ->where(function ($query) use ($order) {
                $query
                    ->where(function ($orderQuery) use ($order) {
                        $orderQuery
                            ->whereIn('subject_type', ['order', 'App\\Models\\Order'])
                            ->where('subject_id', $order->id);
                    })
                    ->orWhere(function ($draftQuery) use ($order) {
                        $draftQuery
                            ->where('subject_type', 'draft_order')
                            ->where('subject_id', $order->draft_order_id);
                    });
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc(DB::raw('COALESCE(occurred_at, created_at)'))
            ->limit(40)
            ->get();
    }
    private function rootItemIdsForOrder(int $orderId): Collection
    {
        return $this->purchaseProgressSummary->rootItemIdsForOrder($orderId);
    }

    private function hostFromUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! $host && ! str_starts_with($url, 'http')) {
            $host = parse_url('https://' . $url, PHP_URL_HOST);
        }

        $host = strtolower((string) $host);

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host;
    }

}