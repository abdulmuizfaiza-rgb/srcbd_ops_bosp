<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* dompdf: CSS sederhana, pola sama seperti pdf/penerimaan-honor-ptk.blade.php. */
        body { font-family: sans-serif; font-size: 8px; color: #1e293b; }
        .judul-1 { text-align: center; font-weight: bold; font-size: 13px; margin-bottom: 2px; }
        .judul-2 { text-align: center; font-weight: bold; font-size: 11px; margin-bottom: 2px; }
        .nama-sekolah { font-weight: bold; font-size: 10px; margin-top: 10px; margin-bottom: 3px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 4px; table-layout: fixed; }
        table.data th, table.data td { border: 1px solid #94a3b8; padding: 3px 4px; word-wrap: break-word; }
        table.data th { background-color: #f1f5f9; font-weight: bold; text-align: center; }
        table.data td { text-align: left; }
        table.data td.tengah { text-align: center; }
        table.data td.kanan { text-align: right; }
        tr.baris-total-sekolah td { background-color: #e2e8f0; font-weight: bold; }
        tr.baris-total-keseluruhan td { background-color: #cbd5e1; font-weight: bold; }
        .tanpa-data { color: #94a3b8; font-style: italic; padding: 4px 0; }
    </style>
</head>
<body>
    <div class="judul-1">{{ strtoupper('DAFTAR '.App\Models\RincianPemeliharaanPc::JENIS_OPTIONS[$jenis]) }} - REKAP SELURUH SEKOLAH</div>
    <div class="judul-2">TRIWULAN {{ $triwulan }}, TAHUN ANGGARAN {{ $tahun }}</div>

    @forelse ($daftarSekolah as $sekolah)
        <div class="nama-sekolah">{{ $sekolah->nama_sekolah }} @if ($sekolah->npsn) (NPSN {{ $sekolah->npsn }}) @endif</div>

        @if ($sekolah->rincianPemeliharaanPc->isEmpty())
            <div class="tanpa-data">Belum ada data untuk sekolah ini pada triwulan ini.</div>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th style="width: 3%;">No</th>
                        <th style="width: 8%;">Kode UPB</th>
                        <th style="width: 16%;">Nama Barang</th>
                        <th style="width: 12%;">Nama Merk Barang</th>
                        <th style="width: 6%;">Volume</th>
                        <th style="width: 8%;">Satuan</th>
                        <th style="width: 10%;">Harga Satuan</th>
                        <th style="width: 10%;">Total Harga</th>
                        <th style="width: 10%;">Asal Usul</th>
                        <th style="width: 8%;">Tanggal</th>
                        <th style="width: 9%;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sekolah->rincianPemeliharaanPc as $i => $baris)
                        <tr>
                            <td class="tengah">{{ $i + 1 }}</td>
                            <td>{{ $baris->kode_upb }}</td>
                            <td>{{ $baris->nama_barang }}</td>
                            <td>{{ $baris->nama_merk_barang }}</td>
                            <td class="kanan">{{ $baris->volume }}</td>
                            <td>{{ $baris->satuan }}</td>
                            <td class="kanan">Rp {{ number_format((int) $baris->harga_satuan, 0, ',', '.') }}</td>
                            <td class="kanan">Rp {{ number_format((int) $baris->total_harga, 0, ',', '.') }}</td>
                            <td>{{ $baris->asal_usul }}</td>
                            <td class="tengah">{{ $baris->tanggal?->format('d-m-Y') }}</td>
                            <td>{{ $baris->keterangan }}</td>
                        </tr>
                    @endforeach
                    <tr class="baris-total-sekolah">
                        <td colspan="7" class="tengah">Total Harga - {{ $sekolah->nama_sekolah }}</td>
                        <td class="kanan">Rp {{ number_format((int) $sekolah->rincianPemeliharaanPc->sum('total_harga'), 0, ',', '.') }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tbody>
            </table>
        @endif
    @empty
        <p>Tidak ada sekolah yang bisa ditampilkan.</p>
    @endforelse

    @if ($daftarSekolah->isNotEmpty())
        <table class="data" style="margin-top: 10px;">
            <tbody>
                <tr class="baris-total-keseluruhan">
                    <td class="tengah">Total Harga Seluruh Sekolah</td>
                    <td class="kanan">Rp {{ number_format((int) $totalKeseluruhan, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    @endif
</body>
</html>
