<?php
    $retailerName = $retailerName ?? 'Unknown retailer';
    $items = $items ?? [];
    $subtotal = (float) ($subtotal ?? 0);
    $dabbaFee = (float) ($dabbaFee ?? 0);
    $total = $subtotal + $dabbaFee;
?>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:18px;border:1px solid #dbe3ef;border-radius:12px;overflow:hidden;">
    <tr>
        <td style="padding:14px 16px;background:#f8fafc;border-left:5px solid #a21caf;">
            <div style="font-size:15px;font-weight:800;color:#111827;"><?php echo e($retailerName); ?></div>
            <div style="font-size:12px;color:#475569;margin-top:8px;">
                Retail subtotal: <strong>£<?php echo e(number_format($subtotal, 2)); ?></strong>
                &nbsp;|&nbsp;
                Dabba fee: <strong>£<?php echo e(number_format($dabbaFee, 2)); ?></strong>
                &nbsp;|&nbsp;
                Total: <strong>£<?php echo e(number_format($total, 2)); ?></strong>
            </div>
        </td>
    </tr>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
        <tr>
            <td style="padding:14px 16px;border-top:1px solid #e5e7eb;">
                <div style="font-size:13px;font-weight:700;line-height:1.45;color:#111827;">
                    <?php echo e($item['description'] ?? $item['product_code'] ?? 'Item'); ?>

                </div>

                <div style="margin-top:8px;font-size:12px;color:#475569;">
                    Qty: <strong><?php echo e($item['qty'] ?? 1); ?></strong>
                    &nbsp;|&nbsp;
                    Unit: <strong>£<?php echo e(number_format((float) ($item['estimated_price'] ?? 0), 2)); ?></strong>
                    &nbsp;|&nbsp;
                    Line: <strong>£<?php echo e(number_format(((int) ($item['qty'] ?? 1)) * ((float) ($item['estimated_price'] ?? 0)), 2)); ?></strong>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($item['retailer_url'])): ?>
                    <div style="margin-top:8px;">
                        <a href="<?php echo e($item['retailer_url']); ?>" style="color:#1d4ed8;font-size:12px;font-weight:700;">View product</a>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($item['notes'] ?? '')): ?>
                    <div style="margin-top:10px;padding:10px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0;">
                        <div style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Customer item notes</div>
                        <div style="white-space:pre-wrap;font-size:12px;line-height:1.55;color:#334155;"><?php echo e($item['notes']); ?></div>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </td>
        </tr>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
</table>
<?php /**PATH /var/www/dabba-live/dabbadesk-v2/resources/views/emails/partials/retailer-group.blade.php ENDPATH**/ ?>