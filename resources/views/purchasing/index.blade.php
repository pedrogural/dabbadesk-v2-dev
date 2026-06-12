<x-app-layout>
    <x-slot name="header">Purchase Queue</x-slot>

    @php
        $money = fn ($amount) => '£' . number_format((float) $amount, 2);
        $paymentBadge = [
            'paid' => ['label' => 'Fully Paid', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
            'part_paid' => ['label' => 'Part Paid', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
            'unpaid' => ['label' => 'Unpaid', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200', 'dot' => 'bg-rose-500'],
        ];
    @endphp

    <div class="mx-auto max-w-6xl space-y-5">
        <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
            <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-5 py-6 text-white sm:px-7">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-indigo-200">Order → Retailer → Reference</p>
                        <h1 class="mt-2 text-3xl font-black tracking-tight">Purchase Queue</h1>
                        <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-slate-300">
                            Only orders with payment are shown by default. Use All Orders when a purchaser deliberately needs to buy before payment is recorded.
                        </p>
                    </div>
                    <div class="grid grid-cols-3 gap-2 sm:min-w-[420px]">
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-300">To buy</p>
                            <p class="mt-1 text-2xl font-black">{{ $summary['to_buy'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-300">Awaiting</p>
                            <p class="mt-1 text-2xl font-black">{{ $summary['awaiting_arrival'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-300">Problems</p>
                            <p class="mt-1 text-2xl font-black">{{ $summary['problems'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ route('purchasing.index') }}" class="border-b border-slate-100 bg-white px-5 py-4 sm:px-7">
                <input type="hidden" name="tab" value="to_buy">
                <input type="hidden" name="payment" value="{{ $filters['payment'] }}">
                <div class="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <label for="purchase-search" class="sr-only">Search orders</label>
                        <input id="purchase-search" name="q" value="{{ $filters['q'] }}" placeholder="Search order number, customer, item or retailer" class="h-12 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 text-sm font-bold text-slate-900 placeholder:text-slate-400 focus:border-indigo-300 focus:bg-white focus:ring-indigo-200">
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('purchasing.index', ['tab' => 'to_buy', 'payment' => 'paid_or_part', 'q' => $filters['q']]) }}" class="rounded-2xl px-4 py-3 text-sm font-black ring-1 transition {{ $filters['payment'] === 'paid_or_part' ? 'bg-indigo-600 text-white ring-indigo-600' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}">Paid & Part Paid</a>
                        <a href="{{ route('purchasing.index', ['tab' => 'to_buy', 'payment' => 'all', 'q' => $filters['q']]) }}" class="rounded-2xl px-4 py-3 text-sm font-black ring-1 transition {{ $filters['payment'] === 'all' ? 'bg-rose-600 text-white ring-rose-600' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}">All Orders</a>
                        <button class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white hover:bg-indigo-700">Search</button>
                    </div>
                </div>
            </form>
        </section>

        <section class="space-y-3">
            @forelse ($orders as $queueOrder)
                @php
                    $badge = $paymentBadge[$queueOrder['payment_status']] ?? $paymentBadge['unpaid'];
                    $retailerNames = collect($queueOrder['items'])->pluck('retailer_name')->filter()->unique()->values();
                    $retailerTotal = max(1, (int) $queueOrder['retailer_count']);
                    $purchasedRetailers = collect($queueOrder['items'])
                        ->groupBy(fn ($item) => (string) ($item->retailer_id ?? 0) . '|' . ($item->retailer_name ?: 'Unknown retailer'))
                        ->filter(fn ($rows) => (int) $rows->sum('remaining_to_buy_qty') === 0 && (int) $rows->sum('purchased_qty') > 0)
                        ->count();
                    $statusText = $purchasedRetailers === 0 ? 'Not Started' : $purchasedRetailers . ' / ' . $retailerTotal . ' Retailers Purchased';
                @endphp

                <article class="group rounded-[1.7rem] border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md sm:p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-black text-slate-950">#{{ $queueOrder['order_number'] }}</h2>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $badge['class'] }}">
                                    <span class="h-2 w-2 rounded-full {{ $badge['dot'] }}"></span>{{ $badge['label'] }}
                                </span>
                                @if ($queueOrder['payment_status'] === 'part_paid')
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-black text-amber-700 ring-1 ring-amber-200">{{ $money($queueOrder['settled_amount']) }} / {{ $money($queueOrder['grand_total']) }}</span>
                                @elseif ($queueOrder['payment_status'] === 'unpaid')
                                    <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-black text-rose-700 ring-1 ring-rose-200">Override purchase only</span>
                                @endif
                            </div>

                            <p class="mt-1 truncate text-sm font-bold text-slate-700">{{ $queueOrder['customer'] }}</p>
                            <div class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Retailers</p>
                                    <p class="mt-1 font-black text-slate-900">{{ $queueOrder['retailer_count'] }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Items</p>
                                    <p class="mt-1 font-black text-slate-900">{{ $queueOrder['item_count'] }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Progress</p>
                                    <p class="mt-1 font-black text-slate-900">{{ $statusText }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach ($retailerNames as $retailerName)
                                    @php
                                        $rows = collect($queueOrder['items'])->where('retailer_name', $retailerName);
                                        $done = (int) $rows->sum('remaining_to_buy_qty') === 0 && (int) $rows->sum('purchased_qty') > 0;
                                    @endphp
                                    <span class="rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $done ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-50 text-slate-600 ring-slate-200' }}">{{ $retailerName }} {{ $done ? '✓' : '○' }}</span>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-col gap-2 sm:flex-row lg:flex-col">
                            <a href="{{ route('purchasing.orders.show', $queueOrder['order_id']) }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-indigo-700">Open Order</a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                    <p class="text-lg font-black text-slate-900">Nothing needs purchasing here.</p>
                    <p class="mt-2 text-sm font-semibold text-slate-500">Try All Orders or adjust your search.</p>
                </div>
            @endforelse
        </section>
    </div>
</x-app-layout>
