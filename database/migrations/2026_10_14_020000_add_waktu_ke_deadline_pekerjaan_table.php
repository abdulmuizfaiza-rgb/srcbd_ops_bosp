<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah `tanggal_mulai` + ubah `tanggal_deadline` dari `date` jadi
 * `timestamp` (supaya punya komponen JAM, bukan cuma tanggal) pada tabel
 * `deadline_pekerjaan` - permintaan user 2026-10-14 poin 4: "pada menu
 * timeline pekerjaan tambahkan tanggal awal dimulai pekerjaan dan
 * tanggal akhir pekerjaan disertai dengan waktunya."
 *
 * `tanggal_mulai` NULLABLE - murni informasi kapan pekerjaan mulai boleh
 * dikerjakan. SENGAJA TIDAK dipakai untuk ikut memblokir input (blokir
 * TETAP hanya berdasarkan `tanggal_deadline` seperti sebelumnya) -
 * permintaan user hanya minta tanggal ini "ditambahkan", tidak secara
 * eksplisit minta efek blokir baru sebelum tanggal mulai, jadi TIDAK
 * ditambahkan aturan bisnis baru yang tidak diminta.
 *
 * `tanggal_deadline` SEBELUMNYA `date` (tanpa jam - "lewat" dihitung
 * mulai HARI SETELAH tanggal deadline, hari deadline sendiri masih
 * dianggap boleh, lihat migration `create_deadline_pekerjaan_table`).
 * SEKARANG jadi `timestamp` dengan jam presisi - user eksplisit minta
 * "disertai dengan waktunya", jadi definisi "lewat" ikut berubah jadi
 * presisi ke JAM (lihat App\Models\DeadlinePekerjaan::sudahLewat()),
 * BUKAN lagi longgar sampai akhir hari. Baris LAMA (tanggal tanpa jam)
 * otomatis dianggap jam 00:00:00 - PostgreSQL otomatis cast
 * date->timestamp dengan jam 00:00, tidak perlu migrasi data tambahan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deadline_pekerjaan', function (Blueprint $table) {
            $table->timestamp('tanggal_mulai')->nullable()->after('triwulan');
        });

        Schema::table('deadline_pekerjaan', function (Blueprint $table) {
            $table->timestamp('tanggal_deadline')->change();
        });
    }

    public function down(): void
    {
        Schema::table('deadline_pekerjaan', function (Blueprint $table) {
            $table->date('tanggal_deadline')->change();
            $table->dropColumn('tanggal_mulai');
        });
    }
};
