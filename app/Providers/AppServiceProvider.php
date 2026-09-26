<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Superadmin: kelola Pengguna, memantau Pendataan OPS/BOSP, dan menambah sekolah baru
        // di Profil Sekolah. Admin OPS: hanya Pendataan OPS. Admin BOSP: hanya Pendataan BOSP.
        // Profil Sekolah (tabel semua sekolah) bisa dilihat oleh Superadmin, Admin OPS,
        // dan Admin BOSP; hak edit/tambah/hapus per baris diatur di dalam komponennya sendiri.
        Gate::define('akses-profil-sekolah', fn (User $user) => true);

        Gate::define('akses-pengguna', fn (User $user) => $user->isSuperadmin());

        Gate::define('akses-tampilan', fn (User $user) => $user->isSuperadmin());

        Gate::define(
            'akses-pendataan-ops',
            fn (User $user) => $user->isSuperadmin() || $user->isAdminOps()
        );

        Gate::define(
            'akses-pendataan-bosp',
            fn (User $user) => $user->isSuperadmin() || $user->isAdminBosp()
        );

        // Timeline Pekerjaan - DIBALIK 2026-09-23 (round kesepuluh, poin 2):
        // SEBELUMNYA (round kedelapan bagian B, jawaban AskUserQuestion
        // "Akses lihat" -> "Semua bisa lihat") Superadmin, Admin OPS, & Admin
        // BOSP SAMA-SAMA bisa membuka menu ini. Permintaan user round
        // kesepuluh membalik keputusan itu secara eksplisit: "menu Timeline
        // pekerjaan hanya muncul di superadmin, di admin ops dan admin bosp
        // tidak di munculkan" - SEKARANG HANYA Superadmin.
        //
        // CATATAN TAFSIRAN (perluasan teknis dari permintaan, mohon
        // dikoreksi kalau kurang sesuai): permintaan user secara literal
        // hanya menyebut menu-nya "tidak dimunculkan" (soal tampilan
        // sidebar) - Gate INI dipakai KEDUANYA sekaligus (sidebar lewat
        // @can di resources/views/livewire/layout/navigation.blade.php, DAN
        // middleware 'can:akses-timeline-pekerjaan' pada rute
        // timeline-pekerjaan.index di routes/web.php), jadi mengubahnya di
        // sini otomatis JUGA memblokir akses LANGSUNG lewat URL bagi Admin
        // OPS/Admin BOSP, bukan cuma menyembunyikan link-nya. Ini dipilih
        // supaya konsisten dengan pola menu lain di aplikasi (menu yang
        // "tidak dimunculkan" untuk suatu peran juga tidak bisa dibuka
        // langsung via URL oleh peran itu) - kalau Bapak ternyata hanya
        // ingin link-nya disembunyikan TAPI akses URL langsung tetap
        // dibolehkan, mohon beri tahu supaya bisa dipisah lagi.
        Gate::define('akses-timeline-pekerjaan', fn (User $user) => $user->isSuperadmin());

        // Menu "Panduan Aplikasi" (BARU 2026-09-24, round kedua puluh empat,
        // poin 6) - keputusan AskUserQuestion "Hanya Superadmin yang bisa
        // buka menu ini", sama seperti pola akses-pengguna/akses-tampilan/
        // akses-timeline-pekerjaan di atas (Admin OPS/Admin BOSP TIDAK bisa
        // membuka menu ini sama sekali, baik lewat sidebar maupun URL
        // langsung).
        Gate::define('akses-panduan-aplikasi', fn (User $user) => $user->isSuperadmin());

        // Menu "Backup" (BARU, round kedua puluh lima, 2026-09-24, poin 2) -
        // keputusan AskUserQuestion "Tersimpan di server, terdaftar per
        // tahun, khusus Superadmin", sama seperti pola gate-gate lain di
        // atas.
        Gate::define('akses-backup', fn (User $user) => $user->isSuperadmin());

        // Menu Pengumuman (permintaan user 2026-09-26) - khusus Superadmin,
        // sama seperti pola gate-gate lain di atas.
        Gate::define('akses-pengumuman', fn (User $user) => $user->isSuperadmin());
    }
}
