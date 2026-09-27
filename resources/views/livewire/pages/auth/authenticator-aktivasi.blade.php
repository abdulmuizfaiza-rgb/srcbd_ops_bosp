<?php

use App\Models\User;
use App\Services\GoogleAuthenticatorService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

/**
 * Halaman aktivasi Google Authenticator (2FA) - permintaan user 2026-09-27.
 * Dipaksa muncul (lewat EnsureGoogleAuthenticatorVerified, lihat
 * bootstrap/app.php) tepat setelah login pertama kali untuk pengguna yang
 * belum pernah menyelesaikan aktivasi (kolom google2fa_aktif_at masih null),
 * baik Superadmin, Admin OPS, maupun Admin BOSP - TIDAK ADA pengecualian
 * role (dikonfirmasi user).
 *
 * Alur: tampilkan barcode (QR code) + kunci manual -> pengguna scan pakai
 * aplikasi Google Authenticator di HP -> ketik kode 6 digit yang muncul di
 * HP untuk konfirmasi barcode sudah benar-benar tersimpan -> baru dianggap
 * aktif (google2fa_aktif_at diisi).
 */
new #[Layout('layouts.guest')] class extends Component
{
    public string $kode = '';

    public string $kunciManual = '';

    public string $svgBarcode = '';

    public function mount(GoogleAuthenticatorService $layanan): void
    {
        /** @var User $user */
        $user = Auth::user();

        // Sudah pernah aktif - tidak ada yang perlu diaktivasi lagi di sini,
        // lempar ke alur verifikasi kode normal.
        if ($user->authenticatorAktif()) {
            $this->redirect(route('authenticator.verifikasi'), navigate: true);

            return;
        }

        // Kalau secret belum ada sama sekali, buat baru & simpan sekarang
        // (belum aktif - google2fa_aktif_at masih null) supaya kalau
        // halaman ini di-refresh, barcode yang tampil TETAP SAMA (bukan
        // berubah tiap refresh, yang akan bikin bingung kalau sudah
        // terlanjur di-scan).
        if (! $user->google2fa_secret) {
            $user->forceFill(['google2fa_secret' => $layanan->buatSecretBaru()])->save();
            $user->refresh();
        }

        $this->kunciManual = $user->google2fa_secret;
        $this->svgBarcode = $layanan->svgBarcode($user, $user->google2fa_secret);
    }

    public function aktifkan(GoogleAuthenticatorService $layanan): void
    {
        $this->validate([
            'kode' => ['required', 'digits:6'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (! $layanan->kodeValid($user->google2fa_secret, $this->kode)) {
            $this->addError('kode', 'Kode tidak sesuai. Pastikan jam di HP Anda sudah benar, lalu coba kode terbaru yang muncul di aplikasi Authenticator.');

            return;
        }

        $user->forceFill([
            'google2fa_aktif_at' => now(),
            'google2fa_reset_diminta_at' => null,
        ])->save();

        session(['google2fa_terverifikasi' => true]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6 text-center">
        <h1 class="text-lg font-semibold text-[color:var(--warna-huruf-login)]">Aktifkan Google Authenticator</h1>
        <p class="text-sm text-[color:var(--warna-huruf-login)]">
            Sebagai lapisan keamanan tambahan, akun Anda wajib diaktifkan dengan aplikasi Google Authenticator sebelum bisa masuk ke halaman aplikasi.
        </p>
    </div>

    <div class="mb-6 flex flex-col items-center gap-3">
        <div class="bg-white p-3 rounded-lg border border-slate-200 inline-block">
            {!! $svgBarcode !!}
        </div>
        <p class="text-xs text-slate-500 text-center">
            Buka aplikasi <span class="font-medium">Google Authenticator</span> di HP Anda, pilih "Scan barcode", lalu arahkan kamera ke gambar di atas.
        </p>
        <details class="text-xs text-slate-500 w-full">
            <summary class="cursor-pointer text-center select-none">Tidak bisa scan? Masukkan kunci secara manual</summary>
            <p class="mt-2 text-center font-mono tracking-wider break-all bg-slate-50 border border-slate-200 rounded px-2 py-1">{{ $kunciManual }}</p>
        </details>
    </div>

    <form wire:submit="aktifkan">
        <div>
            <x-input-label for="kode" :value="__('Kode 6 Digit dari Google Authenticator')" class="!text-[color:var(--warna-huruf-login)]" />
            <x-text-input wire:model="kode" id="kode" class="block mt-1 w-full text-center tracking-[0.5em] text-lg" type="text" inputmode="numeric" maxlength="6" required autofocus />
            <x-input-error :messages="$errors->get('kode')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>Aktifkan</x-primary-button>
        </div>
    </form>
</div>
