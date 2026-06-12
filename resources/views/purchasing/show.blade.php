<x-app-layout>
    <x-slot name="header">Purchase Desk</x-slot>

    @php
        $money = fn ($amount) => '£' . number_format((float) $amount, 2);
        $orderNumber = $order->order_number ?? $order->id;
        $customer = trim((string) ($order->bill_to_company ?: $order->bill_to_name ?: 'Unknown customer'));
        $paymentStatus = $queueOrder['payment_status'] ?? 'unpaid';
        $paymentLabel = match ($paymentStatus) {
            'paid' => 'Paid',
            'part_paid' => 'Part paid',
            default => 'Unpaid',
        };
        $paymentClass = match ($paymentStatus) {
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'part_paid' => 'bg-amber-50 text-amber-700 ring-amber-200',
            default => 'bg-rose-50 text-rose-700 ring-rose-200',
        };
        $requestedQty = (int) ($queueOrder['requested_qty'] ?? $items->sum('quantity'));
        $purchasedQty = (int) ($queueOrder['purchased_qty'] ?? $items->sum('purchased_qty'));
        $remainingQty = (int) ($queueOrder['remaining_to_buy_qty'] ?? $items->sum('remaining_to_buy_qty'));
        $awaitingQty = (int) ($queueOrder['awaiting_arrival_qty'] ?? $items->sum('awaiting_arrival_qty'));
        $problemQty = (int) ($queueOrder['problem_qty'] ?? $items->sum('problem_qty'));
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

        <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
            <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-5 py-6 text-white sm:px-7">
                <a href="{{ route('purchasing.index') }}" class="text-xs font-black uppercase tracking-[0.22em] text-indigo-200 hover:text-white">← Back to Purchase Queue</a>

                <div class="mt-4 flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-3xl font-black tracking-tight">Order #{{ $orderNumber }}</h1>
                            <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $paymentClass }}">{{ $paymentLabel }}</span>
                            @if (($order->purchase_mode ?? '') === 'customer_self_purchase')
                                <span class="rounded-full bg-sky-50 px-3 py-1.5 text-xs font-black text-sky-700 ring-1 ring-sky-200">Customer self-purchase</span>
                            @endif
                        </div>
                        <p class="mt-2 text-sm font-semibold text-slate-300">{{ $customer }}</p>
                        @if ($order->bill_to_email)
                            <p class="mt-1 text-xs font-bold text-slate-400">{{ $order->bill_to_email }}</p>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5 xl:min-w-[720px]">
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-300">Order total</p>
                            <p class="mt-1 text-lg font-black">{{ $money($order->grand_total ?? 0) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-300">Requested</p>
                            <p class="mt-1 text-lg font-black">{{ $requestedQty }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-300">Purchased</p>
                            <p class="mt-1 text-lg font-black">{{ $purchasedQty }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-300">To buy</p>
                            <p class="mt-1 text-lg font-black">{{ $remainingQty }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-300">Awaiting</p>
                            <p class="mt-1 text-lg font-black">{{ $awaitingQty }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-white/10 bg-slate-50 px-5 py-4 sm:px-7">
                <div class="flex flex-wrap items-center gap-2 text-xs font-black">
                    <span class="rounded-full bg-white px-3 py-1.5 text-slate-700 ring-1 ring-slate-200">Retailer cards: {{ $retailers->count() }}</span>
                    <span class="rounded-full bg-white px-3 py-1.5 text-slate-700 ring-1 ring-slate-200">Problem qty: {{ $problemQty }}</span>
                    <a href="{{ route('orders.show', $order->id) }}" class="rounded-full bg-white px-3 py-1.5 text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-50">View full order ↗</a>
                </div>
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
                    $waitingValue = $remainingItems->sum(fn ($item) => ((float) $item->unit_price) * ((int) $item->remaining_to_buy_qty));
                    $isComplete = $waitingQty === 0 && $purchasedQtyForRetailer > 0;
                    $cardRing = $problemQtyForRetailer > 0 ? 'border-rose-200' : ($waitingQty > 0 ? 'border-indigo-200' : 'border-emerald-200');
                    $statusLabel = $problemQtyForRetailer > 0 ? 'Needs attention' : ($waitingQty > 0 ? 'Ready to buy' : 'Purchased');
                    $statusClass = $problemQtyForRetailer > 0 ? 'bg-rose-50 text-rose-700 ring-rose-200' : ($waitingQty > 0 ? 'bg-indigo-50 text-indigo-700 ring-indigo-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200');
                @endphp

                <article class="overflow-hidden rounded-[1.75rem] border {{ $cardRing }} bg-white shadow-sm" data-retailer-card>
                    <div class="border-b border-slate-100 p-5 sm:p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-2xl font-black text-slate-950">{{ $retailer['retailer_name'] }}</h2>
                                    <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $statusClass }}">{{ $statusLabel }}</span>
                                </div>
                                <p class="mt-2 text-sm font-bold text-slate-500">
                                    {{ $remainingItems->count() }} item line{{ $remainingItems->count() === 1 ? '' : 's' }} waiting · {{ $waitingQty }} qty to buy
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-4 lg:min-w-[560px]">
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Waiting value</p>
                                    <p class="mt-1 font-black text-slate-950">{{ $money($waitingValue) }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Purchased qty</p>
                                    <p class="mt-1 font-black text-slate-950">{{ $purchasedQtyForRetailer }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Awaiting qty</p>
                                    <p class="mt-1 font-black text-slate-950">{{ $awaitingQtyForRetailer }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Problems</p>
                                    <p class="mt-1 font-black text-slate-950">{{ $problemQtyForRetailer }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('purchasing.purchases.bulk') }}" class="p-5 sm:p-6" data-purchase-form>
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $order->id }}">

                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="hidden grid-cols-[48px_1fr_120px_120px_130px_70px] gap-3 bg-slate-50 px-4 py-3 text-[10px] font-black uppercase tracking-wide text-slate-400 md:grid">
                                <div>Buy</div>
                                <div>Item</div>
                                <div>Requested</div>
                                <div>Remaining</div>
                                <div>Purchase price</div>
                                <div>Link</div>
                            </div>

                            <div class="divide-y divide-slate-100">
                                @foreach ($retailerItems as $item)
                                    @php
                                        $remaining = (int) $item->remaining_to_buy_qty;
                                        $canBuy = $remaining > 0;
                                        $rootEvents = collect($purchasesByRoot[$item->lineage_root_id] ?? []);
                                    @endphp

                                    <div class="grid gap-3 px-4 py-4 md:grid-cols-[48px_1fr_120px_120px_130px_70px] md:items-center {{ $canBuy ? 'bg-white' : 'bg-slate-50/70' }}">
                                        <div>
                                            @if ($canBuy)
                                                <input type="checkbox" name="order_item_ids[]" value="{{ $item->item_id }}" checked data-line-checkbox class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                            @else
                                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-50 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">✓</span>
                                            @endif
                                        </div>

                                        <div class="min-w-0">
                                            <p class="font-black text-slate-950">{{ $item->item_name }}</p>
                                            <div class="mt-1 flex flex-wrap gap-2 text-xs font-semibold text-slate-500">
                                                @if ($item->product_code)
                                                    <span>SKU {{ $item->product_code }}</span>
                                                @endif
                                                @if ($item->marketplace_seller)
                                                    <span>Seller {{ $item->marketplace_seller }}</span>
                                                @endif
                                                <span>Customer price {{ $money($item->unit_price) }}</span>
                                            </div>
                                            @if ($rootEvents->isNotEmpty())
                                                <details class="mt-2">
                                                    <summary class="cursor-pointer select-none text-xs font-black text-indigo-700">{{ $rootEvents->count() }} purchase event{{ $rootEvents->count() === 1 ? '' : 's' }}</summary>
                                                    <div class="mt-2 space-y-1 rounded-xl bg-slate-50 p-2 text-xs font-semibold text-slate-600 ring-1 ring-slate-100">
                                                        @foreach ($rootEvents->take(4) as $event)
                                                            <div class="flex flex-wrap justify-between gap-2">
                                                                <span>Qty {{ (int) $event->qty }} · {{ ucfirst(str_replace('_', ' ', $event->status)) }}</span>
                                                                <span>{{ $event->retailer_order_reference ?: 'No ref' }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </details>
                                            @endif
                                        </div>

                                        <div class="flex items-center justify-between gap-2 md:block">
                                            <span class="text-xs font-black uppercase tracking-wide text-slate-400 md:hidden">Requested</span>
                                            <span class="font-black text-slate-900">{{ (int) $item->quantity }}</span>
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-400 md:hidden">Buying now</label>
                                            @if ($canBuy)
                                                <input name="qty[{{ $item->item_id }}]" type="number" min="0" max="{{ $remaining }}" value="{{ $remaining }}" data-line-qty class="h-11 w-full rounded-2xl border-slate-200 bg-white px-3 text-sm font-black text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                                <p class="mt-1 text-[11px] font-bold text-slate-400">{{ $remaining }} left</p>
                                            @else
                                                <p class="font-black text-emerald-700">0 left</p>
                                            @endif
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-400 md:hidden">Purchase price</label>
                                            @if ($canBuy)
                                                <input name="purchase_unit_price[{{ $item->item_id }}]" type="number" min="0" step="0.01" value="{{ number_format((float) $item->unit_price, 2, '.', '') }}" class="h-11 w-full rounded-2xl border-slate-200 bg-white px-3 text-sm font-black text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
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
                            <div class="mt-4 rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <p class="text-sm font-black text-indigo-950"><span data-selected-lines>{{ $remainingItems->count() }}</span> selected item line{{ $remainingItems->count() === 1 ? '' : 's' }}</p>
                                        <p class="mt-1 text-xs font-bold text-indigo-700">Tick only the items you are buying now. Reduce quantity where the retailer has less stock.</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" data-select-all class="rounded-xl bg-white px-3 py-2 text-xs font-black text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-50">Select all</button>
                                        <button type="button" data-select-none class="rounded-xl bg-white px-3 py-2 text-xs font-black text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">Select none</button>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-3 lg:grid-cols-4">
                                    <div class="lg:col-span-2">
                                        <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Retailer order reference</label>
                                        <input name="retailer_order_reference" maxlength="255" placeholder="e.g. 123-1234567-1234567" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white px-4 text-sm font-black text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">ETA / expected UK hub</label>
                                        <input name="expected_uk_hub_at" type="date" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white px-4 text-sm font-black text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Ordered date</label>
                                        <input name="ordered_at" type="date" value="{{ now()->format('Y-m-d') }}" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white px-4 text-sm font-black text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                    </div>
                                    <div class="lg:col-span-4">
                                        <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Internal note</label>
                                        <textarea name="note" rows="2" maxlength="2000" placeholder="Optional note for this purchase batch" class="mt-1 w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-indigo-300 focus:ring-indigo-200"></textarea>
                                    </div>
                                </div>

                                <div class="mt-4 flex justify-end">
                                    <button class="rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-black text-white shadow-sm hover:bg-indigo-700">Save selected purchase</button>
                                </div>
                            </div>
                        @else
                            <div class="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-sm font-black text-emerald-800">
                                Nothing left to buy for this retailer. Purchased items stay visible here for context.
                            </div>
                        @endif
                    </form>
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
        document.querySelectorAll('[data-purchase-form]').forEach((form) => {
            const checkboxes = Array.from(form.querySelectorAll('[data-line-checkbox]'));
            const selected = form.querySelector('[data-selected-lines]');
            const update = () => {
                if (! selected) return;
                selected.textContent = checkboxes.filter((checkbox) => checkbox.checked).length;
            };

            form.querySelector('[data-select-all]')?.addEventListener('click', () => {
                checkboxes.forEach((checkbox) => checkbox.checked = true);
                update();
            });

            form.querySelector('[data-select-none]')?.addEventListener('click', () => {
                checkboxes.forEach((checkbox) => checkbox.checked = false);
                update();
            });

            checkboxes.forEach((checkbox) => checkbox.addEventListener('change', update));
            update();
        });
    </script>
</x-app-layout>
