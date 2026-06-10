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

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="min-w-0">
                    <a href="{{ route('orders.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">← Back to Orders</a>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight text-slate-950">Order #{{ $order->order_number }}</h1>
                        <button type="button" data-copy-value="{{ $copyOrderNumber }}" class="copy-btn rounded-full border border-indigo-200 bg-white px-3 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Copy</button>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $revisionBadgeClasses }}">{{ $revisionBadgeLabel }}</span>
                        <span class="rounded-full {{ $isCustomerSelfPurchase ? 'bg-sky-100 text-sky-700' : 'bg-indigo-100 text-indigo-700' }} px-2.5 py-1 text-xs font-semibold">
                            {{ $isCustomerSelfPurchase ? 'Self-purchase' : 'Dabba purchase' }}
                        </span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $paymentStatusClasses }}">{{ $paymentStatusLabel }}</span>
                        @if ($walletAttentionTotal > 0.004)
                            <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-200">Wallet credit £{{ number_format($walletAttentionTotal, 2) }}</span>
                        @endif
                        @if ($balanceDue > 0.004)
                            <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-200">Outstanding £{{ number_format($balanceDue, 2) }}</span>
                        @else
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">No outstanding balance</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-slate-500">{{ $customerFullName ?: 'Unknown customer' }} · Total £{{ number_format($orderTotal, 2) }} · Settled £{{ number_format($settledTotal, 2) }}</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="$dispatch('open-payment-modal')" class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">Record payment</button>
                    <button type="button" @click="$dispatch('open-invoice-modal')" class="rounded-2xl {{ $hasInvoiceWorkspace ? 'bg-slate-900 hover:bg-slate-800' : 'bg-amber-600 hover:bg-amber-700' }} px-4 py-2 text-sm font-semibold text-white shadow-sm">{{ $hasInvoiceWorkspace ? 'New invoice version' : 'Create invoice' }}</button>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 rounded-2xl bg-slate-50 p-2 ring-1 ring-slate-100">
                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100 hover:bg-emerald-100">WhatsApp ↗</a>
                @endif
                @if ($customerEmail)
                    <a href="mailto:{{ $customerEmail }}" class="rounded-xl bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-100">Email ↗</a>
                @endif
                @if ($copyFullAddress)
                    <button type="button" data-copy-value="{{ $copyFullAddress }}" class="copy-btn rounded-xl border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Copy address</button>
                @endif
                <button type="button" data-copy-value="{{ $copyOrderNumber }}" class="copy-btn rounded-xl border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Copy order #</button>
                @if (! empty($order->customer_id))
                    <a href="{{ route('customers.edit', $order->customer_id) }}" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-indigo-100 hover:text-indigo-700">Customer ↗</a>
                @endif
                <a href="{{ route('money-desk.orders.show', $order->id) }}" class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100 hover:bg-emerald-100">Finance ↗</a>
            </div>

            <div class="mt-5 overflow-x-auto">
                <div class="inline-flex min-w-full gap-2 rounded-2xl bg-slate-100 p-1">
                    <button type="button" @click="tab = 'overview'" :class="tab === 'overview' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'" class="rounded-xl px-4 py-2 text-sm font-semibold">Overview</button>
                    <button type="button" @click="tab = 'items'" :class="tab === 'items' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'" class="rounded-xl px-4 py-2 text-sm font-semibold">Items</button>
                    <button type="button" @click="tab = 'purchase_status'" :class="tab === 'purchase_status' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'" class="rounded-xl px-4 py-2 text-sm font-semibold">Purchase Status</button>
                    <button type="button" @click="tab = 'finance'" :class="tab === 'finance' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'" class="rounded-xl px-4 py-2 text-sm font-semibold">Finance</button>
                    <button type="button" @click="tab = 'notes'" :class="tab === 'notes' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-800'" class="rounded-xl px-4 py-2 text-sm font-semibold">Communication & History</button>
                </div>
            </div>
        </section>
