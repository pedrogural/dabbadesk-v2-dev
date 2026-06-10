<?php $__env->startSection('title', 'Order request received ' . $reference); ?>
<?php $__env->startSection('subtitle', 'Order request confirmation'); ?>
<?php $__env->startSection('reference', $reference); ?>

<?php $__env->startSection('content'); ?>
    <h1 style="margin:0 0 8px;font-size:22px;line-height:1.25;color:#111827;">
        Thanks — we’ve received your request.
    </h1>

    <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#475569;">
        We’ll review your items and confirm any questions before we proceed. Please keep your reference handy:
        <strong><?php echo e($reference); ?></strong>.
    </p>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($customerDetails ?? [])): ?>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
            style="margin:18px 0;border:1px solid #dbe3ef;border-radius:12px;overflow:hidden;">
            <tr>
                <td
                    style="background:#f8fafc;padding:12px 16px;font-size:11px;font-weight:800;color:#334155;text-transform:uppercase;">
                    Your details
                </td>
            </tr>
            <tr>
                <td style="padding:16px;">
                    <p style="margin:0 0 8px;font-size:13px;"><strong>Name:</strong>
                        <?php echo e($customerDetails['name'] ?? 'Not supplied'); ?></p>
                    <p style="margin:0 0 8px;font-size:13px;"><strong>Email:</strong>
                        <?php echo e($customerDetails['email'] ?? 'Not supplied'); ?></p>
                    <p style="margin:0 0 8px;font-size:13px;"><strong>Phone:</strong>
                        <?php echo e($customerDetails['phone'] ?? 'Not supplied'); ?></p>
                    <p style="margin:0 0 8px;font-size:13px;"><strong>Address:</strong>
                        <?php echo e($customerDetails['address'] ?? 'Not supplied'); ?></p>
                    <p style="margin:0;font-size:13px;"><strong>Country:</strong>
                        <?php echo e($customerDetails['country'] ?? 'Not supplied'); ?></p>
                </td>
            </tr>
        </table>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
        style="margin:18px 0;border:1px solid #bbf7d0;background:#f0fdf4;border-radius:12px;">
        <tr>
            <td style="padding:16px;">
                <div style="font-size:13px;font-weight:800;color:#166534;">What happens next?</div>
                <ol style="margin:10px 0 0;padding-left:20px;font-size:13px;line-height:1.7;color:#166534;">
                    <li>We check availability and details.</li>
                    <li>We confirm pricing and timing.</li>
                    <li>We contact you with the next step.</li>
                </ol>
            </td>
        </tr>
    </table>


    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($requestNotes ?? '')): ?>
        <div style="margin:20px 0;padding:16px;border:1px solid #dbe3ef;border-radius:12px;background:#f8fafc;">
            <div style="font-size:13px;font-weight:800;color:#111827;margin-bottom:8px;">Your request notes</div>
            <div style="white-space:pre-wrap;font-size:13px;line-height:1.6;color:#475569;"><?php echo e($requestNotes); ?></div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
        <?php echo $__env->make('emails.partials.retailer-group', [
            'retailerName' => $group['retailer_name'],
            'items' => $group['items'],
            'subtotal' => $group['subtotal'],
            'dabbaFee' => $group['dabba_fee'],
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
        style="margin-top:20px;border:1px solid #dbe3ef;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="padding:16px;background:#f8fafc;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td style="font-size:13px;color:#475569;">Retail subtotal</td>
                        <td align="right" style="font-size:13px;font-weight:700;">£<?php echo e(number_format($retailSubtotal, 2)); ?>

                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top:8px;font-size:13px;color:#475569;">Dabba fees</td>
                        <td align="right" style="padding-top:8px;font-size:13px;font-weight:700;">
                            £<?php echo e(number_format($dabbaFees, 2)); ?></td>
                    </tr>
                    <tr>
                        <td style="padding-top:12px;border-top:1px solid #dbe3ef;font-size:16px;font-weight:800;">Estimated
                            total</td>
                        <td align="right"
                            style="padding-top:12px;border-top:1px solid #dbe3ef;font-size:18px;font-weight:900;">
                            £<?php echo e(number_format($grandTotal, 2)); ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($attachmentCount ?? 0) > 0): ?>
        <div style="margin-top:20px;padding:16px;border:1px solid #dbe3ef;border-radius:12px;background:#f8fafc;">
            <div style="font-size:13px;font-weight:800;color:#111827;margin-bottom:8px;">
                Attachments received: <?php echo e($attachmentCount); ?>

            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $attachments ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attachment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <div style="font-size:12px;color:#475569;line-height:1.7;">
                    • <?php echo e($attachment['original_name'] ?? 'Attachment'); ?>

                </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <p style="margin:18px 0 0;font-size:13px;line-height:1.6;color:#475569;">
        This email confirms we received your request. It is not yet an invoice or payment request.
    </p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('emails.layouts.dabba', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/dabba-live/dabbadesk-v2/resources/views/emails/customer/order-request-confirmation.blade.php ENDPATH**/ ?>