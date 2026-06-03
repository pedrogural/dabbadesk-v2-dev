<x-app-layout>
    <x-slot name="header">Orders</x-slot>

    <div class="space-y-4">
        <div class="rounded-3xl bg-white px-5 py-4 shadow-sm border border-slate-200">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-black tracking-tight text-slate-950">Orders</h1>
                    @if (empty($filters['show_history']))
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-emerald-700">Current revisions only</span>
                    @else
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-amber-700">History included</span>
                    @endif
                </div>
                <p class="text-sm font-semibold text-slate-500">Showing {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }} orders</p>
            </div>
        </div>

        <div class="rounded-3xl bg-white p-4 shadow-sm border border-slate-200">
            <form method="GET" action="{{ route('orders.index') }}" data-live-search-form class="space-y-3">
                <div class="grid grid-cols-1 gap-3 xl:grid-cols-[minmax(0,1fr)_220px_auto] xl:items-center">
                    <div>
                        <label for="q" class="sr-only">Search orders</label>
                        <input
                            id="q"
                            name="q"
                            value="{{ $filters['q'] }}"
                            type="text"
                            autocomplete="off"
                            placeholder="Search order number, customer, email, item, SKU, retailer ref or tracking…"
                            data-live-search-input
                            class="h-[46px] w-full rounded-2xl border-slate-300 px-4 text-sm font-semibold shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div>
                        <label for="status" class="sr-only">Status</label>
                        <select id="status" name="status" class="h-[46px] w-full rounded-2xl border-slate-300 px-4 text-sm font-bold text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" data-live-search-submit>
                            <option value="">All statuses</option>
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ match($status) { 'paid' => 'Paid', 'unpaid' => 'Unpaid', 'purchased' => 'Purchased', 'arrived' => 'Arrived', default => str_replace('_', ' ', ucfirst($status)) } }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <label class="inline-flex h-[42px] cursor-pointer items-center justify-center gap-2 rounded-2xl border px-4 text-sm font-black shadow-sm {{ ! empty($filters['mine']) ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                            <input type="checkbox" name="mine" value="1" @checked(! empty($filters['mine'])) data-live-search-submit class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            Mine
                        </label>
                        <label class="inline-flex h-[42px] cursor-pointer items-center justify-center gap-2 rounded-2xl border px-4 text-sm font-black shadow-sm {{ ! empty($filters['show_history']) ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                            <input type="checkbox" name="show_history" value="1" @checked(! empty($filters['show_history'])) data-live-search-submit class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            History
                        </label>
                        @if ($filters['q'] || $filters['status'] || ! empty($filters['mine']) || ! empty($filters['show_history']))
                            <a href="{{ route('orders.index') }}" class="inline-flex h-[42px] items-center justify-center rounded-2xl bg-slate-100 px-4 text-sm font-black text-slate-600 hover:bg-slate-200">Clear</a>
                        @endif
                    </div>
                </div>
                <p class="text-xs font-semibold text-slate-400">Search updates automatically after a short pause. Paid/unpaid filters are finance-derived; purchased/arrived filters use operational progress.</p>
            </form>
        </div>

        <div class="rounded-3xl bg-white p-4 shadow-sm border border-slate-200">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base font-black text-slate-900">Order results</h2>
                    @if (empty($filters['show_history']))
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-500">Current/latest revisions</span>
                    @else
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700">Superseded revisions included</span>
                    @endif
                </div>
                <p class="text-sm font-semibold text-slate-500">{{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }}</p>
            </div>

            <div class="mt-4 space-y-2">
                @forelse ($orders as $order)
                    @php
                        $customerName = $order->bill_to_name ?: (trim(($order->first_name ?? '') . ' ' . ($order->last_name ?? '')) ?: ($order->company_name ?: 'Unknown customer'));
                        $revisionTotal = max(1, (int) ($order->revision_total ?? 1));
                        $balanceDue = round((float) ($order->balance_due ?? 0), 2);
                        $settledTotal = round((float) ($order->settled_total ?? 0), 2);
                        $isPaid = $balanceDue <= 0.004;
                        $isPartPaid = ! $isPaid && $settledTotal > 0.004;
                        $paymentLabel = $isPaid ? 'Paid' : ($isPartPaid ? 'Part paid' : 'Unpaid');
                        $paymentBadgeClass = $isPaid
                            ? 'bg-emerald-100 text-emerald-700'
                            : ($isPartPaid ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700');
                        $legacyStatus = (string) ($order->status ?? '');
                        $showLegacyException = in_array($legacyStatus, ['cancelled', 'canceled'], true);
                    @endphp
                    <div class="rounded-2xl border border-slate-200 bg-white p-3 transition hover:border-indigo-200 hover:bg-indigo-50/25">
                        <div class="grid grid-cols-1 gap-3 xl:grid-cols-12 xl:items-center">
                            <div class="xl:col-span-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-black text-slate-950">Order #{{ $order->order_number }}</h3>
                                    <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-black text-indigo-700">Rev {{ $order->revision_number ?? 1 }}/{{ $revisionTotal }}</span>
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-black {{ $paymentBadgeClass }}">{{ $paymentLabel }}</span>
                                    @if ($showLegacyException)
                                        <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-bold text-rose-700">Cancelled</span>
                                    @endif
                                    @if (($order->revision_state ?? 'current') === 'superseded')
                                        <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-bold text-rose-700">Superseded</span>
                                    @elseif (($order->revision_state ?? 'current') === 'current_revision')
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700">Current</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm font-black text-slate-700">
                                    <a href="{{ route('customers.show', $order->customer_id) }}" class="hover:text-indigo-700 hover:underline">{{ $customerName }}</a>
                                </p>
                                <p class="mt-0.5 truncate text-xs text-slate-400">
                                    {{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') : 'No date' }}
                                    @if ($order->bill_to_email)
                                        · {{ $order->bill_to_email }}
                                    @endif
                                </p>
                            </div>

                            <div class="xl:col-span-2">
                                <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Items</p>
                                <p class="mt-1 text-sm font-bold text-slate-800">{{ $order->item_count }} lines · {{ $order->total_qty }} qty</p>
                            </div>

                            <div class="xl:col-span-2">
                                <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Progress</p>
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700">{{ $order->purchased_qty }}/{{ $order->total_qty }} purchased</span>
                                    <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-black text-sky-700">{{ $order->arrived_qty }}/{{ $order->total_qty }} arrived</span>
                                </div>
                            </div>

                            <div class="xl:col-span-2">
                                <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Finance</p>
                                <p class="mt-1 text-sm font-black text-slate-900">£{{ number_format($order->grand_total ?? 0, 2) }}</p>
                                <p class="text-xs font-bold {{ $balanceDue > 0 ? 'text-rose-600' : 'text-emerald-600' }}">Due £{{ number_format($balanceDue, 2) }}</p>
                            </div>

                            <div class="xl:col-span-2 flex flex-wrap items-center justify-start gap-2 xl:justify-end">
                                <a href="{{ route('orders.show', $order->id) }}" class="rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white hover:bg-indigo-700">Open ↗</a>
                                <a href="{{ route('money-desk.orders.show', $order->id) }}" class="rounded-2xl bg-purple-50 px-3 py-2.5 text-sm font-black text-purple-700 hover:bg-purple-100">Finance</a>
                                @if (! empty($order->draft_order_id))
                                    <a href="{{ route('draft-orders.show', $order->draft_order_id) }}" class="text-xs font-black text-slate-400 hover:text-indigo-700">Draft ↗</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">No orders found.</div>
                @endforelse
            </div>

            <div class="mt-5">{{ $orders->links() }}</div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('[data-live-search-form]');
            const input = document.querySelector('[data-live-search-input]');
            if (!form || !input) return;

            let timer = null;
            let lastValue = input.value || '';
            const submit = function () {
                if (input.value === lastValue && document.activeElement === input) return;
                lastValue = input.value;
                form.requestSubmit ? form.requestSubmit() : form.submit();
            };

            input.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(submit, 1200);
            });

            form.querySelectorAll('[data-live-search-submit]').forEach(function (field) {
                field.addEventListener('change', function () {
                    form.requestSubmit ? form.requestSubmit() : form.submit();
                });
            });
        });
    </script>
</x-app-layout>
