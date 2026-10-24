<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permintaan user 2026-10-24: Admin OPS/Admin BOSP yang ingin membuka
 * data tahun SEBELUM Tahun Anggaran Aktif (lihat
 * 2026_10_24_010000_create_tahun_anggaran_settings_table) harus minta
 * izin ke Superadmin lebih dulu - 1 baris per kombinasi akun+tahun.
 *
 * Status (kolom `status`): 'menunggu' (baru diajukan, belum diproses),
 * 'disetujui' (Superadmin memberi izin - PERMANEN sampai dicabut, sesuai
 * jawaban user lewat AskUserQuestion: "Permanen sampai dicabut
 * Superadmin"), atau 'ditolak' (Superadmin menolak - nilai status ini
 * JUGA dipakai untuk "mencabut" izin yang sebelumnya 'disetujui', lihat
 * App\Livewire\TahunAnggaran\Index::cabutIzin() - tidak ada kolom/status
 * terpisah untuk "dicabut", supaya 1 kombinasi akun+tahun selalu punya
 * TEPAT 1 baris riwayat terbaru, bukan riwayat bertumpuk).
 *
 * `unique(['user_id', 'tahun'])` - 1 akun hanya boleh punya 1 baris
 * permintaan per tahun (kalau mengajukan ulang tahun yang sama setelah
 * ditolak, baris yang SAMA di-update kembali ke 'menunggu', bukan
 * membuat baris baru - lihat ajukanPermintaan() di Livewire-nya).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_akses_tahun', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->string('status')->default('menunggu');
            $table->timestamp('diminta_pada');
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diproses_pada')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_akses_tahun');
    }
};
