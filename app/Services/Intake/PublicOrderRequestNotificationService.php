<?php

namespace App\Services\Intake;

use App\Services\Notifications\StaffNotificationService;

class PublicOrderRequestNotificationService
{
    public function __construct(
        protected StaffNotificationService $notifications
    ) {
    }

    public function notifyStaff(array $payload, ?string $reference = null): void
    {
        $customerName = trim((string) ($payload['customer_name'] ?? ''));
        $customerEmail = (string) ($payload['customer_email'] ?? 'Not supplied');
        $customerPhone = (string) ($payload['customer_phone'] ?? 'Not supplied');

        $items = is_array($payload['items'] ?? null)
            ? $payload['items']
            : [];

        $rows = '';

        foreach ($items as $index => $item) {
            $number = $index + 1;

            $url = e((string) ($item['retailer_url'] ?? ''));
            $retailer = e((string) ($item['retailer_name'] ?? 'Unknown'));
            $qty = e((string) ($item['qty'] ?? 1));
            $price = e(number_format((float) ($item['estimated_price'] ?? 0), 2));

            $description = e((string) (
                $item['description']
                ?? $item['product_code']
                ?? 'Item'
            ));

            $link = $url !== ''
                ? "<a href=\"{$url}\" target=\"_blank\">Open product</a>"
                : 'No URL supplied';

            $rows .= "
                <tr>
                    <td>{$number}</td>
                    <td>{$retailer}</td>
                    <td>{$description}</td>
                    <td>{$qty}</td>
                    <td>{$price}</td>
                    <td>{$link}</td>
                </tr>
            ";
        }

        $subject = $reference
            ? "New order request {$reference}"
            : 'New public order request received';

        $html = "
            <h2>New public order request received</h2>

            <p><strong>Request reference:</strong> " . e($reference ?: 'Pending') . "</p>

            <hr>

            <p><strong>Customer:</strong> " . e($customerName ?: 'Not supplied') . "</p>
            <p><strong>Email:</strong> " . e($customerEmail) . "</p>
            <p><strong>Phone:</strong> " . e($customerPhone) . "</p>

            <h3>Requested items</h3>

            <table border=\"1\" cellpadding=\"6\" cellspacing=\"0\" width=\"100%\">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Retailer</th>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Unit price</th>
                        <th>URL</th>
                    </tr>
                </thead>
                <tbody>
                    {$rows}
                </tbody>
            </table>
        ";

        $this->notifications->send(
            $subject,
            $html
        );
    }
}