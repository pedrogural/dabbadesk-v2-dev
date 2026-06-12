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

     <?php $__env->slot('header', null, []); ?> Order Purchasing Workspace <?php $__env->endSlot(); ?>

    <?php
        $money = fn ($amount) => '£' . number_format((float) $amount, 2);
        $orderNumber = $order->order_number ?? $order->id;
        $customer = trim((string) ($order->bill_to_company ?: $order->bill_to_name ?: 'Unknown customer'));
        $paymentStatus = $queueOrder['payment_status'] ?? 'unpaid';
        $paymentLabel = match ($paymentStatus) {
            'paid' => 'Paid',
            'part_paid' => 'Part paid',
            default => 'Unpaid',
        };
        $paymentClass = match ($paymentStatus) {
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'part_paid' => 'bg-amber-50 text-amber-700 ring-amber-200',
            default => 'bg-rose-50 text-rose-700 ring-rose-200',
        };
        $requestedQty = (int) ($queueOrder['requested_qty'] ?? $items->sum('quantity'));
        $purchasedQty = (int) ($queueOrder['purchased_qty'] ?? $items->sum('purchased_qty'));
        $remainingQty = (int) ($queueOrder['remaining_to_buy_qty'] ?? $items->sum('remaining_to_buy_qty'));
        $awaitingQty = (int) ($queueOrder['awaiting_arrival_qty'] ?? $items->sum('awaiting_arrival_qty'));
        $problemQty = (int) ($queueOrder['problem_qty'] ?? $items->sum('problem_qty'));
    ?>

    <div class="mx-auto max-w-7xl space-y-5">
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

        <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
            <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-5 py-6 text-white sm:px-7">
                <a href="<?php echo e(route('purchasing.index')); ?>" class="text-xs font-black uppercase tracking-[0.22em] text-indigo-200 hover:text-white">← Back to Purchase Queue</a>

                <div class="mt-4 flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-3xl font-black tracking-tight">Purchase Items for Order #<?php echo e($orderNumber); ?></h1>
                            <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 <?php echo e($paymentClass); ?>"><?php echo e($paymentLabel); ?></span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($order->purchase_mode ?? '') === 'customer_self_purchase'): ?>
                                <span class="rounded-full bg-sky-50 px-3 py-1.5 text-xs font-black text-sky-700 ring-1 ring-sky-200">Customer self-purchase</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <p class="mt-2 text-sm font-semibold text-slate-300">Order-specific purchasing screen · <?php echo e($customer); ?></p>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->bill_to_email): ?>
                            <p class="mt-1 text-xs font-bold text-slate-400"><?php echo e($order->bill_to_email); ?></p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5 xl:min-w-[720px]">
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-300">Order total</p>
                            <p class="mt-1 text-lg font-black"><?php echo e($money($order->grand_total ?? 0)); ?></p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-300">Requested</p>
                            <p class="mt-1 text-lg font-black"><?php echo e($requestedQty); ?></p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-300">Purchased</p>
                            <p class="mt-1 text-lg font-black"><?php echo e($purchasedQty); ?></p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-300">To buy</p>
                            <p class="mt-1 text-lg font-black"><?php echo e($remainingQty); ?></p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-300">Awaiting</p>
                            <p class="mt-1 text-lg font-black"><?php echo e($awaitingQty); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-white/10 bg-slate-50 px-5 py-4 sm:px-7">
                <div class="flex flex-wrap items-center gap-2 text-xs font-black">
                    <span class="rounded-full bg-white px-3 py-1.5 text-slate-700 ring-1 ring-slate-200">Retailer cards: <?php echo e($retailers->count()); ?></span>
                    <span class="rounded-full bg-white px-3 py-1.5 text-slate-700 ring-1 ring-slate-200">Problem qty: <?php echo e($problemQty); ?></span>
                    <a href="<?php echo e(route('orders.show', $order->id)); ?>" class="rounded-full bg-white px-3 py-1.5 text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-50">View full order ↗</a>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $retailers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $retailer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php
                    $retailerItems = collect($retailer['items']);
                    $remainingItems = $retailerItems->filter(fn ($item) => (int) $item->remaining_to_buy_qty > 0)->values();
                    $waitingQty = (int) $remainingItems->sum('remaining_to_buy_qty');
                    $purchasedQtyForRetailer = (int) $retailerItems->sum('purchased_qty');
                    $awaitingQtyForRetailer = (int) $retailerItems->sum('awaiting_arrival_qty');
                    $problemQtyForRetailer = (int) $retailerItems->sum('problem_qty');
                    $waitingValue = $remainingItems->sum(fn ($item) => ((float) $item->unit_price) * ((int) $item->remaining_to_buy_qty));
                    $isComplete = $waitingQty === 0 && $purchasedQtyForRetailer > 0;
                    $cardRing = $problemQtyForRetailer > 0 ? 'border-rose-200' : ($waitingQty > 0 ? 'border-indigo-200' : 'border-emerald-200');
                    $statusLabel = $problemQtyForRetailer > 0 ? 'Needs attention' : ($waitingQty > 0 ? 'Ready to buy' : 'Purchased');
                    $statusClass = $problemQtyForRetailer > 0 ? 'bg-rose-50 text-rose-700 ring-rose-200' : ($waitingQty > 0 ? 'bg-indigo-50 text-indigo-700 ring-indigo-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200');
                ?>

                <article class="overflow-hidden rounded-[1.75rem] border <?php echo e($cardRing); ?> bg-white shadow-sm" data-retailer-card>
                    <div class="border-b border-slate-100 p-5 sm:p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-2xl font-black text-slate-950"><?php echo e($retailer['retailer_name']); ?></h2>
                                    <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span>
                                </div>
                                <p class="mt-2 text-sm font-bold text-slate-500">
                                    <?php echo e($remainingItems->count()); ?> item line<?php echo e($remainingItems->count() === 1 ? '' : 's'); ?> waiting · <?php echo e($waitingQty); ?> qty to buy
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-4 lg:min-w-[560px]">
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Waiting value</p>
                                    <p class="mt-1 font-black text-slate-950"><?php echo e($money($waitingValue)); ?></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Purchased qty</p>
                                    <p class="mt-1 font-black text-slate-950"><?php echo e($purchasedQtyForRetailer); ?></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Awaiting qty</p>
                                    <p class="mt-1 font-black text-slate-950"><?php echo e($awaitingQtyForRetailer); ?></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Problems</p>
                                    <p class="mt-1 font-black text-slate-950"><?php echo e($problemQtyForRetailer); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="<?php echo e(route('purchasing.purchases.bulk')); ?>" class="p-5 sm:p-6" data-purchase-form>
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="order_id" value="<?php echo e($order->id); ?>">

                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="hidden grid-cols-[48px_1fr_120px_120px_130px_70px] gap-3 bg-slate-50 px-4 py-3 text-[10px] font-black uppercase tracking-wide text-slate-400 md:grid">
                                <div>Buy</div>
                                <div>Item</div>
                                <div>Requested</div>
                                <div>Remaining</div>
                                <div>Purchase price</div>
                                <div>Link</div>
                            </div>

                            <div class="divide-y divide-slate-100">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $retailerItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <?php
                                        $remaining = (int) $item->remaining_to_buy_qty;
                                        $canBuy = $remaining > 0;
                                        $rootEvents = collect($purchasesByRoot[$item->lineage_root_id] ?? []);
                                    ?>

                                    <div class="grid gap-3 px-4 py-4 md:grid-cols-[48px_1fr_120px_120px_130px_70px] md:items-center <?php echo e($canBuy ? 'bg-white' : 'bg-slate-50/70'); ?>">
                                        <div>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canBuy): ?>
                                                <input type="checkbox" name="order_item_ids[]" value="<?php echo e($item->item_id); ?>" data-line-checkbox class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                            <?php else: ?>
                                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-50 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">✓</span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>

                                        <div class="min-w-0">
                                            <p class="font-black text-slate-950"><?php echo e($item->item_name); ?></p>
                                            <div class="mt-1 flex flex-wrap gap-2 text-xs font-semibold text-slate-500">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->product_code): ?>
                                                    <span>SKU <?php echo e($item->product_code); ?></span>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->marketplace_seller): ?>
                                                    <span>Seller <?php echo e($item->marketplace_seller); ?></span>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                <span>Customer price <?php echo e($money($item->unit_price)); ?></span>
                                            </div>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rootEvents->isNotEmpty()): ?>
                                                <details class="mt-2">
                                                    <summary class="cursor-pointer select-none text-xs font-black text-indigo-700"><?php echo e($rootEvents->count()); ?> purchase event<?php echo e($rootEvents->count() === 1 ? '' : 's'); ?></summary>
                                                    <div class="mt-2 space-y-1 rounded-xl bg-slate-50 p-2 text-xs font-semibold text-slate-600 ring-1 ring-slate-100">
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $rootEvents->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                            <div class="flex flex-wrap justify-between gap-2">
                                                                <span>Qty <?php echo e((int) $event->qty); ?> · <?php echo e(ucfirst(str_replace('_', ' ', $event->status))); ?></span>
                                                                <span><?php echo e($event->retailer_order_reference ?: 'No ref'); ?></span>
                                                            </div>
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                                    </div>
                                                </details>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>

                                        <div class="flex items-center justify-between gap-2 md:block">
                                            <span class="text-xs font-black uppercase tracking-wide text-slate-400 md:hidden">Requested</span>
                                            <span class="font-black text-slate-900"><?php echo e((int) $item->quantity); ?></span>
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-400 md:hidden">Buying now</label>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canBuy): ?>
                                                <input name="qty[<?php echo e($item->item_id); ?>]" type="number" min="0" max="<?php echo e($remaining); ?>" value="<?php echo e($remaining); ?>" data-line-qty class="h-11 w-full rounded-2xl border-slate-200 bg-white px-3 text-sm font-black text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                                <p class="mt-1 text-[11px] font-bold text-slate-400"><?php echo e($remaining); ?> left</p>
                                            <?php else: ?>
                                                <p class="font-black text-emerald-700">0 left</p>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-400 md:hidden">Purchase price</label>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canBuy): ?>
                                                <input name="purchase_unit_price[<?php echo e($item->item_id); ?>]" type="number" min="0" step="0.01" value="<?php echo e(number_format((float) $item->unit_price, 2, '.', '')); ?>" class="h-11 w-full rounded-2xl border-slate-200 bg-white px-3 text-sm font-black text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                            <?php else: ?>
                                                <span class="text-sm font-bold text-slate-400">—</span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>

                                        <div>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->product_url): ?>
                                                <a href="<?php echo e($item->product_url); ?>" target="_blank" class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-sm font-black text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-100" title="Open product link">↗</a>
                                            <?php else: ?>
                                                <span class="text-sm font-bold text-slate-300">—</span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                    </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </div>
                        </div>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($remainingItems->isNotEmpty()): ?>
                            <div class="mt-4 rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <p class="text-sm font-black text-indigo-950"><span data-selected-lines>0</span> selected item lines</p>
                                        <p class="mt-1 text-xs font-bold text-indigo-700">No items are selected by default. Tick only the items you are buying now. Reduce quantity where the retailer has less stock.</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" data-select-all class="rounded-xl bg-white px-3 py-2 text-xs font-black text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-50">Select all</button>
                                        <button type="button" data-select-none class="rounded-xl bg-white px-3 py-2 text-xs font-black text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">Clear selection</button>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-3 lg:grid-cols-4">
                                    <div class="lg:col-span-2">
                                        <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Retailer order reference</label>
                                        <input name="retailer_order_reference" maxlength="255" required placeholder="e.g. 123-1234567-1234567" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white px-4 text-sm font-black text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">ETA / expected UK hub</label>
                                        <input name="expected_uk_hub_at" type="date" required class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white px-4 text-sm font-black text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Ordered date</label>
                                        <input name="ordered_at" type="date" value="<?php echo e(now()->format('Y-m-d')); ?>" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white px-4 text-sm font-black text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                    </div>
                                    <div class="lg:col-span-4">
                                        <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Internal note</label>
                                        <textarea name="note" rows="2" maxlength="2000" placeholder="Optional note for this purchase batch" class="mt-1 w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-indigo-300 focus:ring-indigo-200"></textarea>
                                    </div>
                                </div>

                                <div class="mt-4 flex justify-end">
                                    <button class="rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-black text-white shadow-sm hover:bg-indigo-700">Save selected purchase</button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-sm font-black text-emerald-800">
                                Nothing left to buy for this retailer. Purchased items stay visible here for context.
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </form>
                </article>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                    <p class="text-lg font-black text-slate-900">No purchasable items found for this order.</p>
                    <p class="mt-2 text-sm font-semibold text-slate-500">This may be completed, cancelled, superseded, or customer self-purchase.</p>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </section>
    </div>

    <script>
        document.querySelectorAll('[data-purchase-form]').forEach((form) => {
            const checkboxes = Array.from(form.querySelectorAll('[data-line-checkbox]'));
            const selected = form.querySelector('[data-selected-lines]');
            const update = () => {
                if (! selected) return;
                selected.textContent = checkboxes.filter((checkbox) => checkbox.checked).length;
            };

            form.querySelector('[data-select-all]')?.addEventListener('click', () => {
                checkboxes.forEach((checkbox) => checkbox.checked = true);
                update();
            });

            form.querySelector('[data-select-none]')?.addEventListener('click', () => {
                checkboxes.forEach((checkbox) => checkbox.checked = false);
                update();
            });

            checkboxes.forEach((checkbox) => checkbox.addEventListener('change', update));

            form.addEventListener('submit', (event) => {
                const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
                const reference = form.querySelector('[name="retailer_order_reference"]');
                const eta = form.querySelector('[name="expected_uk_hub_at"]');

                if (selectedCount === 0) {
                    event.preventDefault();
                    alert('Please select at least one item to purchase.');
                    return;
                }

                if (! reference?.value.trim()) {
                    event.preventDefault();
                    reference?.focus();
                    alert('Retailer order reference is required before saving a purchase.');
                    return;
                }

                if (! eta?.value) {
                    event.preventDefault();
                    eta?.focus();
                    alert('ETA / expected UK hub date is required before saving a purchase.');
                    return;
                }
            });

            update();
        });
    </script>
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