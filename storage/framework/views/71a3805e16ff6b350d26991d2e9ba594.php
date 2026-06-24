        <div x-show="tab === 'finance'" x-cloak class="space-y-5">
            <div class="grid gap-5 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Finance summary</p>
                            <h2 class="mt-1 text-lg font-black text-slate-950">What does the customer owe?</h2>
                            <p class="mt-1 text-sm text-slate-500">A plain-English view of the money position for this order.</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-black uppercase tracking-wide ring-1 <?php echo e($paymentStatusClasses); ?>"><?php echo e($paymentStatusLabel); ?></span>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-3xl bg-slate-50 p-4 ring-1 ring-slate-100">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Order total</p>
                            <p class="mt-1 text-2xl font-black text-slate-950">£<?php echo e(number_format($orderTotal, 2)); ?></p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">Full value of this order snapshot.</p>
                        </div>
                        <div class="rounded-3xl <?php echo e($balanceDue > 0.004 ? 'bg-rose-50 ring-rose-100' : 'bg-emerald-50 ring-emerald-100'); ?> p-4 ring-1">
                            <p class="text-[10px] font-black uppercase tracking-wide <?php echo e($balanceDue > 0.004 ? 'text-rose-700' : 'text-emerald-700'); ?>">Outstanding balance</p>
                            <p class="mt-1 text-2xl font-black <?php echo e($balanceDue > 0.004 ? 'text-rose-700' : 'text-emerald-700'); ?>">£<?php echo e(number_format($balanceDue, 2)); ?></p>
                            <p class="mt-1 text-xs font-semibold <?php echo e($balanceDue > 0.004 ? 'text-rose-700' : 'text-emerald-700'); ?>">
                                <?php echo e($balanceDue > 0.004 ? 'Customer still needs to settle this amount.' : 'This order is financially settled.'); ?>

                            </p>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3">
                            <p class="text-[10px] font-black uppercase tracking-wide text-emerald-700">Payments received</p>
                            <p class="mt-1 text-lg font-black text-emerald-700">£<?php echo e(number_format((float) ($finance['payments_used'] ?? 0), 2)); ?></p>
                            <p class="mt-1 text-[11px] font-semibold text-emerald-700">Real customer payments applied to this order.</p>
                        </div>
                        <div class="rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3">
                            <p class="text-[10px] font-black uppercase tracking-wide text-sky-700">Wallet used</p>
                            <p class="mt-1 text-lg font-black text-sky-700">£<?php echo e(number_format((float) ($finance['wallet_used'] ?? 0), 2)); ?></p>
                            <p class="mt-1 text-[11px] font-semibold text-sky-700">Existing wallet balance used on this order.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Net settled</p>
                            <p class="mt-1 text-lg font-black text-slate-900">£<?php echo e(number_format($settledTotal, 2)); ?></p>
                            <p class="mt-1 text-[11px] font-semibold text-slate-500">Payments plus wallet use, minus reversals/refunds.</p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $isHistoricalRevision): ?>
                            <button type="button" @click="$dispatch('open-payment-modal')" class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-emerald-700">Record payment</button>
                            <button type="button" @click="$dispatch('open-refund-modal')" class="rounded-2xl bg-rose-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-rose-700">Issue refund</button>
                            <button type="button" @click="$dispatch('open-credit-modal')" class="rounded-2xl bg-sky-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-sky-700">Issue credit</button>
                        <?php else: ?>
                            <span class="rounded-2xl bg-amber-100 px-4 py-2 text-sm font-black text-amber-800 ring-1 ring-amber-200">Financial actions disabled</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <button type="button" data-copy-value="<?php echo e(e($copyPaymentDetails)); ?>" class="rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-black text-indigo-700 hover:bg-indigo-100">Copy payment details</button>
                        <a href="<?php echo e(route('money-desk.orders.show', $order->id)); ?>" class="rounded-2xl border border-emerald-200 bg-white px-4 py-2 text-sm font-black text-emerald-700 hover:bg-emerald-50">Money Desk ↗</a>
                    </div>

                    <p class="mt-3 rounded-2xl bg-slate-50 px-4 py-3 text-xs font-semibold leading-5 text-slate-500">
                        Payment default is <span class="font-black text-slate-800">Online Payment Link (Card)</span>, matching Dabba's normal payment flow.
                    </p>
                </section>

                <section class="rounded-3xl border <?php echo e($walletAvailable > 0.004 ? 'border-sky-200 bg-sky-50' : 'border-slate-200 bg-white'); ?> p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] <?php echo e($walletAvailable > 0.004 ? 'text-sky-700' : 'text-slate-400'); ?>">Customer wallet</p>
                            <h2 class="mt-1 text-lg font-black text-slate-950">Does the customer have credit?</h2>
                            <p class="mt-1 text-sm <?php echo e($walletAvailable > 0.004 ? 'text-sky-900' : 'text-slate-500'); ?>">Shows reusable customer-owned balance, not ordinary payments already consumed by this order.</p>
                        </div>
                        <span class="rounded-full <?php echo e($walletAvailable > 0.004 ? 'bg-sky-100 text-sky-700 ring-sky-200' : 'bg-slate-100 text-slate-500 ring-slate-200'); ?> px-3 py-1 text-xs font-black uppercase tracking-wide ring-1">
                            <?php echo e($walletAvailable > 0.004 ? 'Credit available' : 'No wallet credit'); ?>

                        </span>
                    </div>

                    <div class="mt-5 rounded-3xl bg-white/80 p-4 ring-1 ring-black/5">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Wallet available</p>
                        <p class="mt-1 text-3xl font-black <?php echo e($walletAvailable > 0.004 ? 'text-sky-700' : 'text-slate-400'); ?>">£<?php echo e(number_format($walletAvailable, 2)); ?></p>
                        <p class="mt-1 text-xs font-semibold text-slate-500">Total open wallet balance for this customer.</p>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-white/80 px-4 py-3 ring-1 ring-black/5">
                            <p class="text-[10px] font-black uppercase tracking-wide text-violet-700">From order amendments</p>
                            <p class="mt-1 text-lg font-black text-violet-700">£<?php echo e(number_format($walletCreditFromRevisions, 2)); ?></p>
                        </div>
                        <div class="rounded-2xl bg-white/80 px-4 py-3 ring-1 ring-black/5">
                            <p class="text-[10px] font-black uppercase tracking-wide text-amber-700">From overpayments</p>
                            <p class="mt-1 text-lg font-black text-amber-700">£<?php echo e(number_format($walletCreditFromOverpayments, 2)); ?></p>
                        </div>
                    </div>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($walletAttentionTotal > 0.004): ?>
                        <div class="mt-4 rounded-2xl border border-sky-200 bg-white px-4 py-3 text-sm font-semibold leading-6 text-sky-950">
                            <span class="font-black">Staff action:</span>
                            Tell the customer they have <span class="font-black">£<?php echo e(number_format($walletAttentionTotal, 2)); ?></span> wallet credit generated from <?php echo e($walletAttentionSources->isNotEmpty() ? $walletAttentionSources->implode(' and ') : 'this order history'); ?>.
                        </div>
                    <?php else: ?>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold leading-6 text-slate-500">
                            No amendment or overpayment credit needs staff attention for this order.
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </section>
            </div>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Payments</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">Every payment can be reversed</h2>
                        <p class="mt-1 text-sm text-slate-500">Each recorded payment has its own reverse action, so later part-payments are handled the same way as the original payment.</p>
                    </div>
                    <span class="rounded-full <?php echo e($reversiblePaymentCount > 0 ? 'bg-rose-50 text-rose-700 ring-rose-200' : 'bg-slate-100 text-slate-500 ring-slate-200'); ?> px-3 py-1 text-xs font-black uppercase tracking-wide ring-1">
                        <?php echo e($reversiblePaymentCount); ?> reversible
                    </span>
                </div>

                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                    <div class="hidden grid-cols-[1fr_1fr_1fr_1fr_auto] gap-3 bg-slate-50 px-4 py-3 text-[10px] font-black uppercase tracking-wide text-slate-400 md:grid">
                        <span>Date</span>
                        <span>Method</span>
                        <span>Reference</span>
                        <span class="text-right">Amount</span>
                        <span class="text-right">Action</span>
                    </div>
                    <div class="divide-y divide-slate-200">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $paymentRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $paymentRow): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <?php
                                $paymentAmount = (float) ($paymentRow->amount ?? 0);
                                $paymentDate = ($paymentRow->received_at ?: $paymentRow->created_at) ? \Carbon\Carbon::parse($paymentRow->received_at ?: $paymentRow->created_at)->format('d M Y H:i') : 'No date';
                                $paymentMethodLabel = $paymentRow->payment_type_name ?: Str::of((string) ($paymentRow->method ?: 'Payment'))->replace('_', ' ')->title();
                                $paymentReferenceLabel = $paymentRow->reference ?: ($paymentRow->provider ?: 'No reference');
                                $paymentReverseLabel = '£' . number_format($paymentAmount, 2) . ' · ' . $paymentMethodLabel . ' · ' . $paymentDate;
                                $paymentCanReverse = ($paymentRow->status ?? '') === 'recorded' && empty($paymentRow->has_void);
                                $paymentReverseRoute = ($paymentRow->source_table ?? '') === 'customer_ledger_entry'
                                    ? route('orders.ledger-payments.void', [$order->id, $paymentRow->id])
                                    : route('orders.payments.void', [$order->id, $paymentRow->id]);
                                $paymentStatusText = ($paymentRow->type ?? '') === 'ledger_payment' ? 'Overpayment / wallet' : 'Recorded';
                            ?>
                            <div class="grid gap-3 px-4 py-3 text-sm md:grid-cols-[1fr_1fr_1fr_1fr_auto] md:items-center">
                                <div>
                                    <p class="font-black text-slate-900"><?php echo e($paymentDate); ?></p>
                                    <p class="mt-0.5 text-[11px] font-semibold text-slate-400 md:hidden">Date</p>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-700"><?php echo e($paymentMethodLabel); ?></p>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paymentRow->provider): ?>
                                        <p class="mt-0.5 text-[11px] font-semibold text-slate-400"><?php echo e($paymentRow->provider); ?></p>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-600"><?php echo e($paymentReferenceLabel); ?></p>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paymentRow->note): ?>
                                        <p class="mt-0.5 line-clamp-1 text-[11px] font-semibold text-slate-400"><?php echo e($paymentRow->note); ?></p>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div class="md:text-right">
                                    <p class="font-black text-emerald-700">£<?php echo e(number_format($paymentAmount, 2)); ?></p>
                                    <p class="mt-0.5 text-[11px] font-black uppercase tracking-wide <?php echo e($paymentCanReverse ? 'text-emerald-600' : 'text-slate-400'); ?>"><?php echo e($paymentCanReverse ? $paymentStatusText : 'Reversed'); ?></p>
                                </div>
                                <div class="md:text-right">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paymentCanReverse && ! $isHistoricalRevision): ?>
                                        <button type="button" @click="$dispatch('open-reverse-payment-modal', { action: '<?php echo e($paymentReverseRoute); ?>', label: '<?php echo e(addslashes($paymentReverseLabel)); ?>' })" class="rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-black text-rose-700 hover:bg-rose-50">Reverse</button>
                                    <?php else: ?>
                                        <span class="rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-400">Reversed</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <div class="px-4 py-8 text-center text-sm font-semibold text-slate-500">No payments recorded yet.</div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </section>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Invoice</p>
                            <h2 class="mt-1 text-lg font-black text-slate-950">Invoice snapshot</h2>
                            <p class="mt-1 text-sm text-slate-500">Invoice PDFs are historical outputs from this order snapshot.</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-black uppercase tracking-wide ring-1 <?php echo e($invoiceStatusClasses); ?>"><?php echo e($invoiceStatusLabel); ?></span>
                    </div>

                    <div class="mt-4 rounded-3xl border <?php echo e($hasInvoiceWorkspace ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'); ?> p-4">
                        <p class="text-xs font-black uppercase tracking-[0.2em] <?php echo e($hasInvoiceWorkspace ? 'text-emerald-700' : 'text-amber-700'); ?>"><?php echo e($hasInvoiceWorkspace ? 'Current invoice' : 'Invoice needed'); ?></p>
                        <h3 class="mt-1 text-base font-black text-slate-950">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasInvoiceWorkspace): ?>
                                Invoice #<?php echo e($invoiceNumber); ?> · version <?php echo e($latestInvoiceVersion->version); ?>

                            <?php else: ?>
                                No invoice created yet
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </h3>
                        <p class="mt-1 text-xs font-semibold <?php echo e($hasInvoiceWorkspace ? 'text-emerald-900' : 'text-amber-900'); ?>">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasInvoiceWorkspace): ?>
                                Issued <?php echo e($latestInvoiceVersion->issued_at ? \Carbon\Carbon::parse($latestInvoiceVersion->issued_at)->format('d M Y H:i') : 'date unknown'); ?> · Total £<?php echo e(number_format((float) $latestInvoiceVersion->grand_total, 2)); ?>

                            <?php else: ?>
                                Create the first invoice snapshot from this order when ready.
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </p>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasInvoiceWorkspace): ?>
                            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                <div class="rounded-2xl bg-white p-3 ring-1 ring-black/5">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Items</p>
                                    <p class="mt-1 text-sm font-black text-slate-900">£<?php echo e(number_format((float) $latestInvoiceVersion->items_subtotal, 2)); ?></p>
                                </div>
                                <div class="rounded-2xl bg-white p-3 ring-1 ring-black/5">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Delivery + fee</p>
                                    <p class="mt-1 text-sm font-black text-slate-900">£<?php echo e(number_format((float) $latestInvoiceVersion->delivery_total + (float) $latestInvoiceVersion->dabba_fee_total, 2)); ?></p>
                                </div>
                                <div class="rounded-2xl bg-white p-3 ring-1 ring-black/5">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Grand total</p>
                                    <p class="mt-1 text-sm font-black text-slate-900">£<?php echo e(number_format((float) $latestInvoiceVersion->grand_total, 2)); ?></p>
                                </div>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $isHistoricalRevision): ?>
                                <button type="button" @click="$dispatch('open-invoice-modal')" class="rounded-2xl <?php echo e($hasInvoiceWorkspace ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-amber-600 hover:bg-amber-700'); ?> px-4 py-2 text-sm font-black text-white shadow-sm"><?php echo e($hasInvoiceWorkspace ? 'Create invoice version' : 'Create invoice'); ?></button>
                            <?php else: ?>
                                <span class="rounded-2xl bg-amber-100 px-4 py-2 text-sm font-black text-amber-800 ring-1 ring-amber-200">Read-only snapshot</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasInvoiceWorkspace && ! empty($invoiceRoot->pdf_path)): ?>
                                <a href="<?php echo e(asset('storage/' . ltrim($invoiceRoot->pdf_path, '/'))); ?>" target="_blank" rel="noopener noreferrer" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">View / download ↗</a>
                            <?php else: ?>
                                <button type="button" disabled class="cursor-not-allowed rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-400">PDF next</button>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <button type="button" disabled class="cursor-not-allowed rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-400">Send next</button>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Invoice history</p>
                            <h2 class="mt-1 text-lg font-black text-slate-950">Invoice versions</h2>
                            <p class="mt-1 text-sm text-slate-500">Previous invoice snapshots for this order.</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600"><?php echo e($invoiceVersions->count()); ?> version<?php echo e($invoiceVersions->count() === 1 ? '' : 's'); ?></span>
                    </div>
                    <div class="mt-4 space-y-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $invoiceVersions->take(5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $version): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div>
                                    <p class="text-sm font-black text-slate-950">Invoice #<?php echo e($invoiceNumber); ?> · version <?php echo e($version->version); ?></p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500"><?php echo e($version->issued_at ? \Carbon\Carbon::parse($version->issued_at)->format('d M Y H:i') : 'Issued date unknown'); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($version->issued_by_name)): ?> · <?php echo e($version->issued_by_name); ?> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-black text-slate-950">£<?php echo e(number_format((float) $version->grand_total, 2)); ?></p>
                                    <p class="mt-1 text-xs font-black uppercase tracking-wide text-emerald-700"><?php echo e(Str::of((string) $version->status)->replace('_', ' ')->title()); ?></p>
                                </div>
                            </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm font-semibold text-slate-500">No invoice versions yet.</div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </section>
            </div>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Money story</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">What happened over time?</h2>
                        <p class="mt-1 text-sm text-slate-500">Payments, wallet use, refunds and reversals affecting this order.</p>
                    </div>
                    <a href="<?php echo e(route('money-desk.orders.show', $order->id)); ?>" class="rounded-2xl bg-emerald-50 px-4 py-2 text-xs font-black text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-100">Finance detail ↗</a>
                </div>

                <div class="mt-4 space-y-2">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $paymentTimeline->take(10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php
                            $eventType = (string) ($event->type ?? 'event');
                            $eventLabel = match ($eventType) {
                                'payment' => 'Payment received',
                                'payment_void' => 'Payment reversed',
                                'credit_application' => 'Wallet credit used',
                                'credit_application_void' => 'Wallet use reversed',
                                'refund' => 'Refund recorded',
                                'refund_void' => 'Refund reversed',
                                default => Str::of($eventType)->replace('_', ' ')->title(),
                            };
                            $eventAmount = (float) ($event->amount ?? 0);
                            $eventAmountClasses = $eventAmount < 0 ? 'text-rose-700' : ($eventType === 'credit_application' ? 'text-sky-700' : 'text-emerald-700');
                        ?>
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-700 ring-1 ring-slate-200"><?php echo e($eventLabel); ?></span>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($event->payment_type_name): ?>
                                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700 ring-1 ring-indigo-100"><?php echo e($event->payment_type_name); ?></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($event->status): ?>
                                        <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-bold text-slate-500"><?php echo e($event->status); ?></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <p class="mt-1 truncate text-xs font-semibold text-slate-500">
                                    <?php echo e($event->reference ?: ($event->method ?: 'No reference')); ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($event->provider): ?> · <?php echo e($event->provider); ?> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </p>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($event->note): ?>
                                    <p class="mt-1 line-clamp-1 text-xs text-slate-500"><?php echo e($event->note); ?></p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black <?php echo e($eventAmountClasses); ?>">£<?php echo e(number_format($eventAmount, 2)); ?></p>
                                <p class="mt-0.5 text-[11px] font-semibold text-slate-400"><?php echo e(($event->received_at ?: $event->created_at) ? \Carbon\Carbon::parse($event->received_at ?: $event->created_at)->format('d M Y H:i') : 'No date'); ?></p>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($event->type ?? '') === 'payment' && ($event->status ?? '') === 'recorded' && empty($event->has_void) && ! $isHistoricalRevision): ?>
                                    <button type="button" @click="$dispatch('open-reverse-payment-modal', { action: '<?php echo e(route('orders.payments.void', [$order->id, $event->id])); ?>', label: '£<?php echo e(number_format((float) $event->amount, 2)); ?> · <?php echo e(addslashes($event->payment_type_name ?: 'Payment')); ?>' })" class="mt-2 rounded-xl border border-rose-200 bg-white px-3 py-1 text-[11px] font-black text-rose-700 hover:bg-rose-50">Reverse</button>
                                <?php elseif(($event->type ?? '') === 'payment' && ! empty($event->has_void)): ?>
                                    <p class="mt-2 text-[11px] font-black uppercase tracking-wide text-slate-400">Reversed</p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm font-semibold text-slate-500">No finance events recorded yet.</div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </section>
        </div>
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/orders/show/tabs/_finance.blade.php ENDPATH**/ ?>