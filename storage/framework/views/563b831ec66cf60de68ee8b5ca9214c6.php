<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e(config('app.name', 'Laravel')); ?></title>

        
        <?php ($tampilanHalaman = \App\Models\PengaturanTampilan::current()); ?>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=<?php echo e($tampilanHalaman->fonts_query); ?>&display=swap" rel="stylesheet" />

        <style>
            :root {
                --font-halaman: '<?php echo e($tampilanHalaman->jenis_huruf_halaman); ?>', sans-serif;
                --ukuran-halaman: <?php echo e($tampilanHalaman->ukuran_huruf_halaman_px); ?>;
            }
        </style>

        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="font-sans text-slate-900 antialiased" style="font-family: var(--font-halaman); font-size: var(--ukuran-halaman);">
        <div class="relative min-h-screen overflow-hidden bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900">
            
            <div class="pointer-events-none fixed inset-0 overflow-hidden">
                <div class="absolute -top-24 -left-24 h-96 w-96 rounded-full bg-blue-600/30 blur-3xl animate-blob-a"></div>
                <div class="absolute top-1/3 -right-24 h-96 w-96 rounded-full bg-sky-500/20 blur-3xl animate-blob-b"></div>
                <div class="absolute -bottom-32 left-1/3 h-96 w-96 rounded-full bg-indigo-500/20 blur-3xl animate-blob-c"></div>
            </div>

            
            <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex items-center justify-between gap-4 animate-fade-in-down">
                <a href="<?php echo e(route('beranda')); ?>" wire:navigate class="flex items-center gap-2.5 min-w-0">
                    <?php if (isset($component)) { $__componentOriginal8892e718f3d0d7a916180885c6f012e7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8892e718f3d0d7a916180885c6f012e7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.application-logo','data' => ['class' => 'w-9 h-9 shrink-0 fill-current text-blue-300 drop-shadow animate-float-logo']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('application-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-9 h-9 shrink-0 fill-current text-blue-300 drop-shadow animate-float-logo']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $attributes = $__attributesOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $component = $__componentOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__componentOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
                    <span class="text-white font-semibold text-sm sm:text-base truncate">Aplikasi OPS_BOSP SR CBD</span>
                </a>

                <a href="<?php echo e(route('verifikasi-akses')); ?>" wire:navigate
                    class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2 sm:px-5 sm:py-2.5 rounded-xl bg-white/15 hover:bg-white/25 backdrop-blur-md ring-1 ring-white/30 text-white text-sm font-semibold transition shadow-lg">
                    Masuk
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 010-1.06L10.94 10 7.21 6.29a.75.75 0 111.06-1.06l4.25 4.25a.75.75 0 010 1.06l-4.25 4.25a.75.75 0 01-1.06 0z" clip-rule="evenodd" /></svg>
                </a>
            </div>

            <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
                <?php echo e($slot); ?>

            </div>
        </div>
    </body>
</html>
<?php /**PATH D:\Aplikasi SRCBD_OPS_BOSP\srcbd-ops-bosp-source\resources\views/layouts/beranda.blade.php ENDPATH**/ ?>