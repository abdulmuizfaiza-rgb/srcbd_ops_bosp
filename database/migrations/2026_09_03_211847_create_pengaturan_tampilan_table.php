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
        Schema::create('pengaturan_tampilan', function (Blueprint $table) {
            $table->id();
            $table->string('background_landing')->nullable();
            $table->string('background_login')->nullable();
            $table->string('warna_halaman', 20)->default('#f1f5f9');
            $table->string('warna_menu', 20)->default('#0f172a');
            $table->string('ukuran_huruf_halaman', 10)->default('sedang');
            $table->string('ukuran_huruf_menu', 10)->default('sedang');
            $table->string('jenis_huruf_halaman', 40)->default('Figtree');
            $table->string('jenis_huruf_menu', 40)->default('Figtree');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturan_tampilan');
    }
};
