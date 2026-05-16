<x-app-layout>
    <x-slot name="header">
        Financial Checks
    </x-slot>

    <div class="space-y-6">

        <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <a href="{{ route('money-desk.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                        ← Back to Money Desk
                    </a>

                    <h1 class="mt-3 text-2xl font-bold text-slate-900">
                        Financial Checks
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Read-only checks for orders, wallet balances and refunds that may need human review.
                    </p>
                </div>

                <span class="inline-flex w-fit rounded-full bg-rose-100 px-4 py-2 text-sm font-semibold text-rose-700">
                    {{ $summary['total'] ?? 0 }} items need attention
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-6">
            <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-200">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Overpaid orders</p>
                <p class="mt-3 text-3xl font-bold text-rose-600">{{ $summary['over_settled_orders'] ?? 0 }}</p>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-200">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Paid but due</p>
                <p class="mt-3 text-3xl font-bold text-amber-600">{{ $summary['paid_but_due_orders'] ?? 0 }}</p>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-200">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">No transactions</p>
                <p class="mt-3 text-3xl font-bold text-slate-700">{{ $summary['orders_with_no_transactions'] ?? 0 }}</p>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-200">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Wallet issues</p>
                <p class="mt-3 text-3xl font-bold text-indigo-600">{{ $summary['wallet_problems'] ?? 0 }}</p>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-200">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Refund issues</p>
                <p class="mt-3 text-3xl font-bold text-purple-600">{{ $summary['refund_problems'] ?? 0 }}</p>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-200">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Loose ledger</p>
                <p class="mt-3 text-3xl font-bold text-orange-600">{{ $summary['orphan_ledger_entries'] ?? 0 }}</p>
            </div>
        </div>

        <div class="rounded-3xl border border-indigo-200 bg-indigo-50 p-6">
            <h2 class="text-lg font-bold text-indigo-900">
                How to use this page
            </h2>

            <p class="mt-2 text-sm leading-6 text-indigo-800">
                These checks do not mean something is definitely wrong. They mean “please look at this”.
                No money is changed from this screen.
            </p>
        </div>

        <div class="space-y-6">

            <x-money-desk.anomaly-section
                title="Overpaid orders"
                description="These orders appear to have more money applied than the order total."
                :items="$overSettledOrders"
                empty="No overpaid orders found."
                type="overpaid"
            />

            <x-money-desk.anomaly-section
                title="Orders marked paid/completed but still showing money due"
                description="These orders look operationally advanced, but the finance summary still shows a balance."
                :items="$paidButDueOrders"
                empty="No paid-but-due orders found."
                type="paid_due"
            />

            <x-money-desk.anomaly-section
                title="Paid/completed orders with no settlement history"
                description="These orders are marked as paid or beyond, but no order settlement rows were found."
                :items="$ordersWithNoTransactions"
                empty="No advanced orders without transactions found."
                type="no_transactions"
            />

            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-900">
                    Wallet balance checks
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Wallet credits where the remaining balance looks unusual.
                </p>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                                <th class="py-3 pr-4">Customer</th>
                                <th class="py-3 pr-4">Reason</th>
                                <th class="py-3 pr-4">Source</th>
                                <th class="py-3 pr-4 text-right">Original</th>
                                <th class="py-3 pr-4 text-right">Remaining</th>
                                <th class="py-3 pr-4 text-right">Open</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($walletProblems as $credit)
                                <tr>
                                    <td class="py-3 pr-4">
                                        <div class="font-semibold text-slate-800">
                                            {{ trim(($credit->first_name ?? '') . ' ' . ($credit->last_name ?? '')) ?: 'Unknown customer' }}
                                        </div>
                                        <div class="text-xs text-slate-400">
                                            Customer ID: {{ $credit->customer_id }}
                                        </div>
                                    </td>

                                    <td class="py-3 pr-4 text-slate-600">
                                        {{ $credit->plain_reason }}
                                    </td>

                                    <td class="py-3 pr-4 text-slate-600">
                                        {{ str_replace('_', ' ', $credit->source_type) }}
                                    </td>

                                    <td class="py-3 pr-4 text-right font-semibold text-slate-900">
                                        {{ $credit->currency ?? 'GBP' }} {{ number_format($credit->amount ?? 0, 2) }}
                                    </td>

                                    <td class="py-3 pr-4 text-right font-bold text-indigo-600">
                                        {{ $credit->currency ?? 'GBP' }} {{ number_format($credit->remaining_amount ?? 0, 2) }}
                                    </td>

                                    <td class="py-3 pr-4 text-right">
                                        <a
                                            href="{{ route('money-desk.customers.show', $credit->customer_id) }}"
                                            class="inline-flex rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                                        >
                                            Customer
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-400">
                                        No wallet balance problems found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <x-money-desk.anomaly-section
                title="Refund checks"
                description="These orders appear to have more refunded than was paid or used from wallet."
                :items="$refundProblems"
                empty="No refund problems found."
                type="refund"
            />

            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-900">
                    Loose real-money ledger entries
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    These are real-money entries without a source link. Some may be valid legacy rows, but they are worth checking.
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
                            @forelse ($orphanLedgerEntries as $entry)
                                <tr>
                                    <td class="py-3 pr-4 text-slate-500">
                                        {{ $entry->occurred_at ? \Carbon\Carbon::parse($entry->occurred_at)->format('d M Y') : '—' }}
                                    </td>

                                    <td class="py-3 pr-4">
                                        <div class="font-semibold text-slate-800">
                                            {{ trim(($entry->first_name ?? '') . ' ' . ($entry->last_name ?? '')) ?: 'Unknown customer' }}
                                        </div>
                                        <div class="text-xs text-slate-400">
                                            Customer ID: {{ $entry->customer_id }}
                                        </div>
                                    </td>

                                    <td class="py-3 pr-4 text-slate-600">
                                        {{ str_replace('_', ' ', $entry->type) }}
                                    </td>

                                    <td class="py-3 pr-4 text-slate-600">
                                        {{ $entry->reference ?: '—' }}
                                    </td>

                                    <td class="py-3 pr-4 text-right font-semibold text-slate-900">
                                        {{ $entry->currency ?? 'GBP' }} {{ number_format($entry->amount ?? 0, 2) }}
                                    </td>

                                    <td class="py-3 pr-4 text-right">
                                        <a
                                            href="{{ route('money-desk.customers.show', $entry->customer_id) }}"
                                            class="inline-flex rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                                        >
                                            Customer
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-400">
                                        No loose ledger entries found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</x-app-layout>