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

            /*
             * Animasi teks berjalan (marquee) untuk pengumuman aktif
             * (permintaan user 2026-09-26) - SENGAJA disalin persis dari
             * resources/views/layouts/app.blade.php (bukan
             * @keyframes/class baru), supaya layout INI (dipakai
             * halaman publik "beranda", TERPISAH dari layouts/app.blade.php
             * yang dipakai halaman setelah login) tidak perlu ikut
             * memuat file itu. Ditaruh inline (bukan
             * resources/css/app.css) dengan alasan yang SAMA seperti di
             * app.blade.php: tidak perlu "npm run build" ulang.
             */
            @keyframes infoTimelineMarquee {
                0%   { transform: translateX(100%); }
                100% { transform: translateX(-100%); }
            }
            .animate-info-timeline-marquee {
                display: inline-block;
                white-space: nowrap;
                animation: infoTimelineMarquee 16s linear infinite;
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

            
            <?php ($pengumumanAktif = \App\Models\Pengumuman::aktifSaatIni()->get()); ?>

            
            <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex items-center justify-between gap-3 sm:gap-4 animate-fade-in-down">
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

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pengumumanAktif->isNotEmpty()): ?>
                    <div x-data="{ tampilDetailPengumuman: false, timerPengumuman: null }" class="flex-1 min-w-0">
                        <button type="button"
                            @click="tampilDetailPengumuman = true; clearTimeout(timerPengumuman); timerPengumuman = setTimeout(() => (tampilDetailPengumuman = false), 10000)"
                            class="w-full overflow-hidden rounded-lg border border-amber-300/40 bg-gradient-to-r from-amber-400/90 via-orange-400/90 to-rose-400/90 px-3 py-1.5 text-left hover:brightness-110 transition">
                            <span class="animate-info-timeline-marquee inline-block text-xs sm:text-sm font-semibold text-white drop-shadow-sm">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $pengumumanAktif; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    📢 <?php echo e($p->judul); ?>&nbsp;&nbsp;&nbsp;•&nbsp;&nbsp;&nbsp;
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </span>
                            <span class="block text-[10px] sm:text-[11px] text-white/85 leading-tight mt-0.5">
                                Silahkan Klik Link Informasi nya untuk melihat Detail nya
                            </span>
                        </button>

                        
                        <template x-teleport="body">
                            <div x-show="tampilDetailPengumuman" x-cloak style="display: none; z-index: 80;"
                                class="fixed inset-0 flex items-center justify-center px-4"
                                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                                <div class="fixed inset-0 bg-slate-900/60" @click="tampilDetailPengumuman = false; clearTimeout(timerPengumuman)"></div>

                                <div class="relative w-full max-w-lg max-h-[80vh] overflow-y-auto scrollbar-modern rounded-xl bg-white p-6 shadow-2xl">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-semibold text-slate-900">Pengumuman</h3>
                                        <button type="button" @click="tampilDetailPengumuman = false; clearTimeout(timerPengumuman)" class="text-slate-400 hover:text-slate-600">
                                            <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                        </button>
                                    </div>

                                    <div class="space-y-5">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $pengumumanAktif; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="<?php echo e(! $loop->last ? 'pb-5 border-b border-slate-100' : ''); ?>">
                                                <h4 class="font-semibold text-slate-800"><?php echo e($p->judul); ?></h4>
                                                <p class="text-xs text-slate-400 mt-0.5"><?php echo e($p->tanggal_aktif->translatedFormat('d F Y')); ?> - <?php echo e($p->tanggal_nonaktif->translatedFormat('d F Y')); ?></p>
                                                <p class="text-sm text-slate-600 mt-2 whitespace-pre-line"><?php echo e($p->isi); ?></p>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

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