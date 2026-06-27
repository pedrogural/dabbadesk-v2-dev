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

    <?php
        $money = fn ($value) => '£' . number_format((float) $value, 2);
        $pct = function ($part, $total) {
            $total = max(1, (int) $total);
            return min(100, max(0, round(((int) $part / $total) * 100)));
        };
    ?>

    <div class="mx-auto max-w-7xl space-y-5">
        <section class="rounded-[1.75rem] border border-indigo-100 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-indigo-600">Purchase Desk V2</p>
                    <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-950">Purchasing queue</h1>
                    <p class="mt-1 max-w-3xl text-sm font-semibold text-slate-500">Read-only Pass 2. This screen calculates what needs buying from active order items, purchase events and pre-purchase issue records. No purchase actions are enabled yet.</p>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-5 lg:min-w-[680px]">
                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Orders</p><p class="mt-1 text-lg font-black text-slate-950"><?php echo e($summary['orders_count']); ?></p></div>
                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Active qty</p><p class="mt-1 text-lg font-black text-slate-950"><?php echo e($summary['active_item_qty']); ?></p></div>
                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">To buy</p><p class="mt-1 text-lg font-black text-slate-950"><?php echo e($summary['remaining_to_buy_qty']); ?></p></div>
                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Purchased</p><p class="mt-1 text-lg font-black text-slate-950"><?php echo e($summary['purchased_qty']); ?></p></div>
                    <div class="rounded-2xl bg-amber-50 p-3 ring-1 ring-amber-100"><p class="text-[10px] font-black uppercase tracking-wide text-amber-600">Problems</p><p class="mt-1 text-lg font-black text-amber-800"><?php echo e($summary['pre_purchase_problem_qty']); ?></p></div>
                </div>
            </div>
        </section>

        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="<?php echo e(route('purchase-desk-v2.index')); ?>" x-data="{ timer: null }" class="grid gap-3 lg:grid-cols-[1fr_240px]">
                <div>
                    <label class="text-xs font-black uppercase tracking-wide text-slate-400">Live search</label>
                    <input
                        type="search"
                        name="q"
                        value="<?php echo e($filters['q']); ?>"
                        placeholder="Customer, order number, email, product code, item description, address..."
                        class="mt-1 w-full rounded-2xl border-slate-300 text-sm font-semibold"
                        @input="clearTimeout(timer); timer = setTimeout(() => $el.form.submit(), 450)"
                    >
                </div>
                <div>
                    <label class="text-xs font-black uppercase tracking-wide text-slate-400">Payment filter</label>
                    <select name="payment" class="mt-1 w-full rounded-2xl border-slate-300 text-sm font-bold" @change="$el.form.submit()">
                        <option value="paid_or_part" <?php if($filters['payment'] === 'paid_or_part'): echo 'selected'; endif; ?>>Paid / Part-paid</option>
                        <option value="pending_payment" <?php if($filters['payment'] === 'pending_payment'): echo 'selected'; endif; ?>>Pending payment</option>
                        <option value="all_active" <?php if($filters['payment'] === 'all_active'): echo 'selected'; endif; ?>>All active orders</option>
                    </select>
                </div>
            </form>
        </section>

        <section class="space-y-3">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php
                    $customer = $order->bill_to_company ?: ($order->bill_to_name ?: 'Unknown customer');
                    $purchasedPct = $pct($order->purchased_qty, $order->active_item_qty);
                    $arrivedPct = $pct($order->arrived_qty, $order->active_item_qty);
                ?>
                <article class="rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="<?php echo e(route('orders.show', $order->id)); ?>" class="text-lg font-black text-indigo-700 hover:text-indigo-900" title="Open order page">#<?php echo e($order->order_number); ?> ↗</a>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-slate-600"><?php echo e(str_replace('_', ' ', $order->status)); ?></span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int) $order->pre_purchase_problem_qty > 0): ?>
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-black text-amber-700 ring-1 ring-amber-100"><?php echo e($order->pre_purchase_problem_qty); ?> pre-purchase problem</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <p class="mt-1 truncate text-sm font-bold text-slate-800"><?php echo e($customer); ?></p>
                            <p class="mt-1 text-xs font-semibold text-slate-400">Operator: <?php echo e($order->operator_name ?: 'Unknown'); ?> · Created <?php echo e(optional($order->created_at ? \Carbon\Carbon::parse($order->created_at) : null)->format('d M Y')); ?></p>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-4 xl:w-[620px]">
                            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Order total</p><p class="mt-1 text-sm font-black text-slate-950"><?php echo e($money($order->grand_total)); ?></p></div>
                            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Due</p><p class="mt-1 text-sm font-black <?php echo e((float) $order->balance_due > 0 ? 'text-rose-700' : 'text-emerald-700'); ?>"><?php echo e($money($order->balance_due)); ?></p></div>
                            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Purchased</p><p class="mt-1 text-sm font-black text-slate-950"><?php echo e($order->purchased_qty); ?> / <?php echo e($order->active_item_qty); ?></p></div>
                            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Arrived</p><p class="mt-1 text-sm font-black text-slate-950"><?php echo e($order->arrived_qty); ?> / <?php echo e($order->active_item_qty); ?></p></div>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-2 lg:grid-cols-2">
                        <div>
                            <div class="mb-1 flex justify-between text-[11px] font-black uppercase tracking-wide text-slate-400"><span>Purchased progress</span><span><?php echo e($purchasedPct); ?>%</span></div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-indigo-500" style="width: <?php echo e($purchasedPct); ?>%"></div></div>
                        </div>
                        <div>
                            <div class="mb-1 flex justify-between text-[11px] font-black uppercase tracking-wide text-slate-400"><span>Arrivals progress</span><span><?php echo e($arrivedPct); ?>%</span></div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" style="width: <?php echo e($arrivedPct); ?>%"></div></div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-black">
                        <a href="<?php echo e(route('purchase-desk-v2.orders.show', $order->id)); ?>" class="rounded-full bg-indigo-600 px-3 py-1.5 text-white hover:bg-indigo-700">Purchase items</a>
                        <a href="<?php echo e(route('orders.show', $order->id)); ?>" class="rounded-full bg-slate-100 px-3 py-1.5 text-slate-700 hover:bg-slate-200">Order page</a>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($order->draft_order_id)): ?>
                            <a href="<?php echo e(route('draft-orders.show', $order->draft_order_id)); ?>" class="rounded-full bg-slate-100 px-3 py-1.5 text-slate-700 hover:bg-slate-200">Draft page</a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <a href="<?php echo e(route('money-desk.orders.show', $order->id)); ?>" class="rounded-full bg-slate-100 px-3 py-1.5 text-slate-700 hover:bg-slate-200">Finance page</a>
                    </div>
                </article>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-white p-8 text-center text-sm font-bold text-slate-500">No orders matched this filter.</div>
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
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/purchase-desk-v2/index.blade.php ENDPATH**/ ?>