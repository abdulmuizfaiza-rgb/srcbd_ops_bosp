<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* dompdf: CSS sederhana saja (tidak ada flex/grid) supaya rendering
           konsisten - layout tabel HTML biasa meniru kop/tabel/lembar tanda
           tangan yang sama seperti hasil Export Excel.

           PENTING (2026-09-05, lanjutan): lembar tanda tangan Pengawas &
           Kepala Sekolah SEKARANG jadi baris tambahan DI DALAM tabel data
           yang sama (table.data), BUKAN tabel .ttd terpisah seperti
           sebelumnya - supaya posisi tanda tangan benar-benar SEJAJAR
           dengan kolom data di atasnya (Pengawas selalu mulai dari kolom
           B, Kepala Sekolah mulai dari kolom yang diminta per lampiran),
           yang hanya bisa dijamin presisi kalau memakai colspan pada
           tabel yang sama (lebar kolom tabel terpisah tidak dijamin sama
           persis walau jumlah kolomnya sama). */
        body { font-family: sans-serif; font-size: 10px; color: #1e293b; }
        .section { page-break-after: always; }
        .section:last-child { page-break-after: auto; }
        .judul-1 { text-align: center; font-weight: bold; font-size: 13px; margin-bottom: 2px; }
        .judul-2, .judul-3 { text-align: center; font-weight: bold; font-size: 11px; margin-bottom: 2px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        table.data th, table.data td { border: 1px solid #94a3b8; padding: 4px 5px; word-wrap: break-word; }
        table.data th { background-color: #f1f5f9; font-weight: bold; text-align: center; }
        table.data td { text-align: left; }
        table.data td.tengah { text-align: center; }
        table.data td.kanan { text-align: right; }
        .kosong { text-align: center; color: #94a3b8; padding: 10px; }

        /* Lampiran 2c: 13 kolom pada 1 halaman A4 landscape - huruf &
           padding dikecilkan (dibanding 2a/2b) supaya seluruh kolom tetap
           muat rapi tanpa dipisah halaman, mengikuti pilihan "perkecil
           otomatis" yang diminta user. */
        table.data.tabel-lebar { font-size: 8px; }
        table.data.tabel-lebar th, table.data.tabel-lebar td { padding: 2px 3px; }

        /* Baris lembar tanda tangan (di dalam table.data yang sama, lihat
           catatan di atas) - tanpa garis tabel & sedikit lebih lega. */
        tr.baris-spasi-ttd td { border: none; height: 18px; padding: 0; }
        tr.baris-ttd td { border: none; padding: 2px 5px; vertical-align: top; }
        tr.baris-ttd td.spasi-nama { height: 26px; }
        table.data.tabel-lebar tr.baris-ttd td { font-size: 9px; padding: 2px 3px; }
    </style>
</head>
<body>

    {{-- ============================= LAMPIRAN 2A ============================= --}}
    {{--
        Kolom: A=NRG B=NUPTK C=Nama PTK D=Status Kepegawaian E=Nama Sekolah
        F=Gaji Pokok Bulan Januari G=NPWP (7 kolom).
        Tanda tangan: Pengawas mulai kolom B (span B-E), Kepala Sekolah
        mulai kolom F (span F-G) - sesuai permintaan user.
    --}}
    <div class="section">
        <div class="judul-1">
            LAMPIRAN SURAT REKOMENDASI TENTANG USULAN PENERIMA TUNJANGAN PROFESI GURU
            @if ($sekolah === null) - REKAP SELURUH SEKOLAH @endif
        </div>
        <div class="judul-2">TRIWULAN {{ $triwulan }} TAHUN ANGGARAN {{ $tahun }}</div>
        <div class="judul-3">KOMISARIAT/KECAMATAN CIBADAK</div>

        <table class="data">
            <thead>
                <tr>
                    <th>NRG</th>
                    <th>NUPTK</th>
                    <th>Nama PTK</th>
                    <th>Status Kepegawaian</th>
                    <th>Nama Sekolah</th>
                    <th>Gaji Pokok Bulan Januari {{ $tahun }}</th>
                    <th>NPWP</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($baris2a as $item)
                    <tr>
                        <td>{{ $item->nrg }}</td>
                        <td>{{ $item->nuptk }}</td>
                        <td>{{ $item->nama_ptk }}</td>
                        <td class="tengah">{{ $item->status_kepegawaian }}</td>
                        <td>{{ $item->profilSekolah->nama_sekolah ?? $sekolah?->nama_sekolah }}</td>
                        <td class="kanan">Rp {{ number_format($item->gaji_pokok_januari, 0, ',', '.') }}</td>
                        <td>{{ $item->npwp }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="kosong">Belum ada data.</td></tr>
                @endforelse

                @if ($sekolah !== null)
                    <tr class="baris-spasi-ttd"><td colspan="7"></td></tr>
                    <tr class="baris-ttd">
                        <td></td>
                        <td colspan="4">Mengetahui/Menyetujui :</td>
                        <td colspan="2">Sukabumi, .................... {{ $tahun }}</td>
                    </tr>
                    <tr class="baris-ttd">
                        <td></td>
                        <td colspan="4">Pengawas</td>
                        <td colspan="2">Kepala Sekolah,</td>
                    </tr>
                    <tr class="baris-ttd">
                        <td></td>
                        <td colspan="4" class="spasi-nama"></td>
                        <td colspan="2" class="spasi-nama"></td>
                    </tr>
                    <tr class="baris-ttd">
                        <td></td>
                        <td colspan="4">{{ $sekolah->nama_pengawas ?: '...................................' }}</td>
                        <td colspan="2">{{ $sekolah->nama_kepala_sekolah ?: '...................................' }}</td>
                    </tr>
                    <tr class="baris-ttd">
                        <td></td>
                        <td colspan="4">NIP. {{ $sekolah->nip_pengawas ?: '...................................' }}</td>
                        <td colspan="2">NIP. {{ $sekolah->nip_kepala_sekolah ?: '...................................' }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- ============================= LAMPIRAN 2B ============================= --}}
    {{--
        Kolom: A=NRG B=NUPTK C=Nama PTK D=Nama Sekolah E=Keterangan F=TMT
        (6 kolom).
        Tanda tangan: Pengawas mulai kolom B (span B-D), Kepala Sekolah
        mulai kolom E (span E-F) - sesuai permintaan user.
    --}}
    <div class="section">
        <div class="judul-1">
            LAMPIRAN SURAT REKOMENDASI TENTANG USULAN PENERIMA TUNJANGAN PROFESI GURU
            @if ($sekolah === null) - REKAP SELURUH SEKOLAH @endif
        </div>
        <div class="judul-2">TRIWULAN {{ $triwulan }} TAHUN ANGGARAN {{ $tahun }}</div>
        <div class="judul-3">KOMISARIAT/KECAMATAN CIBADAK</div>

        <table class="data">
            <thead>
                <tr>
                    <th>NRG</th>
                    <th>NUPTK</th>
                    <th>Nama PTK</th>
                    <th>Nama Sekolah</th>
                    <th>Keterangan</th>
                    <th>TMT</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($baris2b as $item)
                    <tr>
                        <td>{{ $item->nrg }}</td>
                        <td>{{ $item->nuptk }}</td>
                        <td>{{ $item->nama_ptk }}</td>
                        <td>{{ $item->profilSekolah->nama_sekolah ?? $sekolah?->nama_sekolah }}</td>
                        <td>{{ $item->keterangan }}</td>
                        <td class="tengah">{{ optional($item->tmt)->format('d-m-Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="kosong">Belum ada data.</td></tr>
                @endforelse

                @if ($sekolah !== null)
                    <tr class="baris-spasi-ttd"><td colspan="6"></td></tr>
                    <tr class="baris-ttd">
                        <td></td>
                        <td colspan="3">Mengetahui/Menyetujui :</td>
                        <td colspan="2">Sukabumi, ................................ {{ $tahun }}</td>
                    </tr>
                    <tr class="baris-ttd">
                        <td></td>
                        <td colspan="3">Pengawas</td>
                        <td colspan="2">Kepala Sekolah</td>
                    </tr>
                    <tr class="baris-ttd">
                        <td></td>
                        <td colspan="3" class="spasi-nama"></td>
                        <td colspan="2" class="spasi-nama"></td>
                    </tr>
                    <tr class="baris-ttd">
                        <td></td>
                        <td colspan="3">{{ $sekolah->nama_pengawas ?: '................................' }}</td>
                        <td colspan="2">{{ $sekolah->nama_kepala_sekolah ?: '................................' }}</td>
                    </tr>
                    <tr class="baris-ttd">
                        <td></td>
                        <td colspan="3">NIP. {{ $sekolah->nip_pengawas ?: '...................................' }}</td>
                        <td colspan="2">NIP. {{ $sekolah->nip_kepala_sekolah ?: '...................................' }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- ============================= LAMPIRAN 2C ============================= --}}
    {{--
        Kolom: A=NRG B=NUPTK C=Nama PTK D=Tempat Tugas E=Kecamatan
        F=Jenis Kepangkatan G=Golongan H=Masa Kerja I=Pangkat/Berkala J=TMT
        K=Gaji Pokok Lama L=Gaji Pokok Baru M=Keterangan (13 kolom).
        Tanda tangan: Pengawas mulai kolom B (span B-K), Kepala Sekolah
        mulai kolom L (span L-M) - sesuai permintaan user. Tabel memakai
        class "tabel-lebar" (huruf & padding lebih kecil) supaya 13 kolom
        tetap muat rapi dalam 1 halaman.
    --}}
    <div class="section">
        <div class="judul-1">DAFTAR PENYESUAIAN GAJI POKOK</div>
        @if ($sekolah === null)
            <div class="judul-2">REKAP SELURUH SEKOLAH - TRIWULAN {{ $triwulan }} TAHUN {{ $tahun }}</div>
        @else
            <div class="judul-2">KECAMATAN : {{ $baris2c->first()->kecamatan ?? '...................................' }}</div>
        @endif
        <div class="judul-3">SUBRAYON : CIBADAK</div>

        <table class="data tabel-lebar">
            <thead>
                <tr>
                    <th>NRG</th>
                    <th>NUPTK</th>
                    <th>Nama PTK</th>
                    <th>Tempat Tugas</th>
                    <th>Kecamatan</th>
                    <th>Jenis Kepangkatan</th>
                    <th>Golongan</th>
                    <th>Masa Kerja</th>
                    <th>Pangkat/Berkala</th>
                    <th>TMT</th>
                    <th>Gaji Pokok Lama</th>
                    <th>Gaji Pokok Baru</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($baris2c as $item)
                    <tr>
                        <td>{{ $item->nrg }}</td>
                        <td>{{ $item->nuptk }}</td>
                        <td>{{ $item->nama_ptk }}</td>
                        <td>{{ $item->profilSekolah->nama_sekolah ?? $sekolah?->nama_sekolah }}</td>
                        <td class="tengah">{{ $item->kecamatan }}</td>
                        <td class="tengah">{{ $item->jenis_kepangkatan }}</td>
                        <td class="tengah">{{ $item->golongan }}</td>
                        <td class="tengah">{{ $item->masa_kerja }}</td>
                        <td class="tengah">{{ $item->pangkat_berkala }}</td>
                        <td class="tengah">{{ optional($item->tmt)->format('d-m-Y') }}</td>
                        <td class="kanan">Rp {{ number_format($item->gaji_pokok_lama, 0, ',', '.') }}</td>
                        <td class="kanan">Rp {{ number_format($item->gaji_pokok_baru, 0, ',', '.') }}</td>
                        <td>{{ $item->keterangan }}</td>
                    </tr>
                @empty
                    <tr><td colspan="13" class="kosong">Belum ada data.</td></tr>
                @endforelse

                @if ($sekolah !== null)
                    <tr class="baris-spasi-ttd"><td colspan="13"></td></tr>
                    <tr class="baris-ttd">
                        <td></td>
                        <td colspan="10">Mengetahui/Menyetujui :</td>
                        <td colspan="2">Sukabumi, .................... {{ $tahun }}</td>
                    </tr>
                    <tr class="baris-ttd">
                        <td></td>
                        <td colspan="10">Pengawas</td>
                        <td colspan="2">Kepala Sekolah</td>
                    </tr>
                    <tr class="baris-ttd">
                        <td></td>
                        <td colspan="10" class="spasi-nama"></td>
                        <td colspan="2" class="spasi-nama"></td>
                    </tr>
                    <tr class="baris-ttd">
                        <td></td>
                        <td colspan="10">{{ $sekolah->nama_pengawas ?: '...........................' }}</td>
                        <td colspan="2">{{ $sekolah->nama_kepala_sekolah ?: '...........................' }}</td>
                    </tr>
                    <tr class="baris-ttd">
                        <td></td>
                        <td colspan="10">NIP. {{ $sekolah->nip_pengawas ?: '...................................' }}</td>
                        <td colspan="2">NIP. {{ $sekolah->nip_kepala_sekolah ?: '...................................' }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

</body>
</html>
