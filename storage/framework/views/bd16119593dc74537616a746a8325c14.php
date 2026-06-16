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
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/orders/show/_revision_history.blade.php ENDPATH**/ ?>