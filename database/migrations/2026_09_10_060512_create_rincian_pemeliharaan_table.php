<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Belanja Pemeliharaan Bangunan - Pendataan BOSP (permintaan user
 * 2026-09-10, Part 17 poin 2): menu baru dengan 2 tab - "Rincian
 * Pemeliharaan Bangunan" (barang) & "Rincian Jasa Pemeliharaan" (jasa),
 * masing-masing punya 4 sub-tab Triwulan sendiri.
 *
 * Field PERSIS SAMA untuk kedua tab (dikonfirmasi lewat AskUserQuestion,
 * kedua gambar contoh tabel yang diupload user memang identik): Kode
 * UPB, NPSN & Nama Sekolah (otomatis dari Profil Sekolah), Nama Barang,
 * Nama Merk Barang, Volume, Satuan, Harga Satuan, Total Harga (hasil
 * rumus Volume x Harga Satuan), Asal Usul, Tanggal, Keterangan - karena
 * itu dipakai SATU tabel dengan kolom "jenis" ('barang'/'jasa') sebagai
 * pembeda, bukan 2 tabel terpisah (field & logic-nya 100% identik,
 * hanya konteks datanya beda) - lihat App\Models\RincianPemeliharaan.
 *
 * Sesuai jawaban AskUserQuestion 2026-09-10: 1 sekolah bisa punya BANYAK
 * baris per triwulan per jenis (banyak barang/jasa berbeda), dan Nama
 * Barang BOLEH DUPLIKAT (sama seperti Uraian Pembayaran di
 * langganan_daya_jasa) - TIDAK ADA unique constraint pada kolom manapun
 * selain primary key.
 *
 * Kolom "Kode UPB" - field BARU yang belum pernah ada di aplikasi ini -
 * sesuai jawaban AskUserQuestion, diisi manual per baris (teks bebas,
 * TIDAK terhubung ke Profil Sekolah atau tabel lain).
 *
 * NPSN & Nama Sekolah SENGAJA TIDAK disimpan sebagai kolom sendiri -
 * selalu diambil dari relasi profil_sekolah_id saat ditampilkan/export,
 * pola sama seperti langganan_daya_jasa & penerimaan_honor_ptk.
 *
 * Kolom total_harga = HASIL RUMUS (Volume x Harga Satuan, lihat
 * App\Models\RincianPemeliharaan::hitungTotalHarga()) - selalu dihitung
 * otomatis oleh sistem, bukan input manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rincian_pemeliharaan', function (Blueprint $table) {
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
        Schema::dropIfExists('rincian_pemeliharaan');
    }
};
