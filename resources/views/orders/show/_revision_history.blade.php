        @if (($revisionHistory ?? collect())->count() > 1)
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Revision history</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">Revision History ({{ ($revisionHistory ?? collect())->count() }})</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Audit trail</span>
                </div>

                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                    @foreach ($revisionHistory as $revision)
                        @php
                            $isCurrentRevision = (int) $revision->id === (int) $order->id;
                            $isSupersededRevision = ($revision->revision_state ?? '') === 'superseded';
                            $statusLabel = $isCurrentRevision ? 'Viewing now' : ($isSupersededRevision ? 'Superseded' : 'Current');
                            $statusClasses = $isCurrentRevision ? 'bg-emerald-100 text-emerald-700' : ($isSupersededRevision ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600');
                        @endphp
                        <div class="grid gap-3 border-b border-slate-100 px-4 py-3 last:border-b-0 md:grid-cols-[90px_130px_1fr_120px_auto] md:items-center">
                            <div class="font-black text-slate-950">Rev {{ $revision->revision_number }}</div>

                            <div>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $statusClasses }}">{{ $statusLabel }}</span>
                            </div>

                            <div class="text-sm font-semibold text-slate-600">
                                {{ $revision->created_at ? \Carbon\Carbon::parse($revision->created_at)->format('d M Y H:i') : 'Date unknown' }}
                                @if (! empty($revision->revision_note))
                                    <p class="mt-1 line-clamp-1 text-xs font-normal text-slate-400">{{ $revision->revision_note }}</p>
                                @endif
                            </div>

                            <div class="text-sm font-black text-slate-950 md:text-right">£{{ number_format($revision->grand_total ?? 0, 2) }}</div>

                            <div class="flex flex-wrap gap-2 md:justify-end">
                                @if (! $isCurrentRevision)
                                    <a href="{{ route('orders.show', $revision->id) }}" class="rounded-xl bg-white px-3 py-2 text-xs font-bold text-slate-700 ring-1 ring-slate-200 hover:bg-indigo-50 hover:text-indigo-700">Snapshot</a>
                                @endif
                                @if (! empty($revision->draft_order_id))
                                    <a href="{{ route('draft-orders.show', $revision->draft_order_id) }}" class="rounded-xl bg-white px-3 py-2 text-xs font-bold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100">View Draft</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
