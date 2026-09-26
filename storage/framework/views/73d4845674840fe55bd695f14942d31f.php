
<?php
    $warnaAktif = $warna === 'blue' ? 'bg-blue-600 border-blue-600 text-white' : 'bg-violet-600 border-violet-600 text-white';
    $warnaFokus = $warna === 'blue' ? 'focus:border-blue-500 focus:ring-blue-500' : 'focus:border-violet-500 focus:ring-violet-500';
?>
<div class="flex flex-wrap items-center gap-4 bg-white p-4 rounded-xl shadow-sm border border-slate-200/70">
    <div class="flex items-center gap-2.5">
        <label for="dashboardTahun<?php echo e(ucfirst($warna)); ?>" class="text-sm text-slate-600">Tahun</label>
        <select wire:model.live="tahun" id="dashboardTahun<?php echo e(ucfirst($warna)); ?>"
                class="border-slate-300 <?php echo e($warnaFokus); ?> rounded-md shadow-sm text-sm">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $tahunOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opsiTahun): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($opsiTahun); ?>"><?php echo e($opsiTahun); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </select>
    </div>
    <div class="flex items-center gap-1.5">
        <label class="text-sm text-slate-600 mr-1">Triwulan</label>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = [1, 2, 3, 4]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opsiTriwulan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button type="button" wire:click="$set('triwulan', <?php echo e($opsiTriwulan); ?>)"
                class="px-3 py-1.5 rounded-md text-sm font-medium border transition
                    <?php echo e($triwulanAktif === $opsiTriwulan ? $warnaAktif : 'bg-white border-slate-300 text-slate-600 hover:bg-slate-50'); ?>">
                TW-<?php echo e($opsiTriwulan); ?>

            </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php /**PATH D:\Aplikasi SRCBD_OPS_BOSP\srcbd-ops-bosp-source\resources\views/livewire/dashboard/partials/selector-triwulan.blade.php ENDPATH**/ ?>