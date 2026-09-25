<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* dompdf: CSS sederhana, pola sama seperti pdf/unduhan-lampiran.blade.php. */
        body { font-family: sans-serif; font-size: 9px; color: #1e293b; }
        .judul-1 { text-align: center; font-weight: bold; font-size: 13px; margin-bottom: 2px; }
        .judul-2, .judul-3 { text-align: center; font-weight: bold; font-size: 11px; margin-bottom: 2px; }
        .sub-judul { font-weight: bold; font-size: 10px; margin-top: 14px; margin-bottom: 4px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; table-layout: fixed; }
        table.data th, table.data td { border: 1px solid #94a3b8; padding: 3px 4px; word-wrap: break-word; }
        table.data th { background-color: #f1f5f9; font-weight: bold; text-align: center; }
        table.data td { text-align: left; }
        table.data td.tengah { text-align: center; }
        table.data td.kanan { text-align: right; }
        tr.baris-jumlah td { background-color: #e2e8f0; font-weight: bold; }
        tr.baris-spasi-ttd td { border: none; height: 18px; padding: 0; }
        tr.baris-ttd td { border: none; padding: 2px 5px; vertical-align: top; }
        tr.baris-ttd td.spasi-nama { height: 26px; }
    </style>
</head>
<body>
    <div class="judul-1">REKAPITULASI PAJAK REGULER DAN PAJAK DAERAH</div>
    <div class="judul-2">DANA BANTUAN OPERASIONAL SEKOLAH (BOS)</div>
    <div class="judul-3">{{ $sekolah->nama_sekolah }} - PERIODE JANUARI-DESEMBER TAHUN ANGGARAN {{ $tahun }}</div>

    <table class="data">
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Bulan</th>
                <th colspan="6">PENERIMAAN / DEBIT</th>
                <th colspan="6">PENGELUARAN / KREDIT</th>
                <th rowspan="2">Saldo</th>
            </tr>
            <tr>
                @foreach ($labelPajak as $label)
                    <th>{{ $label }}</th>
                @endforeach
                <th>Jumlah</th>
                @foreach ($labelPajak as $label)
                    <th>{{ $label }}</th>
                @endforeach
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bulanOptions as $bulan => $labelBulan)
                <tr>
                    <td class="tengah">{{ $bulan }}</td>
                    <td>{{ $labelBulan }}</td>
                    @foreach ($fieldDebit as $field)
                        <td class="kanan">{{ number_format((int) ($baris[$bulan][$field] ?? 0), 0, ',', '.') }}</td>
                    @endforeach
                    <td class="kanan">{{ number_format($jumlahPerBulan[$bulan]['debit'], 0, ',', '.') }}</td>
                    @foreach ($fieldKredit as $field)
                        <td class="kanan">{{ number_format((int) ($baris[$bulan][$field] ?? 0), 0, ',', '.') }}</td>
                    @endforeach
                    <td class="kanan">{{ number_format($jumlahPerBulan[$bulan]['kredit'], 0, ',', '.') }}</td>
                    <td class="kanan">{{ number_format($saldoPerBulan[$bulan], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="baris-jumlah">
                <td colspan="2" class="tengah">Jumlah</td>
                @foreach ($fieldDebit as $field)
                    <td class="kanan">{{ number_format($totalRaw[$field], 0, ',', '.') }}</td>
                @endforeach
                <td class="kanan">{{ number_format($totalJumlahDebit, 0, ',', '.') }}</td>
                @foreach ($fieldKredit as $field)
                    <td class="kanan">{{ number_format($totalRaw[$field], 0, ',', '.') }}</td>
                @endforeach
                <td class="kanan">{{ number_format($totalJumlahKredit, 0, ',', '.') }}</td>
                <td class="kanan">{{ number_format($saldoAkhir, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="sub-judul">REKAPITULASI PER TRIWULAN</div>
    <table class="data">
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Triwulan</th>
                <th colspan="6">Penerimaan (Debit)</th>
                <th colspan="6">Pengeluaran (Kredit)</th>
                <th rowspan="2">Saldo</th>
            </tr>
            <tr>
                @foreach ($labelPajak as $label)
                    <th>{{ $label }}</th>
                @endforeach
                <th>Jumlah</th>
                @foreach ($labelPajak as $label)
                    <th>{{ $label }}</th>
                @endforeach
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($triwulanOptions as $tw => $labelTw)
                <tr>
                    <td class="tengah">{{ $tw }}</td>
                    <td>{{ $labelTw }}</td>
                    @foreach ($fieldDebit as $field)
                        <td class="kanan">{{ number_format($triwulanData[$tw]['rincian'][$field] ?? 0, 0, ',', '.') }}</td>
                    @endforeach
                    <td class="kanan">{{ number_format($triwulanData[$tw]['debit'], 0, ',', '.') }}</td>
                    @foreach ($fieldKredit as $field)
                        <td class="kanan">{{ number_format($triwulanData[$tw]['rincian'][$field] ?? 0, 0, ',', '.') }}</td>
                    @endforeach
                    <td class="kanan">{{ number_format($triwulanData[$tw]['kredit'], 0, ',', '.') }}</td>
                    <td class="kanan">{{ number_format($triwulanData[$tw]['saldo'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="baris-jumlah">
                <td colspan="2" class="tengah">Jumlah</td>
                @foreach ($fieldDebit as $field)
                    <td class="kanan">{{ number_format($totalRaw[$field], 0, ',', '.') }}</td>
                @endforeach
                <td class="kanan">{{ number_format($totalJumlahDebit, 0, ',', '.') }}</td>
                @foreach ($fieldKredit as $field)
                    <td class="kanan">{{ number_format($totalRaw[$field], 0, ',', '.') }}</td>
                @endforeach
                <td class="kanan">{{ number_format($totalJumlahKredit, 0, ',', '.') }}</td>
                <td class="kanan">{{ number_format($saldoAkhir, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Lembar tanda tangan Bendahara BOSP & Kepala Sekolah, sebagai baris
         tambahan DI DALAM tabel bulanan agar sejajar kolom (pola sama
         seperti pdf/unduhan-lampiran.blade.php). --}}
    <table class="data" style="margin-top: 14px;">
        <tr class="baris-spasi-ttd"><td colspan="7"></td><td colspan="7"></td></tr>
        <tr class="baris-ttd">
            <td colspan="7">Mengetahui/Menyetujui :</td>
            <td colspan="7">Sukabumi, .................... {{ $tahun }}</td>
        </tr>
        <tr class="baris-ttd">
            <td colspan="7">Bendahara BOSP</td>
            <td colspan="7">Kepala Sekolah,</td>
        </tr>
        <tr class="baris-spasi-ttd"><td colspan="7" class="spasi-nama"></td><td colspan="7" class="spasi-nama"></td></tr>
        <tr class="baris-ttd">
            <td colspan="7">{{ $sekolah->nama_bendahara ?: '...................................' }}</td>
            <td colspan="7">{{ $sekolah->nama_kepala_sekolah ?: '...................................' }}</td>
        </tr>
        <tr class="baris-ttd">
            <td colspan="7">NIP. {{ $sekolah->nip_bendahara ?: '...................................' }}</td>
            <td colspan="7">NIP. {{ $sekolah->nip_kepala_sekolah ?: '...................................' }}</td>
        </tr>
    </table>
</body>
</html>
