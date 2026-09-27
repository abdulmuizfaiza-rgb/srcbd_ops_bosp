<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* 35 kolom data - font & padding dibuat sangat rapat supaya masih
           bisa tercetak dalam 1 halaman lebar A4 landscape. Untuk
           kenyamanan membaca/mengedit, gunakan tombol "Unduh Excel 4
           Triwulan" (auto-lebar per kolom). */
        body { font-family: sans-serif; font-size: 5.5px; color: #1e293b; }
        .judul-1 { text-align: center; font-weight: bold; font-size: 11px; margin-bottom: 2px; }
        .judul-2 { text-align: center; font-weight: bold; font-size: 9px; margin-bottom: 2px; }
        .nama-sekolah { font-weight: bold; font-size: 7px; margin-top: 6px; margin-bottom: 2px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 2px; table-layout: fixed; }
        table.data th, table.data td { border: 1px solid #94a3b8; padding: 1px 2px; word-wrap: break-word; overflow: hidden; }
        table.data th { background-color: #f1f5f9; font-weight: bold; text-align: center; }
        table.data td { text-align: left; }
        table.data td.tengah { text-align: center; }
        table.data td.kanan { text-align: right; }
        .tanpa-data { color: #94a3b8; font-style: italic; padding: 3px 0; font-size: 8px; }
    </style>
</head>
<body>
    <div class="judul-1">DAFTAR BELANJA MODAL (BMD) - REKAP SELURUH SEKOLAH</div>
    <div class="judul-2">TRIWULAN {{ $triwulan }}, TAHUN ANGGARAN {{ $tahun }}</div>

    @forelse ($daftarSekolah as $sekolah)
        <div class="nama-sekolah">{{ $sekolah->nama_sekolah }} @if ($sekolah->npsn) (NPSN {{ $sekolah->npsn }}) @endif</div>

        @if ($sekolah->rincianBelanjaModalBmd->isEmpty())
            <div class="tanpa-data">Belum ada data BMD untuk sekolah ini pada triwulan ini.</div>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Bentuk Kontrak</th>
                        <th>Program</th>
                        <th>Kegiatan</th>
                        <th>Kode Sub Keg.</th>
                        <th>Nama Sub Keg.</th>
                        <th>Atribusi</th>
                        <th>Jml Termin</th>
                        <th>PPK</th>
                        <th>No. Dokumen</th>
                        <th>Tgl Perolehan</th>
                        <th>Penyedia</th>
                        <th>Kode Belanja</th>
                        <th>Rekening Belanja</th>
                        <th>Jenis Aset</th>
                        <th>Sub Sub Objek</th>
                        <th>Jumlah</th>
                        <th>Satuan</th>
                        <th>Harga Satuan</th>
                        <th>Total</th>
                        <th>No BAST</th>
                        <th>Tgl BAST</th>
                        <th>Ket. (BOS)</th>
                        <th>No. Surat Pernyataan</th>
                        <th>Tgl Surat Pernyataan</th>
                        <th>Nama Pengurus Brg</th>
                        <th>Jabatan</th>
                        <th>Pejabat Penatausahaan</th>
                        <th>Nama Barang</th>
                        <th>Spesifikasi Nama Brg</th>
                        <th>Spek. Lain (SN)</th>
                        <th>Merk/Pengarang</th>
                        <th>Ket. (BOSP)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sekolah->rincianBelanjaModalBmd as $i => $baris)
                        <tr>
                            <td class="tengah">{{ $i + 1 }}</td>
                            <td>{{ $baris->bentuk_kontrak }}</td>
                            <td>{{ \App\Models\RincianBelanjaModalBmd::PROGRAM }}</td>
                            <td>{{ \App\Models\RincianBelanjaModalBmd::KEGIATAN }}</td>
                            <td>{{ \App\Models\RincianBelanjaModalBmd::KODE_SUB_KEGIATAN }}</td>
                            <td>{{ \App\Models\RincianBelanjaModalBmd::NAMA_SUB_KEGIATAN }}</td>
                            <td>{{ $baris->atribusi }}</td>
                            <td class="tengah">{{ $baris->jumlah_termin }}</td>
                            <td>{{ $baris->ppk }}</td>
                            <td>{{ $baris->nomor_dokumen }}</td>
                            <td class="tengah">{{ $baris->tanggal_perolehan?->format('d-m-Y') }}</td>
                            <td>{{ $baris->penyedia }}</td>
                            <td>{{ $baris->kode_belanja }}</td>
                            <td>{{ $baris->rekening_belanja }}</td>
                            <td>{{ $baris->jenis_aset }}</td>
                            <td>{{ $baris->sub_sub_rincian_objek }}</td>
                            <td class="kanan">{{ $baris->jumlah }}</td>
                            <td>{{ $baris->satuan }}</td>
                            <td class="kanan">{{ number_format((int) $baris->harga_satuan, 0, ',', '.') }}</td>
                            <td class="kanan">{{ number_format((int) $baris->total, 0, ',', '.') }}</td>
                            <td>{{ $baris->no_bast }}</td>
                            <td class="tengah">{{ $baris->tanggal_bast?->format('d-m-Y') }}</td>
                            <td>{{ $baris->keterangan_bos }}</td>
                            <td>{{ $baris->nomor_surat_pernyataan }}</td>
                            <td class="tengah">{{ $baris->tanggal_surat_pernyataan?->format('d-m-Y') }}</td>
                            <td>{{ $baris->nama_pengurus_barang }}</td>
                            <td>{{ $baris->jabatan }}</td>
                            <td>{{ $baris->pejabat_penata_usaha }}</td>
                            <td>{{ $baris->nama_barang }}</td>
                            <td>{{ $baris->spesifikasi_nama_barang }}</td>
                            <td>{{ $baris->spesifikasi_lain }}</td>
                            <td>{{ $baris->merk_pengarang }}</td>
                            <td>{{ $baris->keterangan_bosp }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @empty
        <p>Tidak ada sekolah yang bisa ditampilkan.</p>
    @endforelse
</body>
</html>
