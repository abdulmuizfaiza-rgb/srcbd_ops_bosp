<?php

use App\Livewire\Actions\Logout;
use App\Models\PendataanBosp;
use App\Models\PendataanOps;
use App\Models\PengaturanTampilan;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    /**
     * Dipanggil setiap kali Profil Sekolah/Identitas OPS/Identitas BOSP
     * berhasil disimpan di komponen manapun pada halaman yang sama -
     * memaksa menu sidebar ini ikut re-render (lewat with() di bawah)
     * supaya menu yang baru terbuka (mis. "Pendataan OPS - Identitas
     * OPS") langsung muncul tanpa user harus klik F5/refresh manual.
     */
    #[On('kelengkapan-diperbarui')]
    public function segarkanKelengkapan(): void
    {
        // Sengaja kosong - method ini hanya dipakai supaya Livewire
        // menganggap komponen perlu di-render ulang saat event diterima.
    }

    public function with(): array
    {
        $user = auth()->user();
        // Sengaja query ulang (bukan lewat property relasi $user->profilSekolah)
        // supaya data sekolah yang dipakai untuk cek kelengkapan SELALU
        // terbaru dari database setiap kali komponen ini di-render ulang
        // (termasuk saat dipicu event "kelengkapan-diperbarui"), dan tidak
        // memakai data relasi lama yang mungkin sudah ter-cache di objek
        // user sejak render sebelumnya.
        $sekolah = $user->profilSekolah()->first();

        // Superadmin selalu dianggap "lengkap" (tidak kena gate onboarding).
        // Tahap 1: Profil Sekolah sekolahnya sudah lengkap/updated.
        $profilLengkap = $user->isSuperadmin() || ($sekolah && $sekolah->isLengkap());

        // Tahap 2: Identitas OPS/Identitas BOSP (sesuai levelnya) sudah diisi.
        $identitasLengkap = true;
        if (! $user->isSuperadmin() && $profilLengkap) {
            if ($user->isAdminOps()) {
                $identitasLengkap = PendataanOps::where('profil_sekolah_id', $sekolah->id)->exists();
            } elseif ($user->isAdminBosp()) {
                $identitasLengkap = PendataanBosp::where('profil_sekolah_id', $sekolah->id)->exists();
            }
        }

        return [
            'tampilan' => PengaturanTampilan::current(),
            'profilLengkap' => $profilLengkap,
            'identitasLengkap' => $identitasLengkap,
        ];
    }
}; ?>

<div x-data="{ open: false }">
    <!-- Mobile top bar -->
    <div class="menu-tampilan lg:hidden sticky top-0 z-40 flex items-center justify-between bg-slate-900 px-4 py-3 shadow-sm" style="background-color: {{ $tampilan->warna_menu }}">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2">
            <x-application-logo class="h-8 w-auto fill-current text-blue-400" />
            <span class="font-semibold text-white tracking-tight">{{ config('app.name') }}</span>
        </a>

        <button @click="open = true" class="inline-flex items-center justify-center p-2 rounded-md text-slate-300 hover:bg-slate-800 hover:text-white focus:outline-none transition">
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <!-- Mobile slide-over menu (from the left) -->
    <div x-show="open" style="display: none;" class="lg:hidden fixed inset-0 z-50">
        <div class="absolute inset-0 bg-slate-900/60" x-show="open"
             x-transition:enter="transition-opacity ease-linear duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="open = false"></div>

        <div class="menu-tampilan absolute left-0 inset-y-0 w-72 max-w-[85%] bg-slate-900 shadow-xl flex flex-col"
             style="background-color: {{ $tampilan->warna_menu }}"
             x-show="open"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">

            <div class="flex items-center justify-between px-4 py-4 border-b border-slate-800">
                <span class="font-semibold text-white tracking-tight">{{ config('app.name') }}</span>
                <button @click="open = false" class="p-2 rounded-md text-slate-400 hover:bg-slate-800 hover:text-white focus:outline-none">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                @if ($profilLengkap && $identitasLengkap)
                    <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate.hover>
                        {{ __('Dashboard') }}
                    </x-sidebar-link>
                @endif

                @can('akses-profil-sekolah')
                    <x-sidebar-link :href="route('profil-sekolah.index')" :active="request()->routeIs('profil-sekolah.*')" wire:navigate.hover>
                        {{ __('Profil Sekolah') }}
                    </x-sidebar-link>
                @endcan

                @can('akses-pendataan-ops')
                    @if ($profilLengkap)
                        <div x-data="{ opsMenuOpen: {{ request()->routeIs('pendataan-ops.*') ? 'true' : 'false' }} }">
                            <button type="button" @click="opsMenuOpen = ! opsMenuOpen" class="w-full flex items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-[color:var(--warna-huruf-menu)] hover:bg-slate-800/80 hover:text-white transition">
                                <span>{{ __('Pendataan OPS') }}</span>
                                <svg class="h-4 w-4 shrink-0 transition-transform" :class="opsMenuOpen ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="opsMenuOpen" x-transition class="mt-1 ml-3 pl-3 border-l border-slate-800 space-y-1">
                                <x-sidebar-link :href="route('pendataan-ops.index')" :active="request()->routeIs('pendataan-ops.index')" wire:navigate.hover>
                                    {{ __('Identitas OPS') }}
                                </x-sidebar-link>
                                @if ($identitasLengkap)
                                    <x-sidebar-link :href="route('pendataan-ops.lampiran-2a')" :active="request()->routeIs('pendataan-ops.lampiran-2a')" wire:navigate.hover>
                                        {{ __('Lampiran 2a') }}
                                    </x-sidebar-link>
                                    <x-sidebar-link :href="route('pendataan-ops.lampiran-2b')" :active="request()->routeIs('pendataan-ops.lampiran-2b')" wire:navigate.hover>
                                        {{ __('Lampiran 2b') }}
                                    </x-sidebar-link>
                                    <x-sidebar-link :href="route('pendataan-ops.lampiran-2c')" :active="request()->routeIs('pendataan-ops.lampiran-2c')" wire:navigate.hover>
                                        {{ __('Lampiran 2c') }}
                                    </x-sidebar-link>
                                    <x-sidebar-link :href="route('pendataan-ops.surat-tpg')" :active="request()->routeIs('pendataan-ops.surat-tpg')" wire:navigate.hover>
                                        {{ __('Format Surat Rekomendasi & Pembatalan TPG') }}
                                    </x-sidebar-link>
                                    <x-sidebar-link :href="route('pendataan-ops.unduhan')" :active="request()->routeIs('pendataan-ops.unduhan')" wire:navigate.hover>
                                        {{ __('Unduhan') }}
                                    </x-sidebar-link>
                                @endif
                            </div>
                        </div>
                    @endif
                @endcan

                @can('akses-pendataan-bosp')
                    @if ($profilLengkap)
                        <div x-data="{ bospMenuOpen: {{ request()->routeIs('pendataan-bosp.*') ? 'true' : 'false' }} }">
                            <button type="button" @click="bospMenuOpen = ! bospMenuOpen" class="w-full flex items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-[color:var(--warna-huruf-menu)] hover:bg-slate-800/80 hover:text-white transition">
                                <span>{{ __('Pendataan BOSP') }}</span>
                                <svg class="h-4 w-4 shrink-0 transition-transform" :class="bospMenuOpen ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="bospMenuOpen" x-transition class="mt-1 ml-3 pl-3 border-l border-slate-800 space-y-1">
                                <x-sidebar-link :href="route('pendataan-bosp.index')" :active="request()->routeIs('pendataan-bosp.index')" wire:navigate.hover>
                                    {{ __('Identitas Admin BOSP') }}
                                </x-sidebar-link>
                                @if ($identitasLengkap)
                                    <x-sidebar-link :href="route('pendataan-bosp.dana-bosp-tahap')" :active="request()->routeIs('pendataan-bosp.dana-bosp-tahap')" wire:navigate.hover>
                                        {{ __('Dana BOSP Tahap 1 & 2') }}
                                    </x-sidebar-link>
                                    <x-sidebar-link :href="route('pendataan-bosp.rekap-rkas')" :active="request()->routeIs('pendataan-bosp.rekap-rkas')" wire:navigate.hover>
                                        {{ __('Rekap RKAS Awal-Perubahan') }}
                                    </x-sidebar-link>
                                    <x-sidebar-link :href="route('pendataan-bosp.penerimaan-honor-ptk')" :active="request()->routeIs('pendataan-bosp.penerimaan-honor-ptk')" wire:navigate.hover>
                                        {{ __('Penerimaan Honor PTK') }}
                                    </x-sidebar-link>
                                    <x-sidebar-link :href="route('pendataan-bosp.langganan-daya-jasa')" :active="request()->routeIs('pendataan-bosp.langganan-daya-jasa')" wire:navigate.hover>
                                        {{ __('Langganan Daya dan Jasa') }}
                                    </x-sidebar-link>
                                    <x-sidebar-link :href="route('pendataan-bosp.belanja-pemeliharaan-bangunan')" :active="request()->routeIs('pendataan-bosp.belanja-pemeliharaan-bangunan')" wire:navigate.hover>
                                        {{ __('Belanja Pemeliharaan & Jasa Pemeliharaan Bangunan') }}
                                    </x-sidebar-link>
                                    <x-sidebar-link :href="route('pendataan-bosp.belanja-pemeliharaan-pc')" :active="request()->routeIs('pendataan-bosp.belanja-pemeliharaan-pc')" wire:navigate.hover>
                                        {{ __('Belanja Pemeliharaan PC Komputer-Laptop-Printer dll') }}
                                    </x-sidebar-link>
                                    <x-sidebar-link :href="route('pendataan-bosp.biaya-pendaftaran-lomba')" :active="request()->routeIs('pendataan-bosp.biaya-pendaftaran-lomba')" wire:navigate.hover>
                                        {{ __('Biaya Pendaftaran Lomba/Bimtek/Workshop') }}
                                    </x-sidebar-link>
                                    <x-sidebar-link :href="route('pendataan-bosp.belanja-honor-kegiatan')" :active="request()->routeIs('pendataan-bosp.belanja-honor-kegiatan')" wire:navigate.hover>
                                        {{ __('Belanja Honor Kegiatan & Makan Minum Kegiatan & Perjalanan Dinas') }}
                                    </x-sidebar-link>
                                    <x-sidebar-link :href="route('pendataan-bosp.rincian-belanja-modal')" :active="request()->routeIs('pendataan-bosp.rincian-belanja-modal')" wire:navigate.hover>
                                        {{ __('Rincian Belanja Modal & BMD') }}
                                    </x-sidebar-link>
                                    <x-sidebar-link :href="route('pendataan-bosp.rincian-belanja-barang-habis-pakai')" :active="request()->routeIs('pendataan-bosp.rincian-belanja-barang-habis-pakai')" wire:navigate.hover>
                                        {{ __('Rincian Belanja Barang Habis Pakai & Stock Opname') }}
                                    </x-sidebar-link>
                                    <x-sidebar-link :href="route('pendataan-bosp.pajak-bosp-reguler')" :active="request()->routeIs('pendataan-bosp.pajak-bosp-reguler')" wire:navigate.hover>
                                        {{ __('Pajak BOSP Reguler') }}
                                    </x-sidebar-link>
                                    <x-sidebar-link :href="route('pendataan-bosp.laporan-realisasi-bosp')" :active="request()->routeIs('pendataan-bosp.laporan-realisasi-bosp')" wire:navigate.hover>
                                        {{ __('Laporan Realisasi BOSP (Form BPK)') }}
                                    </x-sidebar-link>
                                    <x-sidebar-link :href="route('pendataan-bosp.formulir-bos-k7')" :active="request()->routeIs('pendataan-bosp.formulir-bos-k7')" wire:navigate.hover>
                                        {{ __('Formulir BOS K7b & K7c') }}
                                    </x-sidebar-link>
                                @endif
                            </div>
                        </div>
                    @endif
                @endcan

                @can('akses-timeline-pekerjaan')
                    <x-sidebar-link :href="route('timeline-pekerjaan.index')" :active="request()->routeIs('timeline-pekerjaan.*')" wire:navigate.hover>
                        {{ __('Timeline Pekerjaan') }}
                    </x-sidebar-link>
                @endcan

                @can('akses-pengguna')
                    <x-sidebar-link :href="route('pengguna.index')" :active="request()->routeIs('pengguna.*')" wire:navigate.hover>
                        {{ __('Pengguna') }}
                    </x-sidebar-link>
                @endcan

                @can('akses-tampilan')
                    <x-sidebar-link :href="route('tampilan.index')" :active="request()->routeIs('tampilan.*')" wire:navigate.hover>
                        {{ __('Tampilan') }}
                    </x-sidebar-link>
                @endcan

                @can('akses-panduan-aplikasi')
                    <x-sidebar-link :href="route('panduan-aplikasi.index')" :active="request()->routeIs('panduan-aplikasi.*')" wire:navigate.hover>
                        {{ __('Panduan Aplikasi') }}
                    </x-sidebar-link>
                @endcan

                @can('akses-backup')
                    <x-sidebar-link :href="route('backup.index')" :active="request()->routeIs('backup.*')" wire:navigate.hover>
                        {{ __('Backup') }}
                    </x-sidebar-link>
                @endcan
            </nav>

            <div class="border-t border-slate-800 px-4 py-4">
                <div class="text-sm font-semibold text-white">{{ auth()->user()->display_name }}</div>
                <div class="text-xs text-slate-400 mb-3">{{ auth()->user()->level_akses_label }}</div>

                <x-sidebar-link :href="route('profile')" wire:navigate.hover>
                    {{ __('Profile') }}
                </x-sidebar-link>

                <button wire:click="logout" class="w-full text-start mt-1">
                    <span class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800/80 hover:text-white transition">
                        {{ __('Log Out') }}
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Desktop sidebar (left side) -->
    <aside class="menu-tampilan hidden lg:flex lg:flex-col lg:fixed lg:inset-y-0 lg:left-0 lg:w-64 bg-slate-900" style="background-color: {{ $tampilan->warna_menu }}">
        <div class="flex items-center gap-2 px-5 py-5 border-b border-slate-800">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2">
                <x-application-logo class="h-9 w-auto fill-current text-blue-400" />
                <span class="font-semibold text-white tracking-tight leading-tight">{{ config('app.name') }}</span>
            </a>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-5 space-y-1">
            @if ($profilLengkap && $identitasLengkap)
                <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate.hover>
                    {{ __('Dashboard') }}
                </x-sidebar-link>
            @endif

            @can('akses-profil-sekolah')
                <x-sidebar-link :href="route('profil-sekolah.index')" :active="request()->routeIs('profil-sekolah.*')" wire:navigate.hover>
                    {{ __('Profil Sekolah') }}
                </x-sidebar-link>
            @endcan

            @can('akses-pendataan-ops')
                @if ($profilLengkap)
                    <div x-data="{ opsMenuOpen: {{ request()->routeIs('pendataan-ops.*') ? 'true' : 'false' }} }">
                        <button type="button" @click="opsMenuOpen = ! opsMenuOpen" class="w-full flex items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-[color:var(--warna-huruf-menu)] hover:bg-slate-800/80 hover:text-white transition">
                            <span>{{ __('Pendataan OPS') }}</span>
                            <svg class="h-4 w-4 shrink-0 transition-transform" :class="opsMenuOpen ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="opsMenuOpen" x-transition class="mt-1 ml-3 pl-3 border-l border-slate-800 space-y-1">
                            <x-sidebar-link :href="route('pendataan-ops.index')" :active="request()->routeIs('pendataan-ops.index')" wire:navigate.hover>
                                {{ __('Identitas OPS') }}
                            </x-sidebar-link>
                            @if ($identitasLengkap)
                                <x-sidebar-link :href="route('pendataan-ops.lampiran-2a')" :active="request()->routeIs('pendataan-ops.lampiran-2a')" wire:navigate.hover>
                                    {{ __('Lampiran 2a') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('pendataan-ops.lampiran-2b')" :active="request()->routeIs('pendataan-ops.lampiran-2b')" wire:navigate.hover>
                                    {{ __('Lampiran 2b') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('pendataan-ops.lampiran-2c')" :active="request()->routeIs('pendataan-ops.lampiran-2c')" wire:navigate.hover>
                                    {{ __('Lampiran 2c') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('pendataan-ops.surat-tpg')" :active="request()->routeIs('pendataan-ops.surat-tpg')" wire:navigate.hover>
                                    {{ __('Format Surat Rekomendasi & Pembatalan TPG') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('pendataan-ops.unduhan')" :active="request()->routeIs('pendataan-ops.unduhan')" wire:navigate.hover>
                                    {{ __('Unduhan') }}
                                </x-sidebar-link>
                            @endif
                        </div>
                    </div>
                @endif
            @endcan

            @can('akses-pendataan-bosp')
                @if ($profilLengkap)
                    <div x-data="{ bospMenuOpen: {{ request()->routeIs('pendataan-bosp.*') ? 'true' : 'false' }} }">
                        <button type="button" @click="bospMenuOpen = ! bospMenuOpen" class="w-full flex items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-[color:var(--warna-huruf-menu)] hover:bg-slate-800/80 hover:text-white transition">
                            <span>{{ __('Pendataan BOSP') }}</span>
                            <svg class="h-4 w-4 shrink-0 transition-transform" :class="bospMenuOpen ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="bospMenuOpen" x-transition class="mt-1 ml-3 pl-3 border-l border-slate-800 space-y-1">
                            <x-sidebar-link :href="route('pendataan-bosp.index')" :active="request()->routeIs('pendataan-bosp.index')" wire:navigate.hover>
                                {{ __('Identitas Admin BOSP') }}
                            </x-sidebar-link>
                            @if ($identitasLengkap)
                                <x-sidebar-link :href="route('pendataan-bosp.dana-bosp-tahap')" :active="request()->routeIs('pendataan-bosp.dana-bosp-tahap')" wire:navigate.hover>
                                    {{ __('Dana BOSP Tahap 1 & 2') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('pendataan-bosp.rekap-rkas')" :active="request()->routeIs('pendataan-bosp.rekap-rkas')" wire:navigate.hover>
                                    {{ __('Rekap RKAS Awal-Perubahan') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('pendataan-bosp.penerimaan-honor-ptk')" :active="request()->routeIs('pendataan-bosp.penerimaan-honor-ptk')" wire:navigate.hover>
                                    {{ __('Penerimaan Honor PTK') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('pendataan-bosp.langganan-daya-jasa')" :active="request()->routeIs('pendataan-bosp.langganan-daya-jasa')" wire:navigate.hover>
                                    {{ __('Langganan Daya dan Jasa') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('pendataan-bosp.belanja-pemeliharaan-bangunan')" :active="request()->routeIs('pendataan-bosp.belanja-pemeliharaan-bangunan')" wire:navigate.hover>
                                    {{ __('Belanja Pemeliharaan & Jasa Pemeliharaan Bangunan') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('pendataan-bosp.belanja-pemeliharaan-pc')" :active="request()->routeIs('pendataan-bosp.belanja-pemeliharaan-pc')" wire:navigate.hover>
                                    {{ __('Belanja Pemeliharaan PC Komputer-Laptop-Printer dll') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('pendataan-bosp.biaya-pendaftaran-lomba')" :active="request()->routeIs('pendataan-bosp.biaya-pendaftaran-lomba')" wire:navigate.hover>
                                    {{ __('Biaya Pendaftaran Lomba/Bimtek/Workshop') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('pendataan-bosp.belanja-honor-kegiatan')" :active="request()->routeIs('pendataan-bosp.belanja-honor-kegiatan')" wire:navigate.hover>
                                    {{ __('Belanja Honor Kegiatan & Makan Minum Kegiatan & Perjalanan Dinas') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('pendataan-bosp.rincian-belanja-modal')" :active="request()->routeIs('pendataan-bosp.rincian-belanja-modal')" wire:navigate.hover>
                                    {{ __('Rincian Belanja Modal & BMD') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('pendataan-bosp.rincian-belanja-barang-habis-pakai')" :active="request()->routeIs('pendataan-bosp.rincian-belanja-barang-habis-pakai')" wire:navigate.hover>
                                    {{ __('Rincian Belanja Barang Habis Pakai & Stock Opname') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('pendataan-bosp.pajak-bosp-reguler')" :active="request()->routeIs('pendataan-bosp.pajak-bosp-reguler')" wire:navigate.hover>
                                    {{ __('Pajak BOSP Reguler') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('pendataan-bosp.laporan-realisasi-bosp')" :active="request()->routeIs('pendataan-bosp.laporan-realisasi-bosp')" wire:navigate.hover>
                                    {{ __('Laporan Realisasi BOSP (Form BPK)') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('pendataan-bosp.formulir-bos-k7')" :active="request()->routeIs('pendataan-bosp.formulir-bos-k7')" wire:navigate.hover>
                                    {{ __('Formulir BOS K7b & K7c') }}
                                </x-sidebar-link>
                            @endif
                        </div>
                    </div>
                @endif
            @endcan

            @can('akses-timeline-pekerjaan')
                <x-sidebar-link :href="route('timeline-pekerjaan.index')" :active="request()->routeIs('timeline-pekerjaan.*')" wire:navigate.hover>
                    {{ __('Timeline Pekerjaan') }}
                </x-sidebar-link>
            @endcan

            @can('akses-pengguna')
                <x-sidebar-link :href="route('pengguna.index')" :active="request()->routeIs('pengguna.*')" wire:navigate.hover>
                    {{ __('Pengguna') }}
                </x-sidebar-link>
            @endcan

            @can('akses-tampilan')
                <x-sidebar-link :href="route('tampilan.index')" :active="request()->routeIs('tampilan.*')" wire:navigate.hover>
                    {{ __('Tampilan') }}
                </x-sidebar-link>
            @endcan

            @can('akses-panduan-aplikasi')
                <x-sidebar-link :href="route('panduan-aplikasi.index')" :active="request()->routeIs('panduan-aplikasi.*')" wire:navigate.hover>
                    {{ __('Panduan Aplikasi') }}
                </x-sidebar-link>
            @endcan

            @can('akses-backup')
                <x-sidebar-link :href="route('backup.index')" :active="request()->routeIs('backup.*')" wire:navigate.hover>
                    {{ __('Backup') }}
                </x-sidebar-link>
            @endcan
        </nav>

        <div class="border-t border-slate-800 p-4">
            <x-dropdown align="top" width="56">
                <x-slot name="trigger">
                    <button class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 text-left hover:bg-slate-800/80 transition focus:outline-none">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-semibold text-white">
                            {{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-white">{{ auth()->user()->display_name }}</span>
                            <span class="block truncate text-xs text-slate-400">{{ auth()->user()->level_akses_label }}</span>
                        </span>
                        <svg class="h-4 w-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l4 4a1 1 0 01-1.414 1.414L10 5.414 6.707 8.707a1 1 0 01-1.414-1.414l4-4A1 1 0 0110 3zm-4.707 9.293a1 1 0 011.414 0L10 15.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link :href="route('profile')" wire:navigate>
                        {{ __('Profile') }}
                    </x-dropdown-link>

                    <button wire:click="logout" class="w-full text-start">
                        <x-dropdown-link>
                            {{ __('Log Out') }}
                        </x-dropdown-link>
                    </button>
                </x-slot>
            </x-dropdown>
        </div>
    </aside>
</div>
