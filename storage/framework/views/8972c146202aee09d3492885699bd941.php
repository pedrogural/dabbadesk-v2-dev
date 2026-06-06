<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title><?php echo $__env->yieldContent('title', 'Dabba Direct'); ?></title>
</head>
<body style="margin:0;padding:0;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f3f6fb;padding:24px 12px;">
    <tr>
        <td align="center">
            <table width="680" cellpadding="0" cellspacing="0" role="presentation" style="max-width:680px;width:100%;background:#ffffff;border:1px solid #dbe3ef;border-radius:14px;overflow:hidden;">
                <tr>
                    <td bgcolor="#1d4ed8" style="background:#1d4ed8;color:#ffffff;padding:22px 26px;">
                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                                <td style="color:#ffffff;">
                                    <div style="font-size:18px;font-weight:800;color:#ffffff;">Dabba Direct</div>
                                    <div style="font-size:12px;color:#ffffff;margin-top:4px;"><?php echo $__env->yieldContent('subtitle'); ?></div>
                                </td>
                                <td align="right" style="font-size:12px;font-weight:700;color:#ffffff;">
                                    <?php if (! empty(trim($__env->yieldContent('reference')))): ?>
                                        <span style="color:#ffffff;">Ref: <?php echo $__env->yieldContent('reference'); ?></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:26px;">
                        <?php echo $__env->yieldContent('content'); ?>
                    </td>
                </tr>

                <tr>
                    <td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:16px 26px;font-size:11px;color:#64748b;">
                        This is an automated notification from Dabba Direct.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
<?php /**PATH /var/www/dabba-live/dabbadesk-v2/resources/views/emails/layouts/dabba.blade.php ENDPATH**/ ?>