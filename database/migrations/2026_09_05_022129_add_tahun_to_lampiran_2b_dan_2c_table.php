<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom "tahun" ke Lampiran 2b & Lampiran 2c - menyusul
     * Lampiran 2a yang sudah lebih dulu punya kolom ini (lihat migration
     * 2026_09_04_001137_create_lampiran_2a_table). Dibutuhkan supaya menu
     * Unduhan (2026-09-05) bisa memfilter ketiga lampiran sekaligus
     * berdasarkan Triwulan DAN Tahun secara konsisten.
     *
     * Data yang SUDAH ADA (dibuat sebelum kolom ini ada) di-backfill
     * memakai tahun dari created_at baris tersebut (bukan disamaratakan
     * "tahun sekarang") supaya lebih akurat mencerminkan kapan data itu
     * sebenarnya diinput - dilakukan lewat loop PHP (bukan SQL mentah)
     * supaya portable baik di PostgreSQL (produksi) maupun SQLite
     * (test/smoke).
     */
    public function up(): void
    {
        Schema::table('lampiran_2b', function (Blueprint $table) {
            $table->unsignedSmallInteger('tahun')->nullable()->after('triwulan');
        });

        Schema::table('lampiran_2c', function (Blueprint $table) {
            $table->unsignedSmallInteger('tahun')->nullable()->after('triwulan');
        });

        $this->backfillTahun('lampiran_2b');
        $this->backfillTahun('lampiran_2c');
    }

    private function backfillTahun(string $table): void
    {
        DB::table($table)->orderBy('id')->chunkById(500, function ($baris) use ($table) {
            foreach ($baris as $item) {
                DB::table($table)
                    ->where('id', $item->id)
                    ->update(['tahun' => Carbon::parse($item->created_at)->year]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lampiran_2b', function (Blueprint $table) {
            $table->dropColumn('tahun');
        });

        Schema::table('lampiran_2c', function (Blueprint $table) {
            $table->dropColumn('tahun');
        });
    }
};
