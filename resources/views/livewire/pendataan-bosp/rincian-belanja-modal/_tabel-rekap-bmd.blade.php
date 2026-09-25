{{--
    Tabel sub-tab "Rekap BMD Tahun Anggaran {tahun}" (permintaan user
    2026-09-22, jawaban AskUserQuestion "Hanya tampil & export
    (Recommended)") - BEDA dengan _tabel-bmd.blade.php (per-Triwulan, bisa
    diedit, dikelompokkan per-sekolah dengan tbody buka/tutup):
    - FLAT (tidak dikelompokkan per-sekolah, tidak ada buka/tutup).
    - READ-ONLY sepenuhnya - TIDAK ADA kotak input/select/tombol Edit/
      Hapus, TIDAK ADA baris placeholder siap-isi, TIDAK ADA kolom Aksi.
    - Kolom "Triwulan" ditambahkan sebagai kolom KEDUA (setelah No),
      karena tabel ini mencakup TW-1 s.d. TW-4 tahun berjalan sekaligus.
    - Diberi baris subtotal PER TRIWULAN (begitu Triwulan-nya berganti)
      & SATU baris Jumlah Total Keseluruhan di baris paling akhir.
    Data ($daftarRekapBmd) & urutannya (Triwulan -> Negeri/Swasta -> Nama
    Sekolah (A-Z) -> Tgl. Perolehan) berasal dari
    Index::renderRekapBmd()/queryDasarRekapBmd().
--}}
<div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg" style="max-height: 32rem; zoom: {{ $zoomPercent }}%;">
    <table class="min-w-full divide-y divide-slate-200 text-xs">
        <thead class="sticky top-0 z-10 bg-slate-50">
            <tr class="text-left text-slate-500">
                <th class="px-2 py-2.5">No</th>
                <th class="px-2 py-2.5">Triwulan</th>
                <th class="px-2 py-2.5">NPSN</th>
                <th class="px-2 py-2.5">Lokasi</th>
                <th class="px-2 py-2.5">Subrayon</th>
                <th class="px-2 py-2.5">Bentuk Kontrak / Transaksi</th>
                <th class="px-2 py-2.5">Program</th>
                <th class="px-2 py-2.5">Kegiatan</th>
                <th class="px-2 py-2.5">Kode Sub Kegiatan</th>
                <th class="px-2 py-2.5">Nama Sub Kegiatan</th>
                <th class="px-2 py-2.5">Atribusi</th>
                <th class="px-2 py-2.5">Jumlah Termin</th>
                <th class="px-2 py-2.5">PPK</th>
                <th class="px-2 py-2.5">Nomor Dokumen</th>
                <th class="px-2 py-2.5">Tgl. Perolehan</th>
                <th class="px-2 py-2.5">Penyedia</th>
                <th class="px-2 py-2.5">Kode Belanja</th>
                <th class="px-2 py-2.5">Rekening Belanja</th>
                <th class="px-2 py-2.5">Jenis Aset (KIB)</th>
                <th class="px-2 py-2.5">Sub Sub Rincian Objek</th>
                <th class="px-2 py-2.5">Jumlah</th>
                <th class="px-2 py-2.5">Satuan</th>
                <th class="px-2 py-2.5">Harga Satuan</th>
                <th class="px-2 py-2.5">Total</th>
                <th class="px-2 py-2.5">No BAST</th>
                <th class="px-2 py-2.5">Tgl BAST</th>
                <th class="px-2 py-2.5">Keterangan</th>
                <th class="px-2 py-2.5">Nomor Surat Pernyataan</th>
                <th class="px-2 py-2.5">Tgl. Surat Pernyataan</th>
                <th class="px-2 py-2.5">Nama Pengurus Barang</th>
                <th class="px-2 py-2.5">Jabatan</th>
                <th class="px-2 py-2.5">Pejabat Penata Usaha</th>
                <th class="px-2 py-2.5">Nama Barang</th>
                <th class="px-2 py-2.5">Spesifikasi Nama Barang</th>
                <th class="px-2 py-2.5">Spesifikasi Lain (Serial Nomor)</th>
                <th class="px-2 py-2.5">Merk/Pengarang</th>
                <th class="px-2 py-2.5">Keterangan</th>
            </tr>
        </thead>
        @forelse ($daftarRekapBmd as $row)
            @php
                $nomorRekap = ($nomorRekap ?? 0) + 1;
                $triwulanSebelumnya = $triwulanSebelumnya ?? null;
                $subtotalTriwulan = ($triwulanSebelumnya === $row->triwulan) ? ($subtotalTriwulan ?? 0) + (int) $row->total : (int) $row->total;
            @endphp

            @if ($triwulanSebelumnya !== null && $triwulanSebelumnya !== $row->triwulan)
                <tbody wire:key="rekap-bmd-subtotal-{{ $triwulanSebelumnya }}">
                    <tr class="bg-slate-100 font-bold text-slate-700 border-t-2 border-slate-300">
                        <td colspan="23" class="px-2 py-2 text-right">
                            Jumlah BMD {{ $triwulanOptions[$triwulanSebelumnya] ?? 'Triwulan '.$triwulanSebelumnya }}
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap text-right">Rp {{ number_format((int) ($subtotalTriwulanSelesai ?? 0), 0, ',', '.') }}</td>
                        <td colspan="13"></td>
                    </tr>
                </tbody>
            @endif

            @php
                $subtotalTriwulanSelesai = $subtotalTriwulan;
                $triwulanSebelumnya = $row->triwulan;
            @endphp

            <tbody wire:key="rekap-bmd-baris-{{ $row->id }}">
                <tr class="{{ $nomorRekap % 2 === 0 ? 'bg-white' : 'bg-slate-50/40' }}">
                    <td class="px-2 py-2 whitespace-nowrap text-slate-400 text-right">{{ $nomorRekap }}</td>
                    <td class="px-2 py-2 whitespace-nowrap font-semibold text-slate-600">{{ $triwulanOptions[$row->triwulan] ?? 'Triwulan '.$row->triwulan }}</td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-600 bg-yellow-50">{{ $row->profilSekolah->npsn ?? '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800 bg-yellow-50">{{ $row->profilSekolah->nama_sekolah ?? '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-600 bg-yellow-50">{{ $row->profilSekolah->subrayon ?? '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->bentuk_kontrak ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-500 bg-yellow-50">{{ $programBmd }}</td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-500 bg-yellow-50">{{ $kegiatanBmd }}</td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-500 bg-yellow-50">{{ $kodeSubKegiatanBmd }}</td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-500 bg-yellow-50">{{ $namaSubKegiatanBmd }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->atribusi ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->jumlah_termin ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-600 bg-yellow-50">{{ $row->ppk ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->nomor_dokumen ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->tanggal_perolehan?->format('d-m-Y') ?? '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->penyedia ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->kode_belanja ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->rekening_belanja ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-500 bg-yellow-50">{{ $row->jenis_aset ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->sub_sub_rincian_objek ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap text-right">{{ $row->jumlah ?? '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->satuan ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap text-right">Rp {{ number_format((int) $row->harga_satuan, 0, ',', '.') }}</td>
                    <td class="px-2 py-2 whitespace-nowrap text-right font-bold text-slate-700 bg-yellow-50">Rp {{ number_format((int) $row->total, 0, ',', '.') }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->no_bast ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->tanggal_bast?->format('d-m-Y') ?? '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-500 bg-yellow-50">{{ $row->keterangan_bos }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->nomor_surat_pernyataan ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->tanggal_surat_pernyataan?->format('d-m-Y') ?? '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->nama_pengurus_barang ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->jabatan ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->pejabat_penata_usaha ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap font-medium">{{ $row->nama_barang }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->spesifikasi_nama_barang ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->spesifikasi_lain ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap">{{ $row->merk_pengarang ?: '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-500 bg-yellow-50">{{ $row->keterangan_bosp }}</td>
                </tr>
            </tbody>

            @if ($loop->last)
                <tbody wire:key="rekap-bmd-subtotal-{{ $row->triwulan }}">
                    <tr class="bg-slate-100 font-bold text-slate-700 border-t-2 border-slate-300">
                        <td colspan="23" class="px-2 py-2 text-right">
                            Jumlah BMD {{ $triwulanOptions[$row->triwulan] ?? 'Triwulan '.$row->triwulan }}
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap text-right">Rp {{ number_format((int) $subtotalTriwulan, 0, ',', '.') }}</td>
                        <td colspan="13"></td>
                    </tr>
                </tbody>

                <tbody>
                    <tr class="bg-slate-200 font-bold text-slate-800 border-t-2 border-slate-400">
                        <td colspan="23" class="px-2 py-2.5 text-right">
                            Jumlah BMD Keseluruhan Tahun Anggaran {{ $tahun }}
                        </td>
                        <td class="px-2 py-2.5 whitespace-nowrap text-right">Rp {{ number_format((int) $daftarRekapBmd->sum('total'), 0, ',', '.') }}</td>
                        <td colspan="13"></td>
                    </tr>
                </tbody>
            @endif
        @empty
            <tbody>
                <tr>
                    <td colspan="37" class="px-3 py-6 text-center text-slate-400">Tidak ada data BMD pada tahun anggaran ini.</td>
                </tr>
            </tbody>
        @endforelse
    </table>
</div>
