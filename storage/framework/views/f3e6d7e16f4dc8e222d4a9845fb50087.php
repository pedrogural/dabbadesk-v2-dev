<!DOCTYPE html>
<html
    lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>"
    x-data="{
        sidebarOpen: false,
        orderRequestCount: 0,

        async refreshOrderRequestCount() {
            try {
                const response = await fetch('<?php echo e(route('order-requests.counter')); ?>', {
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

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
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
        class="fixed inset-y-0 left-0 z-50 w-72 transform bg-slate-950 text-slate-200 transition-transform duration-300 lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="flex h-20 items-center justify-between border-b border-slate-800 px-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white">
                    DabbaDesk
                </h1>

                <p class="mt-1 text-xs text-slate-400">
                    Dabba Direct CMS
                </p>
            </div>

            <button
                type="button"
                class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden"
                @click="sidebarOpen = false"
            >
                ✕
            </button>
        </div>

        <nav class="space-y-2 px-4 py-6">

            <?php
                $navItems = [
                    ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => '🏠'],
                    ['label' => 'Orders', 'route' => 'orders.index', 'icon' => '📦'],
                    ['label' => 'Order Requests', 'route' => 'order-requests.index', 'icon' => '📝', 'counter' => true],
                    ['label' => 'Draft Orders', 'route' => 'draft-orders.index', 'icon' => '🧩'],
                    ['label' => 'Customer Desk', 'route' => 'customers.index', 'icon' => '👥'],
                    ['label' => 'Money Desk', 'route' => 'money-desk.index', 'icon' => '💷'],
                    ['label' => 'Purchase Desk', 'route' => null, 'icon' => '🛒'],
                    ['label' => 'Marking', 'route' => null, 'icon' => '🏷️'],
                    ['label' => 'Collection', 'route' => null, 'icon' => '🚚'],
                    ['label' => 'Comms Desk', 'route' => null, 'icon' => '✉️'],
                    ['label' => 'Admin Fees', 'route' => 'admin.fees.index', 'icon' => '⚙️', 'admin' => true],
                    ['label' => 'Audit Desk', 'route' => null, 'icon' => '🧾'],
                ];
            ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $navItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($item['admin']) && Auth::user()->role !== 'admin'): ?>
                    <?php continue; ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php
                    $isActive = $item['route'] && request()->routeIs($item['route'], str_replace('.index', '.*', $item['route']));
                    $href = $item['route'] ? route($item['route']) : '#';
                ?>

                <a
                    href="<?php echo e($href); ?>"
                    class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition
                    <?php echo e($isActive
                            ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-950/30'
                            : 'text-slate-300 hover:bg-slate-800 hover:text-white'); ?>"
                >
                    <span class="text-lg">
                        <?php echo e($item['icon']); ?>

                    </span>

                    <span>
                        <?php echo e($item['label']); ?>

                    </span>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($item['counter'])): ?>
                        <template x-if="orderRequestCount > 0">
                            <span
                                x-text="orderRequestCount"
                                class="ml-auto rounded-full bg-rose-500 px-2 py-0.5 text-xs font-black text-white"
                            ></span>
                        </template>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (! ($item['route'])): ?>
                        <span class="ml-auto rounded-full bg-slate-800 px-2 py-0.5 text-[10px] text-slate-400 group-hover:bg-slate-700">
                            Soon
                        </span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                </a>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

        </nav>

        <div class="absolute bottom-0 left-0 right-0 border-t border-slate-800 p-4">

            <div class="mb-4 rounded-2xl bg-slate-900 p-4">
                <p class="text-sm font-semibold text-white">
                    <?php echo e(Auth::user()->name); ?>

                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Staff account
                </p>
            </div>

            <form method="POST" action="<?php echo e(route('logout')); ?>">
                <?php echo csrf_field(); ?>

                <button
                    type="submit"
                    class="w-full rounded-2xl bg-rose-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-rose-600"
                >
                    Logout
                </button>
            </form>

        </div>

    </aside>

    <!-- Main wrapper -->
    <div class="lg:pl-72">

        <!-- Topbar -->
        <header class="sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-200 bg-white/90 px-4 shadow-sm backdrop-blur sm:px-6">

            <div class="flex items-center gap-4">

                <button
                    type="button"
                    class="rounded-xl border border-slate-200 bg-white p-2 text-slate-600 shadow-sm lg:hidden"
                    @click="sidebarOpen = true"
                >
                    ☰
                </button>

                <div>
                    <h2 class="text-xl font-bold text-slate-900 sm:text-2xl">
                        <?php echo e($header ?? 'Dashboard'); ?>

                    </h2>

                    <p class="mt-1 hidden text-sm text-slate-500 sm:block">
                        DabbaDesk operations
                    </p>
                </div>

            </div>

            <div class="flex items-center gap-3">

                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold text-slate-700">
                        <?php echo e(Auth::user()->name); ?>

                    </p>

                    <p class="text-xs text-slate-400">
                        Logged in
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-600 font-bold text-white shadow-lg shadow-indigo-200">
                    <?php echo e(strtoupper(substr(Auth::user()->name, 0, 1))); ?>

                </div>

            </div>

        </header>

        <!-- Page content -->
        <main class="p-4 sm:p-6 lg:p-8">
            <?php echo e($slot); ?>

        </main>

    </div>

</div>

</body>
</html><?php /**PATH /var/www/dabba-live/dabbadesk-v2/resources/views/layouts/app.blade.php ENDPATH**/ ?>