<?php

use App\Models\RincianBelanjaBarangHabisPakai;
use App\Models\StockOpnameBarangPersediaan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rumus Stock Opname & keterkaitan otomatis dengan Rincian Belanja
 * Barang Habis Pakai (permintaan user 2026-09-19, lanjutan langsung
 * dari fitur Stock Opname update-15.39).
 *
 * Sebelumnya (update-15.39) SELURUH kolom Kuantitas/Jumlah Stock Opname
 * masih manual & Nama Barang Persediaan diisi manual bebas per baris
 * (jawaban AskUserQuestion 2026-09-18: "nanti saja" utk rumus, "Input
 * manual sendiri per baris" utk Nama Barang). Permintaan user
 * 2026-09-19 MENGUBAH keputusan itu - user sekarang memberi rumus
 * eksplisit (lihat App\Models\StockOpnameBarangPersediaan) & meminta
 * Nama Barang Persediaan diambil dari Rincian Belanja Barang Habis
 * Pakai triwulan yang sama.
 *
 * Jawaban AskUserQuestion 2026-09-19 (2 pertanyaan, keduanya opsi
 * "Recommended" dipilih):
 * 1. Mekanisme keterkaitan: "Baris otomatis mengikuti RBBHP" - 1 baris
 *    Rincian Belanja Barang Habis Pakai = 1 baris Stock Opname (dibuat
 *    OTOMATIS), Nama Barang Persediaan jadi READ-ONLY (bukan diketik
 *    manual lagi), jumlah baris Stock Opname ikut bertambah/berkurang
 *    sesuai RBBHP triwulan yang sama.
 * 2. Data Stock Opname LAMA (nama barang manual, TIDAK cocok dengan
 *    barang manapun di RBBHP triwulan yang sama) - "Biarkan tersimpan
 *    apa adanya": TIDAK dihapus/diubah oleh migration ini, hanya baris
 *    yang di-backfill/dibuat baru sejak migration ini yang mengikuti
 *    aturan baru.
 *
 * Kolom BARU: `rincian_belanja_barang_habis_pakai_id` (FK, nullable,
 * cascadeOnDelete supaya baris Stock Opname OTOMATIS ikut terhapus
 * kalau baris RBBHP sumbernya dihapus - sesuai "baris ikut
 * berkurang"). NULL = baris LAMA/legacy (manual, sebelum fitur ini),
 * TIDAK PERNAH diisi lagi utk baris baru sejak migration ini (baris
 * baru SELALU dibuat lewat RincianBelanjaBarangHabisPakai::booted()
 * created-event, lihat model tsb).
 *
 * Backfill: setiap baris RincianBelanjaBarangHabisPakai yang BELUM
 * punya baris StockOpnameBarangPersediaan terkait (rincian_belanja_id)
 * dibuatkan SATU baris Stock Opname baru (nama_barang/satuan/harga/
 * kuantitas-kuantitas kosong - MASIH manual seperti sebelumnya, HANYA
 * Nama Barang Persediaan & Keterangan yang berubah jadi otomatis),
 * keterangan otomatis "BOSP Triwulan {triwulan} Tahun {tahun}" langsung
 * diisi saat backfill (lihat
 * StockOpnameBarangPersediaan::keteranganOtomatis()).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Nama constraint UNIQUE diberi eksplisit ("stock_opname_rincian_id_unique",
        // BUKAN nama default Laravel) - nama default
        // "stock_opname_barang_persediaan_rincian_belanja_barang_habis_pakai_id_unique"
        // & nama FK default dari constrained() di bawah SAMA-SAMA
        // terpotong PostgreSQL (batas identifier 63 karakter) jadi
        // string IDENTIK, sehingga menabrak constraint FK yang baru saja
        // dibuat kalau nama unique-nya dibiarkan default.
        Schema::table('stock_opname_barang_persediaan', function (Blueprint $table) {
            $table->foreignId('rincian_belanja_barang_habis_pakai_id')
                ->nullable()
                ->after('profil_sekolah_id')
                ->constrained('rincian_belanja_barang_habis_pakai')
                ->cascadeOnDelete();
            $table->unique('rincian_belanja_barang_habis_pakai_id', 'stock_opname_rincian_id_unique');
        });

        RincianBelanjaBarangHabisPakai::query()
            ->whereDoesntHave('stockOpnameBarangPersediaan')
            ->orderBy('id')
            ->chunkById(200, function ($daftarRincian) {
                foreach ($daftarRincian as $rincian) {
                    StockOpnameBarangPersediaan::create([
                        'profil_sekolah_id' => $rincian->profil_sekolah_id,
                        'rincian_belanja_barang_habis_pakai_id' => $rincian->id,
                        'tahun' => $rincian->tahun,
                        'triwulan' => $rincian->triwulan,
                        'keterangan' => StockOpnameBarangPersediaan::keteranganOtomatis($rincian->triwulan, $rincian->tahun),
                        'created_by' => $rincian->created_by,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('stock_opname_barang_persediaan', function (Blueprint $table) {
            $table->dropUnique('stock_opname_rincian_id_unique');
            $table->dropConstrainedForeignId('rincian_belanja_barang_habis_pakai_id');
        });
    }
};
