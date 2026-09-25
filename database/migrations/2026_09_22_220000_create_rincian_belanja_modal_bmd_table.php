<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tab BARU "BMD" pada menu Pendataan BOSP > Rincian Belanja Modal
 * (permintaan user 2026-09-22, gambar acuan "DAFTAR BELANJA MODAL TAHUN
 * ANGGARAN 2026") - tab UTAMA ketiga (di samping 2 tab "jenis" yang
 * sudah ada: Peralatan & Mesin (KIB B) / Aset Tetap Lainnya (KIB E)),
 * dengan 4 sub-tab Triwulan sendiri, sama seperti pola tab utama kedua
 * "Stock Opname" pada menu Rincian Belanja Barang Habis Pakai
 * (2026-09-18).
 *
 * Field pada tab ini TIDAK ADA SAMA SEKALI kemiripan dengan field 2 tab
 * "jenis" yang sudah ada (Kode UPB/Nama Barang/dst.) - field BMD jauh
 * lebih banyak & berbeda total (~24 field manual + beberapa field
 * otomatis, sesuai gambar acuan) - karena itu dipakai TABEL BARU
 * TERPISAH (bukan menambah kolom "jenis" baru pada tabel
 * rincian_belanja_modal yang sudah ada), mengikuti pola yang sama
 * seperti App\Models\StockOpnameBarangPersediaan (tabel terpisah dari
 * RincianBelanjaBarangHabisPakai).
 *
 * NPSN, Lokasi (Nama Sekolah), & Subrayon TIDAK disimpan sebagai kolom -
 * selalu diambil dari relasi profil_sekolah_id (sama seperti seluruh
 * menu lain di aplikasi ini).
 *
 * Program, Kegiatan, Kode Sub Kegiatan, & Nama Sub Kegiatan JUGA TIDAK
 * disimpan sebagai kolom - nilainya SELALU SAMA PERSIS untuk setiap
 * baris (user: "di isi Program Pengelolaan Pendidikan" dkk., BUKAN
 * "diisi manual" seperti kelompok field sesudahnya) sehingga cukup
 * berupa KONSTANTA di App\Models\RincianBelanjaModalBmd
 * (PROGRAM/KEGIATAN/KODE_SUB_KEGIATAN/NAMA_SUB_KEGIATAN), ditampilkan
 * apa adanya di tabel/Export tanpa perlu kolom database.
 *
 * Kolom `jenis_aset` (Jenis Aset (KIB)) DISIMPAN (bukan hanya dihitung
 * saat tampil) meski nilainya SELALU otomatis mengikuti `rekening_belanja`
 * (jawaban AskUserQuestion 2026-09-22: "Otomatis saling mengikuti") -
 * mengikuti pola write-through/materialized column yang baku di proyek
 * ini (sama seperti `total`), supaya Export/Import & tampilan tabel
 * konsisten tanpa logic tambahan.
 *
 * Kolom `total` = Jumlah x Harga Satuan (App\Models\RincianBelanjaModalBmd::hitungTotal()).
 *
 * Kolom `keterangan_bos` & `keterangan_bosp` = DUA kolom "Keterangan"
 * BERBEDA yang SAMA-SAMA ada pada gambar acuan (dikonfirmasi lewat 2
 * posisi terpisah pada gambar: satu dekat kolom BAST, satu lagi di
 * kolom paling akhir) - masing-masing OTOMATIS "BOS SMPN TRIWULAN {n}
 * TAHUN {tahun} (REGULER)" & "BOSP TRIWULAN {n} TAHUN {tahun}" (lihat
 * App\Models\RincianBelanjaModalBmd::keteranganBosOtomatis()/
 * keteranganBospOtomatis()) - keduanya ditampilkan dengan judul kolom
 * "Keterangan" yang sama di tabel/Export, sesuai gambar.
 *
 * Sesuai pola menu lain: 1 sekolah bisa punya BANYAK baris per
 * triwulan, Nama Barang BOLEH DUPLIKAT - TIDAK ADA unique constraint
 * selain primary key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rincian_belanja_modal_bmd', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('triwulan');

            $table->string('bentuk_kontrak')->nullable();
            $table->string('atribusi')->nullable();
            $table->string('jumlah_termin')->nullable();
            $table->string('ppk')->nullable();
            $table->string('nomor_dokumen')->nullable();
            $table->date('tanggal_perolehan')->nullable();
            $table->string('penyedia')->nullable();
            $table->string('kode_belanja')->nullable();
            $table->string('rekening_belanja')->nullable();
            $table->string('jenis_aset')->nullable();
            $table->string('sub_sub_rincian_objek')->nullable();
            $table->integer('jumlah')->nullable();
            $table->string('satuan', 50)->nullable();
            $table->bigInteger('harga_satuan')->nullable();
            $table->bigInteger('total')->nullable();
            $table->string('no_bast')->nullable();
            $table->date('tanggal_bast')->nullable();
            $table->string('keterangan_bos')->nullable();
            $table->string('nomor_surat_pernyataan')->nullable();
            $table->date('tanggal_surat_pernyataan')->nullable();
            $table->string('nama_pengurus_barang')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('pejabat_penata_usaha')->nullable();
            $table->string('nama_barang')->nullable();
            $table->string('spesifikasi_nama_barang')->nullable();
            $table->string('spesifikasi_lain')->nullable();
            $table->string('merk_pengarang')->nullable();
            $table->string('keterangan_bosp')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tahun', 'triwulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rincian_belanja_modal_bmd');
    }
};
