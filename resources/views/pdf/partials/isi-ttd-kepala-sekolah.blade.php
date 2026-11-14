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
    - $qrGambar: data URI PNG QR Code (dari
      TandaTanganDigitalService::gambarQr()) - dipakai HANYA kalau
      mode=qrcode.
    - $tinggiPx: tinggi ruang kosong yang mau dipertahankan (px), supaya
      tata letak dokumen tidak berubah - DEFAULT 64 (sesuai surat-tpg-ttd
      yang sudah ada).

    Kalau mode=gambar tapi $tandaTanganSrc kosong (sekolah belum upload),
    atau mode=qrcode tapi $qrGambar kosong, SENGAJA jatuh kembali ke
    perilaku 'tanpa' (ruang kosong biasa) - BUKAN error - supaya dokumen
    tetap bisa dicetak normal walau datanya belum lengkap.

    PERBAIKAN TATA LETAK, ALUR NORMAL BUKAN ABSOLUTE (2026-11-14, laporan
    user "hasilnya sangat jelek", cap menimpa tulisan "Kepala Sekolah,"):
    tanda tangan & cap masing-masing taruh di barisnya sendiri (lewat
    <br>, BUKAN position:absolute yg TIDAK konsisten di dompdf), keduanya
    center horizontal lewat text-align:center pada div pembungkus, lalu
    cap ditarik ke ATAS dengan margin-top NEGATIF supaya menumpuk/overlap
    ke bagian bawah tanda tangan.

    PERBAIKAN UKURAN GAMBAR & QR Code DI PDF SUNGGUHAN, BUKAN DI
    PRATINJAU SAJA (2026-11-14, laporan user "posisi dan ukuran nya belum
    benar" utk mode gambar, dan "ketika diklik Cetak/Unduh PDF kosong
    tidak ada" utk mode QR - keduanya DIBUKTIKAN lewat uji dompdf
    LANGSUNG pakai gambar tanda tangan & cap sekolah ASLI milik user,
    bukan tebakan):
    1) Tanda tangan & cap SEBELUMNYA pakai CSS `max-width`/`max-height`
       pada <img> supaya ukurannya otomatis - TERBUKTI dompdf tidak
       menghitung ukuran itu dgn benar SEBELUM tata letak
       text-align:center dihitung, jadi gambar tidak center (nempel kiri)
       dan ukurannya tidak presisi. Diganti ke atribut `width`/`height`
       EKSPLISIT dalam piksel (dihitung di PHP lewat
       TandaTanganDigitalService::ukuranProporsional(), BUKAN CSS) -
       posisi center & ukuran langsung presisi di dompdf.
    2) QR Code SEBELUMNYA markup <svg> inline (dari bacon-qr-code) -
       TERBUKTI dompdf tidak bisa merender <svg> kompleks itu sama sekali
       (walau tampil benar di pratinjau browser biasa - browser & dompdf
       TIDAK selalu berperilaku sama). Diganti ke PNG hasil gambar GD
       murni (TandaTanganDigitalService::gambarQr()), ditempel sebagai
       <img src="data:image/png;base64,..."> - pola yang SUDAH TERBUKTI
       jalan di dompdf utk tanda tangan & cap.
--}}
@php($modeTandaTangan = $modeTandaTangan ?? 'tanpa')
@php($tinggiPx = $tinggiPx ?? 64)
@if ($modeTandaTangan === 'gambar' && ! empty($tandaTanganSrc))
    @php($layananTtd = app(\App\Services\TandaTanganDigitalService::class))
    @php($ukTtd = $layananTtd->ukuranProporsional($tandaTanganSrc, $tinggiPx * 2, (int) round($tinggiPx * 0.85)))
    <div style="height:{{ $tinggiPx }}px;text-align:center;line-height:1;">
        <img src="{{ $tandaTanganSrc }}" width="{{ $ukTtd['width'] }}" height="{{ $ukTtd['height'] }}">
        @if (! empty($capSekolahSrc))
            @php($ukCap = $layananTtd->ukuranProporsional($capSekolahSrc, (int) round($tinggiPx * 1.15), (int) round($tinggiPx * 1.15)))
            @php($tarikKeAtas = (int) round($ukCap['height'] * 0.55))
            <br>
            <img src="{{ $capSekolahSrc }}" width="{{ $ukCap['width'] }}" height="{{ $ukCap['height'] }}" style="opacity:0.8;margin-top:-{{ $tarikKeAtas }}px;">
        @endif
    </div>
@elseif ($modeTandaTangan === 'qrcode' && ! empty($qrGambar))
    {{-- SENGAJA pakai min-height (bukan height tetap) - ukuran tampil QR
         Code (100x100px) boleh lebih tinggi dari $tinggiPx supaya tetap
         mudah dipindai, bukan dipaksa gepeng/kecil. Gambar PNG-nya
         sendiri di-generate di resolusi lebih tinggi (300px, lihat
         TandaTanganDigitalService::gambarQr()) lalu di-skalakan turun ke
         100x100px lewat atribut width/height EKSPLISIT supaya tetap
         tajam saat dicetak. --}}
    <div style="min-height:{{ $tinggiPx }}px;text-align:center;">
        <img src="{{ $qrGambar }}" width="100" height="100">
    </div>
@else
    <div style="height:{{ $tinggiPx }}px;"></div>
@endif
