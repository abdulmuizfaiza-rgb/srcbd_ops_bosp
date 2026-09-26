<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* dompdf: CSS sederhana, pola sama seperti pdf/pajak-bosp-reguler.blade.php. */
        body { font-family: sans-serif; font-size: 9px; color: #1e293b; }
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
    <div class="judul-1">DAFTAR PENERIMAAN HONOR PENDIDIK DAN TENAGA KEPENDIDIKAN - REKAP SELURUH SEKOLAH</div>
    <div class="judul-2">TRIWULAN {{ $triwulan }}, TAHUN ANGGARAN {{ $tahun }}</div>

    @forelse ($daftarSekolah as $sekolah)
        <div class="nama-sekolah">{{ $sekolah->nama_sekolah }} @if ($sekolah->npsn) (NPSN {{ $sekolah->npsn }}) @endif</div>

        @if ($sekolah->penerimaanHonorPtk->isEmpty())
            <div class="tanpa-data">Belum ada data Penerimaan Honor PTK untuk sekolah ini pada triwulan ini.</div>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th style="width: 3%;">No</th>
                        <th style="width: 14%;">NUPTK</th>
                        <th style="width: 20%;">Nama Penerima</th>
                        <th style="width: 8%;">Volume</th>
                        <th style="width: 10%;">Satuan</th>
                        <th style="width: 13%;">Tarif Harga</th>
                        <th style="width: 15%;">Jumlah Honor Yang Diterima</th>
                        <th style="width: 12%;">Tanggal Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sekolah->penerimaanHonorPtk as $i => $baris)
                        <tr>
                            <td class="tengah">{{ $i + 1 }}</td>
                            <td>{{ $baris->nuptk }}</td>
                            <td>{{ $baris->nama_penerima }}</td>
                            <td class="kanan">{{ $baris->volume }}</td>
                            <td>{{ $baris->satuan }}</td>
                            <td class="kanan">Rp {{ number_format((int) $baris->tarif_harga, 0, ',', '.') }}</td>
                            <td class="kanan">Rp {{ number_format((int) $baris->jumlah_honor, 0, ',', '.') }}</td>
                            <td class="tengah">{{ $baris->tanggal_bayar?->format('d-m-Y') }}</td>
                        </tr>
                    @endforeach
                    <tr class="baris-total-sekolah">
                        <td colspan="6" class="tengah">Total Jumlah Honor Yang Diterima - {{ $sekolah->nama_sekolah }}</td>
                        <td class="kanan">Rp {{ number_format((int) $sekolah->penerimaanHonorPtk->sum('jumlah_honor'), 0, ',', '.') }}</td>
                        <td></td>
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
                    <td class="tengah">Total Jumlah Honor Yang Diterima Seluruh Sekolah</td>
                    <td class="kanan">Rp {{ number_format((int) $totalKeseluruhan, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    @endif
</body>
</html>
