<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Round DUA PULUH LIMA (2026-09-24), poin 1: permintaan user "pada menu
 * Panduan Aplikasi untuk upload file ada pilihan upload file lebih dari 1
 * dengan Judul Book Manual yang sama. termasuk juga untuk link google
 * drive nya juga".
 *
 * Sebelum menulis migration ini, ditanyakan lewat AskUserQuestion
 * (2026-09-24) karena ini keputusan struktur data/bisnis, bukan sekadar
 * teknis:
 * - "Struktur Data" -> "Satu Judul = banyak file & link (Recommended)":
 *   SATU baris `panduan_aplikasi` (Judul + Deskripsi) sekarang bisa punya
 *   BANYAK file & BANYAK link sekaligus - karena itu `link_drive` &
 *   `file_path`/`file_nama_asli`/`file_ukuran`/`file_mime` DIPINDAH dari
 *   tabel `panduan_aplikasi` ke DUA tabel anak baru (`panduan_aplikasi_file`
 *   & `panduan_aplikasi_link`, relasi hasMany/belongsTo - lihat
 *   App\Models\PanduanAplikasi::files()/links()), supaya tiap file/link
 *   bisa diunduh/dibuka & DIHAPUS SATU PER SATU tanpa menghapus Judul atau
 *   file/link lain di bawah Judul yang sama.
 * - "Validasi Wajib Isi" -> "Minimal 1 (file ATAU link) untuk keseluruhan
 *   Judul (Recommended)": aturan lama "minimal salah satu wajib diisi"
 *   (round 24) sekarang berlaku utk TOTAL keseluruhan Judul (jumlah file +
 *   jumlah link >= 1), BUKAN per-field lagi - lihat App\Livewire\
 *   PanduanAplikasi\Index::simpan()/hapusFile()/hapusLink().
 *
 * Migration ini MEMPERTAHANKAN data lama (kalau sudah ada panduan yang
 * dibuat sejak round 24 dirilis): setiap `file_path`/`link_drive` yang
 * TERISI pada tabel `panduan_aplikasi` dipindahkan (bukan dihapus) menjadi
 * satu baris pada tabel anak yang sesuai, SEBELUM kolom lama itu dihapus
 * dari `panduan_aplikasi` - tidak ada data yang hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panduan_aplikasi_file', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panduan_aplikasi_id')->constrained('panduan_aplikasi')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_nama_asli')->nullable();
            $table->unsignedBigInteger('file_ukuran')->nullable();
            $table->string('file_mime')->nullable();
            $table->timestamps();
        });

        Schema::create('panduan_aplikasi_link', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panduan_aplikasi_id')->constrained('panduan_aplikasi')->cascadeOnDelete();
            $table->string('link_drive');
            $table->timestamps();
        });

        // Pindahkan data lama (round 24) ke tabel anak SEBELUM kolomnya
        // dihapus dari panduan_aplikasi - lihat docblock di atas.
        DB::table('panduan_aplikasi')->whereNotNull('file_path')->orderBy('id')->get()->each(function ($row) {
            DB::table('panduan_aplikasi_file')->insert([
                'panduan_aplikasi_id' => $row->id,
                'file_path' => $row->file_path,
                'file_nama_asli' => $row->file_nama_asli,
                'file_ukuran' => $row->file_ukuran,
                'file_mime' => $row->file_mime,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        });

        DB::table('panduan_aplikasi')->whereNotNull('link_drive')->orderBy('id')->get()->each(function ($row) {
            DB::table('panduan_aplikasi_link')->insert([
                'panduan_aplikasi_id' => $row->id,
                'link_drive' => $row->link_drive,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        });

        Schema::table('panduan_aplikasi', function (Blueprint $table) {
            $table->dropColumn(['link_drive', 'file_path', 'file_nama_asli', 'file_ukuran', 'file_mime']);
        });
    }

    public function down(): void
    {
        Schema::table('panduan_aplikasi', function (Blueprint $table) {
            $table->string('link_drive')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_nama_asli')->nullable();
            $table->unsignedBigInteger('file_ukuran')->nullable();
            $table->string('file_mime')->nullable();
        });

        // Kembalikan HANYA file/link PERTAMA tiap Judul ke kolom lama
        // (kolom lama cuma muat satu) - cukup utk rollback darurat, bukan
        // dipakai dalam kondisi normal.
        DB::table('panduan_aplikasi_file')->orderBy('id')->get()->groupBy('panduan_aplikasi_id')->each(function ($rows) {
            $pertama = $rows->first();
            DB::table('panduan_aplikasi')->where('id', $pertama->panduan_aplikasi_id)->update([
                'file_path' => $pertama->file_path,
                'file_nama_asli' => $pertama->file_nama_asli,
                'file_ukuran' => $pertama->file_ukuran,
                'file_mime' => $pertama->file_mime,
            ]);
        });

        DB::table('panduan_aplikasi_link')->orderBy('id')->get()->groupBy('panduan_aplikasi_id')->each(function ($rows) {
            $pertama = $rows->first();
            DB::table('panduan_aplikasi')->where('id', $pertama->panduan_aplikasi_id)->update([
                'link_drive' => $pertama->link_drive,
            ]);
        });

        Schema::dropIfExists('panduan_aplikasi_link');
        Schema::dropIfExists('panduan_aplikasi_file');
    }
};
