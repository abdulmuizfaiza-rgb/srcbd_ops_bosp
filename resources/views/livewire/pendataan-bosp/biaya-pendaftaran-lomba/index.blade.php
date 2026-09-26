<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Biaya Pendaftaran Lomba/Bimtek/Workshop') }}
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
                {{-- Tab Triwulan - user tidak menentukan warna khusus untuk menu
                     ini, jadi dipakai skema warna BAWAAN (Sky/Emerald/Amber/
                     Fuchsia) sama seperti Langganan Daya Jasa - teks tab persis
                     sesuai permintaan user 2026-09-11 ("Biaya Pendaftaran
                     Lomba/Bimtek/Workshop TW-n"). --}}
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
                                Biaya Pendaftaran Lomba/Bimtek/Workshop TW-{{ $value }}
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

                            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari uraian / sekolah..." class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">

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
                                    <th class="px-2 py-2.5">Uraian</th>
                                    <th class="px-2 py-2.5">Volume</th>
                                    <th class="px-2 py-2.5">Satuan</th>
                                    <th class="px-2 py-2.5">Tarif Harga</th>
                                    <th class="px-2 py-2.5">Jumlah</th>
                                    <th class="px-2 py-2.5">Tanggal</th>
                                    <th class="px-2 py-2.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            {{-- Tabel diringkas per sekolah dengan simbol "+"/"-" SEJAK
                                 AWAL (pelajaran Part 16, disamakan dengan Langganan Daya
                                 Jasa/Belanja Pemeliharaan Bangunan/PC) - satu <tbody> per
                                 sekolah, masing-masing dengan Alpine x-data sendiri. --}}
                            @php $nomorSekolah = 0; @endphp
                            @forelse ($daftarSekolah as $sekolah)
                                @php
                                    $nomorSekolah++;
                                    $jumlahBaris = $sekolah->biayaPendaftaranLomba->count();
                                    $nomorBaris = 0;
                                @endphp
                                <tbody wire:key="biaya-lomba-grup-{{ $sekolah->id }}" x-data="{ terbuka: false }" class="divide-y divide-slate-100 border-b-2 border-slate-200">
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
                                        <td colspan="6" class="px-2 py-2 whitespace-nowrap text-slate-400 italic">
                                            {{ $jumlahBaris }} data sudah diinput - klik untuk <span x-text="terbuka ? 'menutup' : 'melihat'"></span>
                                        </td>
                                        <td class="px-2 py-2"></td>
                                    </tr>

                                    @foreach ($sekolah->biayaPendaftaranLomba as $row)
                                        @php $nomorBaris++; $revisi = $revisiBaris[$row->id] ?? 0; @endphp
                                        <tr wire:key="biaya-lomba-baris-{{ $row->id }}" x-show="terbuka" x-cloak>
                                            <td class="px-2 py-2">
                                                <input type="checkbox" wire:model="dipilih" value="{{ $row->id }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-slate-400 text-right">{{ $nomorSekolah }}.{{ $nomorBaris }}</td>
                                            <td class="px-2 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->npsn ?? '-' }}</td>
                                            <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800">{{ $sekolah->nama_sekolah ?? '-' }}</td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <input type="text" wire:key="biaya-lomba-input-{{ $row->id }}-uraian" wire:model.blur="baris.{{ $row->id }}.uraian" class="w-40 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$row->id.'.uraian') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                                @error('baris.'.$row->id.'.uraian')
                                                    <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <input type="text" inputmode="numeric" wire:key="biaya-lomba-input-{{ $row->id }}-volume" wire:model.blur="baris.{{ $row->id }}.volume" class="w-16 text-xs text-right rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$row->id.'.volume') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                                @error('baris.'.$row->id.'.volume')
                                                    <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <input type="text" wire:key="biaya-lomba-input-{{ $row->id }}-satuan" wire:model.blur="baris.{{ $row->id }}.satuan" class="w-20 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$row->id.'.satuan') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                                @error('baris.'.$row->id.'.satuan')
                                                    <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <x-honor-ptk-tarif-cell :row-id="$row->id" field="tarif_harga" :value="$baris[$row->id]['tarif_harga'] ?? ''" :revisi="$revisi" />
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-right font-bold text-slate-700">Rp {{ number_format((int) $row->jumlah, 0, ',', '.') }}</td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <input type="date" wire:key="biaya-lomba-input-{{ $row->id }}-tanggal" wire:model.blur="baris.{{ $row->id }}.tanggal" class="text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$row->id.'.tanggal') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                                @error('baris.'.$row->id.'.tanggal')
                                                    <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-right space-x-2">
                                                <button wire:click="edit({{ $row->id }})" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline"><x-icon name="pencil" class="w-3 h-3" />Edit</button>
                                                <button wire:click="konfirmasiHapus({{ $row->id }})" class="inline-flex items-center gap-1 text-xs text-red-600 hover:underline"><x-icon name="trash" class="w-3 h-3" />Hapus</button>
                                            </td>
                                        </tr>
                                    @endforeach

                                    @php $idBaru = -$sekolah->id; $revisiBaru = $revisiBaris[$idBaru] ?? 0; @endphp
                                    <tr wire:key="biaya-lomba-baru-{{ $sekolah->id }}" class="bg-blue-50/40" x-show="terbuka" x-cloak>
                                        <td class="px-2 py-2"></td>
                                        <td class="px-2 py-2 whitespace-nowrap text-slate-300">&nbsp;</td>
                                        <td class="px-2 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->npsn ?? '-' }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800">{{ $sekolah->nama_sekolah ?? '-' }}</td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <input type="text" wire:key="biaya-lomba-input-baru-{{ $sekolah->id }}-uraian" wire:model.blur="baris.{{ $idBaru }}.uraian" placeholder="Uraian" class="w-40 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$idBaru.'.uraian') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-dashed border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                            @error('baris.'.$idBaru.'.uraian')
                                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <input type="text" inputmode="numeric" wire:key="biaya-lomba-input-baru-{{ $sekolah->id }}-volume" wire:model.blur="baris.{{ $idBaru }}.volume" placeholder="0" class="w-16 text-xs text-right rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$idBaru.'.volume') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-dashed border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                            @error('baris.'.$idBaru.'.volume')
                                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <input type="text" wire:key="biaya-lomba-input-baru-{{ $sekolah->id }}-satuan" wire:model.blur="baris.{{ $idBaru }}.satuan" placeholder="Satuan" class="w-20 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$idBaru.'.satuan') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-dashed border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                            @error('baris.'.$idBaru.'.satuan')
                                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <x-honor-ptk-tarif-cell :row-id="$idBaru" field="tarif_harga" :value="$baris[$idBaru]['tarif_harga'] ?? ''" :revisi="$revisiBaru" />
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-right font-bold text-slate-400">Rp 0</td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <input type="date" wire:key="biaya-lomba-input-baru-{{ $sekolah->id }}-tanggal" wire:model.blur="baris.{{ $idBaru }}.tanggal" class="text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$idBaru.'.tanggal') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-dashed border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                            @error('baris.'.$idBaru.'.tanggal')
                                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-right">
                                            <button type="button" x-on:click="$el.closest('tr').querySelector('input,select')?.focus()" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline">
                                                <x-icon name="plus" class="w-3 h-3" />Baris baru
                                            </button>
                                        </td>
                                    </tr>

                                    {{-- Baris Jumlah Biaya Pendaftaran Lomba/Bimtek/Workshop PER SEKOLAH
                                         - permintaan user 2026-09-16: "tambahkan baris Jumlah Biaya
                                         Pendaftaran Lomba/Bimtek/Workshop (kolom 1 sampai kolom 7 di
                                         merge cell) dan kolom Jumlah di total kan". Sengaja ikut
                                         x-show="terbuka" + x-cloak (sama seperti baris detail lain) -
                                         jawaban AskUserQuestion "Per sekolah + Total seluruh sekolah":
                                         baris ini HANYA tampil saat grup sekolah dibuka, pola sama
                                         seperti Langganan Daya Jasa. Kolom 1-7 (No s.d. Tarif Harga)
                                         digabung jadi satu sel label (colspan="7"), nilai total di kolom
                                         ke-8 (Jumlah), kolom Tanggal & Aksi dikosongkan (colspan="2"). --}}
                                    <tr wire:key="biaya-lomba-total-sekolah-{{ $sekolah->id }}" class="bg-slate-100 font-bold text-slate-700 border-t-2 border-slate-300" x-show="terbuka" x-cloak>
                                        <td colspan="8" class="px-2 py-2 text-right">Jumlah Biaya Pendaftaran Lomba/Bimtek/Workshop</td>
                                        <td class="px-2 py-2 whitespace-nowrap text-right">Rp {{ number_format((int) $sekolah->biayaPendaftaranLomba->sum('jumlah'), 0, ',', '.') }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tbody>
                            @empty
                                <tbody>
                                    <tr>
                                        <td colspan="11" class="px-3 py-6 text-center text-slate-400">Tidak ada sekolah yang bisa ditampilkan.</td>
                                    </tr>
                                </tbody>
                            @endforelse

                            {{-- Baris Jumlah Biaya Pendaftaran Lomba/Bimtek/Workshop UNTUK SELURUH
                                 SEKOLAH - jawaban AskUserQuestion "Per sekolah + Total seluruh
                                 sekolah". BEDA dengan baris Jumlah per sekolah di atas (yang ikut
                                 tersembunyi/tampil bersama grupnya) - baris ini SELALU terlihat di
                                 paling bawah tabel, tidak collapsible. Nilainya
                                 ($totalBiayaKeseluruhan, dihitung di render()) otomatis ikut scope
                                 peran & filter yang sedang aktif karena dijumlahkan dari
                                 $daftarSekolah yang sama dengan yang dirender di atas. --}}
                            @if ($daftarSekolah->isNotEmpty())
                                <tbody>
                                    <tr class="bg-slate-200 font-bold text-slate-800 border-t-2 border-slate-400">
                                        <td colspan="8" class="px-2 py-2.5 text-right">Jumlah Biaya Pendaftaran Lomba/Bimtek/Workshop Seluruh Sekolah</td>
                                        <td class="px-2 py-2.5 whitespace-nowrap text-right">Rp {{ number_format((int) $totalBiayaKeseluruhan, 0, ',', '.') }}</td>
                                        <td colspan="2"></td>
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
    <x-modal name="biaya-pendaftaran-lomba-form" :show="$showForm" maxWidth="lg">
        <form wire:submit="simpan" class="p-6">
            <h2 class="text-lg font-medium text-slate-900 mb-1">
                {{ $editingId ? 'Edit Data Biaya Pendaftaran Lomba/Bimtek/Workshop' : 'Tambah Data Biaya Pendaftaran Lomba/Bimtek/Workshop' }}
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

                <div class="sm:col-span-2">
                    <x-input-label for="uraian" value="Uraian" />
                    <x-text-input wire:model="uraian" id="uraian" class="block mt-1 w-full" type="text" placeholder="mis. Pendaftaran Lomba OSN, Bimtek Kurikulum" />
                    <x-input-error :messages="$errors->get('uraian')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="volume" value="Volume" />
                    <x-text-input wire:model="volume" id="volume" class="block mt-1 w-full" type="text" inputmode="numeric" />
                    <x-input-error :messages="$errors->get('volume')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="satuan" value="Satuan" />
                    <x-text-input wire:model="satuan" id="satuan" class="block mt-1 w-full" type="text" placeholder="mis. Orang" />
                    <x-input-error :messages="$errors->get('satuan')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="tarif_harga" value="Tarif Harga" />
                    <x-currency-input name="tarif_harga" :value="$tarif_harga" :reset-key="$formInstance" />
                    <x-input-error :messages="$errors->get('tarif_harga')" class="mt-2" />
                </div>

                <div
                    x-data
                    x-init="
                        hitungJumlah = () => {
                            const v = parseInt(String($wire.volume ?? '').replace(/\D/g, '')) || 0;
                            const t = parseInt(String($wire.tarif_harga ?? '').replace(/\D/g, '')) || 0;
                            return new Intl.NumberFormat('id-ID').format(v * t);
                        }
                    "
                >
                    <x-input-label value="Jumlah" />
                    <div class="mt-1 block w-full rounded-md border border-blue-200 bg-blue-50 text-slate-700 text-sm px-3 py-2 text-right" x-text="'Rp ' + hitungJumlah()"></div>
                    <p class="text-xs text-slate-400 mt-1">Otomatis: Volume x Tarif Harga (dihitung ulang saat disimpan).</p>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="tanggal" value="Tanggal" />
                    <x-text-input wire:model="tanggal" id="tanggal" class="block mt-1 w-full" type="date" />
                    <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="batal" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                <x-primary-button class="!px-3 !py-1.5 !text-[10px]"><x-icon name="check" class="w-3.5 h-3.5 mr-1" />Simpan</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Konfirmasi Hapus --}}
    <x-modal name="biaya-pendaftaran-lomba-hapus" :show="$confirmingDeleteId !== null" maxWidth="md">
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
    <x-modal name="biaya-pendaftaran-lomba-hapus-terpilih" :show="$confirmingHapusTerpilih" maxWidth="md">
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
