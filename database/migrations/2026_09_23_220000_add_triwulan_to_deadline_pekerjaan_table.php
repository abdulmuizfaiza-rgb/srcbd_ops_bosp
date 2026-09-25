<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah dimensi Triwulan ke `deadline_pekerjaan` - Round kesembilan
 * bagian B (permintaan user 2026-09-23, poin 3): "pada menu Timeline
 * Pekerjaan tambahkan pilihan triwulan, jadi pengaturan timeline
 * berdasarkan triwulan."
 *
 * Jawaban AskUserQuestion 2026-09-23 "Cakupan triwulan" -> "Semua 16
 * tahap kerja (Recommended)": SETIAP dari 16 tahap kerja (termasuk yang
 * sebelumnya tahunan saja seperti Lampiran OPS 2a/2b/2c, Rekap RKAS,
 * Dana BOSP Tahap) SEKARANG punya SAMPAI 4 baris deadline terpisah per
 * tahun (1 per triwulan) - BUKAN keputusan bahwa menu-menu itu "harus"
 * diisi 4x setahun (itu urusan menu masing-masing, tidak berubah),
 * murni supaya Superadmin punya fleksibilitas memberi deadline berbeda
 * per triwulan untuk SEMUA tahap kerja secara konsisten (lihat juga
 * fitur "terapkan ke semua tahap kerja" pada
 * App\Livewire\TimelinePekerjaan\Index::terapkanSemua() - keputusan UI
 * TEKNIS, bukan ditanyakan eksplisit ke user, lihat PETUNJUK.txt paket
 * update ini).
 *
 * Kolom `triwulan` (1-4) ditambahkan TIDAK NULLABLE - unique constraint
 * lama `[tahun, kunci_menu]` diganti `[tahun, kunci_menu, triwulan]`.
 * Baris LAMA (dari Round 8B, sebelum kolom ini ada) DIBACKFILL ke
 * `triwulan = 1` supaya tidak hilang (deadline yang sudah diatur
 * Superadmin sebelumnya tetap kelihatan, sekarang sebagai deadline
 * Triwulan 1 - kalau memang dimaksudkan berlaku sepanjang tahun,
 * Superadmin tinggal memakai tombol "terapkan ke semua tahap kerja"
 * per triwulan lain, dijelaskan di PETUNJUK.txt). Ini keputusan teknis
 * migrasi data (bukan aturan bisnis baru) - satu-satunya pilihan yang
 * tidak diam-diam MENGHILANGKAN data yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deadline_pekerjaan', function (Blueprint $table) {
            $table->dropUnique(['tahun', 'kunci_menu']);
            $table->unsignedTinyInteger('triwulan')->nullable()->after('kunci_menu');
        });

        // Backfill baris lama (dari sebelum kolom ini ada) ke Triwulan 1 -
        // lihat docblock migration ini untuk alasannya.
        DB::table('deadline_pekerjaan')->whereNull('triwulan')->update(['triwulan' => 1]);

        Schema::table('deadline_pekerjaan', function (Blueprint $table) {
            $table->unsignedTinyInteger('triwulan')->nullable(false)->change();
            $table->unique(['tahun', 'kunci_menu', 'triwulan']);
        });
    }

    public function down(): void
    {
        Schema::table('deadline_pekerjaan', function (Blueprint $table) {
            $table->dropUnique(['tahun', 'kunci_menu', 'triwulan']);
            $table->dropColumn('triwulan');
            $table->unique(['tahun', 'kunci_menu']);
        });
    }
};
