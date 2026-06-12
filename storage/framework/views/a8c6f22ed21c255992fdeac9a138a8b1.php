<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

     <?php $__env->slot('header', null, []); ?> Purchasing Workspace <?php $__env->endSlot(); ?>

    <?php
        $customerName = $order->bill_to_company ?: $order->bill_to_name;
        $tabs = [
            'overview' => 'Overview',
            'to_buy' => 'Items To Buy',
            'awaiting_arrival' => 'Awaiting Arrival',
            'problems' => 'Problems',
            'timeline' => 'Timeline',
        ];
        $statusBadge = function ($item) {
            if (($item->purchase_remaining_qty ?? 0) > 0 && ($item->problem_qty ?? 0) > 0) return ['Sourcing issue', 'bg-amber-50 text-amber-700 border-amber-100'];
            if (($item->purchase_remaining_qty ?? 0) > 0) return ['Ready to buy', 'bg-emerald-50 text-emerald-700 border-emerald-100'];
            if (($item->arrival_remaining_qty ?? 0) > 0) return ['Awaiting arrival', 'bg-sky-50 text-sky-700 border-sky-100'];
            if (($item->problem_qty ?? 0) > 0) return ['Problem', 'bg-rose-50 text-rose-700 border-rose-100'];
            return ['Complete', 'bg-slate-50 text-slate-600 border-slate-100'];
        };
        $paymentClass = match ($order->payment_status) {
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'part_paid' => 'bg-amber-50 text-amber-700 ring-amber-100',
            default => 'bg-rose-50 text-rose-700 ring-rose-100',
        };
    ?>

    <div class="space-y-6">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status') || session('success')): ?>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 shadow-sm">
                <?php echo e(session('status') ?: session('success')); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800 shadow-sm">
                <?php echo e($errors->first()); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="<?php echo e(route('purchasing.index')); ?>" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 hover:bg-slate-200">← Purchasing Desk</a>
                        <a href="<?php echo e(route('orders.show', $order->id)); ?>" class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700 ring-1 ring-indigo-100">Order UX ↗</a>
                    </div>
                    <p class="mt-4 text-xs font-black uppercase tracking-[0.24em] text-indigo-500">Purchasing workspace</p>
                    <h1 class="mt-1 text-2xl font-black text-slate-950">Order #<?php echo e($order->order_number); ?></h1>
                    <p class="mt-1 text-sm font-semibold text-slate-600"><?php echo e($customerName ?: 'Unknown customer'); ?> · <?php echo e(ucfirst(str_replace('_', ' ', (string) $order->status))); ?></p>
                    <p class="mt-2 max-w-3xl text-sm font-semibold text-slate-500">Purchases are recorded inside this customer order only. Even if another customer has items from the same retailer, they are never merged into one basket.</p>
                </div>
                <div class="grid gap-2 text-sm font-bold sm:min-w-[18rem]">
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-100">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-400">Payment</p>
                        <p class="mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 <?php echo e($paymentClass); ?>"><?php echo e(ucfirst(str_replace('_', ' ', $order->payment_status))); ?></p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-100">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-400">Settled / Total</p>
                        <p class="mt-1 font-black text-slate-950">£<?php echo e(number_format((float) $order->settled_amount, 2)); ?> / £<?php echo e(number_format((float) $order->grand_total, 2)); ?></p>
                    </div>
                </div>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-5">
                <div class="rounded-3xl bg-slate-50 p-4 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Order qty</p><p class="mt-1 text-2xl font-black text-slate-950"><?php echo e((int) ($summary['item_qty'] ?? 0)); ?></p></div>
                <div class="rounded-3xl bg-emerald-50 p-4 ring-1 ring-emerald-100"><p class="text-[10px] font-black uppercase tracking-wide text-emerald-700">To buy</p><p class="mt-1 text-2xl font-black text-emerald-700"><?php echo e((int) ($summary['remaining_purchase_qty'] ?? 0)); ?></p></div>
                <div class="rounded-3xl bg-indigo-50 p-4 ring-1 ring-indigo-100"><p class="text-[10px] font-black uppercase tracking-wide text-indigo-700">Purchased</p><p class="mt-1 text-2xl font-black text-indigo-700"><?php echo e((int) ($summary['purchased_qty'] ?? 0)); ?></p></div>
                <div class="rounded-3xl bg-sky-50 p-4 ring-1 ring-sky-100"><p class="text-[10px] font-black uppercase tracking-wide text-sky-700">Awaiting arrival</p><p class="mt-1 text-2xl font-black text-sky-700"><?php echo e((int) ($summary['remaining_arrival_qty'] ?? 0)); ?></p></div>
                <div class="rounded-3xl bg-rose-50 p-4 ring-1 ring-rose-100"><p class="text-[10px] font-black uppercase tracking-wide text-rose-700">Problems</p><p class="mt-1 text-2xl font-black text-rose-700"><?php echo e((int) ($summary['problem_qty'] ?? 0)); ?></p></div>
            </div>

            <div class="mt-5 flex flex-wrap gap-2 rounded-2xl bg-slate-100 p-1">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <a href="<?php echo e(route('purchasing.orders.show', [$order->id, 'tab' => $key])); ?>" class="rounded-xl px-4 py-2 text-sm font-black <?php echo e($activeTab === $key ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'); ?>"><?php echo e($label); ?></a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </section>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeTab === 'timeline'): ?>
            <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Timeline</p>
                <h2 class="mt-1 text-lg font-black text-slate-950">Purchase events</h2>
                <?php echo $__env->make('shared.purchasing._purchase_event_table', ['purchaseEvents' => $purchaseEvents], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </section>
        <?php elseif($activeTab === 'problems'): ?>
            <section class="space-y-4">
                <div class="rounded-[2rem] border border-rose-200 bg-rose-50 p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-rose-700">Problems</p>
                    <h2 class="mt-1 text-lg font-black text-rose-950">Operational exceptions</h2>
                    <p class="mt-1 text-sm font-semibold text-rose-800">Purchasing problems do not change finance automatically. Resolve through repurchase, amendment, refund, wallet credit, removal or customer decision.</p>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $problemItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php [$label, $badgeClass] = $statusBadge($item); ?>
                    <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="font-black text-slate-950"><?php echo e(\Illuminate\Support\Str::limit($item->item_name, 150)); ?></p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">Item #<?php echo e($item->id); ?> · Root #<?php echo e($item->root_item_id); ?> · <?php echo e($item->retailer_display_name); ?></p>
                            </div>
                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-black <?php echo e($badgeClass); ?>"><?php echo e($label); ?></span>
                        </div>
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <?php echo $__env->make('shared.purchasing._item_action_forms', ['item' => $item], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    </article>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-10 text-center shadow-sm">
                        <p class="text-lg font-black text-slate-900">No problem items for this order.</p>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($problemEvents->isNotEmpty()): ?>
                    <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Problem event history</p>
                        <?php echo $__env->make('shared.purchasing._purchase_event_table', ['purchaseEvents' => $problemEvents], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </section>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </section>
        <?php elseif($activeTab === 'to_buy' || $activeTab === 'awaiting_arrival'): ?>
            <?php $activeItems = $activeTab === 'to_buy' ? $toBuyItems : $arrivalItems; ?>
            <section class="space-y-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $activeItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php [$label, $badgeClass] = $statusBadge($item); ?>
                    <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <header class="grid gap-3 border-b border-slate-100 bg-slate-50/70 p-5 lg:grid-cols-[minmax(0,1fr)_90px_90px_90px_130px] lg:items-center">
                            <div class="min-w-0">
                                <p class="font-black text-slate-950"><?php echo e(\Illuminate\Support\Str::limit($item->item_name, 150)); ?></p>
                                <p class="mt-1 text-xs font-semibold text-slate-500"><?php echo e($item->retailer_display_name); ?> · Item #<?php echo e($item->id); ?> · Root #<?php echo e($item->root_item_id); ?></p>
                            </div>
                            <div class="text-sm font-black text-slate-700">Qty <?php echo e($item->quantity); ?></div>
                            <div class="text-sm font-black text-emerald-700">Buy <?php echo e($item->purchase_remaining_qty); ?></div>
                            <div class="text-sm font-black text-sky-700">Arr <?php echo e($item->arrived_qty); ?></div>
                            <div><span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-black <?php echo e($badgeClass); ?>"><?php echo e($label); ?></span></div>
                        </header>
                        <div class="p-5">
                            <?php echo $__env->make('shared.purchasing._item_action_forms', ['item' => $item], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    </article>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-10 text-center shadow-sm">
                        <p class="text-lg font-black text-slate-900">Nothing here for this order.</p>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </section>
        <?php else: ?>
            <section class="space-y-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $retailerGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $retailer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <header class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/70 p-5 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="text-lg font-black text-slate-950"><?php echo e($retailer->name ?? 'Unknown retailer'); ?></p>
                                <p class="mt-1 text-xs font-semibold text-slate-500"><?php echo e($retailer->host ?? ''); ?></p>
                            </div>
                            <div class="flex flex-wrap gap-2 text-xs font-black">
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700 ring-1 ring-emerald-100"><?php echo e((int) ($retailer->remaining_qty ?? 0)); ?> to buy</span>
                                <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700 ring-1 ring-indigo-100"><?php echo e((int) ($retailer->purchased_qty ?? 0)); ?> purchased</span>
                                <span class="rounded-full bg-sky-50 px-2.5 py-1 text-sky-700 ring-1 ring-sky-100"><?php echo e((int) ($retailer->arrived_qty ?? 0)); ?> arrived</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int) ($retailer->problem_qty ?? 0) > 0): ?>
                                    <span class="rounded-full bg-rose-50 px-2.5 py-1 text-rose-700 ring-1 ring-rose-100"><?php echo e((int) ($retailer->problem_qty ?? 0)); ?> problem</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </header>
                        <div class="divide-y divide-slate-100">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = collect($retailer->items ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <?php [$label, $badgeClass] = $statusBadge($item); ?>
                                <div class="grid gap-3 px-5 py-4 lg:grid-cols-[minmax(0,1.6fr)_90px_90px_90px_130px] lg:items-center">
                                    <div class="min-w-0">
                                        <p class="font-black text-slate-950"><?php echo e(\Illuminate\Support\Str::limit($item->item_name, 130)); ?></p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500">Item #<?php echo e($item->id); ?> · Root #<?php echo e($item->root_item_id); ?></p>
                                    </div>
                                    <div class="text-sm font-black text-slate-700">Qty <?php echo e($item->quantity); ?></div>
                                    <div class="text-sm font-black text-emerald-700">Buy <?php echo e($item->purchase_remaining_qty); ?></div>
                                    <div class="text-sm font-black text-sky-700">Arr <?php echo e($item->arrived_qty); ?></div>
                                    <div><span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-black <?php echo e($badgeClass); ?>"><?php echo e($label); ?></span></div>
                                </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </div>
                    </article>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-10 text-center shadow-sm">
                        <p class="text-lg font-black text-slate-900">No purchasing items found for this order.</p>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </section>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/purchasing/show.blade.php ENDPATH**/ ?>