<?php

namespace App\Livewire\Forms;

use App\Models\FailedLoginAttempt;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string')]
    public string $username = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Titik koordinat (lat/long) saat login - diisi dari Browser
     * Geolocation API di halaman login (lihat login.blade.php, dipicu
     * saat user memilih jenis akses, SEBELUM username/password dikirim
     * - jadi sudah tersedia untuk percobaan yang BERHASIL maupun GAGAL).
     * Permintaan user (2026-09-26): menu Pengguna > tab Riwayat Login
     * menampilkan kolom ini untuk login yang berhasil. SEJAK permintaan
     * user 2026-10-03, nilai yang sama juga dikirim ke
     * FailedLoginAttempt::catat() untuk percobaan yang GAGAL (menu "Cek
     * Database dan Aplikasi" > tab "Percobaan Login Gagal"). Kalau user
     * menolak izin lokasi browser, kedua properti ini tetap NULL - baris
     * riwayat/percobaan login tetap dibuat, hanya kolom koordinatnya
     * kosong.
     */
    #[Validate('nullable|numeric')]
    public ?float $latitude = null;

    #[Validate('nullable|numeric')]
    public ?float $longitude = null;

    /**
     * Jumlah percobaan login gagal secara beruntun pada form ini.
     * Setelah 2x gagal, tombol "Pemulihan Akun" ditampilkan.
     */
    public int $percobaanGagal = 0;

    /**
     * Permintaan user 2026-10-03: kunci 1 perangkat per akun, KHUSUS
     * Admin OPS & Admin BOSP (Superadmin dikecualikan). Kedua properti
     * di bawah mengatur pop-up penolakan "Akses Anda Ditolak: Batas
     * Perangkat Terpenuhi !" di login.blade.php - lihat authenticate()
     * & cariSesiLainAktif() untuk logika lengkapnya, dan
     * paksaLogoutPerangkatLain() untuk jalur "Paksa Logout Perangkat
     * Lain" di pop-up tsb.
     */
    public bool $tampilkanPopupPerangkatLain = false;

    #[Validate('nullable|string')]
    public string $passwordKonfirmasiPaksa = '';

    /**
     * Kata sandi pemulihan (default) per level akses.
     *
     * SENGAJA HANYA Superadmin (2026-09-05) - jalur pemulihan mandiri
     * untuk Admin OPS/Admin BOSP dinonaktifkan TOTAL (bukan cuma
     * disembunyikan dari tampilan login): satu-satunya cara akun kedua
     * level ini reset password sekarang lewat Superadmin (menu Kelola
     * Pengguna, tombol "Reset Password" - lihat Pengguna\Index). Kalau
     * dulu ada yang tahu kata sandi pemulihan default lama ('adminops'/
     * 'adminbosp'), sekarang sudah tidak bisa dipakai sama sekali lagi.
     *
     * @return array<string, string>
     */
    public static function kataSandiPemulihan(): array
    {
        return [
            User::LEVEL_SUPERADMIN => 'superadmin',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $akun = User::where('username', $this->username)->first();

        if ($akun && ! $akun->is_approved) {
            throw ValidationException::withMessages([
                'form.username' => 'Akun Anda masih menunggu persetujuan Superadmin dan belum bisa digunakan untuk login.',
            ]);
        }

        if (! Auth::attempt($this->only(['username', 'password']), $this->remember)) {
            RateLimiter::hit($this->throttleKey());
            $this->percobaanGagal++;

            FailedLoginAttempt::catat($this->username, request()->ip(), request()->userAgent(), $this->latitude, $this->longitude);

            throw ValidationException::withMessages([
                'form.username' => trans('auth.failed'),
            ]);
        }

        $user = Auth::user();

        // Permintaan user 2026-10-03: kunci 1 perangkat per akun, KHUSUS
        // Admin OPS & Admin BOSP (Superadmin dikecualikan - bisa login
        // dari beberapa perangkat sekaligus seperti sebelumnya).
        // Kredensial SUDAH benar di titik ini (Auth::attempt sudah lolos)
        // - pengecekan device-lock SENGAJA dilakukan SESUDAH ini (bukan
        // sebelum Auth::attempt), supaya orang yang belum tahu password
        // yang benar tidak bisa "mengintip" apakah akun ini sedang aktif
        // di perangkat lain atau tidak.
        if (in_array($user->level_akses, [User::LEVEL_ADMIN_OPS, User::LEVEL_ADMIN_BOSP], true)) {
            $sesiLain = $this->cariSesiLainAktif($user);

            if ($sesiLain) {
                // Batalkan login yang baru saja berhasil - JANGAN
                // lanjutkan, tampilkan pop-up penolakan sebagai gantinya
                // (lihat login.blade.php). Auth::logout() di sini WAJIB
                // (bukan kosmetik) - supaya state Auth bersih lagi saat
                // user klik "Paksa Logout Perangkat Lain" (method
                // paksaLogoutPerangkatLain() di bawah re-verifikasi
                // password dari awal, tidak mengandalkan sesi Auth yang
                // baru saja terbentuk ini).
                Auth::logout();
                $this->tampilkanPopupPerangkatLain = true;

                return;
            }
        }

        RateLimiter::clear($this->throttleKey());
        $this->percobaanGagal = 0;

        $this->catatRiwayatLogin($user);
    }

    /**
     * Mengakhiri sesi lain yang masih aktif (setelah user mengonfirmasi
     * ulang password-nya lewat tombol "Paksa Logout Perangkat Lain" di
     * pop-up penolakan - permintaan user 2026-10-03) lalu melanjutkan
     * login di perangkat/browser ini. Password diminta ULANG di sini
     * (bukan mengandalkan Auth::attempt yang sudah lolos sebelumnya di
     * authenticate()) karena authenticate() SUDAH memanggil Auth::logout()
     * begitu konflik terdeteksi - state Auth saat method ini dipanggil
     * sudah bukan "sudah login" lagi, jadi verifikasi ulang memang perlu
     * dilakukan dari awal, bukan sekadar pengulangan demi keamanan.
     */
    public function paksaLogoutPerangkatLain(): void
    {
        $akun = User::where('username', $this->username)->first();

        if (trim($this->passwordKonfirmasiPaksa) === '') {
            $this->addError('passwordKonfirmasiPaksa', 'Kata sandi wajib diisi.');

            return;
        }

        if (! $akun || ! Hash::check($this->passwordKonfirmasiPaksa, $akun->password)) {
            $this->addError('passwordKonfirmasiPaksa', 'Kata sandi tidak sesuai.');

            return;
        }

        $sesiLain = $this->cariSesiLainAktif($akun);

        if ($sesiLain) {
            $sesiLain->update(['logout_at' => now()]);
        }

        Auth::login($akun, $this->remember);

        RateLimiter::clear($this->throttleKey());
        $this->percobaanGagal = 0;
        $this->tampilkanPopupPerangkatLain = false;
        $this->passwordKonfirmasiPaksa = '';

        $this->catatRiwayatLogin($akun);
    }

    /**
     * Membatalkan pop-up penolakan "Batas Perangkat Terpenuhi" (tombol
     * "Batal" - permintaan user 2026-10-03) - user tetap di halaman
     * login, bisa mencoba akun lain atau mencoba lagi nanti.
     */
    public function batalkanPopupPerangkatLain(): void
    {
        $this->tampilkanPopupPerangkatLain = false;
        $this->passwordKonfirmasiPaksa = '';
    }

    /**
     * Cari baris LoginHistory LAIN yang MASIH BENAR-BENAR AKTIF untuk 1
     * akun (permintaan user 2026-10-03: kunci 1 perangkat per akun).
     *
     * "Aktif" di sini TIDAK HANYA logout_at masih NULL - itu saja tidak
     * cukup, karena kebanyakan orang menutup tab/browser begitu saja
     * TANPA klik Logout (logout_at akan tetap NULL SELAMANYA kalau hanya
     * mengandalkan itu, berakibat akun terkunci PERMANEN tidak bisa
     * login dari perangkat manapun termasuk perangkat lamanya sendiri).
     * Baris logout_at=NULL baru dianggap BENAR-BENAR aktif kalau SESSION
     * Laravel miliknya (tabel `sessions` bawaan Laravel, dicocokkan lewat
     * kolom session_id yang direkam saat login - lihat
     * catatRiwayatLogin()) juga masih tercatat last_activity dalam batas
     * waktu sesi aplikasi (config('session.lifetime'), default 120
     * menit) - last_activity ini otomatis terupdate oleh Laravel sendiri
     * di SETIAP request yang diautentikasi, jadi tidak perlu mekanisme
     * "heartbeat" tambahan apapun.
     *
     * Baris yang ternyata SUDAH BASI (session sudah tidak aktif/sudah
     * dibersihkan housekeeping Laravel) langsung dibersihkan di sini
     * (logout_at diisi) supaya tidak terus dicek ulang di percobaan
     * login berikutnya, dan menu Pengguna > Riwayat Login tidak
     * menampilkan "Masih berlangsung" selamanya untuk sesi yang
     * sebenarnya sudah mati.
     */
    private function cariSesiLainAktif(User $user): ?LoginHistory
    {
        $batasAktif = now()->subMinutes((int) config('session.lifetime'))->getTimestamp();

        $kandidat = LoginHistory::where('user_id', $user->id)
            ->whereNull('logout_at')
            ->latest('login_at')
            ->get();

        foreach ($kandidat as $riwayat) {
            $masihAktif = $riwayat->session_id && DB::table('sessions')
                ->where('id', $riwayat->session_id)
                ->where('last_activity', '>=', $batasAktif)
                ->exists();

            if ($masihAktif) {
                return $riwayat;
            }

            $riwayat->update(['logout_at' => $riwayat->login_at]);
        }

        return null;
    }

    /**
     * Login menggunakan kata sandi pemulihan (default) sesuai level akses
     * akun dengan username yang diketik, lalu wajibkan ganti password.
     *
     * HANYA untuk Superadmin (2026-09-05) - Admin OPS/Admin BOSP yang
     * mencoba jalur ini (mis. lewat request langsung, bukan dari tombol
     * di UI yang memang sudah disembunyikan untuk mereka) akan selalu
     * ditolak dengan pesan yang mengarahkan ke Superadmin.
     *
     * @throws ValidationException
     */
    public function pemulihan(): void
    {
        $this->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $this->username)->first();

        if ($user && $user->level_akses !== User::LEVEL_SUPERADMIN) {
            throw ValidationException::withMessages([
                'form.username' => 'Pemulihan akun mandiri sudah tidak tersedia untuk Admin OPS/Admin BOSP. Silakan hubungi Superadmin untuk me-reset password akun Anda.',
            ]);
        }

        $kataSandi = $user ? (static::kataSandiPemulihan()[$user->level_akses] ?? null) : null;

        if (! $user || $kataSandi === null || ! hash_equals($kataSandi, $this->password)) {
            FailedLoginAttempt::catat($this->username, request()->ip(), request()->userAgent(), $this->latitude, $this->longitude);

            throw ValidationException::withMessages([
                'form.username' => 'Akun tidak ditemukan atau kata sandi pemulihan tidak sesuai.',
            ]);
        }

        if (! $user->is_approved) {
            throw ValidationException::withMessages([
                'form.username' => 'Akun Anda masih menunggu persetujuan Superadmin dan belum bisa digunakan untuk login.',
            ]);
        }

        Auth::login($user, $this->remember);

        // Permintaan user 2026-09-27 (jalur darurat Superadmin kehilangan
        // Authenticator): login lewat kata sandi pemulihan SELALU me-reset
        // Google Authenticator akun ini (secret lama dibuang, wajib
        // aktivasi/scan barcode ulang dari awal) - supaya Superadmin yang
        // kehilangan HP tidak terkunci total menunggu email/Superadmin lain
        // yang tidak ada. Ini hanya berlaku utk Superadmin (satu-satunya
        // level yang punya jalur pemulihan mandiri, lihat validasi di atas).
        $user->forceFill([
            'must_change_password' => true,
            'google2fa_secret' => null,
            'google2fa_aktif_at' => null,
            'google2fa_reset_diminta_at' => null,
        ])->save();

        RateLimiter::clear($this->throttleKey());
        $this->percobaanGagal = 0;

        $this->catatRiwayatLogin($user);
    }

    /**
     * Catat satu baris riwayat login (permintaan user 2026-09-26, menu
     * Pengguna > tab Riwayat Login) - dipanggil tepat setelah Auth::attempt
     * atau Auth::login berhasil, baik dari jalur login normal maupun
     * jalur pemulihan akun. Kolom nama_sekolah/email disalin (snapshot)
     * dari data akun saat ini supaya riwayat lama tidak berubah kalau
     * data akun diedit belakangan.
     */
    private function catatRiwayatLogin(User $user): void
    {
        LoginHistory::create([
            'user_id' => $user->id,
            'level_akses' => $user->level_akses,
            'email' => $user->email,
            'nama_sekolah' => $user->profilSekolah?->nama_sekolah ?? $user->nama_sekolah,
            'login_at' => now(),
            'ip_address' => request()->ip(),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'session_id' => session()->getId(),
        ]);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->username).'|'.request()->ip());
    }
}
