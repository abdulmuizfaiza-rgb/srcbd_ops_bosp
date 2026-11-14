{{--
    Partial BERSAMA untuk isi sel/ruang tanda tangan Kepala Sekolah di
    SEMUA dokumen PDF yang punya tanda tangan Kepala Sekolah (Fase 2
    fitur Tanda Tangan Kepala Sekolah & Cap Sekolah, permintaan user
    2026-11-14: "buatkan pilihan tanpa tanda tangan kepala sekolah dan
    cap sekolah, dengan tanda tangan kepala sekolah dan cap sekolah,
    dengan tanda tangan kepala sekolah dan cap sekolah berupa QRCode").

    SENGAJA dibuat sebagai partial KECIL yang hanya mengisi RUANG KOSONG
    tanda tangan (bukan partial tabel penuh) supaya bisa ditempel di
    berbagai macam struktur tabel tanda tangan yang sudah ada di
    masing-masing dokumen (surat-tpg-ttd sudah pakai <div height>,
    formulir-bos-k7b/c & pajak-bosp-reguler & unduhan-lampiran pakai
    baris tabel `.ruang-ttd`) tanpa mengubah struktur tabel aslinya -
    cukup @include di tempat ruang kosongnya berada.

    Variabel:
    - $modeTandaTangan: 'tanpa' (default/fallback) | 'gambar' | 'qrcode'.
    - $tandaTanganSrc: data URI PNG dari ProfilSekolah::
      tandaTanganKepalaSekolahDataUri() - dipakai HANYA kalau mode=gambar.
    - $capSekolahSrc: data URI PNG dari ProfilSekolah::capSekolahDataUri()
      - ditumpuk di atas tanda tangan (opacity turun sedikit supaya tanda
        tangan tetap kebaca, meniru cara cap fisik biasa dibubuhkan
        menimpa sebagian tanda tangan). HANYA dipakai kalau mode=gambar.
    - $qrSvg: markup SVG mentah (dari TandaTanganDigitalService::svgQr)
      - dipakai HANYA kalau mode=qrcode.
    - $tinggiPx: tinggi ruang kosong yang mau dipertahankan (px), supaya
      tata letak dokumen tidak berubah - DEFAULT 64 (sesuai surat-tpg-ttd
      yang sudah ada).

    Kalau mode=gambar tapi $tandaTanganSrc kosong (sekolah belum upload),
    atau mode=qrcode tapi $qrSvg kosong, SENGAJA jatuh kembali ke
    perilaku 'tanpa' (ruang kosong biasa) - BUKAN error - supaya dokumen
    tetap bisa dicetak normal walau datanya belum lengkap.

    PERBAIKAN TATA LETAK (2026-11-14, laporan user "hasilnya sangat jelek"
    disertai screenshot cap menimpa tulisan "Kepala Sekolah," di ATAS
    ruang tanda tangan, tidak simetris): versi SEBELUMNYA menumpuk cap di
    atas tanda tangan pakai `position:absolute` + `margin-left` negatif -
    dompdf TIDAK konsisten merender `position:absolute`, jadi hasilnya
    melenceng jauh dari posisi seharusnya (menimpa teks label di ATAS
    kotak, bukan di tengah tanda tangan). Diganti total ke pendekatan
    ALUR NORMAL (bukan absolute): tanda tangan & cap masing-masing taruh
    di barisnya sendiri (lewat <br>, BUKAN posisi absolut), keduanya
    center horizontal lewat text-align:center pada div pembungkus (sudah
    terbukti bekerja benar di dompdf di seluruh aplikasi ini), lalu cap
    ditarik ke ATAS dengan margin-top NEGATIF (bukan position:absolute)
    supaya menumpuk/overlap ke bagian BAWAH tanda tangan - teknik overlap
    pakai margin negatif ini JAUH lebih konsisten di dompdf dibanding
    position:absolute. Ukuran tanda tangan & cap dihitung PROPORSIONAL
    terhadap $tinggiPx (bukan angka tetap) supaya pas & rapi di kotak
    manapun (64px di surat-tpg-ttd, 56px di tabel 3-kolom lembar
    validasi/rekap cair) - TIDAK ADA LAGI yang menimpa baris di atasnya.
--}}
@php($modeTandaTangan = $modeTandaTangan ?? 'tanpa')
@php($tinggiPx = $tinggiPx ?? 64)
@if ($modeTandaTangan === 'gambar' && ! empty($tandaTanganSrc))
    @php($ukuranTtd = (int) round($tinggiPx * 0.5))
    @php($ukuranCap = (int) round($tinggiPx * 0.72))
    @php($tarikKeAtas = (int) round($ukuranCap * 0.5))
    <div style="height:{{ $tinggiPx }}px;text-align:center;line-height:1;">
        <img src="{{ $tandaTanganSrc }}" style="max-height:{{ $ukuranTtd }}px;max-width:{{ $tinggiPx * 2 }}px;">
        @if (! empty($capSekolahSrc))
            <br>
            <img src="{{ $capSekolahSrc }}" style="width:{{ $ukuranCap }}px;height:{{ $ukuranCap }}px;opacity:0.75;margin-top:-{{ $tarikKeAtas }}px;">
        @endif
    </div>
@elseif ($modeTandaTangan === 'qrcode' && ! empty($qrSvg))
    {{-- SENGAJA pakai min-height (bukan height tetap) - ukuran QR Code
         dari TandaTanganDigitalService (default 100px) boleh lebih
         tinggi dari $tinggiPx supaya tetap mudah dipindai, bukan
         dipaksa gepeng/kecil. --}}
    <div style="min-height:{{ $tinggiPx }}px;text-align:center;">
        {!! $qrSvg !!}
    </div>
@else
    <div style="height:{{ $tinggiPx }}px;"></div>
@endif
