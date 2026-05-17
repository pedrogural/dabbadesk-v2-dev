<?php

namespace App\Services\Intake;

use App\Services\Notifications\StaffNotificationService;

class PublicOrderRequestNotificationService
{
    public function __construct(
        protected StaffNotificationService $notifications
    ) {
    }

    public function notifyStaff(array $payload): void
    {
        $customer = $payload['customer'] ?? [];
        $items = $payload['items'] ?? [];

        $customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        $customerEmail = $customer['email'] ?? 'Not supplied';
        $customerPhone = $customer['phone'] ?? 'Not supplied';

        $rows = '';

        foreach ($items as $index => $item) {
            $number = $index + 1;

            $url = e($item['url'] ?? '');
            $retailer = e($item['retailer_name'] ?? 'Unknown');
            $qty = e((string) ($item['quantity'] ?? 1));
            $price = e((string) ($item['unit_price'] ?? ''));

            $rows .= "
                <tr>
                    <td>{$number}</td>
                    <td>{$retailer}</td>
                    <td>{$qty}</td>
                    <td>{$price}</td>
                    <td><a href=\"{$url}\">Product link</a></td>
                </tr>
            ";
        }

        $html = "
            <h2>New public order request received</h2>

            <p><strong>Customer:</strong> " . e($customerName ?: 'Not supplied') . "</p>
            <p><strong>Email:</strong> " . e($customerEmail) . "</p>
            <p><strong>Phone:</strong> " . e($customerPhone) . "</p>

            <h3>Requested items</h3>

            <table border=\"1\" cellpadding=\"6\" cellspacing=\"0\">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Retailer</th>
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
            'New public order request received',
            $html
        );
    }
}
