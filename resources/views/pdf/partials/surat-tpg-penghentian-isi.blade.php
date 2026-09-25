{{--
    Isi Surat Penghentian Tunjangan Profesi Guru (Tab 2) - format sesuai
    gambar 2 yang diupload user (2026-09-24, round kedelapan belas).
    Dipakai bersama (SATU partial) di 3 tempat: preview layar (Livewire,
    $editable=true), PDF (dompdf, $editable=false), Word/.doc
    ($editable=false).

    Field Nama/NIP/Tempat Tugas pada blok "Yang bertanda tangan dibawah
    ini" SELALU diambil otomatis (permintaan user eksplisit): Nama <-
    ProfilSekolah::nama_kepala_sekolah, NIP <- nip_kepala_sekolah,
    Tempat Tugas <- nama_sekolah. Jabatan SELALU teks tetap "Kepala
    Sekolah" (bukan diambil dari data, sesuai gambar contoh).

    Kata "Triwulan" (BUKAN "Semester") dipakai di sini juga - lihat
    dokumentasi lengkap penyimpangan ini di App\Models\SuratTpg::labelTriwulan()
    docblock & progress-log - gambar contoh aslinya menuliskan "Semester
    I (Satu)" tapi user secara eksplisit meminta kedua tab dibuat
    berdasarkan triwulan.

    Perbaikan 2026-09-24 (round kesembilan belas, permintaan user poin 1,
    4 & 6): daftar 10 alasan & paragraf penutup yang SEBELUMNYA berkotak
    (border:1px solid #000) sekarang polos tanpa kotak, sama seperti
    perubahan pada surat-tpg-rekomendasi-isi.blade.php - "isi surat nya
    tidak perlu pakai garis hitam" berlaku untuk KEDUA tab. Blok identitas
    "Yang bertanda tangan dibawah ini" dari awal memang sudah tanpa
    border, jadi tidak berubah.

    Perbaikan 2026-09-24 (round kedua puluh, permintaan user poin 1):
    Input Nomor Surat sekarang rata kiri (bukan rata tengah) supaya teks
    yang diketik langsung menempel setelah tulisan "Nomor :" - "di isi
    dengan posisi sebelah kiri setelah tulisan No. / Nomor". (Poin 4 -
    spasi 1,5 - HANYA diminta utk tab Surat Rekomendasi TPG, TIDAK
    berlaku di sini.)

    Variabel: sama seperti surat-tpg-rekomendasi-isi.blade.php.
--}}
@php($editable = $editable ?? false)
@php($labelTriwulan = \App\Models\SuratTpg::labelTriwulan($triwulan))

<div style="text-align:center;margin-bottom:4px;">
    <span style="font-weight:bold;font-size:13pt;text-decoration:underline;">PENGHENTIAN TUNJANGAN PROFESI GURU</span><br>
    <span style="font-size:11pt;">
        Nomor :
        @if ($editable)
            <input type="text" wire:model.live="nomorSurat" placeholder="-------------------------------------.." class="border-0 border-b border-slate-400 bg-transparent text-sm focus:ring-0 focus:border-blue-500 px-1 py-0 text-left" style="display:inline-block;width:260px;">
        @else
            {{ $nomorSurat ?: '-------------------------------------..' }}
        @endif
    </span>
</div>

<div style="margin-top:14px;font-size:11pt;">
    Yang bertanda tangan dibawah ini :
</div>

<table style="width:100%;border-collapse:collapse;font-size:11pt;margin-top:4px;">
    <tr>
        <td style="width:140px;padding:2px 0;vertical-align:top;">Nama</td>
        <td style="width:14px;vertical-align:top;">:</td>
        <td style="vertical-align:top;">{{ $namaKepsek ?: '............................' }}</td>
    </tr>
    <tr>
        <td style="padding:2px 0;vertical-align:top;">NIP.</td>
        <td style="vertical-align:top;">:</td>
        <td style="vertical-align:top;">{{ $nipKepsek ?: '............................' }}</td>
    </tr>
    <tr>
        <td style="padding:2px 0;vertical-align:top;">Jabatan</td>
        <td style="vertical-align:top;">:</td>
        <td style="vertical-align:top;">Kepala Sekolah</td>
    </tr>
    <tr>
        <td style="padding:2px 0;vertical-align:top;">Tempat Tugas</td>
        <td style="vertical-align:top;">:</td>
        <td style="vertical-align:top;">{{ $namaSekolah ?: '............................' }}</td>
    </tr>
</table>

<div style="margin-top:12px;font-size:11pt;text-align:justify;">
    Menerangkan bahwa, berdasarkan hasil verifikasi dan validasi serta penilaian terhadap kinerja guru yang terdiri dari aspek :
</div>

<div style="font-size:11pt;margin:8px 0 2px;">
    <div style="margin:2px 0;">1. Tidak melaksanakan tugas dengan baik sesuai peraturan perundang-undangan;</div>
    <div style="margin:2px 0;">2. Tidak memenuhi jam tatap muka minimal 24 jam/minggu;</div>
    <div style="margin:2px 0;">3. Memiliki hasil nilai (PK) Guru dengan sebutan kurang baik pada tahun sebelumnya;</div>
    <div style="margin:2px 0;">4. Tidak memiliki surat keputusan tunjangan profesi (SKTP) yang dikeluarkan oleh Kementerian Pendidikan dan Kebudayaan;</div>
    <div style="margin:2px 0;">5. Sedang menerima sanksi hukum, baik disiplin pegawai maupun administrasi;</div>
    <div style="margin:2px 0;">6. Sudah memasuki usia 60 (enam puluh) tahun;</div>
    <div style="margin:2px 0;">7. Sedang melaksanakan tugas belajar;</div>
    <div style="margin:2px 0;">8. Meninggal Dunia;</div>
    <div style="margin:2px 0;">9. Cuti;</div>
    <div style="margin:2px 0 10px;">10. Mutasi ke struktural.</div>
</div>

<p style="font-size:11pt;text-align:justify;margin:8px 0;">
    Sesuai dengan hasil verifikasi dan validasi serta penilaian terhadap kinerja guru tersebut di atas, maka kepada nama-nama sebagaimana tercantum pada lampiran ini untuk dihentikan Pembayaran Tunjangan Profesi Guru {{ $labelTriwulan }} Tahun Anggaran {{ $tahun }} setelah mendapat persetujuan Pengawas sesuai dengan binaan masing-masing. Demikian surat keterangan ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.
</p>

@include('pdf.partials.surat-tpg-ttd', [
    'namaPengawas' => $namaPengawas ?? null,
    'nipPengawas' => $nipPengawas ?? null,
    'namaKepsek' => $namaKepsek ?? null,
    'nipKepsek' => $nipKepsek ?? null,
    'tanggalSurat' => $tanggalSurat ?? null,
    'editable' => $editable,
])
