<?php

namespace App\Livewire\Forms;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
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
     * saat user memilih jenis akses). Permintaan user (2026-09-26):
     * menu Pengguna > tab Riwayat Login menampilkan kolom ini. Kalau
     * user menolak izin lokasi browser, kedua properti ini tetap NULL -
     * baris riwayat login tetap dibuat, hanya kolom koordinatnya kosong.
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

            throw ValidationException::withMessages([
                'form.username' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        $this->percobaanGagal = 0;

        $this->catatRiwayatLogin(Auth::user());
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
        $user->forceFill(['must_change_password' => true])->save();

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
