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
        Orders
     <?php $__env->endSlot(); ?>

    <?php
        $orderType = $order->order_type ?? $order->purchase_mode ?? 'standard';
        $isCustomerSelfPurchase = $orderType === 'customer_self_purchase';
        $customerRequestNotes = collect($notes ?? [])->filter(function ($note) {
            return ($note->type ?? '') === 'order_request_note' || ($note->title ?? '') === 'Customer order request notes';
        })->values();
    ?>

    <div class="space-y-6">

        <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <a href="<?php echo e(route('orders.index')); ?>" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                        ← Back to Orders
                    </a>

                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-bold text-slate-950">
                            Order #<?php echo e($order->order_number); ?>

                        </h1>

                        <span class="rounded-full <?php echo e($isCustomerSelfPurchase ? 'bg-sky-100 text-sky-700' : 'bg-indigo-100 text-indigo-700'); ?> px-3 py-1 text-sm font-black">
                            <?php echo e($isCustomerSelfPurchase ? 'Customer self-purchase' : 'Dabba purchase'); ?>

                        </span>

                        <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">
                            <?php echo e(str_replace('_', ' ', ucfirst($order->status))); ?>

                        </span>

                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-black text-indigo-700">
                            Rev <?php echo e($order->revision_number ?? 1); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($order->revision_total ?? 1) > 1): ?> of <?php echo e($order->revision_total); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </span>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($order->revision_state ?? 'current') === 'superseded'): ?>
                            <span class="rounded-full bg-rose-100 px-3 py-1 text-sm font-black text-rose-700">Superseded</span>
                        <?php elseif(($order->revision_state ?? 'current') === 'current_revision'): ?>
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-black text-emerald-700">Current revision</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $isCustomerSelfPurchase && (($progress['remaining_purchase_qty'] ?? 0) > 0)): ?>
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-700">
                                <?php echo e($progress['remaining_purchase_qty']); ?> still to purchase
                            </span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <p class="mt-2 text-sm text-slate-500">
                        Placed <?php echo e($order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') : 'date unknown'); ?>

                        · Order ID <?php echo e($order->id); ?>

                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a
                        href="<?php echo e(route('draft-orders.show', $order->draft_order_id)); ?>"
                        class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                    >
                        Open Draft ↗
                    </a>

                    <a
                        href="<?php echo e(route('money-desk.orders.show', $order->id)); ?>"
                        class="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-700"
                    >
                        Finance ↗
                    </a>

                    <a
                        href="<?php echo e(route('money-desk.customers.show', $order->customer_id)); ?>"
                        class="rounded-2xl bg-indigo-100 px-4 py-3 text-sm font-semibold text-indigo-700 hover:bg-indigo-200"
                    >
                        Customer Finance ↗
                    </a>
                </div>
            </div>
        </div>


        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isCustomerSelfPurchase): ?>
            <div class="rounded-3xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-sky-700">Customer self-purchase</p>
                        <h2 class="mt-1 text-lg font-black text-sky-950">Customer bought the goods directly from the retailer</h2>
                        <p class="mt-1 text-sm font-semibold leading-6 text-sky-900">Dabba should not purchase these goods. Continue with arrival, customs, collection and delivery workflow when the goods reach Dabba.</p>
                    </div>
                    <a href="<?php echo e(route('money-desk.orders.show', $order->id)); ?>" class="rounded-2xl bg-white px-4 py-2.5 text-sm font-black text-sky-700 ring-1 ring-sky-200 hover:bg-sky-100">Finance ↗</a>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customerRequestNotes->isNotEmpty()): ?>
            <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-700">Customer order request notes</p>
                        <h2 class="mt-1 text-lg font-black text-amber-950">Original customer notes carried through from request</h2>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black uppercase tracking-wide text-amber-700 ring-1 ring-amber-200">Pinned lifecycle note</span>
                </div>

                <div class="mt-4 space-y-3">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $customerRequestNotes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $requestNote): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="rounded-2xl bg-white/80 px-4 py-3 text-sm leading-6 text-amber-950 ring-1 ring-amber-100">
                            <p class="whitespace-pre-line"><?php echo e($requestNote->body); ?></p>
                            <p class="mt-2 text-xs font-semibold text-amber-700">
                                <?php echo e(($requestNote->occurred_at ?: $requestNote->created_at) ? \Carbon\Carbon::parse($requestNote->occurred_at ?: $requestNote->created_at)->format('d M Y H:i') : 'Date unknown'); ?>

                            </p>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <div class="xl:col-span-4 rounded-3xl bg-white p-5 shadow-sm border border-slate-200">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Customer</p>

                        <h2 class="mt-3 text-xl font-bold text-slate-950">
                            <?php echo e($order->bill_to_name ?: trim(($order->first_name ?? '') . ' ' . ($order->last_name ?? '')) ?: 'Unknown customer'); ?>

                        </h2>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->bill_to_company || $order->company_name): ?>
                            <p class="mt-1 text-sm text-slate-500">
                                <?php echo e($order->bill_to_company ?: $order->company_name); ?>

                            </p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <a
                        href="<?php echo e(route('money-desk.customers.show', $order->customer_id)); ?>"
                        title="Open customer finance"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-slate-600 hover:bg-indigo-100 hover:text-indigo-700"
                    >
                        ↗
                    </a>
                </div>

                <div class="mt-5 space-y-3 text-sm text-slate-600">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->bill_to_email): ?>
                        <p>✉ <?php echo e($order->bill_to_email); ?></p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->bill_to_phone): ?>
                        <p>☎ <?php echo e($order->bill_to_phone); ?></p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->bill_to_address_line1 || $order->bill_to_postcode): ?>
                        <p class="leading-6">
                            <?php echo e($order->bill_to_address_line1); ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->bill_to_postcode): ?>
                                <br><?php echo e($order->bill_to_postcode); ?>

                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <div class="xl:col-span-5 rounded-3xl bg-white p-5 shadow-sm border border-slate-200">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Lifecycle</p>

                <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Requested</p>
                        <p class="mt-1 text-xl font-black text-slate-950"><?php echo e($progress['item_qty'] ?? 0); ?></p>
                    </div>

                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <p class="text-[11px] font-black uppercase tracking-wide text-emerald-600">Purchased</p>
                        <p class="mt-1 text-xl font-black text-emerald-700"><?php echo e($progress['purchased_qty'] ?? 0); ?></p>
                    </div>

                    <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3">
                        <p class="text-[11px] font-black uppercase tracking-wide text-sky-600">Arrived</p>
                        <p class="mt-1 text-xl font-black text-sky-700"><?php echo e($progress['arrived_qty'] ?? 0); ?></p>
                    </div>

                    <div class="rounded-2xl border border-purple-200 bg-purple-50 px-4 py-3">
                        <p class="text-[11px] font-black uppercase tracking-wide text-purple-600">Ready</p>
                        <p class="mt-1 text-xl font-black text-purple-700"><?php echo e($progress['ready_qty'] ?? 0); ?></p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 ring-1 ring-slate-100">
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Completed</p>
                        <p class="mt-1 text-xl font-black text-slate-950"><?php echo e($progress['collected_qty'] ?? 0); ?></p>
                    </div>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $isCustomerSelfPurchase && (($progress['remaining_purchase_qty'] ?? 0) > 0)): ?>
                    <p class="mt-4 rounded-2xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 ring-1 ring-amber-100">
                        <?php echo e($progress['remaining_purchase_qty']); ?> item(s) still need purchasing.
                    </p>
                <?php elseif($isCustomerSelfPurchase): ?>
                    <p class="mt-4 rounded-2xl bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-800 ring-1 ring-sky-100">
                        Customer-purchased goods skip Dabba buying and continue into arrival, customs and collection.
                    </p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div class="xl:col-span-3 rounded-3xl bg-white p-5 shadow-sm border border-slate-200">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Order summary</p>

                <div class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500"><?php echo e($isCustomerSelfPurchase ? 'Goods value (reference)' : 'Items subtotal'); ?></span>
                        <span class="font-semibold text-slate-900">£<?php echo e(number_format($order->subtotal ?? 0, 2)); ?></span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Delivery fees</span>
                        <span class="font-semibold text-slate-900">£<?php echo e(number_format($order->retailer_delivery_fee_total ?? 0, 2)); ?></span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Dabba fees</span>
                        <span class="font-semibold text-slate-900">£<?php echo e(number_format($order->dabba_fee_amount ?? 0, 2)); ?></span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Paid / settled</span>
                        <span class="font-semibold text-emerald-600">£<?php echo e(number_format($finance['settled_total'] ?? 0, 2)); ?></span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Balance due</span>
                        <span class="font-semibold <?php echo e(($finance['balance_due'] ?? 0) > 0 ? 'text-rose-600' : 'text-slate-400'); ?>">
                            £<?php echo e(number_format($finance['balance_due'] ?? 0, 2)); ?>

                        </span>
                    </div>

                    <div class="border-t border-slate-200 pt-4">
                        <div class="flex justify-between gap-4">
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-400"><?php echo e($isCustomerSelfPurchase ? 'Billable total' : 'Total'); ?></span>
                            <span class="text-2xl font-bold text-slate-950">£<?php echo e(number_format($order->grand_total ?? 0, 2)); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <div class="xl:col-span-12 rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Retailers &amp; Items</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Product links, purchase progress and arrival progress are grouped by retailer.
                        </p>
                    </div>

                    <span class="text-sm text-slate-400">
                        <?php echo e($retailerGroups->count()); ?> retailer group(s)
                    </span>
                </div>

                <div class="mt-6 space-y-6">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $retailerGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="overflow-hidden rounded-3xl border border-slate-200">
                            <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="font-bold text-slate-950"><?php echo e($group->name); ?></h3>
                                    <p class="mt-1 text-xs text-slate-500">
                                        <?php echo e($group->item_count); ?> line(s) · Qty <?php echo e($group->total_qty); ?>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($group->host && $group->host !== $group->name): ?>
                                            · <?php echo e($group->host); ?>

                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2 text-xs font-semibold">
                                    <span class="rounded-full bg-white px-3 py-2 text-slate-700 ring-1 ring-slate-200">
                                        Total £<?php echo e(number_format($group->line_total ?? 0, 2)); ?>

                                    </span>

                                    <span class="rounded-full bg-emerald-100 px-3 py-2 text-emerald-700">
                                        Purchased <?php echo e($group->purchased_qty); ?>/<?php echo e($group->total_qty); ?>

                                    </span>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($group->remaining_qty > 0): ?>
                                        <span class="rounded-full bg-rose-100 px-3 py-2 text-rose-700">
                                            Remaining <?php echo e($group->remaining_qty); ?>

                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                    <span class="rounded-full bg-sky-100 px-3 py-2 text-sky-700">
                                        Arrived <?php echo e($group->arrived_qty); ?>/<?php echo e($group->total_qty); ?>

                                    </span>
                                </div>
                            </div>

                            <div class="divide-y divide-slate-100">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $group->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <div class="p-5 <?php echo e($item->requires_inspection ? 'bg-purple-50/60' : 'bg-white'); ?>">
                                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-center">
                                            <div class="lg:col-span-5">
                                                <div class="flex items-start gap-3">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->product_url): ?>
                                                        <a
                                                            href="<?php echo e($item->product_url); ?>"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            title="Open product page"
                                                            class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 text-lg font-bold text-indigo-700 hover:bg-indigo-100"
                                                        >
                                                            ↗
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-300">
                                                            —
                                                        </span>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                                    <div>
                                                        <h4 class="font-bold text-slate-950"><?php echo e($item->item_name); ?></h4>

                                                        <div class="mt-2 flex flex-wrap gap-2">
                                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                                Qty <?php echo e($item->quantity); ?>

                                                            </span>

                                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->product_code): ?>
                                                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                                    <?php echo e($item->product_code); ?>

                                                                </span>
                                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->requires_inspection): ?>
                                                                <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700">
                                                                    Purple check
                                                                </span>
                                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="lg:col-span-2">
                                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Purchase</p>
                                                <p class="mt-1 font-semibold <?php echo e($item->purchase_remaining_qty > 0 ? 'text-rose-600' : 'text-emerald-600'); ?>">
                                                    <?php echo e($item->purchased_qty); ?>/<?php echo e($item->quantity); ?>

                                                </p>
                                                <p class="mt-1 text-xs text-slate-400">
                                                    <?php echo e($isCustomerSelfPurchase ? 'Bought by customer' : ($item->purchase_remaining_qty > 0 ? 'Pending purchase' : 'Purchased')); ?>

                                                </p>
                                            </div>

                                            <div class="lg:col-span-2">
                                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Arrival</p>
                                                <p class="mt-1 font-semibold <?php echo e($item->arrived_qty > 0 ? 'text-sky-600' : 'text-slate-500'); ?>">
                                                    <?php echo e($item->arrived_qty); ?>/<?php echo e($item->quantity); ?>

                                                </p>
                                                <p class="mt-1 text-xs text-slate-400">
                                                    <?php echo e($item->latest_arrival_status ? str_replace('_', ' ', $item->latest_arrival_status) : 'Not arrived'); ?>

                                                </p>
                                            </div>

                                            <div class="lg:col-span-3 lg:text-right">
                                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Price</p>
                                                <p class="mt-1 text-lg font-bold text-slate-950">
                                                    £<?php echo e(number_format($item->line_total ?? 0, 2)); ?>

                                                </p>

                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->latest_retailer_order_reference || $item->retailer_order_reference): ?>
                                                    <p class="mt-1 text-xs text-slate-500">
                                                        Ref: <?php echo e($item->latest_retailer_order_reference ?: $item->retailer_order_reference); ?>

                                                    </p>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </div>
                                        </div>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->inspection_note): ?>
                                            <p class="mt-4 rounded-2xl bg-purple-100 px-4 py-3 text-sm text-purple-800">
                                                <?php echo e($item->inspection_note); ?>

                                            </p>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </div>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                            No items found for this order.
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Arrival / collection events</h2>
                        <p class="mt-1 text-sm text-slate-500">Key lifecycle dates for received goods.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600"><?php echo e($arrivals->count()); ?> event<?php echo e($arrivals->count() === 1 ? '' : 's'); ?></span>
                </div>

                <div class="mt-5 space-y-3">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $arrivals->take(12); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $arrival): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="rounded-2xl border border-slate-200 p-4 <?php echo e($arrival->requires_marking_attention ? 'border-purple-300 bg-purple-50/60' : ''); ?>">
                            <p class="font-semibold text-slate-900"><?php echo e($arrival->item_name); ?></p>
                            <p class="mt-1 text-sm text-slate-500">Qty <?php echo e($arrival->qty); ?> · Current status: <?php echo e(str_replace('_', ' ', $arrival->status)); ?></p>

                            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl bg-sky-50 px-3 py-3 ring-1 ring-sky-100">
                                    <p class="text-[11px] font-black uppercase tracking-wide text-sky-700">Arrived</p>
                                    <p class="mt-1 text-sm font-bold text-slate-900"><?php echo e($arrival->matched_at ? \Carbon\Carbon::parse($arrival->matched_at)->format('d M Y') : 'Not recorded'); ?></p>
                                </div>

                                <div class="rounded-2xl bg-purple-50 px-3 py-3 ring-1 ring-purple-100">
                                    <p class="text-[11px] font-black uppercase tracking-wide text-purple-700">Informed / ready</p>
                                    <p class="mt-1 text-sm font-bold text-slate-900"><?php echo e(! empty($arrival->informed_at) ? \Carbon\Carbon::parse($arrival->informed_at)->format('d M Y') : 'Not recorded'); ?></p>
                                </div>

                                <div class="rounded-2xl bg-emerald-50 px-3 py-3 ring-1 ring-emerald-100">
                                    <p class="text-[11px] font-black uppercase tracking-wide text-emerald-700"><?php echo e($arrival->completion_label ?? 'Collected / delivered'); ?></p>
                                    <p class="mt-1 text-sm font-bold text-slate-900"><?php echo e(! empty($arrival->completed_at) ? \Carbon\Carbon::parse($arrival->completed_at)->format('d M Y') : 'Not recorded'); ?></p>
                                </div>
                            </div>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($arrival->notes): ?>
                                <p class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-600">
                                    <?php echo e($arrival->notes); ?>

                                </p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                            No arrival events yet.
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-950">Notes and activity</h2>

                <div class="mt-5 space-y-3">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $notes->take(8); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $note): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($note->is_pinned): ?>
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">pinned</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                    <?php echo e(str_replace('_', ' ', $note->type)); ?>

                                </span>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($note->title): ?>
                                    <span class="font-semibold text-slate-900"><?php echo e($note->title); ?></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <p class="mt-3 whitespace-pre-line text-sm text-slate-600"><?php echo e($note->body); ?></p>

                            <p class="mt-3 text-xs text-slate-400">
                                <?php echo e(($note->occurred_at ?: $note->created_at) ? \Carbon\Carbon::parse($note->occurred_at ?: $note->created_at)->format('d M Y H:i') : 'No date'); ?>

                            </p>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                            No notes found.
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($revisionHistory ?? collect())->count() > 1): ?>
            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Revision history</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">Order #<?php echo e($order->order_number); ?> has <?php echo e(($revisionHistory ?? collect())->count()); ?> saved revision snapshots</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Audit trail</span>
                </div>

                <div class="mt-5 space-y-3">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $revisionHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $revision): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php
                            $isCurrentRevision = (int) $revision->id === (int) $order->id;
                            $isSupersededRevision = ($revision->revision_state ?? '') === 'superseded';
                        ?>
                        <div class="flex flex-col gap-3 rounded-2xl border <?php echo e($isCurrentRevision ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50'); ?> px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-black text-slate-950">Rev <?php echo e($revision->revision_number); ?> of <?php echo e($revision->revision_total); ?></p>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isCurrentRevision): ?>
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">Viewing now</span>
                                    <?php elseif($isSupersededRevision): ?>
                                        <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-black text-rose-700">Superseded</span>
                                    <?php else: ?>
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Current</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600"><?php echo e(str_replace('_', ' ', $revision->status)); ?></span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    Created <?php echo e($revision->created_at ? \Carbon\Carbon::parse($revision->created_at)->format('d M Y H:i') : 'date unknown'); ?> · Total £<?php echo e(number_format($revision->grand_total ?? 0, 2)); ?>

                                </p>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($revision->revision_note)): ?>
                                    <p class="mt-1 text-xs text-slate-500 line-clamp-2"><?php echo e($revision->revision_note); ?></p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $isCurrentRevision): ?>
                                    <a href="<?php echo e(route('orders.show', $revision->id)); ?>" class="rounded-2xl bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-indigo-50 hover:text-indigo-700">View snapshot</a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($revision->draft_order_id)): ?>
                                    <a href="<?php echo e(route('draft-orders.show', $revision->draft_order_id)); ?>" class="rounded-2xl bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-slate-100">Open Draft</a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </div>
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
<?php endif; ?><?php /**PATH /var/www/dabba-live/dabbadesk-v2/resources/views/orders/show.blade.php ENDPATH**/ ?>