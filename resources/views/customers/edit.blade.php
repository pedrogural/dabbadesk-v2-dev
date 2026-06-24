<x-app-layout>
    <x-slot name="header">Edit customer #{{ $customer->id }}</x-slot>

    <form method="POST" action="{{ route('customers.update', $customer->id) }}">
        @csrf
        @method('PATCH')
        @include('customers._form')
    </form>

    <section class="mt-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-black text-slate-950">Customer notes</h2>
                <p class="mt-1 text-sm text-slate-500">General notes stay with this customer and should be visible throughout future drafts and orders.</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ ($notes ?? collect())->count() }} notes</span>
        </div>

        <form method="POST" action="{{ route('customers.notes.store', $customer->id) }}" class="mt-5 rounded-3xl bg-slate-50 p-4">
            @csrf
            <label class="mb-2 block text-xs font-black uppercase tracking-widest text-slate-500">Add customer note</label>
            <textarea
                name="body"
                rows="3"
                required
                minlength="2"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-purple-500 focus:ring-4 focus:ring-purple-100"
                placeholder="Example: prefers WhatsApp, call after 4pm, delivery preference, special fee context..."
            ></textarea>
            @error('body')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
            <div class="mt-3 flex justify-end">
                <button class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white hover:bg-indigo-700">Save note</button>
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
</x-app-layout>
