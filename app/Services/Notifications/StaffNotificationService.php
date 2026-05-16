<?php

namespace App\Services\Notifications;

use App\Support\Mail\MicrosoftGraphMailClient;

class StaffNotificationService
{
    public function __construct(
        protected MicrosoftGraphMailClient $mail
    ) {
    }

    public function send(
        string $subject,
        string $html
    ): void {
        $this->mail->send(
            config('graphmail.internal_notification_email'),
            $subject,
            $html
        );
    }
}
