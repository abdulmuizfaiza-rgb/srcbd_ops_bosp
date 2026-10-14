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
            sebelumnya hari ini): dibagi 2 panel berdampingan - panel KIRI
            menampilkan gambar (Background Landing Page - "background
            halaman" sesuai istilah user - dengan Background Form Login
            sebagai cadangan kalau Background Landing belum diisi, lalu
            gradasi bawaan kalau keduanya belum diisi), panel KANAN berisi
            form (Login/Registrasi/Verifikasi Akses/Authenticator) dengan
            tingkat transparansi "normal" (bg-white/70, SEBELUM rentetan
            perubahan transparansi hari ini). SISI KIRI/KANAN SEMPAT
            BOLAK-BALIK beberapa kali hari ini - POSISI FINAL dikonfirmasi
            user: "saya ingin kiri landing page, kanan form login/form
            verifikasi". Berlaku untuk SEMUA halaman yang memakai layout
            ini (ditegaskan user: form Verifikasi & form Login, tapi
            layout ini memang dipakai bersama oleh semua halaman auth).

            Panel gambar KIRI disembunyikan di layar sempit/HP (permintaan
            user sebelumnya, tetap dipertahankan) - "hidden sm:block" -
            supaya form tetap lega & full-width di HP.
        --}}
        <div class="min-h-screen flex">
            {{--
                Panel kiri: gambar Background Landing Page. PERBAIKAN
                (2026-10-14, lanjutan lagi): sempat dicoba "bg-contain
                bg-no-repeat" supaya gambar utuh semua terlihat, tapi user
                lalu minta tinggi gambar menutup penuh tinggi panel (tidak
                ada ruang kosong/putih di atas-bawah) - dikembalikan ke
                "bg-cover" (gambar diperbesar proporsional sampai memenuhi
                penuh tinggi & lebar panel; karena gambar lebih landscape
                dibanding proporsi panel, bagian yang terpotong otomatis
                ada di sisi kiri-kanan, BUKAN atas-bawah, dan background
                tetap terpusat lewat bg-center).

                PERBAIKAN (2026-10-14, lanjutan lagi #2): user minta garis
                pembatas antara panel gambar & panel form digeser ke kanan
                ("panjang gambar landing page sampai batas garis form
                login") - panel kiri diganti dari lebar tetap "lg:w-3/5"
                jadi "lg:flex-1" (otomatis mengisi SISA lebar layar),
                sedangkan panel kanan (form) diganti dari "lg:w-2/5" jadi
                lebar TETAP (sekitar lebar kotak form max-w-md 448px +
                padding kiri-kanan px-6) supaya panel form selalu sempit
                mendekati lebar kotak form itu sendiri, di layar selebar
                apa pun. Nilai awalnya "520px", lalu diperkecil lagi ke
                "460px" atas permintaan user "sedikit lagi geser ke
                kanan" (kotak form sendiri otomatis menyusut mengikuti
                lebar panel karena memakai "w-full sm:max-w-md", jadi
                aman walau panel dipersempit).
            --}}
            <div
                class="hidden sm:block sm:w-1/2 lg:w-auto lg:flex-1 bg-cover bg-center bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 order-1"
                @if ($tampilanHalaman->background_landing_url)
                    style="background-image: url('{{ $tampilanHalaman->background_landing_url }}');"
                @elseif ($tampilanHalaman->background_login_url)
                    style="background-image: url('{{ $tampilanHalaman->background_login_url }}');"
                @endif
            ></div>

            {{-- Panel kanan: form --}}
            {{--
                PERBAIKAN (2026-10-14, lanjutan): user ingin panel form
                login/verifikasi JUGA menampilkan gambar "Background Form
                Login" (background_login_url) sebagai latar - sebelumnya
                panel ini polos (bg-slate-50 saja, tanpa gambar). Dipakai
                "bg-cover" di sini (BUKAN bg-contain seperti panel kiri)
                karena gambar ini hanya jadi latar DI BELAKANG kotak form
                (bg-white/70 tetap di atasnya), bukan gambar utama yang
                harus terlihat utuh.
            --}}
            <div
                class="relative w-full sm:w-1/2 lg:w-[460px] lg:flex-shrink-0 flex flex-col items-center justify-center px-6 py-10 bg-slate-50 bg-cover bg-center order-2"
                @if ($tampilanHalaman->background_login_url)
                    style="background-image: url('{{ $tampilanHalaman->background_login_url }}'); --warna-huruf-login: #334155;"
                @else
                    style="--warna-huruf-login: #334155;"
                @endif
                {{--
                    PERBAIKAN (2026-10-14, lanjutan): tulisan di kotak form
                    (judul, label, link) nyaris tak terlihat di panel baru
                    ini - ROOT CAUSE: warna tulisan mengikuti pengaturan
                    Superadmin "Warna Huruf Form Login" (--warna-huruf-
                    login), yang selama ini diatur TERANG supaya kontras
                    dengan gambar GELAP di belakang kotak (desain LAMA).
                    Sekarang kotak selalu duduk di panel TERANG (bg-slate-
                    50), jadi warna terang itu jadi tak terbaca. Di-override
                    di sini ke warna gelap yang aman dibaca, KHUSUS untuk
                    panel form tata-letak baru ini - pengaturan Superadmin
                    "Warna Huruf Form Login" tetap tersimpan apa adanya
                    (tidak diubah/dihapus), hanya efeknya di panel INI yang
                    ditimpa supaya selalu terbaca.
                --}}
            >
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
