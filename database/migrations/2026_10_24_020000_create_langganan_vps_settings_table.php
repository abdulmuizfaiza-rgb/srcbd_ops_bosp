<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permintaan user 2026-10-24: Superadmin perlu diingatkan kapan deadline
 * biaya langganan VPS (idcloudhost) harus di-top up lagi, ditampilkan di
 * tab baru "Storage & Langganan VPS" pada menu Cek Database dan Aplikasi
 * (lihat App\Livewire\CekDatabaseAplikasi\Index). Satu baris/singleton
 * (pola SAMA seperti App\Models\TahunAnggaranSetting/SyncSetting/
 * PengaturanTampilan/GoogleDriveSetting).
 *
 * Dikonfirmasi lewat AskUserQuestion: tanggal jatuh tempo diisi MANUAL
 * oleh Superadmin (bukan ditarik otomatis lewat API idcloudhost) - jadi
 * TIDAK ADA kredensial/API key apa pun yang disimpan di tabel ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('langganan_vps_settings', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('langganan_vps_settings');
    }
};
