        <div x-show="tab === 'notes'" x-cloak class="space-y-5">
            <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Order notes</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">Operational timeline</h2>
                        <p class="mt-1 text-sm text-slate-500">Use this for order-level staff notes after the order snapshot has been created.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $userOrderNotes->count() }} note{{ $userOrderNotes->count() === 1 ? '' : 's' }}</span>
                </div>

                <form method="POST" action="{{ route('orders.notes.store', $order->id) }}" class="mt-4 rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                    @csrf
                    <label class="text-[11px] font-black uppercase tracking-wide text-indigo-800">Add order note</label>
                    <textarea name="body" rows="3" required minlength="2" maxlength="5000" placeholder="Supplier update, customer call, internal instruction…" class="mt-1 w-full rounded-2xl border border-indigo-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('body') }}</textarea>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <label class="flex cursor-pointer items-center gap-2 text-xs font-bold text-indigo-900">
                            <input type="checkbox" name="is_pinned" value="1" class="rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500">
                            Pin this note
                        </label>
                        <button type="submit" class="rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-indigo-700">Add note</button>
                    </div>
                </form>

                <div class="mt-5 space-y-3">
                    @forelse ($userOrderNotes->take(12) as $note)
                        <div class="rounded-2xl border {{ $note->is_pinned ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white' }} p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($note->is_pinned)
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700">pinned</span>
                                @endif
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ str_replace('_', ' ', $note->type) }}</span>
                                @if ($note->title)
                                    <span class="text-sm font-black text-slate-900">{{ $note->title }}</span>
                                @endif
                            </div>

                            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $note->body }}</p>

                            <p class="mt-3 text-xs font-semibold text-slate-400">
                                {{ ($note->occurred_at ?: $note->created_at) ? \Carbon\Carbon::parse($note->occurred_at ?: $note->created_at)->format('d M Y H:i') : 'No date' }}
                            </p>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                            No order notes yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
