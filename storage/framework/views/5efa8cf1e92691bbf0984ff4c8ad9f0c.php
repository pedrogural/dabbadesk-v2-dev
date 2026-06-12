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
        Order Finance
     <?php $__env->endSlot(); ?>

    <div class="space-y-6">

        <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <a href="<?php echo e(route('money-desk.index')); ?>" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">
                        ← Back to Money Desk
                    </a>

                    <h1 class="mt-3 text-2xl font-bold text-slate-900">
                        Order #<?php echo e($order->order_number); ?>

                    </h1>

                    <div class="mt-2 flex flex-wrap gap-2 text-sm text-slate-500">
                        <span><?php echo e($order->bill_to_name ?: trim(($order->first_name ?? '') . ' ' . ($order->last_name ?? ''))); ?></span>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->bill_to_company): ?>
                            <span>· <?php echo e($order->bill_to_company); ?></span>
                        <?php elseif($order->company_name): ?>
                            <span>· <?php echo e($order->company_name); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->bill_to_email): ?>
                            <span>· <?php echo e($order->bill_to_email); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <span>· Status: <?php echo e(str_replace('_', ' ', $order->status)); ?></span>
                    </div>
                </div>

                <a
                    href="<?php echo e(route('money-desk.customers.show', $order->customer_id)); ?>"
                    class="inline-flex w-fit rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    View customer finance
                </a>
            </div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($warnings)): ?>
            <div class="rounded-3xl border border-amber-200 bg-amber-50 p-6">
                <h2 class="text-lg font-bold text-amber-900">
                    Check this order
                </h2>

                <div class="mt-4 space-y-2">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $warnings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $warning): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="rounded-2xl bg-white/70 px-4 py-3 text-sm font-semibold text-amber-800">
                            <?php echo e($warning); ?>

                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">Order total</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">
                    £<?php echo e(number_format($summary['order_total'] ?? 0, 2)); ?>

                </p>
                <p class="mt-2 text-xs text-slate-400">Total customer was asked to pay</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">Payments used</p>
                <p class="mt-3 text-3xl font-bold text-emerald-600">
                    £<?php echo e(number_format($summary['payments_used'] ?? 0, 2)); ?>

                </p>
                <p class="mt-2 text-xs text-slate-400">Customer money used on this order</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">Wallet used</p>
                <p class="mt-3 text-3xl font-bold text-indigo-600">
                    £<?php echo e(number_format($summary['wallet_used'] ?? 0, 2)); ?>

                </p>
                <p class="mt-2 text-xs text-slate-400">Existing wallet balance used here</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">Refund effect</p>
                <p class="mt-3 text-3xl font-bold text-rose-600">
                    £<?php echo e(number_format($summary['refunds'] ?? 0, 2)); ?>

                </p>
                <p class="mt-2 text-xs text-slate-400">Value refunded/reduced from order</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">Still due</p>
                <p class="mt-3 text-3xl font-bold <?php echo e(($summary['balance_due'] ?? 0) > 0 ? 'text-rose-600' : 'text-slate-400'); ?>">
                    £<?php echo e(number_format($summary['balance_due'] ?? 0, 2)); ?>

                </p>
                <p class="mt-2 text-xs text-slate-400">Remaining unpaid amount</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

            <div class="xl:col-span-2 rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-900">
                    Order payment story
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Plain-English view of how this order was settled.
                </p>

                <div class="mt-6 space-y-4">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $timeline; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="font-semibold text-slate-900">
                                        <?php echo e($event->plain_label); ?>

                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        <?php echo e($event->plain_explanation); ?>

                                    </p>

                                    <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-400">
                                        <span>
                                            <?php echo e($event->created_at ? \Carbon\Carbon::parse($event->created_at)->format('d M Y H:i') : 'No date'); ?>

                                        </span>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($event->payment_type_name): ?>
                                            <span>· <?php echo e($event->payment_type_name); ?></span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($event->reference): ?>
                                            <span>· Ref: <?php echo e($event->reference); ?></span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($event->note): ?>
                                        <p class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-600">
                                            <?php echo e($event->note); ?>

                                        </p>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>

                                <div class="text-right">
                                    <p class="text-lg font-bold text-slate-900">
                                        <?php echo e($event->currency ?? 'GBP'); ?> <?php echo e(number_format($event->amount ?? 0, 2)); ?>

                                    </p>

                                    <p class="mt-1 text-xs uppercase tracking-wide text-slate-400">
                                        <?php echo e($event->status); ?>

                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                            No payment or settlement events found for this order.
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                    <h2 class="text-lg font-bold text-slate-900">
                        Snapshot totals
                    </h2>

                    <div class="mt-5 space-y-4 text-sm">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <span class="text-slate-500">Items subtotal</span>
                            <span class="font-semibold text-slate-900">£<?php echo e(number_format($order->subtotal ?? 0, 2)); ?></span>
                        </div>

                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <span class="text-slate-500">Retailer delivery</span>
                            <span class="font-semibold text-slate-900">£<?php echo e(number_format($order->retailer_delivery_fee_total ?? 0, 2)); ?></span>
                        </div>

                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <span class="text-slate-500">Dabba service fee</span>
                            <span class="font-semibold text-slate-900">£<?php echo e(number_format($order->dabba_fee_amount ?? 0, 2)); ?></span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-slate-700">Order total</span>
                            <span class="font-bold text-slate-900">£<?php echo e(number_format($order->grand_total ?? 0, 2)); ?></span>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                    <h2 class="text-lg font-bold text-slate-900">
                        Wallet used on this order
                    </h2>

                    <div class="mt-5 space-y-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $walletApplications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $application): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-800">
                                            <?php echo e(str_replace('_', ' ', ucfirst($application->source_type))); ?>

                                        </p>
                                        <p class="mt-1 text-xs text-slate-400">
                                            <?php echo e($application->applied_at ? \Carbon\Carbon::parse($application->applied_at)->format('d M Y') : 'No applied date'); ?>

                                        </p>
                                    </div>

                                    <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                                        <?php echo e($application->credit_status); ?>

                                    </span>
                                </div>

                                <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <p class="text-slate-400">Used here</p>
                                        <p class="font-bold text-indigo-600">
                                            <?php echo e($application->currency ?? 'GBP'); ?> <?php echo e(number_format($application->amount_applied ?? 0, 2)); ?>

                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-slate-400">Wallet left</p>
                                        <p class="font-semibold text-slate-800">
                                            <?php echo e($application->currency ?? 'GBP'); ?> <?php echo e(number_format($application->remaining_amount ?? 0, 2)); ?>

                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                                No wallet balance was used on this order.
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
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
<?php endif; ?><?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/money-desk/order-show.blade.php ENDPATH**/ ?>