<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Order Request {{ $requestRow->request_ref }}</h2>
                <p class="mt-1 text-sm text-gray-500">Confirm the customer, tidy details, then convert to draft.</p>
            </div>
            <a href="{{ route('order-requests.index') }}" class="rounded-2xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50">Back to requests</a>
        </div>
    </x-slot>

    @php
        $requestName = trim(($requestRow->customer_first_name ?? '') . ' ' . ($requestRow->customer_last_name ?? '')) ?: ($requestRow->customer_company_name ?: 'Unknown customer');
        $defaultMode = old('customer_mode', $selectedCustomer ? 'existing' : 'create');
        $existingCustomerAction = old('existing_customer_action', 'keep');
        $hasCustomerDifferences = ! empty($customerDifferences ?? []);
        $existingFirst = old('first_name', $requestRow->customer_first_name ?? $selectedCustomer->first_name ?? '');
        $existingLast = old('last_name', $requestRow->customer_last_name ?? $selectedCustomer->last_name ?? '');
        $existingCompany = old('company_name', $requestRow->customer_company_name ?? $selectedCustomer->company_name ?? '');
        $existingEmail = old('email', $requestRow->customer_email ?? $selectedCustomer->email ?? '');
        $existingPhone = old('phone_digits', $requestRow->customer_phone_digits ?? $selectedCustomer->phone_digits ?? '');
        $existingPhoneCountry = old('phone_country_id', $requestRow->customer_phone_country_id ?? $selectedCustomer->phone_country_id ?? '');
        $existingAddress = old('address_line1', $requestRow->customer_address_line1 ?? $selectedCustomer->address_line1 ?? '');
        $existingPostcode = old('address_postcode', $requestRow->customer_address_postcode ?? $selectedCustomer->address_postcode ?? '');
        $existingAddressCountry = old('address_country_id', $requestRow->customer_address_country_id ?? $selectedCustomer->address_country_id ?? '');
        $isConverted = ! empty($requestRow->converted_at) || ($requestRow->status ?? '') === 'converted';
        $isCancelled = ($requestRow->status ?? '') === 'cancelled';
        $hasUnresolvedRetailers = isset($unresolvedRetailers) && $unresolvedRetailers->isNotEmpty();
        $isCustomerSelfPurchase = ($requestRow->purchase_mode ?? 'standard') === 'customer_self_purchase';
    @endphp

    <style>[x-cloak] { display: none !important; }</style>

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-800">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
            <section class="space-y-5">

                @if ($isCustomerSelfPurchase)
                    <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 shadow-sm">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-sky-700">Customer self-purchase request</p>
                        <p class="mt-1 text-sm font-semibold leading-6 text-sky-900">Company policy: this request must contain only goods the customer will buy/pay for directly. Dabba will charge service/delivery and manage arrival/collection after goods reach Dabba.</p>
                    </div>
                @endif

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-gray-400">Submitted by customer</p>
                            <h3 class="mt-1 text-2xl font-black text-gray-900">{{ $requestName }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ $requestRow->customer_email ?: 'No email' }} · {{ $requestRow->customer_phone_digits ?: 'No phone' }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($isCustomerSelfPurchase)
                                <span class="rounded-full bg-sky-100 px-4 py-2 text-sm font-black text-sky-800">Customer self-purchase</span>
                            @else
                                <span class="rounded-full bg-indigo-50 px-4 py-2 text-sm font-black text-indigo-700">Dabba purchase</span>
                            @endif
                            <span class="rounded-full px-4 py-2 text-sm font-black {{ $requestRow->converted_at ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ ucfirst($requestRow->status) }}</span>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-3">
                        <div class="rounded-2xl bg-gray-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Company</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $requestRow->customer_company_name ?: '—' }}</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Phone</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $requestRow->customer_phone_digits ? (($requestRow->phone_country_code ? '+' . $requestRow->phone_country_code . ' ' : '') . $requestRow->customer_phone_digits) : '—' }}</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Estimate</p>
                            <p class="mt-1 text-sm font-black text-gray-900">£{{ number_format((float) $requestRow->estimated_total, 2) }}</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 p-4 md:col-span-3">
                            <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Address</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">
                                {{ $requestRow->customer_address_line1 ?: '—' }}
                                @if ($requestRow->customer_address_postcode)<span class="text-gray-500">{{ $requestRow->customer_address_postcode }}</span>@endif
                                @if ($requestRow->address_country_name)<span class="text-gray-500">{{ $requestRow->address_country_name }}</span>@endif
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-2xl border {{ trim((string) ($requestRow->notes ?? '')) !== '' ? 'border-amber-200 bg-amber-50' : 'border-gray-200 bg-gray-50' }} p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-xs font-black uppercase tracking-wide {{ trim((string) ($requestRow->notes ?? '')) !== '' ? 'text-amber-700' : 'text-gray-500' }}">Customer order request notes</p>
                            <span class="rounded-full bg-white px-3 py-1 text-[11px] font-black uppercase tracking-wide {{ trim((string) ($requestRow->notes ?? '')) !== '' ? 'text-amber-700 ring-1 ring-amber-200' : 'text-gray-400 ring-1 ring-gray-200' }}">
                                {{ trim((string) ($requestRow->notes ?? '')) !== '' ? 'carried through lifecycle' : 'none supplied' }}
                            </span>
                        </div>
                        @if (trim((string) ($requestRow->notes ?? '')) !== '')
                            <p class="mt-2 whitespace-pre-wrap text-sm leading-6 text-amber-950">{{ $requestRow->notes }}</p>
                            <p class="mt-3 text-xs font-semibold text-amber-800">These notes are copied into the draft notes and remain visible on the final order timeline.</p>
                        @else
                            <p class="mt-2 text-sm text-gray-500">The customer did not add order-level notes to this request.</p>
                        @endif
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                        <div>
                            <h3 class="text-lg font-black text-gray-900">Submitted items</h3>
                            <p class="text-sm text-gray-500">Compact review; detailed polishing still happens in the draft workbench.</p>
                        </div>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-black text-gray-600">{{ $items->count() }} item{{ $items->count() === 1 ? '' : 's' }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Item</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Retailer</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-gray-500">Qty</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-gray-500">Unit</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-gray-500">Line</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($items as $item)
                                    <tr>
                                        <td class="px-4 py-3 align-top">
                                            <div class="max-w-xl text-sm font-bold text-gray-900">{{ $item->description }}</div>
                                            <div class="mt-1 flex flex-wrap gap-2 text-xs text-gray-500">
                                                @if ($item->product_code)<span>Code: {{ $item->product_code }}</span>@endif
                                                @if ($item->retailer_url)
                                                    <a href="{{ $item->retailer_url }}" target="_blank" rel="noopener noreferrer" aria-label="Open product link" title="Open product link" class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-lg font-black leading-none text-blue-600 shadow-sm hover:bg-blue-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">↗</a>
                                                @endif
                                            </div>
                                            @if ($item->notes)<div class="mt-2 rounded-xl bg-gray-50 p-2 text-xs text-gray-600">{{ $item->notes }}</div>@endif
                                        </td>
                                        <td class="px-4 py-3 align-top text-sm text-gray-700">
                                            <div class="font-bold">{{ $item->matched_retailer_name ?: ($item->retailer_name ?: 'Needs review') }}</div>
                                            @if (! $item->matched_retailer_name)<div class="mt-1 inline-flex rounded-full bg-orange-100 px-2 py-1 text-xs font-bold text-orange-800">Needs retailer setup</div>@endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right align-top text-sm font-bold text-gray-900">{{ $item->quantity }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right align-top text-sm text-gray-700">£{{ number_format((float) $item->unit_price, 2) }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right align-top text-sm font-black text-gray-900">£{{ number_format((float) ($item->line_total ?? ($item->unit_price * $item->quantity)), 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <aside class="space-y-5 xl:sticky xl:top-6 xl:self-start">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-black text-gray-900">Customer & conversion</h3>
                            <p class="mt-1 text-sm text-gray-500">Auto-match is retained, but staff can edit before draft creation.</p>
                        </div>
                    </div>

                    @if ($isConverted)
                        <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                            <p class="font-black">Already converted</p>
                            <p class="mt-1">Draft order ID: {{ $requestRow->converted_draft_order_id }}</p>
                            <p class="mt-1 text-xs">Converted at {{ $requestRow->converted_at }}</p>
                            @if ($requestRow->converted_draft_order_id)
                                <a href="{{ route('draft-orders.show', $requestRow->converted_draft_order_id) }}" class="mt-3 inline-flex rounded-2xl bg-emerald-600 px-4 py-2 text-xs font-black text-white hover:bg-emerald-700">Open Draft</a>
                            @endif
                        </div>
                    @elseif ($isCancelled)
                        <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                            <p class="font-black">Request cancelled</p>
                            <p class="mt-1">This request will not be converted to a draft.</p>
                            @if (! empty($cancellationLog?->body))
                                <div class="mt-3 rounded-xl bg-white/70 p-3 text-xs leading-5 text-rose-900 ring-1 ring-rose-100">
                                    <p class="font-black uppercase tracking-wide text-rose-700">Cancellation reason</p>
                                    <p class="mt-1 whitespace-pre-line">{{ $cancellationLog->body }}</p>
                                </div>
                            @endif
                        </div>
                    @else
                        @if (! $requestRow->reviewed_at)
                            <form method="POST" action="{{ route('order-requests.review', $requestRow->id) }}" class="mt-4">
                                @csrf
                                <button type="submit" class="w-full rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-black text-indigo-700 hover:bg-indigo-100">Mark as under review</button>
                            </form>
                        @endif

                        <details class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4">
                            <summary class="cursor-pointer text-sm font-black text-rose-800">Cancel this request</summary>
                            <form method="POST" action="{{ route('order-requests.cancel', $requestRow->id) }}" class="mt-3 space-y-3" onsubmit="return confirm('Cancel this order request? This prevents conversion to draft.');">
                                @csrf
                                <div>
                                    <label class="text-xs font-black uppercase tracking-wide text-rose-700">Reason</label>
                                    <textarea name="cancel_reason" rows="3" required minlength="3" placeholder="Customer changed mind, duplicate request, submitted by mistake…" class="mt-1 w-full rounded-2xl border border-rose-200 bg-white px-4 py-3 text-sm focus:border-rose-500 focus:ring-rose-500">{{ old('cancel_reason') }}</textarea>
                                </div>
                                <button type="submit" class="w-full rounded-2xl bg-rose-600 px-4 py-3 text-sm font-black text-white hover:bg-rose-700">Cancel order request</button>
                            </form>
                        </details>

                        @if ($hasUnresolvedRetailers)
                            <section id="retailer-review-queue" class="mt-4 overflow-hidden rounded-3xl border border-amber-300 bg-white shadow-sm" x-data="retailerReviewQueue(@js($unresolvedRetailers->values()))">
                                <div class="border-b border-amber-200 bg-amber-50 p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="max-w-2xl">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full bg-amber-600 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-white">Action needed</span>
                                                <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-amber-800 ring-1 ring-amber-200">
                                                    {{ $unresolvedRetailers->count() }} unknown retailer{{ $unresolvedRetailers->count() === 1 ? '' : 's' }}
                                                </span>
                                            </div>
                                            <h3 class="mt-3 text-lg font-black text-gray-950">Retailer review required</h3>
                                            <p class="mt-1 text-sm leading-6 text-amber-900">
                                                Conversion is paused until each unknown retailer is linked to the retailer table. The list below stays compact; click <span class="font-black">Review</span> to check one retailer at a time.
                                            </p>
                                        </div>
                                        <div class="rounded-2xl bg-white px-4 py-3 text-center ring-1 ring-amber-200">
                                            <p class="text-[11px] font-black uppercase tracking-wide text-amber-700">Convert button</p>
                                            <p class="mt-1 text-sm font-black text-gray-950">Locked</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="divide-y divide-gray-100 bg-white">
                                    @foreach ($unresolvedRetailers as $loopIndex => $retailer)
                                        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                                            <div class="min-w-0">
                                                <p class="text-[11px] font-black uppercase tracking-wide text-gray-400">Unknown retailer {{ $loop->iteration }} of {{ $unresolvedRetailers->count() }}</p>
                                                <h4 class="mt-1 truncate text-base font-black text-gray-950">{{ $retailer['base_url'] ?: $retailer['name'] }}</h4>
                                                <p class="mt-1 text-sm text-gray-600">
                                                    Found on <span class="font-black text-gray-900">{{ $retailer['items_count'] ?? 1 }}</span> request item{{ ($retailer['items_count'] ?? 1) === 1 ? '' : 's' }}
                                                    @if (! empty($retailer['urls']))
                                                        · {{ count($retailer['urls']) }} source link{{ count($retailer['urls']) === 1 ? '' : 's' }}
                                                    @endif
                                                </p>
                                            </div>
                                            <button type="button" @click="open({{ $loopIndex }})" class="rounded-2xl bg-gray-900 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
                                                Review
                                            </button>
                                        </div>
                                    @endforeach
                                </div>

                                <div x-cloak x-show="isOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4">
                                    <div @click.away="close()" class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white shadow-2xl ring-1 ring-gray-200">
                                        <form method="POST" action="{{ route('order-requests.retailers.store', $requestRow->id) }}" class="p-5 sm:p-6">
                                            @csrf
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <p class="text-xs font-black uppercase tracking-wide text-amber-700" x-text="currentLabel"></p>
                                                    <h3 class="mt-1 text-xl font-black text-gray-950">Unknown retailer</h3>
                                                    <p class="mt-1 text-sm text-gray-600">
                                                        Check the display name and base domain, then add it to the retailer table and link matching request items.
                                                    </p>
                                                </div>
                                                <button type="button" @click="close()" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-gray-200 bg-white text-xl font-black text-gray-500 hover:bg-gray-50" aria-label="Close retailer review">×</button>
                                            </div>

                                            <div class="mt-5 space-y-4">
                                                <div>
                                                    <label class="block text-[11px] font-black uppercase tracking-wide text-gray-500">Retailer name</label>
                                                    <input name="name" required :value="current?.name || ''" placeholder="Mobiquip" class="mt-1 w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                                    <p class="mt-1 text-xs text-gray-500">This is the name staff will see in drafts and purchasing.</p>
                                                </div>

                                                <div>
                                                    <label class="block text-[11px] font-black uppercase tracking-wide text-gray-500">Base domain</label>
                                                    <input name="base_url" required :value="current?.base_url || ''" placeholder="mobiquip.co.uk" class="mt-1 w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                                    <p class="mt-1 text-xs text-gray-500">Use only the shop domain, not the full product page.</p>
                                                </div>
                                            </div>

                                            <details class="mt-5 rounded-2xl border border-gray-200 bg-gray-50 p-3">
                                                <summary class="cursor-pointer text-xs font-black uppercase tracking-wide text-gray-500">
                                                    Source links (<span x-text="sourceCount"></span>)
                                                </summary>
                                                <div class="mt-3 space-y-2">
                                                    <template x-for="sourceUrl in (current?.urls || [])" :key="sourceUrl">
                                                        <div class="flex items-start gap-2 rounded-xl bg-white p-2 ring-1 ring-gray-100">
                                                            <a :href="sourceUrl" target="_blank" rel="noopener noreferrer" aria-label="Open source link" title="Open source link" class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-lg font-black leading-none text-blue-600 shadow-sm hover:bg-blue-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">↗</a>
                                                            <span class="min-w-0 break-all text-xs leading-5 text-gray-600" x-text="sourceUrl"></span>
                                                        </div>
                                                    </template>
                                                    <p x-show="sourceCount === 0" class="text-xs text-gray-500">No source URL was supplied for this item.</p>
                                                </div>
                                            </details>

                                            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                                <button type="button" @click="close()" class="rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-black text-gray-700 hover:bg-gray-50">Cancel</button>
                                                <button type="submit" class="rounded-2xl bg-amber-600 px-5 py-3 text-sm font-black text-white shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                                                    Add & link retailer
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </section>

                            <script>
                                document.addEventListener('alpine:init', () => {
                                    Alpine.data('retailerReviewQueue', (retailers) => ({
                                        retailers: retailers || [],
                                        currentIndex: null,
                                        get isOpen() { return this.currentIndex !== null; },
                                        get current() { return this.currentIndex === null ? null : this.retailers[this.currentIndex]; },
                                        get sourceCount() { return (this.current?.urls || []).length; },
                                        get currentLabel() { return this.currentIndex === null ? '' : `Unknown retailer ${this.currentIndex + 1} of ${this.retailers.length}`; },
                                        open(index) { this.currentIndex = index; },
                                        close() { this.currentIndex = null; },
                                    }));
                                });
                            </script>
                        @endif

                        <form method="GET" action="{{ route('order-requests.show', $requestRow->id) }}" class="mt-4 rounded-2xl border border-gray-200 p-4">
                            <label class="text-xs font-black uppercase tracking-wide text-gray-500">Search customer base</label>
                            <div class="mt-2 flex gap-2">
                                <input type="search" name="customer_q" value="{{ $customerSearch }}" placeholder="Name, email, phone, company…" class="min-w-0 flex-1 rounded-2xl border border-gray-200 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <button type="submit" class="rounded-2xl bg-gray-900 px-4 py-3 text-sm font-black text-white hover:bg-gray-800">Find</button>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">{{ $customerSearch !== '' ? 'Search results shown below.' : 'Suggested matches are auto-selected from request details.' }}</p>
                        </form>

                        <form method="POST" action="{{ route('order-requests.convert', $requestRow->id) }}" class="mt-4 space-y-4">
                            @csrf

                            <div class="rounded-2xl border border-gray-200 p-4">
                                <label class="flex cursor-pointer items-start gap-3">
                                    <input type="radio" name="customer_mode" value="existing" class="mt-1" @checked($defaultMode === 'existing')>
                                    <span>
                                        <span class="block text-sm font-black text-gray-900">Use existing customer</span>
                                        <span class="block text-xs text-gray-500">Select, then edit the fields below if phone, email or address changed.</span>
                                    </span>
                                </label>

                                <div class="mt-3 flex gap-2">
                                    <select name="customer_id" class="min-w-0 flex-1 rounded-2xl border border-gray-200 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500" onchange="const u=new URL(window.location.href); u.searchParams.set('customer_id', this.value); window.location.href=u.toString();">
                                        <option value="">Select customer…</option>
                                        @foreach ($customerOptions as $customer)
                                            @php $customerName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: ($customer->company_name ?: 'Customer #' . $customer->id); @endphp
                                            <option value="{{ $customer->id }}" @selected((int) $selectedCustomerId === (int) $customer->id)>#{{ $customer->id }} — {{ $customerName }}{{ $customer->email ? ' — ' . $customer->email : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                @if ($selectedCustomer)
                                    <div class="mt-3 rounded-2xl bg-emerald-50 p-3 text-xs text-emerald-900">
                                        <strong>Selected:</strong> #{{ $selectedCustomer->id }} — {{ trim(($selectedCustomer->first_name ?? '') . ' ' . ($selectedCustomer->last_name ?? '')) ?: $selectedCustomer->company_name }}
                                    </div>
                                @elseif ($customerOptions->isEmpty())
                                    <p class="mt-2 text-xs font-semibold text-amber-700">No match yet. Search again or use create new below.</p>
                                @endif


                                @if ($selectedCustomer)
                                    <div class="mt-4 rounded-2xl border {{ $hasCustomerDifferences ? 'border-amber-300 bg-amber-50' : 'border-emerald-200 bg-emerald-50' }} p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <h4 class="text-sm font-black {{ $hasCustomerDifferences ? 'text-amber-950' : 'text-emerald-950' }}">
                                                    {{ $hasCustomerDifferences ? 'Customer details differ from stored record' : 'Submitted details match the selected customer' }}
                                                </h4>
                                                <p class="mt-1 text-xs {{ $hasCustomerDifferences ? 'text-amber-800' : 'text-emerald-800' }}">
                                                    Existing customers are now kept unchanged unless you deliberately choose to update them.
                                                </p>
                                            </div>
                                            <span class="rounded-full bg-white px-2 py-1 text-[11px] font-black uppercase tracking-wide {{ $hasCustomerDifferences ? 'text-amber-700' : 'text-emerald-700' }}">
                                                {{ $hasCustomerDifferences ? count($customerDifferences) . ' difference' . (count($customerDifferences) === 1 ? '' : 's') : 'safe' }}
                                            </span>
                                        </div>

                                        @if ($hasCustomerDifferences)
                                            <div class="mt-3 space-y-2">
                                                @foreach ($customerDifferences as $difference)
                                                    <div class="rounded-xl bg-white p-3 ring-1 ring-amber-200">
                                                        <p class="text-xs font-black uppercase tracking-wide text-amber-700">{{ $difference['label'] }}</p>
                                                        <div class="mt-2 grid gap-2 text-xs sm:grid-cols-2">
                                                            <div>
                                                                <p class="font-bold text-gray-500">Stored customer record</p>
                                                                <p class="mt-1 whitespace-pre-wrap font-semibold text-gray-900">{{ $difference['stored'] }}</p>
                                                            </div>
                                                            <div>
                                                                <p class="font-bold text-gray-500">Submitted in request</p>
                                                                <p class="mt-1 whitespace-pre-wrap font-semibold text-gray-900">{{ $difference['submitted'] }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="mt-4 grid gap-2">
                                            <label class="flex cursor-pointer items-start gap-3 rounded-xl bg-white p-3 ring-1 ring-gray-200">
                                                <input type="radio" name="existing_customer_action" value="keep" class="mt-1" @checked($existingCustomerAction !== 'update')>
                                                <span>
                                                    <span class="block text-sm font-black text-gray-900">Use existing customer without changing their saved details</span>
                                                    <span class="block text-xs text-gray-500">Safest default. The request can still be converted to a draft for this customer.</span>
                                                </span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-3 rounded-xl bg-white p-3 ring-1 ring-gray-200">
                                                <input type="radio" name="existing_customer_action" value="update" class="mt-1" @checked($existingCustomerAction === 'update')>
                                                <span>
                                                    <span class="block text-sm font-black text-gray-900">Update existing customer using the editable details below</span>
                                                    <span class="block text-xs text-gray-500">Use when the customer has changed address, phone or email, or the saved record needs correction.</span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="rounded-2xl border border-gray-200 p-4">
                                <label class="flex cursor-pointer items-start gap-3">
                                    <input type="radio" name="customer_mode" value="create" class="mt-1" @checked($defaultMode === 'create')>
                                    <span>
                                        <span class="block text-sm font-black text-gray-900">Create new customer</span>
                                        <span class="block text-xs text-gray-500">Editable before saving, useful for typos in request details.</span>
                                    </span>
                                </label>
                            </div>

                            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="text-sm font-black text-blue-950">Submitted/editable customer details</h4>
                                    <span class="rounded-full bg-white px-2 py-1 text-[11px] font-black uppercase tracking-wide text-blue-700">editable</span>
                                </div>

                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="text-xs font-bold uppercase tracking-wide text-blue-800">First name</label>
                                        <input name="first_name" value="{{ $existingFirst }}" class="mt-1 w-full rounded-xl border border-blue-200 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold uppercase tracking-wide text-blue-800">Last name</label>
                                        <input name="last_name" value="{{ $existingLast }}" class="mt-1 w-full rounded-xl border border-blue-200 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="text-xs font-bold uppercase tracking-wide text-blue-800">Company</label>
                                        <input name="company_name" value="{{ $existingCompany }}" class="mt-1 w-full rounded-xl border border-blue-200 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="text-xs font-bold uppercase tracking-wide text-blue-800">Email</label>
                                        <input name="email" value="{{ $existingEmail }}" class="mt-1 w-full rounded-xl border border-blue-200 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold uppercase tracking-wide text-blue-800">Phone country</label>
                                        <select name="phone_country_id" class="mt-1 w-full rounded-xl border border-blue-200 bg-white px-3 py-2 text-sm">
                                            <option value="">—</option>
                                            @foreach ($countries as $country)
                                                <option value="{{ $country->id }}" @selected((string) $existingPhoneCountry === (string) $country->id)>{{ $country->phone_code ? '+' . $country->phone_code . ' — ' : '' }}{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold uppercase tracking-wide text-blue-800">Phone digits</label>
                                        <input name="phone_digits" value="{{ $existingPhone }}" class="mt-1 w-full rounded-xl border border-blue-200 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="text-xs font-bold uppercase tracking-wide text-blue-800">Address</label>
                                        <input name="address_line1" value="{{ $existingAddress }}" class="mt-1 w-full rounded-xl border border-blue-200 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold uppercase tracking-wide text-blue-800">Postcode</label>
                                        <input name="address_postcode" value="{{ $existingPostcode }}" class="mt-1 w-full rounded-xl border border-blue-200 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold uppercase tracking-wide text-blue-800">Address country</label>
                                        <select name="address_country_id" class="mt-1 w-full rounded-xl border border-blue-200 bg-white px-3 py-2 text-sm">
                                            <option value="">—</option>
                                            @foreach ($countries as $country)
                                                <option value="{{ $country->id }}" @selected((string) $existingAddressCountry === (string) $country->id)>{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                                Draft number will be <strong>{{ $requestRow->request_ref }}</strong>. For existing customers, saved customer details are only changed when you choose the update option above.
                            </div>

                            @if ($hasUnresolvedRetailers)
                                <a href="#retailer-review-queue" class="block w-full rounded-2xl bg-amber-600 px-4 py-3 text-center text-sm font-black text-white shadow-sm hover:bg-amber-700">Resolve {{ $unresolvedRetailers->count() }} retailer{{ $unresolvedRetailers->count() === 1 ? '' : 's' }} before conversion</a>
                            @else
                                <button type="submit" class="w-full rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-black text-white shadow-sm hover:bg-emerald-700">Convert to draft order</button>
                            @endif
                        </form>
                    @endif
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-lg font-black text-gray-900">Attachments</h3>
                    @if ($attachments->isEmpty())
                        <p class="mt-2 text-sm text-gray-500">No attachments.</p>
                    @else
                        <div class="mt-3 space-y-2">
                            @foreach ($attachments as $attachment)
                                <div class="rounded-xl border border-gray-200 p-3 text-sm">
                                    <div class="font-bold text-gray-900">{{ $attachment->original_name ?? $attachment->path ?? 'Attachment' }}</div>
                                    <div class="mt-1 text-xs text-gray-500">{{ $attachment->mime_type ?? 'file' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
