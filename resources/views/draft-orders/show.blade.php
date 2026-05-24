<x-app-layout>
    <x-slot name="header">Draft #{{ $draft->draft_number ?: $draft->id }}</x-slot>

    @php
        $customerName =
            trim(($draft->first_name ?? '') . ' ' . ($draft->last_name ?? '')) ?:
            ($draft->company_name ?:
            'Unknown customer');
        $qtyTotal = (int) $items->sum('qty');
        $activeTab = request('tab', 'products');
        $lastAddedItemId = (int) session('last_added_item_id', 0);
        $summaryByRetailer = $retailerSummaries->keyBy('retailer_id');
        $groupedItems = $items->groupBy(fn($item) => $item->retailer_id ?: 0);
        $retailerRows = $retailerSummaries->keyBy('retailer_id');
        $money = fn($value) => '£' . number_format((float) ($value ?? 0), 2);
        $draftNo = $draft->draft_number ?: $draft->id;
        $retailerLogoUrl = function ($logoPath) {
            $path = trim((string) ($logoPath ?? ''));
            if ($path === '') {
                return null;
            }

            $path = str_replace(['\\/', '\\'], ['/', '/'], $path);
            if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])) {
                return $path;
            }

            $path = ltrim($path, '/');
            $path = preg_replace('#^public/#', '', $path);
            $path = preg_replace('#^storage/app/public/#', '', $path);
            $path = preg_replace('#^app/public/#', '', $path);
            $path = preg_replace('#^storage/#', '', $path);

            if (!\Illuminate\Support\Str::startsWith($path, 'retailers/')) {
                $path = 'retailers/' . basename($path);
            }

            return asset('storage/' . $path);
        };
    @endphp

    <style>
        [x-cloak] {
            display: none !important
        }

        .draft-ui input:not([type="checkbox"]):not(.currency-input):not(.compact-input):not(.sku-input):not(.url-input):not(.bare-input),
        .draft-ui textarea:not(.description-input),
        .draft-ui select {
            border: 1px solid #cbd5e1 !important;
            border-radius: 14px !important;
            background: #fff !important;
            padding: 11px 14px !important;
            min-height: 44px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04) !important;
        }

        .draft-ui textarea:not(.description-input) {
            min-height: 76px;
            line-height: 1.5
        }

        .draft-ui input:focus,
        .draft-ui textarea:focus,
        .draft-ui select:focus {
            outline: 2px solid transparent !important;
            border-color: #9333ea !important;
            box-shadow: 0 0 0 3px rgba(147, 51, 234, .18) !important;
        }

        .draft-ui .field-label {
            display: block;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            margin-bottom: 7px
        }

        .draft-ui .input-clean {
            width: 100%;
            border: 1px solid #cbd5e1 !important;
            border-radius: 14px !important;
            background: #fff !important;
            padding: 11px 13px !important;
            min-height: 44px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04) !important
        }

        .draft-ui .row-action {
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            background: #fff;
            padding: 0 15px;
            min-height: 44px;
            font-size: 13px;
            font-weight: 900;
            color: #0f172a;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04)
        }

        .draft-ui .row-action:hover {
            background: #f8fafc
        }

        .draft-ui .retailer-card {
            background: #fff
        }

        .draft-ui .retailer-header {
            display: grid;
            grid-template-columns: minmax(230px, 1fr) 250px minmax(420px, 1.65fr);
            gap: 24px;
            align-items: center;
            padding: 24px 28px;
            border-bottom: 1px solid #e2e8f0
        }

        .draft-ui .retailer-identity {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0
        }

        .draft-ui .retailer-logo {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .draft-ui .retailer-initial {
            display: grid;
            place-items: center;
            height: 64px;
            width: 64px;
            border-radius: 22px;
            background: #0f172a;
            color: #fff;
            font-size: 24px;
            font-weight: 950;
            flex: 0 0 auto
        }

        .draft-ui .retailer-name {
            font-size: 25px;
            font-weight: 950;
            line-height: 1.1;
            color: #020617;
            letter-spacing: -.025em
        }

        .draft-ui .retailer-subline {
            margin-top: 8px;
            font-size: 14px;
            font-weight: 850;
            color: #64748b
        }

        .draft-ui .retailer-delivery-panel {
            border-left: 1px solid #e2e8f0;
            padding-left: 22px
        }

        .draft-ui .retailer-delivery-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 48px;
            gap: 10px;
            align-items: end
        }

        .draft-ui .money-label {
            display: block;
            font-size: 11px;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .09em;
            color: #64748b;
            margin-bottom: 7px
        }

        .draft-ui .money-tile-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(105px, 1fr));
            gap: 12px
        }

        .draft-ui .money-box {
            border-radius: 18px;
            background: #f8fafc;
            padding: 15px 16px;
            min-height: 74px;
            display: flex;
            flex-direction: column;
            justify-content: center
        }

        .draft-ui .money-box-purple {
            background: #faf5ff;
            color: #7e22ce
        }

        .draft-ui .money-value {
            font-size: 19px;
            font-weight: 950;
            color: #020617;
            white-space: nowrap
        }

        .draft-ui .money-box-purple .money-label,
        .draft-ui .money-box-purple .money-value {
            color: #7e22ce
        }

        .draft-ui .currency-input-wrap {
            display: flex;
            align-items: center;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            background: #fff;
            min-height: 48px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04)
        }

        .draft-ui .currency-input-wrap:focus-within {
            border-color: #9333ea;
            box-shadow: 0 0 0 3px rgba(147, 51, 234, .18)
        }

        .draft-ui .currency-prefix {
            height: 48px;
            display: flex;
            align-items: center;
            padding-left: 14px;
            padding-right: 10px;
            font-weight: 900;
            color: #64748b;
            background: #f8fafc;
            border-right: 1px solid #e2e8f0
        }

        .draft-ui .currency-input {
            appearance: textfield;
            border: 0 !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            min-height: 46px !important;
            padding: 0 12px !important;
            text-align: right;
            font-weight: 900;
            color: #0f172a;
            background: transparent !important;
            width: 100%
        }

        .draft-ui .currency-input::-webkit-outer-spin-button,
        .draft-ui .currency-input::-webkit-inner-spin-button {
            appearance: none;
            margin: 0
        }

        .draft-ui .currency-input:focus {
            box-shadow: none !important;
            border: 0 !important;
            outline: none !important
        }

        .draft-ui .save-btn {
            display: grid;
            place-items: center;
            height: 48px;
            width: 48px;
            border-radius: 14px;
            border: 1px solid #7e22ce;
            background: #9333ea;
            color: white;
            font-weight: 950;
            transition: .15s;
            box-shadow: 0 8px 16px rgba(147, 51, 234, .18)
        }

        .draft-ui .save-btn:hover {
            background: #7e22ce
        }

        .draft-ui .basket-scroll {
            overflow-x: auto
        }

        .draft-ui .basket-grid {
            display: grid;
            grid-template-columns: 56px minmax(340px, 1fr) 96px 146px 160px 138px 62px;
            gap: 16px;
            align-items: start;
            min-width: 1060px
        }

        .draft-ui .basket-grid-head {
            align-items: center;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 12px;
            font-weight: 950;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: 15px 28px
        }

        .draft-ui .basket-row {
            padding: 20px 28px;
            border-bottom: 1px solid #e2e8f0;
            background: #fff
        }

        .draft-ui .basket-row:last-child {
            border-bottom: 0
        }

        .draft-ui .row-number {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border: 1px solid #dbe4ef;
            border-radius: 14px;
            background: #fff;
            font-weight: 950;
            color: #0f172a;
            font-size: 16px
        }

        .draft-ui .item-description-card {
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            background: #fff;
            padding: 12px 14px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04)
        }

        .draft-ui .item-description-card:focus-within {
            border-color: #9333ea;
            box-shadow: 0 0 0 3px rgba(147, 51, 234, .18)
        }

        .draft-ui .description-input {
            width: 100%;
            min-height: 62px !important;
            max-height: 120px;
            resize: vertical;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            padding: 0 !important;
            box-shadow: none !important;
            font-size: 14px !important;
            line-height: 1.55 !important;
            font-weight: 650 !important;
            color: #64748b !important;
            overflow: auto
        }

        .draft-ui .description-input:focus {
            border: 0 !important;
            box-shadow: none !important;
            outline: none !important
        }

        .draft-ui .url-edit-row {
            display: flex;
            align-items: stretch;
            gap: 8px;
            margin-top: 10px;
            width: 100%
        }

        .draft-ui .url-input {
            margin-top: 0;
            width: 100%;
            flex: 1 1 auto;
            border-radius: 14px !important;
            border: 1px solid #dbe4ef !important;
            background: #fff !important;
            padding: 11px 14px !important;
            font-size: 13px !important;
            font-weight: 800 !important;
            color: #7e22ce !important;
            min-height: 44px !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04) !important
        }

        .draft-ui .url-open-btn {
            flex: 0 0 44px;
            display: grid;
            place-items: center;
            height: 44px;
            border-radius: 14px;
            border: 1px solid #dbe4ef;
            background: #fff;
            color: #475569;
            font-size: 20px;
            font-weight: 950;
            text-decoration: none;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            transition: .15s
        }

        .draft-ui .url-open-btn:hover {
            border-color: #9333ea;
            color: #7e22ce;
            box-shadow: 0 0 0 3px rgba(147, 51, 234, .12)
        }

        .draft-ui .url-input:focus {
            border-color: #9333ea !important;
            box-shadow: 0 0 0 3px rgba(147, 51, 234, .18) !important;
            outline: none !important
        }

        .draft-ui .sku-input {
            margin-top: 10px;
            width: 100%;
            max-width: 420px;
            border-radius: 14px !important;
            border: 1px solid #dbe4ef !important;
            background: #fff !important;
            padding: 11px 14px !important;
            font-size: 14px !important;
            font-weight: 800 !important;
            color: #64748b !important;
            min-height: 44px !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04) !important
        }

        .draft-ui .save-state {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            font-size: 11px;
            font-weight: 950;
            color: #64748b
        }

        .draft-ui .save-state-dot {
            height: 7px;
            width: 7px;
            border-radius: 999px;
            background: #94a3b8
        }

        .draft-ui .save-state[data-state=dirty] {
            color: #ca8a04
        }

        .draft-ui .save-state[data-state=dirty] .save-state-dot {
            background: #f59e0b
        }

        .draft-ui .save-state[data-state=saving] {
            color: #7e22ce
        }

        .draft-ui .save-state[data-state=saving] .save-state-dot {
            background: #9333ea;
            animation: pulse 1s infinite
        }

        .draft-ui .save-state[data-state=saved] {
            color: #16a34a
        }

        .draft-ui .save-state[data-state=saved] .save-state-dot {
            background: #22c55e
        }

        .draft-ui .save-state[data-state=error] {
            color: #dc2626
        }

        .draft-ui .save-state[data-state=error] .save-state-dot {
            background: #ef4444
        }

        .draft-ui .toast-card {
            border-left: 4px solid #22c55e
        }

        .draft-ui .compact-input {
            width: 100%;
            border: 1px solid #cbd5e1 !important;
            border-radius: 14px !important;
            min-height: 48px !important;
            padding: 11px 13px !important;
            text-align: center;
            font-weight: 900;
            color: #0f172a;
            background: #fff !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04) !important
        }

        .draft-ui .readonly-total {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            font-weight: 950;
            color: #0f172a
        }

        .draft-ui .micro-help {
            margin-top: 7px;
            font-size: 11px;
            font-weight: 800;
            line-height: 1.35;
            color: #94a3b8
        }

        .draft-ui .trash-btn {
            display: inline-grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 14px;
            border: 1px solid #fecaca;
            background: #fff;
            color: #ef4444;
            font-weight: 950;
            transition: .15s
        }

        .draft-ui .trash-btn:hover {
            background: #fef2f2;
            border-color: #fca5a5;
            color: #dc2626
        }

        .draft-ui .row-label {
            display: none;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            margin-bottom: 7px
        }

        .draft-ui .autosave-hint {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            font-size: 11px;
            font-weight: 900;
            color: #64748b
        }

        .draft-ui .autosave-dot {
            height: 7px;
            width: 7px;
            border-radius: 999px;
            background: #22c55e
        }

        .draft-ui details>summary {
            cursor: pointer;
            list-style: none
        }

        .draft-ui details>summary::-webkit-details-marker {
            display: none
        }

        @media(max-width:1500px) {
            .draft-ui .retailer-header {
                grid-template-columns: minmax(210px, 1fr) 230px minmax(360px, 1.5fr);
                gap: 16px;
                padding: 22px
            }

            .draft-ui .money-tile-grid {
                gap: 10px
            }

            .draft-ui .money-box {
                padding: 13px 14px
            }

            .draft-ui .money-value {
                font-size: 17px
            }

            .draft-ui .basket-grid {
                grid-template-columns: 52px minmax(310px, 1fr) 86px 132px 146px 126px 56px;
                gap: 13px;
                min-width: 960px
            }

            .draft-ui .basket-row,
            .draft-ui .basket-grid-head {
                padding-left: 22px;
                padding-right: 22px
            }
        }

        @media(max-width:1180px) {
            .draft-ui .retailer-header {
                display: block
            }

            .draft-ui .retailer-delivery-panel {
                margin-top: 18px;
                border-left: 0;
                padding-left: 0
            }

            .draft-ui .money-tile-grid {
                margin-top: 18px;
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }
        }

        @media(max-width:900px) {

            .draft-ui .basket-grid,
            .draft-ui .basket-grid-head {
                display: block;
                min-width: 0
            }

            .draft-ui .basket-grid-head {
                display: none
            }

            .draft-ui .basket-row {
                display: grid;
                gap: 12px
            }

            .draft-ui .row-label {
                display: block
            }

            .draft-ui .row-number {
                margin-bottom: 8px
            }
        }
    </style>

    <div
        class="draft-ui space-y-4"
        x-data="draftWorkspace({
            detectUrl: '{{ route('draft-orders.detect-retailer') }}',
            quickRetailerUrl: '{{ route('draft-orders.retailers.quick-store') }}',
            csrf: '{{ csrf_token() }}',
            initialTab: '{{ $activeTab }}'
        })"
        x-init="boot()"
        @delete-item.window="deleteModal = { open: true, url: $event.detail.url, title: $event.detail.title }"
    >
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700">
                <strong>Something needs checking:</strong> {{ $errors->first() }}
            </div>
        @endif

        @if (session('success'))
            <div
                x-data="{ show: true }"
                x-show="show"
                x-transition
                class="fixed bottom-6 right-6 z-50 w-[360px] max-w-[calc(100vw-2rem)] rounded-3xl border border-emerald-200 bg-white p-4 shadow-2xl"
            >
                <div class="flex gap-3">
                    <div class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-600">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="h-5 w-5"
                        >
                            <path d="M20 6 9 17l-5-5" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-black text-slate-950">Saved</p>
                        <p class="mt-1 text-sm text-slate-500">{{ session('success') }}</p>
                    </div>
                    <button
                        type="button"
                        @click="show=false"
                        class="rounded-xl px-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                    >✕</button>
                </div>
            </div>
        @endif

        {{-- Header --}}
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-5 px-6 py-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <a
                        href="{{ route('draft-orders.index') }}"
                        class="text-sm font-semibold text-slate-500 hover:text-slate-950"
                    >← Back to drafts</a>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-black tracking-tight text-slate-950">Draft #{{ $draftNo }}</h1>
                        <span
                            class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black uppercase tracking-wide text-amber-700"
                        >{{ $draft->status ?: 'open' }}</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-500">
                        {{ $customerName }}
                        @if ($draft->request_ref)
                            <span class="mx-1">•</span> Source: Order request #{{ $draft->request_ref }}
                        @endif
                        @if ($draft->created_at)
                            <span class="mx-1">•</span> Created
                            {{ \Carbon\Carbon::parse($draft->created_at)->format('d M Y, H:i') }}
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        disabled
                        class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-400"
                    >Duplicate soon</button>
                    <button
                        type="button"
                        disabled
                        class="rounded-2xl bg-purple-600 px-5 py-3 text-sm font-black text-white opacity-80"
                    >Finalise to Order soon</button>
                </div>
            </div>
            <div class="flex gap-1 border-t border-slate-100 px-5 py-3">
                @foreach ([['products', 'Products', '🛒'], ['customer', 'Customer', '👤'], ['notes', 'Notes', '📝'], ['fees', 'Dabba fees', '🏷️'], ['activity', 'Activity', '〽️']] as [$key, $label, $icon])
                    <button
                        type="button"
                        @click="tab='{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'border-purple-600 text-purple-700 bg-purple-50' :
                            'border-transparent text-slate-600 hover:bg-slate-50'"
                        class="rounded-2xl border px-4 py-2.5 text-sm font-black transition"
                    >
                        <span class="mr-1">{{ $icon }}</span>{{ $label }}
                    </button>
                @endforeach
            </div>
        </section>

        <div class="grid gap-4 2xl:grid-cols-[minmax(0,1fr)_340px]">
            <main class="space-y-4">
                <section
                    x-show="tab === 'products'"
                    x-cloak
                    class="space-y-4"
                >
                    {{-- Better add product panel --}}
                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Add product</h2>
                                <p class="mt-1 text-sm text-slate-500">Paste the URL, confirm the retailer, enter
                                    quantity and unit price. Delivery fees are adjusted in the basket rows below.</p>
                            </div>
                            <button
                                type="button"
                                disabled
                                class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-400"
                            >Add multiple soon</button>
                        </div>

                        <form
                            method="POST"
                            action="{{ route('draft-orders.items.store', $draft->id) }}"
                            class="space-y-4"
                        >
                            @csrf
                            <div class="grid gap-4 2xl:grid-cols-[minmax(0,1fr)_260px_110px_150px] 2xl:items-end">
                                <div>
                                    <label class="field-label">Product URL / code</label>
                                    <div class="flex gap-2">
                                        <input
                                            name="url"
                                            x-model="newItem.url"
                                            @blur.debounce.300ms="detectRetailer()"
                                            placeholder="Paste full product URL, Amazon short link, or product code"
                                            class="input-clean min-w-0 flex-1 text-sm"
                                        >
                                        <button
                                            type="button"
                                            @click="detectRetailer()"
                                            class="row-action shrink-0"
                                            title="Detect retailer"
                                        >Detect</button>
                                    </div>
                                </div>
                                <div>
                                    <label class="field-label">Retailer</label>
                                    <select
                                        name="retailer_id"
                                        x-model="newItem.retailerId"
                                        class="input-clean text-sm"
                                    >
                                        <option value="">Choose retailer</option>
                                        @foreach ($retailers as $retailer)
                                            <option value="{{ $retailer->id }}">{{ $retailer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="field-label">Quantity</label>
                                    <input
                                        name="qty"
                                        x-model="newItem.qty"
                                        type="number"
                                        min="1"
                                        value="1"
                                        class="input-clean text-sm"
                                    >
                                </div>
                                <div>
                                    <label class="field-label">Unit price £</label>
                                    <input
                                        name="unit_price"
                                        x-model="newItem.unitPrice"
                                        @focus="if (String(newItem.unitPrice) === '0' || String(newItem.unitPrice) === '0.00') newItem.unitPrice = ''"
                                        @blur="if (String(newItem.unitPrice).trim() === '') newItem.unitPrice = '0.00'"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="input-clean text-sm"
                                    >
                                </div>
                            </div>

                            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_240px_170px] xl:items-end">
                                <div>
                                    <label class="field-label">Description / item notes</label>
                                    <textarea
                                        name="description"
                                        rows="2"
                                        placeholder="Item details, colour, size, customer notes..."
                                        class="input-clean text-sm"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="field-label">Product code / SKU</label>
                                    <input
                                        name="product_code"
                                        placeholder="Optional SKU"
                                        class="input-clean text-sm"
                                    >
                                    <input
                                        type="hidden"
                                        name="sku"
                                        value=""
                                    >
                                </div>
                                <button
                                    type="submit"
                                    class="min-h-[46px] whitespace-nowrap rounded-2xl bg-purple-600 px-6 py-3 text-sm font-black text-white shadow-sm hover:bg-purple-700"
                                >Add item</button>
                            </div>
                            <div
                                class="rounded-2xl bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-600"
                                x-text="detectMessage || 'Retailer detection is automatic. If a short URL resolves, the full product URL will be saved.'"
                            ></div>
                        </form>
                    </section>

                    {{-- Basket --}}
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-6">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-xl font-black text-slate-950">Basket items ({{ $items->count() }})
                                    </h2>
                                    <span
                                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Newest
                                        item at the top</span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">Retailer delivery is one editable retailer-level
                                    charge. Seller delivery stays per item for marketplace sellers like Amazon/eBay.</p>
                            </div>
                            <div class="flex gap-2">
                                <input
                                    x-model="basketSearch"
                                    placeholder="Search items..."
                                    class="input-clean w-64 text-sm"
                                >
                                <button
                                    type="button"
                                    disabled
                                    class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-400"
                                >↕ Reorder</button>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-200">
                            @forelse ($groupedItems as $retailerId => $retailerItems)
                                @php
                                    $first = $retailerItems->first();
                                    $summary = $retailerRows->get($retailerId);
                                    $retailerName = $first->retailer_name ?: 'Unknown retailer';
                                    $initial = strtoupper(substr($retailerName, 0, 1));
                                    $logoPath = $summary->retailer_logo_path ?? ($first->retailer_logo_path ?? null);
                                    $logoUrl = $retailerLogoUrl($logoPath);
                                    $goodsTotal = round(
                                        (float) ($summary->retailer_subtotal ?? $retailerItems->sum('line_subtotal')),
                                        2,
                                    );
                                    $sellerDeliveryTotal = round(
                                        (float) $retailerItems->sum(
                                            fn($row) => (float) ($row->item_retailer_delivery_fee ??
                                                ($row->item_delivery_fee ?? 0)),
                                        ),
                                        2,
                                    );
                                    $retailerDeliveryFee = round(
                                        (float) ($summary->retailer_delivery_fee_total ?? 0),
                                        2,
                                    );
                                    $dabbaFee = round((float) ($summary->dabba_fee ?? 0), 2);
                                    $retailerGrand = round(
                                        $goodsTotal + $sellerDeliveryTotal + $retailerDeliveryFee + $dabbaFee,
                                        2,
                                    );
                                @endphp
                                <section
                                    x-data="{ open: true }"
                                    class="retailer-card"
                                >
                                    <div class="retailer-header">
                                        <button
                                            type="button"
                                            @click="open=!open"
                                            class="retailer-identity text-left"
                                        >
                                            @if ($logoUrl)
                                                <span
                                                    style="width:64px;height:64px;min-width:64px;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:22px;background:#fff;border:1px solid #e2e8f0;"
                                                >
                                                    <img
                                                        src="{{ $logoUrl }}"
                                                        alt="{{ $retailerName }} logo"
                                                        style="display:block;width:100%;height:100%;object-fit:contain;padding:8px;"
                                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';"
                                                    >
                                                    <span
                                                        class="retailer-initial absolute inset-0 hidden">{{ $initial }}</span>
                                                </span>
                                            @else
                                                <span class="retailer-initial">{{ $initial }}</span>
                                            @endif
                                            <span class="min-w-0">
                                                <span class="retailer-name block truncate">{{ $retailerName }}</span>
                                                <span class="retailer-subline block">
                                                    {{ $retailerItems->count() }}
                                                    {{ Str::plural('item', $retailerItems->count()) }}
                                                    <span class="mx-1">•</span>
                                                    Goods: {{ $money($goodsTotal) }}
                                                </span>
                                            </span>
                                        </button>

                                        <div class="retailer-delivery-panel">
                                            <form
                                                method="POST"
                                                action="{{ route('draft-orders.retailers.delivery.update', [$draft->id, $retailerId]) }}"
                                                class="retailer-delivery-form"
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <div>
                                                    <label class="money-label">Retailer delivery fee</label>
                                                    <div class="currency-input-wrap">
                                                        <span class="currency-prefix">£</span>
                                                        <input
                                                            name="retailer_delivery_fee_total"
                                                            value="{{ number_format($retailerDeliveryFee, 2, '.', '') }}"
                                                            type="number"
                                                            min="0"
                                                            step="0.01"
                                                            class="currency-input"
                                                            aria-label="Retailer delivery fee"
                                                        >
                                                    </div>
                                                </div>
                                                <button
                                                    type="submit"
                                                    class="save-btn"
                                                    title="Save retailer delivery fee"
                                                    aria-label="Save retailer delivery fee"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2.6"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        class="h-5 w-5"
                                                    >
                                                        <path d="M20 6 9 17l-5-5" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>

                                        <div class="money-tile-grid">
                                            <div class="money-box"><span class="money-label">Goods</span><span
                                                    class="money-value"
                                                >{{ $money($goodsTotal) }}</span></div>
                                            <div class="money-box"><span class="money-label">Seller
                                                    delivery</span><span
                                                    class="money-value">{{ $money($sellerDeliveryTotal) }}</span>
                                            </div>
                                            <div class="money-box"><span class="money-label">Dabba fee</span><span
                                                    class="money-value"
                                                >{{ $money($dabbaFee) }}</span></div>
                                            <div class="money-box money-box-purple"><span class="money-label">Retailer
                                                    total</span><span
                                                    class="money-value">{{ $money($retailerGrand) }}</span></div>
                                        </div>
                                    </div>

                                    <div
                                        x-show="open"
                                        x-collapse
                                    >
                                        <div class="basket-scroll">
                                            <div class="basket-grid basket-grid-head">
                                                <div>#</div>
                                                <div>Item description</div>
                                                <div>Qty</div>
                                                <div>Unit price</div>
                                                <div>Seller delivery <span
                                                        title="Per-item delivery from marketplace sellers like Amazon/eBay"
                                                    >ⓘ</span></div>
                                                <div>Line total</div>
                                                <div class="text-center">Actions</div>
                                            </div>

                                            @foreach ($retailerItems as $item)
                                                @php
                                                    $lineTotal =
                                                        (float) ($item->line_total ??
                                                            ($item->qty ?? 1) * ($item->unit_price ?? 0) +
                                                                ($item->item_retailer_delivery_fee ??
                                                                    ($item->item_delivery_fee ?? 0)));
                                                    $title = trim((string) ($item->description ?: 'New item'));
                                                    $shortUrl = $item->url
                                                        ? preg_replace('/^https?:\/\/www\./', '', $item->url)
                                                        : '';
                                                    $isJustAdded = $lastAddedItemId === (int) $item->id;
                                                @endphp
                                                <form
                                                    method="POST"
                                                    action="{{ route('draft-orders.items.update', [$draft->id, $item->id]) }}"
                                                    id="item-{{ $item->id }}"
                                                    class="basket-grid basket-row {{ $isJustAdded ? 'bg-purple-50/70' : '' }}"
                                                    x-data="{
                                                        qty: {{ (int) $item->qty }},
                                                        unit: {{ number_format((float) $item->unit_price, 2, '.', '') }},
                                                        delivery: {{ number_format((float) ($item->item_retailer_delivery_fee ?? ($item->item_delivery_fee ?? 0)), 2, '.', '') }},
                                                        saveState: 'saved',
                                                        saveMessage: 'Saved',
                                                        saveTimer: null,
                                                        get total() { return ((parseFloat(this.qty) || 0) * (parseFloat(this.unit) || 0) + (parseFloat(this.delivery) || 0)).toFixed(2); },
                                                        markDirty() {
                                                            this.saveState = 'dirty';
                                                            this.saveMessage = 'Unsaved changes';
                                                        },
                                                        queueSave() {
                                                            this.markDirty();
                                                            clearTimeout(this.saveTimer);
                                                            this.saveTimer = setTimeout(() => this.save(), 350);
                                                        },
                                                        async save() {
                                                            this.saveState = 'saving';
                                                            this.saveMessage = 'Saving…';
                                                            const result = await window.dabbaDraftAutosave($root);
                                                            this.saveState = result.ok ? 'saved' : 'error';
                                                            this.saveMessage = result.message || (result.ok ? 'Saved' : 'Could not save — please check the values and try again.');
                                                        }
                                                    }"
                                                    x-show="matchesSearch($el)"
                                                >
                                                    @csrf
                                                    @method('PATCH')
                                                    <input
                                                        type="hidden"
                                                        name="retailer_id"
                                                        value="{{ $item->retailer_id }}"
                                                    >

                                                    <div class="pt-1">
                                                        <span class="row-number">{{ $loop->iteration }}</span>
                                                    </div>

                                                    <div class="min-w-0">
                                                        @if ($isJustAdded)
                                                            <span
                                                                class="mb-2 inline-flex rounded bg-purple-600 px-2 py-1 text-[10px] font-black uppercase tracking-widest text-white"
                                                            >Just added</span>
                                                        @endif
                                                        <div class="item-description-card">
                                                            <textarea
                                                                name="description"
                                                                rows="3"
                                                                @input="markDirty()"
                                                                @blur="save()"
                                                                class="description-input"
                                                            >{{ $item->description }}</textarea>
                                                        </div>
                                                        <label
                                                            class="mt-3 block text-[10px] font-black uppercase tracking-widest text-slate-400"
                                                        >Product URL</label>
                                                        <div class="url-edit-row">
                                                            <input
                                                                name="url"
                                                                value="{{ $item->url }}"
                                                                @input="markDirty()"
                                                                @blur="save()"
                                                                placeholder="Product URL — changing this can switch retailer after resolving"
                                                                class="url-input"
                                                            >
                                                            @if ($item->url)
                                                                <a
                                                                    href="{{ $item->url }}"
                                                                    target="_blank"
                                                                    rel="noopener"
                                                                    class="url-open-btn"
                                                                    title="Open product URL"
                                                                    aria-label="Open product URL"
                                                                >↗</a>
                                                            @endif
                                                        </div>
                                                        <input
                                                            name="product_code"
                                                            value="{{ $item->product_code }}"
                                                            @input="markDirty()"
                                                            @blur="save()"
                                                            placeholder="SKU / product code"
                                                            class="sku-input"
                                                        >
                                                        <span
                                                            class="save-state"
                                                            :data-state="saveState"
                                                        ><span class="save-state-dot"></span><span
                                                                x-text="saveMessage"
                                                            ></span></span>
                                                    </div>

                                                    <div>
                                                        <label class="row-label">Qty</label>
                                                        <input
                                                            name="qty"
                                                            x-model.number="qty"
                                                            value="{{ (int) $item->qty }}"
                                                            @input="queueSave()"
                                                            @blur="save()"
                                                            type="number"
                                                            min="1"
                                                            class="compact-input"
                                                        >
                                                    </div>

                                                    <div>
                                                        <label class="row-label">Unit price</label>
                                                        <div class="currency-input-wrap">
                                                            <span class="currency-prefix">£</span>
                                                            <input
                                                                name="unit_price"
                                                                x-model.number="unit"
                                                                value="{{ number_format((float) $item->unit_price, 2, '.', '') }}"
                                                                @focus="if (Number(unit) === 0) unit = ''"
                                                                @input="queueSave()"
                                                                @blur="if (unit === '' || unit === null) unit = 0; save()"
                                                                type="number"
                                                                min="0"
                                                                step="0.01"
                                                                class="currency-input"
                                                            >
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <label class="row-label">Seller delivery</label>
                                                        <div class="currency-input-wrap">
                                                            <span class="currency-prefix">£</span>
                                                            <input
                                                                name="item_retailer_delivery_fee"
                                                                x-model.number="delivery"
                                                                value="{{ number_format((float) ($item->item_retailer_delivery_fee ?? ($item->item_delivery_fee ?? 0)), 2, '.', '') }}"
                                                                @focus="if (Number(delivery) === 0) delivery = ''"
                                                                @input="queueSave()"
                                                                @blur="if (delivery === '' || delivery === null) delivery = 0; save()"
                                                                type="number"
                                                                min="0"
                                                                step="0.01"
                                                                class="currency-input"
                                                            >
                                                        </div>
                                                        <p class="micro-help">For Amazon/eBay marketplace seller fees
                                                        </p>
                                                    </div>

                                                    <div>
                                                        <label class="row-label">Line total</label>
                                                        <div
                                                            class="readonly-total"
                                                            x-text="'£' + total"
                                                        ></div>
                                                        <p class="micro-help">Qty × unit + delivery</p>
                                                    </div>

                                                    <div class="flex items-start justify-center">
                                                        <button
                                                            type="button"
                                                            @click.prevent="$dispatch('delete-item', { url: '{{ route('draft-orders.items.destroy', [$draft->id, $item->id]) }}', title: @js(Str::limit($title, 90)) })"
                                                            class="trash-btn"
                                                            title="Remove item"
                                                            aria-label="Remove item"
                                                        >
                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                viewBox="0 0 24 24"
                                                                fill="none"
                                                                stroke="currentColor"
                                                                stroke-width="2.4"
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                class="h-5 w-5"
                                                            >
                                                                <path d="M3 6h18" />
                                                                <path d="M8 6V4h8v2" />
                                                                <path d="M19 6l-1 14H6L5 6" />
                                                                <path d="M10 11v6" />
                                                                <path d="M14 11v6" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </form>
                                            @endforeach
                                        </div>

                                        <div
                                            class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                                            <button
                                                type="button"
                                                disabled
                                                class="text-sm font-black text-purple-600"
                                            >＋ Add item to {{ $retailerName }} soon</button>
                                            <div
                                                class="flex flex-wrap items-center gap-6 text-sm font-black text-slate-500">
                                                <span>{{ $retailerItems->count() }} items</span>
                                                <span>Goods: {{ $money($goodsTotal) }}</span>
                                                <span>Seller delivery: {{ $money($sellerDeliveryTotal) }}</span>
                                                <span>Retailer delivery fee: {{ $money($retailerDeliveryFee) }}</span>
                                                <span>Dabba fee: {{ $money($dabbaFee) }}</span>
                                                <span class="text-purple-700">Retailer total:
                                                    {{ $money($retailerGrand) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            @empty
                                <div class="p-10 text-center text-slate-500">No items in this draft yet.</div>
                            @endforelse
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 p-5">
                            <button
                                type="button"
                                disabled
                                class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-400"
                            >Bulk actions soon</button>
                            <div class="text-sm font-semibold text-slate-500">{{ $items->count() }} items · Last
                                updated
                                {{ $draft->updated_at ? \Carbon\Carbon::parse($draft->updated_at)->diffForHumans() : 'recently' }}
                            </div>
                            <button
                                type="button"
                                disabled
                                class="rounded-2xl border border-rose-200 px-4 py-2.5 text-sm font-black text-rose-400"
                            >Clear all items soon</button>
                        </div>
                    </section>

                    <section
                        x-show="tab === 'customer'"
                        x-cloak
                        class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <h2 class="text-xl font-black text-slate-950">Customer</h2>
                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-xs font-black uppercase tracking-widest text-slate-500">Name</p>
                                <p class="mt-1 font-bold text-slate-900">{{ $customerName }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-xs font-black uppercase tracking-widest text-slate-500">Customer ID</p>
                                <p class="mt-1 font-bold text-slate-900">{{ $draft->customer_id }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-xs font-black uppercase tracking-widest text-slate-500">Phone</p>
                                <p class="mt-1 font-bold text-slate-900">{{ $customerDetails['phone'] ?? '—' }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-xs font-black uppercase tracking-widest text-slate-500">Email</p>
                                <p class="mt-1 font-bold text-slate-900">{{ $customerDetails['email'] ?? '—' }}</p>
                            </div>
                        </div>
                    </section>

                    <section
                        x-show="tab === 'notes'"
                        x-cloak
                        class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <h2 class="text-xl font-black text-slate-950">Internal notes</h2>
                        <form
                            method="POST"
                            action="{{ route('draft-orders.notes.store', $draft->id) }}"
                            class="mt-4"
                        >
                            @csrf
                            <textarea
                                name="body"
                                rows="3"
                                placeholder="Add internal note..."
                                class="w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500"
                            ></textarea>
                            <div class="mt-3 flex justify-end"><button
                                    type="submit"
                                    class="rounded-2xl bg-purple-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-purple-700"
                                >Add note</button></div>
                        </form>
                        <div class="mt-5 space-y-3">
                            @forelse ($notes as $note)
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-sm font-bold text-slate-900">
                                            {{ $note->title ?: ucfirst(str_replace('_', ' ', $note->type)) }}</p>
                                        <p class="text-xs text-slate-400">{{ $note->author_name ?: 'System' }} ·
                                            {{ $note->created_at ? \Carbon\Carbon::parse($note->created_at)->format('d M Y H:i') : '' }}
                                        </p>
                                    </div>
                                    <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $note->body }}</p>
                                </div>
                            @empty
                                <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">No notes yet.</p>
                            @endforelse
                        </div>
                    </section>

                    <section
                        x-show="tab === 'fees'"
                        x-cloak
                        class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <h2 class="text-xl font-black text-slate-950">Dabba fees</h2>
                        <div class="mt-5 grid gap-3 md:grid-cols-2">
                            @forelse ($retailerSummaries as $summary)
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <p class="font-black text-slate-900">
                                        {{ $summary->retailer_name ?: 'Unknown retailer' }}</p>
                                    <div class="mt-3 space-y-1 text-sm">
                                        <div class="flex justify-between">
                                            <span>Subtotal</span><strong>{{ $money($summary->retailer_subtotal) }}</strong>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Delivery</span><strong>{{ $money($summary->retailer_delivery_fee_total) }}</strong>
                                        </div>
                                        <div class="flex justify-between border-t pt-2">
                                            <span>Fee</span><strong>{{ $money($summary->dabba_fee) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">No fee groups yet.</p>
                            @endforelse
                        </div>
                    </section>

                    <section
                        x-show="tab === 'activity'"
                        x-cloak
                        class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <h2 class="text-xl font-black text-slate-950">Activity</h2>
                        <p class="mt-2 text-sm text-slate-500">Activity timeline will be expanded after finalise
                            workflow.</p>
                    </section>
            </main>

            {{-- Sticky sidebar --}}
            <aside class="space-y-4 xl:sticky xl:top-4 xl:self-start">
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-black text-slate-950">Order summary</h2><button
                            type="button"
                            disabled
                            class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-black text-slate-400"
                        >Details</button>
                    </div>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between"><span class="text-slate-500">Items
                                subtotal</span><strong>{{ $money($draft->items_subtotal) }}</strong></div>
                        <div class="flex justify-between"><span class="text-slate-500">Delivery
                                fees</span><strong>{{ $money($draft->retailer_delivery_total) }}</strong></div>
                        <div class="flex justify-between"><span class="text-slate-500">Retailer
                                fees</span><strong>{{ $money($draft->dabba_fee_total) }}</strong></div>
                    </div>
                    <div class="mt-4 border-t border-slate-200 pt-4">
                        <div class="flex items-end justify-between"><span
                                class="text-sm font-black text-slate-600">Total</span><strong
                                class="text-3xl font-black text-slate-950"
                            >{{ $money($draft->grand_total) }}</strong></div>
                        <span
                            class="mt-3 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600"
                        >Qty {{ $qtyTotal }}</span>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-black text-slate-950">Customer</h2><button
                            type="button"
                            disabled
                            class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-black text-slate-400"
                        >Edit soon</button>
                    </div>
                    <div class="mt-4 space-y-2 text-sm text-slate-600">
                        <p class="font-black text-slate-950">{{ $customerName }}</p>
                        <p>{{ $customerDetails['phone'] ?? '—' }}</p>
                        <p>{{ $customerDetails['email'] ?? '—' }}</p>
                        <p class="whitespace-pre-line">{{ $customerDetails['address'] ?? '—' }}</p>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-black text-slate-950">Draft settings</h2><button
                            type="button"
                            disabled
                            class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-black text-slate-400"
                        >Edit</button>
                    </div>
                    <form
                        method="POST"
                        action="{{ route('draft-orders.update', $draft->id) }}"
                        class="mt-4 space-y-3"
                    >
                        @csrf
                        @method('PATCH')
                        <div><label
                                class="text-xs font-black uppercase tracking-widest text-slate-500">Status</label><select
                                name="status"
                                class="mt-1 w-full rounded-2xl border-slate-300 text-sm focus:border-purple-500 focus:ring-purple-500"
                            >
                                @foreach ($statusOptions as $status)
                                    <option
                                        value="{{ $status }}"
                                        @selected(($draft->status ?? 'open') === $status)
                                    >{{ ucfirst($status) }}</option>
                                @endforeach
                            </select></div>
                        <div><label class="text-xs font-black uppercase tracking-widest text-slate-500">Fee
                                mode</label><select
                                name="fee_mode"
                                class="mt-1 w-full rounded-2xl border-slate-300 text-sm focus:border-purple-500 focus:ring-purple-500"
                            >
                                <option
                                    value="standard"
                                    @selected(($draft->fee_mode ?? 'standard') === 'standard')
                                >Standard fee</option>
                                <option
                                    value="fee_disabled"
                                    @selected(($draft->fee_mode ?? '') === 'fee_disabled')
                                >Fee disabled</option>
                            </select></div>
                        <label
                            class="flex items-center gap-2 rounded-2xl bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-700"
                        ><input
                                type="checkbox"
                                name="home_delivery_requested"
                                value="1"
                                @checked(!empty($draft->home_delivery_requested))
                                class="rounded border-slate-300 text-purple-600 focus:ring-purple-500"
                            > Home delivery requested</label>
                        <button
                            type="submit"
                            class="w-full rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white hover:bg-slate-800"
                        >Save draft</button>
                    </form>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-950">Actions</h2>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            disabled
                            class="rounded-2xl border border-purple-200 px-4 py-3 text-sm font-black text-purple-400"
                        >Save draft</button>
                        <button
                            type="button"
                            disabled
                            class="rounded-2xl bg-purple-600 px-4 py-3 text-sm font-black text-white opacity-80"
                        >Finalise to Order</button>
                    </div>
                </section>
            </aside>
        </div>

        {{-- Modern delete confirmation modal --}}
        <div
            x-show="deleteModal.open"
            x-cloak
            class="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4"
            @keydown.escape.window="deleteModal.open=false"
        >
            <div
                @click.outside="deleteModal.open=false"
                class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl"
            >
                <div class="flex items-start gap-4">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-rose-50 text-rose-600">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.4"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="h-6 w-6"
                        >
                            <path d="M3 6h18" />
                            <path d="M8 6V4h8v2" />
                            <path d="M19 6l-1 14H6L5 6" />
                            <path d="M10 11v6" />
                            <path d="M14 11v6" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-xl font-black text-slate-950">Remove item?</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">This will remove <strong
                                x-text="deleteModal.title"
                            ></strong> from the draft basket.</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        @click="deleteModal.open=false"
                        class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-50"
                    >Cancel</button>
                    <button
                        type="button"
                        @click="confirmDeleteItem()"
                        class="rounded-2xl bg-rose-600 px-5 py-3 text-sm font-black text-white hover:bg-rose-700"
                    >Remove item</button>
                </div>
            </div>
        </div>


        <div
            x-show="toast.open"
            x-cloak
            class="toast-card fixed bottom-6 right-6 z-50 w-full max-w-sm rounded-3xl border border-slate-200 bg-white p-5 shadow-2xl"
            x-transition
        >
            <div class="flex items-start gap-3">
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-emerald-50 text-emerald-600">✓
                </div>
                <div class="min-w-0">
                    <div
                        class="text-sm font-black text-slate-950"
                        x-text="toast.title"
                    ></div>
                    <div
                        class="mt-1 text-sm text-slate-500"
                        x-text="toast.message"
                    ></div>
                </div>
                <button
                    type="button"
                    @click="toast.open=false"
                    class="ml-auto rounded-xl px-2 py-1 text-slate-400 hover:bg-slate-100"
                >✕</button>
            </div>
        </div>

        {{-- Retailer-not-detected modal --}}
        <div
            x-show="retailerModal.open"
            x-cloak
            class="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4"
            @keydown.escape.window="retailerModal.open=false"
        >
            <div
                @click.outside="retailerModal.open=false"
                class="w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-black text-slate-950">Retailer not detected</h2>
                        <p class="mt-1 text-sm text-slate-500">Add this retailer once and continue. The domain is
                            already cleaned for you.</p>
                    </div>
                    <button
                        type="button"
                        @click="retailerModal.open=false"
                        class="rounded-xl px-3 py-2 text-slate-500 hover:bg-slate-100"
                    >✕</button>
                </div>
                <div class="mt-5 space-y-3">
                    <div><label class="text-xs font-black uppercase tracking-widest text-slate-500">Cleaned URL /
                            domain</label><input
                            x-model="retailerModal.baseUrl"
                            class="mt-1 w-full rounded-2xl border-slate-300 text-sm focus:border-purple-500 focus:ring-purple-500"
                        ></div>
                    <div><label class="text-xs font-black uppercase tracking-widest text-slate-500">Retailer name
                            *</label><input
                            x-model="retailerModal.name"
                            placeholder="e.g. Example Retailer"
                            class="mt-1 w-full rounded-2xl border-slate-300 text-sm focus:border-purple-500 focus:ring-purple-500"
                        ></div>
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input
                            type="checkbox"
                            checked
                            class="rounded border-slate-300 text-purple-600 focus:ring-purple-500"
                        > Active</label>
                    <p
                        x-show="retailerModal.error"
                        x-text="retailerModal.error"
                        class="text-sm font-semibold text-rose-600"
                    ></p>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        @click="retailerModal.open=false"
                        class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-50"
                    >Cancel</button>
                    <button
                        type="button"
                        @click="saveRetailer()"
                        class="rounded-2xl bg-purple-600 px-5 py-3 text-sm font-black text-white hover:bg-purple-700"
                    >Save retailer & assign to item</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.dabbaDraftAutosave = async function(form) {
            const debugPayload = {};

            try {
                if (!(form instanceof HTMLFormElement)) {
                    console.error('Draft autosave received non-form element', {
                        received: form,
                        type: form ? form.constructor?.name : null,
                    });

                    return {
                        ok: false,
                        message: 'Autosave setup error: expected form element, got ' + (form ? form.constructor
                            ?.name : 'nothing') + '.',
                    };
                }

                const fields = form.querySelectorAll('input[name], textarea[name], select[name]');
                const data = new FormData();

                fields.forEach((field) => {
                    if (!field.name || field.disabled) return;

                    if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) {
                        return;
                    }

                    data.append(field.name, field.value ?? '');
                    if (field.name !== '_token') {
                        debugPayload[field.name] = field.value ?? '';
                    }
                });

                if (!data.has('_method')) {
                    data.append('_method', 'PATCH');
                    debugPayload._method = 'PATCH';
                }

                const csrf = data.get('_token') ||
                    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                    '{{ csrf_token() }}';

                console.info('Draft autosave starting', {
                    action: form.action,
                    methodOverride: data.get('_method'),
                    payload: debugPayload,
                });

                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf,
                    },
                    credentials: 'same-origin',
                    body: data,
                });

                const rawBody = await response.text();
                let payload = {};

                try {
                    payload = rawBody ? JSON.parse(rawBody) : {};
                } catch (jsonError) {
                    console.error('Draft autosave response was not JSON', {
                        status: response.status,
                        rawBody,
                        jsonError,
                    });

                    return {
                        ok: false,
                        message: `Autosave returned HTTP ${response.status}, but not JSON.`,
                    };
                }

                console.info('Draft autosave response', {
                    status: response.status,
                    ok: response.ok,
                    payload,
                });

                if (!response.ok || payload.ok === false) {
                    const firstValidationError = payload.errors ?
                        Object.values(payload.errors).flat().filter(Boolean)[0] :
                        null;

                    return {
                        ok: false,
                        message: firstValidationError || payload.message || payload.error ||
                            `Autosave failed with HTTP ${response.status}.`,
                    };
                }

                if (payload.reload) {
                    setTimeout(() => window.location.reload(), 500);
                }

                return {
                    ok: true,
                    message: payload.message || 'Saved.'
                };
            } catch (e) {
                console.error('Draft autosave crashed before receiving a valid response', {
                    errorName: e?.name,
                    errorMessage: e?.message,
                    stack: e?.stack,
                    action: form?.action,
                    payload: debugPayload,
                });

                return {
                    ok: false,
                    message: `Autosave JavaScript/network error: ${e?.message || e}`,
                };
            }
        };

        window.addEventListener('error', function(event) {
            console.error('Draft page JavaScript error', {
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                error: event.error,
            });
        });

        window.addEventListener('unhandledrejection', function(event) {
            console.error('Draft page unhandled promise rejection', event.reason);
        });

        function draftWorkspace(config) {
            return {
                tab: config.initialTab || 'products',
                basketSearch: '',
                detectMessage: '',
                detectedRetailerId: null,
                newItem: {
                    url: '',
                    retailerId: '',
                    qty: 1,
                    unitPrice: '0.00'
                },
                retailerModal: {
                    open: false,
                    name: '',
                    baseUrl: '',
                    error: ''
                },
                deleteModal: {
                    open: false,
                    url: '',
                    title: ''
                },
                toast: {
                    open: false,
                    title: '',
                    message: ''
                },

                boot() {
                    const justAdded = document.querySelector('[id^="item-"].bg-purple-50\\/70');
                    if (justAdded) setTimeout(() => justAdded.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    }), 250);
                },

                matchesSearch(el) {
                    const q = (this.basketSearch || '').trim().toLowerCase();
                    if (!q) return true;
                    return el.innerText.toLowerCase().includes(q);
                },

                showToast(title, message) {
                    this.toast.title = title;
                    this.toast.message = message;
                    this.toast.open = true;
                    setTimeout(() => this.toast.open = false, 3500);
                },

                async confirmDeleteItem() {
                    const url = this.deleteModal.url;
                    const title = this.deleteModal.title;
                    this.deleteModal.open = false;
                    try {
                        const response = await fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': config.csrf,
                            },
                        });
                        if (!response.ok) throw new Error('Delete failed');
                        this.showToast('Item removed', '“' + title + '” has been removed.');
                        setTimeout(() => window.location.reload(), 600);
                    } catch (e) {
                        this.showToast('Could not remove item', 'Please refresh and try again.');
                    }
                },

                cleanHost(url) {
                    try {
                        let value = (url || '').trim();
                        if (!value) return '';
                        if (!value.includes('://')) value = 'https://' + value;
                        let host = new URL(value).hostname.toLowerCase();
                        return host.replace(/^www\./, '');
                    } catch (e) {
                        return (url || '').replace(/^https?:\/\//, '').replace(/^www\./, '').split('/')[0].toLowerCase();
                    }
                },

                guessRetailerName(host) {
                    if (!host) return '';
                    const first = host.split('.')[0] || '';
                    return first.replace(/[-_]+/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                },

                async detectRetailer() {
                    this.detectMessage = '';
                    this.detectedRetailerId = null;
                    if (!this.newItem.url.trim()) return;

                    try {
                        const response = await fetch(config.detectUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': config.csrf,
                            },
                            body: JSON.stringify({
                                url: this.newItem.url
                            }),
                        });

                        const payload = await response.json();
                        const retailer = payload.retailer || {};

                        if (retailer.final_url || retailer.finalUrl) {
                            this.newItem.url = retailer.final_url || retailer.finalUrl;
                        }

                        if (retailer.retailer_id || retailer.retailerId) {
                            const id = retailer.retailer_id || retailer.retailerId;
                            this.newItem.retailerId = String(id);
                            this.detectedRetailerId = id;
                            this.detectMessage = 'Retailer detected: ' + (retailer.name || 'matched') + ((retailer
                                .final_url || retailer.finalUrl) ? ' · URL expanded/cleaned' : '');
                            return;
                        }

                        const host = retailer.host || this.cleanHost(this.newItem.url);
                        this.retailerModal.baseUrl = host;
                        this.retailerModal.name = retailer.name || this.guessRetailerName(host);
                        this.retailerModal.error = '';
                        this.retailerModal.open = true;
                        this.detectMessage = 'Retailer not recognised. Add it once and continue.';
                    } catch (e) {
                        this.detectMessage = 'Could not detect retailer. You can choose one manually.';
                    }
                },

                async saveRetailer() {
                    this.retailerModal.error = '';
                    if (!this.retailerModal.name.trim() || !this.retailerModal.baseUrl.trim()) {
                        this.retailerModal.error = 'Retailer name and domain are required.';
                        return;
                    }

                    const response = await fetch(config.quickRetailerUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': config.csrf,
                        },
                        body: JSON.stringify({
                            name: this.retailerModal.name,
                            base_url: this.retailerModal.baseUrl
                        }),
                    });

                    const payload = await response.json();
                    if (!response.ok || !payload.ok) {
                        this.retailerModal.error = payload.message || 'Could not save retailer.';
                        return;
                    }

                    const retailer = payload.retailer;
                    const select = document.querySelector('select[name="retailer_id"]');
                    let option = select.querySelector('option[value="' + retailer.id + '"]');
                    if (!option) {
                        option = new Option(retailer.name, retailer.id, true, true);
                        select.add(option);
                    }
                    this.newItem.retailerId = String(retailer.id);
                    this.detectedRetailerId = retailer.id;
                    this.detectMessage = 'Retailer added and selected: ' + retailer.name;
                    this.retailerModal.open = false;
                }
            }
        }
    </script>
</x-app-layout>
