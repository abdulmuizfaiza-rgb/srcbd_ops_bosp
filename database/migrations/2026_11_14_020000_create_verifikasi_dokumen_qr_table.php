<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel pencatatan dokumen yang diunduh dengan mode "Tanda Tangan & Cap
 * berupa QR Code" (Fase 2 fitur Tanda Tangan Kepala Sekolah & Cap Sekolah,
 * permintaan user 2026-11-14 - lihat progress-log.md).
 *
 * Satu baris = satu KALI unduhan dokumen dalam mode QR Code. `uuid` adalah
 * token yang di-encode ke dalam gambar QR Code di PDF-nya DAN jadi bagian
 * URL halaman verifikasi publik (/verifikasi-dokumen/{uuid}) - saat QR
 * di-scan, halaman itu mencari baris ini berdasarkan uuid untuk
 * menampilkan info dokumennya.
 *
 * KEPUTUSAN SCOPE (dikonfirmasi user via AskUserQuestion 2026-11-14):
 * - Baris HANYA dibuat untuk mode QR Code (bukan untuk mode "Tanpa Tanda
 *   Tangan" atau "Dengan Tanda Tangan & Cap gambar") - karena hanya mode
 *   QR Code yang punya mekanisme verifikasi digital; dua mode lainnya
 *   tidak menghasilkan URL verifikasi apa pun jadi tidak perlu dicatat.
 * - nama_sekolah & nama_kepala_sekolah SENGAJA disimpan sebagai salinan
 *   teks (snapshot) di saat dokumen diunduh, BUKAN relasi murni ke
 *   ProfilSekolah - supaya kalau nama sekolah/kepala sekolah berubah di
 *   kemudian hari, halaman verifikasi tetap menampilkan nama yang berlaku
 *   PADA SAAT dokumen itu diterbitkan (riwayat historis akurat).
 * - profil_sekolah_id & dibuat_oleh nullable + set null saat parent-nya
 *   dihapus (BUKAN cascade delete) - supaya riwayat verifikasi tetap
 *   bertahan (tidak ikut terhapus) walau sekolah/user-nya suatu saat
 *   dihapus; data tampilan verifikasi tetap aman karena sudah ada
 *   snapshot teks di atas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifikasi_dokumen_qr', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('jenis_dokumen');
            $table->foreignId('profil_sekolah_id')->nullable()->constrained('profil_sekolah')->nullOnDelete();
            $table->string('nama_sekolah');
            $table->string('nama_kepala_sekolah')->nullable();
            $table->unsignedSmallInteger('tahun')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifikasi_dokumen_qr');
    }
};
