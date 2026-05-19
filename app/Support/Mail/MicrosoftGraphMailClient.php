<?php

namespace App\Support\Mail;

use Illuminate\Support\Facades\Http;

class MicrosoftGraphMailClient
{
    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        ?string $replyTo = null
    ): void {
        $from = config('graphmail.from_address');

        $message = [
            'subject' => $subject,
            'body' => [
                'contentType' => 'HTML',
                'content' => $htmlBody,
            ],
            'toRecipients' => [
                [
                    'emailAddress' => [
                        'address' => $to,
                    ],
                ],
            ],
        ];

        if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $message['replyTo'] = [
                [
                    'emailAddress' => [
                        'address' => $replyTo,
                    ],
                ],
            ];
        }

        $payload = [
            'message' => $message,
            'saveToSentItems' => true,
        ];

        Http::withToken($this->accessToken())
            ->post("https://graph.microsoft.com/v1.0/users/{$from}/sendMail", $payload)
            ->throw();
    }

    private function accessToken(): string
    {
        $tenantId = config('graphmail.tenant_id');

        $response = Http::asForm()
            ->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
                'client_id' => config('graphmail.client_id'),
                'client_secret' => config('graphmail.client_secret'),
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ])
            ->throw()
            ->json();

        return $response['access_token'];
    }
}
