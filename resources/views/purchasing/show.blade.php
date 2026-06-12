<x-app-layout>
    <x-slot name="header">Purchasing Workspace</x-slot>

    @php
        $money = fn ($amount) => '£' . number_format((float) $amount, 2);
        $orderNumber = $order->order_number ?? $order->id;
        $customer = trim((string) ($order->bill_to_company ?: $order->bill_to_name ?: 'Unknown customer'));
        $paymentStatus = $queueOrder['payment_status'] ?? 'unpaid';
        $paymentLabel = match ($paymentStatus) {'paid' => 'Paid', 'part_paid' => 'Part paid', default => 'Unpaid'};
        $paymentClass = match ($paymentStatus) {'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'part_paid' => 'bg-amber-50 text-amber-700 ring-amber-200', default => 'bg-rose-50 text-rose-700 ring-rose-200'};
        $requestedQty = (int) ($queueOrder['requested_qty'] ?? $items->sum('quantity'));
        $purchasedQty = (int) ($queueOrder['purchased_qty'] ?? $items->sum('purchased_qty'));
        $remainingQty = (int) ($queueOrder['remaining_to_buy_qty'] ?? $items->sum('remaining_to_buy_qty'));
        $awaitingQty = (int) ($queueOrder['awaiting_arrival_qty'] ?? $items->sum('awaiting_arrival_qty'));
        $problemQty = (int) ($queueOrder['problem_qty'] ?? $items->sum('problem_qty'));
        $inspectionQty = (int) ($queueOrder['inspection_count'] ?? $items->filter(fn ($i) => (int)($i->requires_inspection ?? 0) === 1)->count());
    @endphp

    <div class="mx-auto max-w-7xl space-y-5">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-800">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <a href="{{ route('purchasing.index') }}" class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600 hover:text-indigo-800">← Back to Purchasing Desk</a>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-black tracking-tight text-slate-950">Purchasing Workspace</h1>
                        <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $paymentClass }}">{{ $paymentLabel }}</span>
                        @if ($inspectionQty > 0)
                            <span class="rounded-full bg-purple-50 px-3 py-1.5 text-xs font-black text-purple-700 ring-1 ring-purple-200">{{ $inspectionQty }} package check</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm font-bold text-slate-700">Purchase items for Order #{{ $orderNumber }} · {{ $customer }}</p>
                    @if ($order->bill_to_email)
                        <p class="mt-1 text-xs font-semibold text-slate-400">{{ $order->bill_to_email }}</p>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-6 xl:min-w-[820px]">
                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Order total</p><p class="mt-1 text-lg font-black text-slate-950">{{ $money($order->grand_total ?? 0) }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Requested</p><p class="mt-1 text-lg font-black text-slate-950">{{ $requestedQty }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Purchased</p><p class="mt-1 text-lg font-black text-slate-950">{{ $purchasedQty }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Awaiting Purchase</p><p class="mt-1 text-lg font-black text-slate-950">{{ $remainingQty }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Awaiting Arrival</p><p class="mt-1 text-lg font-black text-slate-950">{{ $awaitingQty }}</p></div>
                    <div class="rounded-2xl bg-purple-50 p-3 ring-1 ring-purple-100"><p class="text-[10px] font-black uppercase tracking-wide text-purple-500">Package Check</p><p class="mt-1 text-lg font-black text-purple-800">{{ $inspectionQty }}</p></div>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4 text-xs font-black">
                <span class="rounded-full bg-slate-50 px-3 py-1.5 text-slate-700 ring-1 ring-slate-200">Retailer cards: {{ $retailers->count() }}</span>
                <span class="rounded-full bg-slate-50 px-3 py-1.5 text-slate-700 ring-1 ring-slate-200">Problem qty: {{ $problemQty }}</span>
                <a href="{{ route('orders.show', $order->id) }}" class="rounded-full bg-indigo-50 px-3 py-1.5 text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-100">View full order ↗</a>
            </div>
        </section>

        <section class="space-y-4">
            @forelse ($retailers as $retailer)
                @php
                    $retailerItems = collect($retailer['items']);
                    $remainingItems = $retailerItems->filter(fn ($item) => (int) $item->remaining_to_buy_qty > 0)->values();
                    $waitingQty = (int) $remainingItems->sum('remaining_to_buy_qty');
                    $purchasedQtyForRetailer = (int) $retailerItems->sum('purchased_qty');
                    $awaitingQtyForRetailer = (int) $retailerItems->sum('awaiting_arrival_qty');
                    $problemQtyForRetailer = (int) $retailerItems->sum('problem_qty');
                    $inspectionForRetailer = $retailerItems->filter(fn ($item) => (int)($item->requires_inspection ?? 0) === 1)->count();
                    $waitingValue = $remainingItems->sum(fn ($item) => ((float) $item->unit_price) * ((int) $item->remaining_to_buy_qty));
                    $cardRing = $inspectionForRetailer > 0 ? 'border-purple-200' : ($problemQtyForRetailer > 0 ? 'border-rose-200' : ($waitingQty > 0 ? 'border-indigo-200' : 'border-emerald-200'));
                    $statusLabel = $problemQtyForRetailer > 0 ? 'Needs attention' : ($waitingQty > 0 ? 'Awaiting Purchase' : 'Purchased');
                    $statusClass = $problemQtyForRetailer > 0 ? 'bg-rose-50 text-rose-700 ring-rose-200' : ($waitingQty > 0 ? 'bg-indigo-50 text-indigo-700 ring-indigo-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200');
                    $purchaseFormId = 'purchase-retailer-' . ($retailer['retailer_id'] ?? 'unknown') . '-' . $loop->index;
                @endphp

                <article class="overflow-visible rounded-[1.75rem] border {{ $cardRing }} bg-white shadow-sm" data-retailer-card>
                    <div class="border-b border-slate-100 p-5 sm:p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-2xl font-black text-slate-950">{{ $retailer['retailer_name'] }}</h2>
                                    <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $statusClass }}">{{ $statusLabel }}</span>
                                    @if ($inspectionForRetailer > 0)
                                        <span class="rounded-full bg-purple-50 px-3 py-1.5 text-xs font-black text-purple-700 ring-1 ring-purple-200">Package check</span>
                                    @endif
                                </div>
                                <p class="mt-2 text-sm font-bold text-slate-500">{{ $remainingItems->count() }} item line{{ $remainingItems->count() === 1 ? '' : 's' }} waiting · {{ $waitingQty }} qty to buy</p>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-5 lg:min-w-[680px]">
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Waiting value</p><p class="mt-1 font-black text-slate-950">{{ $money($waitingValue) }}</p></div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Purchased qty</p><p class="mt-1 font-black text-slate-950">{{ $purchasedQtyForRetailer }}</p></div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Awaiting qty</p><p class="mt-1 font-black text-slate-950">{{ $awaitingQtyForRetailer }}</p></div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Problems</p><p class="mt-1 font-black text-slate-950">{{ $problemQtyForRetailer }}</p></div>
                                <div class="rounded-2xl bg-purple-50 p-3 ring-1 ring-purple-100"><p class="text-[10px] font-black uppercase tracking-wide text-purple-500">Package Check</p><p class="mt-1 font-black text-purple-800">{{ $inspectionForRetailer }}</p></div>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6">
                        <input form="{{ $purchaseFormId }}" type="hidden" name="order_id" value="{{ $order->id }}">

                        @if ($remainingItems->isNotEmpty())
                            <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-black text-indigo-950"><span data-selected-lines>0</span> selected item lines</p>
                                    <p class="mt-1 text-xs font-bold text-indigo-700">Nothing is selected by default. Tick only the lines you are buying now.</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" data-select-all class="rounded-xl bg-white px-3 py-2 text-xs font-black text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-50">Select all</button>
                                    <button type="button" data-select-none class="rounded-xl bg-white px-3 py-2 text-xs font-black text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">Clear selection</button>
                                </div>
                            </div>
                        @endif

                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="hidden grid-cols-[56px_1fr_90px_150px_160px_96px] gap-3 bg-slate-50 px-4 py-3 text-[10px] font-black uppercase tracking-wide text-slate-400 md:grid">
                                <div>Buy</div><div>Item</div><div>Requested</div><div>Qty buying now</div><div>Actual purchase price</div><div>Link</div>
                            </div>

                            <div class="divide-y divide-slate-100">
                                @foreach ($retailerItems as $item)
                                    @php
                                        $remaining = (int) $item->remaining_to_buy_qty;
                                        $canBuy = $remaining > 0;
                                        $isPurple = (int)($item->requires_inspection ?? 0) === 1;
                                        $rowClass = $isPurple ? 'bg-purple-50/80 ring-1 ring-inset ring-purple-100' : ($canBuy ? 'bg-white' : 'bg-slate-50/70');
                                        $history = collect($purchasesByRoot[$item->lineage_root_id] ?? []);
                                    @endphp

                                    <div class="grid gap-3 px-4 py-4 md:grid-cols-[56px_1fr_90px_150px_160px_96px] md:items-start {{ $rowClass }}">
                                        <div>
                                            @if ($canBuy)
                                                <label class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 ring-1 ring-slate-200">
                                                    <input form="{{ $purchaseFormId }}" type="checkbox" name="order_item_ids[]" value="{{ $item->item_id }}" data-line-checkbox class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                    <span class="text-xs font-black text-slate-700 md:hidden">Buy</span>
                                                </label>
                                            @else
                                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-sm font-black text-emerald-700 ring-1 ring-emerald-100">✓</span>
                                            @endif
                                        </div>

                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-black leading-5 text-slate-950">{{ $item->item_name }}</p>
                                                @if ($isPurple)
                                                    <span class="rounded-full bg-purple-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-purple-800">Purple · package check</span>
                                                @endif
                                            </div>
                                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $item->product_code ?: 'No product code' }} · {{ $item->retailer_name }}</p>
                                            @if ($isPurple && $item->inspection_note)
                                                <p class="mt-2 rounded-xl bg-purple-100 px-3 py-2 text-xs font-bold text-purple-900">{{ $item->inspection_note }}</p>
                                            @endif

                                            <div class="mt-3 rounded-xl border border-slate-200 bg-white p-3">
                                                <form method="POST" action="{{ route('purchasing.items.inspection.update', $item->item_id) }}" class="space-y-2">
                                                    @csrf
                                                    <label class="inline-flex items-center gap-2 text-xs font-black text-purple-800">
                                                        <input type="checkbox" name="requires_inspection" value="1" class="rounded border-purple-300 text-purple-600 focus:ring-purple-500" {{ $isPurple ? 'checked' : '' }}>
                                                        Purple / requires package check
                                                    </label>
                                                    <div class="flex flex-col gap-2 sm:flex-row">
                                                        <input name="inspection_note" value="{{ $item->inspection_note }}" maxlength="2000" placeholder="Reason, e.g. check invoice inside parcel" class="h-10 flex-1 rounded-xl border-purple-200 bg-purple-50/50 px-3 text-xs font-bold text-purple-950 placeholder:text-purple-300 focus:border-purple-400 focus:ring-purple-200">
                                                        <button class="rounded-xl bg-purple-700 px-3 py-2 text-xs font-black text-white hover:bg-purple-800">Save flag</button>
                                                    </div>
                                                </form>
                                            </div>

                                            @if ($history->isNotEmpty())
                                                <details class="mt-3 rounded-xl border border-slate-200 bg-white p-3">
                                                    <summary class="cursor-pointer text-xs font-black text-slate-700">Purchase history · {{ $history->count() }}</summary>
                                                    <div class="mt-3 space-y-2">
                                                        @foreach ($history as $purchase)
                                                            @php
                                                                $activeArrivalQty = (int) collect($arrivals)->where('order_item_purchase_id', $purchase->id)->sum('qty');
                                                                $wasUndone = ! empty($purchase->cancelled_at);
                                                            @endphp
                                                            <div class="rounded-xl border {{ $wasUndone ? 'border-slate-200 bg-slate-50' : 'border-indigo-100 bg-indigo-50/60' }} p-3 text-xs">
                                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                                    <p class="font-black text-slate-900">Qty {{ (int) $purchase->qty }} · {{ $money($purchase->purchase_unit_price ?? 0) }} · Ref {{ $purchase->retailer_order_reference ?: '—' }}</p>
                                                                    <span class="font-black {{ $wasUndone ? 'text-slate-400' : 'text-indigo-700' }}">{{ $wasUndone ? 'Undone' : 'Active' }}</span>
                                                                </div>
                                                                <p class="mt-1 font-semibold text-slate-500">ETA {{ $purchase->expected_uk_hub_at ? \Carbon\Carbon::parse($purchase->expected_uk_hub_at)->format('d M Y') : '—' }} · Arrived qty {{ $activeArrivalQty }}</p>
                                                                @if (! $wasUndone && $activeArrivalQty === 0)
                                                                    <form method="POST" action="{{ route('purchasing.purchases.undo', $purchase->id) }}" class="mt-2 flex flex-col gap-2 sm:flex-row" onsubmit="return confirm('Undo this purchase and return the quantity to Awaiting Purchase?')">
                                                                        @csrf
                                                                        <input name="reason" required placeholder="Undo reason" class="h-9 flex-1 rounded-xl border-slate-200 bg-white px-3 text-xs font-bold focus:border-rose-300 focus:ring-rose-200">
                                                                        <button class="rounded-xl bg-rose-600 px-3 py-2 text-xs font-black text-white hover:bg-rose-700">Undo purchase</button>
                                                                    </form>
                                                                @elseif (! $wasUndone)
                                                                    <p class="mt-2 rounded-lg bg-white px-2 py-1 font-bold text-slate-500 ring-1 ring-slate-200">Cannot undo while arrival exists.</p>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </details>
                                            @endif
                                        </div>

                                        <div><span class="text-xs font-black uppercase tracking-wide text-slate-400 md:hidden">Requested</span><p class="font-black text-slate-900">{{ (int) $item->quantity }}</p></div>

                                        <div>
                                            <label class="mb-1 flex items-center gap-1 text-[10px] font-black uppercase tracking-wide text-slate-500">Editable qty <span class="rounded bg-indigo-50 px-1.5 py-0.5 text-[9px] text-indigo-700 ring-1 ring-indigo-100">EDIT</span></label>
                                            @if ($canBuy)
                                                <input form="{{ $purchaseFormId }}" name="qty[{{ $item->item_id }}]" type="number" min="0" max="{{ $remaining }}" value="{{ $remaining }}" data-line-qty class="h-12 w-full rounded-xl border-2 border-indigo-200 bg-indigo-50/60 px-3 text-sm font-black text-slate-950 shadow-inner focus:border-indigo-500 focus:bg-white focus:ring-indigo-200">
                                                <p class="mt-1 text-[11px] font-bold text-slate-500">{{ $remaining }} left</p>
                                            @else
                                                <p class="font-black text-emerald-700">0 left</p>
                                            @endif
                                        </div>

                                        <div>
                                            <label class="mb-1 flex items-center gap-1 text-[10px] font-black uppercase tracking-wide text-slate-500">Editable price <span class="rounded bg-indigo-50 px-1.5 py-0.5 text-[9px] text-indigo-700 ring-1 ring-indigo-100">EDIT</span></label>
                                            @if ($canBuy)
                                                <input form="{{ $purchaseFormId }}" name="purchase_unit_price[{{ $item->item_id }}]" type="number" min="0" step="0.01" value="{{ number_format((float) $item->unit_price, 2, '.', '') }}" class="h-12 w-full rounded-xl border-2 border-indigo-200 bg-indigo-50/60 px-3 text-sm font-black text-slate-950 shadow-inner focus:border-indigo-500 focus:bg-white focus:ring-indigo-200">
                                            @else
                                                <span class="text-sm font-bold text-slate-400">—</span>
                                            @endif
                                        </div>

                                        <div>
                                            @if ($item->product_url)
                                                <a href="{{ $item->product_url }}" target="_blank" class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-sm font-black text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-100" title="Open product link">↗</a>
                                            @else
                                                <span class="text-sm font-bold text-slate-300">—</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @if ($remainingItems->isNotEmpty())
                            <form id="{{ $purchaseFormId }}" method="POST" action="{{ route('purchasing.purchases.bulk') }}" class="sticky bottom-4 z-20 mt-4 rounded-2xl border border-indigo-200 bg-white/95 p-4 shadow-2xl shadow-indigo-950/10 backdrop-blur" data-purchase-form>
                                @csrf
                                <div class="grid gap-3 lg:grid-cols-[1fr_180px_170px_auto] lg:items-end">
                                    <div>
                                        <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Retailer order reference *</label>
                                        <input name="retailer_order_reference" maxlength="255" required placeholder="e.g. 123-1234567-1234567" class="mt-1 h-11 w-full rounded-2xl border-2 border-indigo-200 bg-indigo-50/60 px-4 text-sm font-black text-slate-900 placeholder:text-indigo-300 focus:border-indigo-500 focus:bg-white focus:ring-indigo-200">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">ETA / expected UK hub *</label>
                                        <input name="expected_uk_hub_at" type="date" required class="mt-1 h-11 w-full rounded-2xl border-2 border-indigo-200 bg-indigo-50/60 px-4 text-sm font-black text-slate-900 focus:border-indigo-500 focus:bg-white focus:ring-indigo-200">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Ordered date</label>
                                        <input name="ordered_at" type="date" value="{{ now()->format('Y-m-d') }}" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white px-4 text-sm font-black text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                    </div>
                                    <button class="h-11 rounded-2xl bg-indigo-600 px-6 text-sm font-black text-white shadow-sm hover:bg-indigo-700">Record Purchase</button>
                                </div>
                                <div class="mt-3 grid gap-3 lg:grid-cols-[1fr_auto] lg:items-center">
                                    <textarea name="note" rows="1" maxlength="2000" placeholder="Optional internal note for this purchase batch" class="rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-indigo-300 focus:bg-white focus:ring-indigo-200"></textarea>
                                    <p class="text-xs font-black text-indigo-950"><span data-selected-lines-footer>0</span> selected</p>
                                </div>
                            </form>
                        @else
                            <div class="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-sm font-black text-emerald-800">Nothing left to buy for this retailer. Purchased items stay visible here for context.</div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                    <p class="text-lg font-black text-slate-900">No purchasable items found for this order.</p>
                    <p class="mt-2 text-sm font-semibold text-slate-500">This may be completed, cancelled, superseded, or customer self-purchase.</p>
                </div>
            @endforelse
        </section>
    </div>

    <script>
        document.querySelectorAll('[data-retailer-card]').forEach((card) => {
            const checkboxes = Array.from(card.querySelectorAll('[data-line-checkbox]'));
            const selected = card.querySelector('[data-selected-lines]');
            const selectedFooter = card.querySelector('[data-selected-lines-footer]');
            const form = card.querySelector('[data-purchase-form]');
            const update = () => {
                const count = checkboxes.filter((checkbox) => checkbox.checked).length;
                if (selected) selected.textContent = count;
                if (selectedFooter) selectedFooter.textContent = count;
            };

            card.querySelector('[data-select-all]')?.addEventListener('click', () => {
                checkboxes.forEach((checkbox) => checkbox.checked = true);
                update();
            });

            card.querySelector('[data-select-none]')?.addEventListener('click', () => {
                checkboxes.forEach((checkbox) => checkbox.checked = false);
                update();
            });

            checkboxes.forEach((checkbox) => checkbox.addEventListener('change', update));

            form?.addEventListener('submit', (event) => {
                const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
                const reference = form.querySelector('[name="retailer_order_reference"]');
                const eta = form.querySelector('[name="expected_uk_hub_at"]');

                if (selectedCount === 0) {
                    event.preventDefault();
                    alert('Please select at least one item to purchase.');
                    return;
                }

                if (! reference?.value.trim()) {
                    event.preventDefault();
                    reference?.focus();
                    alert('Retailer order reference is required before saving a purchase.');
                    return;
                }

                if (! eta?.value) {
                    event.preventDefault();
                    eta?.focus();
                    alert('ETA / expected UK hub date is required before saving a purchase.');
                    return;
                }
            });

            update();
        });
    </script>
</x-app-layout>
