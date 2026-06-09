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
        $phoneCountryCode = trim((string) ($order->bill_to_country_phone_code ?? ''));
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
        $walletAvailable = round((float) ($finance['wallet_available'] ?? 0), 2);
        $paymentStatusLabel = $balanceDue <= 0.004 && $orderTotal > 0 ? 'Paid in full' : ($settledTotal > 0 ? 'Partially paid' : 'Awaiting payment');
        $paymentStatusClasses = $balanceDue <= 0.004 && $orderTotal > 0 ? 'bg-emerald-100 text-emerald-700 ring-emerald-200' : ($settledTotal > 0 ? 'bg-amber-100 text-amber-700 ring-amber-200' : 'bg-rose-100 text-rose-700 ring-rose-200');
        $invoiceStatusLabel = $isInvoiced ? 'Invoice issued' : 'Awaiting invoice';
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
    @endphp

    <div class="space-y-6" data-order-copy-scope >
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

        <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <a href="{{ route('orders.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                        ← Back to Orders
                    </a>

                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-bold text-slate-950">
                            Order #{{ $order->order_number }}
                        </h1>

                        <span class="rounded-full {{ $isCustomerSelfPurchase ? 'bg-sky-100 text-sky-700' : 'bg-indigo-100 text-indigo-700' }} px-3 py-1 text-sm font-black">
                            {{ $isCustomerSelfPurchase ? 'Customer self-purchase' : 'Dabba purchase' }}
                        </span>

                        <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">
                            {{ str_replace('_', ' ', ucfirst($order->status)) }}
                        </span>

                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-black text-indigo-700">
                            Rev {{ $order->revision_number ?? 1 }}@if(($order->revision_total ?? 1) > 1) of {{ $order->revision_total }}@endif
                        </span>

                        @if (($order->revision_state ?? 'current') === 'superseded')
                            <span class="rounded-full bg-rose-100 px-3 py-1 text-sm font-black text-rose-700">Superseded</span>
                        @elseif (($order->revision_state ?? 'current') === 'current_revision')
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-black text-emerald-700">Current revision</span>
                        @endif

                        @if (! $isCustomerSelfPurchase && (($progress['remaining_purchase_qty'] ?? 0) > 0))
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-700">
                                {{ $progress['remaining_purchase_qty'] }} still to purchase
                            </span>
                        @endif
                    </div>

                    <p class="mt-2 text-sm text-slate-500">
                        Placed {{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') : 'date unknown' }}
                        · Order ID {{ $order->id }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a
                        href="{{ route('draft-orders.show', $order->draft_order_id) }}"
                        class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                    >
                        Open Draft ↗
                    </a>

                    <a
                        href="{{ route('money-desk.orders.show', $order->id) }}"
                        class="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-700"
                    >
                        Finance ↗
                    </a>

                    <a
                        href="{{ route('money-desk.customers.show', $order->customer_id) }}"
                        class="rounded-2xl bg-indigo-100 px-4 py-3 text-sm font-semibold text-indigo-700 hover:bg-indigo-200"
                    >
                        Customer Finance ↗
                    </a>
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

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
            <div class="xl:col-span-4 rounded-3xl bg-white p-4 shadow-sm border border-slate-200">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">Customer operations</p>
                        <h2 class="mt-1 truncate text-lg font-black text-slate-950">{{ $customerFullName ?: 'Unknown customer' }}</h2>
                        <p class="mt-0.5 text-xs font-semibold text-slate-500">Payment-link copy tools</p>
                    </div>
                    @if (! empty($order->customer_id))
                        <a href="{{ route('customers.edit', $order->customer_id) }}" title="Open customer record" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-600 hover:bg-indigo-100 hover:text-indigo-700">↗</a>
                    @endif
                </div>

                <div class="mt-3 grid gap-2">
                    <div class="rounded-2xl bg-slate-50 px-3 py-2 ring-1 ring-slate-100">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Full name</p>
                                <p class="truncate text-sm font-black text-slate-950">{{ $customerFullName ?: '—' }}</p>
                            </div>
                            @if ($customerFullName)
                                <button type="button" data-copy-value="{{ $customerFullName }}" class="copy-btn inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-indigo-200 bg-white text-xs font-black text-indigo-700 hover:bg-indigo-50" title="Copy full name">📋</button>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-2xl bg-slate-50 px-3 py-2 ring-1 ring-slate-100">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Company</p>
                                <p class="truncate text-sm font-black text-slate-950">{{ $customerCompany ?: '—' }}</p>
                            </div>
                            @if ($customerCompany)
                                <button type="button" data-copy-value="{{ $customerCompany }}" class="copy-btn inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-indigo-200 bg-white text-xs font-black text-indigo-700 hover:bg-indigo-50" title="Copy company">📋</button>
                            @endif
                        </div>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 px-3 py-2 ring-1 ring-slate-100">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Email</p>
                                    <p class="truncate text-sm font-black text-slate-950">{{ $customerEmail ?: '—' }}</p>
                                </div>
                                @if ($customerEmail)
                                    <button type="button" data-copy-value="{{ $customerEmail }}" class="copy-btn inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-indigo-200 bg-white text-xs font-black text-indigo-700 hover:bg-indigo-50" title="Copy email">📋</button>
                                @endif
                            </div>
                        </div>

                        <div class="rounded-2xl bg-slate-50 px-3 py-2 ring-1 ring-slate-100">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Phone</p>
                                    <p class="truncate text-sm font-black text-slate-950">{{ $customerPhone ?: '—' }}</p>
                                </div>
                                @if ($customerPhone)
                                    <button type="button" data-copy-value="{{ $customerPhone }}" class="copy-btn inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-indigo-200 bg-white text-xs font-black text-indigo-700 hover:bg-indigo-50" title="Copy phone">📋</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-4 rounded-3xl bg-white p-4 shadow-sm border border-slate-200">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">Full address</p>
                        <h2 class="mt-1 text-base font-black text-slate-950">Billing / payment details</h2>
                    </div>
                    @if ($copyFullAddress)
                        <button type="button" data-copy-value="{{ $copyFullAddress }}" class="copy-btn rounded-2xl border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-black text-indigo-700 hover:bg-indigo-100" title="Copy full formatted address">📋 Copy</button>
                    @endif
                </div>

                <div class="mt-3 rounded-2xl bg-slate-50 px-4 py-3 text-[13px] leading-5 text-slate-700 ring-1 ring-slate-100">
                    @if ($copyFullAddress)
                        @if ($customerFullName)
                            <p class="font-black text-slate-950">{{ $customerFullName }}</p>
                        @endif
                        @if ($customerCompany)
                            <p class="font-semibold text-slate-800">{{ $customerCompany }}</p>
                        @endif
                        @if ($addressLines->isNotEmpty())
                            <div class="mt-2 space-y-0.5 font-semibold text-slate-600">
                                @foreach ($addressLines as $line)
                                    <p>{{ $line }}</p>
                                @endforeach
                            </div>
                        @else
                            <p class="text-slate-400">No billing address captured.</p>
                        @endif
                    @else
                        <p class="text-slate-400">No customer/address details captured.</p>
                    @endif
                </div>
            </div>

            <div class="xl:col-span-4 rounded-3xl bg-white p-4 shadow-sm border border-slate-200">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">Lifecycle & source</p>
                    <a href="{{ route('money-desk.orders.show', $order->id) }}" class="rounded-xl bg-white px-3 py-1.5 text-xs font-black text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">Finance ↗</a>
                </div>

                <div class="mt-3 grid gap-2 sm:grid-cols-3">
                    <div class="rounded-2xl border border-indigo-200 bg-indigo-50 px-3 py-2">
                        <p class="text-[10px] font-black uppercase tracking-wide text-indigo-600">Request</p>
                        <div class="mt-1 flex items-center justify-between gap-2">
                            <p class="truncate text-sm font-black text-slate-950">{{ $requestRef ?: '—' }}</p>
                            @if (! empty($order->order_request_id))
                                <a href="{{ route('order-requests.show', $order->order_request_id) }}" class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white text-xs font-black text-indigo-700 ring-1 ring-indigo-200 hover:bg-indigo-100" title="Open request">↗</a>
                            @endif
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Draft</p>
                        <div class="mt-1 flex items-center justify-between gap-2">
                            <p class="truncate text-sm font-black text-slate-950">{{ $draftNumber ?: ('#' . $order->draft_order_id) }}</p>
                            <a href="{{ route('draft-orders.show', $order->draft_order_id) }}" class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white text-xs font-black text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100" title="Open draft">↗</a>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2">
                        <p class="text-[10px] font-black uppercase tracking-wide text-emerald-600">Order</p>
                        <p class="mt-1 truncate text-sm font-black text-slate-950">{{ $order->order_number }}</p>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-4 gap-1.5">
                    <div class="rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-center">
                        <p class="text-[9px] font-black uppercase tracking-wide text-slate-400">Items</p>
                        <p class="text-base font-black text-slate-950">{{ $progress['item_qty'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-2 py-1.5 text-center">
                        <p class="text-[9px] font-black uppercase tracking-wide text-emerald-600">Bought</p>
                        <p class="text-base font-black text-emerald-700">{{ $progress['purchased_qty'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl border border-sky-200 bg-sky-50 px-2 py-1.5 text-center">
                        <p class="text-[9px] font-black uppercase tracking-wide text-sky-600">Arrived</p>
                        <p class="text-base font-black text-sky-700">{{ $progress['arrived_qty'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-center">
                        <p class="text-[9px] font-black uppercase tracking-wide text-slate-400">Done</p>
                        <p class="text-base font-black text-slate-950">{{ $progress['collected_qty'] ?? 0 }}</p>
                    </div>
                </div>

                <div class="mt-3 rounded-2xl border {{ ($finance['balance_due'] ?? 0) > 0 ? 'border-rose-200 bg-rose-50' : 'border-emerald-200 bg-emerald-50' }} px-4 py-2.5">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-[11px] font-black uppercase tracking-wide {{ ($finance['balance_due'] ?? 0) > 0 ? 'text-rose-700' : 'text-emerald-700' }}">Balance due</span>
                        <span class="text-lg font-black {{ ($finance['balance_due'] ?? 0) > 0 ? 'text-rose-700' : 'text-emerald-700' }}">£{{ number_format($finance['balance_due'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-12">
            <div class="xl:col-span-8 rounded-3xl bg-white p-5 shadow-sm border border-slate-200">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Order summary</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-5">
                    <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">{{ $isCustomerSelfPurchase ? 'Goods value' : 'Items subtotal' }}</p>
                        <p class="mt-1 text-lg font-black text-slate-950">£{{ number_format($order->subtotal ?? 0, 2) }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Delivery</p>
                        <p class="mt-1 text-lg font-black text-slate-950">£{{ number_format($order->retailer_delivery_fee_total ?? 0, 2) }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Dabba fee</p>
                        <p class="mt-1 text-lg font-black text-slate-950">£{{ number_format($order->dabba_fee_amount ?? 0, 2) }}</p>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 p-4 ring-1 ring-emerald-100">
                        <p class="text-[11px] font-black uppercase tracking-wide text-emerald-700">Paid / settled</p>
                        <p class="mt-1 text-lg font-black text-emerald-700">£{{ number_format($finance['settled_total'] ?? 0, 2) }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-950 p-4 text-white">
                        <p class="text-[11px] font-black uppercase tracking-wide text-white/60">{{ $isCustomerSelfPurchase ? 'Billable total' : 'Total' }}</p>
                        <p class="mt-1 text-xl font-black">£{{ number_format($order->grand_total ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-4 rounded-3xl bg-white p-5 shadow-sm border border-slate-200">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Attachments</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">Request files</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $requestAttachments->count() }}</span>
                </div>
                @if ($requestAttachments->isEmpty())
                    <p class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-500">No request attachments found.</p>
                @else
                    <div class="mt-4 space-y-2">
                        @foreach ($requestAttachments as $attachment)
                            <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-slate-900">{{ $attachment->original_name ?? basename((string) $attachment->path) ?? 'Attachment' }}</p>
                                    <p class="mt-0.5 text-xs font-semibold text-slate-500">{{ $attachment->mime ?? 'file' }}</p>
                                </div>
                                <a href="{{ route('order-requests.attachments.show', [$order->order_request_id, $attachment->id]) }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-indigo-200 bg-white text-base font-black text-indigo-600 hover:bg-indigo-50" title="Open attachment">↗</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>


        <div class="grid grid-cols-1 gap-5 xl:grid-cols-12">
            <div class="xl:col-span-5 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Finance workspace</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">Invoice & payment</h2>
                        <p class="mt-1 text-sm text-slate-500">Routine payment recording now lives on the order page. Money Desk remains the analysis view.</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-black uppercase tracking-wide ring-1 {{ $paymentStatusClasses }}">{{ $paymentStatusLabel }}</span>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Order total</p>
                        <p class="mt-1 text-base font-black text-slate-950">£{{ number_format($orderTotal, 2) }}</p>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 p-3 ring-1 ring-emerald-100">
                        <p class="text-[10px] font-black uppercase tracking-wide text-emerald-700">Paid / settled</p>
                        <p class="mt-1 text-base font-black text-emerald-700">£{{ number_format($settledTotal, 2) }}</p>
                    </div>
                    <div class="rounded-2xl {{ $balanceDue > 0 ? 'bg-rose-50 ring-rose-100' : 'bg-emerald-50 ring-emerald-100' }} p-3 ring-1">
                        <p class="text-[10px] font-black uppercase tracking-wide {{ $balanceDue > 0 ? 'text-rose-700' : 'text-emerald-700' }}">Outstanding</p>
                        <p class="mt-1 text-base font-black {{ $balanceDue > 0 ? 'text-rose-700' : 'text-emerald-700' }}">£{{ number_format($balanceDue, 2) }}</p>
                    </div>
                    <div class="rounded-2xl {{ $walletCreditFromOverpayments > 0 ? 'bg-sky-50 ring-sky-100' : 'bg-slate-50 ring-slate-100' }} p-3 ring-1">
                        <p class="text-[10px] font-black uppercase tracking-wide {{ $walletCreditFromOverpayments > 0 ? 'text-sky-700' : 'text-slate-400' }}">Wallet credit</p>
                        <p class="mt-1 text-base font-black {{ $walletCreditFromOverpayments > 0 ? 'text-sky-700' : 'text-slate-500' }}">£{{ number_format($walletCreditFromOverpayments, 2) }}</p>
                        @if ($walletAvailable > $walletCreditFromOverpayments + 0.004)
                            <p class="mt-1 text-[10px] font-bold text-slate-400">Customer wallet total £{{ number_format($walletAvailable, 2) }}</p>
                        @endif
                    </div>
                </div>

                <div class="mt-4 grid gap-2 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Invoice status</p>
                        <p class="mt-1 text-sm font-black {{ $isInvoiced ? 'text-emerald-700' : 'text-amber-700' }}">{{ $invoiceStatusLabel }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Payment status</p>
                        <p class="mt-1 text-sm font-black {{ $balanceDue > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $paymentStatusLabel }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Purchasing status</p>
                        <p class="mt-1 text-sm font-black text-slate-800">{{ $purchaseStatusLabel }}</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" disabled class="cursor-not-allowed rounded-2xl bg-slate-200 px-4 py-2 text-sm font-black text-slate-500">Create invoice · next</button>
                    <button type="button" @click="$dispatch('open-payment-modal')" class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-emerald-700">Record payment</button>
                    <button type="button" data-copy-value="{{ e($copyPaymentDetails) }}" class="rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-black text-indigo-700 hover:bg-indigo-100">Copy payment details</button>
                </div>

                <p class="mt-3 rounded-2xl bg-slate-50 px-4 py-3 text-xs font-semibold leading-5 text-slate-500">
                    Payment default is <span class="font-black text-slate-800">Online Payment Link (Card)</span>, matching Dabba's normal payment flow.
                </p>
                @if ($walletCreditFromOverpayments > 0)
                    <p class="mt-2 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs font-semibold leading-5 text-sky-900">
                        This order has <span class="font-black">£{{ number_format($walletCreditFromOverpayments, 2) }}</span> in customer wallet credit created from overpayment.
                    </p>
                @endif
            </div>

            <div class="xl:col-span-7 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Payment history</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">Settlement timeline</h2>
                        <p class="mt-1 text-sm text-slate-500">Shows payment, wallet-credit, refund and correction events that affect this order.</p>
                    </div>
                    <a href="{{ route('money-desk.orders.show', $order->id) }}" class="rounded-2xl bg-emerald-50 px-4 py-2 text-xs font-black text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-100">Finance detail ↗</a>
                </div>

                <div class="mt-4 space-y-2">
                    @forelse ($paymentTimeline->take(8) as $event)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-700 ring-1 ring-slate-200">{{ str_replace('_', ' ', ucfirst($event->type)) }}</span>
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
                                <p class="text-sm font-black {{ ((float) $event->amount) < 0 ? 'text-rose-700' : 'text-emerald-700' }}">£{{ number_format((float) $event->amount, 2) }}</p>
                                <p class="mt-0.5 text-[11px] font-semibold text-slate-400">{{ ($event->received_at ?: $event->created_at) ? \Carbon\Carbon::parse($event->received_at ?: $event->created_at)->format('d M Y H:i') : 'No date' }}</p>
                                @if (($event->type ?? '') === 'payment' && ($event->status ?? '') === 'recorded' && empty($event->has_void))
                                    <button type="button" @click="$dispatch('open-reverse-payment-modal', { action: '{{ route('orders.payments.void', [$order->id, $event->id]) }}', label: '£{{ number_format((float) $event->amount, 2) }} · {{ addslashes($event->payment_type_name ?: 'Payment') }}' })" class="mt-2 rounded-xl border border-rose-200 bg-white px-3 py-1 text-[11px] font-black text-rose-700 hover:bg-rose-50">Reverse</button>
                                @elseif (($event->type ?? '') === 'payment' && ! empty($event->has_void))
                                    <p class="mt-2 text-[11px] font-black uppercase tracking-wide text-slate-400">Reversed</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm font-semibold text-slate-500">No settlement events recorded yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <div class="xl:col-span-12 rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
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
            </div>

            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
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