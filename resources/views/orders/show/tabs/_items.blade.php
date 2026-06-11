        <div x-show="tab === 'items'" x-cloak>
        <section class="w-full overflow-hidden rounded-3xl bg-white p-5 shadow-sm border border-slate-200">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Retailers &amp; Items</h2>
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
                                                    {{ $isCustomerSelfPurchase ? 'Bought by customer' : ($item->purchase_remaining_qty > 0 ? 'Pending purchase' : 'Purchased') }}
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
        </section>

        </div>
