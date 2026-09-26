<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

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

            /*
             * Animasi teks berjalan (marquee) untuk pengumuman aktif
             * (permintaan user 2026-09-26) - SENGAJA disalin persis dari
             * resources/views/layouts/app.blade.php (bukan
             * @keyframes/class baru), supaya layout INI (dipakai
             * halaman publik "beranda", TERPISAH dari layouts/app.blade.php
             * yang dipakai halaman setelah login) tidak perlu ikut
             * memuat file itu. Ditaruh inline (bukan
             * resources/css/app.css) dengan alasan yang SAMA seperti di
             * app.blade.php: tidak perlu "npm run build" ulang.
             */
            @keyframes infoTimelineMarquee {
                0%   { transform: translateX(100%); }
                100% { transform: translateX(-100%); }
            }
            .animate-info-timeline-marquee {
                display: inline-block;
                white-space: nowrap;
                animation: infoTimelineMarquee 16s linear infinite;
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

            {{--
                Pengumuman aktif (permintaan user 2026-09-26) - diambil
                LANGSUNG di sini via @php (pola yang sama dgn
                $tampilanHalaman di atas), karena layout ini ada di LUAR
                komponen Livewire Beranda\Index (bar atas ini dipakai di
                semua halaman berlayout "beranda", bukan cuma di dalam
                $slot). Kalau lebih dari satu pengumuman aktif bersamaan
                (tanggal hari ini ada di antara Tanggal Aktif & Tanggal
                Non Aktif masing-masing - lihat
                App\Models\Pengumuman::scopeAktifSaatIni()), SEMUA
                digabung jadi satu teks berjalan yang sama (dipisah "•"),
                bukan cuma yang terbaru - sesuai jawaban AskUserQuestion.
                Klik teks berjalan membuka 1 modal berisi Judul + Isi
                LENGKAP semua pengumuman yang sedang aktif (bukan
                mencoba mendeteksi kata mana yang diklik saat teks
                sedang bergerak - tidak memungkinkan secara teknis).
                Animasi teks berjalan (.animate-info-timeline-marquee)
                memakai ULANG class yang SUDAH ADA di
                resources/views/layouts/app.blade.php (info Timeline
                Pekerjaan), bukan bikin keyframe baru.
            --}}
            @php($pengumumanAktif = \App\Models\Pengumuman::aktifSaatIni()->get())

            {{-- Bar atas: logo + nama aplikasi (kiri), pengumuman berjalan (tengah, kalau ada), tombol Masuk (kanan atas) --}}
            <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex items-center justify-between gap-3 sm:gap-4 animate-fade-in-down">
                <a href="{{ route('beranda') }}" wire:navigate class="flex items-center gap-2.5 min-w-0">
                    <x-application-logo class="w-9 h-9 shrink-0 fill-current text-blue-300 drop-shadow animate-float-logo" />
                    <span class="text-white font-semibold text-sm sm:text-base truncate">Aplikasi OPS_BOSP SR CBD</span>
                </a>

                @if ($pengumumanAktif->isNotEmpty())
                    <div x-data="{ tampilDetailPengumuman: false, timerPengumuman: null }" class="flex-1 min-w-0">
                        <button type="button"
                            @click="tampilDetailPengumuman = true; clearTimeout(timerPengumuman); timerPengumuman = setTimeout(() => (tampilDetailPengumuman = false), 10000)"
                            class="w-full overflow-hidden rounded-lg border border-amber-300/40 bg-gradient-to-r from-amber-400/90 via-orange-400/90 to-rose-400/90 px-3 py-1.5 text-left hover:brightness-110 transition">
                            <span class="animate-info-timeline-marquee inline-block text-xs sm:text-sm font-semibold text-white drop-shadow-sm">
                                @foreach ($pengumumanAktif as $p)
                                    📢 {{ $p->judul }}&nbsp;&nbsp;&nbsp;•&nbsp;&nbsp;&nbsp;
                                @endforeach
                            </span>
                            <span class="block text-center text-[10px] sm:text-[11px] text-white/85 leading-tight mt-0.5">
                                Silahkan Klik Link Informasi nya untuk melihat Detail nya
                            </span>
                        </button>

                        {{-- Modal detail (klik banner pengumuman untuk membuka), auto-tertutup 10 detik
                             atau bisa ditutup manual lewat tombol close / klik area gelap di luar modal.
                             Dibungkus <template x-teleport="body"> supaya saat modal ditampilkan, Alpine
                             memindahkannya ke akhir <body>. Ini WAJIB: bar atas (div dengan class
                             animate-fade-in-down) memakai animasi CSS transform, dan `position: fixed`
                             pada elemen turunan dari elemen yang punya transform jadi terikat ke elemen
                             itu (bukan ke seluruh layar) - itu sebabnya sebelumnya modal muncul terpotong
                             di pojok kiri atas, bukan di tengah halaman. --}}
                        <template x-teleport="body">
                            <div x-show="tampilDetailPengumuman" x-cloak style="display: none; z-index: 80;"
                                class="fixed inset-0 flex items-center justify-center px-4"
                                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                                <div class="fixed inset-0 bg-slate-900/60" @click="tampilDetailPengumuman = false; clearTimeout(timerPengumuman)"></div>

                                <div class="relative w-full max-w-md max-h-[80vh] overflow-y-auto scrollbar-modern rounded-xl bg-white p-6 shadow-2xl">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-semibold text-slate-900">Pengumuman</h3>
                                        <button type="button" @click="tampilDetailPengumuman = false; clearTimeout(timerPengumuman)" class="text-slate-400 hover:text-slate-600">
                                            <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                        </button>
                                    </div>

                                    <div class="space-y-5">
                                        @foreach ($pengumumanAktif as $p)
                                            <div class="{{ ! $loop->last ? 'pb-5 border-b border-slate-100' : '' }}">
                                                <h4 class="font-semibold text-slate-800">{{ $p->judul }}</h4>
                                                <p class="text-xs text-slate-400 mt-0.5">{{ $p->tanggal_aktif->translatedFormat('d F Y') }} - {{ $p->tanggal_nonaktif->translatedFormat('d F Y') }}</p>
                                                <p class="text-sm text-slate-600 mt-2 whitespace-pre-line">{{ $p->isi }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                @endif

                <a href="{{ route('verifikasi-akses') }}" wire:navigate
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
