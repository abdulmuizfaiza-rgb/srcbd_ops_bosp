<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Field Photo OPS (ukuran 2x3, otomatis di-crop sistem) dan Upload SK
     * OPS Terbaru (PDF, maks 1 MB) pada menu Identitas OPS.
     */
    public function up(): void
    {
        Schema::table('pendataan_ops', function (Blueprint $table) {
            $table->string('foto_ops')->nullable()->after('no_whatsapp');
            $table->string('sk_ops')->nullable()->after('foto_ops');
        });
    }

    public function down(): void
    {
        Schema::table('pendataan_ops', function (Blueprint $table) {
            $table->dropColumn(['foto_ops', 'sk_ops']);
        });
    }
};
