<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rincian Belanja Barang Habis Pakai - Pendataan BOSP (permintaan user
 * 2026-09-11, Part 21 poin 3): menu baru, SATU LAPIS tab (4 sub-tab
 * Triwulan langsung, TIDAK ADA tab utama tambahan - beda dengan Rincian
 * Belanja Modal yang punya 2 tab utama) - mengikuti pola struktur SATU
 * LAPIS tab yang sama seperti Biaya Pendaftaran Lomba/Bimtek/Workshop
 * (Part 19).
 *
 * Field-nya (sesuai gambar 3 contoh tabel yang diupload user) PERSIS
 * SAMA dengan Rincian Pemeliharaan/Rincian Belanja Modal: Kode UPB, NPSN
 * & Nama Sekolah (otomatis dari Profil Sekolah), Nama Barang, Nama Merk
 * Barang, Volume, Satuan, Harga Satuan, Total Harga (hasil rumus Volume
 * x Harga Satuan), Asal Usul, Tanggal, Keterangan - karena menu ini HANYA
 * SATU jenis (tidak ada tab utama), TIDAK ADA kolom "jenis" pada tabel
 * ini (beda dengan rincian_pemeliharaan/rincian_belanja_modal yang punya
 * kolom "jenis" untuk membedakan tab utamanya).
 *
 * Sesuai jawaban AskUserQuestion 2026-09-11 (persis sama seperti Part
 * 17/19): 1 sekolah bisa punya BANYAK baris per triwulan, dan Nama
 * Barang BOLEH DUPLIKAT - TIDAK ADA unique constraint pada kolom manapun
 * selain primary key.
 *
 * Kolom "Kode UPB" diisi manual (teks bebas, TIDAK terhubung ke Profil
 * Sekolah atau tabel lain) - sesuai jawaban AskUserQuestion.
 *
 * NPSN & Nama Sekolah SENGAJA TIDAK disimpan sebagai kolom sendiri -
 * selalu diambil dari relasi profil_sekolah_id saat ditampilkan/export.
 *
 * Kolom total_harga = HASIL RUMUS (Volume x Harga Satuan, lihat
 * App\Models\RincianBelanjaBarangHabisPakai::hitungTotalHarga()) -
 * selalu dihitung otomatis oleh sistem, bukan input manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rincian_belanja_barang_habis_pakai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('triwulan');
            $table->string('kode_upb')->nullable();
            $table->string('nama_barang')->nullable();
            $table->string('nama_merk_barang')->nullable();
            $table->integer('volume')->nullable();
            $table->string('satuan', 50)->nullable();
            $table->bigInteger('harga_satuan')->nullable();
            $table->bigInteger('total_harga')->nullable();
            $table->string('asal_usul')->nullable();
            $table->date('tanggal')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tahun', 'triwulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rincian_belanja_barang_habis_pakai');
    }
};
