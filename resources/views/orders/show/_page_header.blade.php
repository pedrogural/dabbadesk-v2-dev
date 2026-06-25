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

        @if ($isHistoricalRevision)
            <section class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-700">Viewing historical revision</p>
                        <h2 class="mt-1 text-lg font-black text-amber-950">Revision {{ $revisionNumber }} is superseded and read-only.</h2>
                        <p class="mt-1 text-sm font-semibold text-amber-900">Financial and operational actions are disabled for this snapshot. Open the active revision to record payments, refunds, invoices, purchases or other changes.</p>
                    </div>
                    <a href="{{ $activeRevisionUrl }}" class="inline-flex items-center justify-center rounded-2xl bg-amber-700 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-amber-800">View active revision</a>
                </div>
            </section>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-2">
                <a href="{{ route('orders.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">← Back to Orders</a>

                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-950">Order #{{ $order->order_number }}</h1>
                    <button type="button" data-copy-value="{{ $copyOrderNumber }}" title="Copy order number" aria-label="Copy order number" class="copy-btn inline-flex h-7 w-7 items-center justify-center rounded-full border border-indigo-200 bg-white text-sm text-indigo-700 hover:bg-indigo-50">📋</button>
                </div>

                <p class="text-lg font-semibold text-slate-800">{{ $customerFullName ?: 'Unknown customer' }}</p>

                <p class="text-sm font-semibold {{ $balanceDue > 0.004 ? 'text-rose-700' : 'text-emerald-700' }}">
                    {{ $paymentStatusLabel }}
                    <span class="text-slate-300">•</span>
                    @if ($balanceDue > 0.004)
                        Outstanding £{{ number_format($balanceDue, 2) }}
                    @else
                        No outstanding balance
                    @endif
                </p>
            </div>

            <div class="mt-5 overflow-x-auto">
                <div class="inline-flex min-w-full gap-2 rounded-3xl border border-indigo-100 bg-indigo-50/70 p-1.5 shadow-inner shadow-indigo-100/40">
                    <button type="button" @click="tab = 'overview'" :class="tab === 'overview' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-200 ring-1 ring-indigo-700/10' : 'bg-white text-slate-700 ring-1 ring-indigo-100 hover:bg-indigo-100 hover:text-indigo-800'" class="rounded-2xl px-4 py-2.5 text-sm font-black transition whitespace-nowrap">Overview</button>
                    <button type="button" @click="tab = 'items'" :class="tab === 'items' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-200 ring-1 ring-indigo-700/10' : 'bg-white text-slate-700 ring-1 ring-indigo-100 hover:bg-indigo-100 hover:text-indigo-800'" class="rounded-2xl px-4 py-2.5 text-sm font-black transition whitespace-nowrap">Items</button>
                    <button type="button" @click="tab = 'purchase_status'" :class="tab === 'purchase_status' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-200 ring-1 ring-indigo-700/10' : 'bg-white text-slate-700 ring-1 ring-indigo-100 hover:bg-indigo-100 hover:text-indigo-800'" class="rounded-2xl px-4 py-2.5 text-sm font-black transition whitespace-nowrap">Purchasing</button>
                    <button type="button" @click="tab = 'finance'" :class="tab === 'finance' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-200 ring-1 ring-indigo-700/10' : 'bg-white text-slate-700 ring-1 ring-indigo-100 hover:bg-indigo-100 hover:text-indigo-800'" class="rounded-2xl px-4 py-2.5 text-sm font-black transition whitespace-nowrap">Finance</button>
                    <button type="button" @click="tab = 'notes'" :class="tab === 'notes' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-200 ring-1 ring-indigo-700/10' : 'bg-white text-slate-700 ring-1 ring-indigo-100 hover:bg-indigo-100 hover:text-indigo-800'" class="rounded-2xl px-4 py-2.5 text-sm font-black transition whitespace-nowrap">History</button>
                </div>
            </div>
        </section>
