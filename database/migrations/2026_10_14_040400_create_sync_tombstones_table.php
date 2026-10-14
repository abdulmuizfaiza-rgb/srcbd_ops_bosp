<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permintaan user 2026-10-14 poin 3 - jawaban AskUserQuestion
 * "Penghapusan ikut disinkronkan (Recommended)": tiap kali 1 baris di
 * salah satu dari 24 modul yang disinkronkan DIHAPUS, dicatat "batu
 * nisan"-nya di sini (lihat App\Models\Concerns\Syncable) - supaya saat
 * sinkronisasi berikutnya, sisi lain tahu baris itu HARUS ikut dihapus
 * juga, bukan cuma "tidak pernah dikirim ulang".
 *
 * `kunci` menyimpan kolom-kolom kunci unik alami modul itu (atau
 * sync_uuid untuk modul yang tidak punya kunci alami) sebagai JSON -
 * dipakai App\Services\SyncEngine untuk mencari & menghapus baris yang
 * cocok di sisi penerima.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_tombstones', function (Blueprint $table) {
            $table->id();
            $table->string('tabel');
            $table->json('kunci');
            $table->timestamp('dihapus_pada');
            $table->foreignId('dihapus_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tabel', 'dihapus_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_tombstones');
    }
};
