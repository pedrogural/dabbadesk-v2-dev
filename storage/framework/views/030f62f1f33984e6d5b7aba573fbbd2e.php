<div class="overflow-hidden rounded-2xl border border-slate-200">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-400">
            <tr>
                <th class="px-4 py-3">Item</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Qty</th>
                <th class="px-4 py-3">Reference</th>
                <th class="px-4 py-3">Dates</th>
                <th class="px-4 py-3">Notes</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $purchaseEvents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $purchase): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php
                    $purchaseStatus = (string) ($purchase->status ?? '');
                    $isGood = in_array($purchaseStatus, ['purchased', 'ordered', 'received'], true);
                    $isProblem = in_array($purchaseStatus, ['failed', 'problem', 'supplier_cancelled', 'cancelled', 'unfulfilled', 'unavailable', 'lost', 'damaged', 'wrong_item'], true);
                ?>
                <tr>
                    <td class="px-4 py-3">
                        <p class="font-black text-slate-900"><?php echo e(\Illuminate\Support\Str::limit($purchase->item_name ?? 'Item', 80)); ?></p>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($purchase->marketplace_seller)): ?>
                            <p class="mt-1 text-xs font-semibold text-slate-500">Seller: <?php echo e($purchase->marketplace_seller); ?></p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 <?php echo e($isGood ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : ($isProblem ? 'bg-rose-50 text-rose-700 ring-rose-100' : 'bg-slate-50 text-slate-600 ring-slate-100')); ?>">
                            <?php echo e(\Illuminate\Support\Str::of($purchaseStatus ?: 'pending')->replace('_', ' ')->title()); ?>

                        </span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($purchase->problem_code)): ?>
                            <p class="mt-1 text-xs font-bold text-rose-700"><?php echo e(\Illuminate\Support\Str::of($purchase->problem_code)->replace('_', ' ')->title()); ?></p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                    <td class="px-4 py-3 font-black text-slate-700"><?php echo e((int) ($purchase->qty ?? 0)); ?></td>
                    <td class="px-4 py-3">
                        <p class="font-semibold text-slate-700"><?php echo e($purchase->retailer_order_reference ?: '—'); ?></p>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($purchase->retailer_order_reference)): ?>
                            <button type="button" data-copy-value="<?php echo e($purchase->retailer_order_reference); ?>" class="mt-1 text-xs font-black text-indigo-600 hover:text-indigo-700">Copy</button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-xs font-semibold text-slate-500">
                        <p>Ordered: <?php echo e(! empty($purchase->ordered_at) ? \Carbon\Carbon::parse($purchase->ordered_at)->format('d M Y') : '—'); ?></p>
                        <p>UK hub: <?php echo e(! empty($purchase->expected_uk_hub_at) ? \Carbon\Carbon::parse($purchase->expected_uk_hub_at)->format('d M Y') : '—'); ?></p>
                    </td>
                    <td class="px-4 py-3 text-xs font-semibold text-slate-500">
                        <?php echo e(\Illuminate\Support\Str::limit($purchase->problem_notes ?: ($purchase->internal_notes ?: ($purchase->note ?: '—')), 110)); ?>

                    </td>
                    <td class="px-4 py-3 text-right">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($purchase->cancelled_at)): ?>
                            <form method="POST" action="<?php echo e(route('purchasing.events.undo', $purchase->id)); ?>" onsubmit="return confirm('Undo this purchasing event?');">
                                <?php echo csrf_field(); ?>
                                <button class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-600 hover:bg-slate-50">Undo</button>
                            </form>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                </tr>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-sm font-semibold text-slate-500">No purchase events yet.</td>
                </tr>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </tbody>
    </table>
</div>
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/shared/purchasing/_purchase_event_table.blade.php ENDPATH**/ ?>