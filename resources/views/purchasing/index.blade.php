<x-app-layout>
    <x-slot name="header">Purchasing Desk</x-slot>

    @php
        $activeTab = $filters['tab'] ?? 'to_buy';
        $activePayment = $filters['payment'] ?? 'paid';
        $tabs = [
            'to_buy' => ['label' => 'To Buy', 'qty' => $summary['to_buy_qty'] ?? 0, 'orders' => $summary['to_buy_orders'] ?? 0, 'hint' => 'Paid order-first buying queue'],
            'awaiting_arrival' => ['label' => 'Awaiting Arrival', 'qty' => $summary['awaiting_arrival_qty'] ?? 0, 'orders' => $summary['awaiting_arrival_orders'] ?? 0, 'hint' => 'Bought, not fully arrived'],
            'problems' => ['label' => 'Problems', 'qty' => $summary['problem_qty'] ?? 0, 'orders' => $summary['problem_orders'] ?? 0, 'hint' => 'Needs human decision'],
            'all' => ['label' => 'All Active', 'qty' => ($summary['to_buy_qty'] ?? 0) + ($summary['awaiting_arrival_qty'] ?? 0) + ($summary['problem_qty'] ?? 0), 'orders' => $summary['visible_order_count'] ?? 0, 'hint' => 'Operational overview'],
        ];
        $paymentLabels = [
            'paid' => 'Paid only',
            'part_paid' => 'Part-paid only',
            'unpaid' => 'Unpaid only',
            'all' => 'All payment states',
        ];
        $paymentBadgeClasses = [
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'part_paid' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'unpaid' => 'bg-rose-50 text-rose-700 ring-rose-100',
        ];
    @endphp

    <div class="space-y-6">
        @if (session('status') || session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 shadow-sm">
                {{ session('status') ?: session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800 shadow-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.24em] text-indigo-500">DabbaDesk</p>
                    <h1 class="mt-1 text-2xl font-black text-slate-950">Purchasing Desk</h1>
                    <p class="mt-1 max-w-3xl text-sm font-semibold text-slate-500">Order-first purchasing. This page is the queue; open an order workspace to record purchases or resolve problems.</p>
                </div>
                <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm font-bold text-slate-600 ring-1 ring-slate-100">
                    {{ $summary['visible_order_count'] ?? 0 }} visible order{{ ($summary['visible_order_count'] ?? 0) == 1 ? '' : 's' }}
                </div>
            </div>

            <form method="GET" action="{{ route('purchasing.index') }}" class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_140px]">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-wide text-slate-400">Search</span>
                    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Order number, customer, item, retailer, reference…" class="mt-1 h-11 w-full rounded-2xl border-slate-300 bg-white px-4 text-sm font-semibold shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-wide text-slate-400">Payment filter</span>
                    <select name="payment" class="mt-1 h-11 w-full rounded-2xl border-slate-300 bg-white px-4 text-sm font-bold shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($paymentLabels as $value => $label)
                            <option value="{{ $value }}" @selected($activePayment === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="flex items-end">
                    <button class="h-11 w-full rounded-2xl bg-slate-950 px-4 text-sm font-black text-white shadow-sm hover:bg-slate-800">Apply</button>
                </div>
            </form>

            <div class="mt-5 grid gap-3 md:grid-cols-4">
                @foreach ($tabs as $key => $tab)
                    <a href="{{ route('purchasing.index', array_filter(['tab' => $key, 'payment' => $activePayment, 'q' => $filters['q'] ?? null], fn ($v) => $v !== null && $v !== '')) }}" class="rounded-3xl border p-4 transition {{ $activeTab === $key ? 'border-indigo-200 bg-indigo-50 shadow-sm' : 'border-slate-200 bg-white hover:bg-slate-50' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-black {{ $activeTab === $key ? 'text-indigo-700' : 'text-slate-800' }}">{{ $tab['label'] }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">{{ $tab['hint'] }}</p>
                            </div>
                            <span class="rounded-full {{ $activeTab === $key ? 'bg-white text-indigo-700 ring-indigo-100' : 'bg-slate-100 text-slate-600 ring-slate-200' }} px-2.5 py-1 text-xs font-black ring-1">{{ $tab['orders'] }} orders</span>
                        </div>
                        <p class="mt-4 text-2xl font-black {{ $activeTab === $key ? 'text-indigo-700' : 'text-slate-950' }}">{{ $tab['qty'] }}</p>
                        <p class="text-xs font-bold text-slate-400">quantity</p>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            @forelse ($orderGroups as $group)
                @php
                    $progressTotal = max(1, (int) $group->requested_qty);
                    $progressDone = min($progressTotal, (int) $group->purchased_qty + (int) $group->problem_qty);
                    $progressPercent = (int) round(($progressDone / $progressTotal) * 100);
                    $paymentClass = $paymentBadgeClasses[$group->payment_status] ?? 'bg-slate-50 text-slate-700 ring-slate-100';
                @endphp
                <article class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('purchasing.orders.show', $group->order_id) }}" class="text-xl font-black text-indigo-700 hover:text-indigo-800">Order #{{ $group->order_number }} ↗</a>
                                <span class="rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $paymentClass }}">{{ ucfirst(str_replace('_', ' ', $group->payment_status)) }}</span>
                            </div>
                            <p class="mt-1 text-sm font-semibold text-slate-600">{{ $group->customer_name ?: 'Unknown customer' }}</p>
                            <p class="mt-2 text-xs font-bold text-slate-400">{{ $group->retailer_count }} retailer{{ $group->retailer_count === 1 ? '' : 's' }} · {{ $group->item_count }} item line{{ $group->item_count === 1 ? '' : 's' }} · {{ $group->requested_qty }} qty</p>
                        </div>
                        <a href="{{ route('purchasing.orders.show', $group->order_id) }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-slate-800">Open workspace</a>
                    </div>

                    <div class="mt-5 grid grid-cols-4 gap-2 text-center">
                        <div class="rounded-2xl bg-emerald-50 p-3 ring-1 ring-emerald-100"><p class="text-[10px] font-black uppercase text-emerald-700">To buy</p><p class="text-lg font-black text-emerald-700">{{ $group->pending_qty }}</p></div>
                        <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase text-slate-400">Bought</p><p class="text-lg font-black text-slate-900">{{ $group->purchased_qty }}</p></div>
                        <div class="rounded-2xl bg-sky-50 p-3 ring-1 ring-sky-100"><p class="text-[10px] font-black uppercase text-sky-700">Awaiting</p><p class="text-lg font-black text-sky-700">{{ $group->awaiting_arrival_qty }}</p></div>
                        <div class="rounded-2xl bg-rose-50 p-3 ring-1 ring-rose-100"><p class="text-[10px] font-black uppercase text-rose-700">Problems</p><p class="text-lg font-black text-rose-700">{{ $group->problem_qty }}</p></div>
                    </div>

                    <div class="mt-4">
                        <div class="flex items-center justify-between text-xs font-black text-slate-400">
                            <span>Purchase progress</span>
                            <span>{{ $progressPercent }}%</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-indigo-500" style="width: {{ $progressPercent }}%"></div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($group->retailer_groups as $retailer)
                            <span class="rounded-full bg-slate-50 px-2.5 py-1 text-xs font-black text-slate-600 ring-1 ring-slate-100">{{ $retailer->name }} · {{ $retailer->pending_qty }} buy · {{ $retailer->awaiting_arrival_qty }} wait</span>
                        @endforeach
                    </div>
                </article>
            @empty
                <div class="xl:col-span-2 rounded-[2rem] border border-slate-200 bg-white p-10 text-center shadow-sm">
                    <p class="text-lg font-black text-slate-900">Nothing in this tab.</p>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Try a different payment filter or clear the search.</p>
                </div>
            @endforelse
        </section>
    </div>
</x-app-layout>
