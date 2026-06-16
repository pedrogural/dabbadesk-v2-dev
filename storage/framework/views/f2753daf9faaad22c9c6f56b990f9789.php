<?php
    $remainingToBuy = max(0, (int) ($item->purchase_remaining_qty ?? 0));
    $arrivalRemaining = max(0, (int) ($item->arrival_remaining_qty ?? 0));
    $problemActionQty = max(1, max($remainingToBuy, $arrivalRemaining));
    $canRecordPurchase = $remainingToBuy > 0;
?>

<div class="grid gap-4 lg:grid-cols-2">
    <form method="POST" action="<?php echo e(route('purchasing.purchases.store')); ?>" class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="order_item_id" value="<?php echo e($item->id); ?>">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Record purchase</p>
                <p class="mt-1 text-xs font-semibold text-emerald-800">Creates a purchase event for this customer order only.</p>
            </div>
            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-100"><?php echo e($remainingToBuy); ?> left</span>
        </div>

        <div class="mt-3 grid gap-2 sm:grid-cols-2">
            <label class="text-xs font-bold text-slate-600">Qty
                <input name="qty" type="number" min="1" max="<?php echo e(max(1, $remainingToBuy)); ?>" value="<?php echo e(max(1, $remainingToBuy)); ?>" class="mt-1 h-10 w-full rounded-xl border-slate-300 text-sm font-bold" <?php if(! $canRecordPurchase): echo 'disabled'; endif; ?>>
            </label>
            <label class="text-xs font-bold text-slate-600">Unit cost
                <input name="purchase_unit_price" type="number" step="0.01" min="0" placeholder="0.00" class="mt-1 h-10 w-full rounded-xl border-slate-300 text-sm font-bold" <?php if(! $canRecordPurchase): echo 'disabled'; endif; ?>>
            </label>
            <label class="sm:col-span-2 text-xs font-bold text-slate-600">Retailer order reference
                <input name="retailer_order_reference" type="text" placeholder="e.g. 204-1234567-1234567" class="mt-1 h-10 w-full rounded-xl border-slate-300 text-sm font-bold" <?php if(! $canRecordPurchase): echo 'disabled'; endif; ?>>
            </label>
            <label class="text-xs font-bold text-slate-600">Marketplace seller
                <input name="marketplace_seller" type="text" placeholder="Optional" class="mt-1 h-10 w-full rounded-xl border-slate-300 text-sm font-bold" <?php if(! $canRecordPurchase): echo 'disabled'; endif; ?>>
            </label>
            <label class="text-xs font-bold text-slate-600">Ordered date
                <input name="ordered_at" type="date" value="<?php echo e(now()->toDateString()); ?>" class="mt-1 h-10 w-full rounded-xl border-slate-300 text-sm font-bold" <?php if(! $canRecordPurchase): echo 'disabled'; endif; ?>>
            </label>
            <label class="text-xs font-bold text-slate-600">Expected dispatch
                <input name="expected_dispatch_at" type="date" class="mt-1 h-10 w-full rounded-xl border-slate-300 text-sm font-bold" <?php if(! $canRecordPurchase): echo 'disabled'; endif; ?>>
            </label>
            <label class="text-xs font-bold text-slate-600">Expected UK hub
                <input name="expected_uk_hub_at" type="date" class="mt-1 h-10 w-full rounded-xl border-slate-300 text-sm font-bold" <?php if(! $canRecordPurchase): echo 'disabled'; endif; ?>>
            </label>
            <label class="sm:col-span-2 text-xs font-bold text-slate-600">Purchase note
                <textarea name="note" rows="2" placeholder="Optional note" class="mt-1 w-full rounded-xl border-slate-300 text-sm font-bold" <?php if(! $canRecordPurchase): echo 'disabled'; endif; ?>></textarea>
            </label>
            <label class="sm:col-span-2 flex items-center gap-2 text-xs font-bold text-slate-600">
                <input name="requires_marking_attention" value="1" type="checkbox" class="rounded border-slate-300 text-emerald-600" <?php if(! $canRecordPurchase): echo 'disabled'; endif; ?>>
                Needs marking attention on arrival
            </label>
        </div>

        <button class="mt-3 w-full rounded-xl bg-emerald-600 px-3 py-2 text-xs font-black text-white shadow-sm hover:bg-emerald-700 disabled:bg-slate-300" <?php if(! $canRecordPurchase): echo 'disabled'; endif; ?>>Save purchase</button>
    </form>

    <form method="POST" action="<?php echo e(route('purchasing.problems.store')); ?>" class="rounded-2xl border border-rose-100 bg-rose-50/70 p-4">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="order_item_id" value="<?php echo e($item->id); ?>">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-rose-700">Record problem</p>
                <p class="mt-1 text-xs font-semibold text-rose-800">Operational only. No finance, invoice, wallet or refund change.</p>
            </div>
            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black text-rose-700 ring-1 ring-rose-100">Qty <?php echo e($problemActionQty); ?></span>
        </div>

        <div class="mt-3 grid gap-2 sm:grid-cols-2">
            <label class="text-xs font-bold text-slate-600">Qty
                <input name="qty" type="number" min="1" value="<?php echo e($problemActionQty); ?>" class="mt-1 h-10 w-full rounded-xl border-slate-300 text-sm font-bold">
            </label>
            <label class="text-xs font-bold text-slate-600">Problem
                <select name="problem_code" class="mt-1 h-10 w-full rounded-xl border-slate-300 text-sm font-bold">
                    <option value="unavailable">Could not source</option>
                    <option value="supplier_cancelled">Supplier cancelled</option>
                    <option value="lost">Lost</option>
                    <option value="damaged">Damaged</option>
                    <option value="wrong_item">Wrong item</option>
                    <option value="retailer_refunded">Retailer refunded</option>
                    <option value="other">Other</option>
                </select>
            </label>
            <label class="sm:col-span-2 text-xs font-bold text-slate-600">Next action
                <select name="resolution_action" class="mt-1 h-10 w-full rounded-xl border-slate-300 text-sm font-bold">
                    <option value="customer_decision_required">Customer decision required</option>
                    <option value="repurchase">Repurchase / source again</option>
                    <option value="replacement">Replace via amendment</option>
                    <option value="refund_required">Refund / credit required</option>
                    <option value="remove_or_credit">Remove / credit later</option>
                    <option value="wait_for_retailer">Wait for retailer</option>
                    <option value="other">Other</option>
                </select>
            </label>
            <label class="sm:col-span-2 text-xs font-bold text-slate-600">Problem notes
                <textarea name="problem_notes" rows="3" placeholder="What happened and what staff should do next" class="mt-1 w-full rounded-xl border-slate-300 text-sm font-bold"></textarea>
            </label>
        </div>

        <button class="mt-3 w-full rounded-xl bg-rose-600 px-3 py-2 text-xs font-black text-white shadow-sm hover:bg-rose-700">Save problem</button>
    </form>
</div>
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/shared/purchasing/_item_action_forms.blade.php ENDPATH**/ ?>