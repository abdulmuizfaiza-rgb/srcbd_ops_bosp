<?php

use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public ?int $profil_sekolah_id = null;

    public string $npsn = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $level_akses = '';

    public bool $selesai = false;

    protected function jabatanOtomatis(string $level): string
    {
        return match ($level) {
            User::LEVEL_ADMIN_OPS => 'Operator Sekolah',
            User::LEVEL_ADMIN_BOSP => 'Admin BOSP',
            default => '',
        };
    }

    public function updatedProfilSekolahId(): void
    {
        $this->npsn = (string) (ProfilSekolah::find($this->profil_sekolah_id)?->npsn ?? '');
    }

    public function daftar(): void
    {
        $validated = $this->validate([
            'profil_sekolah_id' => ['required', Rule::exists('profil_sekolah', 'id')],
            'level_akses' => ['required', Rule::in([User::LEVEL_ADMIN_OPS, User::LEVEL_ADMIN_BOSP])],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'username'),
            ],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $sudahAda = User::where('profil_sekolah_id', $validated['profil_sekolah_id'])
            ->where('level_akses', $validated['level_akses'])
            ->exists();

        if ($sudahAda) {
            $labelLevel = User::levelAksesOptions()[$validated['level_akses']];
            $this->addError('profil_sekolah_id', "Sekolah ini sudah memiliki akun {$labelLevel} (baik yang aktif maupun yang masih menunggu persetujuan).");

            return;
        }

        $sekolah = ProfilSekolah::findOrFail($validated['profil_sekolah_id']);

        User::create([
            'username' => $validated['email'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'level_akses' => $validated['level_akses'],
            'profil_sekolah_id' => $sekolah->id,
            'nama_sekolah' => $sekolah->nama_sekolah,
            'jabatan' => $this->jabatanOtomatis($validated['level_akses']),
            'must_change_password' => true,
            'is_approved' => false,
        ]);

        $this->selesai = true;
    }

    public function with(): array
    {
        return [
            // Urutan Status (Negeri dulu, baru Swasta) -> Kecamatan -> Nama
            // Sekolah - mengikuti urutan baku yang sudah dipakai di menu
            // Profil Sekolah & Unduhan (Superadmin), supaya konsisten di
            // seluruh aplikasi.
            'sekolahOptions' => ProfilSekolah::query()
                ->orderByRaw("CASE WHEN status = 'negeri' THEN 0 WHEN status = 'swasta' THEN 1 ELSE 2 END")
                ->orderByRaw('kecamatan IS NULL')
                ->orderBy('kecamatan')
                ->orderBy('nama_sekolah')
                ->get(),
        ];
    }
}; ?>

<div class="form-registrasi">
    @if ($selesai)
        <div class="text-center space-y-4">
            <h1 class="text-lg font-semibold text-[color:var(--warna-huruf-registrasi)]">Registrasi Berhasil</h1>
            <p class="text-sm text-[color:var(--warna-huruf-registrasi)]">
                Akun Anda sudah terdaftar dan sedang menunggu persetujuan Superadmin.
                Silakan hubungi Superadmin untuk mengaktifkan akun Anda sebelum login.
            </p>
            <a href="{{ route('login') }}" wire:navigate class="inline-block text-sm text-blue-600 hover:underline">
                Kembali ke halaman Login
            </a>
        </div>
    @else
        <div class="mb-4 text-center">
            <h1 class="text-lg font-semibold text-[color:var(--warna-huruf-registrasi)]">Registrasi Admin OPS / Admin BOSP</h1>
            <p class="text-sm text-[color:var(--warna-huruf-registrasi)]">Akun baru perlu disetujui Superadmin sebelum bisa login.</p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form wire:submit="daftar" class="space-y-4">
            <div>
                <x-input-label for="level_akses" value="Level Akses" class="!text-[color:var(--warna-huruf-registrasi)]" />
                <select wire:model="level_akses" id="level_akses" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                    <option value="">-- Pilih Level Akses --</option>
                    <option value="admin_ops">Admin OPS</option>
                    <option value="admin_bosp">Admin BOSP</option>
                </select>
                <x-input-error :messages="$errors->get('level_akses')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="profil_sekolah_id" value="Nama Sekolah" class="!text-[color:var(--warna-huruf-registrasi)]" />
                <select wire:model.live="profil_sekolah_id" id="profil_sekolah_id" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                    <option value="">-- Pilih Sekolah --</option>
                    @foreach ($sekolahOptions as $sekolah)
                        <option value="{{ $sekolah->id }}">{{ $sekolah->nama_sekolah }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-[color:var(--warna-huruf-registrasi)] mt-1">Sekolah belum ada di daftar? Hubungi Superadmin untuk didaftarkan dulu di menu Profil Sekolah.</p>
                <x-input-error :messages="$errors->get('profil_sekolah_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="npsn" value="NPSN" class="!text-[color:var(--warna-huruf-registrasi)]" />
                <x-text-input id="npsn" class="block mt-1 w-full bg-slate-100" type="text" :value="$npsn" readonly />
            </div>

            <div>
                <x-input-label for="email" value="Email (akan menjadi username)" class="!text-[color:var(--warna-huruf-registrasi)]" />
                <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" value="Password" class="!text-[color:var(--warna-huruf-registrasi)]" />
                <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" value="Konfirmasi Password" class="!text-[color:var(--warna-huruf-registrasi)]" />
                <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full" type="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between mt-4">
                <a href="{{ route('login') }}" wire:navigate class="text-sm text-[color:var(--warna-huruf-registrasi)] hover:text-slate-700 underline">
                    Sudah punya akun? Login
                </a>
                <x-primary-button>Daftar</x-primary-button>
            </div>
        </form>
    @endif
</div>
