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
        tr.baris-jumlah td { background-color: #e2e8f0; font-weight: bold; }
        tr.baris-total-tahun td { background-color: #cbd5e1; font-weight: bold; }
    </style>
</head>
<body>
    <div class="judul-1">REKAPITULASI LAPORAN REALISASI BOSP (FORM BPK) - REKAP SELURUH SEKOLAH</div>
    <div class="judul-2">TAHUN ANGGARAN {{ $tahun }}</div>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 2%;">Triwulan</th>
                <th style="width: 6%;">NPSN</th>
                <th style="width: 10%;">Nama Sekolah</th>
                @foreach ($label as $field => $teks)
                    <th>{{ $teks }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($hasil as $item)
                @php $sekolah = $item['sekolah']; @endphp
                @foreach ([1, 2, 3, 4] as $triwulan)
                    @php $data = $item['perTriwulan'][$triwulan] ?? []; @endphp
                    <tr>
                        <td class="tengah">TW-{{ $triwulan }}</td>
                        <td class="kiri">{{ $sekolah->npsn }}</td>
                        <td class="kiri">{{ $sekolah->nama_sekolah }}</td>
                        @foreach ($label as $field => $teks)
                            <td class="{{ $field === 'verifikasi_saldo' ? 'tengah' : '' }}">
                                {{ $field === 'verifikasi_saldo' ? (($data[$field] ?? null) !== null && ($data[$field] ?? '') !== '' ? $data[$field] : '-') : number_format((int) ($data[$field] ?? 0), 0, ',', '.') }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                <tr class="baris-jumlah">
                    <td class="tengah">Jumlah</td>
                    <td class="kiri">{{ $sekolah->npsn }}</td>
                    <td class="kiri">{{ $sekolah->nama_sekolah }}</td>
                    @php $jumlah = $item['jumlah'] ?? []; @endphp
                    @foreach ($label as $field => $teks)
                        <td class="{{ $field === 'verifikasi_saldo' ? 'tengah' : '' }}">
                            {{ $field === 'verifikasi_saldo' ? (($jumlah[$field] ?? '') !== '' ? $jumlah[$field] : '-') : number_format((int) ($jumlah[$field] ?? 0), 0, ',', '.') }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ 3 + count($label) }}">Tidak ada sekolah yang bisa ditampilkan.</td></tr>
            @endforelse
            @if (count($hasil) > 0)
                <tr class="baris-total-tahun">
                    <td colspan="3" class="tengah">JUMLAH TAHUN ANGGARAN {{ $tahun }}</td>
                    @foreach ($label as $field => $teks)
                        <td class="{{ $field === 'verifikasi_saldo' ? 'tengah' : '' }}">
                            {{ $field === 'verifikasi_saldo' ? (($totalTahunAnggaran[$field] ?? '') !== '' ? $totalTahunAnggaran[$field] : '-') : number_format((int) ($totalTahunAnggaran[$field] ?? 0), 0, ',', '.') }}
                        </td>
                    @endforeach
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
