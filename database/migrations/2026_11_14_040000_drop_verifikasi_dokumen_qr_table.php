<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penghapusan PERMANEN tabel verifikasi_dokumen_qr - bagian dari
 * pembatalan total fitur Tanda Tangan Kepala Sekolah & Cap Sekolah
 * (permintaan user 2026-11-14, "Hapus permanen semuanya") - lihat
 * docblock 2026_11_14_030000_drop_tanda_tangan_dan_cap_sekolah_from_profil_sekolah_table
 * untuk konteks lengkap.
 *
 * Tabel ini (dibuat di migration
 * 2026_11_14_020000_create_verifikasi_dokumen_qr_table) HANYA dipakai
 * oleh mode "Tanda Tangan & Cap berupa QR Code" yang sekarang sudah
 * dihapus seluruhnya dari aplikasi (controller, service, route, &
 * halaman verifikasi publiknya juga ikut dihapus).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('verifikasi_dokumen_qr');
    }

    public function down(): void
    {
        Schema::create('verifikasi_dokumen_qr', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('jenis_dokumen');
            $table->foreignId('profil_sekolah_id')->nullable()->constrained('profil_sekolah')->nullOnDelete();
            $table->string('nama_sekolah');
            $table->string('nama_kepala_sekolah')->nullable();
            $table->unsignedSmallInteger('tahun')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
};
