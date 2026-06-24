        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $isHistoricalRevision): ?>
        <div x-data="{ invoiceOpen: false }" @open-invoice-modal.window="invoiceOpen = true" x-cloak x-show="invoiceOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-indigo-950/45 p-4">
            <div @click.away="invoiceOpen = false" class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-700">Invoice workspace</p>
                        <h3 class="mt-1 text-xl font-black text-slate-950"><?php echo e($hasInvoiceWorkspace ? 'Create invoice version' : 'Create invoice'); ?></h3>
                        <p class="mt-1 text-sm font-semibold text-slate-500">This creates an issued invoice snapshot from the current order totals. PDF/email sending comes next.</p>
                    </div>
                    <button type="button" @click="invoiceOpen = false" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-xl font-black text-slate-500 hover:bg-slate-50">×</button>
                </div>

                <form method="POST" action="<?php echo e(route('orders.invoices.store', $order->id)); ?>" class="mt-5 space-y-4">
                    <?php echo csrf_field(); ?>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Invoice no.</p>
                            <p class="mt-1 text-sm font-black text-slate-950"><?php echo e($invoiceNumber); ?></p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Next version</p>
                            <p class="mt-1 text-sm font-black text-slate-950">v<?php echo e(($invoiceVersions->max('version') ?? 0) + 1); ?></p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Total</p>
                            <p class="mt-1 text-sm font-black text-slate-950">£<?php echo e(number_format($orderTotal, 2)); ?></p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold leading-6 text-amber-950">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasInvoiceWorkspace): ?>
                            A previous invoice version already exists. Creating a new version preserves the older snapshot for history.
                        <?php else: ?>
                            This will mark the order as invoiced and create the first invoice version. It will not generate or send a PDF yet.
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <div>
                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Customer note for invoice snapshot</label>
                        <textarea name="customer_note" rows="3" maxlength="2000" placeholder="Optional note to carry on the invoice snapshot…" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-amber-500 focus:ring-amber-500"><?php echo e(old('customer_note')); ?></textarea>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" @click="invoiceOpen = false" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="rounded-2xl bg-amber-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-amber-700"><?php echo e($hasInvoiceWorkspace ? 'Create new version' : 'Create invoice'); ?></button>
                    </div>
                </form>
            </div>
        </div>


        <div x-data="{ paymentOpen: false }" @open-payment-modal.window="paymentOpen = true" x-cloak x-show="paymentOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-indigo-950/45 p-4">
            <div @click.away="paymentOpen = false" class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-700">Record payment</p>
                        <h3 class="mt-1 text-xl font-black text-slate-950">Order #<?php echo e($order->order_number); ?></h3>
                        <p class="mt-1 text-sm font-semibold text-slate-500">Outstanding balance: £<?php echo e(number_format($balanceDue, 2)); ?></p>
                    </div>
                    <button type="button" @click="paymentOpen = false" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-xl font-black text-slate-500 hover:bg-slate-50">×</button>
                </div>

                <form method="POST" action="<?php echo e(route('orders.payments.store', $order->id)); ?>" class="mt-5 space-y-4" x-data="{ amount: '<?php echo e(old('amount', $balanceDue > 0 ? number_format($balanceDue, 2, '.', '') : '')); ?>', balanceDue: <?php echo e(json_encode($balanceDue)); ?>, overpaymentConfirm: false, overpaymentConfirmed: false, get numericAmount() { const value = parseFloat(this.amount || '0'); return Number.isFinite(value) ? value : 0; }, get appliedAmount() { return Math.min(this.numericAmount, this.balanceDue); }, get overpaymentAmount() { return Math.max(0, this.numericAmount - this.balanceDue); }, money(value) { return '£' + Number(value || 0).toFixed(2); }, submitPayment(form) { if (this.overpaymentAmount > 0 && ! this.overpaymentConfirmed) { this.overpaymentConfirm = true; return; } form.submit(); } }" @submit.prevent="submitPayment($el)">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="confirmed_overpayment" :value="overpaymentConfirmed ? 1 : 0">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-slate-500">Amount</label>
                            <input name="amount" x-model="amount" type="number" step="0.01" min="0.01" required class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-slate-500">Received date</label>
                            <input name="received_at" type="datetime-local" value="<?php echo e(old('received_at', now()->format('Y-m-d\\TH:i'))); ?>" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Payment type</label>
                        <select name="payment_type" required class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 focus:border-emerald-500 focus:ring-emerald-500">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $paymentTypeOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $paymentTypeOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($paymentTypeOption); ?>" <?php if(old('payment_type', 'Online Payment Link (Card)') === $paymentTypeOption): echo 'selected'; endif; ?>><?php echo e($paymentTypeOption); ?><?php echo e($loop->first ? ' · most common' : ''); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Reference</label>
                        <input name="reference" value="<?php echo e(old('reference')); ?>" placeholder="Gateway transaction ID, BACS ref, receipt number…" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Notes</label>
                        <textarea name="note" rows="3" maxlength="255" placeholder="Optional internal note…" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:ring-emerald-500"><?php echo e(old('note')); ?></textarea>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold leading-5 text-slate-600">
                        <template x-if="numericAmount <= 0">
                            <span>Enter the payment amount received from the customer.</span>
                        </template>
                        <template x-if="numericAmount > 0 && overpaymentAmount <= 0">
                            <span><strong x-text="money(appliedAmount)"></strong> will be applied to this order.</span>
                        </template>
                        <template x-if="overpaymentAmount > 0">
                            <span class="text-amber-900">
                                <strong>Overpayment warning:</strong>
                                <span x-text="money(appliedAmount)"></span> will settle this order and
                                <span class="font-black" x-text="money(overpaymentAmount)"></span>
                                will be moved to the customer wallet.
                            </span>
                        </template>
                    </div>

                    <div x-cloak x-show="overpaymentConfirm" x-transition.opacity class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-700">Overpayment confirmation required</p>
                        <h4 class="mt-1 text-base font-black text-slate-950">This payment is more than the outstanding balance.</h4>
                        <div class="mt-3 grid gap-2 sm:grid-cols-3">
                            <div class="rounded-xl bg-white px-3 py-2 ring-1 ring-amber-100">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Outstanding</p>
                                <p class="font-black" x-text="money(balanceDue)"></p>
                            </div>
                            <div class="rounded-xl bg-white px-3 py-2 ring-1 ring-amber-100">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Applied to order</p>
                                <p class="font-black text-emerald-700" x-text="money(appliedAmount)"></p>
                            </div>
                            <div class="rounded-xl bg-white px-3 py-2 ring-1 ring-amber-100">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">To customer wallet</p>
                                <p class="font-black text-sky-700" x-text="money(overpaymentAmount)"></p>
                            </div>
                        </div>
                        <p class="mt-3 font-semibold">Are you sure you want to record this overpayment and add the surplus to the customer's wallet?</p>
                        <div class="mt-4 flex flex-wrap justify-end gap-3">
                            <button type="button" @click="overpaymentConfirm = false" class="rounded-xl border border-amber-200 bg-white px-4 py-2 text-xs font-black text-amber-800 hover:bg-amber-100">Go back</button>
                            <button type="button" @click="overpaymentConfirmed = true; overpaymentConfirm = false; $nextTick(() => $el.closest('form').submit())" class="rounded-xl bg-amber-600 px-4 py-2 text-xs font-black text-white hover:bg-amber-700">Yes, record overpayment</button>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" @click="paymentOpen = false" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-emerald-700"><span x-text="overpaymentAmount > 0 ? 'Review overpayment' : 'Save payment'"></span></button>
                    </div>
                </form>
            </div>
        </div>


        <div x-data="{ refundOpen: false }" @open-refund-modal.window="refundOpen = true" x-cloak x-show="refundOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-indigo-950/45 p-4">
            <div @click.away="refundOpen = false" class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-rose-700">Issue refund</p>
                        <h3 class="mt-1 text-xl font-black text-slate-950">Refund money back to the customer</h3>
                        <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">This records a refund, updates the order balance and keeps a finance audit trail.</p>
                    </div>
                    <button type="button" @click="refundOpen = false" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-xl font-black text-slate-500 hover:bg-slate-50">×</button>
                </div>
                <form method="POST" action="<?php echo e(route('orders.refunds.store', $order->id)); ?>" class="mt-5 space-y-4">
                    <?php echo csrf_field(); ?>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-slate-500">Amount</label>
                            <input name="amount" type="number" min="0.01" max="999999.99" step="0.01" required placeholder="0.00" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 focus:border-rose-500 focus:ring-rose-500">
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-slate-500">Method</label>
                            <select name="refund_method" required class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 focus:border-rose-500 focus:ring-rose-500">
                                <option>Online Payment Link (Card)</option>
                                <option>Card (Office)</option>
                                <option>Bank Transfer (BACS)</option>
                                <option>Cash</option>
                                <option>PayPal</option>
                                <option>Other</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Reference</label>
                        <input name="reference" maxlength="100" placeholder="Gateway, bank or cash reference" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-rose-500 focus:ring-rose-500">
                    </div>
                    <div>
                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Reason</label>
                        <textarea name="reason" rows="3" maxlength="255" required placeholder="Why is this refund being issued?" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-rose-500 focus:ring-rose-500"></textarea>
                    </div>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" @click="refundOpen = false" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="rounded-2xl bg-rose-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-rose-700">Issue refund</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-data="{ creditOpen: false }" @open-credit-modal.window="creditOpen = true" x-cloak x-show="creditOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-indigo-950/45 p-4">
            <div @click.away="creditOpen = false" class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-sky-700">Issue credit</p>
                        <h3 class="mt-1 text-xl font-black text-slate-950">Add credit to the customer wallet</h3>
                        <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">This creates reusable customer credit and records it against this order.</p>
                    </div>
                    <button type="button" @click="creditOpen = false" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-xl font-black text-slate-500 hover:bg-slate-50">×</button>
                </div>
                <form method="POST" action="<?php echo e(route('orders.credits.store', $order->id)); ?>" class="mt-5 space-y-4">
                    <?php echo csrf_field(); ?>
                    <div>
                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Amount</label>
                        <input name="amount" type="number" min="0.01" max="999999.99" step="0.01" required placeholder="0.00" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Reason</label>
                        <textarea name="reason" rows="3" maxlength="255" required placeholder="Why is this customer credit being issued?" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-sky-500 focus:ring-sky-500"></textarea>
                    </div>
                    <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-950">
                        The customer can use this credit on a future order. The order finance timeline will show it as credit issued.
                    </div>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" @click="creditOpen = false" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="rounded-2xl bg-sky-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-sky-700">Issue credit</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-data="{ voidPaymentAction: null, reversePaymentLabel: null }" @open-reverse-payment-modal.window="voidPaymentAction = $event.detail.action; reversePaymentLabel = $event.detail.label" x-cloak x-show="voidPaymentAction" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-indigo-950/45 p-4">
            <div @click.away="voidPaymentAction = null" class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-rose-700">Reverse payment</p>
                        <h3 class="mt-1 text-xl font-black text-slate-950">Reverse this payment?</h3>
                        <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">DabbaDesk will not delete history. It will record reversal rows, update the order balance, and void unused overpayment wallet credit created by this payment.</p>
                    </div>
                    <button type="button" @click="voidPaymentAction = null" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-xl font-black text-slate-500 hover:bg-slate-50">×</button>
                </div>

                <form method="POST" :action="voidPaymentAction" class="mt-5 space-y-4">
                    <?php echo csrf_field(); ?>
                    <div class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-900" x-show="reversePaymentLabel">
                        Reversing: <span x-text="reversePaymentLabel"></span>
                    </div>
                    <div>
                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Reason</label>
                        <select name="reversal_reason" required class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 focus:border-rose-500 focus:ring-rose-500">
                            <option value="">Choose reason…</option>
                            <option value="Wrong order">Wrong order</option>
                            <option value="Wrong customer">Wrong customer</option>
                            <option value="Duplicate payment">Duplicate payment</option>
                            <option value="Data entry error">Data entry error</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Notes</label>
                        <textarea name="reversal_note" rows="3" maxlength="255" placeholder="Optional explanation for the audit trail…" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-rose-500 focus:ring-rose-500"></textarea>
                    </div>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" @click="voidPaymentAction = null; reversePaymentLabel = null" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">Keep payment</button>
                        <button type="submit" class="rounded-2xl bg-rose-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-rose-700">Reverse payment</button>
                    </div>
                </form>
            </div>
        </div>

        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/orders/show/_modals.blade.php ENDPATH**/ ?>