        @if (($revisionHistory ?? collect())->count() > 1)
            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Revision history</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">Order #{{ $order->order_number }} has {{ ($revisionHistory ?? collect())->count() }} saved revision snapshots</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Audit trail</span>
                </div>

                <div class="mt-5 space-y-3">
                    @foreach ($revisionHistory as $revision)
                        @php
                            $isCurrentRevision = (int) $revision->id === (int) $order->id;
                            $isSupersededRevision = ($revision->revision_state ?? '') === 'superseded';
                        @endphp
                        <div class="flex flex-col gap-3 rounded-2xl border {{ $isCurrentRevision ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50' }} px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-black text-slate-950">Rev {{ $revision->revision_number }} of {{ $revision->revision_total }}</p>
                                    @if ($isCurrentRevision)
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">Viewing now</span>
                                    @elseif ($isSupersededRevision)
                                        <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-black text-rose-700">Superseded</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Current</span>
                                    @endif
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600">{{ str_replace('_', ' ', $revision->status) }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    Created {{ $revision->created_at ? \Carbon\Carbon::parse($revision->created_at)->format('d M Y H:i') : 'date unknown' }} · Total £{{ number_format($revision->grand_total ?? 0, 2) }}
                                </p>
                                @if (! empty($revision->revision_note))
                                    <p class="mt-1 text-xs text-slate-500 line-clamp-2">{{ $revision->revision_note }}</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if (! $isCurrentRevision)
                                    <a href="{{ route('orders.show', $revision->id) }}" class="rounded-2xl bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-indigo-50 hover:text-indigo-700">View snapshot</a>
                                @endif
                                @if (! empty($revision->draft_order_id))
                                    <a href="{{ route('draft-orders.show', $revision->draft_order_id) }}" class="rounded-2xl bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-slate-100">Open Draft</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
