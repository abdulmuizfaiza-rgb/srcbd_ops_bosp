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
        Schema::create('lembar_validasi_tpg', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('triwulan');
            $table->date('tanggal_verifikasi')->nullable();
            $table->text('catatan_hasil_verifikasi')->nullable();
            $table->timestamps();
            $table->unique(['profil_sekolah_id', 'tahun', 'triwulan'], 'lembar_validasi_tpg_unik');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lembar_validasi_tpg');
    }
};
