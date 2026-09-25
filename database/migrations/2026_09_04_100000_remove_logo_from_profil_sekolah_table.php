<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Field Logo Sekolah & Logo Pemda dihapus dari menu Profil Sekolah -
     * sudah tidak dipakai di aplikasi maupun hasil export Lampiran manapun.
     */
    public function up(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->dropColumn(['logo_sekolah', 'logo_pemda']);
        });
    }

    public function down(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->string('logo_sekolah')->nullable()->after('alamat_sekolah');
            $table->string('logo_pemda')->nullable()->after('logo_sekolah');
        });
    }
};
