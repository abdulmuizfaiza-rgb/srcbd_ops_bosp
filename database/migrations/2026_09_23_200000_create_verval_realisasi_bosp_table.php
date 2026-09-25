<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verval (verifikasi & validasi) Laporan Realisasi BOSP (Form BPK) -
 * permintaan user 2026-09-23 (round keenam, poin 3, disertai gambar
 * contoh tabel "VALIDASI HASIL ENTRY DATA BOSP" - 13 baris Uraian x 4
 * kolom Triwulan, tiap Triwulan punya sub-kolom Verval Sesuai/Belum
 * Sesuai).
 *
 * SATU baris = 1 sekolah + 1 tahun + 1 triwulan (unik) - status verval
 * Admin BOSP untuk triwulan itu. Jawaban AskUserQuestion 2026-09-23
 * (round keenam) mengonfirmasi:
 * - KEEMPAT triwulan independen (bukan "cukup 1 triwulan saja") - baris
 *   terpisah per triwulan, halaman "Laporan Realisasi BOSP (Form BPK)"
 *   baru aktif kalau SEMUA 4 triwulan sudah berstatus `sesuai` (lihat
 *   App\Models\VervalRealisasiBosp::semuaTriwulanSesuai()).
 * - `status` punya 2 nilai aktif ("Sesuai"/"Belum Sesuai", BUKAN sekadar
 *   ceklist ya/tidak) - kolom string nullable (null = belum di-verval
 *   sama sekali/belum disentuh Admin BOSP).
 * - Begitu `status` = "sesuai", baris itu TERKUNCI PERMANEN (tidak bisa
 *   diubah lagi lewat UI manapun - lihat guard di
 *   App\Livewire\PendataanBosp\LaporanRealisasiBosp\Index::setVerval())
 *   & data isian triwulan itu pada 8 menu sumber (Penerimaan Honor PTK,
 *   Daya & Jasa, dst) JUGA ikut jadi read-only (lihat
 *   App\Livewire\Concerns\MenolakEditJikaTerkunciVerval).
 * - HANYA Admin BOSP (bukan Superadmin) yang melihat/mengisi halaman
 *   validasi ini - Superadmin langsung ke halaman laporan seperti biasa.
 *
 * `diverval_oleh` & `diverval_pada` dicatat untuk jejak audit (siapa &
 * kapan) - bukan permintaan eksplisit user, tapi pola umum di aplikasi
 * ini (mis. `created_by` pada tabel lain) untuk kolom pencatatan siapa
 * yang melakukan aksi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verval_realisasi_bosp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('triwulan');
            $table->string('status')->nullable();
            $table->foreignId('diverval_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverval_pada')->nullable();
            $table->timestamps();

            $table->unique(['profil_sekolah_id', 'tahun', 'triwulan']);
            $table->index(['tahun', 'triwulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verval_realisasi_bosp');
    }
};
