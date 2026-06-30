<x-app-layout>
    @php
        $money = fn ($amount) => '£' . number_format((float) $amount, 2);
        $dateLabel = function ($value) {
            if (empty($value)) {
                return 'Not recorded';
            }
            try {
                return \Carbon\Carbon::parse($value)->format('d M Y');
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        };
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-500">Purchases</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">Purchased History</h1>
                    <p class="mt-1 max-w-3xl text-sm font-normal leading-6 text-slate-600">
                        Search everything that has already been purchased, including lines that are waiting for arrival, arrived, customer informed or collected.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-4 lg:min-w-[520px]">
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-100">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Lines</p>
                        <p class="mt-1 text-lg font-semibold text-slate-950">{{ $summary['lines_count'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-100">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Units</p>
                        <p class="mt-1 text-lg font-semibold text-slate-950">{{ $summary['units_count'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-100">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Total</p>
                        <p class="mt-1 text-lg font-semibold text-slate-950">{{ $money($summary['purchase_total'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-2xl bg-amber-50 px-4 py-3 ring-1 ring-amber-100">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-amber-700">Pending arrival</p>
                        <p class="mt-1 text-lg font-semibold text-amber-800">{{ $summary['pending_arrival_count'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('purchases.history') }}" class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 lg:grid-cols-12 lg:items-end">
                <label class="lg:col-span-4">
                    <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Search</span>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Order, customer, item, retailer, reference..." class="mt-1 w-full rounded-2xl border-slate-200 text-sm shadow-sm focus:border-indigo-400 focus:ring-indigo-400">
                </label>

                <label class="lg:col-span-2">
                    <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</span>
                    <select name="status" class="mt-1 w-full rounded-2xl border-slate-200 text-sm shadow-sm focus:border-indigo-400 focus:ring-indigo-400">
                        @foreach ([
                            'all' => 'All statuses',
                            'pending_arrival' => 'Pending arrival',
                            'arrived' => 'Arrived',
                            'customer_informed' => 'Customer informed',
                            'ready_for_collection' => 'Ready for collection',
                            'collected' => 'Collected',
                            'delivered' => 'Delivered',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? 'all') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="lg:col-span-2">
                    <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Retailer</span>
                    <select name="retailer_id" class="mt-1 w-full rounded-2xl border-slate-200 text-sm shadow-sm focus:border-indigo-400 focus:ring-indigo-400">
                        <option value="">All retailers</option>
                        @foreach ($retailers as $retailer)
                            <option value="{{ $retailer->id }}" @selected((string)($filters['retailer_id'] ?? '') === (string)$retailer->id)>{{ $retailer->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="lg:col-span-1">
                    <span class="text-xs font-medium uppercase tracking-wide text-slate-500">From</span>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 w-full rounded-2xl border-slate-200 text-sm shadow-sm focus:border-indigo-400 focus:ring-indigo-400">
                </label>

                <label class="lg:col-span-1">
                    <span class="text-xs font-medium uppercase tracking-wide text-slate-500">To</span>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 w-full rounded-2xl border-slate-200 text-sm shadow-sm focus:border-indigo-400 focus:ring-indigo-400">
                </label>

                <label class="flex items-center gap-2 rounded-2xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 lg:col-span-1">
                    <input type="checkbox" name="my" value="1" @checked($filters['my'] ?? false) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Mine
                </label>

                <div class="flex gap-2 lg:col-span-1">
                    <button type="submit" class="w-full rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Search</button>
                </div>
            </div>
        </form>

        <div class="space-y-3">
            @forelse ($purchases as $purchase)
                <article class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $purchase->lifecycle_badge_classes }}">{{ $purchase->lifecycle_label }}</span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200">{{ $purchase->retailer_name }}</span>
                                @if (! empty($purchase->retailer_order_reference))
                                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 ring-1 ring-indigo-100">Ref {{ $purchase->retailer_order_reference }}</span>
                                @endif
                            </div>

                            <h2 class="mt-3 text-base font-semibold leading-6 text-slate-950">{{ $purchase->item_display_name }}</h2>
                            <p class="mt-1 text-sm font-normal text-slate-600">
                                Order <a href="{{ route('orders.show', $purchase->order_id) }}" class="font-semibold text-indigo-600 hover:text-indigo-500">{{ $purchase->order_number }}</a>
                                · {{ $purchase->customer_display_name }}
                            </p>

                            <div class="mt-3 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-5">
                                <div>
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Purchased</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ (int) $purchase->qty }} unit{{ (int) $purchase->qty === 1 ? '' : 's' }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Arrived</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ (int) $purchase->arrived_qty }} / {{ (int) $purchase->qty }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Purchase price</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ $money($purchase->purchase_line_total ?? 0) }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Purchased date</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ $dateLabel($purchase->purchased_at_display) }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">ETA</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ $dateLabel($purchase->estimated_retailer_delivery_date) }}</p>
                                </div>
                            </div>

                            @if (! empty($purchase->note) || ! empty($purchase->marketplace_seller))
                                <div class="mt-3 rounded-2xl bg-slate-50 px-3 py-2 text-sm text-slate-600 ring-1 ring-slate-100">
                                    @if (! empty($purchase->marketplace_seller))
                                        <span class="font-medium text-slate-700">Seller:</span> {{ $purchase->marketplace_seller }}
                                    @endif
                                    @if (! empty($purchase->note))
                                        <span class="{{ ! empty($purchase->marketplace_seller) ? 'ml-2' : '' }}">{{ $purchase->note }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="flex shrink-0 flex-wrap gap-2 lg:w-44 lg:flex-col">
                            <a href="{{ route('orders.show', $purchase->order_id) }}" class="rounded-2xl border border-slate-200 px-4 py-2 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Open order</a>
                            <a href="{{ route('purchases.orders.show', ['order' => $purchase->order_id, 'view' => 'purchased']) }}" class="rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-center text-sm font-semibold text-indigo-700 hover:bg-indigo-100">View batch</a>
                            @if ((int) $purchase->arrived_qty < 1)
                                <a href="{{ route('purchases.orders.show', ['order' => $purchase->order_id, 'view' => 'purchased']) }}" class="rounded-2xl border border-slate-200 px-4 py-2 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Edit / Undo</a>
                            @else
                                <span class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-center text-sm font-medium text-slate-500">Arrival linked</span>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                    <p class="text-base font-semibold text-slate-900">No purchased items found.</p>
                    <p class="mt-1 text-sm text-slate-500">Try a broader search or choose “All statuses”.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
