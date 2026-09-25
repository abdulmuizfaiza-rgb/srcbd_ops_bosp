<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pajak BOSP Reguler - Pendataan BOSP (permintaan user 2026-09-11, Part
 * 23), disertai 2 gambar contoh template Excel "REKAPITULASI PAJAK
 * REGULER DAN PAJAK DAERAH - DANA BANTUAN OPERASIONAL SEKOLAH (BOS) -
 * PERIODE JANUARI-DESEMBER TAHUN ANGGARAN {tahun}": data dibuat PER
 * SEKOLAH, PER TAHUN, 12 baris tetap (1 baris per bulan Januari-Desember)
 * - BUKAN "banyak baris per sekolah" seperti kebanyakan menu lain di
 * aplikasi ini (mis. Penerimaan Honor PTK), karena bulan sudah menjadi
 * kunci baris yang tetap/fixed sesuai bentuk tabel pada gambar contoh.
 *
 * 10 kolom pajak (field input manual per baris/bulan), sesuai gambar:
 * - PENERIMAAN/DEBIT: PPN, PPh21, PPh23, PPh4 (PPh Pasal 4 ayat 2), SSPD
 *   (Surat Setoran Pajak Daerah).
 * - PENGELUARAN/KREDIT: PPN, PPh21, PPh23, PPh4, SSPD (field sama,
 *   sisi kredit).
 *
 * Kolom "Jumlah" (total Debit per baris, total Kredit per baris) dan
 * "Saldo" (kumulatif berjalan, carry-over antar bulan - lihat jawaban
 * AskUserQuestion 2026-09-11 "Kumulatif, carry-over antar bulan") SENGAJA
 * TIDAK disimpan sebagai kolom di tabel ini - SELALU dihitung dinamis
 * saat render()/export (lihat App\Models\PajakBospReguler), supaya tidak
 * perlu cascading-recompute ke seluruh bulan berikutnya setiap kali 1
 * bulan diedit (beda dengan RekapRkas yang MEMANG menyimpan hasil
 * rumusnya ke kolom karena tidak ada sifat kumulatif/carry-over).
 *
 * Baris Triwulan (tabel kedua pada gambar) JUGA TIDAK disimpan sendiri -
 * dihitung otomatis dari agregat 3 bulan terkait (lihat jawaban
 * AskUserQuestion "Otomatis dari data bulanan").
 *
 * NPSN & Nama Sekolah TIDAK disimpan sebagai kolom di tabel ini - selalu
 * diambil dari relasi profil_sekolah_id.
 *
 * Unique constraint gabungan (profil_sekolah_id, tahun, bulan): karena
 * struktur tabel pada gambar HANYA punya 1 baris tetap per bulan per
 * sekolah per tahun (bukan banyak baris bebas per sekolah seperti menu
 * lain), maka setiap kombinasi sekolah+tahun+bulan HANYA BOLEH punya 1
 * baris data - mencegah baris duplikat untuk bulan yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pajak_bosp_reguler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan');

            $table->bigInteger('ppn_debit')->nullable();
            $table->bigInteger('pph21_debit')->nullable();
            $table->bigInteger('pph23_debit')->nullable();
            $table->bigInteger('pph4_debit')->nullable();
            $table->bigInteger('sspd_debit')->nullable();

            $table->bigInteger('ppn_kredit')->nullable();
            $table->bigInteger('pph21_kredit')->nullable();
            $table->bigInteger('pph23_kredit')->nullable();
            $table->bigInteger('pph4_kredit')->nullable();
            $table->bigInteger('sspd_kredit')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['profil_sekolah_id', 'tahun', 'bulan']);
            $table->index(['tahun', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pajak_bosp_reguler');
    }
};
