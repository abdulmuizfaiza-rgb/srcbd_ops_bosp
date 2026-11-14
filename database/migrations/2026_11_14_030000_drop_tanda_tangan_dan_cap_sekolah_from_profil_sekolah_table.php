<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penghapusan PERMANEN field Tanda Tangan Kepala Sekolah & Cap Sekolah -
 * permintaan user 2026-11-14 ("hapus saja field Tanda Tangan Kepala
 * Sekolah dan Field Cap Sekolah dan juga hapus juga pilihan tanda tangan
 * nya ... Hapus permanen semuanya").
 *
 * Fitur ini (ditambahkan di migration
 * 2026_11_14_010000_add_tanda_tangan_dan_cap_sekolah_to_profil_sekolah_table)
 * dibatalkan total setelah berulang kali bermasalah saat dicetak ke PDF
 * (tanda tangan/cap tidak konsisten muncul, diduga akibat cache view/
 * opcache PHP pada server dev user, bukan cacat kode) - user memutuskan
 * untuk membatalkan fitur daripada melanjutkan debug. Migration lama
 * SENGAJA TIDAK diubah/dihapus (sudah pernah dijalankan) - migration BARU
 * ini yang membalikkan (drop) kolomnya, sesuai konvensi Laravel.
 *
 * Menghapus migration lama dari tabel migrations juga TIDAK dilakukan di
 * sini - cukup jalankan migration ini dengan `php artisan migrate`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->dropColumn(['tanda_tangan_kepala_sekolah', 'cap_sekolah']);
        });
    }

    public function down(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->string('tanda_tangan_kepala_sekolah')->nullable()->after('logo_sekolah');
            $table->string('cap_sekolah')->nullable()->after('tanda_tangan_kepala_sekolah');
        });
    }
};
