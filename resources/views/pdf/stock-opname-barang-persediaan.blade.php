<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 9px; color: #1e293b; }
        .judul-1 { text-align: center; font-weight: bold; font-size: 13px; margin-bottom: 2px; }
        .judul-2 { text-align: center; font-weight: bold; font-size: 11px; margin-bottom: 2px; }
        .nama-sekolah { font-weight: bold; font-size: 10px; margin-top: 10px; margin-bottom: 3px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 4px; table-layout: fixed; }
        table.data th, table.data td { border: 1px solid #94a3b8; padding: 2px 3px; word-wrap: break-word; }
        table.data th { background-color: #f1f5f9; font-weight: bold; text-align: center; }
        table.data td { text-align: left; }
        table.data td.tengah { text-align: center; }
        table.data td.kanan { text-align: right; }
        tr.baris-total-sekolah td { background-color: #e2e8f0; font-weight: bold; }
        .tanpa-data { color: #94a3b8; font-style: italic; padding: 4px 0; }
    </style>
</head>
<body>
    <div class="judul-1">STOCK OPNAME RINCIAN BARANG PERSEDIAAN BOSP - REKAP SELURUH SEKOLAH</div>
    <div class="judul-2">TRIWULAN {{ $triwulan }}, TAHUN ANGGARAN {{ $tahun }}</div>

    @forelse ($daftarSekolah as $sekolah)
        <div class="nama-sekolah">{{ $sekolah->nama_sekolah }} @if ($sekolah->npsn) (NPSN {{ $sekolah->npsn }}) @endif</div>

        @if ($sekolah->stockOpnameBarangPersediaan->isEmpty())
            <div class="tanpa-data">Belum ada data untuk sekolah ini pada triwulan ini.</div>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th style="width: 3%;">No</th>
                        <th style="width: 16%;">Nama Barang Persediaan</th>
                        <th style="width: 7%;">Satuan</th>
                        <th style="width: 8%;">Harga</th>
                        <th style="width: 8%;">Saldo Awal (Kt)</th>
                        <th style="width: 8%;">Saldo Awal (Rp)</th>
                        <th style="width: 8%;">Penerimaan (Kt)</th>
                        <th style="width: 8%;">Penerimaan (Rp)</th>
                        <th style="width: 8%;">Pengeluaran (Kt)</th>
                        <th style="width: 8%;">Pengeluaran (Rp)</th>
                        <th style="width: 8%;">Saldo Akhir (Kt)</th>
                        <th style="width: 10%;">Saldo Akhir (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sekolah->stockOpnameBarangPersediaan as $i => $baris)
                        <tr>
                            <td class="tengah">{{ $i + 1 }}</td>
                            <td>{{ $baris->namaBarangTampil() }}</td>
                            <td>{{ $baris->satuan }}</td>
                            <td class="kanan">Rp {{ number_format((int) $baris->harga, 0, ',', '.') }}</td>
                            <td class="kanan">{{ $baris->saldo_awal_kuantitas }}</td>
                            <td class="kanan">Rp {{ number_format((int) $baris->saldo_awal_jumlah, 0, ',', '.') }}</td>
                            <td class="kanan">{{ $baris->penerimaan_kuantitas }}</td>
                            <td class="kanan">Rp {{ number_format((int) $baris->penerimaan_jumlah, 0, ',', '.') }}</td>
                            <td class="kanan">{{ $baris->pengeluaran_kuantitas }}</td>
                            <td class="kanan">Rp {{ number_format((int) $baris->pengeluaran_jumlah, 0, ',', '.') }}</td>
                            <td class="kanan">{{ $baris->saldo_akhir_kuantitas }}</td>
                            <td class="kanan">Rp {{ number_format((int) $baris->saldo_akhir_jumlah, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="baris-total-sekolah">
                        <td colspan="11" class="tengah">Total Saldo Akhir (Rp) - {{ $sekolah->nama_sekolah }}</td>
                        <td class="kanan">Rp {{ number_format((int) $sekolah->stockOpnameBarangPersediaan->sum('saldo_akhir_jumlah'), 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        @endif
    @empty
        <p>Tidak ada sekolah yang bisa ditampilkan.</p>
    @endforelse
</body>
</html>
