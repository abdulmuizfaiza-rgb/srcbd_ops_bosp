<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Upload Rekening Koran - menu baru Pendataan BOSP (permintaan user
 * 2026-11-14). 1 baris per sekolah PER TAHUN (pola sama persis seperti
 * RekapRkas/DanaBospTahap/PajakBospReguler - unique constraint
 * profil_sekolah_id+tahun), dengan 4 kolom file (path disk `public`,
 * SAMA PERSIS pola upload PDF `sk_ops` di App\Livewire\PendataanOps\
 * Index - validasi mimes:pdf, max 1 MB).
 *
 * KEPUTUSAN PENTING - "mode pilih 1 Triwulan dulu, baru kolom itu yang
 * aktif" (dikonfirmasi lewat AskUserQuestion 2026-11-14): aturan ini
 * MURNI logika tampilan (Livewire property $triwulanDipilih di
 * App\Livewire\PendataanBosp\RekeningKoran\Index), BUKAN dipaksakan di
 * level database - keempat kolom TETAP bisa diisi kapan saja satu per
 * satu lewat menu, hanya saja UI-nya membatasi 1 kolom aktif per saat
 * supaya sekolah tidak salah upload ke Triwulan yang keliru.
 *
 * Superadmin menggabungkan TW1-4 setiap sekolah jadi 1 file PDF lewat
 * App\Services\RekeningKoranMergeService (hasil gabungan DIBUAT ULANG
 * setiap kali tombol "Gabungkan" ditekan, TIDAK pernah disimpan sebagai
 * sumber kebenaran terpisah di tabel ini - menghindari risiko data usang/
 * stale seperti yang pernah terjadi pada fitur lain di aplikasi ini).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekening_koran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->string('rekening_koran_tw1')->nullable();
            $table->string('rekening_koran_tw2')->nullable();
            $table->string('rekening_koran_tw3')->nullable();
            $table->string('rekening_koran_tw4')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['profil_sekolah_id', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekening_koran');
    }
};
