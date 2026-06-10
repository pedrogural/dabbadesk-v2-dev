<x-app-layout>
    <x-slot name="header">
        Orders
    </x-slot>

    @php
        $orderType = $order->order_type ?? $order->purchase_mode ?? 'standard';
        $isCustomerSelfPurchase = $orderType === 'customer_self_purchase';
        $customerRequestNotes = collect($notes ?? [])->filter(function ($note) {
            return ($note->type ?? '') === 'order_request_note' || ($note->title ?? '') === 'Customer order request notes';
        })->values();
        $requestAttachments = collect($requestAttachments ?? []);
        $customerFullName = trim((string) ($order->bill_to_name ?: trim(($order->first_name ?? '') . ' ' . ($order->last_name ?? ''))));
        $customerCompany = trim((string) ($order->bill_to_company ?: $order->company_name));
        $customerEmail = trim((string) ($order->bill_to_email ?? ''));
        $rawCustomerPhone = trim((string) ($order->bill_to_phone ?? ''));
        $phoneCountryCode = trim((string) ($order->customer_phone_country_code ?? ''));
        $customerPhoneDigits = preg_replace('/\D+/', '', $rawCustomerPhone);
        $customerPhone = $rawCustomerPhone;
        if ($rawCustomerPhone !== '' && ! str_starts_with($rawCustomerPhone, '+') && $phoneCountryCode !== '') {
            $customerPhone = '+' . ltrim($phoneCountryCode, '+') . ' ' . $customerPhoneDigits;
        }
        $addressLines = collect([
            trim((string) ($order->bill_to_address_line1 ?? '')),
            trim((string) ($order->bill_to_postcode ?? '')),
            trim((string) ($order->bill_to_country_name ?? '')),
        ])->filter(fn ($line) => $line !== '')->values();
        $copyAddressLines = collect([$customerFullName, $customerCompany])->filter(fn ($line) => trim((string) $line) !== '')->merge($addressLines)->values();
        $copyFullAddress = $copyAddressLines->implode("\n");
        $requestRef = trim((string) ($order->order_request_ref ?? ''));
        $draftNumber = trim((string) ($order->draft_number ?? ''));
        $paymentTimeline = collect($paymentTimeline ?? []);
        $paymentRows = $paymentTimeline->filter(fn ($event) => in_array(($event->type ?? ''), ['payment', 'ledger_payment'], true))->values();
        $reversiblePaymentCount = $paymentRows->filter(fn ($event) => ($event->status ?? '') === 'recorded' && empty($event->has_void))->count();
        $userOrderNotes = collect($notes ?? [])->reject(function ($note) {
            return ($note->type ?? '') === 'order_request_note' || ($note->title ?? '') === 'Customer order request notes';
        })->values();
        $isInvoiced = ! empty($order->invoiced_at) || in_array((string) ($order->status ?? ''), ['invoiced', 'partially_paid', 'paid'], true);
        $balanceDue = round((float) ($finance['balance_due'] ?? 0), 2);
        $settledTotal = round((float) ($finance['settled_total'] ?? 0), 2);
        $orderTotal = round((float) ($finance['order_total'] ?? $order->grand_total ?? 0), 2);
        $walletCreditFromOverpayments = round((float) ($finance['wallet_credit_from_overpayments'] ?? 0), 2);
        $walletCreditFromRevisions = round((float) ($finance['wallet_credit_from_revisions'] ?? 0), 2);
        $walletAttentionTotal = round((float) ($finance['wallet_attention_total'] ?? ($walletCreditFromOverpayments + $walletCreditFromRevisions)), 2);
        $walletAttentionSources = collect($finance['wallet_attention_sources'] ?? [])->filter()->values();
        $walletAvailable = round((float) ($finance['wallet_available'] ?? 0), 2);
        $paymentStatusLabel = $balanceDue <= 0.004 && $orderTotal > 0 ? 'Paid in full' : ($settledTotal > 0 ? 'Partially paid' : 'Awaiting payment');
        $paymentStatusClasses = $balanceDue <= 0.004 && $orderTotal > 0 ? 'bg-emerald-100 text-emerald-700 ring-emerald-200' : ($settledTotal > 0 ? 'bg-amber-100 text-amber-700 ring-amber-200' : 'bg-rose-100 text-rose-700 ring-rose-200');
        $invoiceWorkspace = $invoiceWorkspace ?? [];
        $invoiceRoot = $invoiceWorkspace['invoice'] ?? null;
        $latestInvoiceVersion = $invoiceWorkspace['latest_version'] ?? null;
        $invoiceVersions = collect($invoiceWorkspace['versions'] ?? []);
        $hasInvoiceWorkspace = ! empty($invoiceRoot) && ! empty($latestInvoiceVersion);
        $invoiceNumber = $invoiceRoot->invoice_number ?? $order->order_number;
        $invoiceStatusLabel = $hasInvoiceWorkspace
            ? Str::of((string) ($latestInvoiceVersion->status ?? 'ISSUED'))->replace('_', ' ')->title()
            : 'No invoice created';
        $invoiceStatusClasses = $hasInvoiceWorkspace ? 'bg-emerald-100 text-emerald-700 ring-emerald-200' : 'bg-amber-100 text-amber-700 ring-amber-200';
        $purchaseStatusLabel = $isCustomerSelfPurchase ? 'Customer purchased' : (((int) ($progress['remaining_purchase_qty'] ?? 0) > 0) ? 'Awaiting purchase' : 'Purchased');
        $paymentTypeOptions = [
            'Online Payment Link (Card)',
            'Card (Office)',
            'Bank Transfer (BACS)',
            'Cash',
            'PayPal',
            'Customer Wallet',
            'Adjustment / Correction',
            'Other',
        ];
        $copyPaymentDetails = collect([
            $customerFullName,
            $customerCompany,
            $customerEmail,
            'Order #' . $order->order_number,
            'Outstanding £' . number_format($balanceDue, 2),
        ])->filter(fn ($line) => trim((string) $line) !== '')->implode("\n");
        $copyOrderNumber = 'Order #' . $order->order_number;
        $copyCustomerId = ! empty($order->customer_id) ? 'Customer #' . $order->customer_id : '';
        $whatsappDigits = preg_replace('/\D+/', '', $customerPhone);
        if (str_starts_with($whatsappDigits, '00')) {
            $whatsappDigits = substr($whatsappDigits, 2);
        }
        $whatsappUrl = $whatsappDigits !== '' ? 'https://wa.me/' . $whatsappDigits : null;
        $revisionNumber = (int) ($order->revision_number ?? 1);
        $revisionTotal = (int) ($order->revision_total ?? 1);
        $revisionState = (string) ($order->revision_state ?? 'current');
        $revisionBadgeLabel = $revisionTotal > 1 ? 'Revision ' . $revisionNumber . ' of ' . $revisionTotal : 'Original order';
        $revisionBadgeClasses = $revisionState === 'superseded' ? 'bg-rose-100 text-rose-700 ring-rose-200' : ($revisionTotal > 1 ? 'bg-violet-100 text-violet-700 ring-violet-200' : 'bg-slate-100 text-slate-600 ring-slate-200');
        $retailerItems = collect($retailerGroups ?? [])->flatMap(fn ($group) => collect($group->items ?? []));
        $needsAttentionItems = $retailerItems->filter(function ($item) use ($isCustomerSelfPurchase) {
            if ($isCustomerSelfPurchase) {
                return false;
            }

            return (int) ($item->purchase_remaining_qty ?? 0) > 0 || filled($item->inspection_note ?? null);
        })->count();
        $arrivalIssueItems = $retailerItems->filter(function ($item) {
            $status = (string) ($item->latest_arrival_status ?? '');

            return str_contains($status, 'problem') || str_contains($status, 'issue') || str_contains($status, 'missing') || str_contains($status, 'damaged');
        })->count();
        $hasAlerts = $walletAttentionTotal > 0.004 || $needsAttentionItems > 0 || $arrivalIssueItems > 0 || $isCustomerSelfPurchase || $walletCreditFromRevisions > 0.004;
    @endphp
    <div class="space-y-5" data-order-copy-scope x-data="{ tab: 'overview' }">
        @include('orders.show._page_header')

        @include('orders.show.tabs._overview')

        @include('orders.show.tabs._finance')

        @include('orders.show.tabs._items')

        @include('orders.show.tabs._purchasing')

        @include('orders.show.tabs._notes')

        @include('orders.show._revision_history')
    </div>

    @include('orders.show._modals')

    @include('orders.show._copy_script')

</x-app-layout>
