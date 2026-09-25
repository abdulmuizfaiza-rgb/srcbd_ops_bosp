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
            $table->string('status')->nullable()->after('nama_sekolah');
            $table->string('kecamatan')->nullable()->after('status');
            $table->unique('npsn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->dropUnique(['npsn']);
            $table->dropColumn(['status', 'kecamatan']);
        });
    }
};
