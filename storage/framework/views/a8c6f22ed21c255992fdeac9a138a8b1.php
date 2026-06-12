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

     <?php $__env->slot('header', null, []); ?> 
        Purchasing Workspace
     <?php $__env->endSlot(); ?>

    <?php
        $orderNumber = $order->order_number ?? $order->id;
        $customer = trim((string) ($order->bill_to_company ?: $order->bill_to_name ?: 'Unknown customer'));
        $queueOrder = $queueOrder ?? null;
        $buyItems = $items->filter(fn ($item) => (int) $item->remaining_to_buy_qty > 0)->values();
        $awaitingItems = $items->filter(fn ($item) => (int) $item->awaiting_arrival_qty > 0)->values();
        $problemItems = $items->filter(fn ($item) => (int) $item->problem_qty > 0)->values();
        $tabUrls = collect($tabs)->mapWithKeys(fn ($label, $key) => [$key => route('purchasing.orders.show', ['order' => $order->id, 'tab' => $key])]);
        $paymentStatus = $queueOrder['payment_status'] ?? 'unknown';
    ?>

    <div class="space-y-6">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800"><?php echo e(session('success')); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-800">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div><?php echo e($error); ?></div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-5 py-6 text-white sm:px-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <a href="<?php echo e(route('purchasing.index')); ?>" class="text-xs font-black uppercase tracking-[0.22em] text-indigo-200 hover:text-white">← Back to queue</a>
                        <h2 class="mt-3 text-3xl font-black tracking-tight">Order #<?php echo e($orderNumber); ?></h2>
                        <p class="mt-1 text-sm font-semibold text-slate-300"><?php echo e($customer); ?></p>
                        <p class="mt-2 max-w-2xl text-xs font-semibold leading-5 text-slate-400">Purchasing is always recorded inside this customer order. Items from different customer orders are never merged into one retailer basket.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:min-w-[520px]">
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <div class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-300">To buy</div>
                            <div class="mt-1 text-2xl font-black"><?php echo e($queueOrder['remaining_to_buy_qty'] ?? 0); ?></div>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <div class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-300">Awaiting</div>
                            <div class="mt-1 text-2xl font-black"><?php echo e($queueOrder['awaiting_arrival_qty'] ?? 0); ?></div>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <div class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-300">Problems</div>
                            <div class="mt-1 text-2xl font-black"><?php echo e($queueOrder['problem_qty'] ?? 0); ?></div>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <div class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-300">Payment</div>
                            <div class="mt-2 inline-flex rounded-full bg-white px-2 py-1 text-xs font-black text-slate-900"><?php echo e(str_replace('_', '-', ucfirst($paymentStatus))); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-b border-slate-100 bg-white px-5 py-3 sm:px-6">
                <div class="flex flex-wrap gap-2">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <a href="<?php echo e($tabUrls[$key]); ?>" class="rounded-2xl px-4 py-2 text-sm font-black ring-1 transition <?php echo e($activeTab === $key ? 'bg-indigo-600 text-white ring-indigo-600 shadow-sm shadow-indigo-100' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50 hover:text-slate-950'); ?>">
                            <?php echo e($label); ?>

                        </a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <a href="<?php echo e(route('orders.show', $order->id)); ?>" class="rounded-2xl bg-slate-100 px-4 py-2 text-sm font-black text-slate-700 ring-1 ring-slate-200 hover:bg-slate-200">Order UX ↗</a>
                </div>
            </div>
        </section>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeTab === 'overview'): ?>
            <section class="grid gap-5 lg:grid-cols-3">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                    <h3 class="text-lg font-black text-slate-950">Retailer work groups</h3>
                    <p class="mt-1 text-sm text-slate-500">Retailers are sections inside this order, not global baskets.</p>

                    <div class="mt-5 divide-y divide-slate-100 rounded-2xl border border-slate-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $retailers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $retailer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="font-black text-slate-900"><?php echo e($retailer['retailer_name']); ?></div>
                                    <div class="mt-1 text-xs font-semibold text-slate-400"><?php echo e($retailer['items']->count()); ?> item<?php echo e($retailer['items']->count() === 1 ? '' : 's'); ?></div>
                                </div>
                                <div class="flex flex-wrap gap-2 text-xs font-black">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($retailer['remaining_to_buy_qty'] > 0): ?>
                                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700 ring-1 ring-indigo-200"><?php echo e($retailer['remaining_to_buy_qty']); ?> to buy</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($retailer['awaiting_arrival_qty'] > 0): ?>
                                        <span class="rounded-full bg-sky-50 px-2.5 py-1 text-sky-700 ring-1 ring-sky-200"><?php echo e($retailer['awaiting_arrival_qty']); ?> awaiting</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($retailer['problem_qty'] > 0): ?>
                                        <span class="rounded-full bg-rose-50 px-2.5 py-1 text-rose-700 ring-1 ring-rose-200"><?php echo e($retailer['problem_qty']); ?> problem</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-black text-slate-950">Next action</h3>
                    <div class="mt-4 space-y-3 text-sm font-semibold text-slate-600">
                        <a href="<?php echo e($tabUrls['buy']); ?>" class="block rounded-2xl bg-indigo-50 p-4 text-indigo-800 ring-1 ring-indigo-100">Buy: record purchase events and undo mistakes.</a>
                        <a href="<?php echo e($tabUrls['awaiting']); ?>" class="block rounded-2xl bg-sky-50 p-4 text-sky-800 ring-1 ring-sky-100">Awaiting Arrival: review bought items waiting for goods.</a>
                        <a href="<?php echo e($tabUrls['problems']); ?>" class="block rounded-2xl bg-rose-50 p-4 text-rose-800 ring-1 ring-rose-100">Problems: sourcing failures and supplier issues.</a>
                    </div>
                </div>
            </section>
        <?php elseif($activeTab === 'buy'): ?>
            <?php echo $__env->make('purchasing.partials.items', [
                'rows' => $buyItems->merge($items->filter(fn ($item) => (int) $item->remaining_to_buy_qty === 0 && (int) $item->purchased_qty > 0))->unique('item_id')->values(),
                'title' => 'Buy items',
                'empty' => 'There are no remaining items to buy for this order.',
                'showPurchaseAction' => true,
                'purchasesByRoot' => $purchasesByRoot,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($activeTab === 'awaiting'): ?>
            <?php echo $__env->make('purchasing.partials.items', ['rows' => $awaitingItems, 'title' => 'Awaiting arrival', 'empty' => 'Nothing is currently awaiting arrival.', 'showPurchaseAction' => false, 'purchasesByRoot' => $purchasesByRoot], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($activeTab === 'problems'): ?>
            <?php echo $__env->make('purchasing.partials.items', ['rows' => $problemItems, 'title' => 'Problems', 'empty' => 'No open purchasing problems for this order.', 'showPurchaseAction' => false, 'purchasesByRoot' => $purchasesByRoot], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php else: ?>
            <section class="grid gap-5 lg:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-black text-slate-950">Purchase events</h3>
                    <div class="mt-4 divide-y divide-slate-100 rounded-2xl border border-slate-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $purchases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $purchase): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <div class="p-4 <?php echo e($purchase->cancelled_at ? 'bg-slate-50 text-slate-400' : ''); ?>">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-black text-slate-900"><?php echo e($purchase->item_name); ?></div>
                                        <div class="mt-1 text-xs font-semibold text-slate-400"><?php echo e($purchase->master_retailer_name ?: 'Retailer'); ?> · Qty <?php echo e($purchase->qty); ?> · Ref <?php echo e($purchase->retailer_order_reference ?: '—'); ?></div>
                                    </div>
                                    <span class="rounded-full bg-slate-50 px-2.5 py-1 text-xs font-black text-slate-600 ring-1 ring-slate-200"><?php echo e($purchase->cancelled_at ? 'undone' : $purchase->status); ?></span>
                                </div>
                            </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <div class="p-6 text-sm font-semibold text-slate-500">No purchase events yet.</div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-black text-slate-950">Arrival matches</h3>
                    <div class="mt-4 divide-y divide-slate-100 rounded-2xl border border-slate-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $arrivals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $arrival): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-black text-slate-900"><?php echo e($arrival->item_name); ?></div>
                                        <div class="mt-1 text-xs font-semibold text-slate-400">Qty <?php echo e($arrival->qty); ?> · <?php echo e($arrival->matched_at); ?></div>
                                    </div>
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-200"><?php echo e($arrival->status); ?></span>
                                </div>
                            </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <div class="p-6 text-sm font-semibold text-slate-500">No arrival matches yet.</div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
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