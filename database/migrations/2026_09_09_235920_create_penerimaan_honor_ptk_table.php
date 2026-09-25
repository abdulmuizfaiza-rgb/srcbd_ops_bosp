<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penerimaan Honor PTK - BEDA dengan Rekap RKAS (1 baris tetap per
 * sekolah per tahun): 1 sekolah bisa punya BANYAK baris (1 baris per
 * PTK penerima honor), per tahun & per triwulan (4 triwulan) - sesuai
 * jawaban AskUserQuestion 2026-09-09 ("Banyak baris per sekolah").
 *
 * NPSN & Nama Sekolah SENGAJA TIDAK disimpan di tabel ini (walau
 * diminta tampil "otomatis dari field NPSN/Nama Sekolah pada menu
 * Profil Sekolah") - cukup relasi profil_sekolah_id, nilainya diambil
 * langsung dari tabel profil_sekolah saat ditampilkan, supaya selalu
 * sinkron & tidak ada data terduplikasi/bisa basi.
 *
 * Kolom jumlah_honor = HASIL RUMUS (Volume x Tarif Harga, lihat
 * App\Models\PenerimaanHonorPtk::hitungJumlahHonor()) - selalu dihitung
 * otomatis oleh sistem, bukan input manual.
 *
 * unique(profil_sekolah_id, tahun, triwulan, nuptk) - dipakai sebagai
 * kunci pencocokan saat import Excel diulang (sesuai jawaban
 * AskUserQuestion: "Update baris yang sudah ada") - baris tanpa NUPTK
 * (null) TIDAK ikut kena unique constraint ini (PostgreSQL memperlakukan
 * NULL sebagai selalu berbeda), jadi baris tanpa NUPTK akan selalu
 * dianggap baris baru saat diimpor ulang (tidak ada kunci pencocokan
 * yang bisa diandalkan tanpa NUPTK).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('penerimaan_honor_ptk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('triwulan');
            $table->string('nuptk', 16)->nullable();
            $table->string('nama_penerima')->nullable();
            $table->integer('volume')->nullable();
            $table->string('satuan', 50)->nullable();
            $table->bigInteger('tarif_harga')->nullable();
            $table->bigInteger('jumlah_honor')->nullable();
            $table->date('tanggal_bayar')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['profil_sekolah_id', 'tahun', 'triwulan', 'nuptk']);
            $table->index(['tahun', 'triwulan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penerimaan_honor_ptk');
    }
};
