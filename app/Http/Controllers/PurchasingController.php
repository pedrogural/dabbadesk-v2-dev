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

        DB::transaction(function () use ($item, $validated, $rootItemId, $qty, $unitPrice, $lineTotal, $orderedAt, $expectedHubAt, $userId) {
            $purchaseId = DB::table('order_item_purchases')->insertGetId([
                'order_item_id' => (int) $item->id,
                'root_item_id' => $rootItemId,
                'order_id' => (int) $item->order_id,
                'order_retailer_id' => $item->order_retailer_id,
                'retailer_id' => $item->retailer_id,
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
            'retailer_order_reference' => ['required', 'string', 'max:255'],
            'marketplace_seller' => ['nullable', 'string', 'max:255'],
            'ordered_at' => ['nullable', 'date'],
            'expected_uk_hub_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $orderId = (int) $validated['order_id'];
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
        $createdPurchaseIds = [];
        $totalQty = 0;

        DB::transaction(function () use ($lines, $reference, $seller, $note, $orderedAt, $expectedHubAt, $userId, &$createdPurchaseIds, &$totalQty) {
            foreach ($lines as $line) {
                $item = $line['item'];
                $qty = (int) $line['qty'];
                $totalQty += $qty;

                $purchaseId = DB::table('order_item_purchases')->insertGetId([
                    'order_item_id' => (int) $item->id,
                    'root_item_id' => (int) $line['root_item_id'],
                    'order_id' => (int) $item->order_id,
                    'order_retailer_id' => $item->order_retailer_id,
                    'retailer_id' => $item->retailer_id,
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
            'problem_code' => ['required', 'string', 'max:50'],
            'resolution_action' => ['nullable', 'string', 'max:50'],
            'problem_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $allowedProblemCodes = [
            'unavailable',
            'supplier_cancelled',
            'lost',
            'damaged',
            'wrong_item',
            'retailer_refunded',
            'other',
        ];

        $allowedResolutionActions = [
            'customer_decision_required',
            'repurchase',
            'replacement',
            'refund_required',
            'remove_or_credit',
            'wait_for_retailer',
            'other',
        ];

        $problemCode = in_array($validated['problem_code'], $allowedProblemCodes, true)
            ? $validated['problem_code']
            : 'other';

        $resolutionAction = trim((string) ($validated['resolution_action'] ?? 'customer_decision_required'));
        $resolutionAction = in_array($resolutionAction, $allowedResolutionActions, true)
            ? $resolutionAction
            : 'customer_decision_required';

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

        if (($item->purchase_mode ?? '') === 'customer_self_purchase') {
            throw ValidationException::withMessages([
                'problem' => 'This is a customer self-purchase order. Dabba should not record buying problems for it.',
            ]);
        }

        $rootItemId = (int) ($item->root_item_id ?: $item->id);
        $remaining = $this->remainingToBuyQty($rootItemId, (int) $item->quantity);
        $qty = (int) $validated['qty'];

        if ($remaining > 0 && $qty > $remaining) {
            throw ValidationException::withMessages([
                'qty' => 'Only ' . $remaining . ' item' . ($remaining === 1 ? '' : 's') . ' remain open for this item.',
            ]);
        }

        $status = match ($problemCode) {
            'supplier_cancelled' => 'unfulfilled',
            'retailer_refunded' => 'retailer_refunded',
            'lost' => 'lost',
            'damaged' => 'damaged',
            'wrong_item' => 'wrong_item',
            'unavailable' => 'unavailable',
            default => 'problem',
        };

        $userId = Auth::id();
        $problemNotes = trim((string) ($validated['problem_notes'] ?? '')) ?: null;

        DB::transaction(function () use ($item, $rootItemId, $qty, $status, $problemCode, $resolutionAction, $problemNotes, $userId) {
            $problemId = DB::table('order_item_purchases')->insertGetId([
                'order_item_id' => (int) $item->id,
                'root_item_id' => $rootItemId,
                'order_id' => (int) $item->order_id,
                'order_retailer_id' => $item->order_retailer_id,
                'retailer_id' => $item->retailer_id,
                'qty' => $qty,
                'status' => $status,
                'currency' => $item->purchase_currency ?: 'GBP',
                'marketplace_seller' => $item->marketplace_seller ?: null,
                'retailer_order_reference' => $item->retailer_order_reference ?: null,
                'problem_code' => $problemCode,
                'resolution_status' => 'pending',
                'resolution_action' => $resolutionAction,
                'problem_notes' => $problemNotes,
                'note' => $problemNotes,
                'ordered_at' => now(),
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('order_items')
                ->where('id', (int) $item->id)
                ->update([
                    'purchase_problem_reason' => $problemCode,
                    'purchase_problem_note' => $problemNotes,
                    'last_status_changed_at' => now(),
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            DB::table('activity_logs')->insert([
                'subject_type' => 'order',
                'subject_id' => (int) $item->order_id,
                'type' => 'purchasing',
                'is_pinned' => 0,
                'title' => 'Purchasing problem recorded',
                'body' => 'Purchasing problem #' . $problemId . ' recorded for item #' . $item->id . ' on Order #' . $item->order_number . '. Qty ' . $qty . '. Problem: ' . $problemCode . '. Next action: ' . $resolutionAction . '.',
                'occurred_at' => now(),
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('purchasing.orders.show', ['order' => (int) $item->order_id, 'tab' => 'problems'])
            ->with('success', 'Purchasing problem recorded.');
    }

    public function undoPurchase(Request $request, int $purchase)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        $row = DB::table('order_item_purchases')->where('id', $purchase)->first();
        abort_if(! $row, 404);

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

            DB::table('order_items')
                ->where('id', $row->order_item_id)
                ->update([
                    'status' => ((int) $activeQty > 0) ? 'purchased' : 'requested',
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
                'body' => 'Purchase #' . $purchase . ' was undone for Order #' . ($order->order_number ?? $row->order_id) . '. Reason: ' . $reason,
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

        $requiresInspection = (bool) ($validated['requires_inspection'] ?? false);
        $note = trim((string) ($validated['inspection_note'] ?? ''));

        if ($requiresInspection && $note === '') {
            throw ValidationException::withMessages([
                'inspection_note' => 'Please add a package check note when marking an item purple.',
            ]);
        }

        $userId = Auth::id();

        DB::transaction(function () use ($row, $requiresInspection, $note, $userId) {
            DB::table('order_items')
                ->where('id', (int) $row->id)
                ->update([
                    'requires_inspection' => $requiresInspection ? 1 : 0,
                    'inspection_note' => $requiresInspection ? $note : null,
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

        return redirect()
            ->route('purchasing.orders.show', ['order' => (int) $row->order_id, 'tab' => 'buy'])
            ->with('success', $requiresInspection ? 'Item marked purple for package check.' : 'Package check marker cleared.');
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
            ->sum('qty');

        return max(0, $itemQty - $purchased - $terminalProblem);
    }
}
