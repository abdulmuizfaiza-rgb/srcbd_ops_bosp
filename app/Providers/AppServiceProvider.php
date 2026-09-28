<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Carbon;
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
        // Tampilan pagination baku SELURUH aplikasi (permintaan user
        // 2026-09-28, disertai contoh gambar: kartu putih bulat berisi
        // Previous/nomor halaman/Next, nomor aktif bergaris biru) -
        // TIDAK didaftarkan lewat Paginator::defaultView() di sini,
        // karena percobaan pertama (begitu) TERBUKTI TIDAK BEKERJA:
        // Livewire sendiri (SupportPagination::boot(), jalan tiap kali
        // komponen ber-paginasi di-render) SELALU menimpa balik
        // pengaturan ini ke tampilan bawaannya sendiri
        // ('livewire::tailwind') persis sebelum {{ $paginator->links() }}
        // dipanggil. Solusi yang benar: file
        // resources/views/vendor/livewire/tailwind.blade.php (Laravel
        // otomatis memakai file di path ini utk override view bawaan
        // paket 'livewire::tailwind', tanpa perlu registrasi apapun).
        // Baris Paginator::defaultView() DIHAPUS dari sini karena
        // percuma/menyesatkan - lihat file blade tsb utk versi yang
        // benar-benar dipakai.

        // Zona waktu tampilan (BARU, 2026-09-27, permintaan user "settingan
        // jam waktu upload tidak sesuai settingan yang ada di laptop" pada
        // menu Backup - lalu dikonfirmasi lewat AskUserQuestion berlaku utk
        // SEMUA tampilan jam di aplikasi, bukan cuma menu Backup). Akar
        // masalah: config('app.timezone') aplikasi ini memang 'UTC' (bukan
        // bug - ini praktik standar, supaya data tersimpan konsisten), tapi
        // TIDAK ADA konversi ke waktu lokal saat DITAMPILKAN ke user,
        // sehingga jam yang tampil di layar (mis. "12:47") sebenarnya jam
        // UTC, terpaut 7 jam dari WIB (jawaban user - Superadmin aplikasi
        // ini berlokasi WIB). config('app.timezone') SENGAJA TIDAK diubah
        // (data created_at/login_at/dst yang SUDAH tersimpan tetap UTC apa
        // adanya - kalau config ini yang diubah, data LAMA justru akan
        // salah tampil karena disangka sudah WIB) - sebagai gantinya macro
        // Carbon baru ->keWaktuLokal() ini HANYA dipakai saat MENAMPILKAN
        // jam ke user (dipanggil di blade view), mengonversi dari UTC
        // (tersimpan) ke Asia/Jakarta (WIB) tanpa menyentuh data di
        // database sama sekali. Dipakai di: menu Backup (tanggal dibuat),
        // Cek Database dan Aplikasi (Log Login Gagal & Log Akses Data),
        // Panduan Aplikasi (tanggal diunggah), dan Pengguna (riwayat
        // login/logout).
        Carbon::macro('keWaktuLokal', function () {
            /** @var Carbon $this */
            return $this->copy()->timezone('Asia/Jakarta');
        });

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

        // Menu "Cek Database dan Aplikasi" (BARU, permintaan user
        // 2026-09-27) - kontrol integritas data & keamanan akses, khusus
        // Superadmin, sama seperti pola gate-gate lain di atas.
        Gate::define('akses-cek-database-aplikasi', fn (User $user) => $user->isSuperadmin());
    }
}
