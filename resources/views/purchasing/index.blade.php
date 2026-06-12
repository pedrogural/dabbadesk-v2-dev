<x-app-layout>
    <x-slot name="header">
        Purchasing Desk
    </x-slot>

    @php
        $badgeClasses = [
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'part_paid' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'unpaid' => 'bg-slate-50 text-slate-600 ring-slate-200',
        ];
        $actionClasses = [
            'Buy Items' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
            'Await Arrival' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'Resolve Problem' => 'bg-rose-50 text-rose-700 ring-rose-200',
            'Completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        ];
    @endphp

    <div class="space-y-6">
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-gradient-to-br from-slate-50 to-white px-5 py-5 sm:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-indigo-500">Order-first purchasing</p>
                        <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950">Purchasing queue</h2>
                        <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                            A calm queue of customer orders needing purchasing attention. Details live inside the workspace.
                        </p>
                    </div>

                    <form method="GET" action="{{ route('purchasing.index') }}" class="flex w-full flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm lg:w-auto lg:min-w-[520px] lg:flex-row lg:items-center">
                        <input type="hidden" name="tab" value="{{ $filters['tab'] }}">
                        <label class="sr-only" for="purchasing-q">Search purchasing queue</label>
                        <input
                            id="purchasing-q"
                            name="q"
                            value="{{ $filters['q'] }}"
                            placeholder="Search order, customer, item or retailer"
                            class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800 placeholder:text-slate-400 focus:border-indigo-300 focus:bg-white focus:ring-indigo-200"
                        >
                        <select name="payment" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-bold text-slate-700 focus:border-indigo-300 focus:bg-white focus:ring-indigo-200">
                            @foreach ($paymentOptions as $value => $label)
                                <option value="{{ $value }}" @selected($filters['payment'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-black text-white shadow-sm transition hover:bg-indigo-700">
                            Search
                        </button>
                    </form>
                </div>
            </div>

            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex flex-wrap gap-2">
                    @foreach ($tabs as $key => $tab)
                        @php
                            $isActive = $filters['tab'] === $key;
                            $url = route('purchasing.index', array_filter([
                                'tab' => $key,
                                'payment' => $filters['payment'],
                                'q' => $filters['q'],
                            ], fn ($value) => $value !== null && $value !== ''));
                        @endphp
                        <a
                            href="{{ $url }}"
                            class="inline-flex items-center gap-2 rounded-2xl px-4 py-2 text-sm font-black ring-1 transition {{ $isActive ? 'bg-indigo-600 text-white ring-indigo-600 shadow-sm shadow-indigo-100' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50 hover:text-slate-900' }}"
                        >
                            <span>{{ $tab['label'] }}</span>
                            <span class="rounded-full px-2 py-0.5 text-[11px] {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">{{ $tab['count'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">
                        <tr>
                            <th class="px-5 py-3 sm:px-6">Order</th>
                            <th class="px-5 py-3">Customer</th>
                            <th class="px-5 py-3">Action</th>
                            <th class="px-5 py-3">Payment</th>
                            <th class="px-5 py-3 text-right">Qty</th>
                            <th class="px-5 py-3 text-right">Open</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($orders as $queueOrder)
                            <tr class="transition hover:bg-indigo-50/30">
                                <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                    <div class="font-black text-slate-950">#{{ $queueOrder['order_number'] }}</div>
                                    <div class="mt-1 text-xs font-semibold text-slate-400">{{ $queueOrder['order_status'] ?: 'active' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="max-w-[320px] truncate font-bold text-slate-800">{{ $queueOrder['customer'] }}</div>
                                    <div class="mt-1 max-w-[320px] truncate text-xs font-semibold text-slate-400">{{ $queueOrder['email'] }}</div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $actionClasses[$queueOrder['action']] ?? 'bg-slate-50 text-slate-600 ring-slate-200' }}">
                                        {{ $queueOrder['action'] }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $badgeClasses[$queueOrder['payment_status']] ?? 'bg-slate-50 text-slate-600 ring-slate-200' }}">
                                        {{ str_replace('_', '-', ucfirst($queueOrder['payment_status'])) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <div class="font-black text-slate-900">
                                        @if ($filters['tab'] === 'awaiting_arrival')
                                            {{ $queueOrder['awaiting_arrival_qty'] }} awaiting
                                        @elseif ($filters['tab'] === 'problems')
                                            {{ $queueOrder['problem_qty'] }} problem
                                        @elseif ($filters['tab'] === 'completed')
                                            {{ $queueOrder['purchased_qty'] }} bought
                                        @else
                                            {{ $queueOrder['remaining_to_buy_qty'] }} to buy
                                        @endif
                                    </div>
                                    <div class="mt-1 text-xs font-semibold text-slate-400">{{ $queueOrder['retailer_count'] }} retailer{{ $queueOrder['retailer_count'] === 1 ? '' : 's' }}</div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <a href="{{ route('purchasing.orders.show', $queueOrder['order_id']) }}" class="inline-flex items-center rounded-xl bg-slate-950 px-3 py-2 text-xs font-black text-white shadow-sm transition hover:bg-indigo-700">
                                        Open Workspace
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-14 text-center sm:px-6">
                                    <div class="text-lg font-black text-slate-800">Nothing here</div>
                                    <p class="mt-2 text-sm font-medium text-slate-500">This queue has no matching orders right now.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
