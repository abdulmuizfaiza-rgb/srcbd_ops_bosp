<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Data Sekolah') }}
    </h2>
</x-slot>

<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm whitespace-pre-line">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errorImport)
                <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm whitespace-pre-line">
                    {{ $errorImport }}
                </div>
            @endif

            @if ($errorImportPtk)
                <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm whitespace-pre-line">
                    {{ $errorImportPtk }}
                </div>
            @endif

            @if ($errorExportPtk)
                <div class="p-4 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-sm">
                    {{ $errorExportPtk }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                {{-- Tab Utama: Profil Sekolah / Data PTK (permintaan user
                     2026-10-01: ganti nama menu jadi "Data Sekolah", Profil
                     Sekolah jadi Tab 1, Data PTK Tab 2 baru). --}}
                <div class="border-b border-slate-200 px-4 sm:px-8 pt-4 pb-4">
                    <nav class="flex flex-wrap gap-3">
                        <button
                            type="button"
                            wire:click="pindahTabUtama('profil')"
                            @class([
                                'px-4 py-2.5 rounded-t-xl text-sm font-bold tracking-wide transition-all duration-150 border-b-4',
                                'bg-white text-slate-800 border-indigo-500 shadow-sm' => $tabUtama === 'profil',
                                'bg-slate-50 text-slate-500 border-transparent hover:bg-slate-100 hover:text-slate-700' => $tabUtama !== 'profil',
                            ])
                        >
                            Profil Sekolah
                        </button>
                        <button
                            type="button"
                            wire:click="pindahTabUtama('data_ptk')"
                            @class([
                                'px-4 py-2.5 rounded-t-xl text-sm font-bold tracking-wide transition-all duration-150 border-b-4',
                                'bg-white text-slate-800 border-indigo-500 shadow-sm' => $tabUtama === 'data_ptk',
                                'bg-slate-50 text-slate-500 border-transparent hover:bg-slate-100 hover:text-slate-700' => $tabUtama !== 'data_ptk',
                            ])
                        >
                            Data PTK
                        </button>
                    </nav>
                </div>

                @if ($tabUtama === 'profil')
                    {{-- ======================================================
                         TAB 1: PROFIL SEKOLAH - TIDAK ADA PERUBAHAN LOGIKA,
                         hanya dipindah ke dalam tab (lihat Index.php bagian
                         "Profil Sekolah - TIDAK ADA PERUBAHAN").
                         ====================================================== --}}
                    <div class="p-4 sm:p-8">
                        <div class="flex flex-col xl:flex-row xl:flex-wrap xl:items-start xl:justify-between gap-3 mb-6">
                            <div>
                                <h3 class="text-lg font-medium text-slate-900">Data Sekolah</h3>
                                <p class="text-sm text-slate-500">Diurutkan berdasarkan status (Negeri &rarr; Swasta), lalu kecamatan.</p>
                            </div>

                            @if ($bolehKelolaSemua)
                                <div class="flex flex-wrap sm:flex-row sm:items-center gap-2.5">
                                    <div class="flex items-center gap-1.5 border border-black/10 rounded-md px-1.5 py-1 bg-black/10">
                                        <input type="file" wire:model="fileImport" accept=".xlsx,.xls,.csv" class="text-[11px] text-slate-700 w-28 sm:w-36 file:mr-1.5 file:py-0.5 file:px-1.5 file:rounded file:border-0 file:bg-white file:text-slate-700 file:text-[11px] file:shadow-sm hover:file:bg-slate-100">
                                        <x-secondary-button wire:click="import" wire:loading.attr="disabled" wire:target="fileImport,import" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                            <x-icon name="upload" class="w-3.5 h-3.5 mr-1" />
                                            Import Excel
                                        </x-secondary-button>
                                    </div>

                                    <div class="flex items-center gap-1.5">
                                        <x-secondary-button wire:click="export" wire:loading.attr="disabled" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                            <x-icon name="download" class="w-3.5 h-3.5 mr-1" />
                                            Export Excel
                                        </x-secondary-button>
                                        <x-primary-button wire:click="tambah" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                            <x-icon name="plus" class="w-3.5 h-3.5 mr-1" />
                                            Tambah Sekolah
                                        </x-primary-button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2.5 mb-4">
                            <x-zoom-controls :zoom="$zoomPercent" />

                            @if ($bolehKelolaSemua)
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-filter-select wireModel="filterNamaSekolah" label="Nama Sekolah" allLabel="Semua Sekolah" :options="$filterNamaSekolahOptions" color="indigo" />
                                    <x-filter-select wireModel="filterStatus" label="Status" allLabel="Semua Status" :options="$statusOptions" color="sky" />
                                    <x-filter-select wireModel="filterKecamatan" label="Kecamatan" allLabel="Semua Kecamatan" :options="$filterKecamatanOptions" color="emerald" />
                                </div>
                            @endif
                        </div>

                        @if ($bolehKelolaSemua)
                            <div wire:loading wire:target="fileImport,import" class="text-xs text-slate-400 -mt-3 mb-3">Memproses import...</div>
                            <x-input-error :messages="$errors->get('fileImport')" class="text-xs -mt-3 mb-3 block" />
                        @endif

                        <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg" style="max-height: 30rem; zoom: {{ $zoomPercent }}%;">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="sticky top-0 z-10 bg-slate-50">
                                    <tr class="text-left text-slate-500">
                                        <th class="px-3 py-2">No</th>
                                        <th class="px-3 py-2">NPSN</th>
                                        <th class="px-3 py-2">Kode UPB</th>
                                        <th class="px-3 py-2">Nama Sekolah</th>
                                        <th class="px-3 py-2">Status</th>
                                        <th class="px-3 py-2">Kecamatan</th>
                                        <th class="px-3 py-2">Subrayon</th>
                                        <th class="px-3 py-2">Kepala Sekolah</th>
                                        <th class="px-3 py-2 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse ($daftarSekolah as $sekolah)
                                        <tr class="@if ($sekolah->id === $sekolahSayaId) bg-blue-50/60 @endif">
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-500">{{ $loop->iteration }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->npsn ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->kode_upb ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap font-medium text-slate-800">
                                                {{ $sekolah->nama_sekolah }}
                                                @if ($sekolah->id === $sekolahSayaId)
                                                    <span class="ml-1 inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Sekolah Anda</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                                    @class([
                                                        'bg-emerald-100 text-emerald-700' => $sekolah->status === 'negeri',
                                                        'bg-amber-100 text-amber-700' => $sekolah->status === 'swasta',
                                                        'bg-slate-100 text-slate-500' => ! $sekolah->status,
                                                    ])">
                                                    {{ $sekolah->status_label }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->kecamatan ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->subrayon ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->nama_kepala_sekolah ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-right space-x-2">
                                                @if ($bolehKelolaSemua || $sekolah->id === $sekolahSayaId)
                                                    <button wire:click="edit({{ $sekolah->id }})" class="text-blue-600 hover:underline">Edit</button>
                                                @endif
                                                @if ($bolehKelolaSemua)
                                                    <button wire:click="konfirmasiHapus({{ $sekolah->id }})" class="text-red-600 hover:underline">Hapus</button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="px-3 py-6 text-center text-slate-400">Belum ada data sekolah.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    {{-- ======================================================
                         TAB 2: DATA PTK (BARU, permintaan user 2026-10-01).
                         ====================================================== --}}
                    <div class="p-4 sm:p-8">
                        <div class="flex flex-col xl:flex-row xl:flex-wrap xl:items-start xl:justify-between gap-3 mb-6">
                            <div class="flex flex-wrap sm:flex-row sm:items-center gap-2.5">
                                <input wire:model.live.debounce.300ms="searchPtk" type="text" placeholder="Cari Nama PTK / Nama Sekolah..." class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">

                                @if ($bolehKelolaDataPtkSemua)
                                    <select wire:model.live="filterSekolahPtk" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">
                                        <option value="">Semua Sekolah</option>
                                        @foreach ($sekolahOptionsPtk as $sekolah)
                                            <option value="{{ $sekolah->id }}">{{ $sekolah->nama_sekolah }}</option>
                                        @endforeach
                                    </select>
                                @endif

                                <select wire:model.live="filterStatusSertifikasiPtk" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">
                                    <option value="">Semua Status Sertifikasi</option>
                                    @foreach ($statusSertifikasiOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>

                                <select wire:model.live="filterStatusKeaktifanPtk" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">
                                    <option value="">Semua Status Keaktifan</option>
                                    @foreach ($statusKeaktifanOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>

                                <select wire:model.live="filterStatusDapodikPtk" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">
                                    <option value="">Semua Status Dapodik</option>
                                    @foreach ($statusDapodikOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>

                                <x-zoom-controls :zoom="$zoomPercent" />
                            </div>

                            <div class="flex flex-wrap sm:flex-row sm:items-center gap-2.5">
                                @if ($bolehTambahDataPtk)
                                    <div class="flex items-center gap-1.5 border border-black/10 rounded-md px-1.5 py-1 bg-black/10">
                                        <input type="file" wire:model="fileImportPtk" accept=".xlsx,.xls,.csv" class="text-[11px] text-slate-700 w-28 sm:w-36 file:mr-1.5 file:py-0.5 file:px-1.5 file:rounded file:border-0 file:bg-white file:text-slate-700 file:text-[11px] file:shadow-sm hover:file:bg-slate-100">
                                        <x-secondary-button wire:click="importPtk" wire:loading.attr="disabled" wire:target="fileImportPtk,importPtk" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                            <x-icon name="upload" class="w-3.5 h-3.5 mr-1" />
                                            Import Excel
                                        </x-secondary-button>
                                    </div>
                                @endif

                                <div class="flex items-center gap-1.5">
                                    <x-secondary-button wire:click="exportPtk" wire:loading.attr="disabled" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                        <x-icon name="download" class="w-3.5 h-3.5 mr-1" />
                                        Export Excel
                                    </x-secondary-button>
                                    <x-secondary-button wire:click="unduhPtk" wire:loading.attr="disabled" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                        <x-icon name="download" class="w-3.5 h-3.5 mr-1" />
                                        Unduh
                                    </x-secondary-button>
                                    @if ($bolehTambahDataPtk)
                                        <x-primary-button wire:click="tambahPtk" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                            <x-icon name="plus" class="w-3.5 h-3.5 mr-1" />
                                            Tambah PTK
                                        </x-primary-button>
                                        <x-danger-button type="button" wire:click="konfirmasiHapusTerpilihPtk" wire:loading.attr="disabled" :disabled="count($dipilihPtk) === 0" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                            <x-icon name="trash" class="w-3.5 h-3.5 mr-1" />
                                            Hapus Terpilih ({{ count($dipilihPtk) }})
                                        </x-danger-button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if ($bolehTambahDataPtk)
                            <div wire:loading wire:target="fileImportPtk,importPtk" class="text-xs text-slate-400 -mt-3 mb-3">Memproses import...</div>
                            <x-input-error :messages="$errors->get('fileImportPtk')" class="text-xs -mt-3 mb-3 block" />
                        @endif

                        <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg" style="max-height: 30rem; zoom: {{ $zoomPercent }}%;">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="sticky top-0 z-10 bg-slate-50">
                                    <tr class="text-left text-slate-500">
                                        @if ($bolehTambahDataPtk)
                                            <th class="px-3 py-2">
                                                <input type="checkbox" wire:click="togglePtkSemua" @checked($semuaTerpilihPtk) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" title="Pilih/batal pilih semua baris di halaman ini">
                                            </th>
                                        @endif
                                        <th class="px-3 py-2">No</th>
                                        <th class="px-3 py-2">NIK</th>
                                        <th class="px-3 py-2">NUPTK</th>
                                        <th class="px-3 py-2">NIP</th>
                                        <th class="px-3 py-2">Nama PTK</th>
                                        <th class="px-3 py-2">Tempat Lahir</th>
                                        <th class="px-3 py-2">Tanggal Lahir</th>
                                        <th class="px-3 py-2">Jabatan</th>
                                        <th class="px-3 py-2">Pangkat / Golongan</th>
                                        <th class="px-3 py-2">Status Kepegawaian</th>
                                        <th class="px-3 py-2">Jenis PTK</th>
                                        <th class="px-3 py-2">TMT Di Sekolah Induk</th>
                                        <th class="px-3 py-2">Pendidikan Terakhir</th>
                                        <th class="px-3 py-2">Jurusan / Prodi</th>
                                        <th class="px-3 py-2">Tahun Lulus Ijazah</th>
                                        <th class="px-3 py-2">Status Sertifikasi</th>
                                        <th class="px-3 py-2">Bidang Studi Sertifikasi</th>
                                        <th class="px-3 py-2">Tahun Lulus Sertifikasi</th>
                                        <th class="px-3 py-2">Nomor Sertifikat Sertifikasi</th>
                                        <th class="px-3 py-2">Nomor Registrasi Guru</th>
                                        <th class="px-3 py-2">Nomor Peserta Sertifikasi</th>
                                        <th class="px-3 py-2">Nama Sekolah</th>
                                        <th class="px-3 py-2">Status Dapodik</th>
                                        <th class="px-3 py-2">Status Keaktifan</th>
                                        <th class="px-3 py-2 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                @forelse ($daftarPtk as $baris)
                                    {{-- Simbol "+" pada kolom No (permintaan user 2026-10-01) - membuka
                                         panel info tambahan (pembuat & kapan data dibuat/diperbarui) yang
                                         belum ada kolomnya sendiri di tabel, TANPA menyembunyikan kolom
                                         manapun yang sudah ada (pola toggle Alpine.js sama seperti tab
                                         Cek Kecocokan Data). --}}
                                    <tbody x-data="{ terbuka: false }" class="divide-y divide-slate-100">
                                        <tr wire:key="data-ptk-baris-{{ $baris->id }}">
                                            @if ($bolehTambahDataPtk)
                                                <td class="px-3 py-2">
                                                    @if ($bolehKelolaDataPtkSemua || auth()->user()->profil_sekolah_id === $baris->profil_sekolah_id)
                                                        <input type="checkbox" wire:model="dipilihPtk" value="{{ $baris->id }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                                    @endif
                                                </td>
                                            @endif
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-500">
                                                <button type="button" x-on:click="terbuka = ! terbuka" class="inline-flex items-center justify-center w-5 h-5 mr-1.5 rounded border border-slate-300 text-slate-500 text-xs font-bold align-middle hover:bg-slate-100" title="Lihat info tambahan">
                                                    <span x-text="terbuka ? '−' : '+'"></span>
                                                </button>
                                                {{ $loop->iteration + $daftarPtk->firstItem() - 1 }}
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->nik }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->nuptk ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->nip ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap font-medium text-slate-800">{{ $baris->nama_ptk }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->tempat_lahir ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->tanggal_lahir?->format('d-m-Y') ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->jabatan }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->pangkat_golongan ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->status_kepegawaian }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->jenis_ptk }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->tmt_sekolah_induk?->format('d-m-Y') ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->pendidikan_terakhir }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->jurusan_prodi ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->tahun_lulus_ijazah ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                                    @class([
                                                        'bg-emerald-100 text-emerald-700' => $baris->status_sertifikasi === 'Sudah',
                                                        'bg-slate-100 text-slate-500' => $baris->status_sertifikasi !== 'Sudah',
                                                    ])">
                                                    {{ $baris->status_sertifikasi }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->bidang_studi_sertifikasi ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->tahun_lulus_sertifikasi ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->nomor_sertifikat_sertifikasi ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->nomor_registrasi_guru ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->nomor_peserta_sertifikasi ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap font-medium text-slate-800">{{ $baris->profilSekolah->nama_sekolah ?? '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->status_dapodik }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                                    @class([
                                                        'bg-emerald-100 text-emerald-700' => $baris->status_keaktifan === 'Aktif',
                                                        'bg-slate-100 text-slate-500' => $baris->status_keaktifan !== 'Aktif',
                                                    ])">
                                                    {{ $baris->status_keaktifan }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap text-right space-x-2">
                                                @if ($bolehKelolaDataPtkSemua || auth()->user()->profil_sekolah_id === $baris->profil_sekolah_id)
                                                    <button wire:click="editPtk({{ $baris->id }})" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline"><x-icon name="pencil" class="w-3 h-3" />Edit</button>
                                                    @if ($bolehTambahDataPtk)
                                                        <button wire:click="konfirmasiHapusPtk({{ $baris->id }})" class="inline-flex items-center gap-1 text-xs text-red-600 hover:underline"><x-icon name="trash" class="w-3 h-3" />Hapus</button>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                        <tr x-show="terbuka" x-cloak>
                                            <td colspan="26" class="px-3 pb-3 pt-0 bg-slate-50">
                                                <div class="rounded-md border border-slate-200 bg-white px-4 py-3 text-xs text-slate-600 grid grid-cols-1 sm:grid-cols-3 gap-2">
                                                    <div>
                                                        <p class="text-slate-400">Dibuat Oleh</p>
                                                        <p class="font-medium text-slate-700">{{ $baris->creator->name ?? '-' }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-slate-400">Dibuat Pada</p>
                                                        <p class="font-medium text-slate-700">{{ $baris->created_at?->format('d-m-Y H:i') ?? '-' }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-slate-400">Terakhir Diperbarui</p>
                                                        <p class="font-medium text-slate-700">{{ $baris->updated_at?->format('d-m-Y H:i') ?? '-' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                @empty
                                    <tbody class="divide-y divide-slate-100">
                                        <tr>
                                            <td colspan="26" class="px-3 py-6 text-center text-slate-400">Belum ada data PTK.</td>
                                        </tr>
                                    </tbody>
                                @endforelse
                            </table>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex-1">
                                {{ $daftarPtk->links() }}
                            </div>
                            <x-pagination-per-page wire:model.live="perPagePtk" />
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal Form Tambah/Edit Sekolah (Tab 1) --}}
    <x-modal name="sekolah-form" :show="$showForm" maxWidth="lg">
        <form wire:submit="simpan" class="p-6">
            <h2 class="text-lg font-medium text-slate-900 mb-4">
                {{ $editingId ? 'Edit Data Sekolah' : 'Tambah Sekolah' }}
            </h2>

            <div class="space-y-4">
                @if ($bolehKelolaSemua)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="npsn" value="NPSN" />
                            <x-text-input wire:model="npsn" id="npsn" class="block mt-1 w-full" type="text" required />
                            <x-input-error :messages="$errors->get('npsn')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="kode_upb" value="Kode UPB" />
                            <x-text-input wire:model="kode_upb" id="kode_upb" class="block mt-1 w-full" type="text" />
                            <x-input-error :messages="$errors->get('kode_upb')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="nama_sekolah" value="Nama Sekolah" />
                            <x-text-input wire:model="nama_sekolah" id="nama_sekolah" class="block mt-1 w-full" type="text" required />
                            <x-input-error :messages="$errors->get('nama_sekolah')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="status" value="Status" />
                            <select wire:model="status" id="status" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                                <option value="">-- Pilih Status --</option>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="kecamatan" value="Kecamatan" />
                            <x-text-input wire:model="kecamatan" id="kecamatan" class="block mt-1 w-full" type="text" required />
                            <x-input-error :messages="$errors->get('kecamatan')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="subrayon" value="Subrayon" />
                            <x-text-input wire:model="subrayon" id="subrayon" class="block mt-1 w-full" type="text" />
                            <x-input-error :messages="$errors->get('subrayon')" class="mt-2" />
                        </div>
                    </div>
                @elseif ($editingId)
                    <div class="grid grid-cols-2 gap-4 p-3 bg-slate-50 rounded-lg text-sm">
                        <div>
                            <p class="text-xs text-slate-500">NPSN</p>
                            <p class="font-medium text-slate-700">{{ $npsn ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Nama Sekolah</p>
                            <p class="font-medium text-slate-700">{{ $nama_sekolah ?: '-' }}</p>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400">NPSN, Kode UPB, nama sekolah, status, kecamatan, dan subrayon hanya bisa diubah oleh Superadmin.</p>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="nama_kepala_sekolah" value="Nama Kepala Sekolah" />
                        <x-text-input wire:model="nama_kepala_sekolah" id="nama_kepala_sekolah" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('nama_kepala_sekolah')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="nip_kepala_sekolah" value="NIP Kepala Sekolah" />
                        <x-text-input wire:model="nip_kepala_sekolah" id="nip_kepala_sekolah" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('nip_kepala_sekolah')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="no_whatsapp_kepala_sekolah" value="No Whatsapp Kepala Sekolah" />
                        <x-text-input wire:model="no_whatsapp_kepala_sekolah" id="no_whatsapp_kepala_sekolah" class="block mt-1 w-full" type="text" inputmode="numeric" maxlength="13" placeholder="Contoh: 081234567890" />
                        <x-input-error :messages="$errors->get('no_whatsapp_kepala_sekolah')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="status_kepegawaian_kepsek" value="Status Kepegawaian Kepala Sekolah" />
                        <select wire:model="status_kepegawaian_kepsek" id="status_kepegawaian_kepsek" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                            <option value="">-- Pilih Status Kepegawaian --</option>
                            @foreach ($statusKepegawaianOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status_kepegawaian_kepsek')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="nama_pengawas" value="Nama Pengawas" />
                        <x-text-input wire:model="nama_pengawas" id="nama_pengawas" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('nama_pengawas')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="nip_pengawas" value="NIP Pengawas" />
                        <x-text-input wire:model="nip_pengawas" id="nip_pengawas" class="block mt-1 w-full" type="text" />
                        <p class="text-xs text-slate-400 mt-1">Nama & NIP Pengawas otomatis muncul pada baris tanda tangan hasil export Excel Lampiran 2a, 2b, dan 2c.</p>
                        <x-input-error :messages="$errors->get('nip_pengawas')" class="mt-2" />
                    </div>
                </div>

                <hr class="border-slate-200">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="nama_bendahara" value="Nama Bendahara" />
                        <x-text-input wire:model="nama_bendahara" id="nama_bendahara" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('nama_bendahara')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="status_kepegawaian_bendahara" value="Status Kepegawaian Bendahara" />
                        <select wire:model.live="status_kepegawaian_bendahara" id="status_kepegawaian_bendahara" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                            <option value="">-- Pilih Status Kepegawaian --</option>
                            @foreach ($statusKepegawaianOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status_kepegawaian_bendahara')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="nip_bendahara" value="NIP Bendahara" />
                        <x-text-input wire:model="nip_bendahara" id="nip_bendahara" class="block mt-1 w-full" type="text"
                            placeholder="{{ $status_kepegawaian_bendahara === 'Honorer' ? 'Ketik tanda -' : '18 digit angka' }}" />
                        <p class="text-xs text-slate-400 mt-1">Wajib 18 digit angka untuk ASN (PNS/ASN PPPK/ASN PPPK-PW), atau ketik tanda "-" untuk Non-ASN (Honorer).</p>
                        <x-input-error :messages="$errors->get('nip_bendahara')" class="mt-2" />
                    </div>
                </div>

                <hr class="border-slate-200">

                <div>
                    <x-input-label for="alamat_sekolah" value="Alamat Sekolah" />
                    <textarea wire:model="alamat_sekolah" id="alamat_sekolah" rows="3" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full"></textarea>
                    <x-input-error :messages="$errors->get('alamat_sekolah')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="batal">Batal</x-secondary-button>
                <x-primary-button>Simpan</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Konfirmasi Hapus Sekolah (Tab 1) --}}
    <x-modal name="sekolah-hapus" :show="$confirmingDeleteId !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Hapus sekolah ini?</h2>
            <p class="mt-1 text-sm text-slate-600">Tindakan ini tidak dapat dibatalkan.</p>

            @if ($errorHapus)
                <div class="mt-3 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                    {{ $errorHapus }}
                </div>
            @endif

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalHapus">Batal</x-secondary-button>
                <x-danger-button wire:click="hapus">Hapus</x-danger-button>
            </div>
        </div>
    </x-modal>

    {{-- Modal Form Tambah/Edit Data PTK (Tab 2, BARU) --}}
    <x-modal name="data-ptk-form" :show="$showFormPtk" maxWidth="2xl">
        <form wire:submit="simpanPtk" class="p-6">
            <h2 class="text-lg font-medium text-slate-900 mb-4">
                {{ $editingPtkId ? 'Edit Data PTK' : 'Tambah Data PTK' }}
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="nik" value="NIK" />
                    <x-text-input wire:model="nik" id="nik" class="block mt-1 w-full" type="text" inputmode="numeric" maxlength="16" />
                    <x-input-error :messages="$errors->get('nik')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="nuptk" value="NUPTK" />
                    <x-text-input wire:model="nuptk" id="nuptk" class="block mt-1 w-full" type="text" inputmode="numeric" maxlength="16" />
                    <x-input-error :messages="$errors->get('nuptk')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="nip" value="NIP" />
                    <x-text-input wire:model="nip" id="nip" class="block mt-1 w-full" type="text" inputmode="numeric" maxlength="18" />
                    <x-input-error :messages="$errors->get('nip')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="nama_ptk" value="Nama PTK" />
                    <x-text-input wire:model="nama_ptk" id="nama_ptk" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('nama_ptk')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="tempat_lahir" value="Tempat Lahir" />
                    <x-text-input wire:model="tempat_lahir" id="tempat_lahir" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('tempat_lahir')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="tanggal_lahir" value="Tanggal Lahir" />
                    <x-text-input wire:model="tanggal_lahir" id="tanggal_lahir" class="block mt-1 w-full" type="date" />
                    <x-input-error :messages="$errors->get('tanggal_lahir')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="jabatan" value="Jabatan" />
                    <select wire:model="jabatan" id="jabatan" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                        <option value="">-- Pilih Jabatan --</option>
                        @foreach ($jabatanOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('jabatan')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="pangkat_golongan" value="Pangkat / Golongan" />
                    <select wire:model="pangkat_golongan" id="pangkat_golongan" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                        <option value="">-- Pilih Pangkat / Golongan --</option>
                        @foreach ($pangkatGolonganOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('pangkat_golongan')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="status_kepegawaian" value="Status Kepegawaian" />
                    <select wire:model="status_kepegawaian" id="status_kepegawaian" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                        <option value="">-- Pilih Status Kepegawaian --</option>
                        @foreach ($statusKepegawaianPtkOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('status_kepegawaian')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="jenis_ptk" value="Jenis PTK" />
                    <select wire:model="jenis_ptk" id="jenis_ptk" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                        <option value="">-- Pilih Jenis PTK --</option>
                        @foreach ($jenisPtkOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('jenis_ptk')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="tmt_sekolah_induk" value="TMT Di Sekolah Induk" />
                    <x-text-input wire:model="tmt_sekolah_induk" id="tmt_sekolah_induk" class="block mt-1 w-full" type="date" />
                    <x-input-error :messages="$errors->get('tmt_sekolah_induk')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="pendidikan_terakhir" value="Pendidikan Terakhir" />
                    <select wire:model="pendidikan_terakhir" id="pendidikan_terakhir" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                        <option value="">-- Pilih Pendidikan Terakhir --</option>
                        @foreach ($pendidikanTerakhirOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('pendidikan_terakhir')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="jurusan_prodi" value="Jurusan / Prodi Sesuai Ijazah Terakhir" />
                    <x-text-input wire:model="jurusan_prodi" id="jurusan_prodi" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('jurusan_prodi')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="tahun_lulus_ijazah" value="Tahun Lulus Ijazah" />
                    <x-text-input wire:model="tahun_lulus_ijazah" id="tahun_lulus_ijazah" class="block mt-1 w-full" type="text" inputmode="numeric" maxlength="4" />
                    <x-input-error :messages="$errors->get('tahun_lulus_ijazah')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="status_sertifikasi" value="Status Sertifikasi" />
                    <select wire:model.live="status_sertifikasi" id="status_sertifikasi" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                        <option value="">-- Pilih Status Sertifikasi --</option>
                        @foreach ($statusSertifikasiOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-400 mt-1">Jika "Belum", field rincian sertifikasi di bawah otomatis terisi tanda "-".</p>
                    <x-input-error :messages="$errors->get('status_sertifikasi')" class="mt-2" />
                </div>

                @php $rincianSertifikasiTerkunci = $status_sertifikasi === \App\Models\DataPtk::STATUS_SERTIFIKASI_BELUM; @endphp

                <div>
                    <x-input-label for="bidang_studi_sertifikasi" value="Bidang Studi Sertifikasi" />
                    <x-text-input wire:model="bidang_studi_sertifikasi" id="bidang_studi_sertifikasi" class="block mt-1 w-full" type="text" @disabled($rincianSertifikasiTerkunci) />
                    <x-input-error :messages="$errors->get('bidang_studi_sertifikasi')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="tahun_lulus_sertifikasi" value="Tahun Lulus Sertifikasi" />
                    <x-text-input wire:model="tahun_lulus_sertifikasi" id="tahun_lulus_sertifikasi" class="block mt-1 w-full" type="text" inputmode="numeric" maxlength="4" @disabled($rincianSertifikasiTerkunci) />
                    <x-input-error :messages="$errors->get('tahun_lulus_sertifikasi')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="nomor_sertifikat_sertifikasi" value="Nomor Sertifikat Sertifikasi" />
                    <x-text-input wire:model="nomor_sertifikat_sertifikasi" id="nomor_sertifikat_sertifikasi" class="block mt-1 w-full" type="text" @disabled($rincianSertifikasiTerkunci) />
                    <x-input-error :messages="$errors->get('nomor_sertifikat_sertifikasi')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="nomor_registrasi_guru" value="Nomor Registrasi Guru" />
                    <x-text-input wire:model="nomor_registrasi_guru" id="nomor_registrasi_guru" class="block mt-1 w-full" type="text" @disabled($rincianSertifikasiTerkunci) />
                    <x-input-error :messages="$errors->get('nomor_registrasi_guru')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="nomor_peserta_sertifikasi" value="Nomor Peserta Sertifikasi" />
                    <x-text-input wire:model="nomor_peserta_sertifikasi" id="nomor_peserta_sertifikasi" class="block mt-1 w-full" type="text" @disabled($rincianSertifikasiTerkunci) />
                    <x-input-error :messages="$errors->get('nomor_peserta_sertifikasi')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="profil_sekolah_id" value="Nama Sekolah" />
                    @if ($bolehKelolaDataPtkSemua)
                        <select wire:model="profil_sekolah_id" id="profil_sekolah_id" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                            <option value="">-- Pilih Sekolah --</option>
                            @foreach ($sekolahOptionsPtk as $sekolah)
                                <option value="{{ $sekolah->id }}">{{ $sekolah->nama_sekolah }}</option>
                            @endforeach
                        </select>
                    @else
                        <x-text-input value="{{ $sekolahOptionsPtk->firstWhere('id', $profil_sekolah_id)->nama_sekolah ?? '-' }}" id="profil_sekolah_id_tampilan" class="block mt-1 w-full bg-slate-50" type="text" disabled />
                    @endif
                    <x-input-error :messages="$errors->get('profil_sekolah_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="status_dapodik" value="Status Dapodik" />
                    <select wire:model="status_dapodik" id="status_dapodik" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                        <option value="">-- Pilih Status Dapodik --</option>
                        @foreach ($statusDapodikOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('status_dapodik')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="status_keaktifan" value="Status Keaktifan" />
                    <select wire:model="status_keaktifan" id="status_keaktifan" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                        <option value="">-- Pilih Status Keaktifan --</option>
                        @foreach ($statusKeaktifanOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('status_keaktifan')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="batalPtk" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                <x-primary-button class="!px-3 !py-1.5 !text-[10px]"><x-icon name="check" class="w-3.5 h-3.5 mr-1" />Simpan</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Konfirmasi Hapus Data PTK (Tab 2) --}}
    <x-modal name="data-ptk-hapus" :show="$confirmingDeletePtkId !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Hapus data PTK ini?</h2>
            <p class="mt-1 text-sm text-slate-600">Tindakan ini tidak dapat dibatalkan.</p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalHapusPtk" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                <x-danger-button wire:click="hapusPtk" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="trash" class="w-3.5 h-3.5 mr-1" />Hapus</x-danger-button>
            </div>
        </div>
    </x-modal>

    {{-- Modal Konfirmasi Hapus Terpilih Data PTK (hapus massal, Tab 2) --}}
    <x-modal name="data-ptk-hapus-terpilih" :show="$confirmingHapusTerpilihPtk" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Hapus {{ count($dipilihPtk) }} data PTK terpilih?</h2>
            <p class="mt-1 text-sm text-slate-600">Semua baris yang dicentang akan dihapus sekaligus. Tindakan ini tidak dapat dibatalkan.</p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalHapusTerpilihPtk" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                <x-danger-button wire:click="hapusTerpilihPtk" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="trash" class="w-3.5 h-3.5 mr-1" />Hapus Semua Terpilih</x-danger-button>
            </div>
        </div>
    </x-modal>

    {{-- Popup wajib: Profil Sekolah milik akun ini belum lengkap --}}
    <x-modal-wajib name="profil-belum-lengkap" :show="$tampilkanPeringatanBelumLengkap" maxWidth="md">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-amber-100 text-amber-600">
                    <x-icon name="alert-circle" class="w-6 h-6" />
                </div>
                <div>
                    <h2 class="text-lg font-medium text-slate-900">Profil Sekolah Belum Lengkap</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Data Profil Sekolah Anda belum lengkap. Mohon segera lengkapi/update data di bawah ini
                        (Kepala Sekolah, Pengawas, Bendahara, dan Alamat Sekolah) supaya menu-menu lain pada
                        aplikasi ini dapat digunakan.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-primary-button wire:click="tutupPeringatanBelumLengkap">Mengerti, Isi Sekarang</x-primary-button>
            </div>
        </div>
    </x-modal-wajib>
</div>
