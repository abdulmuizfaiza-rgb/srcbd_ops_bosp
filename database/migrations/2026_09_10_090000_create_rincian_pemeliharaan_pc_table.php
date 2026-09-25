<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Belanja Pemeliharaan PC Komputer-Laptop-Printer dll - Pendataan BOSP
 * (permintaan user 2026-09-10, Part 18): menu baru yang BERDIRI SENDIRI
 * (terpisah dari menu "Belanja Pemeliharaan Bangunan"), dengan 2 tab -
 * "Rincian Pemeliharaan PC Komputer-Laptop-Printer dll" (barang) &
 * "Rincian Jasa Pemeliharaan PC-Laptop-Printer dll" (jasa), masing-
 * masing punya 4 sub-tab Triwulan sendiri.
 *
 * Field PERSIS SAMA dengan menu "Belanja Pemeliharaan Bangunan" (gambar
 * contoh tabel yang diupload user untuk kedua tab identik dengan Part
 * 17): Kode UPB, NPSN & Nama Sekolah (otomatis dari Profil Sekolah),
 * Nama Barang, Nama Merk Barang, Volume, Satuan, Harga Satuan, Total
 * Harga (hasil rumus Volume x Harga Satuan), Asal Usul, Tanggal,
 * Keterangan - karena itu dipakai SATU tabel dengan kolom "jenis"
 * ('barang'/'jasa') sebagai pembeda, bukan 2 tabel terpisah - lihat
 * App\Models\RincianPemeliharaanPc. Tabelnya sendiri TERPISAH dari
 * "rincian_pemeliharaan" (menu Bangunan) sesuai jawaban AskUserQuestion
 * 2026-09-10 ("Menu & tabel terpisah dari Bangunan").
 *
 * Sesuai jawaban AskUserQuestion 2026-09-10 (sama seperti Part 17): 1
 * sekolah bisa punya BANYAK baris per triwulan per jenis, dan Nama
 * Barang BOLEH DUPLIKAT - TIDAK ADA unique constraint pada kolom manapun
 * selain primary key. Kolom "Kode UPB" diisi manual per baris (teks
 * bebas, TIDAK terhubung ke Profil Sekolah atau tabel lain).
 *
 * NPSN & Nama Sekolah SENGAJA TIDAK disimpan sebagai kolom sendiri -
 * selalu diambil dari relasi profil_sekolah_id saat ditampilkan/export.
 *
 * Kolom total_harga = HASIL RUMUS (Volume x Harga Satuan, lihat
 * App\Models\RincianPemeliharaanPc::hitungTotalHarga()) - selalu
 * dihitung otomatis oleh sistem, bukan input manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rincian_pemeliharaan_pc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->enum('jenis', ['barang', 'jasa']);
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

            $table->index(['jenis', 'tahun', 'triwulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rincian_pemeliharaan_pc');
    }
};
