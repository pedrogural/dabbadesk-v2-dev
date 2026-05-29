<x-app-layout>
    <x-slot name="header">Customer #{{ $customer->id }}</x-slot>

    @php $name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: ($customer->company_name ?: 'Unknown customer'); @endphp

    <div class="space-y-5">
        @if (session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>@endif
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div><a href="{{ route('customers.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-900">← Back to customers</a><h1 class="mt-3 text-3xl font-black text-slate-950">{{ $name }}</h1><p class="mt-1 text-sm text-slate-500">{{ $details['email'] ?: 'No email' }} · {{ $details['phone'] ?: 'No phone' }}</p></div>
                <a href="{{ route('customers.edit', $customer->id) }}" class="rounded-2xl bg-purple-600 px-5 py-3 text-sm font-black text-white hover:bg-purple-700">Edit customer</a>
            </div>
        </section>
        <div class="grid gap-5 lg:grid-cols-3">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2"><h2 class="text-xl font-black text-slate-950">Details</h2><dl class="mt-5 grid gap-4 md:grid-cols-2 text-sm"><div class="rounded-2xl bg-slate-50 p-4"><dt class="font-black uppercase tracking-widest text-slate-500 text-xs">Type</dt><dd class="mt-1 font-bold text-slate-900">{{ $customer->customer_type }}</dd></div><div class="rounded-2xl bg-slate-50 p-4"><dt class="font-black uppercase tracking-widest text-slate-500 text-xs">Reference</dt><dd class="mt-1 font-bold text-slate-900">{{ $customer->reference ?: '—' }}</dd></div><div class="rounded-2xl bg-slate-50 p-4 md:col-span-2"><dt class="font-black uppercase tracking-widest text-slate-500 text-xs">Address</dt><dd class="mt-1 whitespace-pre-line font-bold text-slate-900">@if($details['address']){{ collect([$details['address']->line1,$details['address']->line2,$details['address']->city,$details['address']->region,$details['address']->postcode,$details['address']->country_name])->filter()->implode("\n") }}@else — @endif</dd></div></dl></section>
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="text-xl font-black text-slate-950">Effective fee</h2><div class="mt-5 rounded-2xl bg-purple-50 p-4"><p class="text-xs font-black uppercase tracking-widest text-purple-500">{{ $effectiveFee['source'] }}</p><p class="mt-1 text-2xl font-black text-purple-800">{{ number_format(($effectiveFee['percentage_rate'] ?? 0) * 100, 2) }}%</p><p class="text-sm font-bold text-purple-700">Minimum £{{ number_format($effectiveFee['minimum_fee'] ?? 0, 2) }}</p></div><p class="mt-4 text-xs font-semibold leading-5 text-slate-500">This is what new draft orders will use for this customer.</p></section>
        </div>
    </div>
</x-app-layout>
