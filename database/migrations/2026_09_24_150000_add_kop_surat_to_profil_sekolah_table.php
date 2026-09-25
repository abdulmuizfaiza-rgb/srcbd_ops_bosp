<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kop Surat (gambar kop/kepala surat sekolah, diupload manual oleh
 * Admin OPS) - permintaan user 2026-09-24 (round kesembilan belas),
 * dipakai pertama kali oleh menu "Format Surat Rekomendasi & Pembatalan
 * TPG" (App\Livewire\PendataanOps\SuratTpg\Index) untuk menggantikan
 * placeholder "LOGO"+"KOP SEKOLAH" bawaan round sebelumnya.
 *
 * SENGAJA disimpan di tabel `profil_sekolah` (bukan tabel `surat_tpg`)
 * krn kop surat adalah aset TETAP milik sekolah (tidak berubah per
 * tahun/triwulan/jenis surat) - supaya kalau nanti ada menu cetak surat
 * lain yang butuh kop yang sama, tinggal dipakai ulang tanpa upload
 * ulang. INI BUKAN mengaktifkan kembali kolom `logo_sekolah`/`logo_pemda`
 * yang sudah sengaja dihapus (lihat migration
 * `remove_logo_from_profil_sekolah_table`) - itu dulu untuk logo generik
 * yang tidak jadi dipakai; `kop_surat` ini kolom baru & terpisah, untuk
 * kebutuhan spesifik yang baru diminta user sekarang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->string('kop_surat')->nullable()->after('alamat_sekolah');
        });
    }

    public function down(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->dropColumn('kop_surat');
        });
    }
};
