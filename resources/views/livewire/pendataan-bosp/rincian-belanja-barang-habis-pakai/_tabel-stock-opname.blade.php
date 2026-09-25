{{--
    Tabel tab "Stock Opname" (permintaan user 2026-09-18, rumus & baris
    otomatis dari RBBHP ditambahkan 2026-09-19) - kolom sesuai gambar
    contoh "STOCK OPNAME RINCIAN BARANG PERSEDIAAN BOSP TAHUN ANGGARAN"
    yang diupload user: No, NPSN, Nama Sekolah, Subrayon (3 kolom
    otomatis dari Profil Sekolah), Nama Barang Persediaan, Satuan
    (Unit/Harga), Saldo Awal (Kuantitas/Jumlah Rp), Penerimaan
    (Kuantitas/Jumlah Rp), Pengeluaran (Kuantitas/Jumlah Rp), Saldo
    Akhir (Kuantitas/Jumlah Rp), Keterangan.

    SEJAK permintaan user 2026-09-19 (jawaban AskUserQuestion "Baris
    otomatis mengikuti RBBHP"): baris SELALU mengikuti Rincian Belanja
    Barang Habis Pakai triwulan yang sama (TIDAK ADA LAGI baris
    placeholder/tombol Tambah/Edit/Hapus di tab ini - lihat catatan
    kelas Index). Nama Barang Persediaan (StockOpnameBarangPersediaan::
    namaBarangTampil()), keempat kolom Jumlah (Rp), Kuantitas Saldo
    Akhir, & Keterangan SEKARANG READ-ONLY (bg-yellow-50, pola sama
    seperti kolom komputasi LaporanRealisasiBosp) - HANYA Satuan, Harga,
    & ketiga Kuantitas (Saldo Awal/Penerimaan/Pengeluaran) yang MASIH
    kotak input biru (manual).

    Baris "JUMLAH STOCK OPNAME" di paling bawah (permintaan user
    2026-09-19 poin 1) - kolom 1-7 di-merge jadi 1 sel label, kolom 8-15
    berisi total (lihat Index::render(), variabel $totalOpname).
--}}
<div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg{{ $terkunciTriwulanIni ? ' pointer-events-none opacity-60 select-none' : '' }}" style="max-height: 32rem; zoom: {{ $zoomPercent }}%;">
    <table class="min-w-full divide-y divide-slate-200 text-xs">
        <thead class="sticky top-0 z-10 bg-slate-50">
            <tr class="text-center text-slate-500">
                <th rowspan="2" class="px-2 py-2.5 align-middle">No</th>
                <th rowspan="2" class="px-2 py-2.5 align-middle">NPSN</th>
                <th rowspan="2" class="px-2 py-2.5 align-middle">Nama Sekolah</th>
                <th rowspan="2" class="px-2 py-2.5 align-middle">Subrayon</th>
                <th rowspan="2" class="px-2 py-2.5 align-middle">Nama Barang Persediaan</th>
                <th colspan="2" class="px-2 py-2.5">Satuan</th>
                <th colspan="2" class="px-2 py-2.5">Saldo Awal</th>
                <th colspan="2" class="px-2 py-2.5">Penerimaan</th>
                <th colspan="2" class="px-2 py-2.5">Pengeluaran</th>
                <th colspan="2" class="px-2 py-2.5">Saldo Akhir</th>
                <th rowspan="2" class="px-2 py-2.5 align-middle">Keterangan</th>
            </tr>
            <tr class="text-center text-slate-500">
                <th class="px-2 py-2 whitespace-nowrap">Unit</th>
                <th class="px-2 py-2 whitespace-nowrap">Harga</th>
                <th class="px-2 py-2 whitespace-nowrap">Kuantitas</th>
                <th class="px-2 py-2 whitespace-nowrap">Jumlah (Rp)</th>
                <th class="px-2 py-2 whitespace-nowrap">Kuantitas</th>
                <th class="px-2 py-2 whitespace-nowrap">Jumlah (Rp)</th>
                <th class="px-2 py-2 whitespace-nowrap">Kuantitas</th>
                <th class="px-2 py-2 whitespace-nowrap">Jumlah (Rp)</th>
                <th class="px-2 py-2 whitespace-nowrap">Kuantitas</th>
                <th class="px-2 py-2 whitespace-nowrap">Jumlah (Rp)</th>
            </tr>
        </thead>
        @php $nomorSekolahOpname = 0; @endphp
        @forelse ($daftarSekolah as $sekolah)
            @php
                $nomorSekolahOpname++;
                $jumlahBarisOpname = $sekolah->stockOpnameBarangPersediaan->count();
            @endphp
            <tbody wire:key="stock-opname-grup-{{ $sekolah->id }}" x-data="{ terbuka: false }" class="divide-y divide-slate-100 border-b-2 border-slate-200">
                <tr class="bg-slate-50/70 hover:bg-slate-100 cursor-pointer select-none" x-on:click="terbuka = ! terbuka">
                    <td class="px-2 py-2 whitespace-nowrap text-slate-600 font-semibold">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded border border-slate-300 bg-white text-slate-500 shrink-0">
                                <x-icon name="plus" class="w-2.5 h-2.5" x-show="! terbuka" x-cloak />
                                <x-icon name="minus" class="w-2.5 h-2.5" x-show="terbuka" x-cloak />
                            </span>
                            {{ $nomorSekolahOpname }}
                        </span>
                    </td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->npsn ?? '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800">{{ $sekolah->nama_sekolah ?? '-' }}</td>
                    <td colspan="12" class="px-2 py-2 whitespace-nowrap text-slate-400 italic">
                        {{ $jumlahBarisOpname }} data sudah diinput - klik untuk <span x-text="terbuka ? 'menutup' : 'melihat'"></span>
                    </td>
                </tr>

                @php $nomorBarisOpname = 0; @endphp
                @foreach ($sekolah->stockOpnameBarangPersediaan as $row)
                    @php $nomorBarisOpname++; $revisi = $revisiBarisOpname[$row->id] ?? 0; @endphp
                    <tr wire:key="stock-opname-baris-{{ $row->id }}" x-show="terbuka" x-cloak>
                        <td class="px-2 py-2 whitespace-nowrap text-slate-400 text-right">{{ $nomorSekolahOpname }}.{{ $nomorBarisOpname }}</td>
                        <td class="px-2 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->npsn ?? '-' }}</td>
                        <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800">{{ $sekolah->nama_sekolah ?? '-' }}</td>
                        <td class="px-2 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->subrayon ?? '-' }}</td>
                        {{-- Nama Barang Persediaan - READ-ONLY sejak 2026-09-19 (diambil
                             dari Rincian Belanja Barang Habis Pakai triwulan yang sama,
                             lihat StockOpnameBarangPersediaan::namaBarangTampil()). --}}
                        <td class="px-2 py-2 whitespace-nowrap text-slate-700 bg-yellow-50">{{ $barisOpname[$row->id]['nama_barang'] ?? '-' }}</td>
                        <td class="px-1 py-1.5 whitespace-nowrap">
                            <input type="text" wire:model.blur="barisOpname.{{ $row->id }}.satuan" class="w-16 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('barisOpname.'.$row->id.'.satuan') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                            @error('barisOpname.'.$row->id.'.satuan')
                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                            @enderror
                        </td>
                        <td class="px-1 py-1.5 whitespace-nowrap">
                            <x-stock-opname-tarif-cell :row-id="$row->id" field="harga" :value="$barisOpname[$row->id]['harga'] ?? ''" :revisi="$revisi" />
                        </td>
                        @foreach (['saldo_awal', 'penerimaan', 'pengeluaran', 'saldo_akhir'] as $grup)
                            <td class="px-1 py-1.5 whitespace-nowrap">
                                @if ($grup === 'saldo_akhir')
                                    {{-- Kuantitas Saldo Akhir - READ-ONLY sejak 2026-09-19 (hasil
                                         rumus, lihat StockOpnameBarangPersediaan::hitungSaldoAkhirKuantitas()). --}}
                                    <div class="w-16 text-right text-xs rounded px-1.5 py-1.5 bg-yellow-50 text-slate-700 font-semibold">
                                        {{ number_format((int) ($barisOpname[$row->id]['saldo_akhir_kuantitas'] ?? 0), 0, ',', '.') }}
                                    </div>
                                @else
                                    <input type="text" inputmode="numeric" wire:model.blur="barisOpname.{{ $row->id }}.{{ $grup }}_kuantitas" class="w-16 text-xs text-right rounded px-1.5 py-1.5 border {{ $errors->has('barisOpname.'.$row->id.'.'.$grup.'_kuantitas') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none">
                                    @error('barisOpname.'.$row->id.'.'.$grup.'_kuantitas')
                                        <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                    @enderror
                                @endif
                            </td>
                            {{-- Jumlah (Rp) SETIAP grup - READ-ONLY sejak 2026-09-19 (hasil
                                 rumus Harga x Kuantitas, atau Saldo Awal + Penerimaan -
                                 Pengeluaran utk grup Saldo Akhir - lihat
                                 StockOpnameBarangPersediaan::hitungJumlah()/hitungSaldoAkhirJumlah()). --}}
                            <td class="px-2 py-2 whitespace-nowrap text-right font-semibold text-slate-700 bg-yellow-50">
                                Rp {{ number_format((int) ($barisOpname[$row->id][$grup.'_jumlah'] ?? 0), 0, ',', '.') }}
                            </td>
                        @endforeach
                        {{-- Keterangan - READ-ONLY sejak 2026-09-19 ("BOSP Triwulan {tw}
                             Tahun {tahun}" otomatis, lihat
                             StockOpnameBarangPersediaan::keteranganOtomatis()). --}}
                        <td class="px-2 py-2 whitespace-nowrap text-slate-700 bg-yellow-50">{{ $barisOpname[$row->id]['keterangan'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        @empty
            <tbody>
                <tr>
                    <td colspan="16" class="px-3 py-6 text-center text-slate-400">Tidak ada sekolah yang bisa ditampilkan.</td>
                </tr>
            </tbody>
        @endforelse

        @if ($daftarSekolah->isNotEmpty())
            {{-- Baris "JUMLAH STOCK OPNAME" - SELALU tampil di paling bawah
                 (BUKAN collapsible seperti baris per sekolah di atas), kolom
                 1-7 (No s.d. Harga) di-merge jadi 1 sel label (permintaan
                 user 2026-09-19 poin 1). --}}
            <tbody class="divide-y divide-slate-100 border-t-4 border-slate-300">
                <tr class="bg-slate-200 font-bold">
                    <td colspan="7" class="px-2 py-2 whitespace-nowrap text-center text-slate-900">JUMLAH STOCK OPNAME</td>
                    @foreach (['saldo_awal', 'penerimaan', 'pengeluaran', 'saldo_akhir'] as $grup)
                        <td class="px-2 py-2 whitespace-nowrap text-right text-slate-800">{{ number_format($totalOpname[$grup.'_kuantitas'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-2 py-2 whitespace-nowrap text-right text-slate-800">Rp {{ number_format($totalOpname[$grup.'_jumlah'] ?? 0, 0, ',', '.') }}</td>
                    @endforeach
                    <td class="px-2 py-2"></td>
                </tr>
            </tbody>
        @endif
    </table>
</div>
