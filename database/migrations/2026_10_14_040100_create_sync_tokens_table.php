<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permintaan user 2026-10-14 poin 3: token yang dibuat di sisi ONLINE
 * (lewat menu "Sinkronisasi dan Tarik Data") supaya sisi LOKAL bisa
 * mengautentikasi diri saat memanggil API Online (baik untuk mengirim/
 * Sinkronisasi MAUPUN menarik/Tarik Data - 1 token dipakai untuk
 * keduanya, karena yang menghubungi selalu Lokal, Online hanya
 * melayani).
 *
 * `token_hash` menyimpan HASH (hash('sha256', ...)) dari token, BUKAN
 * token aslinya - mengikuti pola standar Laravel Sanctum/"API key tidak
 * pernah disimpan polos di database" (sesuai prinsip keamanan dari audit
 * 2026-10-04: "API key tidak boleh bocor ke frontend/disimpan polos").
 * Token ASLI hanya ditampilkan SATU KALI ke Superadmin tepat setelah
 * dibuat/diganti (lihat App\Livewire\Sinkronisasi\Index::buatTokenBaru())
 * - setelah itu tidak bisa dilihat lagi, hanya bisa diganti baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash')->unique();
            $table->string('label')->nullable();
            $table->foreignId('dibuat_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('terakhir_dipakai_pada')->nullable();
            $table->timestamp('dicabut_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_tokens');
    }
};
