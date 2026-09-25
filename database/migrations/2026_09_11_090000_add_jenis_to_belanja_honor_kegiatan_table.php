<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Belanja Honor Kegiatan - Pendataan BOSP (permintaan user 2026-09-11,
 * Part 20): menu ini diubah dari 1 tab (4 triwulan saja, dari Part 19)
 * menjadi 3 TAB UTAMA - "Honor Kegiatan" (data lama), "Belanja Makan &
 * Minum" (baru), "Belanja Perjalanan Dinas" (baru) - masing-masing
 * punya 4 sub-tab Triwulan sendiri, mengikuti pola 2-lapis-tab yang
 * sama seperti Belanja Pemeliharaan Bangunan/PC (Part 17/18).
 *
 * Field ketiga tab PERSIS SAMA (dikonfirmasi lewat AskUserQuestion
 * 2026-09-11, ketiga gambar contoh tabel yang diupload user memang
 * identik: No, NPSN, Nama Sekolah, Uraian, Volume, Satuan, Tarif Harga,
 * Jumlah, Tanggal), karena itu dipakai SATU tabel (belanja_honor_
 * kegiatan, sudah ada sejak Part 19) dengan kolom "jenis" BARU sebagai
 * pembeda tab utama, bukan 3 tabel terpisah - sesuai jawaban
 * AskUserQuestion 2026-09-11 ("1 tabel dengan kolom pembeda") - lihat
 * App\Models\BelanjaHonorKegiatan::JENIS_OPTIONS.
 *
 * BEDA dengan migration create_rincian_pemeliharaan_table (yang sejak
 * awal sudah punya kolom "jenis" karena tabelnya baru dibuat) - tabel
 * belanja_honor_kegiatan ini SUDAH ADA & bisa saja sudah berisi data
 * (dari Part 19) sebelum update ini dipasang, karena itu kolom "jenis"
 * ditambah dengan default 'honor_kegiatan' supaya SEMUA baris lama
 * (yang sebelum update ini semuanya adalah data "Belanja Honor
 * Kegiatan") otomatis ter-backfill jadi jenis='honor_kegiatan' tanpa
 * perlu migration data terpisah (Postgres 11+ mengisi kolom baru
 * berdefault langsung ke baris lama saat ADD COLUMN, sama seperti
 * MySQL/SQLite) - tidak ada baris lama yang jadi NULL/rusak.
 *
 * Index lama ['tahun','triwulan'] diganti jadi ['jenis','tahun',
 * 'triwulan'] (pola sama seperti index pada rincian_pemeliharaan),
 * supaya query per-tab-utama (queryDasar() di Livewire Index) tetap
 * efisien.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('belanja_honor_kegiatan', function (Blueprint $table) {
            $table->enum('jenis', ['honor_kegiatan', 'makan_minum', 'perjalanan_dinas'])
                ->default('honor_kegiatan')
                ->after('profil_sekolah_id');
        });

        Schema::table('belanja_honor_kegiatan', function (Blueprint $table) {
            $table->dropIndex(['tahun', 'triwulan']);
            $table->index(['jenis', 'tahun', 'triwulan']);
        });
    }

    public function down(): void
    {
        Schema::table('belanja_honor_kegiatan', function (Blueprint $table) {
            $table->dropIndex(['jenis', 'tahun', 'triwulan']);
            $table->index(['tahun', 'triwulan']);
            $table->dropColumn('jenis');
        });
    }
};
