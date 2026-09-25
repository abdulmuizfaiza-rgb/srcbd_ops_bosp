<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rincian Belanja Modal - Pendataan BOSP (permintaan user 2026-09-11,
 * Part 21 poin 2): menu baru dengan 2 tab - "Rincian Belanja Modal
 * Peralatan & Mesin (KIB B)" & "Rincian Belanja Modal Aset Tetap
 * Lainnya (KIB E)", masing-masing punya 4 sub-tab Triwulan sendiri.
 *
 * Field PERSIS SAMA untuk kedua tab (3 gambar contoh tabel yang diupload
 * user - gambar 1 KIB B & gambar 2 KIB E - identik): Kode UPB, NPSN &
 * Nama Sekolah (otomatis dari Profil Sekolah), Nama Barang, Nama Merk
 * Barang, Volume, Satuan, Harga Satuan, Total Harga (hasil rumus Volume
 * x Harga Satuan), Asal Usul, Tanggal, Keterangan - karena itu dipakai
 * SATU tabel dengan kolom "jenis" ('peralatan_mesin'/'aset_tetap_lainnya')
 * sebagai pembeda, bukan 2 tabel terpisah, mengikuti pola yang sama
 * seperti App\Models\RincianPemeliharaan (Part 17) - tabel BARU (bukan
 * ALTER, karena menu ini belum pernah ada sebelumnya), jadi kolom
 * "jenis" langsung ada dari awal tanpa perlu default/backfill.
 *
 * Sesuai jawaban AskUserQuestion 2026-09-11 (persis sama seperti Part
 * 17): 1 sekolah bisa punya BANYAK baris per triwulan per jenis, dan
 * Nama Barang BOLEH DUPLIKAT - TIDAK ADA unique constraint pada kolom
 * manapun selain primary key.
 *
 * Kolom "Kode UPB" diisi manual (teks bebas, TIDAK terhubung ke Profil
 * Sekolah atau tabel lain) - sesuai jawaban AskUserQuestion.
 *
 * NPSN & Nama Sekolah SENGAJA TIDAK disimpan sebagai kolom sendiri -
 * selalu diambil dari relasi profil_sekolah_id saat ditampilkan/export.
 *
 * Kolom total_harga = HASIL RUMUS (Volume x Harga Satuan, lihat
 * App\Models\RincianBelanjaModal::hitungTotalHarga()) - selalu dihitung
 * otomatis oleh sistem, bukan input manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rincian_belanja_modal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->enum('jenis', ['peralatan_mesin', 'aset_tetap_lainnya']);
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
        Schema::dropIfExists('rincian_belanja_modal');
    }
};
