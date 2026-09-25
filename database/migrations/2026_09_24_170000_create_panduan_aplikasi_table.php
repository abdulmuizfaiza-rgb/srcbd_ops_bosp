<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menu "Panduan Aplikasi" (BARU, 2026-09-24, round kedua puluh empat, poin
 * 6, permintaan user "Buatkan menu baru dengan nama Panduan Aplikasi...").
 * Daftar Book Manual/panduan penggunaan aplikasi yang bisa diupload
 * Superadmin - lihat App\Models\PanduanAplikasi.
 *
 * Keputusan bisnis yang SENGAJA ditanyakan lewat AskUserQuestion
 * (2026-09-24) sebelum menulis migration ini:
 * - "Akses Panduan" -> "Hanya Superadmin yang bisa buka menu ini": menu
 *   ini (termasuk CRUD-nya) HANYA bisa dibuka Superadmin - sama seperti
 *   pola menu Kelola Pengguna/Timeline Pekerjaan/Tampilan, lihat Gate
 *   'akses-panduan-aplikasi' di App\Providers\AppServiceProvider.
 * - "Link vs File" -> "Minimal salah satu wajib diisi (Recommended)":
 *   `link_drive` & `file_path` SAMA-SAMA nullable di tabel ini (SATU baris
 *   boleh hanya punya salah satu, atau boleh keduanya sekaligus) -
 *   validasi "TIDAK BOLEH kosong dua-duanya sekaligus" dilakukan di level
 *   Livewire (App\Livewire\PanduanAplikasi\Index::simpan()), BUKAN
 *   constraint database, supaya pesan errornya bisa ramah pengguna.
 *
 * file_nama_asli/file_ukuran/file_mime disimpan terpisah dari file_path
 * (path acak hasil WithFileUploads::store()) supaya nama file ASLI (utk
 * ditampilkan & dipakai sbg nama unduhan) & ukurannya (kolom "Ukuran" pada
 * tabel) tidak perlu query ulang ke disk setiap render.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panduan_aplikasi', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('link_drive')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_nama_asli')->nullable();
            $table->unsignedBigInteger('file_ukuran')->nullable();
            $table->string('file_mime')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panduan_aplikasi');
    }
};
