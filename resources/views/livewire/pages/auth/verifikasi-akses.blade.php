<?php

use App\Mail\TokenVerifikasiAkses;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

/**
 * Gerbang verifikasi email + token 6 digit SEBELUM halaman login yang
 * sudah ada (resources/views/livewire/pages/auth/login.blade.php) bisa
 * dibuka - diminta user 2026-09-26 sebagai lapisan keamanan tambahan
 * supaya tidak semua orang bisa sembarangan mengakses form login.
 *
 * Keputusan yang sudah dikonfirmasi user lewat AskUserQuestion (2026-09-26):
 * - Kanal token: EMAIL saja dulu (WhatsApp menyusul di fase berikutnya).
 * - Masa berlaku token: 15 MENIT (bukan 15 detik seperti kalimat awal
 *   permintaan user - sudah dikonfirmasi ulang, 15 detik tidak praktis).
 * - Ini GERBANG TAMBAHAN, BUKAN pengganti login username/password yang
 *   sudah ada - LoginForm & login.blade.php TIDAK diubah logikanya sama
 *   sekali, cuma ditambah pengecekan mount() supaya cuma bisa dibuka
 *   setelah lolos gerbang ini (lihat login.blade.php).
 * - Superadmin IKUT alur yang sama (pakai email abdulmuizfaiza@gmail.com,
 *   lihat migrasi 2026_09_26_090000_add_email_to_users_table.php).
 * - Email TIDAK ditemukan sama sekali -> diarahkan ke form registrasi
 *   (BUKAN ditolak balik ke beranda - ini sempat jadi dua kemungkinan
 *   yang kontradiktif di kalimat awal user, sudah dikonfirmasi ulang).
 * - Akun ditemukan tapi belum disetujui Superadmin -> tetap di gerbang
 *   ini, tampilkan pesan menunggu persetujuan (tidak kirim token).
 * - Tombol "Kirim Ulang Token" - jeda 60 detik supaya tidak dipakai spam.
 *
 * Token disimpan di Cache (BUKAN tabel baru) memakai key per user_id,
 * mengikuti pola RateLimiter yang sudah dipakai di LoginForm - tidak
 * perlu migrasi tabel tambahan. Dibatasi juga maksimal 5x percobaan
 * salah per token supaya tidak bisa ditebak asal (brute-force 6 digit).
 */
new #[Layout('layouts.guest')] class extends Component
{
    public string $step = 'email';

    public string $email = '';

    public string $token = '';

    public ?int $userId = null;

    public ?int $resendAvailableAt = null;

    private const MASA_BERLAKU_MENIT = 15;

    private const JEDA_KIRIM_ULANG_DETIK = 60;

    private const MAKS_PERCOBAAN_TOKEN = 5;

    private function cacheKeyToken(int $userId): string
    {
        return "otp-login:token:{$userId}";
    }

    private function cacheKeyResend(int $userId): string
    {
        return "otp-login:resend:{$userId}";
    }

    private function cacheKeyPercobaan(int $userId): string
    {
        return "otp-login:percobaan:{$userId}";
    }

    private function buatDanKirimToken(User $user): void
    {
        $token = (string) random_int(100000, 999999);

        Cache::put(
            $this->cacheKeyToken($user->id),
            ['hash' => Hash::make($token), 'expires_at' => now()->addMinutes(self::MASA_BERLAKU_MENIT)->timestamp],
            now()->addMinutes(self::MASA_BERLAKU_MENIT)
        );

        Cache::forget($this->cacheKeyPercobaan($user->id));

        try {
            Mail::to($user->email)->send(new TokenVerifikasiAkses($user, $token));
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim token verifikasi akses ke '.$user->email.': '.$e->getMessage());
            $this->addError('email', 'Gagal mengirim email token. Coba lagi beberapa saat lagi atau hubungi Superadmin.');

            return;
        }

        Cache::put($this->cacheKeyResend($user->id), true, now()->addSeconds(self::JEDA_KIRIM_ULANG_DETIK));

        $this->userId = $user->id;
        $this->resendAvailableAt = now()->addSeconds(self::JEDA_KIRIM_ULANG_DETIK)->timestamp;
        $this->step = 'token';
        $this->token = '';
    }

    public function lanjut(): void
    {
        $validated = $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            session()->flash('status', 'Email tersebut belum terdaftar. Silakan lakukan registrasi terlebih dahulu.');
            $this->redirect(route('register'), navigate: true);

            return;
        }

        if (! $user->is_approved) {
            $this->addError('email', 'Akun Anda sudah terdaftar namun masih menunggu persetujuan Superadmin. Silakan tunggu persetujuan sebelum mencoba masuk.');

            return;
        }

        $this->buatDanKirimToken($user);
    }

    public function kirimUlang(): void
    {
        if (! $this->userId) {
            $this->step = 'email';

            return;
        }

        if (Cache::has($this->cacheKeyResend($this->userId))) {
            $this->addError('token', 'Mohon tunggu sebentar sebelum meminta token baru.');

            return;
        }

        $user = User::find($this->userId);

        if (! $user) {
            $this->step = 'email';

            return;
        }

        $this->buatDanKirimToken($user);
    }

    public function verifikasi(): void
    {
        $this->validate([
            'token' => ['required', 'string', 'size:6'],
        ]);

        if (! $this->userId) {
            $this->step = 'email';

            return;
        }

        $percobaanKey = $this->cacheKeyPercobaan($this->userId);

        if (Cache::get($percobaanKey, 0) >= self::MAKS_PERCOBAAN_TOKEN) {
            $this->addError('token', 'Terlalu banyak percobaan salah. Silakan klik "Kirim Ulang Token" untuk mendapatkan token baru.');

            return;
        }

        $data = Cache::get($this->cacheKeyToken($this->userId));

        if (! $data || now()->timestamp > $data['expires_at']) {
            $this->addError('token', 'Token sudah kadaluarsa. Silakan klik "Kirim Ulang Token".');

            return;
        }

        if (! Hash::check($this->token, $data['hash'])) {
            Cache::put($percobaanKey, Cache::get($percobaanKey, 0) + 1, now()->addMinutes(self::MASA_BERLAKU_MENIT));
            $this->addError('token', 'Token salah. Silakan periksa kembali atau kirim ulang.');

            return;
        }

        // Lolos gerbang verifikasi - simpan tanda di session supaya
        // halaman /login (form username/password yang SUDAH ADA & TIDAK
        // DIUBAH logikanya) bisa diakses. Lihat mount() di login.blade.php.
        session()->put('gerbang_akses_login_user_id', $this->userId);

        Cache::forget($this->cacheKeyToken($this->userId));
        Cache::forget($percobaanKey);

        $this->redirect(route('login'), navigate: true);
    }

    public function gantiEmail(): void
    {
        $this->reset(['step', 'token', 'userId', 'resendAvailableAt']);
    }
}; ?>

<div>
    <div class="mb-4">
        <a href="{{ route('beranda') }}" wire:navigate
            class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 transition">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 010 1.06L9.06 10l3.73 3.71a.75.75 0 11-1.06 1.06l-4.25-4.25a.75.75 0 010-1.06l4.25-4.25a.75.75 0 011.06 0z" clip-rule="evenodd" /></svg>
            Kembali ke Beranda
        </a>
    </div>

    @if ($step === 'email')
        <div class="mb-6 text-center">
            <h1 class="text-lg font-semibold text-[color:var(--warna-huruf-login)]">Verifikasi Akses</h1>
            <p class="text-sm text-[color:var(--warna-huruf-login)]">Masukkan alamat email yang terdaftar untuk melanjutkan ke halaman login.</p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form wire:submit="lanjut">
            <div>
                <x-input-label for="email" :value="__('Alamat Email')" class="!text-[color:var(--warna-huruf-login)]" />
                <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-primary-button>Lanjutkan</x-primary-button>
            </div>
        </form>
    @else
        <div class="mb-6 text-center">
            <h1 class="text-lg font-semibold text-[color:var(--warna-huruf-login)]">Verifikasi Token</h1>
            <p class="text-sm text-[color:var(--warna-huruf-login)]">
                Kode verifikasi 6 digit telah dikirim ke <span class="font-medium">{{ $email }}</span>. Berlaku selama 15 menit.
            </p>
        </div>

        <form wire:submit="verifikasi">
            <div>
                <x-input-label for="token" :value="__('Token 6 Digit')" class="!text-[color:var(--warna-huruf-login)]" />
                <x-text-input wire:model="token" id="token" class="block mt-1 w-full text-center tracking-[0.5em] text-lg" type="text" inputmode="numeric" maxlength="6" required autofocus />
                <x-input-error :messages="$errors->get('token')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between mt-4">
                <button type="button" wire:click="gantiEmail" class="text-sm text-[color:var(--warna-huruf-login)] hover:text-slate-700 underline">
                    Ganti Email
                </button>
                <x-primary-button>Verifikasi</x-primary-button>
            </div>
        </form>

        <div class="mt-4 text-center"
            wire:key="cooldown-{{ $resendAvailableAt }}"
            x-data="{
                target: {{ $resendAvailableAt ?? 0 }},
                remaining: 0,
                tick() { this.remaining = Math.max(0, this.target - Math.floor(Date.now() / 1000)); }
            }"
            x-init="tick(); let t = setInterval(() => { tick(); if (remaining <= 0) clearInterval(t); }, 1000)">
            <button type="button" wire:click="kirimUlang" :disabled="remaining > 0"
                class="text-sm underline disabled:no-underline disabled:cursor-not-allowed"
                :class="remaining > 0 ? 'text-slate-400' : 'text-[color:var(--warna-huruf-login)] hover:text-slate-700'">
                <span x-show="remaining > 0">Kirim Ulang Token (<span x-text="remaining"></span> detik)</span>
                <span x-show="remaining === 0">Kirim Ulang Token</span>
            </button>
        </div>
    @endif
</div>
