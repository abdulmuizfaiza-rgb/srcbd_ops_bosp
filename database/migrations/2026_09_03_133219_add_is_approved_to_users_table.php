<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Default true, karena akun yang dibuat manual oleh Superadmin lewat
     * menu Pengguna dianggap sudah otomatis disetujui. Hanya akun hasil
     * registrasi mandiri (Admin OPS/Admin BOSP) yang dibuat dengan false,
     * menunggu persetujuan Superadmin.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_approved')->default(true)->after('must_change_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_approved');
        });
    }
};
