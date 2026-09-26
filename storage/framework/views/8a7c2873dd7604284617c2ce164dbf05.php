
<div class="space-y-8">
    
    <div class="text-center pt-6 sm:pt-10 pb-2 animate-fade-in-up">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md ring-1 ring-white/20 text-blue-100 text-xs font-medium">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
            </span>
            Informasi Terkini - Tahun <?php echo e($tahun); ?>, Triwulan <?php echo e($triwulan); ?>

        </span>

        <h1 class="mt-4 text-2xl sm:text-4xl font-bold text-white">
            Progres Pendataan OPS &amp; BOSP
        </h1>
        <p class="mt-2 text-sm sm:text-base text-blue-100/90 max-w-2xl mx-auto">
            Rekap sekolah yang sudah dan belum melakukan Pendataan OPS maupun Pendataan BOSP untuk Triwulan <?php echo e($triwulan); ?>

            Tahun <?php echo e($tahun); ?> - dari total <?php echo e($totalSekolah); ?> sekolah terdaftar.
        </p>
    </div>

    
    <div class="animate-fade-in-up">
        <?php echo $__env->make('livewire.dashboard.partials.selector-triwulan', ['warna' => 'blue'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>

    
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        
        <div class="relative overflow-hidden rounded-2xl shadow-xl bg-gradient-to-br from-violet-600 via-purple-600 to-fuchsia-600 animate-fade-in-up">
            <div class="pointer-events-none absolute -top-10 -right-10 w-56 h-56 rounded-full bg-white/10 blur-2xl animate-blob-a"></div>
            <div class="pointer-events-none absolute -bottom-16 -left-10 w-64 h-64 rounded-full bg-fuchsia-300/20 blur-2xl animate-blob-b"></div>

            <div class="relative p-6 sm:p-7 text-white">
                <p class="text-xs font-semibold uppercase tracking-wider text-violet-100">Pendataan OPS</p>
                <div class="mt-3 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-4xl font-bold leading-none"><?php echo e($opsPersen); ?>%</p>
                        <p class="text-xs text-violet-100 mt-1">Sekolah sudah pendataan</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm"><span class="font-bold"><?php echo e($opsSudah); ?></span> Sudah</p>
                        <p class="text-sm text-violet-100"><span class="font-bold"><?php echo e($opsBelum); ?></span> Belum</p>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="relative overflow-hidden rounded-2xl shadow-xl bg-gradient-to-br from-sky-600 via-blue-600 to-indigo-600 animate-fade-in-up" style="animation-delay:.15s">
            <div class="pointer-events-none absolute -top-10 -right-10 w-56 h-56 rounded-full bg-white/10 blur-2xl animate-blob-a"></div>
            <div class="pointer-events-none absolute -bottom-16 -left-10 w-64 h-64 rounded-full bg-sky-300/20 blur-2xl animate-blob-c"></div>

            <div class="relative p-6 sm:p-7 text-white">
                <p class="text-xs font-semibold uppercase tracking-wider text-sky-100">Pendataan BOSP</p>
                <div class="mt-3 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-4xl font-bold leading-none"><?php echo e($bospPersen); ?>%</p>
                        <p class="text-xs text-sky-100 mt-1">Sekolah sudah pendataan</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm"><span class="font-bold"><?php echo e($bospSudah); ?></span> Sudah</p>
                        <p class="text-sm text-sky-100"><span class="font-bold"><?php echo e($bospBelum); ?></span> Belum</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 pb-4">
        <?php echo $__env->make('livewire.dashboard.partials.daftar-status-sekolah', [
            'judul' => 'Daftar Sekolah - Pendataan OPS',
            'keterangan' => 'Lampiran 2a, 2b, dan 2c lengkap - Triwulan '.$triwulan.', Tahun '.$tahun.'.',
            'daftarStatus' => $halamanOps,
            'labelSudah' => 'Sudah Pendataan',
            'labelBelum' => 'Belum Pendataan',
            'jumlahSudah' => $opsSudah,
            'jumlahBelum' => $opsBelum,
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <?php echo $__env->make('livewire.dashboard.partials.daftar-status-sekolah', [
            'judul' => 'Daftar Sekolah - Pendataan BOSP',
            'keterangan' => 'Ada data di salah satu menu BOSP - Triwulan '.$triwulan.', Tahun '.$tahun.'.',
            'daftarStatus' => $halamanBosp,
            'labelSudah' => 'Sudah Pendataan',
            'labelBelum' => 'Belum Pendataan',
            'jumlahSudah' => $bospSudah,
            'jumlahBelum' => $bospBelum,
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
</div>
<?php /**PATH D:\Aplikasi SRCBD_OPS_BOSP\srcbd-ops-bosp-source\resources\views/livewire/beranda/index.blade.php ENDPATH**/ ?>