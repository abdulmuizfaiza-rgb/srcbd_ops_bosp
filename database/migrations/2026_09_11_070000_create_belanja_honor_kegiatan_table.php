<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Belanja Honor Kegiatan - menu baru Pendataan BOSP (permintaan user
 * 2026-09-11). Field pada gambar contoh tabel yang diupload user
 * IDENTIK dengan struktur Langganan Daya dan Jasa (Part 15/17) DAN
 * Biaya Pendaftaran Lomba/Bimtek/Workshop di atas: No, NPSN, Nama
 * Sekolah, Uraian, Volume, Satuan, Tarif Harga, Jumlah, Tanggal - HANYA
 * nama menu & label tab yang berbeda.
 *
 * Sesuai jawaban AskUserQuestion 2026-09-11 (berlaku untuk kedua menu
 * baru round ini): 1 sekolah bisa punya BANYAK baris per triwulan, dan
 * Uraian BOLEH DUPLIKAT - TIDAK ADA unique constraint pada kolom
 * manapun di tabel ini selain primary key.
 *
 * Menu ini BERDIRI SENDIRI, tabel database SENDIRI (TERPISAH dari
 * biaya_pendaftaran_lomba di atas, walau field-nya identik) - kedua
 * menu ini adalah 2 menu berbeda yang diminta terpisah oleh user
 * ("tambahkan menu baru" disebutkan 2x untuk 2 nama menu berbeda),
 * BUKAN 2 tab dari 1 menu (beda dengan pola Belanja Pemeliharaan
 * Bangunan/PC Part 17/18 yang memang eksplisit "2 tab" dalam 1 menu).
 *
 * NPSN & Nama Sekolah SENGAJA TIDAK disimpan sebagai kolom sendiri -
 * selalu diambil dari relasi profil_sekolah_id saat ditampilkan/export.
 *
 * Kolom jumlah = HASIL RUMUS (Volume x Tarif Harga, lihat
 * App\Models\BelanjaHonorKegiatan::hitungJumlah()) - selalu dihitung
 * otomatis oleh sistem, bukan input manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('belanja_honor_kegiatan', function (Blueprint $table) {
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
        Schema::dropIfExists('belanja_honor_kegiatan');
    }
};
