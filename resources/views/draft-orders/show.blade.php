<x-app-layout>
    <x-slot name="header">Draft #{{ $draft->draft_number ?: $draft->id }}</x-slot>

    @php
        $customerName = trim(($draft->first_name ?? '') . ' ' . ($draft->last_name ?? '')) ?: ($draft->company_name ?: 'Unknown customer');
        $qtyTotal = $items->sum('qty');
        $groupedItems = $items->groupBy(fn ($item) => $item->retailer_id ?: 0);
        $summaryByRetailer = $retailerSummaries->keyBy('retailer_id');
        $activeTab = request('tab', 'products');
    @endphp

    <div class="space-y-5" x-data="{ tab: '{{ $activeTab }}', itemFilter: '' }">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700">
                <strong>Something needs checking:</strong> {{ $errors->first() }}
            </div>
        @endif

        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-5 border-b border-slate-100 p-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <a href="{{ route('draft-orders.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-900">← Back to drafts</a>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-black tracking-tight text-slate-950">{{ $customerName }}</h1>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-slate-600">Draft #{{ $draft->draft_number ?: $draft->id }}</span>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-amber-700">{{ $draft->status ?: 'draft' }}</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-500">
                        Source: {{ $draft->request_ref ? 'Order request ' . $draft->request_ref : 'Manual draft' }}
                        · Created {{ $draft->created_at ? \Carbon\Carbon::parse($draft->created_at)->format('d M Y, H:i') : '—' }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" disabled class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-400">Duplicate soon</button>
                    @if ($draft->finalized_order_id)
                        <a href="{{ route('orders.show', $draft->finalized_order_id) }}" class="rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">Open order #{{ $draft->finalized_order_number }}</a>
                    @else
                        <button type="button" disabled class="rounded-2xl bg-purple-600/60 px-4 py-2.5 text-sm font-bold text-white">Finalise to Order soon</button>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-2 p-4">
                @foreach ([
                    'products' => 'Products',
                    'customer' => 'Customer',
                    'notes' => 'Notes',
                    'fees' => 'Dabba fees',
                    'activity' => 'Activity',
                ] as $key => $label)
                    <button type="button" @click="tab = '{{ $key }}'" class="rounded-2xl px-4 py-2.5 text-sm font-bold transition" :class="tab === '{{ $key }}' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-12">
            <main class="space-y-5 xl:col-span-8 2xl:col-span-9">
                <section x-show="tab === 'products'" x-cloak class="space-y-5">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-lg font-black text-slate-900">Add product</h2>
                                <p class="mt-1 text-sm text-slate-500">Paste a product URL or enter a code. Retailer can be corrected manually.</p>
                            </div>
                            <button type="button" disabled class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-400">Add multiple soon</button>
                        </div>

                        <form method="POST" action="{{ route('draft-orders.items.store', $draft->id) }}" class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-12">
                            @csrf
                            <div class="lg:col-span-4">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Product URL</label>
                                <input name="url" placeholder="https://..." class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            </div>
                            <div class="lg:col-span-3">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Retailer</label>
                                <select name="retailer_id" required class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                    <option value="">Choose retailer...</option>
                                    @foreach ($retailers as $retailer)
                                        <option value="{{ $retailer->id }}">{{ $retailer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="lg:col-span-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Qty</label>
                                <input name="qty" type="number" min="1" value="1" class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            </div>
                            <div class="lg:col-span-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Unit price £</label>
                                <input name="unit_price" type="number" min="0" step="0.01" value="0.00" class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            </div>
                            <div class="lg:col-span-1 lg:pt-7">
                                <button type="submit" class="w-full rounded-2xl bg-purple-600 px-4 py-2.5 text-sm font-black text-white hover:bg-purple-700">Add</button>
                            </div>
                            <div class="lg:col-span-7">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Description / item notes</label>
                                <textarea name="description" rows="2" placeholder="Item details..." class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500"></textarea>
                            </div>
                            <div class="lg:col-span-3">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Product code / SKU</label>
                                <input name="product_code" placeholder="Optional SKU" class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            </div>
                            <div class="lg:col-span-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Item delivery £</label>
                                <input name="item_retailer_delivery_fee" type="number" min="0" step="0.01" value="0.00" class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            </div>
                        </form>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Filter visible items</label>
                        <div class="mt-2 flex flex-col gap-3 lg:flex-row">
                            <input x-model.debounce.250ms="itemFilter" placeholder="Search retailer, code, description..." class="min-w-0 flex-1 rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="rounded-2xl bg-slate-50 px-4 py-2.5 text-sm font-bold text-slate-700 ring-1 ring-slate-200">Focus URL</button>
                                <button type="button" class="rounded-2xl bg-slate-50 px-4 py-2.5 text-sm font-bold text-slate-700 ring-1 ring-slate-200">Unassigned only</button>
                                <button type="button" class="rounded-2xl bg-slate-50 px-4 py-2.5 text-sm font-bold text-slate-700 ring-1 ring-slate-200">Unchecked only</button>
                            </div>
                        </div>
                    </div>

                    @forelse ($groupedItems as $retailerId => $retailerItems)
                        @php
                            $firstItem = $retailerItems->first();
                            $retailerSummary = $summaryByRetailer->get($retailerId);
                            $retailerSearch = strtolower(($firstItem->retailer_name ?? '') . ' ' . $retailerItems->pluck('description')->join(' ') . ' ' . $retailerItems->pluck('product_code')->join(' ') . ' ' . $retailerItems->pluck('url')->join(' '));
                        @endphp
                        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" x-show="itemFilter === '' || '{{ addslashes($retailerSearch) }}'.includes(itemFilter.toLowerCase())">
                            <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/80 p-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-xl shadow-sm ring-1 ring-slate-200">{{ strtoupper(mb_substr($firstItem->retailer_name ?: '?', 0, 1)) }}</div>
                                    <div>
                                        <h3 class="font-black text-slate-900">{{ $firstItem->retailer_name ?: 'Unknown retailer' }}</h3>
                                        <div class="mt-1 flex flex-wrap gap-2 text-xs font-bold">
                                            <span class="rounded-full bg-white px-2.5 py-1 text-slate-600 ring-1 ring-slate-200">Items: {{ $retailerItems->count() }}</span>
                                            <span class="rounded-full bg-white px-2.5 py-1 text-slate-600 ring-1 ring-slate-200">Subtotal £{{ number_format($retailerSummary->retailer_subtotal ?? $retailerItems->sum('line_subtotal'), 2) }}</span>
                                            <span class="rounded-full bg-white px-2.5 py-1 text-slate-600 ring-1 ring-slate-200">Delivery £{{ number_format($retailerSummary->retailer_delivery_fee_total ?? 0, 2) }}</span>
                                            <span class="rounded-full bg-purple-100 px-2.5 py-1 text-purple-700">Dabba £{{ number_format($retailerSummary->dabba_fee ?? 0, 2) }}</span>
                                            <span class="rounded-full bg-slate-900 px-2.5 py-1 text-white">£{{ number_format($retailerSummary->retailer_grand_total ?? $retailerItems->sum('line_total'), 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-sm font-black uppercase tracking-widest text-slate-500">Retailer group</div>
                            </div>

                            <div class="space-y-4 p-4">
                                @foreach ($retailerItems as $item)
                                    <form method="POST" action="{{ route('draft-orders.items.update', [$draft->id, $item->id]) }}" class="rounded-3xl border border-slate-200 p-4">
                                        @csrf
                                        @method('PATCH')
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="flex flex-wrap gap-2 text-xs font-bold">
                                                <span class="rounded-full bg-purple-100 px-3 py-1 text-purple-700">Item {{ $loop->iteration }}</span>
                                                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">Qty: {{ $item->qty }}</span>
                                                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">Unit: £{{ number_format($item->unit_price, 2) }}</span>
                                                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">Unchecked</span>
                                            </div>
                                            <div class="flex gap-2">
                                                <button type="submit" class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">Save</button>
                                            </div>
                                        </div>

                                        <input type="hidden" name="retailer_id" value="{{ $item->retailer_id }}">
                                        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-12">
                                            <div class="lg:col-span-7">
                                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Description</label>
                                                <textarea name="description" rows="2" class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">{{ $item->description }}</textarea>
                                            </div>
                                            <div class="lg:col-span-5 grid grid-cols-3 gap-3">
                                                <div>
                                                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Quantity</label>
                                                    <input name="qty" type="number" min="1" value="{{ $item->qty }}" class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                                </div>
                                                <div>
                                                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Unit £</label>
                                                    <input name="unit_price" type="number" min="0" step="0.01" value="{{ number_format($item->unit_price, 2, '.', '') }}" class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                                </div>
                                                <div>
                                                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Delivery £</label>
                                                    <input name="item_retailer_delivery_fee" type="number" min="0" step="0.01" value="{{ number_format($item->item_retailer_delivery_fee ?? $item->item_delivery_fee ?? 0, 2, '.', '') }}" class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                                </div>
                                            </div>
                                            <div class="lg:col-span-4">
                                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Product code / SKU</label>
                                                <input name="product_code" value="{{ $item->product_code }}" placeholder="Enter code..." class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                                <input type="hidden" name="sku" value="{{ $item->sku }}">
                                            </div>
                                            <div class="lg:col-span-8">
                                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Product URL</label>
                                                <div class="mt-2 flex gap-2">
                                                    <input name="url" value="{{ $item->url }}" class="min-w-0 flex-1 rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                                    @if ($item->url)
                                                        <a href="{{ $item->url }}" target="_blank" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">↗</a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </form>

                                    <form method="POST" action="{{ route('draft-orders.items.destroy', [$draft->id, $item->id]) }}" onsubmit="return confirm('Remove this draft item?')" class="px-4">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-bold text-rose-600 hover:text-rose-700">Remove item</button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">No items in this draft yet.</div>
                    @endforelse
                </section>

                <section x-show="tab === 'customer'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black text-slate-900">Customer</h2>
                    <p class="mt-1 text-sm text-slate-500">Customer editing will be connected next. For now this confirms the draft is linked to the correct customer.</p>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase tracking-widest text-slate-500">Name</p><p class="mt-1 font-bold text-slate-900">{{ $customerName }}</p></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase tracking-widest text-slate-500">Customer ID</p><p class="mt-1 font-bold text-slate-900">{{ $draft->customer_id }}</p></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase tracking-widest text-slate-500">Phone</p><p class="mt-1 font-bold text-slate-900">{{ $customerDetails['phone'] ?? '—' }}</p></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase tracking-widest text-slate-500">Email</p><p class="mt-1 font-bold text-slate-900">{{ $customerDetails['email'] ?? '—' }}</p></div>
                    </div>
                </section>

                <section x-show="tab === 'notes'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-xl font-black text-slate-900">Internal notes</h2>
                    </div>
                    <form method="POST" action="{{ route('draft-orders.notes.store', $draft->id) }}" class="mt-4">
                        @csrf
                        <textarea name="body" rows="3" placeholder="Add internal note..." class="w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500"></textarea>
                        <div class="mt-3 flex justify-end"><button type="submit" class="rounded-2xl bg-purple-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-purple-700">Add note</button></div>
                    </form>
                    <div class="mt-5 space-y-3">
                        @forelse ($notes as $note)
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2"><p class="text-sm font-bold text-slate-900">{{ $note->title ?: ucfirst(str_replace('_', ' ', $note->type)) }}</p><p class="text-xs text-slate-400">{{ $note->author_name ?: 'System' }} · {{ $note->created_at ? \Carbon\Carbon::parse($note->created_at)->format('d M Y H:i') : '' }}</p></div>
                                <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $note->body }}</p>
                            </div>
                        @empty
                            <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">No notes yet.</p>
                        @endforelse
                    </div>
                </section>

                <section x-show="tab === 'fees'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black text-slate-900">Dabba fees</h2>
                    <p class="mt-1 text-sm text-slate-500">Fee policy is calculated per retailer. Use draft settings on the right to disable fees where needed.</p>
                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        @foreach ($retailerSummaries as $summary)
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <p class="font-black text-slate-900">{{ $summary->retailer_name ?: 'Unknown retailer' }}</p>
                                <div class="mt-3 space-y-1 text-sm"><div class="flex justify-between"><span>Rate</span><strong>{{ number_format($summary->dabba_fee_rate ?? 20, 2) }}%</strong></div><div class="flex justify-between"><span>Minimum</span><strong>£{{ number_format($summary->dabba_fee_min ?? 10, 2) }}</strong></div><div class="flex justify-between border-t pt-2"><span>Fee</span><strong>£{{ number_format($summary->dabba_fee ?? 0, 2) }}</strong></div></div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section x-show="tab === 'activity'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black text-slate-900">Activity</h2>
                    <div class="mt-5 space-y-3">
                        @forelse ($notes as $note)
                            <div class="flex gap-3"><div class="mt-1 h-3 w-3 rounded-full bg-emerald-400"></div><div><p class="text-sm font-bold text-slate-900">{{ $note->title ?: ucfirst(str_replace('_', ' ', $note->type)) }}</p><p class="text-xs text-slate-500">{{ $note->created_at ? \Carbon\Carbon::parse($note->created_at)->format('d M Y H:i') : '' }} · {{ $note->author_name ?: 'System' }}</p></div></div>
                        @empty
                            <p class="text-sm text-slate-500">No activity yet.</p>
                        @endforelse
                    </div>
                </section>
            </main>

            <aside class="xl:col-span-4 2xl:col-span-3">
                <div class="sticky top-24 space-y-4">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-black text-slate-900">Order summary</h3>
                        <div class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between"><span class="text-slate-500">Items subtotal</span><strong>£{{ number_format($draft->items_subtotal ?? 0, 2) }}</strong></div>
                            <div class="flex justify-between"><span class="text-slate-500">Delivery fees</span><strong>£{{ number_format($draft->retailer_delivery_total ?? 0, 2) }}</strong></div>
                            <div class="flex justify-between"><span class="text-slate-500">Dabba fees</span><strong>£{{ number_format($draft->dabba_fee_total ?? 0, 2) }}</strong></div>
                            <div class="flex justify-between border-t border-slate-200 pt-4 text-xl"><span class="font-black text-slate-900">Total</span><strong class="text-slate-950">£{{ number_format($draft->grand_total ?? 0, 2) }}</strong></div>
                        </div>
                        <div class="mt-4 inline-flex rounded-full bg-slate-50 px-3 py-1 text-xs font-black text-slate-700 ring-1 ring-slate-200">Qty {{ $qtyTotal }}</div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between"><h3 class="text-base font-black text-slate-900">Customer</h3><button type="button" disabled class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-400">Edit soon</button></div>
                        <div class="mt-4 space-y-2 text-sm text-slate-600">
                            <p class="font-bold text-slate-900">{{ $customerName }}</p>
                            <p>{{ $customerDetails['phone'] ?? 'No phone shown' }}</p>
                            <p>{{ $customerDetails['email'] ?? 'No email shown' }}</p>
                            @if (!empty($customerDetails['address']))<p class="whitespace-pre-line">{{ $customerDetails['address'] }}</p>@endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('draft-orders.update', $draft->id) }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        @csrf
                        @method('PATCH')
                        <h3 class="text-base font-black text-slate-900">Draft settings</h3>
                        <div class="mt-4 space-y-3">
                            <div><label class="text-xs font-black uppercase tracking-widest text-slate-500">Status</label><select name="status" class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">@foreach ($statusOptions as $status)<option value="{{ $status }}" @selected($draft->status === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></div>
                            <div><label class="text-xs font-black uppercase tracking-widest text-slate-500">Fee mode</label><select name="fee_mode" class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500"><option value="standard" @selected($draft->fee_mode === 'standard')>Standard fee</option><option value="fee_disabled" @selected($draft->fee_mode === 'fee_disabled')>Fee disabled</option></select></div>
                            <label class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3 text-sm font-bold text-slate-700"><input type="checkbox" name="home_delivery_requested" value="1" class="rounded border-slate-300 text-purple-600 focus:ring-purple-500" @checked($draft->home_delivery_requested)>Home delivery requested</label>
                            <button type="submit" class="w-full rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800">Save settings</button>
                        </div>
                    </form>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-black text-slate-900">Actions</h3>
                        <div class="mt-4 space-y-2">
                            <button type="button" disabled class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-left text-sm font-bold text-slate-400">Duplicate draft soon</button>
                            <button type="button" disabled class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-left text-sm font-bold text-slate-400">Move to another request soon</button>
                            <button type="button" disabled class="w-full rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-left text-sm font-bold text-rose-400">Delete draft soon</button>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
