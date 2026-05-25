<?php

namespace App\Services\Notifications;

use App\Support\Mail\MicrosoftGraphMailClient;
use Illuminate\Support\Facades\DB;

class CustomerNotificationService
{
    public function __construct(
        protected MicrosoftGraphMailClient $mail
    ) {
    }

    public function sendOrderRequestConfirmation(
        string $to,
        string $reference,
        array $payload
    ): void {
        $summary = $this->buildSummary($payload);

        $html = view('emails.customer.order-request-confirmation', [
            'reference' => $reference,
            ...$summary,
        ])->render();

        $this->mail->send(
            $to,
            "Dabba Direct: order request received ({$reference})",
            $html
        );
    }

    private function buildSummary(array $payload): array
    {
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];

        $rate = (float) ($payload['_fee_policy']['percentage_rate'] ?? 0.20);
        $min = (float) ($payload['_fee_policy']['minimum_fee'] ?? 10);

        $groups = [];
        $retailSubtotal = 0.0;
        $dabbaFees = 0.0;

        foreach ($items as $item) {
            $retailer = trim((string) ($item['retailer_name'] ?? 'Unknown retailer'));
            $retailer = $retailer !== '' ? $retailer : 'Unknown retailer';

            $qty = max(1, (int) ($item['qty'] ?? 1));
            $unit = (float) ($item['estimated_price'] ?? 0);
            $line = round($qty * $unit, 2);

            $retailSubtotal += $line;

            if (!isset($groups[$retailer])) {
                $groups[$retailer] = [
                    'retailer_name' => $retailer,
                    'items' => [],
                    'subtotal' => 0.0,
                    'dabba_fee' => 0.0,
                ];
            }

            $groups[$retailer]['items'][] = $item;
            $groups[$retailer]['subtotal'] += $line;
        }

        foreach ($groups as $retailer => $group) {
            $fee = max($min, $group['subtotal'] * $rate);
            $fee = round($fee, 2);

            $groups[$retailer]['dabba_fee'] = $fee;
            $dabbaFees += $fee;
        }

        $attachments = is_array($payload['_attachments'] ?? null)
            ? $payload['_attachments']
            : [];

        $addressCountry = $this->countryName((string) ($payload['address_country'] ?? ''));
        $phoneCountry = $this->countryName((string) ($payload['customer_phone_country'] ?? ''));

        return [
            'customerName' => trim((string) ($payload['customer_name'] ?? '')),
            'customerEmail' => (string) ($payload['customer_email'] ?? ''),
            'customerPhone' => (string) ($payload['customer_phone'] ?? ''),
            'customerPhoneCountry' => $phoneCountry,
            'customerCompany' => (string) ($payload['customer_company_name'] ?? ''),
            'customerAddressLine1' => (string) ($payload['address_line1'] ?? ''),
            'customerAddressLine2' => (string) ($payload['address_line2'] ?? ''),
            'customerAddressCity' => (string) ($payload['address_city'] ?? ''),
            'customerAddressPostcode' => (string) ($payload['address_postcode'] ?? ''),
            'customerAddressCountry' => $addressCountry,
            'groups' => array_values($groups),
            'retailSubtotal' => round($retailSubtotal, 2),
            'dabbaFees' => round($dabbaFees, 2),
            'grandTotal' => round($retailSubtotal + $dabbaFees, 2),
            'attachmentCount' => (int) ($payload['_attachment_count'] ?? count($attachments)),
            'attachments' => $attachments,
            'requestNotes' => trim((string) ($payload['notes'] ?? '')),
        ];
    }

    private function countryName(string $code): string
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return '';
        }

        $country = DB::table('countries')
            ->whereRaw('UPPER(iso2) = ?', [$code])
            ->orWhereRaw('UPPER(iso3) = ?', [$code])
            ->orWhereRaw('UPPER(name) = ?', [$code])
            ->first(['name']);

        return $country?->name ?? $code;
    }
}
