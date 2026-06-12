<section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h3 class="text-lg font-black text-slate-950"><?php echo e($title); ?></h3>
            <p class="mt-1 text-sm font-semibold text-slate-500">Action first. Detail only where it helps the operator finish this order.</p>
        </div>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showPurchaseAction ?? false): ?>
        <?php
            $bulkRetailers = collect($rows)
                ->filter(fn ($item) => (int) ($item->remaining_to_buy_qty ?? 0) > 0)
                ->groupBy(fn ($item) => (string) ($item->retailer_id ?? 0) . '|' . ($item->retailer_name ?: 'Unknown retailer'))
                ->map(function ($items) {
                    $first = $items->first();
                    return [
                        'retailer_id' => $first->retailer_id ?? null,
                        'retailer_name' => $first->retailer_name ?: 'Unknown retailer',
                        'items' => $items->values(),
                        'qty' => (int) $items->sum('remaining_to_buy_qty'),
                    ];
                })
                ->values();
        ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($bulkRetailers->isNotEmpty()): ?>
            <div class="mt-5 space-y-3">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $bulkRetailers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bulkRetailer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <details class="overflow-hidden rounded-3xl border border-indigo-100 bg-indigo-50/70 shadow-sm">
                        <summary class="flex cursor-pointer select-none items-center justify-between gap-3 px-4 py-3 text-sm font-black text-indigo-900 sm:px-5">
                            <span>Bulk purchase — <?php echo e($bulkRetailer['retailer_name']); ?></span>
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs text-indigo-700 ring-1 ring-indigo-100"><?php echo e($bulkRetailer['qty']); ?> to buy</span>
                        </summary>

                        <form method="POST" action="<?php echo e(route('purchasing.purchases.bulk')); ?>" class="border-t border-indigo-100 bg-white p-4 sm:p-5">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="order_id" value="<?php echo e($bulkRetailer['items']->first()->order_id); ?>">

                            <div class="grid gap-3 md:grid-cols-2">
                                <div>
                                    <label class="text-[10px] font-black uppercase tracking-wide text-indigo-700">Retailer order ref</label>
                                    <input name="retailer_order_reference" maxlength="255" placeholder="One retailer reference for this basket" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm font-bold text-slate-900 focus:border-indigo-400 focus:ring-indigo-300">
                                </div>
                                <div>
                                    <label class="text-[10px] font-black uppercase tracking-wide text-indigo-700">Marketplace seller</label>
                                    <input name="marketplace_seller" maxlength="255" placeholder="Optional seller name" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm font-bold text-slate-900 focus:border-indigo-400 focus:ring-indigo-300">
                                </div>
                                <div>
                                    <label class="text-[10px] font-black uppercase tracking-wide text-indigo-700">Ordered date</label>
                                    <input name="ordered_at" type="date" value="<?php echo e(now()->format('Y-m-d')); ?>" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm font-bold text-slate-900 focus:border-indigo-400 focus:ring-indigo-300">
                                </div>
                                <div>
                                    <label class="text-[10px] font-black uppercase tracking-wide text-indigo-700">Expected UK hub</label>
                                    <input name="expected_uk_hub_at" type="date" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm font-bold text-slate-900 focus:border-indigo-400 focus:ring-indigo-300">
                                </div>
                            </div>

                            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                <div class="grid grid-cols-12 gap-2 border-b border-slate-200 bg-slate-100 px-3 py-2 text-[10px] font-black uppercase tracking-wide text-slate-500">
                                    <div class="col-span-7">Item</div>
                                    <div class="col-span-2">Qty</div>
                                    <div class="col-span-3">Unit cost</div>
                                </div>
                                <div class="divide-y divide-slate-200 bg-white">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $bulkRetailer['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bulkItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <div class="grid grid-cols-12 gap-2 px-3 py-3">
                                            <div class="col-span-7 min-w-0">
                                                <input type="hidden" name="order_item_ids[]" value="<?php echo e($bulkItem->item_id); ?>">
                                                <div class="truncate text-sm font-black text-slate-900"><?php echo e($bulkItem->item_name); ?></div>
                                                <div class="mt-1 text-xs font-semibold text-slate-400">Remaining <?php echo e((int) $bulkItem->remaining_to_buy_qty); ?></div>
                                            </div>
                                            <div class="col-span-2">
                                                <input name="qty[<?php echo e($bulkItem->item_id); ?>]" type="number" min="0" max="<?php echo e((int) $bulkItem->remaining_to_buy_qty); ?>" value="<?php echo e((int) $bulkItem->remaining_to_buy_qty); ?>" class="w-full rounded-lg border border-slate-200 px-2 py-2 text-sm font-bold text-slate-900 focus:border-indigo-400 focus:ring-indigo-300">
                                            </div>
                                            <div class="col-span-3">
                                                <input name="purchase_unit_price[<?php echo e($bulkItem->item_id); ?>]" type="number" step="0.01" min="0" placeholder="0.00" class="w-full rounded-lg border border-slate-200 px-2 py-2 text-sm font-bold text-slate-900 focus:border-indigo-400 focus:ring-indigo-300">
                                            </div>
                                        </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="text-[10px] font-black uppercase tracking-wide text-indigo-700">Notes</label>
                                <textarea name="note" rows="2" maxlength="2000" placeholder="Optional shared note for this retailer purchase" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-400 focus:ring-indigo-300"></textarea>
                            </div>

                            <div class="mt-4 flex justify-end">
                                <button class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white shadow-sm hover:bg-indigo-700">Record bulk purchase for <?php echo e($bulkRetailer['retailer_name']); ?></button>
                            </div>
                        </form>
                    </details>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div class="mt-5 space-y-4">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <?php
                $rootId = (int) ($item->lineage_root_id ?? $item->item_id);
                $history = collect($purchasesByRoot[$rootId] ?? []);
                $activeHistory = $history->whereNull('cancelled_at')->values();
                $undoneHistory = $history->filter(fn ($purchase) => ! empty($purchase->cancelled_at))->values();
                $canBuy = (int) ($item->remaining_to_buy_qty ?? 0) > 0;
            ?>

            <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="p-4 sm:p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-black uppercase tracking-[0.12em] text-slate-500"><?php echo e($item->retailer_name); ?></span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->product_code): ?>
                                    <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-black text-slate-500 ring-1 ring-slate-200">SKU <?php echo e($item->product_code); ?></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div class="mt-2 font-black leading-6 text-slate-950"><?php echo e($item->item_name); ?></div>
                            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs font-black">
                                <span class="text-slate-500">Qty <?php echo e((int) $item->quantity); ?></span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int) $item->remaining_to_buy_qty > 0): ?>
                                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700 ring-1 ring-indigo-100"><?php echo e((int) $item->remaining_to_buy_qty); ?> to buy</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int) $item->awaiting_arrival_qty > 0): ?>
                                    <span class="rounded-full bg-sky-50 px-2.5 py-1 text-sky-700 ring-1 ring-sky-100"><?php echo e((int) $item->awaiting_arrival_qty); ?> awaiting</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int) $item->problem_qty > 0): ?>
                                    <span class="rounded-full bg-rose-50 px-2.5 py-1 text-rose-700 ring-1 ring-rose-100"><?php echo e((int) $item->problem_qty); ?> problem</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->product_url): ?>
                                <a href="<?php echo e($item->product_url); ?>" target="_blank" class="mt-3 inline-flex rounded-xl bg-indigo-50 px-3 py-2 text-xs font-black text-indigo-700 hover:bg-indigo-100">↗ Product link</a>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showPurchaseAction ?? false): ?>
                            <details class="w-full rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4 lg:w-[420px]" <?php if($canBuy): ?> open <?php endif; ?>>
                                <summary class="cursor-pointer select-none text-sm font-black text-emerald-800">
                                    Record purchase
                                </summary>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canBuy): ?>
                                    <form method="POST" action="<?php echo e(route('purchasing.purchases.store')); ?>" class="mt-4 space-y-3">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="order_item_id" value="<?php echo e($item->item_id); ?>">
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <div>
                                                <label class="text-[10px] font-black uppercase tracking-wide text-emerald-700">Qty</label>
                                                <input name="qty" type="number" min="1" max="<?php echo e((int) $item->remaining_to_buy_qty); ?>" value="<?php echo e((int) $item->remaining_to_buy_qty); ?>" required class="mt-1 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm font-bold text-slate-900 focus:border-emerald-400 focus:ring-emerald-300">
                                            </div>
                                            <div>
                                                <label class="text-[10px] font-black uppercase tracking-wide text-emerald-700">Unit cost</label>
                                                <input name="purchase_unit_price" type="number" step="0.01" min="0" placeholder="0.00" class="mt-1 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm font-bold text-slate-900 focus:border-emerald-400 focus:ring-emerald-300">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-black uppercase tracking-wide text-emerald-700">Retailer order ref</label>
                                            <input name="retailer_order_reference" maxlength="255" placeholder="Amazon / Argos / retailer ref" class="mt-1 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm font-bold text-slate-900 focus:border-emerald-400 focus:ring-emerald-300">
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-black uppercase tracking-wide text-emerald-700">Marketplace seller</label>
                                            <input name="marketplace_seller" maxlength="255" value="<?php echo e($item->marketplace_seller); ?>" class="mt-1 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm font-bold text-slate-900 focus:border-emerald-400 focus:ring-emerald-300">
                                        </div>
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <div>
                                                <label class="text-[10px] font-black uppercase tracking-wide text-emerald-700">Ordered date</label>
                                                <input name="ordered_at" type="date" value="<?php echo e(now()->format('Y-m-d')); ?>" class="mt-1 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm font-bold text-slate-900 focus:border-emerald-400 focus:ring-emerald-300">
                                            </div>
                                            <div>
                                                <label class="text-[10px] font-black uppercase tracking-wide text-emerald-700">Expected UK hub</label>
                                                <input name="expected_uk_hub_at" type="date" class="mt-1 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm font-bold text-slate-900 focus:border-emerald-400 focus:ring-emerald-300">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-black uppercase tracking-wide text-emerald-700">Notes</label>
                                            <textarea name="note" rows="2" maxlength="2000" placeholder="Optional purchase notes" class="mt-1 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-emerald-400 focus:ring-emerald-300"></textarea>
                                        </div>
                                        <button class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white shadow-sm hover:bg-emerald-700">Save purchase</button>
                                    </form>
                                <?php else: ?>
                                    <p class="mt-3 text-sm font-semibold text-emerald-800">Nothing remains to buy for this item.</p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </details>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($history->isNotEmpty()): ?>
                    <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-4 sm:px-5">
                        <div class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Purchase history</div>
                        <div class="mt-3 space-y-2">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $activeHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $purchase): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <div class="flex flex-col gap-3 rounded-2xl bg-white p-3 ring-1 ring-slate-200 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 text-sm font-black text-slate-900">
                                            <span>Qty <?php echo e((int) $purchase->qty); ?></span>
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] text-emerald-700 ring-1 ring-emerald-100"><?php echo e(Str::of((string) $purchase->status)->replace('_', ' ')->title()); ?></span>
                                        </div>
                                        <div class="mt-1 text-xs font-semibold text-slate-500">
                                            Ref: <?php echo e($purchase->retailer_order_reference ?: '—'); ?>

                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($purchase->ordered_at): ?>
                                                · Ordered <?php echo e(\Carbon\Carbon::parse($purchase->ordered_at)->format('d M Y')); ?>

                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($purchase->expected_uk_hub_at): ?>
                                                · Hub <?php echo e(\Carbon\Carbon::parse($purchase->expected_uk_hub_at)->format('d M')); ?>

                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                    </div>
                                    <details class="rounded-xl border border-rose-100 bg-rose-50 px-3 py-2">
                                        <summary class="cursor-pointer text-xs font-black text-rose-700">Undo</summary>
                                        <form method="POST" action="<?php echo e(route('purchasing.purchases.undo', $purchase->id)); ?>" class="mt-2 flex flex-col gap-2 sm:w-80">
                                            <?php echo csrf_field(); ?>
                                            <input name="reason" required maxlength="255" placeholder="Reason for undo" class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-slate-900 focus:border-rose-400 focus:ring-rose-300">
                                            <button class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-black text-white hover:bg-rose-700">Undo purchase</button>
                                        </form>
                                    </details>
                                </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $undoneHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $purchase): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <div class="rounded-2xl bg-white/70 p-3 text-xs font-semibold text-slate-400 ring-1 ring-slate-100">
                                    Undone purchase #<?php echo e($purchase->id); ?> · Qty <?php echo e((int) $purchase->qty); ?> · <?php echo e($purchase->cancelled_at); ?>

                                </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </article>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm font-semibold text-slate-500"><?php echo e($empty); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</section>
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/purchasing/partials/items.blade.php ENDPATH**/ ?>