<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nama Yayasan & Logo Sekolah - permintaan user 2026-10-15, dipakai
 * pertama kali oleh tab "Cover SMPN"/"Cover SMP-S" pada menu "Format
 * Surat Rekomendasi & Pembatalan TPG" (App\Livewire\PendataanOps\
 * SuratTpg\Index) - "Nama Yayasan" otomatis ditampilkan di Cover SMP-S
 * (khusus sekolah Swasta), "Logo Sekolah" otomatis ditampilkan di
 * TENGAH halaman pada KEDUA cover (Negeri maupun Swasta).
 *
 * CATATAN PENTING soal nama kolom `logo_sekolah`: kolom dengan nama SAMA
 * PERSIS pernah ada sebelumnya lalu DIHAPUS lewat migration
 * `2026_09_04_100000_remove_logo_from_profil_sekolah_table` karena saat
 * itu "sudah tidak dipakai di aplikasi maupun hasil export Lampiran
 * manapun". Ini BUKAN kebalikan dari keputusan itu secara diam-diam -
 * kolom lama itu generik & tidak terpakai, sedangkan kolom baru ini
 * punya kebutuhan SPESIFIK & terkonfirmasi (dikonfirmasi user via
 * AskUserQuestion 2026-10-15: "Field upload baru Logo Sekolah di Profil
 * Sekolah, terpisah dari Kop Surat"). Kolom `logo_pemda` yang dulu juga
 * ikut dihapus TIDAK ikut dibuat ulang di sini - tidak ada permintaan
 * untuk itu.
 *
 * Diupload/dihapus lewat menu Profil Sekolah (App\Livewire\ProfilSekolah\
 * Index::updatedLogoSekolahBaru()/hapusLogoSekolah()), disimpan ke disk
 * `public` (pola sama seperti `kop_surat`) - lihat juga
 * App\Models\ProfilSekolah::logoSekolahDataUri() &
 * App\Http\Controllers\LogoSekolahFileController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->string('nama_yayasan')->nullable()->after('alamat_sekolah');
            $table->string('logo_sekolah')->nullable()->after('nama_yayasan');
        });
    }

    public function down(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->dropColumn(['nama_yayasan', 'logo_sekolah']);
        });
    }
};
