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



        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isHistoricalRevision): ?>
            <section class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-700">Viewing historical revision</p>
                        <h2 class="mt-1 text-lg font-black text-amber-950">Revision <?php echo e($revisionNumber); ?> is superseded and read-only.</h2>
                        <p class="mt-1 text-sm font-semibold text-amber-900">Financial and operational actions are disabled for this snapshot. Open the active revision to record payments, refunds, invoices, purchases or other changes.</p>
                    </div>
                    <a href="<?php echo e($activeRevisionUrl); ?>" class="inline-flex items-center justify-center rounded-2xl bg-amber-700 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-amber-800">View active revision</a>
                </div>
            </section>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-2">
                <a href="<?php echo e(route('orders.index')); ?>" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">← Back to Orders</a>

                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-950">Order #<?php echo e($order->order_number); ?></h1>
                    <button type="button" data-copy-value="<?php echo e($copyOrderNumber); ?>" title="Copy order number" aria-label="Copy order number" class="copy-btn inline-flex h-7 w-7 items-center justify-center rounded-full border border-indigo-200 bg-white text-sm text-indigo-700 hover:bg-indigo-50">📋</button>
                </div>

                <p class="text-lg font-semibold text-slate-800"><?php echo e($customerFullName ?: 'Unknown customer'); ?></p>

                <p class="text-sm font-semibold <?php echo e($balanceDue > 0.004 ? 'text-rose-700' : 'text-emerald-700'); ?>">
                    <?php echo e($paymentStatusLabel); ?>

                    <span class="text-slate-300">•</span>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($balanceDue > 0.004): ?>
                        Outstanding £<?php echo e(number_format($balanceDue, 2)); ?>

                    <?php else: ?>
                        No outstanding balance
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </p>
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