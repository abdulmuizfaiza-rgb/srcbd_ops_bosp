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
        Schema::table('pendataan_bosp', function (Blueprint $table) {
            $table->foreignId('profil_sekolah_id')->nullable()->after('id')->unique()->constrained('profil_sekolah')->cascadeOnDelete();
            $table->string('nuptk')->nullable();
            $table->string('nama')->nullable();
            $table->string('nip')->nullable();
            $table->string('jk', 1)->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('status_kepegawaian')->nullable();
            $table->string('pendidikan_terakhir')->nullable();
            $table->string('jurusan')->nullable();
            $table->string('nama_perguruan_tinggi')->nullable();
            $table->string('no_whatsapp', 12)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pendataan_bosp', function (Blueprint $table) {
            $table->dropConstrainedForeignId('profil_sekolah_id');
            $table->dropColumn([
                'nuptk', 'nama', 'nip', 'jk', 'tempat_lahir', 'tanggal_lahir',
                'status_kepegawaian', 'pendidikan_terakhir', 'jurusan',
                'nama_perguruan_tinggi', 'no_whatsapp',
            ]);
        });
    }
};
