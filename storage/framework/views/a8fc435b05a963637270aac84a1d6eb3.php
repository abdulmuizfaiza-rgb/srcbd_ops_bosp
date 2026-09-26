
<div class="bg-white rounded-xl shadow-sm border border-slate-200/70 overflow-hidden">
    <div class="p-5 pb-3 flex items-center justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-slate-800"><?php echo e($judul); ?></p>
            <p class="text-xs text-slate-500"><?php echo e($keterangan); ?></p>
        </div>
        <div class="shrink-0 flex items-center gap-2 text-xs">
            <span class="px-2 py-1 rounded-lg bg-blue-50 text-blue-700 font-semibold"><?php echo e($jumlahSudah); ?> Sudah</span>
            <span class="px-2 py-1 rounded-lg bg-red-50 text-red-700 font-semibold"><?php echo e($jumlahBelum); ?> Belum</span>
        </div>
    </div>
    <div class="divide-y divide-slate-100">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $daftarStatus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $baris): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="px-5 py-2.5 flex items-center justify-between gap-3 text-sm">
                <span class="text-slate-700 truncate"><?php echo e($baris['sekolah']->nama_sekolah); ?></span>
                <span class="shrink-0 text-[11px] px-2 py-0.5 rounded-full <?php echo e($baris['sudah'] ? 'bg-blue-50 text-blue-700' : 'bg-red-50 text-red-700'); ?>">
                    <?php echo e($baris['sudah'] ? $labelSudah : $labelBelum); ?>

                </span>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="text-sm text-slate-400 text-center py-8">Belum ada data sekolah.</p>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($daftarStatus->hasPages()): ?>
        <div class="px-5 py-3 border-t border-slate-100">
            <?php echo e($daftarStatus->links(data: ['scrollTo' => false])); ?>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH D:\Aplikasi SRCBD_OPS_BOSP\srcbd-ops-bosp-source\resources\views/livewire/dashboard/partials/daftar-status-sekolah.blade.php ENDPATH**/ ?>