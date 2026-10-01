<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

        {{-- Pengaturan Tampilan (warna & huruf) - diatur Superadmin lewat menu Tampilan --}}
        @php($tampilanHalaman = \App\Models\PengaturanTampilan::current())

        {{--
            Round 9 Bagian C (permintaan user 2026-09-23, poin 4): ringkasan
            info timeline untuk popup+marquee saat Admin OPS/Admin BOSP baru
            saja login - dihitung SEKALI di sini (server-side, langsung saat
            layout ini dirender), lalu di-seed langsung ke x-data Alpine di
            bawah. Seluruh logikanya (kenapa dihitung server-side bukan
            lewat event Livewire, kenapa ditulis 1 baris pendek di sini,
            kapan popup dilewati total) didokumentasikan LENGKAP di
            docblock method
            App\Models\DeadlinePekerjaan::ringkasanPopupLoginUntukUserSaatIni()
            - sengaja TIDAK diulang panjang di sini (file Blade) supaya
            komentar ini tidak memuat kata kunci sintaks Blade yang bisa
            salah ditafsirkan compiler-nya sendiri.
        --}}
        @php($ringkasanPopupTimelineLogin = \App\Models\DeadlinePekerjaan::ringkasanPopupLoginUntukUserSaatIni())

        <!-- Fonts (hanya jenis huruf yang benar-benar dipakai, supaya halaman lebih cepat) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family={{ $tampilanHalaman->fonts_query }}&display=swap" rel="stylesheet" />

        <style>
            :root {
                --font-halaman: '{{ $tampilanHalaman->jenis_huruf_halaman }}', sans-serif;
                --font-menu: '{{ $tampilanHalaman->jenis_huruf_menu }}', sans-serif;
                --ukuran-halaman: {{ $tampilanHalaman->ukuran_huruf_halaman_px }};
                --ukuran-menu: {{ $tampilanHalaman->ukuran_huruf_menu_px }};
                --warna-huruf-menu: {{ $tampilanHalaman->warna_huruf_menu }};
            }

            /*
             * Round 9 Bagian C (permintaan user 2026-09-23, poin 4) - popup
             * info timeline saat login + running text (marquee) di atas
             * halaman. Ditaruh inline di sini (bukan resources/css/app.css)
             * supaya TIDAK perlu "npm run build" ulang saat update ini
             * dipasang - cukup salin file Blade seperti update-update
             * sebelumnya.
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
            @keyframes infoTimelinePopupGlow {
                0%, 100% { box-shadow: 0 20px 45px -10px rgba(99, 33, 200, 0.55); }
                50%      { box-shadow: 0 20px 55px -8px rgba(219, 39, 119, 0.65); }
            }
            .animate-info-timeline-popup {
                animation: infoTimelinePopupGlow 1.8s ease-in-out infinite;
            }
        </style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen" style="background-color: {{ $tampilanHalaman->warna_halaman }}">
            <livewire:layout.navigation />

            {{--
                Popup info timeline saat login (5 detik, tengah layar,
                animasi & warna menarik) lalu berubah jadi running text/
                marquee terus-menerus di atas halaman (Round 9 Bagian C,
                permintaan user 2026-09-23 poin 4). Ditaruh di layout
                bersama ini (bukan di komponen navigasi/sidebar) supaya
                muncul di SEMUA halaman berlayout "app", persis di atas
                area konten (dekat judul menu), terlepas dari halaman mana
                yang pertama dibuka setelah login.

                Ringkasan ($ringkasanPopupTimelineLogin, NULL kalau popup
                harus dilewati) sudah dihitung server-side di blok PHP di
                bagian <head> atas - lihat penjelasan lengkap di sana
                (termasuk kenapa TIDAK dipakai pendekatan event Livewire +
                pendengar JS sisi client, karena race condition timing
                saat halaman pertama kali dimuat). x-data di-seed
                LANGSUNG dari nilai server (fungsi bantu "js" Blade),
                x-init hanya memulai
                timer 5 detiknya kalau memang ada yang ditampilkan.

                Jawaban AskUserQuestion terkait:
                - "Isi info" -> "Ringkasan jumlah + yang paling mendesak"
                - "Kondisi kosong" -> "Dilewati saja"
                - "Perilaku marquee" -> "Berputar terus (looping)"
            --}}
            <div x-data="{
                    tampilPopup: {{ $ringkasanPopupTimelineLogin ? 'true' : 'false' }},
                    tampilMarquee: false,
                    ringkasan: @js($ringkasanPopupTimelineLogin),
                 }"
                 x-init="if (tampilPopup) { setTimeout(() => { tampilPopup = false; tampilMarquee = true; }, 5000); }">
                {{-- Popup tengah layar (5 detik pertama) --}}
                <div x-show="tampilPopup" style="display: none;"
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-75" x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90"
                     class="fixed inset-x-0 top-24 z-[70] flex justify-center px-4 pointer-events-none">
                    <div class="animate-info-timeline-popup pointer-events-auto max-w-sm rounded-2xl bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 px-5 py-4 text-white ring-1 ring-white/20">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-white/20 animate-pulse">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 7v5l3 3" />
                                </svg>
                            </span>
                            <div class="text-sm leading-snug">
                                <p class="font-semibold">Info Timeline Pekerjaan</p>
                                <template x-if="ringkasan">
                                    <p class="mt-1 text-white/90">
                                        <span x-text="ringkasan.jumlah"></span> tahap kerja sudah lewat deadline tahun ini.
                                        Paling mendesak: <span class="font-semibold" x-text="ringkasan.labelPalingMendesak"></span>
                                        (Triwulan <span x-text="ringkasan.triwulanPalingMendesak"></span>) -
                                        lewat sejak <span x-text="ringkasan.tanggalPalingMendesak"></span>.
                                    </p>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Running text/marquee (setelah 5 detik, berputar terus) --}}
                <div x-show="tampilMarquee" style="display: none;" x-cloak
                     class="lg:ml-64 overflow-hidden border-b border-amber-300 bg-gradient-to-r from-amber-400 via-orange-400 to-rose-400 py-1.5">
                    <template x-if="ringkasan">
                        <span class="animate-info-timeline-marquee text-sm font-semibold text-white drop-shadow-sm">
                            ⏰ Info Timeline Pekerjaan — <span x-text="ringkasan.jumlah"></span> tahap kerja sudah lewat deadline tahun ini.
                            Paling mendesak: <span x-text="ringkasan.labelPalingMendesak"></span>
                            (Triwulan <span x-text="ringkasan.triwulanPalingMendesak"></span>),
                            lewat sejak <span x-text="ringkasan.tanggalPalingMendesak"></span>.
                        </span>
                    </template>
                </div>
            </div>

            <div class="lg:ml-64">
                <!-- Page Heading -->
                @if (isset($header))
                    <header class="bg-white border-b border-slate-200 shadow-sm">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>

            {{--
                ============ POP-UP PENGINGAT "KESEHATAN" & "WAKTU SALAT" ============
                Permintaan user 2026-10-01. Muncul di SEMUA halaman berlayout
                "app" (semua akun yang sudah login - Superadmin, Admin OPS,
                Admin BOSP), jawaban AskUserQuestion "Semua halaman, semua
                akun yang sudah login". Ditaruh di layout bersama ini (pola
                sama seperti popup info timeline login di atas) supaya
                otomatis ikut muncul di halaman manapun yang pertama dibuka.

                Jadwal (jam WIB, format 24 jam, jawaban AskUserQuestion
                2026-10-01 - jam TETAP setiap hari, BUKAN jadwal salat
                akurat sesuai lokasi/tanggal):
                - 06:00 & 09:00 -> pop-up "Kesehatan" saja.
                - 12:00 -> pop-up "Kesehatan" dulu, SETELAH ditutup baru
                  muncul pop-up "Waktu Salat Dzuhur" (permintaan user:
                  "setelah itu muncul kalimat ini...").
                - 15:00 -> pop-up "Kesehatan" dulu, SETELAH ditutup baru
                  muncul pop-up "Waktu Salat Ashar".
                - 18:00 -> pop-up "Waktu Salat Maghrib" saja (tidak ada di
                  daftar jam pop-up Kesehatan yang diminta user).

                Dicek setiap 20 detik, TAPI hanya diproses dalam 5 menit
                pertama tiap jam patokan (supaya tidak "telat tampil"
                berjam-jam kalau pengguna baru membuka aplikasi beberapa
                menit setelah jam patokan, tapi juga tidak tiba-tiba muncul
                di luar jam yang dimaksud). Ditandai per slot+tanggal di
                localStorage browser supaya TIDAK muncul berkali-kali di
                hari yang sama (kalau localStorage dibersihkan atau dibuka
                dari perangkat/browser lain, pop-up bisa muncul lagi -
                wajar, bukan bug, karena pengingat ini murni sisi
                client/browser, tidak disimpan di database).
            --}}
            <div
                x-data="{
                    tampil: false,
                    jenis: null,
                    judul: '',
                    pesan: '',
                    emoji: '',
                    antrian: null,
                    kunciSudahTampil(kunci) {
                        try { return localStorage.getItem(kunci) === this.hariIni(); } catch (e) { return false; }
                    },
                    tandaiSudahTampil(kunci) {
                        try { localStorage.setItem(kunci, this.hariIni()); } catch (e) {}
                    },
                    hariIni() {
                        const d = new Date();
                        return d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate();
                    },
                    cekWaktu() {
                        if (this.tampil || this.antrian) { return; }
                        const sekarang = new Date();
                        const jam = sekarang.getHours();
                        if (sekarang.getMinutes() >= 5) { return; }

                        const salatPerJam = { 12: 'Dzuhur', 15: 'Ashar', 18: 'Maghrib' };

                        if ([6, 9, 12, 15].includes(jam)) {
                            const kunci = 'srcbd_pop_kesehatan_' + jam;
                            if (! this.kunciSudahTampil(kunci)) {
                                this.tandaiSudahTampil(kunci);
                                if (salatPerJam[jam]) {
                                    this.antrian = { label: salatPerJam[jam], kunci: 'srcbd_pop_salat_' + jam };
                                }
                                this.tampilkan('kesehatan');
                                return;
                            }
                        }

                        if (jam === 18) {
                            const kunci = 'srcbd_pop_salat_18';
                            if (! this.kunciSudahTampil(kunci)) {
                                this.tandaiSudahTampil(kunci);
                                this.tampilkan('salat', salatPerJam[18]);
                            }
                        }
                    },
                    tampilkan(jenis, labelSalat) {
                        this.jenis = jenis;
                        if (jenis === 'kesehatan') {
                            this.emoji = '🔋';
                            this.judul = 'Waktunya Istirahat Sejenak';
                            this.pesan = 'Ingat, bahagiamu butuh di-recharge juga! 🔋 Pekerjaan ini tidak akan lari dikejar, tapi kesehatanmu bisa berkurang. Istirahat dulu, yuk!';
                        } else {
                            this.emoji = '🕌';
                            this.judul = 'Waktunya Salat ' + labelSalat;
                            this.pesan = 'Kerjaannya dipending dulu yuk, panggilan Allah diutamakan. ✨ Waktunya Salat ' + labelSalat + '. Tenangkan hati sejenak di sajadah.';
                        }
                        this.tampil = true;
                    },
                    tutup() {
                        this.tampil = false;
                        if (this.antrian) {
                            const antrianBerikutnya = this.antrian;
                            this.antrian = null;
                            setTimeout(() => {
                                if (! this.kunciSudahTampil(antrianBerikutnya.kunci)) {
                                    this.tandaiSudahTampil(antrianBerikutnya.kunci);
                                    this.tampilkan('salat', antrianBerikutnya.label);
                                }
                            }, 700);
                        }
                    },
                }"
                x-init="cekWaktu(); setInterval(() => cekWaktu(), 20000)"
            >
                <div x-show="tampil" style="display: none;" x-cloak
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4">
                    <div x-show="tampil"
                         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-75 -translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90"
                         class="relative w-full sm:max-w-md overflow-hidden rounded-2xl shadow-2xl ring-1 ring-white/10"
                         :class="jenis === 'salat' ? 'bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-600' : 'bg-gradient-to-br from-amber-400 via-orange-500 to-rose-500'">
                        <div class="pointer-events-none absolute -top-8 -right-8 w-40 h-40 rounded-full bg-white/10 blur-2xl animate-blob-a"></div>
                        <div class="pointer-events-none absolute -bottom-10 -left-10 w-40 h-40 rounded-full bg-white/10 blur-2xl animate-blob-b"></div>

                        <div class="relative px-6 py-8 text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/20 text-4xl animate-bounce" x-text="emoji"></div>
                            <h3 class="mt-4 text-lg font-bold text-white" x-text="judul"></h3>
                            <p class="mt-2 text-sm text-white/90 leading-relaxed" x-text="pesan"></p>
                            <button type="button" x-on:click="tutup()"
                                    class="mt-6 inline-flex items-center justify-center rounded-full bg-white px-5 py-2 text-sm font-semibold shadow-sm hover:bg-white/90 transition"
                                    :class="jenis === 'salat' ? 'text-emerald-700' : 'text-orange-700'">
                                <span x-text="jenis === 'salat' ? 'Baik, Saya Salat Dulu 🙏' : 'Baik, Istirahat Sebentar'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
