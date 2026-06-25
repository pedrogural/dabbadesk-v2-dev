        <div x-show="tab === 'items'" x-cloak>
            @php
                $purchaseRowsByRoot = collect($purchases ?? [])->groupBy(fn ($purchase) => (int) ($purchase->root_item_id ?: $purchase->order_item_id));
                $arrivalRowsByItem = collect($arrivals ?? [])->groupBy(fn ($arrival) => (int) ($arrival->order_item_id ?? 0));
                $dateTimeLabel = function ($value, string $fallback = '—') {
                    if (empty($value)) {
                        return $fallback;
                    }

                    try {
                        return \Carbon\Carbon::parse($value)->format('d M Y');
                    } catch (\Throwable $e) {
                        return $fallback;
                    }
                };
                $moneyLabel = fn ($value) => '£' . number_format((float) ($value ?? 0), 2);
                $statusText = fn ($value) => trim((string) $value) !== '' ? ucwords(str_replace('_', ' ', (string) $value)) : '—';
            @endphp

            <section class="w-full overflow-hidden rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">Items workspace</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">
                            Read-only item view. Open a card to see product, pricing, lifecycle, purchase, tracking and problem context.
                        </p>
                    </div>

                    <span class="rounded-full bg-slate-50 px-3 py-1.5 text-xs font-black text-slate-500 ring-1 ring-slate-200">
                        {{ $retailerGroups->count() }} retailer group{{ $retailerGroups->count() === 1 ? '' : 's' }}
                    </span>
                </div>

                <div class="mt-6 space-y-6">
                    @forelse ($retailerGroups as $group)
                        <div class="overflow-hidden rounded-3xl border border-indigo-100 bg-white shadow-sm">
                            <div class="flex flex-col gap-3 border-b border-indigo-200 bg-indigo-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <h3 class="truncate text-base font-black text-indigo-950">{{ $group->name }}</h3>
                                    <p class="mt-1 text-xs font-semibold text-indigo-700/80">
                                        {{ $group->item_count }} line{{ $group->item_count === 1 ? '' : 's' }} · Qty {{ $group->total_qty }}
                                        @if ($group->host && $group->host !== $group->name)
                                            · {{ $group->host }}
                                        @endif
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2 text-xs font-black">
                                    <span class="rounded-full bg-white/85 px-3 py-2 text-indigo-900 ring-1 ring-indigo-100">
                                        Total £{{ number_format($group->line_total ?? 0, 2) }}
                                    </span>
                                    <span class="rounded-full bg-white/70 px-3 py-2 text-indigo-800 ring-1 ring-indigo-100">
                                        Purchased {{ $group->purchased_qty }}/{{ $group->total_qty }}
                                    </span>
                                    <span class="rounded-full bg-white/70 px-3 py-2 text-indigo-800 ring-1 ring-indigo-100">
                                        Arrived {{ $group->arrived_qty }}/{{ $group->total_qty }}
                                    </span>
                                    @if ($group->remaining_qty > 0)
                                        <span class="rounded-full bg-rose-100 px-3 py-2 text-rose-700 ring-1 ring-rose-200">
                                            Remaining {{ $group->remaining_qty }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="divide-y divide-slate-100">
                                @foreach ($group->items as $item)
                                    @php
                                        $itemQty = max(0, (int) ($item->quantity ?? 0));
                                        $purchasedQty = max(0, (int) ($item->purchased_qty ?? 0));
                                        $arrivedQty = max(0, (int) ($item->arrived_qty ?? 0));
                                        $problemQty = max(0, (int) ($item->problem_qty ?? 0));
                                        $remainingPurchaseQty = max(0, (int) ($item->purchase_remaining_qty ?? 0));
                                        $expectedArrivalQty = max(0, (int) ($item->expected_arrival_qty ?? 0));
                                        $arrivalRemainingQty = max(0, (int) ($item->arrival_remaining_qty ?? 0));
                                        $rawArrivalStatus = (string) ($item->latest_arrival_status ?? '');
                                        $goodsLineTotal = round($itemQty * (float) ($item->unit_price ?? 0), 2);
                                        $itemDeliveryFee = round((float) ($item->item_retailer_delivery_fee ?? 0), 2);
                                        $sharedDeliveryFee = round((float) ($item->retailer_delivery_allocated ?? 0), 2);
                                        $dabbaFee = round((float) ($item->dabba_fee_allocated ?? 0), 2);
                                        $customerValue = round((float) ($item->line_total ?? $goodsLineTotal) + $itemDeliveryFee + $sharedDeliveryFee + $dabbaFee, 2);
                                        $itemPurchases = $purchaseRowsByRoot->get((int) ($item->root_item_id ?: $item->id), collect());
                                        $itemArrivals = $arrivalRowsByItem->get((int) $item->id, collect());
                                        $latestPurchase = $itemPurchases->first();
                                        $purchaseCostTotal = round((float) $itemPurchases->sum('purchase_line_total'), 2);
                                        $latestEta = $item->latest_expected_uk_hub_at ?: ($latestPurchase->expected_uk_hub_at ?? null);
                                        $latestInformedAt = $itemArrivals->pluck('informed_at')->filter()->sortDesc()->first();
                                        $latestCompletedAt = $itemArrivals->pluck('completed_at')->filter()->sortDesc()->first();

                                        if ($problemQty > 0) {
                                            $healthLabel = 'Problem';
                                            $healthClasses = 'bg-rose-50 text-rose-700 ring-rose-200';
                                        } elseif ($balanceDue > 0.004 || $remainingPurchaseQty > 0 || $arrivalRemainingQty > 0) {
                                            $healthLabel = 'Waiting';
                                            $healthClasses = 'bg-amber-50 text-amber-700 ring-amber-200';
                                        } else {
                                            $healthLabel = 'Normal';
                                            $healthClasses = 'bg-emerald-50 text-emerald-700 ring-emerald-200';
                                        }

                                        if ($balanceDue > 0.004) {
                                            $statusLabel = 'Pending payment';
                                            $statusClasses = 'bg-rose-50 text-rose-700 ring-rose-100';
                                        } elseif ($problemQty > 0) {
                                            $statusLabel = 'Problem';
                                            $statusClasses = 'bg-amber-50 text-amber-700 ring-amber-100';
                                        } elseif (in_array($rawArrivalStatus, ['delivered', 'collected', 'customer_informed', 'informed', 'ready_for_collection', 'for_delivery'], true)) {
                                            $statusLabel = $statusText($rawArrivalStatus);
                                            $statusClasses = 'bg-emerald-50 text-emerald-700 ring-emerald-100';
                                        } elseif ($arrivedQty >= $itemQty && $itemQty > 0) {
                                            $statusLabel = 'Arrived';
                                            $statusClasses = 'bg-sky-50 text-sky-700 ring-sky-100';
                                        } elseif ($isCustomerSelfPurchase) {
                                            $statusLabel = 'Customer purchased';
                                            $statusClasses = 'bg-sky-50 text-sky-700 ring-sky-100';
                                        } elseif ($purchasedQty >= $itemQty && $itemQty > 0) {
                                            $statusLabel = 'Purchased';
                                            $statusClasses = 'bg-indigo-50 text-indigo-700 ring-indigo-100';
                                        } elseif ($remainingPurchaseQty > 0) {
                                            $statusLabel = 'Pending purchase';
                                            $statusClasses = 'bg-slate-100 text-slate-700 ring-slate-200';
                                        } else {
                                            $statusLabel = $statusText($item->status ?? 'requested');
                                            $statusClasses = 'bg-slate-100 text-slate-700 ring-slate-200';
                                        }

                                        $steps = [
                                            ['label' => 'Requested', 'done' => true, 'detail' => 'Order item created'],
                                            ['label' => 'Paid', 'done' => $balanceDue <= 0.004, 'detail' => $balanceDue <= 0.004 ? 'No outstanding balance' : 'Awaiting payment'],
                                            ['label' => 'Purchased', 'done' => $purchasedQty > 0 || $isCustomerSelfPurchase, 'detail' => $isCustomerSelfPurchase ? 'Customer self-purchase' : ($purchasedQty . '/' . $itemQty . ' bought')],
                                            ['label' => 'Expected', 'done' => filled($latestEta), 'detail' => filled($latestEta) ? $dateTimeLabel($latestEta) : ($arrivalRemainingQty > 0 ? 'Waiting for ETA' : '—')],
                                            ['label' => 'Arrived', 'done' => $arrivedQty > 0, 'detail' => $arrivedQty > 0 ? ($arrivedQty . '/' . $expectedArrivalQty . ' arrived') : 'Not arrived'],
                                            ['label' => 'Informed', 'done' => filled($latestInformedAt), 'detail' => filled($latestInformedAt) ? $dateTimeLabel($latestInformedAt) : 'Not informed'],
                                            ['label' => 'Collected / Delivered', 'done' => filled($latestCompletedAt) || in_array($rawArrivalStatus, ['collected', 'delivered'], true), 'detail' => filled($latestCompletedAt) ? $dateTimeLabel($latestCompletedAt) : (in_array($rawArrivalStatus, ['collected', 'delivered'], true) ? $statusText($rawArrivalStatus) : 'Not completed')],
                                        ];
                                    @endphp

                                    <article x-data="{ open: false }" class="bg-white {{ $item->requires_inspection ? 'bg-purple-50/30' : '' }}">
                                        <div class="p-5">
                                            <div class="grid gap-4 lg:grid-cols-[1fr_180px_180px_150px] lg:items-start">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 {{ $healthClasses }}">{{ $healthLabel }}</span>
                                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 {{ $statusClasses }}">{{ $statusLabel }}</span>
                                                        @if ($item->requires_inspection)
                                                            <span class="inline-flex rounded-full bg-purple-100 px-3 py-1 text-xs font-black text-purple-700 ring-1 ring-purple-200">Purple check</span>
                                                        @endif
                                                    </div>
                                                    <h4 class="mt-3 text-base font-black leading-6 text-slate-950">{{ $item->item_name }}</h4>
                                                    <p class="mt-1 text-xs font-semibold text-slate-500">
                                                        {{ $item->retailer_display_name }}
                                                        @if ($item->product_code)
                                                            · Code {{ $item->product_code }}
                                                        @endif
                                                        @if ($item->latest_retailer_order_reference || $item->retailer_order_reference)
                                                            · Ref {{ $item->latest_retailer_order_reference ?: $item->retailer_order_reference }}
                                                        @endif
                                                    </p>
                                                </div>

                                                <div class="grid grid-cols-2 gap-2 text-sm lg:grid-cols-1">
                                                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Qty</p>
                                                        <p class="mt-1 font-black text-slate-950">{{ $itemQty }}</p>
                                                    </div>
                                                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Customer value</p>
                                                        <p class="mt-1 font-black text-slate-950">{{ $moneyLabel($customerValue) }}</p>
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-2 gap-2 text-sm lg:grid-cols-1">
                                                    <div class="rounded-2xl bg-indigo-50 p-3 ring-1 ring-indigo-100">
                                                        <p class="text-[10px] font-black uppercase tracking-wide text-indigo-500">Purchased</p>
                                                        <p class="mt-1 font-black text-indigo-950">{{ $purchasedQty }}/{{ $itemQty }}</p>
                                                    </div>
                                                    <div class="rounded-2xl bg-sky-50 p-3 ring-1 ring-sky-100">
                                                        <p class="text-[10px] font-black uppercase tracking-wide text-sky-500">Arrived</p>
                                                        <p class="mt-1 font-black text-sky-950">{{ $arrivedQty }}/{{ max($expectedArrivalQty, $itemQty) }}</p>
                                                    </div>
                                                </div>

                                                <div class="flex flex-col gap-2">
                                                    <button type="button" @click="open = ! open" class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white shadow-sm hover:bg-indigo-700">
                                                        <span x-show="!open">▶ Item details</span>
                                                        <span x-show="open" x-cloak>▼ Hide details</span>
                                                    </button>
                                                    @if ($item->product_url)
                                                        <a href="{{ $item->product_url }}" target="_blank" rel="noopener noreferrer" title="Open product page" class="inline-flex items-center justify-center rounded-2xl bg-white px-4 py-2.5 text-sm font-black text-indigo-700 ring-1 ring-indigo-200 hover:bg-indigo-50">↗ Product</a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div x-show="open" x-collapse x-cloak class="border-t border-slate-100 bg-slate-50/50 p-5">
                                            <div class="grid gap-4 xl:grid-cols-3">
                                                <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                                                    <h5 class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Product & pricing</h5>
                                                    <dl class="mt-4 grid gap-3 text-sm">
                                                        <div class="flex items-start justify-between gap-3"><dt class="font-bold text-slate-500">Retailer</dt><dd class="text-right font-black text-slate-900">{{ $item->retailer_display_name }}</dd></div>
                                                        <div class="flex items-start justify-between gap-3"><dt class="font-bold text-slate-500">Unit price</dt><dd class="text-right font-black text-slate-900">{{ $moneyLabel($item->unit_price ?? 0) }}</dd></div>
                                                        <div class="flex items-start justify-between gap-3"><dt class="font-bold text-slate-500">Goods total</dt><dd class="text-right font-black text-slate-900">{{ $moneyLabel($goodsLineTotal) }}</dd></div>
                                                        @if ($itemDeliveryFee > 0 || $sharedDeliveryFee > 0)
                                                            <div class="flex items-start justify-between gap-3"><dt class="font-bold text-slate-500">Retailer delivery</dt><dd class="text-right font-black text-slate-900">{{ $moneyLabel($itemDeliveryFee + $sharedDeliveryFee) }}</dd></div>
                                                        @endif
                                                        @if ($dabbaFee > 0)
                                                            <div class="flex items-start justify-between gap-3"><dt class="font-bold text-slate-500">Dabba fee allocated</dt><dd class="text-right font-black text-slate-900">{{ $moneyLabel($dabbaFee) }}</dd></div>
                                                        @endif
                                                        @if ($purchaseCostTotal > 0)
                                                            <div class="flex items-start justify-between gap-3 rounded-2xl bg-emerald-50 px-3 py-2 ring-1 ring-emerald-100"><dt class="font-black text-emerald-700">Purchase cost</dt><dd class="text-right font-black text-emerald-900">{{ $moneyLabel($purchaseCostTotal) }}</dd></div>
                                                        @endif
                                                    </dl>
                                                </section>

                                                <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                                                    <h5 class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Lifecycle</h5>
                                                    <div class="mt-4 space-y-2">
                                                        @foreach ($steps as $step)
                                                            <div class="flex items-start gap-3 rounded-2xl {{ $step['done'] ? 'bg-emerald-50 ring-emerald-100' : 'bg-slate-50 ring-slate-100' }} px-3 py-2 ring-1">
                                                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $step['done'] ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-500' }} text-xs font-black">{{ $step['done'] ? '✓' : '·' }}</span>
                                                                <div class="min-w-0">
                                                                    <p class="text-sm font-black {{ $step['done'] ? 'text-emerald-950' : 'text-slate-700' }}">{{ $step['label'] }}</p>
                                                                    <p class="mt-0.5 text-xs font-semibold {{ $step['done'] ? 'text-emerald-700' : 'text-slate-400' }}">{{ $step['detail'] }}</p>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </section>

                                                <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                                                    <h5 class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Tracking & notes</h5>
                                                    <div class="mt-4 space-y-3 text-sm">
                                                        <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Tracking reference</p>
                                                            <p class="mt-1 break-words font-bold text-slate-900">{{ $item->tracking_reference ?: 'No tracking reference recorded.' }}</p>
                                                        </div>
                                                        @if ($item->inspection_note)
                                                            <div class="rounded-2xl bg-purple-50 p-3 ring-1 ring-purple-100">
                                                                <p class="text-[10px] font-black uppercase tracking-wide text-purple-500">Purple check note</p>
                                                                <p class="mt-1 font-bold text-purple-900">{{ $item->inspection_note }}</p>
                                                            </div>
                                                        @endif
                                                        @if ($problemQty > 0)
                                                            <div class="rounded-2xl bg-rose-50 p-3 ring-1 ring-rose-100">
                                                                <p class="text-[10px] font-black uppercase tracking-wide text-rose-500">Problems</p>
                                                                <p class="mt-1 font-bold text-rose-900">{{ $problemQty }} item{{ $problemQty === 1 ? '' : 's' }} affected by purchasing / arrival problem records.</p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </section>
                                            </div>

                                            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                                <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                                                    <h5 class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Purchases</h5>
                                                    <div class="mt-4 space-y-3">
                                                        @forelse ($itemPurchases as $purchase)
                                                            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-black text-indigo-700 ring-1 ring-indigo-100">{{ $statusText($purchase->status ?? 'purchased') }}</span>
                                                                    <span class="text-xs font-black text-slate-500">Qty {{ (int) ($purchase->qty ?? 0) }}</span>
                                                                </div>
                                                                <div class="mt-3 grid gap-2 text-xs font-semibold text-slate-600 sm:grid-cols-2">
                                                                    <p><span class="font-black text-slate-400">Reference:</span> {{ $purchase->retailer_order_reference ?: '—' }}</p>
                                                                    <p><span class="font-black text-slate-400">Purchased:</span> {{ $dateTimeLabel($purchase->ordered_at ?: $purchase->created_at) }}</p>
                                                                    <p><span class="font-black text-slate-400">ETA UK hub:</span> {{ $dateTimeLabel($purchase->expected_uk_hub_at) }}</p>
                                                                    <p><span class="font-black text-slate-400">Cost:</span> {{ $moneyLabel($purchase->purchase_line_total ?? 0) }}</p>
                                                                </div>
                                                                @if ($purchase->problem_code || $purchase->problem_notes || $purchase->internal_notes)
                                                                    <p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-xs font-bold text-amber-900 ring-1 ring-amber-100">
                                                                        {{ $purchase->problem_notes ?: ($purchase->internal_notes ?: $statusText($purchase->problem_code)) }}
                                                                    </p>
                                                                @endif
                                                            </div>
                                                        @empty
                                                            <p class="rounded-2xl bg-slate-50 px-3 py-4 text-sm font-semibold text-slate-500 ring-1 ring-slate-100">No purchase events recorded for this item yet.</p>
                                                        @endforelse
                                                    </div>
                                                </section>

                                                <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                                                    <h5 class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Arrivals / release</h5>
                                                    <div class="mt-4 space-y-3">
                                                        @forelse ($itemArrivals as $arrival)
                                                            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                                    <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-black text-sky-700 ring-1 ring-sky-100">{{ $statusText($arrival->status ?? 'arrived') }}</span>
                                                                    <span class="text-xs font-black text-slate-500">Qty {{ (int) ($arrival->qty ?? 0) }}</span>
                                                                </div>
                                                                <div class="mt-3 grid gap-2 text-xs font-semibold text-slate-600 sm:grid-cols-2">
                                                                    <p><span class="font-black text-slate-400">Arrived:</span> {{ $dateTimeLabel($arrival->matched_at) }}</p>
                                                                    <p><span class="font-black text-slate-400">Informed:</span> {{ $dateTimeLabel($arrival->informed_at) }}</p>
                                                                    <p><span class="font-black text-slate-400">Completed:</span> {{ $dateTimeLabel($arrival->completed_at) }}</p>
                                                                    <p><span class="font-black text-slate-400">Purchase ref:</span> {{ $arrival->retailer_order_reference ?: '—' }}</p>
                                                                </div>
                                                                @if ($arrival->notes)
                                                                    <p class="mt-3 rounded-xl bg-white px-3 py-2 text-xs font-bold text-slate-600 ring-1 ring-slate-100">{{ $arrival->notes }}</p>
                                                                @endif
                                                            </div>
                                                        @empty
                                                            <p class="rounded-2xl bg-slate-50 px-3 py-4 text-sm font-semibold text-slate-500 ring-1 ring-slate-100">No arrival or release events recorded for this item yet.</p>
                                                        @endforelse
                                                    </div>
                                                </section>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                            No items found for this order.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
