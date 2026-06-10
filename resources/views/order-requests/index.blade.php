<x-app-layout>
    <x-slot name="header">
        Order Requests
    </x-slot>

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-sm font-black text-indigo-700">
                        {{ $newRequestCount }} new
                    </span>
                </div>

                <p class="mt-2 text-sm font-bold text-slate-700">
                    New public order requests waiting for review and conversion.
                </p>
            </div>

            <a
                href="{{ route('order-requests.create-manual') }}"
                class="inline-flex h-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-r from-violet-600 to-indigo-600 px-6 text-sm font-black text-white shadow-lg shadow-indigo-200 transition hover:from-violet-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100"
            >
                + New Request
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800 shadow-sm">
                {{ session('status') }}
            </div>
        @endif

        <form
            id="order-request-filters"
            method="GET"
            action="{{ route('order-requests.index') }}"
            class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6 lg:p-7"
        >
            <div class="flex flex-col gap-5 xl:flex-row xl:items-start">
                <div class="w-full max-w-3xl">
                    <label for="q" class="block text-xs font-black uppercase tracking-wide text-slate-700">Search</label>

                    <div class="relative mt-3">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="m17 17-3.8-3.8m1.55-4.45a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                        </div>

                        <input
                            id="q"
                            name="q"
                            value="{{ $search }}"
                            placeholder="Ref, customer, email or phone"
                            autocomplete="off"
                            class="block h-14 w-full rounded-2xl border border-slate-200 bg-white pl-12 pr-4 text-sm font-semibold text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <p class="mt-3 text-xs font-medium text-slate-400">
                        Search updates automatically after a short pause.
                    </p>
                </div>

                <div class="w-full max-w-xs xl:ml-6">
                    <label for="status" class="block text-xs font-black uppercase tracking-wide text-slate-700">Status</label>

                    <select
                        id="status"
                        name="status"
                        class="mt-3 block h-14 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-900 shadow-sm transition focus:border-indigo-500 focus:ring-indigo-500"
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
                <div class="mt-5">
                    <button type="submit" class="rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-black text-white shadow-sm hover:bg-indigo-700">
                        Filter
                    </button>
                </div>
            </noscript>
        </form>

        <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="whitespace-nowrap px-6 py-5 text-left text-xs font-black uppercase tracking-wide text-slate-500">Ref</th>
                            <th class="whitespace-nowrap px-6 py-5 text-left text-xs font-black uppercase tracking-wide text-slate-500">Source</th>
                            <th class="whitespace-nowrap px-6 py-5 text-left text-xs font-black uppercase tracking-wide text-slate-500">Order Type</th>
                            <th class="whitespace-nowrap px-6 py-5 text-left text-xs font-black uppercase tracking-wide text-slate-500">Customer</th>
                            <th class="whitespace-nowrap px-6 py-5 text-left text-xs font-black uppercase tracking-wide text-slate-500">Email</th>
                            <th class="whitespace-nowrap px-6 py-5 text-left text-xs font-black uppercase tracking-wide text-slate-500">Notes</th>
                            <th class="whitespace-nowrap px-6 py-5 text-left text-xs font-black uppercase tracking-wide text-slate-500">Status</th>
                            <th class="whitespace-nowrap px-6 py-5 text-right text-xs font-black uppercase tracking-wide text-slate-500">Estimate</th>
                            <th class="whitespace-nowrap px-6 py-5 text-right text-xs font-black uppercase tracking-wide text-slate-500">Received</th>
                            <th class="whitespace-nowrap px-6 py-5 text-right text-xs font-black uppercase tracking-wide text-slate-500">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($requests as $request)
                            @php
                                $name = trim(($request->customer_first_name ?? '') . ' ' . ($request->customer_last_name ?? ''));

                                if ($name === '') {
                                    $name = $request->customer_company_name ?: 'Unknown customer';
                                }

                                $sourceLabel = match ((string) ($request->source ?? '')) {
                                    'manual_office', 'office' => 'Office',
                                    'manual_email', 'email' => 'Email',
                                    'manual_whatsapp', 'whatsapp' => 'WhatsApp',
                                    'manual_phone', 'phone' => 'Phone',
                                    'manual_other', 'other' => 'Other',
                                    default => 'Public',
                                };

                                $sourceClass = match ($sourceLabel) {
                                    'Office' => 'bg-sky-100 text-sky-800',
                                    'Email' => 'bg-indigo-100 text-indigo-800',
                                    'WhatsApp' => 'bg-emerald-100 text-emerald-800',
                                    'Phone' => 'bg-amber-100 text-amber-800',
                                    'Other' => 'bg-slate-100 text-slate-700',
                                    default => 'bg-purple-100 text-purple-800',
                                };

                                $statusClass = match ((string) $request->status) {
                                    'converted' => 'bg-emerald-100 text-emerald-800',
                                    'cancelled' => 'bg-rose-100 text-rose-800',
                                    'reviewing' => 'bg-indigo-100 text-indigo-800',
                                    default => $request->converted_at ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800',
                                };
                            @endphp

                            <tr class="transition hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-6 py-7 align-middle text-sm font-black text-indigo-700">
                                    {{ $request->request_ref }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-7 align-middle text-sm">
                                    <span class="{{ $sourceClass }} inline-flex items-center rounded-full px-3 py-1 text-xs font-black leading-5">
                                        {{ $sourceLabel }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-6 py-7 align-middle text-sm">
                                    @if (($request->purchase_mode ?? 'standard') === 'customer_self_purchase')
                                        <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-black leading-5 text-sky-800">Self-purchase</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-black leading-5 text-indigo-700">Dabba purchase</span>
                                    @endif
                                </td>

                                <td class="min-w-[170px] px-6 py-7 align-middle text-sm font-black text-slate-950">
                                    <div class="truncate">{{ $name }}</div>
                                    @if ($request->customer_company_name)
                                        <div class="mt-1 truncate text-xs font-semibold text-slate-500">{{ $request->customer_company_name }}</div>
                                    @endif
                                </td>

                                <td class="min-w-[220px] px-6 py-7 align-middle text-sm font-medium text-slate-600">
                                    <div class="truncate">{{ $request->customer_email ?: '—' }}</div>
                                </td>

                                <td class="min-w-[130px] px-6 py-7 align-middle text-sm text-slate-600">
                                    @if (trim((string) ($request->notes ?? '')) !== '')
                                        <div class="max-w-[260px] rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold leading-5 text-amber-900">
                                            <div class="line-clamp-2">{{ \Illuminate\Support\Str::limit($request->notes, 120) }}</div>
                                        </div>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-6 py-7 align-middle text-sm">
                                    <span class="{{ $statusClass }} inline-flex items-center rounded-full px-3 py-1 text-xs font-black leading-5">
                                        {{ ucfirst($request->status ?: 'received') }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-6 py-7 align-middle text-right text-sm font-black text-slate-950">
                                    £{{ number_format((float) $request->estimated_total, 2) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-7 align-middle text-right text-sm font-medium text-slate-600">
                                    {{ \Carbon\Carbon::parse($request->submitted_at ?: $request->created_at)->format('d/m/Y H:i') }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-7 align-middle text-right text-sm">
                                    <a
                                        href="{{ route('order-requests.show', $request->id) }}"
                                        class="inline-flex h-11 items-center justify-center rounded-2xl bg-slate-950 px-5 text-sm font-black text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-200"
                                    >
                                        Open
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-16 text-center">
                                    <div class="text-sm font-bold text-slate-700">No order requests found.</div>
                                    <div class="mt-1 text-xs font-medium text-slate-400">Try clearing the search or changing the status filter.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-4 border-t border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-medium text-slate-600">
                    Showing {{ $requests->firstItem() ?? 0 }} to {{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }} results
                </p>

                <div class="order-request-pagination">
                    {{ $requests->links() }}
                </div>
            </div>
        </div>
    </div>

    <style>
        .order-request-pagination nav > div:first-child {
            display: none;
        }

        .order-request-pagination nav > div:last-child {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .order-request-pagination nav > div:last-child > div:first-child {
            display: none;
        }

        .order-request-pagination nav > div:last-child > div:last-child {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .order-request-pagination a,
        .order-request-pagination span[aria-current] span,
        .order-request-pagination span[aria-disabled="true"] span {
            border-radius: 0.85rem !important;
            font-weight: 800 !important;
            min-height: 2.75rem;
            min-width: 2.75rem;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
        }
    </style>

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
