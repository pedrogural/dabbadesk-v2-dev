<x-app-layout>
    <x-slot name="header">Orders</x-slot>

    <div class="space-y-6">
        <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Orders</h1>
                    <p class="mt-1 text-sm text-slate-500">Read-only order desk for finding orders, checking progress, finance position, purchasing and arrival status.</p>
                </div>
                <span class="inline-flex w-fit rounded-full bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-700">Read-only mode</span>
            </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
            <form method="GET" action="{{ route('orders.index') }}" class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-end">
                <div class="lg:col-span-6">
                    <label for="q" class="text-sm font-semibold text-slate-700">Search orders</label>
                    <input id="q" name="q" value="{{ $filters['q'] }}" type="text" placeholder="Request/order number, customer, email, item, SKU, retailer ref or tracking" class="mt-2 w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="lg:col-span-3">
                    <label for="status" class="text-sm font-semibold text-slate-700">Status</label>
                    <select id="status" name="status" class="mt-2 w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-1 space-y-2">
                    <label class="flex min-h-[48px] items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm">
                        <input type="checkbox" name="mine" value="1" @checked(! empty($filters['mine'])) onchange="this.form.submit()" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Mine
                    </label>
                </div>

                <div class="lg:col-span-1">
                    <label class="flex min-h-[48px] items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm">
                        <input type="checkbox" name="show_history" value="1" @checked(! empty($filters['show_history'])) onchange="this.form.submit()" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        History
                    </label>
                </div>

                <div class="lg:col-span-1 flex gap-2">
                    <button type="submit" class="w-full rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Search</button>
                    @if ($filters['q'] || $filters['status'] || ! empty($filters['mine']) || ! empty($filters['show_history']))
                        <a href="{{ route('orders.index') }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-200">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-bold text-slate-900">Order results</h2>
                <p class="text-sm text-slate-500">Showing {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }}</p>
            </div>

            <div class="mt-5 space-y-4">
                @forelse ($orders as $order)
                    @php
                        $customerName = $order->bill_to_name ?: (trim(($order->first_name ?? '') . ' ' . ($order->last_name ?? '')) ?: ($order->company_name ?: 'Unknown customer'));
                    @endphp
                    <div class="rounded-3xl border border-slate-200 p-5 hover:border-indigo-200 hover:bg-indigo-50/30">
                        <div class="grid grid-cols-1 gap-4 xl:grid-cols-12 xl:items-center">
                            <div class="xl:col-span-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-lg font-black text-slate-950">Order #{{ $order->order_number }}</h3>
                                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700">Rev {{ $order->revision_number ?? 1 }}</span>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ str_replace('_', ' ', $order->status) }}</span>
                                    @if (($order->revision_state ?? 'current') === 'superseded')
                                        <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-700">Superseded</span>
                                    @elseif (($order->revision_state ?? 'current') === 'current_revision')
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Current revision</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm font-black text-slate-700">
                                    <a href="{{ route('customers.show', $order->customer_id) }}" class="hover:text-indigo-700 hover:underline">{{ $customerName }}</a>
                                </p>
                                <p class="mt-1 text-xs text-slate-400">
                                    Order ID {{ $order->id }} · {{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') : 'No date' }}
                                    @if ($order->bill_to_email)
                                        · {{ $order->bill_to_email }}
                                    @endif
                                </p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">
                                    Created by {{ $order->created_by_name ?: 'Unknown user' }}
                                    @if (($order->updated_by_name ?? null) && $order->updated_by_name !== $order->created_by_name)
                                        · Updated by {{ $order->updated_by_name }}
                                    @endif
                                </p>
                            </div>

                            <div class="xl:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Items</p>
                                <p class="mt-1 text-sm font-semibold text-slate-800">{{ $order->item_count }} lines / {{ $order->total_qty }} qty</p>
                            </div>

                            <div class="xl:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Progress</p>
                                <p class="mt-1 text-sm font-semibold text-slate-800">Purchased {{ $order->purchased_qty }} · Arrived {{ $order->arrived_qty }}</p>
                            </div>

                            <div class="xl:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Finance</p>
                                <p class="mt-1 text-sm font-semibold text-slate-800">£{{ number_format($order->grand_total ?? 0, 2) }}</p>
                                <p class="mt-1 text-xs {{ ($order->balance_due ?? 0) > 0 ? 'text-rose-600' : 'text-slate-400' }}">Due: £{{ number_format($order->balance_due ?? 0, 2) }}</p>
                            </div>

                            <div class="xl:col-span-2 flex flex-wrap justify-start gap-2 xl:justify-end">
                                <a href="{{ route('orders.show', $order->id) }}" class="rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Open order</a>
                                @if (! empty($order->draft_order_id))
                                    <a href="{{ route('draft-orders.show', $order->draft_order_id) }}" class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-200">Open Draft</a>
                                @endif
                                <a href="{{ route('money-desk.orders.show', $order->id) }}" class="rounded-2xl bg-emerald-100 px-4 py-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-200">Finance</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">No orders found.</div>
                @endforelse
            </div>

            <div class="mt-6">{{ $orders->links() }}</div>
        </div>
    </div>
</x-app-layout>
