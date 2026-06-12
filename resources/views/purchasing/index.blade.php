<x-app-layout>
    <x-slot name="header">Purchasing Desk</x-slot>

    @php
        $activeStatus = $filters['status'] ?? 'to_buy';
        $statusTabs = [
            'to_buy' => ['label' => 'To Buy', 'qty' => $summary['to_buy_qty'] ?? 0, 'orders' => $summary['to_buy_orders'] ?? 0],
            'problems' => ['label' => 'Problems', 'qty' => $summary['problem_qty'] ?? 0, 'orders' => $summary['problem_orders'] ?? 0],
            'awaiting_arrival' => ['label' => 'Awaiting Arrival', 'qty' => $summary['awaiting_arrival_qty'] ?? 0, 'orders' => $summary['awaiting_arrival_orders'] ?? 0],
        ];
        $problemLabels = [
            'supplier_cancelled' => 'Supplier cancelled',
            'lost' => 'Lost',
            'damaged' => 'Damaged',
            'wrong_item' => 'Wrong item',
            'retailer_refunded' => 'Retailer refunded',
            'unavailable' => 'Unavailable',
            'other' => 'Other',
        ];
    @endphp

    <div class="space-y-5">
        <section class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-950">Purchasing Desk</h1>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Order-first purchasing. Each customer order is bought as its own basket, even when the retailer is the same.</p>
                </div>

                <div class="rounded-2xl bg-indigo-50 px-4 py-3 text-sm font-black text-indigo-800 ring-1 ring-indigo-100">
                    {{ number_format($orderGroups->count()) }} order{{ $orderGroups->count() === 1 ? '' : 's' }} shown
                </div>
            </div>

            @if (session('status'))
                <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                @foreach ($statusTabs as $key => $tab)
                    @php
                        $isActive = $activeStatus === $key;
                        $url = route('purchasing.index', array_filter(['status' => $key, 'q' => $filters['q'] ?? null], fn ($v) => $v !== null && $v !== ''));
                    @endphp
                    <a href="{{ $url }}" class="rounded-3xl border p-4 shadow-sm transition {{ $isActive ? 'border-indigo-200 bg-indigo-50 ring-2 ring-indigo-100' : 'border-slate-200 bg-white hover:bg-slate-50' }}">
                        <span class="block text-sm font-black {{ $isActive ? 'text-indigo-800' : 'text-slate-600' }}">{{ $tab['label'] }}</span>
                        <span class="mt-2 block text-3xl font-black tracking-tight text-slate-950">{{ number_format($tab['qty']) }}</span>
                        <span class="mt-1 block text-xs font-bold text-slate-400">{{ number_format($tab['orders']) }} order{{ $tab['orders'] == 1 ? '' : 's' }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('purchasing.index') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_220px_auto] lg:items-center">
                <input type="hidden" name="status" value="{{ $activeStatus }}">
                <div>
                    <label for="q" class="sr-only">Search purchasing</label>
                    <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" type="text" placeholder="Search order number, customer, item, product code or retailer..." class="h-12 w-full rounded-2xl border-slate-300 px-4 text-sm font-semibold shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <select name="status" class="h-12 rounded-2xl border-slate-300 px-4 text-sm font-black text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ($statusTabs as $key => $tab)
                        <option value="{{ $key }}" @selected($activeStatus === $key)>{{ $tab['label'] }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="h-12 rounded-2xl bg-indigo-600 px-5 text-sm font-black text-white shadow-sm transition hover:bg-indigo-700">Search</button>
                    <a href="{{ route('purchasing.index', ['status' => $activeStatus]) }}" class="inline-flex h-12 items-center rounded-2xl border border-slate-200 px-5 text-sm font-black text-slate-600 transition hover:bg-slate-50">Clear</a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-4">
                <div class="grid grid-cols-[110px_minmax(180px,1fr)_150px_150px_120px] gap-4 text-left text-xs font-black uppercase tracking-wide text-slate-400">
                    <div>Order</div>
                    <div>Customer</div>
                    <div>Action</div>
                    <div>Payment</div>
                    <div class="text-right">Open</div>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($orderGroups as $orderGroup)
                    @php
                        $actionLabel = 'Review';
                        $actionClass = 'bg-slate-100 text-slate-700 ring-slate-200';

                        if ((int) $orderGroup->problem_qty > 0) {
                            $actionLabel = 'Resolve problem';
                            $actionClass = 'bg-rose-50 text-rose-700 ring-rose-100';
                        } elseif ((int) $orderGroup->pending_qty > 0) {
                            $actionLabel = 'Buy items';
                            $actionClass = 'bg-emerald-50 text-emerald-700 ring-emerald-100';
                        } elseif ((int) $orderGroup->awaiting_arrival_qty > 0) {
                            $actionLabel = 'Await arrival';
                            $actionClass = 'bg-amber-50 text-amber-700 ring-amber-100';
                        }

                        $paymentLabel = ucfirst(str_replace('_', ' ', (string) ($orderGroup->payment_status ?? 'unknown')));
                    @endphp

                    <div class="grid grid-cols-[110px_minmax(180px,1fr)_150px_150px_120px] items-center gap-4 px-5 py-4 transition hover:bg-slate-50/80">
                        <div>
                            <p class="text-base font-black text-slate-950">{{ $orderGroup->order_number ?: '#' . $orderGroup->order_id }}</p>
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-slate-800">{{ $orderGroup->customer_name ?: 'Customer not named' }}</p>
                        </div>

                        <div>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 {{ $actionClass }}">{{ $actionLabel }}</span>
                        </div>

                        <div>
                            <span class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700 ring-1 ring-indigo-100">{{ $paymentLabel }}</span>
                        </div>

                        <div class="text-right">
                            <a href="{{ route('orders.show', $orderGroup->order_id) }}" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-xs font-black text-white transition hover:bg-slate-800">Open ↗</a>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-12 text-center">
                        <p class="text-lg font-black text-slate-900">Nothing in this purchasing queue.</p>
                        <p class="mt-1 text-sm font-semibold text-slate-500">Try a different tab or clear the search.</p>
                    </div>
                @endforelse
            </div>
        </section>

        @if ($recentEvents->isNotEmpty())
            <section class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Recent purchasing events</h2>
                <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Order</th>
                                <th class="px-4 py-3">Item</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Qty</th>
                                <th class="px-4 py-3">Ref</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($recentEvents as $event)
                                <tr>
                                    <td class="px-4 py-3 font-black text-slate-800">{{ $event->order_number }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-600">{{ Str::limit($event->item_name, 70) }}</td>
                                    <td class="px-4 py-3 font-black {{ in_array($event->status, ['failed', 'problem']) ? 'text-rose-700' : 'text-emerald-700' }}">{{ str_replace('_', ' ', ucfirst($event->problem_code ?: $event->status)) }}</td>
                                    <td class="px-4 py-3 font-black text-slate-700">{{ $event->qty }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-500">{{ $event->retailer_order_reference ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
