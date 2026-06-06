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
        Dashboard
     <?php $__env->endSlot(); ?>

    <div class="space-y-6">

        <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        DabbaDesk Dashboard
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Live read-only overview of orders, finance and operational attention points.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a
                        href="<?php echo e(route('money-desk.index')); ?>"
                        class="inline-flex rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        Open Money Desk
                    </a>

                    <a
                        href="<?php echo e(route('money-desk.anomalies')); ?>"
                        class="inline-flex rounded-2xl bg-rose-600 px-4 py-3 text-sm font-semibold text-white hover:bg-rose-700"
                    >
                        Financial checks
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-rose-200 bg-rose-50 p-6 shadow-sm">
                <p class="text-sm font-semibold text-rose-700">
                    Needs finance attention
                </p>

                <p class="mt-3 text-4xl font-bold text-rose-700">
                    <?php echo e($alerts['finance_anomalies'] ?? 0); ?>

                </p>

                <p class="mt-2 text-sm text-rose-700/80">
                    Orders, wallet rows or ledger entries that may need review.
                </p>

                <a
                    href="<?php echo e(route('money-desk.anomalies')); ?>"
                    class="mt-5 inline-flex rounded-2xl bg-white px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100"
                >
                    Review checks
                </a>
            </div>

            <div class="rounded-3xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                <p class="text-sm font-semibold text-amber-700">
                    Waiting for payment
                </p>

                <p class="mt-3 text-4xl font-bold text-amber-700">
                    <?php echo e($operations['orders_waiting_payment'] ?? 0); ?>

                </p>

                <p class="mt-2 text-sm text-amber-700/80">
                    Orders that still appear to need customer payment.
                </p>
            </div>

            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                <p class="text-sm font-semibold text-emerald-700">
                    Waiting to purchase
                </p>

                <p class="mt-3 text-4xl font-bold text-emerald-700">
                    <?php echo e($operations['orders_waiting_purchase'] ?? 0); ?>

                </p>

                <p class="mt-2 text-sm text-emerald-700/80">
                    Paid orders that may need purchasing work.
                </p>
            </div>

            <div class="rounded-3xl border border-indigo-200 bg-indigo-50 p-6 shadow-sm">
                <p class="text-sm font-semibold text-indigo-700">
                    Wallet liability
                </p>

                <p class="mt-3 text-4xl font-bold text-indigo-700">
                    £<?php echo e(number_format($finance['wallet_liability'] ?? 0, 2)); ?>

                </p>

                <p class="mt-2 text-sm text-indigo-700/80">
                    Customer balance currently owed and reusable.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">Orders created today</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">
                    <?php echo e($today['orders_created_today'] ?? 0); ?>

                </p>
                <p class="mt-2 text-xs text-slate-400">New order snapshots</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">Payments today</p>
                <p class="mt-3 text-3xl font-bold text-emerald-600">
                    £<?php echo e(number_format($today['payments_received_today'] ?? 0, 2)); ?>

                </p>
                <p class="mt-2 text-xs text-slate-400">Real money received today</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">Arrivals today</p>
                <p class="mt-3 text-3xl font-bold text-indigo-600">
                    <?php echo e($operations['arrivals_today'] ?? 0); ?>

                </p>
                <p class="mt-2 text-xs text-slate-400">Warehouse arrival assignments</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">Ready for collection / delivery</p>
                <p class="mt-3 text-3xl font-bold text-purple-600">
                    <?php echo e($operations['orders_ready_for_collection'] ?? 0); ?>

                </p>
                <p class="mt-2 text-xs text-slate-400">Customer-facing release work</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-900">
                    Finance attention breakdown
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    These are read-only warning counters.
                </p>

                <div class="mt-5 space-y-3">
                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <span class="text-sm text-slate-600">Overpaid orders</span>
                        <span class="font-bold text-rose-600"><?php echo e($alerts['over_settled_orders'] ?? 0); ?></span>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <span class="text-sm text-slate-600">Paid but still due</span>
                        <span class="font-bold text-amber-600"><?php echo e($alerts['paid_but_due_orders'] ?? 0); ?></span>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <span class="text-sm text-slate-600">No settlement history</span>
                        <span class="font-bold text-slate-700"><?php echo e($alerts['orders_with_no_transactions'] ?? 0); ?></span>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <span class="text-sm text-slate-600">Wallet issues</span>
                        <span class="font-bold text-indigo-600"><?php echo e($alerts['wallet_problems'] ?? 0); ?></span>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <span class="text-sm text-slate-600">Refund issues</span>
                        <span class="font-bold text-purple-600"><?php echo e($alerts['refund_problems'] ?? 0); ?></span>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <span class="text-sm text-slate-600">Loose real-money entries</span>
                        <span class="font-bold text-orange-600"><?php echo e($alerts['loose_ledger_entries'] ?? 0); ?></span>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-2 rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-900">
                    Recent orders
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Latest order snapshots with basic payment position.
                </p>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                                <th class="py-3 pr-4">Order</th>
                                <th class="py-3 pr-4">Customer</th>
                                <th class="py-3 pr-4">Status</th>
                                <th class="py-3 pr-4 text-right">Total</th>
                                <th class="py-3 pr-4 text-right">Due</th>
                                <th class="py-3 pr-4 text-right">Open</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td class="py-3 pr-4">
                                        <div class="font-semibold text-slate-800">
                                            #<?php echo e($order->order_number); ?>

                                        </div>
                                        <div class="text-xs text-slate-400">
                                            <?php echo e($order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d M Y') : 'No date'); ?>

                                        </div>
                                    </td>

                                    <td class="py-3 pr-4 text-slate-700">
                                        <?php echo e($order->bill_to_name ?: trim(($order->first_name ?? '') . ' ' . ($order->last_name ?? '')) ?: 'Unknown customer'); ?>

                                    </td>

                                    <td class="py-3 pr-4">
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                            <?php echo e(str_replace('_', ' ', $order->status)); ?>

                                        </span>
                                    </td>

                                    <td class="py-3 pr-4 text-right font-semibold text-slate-900">
                                        £<?php echo e(number_format($order->grand_total ?? 0, 2)); ?>

                                    </td>

                                    <td class="py-3 pr-4 text-right font-bold <?php echo e(($order->balance_due ?? 0) > 0 ? 'text-rose-600' : 'text-slate-400'); ?>">
                                        £<?php echo e(number_format($order->balance_due ?? 0, 2)); ?>

                                    </td>

                                    <td class="py-3 pr-4 text-right">
                                        <a
                                            href="<?php echo e(route('money-desk.orders.show', $order->id)); ?>"
                                            class="inline-flex rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                                        >
                                            Finance
                                        </a>
                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-400">
                                        No recent orders found.
                                    </td>
                                </tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
            <h2 class="text-lg font-bold text-slate-900">
                Recent money movement
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Real money ledger events: payments and refunds.
            </p>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                            <th class="py-3 pr-4">Date</th>
                            <th class="py-3 pr-4">Customer</th>
                            <th class="py-3 pr-4">Type</th>
                            <th class="py-3 pr-4">Reference</th>
                            <th class="py-3 pr-4 text-right">Amount</th>
                            <th class="py-3 pr-4 text-right">Open</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $recentPayments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr>
                                <td class="py-3 pr-4 text-slate-500">
                                    <?php echo e($payment->occurred_at ? \Carbon\Carbon::parse($payment->occurred_at)->format('d M Y') : '—'); ?>

                                </td>

                                <td class="py-3 pr-4">
                                    <div class="font-semibold text-slate-800">
                                        <?php echo e(trim(($payment->first_name ?? '') . ' ' . ($payment->last_name ?? '')) ?: 'Unknown customer'); ?>

                                    </div>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($payment->company_name): ?>
                                        <div class="text-xs text-slate-400">
                                            <?php echo e($payment->company_name); ?>

                                        </div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>

                                <td class="py-3 pr-4">
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                        <?php echo e(str_replace('_', ' ', $payment->type)); ?>

                                    </span>
                                </td>

                                <td class="py-3 pr-4 text-slate-600">
                                    <?php echo e($payment->reference ?: '—'); ?>

                                </td>

                                <td class="py-3 pr-4 text-right font-semibold text-slate-900">
                                    <?php echo e($payment->currency ?? 'GBP'); ?> <?php echo e(number_format($payment->amount ?? 0, 2)); ?>

                                </td>

                                <td class="py-3 pr-4 text-right">
                                    <a
                                        href="<?php echo e(route('money-desk.customers.show', $payment->customer_id)); ?>"
                                        class="inline-flex rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                                    >
                                        Customer
                                    </a>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-400">
                                    No recent money movement found.
                                </td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

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
<?php /**PATH /var/www/dabba-live/dabbadesk-v2/resources/views/dashboard.blade.php ENDPATH**/ ?>