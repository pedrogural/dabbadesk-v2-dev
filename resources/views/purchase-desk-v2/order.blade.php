<x-app-layout>
    <style>[x-cloak] { display: none !important; }</style>
    @php
        $money = fn ($value) => '£' . number_format((float) $value, 2);
        $customer = $order->bill_to_company ?: ($order->bill_to_name ?: 'Unknown customer');
        $fmtDate = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d M Y') : '—';
        $currentView = $filters['view'] ?? 'all';
    @endphp

    <div class="mx-auto max-w-7xl space-y-5">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800">{{ $errors->first() }}</div>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <a href="{{ route('purchases.index') }}" class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600 hover:text-indigo-800">← Back to purchases</a>
                    <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">Purchases · Order #{{ $order->order_number }}</h1>
                    <p class="mt-1 text-sm font-medium text-slate-700">{{ $customer }}</p>
                    <p class="mt-1 text-xs text-slate-400">Created {{ $fmtDate($order->created_at) }} · Purchases workspace</p>
                </div>

                <div class="flex flex-wrap gap-2 text-xs font-semibold">
                    <a href="{{ route('purchases.index') }}" class="rounded-full bg-indigo-600 px-3 py-2 text-white hover:bg-indigo-700">Queue</a>
                    <a href="{{ route('orders.show', $order->id) }}" class="rounded-full bg-slate-100 px-3 py-2 text-slate-700 hover:bg-slate-200">Order page</a>
                    @if (!empty($order->draft_order_id))
                        <a href="{{ route('draft-orders.show', $order->draft_order_id) }}" class="rounded-full bg-slate-100 px-3 py-2 text-slate-700 hover:bg-slate-200">Draft page</a>
                    @endif
                    <a href="{{ route('money-desk.orders.show', $order->id) }}" class="rounded-full bg-slate-100 px-3 py-2 text-slate-700 hover:bg-slate-200">Finance page</a>
                </div>
            </div>
        </section>

        <section class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Order total</p><p class="mt-1 text-xl font-semibold text-slate-900">{{ $money($order->grand_total) }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Due</p><p class="mt-1 text-xl font-semibold {{ $summary['balance_due'] > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $money($summary['balance_due']) }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Item value</p><p class="mt-1 text-xl font-semibold text-slate-900">{{ $money($summary['items_cost']) }}</p></div>
            <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-indigo-500">Remaining</p><p class="mt-1 text-xl font-semibold text-indigo-800">{{ $summary['remaining_to_buy_qty'] }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Purchased</p><p class="mt-1 text-xl font-semibold text-slate-900">{{ $summary['purchased_qty'] }}</p></div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Problems</p><p class="mt-1 text-xl font-semibold text-amber-800">{{ $summary['pre_purchase_problem_qty'] }}</p></div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('purchases.orders.show', $order->id) }}" x-data="{ timer: null }" class="grid gap-3 lg:grid-cols-[1fr_220px]">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-400">Search within this order</label>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Product code, description, URL..." class="mt-1 w-full rounded-2xl border-slate-300 text-sm" @input="clearTimeout(timer); timer = setTimeout(() => $el.form.submit(), 450)">
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-400">View</label>
                    <select name="view" class="mt-1 w-full rounded-2xl border-slate-300 text-sm" @change="$el.form.submit()">
                        <option value="actionable" @selected($currentView === 'actionable')>Needs purchase now</option>
                        <option value="to_buy" @selected($currentView === 'to_buy')>Remaining to buy</option>
                        <option value="problems" @selected($currentView === 'problems')>Problems</option>
                        <option value="purchased" @selected($currentView === 'purchased')>Purchased / history</option>
                        <option value="all" @selected($currentView === 'all')>All active items</option>
                    </select>
                </div>
            </form>
        </section>

        <section class="space-y-6">
            @forelse ($retailers as $retailer)
                @php
                    $actionableRows = $retailer['items']->filter(function ($row) {
                        return max(0, (int) $row->remaining_to_buy_qty + (int) $row->active_pre_purchase_issue_qty) > 0;
                    })->values();
                    $selectableKeys = $actionableRows->map(fn ($row) => 'item_' . (int) $row->item_id)->values();
                    $defaultSupplierId = $retailer['retailer_id'];
                    $supplierModalKey = 'supplier_modal_' . ($retailer['retailer_id'] ?: 'unknown');
                    $purchasedAnchor = 'purchased-batches-' . ($retailer['retailer_id'] ?: 'unknown');
                    $hasPurchaseBatches = (int) ($retailer['purchase_batches_count'] ?? 0) > 0;
                @endphp

                <article
                    class="overflow-visible rounded-3xl border border-slate-200 bg-white shadow-sm"
                    x-data="{
                        selected: {},
                        supplierModal: false,
                        batchesOpen: false,
                        selectable: @js($selectableKeys),
                        selectedCount() { return Object.values(this.selected).filter(Boolean).length; },
                        selectAll() { this.selectable.forEach((key) => { this.selected[key] = true; }); },
                        clearSelection() { this.selected = {}; },
                        showPurchased(anchorId) {
                            this.batchesOpen = true;
                            this.$nextTick(() => {
                                const target = document.getElementById(anchorId);
                                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            });
                        }
                    }"
                >
                    <div class="border-b border-slate-200 bg-white p-5 sm:p-6">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                            <div>
                                <div class="flex items-center gap-2 text-sm text-slate-500">
                                    <a href="{{ route('purchases.index') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Purchases</a>
                                    <span>›</span>
                                    <span>{{ $retailer['retailer_name'] }}</span>
                                </div>
                                <h2 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">{{ $retailer['retailer_name'] }}</h2>
                                <p class="mt-1 text-sm text-slate-600">Review items to buy from this retailer and record your purchase below.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if ($hasPurchaseBatches)
                                    <button type="button" class="inline-flex items-center gap-2 rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 shadow-sm hover:bg-indigo-100" @click="showPurchased('{{ $purchasedAnchor }}')">
                                        <span>View purchased batches</span>
                                        <span class="rounded-full bg-white px-2 py-0.5 text-xs">{{ (int) ($retailer['purchase_batches_count'] ?? 0) }}</span>
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                                    </button>
                                @endif
                                <a href="{{ route('orders.show', $order->id) }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Order details</a>
                                <button type="button" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" disabled>Retailer details</button>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('purchases.basket.store', ['order' => $order->id]) }}">
                        @csrf

                        <div class="m-4 rounded-2xl border border-indigo-100 bg-indigo-50/40 p-4 sm:m-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-slate-950"><span x-text="selectedCount()">0</span> item line<span x-show="selectedCount() !== 1">s</span> selected</p>
                                    <p class="mt-1 text-sm font-semibold text-indigo-700">Select one or more item lines to add them to your purchase basket.</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="rounded-xl border border-indigo-100 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 shadow-sm hover:bg-indigo-50" @click="selectAll()">Select all</button>
                                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50" @click="clearSelection()">Clear selection</button>
                                </div>
                            </div>
                        </div>

                        <div class="mx-4 overflow-hidden rounded-2xl border border-slate-200 bg-white sm:mx-5">
                            <div class="hidden border-b border-slate-100 bg-slate-50/70 px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-500 lg:grid lg:grid-cols-[56px_1.55fr_120px_160px_160px_120px_110px] lg:gap-4">
                                <div>Buy</div>
                                <div>Item</div>
                                <div>Requested</div>
                                <div>Qty</div>
                                <div>Price</div>
                                <div>Delivery</div>
                                <div>Link</div>
                            </div>

                            <div class="divide-y divide-slate-100">
                                @foreach ($retailer['items'] as $item)
                                    @php
                                        $rootId = (int) $item->lineage_root_id;
                                        $events = $purchaseEventsByRoot->get($rootId, collect());
                                        $itemIssues = $issuesByRoot->get($rootId, collect());
                                        $maxPurchasableQty = max(0, (int) $item->remaining_to_buy_qty + (int) $item->active_pre_purchase_issue_qty);
                                        $isActionable = $maxPurchasableQty > 0;
                                        $isFullyPurchased = ! $isActionable && (int) $item->purchased_qty > 0;
                                        $lineValue = $item->line_subtotal ?: $item->line_total;
                                        $rowKey = 'item_' . (int) $item->item_id;
                                    @endphp

                                    <div
                                        class="px-4 py-4 transition sm:px-5"
                                        @class([
                                            'bg-white' => $isActionable,
                                            'bg-emerald-50/25' => $isFullyPurchased,
                                            'bg-slate-50/60' => ! $isActionable && ! $isFullyPurchased,
                                        ])
                                        :class="selected['{{ $rowKey }}'] ? 'bg-indigo-50/50 ring-1 ring-inset ring-indigo-200' : ''"
                                    >
                                        <div class="grid gap-4 lg:grid-cols-[56px_1.55fr_120px_160px_160px_120px_110px] lg:items-center">
                                            <div>
                                                @if ($isActionable)
                                                    <button
                                                        type="button"
                                                        class="grid h-9 w-9 place-items-center rounded-xl border text-sm transition"
                                                        :class="selected['{{ $rowKey }}'] ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm shadow-indigo-200' : 'border-slate-300 bg-white text-slate-400 hover:border-indigo-300 hover:text-indigo-600'"
                                                        @click="selected['{{ $rowKey }}'] = !selected['{{ $rowKey }}']"
                                                        title="Select this line to record purchase"
                                                    >
                                                        <span x-show="!selected['{{ $rowKey }}']">□</span>
                                                        <span x-show="selected['{{ $rowKey }}']" x-cloak>✓</span>
                                                    </button>
                                                    <input type="hidden" name="lines[{{ $item->item_id }}][selected]" value="1" :disabled="!selected['{{ $rowKey }}']">
                                                @elseif ($isFullyPurchased)
                                                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">✓</span>
                                                @else
                                                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-slate-400 ring-1 ring-slate-200">–</span>
                                                @endif
                                            </div>

                                            <div class="min-w-0">
                                                <h3 class="text-base font-semibold leading-snug text-slate-950">{{ $item->item_name }}</h3>
                                                <div class="mt-1 flex flex-wrap gap-x-2 gap-y-1 text-sm text-slate-500">
                                                    <span>{{ $item->product_code ?: 'No product code' }}</span>
                                                    <span>·</span>
                                                    <span>{{ $item->retailer_name }}</span>
                                                    @if ($item->marketplace_seller)
                                                        <span>· Seller: {{ $item->marketplace_seller }}</span>
                                                    @endif
                                                </div>

                                                @if ($item->active_pre_purchase_issue_qty > 0)
                                                    <span class="mt-3 inline-flex rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800">⚠ Record purchasing issue</span>
                                                @endif

                                                @if ($events->count())
                                                    <p class="mt-3 text-xs font-semibold text-emerald-700">Purchased lines are summarised in the Purchased batches section below.</p>
                                                @endif

                                                @if ($itemIssues->count())
                                                    <details class="mt-3 max-w-md rounded-xl border border-amber-200 bg-amber-50 px-3 py-2">
                                                        <summary class="cursor-pointer text-sm font-semibold text-amber-900">Open purchase issue · {{ $itemIssues->count() }}</summary>
                                                        <div class="mt-3 space-y-2 text-xs">
                                                            @foreach ($itemIssues as $issue)
                                                                <div class="rounded-xl bg-white p-3 text-amber-900 ring-1 ring-amber-100">
                                                                    <p class="font-semibold">{{ str_replace('_', ' ', $issue->issue_type) }} · {{ str_replace('_', ' ', $issue->status) }}</p>
                                                                    @if ($issue->notes)
                                                                        <p class="mt-1">{{ $issue->notes }}</p>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </details>
                                                @endif
                                            </div>

                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 lg:hidden">Requested</p>
                                                <p class="text-base font-semibold text-slate-950">{{ $item->quantity }}</p>
                                            </div>

                                            <div>
                                                <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Qty</label>
                                                @if ($isActionable)
                                                    <input type="number" name="lines[{{ $item->item_id }}][qty]" min="1" max="{{ $maxPurchasableQty }}" value="{{ $maxPurchasableQty }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm font-semibold disabled:bg-slate-50 disabled:text-slate-400" :disabled="!selected['{{ $rowKey }}']">
                                                    <p class="mt-1 text-xs text-slate-500">{{ $maxPurchasableQty }} left</p>
                                                @else
                                                    <p class="mt-1 text-sm font-semibold text-emerald-700">0 left</p>
                                                @endif
                                            </div>

                                            <div>
                                                <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Price</label>
                                                @if ($isActionable)
                                                    <input type="number" name="lines[{{ $item->item_id }}][purchase_unit_price]" step="0.01" min="0" value="{{ number_format((float) $item->unit_price, 2, '.', '') }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm font-semibold disabled:bg-slate-50 disabled:text-slate-400" :disabled="!selected['{{ $rowKey }}']">
                                                @else
                                                    <p class="mt-1 text-sm text-slate-400">—</p>
                                                @endif
                                            </div>

                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 lg:hidden">Delivery</p>
                                                <p class="text-sm text-slate-400">—</p>
                                            </div>

                                            <div>
                                                @if ($item->product_url)
                                                    <a href="{{ $item->product_url }}" target="_blank" class="grid h-10 w-10 place-items-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100 hover:bg-indigo-100">↗</a>
                                                @else
                                                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-slate-50 text-slate-300 ring-1 ring-slate-100">–</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="sticky bottom-4 z-20 mx-4 mt-4 rounded-3xl border border-indigo-300 bg-white shadow-xl shadow-indigo-100 sm:mx-5">
                            <div class="rounded-t-3xl bg-indigo-600 px-4 py-3 text-white sm:px-5">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-indigo-100">Record purchase</p>
                                        <p class="mt-1 text-sm font-semibold">Select item lines above, then save this retailer basket.</p>
                                    </div>
                                    <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold"><span x-text="selectedCount()">0</span> selected</span>
                                </div>
                            </div>

                            <div class="space-y-4 bg-indigo-50/45 p-4 sm:p-5">
                                <input type="hidden" name="retailer_id" value="{{ $retailer['retailer_id'] }}">
                                <div class="grid gap-3 xl:grid-cols-[minmax(250px,1.25fr)_minmax(260px,1.15fr)_minmax(185px,0.85fr)_minmax(185px,0.85fr)_190px] xl:items-start">
                                    <div class="min-w-0">
                                        <label class="block min-h-[1rem] text-xs font-semibold uppercase tracking-wide text-indigo-700">Purchased from / supplier</label>
                                        <div class="mt-2 flex gap-2">
                                            <select name="supplier_retailer_id" class="h-11 w-full rounded-xl border-indigo-200 bg-white text-sm font-semibold text-slate-700" required>
                                                @foreach ($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}" @selected((int) $supplier->id === (int) $defaultSupplierId)>{{ $supplier->name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="h-11 shrink-0 rounded-xl border border-indigo-200 bg-white px-4 text-sm font-semibold text-indigo-700 hover:bg-indigo-50" @click="supplierModal = true">+ Add</button>
                                        </div>
                                        <p class="mt-1 min-h-[1rem] text-xs text-indigo-700">Defaults to this retailer.</p>
                                    </div>

                                    <div class="min-w-0">
                                        <label class="block min-h-[1rem] text-xs font-semibold uppercase tracking-wide text-indigo-700">Retailer order reference *</label>
                                        <input type="text" name="retailer_order_reference" class="mt-2 h-11 w-full rounded-xl border-indigo-200 bg-white text-sm font-semibold" placeholder="e.g. 123-1234567-1234567" required>
                                        <p class="mt-1 min-h-[1rem] text-xs text-transparent">Required</p>
                                    </div>

                                    <div class="min-w-0">
                                        <label class="block min-h-[1rem] text-xs font-semibold uppercase tracking-wide text-indigo-700">ETA / expected UK hub</label>
                                        <input type="date" name="estimated_retailer_delivery_date" class="mt-2 h-11 w-full rounded-xl border-indigo-200 bg-white text-sm font-semibold">
                                        <p class="mt-1 min-h-[1rem] text-xs text-transparent">Optional</p>
                                    </div>

                                    <div class="min-w-0">
                                        <label class="block min-h-[1rem] text-xs font-semibold uppercase tracking-wide text-indigo-700">Ordered date</label>
                                        <input type="date" name="ordered_at" value="{{ now()->toDateString() }}" class="mt-2 h-11 w-full rounded-xl border-indigo-200 bg-white text-sm font-semibold">
                                        <p class="mt-1 min-h-[1rem] text-xs text-transparent">Today</p>
                                    </div>

                                    <div class="min-w-0">
                                        <span class="block min-h-[1rem] text-xs font-semibold uppercase tracking-wide text-transparent">Action</span>
                                        <button type="submit" class="mt-2 h-11 w-full rounded-2xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm shadow-indigo-200 hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-indigo-300" :disabled="selectedCount() === 0">Record Purchase</button>
                                        <p class="mt-1 min-h-[1rem] text-xs text-transparent">Submit</p>
                                    </div>
                                </div>

                                <textarea name="note" rows="2" class="block w-full rounded-xl border-indigo-200 bg-white text-sm" placeholder="Optional internal note for this purchase batch"></textarea>
                            </div>
                        </div>
                    </form>

                    @php
                        $purchaseBatches = $retailer['purchase_batches'] ?? collect();
                    @endphp

                    <section id="{{ $purchasedAnchor }}" class="scroll-mt-6 m-4 mt-5 rounded-2xl border border-slate-200 bg-slate-50/70 sm:m-5 sm:mt-5">
                        <button type="button" class="flex w-full flex-col gap-3 rounded-2xl px-4 py-4 text-left hover:bg-white/70 sm:flex-row sm:items-center sm:justify-between sm:px-5" @click="batchesOpen = !batchesOpen">
                            <div class="flex gap-3">
                                <span class="mt-0.5 inline-grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path></svg>
                                </span>
                                <div>
                                    <p class="text-base font-semibold text-slate-950">Purchased batches · {{ $purchaseBatches->count() }}</p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ (int) ($retailer['purchase_batches_count'] ?? 0) }} batch{{ (int) ($retailer['purchase_batches_count'] ?? 0) === 1 ? '' : 'es' }} ·
                                        {{ (int) ($retailer['purchase_batches_line_count'] ?? 0) }} line{{ (int) ($retailer['purchase_batches_line_count'] ?? 0) === 1 ? '' : 's' }} purchased
                                        @if (($retailer['purchase_batches_total'] ?? 0) > 0)
                                            · Total {{ $money($retailer['purchase_batches_total']) }}.
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm">
                                <span x-text="batchesOpen ? 'Hide purchased' : 'Show purchased'"></span>
                                <svg class="h-4 w-4 transition-transform" :class="batchesOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                            </span>
                        </button>

                        <div x-show="batchesOpen" x-cloak class="border-t border-slate-200 p-4 sm:p-5">
                            @if ($purchaseBatches->isEmpty())
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-sm font-medium text-slate-500">No purchases have been recorded for this retailer yet.</div>
                            @else
                                <div class="space-y-3">
                                    @foreach ($purchaseBatches as $batch)
                                        @php
                                            $batchDate = $batch['date'] ? \Carbon\Carbon::parse($batch['date']) : null;
                                            $batchTime = $batch['time_at'] ? \Carbon\Carbon::parse($batch['time_at']) : null;
                                            $batchLines = $batch['lines'] ?? collect();
                                        @endphp
                                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" x-data="{ open: false, editing: false, undoing: false, confirmBatchUndo: false, batchUndoReason: '', batchUndoError: '', checkBatchUndo() { if (this.batchUndoReason.trim().length < 2) { this.batchUndoError = 'Please enter a short reason before undoing this batch.'; return; } this.batchUndoError = ''; this.confirmBatchUndo = true; } }">
                                            <button type="button" class="w-full px-4 py-4 text-left hover:bg-slate-50 sm:px-5" @click="open = !open" :aria-expanded="open.toString()">
                                                <div class="grid gap-4 lg:grid-cols-[210px_1fr_220px_140px_28px] lg:items-center">
                                                    <div class="flex items-center gap-3">
                                                        <span class="inline-grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
                                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path></svg>
                                                        </span>
                                                        <div>
                                                            <p class="text-sm font-semibold text-slate-950">{{ $batchDate ? $batchDate->format('d M Y') : 'Date unknown' }}</p>
                                                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $batchTime ? $batchTime->format('H:i') : 'Time not recorded' }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="flex min-w-0 items-center gap-3">
                                                        <span class="inline-grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
                                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9h18l-1 11H4L3 9Z"></path><path d="M5 9l2-5h10l2 5"></path><path d="M9 13v3M15 13v3"></path></svg>
                                                        </span>
                                                        <div class="min-w-0">
                                                            <p class="truncate text-sm font-semibold text-slate-900">{{ $batch['supplier_name'] }}</p>
                                                            <p class="mt-1 truncate text-sm text-slate-500">Ref: {{ $batch['retailer_order_reference'] ?: 'No reference recorded' }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <span class="inline-grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
                                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 8-9-5-9 5 9 5 9-5Z"></path><path d="M3 8v8l9 5 9-5V8"></path><path d="M12 13v8"></path></svg>
                                                        </span>
                                                        <div>
                                                            <p class="text-sm font-semibold text-slate-900">{{ $batch['line_count'] }} line{{ $batch['line_count'] === 1 ? '' : 's' }} · {{ $batch['qty'] }} unit{{ $batch['qty'] === 1 ? '' : 's' }}</p>
                                                            <p class="mt-1 text-xs font-semibold text-slate-500">ETA {{ $batch['eta'] ? $fmtDate($batch['eta']) : 'not set' }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="text-left lg:text-right">
                                                        <p class="text-sm font-semibold text-slate-950">{{ $money($batch['total']) }}</p>
                                                        <p class="mt-1 text-xs font-semibold text-indigo-600" x-text="open ? 'Hide details' : 'Open details'"></p>
                                                    </div>
                                                    <div class="flex justify-start lg:justify-end">
                                                        <svg class="h-5 w-5 text-indigo-600 transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                                                    </div>
                                                </div>
                                            </button>

                                            <div x-show="open" x-cloak class="border-t border-slate-100 bg-slate-50/60 p-4 sm:p-5">
                                                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                    <div>
                                                        <p class="text-sm font-semibold text-slate-900">Purchase batch details</p>
                                                        <p class="mt-1 text-xs font-medium text-slate-500">Edit supplier, reference, ETA, ordered date and notes. You can also undo one line or the whole batch if a purchase was recorded by mistake.</p>
                                                    </div>
                                                    <div class="flex flex-wrap gap-2">
                                                        <button type="button" class="inline-flex items-center justify-center rounded-xl border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 shadow-sm hover:bg-indigo-50" @click.stop="editing = !editing; if (editing) undoing = false" x-text="editing ? 'Cancel edit' : 'Edit batch'"></button>
                                                        <button type="button" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-700 shadow-sm hover:bg-rose-50" @click.stop="undoing = !undoing; if (undoing) editing = false" x-text="undoing ? 'Cancel undo' : 'Undo batch'"></button>
                                                    </div>
                                                </div>

                                                <form x-show="editing" x-cloak method="POST" action="{{ route('purchases.batches.update', $order->id) }}" class="mb-4 rounded-2xl border border-indigo-100 bg-white p-4 shadow-sm">
                                                    @csrf
                                                    @method('PATCH')
                                                    @foreach (($batch['purchase_ids'] ?? collect()) as $purchaseId)
                                                        <input type="hidden" name="purchase_ids[]" value="{{ $purchaseId }}">
                                                    @endforeach

                                                    <div class="grid gap-3 lg:grid-cols-[minmax(190px,1fr)_minmax(220px,1.1fr)_170px_170px] lg:items-start">
                                                        <div>
                                                            <label class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Purchased from / supplier</label>
                                                            <select name="supplier_retailer_id" required class="mt-1 h-11 w-full rounded-xl border-indigo-200 bg-white text-sm font-semibold text-slate-900">
                                                                @foreach ($suppliers as $supplier)
                                                                    <option value="{{ $supplier->id }}" @selected((int) $supplier->id === (int) ($batch['supplier_retailer_id'] ?? 0))>{{ $supplier->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div>
                                                            <label class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Retailer order reference</label>
                                                            <input type="text" name="retailer_order_reference" value="{{ $batch['retailer_order_reference'] }}" maxlength="255" class="mt-1 h-11 w-full rounded-xl border-indigo-200 bg-white text-sm font-semibold text-slate-900" placeholder="e.g. 123-1234567-1234567">
                                                        </div>

                                                        <div>
                                                            <label class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">ETA / expected UK hub</label>
                                                            <input type="date" name="estimated_retailer_delivery_date" value="{{ $batch['eta'] ? \Carbon\Carbon::parse($batch['eta'])->toDateString() : '' }}" class="mt-1 h-11 w-full rounded-xl border-indigo-200 bg-white text-sm font-semibold text-slate-900">
                                                        </div>

                                                        <div>
                                                            <label class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Ordered date</label>
                                                            <input type="date" name="ordered_at" value="{{ $batchDate ? $batchDate->toDateString() : '' }}" class="mt-1 h-11 w-full rounded-xl border-indigo-200 bg-white text-sm font-semibold text-slate-900">
                                                        </div>
                                                    </div>

                                                    <div class="mt-3 grid gap-3 lg:grid-cols-[1fr_180px] lg:items-end">
                                                        <div>
                                                            <label class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Internal note</label>
                                                            <textarea name="note" rows="2" maxlength="2000" class="mt-1 block w-full rounded-xl border-indigo-200 bg-white text-sm text-slate-900" placeholder="Optional internal note for this purchase batch">{{ $batch['note'] }}</textarea>
                                                        </div>
                                                        <button type="submit" class="h-11 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm shadow-indigo-200 hover:bg-indigo-700">Save batch</button>
                                                    </div>
                                                </form>

                                                <form x-ref="batchUndoForm" x-show="undoing" x-cloak method="POST" action="{{ route('purchases.batches.undo', $order->id) }}" class="mb-4 rounded-2xl border border-rose-100 bg-rose-50/60 p-4 shadow-sm">
                                                    @csrf
                                                    @foreach (($batch['purchase_ids'] ?? collect()) as $purchaseId)
                                                        <input type="hidden" name="purchase_ids[]" value="{{ $purchaseId }}">
                                                    @endforeach
                                                    <label class="text-[11px] font-semibold uppercase tracking-wide text-rose-700">Reason for undoing this batch</label>
                                                    <div class="mt-1 grid gap-3 lg:grid-cols-[1fr_180px] lg:items-center">
                                                        <input type="text" name="reason" x-model="batchUndoReason" maxlength="255" class="h-11 w-full rounded-xl border-rose-200 bg-white text-sm text-slate-900" placeholder="e.g. Recorded on the wrong retailer order" @input="batchUndoError = ''">
                                                        <button type="button" class="h-11 rounded-xl bg-rose-600 px-5 text-sm font-semibold text-white shadow-sm shadow-rose-200 hover:bg-rose-700" @click="checkBatchUndo()">Undo all lines</button>
                                                    </div>
                                                    <p x-show="batchUndoError" x-cloak class="mt-2 rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700" x-text="batchUndoError"></p>
                                                    <p class="mt-2 text-xs font-medium text-rose-700">This does not delete history. It creates reversal events and removes these lines from the active purchased totals.</p>
                                                </form>

                                                <div x-show="confirmBatchUndo" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-slate-900/40 p-4">
                                                    <div class="w-full max-w-md rounded-3xl bg-white p-5 shadow-2xl ring-1 ring-rose-100" @click.outside="confirmBatchUndo = false">
                                                        <div class="flex items-start gap-3">
                                                            <span class="inline-grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-rose-50 text-rose-600 ring-1 ring-rose-100">
                                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="m19 6-1 14H6L5 6"></path><path d="M10 11v5"></path><path d="M14 11v5"></path></svg>
                                                            </span>
                                                            <div>
                                                                <h3 class="text-base font-semibold text-slate-950">Undo this whole purchase batch?</h3>
                                                                <p class="mt-1 text-sm text-slate-600">All active lines in this batch will return to the buying list. The original purchase records will stay in history with reversal events.</p>
                                                            </div>
                                                        </div>
                                                        <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                                            <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="confirmBatchUndo = false">Keep batch</button>
                                                            <button type="button" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-rose-200 hover:bg-rose-700" @click="$refs.batchUndoForm.requestSubmit()">Yes, undo all lines</button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                                                    <div class="hidden border-b border-slate-100 bg-white px-4 py-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400 md:grid md:grid-cols-[1fr_90px_110px_110px_80px_210px] md:gap-3">
                                                        <div>Item</div>
                                                        <div>Qty</div>
                                                        <div>Unit price</div>
                                                        <div>Line total</div>
                                                        <div>Link</div>
                                                        <div>Undo</div>
                                                    </div>
                                                    <div class="divide-y divide-slate-100">
                                                        @foreach ($batchLines as $line)
                                                            <div class="grid gap-3 px-4 py-3 text-sm md:grid-cols-[1fr_90px_110px_110px_80px_210px] md:items-center">
                                                                <div class="min-w-0">
                                                                    <p class="font-semibold text-slate-900">{{ $line->item_name ?: 'Purchased item' }}</p>
                                                                    <p class="mt-1 truncate text-xs text-slate-500">{{ $line->product_code ?: 'No product code' }}</p>
                                                                </div>
                                                                <div class="font-semibold text-slate-700">Qty {{ $line->qty }}</div>
                                                                <div class="text-slate-600">{{ $line->purchase_unit_price !== null ? $money($line->purchase_unit_price) : '—' }}</div>
                                                                <div class="font-semibold text-slate-900">{{ $line->purchase_line_total !== null ? $money($line->purchase_line_total) : '—' }}</div>
                                                                <div>
                                                                    @if ($line->product_url)
                                                                        <a href="{{ $line->product_url }}" target="_blank" class="inline-grid h-10 w-10 place-items-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100 hover:bg-indigo-100">↗</a>
                                                                    @else
                                                                        <span class="inline-grid h-10 w-10 place-items-center rounded-xl bg-slate-50 text-slate-300 ring-1 ring-slate-100">–</span>
                                                                    @endif
                                                                </div>
                                                                <div>
                                                                    <div x-data="{ confirmLineUndo: false, lineUndoReason: '', lineUndoError: '', checkLineUndo() { if (this.lineUndoReason.trim().length < 2) { this.lineUndoError = 'Please enter a reason.'; return; } this.lineUndoError = ''; this.confirmLineUndo = true; } }">
                                                                        <form x-ref="lineUndoForm" method="POST" action="{{ route('purchases.lines.undo', ['order' => $order->id, 'purchase' => $line->id]) }}" class="flex gap-2">
                                                                            @csrf
                                                                            <input type="text" name="reason" x-model="lineUndoReason" maxlength="255" class="h-10 min-w-0 flex-1 rounded-xl border-slate-200 bg-white text-xs text-slate-900" placeholder="Reason" @input="lineUndoError = ''">
                                                                            <button type="button" class="h-10 rounded-xl border border-rose-200 bg-rose-50 px-3 text-xs font-semibold text-rose-700 hover:bg-rose-100" @click="checkLineUndo()">Undo</button>
                                                                        </form>
                                                                        <p x-show="lineUndoError" x-cloak class="mt-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700" x-text="lineUndoError"></p>

                                                                        <div x-show="confirmLineUndo" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-slate-900/40 p-4">
                                                                            <div class="w-full max-w-md rounded-3xl bg-white p-5 shadow-2xl ring-1 ring-rose-100" @click.outside="confirmLineUndo = false">
                                                                                <div class="flex items-start gap-3">
                                                                                    <span class="inline-grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-rose-50 text-rose-600 ring-1 ring-rose-100">
                                                                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="m19 6-1 14H6L5 6"></path><path d="M10 11v5"></path><path d="M14 11v5"></path></svg>
                                                                                    </span>
                                                                                    <div>
                                                                                        <h3 class="text-base font-semibold text-slate-950">Undo this purchase line?</h3>
                                                                                        <p class="mt-1 text-sm text-slate-600">This line will return to the buying list. The original purchase record will stay in history with a reversal event.</p>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                                                                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="confirmLineUndo = false">Keep line</button>
                                                                                    <button type="button" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-rose-200 hover:bg-rose-700" @click="$refs.lineUndoForm.requestSubmit()">Yes, undo line</button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <p class="mt-4 flex items-start gap-2 text-sm font-medium text-indigo-700">
                                    <span class="mt-0.5 inline-grid h-5 w-5 shrink-0 place-items-center rounded-full border border-indigo-200 bg-white text-xs font-bold">i</span>
                                    <span>Showing completed purchase batches only. Outstanding items remain in the list above.</span>
                                </p>
                            @endif
                        </div>
                    </section>

                    <div x-show="supplierModal" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-slate-900/40 p-4">
                        <div class="w-full max-w-lg rounded-3xl bg-white p-5 shadow-2xl" @click.outside="supplierModal = false">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-950">Add supplier</h3>
                                    <p class="mt-1 text-sm text-slate-500">Creates an active retailer/supplier, then returns you to this purchase screen.</p>
                                </div>
                                <button type="button" class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-600" @click="supplierModal = false">Close</button>
                            </div>

                            <form method="POST" action="{{ route('purchases.suppliers.store', ['order' => $order->id]) }}" class="mt-5 space-y-4">
                                @csrf
                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Supplier / retailer name</label>
                                    <input type="text" name="name" class="mt-1 w-full rounded-xl border-slate-300 text-sm font-semibold" placeholder="e.g. The Range" minlength="2" maxlength="191" required>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Website / base URL</label>
                                    <input type="text" name="base_url" class="mt-1 w-full rounded-xl border-slate-300 text-sm" placeholder="example.co.uk" minlength="3" maxlength="2048" required>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">Use the main website. DabbaDesk will add https://, strip www/path/query text, and prevent duplicate retailer hosts.</p>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button type="button" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700" @click="supplierModal = false">Cancel</button>
                                    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save supplier</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm font-medium text-slate-500">No active purchasable items found for this order/filter.</div>
            @endforelse
        </section>
    </div>
</x-app-layout>
