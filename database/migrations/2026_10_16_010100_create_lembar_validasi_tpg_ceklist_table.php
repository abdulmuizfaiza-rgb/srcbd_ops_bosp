<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lembar_validasi_tpg_ceklist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('triwulan');
            // NUPTK (BUKAN lampiran_2a_id) sengaja dipakai sebagai kunci
            // identitas PTK - lihat docblock App\Models\
            // LembarValidasiTpgCeklist utk alasan lengkap (ID auto-
            // increment baris lampiran_2a TIDAK BISA dipakai sbg kunci
            // krn tidak nyambung antara database Lokal & Online, lihat
            // App\Services\SyncEngine - pola persis sama dgn kunci unik
            // alami App\Models\PenerimaanHonorPtk yang sudah ada).
            $table->string('nuptk', 16);
            $table->boolean('memiliki_sertifikat_pendidik')->default(false);
            $table->boolean('berstatus_guru_asn')->default(false);
            $table->boolean('aktif_melaksanakan_tugas_dapodik')->default(false);
            $table->boolean('memiliki_nuptk_nrg')->default(false);
            $table->boolean('memenuhi_beban_kerja')->default(false);
            $table->boolean('mengajar_linier_sertifikat')->default(false);
            $table->boolean('bukan_pegawai_tetap_instansi_lain')->default(false);
            $table->boolean('berstatus_valid_info_gtk')->default(false);
            $table->timestamps();
            $table->unique(['profil_sekolah_id', 'tahun', 'triwulan', 'nuptk'], 'lembar_validasi_tpg_ceklist_unik');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lembar_validasi_tpg_ceklist');
    }
};
