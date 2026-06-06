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
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Order Requests</h2>
                <p class="mt-1 text-sm text-gray-500">New public order requests waiting for review and conversion.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="<?php echo e(route('order-requests.create-manual')); ?>" class="rounded-2xl bg-purple-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-purple-700">+ New Request</a>
                <span class="rounded-full bg-indigo-100 px-4 py-2 text-sm font-bold text-indigo-700"><?php echo e($newRequestCount); ?>

                    new</span>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="space-y-6">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
            <div
                class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <form
            id="order-request-filters"
            method="GET"
            action="<?php echo e(route('order-requests.index')); ?>"
            class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
        >
            <div class="grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
                <div>
                    <label
                        for="q"
                        class="text-xs font-bold uppercase tracking-wide text-gray-500"
                    >Search</label>
                    <input
                        id="q"
                        name="q"
                        value="<?php echo e($search); ?>"
                        placeholder="Ref, customer, email or phone"
                        autocomplete="off"
                        class="mt-2 w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    <p class="mt-1 text-xs text-gray-400">Search updates automatically after a short pause.</p>
                </div>

                <div>
                    <label
                        for="status"
                        class="text-xs font-bold uppercase tracking-wide text-gray-500"
                    >Status</label>
                    <select
                        id="status"
                        name="status"
                        class="mt-2 rounded-2xl border border-gray-200 px-4 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option
                            value="open"
                            <?php if($status === 'open'): echo 'selected'; endif; ?>
                        >Needs Action</option>
                        <option
                            value="all"
                            <?php if($status === 'all'): echo 'selected'; endif; ?>
                        >All</option>
                        <option
                            value="received"
                            <?php if($status === 'received'): echo 'selected'; endif; ?>
                        >Received</option>
                        <option
                            value="reviewing"
                            <?php if($status === 'reviewing'): echo 'selected'; endif; ?>
                        >Reviewing</option>
                        <option
                            value="converted"
                            <?php if($status === 'converted'): echo 'selected'; endif; ?>
                        >Converted</option>
                        <option
                            value="cancelled"
                            <?php if($status === 'cancelled'): echo 'selected'; endif; ?>
                        >Cancelled</option>
                    </select>
                </div>
            </div>

            <noscript>
                <div class="mt-4">
                    <button
                        type="submit"
                        class="rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-black text-white shadow-sm hover:bg-indigo-700"
                    >
                        Filter
                    </button>
                </div>
            </noscript>
        </form>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Ref
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Source</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Order type</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                                Customer</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                                Email</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                                Notes</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                                Status</th>
                            <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-gray-500">
                                Estimate</th>
                            <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-gray-500">
                                Received</th>
                            <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-gray-500">
                                Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $request): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <?php
                                $name = trim(
                                    ($request->customer_first_name ?? '') . ' ' . ($request->customer_last_name ?? ''),
                                );
                                if ($name === '') {
                                    $name = $request->customer_company_name ?: 'Unknown customer';
                                }
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-black text-indigo-700">
                                    <?php echo e($request->request_ref); ?></td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <?php
                                        $sourceLabel = match ((string) ($request->source ?? '')) {
                                            'manual_office', 'office' => 'Office',
                                            'manual_email', 'email' => 'Email',
                                            'manual_whatsapp', 'whatsapp' => 'WhatsApp',
                                            'manual_phone', 'phone' => 'Phone',
                                            'manual_other', 'other' => 'Other',
                                            default => 'Public',
                                        };
                                        $sourceClass = match ($sourceLabel) {
                                            'Office' => 'bg-sky-100 text-sky-800',
                                            'Email' => 'bg-indigo-100 text-indigo-800',
                                            'WhatsApp' => 'bg-emerald-100 text-emerald-800',
                                            'Phone' => 'bg-amber-100 text-amber-800',
                                            'Other' => 'bg-slate-100 text-slate-700',
                                            default => 'bg-purple-100 text-purple-800',
                                        };
                                    ?>
                                    <span class="<?php echo e($sourceClass); ?> rounded-full px-3 py-1 text-xs font-black"><?php echo e($sourceLabel); ?></span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($request->purchase_mode ?? 'standard') === 'customer_self_purchase'): ?>
                                        <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-black text-sky-800">Self-purchase</span>
                                    <?php else: ?>
                                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700">Dabba purchase</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="px-5 py-4 text-sm font-semibold text-gray-900">
                                    <?php echo e($name); ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($request->customer_company_name): ?>
                                        <div class="text-xs font-normal text-gray-500">
                                            <?php echo e($request->customer_company_name); ?></div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600"><?php echo e($request->customer_email ?: '—'); ?></td>
                                <td class="max-w-xs px-5 py-4 text-sm text-gray-600">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(trim((string) ($request->notes ?? '')) !== ''): ?>
                                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold leading-5 text-amber-900">
                                            <span class="mb-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-amber-700">Customer note</span>
                                            <div class="line-clamp-2"><?php echo e(\Illuminate\Support\Str::limit($request->notes, 120)); ?></div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400">—</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="px-5 py-4">
                                    <?php
                                        $statusClass = match ((string) $request->status) {
                                            'converted' => 'bg-emerald-100 text-emerald-800',
                                            'cancelled' => 'bg-rose-100 text-rose-800',
                                            'reviewing' => 'bg-indigo-100 text-indigo-800',
                                            default => $request->converted_at ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800',
                                        };
                                    ?>
                                    <span class="<?php echo e($statusClass); ?> rounded-full px-3 py-1 text-xs font-bold">
                                        <?php echo e(ucfirst($request->status ?: 'received')); ?>

                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold text-gray-900">
                                    £<?php echo e(number_format((float) $request->estimated_total, 2)); ?></td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm text-gray-500">
                                    <?php echo e(\Carbon\Carbon::parse($request->submitted_at ?: $request->created_at)->format('d/m/Y H:i')); ?>

                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <a
                                        href="<?php echo e(route('order-requests.show', $request->id)); ?>"
                                        class="rounded-xl bg-slate-900 px-4 py-2 font-bold text-white hover:bg-slate-700"
                                    >
                                        Open
                                    </a>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <tr>
                                <td
                                    colspan="10"
                                    class="px-5 py-10 text-center text-sm text-gray-500"
                                >No order requests found.</td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div><?php echo e($requests->links()); ?></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('order-request-filters');
            const search = document.getElementById('q');
            const status = document.getElementById('status');

            if (!form || !search || !status) {
                return;
            }

            let filterTimer = null;

            const submitFilters = () => {
                if (filterTimer) {
                    window.clearTimeout(filterTimer);
                }

                form.requestSubmit();
            };

            search.addEventListener('input', () => {
                if (filterTimer) {
                    window.clearTimeout(filterTimer);
                }

                filterTimer = window.setTimeout(() => {
                    form.requestSubmit();
                }, 1200);
            });

            status.addEventListener('change', submitFilters);
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
<?php /**PATH /var/www/dabba-live/dabbadesk-v2/resources/views/order-requests/index.blade.php ENDPATH**/ ?>