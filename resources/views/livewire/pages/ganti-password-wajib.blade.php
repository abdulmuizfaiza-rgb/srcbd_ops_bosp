<?php

use App\Livewire\Actions\Logout;
use App\Support\PasswordPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    public string $password_confirmation = '';

    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }

    public function simpan(): void
    {
        $validated = $this->validate([
            'password' => ['required', 'string', PasswordPolicy::rule(), 'confirmed'],
        ], PasswordPolicy::messages());

        $user = Auth::user();
        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ])->save();

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-4 text-center">
        <h1 class="text-lg font-semibold text-slate-800">Ganti Password Wajib</h1>
        <p class="text-sm text-slate-500 mt-1">
            Anda login menggunakan kata sandi pemulihan (default). Untuk keamanan,
            silakan buat kata sandi baru sebelum melanjutkan.
        </p>
    </div>

    <form wire:submit="simpan" class="space-y-4">
        <div>
            <x-input-label for="password" value="Password Baru" />
            <x-password-strength-meter>
                <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" autocomplete="new-password" autofocus />
            </x-password-strength-meter>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Konfirmasi Password Baru" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <button type="button" wire:click="logout" class="text-sm text-slate-500 hover:text-slate-700 underline">
                Keluar
            </button>
            <x-primary-button>Simpan Password Baru</x-primary-button>
        </div>
    </form>
</div>
