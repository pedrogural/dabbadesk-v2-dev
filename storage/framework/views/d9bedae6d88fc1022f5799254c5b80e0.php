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
        Purchasing Desk
     <?php $__env->endSlot(); ?>

    <?php
        $badgeClasses = [
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'part_paid' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'unpaid' => 'bg-slate-50 text-slate-600 ring-slate-200',
        ];
        $actionClasses = [
            'Buy Items' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
            'Await Arrival' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'Resolve Problem' => 'bg-rose-50 text-rose-700 ring-rose-200',
            'Completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        ];
    ?>

    <div class="space-y-6">
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-gradient-to-br from-slate-50 to-white px-5 py-5 sm:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-indigo-500">Order-first purchasing</p>
                        <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950">Purchasing queue</h2>
                        <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                            A calm queue of customer orders needing purchasing attention. Details live inside the workspace.
                        </p>
                    </div>

                    <form method="GET" action="<?php echo e(route('purchasing.index')); ?>" class="flex w-full flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm lg:w-auto lg:min-w-[520px] lg:flex-row lg:items-center">
                        <input type="hidden" name="tab" value="<?php echo e($filters['tab']); ?>">
                        <label class="sr-only" for="purchasing-q">Search purchasing queue</label>
                        <input
                            id="purchasing-q"
                            name="q"
                            value="<?php echo e($filters['q']); ?>"
                            placeholder="Search order, customer, item or retailer"
                            class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800 placeholder:text-slate-400 focus:border-indigo-300 focus:bg-white focus:ring-indigo-200"
                        >
                        <select name="payment" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-bold text-slate-700 focus:border-indigo-300 focus:bg-white focus:ring-indigo-200">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $paymentOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($value); ?>" <?php if($filters['payment'] === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                        <button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-black text-white shadow-sm transition hover:bg-indigo-700">
                            Search
                        </button>
                    </form>
                </div>
            </div>

            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex flex-wrap gap-2">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php
                            $isActive = $filters['tab'] === $key;
                            $url = route('purchasing.index', array_filter([
                                'tab' => $key,
                                'payment' => $filters['payment'],
                                'q' => $filters['q'],
                            ], fn ($value) => $value !== null && $value !== ''));
                        ?>
                        <a
                            href="<?php echo e($url); ?>"
                            class="inline-flex items-center gap-2 rounded-2xl px-4 py-2 text-sm font-black ring-1 transition <?php echo e($isActive ? 'bg-indigo-600 text-white ring-indigo-600 shadow-sm shadow-indigo-100' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50 hover:text-slate-900'); ?>"
                        >
                            <span><?php echo e($tab['label']); ?></span>
                            <span class="rounded-full px-2 py-0.5 text-[11px] <?php echo e($isActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500'); ?>"><?php echo e($tab['count']); ?></span>
                        </a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">
                        <tr>
                            <th class="px-5 py-3 sm:px-6">Order</th>
                            <th class="px-5 py-3">Customer</th>
                            <th class="px-5 py-3">Action</th>
                            <th class="px-5 py-3">Payment</th>
                            <th class="px-5 py-3 text-right">Qty</th>
                            <th class="px-5 py-3 text-right">Open</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $queueOrder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr class="transition hover:bg-indigo-50/30">
                                <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                    <div class="font-black text-slate-950">#<?php echo e($queueOrder['order_number']); ?></div>
                                    <div class="mt-1 text-xs font-semibold text-slate-400"><?php echo e($queueOrder['order_status'] ?: 'active'); ?></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="max-w-[320px] truncate font-bold text-slate-800"><?php echo e($queueOrder['customer']); ?></div>
                                    <div class="mt-1 max-w-[320px] truncate text-xs font-semibold text-slate-400"><?php echo e($queueOrder['email']); ?></div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 <?php echo e($actionClasses[$queueOrder['action']] ?? 'bg-slate-50 text-slate-600 ring-slate-200'); ?>">
                                        <?php echo e($queueOrder['action']); ?>

                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 <?php echo e($badgeClasses[$queueOrder['payment_status']] ?? 'bg-slate-50 text-slate-600 ring-slate-200'); ?>">
                                        <?php echo e(str_replace('_', '-', ucfirst($queueOrder['payment_status']))); ?>

                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <div class="font-black text-slate-900">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($filters['tab'] === 'awaiting_arrival'): ?>
                                            <?php echo e($queueOrder['awaiting_arrival_qty']); ?> awaiting
                                        <?php elseif($filters['tab'] === 'problems'): ?>
                                            <?php echo e($queueOrder['problem_qty']); ?> problem
                                        <?php elseif($filters['tab'] === 'completed'): ?>
                                            <?php echo e($queueOrder['purchased_qty']); ?> bought
                                        <?php else: ?>
                                            <?php echo e($queueOrder['remaining_to_buy_qty']); ?> to buy
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                    <div class="mt-1 text-xs font-semibold text-slate-400"><?php echo e($queueOrder['retailer_count']); ?> retailer<?php echo e($queueOrder['retailer_count'] === 1 ? '' : 's'); ?></div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <a href="<?php echo e(route('purchasing.orders.show', $queueOrder['order_id'])); ?>" class="inline-flex items-center rounded-xl bg-slate-950 px-3 py-2 text-xs font-black text-white shadow-sm transition hover:bg-indigo-700">
                                        Open Workspace
                                    </a>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <tr>
                                <td colspan="6" class="px-5 py-14 text-center sm:px-6">
                                    <div class="text-lg font-black text-slate-800">Nothing here</div>
                                    <p class="mt-2 text-sm font-medium text-slate-500">This queue has no matching orders right now.</p>
                                </td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
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