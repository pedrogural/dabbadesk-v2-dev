<?php

namespace App\Http\Controllers;

use App\Services\Purchasing\PurchasingQueueService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchasingController extends Controller
{
    public function index(Request $request, PurchasingQueueService $service)
    {
        return view('purchasing.index', $service->queue([
            'tab' => $request->query('tab', 'to_buy'),
            'payment' => $request->query('payment', 'paid_or_part'),
            'q' => $request->query('q', ''),
            'mine' => $request->boolean('mine'),
            'purchased_problem_view' => $request->query('problem_view', 'items'),
            'user_id' => Auth::id(),
        ]));
    }

    public function show(int $order, Request $request, PurchasingQueueService $service)
    {
        $workspace = $service->workspace($order);

        abort_if(! $workspace, 404);

        $workspace['activeTab'] = in_array($request->query('tab'), array_keys($workspace['tabs']), true)
            ? $request->query('tab')
            : 'overview';

        return view('purchasing.show', $workspace);
    }

    public function storePurchase(Request $request)
    {
        $validated = $request->validate([
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'qty' => ['required', 'integer', 'min:1', 'max:999'],
            'purchase_unit_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'purchased_retailer_id' => ['nullable', 'integer', 'exists:retailers,id'],
            'retailer_order_reference' => ['required', 'string', 'max:255'],
            'marketplace_seller' => ['nullable', 'string', 'max:255'],
            'ordered_at' => ['nullable', 'date'],
            'expected_uk_hub_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $item = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->leftJoin('order_retailers as ore', 'ore.id', '=', 'oi.order_retailer_id')
            ->select([
                'oi.*',
                'o.order_number',
                'o.purchase_mode',
                'ore.retailer_id',
            ])
            ->where('oi.id', (int) $validated['order_item_id'])
            ->first();

        abort_if(! $item, 404);
        $this->assertOrderIsMutable((int) $item->order_id);

        if (($item->purchase_mode ?? '') === 'customer_self_purchase') {
            throw ValidationException::withMessages([
                'purchase' => 'This is a customer self-purchase order. Dabba should not record buying events for it.',
            ]);
        }

        $rootItemId = (int) ($item->root_item_id ?: $item->id);
        $remaining = $this->remainingToBuyQty($rootItemId, (int) $item->quantity);
        $qty = (int) $validated['qty'];

        if ($qty > $remaining) {
            throw ValidationException::withMessages([
                'qty' => 'Only ' . $remaining . ' item' . ($remaining === 1 ? '' : 's') . ' remain to be purchased.',
            ]);
        }

        $unitPrice = round((float) $validated['purchase_unit_price'], 2);
        $lineTotal = round($unitPrice * $qty, 2);
        $orderedAt = ! empty($validated['ordered_at']) ? Carbon::parse($validated['ordered_at']) : now();
        $expectedHubAt = ! empty($validated['expected_uk_hub_at']) ? Carbon::parse($validated['expected_uk_hub_at']) : null;
        $userId = Auth::id();
        $purchaseRetailerId = ! empty($validated['purchased_retailer_id']) ? (int) $validated['purchased_retailer_id'] : (int) ($item->retailer_id ?? 0);

        DB::transaction(function () use ($item, $validated, $rootItemId, $qty, $unitPrice, $lineTotal, $orderedAt, $expectedHubAt, $userId, $purchaseRetailerId) {
            $purchaseId = DB::table('order_item_purchases')->insertGetId([
                'order_item_id' => (int) $item->id,
                'root_item_id' => $rootItemId,
                'order_id' => (int) $item->order_id,
                'order_retailer_id' => $item->order_retailer_id,
                'retailer_id' => $purchaseRetailerId ?: $item->retailer_id,
                'qty' => $qty,
                'status' => 'purchased',
                'purchase_unit_price' => $unitPrice,
                'purchase_line_total' => $lineTotal,
                'currency' => $item->purchase_currency ?: 'GBP',
                'marketplace_seller' => trim((string) ($validated['marketplace_seller'] ?? '')) ?: ($item->marketplace_seller ?: null),
                'retailer_order_reference' => trim((string) ($validated['retailer_order_reference'] ?? '')) ?: null,
                'note' => trim((string) ($validated['note'] ?? '')) ?: null,
                'ordered_at' => $orderedAt,
                'expected_uk_hub_at' => $expectedHubAt,
                'resolution_status' => null,
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('order_items')
                ->where('id', (int) $item->id)
                ->update([
                    'status' => 'purchased',
                    'purchase_price' => $unitPrice ?? $item->purchase_price,
                    'purchased_at' => $orderedAt,
                    'retailer_order_reference' => trim((string) ($validated['retailer_order_reference'] ?? '')) ?: $item->retailer_order_reference,
                    'marketplace_seller' => trim((string) ($validated['marketplace_seller'] ?? '')) ?: $item->marketplace_seller,
                    'purchase_problem_reason' => null,
                    'purchase_problem_note' => null,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            DB::table('order_item_purchases')
                ->where('root_item_id', $rootItemId)
                ->whereIn('status', ['unfulfilled', 'failed', 'problem', 'supplier_problem', 'supplier_cancelled', 'cancelled', 'refunded', 'retailer_refunded', 'lost', 'damaged', 'wrong_item', 'unavailable'])
                ->whereNull('cancelled_at')
                ->where(function ($query) {
                    $query->whereNull('resolution_status')
                        ->orWhere('resolution_status', 'pending');
                })
                ->update([
                    'resolution_status' => 'resolved',
                    'resolution_action' => 'purchased_successfully',
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);


            DB::table('purchase_issues')
                ->where('root_item_id', $rootItemId)
                ->whereIn('status', ['open', 'awaiting_customer', 'returned_to_buy'])
                ->update([
                    'status' => 'resolved',
                    'resolution_type' => 'purchased_successfully',
                    'resolution_notes' => 'Resolved automatically when purchase was recorded.',
                    'resolved_at' => now(),
                    'resolved_by_user_id' => $userId,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            DB::table('activity_logs')->insert([
                'subject_type' => 'order',
                'subject_id' => (int) $item->order_id,
                'type' => 'purchasing',
                'is_pinned' => 0,
                'title' => 'Purchase recorded',
                'body' => 'Purchase #' . $purchaseId . ' recorded for item #' . $item->id . ' on Order #' . $item->order_number . '. Qty ' . $qty . '.',
                'occurred_at' => now(),
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('purchasing.orders.show', ['order' => (int) $item->order_id, 'tab' => 'buy'])
            ->with('success', 'Purchase recorded.');
    }


    public function storeBulkPurchase(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'order_item_ids' => ['required', 'array', 'min:1'],
            'order_item_ids.*' => ['integer', 'exists:order_items,id'],
            'qty' => ['required', 'array'],
            'qty.*' => ['nullable', 'integer', 'min:0', 'max:999'],
            'purchase_unit_price' => ['nullable', 'array'],
            'purchase_unit_price.*' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'purchased_retailer_id' => ['nullable', 'integer', 'exists:retailers,id'],
            'retailer_order_reference' => ['required', 'string', 'max:255'],
            'marketplace_seller' => ['nullable', 'string', 'max:255'],
            'ordered_at' => ['nullable', 'date'],
            'expected_uk_hub_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $orderId = (int) $validated['order_id'];
        $this->assertOrderIsMutable($orderId);
        $itemIds = collect($validated['order_item_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        $items = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->leftJoin('order_retailers as ore', 'ore.id', '=', 'oi.order_retailer_id')
            ->select([
                'oi.*',
                'o.order_number',
                'o.purchase_mode',
                'ore.retailer_id',
            ])
            ->where('oi.order_id', $orderId)
            ->whereIn('oi.id', $itemIds)
            ->orderBy('oi.sort_order')
            ->get();

        abort_if($items->count() !== $itemIds->count(), 404);

        $first = $items->first();
        abort_if(! $first, 404);

        if (($first->purchase_mode ?? '') === 'customer_self_purchase') {
            throw ValidationException::withMessages([
                'purchase' => 'This is a customer self-purchase order. Dabba should not record buying events for it.',
            ]);
        }

        $retailerIds = $items->pluck('retailer_id')->filter(fn ($value) => $value !== null)->unique()->values();
        if ($retailerIds->count() > 1) {
            throw ValidationException::withMessages([
                'purchase' => 'Bulk purchase can only be recorded for one retailer section at a time.',
            ]);
        }

        $qtyInput = collect($validated['qty'] ?? []);
        $unitInput = collect($validated['purchase_unit_price'] ?? []);
        $lines = [];

        foreach ($items as $item) {
            $itemId = (int) $item->id;
            $qty = (int) ($qtyInput->get((string) $itemId, $qtyInput->get($itemId, 0)) ?: 0);

            if ($qty <= 0) {
                continue;
            }

            $rootItemId = (int) ($item->root_item_id ?: $item->id);
            $remaining = $this->remainingToBuyQty($rootItemId, (int) $item->quantity);

            if ($qty > $remaining) {
                throw ValidationException::withMessages([
                    'qty.' . $itemId => 'Only ' . $remaining . ' item' . ($remaining === 1 ? '' : 's') . ' remain to be purchased for item #' . $itemId . '.',
                ]);
            }

            $rawUnitPrice = $unitInput->get((string) $itemId, $unitInput->get($itemId));
            if ($rawUnitPrice === null || $rawUnitPrice === '') {
                throw ValidationException::withMessages([
                    'purchase_unit_price.' . $itemId => 'Purchase price is required for each selected item.',
                ]);
            }

            $unitPrice = round((float) $rawUnitPrice, 2);

            $lines[] = [
                'item' => $item,
                'root_item_id' => $rootItemId,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice !== null ? round($unitPrice * $qty, 2) : null,
            ];
        }

        if (count($lines) === 0) {
            throw ValidationException::withMessages([
                'qty' => 'Choose at least one item quantity to record in this bulk purchase.',
            ]);
        }

        $orderedAt = ! empty($validated['ordered_at']) ? Carbon::parse($validated['ordered_at']) : now();
        $expectedHubAt = ! empty($validated['expected_uk_hub_at']) ? Carbon::parse($validated['expected_uk_hub_at']) : null;
        $userId = Auth::id();
        $reference = trim((string) ($validated['retailer_order_reference'] ?? '')) ?: null;
        $seller = trim((string) ($validated['marketplace_seller'] ?? '')) ?: null;
        $note = trim((string) ($validated['note'] ?? '')) ?: null;
        $purchaseRetailerId = ! empty($validated['purchased_retailer_id']) ? (int) $validated['purchased_retailer_id'] : null;
        $createdPurchaseIds = [];
        $totalQty = 0;

        DB::transaction(function () use ($lines, $reference, $seller, $note, $orderedAt, $expectedHubAt, $userId, $purchaseRetailerId, &$createdPurchaseIds, &$totalQty) {
            foreach ($lines as $line) {
                $item = $line['item'];
                $qty = (int) $line['qty'];
                $totalQty += $qty;

                $purchaseId = DB::table('order_item_purchases')->insertGetId([
                    'order_item_id' => (int) $item->id,
                    'root_item_id' => (int) $line['root_item_id'],
                    'order_id' => (int) $item->order_id,
                    'order_retailer_id' => $item->order_retailer_id,
                    'retailer_id' => $purchaseRetailerId ?: $item->retailer_id,
                    'qty' => $qty,
                    'status' => 'purchased',
                    'purchase_unit_price' => $line['unit_price'],
                    'purchase_line_total' => $line['line_total'],
                    'currency' => $item->purchase_currency ?: 'GBP',
                    'marketplace_seller' => $seller ?: ($item->marketplace_seller ?: null),
                    'retailer_order_reference' => $reference,
                    'note' => $note,
                    'ordered_at' => $orderedAt,
                    'expected_uk_hub_at' => $expectedHubAt,
                    'resolution_status' => null,
                    'created_by_user_id' => $userId,
                    'updated_by_user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $createdPurchaseIds[] = $purchaseId;

                DB::table('order_items')
                    ->where('id', (int) $item->id)
                    ->update([
                        'status' => 'purchased',
                        'purchase_price' => $line['unit_price'] ?? $item->purchase_price,
                        'purchased_at' => $orderedAt,
                        'retailer_order_reference' => $reference ?: $item->retailer_order_reference,
                        'marketplace_seller' => $seller ?: $item->marketplace_seller,
                        'purchase_problem_reason' => null,
                        'purchase_problem_note' => null,
                        'updated_by_user_id' => $userId,
                        'updated_at' => now(),
                    ]);

                DB::table('order_item_purchases')
                    ->where('root_item_id', (int) $line['root_item_id'])
                    ->whereIn('status', ['unfulfilled', 'failed', 'problem', 'supplier_problem', 'supplier_cancelled', 'cancelled', 'refunded', 'retailer_refunded', 'lost', 'damaged', 'wrong_item', 'unavailable'])
                    ->whereNull('cancelled_at')
                    ->where(function ($query) {
                        $query->whereNull('resolution_status')
                            ->orWhere('resolution_status', 'pending');
                    })
                    ->update([
                        'resolution_status' => 'resolved',
                        'resolution_action' => 'purchased_successfully',
                        'updated_by_user_id' => $userId,
                        'updated_at' => now(),
                    ]);


                DB::table('purchase_issues')
                    ->where('root_item_id', (int) $line['root_item_id'])
                    ->whereIn('status', ['open', 'awaiting_customer', 'returned_to_buy'])
                    ->update([
                        'status' => 'resolved',
                        'resolution_type' => 'purchased_successfully',
                        'resolution_notes' => 'Resolved automatically when bulk purchase was recorded.',
                        'resolved_at' => now(),
                        'resolved_by_user_id' => $userId,
                        'updated_by_user_id' => $userId,
                        'updated_at' => now(),
                    ]);
            }

            $firstItem = $lines[0]['item'];
            DB::table('activity_logs')->insert([
                'subject_type' => 'order',
                'subject_id' => (int) $firstItem->order_id,
                'type' => 'purchasing',
                'is_pinned' => 0,
                'title' => 'Bulk purchase recorded',
                'body' => 'Bulk purchase recorded for Order #' . $firstItem->order_number . '. ' . count($createdPurchaseIds) . ' item line' . (count($createdPurchaseIds) === 1 ? '' : 's') . ', qty ' . $totalQty . '. Ref: ' . ($reference ?: '—') . '.',
                'occurred_at' => now(),
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('purchasing.orders.show', ['order' => $orderId, 'tab' => 'buy'])
            ->with('success', 'Bulk purchase recorded for ' . count($createdPurchaseIds) . ' item line' . (count($createdPurchaseIds) === 1 ? '' : 's') . '.');
    }


    public function storeProblem(Request $request)
    {
        $validated = $request->validate([
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'qty' => ['required', 'integer', 'min:1', 'max:999'],
            'issue_type' => ['required', 'string', 'max:50'],
            'issue_stage' => ['nullable', 'string', 'max:30'],
            'arrival_expectation' => ['nullable', 'string', 'max:30'],
            'next_action' => ['nullable', 'string', 'max:50'],
            'finance_actions' => ['nullable', 'array'],
            'finance_actions.*' => ['string', 'max:50'],
            'severity' => ['required', 'string', 'max:20'],
            'requires_customer_action' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $allowedIssueTypes = [
            'out_of_stock',
            'price_increase',
            'retailer_restriction',
            'retailer_cancelled',
            'awaiting_customer_decision',
            'supplier_delay',
            'wrong_product_link',
            'supplier_cancelled_after_purchase',
            'lost_in_transit',
            'damaged_after_purchase',
            'wrong_item_received',
            'missing_from_parcel',
            'supplier_refunded_dabba',
            'replacement_expected',
            'other',
        ];

        $issueType = in_array($validated['issue_type'], $allowedIssueTypes, true)
            ? $validated['issue_type']
            : 'other';

        $issueStage = in_array(($validated['issue_stage'] ?? 'pre_purchase'), ['pre_purchase', 'post_purchase', 'arrival'], true)
            ? (string) ($validated['issue_stage'] ?? 'pre_purchase')
            : 'pre_purchase';

        $arrivalExpectation = in_array(($validated['arrival_expectation'] ?? 'expected'), ['expected', 'replacement_expected', 'not_expected'], true)
            ? (string) ($validated['arrival_expectation'] ?? 'expected')
            : 'expected';

        $nextActionLabels = [
            'keep_in_purchase_issues' => 'Keep in purchase issues',
            'remove_from_arrivals' => 'Remove from arrivals queue',
            'return_to_buy' => 'Return to purchasing queue',
            'replacement_expected' => 'Replacement expected',
            'awaiting_supplier_response' => 'Awaiting supplier response',
            'awaiting_customer_decision' => 'Awaiting customer decision',
            'write_off' => 'Write off / absorb loss',
            'other' => 'Other / see notes',
        ];

        $financeActionLabels = [
            'customer_refund_required' => 'Customer refund required',
            'wallet_credit_required' => 'Wallet credit required',
            'supplier_refund_pending' => 'Supplier refund pending',
            'supplier_refunded' => 'Supplier refunded Dabba',
            'manual_finance_review' => 'Manual finance review',
        ];

        $financeActions = collect($validated['finance_actions'] ?? [])
            ->map(fn ($action) => (string) $action)
            ->filter(fn ($action) => array_key_exists($action, $financeActionLabels))
            ->unique()
            ->values()
            ->all();

        $nextAction = array_key_exists((string) ($validated['next_action'] ?? ''), $nextActionLabels)
            ? (string) $validated['next_action']
            : ($issueStage === 'pre_purchase' ? 'keep_in_purchase_issues' : 'remove_from_arrivals');

        if ($issueStage === 'pre_purchase') {
            $arrivalExpectation = 'expected';
        } elseif ($nextAction === 'replacement_expected') {
            $arrivalExpectation = 'replacement_expected';
        } elseif (in_array($nextAction, ['remove_from_arrivals', 'return_to_buy', 'write_off'], true)) {
            $arrivalExpectation = 'not_expected';
        }

        $severity = in_array($validated['severity'], ['low', 'medium', 'high'], true)
            ? $validated['severity']
            : 'medium';

        $requiresCustomerAction = (bool) ($validated['requires_customer_action'] ?? false);
        if ($issueType === 'awaiting_customer_decision' || $nextAction === 'awaiting_customer_decision') {
            $requiresCustomerAction = true;
        }

        $item = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->leftJoin('order_retailers as ore', 'ore.id', '=', 'oi.order_retailer_id')
            ->select([
                'oi.*',
                'o.order_number',
                'o.purchase_mode',
                'ore.retailer_id',
            ])
            ->where('oi.id', (int) $validated['order_item_id'])
            ->first();

        abort_if(! $item, 404);
        $this->assertOrderIsMutable((int) $item->order_id);

        if (($item->purchase_mode ?? '') === 'customer_self_purchase') {
            throw ValidationException::withMessages([
                'issue' => 'This is a customer self-purchase order. Dabba should not record purchasing issues for it.',
            ]);
        }

        $rootItemId = (int) ($item->root_item_id ?: $item->id);

        $activeIssue = DB::table('purchase_issues')
            ->where('root_item_id', $rootItemId)
            ->whereIn('status', ['open', 'awaiting_customer', 'returned_to_buy'])
            ->first();

        if ($activeIssue) {
            throw ValidationException::withMessages([
                'issue' => 'This item already has an open purchased-item problem. Please use Open Problems to update or cancel the existing problem instead of creating another one.',
            ]);
        }

        $remaining = $this->remainingToBuyQty($rootItemId, (int) $item->quantity);
        $purchasedOpen = $this->openPurchasedQtyForException($rootItemId);
        $qty = (int) $validated['qty'];

        if ($issueStage === 'pre_purchase') {
            if ($remaining <= 0) {
                throw ValidationException::withMessages([
                    'qty' => 'There is no remaining quantity available to move into Purchasing Issues.',
                ]);
            }

            if ($qty > $remaining) {
                throw ValidationException::withMessages([
                    'qty' => 'Only ' . $remaining . ' item' . ($remaining === 1 ? '' : 's') . ' remain open for this item.',
                ]);
            }
        } else {
            if ($purchasedOpen <= 0) {
                throw ValidationException::withMessages([
                    'qty' => 'No open purchased quantity is available for this purchased item problem. Use this only for items that were bought and are still expected to arrive.',
                ]);
            }

            if ($qty > $purchasedOpen) {
                throw ValidationException::withMessages([
                    'qty' => 'Only ' . $purchasedOpen . ' purchased item' . ($purchasedOpen === 1 ? '' : 's') . ' can be marked with this purchased item problem.',
                ]);
            }
        }

        $status = $requiresCustomerAction ? 'awaiting_customer' : 'open';
        $notes = trim((string) ($validated['notes'] ?? '')) ?: null;
        $nextActionNote = 'Action selected: ' . ($nextActionLabels[$nextAction] ?? ucfirst(str_replace('_', ' ', $nextAction))) . '.';

        $systemEffectNotes = [];
        if ($issueStage !== 'pre_purchase' && $arrivalExpectation === 'not_expected') {
            $systemEffectNotes[] = 'System effect: affected quantity is removed from expected arrivals / awaiting-arrival calculations.';
        } elseif ($issueStage !== 'pre_purchase' && $arrivalExpectation === 'replacement_expected') {
            $systemEffectNotes[] = 'System effect: affected quantity remains expected because a replacement is expected.';
        }

        if (! empty($financeActions)) {
            $financeLabels = collect($financeActions)->map(fn ($action) => $financeActionLabels[$action] ?? ucfirst(str_replace('_', ' ', $action)))->implode(', ');
            $systemEffectNotes[] = 'Finance follow-up required: ' . $financeLabels . '. No finance, invoice, wallet, refund, payment or ledger change was made automatically.';
        } else {
            $systemEffectNotes[] = 'Finance note: no refund, wallet credit, invoice or payment change was made automatically.';
        }

        $autoNotes = trim($nextActionNote . "\n" . implode("\n", $systemEffectNotes));
        $notes = $notes ? ($autoNotes . "\n\nOperator notes:\n" . $notes) : $autoNotes;
        $userId = Auth::id();

        DB::transaction(function () use ($item, $rootItemId, $qty, $issueType, $issueStage, $arrivalExpectation, $nextAction, $financeActions, $severity, $status, $requiresCustomerAction, $notes, $userId) {
            $issueId = DB::table('purchase_issues')->insertGetId([
                'order_item_id' => (int) $item->id,
                'root_item_id' => $rootItemId,
                'order_id' => (int) $item->order_id,
                'order_retailer_id' => $item->order_retailer_id,
                'retailer_id' => $item->retailer_id,
                'qty' => $qty,
                'affected_qty' => $qty,
                'issue_type' => $issueType,
                'issue_stage' => $issueStage,
                'arrival_expectation' => $arrivalExpectation,
                'resolution_type' => $nextAction,
                'finance_action_required' => ! empty($financeActions) ? 1 : 0,
                'finance_actions' => ! empty($financeActions) ? json_encode($financeActions) : null,
                'severity' => $severity,
                'status' => $status,
                'notes' => $notes,
                'requires_customer_action' => $requiresCustomerAction ? 1 : 0,
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('order_items')
                ->where('id', (int) $item->id)
                ->update([
                    'purchase_problem_reason' => $issueType,
                    'purchase_problem_note' => $notes,
                    'last_status_changed_at' => now(),
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            $this->syncPurchaseEventsForIssue($issueId, $userId);

            DB::table('activity_logs')->insert([
                'subject_type' => 'order',
                'subject_id' => (int) $item->order_id,
                'type' => 'purchasing_issue',
                'is_pinned' => 0,
                'title' => 'Purchasing issue recorded',
                'body' => 'Purchasing issue #' . $issueId . ' recorded for item #' . $item->id . ' on Order #' . $item->order_number . '. Qty ' . $qty . '. Stage: ' . $issueStage . '. Issue: ' . $issueType . '. Arrival expectation: ' . $arrivalExpectation . '. Severity: ' . $severity . '. Finance follow-ups: ' . (! empty($financeActions) ? implode(', ', $financeActions) : 'none') . '. No finance, invoice, wallet or refund changes were made.',
                'occurred_at' => now(),
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        if ($request->boolean('return_to_purchased_item_problems')) {
            return redirect()
                ->route('purchasing.index', ['tab' => 'purchased_item_problems', 'problem_view' => 'open', 'payment' => 'all', 'q' => $request->input('return_search')])
                ->with('success', $arrivalExpectation === 'not_expected' ? 'Purchased item problem recorded. Affected quantity has been removed from expected arrivals.' : 'Purchased item problem recorded.');
        }

        return redirect()
            ->route('purchasing.orders.show', ['order' => (int) $item->order_id, 'tab' => $issueStage === 'pre_purchase' ? 'problems' : 'purchased_item_problems', 'issue_view' => $issueStage === 'pre_purchase' ? 'pre' : 'post'])
            ->with('success', $issueStage === 'pre_purchase' ? 'Purchasing issue recorded.' : 'Purchased item problem recorded.');
    }

    public function updateProblem(Request $request, int $problem)
    {
        $validated = $request->validate([
            'issue_type' => ['required', 'string', 'max:50'],
            'issue_stage' => ['nullable', 'string', 'max:30'],
            'arrival_expectation' => ['nullable', 'string', 'max:30'],
            'next_action' => ['nullable', 'string', 'max:50'],
            'finance_actions' => ['nullable', 'array'],
            'finance_actions.*' => ['string', 'max:50'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:999'],
            'severity' => ['required', 'string', 'max:20'],
            'requires_customer_action' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'customer_replied' => ['nullable', 'boolean'],
        ]);

        $issue = DB::table('purchase_issues')->where('id', $problem)->first();
        abort_if(! $issue, 404);
        $this->assertOrderIsMutable((int) $issue->order_id);

        if (in_array((string) $issue->status, ['resolved', 'cancelled'], true)) {
            throw ValidationException::withMessages(['issue' => 'Resolved issues cannot be updated.']);
        }

        $allowedIssueTypes = ['out_of_stock', 'price_increase', 'retailer_restriction', 'retailer_cancelled', 'awaiting_customer_decision', 'supplier_delay', 'wrong_product_link', 'supplier_cancelled_after_purchase', 'lost_in_transit', 'damaged_after_purchase', 'wrong_item_received', 'missing_from_parcel', 'supplier_refunded_dabba', 'replacement_expected', 'other'];
        $issueType = in_array($validated['issue_type'], $allowedIssueTypes, true) ? $validated['issue_type'] : 'other';
        $issueStage = in_array(($validated['issue_stage'] ?? ($issue->issue_stage ?? 'pre_purchase')), ['pre_purchase', 'post_purchase', 'arrival'], true) ? (string) ($validated['issue_stage'] ?? ($issue->issue_stage ?? 'pre_purchase')) : 'pre_purchase';
        $arrivalExpectation = in_array(($validated['arrival_expectation'] ?? ($issue->arrival_expectation ?? 'expected')), ['expected', 'replacement_expected', 'not_expected'], true) ? (string) ($validated['arrival_expectation'] ?? ($issue->arrival_expectation ?? 'expected')) : 'expected';
        $nextActionLabels = [
            'keep_in_purchase_issues' => 'Keep in purchase issues',
            'remove_from_arrivals' => 'Remove from arrivals queue',
            'return_to_buy' => 'Return to purchasing queue',
            'replacement_expected' => 'Replacement expected',
            'awaiting_supplier_response' => 'Awaiting supplier response',
            'awaiting_customer_decision' => 'Awaiting customer decision',
            'write_off' => 'Write off / absorb loss',
            'other' => 'Other / see notes',
        ];

        $financeActionLabels = [
            'customer_refund_required' => 'Customer refund required',
            'wallet_credit_required' => 'Wallet credit required',
            'supplier_refund_pending' => 'Supplier refund pending',
            'supplier_refunded' => 'Supplier refunded Dabba',
            'manual_finance_review' => 'Manual finance review',
        ];

        $financeActions = collect($validated['finance_actions'] ?? [])
            ->map(fn ($action) => (string) $action)
            ->filter(fn ($action) => array_key_exists($action, $financeActionLabels))
            ->unique()
            ->values()
            ->all();

        $nextAction = array_key_exists((string) ($validated['next_action'] ?? ''), $nextActionLabels)
            ? (string) $validated['next_action']
            : (string) ($issue->resolution_type ?: (($issueStage === 'pre_purchase') ? 'keep_in_purchase_issues' : 'remove_from_arrivals'));
        if ($issueStage === 'pre_purchase') {
            $arrivalExpectation = 'expected';
        } elseif ($nextAction === 'replacement_expected') {
            $arrivalExpectation = 'replacement_expected';
        } elseif (in_array($nextAction, ['remove_from_arrivals', 'return_to_buy', 'write_off'], true)) {
            $arrivalExpectation = 'not_expected';
        }
        $qty = (int) ($validated['qty'] ?? ($issue->affected_qty ?: $issue->qty ?: 1));
        $severity = in_array($validated['severity'], ['low', 'medium', 'high'], true) ? $validated['severity'] : 'medium';
        $requiresCustomerAction = (bool) ($validated['requires_customer_action'] ?? false);
        if ($issueType === 'awaiting_customer_decision' || $nextAction === 'awaiting_customer_decision') {
            $requiresCustomerAction = true;
        }
        $status = $requiresCustomerAction ? 'awaiting_customer' : 'open';
        $notes = trim((string) ($validated['notes'] ?? '')) ?: null;
        if ($notes !== null) {
            $notes = preg_replace('/^Action selected:.*?(?:\r?\n|$)/m', '', $notes);
            $notes = preg_replace('/^System effect:.*?(?:\r?\n|$)/m', '', $notes);
            $notes = preg_replace('/^Finance note:.*?(?:\r?\n|$)/m', '', $notes);
            $notes = preg_replace('/^Finance follow-up required:.*?(?:\r?\n|$)/m', '', $notes);
            $notes = preg_replace('/^Operator notes:\s*(?:\r?\n)?/m', '', $notes);
            $notes = trim((string) $notes) ?: null;
        }

        $nextActionNote = 'Action selected: ' . ($nextActionLabels[$nextAction] ?? ucfirst(str_replace('_', ' ', $nextAction))) . '.';
        $systemEffectNotes = [];
        if ($issueStage !== 'pre_purchase' && $arrivalExpectation === 'not_expected') {
            $systemEffectNotes[] = 'System effect: affected quantity is removed from expected arrivals / awaiting-arrival calculations.';
        } elseif ($issueStage !== 'pre_purchase' && $arrivalExpectation === 'replacement_expected') {
            $systemEffectNotes[] = 'System effect: affected quantity remains expected because a replacement is expected.';
        }

        if (! empty($financeActions)) {
            $financeLabels = collect($financeActions)->map(fn ($action) => $financeActionLabels[$action] ?? ucfirst(str_replace('_', ' ', $action)))->implode(', ');
            $systemEffectNotes[] = 'Finance follow-up required: ' . $financeLabels . '. No finance, invoice, wallet, refund, payment or ledger change was made automatically.';
        } else {
            $systemEffectNotes[] = 'Finance note: no refund, wallet credit, invoice or payment change was made automatically.';
        }
        $autoNotes = trim($nextActionNote . "\n" . implode("\n", $systemEffectNotes));
        $notes = $notes ? ($autoNotes . "\n\nOperator notes:\n" . $notes) : $autoNotes;
        $userId = Auth::id();

        DB::transaction(function () use ($issue, $problem, $issueType, $issueStage, $arrivalExpectation, $nextAction, $financeActions, $qty, $severity, $status, $requiresCustomerAction, $notes, $validated, $userId) {
            DB::table('purchase_issues')
                ->where('id', $problem)
                ->update([
                    'issue_type' => $issueType,
                    'issue_stage' => $issueStage,
                    'arrival_expectation' => $arrivalExpectation,
                    'resolution_type' => $nextAction,
                    'finance_action_required' => ! empty($financeActions) ? 1 : 0,
                    'finance_actions' => ! empty($financeActions) ? json_encode($financeActions) : null,
                    'qty' => $qty,
                    'affected_qty' => $qty,
                    'severity' => $severity,
                    'status' => $status,
                    'notes' => $notes,
                    'requires_customer_action' => $requiresCustomerAction ? 1 : 0,
                    'customer_replied_at' => ! empty($validated['customer_replied']) ? now() : $issue->customer_replied_at,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            DB::table('order_items')
                ->where('id', (int) $issue->order_item_id)
                ->update([
                    'purchase_problem_reason' => $issueType,
                    'purchase_problem_note' => $notes,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            $this->syncPurchaseEventsForIssue($problem, $userId);

            DB::table('activity_logs')->insert([
                'subject_type' => 'order',
                'subject_id' => (int) $issue->order_id,
                'type' => 'purchasing_issue',
                'is_pinned' => 0,
                'title' => 'Purchasing issue updated',
                'body' => 'Purchasing issue #' . $problem . ' was updated. Issue: ' . $issueType . '. Severity: ' . $severity . '. Finance follow-ups: ' . (! empty($financeActions) ? implode(', ', $financeActions) : 'none') . '.',
                'occurred_at' => now(),
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        if ($request->boolean('return_to_purchased_item_problems')) {
            return redirect()
                ->route('purchasing.index', ['tab' => 'purchased_item_problems', 'problem_view' => 'open', 'payment' => 'all', 'q' => $request->input('return_search')])
                ->with('success', $arrivalExpectation === 'not_expected' ? 'Purchased item problem updated. Affected quantity has been removed from expected arrivals.' : 'Purchased item problem updated.');
        }

        return redirect()
            ->route('purchasing.orders.show', ['order' => (int) $issue->order_id, 'tab' => 'problems', 'issue_view' => (($issue->issue_stage ?? 'pre_purchase') === 'pre_purchase' ? 'pre' : 'post')])
            ->with('success', 'Purchasing issue updated.');
    }

    public function cancelProblem(Request $request, int $problem)
    {
        $issue = DB::table('purchase_issues')->where('id', $problem)->first();
        abort_if(! $issue, 404);
        $this->assertOrderIsMutable((int) $issue->order_id);

        if ((string) $issue->status === 'cancelled') {
            return redirect()
                ->route('purchasing.index', ['tab' => 'purchased_item_problems', 'problem_view' => 'open', 'payment' => 'all', 'q' => $request->input('return_search')])
                ->with('success', 'Problem was already cancelled.');
        }

        $userId = Auth::id();

        DB::transaction(function () use ($issue, $problem, $userId) {
            DB::table('purchase_issues')
                ->where('id', $problem)
                ->update([
                    'status' => 'cancelled',
                    'requires_customer_action' => 0,
                    'resolution_type' => 'cancelled_error',
                    'resolution_notes' => trim(((string) ($issue->resolution_notes ?? '')) . "
Cancelled as an incorrect or duplicate recorded problem."),
                    'resolved_at' => now(),
                    'resolved_by_user_id' => $userId,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            $activeIssueQty = (int) DB::table('purchase_issues')
                ->where('root_item_id', (int) $issue->root_item_id)
                ->whereIn('status', ['open', 'awaiting_customer', 'returned_to_buy'])
                ->sum('qty');

            DB::table('order_items')
                ->where('id', (int) $issue->order_item_id)
                ->update([
                    'purchase_problem_reason' => $activeIssueQty > 0 ? DB::raw('purchase_problem_reason') : null,
                    'purchase_problem_note' => $activeIssueQty > 0 ? DB::raw('purchase_problem_note') : null,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            $this->restorePurchaseEventsForIssue($problem, $userId);

            DB::table('activity_logs')->insert([
                'subject_type' => 'order',
                'subject_id' => (int) $issue->order_id,
                'type' => 'purchasing_issue',
                'is_pinned' => 0,
                'title' => 'Purchased item problem cancelled',
                'body' => 'Purchased item problem #' . $problem . ' was cancelled. No finance, invoice, wallet or refund changes were made.',
                'occurred_at' => now(),
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        if ($request->boolean('return_to_purchased_item_problems', true)) {
            return redirect()
                ->route('purchasing.index', ['tab' => 'purchased_item_problems', 'problem_view' => 'open', 'payment' => 'all', 'q' => $request->input('return_search')])
                ->with('success', 'Recorded problem cancelled.');
        }

        return redirect()
            ->route('purchasing.orders.show', ['order' => (int) $issue->order_id, 'tab' => 'purchased_item_problems'])
            ->with('success', 'Recorded problem cancelled.');
    }

    public function markCustomerContacted(int $problem)
    {
        $issue = DB::table('purchase_issues')->where('id', $problem)->first();
        abort_if(! $issue, 404);
        $this->assertOrderIsMutable((int) $issue->order_id);

        $userId = Auth::id();

        DB::transaction(function () use ($issue, $problem, $userId) {
            DB::table('purchase_issues')
                ->where('id', $problem)
                ->update([
                    'requires_customer_action' => 1,
                    'status' => 'awaiting_customer',
                    'customer_contacted_at' => now(),
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            DB::table('activity_logs')->insert([
                'subject_type' => 'order',
                'subject_id' => (int) $issue->order_id,
                'type' => 'purchasing_issue',
                'is_pinned' => 0,
                'title' => 'Customer contact recorded',
                'body' => 'Customer contact was recorded for purchasing issue #' . $problem . '.',
                'occurred_at' => now(),
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('purchasing.orders.show', ['order' => (int) $issue->order_id, 'tab' => 'problems', 'issue_view' => (($issue->issue_stage ?? 'pre_purchase') === 'pre_purchase' ? 'pre' : 'post')])
            ->with('success', 'Customer contact recorded.');
    }

    public function resolveProblem(Request $request, int $problem)
    {
        $validated = $request->validate([
            'resolution_type' => ['required', 'string', 'max:50'],
            'resolution_notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $issue = DB::table('purchase_issues')->where('id', $problem)->first();
        abort_if(! $issue, 404);
        $this->assertOrderIsMutable((int) $issue->order_id);

        $allowedResolutionTypes = ['return_to_buy', 'purchased_successfully', 'alternative_purchased', 'replacement_expected', 'closed_no_replacement', 'supplier_refunded', 'customer_cancelled', 'customer_refunded', 'duplicate_item', 'no_longer_required', 'written_off', 'other'];
        $resolutionType = in_array($validated['resolution_type'], $allowedResolutionTypes, true)
            ? $validated['resolution_type']
            : 'return_to_buy';

        $resolutionNotes = trim((string) ($validated['resolution_notes'] ?? '')) ?: null;
        $userId = Auth::id();

        DB::transaction(function () use ($issue, $problem, $resolutionType, $resolutionNotes, $userId) {
            $returnsToBuy = $resolutionType === 'return_to_buy';
            $issueStatus = $returnsToBuy ? 'returned_to_buy' : 'resolved';

            DB::table('purchase_issues')
                ->where('id', $problem)
                ->update([
                    'status' => $issueStatus,
                    'requires_customer_action' => 0,
                    'arrival_expectation' => match ($resolutionType) {
                        'return_to_buy', 'closed_no_replacement', 'supplier_refunded', 'customer_refunded', 'customer_cancelled', 'written_off', 'no_longer_required' => 'not_expected',
                        'replacement_expected' => 'replacement_expected',
                        default => ($issue->arrival_expectation ?? 'expected'),
                    },
                    'resolution_type' => $resolutionType,
                    'resolution_notes' => $resolutionNotes,
                    'resolved_at' => now(),
                    'resolved_by_user_id' => $userId,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            $activeIssueQty = (int) DB::table('purchase_issues')
                ->where('root_item_id', (int) $issue->root_item_id)
                ->whereIn('status', ['open', 'awaiting_customer', 'returned_to_buy'])
                ->sum('qty');

            DB::table('order_items')
                ->where('id', (int) $issue->order_item_id)
                ->update([
                    'purchase_problem_reason' => $activeIssueQty > 0 ? DB::raw('purchase_problem_reason') : null,
                    'purchase_problem_note' => $activeIssueQty > 0 ? DB::raw('purchase_problem_note') : null,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            DB::table('activity_logs')->insert([
                'subject_type' => 'order',
                'subject_id' => (int) $issue->order_id,
                'type' => 'purchasing_issue',
                'is_pinned' => 0,
                'title' => 'Purchasing issue resolved',
                'body' => 'Purchasing issue #' . $problem . ' was resolved as ' . $resolutionType . '. ' . ($issueStatus === 'returned_to_buy' ? 'The quantity was returned to the purchase queue. ' : '') . 'No finance, invoice, wallet or refund changes were made.',
                'occurred_at' => now(),
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $targetTab = $resolutionType === 'return_to_buy' ? 'buy' : 'problems';
        $targetIssueView = (($issue->issue_stage ?? 'pre_purchase') === 'pre_purchase') ? 'pre' : 'post';
        $message = $targetTab === 'buy'
            ? 'Issue resolved and item returned to the purchase queue.'
            : 'Purchasing issue resolved.';

        return redirect()
            ->route('purchasing.orders.show', ['order' => (int) $issue->order_id, 'tab' => $targetTab, 'issue_view' => $targetTab === 'problems' ? $targetIssueView : null])
            ->with('success', $message);
    }

    public function undoPurchase(Request $request, int $purchase)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        $row = DB::table('order_item_purchases')->where('id', $purchase)->first();
        abort_if(! $row, 404);
        $this->assertOrderIsMutable((int) $row->order_id);

        if ($row->cancelled_at !== null) {
            return redirect()
                ->route('purchasing.orders.show', ['order' => (int) $row->order_id, 'tab' => 'buy'])
                ->with('success', 'Purchase was already undone.');
        }

        $hasArrival = DB::table('purchase_arrival_assignments')
            ->where('order_item_purchase_id', $purchase)
            ->whereNull('undone_at')
            ->exists();

        if ($hasArrival) {
            throw ValidationException::withMessages([
                'undo' => 'This purchase has an active arrival assignment. Undo the arrival assignment before undoing the purchase.',
            ]);
        }

        $userId = Auth::id();
        $reason = trim((string) $validated['reason']);
        $noteAppend = "\nPurchase undone " . now()->format('Y-m-d H:i') . ' by user #' . ($userId ?: 'unknown') . ': ' . $reason;

        DB::transaction(function () use ($row, $purchase, $userId, $reason, $noteAppend) {
            DB::table('order_item_purchases')
                ->where('id', $purchase)
                ->update([
                    'cancelled_at' => now(),
                    'internal_notes' => trim((string) ($row->internal_notes ?? '') . $noteAppend),
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            $activeQty = DB::table('order_item_purchases')
                ->where('root_item_id', $row->root_item_id)
                ->whereIn('status', ['purchased', 'ordered', 'received'])
                ->whereNull('cancelled_at')
                ->sum('qty');

            $activeProblemQty = (int) DB::table('order_item_purchases')
                ->where('root_item_id', $row->root_item_id)
                ->whereIn('status', ['unfulfilled', 'failed', 'problem', 'supplier_problem', 'supplier_cancelled', 'cancelled', 'refunded', 'retailer_refunded', 'lost', 'damaged', 'wrong_item', 'unavailable'])
                ->whereNull('cancelled_at')
                ->where(function ($query) {
                    $query->whereNull('resolution_status')
                        ->orWhere('resolution_status', 'pending');
                })
                ->sum('qty');

            DB::table('order_items')
                ->where('id', $row->order_item_id)
                ->update([
                    'status' => ((int) $activeQty > 0) ? 'purchased' : 'requested',
                    'purchase_problem_reason' => ((int) $activeProblemQty > 0) ? DB::raw('purchase_problem_reason') : null,
                    'purchase_problem_note' => ((int) $activeProblemQty > 0) ? DB::raw('purchase_problem_note') : null,
                    // Undo purchase means undo the purchase-specific package-check marker too.
                    // Staff can re-apply Purple Check if the item is purchased again.
                    'requires_inspection' => 0,
                    'inspection_note' => null,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            $order = DB::table('orders')->where('id', $row->order_id)->first();

            DB::table('activity_logs')->insert([
                'subject_type' => 'order',
                'subject_id' => (int) $row->order_id,
                'type' => 'purchasing',
                'is_pinned' => 0,
                'title' => 'Purchase undone',
                'body' => 'Purchase #' . $purchase . ' was undone for Order #' . ($order->order_number ?? $row->order_id) . '. Reason: ' . $reason . ' Package check marker was cleared automatically.',
                'occurred_at' => now(),
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('purchasing.orders.show', ['order' => (int) $row->order_id, 'tab' => 'buy'])
            ->with('success', 'Purchase undone.');
    }



    public function updateInspectionFlag(Request $request, int $item)
    {
        $validated = $request->validate([
            'requires_inspection' => ['nullable', 'boolean'],
            'inspection_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $row = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->select(['oi.*', 'o.order_number'])
            ->where('oi.id', $item)
            ->first();

        abort_if(! $row, 404);
        $this->assertOrderIsMutable((int) $row->order_id);

        $requiresInspection = (bool) ($validated['requires_inspection'] ?? false);
        $note = trim((string) ($validated['inspection_note'] ?? ''));

        $userId = Auth::id();

        DB::transaction(function () use ($row, $requiresInspection, $note, $userId) {
            DB::table('order_items')
                ->where('id', (int) $row->id)
                ->update([
                    'requires_inspection' => $requiresInspection ? 1 : 0,
                    'inspection_note' => $requiresInspection ? ($note !== '' ? $note : null) : null,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            DB::table('activity_logs')->insert([
                'subject_type' => 'order',
                'subject_id' => (int) $row->order_id,
                'type' => 'purchasing',
                'is_pinned' => 0,
                'title' => $requiresInspection ? 'Package check marked' : 'Package check cleared',
                'body' => $requiresInspection
                    ? 'Item #' . $row->id . ' on Order #' . ($row->order_number ?? $row->order_id) . ' was marked for package check. Note: ' . $note
                    : 'Item #' . $row->id . ' on Order #' . ($row->order_number ?? $row->order_id) . ' package check marker was cleared.',
                'occurred_at' => now(),
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'requires_inspection' => $requiresInspection,
                'inspection_note' => $requiresInspection ? ($note !== '' ? $note : null) : null,
                'message' => $requiresInspection ? 'Package check saved.' : 'Package check cleared.',
            ]);
        }

        return redirect()
            ->route('purchasing.orders.show', ['order' => (int) $row->order_id, 'tab' => 'buy'])
            ->with('success', $requiresInspection ? 'Item marked purple for package check.' : 'Package check marker cleared.');
    }


    public function quickStoreRetailer(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:191'],
            'base_url' => ['required', 'string', 'min:3', 'max:191'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
        ]);

        if (! empty($validated['order_id'])) {
            $this->assertOrderIsMutable((int) $validated['order_id']);
        }

        $baseUrl = trim((string) $validated['base_url']);
        if (! preg_match('/^https?:\/\//i', $baseUrl)) {
            $baseUrl = 'https://' . $baseUrl;
        }

        $name = trim((string) $validated['name']);
        $userId = Auth::id();

        $existing = DB::table('retailers')
            ->where('base_url', $baseUrl)
            ->first();

        if ($existing) {
            return redirect()
                ->route('purchasing.orders.show', ['order' => (int) ($validated['order_id'] ?? 0), 'tab' => 'buy'])
                ->with('success', 'Retailer already exists: ' . $existing->name . '.');
        }

        DB::table('retailers')->insert([
            'name' => $name,
            'base_url' => $baseUrl,
            'is_active' => 1,
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('purchasing.orders.show', ['order' => (int) ($validated['order_id'] ?? 0), 'tab' => 'buy'])
            ->with('success', 'Retailer added. You can now select it in Purchased from / supplier.');
    }


    private function syncPurchaseEventsForIssue(int $issueId, ?int $userId = null): void
    {
        $issue = DB::table('purchase_issues')->where('id', $issueId)->first();

        if (! $issue || ($issue->issue_stage ?? 'pre_purchase') === 'pre_purchase') {
            return;
        }

        if (($issue->arrival_expectation ?? 'expected') !== 'not_expected') {
            $this->restorePurchaseEventsForIssue($issueId, $userId);
            return;
        }

        $notExpectedActions = [
            'remove_from_arrivals',
            'return_to_buy',
            'write_off',
        ];

        if (! in_array((string) ($issue->resolution_type ?? ''), $notExpectedActions, true)) {
            return;
        }

        $status = match ((string) $issue->issue_type) {
            'lost_in_transit' => 'lost',
            'damaged_after_purchase' => 'damaged',
            'wrong_item_received' => 'wrong_item',
            'missing_from_parcel' => 'problem',
            'supplier_refunded_dabba' => 'retailer_refunded',
            default => 'supplier_cancelled',
        };

        $remainingQty = max(1, (int) ($issue->affected_qty ?: $issue->qty ?: 1));
        $marker = 'Marked not expected via purchased item problem #' . $issueId . '.';

        $purchaseRows = DB::table('order_item_purchases')
            ->where('root_item_id', (int) $issue->root_item_id)
            ->whereIn('status', ['purchased', 'ordered', 'received'])
            ->whereNull('cancelled_at')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'qty', 'problem_notes']);

        foreach ($purchaseRows as $purchase) {
            if ($remainingQty <= 0) {
                break;
            }

            $existingNotes = trim((string) ($purchase->problem_notes ?? ''));
            $notes = $existingNotes === '' ? $marker : ($existingNotes . "\n" . $marker);

            DB::table('order_item_purchases')
                ->where('id', (int) $purchase->id)
                ->update([
                    'status' => $status,
                    'resolution_status' => 'not_expected',
                    'problem_code' => (string) $issue->issue_type,
                    'resolution_action' => (string) $issue->resolution_type,
                    'problem_notes' => $notes,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            $remainingQty -= max(1, (int) $purchase->qty);
        }
    }

    private function restorePurchaseEventsForIssue(int $issueId, ?int $userId = null): void
    {
        $issue = DB::table('purchase_issues')->where('id', $issueId)->first();

        if (! $issue || ($issue->issue_stage ?? 'pre_purchase') === 'pre_purchase') {
            return;
        }

        $otherActiveNotExpectedIssueExists = DB::table('purchase_issues')
            ->where('root_item_id', (int) $issue->root_item_id)
            ->where('id', '!=', $issueId)
            ->whereIn('status', ['open', 'awaiting_customer', 'resolved', 'returned_to_buy'])
            ->where('arrival_expectation', 'not_expected')
            ->exists();

        if ($otherActiveNotExpectedIssueExists) {
            return;
        }

        $marker = 'Marked not expected via purchased item problem #' . $issueId . '.';

        DB::table('order_item_purchases')
            ->where('root_item_id', (int) $issue->root_item_id)
            ->where('problem_notes', 'like', '%' . $marker . '%')
            ->update([
                'status' => 'purchased',
                'resolution_status' => null,
                'problem_code' => null,
                'resolution_action' => null,
                'updated_by_user_id' => $userId,
                'updated_at' => now(),
            ]);
    }

    private function assertOrderIsMutable(int $orderId): void
    {
        $order = DB::table('orders')->where('id', $orderId)->first(['id', 'order_number', 'status', 'cancel_reason']);

        abort_if(! $order, 404);

        $hasNewerActiveRevision = DB::table('orders')
            ->where('order_number', $order->order_number)
            ->where('id', '>', $orderId)
            ->where('status', '!=', 'superseded')
            ->where(function ($query) {
                $query->whereNull('cancel_reason')
                    ->orWhere('cancel_reason', '!=', 'superseded');
            })
            ->exists();

        $isHistoricalRevision = ($order->status === 'superseded')
            || ($order->cancel_reason === 'superseded')
            || $hasNewerActiveRevision;

        if ($isHistoricalRevision) {
            throw ValidationException::withMessages([
                'order' => 'This is a historical order revision. Purchasing actions are disabled. Open the active revision to make changes.',
            ]);
        }
    }

    private function openPurchasedQtyForException(int $rootItemId): int
    {
        $purchased = (int) DB::table('order_item_purchases')
            ->where('root_item_id', $rootItemId)
            ->whereIn('status', ['purchased', 'ordered', 'received'])
            ->whereNull('cancelled_at')
            ->sum('qty');

        $arrived = (int) DB::table('purchase_arrival_assignments')
            ->where('root_item_id', $rootItemId)
            ->whereNull('undone_at')
            ->sum('qty');

        $notExpected = (int) DB::table('purchase_issues')
            ->where('root_item_id', $rootItemId)
            ->whereIn('status', ['open', 'awaiting_customer', 'resolved', 'returned_to_buy'])
            ->where(function ($query) {
                $query->where('arrival_expectation', 'not_expected')
                    ->orWhere('resolution_type', 'return_to_buy');
            })
            ->sum('qty');

        return max(0, $purchased - $arrived - $notExpected);
    }

    private function remainingToBuyQty(int $rootItemId, int $itemQty): int
    {
        $purchased = (int) DB::table('order_item_purchases')
            ->where('root_item_id', $rootItemId)
            ->whereIn('status', ['purchased', 'ordered', 'received'])
            ->whereNull('cancelled_at')
            ->sum('qty');

        $terminalProblem = (int) DB::table('order_item_purchases')
            ->where('root_item_id', $rootItemId)
            ->whereIn('status', ['unfulfilled', 'failed', 'problem', 'supplier_problem', 'supplier_cancelled', 'cancelled', 'refunded', 'retailer_refunded', 'lost', 'damaged', 'wrong_item', 'unavailable'])
            ->whereNull('cancelled_at')
            ->sum('qty');

        $activeIssueQty = (int) DB::table('purchase_issues')
            ->where('root_item_id', $rootItemId)
            ->where(function ($query) {
                $query->whereNull('issue_stage')
                    ->orWhere('issue_stage', 'pre_purchase');
            })
            ->whereIn('status', ['open', 'awaiting_customer', 'returned_to_buy'])
            ->sum('qty');

        return max(0, $itemQty - $purchased - $terminalProblem - $activeIssueQty);
    }
}
