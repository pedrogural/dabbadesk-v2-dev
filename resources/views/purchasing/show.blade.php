<x-app-layout>
    <x-slot name="header">Purchasing Workspace</x-slot>

    @php
        $money = fn ($amount) => '£' . number_format((float) $amount, 2);
        $orderNumber = $order->order_number ?? $order->id;
        $customer = trim((string) ($order->bill_to_company ?: $order->bill_to_name ?: 'Unknown customer'));
        $paymentStatus = $queueOrder['payment_status'] ?? 'unpaid';
        $paymentLabel = match ($paymentStatus) {'paid' => 'Paid', 'part_paid' => 'Part paid', default => 'Unpaid'};
        $paymentClass = match ($paymentStatus) {'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'part_paid' => 'bg-amber-50 text-amber-700 ring-amber-200', default => 'bg-rose-50 text-rose-700 ring-rose-200'};
        $requestedQty = (int) ($queueOrder['requested_qty'] ?? $items->sum('quantity'));
        $purchasedQty = (int) ($queueOrder['purchased_qty'] ?? $items->sum('purchased_qty'));
        $remainingQty = (int) ($queueOrder['remaining_to_buy_qty'] ?? $items->sum('remaining_to_buy_qty'));
        $awaitingQty = (int) ($queueOrder['awaiting_arrival_qty'] ?? $items->sum('awaiting_arrival_qty'));
        $problemQty = (int) ($queueOrder['problem_qty'] ?? $items->sum('problem_qty'));
        $inspectionQty = (int) ($queueOrder['inspection_count'] ?? $items->filter(fn ($i) => (int)($i->requires_inspection ?? 0) === 1)->count());
        $activeTab = $activeTab ?? 'overview';
        $issueTypeLabels = [
            'out_of_stock' => 'Out of Stock',
            'price_increase' => 'Price Increase',
            'retailer_restriction' => 'Retailer Restriction',
            'retailer_cancelled' => 'Retailer Cancelled',
            'awaiting_customer_decision' => 'Awaiting Customer Decision',
            'supplier_delay' => 'Supplier Delay',
            'wrong_product_link' => 'Wrong Product Link',
            'supplier_cancelled_after_purchase' => 'Supplier Cancelled After Purchase',
            'lost_in_transit' => 'Lost In Transit',
            'damaged_after_purchase' => 'Damaged After Purchase',
            'wrong_item_received' => 'Wrong Item Received',
            'missing_from_parcel' => 'Missing From Parcel',
            'supplier_refunded_dabba' => 'Supplier Refunded Dabba',
            'replacement_expected' => 'Replacement Expected',
            'other' => 'Other',
        ];
        $severityClasses = [
            'low' => 'bg-slate-50 text-slate-700 ring-slate-200',
            'medium' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'high' => 'bg-rose-50 text-rose-700 ring-rose-200',
        ];
        $statusClasses = [
            'open' => 'bg-blue-50 text-blue-700 ring-blue-200',
            'awaiting_customer' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'returned_to_buy' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
            'resolved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        ];
        $issueStageLabels = [
            'pre_purchase' => 'Pre-purchase',
            'post_purchase' => 'Post-purchase',
            'arrival' => 'Arrival',
        ];
        $arrivalExpectationLabels = [
            'expected' => 'Expected',
            'replacement_expected' => 'Replacement expected',
            'not_expected' => 'Not expected',
        ];
        $prePurchaseIssuesForView = $issues->filter(fn ($issue) => ($issue->issue_stage ?? 'pre_purchase') === 'pre_purchase')->values();
        $postPurchaseIssuesForView = $issues->filter(fn ($issue) => in_array(($issue->issue_stage ?? 'pre_purchase'), ['post_purchase', 'arrival'], true))->values();
        $activeIssueStatuses = ['open', 'awaiting_customer'];
        $closedIssueStatuses = ['resolved', 'cancelled', 'returned_to_buy'];
        $prePurchaseIssueCount = $prePurchaseIssuesForView->filter(fn ($issue) => in_array((string) $issue->status, $activeIssueStatuses, true))->count();
        $postPurchaseIssueCount = $postPurchaseIssuesForView->filter(fn ($issue) => in_array((string) $issue->status, $activeIssueStatuses, true))->count();

        $isIssueTab = in_array($activeTab, ['problems', 'exceptions'], true);
        $issueView = request('issue_view');
        $activeIssueView = $activeTab === 'exceptions' ? 'post' : (in_array($issueView, ['pre', 'post'], true) ? $issueView : 'pre');
        $currentIssueSetForView = $activeIssueView === 'post' ? $postPurchaseIssuesForView : $prePurchaseIssuesForView;
        $activeIssuesForView = $currentIssueSetForView->filter(fn ($issue) => in_array((string) $issue->status, $activeIssueStatuses, true))->values();
        $resolvedIssuesForView = $currentIssueSetForView->reject(fn ($issue) => in_array((string) $issue->status, $activeIssueStatuses, true))->values();
        $issuePageTitle = $activeIssueView === 'post' ? 'Purchased item problems' : 'Pre-purchase problems';
        $issueEmptyTitle = $activeIssueView === 'post' ? 'No active purchased item problems.' : 'No active pre-purchase problems.';
        $issueEmptyText = $activeIssueView === 'post'
            ? 'Cancelled, lost, damaged, wrong, missing or refunded purchased items will appear here.'
            : 'Out of stock, price changes, bad links, restrictions and customer decisions before purchase will appear here.';

        $purchasedProblemSearch = trim((string) request('problem_search', ''));
        $purchasedProblemRows = $items
            ->filter(fn ($item) => (int) ($item->purchased_qty ?? 0) > 0)
            ->filter(function ($item) use ($purchasedProblemSearch, $customer, $orderNumber) {
                if ($purchasedProblemSearch === '') {
                    return true;
                }

                $haystack = strtolower(implode(' ', [
                    $customer,
                    $orderNumber,
                    $item->item_name ?? '',
                    $item->product_code ?? '',
                    $item->product_url ?? '',
                    $item->retailer_name ?? '',
                    $item->marketplace_seller ?? '',
                ]));

                return str_contains($haystack, strtolower($purchasedProblemSearch));
            })
            ->values();
    @endphp

    <div class="mx-auto max-w-7xl space-y-5">
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
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <a href="{{ route('purchasing.index') }}" class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600 hover:text-indigo-800">← Back to Purchasing Desk</a>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-black tracking-tight text-slate-950">Order #{{ $orderNumber }}</h1>
                        <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $paymentClass }}">{{ $paymentLabel }}</span>
                        @if ($inspectionQty > 0)
                            <span class="rounded-full bg-purple-50 px-3 py-1.5 text-xs font-black text-purple-700 ring-1 ring-purple-200">{{ $inspectionQty }} package check</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm font-bold text-slate-700">{{ $customer }}</p>
                    @if ($order->bill_to_email)
                        <p class="mt-1 text-xs font-semibold text-slate-400">{{ $order->bill_to_email }}</p>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:min-w-[560px]">
                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Order total</p><p class="mt-1 text-lg font-black text-slate-950">{{ $money($order->grand_total ?? 0) }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Requested</p><p class="mt-1 text-lg font-black text-slate-950">{{ $requestedQty }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Purchased</p><p class="mt-1 text-lg font-black text-slate-950">{{ $purchasedQty }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">To Buy</p><p class="mt-1 text-lg font-black text-slate-950">{{ $remainingQty }}</p></div>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4 text-xs font-black">
                <span class="rounded-full {{ $prePurchaseIssueCount > 0 ? 'bg-amber-50 text-amber-700 ring-amber-100' : 'bg-slate-50 text-slate-700 ring-slate-200' }} px-3 py-1.5 ring-1">Current purchasing problems: {{ $prePurchaseIssueCount }}</span>
                <span class="rounded-full {{ $postPurchaseIssueCount > 0 ? 'bg-rose-50 text-rose-700 ring-rose-100' : 'bg-slate-50 text-slate-700 ring-slate-200' }} px-3 py-1.5 ring-1">Current purchased item problems: {{ $postPurchaseIssueCount }}</span>
                <a href="{{ route('orders.show', $order->id) }}" class="rounded-full bg-indigo-50 px-3 py-1.5 text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-100">View full order ↗</a>
            </div>
        </section>

        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-2 shadow-sm">
            <div class="flex flex-wrap gap-2 text-sm font-black">
                @foreach (($tabs ?? []) as $tabKey => $tabLabel)
                    @continue($tabKey === 'exceptions')
                    @php
                        $isActiveMainTab = $tabKey === 'problems'
                            ? in_array($activeTab, ['problems', 'exceptions'], true)
                            : $activeTab === $tabKey;
                        $displayTabLabel = $tabKey === 'problems' ? 'Current Issues' : $tabLabel;
                    @endphp
                    <a href="{{ route('purchasing.orders.show', ['order' => $order->id, 'tab' => $tabKey]) }}" class="rounded-2xl px-4 py-2 ring-1 transition {{ $isActiveMainTab ? 'bg-indigo-600 text-white ring-indigo-600 shadow-sm' : 'bg-white text-slate-600 ring-slate-200 hover:bg-indigo-50 hover:text-indigo-700' }}">{{ $displayTabLabel }}</a>
                @endforeach
            </div>
        </section>

        @if ($activeTab === 'purchased_item_problems')
            <section class="space-y-4">
                <div class="rounded-[1.5rem] border border-rose-200 bg-rose-50/70 p-5 shadow-sm">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-rose-600">After purchase</p>
                            <h2 class="mt-1 text-xl font-black text-slate-950">Purchased Item Problems</h2>
                            <p class="mt-1 max-w-3xl text-sm font-semibold leading-6 text-slate-600">
                                Use this when an item was already bought and something went wrong: supplier cancelled, lost in transit, damaged, wrong item, missing item, supplier refund, or replacement decision.
                            </p>
                        </div>
                        <div class="rounded-2xl bg-white px-4 py-3 text-sm font-black text-rose-700 ring-1 ring-rose-200">
                            {{ $purchasedProblemRows->count() }} purchased item{{ $purchasedProblemRows->count() === 1 ? '' : 's' }} found
                        </div>
                    </div>
                </div>

                <form method="GET" action="{{ route('purchasing.orders.show', $order->id) }}" class="rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm">
                    <input type="hidden" name="tab" value="purchased_item_problems">
                    <div class="grid gap-3 lg:grid-cols-[1fr_auto]">
                        <label class="block">
                            <span class="text-xs font-black uppercase tracking-wide text-slate-500">Find purchased item</span>
                            <input
                                type="text"
                                name="problem_search"
                                value="{{ $purchasedProblemSearch }}"
                                placeholder="Search customer, order number, item, product code, retailer or link"
                                class="mt-1 h-12 w-full rounded-2xl border-slate-300 text-sm font-bold shadow-sm focus:border-rose-400 focus:ring-rose-200"
                            >
                        </label>
                        <div class="flex items-end gap-2">
                            <button class="h-12 rounded-2xl bg-rose-600 px-5 text-sm font-black text-white shadow-sm hover:bg-rose-700">Search</button>
                            @if ($purchasedProblemSearch !== '')
                                <a href="{{ route('purchasing.orders.show', ['order' => $order->id, 'tab' => 'purchased_item_problems']) }}" class="inline-flex h-12 items-center rounded-2xl border border-slate-200 bg-white px-4 text-sm font-black text-slate-600 hover:bg-slate-50">Clear</a>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="hidden grid-cols-[120px_1fr_150px_110px_120px_170px] gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 text-[10px] font-black uppercase tracking-wide text-slate-400 lg:grid">
                        <div>Order</div>
                        <div>Item</div>
                        <div>Retailer</div>
                        <div>Purchased</div>
                        <div>Expected</div>
                        <div>Action</div>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse ($purchasedProblemRows as $item)
                            @php
                                $openPurchasedForProblem = max(0, (int) ($item->awaiting_arrival_qty ?? 0));
                                $existingPostProblemQty = (int) ($item->active_post_purchase_issue_qty ?? 0);
                            @endphp
                            <article class="grid gap-3 px-4 py-4 text-sm lg:grid-cols-[120px_1fr_150px_110px_120px_170px] lg:items-center">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 lg:hidden">Order</p>
                                    <p class="font-black text-slate-900">#{{ $orderNumber }}</p>
                                    <p class="mt-0.5 text-xs font-semibold text-slate-500">{{ $customer }}</p>
                                </div>

                                <div class="min-w-0">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 lg:hidden">Item</p>
                                    <p class="font-black leading-5 text-slate-950">{{ $item->item_name }}</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ $item->product_code ?: 'No product code' }}</p>
                                    @if ($existingPostProblemQty > 0)
                                        <span class="mt-2 inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-black text-rose-700 ring-1 ring-rose-200">{{ $existingPostProblemQty }} existing problem qty</span>
                                    @endif
                                </div>

                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 lg:hidden">Retailer</p>
                                    <p class="font-bold text-slate-700">{{ $item->retailer_name ?: 'Unknown retailer' }}</p>
                                </div>

                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 lg:hidden">Purchased</p>
                                    <p class="font-black text-slate-950">{{ (int) ($item->purchased_qty ?? 0) }}</p>
                                </div>

                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 lg:hidden">Expected</p>
                                    <p class="font-black {{ $openPurchasedForProblem > 0 ? 'text-emerald-700' : 'text-slate-400' }}">{{ $openPurchasedForProblem }}</p>
                                </div>

                                <div>
                                    @if ($openPurchasedForProblem > 0)
                                        <button
                                            type="button"
                                            data-issue-open
                                            data-order-item-id="{{ $item->item_id }}"
                                            data-remaining="{{ $openPurchasedForProblem }}"
                                            data-item-name="{{ e($item->item_name) }}"
                                            data-issue-stage="post_purchase"
                                            data-arrival-expectation="not_expected"
                                            class="inline-flex w-full items-center justify-center rounded-2xl bg-rose-600 px-4 py-2.5 text-xs font-black text-white shadow-sm hover:bg-rose-700"
                                        >
                                            ⚠ Record Problem
                                        </button>
                                    @else
                                        <div class="rounded-2xl bg-slate-50 px-3 py-2 text-xs font-bold leading-5 text-slate-500 ring-1 ring-slate-100">No expected quantity left to mark.</div>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="p-10 text-center">
                                <p class="text-lg font-black text-slate-900">No purchased items found.</p>
                                <p class="mt-2 text-sm font-semibold text-slate-500">Try another search, or check that the item has actually been purchased and is still expected to arrive.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        @endif

        @if ($isIssueTab)
            <section class="space-y-3">
                <div class="rounded-[1.5rem] border {{ $activeIssueView === 'post' ? 'border-rose-200 bg-rose-50/70' : 'border-amber-200 bg-amber-50/70' }} p-5 shadow-sm">
                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] {{ $activeIssueView === 'post' ? 'text-rose-600' : 'text-amber-600' }}">{{ $activeIssueView === 'post' ? 'After purchase' : 'Before purchase' }}</p>
                            <h2 class="mt-1 text-xl font-black text-slate-950">{{ $issuePageTitle }}</h2>
                            <p class="mt-1 text-sm font-semibold text-slate-600">
                                {{ $activeIssueView === 'post'
                                    ? 'Problems after an item was bought: supplier cancelled, lost, damaged, wrong item, missing parcel, supplier refund or replacement decision.'
                                    : 'Problems before an item can be bought: out of stock, price increase, bad link, restriction or customer decision needed.' }}
                            </p>
                        </div>
                        <div class="rounded-2xl bg-white px-4 py-3 text-sm font-black text-slate-700 ring-1 ring-slate-200">
                            {{ $activeIssuesForView->count() }} current · {{ $resolvedIssuesForView->count() }} closed history
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 rounded-[1.25rem] border border-white/70 bg-white/80 p-2 text-sm font-black shadow-sm">
                    <a href="{{ route('purchasing.orders.show', ['order' => $order->id, 'tab' => 'problems', 'issue_view' => 'pre']) }}"
                       class="rounded-2xl px-4 py-2 ring-1 transition {{ $activeIssueView === 'pre' ? 'bg-amber-500 text-white ring-amber-500 shadow-sm' : 'bg-white text-amber-800 ring-amber-200 hover:bg-amber-50' }}">
                        Pre-Purchase Problems <span class="opacity-80">{{ $prePurchaseIssueCount }}</span>
                    </a>
                    <a href="{{ route('purchasing.orders.show', ['order' => $order->id, 'tab' => 'problems', 'issue_view' => 'post']) }}"
                       class="rounded-2xl px-4 py-2 ring-1 transition {{ $activeIssueView === 'post' ? 'bg-rose-600 text-white ring-rose-600 shadow-sm' : 'bg-white text-rose-800 ring-rose-200 hover:bg-rose-50' }}">
                        Purchased Item Problems <span class="opacity-80">{{ $postPurchaseIssueCount }}</span>
                    </a>
                </div>

                <div class="rounded-[1.25rem] border border-white/70 bg-white/80 p-4 text-sm font-semibold leading-6 text-slate-600 shadow-sm">
                    <span class="font-black text-slate-900">Current queue:</span> only unresolved items needing action are shown below. Closed / returned-to-buy / resolved records are kept separately under history and should not be worked from there.
                </div>

                @forelse ($activeIssuesForView as $issue)
                    @php
                        $issueTypeLabel = $issueTypeLabels[$issue->issue_type] ?? ucfirst(str_replace('_', ' ', (string) $issue->issue_type));
                        $severityClass = $severityClasses[$issue->severity] ?? $severityClasses['medium'];
                        $statusClass = $statusClasses[$issue->status] ?? 'bg-slate-50 text-slate-700 ring-slate-200';
                        $activeCardBorder = $activeIssueView === 'post' ? 'border-rose-200' : 'border-amber-200';
                        $activeQueuePillClass = $activeIssueView === 'post' ? 'bg-rose-600 text-white ring-rose-600' : 'bg-amber-500 text-white ring-amber-500';
                    @endphp
                    <article class="rounded-[1.5rem] border {{ $activeCardBorder }} bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $activeQueuePillClass }}">CURRENT - NEEDS RESOLUTION</span>
                                    <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $severityClass }}">{{ strtoupper((string) $issue->severity) }}</span>
                                    <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $statusClass }}">{{ str_replace('_', ' ', strtoupper((string) $issue->status)) }}</span>
                                    <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-black text-amber-700 ring-1 ring-amber-200">{{ $issueTypeLabel }}</span>
                                    <span class="rounded-full bg-sky-50 px-3 py-1.5 text-xs font-black text-sky-700 ring-1 ring-sky-200">{{ $issueStageLabels[$issue->issue_stage ?? 'pre_purchase'] ?? 'Pre-purchase' }}</span>
                                    @if (($issue->issue_stage ?? 'pre_purchase') !== 'pre_purchase')
                                        <span class="rounded-full bg-purple-50 px-3 py-1.5 text-xs font-black text-purple-700 ring-1 ring-purple-200">Arrival: {{ $arrivalExpectationLabels[$issue->arrival_expectation ?? 'expected'] ?? 'Expected' }}</span>
                                    @endif
                                    @if ((int) $issue->requires_customer_action === 1)
                                        <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-black text-amber-700 ring-1 ring-amber-200">Customer action</span>
                                    @endif
                                </div>

                                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                                    <div class="hidden grid-cols-[160px_1fr_90px_120px_70px] gap-3 bg-slate-50 px-4 py-3 text-[10px] font-black uppercase tracking-wide text-slate-400 md:grid">
                                        <div>Product code</div>
                                        <div>Product description</div>
                                        <div>Qty</div>
                                        <div>Price</div>
                                        <div>Link</div>
                                    </div>
                                    <div class="grid gap-3 px-4 py-4 text-sm md:grid-cols-[160px_1fr_90px_120px_70px] md:items-center">
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 md:hidden">Product code</p>
                                            <p class="font-black text-slate-800">{{ $issue->product_code ?: '—' }}</p>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 md:hidden">Product description</p>
                                            <p class="font-black leading-5 text-slate-950">{{ $issue->item_name }}</p>
                                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $issue->retailer_name }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 md:hidden">Qty</p>
                                            <p class="font-black text-slate-950">{{ (int) $issue->qty }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 md:hidden">Price</p>
                                            <p class="font-black text-slate-950">{{ $money($issue->unit_price ?? 0) }}</p>
                                        </div>
                                        <div>
                                            @if ($issue->product_url)
                                                <a href="{{ $issue->product_url }}" target="_blank" rel="noopener" class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-sm font-black text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-100" title="Open product link">↗</a>
                                            @else
                                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-sm font-black text-slate-300 ring-1 ring-slate-100">—</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                @if ($issue->notes)
                                    <p class="mt-3 rounded-2xl bg-slate-50 p-3 text-sm font-semibold leading-6 text-slate-700 ring-1 ring-slate-100">{{ $issue->notes }}</p>
                                @endif
                            </div>
                            <div class="grid gap-2 text-sm sm:grid-cols-3 lg:min-w-[520px]">
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Reported by</p><p class="mt-1 font-black text-slate-950">{{ $issue->created_by_name ?: 'Unknown' }}</p></div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Reported</p><p class="mt-1 font-black text-slate-950">{{ $issue->created_at ? \Carbon\Carbon::parse($issue->created_at)->format('d M Y') : '—' }}</p></div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Customer contacted</p><p class="mt-1 font-black text-slate-950">{{ $issue->customer_contacted_at ? \Carbon\Carbon::parse($issue->customer_contacted_at)->format('d M Y') : '—' }}</p></div>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 border-t border-slate-100 pt-4 lg:grid-cols-2">
                            <details class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <summary class="cursor-pointer text-sm font-black text-slate-800">Update issue</summary>
                                <form method="POST" action="{{ route('purchasing.problems.update', $issue->id) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                                    @csrf
                                    @method('PATCH')
                                    <label class="text-xs font-bold text-slate-600">Issue type
                                        <select name="issue_type" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white text-sm font-bold">
                                            @foreach ($issueTypeLabels as $value => $label)
                                                <option value="{{ $value }}" {{ $issue->issue_type === $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label class="text-xs font-bold text-slate-600">Issue stage
                                        <select name="issue_stage" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white text-sm font-bold">
                                            <option value="pre_purchase" @selected(($issue->issue_stage ?? 'pre_purchase') === 'pre_purchase')>Pre-purchase / could not buy</option>
                                            <option value="post_purchase" @selected(($issue->issue_stage ?? 'pre_purchase') === 'post_purchase')>Purchased item problem / bought but problem later</option>
                                            <option value="arrival" @selected(($issue->issue_stage ?? 'pre_purchase') === 'arrival')>Arrival / warehouse problem</option>
                                        </select>
                                    </label>
                                    <label class="text-xs font-bold text-slate-600">Arrival expectation
                                        <select name="arrival_expectation" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white text-sm font-bold">
                                            <option value="expected" @selected(($issue->arrival_expectation ?? 'expected') === 'expected')>Still expected</option>
                                            <option value="replacement_expected" @selected(($issue->arrival_expectation ?? 'expected') === 'replacement_expected')>Replacement expected</option>
                                            <option value="not_expected" @selected(($issue->arrival_expectation ?? 'expected') === 'not_expected')>Not expected / remove from arrivals</option>
                                        </select>
                                    </label>
                                    <label class="text-xs font-bold text-slate-600">Severity
                                        <select name="severity" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white text-sm font-bold">
                                            <option value="low" {{ $issue->severity === 'low' ? 'selected' : '' }}>Low</option>
                                            <option value="medium" {{ $issue->severity === 'medium' ? 'selected' : '' }}>Medium</option>
                                            <option value="high" {{ $issue->severity === 'high' ? 'selected' : '' }}>High</option>
                                        </select>
                                    </label>
                                    <label class="sm:col-span-2 inline-flex items-center gap-2 text-xs font-bold text-slate-600">
                                        <input type="checkbox" name="requires_customer_action" value="1" class="rounded border-slate-300 text-amber-600" {{ (int) $issue->requires_customer_action === 1 ? 'checked' : '' }}>
                                        Customer action required
                                    </label>
                                    <label class="sm:col-span-2 text-xs font-bold text-slate-600">Notes
                                        <textarea name="notes" rows="3" class="mt-1 w-full rounded-2xl border-slate-200 bg-white text-sm font-bold">{{ $issue->notes }}</textarea>
                                    </label>
                                    <label class="sm:col-span-2 inline-flex items-center gap-2 text-xs font-bold text-slate-600">
                                        <input type="checkbox" name="customer_replied" value="1" class="rounded border-slate-300 text-emerald-600">
                                        Mark customer replied now
                                    </label>
                                    <button class="sm:col-span-2 rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-black text-white hover:bg-indigo-700">Save update</button>
                                </form>
                            </details>

                            <details class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4">
                                <summary class="cursor-pointer text-sm font-black text-emerald-900">Resolve issue</summary>
                                <form method="POST" action="{{ route('purchasing.problems.resolve', $issue->id) }}" class="mt-4 grid gap-3">
                                    @csrf
                                    <div class="rounded-2xl border border-indigo-200 bg-white p-3 text-xs font-bold leading-5 text-slate-600">
                                        Choose what should happen to this quantity next. <span class="font-black text-indigo-700">Return to purchase queue</span> will put the item back into To Buy so it can be purchased normally. Terminal options close the issue without creating any finance, refund, wallet or invoice changes.
                                    </div>
                                    <label class="text-xs font-bold text-emerald-800">Resolution action
                                        <select name="resolution_type" class="mt-1 h-11 w-full rounded-2xl border-emerald-200 bg-white text-sm font-bold">
                                            <option value="return_to_buy">Return To Purchase Queue</option>
                                            <option value="replacement_expected">Replacement Expected</option>
                                            <option value="closed_no_replacement">No Replacement Expected</option>
                                            <option value="supplier_refunded">Supplier Refunded Dabba</option>
                                            <option value="customer_cancelled">Customer Cancelled - Do Not Buy</option>
                                            <option value="customer_refunded">Customer Refunded - Do Not Buy</option>
                                            <option value="duplicate_item">Duplicate Item - Do Not Buy</option>
                                            <option value="no_longer_required">No Longer Required - Do Not Buy</option>
                                            <option value="written_off">Written Off / Absorbed Loss</option>
                                            <option value="other">Other / Closed</option>
                                        </select>
                                    </label>
                                    <label class="text-xs font-bold text-emerald-800">Resolution notes
                                        <textarea name="resolution_notes" rows="3" placeholder="What was decided?" class="mt-1 w-full rounded-2xl border-emerald-200 bg-white text-sm font-bold"></textarea>
                                    </label>
                                    <button class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-black text-white hover:bg-emerald-700">Apply resolution</button>
                                </form>
                            </details>
                        </div>

                        @if (! $issue->customer_contacted_at)
                            <form method="POST" action="{{ route('purchasing.problems.contacted', $issue->id) }}" class="mt-3">
                                @csrf
                                <button class="rounded-2xl bg-amber-600 px-4 py-2 text-sm font-black text-white hover:bg-amber-700">Mark customer contacted</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                        <p class="text-lg font-black text-slate-900">{{ $issueEmptyTitle }}</p>
                        <p class="mt-2 text-sm font-semibold text-slate-500">{{ $issueEmptyText }}</p>
                    </div>
                @endforelse

                @if ($resolvedIssuesForView->isNotEmpty())
                    <details class="rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-5 shadow-sm">
                        <summary class="cursor-pointer text-sm font-black text-slate-800">Closed history - not active work queue ({{ $resolvedIssuesForView->count() }})</summary>
                        <p class="mt-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold leading-6 text-slate-600">These records are kept for audit/history. They are resolved, returned to buy, cancelled, or otherwise closed and should not be treated as current operator work.</p>
                        <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                            <div class="hidden grid-cols-[150px_1fr_90px_190px] gap-3 bg-slate-100 px-4 py-3 text-[10px] font-black uppercase tracking-wide text-slate-500 md:grid">
                                <div>Status</div>
                                <div>Item</div>
                                <div>Qty</div>
                                <div>Resolution</div>
                            </div>
                            @foreach ($resolvedIssuesForView as $historyIssue)
                                @php
                                    $historyStatusClass = $statusClasses[$historyIssue->status] ?? 'bg-slate-50 text-slate-700 ring-slate-200';
                                @endphp
                                <div class="grid gap-3 border-t border-slate-100 bg-white px-4 py-3 text-sm first:border-t-0 md:grid-cols-[150px_1fr_90px_190px] md:items-center">
                                    <div><span class="rounded-full px-2.5 py-1 text-[10px] font-black ring-1 {{ $historyStatusClass }}">HISTORY: {{ str_replace('_', ' ', strtoupper((string) $historyIssue->status)) }}</span></div>
                                    <div>
                                        <p class="font-bold text-slate-800">{{ $historyIssue->item_name }}</p>
                                        <p class="mt-1 text-xs font-semibold text-slate-400">{{ $historyIssue->retailer_name }}</p>
                                    </div>
                                    <div class="font-black text-slate-950">{{ (int) $historyIssue->qty }}</div>
                                    <div class="text-xs font-bold text-slate-500">{{ str_replace('_', ' ', ucfirst((string) ($historyIssue->resolution_type ?: '—'))) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
            </section>
        @endif

        @if (! $isIssueTab)
        <section class="space-y-4">
            @forelse ($retailers as $retailer)
                @php
                    $retailerItems = collect($retailer['items']);
                    $remainingItems = $retailerItems->filter(fn ($item) => (int) $item->remaining_to_buy_qty > 0)->values();
                    $waitingQty = (int) $remainingItems->sum('remaining_to_buy_qty');
                    $purchasedQtyForRetailer = (int) $retailerItems->sum('purchased_qty');
                    $awaitingQtyForRetailer = (int) $retailerItems->sum('awaiting_arrival_qty');
                    $problemQtyForRetailer = (int) $retailerItems->sum('problem_qty');
                    $inspectionForRetailer = $retailerItems->filter(fn ($item) => (int)($item->requires_inspection ?? 0) === 1)->count();
                    $retailerDeliveryFees = round((float) $retailerItems->sum(fn ($item) => (float) ($item->visible_delivery_fee ?? 0)), 2);
                    $waitingDeliveryFees = round((float) $remainingItems->sum(fn ($item) => (float) ($item->visible_delivery_fee ?? 0)), 2);
                    $waitingValue = $remainingItems->sum(fn ($item) => ((float) $item->unit_price) * ((int) $item->remaining_to_buy_qty));
                    $cardRing = $inspectionForRetailer > 0 ? 'border-purple-200' : ($problemQtyForRetailer > 0 ? 'border-rose-200' : ($waitingQty > 0 ? 'border-indigo-200' : 'border-emerald-200'));
                    $statusLabel = $problemQtyForRetailer > 0 ? 'Needs attention' : ($waitingQty > 0 ? 'Awaiting Purchase' : 'Purchased');
                    $statusClass = $problemQtyForRetailer > 0 ? 'bg-rose-50 text-rose-700 ring-rose-200' : ($waitingQty > 0 ? 'bg-indigo-50 text-indigo-700 ring-indigo-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200');
                    $purchaseFormId = 'purchase-retailer-' . ($retailer['retailer_id'] ?? 'unknown') . '-' . $loop->index;
                @endphp

                <article class="overflow-visible rounded-[1.75rem] border {{ $cardRing }} bg-white shadow-sm" data-retailer-card>
                    <div class="border-b border-slate-100 p-5 sm:p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-2xl font-black text-slate-950">{{ $retailer['retailer_name'] }}</h2>
                                    <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $statusClass }}">{{ $statusLabel }}</span>
                                    @if ($inspectionForRetailer > 0)
                                        <span class="rounded-full bg-purple-50 px-3 py-1.5 text-xs font-black text-purple-700 ring-1 ring-purple-200">Package check</span>
                                    @endif
                                </div>
                                <p class="mt-2 text-sm font-bold text-slate-500">{{ $remainingItems->count() }} item line{{ $remainingItems->count() === 1 ? '' : 's' }} waiting · {{ $waitingQty }} qty to buy</p>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-3 xl:grid-cols-6 lg:min-w-[760px]">
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Waiting value</p><p class="mt-1 font-black text-slate-950">{{ $money($waitingValue) }}</p></div>
                                <div class="rounded-2xl bg-amber-50 p-3 ring-1 ring-amber-100"><p class="text-[10px] font-black uppercase tracking-wide text-amber-600">Delivery fees</p><p class="mt-1 font-black text-amber-950">{{ $money($retailerDeliveryFees) }}</p><p class="mt-0.5 text-[10px] font-bold text-amber-700">Waiting {{ $money($waitingDeliveryFees) }}</p></div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Purchased qty</p><p class="mt-1 font-black text-slate-950">{{ $purchasedQtyForRetailer }}</p></div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Awaiting qty</p><p class="mt-1 font-black text-slate-950">{{ $awaitingQtyForRetailer }}</p></div>
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Problems</p><p class="mt-1 font-black text-slate-950">{{ $problemQtyForRetailer }}</p></div>
                                <div class="rounded-2xl bg-purple-50 p-3 ring-1 ring-purple-100"><p class="text-[10px] font-black uppercase tracking-wide text-purple-500">Package Check</p><p class="mt-1 font-black text-purple-800">{{ $inspectionForRetailer }}</p></div>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6">
                        <input form="{{ $purchaseFormId }}" type="hidden" name="order_id" value="{{ $order->id }}">

                        @if ($remainingItems->isNotEmpty())
                            <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-black text-indigo-950"><span data-selected-lines>0</span> selected item lines</p>
                                    <p class="mt-1 text-xs font-bold text-indigo-700">Nothing is selected by default. Tick only the lines you are buying now.</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" data-select-all class="rounded-xl bg-white px-3 py-2 text-xs font-black text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-50">Select all</button>
                                    <button type="button" data-select-none class="rounded-xl bg-white px-3 py-2 text-xs font-black text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">Clear selection</button>
                                </div>
                            </div>
                        @endif

                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="hidden grid-cols-[56px_1fr_90px_150px_130px_130px_170px_64px] gap-3 bg-slate-50 px-4 py-3 text-[10px] font-black uppercase tracking-wide text-slate-400 md:grid">
                                <div>Buy</div><div>Item</div><div>Requested</div><div>Qty</div><div>Price</div><div>Delivery</div><div>Purple check</div><div>Link</div>
                            </div>

                            <div class="divide-y divide-slate-100">
                                @foreach ($retailerItems as $item)
                                    @php
                                        $remaining = (int) $item->remaining_to_buy_qty;
                                        $canBuy = $remaining > 0;
                                        $isPurple = (int)($item->requires_inspection ?? 0) === 1;
                                        $rowClass = $canBuy ? 'bg-white' : 'bg-slate-50/70';
                                        $history = collect($purchasesByRoot[$item->lineage_root_id] ?? []);
                                    @endphp

                                    <div class="grid gap-3 px-4 py-4 md:grid-cols-[56px_1fr_90px_150px_130px_130px_170px_64px] md:items-start {{ $rowClass }}">
                                        <div>
                                            @if ($canBuy)
                                                <label class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 ring-1 ring-slate-200">
                                                    <input form="{{ $purchaseFormId }}" type="checkbox" name="order_item_ids[]" value="{{ $item->item_id }}" data-line-checkbox class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                    <span class="text-xs font-black text-slate-700 md:hidden">Buy</span>
                                                </label>
                                            @else
                                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-sm font-black text-emerald-700 ring-1 ring-emerald-100">✓</span>
                                            @endif
                                        </div>

                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-black leading-5 text-slate-950">{{ $item->item_name }}</p>
                                            </div>
                                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $item->product_code ?: 'No product code' }} · {{ $item->retailer_name }}</p>
                                            @if ((float) ($item->visible_delivery_fee ?? 0) > 0)
                                                <p class="mt-1 text-xs font-black text-amber-700">Retailer delivery fee: {{ $money($item->visible_delivery_fee) }}</p>
                                            @endif

                                            @if ($canBuy)
                                                <button type="button"
                                                    class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-black text-amber-800 hover:bg-amber-100"
                                                    data-issue-open
                                                    data-order-item-id="{{ $item->item_id }}"
                                                    data-item-name="{{ e($item->item_name) }}"
                                                    data-remaining="{{ $remaining }}">
                                                    ⚠ Record purchasing issue
                                                </button>
                                            @endif

                                            @if ($history->isNotEmpty())
                                                <details class="mt-3 rounded-xl border border-slate-200 bg-white p-3">
                                                    <summary class="cursor-pointer text-xs font-black text-slate-700">Purchase history · {{ $history->count() }}</summary>
                                                    <div class="mt-3 space-y-2">
                                                        @foreach ($history as $purchase)
                                                            @php
                                                                $activeArrivalQty = (int) collect($arrivals)->where('order_item_purchase_id', $purchase->id)->sum('qty');
                                                                $wasUndone = ! empty($purchase->cancelled_at);
                                                                $postPurchaseAvailableQty = max(0, (int) $purchase->qty - $activeArrivalQty);
                                                            @endphp
                                                            <div class="rounded-xl border {{ $wasUndone ? 'border-slate-200 bg-slate-50' : 'border-indigo-100 bg-indigo-50/60' }} p-3 text-xs">
                                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                                    <p class="font-black text-slate-900">Qty {{ (int) $purchase->qty }} · {{ $money($purchase->purchase_unit_price ?? 0) }} · Ref {{ $purchase->retailer_order_reference ?: '—' }}</p>
                                                                    <span class="font-black {{ $wasUndone ? 'text-slate-400' : 'text-indigo-700' }}">{{ $wasUndone ? 'Undone' : 'Active' }}</span>
                                                                </div>
                                                                <p class="mt-1 font-semibold text-slate-500">ETA {{ $purchase->expected_uk_hub_at ? \Carbon\Carbon::parse($purchase->expected_uk_hub_at)->format('d M Y') : '—' }} · Ordered {{ $purchase->ordered_at ? \Carbon\Carbon::parse($purchase->ordered_at)->format('d M Y') : '—' }} · Arrived qty {{ $activeArrivalQty }}</p>

                                                                @if (! $wasUndone)
                                                                    <details class="mt-3 rounded-xl border border-indigo-100 bg-white p-3">
                                                                        <summary class="cursor-pointer text-xs font-black text-indigo-700">Edit purchase details</summary>
                                                                        <form method="POST" action="{{ route('purchasing.purchases.update', $purchase->id) }}" class="mt-3 space-y-3">
                                                                            @csrf
                                                                            @method('PATCH')
                                                                            <div class="grid gap-3 lg:grid-cols-2">
                                                                                <div>
                                                                                    <label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-500">Purchased from / supplier</label>
                                                                                    <select name="retailer_id" class="h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-xs font-bold text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                                                                        @foreach (($allRetailers ?? collect()) as $retailerOption)
                                                                                            <option value="{{ $retailerOption->id }}" @selected((int) $purchase->retailer_id === (int) $retailerOption->id)>{{ $retailerOption->name }}</option>
                                                                                        @endforeach
                                                                                    </select>
                                                                                </div>
                                                                                <div>
                                                                                    <label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-500">Retailer order reference *</label>
                                                                                    <input name="retailer_order_reference" required maxlength="255" value="{{ $purchase->retailer_order_reference }}" class="h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-xs font-bold text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                                                                </div>
                                                                                <div>
                                                                                    <label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-500">ETA / expected UK hub *</label>
                                                                                    <input name="expected_uk_hub_at" type="date" required value="{{ $purchase->expected_uk_hub_at ? \Carbon\Carbon::parse($purchase->expected_uk_hub_at)->format('Y-m-d') : '' }}" class="h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-xs font-bold text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                                                                </div>
                                                                                <div>
                                                                                    <label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-500">Ordered date</label>
                                                                                    <input name="ordered_at" type="date" value="{{ $purchase->ordered_at ? \Carbon\Carbon::parse($purchase->ordered_at)->format('Y-m-d') : '' }}" class="h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-xs font-bold text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                                                                </div>
                                                                                <div>
                                                                                    <label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-500">Purchase price</label>
                                                                                    <input name="purchase_unit_price" type="number" min="0" step="0.01" value="{{ number_format((float) ($purchase->purchase_unit_price ?? 0), 2, '.', '') }}" class="h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-xs font-bold text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                                                                </div>
                                                                                <div>
                                                                                    <label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-500">Marketplace seller</label>
                                                                                    <input name="marketplace_seller" maxlength="255" value="{{ $purchase->marketplace_seller }}" class="h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-xs font-bold text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                                                                </div>
                                                                            </div>
                                                                            <div>
                                                                                <label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-500">Internal note</label>
                                                                                <textarea name="note" rows="2" maxlength="2000" class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">{{ $purchase->note }}</textarea>
                                                                            </div>
                                                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                                                <p class="text-[11px] font-bold text-slate-500">Quantity stays {{ (int) $purchase->qty }}. Use undo/re-record if the quantity itself is wrong.</p>
                                                                                <button class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-black text-white hover:bg-indigo-700">Save purchase changes</button>
                                                                            </div>
                                                                        </form>
                                                                    </details>
                                                                @endif

                                                                @if (! $wasUndone && $activeArrivalQty === 0)
                                                                    <form method="POST" action="{{ route('purchasing.purchases.undo', $purchase->id) }}" class="mt-2 flex flex-col gap-2 sm:flex-row" data-confirm-undo>
                                                                        @csrf
                                                                        <input name="reason" required placeholder="Undo reason" class="h-9 flex-1 rounded-xl border-slate-200 bg-white px-3 text-xs font-bold focus:border-rose-300 focus:ring-rose-200">
                                                                        <button class="rounded-xl bg-rose-600 px-3 py-2 text-xs font-black text-white hover:bg-rose-700">Undo purchase</button>
                                                                    </form>
                                                                @elseif (! $wasUndone)
                                                                    <p class="mt-2 rounded-lg bg-white px-2 py-1 font-bold text-slate-500 ring-1 ring-slate-200">Cannot undo while arrival exists.</p>
                                                                @endif

                                                                @if (! $wasUndone && $postPurchaseAvailableQty > 0)
                                                                    <button type="button"
                                                                        class="mt-3 flex w-full items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-black text-rose-800 shadow-sm hover:bg-rose-100"
                                                                        data-issue-open
                                                                        data-order-item-id="{{ $item->item_id }}"
                                                                        data-item-name="{{ e($item->item_name) }}"
                                                                        data-remaining="{{ $postPurchaseAvailableQty }}"
                                                                        data-issue-stage="post_purchase"
                                                                        data-arrival-expectation="not_expected">
                                                                        <span>⚠</span>
                                                                        <span>Problem after purchase</span>
                                                                    </button>
                                                                    <p class="mt-1 text-[11px] font-bold text-rose-600">Use for supplier cancelled, lost, damaged, wrong item, missing parcel or supplier refund.</p>
                                                                @elseif (! $wasUndone)
                                                                    <p class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-[11px] font-bold text-slate-500 ring-1 ring-slate-200">No open purchased quantity left for a purchased item problem.</p>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </details>
                                            @endif
                                        </div>

                                        <div><span class="text-xs font-black uppercase tracking-wide text-slate-400 md:hidden">Requested</span><p class="font-black text-slate-900">{{ (int) $item->quantity }}</p></div>

                                        <div>
                                            <label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-500">Qty</label>
                                            @if ($canBuy)
                                                <input form="{{ $purchaseFormId }}" name="qty[{{ $item->item_id }}]" type="number" min="0" max="{{ $remaining }}" value="{{ $remaining }}" data-line-qty class="h-12 w-full rounded-xl border-2 border-indigo-400 bg-indigo-50 px-3 text-sm font-black text-slate-950 shadow-inner ring-2 ring-indigo-100 focus:border-indigo-600 focus:bg-white focus:ring-indigo-300">
                                                <p class="mt-1 text-[11px] font-bold text-slate-500">{{ $remaining }} left</p>
                                            @else
                                                <p class="font-black text-emerald-700">0 left</p>
                                            @endif
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-500">Price</label>
                                            @if ($canBuy)
                                                <input form="{{ $purchaseFormId }}" name="purchase_unit_price[{{ $item->item_id }}]" type="number" min="0" step="0.01" value="{{ number_format((float) $item->unit_price, 2, '.', '') }}" class="h-12 w-full rounded-xl border-2 border-indigo-400 bg-indigo-50 px-3 text-sm font-black text-slate-950 shadow-inner ring-2 ring-indigo-100 focus:border-indigo-600 focus:bg-white focus:ring-indigo-300">
                                            @else
                                                <span class="text-sm font-bold text-slate-400">—</span>
                                            @endif
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-500">Delivery</label>
                                            @if ((float) ($item->visible_delivery_fee ?? 0) > 0)
                                                <p class="rounded-xl bg-amber-50 px-3 py-3 text-sm font-black text-amber-800 ring-1 ring-amber-100">{{ $money($item->visible_delivery_fee) }}</p>
                                                @if ((float) ($item->item_retailer_delivery_fee ?? 0) > 0 && (float) ($item->retailer_delivery_allocated ?? 0) > 0)
                                                    <p class="mt-1 text-[10px] font-bold leading-4 text-amber-700">Item {{ $money($item->item_retailer_delivery_fee) }} · Shared {{ $money($item->retailer_delivery_allocated) }}</p>
                                                @endif
                                            @else
                                                <span class="text-sm font-bold text-slate-300">—</span>
                                            @endif
                                        </div>

                                        <div class="self-start">
                                            <label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-500">Purple check</label>
                                            <button type="button"
                                                data-inspection-open
                                                data-action="{{ route('purchasing.items.inspection.update', $item->item_id) }}"
                                                data-item-name="{{ e($item->item_name) }}"
                                                data-is-purple="{{ $isPurple ? '1' : '0' }}"
                                                data-note="{{ e($item->inspection_note ?? '') }}"
                                                class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl border px-3 text-xs font-black transition {{ $isPurple ? 'border-purple-300 bg-purple-100 text-purple-800 ring-2 ring-purple-100' : 'border-slate-200 bg-white text-slate-600 hover:border-purple-200 hover:bg-purple-50 hover:text-purple-700' }}">
                                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-md {{ $isPurple ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-400' }}">{{ $isPurple ? '✓' : '+' }}</span>
                                                <span>{{ $isPurple ? 'Purple check' : 'Add check' }}</span>
                                            </button>
                                            @if ($isPurple && ! empty($item->inspection_note))
                                                <p class="mt-1 truncate text-[10px] font-bold text-purple-500" title="{{ $item->inspection_note }}">{{ $item->inspection_note }}</p>
                                            @endif
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-500">Link</label>
                                            @if ($item->product_url)
                                                <a href="{{ $item->product_url }}" target="_blank" class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-sm font-black text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-100" title="Open product link">↗</a>
                                            @else
                                                <span class="text-sm font-bold text-slate-300">—</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @if ($remainingItems->isNotEmpty())
                            <form id="{{ $purchaseFormId }}" method="POST" action="{{ route('purchasing.purchases.bulk') }}" class="sticky bottom-4 z-20 mt-5 overflow-hidden rounded-[1.5rem] border-2 border-indigo-300 bg-indigo-50/95 shadow-2xl shadow-indigo-950/20 backdrop-blur" data-purchase-form>
                                @csrf
                                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-indigo-200 bg-indigo-600 px-4 py-3 text-white">
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-indigo-100">Record purchase</p>
                                        <p class="text-sm font-black">Select item lines above, then save this retailer basket.</p>
                                    </div>
                                    <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-black ring-1 ring-white/20"><span data-selected-lines-footer>0</span> selected</span>
                                </div>
                                <div class="p-4">
                                    <div class="grid gap-3 xl:grid-cols-[minmax(300px,360px)_minmax(260px,1fr)_180px_160px_auto] xl:items-start">
                                        <div>
                                            <label class="text-[10px] font-black uppercase tracking-wide text-indigo-700">Purchased from / supplier</label>
                                            <div class="mt-1 grid grid-cols-[minmax(0,1fr)_auto] gap-2">
                                                <select name="purchased_retailer_id" class="h-11 w-full min-w-0 rounded-2xl border-2 border-indigo-300 bg-white px-3 text-sm font-black text-slate-900 focus:border-indigo-500 focus:ring-indigo-200">
                                                    @foreach (($allRetailers ?? collect()) as $availableRetailer)
                                                        <option value="{{ $availableRetailer->id }}" {{ (int) $availableRetailer->id === (int) ($retailer['retailer_id'] ?? 0) ? 'selected' : '' }}>{{ $availableRetailer->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="button" data-retailer-modal-open class="inline-flex h-11 shrink-0 items-center justify-center rounded-2xl bg-white px-4 text-xs font-black text-indigo-700 ring-2 ring-indigo-200 hover:bg-indigo-100">+ Add</button>
                                            </div>
                                            <p class="mt-1 min-h-[2rem] text-[11px] font-bold leading-4 text-indigo-900/70">Defaults to this retailer. Add a missing supplier without leaving the page.</p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-black uppercase tracking-wide text-indigo-700">Retailer order reference *</label>
                                            <input name="retailer_order_reference" maxlength="255" required placeholder="e.g. 123-1234567-1234567" class="mt-1 h-11 w-full rounded-2xl border-2 border-indigo-300 bg-white px-4 text-sm font-black text-slate-900 placeholder:text-indigo-300 focus:border-indigo-500 focus:ring-indigo-200">
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-black uppercase tracking-wide text-indigo-700">ETA / expected UK hub *</label>
                                            <input name="expected_uk_hub_at" type="date" required class="mt-1 h-11 w-full rounded-2xl border-2 border-indigo-300 bg-white px-4 text-sm font-black text-slate-900 focus:border-indigo-500 focus:ring-indigo-200">
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-black uppercase tracking-wide text-indigo-700">Ordered date</label>
                                            <input name="ordered_at" type="date" value="{{ now()->format('Y-m-d') }}" class="mt-1 h-11 w-full rounded-2xl border-2 border-indigo-200 bg-white px-4 text-sm font-black text-slate-900 focus:border-indigo-300 focus:ring-indigo-200">
                                        </div>
                                        <div class="self-start">
                                            <label class="block text-[10px] font-black uppercase tracking-wide opacity-0 select-none">Action</label>
                                            <button class="mt-1 h-11 w-full rounded-2xl bg-indigo-700 px-6 text-sm font-black text-white shadow-sm hover:bg-indigo-800 xl:min-w-[170px]">Record Purchase</button>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <textarea name="note" rows="1" maxlength="2000" placeholder="Optional internal note for this purchase batch" class="w-full rounded-2xl border-2 border-indigo-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-indigo-300 focus:ring-indigo-200"></textarea>
                                    </div>
                                </div>
                            </form>
                        @else
                            <div class="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-sm font-black text-emerald-800">Nothing left to buy for this retailer. Purchased items stay visible here for context.</div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                    <p class="text-lg font-black text-slate-900">No purchasable items found for this order.</p>
                    <p class="mt-2 text-sm font-semibold text-slate-500">This may be completed, cancelled, superseded, or customer self-purchase.</p>
                </div>
            @endforelse
        </section>
        @endif
    </div>


    <div id="issue-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm" aria-hidden="true">
        <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-600">Purchase issue</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Record purchase problem</h2>
                    <p id="issue-modal-item" class="mt-1 text-sm font-semibold text-slate-500"></p>
                </div>
                <button type="button" data-issue-close class="rounded-2xl bg-slate-50 px-3 py-2 text-sm font-black text-slate-500 ring-1 ring-slate-200 hover:bg-slate-100">×</button>
            </div>

            <form method="POST" action="{{ route('purchasing.problems.store') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                @csrf
                <input type="hidden" name="order_item_id" id="issue-order-item-id">
                <label class="text-xs font-bold text-slate-600">What happened?
                    <select name="issue_type" id="issue-type" required class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white text-sm font-bold focus:border-amber-300 focus:ring-amber-200">
                        <optgroup label="Before purchase">
                            <option value="out_of_stock">Out of stock</option>
                            <option value="price_increase">Price increased</option>
                            <option value="retailer_restriction">Retailer restriction</option>
                            <option value="retailer_cancelled">Retailer cancelled before purchase</option>
                            <option value="wrong_product_link">Wrong product link</option>
                            <option value="awaiting_customer_decision">Need customer decision</option>
                            <option value="supplier_delay">Supplier delay</option>
                        </optgroup>
                        <optgroup label="After purchase">
                            <option value="supplier_cancelled_after_purchase">Supplier cancelled after purchase</option>
                            <option value="lost_in_transit">Lost in transit</option>
                            <option value="damaged_after_purchase">Damaged after purchase</option>
                            <option value="wrong_item_received">Wrong item received</option>
                            <option value="missing_from_parcel">Missing from parcel</option>
                            <option value="supplier_refunded_dabba">Supplier refunded Dabba</option>
                            <option value="replacement_expected">Supplier sending replacement</option>
                        </optgroup>
                        <option value="other">Other</option>
                    </select>
                </label>
                <label class="text-xs font-bold text-slate-600">Severity
                    <select name="severity" required class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white text-sm font-bold focus:border-amber-300 focus:ring-amber-200">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </label>
                <input type="hidden" name="issue_stage" id="issue-stage" value="pre_purchase">
                <input type="hidden" name="arrival_expectation" id="issue-arrival-expectation" value="expected">
                <label class="text-xs font-bold text-slate-600">Operational action
                    <select name="next_action" id="issue-next-action" class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white text-sm font-bold focus:border-amber-300 focus:ring-amber-200">
                        <option value="keep_in_purchase_issues">Keep in purchase issues</option>
                        <option value="remove_from_arrivals">Remove from arrivals queue</option>
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
                    <p class="mt-1 text-xs font-semibold leading-5 text-sky-900">Optional. These only flag the item for Finance; they do not issue refunds, wallet credits, invoices or ledger changes.</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        <label class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-bold text-slate-700 ring-1 ring-sky-100"><input type="checkbox" name="finance_actions[]" value="customer_refund_required" class="rounded border-slate-300 text-sky-600"> Customer refund required</label>
                        <label class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-bold text-slate-700 ring-1 ring-sky-100"><input type="checkbox" name="finance_actions[]" value="wallet_credit_required" class="rounded border-slate-300 text-sky-600"> Wallet credit required</label>
                        <label class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-bold text-slate-700 ring-1 ring-sky-100"><input type="checkbox" name="finance_actions[]" value="supplier_refund_pending" class="rounded border-slate-300 text-sky-600"> Supplier refund pending</label>
                        <label class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-bold text-slate-700 ring-1 ring-sky-100"><input type="checkbox" name="finance_actions[]" value="supplier_refunded" class="rounded border-slate-300 text-sky-600"> Supplier refunded Dabba</label>
                        <label class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-bold text-slate-700 ring-1 ring-sky-100"><input type="checkbox" name="finance_actions[]" value="manual_finance_review" class="rounded border-slate-300 text-sky-600"> Manual finance review</label>
                    </div>
                </div>

                <label class="text-xs font-bold text-slate-600">Quantity affected
                    <input id="issue-qty" name="qty" type="number" min="1" value="1" required class="mt-1 h-11 w-full rounded-2xl border-slate-200 bg-white text-sm font-bold focus:border-amber-300 focus:ring-amber-200">
                </label>
                <label class="flex items-center gap-2 rounded-2xl border border-amber-100 bg-amber-50/70 px-4 py-3 text-xs font-bold text-amber-800">
                    <input type="checkbox" name="requires_customer_action" value="1" class="rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                    Customer action required
                </label>
                <label class="sm:col-span-2 text-xs font-bold text-slate-600">Notes
                    <textarea name="notes" rows="4" maxlength="4000" placeholder="Add details for the team. Example: Amazon cancelled, remove from arrivals queue and ask finance to credit/refund customer." class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 text-sm font-bold text-slate-900 focus:border-amber-300 focus:bg-white focus:ring-amber-200"></textarea>
                </label>
                <div class="sm:col-span-2 rounded-2xl bg-slate-50 p-3 text-xs font-semibold leading-5 text-slate-600 ring-1 ring-slate-100">
                    This is operational only. Operational action controls purchasing/arrivals. Finance follow-up flags are separate and do not create refunds, credits, invoice adjustments, wallet entries or ledger transactions.
                </div>
                <div class="sm:col-span-2 flex justify-end gap-2">
                    <button type="button" data-issue-close class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button class="rounded-2xl bg-amber-600 px-5 py-2 text-sm font-black text-white hover:bg-amber-700">Record issue</button>
                </div>
            </form>
        </div>
    </div>


    <div id="inspection-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm" aria-hidden="true">
        <div class="w-full max-w-lg overflow-hidden rounded-[1.5rem] bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 bg-purple-700 px-5 py-4 text-white">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-purple-100">Purple check</p>
                    <h3 class="mt-1 text-lg font-black">Package inspection / marking note</h3>
                    <p id="inspection-modal-item" class="mt-1 text-xs font-bold text-purple-100"></p>
                </div>
                <button type="button" data-inspection-close class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-xl font-black hover:bg-white/20" aria-label="Close purple check form">×</button>
            </div>
            <form id="inspection-modal-form" method="POST" action="#" class="space-y-4 p-5">
                @csrf
                <label class="flex items-center gap-3 rounded-2xl border border-purple-100 bg-purple-50 px-4 py-3 text-sm font-black text-purple-800">
                    <input type="checkbox" name="requires_inspection" value="1" id="inspection-modal-toggle" class="rounded border-purple-300 text-purple-600 focus:ring-purple-500">
                    Purple check required
                </label>
                <div>
                    <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Reason / note</label>
                    <textarea name="inspection_note" id="inspection-modal-note" rows="4" maxlength="2000" placeholder="Optional reason for the package check" class="mt-1 w-full rounded-2xl border-2 border-purple-100 bg-purple-50/50 px-4 py-3 text-sm font-bold text-purple-950 placeholder:text-purple-300 focus:border-purple-400 focus:bg-white focus:ring-purple-200"></textarea>
                    <p class="mt-1 text-xs font-semibold text-slate-500">This is operational only. It highlights the item for package checking/marking.</p>
                </div>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                    <button type="button" id="inspection-clear" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-600 hover:bg-slate-50">Clear check</button>
                    <div class="flex gap-2 sm:justify-end">
                        <button type="button" data-inspection-close class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-600 hover:bg-slate-50">Cancel</button>
                        <button class="rounded-2xl bg-purple-700 px-5 py-2 text-sm font-black text-white shadow-sm hover:bg-purple-800">Save purple check</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="purchase-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm" aria-hidden="true">
        <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
            <p id="purchase-modal-title" class="text-lg font-black text-slate-950">Check this purchase</p>
            <p id="purchase-modal-message" class="mt-2 text-sm font-semibold leading-6 text-slate-600"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" id="purchase-modal-cancel" class="hidden rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="button" id="purchase-modal-ok" class="rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-black text-white hover:bg-indigo-700">OK</button>
            </div>
        </div>
    </div>


        <div id="quick-retailer-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4" aria-hidden="true">
            <div class="w-full max-w-lg overflow-hidden rounded-[1.5rem] bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 bg-indigo-600 px-5 py-4 text-white">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-indigo-100">Add retailer</p>
                        <h3 class="mt-1 text-lg font-black">Add a missing supplier</h3>
                    </div>
                    <button type="button" data-retailer-modal-close class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-xl font-black hover:bg-white/20" aria-label="Close retailer form">×</button>
                </div>
                <form method="POST" action="{{ route('purchasing.retailers.quick-store') }}" class="space-y-4 p-5">
                    @csrf
                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Retailer name *</label>
                        <input name="name" required minlength="2" maxlength="191" placeholder="e.g. Amazon, Argos, John Lewis" class="mt-1 h-11 w-full rounded-2xl border-2 border-slate-200 px-4 text-sm font-black text-slate-900 focus:border-indigo-400 focus:ring-indigo-200">
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">Base website *</label>
                        <input name="base_url" required minlength="3" maxlength="191" placeholder="amazon.co.uk" class="mt-1 h-11 w-full rounded-2xl border-2 border-slate-200 px-4 text-sm font-black text-slate-900 focus:border-indigo-400 focus:ring-indigo-200">
                        <p class="mt-1 text-xs font-semibold text-slate-500">Use the main site address. DabbaDesk will add https:// if needed.</p>
                    </div>
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" data-retailer-modal-close class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-600 hover:bg-slate-50">Cancel</button>
                        <button class="rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-indigo-700">Add retailer</button>
                    </div>
                </form>
            </div>
        </div>

    <script>

        document.querySelectorAll('[data-retailer-modal-open]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = document.getElementById('quick-retailer-modal');
                if (! modal) return;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.setAttribute('aria-hidden', 'false');
                const firstInput = modal.querySelector('input[name="name"]');
                if (firstInput) firstInput.focus();
            });
        });

        document.querySelectorAll('[data-retailer-modal-close]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = document.getElementById('quick-retailer-modal');
                if (! modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.setAttribute('aria-hidden', 'true');
            });
        });

        const quickRetailerModal = document.getElementById('quick-retailer-modal');
        if (quickRetailerModal) {
            quickRetailerModal.addEventListener('click', (event) => {
                if (event.target === quickRetailerModal) {
                    quickRetailerModal.classList.add('hidden');
                    quickRetailerModal.classList.remove('flex');
                    quickRetailerModal.setAttribute('aria-hidden', 'true');
                }
            });
        }


        const issueModal = document.getElementById('issue-modal');
        const issueOrderItemId = document.getElementById('issue-order-item-id');
        const issueQty = document.getElementById('issue-qty');
        const issueModalItem = document.getElementById('issue-modal-item');
        const issueStage = document.getElementById('issue-stage');
        const issueArrivalExpectation = document.getElementById('issue-arrival-expectation');
        const issueType = document.getElementById('issue-type');
        const issueNextAction = document.getElementById('issue-next-action');

        const setIssueModalForStage = (stage) => {
            const isPostPurchase = stage === 'post_purchase' || stage === 'arrival';

            if (issueType) {
                issueType.value = isPostPurchase ? 'supplier_cancelled_after_purchase' : 'out_of_stock';
            }

            if (issueNextAction) {
                issueNextAction.value = isPostPurchase ? 'remove_from_arrivals' : 'keep_in_purchase_issues';
            }

            if (issueArrivalExpectation) {
                issueArrivalExpectation.value = isPostPurchase ? 'not_expected' : 'expected';
            }
        };

        const applyNextActionToHiddenFields = () => {
            const action = issueNextAction?.value || 'keep_in_purchase_issues';
            const stage = issueStage?.value || 'pre_purchase';

            if (!issueArrivalExpectation) {
                return;
            }

            if (stage === 'pre_purchase') {
                issueArrivalExpectation.value = 'expected';
                return;
            }

            if (action === 'replacement_expected') {
                issueArrivalExpectation.value = 'replacement_expected';
                return;
            }

            if (['remove_from_arrivals', 'return_to_buy', 'write_off'].includes(action)) {
                issueArrivalExpectation.value = 'not_expected';
                return;
            }

            issueArrivalExpectation.value = 'expected';
        };

        issueNextAction?.addEventListener('change', applyNextActionToHiddenFields);

        document.querySelectorAll('[data-issue-open]').forEach((button) => {
            button.addEventListener('click', () => {
                const remaining = Math.max(1, parseInt(button.dataset.remaining || '1', 10));
                const stage = button.dataset.issueStage || 'pre_purchase';
                if (issueOrderItemId) issueOrderItemId.value = button.dataset.orderItemId || '';
                if (issueQty) {
                    issueQty.value = remaining;
                    issueQty.max = remaining;
                }
                if (issueModalItem) issueModalItem.textContent = `${button.dataset.itemName || 'Item'} · ${remaining} available`;
                if (issueStage) issueStage.value = stage;
                setIssueModalForStage(stage);
                if (issueArrivalExpectation && button.dataset.arrivalExpectation) issueArrivalExpectation.value = button.dataset.arrivalExpectation;
                applyNextActionToHiddenFields();
                issueModal?.classList.remove('hidden');
                issueModal?.classList.add('flex');
                issueModal?.setAttribute('aria-hidden', 'false');
            });
        });

        document.querySelectorAll('[data-issue-close]').forEach((button) => {
            button.addEventListener('click', () => {
                issueModal?.classList.add('hidden');
                issueModal?.classList.remove('flex');
                issueModal?.setAttribute('aria-hidden', 'true');
            });
        });

        issueModal?.addEventListener('click', (event) => {
            if (event.target === issueModal) {
                issueModal.classList.add('hidden');
                issueModal.classList.remove('flex');
                issueModal.setAttribute('aria-hidden', 'true');
            }
        });

        const purchaseModal = document.getElementById('purchase-modal');
        const purchaseModalTitle = document.getElementById('purchase-modal-title');
        const purchaseModalMessage = document.getElementById('purchase-modal-message');
        const purchaseModalOk = document.getElementById('purchase-modal-ok');
        const purchaseModalCancel = document.getElementById('purchase-modal-cancel');
        let purchaseModalConfirmCallback = null;

        const showPurchaseModal = ({ title, message, confirm = false, onConfirm = null }) => {
            purchaseModalTitle.textContent = title || 'Check this purchase';
            purchaseModalMessage.textContent = message || '';
            purchaseModalConfirmCallback = onConfirm;
            purchaseModalCancel.classList.toggle('hidden', !confirm);
            purchaseModalOk.textContent = confirm ? 'Confirm' : 'OK';
            purchaseModal.classList.remove('hidden');
            purchaseModal.classList.add('flex');
            purchaseModal.setAttribute('aria-hidden', 'false');
            purchaseModalOk.focus();
        };

        const closePurchaseModal = () => {
            purchaseModal.classList.add('hidden');
            purchaseModal.classList.remove('flex');
            purchaseModal.setAttribute('aria-hidden', 'true');
            purchaseModalConfirmCallback = null;
        };

        purchaseModalCancel?.addEventListener('click', closePurchaseModal);
        purchaseModal?.addEventListener('click', (event) => {
            if (event.target === purchaseModal) closePurchaseModal();
        });
        purchaseModalOk?.addEventListener('click', () => {
            const callback = purchaseModalConfirmCallback;
            closePurchaseModal();
            if (callback) callback();
        });

        document.querySelectorAll('[data-retailer-card]').forEach((card) => {
            const checkboxes = Array.from(card.querySelectorAll('[data-line-checkbox]'));
            const selected = card.querySelector('[data-selected-lines]');
            const selectedFooter = card.querySelector('[data-selected-lines-footer]');
            const form = card.querySelector('[data-purchase-form]');
            const update = () => {
                const count = checkboxes.filter((checkbox) => checkbox.checked).length;
                if (selected) selected.textContent = count;
                if (selectedFooter) selectedFooter.textContent = count;
            };

            card.querySelector('[data-select-all]')?.addEventListener('click', () => {
                checkboxes.forEach((checkbox) => checkbox.checked = true);
                update();
            });

            card.querySelector('[data-select-none]')?.addEventListener('click', () => {
                checkboxes.forEach((checkbox) => checkbox.checked = false);
                update();
            });

            checkboxes.forEach((checkbox) => checkbox.addEventListener('change', update));

            form?.addEventListener('submit', (event) => {
                const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
                const reference = form.querySelector('[name="retailer_order_reference"]');
                const eta = form.querySelector('[name="expected_uk_hub_at"]');

                if (selectedCount === 0) {
                    event.preventDefault();
                    showPurchaseModal({
                        title: 'No items selected',
                        message: 'Please select at least one item before recording a purchase.'
                    });
                    return;
                }

                if (! reference?.value.trim()) {
                    event.preventDefault();
                    reference?.focus();
                    showPurchaseModal({
                        title: 'Retailer reference required',
                        message: 'Enter the retailer order reference before saving this purchase.'
                    });
                    return;
                }

                if (! eta?.value) {
                    event.preventDefault();
                    eta?.focus();
                    showPurchaseModal({
                        title: 'ETA required',
                        message: 'Enter the ETA / expected UK hub date before saving this purchase.'
                    });
                    return;
                }
            });

            update();
        });


        const inspectionModal = document.getElementById('inspection-modal');
        const inspectionForm = document.getElementById('inspection-modal-form');
        const inspectionToggle = document.getElementById('inspection-modal-toggle');
        const inspectionNote = document.getElementById('inspection-modal-note');
        const inspectionItem = document.getElementById('inspection-modal-item');
        const inspectionClear = document.getElementById('inspection-clear');

        const openInspectionModal = (button) => {
            if (! inspectionModal || ! inspectionForm) return;
            inspectionForm.action = button.dataset.action || '#';
            if (inspectionItem) inspectionItem.textContent = button.dataset.itemName || '';
            if (inspectionToggle) inspectionToggle.checked = button.dataset.isPurple === '1';
            if (inspectionNote) inspectionNote.value = button.dataset.note || '';
            inspectionModal.classList.remove('hidden');
            inspectionModal.classList.add('flex');
            inspectionModal.setAttribute('aria-hidden', 'false');
            setTimeout(() => inspectionToggle?.focus(), 50);
        };

        const closeInspectionModal = () => {
            if (! inspectionModal) return;
            inspectionModal.classList.add('hidden');
            inspectionModal.classList.remove('flex');
            inspectionModal.setAttribute('aria-hidden', 'true');
        };

        document.querySelectorAll('[data-inspection-open]').forEach((button) => {
            button.addEventListener('click', () => openInspectionModal(button));
        });

        document.querySelectorAll('[data-inspection-close]').forEach((button) => {
            button.addEventListener('click', closeInspectionModal);
        });

        inspectionModal?.addEventListener('click', (event) => {
            if (event.target === inspectionModal) closeInspectionModal();
        });

        inspectionClear?.addEventListener('click', () => {
            if (inspectionToggle) inspectionToggle.checked = false;
            if (inspectionNote) inspectionNote.value = '';
            inspectionForm?.requestSubmit();
        });

        document.querySelectorAll('form[data-confirm-undo]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                showPurchaseModal({
                    title: 'Undo this purchase?',
                    message: 'This will return the quantity to Awaiting Purchase. This is only allowed when the purchase has not arrived.',
                    confirm: true,
                    onConfirm: () => form.submit()
                });
            });
        });
    </script>
</x-app-layout>
