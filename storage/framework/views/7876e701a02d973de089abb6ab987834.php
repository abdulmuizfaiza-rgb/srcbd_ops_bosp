<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e(config('app.name', 'Laravel')); ?></title>

        
        <?php ($tampilanHalaman = \App\Models\PengaturanTampilan::current()); ?>

        <!-- Fonts (hanya jenis huruf yang benar-benar dipakai, supaya halaman lebih cepat) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=<?php echo e($tampilanHalaman->fonts_query); ?>&display=swap" rel="stylesheet" />

        <style>
            :root {
                --font-halaman: '<?php echo e($tampilanHalaman->jenis_huruf_halaman); ?>', sans-serif;
                --font-menu: '<?php echo e($tampilanHalaman->jenis_huruf_menu); ?>', sans-serif;
                --ukuran-halaman: <?php echo e($tampilanHalaman->ukuran_huruf_halaman_px); ?>;
                --ukuran-menu: <?php echo e($tampilanHalaman->ukuran_huruf_menu_px); ?>;
                --warna-huruf-landing: <?php echo e($tampilanHalaman->warna_huruf_landing); ?>;
                --warna-huruf-login: <?php echo e($tampilanHalaman->warna_huruf_login); ?>;
                --warna-huruf-registrasi: <?php echo e($tampilanHalaman->warna_huruf_registrasi); ?>;
                --ukuran-registrasi: <?php echo e($tampilanHalaman->ukuran_huruf_registrasi_px); ?>;
                --font-registrasi: '<?php echo e($tampilanHalaman->jenis_huruf_registrasi); ?>', sans-serif;
            }
        </style>

        <!-- Scripts -->
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="font-sans text-slate-900 antialiased">
        
        <div class="relative min-h-screen flex flex-col sm:justify-center items-center sm:items-end pt-6 sm:pt-0 sm:pr-6 md:pr-16 lg:pr-24 xl:pr-32 overflow-hidden bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900"
            <?php if($tampilanHalaman->background_landing_url): ?>
                style="background-image: url('<?php echo e($tampilanHalaman->background_landing_url); ?>'); background-size: cover; background-position: center;"
            <?php endif; ?>
        >
            
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -top-24 -left-24 h-96 w-96 rounded-full bg-blue-600/30 blur-3xl animate-blob-a"></div>
                <div class="absolute top-1/3 -right-24 h-96 w-96 rounded-full bg-sky-500/20 blur-3xl animate-blob-b"></div>
                <div class="absolute -bottom-32 left-1/3 h-96 w-96 rounded-full bg-indigo-500/20 blur-3xl animate-blob-c"></div>
            </div>

            <div class="relative z-10 animate-fade-in-down">
                <a href="/" wire:navigate class="block">
                    <?php if (isset($component)) { $__componentOriginal8892e718f3d0d7a916180885c6f012e7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8892e718f3d0d7a916180885c6f012e7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.application-logo','data' => ['class' => 'w-16 h-16 fill-current text-blue-300 drop-shadow-lg animate-float-logo']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('application-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-16 h-16 fill-current text-blue-300 drop-shadow-lg animate-float-logo']); ?>
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
                </a>
            </div>

            <div
                x-data="{
                    rotateX: 0,
                    rotateY: 0,
                    tilt(e) {
                        const r = $el.getBoundingClientRect();
                        const px = (e.clientX - r.left) / r.width;
                        const py = (e.clientY - r.top) / r.height;
                        this.rotateY = (px - 0.5) * 10;
                        this.rotateX = (0.5 - py) * 10;
                    },
                    reset() { this.rotateX = 0; this.rotateY = 0; }
                }"
                @mousemove="tilt($event)"
                @mouseleave="reset()"
                
                :style="{ transform: 'perspective(1200px) rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg)' }"
                class="teks-tebal-akses relative z-10 w-full sm:max-w-md mt-6 px-6 py-6 bg-white/70 backdrop-blur-2xl shadow-2xl ring-1 ring-white/30 overflow-hidden sm:rounded-2xl transition-transform duration-200 ease-out will-change-transform animate-fade-in-up"
                <?php if($tampilanHalaman->background_login_url): ?>
                    style="background-image: url('<?php echo e($tampilanHalaman->background_login_url); ?>'); background-size: cover; background-position: center;"
                <?php endif; ?>
            >
                <?php echo e($slot); ?>

            </div>
        </div>
    </body>
</html>
<?php /**PATH D:\Aplikasi SRCBD_OPS_BOSP\srcbd-ops-bosp-source\resources\views/layouts/guest.blade.php ENDPATH**/ ?>