<x-app-layout>
    <x-slot name="header">
        Purchasing Workspace
    </x-slot>

    @php
        $orderNumber = $order->order_number ?? $order->id;
        $customer = trim((string) ($order->bill_to_company ?: $order->bill_to_name ?: 'Unknown customer'));
        $queueOrder = $queueOrder ?? null;
        $buyItems = $items->filter(fn ($item) => (int) $item->remaining_to_buy_qty > 0)->values();
        $awaitingItems = $items->filter(fn ($item) => (int) $item->awaiting_arrival_qty > 0)->values();
        $problemItems = $items->filter(fn ($item) => (int) $item->problem_qty > 0)->values();
        $tabUrls = collect($tabs)->mapWithKeys(fn ($label, $key) => [$key => route('purchasing.show', ['order' => $order->id, 'tab' => $key])]);
        $paymentStatus = $queueOrder['payment_status'] ?? 'unknown';
        $paymentBadge = [
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'part_paid' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'unpaid' => 'bg-slate-50 text-slate-600 ring-slate-200',
        ][$paymentStatus] ?? 'bg-slate-50 text-slate-600 ring-slate-200';
    @endphp

    <div class="space-y-6">
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-5 py-6 text-white sm:px-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <a href="{{ route('purchasing.index') }}" class="text-xs font-black uppercase tracking-[0.22em] text-indigo-200 hover:text-white">← Back to queue</a>
                        <h2 class="mt-3 text-3xl font-black tracking-tight">Order #{{ $orderNumber }}</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-300">{{ $customer }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:min-w-[520px]">
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <div class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-300">To buy</div>
                            <div class="mt-1 text-2xl font-black">{{ $queueOrder['remaining_to_buy_qty'] ?? 0 }}</div>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <div class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-300">Awaiting</div>
                            <div class="mt-1 text-2xl font-black">{{ $queueOrder['awaiting_arrival_qty'] ?? 0 }}</div>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <div class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-300">Problems</div>
                            <div class="mt-1 text-2xl font-black">{{ $queueOrder['problem_qty'] ?? 0 }}</div>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                            <div class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-300">Payment</div>
                            <div class="mt-2 inline-flex rounded-full bg-white px-2 py-1 text-xs font-black text-slate-900">{{ str_replace('_', '-', ucfirst($paymentStatus)) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-b border-slate-100 bg-white px-5 py-3 sm:px-6">
                <div class="flex flex-wrap gap-2">
                    @foreach ($tabs as $key => $label)
                        <a href="{{ $tabUrls[$key] }}" class="rounded-2xl px-4 py-2 text-sm font-black ring-1 transition {{ $activeTab === $key ? 'bg-indigo-600 text-white ring-indigo-600 shadow-sm shadow-indigo-100' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50 hover:text-slate-950' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        @if ($activeTab === 'overview')
            <section class="grid gap-5 lg:grid-cols-3">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                    <h3 class="text-lg font-black text-slate-950">Retailer work groups</h3>
                    <p class="mt-1 text-sm text-slate-500">Retailers are shown only inside this order. We never combine different customer orders into one retailer basket.</p>

                    <div class="mt-5 divide-y divide-slate-100 rounded-2xl border border-slate-100">
                        @foreach ($retailers as $retailer)
                            <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="font-black text-slate-900">{{ $retailer['retailer_name'] }}</div>
                                    <div class="mt-1 text-xs font-semibold text-slate-400">{{ $retailer['items']->count() }} item{{ $retailer['items']->count() === 1 ? '' : 's' }}</div>
                                </div>
                                <div class="flex flex-wrap gap-2 text-xs font-black">
                                    @if ($retailer['remaining_to_buy_qty'] > 0)
                                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700 ring-1 ring-indigo-200">{{ $retailer['remaining_to_buy_qty'] }} to buy</span>
                                    @endif
                                    @if ($retailer['awaiting_arrival_qty'] > 0)
                                        <span class="rounded-full bg-sky-50 px-2.5 py-1 text-sky-700 ring-1 ring-sky-200">{{ $retailer['awaiting_arrival_qty'] }} awaiting</span>
                                    @endif
                                    @if ($retailer['problem_qty'] > 0)
                                        <span class="rounded-full bg-rose-50 px-2.5 py-1 text-rose-700 ring-1 ring-rose-200">{{ $retailer['problem_qty'] }} problem</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-black text-slate-950">Workspace focus</h3>
                    <div class="mt-4 space-y-3 text-sm font-semibold text-slate-600">
                        <div class="rounded-2xl bg-slate-50 p-4">Use <strong>Buy</strong> for items still needing purchase.</div>
                        <div class="rounded-2xl bg-slate-50 p-4">Use <strong>Awaiting Arrival</strong> to check bought items not yet matched.</div>
                        <div class="rounded-2xl bg-slate-50 p-4">Use <strong>Problems</strong> for supplier failures or sourcing issues.</div>
                    </div>
                </div>
            </section>
        @elseif ($activeTab === 'buy')
            @include('purchasing.partials.items', ['rows' => $buyItems, 'title' => 'Items to buy', 'empty' => 'There are no remaining items to buy for this order.'])
        @elseif ($activeTab === 'awaiting')
            @include('purchasing.partials.items', ['rows' => $awaitingItems, 'title' => 'Awaiting arrival', 'empty' => 'Nothing is currently awaiting arrival.'])
        @elseif ($activeTab === 'problems')
            @include('purchasing.partials.items', ['rows' => $problemItems, 'title' => 'Problems', 'empty' => 'No open purchasing problems for this order.'])
        @else
            <section class="grid gap-5 lg:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-black text-slate-950">Purchase events</h3>
                    <div class="mt-4 divide-y divide-slate-100 rounded-2xl border border-slate-100">
                        @forelse ($purchases as $purchase)
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-black text-slate-900">{{ $purchase->item_name }}</div>
                                        <div class="mt-1 text-xs font-semibold text-slate-400">{{ $purchase->master_retailer_name ?: 'Retailer' }} · Qty {{ $purchase->qty }}</div>
                                    </div>
                                    <span class="rounded-full bg-slate-50 px-2.5 py-1 text-xs font-black text-slate-600 ring-1 ring-slate-200">{{ $purchase->status }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-sm font-semibold text-slate-500">No purchase events yet.</div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-black text-slate-950">Arrival matches</h3>
                    <div class="mt-4 divide-y divide-slate-100 rounded-2xl border border-slate-100">
                        @forelse ($arrivals as $arrival)
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-black text-slate-900">{{ $arrival->item_name }}</div>
                                        <div class="mt-1 text-xs font-semibold text-slate-400">Qty {{ $arrival->qty }} · {{ $arrival->matched_at }}</div>
                                    </div>
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">{{ $arrival->status }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-sm font-semibold text-slate-500">No arrival matches yet.</div>
                        @endforelse
                    </div>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
