<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{
        sidebarOpen: false,
        orderRequestCount: 0,

        async refreshOrderRequestCount() {
            try {
                const response = await fetch('{{ route('order-requests.counter') }}', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data && data.ok) {
                    this.orderRequestCount = data.count || 0;
                }
            } catch (e) {
                // silent
            }
        }
    }"
    x-init="
        refreshOrderRequestCount();
        setInterval(() => refreshOrderRequestCount(), 30000);
    "
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DabbaDesk</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-100 text-slate-800 antialiased">

<div class="min-h-screen">

    <!-- Mobile overlay -->
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"
        @click="sidebarOpen = false"
    ></div>

    <!-- Sidebar -->
    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-64 transform flex-col bg-slate-950 text-slate-200 transition-transform duration-300 lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="flex h-14 shrink-0 items-center justify-between border-b border-slate-800 px-4">
            <div>
                <h1 class="text-lg font-black tracking-tight text-white">
                    DabbaDesk
                </h1>

                <p class="text-[11px] font-semibold text-slate-400">
                    Dabba Direct CMS
                </p>
            </div>

            <button
                type="button"
                class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden"
                @click="sidebarOpen = false"
            >
                ✕
            </button>
        </div>

        <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto overflow-x-visible px-2.5 py-3 pr-3 scroll-smooth [scrollbar-width:thin] [scrollbar-color:#475569_transparent]">

            @php
                $navItems = [
                    ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => '🏠'],
                    ['label' => 'Orders', 'route' => 'orders.index', 'icon' => '📦'],
                    ['label' => 'Order Requests', 'route' => 'order-requests.index', 'icon' => '📝', 'counter' => true],
                    ['label' => 'Draft Orders', 'route' => 'draft-orders.index', 'icon' => '🧩'],
                    ['label' => 'Customer Desk', 'route' => 'customers.index', 'icon' => '👥'],
                    ['label' => 'Money Desk', 'route' => 'money-desk.index', 'icon' => '💷'],
                    ['label' => 'Purchasing Desk', 'route' => 'purchasing.index', 'icon' => '🛒'],
                    ['label' => 'Marking', 'route' => null, 'icon' => '🏷️'],
                    ['label' => 'Collection', 'route' => null, 'icon' => '🚚'],
                    ['label' => 'Comms Desk', 'route' => null, 'icon' => '✉️'],
                    ['label' => 'Admin Fees', 'route' => 'admin.fees.index', 'icon' => '⚙️', 'admin' => true],
                    ['label' => 'Audit Desk', 'route' => null, 'icon' => '🧾'],
                ];
            @endphp

            @foreach ($navItems as $item)
                @if (!empty($item['admin']) && Auth::user()->role !== 'admin')
                    @continue
                @endif

                @php
                    $isActive = $item['route'] && request()->routeIs($item['route'], str_replace('.index', '.*', $item['route']));
                    $href = $item['route'] ? route($item['route']) : '#';
                @endphp

                <a
                    href="{{ $href }}"
                    class="group flex min-w-0 items-center gap-2.5 overflow-visible rounded-xl px-3 py-2 pr-3.5 text-[13px] font-semibold leading-tight transition
                    {{
                        $isActive
                            ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-950/30'
                            : 'text-slate-300 hover:bg-slate-800 hover:text-white'
                    }}"
                >
                    <span class="w-6 shrink-0 text-center text-base leading-none">
                        {{ $item['icon'] }}
                    </span>

                    <span class="min-w-0 flex-1 truncate">
                        {{ $item['label'] }}
                    </span>

                    @if (!empty($item['counter']))
                        <template x-if="orderRequestCount > 0">
                            <span
                                x-text="orderRequestCount"
                                class="ml-1 inline-flex h-4 min-w-4 shrink-0 items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-black leading-none text-white ring-2 ring-indigo-600/20"
                            ></span>
                        </template>
                    @endif

                    @unless($item['route'])
                        <span class="ml-1 inline-flex shrink-0 items-center rounded-full bg-slate-800/80 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wide text-slate-400 group-hover:bg-slate-700/80 group-hover:text-slate-300">
                            soon
                        </span>
                    @endunless

                </a>

            @endforeach

        </nav>

        <div class="shrink-0 border-t border-slate-800 p-3">

            <div class="mb-2 rounded-xl bg-slate-900 px-3 py-2">
                <p class="truncate text-xs font-black text-white">
                    {{ Auth::user()->name }}
                </p>

                <p class="text-[10px] font-semibold text-slate-400">
                    Staff account
                </p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="w-full rounded-xl bg-rose-500 px-3 py-2 text-xs font-black text-white transition hover:bg-rose-600"
                >
                    Logout
                </button>
            </form>

        </div>

    </aside>

    <!-- Main wrapper -->
    <div class="lg:pl-64">

        <!-- Topbar -->
        <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/90 px-4 shadow-sm backdrop-blur sm:px-5">

            <div class="flex min-w-0 items-center gap-3">

                <button
                    type="button"
                    class="rounded-xl border border-slate-200 bg-white p-2 text-slate-600 shadow-sm lg:hidden"
                    @click="sidebarOpen = true"
                >
                    ☰
                </button>

                <div class="min-w-0">
                    <h2 class="truncate text-lg font-black text-slate-900 sm:text-xl">
                        {{ $header ?? 'Dashboard' }}
                    </h2>

                    <p class="hidden text-xs font-semibold text-slate-500 sm:block">
                        DabbaDesk operations
                    </p>
                </div>

            </div>

            <div class="flex items-center gap-3">

                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold text-slate-700">
                        {{ Auth::user()->name }}
                    </p>

                    <p class="text-xs text-slate-400">
                        Logged in
                    </p>
                </div>

                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-sm font-black text-white shadow-lg shadow-indigo-200">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>

            </div>

        </header>

        <!-- Page content -->
        <main class="p-3 sm:p-5 lg:p-6">
            {{ $slot }}
        </main>

    </div>

</div>

</body>
</html>