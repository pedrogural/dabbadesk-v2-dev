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
