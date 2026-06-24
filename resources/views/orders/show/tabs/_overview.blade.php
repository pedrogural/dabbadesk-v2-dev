        <div x-show="tab === 'overview'" x-cloak class="space-y-5">
            <div class="flex flex-col items-start gap-5 xl:flex-row xl:items-stretch">
                <section class="w-full max-w-[520px] rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="space-y-4">
                        <div class="min-w-0">
                            <div class="inline-flex max-w-full items-center gap-2">
                                <h2 class="truncate text-xl font-black text-slate-950">{{ $customerFullName ?: 'Unknown customer' }}</h2>
                                @if ($customerFullName)
                                    <button type="button" data-copy-value="{{ $customerFullName }}" title="Copy customer name" aria-label="Copy customer name" class="copy-btn inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-indigo-200 bg-white text-sm text-indigo-700 hover:bg-indigo-50">📋</button>
                                @endif
                            </div>
                            @if ($customerCompany)
                                <p class="mt-1 truncate text-sm font-semibold text-slate-500">{{ $customerCompany }}</p>
                            @endif
                        </div>

                        @if ($customerEmail)
                            <div class="flex min-w-0 flex-wrap items-center gap-2">
                                <span class="min-w-0 break-all text-sm font-semibold text-slate-800">{{ $customerEmail }}</span>
                                <button type="button" data-copy-value="{{ $customerEmail }}" title="Copy email" aria-label="Copy email" class="copy-btn inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-indigo-200 bg-white text-sm text-indigo-700 hover:bg-indigo-50">📋</button>
                                <a href="mailto:{{ $customerEmail }}" title="Email customer" aria-label="Email customer" class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-sm font-semibold text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-100">↗</a>
                            </div>
                        @else
                            <p class="text-sm font-semibold text-slate-400">No email captured.</p>
                        @endif

                        @if ($customerPhone)
                            <div class="flex min-w-0 flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-slate-800">{{ $customerPhone }}</span>
                                <button type="button" data-copy-value="{{ $customerPhone }}" title="Copy phone" aria-label="Copy phone" class="copy-btn inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-indigo-200 bg-white text-sm text-indigo-700 hover:bg-indigo-50">📋</button>
                                @if ($whatsappUrl)
                                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" title="Open WhatsApp" aria-label="Open WhatsApp" class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-100 hover:bg-emerald-100">↗</a>
                                @endif
                            </div>
                        @else
                            <p class="text-sm font-semibold text-slate-400">No phone captured.</p>
                        @endif

                        <div class="min-w-0">
                            @if ($addressLines->isNotEmpty())
                                <div class="inline-flex max-w-full items-end gap-2">
                                    <div class="space-y-0.5 text-sm font-semibold leading-5 text-slate-800">
                                        @foreach ($addressLines as $line)
                                            <p>{{ $line }}</p>
                                        @endforeach
                                    </div>
                                    @if ($copyFullAddress)
                                        <button type="button" data-copy-value="{{ $copyFullAddress }}" title="Copy address" aria-label="Copy address" class="copy-btn mb-[-2px] inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-indigo-200 bg-white text-sm text-indigo-700 hover:bg-indigo-50">📋</button>
                                    @endif
                                </div>
                            @else
                                <p class="text-sm font-semibold text-slate-400">No billing address captured.</p>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="w-full max-w-[360px] rounded-3xl {{ $balanceDue > 0 ? 'border-rose-200 bg-rose-50' : 'border-emerald-200 bg-emerald-50' }} border p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] {{ $balanceDue > 0 ? 'text-rose-700' : 'text-emerald-700' }}">Outstanding balance</p>
                    <p class="mt-2 text-4xl font-semibold {{ $balanceDue > 0 ? 'text-rose-700' : 'text-emerald-700' }}">£{{ number_format($balanceDue, 2) }}</p>
                    <p class="mt-2 text-sm text-slate-600">Total £{{ number_format($orderTotal, 2) }} · Settled £{{ number_format($settledTotal, 2) }}</p>
                    @if ($walletAvailable > 0.004)
                        <p class="mt-2 rounded-2xl bg-white/70 px-3 py-2 text-sm font-semibold text-sky-800 ring-1 ring-sky-100">Wallet available: £{{ number_format($walletAvailable, 2) }}</p>
                    @endif
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if (! $isHistoricalRevision)
                            <button type="button" @click="$dispatch('open-payment-modal')" class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Record payment</button>
                        @else
                            <span class="rounded-2xl bg-amber-100 px-4 py-2 text-sm font-black text-amber-800 ring-1 ring-amber-200">Read-only historical revision</span>
                        @endif
                        <button type="button" data-copy-value="{{ e($copyPaymentDetails) }}" class="copy-btn rounded-2xl border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Copy payment details</button>
                    </div>
                </section>
            </div>

            <div class="grid gap-5 lg:grid-cols-3">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Invoice snapshot</p>
                            <h3 class="mt-1 text-lg font-black text-slate-950">
                                @if ($hasInvoiceWorkspace)
                                    Invoice #{{ $invoiceNumber }} · v{{ $latestInvoiceVersion->version ?? ($invoiceVersions->max('version') ?? 1) }}
                                @else
                                    No invoice snapshot yet
                                @endif
                            </h3>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-black ring-1 {{ $hasInvoiceWorkspace ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">
                            {{ $hasInvoiceWorkspace ? 'Issued snapshot' : 'Needs invoice' }}
                        </span>
                    </div>

                    <p class="mt-3 text-sm font-semibold leading-6 text-slate-600">
                        @if ($hasInvoiceWorkspace)
                            The invoice snapshot exists. Creating another version preserves this one for history.
                        @else
                            Create the first invoice snapshot when this order is ready to invoice.
                        @endif
                    </p>
                    <p class="mt-2 rounded-2xl bg-slate-50 px-3 py-2 text-xs font-bold leading-5 text-slate-500 ring-1 ring-slate-100">
                        PDF generation and email sending are still separate workflow work; this button only creates the invoice snapshot/version.
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if (! $isHistoricalRevision)
                            <button type="button" @click="$dispatch('open-invoice-modal')" class="rounded-2xl {{ $hasInvoiceWorkspace ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-amber-600 hover:bg-amber-700' }} px-4 py-2 text-sm font-black text-white shadow-sm">
                                {{ $hasInvoiceWorkspace ? 'Create next invoice version' : 'Create invoice snapshot' }}
                            </button>
                        @else
                            <p class="rounded-2xl bg-amber-50 px-4 py-2 text-sm font-black text-amber-800 ring-1 ring-amber-100">Invoice actions disabled for historical revisions.</p>
                        @endif
                        <button type="button" @click="tab = 'finance'" class="rounded-2xl bg-white px-4 py-2 text-sm font-black text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">View finance tab</button>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Order progress</p>
                    <h3 class="mt-1 text-lg font-black text-slate-950">{{ $purchaseStatusLabel }}</h3>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Items</p>
                            <p class="mt-1 text-lg font-black text-slate-950">{{ $progress['item_qty'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-2xl bg-indigo-50 p-3 ring-1 ring-indigo-100">
                            <p class="text-[10px] font-black uppercase tracking-wide text-indigo-500">Bought</p>
                            <p class="mt-1 text-lg font-black text-indigo-950">{{ $progress['purchased_qty'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-2xl bg-sky-50 p-3 ring-1 ring-sky-100">
                            <p class="text-[10px] font-black uppercase tracking-wide text-sky-600">Arrived</p>
                            <p class="mt-1 text-lg font-black text-sky-950">{{ $progress['arrived_qty'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-2xl bg-emerald-50 p-3 ring-1 ring-emerald-100">
                            <p class="text-[10px] font-black uppercase tracking-wide text-emerald-600">Done</p>
                            <p class="mt-1 text-lg font-black text-emerald-950">{{ $progress['collected_qty'] ?? 0 }}</p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs font-semibold leading-5 text-slate-500">Quick snapshot only. Use Items, Purchasing, and Finance tabs for detailed work.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Related workspaces</p>
                    <h3 class="mt-1 text-lg font-black text-slate-950">Open the linked records</h3>
                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">Jump to the original request, editable draft, or Money Desk view for this order.</p>
                    <div class="mt-4 grid gap-2">
                        @if (! empty($order->order_request_id))
                            <a href="{{ route('order-requests.show', $order->order_request_id) }}" class="flex items-center justify-between rounded-2xl bg-indigo-50 px-4 py-3 text-sm font-black text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-100">
                                <span>Customer request</span><span>↗</span>
                            </a>
                        @endif
                        <a href="{{ route('draft-orders.show', $order->draft_order_id) }}" class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 text-sm font-black text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100">
                            <span>Draft workspace</span><span>↗</span>
                        </a>
                        <a href="{{ route('money-desk.orders.show', $order->id) }}" class="flex items-center justify-between rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700 ring-1 ring-emerald-100 hover:bg-emerald-100">
                            <span>Money Desk</span><span>↗</span>
                        </a>
                    </div>
                </div>
            </div>

        @if ($isCustomerSelfPurchase)
            <div class="rounded-3xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-sky-700">Customer self-purchase</p>
                        <h2 class="mt-1 text-lg font-black text-sky-950">Customer bought the goods directly from the retailer</h2>
                        <p class="mt-1 text-sm font-semibold leading-6 text-sky-900">Dabba should not purchase these goods. Continue with arrival, customs, collection and delivery workflow when the goods reach Dabba.</p>
                    </div>
                    <a href="{{ route('money-desk.orders.show', $order->id) }}" class="rounded-2xl bg-white px-4 py-2.5 text-sm font-black text-sky-700 ring-1 ring-sky-200 hover:bg-sky-100">Finance ↗</a>
                </div>
            </div>
        @endif
        @if ($customerRequestNotes->isNotEmpty())
            <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-700">Customer order request notes</p>
                        <h2 class="mt-1 text-lg font-black text-amber-950">Original customer notes carried through from request</h2>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black uppercase tracking-wide text-amber-700 ring-1 ring-amber-200">Pinned lifecycle note</span>
                </div>

                <div class="mt-4 space-y-3">
                    @foreach ($customerRequestNotes as $requestNote)
                        <div class="rounded-2xl bg-white/80 px-4 py-3 text-sm leading-6 text-amber-950 ring-1 ring-amber-100">
                            <p class="whitespace-pre-line">{{ $requestNote->body }}</p>
                            <p class="mt-2 text-xs font-semibold text-amber-700">
                                {{ ($requestNote->occurred_at ?: $requestNote->created_at) ? \Carbon\Carbon::parse($requestNote->occurred_at ?: $requestNote->created_at)->format('d M Y H:i') : 'Date unknown' }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

            @include('orders.show._revision_history')
        </div>
