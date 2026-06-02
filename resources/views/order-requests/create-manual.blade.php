<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">New Manual Request</h2>
                <p class="mt-1 text-sm text-gray-500">Create an intake record and open an empty Draft Workbench for staff entry.</p>
            </div>
            <a href="{{ route('order-requests.index') }}" class="rounded-2xl bg-slate-100 px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-200">Back to requests</a>
        </div>
    </x-slot>

    <div class="space-y-4">
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

        <div class="rounded-3xl border border-indigo-100 bg-indigo-50 px-5 py-4 text-sm text-indigo-900">
            <p class="font-black">Fast staff workflow</p>
            <p class="mt-1">This creates an empty order request for audit/source tracking, immediately converts it to a draft, then sends you to Draft Workbench to add products once.</p>
        </div>

        <form method="GET" action="{{ route('order-requests.create-manual') }}" class="rounded-3xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-200">
            <label for="customer_q" class="text-xs font-black uppercase tracking-widest text-slate-500">Find existing customer</label>
            <div class="mt-2 grid gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
                <input id="customer_q" name="customer_q" value="{{ $customerSearch }}" placeholder="Search full name, company, email, phone, postcode or address" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold shadow-sm focus:border-purple-500 focus:ring-4 focus:ring-purple-100">
                <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-black text-white hover:bg-slate-700">Search</button>
            </div>
        </form>

        <form method="POST" action="{{ route('order-requests.store-manual') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <section class="rounded-3xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-200">
                <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_260px] md:items-end">
                    <div>
                        <h3 class="text-lg font-black text-slate-950">Request source</h3>
                        <p class="mt-1 text-sm text-slate-500">Where did this customer request come from?</p>
                    </div>
                    <div>
                        <label for="source" class="text-xs font-black uppercase tracking-widest text-slate-500">Source</label>
                        <select id="source" name="source" class="mt-1 w-full rounded-2xl border-slate-300 text-sm font-black text-slate-700 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            @foreach ([
                                'office' => 'In office',
                                'email' => 'Email',
                                'whatsapp' => 'WhatsApp',
                                'phone' => 'Phone',
                                'other' => 'Other',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('source', 'office') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-200">
                <h3 class="text-lg font-black text-slate-950">Customer</h3>
                <p class="mt-1 text-sm text-slate-500">Use an existing customer where possible. New customer fields are only used when “Create new customer” is selected.</p>

                <div class="mt-4 grid gap-4 xl:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <label class="flex items-center gap-3 text-sm font-black text-slate-800">
                            <input type="radio" name="customer_mode" value="existing" class="text-purple-600 focus:ring-purple-500" @checked(old('customer_mode', 'existing') === 'existing')>
                            Existing customer
                        </label>

                        <div class="mt-4 space-y-3">
                            @forelse ($customerOptions as $customer)
                                @php
                                    $customerName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: ($customer->company_name ?: 'Unnamed customer');
                                @endphp
                                <label class="block cursor-pointer rounded-2xl border border-slate-200 px-4 py-3 hover:bg-slate-50">
                                    <div class="flex items-start gap-3">
                                        <input type="radio" name="customer_id" value="{{ $customer->id }}" class="mt-1 text-purple-600 focus:ring-purple-500" @checked((string) old('customer_id') === (string) $customer->id)>
                                        <div>
                                            <div class="text-sm font-black text-slate-900">{{ $customerName }}</div>
                                            @if ($customer->company_name && $customerName !== $customer->company_name)
                                                <div class="text-xs font-semibold text-slate-500">{{ $customer->company_name }}</div>
                                            @endif
                                            <div class="mt-1 text-xs text-slate-500">{{ $customer->email ?: 'No email' }} @if($customer->phone) · {{ $customer->phone }} @endif</div>
                                        </div>
                                    </div>
                                </label>
                            @empty
                                <div class="rounded-2xl bg-slate-50 px-4 py-5 text-sm text-slate-500">
                                    Search above to select an existing customer, or choose “Create new customer”.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-4">
                        <label class="flex items-center gap-3 text-sm font-black text-slate-800">
                            <input type="radio" name="customer_mode" value="create" class="text-purple-600 focus:ring-purple-500" @checked(old('customer_mode') === 'create')>
                            Create new customer
                        </label>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">First name</label>
                                <input name="first_name" value="{{ old('first_name') }}" class="mt-1 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            </div>
                            <div>
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Surname</label>
                                <input name="last_name" value="{{ old('last_name') }}" class="mt-1 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Company</label>
                                <input name="company_name" value="{{ old('company_name') }}" class="mt-1 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            </div>
                            <div>
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Email</label>
                                <input name="email" type="email" value="{{ old('email') }}" class="mt-1 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            </div>
                            <div>
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Phone</label>
                                <input name="phone_digits" value="{{ old('phone_digits') }}" class="mt-1 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            </div>
                            <div>
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Phone country</label>
                                <select name="phone_country_id" class="mt-1 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                    <option value="">Choose</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->id }}" @selected((string) old('phone_country_id') === (string) $country->id)>{{ $country->name }} @if($country->phone_code) (+{{ $country->phone_code }}) @endif</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Address country</label>
                                <select name="address_country_id" class="mt-1 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                    <option value="">Choose</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->id }}" @selected((string) old('address_country_id') === (string) $country->id)>{{ $country->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Address line</label>
                                <input name="address_line1" value="{{ old('address_line1') }}" class="mt-1 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            </div>
                            <div>
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Postcode</label>
                                <input name="address_postcode" value="{{ old('address_postcode') }}" class="mt-1 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-200">
                <h3 class="text-lg font-black text-slate-950">Intake note & attachments</h3>
                <p class="mt-1 text-sm text-slate-500">Optional. Products will be added once in the Draft Workbench after this draft opens.</p>

                <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <div>
                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Request note</label>
                        <textarea name="notes" rows="5" placeholder="Paste WhatsApp/email summary, customer preferences, special instructions..." class="mt-1 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">{{ old('notes') }}</textarea>
                    </div>
                    <div>
                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Attachments</label>
                        <input name="attachments[]" type="file" multiple class="mt-1 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm shadow-sm file:mr-4 file:rounded-xl file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-black file:text-slate-700 hover:file:bg-slate-200">
                        <p class="mt-2 text-xs text-slate-500">Screenshots, PDFs, email exports or photos. Max 10MB each.</p>
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('order-requests.index') }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-200">Cancel</a>
                <button type="submit" class="rounded-2xl bg-purple-600 px-6 py-3 text-sm font-black text-white shadow-sm hover:bg-purple-700">Create request & open empty draft</button>
            </div>
        </form>
    </div>
</x-app-layout>
