<x-app-layout>
    <x-slot name="header">Draft Orders</x-slot>

    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Draft Order Workspace</h1>
                    <p class="mt-1 text-sm text-slate-500">Edit converted requests before they become locked operational order snapshots.</p>
                </div>
                <span class="inline-flex w-fit rounded-full bg-indigo-100 px-4 py-2 text-sm font-semibold text-indigo-700">Request → Draft → Order</span>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('draft-orders.index') }}" class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-end" x-data="{ timer: null, submitSoon() { clearTimeout(this.timer); this.timer = setTimeout(() => this.$refs.form.submit(), 450); } }" x-ref="form">
                <div class="lg:col-span-6">
                    <label for="q" class="text-sm font-semibold text-slate-700">Search drafts</label>
                    <input id="q" name="q" value="{{ $filters['q'] }}" type="text" placeholder="Request ref, draft id, customer, email, phone, item, SKU or URL" class="mt-2 w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @input="submitSoon()">
                </div>

                <div class="lg:col-span-3">
                    <label for="status" class="text-sm font-semibold text-slate-700">Status</label>
                    <select id="status" name="status" class="mt-2 w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" onchange="this.form.submit()">
                        <option value="">All supported statuses</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-1">
                    <label class="flex min-h-[48px] items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm">
                        <input type="checkbox" name="mine" value="1" @checked(! empty($filters['mine'])) onchange="this.form.submit()" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Mine
                    </label>
                </div>

                <div class="lg:col-span-2 flex gap-2">
                    <button type="submit" class="w-full rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Search</button>
                    @if ($filters['q'] || $filters['status'] || ! empty($filters['mine']))
                        <a href="{{ route('draft-orders.index') }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-200">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Draft results</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Statuses currently supported: open, reviewing, ready, consumed, cancelled.</p>
                </div>
                <p class="text-sm text-slate-500">Showing {{ $drafts->firstItem() ?? 0 }}–{{ $drafts->lastItem() ?? 0 }} of {{ $drafts->total() }}</p>
            </div>

            <div class="mt-5 space-y-4">
                @forelse ($drafts as $draft)
                    @php
                        $customerName = trim(($draft->first_name ?? '') . ' ' . ($draft->last_name ?? '')) ?: ($draft->company_name ?: 'Unknown customer');
                        $primaryRef = $draft->request_ref ?: ($draft->draft_number ?: $draft->id);
                    @endphp
                    <div class="rounded-3xl border border-slate-200 p-5 hover:border-indigo-200 hover:bg-indigo-50/30">
                        <div class="grid grid-cols-1 gap-4 xl:grid-cols-12 xl:items-center">
                            <div class="xl:col-span-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-lg font-black text-slate-950">Request #{{ $primaryRef }}</h3>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ str_replace('_', ' ', $draft->status) }}</span>
                                    @if ($draft->finalized_order_id)
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">linked order</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm font-black text-slate-700">
                                    <a href="{{ route('customers.show', $draft->customer_id) }}" class="hover:text-indigo-700 hover:underline">{{ $customerName }}</a>
                                </p>
                                <p class="mt-1 text-xs text-slate-400">Draft ID {{ $draft->id }}{{ $draft->draft_number && $draft->draft_number !== (string) $primaryRef && $draft->draft_number !== (string) $draft->id ? ' · Legacy ref '.$draft->draft_number : '' }} · Updated {{ $draft->updated_at ? \Carbon\Carbon::parse($draft->updated_at)->format('d M Y H:i') : 'unknown' }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">
                                    Created by {{ $draft->created_by_name ?: 'Unknown user' }}
                                    @if (($draft->updated_by_name ?? null) && $draft->updated_by_name !== $draft->created_by_name)
                                        · Updated by {{ $draft->updated_by_name }}
                                    @endif
                                </p>
                            </div>

                            <div class="xl:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Items</p>
                                <p class="mt-1 text-sm font-semibold text-slate-800">{{ $draft->item_count }} lines / {{ $draft->total_qty }} qty</p>
                            </div>

                            <div class="xl:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Subtotal</p>
                                <p class="mt-1 text-sm font-semibold text-slate-800">£{{ number_format($draft->items_subtotal ?? 0, 2) }}</p>
                            </div>

                            <div class="xl:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Draft total</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">£{{ number_format($draft->grand_total ?? 0, 2) }}</p>
                            </div>

                            <div class="xl:col-span-2 flex justify-start xl:justify-end">
                                <a href="{{ route('draft-orders.show', $draft->id) }}" class="rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Open request draft</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">No draft orders found.</div>
                @endforelse
            </div>

            <div class="mt-6">{{ $drafts->links() }}</div>
        </div>
    </div>
</x-app-layout>
