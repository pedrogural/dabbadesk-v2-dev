<x-app-layout>
    <x-slot name="header">Purchasing Order #{{ $order->order_number ?? $order->id }}</x-slot>

    @php
        $money = fn ($amount) => '£' . number_format((float) $amount, 2);
        $orderNumber = $order->order_number ?? $order->id;
        $customer = trim((string) ($order->bill_to_company ?: $order->bill_to_name ?: 'Unknown customer'));
        $paymentStatus = $queueOrder['payment_status'] ?? 'unpaid';
        $paymentLabel = match ($paymentStatus) {'paid' => 'Paid', 'part_paid' => 'Part paid', default => 'Unpaid'};
        $paymentClass = match ($paymentStatus) {'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'part_paid' => 'bg-amber-50 text-amber-700 ring-amber-200', default => 'bg-rose-50 text-rose-700 ring-rose-200'};
        $remainingQty = (int) ($queueOrder['remaining_to_buy_qty'] ?? $items->sum('remaining_to_buy_qty'));
        $purchasedQty = (int) ($queueOrder['purchased_qty'] ?? $items->sum('purchased_qty'));
        $problemQty = (int) ($queueOrder['problem_qty'] ?? $items->sum('problem_qty'));
        $inspectionQty = (int) ($queueOrder['inspection_count'] ?? $items->filter(fn ($i) => (int)($i->requires_inspection ?? 0) === 1)->count());
        $problemCodes = [
            'out_of_stock' => 'Out of Stock',
            'price_increased' => 'Price Increased',
            'discontinued' => 'Discontinued',
            'retailer_restriction' => 'Retailer Restriction',
            'supplier_cancelled' => 'Supplier Cancelled',
            'wrong_listing' => 'Wrong Listing',
            'unavailable' => 'Unavailable',
            'lost' => 'Lost',
            'damaged' => 'Damaged',
            'wrong_item' => 'Wrong Item',
            'retailer_refunded' => 'Retailer Refunded',
            'other' => 'Other',
        ];
        $purchaseStatuses = ['purchased', 'ordered', 'received'];
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

        <section class="overflow-hidden rounded-[1.75rem] border border-indigo-100 bg-white shadow-sm">
            <div class="bg-indigo-50/70 px-5 py-5 sm:px-6">
                <a href="{{ route('purchasing.index') }}" class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600 hover:text-indigo-800">← Back to Purchasing Desk</a>
                <div class="mt-3 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-2xl font-black tracking-tight text-slate-950">Purchasing Order #{{ $orderNumber }}</h1>
                            <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $paymentClass }}">{{ $paymentLabel }}</span>
                            @if ($inspectionQty > 0)
                                <span class="rounded-full bg-purple-50 px-3 py-1.5 text-xs font-black text-purple-700 ring-1 ring-purple-200">{{ $inspectionQty }} package check</span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm font-bold text-slate-700">{{ $customer }}</p>
                        @if ($order->bill_to_email)
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $order->bill_to_email }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 border-t border-indigo-100 bg-white px-5 py-4 text-xs font-black sm:px-6">
                <span class="rounded-full bg-slate-50 px-3 py-1.5 text-slate-700 ring-1 ring-slate-200">Retailers: {{ $retailers->count() }}</span>
                <span class="rounded-full bg-slate-50 px-3 py-1.5 text-slate-700 ring-1 ring-slate-200">Order total: {{ $money($order->grand_total ?? 0) }}</span>
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
                    $problemQtyForRetailer = (int) $retailerItems->sum('problem_qty');
                    $inspectionForRetailer = $retailerItems->filter(fn ($item) => (int)($item->requires_inspection ?? 0) === 1)->count();
                    $waitingValue = $remainingItems->sum(fn ($item) => ((float) $item->unit_price) * ((int) $item->remaining_to_buy_qty));
                    $cardRing = $problemQtyForRetailer > 0 ? 'border-rose-200' : ($waitingQty > 0 ? 'border-indigo-200' : 'border-emerald-200');
                    $purchaseFormId = 'purchase-retailer-' . ($retailer['retailer_id'] ?? 'unknown') . '-' . $loop->index;
                    $bulkEditFormId = 'bulk-edit-retailer-' . ($retailer['retailer_id'] ?? 'unknown') . '-' . $loop->index;
                @endphp

                <article class="overflow-visible rounded-[1.75rem] border {{ $cardRing }} bg-white shadow-sm" data-retailer-card>
                    <div class="border-b border-indigo-100 bg-indigo-50/60 p-5 sm:p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-2xl font-black text-slate-950">{{ $retailer['retailer_name'] }}</h2>
                                    @if ($inspectionForRetailer > 0)
                                        <span class="rounded-full bg-purple-100 px-3 py-1.5 text-xs font-black text-purple-800 ring-1 ring-purple-200">🟪 Package check</span>
                                    @endif
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2 text-xs font-black">
                                    <span class="rounded-full bg-white px-3 py-1.5 text-indigo-800 ring-1 ring-indigo-100">Items: {{ $remainingItems->count() }}</span>
                                    <span class="rounded-full bg-white px-3 py-1.5 text-indigo-800 ring-1 ring-indigo-100">Qty: {{ $waitingQty }}</span>
                                    <span class="rounded-full bg-white px-3 py-1.5 text-indigo-800 ring-1 ring-indigo-100">Value: {{ $money($waitingValue) }}</span>
                                    @if ($purchasedQtyForRetailer > 0)
                                        <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-emerald-700 ring-1 ring-emerald-100">Purchased: {{ $purchasedQtyForRetailer }}</span>
                                    @endif
                                    @if ($problemQtyForRetailer > 0)
                                        <span class="rounded-full bg-rose-50 px-3 py-1.5 text-rose-700 ring-1 ring-rose-100">Problems: {{ $problemQtyForRetailer }}</span>
                                    @endif
                                </div>
                            </div>

                            @if ($remainingItems->isNotEmpty())
                                <div class="flex flex-wrap gap-2 lg:justify-end">
                                    <button type="button" data-select-all class="rounded-xl bg-white px-3 py-2 text-xs font-black text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-50">Select all</button>
                                    <button type="button" data-select-none class="rounded-xl bg-white px-3 py-2 text-xs font-black text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">Clear selection</button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-5 p-5 sm:p-6">
                        <input form="{{ $purchaseFormId }}" type="hidden" name="order_id" value="{{ $order->id }}">

                        <section>
                            <div class="mb-3">
                                <h3 class="text-sm font-black uppercase tracking-wide text-indigo-700">To Purchase</h3>
                            </div>

                            @if ($remainingItems->isNotEmpty())
                                <div class="overflow-hidden rounded-2xl border border-slate-200">
                                    <div class="hidden grid-cols-[56px_1fr_110px_150px_160px_88px] gap-3 bg-slate-50 px-4 py-3 text-[10px] font-black uppercase tracking-wide text-slate-400 md:grid">
                                        <div>Buy</div><div>Item</div><div>Remaining</div><div>Qty now</div><div>Actual price</div><div>Link</div>
                                    </div>
                                    <div class="divide-y divide-slate-100">
                                        @foreach ($remainingItems as $item)
                                            @php
                                                $remaining = (int) $item->remaining_to_buy_qty;
                                                $isPurple = (int)($item->requires_inspection ?? 0) === 1;
                                            @endphp
                                            <div class="grid gap-3 px-4 py-4 md:grid-cols-[56px_1fr_110px_150px_160px_88px] md:items-start {{ $isPurple ? 'bg-purple-50/80 ring-1 ring-inset ring-purple-100' : 'bg-white' }}">
                                                <div>
                                                    <label class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 ring-1 ring-slate-200">
                                                        <input form="{{ $purchaseFormId }}" type="checkbox" name="order_item_ids[]" value="{{ $item->item_id }}" data-line-checkbox class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                        <span class="text-xs font-black text-slate-700 md:hidden">Buy</span>
                                                    </label>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <p class="font-black leading-5 text-slate-950">{{ $item->item_name }}</p>
                                                        @if ($isPurple)
                                                            <span class="rounded-full bg-purple-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-purple-800">Package check</span>
                                                        @endif
                                                    </div>
                                                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ $item->product_code ?: 'No product code' }}</p>
                                                    @if ($isPurple && $item->inspection_note)
                                                        <p class="mt-2 rounded-xl bg-purple-100 px-3 py-2 text-xs font-bold text-purple-900">{{ $item->inspection_note }}</p>
                                                    @endif

                                                    <details class="mt-3 rounded-xl border border-amber-200 bg-amber-50/70 p-3">
                                                        <summary class="cursor-pointer text-xs font-black text-amber-800">Record purchasing issue</summary>
                                                        <form method="POST" action="{{ route('purchasing.problems.store') }}" class="mt-3 grid gap-2 sm:grid-cols-[1fr_110px]" data-issue-form>
                                                            @csrf
                                                            <input type="hidden" name="order_item_id" value="{{ $item->item_id }}">
                                                            <div>
                                                                <label class="text-[10px] font-black uppercase tracking-wide text-amber-700">Issue type *</label>
                                                                <select name="problem_code" required class="mt-1 h-10 w-full rounded-xl border-amber-200 bg-white px-3 text-xs font-black text-slate-900 focus:border-amber-400 focus:ring-amber-200">
                                                                    <option value="out_of_stock">Out of Stock</option>
                                                                    <option value="price_increased">Price Increased</option>
                                                                    <option value="discontinued">Discontinued</option>
                                                                    <option value="retailer_restriction">Retailer Restriction</option>
                                                                    <option value="supplier_cancelled">Supplier Cancelled</option>
                                                                    <option value="wrong_listing">Wrong Listing</option>
                                                                    <option value="other">Other</option>
                                                                </select>
                                                            </div>
                                                            <div>
                                                                <label class="text-[10px] font-black uppercase tracking-wide text-amber-700">Qty *</label>
                                                                <input name="qty" required type="number" min="1" max="{{ $remaining }}" value="{{ $remaining }}" class="mt-1 h-10 w-full rounded-xl border-amber-200 bg-white px-3 text-xs font-black text-slate-900 focus:border-amber-400 focus:ring-amber-200">
                                                            </div>
                                                            <div class="sm:col-span-2">
                                                                <label class="text-[10px] font-black uppercase tracking-wide text-amber-700">Notes</label>
                                                                <input name="problem_notes" maxlength="2000" placeholder="Optional detail, e.g. price changed from £25 to £40" class="mt-1 h-10 w-full rounded-xl border-amber-200 bg-white px-3 text-xs font-bold text-slate-900 focus:border-amber-400 focus:ring-amber-200">
                                                                <input type="hidden" name="resolution_action" value="customer_decision_required">
                                                            </div>
                                                            <div class="sm:col-span-2 flex justify-end">
                                                                <button class="rounded-xl bg-amber-600 px-3 py-2 text-xs font-black text-white hover:bg-amber-700">Save issue</button>
                                                            </div>
                                                        </form>
                                                    </details>
                                                </div>
                                                <div><span class="text-xs font-black uppercase tracking-wide text-slate-400 md:hidden">Remaining</span><p class="font-black text-slate-900">{{ $remaining }}</p></div>
                                                <div>
                                                    <label class="mb-1 flex items-center gap-1 text-[10px] font-black uppercase tracking-wide text-slate-500">Editable qty <span class="rounded bg-indigo-50 px-1.5 py-0.5 text-[9px] text-indigo-700 ring-1 ring-indigo-100">EDIT</span></label>
                                                    <input form="{{ $purchaseFormId }}" name="qty[{{ $item->item_id }}]" type="number" min="0" max="{{ $remaining }}" value="{{ $remaining }}" data-line-qty class="h-12 w-full rounded-xl border-2 border-indigo-400 bg-indigo-50 px-3 text-sm font-black text-slate-950 shadow-inner ring-2 ring-indigo-100 focus:border-indigo-600 focus:bg-white focus:ring-indigo-300">
                                                </div>
                                                <div>
                                                    <label class="mb-1 flex items-center gap-1 text-[10px] font-black uppercase tracking-wide text-slate-500">Editable price <span class="rounded bg-indigo-50 px-1.5 py-0.5 text-[9px] text-indigo-700 ring-1 ring-indigo-100">EDIT</span></label>
                                                    <input form="{{ $purchaseFormId }}" name="purchase_unit_price[{{ $item->item_id }}]" type="number" min="0" step="0.01" value="{{ number_format((float) $item->unit_price, 2, '.', '') }}" class="h-12 w-full rounded-xl border-2 border-indigo-400 bg-indigo-50 px-3 text-sm font-black text-slate-950 shadow-inner ring-2 ring-indigo-100 focus:border-indigo-600 focus:bg-white focus:ring-indigo-300">
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
                                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-sm font-black text-emerald-800">Nothing left to buy for this retailer.</div>
                            @endif
                        </section>

                        <section>
                            @php
                                $retailerPurchaseEvents = collect();
                                foreach ($retailerItems as $item) {
                                    $history = collect($purchasesByRoot[$item->lineage_root_id] ?? []);
                                    $retailerPurchaseEvents = $retailerPurchaseEvents->merge($history->filter(fn ($purchase) => in_array($purchase->status, $purchaseStatuses, true)));
                                }
                                $retailerPurchaseEvents = $retailerPurchaseEvents->unique('id')->values();
                                $editablePurchaseCount = $retailerPurchaseEvents->filter(function ($purchase) use ($arrivals) {
                                    $activeArrivalQty = (int) collect($arrivals)->where('order_item_purchase_id', $purchase->id)->sum('qty');
                                    return empty($purchase->cancelled_at) && $activeArrivalQty === 0;
                                })->count();
                            @endphp
                            <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="text-sm font-black uppercase tracking-wide text-emerald-700">Purchased</h3>
                                    <p class="mt-1 text-xs font-bold text-slate-500">View, edit, or undo purchases before arrival. Use bulk edit for shared fields.</p>
                                </div>
                            </div>

                            @if ($retailerPurchaseEvents->isNotEmpty())
                                <div class="space-y-3">
                                    @foreach ($retailerPurchaseEvents as $purchase)
                                        @php
                                            $activeArrivalQty = (int) collect($arrivals)->where('order_item_purchase_id', $purchase->id)->sum('qty');
                                            $wasUndone = ! empty($purchase->cancelled_at);
                                            $canEditPurchase = ! $wasUndone && $activeArrivalQty === 0;
                                        @endphp
                                        <div class="rounded-2xl border {{ $wasUndone ? 'border-slate-200 bg-slate-50' : ($canEditPurchase ? 'border-emerald-200 bg-emerald-50/60' : 'border-amber-200 bg-amber-50/60') }} p-4 text-sm">
                                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        @if ($canEditPurchase)
                                                            <input form="{{ $bulkEditFormId }}" type="checkbox" name="purchase_ids[]" value="{{ $purchase->id }}" data-purchase-edit-checkbox class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                                        @endif
                                                        <p class="font-black text-slate-950">{{ $purchase->item_name }} · Qty {{ (int) $purchase->qty }} · {{ $money($purchase->purchase_unit_price ?? 0) }}</p>
                                                        @if ((int)($purchase->requires_inspection ?? 0) === 1)
                                                            <span class="rounded-full bg-purple-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-purple-800">🟪 Package check</span>
                                                        @endif
                                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-black uppercase tracking-wide {{ $wasUndone ? 'bg-slate-100 text-slate-500' : ($canEditPurchase ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700') }}">{{ $wasUndone ? 'Undone' : ($canEditPurchase ? 'Editable' : 'Arrival exists') }}</span>
                                                    </div>
                                                    <p class="mt-1 text-xs font-bold text-slate-500">Ref {{ $purchase->retailer_order_reference ?: '—' }} · ETA {{ $purchase->expected_uk_hub_at ? \Carbon\Carbon::parse($purchase->expected_uk_hub_at)->format('d M Y') : '—' }} · Tracking —</p>
                                                    @if ((int)($purchase->requires_inspection ?? 0) === 1 && ! empty($purchase->inspection_note))
                                                        <p class="mt-2 rounded-xl bg-purple-100 px-3 py-2 text-xs font-bold text-purple-900 ring-1 ring-purple-200">{{ $purchase->inspection_note }}</p>
                                                    @endif
                                                    @if ($purchase->note)
                                                        <p class="mt-2 rounded-xl bg-white/80 px-3 py-2 text-xs font-bold text-slate-600 ring-1 ring-slate-100">{{ $purchase->note }}</p>
                                                    @endif
                                                </div>
                                                <div class="flex flex-wrap gap-2">
                                                    @if ($canEditPurchase)
                                                        <details class="rounded-xl bg-white px-3 py-2 ring-1 ring-emerald-100">
                                                            <summary class="cursor-pointer text-xs font-black text-emerald-700">Edit</summary>
                                                            <form method="POST" action="{{ route('purchasing.purchases.update', $purchase->id) }}" class="mt-3 grid min-w-[280px] gap-2 md:min-w-[520px] md:grid-cols-2" data-edit-purchase-form>
                                                                @csrf
                                                                @method('PATCH')
                                                                <div>
                                                                    <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Qty *</label>
                                                                    <input name="qty" required type="number" min="1" max="999" value="{{ (int) $purchase->qty }}" class="mt-1 h-10 w-full rounded-xl border-2 border-emerald-300 bg-emerald-50 px-3 text-xs font-black text-slate-950 focus:border-emerald-600 focus:bg-white focus:ring-emerald-200">
                                                                </div>
                                                                <div>
                                                                    <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Purchase price *</label>
                                                                    <input name="purchase_unit_price" required type="number" min="0" step="0.01" value="{{ number_format((float) ($purchase->purchase_unit_price ?? 0), 2, '.', '') }}" class="mt-1 h-10 w-full rounded-xl border-2 border-emerald-300 bg-emerald-50 px-3 text-xs font-black text-slate-950 focus:border-emerald-600 focus:bg-white focus:ring-emerald-200">
                                                                </div>
                                                                <div class="md:col-span-2">
                                                                    <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Retailer reference *</label>
                                                                    <input name="retailer_order_reference" required maxlength="255" value="{{ $purchase->retailer_order_reference }}" class="mt-1 h-10 w-full rounded-xl border-2 border-emerald-300 bg-emerald-50 px-3 text-xs font-black text-slate-950 focus:border-emerald-600 focus:bg-white focus:ring-emerald-200">
                                                                </div>
                                                                <div>
                                                                    <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">ETA *</label>
                                                                    <input name="expected_uk_hub_at" required type="date" value="{{ $purchase->expected_uk_hub_at ? \Carbon\Carbon::parse($purchase->expected_uk_hub_at)->format('Y-m-d') : '' }}" class="mt-1 h-10 w-full rounded-xl border-2 border-emerald-300 bg-emerald-50 px-3 text-xs font-black text-slate-950 focus:border-emerald-600 focus:bg-white focus:ring-emerald-200">
                                                                </div>
                                                                <div>
                                                                    <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Ordered date</label>
                                                                    <input name="ordered_at" type="date" value="{{ $purchase->ordered_at ? \Carbon\Carbon::parse($purchase->ordered_at)->format('Y-m-d') : now()->format('Y-m-d') }}" class="mt-1 h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-xs font-bold text-slate-950 focus:border-emerald-300 focus:ring-emerald-200">
                                                                </div>
                                                                <div class="md:col-span-2">
                                                                    <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Optional note</label>
                                                                    <input name="note" maxlength="2000" value="{{ $purchase->note }}" class="mt-1 h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-xs font-bold text-slate-950 focus:border-emerald-300 focus:ring-emerald-200">
                                                                </div>
                                                                <div class="md:col-span-2 flex flex-wrap items-center gap-2">
                                                                    <button class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-black text-white hover:bg-emerald-700">Save purchase changes</button>
                                                                    <span class="text-[11px] font-bold text-slate-500">Only editable before arrival is recorded.</span>
                                                                </div>
                                                            </form>
                                                        </details>

                                                        <form method="POST" action="{{ route('purchasing.purchases.undo', $purchase->id) }}" class="flex gap-2" data-confirm-undo>
                                                            @csrf
                                                            <input name="reason" required placeholder="Undo reason" class="h-9 w-36 rounded-xl border-slate-200 bg-white px-3 text-xs font-bold focus:border-rose-300 focus:ring-rose-200">
                                                            <button class="rounded-xl bg-rose-600 px-3 py-2 text-xs font-black text-white hover:bg-rose-700">Undo</button>
                                                        </form>
                                                    @else
                                                        <span class="rounded-xl bg-white px-3 py-2 text-xs font-black text-slate-500 ring-1 ring-slate-200">Locked</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if ($editablePurchaseCount > 0)
                                    <form id="{{ $bulkEditFormId }}" method="POST" action="{{ route('purchasing.purchases.bulk-update') }}" class="sticky bottom-4 z-20 mt-4 rounded-2xl border border-emerald-200 bg-white/95 p-4 shadow-2xl shadow-emerald-950/10 backdrop-blur" data-bulk-edit-purchases>
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                                        <div class="grid gap-3 lg:grid-cols-[1fr_180px_170px_1fr_auto] lg:items-end">
                                            <div>
                                                <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Retailer reference</label>
                                                <input name="retailer_order_reference" maxlength="255" placeholder="Update selected purchases" class="mt-1 h-11 w-full rounded-2xl border-emerald-200 bg-emerald-50/60 px-4 text-sm font-bold text-slate-900 focus:border-emerald-400 focus:bg-white focus:ring-emerald-200">
                                            </div>
                                            <div>
                                                <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">ETA</label>
                                                <input name="expected_uk_hub_at" type="date" class="mt-1 h-11 w-full rounded-2xl border-emerald-200 bg-emerald-50/60 px-4 text-sm font-bold text-slate-900 focus:border-emerald-400 focus:bg-white focus:ring-emerald-200">
                                            </div>
                                            <div>
                                                <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Ordered date</label>
                                                <input name="ordered_at" type="date" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white px-4 text-sm font-bold text-slate-900 focus:border-emerald-300 focus:ring-emerald-200">
                                            </div>
                                            <div>
                                                <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Note</label>
                                                <input name="note" maxlength="2000" placeholder="Optional shared note" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white px-4 text-sm font-bold text-slate-900 focus:border-emerald-300 focus:ring-emerald-200">
                                            </div>
                                            <button class="h-11 rounded-2xl bg-emerald-600 px-5 text-sm font-black text-white hover:bg-emerald-700">Update Selected</button>
                                        </div>
                                        <p class="mt-2 text-xs font-black text-emerald-950"><span data-selected-purchases-footer>0</span> selected purchases</p>
                                    </form>
                                @endif
                            @else
                                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm font-bold text-slate-500">No purchases recorded for this retailer yet.</div>
                            @endif
                        </section>

                        <section>
                            @php
                                $problemEvents = collect();
                                foreach ($retailerItems as $item) {
                                    $history = collect($purchasesByRoot[$item->lineage_root_id] ?? []);
                                    $problemEvents = $problemEvents->merge($history->filter(fn ($purchase) => ! in_array($purchase->status, $purchaseStatuses, true)));
                                }
                                $problemEvents = $problemEvents->unique('id')->values();
                                $activeProblemEvents = $problemEvents->filter(fn ($problem) => ($problem->resolution_status ?: 'pending') === 'pending')->values();
                                $problemHistoryEvents = $problemEvents->filter(fn ($problem) => ($problem->resolution_status ?: 'pending') !== 'pending')->values();
                            @endphp
                            <div class="mb-3">
                                <h3 class="text-sm font-black uppercase tracking-wide text-rose-700">Problems</h3>
                                <p class="mt-1 text-xs font-bold text-slate-500">Edit active issues, resolve them back to purchase, or close them permanently.</p>
                            </div>

                            @if ($activeProblemEvents->isNotEmpty())
                                <div class="space-y-3">
                                    @foreach ($activeProblemEvents as $problem)
                                        <div class="rounded-2xl border border-rose-100 bg-rose-50/70 p-4 text-sm">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                <div class="min-w-0">
                                                    <p class="font-black text-rose-950">{{ $problem->item_name }} · {{ $problemCodes[$problem->problem_code] ?? 'Purchasing Issue' }}</p>
                                                    <p class="mt-1 text-xs font-bold text-rose-700">Qty {{ (int) $problem->qty }} · {{ $problem->problem_notes ?: 'No note recorded' }}</p>
                                                </div>
                                                <span class="w-fit rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase tracking-wide text-rose-700 ring-1 ring-rose-100">Active</span>
                                            </div>

                                            <div class="mt-3 grid gap-3 lg:grid-cols-2">
                                                <details class="rounded-2xl border border-rose-100 bg-white p-3">
                                                    <summary class="cursor-pointer text-xs font-black text-rose-800">Edit problem</summary>
                                                    <form method="POST" action="{{ route('purchasing.problems.update', $problem->id) }}" class="mt-3 grid gap-2 sm:grid-cols-[1fr_110px]">
                                                        @csrf
                                                        @method('PATCH')
                                                        <div>
                                                            <label class="text-[10px] font-black uppercase tracking-wide text-rose-700">Issue type</label>
                                                            <select name="problem_code" required class="mt-1 h-10 w-full rounded-xl border-rose-200 bg-white px-3 text-xs font-black text-slate-900 focus:border-rose-400 focus:ring-rose-200">
                                                                @foreach ($problemCodes as $code => $label)
                                                                    <option value="{{ $code }}" @selected($problem->problem_code === $code)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="text-[10px] font-black uppercase tracking-wide text-rose-700">Qty</label>
                                                            <input name="qty" required type="number" min="1" max="999" value="{{ (int) $problem->qty }}" class="mt-1 h-10 w-full rounded-xl border-rose-200 bg-white px-3 text-xs font-black text-slate-900 focus:border-rose-400 focus:ring-rose-200">
                                                        </div>
                                                        <div class="sm:col-span-2">
                                                            <label class="text-[10px] font-black uppercase tracking-wide text-rose-700">Notes</label>
                                                            <input name="problem_notes" maxlength="2000" value="{{ $problem->problem_notes }}" placeholder="Optional details" class="mt-1 h-10 w-full rounded-xl border-rose-200 bg-white px-3 text-xs font-bold text-slate-900 focus:border-rose-400 focus:ring-rose-200">
                                                            <input type="hidden" name="resolution_action" value="{{ $problem->resolution_action ?: 'customer_decision_required' }}">
                                                        </div>
                                                        <div class="sm:col-span-2 flex justify-end">
                                                            <button class="rounded-xl bg-rose-600 px-3 py-2 text-xs font-black text-white hover:bg-rose-700">Save problem</button>
                                                        </div>
                                                    </form>
                                                </details>

                                                <details class="rounded-2xl border border-emerald-100 bg-white p-3">
                                                    <summary class="cursor-pointer text-xs font-black text-emerald-800">Resolve problem</summary>
                                                    <form method="POST" action="{{ route('purchasing.problems.resolve', $problem->id) }}" class="mt-3 space-y-3">
                                                        @csrf
                                                        <div class="grid gap-2 sm:grid-cols-2">
                                                            <label class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-3 text-xs font-black text-emerald-900">
                                                                <input type="radio" name="resolution" value="return_to_purchase" checked class="mr-2 border-emerald-300 text-emerald-600 focus:ring-emerald-500">
                                                                Return to purchase
                                                            </label>
                                                            <label class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs font-black text-slate-700">
                                                                <input type="radio" name="resolution" value="closed" class="mr-2 border-slate-300 text-slate-700 focus:ring-slate-500">
                                                                Close permanently
                                                            </label>
                                                        </div>
                                                        <div>
                                                            <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Resolution note</label>
                                                            <input name="resolution_note" maxlength="2000" placeholder="Optional, e.g. stock now available" class="mt-1 h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-xs font-bold text-slate-900 focus:border-emerald-400 focus:ring-emerald-200">
                                                        </div>
                                                        <div class="flex justify-end">
                                                            <button class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-black text-white hover:bg-emerald-700">Resolve</button>
                                                        </div>
                                                    </form>
                                                </details>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm font-bold text-slate-500">No active purchasing problems for this retailer.</div>
                            @endif

                            @if ($problemHistoryEvents->isNotEmpty())
                                <details class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <summary class="cursor-pointer text-xs font-black uppercase tracking-wide text-slate-600">Problem history ({{ $problemHistoryEvents->count() }})</summary>
                                    <div class="mt-3 space-y-2">
                                        @foreach ($problemHistoryEvents as $problem)
                                            <div class="rounded-xl bg-white p-3 text-xs ring-1 ring-slate-100">
                                                <p class="font-black text-slate-900">{{ $problem->item_name }} · {{ $problemCodes[$problem->problem_code] ?? 'Purchasing Issue' }}</p>
                                                <p class="mt-1 font-bold text-slate-500">Qty {{ (int) $problem->qty }} · {{ $problem->resolution_status === 'returned_to_purchase' ? 'Returned to purchase' : 'Closed' }}</p>
                                                @if ($problem->internal_notes)
                                                    <p class="mt-2 whitespace-pre-line rounded-lg bg-slate-50 p-2 font-semibold text-slate-500">{{ trim($problem->internal_notes) }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @endif
                        </section>
                    </div>
                </article>
            @empty
                <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                    <p class="text-lg font-black text-slate-900">No purchasing data found for this order.</p>
                    <p class="mt-2 text-sm font-semibold text-slate-500">This may be completed, cancelled, superseded, or customer self-purchase.</p>
                </div>
            @endforelse
        </section>
    </div>

    <div id="purchase-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm" aria-hidden="true">
        <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
            <p id="purchase-modal-title" class="text-lg font-black text-slate-950">Check this purchase</p>
            <p id="purchase-modal-message" class="mt-2 text-sm font-semibold leading-6 text-slate-600"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" id="purchase-modal-cancel" class="hidden rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="button" id="purchase-modal-ok" class="rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-black text-white hover:bg-indigo-700">OK</button>
            </div>
        </div>
    </div>

    <script>
        const purchaseModal = document.getElementById('purchase-modal');
        const purchaseModalTitle = document.getElementById('purchase-modal-title');
        const purchaseModalMessage = document.getElementById('purchase-modal-message');
        const purchaseModalOk = document.getElementById('purchase-modal-ok');
        const purchaseModalCancel = document.getElementById('purchase-modal-cancel');
        let purchaseModalConfirmCallback = null;

        const showPurchaseModal = ({ title, message, confirm = false, onConfirm = null }) => {
            purchaseModalTitle.textContent = title || 'Check this purchase';
            purchaseModalMessage.textContent = message || '';
            purchaseModalConfirmCallback = onConfirm;
            purchaseModalCancel.classList.toggle('hidden', !confirm);
            purchaseModalOk.textContent = confirm ? 'Confirm' : 'OK';
            purchaseModal.classList.remove('hidden');
            purchaseModal.classList.add('flex');
            purchaseModal.setAttribute('aria-hidden', 'false');
            purchaseModalOk.focus();
        };

        const closePurchaseModal = () => {
            purchaseModal.classList.add('hidden');
            purchaseModal.classList.remove('flex');
            purchaseModal.setAttribute('aria-hidden', 'true');
            purchaseModalConfirmCallback = null;
        };

        purchaseModalCancel?.addEventListener('click', closePurchaseModal);
        purchaseModal?.addEventListener('click', (event) => {
            if (event.target === purchaseModal) closePurchaseModal();
        });
        purchaseModalOk?.addEventListener('click', () => {
            const callback = purchaseModalConfirmCallback;
            closePurchaseModal();
            if (callback) callback();
        });

        document.querySelectorAll('[data-retailer-card]').forEach((card) => {
            const checkboxes = Array.from(card.querySelectorAll('[data-line-checkbox]'));
            const selectedFooter = card.querySelector('[data-selected-lines-footer]');
            const form = card.querySelector('[data-purchase-form]');
            const update = () => {
                const count = checkboxes.filter((checkbox) => checkbox.checked).length;
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
                    showPurchaseModal({ title: 'No items selected', message: 'Please select at least one item before recording a purchase.' });
                    return;
                }

                if (! reference?.value.trim()) {
                    event.preventDefault();
                    reference?.focus();
                    showPurchaseModal({ title: 'Retailer reference required', message: 'Enter the retailer order reference before saving this purchase.' });
                    return;
                }

                if (! eta?.value) {
                    event.preventDefault();
                    eta?.focus();
                    showPurchaseModal({ title: 'ETA required', message: 'Enter the ETA / expected UK hub date before saving this purchase.' });
                    return;
                }
            });

            update();
        });

        document.querySelectorAll('[data-bulk-edit-purchases]').forEach((form) => {
            const boxes = Array.from(document.querySelectorAll(`[form="${form.id}"][data-purchase-edit-checkbox]`));
            const footer = form.querySelector('[data-selected-purchases-footer]');
            const update = () => {
                const count = boxes.filter((box) => box.checked).length;
                if (footer) footer.textContent = count;
            };
            boxes.forEach((box) => box.addEventListener('change', update));
            form.addEventListener('submit', (event) => {
                const selectedCount = boxes.filter((box) => box.checked).length;
                if (selectedCount === 0) {
                    event.preventDefault();
                    showPurchaseModal({ title: 'No purchases selected', message: 'Select at least one editable purchase before using bulk edit.' });
                    return;
                }
                const hasSharedValue = Array.from(form.querySelectorAll('input[name="retailer_order_reference"], input[name="expected_uk_hub_at"], input[name="ordered_at"], input[name="note"]'))
                    .some((input) => input.value.trim() !== '');
                if (! hasSharedValue) {
                    event.preventDefault();
                    showPurchaseModal({ title: 'Nothing to update', message: 'Enter at least one shared field before updating selected purchases.' });
                }
            });
            update();
        });

        document.querySelectorAll('form[data-confirm-undo]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                showPurchaseModal({
                    title: 'Undo this purchase?',
                    message: 'This will return the quantity to To Purchase. This is only allowed when the purchase has not arrived.',
                    confirm: true,
                    onConfirm: () => form.submit()
                });
            });
        });
    </script>
</x-app-layout>
