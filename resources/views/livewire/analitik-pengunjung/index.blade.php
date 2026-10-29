<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Analitik Pengunjung') }}
    </h2>
</x-slot>

<div>
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ============ FILTER PERIODE ============ --}}
            <div class="bg-white shadow sm:rounded-lg p-4 sm:p-6 flex flex-wrap items-center gap-3">
                <span class="text-sm font-medium text-slate-600">Periode:</span>
                @foreach ([7 => '7 Hari Terakhir', 30 => '30 Hari Terakhir', 90 => '90 Hari Terakhir'] as $nilai => $label)
                    <button wire:click="$set('periode', {{ $nilai }})"
                            class="px-3 py-1.5 rounded-full text-sm font-medium transition @if ($periode === $nilai) bg-indigo-600 text-white @else bg-slate-100 text-slate-600 hover:bg-slate-200 @endif">
                        {{ $label }}
                    </button>
                @endforeach
                <span class="text-xs text-slate-400 ml-auto">
                    Data dirangkum dari menu "Pengguna &gt; Riwayat Login" dan "Cek Database dan Aplikasi &gt; Log Akses Data".
                </span>
            </div>

            {{-- ============ KARTU RINGKASAN ============ --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70">
                    <p class="text-xs font-medium text-slate-500">Total Login</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($ringkasan['totalLogin']) }}</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70">
                    <p class="text-xs font-medium text-slate-500">Pengunjung Unik (Akun)</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($ringkasan['pengunjungUnik']) }}</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70">
                    <p class="text-xs font-medium text-slate-500">Total Aktivitas (Buka + Unduh)</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($ringkasan['totalAktivitas']) }}</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200/70">
                    <p class="text-xs font-medium text-slate-500">Rata-rata Login / Hari</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ $ringkasan['rataRataLoginPerHari'] }}</p>
                </div>
            </div>

            {{-- ============ TREN LOGIN HARIAN (LINE) ============ --}}
            <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                <p class="text-sm font-semibold text-slate-800">Tren Login Harian</p>
                <p class="text-xs text-slate-500 mb-4">Siapa saja yang login ke aplikasi, per hari.</p>
                <div wire:ignore
                     x-data="{
                        init() {
                            new Chart(this.$refs.canvas.getContext('2d'), {
                                type: 'line',
                                data: {
                                    labels: @js(array_column($trendLoginHarian, 'label')),
                                    datasets: [{
                                        label: 'Login',
                                        data: @js(array_column($trendLoginHarian, 'jumlah')),
                                        borderColor: '#6366f1',
                                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                        fill: true,
                                        tension: 0.3,
                                        pointRadius: 2,
                                    }],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        x: { grid: { display: false } },
                                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } },
                                    },
                                    plugins: { legend: { display: false } },
                                },
                            });
                        }
                     }"
                     class="h-64">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            {{-- ============ LOGIN PER LEVEL AKSES + TOP SEKOLAH ============ --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                    <p class="text-sm font-semibold text-slate-800">Siapa yang Login</p>
                    <p class="text-xs text-slate-500 mb-4">Pembagian login berdasarkan level akses.</p>
                    @if (count($loginPerLevel) === 0)
                        <p class="text-sm text-slate-400 py-8 text-center">Belum ada data login pada periode ini.</p>
                    @else
                        <div wire:ignore
                             x-data="{
                                init() {
                                    new Chart(this.$refs.canvas.getContext('2d'), {
                                        type: 'doughnut',
                                        data: {
                                            labels: @js(array_column($loginPerLevel, 'label')),
                                            datasets: [{
                                                data: @js(array_column($loginPerLevel, 'jumlah')),
                                                backgroundColor: @js(array_column($loginPerLevel, 'warna')),
                                                borderWidth: 0,
                                            }],
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            cutout: '65%',
                                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } } },
                                        },
                                    });
                                }
                             }"
                             class="h-56">
                            <canvas x-ref="canvas"></canvas>
                        </div>
                    @endif
                </div>

                <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                    <p class="text-sm font-semibold text-slate-800">Asal Sekolah/Instansi</p>
                    <p class="text-xs text-slate-500 mb-4">10 sekolah dengan login terbanyak (Superadmin tidak disertakan - tidak terikat 1 sekolah).</p>
                    @if (count($topSekolah) === 0)
                        <p class="text-sm text-slate-400 py-8 text-center">Belum ada data login sekolah pada periode ini.</p>
                    @else
                        <div wire:ignore
                             x-data="{
                                init() {
                                    new Chart(this.$refs.canvas.getContext('2d'), {
                                        type: 'bar',
                                        data: {
                                            labels: @js(array_column($topSekolah, 'nama_sekolah')),
                                            datasets: [{
                                                label: 'Login',
                                                data: @js(array_column($topSekolah, 'jumlah')),
                                                backgroundColor: '#10b981',
                                                borderRadius: 4,
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
                             class="h-56">
                            <canvas x-ref="canvas"></canvas>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ============ ASAL LOKASI GEOGRAFIS ============ --}}
            <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                <p class="text-sm font-semibold text-slate-800">Asal Lokasi Geografis</p>
                <p class="text-xs text-slate-500 mb-4">
                    Titik koordinat diambil dari izin lokasi browser saat login (dibulatkan ± 100 meter).
                    {{ $cakupanLokasi['adaLokasi'] }} dari {{ $cakupanLokasi['total'] }} login ({{ $cakupanLokasi['persen'] }}%) punya data lokasi -
                    sisanya menolak izin lokasi atau browsernya tidak mendukung.
                </p>
                @if (count($titikLokasi) === 0)
                    <p class="text-sm text-slate-400 py-8 text-center">Belum ada data lokasi pada periode ini.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                                    <th class="py-2 pr-4">Koordinat</th>
                                    <th class="py-2 pr-4">Jumlah Login</th>
                                    <th class="py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($titikLokasi as $titik)
                                    <tr class="border-b border-slate-100">
                                        <td class="py-2 pr-4 text-slate-700">{{ $titik['latitude'] }}, {{ $titik['longitude'] }}</td>
                                        <td class="py-2 pr-4 text-slate-700">{{ number_format($titik['jumlah']) }}</td>
                                        <td class="py-2">
                                            <a href="https://www.google.com/maps?q={{ $titik['latitude'] }},{{ $titik['longitude'] }}"
                                               target="_blank" rel="noopener"
                                               class="text-indigo-600 hover:underline text-xs">Lihat di Peta &rarr;</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- ============ ASAL PERANGKAT/BROWSER + MENU TERPOPULER ============ --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                    <p class="text-sm font-semibold text-slate-800">Asal Perangkat/Browser</p>
                    <p class="text-xs text-slate-500 mb-4">Diuraikan otomatis dari User-Agent saat membuka halaman/mengunduh data.</p>
                    @if (count($perangkat) === 0)
                        <p class="text-sm text-slate-400 py-8 text-center">Belum ada data aktivitas pada periode ini.</p>
                    @else
                        <div wire:ignore
                             x-data="{
                                init() {
                                    new Chart(this.$refs.canvas.getContext('2d'), {
                                        type: 'bar',
                                        data: {
                                            labels: @js(array_column($perangkat, 'label')),
                                            datasets: [{
                                                label: 'Aktivitas',
                                                data: @js(array_column($perangkat, 'jumlah')),
                                                backgroundColor: '#f59e0b',
                                                borderRadius: 4,
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
                             class="h-56">
                            <canvas x-ref="canvas"></canvas>
                        </div>
                    @endif
                </div>

                <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                    <p class="text-sm font-semibold text-slate-800">Menu Paling Sering Dibuka</p>
                    <p class="text-xs text-slate-500 mb-4">10 menu/halaman dengan jumlah "dibuka" terbanyak.</p>
                    @if (count($topMenuDibuka) === 0)
                        <p class="text-sm text-slate-400 py-8 text-center">Belum ada data aktivitas pada periode ini.</p>
                    @else
                        <div wire:ignore
                             x-data="{
                                init() {
                                    new Chart(this.$refs.canvas.getContext('2d'), {
                                        type: 'bar',
                                        data: {
                                            labels: @js(array_column($topMenuDibuka, 'nama_menu')),
                                            datasets: [{
                                                label: 'Dibuka',
                                                data: @js(array_column($topMenuDibuka, 'jumlah')),
                                                backgroundColor: '#6366f1',
                                                borderRadius: 4,
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
                             class="h-56">
                            <canvas x-ref="canvas"></canvas>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ============ DATA PALING SERING DIUNDUH ============ --}}
            <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200/70">
                <p class="text-sm font-semibold text-slate-800">Data Paling Sering Diunduh</p>
                <p class="text-xs text-slate-500 mb-4">10 menu/data dengan jumlah "diunduh" terbanyak - dipisah dari sekadar membuka halaman.</p>
                @if (count($topMenuDiunduh) === 0)
                    <p class="text-sm text-slate-400 py-8 text-center">Belum ada data unduhan pada periode ini.</p>
                @else
                    <div wire:ignore
                         x-data="{
                            init() {
                                new Chart(this.$refs.canvas.getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: @js(array_column($topMenuDiunduh, 'nama_menu')),
                                        datasets: [{
                                            label: 'Diunduh',
                                            data: @js(array_column($topMenuDiunduh, 'jumlah')),
                                            backgroundColor: '#10b981',
                                            borderRadius: 4,
                                        }],
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        scales: {
                                            x: { grid: { display: false }, ticks: { autoSkip: false, maxRotation: 30, minRotation: 0 } },
                                            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } },
                                        },
                                        plugins: { legend: { display: false } },
                                    },
                            });
                            }
                         }"
                         class="h-72">
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
