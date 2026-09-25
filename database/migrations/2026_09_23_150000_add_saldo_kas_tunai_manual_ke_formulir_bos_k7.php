<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Formulir BOS K7b - permintaan user 2026-09-23 (round kedua): field
 * "Saldo Kas Tunai" di tab K7b harus bisa diisi manual. Jawaban
 * AskUserQuestion 2026-09-23 (round kedua) mengonfirmasi PERILAKU:
 * "Tetap dari rincian, manual cuma cadangan" - artinya Saldo Kas Tunai
 * TETAP dihitung otomatis dari total rincian lembar+keping uang SELAMA
 * rincian itu diisi (total > 0); kotak manual di kolom baru ini HANYA
 * dipakai sebagai fallback kalau rincian pecahan uang masih kosong
 * (total = 0). Lihat App\Models\FormulirBosK7::hitungSaldoKasTunai()
 * untuk logika fallback-nya.
 *
 * Kolom baru terpisah (BUKAN mengubah migration `create_formulir_bos_k7`
 * yang sudah berjalan) - sesuai aturan standing "jangan merubah yang
 * sudah berfungsi dan sudah berjalan".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formulir_bos_k7', function (Blueprint $table) {
            $table->bigInteger('saldo_kas_tunai_manual')->default(0)->after('saldo_rekening_bank');
        });
    }

    public function down(): void
    {
        Schema::table('formulir_bos_k7', function (Blueprint $table) {
            $table->dropColumn('saldo_kas_tunai_manual');
        });
    }
};
