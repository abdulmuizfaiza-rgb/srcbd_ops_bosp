<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e(config('app.name', 'Laravel')); ?></title>

        
        <?php ($tampilanHalaman = \App\Models\PengaturanTampilan::current()); ?>

        
        <?php ($ringkasanPopupTimelineLogin = \App\Models\DeadlinePekerjaan::ringkasanPopupLoginUntukUserSaatIni()); ?>

        <!-- Fonts (hanya jenis huruf yang benar-benar dipakai, supaya halaman lebih cepat) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=<?php echo e($tampilanHalaman->fonts_query); ?>&display=swap" rel="stylesheet" />

        <style>
            :root {
                --font-halaman: '<?php echo e($tampilanHalaman->jenis_huruf_halaman); ?>', sans-serif;
                --font-menu: '<?php echo e($tampilanHalaman->jenis_huruf_menu); ?>', sans-serif;
                --ukuran-halaman: <?php echo e($tampilanHalaman->ukuran_huruf_halaman_px); ?>;
                --ukuran-menu: <?php echo e($tampilanHalaman->ukuran_huruf_menu_px); ?>;
                --warna-huruf-menu: <?php echo e($tampilanHalaman->warna_huruf_menu); ?>;
            }

            /*
             * Round 9 Bagian C (permintaan user 2026-09-23, poin 4) - popup
             * info timeline saat login + running text (marquee) di atas
             * halaman. Ditaruh inline di sini (bukan resources/css/app.css)
             * supaya TIDAK perlu "npm run build" ulang saat update ini
             * dipasang - cukup salin file Blade seperti update-update
             * sebelumnya.
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
            @keyframes infoTimelinePopupGlow {
                0%, 100% { box-shadow: 0 20px 45px -10px rgba(99, 33, 200, 0.55); }
                50%      { box-shadow: 0 20px 55px -8px rgba(219, 39, 119, 0.65); }
            }
            .animate-info-timeline-popup {
                animation: infoTimelinePopupGlow 1.8s ease-in-out infinite;
            }
        </style>

        <!-- Scripts -->
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen" style="background-color: <?php echo e($tampilanHalaman->warna_halaman); ?>">
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('layout.navigation', []);

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-649282575-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>

            
            <div x-data="{
                    tampilPopup: <?php echo e($ringkasanPopupTimelineLogin ? 'true' : 'false'); ?>,
                    tampilMarquee: false,
                    ringkasan: <?php echo \Illuminate\Support\Js::from($ringkasanPopupTimelineLogin)->toHtml() ?>,
                 }"
                 x-init="if (tampilPopup) { setTimeout(() => { tampilPopup = false; tampilMarquee = true; }, 5000); }">
                
                <div x-show="tampilPopup" style="display: none;"
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-75" x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90"
                     class="fixed inset-x-0 top-24 z-[70] flex justify-center px-4 pointer-events-none">
                    <div class="animate-info-timeline-popup pointer-events-auto max-w-sm rounded-2xl bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 px-5 py-4 text-white ring-1 ring-white/20">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-white/20 animate-pulse">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 7v5l3 3" />
                                </svg>
                            </span>
                            <div class="text-sm leading-snug">
                                <p class="font-semibold">Info Timeline Pekerjaan</p>
                                <template x-if="ringkasan">
                                    <p class="mt-1 text-white/90">
                                        <span x-text="ringkasan.jumlah"></span> tahap kerja sudah lewat deadline tahun ini.
                                        Paling mendesak: <span class="font-semibold" x-text="ringkasan.labelPalingMendesak"></span>
                                        (Triwulan <span x-text="ringkasan.triwulanPalingMendesak"></span>) -
                                        lewat sejak <span x-text="ringkasan.tanggalPalingMendesak"></span>.
                                    </p>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div x-show="tampilMarquee" style="display: none;" x-cloak
                     class="lg:ml-64 overflow-hidden border-b border-amber-300 bg-gradient-to-r from-amber-400 via-orange-400 to-rose-400 py-1.5">
                    <template x-if="ringkasan">
                        <span class="animate-info-timeline-marquee text-sm font-semibold text-white drop-shadow-sm">
                            ⏰ Info Timeline Pekerjaan — <span x-text="ringkasan.jumlah"></span> tahap kerja sudah lewat deadline tahun ini.
                            Paling mendesak: <span x-text="ringkasan.labelPalingMendesak"></span>
                            (Triwulan <span x-text="ringkasan.triwulanPalingMendesak"></span>),
                            lewat sejak <span x-text="ringkasan.tanggalPalingMendesak"></span>.
                        </span>
                    </template>
                </div>
            </div>

            <div class="lg:ml-64">
                <!-- Page Heading -->
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($header)): ?>
                    <header class="bg-white border-b border-slate-200 shadow-sm">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            <?php echo e($header); ?>

                        </div>
                    </header>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <!-- Page Content -->
                <main>
                    <?php echo e($slot); ?>

                </main>
            </div>
        </div>
    </body>
</html>
<?php /**PATH D:\Aplikasi SRCBD_OPS_BOSP\srcbd-ops-bosp-source\resources\views/layouts/app.blade.php ENDPATH**/ ?>