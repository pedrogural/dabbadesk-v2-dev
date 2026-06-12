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
        $money = fn ($amount) => '£' . number_format((float) $amount, 2);
        $paymentBadge = [
            'paid' => ['label' => 'Fully Paid', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
            'part_paid' => ['label' => 'Part Paid', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
            'unpaid' => ['label' => 'Unpaid', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200', 'dot' => 'bg-rose-500'],
        ];
    ?>

    <div class="mx-auto max-w-6xl space-y-5">
        <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-950">Purchasing Desk</h1>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Orders awaiting purchase activity.</p>
                    <p class="mt-2 max-w-2xl text-xs font-semibold leading-5 text-slate-400">
                        Paid and part-paid orders are shown by default. Use All Orders only when a purchaser deliberately needs to buy before payment is recorded.
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-2 sm:min-w-[420px]">
                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">To Purchase</p>
                        <p class="mt-1 text-2xl font-black text-slate-950"><?php echo e($summary['to_buy'] ?? 0); ?></p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Awaiting Arrival</p>
                        <p class="mt-1 text-2xl font-black text-slate-950"><?php echo e($summary['awaiting_arrival'] ?? 0); ?></p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Problems</p>
                        <p class="mt-1 text-2xl font-black text-slate-950"><?php echo e($summary['problems'] ?? 0); ?></p>
                    </div>
                </div>
            </div>

            <form method="GET" action="<?php echo e(route('purchasing.index')); ?>" class="mt-5 border-t border-slate-100 pt-4">
                <input type="hidden" name="tab" value="to_buy">
                <input type="hidden" name="payment" value="<?php echo e($filters['payment']); ?>">
                <div class="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <label for="purchase-search" class="sr-only">Search orders</label>
                        <input id="purchase-search" name="q" value="<?php echo e($filters['q']); ?>" placeholder="Search order number, customer, item or retailer" class="h-12 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 text-sm font-bold text-slate-900 placeholder:text-slate-400 focus:border-indigo-300 focus:bg-white focus:ring-indigo-200">
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="<?php echo e(route('purchasing.index', ['tab' => 'to_buy', 'payment' => 'paid_or_part', 'q' => $filters['q']])); ?>" class="rounded-2xl px-4 py-3 text-sm font-black ring-1 transition <?php echo e($filters['payment'] === 'paid_or_part' ? 'bg-indigo-600 text-white ring-indigo-600' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50'); ?>">Paid & Part Paid</a>
                        <a href="<?php echo e(route('purchasing.index', ['tab' => 'to_buy', 'payment' => 'all', 'q' => $filters['q']])); ?>" class="rounded-2xl px-4 py-3 text-sm font-black ring-1 transition <?php echo e($filters['payment'] === 'all' ? 'bg-rose-600 text-white ring-rose-600' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50'); ?>">All Orders</a>
                        <button class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white hover:bg-indigo-700">Search</button>
                    </div>
                </div>
            </form>
        </section>

        <section class="space-y-3">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $queueOrder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php
                    $badge = $paymentBadge[$queueOrder['payment_status']] ?? $paymentBadge['unpaid'];
                    $retailerNames = collect($queueOrder['items'])->pluck('retailer_name')->filter()->unique()->values();
                    $remainingQty = (int) ($queueOrder['remaining_to_buy_qty'] ?? 0);
                    $awaitingQty = (int) ($queueOrder['awaiting_arrival_qty'] ?? 0);
                    $problemQty = (int) ($queueOrder['problem_qty'] ?? 0);
                    $waitingItems = collect($queueOrder['items'])->filter(fn ($item) => (int) $item->remaining_to_buy_qty > 0);
                    $waitingValue = $waitingItems->sum(fn ($item) => ((float) $item->unit_price) * ((int) $item->remaining_to_buy_qty));
                    $statusText = $problemQty > 0 ? 'Problem Needs Review' : ($remainingQty > 0 ? 'Awaiting Purchase' : ($awaitingQty > 0 ? 'Awaiting Arrival' : 'Complete'));
                ?>

                <article class="group rounded-[1.7rem] border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md sm:p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-black text-slate-950">#<?php echo e($queueOrder['order_number']); ?></h2>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-black ring-1 <?php echo e($badge['class']); ?>">
                                    <span class="h-2 w-2 rounded-full <?php echo e($badge['dot']); ?>"></span><?php echo e($badge['label']); ?>

                                </span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($queueOrder['payment_status'] === 'part_paid'): ?>
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-black text-amber-700 ring-1 ring-amber-200"><?php echo e($money($queueOrder['settled_amount'])); ?> / <?php echo e($money($queueOrder['grand_total'])); ?></span>
                                <?php elseif($queueOrder['payment_status'] === 'unpaid'): ?>
                                    <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-black text-rose-700 ring-1 ring-rose-200">Override purchase only</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <p class="mt-1 truncate text-sm font-bold text-slate-700"><?php echo e($queueOrder['customer']); ?></p>

                            <div class="mt-4 grid gap-3 text-sm sm:grid-cols-4">
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Retailers</p>
                                    <p class="mt-1 font-black text-slate-900"><?php echo e($queueOrder['retailer_count']); ?></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Awaiting Purchase</p>
                                    <p class="mt-1 font-black text-slate-900"><?php echo e($waitingItems->count()); ?> item<?php echo e($waitingItems->count() === 1 ? '' : 's'); ?> / <?php echo e($remainingQty); ?> qty</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Customer Value</p>
                                    <p class="mt-1 font-black text-slate-900"><?php echo e($money($waitingValue)); ?></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Progress</p>
                                    <p class="mt-1 font-black text-slate-900"><?php echo e($statusText); ?></p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $retailerNames; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $retailerName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <?php
                                        $rows = collect($queueOrder['items'])->where('retailer_name', $retailerName);
                                        $done = (int) $rows->sum('remaining_to_buy_qty') === 0 && (int) $rows->sum('purchased_qty') > 0;
                                    ?>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-black ring-1 <?php echo e($done ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-50 text-slate-600 ring-slate-200'); ?>"><?php echo e($retailerName); ?> <?php echo e($done ? '✓' : '○'); ?></span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-col gap-2 sm:flex-row lg:flex-col">
                            <a href="<?php echo e(route('purchasing.orders.show', $queueOrder['order_id'])); ?>" class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-indigo-700">Purchase Items</a>
                        </div>
                    </div>
                </article>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                    <p class="text-lg font-black text-slate-900">Nothing needs purchasing here.</p>
                    <p class="mt-2 text-sm font-semibold text-slate-500">Try All Orders or adjust your search.</p>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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