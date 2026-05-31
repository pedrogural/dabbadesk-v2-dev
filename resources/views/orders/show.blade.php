<x-app-layout>
    <x-slot name="header">
        Order #{{ $order->order_number }} · Rev {{ $order->revision_number ?? 1 }}
    </x-slot>

    @php
        $customerRequestNotes = collect($notes ?? [])->filter(function ($note) {
            return ($note->type ?? '') === 'order_request_note' || ($note->title ?? '') === 'Customer order request notes';
        })->values();
    @endphp

    <div class="space-y-6">

        <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <a href="{{ route('orders.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                        ← Back to Orders
                    </a>

                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-bold text-slate-950">
                            Order #{{ $order->order_number }}
                        </h1>

                        <span class="rounded-full bg-indigo-100 px-3 py-1 text-sm font-semibold text-indigo-700">
                            {{ str_replace('_', ' ', ucfirst($order->status)) }}
                        </span>

                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-black text-indigo-700">
                            Rev {{ $order->revision_number ?? 1 }}
                        </span>

                        @if (($order->revision_state ?? 'current') === 'superseded')
                            <span class="rounded-full bg-rose-100 px-3 py-1 text-sm font-black text-rose-700">Superseded</span>
                        @elseif (($order->revision_state ?? 'current') === 'current_revision')
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-black text-emerald-700">Current revision</span>
                        @endif

                        @if (($progress['remaining_purchase_qty'] ?? 0) > 0)
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-700">
                                {{ $progress['remaining_purchase_qty'] }} still to purchase
                            </span>
                        @endif
                    </div>

                    <p class="mt-2 text-sm text-slate-500">
                        Placed {{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') : 'date unknown' }}
                        · Order ID {{ $order->id }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a
                        href="{{ route('draft-orders.show', $order->draft_order_id) }}"
                        class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                    >
                        Open Draft
                    </a>

                    <a
                        href="{{ route('money-desk.orders.show', $order->id) }}"
                        class="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-700"
                    >
                        Finance view
                    </a>

                    <a
                        href="{{ route('money-desk.customers.show', $order->customer_id) }}"
                        class="rounded-2xl bg-indigo-100 px-4 py-3 text-sm font-semibold text-indigo-700 hover:bg-indigo-200"
                    >
                        Customer finance
                    </a>
                </div>
            </div>
        </div>

        @if ($customerRequestNotes->isNotEmpty())
            <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-700">Customer order request notes</p>
                        <h2 class="mt-1 text-lg font-black text-amber-950">Original customer notes carried through from request</h2>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black uppercase tracking-wide text-amber-700 ring-1 ring-amber-200">Pinned lifecycle note</span>
                </div>

                <div class="mt-4 space-y-3">
                    @foreach ($customerRequestNotes as $requestNote)
                        <div class="rounded-2xl bg-white/80 px-4 py-3 text-sm leading-6 text-amber-950 ring-1 ring-amber-100">
                            <p class="whitespace-pre-line">{{ $requestNote->body }}</p>
                            <p class="mt-2 text-xs font-semibold text-amber-700">
                                {{ ($requestNote->occurred_at ?: $requestNote->created_at) ? \Carbon\Carbon::parse($requestNote->occurred_at ?: $requestNote->created_at)->format('d M Y H:i') : 'Date unknown' }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <div class="xl:col-span-4 rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Customer</p>

                        <h2 class="mt-3 text-xl font-bold text-slate-950">
                            {{ $order->bill_to_name ?: trim(($order->first_name ?? '') . ' ' . ($order->last_name ?? '')) ?: 'Unknown customer' }}
                        </h2>

                        @if ($order->bill_to_company || $order->company_name)
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $order->bill_to_company ?: $order->company_name }}
                            </p>
                        @endif
                    </div>

                    <a
                        href="{{ route('money-desk.customers.show', $order->customer_id) }}"
                        title="Open customer finance"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-slate-600 hover:bg-indigo-100 hover:text-indigo-700"
                    >
                        ↗
                    </a>
                </div>

                <div class="mt-5 space-y-3 text-sm text-slate-600">
                    @if ($order->bill_to_email)
                        <p>✉ {{ $order->bill_to_email }}</p>
                    @endif

                    @if ($order->bill_to_phone)
                        <p>☎ {{ $order->bill_to_phone }}</p>
                    @endif

                    @if ($order->bill_to_address_line1 || $order->bill_to_postcode)
                        <p class="leading-6">
                            {{ $order->bill_to_address_line1 }}
                            @if ($order->bill_to_postcode)
                                <br>{{ $order->bill_to_postcode }}
                            @endif
                        </p>
                    @endif
                </div>
            </div>

            <div class="xl:col-span-5 rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Order health</p>

                <div class="mt-5 grid grid-cols-2 gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Items</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ $progress['item_qty'] ?? 0 }}</p>
                        <p class="mt-1 text-xs text-slate-500">requested</p>
                    </div>

                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-emerald-600">Purchased</p>
                        <p class="mt-2 text-2xl font-bold text-emerald-700">{{ $progress['purchased_qty'] ?? 0 }}</p>
                        <p class="mt-1 text-xs text-emerald-700">bought so far</p>
                    </div>

                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-rose-600">Remaining</p>
                        <p class="mt-2 text-2xl font-bold text-rose-700">{{ $progress['remaining_purchase_qty'] ?? 0 }}</p>
                        <p class="mt-1 text-xs text-rose-700">still to buy</p>
                    </div>

                    <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-sky-600">Arrived</p>
                        <p class="mt-2 text-2xl font-bold text-sky-700">{{ $progress['arrived_qty'] ?? 0 }}</p>
                        <p class="mt-1 text-xs text-sky-700">received</p>
                    </div>

                    <div class="rounded-2xl border border-purple-200 bg-purple-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-purple-600">Ready</p>
                        <p class="mt-2 text-2xl font-bold text-purple-700">{{ $progress['ready_qty'] ?? 0 }}</p>
                        <p class="mt-1 text-xs text-purple-700">collection/delivery</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Completed</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ $progress['collected_qty'] ?? 0 }}</p>
                        <p class="mt-1 text-xs text-slate-500">collected/delivered</p>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-3 rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Order summary</p>

                <div class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Items subtotal</span>
                        <span class="font-semibold text-slate-900">£{{ number_format($order->subtotal ?? 0, 2) }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Delivery fees</span>
                        <span class="font-semibold text-slate-900">£{{ number_format($order->retailer_delivery_fee_total ?? 0, 2) }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Dabba fees</span>
                        <span class="font-semibold text-slate-900">£{{ number_format($order->dabba_fee_amount ?? 0, 2) }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Paid / settled</span>
                        <span class="font-semibold text-emerald-600">£{{ number_format($finance['settled_total'] ?? 0, 2) }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Balance due</span>
                        <span class="font-semibold {{ ($finance['balance_due'] ?? 0) > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                            £{{ number_format($finance['balance_due'] ?? 0, 2) }}
                        </span>
                    </div>

                    <div class="border-t border-slate-200 pt-4">
                        <div class="flex justify-between gap-4">
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Total</span>
                            <span class="text-2xl font-bold text-slate-950">£{{ number_format($order->grand_total ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (($progress['remaining_purchase_qty'] ?? 0) > 0)
            <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5">
                <p class="text-sm font-semibold text-amber-800">
                    {{ $progress['remaining_purchase_qty'] }} item(s) still need purchasing.
                </p>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <div class="xl:col-span-8 rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Items grouped by retailer</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Product links, purchase progress and arrival progress are grouped by retailer.
                        </p>
                    </div>

                    <span class="text-sm text-slate-400">
                        {{ $retailerGroups->count() }} retailer group(s)
                    </span>
                </div>

                <div class="mt-6 space-y-6">
                    @forelse ($retailerGroups as $group)
                        <div class="overflow-hidden rounded-3xl border border-slate-200">
                            <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="font-bold text-slate-950">{{ $group->name }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $group->item_count }} line(s) · Qty {{ $group->total_qty }}
                                        @if ($group->host && $group->host !== $group->name)
                                            · {{ $group->host }}
                                        @endif
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2 text-xs font-semibold">
                                    <span class="rounded-full bg-white px-3 py-2 text-slate-700 ring-1 ring-slate-200">
                                        Total £{{ number_format($group->line_total ?? 0, 2) }}
                                    </span>

                                    <span class="rounded-full bg-emerald-100 px-3 py-2 text-emerald-700">
                                        Purchased {{ $group->purchased_qty }}/{{ $group->total_qty }}
                                    </span>

                                    @if ($group->remaining_qty > 0)
                                        <span class="rounded-full bg-rose-100 px-3 py-2 text-rose-700">
                                            Remaining {{ $group->remaining_qty }}
                                        </span>
                                    @endif

                                    <span class="rounded-full bg-sky-100 px-3 py-2 text-sky-700">
                                        Arrived {{ $group->arrived_qty }}/{{ $group->total_qty }}
                                    </span>
                                </div>
                            </div>

                            <div class="divide-y divide-slate-100">
                                @foreach ($group->items as $item)
                                    <div class="p-5 {{ $item->requires_inspection ? 'bg-purple-50/60' : 'bg-white' }}">
                                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-center">
                                            <div class="lg:col-span-5">
                                                <div class="flex items-start gap-3">
                                                    @if ($item->product_url)
                                                        <a
                                                            href="{{ $item->product_url }}"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            title="Open product page"
                                                            class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 text-lg font-bold text-indigo-700 hover:bg-indigo-100"
                                                        >
                                                            ↗
                                                        </a>
                                                    @else
                                                        <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-300">
                                                            —
                                                        </span>
                                                    @endif

                                                    <div>
                                                        <h4 class="font-bold text-slate-950">{{ $item->item_name }}</h4>

                                                        <div class="mt-2 flex flex-wrap gap-2">
                                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                                Qty {{ $item->quantity }}
                                                            </span>

                                                            @if ($item->product_code)
                                                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                                    {{ $item->product_code }}
                                                                </span>
                                                            @endif

                                                            @if ($item->requires_inspection)
                                                                <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700">
                                                                    Purple check
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="lg:col-span-2">
                                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Purchase</p>
                                                <p class="mt-1 font-semibold {{ $item->purchase_remaining_qty > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                                    {{ $item->purchased_qty }}/{{ $item->quantity }}
                                                </p>
                                                <p class="mt-1 text-xs text-slate-400">
                                                    {{ $item->purchase_remaining_qty > 0 ? 'Pending purchase' : 'Purchased' }}
                                                </p>
                                            </div>

                                            <div class="lg:col-span-2">
                                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Arrival</p>
                                                <p class="mt-1 font-semibold {{ $item->arrived_qty > 0 ? 'text-sky-600' : 'text-slate-500' }}">
                                                    {{ $item->arrived_qty }}/{{ $item->quantity }}
                                                </p>
                                                <p class="mt-1 text-xs text-slate-400">
                                                    {{ $item->latest_arrival_status ? str_replace('_', ' ', $item->latest_arrival_status) : 'Not arrived' }}
                                                </p>
                                            </div>

                                            <div class="lg:col-span-3 lg:text-right">
                                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Price</p>
                                                <p class="mt-1 text-lg font-bold text-slate-950">
                                                    £{{ number_format($item->line_total ?? 0, 2) }}
                                                </p>

                                                @if ($item->latest_retailer_order_reference || $item->retailer_order_reference)
                                                    <p class="mt-1 text-xs text-slate-500">
                                                        Ref: {{ $item->latest_retailer_order_reference ?: $item->retailer_order_reference }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>

                                        @if ($item->inspection_note)
                                            <p class="mt-4 rounded-2xl bg-purple-100 px-4 py-3 text-sm text-purple-800">
                                                {{ $item->inspection_note }}
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                            No items found for this order.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="xl:col-span-4 space-y-6">
                <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                    <h2 class="text-lg font-bold text-slate-950">Operations</h2>

                    <div class="mt-5 space-y-3">
                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                            <span class="text-sm font-semibold text-slate-700">Active order</span>
                            <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold text-indigo-700">{{ $progress['item_qty'] ?? 0 }}</span>
                        </div>

                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                            <span class="text-sm font-semibold text-slate-700">Purchased</span>
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">{{ $progress['purchased_qty'] ?? 0 }}</span>
                        </div>

                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                            <span class="text-sm font-semibold text-slate-700">Arrived</span>
                            <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-bold text-sky-700">{{ $progress['arrived_qty'] ?? 0 }}</span>
                        </div>

                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                            <span class="text-sm font-semibold text-slate-700">Ready</span>
                            <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-bold text-purple-700">{{ $progress['ready_qty'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                    <h2 class="text-lg font-bold text-slate-950">Quick links</h2>

                    <div class="mt-5 divide-y divide-slate-100">
                        <a href="{{ route('money-desk.orders.show', $order->id) }}" class="flex items-center justify-between py-3 text-sm font-semibold text-slate-700 hover:text-emerald-700">
                            View order finance <span>↗</span>
                        </a>

                        <a href="{{ route('money-desk.customers.show', $order->customer_id) }}" class="flex items-center justify-between py-3 text-sm font-semibold text-slate-700 hover:text-indigo-700">
                            View customer finance <span>↗</span>
                        </a>

                        <a href="{{ route('orders.index') }}?q={{ urlencode($order->bill_to_email ?: $order->bill_to_name) }}" class="flex items-center justify-between py-3 text-sm font-semibold text-slate-700 hover:text-indigo-700">
                            Search related orders <span>↗</span>
                        </a>
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                    <h2 class="text-lg font-bold text-slate-950">Latest purchase events</h2>

                    <div class="mt-5 space-y-3">
                        @forelse ($purchases->take(5) as $purchase)
                            <div class="rounded-2xl border border-slate-200 p-4 {{ $purchase->requires_marking_attention ? 'border-purple-300 bg-purple-50/60' : '' }}">
                                <p class="font-semibold text-slate-900">{{ $purchase->item_name }}</p>
                                <p class="mt-1 text-sm text-slate-500">
                                    Qty {{ $purchase->qty }} · {{ str_replace('_', ' ', $purchase->status) }}
                                </p>

                                @if ($purchase->retailer_order_reference)
                                    <p class="mt-2 text-xs text-slate-400">Ref: {{ $purchase->retailer_order_reference }}</p>
                                @endif
                            </div>
                        @empty
                            <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                                No purchase events yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-950">Arrival / collection events</h2>

                <div class="mt-5 space-y-3">
                    @forelse ($arrivals->take(8) as $arrival)
                        <div class="rounded-2xl border border-slate-200 p-4 {{ $arrival->requires_marking_attention ? 'border-purple-300 bg-purple-50/60' : '' }}">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $arrival->item_name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Qty {{ $arrival->qty }} · {{ str_replace('_', ' ', $arrival->status) }}
                                    </p>

                                    @if ($arrival->notes)
                                        <p class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-600">
                                            {{ $arrival->notes }}
                                        </p>
                                    @endif
                                </div>

                                <p class="text-xs text-slate-400">
                                    {{ $arrival->matched_at ? \Carbon\Carbon::parse($arrival->matched_at)->format('d M Y') : 'No date' }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                            No arrival events yet.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-950">Notes and activity</h2>

                <div class="mt-5 space-y-3">
                    @forelse ($notes->take(8) as $note)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($note->is_pinned)
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">pinned</span>
                                @endif

                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                    {{ str_replace('_', ' ', $note->type) }}
                                </span>

                                @if ($note->title)
                                    <span class="font-semibold text-slate-900">{{ $note->title }}</span>
                                @endif
                            </div>

                            <p class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ $note->body }}</p>

                            <p class="mt-3 text-xs text-slate-400">
                                {{ ($note->occurred_at ?: $note->created_at) ? \Carbon\Carbon::parse($note->occurred_at ?: $note->created_at)->format('d M Y H:i') : 'No date' }}
                            </p>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                            No notes found.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-indigo-200 bg-indigo-50 px-5 py-4 text-sm font-semibold text-indigo-800">
            Read-only view. No changes can be made to orders from this screen.
        </div>

    </div>
</x-app-layout>