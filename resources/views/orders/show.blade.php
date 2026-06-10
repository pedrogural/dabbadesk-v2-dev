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
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-800">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="min-w-0">
                    <a href="{{ route('orders.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">← Back to Orders</a>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight text-slate-950">Order #{{ $order->order_number }}</h1>
                        <button type="button" data-copy-value="{{ $copyOrderNumber }}" class="copy-btn rounded-full border border-indigo-200 bg-white px-3 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Copy</button>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $revisionBadgeClasses }}">{{ $revisionBadgeLabel }}</span>
                        <span class="rounded-full {{ $isCustomerSelfPurchase ? 'bg-sky-100 text-sky-700' : 'bg-indigo-100 text-indigo-700' }} px-2.5 py-1 text-xs font-semibold">
                            {{ $isCustomerSelfPurchase ? 'Self-purchase' : 'Dabba purchase' }}
                        </span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $paymentStatusClasses }}">{{ $paymentStatusLabel }}</span>
                        @if ($walletAttentionTotal > 0.004)
                            <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-200">Wallet credit £{{ number_format($walletAttentionTotal, 2) }}</span>
                        @endif
                        @if ($balanceDue > 0.004)
                            <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-200">Outstanding £{{ number_format($balanceDue, 2) }}</span>
                        @else
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">No outstanding balance</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-slate-500">{{ $customerFullName ?: 'Unknown customer' }} · Total £{{ number_format($orderTotal, 2) }} · Settled £{{ number_format($settledTotal, 2) }}</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="$dispatch('open-payment-modal')" class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">Record payment</button>
                    <button type="button" @click="$dispatch('open-invoice-modal')" class="rounded-2xl {{ $hasInvoiceWorkspace ? 'bg-slate-900 hover:bg-slate-800' : 'bg-amber-600 hover:bg-amber-700' }} px-4 py-2 text-sm font-semibold text-white shadow-sm">{{ $hasInvoiceWorkspace ? 'New invoice version' : 'Create invoice' }}</button>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 rounded-2xl bg-slate-50 p-2 ring-1 ring-slate-100">
                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100 hover:bg-emerald-100">WhatsApp ↗</a>
                @endif
                @if ($customerEmail)
                    <a href="mailto:{{ $customerEmail }}" class="rounded-xl bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-100">Email ↗</a>
                @endif
                @if ($copyFullAddress)
                    <button type="button" data-copy-value="{{ $copyFullAddress }}" class="copy-btn rounded-xl border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Copy address</button>
                @endif
                <button type="button" data-copy-value="{{ $copyOrderNumber }}" class="copy-btn rounded-xl border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Copy order #</button>
                @if (! empty($order->customer_id))
                    <a href="{{ route('customers.edit', $order->customer_id) }}" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-indigo-100 hover:text-indigo-700">Customer ↗</a>
                @endif
                <a href="{{ route('money-desk.orders.show', $order->id) }}" class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100 hover:bg-emerald-100">Finance ↗</a>
            </div>

            <div class="mt-5 overflow-x-auto">
                <div class="inline-flex min-w-full gap-2 rounded-2xl bg-slate-100 p-1">
                    <button type="button" @click="tab = 'overview'" :class="tab === 'overview' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'" class="rounded-xl px-4 py-2 text-sm font-semibold">Overview</button>
                    <button type="button" @click="tab = 'finance'" :class="tab === 'finance' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'" class="rounded-xl px-4 py-2 text-sm font-semibold">Finance</button>
                    <button type="button" @click="tab = 'items'" :class="tab === 'items' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'" class="rounded-xl px-4 py-2 text-sm font-semibold">Items</button>
                    <button type="button" @click="tab = 'arrival'" :class="tab === 'arrival' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'" class="rounded-xl px-4 py-2 text-sm font-semibold">Arrival</button>
                    <button type="button" @click="tab = 'notes'" :class="tab === 'notes' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'" class="rounded-xl px-4 py-2 text-sm font-semibold">Notes</button>
                </div>
            </div>
        </section>

        <div x-show="tab === 'overview'" x-cloak class="space-y-5">
            @if ($hasAlerts)
                <section class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-700">Alerts</p>
                            <h2 class="mt-1 text-lg font-black text-amber-950">Operator reminders for this order</h2>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-amber-700 ring-1 ring-amber-200">Shows only when needed</span>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @if ($walletAttentionTotal > 0.004)
                            <div class="rounded-2xl bg-white/90 p-4 ring-1 ring-sky-100">
                                <p class="text-xs font-black uppercase tracking-wide text-sky-700">Wallet credit exists</p>
                                <p class="mt-1 text-xl font-black text-sky-950">£{{ number_format($walletAttentionTotal, 2) }}</p>
                                <p class="mt-1 text-sm font-semibold leading-5 text-sky-900">Generated from {{ $walletAttentionSources->isNotEmpty() ? $walletAttentionSources->implode(' and ') : 'this order history' }}. Staff should inform the customer.</p>
                            </div>
                        @endif

                        @if ($walletCreditFromRevisions > 0.004)
                            <div class="rounded-2xl bg-white/90 p-4 ring-1 ring-violet-100">
                                <p class="text-xs font-black uppercase tracking-wide text-violet-700">Revision-generated credit</p>
                                <p class="mt-1 text-xl font-black text-violet-950">£{{ number_format($walletCreditFromRevisions, 2) }}</p>
                                <p class="mt-1 text-sm font-semibold leading-5 text-violet-900">This came from a revised or superseded order. Make the credit obvious to the customer before they ask.</p>
                            </div>
                        @endif

                        @if ($needsAttentionItems > 0)
                            <div class="rounded-2xl bg-white/90 p-4 ring-1 ring-rose-100">
                                <p class="text-xs font-black uppercase tracking-wide text-rose-700">Needs attention</p>
                                <p class="mt-1 text-xl font-black text-rose-950">{{ $needsAttentionItems }} item{{ $needsAttentionItems === 1 ? '' : 's' }}</p>
                                <p class="mt-1 text-sm font-semibold leading-5 text-rose-900">There are pending purchase or inspection reminders in the item list.</p>
                            </div>
                        @endif

                        @if ($arrivalIssueItems > 0)
                            <div class="rounded-2xl bg-white/90 p-4 ring-1 ring-orange-100">
                                <p class="text-xs font-black uppercase tracking-wide text-orange-700">Arrival issue</p>
                                <p class="mt-1 text-xl font-black text-orange-950">{{ $arrivalIssueItems }} item{{ $arrivalIssueItems === 1 ? '' : 's' }}</p>
                                <p class="mt-1 text-sm font-semibold leading-5 text-orange-900">Arrival status contains an issue, problem, damaged or missing marker.</p>
                            </div>
                        @endif

                        @if ($isCustomerSelfPurchase)
                            <div class="rounded-2xl bg-white/90 p-4 ring-1 ring-sky-100">
                                <p class="text-xs font-black uppercase tracking-wide text-sky-700">Self-purchase reminder</p>
                                <p class="mt-1 text-sm font-semibold leading-5 text-sky-900">Do not buy these goods for the customer. Continue with arrival, customs, collection and delivery once goods reach Dabba.</p>
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            <div class="flex flex-col items-start gap-5 xl:flex-row xl:items-stretch">
                <section class="w-full max-w-[720px] rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 21a8 8 0 0 0-16 0" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-400">Customer</p>
                                <h2 class="mt-1 truncate text-xl font-black text-slate-950">{{ $customerFullName ?: 'Unknown customer' }}</h2>
                                @if ($customerCompany)
                                    <p class="mt-1 truncate text-sm font-semibold text-slate-500">{{ $customerCompany }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if (! empty($order->customer_id))
                                <a href="{{ route('customers.edit', $order->customer_id) }}" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-indigo-100 hover:text-indigo-700">Customer ↗</a>
                            @endif
                            @if ($customerFullName)
                                <button type="button" data-copy-value="{{ $customerFullName }}" class="copy-btn rounded-xl border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Copy name</button>
                            @endif
                            @if ($copyCustomerId)
                                <button type="button" data-copy-value="{{ $copyCustomerId }}" class="copy-btn rounded-xl border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Copy ID</button>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 space-y-2">
                        <div class="grid gap-3 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-100 md:grid-cols-[30px_92px_minmax(0,1fr)_auto] md:items-center">
                            <div class="hidden text-slate-400 md:block">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                            </div>
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Email</p>
                            <p class="min-w-0 break-all text-sm font-semibold text-slate-800">{{ $customerEmail ?: 'No email' }}</p>
                            @if ($customerEmail)
                                <button type="button" data-copy-value="{{ $customerEmail }}" class="copy-btn justify-self-start rounded-xl border border-indigo-200 bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 md:justify-self-end">Copy</button>
                            @endif
                        </div>

                        <div class="grid gap-3 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-100 md:grid-cols-[30px_92px_minmax(0,1fr)_auto] md:items-center">
                            <div class="hidden text-slate-400 md:block">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.32 1.78.59 2.63a2 2 0 0 1-.45 2.11L8 9.71a16 16 0 0 0 6.29 6.29l1.25-1.25a2 2 0 0 1 2.11-.45c.85.27 1.73.47 2.63.59A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Phone</p>
                            <p class="text-sm font-semibold text-slate-800">{{ $customerPhone ?: 'No phone' }}</p>
                            <div class="flex flex-wrap gap-2 md:justify-self-end">
                                @if ($customerPhone)
                                    <button type="button" data-copy-value="{{ $customerPhone }}" class="copy-btn rounded-xl border border-indigo-200 bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Copy</button>
                                @endif
                                @if ($whatsappUrl)
                                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100 hover:bg-emerald-100">WhatsApp ↗</a>
                                @endif
                            </div>
                        </div>

                        <div class="grid gap-3 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-100 md:grid-cols-[30px_92px_minmax(0,1fr)_auto] md:items-start">
                            <div class="hidden pt-0.5 text-slate-400 md:block">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <p class="pt-0.5 text-[10px] font-black uppercase tracking-wide text-slate-400">Address</p>
                            <div class="min-w-0">
                                @if ($addressLines->isNotEmpty())
                                    <div class="space-y-0.5 text-sm font-semibold leading-5 text-slate-800">
                                        @foreach ($addressLines as $line)
                                            <p>{{ $line }}</p>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-slate-400">No billing address captured.</p>
                                @endif
                            </div>
                            @if ($copyFullAddress)
                                <button type="button" data-copy-value="{{ $copyFullAddress }}" class="copy-btn justify-self-start rounded-xl border border-indigo-200 bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 md:justify-self-end">Copy</button>
                            @endif
                        </div>

                        @if ($copyCustomerId)
                            <div class="grid gap-3 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-100 md:grid-cols-[30px_92px_minmax(0,1fr)_auto] md:items-center">
                                <div class="hidden text-slate-400 md:block">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="14" x="3" y="5" rx="2"/><path d="M7 9h4"/><path d="M7 13h2"/><circle cx="16" cy="11" r="2"/></svg>
                                </div>
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Customer ID</p>
                                <p class="text-sm font-semibold text-slate-800">#{{ $order->customer_id }}</p>
                                <button type="button" data-copy-value="{{ $copyCustomerId }}" class="copy-btn justify-self-start rounded-xl border border-indigo-200 bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 md:justify-self-end">Copy</button>
                            </div>
                        @endif
                    </div>
                </section>

                <section class="w-full max-w-[360px] rounded-3xl {{ $balanceDue > 0 ? 'border-rose-200 bg-rose-50' : 'border-emerald-200 bg-emerald-50' }} border p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] {{ $balanceDue > 0 ? 'text-rose-700' : 'text-emerald-700' }}">Outstanding balance</p>
                    <p class="mt-2 text-4xl font-semibold {{ $balanceDue > 0 ? 'text-rose-700' : 'text-emerald-700' }}">£{{ number_format($balanceDue, 2) }}</p>
                    <p class="mt-2 text-sm text-slate-600">Total £{{ number_format($orderTotal, 2) }} · Settled £{{ number_format($settledTotal, 2) }}</p>
                    @if ($walletAvailable > 0.004)
                        <p class="mt-2 rounded-2xl bg-white/70 px-3 py-2 text-sm font-semibold text-sky-800 ring-1 ring-sky-100">Wallet available: £{{ number_format($walletAvailable, 2) }}</p>
                    @endif
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" @click="$dispatch('open-payment-modal')" class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Record payment</button>
                        <button type="button" data-copy-value="{{ e($copyPaymentDetails) }}" class="copy-btn rounded-2xl border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Copy payment details</button>
                    </div>
                </section>
            </div>

            <div class="grid gap-5 lg:grid-cols-3">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Invoice</p>
                    <p class="mt-1 text-lg font-semibold text-slate-950">{{ $invoiceStatusLabel }}</p>
                    <button type="button" @click="$dispatch('open-invoice-modal')" class="mt-3 rounded-2xl {{ $hasInvoiceWorkspace ? 'bg-slate-900 hover:bg-slate-800' : 'bg-amber-600 hover:bg-amber-700' }} px-4 py-2 text-sm font-semibold text-white">{{ $hasInvoiceWorkspace ? 'Create new version' : 'Create invoice' }}</button>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Progress</p>
                    <p class="mt-2 text-sm text-slate-600">Items {{ $progress['item_qty'] ?? 0 }} · Bought {{ $progress['purchased_qty'] ?? 0 }} · Arrived {{ $progress['arrived_qty'] ?? 0 }} · Done {{ $progress['collected_qty'] ?? 0 }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ $purchaseStatusLabel }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Source</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if (! empty($order->order_request_id))
                            <a href="{{ route('order-requests.show', $order->order_request_id) }}" class="rounded-xl bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100">Request ↗</a>
                        @endif
                        <a href="{{ route('draft-orders.show', $order->draft_order_id) }}" class="rounded-xl bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">Draft ↗</a>
                        <a href="{{ route('money-desk.orders.show', $order->id) }}" class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">Finance ↗</a>
                    </div>
                </div>
            </div>

        @if ($isCustomerSelfPurchase)
            <div class="rounded-3xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-sky-700">Customer self-purchase</p>
                        <h2 class="mt-1 text-lg font-black text-sky-950">Customer bought the goods directly from the retailer</h2>
                        <p class="mt-1 text-sm font-semibold leading-6 text-sky-900">Dabba should not purchase these goods. Continue with arrival, customs, collection and delivery workflow when the goods reach Dabba.</p>
                    </div>
                    <a href="{{ route('money-desk.orders.show', $order->id) }}" class="rounded-2xl bg-white px-4 py-2.5 text-sm font-black text-sky-700 ring-1 ring-sky-200 hover:bg-sky-100">Finance ↗</a>
                </div>
            </div>
        @endif
        @if ($revisionTotal > 1 || $walletCreditFromRevisions > 0.004)
            <div class="rounded-3xl border border-violet-200 bg-violet-50 p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-violet-700">Revision impact</p>
                        <h2 class="mt-1 text-lg font-black text-violet-950">{{ $revisionBadgeLabel }}</h2>
                        <p class="mt-1 text-sm font-semibold leading-6 text-violet-900">
                            @if ($walletCreditFromRevisions > 0.004)
                                This revision chain generated £{{ number_format($walletCreditFromRevisions, 2) }} wallet credit for the customer.
                            @else
                                This order has saved historical snapshots. Older versions remain viewable for audit.
                            @endif
                        </p>
                    </div>
                    @if (($revisionHistory ?? collect())->count() > 1)
                        <span class="rounded-2xl bg-white px-4 py-2 text-sm font-black text-violet-700 ring-1 ring-violet-200">{{ ($revisionHistory ?? collect())->count() }} snapshot{{ ($revisionHistory ?? collect())->count() === 1 ? '' : 's' }}</span>
                    @endif
                </div>

                @if (($revisionHistory ?? collect())->count() > 1)
                    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach (($revisionHistory ?? collect())->take(6) as $revision)
                            @php
                                $isCurrentRevision = (int) $revision->id === (int) $order->id;
                                $isSupersededRevision = ($revision->revision_state ?? '') === 'superseded';
                            @endphp
                            <div class="rounded-2xl {{ $isCurrentRevision ? 'bg-white ring-2 ring-violet-200' : 'bg-white/80 ring-1 ring-violet-100' }} p-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-black text-slate-950">Rev {{ $revision->revision_number }}</p>
                                    @if ($isCurrentRevision)
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-black text-emerald-700">Current</span>
                                    @elseif ($isSupersededRevision)
                                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-black text-rose-700">Superseded</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs font-semibold text-slate-500">£{{ number_format($revision->grand_total ?? 0, 2) }} · {{ $revision->created_at ? \Carbon\Carbon::parse($revision->created_at)->format('d M Y') : 'Date unknown' }}</p>
                                @if (! $isCurrentRevision)
                                    <a href="{{ route('orders.show', $revision->id) }}" class="mt-3 inline-flex rounded-xl bg-white px-3 py-1.5 text-xs font-bold text-violet-700 ring-1 ring-violet-200 hover:bg-violet-100">View snapshot ↗</a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        @if ($customerRequestNotes->isNotEmpty())
            <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-700">Customer order request notes</p>
                        <h2 class="mt-1 text-lg font-black text-amber-950">Original customer notes carried through from request</h2>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black uppercase tracking-wide text-amber-700 ring-1 ring-amber-200">Pinned lifecycle note</span>
                </div>

                <div class="mt-4 space-y-3">
                    @foreach ($customerRequestNotes as $requestNote)
                        <div class="rounded-2xl bg-white/80 px-4 py-3 text-sm leading-6 text-amber-950 ring-1 ring-amber-100">
                            <p class="whitespace-pre-line">{{ $requestNote->body }}</p>
                            <p class="mt-2 text-xs font-semibold text-amber-700">
                                {{ ($requestNote->occurred_at ?: $requestNote->created_at) ? \Carbon\Carbon::parse($requestNote->occurred_at ?: $requestNote->created_at)->format('d M Y H:i') : 'Date unknown' }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        </div>

        <div x-show="tab === 'finance'" x-cloak class="space-y-5">
            <div class="grid gap-5 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Finance summary</p>
                            <h2 class="mt-1 text-lg font-black text-slate-950">What does the customer owe?</h2>
                            <p class="mt-1 text-sm text-slate-500">A plain-English view of the money position for this order.</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-black uppercase tracking-wide ring-1 {{ $paymentStatusClasses }}">{{ $paymentStatusLabel }}</span>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-3xl bg-slate-50 p-4 ring-1 ring-slate-100">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Order total</p>
                            <p class="mt-1 text-2xl font-black text-slate-950">£{{ number_format($orderTotal, 2) }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">Full value of this order snapshot.</p>
                        </div>
                        <div class="rounded-3xl {{ $balanceDue > 0.004 ? 'bg-rose-50 ring-rose-100' : 'bg-emerald-50 ring-emerald-100' }} p-4 ring-1">
                            <p class="text-[10px] font-black uppercase tracking-wide {{ $balanceDue > 0.004 ? 'text-rose-700' : 'text-emerald-700' }}">Outstanding balance</p>
                            <p class="mt-1 text-2xl font-black {{ $balanceDue > 0.004 ? 'text-rose-700' : 'text-emerald-700' }}">£{{ number_format($balanceDue, 2) }}</p>
                            <p class="mt-1 text-xs font-semibold {{ $balanceDue > 0.004 ? 'text-rose-700' : 'text-emerald-700' }}">
                                {{ $balanceDue > 0.004 ? 'Customer still needs to settle this amount.' : 'This order is financially settled.' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3">
                            <p class="text-[10px] font-black uppercase tracking-wide text-emerald-700">Payments received</p>
                            <p class="mt-1 text-lg font-black text-emerald-700">£{{ number_format((float) ($finance['payments_used'] ?? 0), 2) }}</p>
                            <p class="mt-1 text-[11px] font-semibold text-emerald-700">Real customer payments applied to this order.</p>
                        </div>
                        <div class="rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3">
                            <p class="text-[10px] font-black uppercase tracking-wide text-sky-700">Wallet used</p>
                            <p class="mt-1 text-lg font-black text-sky-700">£{{ number_format((float) ($finance['wallet_used'] ?? 0), 2) }}</p>
                            <p class="mt-1 text-[11px] font-semibold text-sky-700">Existing wallet balance used on this order.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Net settled</p>
                            <p class="mt-1 text-lg font-black text-slate-900">£{{ number_format($settledTotal, 2) }}</p>
                            <p class="mt-1 text-[11px] font-semibold text-slate-500">Payments plus wallet use, minus reversals/refunds.</p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" @click="$dispatch('open-payment-modal')" class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-emerald-700">Record payment</button>
                        <button type="button" data-copy-value="{{ e($copyPaymentDetails) }}" class="rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-black text-indigo-700 hover:bg-indigo-100">Copy payment details</button>
                        <a href="{{ route('money-desk.orders.show', $order->id) }}" class="rounded-2xl border border-emerald-200 bg-white px-4 py-2 text-sm font-black text-emerald-700 hover:bg-emerald-50">Money Desk ↗</a>
                    </div>

                    <p class="mt-3 rounded-2xl bg-slate-50 px-4 py-3 text-xs font-semibold leading-5 text-slate-500">
                        Payment default is <span class="font-black text-slate-800">Online Payment Link (Card)</span>, matching Dabba's normal payment flow.
                    </p>
                </section>

                <section class="rounded-3xl border {{ $walletAvailable > 0.004 ? 'border-sky-200 bg-sky-50' : 'border-slate-200 bg-white' }} p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] {{ $walletAvailable > 0.004 ? 'text-sky-700' : 'text-slate-400' }}">Customer wallet</p>
                            <h2 class="mt-1 text-lg font-black text-slate-950">Does the customer have credit?</h2>
                            <p class="mt-1 text-sm {{ $walletAvailable > 0.004 ? 'text-sky-900' : 'text-slate-500' }}">Shows reusable customer-owned balance, not ordinary payments already consumed by this order.</p>
                        </div>
                        <span class="rounded-full {{ $walletAvailable > 0.004 ? 'bg-sky-100 text-sky-700 ring-sky-200' : 'bg-slate-100 text-slate-500 ring-slate-200' }} px-3 py-1 text-xs font-black uppercase tracking-wide ring-1">
                            {{ $walletAvailable > 0.004 ? 'Credit available' : 'No wallet credit' }}
                        </span>
                    </div>

                    <div class="mt-5 rounded-3xl bg-white/80 p-4 ring-1 ring-black/5">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Wallet available</p>
                        <p class="mt-1 text-3xl font-black {{ $walletAvailable > 0.004 ? 'text-sky-700' : 'text-slate-400' }}">£{{ number_format($walletAvailable, 2) }}</p>
                        <p class="mt-1 text-xs font-semibold text-slate-500">Total open wallet balance for this customer.</p>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-white/80 px-4 py-3 ring-1 ring-black/5">
                            <p class="text-[10px] font-black uppercase tracking-wide text-violet-700">From order amendments</p>
                            <p class="mt-1 text-lg font-black text-violet-700">£{{ number_format($walletCreditFromRevisions, 2) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/80 px-4 py-3 ring-1 ring-black/5">
                            <p class="text-[10px] font-black uppercase tracking-wide text-amber-700">From overpayments</p>
                            <p class="mt-1 text-lg font-black text-amber-700">£{{ number_format($walletCreditFromOverpayments, 2) }}</p>
                        </div>
                    </div>

                    @if ($walletAttentionTotal > 0.004)
                        <div class="mt-4 rounded-2xl border border-sky-200 bg-white px-4 py-3 text-sm font-semibold leading-6 text-sky-950">
                            <span class="font-black">Staff action:</span>
                            Tell the customer they have <span class="font-black">£{{ number_format($walletAttentionTotal, 2) }}</span> wallet credit generated from {{ $walletAttentionSources->isNotEmpty() ? $walletAttentionSources->implode(' and ') : 'this order history' }}.
                        </div>
                    @else
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold leading-6 text-slate-500">
                            No amendment or overpayment credit needs staff attention for this order.
                        </div>
                    @endif
                </section>
            </div>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Invoice</p>
                            <h2 class="mt-1 text-lg font-black text-slate-950">Invoice snapshot</h2>
                            <p class="mt-1 text-sm text-slate-500">Invoice PDFs are historical outputs from this order snapshot.</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-black uppercase tracking-wide ring-1 {{ $invoiceStatusClasses }}">{{ $invoiceStatusLabel }}</span>
                    </div>

                    <div class="mt-4 rounded-3xl border {{ $hasInvoiceWorkspace ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }} p-4">
                        <p class="text-xs font-black uppercase tracking-[0.2em] {{ $hasInvoiceWorkspace ? 'text-emerald-700' : 'text-amber-700' }}">{{ $hasInvoiceWorkspace ? 'Current invoice' : 'Invoice needed' }}</p>
                        <h3 class="mt-1 text-base font-black text-slate-950">
                            @if ($hasInvoiceWorkspace)
                                Invoice #{{ $invoiceNumber }} · version {{ $latestInvoiceVersion->version }}
                            @else
                                No invoice created yet
                            @endif
                        </h3>
                        <p class="mt-1 text-xs font-semibold {{ $hasInvoiceWorkspace ? 'text-emerald-900' : 'text-amber-900' }}">
                            @if ($hasInvoiceWorkspace)
                                Issued {{ $latestInvoiceVersion->issued_at ? \Carbon\Carbon::parse($latestInvoiceVersion->issued_at)->format('d M Y H:i') : 'date unknown' }} · Total £{{ number_format((float) $latestInvoiceVersion->grand_total, 2) }}
                            @else
                                Create the first invoice snapshot from this order when ready.
                            @endif
                        </p>

                        @if ($hasInvoiceWorkspace)
                            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                <div class="rounded-2xl bg-white p-3 ring-1 ring-black/5">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Items</p>
                                    <p class="mt-1 text-sm font-black text-slate-900">£{{ number_format((float) $latestInvoiceVersion->items_subtotal, 2) }}</p>
                                </div>
                                <div class="rounded-2xl bg-white p-3 ring-1 ring-black/5">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Delivery + fee</p>
                                    <p class="mt-1 text-sm font-black text-slate-900">£{{ number_format((float) $latestInvoiceVersion->delivery_total + (float) $latestInvoiceVersion->dabba_fee_total, 2) }}</p>
                                </div>
                                <div class="rounded-2xl bg-white p-3 ring-1 ring-black/5">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Grand total</p>
                                    <p class="mt-1 text-sm font-black text-slate-900">£{{ number_format((float) $latestInvoiceVersion->grand_total, 2) }}</p>
                                </div>
                            </div>
                        @endif

                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" @click="$dispatch('open-invoice-modal')" class="rounded-2xl {{ $hasInvoiceWorkspace ? 'bg-slate-900 hover:bg-slate-800' : 'bg-amber-600 hover:bg-amber-700' }} px-4 py-2 text-sm font-black text-white shadow-sm">{{ $hasInvoiceWorkspace ? 'Create invoice version' : 'Create invoice' }}</button>
                            @if ($hasInvoiceWorkspace && ! empty($invoiceRoot->pdf_path))
                                <a href="{{ asset('storage/' . ltrim($invoiceRoot->pdf_path, '/')) }}" target="_blank" rel="noopener noreferrer" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">View / download ↗</a>
                            @else
                                <button type="button" disabled class="cursor-not-allowed rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-400">PDF next</button>
                            @endif
                            <button type="button" disabled class="cursor-not-allowed rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-400">Send next</button>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Invoice history</p>
                            <h2 class="mt-1 text-lg font-black text-slate-950">Invoice versions</h2>
                            <p class="mt-1 text-sm text-slate-500">Previous invoice snapshots for this order.</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $invoiceVersions->count() }} version{{ $invoiceVersions->count() === 1 ? '' : 's' }}</span>
                    </div>
                    <div class="mt-4 space-y-2">
                        @forelse ($invoiceVersions->take(5) as $version)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div>
                                    <p class="text-sm font-black text-slate-950">Invoice #{{ $invoiceNumber }} · version {{ $version->version }}</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ $version->issued_at ? \Carbon\Carbon::parse($version->issued_at)->format('d M Y H:i') : 'Issued date unknown' }} @if(! empty($version->issued_by_name)) · {{ $version->issued_by_name }} @endif</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-black text-slate-950">£{{ number_format((float) $version->grand_total, 2) }}</p>
                                    <p class="mt-1 text-xs font-black uppercase tracking-wide text-emerald-700">{{ Str::of((string) $version->status)->replace('_', ' ')->title() }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm font-semibold text-slate-500">No invoice versions yet.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Money story</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">What happened over time?</h2>
                        <p class="mt-1 text-sm text-slate-500">Payments, wallet use, refunds and reversals affecting this order.</p>
                    </div>
                    <a href="{{ route('money-desk.orders.show', $order->id) }}" class="rounded-2xl bg-emerald-50 px-4 py-2 text-xs font-black text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-100">Finance detail ↗</a>
                </div>

                <div class="mt-4 space-y-2">
                    @forelse ($paymentTimeline->take(10) as $event)
                        @php
                            $eventType = (string) ($event->type ?? 'event');
                            $eventLabel = match ($eventType) {
                                'payment' => 'Payment received',
                                'payment_void' => 'Payment reversed',
                                'credit_application' => 'Wallet credit used',
                                'credit_application_void' => 'Wallet use reversed',
                                'refund' => 'Refund recorded',
                                'refund_void' => 'Refund reversed',
                                default => Str::of($eventType)->replace('_', ' ')->title(),
                            };
                            $eventAmount = (float) ($event->amount ?? 0);
                            $eventAmountClasses = $eventAmount < 0 ? 'text-rose-700' : ($eventType === 'credit_application' ? 'text-sky-700' : 'text-emerald-700');
                        @endphp
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-700 ring-1 ring-slate-200">{{ $eventLabel }}</span>
                                    @if ($event->payment_type_name)
                                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700 ring-1 ring-indigo-100">{{ $event->payment_type_name }}</span>
                                    @endif
                                    @if ($event->status)
                                        <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-bold text-slate-500">{{ $event->status }}</span>
                                    @endif
                                </div>
                                <p class="mt-1 truncate text-xs font-semibold text-slate-500">
                                    {{ $event->reference ?: ($event->method ?: 'No reference') }}
                                    @if ($event->provider) · {{ $event->provider }} @endif
                                </p>
                                @if ($event->note)
                                    <p class="mt-1 line-clamp-1 text-xs text-slate-500">{{ $event->note }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black {{ $eventAmountClasses }}">£{{ number_format($eventAmount, 2) }}</p>
                                <p class="mt-0.5 text-[11px] font-semibold text-slate-400">{{ ($event->received_at ?: $event->created_at) ? \Carbon\Carbon::parse($event->received_at ?: $event->created_at)->format('d M Y H:i') : 'No date' }}</p>
                                @if (($event->type ?? '') === 'payment' && ($event->status ?? '') === 'recorded' && empty($event->has_void))
                                    <button type="button" @click="$dispatch('open-reverse-payment-modal', { action: '{{ route('orders.payments.void', [$order->id, $event->id]) }}', label: '£{{ number_format((float) $event->amount, 2) }} · {{ addslashes($event->payment_type_name ?: 'Payment') }}' })" class="mt-2 rounded-xl border border-rose-200 bg-white px-3 py-1 text-[11px] font-black text-rose-700 hover:bg-rose-50">Reverse</button>
                                @elseif (($event->type ?? '') === 'payment' && ! empty($event->has_void))
                                    <p class="mt-2 text-[11px] font-black uppercase tracking-wide text-slate-400">Reversed</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm font-semibold text-slate-500">No finance events recorded yet.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <div x-show="tab === 'items'" x-cloak>
        <section class="w-full overflow-hidden rounded-3xl bg-white p-5 shadow-sm border border-slate-200">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Retailers &amp; Items</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Product links, purchase progress and arrival progress are grouped by retailer.
                        </p>
                    </div>

                    <span class="text-sm text-slate-400">
                        {{ $retailerGroups->count() }} retailer group(s)
                    </span>
                </div>

                <div class="mt-6 space-y-6">
                    @forelse ($retailerGroups as $group)
                        <div class="overflow-hidden rounded-3xl border border-slate-200">
                            <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="font-bold text-slate-950">{{ $group->name }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $group->item_count }} line(s) · Qty {{ $group->total_qty }}
                                        @if ($group->host && $group->host !== $group->name)
                                            · {{ $group->host }}
                                        @endif
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2 text-xs font-semibold">
                                    <span class="rounded-full bg-white px-3 py-2 text-slate-700 ring-1 ring-slate-200">
                                        Total £{{ number_format($group->line_total ?? 0, 2) }}
                                    </span>

                                    <span class="rounded-full bg-emerald-100 px-3 py-2 text-emerald-700">
                                        Purchased {{ $group->purchased_qty }}/{{ $group->total_qty }}
                                    </span>

                                    @if ($group->remaining_qty > 0)
                                        <span class="rounded-full bg-rose-100 px-3 py-2 text-rose-700">
                                            Remaining {{ $group->remaining_qty }}
                                        </span>
                                    @endif

                                    <span class="rounded-full bg-sky-100 px-3 py-2 text-sky-700">
                                        Arrived {{ $group->arrived_qty }}/{{ $group->total_qty }}
                                    </span>
                                </div>
                            </div>

                            <div class="divide-y divide-slate-100">
                                @foreach ($group->items as $item)
                                    <div class="p-5 {{ $item->requires_inspection ? 'bg-purple-50/60' : 'bg-white' }}">
                                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-center">
                                            <div class="lg:col-span-5">
                                                <div class="flex items-start gap-3">
                                                    @if ($item->product_url)
                                                        <a
                                                            href="{{ $item->product_url }}"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            title="Open product page"
                                                            class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 text-lg font-bold text-indigo-700 hover:bg-indigo-100"
                                                        >
                                                            ↗
                                                        </a>
                                                    @else
                                                        <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-300">
                                                            —
                                                        </span>
                                                    @endif

                                                    <div>
                                                        <h4 class="font-bold text-slate-950">{{ $item->item_name }}</h4>

                                                        <div class="mt-2 flex flex-wrap gap-2">
                                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                                Qty {{ $item->quantity }}
                                                            </span>

                                                            @if ($item->product_code)
                                                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                                    {{ $item->product_code }}
                                                                </span>
                                                            @endif

                                                            @if ($item->requires_inspection)
                                                                <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700">
                                                                    Purple check
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="lg:col-span-2">
                                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Purchase</p>
                                                <p class="mt-1 font-semibold {{ $item->purchase_remaining_qty > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                                    {{ $item->purchased_qty }}/{{ $item->quantity }}
                                                </p>
                                                <p class="mt-1 text-xs text-slate-400">
                                                    {{ $isCustomerSelfPurchase ? 'Bought by customer' : ($item->purchase_remaining_qty > 0 ? 'Pending purchase' : 'Purchased') }}
                                                </p>
                                            </div>

                                            <div class="lg:col-span-2">
                                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Arrival</p>
                                                <p class="mt-1 font-semibold {{ $item->arrived_qty > 0 ? 'text-sky-600' : 'text-slate-500' }}">
                                                    {{ $item->arrived_qty }}/{{ $item->quantity }}
                                                </p>
                                                <p class="mt-1 text-xs text-slate-400">
                                                    {{ $item->latest_arrival_status ? str_replace('_', ' ', $item->latest_arrival_status) : 'Not arrived' }}
                                                </p>
                                            </div>

                                            <div class="lg:col-span-3 lg:text-right">
                                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Price</p>
                                                <p class="mt-1 text-lg font-bold text-slate-950">
                                                    £{{ number_format($item->line_total ?? 0, 2) }}
                                                </p>

                                                @if ($item->latest_retailer_order_reference || $item->retailer_order_reference)
                                                    <p class="mt-1 text-xs text-slate-500">
                                                        Ref: {{ $item->latest_retailer_order_reference ?: $item->retailer_order_reference }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>

                                        @if ($item->inspection_note)
                                            <p class="mt-4 rounded-2xl bg-purple-100 px-4 py-3 text-sm text-purple-800">
                                                {{ $item->inspection_note }}
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                            No items found for this order.
                        </div>
                    @endforelse
                </div>
        </section>

        </div>

        <div x-show="tab === 'arrival'" x-cloak>
            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Arrival / collection events</h2>
                        <p class="mt-1 text-sm text-slate-500">Key lifecycle dates for received goods.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $arrivals->count() }} event{{ $arrivals->count() === 1 ? '' : 's' }}</span>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($arrivals->take(12) as $arrival)
                        <div class="rounded-2xl border border-slate-200 p-4 {{ $arrival->requires_marking_attention ? 'border-purple-300 bg-purple-50/60' : '' }}">
                            <p class="font-semibold text-slate-900">{{ $arrival->item_name }}</p>
                            <p class="mt-1 text-sm text-slate-500">Qty {{ $arrival->qty }} · Current status: {{ str_replace('_', ' ', $arrival->status) }}</p>

                            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl bg-sky-50 px-3 py-3 ring-1 ring-sky-100">
                                    <p class="text-[11px] font-black uppercase tracking-wide text-sky-700">Arrived</p>
                                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $arrival->matched_at ? \Carbon\Carbon::parse($arrival->matched_at)->format('d M Y') : 'Not recorded' }}</p>
                                </div>

                                <div class="rounded-2xl bg-purple-50 px-3 py-3 ring-1 ring-purple-100">
                                    <p class="text-[11px] font-black uppercase tracking-wide text-purple-700">Informed / ready</p>
                                    <p class="mt-1 text-sm font-bold text-slate-900">{{ ! empty($arrival->informed_at) ? \Carbon\Carbon::parse($arrival->informed_at)->format('d M Y') : 'Not recorded' }}</p>
                                </div>

                                <div class="rounded-2xl bg-emerald-50 px-3 py-3 ring-1 ring-emerald-100">
                                    <p class="text-[11px] font-black uppercase tracking-wide text-emerald-700">{{ $arrival->completion_label ?? 'Collected / delivered' }}</p>
                                    <p class="mt-1 text-sm font-bold text-slate-900">{{ ! empty($arrival->completed_at) ? \Carbon\Carbon::parse($arrival->completed_at)->format('d M Y') : 'Not recorded' }}</p>
                                </div>
                            </div>

                            @if ($arrival->notes)
                                <p class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-600">
                                    {{ $arrival->notes }}
                                </p>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                            No arrival events yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div x-show="tab === 'notes'" x-cloak class="space-y-5">
            <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Order notes</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">Operational timeline</h2>
                        <p class="mt-1 text-sm text-slate-500">Use this for order-level staff notes after the order snapshot has been created.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $userOrderNotes->count() }} note{{ $userOrderNotes->count() === 1 ? '' : 's' }}</span>
                </div>

                <form method="POST" action="{{ route('orders.notes.store', $order->id) }}" class="mt-4 rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                    @csrf
                    <label class="text-[11px] font-black uppercase tracking-wide text-indigo-800">Add order note</label>
                    <textarea name="body" rows="3" required minlength="2" maxlength="5000" placeholder="Supplier update, customer call, internal instruction…" class="mt-1 w-full rounded-2xl border border-indigo-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('body') }}</textarea>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <label class="flex cursor-pointer items-center gap-2 text-xs font-bold text-indigo-900">
                            <input type="checkbox" name="is_pinned" value="1" class="rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500">
                            Pin this note
                        </label>
                        <button type="submit" class="rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-indigo-700">Add note</button>
                    </div>
                </form>

                <div class="mt-5 space-y-3">
                    @forelse ($userOrderNotes->take(12) as $note)
                        <div class="rounded-2xl border {{ $note->is_pinned ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white' }} p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($note->is_pinned)
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700">pinned</span>
                                @endif
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ str_replace('_', ' ', $note->type) }}</span>
                                @if ($note->title)
                                    <span class="text-sm font-black text-slate-900">{{ $note->title }}</span>
                                @endif
                            </div>

                            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $note->body }}</p>

                            <p class="mt-3 text-xs font-semibold text-slate-400">
                                {{ ($note->occurred_at ?: $note->created_at) ? \Carbon\Carbon::parse($note->occurred_at ?: $note->created_at)->format('d M Y H:i') : 'No date' }}
                            </p>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                            No order notes yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        @if (($revisionHistory ?? collect())->count() > 1)
            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Revision history</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">Order #{{ $order->order_number }} has {{ ($revisionHistory ?? collect())->count() }} saved revision snapshots</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Audit trail</span>
                </div>

                <div class="mt-5 space-y-3">
                    @foreach ($revisionHistory as $revision)
                        @php
                            $isCurrentRevision = (int) $revision->id === (int) $order->id;
                            $isSupersededRevision = ($revision->revision_state ?? '') === 'superseded';
                        @endphp
                        <div class="flex flex-col gap-3 rounded-2xl border {{ $isCurrentRevision ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50' }} px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-black text-slate-950">Rev {{ $revision->revision_number }} of {{ $revision->revision_total }}</p>
                                    @if ($isCurrentRevision)
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">Viewing now</span>
                                    @elseif ($isSupersededRevision)
                                        <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-black text-rose-700">Superseded</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Current</span>
                                    @endif
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600">{{ str_replace('_', ' ', $revision->status) }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    Created {{ $revision->created_at ? \Carbon\Carbon::parse($revision->created_at)->format('d M Y H:i') : 'date unknown' }} · Total £{{ number_format($revision->grand_total ?? 0, 2) }}
                                </p>
                                @if (! empty($revision->revision_note))
                                    <p class="mt-1 text-xs text-slate-500 line-clamp-2">{{ $revision->revision_note }}</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if (! $isCurrentRevision)
                                    <a href="{{ route('orders.show', $revision->id) }}" class="rounded-2xl bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-indigo-50 hover:text-indigo-700">View snapshot</a>
                                @endif
                                @if (! empty($revision->draft_order_id))
                                    <a href="{{ route('draft-orders.show', $revision->draft_order_id) }}" class="rounded-2xl bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-slate-100">Open Draft</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        </div>
    </div>
        <div x-data="{ invoiceOpen: false }" @open-invoice-modal.window="invoiceOpen = true" x-cloak x-show="invoiceOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <div @click.away="invoiceOpen = false" class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-700">Invoice workspace</p>
                        <h3 class="mt-1 text-xl font-black text-slate-950">{{ $hasInvoiceWorkspace ? 'Create invoice version' : 'Create invoice' }}</h3>
                        <p class="mt-1 text-sm font-semibold text-slate-500">This creates an issued invoice snapshot from the current order totals. PDF/email sending comes next.</p>
                    </div>
                    <button type="button" @click="invoiceOpen = false" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-xl font-black text-slate-500 hover:bg-slate-50">×</button>
                </div>

                <form method="POST" action="{{ route('orders.invoices.store', $order->id) }}" class="mt-5 space-y-4">
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Invoice no.</p>
                            <p class="mt-1 text-sm font-black text-slate-950">{{ $invoiceNumber }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Next version</p>
                            <p class="mt-1 text-sm font-black text-slate-950">v{{ ($invoiceVersions->max('version') ?? 0) + 1 }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Total</p>
                            <p class="mt-1 text-sm font-black text-slate-950">£{{ number_format($orderTotal, 2) }}</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold leading-6 text-amber-950">
                        @if ($hasInvoiceWorkspace)
                            A previous invoice version already exists. Creating a new version preserves the older snapshot for history.
                        @else
                            This will mark the order as invoiced and create the first invoice version. It will not generate or send a PDF yet.
                        @endif
                    </div>

                    <div>
                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Customer note for invoice snapshot</label>
                        <textarea name="customer_note" rows="3" maxlength="2000" placeholder="Optional note to carry on the invoice snapshot…" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-amber-500 focus:ring-amber-500">{{ old('customer_note') }}</textarea>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" @click="invoiceOpen = false" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="rounded-2xl bg-amber-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-amber-700">{{ $hasInvoiceWorkspace ? 'Create new version' : 'Create invoice' }}</button>
                    </div>
                </form>
            </div>
        </div>


        <div x-data="{ paymentOpen: false }" @open-payment-modal.window="paymentOpen = true" x-cloak x-show="paymentOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <div @click.away="paymentOpen = false" class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-700">Record payment</p>
                        <h3 class="mt-1 text-xl font-black text-slate-950">Order #{{ $order->order_number }}</h3>
                        <p class="mt-1 text-sm font-semibold text-slate-500">Outstanding balance: £{{ number_format($balanceDue, 2) }}</p>
                    </div>
                    <button type="button" @click="paymentOpen = false" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-xl font-black text-slate-500 hover:bg-slate-50">×</button>
                </div>

                <form method="POST" action="{{ route('orders.payments.store', $order->id) }}" class="mt-5 space-y-4" x-data="{ amount: '{{ old('amount', $balanceDue > 0 ? number_format($balanceDue, 2, '.', '') : '') }}', balanceDue: {{ json_encode($balanceDue) }}, overpaymentConfirm: false, overpaymentConfirmed: false, get numericAmount() { const value = parseFloat(this.amount || '0'); return Number.isFinite(value) ? value : 0; }, get appliedAmount() { return Math.min(this.numericAmount, this.balanceDue); }, get overpaymentAmount() { return Math.max(0, this.numericAmount - this.balanceDue); }, money(value) { return '£' + Number(value || 0).toFixed(2); }, submitPayment(form) { if (this.overpaymentAmount > 0 && ! this.overpaymentConfirmed) { this.overpaymentConfirm = true; return; } form.submit(); } }" @submit.prevent="submitPayment($el)">
                    @csrf
                    <input type="hidden" name="confirmed_overpayment" :value="overpaymentConfirmed ? 1 : 0">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-slate-500">Amount</label>
                            <input name="amount" x-model="amount" type="number" step="0.01" min="0.01" required class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-slate-500">Received date</label>
                            <input name="received_at" type="datetime-local" value="{{ old('received_at', now()->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Payment type</label>
                        <select name="payment_type" required class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach ($paymentTypeOptions as $paymentTypeOption)
                                <option value="{{ $paymentTypeOption }}" @selected(old('payment_type', 'Online Payment Link (Card)') === $paymentTypeOption)>{{ $paymentTypeOption }}{{ $loop->first ? ' · most common' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Reference</label>
                        <input name="reference" value="{{ old('reference') }}" placeholder="Gateway transaction ID, BACS ref, receipt number…" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Notes</label>
                        <textarea name="note" rows="3" maxlength="255" placeholder="Optional internal note…" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:ring-emerald-500">{{ old('note') }}</textarea>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold leading-5 text-slate-600">
                        <template x-if="numericAmount <= 0">
                            <span>Enter the payment amount received from the customer.</span>
                        </template>
                        <template x-if="numericAmount > 0 && overpaymentAmount <= 0">
                            <span><strong x-text="money(appliedAmount)"></strong> will be applied to this order.</span>
                        </template>
                        <template x-if="overpaymentAmount > 0">
                            <span class="text-amber-900">
                                <strong>Overpayment warning:</strong>
                                <span x-text="money(appliedAmount)"></span> will settle this order and
                                <span class="font-black" x-text="money(overpaymentAmount)"></span>
                                will be moved to the customer wallet.
                            </span>
                        </template>
                    </div>

                    <div x-cloak x-show="overpaymentConfirm" x-transition.opacity class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-700">Overpayment confirmation required</p>
                        <h4 class="mt-1 text-base font-black text-slate-950">This payment is more than the outstanding balance.</h4>
                        <div class="mt-3 grid gap-2 sm:grid-cols-3">
                            <div class="rounded-xl bg-white px-3 py-2 ring-1 ring-amber-100">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Outstanding</p>
                                <p class="font-black" x-text="money(balanceDue)"></p>
                            </div>
                            <div class="rounded-xl bg-white px-3 py-2 ring-1 ring-amber-100">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Applied to order</p>
                                <p class="font-black text-emerald-700" x-text="money(appliedAmount)"></p>
                            </div>
                            <div class="rounded-xl bg-white px-3 py-2 ring-1 ring-amber-100">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">To customer wallet</p>
                                <p class="font-black text-sky-700" x-text="money(overpaymentAmount)"></p>
                            </div>
                        </div>
                        <p class="mt-3 font-semibold">Are you sure you want to record this overpayment and add the surplus to the customer's wallet?</p>
                        <div class="mt-4 flex flex-wrap justify-end gap-3">
                            <button type="button" @click="overpaymentConfirm = false" class="rounded-xl border border-amber-200 bg-white px-4 py-2 text-xs font-black text-amber-800 hover:bg-amber-100">Go back</button>
                            <button type="button" @click="overpaymentConfirmed = true; overpaymentConfirm = false; $nextTick(() => $el.closest('form').submit())" class="rounded-xl bg-amber-600 px-4 py-2 text-xs font-black text-white hover:bg-amber-700">Yes, record overpayment</button>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" @click="paymentOpen = false" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-emerald-700"><span x-text="overpaymentAmount > 0 ? 'Review overpayment' : 'Save payment'"></span></button>
                    </div>
                </form>
            </div>
        </div>

        <div x-data="{ voidPaymentAction: null, reversePaymentLabel: null }" @open-reverse-payment-modal.window="voidPaymentAction = $event.detail.action; reversePaymentLabel = $event.detail.label" x-cloak x-show="voidPaymentAction" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <div @click.away="voidPaymentAction = null" class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-rose-700">Reverse payment</p>
                        <h3 class="mt-1 text-xl font-black text-slate-950">Reverse this payment?</h3>
                        <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">DabbaDesk will not delete history. It will record reversal rows, update the order balance, and void unused overpayment wallet credit created by this payment.</p>
                    </div>
                    <button type="button" @click="voidPaymentAction = null" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-xl font-black text-slate-500 hover:bg-slate-50">×</button>
                </div>

                <form method="POST" :action="voidPaymentAction" class="mt-5 space-y-4">
                    @csrf
                    <div class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-900" x-show="reversePaymentLabel">
                        Reversing: <span x-text="reversePaymentLabel"></span>
                    </div>
                    <div>
                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Reason</label>
                        <select name="reversal_reason" required class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 focus:border-rose-500 focus:ring-rose-500">
                            <option value="">Choose reason…</option>
                            <option value="Wrong order">Wrong order</option>
                            <option value="Wrong customer">Wrong customer</option>
                            <option value="Duplicate payment">Duplicate payment</option>
                            <option value="Data entry error">Data entry error</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Notes</label>
                        <textarea name="reversal_note" rows="3" maxlength="255" placeholder="Optional explanation for the audit trail…" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-rose-500 focus:ring-rose-500"></textarea>
                    </div>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" @click="voidPaymentAction = null; reversePaymentLabel = null" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">Keep payment</button>
                        <button type="submit" class="rounded-2xl bg-rose-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-rose-700">Reverse payment</button>
                    </div>
                </form>
            </div>
        </div>

    <script>
        document.addEventListener('click', async function (event) {
            const button = event.target.closest('[data-copy-value]');
            if (!button) return;

            const original = button.textContent;
            const value = button.dataset.copyValue || '';

            try {
                await navigator.clipboard.writeText(value);
                button.textContent = '✓';
                button.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
                window.setTimeout(function () {
                    button.textContent = original;
                    button.classList.remove('bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
                }, 1200);
            } catch (error) {
                const textarea = document.createElement('textarea');
                textarea.value = value;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();
                document.execCommand('copy');
                textarea.remove();
                button.textContent = '✓';
                window.setTimeout(function () { button.textContent = original; }, 1200);
            }
        });
    </script>

</x-app-layout>
