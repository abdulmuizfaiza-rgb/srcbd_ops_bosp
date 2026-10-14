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
        {{--
            PEROMBAKAN TATA LETAK (permintaan user 2026-10-14, menggantikan
            pendekatan "kotak transparan di atas gambar" yang dipakai
            sebelumnya hari ini): sekarang dibagi 2 panel berdampingan -
            panel KIRI menampilkan gambar (Background Form Login, dengan
            Background Landing sebagai cadangan kalau belum diisi, lalu
            gradasi bawaan kalau keduanya belum diisi), panel KANAN berisi
            form (Login/Registrasi/Verifikasi Akses/Authenticator) dengan
            tingkat transparansi "normal" (bg-white/70, SEBELUM rentetan
            perubahan transparansi hari ini). Berlaku untuk SEMUA halaman
            yang memakai layout ini (ditegaskan user: form Verifikasi &
            form Login, tapi layout ini memang dipakai bersama oleh semua
            halaman auth).

            Panel gambar KIRI disembunyikan di layar sempit/HP (permintaan
            user) - "hidden sm:block" - supaya form tetap lega & full-width
            di HP.
        --}}
        <div class="min-h-screen flex">
            {{-- Panel kiri: gambar --}}
            <div
                class="hidden sm:block sm:w-1/2 lg:w-3/5 bg-cover bg-center bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900"
                @if ($tampilanHalaman->background_login_url)
                    style="background-image: url('{{ $tampilanHalaman->background_login_url }}');"
                @elseif ($tampilanHalaman->background_landing_url)
                    style="background-image: url('{{ $tampilanHalaman->background_landing_url }}');"
                @endif
            ></div>

            {{-- Panel kanan: form --}}
            <div class="relative w-full sm:w-1/2 lg:w-2/5 flex flex-col items-center justify-center px-6 py-10 bg-slate-50">
                <div class="mb-6">
                    <a href="/" wire:navigate class="block">
                        <x-application-logo class="w-16 h-16 fill-current text-blue-600" />
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
                    :style="{ transform: 'perspective(1200px) rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg)' }"
                    class="teks-tebal-akses w-full sm:max-w-md px-6 py-6 bg-white/70 backdrop-blur-2xl shadow-2xl ring-1 ring-white/30 overflow-hidden rounded-2xl transition-transform duration-200 ease-out will-change-transform"
                >
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
