<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Penerimaan Honor PTK') }}
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
                {{-- Tab Triwulan - warna KHUSUS menu ini (Biru/Ungu/Kuning/Hijau,
                     permintaan user 2026-09-10), sengaja tidak memakai
                     <x-tab-triwulan> yang skema warnanya beda (dipakai
                     Lampiran 2a/2b/2c) supaya menu-menu itu tidak ikut berubah. --}}
                <div class="border-b border-slate-200 px-4 sm:px-8 pt-4 pb-4">
                    <nav class="flex flex-wrap gap-3">
                        @foreach ($triwulanOptions as $value => $info)
                            @php
                                $skema = match ($info['warna']) {
                                    'blue' => ['aktif' => 'from-blue-400 to-blue-600', 'nonaktif' => 'from-blue-50 to-blue-100 text-blue-700 ring-blue-200'],
                                    'purple' => ['aktif' => 'from-purple-400 to-purple-600', 'nonaktif' => 'from-purple-50 to-purple-100 text-purple-700 ring-purple-200'],
                                    'yellow' => ['aktif' => 'from-yellow-400 to-yellow-600', 'nonaktif' => 'from-yellow-50 to-yellow-100 text-yellow-700 ring-yellow-200'],
                                    'green' => ['aktif' => 'from-green-400 to-green-600', 'nonaktif' => 'from-green-50 to-green-100 text-green-700 ring-green-200'],
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
                                Penerimaan Honor PTK TW-{{ $value }}
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

                            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama penerima / NUPTK / sekolah..." class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">

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
                                    <th class="px-2 py-2.5">NUPTK</th>
                                    <th class="px-2 py-2.5">Nama Penerima</th>
                                    <th class="px-2 py-2.5">Volume</th>
                                    <th class="px-2 py-2.5">Satuan</th>
                                    <th class="px-2 py-2.5">Tarif Harga</th>
                                    <th class="px-2 py-2.5">Jumlah Honor Yang Diterima</th>
                                    <th class="px-2 py-2.5">Tanggal Bayar</th>
                                    <th class="px-2 py-2.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            {{-- Sengaja pakai BEBERAPA <tbody> (bukan satu <tbody> berisi semua
                                 baris) - satu <tbody> per sekolah, masing-masing dengan Alpine
                                 x-data sendiri (terbuka/tertutup). Ini HTML5 valid (satu <table>
                                 boleh punya banyak <tbody>) dan diperlukan supaya baris-baris
                                 PTK tiap sekolah bisa disembunyikan/ditampilkan lewat simbol
                                 +/- tanpa merusak struktur tabel (membungkus <tr> dengan <div>
                                 tidak valid HTML). Permintaan user 2026-09-10 poin 3: simbol +
                                 di Kolom No (sebelum nomor) untuk membuka/menutup baris data
                                 yang sudah diinput tiap sekolah - jawaban AskUserQuestion
                                 "Tabel diringkas per sekolah, + untuk buka/tutup (Recommended)". --}}
                            @php $nomorSekolah = 0; @endphp
                            @forelse ($daftarSekolah as $sekolah)
                                @php
                                    $nomorSekolah++;
                                    $jumlahBaris = $sekolah->penerimaanHonorPtk->count();
                                    $nomorBaris = 0;
                                    $totalSekolah = $sekolah->penerimaanHonorPtk->sum('jumlah_honor');
                                @endphp
                                <tbody wire:key="honor-ptk-grup-{{ $sekolah->id }}" x-data="{ terbuka: false }" class="divide-y divide-slate-100 border-b-2 border-slate-200">
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
                                        <td colspan="7" class="px-2 py-2 whitespace-nowrap text-slate-400 italic">
                                            {{ $jumlahBaris }} data PTK sudah diinput - klik untuk <span x-text="terbuka ? 'menutup' : 'melihat'"></span>
                                        </td>
                                        <td class="px-2 py-2"></td>
                                    </tr>

                                    @foreach ($sekolah->penerimaanHonorPtk as $row)
                                        @php $nomorBaris++; $revisi = $revisiBaris[$row->id] ?? 0; @endphp
                                        <tr wire:key="honor-ptk-baris-{{ $row->id }}" x-show="terbuka" x-cloak>
                                            <td class="px-2 py-2">
                                                <input type="checkbox" wire:model="dipilih" value="{{ $row->id }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-slate-400 text-right">{{ $nomorSekolah }}.{{ $nomorBaris }}</td>
                                            <td class="px-2 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->npsn ?? '-' }}</td>
                                            <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800">{{ $sekolah->nama_sekolah ?? '-' }}</td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <input type="text" inputmode="numeric" maxlength="16" wire:key="honor-ptk-input-{{ $row->id }}-nuptk" wire:model.blur="baris.{{ $row->id }}.nuptk" class="w-32 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$row->id.'.nuptk') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                                @error('baris.'.$row->id.'.nuptk')
                                                    <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <input type="text" wire:key="honor-ptk-input-{{ $row->id }}-nama_penerima" wire:model.blur="baris.{{ $row->id }}.nama_penerima" class="w-40 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$row->id.'.nama_penerima') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                                @error('baris.'.$row->id.'.nama_penerima')
                                                    <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <input type="text" inputmode="numeric" wire:key="honor-ptk-input-{{ $row->id }}-volume" wire:model.blur="baris.{{ $row->id }}.volume" class="w-16 text-xs text-right rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$row->id.'.volume') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                                @error('baris.'.$row->id.'.volume')
                                                    <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <input type="text" wire:key="honor-ptk-input-{{ $row->id }}-satuan" wire:model.blur="baris.{{ $row->id }}.satuan" class="w-20 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$row->id.'.satuan') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                                @error('baris.'.$row->id.'.satuan')
                                                    <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <x-honor-ptk-tarif-cell :row-id="$row->id" field="tarif_harga" :value="$baris[$row->id]['tarif_harga'] ?? ''" :revisi="$revisi" />
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-right font-bold text-slate-700">Rp {{ number_format((int) $row->jumlah_honor, 0, ',', '.') }}</td>
                                            <td class="px-1 py-1.5 whitespace-nowrap">
                                                <input type="date" wire:key="honor-ptk-input-{{ $row->id }}-tanggal_bayar" wire:model.blur="baris.{{ $row->id }}.tanggal_bayar" class="text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$row->id.'.tanggal_bayar') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                                @error('baris.'.$row->id.'.tanggal_bayar')
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
                                         sekolah (permintaan user 2026-09-10: "superadmin dan admin BOSP
                                         bisa input langsung pada tabel selain pada form inputan") -
                                         kunci NEGATIF (-ID sekolah), begitu salah satu kotaknya diisi &
                                         di-blur, baris PTK baru langsung dibuat (lihat
                                         Index::updatedBarisBaru()) & placeholder ini otomatis kosong
                                         lagi, siap dipakai untuk PTK berikutnya di sekolah yang sama.
                                         Ikut x-show="terbuka" supaya konsisten disembunyikan/ditampilkan
                                         bersama baris-baris PTK lain di sekolah ini. --}}
                                    @php $idBaru = -$sekolah->id; $revisiBaru = $revisiBaris[$idBaru] ?? 0; @endphp
                                    <tr wire:key="honor-ptk-baru-{{ $sekolah->id }}" class="bg-blue-50/40" x-show="terbuka" x-cloak>
                                        <td class="px-2 py-2"></td>
                                        <td class="px-2 py-2 whitespace-nowrap text-slate-300">&nbsp;</td>
                                        <td class="px-2 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->npsn ?? '-' }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800">{{ $sekolah->nama_sekolah ?? '-' }}</td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <input type="text" inputmode="numeric" maxlength="16" wire:key="honor-ptk-input-baru-{{ $sekolah->id }}-nuptk" wire:model.blur="baris.{{ $idBaru }}.nuptk" placeholder="NUPTK" class="w-32 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$idBaru.'.nuptk') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-dashed border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                            @error('baris.'.$idBaru.'.nuptk')
                                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <input type="text" wire:key="honor-ptk-input-baru-{{ $sekolah->id }}-nama_penerima" wire:model.blur="baris.{{ $idBaru }}.nama_penerima" placeholder="Nama Penerima" class="w-40 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$idBaru.'.nama_penerima') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-dashed border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                            @error('baris.'.$idBaru.'.nama_penerima')
                                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <input type="text" inputmode="numeric" wire:key="honor-ptk-input-baru-{{ $sekolah->id }}-volume" wire:model.blur="baris.{{ $idBaru }}.volume" placeholder="0" class="w-16 text-xs text-right rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$idBaru.'.volume') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-dashed border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                            @error('baris.'.$idBaru.'.volume')
                                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <input type="text" wire:key="honor-ptk-input-baru-{{ $sekolah->id }}-satuan" wire:model.blur="baris.{{ $idBaru }}.satuan" placeholder="Satuan" class="w-20 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$idBaru.'.satuan') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-dashed border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                            @error('baris.'.$idBaru.'.satuan')
                                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <x-honor-ptk-tarif-cell :row-id="$idBaru" field="tarif_harga" :value="$baris[$idBaru]['tarif_harga'] ?? ''" :revisi="$revisiBaru" />
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-right font-bold text-slate-400">Rp 0</td>
                                        <td class="px-1 py-1.5 whitespace-nowrap">
                                            <input type="date" wire:key="honor-ptk-input-baru-{{ $sekolah->id }}-tanggal_bayar" wire:model.blur="baris.{{ $idBaru }}.tanggal_bayar" class="text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$idBaru.'.tanggal_bayar') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-dashed border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                            @error('baris.'.$idBaru.'.tanggal_bayar')
                                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-right">
                                            {{-- Tombol "+" - sesuai permintaan user 2026-09-10, dipindah ke
                                                 dekat kolom Aksi (bukan lagi di kolom No.) & dijadikan tombol
                                                 SUNGGUHAN (sebelumnya cuma ikon statis tanpa aksi apapun,
                                                 makanya terasa "tidak berfungsi"). Aksinya: fokus ke kotak
                                                 pertama baris ini (murni bantuan navigasi sisi klien lewat
                                                 Alpine, TIDAK ada request ke server) - mempermudah mulai
                                                 mengetik, sesuai jawaban AskUserQuestion "Fokus ke kotak
                                                 pertama baris". --}}
                                            <button type="button" x-on:click="$el.closest('tr').querySelector('input,select')?.focus()" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline">
                                                <x-icon name="plus" class="w-3 h-3" />Baris baru
                                            </button>
                                        </td>
                                    </tr>

                                    {{-- Baris Total Jumlah Honor Yang Diterima PER SEKOLAH -
                                         permintaan user: "tambahkan baris Total Jumlah Honor
                                         Yang Diterima (merge cell dari kolom 1 sampai kolom 8)
                                         pada kolom Jumlah Honor Yang Diterima dari setiap
                                         sekolah". Sengaja ikut x-show="terbuka" + x-cloak (sama
                                         seperti baris detail lain) - jawaban AskUserQuestion:
                                         baris ini HANYA tampil saat grup sekolah dibuka, bukan
                                         di baris ringkasan yang masih tertutup, supaya konsisten
                                         dengan pola +/- yang sudah ada. Kolom 1-8 (No s.d. Tarif
                                         Harga) digabung jadi satu sel label (colspan="8"), nilai
                                         total ditaruh di kolom ke-9 (Jumlah Honor Yang Diterima),
                                         kolom Tanggal Bayar & Aksi dikosongkan. --}}
                                    <tr wire:key="honor-ptk-total-sekolah-{{ $sekolah->id }}" class="bg-slate-100 font-bold text-slate-700 border-t-2 border-slate-300" x-show="terbuka" x-cloak>
                                        <td colspan="9" class="px-2 py-2 text-right">Total Jumlah Honor Yang Diterima</td>
                                        <td class="px-2 py-2 whitespace-nowrap text-right">Rp {{ number_format((int) $totalSekolah, 0, ',', '.') }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tbody>
                            @empty
                                <tbody>
                                    <tr>
                                        <td colspan="12" class="px-3 py-6 text-center text-slate-400">Tidak ada sekolah yang bisa ditampilkan.</td>
                                    </tr>
                                </tbody>
                            @endforelse

                            {{-- Baris Total Jumlah Honor Yang Diterima UNTUK SELURUH SEKOLAH -
                                 permintaan user poin ke-2: "...dan untuk seluruh sekolah".
                                 BEDA dengan baris Total per sekolah di atas (yang ikut
                                 tersembunyi/tampil bersama grupnya) - baris ini SELALU
                                 terlihat di paling bawah tabel, tidak collapsible, sama
                                 seperti pola baris "Jumlah" (total otomatis) pada Rekap RKAS.
                                 Nilainya ($totalHonorKeseluruhan, dihitung di render())
                                 otomatis ikut scope peran & filter yang sedang aktif karena
                                 dijumlahkan dari $daftarSekolah yang sama dengan yang
                                 dirender di atas. Kolom 1-8 digabung (colspan="8") sebagai
                                 label, nilai di kolom ke-9 (Jumlah Honor Yang Diterima). --}}
                            @if ($daftarSekolah->isNotEmpty())
                                <tbody>
                                    <tr class="bg-slate-200 font-bold text-slate-800 border-t-2 border-slate-400">
                                        <td colspan="9" class="px-2 py-2.5 text-right">Total Jumlah Honor Yang Diterima Seluruh Sekolah</td>
                                        <td class="px-2 py-2.5 whitespace-nowrap text-right">Rp {{ number_format((int) $totalHonorKeseluruhan, 0, ',', '.') }}</td>
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
    <x-modal name="penerimaan-honor-ptk-form" :show="$showForm" maxWidth="lg">
        <form wire:submit="simpan" class="p-6">
            <h2 class="text-lg font-medium text-slate-900 mb-1">
                {{ $editingId ? 'Edit Data Penerimaan Honor PTK' : 'Tambah Data Penerimaan Honor PTK' }}
            </h2>
            <p class="text-sm text-slate-500 mb-4">Tahun {{ $tahun }} - {{ $triwulanOptions[$triwulan]['label'] }}</p>

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
                    <x-input-label for="nuptk" value="NUPTK" />
                    <x-text-input wire:model="nuptk" id="nuptk" class="block mt-1 w-full" type="text" inputmode="numeric" maxlength="16" />
                    <x-input-error :messages="$errors->get('nuptk')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="nama_penerima" value="Nama Penerima" />
                    <x-text-input wire:model="nama_penerima" id="nama_penerima" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('nama_penerima')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="volume" value="Volume" />
                    <x-text-input wire:model="volume" id="volume" class="block mt-1 w-full" type="text" inputmode="numeric" />
                    <x-input-error :messages="$errors->get('volume')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="satuan" value="Satuan" />
                    <x-text-input wire:model="satuan" id="satuan" class="block mt-1 w-full" type="text" placeholder="mis. OB, Jam, Kegiatan" />
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
                    <x-input-label value="Jumlah Honor Yang Diterima" />
                    {{-- Dihitung LANGSUNG di sisi klien (Alpine, baca $wire.volume &
                         $wire.tarif_harga yang tetap reaktif walau wire:model-nya
                         tanpa ".live") - permintaan user 2026-09-10 poin 2: kotak
                         ini sebelumnya baru ter-update setelah form benar-benar
                         disimpan (baru ada request ke server), sekarang langsung
                         berubah sambil mengetik di kotak Volume/Tarif Harga,
                         TANPA request tambahan ke server. Rumus akhir tetap
                         dihitung ulang oleh PHP (hitungJumlahHonor()) saat
                         disimpan - ini murni pratinjau. --}}
                    <div class="mt-1 block w-full rounded-md border border-blue-200 bg-blue-50 text-slate-700 text-sm px-3 py-2 text-right" x-text="'Rp ' + hitungJumlah()"></div>
                    <p class="text-xs text-slate-400 mt-1">Otomatis: Volume x Tarif Harga (dihitung ulang saat disimpan).</p>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="tanggal_bayar" value="Tanggal Bayar" />
                    <x-text-input wire:model="tanggal_bayar" id="tanggal_bayar" class="block mt-1 w-full" type="date" />
                    <x-input-error :messages="$errors->get('tanggal_bayar')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="batal" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                <x-primary-button class="!px-3 !py-1.5 !text-[10px]"><x-icon name="check" class="w-3.5 h-3.5 mr-1" />Simpan</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Konfirmasi Hapus --}}
    <x-modal name="penerimaan-honor-ptk-hapus" :show="$confirmingDeleteId !== null" maxWidth="md">
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
         2026-09-26 supaya admin bisa hapus banyak data sekaligus lewat
         checkbox, tanpa harus satu-satu lewat tombol Hapus per baris. --}}
    <x-modal name="penerimaan-honor-ptk-hapus-terpilih" :show="$confirmingHapusTerpilih" maxWidth="md">
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
