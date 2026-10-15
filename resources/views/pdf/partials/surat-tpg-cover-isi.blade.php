{{--
    Isi tab "Cover SMPN"/"Cover SMP-S" (BARU 2026-10-15, permintaan user)
    pada menu "Format Surat Rekomendasi & Pembatalan TPG" - dipakai
    bersama oleh KEDUA jenis cover, pada ketiga tempat ditampilkan
    (preview layar/Livewire, PDF via dompdf, & Word), sama seperti pola
    partial surat-tpg-kop.blade.php.

    BEDA PENTING dengan 3 tab lain (Rekomendasi/Penghentian/Pernyataan):
    tab ini TIDAK PUNYA field manual sama sekali ("untuk tab 1 dan tab 2
    diambil otomatis datanya" - permintaan user eksplisit) - seluruh isi
    murni otomatis dari: filter Tahun/Triwulan yang sudah ada di menu ini
    ($tahun/$triwulan), & data Profil Sekolah ($sekolah->nama_sekolah,
    alamat_sekolah, status, nama_yayasan, logo_sekolah). Karena itu
    partial ini TIDAK menerima/memakai $editable sama sekali - tidak ada
    kotak upload/input apa pun di sini, beda dgn partial isi surat
    lainnya. Logo Sekolah diupload lewat menu Profil Sekolah (BUKAN di
    sini), lihat App\Livewire\ProfilSekolah\Index::updatedLogoSekolahBaru().

    Variabel yang dipakai (semua sudah disediakan oleh
    App\Livewire\PendataanOps\SuratTpg\Index::dataSurat() &
    App\Http\Controllers\SuratTpgCetakController):
    - $sekolah (App\Models\ProfilSekolah): WAJIB ada (blok ini hanya
      di-include saat $sekolah sudah dipilih - lihat blade induk).
    - $tahun, $triwulan (int).
    - $logoSekolahSrc (string|null): src siap pakai utk <img> - route
      terautentikasi (preview layar) ATAU data URI base64 (PDF/Word),
      lihat App\Models\ProfilSekolah::logoSekolahDataUri() &
      App\Http\Controllers\LogoSekolahFileController - pola SAMA PERSIS
      dengan $kopSuratSrc pada partial surat-tpg-kop.blade.php.

    Sumber isi cover (ditentukan dari gambar referensi user 2026-10-15):
    - Negeri ("Cover SMPN"): judul "...APARATUR SIPIL NEGARA", lalu Nama
      Sekolah, Alamat Sekolah, Tahun.
    - Swasta ("Cover SMP-S"): judul "...NON APARATUR SIPIL NEGARA", lalu
      TAMBAHAN baris Nama Yayasan (dari Profil Sekolah) SEBELUM Nama
      Sekolah, baru Alamat Sekolah, Tahun.

    SENGAJA ditulis dgn div polos (BUKAN flexbox/grid - dompdf tidak
    mendukungnya), semua center lewat text-align, mengikuti konvensi
    partial surat lain di aplikasi ini.
--}}
@php($isNegeri = $sekolah->status === \App\Models\ProfilSekolah::STATUS_NEGERI)

<div style="text-align:center;">
    <div style="font-weight:bold;font-size:13pt;line-height:1.5;text-transform:uppercase;">
        Verifikasi dan Validasi<br>
        Tunjangan Profesi Guru<br>
        {{ $isNegeri ? 'Aparatur Sipil Negara' : 'Non Aparatur Sipil Negara' }}<br>
        Triwulan {{ $triwulan }} Tahun {{ $tahun }}
    </div>

    <div style="margin-top:110px;">
        @if ($logoSekolahSrc)
            <img src="{{ $logoSekolahSrc }}" style="max-width:160px;max-height:160px;">
        @else
            <p style="color:#94a3b8;font-size:9pt;">(Logo Sekolah belum diupload - lengkapi lewat menu Profil Sekolah)</p>
        @endif
    </div>

    <div style="margin-top:90px;font-weight:bold;font-size:13pt;line-height:1.5;">
        @unless ($isNegeri)
            {{ $sekolah->nama_yayasan ?: '...................................' }}<br>
        @endunless
        {{ $sekolah->nama_sekolah }}
    </div>
    <div style="font-weight:bold;font-size:11pt;margin-top:4px;">
        {{ $sekolah->alamat_sekolah ?: '...................................' }}
    </div>
    <div style="font-weight:bold;font-size:11pt;">
        {{ $tahun }}
    </div>
</div>
