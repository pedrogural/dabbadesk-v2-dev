<x-app-layout>
    <x-slot name="header">Orders</x-slot>

    @php
        $currentModeLabel = empty($filters['show_history']) ? 'Current revisions only' : 'History included';
        $currentModeClass = empty($filters['show_history'])
            ? 'bg-emerald-50 text-emerald-700 ring-emerald-100'
            : 'bg-amber-50 text-amber-700 ring-amber-100';
    @endphp

    <div class="space-y-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('orders.index') }}" data-live-search-form class="space-y-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex min-w-0 flex-wrap items-center gap-3">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M20 6 9 17l-5-5" /></svg>
                        </span>
                        <span class="rounded-full px-3 py-1 text-xs font-black uppercase tracking-wide ring-1 {{ $currentModeClass }}">{{ $currentModeLabel }}</span>
                    </div>
                    <p class="text-sm font-black text-slate-500">{{ number_format($orders->total()) }} orders</p>
                </div>

                <div class="grid grid-cols-1 gap-3 xl:grid-cols-[minmax(340px,0.75fr)_220px_auto] xl:items-center">
                    <div class="relative">
                        <label for="q" class="sr-only">Search orders</label>
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.3-4.3" /></svg>
                        </span>
                        <input
                            id="q"
                            name="q"
                            value="{{ $filters['q'] }}"
                            type="text"
                            autocomplete="off"
                            placeholder="Search orders..."
                            data-live-search-input
                            class="h-[48px] w-full rounded-2xl border-slate-300 pl-12 pr-4 text-sm font-semibold shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div>
                        <label for="status" class="sr-only">Status</label>
                        <select id="status" name="status" class="h-[46px] w-full rounded-2xl border-slate-300 px-4 text-sm font-bold text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" data-live-search-submit>
                            <option value="">All active statuses</option>
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ match($status) { 'paid' => 'Paid', 'unpaid' => 'Unpaid', 'purchased' => 'Purchased', 'arrived' => 'Arrived', 'partially_purchased' => 'Partially purchased', 'customer_informed' => 'Customer informed', 'cancelled' => 'Cancelled', 'canceled' => 'Cancelled', default => str_replace('_', ' ', ucfirst($status)) } }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 xl:justify-end">
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

                <p class="text-xs font-semibold text-slate-400">Search updates automatically after a short pause. Default view shows unpaid orders. “All active statuses” excludes cancelled orders; use the Cancelled filter to view cancelled orders.</p>
            </form>
        </div>

        <div class="space-y-3">
            @if ($orders->count())
            @foreach ($orders as $order)
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
                    $totalQty = max(0, (int) ($order->total_qty ?? 0));
                    $purchasedQty = min($totalQty, max(0, (int) ($order->purchased_qty ?? 0)));
                    $arrivedQty = min($totalQty, max(0, (int) ($order->arrived_qty ?? 0)));
                    $purchasedPct = $totalQty > 0 ? min(100, round(($purchasedQty / $totalQty) * 100)) : 0;
                    $arrivedPct = $totalQty > 0 ? min(100, round(($arrivedQty / $totalQty) * 100)) : 0;
                    $createdBy = $order->created_by_name ?: 'Unknown';
                    $isCustomerSelfPurchase = ($order->purchase_mode ?? 'standard') === 'customer_self_purchase';
                @endphp

                <article class="rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50/20">
                    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(250px,1.35fr)_180px_190px_minmax(320px,1.2fr)_360px] xl:items-center">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-black leading-tight text-slate-950">Order #{{ $order->order_number }}</h3>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide {{ $paymentBadgeClass }}">{{ $paymentLabel }}</span>
                                @if (($order->revision_state ?? 'current') === 'superseded')
                                    <span class="rounded-full bg-rose-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-rose-700">Superseded</span>
                                @elseif (($order->revision_state ?? 'current') === 'current_revision')
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-emerald-700">Current</span>
                                @endif
                                @if ($isCustomerSelfPurchase)
                                    <span class="rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-sky-700">Self-purchase</span>
                                @endif
                                @if ($showLegacyException)
                                    <span class="rounded-full bg-rose-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-rose-700">Cancelled</span>
                                @endif
                                @if ($revisionTotal > 1)
                                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-indigo-700">Rev {{ $order->revision_number ?? 1 }}/{{ $revisionTotal }}</span>
                                @endif
                            </div>
                            <p class="mt-2 truncate text-xl font-black text-slate-900">
                                <a href="{{ route('customers.show', $order->customer_id) }}" class="hover:text-indigo-700 hover:underline">{{ $customerName }}</a>
                            </p>
                            <p class="mt-1 truncate text-xs font-semibold text-slate-400">
                                {{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') : 'No date' }}
                                @if ($order->bill_to_email)
                                    · {{ $order->bill_to_email }}
                                @endif
                            </p>
                        </div>

                        <div class="border-slate-200 xl:border-l xl:pl-5">
                            <p class="text-xs font-bold text-slate-500">Created By</p>
                            <div class="mt-2 flex items-center gap-2 text-sm font-black text-slate-800">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-slate-500"><path d="M20 21a8 8 0 0 0-16 0" /><circle cx="12" cy="7" r="4" /></svg>
                                <span class="truncate">{{ $createdBy }}</span>
                            </div>
                        </div>

                        <div class="border-slate-200 xl:border-l xl:pl-5">
                            <p class="text-xs font-bold text-slate-500">Finance</p>
                            <p class="mt-1 text-2xl font-black {{ $balanceDue > 0 ? 'text-slate-950' : 'text-emerald-700' }}">£{{ number_format($order->grand_total ?? 0, 2) }}</p>
                            <p class="mt-1 text-sm font-bold {{ $balanceDue > 0 ? 'text-rose-600' : 'text-slate-500' }}">Due £{{ number_format($balanceDue, 2) }}</p>
                        </div>

                        <div class="border-slate-200 xl:border-l xl:pl-5">
                            <p class="text-xs font-bold text-slate-500">Progress</p>
                            <div class="mt-2 space-y-2.5">
                                <div class="grid grid-cols-[30px_118px_minmax(80px,1fr)] items-center gap-2">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" class="h-4.5 w-4.5"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h8.72a2 2 0 0 0 2-1.58L21 7H5.12"/></svg>
                                    </span>
                                    <span class="text-sm font-black text-slate-800">
                                        @if ($isCustomerSelfPurchase)
                                            <span class="text-sky-700">Customer buys</span>
                                        @else
                                            <span class="text-emerald-700">{{ $purchasedQty }}/{{ $totalQty }}</span> Purchased
                                        @endif
                                    </span>
                                    <span class="h-2 rounded-full bg-slate-200"><span class="block h-2 rounded-full bg-emerald-600" style="width: {{ $isCustomerSelfPurchase ? 100 : $purchasedPct }}%"></span></span>
                                </div>
                                <div class="grid grid-cols-[30px_118px_minmax(80px,1fr)] items-center gap-2">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-sky-50 text-slate-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" class="h-4.5 w-4.5"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                                    </span>
                                    <span class="text-sm font-black text-slate-800"><span class="text-sky-700">{{ $arrivedQty }}/{{ $totalQty }}</span> Arrived</span>
                                    <span class="h-2 rounded-full bg-slate-200"><span class="block h-2 rounded-full bg-sky-600" style="width: {{ $arrivedPct }}%"></span></span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 border-slate-200 xl:border-l xl:pl-5">
                            <a href="{{ route('orders.show', $order->id) }}" class="group rounded-2xl border border-slate-200 bg-white px-3 py-3 text-center shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50">
                                <span class="mx-auto inline-flex h-8 w-8 items-center justify-center text-indigo-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-7 w-7"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                                </span>
                                <span class="mt-1 block text-sm font-black text-indigo-700">Order</span>
                                <span class="mt-0.5 block text-[11px] font-bold text-slate-500">View order ↗</span>
                            </a>

                            @if (! empty($order->draft_order_id))
                                <a href="{{ route('draft-orders.show', $order->draft_order_id) }}" class="group rounded-2xl border border-slate-200 bg-white px-3 py-3 text-center shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
                                    <span class="mx-auto inline-flex h-8 w-8 items-center justify-center text-slate-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-7 w-7"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 13h8"/><path d="M8 17h5"/></svg>
                                    </span>
                                    <span class="mt-1 block text-sm font-black text-slate-700">Draft</span>
                                    <span class="mt-0.5 block text-[11px] font-bold text-slate-500">View draft ↗</span>
                                </a>
                            @else
                                <span class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-3 py-3 text-center opacity-60">
                                    <span class="mx-auto inline-flex h-8 w-8 items-center justify-center text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-7 w-7"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                                    </span>
                                    <span class="mt-1 block text-sm font-black text-slate-500">Draft</span>
                                    <span class="mt-0.5 block text-[11px] font-bold text-slate-400">Unavailable</span>
                                </span>
                            @endif

                            <a href="{{ route('money-desk.orders.show', $order->id) }}" class="group rounded-2xl border border-slate-200 bg-white px-3 py-3 text-center shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50">
                                <span class="mx-auto inline-flex h-8 w-8 items-center justify-center text-emerald-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-7 w-7"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3v4a1 1 0 0 1-1 1H5a2 2 0 0 1-2-2V5"/><path d="M18 12h.01"/></svg>
                                </span>
                                <span class="mt-1 block text-sm font-black text-emerald-700">Finance</span>
                                <span class="mt-0.5 block text-[11px] font-bold text-slate-500">View finance ↗</span>
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
            @else
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-4 py-10 text-center text-sm text-slate-500 shadow-sm">No orders found.</div>
            @endif
        </div>

        <div>{{ $orders->links() }}</div>
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
