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

     <?php $__env->slot('header', null, []); ?> Order Purchasing <?php $__env->endSlot(); ?>

    <?php
        $money = fn ($amount) => '£' . number_format((float) $amount, 2);
        $orderNumber = $order->order_number ?? $order->id;
        $customer = trim((string) ($order->bill_to_company ?: $order->bill_to_name ?: 'Unknown customer'));
        $paymentStatus = $queueOrder['payment_status'] ?? 'unpaid';
        $paymentLabel = match ($paymentStatus) {
            'paid' => 'Fully Paid',
            'part_paid' => 'Part Paid',
            default => 'Unpaid',
        };
        $paymentClass = match ($paymentStatus) {
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'part_paid' => 'bg-amber-50 text-amber-700 ring-amber-200',
            default => 'bg-rose-50 text-rose-700 ring-rose-200',
        };
        $retailerTotal = max(1, $retailers->count());
        $purchasedRetailers = $retailers->filter(fn ($retailer) => (int) $retailer['remaining_to_buy_qty'] === 0 && (int) $retailer['purchased_qty'] > 0)->count();
    ?>

    <div class="mx-auto max-w-6xl space-y-5">
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
                <div class="mt-4 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h1 class="text-3xl font-black tracking-tight">Order #<?php echo e($orderNumber); ?></h1>
                        <p class="mt-1 text-sm font-semibold text-slate-300"><?php echo e($customer); ?></p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 <?php echo e($paymentClass); ?>"><?php echo e($paymentLabel); ?></span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paymentStatus === 'part_paid'): ?>
                                <span class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-black text-white ring-1 ring-white/10"><?php echo e($money($queueOrder['settled_amount'] ?? 0)); ?> / <?php echo e($money($queueOrder['grand_total'] ?? 0)); ?></span>
                            <?php elseif($paymentStatus === 'unpaid'): ?>
                                <span class="rounded-full bg-rose-500/20 px-3 py-1.5 text-xs font-black text-rose-100 ring-1 ring-rose-300/30">Manual purchase override</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-3 lg:min-w-[520px]">
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-300">Order Value</p>
                            <p class="mt-1 text-xl font-black"><?php echo e($money($queueOrder['grand_total'] ?? $order->grand_total ?? 0)); ?></p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-300">Paid</p>
                            <p class="mt-1 text-xl font-black"><?php echo e($money($queueOrder['settled_amount'] ?? 0)); ?></p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-300">Purchasing</p>
                            <p class="mt-1 text-xl font-black"><?php echo e($purchasedRetailers); ?> / <?php echo e($retailerTotal); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-b border-slate-100 bg-white px-5 py-4 sm:px-7">
                <div class="flex flex-wrap gap-2">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $retailers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $retailer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php
                            $hasProblem = (int) $retailer['problem_qty'] > 0;
                            $isPurchased = (int) $retailer['remaining_to_buy_qty'] === 0 && (int) $retailer['purchased_qty'] > 0;
                            $pillClass = $hasProblem
                                ? 'bg-rose-50 text-rose-700 ring-rose-200'
                                : ($isPurchased ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-50 text-slate-600 ring-slate-200');
                        ?>
                        <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 <?php echo e($pillClass); ?>"><?php echo e($retailer['retailer_name']); ?> <?php echo e($hasProblem ? '!' : ($isPurchased ? '✓' : '○')); ?></span>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $retailers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $retailer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php
                    $retailerItems = collect($retailer['items']);
                    $remainingItems = $retailerItems->filter(fn ($item) => (int) $item->remaining_to_buy_qty > 0)->values();
                    $rootIds = $retailerItems->pluck('lineage_root_id')->filter()->unique()->values();
                    $history = $purchases->filter(fn ($purchase) => $rootIds->contains((int) $purchase->root_item_id))->whereNull('cancelled_at')->values();
                    $refs = $history->pluck('retailer_order_reference')->filter()->unique()->values();
                    $expected = $history->pluck('expected_uk_hub_at')->filter()->sort()->first();
                    $hasProblem = (int) $retailer['problem_qty'] > 0;
                    $isPurchased = (int) $retailer['remaining_to_buy_qty'] === 0 && (int) $retailer['purchased_qty'] > 0;
                    $isReceived = $isPurchased && (int) $retailer['awaiting_arrival_qty'] === 0 && (int) $retailer['arrived_qty'] > 0;
                    $statusLabel = $hasProblem ? 'Problem' : ($isReceived ? 'Received' : ($isPurchased ? 'Purchased' : 'Ready To Purchase'));
                    $cardClass = $hasProblem ? 'border-rose-200' : ($isPurchased ? 'border-emerald-200' : 'border-slate-200');
                ?>

                <article class="overflow-hidden rounded-[1.7rem] border <?php echo e($cardClass); ?> bg-white shadow-sm">
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Retailer</p>
                                <h2 class="mt-1 text-2xl font-black text-slate-950"><?php echo e($retailer['retailer_name']); ?></h2>
                                <p class="mt-1 text-sm font-bold text-slate-500"><?php echo e($retailerItems->count()); ?> item<?php echo e($retailerItems->count() === 1 ? '' : 's'); ?></p>
                            </div>
                            <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 <?php echo e($hasProblem ? 'bg-rose-50 text-rose-700 ring-rose-200' : ($isPurchased ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-indigo-50 text-indigo-700 ring-indigo-200')); ?>"><?php echo e($statusLabel); ?></span>
                        </div>

                        <div class="mt-5 space-y-2">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $retailerItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-black text-slate-900"><?php echo e($item->item_name); ?></p>
                                            <p class="mt-1 text-xs font-semibold text-slate-500">Qty <?php echo e((int) $item->quantity); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->product_code): ?> · SKU <?php echo e($item->product_code); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></p>
                                        </div>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->product_url): ?>
                                            <a href="<?php echo e($item->product_url); ?>" target="_blank" class="shrink-0 rounded-xl bg-white px-2.5 py-1.5 text-xs font-black text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-50">Open</a>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </div>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isPurchased): ?>
                            <div class="mt-5 rounded-2xl bg-emerald-50 p-4 ring-1 ring-emerald-100">
                                <p class="text-[10px] font-black uppercase tracking-wide text-emerald-700">Reference</p>
                                <p class="mt-1 text-sm font-black text-emerald-950"><?php echo e($refs->isNotEmpty() ? $refs->join(', ') : 'No reference saved'); ?></p>
                                <p class="mt-3 text-[10px] font-black uppercase tracking-wide text-emerald-700">Expected Delivery</p>
                                <p class="mt-1 text-sm font-black text-emerald-950"><?php echo e($expected ? \Carbon\Carbon::parse($expected)->format('d M Y') : 'Not set'); ?></p>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <div class="border-t border-slate-100 bg-slate-50/70 p-4">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($remainingItems->isNotEmpty()): ?>
                            <details class="rounded-2xl border border-indigo-100 bg-white shadow-sm" <?php if($loop->first): ?> open <?php endif; ?>>
                                <summary class="cursor-pointer select-none px-4 py-3 text-sm font-black text-indigo-800">Purchase <?php echo e($retailer['retailer_name']); ?></summary>
                                <form method="POST" action="<?php echo e(route('purchasing.purchases.bulk')); ?>" class="space-y-4 border-t border-indigo-100 p-4">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="order_id" value="<?php echo e($order->id); ?>">

                                    <div class="rounded-2xl bg-indigo-50 p-3 ring-1 ring-indigo-100">
                                        <p class="text-xs font-black uppercase tracking-wide text-indigo-700">Items included</p>
                                        <div class="mt-2 space-y-2">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $remainingItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                <div class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-2 ring-1 ring-indigo-100">
                                                    <div class="min-w-0">
                                                        <input type="hidden" name="order_item_ids[]" value="<?php echo e($item->item_id); ?>">
                                                        <p class="truncate text-sm font-black text-slate-900"><?php echo e($item->item_name); ?></p>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-xs font-black text-slate-400">Qty</span>
                                                        <input name="qty[<?php echo e($item->item_id); ?>]" type="number" min="0" max="<?php echo e((int) $item->remaining_to_buy_qty); ?>" value="<?php echo e((int) $item->remaining_to_buy_qty); ?>" class="h-9 w-20 rounded-xl border-indigo-200 text-sm font-black focus:border-indigo-400 focus:ring-indigo-300">
                                                    </div>
                                                </div>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div class="sm:col-span-2">
                                            <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Retailer Reference</label>
                                            <input name="retailer_order_reference" maxlength="255" placeholder="One reference for this retailer purchase" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white px-4 text-sm font-black text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Expected Delivery</label>
                                            <input name="expected_uk_hub_at" type="date" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white px-4 text-sm font-black text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Ordered Date</label>
                                            <input name="ordered_at" type="date" value="<?php echo e(now()->format('Y-m-d')); ?>" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white px-4 text-sm font-black text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Internal Notes</label>
                                            <textarea name="note" rows="2" maxlength="2000" placeholder="Optional" class="mt-1 w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-indigo-300 focus:ring-indigo-200"></textarea>
                                        </div>
                                    </div>

                                    <button class="w-full rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-sm hover:bg-indigo-700">Save Purchase</button>
                                </form>
                            </details>
                        <?php else: ?>
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="text-sm font-black text-slate-700">This retailer is purchased.</p>
                                <details class="relative">
                                    <summary class="cursor-pointer select-none rounded-2xl bg-white px-4 py-2 text-xs font-black text-slate-700 ring-1 ring-slate-200">Actions</summary>
                                    <div class="mt-2 w-52 rounded-2xl border border-slate-200 bg-white p-2 text-xs font-black text-slate-600 shadow-sm">
                                        <a href="<?php echo e(route('orders.show', $order->id)); ?>" class="block rounded-xl px-3 py-2 hover:bg-slate-50">View full order</a>
                                        <span class="block rounded-xl px-3 py-2 text-slate-400">Split purchase later</span>
                                        <span class="block rounded-xl px-3 py-2 text-slate-400">View activity later</span>
                                    </div>
                                </details>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </article>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
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
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/purchasing/show.blade.php ENDPATH**/ ?>