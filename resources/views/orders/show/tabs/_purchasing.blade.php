        <div x-show="tab === 'purchase_status'" x-cloak class="space-y-5">
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Purchase status</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">Retailer references and buying progress</h2>
                        <p class="mt-1 text-sm text-slate-500">This is a status view only. The dedicated purchasing module can handle the actual buying workflow later.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700 ring-1 ring-indigo-100">{{ $purchases->count() }} purchase record{{ $purchases->count() === 1 ? '' : 's' }}</span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ (int) ($progress['purchased_qty'] ?? 0) }}/{{ (int) ($progress['item_qty'] ?? 0) }} purchased</span>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-3">
                    <div class="rounded-3xl bg-slate-50 p-4 ring-1 ring-slate-100">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Overall status</p>
                        <p class="mt-1 text-xl font-black text-slate-950">{{ $purchaseStatusLabel }}</p>
                        <p class="mt-1 text-xs font-semibold text-slate-500">Simple staff-readable purchase position.</p>
                    </div>
                    <div class="rounded-3xl bg-emerald-50 p-4 ring-1 ring-emerald-100">
                        <p class="text-[10px] font-black uppercase tracking-wide text-emerald-700">Purchased quantity</p>
                        <p class="mt-1 text-xl font-black text-emerald-700">{{ (int) ($progress['purchased_qty'] ?? 0) }}</p>
                        <p class="mt-1 text-xs font-semibold text-emerald-700">Items already marked ordered/purchased.</p>
                    </div>
                    <div class="rounded-3xl {{ (int) ($progress['remaining_purchase_qty'] ?? 0) > 0 ? 'bg-amber-50 ring-amber-100' : 'bg-slate-50 ring-slate-100' }} p-4 ring-1">
                        <p class="text-[10px] font-black uppercase tracking-wide {{ (int) ($progress['remaining_purchase_qty'] ?? 0) > 0 ? 'text-amber-700' : 'text-slate-400' }}">Still to purchase</p>
                        <p class="mt-1 text-xl font-black {{ (int) ($progress['remaining_purchase_qty'] ?? 0) > 0 ? 'text-amber-700' : 'text-slate-500' }}">{{ (int) ($progress['remaining_purchase_qty'] ?? 0) }}</p>
                        <p class="mt-1 text-xs font-semibold {{ (int) ($progress['remaining_purchase_qty'] ?? 0) > 0 ? 'text-amber-700' : 'text-slate-500' }}">No buying tools here yet — status only.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Retailer purchase records</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">References, dates and notes</h2>
                    </div>
                </div>

                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                    <div class="hidden grid-cols-[minmax(0,1.4fr)_110px_minmax(0,1fr)_130px_120px_minmax(0,1fr)] gap-3 bg-slate-50 px-4 py-3 text-xs font-black uppercase tracking-wide text-slate-400 lg:grid">
                        <div>Item</div>
                        <div>Status</div>
                        <div>Retailer ref</div>
                        <div>Purchased</div>
                        <div>Cost</div>
                        <div>Notes</div>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($purchases as $purchase)
                            <div class="grid gap-3 px-4 py-4 text-sm lg:grid-cols-[minmax(0,1.4fr)_110px_minmax(0,1fr)_130px_120px_minmax(0,1fr)] lg:items-center">
                                <div>
                                    <p class="font-black text-slate-950">{{ $purchase->item_name }}</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">Qty {{ $purchase->qty }} @if($purchase->marketplace_seller) · {{ $purchase->marketplace_seller }} @endif</p>
                                </div>
                                <div>
                                    <span class="rounded-full {{ in_array((string) $purchase->status, ['purchased', 'ordered', 'received'], true) ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100' }} px-3 py-1 text-xs font-black ring-1">
                                        {{ Str::of((string) ($purchase->status ?: 'pending'))->replace('_', ' ')->title() }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $purchase->retailer_order_reference ?: 'No reference yet' }}</p>
                                    @if ($purchase->retailer_order_reference)
                                        <button type="button" data-copy-value="{{ $purchase->retailer_order_reference }}" class="mt-1 text-xs font-black text-indigo-600 hover:text-indigo-700">Copy ref</button>
                                    @endif
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $purchase->ordered_at ? \Carbon\Carbon::parse($purchase->ordered_at)->format('d M Y') : 'Not recorded' }}</p>
                                    @if ($purchase->expected_uk_hub_at)
                                        <p class="mt-1 text-xs text-slate-500">Hub: {{ \Carbon\Carbon::parse($purchase->expected_uk_hub_at)->format('d M') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <p class="font-black text-slate-950">£{{ number_format((float) ($purchase->purchase_line_total ?? 0), 2) }}</p>
                                    @if ($purchase->purchase_unit_price)
                                        <p class="mt-1 text-xs text-slate-500">Unit £{{ number_format((float) $purchase->purchase_unit_price, 2) }}</p>
                                    @endif
                                </div>
                                <div>
                                    <p class="line-clamp-2 text-xs font-semibold text-slate-500">{{ $purchase->problem_notes ?: ($purchase->internal_notes ?: ($purchase->note ?: '—')) }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="px-4 py-8 text-center text-sm font-semibold text-slate-500">No purchase records yet. Item-level purchase status is still visible in the Items tab.</div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
