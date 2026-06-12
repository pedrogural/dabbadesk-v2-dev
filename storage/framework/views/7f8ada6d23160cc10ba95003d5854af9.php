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
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-indigo-500">Intake Review Desk</p>
                <h2 class="mt-1 text-2xl font-black leading-tight text-slate-950">Order Request <?php echo e($requestRow->request_ref); ?></h2>
                <p class="mt-1 text-sm text-slate-500">Review the customer request, correct bad links, resolve retailers, then convert to Draft Workbench.</p>
            </div>
            <a href="<?php echo e(route('order-requests.index')); ?>" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 shadow-sm hover:bg-slate-50">Back to requests</a>
        </div>
     <?php $__env->endSlot(); ?>

    <?php
        $requestName = trim(($requestRow->customer_first_name ?? '') . ' ' . ($requestRow->customer_last_name ?? '')) ?: ($requestRow->customer_company_name ?: 'Unknown customer');
        $defaultMode = old('customer_mode', $selectedCustomer ? 'existing' : 'create');
        $existingCustomerAction = old('existing_customer_action', 'keep');
        $hasCustomerDifferences = ! empty($customerDifferences ?? []);
        $existingFirst = old('first_name', $requestRow->customer_first_name ?? $selectedCustomer->first_name ?? '');
        $existingLast = old('last_name', $requestRow->customer_last_name ?? $selectedCustomer->last_name ?? '');
        $existingCompany = old('company_name', $requestRow->customer_company_name ?? $selectedCustomer->company_name ?? '');
        $existingEmail = old('email', $requestRow->customer_email ?? $selectedCustomer->email ?? '');
        $existingPhone = old('phone_digits', $requestRow->customer_phone_digits ?? $selectedCustomer->phone_digits ?? '');
        $existingPhoneCountry = old('phone_country_id', $requestRow->customer_phone_country_id ?? $selectedCustomer->phone_country_id ?? '');
        $existingAddress = old('address_line1', $requestRow->customer_address_line1 ?? $selectedCustomer->address_line1 ?? '');
        $existingPostcode = old('address_postcode', $requestRow->customer_address_postcode ?? $selectedCustomer->address_postcode ?? '');
        $existingAddressCountry = old('address_country_id', $requestRow->customer_address_country_id ?? $selectedCustomer->address_country_id ?? '');
        $isConverted = ! empty($requestRow->converted_at) || ($requestRow->status ?? '') === 'converted';
        $isCancelled = ($requestRow->status ?? '') === 'cancelled';
        $hasUnresolvedRetailers = isset($unresolvedRetailers) && $unresolvedRetailers->isNotEmpty();
        $resolvedRetailerCount = $items->filter(fn ($item) => ! empty($item->retailer_id) && ! empty($item->matched_retailer_name))->count();
        $unresolvedItemCount = max(0, $items->count() - $resolvedRetailerCount);
        $isCustomerSelfPurchase = ($requestRow->purchase_mode ?? 'standard') === 'customer_self_purchase';
        $submittedAt = $requestRow->submitted_at ?: $requestRow->created_at;
        $canEdit = ! $isConverted && ! $isCancelled;
        $canConvert = $canEdit && ! $hasUnresolvedRetailers;
        $statusLabel = $isConverted ? 'Converted' : ($isCancelled ? 'Cancelled' : ucfirst((string) ($requestRow->status ?: 'received')));
    ?>

    <style>[x-cloak] { display: none !important; }</style>

    <div class="space-y-5 pb-24" x-data="{ cancelOpen: false }">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800"><?php echo e(session('status')); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

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

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isCustomerSelfPurchase): ?>
            <div class="rounded-3xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-sky-700">Customer self-purchase request</p>
                        <p class="mt-2 max-w-4xl text-sm font-semibold leading-6 text-sky-950">Customer buys/pays the retailer directly. Dabba should not put these items into Dabba purchasing-to-buy workflows, but the request still needs clean retailer/product data for arrival, customs and customer communication later.</p>
                    </div>
                    <span class="rounded-full bg-white px-4 py-2 text-xs font-black uppercase tracking-wide text-sky-700 ring-1 ring-sky-200">Service / shipping only</span>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div class="grid gap-5 2xl:grid-cols-[minmax(0,1fr)_430px]">
            <main class="space-y-5">
                <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.25fr)_minmax(320px,0.75fr)]">
                        <div>
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-slate-400">Submitted customer</p>
                                    <h3 class="mt-1 text-2xl font-black text-slate-950"><?php echo e($requestName); ?></h3>
                                    <p class="mt-1 text-sm font-semibold text-slate-500"><?php echo e($requestRow->customer_email ?: 'No email supplied'); ?></p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-4 py-2 text-xs font-black uppercase tracking-wide <?php echo e($isConverted ? 'bg-emerald-100 text-emerald-800' : ($isCancelled ? 'bg-rose-100 text-rose-800' : 'bg-indigo-100 text-indigo-700')); ?>"><?php echo e($statusLabel); ?></span>
                                    <span class="rounded-full px-4 py-2 text-xs font-black uppercase tracking-wide <?php echo e($isCustomerSelfPurchase ? 'bg-sky-100 text-sky-800' : 'bg-slate-100 text-slate-700'); ?>"><?php echo e($isCustomerSelfPurchase ? 'Self purchase' : 'Dabba purchase'); ?></span>
                                </div>
                            </div>

                            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Request ref</p>
                                    <p class="mt-1 text-sm font-black text-slate-950"><?php echo e($requestRow->request_ref); ?></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Submitted</p>
                                    <p class="mt-1 text-sm font-black text-slate-950"><?php echo e($submittedAt ?: '—'); ?></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Source</p>
                                    <p class="mt-1 text-sm font-black text-slate-950"><?php echo e(Str::of((string) ($requestRow->source ?: 'public'))->replace('_', ' ')->title()); ?></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Estimate</p>
                                    <p class="mt-1 text-sm font-black text-slate-950">£<?php echo e(number_format((float) $requestRow->estimated_total, 2)); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Phone</p>
                                <p class="mt-1 text-sm font-black text-slate-950"><?php echo e($requestRow->customer_phone_digits ? (($requestRow->phone_country_code ? '+' . $requestRow->phone_country_code . ' ' : '') . $requestRow->customer_phone_digits) : '—'); ?></p>
                                <p class="mt-1 text-xs text-slate-500"><?php echo e($requestRow->phone_country_name ?: ''); ?></p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Address</p>
                                <p class="mt-1 text-sm font-black text-slate-950"><?php echo e($requestRow->customer_address_line1 ?: '—'); ?></p>
                                <p class="mt-1 text-xs text-slate-500"><?php echo e(trim(($requestRow->customer_address_postcode ?: '') . ' ' . ($requestRow->address_country_name ?: '')) ?: 'No country/postcode'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 rounded-2xl border <?php echo e(trim((string) ($requestRow->notes ?? '')) !== '' ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-slate-50'); ?> p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-xs font-black uppercase tracking-wide <?php echo e(trim((string) ($requestRow->notes ?? '')) !== '' ? 'text-amber-700' : 'text-slate-500'); ?>">Customer request notes</p>
                            <span class="rounded-full bg-white px-3 py-1 text-[11px] font-black uppercase tracking-wide <?php echo e(trim((string) ($requestRow->notes ?? '')) !== '' ? 'text-amber-700 ring-1 ring-amber-200' : 'text-slate-400 ring-1 ring-slate-200'); ?>"><?php echo e(trim((string) ($requestRow->notes ?? '')) !== '' ? 'copied through lifecycle' : 'none supplied'); ?></span>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(trim((string) ($requestRow->notes ?? '')) !== ''): ?>
                            <p class="mt-2 whitespace-pre-wrap text-sm leading-6 text-amber-950"><?php echo e($requestRow->notes); ?></p>
                        <?php else: ?>
                            <p class="mt-2 text-sm text-slate-500">The customer did not add order-level notes to this request.</p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </section>

                <section class="grid gap-4 lg:grid-cols-3">
                    <div class="rounded-3xl border <?php echo e($hasUnresolvedRetailers ? 'border-amber-300 bg-amber-50' : 'border-emerald-200 bg-emerald-50'); ?> p-5 shadow-sm lg:col-span-2">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.2em] <?php echo e($hasUnresolvedRetailers ? 'text-amber-700' : 'text-emerald-700'); ?>">Request health</p>
                                <h3 class="mt-1 text-xl font-black text-slate-950"><?php echo e($hasUnresolvedRetailers ? 'Review required before draft' : 'Ready for draft conversion'); ?></h3>
                                <p class="mt-1 text-sm font-semibold <?php echo e($hasUnresolvedRetailers ? 'text-amber-900' : 'text-emerald-900'); ?>">
                                    <?php echo e($hasUnresolvedRetailers ? 'Order Requests are the correction stage. Resolve every retailer before this request can move forward.' : 'All request retailers are resolved. The draft can inherit clean intake data.'); ?>

                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-4 py-2 text-xs font-black uppercase tracking-wide <?php echo e($hasUnresolvedRetailers ? 'bg-amber-600 text-white' : 'bg-emerald-600 text-white'); ?>"><?php echo e($hasUnresolvedRetailers ? 'Locked' : 'Clean'); ?></span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canEdit && $hasUnresolvedRetailers): ?>
                                    <a href="#retailer-review-queue" class="rounded-full bg-slate-950 px-4 py-2 text-xs font-black uppercase tracking-wide text-white shadow-sm hover:bg-slate-800">Review / add retailer</a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                        <div class="mt-5 grid gap-3 sm:grid-cols-4">
                            <div class="rounded-2xl bg-white p-4 text-center ring-1 ring-black/5">
                                <p class="text-2xl font-black text-slate-950"><?php echo e($items->count()); ?></p>
                                <p class="mt-1 text-[11px] font-black uppercase tracking-wide text-slate-400">Items</p>
                            </div>
                            <div class="rounded-2xl bg-white p-4 text-center ring-1 ring-black/5">
                                <p class="text-2xl font-black text-slate-950"><?php echo e($resolvedRetailerCount); ?>/<?php echo e($items->count()); ?></p>
                                <p class="mt-1 text-[11px] font-black uppercase tracking-wide text-slate-400">Retailers resolved</p>
                            </div>
                            <div class="rounded-2xl bg-white p-4 text-center ring-1 ring-black/5">
                                <p class="text-2xl font-black <?php echo e($unresolvedItemCount > 0 ? 'text-amber-700' : 'text-emerald-700'); ?>"><?php echo e($unresolvedItemCount); ?></p>
                                <p class="mt-1 text-[11px] font-black uppercase tracking-wide text-slate-400">Needs attention</p>
                            </div>
                            <div class="rounded-2xl bg-white p-4 text-center ring-1 ring-black/5">
                                <p class="text-2xl font-black text-slate-950"><?php echo e($attachments->count()); ?></p>
                                <p class="mt-1 text-[11px] font-black uppercase tracking-wide text-slate-400">Attachments</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Attachments</p>
                                <h3 class="mt-1 text-lg font-black text-slate-950">Customer files</h3>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600"><?php echo e($attachments->count()); ?></span>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($attachments->isEmpty()): ?>
                            <p class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-500">No attachments supplied.</p>
                        <?php else: ?>
                            <div class="mt-4 space-y-2">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attachment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-black text-slate-900"><?php echo e($attachment->original_name ?? basename((string) $attachment->path) ?? 'Attachment'); ?></p>
                                            <p class="mt-0.5 text-xs font-semibold text-slate-500"><?php echo e($attachment->mime ?? 'file'); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($attachment->size)): ?> · <?php echo e(number_format(((float) $attachment->size) / 1024, 1)); ?> KB <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></p>
                                        </div>
                                        <a href="<?php echo e(route('order-requests.attachments.show', [$requestRow->id, $attachment->id])); ?>" target="_blank" rel="noopener noreferrer" aria-label="Open attachment" title="Open attachment" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-indigo-200 bg-white text-lg font-black text-indigo-600 shadow-sm hover:bg-indigo-50">↗</a>
                                    </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </section>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canEdit && $hasUnresolvedRetailers): ?>
                    <section id="retailer-review-queue" class="rounded-3xl border border-amber-300 bg-white shadow-sm" x-data="retailerReviewQueue(<?php echo \Illuminate\Support\Js::from($unresolvedRetailers->values())->toHtml() ?>)">
                        <div class="border-b border-amber-200 bg-amber-50 p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-700">Action needed</p>
                                    <h3 class="mt-1 text-xl font-black text-slate-950">Review / add unknown retailers</h3>
                                    <p class="mt-1 max-w-3xl text-sm font-semibold leading-6 text-amber-900">This is where unresolved retailers are fixed. If the link is wrong, correct it on the item first. If the retailer is genuinely new, review it here and add/link it to the affected items.</p>
                                </div>
                                <span class="rounded-full bg-amber-600 px-4 py-2 text-xs font-black uppercase tracking-wide text-white"><?php echo e($unresolvedRetailers->count()); ?> unresolved</span>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-100">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $unresolvedRetailers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loopIndex => $retailer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Unknown retailer <?php echo e($loop->iteration); ?> of <?php echo e($unresolvedRetailers->count()); ?></p>
                                        <h4 class="mt-1 truncate text-base font-black text-slate-950"><?php echo e($retailer['base_url'] ?: $retailer['name']); ?></h4>
                                        <p class="mt-1 text-sm text-slate-600">Found on <span class="font-black text-slate-900"><?php echo e($retailer['items_count'] ?? 1); ?></span> item<?php echo e(($retailer['items_count'] ?? 1) === 1 ? '' : 's'); ?></p>
                                    </div>
                                    <button type="button" @click="open(<?php echo e($loopIndex); ?>)" class="rounded-2xl bg-amber-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-amber-700">Review / add</button>
                                </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </div>

                        <div x-cloak x-show="isOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
                            <div @click.away="close()" class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white shadow-2xl ring-1 ring-slate-200">
                                <form method="POST" action="<?php echo e(route('order-requests.retailers.store', $requestRow->id)); ?>" class="p-5 sm:p-6">
                                    <?php echo csrf_field(); ?>
                                    <template x-for="itemId in (current?.item_ids || [])" :key="itemId">
                                        <input type="hidden" name="item_ids[]" :value="itemId">
                                    </template>
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-xs font-black uppercase tracking-wide text-amber-700" x-text="currentLabel"></p>
                                            <h3 class="mt-1 text-xl font-black text-slate-950">Review / add unknown retailer</h3>
                                            <p class="mt-1 text-sm text-slate-600">This will create the retailer if it does not already exist, then link the affected request items.</p>
                                        </div>
                                        <button type="button" @click="close()" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-xl font-black text-slate-500 hover:bg-slate-50" aria-label="Close retailer review">×</button>
                                    </div>

                                    <div class="mt-5 space-y-4">
                                        <div>
                                            <label class="block text-[11px] font-black uppercase tracking-wide text-slate-500">Retailer name</label>
                                            <input name="name" required :value="current?.name || ''" placeholder="Argos" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-black uppercase tracking-wide text-slate-500">Base domain</label>
                                            <input name="base_url" required :value="current?.base_url || ''" placeholder="argos.co.uk" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                            <p class="mt-1 text-xs text-slate-500">Use only the shop domain, not a product page.</p>
                                        </div>
                                    </div>

                                    <details class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                        <summary class="cursor-pointer text-xs font-black uppercase tracking-wide text-slate-500">Source links (<span x-text="sourceCount"></span>)</summary>
                                        <div class="mt-3 space-y-2">
                                            <template x-for="sourceUrl in (current?.urls || [])" :key="sourceUrl">
                                                <div class="flex items-start gap-2 rounded-xl bg-white p-2 ring-1 ring-slate-100">
                                                    <a :href="sourceUrl" target="_blank" rel="noopener noreferrer" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 text-base font-black text-indigo-600 hover:bg-indigo-100">↗</a>
                                                    <p class="min-w-0 break-all text-xs font-semibold text-slate-600" x-text="sourceUrl"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </details>

                                    <div class="mt-6 flex flex-wrap justify-end gap-3">
                                        <button type="button" @click="close()" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">Cancel</button>
                                        <button type="submit" class="rounded-2xl bg-amber-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-amber-700">Add/link retailer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </section>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


                <section class="rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div>
                            <h3 class="text-xl font-black text-slate-950">Request items</h3>
                            <p class="mt-1 text-sm text-slate-500">Correct bad links here. DabbaDesk re-runs retailer matching when an item is saved.</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600"><?php echo e($items->count()); ?> item<?php echo e($items->count() === 1 ? '' : 's'); ?></span>
                    </div>

                    <div class="grid gap-4 p-5">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <?php
                                $itemRetailerResolved = ! empty($item->retailer_id) && ! empty($item->matched_retailer_name);
                                $itemTotal = (float) ($item->line_total ?? ((float) $item->unit_price * (int) $item->quantity));
                            ?>
                            <article class="rounded-3xl border <?php echo e($itemRetailerResolved ? 'border-slate-200 bg-white' : 'border-amber-300 bg-amber-50'); ?> p-4 shadow-sm">
                                <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_220px]">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-slate-600">Item #<?php echo e($loop->iteration); ?></span>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($itemRetailerResolved): ?>
                                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-emerald-700">Retailer resolved</span>
                                                    <?php else: ?>
                                                        <span class="rounded-full bg-amber-600 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-white">Needs retailer</span>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </div>
                                                <h4 class="mt-2 text-base font-black leading-6 text-slate-950"><?php echo e($item->description ?: 'No description supplied'); ?></h4>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Line total</p>
                                                <p class="text-lg font-black text-slate-950">£<?php echo e(number_format($itemTotal, 2)); ?></p>
                                            </div>
                                        </div>

                                        <div class="mt-4 grid gap-3 md:grid-cols-3">
                                            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100 md:col-span-2">
                                                <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Customer/product link</p>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->retailer_url): ?>
                                                    <div class="mt-1 flex items-center gap-2">
                                                        <a href="<?php echo e($item->retailer_url); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-indigo-200 bg-white text-base font-black text-indigo-600 hover:bg-indigo-50" title="Open product link" aria-label="Open product link">↗</a>
                                                        <p class="min-w-0 truncate text-sm font-semibold text-slate-700"><?php echo e($item->retailer_url); ?></p>
                                                    </div>
                                                <?php else: ?>
                                                    <p class="mt-1 text-sm font-semibold text-slate-400">No link supplied</p>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </div>
                                            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                                <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Product code</p>
                                                <p class="mt-1 text-sm font-black text-slate-900"><?php echo e($item->product_code ?: '—'); ?></p>
                                            </div>
                                            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                                <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Quantity</p>
                                                <p class="mt-1 text-sm font-black text-slate-900"><?php echo e((int) $item->quantity); ?></p>
                                            </div>
                                            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                                <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Unit price</p>
                                                <p class="mt-1 text-sm font-black text-slate-900">£<?php echo e(number_format((float) $item->unit_price, 2)); ?></p>
                                            </div>
                                            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                                <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Raw retailer name</p>
                                                <p class="mt-1 text-sm font-black text-slate-900"><?php echo e($item->retailer_name ?: '—'); ?></p>
                                            </div>
                                        </div>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->notes): ?>
                                            <div class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 p-3">
                                                <p class="text-[11px] font-black uppercase tracking-wide text-amber-700">Item notes</p>
                                                <p class="mt-1 whitespace-pre-wrap text-sm leading-6 text-amber-950"><?php echo e($item->notes); ?></p>
                                            </div>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canEdit): ?>
                                            <details class="mt-4 rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                                                <summary class="cursor-pointer text-sm font-black text-indigo-800">Edit item / correct link</summary>
                                                <form method="POST" action="<?php echo e(route('order-requests.items.update', [$requestRow->id, $item->id])); ?>" class="mt-4 grid gap-3">
                                                    <?php echo csrf_field(); ?>
                                                    <div>
                                                        <label class="text-[11px] font-black uppercase tracking-wide text-indigo-800">Product / retailer link</label>
                                                        <input name="retailer_url" value="<?php echo e(old('retailer_url', $item->retailer_url)); ?>" placeholder="https://www.argos.co.uk/..." class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500">
                                                        <p class="mt-1 text-[11px] font-semibold text-indigo-700">Example correction: change <span class="font-black">https://argos</span> to <span class="font-black">https://www.argos.co.uk</span>, then save.</p>
                                                    </div>
                                                    <div class="grid gap-3 md:grid-cols-2">
                                                        <div>
                                                            <label class="text-[11px] font-black uppercase tracking-wide text-indigo-800">Retailer name</label>
                                                            <input name="retailer_name" value="<?php echo e(old('retailer_name', $item->retailer_name ?: $item->matched_retailer_name)); ?>" placeholder="Argos" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500">
                                                        </div>
                                                        <div>
                                                            <label class="text-[11px] font-black uppercase tracking-wide text-indigo-800">Product code / SKU</label>
                                                            <input name="product_code" value="<?php echo e(old('product_code', $item->product_code)); ?>" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500">
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="text-[11px] font-black uppercase tracking-wide text-indigo-800">Description</label>
                                                        <textarea name="description" rows="2" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500"><?php echo e(old('description', $item->description)); ?></textarea>
                                                    </div>
                                                    <div class="grid gap-3 md:grid-cols-3">
                                                        <div>
                                                            <label class="text-[11px] font-black uppercase tracking-wide text-indigo-800">Qty</label>
                                                            <input type="number" min="1" name="quantity" value="<?php echo e(old('quantity', $item->quantity)); ?>" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500">
                                                        </div>
                                                        <div>
                                                            <label class="text-[11px] font-black uppercase tracking-wide text-indigo-800">Unit price</label>
                                                            <input type="number" step="0.01" min="0" name="unit_price" value="<?php echo e(old('unit_price', number_format((float) $item->unit_price, 2, '.', ''))); ?>" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500">
                                                        </div>
                                                        <div class="flex items-end">
                                                            <button type="submit" class="w-full rounded-xl bg-indigo-600 px-3 py-2 text-sm font-black text-white shadow-sm hover:bg-indigo-700">Save & re-detect</button>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="text-[11px] font-black uppercase tracking-wide text-indigo-800">Item notes</label>
                                                        <textarea name="notes" rows="2" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500"><?php echo e(old('notes', $item->notes)); ?></textarea>
                                                    </div>
                                                </form>
                                            </details>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>

                                    <aside class="rounded-2xl <?php echo e($itemRetailerResolved ? 'bg-emerald-50 ring-1 ring-emerald-200' : 'bg-white ring-1 ring-amber-300'); ?> p-4">
                                        <p class="text-[11px] font-black uppercase tracking-wide <?php echo e($itemRetailerResolved ? 'text-emerald-700' : 'text-amber-700'); ?>">Retailer status</p>
                                        <p class="mt-2 text-lg font-black <?php echo e($itemRetailerResolved ? 'text-emerald-950' : 'text-slate-950'); ?>"><?php echo e($item->matched_retailer_name ?: ($item->retailer_name ?: 'Unknown retailer')); ?></p>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($itemRetailerResolved): ?>
                                            <p class="mt-1 text-xs font-bold text-emerald-700">Matched to retailer ID #<?php echo e($item->retailer_id); ?></p>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->matched_retailer_base_url): ?>
                                                <p class="mt-2 break-all text-xs text-emerald-800"><?php echo e($item->matched_retailer_base_url); ?></p>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <?php else: ?>
                                            <p class="mt-1 text-sm font-bold text-amber-800">Correct the link, or use Review / add retailer before converting.</p>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canEdit): ?>
                                                <a href="#retailer-review-queue" class="mt-3 inline-flex rounded-xl bg-amber-600 px-3 py-2 text-xs font-black text-white hover:bg-amber-700">Review / add retailer</a>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </aside>
                                </div>
                            </article>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm font-semibold text-slate-500">No request items found.</div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </section>

            </main>

            <aside class="space-y-5 2xl:sticky 2xl:top-6 2xl:self-start">
                <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h3 class="text-lg font-black text-slate-950">Customer match & conversion</h3>
                    <p class="mt-1 text-sm text-slate-500">Choose/create the customer record before creating the draft.</p>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isConverted): ?>
                        <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                            <p class="font-black">Already converted</p>
                            <p class="mt-1">Draft order ID: <?php echo e($requestRow->converted_draft_order_id); ?></p>
                            <p class="mt-1 text-xs">Converted at <?php echo e($requestRow->converted_at); ?></p>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($requestRow->converted_draft_order_id): ?>
                                <a href="<?php echo e(route('draft-orders.show', $requestRow->converted_draft_order_id)); ?>" class="mt-3 inline-flex rounded-2xl bg-emerald-600 px-4 py-2 text-xs font-black text-white hover:bg-emerald-700">Open Draft</a>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php elseif($isCancelled): ?>
                        <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                            <p class="font-black">Request cancelled</p>
                            <p class="mt-1">This request will not be converted to a draft.</p>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($cancellationLog?->body)): ?>
                                <div class="mt-3 rounded-xl bg-white/70 p-3 text-xs leading-5 text-rose-900 ring-1 ring-rose-100">
                                    <p class="font-black uppercase tracking-wide text-rose-700">Cancellation reason</p>
                                    <p class="mt-1 whitespace-pre-line"><?php echo e($cancellationLog->body); ?></p>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $requestRow->reviewed_at): ?>
                            <form method="POST" action="<?php echo e(route('order-requests.review', $requestRow->id)); ?>" class="mt-4">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="w-full rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-black text-indigo-700 hover:bg-indigo-100">Mark as under review</button>
                            </form>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <form method="GET" action="<?php echo e(route('order-requests.show', $requestRow->id)); ?>" class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <label class="text-xs font-black uppercase tracking-wide text-slate-500">Search existing customer</label>
                            <div class="mt-2 flex gap-2">
                                <input name="customer_q" value="<?php echo e($customerSearch); ?>" placeholder="Name, email, phone…" class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                <button class="rounded-xl bg-slate-950 px-3 py-2 text-sm font-black text-white">Search</button>
                            </div>
                        </form>

                        <form method="POST" action="<?php echo e(route('order-requests.convert', $requestRow->id)); ?>" class="mt-4 space-y-4">
                            <?php echo csrf_field(); ?>

                            <div class="rounded-2xl border border-slate-200 p-4">
                                <label class="flex cursor-pointer items-start gap-3">
                                    <input type="radio" name="customer_mode" value="existing" class="mt-1" <?php if($defaultMode === 'existing'): echo 'checked'; endif; ?>>
                                    <span>
                                        <span class="block text-sm font-black text-slate-900">Use existing customer</span>
                                        <span class="block text-xs text-slate-500">Recommended when a matching customer exists.</span>
                                    </span>
                                </label>

                                <select name="customer_id" class="mt-3 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                    <option value="">Choose customer…</option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $customerOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <option value="<?php echo e($customer->id); ?>" <?php if((int) $selectedCustomerId === (int) $customer->id): echo 'selected'; endif; ?>>
                                            #<?php echo e($customer->id); ?> — <?php echo e(trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: ($customer->company_name ?? 'Customer')); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($customer->email)): ?> · <?php echo e($customer->email); ?> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($customerOptions ?? collect())->isEmpty()): ?>
                                    <p class="mt-2 text-xs font-semibold text-slate-500">No suggested customers found yet. Search above or create a new customer below.</p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedCustomer && $hasCustomerDifferences): ?>
                                    <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                                        <p class="font-black uppercase tracking-wide text-amber-700">Request differs from saved customer</p>
                                        <div class="mt-3 space-y-2">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $customerDifferences; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $difference): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                <?php
                                                    $differenceLabel = is_array($difference) ? ($difference['label'] ?? 'Customer detail') : 'Customer detail';
                                                    $storedValue = is_array($difference) ? ($difference['stored'] ?? '—') : null;
                                                    $submittedValue = is_array($difference) ? ($difference['submitted'] ?? '—') : $difference;
                                                ?>
                                                <div class="rounded-xl bg-white/80 p-3 ring-1 ring-amber-100">
                                                    <p class="text-[11px] font-black uppercase tracking-wide text-amber-700"><?php echo e($differenceLabel); ?></p>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($storedValue !== null): ?>
                                                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                                            <div>
                                                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Saved</p>
                                                                <p class="mt-0.5 break-words text-xs font-bold text-slate-700"><?php echo e($storedValue ?: '—'); ?></p>
                                                            </div>
                                                            <div>
                                                                <p class="text-[10px] font-black uppercase tracking-wide text-amber-600">Request</p>
                                                                <p class="mt-0.5 break-words text-xs font-bold text-amber-950"><?php echo e($submittedValue ?: '—'); ?></p>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <p class="mt-1 break-words text-xs font-bold text-amber-950"><?php echo e($submittedValue); ?></p>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </div>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <div class="mt-3 grid gap-2">
                                    <label class="flex cursor-pointer items-start gap-3 rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200">
                                        <input type="radio" name="existing_customer_action" value="keep" class="mt-1" <?php if($existingCustomerAction !== 'update'): echo 'checked'; endif; ?>>
                                        <span>
                                            <span class="block text-sm font-black text-slate-900">Keep saved customer details</span>
                                            <span class="block text-xs text-slate-500">Safest default.</span>
                                        </span>
                                    </label>
                                    <label class="flex cursor-pointer items-start gap-3 rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200">
                                        <input type="radio" name="existing_customer_action" value="update" class="mt-1" <?php if($existingCustomerAction === 'update'): echo 'checked'; endif; ?>>
                                        <span>
                                            <span class="block text-sm font-black text-slate-900">Update saved customer details</span>
                                            <span class="block text-xs text-slate-500">Use when the request contains corrected contact/address details.</span>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 p-4">
                                <label class="flex cursor-pointer items-start gap-3">
                                    <input type="radio" name="customer_mode" value="create" class="mt-1" <?php if($defaultMode === 'create'): echo 'checked'; endif; ?>>
                                    <span>
                                        <span class="block text-sm font-black text-slate-900">Create new customer</span>
                                        <span class="block text-xs text-slate-500">Use when no existing match is suitable.</span>
                                    </span>
                                </label>
                            </div>

                            <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="text-sm font-black text-indigo-950">Submitted/editable customer details</h4>
                                    <span class="rounded-full bg-white px-2 py-1 text-[11px] font-black uppercase tracking-wide text-indigo-700">editable</span>
                                </div>

                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="text-xs font-bold uppercase tracking-wide text-indigo-800">First name</label>
                                        <input name="first_name" value="<?php echo e($existingFirst); ?>" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold uppercase tracking-wide text-indigo-800">Last name</label>
                                        <input name="last_name" value="<?php echo e($existingLast); ?>" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="text-xs font-bold uppercase tracking-wide text-indigo-800">Company</label>
                                        <input name="company_name" value="<?php echo e($existingCompany); ?>" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="text-xs font-bold uppercase tracking-wide text-indigo-800">Email</label>
                                        <input name="email" value="<?php echo e($existingEmail); ?>" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold uppercase tracking-wide text-indigo-800">Phone country</label>
                                        <select name="phone_country_id" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm">
                                            <option value="">—</option>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $countries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $country): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                <option value="<?php echo e($country->id); ?>" <?php if((string) $existingPhoneCountry === (string) $country->id): echo 'selected'; endif; ?>><?php echo e($country->phone_code ? '+' . $country->phone_code . ' — ' : ''); ?><?php echo e($country->name); ?></option>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold uppercase tracking-wide text-indigo-800">Phone digits</label>
                                        <input name="phone_digits" value="<?php echo e($existingPhone); ?>" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="text-xs font-bold uppercase tracking-wide text-indigo-800">Address</label>
                                        <textarea name="address_line1" rows="3" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm"><?php echo e($existingAddress); ?></textarea>
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold uppercase tracking-wide text-indigo-800">Postcode</label>
                                        <input name="address_postcode" value="<?php echo e($existingPostcode); ?>" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold uppercase tracking-wide text-indigo-800">Address country</label>
                                        <select name="address_country_id" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm">
                                            <option value="">—</option>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $countries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $country): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                <option value="<?php echo e($country->id); ?>" <?php if((string) $existingAddressCountry === (string) $country->id): echo 'selected'; endif; ?>><?php echo e($country->name); ?></option>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border <?php echo e($canConvert ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-900'); ?> p-3 text-xs font-semibold">
                                Draft number will be <strong><?php echo e($requestRow->request_ref); ?></strong>. <?php echo e($canConvert ? 'All retailers are resolved.' : 'Resolve all retailers before conversion.'); ?>

                            </div>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canConvert): ?>
                                <button type="submit" class="w-full rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-black text-white shadow-sm hover:bg-emerald-700">Convert to Draft Workbench</button>
                            <?php else: ?>
                                <a href="#retailer-review-queue" class="block w-full rounded-2xl bg-amber-600 px-4 py-3 text-center text-sm font-black text-white shadow-sm hover:bg-amber-700">Resolve retailers before conversion</a>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </form>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </section>
            </aside>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canEdit): ?>
            <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-2xl backdrop-blur">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-slate-400">Order Request <?php echo e($requestRow->request_ref); ?></p>
                        <p class="text-sm font-bold <?php echo e($canConvert ? 'text-emerald-700' : 'text-amber-700'); ?>"><?php echo e($canConvert ? 'Ready to convert to draft.' : 'Conversion locked until all retailers are resolved.'); ?></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" @click="cancelOpen = true" class="rounded-2xl border border-rose-200 bg-white px-4 py-2 text-sm font-black text-rose-700 shadow-sm hover:bg-rose-50">Cancel Request</button>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canConvert): ?>
                            <button type="button" onclick="document.querySelector('form[action=&quot;<?php echo e(route('order-requests.convert', $requestRow->id)); ?>&quot;]').requestSubmit()" class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-emerald-700">Convert to Draft</button>
                        <?php else: ?>
                            <a href="#retailer-review-queue" class="rounded-2xl bg-amber-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-amber-700">Resolve Retailers</a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </div>

            <div x-cloak x-show="cancelOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
                <div @click.away="cancelOpen = false" class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-rose-600">Cancel request</p>
                            <h3 class="mt-1 text-xl font-black text-slate-950">Cancel Order Request <?php echo e($requestRow->request_ref); ?>?</h3>
                            <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">This prevents conversion to draft. Use this only for duplicates, customer cancellation, or invalid submissions.</p>
                        </div>
                        <button type="button" @click="cancelOpen = false" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-xl font-black text-slate-500 hover:bg-slate-50">×</button>
                    </div>

                    <form method="POST" action="<?php echo e(route('order-requests.cancel', $requestRow->id)); ?>" class="mt-5 space-y-4">
                        <?php echo csrf_field(); ?>
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-rose-700">Reason</label>
                            <textarea name="cancel_reason" rows="4" required minlength="3" placeholder="Customer changed mind, duplicate request, submitted by mistake…" class="mt-1 w-full rounded-2xl border border-rose-200 bg-white px-4 py-3 text-sm focus:border-rose-500 focus:ring-rose-500"><?php echo e(old('cancel_reason')); ?></textarea>
                        </div>
                        <div class="flex flex-wrap justify-end gap-3">
                            <button type="button" @click="cancelOpen = false" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">Keep request</button>
                            <button type="submit" class="rounded-2xl bg-rose-600 px-4 py-2 text-sm font-black text-white hover:bg-rose-700">Cancel request</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <script>
        function retailerReviewQueue(retailers) {
            return {
                retailers: retailers || [],
                index: null,
                get isOpen() { return this.index !== null; },
                get current() { return this.index === null ? null : this.retailers[this.index]; },
                get currentLabel() { return this.current ? `Retailer ${this.index + 1} of ${this.retailers.length}` : ''; },
                get sourceCount() { return (this.current?.urls || []).length; },
                open(index) { this.index = index; },
                close() { this.index = null; },
            }
        }
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
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/order-requests/show.blade.php ENDPATH**/ ?>