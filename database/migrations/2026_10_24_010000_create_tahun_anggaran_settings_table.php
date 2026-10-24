<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permintaan user 2026-10-24: Superadmin mengatur "Tahun Anggaran Aktif"
 * secara global untuk SELURUH Admin OPS/Admin BOSP, supaya mereka tidak
 * salah input data di tahun yang salah. Satu baris/singleton (pola SAMA
 * seperti App\Models\SyncSetting/PengaturanTampilan/GoogleDriveSetting -
 * lihat docblock model App\Models\TahunAnggaranSetting untuk detail).
 *
 * Nilai `tahun_aktif` dipakai sebagai tahun default yang langsung dituju
 * saat Admin OPS/Admin BOSP berhasil login (lihat halaman baru
 * resources/views/livewire/pages/auth/pilih-tahun.blade.php), dan sebagai
 * acuan "tahun sebelumnya" yang bisa diminta izin aksesnya lewat
 * App\Models\PermintaanAksesTahun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_anggaran_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun_aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahun_anggaran_settings');
    }
};
