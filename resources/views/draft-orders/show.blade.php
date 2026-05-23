<x-app-layout>
    <x-slot name="header">Draft #{{ $draft->draft_number ?: $draft->id }}</x-slot>

    @php
        $customerName = trim(($draft->first_name ?? '') . ' ' . ($draft->last_name ?? '')) ?: ($draft->company_name ?: 'Unknown customer');
        $qtyTotal = (int) $items->sum('qty');
        $activeTab = request('tab', 'products');
        $lastAddedItemId = (int) session('last_added_item_id', 0);
        $summaryByRetailer = $retailerSummaries->keyBy('retailer_id');
        $groupedItems = $items->groupBy(fn ($item) => $item->retailer_id ?: 0);
        $retailerRows = $retailerSummaries->keyBy('retailer_id');
        $money = fn ($value) => '£' . number_format((float) ($value ?? 0), 2);
        $draftNo = $draft->draft_number ?: $draft->id;
    @endphp

    <style>
            [x-cloak]{display:none!important}
            .draft-ui input:not([type="checkbox"]),
            .draft-ui textarea,
            .draft-ui select{
                border:1px solid #cbd5e1!important;
                border-radius:14px!important;
                background:#fff!important;
                padding:11px 14px!important;
                min-height:44px;
                box-shadow:0 1px 2px rgba(15,23,42,.04)!important;
            }
            .draft-ui textarea{min-height:76px;line-height:1.45}
            .draft-ui input:focus,.draft-ui textarea:focus,.draft-ui select:focus{
                outline:2px solid transparent!important;
                border-color:#9333ea!important;
                box-shadow:0 0 0 3px rgba(147,51,234,.18)!important;
            }
            .draft-ui .field-label{display:block;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:#64748b;margin-bottom:7px}
            .draft-ui .input-clean{width:100%;border:1px solid #cbd5e1!important;border-radius:14px!important;background:#fff!important;padding:11px 13px!important;min-height:44px;box-shadow:0 1px 2px rgba(15,23,42,.04)!important}
            .draft-ui .basket-head{display:none}
            .draft-ui .basket-row{display:block;padding:18px;border-bottom:1px solid #e2e8f0}
            .draft-ui .basket-row:last-child{border-bottom:0}
            .draft-ui .mini-input{width:100%;border:1px solid #cbd5e1!important;border-radius:12px!important;padding:9px 10px!important;min-height:40px;font-size:14px;font-weight:700;background:#fff!important}
            .draft-ui .row-action{display:inline-flex;align-items:center;justify-content:center;border-radius:12px;border:1px solid #cbd5e1;background:#fff;padding:9px 11px;font-size:13px;font-weight:900;white-space:nowrap}
            .draft-ui .row-action-danger{border-color:#fecaca;color:#dc2626}
            .draft-ui .item-main-grid{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:14px;align-items:start}
            .draft-ui .item-fields-grid{display:grid;grid-template-columns:90px 130px 150px 135px minmax(150px,1fr);gap:12px;align-items:end;margin-top:14px}
            .draft-ui .row-label{display:block;margin-bottom:6px;font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:#64748b}
            .draft-ui .item-actions{display:flex;justify-content:flex-end;gap:8px;align-items:center}
            @media(max-width:1450px){.draft-ui .item-fields-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.draft-ui .item-actions{justify-content:flex-start}}
            @media(max-width:760px){.draft-ui .item-main-grid{grid-template-columns:1fr}.draft-ui .item-fields-grid{grid-template-columns:1fr}}
            .draft-ui details > summary{cursor:pointer;list-style:none}
            .draft-ui details > summary::-webkit-details-marker{display:none}
        </style>

    <div
        class="draft-ui space-y-4"
        x-data="draftWorkspace({
            detectUrl: '{{ route('draft-orders.detect-retailer') }}',
            quickRetailerUrl: '{{ route('draft-orders.retailers.quick-store') }}',
            csrf: '{{ csrf_token() }}',
            initialTab: '{{ $activeTab }}'
        })"
        x-init="boot()"
    >
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

        {{-- Header --}}
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-5 px-6 py-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <a href="{{ route('draft-orders.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-950">← Back to drafts</a>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-black tracking-tight text-slate-950">Draft #{{ $draftNo }}</h1>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black uppercase tracking-wide text-amber-700">{{ $draft->status ?: 'open' }}</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-500">
                        {{ $customerName }}
                        @if ($draft->request_ref)
                            <span class="mx-1">•</span> Source: Order request #{{ $draft->request_ref }}
                        @endif
                        @if ($draft->created_at)
                            <span class="mx-1">•</span> Created {{ \Carbon\Carbon::parse($draft->created_at)->format('d M Y, H:i') }}
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="button" disabled class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-400">Duplicate soon</button>
                    <button type="button" disabled class="rounded-2xl bg-purple-600 px-5 py-3 text-sm font-black text-white opacity-80">Finalise to Order soon</button>
                </div>
            </div>
            <div class="flex gap-1 border-t border-slate-100 px-5 py-3">
                @foreach ([['products','Products','🛒'],['customer','Customer','👤'],['notes','Notes','📝'],['fees','Dabba fees','🏷️'],['activity','Activity','〽️']] as [$key,$label,$icon])
                    <button type="button" @click="tab='{{ $key }}'" :class="tab === '{{ $key }}' ? 'border-purple-600 text-purple-700 bg-purple-50' : 'border-transparent text-slate-600 hover:bg-slate-50'" class="rounded-2xl border px-4 py-2.5 text-sm font-black transition">
                        <span class="mr-1">{{ $icon }}</span>{{ $label }}
                    </button>
                @endforeach
            </div>
        </section>

        <div class="grid gap-4 2xl:grid-cols-[minmax(0,1fr)_340px]">
            <main class="space-y-4">
                <section x-show="tab === 'products'" x-cloak class="space-y-4">
                    {{-- Better add product panel --}}
                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Add product</h2>
                                <p class="mt-1 text-sm text-slate-500">Paste the URL, confirm the retailer, enter quantity and unit price. Delivery fees are adjusted in the basket rows below.</p>
                            </div>
                            <button type="button" disabled class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-400">Add multiple soon</button>
                        </div>

                        <form method="POST" action="{{ route('draft-orders.items.store', $draft->id) }}" class="space-y-4">
                            @csrf
                            <div class="grid gap-4 2xl:grid-cols-[minmax(0,1fr)_260px_110px_150px] 2xl:items-end">
                                <div>
                                    <label class="field-label">Product URL / code</label>
                                    <div class="flex gap-2">
                                        <input name="url" x-model="newItem.url" @blur.debounce.300ms="detectRetailer()" placeholder="Paste full product URL, Amazon short link, or product code" class="input-clean min-w-0 flex-1 text-sm">
                                        <button type="button" @click="detectRetailer()" class="row-action shrink-0" title="Detect retailer">Detect</button>
                                    </div>
                                </div>
                                <div>
                                    <label class="field-label">Retailer</label>
                                    <select name="retailer_id" x-model="newItem.retailerId" class="input-clean text-sm">
                                        <option value="">Choose retailer</option>
                                        @foreach ($retailers as $retailer)
                                            <option value="{{ $retailer->id }}">{{ $retailer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="field-label">Quantity</label>
                                    <input name="qty" x-model="newItem.qty" type="number" min="1" value="1" class="input-clean text-sm">
                                </div>
                                <div>
                                    <label class="field-label">Unit price £</label>
                                    <input name="unit_price" x-model="newItem.unitPrice" @focus="if (String(newItem.unitPrice) === '0' || String(newItem.unitPrice) === '0.00') newItem.unitPrice = ''" @blur="if (String(newItem.unitPrice).trim() === '') newItem.unitPrice = '0.00'" type="number" min="0" step="0.01" class="input-clean text-sm">
                                </div>
                            </div>

                            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_240px_170px] xl:items-end">
                                <div>
                                    <label class="field-label">Description / item notes</label>
                                    <textarea name="description" rows="2" placeholder="Item details, colour, size, customer notes..." class="input-clean text-sm"></textarea>
                                </div>
                                <div>
                                    <label class="field-label">Product code / SKU</label>
                                    <input name="product_code" placeholder="Optional SKU" class="input-clean text-sm">
                                    <input type="hidden" name="sku" value="">
                                </div>
                                <button type="submit" class="rounded-2xl bg-purple-600 px-6 py-3 text-sm font-black text-white shadow-sm hover:bg-purple-700 whitespace-nowrap min-h-[46px]">Add item</button>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-600" x-text="detectMessage || 'Retailer detection is automatic. If a short URL resolves, the full product URL will be saved.'"></div>
                        </form>
                    </section>

                    {{-- Basket --}}
                    <section class="overflow-visible rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-xl font-black text-slate-950">Basket items ({{ $items->count() }})</h2>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Newest item at the top</span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">Inline editing: quantity, unit price and marketplace/seller delivery are visible on every row. Retailer totals update after save.</p>
                            </div>
                            <div class="flex gap-2">
                                <input x-model="basketSearch" placeholder="Search items..." class="input-clean w-64 text-sm">
                                <button type="button" disabled class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-400">↕ Reorder</button>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-100">
                            @forelse ($groupedItems as $retailerId => $retailerItems)
                                @php
                                    $first = $retailerItems->first();
                                    $summary = $retailerRows->get($retailerId);
                                    $retailerName = $first->retailer_name ?: 'Unknown retailer';
                                    $initial = strtoupper(substr($retailerName, 0, 1));
                                @endphp
                                <div class="p-5" x-data="{ open: true }">
                                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <button type="button" @click="open=!open" class="flex min-w-0 items-center gap-3 text-left">
                                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-slate-950 text-lg font-black text-white">{{ $initial }}</span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-lg font-black text-slate-950">{{ $retailerName }}</span>
                                                <span class="mt-1 inline-flex rounded-full bg-purple-100 px-3 py-1 text-xs font-black text-purple-700">{{ $retailerItems->count() }} {{ Str::plural('item', $retailerItems->count()) }}</span>
                                            </span>
                                        </button>
                                        <div class="flex flex-wrap items-center gap-5 text-sm">
                                            <span class="text-slate-500">Goods <strong class="text-slate-950">{{ $money($summary->retailer_subtotal ?? $retailerItems->sum('line_subtotal')) }}</strong></span>
                                            <span class="text-slate-500">Seller delivery <strong class="text-slate-950">{{ $money($summary->retailer_delivery_fee_total ?? $retailerItems->sum('item_retailer_delivery_fee')) }}</strong></span>
                                            <span class="text-slate-500">Dabba fee <strong class="text-slate-950">{{ $money($summary->dabba_fee ?? 0) }}</strong></span>
                                            <span class="text-slate-500">Retailer total <strong class="text-slate-950">{{ $money($summary->retailer_grand_total ?? 0) }}</strong></span>
                                            <button type="button" @click="open=!open" class="text-slate-500" x-text="open ? '⌃' : '⌄'"></button>
                                        </div>
                                    </div>

                                    <div x-show="open" x-collapse class="mt-3 overflow-visible rounded-2xl border border-slate-200 bg-white">
                                        <div class="border-b border-slate-100 bg-slate-50 px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-500">
                                            Items for {{ $retailerName }} — edit quantity, unit price and marketplace/seller delivery directly in each row
                                        </div>
                                        @foreach ($retailerItems as $item)
                                            @php
                                                $lineTotal = (float) ($item->line_total ?? (($item->qty ?? 1) * ($item->unit_price ?? 0) + ($item->item_retailer_delivery_fee ?? 0)));
                                                $title = trim((string) ($item->description ?: 'New item'));
                                                $shortUrl = $item->url ? preg_replace('/^https?:\/\/www\./', '', $item->url) : '';
                                                $isJustAdded = $lastAddedItemId === (int) $item->id;
                                            @endphp
                                            <form method="POST" action="{{ route('draft-orders.items.update', [$draft->id, $item->id]) }}" id="row-form-{{ $item->id }}" class="basket-row {{ $isJustAdded ? 'bg-purple-50/70' : 'bg-white' }}" x-data="{
                                                qty: {{ (int) $item->qty }},
                                                unit: {{ number_format((float) $item->unit_price, 2, '.', '') }},
                                                delivery: {{ number_format((float) ($item->item_retailer_delivery_fee ?? $item->item_delivery_fee ?? 0), 2, '.', '') }},
                                                get total(){ return ((parseFloat(this.qty)||0) * (parseFloat(this.unit)||0) + (parseFloat(this.delivery)||0)).toFixed(2); }
                                            }" x-show="matchesSearch($el)">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="retailer_id" value="{{ $item->retailer_id }}">
                                                <input type="hidden" name="url" value="{{ $item->url }}">
                                                <input type="hidden" name="sku" value="{{ $item->sku }}">
                                                <div class="item-main-grid">
                                                    <div class="min-w-0">
                                                        @if ($isJustAdded)
                                                            <span class="mb-2 inline-flex rounded bg-purple-600 px-2 py-1 text-[10px] font-black uppercase tracking-widest text-white">Just added</span>
                                                        @endif
                                                        <label class="row-label">Item description</label>
                                                        <textarea name="description" rows="2" class="mini-input min-h-[64px] text-sm font-semibold leading-5">{{ $item->description }}</textarea>
                                                        <div class="mt-2 grid gap-2 md:grid-cols-[minmax(0,1fr)_180px]">
                                                            @if ($shortUrl)
                                                                <a href="{{ $item->url }}" target="_blank" class="truncate rounded-xl bg-purple-50 px-3 py-2 text-xs font-bold text-purple-700 hover:underline">{{ $shortUrl }} ↗</a>
                                                            @else
                                                                <span class="rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-400">No URL</span>
                                                            @endif
                                                            <input name="product_code" value="{{ $item->product_code }}" placeholder="SKU/code" class="mini-input text-xs">
                                                        </div>
                                                    </div>
                                                    <div class="item-actions">
                                                        <button type="submit" class="row-action bg-slate-950 text-white border-slate-950 hover:bg-slate-800">Save row</button>
                                                        <button type="submit" formaction="{{ route('draft-orders.items.destroy', [$draft->id, $item->id]) }}" formmethod="POST" onclick="event.preventDefault(); if(confirm('Remove {{ addslashes(Str::limit($title, 80)) }}?')) { const f=this.closest('form'); let m=f.querySelector('input[name=_method]'); m.value='DELETE'; f.submit(); }" class="row-action row-action-danger hover:bg-rose-50">Remove</button>
                                                    </div>
                                                </div>

                                                <div class="item-fields-grid">
                                                    <div>
                                                        <label class="row-label">Quantity</label>
                                                        <input name="qty" x-model.number="qty" type="number" min="1" class="mini-input">
                                                    </div>
                                                    <div>
                                                        <label class="row-label">Unit price £</label>
                                                        <input name="unit_price" x-model.number="unit" @focus="if (Number(unit) === 0) unit = ''" @blur="if (unit === '' || unit === null) unit = 0" type="number" min="0" step="0.01" class="mini-input">
                                                    </div>
                                                    <div>
                                                        <label class="row-label">Seller delivery £</label>
                                                        <input name="item_retailer_delivery_fee" x-model.number="delivery" type="number" min="0" step="0.01" class="mini-input">
                                                    </div>
                                                    <div>
                                                        <label class="row-label">Line total</label>
                                                        <input type="text" :value="'£' + total" readonly class="mini-input bg-slate-50 font-black">
                                                    </div>
                                                    <div>
                                                        <label class="row-label">Status</label>
                                                        <span class="inline-flex min-h-[40px] w-full items-center justify-center rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-black text-slate-700">● Unchecked</span>
                                                    </div>
                                                    <div class="text-xs font-semibold text-slate-400">
                                                        <label class="row-label">Formula</label>
                                                        Qty × unit price + seller delivery
                                                    </div>
                                                </div>
                                            </form>
                                        @endforeach
                                        <div class="bg-slate-50 px-4 py-3">
                                            <button type="button" disabled class="text-sm font-black text-purple-600">＋ Add item to {{ $retailerName }} soon</button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-10 text-center text-slate-500">No items in this draft yet.</div>
                            @endforelse
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 p-5">
                            <button type="button" disabled class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-400">Bulk actions soon</button>
                            <div class="text-sm font-semibold text-slate-500">{{ $items->count() }} items · Last updated {{ $draft->updated_at ? \Carbon\Carbon::parse($draft->updated_at)->diffForHumans() : 'recently' }}</div>
                            <button type="button" disabled class="rounded-2xl border border-rose-200 px-4 py-2.5 text-sm font-black text-rose-400">Clear all items soon</button>
                        </div>
                    </section>
                </section>

                <section x-show="tab === 'customer'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black text-slate-950">Customer</h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase tracking-widest text-slate-500">Name</p><p class="mt-1 font-bold text-slate-900">{{ $customerName }}</p></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase tracking-widest text-slate-500">Customer ID</p><p class="mt-1 font-bold text-slate-900">{{ $draft->customer_id }}</p></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase tracking-widest text-slate-500">Phone</p><p class="mt-1 font-bold text-slate-900">{{ $customerDetails['phone'] ?? '—' }}</p></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase tracking-widest text-slate-500">Email</p><p class="mt-1 font-bold text-slate-900">{{ $customerDetails['email'] ?? '—' }}</p></div>
                    </div>
                </section>

                <section x-show="tab === 'notes'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black text-slate-950">Internal notes</h2>
                    <form method="POST" action="{{ route('draft-orders.notes.store', $draft->id) }}" class="mt-4">
                        @csrf
                        <textarea name="body" rows="3" placeholder="Add internal note..." class="w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500"></textarea>
                        <div class="mt-3 flex justify-end"><button type="submit" class="rounded-2xl bg-purple-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-purple-700">Add note</button></div>
                    </form>
                    <div class="mt-5 space-y-3">
                        @forelse ($notes as $note)
                            <div class="rounded-2xl bg-slate-50 p-4"><div class="flex flex-wrap items-center justify-between gap-2"><p class="text-sm font-bold text-slate-900">{{ $note->title ?: ucfirst(str_replace('_', ' ', $note->type)) }}</p><p class="text-xs text-slate-400">{{ $note->author_name ?: 'System' }} · {{ $note->created_at ? \Carbon\Carbon::parse($note->created_at)->format('d M Y H:i') : '' }}</p></div><p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $note->body }}</p></div>
                        @empty
                            <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">No notes yet.</p>
                        @endforelse
                    </div>
                </section>

                <section x-show="tab === 'fees'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black text-slate-950">Dabba fees</h2>
                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        @forelse ($retailerSummaries as $summary)
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <p class="font-black text-slate-900">{{ $summary->retailer_name ?: 'Unknown retailer' }}</p>
                                <div class="mt-3 space-y-1 text-sm">
                                    <div class="flex justify-between"><span>Subtotal</span><strong>{{ $money($summary->retailer_subtotal) }}</strong></div>
                                    <div class="flex justify-between"><span>Delivery</span><strong>{{ $money($summary->retailer_delivery_fee_total) }}</strong></div>
                                    <div class="flex justify-between border-t pt-2"><span>Fee</span><strong>{{ $money($summary->dabba_fee) }}</strong></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No fee groups yet.</p>
                        @endforelse
                    </div>
                </section>

                <section x-show="tab === 'activity'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black text-slate-950">Activity</h2>
                    <p class="mt-2 text-sm text-slate-500">Activity timeline will be expanded after finalise workflow.</p>
                </section>
            </main>

            {{-- Sticky sidebar --}}
            <aside class="space-y-4 xl:sticky xl:top-4 xl:self-start">
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between"><h2 class="text-lg font-black text-slate-950">Order summary</h2><button type="button" disabled class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-black text-slate-400">Details</button></div>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between"><span class="text-slate-500">Items subtotal</span><strong>{{ $money($draft->items_subtotal) }}</strong></div>
                        <div class="flex justify-between"><span class="text-slate-500">Delivery fees</span><strong>{{ $money($draft->retailer_delivery_total) }}</strong></div>
                        <div class="flex justify-between"><span class="text-slate-500">Retailer fees</span><strong>{{ $money($draft->dabba_fee_total) }}</strong></div>
                    </div>
                    <div class="mt-4 border-t border-slate-200 pt-4">
                        <div class="flex items-end justify-between"><span class="text-sm font-black text-slate-600">Total</span><strong class="text-3xl font-black text-slate-950">{{ $money($draft->grand_total) }}</strong></div>
                        <span class="mt-3 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Qty {{ $qtyTotal }}</span>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between"><h2 class="text-lg font-black text-slate-950">Customer</h2><button type="button" disabled class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-black text-slate-400">Edit soon</button></div>
                    <div class="mt-4 space-y-2 text-sm text-slate-600">
                        <p class="font-black text-slate-950">{{ $customerName }}</p>
                        <p>{{ $customerDetails['phone'] ?? '—' }}</p>
                        <p>{{ $customerDetails['email'] ?? '—' }}</p>
                        <p class="whitespace-pre-line">{{ $customerDetails['address'] ?? '—' }}</p>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between"><h2 class="text-lg font-black text-slate-950">Draft settings</h2><button type="button" disabled class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-black text-slate-400">Edit</button></div>
                    <form method="POST" action="{{ route('draft-orders.update', $draft->id) }}" class="mt-4 space-y-3">
                        @csrf
                        @method('PATCH')
                        <div><label class="text-xs font-black uppercase tracking-widest text-slate-500">Status</label><select name="status" class="mt-1 w-full rounded-2xl border-slate-300 text-sm focus:border-purple-500 focus:ring-purple-500">@foreach ($statusOptions as $status)<option value="{{ $status }}" @selected(($draft->status ?? 'open') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
                        <div><label class="text-xs font-black uppercase tracking-widest text-slate-500">Fee mode</label><select name="fee_mode" class="mt-1 w-full rounded-2xl border-slate-300 text-sm focus:border-purple-500 focus:ring-purple-500"><option value="standard" @selected(($draft->fee_mode ?? 'standard') === 'standard')>Standard fee</option><option value="fee_disabled" @selected(($draft->fee_mode ?? '') === 'fee_disabled')>Fee disabled</option></select></div>
                        <label class="flex items-center gap-2 rounded-2xl bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-700"><input type="checkbox" name="home_delivery_requested" value="1" @checked(!empty($draft->home_delivery_requested)) class="rounded border-slate-300 text-purple-600 focus:ring-purple-500"> Home delivery requested</label>
                        <button type="submit" class="w-full rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white hover:bg-slate-800">Save draft</button>
                    </form>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-950">Actions</h2>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <button type="button" disabled class="rounded-2xl border border-purple-200 px-4 py-3 text-sm font-black text-purple-400">Save draft</button>
                        <button type="button" disabled class="rounded-2xl bg-purple-600 px-4 py-3 text-sm font-black text-white opacity-80">Finalise to Order</button>
                    </div>
                </section>
            </aside>
        </div>

        {{-- Retailer-not-detected modal --}}
        <div x-show="retailerModal.open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4" @keydown.escape.window="retailerModal.open=false">
            <div @click.outside="retailerModal.open=false" class="w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-black text-slate-950">Retailer not detected</h2>
                        <p class="mt-1 text-sm text-slate-500">Add this retailer once and continue. The domain is already cleaned for you.</p>
                    </div>
                    <button type="button" @click="retailerModal.open=false" class="rounded-xl px-3 py-2 text-slate-500 hover:bg-slate-100">✕</button>
                </div>
                <div class="mt-5 space-y-3">
                    <div><label class="text-xs font-black uppercase tracking-widest text-slate-500">Cleaned URL / domain</label><input x-model="retailerModal.baseUrl" class="mt-1 w-full rounded-2xl border-slate-300 text-sm focus:border-purple-500 focus:ring-purple-500"></div>
                    <div><label class="text-xs font-black uppercase tracking-widest text-slate-500">Retailer name *</label><input x-model="retailerModal.name" placeholder="e.g. Example Retailer" class="mt-1 w-full rounded-2xl border-slate-300 text-sm focus:border-purple-500 focus:ring-purple-500"></div>
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" checked class="rounded border-slate-300 text-purple-600 focus:ring-purple-500"> Active</label>
                    <p x-show="retailerModal.error" x-text="retailerModal.error" class="text-sm font-semibold text-rose-600"></p>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="retailerModal.open=false" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="button" @click="saveRetailer()" class="rounded-2xl bg-purple-600 px-5 py-3 text-sm font-black text-white hover:bg-purple-700">Save retailer & assign to item</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function draftWorkspace(config) {
            return {
                tab: config.initialTab || 'products',
                basketSearch: '',
                detectMessage: '',
                detectedRetailerId: null,
                newItem: { url: '', retailerId: '', qty: 1, unitPrice: '0.00' },
                retailerModal: { open: false, name: '', baseUrl: '', error: '' },

                boot() {
                    const justAdded = document.querySelector('[id^="item-"].bg-purple-50\\/70');
                    if (justAdded) setTimeout(() => justAdded.scrollIntoView({ behavior: 'smooth', block: 'center' }), 250);
                },

                matchesSearch(el) {
                    const q = (this.basketSearch || '').trim().toLowerCase();
                    if (!q) return true;
                    return el.innerText.toLowerCase().includes(q);
                },

                cleanHost(url) {
                    try {
                        let value = (url || '').trim();
                        if (!value) return '';
                        if (!value.includes('://')) value = 'https://' + value;
                        let host = new URL(value).hostname.toLowerCase();
                        return host.replace(/^www\./, '');
                    } catch (e) {
                        return (url || '').replace(/^https?:\/\//, '').replace(/^www\./, '').split('/')[0].toLowerCase();
                    }
                },

                guessRetailerName(host) {
                    if (!host) return '';
                    const first = host.split('.')[0] || '';
                    return first.replace(/[-_]+/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                },

                async detectRetailer() {
                    this.detectMessage = '';
                    this.detectedRetailerId = null;
                    if (!this.newItem.url.trim()) return;

                    try {
                        const response = await fetch(config.detectUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': config.csrf,
                            },
                            body: JSON.stringify({ url: this.newItem.url }),
                        });

                        const payload = await response.json();
                        const retailer = payload.retailer || {};

                        if (retailer.final_url || retailer.finalUrl) {
                            this.newItem.url = retailer.final_url || retailer.finalUrl;
                        }

                        if (retailer.retailer_id || retailer.retailerId) {
                            const id = retailer.retailer_id || retailer.retailerId;
                            this.newItem.retailerId = String(id);
                            this.detectedRetailerId = id;
                            this.detectMessage = 'Retailer detected: ' + (retailer.name || 'matched') + ((retailer.final_url || retailer.finalUrl) ? ' · URL expanded/cleaned' : '');
                            return;
                        }

                        const host = retailer.host || this.cleanHost(this.newItem.url);
                        this.retailerModal.baseUrl = host;
                        this.retailerModal.name = retailer.name || this.guessRetailerName(host);
                        this.retailerModal.error = '';
                        this.retailerModal.open = true;
                        this.detectMessage = 'Retailer not recognised. Add it once and continue.';
                    } catch (e) {
                        this.detectMessage = 'Could not detect retailer. You can choose one manually.';
                    }
                },

                async saveRetailer() {
                    this.retailerModal.error = '';
                    if (!this.retailerModal.name.trim() || !this.retailerModal.baseUrl.trim()) {
                        this.retailerModal.error = 'Retailer name and domain are required.';
                        return;
                    }

                    const response = await fetch(config.quickRetailerUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': config.csrf,
                        },
                        body: JSON.stringify({ name: this.retailerModal.name, base_url: this.retailerModal.baseUrl }),
                    });

                    const payload = await response.json();
                    if (!response.ok || !payload.ok) {
                        this.retailerModal.error = payload.message || 'Could not save retailer.';
                        return;
                    }

                    const retailer = payload.retailer;
                    const select = document.querySelector('select[name="retailer_id"]');
                    let option = select.querySelector('option[value="' + retailer.id + '"]');
                    if (!option) {
                        option = new Option(retailer.name, retailer.id, true, true);
                        select.add(option);
                    }
                    this.newItem.retailerId = String(retailer.id);
                    this.detectedRetailerId = retailer.id;
                    this.detectMessage = 'Retailer added and selected: ' + retailer.name;
                    this.retailerModal.open = false;
                }
            }
        }
    </script>
</x-app-layout>
