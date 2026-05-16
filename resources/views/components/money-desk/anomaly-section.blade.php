@props([
    'title',
    'description',
    'items',
    'empty',
    'type' => 'order',
])

<div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
    <h2 class="text-lg font-bold text-slate-900">
        {{ $title }}
    </h2>

    <p class="mt-1 text-sm text-slate-500">
        {{ $description }}
    </p>

    <div class="mt-5 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                    <th class="py-3 pr-4">Order</th>
                    <th class="py-3 pr-4">Customer</th>
                    <th class="py-3 pr-4">Status</th>
                    <th class="py-3 pr-4 text-right">Order total</th>

                    @if ($type === 'refund')
                        <th class="py-3 pr-4 text-right">Paid/used</th>
                        <th class="py-3 pr-4 text-right">Refunded</th>
                    @elseif ($type === 'overpaid')
                        <th class="py-3 pr-4 text-right">Settled</th>
                        <th class="py-3 pr-4 text-right">Over by</th>
                    @elseif ($type === 'paid_due')
                        <th class="py-3 pr-4 text-right">Settled</th>
                        <th class="py-3 pr-4 text-right">Still due</th>
                    @else
                        <th class="py-3 pr-4 text-right">Transactions</th>
                    @endif

                    <th class="py-3 pr-4 text-right">Open</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse ($items as $item)
                    <tr>
                        <td class="py-3 pr-4">
                            <div class="font-semibold text-slate-800">
                                #{{ $item->order_number }}
                            </div>
                            <div class="text-xs text-slate-400">
                                {{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d M Y') : 'No date' }}
                            </div>
                        </td>

                        <td class="py-3 pr-4">
                            <div class="font-semibold text-slate-800">
                                {{ $item->bill_to_name ?: trim(($item->first_name ?? '') . ' ' . ($item->last_name ?? '')) ?: 'Unknown customer' }}
                            </div>
                            <div class="text-xs text-slate-400">
                                Customer ID: {{ $item->customer_id }}
                            </div>
                        </td>

                        <td class="py-3 pr-4">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                {{ str_replace('_', ' ', $item->status) }}
                            </span>
                        </td>

                        <td class="py-3 pr-4 text-right font-semibold text-slate-900">
                            £{{ number_format($item->grand_total ?? 0, 2) }}
                        </td>

                        @if ($type === 'refund')
                            <td class="py-3 pr-4 text-right font-semibold text-slate-900">
                                £{{ number_format($item->payments_and_wallet_used ?? 0, 2) }}
                            </td>
                            <td class="py-3 pr-4 text-right font-bold text-purple-600">
                                £{{ number_format($item->refunds_total ?? 0, 2) }}
                            </td>
                        @elseif ($type === 'overpaid')
                            <td class="py-3 pr-4 text-right font-semibold text-slate-900">
                                £{{ number_format($item->settled_total ?? 0, 2) }}
                            </td>
                            <td class="py-3 pr-4 text-right font-bold text-rose-600">
                                £{{ number_format($item->difference_amount ?? 0, 2) }}
                            </td>
                        @elseif ($type === 'paid_due')
                            <td class="py-3 pr-4 text-right font-semibold text-slate-900">
                                £{{ number_format($item->settled_total ?? 0, 2) }}
                            </td>
                            <td class="py-3 pr-4 text-right font-bold text-amber-600">
                                £{{ number_format($item->balance_due ?? 0, 2) }}
                            </td>
                        @else
                            <td class="py-3 pr-4 text-right font-semibold text-slate-900">
                                {{ $item->transaction_count ?? 0 }}
                            </td>
                        @endif

                        <td class="py-3 pr-4 text-right">
                            <a
                                href="{{ route('money-desk.orders.show', $item->id) }}"
                                class="inline-flex rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                            >
                                Order
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-slate-400">
                            {{ $empty }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>