        <?php
            $purchaseWorkspace = $purchaseWorkspace ?? [];
            $purchaseSummary = $purchaseWorkspace['summary'] ?? ($progress ?? []);
            $purchaseRetailerGroups = collect($purchaseWorkspace['retailer_groups'] ?? $retailerGroups ?? []);
            $purchaseEvents = collect($purchaseWorkspace['purchases'] ?? $purchases ?? []);
            $purchaseTab = request('purchase_tab', 'retailers');
            if (! in_array($purchaseTab, ['retailers', 'events', 'problems'], true)) {
                $purchaseTab = 'retailers';
            }
            $purchaseProblems = $purchaseEvents->filter(fn ($event) => in_array((string) ($event->status ?? ''), ['failed', 'problem', 'supplier_cancelled', 'cancelled', 'unfulfilled', 'unavailable', 'lost', 'damaged', 'wrong_item'], true))->values();
            $statusBadge = function ($item) {
                if (($item->purchase_remaining_qty ?? 0) > 0 && ($item->problem_qty ?? 0) > 0) return ['Sourcing issue', 'bg-amber-50 text-amber-700 border-amber-100'];
                if (($item->purchase_remaining_qty ?? 0) > 0) return ['Ready to buy', 'bg-emerald-50 text-emerald-700 border-emerald-100'];
                if (($item->arrival_remaining_qty ?? 0) > 0) return ['Awaiting arrival', 'bg-sky-50 text-sky-700 border-sky-100'];
                if (($item->problem_qty ?? 0) > 0) return ['Problem', 'bg-rose-50 text-rose-700 border-rose-100'];
                return ['Complete', 'bg-slate-50 text-slate-600 border-slate-100'];
            };
        ?>

        <div x-show="tab === 'purchase_status'" x-cloak class="space-y-5">
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-indigo-500">Purchase workspace</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">Order-first purchasing for #<?php echo e($order->order_number); ?></h2>
                        <p class="mt-1 max-w-3xl text-sm font-semibold text-slate-500">Purchases are recorded inside this customer order. Even if another customer has items from the same retailer, they are never merged into one basket.</p>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $isHistoricalRevision): ?>
                        <a href="<?php echo e(route('purchasing.show', $order->id)); ?>" class="rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-indigo-700">Open Purchasing Workspace ↗</a>
                    <?php else: ?>
                        <span class="rounded-2xl bg-amber-100 px-4 py-2 text-sm font-black text-amber-800 ring-1 ring-amber-200">Purchasing actions disabled</span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-4">
                    <div class="rounded-3xl bg-slate-50 p-4 ring-1 ring-slate-100">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Order qty</p>
                        <p class="mt-1 text-2xl font-black text-slate-950"><?php echo e((int) ($purchaseSummary['item_qty'] ?? 0)); ?></p>
                    </div>
                    <div class="rounded-3xl bg-emerald-50 p-4 ring-1 ring-emerald-100">
                        <p class="text-[10px] font-black uppercase tracking-wide text-emerald-700">Purchased</p>
                        <p class="mt-1 text-2xl font-black text-emerald-700"><?php echo e((int) ($purchaseSummary['purchased_qty'] ?? 0)); ?></p>
                    </div>
                    <div class="rounded-3xl bg-sky-50 p-4 ring-1 ring-sky-100">
                        <p class="text-[10px] font-black uppercase tracking-wide text-sky-700">Arrived</p>
                        <p class="mt-1 text-2xl font-black text-sky-700"><?php echo e((int) ($purchaseSummary['arrived_qty'] ?? 0)); ?></p>
                    </div>
                    <div class="rounded-3xl <?php echo e((int) ($purchaseSummary['remaining_purchase_qty'] ?? 0) > 0 ? 'bg-amber-50 ring-amber-100' : 'bg-slate-50 ring-slate-100'); ?> p-4 ring-1">
                        <p class="text-[10px] font-black uppercase tracking-wide <?php echo e((int) ($purchaseSummary['remaining_purchase_qty'] ?? 0) > 0 ? 'text-amber-700' : 'text-slate-400'); ?>">Still to buy</p>
                        <p class="mt-1 text-2xl font-black <?php echo e((int) ($purchaseSummary['remaining_purchase_qty'] ?? 0) > 0 ? 'text-amber-700' : 'text-slate-500'); ?>"><?php echo e((int) ($purchaseSummary['remaining_purchase_qty'] ?? 0)); ?></p>
                    </div>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isCustomerSelfPurchase): ?>
                    <div class="mt-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-bold text-sky-800">
                        Customer self-purchase order: Dabba does not buy the goods, but arrival/warehouse workflows still apply later.
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <div class="mt-5 flex flex-wrap gap-2 rounded-2xl bg-slate-100 p-1">
                    <a href="<?php echo e(route('orders.show', [$order->id, 'purchase_tab' => 'retailers'])); ?>" class="rounded-xl px-4 py-2 text-sm font-black <?php echo e($purchaseTab === 'retailers' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'); ?>">Retailers</a>
                    <a href="<?php echo e(route('orders.show', [$order->id, 'purchase_tab' => 'events'])); ?>" class="rounded-xl px-4 py-2 text-sm font-black <?php echo e($purchaseTab === 'events' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'); ?>">Purchases</a>
                    <a href="<?php echo e(route('orders.show', [$order->id, 'purchase_tab' => 'problems'])); ?>" class="rounded-xl px-4 py-2 text-sm font-black <?php echo e($purchaseTab === 'problems' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'); ?>">Problems</a>
                </div>
            </section>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($purchaseTab === 'events'): ?>
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4">
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Purchase events</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">Retailer refs, costs and dates</h2>
                    </div>
                    <?php echo $__env->make('shared.purchasing._purchase_event_table', ['purchaseEvents' => $purchaseEvents], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </section>
            <?php elseif($purchaseTab === 'problems'): ?>
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4">
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Purchasing problems</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">Operational exceptions</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">These do not change finance automatically. Resolution happens through amendment, refund, wallet credit, repurchase or customer decision.</p>
                    </div>
                    <?php echo $__env->make('shared.purchasing._purchase_event_table', ['purchaseEvents' => $purchaseProblems], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </section>
            <?php else: ?>
                <section class="space-y-4">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $purchaseRetailerGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $retailer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                            <header class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/70 p-5 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-lg font-black text-slate-950"><?php echo e($retailer->name ?? 'Unknown retailer'); ?></p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500"><?php echo e($retailer->host ?? ''); ?></p>
                                </div>
                                <div class="flex flex-wrap gap-2 text-xs font-black">
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700 ring-1 ring-emerald-100"><?php echo e((int) ($retailer->remaining_qty ?? 0)); ?> to buy</span>
                                    <span class="rounded-full bg-sky-50 px-2.5 py-1 text-sky-700 ring-1 ring-sky-100"><?php echo e((int) ($retailer->arrived_qty ?? 0)); ?> arrived</span>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int) ($retailer->problem_qty ?? 0) > 0): ?>
                                        <span class="rounded-full bg-rose-50 px-2.5 py-1 text-rose-700 ring-1 ring-rose-100"><?php echo e((int) ($retailer->problem_qty ?? 0)); ?> problem</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </header>

                            <div class="divide-y divide-slate-100">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = collect($retailer->items ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <?php [$label, $badgeClass] = $statusBadge($item); ?>
                                    <details class="group">
                                        <summary class="grid cursor-pointer gap-3 px-5 py-4 hover:bg-slate-50 lg:grid-cols-[minmax(0,1.6fr)_90px_90px_90px_130px] lg:items-center">
                                            <div class="min-w-0">
                                                <p class="font-black text-slate-950"><?php echo e(\Illuminate\Support\Str::limit($item->item_name, 130)); ?></p>
                                                <p class="mt-1 text-xs font-semibold text-slate-500">Item #<?php echo e($item->id); ?> · Root #<?php echo e($item->root_item_id); ?></p>
                                            </div>
                                            <div class="text-sm font-black text-slate-700">Qty <?php echo e($item->quantity); ?></div>
                                            <div class="text-sm font-black text-emerald-700">Buy <?php echo e($item->purchase_remaining_qty); ?></div>
                                            <div class="text-sm font-black text-sky-700">Arr <?php echo e($item->arrived_qty); ?></div>
                                            <div><span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-black <?php echo e($badgeClass); ?>"><?php echo e($label); ?></span></div>
                                        </summary>
                                        <div class="border-t border-slate-100 bg-slate-50/60 p-5">
                                            <?php echo $__env->make('shared.purchasing._item_action_forms', ['item' => $item], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                        </div>
                                    </details>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </div>
                        </article>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <div class="rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                            <p class="font-black text-slate-900">No purchasing items found for this order.</p>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </section>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/orders/show/tabs/_purchasing.blade.php ENDPATH**/ ?>