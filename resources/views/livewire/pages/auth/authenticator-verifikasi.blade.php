<?php

use App\Models\User;
use App\Services\GoogleAuthenticatorService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

/**
 * Halaman verifikasi kode 6 digit Google Authenticator (2FA) - permintaan
 * user 2026-09-27. Dipaksa muncul (lewat EnsureGoogleAuthenticatorVerified,
 * lihat bootstrap/app.php) SETIAP kali pengguna yang sudah pernah
 * mengaktifkan Authenticator login ulang - TIDAK ADA pengecualian role.
 *
 * Fitur "Lupa Authenticator?" (dikonfirmasi user lewat AskUserQuestion):
 * pengguna HANYA menandai permintaan reset (google2fa_reset_diminta_at)
 * lalu di-logout - TIDAK ada reset otomatis di sini. Superadmin yang
 * melihat permintaan ini di menu Pengguna > tab Authenticator, dan
 * SUPERADMIN yang menekan tombol untuk benar-benar mengirim barcode baru
 * ke email pengguna tersebut (lihat App\Livewire\Pengguna\Index).
 */
new #[Layout('layouts.guest')] class extends Component
{
    public string $kode = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        // Belum pernah aktif - seharusnya tidak sampai ke halaman ini,
        // lempar ke alur aktivasi.
        if (! $user->authenticatorAktif()) {
            $this->redirect(route('authenticator.aktivasi'), navigate: true);
        }
    }

    public function verifikasi(GoogleAuthenticatorService $layanan): void
    {
        $this->validate([
            'kode' => ['required', 'digits:6'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (! $layanan->kodeValid($user->google2fa_secret, $this->kode)) {
            $this->addError('kode', 'Kode tidak sesuai. Periksa jam di HP Anda, lalu coba kode terbaru yang muncul di aplikasi Authenticator.');

            return;
        }

        session(['google2fa_terverifikasi' => true]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Pengguna menekan "Lupa Authenticator?" - HANYA menandai permintaan
     * reset supaya terlihat oleh Superadmin (menu Pengguna > tab
     * Authenticator), lalu logout. Superadmin sendiri yang melakukan
     * reset & mengirim barcode baru ke email pengguna terdaftar.
     */
    public function lupaAuthenticator(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $user->forceFill(['google2fa_reset_diminta_at' => now()])->save();

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        session()->flash('status', 'Permintaan reset Authenticator sudah dikirim. Superadmin akan mengirimkan barcode baru ke email Anda yang terdaftar.');

        $this->redirect(route('verifikasi-akses'), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6 text-center">
        <h1 class="text-lg font-semibold text-[color:var(--warna-huruf-login)]">Verifikasi Google Authenticator</h1>
        <p class="text-sm text-[color:var(--warna-huruf-login)]">
            Masukkan kode 6 digit yang muncul di aplikasi Google Authenticator pada HP Anda.
        </p>
    </div>

    <form wire:submit="verifikasi">
        <div>
            <x-input-label for="kode" :value="__('Kode 6 Digit')" class="!text-[color:var(--warna-huruf-login)]" />
            <x-text-input wire:model="kode" id="kode" class="block mt-1 w-full text-center tracking-[0.5em] text-lg" type="text" inputmode="numeric" maxlength="6" required autofocus />
            <x-input-error :messages="$errors->get('kode')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <button type="button" wire:click="lupaAuthenticator" wire:confirm="Superadmin akan diminta mengirimkan barcode baru ke email Anda. Anda akan keluar dari sesi login saat ini. Lanjutkan?" class="text-sm text-[color:var(--warna-huruf-login)] hover:text-slate-700 underline">
                Lupa Authenticator?
            </button>
            <x-primary-button>Verifikasi</x-primary-button>
        </div>
    </form>
</div>
