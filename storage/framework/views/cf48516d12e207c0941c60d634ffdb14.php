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

    <style>
        :where(input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]), select, textarea) {
            border-color: #cbd5e1;
            padding-left: 0.875rem;
            padding-right: 0.875rem;
        }
        :where(textarea) {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }
        :where(input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]), select) {
            padding-top: 0.625rem;
            padding-bottom: 0.625rem;
        }
        :where(input, select, textarea):focus {
            border-color: #6366f1;
            outline: 2px solid transparent;
            outline-offset: 2px;
            box-shadow: 0 0 0 3px rgb(99 102 241 / 0.14);
        }
    </style>
</head>

<body class="bg-slate-100 text-slate-800 antialiased">

<div class="min-h-screen">

    <!-- Mobile overlay -->
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        class="fixed inset-0 z-40 bg-indigo-950/45 lg:hidden"
        @click="sidebarOpen = false"
    ></div>

    <!-- Sidebar -->
    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-64 transform flex-col bg-indigo-950 text-indigo-50 transition-transform duration-300 lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="flex h-14 shrink-0 items-center justify-between border-b border-indigo-900/60 px-4">
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
                class="rounded-lg p-1.5 text-slate-400 hover:bg-indigo-700 hover:text-white lg:hidden"
                @click="sidebarOpen = false"
            >
                ✕
            </button>
        </div>

        <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto overflow-x-visible px-2.5 py-3 pr-3 scroll-smooth [scrollbar-width:thin] [scrollbar-color:#475569_transparent]">

            <?php
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
                    ['label' => 'Data Quality', 'route' => 'admin.text-cleanup.index', 'icon' => '🧹', 'admin' => true],
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
                    class="group flex min-w-0 items-center gap-2.5 overflow-visible rounded-xl px-3 py-2 pr-3.5 text-[13px] font-semibold leading-tight transition
                    <?php echo e($isActive
                            ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-950/30'
                            : 'text-slate-300 hover:bg-indigo-700 hover:text-white'); ?>"
                >
                    <span class="w-6 shrink-0 text-center text-base leading-none">
                        <?php echo e($item['icon']); ?>

                    </span>

                    <span class="min-w-0 flex-1 truncate">
                        <?php echo e($item['label']); ?>

                    </span>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($item['counter'])): ?>
                        <template x-if="orderRequestCount > 0">
                            <span
                                x-text="orderRequestCount"
                                class="ml-1 inline-flex h-4 min-w-4 shrink-0 items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-black leading-none text-white ring-2 ring-indigo-600/20"
                            ></span>
                        </template>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (! ($item['route'])): ?>
                        <span class="ml-1 inline-flex shrink-0 items-center rounded-full bg-indigo-800/70 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wide text-slate-400 group-hover:bg-indigo-700/80 group-hover:text-slate-300">
                            soon
                        </span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                </a>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

        </nav>

        <div class="shrink-0 border-t border-indigo-900/60 p-3">

            <div class="mb-2 rounded-xl bg-indigo-600 px-3 py-2">
                <p class="truncate text-xs font-black text-white">
                    <?php echo e(Auth::user()->name); ?>

                </p>

                <p class="text-[10px] font-semibold text-slate-400">
                    Staff account
                </p>
            </div>

            <form method="POST" action="<?php echo e(route('logout')); ?>">
                <?php echo csrf_field(); ?>

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
                        <?php echo e($header ?? 'Dashboard'); ?>

                    </h2>

                    <p class="hidden text-xs font-semibold text-slate-500 sm:block">
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

                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-sm font-black text-white shadow-lg shadow-indigo-200">
                    <?php echo e(strtoupper(substr(Auth::user()->name, 0, 1))); ?>

                </div>

            </div>

        </header>

        <!-- Page content -->
        <main class="p-3 sm:p-5 lg:p-6">
            <?php echo e($slot); ?>

        </main>

    </div>

</div>


<div id="dabba-confirm-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm" aria-hidden="true">
    <div class="w-full max-w-md overflow-hidden rounded-[1.75rem] bg-white shadow-2xl ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-5 py-4">
            <p id="dabba-confirm-eyebrow" class="text-[10px] font-black uppercase tracking-[0.18em] text-indigo-600">Confirm action</p>
            <h3 id="dabba-confirm-title" class="mt-1 text-lg font-black text-slate-950">Are you sure?</h3>
            <p id="dabba-confirm-message" class="mt-2 text-sm font-semibold leading-6 text-slate-600">Please confirm this action.</p>
        </div>
        <div class="flex flex-col-reverse gap-2 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end">
            <button type="button" id="dabba-confirm-cancel" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-100">Keep working</button>
            <button type="button" id="dabba-confirm-ok" class="rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-indigo-700">Confirm</button>
        </div>
    </div>
</div>

<script>
    (() => {
        const modal = document.getElementById('dabba-confirm-modal');
        if (! modal) return;

        const title = document.getElementById('dabba-confirm-title');
        const message = document.getElementById('dabba-confirm-message');
        const eyebrow = document.getElementById('dabba-confirm-eyebrow');
        const ok = document.getElementById('dabba-confirm-ok');
        const cancel = document.getElementById('dabba-confirm-cancel');
        let pendingForm = null;
        let pendingButton = null;

        const open = (trigger) => {
            pendingForm = trigger.closest('form');
            pendingButton = trigger;
            eyebrow.textContent = trigger.dataset.confirmEyebrow || 'Confirm action';
            title.textContent = trigger.dataset.confirmTitle || 'Are you sure?';
            message.textContent = trigger.dataset.confirmMessage || 'Please confirm this action.';
            ok.textContent = trigger.dataset.confirmButton || 'Confirm';
            cancel.textContent = trigger.dataset.confirmCancel || 'Keep working';
            ok.className = trigger.dataset.confirmDanger === '1'
                ? 'rounded-2xl bg-rose-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-rose-700'
                : 'rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-indigo-700';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
            setTimeout(() => cancel?.focus(), 25);
        };

        const close = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');
            pendingButton = null;
            pendingForm = null;
        };

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (! (form instanceof HTMLFormElement)) return;
            if (! form.matches('[data-confirm]')) return;
            if (form.dataset.confirmed === '1') {
                form.dataset.confirmed = '0';
                return;
            }
            event.preventDefault();
            open(form);
        });

        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-confirm-click]');
            if (! button) return;
            event.preventDefault();
            open(button);
        });

        ok?.addEventListener('click', () => {
            if (pendingForm) {
                pendingForm.dataset.confirmed = '1';
                pendingForm.submit();
                return;
            }
            const formId = pendingButton?.dataset.confirmForm;
            const form = formId ? document.getElementById(formId) : null;
            if (form) form.submit();
            close();
        });

        cancel?.addEventListener('click', close);
        modal.addEventListener('click', (event) => {
            if (event.target === modal) close();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && ! modal.classList.contains('hidden')) close();
        });
    })();
</script>

</body>
</html><?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/layouts/app.blade.php ENDPATH**/ ?>