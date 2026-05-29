<x-app-layout>
    <x-slot name="header">Customer #{{ $customer->id }}</x-slot>

    @php
        $name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: ($customer->company_name ?: 'Unknown customer');
        $address = $details['address'] ?? null;
        $formattedAddress = $address
            ? collect([$address->line1, $address->line2, $address->city, $address->region, $address->postcode, $address->country_name])->filter()->implode("\n")
            : '';
    @endphp

    <div class="space-y-5">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <a href="{{ route('customers.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-900">← Back to customers</a>
                    <h1 class="mt-3 text-3xl font-black text-slate-950">{{ $name }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $details['email'] ?: 'No email' }} · {{ $details['phone'] ?: 'No phone' }}</p>
                </div>
                <a href="{{ route('customers.edit', $customer->id) }}" class="rounded-2xl bg-purple-600 px-5 py-3 text-sm font-black text-white hover:bg-purple-700">Edit customer</a>
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
            <div class="space-y-5">
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black text-slate-950">Details</h2>
                    <dl class="mt-5 grid gap-4 md:grid-cols-2 text-sm">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-widest text-slate-500">Type</dt>
                            <dd class="mt-1 font-bold text-slate-900">{{ ucfirst($customer->customer_type ?: 'individual') }}</dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-widest text-slate-500">Reference</dt>
                            <dd class="mt-1 font-bold text-slate-900">{{ $customer->reference ?: '—' }}</dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 md:col-span-2">
                            <dt class="text-xs font-black uppercase tracking-widest text-slate-500">Address</dt>
                            <dd class="mt-1 whitespace-pre-line font-bold text-slate-900">{{ $formattedAddress ?: '—' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-black text-slate-950">Customer notes</h2>
                            <p class="mt-1 text-sm text-slate-500">General customer notes stay with the customer and are useful across future drafts and orders.</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ ($notes ?? collect())->count() }} notes</span>
                    </div>

                    <form method="POST" action="{{ route('customers.notes.store', $customer->id) }}" class="mt-5 rounded-3xl bg-slate-50 p-4">
                        @csrf
                        <label class="mb-2 block text-xs font-black uppercase tracking-widest text-slate-500">Add note</label>
                        <textarea name="body" rows="3" required minlength="2" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-purple-500 focus:ring-4 focus:ring-purple-100" placeholder="Example: prefers WhatsApp, call after 4pm, delivery preference, special fee context..."></textarea>
                        @error('body')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                        <div class="mt-3 flex justify-end">
                            <button class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white hover:bg-slate-800">Save note</button>
                        </div>
                    </form>

                    <div class="mt-5 space-y-3">
                        @forelse (($notes ?? collect()) as $note)
                            <article class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="text-sm font-black text-slate-950">{{ $note->title ?: 'Customer note' }}</h3>
                                    <p class="text-xs font-semibold text-slate-400">
                                        {{ $note->author_name ?: 'System' }} · {{ $note->created_at ? \Carbon\Carbon::parse($note->created_at)->format('d M Y H:i') : '' }}
                                    </p>
                                </div>
                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $note->body }}</p>
                            </article>
                        @empty
                            <p class="rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-500">No customer notes yet.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="space-y-5">
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black text-slate-950">Effective fee</h2>
                    <div class="mt-5 rounded-2xl bg-purple-50 p-4">
                        <p class="text-xs font-black uppercase tracking-widest text-purple-500">{{ $effectiveFee['source'] }}</p>
                        <p class="mt-1 text-2xl font-black text-purple-800">{{ number_format(($effectiveFee['percentage_rate'] ?? 0) * 100, 2) }}%</p>
                        <p class="text-sm font-bold text-purple-700">Minimum £{{ number_format($effectiveFee['minimum_fee'] ?? 0, 2) }}</p>
                    </div>
                    <p class="mt-4 text-xs font-semibold leading-5 text-slate-500">This is what new draft orders will use for this customer.</p>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
