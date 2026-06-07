<x-app-layout>
    <x-slot name="header">Order Request #{{ $draft->request_ref ?: ($draft->draft_number ?: $draft->id) }}</x-slot>

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
        $normaliseFeeRate = function ($rate) {
            $rate = (float) ($rate ?? 0.20);
            return $rate > 1 ? ($rate / 100) : $rate;
        };
        $isCustomerSelfPurchase = ($draft->purchase_mode ?? 'standard') === 'customer_self_purchase';
        $computedGoodsSubtotal = round((float) $items->sum(fn($item) => ((float) ($item->qty ?? 1)) * ((float) ($item->unit_price ?? 0))), 2);
        $computedItemsSubtotal = $isCustomerSelfPurchase ? 0.0 : $computedGoodsSubtotal;
        $computedSellerDeliveryTotal = round((float) $items->sum(fn($item) => (float) ($item->item_retailer_delivery_fee ?? ($item->item_delivery_fee ?? 0))), 2);
        $computedRetailerDeliveryTotal = round((float) $retailerSummaries->sum(fn($summary) => (float) ($summary->retailer_delivery_fee_total ?? 0)), 2);
        $computedDabbaFeeTotal = round((float) $groupedItems->sum(function ($retailerItems, $retailerId) use ($retailerRows, $draft, $normaliseFeeRate) {
            $summary = $retailerRows->get($retailerId);
            if ((bool) ($summary->dabba_fee_is_disabled ?? false)) {
                return 0;
            }
            $goods = (float) $retailerItems->sum(fn($item) => ((float) ($item->qty ?? 1)) * ((float) ($item->unit_price ?? 0)));
            if ($goods <= 0) {
                return 0;
            }
            $rate = $normaliseFeeRate($summary->dabba_fee_rate ?? $draft->dabba_fee_rate ?? 0.20);
            $minimum = (float) ($summary->dabba_fee_min ?? $draft->dabba_fee_min ?? 10);
            return max($minimum, $goods * $rate);
        }), 2);
        $computedDeliveryTotal = round($computedSellerDeliveryTotal + $computedRetailerDeliveryTotal, 2);
        $initialDraftTotals = [
            'itemsSubtotal' => $computedItemsSubtotal,
            'retailerDelivery' => $computedDeliveryTotal,
            'dabbaFee' => $computedDabbaFeeTotal,
            'grandTotal' => round($computedItemsSubtotal + $computedDeliveryTotal + $computedDabbaFeeTotal, 2),
        ];
        $draftNo = $draft->draft_number ?: $draft->id;
        $requestRef = $draft->request_ref ?: $draftNo;
        $hasChildOrder = ! empty($draft->finalized_order_id);
        $isConsumedDraft = in_array((string) ($draft->status ?? ''), ['consumed', 'finalised'], true) || in_array((string) ($draft->state ?? ''), ['consumed', 'finalised'], true);
        $isCancelledDraft = in_array((string) ($draft->status ?? ''), ['cancelled', 'canceled'], true) || in_array((string) ($draft->state ?? ''), ['cancelled', 'canceled'], true);
        $isDraftEditable = ! $isCancelledDraft;
        $isReopenedVersionDraft = $hasChildOrder && ! $isConsumedDraft;
        $finalizedOrderLabel = $draft->finalized_order_number ? ('Order #' . $draft->finalized_order_number) : ($draft->finalized_order_id ? ('Order ID #' . $draft->finalized_order_id) : null);
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

        .draft-ui .retailer-stack {
            padding: 24px;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            border-top: 1px solid #e2e8f0
        }

        .draft-ui .retailer-card {
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            border: 1px solid #dbeafe;
            border-radius: 26px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, .08);
            overflow: hidden
        }

        .draft-ui .retailer-card + .retailer-card {
            margin-top: 22px
        }

        .draft-ui .retailer-header {
            display: grid;
            grid-template-columns: minmax(280px, 1.25fr) 220px minmax(360px, 1.45fr);
            gap: 18px;
            align-items: center;
            padding: 14px 18px;
            border-bottom: 1px solid #dbeafe;
            background: linear-gradient(90deg, #eff6ff 0%, #ffffff 48%, #faf5ff 100%)
        }

        .draft-ui .retailer-identity {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            border-radius: 20px;
            background: rgba(124, 58, 237, .075);
            border: 1px solid rgba(124, 58, 237, .12);
            padding: 10px 12px
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
            height: 48px;
            width: 48px;
            border-radius: 17px;
            background: linear-gradient(135deg, #ede9fe, #dbeafe);
            color: #5b21b6;
            font-size: 18px;
            font-weight: 950;
            flex: 0 0 auto
        }

        .draft-ui .retailer-name {
            font-size: 18px;
            font-weight: 950;
            line-height: 1.18;
            color: #6d28d9;
            letter-spacing: -.015em
        }

        .draft-ui .retailer-subline {
            margin-top: 5px;
            font-size: 12px;
            font-weight: 850;
            color: #64748b
        }

        .draft-ui .retailer-delivery-panel {
            border-left: 1px solid #e2e8f0;
            padding-left: 16px
        }

        .draft-ui .retailer-delivery-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 48px;
            gap: 10px;
            align-items: end
        }

        .draft-ui .money-label {
            display: block;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .09em;
            color: #64748b;
            margin-bottom: 7px
        }

        .draft-ui .money-tile-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(92px, 1fr));
            gap: 7px
        }

        .draft-ui .money-box {
            border-radius: 14px;
            background: #f8fafc;
            padding: 10px 12px;
            min-height: 58px;
            display: flex;
            flex-direction: column;
            justify-content: center
        }

        .draft-ui .money-box-purple {
            background: #faf5ff;
            color: #7e22ce
        }

        .draft-ui .money-value {
            font-size: 15px;
            font-weight: 900;
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
            grid-template-columns: 56px minmax(340px, 1fr) 96px 146px 160px 138px 92px;
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
            background: #fff;
            transition: background .18s ease, box-shadow .18s ease
        }

        .draft-ui .basket-row.is-reviewed {
            background: #ecfdf5;
            box-shadow: inset 5px 0 0 #86efac
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
            border: 2px solid #c4b5fd;
            border-radius: 16px;
            background: #fdfcff;
            padding: 12px 14px;
            box-shadow: 0 6px 18px rgba(88, 28, 135, .08)
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
                grid-template-columns: minmax(250px, 1.2fr) 210px minmax(330px, 1.35fr);
                gap: 14px;
                padding: 14px 16px
            }

            .draft-ui .money-tile-grid {
                gap: 7px
            }

            .draft-ui .money-box {
                padding: 9px 10px
            }

            .draft-ui .money-value {
                font-size: 14px
            }

            .draft-ui .basket-grid {
                grid-template-columns: 52px minmax(310px, 1fr) 86px 132px 146px 126px 56px;
                gap: 13px;
                min-width: 960px
            }

            .draft-ui .basket-row,
            .draft-ui .basket-grid-head {
                padding-left: 18px;
                padding-right: 18px
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
            initialTab: '{{ $activeTab }}',
            isConsumedDraft: @js($isConsumedDraft),
            isCustomerSelfPurchase: @js($isCustomerSelfPurchase),
            isCancelledDraft: @js($isCancelledDraft),
            hasChildOrder: @js($hasChildOrder),
            finalizedOrderLabel: @js($finalizedOrderLabel),
            finalizedOrderUrl: @js($draft->finalized_order_id ? route('orders.show', $draft->finalized_order_id) : null),
            totals: @js($initialDraftTotals)
        })"
        x-init="boot()"
        @delete-item.window="deleteModal = { open: true, url: $event.detail.url, title: $event.detail.title }"
        @consumed-draft-edit-attempt.window="openConsumedEditModal($event.detail.form || null)"
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

        @if ($isCancelledDraft)
            <section class="rounded-3xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white text-rose-600 shadow-sm">✕</div>
                    <div>
                        <h2 class="text-lg font-black text-rose-950">Cancelled draft is locked</h2>
                        <p class="mt-1 text-sm font-semibold leading-6 text-rose-800">
                            Products, prices, customer details, fees and delivery charges cannot be changed while this draft is cancelled. Change the status back to open first if the draft needs to be worked on again.
                        </p>
                    </div>
                </div>
            </section>
        @endif


        @if ($isCustomerSelfPurchase)
            <section class="rounded-3xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="max-w-4xl">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-sky-700">👤 Customer self-purchase</p>
                        <h2 class="mt-1 text-lg font-black text-sky-950">Customer has purchased the goods directly from the retailer.</h2>
                        <p class="mt-2 text-sm font-semibold leading-6 text-sky-900">
                            Dabba will not purchase these items and will not invoice the customer for the goods value.
                            Goods values are kept for fee calculation, arrivals, warehouse handling and customs documentation.
                            Only Dabba service, shipping and handling fees are billable.
                        </p>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-sky-700 ring-1 ring-sky-200">No Dabba buying</span>
                </div>
            </section>
        @endif

        {{-- Compact header --}}
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 px-5 py-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                        <a
                            href="{{ route('draft-orders.index') }}"
                            class="font-semibold text-slate-500 hover:text-slate-950"
                        >← Back to drafts</a>
                    </div>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-black tracking-tight text-slate-950">Draft Workbench</h1>
                        @if ($isCustomerSelfPurchase)
                            <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-black uppercase tracking-wide text-sky-700">Customer self-purchase</span>
                        @else
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-indigo-700">Dabba purchase</span>
                        @endif
                        <span class="rounded-full {{ $isConsumedDraft ? 'bg-amber-100 text-amber-700' : ($isCancelledDraft ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700') }} px-3 py-1 text-xs font-black uppercase tracking-wide">{{ $draft->status ?: 'open' }}</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $customerName }}
                        <span class="mx-1">•</span> Request #{{ $requestRef }}
                        <span class="mx-1">•</span> Draft #{{ $draft->id }}
                        @if ($draft->created_at)
                            <span class="mx-1">•</span> Created {{ \Carbon\Carbon::parse($draft->created_at)->format('d M Y, H:i') }}
                        @endif
                    </p>
                    @if ($hasChildOrder)
                        <div class="mt-2 flex flex-wrap items-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                            <span class="font-black tracking-wide text-amber-700">{{ $isConsumedDraft ? 'Consumed Draft' : 'Version Draft' }}</span>
                            <span class="text-amber-300">•</span>
                            <span class="font-semibold">Created {{ $finalizedOrderLabel ?: 'an order' }}</span>
                            @if ($isConsumedDraft)
                                <span class="hidden text-amber-300 md:inline">•</span>
                                <span class="font-semibold text-amber-800">Editing will prepare a new version.</span>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($draft->finalized_order_id)
                        <a
                            href="{{ route('orders.show', $draft->finalized_order_id) }}"
                            class="rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white hover:bg-emerald-700"
                        >Open {{ $finalizedOrderLabel }}</a>
                    @endif
                    @if ($isCancelledDraft)
                        <button
                            type="button"
                            disabled
                            class="cursor-not-allowed rounded-2xl bg-slate-200 px-4 py-2.5 text-sm font-black text-slate-500"
                            title="Cancelled drafts must be reopened before they can be finalised."
                        >Finalise locked</button>
                    @else
                        <button
                            type="button"
                            @click="openFinaliseModal()"
                            class="rounded-2xl bg-purple-600 px-4 py-2.5 text-sm font-black text-white shadow-sm hover:bg-purple-700"
                        >{{ $isConsumedDraft ? 'Create New Version' : 'Finalise to Order' }}</button>
                    @endif
                </div>
            </div>
            <div class="flex gap-1 border-t border-slate-100 px-5 py-2">
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

        <div class="grid gap-4 2xl:grid-cols-[minmax(0,1fr)_290px]">
            <main class="space-y-4">
                <section
                    x-show="tab === 'products'"
                    x-cloak
                    class="space-y-4"
                >
                    {{-- Better add product panel --}}
                    @if ($isCancelledDraft)
                        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="text-xl font-black text-slate-950">Add product</h2>
                            <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
                                This draft is cancelled, so adding products is disabled. Reopen the draft from Draft settings before adding or changing items.
                            </div>
                        </section>
                    @else
                    <section class="rounded-3xl border-2 border-purple-200 bg-white p-6 shadow-sm ring-4 ring-purple-50">
                        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Add product</h2>
                                <p class="mt-1 text-sm text-slate-500">Paste the URL, confirm the retailer, enter
                                    quantity and unit price. Delivery fees are adjusted in the basket rows below.</p>
                            </div>
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
                                            id="draft-product-url-input"
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
                    @endif

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

                        <div class="retailer-stack">
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
                                    x-data="{
                                        open: true,
                                        retailerId: @js((string) $retailerId),
                                        goodsTotal: {{ number_format($goodsTotal, 2, '.', '') }},
                                        sellerDeliveryTotal: {{ number_format($sellerDeliveryTotal, 2, '.', '') }},
                                        retailerDeliveryFee: {{ number_format($retailerDeliveryFee, 2, '.', '') }},
                                        dabbaFee: {{ number_format($dabbaFee, 2, '.', '') }},
                                        dabbaRate: {{ number_format((float) ($summary->dabba_fee_rate ?? ($draft->dabba_fee_rate ?? 0.20)), 4, '.', '') }},
                                        dabbaMin: {{ number_format((float) ($summary->dabba_fee_min ?? 10), 2, '.', '') }},
                                        dabbaDisabled: @js((bool) ($summary->dabba_fee_is_disabled ?? false)),
                                        isCustomerSelfPurchase: @js($isCustomerSelfPurchase),
                                        get retailerGrand() { return (this.isCustomerSelfPurchase ? 0 : this.goodsTotal) + this.sellerDeliveryTotal + this.retailerDeliveryFee + this.dabbaFee; },
                                        money(value) { return '£' + Number(value || 0).toFixed(2); },
                                        calculateDabbaFee() {
                                            if (this.dabbaDisabled) return 0;
                                            if (this.goodsTotal <= 0) return 0;
                                            const rate = Number(this.dabbaRate || 0.20);
                                            const multiplier = rate > 1 ? (rate / 100) : rate;
                                            return Math.max(this.dabbaMin, this.goodsTotal * multiplier);
                                        }
                                    }"
                                    @draft-item-repriced.window="if ($event.detail.retailerId === retailerId) { const oldFee = dabbaFee; goodsTotal += Number($event.detail.goodsDelta || 0); sellerDeliveryTotal += Number($event.detail.deliveryDelta || 0); dabbaFee = calculateDabbaFee(); $dispatch('draft-totals-repriced', { goodsDelta: Number($event.detail.goodsDelta || 0), deliveryDelta: Number($event.detail.deliveryDelta || 0), feeDelta: dabbaFee - oldFee }); }"
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
                                                    style="width:48px;height:48px;min-width:48px;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:17px;background:#fff;border:1px solid #e2e8f0;"
                                                >
                                                    <img
                                                        src="{{ $logoUrl }}"
                                                        alt="{{ $retailerName }} logo"
                                                        style="display:block;width:100%;height:100%;object-fit:contain;padding:6px;"
                                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';"
                                                    >
                                                    <span
                                                        class="retailer-initial absolute inset-0 hidden">{{ $initial }}</span>
                                                </span>
                                            @else
                                                <span class="retailer-initial">{{ $initial }}</span>
                                            @endif
                                            <span class="min-w-0">
                                                <span class="retailer-name block" title="{{ $retailerName }}">{{ $retailerName }}</span>
                                                <span class="retailer-subline block">
                                                    {{ $retailerItems->count() }}
                                                    {{ Str::plural('item', $retailerItems->count()) }}
                                                    <span class="mx-1">•</span>
                                                    {{ $isCustomerSelfPurchase ? 'Goods value:' : 'Goods:' }} {{ $money($goodsTotal) }}
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
                                                            value="{{ $retailerDeliveryFee > 0 ? number_format($retailerDeliveryFee, 2, '.', '') : '' }}"
                                                            type="number"
                                                            min="0"
                                                            step="0.01"
                                                            class="currency-input"
                                                            placeholder="Delivery"
                                                            onfocus="if (this.value === '0' || this.value === '0.00') this.value = '';"
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
                                            <div class="money-box"><span class="money-label">{{ $isCustomerSelfPurchase ? 'Goods value' : 'Goods' }}</span><span
                                                    class="money-value"
                                                 x-text="money(goodsTotal)"></span></div>
                                            <div class="money-box"><span class="money-label">Seller
                                                    delivery</span><span
                                                    class="money-value" x-text="money(sellerDeliveryTotal)"></span>
                                            </div>
                                            <div class="money-box"><span class="money-label">Dabba fee</span><span
                                                    class="money-value"
                                                 x-text="money(dabbaFee)"></span></div>
                                            <div class="money-box money-box-purple"><span class="money-label">{{ $isCustomerSelfPurchase ? 'Billable' : 'Retailer total' }}</span><span
                                                    class="money-value" x-text="money(retailerGrand)"></span></div>
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
                                                <div class="text-center">Review</div>
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
                                                    $isReviewed = ! empty($item->reviewed_at);
                                                @endphp
                                                <form
                                                    method="POST"
                                                    action="{{ route('draft-orders.items.update', [$draft->id, $item->id]) }}"
                                                    id="item-{{ $item->id }}"
                                                    class="basket-grid basket-row {{ $isJustAdded ? 'bg-purple-50/70' : '' }}" :class="{ 'is-reviewed': reviewed }"
                                                    x-data="{
                                                        qty: {{ (int) $item->qty }},
                                                        unit: {{ number_format((float) $item->unit_price, 2, '.', '') }},
                                                        delivery: {{ number_format((float) ($item->item_retailer_delivery_fee ?? ($item->item_delivery_fee ?? 0)), 2, '.', '') }},
                                                        reviewed: @js($isReviewed),
                                                        previousSubtotal: {{ number_format((float) (($item->qty ?? 1) * ($item->unit_price ?? 0)), 2, '.', '') }},
                                                        previousDelivery: {{ number_format((float) ($item->item_retailer_delivery_fee ?? ($item->item_delivery_fee ?? 0)), 2, '.', '') }},
                                                        retailerId: @js((string) $retailerId),
                                                        saveState: 'saved',
                                                        saveMessage: 'Saved',
                                                        saveTimer: null,
                                                        get total() { return ((parseFloat(this.qty) || 0) * (parseFloat(this.unit) || 0) + (parseFloat(this.delivery) || 0)).toFixed(2); },
                                                        markDirty() {
                                                            this.saveState = 'dirty';
                                                            this.saveMessage = 'Unsaved changes';
                                                            this.pushLiveTotals();
                                                        },
                                                        pushLiveTotals() {
                                                            const nextSubtotal = (parseFloat(this.qty) || 0) * (parseFloat(this.unit) || 0);
                                                            const nextDelivery = parseFloat(this.delivery) || 0;
                                                            const goodsDelta = nextSubtotal - this.previousSubtotal;
                                                            const deliveryDelta = nextDelivery - this.previousDelivery;
                                                            if (Math.abs(goodsDelta) < 0.001 && Math.abs(deliveryDelta) < 0.001) return;
                                                            this.previousSubtotal = nextSubtotal;
                                                            this.previousDelivery = nextDelivery;
                                                            this.$dispatch('draft-item-repriced', { retailerId: this.retailerId, goodsDelta, deliveryDelta });
                                                        },
                                                        toggleReviewed() {
                                                            this.reviewed = ! this.reviewed;
                                                            this.markDirty();
                                                            this.save();
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
                                                    <input type="hidden" name="reviewed" :value="reviewed ? 1 : 0">
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

                                                    <div class="flex flex-col items-center gap-2">
                                                        <button
                                                            type="button"
                                                            @click.prevent="toggleReviewed()"
                                                            class="rounded-xl border px-3 py-2 text-[11px] font-black uppercase tracking-wide transition"
                                                            :class="reviewed ? 'border-emerald-300 bg-emerald-100 text-emerald-800' : 'border-slate-200 bg-white text-slate-500 hover:bg-emerald-50 hover:text-emerald-700'"
                                                            x-text="reviewed ? 'Reviewed' : 'Review'"
                                                            title="Mark item as reviewed"
                                                        ></button>
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
                                            <span class="text-sm font-black text-slate-500">{{ $retailerItems->count() }} {{ Str::plural('item', $retailerItems->count()) }} for {{ $retailerName }}</span>
                                            <div
                                                class="flex flex-wrap items-center gap-6 text-sm font-black text-slate-500">
                                                <span>{{ $retailerItems->count() }} items</span>
                                                <span>{{ $isCustomerSelfPurchase ? 'Goods value:' : 'Goods:' }} <span x-text="money(goodsTotal)"></span></span>
                                                <span>Seller delivery: <span x-text="money(sellerDeliveryTotal)"></span></span>
                                                <span>Retailer delivery fee: {{ $money($retailerDeliveryFee) }}</span>
                                                <span>Dabba fee: <span x-text="money(dabbaFee)"></span></span>
                                                <span class="text-purple-700">{{ $isCustomerSelfPurchase ? 'Billable total:' : 'Retailer total:' }} <span x-text="money(retailerGrand)"></span></span>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            @empty
                                <div class="p-10 text-center text-slate-500">No items in this draft yet.</div>
                            @endforelse
                        </div>

                        <div class="border-t border-slate-100 p-5 text-sm font-semibold text-slate-500">{{ $items->count() }} items · Last
                                updated
                                {{ $draft->updated_at ? \Carbon\Carbon::parse($draft->updated_at)->diffForHumans() : 'recently' }}
                            </div>
                    </section>
                </section>

                    <section
                        x-show="tab === 'customer'"
                        x-cloak
                        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Customer details</h2>
                                <p class="mt-1 text-sm text-slate-500">Update the linked customer record before finalising the draft. These details will feed the order snapshot later.</p>
                            </div>
                            <span class="rounded-full bg-purple-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-purple-700">Customer #{{ $draft->customer_id }}</span>
                        </div>

                        @if (!empty($requestNotes['has_notes']))
                            <div class="mt-5 rounded-3xl border border-amber-200 bg-amber-50 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-xs font-black uppercase tracking-widest text-amber-700">Customer order request notes</p>
                                    @if (!empty($requestNotes['request_ref']))
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800">Request #{{ $requestNotes['request_ref'] }}</span>
                                    @endif
                                </div>
                                @if (!empty($requestNotes['notes']))
                                    <p class="mt-2 whitespace-pre-line text-sm font-semibold leading-6 text-amber-950">{{ $requestNotes['notes'] }}</p>
                                @elseif (!empty($requestNotes['converted_note_body']))
                                    <p class="mt-2 whitespace-pre-line text-sm font-semibold leading-6 text-amber-950">{{ $requestNotes['converted_note_body'] }}</p>
                                @endif
                                <p class="mt-3 text-xs font-bold text-amber-700">This note stays visible throughout the draft and order lifecycle.</p>
                            </div>
                        @endif

                        @php
                            $addressRow = $customerDetails['address_row'] ?? [];
                            $selectedCountryId = old('country_id', $addressRow['country_id'] ?? null);
                            $selectedPhoneCountryId = old('phone_country_id', $customerDetails['phone_country_id'] ?? $selectedCountryId);
                        @endphp

                        <form method="POST" action="{{ route('draft-orders.customer.update', $draft->id) }}" class="mt-5 space-y-5">
                            @csrf
                            @method('PATCH')

                            <div class="grid gap-4 md:grid-cols-3">
                                <div>
                                    <label class="field-label">First name</label>
                                    <input name="first_name" value="{{ old('first_name', $draft->first_name) }}" class="input-clean text-sm">
                                </div>
                                <div>
                                    <label class="field-label">Last name</label>
                                    <input name="last_name" value="{{ old('last_name', $draft->last_name) }}" class="input-clean text-sm">
                                </div>
                                <div>
                                    <label class="field-label">Company</label>
                                    <input name="company_name" value="{{ old('company_name', $draft->company_name) }}" class="input-clean text-sm">
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_180px_minmax(0,1fr)]">
                                <div>
                                    <label class="field-label">Email</label>
                                    <input name="email" type="email" value="{{ old('email', $customerDetails['email'] ?? '') }}" class="input-clean text-sm">
                                </div>
                                <div>
                                    <label class="field-label">Phone country</label>
                                    <select name="phone_country_id" class="input-clean text-sm">
                                        <option value="">—</option>
                                        @foreach ($countries as $country)
                                            <option value="{{ $country->id }}" @selected((int) $selectedPhoneCountryId === (int) $country->id)>{{ $country->iso2 ? strtoupper($country->iso2) . ' · ' : '' }}{{ $country->phone_code ? '+' . ltrim($country->phone_code, '+') : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="field-label">Phone</label>
                                    <input name="phone" value="{{ old('phone', $customerDetails['phone'] ?? '') }}" class="input-clean text-sm">
                                </div>
                            </div>

                            <div class="rounded-3xl bg-slate-50 p-4">
                                <h3 class="font-black text-slate-950">Primary address</h3>
                                <div class="mt-4 grid gap-4 md:grid-cols-2">
                                    <div class="md:col-span-2">
                                        <label class="field-label">Address line 1</label>
                                        <input name="line1" value="{{ old('line1', $addressRow['line1'] ?? '') }}" class="input-clean text-sm">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="field-label">Address line 2</label>
                                        <input name="line2" value="{{ old('line2', $addressRow['line2'] ?? '') }}" class="input-clean text-sm">
                                    </div>
                                    <div>
                                        <label class="field-label">City</label>
                                        <input name="city" value="{{ old('city', $addressRow['city'] ?? '') }}" class="input-clean text-sm">
                                    </div>
                                    <div>
                                        <label class="field-label">Region</label>
                                        <input name="region" value="{{ old('region', $addressRow['region'] ?? '') }}" class="input-clean text-sm">
                                    </div>
                                    <div>
                                        <label class="field-label">Postcode</label>
                                        <input name="postcode" value="{{ old('postcode', $addressRow['postcode'] ?? '') }}" class="input-clean text-sm">
                                    </div>
                                    <div>
                                        <label class="field-label">Country</label>
                                        <select name="country_id" class="input-clean text-sm">
                                            <option value="">Choose country</option>
                                            @foreach ($countries as $country)
                                                <option value="{{ $country->id }}" @selected((int) $selectedCountryId === (int) $country->id)>{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="rounded-2xl bg-purple-600 px-5 py-3 text-sm font-black text-white hover:bg-purple-700">Save customer details</button>
                            </div>
                        </form>
                    </section>

                    <section
                        x-show="tab === 'notes'"
                        x-cloak
                        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <h2 class="text-xl font-black text-slate-950">Notes</h2>
                        <p class="mt-1 text-sm text-slate-500">Customer request notes are shown first. Internal staff notes are below.</p>

                        @if (!empty($requestNotes['has_notes']))
                            <div class="mt-4 rounded-3xl border border-amber-200 bg-amber-50 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-xs font-black uppercase tracking-widest text-amber-700">Customer order request notes</p>
                                    @if (!empty($requestNotes['request_ref']))
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800">Request #{{ $requestNotes['request_ref'] }}</span>
                                    @endif
                                </div>
                                @if (!empty($requestNotes['notes']))
                                    <p class="mt-2 whitespace-pre-line text-sm font-semibold leading-6 text-amber-950">{{ $requestNotes['notes'] }}</p>
                                @elseif (!empty($requestNotes['converted_note_body']))
                                    <p class="mt-2 whitespace-pre-line text-sm font-semibold leading-6 text-amber-950">{{ $requestNotes['converted_note_body'] }}</p>
                                @endif
                            </div>
                        @endif



                        @if (!empty($requestNotes['order_request_id']) && isset($requestAttachments) && $requestAttachments->isNotEmpty())
                            <div class="mt-4 rounded-3xl border border-indigo-200 bg-indigo-50 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-xs font-black uppercase tracking-widest text-indigo-700">Customer file attachments</p>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-indigo-700 ring-1 ring-indigo-100">{{ $requestAttachments->count() }} file{{ $requestAttachments->count() === 1 ? '' : 's' }}</span>
                                </div>
                                <div class="mt-3 grid gap-2 md:grid-cols-2">
                                    @foreach ($requestAttachments as $attachment)
                                        <a
                                            href="{{ route('order-requests.attachments.show', [$requestNotes['order_request_id'], $attachment->id]) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="flex items-center justify-between gap-3 rounded-2xl border border-indigo-100 bg-white px-4 py-3 text-sm font-bold text-slate-800 hover:border-indigo-300 hover:bg-indigo-50"
                                        >
                                            <span class="min-w-0 truncate">{{ $attachment->original_name ?? $attachment->path ?? 'Attachment' }}</span>
                                            <span class="shrink-0 text-indigo-700">↗</span>
                                        </a>
                                    @endforeach
                                </div>
                                <p class="mt-3 text-xs font-bold text-indigo-700">These are the files originally attached to the customer order request.</p>
                            </div>
                        @endif

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
                        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        @php
                            $displayRate = (float) ($draft->dabba_fee_rate ?? 0.20);
                            if ($displayRate <= 1) { $displayRate = $displayRate * 100; }
                        @endphp
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Dabba fees</h2>
                                <p class="mt-1 text-sm text-slate-500">Adjust the draft-level fee policy before finalising. Current rule is max(minimum fee, rate × retailer goods subtotal).</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Draft-only policy</span>
                        </div>

                        <form method="POST" action="{{ route('draft-orders.fees.update', $draft->id) }}" class="mt-5 rounded-3xl bg-slate-50 p-4">
                            @csrf
                            @method('PATCH')
                            <div class="grid gap-4 md:grid-cols-[220px_180px_180px_minmax(0,1fr)] md:items-end">
                                <div>
                                    <label class="field-label">Fee mode</label>
                                    <select name="fee_mode" class="input-clean text-sm">
                                        <option value="standard" @selected(($draft->fee_mode ?? 'standard') === 'standard')>Standard fee</option>
                                        <option value="fee_disabled" @selected(($draft->fee_mode ?? '') === 'fee_disabled')>Fee disabled</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="field-label">Rate %</label>
                                    <input name="dabba_fee_rate" type="number" step="0.01" min="0" max="100" value="{{ old('dabba_fee_rate', number_format($displayRate, 2, '.', '')) }}" class="input-clean text-sm">
                                </div>
                                <div>
                                    <label class="field-label">Minimum £</label>
                                    <input name="dabba_fee_min" type="number" step="0.01" min="0" value="{{ old('dabba_fee_min', number_format((float) ($draft->dabba_fee_min ?? 10), 2, '.', '')) }}" class="input-clean text-sm">
                                </div>
                                <div class="flex md:justify-end">
                                    <button type="submit" class="rounded-2xl bg-purple-600 px-5 py-3 text-sm font-black text-white hover:bg-purple-700">Save fee policy</button>
                                </div>
                            </div>
                            <p class="mt-3 text-xs font-bold text-slate-500">Changing this recalculates all retailer groups on this draft only. It does not alter historic orders.</p>
                        </form>

                        <div class="mt-5 grid gap-3 md:grid-cols-2">
                            @forelse ($retailerSummaries as $summary)
                                @php
                                    $summaryRate = (float) ($summary->dabba_fee_rate ?? $draft->dabba_fee_rate ?? 0.20);
                                    if ($summaryRate <= 1) { $summaryRate = $summaryRate * 100; }
                                @endphp
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-black text-slate-900">{{ $summary->retailer_name ?: 'Unknown retailer' }}</p>
                                            <p class="mt-1 text-xs font-bold text-slate-500">Rate {{ number_format($summaryRate, 2) }}% · Min {{ $money($summary->dabba_fee_min ?? $draft->dabba_fee_min ?? 10) }}</p>
                                        </div>
                                        @if (!empty($summary->dabba_fee_is_disabled))
                                            <span class="rounded-full bg-amber-100 px-2 py-1 text-[10px] font-black uppercase text-amber-700">Disabled</span>
                                        @endif
                                    </div>
                                    <div class="mt-3 space-y-1 text-sm">
                                        <div class="flex justify-between"><span>{{ $isCustomerSelfPurchase ? 'Goods value (reference)' : 'Goods subtotal' }}</span><strong>{{ $money($summary->retailer_subtotal) }}</strong></div>
                                        <div class="flex justify-between"><span>Retailer delivery</span><strong>{{ $money($summary->retailer_delivery_fee_total) }}</strong></div>
                                        <div class="flex justify-between border-t pt-2"><span>Dabba fee</span><strong>{{ $money($summary->dabba_fee) }}</strong></div>
                                        <div class="flex justify-between text-purple-700"><span>{{ $isCustomerSelfPurchase ? 'Billable total' : 'Retailer total' }}</span><strong>{{ $money($summary->retailer_grand_total) }}</strong></div>
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
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Activity timeline</h2>
                                <p class="mt-1 text-sm font-semibold text-slate-500">Meaningful draft events, notes and version history for this workbench.</p>
                            </div>
                            <span class="inline-flex w-fit rounded-full bg-purple-50 px-3 py-1 text-xs font-black text-purple-700">
                                {{ ($activityLogs ?? collect())->count() }} event{{ ($activityLogs ?? collect())->count() === 1 ? '' : 's' }}
                            </span>
                        </div>

                        <div class="mt-5">
                            @forelse (($activityLogs ?? collect()) as $log)
                                @php
                                    $activityDate = $log->occurred_at ?: $log->created_at;
                                    $activityLabel = match ((string) ($log->type ?? '')) {
                                        'note', 'draft_note' => 'Staff note',
                                        'system_note' => 'System update',
                                        'supplier_note' => 'Supplier update',
                                        'customer_request_note', 'request_note' => 'Order request note',
                                        'order_version' => 'Version history',
                                        default => \Illuminate\Support\Str::headline((string) ($log->type ?? 'Activity')),
                                    };
                                    $activityTone = match ((string) ($log->type ?? '')) {
                                        'note', 'draft_note' => 'bg-blue-50 text-blue-700 ring-blue-100',
                                        'supplier_note' => 'bg-amber-50 text-amber-700 ring-amber-100',
                                        'customer_request_note', 'request_note' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                                        'order_version' => 'bg-violet-50 text-violet-700 ring-violet-100',
                                        default => 'bg-purple-50 text-purple-700 ring-purple-100',
                                    };
                                    $activityTitle = trim((string) ($log->title ?? '')) ?: $activityLabel;
                                    $activityBody = trim((string) ($log->body ?? ''));
                                    if (\Illuminate\Support\Str::startsWith($activityBody, ['{', '['])) {
                                        $decodedActivity = json_decode($activityBody, true);
                                        if (json_last_error() === JSON_ERROR_NONE) {
                                            $activityBody = collect($decodedActivity)
                                                ->map(fn ($value, $key) => \Illuminate\Support\Str::headline((string) $key) . ': ' . (is_scalar($value) ? (string) $value : json_encode($value)))
                                                ->implode("
");
                                        }
                                    }
                                @endphp

                                <div class="relative pl-8 {{ ! $loop->last ? 'pb-5' : '' }}">
                                    @if (! $loop->last)
                                        <div class="absolute left-[10px] top-6 h-full w-px bg-slate-200"></div>
                                    @endif
                                    <div class="absolute left-0 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-white ring-4 ring-purple-50">
                                        <div class="h-2.5 w-2.5 rounded-full bg-purple-500"></div>
                                    </div>

                                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide ring-1 {{ $activityTone }}">{{ $activityLabel }}</span>
                                                    <h3 class="text-sm font-black text-slate-950">{{ $activityTitle }}</h3>
                                                </div>
                                                @if ($activityBody !== '')
                                                    <p class="mt-2 whitespace-pre-line text-sm font-semibold leading-6 text-slate-600">{{ $activityBody }}</p>
                                                @endif
                                                @if (($log->type ?? '') === 'order_version' && !empty($log->order_id))
                                                    <a href="{{ route('orders.show', $log->order_id) }}" class="mt-3 inline-flex items-center rounded-xl bg-violet-600 px-3 py-1.5 text-xs font-black text-white hover:bg-violet-700">
                                                        Open order ↗
                                                    </a>
                                                @endif
                                            </div>
                                            <div class="shrink-0 text-left text-xs font-bold text-slate-400 sm:text-right">
                                                <div>{{ $activityDate ? \Illuminate\Support\Carbon::parse($activityDate)->format('d M Y') : 'Date unknown' }}</div>
                                                <div>{{ $activityDate ? \Illuminate\Support\Carbon::parse($activityDate)->format('H:i') : '' }}</div>
                                            </div>
                                        </div>
                                        <div class="mt-3 text-xs font-bold text-slate-400">
                                            {{ $log->author_name ?: 'DabbaDesk' }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                                    <p class="text-sm font-black text-slate-700">No activity recorded yet.</p>
                                    <p class="mt-1 text-sm text-slate-500">New meaningful actions on this draft will appear here.</p>
                                </div>
                            @endforelse
                        </div>
                    </section>
            </main>

            {{-- Sticky sidebar --}}
            <aside class="space-y-3 text-[13px] xl:sticky xl:top-4 xl:self-start">
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-black text-slate-950">{{ $isCustomerSelfPurchase ? 'Billable summary' : 'Order summary' }}</h2><button
                            type="button"
                            disabled
                            class="rounded-xl border border-slate-200 px-2.5 py-1 text-[11px] font-black text-slate-400"
                        >Details</button>
                    </div>
                    <div class="mt-3 space-y-2 text-[13px]">
                        @if ($isCustomerSelfPurchase)
                            <div class="flex justify-between"><span class="text-slate-500">Goods value <span class="text-[10px] font-black uppercase text-sky-600">ref</span></span><strong>{{ $money($computedGoodsSubtotal) }}</strong></div>
                        @else
                            <div class="flex justify-between"><span class="text-slate-500">Items subtotal</span><strong x-text="money(totals.itemsSubtotal)"></strong></div>
                        @endif
                        <div class="flex justify-between"><span class="text-slate-500">Delivery fees</span><strong x-text="money(totals.retailerDelivery)"></strong></div>
                        <div class="flex justify-between"><span class="text-slate-500">Dabba fee</span><strong x-text="money(totals.dabbaFee)"></strong></div>
                    </div>
                    <div class="mt-3 border-t border-slate-200 pt-3">
                        <div class="flex items-end justify-between"><span
                                class="text-sm font-black text-slate-600">{{ $isCustomerSelfPurchase ? 'Billable total' : 'Total' }}</span><strong
                                class="text-2xl font-black text-slate-950"
                                x-text="money(totals.grandTotal)"
                            ></strong></div>
                        <span
                            class="mt-3 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600"
                        >Qty {{ $qtyTotal }}</span>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-black text-slate-950">Customer</h2><button
                            type="button"
                            @click="tab='customer'"
                            class="rounded-xl border border-slate-200 px-2.5 py-1 text-[11px] font-black text-slate-600 hover:bg-slate-50"
                        >Edit</button>
                    </div>
                    <div class="mt-3 space-y-1.5 text-[13px] text-slate-600">
                        <p class="font-black text-slate-950">{{ $customerName }}</p>
                        <p>{{ $customerDetails['phone'] ?? '—' }}</p>
                        <p>{{ $customerDetails['email'] ?? '—' }}</p>
                        <p class="whitespace-pre-line">{{ $customerDetails['address'] ?? '—' }}</p>
                        @if (!empty($requestNotes['has_notes']))
                            <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-3">
                                <p class="text-[10px] font-black uppercase tracking-widest text-amber-700">Customer request note</p>
                                <p class="mt-1 line-clamp-4 whitespace-pre-line text-xs font-semibold leading-5 text-amber-900">{{ $requestNotes['notes'] ?: $requestNotes['converted_note_body'] }}</p>
                                <button type="button" @click="tab='notes'; window.scrollTo({top: 0, behavior: 'smooth'});" class="mt-2 text-xs font-black text-amber-800 hover:text-amber-950">View notes →</button>
                            </div>
                        @endif
                        @if (!empty($requestNotes['order_request_id']) && isset($requestAttachments) && $requestAttachments->isNotEmpty())
                            <div class="mt-4 rounded-2xl border border-indigo-200 bg-indigo-50 p-3">
                                <p class="text-[10px] font-black uppercase tracking-widest text-indigo-700">Customer attachments</p>
                                <div class="mt-2 space-y-2">
                                    @foreach ($requestAttachments as $attachment)
                                        <a
                                            href="{{ route('order-requests.attachments.show', [$requestNotes['order_request_id'], $attachment->id]) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="flex items-center justify-between gap-2 rounded-xl bg-white px-3 py-2 text-xs font-black text-slate-700 ring-1 ring-indigo-100 hover:bg-indigo-50"
                                        >
                                            <span class="min-w-0 truncate">{{ $attachment->original_name ?? 'Attachment' }}</span>
                                            <span class="shrink-0 text-indigo-700">↗</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-black text-slate-950">Draft settings</h2><button
                            type="button"
                            disabled
                            class="rounded-xl border border-slate-200 px-2.5 py-1 text-[11px] font-black text-slate-400"
                        >Edit</button>
                    </div>
                    <form
                        method="POST"
                        action="{{ route('draft-orders.update', $draft->id) }}"
                        data-allow-cancelled-submit="1"
                        class="mt-3 space-y-2.5"
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
                            class="w-full rounded-2xl bg-slate-950 px-3 py-2.5 text-xs font-black text-white hover:bg-slate-800"
                        >Save draft</button>
                    </form>
                </section>


            </aside>
        </div>

        {{-- Finalise / new version modal --}}
        <div x-show="finaliseModal.open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4" @keydown.escape.window="finaliseModal.open=false">
            <div @click.outside="finaliseModal.open=false" class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
                <div class="flex items-start gap-4">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl" :class="hasChildOrder ? 'bg-amber-50 text-amber-700' : 'bg-purple-50 text-purple-700'">➜</div>
                    <div class="min-w-0">
                        <h2 class="text-xl font-black text-slate-950" x-text="hasChildOrder ? 'Create new order version?' : 'Finalise draft into order?'"></h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600" x-show="!hasChildOrder">
                            This will consume the draft and create a new immutable order snapshot from the current basket, customer and fee details.
                        </p>
                        <p class="mt-2 text-sm leading-6 text-slate-600" x-show="hasChildOrder">
                            This draft has already created <strong>{{ $finalizedOrderLabel ?: 'an order' }}</strong>. Creating a new version will supersede the previous active order and create a new immutable order snapshot.
                        </p>
                        @if ($isCustomerSelfPurchase)
                            <div class="mt-4 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm font-semibold leading-6 text-sky-900">
                                <p class="font-black text-sky-950">Customer Self Purchase confirmation</p>
                                <p class="mt-1">Dabba will not purchase these items. Only service, shipping and handling charges will be invoiced. Goods values remain for reference, arrivals and customs documentation.</p>
                            </div>
                        @endif
                    </div>
                </div>
                <form method="POST" action="{{ route('draft-orders.finalise', $draft->id) }}" data-allow-consumed-submit="1" class="mt-6 flex justify-end gap-3">
                    @csrf
                    @if ($hasChildOrder)
                        <input type="hidden" name="confirm_new_version" value="1">
                    @endif
                    <button type="button" @click="finaliseModal.open=false" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-2xl bg-purple-600 px-5 py-3 text-sm font-black text-white hover:bg-purple-700" x-text="hasChildOrder ? 'Create New Version' : 'Create Order'"></button>
                </form>
            </div>
        </div>

        {{-- Consumed draft edit warning modal --}}
        <div x-show="consumedEditModal.open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4" @keydown.escape.window="consumedEditModal.open=false">
            <div @click.outside="consumedEditModal.open=false" class="w-full max-w-lg rounded-3xl border border-amber-200 bg-white p-6 shadow-2xl">
                <div class="flex items-start gap-4">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-amber-50 text-amber-700">⚠</div>
                    <div class="min-w-0">
                        <h2 class="text-xl font-black text-slate-950">Edit consumed draft?</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            This draft already created {{ $finalizedOrderLabel ?: 'an order' }}. Any changes you make now are for a future order version. The existing child order remains unchanged.
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="consumedEditModal.open=false" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="button" @click="confirmConsumedEdit()" class="rounded-2xl bg-amber-600 px-5 py-3 text-sm font-black text-white hover:bg-amber-700">Continue Editing</button>
                </div>
            </div>
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
        window.dabbaCancelledDraft = @js($isCancelledDraft);
        window.dabbaConsumedDraft = @js($isConsumedDraft);
        window.dabbaConsumedDraftEditAcknowledged = false;

        window.dabbaDraftAutosave = async function(form) {
            const debugPayload = {};

            if (window.dabbaCancelledDraft) {
                return { ok: false, message: 'This draft is cancelled and locked. Reopen it before making changes.' };
            }

            if (window.dabbaConsumedDraft && !window.dabbaConsumedDraftEditAcknowledged) {
                if (form instanceof HTMLFormElement) form.dataset.autosaveRetry = '1';
                window.dispatchEvent(new CustomEvent('consumed-draft-edit-attempt', { detail: { form } }));
                return { ok: false, message: 'Confirm editing this consumed draft first.' };
            }

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

                if (window.dabbaConsumedDraftEditAcknowledged) {
                    data.append('confirm_consumed_edit', '1');
                    debugPayload.confirm_consumed_edit = '1';
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

                if (payload.requires_consumed_edit_confirmation) {
                    if (form instanceof HTMLFormElement) form.dataset.autosaveRetry = '1';
                    window.dispatchEvent(new CustomEvent('consumed-draft-edit-attempt', { detail: { form } }));
                    return { ok: false, message: payload.message || 'Confirm editing this consumed draft first.' };
                }

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

        window.addEventListener('draft-toast', function(event) {
            const detail = event.detail || {};
            const root = document.querySelector('[x-data^="draftWorkspace"]');
            if (root && root.__x && typeof root.__x.$data?.showToast === 'function') {
                root.__x.$data.showToast(detail.title || 'Draft is locked', detail.message || 'This draft cannot be changed.');
            } else {
                alert((detail.title || 'Draft is locked') + '\n' + (detail.message || 'This draft cannot be changed.'));
            }
        });

        document.addEventListener('submit', function(event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (window.dabbaCancelledDraft && form.dataset.allowCancelledSubmit !== '1') {
                event.preventDefault();
                window.dispatchEvent(new CustomEvent('draft-toast', { detail: { title: 'Draft is cancelled', message: 'Reopen the draft before making changes.' } }));
                return;
            }
            if (!window.dabbaConsumedDraft || window.dabbaConsumedDraftEditAcknowledged) return;
            if (form.dataset.allowConsumedSubmit === '1') return;
            if (form.action && form.action.includes('/finalise')) return;

            event.preventDefault();
            window.dispatchEvent(new CustomEvent('consumed-draft-edit-attempt', { detail: { form } }));
        }, true);

        function draftWorkspace(config) {
            return {
                tab: config.initialTab || 'products',
                totals: config.totals || { itemsSubtotal: 0, retailerDelivery: 0, dabbaFee: 0, grandTotal: 0 },
                money(value) { return '£' + Number(value || 0).toFixed(2); },
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
                finaliseModal: {
                    open: false
                },
                consumedEditModal: {
                    open: false,
                    pendingForm: null,
                    pendingDelete: false
                },
                isConsumedDraft: !!config.isConsumedDraft,
                isCancelledDraft: !!config.isCancelledDraft,
                hasChildOrder: !!config.hasChildOrder,
                isCustomerSelfPurchase: !!config.isCustomerSelfPurchase,

                boot() {
                    window.addEventListener('draft-totals-repriced', (event) => {
                        const detail = event.detail || {};
                        const billableGoodsDelta = this.isCustomerSelfPurchase ? 0 : Number(detail.goodsDelta || 0);
                        this.totals.itemsSubtotal += billableGoodsDelta;
                        this.totals.retailerDelivery += Number(detail.deliveryDelta || 0);
                        this.totals.dabbaFee += Number(detail.feeDelta || 0);
                        this.totals.grandTotal += billableGoodsDelta + Number(detail.deliveryDelta || 0) + Number(detail.feeDelta || 0);
                    });

                    const justAdded = document.querySelector('[id^="item-"].bg-purple-50\/70');
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

                openFinaliseModal() {
                    if (this.isCancelledDraft) {
                        this.showToast('Draft is cancelled', 'Reopen the draft before finalising it.');
                        return;
                    }
                    this.finaliseModal.open = true;
                },

                openConsumedEditModal(form) {
                    this.consumedEditModal.pendingForm = form || null;
                    this.consumedEditModal.open = true;
                },

                confirmConsumedEdit() {
                    window.dabbaConsumedDraftEditAcknowledged = true;
                    this.consumedEditModal.open = false;
                    const form = this.consumedEditModal.pendingForm;
                    const shouldDelete = this.consumedEditModal.pendingDelete;
                    this.consumedEditModal.pendingForm = null;
                    this.consumedEditModal.pendingDelete = false;
                    if (shouldDelete) {
                        this.confirmDeleteItem();
                        return;
                    }
                    if (form instanceof HTMLFormElement) {
                        if (form.dataset.autosaveRetry === '1') {
                            window.dabbaDraftAutosave(form).then((result) => {
                                this.showToast(result.ok ? 'Draft change saved' : 'Could not save', result.message || 'Please try again.');
                                if (result.ok && result.reload) setTimeout(() => window.location.reload(), 500);
                            });
                        } else {
                            let input = form.querySelector('input[name="confirm_consumed_edit"]');
                            if (!input) {
                                input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'confirm_consumed_edit';
                                form.appendChild(input);
                            }
                            input.value = '1';
                            form.submit();
                        }
                    }
                },

                async confirmDeleteItem() {
                    if (this.isConsumedDraft && !window.dabbaConsumedDraftEditAcknowledged) {
                        this.consumedEditModal.pendingDelete = true;
                        this.consumedEditModal.open = true;
                        return;
                    }

                    let url = this.deleteModal.url;
                    const title = this.deleteModal.title;
                    if (window.dabbaConsumedDraftEditAcknowledged) {
                        const deleteUrl = new URL(url, window.location.origin);
                        deleteUrl.searchParams.set('confirm_consumed_edit', '1');
                        url = deleteUrl.toString();
                    }
                    this.deleteModal.open = false;
                    try {
                        const response = await fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': config.csrf,
                                'X-Confirm-Consumed-Edit': window.dabbaConsumedDraftEditAcknowledged ? '1' : '0',
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
                                'X-Confirm-Consumed-Edit': window.dabbaConsumedDraftEditAcknowledged ? '1' : '0',
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

                        const retailerId = retailer.retailer_id || retailer.retailerId || null;
                        const requiresManualReview = retailer.requires_manual_review || retailer.requiresManualReview || false;

                        if (retailerId && !requiresManualReview) {
                            const id = retailerId;
                            this.newItem.retailerId = String(id);
                            this.detectedRetailerId = id;
                            this.detectMessage = 'Retailer detected: ' + (retailer.name || 'matched') + ((retailer
                                .final_url || retailer.finalUrl) ? ' · URL expanded/cleaned' : '');
                            return;
                        }

                        const host = retailer.host || retailer.final_host || retailer.finalHost || this.cleanHost(this.newItem.url);
                        this.retailerModal.baseUrl = host;
                        this.retailerModal.name = retailer.name || this.guessRetailerName(host);
                        this.retailerModal.error = '';
                        this.retailerModal.open = true;
                        this.detectMessage = 'Retailer not recognised: ' + (host || 'unknown retailer') + '. Add it once to continue.';
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
