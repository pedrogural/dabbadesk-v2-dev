<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Create Draft / Order</h2>
                <p class="mt-1 text-sm text-gray-500">Find an existing customer or create a new one, then open a clean Draft Workbench.</p>
            </div>
            <a href="{{ route('draft-orders.index') }}" class="rounded-2xl bg-white px-4 py-2 text-sm font-black text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">Back to Drafts</a>
        </div>
    </x-slot>

    @php
        $defaultPhoneCountryId = old('phone_country_id', $defaultPhoneCountryId ?? null);
        $defaultAddressCountryId = old('address_country_id', $defaultAddressCountryId ?? null);
        $defaultPostcode = old('address_postcode', $defaultPostcode ?? 'GX11 1AA');
        $selectedMode = old('customer_mode', $customerOptions->isNotEmpty() ? 'existing' : 'create');
        $selectedSource = old('source', 'office');
        $sourceOptions = [
            'office' => 'Walk-in / Office',
            'email' => 'Email',
            'whatsapp' => 'WhatsApp',
            'phone' => 'Phone',
            'other' => 'Other',
        ];
        $selectedPurchaseMode = old('purchase_mode', $selectedPurchaseMode ?? 'standard');
        $purchaseModeOptions = [
            'standard' => 'Dabba purchases goods',
            'customer_self_purchase' => 'Customer self-purchase',
        ];
        $inputClass = 'mt-1 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-purple-500 focus:outline-none focus:ring-4 focus:ring-purple-100';
        $labelClass = 'text-[11px] font-black uppercase tracking-[0.16em] text-slate-500';
    @endphp

    <style>
        .manual-order-page input:not([type="radio"]):not([type="file"]),
        .manual-order-page select,
        .manual-order-page textarea {
            padding: 0.75rem 1rem !important;
            min-height: 46px;
        }
        .manual-order-page input[type="radio"] {
            margin-top: 0.2rem;
        }
        .manual-order-page .soft-panel {
            background: linear-gradient(135deg, #ffffff 0%, #fbf7ff 100%);
        }
    </style>

    <div class="manual-order-page space-y-4">
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-800">
                <p class="font-black">Please check the form:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="overflow-hidden rounded-[2rem] bg-white shadow-sm ring-1 ring-slate-200">
            <div class="grid gap-4 border-b border-slate-100 bg-slate-50/70 p-5 xl:grid-cols-[minmax(0,1fr)_220px_260px] xl:items-end">
                <form method="GET" action="{{ route('order-requests.create-manual') }}" class="min-w-0">
                    <label for="customer_q" class="{{ $labelClass }}">Search customer</label>
                    <div class="mt-2 flex flex-col gap-3 lg:flex-row">
                        <div class="relative min-w-0 flex-1">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">⌕</span>
                            <input
                                id="customer_q"
                                name="customer_q"
                                value="{{ $customerSearch }}"
                                autofocus
                                placeholder="Full name, surname + initial, company, email, phone, address, postcode..."
                                class="w-full rounded-2xl border border-slate-300 bg-white py-3 pl-11 pr-4 text-sm font-semibold text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-purple-500 focus:outline-none focus:ring-4 focus:ring-purple-100"
                            >
                        </div>
                        <button type="submit" class="rounded-2xl bg-slate-950 px-6 py-3 text-sm font-black text-white shadow-sm hover:bg-slate-800">Search</button>
                        <a href="{{ route('order-requests.create-manual') }}" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-center text-sm font-black text-slate-600 hover:bg-slate-50">Clear</a>
                    </div>
                    <p class="mt-2 text-xs font-semibold text-slate-500">Examples: <span class="font-black text-slate-700">Jasmin Smith</span>, <span class="font-black text-slate-700">Smith J</span>, <span class="font-black text-slate-700">GX11</span>, <span class="font-black text-slate-700">Main Street</span>.</p>
                </form>

                <div>
                    <label for="source_top" class="{{ $labelClass }}">Request source</label>
                    <select id="source_top" form="manual-order-form" name="source" class="{{ $inputClass }}">
                        @foreach ($sourceOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedSource === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="purchase_mode_top" class="{{ $labelClass }}">Order type</label>
                    <select id="purchase_mode_top" form="manual-order-form" name="purchase_mode" class="{{ $inputClass }}">
                        @foreach ($purchaseModeOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedPurchaseMode === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] font-bold text-slate-500">Self-purchase orders are not mixed with Dabba-purchase orders.</p>
                </div>
            </div>

            <form id="manual-order-form" method="POST" action="{{ route('order-requests.store-manual') }}" enctype="multipart/form-data">
                @csrf

                <div class="grid gap-4 p-5 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
                    <section class="rounded-3xl border border-slate-200 bg-white p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-black text-slate-950">Existing customer</h3>
                                <p class="mt-1 text-sm font-semibold text-slate-500">Best option when the customer is already in DabbaDesk.</p>
                            </div>
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-2xl bg-slate-50 px-3 py-2 text-xs font-black text-slate-700 ring-1 ring-slate-200">
                                <input type="radio" name="customer_mode" value="existing" class="text-purple-600 focus:ring-purple-500" @checked($selectedMode === 'existing')>
                                Use selected customer
                            </label>
                        </div>

                        <div class="mt-4 space-y-2.5">
                            @forelse ($customerOptions as $customer)
                                @php
                                    $customerName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: ($customer->company_name ?: 'Unnamed customer');
                                    $addressBits = collect([$customer->address_line1 ?? null, $customer->postcode ?? null, $customer->country_name ?? null])->filter()->implode(' · ');
                                @endphp
                                <label class="block cursor-pointer rounded-2xl border border-slate-200 bg-white px-4 py-3 transition hover:border-purple-200 hover:bg-purple-50/40">
                                    <div class="flex items-start gap-3">
                                        <input type="radio" name="customer_id" value="{{ $customer->id }}" class="mt-1 text-purple-600 focus:ring-purple-500" @checked((string) old('customer_id') === (string) $customer->id)>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-black text-slate-950">{{ $customerName }}</span>
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-black text-slate-500">#{{ $customer->id }}</span>
                                            </div>
                                            @if ($customer->company_name && $customerName !== $customer->company_name)
                                                <div class="mt-0.5 text-xs font-bold text-slate-500">{{ $customer->company_name }}</div>
                                            @endif
                                            <div class="mt-1 text-xs font-semibold leading-5 text-slate-500">
                                                {{ $customer->email ?: 'No email' }}
                                                @if($customer->phone) · {{ $customer->phone }} @endif
                                                @if($addressBits) <br>{{ $addressBits }} @endif
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm font-semibold text-slate-500">
                                    Search above to find an existing customer. If this is a new customer, use the form on the right.
                                </div>
                            @endforelse
                        </div>
                    </section>

                    <section class="soft-panel rounded-3xl border-2 border-purple-200 p-4 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-black text-slate-950">Create new customer</h3>
                                <p class="mt-1 text-sm font-semibold text-slate-500">Defaults follow Dabba rules: Gibraltar phone/address and GX11 1AA postcode.</p>
                            </div>
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-2xl bg-purple-600 px-3 py-2 text-xs font-black text-white shadow-sm">
                                <input type="radio" name="customer_mode" value="create" class="border-white text-purple-600 focus:ring-purple-500" @checked($selectedMode === 'create')>
                                Use new customer
                            </label>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <div>
                                <label class="{{ $labelClass }}">First name</label>
                                <input name="first_name" value="{{ old('first_name') }}" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Surname</label>
                                <input name="last_name" value="{{ old('last_name') }}" class="{{ $inputClass }}">
                            </div>
                            <div class="md:col-span-2">
                                <label class="{{ $labelClass }}">Company</label>
                                <input name="company_name" value="{{ old('company_name') }}" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Email</label>
                                <input name="email" type="email" value="{{ old('email') }}" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Phone</label>
                                <input name="phone_digits" value="{{ old('phone_digits') }}" inputmode="numeric" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Phone country</label>
                                <select name="phone_country_id" class="{{ $inputClass }}">
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->id }}" @selected((string) $defaultPhoneCountryId === (string) $country->id)>{{ $country->name }} @if($country->phone_code) (+{{ ltrim($country->phone_code, '+') }}) @endif</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Address country</label>
                                <select name="address_country_id" class="{{ $inputClass }}">
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->id }}" @selected((string) $defaultAddressCountryId === (string) $country->id)>{{ $country->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="{{ $labelClass }}">Address line</label>
                                <input name="address_line1" value="{{ old('address_line1') }}" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Postcode</label>
                                <input name="address_postcode" value="{{ $defaultPostcode }}" class="{{ $inputClass }}">
                            </div>
                        </div>
                    </section>
                </div>

                <div class="grid gap-4 border-t border-slate-100 p-5 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <div>
                        <label class="{{ $labelClass }}">Request note</label>
                        <textarea name="notes" rows="3" placeholder="Paste WhatsApp/email summary, customer preferences, special instructions..." class="{{ $inputClass }}">{{ old('notes') }}</textarea>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Attachments</label>
                        <input name="attachments[]" type="file" multiple class="mt-1 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm shadow-sm file:mr-4 file:rounded-xl file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-black file:text-slate-700 hover:file:bg-slate-200">
                        <p class="mt-2 text-xs font-semibold text-slate-500">Screenshots, PDFs, email exports or photos. Max 10MB each.</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/70 px-5 py-4">
                    <a href="{{ route('order-requests.index') }}" class="rounded-2xl bg-white px-5 py-3 text-sm font-black text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">Cancel</a>
                    <button type="submit" class="rounded-2xl bg-purple-600 px-6 py-3 text-sm font-black text-white shadow-sm hover:bg-purple-700">Create request & open draft</button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
