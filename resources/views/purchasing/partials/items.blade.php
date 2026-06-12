<section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
    <h3 class="text-lg font-black text-slate-950">{{ $title }}</h3>

    <div class="mt-5 divide-y divide-slate-100 rounded-2xl border border-slate-100">
        @forelse ($rows as $item)
            <div class="p-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-black uppercase tracking-[0.12em] text-slate-500">{{ $item->retailer_name }}</span>
                            @if ($item->product_code)
                                <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-black text-slate-500 ring-1 ring-slate-200">SKU {{ $item->product_code }}</span>
                            @endif
                        </div>
                        <div class="mt-2 font-black leading-6 text-slate-950">{{ $item->item_name }}</div>
                        @if ($item->product_url)
                            <a href="{{ $item->product_url }}" target="_blank" class="mt-2 inline-flex text-xs font-black text-indigo-600 hover:text-indigo-800">↗ Product link</a>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-center sm:grid-cols-4 lg:min-w-[360px]">
                        <div class="rounded-2xl bg-slate-50 p-3">
                            <div class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">Qty</div>
                            <div class="mt-1 text-lg font-black text-slate-900">{{ $item->quantity }}</div>
                        </div>
                        <div class="rounded-2xl bg-indigo-50 p-3">
                            <div class="text-[10px] font-black uppercase tracking-[0.14em] text-indigo-400">To buy</div>
                            <div class="mt-1 text-lg font-black text-indigo-700">{{ $item->remaining_to_buy_qty }}</div>
                        </div>
                        <div class="rounded-2xl bg-sky-50 p-3">
                            <div class="text-[10px] font-black uppercase tracking-[0.14em] text-sky-400">Awaiting</div>
                            <div class="mt-1 text-lg font-black text-sky-700">{{ $item->awaiting_arrival_qty }}</div>
                        </div>
                        <div class="rounded-2xl bg-rose-50 p-3">
                            <div class="text-[10px] font-black uppercase tracking-[0.14em] text-rose-400">Problem</div>
                            <div class="mt-1 text-lg font-black text-rose-700">{{ $item->problem_qty }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-sm font-semibold text-slate-500">{{ $empty }}</div>
        @endforelse
    </div>
</section>
