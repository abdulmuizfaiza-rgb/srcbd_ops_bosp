<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        {{-- Pengaturan Tampilan (huruf & gambar latar) - diatur Superadmin lewat menu Tampilan --}}
        @php($tampilanHalaman = \App\Models\PengaturanTampilan::current())

        <!-- Fonts (hanya jenis huruf yang benar-benar dipakai, supaya halaman lebih cepat) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family={{ $tampilanHalaman->fonts_query }}&display=swap" rel="stylesheet" />

        <style>
            :root {
                --font-halaman: '{{ $tampilanHalaman->jenis_huruf_halaman }}', sans-serif;
                --font-menu: '{{ $tampilanHalaman->jenis_huruf_menu }}', sans-serif;
                --ukuran-halaman: {{ $tampilanHalaman->ukuran_huruf_halaman_px }};
                --ukuran-menu: {{ $tampilanHalaman->ukuran_huruf_menu_px }};
                --warna-huruf-landing: {{ $tampilanHalaman->warna_huruf_landing }};
                --warna-huruf-login: {{ $tampilanHalaman->warna_huruf_login }};
                --warna-huruf-registrasi: {{ $tampilanHalaman->warna_huruf_registrasi }};
                --ukuran-registrasi: {{ $tampilanHalaman->ukuran_huruf_registrasi_px }};
                --font-registrasi: '{{ $tampilanHalaman->jenis_huruf_registrasi }}', sans-serif;
            }
        </style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        {{-- Permintaan user (2026-09-05, lanjutan): kotak landing/login/
             registrasi digeser ke sisi kanan, tengah secara vertikal.
             "items-end" pada flex-col mengatur cross-axis (horizontal),
             jadi menggeser logo+kartu ke tepi kanan container - "justify-
             center" tetap menjaga posisi tengah secara vertikal. Hanya
             berlaku mulai breakpoint sm ke atas supaya di layar sempit
             (mobile) tetap center seperti semula (ruang tidak cukup untuk
             digeser tanpa terpotong). --}}
        <div class="relative min-h-screen flex flex-col sm:justify-center items-center sm:items-end pt-6 sm:pt-0 sm:pr-6 md:pr-16 lg:pr-24 xl:pr-32 overflow-hidden bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900"
            @if ($tampilanHalaman->background_landing_url)
                style="background-image: url('{{ $tampilanHalaman->background_landing_url }}'); background-size: cover; background-position: center;"
            @endif
        >
            {{-- Dekorasi latar belakang animasi --}}
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -top-24 -left-24 h-96 w-96 rounded-full bg-blue-600/30 blur-3xl animate-blob-a"></div>
                <div class="absolute top-1/3 -right-24 h-96 w-96 rounded-full bg-sky-500/20 blur-3xl animate-blob-b"></div>
                <div class="absolute -bottom-32 left-1/3 h-96 w-96 rounded-full bg-indigo-500/20 blur-3xl animate-blob-c"></div>
            </div>

            <div class="relative z-10 animate-fade-in-down">
                <a href="/" wire:navigate class="block">
                    <x-application-logo class="w-16 h-16 fill-current text-blue-300 drop-shadow-lg animate-float-logo" />
                </a>
            </div>

            <div
                x-data="{
                    rotateX: 0,
                    rotateY: 0,
                    tilt(e) {
                        const r = $el.getBoundingClientRect();
                        const px = (e.clientX - r.left) / r.width;
                        const py = (e.clientY - r.top) / r.height;
                        this.rotateY = (px - 0.5) * 10;
                        this.rotateX = (0.5 - py) * 10;
                    },
                    reset() { this.rotateX = 0; this.rotateY = 0; }
                }"
                @mousemove="tilt($event)"
                @mouseleave="reset()"
                {{--
                    PENTING: pakai bentuk OBJECT ({ transform: ... }), BUKAN
                    string ("transform: ..."). Alpine men-set style dalam
                    bentuk string dengan cara menimpa SELURUH isi atribut
                    "style" milik elemen ini (el.style.cssText = ...) - kalau
                    dipakai di sini, background-image dari atribut "style"
                    statis di bawah (kondisi background_login_url) akan ikut
                    KEHAPUS begitu Alpine aktif, walau gambarnya sendiri
                    tersimpan & termuat dengan benar. Bentuk object membuat
                    Alpine hanya mengubah properti "transform" saja lewat
                    style.setProperty(), tanpa menyentuh properti lain
                    (termasuk background-image) yang sudah ada di style
                    attribute yang sama.
                --}}
                :style="{ transform: 'perspective(1200px) rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg)' }"
                class="teks-tebal-akses relative z-10 w-full sm:max-w-md mt-6 px-6 py-6 bg-white/70 backdrop-blur-2xl shadow-2xl ring-1 ring-white/30 overflow-hidden sm:rounded-2xl transition-transform duration-200 ease-out will-change-transform animate-fade-in-up"
                @if ($tampilanHalaman->background_login_url)
                    style="background-image: url('{{ $tampilanHalaman->background_login_url }}'); background-size: cover; background-position: center;"
                @endif
            >
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
