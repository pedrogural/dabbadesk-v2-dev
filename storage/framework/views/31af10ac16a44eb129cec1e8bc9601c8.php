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

     <?php $__env->slot('header', null, []); ?> Purchasing Desk <?php $__env->endSlot(); ?>

    <?php
        $activeTab = $filters['tab'] ?? 'to_buy';
        $activePayment = $filters['payment'] ?? 'paid';
        $tabs = [
            'to_buy' => ['label' => 'To Buy', 'qty' => $summary['to_buy_qty'] ?? 0, 'orders' => $summary['to_buy_orders'] ?? 0, 'hint' => 'Paid order-first buying queue'],
            'awaiting_arrival' => ['label' => 'Awaiting Arrival', 'qty' => $summary['awaiting_arrival_qty'] ?? 0, 'orders' => $summary['awaiting_arrival_orders'] ?? 0, 'hint' => 'Bought, not fully arrived'],
            'problems' => ['label' => 'Problems', 'qty' => $summary['problem_qty'] ?? 0, 'orders' => $summary['problem_orders'] ?? 0, 'hint' => 'Needs human decision'],
            'all' => ['label' => 'All Active', 'qty' => ($summary['to_buy_qty'] ?? 0) + ($summary['awaiting_arrival_qty'] ?? 0) + ($summary['problem_qty'] ?? 0), 'orders' => $summary['visible_order_count'] ?? 0, 'hint' => 'Operational overview'],
        ];
        $paymentLabels = [
            'paid' => 'Paid only',
            'part_paid' => 'Part-paid only',
            'unpaid' => 'Unpaid only',
            'all' => 'All payment states',
        ];
        $statusBadge = function ($item) {
            if (($item->purchase_remaining_qty ?? 0) > 0 && ($item->problem_qty ?? 0) > 0) return ['Sourcing issue', 'bg-amber-50 text-amber-700 border-amber-100'];
            if (($item->purchase_remaining_qty ?? 0) > 0) return ['Ready to buy', 'bg-emerald-50 text-emerald-700 border-emerald-100'];
            if (($item->arrival_remaining_qty ?? 0) > 0) return ['Awaiting arrival', 'bg-sky-50 text-sky-700 border-sky-100'];
            if (($item->problem_qty ?? 0) > 0) return ['Problem', 'bg-rose-50 text-rose-700 border-rose-100'];
            return ['Complete', 'bg-slate-50 text-slate-600 border-slate-100'];
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
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.24em] text-indigo-500">DabbaDesk</p>
                    <h1 class="mt-1 text-2xl font-black text-slate-950">Purchasing Desk</h1>
                    <p class="mt-1 max-w-3xl text-sm font-semibold text-slate-500">Order-first purchasing. Items are grouped by customer order first, then retailer. We never merge different customer orders into one retailer basket.</p>
                </div>
                <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm font-bold text-slate-600 ring-1 ring-slate-100">
                    <?php echo e($summary['visible_order_count'] ?? 0); ?> visible order<?php echo e(($summary['visible_order_count'] ?? 0) == 1 ? '' : 's'); ?>

                </div>
            </div>

            <form method="GET" action="<?php echo e(route('purchasing.index')); ?>" class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_140px]">
                <input type="hidden" name="tab" value="<?php echo e($activeTab); ?>">
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-wide text-slate-400">Search</span>
                    <input name="q" value="<?php echo e($filters['q'] ?? ''); ?>" placeholder="Order number, customer, item, retailer, reference…" class="mt-1 h-11 w-full rounded-2xl border-slate-300 bg-white px-4 text-sm font-semibold shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-wide text-slate-400">Payment filter</span>
                    <select name="payment" class="mt-1 h-11 w-full rounded-2xl border-slate-300 bg-white px-4 text-sm font-bold shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $paymentLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <option value="<?php echo e($value); ?>" <?php if($activePayment === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>
                </label>
                <div class="flex items-end">
                    <button class="h-11 w-full rounded-2xl bg-slate-950 px-4 text-sm font-black text-white shadow-sm hover:bg-slate-800">Apply</button>
                </div>
            </form>

            <div class="mt-5 grid gap-3 md:grid-cols-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <a href="<?php echo e(route('purchasing.index', array_filter(['tab' => $key, 'payment' => $activePayment, 'q' => $filters['q'] ?? null], fn ($v) => $v !== null && $v !== ''))); ?>" class="rounded-3xl border p-4 transition <?php echo e($activeTab === $key ? 'border-indigo-200 bg-indigo-50 shadow-sm' : 'border-slate-200 bg-white hover:bg-slate-50'); ?>">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-black <?php echo e($activeTab === $key ? 'text-indigo-700' : 'text-slate-800'); ?>"><?php echo e($tab['label']); ?></p>
                                <p class="mt-1 text-xs font-semibold text-slate-500"><?php echo e($tab['hint']); ?></p>
                            </div>
                            <span class="rounded-full <?php echo e($activeTab === $key ? 'bg-white text-indigo-700 ring-indigo-100' : 'bg-slate-100 text-slate-600 ring-slate-200'); ?> px-2.5 py-1 text-xs font-black ring-1"><?php echo e($tab['orders']); ?> orders</span>
                        </div>
                        <p class="mt-4 text-2xl font-black <?php echo e($activeTab === $key ? 'text-indigo-700' : 'text-slate-950'); ?>"><?php echo e($tab['qty']); ?></p>
                        <p class="text-xs font-bold text-slate-400">quantity</p>
                    </a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </section>

        <section class="space-y-4">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $orderGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                    <header class="border-b border-slate-100 bg-slate-50/70 p-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="<?php echo e(route('orders.show', $group->order_id)); ?>" class="text-xl font-black text-indigo-700 hover:text-indigo-800">Order #<?php echo e($group->order_number); ?> ↗</a>
                                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black text-slate-600 ring-1 ring-slate-200"><?php echo e(ucfirst(str_replace('_', ' ', $group->payment_status))); ?></span>
                                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black text-slate-600 ring-1 ring-slate-200"><?php echo e($group->retailer_count); ?> retailer<?php echo e($group->retailer_count === 1 ? '' : 's'); ?></span>
                                </div>
                                <p class="mt-1 text-sm font-semibold text-slate-600"><?php echo e($group->customer_name ?: 'Unknown customer'); ?></p>
                            </div>
                            <div class="grid grid-cols-4 gap-2 text-center sm:min-w-[34rem]">
                                <div class="rounded-2xl bg-white p-3 ring-1 ring-slate-100"><p class="text-xs font-black text-slate-400">To buy</p><p class="text-lg font-black text-emerald-700"><?php echo e($group->pending_qty); ?></p></div>
                                <div class="rounded-2xl bg-white p-3 ring-1 ring-slate-100"><p class="text-xs font-black text-slate-400">Bought</p><p class="text-lg font-black text-slate-900"><?php echo e($group->purchased_qty); ?></p></div>
                                <div class="rounded-2xl bg-white p-3 ring-1 ring-slate-100"><p class="text-xs font-black text-slate-400">Awaiting</p><p class="text-lg font-black text-sky-700"><?php echo e($group->awaiting_arrival_qty); ?></p></div>
                                <div class="rounded-2xl bg-white p-3 ring-1 ring-slate-100"><p class="text-xs font-black text-slate-400">Problems</p><p class="text-lg font-black text-rose-700"><?php echo e($group->problem_qty); ?></p></div>
                            </div>
                        </div>
                    </header>

                    <div class="space-y-4 p-5">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $group->retailer_groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $retailer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <section class="rounded-3xl border border-slate-200 bg-white">
                                <div class="flex flex-col gap-2 border-b border-slate-100 p-4 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <p class="text-base font-black text-slate-950"><?php echo e($retailer->name); ?></p>
                                        <p class="text-xs font-semibold text-slate-500"><?php echo e($retailer->host ?: 'Retailer section'); ?></p>
                                    </div>
                                    <div class="flex flex-wrap gap-2 text-xs font-black">
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700 ring-1 ring-emerald-100"><?php echo e($retailer->pending_qty); ?> to buy</span>
                                        <span class="rounded-full bg-sky-50 px-2.5 py-1 text-sky-700 ring-1 ring-sky-100"><?php echo e($retailer->awaiting_arrival_qty); ?> awaiting</span>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($retailer->problem_qty > 0): ?>
                                            <span class="rounded-full bg-rose-50 px-2.5 py-1 text-rose-700 ring-1 ring-rose-100"><?php echo e($retailer->problem_qty); ?> problem</span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                </div>

                                <div class="divide-y divide-slate-100">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $retailer->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <?php [$label, $badgeClass] = $statusBadge($item); ?>
                                        <details class="group">
                                            <summary class="grid cursor-pointer gap-3 px-4 py-4 hover:bg-slate-50 lg:grid-cols-[minmax(0,1.7fr)_90px_90px_90px_130px] lg:items-center">
                                                <div class="min-w-0">
                                                    <p class="font-black text-slate-950"><?php echo e(\Illuminate\Support\Str::limit($item->item_name, 120)); ?></p>
                                                    <p class="mt-1 text-xs font-semibold text-slate-500">Root #<?php echo e($item->root_item_id); ?> · Item #<?php echo e($item->id); ?></p>
                                                </div>
                                                <div class="text-sm font-black text-slate-700">Qty <?php echo e($item->quantity); ?></div>
                                                <div class="text-sm font-black text-emerald-700">Buy <?php echo e($item->purchase_remaining_qty); ?></div>
                                                <div class="text-sm font-black text-sky-700">Arr <?php echo e($item->arrived_qty); ?></div>
                                                <div><span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-black <?php echo e($badgeClass); ?>"><?php echo e($label); ?></span></div>
                                            </summary>
                                            <div class="border-t border-slate-100 bg-slate-50/60 p-4">
                                                <?php echo $__env->make('shared.purchasing._item_action_forms', ['item' => $item], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                            </div>
                                        </details>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </div>
                            </section>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                </article>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <div class="rounded-[2rem] border border-slate-200 bg-white p-10 text-center shadow-sm">
                    <p class="text-lg font-black text-slate-900">Nothing in this tab.</p>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Try a different payment filter or clear the search.</p>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </section>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($recentEvents->isNotEmpty()): ?>
            <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Recent events</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">Latest purchasing activity</h2>
                    </div>
                </div>
                <?php echo $__env->make('shared.purchasing._purchase_event_table', ['purchaseEvents' => $recentEvents], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
<?php /**PATH /var/www/dabba-live/dabbadesk-v2/resources/views/purchasing/index.blade.php ENDPATH**/ ?>