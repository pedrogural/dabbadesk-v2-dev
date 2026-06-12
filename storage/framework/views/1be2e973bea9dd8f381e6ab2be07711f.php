        <div x-show="tab === 'overview'" x-cloak class="space-y-5">
            <div class="flex flex-col items-start gap-5 xl:flex-row xl:items-stretch">
                <section class="w-full max-w-[720px] rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 21a8 8 0 0 0-16 0" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-400">Customer</p>
                                <h2 class="mt-1 truncate text-xl font-black text-slate-950"><?php echo e($customerFullName ?: 'Unknown customer'); ?></h2>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customerCompany): ?>
                                    <p class="mt-1 truncate text-sm font-semibold text-slate-500"><?php echo e($customerCompany); ?></p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($order->customer_id)): ?>
                                <a href="<?php echo e(route('customers.edit', $order->customer_id)); ?>" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-indigo-100 hover:text-indigo-700">Customer ↗</a>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customerFullName): ?>
                                <button type="button" data-copy-value="<?php echo e($customerFullName); ?>" class="copy-btn rounded-xl border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Copy name</button>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($copyCustomerId): ?>
                                <button type="button" data-copy-value="<?php echo e($copyCustomerId); ?>" class="copy-btn rounded-xl border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Copy ID</button>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2">
                        <div class="grid gap-3 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-100 md:grid-cols-[30px_92px_minmax(0,1fr)_auto] md:items-center">
                            <div class="hidden text-slate-400 md:block">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                            </div>
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Email</p>
                            <p class="min-w-0 break-all text-sm font-semibold text-slate-800"><?php echo e($customerEmail ?: 'No email'); ?></p>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customerEmail): ?>
                                <button type="button" data-copy-value="<?php echo e($customerEmail); ?>" class="copy-btn justify-self-start rounded-xl border border-indigo-200 bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 md:justify-self-end">Copy</button>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        <div class="grid gap-3 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-100 md:grid-cols-[30px_92px_minmax(0,1fr)_auto] md:items-center">
                            <div class="hidden text-slate-400 md:block">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.32 1.78.59 2.63a2 2 0 0 1-.45 2.11L8 9.71a16 16 0 0 0 6.29 6.29l1.25-1.25a2 2 0 0 1 2.11-.45c.85.27 1.73.47 2.63.59A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Phone</p>
                            <p class="text-sm font-semibold text-slate-800"><?php echo e($customerPhone ?: 'No phone'); ?></p>
                            <div class="flex flex-wrap gap-2 md:justify-self-end">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customerPhone): ?>
                                    <button type="button" data-copy-value="<?php echo e($customerPhone); ?>" class="copy-btn rounded-xl border border-indigo-200 bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Copy</button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($whatsappUrl): ?>
                                    <a href="<?php echo e($whatsappUrl); ?>" target="_blank" rel="noopener noreferrer" class="rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100 hover:bg-emerald-100">WhatsApp ↗</a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>

                        <div class="grid gap-3 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-100 md:grid-cols-[30px_92px_minmax(0,1fr)_auto] md:items-start">
                            <div class="hidden pt-0.5 text-slate-400 md:block">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <p class="pt-0.5 text-[10px] font-black uppercase tracking-wide text-slate-400">Address</p>
                            <div class="min-w-0">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($addressLines->isNotEmpty()): ?>
                                    <div class="space-y-0.5 text-sm font-semibold leading-5 text-slate-800">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $addressLines; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                            <p><?php echo e($line); ?></p>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-sm text-slate-400">No billing address captured.</p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($copyFullAddress): ?>
                                <button type="button" data-copy-value="<?php echo e($copyFullAddress); ?>" class="copy-btn justify-self-start rounded-xl border border-indigo-200 bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 md:justify-self-end">Copy</button>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($copyCustomerId): ?>
                            <div class="grid gap-3 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-100 md:grid-cols-[30px_92px_minmax(0,1fr)_auto] md:items-center">
                                <div class="hidden text-slate-400 md:block">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="14" x="3" y="5" rx="2"/><path d="M7 9h4"/><path d="M7 13h2"/><circle cx="16" cy="11" r="2"/></svg>
                                </div>
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Customer ID</p>
                                <p class="text-sm font-semibold text-slate-800">#<?php echo e($order->customer_id); ?></p>
                                <button type="button" data-copy-value="<?php echo e($copyCustomerId); ?>" class="copy-btn justify-self-start rounded-xl border border-indigo-200 bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 md:justify-self-end">Copy</button>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </section>

                <section class="w-full max-w-[360px] rounded-3xl <?php echo e($balanceDue > 0 ? 'border-rose-200 bg-rose-50' : 'border-emerald-200 bg-emerald-50'); ?> border p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] <?php echo e($balanceDue > 0 ? 'text-rose-700' : 'text-emerald-700'); ?>">Outstanding balance</p>
                    <p class="mt-2 text-4xl font-semibold <?php echo e($balanceDue > 0 ? 'text-rose-700' : 'text-emerald-700'); ?>">£<?php echo e(number_format($balanceDue, 2)); ?></p>
                    <p class="mt-2 text-sm text-slate-600">Total £<?php echo e(number_format($orderTotal, 2)); ?> · Settled £<?php echo e(number_format($settledTotal, 2)); ?></p>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($walletAvailable > 0.004): ?>
                        <p class="mt-2 rounded-2xl bg-white/70 px-3 py-2 text-sm font-semibold text-sky-800 ring-1 ring-sky-100">Wallet available: £<?php echo e(number_format($walletAvailable, 2)); ?></p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" @click="$dispatch('open-payment-modal')" class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Record payment</button>
                        <button type="button" data-copy-value="<?php echo e(e($copyPaymentDetails)); ?>" class="copy-btn rounded-2xl border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Copy payment details</button>
                    </div>
                </section>
            </div>

            <div class="grid gap-5 lg:grid-cols-3">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Invoice</p>
                    <p class="mt-1 text-lg font-semibold text-slate-950"><?php echo e($invoiceStatusLabel); ?></p>
                    <button type="button" @click="$dispatch('open-invoice-modal')" class="mt-3 rounded-2xl <?php echo e($hasInvoiceWorkspace ? 'bg-slate-900 hover:bg-slate-800' : 'bg-amber-600 hover:bg-amber-700'); ?> px-4 py-2 text-sm font-semibold text-white"><?php echo e($hasInvoiceWorkspace ? 'Create new version' : 'Create invoice'); ?></button>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Progress</p>
                    <p class="mt-2 text-sm text-slate-600">Items <?php echo e($progress['item_qty'] ?? 0); ?> · Bought <?php echo e($progress['purchased_qty'] ?? 0); ?> · Arrived <?php echo e($progress['arrived_qty'] ?? 0); ?> · Done <?php echo e($progress['collected_qty'] ?? 0); ?></p>
                    <p class="mt-2 text-sm text-slate-600"><?php echo e($purchaseStatusLabel); ?></p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Source</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($order->order_request_id)): ?>
                            <a href="<?php echo e(route('order-requests.show', $order->order_request_id)); ?>" class="rounded-xl bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100">Request ↗</a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <a href="<?php echo e(route('draft-orders.show', $order->draft_order_id)); ?>" class="rounded-xl bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">Draft ↗</a>
                        <a href="<?php echo e(route('money-desk.orders.show', $order->id)); ?>" class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">Finance ↗</a>
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
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($revisionTotal > 1 || $walletCreditFromRevisions > 0.004): ?>
            <div class="rounded-3xl border border-violet-200 bg-violet-50 p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-violet-700">Revision impact</p>
                        <h2 class="mt-1 text-lg font-black text-violet-950"><?php echo e($revisionBadgeLabel); ?></h2>
                        <p class="mt-1 text-sm font-semibold leading-6 text-violet-900">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($walletCreditFromRevisions > 0.004): ?>
                                This revision chain generated £<?php echo e(number_format($walletCreditFromRevisions, 2)); ?> wallet credit for the customer.
                            <?php else: ?>
                                This order has saved historical snapshots. Older versions remain viewable for audit.
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </p>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($revisionHistory ?? collect())->count() > 1): ?>
                        <span class="rounded-2xl bg-white px-4 py-2 text-sm font-black text-violet-700 ring-1 ring-violet-200"><?php echo e(($revisionHistory ?? collect())->count()); ?> snapshot<?php echo e(($revisionHistory ?? collect())->count() === 1 ? '' : 's'); ?></span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($revisionHistory ?? collect())->count() > 1): ?>
                    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ($revisionHistory ?? collect())->take(6); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $revision): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <?php
                                $isCurrentRevision = (int) $revision->id === (int) $order->id;
                                $isSupersededRevision = ($revision->revision_state ?? '') === 'superseded';
                            ?>
                            <div class="rounded-2xl <?php echo e($isCurrentRevision ? 'bg-white ring-2 ring-violet-200' : 'bg-white/80 ring-1 ring-violet-100'); ?> p-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-black text-slate-950">Rev <?php echo e($revision->revision_number); ?></p>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isCurrentRevision): ?>
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-black text-emerald-700">Current</span>
                                    <?php elseif($isSupersededRevision): ?>
                                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-black text-rose-700">Superseded</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <p class="mt-1 text-xs font-semibold text-slate-500">£<?php echo e(number_format($revision->grand_total ?? 0, 2)); ?> · <?php echo e($revision->created_at ? \Carbon\Carbon::parse($revision->created_at)->format('d M Y') : 'Date unknown'); ?></p>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $isCurrentRevision): ?>
                                    <a href="<?php echo e(route('orders.show', $revision->id)); ?>" class="mt-3 inline-flex rounded-xl bg-white px-3 py-1.5 text-xs font-bold text-violet-700 ring-1 ring-violet-200 hover:bg-violet-100">View snapshot ↗</a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
        </div>
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/orders/show/tabs/_overview.blade.php ENDPATH**/ ?>