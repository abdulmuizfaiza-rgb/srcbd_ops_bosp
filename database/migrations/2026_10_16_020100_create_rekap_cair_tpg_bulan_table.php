<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel DETAIL (1 baris per PTK per sekolah+tahun+triwulan) - menyimpan
 * status cair TPG tiap bulan (Januari-Desember) utk tab "Data Cair/
 * Belum TPG" - lihat migration create_rekap_cair_tpg_table.php utk
 * konteks lengkap (header) & App\Models\SuratTpg::JENIS_REKAP_CAIR_TPG.
 *
 * Kolom nama bulan memakai ejaan "Pebruari"/"Nopember" (bukan "Februari"/
 * "November") - mengikuti PERSIS ejaan pada gambar contoh yang diupload
 * user (ejaan baku administrasi pemerintahan lama), BUKAN typo.
 *
 * CATATAN SYNC LOKAL/ONLINE (mengikuti pola aman App\Models\
 * PenerimaanHonorPtk & App\Models\LembarValidasiTpgCeklist): kunci unik
 * tabel ini memakai (profil_sekolah_id, tahun, triwulan, nuptk) - BUKAN
 * foreign key ke `lampiran_2a.id` atau `data_ptk.id` - krn
 * App\Services\SyncEngine hanya punya mekanisme penerjemahan utk
 * profil_sekolah_id (lewat NPSN), tidak utk foreign key lain yang
 * menunjuk ID auto-increment modul lain. NUPTK aman dipakai sbg kunci
 * krn wajib diisi & selalu 16 digit pada Lampiran2a (sumber daftar PTK
 * tab ini).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_cair_tpg_bulan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('triwulan');
            $table->string('nuptk', 16);
            $table->string('bulan_januari', 30)->nullable();
            $table->string('bulan_pebruari', 30)->nullable();
            $table->string('bulan_maret', 30)->nullable();
            $table->string('bulan_april', 30)->nullable();
            $table->string('bulan_mei', 30)->nullable();
            $table->string('bulan_juni', 30)->nullable();
            $table->string('bulan_juli', 30)->nullable();
            $table->string('bulan_agustus', 30)->nullable();
            $table->string('bulan_september', 30)->nullable();
            $table->string('bulan_oktober', 30)->nullable();
            $table->string('bulan_nopember', 30)->nullable();
            $table->string('bulan_desember', 30)->nullable();
            $table->timestamps();

            $table->unique(['profil_sekolah_id', 'tahun', 'triwulan', 'nuptk'], 'rekap_cair_tpg_bulan_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_cair_tpg_bulan');
    }
};
