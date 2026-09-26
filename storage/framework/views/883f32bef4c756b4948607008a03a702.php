 <?php $__env->slot('header', null, []); ?> 
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        <?php echo e(__('Dashboard')); ?>

    </h2>
 <?php $__env->endSlot(); ?>

<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-500 overflow-hidden shadow-sm rounded-xl">
                <div class="p-6 sm:p-8 text-white">
                    <p class="text-lg font-semibold">
                        Selamat datang, <?php echo e(auth()->user()->display_name); ?>

                    </p>
                    <p class="text-sm text-indigo-100 mt-1">
                        Anda masuk sebagai <span class="font-medium text-white"><?php echo e(auth()->user()->level_akses_label); ?></span>
                        pada Aplikasi OPS_BOSP SR CBD.
                    </p>
                </div>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($peran === 'superadmin'): ?>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'building','class' => 'w-5 h-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'building','class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none"><?php echo e($totalSekolah); ?></p>
                            <p class="text-xs text-slate-500 mt-1">Total Sekolah</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'building','class' => 'w-5 h-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'building','class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none"><?php echo e($totalNegeri); ?></p>
                            <p class="text-xs text-slate-500 mt-1">Sekolah Negeri</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'building','class' => 'w-5 h-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'building','class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none"><?php echo e($totalSwasta); ?></p>
                            <p class="text-xs text-slate-500 mt-1">Sekolah Swasta</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'users','class' => 'w-5 h-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'users','class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none"><?php echo e($totalPengguna); ?></p>
                            <p class="text-xs text-slate-500 mt-1">Total Pengguna</p>
                        </div>
                    </div>
                </div>

                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <?php
                        $donutKelengkapan = [
                            [
                                'judul' => 'Kelengkapan Profil Sekolah',
                                'lengkap' => $profilLengkap,
                                'belum' => $profilBelum,
                                'warna' => '#4f46e5',
                            ],
                            [
                                'judul' => 'Kelengkapan Identitas OPS',
                                'lengkap' => $idOpsIsi,
                                'belum' => $idOpsBelum,
                                'warna' => '#0ea5e9',
                            ],
                            [
                                'judul' => 'Kelengkapan Identitas BOSP',
                                'lengkap' => $idBospIsi,
                                'belum' => $idBospBelum,
                                'warna' => '#10b981',
                            ],
                        ];
                    ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $donutKelengkapan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $totalD = $d['lengkap'] + $d['belum'];
                            $persenD = $totalD > 0 ? round(($d['lengkap'] / $totalD) * 100) : 0;
                        ?>
                        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70">
                            <p class="text-sm font-semibold text-slate-800"><?php echo e($d['judul']); ?></p>
                            <p class="text-xs text-slate-500 mb-3"><?php echo e($d['lengkap']); ?> dari <?php echo e($totalD); ?> sekolah</p>
                            <div wire:ignore
                                 x-data="{
                                    init() {
                                        new Chart(this.$refs.canvas.getContext('2d'), {
                                            type: 'doughnut',
                                            data: {
                                                labels: ['Lengkap', 'Belum Lengkap'],
                                                datasets: [{
                                                    data: [<?php echo e($d['lengkap']); ?>, <?php echo e($d['belum']); ?>],
                                                    backgroundColor: ['<?php echo e($d['warna']); ?>', '#e2e8f0'],
                                                    borderWidth: 0,
                                                }],
                                            },
                                            options: {
                                                cutout: '72%',
                                                plugins: { legend: { display: false }, tooltip: { enabled: true } },
                                            },
                                        });
                                    }
                                 }"
                                 class="relative h-40">
                                <canvas x-ref="canvas"></canvas>
                                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                    <span class="text-xl font-bold text-slate-800"><?php echo e($persenD); ?>%</span>
                                    <span class="text-[10px] text-slate-400">Lengkap</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                    <p class="text-sm font-semibold text-slate-800">Jumlah Data Lampiran per Triwulan</p>
                    <p class="text-xs text-slate-500 mb-4">Rekap seluruh sekolah - Lampiran 2a, 2b, dan 2c.</p>
                    <div wire:ignore
                         x-data="{
                            init() {
                                new Chart(this.$refs.canvas.getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: <?php echo \Illuminate\Support\Js::from($lampiranPerTriwulan->pluck('triwulan')->map(fn ($t) => 'Triwulan '.$t))->toHtml() ?>,
                                        datasets: [
                                            { label: 'Lampiran 2a', data: <?php echo \Illuminate\Support\Js::from($lampiranPerTriwulan->pluck('lampiran_2a'))->toHtml() ?>, backgroundColor: '#6366f1', borderRadius: 4 },
                                            { label: 'Lampiran 2b', data: <?php echo \Illuminate\Support\Js::from($lampiranPerTriwulan->pluck('lampiran_2b'))->toHtml() ?>, backgroundColor: '#10b981', borderRadius: 4 },
                                            { label: 'Lampiran 2c', data: <?php echo \Illuminate\Support\Js::from($lampiranPerTriwulan->pluck('lampiran_2c'))->toHtml() ?>, backgroundColor: '#f59e0b', borderRadius: 4 },
                                        ],
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        scales: {
                                            x: { grid: { display: false } },
                                            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } },
                                        },
                                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } } },
                                    },
                                });
                            }
                         }"
                         class="h-72">
                        <canvas x-ref="canvas"></canvas>
                    </div>
                </div>

                
                <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                    <p class="text-sm font-semibold text-slate-800">Sebaran Sekolah per Kecamatan</p>
                    <p class="text-xs text-slate-500 mb-4">Jumlah sekolah terdaftar di tiap kecamatan.</p>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sekolahPerKecamatan->isEmpty()): ?>
                        <p class="text-sm text-slate-400 py-8 text-center">Belum ada data sekolah.</p>
                    <?php else: ?>
                        <div wire:ignore
                             x-data="{
                                init() {
                                    new Chart(this.$refs.canvas.getContext('2d'), {
                                        type: 'bar',
                                        data: {
                                            labels: <?php echo \Illuminate\Support\Js::from($sekolahPerKecamatan->keys())->toHtml() ?>,
                                            datasets: [{
                                                label: 'Jumlah Sekolah',
                                                data: <?php echo \Illuminate\Support\Js::from($sekolahPerKecamatan->values())->toHtml() ?>,
                                                backgroundColor: '#4f46e5',
                                                borderRadius: 4,
                                                barThickness: 16,
                                            }],
                                        },
                                        options: {
                                            indexAxis: 'y',
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            scales: {
                                                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } },
                                                y: { grid: { display: false } },
                                            },
                                            plugins: { legend: { display: false } },
                                        },
                                    });
                                }
                             }"
                             style="height: <?php echo e(max(180, $sekolahPerKecamatan->count() * 40)); ?>px">
                            <canvas x-ref="canvas"></canvas>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                
                <?php echo $__env->make('livewire.dashboard.partials.selector-triwulan', ['warna' => 'blue'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'user-plus','class' => 'w-5 h-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'user-plus','class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-blue-600 leading-none"><?php echo e($registrasiOpsSudah); ?></p>
                            <p class="text-xs text-slate-500 mt-1">Admin OPS Sudah Registrasi</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'alert-circle','class' => 'w-5 h-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'alert-circle','class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-red-600 leading-none"><?php echo e($registrasiOpsBelum); ?></p>
                            <p class="text-xs text-slate-500 mt-1">Admin OPS Belum Registrasi</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'user-plus','class' => 'w-5 h-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'user-plus','class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-blue-600 leading-none"><?php echo e($registrasiBospSudah); ?></p>
                            <p class="text-xs text-slate-500 mt-1">Admin BOSP Sudah Registrasi</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'alert-circle','class' => 'w-5 h-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'alert-circle','class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-red-600 leading-none"><?php echo e($registrasiBospBelum); ?></p>
                            <p class="text-xs text-slate-500 mt-1">Admin BOSP Belum Registrasi</p>
                        </div>
                    </div>
                </div>

                <?php echo $__env->make('livewire.dashboard.partials.daftar-registrasi-admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <?php echo $__env->make('livewire.dashboard.partials.daftar-status-sekolah', [
                        'judul' => 'Validasi Pendataan BOSP',
                        'keterangan' => 'Status verval "Sesuai" - Triwulan '.$triwulanAktif.', Tahun '.$tahun.'.',
                        'daftarStatus' => $halamanValidasiBosp,
                        'labelSudah' => 'Sudah Validasi',
                        'labelBelum' => 'Belum Validasi',
                        'jumlahSudah' => $validasiBospSudah,
                        'jumlahBelum' => $validasiBospBelum,
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo $__env->make('livewire.dashboard.partials.daftar-status-sekolah', [
                        'judul' => 'Validasi Pendataan OPS',
                        'keterangan' => 'Lampiran 2a, 2b, dan 2c lengkap - Triwulan '.$triwulanAktif.', Tahun '.$tahun.'.',
                        'daftarStatus' => $halamanValidasiOps,
                        'labelSudah' => 'Sudah Validasi',
                        'labelBelum' => 'Belum Validasi',
                        'jumlahSudah' => $validasiOpsSudah,
                        'jumlahBelum' => $validasiOpsBelum,
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            <?php elseif($peran === 'admin_ops'): ?>
                <?php echo $__env->make('livewire.dashboard.partials.selector-triwulan', ['warna' => 'violet'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                
                <div class="relative overflow-hidden rounded-2xl shadow-lg bg-gradient-to-br from-violet-600 via-purple-600 to-fuchsia-600">
                    <div class="pointer-events-none absolute -top-10 -right-10 w-56 h-56 rounded-full bg-white/10 blur-2xl animate-blob-a"></div>
                    <div class="pointer-events-none absolute -bottom-16 -left-10 w-64 h-64 rounded-full bg-fuchsia-300/20 blur-2xl animate-blob-b"></div>
                    <div class="pointer-events-none absolute top-8 right-28 w-24 h-24 rounded-full bg-white/10 blur-xl animate-blob-c"></div>

                    <div class="relative p-6 sm:p-8 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-violet-100">Dashboard Pendataan OPS</p>
                            <p class="text-2xl sm:text-3xl font-bold mt-1">Rekap Seluruh Sekolah</p>
                            <p class="text-sm text-violet-100 mt-2 max-w-md">
                                Progres pengisian Lampiran 2a, 2b, dan 2c seluruh sekolah - Tahun <?php echo e($tahun); ?>, Triwulan <?php echo e($triwulanAktif); ?>.
                            </p>
                        </div>
                        <div class="shrink-0 bg-white/15 backdrop-blur rounded-xl px-7 py-4 text-center animate-fade-in-up">
                            <p class="text-4xl font-bold leading-none"><?php echo e($persenSelesaiAktif); ?>%</p>
                            <p class="text-[11px] text-violet-100 mt-1">Sekolah Selesai<br>Triwulan <?php echo e($triwulanAktif); ?></p>
                        </div>
                    </div>
                </div>

                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'building','class' => 'w-5 h-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'building','class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none"><?php echo e($totalSekolah); ?></p>
                            <p class="text-xs text-slate-500 mt-1">Total Sekolah</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'building','class' => 'w-5 h-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'building','class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none"><?php echo e($totalNegeri); ?></p>
                            <p class="text-xs text-slate-500 mt-1">Sekolah Negeri</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'building','class' => 'w-5 h-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'building','class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none"><?php echo e($totalSwasta); ?></p>
                            <p class="text-xs text-slate-500 mt-1">Sekolah Swasta</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'clipboard-check','class' => 'w-5 h-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard-check','class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none"><?php echo e($sekolahSudahAktif->count()); ?></p>
                            <p class="text-xs text-slate-500 mt-1">Sudah Selesai TW-<?php echo e($triwulanAktif); ?></p>
                        </div>
                    </div>
                </div>

                
                <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                    <p class="text-sm font-semibold text-slate-800">Pendataan OPS per Triwulan</p>
                    <p class="text-xs text-slate-500 mb-4">
                        Jumlah sekolah sudah vs belum mengerjakan (Lampiran 2a, 2b, dan 2c lengkap) - seluruh sekolah, Tahun <?php echo e($tahun); ?>.
                    </p>
                    <div wire:ignore
                         x-data="{
                            init() {
                                new Chart(this.$refs.canvas.getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: <?php echo \Illuminate\Support\Js::from($opsPerTriwulan->pluck('triwulan')->map(fn ($t) => 'Triwulan '.$t))->toHtml() ?>,
                                        datasets: [
                                            { label: 'Sudah Mengerjakan', data: <?php echo \Illuminate\Support\Js::from($opsPerTriwulan->pluck('selesai'))->toHtml() ?>, backgroundColor: '#7c3aed', borderRadius: 6 },
                                            { label: 'Belum Mengerjakan', data: <?php echo \Illuminate\Support\Js::from($opsPerTriwulan->pluck('belum'))->toHtml() ?>, backgroundColor: '#e9d5ff', borderRadius: 6 },
                                        ],
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        animation: { duration: 900, easing: 'easeOutQuart' },
                                        scales: {
                                            x: { grid: { display: false } },
                                            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } },
                                        },
                                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } } },
                                    },
                                });
                            }
                         }"
                         class="h-72">
                        <canvas x-ref="canvas"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    
                    <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                        <p class="text-sm font-semibold text-slate-800">Progres Keseluruhan</p>
                        <p class="text-xs text-slate-500 mb-4">Sekolah sudah vs belum selesai - Triwulan <?php echo e($triwulanAktif); ?>.</p>
                        <div wire:ignore
                             x-data="{
                                init() {
                                    new Chart(this.$refs.canvas.getContext('2d'), {
                                        type: 'doughnut',
                                        data: {
                                            labels: ['Sudah Selesai', 'Belum Selesai'],
                                            datasets: [{
                                                data: [<?php echo e($sekolahSudahAktif->count()); ?>, <?php echo e($sekolahBelumAktif->count()); ?>],
                                                backgroundColor: ['#7c3aed', '#f1f5f9'],
                                                borderWidth: 0,
                                            }],
                                        },
                                        options: {
                                            cutout: '72%',
                                            animation: { duration: 900, easing: 'easeOutQuart' },
                                            plugins: { legend: { display: false } },
                                        },
                                    });
                                }
                             }"
                             class="relative h-44">
                            <canvas x-ref="canvas"></canvas>
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-2xl font-bold text-slate-800"><?php echo e($persenSelesaiAktif); ?>%</span>
                                <span class="text-[10px] text-slate-400">Selesai</span>
                            </div>
                        </div>
                    </div>

                    
                    <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                        <p class="text-sm font-semibold text-slate-800">Registrasi Admin OPS</p>
                        <p class="text-xs text-slate-500 mb-4">Akun sudah disetujui Superadmin, per status sekolah.</p>
                        <div class="grid grid-cols-2 gap-4">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = ['negeri' => ['label' => 'Negeri', 'warna' => '#7c3aed'], 'swasta' => ['label' => 'Swasta', 'warna' => '#d946ef']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $statusKey => $infoStatus): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php $rekapBaris = $registrasiOps[$statusKey]; ?>
                                <div>
                                    <div wire:ignore
                                         x-data="{
                                            init() {
                                                new Chart(this.$refs.canvas.getContext('2d'), {
                                                    type: 'doughnut',
                                                    data: {
                                                        labels: ['Sudah Registrasi', 'Belum'],
                                                        datasets: [{
                                                            data: [<?php echo e($rekapBaris['sudah']); ?>, <?php echo e(max($rekapBaris['total'] - $rekapBaris['sudah'], 0)); ?>],
                                                            backgroundColor: ['<?php echo e($infoStatus['warna']); ?>', '#f1f5f9'],
                                                            borderWidth: 0,
                                                        }],
                                                    },
                                                    options: {
                                                        cutout: '68%',
                                                        animation: { duration: 900, easing: 'easeOutQuart' },
                                                        plugins: { legend: { display: false } },
                                                    },
                                                });
                                            }
                                         }"
                                         class="relative h-28">
                                        <canvas x-ref="canvas"></canvas>
                                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                            <span class="text-sm font-bold text-slate-800"><?php echo e($rekapBaris['sudah']); ?>/<?php echo e($rekapBaris['total']); ?></span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-center text-slate-500 mt-1"><?php echo e($infoStatus['label']); ?></p>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php echo $__env->make('livewire.dashboard.partials.daftar-sekolah', [
                    'label' => 'mengerjakan pendataan OPS',
                    'sekolahSudah' => $sekolahSudahAktif,
                    'sekolahBelum' => $sekolahBelumAktif,
                    'triwulanAktif' => $triwulanAktif,
                    'catatanSudah' => 'Lampiran 2a, 2b, dan 2c lengkap',
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php elseif($peran === 'admin_bosp'): ?>
                <?php echo $__env->make('livewire.dashboard.partials.selector-triwulan', ['warna' => 'blue'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                
                <div class="relative overflow-hidden rounded-2xl shadow-lg bg-gradient-to-br from-sky-600 via-blue-600 to-indigo-700">
                    <div class="pointer-events-none absolute -top-10 -right-10 w-56 h-56 rounded-full bg-white/10 blur-2xl animate-blob-a"></div>
                    <div class="pointer-events-none absolute -bottom-16 -left-10 w-64 h-64 rounded-full bg-sky-300/20 blur-2xl animate-blob-b"></div>
                    <div class="pointer-events-none absolute top-8 right-28 w-24 h-24 rounded-full bg-white/10 blur-xl animate-blob-c"></div>

                    <div class="relative p-6 sm:p-8 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-sky-100">Dashboard Pendataan BOSP</p>
                            <p class="text-2xl sm:text-3xl font-bold mt-1">Rekap Seluruh Sekolah</p>
                            <p class="text-sm text-sky-100 mt-2 max-w-md">
                                Progres pengerjaan data BOSP seluruh sekolah - Tahun <?php echo e($tahun); ?>, Triwulan <?php echo e($triwulanAktif); ?>.
                            </p>
                        </div>
                        <div class="shrink-0 bg-white/15 backdrop-blur rounded-xl px-7 py-4 text-center animate-fade-in-up">
                            <p class="text-4xl font-bold leading-none"><?php echo e($persenSelesaiAktif); ?>%</p>
                            <p class="text-[11px] text-sky-100 mt-1">Sekolah Sudah<br>Mengerjakan TW-<?php echo e($triwulanAktif); ?></p>
                        </div>
                    </div>
                </div>

                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'building','class' => 'w-5 h-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'building','class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none"><?php echo e($totalSekolah); ?></p>
                            <p class="text-xs text-slate-500 mt-1">Total Sekolah</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'clipboard-check','class' => 'w-5 h-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard-check','class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none"><?php echo e($sekolahSudahAktif->count()); ?></p>
                            <p class="text-xs text-slate-500 mt-1">Sudah Mengerjakan TW-<?php echo e($triwulanAktif); ?></p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'alert-circle','class' => 'w-5 h-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'alert-circle','class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none"><?php echo e($sekolahBelumAktif->count()); ?></p>
                            <p class="text-xs text-slate-500 mt-1">Belum Mengerjakan TW-<?php echo e($triwulanAktif); ?></p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'clipboard-check','class' => 'w-5 h-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard-check','class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none"><?php echo e($validasiBosp['negeri']['sudah'] + $validasiBosp['swasta']['sudah']); ?></p>
                            <p class="text-xs text-slate-500 mt-1">Sudah Validasi (Sesuai) TW-<?php echo e($triwulanAktif); ?></p>
                        </div>
                    </div>
                </div>

                
                <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                    <p class="text-sm font-semibold text-slate-800">Pendataan BOSP per Triwulan</p>
                    <p class="text-xs text-slate-500 mb-4">
                        Jumlah sekolah sudah vs belum mengerjakan (data di salah satu dari 11 menu per triwulan/bulan) - seluruh sekolah, Tahun <?php echo e($tahun); ?>.
                    </p>
                    <div wire:ignore
                         x-data="{
                            init() {
                                new Chart(this.$refs.canvas.getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: <?php echo \Illuminate\Support\Js::from($bospPerTriwulan->pluck('triwulan')->map(fn ($t) => 'Triwulan '.$t))->toHtml() ?>,
                                        datasets: [
                                            { label: 'Sudah Mengerjakan', data: <?php echo \Illuminate\Support\Js::from($bospPerTriwulan->pluck('selesai'))->toHtml() ?>, backgroundColor: '#2563eb', borderRadius: 6 },
                                            { label: 'Belum Mengerjakan', data: <?php echo \Illuminate\Support\Js::from($bospPerTriwulan->pluck('belum'))->toHtml() ?>, backgroundColor: '#bfdbfe', borderRadius: 6 },
                                        ],
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        animation: { duration: 900, easing: 'easeOutQuart' },
                                        scales: {
                                            x: { grid: { display: false } },
                                            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } },
                                        },
                                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } } },
                                    },
                                });
                            }
                         }"
                         class="h-72">
                        <canvas x-ref="canvas"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    
                    <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                        <p class="text-sm font-semibold text-slate-800">Registrasi Admin BOSP</p>
                        <p class="text-xs text-slate-500 mb-4">Akun sudah disetujui Superadmin, per status sekolah.</p>
                        <div class="grid grid-cols-2 gap-4">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = ['negeri' => ['label' => 'Negeri', 'warna' => '#2563eb'], 'swasta' => ['label' => 'Swasta', 'warna' => '#0ea5e9']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $statusKey => $infoStatus): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php $rekapBaris = $registrasiBosp[$statusKey]; ?>
                                <div>
                                    <div wire:ignore
                                         x-data="{
                                            init() {
                                                new Chart(this.$refs.canvas.getContext('2d'), {
                                                    type: 'doughnut',
                                                    data: {
                                                        labels: ['Sudah Registrasi', 'Belum'],
                                                        datasets: [{
                                                            data: [<?php echo e($rekapBaris['sudah']); ?>, <?php echo e(max($rekapBaris['total'] - $rekapBaris['sudah'], 0)); ?>],
                                                            backgroundColor: ['<?php echo e($infoStatus['warna']); ?>', '#f1f5f9'],
                                                            borderWidth: 0,
                                                        }],
                                                    },
                                                    options: {
                                                        cutout: '68%',
                                                        animation: { duration: 900, easing: 'easeOutQuart' },
                                                        plugins: { legend: { display: false } },
                                                    },
                                                });
                                            }
                                         }"
                                         class="relative h-28">
                                        <canvas x-ref="canvas"></canvas>
                                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                            <span class="text-sm font-bold text-slate-800"><?php echo e($rekapBaris['sudah']); ?>/<?php echo e($rekapBaris['total']); ?></span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-center text-slate-500 mt-1"><?php echo e($infoStatus['label']); ?></p>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>

                    
                    <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                        <p class="text-sm font-semibold text-slate-800">Validasi Sekolah</p>
                        <p class="text-xs text-slate-500 mb-4">Status verval "Sesuai" - Triwulan <?php echo e($triwulanAktif); ?>, per status sekolah.</p>
                        <div class="grid grid-cols-2 gap-4">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = ['negeri' => ['label' => 'Negeri', 'warna' => '#0d9488'], 'swasta' => ['label' => 'Swasta', 'warna' => '#14b8a6']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $statusKey => $infoStatus): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php $rekapBaris = $validasiBosp[$statusKey]; ?>
                                <div>
                                    <div wire:ignore
                                         x-data="{
                                            init() {
                                                new Chart(this.$refs.canvas.getContext('2d'), {
                                                    type: 'doughnut',
                                                    data: {
                                                        labels: ['Sudah Validasi', 'Belum'],
                                                        datasets: [{
                                                            data: [<?php echo e($rekapBaris['sudah']); ?>, <?php echo e(max($rekapBaris['total'] - $rekapBaris['sudah'], 0)); ?>],
                                                            backgroundColor: ['<?php echo e($infoStatus['warna']); ?>', '#f1f5f9'],
                                                            borderWidth: 0,
                                                        }],
                                                    },
                                                    options: {
                                                        cutout: '68%',
                                                        animation: { duration: 900, easing: 'easeOutQuart' },
                                                        plugins: { legend: { display: false } },
                                                    },
                                                });
                                            }
                                         }"
                                         class="relative h-28">
                                        <canvas x-ref="canvas"></canvas>
                                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                            <span class="text-sm font-bold text-slate-800"><?php echo e($rekapBaris['sudah']); ?>/<?php echo e($rekapBaris['total']); ?></span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-center text-slate-500 mt-1"><?php echo e($infoStatus['label']); ?></p>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php echo $__env->make('livewire.dashboard.partials.daftar-sekolah', [
                    'label' => 'mengerjakan pendataan BOSP',
                    'sekolahSudah' => $sekolahSudahAktif,
                    'sekolahBelum' => $sekolahBelumAktif,
                    'triwulanAktif' => $triwulanAktif,
                    'catatanSudah' => 'Ada data di salah satu dari 11 menu per triwulan/bulan',
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <div>
                <p class="text-sm font-semibold text-slate-800 mb-3">Menu Cepat</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('akses-profil-sekolah')): ?>
                        <a href="<?php echo e(route('profil-sekolah.index')); ?>" wire:navigate class="block bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                            <p class="text-sm text-slate-500">Kelola</p>
                            <p class="text-lg font-semibold text-slate-800">Profil Sekolah</p>
                        </a>
                    <?php endif; ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('akses-pendataan-ops')): ?>
                        <a href="<?php echo e(route('pendataan-ops.index')); ?>" wire:navigate class="block bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                            <p class="text-sm text-slate-500">Kelola</p>
                            <p class="text-lg font-semibold text-slate-800">Pendataan OPS</p>
                        </a>
                    <?php endif; ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('akses-pendataan-bosp')): ?>
                        <a href="<?php echo e(route('pendataan-bosp.index')); ?>" wire:navigate class="block bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                            <p class="text-sm text-slate-500">Kelola</p>
                            <p class="text-lg font-semibold text-slate-800">Pendataan BOSP</p>
                        </a>
                    <?php endif; ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('akses-pengguna')): ?>
                        <a href="<?php echo e(route('pengguna.index')); ?>" wire:navigate class="block bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                            <p class="text-sm text-slate-500">Kelola</p>
                            <p class="text-lg font-semibold text-slate-800">Pengguna</p>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH D:\Aplikasi SRCBD_OPS_BOSP\srcbd-ops-bosp-source\resources\views/livewire/dashboard/index.blade.php ENDPATH**/ ?>