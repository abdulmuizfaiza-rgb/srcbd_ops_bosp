<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* dompdf: CSS sederhana, pola sama seperti pdf/pajak-bosp-reguler.blade.php.
           Font & padding dibuat sekecil mungkin karena tabelnya sangat lebar
           (31 kolom data) - kertas A3 landscape dipakai (bukan A4 seperti
           menu lain) supaya tetap terbaca & tidak terpotong. */
        body { font-family: sans-serif; font-size: 6.5px; color: #1e293b; }
        .judul-1 { text-align: center; font-weight: bold; font-size: 12px; margin-bottom: 2px; }
        .judul-2 { text-align: center; font-weight: bold; font-size: 10px; margin-bottom: 2px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; table-layout: fixed; }
        table.data th, table.data td { border: 1px solid #94a3b8; padding: 2px 3px; word-wrap: break-word; }
        table.data th { background-color: #dbeafe; font-weight: bold; text-align: center; }
        table.data td { text-align: right; }
        table.data td.kiri { text-align: left; }
        table.data td.tengah { text-align: center; }
        tr.baris-jumlah td { background-color: #e2e8f0; font-weight: bold; }
    </style>
</head>
<body>
    <div class="judul-1">REKAP RKAS AWAL-PERUBAHAN</div>
    <div class="judul-2">TAHUN ANGGARAN {{ $tahun }}</div>

    <table class="data">
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Nama Sekolah</th>
                <th rowspan="2">Anggaran BOSP {{ $tahun }}</th>
                @foreach ($kategori as $info)
                    <th colspan="5">{{ $info['label'] }}</th>
                @endforeach
                <th colspan="3">JUMLAH</th>
            </tr>
            <tr>
                @foreach ($kategori as $info)
                    <th>Sebelum {{ $info['sebelum'] }}</th>
                    <th>Realisasi TA1</th>
                    <th>Perubahan TA2</th>
                    <th>Jml Sesudah</th>
                    <th>Selisih {{ $info['singkatan'] }}</th>
                @endforeach
                <th>Sebelum</th>
                <th>Sesudah</th>
                <th>Selisih</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($daftarSekolah as $i => $sekolah)
                @php($rekap = $sekolah->rekapRkasTahunIni)
                <tr>
                    <td class="tengah">{{ $i + 1 }}</td>
                    <td class="kiri">{{ $sekolah->nama_sekolah }}</td>
                    <td>{{ number_format((int) ($sekolah->anggaranBospOtomatis ?? 0), 0, ',', '.') }}</td>
                    @foreach ($kategori as $kunci => $info)
                        <td>{{ number_format((int) ($rekap?->{$kunci.'_sebelum'} ?? 0), 0, ',', '.') }}</td>
                        <td>{{ number_format((int) ($rekap?->{$kunci.'_realisasi_tahap1'} ?? 0), 0, ',', '.') }}</td>
                        <td>{{ number_format((int) ($rekap?->{$kunci.'_perubahan_tahap2'} ?? 0), 0, ',', '.') }}</td>
                        <td>{{ number_format((int) ($rekap?->{$kunci.'_jml_sesudah'} ?? 0), 0, ',', '.') }}</td>
                        <td>{{ number_format((int) ($rekap?->{$kunci.'_selisih'} ?? 0), 0, ',', '.') }}</td>
                    @endforeach
                    <td>{{ number_format((int) ($rekap?->jumlah_sebelum ?? 0), 0, ',', '.') }}</td>
                    <td>{{ number_format((int) ($rekap?->jumlah_sesudah ?? 0), 0, ',', '.') }}</td>
                    <td>{{ number_format((int) ($rekap?->jumlah_selisih ?? 0), 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="31" class="tengah">Belum ada data sekolah.</td>
                </tr>
            @endforelse

            @if ($daftarSekolah->isNotEmpty())
                <tr class="baris-jumlah">
                    <td colspan="2" class="kiri">JUMLAH</td>
                    <td>{{ number_format((int) ($totalBaris['anggaran_bosp'] ?? 0), 0, ',', '.') }}</td>
                    @foreach ($kategori as $kunci => $info)
                        <td>{{ number_format((int) ($totalBaris[$kunci.'_sebelum'] ?? 0), 0, ',', '.') }}</td>
                        <td>{{ number_format((int) ($totalBaris[$kunci.'_realisasi_tahap1'] ?? 0), 0, ',', '.') }}</td>
                        <td>{{ number_format((int) ($totalBaris[$kunci.'_perubahan_tahap2'] ?? 0), 0, ',', '.') }}</td>
                        <td>{{ number_format((int) ($totalBaris[$kunci.'_jml_sesudah'] ?? 0), 0, ',', '.') }}</td>
                        <td>{{ number_format((int) ($totalBaris[$kunci.'_selisih'] ?? 0), 0, ',', '.') }}</td>
                    @endforeach
                    <td>{{ number_format((int) ($totalBaris['jumlah_sebelum'] ?? 0), 0, ',', '.') }}</td>
                    <td>{{ number_format((int) ($totalBaris['jumlah_sesudah'] ?? 0), 0, ',', '.') }}</td>
                    <td>{{ number_format((int) ($totalBaris['jumlah_selisih'] ?? 0), 0, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
