<x-app-layout>
    <x-slot name="header">Purchasing Desk</x-slot>

    @php
        $activeStatus = $filters['status'] ?? 'to_buy';
        $statusTabs = [
            'to_buy' => ['label' => 'To Buy', 'qty' => $summary['to_buy_qty'] ?? 0, 'orders' => $summary['to_buy_orders'] ?? 0],
            'problems' => ['label' => 'Problems', 'qty' => $summary['problem_qty'] ?? 0, 'orders' => $summary['problem_orders'] ?? 0],
            'awaiting_arrival' => ['label' => 'Awaiting Arrival', 'qty' => $summary['awaiting_arrival_qty'] ?? 0, 'orders' => $summary['awaiting_arrival_orders'] ?? 0],
        ];
        $problemLabels = [
            'supplier_cancelled' => 'Supplier cancelled',
            'lost' => 'Lost',
            'damaged' => 'Damaged',
            'wrong_item' => 'Wrong item',
            'retailer_refunded' => 'Retailer refunded',
            'unavailable' => 'Unavailable',
            'other' => 'Other',
        ];
    @endphp

    <div class="space-y-5">
        <section class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-950">Purchasing Desk</h1>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Order-first purchasing. Each customer order is bought as its own basket, even when the retailer is the same.</p>
                </div>

                <div class="rounded-2xl bg-indigo-50 px-4 py-3 text-sm font-black text-indigo-800 ring-1 ring-indigo-100">
                    {{ number_format($orderGroups->count()) }} order{{ $orderGroups->count() === 1 ? '' : 's' }} shown
                </div>
            </div>

            @if (session('status'))
                <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                @foreach ($statusTabs as $key => $tab)
                    @php
                        $isActive = $activeStatus === $key;
                        $url = route('purchasing.index', array_filter(['status' => $key, 'q' => $filters['q'] ?? null], fn ($v) => $v !== null && $v !== ''));
                    @endphp
                    <a href="{{ $url }}" class="rounded-3xl border p-4 shadow-sm transition {{ $isActive ? 'border-indigo-200 bg-indigo-50 ring-2 ring-indigo-100' : 'border-slate-200 bg-white hover:bg-slate-50' }}">
                        <span class="block text-sm font-black {{ $isActive ? 'text-indigo-800' : 'text-slate-600' }}">{{ $tab['label'] }}</span>
                        <span class="mt-2 block text-3xl font-black tracking-tight text-slate-950">{{ number_format($tab['qty']) }}</span>
                        <span class="mt-1 block text-xs font-bold text-slate-400">{{ number_format($tab['orders']) }} order{{ $tab['orders'] == 1 ? '' : 's' }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('purchasing.index') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_220px_auto] lg:items-center">
                <input type="hidden" name="status" value="{{ $activeStatus }}">
                <div>
                    <label for="q" class="sr-only">Search purchasing</label>
                    <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" type="text" placeholder="Search order number, customer, item, product code or retailer..." class="h-12 w-full rounded-2xl border-slate-300 px-4 text-sm font-semibold shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <select name="status" class="h-12 rounded-2xl border-slate-300 px-4 text-sm font-black text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ($statusTabs as $key => $tab)
                        <option value="{{ $key }}" @selected($activeStatus === $key)>{{ $tab['label'] }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="h-12 rounded-2xl bg-indigo-600 px-5 text-sm font-black text-white shadow-sm transition hover:bg-indigo-700">Search</button>
                    <a href="{{ route('purchasing.index', ['status' => $activeStatus]) }}" class="inline-flex h-12 items-center rounded-2xl border border-slate-200 px-5 text-sm font-black text-slate-600 transition hover:bg-slate-50">Clear</a>
                </div>
            </form>
        </section>

        <section class="space-y-4">
            @forelse ($orderGroups as $orderGroup)
                <article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 bg-slate-50/80 p-4">
                        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-xl font-black text-slate-950">Order {{ $orderGroup->order_number ?: '#' . $orderGroup->order_id }}</h2>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black uppercase tracking-wide text-slate-500 ring-1 ring-slate-200">{{ $orderGroup->retailer_count }} retailer{{ $orderGroup->retailer_count === 1 ? '' : 's' }}</span>
                                </div>
                                <p class="mt-1 text-sm font-semibold text-slate-500">{{ $orderGroup->customer_name ?: 'Customer not named' }}</p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-2xl bg-white px-3 py-2 text-xs font-black text-slate-600 ring-1 ring-slate-200">To buy: {{ $orderGroup->pending_qty }}</span>
                                <span class="rounded-2xl bg-white px-3 py-2 text-xs font-black text-slate-600 ring-1 ring-slate-200">Purchased: {{ $orderGroup->purchased_qty }}</span>
                                <span class="rounded-2xl bg-white px-3 py-2 text-xs font-black text-rose-700 ring-1 ring-rose-100">Problems: {{ $orderGroup->problem_qty }}</span>
                                <span class="rounded-2xl bg-white px-3 py-2 text-xs font-black text-amber-700 ring-1 ring-amber-100">Awaiting: {{ $orderGroup->awaiting_arrival_qty }}</span>
                                <a href="{{ route('orders.show', $orderGroup->order_id) }}" class="inline-flex items-center rounded-2xl bg-slate-900 px-4 py-2 text-xs font-black text-white transition hover:bg-slate-800">Open Order ↗</a>
                            </div>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @foreach ($orderGroup->retailer_groups as $retailerGroup)
                            <div class="p-4">
                                <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                                    <div>
                                        <h3 class="text-sm font-black uppercase tracking-wide text-slate-900">{{ $retailerGroup->name }}</h3>
                                        @if ($retailerGroup->host)
                                            <p class="text-xs font-semibold text-slate-400">{{ $retailerGroup->host }}</p>
                                        @endif
                                    </div>
                                    <p class="text-xs font-black text-slate-500">Pending {{ $retailerGroup->pending_qty }} · Problems {{ $retailerGroup->problem_qty }} · Awaiting {{ $retailerGroup->awaiting_arrival_qty }}</p>
                                </div>

                                <div class="space-y-3">
                                    @foreach ($retailerGroup->items as $item)
                                        @php $problemActionQty = max((int) $item->purchase_remaining_qty, (int) $item->arrival_remaining_qty); @endphp
                                        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                                            <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_340px_340px]">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <p class="truncate text-sm font-black text-slate-950">{{ $item->item_name ?: Str::limit(strip_tags((string) $item->description), 80) }}</p>
                                                        @if ($item->product_url)
                                                            <a href="{{ $item->product_url }}" target="_blank" rel="noopener" class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-black text-slate-600 hover:bg-slate-200" title="Open product link">↗</a>
                                                        @endif
                                                    </div>
                                                    <div class="mt-2 flex flex-wrap gap-2 text-xs font-black">
                                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">Qty {{ $item->quantity }}</span>
                                                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700">To buy {{ $item->purchase_remaining_qty }}</span>
                                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700">Purchased {{ $item->purchased_qty }}</span>
                                                        <span class="rounded-full bg-rose-50 px-2.5 py-1 text-rose-700">Problem {{ $item->problem_qty }}</span>
                                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-700">Awaiting {{ $item->arrival_remaining_qty }}</span>
                                                    </div>
                                                    <dl class="mt-3 grid grid-cols-1 gap-2 text-xs sm:grid-cols-2">
                                                        <div><dt class="font-black uppercase tracking-wide text-slate-400">Product code</dt><dd class="font-semibold text-slate-700">{{ $item->product_code ?: '—' }}</dd></div>
                                                        <div><dt class="font-black uppercase tracking-wide text-slate-400">Retailer ref</dt><dd class="font-semibold text-slate-700">{{ $item->latest_retailer_order_reference ?: $item->retailer_order_reference ?: '—' }}</dd></div>
                                                    </dl>
                                                </div>

                                                <form method="POST" action="{{ route('purchasing.purchases.store') }}" class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-3">
                                                    @csrf
                                                    <input type="hidden" name="order_item_id" value="{{ $item->id }}">
                                                    <p class="mb-2 text-xs font-black uppercase tracking-wide text-emerald-800">Record purchase</p>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <input name="qty" type="number" min="1" max="{{ max(1, $item->purchase_remaining_qty) }}" value="{{ max(1, $item->purchase_remaining_qty) }}" class="h-10 rounded-xl border-slate-300 text-sm font-bold" @disabled($item->purchase_remaining_qty < 1)>
                                                        <input name="purchase_unit_price" type="number" step="0.01" min="0" placeholder="Unit £" class="h-10 rounded-xl border-slate-300 text-sm font-bold" @disabled($item->purchase_remaining_qty < 1)>
                                                        <input name="retailer_order_reference" type="text" placeholder="Supplier ref" class="col-span-2 h-10 rounded-xl border-slate-300 text-sm font-bold" @disabled($item->purchase_remaining_qty < 1)>
                                                        <input name="marketplace_seller" type="text" placeholder="Seller optional" class="col-span-2 h-10 rounded-xl border-slate-300 text-sm font-bold" @disabled($item->purchase_remaining_qty < 1)>
                                                        <input name="ordered_at" type="date" value="{{ now()->toDateString() }}" class="h-10 rounded-xl border-slate-300 text-sm font-bold" @disabled($item->purchase_remaining_qty < 1)>
                                                        <input name="expected_uk_hub_at" type="date" class="h-10 rounded-xl border-slate-300 text-sm font-bold" @disabled($item->purchase_remaining_qty < 1)>
                                                        <textarea name="note" rows="2" placeholder="Notes" class="col-span-2 rounded-xl border-slate-300 text-sm font-bold" @disabled($item->purchase_remaining_qty < 1)></textarea>
                                                    </div>
                                                    <button type="submit" class="mt-2 w-full rounded-xl bg-emerald-600 px-3 py-2 text-xs font-black text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300" @disabled($item->purchase_remaining_qty < 1)>Save purchase</button>
                                                </form>

                                                <form method="POST" action="{{ route('purchasing.problems.store') }}" class="rounded-2xl border border-rose-100 bg-rose-50/60 p-3">
                                                    @csrf
                                                    <input type="hidden" name="order_item_id" value="{{ $item->id }}">
                                                    <p class="mb-2 text-xs font-black uppercase tracking-wide text-rose-800">Record problem</p>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <input name="qty" type="number" min="1" max="{{ max(1, $problemActionQty) }}" value="{{ max(1, $problemActionQty) }}" class="h-10 rounded-xl border-slate-300 text-sm font-bold" @disabled($problemActionQty < 1)>
                                                        <select name="problem_code" class="h-10 rounded-xl border-slate-300 text-sm font-bold" @disabled($problemActionQty < 1)>
                                                            @foreach ($problemLabels as $code => $label)
                                                                <option value="{{ $code }}">{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                        <input name="retailer_order_reference" type="text" placeholder="Supplier ref" class="col-span-2 h-10 rounded-xl border-slate-300 text-sm font-bold" @disabled($problemActionQty < 1)>
                                                        <select name="resolution_action" class="col-span-2 h-10 rounded-xl border-slate-300 text-sm font-bold" @disabled($problemActionQty < 1)>
                                                            <option value="customer_decision_required">Customer decision required</option>
                                                            <option value="repurchase">Repurchase</option>
                                                            <option value="remove_or_credit">Remove / credit later</option>
                                                            <option value="replacement">Replacement</option>
                                                            <option value="wait_for_retailer">Wait for retailer</option>
                                                            <option value="other">Other</option>
                                                        </select>
                                                        <textarea name="problem_notes" rows="3" placeholder="Problem notes" class="col-span-2 rounded-xl border-slate-300 text-sm font-bold" @disabled($problemActionQty < 1)></textarea>
                                                    </div>
                                                    <button type="submit" class="mt-2 w-full rounded-xl bg-rose-600 px-3 py-2 text-xs font-black text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-slate-300" @disabled($problemActionQty < 1)>Save problem</button>
                                                    <p class="mt-2 text-[11px] font-bold leading-snug text-rose-700">Operational only. No invoice, wallet, refund, or payment change.</p>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>
            @empty
                <div class="rounded-[2rem] border border-slate-200 bg-white p-8 text-center shadow-sm">
                    <p class="text-lg font-black text-slate-900">Nothing in this purchasing queue.</p>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Try a different tab or clear the search.</p>
                </div>
            @endforelse
        </section>

        @if ($recentEvents->isNotEmpty())
            <section class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Recent purchasing events</h2>
                <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Order</th>
                                <th class="px-4 py-3">Item</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Qty</th>
                                <th class="px-4 py-3">Ref</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($recentEvents as $event)
                                <tr>
                                    <td class="px-4 py-3 font-black text-slate-800">{{ $event->order_number }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-600">{{ Str::limit($event->item_name, 70) }}</td>
                                    <td class="px-4 py-3 font-black {{ in_array($event->status, ['failed', 'problem']) ? 'text-rose-700' : 'text-emerald-700' }}">{{ str_replace('_', ' ', ucfirst($event->problem_code ?: $event->status)) }}</td>
                                    <td class="px-4 py-3 font-black text-slate-700">{{ $event->qty }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-500">{{ $event->retailer_order_reference ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
