        <div x-show="tab === 'items'" x-cloak>
        <section class="w-full overflow-hidden rounded-3xl bg-white p-5 shadow-sm border border-slate-200">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Retailers &amp; Items</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Product links, purchase progress and arrival progress are grouped by retailer.
                        </p>
                    </div>

                    <span class="text-sm text-slate-400">
                        <?php echo e($retailerGroups->count()); ?> retailer group(s)
                    </span>
                </div>

                <div class="mt-6 space-y-6">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $retailerGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="overflow-hidden rounded-3xl border border-slate-200">
                            <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="font-bold text-slate-950"><?php echo e($group->name); ?></h3>
                                    <p class="mt-1 text-xs text-slate-500">
                                        <?php echo e($group->item_count); ?> line(s) · Qty <?php echo e($group->total_qty); ?>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($group->host && $group->host !== $group->name): ?>
                                            · <?php echo e($group->host); ?>

                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2 text-xs font-semibold">
                                    <span class="rounded-full bg-white px-3 py-2 text-slate-700 ring-1 ring-slate-200">
                                        Total £<?php echo e(number_format($group->line_total ?? 0, 2)); ?>

                                    </span>

                                    <span class="rounded-full bg-emerald-100 px-3 py-2 text-emerald-700">
                                        Purchased <?php echo e($group->purchased_qty); ?>/<?php echo e($group->total_qty); ?>

                                    </span>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($group->remaining_qty > 0): ?>
                                        <span class="rounded-full bg-rose-100 px-3 py-2 text-rose-700">
                                            Remaining <?php echo e($group->remaining_qty); ?>

                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                    <span class="rounded-full bg-sky-100 px-3 py-2 text-sky-700">
                                        Arrived <?php echo e($group->arrived_qty); ?>/<?php echo e($group->total_qty); ?>

                                    </span>
                                </div>
                            </div>

                            <div class="divide-y divide-slate-100">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $group->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <div class="p-5 <?php echo e($item->requires_inspection ? 'bg-purple-50/60' : 'bg-white'); ?>">
                                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-center">
                                            <div class="lg:col-span-5">
                                                <div class="flex items-start gap-3">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->product_url): ?>
                                                        <a
                                                            href="<?php echo e($item->product_url); ?>"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            title="Open product page"
                                                            class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 text-lg font-bold text-indigo-700 hover:bg-indigo-100"
                                                        >
                                                            ↗
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-300">
                                                            —
                                                        </span>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                                    <div>
                                                        <h4 class="font-bold text-slate-950"><?php echo e($item->item_name); ?></h4>

                                                        <div class="mt-2 flex flex-wrap gap-2">
                                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                                Qty <?php echo e($item->quantity); ?>

                                                            </span>

                                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->product_code): ?>
                                                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                                    <?php echo e($item->product_code); ?>

                                                                </span>
                                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->requires_inspection): ?>
                                                                <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700">
                                                                    Purple check
                                                                </span>
                                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="lg:col-span-2">
                                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Purchase</p>
                                                <p class="mt-1 font-semibold <?php echo e($item->purchase_remaining_qty > 0 ? 'text-rose-600' : 'text-emerald-600'); ?>">
                                                    <?php echo e($item->purchased_qty); ?>/<?php echo e($item->quantity); ?>

                                                </p>
                                                <p class="mt-1 text-xs text-slate-400">
                                                    <?php echo e($isCustomerSelfPurchase ? 'Bought by customer' : ($item->purchase_remaining_qty > 0 ? 'Pending purchase' : 'Purchased')); ?>

                                                </p>
                                            </div>

                                            <div class="lg:col-span-2">
                                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Arrival</p>
                                                <p class="mt-1 font-semibold <?php echo e($item->arrived_qty > 0 ? 'text-sky-600' : 'text-slate-500'); ?>">
                                                    <?php echo e($item->arrived_qty); ?>/<?php echo e($item->quantity); ?>

                                                </p>
                                                <p class="mt-1 text-xs text-slate-400">
                                                    <?php echo e($item->latest_arrival_status ? str_replace('_', ' ', $item->latest_arrival_status) : 'Not arrived'); ?>

                                                </p>
                                            </div>

                                            <div class="lg:col-span-3 lg:text-right">
                                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Price</p>
                                                <p class="mt-1 text-lg font-bold text-slate-950">
                                                    £<?php echo e(number_format($item->line_total ?? 0, 2)); ?>

                                                </p>

                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->latest_retailer_order_reference || $item->retailer_order_reference): ?>
                                                    <p class="mt-1 text-xs text-slate-500">
                                                        Ref: <?php echo e($item->latest_retailer_order_reference ?: $item->retailer_order_reference); ?>

                                                    </p>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </div>
                                        </div>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->inspection_note): ?>
                                            <p class="mt-4 rounded-2xl bg-purple-100 px-4 py-3 text-sm text-purple-800">
                                                <?php echo e($item->inspection_note); ?>

                                            </p>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
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