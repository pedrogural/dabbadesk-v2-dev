<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('order-requests.index') }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-800">
                        ← Back
                    </a>
                    <span class="text-sm text-gray-400">/</span>
                    <span class="text-sm font-bold text-gray-500">Request {{ $orderRequest->request_ref }}</span>
                </div>
                <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">
                    Review Order Request
                </h2>
            </div>

            @php
                $badgeClasses = [
                    'received' => 'bg-amber-100 text-amber-800',
                    'in_review' => 'bg-blue-100 text-blue-800',
                    'needs_clarification' => 'bg-orange-100 text-orange-800',
                    'approved' => 'bg-emerald-100 text-emerald-800',
                    'rejected' => 'bg-rose-100 text-rose-800',
                    'converted' => 'bg-purple-100 text-purple-800',
                ];
                $statusClass = $badgeClasses[$orderRequest->status] ?? 'bg-slate-100 text-slate-700';
            @endphp

            <span class="inline-flex w-fit rounded-full px-4 py-2 text-sm font-black {{ $statusClass }}">
                {{ str($orderRequest->status)->replace('_', ' ')->title() }}
            </span>
        </div>
    </x-slot>

    @php
        $customerName = trim(($orderRequest->customer_first_name ?? '') . ' ' . ($orderRequest->customer_last_name ?? ''));
        if ($customerName === '') {
            $customerName = $orderRequest->customer_company_name ?: 'Unknown customer';
        }

        $statusActions = [
            'in_review' => [
                'label' => 'Start review',
                'description' => 'Use this when staff has started checking the request.',
                'classes' => 'bg-blue-600 hover:bg-blue-700',
                'placeholder' => 'Optional note, for example: checking links and prices now.',
            ],
            'needs_clarification' => [
                'label' => 'Needs clarification',
                'description' => 'Use this when customer input is unclear or missing.',
                'classes' => 'bg-orange-500 hover:bg-orange-600',
                'placeholder' => 'Explain what needs clarification, e.g. broken link, missing size, unclear quantity.',
            ],
            'approved' => [
                'label' => 'Approve request',
                'description' => 'Use this when the request is ready for conversion to draft order.',
                'classes' => 'bg-emerald-600 hover:bg-emerald-700',
                'placeholder' => 'Optional approval note, e.g. prices checked and attachments reviewed.',
            ],
            'rejected' => [
                'label' => 'Reject request',
                'description' => 'Use this when the request should not proceed.',
                'classes' => 'bg-rose-600 hover:bg-rose-700',
                'placeholder' => 'Reason for rejection, e.g. prohibited item, impossible supplier, duplicate request.',
            ],
        ];
    @endphp

    <div class="space-y-6">
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

        @if ($errors->any())
            <div class="rounded-2xl bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-800 ring-1 ring-rose-200">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-gray-400">Customer</p>
                            <h3 class="mt-1 text-2xl font-black text-gray-900">{{ $customerName }}</h3>
                            @if ($orderRequest->customer_company_name)
                                <p class="mt-1 text-sm font-semibold text-gray-600">{{ $orderRequest->customer_company_name }}</p>
                            @endif
                        </div>

                        <div class="rounded-2xl bg-indigo-50 px-5 py-4 text-right">
                            <p class="text-xs font-black uppercase tracking-wide text-indigo-500">Estimated item total</p>
                            <p class="mt-1 text-2xl font-black text-indigo-800">£{{ number_format((float) $orderRequest->estimated_total, 2) }}</p>
                        </div>
                    </div>

                    <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-wide text-slate-400">Email</dt>
                            <dd class="mt-1 break-words text-sm font-semibold text-slate-800">{{ $orderRequest->customer_email ?: '—' }}</dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-wide text-slate-400">Phone</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $orderRequest->customer_phone_digits ?: '—' }}</dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-wide text-slate-400">Submitted</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">
                                {{ $orderRequest->submitted_at ? \Illuminate\Support\Carbon::parse($orderRequest->submitted_at)->format('d M Y H:i') : '—' }}
                            </dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 sm:col-span-2 lg:col-span-3">
                            <dt class="text-xs font-black uppercase tracking-wide text-slate-400">Address</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">
                                {{ $orderRequest->customer_address_line1 ?: '—' }}
                                @if ($orderRequest->customer_address_postcode)
                                    <span class="text-slate-500">· {{ $orderRequest->customer_address_postcode }}</span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if ($orderRequest->notes)
                        <div class="mt-6 rounded-2xl bg-amber-50 p-5 ring-1 ring-amber-100">
                            <p class="text-xs font-black uppercase tracking-wide text-amber-600">Customer notes</p>
                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-amber-950">{{ $orderRequest->notes }}</p>
                        </div>
                    @endif
                </section>

                <section class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-100 p-6">
                        <h3 class="text-lg font-black text-gray-900">Requested items</h3>
                        <p class="mt-1 text-sm text-gray-500">Check retailer, product details, quantities, prices and customer notes before approval.</p>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @forelse ($items as $item)
                            @php
                                $lineTotal = (float) ($item->line_total ?? ((float) $item->unit_price * (int) $item->quantity));
                            @endphp

                            <article class="p-6">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">
                                                Item {{ $loop->iteration }}
                                            </span>
                                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700">
                                                {{ $item->retailer_name ?: $item->matched_retailer_name ?: 'Unknown retailer' }}
                                            </span>
                                        </div>

                                        <h4 class="mt-3 text-base font-black text-gray-900">{{ $item->description }}</h4>

                                        <div class="mt-3 grid gap-3 text-sm text-gray-600 sm:grid-cols-2">
                                            <div>
                                                <span class="font-bold text-gray-500">Product code:</span>
                                                {{ $item->product_code ?: '—' }}
                                            </div>
                                            <div>
                                                <span class="font-bold text-gray-500">Matched retailer:</span>
                                                {{ $item->matched_retailer_name ?: 'Not matched' }}
                                            </div>
                                        </div>

                                        @if ($item->retailer_url)
                                            <a href="{{ $item->retailer_url }}" target="_blank" rel="noopener" class="mt-3 inline-flex break-all text-sm font-bold text-indigo-600 hover:text-indigo-800">
                                                Open product / retailer link ↗
                                            </a>
                                        @endif

                                        @if ($item->notes)
                                            <div class="mt-4 rounded-2xl bg-slate-50 p-4">
                                                <p class="text-xs font-black uppercase tracking-wide text-slate-400">Item notes</p>
                                                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $item->notes }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="grid min-w-56 grid-cols-3 gap-2 text-center lg:text-right">
                                        <div class="rounded-2xl bg-slate-50 p-3">
                                            <p class="text-xs font-black uppercase text-slate-400">Qty</p>
                                            <p class="mt-1 text-lg font-black text-slate-900">{{ $item->quantity }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-slate-50 p-3">
                                            <p class="text-xs font-black uppercase text-slate-400">Unit</p>
                                            <p class="mt-1 text-lg font-black text-slate-900">£{{ number_format((float) $item->unit_price, 2) }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-slate-900 p-3 text-white">
                                            <p class="text-xs font-black uppercase text-slate-300">Line</p>
                                            <p class="mt-1 text-lg font-black">£{{ number_format($lineTotal, 2) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="p-10 text-center text-sm text-gray-500">
                                No items found on this request.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-lg font-black text-gray-900">Review actions</h3>
                    <p class="mt-1 text-sm text-gray-500">Move the request through the intake workflow and leave an audit note where useful.</p>

                    @if ($orderRequest->converted_at)
                        <div class="mt-5 rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-100">
                            Converted by {{ $orderRequest->converted_by_name ?: 'staff' }} on {{ \Illuminate\Support\Carbon::parse($orderRequest->converted_at)->format('d M Y H:i') }}.
                        </div>
                    @else
                        <div class="mt-5 space-y-4">
                            @foreach ($statusActions as $targetStatus => $action)
                                <form method="POST" action="{{ route('order-requests.update-status', $orderRequest->id) }}" class="rounded-2xl border border-gray-200 p-4">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $targetStatus }}">

                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-black text-gray-900">{{ $action['label'] }}</p>
                                            <p class="mt-1 text-xs leading-5 text-gray-500">{{ $action['description'] }}</p>
                                        </div>

                                        @if ($orderRequest->status === $targetStatus)
                                            <span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-black uppercase text-slate-500">
                                                Current
                                            </span>
                                        @endif
                                    </div>

                                    <textarea
                                        name="status_note"
                                        rows="2"
                                        class="mt-3 w-full rounded-2xl border-gray-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="{{ $action['placeholder'] }}"
                                    ></textarea>

                                    <button class="mt-3 w-full rounded-2xl px-4 py-3 text-sm font-black text-white shadow-sm {{ $action['classes'] }}">
                                        {{ $action['label'] }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    @endif

                    <dl class="mt-6 space-y-3 border-t border-gray-100 pt-5 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="font-bold text-gray-500">Reviewed</dt>
                            <dd class="text-right font-semibold text-gray-900">
                                {{ $orderRequest->reviewed_at ? \Illuminate\Support\Carbon::parse($orderRequest->reviewed_at)->format('d M Y H:i') : 'Not yet' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="font-bold text-gray-500">Reviewed by</dt>
                            <dd class="text-right font-semibold text-gray-900">{{ $orderRequest->reviewed_by_name ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="font-bold text-gray-500">Source</dt>
                            <dd class="text-right font-semibold text-gray-900">{{ $orderRequest->source }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-lg font-black text-gray-900">Internal review note</h3>
                    <p class="mt-1 text-sm text-gray-500">Use this for staff-only comments before conversion.</p>

                    <form method="POST" action="{{ route('order-requests.notes.store', $orderRequest->id) }}" class="mt-4">
                        @csrf
                        <textarea
                            name="body"
                            rows="4"
                            required
                            class="w-full rounded-2xl border-gray-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Example: Customer forgot colour. Need confirmation before approving."
                        >{{ old('body') }}</textarea>

                        <button class="mt-3 w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-black text-white transition hover:bg-slate-800">
                            Save internal note
                        </button>
                    </form>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-lg font-black text-gray-900">Review timeline</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($reviewNotes as $note)
                            <article class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-black text-slate-900">{{ $note->title ?: 'Review note' }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $note->created_by_name ?: 'Staff' }} ·
                                            {{ $note->occurred_at ? \Illuminate\Support\Carbon::parse($note->occurred_at)->format('d M Y H:i') : \Illuminate\Support\Carbon::parse($note->created_at)->format('d M Y H:i') }}
                                        </p>
                                    </div>
                                    <span class="rounded-full {{ $note->type === 'review_status' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-600' }} px-2 py-1 text-[10px] font-black uppercase">
                                        {{ $note->type === 'review_status' ? 'Status' : 'Note' }}
                                    </span>
                                </div>
                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $note->body }}</p>
                            </article>
                        @empty
                            <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">No internal review notes yet.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-lg font-black text-gray-900">Retailer summary</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($retailerGroups as $group)
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="font-black text-slate-900">{{ $group->name }}</div>
                                <div class="mt-1 flex justify-between text-sm text-slate-500">
                                    <span>{{ $group->item_count }} item{{ $group->item_count === 1 ? '' : 's' }}</span>
                                    <span class="font-bold text-slate-700">£{{ number_format($group->subtotal, 2) }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No retailer data yet.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-lg font-black text-gray-900">Attachments</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($attachments as $attachment)
                            <a
                                href="{{ route('order-requests.attachments.show', [$orderRequest->id, $attachment->id]) }}"
                                class="block rounded-2xl border border-gray-200 p-4 hover:border-indigo-300 hover:bg-indigo-50"
                            >
                                <div class="break-words text-sm font-black text-gray-900">{{ $attachment->original_name }}</div>
                                <div class="mt-1 text-xs text-gray-500">
                                    {{ $attachment->mime ?: 'file' }}
                                    @if ($attachment->size)
                                        · {{ number_format($attachment->size / 1024, 1) }} KB
                                    @endif
                                </div>
                            </a>
                        @empty
                            <p class="text-sm text-gray-500">No attachments uploaded.</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
