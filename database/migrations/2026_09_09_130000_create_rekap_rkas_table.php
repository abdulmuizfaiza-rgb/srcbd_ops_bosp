<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rekap RKAS Awal-Perubahan - 1 baris per sekolah PER TAHUN (sesuai
 * jawaban AskUserQuestion 2026-09-09: dilacak per Tahun seperti Lampiran
 * 2a, bukan 1 data tetap per sekolah seperti Identitas Admin BOSP).
 *
 * Kolom-kolom "hasil rumus" (kolom Jml Sesudah & Selisih pada setiap
 * kategori Belanja, serta kolom Sesudah & Selisih pada baris JUMLAH)
 * untuk SEMENTARA dibuat sebagai kolom input manual biasa juga - sesuai
 * jawaban AskUserQuestion 2026-09-09 poin 2 ("Jadikan input manual
 * sementara") - karena rumus penghitungannya akan ditentukan user di
 * round berikutnya, setelah menu ini jadi.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rekap_rkas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->bigInteger('anggaran_bosp')->nullable();

            foreach (['pegawai', 'pemeliharaan', 'barjas', 'peralatan_mesin', 'aset_lainnya'] as $kategori) {
                $table->bigInteger($kategori.'_sebelum')->nullable();
                $table->bigInteger($kategori.'_realisasi_tahap1')->nullable();
                $table->bigInteger($kategori.'_perubahan_tahap2')->nullable();
                $table->bigInteger($kategori.'_jml_sesudah')->nullable();
                $table->bigInteger($kategori.'_selisih')->nullable();
            }

            $table->bigInteger('jumlah_sebelum')->nullable();
            $table->bigInteger('jumlah_sesudah')->nullable();
            $table->bigInteger('jumlah_selisih')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['profil_sekolah_id', 'tahun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap_rkas');
    }
};
