<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Cek Database dan Aplikasi') }}
    </h2>
</x-slot>

<div>
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                {{-- Tab --}}
                <div class="border-b border-slate-200 px-4 sm:px-8 pt-4">
                    <nav class="flex gap-6 -mb-px">
                        <button wire:click="pindahTab('integritas')" class="pb-3 text-sm font-medium border-b-2 transition @if ($tab === 'integritas') border-blue-600 text-blue-600 @else border-transparent text-slate-500 hover:text-slate-700 @endif">
                            Integritas Data
                        </button>
                        <button wire:click="pindahTab('login_gagal')" class="pb-3 text-sm font-medium border-b-2 transition @if ($tab === 'login_gagal') border-blue-600 text-blue-600 @else border-transparent text-slate-500 hover:text-slate-700 @endif">
                            Percobaan Login Gagal
                        </button>
                        <button wire:click="pindahTab('akses_data')" class="pb-3 text-sm font-medium border-b-2 transition @if ($tab === 'akses_data') border-blue-600 text-blue-600 @else border-transparent text-slate-500 hover:text-slate-700 @endif">
                            Log Akses Data
                        </button>
                        <button wire:click="pindahTab('kecocokan')" class="pb-3 text-sm font-medium border-b-2 transition @if ($tab === 'kecocokan') border-blue-600 text-blue-600 @else border-transparent text-slate-500 hover:text-slate-700 @endif">
                            Cek Kecocokan Data
                        </button>
                    </nav>
                </div>

                <div class="p-4 sm:p-8">
                    @if ($tab === 'integritas')
                        <p class="text-sm text-slate-500 mb-6">Membandingkan data yang tersimpan di database dengan aturan yang berlaku di aplikasi. Idealnya ketiga bagian di bawah ini selalu kosong - kalau ada temuan, gunakan tombol "Bersihkan Otomatis" untuk menyamakan database dengan aplikasi.</p>

                        @foreach ([
                            'yatim' => ['judul' => 'Data Yatim (Induk Sekolah Sudah Dihapus)', 'temuan' => $temuanYatim],
                            'terkunci' => ['judul' => 'Data Terkunci di Layar tapi Masih Tersimpan', 'temuan' => $temuanTerkunci],
                            'duplikat' => ['judul' => 'Data Duplikat', 'temuan' => $temuanDuplikat],
                        ] as $kategori => $bagian)
                            <div class="mb-8 last:mb-0">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-sm font-semibold text-slate-700">{{ $bagian['judul'] }}</h3>
                                    @if (count($bagian['temuan']) > 0)
                                        <x-danger-button wire:click="konfirmasiBersihkan('{{ $kategori }}')" class="!px-3 !py-1.5 !text-[10px]">
                                            <x-icon name="trash" class="w-3.5 h-3.5 mr-1" />Bersihkan Otomatis
                                        </x-danger-button>
                                    @endif
                                </div>

                                @if (count($bagian['temuan']) === 0)
                                    <div class="text-sm text-emerald-600 bg-emerald-50 border border-emerald-200 rounded-lg px-4 py-3">
                                        Tidak ditemukan masalah - data sudah cocok antara aplikasi dan database.
                                    </div>
                                @else
                                    <div class="overflow-x-auto border border-slate-200 rounded-lg">
                                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                                            <thead class="bg-slate-50">
                                                <tr class="text-left text-slate-500">
                                                    <th class="px-3 py-2">Modul</th>
                                                    <th class="px-3 py-2">Keterangan</th>
                                                    <th class="px-3 py-2 text-right">Jumlah Baris</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 bg-white">
                                                @foreach ($bagian['temuan'] as $satu)
                                                    <tr>
                                                        <td class="px-3 py-2 font-medium text-slate-700 whitespace-nowrap">{{ $satu['modul'] }}</td>
                                                        <td class="px-3 py-2 text-slate-600">{{ $satu['keterangan'] }}</td>
                                                        <td class="px-3 py-2 text-right text-slate-700">{{ $satu['jumlah_baris'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @elseif ($tab === 'login_gagal')
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                            <input wire:model.live.debounce.300ms="searchLoginGagal" type="text" placeholder="Cari username / IP address..." class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm sm:w-80">
                            <x-zoom-controls :zoom="$zoomPercent" />
                        </div>

                        <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg" style="max-height: 28rem; zoom: {{ $zoomPercent }}%;">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="sticky top-0 bg-slate-50">
                                    <tr class="text-left text-slate-500">
                                        <th class="px-3 py-2">Username Dicoba</th>
                                        <th class="px-3 py-2">Hari & Tanggal</th>
                                        <th class="px-3 py-2">Waktu</th>
                                        <th class="px-3 py-2">IP Address</th>
                                        <th class="px-3 py-2">Info Perangkat</th>
                                        <th class="px-3 py-2">Lokasi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($loginGagal as $item)
                                        <tr>
                                            <td class="px-3 py-2 text-slate-700 whitespace-nowrap">{{ $item->username_dicoba ?: '-' }}</td>
                                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->created_at?->keWaktuLokal()->translatedFormat('l, d F Y') }}</td>
                                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->created_at?->keWaktuLokal()->format('H:i:s') }}</td>
                                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->ip_address ?: '-' }}</td>
                                            <td class="px-3 py-2 text-slate-500 whitespace-nowrap" title="{{ $item->user_agent }}">{{ \App\Support\InfoPerangkat::label($item->user_agent) }}</td>
                                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">
                                                @if ($item->latitude && $item->longitude)
                                                    <a href="https://www.google.com/maps?q={{ $item->latitude }},{{ $item->longitude }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">Lihat di Peta</a>
                                                @else
                                                    <span class="text-slate-400">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-3 py-6 text-center text-slate-400">Belum ada percobaan login gagal yang tercatat.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex-1">
                                {{ $loginGagal->links() }}
                            </div>
                            <x-pagination-per-page wire:model.live="perPageLoginGagal" />
                        </div>
                    @elseif ($tab === 'akses_data')
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                            <div class="flex flex-col sm:flex-row gap-3">
                                <input wire:model.live.debounce.300ms="searchAksesData" type="text" placeholder="Cari username / nama menu..." class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm sm:w-72">
                                <select wire:model.live="filterJenisAksi" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                                    <option value="">Semua Jenis Aksi</option>
                                    <option value="buka_halaman">Buka Halaman</option>
                                    <option value="unduh">Unduh</option>
                                </select>
                            </div>
                            <x-zoom-controls :zoom="$zoomPercent" />
                        </div>

                        <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg" style="max-height: 28rem; zoom: {{ $zoomPercent }}%;">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="sticky top-0 bg-slate-50">
                                    <tr class="text-left text-slate-500">
                                        <th class="px-3 py-2">Username</th>
                                        <th class="px-3 py-2">Level Akses</th>
                                        <th class="px-3 py-2">Hari & Tanggal</th>
                                        <th class="px-3 py-2">Waktu</th>
                                        <th class="px-3 py-2">Aksi</th>
                                        <th class="px-3 py-2">Menu / Data</th>
                                        <th class="px-3 py-2">IP Address</th>
                                        <th class="px-3 py-2">Info Perangkat</th>
                                        <th class="px-3 py-2">Lokasi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($aksesData as $item)
                                        <tr>
                                            <td class="px-3 py-2 text-slate-700 whitespace-nowrap">{{ $item->username_snapshot ?: '-' }}</td>
                                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->level_akses_snapshot ?: '-' }}</td>
                                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->created_at?->keWaktuLokal()->translatedFormat('l, d F Y') }}</td>
                                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->created_at?->keWaktuLokal()->format('H:i:s') }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                @if ($item->jenis_aksi === 'unduh')
                                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-700">Unduh</span>
                                                @else
                                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-slate-100 text-slate-600">Buka Halaman</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-slate-700 whitespace-nowrap">{{ $item->nama_menu }}{{ $item->detail ? ' - '.$item->detail : '' }}</td>
                                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->ip_address ?: '-' }}</td>
                                            <td class="px-3 py-2 text-slate-500 whitespace-nowrap" title="{{ $item->user_agent }}">{{ \App\Support\InfoPerangkat::label($item->user_agent) }}</td>
                                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">
                                                @if ($item->latitude && $item->longitude)
                                                    <a href="https://www.google.com/maps?q={{ $item->latitude }},{{ $item->longitude }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">Lihat di Peta</a>
                                                @else
                                                    <span class="text-slate-400">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="px-3 py-6 text-center text-slate-400">Belum ada log akses data untuk filter ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex-1">
                                {{ $aksesData->links() }}
                            </div>
                            <x-pagination-per-page wire:model.live="perPageAksesData" />
                        </div>
                    @elseif ($tab === 'kecocokan')
                        <p class="text-sm text-slate-500 mb-4">Membandingkan jumlah data yang dihitung lewat aplikasi dengan jumlah baris asli di database untuk setiap jenis data, dikelompokkan per sekolah. Klik simbol <strong>+</strong> pada nama sekolah untuk melihat rincian jenis data sekolah tersebut. Idealnya semua baris berstatus "Cocok".</p>

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                            <input wire:model.live.debounce.300ms="searchKecocokan" type="text" placeholder="Cari nama sekolah..." class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm sm:w-80">
                            <x-zoom-controls :zoom="$zoomPercent" />
                        </div>

                        <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg" style="max-height: 32rem; zoom: {{ $zoomPercent }}%;">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="sticky top-0 bg-slate-50">
                                    <tr class="text-left text-slate-500">
                                        <th class="px-3 py-2">Nama Sekolah</th>
                                        <th class="px-3 py-2 text-right">Jumlah Jenis Data</th>
                                        <th class="px-3 py-2 text-right">Jumlah Data di Aplikasi</th>
                                        <th class="px-3 py-2 text-right">Jumlah Data di Database</th>
                                        <th class="px-3 py-2">Status</th>
                                    </tr>
                                </thead>
                                @forelse ($kecocokanData as $sekolah)
                                    <tbody x-data="{ terbuka: false }" class="divide-y divide-slate-100 bg-white">
                                        <tr class="cursor-pointer hover:bg-slate-50" x-on:click="terbuka = ! terbuka">
                                            <td class="px-3 py-2 font-medium text-slate-700 whitespace-nowrap">
                                                <span class="inline-flex items-center justify-center w-5 h-5 mr-2 rounded border border-slate-300 text-slate-500 text-xs font-bold align-middle" x-text="terbuka ? '−' : '+'"></span>
                                                {{ $sekolah['nama_sekolah'] }}
                                            </td>
                                            <td class="px-3 py-2 text-right text-slate-600 whitespace-nowrap">{{ count($sekolah['rincian']) }} jenis data</td>
                                            <td class="px-3 py-2 text-right text-slate-600 whitespace-nowrap">{{ $sekolah['total_jumlah_aplikasi'] }}</td>
                                            <td class="px-3 py-2 text-right text-slate-600 whitespace-nowrap">{{ $sekolah['total_jumlah_database'] }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                @if ($sekolah['semua_cocok'])
                                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">Cocok</span>
                                                @else
                                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">Tidak Cocok</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr x-show="terbuka" x-cloak>
                                            <td colspan="5" class="px-3 pb-3 pt-0 bg-slate-50">
                                                <table class="min-w-full divide-y divide-slate-200 text-xs border border-slate-200 rounded-md overflow-hidden">
                                                    <thead class="bg-slate-100">
                                                        <tr class="text-left text-slate-500">
                                                            <th class="px-3 py-1.5">Jenis Data</th>
                                                            <th class="px-3 py-1.5 text-right">Jumlah Data di Aplikasi</th>
                                                            <th class="px-3 py-1.5 text-right">Jumlah Data di Database</th>
                                                            <th class="px-3 py-1.5">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100 bg-white">
                                                        @foreach ($sekolah['rincian'] as $item)
                                                            <tr>
                                                                <td class="px-3 py-1.5 font-medium text-slate-700 whitespace-nowrap">{{ $item['jenis_data'] }}</td>
                                                                <td class="px-3 py-1.5 text-right text-slate-600 whitespace-nowrap">{{ $item['jumlah_aplikasi'] }}</td>
                                                                <td class="px-3 py-1.5 text-right text-slate-600 whitespace-nowrap">{{ $item['jumlah_database'] }}</td>
                                                                <td class="px-3 py-1.5 whitespace-nowrap">
                                                                    @if ($item['cocok'])
                                                                        <span class="inline-flex px-2 py-0.5 text-[11px] font-medium rounded-full bg-emerald-100 text-emerald-700">Cocok</span>
                                                                    @else
                                                                        <span class="inline-flex px-2 py-0.5 text-[11px] font-medium rounded-full bg-red-100 text-red-700">Tidak Cocok</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                                @empty
                                    <tbody>
                                        <tr>
                                            <td colspan="5" class="px-3 py-6 text-center text-slate-400">Tidak ada sekolah yang cocok dengan pencarian ini.</td>
                                        </tr>
                                    </tbody>
                                @endforelse
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi Bersihkan Otomatis --}}
    <x-modal name="cek-database-bersihkan" :show="$confirmingBersihkan !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Bersihkan data bermasalah?</h2>
            <p class="mt-1 text-sm text-slate-600">
                Semua baris pada kategori ini akan dihapus dari database sekaligus supaya cocok dengan yang tampil di aplikasi. Tindakan ini tidak dapat dibatalkan.
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalBersihkan" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                <x-danger-button wire:click="bersihkanOtomatis" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="trash" class="w-3.5 h-3.5 mr-1" />Ya, Bersihkan</x-danger-button>
            </div>
        </div>
    </x-modal>
</div>
