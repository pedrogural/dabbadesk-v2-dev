<x-app-layout>

    <x-slot name="header">
        Dashboard
    </x-slot>

    <div class="space-y-6">

        <!-- Welcome -->
        <div class="rounded-3xl bg-gradient-to-r from-indigo-600 to-indigo-500 p-8 text-white shadow-xl">
            <h1 class="text-3xl font-bold">
                Welcome to DabbaDesk
            </h1>

            <p class="mt-2 text-indigo-100 max-w-2xl">
                Dabba Direct operations platform for order management,
                purchasing, finance, customer service and warehouse workflows.
            </p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">
                    Active Orders
                </p>

                <h3 class="mt-3 text-4xl font-bold text-slate-900">
                    0
                </h3>

                <p class="mt-2 text-sm text-slate-400">
                    Awaiting live integration
                </p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">
                    Ready to Purchase
                </p>

                <h3 class="mt-3 text-4xl font-bold text-amber-500">
                    0
                </h3>

                <p class="mt-2 text-sm text-slate-400">
                    Purchase queue
                </p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">
                    Awaiting Collection
                </p>

                <h3 class="mt-3 text-4xl font-bold text-emerald-500">
                    0
                </h3>

                <p class="mt-2 text-sm text-slate-400">
                    Customer pickups pending
                </p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500">
                    Helpdesk Alerts
                </p>

                <h3 class="mt-3 text-4xl font-bold text-rose-500">
                    0
                </h3>

                <p class="mt-2 text-sm text-slate-400">
                    Issues requiring attention
                </p>
            </div>

        </div>

        <!-- Quick Modules -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-lg font-semibold text-slate-800">
                    Operations
                </h2>

                <div class="mt-5 grid grid-cols-2 gap-4">

                    <button class="rounded-2xl border border-slate-200 p-5 text-left hover:border-indigo-400 hover:bg-indigo-50 transition">
                        <p class="font-semibold text-slate-800">
                            Order Desk
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Manage customer orders
                        </p>
                    </button>

                    <button class="rounded-2xl border border-slate-200 p-5 text-left hover:border-indigo-400 hover:bg-indigo-50 transition">
                        <p class="font-semibold text-slate-800">
                            Purchase Desk
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Supplier workflow
                        </p>
                    </button>

                    <button class="rounded-2xl border border-slate-200 p-5 text-left hover:border-indigo-400 hover:bg-indigo-50 transition">
                        <p class="font-semibold text-slate-800">
                            Marking
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Package processing
                        </p>
                    </button>

                    <button class="rounded-2xl border border-slate-200 p-5 text-left hover:border-indigo-400 hover:bg-indigo-50 transition">
                        <p class="font-semibold text-slate-800">
                            Collection
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Customer pickups
                        </p>
                    </button>

                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-lg font-semibold text-slate-800">
                    System Status
                </h2>

                <div class="mt-5 space-y-4">

                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                        <span class="text-sm font-medium text-slate-600">
                            Database
                        </span>

                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                            Connected
                        </span>
                    </div>

                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                        <span class="text-sm font-medium text-slate-600">
                            Queue System
                        </span>

                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                            Pending
                        </span>
                    </div>

                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                        <span class="text-sm font-medium text-slate-600">
                            DabbaDesk Version
                        </span>

                        <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                            v2 Preview
                        </span>
                    </div>

                </div>
            </div>

        </div>

    </div>

</x-app-layout>