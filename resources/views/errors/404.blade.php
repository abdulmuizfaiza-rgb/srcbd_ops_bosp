<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Halaman Tidak Ditemukan | {{ config('app.name', 'OPS_BOSP SR CBD') }}</title>
    {{--
        Audit 2026-10-04: halaman error custom. SENGAJA dibuat mandiri
        (tanpa @vite, tanpa komponen Blade lain, tanpa query database
        seperti PengaturanTampilan::current() yang dipakai layout biasa) -
        supaya halaman ini TETAP bisa tampil walau penyebab errornya
        adalah aset/CSS gagal dimuat atau koneksi database bermasalah
        (khusus relevan untuk 500/503, tapi gaya yang sama dipakai di
        semua halaman error ini supaya konsisten).
    --}}
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #172554 55%, #0f172a 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
            padding: 1.5rem;
        }
        .kartu {
            background: rgba(255, 255, 255, 0.92);
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            padding: 2.5rem 2rem;
            max-width: 26rem;
            width: 100%;
            text-align: center;
        }
        .kode {
            font-size: 3.5rem;
            font-weight: 800;
            color: #1d4ed8;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.75rem;
        }
        p {
            color: #475569;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        a.tombol {
            display: inline-block;
            background: #1d4ed8;
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.65rem 1.5rem;
            border-radius: 0.5rem;
            transition: background 0.15s ease;
        }
        a.tombol:hover { background: #1e40af; }
    </style>
</head>
<body>
    <div class="kartu">
        <div class="kode">404</div>
        <h1>Halaman Tidak Ditemukan</h1>
        <p>Maaf, halaman yang Anda cari tidak tersedia. Mungkin alamatnya salah ketik, atau halamannya sudah dipindahkan.</p>
        <a class="tombol" href="{{ url('/') }}">Kembali ke Beranda</a>
    </div>
</body>
</html>
