<x-app-layout>
    <x-slot name="header">Admin · Global Fees</x-slot>

    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-950">Global Dabba fee</h1>
                    <p class="mt-1 text-sm text-slate-500">Admin-only. New drafts use this unless the customer has a custom fee or disabled fee.</p>
                </div>
                @if ($activeFee)
                    <div class="rounded-2xl bg-purple-50 px-5 py-4 text-right">
                        <p class="text-xs font-black uppercase tracking-widest text-purple-500">Current active</p>
                        <p class="mt-1 text-xl font-black text-purple-800">{{ number_format(((float) $activeFee->dabba_fee_rate) * 100, 2) }}% · £{{ number_format((float) $activeFee->dabba_fee_min, 2) }} min</p>
                    </div>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.fees.store') }}" class="mt-6 grid gap-4 md:grid-cols-[160px_160px_auto] md:items-end">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-black uppercase tracking-widest text-slate-500">Rate %</label>
                    <input name="dabba_fee_rate" type="number" step="0.01" min="0" max="100" value="{{ old('dabba_fee_rate', $activeFee ? number_format(((float) $activeFee->dabba_fee_rate) * 100, 2, '.', '') : '20.00') }}" class="w-full rounded-2xl border-slate-300 text-sm focus:border-purple-500 focus:ring-purple-500">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-black uppercase tracking-widest text-slate-500">Minimum £</label>
                    <input name="dabba_fee_min" type="number" step="0.01" min="0" value="{{ old('dabba_fee_min', $activeFee ? number_format((float) $activeFee->dabba_fee_min, 2, '.', '') : '10.00') }}" class="w-full rounded-2xl border-slate-300 text-sm focus:border-purple-500 focus:ring-purple-500">
                </div>
                <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white hover:bg-slate-800">Create new active fee</button>
            </form>

            @error('dabba_fee_rate')<p class="mt-3 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror
            @error('dabba_fee_min')<p class="mt-3 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror
        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-lg font-black text-slate-950">Fee history</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-xs font-black uppercase tracking-widest text-slate-500">
                        <tr><th class="px-6 py-3 text-left">ID</th><th class="px-6 py-3 text-left">Rate</th><th class="px-6 py-3 text-left">Minimum</th><th class="px-6 py-3 text-left">Status</th><th class="px-6 py-3 text-left">Created</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($fees as $fee)
                            <tr>
                                <td class="px-6 py-4 font-bold text-slate-900">#{{ $fee->id }}</td>
                                <td class="px-6 py-4">{{ number_format(((float) $fee->dabba_fee_rate) * 100, 2) }}%</td>
                                <td class="px-6 py-4">£{{ number_format((float) $fee->dabba_fee_min, 2) }}</td>
                                <td class="px-6 py-4">@if($fee->is_active)<span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">Active</span>@else<span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-500">Inactive</span>@endif</td>
                                <td class="px-6 py-4 text-slate-500">{{ $fee->created_at }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
