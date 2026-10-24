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
                        <button wire:click="pindahTab('storage')" class="pb-3 text-sm font-medium border-b-2 transition @if ($tab === 'storage') border-blue-600 text-blue-600 @else border-transparent text-slate-500 hover:text-slate-700 @endif">
                            Storage & Langganan VPS
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
                            <div class="flex items-center gap-2">
                                {{-- Permintaan user 2026-10-03: tombol Hapus (per data & semua data) --}}
                                <x-danger-button type="button" wire:click="konfirmasiHapusTerpilihLoginGagal" wire:loading.attr="disabled" :disabled="count($dipilihLoginGagal) === 0" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="trash" class="w-3.5 h-3.5 mr-1" />
                                    Hapus Terpilih ({{ count($dipilihLoginGagal) }})
                                </x-danger-button>
                                <x-secondary-button type="button" wire:click="konfirmasiHapusSemuaLoginGagal" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="trash" class="w-3.5 h-3.5 mr-1" />
                                    Hapus Semua
                                </x-secondary-button>
                                <x-zoom-controls :zoom="$zoomPercent" />
                            </div>
                        </div>

                        <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg" style="max-height: 28rem; zoom: {{ $zoomPercent }}%;">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="sticky top-0 bg-slate-50">
                                    <tr class="text-left text-slate-500">
                                        <th class="px-3 py-2">
                                            <input type="checkbox" wire:click="toggleSemuaLoginGagal" @checked($semuaTerpilihLoginGagal) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" title="Pilih/batal pilih semua baris di halaman ini">
                                        </th>
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
                                            <td class="px-3 py-2">
                                                <input type="checkbox" wire:model="dipilihLoginGagal" value="{{ $item->id }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                            </td>
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
                                            <td colspan="7" class="px-3 py-6 text-center text-slate-400">Belum ada percobaan login gagal yang tercatat.</td>
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

                        {{-- Modal konfirmasi Hapus Terpilih & Hapus Semua - permintaan user 2026-10-03 --}}
                        <x-modal name="cek-database-login-gagal-hapus-terpilih" :show="$confirmingHapusTerpilihLoginGagal" maxWidth="md">
                            <div class="p-6">
                                <h2 class="text-lg font-medium text-slate-900">Hapus {{ count($dipilihLoginGagal) }} catatan terpilih?</h2>
                                <p class="mt-1 text-sm text-slate-600">Semua baris percobaan login gagal yang dicentang akan dihapus sekaligus. Tindakan ini tidak dapat dibatalkan.</p>
                                <div class="mt-6 flex justify-end gap-3">
                                    <x-secondary-button wire:click="batalHapusTerpilihLoginGagal" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                                    <x-danger-button wire:click="hapusTerpilihLoginGagal" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="trash" class="w-3.5 h-3.5 mr-1" />Hapus</x-danger-button>
                                </div>
                            </div>
                        </x-modal>

                        <x-modal name="cek-database-login-gagal-hapus-semua" :show="$confirmingHapusSemuaLoginGagal" maxWidth="md">
                            <div class="p-6">
                                <h2 class="text-lg font-medium text-slate-900">Hapus SEMUA catatan percobaan login gagal?</h2>
                                <p class="mt-1 text-sm text-slate-600">
                                    @if ($searchLoginGagal)
                                        Ini akan menghapus SEMUA baris yang cocok dengan pencarian "{{ $searchLoginGagal }}" saat ini (bukan hanya yang tampil di halaman ini). Tindakan ini tidak dapat dibatalkan.
                                    @else
                                        Tidak ada pencarian aktif - ini akan menghapus SEMUA catatan percobaan login gagal di database. Tindakan ini tidak dapat dibatalkan.
                                    @endif
                                </p>
                                <div class="mt-6 flex justify-end gap-3">
                                    <x-secondary-button wire:click="batalHapusSemuaLoginGagal" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                                    <x-danger-button wire:click="hapusSemuaLoginGagal" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="trash" class="w-3.5 h-3.5 mr-1" />Ya, Hapus Semua</x-danger-button>
                                </div>
                            </div>
                        </x-modal>
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
                            <div class="flex items-center gap-2">
                                {{-- Permintaan user 2026-10-03: tombol Hapus (per data & semua data) --}}
                                <x-danger-button type="button" wire:click="konfirmasiHapusTerpilihAksesData" wire:loading.attr="disabled" :disabled="count($dipilihAksesData) === 0" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="trash" class="w-3.5 h-3.5 mr-1" />
                                    Hapus Terpilih ({{ count($dipilihAksesData) }})
                                </x-danger-button>
                                <x-secondary-button type="button" wire:click="konfirmasiHapusSemuaAksesData" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="trash" class="w-3.5 h-3.5 mr-1" />
                                    Hapus Semua
                                </x-secondary-button>
                                <x-zoom-controls :zoom="$zoomPercent" />
                            </div>
                        </div>

                        <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg" style="max-height: 28rem; zoom: {{ $zoomPercent }}%;">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="sticky top-0 bg-slate-50">
                                    <tr class="text-left text-slate-500">
                                        <th class="px-3 py-2">
                                            <input type="checkbox" wire:click="toggleSemuaAksesData" @checked($semuaTerpilihAksesData) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" title="Pilih/batal pilih semua baris di halaman ini">
                                        </th>
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
                                            <td class="px-3 py-2">
                                                <input type="checkbox" wire:model="dipilihAksesData" value="{{ $item->id }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                            </td>
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
                                            <td colspan="10" class="px-3 py-6 text-center text-slate-400">Belum ada log akses data untuk filter ini.</td>
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

                        {{-- Modal konfirmasi Hapus Terpilih & Hapus Semua - permintaan user 2026-10-03 --}}
                        <x-modal name="cek-database-akses-data-hapus-terpilih" :show="$confirmingHapusTerpilihAksesData" maxWidth="md">
                            <div class="p-6">
                                <h2 class="text-lg font-medium text-slate-900">Hapus {{ count($dipilihAksesData) }} log terpilih?</h2>
                                <p class="mt-1 text-sm text-slate-600">Semua baris log akses data yang dicentang akan dihapus sekaligus. Tindakan ini tidak dapat dibatalkan.</p>
                                <div class="mt-6 flex justify-end gap-3">
                                    <x-secondary-button wire:click="batalHapusTerpilihAksesData" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                                    <x-danger-button wire:click="hapusTerpilihAksesData" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="trash" class="w-3.5 h-3.5 mr-1" />Hapus</x-danger-button>
                                </div>
                            </div>
                        </x-modal>

                        <x-modal name="cek-database-akses-data-hapus-semua" :show="$confirmingHapusSemuaAksesData" maxWidth="md">
                            <div class="p-6">
                                <h2 class="text-lg font-medium text-slate-900">Hapus SEMUA log akses data?</h2>
                                <p class="mt-1 text-sm text-slate-600">
                                    @if ($searchAksesData || $filterJenisAksi)
                                        Ini akan menghapus SEMUA baris yang cocok dengan filter/pencarian aktif saat ini (bukan hanya yang tampil di halaman ini). Tindakan ini tidak dapat dibatalkan.
                                    @else
                                        Tidak ada filter/pencarian aktif - ini akan menghapus SEMUA log akses data di database. Tindakan ini tidak dapat dibatalkan.
                                    @endif
                                </p>
                                <div class="mt-6 flex justify-end gap-3">
                                    <x-secondary-button wire:click="batalHapusSemuaAksesData" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                                    <x-danger-button wire:click="hapusSemuaAksesData" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="trash" class="w-3.5 h-3.5 mr-1" />Ya, Hapus Semua</x-danger-button>
                                </div>
                            </div>
                        </x-modal>
                    @elseif ($tab === 'kecocokan')
                        <p class="text-sm text-slate-500 mb-4">Membandingkan jumlah data yang dihitung lewat aplikasi dengan jumlah baris asli di database untuk setiap jenis data, dikelompokkan per sekolah. Klik simbol <strong>+</strong> pada nama sekolah untuk melihat rincian jenis data sekolah tersebut. Idealnya semua baris berstatus "Cocok".</p>

                        {{--
                            Tombol "Cek Ulang" BARU (permintaan user
                            2026-10-14, klarifikasi ulang) - cekKecocokanData()
                            di PHP sudah otomatis fresh tiap render(), tombol
                            ini + "Terakhir diperiksa" HANYA untuk kepastian
                            visual bagi user (lihat App\Livewire\
                            CekDatabaseAplikasi\Index::cekUlangKecocokanData()).
                        --}}
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-1">
                            <input wire:model.live.debounce.300ms="searchKecocokan" type="text" placeholder="Cari nama sekolah..." class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm sm:w-80">
                            <div class="flex items-center gap-3">
                                <x-secondary-button type="button" wire:click="cekUlangKecocokanData" wire:loading.attr="disabled" wire:target="cekUlangKecocokanData" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <span wire:loading.remove wire:target="cekUlangKecocokanData">Cek Ulang</span>
                                    <span wire:loading wire:target="cekUlangKecocokanData">Memeriksa...</span>
                                </x-secondary-button>
                                <x-zoom-controls :zoom="$zoomPercent" />
                            </div>
                        </div>
                        @if ($kecocokanDataDiperiksaPada)
                            <p class="text-xs text-slate-400 mb-3">Terakhir diperiksa: {{ $kecocokanDataDiperiksaPada }}</p>
                        @else
                            <div class="mb-3"></div>
                        @endif

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

                        {{--
                            Kecocokan Akun Superadmin Lokal vs Online (BARU,
                            permintaan user 2026-10-14) - BERBEDA dari tabel
                            di atas (yang membandingkan Eloquent vs Database
                            DALAM 1 instalasi yang sama): kartu ini
                            membandingkan akun Superadmin ANTARA instalasi
                            Lokal & Online lewat API Sinkronisasi (lihat
                            App\Livewire\CekDatabaseAplikasi\Index::
                            cekUlangSuperadmin()). HANYA aktif di sisi LOKAL
                            (sama seperti tombol aksi menu Sinkronisasi) &
                            HANYA dijalankan saat tombol "Cek Ulang" diklik
                            manual (jawaban AskUserQuestion).
                        --}}
                        <div class="mt-8 border-t border-slate-200 pt-6">
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
                                <h3 class="text-base font-bold text-slate-800">Kecocokan Akun Superadmin (Lokal vs Online)</h3>

                                @if (config('sync.peran') === 'lokal')
                                    <x-secondary-button type="button" wire:click="cekUlangSuperadmin" wire:loading.attr="disabled" wire:target="cekUlangSuperadmin" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                        <span wire:loading.remove wire:target="cekUlangSuperadmin">Cek Ulang</span>
                                        <span wire:loading wire:target="cekUlangSuperadmin">Memeriksa...</span>
                                    </x-secondary-button>
                                @endif
                            </div>

                            @if (config('sync.peran') !== 'lokal')
                                <p class="text-xs text-slate-500">Nonaktif di sisi Online - jalankan "Cek Ulang" dari Aplikasi Lokal (menu Sinkronisasi dan Tarik Data di sisi Lokal yang mengatur URL & Token API-nya).</p>
                            @else
                                <p class="text-xs text-slate-500 mb-3">Membandingkan akun Superadmin (username, email, status disetujui) antara instalasi ini (Lokal) dengan Online, lewat URL & Token API yang sama dengan menu Sinkronisasi dan Tarik Data. Klik "Cek Ulang" untuk memeriksa/memperbarui.</p>

                                @if ($kecocokanSuperadminError)
                                    <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-xs mb-3">{{ $kecocokanSuperadminError }}</div>
                                @endif

                                @if ($kecocokanSuperadmin !== null)
                                    <p class="text-xs text-slate-400 mb-2">Terakhir diperiksa: {{ $kecocokanSuperadminDiperiksaPada }}</p>
                                    <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg">
                                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                                            <thead class="bg-slate-50">
                                                <tr class="text-left text-slate-500">
                                                    <th class="px-3 py-2">Email</th>
                                                    <th class="px-3 py-2">Username di Lokal</th>
                                                    <th class="px-3 py-2">Username di Online</th>
                                                    <th class="px-3 py-2">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 bg-white">
                                                @forelse ($kecocokanSuperadmin as $akun)
                                                    <tr>
                                                        <td class="px-3 py-2 font-medium text-slate-700 whitespace-nowrap">{{ $akun['email'] }}</td>
                                                        <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $akun['lokal']['username'] ?? '(tidak ada di Lokal)' }}</td>
                                                        <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $akun['online']['username'] ?? '(tidak ada di Online)' }}</td>
                                                        <td class="px-3 py-2 whitespace-nowrap">
                                                            @if ($akun['cocok'])
                                                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">Cocok</span>
                                                            @else
                                                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">Tidak Cocok</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="px-3 py-6 text-center text-slate-400">Tidak ada akun Superadmin ditemukan di kedua sisi.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-xs text-slate-400">Belum pernah diperiksa - klik "Cek Ulang" di atas.</p>
                                @endif
                            @endif
                        </div>
                    @elseif ($tab === 'storage')
                        {{--
                            Tab BARU "Storage & Langganan VPS" (permintaan
                            user 2026-10-24) - 3 bagian: (1) kapasitas disk
                            VPS keseluruhan, (2) ukuran database & folder
                            aplikasi, (3) tanggal jatuh tempo langganan VPS
                            idcloudhost (diisi MANUAL oleh Superadmin, lihat
                            App\Livewire\CekDatabaseAplikasi\Index::simpanLangganan()).
                        --}}
                        <div class="space-y-8">
                            {{-- Bagian 1: Kapasitas Disk VPS Keseluruhan --}}
                            <div>
                                <h3 class="text-sm font-semibold text-slate-700 mb-3">Kapasitas Disk VPS Keseluruhan</h3>

                                @if ($infoStorageVps['adalahWindows'])
                                    <div class="p-3 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-xs mb-3">
                                        Anda sedang membuka aplikasi ini di LOKAL (komputer Windows) - angka di bawah ini menunjukkan kapasitas drive komputer Anda sendiri, <strong>BUKAN kapasitas VPS</strong>. Buka halaman ini di <strong>srcibadak.my.id</strong> (ONLINE) untuk melihat kapasitas VPS yang sebenarnya.
                                    </div>
                                @endif

                                @if ($infoStorageVps['tersedia'])
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg">
                                            <p class="text-xs text-slate-500 mb-1">Total Kapasitas</p>
                                            <p class="text-lg font-semibold text-slate-800">{{ $this->formatBytes($infoStorageVps['totalBytes']) }}</p>
                                        </div>
                                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                            <p class="text-xs text-blue-600 mb-1">Terpakai</p>
                                            <p class="text-lg font-semibold text-blue-700">{{ $this->formatBytes($infoStorageVps['terpakaiBytes']) }} <span class="text-sm font-normal">({{ $infoStorageVps['persenTerpakai'] }}%)</span></p>
                                        </div>
                                        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                                            <p class="text-xs text-green-600 mb-1">Tersisa</p>
                                            <p class="text-lg font-semibold text-green-700">{{ $this->formatBytes($infoStorageVps['bebasBytes']) }}</p>
                                        </div>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                        <div class="h-2.5 rounded-full @if ($infoStorageVps['persenTerpakai'] >= 90) bg-red-500 @elseif ($infoStorageVps['persenTerpakai'] >= 75) bg-amber-500 @else bg-blue-500 @endif" style="width: {{ min($infoStorageVps['persenTerpakai'], 100) }}%"></div>
                                    </div>
                                @else
                                    <p class="text-sm text-slate-400">Tidak dapat mengambil info kapasitas disk dari server ini.</p>
                                @endif
                            </div>

                            {{-- Bagian 2: Ukuran Database & Folder Aplikasi --}}
                            <div>
                                <h3 class="text-sm font-semibold text-slate-700 mb-3">Ukuran Database &amp; Folder Aplikasi</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg">
                                        <p class="text-xs text-slate-500 mb-1">Ukuran Database</p>
                                        <p class="text-lg font-semibold text-slate-800">{{ $this->formatBytes($ukuranDatabaseBytes) }}</p>
                                    </div>
                                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg">
                                        <div class="flex items-center justify-between mb-1">
                                            <p class="text-xs text-slate-500">Ukuran Folder Aplikasi</p>
                                            <x-secondary-button type="button" wire:click="hitungUkuranAplikasi" wire:loading.attr="disabled" wire:target="hitungUkuranAplikasi" class="whitespace-nowrap !px-2 !py-1 !text-[10px]">
                                                <span wire:loading.remove wire:target="hitungUkuranAplikasi">Hitung Ukuran Aplikasi</span>
                                                <span wire:loading wire:target="hitungUkuranAplikasi">Menghitung...</span>
                                            </x-secondary-button>
                                        </div>
                                        <p class="text-lg font-semibold text-slate-800">{{ $this->formatBytes($ukuranFolderAplikasiBytes) }}</p>
                                        @if ($ukuranAplikasiDihitungPada)
                                            <p class="text-xs text-slate-400 mt-1">Dihitung pada: {{ $ukuranAplikasiDihitungPada }}</p>
                                        @else
                                            <p class="text-xs text-slate-400 mt-1">Klik tombol di atas untuk menghitung (bisa makan waktu beberapa detik).</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Bagian 3: Langganan VPS (idcloudhost) - diisi manual Superadmin --}}
                            <div>
                                <h3 class="text-sm font-semibold text-slate-700 mb-3">Jatuh Tempo Langganan VPS (idcloudhost)</h3>
                                <p class="text-xs text-slate-500 mb-3">Tanggal ini diisi manual - aplikasi TIDAK mengambil data tagihan otomatis dari idcloudhost. Perbarui tanggalnya setiap kali Anda top up/perpanjang langganan.</p>

                                @if ($statusJatuhTempo)
                                    <div class="p-3 rounded-lg text-sm mb-4 border
                                        @if ($statusJatuhTempo['level'] === 'lewat') bg-red-50 border-red-200 text-red-700
                                        @elseif ($statusJatuhTempo['level'] === 'mendesak') bg-red-50 border-red-200 text-red-700
                                        @elseif ($statusJatuhTempo['level'] === 'perhatian') bg-amber-50 border-amber-200 text-amber-700
                                        @else bg-green-50 border-green-200 text-green-700
                                        @endif">
                                        @if ($statusJatuhTempo['level'] === 'lewat')
                                            <strong>Sudah lewat {{ abs($statusJatuhTempo['sisaHari']) }} hari!</strong> Segera top up langganan VPS supaya aplikasi tidak berhenti berjalan.
                                        @elseif ($statusJatuhTempo['sisaHari'] === 0)
                                            <strong>Jatuh tempo HARI INI.</strong> Segera top up langganan VPS.
                                        @else
                                            Sisa <strong>{{ $statusJatuhTempo['sisaHari'] }} hari</strong> lagi sebelum jatuh tempo.
                                        @endif
                                    </div>
                                @endif

                                <form wire:submit="simpanLangganan" class="space-y-3 max-w-md">
                                    <div>
                                        <x-input-label for="tanggalJatuhTempo" value="Tanggal Jatuh Tempo" />
                                        <input wire:model.live="tanggalJatuhTempo" id="tanggalJatuhTempo" type="date" class="mt-1 block w-full border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                                        <x-input-error :messages="$errors->get('tanggalJatuhTempo')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="catatanLangganan" value="Catatan (opsional)" />
                                        <textarea wire:model="catatanLangganan" id="catatanLangganan" rows="2" placeholder="Misal: paket VPS 2 vCPU/4GB, bayar tiap bulan" class="mt-1 block w-full border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm"></textarea>
                                        <x-input-error :messages="$errors->get('catatanLangganan')" class="mt-1" />
                                    </div>
                                    <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="simpanLangganan">
                                        Simpan
                                    </x-primary-button>
                                </form>
                            </div>
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
