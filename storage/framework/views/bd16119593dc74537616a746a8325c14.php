        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($revisionHistory ?? collect())->count() > 1): ?>
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Revision history</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">Revision History (<?php echo e(($revisionHistory ?? collect())->count()); ?>)</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Audit trail</span>
                </div>

                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $revisionHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $revision): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php
                            $isCurrentRevision = (int) $revision->id === (int) $order->id;
                            $isSupersededRevision = ($revision->revision_state ?? '') === 'superseded';
                            $statusLabel = $isCurrentRevision ? 'Viewing now' : ($isSupersededRevision ? 'Superseded' : 'Current');
                            $statusClasses = $isCurrentRevision ? 'bg-emerald-100 text-emerald-700' : ($isSupersededRevision ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600');
                        ?>
                        <div class="grid gap-3 border-b border-slate-100 px-4 py-3 last:border-b-0 md:grid-cols-[90px_130px_1fr_120px_auto] md:items-center">
                            <div class="font-black text-slate-950">Rev <?php echo e($revision->revision_number); ?></div>

                            <div>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-black <?php echo e($statusClasses); ?>"><?php echo e($statusLabel); ?></span>
                            </div>

                            <div class="text-sm font-semibold text-slate-600">
                                <?php echo e($revision->created_at ? \Carbon\Carbon::parse($revision->created_at)->format('d M Y H:i') : 'Date unknown'); ?>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($revision->revision_note)): ?>
                                    <p class="mt-1 line-clamp-1 text-xs font-normal text-slate-400"><?php echo e($revision->revision_note); ?></p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <div class="text-sm font-black text-slate-950 md:text-right">£<?php echo e(number_format($revision->grand_total ?? 0, 2)); ?></div>

                            <div class="flex flex-wrap gap-2 md:justify-end">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $isCurrentRevision): ?>
                                    <a href="<?php echo e(route('orders.show', $revision->id)); ?>" class="rounded-xl bg-white px-3 py-2 text-xs font-bold text-slate-700 ring-1 ring-slate-200 hover:bg-indigo-50 hover:text-indigo-700">Snapshot</a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($revision->draft_order_id)): ?>
                                    <a href="<?php echo e(route('draft-orders.show', $revision->draft_order_id)); ?>" class="rounded-xl bg-white px-3 py-2 text-xs font-bold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100">View Draft</a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/orders/show/_revision_history.blade.php ENDPATH**/ ?>