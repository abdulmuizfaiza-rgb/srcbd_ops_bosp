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
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->string('status_kepegawaian_kepsek')->nullable()->after('nip_kepala_sekolah');
            $table->string('nama_bendahara')->nullable()->after('status_kepegawaian_kepsek');
            $table->string('nip_bendahara')->nullable()->after('nama_bendahara');
            $table->string('status_kepegawaian_bendahara')->nullable()->after('nip_bendahara');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->dropColumn([
                'status_kepegawaian_kepsek',
                'nama_bendahara',
                'nip_bendahara',
                'status_kepegawaian_bendahara',
            ]);
        });
    }
};
