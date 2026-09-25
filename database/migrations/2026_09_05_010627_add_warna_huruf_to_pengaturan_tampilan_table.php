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
        Schema::table('pengaturan_tampilan', function (Blueprint $table) {
            // Warna huruf (teks) menu sidebar, teks landing page (pilihan
            // jenis akses), dan teks form login - terpisah dari warna_menu
            // (warna LATAR BELAKANG menu) yang sudah ada sebelumnya.
            $table->string('warna_huruf_menu')->default('#cbd5e1')->after('warna_menu');
            $table->string('warna_huruf_landing')->default('#334155')->after('warna_huruf_menu');
            $table->string('warna_huruf_login')->default('#334155')->after('warna_huruf_landing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengaturan_tampilan', function (Blueprint $table) {
            $table->dropColumn(['warna_huruf_menu', 'warna_huruf_landing', 'warna_huruf_login']);
        });
    }
};
