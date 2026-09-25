<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lampiran_2c', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->string('nrg', 12);
            $table->string('nuptk', 16);
            $table->string('nama_ptk');
            $table->string('kecamatan');
            $table->string('jenis_kepangkatan');
            $table->string('golongan');
            $table->string('masa_kerja');
            $table->string('pangkat_berkala');
            $table->date('tmt');
            $table->unsignedBigInteger('gaji_pokok_lama');
            $table->unsignedBigInteger('gaji_pokok_baru');
            $table->text('keterangan');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('profil_sekolah_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lampiran_2c');
    }
};
