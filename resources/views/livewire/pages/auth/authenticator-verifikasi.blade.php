<?php

use App\Mail\BarcodeAuthenticatorDiresetSuperadmin;
use App\Models\User;
use App\Services\GoogleAuthenticatorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

/**
 * Halaman verifikasi kode 6 digit Google Authenticator (2FA) - permintaan
 * user 2026-09-27. Dipaksa muncul (lewat EnsureGoogleAuthenticatorVerified,
 * lihat bootstrap/app.php) SETIAP kali pengguna yang sudah pernah
 * mengaktifkan Authenticator login ulang - TIDAK ADA pengecualian role.
 *
 * Fitur "Lupa Authenticator?" (dikonfirmasi user lewat AskUserQuestion,
 * 2026-09-27): untuk Admin OPS/Admin BOSP, HANYA menandai permintaan reset
 * (google2fa_reset_diminta_at) lalu di-logout - TIDAK ada reset otomatis,
 * Superadmin yang melihat permintaan ini di menu Pengguna > tab
 * Authenticator & menekan tombol untuk benar-benar mengirim barcode baru
 * (lihat App\Livewire\Pengguna\Index::resetAuthenticator()).
 *
 * Permintaan user 2026-10-14: KHUSUS Superadmin, langsung kirim barcode
 * baru ke email terdaftarnya SENDIRI (self-service) - tidak menunggu
 * Superadmin LAIN memproses, karena akun ini bisa saja satu-satunya
 * Superadmin (tidak ada yang bisa memproses permintaannya lewat menu
 * Kelola Pengguna). Dianggap cukup aman karena pengguna baru saja lolos
 * login username/password yang benar (tahap sebelum halaman ini) DAN
 * email yang dituju sudah diverifikasi kepemilikannya lewat gerbang OTP
 * sebelum form login (lihat verifikasi-akses.blade.php) - lihat
 * lupaAuthenticator() di bawah untuk detail lengkap.
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
     * Pengguna menekan "Lupa Authenticator?".
     *
     * Superadmin: langsung di-reset & barcode baru dikirim ke email
     * terdaftarnya sendiri (self-service) - lihat docblock class di atas.
     * Sama persis prosesnya dengan Pengguna\Index::resetAuthenticator()
     * (dipicu Superadmin LAIN), hanya dipicu oleh pemilik akun sendiri di
     * sini.
     *
     * Admin OPS/Admin BOSP: TIDAK berubah - HANYA menandai permintaan
     * reset supaya terlihat oleh Superadmin (menu Pengguna > tab
     * Authenticator), lalu logout. Superadmin sendiri yang melakukan
     * reset & mengirim barcode baru ke email pengguna terdaftar.
     */
    public function lupaAuthenticator(GoogleAuthenticatorService $layanan): void
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->level_akses === User::LEVEL_SUPERADMIN) {
            $secretBaru = $layanan->buatSecretBaru();
            $alamatEmail = $user->email ?: $user->username;

            $user->forceFill([
                'google2fa_secret' => $secretBaru,
                'google2fa_aktif_at' => null,
                'google2fa_reset_diminta_at' => null,
            ])->save();

            $emailTerkirim = true;

            try {
                Mail::to($alamatEmail)->send(new BarcodeAuthenticatorDiresetSuperadmin(
                    $user,
                    $layanan->svgBarcodeDataUri($user, $secretBaru),
                    $secretBaru,
                ));
            } catch (\Throwable $e) {
                $emailTerkirim = false;

                Log::error('Gagal mengirim email barcode Authenticator baru (lupaAuthenticator mandiri Superadmin) ke '.$alamatEmail.': '.$e->getMessage());
            }

            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            if ($emailTerkirim) {
                session()->flash('status', 'Barcode Authenticator baru sudah dikirim ke email Anda yang terdaftar. Silakan cek email, scan barcode baru tsb di aplikasi Authenticator, lalu login ulang.');
            } else {
                session()->flash('status', "Authenticator berhasil di-reset, TAPI email barcode baru GAGAL terkirim ke {$alamatEmail} (kemungkinan SMTP di server belum/salah dikonfigurasi). Kunci manual: {$secretBaru} - catat nilai ini SEKARANG untuk dimasukkan manual di aplikasi Authenticator, lalu login ulang.");
            }

            $this->redirect(route('verifikasi-akses'), navigate: true);

            return;
        }

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
            <button type="button" wire:click="lupaAuthenticator" wire:confirm="{{ auth()->user()->level_akses === \App\Models\User::LEVEL_SUPERADMIN ? 'Barcode Authenticator baru akan langsung dikirim ke email Anda yang terdaftar, dan Anda akan keluar dari sesi login saat ini. Lanjutkan?' : 'Superadmin akan diminta mengirimkan barcode baru ke email Anda. Anda akan keluar dari sesi login saat ini. Lanjutkan?' }}" class="text-sm text-[color:var(--warna-huruf-login)] hover:text-slate-700 underline">
                Lupa Authenticator?
            </button>
            <x-primary-button>Verifikasi</x-primary-button>
        </div>
    </form>
</div>
