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
    - $margin (array, opsional, keys: atas/kanan/bawah/kiri dalam cm):
      dikirim oleh wrapper PDF/Word/SuratTpgCetakController. Preview
      Livewire TIDAK mengirim $margin (hanya punya properti terpisah
      $marginAtas dst pada komponennya) - makanya ada fallback di bawah
      supaya partial ini tetap jalan konsisten di ketiga tempat.

    Sumber isi cover (ditentukan dari gambar referensi user 2026-10-15):
    - Negeri ("Cover SMPN"): judul "...APARATUR SIPIL NEGARA", lalu Nama
      Sekolah, Alamat Sekolah, Tahun.
    - Swasta ("Cover SMP-S"): judul "...NON APARATUR SIPIL NEGARA", lalu
      TAMBAHAN baris Nama Yayasan (dari Profil Sekolah) SEBELUM Nama
      Sekolah, baru Alamat Sekolah, Tahun.

    TATA LETAK (diperbarui 2026-10-15 malam, permintaan user ketiga -
    revisi dari versi "simetris di sisa ruang" sebelumnya yang masih
    dianggap belum pas oleh user):
    - Blok tulisan Nama Yayasan/Nama Sekolah/Alamat/Tahun SELALU berjarak
      PERSIS 2,5cm dari tepi bawah kertas fisik (A4 = 29,7cm), TIDAK
      terpengaruh berapa pun nilai Margin Bawah yang diatur user lewat
      panel "Setting Margin" - dikonfirmasi lewat AskUserQuestion
      (TIDAK berubah dari versi sebelumnya).
    - Logo SEKARANG diposisikan PERSIS DI TENGAH-TENGAH HALAMAN KERTAS
      (titik tengah vertikal kertas A4 = 29,7cm / 2 dari tepi atas
      kertas), BUKAN LAGI di tengah sisa ruang setelah judul seperti
      versi sebelumnya - permintaan eksplisit user 2026-10-15 malam.
      Posisi judul di bagian atas TIDAK berubah (tetap mulai tepat di
      garis margin atas, rata atas).
    - Dicapai TANPA position:absolute/fixed (yang perilakunya beda-beda
      antara dompdf/browser/Word, terutama Word yg exportnya berupa HTML
      disimpan sbg .doc - lihat App\Livewire\PendataanOps\SuratTpg\
      Index::exportWord()) & TANPA calc() di CSS (dukungan dompdf utk
      calc() tidak konsisten) - tetap dipakai teknik <table> dgn tinggi
      yang DIHITUNG LANGSUNG DI PHP, TAPI sekarang baris judul (baris 1)
      & baris logo (baris 2) SAMA-SAMA diberi tinggi TETAP (dihitung di
      PHP, bukan otomatis mengikuti isi): tinggi baris judul dihitung
      sedemikian rupa supaya titik tengah baris logo PERSIS jatuh di
      titik tengah kertas, berapa pun panjang teks judul atau margin
      atas yang sedang diatur. Baris tulisan bawah (baris 3) tetap
      height:auto + vertical-align:bottom seperti sebelumnya (TIDAK
      berubah), supaya tetap presisi 2,5cm dari tepi bawah kertas.
--}}
@php
    // Catatan penulisan komentar: hindari menulis kata directive Blade
    // apa pun (diawali tanda "at", mis. kata "php"/"endphp"/"if") secara
    // harfiah di dalam komentar manapun yang berada di dalam blok
    // directive ini - pernah memicu bug teks komentar bocor tampil di
    // halaman (dilaporkan user 2026-10-15).
    $isNegeri = $sekolah->status === \App\Models\ProfilSekolah::STATUS_NEGERI;

    $m = $margin ?? [
        'atas' => $marginAtas ?? 3,
        'kanan' => $marginKanan ?? 2.5,
        'bawah' => $marginBawah ?? 2.5,
        'kiri' => $marginKiri ?? 2.5,
    ];

    // A4 = 29,7cm. Jarak tetap 2,5cm dari tepi kertas FISIK (bukan dari
    // margin bawah yang diatur user) - permintaan eksplisit user
    // 2026-10-15, dikonfirmasi lewat AskUserQuestion. Tabel ini dimulai
    // tepat di garis margin atas (mengikuti aturan margin halaman milik
    // wrapper PDF/Word & posisi alami elemen pertama pada preview layar),
    // jadi tinggi tabel = tinggi_halaman - margin_atas -
    // jarak_tetap_dari_bawah, membuat batas bawah tabel otomatis jatuh
    // persis di 2,5cm dari tepi bawah kertas, berapa pun margin
    // atas/bawah yang sedang diatur.
    $tinggiHalamanCm = 29.7;
    $jarakTetapDariBawahCm = 2.5;
    $tinggiKontenCm = $tinggiHalamanCm - (float) $m['atas'] - $jarakTetapDariBawahCm;

    // Ukuran tampil Logo Sekolah di halaman cover: 160px, dikonversi ke
    // cm memakai patokan 96 dpi (dipakai jg sbg width/height eksplisit
    // pada tag img di bawah, BUKAN cuma max-width/max-height, supaya
    // ukuran yg dipakai utk perhitungan tinggi baris tabel PASTI sama
    // dgn ukuran tampil sungguhan - Logo Sekolah selalu persegi krn
    // sudah di-crop 450x450px otomatis saat upload, lihat
    // App\Livewire\ProfilSekolah\Index::simpanLogoSekolah()).
    $logoPx = 160;
    $logoUkuranCm = $logoPx / 96 * 2.54;

    // Supaya logo PERSIS di tengah-tengah HALAMAN KERTAS (bukan cuma di
    // tengah sisa ruang setelah judul seperti versi sebelumnya) - baris
    // judul diberi tinggi TETAP (dihitung, bukan otomatis mengikuti
    // panjang teks judul) sebesar jarak dari batas atas tabel (= garis
    // margin atas) sampai batas atas logo, sehingga titik tengah baris
    // logo (baris ke-2 tabel) jatuh PERSIS di titik tengah kertas
    // (29,7cm dibagi 2), berapa pun panjang teks judul atau margin atas
    // yang sedang diatur. Dibatasi minimal 0 (jaga-jaga kalau margin
    // atas diatur sangat besar).
    $tinggiBarisJudulCm = max(0, ($tinggiHalamanCm / 2) - (float) $m['atas'] - ($logoUkuranCm / 2));
@endphp

<table style="width:100%; height: {{ $tinggiKontenCm }}cm; border-collapse:collapse;">
    <tr>
        <td style="height: {{ $tinggiBarisJudulCm }}cm; text-align:center; vertical-align:top;">
            <div style="font-weight:bold;font-size:13pt;line-height:1.5;text-transform:uppercase;">
                Verifikasi dan Validasi<br>
                Tunjangan Profesi Guru<br>
                {{ $isNegeri ? 'Aparatur Sipil Negara' : 'Non Aparatur Sipil Negara' }}<br>
                Triwulan {{ $triwulan }} Tahun {{ $tahun }}
            </div>
        </td>
    </tr>
    <tr>
        <td style="height: {{ $logoUkuranCm }}cm; text-align:center; vertical-align:middle;">
            @if ($logoSekolahSrc)
                <img src="{{ $logoSekolahSrc }}" style="width:{{ $logoPx }}px;height:{{ $logoPx }}px;">
            @else
                <p style="color:#94a3b8;font-size:9pt;">(Logo Sekolah belum diupload - lengkapi lewat menu Profil Sekolah)</p>
            @endif
        </td>
    </tr>
    <tr>
        <td style="text-align:center; vertical-align:bottom;">
            <div style="font-weight:bold;font-size:13pt;line-height:1.5;">
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
        </td>
    </tr>
</table>
