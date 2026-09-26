<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Belanja Pemeliharaan PC Komputer-Laptop-Printer dll') }}
    </h2>
</x-slot>

<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($terkunciTriwulanIni)
                <div class="p-3 bg-amber-50 border border-amber-300 text-amber-800 rounded-lg text-xs flex items-start gap-2">
                    <x-icon name="lock-closed" class="w-4 h-4 shrink-0 mt-0.5" />
                    <span>Triwulan ini sudah divalidasi <strong>&quot;Sesuai&quot;</strong> pada halaman Laporan Realisasi BOSP (Form BPK) - datanya <strong>terkunci permanen</strong>, tidak bisa ditambah/diedit/dihapus lagi. Hubungi Superadmin kalau triwulan ini perlu dibuka kembali.</span>
                </div>
            @endif

            @if ($errorImport)
                <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm whitespace-pre-line">
                    {{ $errorImport }}
                </div>
            @endif

            @if ($errorExport)
                <div class="p-4 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-sm">
                    {{ $errorExport }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                {{-- Tab UTAMA (Rincian Pemeliharaan PC Komputer-Laptop-Printer dll /
                     Rincian Jasa Pemeliharaan PC-Laptop-Printer dll) - lapis tab PALING
                     ATAS, baru di bawahnya ada tab Triwulan (permintaan user
                     2026-09-10, Part 18: "menu baru... yang terdiri dari 2 tab...
                     semua tab dilengkapi... juga berdasarkan tahun"). Triwulan aktif
                     TIDAK direset saat pindah tab utama (lihat Index::pindahTabUtama()). --}}
                <div class="border-b border-slate-200 px-4 sm:px-8 pt-4">
                    <nav class="flex flex-wrap gap-3">
                        @foreach ($jenisOptions as $value => $label)
                            <button
                                type="button"
                                wire:click="pindahTabUtama('{{ $value }}')"
                                @class([
                                    'px-4 py-2.5 rounded-t-xl text-sm font-bold tracking-wide transition-all duration-150 border-b-4',
                                    'bg-white text-slate-800 border-indigo-500 shadow-sm' => $tabUtama === $value,
                                    'bg-slate-50 text-slate-500 border-transparent hover:bg-slate-100 hover:text-slate-700' => $tabUtama !== $value,
                                ])
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </nav>
                </div>

                {{-- Tab Triwulan - skema warna BAWAAN (Sky/Emerald/Amber/Fuchsia)
                     sama seperti Belanja Pemeliharaan Bangunan, label teks berubah
                     mengikuti tab utama yang aktif. --}}
                <div class="border-b border-slate-200 px-4 sm:px-8 pt-4 pb-4">
                    <nav class="flex flex-wrap gap-3">
                        @foreach ($triwulanOptions as $value => $label)
                            @php
                                $skema = match ($value) {
                                    1 => ['aktif' => 'from-sky-400 to-sky-600', 'nonaktif' => 'from-sky-50 to-sky-100 text-sky-700 ring-sky-200'],
                                    2 => ['aktif' => 'from-emerald-400 to-emerald-600', 'nonaktif' => 'from-emerald-50 to-emerald-100 text-emerald-700 ring-emerald-200'],
                                    3 => ['aktif' => 'from-amber-400 to-amber-600', 'nonaktif' => 'from-amber-50 to-amber-100 text-amber-700 ring-amber-200'],
                                    4 => ['aktif' => 'from-fuchsia-400 to-fuchsia-600', 'nonaktif' => 'from-fuchsia-50 to-fuchsia-100 text-fuchsia-700 ring-fuchsia-200'],
                                };
                                $labelTab = $tabUtama === 'jasa' ? 'Rincian Jasa Pemeliharaan PC-Laptop-Printer dll' : 'Rincian Pemeliharaan PC-Laptop-Printer dll';
                            @endphp
                            <button
                                type="button"
                                wire:click="pindahTab({{ $value }})"
                                @class([
                                    'px-4 py-2 rounded-xl text-sm font-bold tracking-wide transition-all duration-150 ring-1 bg-gradient-to-b',
                                    $skema['aktif'].' text-white shadow-lg ring-black/10 border-b-4 border-black/20 scale-105' => $triwulan === $value,
                                    $skema['nonaktif'].' shadow-sm hover:shadow-md hover:scale-[1.02] border-b-2 border-black/5' => $triwulan !== $value,
                                ])
                            >
                                {{ $labelTab }} TW-{{ $value }}
                            </button>
                        @endforeach
                    </nav>
                </div>

                <div class="p-4 sm:p-8">
                    <div class="flex flex-col xl:flex-row xl:flex-wrap xl:items-start xl:justify-between gap-3 mb-6">
                        <div class="flex flex-wrap sm:flex-row sm:items-center gap-2.5">
                            <label for="tahun" class="text-sm text-slate-600">Tahun</label>
                            <select wire:model.live="tahun" id="tahun" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">
                                @foreach ($tahunOptions as $opsiTahun)
                                    <option value="{{ $opsiTahun }}">{{ $opsiTahun }}</option>
                                @endforeach
                            </select>

                            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama barang / kode UPB / sekolah..." class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">

                            @if ($bolehKelolaSemua)
                                <select wire:model.live="filterSekolahId" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">
                                    <option value="">Semua Sekolah</option>
                                    @foreach ($sekolahOptions as $sekolah)
                                        <option value="{{ $sekolah->id }}">{{ $sekolah->nama_sekolah }}</option>
                                    @endforeach
                                </select>
                            @endif

                            <x-zoom-controls :zoom="$zoomPercent" />
                        </div>

                        <div class="flex flex-wrap sm:flex-row sm:items-center gap-2.5">
                            <div class="flex items-center gap-1.5 border border-black/10 rounded-md px-1.5 py-1 bg-black/10">
                                <input type="file" wire:model="fileImport" accept=".xlsx,.xls,.csv" @disabled($terkunciTriwulanIni) class="text-[11px] text-slate-700 w-28 sm:w-36 file:mr-1.5 file:py-0.5 file:px-1.5 file:rounded file:border-0 file:bg-white file:text-slate-700 file:text-[11px] file:shadow-sm hover:file:bg-slate-100">
                                <x-secondary-button wire:click="import" wire:loading.attr="disabled" wire:target="fileImport,import" :disabled="$terkunciTriwulanIni" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="upload" class="w-3.5 h-3.5 mr-1" />
                                    Import Excel
                                </x-secondary-button>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <x-secondary-button wire:click="export" wire:loading.attr="disabled" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="download" class="w-3.5 h-3.5 mr-1" />
                                    Export Excel
                                </x-secondary-button>
                                <x-primary-button type="button" wire:click="tambah" :disabled="$terkunciTriwulanIni" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="plus" class="w-3.5 h-3.5 mr-1" />
                                    Tambah
                                </x-primary-button>
                                <x-danger-button type="button" wire:click="konfirmasiHapusTerpilih" wire:loading.attr="disabled" :disabled="count($dipilih) === 0 || $terkunciTriwulanIni" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="trash" class="w-3.5 h-3.5 mr-1" />
                                    Hapus Terpilih ({{ count($dipilih) }})
                                </x-danger-button>
                            </div>
                        </div>
                    </div>

                    <div wire:loading wire:target="fileImport,import" class="text-xs text-slate-400 -mt-3 mb-3">Memproses import...</div>
                    <x-input-error :messages="$errors->get('fileImport')" class="text-xs -mt-3 mb-3 block" />

                    <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg{{ $terkunciTriwulanIni ? ' pointer-events-none opacity-60 select-none' : '' }}" style="max-height: 32rem; zoom: {{ $zoomPercent }}%;">
                        <table class="min-w-full divide-y divide-slate-200 text-xs">
                            <thead class="sticky top-0 z-10 bg-slate-50">
                                <tr class="text-left text-slate-500">
                                    <th class="px-2 py-2.5">
                                        <input type="checkbox" wire:click="toggleSemua" @checked($semuaTerpilih) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" title="Pilih/batal pilih semua baris yang tampil">
                                    </th>
                                    <th class="px-2 py-2.5">No</th>
                                    <th class="px-2 py-2.5">NPSN</th>
                                    <th class="px-2 py-2.5">Nama Sekolah</th>
                                    <th class="px-2 py-2.5">Kode UPB</th>
                                    <th class="px-2 py-2.5">Nama Barang</th>
                                    <th class="px-2 py-2.5">Nama Merk Barang</th>
                                    <th class="px-2 py-2.5">Volume</th>
                                    <th class="px-2 py-2.5">Satuan</th>
                                    <th class="px-2 py-2.5">Harga Satuan</th>
                                    <th class="px-2 py-2.5">Total Harga</th>
                                    <th class="px-2 py-2.5">Asal Usul</th>
                                    <th class="px-2 py-2.5">Tanggal</th>
                                    <th class="px-2 py-2.5">Keterangan</th>
                                    <th class="px-2 py-2.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            {{-- Pola tbody-per-sekolah dengan simbol +/- di Kolom No, SAMA
                                 PERSIS seperti Belanja Pemeliharaan Bangunan (Part 17) -
                                 diterapkan sejak awal di menu baru ini karena menu ini juga
                                 "banyak baris per sekolah" (jawaban AskUserQuestion
                                 2026-09-10). --}}
                            @php $nomorSekolah = 0; @endphp
                            @forelse ($daftarSekolah as $sekolah)
                                @php
                                    $nomorSekolah++;
                                    $jumlahBaris = $sekolah->rincianPemeliharaanPc->count();
                                    $nomorBaris = 0;
                                @endphp
                                <tbody wire:key="rincian-pemeliharaan-pc-grup-{{ $sekolah->id }}" x-data="{ terbuka: false }" class="divide-y divide-slate-100 border-b-2 border-slate-200">
                                    <tr class="bg-slate-50/70 hover:bg-slate-100 cursor-pointer select-none" x-on:click="terbuka = ! terbuka">
                                        <td class="px-2 py-2" x-on:click.stop></td>
                                        <td class="px-2 py-2 whitespace-nowrap text-slate-600 font-semibold">
                                            <span class="inline-flex items-center gap-1.5">
                                                <span class="inline-flex items-center justify-center w-4 h-4 rounded border border-slate-300 bg-white text-slate-500 shrink-0">
                                                    <x-icon name="plus" class="w-2.5 h-2.5" x-show="! terbuka" x-cloak />
                                                    <x-icon name="minus" class="w-2.5 h-2.5" x-show="terbuka" x-cloak />
                                                </span>
                                                {{ $nomorSekolah }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->npsn ?? '-' }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800">{{ $sekolah->nama_sekolah ?? '-' }}</td>
                                        <td colspan="10" class="px-2 py-2 whitespace-nowrap text-slate-400 italic">
                                            {{ $jumlahBaris }} data sudah diinput - klik untuk <span x-text="terbuka ? 'menutup' : 'melihat'"></span>
                                        </td>
                                        <td class="px-2 py-2"></td>
                                    </tr>

                                    @foreach ($sekolah->rincianPemeliharaanPc as $row)
                                        @php $nomorBaris++; $revisi = $revisiBaris[$row->id] ?? 0; @endphp
                                        <tr wire:key="rincian-pemeliharaan-pc-baris-{{ $row->id }}" x-show="terbuka" x-cloak>
                                            <td class="px-2 py-2">
                                                <input type="checkbox" wire:model="dipilih" value="{{ $row->id }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-slate-400 text-right">{{ $nomorSekolah }}.{{ $nomorBaris }}</td>
                                            <td class="px-2 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->npsn ?? '-' }}</td>
                                            <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800">{{ $sekolah->nama_sekolah ?? '-' }}</td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <input type="text" wire:key="pemeliharaan-pc-input-{{ $row->id }}-kode_upb" wire:model.blur="baris.{{ $row->id }}.kode_upb" class="w-24 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$row->id.'.kode_upb') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                                @error('baris.'.$row->id.'.kode_upb')
                                                    <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <input type="text" wire:key="pemeliharaan-pc-input-{{ $row->id }}-nama_barang" wire:model.blur="baris.{{ $row->id }}.nama_barang" class="w-36 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$row->id.'.nama_barang') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                                @error('baris.'.$row->id.'.nama_barang')
                                                    <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <input type="text" wire:key="pemeliharaan-pc-input-{{ $row->id }}-nama_merk_barang" wire:model.blur="baris.{{ $row->id }}.nama_merk_barang" class="w-28 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$row->id.'.nama_merk_barang') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                                @error('baris.'.$row->id.'.nama_merk_barang')
                                                    <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <input type="text" inputmode="numeric" wire:key="pemeliharaan-pc-input-{{ $row->id }}-volume" wire:model.blur="baris.{{ $row->id }}.volume" class="w-16 text-xs text-right rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$row->id.'.volume') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                                @error('baris.'.$row->id.'.volume')
                                                    <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <input type="text" wire:key="pemeliharaan-pc-input-{{ $row->id }}-satuan" wire:model.blur="baris.{{ $row->id }}.satuan" class="w-20 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$row->id.'.satuan') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                                @error('baris.'.$row->id.'.satuan')
                                                    <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <x-honor-ptk-tarif-cell :row-id="$row->id" field="harga_satuan" :value="$baris[$row->id]['harga_satuan'] ?? ''" :revisi="$revisi" />
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-right font-bold text-slate-700">Rp {{ number_format((int) $row->total_harga, 0, ',', '.') }}</td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <input type="text" wire:key="pemeliharaan-pc-input-{{ $row->id }}-asal_usul" wire:model.blur="baris.{{ $row->id }}.asal_usul" class="w-28 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$row->id.'.asal_usul') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                                @error('baris.'.$row->id.'.asal_usul')
                                                    <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <input type="date" wire:key="pemeliharaan-pc-input-{{ $row->id }}-tanggal" wire:model.blur="baris.{{ $row->id }}.tanggal" class="text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$row->id.'.tanggal') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                                @error('baris.'.$row->id.'.tanggal')
                                                    <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <input type="text" wire:key="pemeliharaan-pc-input-{{ $row->id }}-keterangan" wire:model.blur="baris.{{ $row->id }}.keterangan" class="w-28 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$row->id.'.keterangan') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                                @error('baris.'.$row->id.'.keterangan')
                                                    <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-right space-x-2">
                                                <button wire:click="edit({{ $row->id }})" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline"><x-icon name="pencil" class="w-3 h-3" />Edit</button>
                                                <button wire:click="konfirmasiHapus({{ $row->id }})" class="inline-flex items-center gap-1 text-xs text-red-600 hover:underline"><x-icon name="trash" class="w-3 h-3" />Hapus</button>
                                            </td>
                                        </tr>
                                    @endforeach

                                    {{-- Baris placeholder kosong siap-isi, SELALU ada di akhir tiap
                                         sekolah - pola sama seperti Belanja Pemeliharaan Bangunan.
                                         Kunci NEGATIF (-ID sekolah); begitu salah satu kotaknya diisi
                                         & di-blur, baris baru langsung dibuat (lihat
                                         Index::updatedBarisBaru()) & placeholder ini otomatis kosong
                                         lagi. Nama Barang BOLEH duplikat (jawaban AskUserQuestion). --}}
                                    @php $idBaru = -$sekolah->id; $revisiBaru = $revisiBaris[$idBaru] ?? 0; @endphp
                                    <tr wire:key="rincian-pemeliharaan-pc-baru-{{ $sekolah->id }}" class="bg-blue-50/40" x-show="terbuka" x-cloak>
                                        <td class="px-2 py-2"></td>
                                        <td class="px-2 py-2 whitespace-nowrap text-slate-300">&nbsp;</td>
                                        <td class="px-2 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->npsn ?? '-' }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800">{{ $sekolah->nama_sekolah ?? '-' }}</td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <input type="text" wire:key="pemeliharaan-pc-input-baru-{{ $sekolah->id }}-kode_upb" wire:model.blur="baris.{{ $idBaru }}.kode_upb" placeholder="Kode UPB" class="w-24 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$idBaru.'.kode_upb') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-dashed border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                            @error('baris.'.$idBaru.'.kode_upb')
                                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <input type="text" wire:key="pemeliharaan-pc-input-baru-{{ $sekolah->id }}-nama_barang" wire:model.blur="baris.{{ $idBaru }}.nama_barang" placeholder="Nama Barang" class="w-36 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$idBaru.'.nama_barang') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-dashed border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                            @error('baris.'.$idBaru.'.nama_barang')
                                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <input type="text" wire:key="pemeliharaan-pc-input-baru-{{ $sekolah->id }}-nama_merk_barang" wire:model.blur="baris.{{ $idBaru }}.nama_merk_barang" placeholder="Merk" class="w-28 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$idBaru.'.nama_merk_barang') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-dashed border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                            @error('baris.'.$idBaru.'.nama_merk_barang')
                                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <input type="text" inputmode="numeric" wire:key="pemeliharaan-pc-input-baru-{{ $sekolah->id }}-volume" wire:model.blur="baris.{{ $idBaru }}.volume" placeholder="0" class="w-16 text-xs text-right rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$idBaru.'.volume') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-dashed border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                            @error('baris.'.$idBaru.'.volume')
                                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <input type="text" wire:key="pemeliharaan-pc-input-baru-{{ $sekolah->id }}-satuan" wire:model.blur="baris.{{ $idBaru }}.satuan" placeholder="Satuan" class="w-20 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$idBaru.'.satuan') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-dashed border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                            @error('baris.'.$idBaru.'.satuan')
                                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <x-honor-ptk-tarif-cell :row-id="$idBaru" field="harga_satuan" :value="$baris[$idBaru]['harga_satuan'] ?? ''" :revisi="$revisiBaru" />
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-right font-bold text-slate-400">Rp 0</td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <input type="text" wire:key="pemeliharaan-pc-input-baru-{{ $sekolah->id }}-asal_usul" wire:model.blur="baris.{{ $idBaru }}.asal_usul" placeholder="Asal Usul" class="w-28 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$idBaru.'.asal_usul') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-dashed border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                            @error('baris.'.$idBaru.'.asal_usul')
                                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <input type="date" wire:key="pemeliharaan-pc-input-baru-{{ $sekolah->id }}-tanggal" wire:model.blur="baris.{{ $idBaru }}.tanggal" class="text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$idBaru.'.tanggal') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-dashed border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                            @error('baris.'.$idBaru.'.tanggal')
                                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <input type="text" wire:key="pemeliharaan-pc-input-baru-{{ $sekolah->id }}-keterangan" wire:model.blur="baris.{{ $idBaru }}.keterangan" placeholder="Keterangan" class="w-28 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$idBaru.'.keterangan') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-dashed border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                            @error('baris.'.$idBaru.'.keterangan')
                                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-right">
                                            <button type="button" x-on:click="$el.closest('tr').querySelector('input,select')?.focus()" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline">
                                                <x-icon name="plus" class="w-3 h-3" />Baris baru
                                            </button>
                                        </td>
                                    </tr>

                                    {{-- Baris Jumlah PER SEKOLAH - permintaan user 2026-09-16: "tambahkan
                                         baris Jumlah ... (kolom 1 sampai kolom 9 di merge cell) dan kolom
                                         Total Harga di total kan". Label mengikuti tab utama aktif
                                         ($tabUtama): "Jumlah Rincian Pemeliharaan PC Komputer-Laptop-
                                         Printer dll" utk jenis "barang", "Jumlah Rincian Jasa Pemeliharaan
                                         PC-Laptop-Printer dll" utk jenis "jasa". Sengaja ikut
                                         x-show="terbuka" + x-cloak (sama seperti baris detail lain) -
                                         jawaban AskUserQuestion "Per sekolah + Total seluruh sekolah":
                                         baris ini HANYA tampil saat grup sekolah dibuka. Kolom 1-9 (No
                                         s.d. Harga Satuan) digabung jadi satu sel label (colspan="9"),
                                         nilai total di kolom ke-10 (Total Harga), kolom Asal Usul/
                                         Tanggal/Keterangan/Aksi dikosongkan (colspan="4"). --}}
                                    <tr wire:key="rincian-pemeliharaan-pc-total-sekolah-{{ $sekolah->id }}" class="bg-slate-100 font-bold text-slate-700 border-t-2 border-slate-300" x-show="terbuka" x-cloak>
                                        <td colspan="10" class="px-2 py-2 text-right">{{ $tabUtama === \App\Models\RincianPemeliharaanPc::JENIS_JASA ? 'Jumlah Rincian Jasa Pemeliharaan PC-Laptop-Printer dll' : 'Jumlah Rincian Pemeliharaan PC Komputer-Laptop-Printer dll' }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap text-right">Rp {{ number_format((int) $sekolah->rincianPemeliharaanPc->sum('total_harga'), 0, ',', '.') }}</td>
                                        <td colspan="4"></td>
                                    </tr>
                                </tbody>
                            @empty
                                <tbody>
                                    <tr>
                                        <td colspan="15" class="px-3 py-6 text-center text-slate-400">Tidak ada sekolah yang bisa ditampilkan.</td>
                                    </tr>
                                </tbody>
                            @endforelse

                            {{-- Baris Jumlah UNTUK SELURUH SEKOLAH - jawaban AskUserQuestion "Per
                                 sekolah + Total seluruh sekolah". BEDA dengan baris Jumlah per
                                 sekolah di atas (yang ikut tersembunyi/tampil bersama grupnya) -
                                 baris ini SELALU terlihat di paling bawah tabel, tidak collapsible.
                                 Nilainya ($totalHargaKeseluruhan, dihitung di render()) otomatis ikut
                                 scope peran, filter, & tab utama yang sedang aktif karena dijumlahkan
                                 dari $daftarSekolah yang sama dengan yang dirender di atas. --}}
                            @if ($daftarSekolah->isNotEmpty())
                                <tbody>
                                    <tr class="bg-slate-200 font-bold text-slate-800 border-t-2 border-slate-400">
                                        <td colspan="10" class="px-2 py-2.5 text-right">{{ $tabUtama === \App\Models\RincianPemeliharaanPc::JENIS_JASA ? 'Jumlah Rincian Jasa Pemeliharaan PC-Laptop-Printer dll Seluruh Sekolah' : 'Jumlah Rincian Pemeliharaan PC Komputer-Laptop-Printer dll Seluruh Sekolah' }}</td>
                                        <td class="px-2 py-2.5 whitespace-nowrap text-right">Rp {{ number_format((int) $totalHargaKeseluruhan, 0, ',', '.') }}</td>
                                        <td colspan="4"></td>
                                    </tr>
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Form Tambah/Edit --}}
    <x-modal name="belanja-pemeliharaan-pc-form" :show="$showForm" maxWidth="lg">
        <form wire:submit="simpan" class="p-6">
            <h2 class="text-lg font-medium text-slate-900 mb-1">
                {{ $editingId ? 'Edit Data '.($jenisOptions[$tabUtama] ?? '') : 'Tambah Data '.($jenisOptions[$tabUtama] ?? '') }}
            </h2>
            <p class="text-sm text-slate-500 mb-4">Tahun {{ $tahun }} - {{ $triwulanOptions[$triwulan] }}</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <x-input-label for="profil_sekolah_id" value="Sekolah" />
                    <select wire:model="profil_sekolah_id" id="profil_sekolah_id" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full" @disabled(! $bolehKelolaSemua)>
                        <option value="">-- Pilih Sekolah --</option>
                        @foreach ($sekolahOptions as $sekolah)
                            <option value="{{ $sekolah->id }}">{{ $sekolah->nama_sekolah }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-400 mt-1">NPSN & Nama Sekolah otomatis mengikuti data pada menu Profil Sekolah.</p>
                    <x-input-error :messages="$errors->get('profil_sekolah_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="kode_upb" value="Kode UPB" />
                    <x-text-input wire:model="kode_upb" id="kode_upb" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('kode_upb')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="nama_barang" value="Nama Barang" />
                    <x-text-input wire:model="nama_barang" id="nama_barang" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('nama_barang')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="nama_merk_barang" value="Nama Merk Barang" />
                    <x-text-input wire:model="nama_merk_barang" id="nama_merk_barang" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('nama_merk_barang')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="volume" value="Volume" />
                    <x-text-input wire:model="volume" id="volume" class="block mt-1 w-full" type="text" inputmode="numeric" />
                    <x-input-error :messages="$errors->get('volume')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="satuan" value="Satuan" />
                    <x-text-input wire:model="satuan" id="satuan" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('satuan')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="harga_satuan" value="Harga Satuan" />
                    <x-currency-input name="harga_satuan" :value="$harga_satuan" :reset-key="$formInstance" />
                    <x-input-error :messages="$errors->get('harga_satuan')" class="mt-2" />
                </div>

                <div
                    x-data
                    x-init="
                        hitungTotalHarga = () => {
                            const v = parseInt(String($wire.volume ?? '').replace(/\D/g, '')) || 0;
                            const h = parseInt(String($wire.harga_satuan ?? '').replace(/\D/g, '')) || 0;
                            return new Intl.NumberFormat('id-ID').format(v * h);
                        }
                    "
                >
                    <x-input-label value="Total Harga" />
                    {{-- Dihitung LANGSUNG di sisi klien (Alpine), pola sama seperti
                         Belanja Pemeliharaan Bangunan. --}}
                    <div class="mt-1 block w-full rounded-md border border-blue-200 bg-blue-50 text-slate-700 text-sm px-3 py-2 text-right" x-text="'Rp ' + hitungTotalHarga()"></div>
                    <p class="text-xs text-slate-400 mt-1">Otomatis: Volume x Harga Satuan (dihitung ulang saat disimpan).</p>
                </div>

                <div>
                    <x-input-label for="asal_usul" value="Asal Usul" />
                    <x-text-input wire:model="asal_usul" id="asal_usul" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('asal_usul')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="tanggal" value="Tanggal" />
                    <x-text-input wire:model="tanggal" id="tanggal" class="block mt-1 w-full" type="date" />
                    <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="keterangan" value="Keterangan" />
                    <textarea wire:model="keterangan" id="keterangan" rows="2" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full text-sm"></textarea>
                    <x-input-error :messages="$errors->get('keterangan')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="batal" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                <x-primary-button class="!px-3 !py-1.5 !text-[10px]"><x-icon name="check" class="w-3.5 h-3.5 mr-1" />Simpan</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Konfirmasi Hapus --}}
    <x-modal name="belanja-pemeliharaan-pc-hapus" :show="$confirmingDeleteId !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Hapus data ini?</h2>
            <p class="mt-1 text-sm text-slate-600">Tindakan ini tidak dapat dibatalkan.</p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalHapus" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                <x-danger-button wire:click="hapus" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="trash" class="w-3.5 h-3.5 mr-1" />Hapus</x-danger-button>
            </div>
        </div>
    </x-modal>

    {{-- Modal Konfirmasi Hapus Terpilih (hapus massal) - permintaan user
         2026-09-26, pola sama seperti Penerimaan Honor PTK. --}}
    <x-modal name="belanja-pemeliharaan-pc-hapus-terpilih" :show="$confirmingHapusTerpilih" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Hapus {{ count($dipilih) }} data terpilih?</h2>
            <p class="mt-1 text-sm text-slate-600">Semua baris yang dicentang akan dihapus sekaligus. Tindakan ini tidak dapat dibatalkan.</p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalHapusTerpilih" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                <x-danger-button wire:click="hapusTerpilih" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="trash" class="w-3.5 h-3.5 mr-1" />Hapus Semua Terpilih</x-danger-button>
            </div>
        </div>
    </x-modal>
</div>
