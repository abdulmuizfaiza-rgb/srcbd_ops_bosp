<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Gerbang verifikasi email + token (diminta user 2026-09-26) - halaman
     * /login ini HANYA bisa dibuka setelah lolos
     * resources/views/livewire/pages/auth/verifikasi-akses.blade.php, yang
     * menandai session 'gerbang_akses_login_user_id' saat token benar.
     * Kalau belum lolos (mis. akses langsung /login tanpa lewat gerbang,
     * atau tab lama), arahkan balik ke gerbang. Logika login username/
     * password DI BAWAH INI SAMA SEKALI TIDAK DIUBAH.
     */
    public function mount(): void
    {
        if (! session()->has('gerbang_akses_login_user_id')) {
            $this->redirect(route('verifikasi-akses'), navigate: true);
        }
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        // Permintaan user 2026-10-04: akun yang baru saja berhasil
        // autentikasi harus SAMA dengan akun yang baru lolos gerbang
        // verifikasi email + token OTP (lihat LoginForm::
        // pastikanSesuaiEmailTerverifikasi()) - kalau tidak sesuai,
        // JANGAN lanjutkan proses redirect/flash normal. SEJAK permintaan
        // user 2026-10-04 (lanjutan): tampilkan pop-up animasi penolakan
        // dulu (lihat blok pop-up "Login Salah" di bawah, properti
        // $tidakSesuaiEmailTerverifikasi sudah true dari authenticate())
        // - redirect ke halaman Verifikasi Akses baru dilakukan user
        // sendiri lewat tombol "Verifikasi Ulang" di pop-up itu (method
        // verifikasiUlang() di bawah), BUKAN otomatis diam-diam seperti
        // sebelumnya.
        if ($this->form->tidakSesuaiEmailTerverifikasi) {
            return;
        }

        // Permintaan user 2026-10-03 (kunci 1 perangkat per akun): kalau
        // authenticate() mendeteksi akun ini (Admin OPS/Admin BOSP) masih
        // aktif di perangkat lain, login DIBATALKAN di dalam authenticate()
        // sendiri (Auth::logout() sudah dipanggil di sana) & pop-up
        // penolakan ditampilkan sebagai gantinya (lihat blok pop-up di
        // bawah) - jangan lanjutkan proses redirect/flash normal.
        if ($this->form->tampilkanPopupPerangkatLain) {
            return;
        }

        // Round 9 Bagian C (permintaan user 2026-09-23, poin 4): tandai
        // "baru saja login" lewat session flash (otomatis tersedia SATU
        // kali saja pada request/kunjungan halaman BERIKUTNYA, lalu hilang
        // sendiri) - dibaca & langsung "dikonsumsi" (session()->pull(),
        // dihapus begitu dibaca) di
        // resources/views/livewire/layout/navigation.blade.php::mount()
        // supaya popup info timeline HANYA muncul 1x tepat setelah login,
        // bukan di setiap kunjungan halaman berikutnya.
        Session::flash('tampilkan_popup_timeline_login', true);

        Session::regenerate();
        session()->forget('google2fa_terverifikasi');

        $this->redirectSetelahLoginKeAlur2fa(Auth::user());
    }

    /**
     * Tombol "Paksa Logout Perangkat Lain" di pop-up penolakan - lihat
     * App\Livewire\Forms\LoginForm::paksaLogoutPerangkatLain() untuk
     * logika lengkap (verifikasi ulang password, akhiri sesi lama, lalu
     * login di perangkat ini). Permintaan user 2026-10-03.
     */
    public function paksaLogoutPerangkatLain(): void
    {
        $this->form->paksaLogoutPerangkatLain();

        if ($this->form->tampilkanPopupPerangkatLain) {
            // Password konfirmasi salah/kosong - pop-up tetap tampil
            // dengan pesan error (lihat $errors->get di Blade), jangan
            // lanjut redirect.
            return;
        }

        Session::flash('tampilkan_popup_timeline_login', true);
        Session::regenerate();
        session()->forget('google2fa_terverifikasi');

        $this->redirectSetelahLoginKeAlur2fa(Auth::user());
    }

    /**
     * Tombol "Batal" di pop-up penolakan - permintaan user 2026-10-03.
     */
    public function batalkanPopupPerangkatLain(): void
    {
        $this->form->batalkanPopupPerangkatLain();
    }

    /**
     * Tombol "Verifikasi Ulang" di pop-up "Login Salah: Akun Tidak
     * Sesuai Verifikasi Email" - permintaan user 2026-10-04 (lanjutan).
     * Gerbang verifikasi lama (session 'gerbang_akses_login_user_id')
     * SUDAH dihapus sejak di LoginForm::pastikanSesuaiEmailTerverifikasi(),
     * jadi di sini cukup arahkan balik ke halaman Verifikasi Akses -
     * mount() di sana akan menampilkan form "Masukkan Email" dari awal.
     */
    public function verifikasiUlang(): void
    {
        Session::flash('status', 'Silakan lakukan verifikasi ulang menggunakan email yang terdaftar saat registrasi, lalu login menggunakan akun yang sesuai dengan email tersebut.');
        $this->redirect(route('verifikasi-akses'), navigate: true);
    }

    /**
     * Login menggunakan kata sandi pemulihan (default) setelah gagal login
     * beberapa kali, lalu diarahkan untuk wajib ganti password.
     */
    public function pemulihan(): void
    {
        $this->form->pemulihan();

        // Permintaan user 2026-10-04: berlaku juga untuk jalur Pemulihan
        // Akun - lihat penjelasan lengkap di method login() di atas
        // (pop-up "Login Salah", bukan redirect otomatis diam-diam).
        if ($this->form->tidakSesuaiEmailTerverifikasi) {
            return;
        }

        Session::regenerate();
        session()->forget('google2fa_terverifikasi');

        $this->redirectSetelahLoginKeAlur2fa(Auth::user());
    }

    /**
     * Google Authenticator (2FA) - permintaan user 2026-09-27. Dipanggil
     * tepat setelah login/pemulihan berhasil (username+password sudah
     * benar) - TIDAK langsung ke dashboard, tapi ke halaman aktivasi
     * (kalau pengguna belum pernah mengaktifkan Authenticator) atau
     * halaman verifikasi kode 6 digit (kalau sudah aktif). URL yang
     * sebenarnya dituju sebelum diarahkan ke /login (kalau ada) tetap
     * tersimpan di session 'url.intended' oleh Laravel, nanti dipakai
     * redirectIntended() di halaman verifikasi/aktivasi setelah 2FA lolos.
     */
    private function redirectSetelahLoginKeAlur2fa(\App\Models\User $user): void
    {
        if (! $user->authenticatorAktif()) {
            $this->redirect(route('authenticator.aktivasi'), navigate: true);

            return;
        }

        $this->redirect(route('authenticator.verifikasi'), navigate: true);
    }
}; ?>

<div x-data="{
    step: 'pilih',
    aksesTerpilih: null,
    /**
     * Permintaan user (2026-09-26, menu Pengguna > tab Riwayat Login):
     * ambil titik koordinat (lat/long) lewat Browser Geolocation API tepat
     * saat user memilih jenis akses (Superadmin/Admin OPS/Admin BOSP) -
     * dipanggil di sini (bukan saat submit form) supaya browser sudah
     * sempat minta izin & dapat posisinya SEBELUM user selesai mengetik
     * username/password. Kalau user menolak izin lokasi, tidak apa-apa -
     * form.latitude/form.longitude tetap kosong (null), baris riwayat
     * login tetap dibuat tanpa koordinat.
     */
    ambilKoordinatLogin() {
        if (! navigator.geolocation) {
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (posisi) => {
                $wire.set('form.latitude', posisi.coords.latitude);
                $wire.set('form.longitude', posisi.coords.longitude);
            },
            () => {
                // Izin ditolak / gagal / timeout - dibiarkan kosong, bukan error.
            },
            { timeout: 8000 }
        );
    },
}">
    {{--
        Pop-up penolakan "Batas Perangkat Terpenuhi" (kunci 1 perangkat
        per akun, khusus Admin OPS/Admin BOSP - permintaan user
        2026-10-03). Gaya visual (gradient card, glow berdenyut, animasi
        scale+opacity) SENGAJA mengikuti pop-up "Info Timeline Pekerjaan"
        yang sudah ada di resources/views/layouts/app.blade.php - supaya
        konsisten dengan kosa-kata animasi pop-up yang sudah dipakai di
        aplikasi ini, hanya warnanya diganti merah/oranye (bukan ungu/pink)
        karena ini pop-up PENOLAKAN, bukan info biasa. <style> ditaruh di
        sini (bukan resources/css/app.css) karena halaman ini memakai
        layouts.guest yang terpisah dari layouts.app - supaya tidak perlu
        npm run build ulang, mengikuti pola yang sama seperti
        animate-info-timeline-popup (juga inline di file layout-nya,
        bukan di app.css).

        PENTING (perbaikan bug, ditemukan 2026-10-04 saat user melaporkan
        pop-up "Login Salah" di bawah tampil terpotong/tidak di tengah):
        kedua pop-up di bawah ini DIBUNGKUS <template x-teleport="body">.
        Kartu login (div pembungkus {{ $slot }} di resources/views/layouts/
        guest.blade.php) punya efek tilt 3D yang memakai `:style="{
        transform: ... }"` + class `will-change-transform` - elemen
        manapun yang py KETURUNAN dari elemen ber-transform akan membuat
        `position: fixed` pada keturunannya itu TERIKAT ke elemen tsb
        (bukan ke seluruh viewport layar) - ditambah `overflow-hidden`
        pada kartu yang sama ikut MEMOTONG pop-up yang coba tampil lebih
        besar dari kartu itu. Hasilnya: pop-up tampil terpotong di area
        kartu login yang kecil, bukan di tengah layar. Solusinya SAMA
        PERSIS seperti yang sudah pernah diterapkan untuk modal
        "Pengumuman" di resources/views/layouts/beranda.blade.php (lihat
        komentar di sana) - `x-teleport="body"` memindahkan pop-up ke
        akhir <body> saat ditampilkan, keluar dari kartu ber-transform
        itu, sehingga `fixed inset-0` benar-benar terikat ke seluruh
        layar. `$wire` tetap bisa dipakai di dalam <template> ini karena
        Alpine mempertahankan context/scope aslinya walau elemennya
        dipindah secara visual.
    --}}
    <style>
        @keyframes popupPerangkatLainGlow {
            0%, 100% { box-shadow: 0 20px 45px -10px rgba(220, 38, 38, 0.55); }
            50%      { box-shadow: 0 20px 55px -8px rgba(234, 88, 12, 0.65); }
        }
        .animate-popup-perangkat-lain {
            animation: popupPerangkatLainGlow 1.8s ease-in-out infinite;
        }

        /* Pop-up "Login Salah" (permintaan user 2026-10-04, lanjutan) -
           kartu kaca gelap + ikon segitiga peringatan amber yang
           berpendar, mengikuti referensi gambar yang diberikan user. */
        @keyframes popupLoginSalahGlow {
            0%, 100% { box-shadow: 0 20px 45px -10px rgba(217, 119, 6, 0.45), 0 0 0 1px rgba(251, 191, 36, 0.15); }
            50%      { box-shadow: 0 20px 55px -8px rgba(251, 146, 60, 0.6), 0 0 0 1px rgba(251, 191, 36, 0.25); }
        }
        .animate-popup-login-salah {
            animation: popupLoginSalahGlow 2s ease-in-out infinite;
        }
        @keyframes popupLoginSalahIkonGlow {
            0%, 100% { opacity: 0.55; transform: scale(1); }
            50%      { opacity: 0.9; transform: scale(1.12); }
        }
        .animate-popup-login-salah-ikon {
            animation: popupLoginSalahIkonGlow 1.8s ease-in-out infinite;
        }
    </style>

    <template x-teleport="body">
        <div x-show="$wire.form.tampilkanPopupPerangkatLain" x-cloak
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4">
            <div
                x-show="$wire.form.tampilkanPopupPerangkatLain"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-75" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90"
                class="animate-popup-perangkat-lain w-full max-w-md rounded-2xl bg-gradient-to-br from-rose-600 via-red-600 to-orange-500 p-6 text-white ring-1 ring-white/20">
                <div class="flex items-start gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white/15 ring-1 ring-white/30 animate-pulse">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="5" y="11" width="14" height="9" rx="2" />
                            <path d="M8 11V7a4 4 0 0 1 8 0v4" />
                            <circle cx="12" cy="15.5" r="1.3" fill="currentColor" stroke="none" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold leading-snug">Akses Anda Ditolak: Batas Perangkat Terpenuhi !</h2>
                        <p class="mt-1.5 text-sm text-white/90 leading-relaxed">Akun Anda terdeteksi sedang aktif di perangkat lain (otomatis Laptop/PC/Tablet/Android). Admin hanya diizinkan login menggunakan 1 perangkat. Silakan logout terlebih dahulu dari perangkat tersebut untuk masuk menggunakan perangkat ini.</p>
                    </div>
                </div>

                <form wire:submit="paksaLogoutPerangkatLain" class="mt-5 border-t border-white/20 pt-4">
                    <label for="password-konfirmasi-paksa" class="text-xs font-medium text-white/90 leading-relaxed">Atau, kalau Anda yakin perangkat lain itu sudah tidak dipakai (misal lupa logout), masukkan kata sandi Anda untuk mengakhiri sesi di perangkat lain & lanjut login di sini:</label>
                    <x-password-input wire:model="form.passwordKonfirmasiPaksa" id="password-konfirmasi-paksa" class="block mt-1.5 w-full !bg-white/10 !border-white/30 !text-white placeholder:!text-white/60" placeholder="Kata sandi Anda" autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('form.passwordKonfirmasiPaksa')" class="mt-1.5 [&>p]:!text-amber-100" />

                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" wire:click="batalkanPopupPerangkatLain" class="rounded-md bg-white/10 px-3 py-1.5 text-xs font-semibold text-white hover:bg-white/20 transition">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="paksaLogoutPerangkatLain" class="rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50 transition">
                            Paksa Logout Perangkat Lain
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    {{--
        Pop-up "Login Salah: Akun Tidak Sesuai Verifikasi Email" -
        permintaan user 2026-10-04 (lanjutan). Didesain ulang 2026-10-04
        (lanjutan lagi) atas permintaan user: tampilan lebih rapi & menarik,
        selalu tepat di tengah layar (lihat catatan x-teleport di atas),
        memakai ikon segitiga peringatan (warning triangle) amber/oranye
        yang berpendar - mengikuti contoh gambar yang diberikan user, beda
        dari kartu gradient merah/oranye pop-up "Batas Perangkat Terpenuhi"
        di atas (kartu kaca gelap + aksen amber, lebih tenang/"warning"
        ketimbang merah penuh/"danger"). Konten disusun rata tengah
        (ikon-judul-isi-tombol) khas gaya dialog peringatan, bukan
        horizontal ikon+teks seperti pop-up di atas. Cuma 1 tombol
        ("Verifikasi Ulang") karena satu-satunya langkah lanjutan yang
        valid dari kondisi ini memang verifikasi ulang (gerbang verifikasi
        lama sudah dihapus dari session begitu ketidaksesuaian ini
        terdeteksi - lihat LoginForm::pastikanSesuaiEmailTerverifikasi()).
    --}}
    <template x-teleport="body">
        <div x-show="$wire.form.tidakSesuaiEmailTerverifikasi" x-cloak
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[80] flex items-center justify-center bg-black/70 backdrop-blur-md px-4">
            <div
                x-show="$wire.form.tidakSesuaiEmailTerverifikasi"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-75" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90"
                class="animate-popup-login-salah relative w-full max-w-sm rounded-2xl bg-slate-900/95 p-7 text-center text-white ring-1 ring-amber-400/30">

                <div class="relative mx-auto flex h-16 w-16 items-center justify-center">
                    <span class="animate-popup-login-salah-ikon absolute inset-0 rounded-full bg-amber-500/40 blur-xl"></span>
                    <svg class="relative h-11 w-11 text-amber-400 drop-shadow-[0_0_6px_rgba(251,191,36,0.65)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3.2 L21.6 20 H2.4 Z" />
                        <line x1="12" y1="9.2" x2="12" y2="13.8" />
                        <circle cx="12" cy="16.6" r="0.95" fill="currentColor" stroke="none" />
                    </svg>
                </div>

                <h2 class="mt-4 text-base font-semibold leading-snug">Login Salah: Akun Tidak Sesuai Verifikasi Email !</h2>
                <p class="mt-2 text-sm text-slate-300 leading-relaxed">Akun yang Anda masukkan tidak sesuai dengan email yang baru saja Anda verifikasi. Silakan lakukan verifikasi ulang menggunakan email yang terdaftar saat registrasi, lalu login menggunakan akun yang sesuai dengan email tersebut.</p>

                <div class="mt-6 border-t border-white/10 pt-4">
                    <button type="button" wire:click="verifikasiUlang" class="w-full rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-900 shadow-[0_0_15px_rgba(251,191,36,0.4)] hover:bg-amber-400 transition">
                        Verifikasi Ulang
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{--
        Permintaan user (2026-09-24, round ketujuh belas): tombol kembali
        ke landing page publik (route "beranda", halaman "/" - lihat
        App\Livewire\Beranda\Index) SAAT SUDAH BERADA di halaman login.
        SENGAJA diletakkan di LUAR kedua blok x-show di bawah (LANGKAH 1
        & LANGKAH 2) supaya SELALU tampil di kedua langkah, bukan cuma
        salah satu - beda dari tombol "Kembali" yang SUDAH ADA di LANGKAH
        2 (itu hanya kembali ke LANGKAH 1 pilihan akses DI DALAM halaman
        /login yang sama, BUKAN ke landing page). Warna teks SENGAJA
        netral (slate, bukan warna_huruf_landing/login yang bisa diatur
        Superadmin lewat menu Tampilan) krn elemen ini tampil di KEDUA
        langkah yang warnanya bisa berbeda.
    --}}
    <div class="mb-4">
        <a href="{{ route('beranda') }}" wire:navigate
            class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 transition">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 010 1.06L9.06 10l3.73 3.71a.75.75 0 11-1.06 1.06l-4.25-4.25a.75.75 0 010-1.06l4.25-4.25a.75.75 0 011.06 0z" clip-rule="evenodd" /></svg>
            Kembali ke Beranda
        </a>
    </div>

    {{-- LANGKAH 1: Landing page pilihan jenis akses --}}
    <div x-show="step === 'pilih'" x-transition.opacity.duration.400ms>
        <div class="mb-6 text-center">
            <h1 class="text-lg font-semibold text-[color:var(--warna-huruf-landing)]">Aplikasi OPS_BOSP SR CBD</h1>
            <p class="text-sm text-[color:var(--warna-huruf-landing)]">Pilih jenis akses Anda untuk melanjutkan</p>
        </div>

        <div class="grid grid-cols-3 gap-3 sm:gap-6 py-2">
            {{--
                Permintaan user (2026-09-05, lanjutan): ketiga ikon diganti
                total jadi ikon orisinal bertema "avatar" terinspirasi
                gambar yang diminta user (Superadmin = sosok berkacamata +
                headphone, Admin OPS = ikon headset customer support, Admin
                BOSP = sosok + laptop) - gambar asli user berwatermark
                pngtree.com (preview stok, bukan file berlisensi bebas
                pakai) jadi TIDAK dipasang langsung, diganti ilustrasi SVG
                orisinal bergaya serupa. Badge lingkaran diubah dari solid
                gradient jadi transparan/glass (bg-white/10 + backdrop-blur)
                dan diberi animasi melayang terus-menerus (class
                "animate-float-logo" yang sudah ada, dipakai bareng logo
                animasi lain di halaman ini) sesuai permintaan "transparan
                melayang" - ping ring lama dihapus supaya tidak menabrak
                efek melayang yang baru.
            --}}
            {{-- Superadmin --}}
            <button type="button" @click="aksesTerpilih = 'Superadmin'; step = 'login'; ambilKoordinatLogin()"
                class="group flex flex-col items-center gap-2 focus:outline-none">
                <span class="relative flex h-16 w-16 sm:h-20 sm:w-20 items-center justify-center animate-float-logo">
                    <span class="relative flex h-full w-full items-center justify-center rounded-full bg-white/10 backdrop-blur-md ring-2 ring-violet-300/70 shadow-lg shadow-indigo-900/30 transition-transform duration-300 group-hover:scale-110">
                        <svg class="h-8 w-8 sm:h-10 sm:w-10 text-violet-100 drop-shadow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12a7 7 0 0 1 14 0" />
                            <rect x="3.3" y="11.3" width="2.8" height="4.4" rx="1.3" fill="currentColor" fill-opacity="0.9" stroke="none" />
                            <rect x="17.9" y="11.3" width="2.8" height="4.4" rx="1.3" fill="currentColor" fill-opacity="0.9" stroke="none" />
                            <circle cx="12" cy="11" r="4.2" />
                            <circle cx="9.7" cy="11" r="1.5" />
                            <circle cx="14.3" cy="11" r="1.5" />
                            <line x1="11.2" y1="11" x2="12.8" y2="11" />
                            <path d="M5.5 21c0-3.6 2.9-5.5 6.5-5.5s6.5 1.9 6.5 5.5" />
                        </svg>
                    </span>
                </span>
                <span class="text-xs sm:text-sm font-semibold text-[color:var(--warna-huruf-landing)] group-hover:text-indigo-700 transition text-center">Superadmin</span>
            </button>

            {{-- Admin OPS --}}
            <button type="button" @click="aksesTerpilih = 'Admin OPS'; step = 'login'; ambilKoordinatLogin()"
                class="group flex flex-col items-center gap-2 focus:outline-none">
                <span class="relative flex h-16 w-16 sm:h-20 sm:w-20 items-center justify-center animate-float-logo" style="animation-delay:.3s">
                    <span class="relative flex h-full w-full items-center justify-center rounded-full bg-white/10 backdrop-blur-md ring-2 ring-sky-300/70 shadow-lg shadow-blue-900/30 transition-transform duration-300 group-hover:scale-110">
                        <svg class="h-8 w-8 sm:h-10 sm:w-10 text-sky-100 drop-shadow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 13v-1a8 8 0 0 1 16 0v1" />
                            <rect x="2.4" y="12.5" width="3.2" height="5" rx="1.4" />
                            <rect x="18.4" y="12.5" width="3.2" height="5" rx="1.4" />
                            <path d="M20 17.5v1a3 3 0 0 1-3 3h-2.2" />
                            <circle cx="13.2" cy="21.3" r="1.1" fill="currentColor" stroke="none" />
                        </svg>
                    </span>
                </span>
                <span class="text-xs sm:text-sm font-semibold text-[color:var(--warna-huruf-landing)] group-hover:text-blue-700 transition text-center">Admin OPS</span>
            </button>

            {{-- Admin BOSP --}}
            <button type="button" @click="aksesTerpilih = 'Admin BOSP'; step = 'login'; ambilKoordinatLogin()"
                class="group flex flex-col items-center gap-2 focus:outline-none">
                <span class="relative flex h-16 w-16 sm:h-20 sm:w-20 items-center justify-center animate-float-logo" style="animation-delay:.6s">
                    <span class="relative flex h-full w-full items-center justify-center rounded-full bg-white/10 backdrop-blur-md ring-2 ring-emerald-300/70 shadow-lg shadow-emerald-900/30 transition-transform duration-300 group-hover:scale-110">
                        <svg class="h-8 w-8 sm:h-10 sm:w-10 text-emerald-100 drop-shadow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="8" r="3.6" />
                            <path d="M15.2 6.5c1.3 .4 1.6 2 .6 3.4" />
                            <path d="M6.5 15.5c0-2.6 2.5-4 5.5-4s5.5 1.4 5.5 4" />
                            <path d="M8.5 16.6v-2.7h7v2.7" />
                            <rect x="7.4" y="16.5" width="9.2" height="1.7" rx="0.7" fill="currentColor" stroke="none" />
                        </svg>
                    </span>
                </span>
                <span class="text-xs sm:text-sm font-semibold text-[color:var(--warna-huruf-landing)] group-hover:text-emerald-700 transition text-center">Admin BOSP</span>
            </button>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('register') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-[color:var(--warna-huruf-landing)] hover:text-slate-700 underline">
                <x-icon name="user-plus" class="w-4 h-4" />
                Registrasi Admin OPS / Admin BOSP
            </a>
        </div>
    </div>

    {{-- LANGKAH 2: Form login --}}
    <div x-show="step === 'login'" x-cloak x-transition.opacity.duration.400ms>
        <button type="button" @click="step = 'pilih'" class="mb-3 inline-flex items-center gap-1 text-sm text-[color:var(--warna-huruf-login)] hover:text-slate-700">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 010 1.06L9.06 10l3.73 3.71a.75.75 0 11-1.06 1.06l-4.25-4.25a.75.75 0 010-1.06l4.25-4.25a.75.75 0 011.06 0z" clip-rule="evenodd" /></svg>
            Kembali
        </button>

        <div class="mb-4 text-center">
            <h1 class="text-lg font-semibold text-[color:var(--warna-huruf-login)]">Aplikasi OPS_BOSP SR CBD</h1>
            <p class="text-sm text-[color:var(--warna-huruf-login)]">
                Masuk sebagai <span class="font-medium text-[color:var(--warna-huruf-login)]" x-text="aksesTerpilih"></span>
            </p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form wire:submit="login">
            <!-- Username -->
            <div>
                <x-input-label for="username" :value="__('Username')" class="!text-[color:var(--warna-huruf-login)]" />
                <x-text-input wire:model="form.username" id="username" class="block mt-1 w-full" type="text" name="username" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('form.username')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-input-label for="password" :value="__('Password')" class="!text-[color:var(--warna-huruf-login)]" />

                <x-password-input wire:model="form.password" id="password" class="block mt-1"
                                name="password"
                                required autocomplete="current-password" />

                <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
            </div>

            <!-- Remember Me -->
            <div class="block mt-4">
                <label for="remember" class="inline-flex items-center">
                    <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500" name="remember">
                    <span class="ms-2 text-sm text-[color:var(--warna-huruf-login)]">{{ __('Remember me') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-primary-button class="ms-3">
                    {{ __('Log in') }}
                </x-primary-button>
            </div>
        </form>

        @if ($form->percobaanGagal >= 2)
            <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                {{--
                    Pemulihan mandiri (kata sandi default) HANYA untuk
                    Superadmin (2026-09-05) - Admin OPS/Admin BOSP diarahkan
                    minta reset password ke Superadmin sebagai gantinya.
                    Dipakai <template x-if> (bukan cuma x-show) supaya
                    tombol "Pemulihan Akun" beserta wire:click-nya SAMA
                    SEKALI tidak ada di DOM saat level yang dipilih bukan
                    Superadmin - bukan cuma disembunyikan lewat CSS.
                --}}
                <template x-if="aksesTerpilih === 'Superadmin'">
                    <div>
                        <p>Gagal login beberapa kali? Gunakan pemulihan akun dengan username sekolah/akun Anda dan kata sandi pemulihan default sesuai level akses Anda (isi di kolom Password), lalu klik tombol di bawah ini.</p>
                        <button type="button" wire:click="pemulihan" class="mt-2 inline-flex items-center px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-md transition">
                            Pemulihan Akun
                        </button>
                    </div>
                </template>
                <template x-if="aksesTerpilih !== 'Superadmin'">
                    <p>Lupa password? Pemulihan akun mandiri sudah tidak tersedia untuk Admin OPS/Admin BOSP. Silakan hubungi Superadmin sekolah/dinas Anda untuk me-reset password akun ini - password baru akan dikirim ke email yang terdaftar.</p>
                </template>
            </div>
        @endif
    </div>
</div>
