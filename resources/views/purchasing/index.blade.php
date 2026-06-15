<x-app-layout>
    <x-slot name="header">Purchasing Desk</x-slot>

    @php
        $money = fn ($amount) => '£' . number_format((float) $amount, 2);
        $activeTab = $filters['tab'] ?? 'to_buy';
        $purchaseRows = collect($purchaseRows ?? []);
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
        $paymentBadge = [
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'part_paid' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'unpaid' => 'bg-rose-50 text-rose-700 ring-rose-200',
        ];
        $paymentLabel = ['paid' => 'Paid', 'part_paid' => 'Part paid', 'unpaid' => 'Unpaid'];
    @endphp

    <div class="mx-auto max-w-6xl space-y-5">
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

        <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-950">Purchasing Desk</h1>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Find what needs buying, review purchases, and deal with purchasing problems.</p>
                </div>

                <div class="grid grid-cols-3 gap-2 sm:min-w-[440px]">
                    <div class="rounded-2xl bg-indigo-50 p-3 ring-1 ring-indigo-100">
                        <p class="text-[10px] font-black uppercase tracking-wide text-indigo-500">To Purchase</p>
                        <p class="mt-1 text-2xl font-black text-indigo-950">{{ $summary['to_buy'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 p-3 ring-1 ring-emerald-100">
                        <p class="text-[10px] font-black uppercase tracking-wide text-emerald-600">Purchased</p>
                        <p class="mt-1 text-2xl font-black text-emerald-950">{{ $summary['purchases'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-2xl bg-rose-50 p-3 ring-1 ring-rose-100">
                        <p class="text-[10px] font-black uppercase tracking-wide text-rose-500">Problems</p>
                        <p class="mt-1 text-2xl font-black text-rose-950">{{ $summary['problems'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                @foreach ($tabs as $tabKey => $tab)
                    <a href="{{ route('purchasing.index', ['tab' => $tabKey, 'payment' => $filters['payment'], 'q' => $filters['q'], 'mine' => ($filters['mine'] ?? false) ? 1 : null]) }}" class="rounded-2xl px-4 py-2 text-sm font-black ring-1 transition {{ $activeTab === $tabKey ? 'bg-slate-950 text-white ring-slate-950' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}">
                        {{ $tab['label'] }} <span class="opacity-70">{{ $tab['count'] }}</span>
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('purchasing.index') }}" class="mt-4">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <input type="hidden" name="payment" value="{{ $filters['payment'] }}">
                <div class="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <label for="purchase-search" class="sr-only">Search purchasing</label>
                        <input id="purchase-search" name="q" value="{{ $filters['q'] }}" placeholder="Search customer, order, retailer ref, item, retailer or email" class="h-12 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 text-sm font-bold text-slate-900 placeholder:text-slate-400 focus:border-indigo-300 focus:bg-white focus:ring-indigo-200">
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="inline-flex h-12 items-center gap-2 rounded-2xl bg-white px-4 text-sm font-black text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">
                            <input type="checkbox" name="mine" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" {{ ($filters['mine'] ?? false) ? 'checked' : '' }}>
                            Mine only
                        </label>
                        <a href="{{ route('purchasing.index', ['tab' => $activeTab, 'payment' => 'paid_or_part', 'q' => $filters['q'], 'mine' => ($filters['mine'] ?? false) ? 1 : null]) }}" class="rounded-2xl px-4 py-3 text-sm font-black ring-1 transition {{ $filters['payment'] === 'paid_or_part' ? 'bg-indigo-600 text-white ring-indigo-600' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}">Paid & Part Paid</a>
                        <a href="{{ route('purchasing.index', ['tab' => $activeTab, 'payment' => 'all', 'q' => $filters['q'], 'mine' => ($filters['mine'] ?? false) ? 1 : null]) }}" class="rounded-2xl px-4 py-3 text-sm font-black ring-1 transition {{ $filters['payment'] === 'all' ? 'bg-rose-600 text-white ring-rose-600' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}">All Orders</a>
                        <button class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white hover:bg-indigo-700">Search</button>
                    </div>
                </div>
            </form>
        </section>

        @if ($activeTab === 'purchases')
            <section class="space-y-3">
                @forelse ($purchaseRows as $purchase)
                    @php
                        $customer = trim((string) ($purchase->bill_to_company ?: $purchase->bill_to_name ?: 'Unknown customer'));
                        $canEdit = (bool) ($purchase->can_edit ?? false);
                    @endphp
                    <article class="rounded-[1.5rem] border border-emerald-100 bg-white p-4 shadow-sm sm:p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">Purchased</span>
                                    @if (! $canEdit)
                                        <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-black text-amber-700 ring-1 ring-amber-200">Locked after arrival</span>
                                    @endif
                                </div>
                                <h2 class="mt-3 text-lg font-black text-slate-950">Order #{{ $purchase->order_number }} · {{ $customer }}</h2>
                                <p class="mt-1 text-sm font-bold text-slate-700">{{ $purchase->retailer_name }} · {{ $purchase->item_name }}</p>
                                <p class="mt-1 break-words text-xs font-semibold text-slate-400">{{ $purchase->bill_to_email }}</p>
                            </div>

                            <div class="grid gap-2 text-sm sm:grid-cols-4 lg:min-w-[620px]">
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Qty</p><p class="mt-1 font-black text-slate-950">{{ (int) $purchase->qty }}</p></div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Ref</p><p class="mt-1 truncate font-black text-slate-950">{{ $purchase->retailer_order_reference ?: '—' }}</p></div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">ETA</p><p class="mt-1 font-black text-slate-950">{{ $purchase->expected_uk_hub_at ? \Carbon\Carbon::parse($purchase->expected_uk_hub_at)->format('d M Y') : '—' }}</p></div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Tracking</p><p class="mt-1 font-black text-slate-950">—</p></div>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs font-bold text-slate-500">
                                Recorded by {{ $purchase->recorded_by_name ?: 'Unknown user' }} · {{ $purchase->created_at ? \Carbon\Carbon::parse($purchase->created_at)->format('d M Y H:i') : '—' }}
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('purchasing.orders.show', ['order' => $purchase->order_id, 'tab' => 'buy']) }}" class="rounded-2xl bg-slate-950 px-4 py-2 text-center text-sm font-black text-white hover:bg-indigo-700">Edit / View Purchase</a>
                                <a href="{{ route('orders.show', $purchase->order_id) }}" class="rounded-2xl bg-white px-4 py-2 text-center text-sm font-black text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">Order ↗</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                        <p class="text-lg font-black text-slate-900">No purchased items found.</p>
                        <p class="mt-2 text-sm font-semibold text-slate-500">Try a different search or payment filter.</p>
                    </div>
                @endforelse
            </section>
        @else
            <section class="space-y-4">
                @forelse ($orders as $order)
                    @php
                        $payClass = $paymentBadge[$order['payment_status']] ?? $paymentBadge['unpaid'];
                        $payText = $paymentLabel[$order['payment_status']] ?? ucfirst((string) $order['payment_status']);
                        $primaryAction = $activeTab === 'problems' ? 'View Problems' : 'Purchase Items';
                    @endphp
                    <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-xl font-black text-slate-950">#{{ $order['order_number'] }}</h2>
                                    <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $payClass }}">{{ $payText }}</span>
                                    @if ((int) $order['inspection_count'] > 0)
                                        <span class="rounded-full bg-purple-50 px-3 py-1.5 text-xs font-black text-purple-700 ring-1 ring-purple-200">Package check</span>
                                    @endif
                                </div>
                                <p class="mt-2 text-sm font-bold text-slate-700">{{ $order['customer'] }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-400">{{ $order['email'] }}</p>
                            </div>

                            <div class="grid gap-2 text-sm sm:grid-cols-3 lg:min-w-[460px]">
                                <div class="rounded-2xl bg-indigo-50 p-3 ring-1 ring-indigo-100"><p class="text-[10px] font-black uppercase tracking-wide text-indigo-500">To purchase</p><p class="mt-1 font-black text-indigo-950">{{ $order['remaining_to_buy_qty'] }}</p></div>
                                <div class="rounded-2xl bg-emerald-50 p-3 ring-1 ring-emerald-100"><p class="text-[10px] font-black uppercase tracking-wide text-emerald-600">Purchased</p><p class="mt-1 font-black text-emerald-950">{{ $order['purchased_qty'] }}</p></div>
                                <div class="rounded-2xl bg-rose-50 p-3 ring-1 ring-rose-100"><p class="text-[10px] font-black uppercase tracking-wide text-rose-500">Problems</p><p class="mt-1 font-black text-rose-950">{{ $order['problem_qty'] }}</p></div>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs font-bold text-slate-500">Retailers: {{ $order['retailer_count'] }} · Order total {{ $money($order['grand_total']) }}</p>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('purchasing.orders.show', ['order' => $order['order_id'], 'tab' => $activeTab === 'problems' ? 'problems' : 'buy']) }}" class="rounded-2xl bg-slate-950 px-4 py-2 text-center text-sm font-black text-white hover:bg-indigo-700">{{ $primaryAction }}</a>
                                <a href="{{ route('orders.show', $order['order_id']) }}" class="rounded-2xl bg-white px-4 py-2 text-center text-sm font-black text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">Order ↗</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                        <p class="text-lg font-black text-slate-900">Nothing found here.</p>
                        <p class="mt-2 text-sm font-semibold text-slate-500">Try another tab, All Orders, or adjust your search.</p>
                    </div>
                @endforelse
            </section>
        @endif
    </div>
</x-app-layout>
