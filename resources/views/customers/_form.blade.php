@php
    $isEdit = isset($customer) && $customer;
    $address = $details['address'] ?? null;
    $defaults = $defaults ?? [];
    $defaultCountryId = $defaults['country_id'] ?? null;
    $defaultPhoneCountryId = $defaults['phone_country_id'] ?? $defaultCountryId;
    $defaultPostcode = $defaults['postcode'] ?? 'GX11 1AA';
    $rateValue = old('dabba_fee_rate', $isEdit && $customer->dabba_fee_rate !== null ? number_format(((float) $customer->dabba_fee_rate) * 100, 2, '.', '') : '');
    $input = 'h-13 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-base font-semibold text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-purple-500 focus:ring-4 focus:ring-purple-100';
    $label = 'mb-2 block text-xs font-black uppercase tracking-widest text-slate-500';
    $errorText = 'mt-1 text-xs font-bold text-rose-600';
@endphp

<style>
    .customer-form input,
    .customer-form select,
    .customer-form textarea { min-height: 52px; }
    .customer-form input[type="checkbox"] { min-height: 0; }
</style>

<div
    class="customer-form space-y-5"
    x-data="customerForm()"
>
    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-700">
            <strong>Something needs checking:</strong> {{ $errors->first() }}
        </div>
    @endif

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-black text-slate-950">Customer details</h2>
                    <p class="mt-1 text-sm text-slate-500">Names are title-cased on blur. Email is saved lowercase. Phone is normalised with the selected dialling country.</p>
                </div>
                <label class="inline-flex items-center gap-2 rounded-2xl bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $customer->is_active ?? 1)) class="rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                    Active customer
                </label>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div>
                    <label class="{{ $label }}">First name *</label>
                    <input name="first_name" value="{{ old('first_name', $customer->first_name ?? '') }}" maxlength="50" required @blur="titleCase($event.target)" class="{{ $input }}" placeholder="e.g. Rachel">
                    @error('first_name')<p class="{{ $errorText }}">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs font-semibold text-slate-400">2–50 letters; hyphen/apostrophe allowed.</p>
                </div>

                <div>
                    <label class="{{ $label }}">Surname *</label>
                    <input name="last_name" value="{{ old('last_name', $customer->last_name ?? '') }}" maxlength="50" required @blur="titleCase($event.target)" class="{{ $input }}" placeholder="e.g. Palmer">
                    @error('last_name')<p class="{{ $errorText }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $label }}">Company</label>
                    <input name="company_name" value="{{ old('company_name', $customer->company_name ?? '') }}" maxlength="150" class="{{ $input }}" placeholder="Optional company name">
                    @error('company_name')<p class="{{ $errorText }}">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs font-semibold text-slate-400">Casing is preserved for brand/company names.</p>
                </div>

                <div>
                    <label class="{{ $label }}">Reference</label>
                    <input name="reference" value="{{ old('reference', $customer->reference ?? '') }}" maxlength="191" class="{{ $input }}" placeholder="Optional internal/customer reference">
                    @error('reference')<p class="{{ $errorText }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $label }}">Email *</label>
                    <input name="email" type="email" value="{{ old('email', $details['email'] ?? '') }}" required @blur="lowercase($event.target)" class="{{ $input }}" placeholder="customer@example.com">
                    @error('email')<p class="{{ $errorText }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $label }}">Phone *</label>
                    <div class="grid gap-3 sm:grid-cols-[170px_minmax(0,1fr)]">
                        <select name="phone_country_id" class="{{ $input }}">
                            <option value="">Dial code</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" @selected((string) old('phone_country_id', $details['phone_country_id'] ?? $defaultPhoneCountryId) === (string) $country->id)>
                                    {{ $country->iso2 ? strtoupper($country->iso2) . ' ' : '' }}{{ $country->phone_code ? '+' . ltrim($country->phone_code, '+') : $country->name }}
                                </option>
                            @endforeach
                        </select>
                        <input name="phone" value="{{ old('phone', $details['phone'] ?? '') }}" required @input="digitsOnly($event.target)" class="{{ $input }}" placeholder="56003351">
                    </div>
                    @error('phone')<p class="{{ $errorText }}">{{ $message }}</p>@enderror
                    @error('phone_country_id')<p class="{{ $errorText }}">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-black text-slate-950">Dabba fee settings</h2>
            <p class="mt-1 text-sm text-slate-500">Customer-specific fee rules are used only for new drafts/orders created after saving.</p>

            <div class="mt-5 space-y-5">
                <label class="flex items-start gap-3 rounded-2xl bg-rose-50 px-4 py-4 text-sm font-bold text-rose-700">
                    <input type="checkbox" name="dabba_fee_is_disabled" value="1" @checked(old('dabba_fee_is_disabled', $customer->dabba_fee_is_disabled ?? 0)) class="mt-0.5 rounded border-rose-300 text-rose-600 focus:ring-rose-500">
                    <span>
                        Disable Dabba fee for this customer
                        <span class="mt-1 block text-xs font-semibold text-rose-500">Use carefully; new drafts will stamp a zero-fee policy.</span>
                    </span>
                </label>

                <div>
                    <label class="{{ $label }}">Fee level</label>
                    <select name="dabba_fee_level" class="{{ $input }}">
                        <option value="global" @selected(old('dabba_fee_level', $customer->dabba_fee_level ?? 'global') === 'global')>Use global fee</option>
                        <option value="custom" @selected(old('dabba_fee_level', $customer->dabba_fee_level ?? 'global') === 'custom')>Custom customer fee</option>
                    </select>
                    @error('dabba_fee_level')<p class="{{ $errorText }}">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $label }}">Rate %</label>
                        <input name="dabba_fee_rate" type="number" step="0.01" min="0" max="100" value="{{ $rateValue }}" class="{{ $input }}" placeholder="20.00">
                        @error('dabba_fee_rate')<p class="{{ $errorText }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Min £</label>
                        <input name="dabba_fee_min" type="number" step="0.01" min="0" value="{{ old('dabba_fee_min', $customer->dabba_fee_min ?? '') }}" class="{{ $input }}" placeholder="10.00">
                        @error('dabba_fee_min')<p class="{{ $errorText }}">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </section>
    </div>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-black text-slate-950">Primary address</h2>
        <p class="mt-1 text-sm text-slate-500">Existing orders keep their historical address snapshots. This edits the current customer address for future drafts.</p>

        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="{{ $label }}">Address line 1 *</label>
                <input name="line1" value="{{ old('line1', $address->line1 ?? '') }}" required minlength="7" @blur="titleCase($event.target)" class="{{ $input }}" placeholder="House / apartment / street">
                @error('line1')<p class="{{ $errorText }}">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="{{ $label }}">Line 2</label>
                <input name="line2" value="{{ old('line2', $address->line2 ?? '') }}" @blur="titleCase($event.target)" class="{{ $input }}" placeholder="Optional">
                @error('line2')<p class="{{ $errorText }}">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="{{ $label }}">City</label>
                <input name="city" value="{{ old('city', $address->city ?? '') }}" @blur="titleCase($event.target)" class="{{ $input }}" placeholder="Optional">
                @error('city')<p class="{{ $errorText }}">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="{{ $label }}">Region</label>
                <input name="region" value="{{ old('region', $address->region ?? '') }}" @blur="titleCase($event.target)" class="{{ $input }}" placeholder="Optional">
                @error('region')<p class="{{ $errorText }}">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="{{ $label }}">Postcode</label>
                <input name="postcode" value="{{ old('postcode', $address->postcode ?? $defaultPostcode) }}" @blur="uppercase($event.target)" class="{{ $input }}" placeholder="GX11 1AA">
                @error('postcode')<p class="{{ $errorText }}">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="{{ $label }}">Country</label>
                <select name="country_id" class="{{ $input }}">
                    <option value="">Choose country</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}" @selected((string) old('country_id', $address->country_id ?? $defaultCountryId) === (string) $country->id)>{{ $country->name }}</option>
                    @endforeach
                </select>
                @error('country_id')<p class="{{ $errorText }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <div class="sticky bottom-0 -mx-1 flex justify-end gap-3 border-t border-slate-200/70 bg-slate-100/90 px-1 py-4 backdrop-blur">
        <a href="{{ $isEdit ? route('customers.show', $customer->id) : route('customers.index') }}" class="rounded-2xl border border-slate-300 bg-white px-6 py-3 text-sm font-black text-slate-700 shadow-sm hover:bg-slate-50">Cancel</a>
        <button class="rounded-2xl bg-purple-600 px-6 py-3 text-sm font-black text-white shadow-sm hover:bg-purple-700">Save customer</button>
    </div>
</div>

<script>
    function customerForm() {
        const smallWords = new Set(['and', 'of', 'the', 'ltd', 'limited']);
        const titlePiece = (piece) => piece ? piece.charAt(0).toLocaleUpperCase() + piece.slice(1).toLocaleLowerCase() : piece;

        return {
            titleCase(el) {
                const value = (el.value || '').trim();
                if (!value) return;
                el.value = value
                    .toLocaleLowerCase()
                    .split(/(\s+)/)
                    .map(part => part.trim() === '' ? part : part
                        .split(/([-’'])/)
                        .map(piece => ['-', '’', "'"].includes(piece) ? piece : titlePiece(piece))
                        .join(''))
                    .join('');
            },
            lowercase(el) {
                el.value = (el.value || '').trim().toLocaleLowerCase();
            },
            uppercase(el) {
                el.value = (el.value || '').trim().toLocaleUpperCase();
            },
            digitsOnly(el) {
                const hasPlus = (el.value || '').trim().startsWith('+');
                const digits = (el.value || '').replace(/\D+/g, '');
                el.value = hasPlus ? '+' + digits : digits;
            }
        }
    }
</script>
