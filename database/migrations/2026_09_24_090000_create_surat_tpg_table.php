<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menu "Format Surat Rekomendasi & Pembatalan TPG" (Pendataan OPS) -
 * permintaan user 2026-09-24 (round kedelapan belas), disertai 2 gambar
 * contoh format surat (Rekomendasi & Penghentian TPG).
 *
 * Keputusan bisnis yang SENGAJA ditanyakan lewat AskUserQuestion
 * (2026-09-24) sebelum menulis migration ini - semua jawaban user
 * memilih opsi yang direkomendasikan:
 * - "Simpan Data" -> "Disimpan ke database (Recommended)": Nomor Surat &
 *   Tanggal Surat yang diketik user DISIMPAN per
 *   sekolah+tahun+triwulan+jenis surat (bukan hanya diisi ulang tiap
 *   kali cetak) - itulah tabel ini.
 * - "Lampiran Guru" -> "Di luar cakupan (Recommended)": TIDAK ada tabel
 *   data guru baru - surat hanya surat pengantar, tidak menyimpan daftar
 *   nama-nama guru penerima/dihentikan TPG.
 * - "Kondisi/Alasan" -> "Semua teks tetap (Recommended)": daftar 4
 *   kondisi (Rekomendasi) & 10 alasan (Penghentian) SELALU ditampilkan
 *   semua sbg teks baku (sesuai gambar contoh) - TIDAK ada kolom
 *   checkbox/pilihan yang perlu disimpan di tabel ini.
 *
 * SATU baris = 1 sekolah + 1 tahun + 1 triwulan + 1 jenis surat (unik) -
 * `jenis` membedakan 2 tab (SuratTpg::JENIS_REKOMENDASI /
 * JENIS_PENGHENTIAN) krn keduanya "dibuat berdasarkan tahun dan
 * triwulan" yang SAMA (permintaan user eksplisit) tapi punya Nomor
 * Surat & Tanggal Surat SENDIRI-SENDIRI (2 dokumen berbeda).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_tpg', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('triwulan');
            $table->string('jenis');
            $table->string('nomor_surat')->nullable();
            $table->date('tanggal_surat')->nullable();
            $table->timestamps();

            $table->unique(['profil_sekolah_id', 'tahun', 'triwulan', 'jenis'], 'surat_tpg_unik');
            $table->index(['tahun', 'triwulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_tpg');
    }
};
