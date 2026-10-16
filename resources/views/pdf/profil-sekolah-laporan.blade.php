<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        {{-- dompdf: CSS sederhana (tidak ada flex/grid), meniru pola pdf/unduhan-lampiran.blade.php. --}}
        body { font-family: sans-serif; font-size: 9px; color: #1e293b; }
        .judul-1 { text-align: center; font-weight: bold; font-size: 13px; margin-bottom: 8px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #94a3b8; padding: 3px 4px; word-wrap: break-word; }
        table.data th { background-color: #f1f5f9; font-weight: bold; text-align: center; }
        table.data td { text-align: left; }
        table.data td.tengah { text-align: center; }
        .kosong { text-align: center; color: #94a3b8; padding: 10px; }
    </style>
</head>
<body>

    <div class="judul-1">DAFTAR PROFIL SEKOLAH</div>

    <table class="data">
        <thead>
            <tr>
                <th>No</th>
                <th>NPSN</th>
                <th>Kode UPB</th>
                <th>Nama Sekolah</th>
                <th>Status</th>
                <th>Kecamatan</th>
                <th>Subrayon</th>
                <th>Nama Kepala Sekolah</th>
                <th>NIP Kepala Sekolah</th>
                <th>No WhatsApp Kepala Sekolah</th>
                <th>Status Kepegawaian Kepala Sekolah</th>
                <th>Nama Pengawas</th>
                <th>NIP Pengawas</th>
                <th>Nama Kasie KPML</th>
                <th>NIP Kasie KPML</th>
                <th>Nama Bendahara</th>
                <th>NIP Bendahara</th>
                <th>Status Kepegawaian Bendahara</th>
                <th>Alamat Sekolah</th>
                <th>Nama Yayasan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($daftarSekolah as $p)
                <tr>
                    <td class="tengah">{{ $loop->iteration }}</td>
                    <td>{{ $p->npsn }}</td>
                    <td>{{ $p->kode_upb }}</td>
                    <td>{{ $p->nama_sekolah }}</td>
                    <td class="tengah">{{ $p->status_label }}</td>
                    <td>{{ $p->kecamatan }}</td>
                    <td>{{ $p->subrayon }}</td>
                    <td>{{ $p->nama_kepala_sekolah }}</td>
                    <td>{{ $p->nip_kepala_sekolah }}</td>
                    <td>{{ $p->no_whatsapp_kepala_sekolah }}</td>
                    <td>{{ $p->status_kepegawaian_kepsek }}</td>
                    <td>{{ $p->nama_pengawas }}</td>
                    <td>{{ $p->nip_pengawas }}</td>
                    <td>{{ $p->nama_kasie_kpml }}</td>
                    <td>{{ $p->nip_kasie_kpml }}</td>
                    <td>{{ $p->nama_bendahara }}</td>
                    <td>{{ $p->nip_bendahara }}</td>
                    <td>{{ $p->status_kepegawaian_bendahara }}</td>
                    <td>{{ $p->alamat_sekolah }}</td>
                    <td>{{ $p->nama_yayasan }}</td>
                </tr>
            @empty
                <tr>
                    <td class="kosong" colspan="20">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
