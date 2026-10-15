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

    TATA LETAK (diperbarui 2026-10-15 sore, permintaan user kedua):
    - Blok tulisan Nama Yayasan/Nama Sekolah/Alamat/Tahun SELALU berjarak
      PERSIS 2,5cm dari tepi bawah kertas fisik (A4 = 29,7cm), TIDAK
      terpengaruh berapa pun nilai Margin Bawah yang diatur user lewat
      panel "Setting Margin" - dikonfirmasi lewat AskUserQuestion.
    - Logo diposisikan di tengah SECARA SIMETRIS di antara batas bawah
      blok judul & batas atas blok tulisan bawah tsb.
    - Dicapai TANPA position:absolute/fixed (yang perilakunya beda-beda
      antara dompdf/browser/Word) & TANPA calc() di CSS (dukungan dompdf
      utk calc() tidak konsisten) - dipakai <table> dgn tinggi (height)
      yang DIHITUNG LANGSUNG DI PHP ($tinggiKontenCm di bawah, bukan di
      CSS), lalu baris logo (tengah) diberi vertical-align:middle supaya
      otomatis mengisi & membagi rata sisa ruang vertikal antara baris
      judul (atas, vertical-align:top) & baris tulisan bawah (bawah,
      vertical-align:bottom) - teknik HTML table klasik utk centering
      vertikal yang presisi & konsisten di dompdf/browser/Word, lebih
      bisa diandalkan drpd flexbox (TIDAK didukung dompdf sama sekali)
      maupun position:absolute+calc(). Baris judul & baris bawah dibiarkan
      height:auto (menyesuaikan isi), sehingga SISA tinggi tabel otomatis
      jadi milik baris logo di tengah.
--}}
@php
    // PENTING: digabung jadi SATU blok @php (bukan 2 directive @php
    // terpisah berurutan) - penulisan sebelumnya (@php(...) bentuk
    // inline, langsung disusul @php...@endphp bentuk blok) ternyata
    // salah dikompilasi Blade (jadi PHP rusak, memicu error "Undefined
    // variable $tinggiKontenCm" yang dilaporkan user 2026-10-15 - 2 @php
    // beruntun TIDAK boleh dipakai di partial ini / sebaiknya dihindari
    // secara umum di aplikasi ini).
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
@endphp

<table style="width:100%; height: {{ $tinggiKontenCm }}cm; border-collapse:collapse;">
    <tr>
        <td style="text-align:center; vertical-align:top;">
            <div style="font-weight:bold;font-size:13pt;line-height:1.5;text-transform:uppercase;">
                Verifikasi dan Validasi<br>
                Tunjangan Profesi Guru<br>
                {{ $isNegeri ? 'Aparatur Sipil Negara' : 'Non Aparatur Sipil Negara' }}<br>
                Triwulan {{ $triwulan }} Tahun {{ $tahun }}
            </div>
        </td>
    </tr>
    <tr>
        <td style="text-align:center; vertical-align:middle;">
            @if ($logoSekolahSrc)
                <img src="{{ $logoSekolahSrc }}" style="max-width:160px;max-height:160px;">
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
