<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

/**
 * Audit keamanan 2026-10-14: menu untuk Superadmin membuat/membuat-ulang
 * kata sandi pemulihan miliknya SENDIRI (dipakai di jalur "Pemulihan
 * Akun" - lihat App\Livewire\Forms\LoginForm::pemulihan()). Menggantikan
 * 1 nilai tetap yang sebelumnya tertulis langsung di kode.
 *
 * Pola tampil: SEKALI tampil di layar tepat setelah dibuat/dibuat-ulang
 * (wajib disimpan sendiri saat itu juga, mis. di pengelola password),
 * TIDAK bisa dilihat lagi sesudahnya - hanya bisa "Buat Ulang" lagi kalau
 * lupa/hilang (nilai lama otomatis tidak berlaku lagi). Konfirmasi
 * password saat ini (current_password) diminta dulu sebelum membuat,
 * supaya tidak sembarang orang yang kebetulan memegang sesi login bisa
 * langsung mengganti kata sandi pemulihan tanpa tahu password asli.
 */
new class extends Component
{
    public string $current_password = '';

    public ?string $nilaiBaru = null;

    public function buatUlang(): void
    {
        try {
            $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password');

            throw $e;
        }

        $nilaiBaru = Str::password(20);

        Auth::user()->forceFill([
            'kata_sandi_pemulihan_hash' => Hash::make($nilaiBaru),
            'kata_sandi_pemulihan_dibuat_at' => now(),
        ])->save();

        $this->reset('current_password');
        $this->nilaiBaru = $nilaiBaru;
    }

    public function tutupNilaiBaru(): void
    {
        $this->reset('nilaiBaru');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-slate-900">Kata Sandi Pemulihan</h2>

        <p class="mt-1 text-sm text-slate-600">
            Dipakai lewat tombol "Pemulihan Akun" di halaman login kalau Anda lupa password biasa. Nilainya hanya ditampilkan SEKALI tepat setelah dibuat - simpan baik-baik di tempat yang aman (misal pengelola password). Setiap kali dipakai berhasil untuk login, nilainya otomatis tidak berlaku lagi (sekali pakai) dan Anda akan menerima email pemberitahuan.
        </p>

        <p class="mt-2 text-sm">
            @if (Auth::user()->kata_sandi_pemulihan_dibuat_at)
                <span class="text-emerald-700">Status: sudah diatur</span> - terakhir dibuat {{ Auth::user()->kata_sandi_pemulihan_dibuat_at->translatedFormat('d F Y, H:i') }} WIB.
            @else
                <span class="text-amber-700">Status: belum pernah diatur</span> - jalur "Pemulihan Akun" tidak akan berfungsi sampai Anda membuatnya.
            @endif
        </p>
    </header>

    @if ($nilaiBaru)
        <div class="mt-4 rounded-lg border border-emerald-300 bg-emerald-50 p-4" x-data="{ disalin: false }">
            <p class="text-sm font-medium text-emerald-800">Kata sandi pemulihan baru Anda (hanya tampil sekali ini saja):</p>
            <div class="mt-2 flex items-center gap-2">
                <code class="flex-1 select-all break-all rounded bg-white px-3 py-2 text-sm font-mono text-slate-800 ring-1 ring-emerald-200">{{ $nilaiBaru }}</code>
                <button type="button"
                    @click="navigator.clipboard.writeText('{{ $nilaiBaru }}'); disalin = true; setTimeout(() => disalin = false, 2000)"
                    class="rounded-md bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700 transition">
                    <span x-show="!disalin">Salin</span>
                    <span x-show="disalin" x-cloak>Tersalin!</span>
                </button>
            </div>
            <p class="mt-2 text-xs text-emerald-700">Simpan nilai ini SEKARANG sebelum menutup kotak ini - tidak akan ditampilkan lagi sesudahnya.</p>
            <button type="button" wire:click="tutupNilaiBaru" class="mt-3 rounded-md bg-slate-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 transition">
                Tutup (saya sudah menyimpannya)
            </button>
        </div>
    @else
        <form wire:submit="buatUlang" class="mt-6 space-y-6">
            <div>
                <x-input-label for="kata_sandi_pemulihan_current_password" value="Password Anda Saat Ini" />
                <x-text-input wire:model="current_password" id="kata_sandi_pemulihan_current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
            </div>

            <div>
                <x-primary-button>Buat / Buat Ulang Kata Sandi Pemulihan</x-primary-button>
            </div>
        </form>
    @endif
</section>
