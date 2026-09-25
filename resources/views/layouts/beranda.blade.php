<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        {{--
            Layout BARU (round keenam belas, 2026-09-24, bagian 2) khusus
            utk landing page publik (route "/", nama route "beranda") -
            TERPISAH dari layouts/guest.blade.php (dipakai /login &
            /register) krn guest.blade.php membatasi konten ke 1 kartu
            sempit (max-w-md) di sisi kanan layar - tidak cocok utk
            halaman ini yang perlu memuat 2 daftar sekolah + kartu
            ringkasan yang jauh lebih lebar. Gaya visual (gradient gelap +
            animasi blob) SENGAJA dibuat konsisten dgn guest.blade.php &
            dashboard admin (animate-blob-a/b/c, animate-fade-in-up/down,
            animate-float-logo - class CSS yang SUDAH ADA di
            resources/css/app.css sejak round sebelumnya, TIDAK ada
            keyframe baru yang ditambahkan) - sesuai permintaan user
            "gaya animasi dan warna yang modern".

            SENGAJA TIDAK memakai App\Models\PengaturanTampilan (warna
            huruf/background landing-login yang diatur Superadmin lewat
            menu Tampilan) - pengaturan itu didesain khusus utk kartu
            sempit login/registrasi, bukan utk halaman lebar bergaya
            dashboard seperti ini. Tetap memakai jenis huruf (font) global
            yang sama dari PengaturanTampilan supaya tipografi tetap
            konsisten di seluruh aplikasi.
        --}}
        @php($tampilanHalaman = \App\Models\PengaturanTampilan::current())

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family={{ $tampilanHalaman->fonts_query }}&display=swap" rel="stylesheet" />

        <style>
            :root {
                --font-halaman: '{{ $tampilanHalaman->jenis_huruf_halaman }}', sans-serif;
                --ukuran-halaman: {{ $tampilanHalaman->ukuran_huruf_halaman_px }};
            }
        </style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased" style="font-family: var(--font-halaman); font-size: var(--ukuran-halaman);">
        <div class="relative min-h-screen overflow-hidden bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900">
            {{-- Dekorasi latar belakang animasi (sama seperti layouts/guest.blade.php) --}}
            <div class="pointer-events-none fixed inset-0 overflow-hidden">
                <div class="absolute -top-24 -left-24 h-96 w-96 rounded-full bg-blue-600/30 blur-3xl animate-blob-a"></div>
                <div class="absolute top-1/3 -right-24 h-96 w-96 rounded-full bg-sky-500/20 blur-3xl animate-blob-b"></div>
                <div class="absolute -bottom-32 left-1/3 h-96 w-96 rounded-full bg-indigo-500/20 blur-3xl animate-blob-c"></div>
            </div>

            {{-- Bar atas: logo + nama aplikasi (kiri), tombol Masuk (kanan atas) --}}
            <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex items-center justify-between gap-4 animate-fade-in-down">
                <a href="{{ route('beranda') }}" wire:navigate class="flex items-center gap-2.5 min-w-0">
                    <x-application-logo class="w-9 h-9 shrink-0 fill-current text-blue-300 drop-shadow animate-float-logo" />
                    <span class="text-white font-semibold text-sm sm:text-base truncate">Aplikasi OPS_BOSP SR CBD</span>
                </a>

                <a href="{{ route('login') }}" wire:navigate
                    class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2 sm:px-5 sm:py-2.5 rounded-xl bg-white/15 hover:bg-white/25 backdrop-blur-md ring-1 ring-white/30 text-white text-sm font-semibold transition shadow-lg">
                    Masuk
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 010-1.06L10.94 10 7.21 6.29a.75.75 0 111.06-1.06l4.25 4.25a.75.75 0 010 1.06l-4.25 4.25a.75.75 0 01-1.06 0z" clip-rule="evenodd" /></svg>
                </a>
            </div>

            <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
