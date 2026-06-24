<x-app-layout>
    <x-slot name="header">Customers</x-slot>

    <div
        class="space-y-5"
        x-data="customerDesk({
            searchUrl: '{{ route('customers.live-search') }}',
            showUrlBase: '{{ url('/customers') }}',
            initialQuery: @js($filters['q'] ?? ''),
            initialResults: @js($initialResults ?? [])
        })"
        x-init="boot()"
    >
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-950">Customer Desk</h1>
                    <p class="mt-1 text-sm text-slate-500">Live search by name, email, phone, company or reference. Edit customer-level Dabba fee rules from each record.</p>
                </div>
                <a href="{{ route('customers.create') }}" class="rounded-2xl bg-purple-600 px-5 py-3 text-sm font-black text-white shadow-sm hover:bg-purple-700">New customer</a>
            </div>

            <div class="mt-6">
                <label class="mb-2 block text-xs font-black uppercase tracking-widest text-slate-500">Live customer search</label>
                <div class="flex flex-col gap-3 lg:flex-row">
                    <div class="relative flex-1">
                        <input
                            x-model="q"
                            @input.debounce.250ms="search()"
                            type="search"
                            autocomplete="off"
                            placeholder="Start typing name, email, phone, company or reference..."
                            class="h-14 w-full rounded-2xl border border-slate-300 bg-white px-5 pr-12 text-base font-semibold text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-purple-500 focus:ring-4 focus:ring-purple-100"
                        >
                        <div class="absolute inset-y-0 right-4 grid place-items-center text-slate-400">
                            <span x-show="!loading">⌕</span>
                            <span x-show="loading" x-cloak class="animate-pulse">…</span>
                        </div>
                    </div>
                    <a :href="q.trim() ? '{{ route('customers.index') }}' + '?q=' + encodeURIComponent(q.trim()) : '{{ route('customers.index') }}'" class="inline-flex h-14 items-center justify-center rounded-2xl bg-indigo-600 px-6 text-sm font-black text-white hover:bg-indigo-700">Full search</a>
                </div>
                <p class="mt-2 text-xs font-semibold text-slate-500">Results update while typing. Press “Full search” only if you want a paginated server result.</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-6 py-4">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-widest text-slate-500">Search results</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-400" x-text="summaryText()"></p>
                </div>
                <button type="button" x-show="q" @click="q=''; search()" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-600 hover:bg-slate-50">Clear</button>
            </div>

            <div class="divide-y divide-slate-100">
                <template x-for="customer in results" :key="customer.id">
                    <article class="grid gap-4 px-6 py-5 hover:bg-slate-50 lg:grid-cols-[minmax(220px,1.1fr)_minmax(260px,1fr)_160px_150px] lg:items-center">
                        <div>
                            <a :href="customer.url" class="text-base font-black text-slate-950 hover:text-purple-700" x-text="customer.name"></a>
                            <div class="mt-1 flex flex-wrap gap-2 text-xs font-semibold text-slate-500">
                                <span x-text="'#' + customer.id"></span>
                                <span x-show="customer.reference">·</span>
                                <span x-show="customer.reference" x-text="customer.reference"></span>
                                <span x-show="!customer.is_active" class="rounded-full bg-rose-50 px-2 py-0.5 text-rose-700">Inactive</span>
                            </div>
                            <p x-show="customer.company_name" class="mt-1 text-sm font-semibold text-slate-500" x-text="customer.company_name"></p>
                        </div>

                        <div class="text-sm font-semibold text-slate-600">
                            <div x-text="customer.email || 'No email'"></div>
                            <div class="mt-1" x-text="customer.phone || 'No phone'"></div>
                        </div>

                        <div>
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600" x-text="customer.fee_label"></span>
                        </div>

                        <div class="flex gap-2 lg:justify-end">
                            <a :href="customer.url" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-700 hover:bg-white">Open</a>
                            <a :href="customer.edit_url" class="rounded-2xl bg-purple-600 px-4 py-2.5 text-sm font-black text-white hover:bg-purple-700">Edit</a>
                        </div>
                    </article>
                </template>

                <div x-show="!loading && results.length === 0" x-cloak class="px-6 py-12 text-center">
                    <p class="text-lg font-black text-slate-900">No customers found</p>
                    <p class="mt-1 text-sm text-slate-500">Try a different spelling, phone fragment, email, or reference.</p>
                </div>
            </div>
        </section>

        @if(($customers ?? null) && method_exists($customers, 'links'))
            <noscript>
                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50 text-xs font-black uppercase tracking-widest text-slate-500"><tr><th class="px-6 py-3 text-left">Customer</th><th class="px-6 py-3 text-left">Contact</th><th class="px-6 py-3 text-left">Fee</th><th class="px-6 py-3 text-right">Action</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                            @foreach ($customers as $customer)
                                @php $name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: ($customer->company_name ?: 'Unknown customer'); @endphp
                                <tr>
                                    <td class="px-6 py-4"><div class="font-black text-slate-950">{{ $name }}</div><div class="text-xs text-slate-500">#{{ $customer->id }} {{ $customer->reference ? '· '.$customer->reference : '' }}</div></td>
                                    <td class="px-6 py-4 text-slate-600"><div>{{ $customer->primary_email ?: '—' }}</div><div>{{ $customer->primary_phone ?: '—' }}</div></td>
                                    <td class="px-6 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $customer->dabba_fee_is_disabled ? 'Fee disabled' : (($customer->dabba_fee_level ?? 'global') === 'vip_min_percent' ? 'Custom fee' : (($customer->dabba_fee_level ?? 'global') === 'vip_percent_only' ? 'Custom percent only' : 'Global fee')) }}</span></td>
                                    <td class="px-6 py-4 text-right"><a href="{{ route('customers.show', $customer->id) }}" class="font-black text-purple-700 hover:text-purple-900">Open</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-100 px-6 py-4">{{ $customers->links() }}</div>
                </section>
            </noscript>
        @endif
    </div>

    <script>
        function customerDesk(config) {
            return {
                q: config.initialQuery || '',
                results: config.initialResults || [],
                loading: false,
                boot() {
                    if (!this.results.length) this.search();
                },
                summaryText() {
                    if (this.loading) return 'Searching…';
                    const count = this.results.length;
                    if (!this.q.trim()) return count + ' most recently updated customers';
                    return count + ' live ' + (count === 1 ? 'match' : 'matches') + ' for “' + this.q.trim() + '”';
                },
                async search() {
                    this.loading = true;
                    try {
                        const url = config.searchUrl + '?q=' + encodeURIComponent(this.q || '');
                        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        const payload = await response.json();
                        this.results = payload.customers || [];
                    } catch (e) {
                        console.error('Customer live search failed', e);
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</x-app-layout>
