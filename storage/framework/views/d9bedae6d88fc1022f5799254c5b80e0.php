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
        $activeStatus = $filters['status'] ?? 'to_buy';
        $statusTabs = [
            'to_buy' => ['label' => 'To Buy', 'qty' => $summary['to_buy_qty'] ?? 0, 'orders' => $summary['to_buy_orders'] ?? 0],
            'problems' => ['label' => 'Problems', 'qty' => $summary['problem_qty'] ?? 0, 'orders' => $summary['problem_orders'] ?? 0],
            'awaiting_arrival' => ['label' => 'Awaiting Arrival', 'qty' => $summary['awaiting_arrival_qty'] ?? 0, 'orders' => $summary['awaiting_arrival_orders'] ?? 0],
        ];
        $problemLabels = [
            'supplier_cancelled' => 'Supplier cancelled',
            'lost' => 'Lost',
            'damaged' => 'Damaged',
            'wrong_item' => 'Wrong item',
            'retailer_refunded' => 'Retailer refunded',
            'unavailable' => 'Unavailable',
            'other' => 'Other',
        ];
    ?>

    <div class="space-y-5">
        <section class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-950">Purchasing Desk</h1>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Order-first purchasing. Each customer order is bought as its own basket, even when the retailer is the same.</p>
                </div>

                <div class="rounded-2xl bg-indigo-50 px-4 py-3 text-sm font-black text-indigo-800 ring-1 ring-indigo-100">
                    <?php echo e(number_format($orderGroups->count())); ?> order<?php echo e($orderGroups->count() === 1 ? '' : 's'); ?> shown
                </div>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
                <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-800">
                    <?php echo e(session('status')); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
                <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">
                    <?php echo e($errors->first()); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $statusTabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php
                        $isActive = $activeStatus === $key;
                        $url = route('purchasing.index', array_filter(['status' => $key, 'q' => $filters['q'] ?? null], fn ($v) => $v !== null && $v !== ''));
                    ?>
                    <a href="<?php echo e($url); ?>" class="rounded-3xl border p-4 shadow-sm transition <?php echo e($isActive ? 'border-indigo-200 bg-indigo-50 ring-2 ring-indigo-100' : 'border-slate-200 bg-white hover:bg-slate-50'); ?>">
                        <span class="block text-sm font-black <?php echo e($isActive ? 'text-indigo-800' : 'text-slate-600'); ?>"><?php echo e($tab['label']); ?></span>
                        <span class="mt-2 block text-3xl font-black tracking-tight text-slate-950"><?php echo e(number_format($tab['qty'])); ?></span>
                        <span class="mt-1 block text-xs font-bold text-slate-400"><?php echo e(number_format($tab['orders'])); ?> order<?php echo e($tab['orders'] == 1 ? '' : 's'); ?></span>
                    </a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="<?php echo e(route('purchasing.index')); ?>" class="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_220px_auto] lg:items-center">
                <input type="hidden" name="status" value="<?php echo e($activeStatus); ?>">
                <div>
                    <label for="q" class="sr-only">Search purchasing</label>
                    <input id="q" name="q" value="<?php echo e($filters['q'] ?? ''); ?>" type="text" placeholder="Search order number, customer, item, product code or retailer..." class="h-12 w-full rounded-2xl border-slate-300 px-4 text-sm font-semibold shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <select name="status" class="h-12 rounded-2xl border-slate-300 px-4 text-sm font-black text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $statusTabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($key); ?>" <?php if($activeStatus === $key): echo 'selected'; endif; ?>><?php echo e($tab['label']); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="h-12 rounded-2xl bg-indigo-600 px-5 text-sm font-black text-white shadow-sm transition hover:bg-indigo-700">Search</button>
                    <a href="<?php echo e(route('purchasing.index', ['status' => $activeStatus])); ?>" class="inline-flex h-12 items-center rounded-2xl border border-slate-200 px-5 text-sm font-black text-slate-600 transition hover:bg-slate-50">Clear</a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-4">
                <div class="grid grid-cols-[110px_minmax(180px,1fr)_150px_150px_120px] gap-4 text-left text-xs font-black uppercase tracking-wide text-slate-400">
                    <div>Order</div>
                    <div>Customer</div>
                    <div>Action</div>
                    <div>Payment</div>
                    <div class="text-right">Open</div>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $orderGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $orderGroup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php
                        $actionLabel = 'Review';
                        $actionClass = 'bg-slate-100 text-slate-700 ring-slate-200';

                        if ((int) $orderGroup->problem_qty > 0) {
                            $actionLabel = 'Resolve problem';
                            $actionClass = 'bg-rose-50 text-rose-700 ring-rose-100';
                        } elseif ((int) $orderGroup->pending_qty > 0) {
                            $actionLabel = 'Buy items';
                            $actionClass = 'bg-emerald-50 text-emerald-700 ring-emerald-100';
                        } elseif ((int) $orderGroup->awaiting_arrival_qty > 0) {
                            $actionLabel = 'Await arrival';
                            $actionClass = 'bg-amber-50 text-amber-700 ring-amber-100';
                        }

                        $paymentLabel = ucfirst(str_replace('_', ' ', (string) ($orderGroup->payment_status ?? 'unknown')));
                    ?>

                    <div class="grid grid-cols-[110px_minmax(180px,1fr)_150px_150px_120px] items-center gap-4 px-5 py-4 transition hover:bg-slate-50/80">
                        <div>
                            <p class="text-base font-black text-slate-950"><?php echo e($orderGroup->order_number ?: '#' . $orderGroup->order_id); ?></p>
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-slate-800"><?php echo e($orderGroup->customer_name ?: 'Customer not named'); ?></p>
                        </div>

                        <div>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 <?php echo e($actionClass); ?>"><?php echo e($actionLabel); ?></span>
                        </div>

                        <div>
                            <span class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700 ring-1 ring-indigo-100"><?php echo e($paymentLabel); ?></span>
                        </div>

                        <div class="text-right">
                            <a href="<?php echo e(route('orders.show', $orderGroup->order_id)); ?>" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-xs font-black text-white transition hover:bg-slate-800">Open ↗</a>
                        </div>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <div class="px-5 py-12 text-center">
                        <p class="text-lg font-black text-slate-900">Nothing in this purchasing queue.</p>
                        <p class="mt-1 text-sm font-semibold text-slate-500">Try a different tab or clear the search.</p>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </section>

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
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/purchasing/index.blade.php ENDPATH**/ ?>