<?php

namespace App\Http\Controllers;

use App\Support\Purchasing\PurchaseActionService;
use App\Support\Purchasing\PurchaseWorkbenchQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurchasingDeskController extends Controller
{
    public function __construct(
        private readonly PurchaseWorkbenchQuery $workbench,
        private readonly PurchaseActionService $actions,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'tab' => trim((string) $request->query('tab', $request->query('status', 'to_buy'))),
            'payment' => trim((string) $request->query('payment', 'paid')),
            'limit' => 500,
        ];

        if (! in_array($filters['tab'], ['to_buy', 'problems', 'awaiting_arrival', 'all'], true)) {
            $filters['tab'] = 'to_buy';
        }

        if (! in_array($filters['payment'], ['paid', 'part_paid', 'unpaid', 'all'], true)) {
            $filters['payment'] = 'paid';
        }

        return view('purchasing.index', [
            'filters' => $filters,
            'summary' => $this->workbench->deskSummary($filters),
            'orderGroups' => $this->workbench->orderGroupsForPurchasingDesk($filters),
            'recentEvents' => $this->workbench->recentPurchaseEvents($filters),
        ]);
    }


    public function showOrder(Request $request, int $order): View
    {
        $orderRecord = DB::table('orders')
            ->select([
                'orders.id',
                'orders.order_number',
                'orders.status',
                'orders.purchase_mode',
                'orders.bill_to_name',
                'orders.bill_to_company',
                'orders.bill_to_email',
                'orders.grand_total',
                'orders.cancelled_at',
                'orders.completed_at',
                DB::raw("COALESCE(settlement_totals.settled_amount, 0) as settled_amount"),
                DB::raw("CASE WHEN COALESCE(settlement_totals.settled_amount,0) >= orders.grand_total AND orders.grand_total > 0 THEN 'paid' WHEN COALESCE(settlement_totals.settled_amount,0) > 0 THEN 'part_paid' ELSE 'unpaid' END as payment_status"),
            ])
            ->leftJoinSub(
                DB::table('order_transactions')
                    ->select([
                        'order_id',
                        DB::raw("SUM(CASE
                            WHEN status = 'recorded' AND type IN ('payment', 'credit_application') THEN amount
                            WHEN status = 'recorded' AND type IN ('payment_void', 'credit_application_void', 'refund') THEN -ABS(amount)
                            WHEN status = 'recorded' AND type = 'refund_void' THEN ABS(amount)
                            ELSE 0
                        END) as settled_amount"),
                    ])
                    ->groupBy('order_id'),
                'settlement_totals',
                fn ($join) => $join->on('settlement_totals.order_id', '=', 'orders.id')
            )
            ->where('orders.id', $order)
            ->first();

        abort_if(! $orderRecord, 404);

        $workspace = $this->workbench->forOrder($order);
        $retailerGroups = collect($workspace['retailer_groups'] ?? []);
        $items = collect($workspace['items'] ?? []);
        $purchaseEvents = collect($workspace['purchases'] ?? []);

        $activeTab = trim((string) $request->query('tab', 'overview'));
        if (! in_array($activeTab, ['overview', 'to_buy', 'awaiting_arrival', 'problems', 'timeline'], true)) {
            $activeTab = 'overview';
        }

        return view('purchasing.show', [
            'order' => $orderRecord,
            'activeTab' => $activeTab,
            'summary' => $workspace['summary'] ?? [],
            'items' => $items,
            'retailerGroups' => $retailerGroups,
            'purchaseEvents' => $purchaseEvents,
            'problemEvents' => $purchaseEvents->filter(fn ($event) => in_array((string) ($event->status ?? ''), [
                'failed', 'problem', 'supplier_problem', 'supplier_cancelled', 'cancelled', 'refunded', 'retailer_refunded', 'lost', 'damaged', 'wrong_item', 'unavailable', 'unfulfilled',
            ], true))->values(),
            'toBuyItems' => $items->filter(fn ($item) => (int) ($item->purchase_remaining_qty ?? 0) > 0)->values(),
            'arrivalItems' => $items->filter(fn ($item) => (int) ($item->arrival_remaining_qty ?? 0) > 0)->values(),
            'problemItems' => $items->filter(fn ($item) => (int) ($item->problem_qty ?? 0) > 0)->values(),
        ]);
    }

    public function storePurchase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'qty' => ['required', 'integer', 'min:1'],
            'purchase_unit_price' => ['nullable', 'numeric', 'min:0'],
            'purchase_line_total' => ['nullable', 'numeric', 'min:0'],
            'retailer_order_reference' => ['nullable', 'string', 'max:255'],
            'marketplace_seller' => ['nullable', 'string', 'max:255'],
            'ordered_at' => ['nullable', 'date'],
            'expected_dispatch_at' => ['nullable', 'date'],
            'expected_uk_hub_at' => ['nullable', 'date'],
            'expected_gibraltar_at' => ['nullable', 'date'],
            'requires_marking_attention' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['user_id'] = $request->user()?->id;
        $validated['currency'] = 'GBP';

        $this->actions->recordPurchase($validated);

        return back()->with('status', 'Purchase recorded.')->with('success', 'Purchase recorded.');
    }

    public function storeProblem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'qty' => ['required', 'integer', 'min:1'],
            'problem_code' => ['required', Rule::in([
                'supplier_cancelled',
                'lost',
                'damaged',
                'wrong_item',
                'retailer_refunded',
                'unavailable',
                'other',
            ])],
            'retailer_order_reference' => ['nullable', 'string', 'max:255'],
            'marketplace_seller' => ['nullable', 'string', 'max:255'],
            'problem_notes' => ['nullable', 'string', 'max:2000'],
            'resolution_action' => ['nullable', Rule::in(['customer_decision_required', 'repurchase', 'refund_required', 'remove_or_credit', 'replacement', 'replaced_via_amendment', 'wait_for_retailer', 'other'])],
        ]);

        $validated['user_id'] = $request->user()?->id;
        $validated['currency'] = 'GBP';
        $validated['resolution_status'] = 'pending';

        $this->actions->recordProblem($validated);

        return back()->with('status', 'Purchasing problem recorded. Finance was not changed.')->with('success', 'Purchasing problem recorded. Finance was not changed.');
    }

    public function undoEvent(Request $request, int $purchase): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->actions->undoPurchase($purchase, $request->user()?->id, $validated['reason'] ?? null);

        return back()->with('status', 'Purchasing event undone.')->with('success', 'Purchasing event undone.');
    }
}
