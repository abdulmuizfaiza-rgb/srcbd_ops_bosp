 <?php $__env->slot('header', null, []); ?> 
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        <?php echo e(__('Backup')); ?>

    </h2>
 <?php $__env->endSlot(); ?>

<div>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                    <?php echo e(session('status')); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('errorBackup')): ?>
                <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                    <?php echo e(session('errorBackup')); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <div class="bg-white shadow sm:rounded-lg p-4 sm:p-8">
                <h3 class="text-lg font-bold text-indigo-900 mb-2">Buat Backup Baru</h3>
                <p class="text-sm text-slate-500 mb-4">
                    Backup berisi seluruh kode aplikasi & data database terbaru, digabung dalam satu file .zip.
                    Proses ini bisa memakan waktu beberapa saat, mohon jangan tutup halaman ini sampai selesai.
                </p>

                <button
                    wire:click="buatBackup"
                    wire:loading.attr="disabled"
                    wire:target="buatBackup"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="buatBackup">Buat Backup Sekarang</span>
                    <span wire:loading wire:target="buatBackup">Sedang membuat backup...</span>
                </button>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="p-4 sm:p-8">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <h3 class="text-lg font-bold text-indigo-900">Riwayat Backup</h3>

                        <div class="flex items-center gap-2">
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'filterTahun','value' => 'Tahun','class' => 'text-xs text-slate-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filterTahun','value' => 'Tahun','class' => 'text-xs text-slate-500']); ?>
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
                            <select wire:model.live="filterTahun" id="filterTahun" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                                <option value="">Semua Tahun</option>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $daftarTahun; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tahun): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($tahun); ?>"><?php echo e($tahun); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead>
                                <tr class="text-left text-slate-500">
                                    <th class="px-3 py-2">Nama File</th>
                                    <th class="px-3 py-2">Tahun</th>
                                    <th class="px-3 py-2">Ukuran</th>
                                    <th class="px-3 py-2">Dibuat</th>
                                    <th class="px-3 py-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $backup; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-slate-800"><?php echo e($item->nama_file); ?></td>
                                        <td class="px-3 py-2 text-slate-600"><?php echo e($item->tahun); ?></td>
                                        <td class="px-3 py-2 text-slate-600 whitespace-nowrap"><?php echo e($item->ukuranManusiawi() ?: '-'); ?></td>
                                        <td class="px-3 py-2 text-slate-600 whitespace-nowrap">
                                            <?php echo e($item->created_at->translatedFormat('d M Y H:i')); ?>

                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->dibuatOleh): ?>
                                                <span class="text-slate-400">oleh <?php echo e($item->dibuatOleh->display_name); ?></span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap space-x-2">
                                            <a href="<?php echo e(route('backup.unduh', $item)); ?>" class="text-emerald-600 hover:underline">Unduh</a>
                                            <button wire:click="hapus(<?php echo e($item->id); ?>)" wire:confirm="Hapus backup ini? Tindakan ini tidak dapat dibatalkan." class="text-red-600 hover:underline">Hapus</button>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="5" class="px-3 py-6 text-center text-slate-400">Belum ada backup yang dibuat.</td>
                                    </tr>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <?php echo e($backup->links()); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH D:\Aplikasi SRCBD_OPS_BOSP\srcbd-ops-bosp-source\resources\views/livewire/backup/index.blade.php ENDPATH**/ ?>