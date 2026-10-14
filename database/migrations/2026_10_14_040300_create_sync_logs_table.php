<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permintaan user 2026-10-14 poin 3: riwayat setiap proses sinkronisasi,
 * dari SUDUT PANDANG instalasi ini sendiri:
 * - 'kirim'  = instalasi ini MENGIRIM (menjalankan tombol Sinkronisasi) -
 *              hanya terjadi di Lokal.
 * - 'terima' = instalasi ini MENERIMA kiriman dari sisi lain - hanya
 *              terjadi di Online (dipicu panggilan API dari Lokal).
 * - 'tarik'  = instalasi ini MENARIK (menjalankan tombol Tarik Data) -
 *              hanya terjadi di Lokal.
 * - 'sajikan'= instalasi ini MELAYANI permintaan tarikan dari sisi lain -
 *              hanya terjadi di Online (dipicu panggilan API dari Lokal).
 *
 * Online TIDAK PERNAH mencatat 'kirim'/'tarik' (Online tidak bisa
 * memulai koneksi balik ke Lokal - jawaban AskUserQuestion 2026-09-xx:
 * tombol aktif HANYA di Lokal, Online hanya menampilkan status/riwayat).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('arah');
            $table->string('status');
            $table->json('ringkasan')->nullable();
            $table->text('pesan_error')->nullable();
            $table->foreignId('backup_pengaman_id')->nullable()->constrained('backups')->nullOnDelete();
            $table->foreignId('dilakukan_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
