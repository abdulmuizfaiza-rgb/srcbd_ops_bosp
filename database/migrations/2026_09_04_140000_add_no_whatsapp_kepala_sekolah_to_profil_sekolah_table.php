<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Field No Whatsapp Kepala Sekolah (12 digit angka), posisi setelah
     * NIP Kepala Sekolah sesuai permintaan user.
     */
    public function up(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->string('no_whatsapp_kepala_sekolah')->nullable()->after('nip_kepala_sekolah');
        });
    }

    public function down(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->dropColumn('no_whatsapp_kepala_sekolah');
        });
    }
};
