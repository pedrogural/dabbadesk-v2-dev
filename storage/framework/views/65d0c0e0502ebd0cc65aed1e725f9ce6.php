        <div x-show="tab === 'items'" x-cloak>
            <section class="w-full overflow-hidden rounded-3xl bg-white p-5 shadow-sm border border-slate-200">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Retailers &amp; Items</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Invoice-style item view grouped by retailer.
                        </p>
                    </div>

                    <span class="text-sm text-slate-400">
                        <?php echo e($retailerGroups->count()); ?> retailer group(s)
                    </span>
                </div>

                <div class="mt-6 space-y-6">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $retailerGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="overflow-hidden rounded-3xl border border-indigo-100 bg-white shadow-sm">
                            <div class="flex flex-col gap-3 border-b border-indigo-200 bg-indigo-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <h3 class="truncate text-base font-black text-indigo-950"><?php echo e($group->name); ?></h3>
                                    <p class="mt-1 text-xs font-semibold text-indigo-700/80">
                                        <?php echo e($group->item_count); ?> line(s) · Qty <?php echo e($group->total_qty); ?>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($group->host && $group->host !== $group->name): ?>
                                            · <?php echo e($group->host); ?>

                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2 text-xs font-black">
                                    <span class="rounded-full bg-white/85 px-3 py-2 text-indigo-900 ring-1 ring-indigo-100">
                                        Total £<?php echo e(number_format($group->line_total ?? 0, 2)); ?>

                                    </span>
                                    <span class="rounded-full bg-white/70 px-3 py-2 text-indigo-800 ring-1 ring-indigo-100">
                                        Purchased <?php echo e($group->purchased_qty); ?>/<?php echo e($group->total_qty); ?>

                                    </span>
                                    <span class="rounded-full bg-white/70 px-3 py-2 text-indigo-800 ring-1 ring-indigo-100">
                                        Arrived <?php echo e($group->arrived_qty); ?>/<?php echo e($group->total_qty); ?>

                                    </span>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($group->remaining_qty > 0): ?>
                                        <span class="rounded-full bg-rose-100 px-3 py-2 text-rose-700 ring-1 ring-rose-200">
                                            Remaining <?php echo e($group->remaining_qty); ?>

                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-100 text-sm">
                                    <thead class="bg-white">
                                        <tr class="text-left text-xs font-black uppercase tracking-wide text-slate-400">
                                            <th class="px-5 py-3">Item</th>
                                            <th class="px-3 py-3 text-right">Qty</th>
                                            <th class="px-3 py-3 text-right">Unit</th>
                                            <th class="px-3 py-3 text-right">Goods total</th>
                                            <th class="px-3 py-3">Status</th>
                                            <th class="px-5 py-3 text-right">Link</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $group->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                            <?php
                                                $itemQty = max(0, (int) ($item->quantity ?? 0));
                                                $purchasedQty = max(0, (int) ($item->purchased_qty ?? 0));
                                                $arrivedQty = max(0, (int) ($item->arrived_qty ?? 0));
                                                $problemQty = max(0, (int) ($item->problem_qty ?? 0));
                                                $remainingPurchaseQty = max(0, (int) ($item->purchase_remaining_qty ?? 0));
                                                $rawArrivalStatus = (string) ($item->latest_arrival_status ?? '');
                                                $goodsLineTotal = round($itemQty * (float) ($item->unit_price ?? 0), 2);

                                                if ($balanceDue > 0.004) {
                                                    $statusLabel = 'Pending payment';
                                                    $statusClasses = 'bg-rose-50 text-rose-700 ring-rose-100';
                                                } elseif ($problemQty > 0) {
                                                    $statusLabel = 'Problem';
                                                    $statusClasses = 'bg-amber-50 text-amber-700 ring-amber-100';
                                                } elseif (in_array($rawArrivalStatus, ['delivered', 'collected', 'customer_informed', 'informed'], true)) {
                                                    $statusLabel = ucwords(str_replace('_', ' ', $rawArrivalStatus));
                                                    $statusClasses = 'bg-emerald-50 text-emerald-700 ring-emerald-100';
                                                } elseif ($arrivedQty >= $itemQty && $itemQty > 0) {
                                                    $statusLabel = 'Arrived';
                                                    $statusClasses = 'bg-sky-50 text-sky-700 ring-sky-100';
                                                } elseif ($isCustomerSelfPurchase) {
                                                    $statusLabel = 'Customer purchased';
                                                    $statusClasses = 'bg-sky-50 text-sky-700 ring-sky-100';
                                                } elseif ($purchasedQty >= $itemQty && $itemQty > 0) {
                                                    $statusLabel = 'Purchased';
                                                    $statusClasses = 'bg-indigo-50 text-indigo-700 ring-indigo-100';
                                                } elseif ($remainingPurchaseQty > 0) {
                                                    $statusLabel = 'Pending purchase';
                                                    $statusClasses = 'bg-slate-100 text-slate-700 ring-slate-200';
                                                } else {
                                                    $statusLabel = ucwords(str_replace('_', ' ', (string) ($item->status ?? 'Requested')));
                                                    $statusClasses = 'bg-slate-100 text-slate-700 ring-slate-200';
                                                }
                                            ?>
                                            <tr class="align-top <?php echo e($item->requires_inspection ? 'bg-purple-50/50' : ''); ?>">
                                                <td class="px-5 py-4">
                                                    <div class="font-bold leading-5 text-slate-950"><?php echo e($item->item_name); ?></div>
                                                    <div class="mt-1 flex flex-wrap gap-2 text-xs font-semibold text-slate-500">
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->product_code): ?>
                                                            <span><?php echo e($item->product_code); ?></span>
                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->latest_retailer_order_reference || $item->retailer_order_reference): ?>
                                                            <span>Ref: <?php echo e($item->latest_retailer_order_reference ?: $item->retailer_order_reference); ?></span>
                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->requires_inspection): ?>
                                                            <span class="rounded-full bg-purple-100 px-2 py-0.5 text-purple-700">Purple check</span>
                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    </div>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->inspection_note): ?>
                                                        <p class="mt-2 rounded-xl bg-purple-100 px-3 py-2 text-xs font-semibold text-purple-800"><?php echo e($item->inspection_note); ?></p>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-4 text-right font-semibold text-slate-700"><?php echo e($itemQty); ?></td>
                                                <td class="whitespace-nowrap px-3 py-4 text-right font-semibold text-slate-700">£<?php echo e(number_format($item->unit_price ?? 0, 2)); ?></td>
                                                <td class="whitespace-nowrap px-3 py-4 text-right font-black text-slate-950">£<?php echo e(number_format($goodsLineTotal, 2)); ?></td>
                                                <td class="whitespace-nowrap px-3 py-4">
                                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 <?php echo e($statusClasses); ?>"><?php echo e($statusLabel); ?></span>
                                                </td>
                                                <td class="px-5 py-4 text-right">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->product_url): ?>
                                                        <a
                                                            href="<?php echo e($item->product_url); ?>"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            title="Open product page"
                                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 text-lg font-black text-indigo-700 hover:bg-indigo-100"
                                                        >↗</a>
                                                    <?php else: ?>
                                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-300">—</span>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                            No items found for this order.
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </section>
        </div>
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/orders/show/tabs/_items.blade.php ENDPATH**/ ?>