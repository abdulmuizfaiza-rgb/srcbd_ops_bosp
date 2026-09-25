<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menu "Format Surat Rekomendasi & Pembatalan TPG" (Pendataan OPS) -
 * permintaan user 2026-09-24 (round kedua puluh satu, tambahan tab 3
 * "Surat Pernyataan", disertai 1 gambar contoh format).
 *
 * Kolom BARU `tahun_pelajaran` (string, nullable) pada tabel `surat_tpg`
 * yang sudah ada - dipakai HANYA oleh jenis surat BARU
 * SuratTpg::JENIS_PERNYATAAN ("2025/2026" dst, contoh dari gambar), diisi
 * MANUAL oleh Admin OPS & tersimpan otomatis (permintaan user eksplisit:
 * "untuk tahun pelajaran di isi manual oleh admin ops dan tersimpan
 * otomatis"), NULL untuk baris jenis Rekomendasi/Penghentian (field ini
 * tidak relevan/tidak tampil pada kedua surat itu). Ditambahkan sebagai
 * kolom pada tabel yang sudah ada (bukan tabel baru) krn `surat_tpg`
 * sudah didesain 1 baris = 1 sekolah + 1 tahun + 1 triwulan + 1 jenis
 * surat, & Surat Pernyataan mengikuti pola sama persis (tahun+triwulan
 * otomatis dari filter, HANYA field ini yang diketik manual - mirip
 * `nomor_surat` pada 2 jenis surat lain).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_tpg', function (Blueprint $table) {
            $table->string('tahun_pelajaran')->nullable()->after('nomor_surat');
        });
    }

    public function down(): void
    {
        Schema::table('surat_tpg', function (Blueprint $table) {
            $table->dropColumn('tahun_pelajaran');
        });
    }
};
