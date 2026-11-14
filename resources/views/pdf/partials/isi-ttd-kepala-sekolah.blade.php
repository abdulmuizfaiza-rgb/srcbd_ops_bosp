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
--}}
@php($modeTandaTangan = $modeTandaTangan ?? 'tanpa')
@php($tinggiPx = $tinggiPx ?? 64)
@if ($modeTandaTangan === 'gambar' && ! empty($tandaTanganSrc))
    <div style="height:{{ $tinggiPx }}px;position:relative;text-align:center;">
        <img src="{{ $tandaTanganSrc }}" style="max-height:{{ max(20, $tinggiPx - 6) }}px;max-width:150px;">
        @if (! empty($capSekolahSrc))
            <img src="{{ $capSekolahSrc }}" style="position:absolute;top:0;left:50%;margin-left:-34px;width:68px;height:68px;opacity:0.8;">
        @endif
    </div>
@elseif ($modeTandaTangan === 'qrcode' && ! empty($qrSvg))
    <div style="height:{{ $tinggiPx }}px;text-align:center;">
        {!! $qrSvg !!}
    </div>
@else
    <div style="height:{{ $tinggiPx }}px;"></div>
@endif
