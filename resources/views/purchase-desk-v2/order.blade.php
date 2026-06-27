<x-app-layout>
    @php
        $money = fn ($value) => '£' . number_format((float) $value, 2);
        $customer = $order->bill_to_company ?: ($order->bill_to_name ?: 'Unknown customer');
        $fmtDate = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d M Y') : '—';
        $currentView = $filters['view'] ?? 'all';
    @endphp

    <div class="mx-auto max-w-7xl space-y-5">
        <section class="rounded-[1.75rem] border border-indigo-100 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <a href="{{ route('purchase-desk-v2.index') }}" class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600 hover:text-indigo-800">← Back to purchasing queue</a>
                    <h1 class="mt-3 text-2xl font-black tracking-tight text-slate-950">Purchase items · Order #{{ $order->order_number }}</h1>
                    <p class="mt-1 text-sm font-bold text-slate-700">{{ $customer }}</p>
                    <p class="mt-1 text-xs font-semibold text-slate-400">Operator: {{ $order->operator_name ?: 'Unknown' }} · Created {{ $fmtDate($order->created_at) }} · Read-only Pass 3</p>
                </div>

                <div class="flex flex-wrap gap-2 text-xs font-black">
                    <a href="{{ route('purchase-desk-v2.index') }}" class="rounded-full bg-indigo-600 px-3 py-2 text-white hover:bg-indigo-700">Queue</a>
                    <a href="{{ route('orders.show', $order->id) }}" class="rounded-full bg-slate-100 px-3 py-2 text-slate-700 hover:bg-slate-200">Order page</a>
                    @if (!empty($order->draft_order_id))
                        <a href="{{ route('draft-orders.show', $order->draft_order_id) }}" class="rounded-full bg-slate-100 px-3 py-2 text-slate-700 hover:bg-slate-200">Draft page</a>
                    @endif
                    <a href="{{ route('money-desk.orders.show', $order->id) }}" class="rounded-full bg-slate-100 px-3 py-2 text-slate-700 hover:bg-slate-200">Finance page</a>
                </div>
            </div>
        </section>

        <section class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-black uppercase tracking-wide text-slate-400">Order total</p><p class="mt-1 text-xl font-black text-slate-950">{{ $money($order->grand_total) }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-black uppercase tracking-wide text-slate-400">Due</p><p class="mt-1 text-xl font-black {{ $summary['balance_due'] > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $money($summary['balance_due']) }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-black uppercase tracking-wide text-slate-400">Item value</p><p class="mt-1 text-xl font-black text-slate-950">{{ $money($summary['items_cost']) }}</p></div>
            <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4 shadow-sm"><p class="text-xs font-black uppercase tracking-wide text-indigo-500">Remaining</p><p class="mt-1 text-xl font-black text-indigo-800">{{ $summary['remaining_to_buy_qty'] }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-black uppercase tracking-wide text-slate-400">Purchased</p><p class="mt-1 text-xl font-black text-slate-950">{{ $summary['purchased_qty'] }}</p></div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4 shadow-sm"><p class="text-xs font-black uppercase tracking-wide text-amber-600">Problems</p><p class="mt-1 text-xl font-black text-amber-800">{{ $summary['pre_purchase_problem_qty'] }}</p></div>
        </section>

        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('purchase-desk-v2.orders.show', $order->id) }}" x-data="{ timer: null }" class="grid gap-3 lg:grid-cols-[1fr_220px]">
                <div>
                    <label class="text-xs font-black uppercase tracking-wide text-slate-400">Search within this order</label>
                    <input
                        type="search"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        placeholder="Product code, description, URL..."
                        class="mt-1 w-full rounded-2xl border-slate-300 text-sm font-semibold"
                        @input="clearTimeout(timer); timer = setTimeout(() => $el.form.submit(), 450)"
                    >
                </div>
                <div>
                    <label class="text-xs font-black uppercase tracking-wide text-slate-400">View</label>
                    <select name="view" class="mt-1 w-full rounded-2xl border-slate-300 text-sm font-bold" @change="$el.form.submit()">
                        <option value="all" @selected($currentView === 'all')>All active items</option>
                        <option value="actionable" @selected($currentView === 'actionable')>Actionable only</option>
                        <option value="to_buy" @selected($currentView === 'to_buy')>To purchase</option>
                        <option value="problems" @selected($currentView === 'problems')>Problems</option>
                        <option value="purchased" @selected($currentView === 'purchased')>Purchased / history</option>
                    </select>
                </div>
            </form>
        </section>

        <section class="space-y-5">
            @forelse ($retailers as $retailer)
                <article class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                    <header class="bg-slate-950 p-4 text-white">
                        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-xl font-black">{{ $retailer['retailer_name'] }}</h2>
                                <p class="mt-1 text-xs font-bold text-slate-300">{{ $retailer['actionable_count'] }} actionable item{{ $retailer['actionable_count'] === 1 ? '' : 's' }} · Item value {{ $money($retailer['items_cost']) }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs font-black sm:grid-cols-5 xl:min-w-[560px]">
                                <div class="rounded-2xl bg-white/10 p-2"><p class="text-slate-300">Active</p><p class="text-lg">{{ $retailer['active_item_qty'] }}</p></div>
                                <div class="rounded-2xl bg-indigo-400/20 p-2"><p class="text-indigo-100">To buy</p><p class="text-lg">{{ $retailer['remaining_to_buy_qty'] }}</p></div>
                                <div class="rounded-2xl bg-white/10 p-2"><p class="text-slate-300">Purchased</p><p class="text-lg">{{ $retailer['purchased_qty'] }}</p></div>
                                <div class="rounded-2xl bg-white/10 p-2"><p class="text-slate-300">Arrived</p><p class="text-lg">{{ $retailer['arrived_qty'] }}</p></div>
                                <div class="rounded-2xl bg-amber-400/20 p-2"><p class="text-amber-100">Problems</p><p class="text-lg">{{ $retailer['pre_purchase_problem_qty'] }}</p></div>
                            </div>
                        </div>
                    </header>

                    <div class="divide-y divide-slate-100">
                        @foreach ($retailer['items'] as $item)
                            @php
                                $rootId = (int) $item->lineage_root_id;
                                $events = $purchaseEventsByRoot->get($rootId, collect());
                                $itemIssues = $issuesByRoot->get($rootId, collect());
                                $isActionable = (int) $item->remaining_to_buy_qty > 0 || (int) $item->active_pre_purchase_issue_qty > 0;
                                $lineValue = $item->line_subtotal ?: $item->line_total;
                            @endphp

                            <div class="p-4 sm:p-5 {{ $isActionable ? 'bg-white' : 'bg-slate-50/60' }}">
                                <div class="grid gap-4 xl:grid-cols-[1fr_340px]">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            @if ($item->remaining_to_buy_qty > 0)
                                                <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-black text-indigo-700 ring-1 ring-indigo-100">TO PURCHASE {{ $item->remaining_to_buy_qty }}</span>
                                            @endif
                                            @if ($item->active_pre_purchase_issue_qty > 0)
                                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-black text-amber-700 ring-1 ring-amber-100">PRE-PURCHASE PROBLEM {{ $item->active_pre_purchase_issue_qty }}</span>
                                            @endif
                                            @if ($item->requires_inspection)
                                                <span class="rounded-full bg-purple-50 px-2.5 py-1 text-[11px] font-black text-purple-700 ring-1 ring-purple-100">PURPLE</span>
                                            @endif
                                            @if (! $isActionable && $item->purchased_qty > 0)
                                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-black text-emerald-700 ring-1 ring-emerald-100">PURCHASED</span>
                                            @endif
                                        </div>

                                        <div class="mt-3 grid gap-3 lg:grid-cols-[170px_1fr]">
                                            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Product code</p>
                                                <p class="mt-1 break-words text-sm font-black text-slate-900">{{ $item->product_code ?: '—' }}</p>
                                                <p class="mt-3 text-[10px] font-black uppercase tracking-wide text-slate-400">Customer price</p>
                                                <p class="mt-1 text-sm font-black text-slate-900">{{ $money($item->unit_price) }}</p>
                                                <p class="mt-3 text-[10px] font-black uppercase tracking-wide text-slate-400">Line value</p>
                                                <p class="mt-1 text-sm font-black text-slate-900">{{ $money($lineValue) }}</p>
                                            </div>

                                            <div>
                                                <h3 class="text-base font-black leading-snug text-slate-950">{{ $item->item_name }}</h3>
                                                @if ($item->description && $item->description !== $item->item_name)
                                                    <p class="mt-1 text-sm font-semibold text-slate-500">{{ Str::limit($item->description, 220) }}</p>
                                                @endif
                                                @if ($item->marketplace_seller)
                                                    <p class="mt-2 text-xs font-bold text-slate-500">Marketplace seller: {{ $item->marketplace_seller }}</p>
                                                @endif
                                                @if ($item->product_url)
                                                    <a href="{{ $item->product_url }}" target="_blank" class="mt-2 inline-block rounded-full bg-slate-100 px-3 py-1.5 text-xs font-black text-indigo-700 hover:bg-indigo-50">Open product link ↗</a>
                                                @endif
                                                @if ($item->inspection_note)
                                                    <p class="mt-3 rounded-2xl bg-purple-50 p-3 text-xs font-semibold text-purple-800 ring-1 ring-purple-100">{{ $item->inspection_note }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <aside class="space-y-3">
                                        <div class="grid grid-cols-2 gap-2">
                                            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Ordered</p><p class="text-lg font-black text-slate-950">{{ $item->quantity }}</p></div>
                                            <div class="rounded-2xl bg-indigo-50 p-3 ring-1 ring-indigo-100"><p class="text-[10px] font-black uppercase tracking-wide text-indigo-500">Remaining</p><p class="text-lg font-black text-indigo-800">{{ $item->remaining_to_buy_qty }}</p></div>
                                            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Purchased</p><p class="text-lg font-black text-slate-950">{{ $item->purchased_qty }}</p></div>
                                            <div class="rounded-2xl bg-emerald-50 p-3 ring-1 ring-emerald-100"><p class="text-[10px] font-black uppercase tracking-wide text-emerald-600">Arrived</p><p class="text-lg font-black text-emerald-800">{{ $item->arrived_qty }}</p></div>
                                        </div>

                                        <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-3">
                                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Actions coming in Pass 4</p>
                                            <div class="mt-2 flex flex-wrap gap-2 text-xs font-black">
                                                <button type="button" disabled class="rounded-full bg-indigo-100 px-3 py-2 text-indigo-400">Record purchase</button>
                                                <button type="button" disabled class="rounded-full bg-amber-100 px-3 py-2 text-amber-500">Record problem</button>
                                                <button type="button" disabled class="rounded-full bg-purple-100 px-3 py-2 text-purple-500">Purple note</button>
                                            </div>
                                        </div>
                                    </aside>
                                </div>

                                @if ($events->count() || $itemIssues->count())
                                    <div class="mt-4 grid gap-3 lg:grid-cols-2">
                                        @if ($events->count())
                                            <details class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                                <summary class="cursor-pointer text-xs font-black uppercase tracking-wide text-slate-500">Purchase history · {{ $events->sum('qty') }} qty</summary>
                                                <div class="mt-3 space-y-2">
                                                    @foreach ($events as $event)
                                                        <div class="rounded-xl bg-white p-3 text-xs ring-1 ring-slate-100">
                                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                                <span class="font-black uppercase text-slate-700">{{ str_replace('_', ' ', $event->status) }} · Qty {{ $event->qty }}</span>
                                                                <span class="font-bold text-slate-400">{{ $fmtDate($event->ordered_at ?: $event->created_at) }}</span>
                                                            </div>
                                                            <p class="mt-1 font-semibold text-slate-600">Price: {{ $event->purchase_unit_price !== null ? $money($event->purchase_unit_price) : '—' }} · Line: {{ $event->purchase_line_total !== null ? $money($event->purchase_line_total) : '—' }}</p>
                                                            @if ($event->retailer_order_reference)
                                                                <p class="mt-1 font-semibold text-slate-600">Retailer ref: {{ $event->retailer_order_reference }}</p>
                                                            @endif
                                                            @if ($event->note)
                                                                <p class="mt-1 font-semibold text-slate-500">{{ $event->note }}</p>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </details>
                                        @endif

                                        @if ($itemIssues->count())
                                            <details open class="rounded-2xl bg-amber-50 p-3 ring-1 ring-amber-100">
                                                <summary class="cursor-pointer text-xs font-black uppercase tracking-wide text-amber-700">Pre-purchase problems · {{ $itemIssues->count() }}</summary>
                                                <div class="mt-3 space-y-2">
                                                    @foreach ($itemIssues as $issue)
                                                        <div class="rounded-xl bg-white p-3 text-xs ring-1 ring-amber-100">
                                                            <div class="flex flex-wrap items-center gap-2">
                                                                <span class="rounded-full bg-amber-100 px-2 py-1 font-black uppercase text-amber-800">{{ str_replace('_', ' ', $issue->issue_type) }}</span>
                                                                <span class="rounded-full bg-slate-100 px-2 py-1 font-black uppercase text-slate-700">{{ str_replace('_', ' ', $issue->status) }}</span>
                                                                <span class="font-bold text-slate-500">Qty {{ $issue->affected_qty ?: $issue->qty }}</span>
                                                            </div>
                                                            @if ($issue->notes)
                                                                <p class="mt-2 font-semibold text-slate-700">{{ $issue->notes }}</p>
                                                            @endif
                                                            <p class="mt-2 font-semibold text-slate-400">Created {{ $fmtDate($issue->created_at) }} by {{ $issue->created_by_name ?: 'Unknown' }}</p>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </details>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </article>
            @empty
                <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-white p-8 text-center text-sm font-bold text-slate-500">No active purchasable items found for this order/filter.</div>
            @endforelse
        </section>
    </div>
</x-app-layout>
