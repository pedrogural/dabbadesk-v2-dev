<x-app-layout>
    <x-slot name="header">
        Dashboard
    </x-slot>

    <div class="space-y-6">

        <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        DabbaDesk Dashboard
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Live read-only overview of orders, finance and operational attention points.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a
                        href="{{ route('money-desk.index') }}"
                        class="inline-flex rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        Open Money Desk
                    </a>

                    <a
                        href="{{ route('money-desk.anomalies') }}"
                        class="inline-flex rounded-2xl bg-rose-600 px-4 py-3 text-sm font-semibold text-white hover:bg-rose-700"
                    >
                        Financial checks
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-rose-200 bg-rose-50 p-6 shadow-sm">
                <p class="text-sm font-semibold text-rose-700">
                    Needs finance attention
                </p>

                <p class="mt-3 text-4xl font-bold text-rose-700">
                    {{ $alerts['finance_anomalies'] ?? 0 }}
                </p>

                <p class="mt-2 text-sm text-rose-700/80">
                    Orders, wallet rows or ledger entries that may need review.
                </p>

                <a
                    href="{{ route('money-desk.anomalies') }}"
                    class="mt-5 inline-flex rounded-2xl bg-white px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100"
                >
                    Review checks
                </a>
            </div>

            <div class="rounded-3xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                <p class="text-sm font-semibold text-amber-700">
                    Waiting for payment
                </p>

                <p class="mt-3 text-4xl font-bold text-amber-700">
                    {{ $operations['orders_waiting_payment'] ?? 0 }}
                </p>

                <p class="mt-2 text-sm text-amber-700/80">
                    Orders that still appear to need customer payment.
                </p>
            </div>

            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                <p class="text-sm font-semibold text-emerald-700">
                    Waiting to purchase
                </p>

                <p class="mt-3 text-4xl font-bold text-emerald-700">
                    {{ $operations['orders_waiting_purchase'] ?? 0 }}
                </p>

                <p class="mt-2 text-sm text-emerald-700/80">
                    Paid orders that may need purchasing work.
                </p>
            </div>

            <div class="rounded-3xl border border-indigo-200 bg-indigo-50 p-6 shadow-sm">
                <p class="text-sm font-semibold text-indigo-700">
                    Wallet liability
                </p>

                <p class="mt-3 text-4xl font-bold text-indigo-700">
                    £{{ number_format($finance['wallet_liability'] ?? 0, 2) }}
                </p>

                <p class="mt-2 text-sm text-indigo-700/80">
                    Customer balance currently owed and reusable.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">Orders created today</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">
                    {{ $today['orders_created_today'] ?? 0 }}
                </p>
                <p class="mt-2 text-xs text-slate-400">New order snapshots</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">Payments today</p>
                <p class="mt-3 text-3xl font-bold text-emerald-600">
                    £{{ number_format($today['payments_received_today'] ?? 0, 2) }}
                </p>
                <p class="mt-2 text-xs text-slate-400">Real money received today</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">Arrivals today</p>
                <p class="mt-3 text-3xl font-bold text-indigo-600">
                    {{ $operations['arrivals_today'] ?? 0 }}
                </p>
                <p class="mt-2 text-xs text-slate-400">Warehouse arrival assignments</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">Ready for collection / delivery</p>
                <p class="mt-3 text-3xl font-bold text-purple-600">
                    {{ $operations['orders_ready_for_collection'] ?? 0 }}
                </p>
                <p class="mt-2 text-xs text-slate-400">Customer-facing release work</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-900">
                    Finance attention breakdown
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    These are read-only warning counters.
                </p>

                <div class="mt-5 space-y-3">
                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <span class="text-sm text-slate-600">Overpaid orders</span>
                        <span class="font-bold text-rose-600">{{ $alerts['over_settled_orders'] ?? 0 }}</span>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <span class="text-sm text-slate-600">Paid but still due</span>
                        <span class="font-bold text-amber-600">{{ $alerts['paid_but_due_orders'] ?? 0 }}</span>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <span class="text-sm text-slate-600">No settlement history</span>
                        <span class="font-bold text-slate-700">{{ $alerts['orders_with_no_transactions'] ?? 0 }}</span>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <span class="text-sm text-slate-600">Wallet issues</span>
                        <span class="font-bold text-indigo-600">{{ $alerts['wallet_problems'] ?? 0 }}</span>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <span class="text-sm text-slate-600">Refund issues</span>
                        <span class="font-bold text-purple-600">{{ $alerts['refund_problems'] ?? 0 }}</span>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <span class="text-sm text-slate-600">Loose real-money entries</span>
                        <span class="font-bold text-orange-600">{{ $alerts['loose_ledger_entries'] ?? 0 }}</span>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-2 rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-900">
                    Recent orders
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Latest order snapshots with basic payment position.
                </p>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                                <th class="py-3 pr-4">Order</th>
                                <th class="py-3 pr-4">Customer</th>
                                <th class="py-3 pr-4">Status</th>
                                <th class="py-3 pr-4 text-right">Total</th>
                                <th class="py-3 pr-4 text-right">Due</th>
                                <th class="py-3 pr-4 text-right">Open</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td class="py-3 pr-4">
                                        <div class="font-semibold text-slate-800">
                                            #{{ $order->order_number }}
                                        </div>
                                        <div class="text-xs text-slate-400">
                                            {{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d M Y') : 'No date' }}
                                        </div>
                                    </td>

                                    <td class="py-3 pr-4 text-slate-700">
                                        {{ $order->bill_to_name ?: trim(($order->first_name ?? '') . ' ' . ($order->last_name ?? '')) ?: 'Unknown customer' }}
                                    </td>

                                    <td class="py-3 pr-4">
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                            {{ str_replace('_', ' ', $order->status) }}
                                        </span>
                                    </td>

                                    <td class="py-3 pr-4 text-right font-semibold text-slate-900">
                                        £{{ number_format($order->grand_total ?? 0, 2) }}
                                    </td>

                                    <td class="py-3 pr-4 text-right font-bold {{ ($order->balance_due ?? 0) > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                        £{{ number_format($order->balance_due ?? 0, 2) }}
                                    </td>

                                    <td class="py-3 pr-4 text-right">
                                        <a
                                            href="{{ route('money-desk.orders.show', $order->id) }}"
                                            class="inline-flex rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                                        >
                                            Finance
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-400">
                                        No recent orders found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
            <h2 class="text-lg font-bold text-slate-900">
                Recent money movement
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Real money ledger events: payments and refunds.
            </p>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                            <th class="py-3 pr-4">Date</th>
                            <th class="py-3 pr-4">Customer</th>
                            <th class="py-3 pr-4">Type</th>
                            <th class="py-3 pr-4">Reference</th>
                            <th class="py-3 pr-4 text-right">Amount</th>
                            <th class="py-3 pr-4 text-right">Open</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentPayments as $payment)
                            <tr>
                                <td class="py-3 pr-4 text-slate-500">
                                    {{ $payment->occurred_at ? \Carbon\Carbon::parse($payment->occurred_at)->format('d M Y') : '—' }}
                                </td>

                                <td class="py-3 pr-4">
                                    <div class="font-semibold text-slate-800">
                                        {{ trim(($payment->first_name ?? '') . ' ' . ($payment->last_name ?? '')) ?: 'Unknown customer' }}
                                    </div>

                                    @if ($payment->company_name)
                                        <div class="text-xs text-slate-400">
                                            {{ $payment->company_name }}
                                        </div>
                                    @endif
                                </td>

                                <td class="py-3 pr-4">
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                        {{ str_replace('_', ' ', $payment->type) }}
                                    </span>
                                </td>

                                <td class="py-3 pr-4 text-slate-600">
                                    {{ $payment->reference ?: '—' }}
                                </td>

                                <td class="py-3 pr-4 text-right font-semibold text-slate-900">
                                    {{ $payment->currency ?? 'GBP' }} {{ number_format($payment->amount ?? 0, 2) }}
                                </td>

                                <td class="py-3 pr-4 text-right">
                                    <a
                                        href="{{ route('money-desk.customers.show', $payment->customer_id) }}"
                                        class="inline-flex rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                                    >
                                        Customer
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-400">
                                    No recent money movement found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
