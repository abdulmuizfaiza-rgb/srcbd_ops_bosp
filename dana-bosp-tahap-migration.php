<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dana BOSP Tahap 1 & 2 - Pendataan BOSP (permintaan user 2026-09-16,
 * Part 30). Posisi menu: sesudah "Rekap RKAS Awal-Perubahan" di sidebar.
 *
 * 1 baris per sekolah PER TAHUN (sama seperti pola RekapRkas/
 * PajakBospReguler - BUKAN 1 data tetap per sekolah seperti Identitas
 * Admin BOSP), karena dana BOSP memang dicairkan per tahun anggaran.
 *
 * SATU tabel menampung field dari KEDUA tab (bukan 2 tabel terpisah) -
 * karena kedua tab menampilkan 1 record yang sama per sekolah+tahun,
 * cuma dikelompokkan tampilannya jadi 2 tab di UI (lihat
 * App\Livewire\PendataanBosp\DanaBospTahap\Index):
 *
 * Tab 1 "Penerimaan BOSP" (kolom manual + kolom hasil rumus):
 * - saldo_bosp_tahun_sebelumnya (manual, Rupiah)
 * - jumlah_siswa (manual, angka biasa - BUKAN Rupiah)
 * - jumlah_dana_bosp_per_tahun (manual, Rupiah, per siswa/tahun)
 * - total_penerimaan_setahun (HASIL RUMUS: jumlah_siswa x jumlah_dana_bosp_per_tahun)
 * - penerimaan_tahap_1 (HASIL RUMUS: intdiv(total_penerimaan_setahun, 2) -
 *   dibulatkan ke bawah/tanpa desimal, sesuai jawaban AskUserQuestion
 *   2026-09-16 "jangan ada desimal")
 * - penerimaan_tahap_2 (HASIL RUMUS: total_penerimaan_setahun - penerimaan_tahap_1,
 *   otomatis menyerap sisa pembagian ganjil)
 *
 * Tab 2 "Tarik Tunai BOSP" (SEMUA manual, tidak ada rumus):
 * - saldo_bosp_tw4_tahun_sebelumnya
 * - tarik_tunai_tw1, tarik_tunai_tw2, tarik_tunai_tw3, tarik_tunai_tw4
 *
 * Lihat App\Models\DanaBospTahap untuk rumus lengkapnya. JANGAN
 * menebak/menerapkan rumus apapun di luar yang sudah ditentukan user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dana_bosp_tahap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');

            // Tab 1: Penerimaan BOSP.
            $table->bigInteger('saldo_bosp_tahun_sebelumnya')->nullable();
            $table->unsignedInteger('jumlah_siswa')->nullable();
            $table->bigInteger('jumlah_dana_bosp_per_tahun')->nullable();
            $table->bigInteger('total_penerimaan_setahun')->nullable();
            $table->bigInteger('penerimaan_tahap_1')->nullable();
            $table->bigInteger('penerimaan_tahap_2')->nullable();

            // Tab 2: Tarik Tunai BOSP.
            $table->bigInteger('saldo_bosp_tw4_tahun_sebelumnya')->nullable();
            $table->bigInteger('tarik_tunai_tw1')->nullable();
            $table->bigInteger('tarik_tunai_tw2')->nullable();
            $table->bigInteger('tarik_tunai_tw3')->nullable();
            $table->bigInteger('tarik_tunai_tw4')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['profil_sekolah_id', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dana_bosp_tahap');
    }
};
