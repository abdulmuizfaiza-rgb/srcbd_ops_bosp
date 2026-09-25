<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stock Opname (Rincian Barang Persediaan BOSP) - menu baru, TAB 2 di
 * dalam menu "Rincian Belanja Barang Habis Pakai" (permintaan user
 * 2026-09-18, sesuai gambar contoh tabel "STOCK OPNAME RINCIAN BARANG
 * PERSEDIAAN BOSP TAHUN ANGGARAN" yang diupload user).
 *
 * TABEL SENDIRI (bukan menambah kolom "jenis" pada
 * rincian_belanja_barang_habis_pakai) karena struktur field-nya BEDA
 * TOTAL - Rincian Belanja Barang Habis Pakai berisi Volume/Harga
 * Satuan/Total Harga (1 set nilai per baris), sedangkan Stock Opname
 * berisi 4 SET Kuantitas+Jumlah (Saldo Awal, Penerimaan, Pengeluaran,
 * Saldo Akhir) plus Satuan(Unit)+Harga per baris - pola tab-utama tetap
 * disalin dari RincianBelanjaModal (2 tab utama), lihat
 * App\Models\RincianBelanjaBarangHabisPakai::TAB_UTAMA_OPTIONS &
 * App\Livewire\PendataanBosp\RincianBelanjaBarangHabisPakai\Index.
 *
 * Sama seperti Rincian Belanja Barang Habis Pakai: 1 sekolah bisa punya
 * BANYAK baris per triwulan (banyak barang persediaan berbeda), Nama
 * Barang Persediaan BOLEH DUPLIKAT (sesuai jawaban AskUserQuestion
 * 2026-09-18 - Input manual sendiri per baris, sama seperti menu
 * Rincian Belanja Barang Habis Pakai, TIDAK disinkron dari data lain).
 *
 * PENTING: seluruh kolom Kuantitas & Jumlah (Rp) - termasuk "Saldo
 * Akhir" - untuk SEMENTARA masih INPUT MANUAL sepenuhnya. User SENGAJA
 * menjawab "nanti saja" untuk 3 pertanyaan AskUserQuestion soal rumus
 * (Saldo Akhir = Saldo Awal + Penerimaan - Pengeluaran? Saldo Awal TW
 * berikutnya = Saldo Akhir TW sebelumnya? Jumlah Rp = Kuantitas x
 * Harga?) - TIDAK ADA satupun rumus yang diterapkan pada ronde ini,
 * menunggu instruksi rumus dari user di ronde berikutnya (pola sama
 * seperti kolom 8-10 Laporan Realisasi BOSP dulu sebelum otomatis).
 *
 * NPSN, Nama Sekolah, & Subrayon TIDAK disimpan sebagai kolom sendiri -
 * selalu diambil dari relasi profil_sekolah_id saat ditampilkan/export
 * (pola sama seperti Rincian Belanja Barang Habis Pakai).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opname_barang_persediaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('triwulan');
            $table->string('nama_barang')->nullable();
            $table->string('satuan', 50)->nullable();
            $table->bigInteger('harga')->nullable();
            $table->integer('saldo_awal_kuantitas')->nullable();
            $table->bigInteger('saldo_awal_jumlah')->nullable();
            $table->integer('penerimaan_kuantitas')->nullable();
            $table->bigInteger('penerimaan_jumlah')->nullable();
            $table->integer('pengeluaran_kuantitas')->nullable();
            $table->bigInteger('pengeluaran_jumlah')->nullable();
            $table->integer('saldo_akhir_kuantitas')->nullable();
            $table->bigInteger('saldo_akhir_jumlah')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tahun', 'triwulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_barang_persediaan');
    }
};
