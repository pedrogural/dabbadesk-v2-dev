<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Order Requests
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Review public submissions before converting them into draft orders.
                </p>
            </div>

            <span class="inline-flex w-fit rounded-full bg-indigo-100 px-4 py-2 text-sm font-bold text-indigo-700">
                {{ $newRequestCount }} new
            </span>
        </div>
    </x-slot>

    @php
        $tabs = [
            'open' => 'Open',
            'received' => 'New',
            'in_review' => 'In review',
            'needs_clarification' => 'Needs clarification',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'all' => 'All',
        ];

        $badgeClasses = [
            'received' => 'bg-amber-100 text-amber-800',
            'in_review' => 'bg-blue-100 text-blue-800',
            'needs_clarification' => 'bg-orange-100 text-orange-800',
            'approved' => 'bg-emerald-100 text-emerald-800',
            'rejected' => 'bg-rose-100 text-rose-800',
        ];
    @endphp

    <div class="space-y-6" x-data="{ search: @js($search), timer: null, submitSoon() { clearTimeout(this.timer); this.timer = setTimeout(() => this.$refs.searchForm.submit(), 450); } }">
        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap gap-2">
                    @foreach ($tabs as $tabKey => $label)
                        <a
                            href="{{ route('order-requests.index', array_filter(['status' => $tabKey, 'q' => $search])) }}"
                            class="rounded-full px-4 py-2 text-sm font-bold transition {{ $status === $tabKey ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                        >
                            {{ $label }}
                            <span class="ml-1 opacity-75">{{ $statusCounts[$tabKey] ?? 0 }}</span>
                        </a>
                    @endforeach
                </div>

                <form
                    method="GET"
                    action="{{ route('order-requests.index') }}"
                    x-ref="searchForm"
                    class="w-full lg:w-[520px]"
                >
                    <input type="hidden" name="status" value="{{ $status }}">

                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.1-5.15a6.25 6.25 0 1 1-12.5 0 6.25 6.25 0 0 1 12.5 0Z" />
                                </svg>
                            </div>

                            <input
                                type="search"
                                name="q"
                                x-model="search"
                                @input="submitSoon()"
                                placeholder="Live search by ref, customer, retailer or item..."
                                class="block w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-12 text-sm font-medium text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100"
                                autocomplete="off"
                            >

                            <button
                                type="button"
                                x-show="search.length > 0"
                                x-transition.opacity
                                @click="search = ''; $nextTick(() => $refs.searchForm.submit())"
                                class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-slate-700"
                                aria-label="Clear search"
                            >
                                ✕
                            </button>
                        </div>

                        <button class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800">
                            Search
                        </button>
                    </div>

                    <p class="mt-2 text-xs text-slate-400">
                        Type to search automatically. Use All to view the full historical request register.
                    </p>
                </form>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-800 ring-1 ring-rose-200">
                {{ session('error') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="hidden overflow-x-auto lg:block">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Ref</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Customer</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Status</th>
                            <th class="px-5 py-3 text-center text-xs font-bold uppercase tracking-wide text-gray-500">Items</th>
                            <th class="px-5 py-3 text-center text-xs font-bold uppercase tracking-wide text-gray-500">Retailers</th>
                            <th class="px-5 py-3 text-center text-xs font-bold uppercase tracking-wide text-gray-500">Files</th>
                            <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-gray-500">Estimate</th>
                            <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-gray-500">Received</th>
                            <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-gray-500">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($requests as $request)
                            @php
                                $name = trim(($request->customer_first_name ?? '') . ' ' . ($request->customer_last_name ?? ''));
                                if ($name === '') {
                                    $name = $request->customer_company_name ?: 'Unknown customer';
                                }
                                $statusClass = $badgeClasses[$request->status] ?? 'bg-slate-100 text-slate-700';
                            @endphp

                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-black text-indigo-700">
                                    {{ $request->request_ref }}
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    <div class="font-semibold text-gray-900">{{ $name }}</div>
                                    <div class="text-xs text-gray-500">{{ $request->customer_email ?: 'No email' }}</div>
                                    @if ($request->customer_company_name)
                                        <div class="text-xs text-gray-500">{{ $request->customer_company_name }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">
                                        {{ str($request->status)->replace('_', ' ')->title() }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center text-sm font-bold text-gray-700">{{ $request->item_count }}</td>
                                <td class="px-5 py-4 text-center text-sm font-bold text-gray-700">{{ $request->retailer_count }}</td>
                                <td class="px-5 py-4 text-center text-sm font-bold text-gray-700">{{ $request->attachment_count }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold text-gray-900">
                                    £{{ number_format((float) $request->estimated_total, 2) }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm text-gray-500">
                                    {{ $request->submitted_at ? \Illuminate\Support\Carbon::parse($request->submitted_at)->format('d M Y H:i') : \Illuminate\Support\Carbon::parse($request->created_at)->format('d M Y H:i') }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <a href="{{ route('order-requests.show', $request->id) }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700">
                                        Review
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-10 text-center text-sm text-gray-500">
                                    No order requests found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-gray-100 lg:hidden">
                @forelse ($requests as $request)
                    @php
                        $name = trim(($request->customer_first_name ?? '') . ' ' . ($request->customer_last_name ?? ''));
                        if ($name === '') {
                            $name = $request->customer_company_name ?: 'Unknown customer';
                        }
                        $statusClass = $badgeClasses[$request->status] ?? 'bg-slate-100 text-slate-700';
                    @endphp

                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-black text-indigo-700">{{ $request->request_ref }}</div>
                                <div class="mt-1 font-bold text-gray-900">{{ $name }}</div>
                                <div class="text-sm text-gray-500">{{ $request->customer_email ?: 'No email' }}</div>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">
                                {{ str($request->status)->replace('_', ' ')->title() }}
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-4 gap-2 text-center text-xs">
                            <div class="rounded-xl bg-slate-50 p-2"><b>{{ $request->item_count }}</b><br>items</div>
                            <div class="rounded-xl bg-slate-50 p-2"><b>{{ $request->retailer_count }}</b><br>retailers</div>
                            <div class="rounded-xl bg-slate-50 p-2"><b>{{ $request->attachment_count }}</b><br>files</div>
                            <div class="rounded-xl bg-slate-50 p-2"><b>£{{ number_format((float) $request->estimated_total, 2) }}</b><br>est.</div>
                        </div>

                        <a href="{{ route('order-requests.show', $request->id) }}" class="mt-4 block rounded-xl bg-indigo-600 px-4 py-3 text-center text-sm font-bold text-white">
                            Review request
                        </a>
                    </div>
                @empty
                    <div class="p-10 text-center text-sm text-gray-500">
                        No order requests found.
                    </div>
                @endforelse
            </div>
        </div>

        <div>
            {{ $requests->links() }}
        </div>
    </div>
</x-app-layout>