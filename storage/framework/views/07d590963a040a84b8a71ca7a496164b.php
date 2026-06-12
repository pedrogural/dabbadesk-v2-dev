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

     <?php $__env->slot('header', null, []); ?> Draft Orders <?php $__env->endSlot(); ?>

    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Draft Order Workspace</h1>
                    <p class="mt-1 text-sm text-slate-500">Edit converted requests before they become locked operational order snapshots.</p>
                </div>
                <span class="inline-flex w-fit rounded-full bg-indigo-100 px-4 py-2 text-sm font-semibold text-indigo-700">Request → Draft → Order</span>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" action="<?php echo e(route('draft-orders.index')); ?>" class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-end" x-data="{ timer: null, submitSoon() { clearTimeout(this.timer); this.timer = setTimeout(() => this.$refs.form.submit(), 450); } }" x-ref="form">
                <div class="lg:col-span-6">
                    <label for="q" class="text-sm font-semibold text-slate-700">Search drafts</label>
                    <input id="q" name="q" value="<?php echo e($filters['q']); ?>" type="text" placeholder="Request ref, draft id, customer, email, phone, item, SKU or URL" class="mt-2 w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @input="submitSoon()">
                </div>

                <div class="lg:col-span-3">
                    <label for="status" class="text-sm font-semibold text-slate-700">Status</label>
                    <select id="status" name="status" class="mt-2 w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" onchange="this.form.submit()">
                        <option value="">All supported statuses</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <option value="<?php echo e($status); ?>" <?php if($filters['status'] === $status): echo 'selected'; endif; ?>><?php echo e(str_replace('_', ' ', ucfirst($status))); ?></option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>
                </div>

                <div class="lg:col-span-1">
                    <label class="flex min-h-[48px] items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm">
                        <input type="checkbox" name="mine" value="1" <?php if(! empty($filters['mine'])): echo 'checked'; endif; ?> onchange="this.form.submit()" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Mine
                    </label>
                </div>

                <div class="lg:col-span-2 flex gap-2">
                    <button type="submit" class="w-full rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Search</button>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($filters['q'] || $filters['status'] || ! empty($filters['mine'])): ?>
                        <a href="<?php echo e(route('draft-orders.index')); ?>" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-200">Clear</a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </form>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Draft results</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Statuses currently supported: open, reviewing, ready, consumed, cancelled.</p>
                </div>
                <p class="text-sm text-slate-500">Showing <?php echo e($drafts->firstItem() ?? 0); ?>–<?php echo e($drafts->lastItem() ?? 0); ?> of <?php echo e($drafts->total()); ?></p>
            </div>

            <div class="mt-5 space-y-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $drafts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $draft): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php
                        $customerName = trim(($draft->first_name ?? '') . ' ' . ($draft->last_name ?? '')) ?: ($draft->company_name ?: 'Unknown customer');
                        $primaryRef = $draft->request_ref ?: ($draft->draft_number ?: $draft->id);
                    ?>
                    <div class="rounded-3xl border border-slate-200 p-5 hover:border-indigo-200 hover:bg-indigo-50/30">
                        <div class="grid grid-cols-1 gap-4 xl:grid-cols-12 xl:items-center">
                            <div class="xl:col-span-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-lg font-black text-slate-950">Request #<?php echo e($primaryRef); ?></h3>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600"><?php echo e(str_replace('_', ' ', $draft->status)); ?></span>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($draft->finalized_order_id): ?>
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">linked order</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <p class="mt-1 text-sm font-black text-slate-700">
                                    <a href="<?php echo e(route('customers.show', $draft->customer_id)); ?>" class="hover:text-indigo-700 hover:underline"><?php echo e($customerName); ?></a>
                                </p>
                                <p class="mt-1 text-xs text-slate-400">Draft ID <?php echo e($draft->id); ?><?php echo e($draft->draft_number && $draft->draft_number !== (string) $primaryRef && $draft->draft_number !== (string) $draft->id ? ' · Legacy ref '.$draft->draft_number : ''); ?> · Updated <?php echo e($draft->updated_at ? \Carbon\Carbon::parse($draft->updated_at)->format('d M Y H:i') : 'unknown'); ?></p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">
                                    Created by <?php echo e($draft->created_by_name ?: 'Unknown user'); ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($draft->updated_by_name ?? null) && $draft->updated_by_name !== $draft->created_by_name): ?>
                                        · Updated by <?php echo e($draft->updated_by_name); ?>

                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </p>
                            </div>

                            <div class="xl:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Items</p>
                                <p class="mt-1 text-sm font-semibold text-slate-800"><?php echo e($draft->item_count); ?> lines / <?php echo e($draft->total_qty); ?> qty</p>
                            </div>

                            <div class="xl:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Subtotal</p>
                                <p class="mt-1 text-sm font-semibold text-slate-800">£<?php echo e(number_format($draft->items_subtotal ?? 0, 2)); ?></p>
                            </div>

                            <div class="xl:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Draft total</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">£<?php echo e(number_format($draft->grand_total ?? 0, 2)); ?></p>
                            </div>

                            <div class="xl:col-span-2 flex justify-start xl:justify-end">
                                <a href="<?php echo e(route('draft-orders.show', $draft->id)); ?>" class="rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Open Draft</a>
                            </div>
                        </div>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">No draft orders found.</div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div class="mt-6"><?php echo e($drafts->links()); ?></div>
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
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/draft-orders/index.blade.php ENDPATH**/ ?>