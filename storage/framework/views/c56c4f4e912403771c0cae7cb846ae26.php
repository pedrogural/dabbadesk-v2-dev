<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title>DabbaDesk Login</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="font-sans antialiased">
    <main class="min-h-screen bg-slate-100 text-slate-900 lg:grid lg:grid-cols-[1.05fr_.95fr]">
        <section class="relative hidden overflow-hidden bg-gradient-to-br from-slate-950 via-purple-950 to-indigo-950 p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-14">
            <div class="pointer-events-none absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,.28) 1px, transparent 0); background-size: 34px 34px;"></div>
            <div class="pointer-events-none absolute -left-28 top-16 h-80 w-80 rounded-full bg-purple-500/30 blur-3xl"></div>
            <div class="pointer-events-none absolute bottom-10 right-0 h-96 w-96 rounded-full bg-sky-400/20 blur-3xl"></div>

            <div class="relative">
                <div class="inline-flex items-center gap-4 rounded-[1.75rem] border border-white/15 bg-white/10 px-5 py-4 shadow-2xl backdrop-blur">
                    <img
                        src="<?php echo e(asset('storage/branding/dabba-logo.png')); ?>"
                        alt="Dabba Direct logo"
                        class="h-16 w-16 rounded-2xl bg-white object-contain p-2 shadow-lg"
                    >
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.3em] text-purple-100">Dabba Direct</p>
                        <h1 class="mt-1 text-3xl font-black tracking-tight">DabbaDesk v2</h1>
                    </div>
                </div>

                <div class="mt-16 max-w-xl">
                    <p class="text-sm font-black uppercase tracking-[0.35em] text-purple-100">Operations Platform</p>
                    <h2 class="mt-5 text-5xl font-black leading-tight tracking-tight xl:text-6xl">Welcome back.</h2>
                    <p class="mt-6 max-w-lg text-lg font-semibold leading-8 text-purple-50/90">
                        Run order requests, draft workbench, orders, finance and warehouse operations from one focused place.
                    </p>
                </div>
            </div>

            <div class="relative max-w-xl rounded-[1.75rem] border border-white/15 bg-white/10 p-6 shadow-2xl backdrop-blur">
                <p class="text-xs font-black uppercase tracking-[0.3em] text-purple-100">DabbaDesk v2</p>
                <p class="mt-4 text-xl font-bold leading-9 text-white">
                    Designed, built and repeatedly rebuilt<br>
                    with coffee, stubbornness,<br>
                    and more revisions than anyone cares to count
                </p>
                <p class="mt-5 text-sm font-black uppercase tracking-[0.22em] text-purple-100">by Peter Gural</p>
            </div>
        </section>

        <section class="flex min-h-screen items-center justify-center px-5 py-8 sm:px-8 lg:px-12">
            <div class="w-full max-w-md">
                <div class="mb-8 text-center lg:hidden">
                    <img
                        src="<?php echo e(asset('storage/branding/dabba-logo.png')); ?>"
                        alt="Dabba Direct logo"
                        class="mx-auto h-20 w-20 rounded-3xl bg-white object-contain p-3 shadow-lg"
                    >
                    <h1 class="mt-5 text-3xl font-black tracking-tight text-slate-950">DabbaDesk v2</h1>
                    <p class="mt-2 text-sm font-bold text-slate-500">Dabba Direct Operations Platform</p>
                </div>

                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-2xl shadow-slate-200/70 sm:p-8">
                    <div>
                        <span class="inline-flex rounded-full bg-purple-50 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-purple-700">Staff access</span>
                        <h2 class="mt-5 text-3xl font-black tracking-tight text-slate-950">Sign in to DabbaDesk</h2>
                        <p class="mt-2 text-sm font-semibold leading-6 text-slate-500">
                            Authorised Dabba Direct staff only. All activity may be logged.
                        </p>
                    </div>

                    <?php if (isset($component)) { $__componentOriginal7c1bf3a9346f208f66ee83b06b607fb5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7c1bf3a9346f208f66ee83b06b607fb5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.auth-session-status','data' => ['class' => 'mt-5','status' => session('status')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('auth-session-status'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-5','status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(session('status'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7c1bf3a9346f208f66ee83b06b607fb5)): ?>
<?php $attributes = $__attributesOriginal7c1bf3a9346f208f66ee83b06b607fb5; ?>
<?php unset($__attributesOriginal7c1bf3a9346f208f66ee83b06b607fb5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7c1bf3a9346f208f66ee83b06b607fb5)): ?>
<?php $component = $__componentOriginal7c1bf3a9346f208f66ee83b06b607fb5; ?>
<?php unset($__componentOriginal7c1bf3a9346f208f66ee83b06b607fb5); ?>
<?php endif; ?>

                    <form method="POST" action="<?php echo e(route('login')); ?>" class="mt-7 space-y-5">
                        <?php echo csrf_field(); ?>

                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'email','value' => __('Email'),'class' => 'font-black text-slate-700']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'email','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Email')),'class' => 'font-black text-slate-700']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'email','class' => 'mt-2 block min-h-[52px] w-full rounded-2xl border-slate-300 px-4 text-base font-semibold shadow-sm focus:border-purple-500 focus:ring-purple-500','type' => 'email','name' => 'email','value' => old('email'),'required' => true,'autofocus' => true,'autocomplete' => 'username']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'email','class' => 'mt-2 block min-h-[52px] w-full rounded-2xl border-slate-300 px-4 text-base font-semibold shadow-sm focus:border-purple-500 focus:ring-purple-500','type' => 'email','name' => 'email','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('email')),'required' => true,'autofocus' => true,'autocomplete' => 'username']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('email'),'class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('email')),'class' => 'mt-2']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                        </div>

                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'password','value' => __('Password'),'class' => 'font-black text-slate-700']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'password','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Password')),'class' => 'font-black text-slate-700']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'password','class' => 'mt-2 block min-h-[52px] w-full rounded-2xl border-slate-300 px-4 text-base font-semibold shadow-sm focus:border-purple-500 focus:ring-purple-500','type' => 'password','name' => 'password','required' => true,'autocomplete' => 'current-password']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'password','class' => 'mt-2 block min-h-[52px] w-full rounded-2xl border-slate-300 px-4 text-base font-semibold shadow-sm focus:border-purple-500 focus:ring-purple-500','type' => 'password','name' => 'password','required' => true,'autocomplete' => 'current-password']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('password'),'class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('password')),'class' => 'mt-2']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <label for="remember_me" class="inline-flex items-center">
                                <input
                                    id="remember_me"
                                    type="checkbox"
                                    class="rounded border-slate-300 text-purple-600 shadow-sm focus:ring-purple-500"
                                    name="remember"
                                >
                                <span class="ms-2 text-sm font-semibold text-slate-600"><?php echo e(__('Remember me')); ?></span>
                            </label>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Route::has('password.request')): ?>
                                <a
                                    class="text-sm font-black text-purple-700 underline-offset-4 hover:text-purple-900 hover:underline focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2"
                                    href="<?php echo e(route('password.request')); ?>"
                                >
                                    <?php echo e(__('Forgot your password?')); ?>

                                </a>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        <button
                            type="submit"
                            class="flex min-h-[52px] w-full items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black uppercase tracking-[0.16em] text-white shadow-lg shadow-slate-950/15 transition hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2"
                        >
                            <?php echo e(__('Log in')); ?>

                        </button>
                    </form>

                    <div class="mt-7 rounded-2xl bg-slate-50 p-4 text-center text-xs font-bold leading-6 text-slate-500">
                        Requests → drafts → orders → money → warehouse.
                    </div>
                </div>

                <p class="mt-6 hidden text-center text-xs font-bold leading-6 text-slate-500 lg:block">
                    Authorised Dabba Direct staff only.
                </p>
            </div>
        </section>
    </main>
</body>
</html>
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/auth/login.blade.php ENDPATH**/ ?>