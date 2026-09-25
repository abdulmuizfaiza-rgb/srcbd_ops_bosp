<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Dashboard') }}
    </h2>
</x-slot>

<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Sapaan --}}
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-500 overflow-hidden shadow-sm rounded-xl">
                <div class="p-6 sm:p-8 text-white">
                    <p class="text-lg font-semibold">
                        Selamat datang, {{ auth()->user()->display_name }}
                    </p>
                    <p class="text-sm text-indigo-100 mt-1">
                        Anda masuk sebagai <span class="font-medium text-white">{{ auth()->user()->level_akses_label }}</span>
                        pada Aplikasi OPS_BOSP SR CBD.
                    </p>
                </div>
            </div>

            @if ($peran === 'superadmin')
                {{-- ============ KPI CARDS ============ --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <x-icon name="building" class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none">{{ $totalSekolah }}</p>
                            <p class="text-xs text-slate-500 mt-1">Total Sekolah</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                            <x-icon name="building" class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none">{{ $totalNegeri }}</p>
                            <p class="text-xs text-slate-500 mt-1">Sekolah Negeri</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                            <x-icon name="building" class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none">{{ $totalSwasta }}</p>
                            <p class="text-xs text-slate-500 mt-1">Sekolah Swasta</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <x-icon name="users" class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none">{{ $totalPengguna }}</p>
                            <p class="text-xs text-slate-500 mt-1">Total Pengguna</p>
                        </div>
                    </div>
                </div>

                {{-- ============ KELENGKAPAN DATA (DONUT) ============ --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    @php
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
                    @endphp

                    @foreach ($donutKelengkapan as $d)
                        @php
                            $totalD = $d['lengkap'] + $d['belum'];
                            $persenD = $totalD > 0 ? round(($d['lengkap'] / $totalD) * 100) : 0;
                        @endphp
                        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70">
                            <p class="text-sm font-semibold text-slate-800">{{ $d['judul'] }}</p>
                            <p class="text-xs text-slate-500 mb-3">{{ $d['lengkap'] }} dari {{ $totalD }} sekolah</p>
                            <div wire:ignore
                                 x-data="{
                                    init() {
                                        new Chart(this.$refs.canvas.getContext('2d'), {
                                            type: 'doughnut',
                                            data: {
                                                labels: ['Lengkap', 'Belum Lengkap'],
                                                datasets: [{
                                                    data: [{{ $d['lengkap'] }}, {{ $d['belum'] }}],
                                                    backgroundColor: ['{{ $d['warna'] }}', '#e2e8f0'],
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
                                    <span class="text-xl font-bold text-slate-800">{{ $persenD }}%</span>
                                    <span class="text-[10px] text-slate-400">Lengkap</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- ============ LAMPIRAN PER TRIWULAN (BAR) ============ --}}
                <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                    <p class="text-sm font-semibold text-slate-800">Jumlah Data Lampiran per Triwulan</p>
                    <p class="text-xs text-slate-500 mb-4">Rekap seluruh sekolah - Lampiran 2a, 2b, dan 2c.</p>
                    <div wire:ignore
                         x-data="{
                            init() {
                                new Chart(this.$refs.canvas.getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: @js($lampiranPerTriwulan->pluck('triwulan')->map(fn ($t) => 'Triwulan '.$t)),
                                        datasets: [
                                            { label: 'Lampiran 2a', data: @js($lampiranPerTriwulan->pluck('lampiran_2a')), backgroundColor: '#6366f1', borderRadius: 4 },
                                            { label: 'Lampiran 2b', data: @js($lampiranPerTriwulan->pluck('lampiran_2b')), backgroundColor: '#10b981', borderRadius: 4 },
                                            { label: 'Lampiran 2c', data: @js($lampiranPerTriwulan->pluck('lampiran_2c')), backgroundColor: '#f59e0b', borderRadius: 4 },
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

                {{-- ============ SEBARAN SEKOLAH PER KECAMATAN (BAR HORIZONTAL) ============ --}}
                <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                    <p class="text-sm font-semibold text-slate-800">Sebaran Sekolah per Kecamatan</p>
                    <p class="text-xs text-slate-500 mb-4">Jumlah sekolah terdaftar di tiap kecamatan.</p>
                    @if ($sekolahPerKecamatan->isEmpty())
                        <p class="text-sm text-slate-400 py-8 text-center">Belum ada data sekolah.</p>
                    @else
                        <div wire:ignore
                             x-data="{
                                init() {
                                    new Chart(this.$refs.canvas.getContext('2d'), {
                                        type: 'bar',
                                        data: {
                                            labels: @js($sekolahPerKecamatan->keys()),
                                            datasets: [{
                                                label: 'Jumlah Sekolah',
                                                data: @js($sekolahPerKecamatan->values()),
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
                             style="height: {{ max(180, $sekolahPerKecamatan->count() * 40) }}px">
                            <canvas x-ref="canvas"></canvas>
                        </div>
                    @endif
                </div>

                {{-- ============ ROUND KEEMPAT BELAS: REGISTRASI & VALIDASI (BARU) ============ --}}
                {{-- Selector Tahun/Triwulan HANYA memengaruhi 2 widget Validasi di bawah
                     (Registrasi Admin OPS/BOSP tidak terikat triwulan) - lihat docblock
                     dataSuperadmin() utk detail. --}}
                @include('livewire.dashboard.partials.selector-triwulan', ['warna' => 'blue'])

                {{-- ============ REGISTRASI ADMIN OPS & ADMIN BOSP ============ --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                            <x-icon name="user-plus" class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-blue-600 leading-none">{{ $registrasiOpsSudah }}</p>
                            <p class="text-xs text-slate-500 mt-1">Admin OPS Sudah Registrasi</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                            <x-icon name="alert-circle" class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-red-600 leading-none">{{ $registrasiOpsBelum }}</p>
                            <p class="text-xs text-slate-500 mt-1">Admin OPS Belum Registrasi</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                            <x-icon name="user-plus" class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-blue-600 leading-none">{{ $registrasiBospSudah }}</p>
                            <p class="text-xs text-slate-500 mt-1">Admin BOSP Sudah Registrasi</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                            <x-icon name="alert-circle" class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-red-600 leading-none">{{ $registrasiBospBelum }}</p>
                            <p class="text-xs text-slate-500 mt-1">Admin BOSP Belum Registrasi</p>
                        </div>
                    </div>
                </div>

                @include('livewire.dashboard.partials.daftar-registrasi-admin')

                {{-- ============ VALIDASI PENDATAAN BOSP & OPS (2 WIDGET TERPISAH) ============ --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    @include('livewire.dashboard.partials.daftar-status-sekolah', [
                        'judul' => 'Validasi Pendataan BOSP',
                        'keterangan' => 'Status verval "Sesuai" - Triwulan '.$triwulanAktif.', Tahun '.$tahun.'.',
                        'daftarStatus' => $halamanValidasiBosp,
                        'labelSudah' => 'Sudah Validasi',
                        'labelBelum' => 'Belum Validasi',
                        'jumlahSudah' => $validasiBospSudah,
                        'jumlahBelum' => $validasiBospBelum,
                    ])
                    @include('livewire.dashboard.partials.daftar-status-sekolah', [
                        'judul' => 'Validasi Pendataan OPS',
                        'keterangan' => 'Lampiran 2a, 2b, dan 2c lengkap - Triwulan '.$triwulanAktif.', Tahun '.$tahun.'.',
                        'daftarStatus' => $halamanValidasiOps,
                        'labelSudah' => 'Sudah Validasi',
                        'labelBelum' => 'Belum Validasi',
                        'jumlahSudah' => $validasiOpsSudah,
                        'jumlahBelum' => $validasiOpsBelum,
                    ])
                </div>
            @elseif ($peran === 'admin_ops')
                @include('livewire.dashboard.partials.selector-triwulan', ['warna' => 'violet'])

                {{-- ============ HERO: DASHBOARD PENDATAAN OPS ============ --}}
                <div class="relative overflow-hidden rounded-2xl shadow-lg bg-gradient-to-br from-violet-600 via-purple-600 to-fuchsia-600">
                    <div class="pointer-events-none absolute -top-10 -right-10 w-56 h-56 rounded-full bg-white/10 blur-2xl animate-blob-a"></div>
                    <div class="pointer-events-none absolute -bottom-16 -left-10 w-64 h-64 rounded-full bg-fuchsia-300/20 blur-2xl animate-blob-b"></div>
                    <div class="pointer-events-none absolute top-8 right-28 w-24 h-24 rounded-full bg-white/10 blur-xl animate-blob-c"></div>

                    <div class="relative p-6 sm:p-8 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-violet-100">Dashboard Pendataan OPS</p>
                            <p class="text-2xl sm:text-3xl font-bold mt-1">Rekap Seluruh Sekolah</p>
                            <p class="text-sm text-violet-100 mt-2 max-w-md">
                                Progres pengisian Lampiran 2a, 2b, dan 2c seluruh sekolah - Tahun {{ $tahun }}, Triwulan {{ $triwulanAktif }}.
                            </p>
                        </div>
                        <div class="shrink-0 bg-white/15 backdrop-blur rounded-xl px-7 py-4 text-center animate-fade-in-up">
                            <p class="text-4xl font-bold leading-none">{{ $persenSelesaiAktif }}%</p>
                            <p class="text-[11px] text-violet-100 mt-1">Sekolah Selesai<br>Triwulan {{ $triwulanAktif }}</p>
                        </div>
                    </div>
                </div>

                {{-- ============ KPI CARDS ============ --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center">
                            <x-icon name="building" class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none">{{ $totalSekolah }}</p>
                            <p class="text-xs text-slate-500 mt-1">Total Sekolah</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                            <x-icon name="building" class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none">{{ $totalNegeri }}</p>
                            <p class="text-xs text-slate-500 mt-1">Sekolah Negeri</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                            <x-icon name="building" class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none">{{ $totalSwasta }}</p>
                            <p class="text-xs text-slate-500 mt-1">Sekolah Swasta</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <x-icon name="clipboard-check" class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none">{{ $sekolahSudahAktif->count() }}</p>
                            <p class="text-xs text-slate-500 mt-1">Sudah Selesai TW-{{ $triwulanAktif }}</p>
                        </div>
                    </div>
                </div>

                {{-- ============ PENDATAAN OPS PER TRIWULAN (BAR) ============ --}}
                <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                    <p class="text-sm font-semibold text-slate-800">Pendataan OPS per Triwulan</p>
                    <p class="text-xs text-slate-500 mb-4">
                        Jumlah sekolah sudah vs belum mengerjakan (Lampiran 2a, 2b, dan 2c lengkap) - seluruh sekolah, Tahun {{ $tahun }}.
                    </p>
                    <div wire:ignore
                         x-data="{
                            init() {
                                new Chart(this.$refs.canvas.getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: @js($opsPerTriwulan->pluck('triwulan')->map(fn ($t) => 'Triwulan '.$t)),
                                        datasets: [
                                            { label: 'Sudah Mengerjakan', data: @js($opsPerTriwulan->pluck('selesai')), backgroundColor: '#7c3aed', borderRadius: 6 },
                                            { label: 'Belum Mengerjakan', data: @js($opsPerTriwulan->pluck('belum')), backgroundColor: '#e9d5ff', borderRadius: 6 },
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
                    {{-- ============ PROGRES KESELURUHAN TW AKTIF (DONUT) ============ --}}
                    <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                        <p class="text-sm font-semibold text-slate-800">Progres Keseluruhan</p>
                        <p class="text-xs text-slate-500 mb-4">Sekolah sudah vs belum selesai - Triwulan {{ $triwulanAktif }}.</p>
                        <div wire:ignore
                             x-data="{
                                init() {
                                    new Chart(this.$refs.canvas.getContext('2d'), {
                                        type: 'doughnut',
                                        data: {
                                            labels: ['Sudah Selesai', 'Belum Selesai'],
                                            datasets: [{
                                                data: [{{ $sekolahSudahAktif->count() }}, {{ $sekolahBelumAktif->count() }}],
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
                                <span class="text-2xl font-bold text-slate-800">{{ $persenSelesaiAktif }}%</span>
                                <span class="text-[10px] text-slate-400">Selesai</span>
                            </div>
                        </div>
                    </div>

                    {{-- ============ REGISTRASI ADMIN OPS (DONUT NEGERI/SWASTA) ============ --}}
                    <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                        <p class="text-sm font-semibold text-slate-800">Registrasi Admin OPS</p>
                        <p class="text-xs text-slate-500 mb-4">Akun sudah disetujui Superadmin, per status sekolah.</p>
                        <div class="grid grid-cols-2 gap-4">
                            @foreach (['negeri' => ['label' => 'Negeri', 'warna' => '#7c3aed'], 'swasta' => ['label' => 'Swasta', 'warna' => '#d946ef']] as $statusKey => $infoStatus)
                                @php $rekapBaris = $registrasiOps[$statusKey]; @endphp
                                <div>
                                    <div wire:ignore
                                         x-data="{
                                            init() {
                                                new Chart(this.$refs.canvas.getContext('2d'), {
                                                    type: 'doughnut',
                                                    data: {
                                                        labels: ['Sudah Registrasi', 'Belum'],
                                                        datasets: [{
                                                            data: [{{ $rekapBaris['sudah'] }}, {{ max($rekapBaris['total'] - $rekapBaris['sudah'], 0) }}],
                                                            backgroundColor: ['{{ $infoStatus['warna'] }}', '#f1f5f9'],
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
                                            <span class="text-sm font-bold text-slate-800">{{ $rekapBaris['sudah'] }}/{{ $rekapBaris['total'] }}</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-center text-slate-500 mt-1">{{ $infoStatus['label'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                @include('livewire.dashboard.partials.daftar-sekolah', [
                    'label' => 'mengerjakan pendataan OPS',
                    'sekolahSudah' => $sekolahSudahAktif,
                    'sekolahBelum' => $sekolahBelumAktif,
                    'triwulanAktif' => $triwulanAktif,
                    'catatanSudah' => 'Lampiran 2a, 2b, dan 2c lengkap',
                ])
            @elseif ($peran === 'admin_bosp')
                @include('livewire.dashboard.partials.selector-triwulan', ['warna' => 'blue'])

                {{-- ============ HERO: DASHBOARD PENDATAAN BOSP ============ --}}
                <div class="relative overflow-hidden rounded-2xl shadow-lg bg-gradient-to-br from-sky-600 via-blue-600 to-indigo-700">
                    <div class="pointer-events-none absolute -top-10 -right-10 w-56 h-56 rounded-full bg-white/10 blur-2xl animate-blob-a"></div>
                    <div class="pointer-events-none absolute -bottom-16 -left-10 w-64 h-64 rounded-full bg-sky-300/20 blur-2xl animate-blob-b"></div>
                    <div class="pointer-events-none absolute top-8 right-28 w-24 h-24 rounded-full bg-white/10 blur-xl animate-blob-c"></div>

                    <div class="relative p-6 sm:p-8 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-sky-100">Dashboard Pendataan BOSP</p>
                            <p class="text-2xl sm:text-3xl font-bold mt-1">Rekap Seluruh Sekolah</p>
                            <p class="text-sm text-sky-100 mt-2 max-w-md">
                                Progres pengerjaan data BOSP seluruh sekolah - Tahun {{ $tahun }}, Triwulan {{ $triwulanAktif }}.
                            </p>
                        </div>
                        <div class="shrink-0 bg-white/15 backdrop-blur rounded-xl px-7 py-4 text-center animate-fade-in-up">
                            <p class="text-4xl font-bold leading-none">{{ $persenSelesaiAktif }}%</p>
                            <p class="text-[11px] text-sky-100 mt-1">Sekolah Sudah<br>Mengerjakan TW-{{ $triwulanAktif }}</p>
                        </div>
                    </div>
                </div>

                {{-- ============ KPI CARDS ============ --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                            <x-icon name="building" class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none">{{ $totalSekolah }}</p>
                            <p class="text-xs text-slate-500 mt-1">Total Sekolah</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <x-icon name="clipboard-check" class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none">{{ $sekolahSudahAktif->count() }}</p>
                            <p class="text-xs text-slate-500 mt-1">Sudah Mengerjakan TW-{{ $triwulanAktif }}</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                            <x-icon name="alert-circle" class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none">{{ $sekolahBelumAktif->count() }}</p>
                            <p class="text-xs text-slate-500 mt-1">Belum Mengerjakan TW-{{ $triwulanAktif }}</p>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70 flex items-center gap-4">
                        <div class="shrink-0 w-11 h-11 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <x-icon name="clipboard-check" class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-800 leading-none">{{ $validasiBosp['negeri']['sudah'] + $validasiBosp['swasta']['sudah'] }}</p>
                            <p class="text-xs text-slate-500 mt-1">Sudah Validasi (Sesuai) TW-{{ $triwulanAktif }}</p>
                        </div>
                    </div>
                </div>

                {{-- ============ PENDATAAN BOSP PER TRIWULAN (BAR) ============ --}}
                <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                    <p class="text-sm font-semibold text-slate-800">Pendataan BOSP per Triwulan</p>
                    <p class="text-xs text-slate-500 mb-4">
                        Jumlah sekolah sudah vs belum mengerjakan (data di salah satu dari 11 menu per triwulan/bulan) - seluruh sekolah, Tahun {{ $tahun }}.
                    </p>
                    <div wire:ignore
                         x-data="{
                            init() {
                                new Chart(this.$refs.canvas.getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: @js($bospPerTriwulan->pluck('triwulan')->map(fn ($t) => 'Triwulan '.$t)),
                                        datasets: [
                                            { label: 'Sudah Mengerjakan', data: @js($bospPerTriwulan->pluck('selesai')), backgroundColor: '#2563eb', borderRadius: 6 },
                                            { label: 'Belum Mengerjakan', data: @js($bospPerTriwulan->pluck('belum')), backgroundColor: '#bfdbfe', borderRadius: 6 },
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
                    {{-- ============ REGISTRASI ADMIN BOSP (DONUT NEGERI/SWASTA) ============ --}}
                    <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                        <p class="text-sm font-semibold text-slate-800">Registrasi Admin BOSP</p>
                        <p class="text-xs text-slate-500 mb-4">Akun sudah disetujui Superadmin, per status sekolah.</p>
                        <div class="grid grid-cols-2 gap-4">
                            @foreach (['negeri' => ['label' => 'Negeri', 'warna' => '#2563eb'], 'swasta' => ['label' => 'Swasta', 'warna' => '#0ea5e9']] as $statusKey => $infoStatus)
                                @php $rekapBaris = $registrasiBosp[$statusKey]; @endphp
                                <div>
                                    <div wire:ignore
                                         x-data="{
                                            init() {
                                                new Chart(this.$refs.canvas.getContext('2d'), {
                                                    type: 'doughnut',
                                                    data: {
                                                        labels: ['Sudah Registrasi', 'Belum'],
                                                        datasets: [{
                                                            data: [{{ $rekapBaris['sudah'] }}, {{ max($rekapBaris['total'] - $rekapBaris['sudah'], 0) }}],
                                                            backgroundColor: ['{{ $infoStatus['warna'] }}', '#f1f5f9'],
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
                                            <span class="text-sm font-bold text-slate-800">{{ $rekapBaris['sudah'] }}/{{ $rekapBaris['total'] }}</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-center text-slate-500 mt-1">{{ $infoStatus['label'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ============ VALIDASI SEKOLAH TW AKTIF (DONUT NEGERI/SWASTA) ============ --}}
                    <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                        <p class="text-sm font-semibold text-slate-800">Validasi Sekolah</p>
                        <p class="text-xs text-slate-500 mb-4">Status verval "Sesuai" - Triwulan {{ $triwulanAktif }}, per status sekolah.</p>
                        <div class="grid grid-cols-2 gap-4">
                            @foreach (['negeri' => ['label' => 'Negeri', 'warna' => '#0d9488'], 'swasta' => ['label' => 'Swasta', 'warna' => '#14b8a6']] as $statusKey => $infoStatus)
                                @php $rekapBaris = $validasiBosp[$statusKey]; @endphp
                                <div>
                                    <div wire:ignore
                                         x-data="{
                                            init() {
                                                new Chart(this.$refs.canvas.getContext('2d'), {
                                                    type: 'doughnut',
                                                    data: {
                                                        labels: ['Sudah Validasi', 'Belum'],
                                                        datasets: [{
                                                            data: [{{ $rekapBaris['sudah'] }}, {{ max($rekapBaris['total'] - $rekapBaris['sudah'], 0) }}],
                                                            backgroundColor: ['{{ $infoStatus['warna'] }}', '#f1f5f9'],
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
                                            <span class="text-sm font-bold text-slate-800">{{ $rekapBaris['sudah'] }}/{{ $rekapBaris['total'] }}</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-center text-slate-500 mt-1">{{ $infoStatus['label'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                @include('livewire.dashboard.partials.daftar-sekolah', [
                    'label' => 'mengerjakan pendataan BOSP',
                    'sekolahSudah' => $sekolahSudahAktif,
                    'sekolahBelum' => $sekolahBelumAktif,
                    'triwulanAktif' => $triwulanAktif,
                    'catatanSudah' => 'Ada data di salah satu dari 11 menu per triwulan/bulan',
                ])
            @endif

            {{-- ============ MENU CEPAT ============ --}}
            <div>
                <p class="text-sm font-semibold text-slate-800 mb-3">Menu Cepat</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @can('akses-profil-sekolah')
                        <a href="{{ route('profil-sekolah.index') }}" wire:navigate class="block bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                            <p class="text-sm text-slate-500">Kelola</p>
                            <p class="text-lg font-semibold text-slate-800">Profil Sekolah</p>
                        </a>
                    @endcan
                    @can('akses-pendataan-ops')
                        <a href="{{ route('pendataan-ops.index') }}" wire:navigate class="block bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                            <p class="text-sm text-slate-500">Kelola</p>
                            <p class="text-lg font-semibold text-slate-800">Pendataan OPS</p>
                        </a>
                    @endcan
                    @can('akses-pendataan-bosp')
                        <a href="{{ route('pendataan-bosp.index') }}" wire:navigate class="block bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                            <p class="text-sm text-slate-500">Kelola</p>
                            <p class="text-lg font-semibold text-slate-800">Pendataan BOSP</p>
                        </a>
                    @endcan
                    @can('akses-pengguna')
                        <a href="{{ route('pengguna.index') }}" wire:navigate class="block bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                            <p class="text-sm text-slate-500">Kelola</p>
                            <p class="text-lg font-semibold text-slate-800">Pengguna</p>
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
