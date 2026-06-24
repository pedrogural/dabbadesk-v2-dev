<x-app-layout>
    <x-slot name="header">Purchasing Desk</x-slot>

    @php
        $money = fn ($amount) => '£' . number_format((float) $amount, 2);
        $activeTab = $filters['tab'] ?? 'to_buy';
        $purchaseRows = collect($purchaseRows ?? []);
        $purchasedProblemRows = collect($purchasedProblemRows ?? []);
        $purchasedProblemView = $purchasedProblemView ?? ($filters['purchased_problem_view'] ?? 'items');
        $purchasedProblemView = $purchasedProblemView === 'recorded' ? 'open' : $purchasedProblemView;
        $purchasedProblemOpenCount = (int) ($purchasedProblemOpenCount ?? ($purchasedProblemView === 'open' ? $purchasedProblemRows->count() : 0));
        $purchasedProblemHistoryCount = (int) ($purchasedProblemHistoryCount ?? ($purchasedProblemView === 'history' ? $purchasedProblemRows->count() : 0));
        $paymentBadge = [
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'part_paid' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'unpaid' => 'bg-rose-50 text-rose-700 ring-rose-200',
        ];
        $paymentLabel = ['paid' => 'Paid', 'part_paid' => 'Part paid', 'unpaid' => 'Unpaid'];
        $purchasedItemProblemRows = $purchaseRows
            ->filter(fn ($purchase) => empty($purchase->cancelled_at))
            ->values();
        $issueTypeLabels = [
            'supplier_cancelled_after_purchase' => 'Supplier cancelled',
            'lost_in_transit' => 'Lost in transit',
            'damaged_after_purchase' => 'Damaged',
            'wrong_item_received' => 'Wrong item received',
            'missing_from_parcel' => 'Missing item',
            'supplier_refunded_dabba' => 'Supplier refunded Dabba',
            'replacement_expected' => 'Replacement / partial shipment issue',
            'other' => 'Other',
        ];
        $nextActionLabels = [
            'remove_from_arrivals' => 'Remove from arrivals queue',
            'return_to_buy' => 'Return to purchasing queue',
            'replacement_expected' => 'Replacement expected',
            'awaiting_supplier_response' => 'Awaiting supplier response',
            'awaiting_customer_decision' => 'Awaiting customer decision',
            'write_off' => 'Write off / absorb loss',
            'other' => 'Other / see notes',
        ];
        $financeActionLabels = [
            'customer_refund_required' => 'Customer refund required',
            'wallet_credit_required' => 'Wallet credit required',
            'supplier_refund_pending' => 'Supplier refund pending',
            'supplier_refunded' => 'Supplier refunded Dabba',
            'manual_finance_review' => 'Manual finance review',
        ];
        $arrivalOutcomeLabels = [
            'expected' => 'Still expected in arrivals',
            'replacement_expected' => 'Replacement expected in arrivals',
            'not_expected' => 'Removed from arrivals queue',
        ];
    @endphp

    <div class="mx-auto max-w-6xl space-y-5">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-800">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-wrap gap-2">
                @foreach ($tabs as $tabKey => $tab)
                    <a href="{{ route('purchasing.index', ['tab' => $tabKey, 'problem_view' => $tabKey === 'purchased_item_problems' ? $purchasedProblemView : null, 'payment' => $filters['payment'], 'q' => $filters['q'], 'mine' => ($filters['mine'] ?? false) ? 1 : null]) }}" class="rounded-2xl px-4 py-2 text-sm font-black ring-1 transition {{ $activeTab === $tabKey ? 'bg-indigo-600 text-white ring-indigo-600 shadow-sm' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}">
                        {{ $tab['label'] }} <span class="opacity-70">{{ $tab['count'] }}</span>
                    </a>
                @endforeach
            </div>

            @if ($activeTab === 'purchased_item_problems')
                <div class="mt-4 rounded-[1.25rem] border border-rose-100 bg-rose-50/70 p-4">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-rose-600">Fast problem recording</p>
                    <h2 class="mt-1 text-lg font-black text-slate-950">Record a problem with a purchased item</h2>
                    <p class="mt-1 text-sm font-semibold leading-6 text-slate-600">
                        Search by order, customer, item, retailer or reference, then click <span class="font-black text-rose-700">Record Problem</span>. This is for items that were already bought and then cancelled, lost, damaged, refunded, wrong, missing or otherwise affected.
                    </p>
                </div>
            @endif

            <form method="GET" action="{{ route('purchasing.index') }}" class="mt-4">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                @if ($activeTab === 'purchased_item_problems')
                    <input type="hidden" name="problem_view" value="{{ $purchasedProblemView }}">
                @endif
                <div class="grid gap-3 lg:grid-cols-[1fr_190px_auto] lg:items-center">
                    <div>
                        <label for="purchase-search" class="sr-only">Search purchasing</label>
                        <input id="purchase-search" name="q" value="{{ $filters['q'] }}" placeholder="Search customer, order, retailer ref, item, retailer or email" class="h-12 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 text-sm font-bold text-slate-900 placeholder:text-slate-400 focus:border-indigo-300 focus:bg-white focus:ring-indigo-200">
                    </div>
                    <div>
                        <label for="payment-filter" class="sr-only">Payment filter</label>
                        <select id="payment-filter" name="payment" class="h-12 w-full rounded-2xl border-slate-200 bg-white px-4 text-sm font-black text-slate-800 focus:border-indigo-300 focus:ring-indigo-200">
                            @foreach ($paymentOptions as $value => $label)
                                <option value="{{ $value }}" {{ ($filters['payment'] ?? 'paid_or_part') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="inline-flex h-12 items-center gap-2 rounded-2xl bg-white px-4 text-sm font-black text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">
                            <input type="checkbox" name="mine" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" {{ ($filters['mine'] ?? false) ? 'checked' : '' }}>
                            Mine only
                        </label>
                        <button class="h-12 rounded-2xl bg-indigo-600 px-5 text-sm font-black text-white hover:bg-indigo-700">Search</button>
                    </div>
                </div>
            </form>
        </section>

        @if ($activeTab === 'purchases')
            <section class="space-y-3">
                @forelse ($purchaseRows as $purchase)
                    @php
                        $customer = trim((string) ($purchase->bill_to_company ?: $purchase->bill_to_name ?: 'Unknown customer'));
                        $canEdit = (bool) ($purchase->can_edit ?? false);
                    @endphp
                    <article class="rounded-[1.5rem] border border-emerald-100 bg-white p-4 shadow-sm sm:p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">Purchased</span>
                                    @if (! $canEdit)
                                        <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-black text-amber-700 ring-1 ring-amber-200">Locked after arrival</span>
                                    @endif
                                </div>
                                <h2 class="mt-3 text-lg font-black text-slate-950">Order #{{ $purchase->order_number }} · {{ $customer }}</h2>
                                <p class="mt-1 text-sm font-bold text-slate-700">{{ $purchase->retailer_name }} · {{ $purchase->item_name }}</p>
                                <p class="mt-1 break-words text-xs font-semibold text-slate-400">{{ $purchase->bill_to_email }}</p>
                            </div>

                            <div class="grid gap-2 text-sm sm:grid-cols-4 lg:min-w-[620px]">
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Qty</p><p class="mt-1 font-black text-slate-950">{{ (int) $purchase->qty }}</p></div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Ref</p><p class="mt-1 truncate font-black text-slate-950">{{ $purchase->retailer_order_reference ?: '—' }}</p></div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">ETA</p><p class="mt-1 font-black text-slate-950">{{ $purchase->expected_uk_hub_at ? \Carbon\Carbon::parse($purchase->expected_uk_hub_at)->format('d M Y') : '—' }}</p></div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Tracking</p><p class="mt-1 font-black text-slate-950">—</p></div>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs font-bold text-slate-500">
                                Recorded by {{ $purchase->recorded_by_name ?: 'Unknown user' }} · {{ $purchase->created_at ? \Carbon\Carbon::parse($purchase->created_at)->format('d M Y H:i') : '—' }}
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('purchasing.orders.show', ['order' => $purchase->order_id, 'tab' => 'buy']) }}" class="rounded-2xl bg-indigo-600 px-4 py-2 text-center text-sm font-black text-white hover:bg-indigo-700">Edit / View Purchase</a>
                                <a href="{{ route('orders.show', $purchase->order_id) }}" class="rounded-2xl bg-white px-4 py-2 text-center text-sm font-black text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">Order ↗</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                        <p class="text-lg font-black text-slate-900">No purchased items found.</p>
                        <p class="mt-2 text-sm font-semibold text-slate-500">Try a different search or payment filter.</p>
                    </div>
                @endforelse
            </section>
        @elseif ($activeTab === 'purchased_item_problems')
            <section class="space-y-4">
                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-3 shadow-sm">
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('purchasing.index', ['tab' => 'purchased_item_problems', 'problem_view' => 'items', 'payment' => $filters['payment'], 'q' => $filters['q'], 'mine' => ($filters['mine'] ?? false) ? 1 : null]) }}"
                           class="rounded-2xl px-4 py-2 text-sm font-black ring-1 transition {{ $purchasedProblemView === 'items' ? 'bg-rose-600 text-white ring-rose-600 shadow-sm' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}">
                            Purchased Items <span class="opacity-70">{{ $purchasedItemProblemRows->count() }}</span>
                        </a>
                        <a href="{{ route('purchasing.index', ['tab' => 'purchased_item_problems', 'problem_view' => 'open', 'payment' => $filters['payment'], 'q' => $filters['q'], 'mine' => ($filters['mine'] ?? false) ? 1 : null]) }}"
                           class="rounded-2xl px-4 py-2 text-sm font-black ring-1 transition {{ $purchasedProblemView === 'open' ? 'bg-rose-600 text-white ring-rose-600 shadow-sm' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}">
                            Open Problems <span class="opacity-70">{{ $purchasedProblemOpenCount }}</span>
                        </a>
                        <a href="{{ route('purchasing.index', ['tab' => 'purchased_item_problems', 'problem_view' => 'history', 'payment' => $filters['payment'], 'q' => $filters['q'], 'mine' => ($filters['mine'] ?? false) ? 1 : null]) }}"
                           class="rounded-2xl px-4 py-2 text-sm font-black ring-1 transition {{ $purchasedProblemView === 'history' ? 'bg-slate-700 text-white ring-slate-700 shadow-sm' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}">
                            History <span class="opacity-70">{{ $purchasedProblemHistoryCount }}</span>
                        </a>
                    </div>
                    <p class="mt-3 text-sm font-semibold leading-6 text-slate-500">
                        Use <span class="font-black text-slate-700">Purchased Items</span> to record a new problem. Use <span class="font-black text-slate-700">Open Problems</span> as the working queue. Use <span class="font-black text-slate-700">History</span> for resolved or cancelled records.
                    </p>
                </div>

                @if (in_array($purchasedProblemView, ['open', 'history'], true))
                    @forelse ($purchasedProblemRows as $problem)
                        @php
                            $customer = trim((string) ($problem->bill_to_company ?: $problem->bill_to_name ?: 'Unknown customer'));
                            $problemLabel = $issueTypeLabels[$problem->issue_type] ?? ucfirst(str_replace('_', ' ', (string) $problem->issue_type));
                            $actionLabel = $nextActionLabels[$problem->resolution_type] ?? ucfirst(str_replace('_', ' ', (string) ($problem->resolution_type ?: 'open')));
                            $statusClass = in_array((string) $problem->status, ['open', 'awaiting_customer'], true)
                                ? 'bg-rose-50 text-rose-700 ring-rose-200'
                                : 'bg-slate-50 text-slate-600 ring-slate-200';
                            $problemFinanceActions = collect(json_decode((string) ($problem->finance_actions ?? '[]'), true) ?: [])
                                ->filter(fn ($action) => array_key_exists($action, $financeActionLabels))
                                ->values();
                        @endphp
                        <article class="rounded-[1.5rem] border {{ in_array((string) $problem->status, ['resolved', 'cancelled'], true) ? 'border-slate-200' : 'border-rose-100' }} bg-white p-4 shadow-sm sm:p-5">
                            <div class="grid gap-4 lg:grid-cols-[1fr_170px_180px_180px_170px] lg:items-start">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full {{ in_array((string) $problem->status, ['resolved', 'cancelled'], true) ? 'bg-slate-50 text-slate-600 ring-slate-200' : 'bg-rose-50 text-rose-700 ring-rose-200' }} px-3 py-1.5 text-xs font-black ring-1">{{ $purchasedProblemView === 'history' ? 'Problem history' : 'Open problem' }}</span>
                                        <span class="rounded-full bg-slate-50 px-3 py-1.5 text-xs font-black text-slate-600 ring-1 ring-slate-200">Order #{{ $problem->order_number }}</span>
                                        <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', (string) $problem->status)) }}</span>
                                    </div>
                                    <h2 class="mt-3 text-lg font-black text-slate-950">{{ $problem->item_name ?: 'Unknown item' }}</h2>
                                    <p class="mt-1 text-sm font-bold text-slate-700">{{ $customer }} · {{ $problem->retailer_name }}</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-400">
                                        Recorded {{ $problem->created_at ? \Carbon\Carbon::parse($problem->created_at)->format('d M Y H:i') : '—' }} by {{ $problem->created_by_name ?: 'Unknown user' }}
                                    </p>
                                </div>

                                <div class="rounded-2xl bg-rose-50 p-3 ring-1 ring-rose-100">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-rose-500">What happened</p>
                                    <p class="mt-1 text-sm font-black text-rose-950">{{ $problemLabel }}</p>
                                </div>
                                <div class="rounded-2xl bg-amber-50 p-3 ring-1 ring-amber-100">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-amber-600">Operational action</p>
                                    <p class="mt-1 text-sm font-black text-amber-950">{{ $actionLabel }}</p>
                                </div>
                                <div class="rounded-2xl {{ $problemFinanceActions->isNotEmpty() ? 'bg-sky-50 ring-sky-100' : 'bg-slate-50 ring-slate-100' }} p-3 ring-1">
                                    <p class="text-[10px] font-black uppercase tracking-wide {{ $problemFinanceActions->isNotEmpty() ? 'text-sky-600' : 'text-slate-400' }}">Finance follow-up</p>
                                    <p class="mt-1 text-sm font-black {{ $problemFinanceActions->isNotEmpty() ? 'text-sky-950' : 'text-slate-500' }}">
                                        {{ $problemFinanceActions->isNotEmpty() ? $problemFinanceActions->map(fn ($action) => $financeActionLabels[$action])->implode(', ') : 'None flagged' }}
                                    </p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Qty / arrival</p>
                                    <p class="mt-1 text-sm font-black text-slate-950">{{ (int) ($problem->affected_qty ?: $problem->qty) }} · {{ $arrivalOutcomeLabels[$problem->arrival_expectation ?: 'expected'] ?? ucfirst(str_replace('_', ' ', (string) ($problem->arrival_expectation ?: 'expected'))) }}</p>
                                </div>
                            </div>

                            @if ((string) $problem->status === 'cancelled')
                                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-3 text-sm font-bold leading-6 text-slate-700">
                                    Cancelled: this record is kept for audit history only. It is not part of the active purchased-item problem queue.
                                </div>
                            @elseif (($problem->arrival_expectation ?? 'expected') === 'not_expected')
                                <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-bold leading-6 text-emerald-900">
                                    ✅ Arrival queue updated: this affected quantity is excluded from awaiting-arrival / soon-to-arrive calculations. Finance has not been changed automatically.
                                </div>
                            @elseif (($problem->arrival_expectation ?? 'expected') === 'replacement_expected')
                                <div class="mt-4 rounded-2xl border border-indigo-200 bg-indigo-50 p-3 text-sm font-bold leading-6 text-indigo-900">
                                    🔁 Replacement expected: this affected quantity remains in expected arrivals.
                                </div>
                            @endif

                            @if ($problem->notes)
                                <div class="mt-4 rounded-2xl bg-slate-50 p-3 text-sm font-semibold leading-6 text-slate-600 ring-1 ring-slate-100">
                                    {{ $problem->notes }}
                                </div>
                            @endif

                            @if (! in_array((string) $problem->status, ['resolved', 'cancelled'], true))
                                <details class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <summary class="cursor-pointer text-sm font-black text-slate-800">Edit recorded problem</summary>

                                    <form method="POST" action="{{ route('purchasing.problems.update', $problem->id) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="issue_stage" value="post_purchase">
                                        <input type="hidden" name="return_to_purchased_item_problems" value="1">
                                        <input type="hidden" name="return_search" value="{{ $filters['q'] }}">

                                        <label class="text-xs font-bold text-slate-600">What happened?
                                            <select name="issue_type" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white text-sm font-bold" required>
                                                @foreach ($issueTypeLabels as $value => $label)
                                                    <option value="{{ $value }}" @selected($problem->issue_type === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </label>

                                        <label class="text-xs font-bold text-slate-600">Quantity affected
                                            <input name="qty" type="number" min="1" max="999" value="{{ (int) ($problem->affected_qty ?: $problem->qty) }}" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white text-sm font-black">
                                        </label>

                                        <label class="sm:col-span-2 text-xs font-bold text-slate-600">Operational action
                                            <select name="next_action" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white text-sm font-bold" required>
                                                @foreach ($nextActionLabels as $value => $label)
                                                    <option value="{{ $value }}" @selected(($problem->resolution_type ?: 'remove_from_arrivals') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </label>

                                        <div class="sm:col-span-2 rounded-2xl border border-sky-100 bg-sky-50/70 p-4">
                                            <p class="text-xs font-black uppercase tracking-wide text-sky-700">Finance follow-up flags</p>
                                            <p class="mt-1 text-xs font-semibold leading-5 text-sky-900">Optional. These do not process payments, wallet credits, invoices or ledgers. They only mark what Finance should review later.</p>
                                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                                @foreach ($financeActionLabels as $value => $label)
                                                    <label class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-bold text-slate-700 ring-1 ring-sky-100">
                                                        <input type="checkbox" name="finance_actions[]" value="{{ $value }}" class="rounded border-slate-300 text-sky-600" @checked($problemFinanceActions->contains($value))>
                                                        {{ $label }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>

                                        <label class="text-xs font-bold text-slate-600">Severity
                                            <select name="severity" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white text-sm font-bold">
                                                <option value="medium" @selected($problem->severity === 'medium')>Medium</option>
                                                <option value="low" @selected($problem->severity === 'low')>Low</option>
                                                <option value="high" @selected($problem->severity === 'high')>High</option>
                                            </select>
                                        </label>

                                        <label class="inline-flex items-center gap-2 pt-7 text-xs font-bold text-slate-600">
                                            <input type="checkbox" name="requires_customer_action" value="1" class="rounded border-slate-300 text-rose-600" @checked((int) $problem->requires_customer_action === 1)>
                                            Customer action required
                                        </label>

                                        <label class="sm:col-span-2 text-xs font-bold text-slate-600">Notes
                                            <textarea name="notes" rows="3" maxlength="4000" class="mt-1 w-full rounded-2xl border-slate-200 bg-white text-sm font-bold">{{ $problem->notes }}</textarea>
                                        </label>

                                        <div class="sm:col-span-2 flex justify-end">
                                            <button class="rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-black text-white hover:bg-indigo-700">Save Changes</button>
                                        </div>
                                    </form>
                                </details>
                            @endif

                            <div class="mt-4 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
                                @if (! in_array((string) $problem->status, ['resolved', 'cancelled'], true))
                                    <form method="POST" action="{{ route('purchasing.problems.cancel', $problem->id) }}" data-confirm data-confirm-danger="1" data-confirm-title="Cancel recorded problem?" data-confirm-message="This keeps the audit history, but removes the problem from active problem counts and restores any linked purchased item back to normal if no other active not-expected problem remains." data-confirm-button="Cancel Problem" data-confirm-cancel="Keep Problem">
                                        @csrf
                                        <input type="hidden" name="return_to_purchased_item_problems" value="1">
                                        <input type="hidden" name="return_search" value="{{ $filters['q'] }}">
                                        <button class="rounded-2xl bg-white px-4 py-2 text-center text-sm font-black text-rose-700 ring-1 ring-rose-200 hover:bg-rose-50">Cancel Problem</button>
                                    </form>
                                @endif
                                <a href="{{ route('purchasing.orders.show', ['order' => $problem->order_id, 'tab' => 'purchased_item_problems']) }}" class="rounded-2xl bg-indigo-600 px-4 py-2 text-center text-sm font-black text-white hover:bg-indigo-700">Open order workspace</a>
                                <a href="{{ route('orders.show', $problem->order_id) }}" class="rounded-2xl bg-white px-4 py-2 text-center text-sm font-black text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">Order ↗</a>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                            <p class="text-lg font-black text-slate-900">{{ $purchasedProblemView === 'history' ? 'No purchased-item problem history found.' : 'No open purchased-item problems found.' }}</p>
                            <p class="mt-2 text-sm font-semibold text-slate-500">{{ $purchasedProblemView === 'history' ? 'Cancelled and resolved records will appear here.' : 'Record one from Purchased Items, or change the search/payment filter.' }}</p>
                        </div>
                    @endforelse
                @else
                    @php
                        $purchasedItemProblemGroups = $purchasedItemProblemRows->groupBy('order_id');
                    @endphp

                    @forelse ($purchasedItemProblemGroups as $orderId => $groupedPurchases)
                        @php
                            $firstPurchase = $groupedPurchases->first();
                            $customer = trim((string) ($firstPurchase->bill_to_company ?: $firstPurchase->bill_to_name ?: 'Unknown customer'));
                            $openProblemCount = $purchasedProblemRows
                                ->filter(fn ($problem) => (int) $problem->order_id === (int) $orderId && in_array((string) $problem->status, ['open', 'awaiting_customer'], true))
                                ->count();
                        @endphp

                        <article class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-200 bg-indigo-50/80 px-4 py-4 sm:px-5">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-white px-3 py-1.5 text-xs font-black text-indigo-700 ring-1 ring-indigo-200">Order #{{ $firstPurchase->order_number }}</span>
                                            <span class="rounded-full bg-white px-3 py-1.5 text-xs font-black text-slate-600 ring-1 ring-slate-200">{{ $groupedPurchases->count() }} purchased item{{ $groupedPurchases->count() === 1 ? '' : 's' }}</span>
                                            @if ($openProblemCount > 0)
                                                <span class="rounded-full bg-rose-100 px-3 py-1.5 text-xs font-black text-rose-700 ring-1 ring-rose-200">{{ $openProblemCount }} open problem{{ $openProblemCount === 1 ? '' : 's' }}</span>
                                            @endif
                                        </div>
                                        <h2 class="mt-2 text-lg font-black text-slate-950">{{ $customer }}</h2>
                                        <p class="mt-1 break-words text-xs font-semibold text-slate-500">{{ $firstPurchase->bill_to_email ?: 'No email' }}</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('purchasing.orders.show', ['order' => $firstPurchase->order_id, 'tab' => 'purchased_item_problems']) }}" class="rounded-2xl bg-indigo-600 px-4 py-2 text-center text-sm font-black text-white hover:bg-indigo-700">Open order workspace</a>
                                        <a href="{{ route('orders.show', $firstPurchase->order_id) }}" class="rounded-2xl bg-white px-4 py-2 text-center text-sm font-black text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">Order ↗</a>
                                    </div>
                                </div>
                            </div>

                            <div class="divide-y divide-slate-100">
                                @foreach ($groupedPurchases as $purchase)
                                    @php
                                        $itemCustomer = trim((string) ($purchase->bill_to_company ?: $purchase->bill_to_name ?: 'Unknown customer'));
                                        $openQty = max(0, (int) ($purchase->qty ?? 0) - (int) ($purchase->active_arrival_qty ?? 0));
                                        $itemOpenProblemCount = $purchasedProblemRows
                                            ->filter(fn ($problem) => (int) $problem->order_item_id === (int) $purchase->order_item_id && in_array((string) $problem->status, ['open', 'awaiting_customer'], true))
                                            ->count();
                                    @endphp

                                    <div class="p-4 sm:p-5">
                                        <div class="grid gap-4 lg:grid-cols-[1fr_120px_120px_220px] lg:items-center">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">Purchased item</span>
                                                    @if ($itemOpenProblemCount > 0)
                                                        <span class="rounded-full bg-rose-50 px-3 py-1.5 text-xs font-black text-rose-700 ring-1 ring-rose-200">Open problem</span>
                                                    @endif
                                                    <span class="rounded-full bg-slate-50 px-3 py-1.5 text-xs font-black text-slate-600 ring-1 ring-slate-200">{{ $purchase->retailer_name }}</span>
                                                </div>
                                                <h3 class="mt-3 text-base font-black text-slate-950">{{ $purchase->item_name }}</h3>
                                                <p class="mt-1 break-words text-xs font-semibold text-slate-400">
                                                    Ref: {{ $purchase->retailer_order_reference ?: '—' }} · {{ $purchase->created_at ? \Carbon\Carbon::parse($purchase->created_at)->format('d M Y H:i') : '—' }}
                                                </p>
                                            </div>

                                            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Purchased</p>
                                                <p class="mt-1 font-black text-slate-950">{{ (int) $purchase->qty }}</p>
                                            </div>
                                            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Available</p>
                                                <p class="mt-1 font-black {{ $openQty > 0 ? 'text-emerald-700' : 'text-slate-400' }}">{{ $openQty }}</p>
                                            </div>

                                            <div class="flex flex-col gap-2">
                                                @if ($openQty > 0)
                                                    <button
                                                        type="button"
                                                        data-purchased-problem-open
                                                        data-order-item-id="{{ $purchase->order_item_id }}"
                                                        data-item-name="{{ e($purchase->item_name) }}"
                                                        data-order-number="{{ e($purchase->order_number) }}"
                                                        data-customer="{{ e($itemCustomer) }}"
                                                        data-remaining="{{ $openQty }}"
                                                        class="inline-flex w-full items-center justify-center rounded-2xl bg-rose-600 px-4 py-3 text-sm font-black text-white shadow-sm hover:bg-rose-700"
                                                    >
                                                        ⚠ Record Problem
                                                    </button>
                                                @else
                                                    <div class="rounded-2xl bg-slate-50 px-3 py-2 text-xs font-bold leading-5 text-slate-500 ring-1 ring-slate-100">No open expected quantity left.</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                            <p class="text-lg font-black text-slate-900">No purchased items found.</p>
                            <p class="mt-2 text-sm font-semibold text-slate-500">Search by order, customer, item or retailer reference. Use All Orders if needed.</p>
                        </div>
                    @endforelse
                @endif
            </section>
        @else
            <section class="space-y-4">
                @forelse ($orders as $order)
                    @php
                        $payClass = $paymentBadge[$order['payment_status']] ?? $paymentBadge['unpaid'];
                        $payText = $paymentLabel[$order['payment_status']] ?? ucfirst((string) $order['payment_status']);
                        $primaryAction = $activeTab === 'problems' ? 'View Purchase Issues' : 'Purchase Items';
                    @endphp
                    <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-xl font-black text-slate-950">#{{ $order['order_number'] }}</h2>
                                    <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $payClass }}">{{ $payText }}</span>
                                    @if ((int) $order['inspection_count'] > 0)
                                        <span class="rounded-full bg-purple-50 px-3 py-1.5 text-xs font-black text-purple-700 ring-1 ring-purple-200">Package check</span>
                                    @endif
                                </div>
                                <p class="mt-2 text-sm font-bold text-slate-700">{{ $order['customer'] }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-400">{{ $order['email'] }}</p>
                            </div>

                            <div class="grid gap-2 text-sm sm:grid-cols-3 lg:min-w-[460px]">
                                <div class="rounded-2xl bg-indigo-50 p-3 ring-1 ring-indigo-100"><p class="text-[10px] font-black uppercase tracking-wide text-indigo-500">To purchase</p><p class="mt-1 font-black text-indigo-950">{{ $order['remaining_to_buy_qty'] }}</p></div>
                                <div class="rounded-2xl bg-emerald-50 p-3 ring-1 ring-emerald-100"><p class="text-[10px] font-black uppercase tracking-wide text-emerald-600">Purchased</p><p class="mt-1 font-black text-emerald-950">{{ $order['purchased_qty'] }}</p></div>
                                <div class="rounded-2xl bg-rose-50 p-3 ring-1 ring-rose-100"><p class="text-[10px] font-black uppercase tracking-wide text-rose-500">Purchase issues</p><p class="mt-1 font-black text-rose-950">{{ $activeTab === 'problems' ? ($order['pre_purchase_problem_qty'] ?? $order['problem_qty']) : $order['problem_qty'] }}</p></div>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs font-bold text-slate-500">Retailers: {{ $order['retailer_count'] }} · Order total {{ $money($order['grand_total']) }}</p>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('purchasing.orders.show', ['order' => $order['order_id'], 'tab' => $activeTab === 'problems' ? 'problems' : 'buy', 'issue_view' => $activeTab === 'problems' ? 'pre' : null]) }}" class="rounded-2xl bg-indigo-600 px-4 py-2 text-center text-sm font-black text-white hover:bg-indigo-700">{{ $primaryAction }}</a>
                                <a href="{{ route('orders.show', $order['order_id']) }}" class="rounded-2xl bg-white px-4 py-2 text-center text-sm font-black text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">Order ↗</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                        <p class="text-lg font-black text-slate-900">Nothing found here.</p>
                        <p class="mt-2 text-sm font-semibold text-slate-500">Try another tab, All Orders, or adjust your search.</p>
                    </div>
                @endforelse
            </section>
        @endif
    </div>

    <div id="purchased-problem-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm" aria-hidden="true">
        <div class="w-full max-w-2xl overflow-hidden rounded-[1.75rem] bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 bg-rose-600 px-5 py-4 text-white">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-rose-100">Purchased item problem</p>
                    <h3 class="mt-1 text-lg font-black">Record problem</h3>
                    <p id="purchased-problem-modal-item" class="mt-1 text-sm font-semibold text-rose-100"></p>
                </div>
                <button type="button" data-purchased-problem-close class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-xl font-black hover:bg-white/20" aria-label="Close problem form">×</button>
            </div>

            <form method="POST" action="{{ route('purchasing.problems.store') }}" class="grid gap-4 p-5 sm:grid-cols-2">
                @csrf
                <input type="hidden" name="order_item_id" id="purchased-problem-order-item-id" value="">
                <input type="hidden" name="issue_stage" value="post_purchase">
                <input type="hidden" name="arrival_expectation" id="purchased-problem-arrival-expectation" value="not_expected">
                <input type="hidden" name="return_to_purchased_item_problems" value="1">
                <input type="hidden" name="return_search" value="{{ $filters['q'] }}">

                <label class="text-xs font-bold text-slate-600">What happened?
                    <select name="issue_type" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white text-sm font-bold" required>
                        <option value="supplier_cancelled_after_purchase">Supplier cancelled</option>
                        <option value="lost_in_transit">Lost in transit</option>
                        <option value="damaged_after_purchase">Damaged</option>
                        <option value="wrong_item_received">Wrong item received</option>
                        <option value="missing_from_parcel">Missing item / missing from parcel</option>
                        <option value="supplier_refunded_dabba">Supplier refunded Dabba</option>
                        <option value="replacement_expected">Partial shipment / replacement issue</option>
                        <option value="other">Other</option>
                    </select>
                </label>

                <label class="text-xs font-bold text-slate-600">Quantity affected
                    <input id="purchased-problem-qty" name="qty" type="number" min="1" value="1" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white text-sm font-black">
                </label>

                <label class="sm:col-span-2 text-xs font-bold text-slate-600">Operational action
                    <select name="next_action" id="purchased-problem-next-action" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white text-sm font-bold" required>
                        <option value="remove_from_arrivals">Remove from arrivals queue now</option>
                        <option value="return_to_buy">Return to purchasing queue</option>
                        <option value="replacement_expected">Replacement expected</option>
                        <option value="awaiting_supplier_response">Awaiting supplier response</option>
                        <option value="awaiting_customer_decision">Awaiting customer decision</option>
                        <option value="write_off">Write off / absorb loss</option>
                        <option value="other">Other / see notes</option>
                    </select>
                </label>

                <div class="sm:col-span-2 rounded-2xl border border-sky-100 bg-sky-50/70 p-4">
                    <p class="text-xs font-black uppercase tracking-wide text-sky-700">Finance follow-up flags</p>
                    <p class="mt-1 text-xs font-semibold leading-5 text-sky-900">Select any finance action that may be needed later. This only flags the item for Finance; it does not issue a refund or wallet credit.</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach ($financeActionLabels as $value => $label)
                            <label class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-bold text-slate-700 ring-1 ring-sky-100">
                                <input type="checkbox" name="finance_actions[]" value="{{ $value }}" class="rounded border-slate-300 text-sky-600">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <label class="text-xs font-bold text-slate-600">Severity
                    <select name="severity" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white text-sm font-bold">
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                        <option value="high">High</option>
                    </select>
                </label>

                <label class="inline-flex items-center gap-2 pt-7 text-xs font-bold text-slate-600">
                    <input type="checkbox" name="requires_customer_action" value="1" class="rounded border-slate-300 text-rose-600">
                    Customer action required
                </label>

                <label class="sm:col-span-2 text-xs font-bold text-slate-600">Notes
                    <textarea name="notes" rows="4" maxlength="4000" placeholder="Add supplier message, tracking note, refund detail, or what staff should do next." class="mt-1 w-full rounded-2xl border-slate-200 bg-white text-sm font-bold"></textarea>
                </label>

                <div class="sm:col-span-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-bold leading-5 text-amber-900">
                    Operational action controls whether the item remains expected in arrivals. Finance follow-up flags are separate and never change payments, wallet, invoices or ledgers automatically.
                </div>

                <div class="sm:col-span-2 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" data-purchased-problem-close class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button class="rounded-2xl bg-rose-600 px-5 py-2 text-sm font-black text-white shadow-sm hover:bg-rose-700">Save Problem</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const purchasingFilterForm = document.querySelector('form[action="{{ route('purchasing.index') }}"]');
        const purchasingSearch = document.getElementById('purchase-search');
        const paymentFilter = document.getElementById('payment-filter');
        let purchasingSearchTimer = null;
        purchasingSearch?.addEventListener('input', () => {
            window.clearTimeout(purchasingSearchTimer);
            purchasingSearchTimer = window.setTimeout(() => purchasingFilterForm?.submit(), 450);
        });
        paymentFilter?.addEventListener('change', () => purchasingFilterForm?.submit());
        purchasingFilterForm?.querySelector('[name="mine"]')?.addEventListener('change', () => purchasingFilterForm?.submit());

        const purchasedProblemModal = document.getElementById('purchased-problem-modal');
        const purchasedProblemItemLabel = document.getElementById('purchased-problem-modal-item');
        const purchasedProblemOrderItemId = document.getElementById('purchased-problem-order-item-id');
        const purchasedProblemQty = document.getElementById('purchased-problem-qty');
        const purchasedProblemNextAction = document.getElementById('purchased-problem-next-action');
        const purchasedProblemArrivalExpectation = document.getElementById('purchased-problem-arrival-expectation');

        const syncPurchasedProblemArrivalExpectation = () => {
            const action = purchasedProblemNextAction?.value || 'remove_from_arrivals';

            if (! purchasedProblemArrivalExpectation) return;

            if (action === 'replacement_expected') {
                purchasedProblemArrivalExpectation.value = 'replacement_expected';
                return;
            }

            if (['remove_from_arrivals', 'return_to_buy', 'write_off'].includes(action)) {
                purchasedProblemArrivalExpectation.value = 'not_expected';
                return;
            }

            purchasedProblemArrivalExpectation.value = 'expected';
        };

        purchasedProblemNextAction?.addEventListener('change', syncPurchasedProblemArrivalExpectation);

        document.querySelectorAll('[data-purchased-problem-open]').forEach((button) => {
            button.addEventListener('click', () => {
                const remaining = Math.max(1, parseInt(button.dataset.remaining || '1', 10));

                if (purchasedProblemOrderItemId) purchasedProblemOrderItemId.value = button.dataset.orderItemId || '';
                if (purchasedProblemQty) {
                    purchasedProblemQty.value = remaining;
                    purchasedProblemQty.max = remaining;
                }
                if (purchasedProblemItemLabel) {
                    purchasedProblemItemLabel.textContent = `Order #${button.dataset.orderNumber || '—'} · ${button.dataset.customer || 'Unknown customer'} · ${button.dataset.itemName || 'Item'} · ${remaining} available`;
                }

                syncPurchasedProblemArrivalExpectation();

                if (purchasedProblemModal) {
                    purchasedProblemModal.classList.remove('hidden');
                    purchasedProblemModal.classList.add('flex');
                    purchasedProblemModal.setAttribute('aria-hidden', 'false');
                }
            });
        });

        document.querySelectorAll('[data-purchased-problem-close]').forEach((button) => {
            button.addEventListener('click', () => {
                if (! purchasedProblemModal) return;
                purchasedProblemModal.classList.add('hidden');
                purchasedProblemModal.classList.remove('flex');
                purchasedProblemModal.setAttribute('aria-hidden', 'true');
            });
        });

        purchasedProblemModal?.addEventListener('click', (event) => {
            if (event.target === purchasedProblemModal) {
                purchasedProblemModal.classList.add('hidden');
                purchasedProblemModal.classList.remove('flex');
                purchasedProblemModal.setAttribute('aria-hidden', 'true');
            }
        });
    </script>
</x-app-layout>
