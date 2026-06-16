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
        $activeTab = $filters['tab'] ?? 'to_buy';
        $purchaseRows = collect($purchaseRows ?? []);
        $problemCodes = [
            'out_of_stock' => 'Out of Stock',
            'price_increased' => 'Price Increased',
            'discontinued' => 'Discontinued',
            'retailer_restriction' => 'Retailer Restriction',
            'supplier_cancelled' => 'Supplier Cancelled',
            'wrong_listing' => 'Wrong Listing',
            'unavailable' => 'Unavailable',
            'lost' => 'Lost',
            'damaged' => 'Damaged',
            'wrong_item' => 'Wrong Item',
            'retailer_refunded' => 'Retailer Refunded',
            'other' => 'Other',
        ];
        $paymentBadge = [
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'part_paid' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'unpaid' => 'bg-rose-50 text-rose-700 ring-rose-200',
        ];
        $paymentLabel = ['paid' => 'Paid', 'part_paid' => 'Part paid', 'unpaid' => 'Unpaid'];
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

        <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-950">Purchasing Desk</h1>
                <p class="mt-1 text-sm font-semibold text-slate-500">Find what needs buying, review purchases, and deal with purchasing problems.</p>
            </div>

            <div class="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tabKey => $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <a href="<?php echo e(route('purchasing.index', ['tab' => $tabKey, 'payment' => $filters['payment'], 'q' => $filters['q'], 'mine' => ($filters['mine'] ?? false) ? 1 : null])); ?>" class="rounded-2xl px-4 py-2 text-sm font-black ring-1 transition <?php echo e($activeTab === $tabKey ? 'bg-slate-950 text-white ring-slate-950' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50'); ?>">
                        <?php echo e($tab['label']); ?> <span class="opacity-70"><?php echo e($tab['count']); ?></span>
                    </a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>

            <form method="GET" action="<?php echo e(route('purchasing.index')); ?>" class="mt-4">
                <input type="hidden" name="tab" value="<?php echo e($activeTab); ?>">
                <input type="hidden" name="payment" value="<?php echo e($filters['payment']); ?>">
                <div class="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <label for="purchase-search" class="sr-only">Search purchasing</label>
                        <input id="purchase-search" name="q" value="<?php echo e($filters['q']); ?>" placeholder="Search customer, order, retailer ref, item, retailer or email" class="h-12 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 text-sm font-bold text-slate-900 placeholder:text-slate-400 focus:border-indigo-300 focus:bg-white focus:ring-indigo-200">
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="inline-flex h-12 items-center gap-2 rounded-2xl bg-white px-4 text-sm font-black text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">
                            <input type="checkbox" name="mine" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" <?php echo e(($filters['mine'] ?? false) ? 'checked' : ''); ?>>
                            Mine only
                        </label>
                        <a href="<?php echo e(route('purchasing.index', ['tab' => $activeTab, 'payment' => 'paid_or_part', 'q' => $filters['q'], 'mine' => ($filters['mine'] ?? false) ? 1 : null])); ?>" class="rounded-2xl px-4 py-3 text-sm font-black ring-1 transition <?php echo e($filters['payment'] === 'paid_or_part' ? 'bg-indigo-600 text-white ring-indigo-600' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50'); ?>">Paid & Part Paid</a>
                        <a href="<?php echo e(route('purchasing.index', ['tab' => $activeTab, 'payment' => 'all', 'q' => $filters['q'], 'mine' => ($filters['mine'] ?? false) ? 1 : null])); ?>" class="rounded-2xl px-4 py-3 text-sm font-black ring-1 transition <?php echo e($filters['payment'] === 'all' ? 'bg-rose-600 text-white ring-rose-600' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50'); ?>">All Orders</a>
                        <button class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white hover:bg-indigo-700">Search</button>
                    </div>
                </div>
            </form>
        </section>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeTab === 'purchases'): ?>
            <section class="space-y-3">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $purchaseRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $purchase): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php
                        $customer = trim((string) ($purchase->bill_to_company ?: $purchase->bill_to_name ?: 'Unknown customer'));
                        $canEdit = (bool) ($purchase->can_edit ?? false);
                    ?>
                    <article class="rounded-[1.5rem] border border-emerald-100 bg-white p-4 shadow-sm sm:p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">Purchased</span>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $canEdit): ?>
                                        <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-black text-amber-700 ring-1 ring-amber-200">Locked after arrival</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <h2 class="mt-3 text-lg font-black text-slate-950">Order #<?php echo e($purchase->order_number); ?> · <?php echo e($customer); ?></h2>
                                <p class="mt-1 text-sm font-bold text-slate-700"><?php echo e($purchase->retailer_name); ?> · <?php echo e($purchase->item_name); ?></p>
                                <p class="mt-1 break-words text-xs font-semibold text-slate-400"><?php echo e($purchase->bill_to_email); ?></p>
                            </div>

                            <div class="grid gap-2 text-sm sm:grid-cols-4 lg:min-w-[620px]">
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Qty</p><p class="mt-1 font-black text-slate-950"><?php echo e((int) $purchase->qty); ?></p></div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Ref</p><p class="mt-1 truncate font-black text-slate-950"><?php echo e($purchase->retailer_order_reference ?: '—'); ?></p></div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">ETA</p><p class="mt-1 font-black text-slate-950"><?php echo e($purchase->expected_uk_hub_at ? \Carbon\Carbon::parse($purchase->expected_uk_hub_at)->format('d M Y') : '—'); ?></p></div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Tracking</p><p class="mt-1 font-black text-slate-950">—</p></div>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs font-bold text-slate-500">
                                Recorded by <?php echo e($purchase->recorded_by_name ?: 'Unknown user'); ?> · <?php echo e($purchase->created_at ? \Carbon\Carbon::parse($purchase->created_at)->format('d M Y H:i') : '—'); ?>

                            </p>
                            <div class="flex flex-wrap gap-2">
                                <a href="<?php echo e(route('purchasing.orders.show', ['order' => $purchase->order_id, 'tab' => 'buy'])); ?>" class="rounded-2xl bg-slate-950 px-4 py-2 text-center text-sm font-black text-white hover:bg-indigo-700">Edit / View Purchase</a>
                                <a href="<?php echo e(route('orders.show', $purchase->order_id)); ?>" class="rounded-2xl bg-white px-4 py-2 text-center text-sm font-black text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">Order ↗</a>
                            </div>
                        </div>
                    </article>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                        <p class="text-lg font-black text-slate-900">No purchased items found.</p>
                        <p class="mt-2 text-sm font-semibold text-slate-500">Try a different search or payment filter.</p>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </section>
        <?php else: ?>
            <section class="space-y-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php
                        $payClass = $paymentBadge[$order['payment_status']] ?? $paymentBadge['unpaid'];
                        $payText = $paymentLabel[$order['payment_status']] ?? ucfirst((string) $order['payment_status']);
                        $primaryAction = $activeTab === 'problems' ? 'View Problems' : 'Purchase Items';
                    ?>
                    <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-xl font-black text-slate-950">#<?php echo e($order['order_number']); ?></h2>
                                    <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 <?php echo e($payClass); ?>"><?php echo e($payText); ?></span>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int) $order['inspection_count'] > 0): ?>
                                        <span class="rounded-full bg-purple-50 px-3 py-1.5 text-xs font-black text-purple-700 ring-1 ring-purple-200">Package check</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <p class="mt-2 text-sm font-bold text-slate-700"><?php echo e($order['customer']); ?></p>
                                <p class="mt-1 text-xs font-semibold text-slate-400"><?php echo e($order['email']); ?></p>
                            </div>

                            <div class="grid gap-2 text-sm sm:grid-cols-3 lg:min-w-[460px]">
                                <div class="rounded-2xl bg-indigo-50 p-3 ring-1 ring-indigo-100"><p class="text-[10px] font-black uppercase tracking-wide text-indigo-500">To purchase</p><p class="mt-1 font-black text-indigo-950"><?php echo e($order['remaining_to_buy_qty']); ?></p></div>
                                <div class="rounded-2xl bg-emerald-50 p-3 ring-1 ring-emerald-100"><p class="text-[10px] font-black uppercase tracking-wide text-emerald-600">Purchased</p><p class="mt-1 font-black text-emerald-950"><?php echo e($order['purchased_qty']); ?></p></div>
                                <div class="rounded-2xl bg-rose-50 p-3 ring-1 ring-rose-100"><p class="text-[10px] font-black uppercase tracking-wide text-rose-500">Problems</p><p class="mt-1 font-black text-rose-950"><?php echo e($order['problem_qty']); ?></p></div>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs font-bold text-slate-500">Retailers: <?php echo e($order['retailer_count']); ?> · Order total <?php echo e($money($order['grand_total'])); ?></p>
                            <div class="flex flex-wrap gap-2">
                                <a href="<?php echo e(route('purchasing.orders.show', ['order' => $order['order_id'], 'tab' => $activeTab === 'problems' ? 'problems' : 'buy'])); ?>" class="rounded-2xl bg-slate-950 px-4 py-2 text-center text-sm font-black text-white hover:bg-indigo-700"><?php echo e($primaryAction); ?></a>
                                <a href="<?php echo e(route('orders.show', $order['order_id'])); ?>" class="rounded-2xl bg-white px-4 py-2 text-center text-sm font-black text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">Order ↗</a>
                            </div>
                        </div>
                    </article>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                        <p class="text-lg font-black text-slate-900">Nothing found here.</p>
                        <p class="mt-2 text-sm font-semibold text-slate-500">Try another tab, All Orders, or adjust your search.</p>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/purchasing/index.blade.php ENDPATH**/ ?>