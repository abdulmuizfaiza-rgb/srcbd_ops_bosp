<?php

use App\Models\PermintaanAksesTahun;
use App\Models\TahunAnggaranSetting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

/**
 * Halaman "Pilih Tahun Anggaran" (permintaan user 2026-10-24) - muncul
 * SETELAH Admin OPS/Admin BOSP berhasil login (& lolos 2FA kalau akun
 * sudah aktifkan Authenticator), SEBELUM masuk ke aplikasi - dipaksa
 * muncul lewat App\Http\Middleware\EnsureTahunSesiDipilih (lihat
 * bootstrap/app.php), meniru POLA PERSIS halaman
 * authenticator-aktivasi/verifikasi.blade.php yang sudah ada.
 *
 * HANYA untuk Admin OPS/Admin BOSP - Superadmin tidak dibatasi tahun
 * sama sekali (middleware di atas sudah mengecualikan Superadmin,
 * mount() di bawah ini jadi lapisan pengaman tambahan kalau Superadmin
 * entah bagaimana sampai ke rute ini).
 *
 * Tahun yang BOLEH dipilih = Tahun Anggaran Aktif (selalu boleh) +
 * tahun-tahun lain yang sudah 'disetujui' Superadmin untuk akun ini
 * (lihat App\Models\PermintaanAksesTahun::tahunDiizinkanUntuk()). Kalau
 * akun itu HANYA punya 1 pilihan (belum pernah disetujui tahun lain),
 * halaman ini dilewati otomatis (langsung set session & lanjut) supaya
 * tidak menambah klik yang tidak perlu untuk kasus paling umum.
 */
new #[Layout('layouts.guest')] class extends Component
{
    /**
     * @var array<int, int>
     */
    public array $tahunOptions = [];

    public string $tahun = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (! in_array($user->level_akses, [User::LEVEL_ADMIN_OPS, User::LEVEL_ADMIN_BOSP], true)) {
            $this->redirect(route('dashboard'), navigate: true);

            return;
        }

        $this->tahunOptions = PermintaanAksesTahun::tahunDiizinkanUntuk($user);

        if (count($this->tahunOptions) <= 1) {
            $this->simpanTahunSesiDanLanjutkan($this->tahunOptions[0] ?? TahunAnggaranSetting::current()->tahun_aktif);

            return;
        }

        // Default pilihan = Tahun Anggaran Aktif (bukan tahun sebelumnya
        // yang sudah disetujui) - supaya tidak input ke tahun lama
        // secara tidak sengaja kalau langsung klik Lanjutkan tanpa
        // memeriksa pilihan.
        $this->tahun = (string) TahunAnggaranSetting::current()->tahun_aktif;
    }

    public function lanjutkan(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $tahunDiizinkan = PermintaanAksesTahun::tahunDiizinkanUntuk($user);

        // Validasi ulang di server - JANGAN percaya nilai dari dropdown
        // begitu saja (bisa saja diubah manual lewat devtools).
        if (! in_array((int) $this->tahun, $tahunDiizinkan, true)) {
            $this->addError('tahun', 'Pilihan tahun tidak valid. Silakan pilih salah satu tahun yang tersedia di bawah ini.');
            $this->tahunOptions = $tahunDiizinkan;

            return;
        }

        $this->simpanTahunSesiDanLanjutkan((int) $this->tahun);
    }

    /**
     * Simpan tahun terpilih ke session lalu lanjutkan ke tujuan semula
     * (url.intended, kalau ada) atau dashboard - persis seperti
     * redirectIntended() di authenticator-aktivasi/verifikasi.blade.php.
     */
    private function simpanTahunSesiDanLanjutkan(int $tahun): void
    {
        session(['tahun_sesi' => $tahun]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6 text-center">
        <h1 class="text-lg font-semibold text-[color:var(--warna-huruf-login)]">Pilih Tahun Anggaran</h1>
        <p class="text-sm text-[color:var(--warna-huruf-login)]">Data yang akan Anda buka/isi akan mengikuti tahun yang dipilih di sini sampai Anda logout.</p>
    </div>

    <form wire:submit="lanjutkan">
        <div>
            <x-input-label for="tahun" value="Tahun Anggaran" class="!text-[color:var(--warna-huruf-login)]" />
            <select wire:model="tahun" id="tahun" class="block mt-1 w-full border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                @foreach ($tahunOptions as $opsiTahun)
                    <option value="{{ $opsiTahun }}">{{ $opsiTahun }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('tahun')" class="mt-2" />
        </div>

        <p class="mt-3 text-xs text-slate-500">
            Ingin membuka tahun lain yang belum tersedia di atas? Login dulu dengan tahun yang tersedia, lalu ajukan permintaan ke Superadmin lewat menu "Tahun Anggaran".
        </p>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-3">
                Lanjutkan
            </x-primary-button>
        </div>
    </form>
</div>
