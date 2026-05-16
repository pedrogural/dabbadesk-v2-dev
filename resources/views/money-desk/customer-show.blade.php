<x-app-layout>
    <x-slot name="header">
        Customer Finance
    </x-slot>

    <div class="space-y-6">

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <a
                        href="{{ route('money-desk.index') }}"
                        class="text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                    >
                        ← Back to Money Desk
                    </a>

                    <h1 class="mt-3 text-2xl font-bold text-slate-900">
                        {{ trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: 'Unnamed customer' }}
                    </h1>

                    <div class="mt-2 flex flex-wrap gap-2 text-sm text-slate-500">
                        <span>Customer ID: {{ $customer->id }}</span>

                        @if ($customer->company_name)
                            <span>· {{ $customer->company_name }}</span>
                        @endif

                        @if ($customer->primary_email)
                            <span>· {{ $customer->primary_email }}</span>
                        @endif

                        @if ($customer->primary_phone)
                            <span>· {{ $customer->primary_phone }}</span>
                        @endif
                    </div>
                </div>

                <span class="inline-flex w-fit rounded-full bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-700">
                    Read-only customer finance
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Customer has paid</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">
                    £{{ number_format($summary['payments_received'] ?? 0, 2) }}
                </p>
                <p class="mt-2 text-xs text-slate-400">Real money received from this customer</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Used on orders</p>
                <p class="mt-3 text-3xl font-bold text-emerald-600">
                    £{{ number_format($summary['order_settled'] ?? 0, 2) }}
                </p>
                <p class="mt-2 text-xs text-slate-400">Payments and wallet balance used to settle orders</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Wallet available</p>
                <p class="mt-3 text-3xl font-bold text-indigo-600">
                    £{{ number_format($summary['wallet_available'] ?? 0, 2) }}
                </p>
                <p class="mt-2 text-xs text-slate-400">Reusable balance owed to this customer</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Refunded out</p>
                <p class="mt-3 text-3xl font-bold text-rose-600">
                    £{{ number_format($summary['refunds_paid_out'] ?? 0, 2) }}
                </p>
                <p class="mt-2 text-xs text-slate-400">Money sent back outside the wallet</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
                <h2 class="text-lg font-bold text-slate-900">
                    Plain-English finance timeline
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    This combines payments, wallet events and order settlement events into one readable history.
                </p>

                <div class="mt-6 space-y-4">
                    @forelse ($timeline as $event)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="font-semibold text-slate-900">
                                        {{ $event->plain_label }}
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $event->plain_explanation }}
                                    </p>

                                    <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-400">
                                        <span>
                                            {{ $event->event_at ? \Carbon\Carbon::parse($event->event_at)->format('d M Y H:i') : 'No date' }}
                                        </span>

                                        @if ($event->order_number)
                                            <span>· Order #{{ $event->order_number }}</span>
                                        @elseif ($event->order_id)
                                            <span>· Order ID {{ $event->order_id }}</span>
                                        @endif

                                        @if ($event->reference)
                                            <span>· Ref: {{ $event->reference }}</span>
                                        @endif
                                    </div>

                                    @if ($event->note)
                                        <p class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-600">
                                            {{ $event->note }}
                                        </p>
                                    @endif
                                </div>

                                <div class="text-right">
                                    <p class="text-lg font-bold text-slate-900">
                                        {{ $event->currency ?? 'GBP' }} {{ number_format($event->amount ?? 0, 2) }}
                                    </p>

                                    <p class="mt-1 text-xs uppercase tracking-wide text-slate-400">
                                        {{ $event->source }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                            No financial timeline events found for this customer.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900">
                        Wallet credits
                    </h2>

                    <div class="mt-5 space-y-3">
                        @forelse ($walletCredits as $credit)
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-800">
                                            {{ str_replace('_', ' ', ucfirst($credit->source_type)) }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-400">
                                            {{ $credit->created_at ? \Carbon\Carbon::parse($credit->created_at)->format('d M Y') : 'No date' }}
                                        </p>
                                    </div>

                                    <span
                                        class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700"
                                    >
                                        {{ $credit->status }}
                                    </span>
                                </div>

                                <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <p class="text-slate-400">Original</p>
                                        <p class="font-semibold text-slate-800">
                                            {{ $credit->currency ?? 'GBP' }}
                                            {{ number_format($credit->amount ?? 0, 2) }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-slate-400">Available</p>
                                        <p class="font-bold text-indigo-600">
                                            {{ $credit->currency ?? 'GBP' }}
                                            {{ number_format($credit->remaining_amount ?? 0, 2) }}
                                        </p>
                                    </div>
                                </div>

                                @if ($credit->notes)
                                    <p class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-600">
                                        {{ $credit->notes }}
                                    </p>
                                @endif
                            </div>
                        @empty
                            <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                                No wallet credits found.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">
                Customer orders finance overview
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Plain view of order total, how much has been settled, and what still appears due.
            </p>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                            <th class="py-3 pr-4">Order</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4 text-right">Order total</th>
                            <th class="py-3 pr-4 text-right">Settled</th>
                            <th class="py-3 pr-4 text-right">Still due</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($orders as $order)
                            <tr>
                                <td class="py-3 pr-4">
                                    <div class="font-semibold text-slate-800">
                                        #{{ $order->order_number }}
                                    </div>
                                    <div class="text-xs text-slate-400">
                                        {{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d M Y') : 'No date' }}
                                    </div>
                                </td>

                                <td class="py-3 pr-4">
                                    <span
                                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600"
                                    >
                                        {{ str_replace('_', ' ', $order->status) }}
                                    </span>
                                </td>

                                <td class="py-3 pr-4 text-right font-semibold text-slate-900">
                                    {{ $order->currency ?? 'GBP' }} {{ number_format($order->grand_total ?? 0, 2) }}
                                </td>

                                <td class="py-3 pr-4 text-right font-semibold text-emerald-600">
                                    {{ $order->currency ?? 'GBP' }} {{ number_format($order->settled_total ?? 0, 2) }}
                                </td>

                                <td
                                    class="{{ ($order->balance_due ?? 0) > 0 ? 'text-rose-600' : 'text-slate-400' }} py-3 pr-4 text-right font-bold">
                                    {{ $order->currency ?? 'GBP' }} {{ number_format($order->balance_due ?? 0, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="py-6 text-center text-slate-400"
                                >
                                    No orders found for this customer.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
