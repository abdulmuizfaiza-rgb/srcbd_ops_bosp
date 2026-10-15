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
            $table->string('nama_kasie_kpml')->nullable()->after('nip_pengawas');
            $table->string('nip_kasie_kpml')->nullable()->after('nama_kasie_kpml');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->dropColumn(['nama_kasie_kpml', 'nip_kasie_kpml']);
        });
    }
};
