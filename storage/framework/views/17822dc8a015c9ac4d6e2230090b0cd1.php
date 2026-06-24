<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

     <?php $__env->slot('header', null, []); ?> 
        Money Desk
     <?php $__env->endSlot(); ?>

    <div
        class="space-y-6"
        x-data="{
            tab: 'ledger',

            customerQuery: '',
            customerFilter: 'all',
            customerResults: [],
            customerLoading: false,
            customerError: '',
            customerTimer: null,
            customerSearchUrl: '<?php echo e(route('money-desk.customer-search')); ?>',

            orderQuery: '',
            orderFilter: 'order_number',
            orderResults: [],
            orderLoading: false,
            orderError: '',
            orderTimer: null,
            orderSearchUrl: '<?php echo e(route('money-desk.order-search')); ?>',

            searchCustomers() {
                clearTimeout(this.customerTimer);

                this.customerTimer = setTimeout(() => {
                    const query = this.customerQuery.trim();

                    if (!query || (this.customerFilter !== 'id' && query.length < 2)) {
                        this.customerResults = [];
                        this.customerLoading = false;
                        this.customerError = '';
                        return;
                    }

                    this.customerLoading = true;
                    this.customerError = '';

                    fetch(`${this.customerSearchUrl}?q=${encodeURIComponent(query)}&filter=${encodeURIComponent(this.customerFilter)}`, {
                        headers: { 'Accept': 'application/json' }
                    })
                        .then(response => {
                            if (!response.ok) throw new Error('Search failed');
                            return response.json();
                        })
                        .then(data => {
                            this.customerResults = data.results || [];
                        })
                        .catch(() => {
                            this.customerResults = [];
                            this.customerError = 'Could not run customer search.';
                        })
                        .finally(() => {
                            this.customerLoading = false;
                        });
                }, 300);
            },

            searchOrders() {
                clearTimeout(this.orderTimer);

                this.orderTimer = setTimeout(() => {
                    const query = this.orderQuery.trim();

                    if (!query || (this.orderFilter !== 'order_number' && query.length < 2)) {
                        this.orderResults = [];
                        this.orderLoading = false;
                        this.orderError = '';
                        return;
                    }

                    this.orderLoading = true;
                    this.orderError = '';

                    fetch(`${this.orderSearchUrl}?q=${encodeURIComponent(query)}&filter=${encodeURIComponent(this.orderFilter)}`, {
                        headers: { 'Accept': 'application/json' }
                    })
                        .then(response => {
                            if (!response.ok) throw new Error('Search failed');
                            return response.json();
                        })
                        .then(data => {
                            this.orderResults = data.results || [];
                        })
                        .catch(() => {
                            this.orderResults = [];
                            this.orderError = 'Could not run order search.';
                        })
                        .finally(() => {
                            this.orderLoading = false;
                        });
                }, 300);
            },

            money(value) {
                const number = Number(value || 0);
                return '£' + number.toLocaleString('en-GB', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        }"
    >

        <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Money Desk</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Read-only financial control centre for payments, wallet balances, refunds and order settlement.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a
                        href="<?php echo e(route('money-desk.anomalies')); ?>"
                        class="inline-flex w-fit rounded-2xl bg-rose-600 px-4 py-3 text-sm font-semibold text-white hover:bg-rose-700"
                    >
                        Financial checks
                    </a>

                    <span class="inline-flex w-fit rounded-full bg-amber-100 px-4 py-3 text-sm font-semibold text-amber-700">
                        Read-only preview
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">Payments Received</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">
                    £<?php echo e(number_format($stats['payments_received'] ?? 0, 2)); ?>

                </p>
                <p class="mt-2 text-xs text-slate-400">Real money ledger</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">Order Settled</p>
                <p class="mt-3 text-3xl font-bold text-emerald-600">
                    £<?php echo e(number_format($stats['orders_settled'] ?? 0, 2)); ?>

                </p>
                <p class="mt-2 text-xs text-slate-400">Order transactions</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">Wallet Balance</p>
                <p class="mt-3 text-3xl font-bold text-indigo-600">
                    £<?php echo e(number_format($stats['wallet_balance'] ?? 0, 2)); ?>

                </p>
                <p class="mt-2 text-xs text-slate-400">Reusable customer credit</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">Refunds</p>
                <p class="mt-3 text-3xl font-bold text-rose-600">
                    £<?php echo e(number_format($stats['refunds'] ?? 0, 2)); ?>

                </p>
                <p class="mt-2 text-xs text-slate-400">External and wallet refunds</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">

            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-900">
                    Customer finance lookup
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Find a customer and open their plain-English finance history.
                </p>

                <div class="mt-5 flex flex-col gap-3 lg:flex-row lg:items-end">
                    <div class="flex-1">
                        <label for="customer_search" class="text-sm font-semibold text-slate-700">
                            Customer search
                        </label>

                        <input
                            id="customer_search"
                            x-model="customerQuery"
                            @input="searchCustomers()"
                            type="text"
                            placeholder="Name, email, phone or ID"
                            class="mt-2 w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div class="w-full lg:w-44">
                        <label for="customer_filter" class="text-sm font-semibold text-slate-700">
                            Filter
                        </label>

                        <select
                            id="customer_filter"
                            x-model="customerFilter"
                            @change="searchCustomers()"
                            class="mt-2 w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="all">All fields</option>
                            <option value="name">Name / company</option>
                            <option value="email">Email</option>
                            <option value="phone">Phone</option>
                            <option value="id">Customer ID</option>
                        </select>
                    </div>
                </div>

                <div class="mt-5">
                    <template x-if="customerLoading">
                        <div class="rounded-2xl bg-indigo-50 px-4 py-3 text-sm font-semibold text-indigo-700">
                            Searching customers…
                        </div>
                    </template>

                    <template x-if="customerError">
                        <div class="rounded-2xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700" x-text="customerError"></div>
                    </template>

                    <template x-if="!customerLoading && customerQuery.trim() && customerResults.length === 0 && !customerError">
                        <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-500">
                            No matching customers found.
                        </div>
                    </template>

                    <template x-if="customerResults.length > 0">
                        <div class="space-y-3">
                            <template x-for="customer in customerResults" :key="customer.id">
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="font-semibold text-slate-900" x-text="`${customer.first_name || ''} ${customer.last_name || ''}`.trim() || 'Unnamed customer'"></p>
                                            <p class="mt-1 text-xs text-slate-400">
                                                ID: <span x-text="customer.id"></span>
                                                <template x-if="customer.company_name">
                                                    <span> · <span x-text="customer.company_name"></span></span>
                                                </template>
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500" x-text="customer.primary_email || customer.primary_phone || 'No primary contact shown'"></p>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <div class="text-right">
                                                <p class="text-xs text-slate-400">Wallet</p>
                                                <p class="font-bold text-indigo-600" x-text="money(customer.wallet_balance)"></p>
                                            </div>

                                            <a
                                                :href="`/money-desk/customers/${customer.id}`"
                                                class="rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                                            >
                                                View finance
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-900">
                    Order finance lookup
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Find an order and see what paid it, what wallet balance was used, and what is still due.
                </p>

                <div class="mt-5 flex flex-col gap-3 lg:flex-row lg:items-end">
                    <div class="flex-1">
                        <label for="order_search" class="text-sm font-semibold text-slate-700">
                            Order search
                        </label>

                        <input
                            id="order_search"
                            x-model="orderQuery"
                            @input="searchOrders()"
                            type="text"
                            placeholder="Order number, customer or email"
                            class="mt-2 w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                    </div>

                    <div class="w-full lg:w-44">
                        <label for="order_filter" class="text-sm font-semibold text-slate-700">
                            Filter
                        </label>

                        <select
                            id="order_filter"
                            x-model="orderFilter"
                            @change="searchOrders()"
                            class="mt-2 w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            <option value="order_number">Order number</option>
                            <option value="customer">Customer</option>
                            <option value="email">Email</option>
                            <option value="all">All fields</option>
                        </select>
                    </div>
                </div>

                <div class="mt-5">
                    <template x-if="orderLoading">
                        <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                            Searching orders…
                        </div>
                    </template>

                    <template x-if="orderError">
                        <div class="rounded-2xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700" x-text="orderError"></div>
                    </template>

                    <template x-if="!orderLoading && orderQuery.trim() && orderResults.length === 0 && !orderError">
                        <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-500">
                            No matching orders found.
                        </div>
                    </template>

                    <template x-if="orderResults.length > 0">
                        <div class="space-y-3">
                            <template x-for="order in orderResults" :key="order.id">
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="font-semibold text-slate-900">
                                                Order #<span x-text="order.order_number"></span>
                                            </p>
                                            <p class="mt-1 text-xs text-slate-400">
                                                <span x-text="order.bill_to_name || `${order.first_name || ''} ${order.last_name || ''}`.trim() || 'Unknown customer'"></span>
                                                · <span x-text="order.status"></span>
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500" x-text="order.bill_to_email || 'No email shown'"></p>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <div class="text-right">
                                                <p class="text-xs text-slate-400">Still due</p>
                                                <p class="font-bold" :class="Number(order.balance_due || 0) > 0 ? 'text-rose-600' : 'text-slate-400'" x-text="money(order.balance_due)"></p>
                                            </div>

                                            <a
                                                :href="`/money-desk/orders/${order.id}`"
                                                class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                                            >
                                                View order finance
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
            <div class="flex flex-wrap gap-3">
                <button
                    @click="tab = 'ledger'"
                    :class="tab === 'ledger' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    class="rounded-2xl px-4 py-2 text-sm font-semibold transition"
                >
                    Real Money Movement
                </button>

                <button
                    @click="tab = 'orders'"
                    :class="tab === 'orders' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    class="rounded-2xl px-4 py-2 text-sm font-semibold transition"
                >
                    Order Settlement
                </button>

                <button
                    @click="tab = 'wallet'"
                    :class="tab === 'wallet' ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    class="rounded-2xl px-4 py-2 text-sm font-semibold transition"
                >
                    Open Wallet Balances
                </button>
            </div>

            <div x-show="tab === 'ledger'" x-transition class="mt-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                                <th class="py-3 pr-4">Date</th>
                                <th class="py-3 pr-4">Customer</th>
                                <th class="py-3 pr-4">Type</th>
                                <th class="py-3 pr-4 text-right">Amount</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $recentLedgerEntries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td class="py-3 pr-4 text-slate-500">
                                        <?php echo e($entry->occurred_at ? \Carbon\Carbon::parse($entry->occurred_at)->format('d M Y') : '—'); ?>

                                    </td>

                                    <td class="py-3 pr-4 font-medium text-slate-700">
                                        <?php echo e(trim(($entry->first_name ?? '') . ' ' . ($entry->last_name ?? '')) ?: 'Unknown customer'); ?>

                                    </td>

                                    <td class="py-3 pr-4">
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                            <?php echo e(str_replace('_', ' ', $entry->type)); ?>

                                        </span>
                                    </td>

                                    <td class="py-3 pr-4 text-right font-semibold text-slate-900">
                                        <?php echo e($entry->currency ?? 'GBP'); ?> <?php echo e(number_format($entry->amount, 2)); ?>

                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-slate-400">
                                        No ledger entries found.
                                    </td>
                                </tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="tab === 'orders'" x-transition class="mt-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                                <th class="py-3 pr-4">Date</th>
                                <th class="py-3 pr-4">Order</th>
                                <th class="py-3 pr-4">Type</th>
                                <th class="py-3 pr-4 text-right">Amount</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $recentOrderTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td class="py-3 pr-4 text-slate-500">
                                        <?php echo e($transaction->created_at ? \Carbon\Carbon::parse($transaction->created_at)->format('d M Y') : '—'); ?>

                                    </td>

                                    <td class="py-3 pr-4 font-medium text-slate-700">
                                        #<?php echo e($transaction->order_number ?? $transaction->order_id); ?>

                                    </td>

                                    <td class="py-3 pr-4">
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                            <?php echo e(str_replace('_', ' ', $transaction->type)); ?>

                                        </span>
                                    </td>

                                    <td class="py-3 pr-4 text-right font-semibold text-slate-900">
                                        <?php echo e($transaction->currency ?? 'GBP'); ?> <?php echo e(number_format($transaction->amount, 2)); ?>

                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-slate-400">
                                        No order transactions found.
                                    </td>
                                </tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="tab === 'wallet'" x-transition class="mt-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                                <th class="py-3 pr-4">Created</th>
                                <th class="py-3 pr-4">Customer</th>
                                <th class="py-3 pr-4">Source</th>
                                <th class="py-3 pr-4 text-right">Original</th>
                                <th class="py-3 pr-4 text-right">Remaining</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $openWalletCredits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $credit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td class="py-3 pr-4 text-slate-500">
                                        <?php echo e($credit->created_at ? \Carbon\Carbon::parse($credit->created_at)->format('d M Y') : '—'); ?>

                                    </td>

                                    <td class="py-3 pr-4 font-medium text-slate-700">
                                        <?php echo e(trim(($credit->first_name ?? '') . ' ' . ($credit->last_name ?? '')) ?: 'Unknown customer'); ?>

                                    </td>

                                    <td class="py-3 pr-4">
                                        <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700">
                                            <?php echo e(str_replace('_', ' ', $credit->source_type)); ?>

                                        </span>
                                    </td>

                                    <td class="py-3 pr-4 text-right text-slate-600">
                                        <?php echo e($credit->currency ?? 'GBP'); ?> <?php echo e(number_format($credit->amount, 2)); ?>

                                    </td>

                                    <td class="py-3 pr-4 text-right font-bold text-purple-600">
                                        <?php echo e($credit->currency ?? 'GBP'); ?> <?php echo e(number_format($credit->remaining_amount, 2)); ?>

                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-slate-400">
                                        No open wallet credits found.
                                    </td>
                                </tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/money-desk/index.blade.php ENDPATH**/ ?>