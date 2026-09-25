<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #1e293b; }
        .judul-1 { text-align: center; font-weight: bold; font-size: 13px; margin-bottom: 2px; }
        .judul-2 { text-align: center; font-weight: bold; font-size: 11px; margin-bottom: 10px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data th, table.data td { border: 1px solid #94a3b8; padding: 4px 5px; }
        table.data th { background-color: #f1f5f9; font-weight: bold; text-align: center; }
        table.data td { text-align: left; }
        table.data td.kanan { text-align: right; }
        table.data td.tengah { text-align: center; }
        .kosong { text-align: center; color: #94a3b8; padding: 10px; }
    </style>
</head>
<body>
    <div class="judul-1">REKAPITULASI PAJAK BOSP REGULER SELURUH SEKOLAH</div>
    <div class="judul-2">TAHUN ANGGARAN {{ $tahun }}</div>

    <table class="data">
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">NPSN</th>
                <th rowspan="2">Nama Sekolah</th>
                <th colspan="6">Total Debit Setahun</th>
                <th colspan="6">Total Kredit Setahun</th>
                <th rowspan="2">Saldo Akhir</th>
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
            @forelse ($rekapSekolah as $i => $rekap)
                <tr>
                    <td class="tengah">{{ $i + 1 }}</td>
                    <td>{{ $rekap['sekolah']->npsn }}</td>
                    <td>{{ $rekap['sekolah']->nama_sekolah }}</td>
                    @foreach ($fieldDebit as $field)
                        <td class="kanan">{{ number_format($rekap['rincian'][$field] ?? 0, 0, ',', '.') }}</td>
                    @endforeach
                    <td class="kanan">Rp {{ number_format($rekap['total_debit'], 0, ',', '.') }}</td>
                    @foreach ($fieldKredit as $field)
                        <td class="kanan">{{ number_format($rekap['rincian'][$field] ?? 0, 0, ',', '.') }}</td>
                    @endforeach
                    <td class="kanan">Rp {{ number_format($rekap['total_kredit'], 0, ',', '.') }}</td>
                    <td class="kanan">Rp {{ number_format($rekap['saldo_akhir'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="16" class="kosong">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
