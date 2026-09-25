<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deadline Pekerjaan - fondasi fitur "Timeline Pekerjaan" (round kedelapan,
 * bagian B). Permintaan user 2026-09-23: "saya juga ingin dibuatkan menu
 * Timeline pekerjaan baik untuk pendataan OPS dan pendataan BOSP supaya
 * lebih disiplin dari sisi deadline pekerjaannya. dimana superadmin bisa
 * mengatur nya untuk deadline pendataan OPS dan deadline pendataan BOSP."
 *
 * Scoping lengkap lewat beberapa ronde `AskUserQuestion` (semua jawaban
 * user, tanggal sama):
 * - "Tahap kerja" -> "Per menu utama": SATU baris = 1 deadline untuk 1
 *   menu utama (`kunci_menu` = nama route menu itu, lihat
 *   App\Models\DeadlinePekerjaan::daftarTahapKerja() untuk daftar
 *   lengkap 16 menu yang dapat deadline - menu identitas/onboarding
 *   "Identitas OPS"/"Identitas Admin BOSP" & menu "Unduhan" [murni
 *   export/cetak] SENGAJA dikecualikan, bukan tahap kerja rutin).
 * - "Cakupan deadline" -> "Sama untuk semua sekolah": SATU deadline per
 *   tahap kerja berlaku untuk SEMUA sekolah sekaligus - TIDAK ADA kolom
 *   profil_sekolah_id di tabel ini (beda dari verval_realisasi_bosp yang
 *   per-sekolah).
 * - "Periode deadline" -> "Per tahun (Recommended)": deadline terikat ke
 *   1 tahun anggaran (`tahun`), konsisten dengan pola kolom `tahun` yang
 *   dipakai di hampir semua tabel data BOSP/OPS lain di aplikasi ini.
 *   Superadmin atur ulang deadline baru tiap tahun (baris lama tahun
 *   sebelumnya TETAP ADA sebagai riwayat, tidak otomatis dihapus/
 *   dicopy).
 * - "Efek terlambat" -> "Ikut memblokir input": begitu tanggal deadline
 *   lewat, menu terkait ikut terkunci/dibatasi (lihat
 *   App\Livewire\Concerns\MenolakEditJikaLewatDeadline - trait BARU,
 *   BEDA dari MenolakEditJikaTerkunciVerval yang sudah ada, diterapkan
 *   MENYUSUL di file terpisah setelah fondasi tabel ini teruji) sampai
 *   Superadmin reset/perpanjang (hapus/ubah barisnya lewat
 *   App\Livewire\TimelinePekerjaan\Index).
 * - "Akses lihat" -> "Semua bisa lihat": Superadmin kelola (create/
 *   update/delete tanggal_deadline lewat updateOrCreate + delete biasa,
 *   TIDAK BUTUH kolom status/soft-delete terpisah), Admin OPS & Admin
 *   BOSP HANYA bisa lihat (read-only) - hak akses diatur di Livewire
 *   component-nya, bukan di skema tabel.
 *
 * `diatur_oleh` dicatat untuk jejak audit (siapa Superadmin yang
 * terakhir mengatur baris ini) - pola sama seperti `diverval_oleh` pada
 * verval_realisasi_bosp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadline_pekerjaan', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->string('kunci_menu');
            $table->date('tanggal_deadline');
            $table->foreignId('diatur_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tahun', 'kunci_menu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadline_pekerjaan');
    }
};
