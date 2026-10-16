<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        {{-- dompdf: CSS sederhana (tidak ada flex/grid), meniru pola pdf/unduhan-lampiran.blade.php.
           PENTING (2026-10-16): table-layout:fixed WAJIB ada - tanpa ini dompdf
           melebarkan tabel sesuai isi kolom terpanjang & memotong sisanya di luar
           halaman (bukan menyesuaikan/word-wrap) - inilah sebab laporan sebelumnya
           "kepotong" (permintaan user). Class "tabel-lebar" (huruf & padding
           dikecilkan) dipakai krn laporan ini 14 kolom - meniru pola yang sama
           di pdf/unduhan-lampiran.blade.php (Lampiran 2c, 13 kolom). --}}
        body { font-family: sans-serif; font-size: 9px; color: #1e293b; }
        .judul-1 { text-align: center; font-weight: bold; font-size: 13px; margin-bottom: 8px; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data th, table.data td { border: 1px solid #94a3b8; padding: 3px 4px; word-wrap: break-word; overflow-wrap: break-word; }
        table.data th { background-color: #f1f5f9; font-weight: bold; text-align: center; }
        table.data td { text-align: left; }
        table.data td.tengah { text-align: center; }
        table.data.tabel-lebar { font-size: 8px; }
        table.data.tabel-lebar th, table.data.tabel-lebar td { padding: 2px 3px; }
        .kosong { text-align: center; color: #94a3b8; padding: 10px; }
    </style>
</head>
<body>

    <div class="judul-1">DAFTAR IDENTITAS OPS</div>

    <table class="data tabel-lebar">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Sekolah</th>
                <th>Nama OPS</th>
                <th>NUPTK</th>
                <th>NIP</th>
                <th>JK</th>
                <th>Tempat Lahir</th>
                <th>Tanggal Lahir</th>
                <th>Status Kepegawaian</th>
                <th>Pendidikan Terakhir</th>
                <th>Jurusan</th>
                <th>Nama Perguruan Tinggi</th>
                <th>No WhatsApp</th>
                <th>SK OPS</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($daftarSekolah as $sekolah)
                @php $ops = $sekolah->identitasOps; @endphp
                <tr>
                    <td class="tengah">{{ $loop->iteration }}</td>
                    <td>{{ $sekolah->nama_sekolah }}</td>
                    <td>{{ $ops?->nama ?? '-' }}</td>
                    <td>{{ $ops?->nuptk ?? '-' }}</td>
                    <td>{{ $ops?->nip ?? '-' }}</td>
                    <td class="tengah">{{ $ops?->jk ?? '-' }}</td>
                    <td>{{ $ops?->tempat_lahir ?? '-' }}</td>
                    <td class="tengah">{{ $ops?->tanggal_lahir ? $ops->tanggal_lahir->format('d-m-Y') : '-' }}</td>
                    <td>{{ $ops?->status_kepegawaian ?? '-' }}</td>
                    <td>{{ $ops?->pendidikan_terakhir ?? '-' }}</td>
                    <td>{{ $ops?->jurusan ?? '-' }}</td>
                    <td>{{ $ops?->nama_perguruan_tinggi ?? '-' }}</td>
                    <td>{{ $ops?->no_whatsapp ?? '-' }}</td>
                    <td class="tengah">{{ $ops?->sk_ops ? 'Ada' : 'Tidak Ada' }}</td>
                </tr>
            @empty
                <tr>
                    <td class="kosong" colspan="14">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
