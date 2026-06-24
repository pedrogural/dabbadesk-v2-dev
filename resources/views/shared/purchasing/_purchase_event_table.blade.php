<div class="overflow-hidden rounded-2xl border border-slate-200">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-400">
            <tr>
                <th class="px-4 py-3">Item</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Qty</th>
                <th class="px-4 py-3">Reference</th>
                <th class="px-4 py-3">Dates</th>
                <th class="px-4 py-3">Notes</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
            @forelse ($purchaseEvents as $purchase)
                @php
                    $purchaseStatus = (string) ($purchase->status ?? '');
                    $isGood = in_array($purchaseStatus, ['purchased', 'ordered', 'received'], true);
                    $isProblem = in_array($purchaseStatus, ['failed', 'problem', 'supplier_cancelled', 'cancelled', 'unfulfilled', 'unavailable', 'lost', 'damaged', 'wrong_item'], true);
                @endphp
                <tr>
                    <td class="px-4 py-3">
                        <p class="font-black text-slate-900">{{ \Illuminate\Support\Str::limit($purchase->item_name ?? 'Item', 80) }}</p>
                        @if (! empty($purchase->marketplace_seller))
                            <p class="mt-1 text-xs font-semibold text-slate-500">Seller: {{ $purchase->marketplace_seller }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $isGood ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : ($isProblem ? 'bg-rose-50 text-rose-700 ring-rose-100' : 'bg-slate-50 text-slate-600 ring-slate-100') }}">
                            {{ \Illuminate\Support\Str::of($purchaseStatus ?: 'pending')->replace('_', ' ')->title() }}
                        </span>
                        @if (! empty($purchase->problem_code))
                            <p class="mt-1 text-xs font-bold text-rose-700">{{ \Illuminate\Support\Str::of($purchase->problem_code)->replace('_', ' ')->title() }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-black text-slate-700">{{ (int) ($purchase->qty ?? 0) }}</td>
                    <td class="px-4 py-3">
                        <p class="font-semibold text-slate-700">{{ $purchase->retailer_order_reference ?: '—' }}</p>
                        @if (! empty($purchase->retailer_order_reference))
                            <button type="button" data-copy-value="{{ $purchase->retailer_order_reference }}" class="mt-1 text-xs font-black text-indigo-600 hover:text-indigo-700">Copy</button>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs font-semibold text-slate-500">
                        <p>Ordered: {{ ! empty($purchase->ordered_at) ? \Carbon\Carbon::parse($purchase->ordered_at)->format('d M Y') : '—' }}</p>
                        <p>UK hub: {{ ! empty($purchase->expected_uk_hub_at) ? \Carbon\Carbon::parse($purchase->expected_uk_hub_at)->format('d M Y') : '—' }}</p>
                    </td>
                    <td class="px-4 py-3 text-xs font-semibold text-slate-500">
                        {{ \Illuminate\Support\Str::limit($purchase->problem_notes ?: ($purchase->internal_notes ?: ($purchase->note ?: '—')), 110) }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if (\Illuminate\Support\Facades\Route::has('purchasing.events.undo') && empty($purchase->cancelled_at))
                            <form method="POST" action="{{ route('purchasing.events.undo', $purchase->id) }}" data-confirm data-confirm-danger="1" data-confirm-title="Undo this purchasing event?" data-confirm-message="This will reverse the purchasing event if it has not been arrived or otherwise locked." data-confirm-button="Undo Purchase" data-confirm-cancel="Keep Purchase">
                                @csrf
                                <button class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-600 hover:bg-slate-50">Undo</button>
                            </form>
                        @else
                            <span class="text-xs font-semibold text-slate-400">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-sm font-semibold text-slate-500">No purchase events yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
