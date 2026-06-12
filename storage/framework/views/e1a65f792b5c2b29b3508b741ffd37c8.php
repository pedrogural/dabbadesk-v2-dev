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

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="min-w-0">
                    <a href="<?php echo e(route('orders.index')); ?>" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">← Back to Orders</a>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight text-slate-950">Order #<?php echo e($order->order_number); ?></h1>
                        <button type="button" data-copy-value="<?php echo e($copyOrderNumber); ?>" class="copy-btn rounded-full border border-indigo-200 bg-white px-3 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Copy</button>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 <?php echo e($revisionBadgeClasses); ?>"><?php echo e($revisionBadgeLabel); ?></span>
                        <span class="rounded-full <?php echo e($isCustomerSelfPurchase ? 'bg-sky-100 text-sky-700' : 'bg-indigo-100 text-indigo-700'); ?> px-2.5 py-1 text-xs font-semibold">
                            <?php echo e($isCustomerSelfPurchase ? 'Self-purchase' : 'Dabba purchase'); ?>

                        </span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 <?php echo e($paymentStatusClasses); ?>"><?php echo e($paymentStatusLabel); ?></span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($walletAttentionTotal > 0.004): ?>
                            <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-200">Wallet credit £<?php echo e(number_format($walletAttentionTotal, 2)); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($balanceDue > 0.004): ?>
                            <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-200">Outstanding £<?php echo e(number_format($balanceDue, 2)); ?></span>
                        <?php else: ?>
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">No outstanding balance</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <p class="mt-1 text-sm text-slate-500"><?php echo e($customerFullName ?: 'Unknown customer'); ?> · Total £<?php echo e(number_format($orderTotal, 2)); ?> · Settled £<?php echo e(number_format($settledTotal, 2)); ?></p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="$dispatch('open-payment-modal')" class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">Record payment</button>
                    <button type="button" @click="$dispatch('open-invoice-modal')" class="rounded-2xl <?php echo e($hasInvoiceWorkspace ? 'bg-slate-900 hover:bg-slate-800' : 'bg-amber-600 hover:bg-amber-700'); ?> px-4 py-2 text-sm font-semibold text-white shadow-sm"><?php echo e($hasInvoiceWorkspace ? 'New invoice version' : 'Create invoice'); ?></button>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 rounded-2xl bg-slate-50 p-2 ring-1 ring-slate-100">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($whatsappUrl): ?>
                    <a href="<?php echo e($whatsappUrl); ?>" target="_blank" rel="noopener noreferrer" class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100 hover:bg-emerald-100">WhatsApp ↗</a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customerEmail): ?>
                    <a href="mailto:<?php echo e($customerEmail); ?>" class="rounded-xl bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-100">Email ↗</a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($copyFullAddress): ?>
                    <button type="button" data-copy-value="<?php echo e($copyFullAddress); ?>" class="copy-btn rounded-xl border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Copy address</button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <button type="button" data-copy-value="<?php echo e($copyOrderNumber); ?>" class="copy-btn rounded-xl border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Copy order #</button>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($order->customer_id)): ?>
                    <a href="<?php echo e(route('customers.edit', $order->customer_id)); ?>" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-indigo-100 hover:text-indigo-700">Customer ↗</a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <a href="<?php echo e(route('money-desk.orders.show', $order->id)); ?>" class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100 hover:bg-emerald-100">Finance ↗</a>
            </div>

            <div class="mt-5 overflow-x-auto">
                <div class="inline-flex min-w-full gap-2 rounded-2xl bg-slate-100 p-1">
                    <button type="button" @click="tab = 'overview'" :class="tab === 'overview' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'" class="rounded-xl px-4 py-2 text-sm font-semibold">Overview</button>
                    <button type="button" @click="tab = 'items'" :class="tab === 'items' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'" class="rounded-xl px-4 py-2 text-sm font-semibold">Items</button>
                    <button type="button" @click="tab = 'purchase_status'" :class="tab === 'purchase_status' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'" class="rounded-xl px-4 py-2 text-sm font-semibold">Purchase Status</button>
                    <button type="button" @click="tab = 'finance'" :class="tab === 'finance' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'" class="rounded-xl px-4 py-2 text-sm font-semibold">Finance</button>
                    <button type="button" @click="tab = 'notes'" :class="tab === 'notes' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'" class="rounded-xl px-4 py-2 text-sm font-semibold">Communication & History</button>
                </div>
            </div>
        </section>
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/orders/show/_page_header.blade.php ENDPATH**/ ?>