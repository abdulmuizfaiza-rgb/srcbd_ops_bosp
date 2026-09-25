<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah field Saldo TW 1-4 di Tab 2 "Tarik Tunai BOSP" menu Dana BOSP
 * Tahap 1 & 2 (permintaan user 2026-09-16, Part 31).
 *
 * Untuk TIAP TW (1-4), ditambahkan 3 kolom:
 * - saldo_kas_bank_twN   (manual, Rupiah)
 * - saldo_kas_tunai_twN  (manual, Rupiah)
 * - saldo_twN            (HASIL RUMUS: saldo_kas_bank_twN + saldo_kas_tunai_twN,
 *   TIDAK PERNAH bisa diedit manual - lihat App\Models\DanaBospTahap::hitungSaldoTw())
 *
 * Posisi tampilan (bukan urutan kolom database): Saldo TW N ditampilkan
 * sejajar di sebelah KANAN field Tarik Tunai TW N yang sudah ada (lihat
 * resources/views/livewire/pendataan-bosp/dana-bosp-tahap/index.blade.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dana_bosp_tahap', function (Blueprint $table) {
            foreach ([1, 2, 3, 4] as $tw) {
                $table->bigInteger("saldo_kas_bank_tw{$tw}")->nullable()->after("tarik_tunai_tw{$tw}");
                $table->bigInteger("saldo_kas_tunai_tw{$tw}")->nullable()->after("saldo_kas_bank_tw{$tw}");
                $table->bigInteger("saldo_tw{$tw}")->nullable()->after("saldo_kas_tunai_tw{$tw}");
            }
        });
    }

    public function down(): void
    {
        Schema::table('dana_bosp_tahap', function (Blueprint $table) {
            foreach ([1, 2, 3, 4] as $tw) {
                $table->dropColumn(["saldo_kas_bank_tw{$tw}", "saldo_kas_tunai_tw{$tw}", "saldo_tw{$tw}"]);
            }
        });
    }
};
