<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Langganan Daya dan Jasa - permintaan user 2026-09-10 (field sesuai
 * gambar contoh tabel yang diupload): NPSN & Nama Sekolah otomatis dari
 * Profil Sekolah, Uraian Pembayaran (teks bebas, mis. "Listrik"/
 * "Internet"/"Air PDAM"), Volume, Satuan, Tarif Harga, Jumlah (hasil
 * rumus Volume x Tarif Harga), Tanggal Bayar - per tahun & per triwulan
 * (4 triwulan), sama seperti Penerimaan Honor PTK.
 *
 * Sesuai jawaban AskUserQuestion 2026-09-10: 1 sekolah bisa punya BANYAK
 * baris per triwulan (listrik/air/internet dst masing-masing 1 baris),
 * dan Uraian Pembayaran BOLEH DUPLIKAT (mis. 2 baris "Listrik" untuk
 * bulan berbeda dalam 1 triwulan) - karena itu, BEDA dengan
 * penerimaan_honor_ptk, TIDAK ADA unique constraint pada kolom manapun
 * di tabel ini selain primary key.
 *
 * NPSN & Nama Sekolah SENGAJA TIDAK disimpan sebagai kolom sendiri -
 * selalu diambil dari relasi profil_sekolah_id saat ditampilkan/export,
 * supaya tidak pernah basi (pola sama seperti penerimaan_honor_ptk).
 *
 * Kolom jumlah = HASIL RUMUS (Volume x Tarif Harga, lihat
 * App\Models\LanggananDayaJasa::hitungJumlah()) - selalu dihitung
 * otomatis oleh sistem, bukan input manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('langganan_daya_jasa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('triwulan');
            $table->string('uraian_pembayaran')->nullable();
            $table->integer('volume')->nullable();
            $table->string('satuan', 50)->nullable();
            $table->bigInteger('tarif_harga')->nullable();
            $table->bigInteger('jumlah')->nullable();
            $table->date('tanggal_bayar')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tahun', 'triwulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('langganan_daya_jasa');
    }
};
