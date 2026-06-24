<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-indigo-600">Admin · Data Quality</p>
                <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">Legacy Text Inspector</h2>
                <p class="mt-1 max-w-3xl text-sm text-slate-600">
                    Find likely legacy character encoding problems and manually correct one database record at a time.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-5">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="rounded-3xl border border-amber-200 bg-amber-50/80 p-4 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-sm font-black text-amber-950">Admin-only inspection tool</h3>
                    <p class="mt-1 text-sm font-medium text-amber-900">
                        This tool only lists fields containing suspicious character sequences. Helper buttons only edit the correction box. Nothing is saved until you click Save Correction.
                    </p>
                </div>
                <div class="flex max-w-3xl flex-wrap gap-1.5 text-xs font-black text-amber-900">
                    @foreach ($badNeedles as $needle)
                        <span class="rounded-full bg-white/70 px-2 py-1 ring-1 ring-amber-200">{{ $needle }}</span>
                    @endforeach
                </div>
            </div>
        </section>

        <form method="GET" action="{{ route('admin.text-cleanup.index') }}" class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 lg:grid-cols-[minmax(260px,420px)_1fr_auto] lg:items-end">
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-wide text-slate-500">Scan area</span>
                    <select name="target" class="mt-1 w-full rounded-2xl border-slate-300 text-sm font-semibold shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All approved text fields</option>
                        @foreach ($targets as $key => $target)
                            <option value="{{ $key }}" @selected($selectedTarget === $key)>
                                {{ $target['group'] }} · {{ $target['label'] }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-black uppercase tracking-wide text-slate-500">Optional search</span>
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="e.g. Nelson, Take That, 360"
                        class="mt-1 w-full rounded-2xl border-slate-300 text-sm font-semibold shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </label>

                <button class="rounded-2xl bg-indigo-600 px-5 py-2.5 text-sm font-black text-white shadow-sm hover:bg-indigo-700">
                    Scan
                </button>
            </div>
        </form>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h3 class="text-base font-black text-slate-950">Suspicious text only</h3>
                <p class="mt-0.5 text-sm font-semibold text-slate-500">{{ count($results) }} result{{ count($results) === 1 ? '' : 's' }} shown</p>
            </div>

            @if (empty($results))
                <div class="p-8 text-center">
                    <p class="text-lg font-black text-slate-900">No suspicious text found.</p>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Try another scan area or search term.</p>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($results as $result)
                        <article class="p-4">
                            <div class="mb-3 flex flex-col gap-1 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-indigo-700 ring-1 ring-indigo-100">
                                            {{ $result['group'] }}
                                        </span>
                                        <span class="text-sm font-black text-slate-950">
                                            {{ $result['label'] }} · {{ $result['table'] }} #{{ $result['id'] }}.{{ $result['field'] }}
                                        </span>
                                    </div>
                                    @if ($result['context'])
                                        <p class="mt-1 text-xs font-bold text-slate-500">{{ $result['context'] }}</p>
                                    @endif
                                </div>

                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-600 ring-1 ring-slate-200">Manual review</span>
                            </div>

                            <form method="POST" action="{{ route('admin.text-cleanup.update') }}" class="grid gap-3 lg:grid-cols-2">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="table" value="{{ $result['table'] }}">
                                <input type="hidden" name="field" value="{{ $result['field'] }}">
                                <input type="hidden" name="record_id" value="{{ $result['id'] }}">
                                <input type="hidden" name="original" value="{{ $result['current'] }}">

                                <div>
                                    <label class="text-xs font-black uppercase tracking-wide text-slate-500">Current text</label>
                                    <div class="mt-1 min-h-28 whitespace-pre-wrap rounded-2xl border border-rose-100 bg-rose-50 p-3 text-sm font-semibold leading-6 text-rose-950">{{ $result['current'] }}</div>
                                </div>

                                <div>
                                    <label class="text-xs font-black uppercase tracking-wide text-slate-500">Corrected text</label>
                                    <textarea
                                        name="replacement"
                                        rows="4"
                                        class="mt-1 min-h-28 w-full rounded-2xl border-indigo-200 bg-indigo-50/30 text-sm font-semibold leading-6 text-slate-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >{{ $result['current'] }}</textarea>

                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        <button
                                            type="button"
                                            class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black text-slate-700 shadow-sm hover:bg-slate-50"
                                            onclick="legacyTextDecode(this)"
                                        >
                                            Decode mojibake
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black text-slate-700 shadow-sm hover:bg-slate-50"
                                            onclick="legacyTextReplace(this, {'Ãƒâ€šÃ‚°': '°', 'Ã‚°': '°', 'Â°': '°'})"
                                        >
                                            Degree symbol
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black text-slate-700 shadow-sm hover:bg-slate-50"
                                            onclick="legacyTextReplace(this, {'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢': '\'', 'Ã¢â‚¬â„¢': '\'', 'â€™': '\'', 'Â´': '\''})"
                                        >
                                            Apostrophe
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black text-slate-700 shadow-sm hover:bg-slate-50"
                                            onclick="legacyTextReplace(this, {'Ã‚Â£': '£', 'Â£': '£'})"
                                        >
                                            Pound sign
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black text-slate-700 shadow-sm hover:bg-slate-50"
                                            onclick="legacyTextReplace(this, {'Ã¢â‚¬â€œ': '-', 'â€“': '-', 'â€”': '-'})"
                                        >
                                            Dash
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black text-slate-700 shadow-sm hover:bg-slate-50"
                                            onclick="legacyTextReplace(this, {'Ã¢â‚¬Å“': '&quot;', 'Ã¢â‚¬Â': '&quot;', 'â€œ': '&quot;', 'â€': '&quot;'})"
                                        >
                                            Quotes
                                        </button>
                                    </div>

                                    <p class="mt-2 text-xs font-semibold text-slate-500">
                                        Helper buttons only change the correction box. Review the text before saving.
                                    </p>

                                    <div class="mt-3 flex justify-end">
                                        <button
                                            type="submit"
                                            data-confirm-click data-confirm-title="Save correction?" data-confirm-message="This will update {{ $result['table'] }} #{{ $result['id'] }} with the corrected text." data-confirm-button="Save Correction" data-confirm-cancel="Keep Editing"
                                            class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-black text-white shadow-sm hover:bg-indigo-700"
                                        >
                                            Save Correction
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <script>
        function legacyTextGetTextarea(button) {
            const form = button.closest('form');
            return form ? form.querySelector('textarea[name="replacement"]') : null;
        }

        function legacyTextCp1252Byte(char) {
            const map = {
                '€': 0x80, '‚': 0x82, 'ƒ': 0x83, '„': 0x84, '…': 0x85, '†': 0x86, '‡': 0x87,
                'ˆ': 0x88, '‰': 0x89, 'Š': 0x8A, '‹': 0x8B, 'Œ': 0x8C, 'Ž': 0x8E,
                '‘': 0x91, '’': 0x92, '“': 0x93, '”': 0x94, '•': 0x95, '–': 0x96, '—': 0x97,
                '˜': 0x98, '™': 0x99, 'š': 0x9A, '›': 0x9B, 'œ': 0x9C, 'ž': 0x9E, 'Ÿ': 0x9F
            };

            if (map[char] !== undefined) {
                return map[char];
            }

            const code = char.charCodeAt(0);

            return code <= 255 ? code : null;
        }

        function legacyTextDecodeMojibakeOnce(value) {
            const bytes = [];

            for (const char of value) {
                const byte = legacyTextCp1252Byte(char);

                if (byte === null) {
                    return value;
                }

                bytes.push(byte);
            }

            return new TextDecoder('utf-8', { fatal: false }).decode(new Uint8Array(bytes));
        }

        function legacyTextSuspiciousScore(value) {
            const matches = value.match(/Ã|Â|â|�|Æ|ƒ|‚|€|œ|„|¢|¤/g);

            return matches ? matches.length : 0;
        }

        function legacyTextDecode(button) {
            const textarea = legacyTextGetTextarea(button);

            if (!textarea) {
                return;
            }

            let value = textarea.value;
            let best = value;
            let bestScore = legacyTextSuspiciousScore(value);

            for (let i = 0; i < 6; i++) {
                const decoded = legacyTextDecodeMojibakeOnce(value);
                const score = legacyTextSuspiciousScore(decoded);

                if (score <= bestScore) {
                    best = decoded;
                    bestScore = score;
                }

                if (decoded === value || score === 0) {
                    break;
                }

                value = decoded;
            }

            textarea.value = best.replace(/[ \t]{2,}/g, ' ').trim();
            textarea.focus();
        }

        function legacyTextReplace(button, replacements) {
            const textarea = legacyTextGetTextarea(button);

            if (!textarea) {
                return;
            }

            let value = textarea.value;

            Object.entries(replacements).forEach(([bad, good]) => {
                value = value.split(bad).join(good);
            });

            textarea.value = value.replace(/[ \t]{2,}/g, ' ').trim();
            textarea.focus();
        }
    </script>
</x-app-layout>