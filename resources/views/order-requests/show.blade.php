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
    @endphp

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
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-gray-400">Submitted by customer</p>
                            <h3 class="mt-1 text-2xl font-black text-gray-900">{{ $requestName }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ $requestRow->customer_email ?: 'No email' }} · {{ $requestRow->customer_phone_digits ?: 'No phone' }}</p>
                        </div>
                        <span class="rounded-full px-4 py-2 text-sm font-black {{ $requestRow->converted_at ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ ucfirst($requestRow->status) }}</span>
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

                    @if ($requestRow->notes)
                        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                            <p class="text-xs font-black uppercase tracking-wide text-amber-700">Customer notes</p>
                            <p class="mt-2 whitespace-pre-wrap text-sm text-amber-900">{{ $requestRow->notes }}</p>
                        </div>
                    @endif
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
                                                @if ($item->retailer_url)<a href="{{ $item->retailer_url }}" target="_blank" class="font-semibold text-indigo-600 hover:text-indigo-800">Open link</a>@endif
                                            </div>
                                            @if ($item->notes)<div class="mt-2 rounded-xl bg-gray-50 p-2 text-xs text-gray-600">{{ $item->notes }}</div>@endif
                                        </td>
                                        <td class="px-4 py-3 align-top text-sm text-gray-700">
                                            <div class="font-bold">{{ $item->matched_retailer_name ?: ($item->retailer_name ?: 'Needs review') }}</div>
                                            @if (! $item->matched_retailer_name)<div class="mt-1 inline-flex rounded-full bg-orange-100 px-2 py-1 text-xs font-bold text-orange-800">Auto-create if needed</div>@endif
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

                    @if ($requestRow->converted_at)
                        <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                            <p class="font-black">Already converted</p>
                            <p class="mt-1">Draft order ID: {{ $requestRow->converted_draft_order_id }}</p>
                            <p class="mt-1 text-xs">Converted at {{ $requestRow->converted_at }}</p>
                        </div>
                    @else
                        @if (! $requestRow->reviewed_at)
                            <form method="POST" action="{{ route('order-requests.review', $requestRow->id) }}" class="mt-4">
                                @csrf
                                <button type="submit" class="w-full rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-black text-indigo-700 hover:bg-indigo-100">Mark as under review</button>
                            </form>
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

                            <button type="submit" class="w-full rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-black text-white shadow-sm hover:bg-emerald-700">Convert to draft order</button>
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
