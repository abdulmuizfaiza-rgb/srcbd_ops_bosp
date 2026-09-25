<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Biaya Pendaftaran Lomba/Bimtek/Workshop - menu baru Pendataan BOSP
 * (permintaan user 2026-09-11). Field pada gambar contoh tabel yang
 * diupload user IDENTIK dengan struktur Langganan Daya dan Jasa (Part
 * 15/17): No, NPSN, Nama Sekolah, Uraian, Volume, Satuan, Tarif Harga,
 * Jumlah, Tanggal - HANYA nama menu & label tab yang berbeda.
 *
 * Sesuai jawaban AskUserQuestion 2026-09-11: 1 sekolah bisa punya
 * BANYAK baris per triwulan ("Banyak baris per sekolah"), dan Uraian
 * BOLEH DUPLIKAT - karena itu TIDAK ADA unique constraint pada kolom
 * manapun di tabel ini selain primary key (pola sama seperti
 * langganan_daya_jasa).
 *
 * Menu ini adalah menu BARU yang BERDIRI SENDIRI (bukan tab tambahan
 * pada menu lain), sehingga memakai tabel database SENDIRI (bukan
 * menumpang ke langganan_daya_jasa ataupun belanja_honor_kegiatan) -
 * konsisten dengan konvensi setiap "menu baru" di aplikasi ini yang
 * selalu punya tabel sendiri.
 *
 * NPSN & Nama Sekolah SENGAJA TIDAK disimpan sebagai kolom sendiri -
 * selalu diambil dari relasi profil_sekolah_id saat ditampilkan/export,
 * supaya tidak pernah basi.
 *
 * Kolom jumlah = HASIL RUMUS (Volume x Tarif Harga, lihat
 * App\Models\BiayaPendaftaranLomba::hitungJumlah()) - selalu dihitung
 * otomatis oleh sistem, bukan input manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biaya_pendaftaran_lomba', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('triwulan');
            $table->string('uraian')->nullable();
            $table->integer('volume')->nullable();
            $table->string('satuan', 50)->nullable();
            $table->bigInteger('tarif_harga')->nullable();
            $table->bigInteger('jumlah')->nullable();
            $table->date('tanggal')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tahun', 'triwulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biaya_pendaftaran_lomba');
    }
};
