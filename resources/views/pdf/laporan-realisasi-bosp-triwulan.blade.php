<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 6px; color: #1e293b; }
        .judul-1 { text-align: center; font-weight: bold; font-size: 11px; margin-bottom: 2px; }
        .judul-2 { text-align: center; font-weight: bold; font-size: 9px; margin-bottom: 4px; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data th, table.data td { border: 1px solid #94a3b8; padding: 1.5px 2px; word-wrap: break-word; overflow: hidden; }
        table.data th { background-color: #f1f5f9; font-weight: bold; text-align: center; }
        table.data td { text-align: right; }
        table.data td.kiri { text-align: left; }
        table.data td.tengah { text-align: center; }
        tr.baris-jumlah td { background-color: #cbd5e1; font-weight: bold; }
    </style>
</head>
<body>
    <div class="judul-1">LAPORAN REALISASI BOSP (FORM BPK) - REKAP SELURUH SEKOLAH</div>
    <div class="judul-2">TRIWULAN {{ $triwulan }}, TAHUN ANGGARAN {{ $tahun }}</div>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 2%;">No</th>
                <th style="width: 6%;">NPSN</th>
                <th style="width: 10%;">Nama Sekolah</th>
                @foreach ($label as $field => $teks)
                    <th>{{ $teks }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($daftarSekolah as $i => $sekolah)
                <tr>
                    <td class="tengah">{{ $i + 1 }}</td>
                    <td class="kiri">{{ $sekolah->npsn }}</td>
                    <td class="kiri">{{ $sekolah->nama_sekolah }}</td>
                    @php $data = $totalBaris[$sekolah->id] ?? []; @endphp
                    @foreach ($label as $field => $teks)
                        <td class="{{ $field === 'verifikasi_saldo' ? 'tengah' : '' }}">
                            {{ $field === 'verifikasi_saldo' ? (($data[$field] ?? '') !== '' ? $data[$field] : '-') : number_format((int) ($data[$field] ?? 0), 0, ',', '.') }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ 3 + count($label) }}">Tidak ada sekolah yang bisa ditampilkan.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
