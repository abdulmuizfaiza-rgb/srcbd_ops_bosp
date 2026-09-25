<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah pengaturan warna, ukuran, & jenis huruf KHUSUS untuk form
     * Registrasi Admin OPS/Admin BOSP (2026-09-05) - sebelumnya form ini
     * memakai warna/ukuran/jenis huruf hardcode (text-slate-*), tidak ikut
     * bisa diatur lewat menu Tampilan seperti menu/landing/login.
     *
     * Nilai default SENGAJA disamakan dengan tampilan hardcode yang sudah
     * ada sebelumnya (warna ~ slate-700, ukuran sedang, jenis Figtree)
     * supaya TIDAK ADA PERUBAHAN TAMPILAN sebelum Superadmin sengaja
     * mengubahnya sendiri di menu Tampilan - sama seperti pola warna huruf
     * menu/landing/login sebelumnya.
     */
    public function up(): void
    {
        Schema::table('pengaturan_tampilan', function (Blueprint $table) {
            $table->string('warna_huruf_registrasi')->default('#334155')->after('warna_huruf_login');
            $table->string('ukuran_huruf_registrasi')->default('sedang')->after('ukuran_huruf_menu');
            $table->string('jenis_huruf_registrasi')->default('Figtree')->after('jenis_huruf_menu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengaturan_tampilan', function (Blueprint $table) {
            $table->dropColumn(['warna_huruf_registrasi', 'ukuran_huruf_registrasi', 'jenis_huruf_registrasi']);
        });
    }
};
