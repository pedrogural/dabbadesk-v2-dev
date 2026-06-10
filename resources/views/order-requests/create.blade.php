<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">New Order Request</h2>
                <p class="mt-1 text-sm text-gray-500">Create a staff-entered request from an office visit, email, WhatsApp, phone call, or other intake source.</p>
            </div>
            <a href="{{ route('order-requests.index') }}" class="rounded-2xl bg-slate-100 px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-200">Back to requests</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-800">
                <p class="font-black">Please check the request details.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
            <form method="GET" action="{{ route('order-requests.create') }}" class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <h3 class="text-sm font-black uppercase tracking-widest text-slate-500">Find existing customer</h3>
                <p class="mt-1 text-sm text-slate-500">Search first if this is an existing Dabba customer. Selecting one will copy their current details into the request.</p>

                <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                    <input
                        name="customer_q"
                        value="{{ $customerSearch }}"
                        placeholder="Search name, company, email or phone"
                        class="min-w-0 flex-1 rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-black text-white hover:bg-slate-700">Search</button>
                </div>

                @if ($customerSearch !== '')
                    <div class="mt-4 space-y-2">
                        @forelse ($customerOptions as $customer)
                            @php
                                $selected = (int) $selectedCustomerId === (int) $customer->id;
                                $name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: ($customer->company_name ?: 'Customer #' . $customer->id);
                            @endphp
                            <a
                                href="{{ route('order-requests.create', ['customer_q' => $customerSearch, 'customer_id' => $customer->id]) }}"
                                class="flex items-center justify-between gap-4 rounded-2xl border px-4 py-3 text-sm transition {{ $selected ? 'border-indigo-300 bg-indigo-50 text-indigo-900' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-indigo-200 hover:bg-indigo-50' }}"
                            >
                                <span>
                                    <span class="block font-black">{{ $name }}</span>
                                    <span class="block text-xs text-slate-500">{{ $customer->email ?: 'No email' }}{{ $customer->phone ? ' · ' . $customer->phone : '' }}</span>
                                </span>
                                <span class="rounded-full {{ $selected ? 'bg-indigo-600 text-white' : 'bg-white text-slate-500' }} px-3 py-1 text-xs font-black">{{ $selected ? 'Selected' : 'Select' }}</span>
                            </a>
                        @empty
                            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-500">No matching customers found. Use manual customer details below.</div>
                        @endforelse
                    </div>
                @endif
            </form>

            <div class="rounded-3xl border border-indigo-100 bg-indigo-50 p-5 text-sm text-indigo-900">
                <h3 class="font-black">Manual request workflow</h3>
                <p class="mt-2 leading-6">This creates a normal order request first. Anything entered here copies into the Draft Workbench, so there is no double entry.</p>
                <div class="mt-4 space-y-2 text-xs font-bold uppercase tracking-wide text-indigo-700">
                    <div>1. Capture customer intention</div>
                    <div>2. Resolve retailers if needed</div>
                    <div>3. Convert to draft</div>
                    <div>4. Finalise order when ready</div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('order-requests.store') }}" enctype="multipart/form-data" x-data="manualOrderRequestForm()" class="space-y-6">
            @csrf

            <input type="hidden" name="customer_mode" value="{{ $selectedCustomer ? 'existing' : 'manual' }}">
            <input type="hidden" name="existing_customer_id" value="{{ $selectedCustomer?->id }}">

            <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <div class="grid gap-4 lg:grid-cols-2">
                    <div>
                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Request source</label>
                        <select name="source" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                    <div>
                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">External reference / note</label>
                        <input name="reference_number" value="{{ old('reference_number') }}" placeholder="Email subject, WhatsApp ref, customer quote ref…" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-widest text-slate-500">Customer</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $selectedCustomer ? 'Using selected customer details.' : 'Enter enough customer detail to create the request. Customer records are created/updated during conversion.' }}</p>
                    </div>
                    @if ($selectedCustomer)
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">Existing customer selected</span>
                    @endif
                </div>

                @if ($selectedCustomer)
                    <div class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                        <p class="font-black text-slate-900">{{ trim($selectedCustomer->first_name . ' ' . $selectedCustomer->last_name) }}</p>
                        @if ($selectedCustomer->company_name)<p>{{ $selectedCustomer->company_name }}</p>@endif
                        <p class="mt-1 text-slate-500">{{ $selectedCustomer->email ?: 'No email' }}{{ $selectedCustomer->phone_digits ? ' · ' . $selectedCustomer->phone_digits : '' }}</p>
                        <a href="{{ route('order-requests.create') }}" class="mt-3 inline-flex rounded-xl bg-white px-3 py-2 text-xs font-black text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100">Use manual details instead</a>
                    </div>
                @else
                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                        <div>
                            <label class="text-xs font-black uppercase tracking-widest text-slate-500">First name</label>
                            <input name="customer_first_name" value="{{ old('customer_first_name') }}" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-widest text-slate-500">Surname</label>
                            <input name="customer_last_name" value="{{ old('customer_last_name') }}" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-widest text-slate-500">Company</label>
                            <input name="customer_company_name" value="{{ old('customer_company_name') }}" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-widest text-slate-500">Email</label>
                            <input name="customer_email" type="email" value="{{ old('customer_email') }}" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-widest text-slate-500">Phone country</label>
                            <select name="customer_phone_country_id" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select country</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" @selected((string) old('customer_phone_country_id') === (string) $country->id)>{{ $country->name }} {{ $country->phone_code ? '(' . $country->phone_code . ')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-widest text-slate-500">Phone digits</label>
                            <input name="customer_phone_digits" value="{{ old('customer_phone_digits') }}" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-widest text-slate-500">Address</label>
                            <textarea name="customer_address_line1" rows="3" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('customer_address_line1') }}</textarea>
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-widest text-slate-500">Postcode</label>
                            <input name="customer_address_postcode" value="{{ old('customer_address_postcode', 'GX11 1AA') }}" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-widest text-slate-500">Address country</label>
                            <select name="customer_address_country_id" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select country</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" @selected((string) old('customer_address_country_id') === (string) $country->id || (old('customer_address_country_id') === null && $country->iso2 === 'GI'))>{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>

            <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-widest text-slate-500">Request items</h3>
                        <p class="mt-1 text-sm text-slate-500">Enter rough links/items once. These lines copy into the Draft Workbench for final pricing and clean-up.</p>
                    </div>
                    <button type="button" @click="addItem()" class="rounded-2xl bg-indigo-100 px-4 py-2 text-sm font-black text-indigo-700 hover:bg-indigo-200">+ Add another item</button>
                </div>

                <div class="mt-5 space-y-4">
                    <template x-for="(item, index) in items" :key="item.key">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <h4 class="text-sm font-black text-slate-800">Item <span x-text="index + 1"></span></h4>
                                <button type="button" @click="removeItem(index)" x-show="items.length > 1" class="rounded-xl bg-white px-3 py-2 text-xs font-black text-rose-600 ring-1 ring-rose-100 hover:bg-rose-50">Remove</button>
                            </div>
                            <div class="grid gap-4 lg:grid-cols-2">
                                <div>
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Product URL</label>
                                    <input :name="`items[${index}][retailer_url]`" x-model="item.retailer_url" placeholder="https://…" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Retailer name</label>
                                    <input :name="`items[${index}][retailer_name]`" x-model="item.retailer_name" placeholder="Optional if URL is supplied" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Product code</label>
                                    <input :name="`items[${index}][product_code]`" x-model="item.product_code" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Qty</label>
                                        <input type="number" min="1" :name="`items[${index}][quantity]`" x-model="item.quantity" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Unit price</label>
                                        <input type="number" min="0" step="0.01" :name="`items[${index}][unit_price]`" x-model="item.unit_price" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                </div>
                                <div class="lg:col-span-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Description</label>
                                    <textarea rows="2" :name="`items[${index}][description]`" x-model="item.description" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                </div>
                                <div class="lg:col-span-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Item notes</label>
                                    <textarea rows="2" :name="`items[${index}][notes]`" x-model="item.notes" placeholder="Colour, size, WhatsApp instruction, delivery note…" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Request notes</label>
                <textarea name="notes" rows="4" placeholder="Paste WhatsApp/email summary, customer instructions, or office notes…" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>

                <label class="mt-5 block text-xs font-black uppercase tracking-widest text-slate-500">Attachments</label>
                <input type="file" name="attachments[]" multiple class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm file:mr-4 file:rounded-xl file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-black file:text-indigo-700 hover:file:bg-indigo-100">
                <p class="mt-2 text-xs text-slate-500">Screenshots, PDFs, photos, or email/WhatsApp extracts can be attached to the request.</p>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('order-requests.index') }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-200">Cancel</a>
                <button type="submit" class="rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-black text-white shadow-sm hover:bg-indigo-700">Create Request</button>
            </div>
        </form>
    </div>

    <script>
        function manualOrderRequestForm() {
            return {
                items: [{ key: Date.now(), retailer_url: '', retailer_name: '', product_code: '', description: '', quantity: 1, unit_price: '0.00', notes: '' }],
                addItem() {
                    this.items.push({ key: Date.now() + Math.random(), retailer_url: '', retailer_name: '', product_code: '', description: '', quantity: 1, unit_price: '0.00', notes: '' });
                    this.$nextTick(() => {
                        const inputs = document.querySelectorAll('input[name$="[retailer_url]"]');
                        inputs[inputs.length - 1]?.focus();
                    });
                },
                removeItem(index) {
                    if (this.items.length > 1) this.items.splice(index, 1);
                }
            }
        }
    </script>
</x-app-layout>
