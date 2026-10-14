<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom `npwp` pada tabel `data_ptk` - permintaan user 2026-10-14
 * poin 1: "pada menu data PTK tambahkan field NPWP dengan posisi setelah
 * field Tahun Lulus Ijazah. dan field NPWP pada lampiran 2a otomatis
 * diambil dari tab data PTK."
 *
 * Nullable (opsional) - konsisten dengan field lain yang bersifat
 * biodata tambahan di tabel ini (nuptk, nip, dll), BUKAN field wajib
 * seperti nik. Validasi format (15-16 digit angka) ditempatkan di
 * App\Livewire\ProfilSekolah\Index::simpanPtk(), mengikuti regex yang
 * sudah dipakai NPWP pada Lampiran 2a
 * (App\Livewire\PendataanOps\Lampiran2a\Index).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_ptk', function (Blueprint $table) {
            $table->string('npwp')->nullable()->after('tahun_lulus_ijazah');
        });
    }

    public function down(): void
    {
        Schema::table('data_ptk', function (Blueprint $table) {
            $table->dropColumn('npwp');
        });
    }
};
