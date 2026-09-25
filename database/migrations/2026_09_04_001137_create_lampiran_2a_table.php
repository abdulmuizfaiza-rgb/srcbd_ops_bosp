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
        Schema::create('lampiran_2a', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedTinyInteger('triwulan');
            $table->unsignedSmallInteger('tahun');
            $table->string('nrg', 12);
            $table->string('nuptk', 16);
            $table->string('nama_ptk');
            $table->string('status_kepegawaian');
            $table->unsignedBigInteger('gaji_pokok_januari');
            $table->string('npwp', 16);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['profil_sekolah_id', 'triwulan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lampiran_2a');
    }
};
