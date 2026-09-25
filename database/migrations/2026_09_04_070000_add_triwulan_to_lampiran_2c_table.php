<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lampiran_2c', function (Blueprint $table) {
            $table->unsignedTinyInteger('triwulan')->default(1)->after('profil_sekolah_id');
            $table->index(['profil_sekolah_id', 'triwulan']);
        });
    }

    public function down(): void
    {
        Schema::table('lampiran_2c', function (Blueprint $table) {
            $table->dropIndex(['profil_sekolah_id', 'triwulan']);
            $table->dropColumn('triwulan');
        });
    }
};
