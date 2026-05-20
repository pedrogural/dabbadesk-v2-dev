<x-app-layout>
    <x-slot name="header">Draft #{{ $draft->draft_number ?: $draft->id }}</x-slot>

    <div class="space-y-6">
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

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="xl:col-span-4 2xl:col-span-3">
                <div class="sticky top-24 space-y-5">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Customer</p>
                                <h2 class="mt-1 text-xl font-black text-slate-900">
                                    {{ trim(($draft->first_name ?? '') . ' ' . ($draft->last_name ?? '')) ?: ($draft->company_name ?: 'Unknown customer') }}
                                </h2>
                                @if ($draft->company_name)
                                    <p class="mt-1 text-sm text-slate-500">{{ $draft->company_name }}</p>
                                @endif
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $draft->status }}</span>
                        </div>

                        <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <dt class="text-xs font-semibold uppercase text-slate-400">Request</dt>
                                <dd class="mt-1 font-bold text-slate-900">{{ $draft->request_ref ?: 'Manual' }}</dd>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <dt class="text-xs font-semibold uppercase text-slate-400">Draft</dt>
                                <dd class="mt-1 font-bold text-slate-900">{{ $draft->draft_number ?: $draft->id }}</dd>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <dt class="text-xs font-semibold uppercase text-slate-400">Subtotal</dt>
                                <dd class="mt-1 font-bold text-slate-900">£{{ number_format($draft->items_subtotal ?? 0, 2) }}</dd>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <dt class="text-xs font-semibold uppercase text-slate-400">Total</dt>
                                <dd class="mt-1 font-black text-indigo-700">£{{ number_format($draft->grand_total ?? 0, 2) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-bold text-slate-900">Draft controls</h3>
                        <form method="POST" action="{{ route('draft-orders.update', $draft->id) }}" class="mt-4 space-y-4">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label class="text-sm font-semibold text-slate-700">Status</label>
                                <select name="status" class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach ($statusOptions as $status)
                                        <option value="{{ $status }}" @selected($draft->status === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-slate-700">Fee mode</label>
                                <select name="fee_mode" class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="standard" @selected($draft->fee_mode === 'standard')>Standard fee</option>
                                    <option value="fee_disabled" @selected($draft->fee_mode === 'fee_disabled')>Fee disabled</option>
                                </select>
                            </div>

                            <label class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3 text-sm font-semibold text-slate-700">
                                <input type="checkbox" name="home_delivery_requested" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" @checked($draft->home_delivery_requested)>
                                Home delivery requested
                            </label>

                            <button type="submit" class="w-full rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-700">Save draft controls</button>
                        </form>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-bold text-slate-900">Finance preview</h3>
                        <div class="mt-4 space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-slate-500">Items subtotal</span><strong>£{{ number_format($draft->items_subtotal ?? 0, 2) }}</strong></div>
                            <div class="flex justify-between"><span class="text-slate-500">Retailer delivery</span><strong>£{{ number_format($draft->retailer_delivery_total ?? 0, 2) }}</strong></div>
                            <div class="flex justify-between"><span class="text-slate-500">Dabba fee</span><strong>£{{ number_format($draft->dabba_fee_total ?? 0, 2) }}</strong></div>
                            <div class="border-t border-slate-200 pt-3 flex justify-between text-base"><span class="font-bold text-slate-900">Grand total</span><strong class="text-indigo-700">£{{ number_format($draft->grand_total ?? 0, 2) }}</strong></div>
                        </div>
                        <p class="mt-3 rounded-2xl bg-amber-50 p-3 text-xs text-amber-800">Finalise to real order is intentionally held for the next step, so this workspace can be tested safely first.</p>
                    </div>
                </div>
            </aside>

            <section class="xl:col-span-8 2xl:col-span-9 space-y-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h1 class="text-2xl font-black text-slate-900">Draft workspace</h1>
                            <p class="mt-1 text-sm text-slate-500">Review and correct items before this becomes an immutable order snapshot.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('draft-orders.index') }}" class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-200">Back to drafts</a>
                            @if ($draft->finalized_order_id)
                                <a href="{{ route('orders.show', $draft->finalized_order_id) }}" class="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-700">Open order #{{ $draft->finalized_order_number }}</a>
                            @else
                                <button type="button" disabled class="cursor-not-allowed rounded-2xl bg-slate-200 px-4 py-3 text-sm font-semibold text-slate-500">Finalise soon</button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-lg font-bold text-slate-900">Retailer totals</h2>
                        <span class="text-sm text-slate-500">Fee = max minimum fee or percentage, per retailer</span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-2 2xl:grid-cols-3">
                        @forelse ($retailerSummaries as $summary)
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <p class="font-bold text-slate-900">{{ $summary->retailer_name ?: 'Unknown retailer' }}</p>
                                <div class="mt-3 space-y-1 text-sm text-slate-600">
                                    <div class="flex justify-between"><span>Items</span><strong>£{{ number_format($summary->retailer_subtotal, 2) }}</strong></div>
                                    <div class="flex justify-between"><span>Delivery</span><strong>£{{ number_format($summary->retailer_delivery_fee_total, 2) }}</strong></div>
                                    <div class="flex justify-between"><span>Fee</span><strong>£{{ number_format($summary->dabba_fee, 2) }}</strong></div>
                                    <div class="flex justify-between border-t pt-2 font-bold text-slate-900"><span>Total</span><strong>£{{ number_format($summary->retailer_grand_total, 2) }}</strong></div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">No retailer totals yet.</div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900">Draft items</h2>
                    <div class="mt-4 space-y-4">
                        @foreach ($items as $item)
                            <form method="POST" action="{{ route('draft-orders.items.update', [$draft->id, $item->id]) }}" class="rounded-3xl border border-slate-200 p-4">
                                @csrf
                                @method('PATCH')
                                <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                                    <div class="lg:col-span-4">
                                        <label class="text-xs font-bold uppercase text-slate-400">Description</label>
                                        <textarea name="description" rows="3" class="mt-1 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $item->description }}</textarea>
                                    </div>
                                    <div class="lg:col-span-3">
                                        <label class="text-xs font-bold uppercase text-slate-400">Retailer</label>
                                        <select name="retailer_id" class="mt-1 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @foreach ($retailers as $retailer)
                                                <option value="{{ $retailer->id }}" @selected($item->retailer_id == $retailer->id)>{{ $retailer->name }}</option>
                                            @endforeach
                                        </select>
                                        <input name="url" value="{{ $item->url }}" placeholder="Product URL" class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div class="lg:col-span-1">
                                        <label class="text-xs font-bold uppercase text-slate-400">Qty</label>
                                        <input name="qty" type="number" min="1" value="{{ $item->qty }}" class="mt-1 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div class="lg:col-span-2">
                                        <label class="text-xs font-bold uppercase text-slate-400">Unit price</label>
                                        <input name="unit_price" type="number" min="0" step="0.01" value="{{ $item->unit_price }}" class="mt-1 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <input name="product_code" value="{{ $item->product_code }}" placeholder="Product code" class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div class="lg:col-span-2">
                                        <label class="text-xs font-bold uppercase text-slate-400">Delivery fee</label>
                                        <input name="item_retailer_delivery_fee" type="number" min="0" step="0.01" value="{{ $item->item_retailer_delivery_fee ?? $item->item_delivery_fee ?? 0 }}" class="mt-1 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <input name="sku" value="{{ $item->sku }}" placeholder="SKU" class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                </div>
                                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                                    <p class="text-sm text-slate-500">Line total: <strong class="text-slate-900">£{{ number_format($item->line_total ?? 0, 2) }}</strong></p>
                                    <div class="flex gap-2">
                                        <button type="submit" class="rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Save item</button>
                                    </div>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('draft-orders.items.destroy', [$draft->id, $item->id]) }}" onsubmit="return confirm('Remove this draft item?')" class="-mt-2 flex justify-end">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700">Remove item</button>
                            </form>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900">Add item</h2>
                    <form method="POST" action="{{ route('draft-orders.items.store', $draft->id) }}" class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-12">
                        @csrf
                        <div class="lg:col-span-4"><textarea name="description" rows="3" placeholder="Item description" class="w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea></div>
                        <div class="lg:col-span-3"><select name="retailer_id" class="w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">@foreach ($retailers as $retailer)<option value="{{ $retailer->id }}">{{ $retailer->name }}</option>@endforeach</select><input name="url" placeholder="Product URL" class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                        <div class="lg:col-span-1"><input name="qty" type="number" min="1" value="1" class="w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                        <div class="lg:col-span-2"><input name="unit_price" type="number" min="0" step="0.01" value="0.00" class="w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><input name="product_code" placeholder="Product code" class="mt-2 w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                        <div class="lg:col-span-2"><input name="item_retailer_delivery_fee" type="number" min="0" step="0.01" value="0.00" class="w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><button type="submit" class="mt-2 w-full rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Add item</button></div>
                    </form>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900">Internal notes</h2>
                    <form method="POST" action="{{ route('draft-orders.notes.store', $draft->id) }}" class="mt-4">
                        @csrf
                        <textarea name="body" rows="3" placeholder="Add an operational note for this draft..." class="w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <div class="mt-3 flex justify-end"><button type="submit" class="rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Add note</button></div>
                    </form>
                    <div class="mt-5 space-y-3">
                        @forelse ($notes as $note)
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-bold text-slate-900">{{ $note->title ?: ucfirst(str_replace('_', ' ', $note->type)) }}</p>
                                    <p class="text-xs text-slate-400">{{ $note->author_name ?: 'System' }} · {{ $note->created_at ? \Carbon\Carbon::parse($note->created_at)->format('d M Y H:i') : '' }}</p>
                                </div>
                                <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $note->body }}</p>
                            </div>
                        @empty
                            <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">No notes yet.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
