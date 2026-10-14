<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permintaan user 2026-10-14 poin 3: pengaturan koneksi (satu
 * baris/singleton, pola sama seperti App\Models\PengaturanTampilan) -
 * HANYA dipakai berarti di sisi LOKAL: URL Online + token API Online
 * yang di-paste Superadmin (disalin dari menu Sinkronisasi sisi
 * Online). Di sisi Online, baris ini tetap ada (migration jalan di
 * kedua sisi) tapi kolomnya tidak pernah diisi/dipakai.
 *
 * `token_online` dienkripsi (cast 'encrypted', bawaan Laravel - pakai
 * APP_KEY) - BUKAN hash seperti sync_tokens, karena di sini token itu
 * justru PERLU dibaca ulang dalam bentuk asli untuk dikirim di header
 * Authorization setiap kali Lokal menghubungi Online. Mengikuti prinsip
 * keamanan yang sama dari audit 2026-10-04 (jangan simpan credential
 * polos) - dienkripsi, bukan plain text, dan TIDAK PERNAH ditampilkan
 * balik penuh ke layar (hanya 4 karakter terakhir, lihat
 * App\Livewire\Sinkronisasi\Index).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_settings', function (Blueprint $table) {
            $table->id();
            $table->string('url_online')->nullable();
            $table->text('token_online')->nullable();
            $table->timestamp('terakhir_tes_koneksi_pada')->nullable();
            $table->boolean('terakhir_tes_koneksi_berhasil')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_settings');
    }
};
