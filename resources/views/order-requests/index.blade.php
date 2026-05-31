<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Order Requests</h2>
                <p class="mt-1 text-sm text-gray-500">New public order requests waiting for review and conversion.</p>
            </div>

            <span class="rounded-full bg-indigo-100 px-4 py-2 text-sm font-bold text-indigo-700">
                {{ $newRequestCount }} new
            </span>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form
            id="order-request-filters"
            method="GET"
            action="{{ route('order-requests.index') }}"
            class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
        >
            <div class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)] lg:items-start">
                <div class="flex flex-col">
                    <label
                        for="q"
                        class="text-xs font-bold uppercase tracking-wide text-gray-500"
                    >
                        Search
                    </label>

                    <input
                        id="q"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Ref, customer, email or phone"
                        autocomplete="off"
                        class="mt-2 w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >

                    <p class="mt-1 text-xs text-gray-400">
                        Search updates automatically after a short pause.
                    </p>
                </div>

                <div class="flex flex-col">
                    <label
                        for="status"
                        class="text-xs font-bold uppercase tracking-wide text-gray-500"
                    >
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="mt-2 w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="open" @selected($status === 'open')>Needs Action</option>
                        <option value="all" @selected($status === 'all')>All</option>
                        <option value="received" @selected($status === 'received')>Received</option>
                        <option value="reviewing" @selected($status === 'reviewing')>Reviewing</option>
                        <option value="converted" @selected($status === 'converted')>Converted</option>
                        <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
                    </select>
                </div>
            </div>

            <noscript>
                <div class="mt-4">
                    <button
                        type="submit"
                        class="rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-black text-white shadow-sm hover:bg-indigo-700"
                    >
                        Filter
                    </button>
                </div>
            </noscript>
        </form>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Ref</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Customer</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Email</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Notes</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-gray-500">Estimate</th>
                            <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-gray-500">Received</th>
                            <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-gray-500">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($requests as $request)
                            @php
                                $name = trim(
                                    ($request->customer_first_name ?? '') . ' ' . ($request->customer_last_name ?? '')
                                );

                                if ($name === '') {
                                    $name = $request->customer_company_name ?: 'Unknown customer';
                                }

                                $statusClass = match ((string) $request->status) {
                                    'converted' => 'bg-emerald-100 text-emerald-800',
                                    'cancelled' => 'bg-rose-100 text-rose-800',
                                    'reviewing' => 'bg-indigo-100 text-indigo-800',
                                    default => $request->converted_at
                                        ? 'bg-emerald-100 text-emerald-800'
                                        : 'bg-amber-100 text-amber-800',
                                };
                            @endphp

                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-black text-indigo-700">
                                    {{ $request->request_ref }}
                                </td>

                                <td class="px-5 py-4 text-sm font-semibold text-gray-900">
                                    {{ $name }}

                                    @if ($request->customer_company_name)
                                        <div class="text-xs font-normal text-gray-500">
                                            {{ $request->customer_company_name }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-5 py-4 text-sm text-gray-600">
                                    {{ $request->customer_email ?: '—' }}
                                </td>

                                <td class="max-w-xs px-5 py-4 text-sm text-gray-600">
                                    @if (trim((string) ($request->notes ?? '')) !== '')
                                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold leading-5 text-amber-900">
                                            <span class="mb-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-amber-700">
                                                Customer note
                                            </span>

                                            <div class="line-clamp-2">
                                                {{ \Illuminate\Support\Str::limit($request->notes, 120) }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                <td class="px-5 py-4">
                                    <span class="{{ $statusClass }} rounded-full px-3 py-1 text-xs font-bold">
                                        {{ ucfirst($request->status ?: 'received') }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold text-gray-900">
                                    £{{ number_format((float) $request->estimated_total, 2) }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($request->submitted_at ?: $request->created_at)->format('d/m/Y H:i') }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <a
                                        href="{{ route('order-requests.show', $request->id) }}"
                                        class="rounded-xl bg-slate-900 px-4 py-2 font-bold text-white hover:bg-slate-700"
                                    >
                                        Open
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="8"
                                    class="px-5 py-10 text-center text-sm text-gray-500"
                                >
                                    No order requests found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $requests->links() }}</div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('order-request-filters');
            const search = document.getElementById('q');
            const status = document.getElementById('status');

            if (!form || !search || !status) {
                return;
            }

            let filterTimer = null;

            const submitFilters = () => {
                if (filterTimer) {
                    window.clearTimeout(filterTimer);
                }

                form.requestSubmit();
            };

            search.addEventListener('input', () => {
                if (filterTimer) {
                    window.clearTimeout(filterTimer);
                }

                filterTimer = window.setTimeout(() => {
                    form.requestSubmit();
                }, 1200);
            });

            status.addEventListener('change', submitFilters);
        });
    </script>
</x-app-layout>