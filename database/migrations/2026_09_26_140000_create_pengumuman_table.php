<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel Pengumuman - permintaan user (2026-09-26): menu baru "Pengumuman"
 * (Superadmin) berisi Judul, Isi, Tanggal Aktif, Tanggal Non Aktif. Tampil
 * di landing page (beranda) sebagai running text di antara nama aplikasi
 * dan tombol Masuk - lihat resources/views/layouts/beranda.blade.php.
 *
 * "Aktif" murni ditentukan dari tanggal (hari ini di antara tanggal_aktif
 * & tanggal_nonaktif, INKLUSIF kedua ujungnya) - TIDAK ada kolom
 * status/toggle terpisah, sesuai permintaan eksplisit user ("superadmin
 * bisa atur mulai & akhir pengumuman nya melalui field Tanggal
 * Pengumuman Aktif, Tanggal Pengumuman Non Aktif").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengumuman', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('isi');
            $table->date('tanggal_aktif');
            $table->date('tanggal_nonaktif');
            $table->timestamps();

            $table->index(['tanggal_aktif', 'tanggal_nonaktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumuman');
    }
};
