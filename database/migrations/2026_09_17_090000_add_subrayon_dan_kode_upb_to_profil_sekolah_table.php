<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah field "Subrayon" & "Kode UPB" ke Profil Sekolah (permintaan user
 * 2026-09-17, Part 32) - dibutuhkan sebagai 2 dari 29 kolom menu baru
 * "Laporan Realisasi BOSP (Form BPK)" (lihat migration
 * create_laporan_realisasi_bosp_table & App\Models\LaporanRealisasiBosp).
 *
 * Sama seperti "Kecamatan" yang sudah ada lebih dulu, KEDUA field ini
 * adalah atribut TETAP PER SEKOLAH (bukan per triwulan/per baris),
 * sehingga disimpan di profil_sekolah - bukan sebagai kolom manual di
 * tabel laporan_realisasi_bosp - sesuai jawaban AskUserQuestion
 * 2026-09-17 ("Tambahkan ke Profil Sekolah (Recommended)" untuk
 * keduanya).
 *
 * "kode_upb" DI SINI beda dengan kolom "kode_upb" yang sudah ada lebih
 * dulu di tabel lain (mis. rincian_belanja_modal) - di sana itu kode
 * PER BARANG inventaris, di sini kode UPB SEKOLAH (Unit Pengelola
 * Barang) - kebetulan sama nama kolomnya, beda tabel & beda arti,
 * TIDAK ADA hubungan/relasi.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->string('kode_upb')->nullable()->after('npsn');
            $table->string('subrayon')->nullable()->after('kecamatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->dropColumn(['kode_upb', 'subrayon']);
        });
    }
};
