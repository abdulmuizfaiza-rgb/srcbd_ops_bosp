<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laporan Realisasi BOSP (Form BPK) - Pendataan BOSP (permintaan user
 * 2026-09-17, Part 32), disertai 2 gambar contoh template Excel
 * "LAPORAN REALISASI BOSP TAHUN {tahun}". Posisi menu: paling akhir di
 * sidebar Pendataan BOSP, sesudah "Pajak BOSP Reguler" (jawaban
 * AskUserQuestion 2026-09-17).
 *
 * Data dibuat PER SEKOLAH, PER TAHUN, PER TRIWULAN (1 baris = 1
 * triwulan, 4 baris tetap per sekolah per tahun - Triwulan 1 s.d. 4) -
 * pola sama seperti PajakBospReguler (1 baris tetap per periode), BUKAN
 * "banyak baris bebas per sekolah" seperti Penerimaan Honor PTK dkk.
 *
 * 5 tab pada menu ini (lihat App\Livewire\PendataanBosp\LaporanRealisasiBosp\Index):
 * - Tab 1-4: "Laporan Realisasi TW 1" s.d. "TW 4" - tabel biasa 1 baris
 *   per sekolah, difilter kolom triwulan (mirip pola RekapRkas/
 *   DanaBospTahap: SEMUA role bisa lihat, Superadmin seluruh sekolah,
 *   Admin BOSP sekolah sendiri saja).
 * - Tab 5: "Rekapitulasi Laporan Realisasi Tahun Anggaran (otomatis)" -
 *   menampilkan RINCIAN 4 TW + baris "Jumlah" PER SEKOLAH (jawaban
 *   AskUserQuestion 2026-09-17 "Rincian 4 TW + Jumlah per sekolah"),
 *   bukan cuma 1 baris ringkasan per sekolah - dihitung otomatis dari
 *   4 baris TW yang sudah ada, TIDAK menyimpan data sendiri.
 *
 * Kolom 1-7 (No, Kode UPB, NPSN, Nama Sekolah, Kecamatan, Subrayon,
 * Triwulan) TIDAK disimpan di tabel ini - Kode UPB/NPSN/Nama
 * Sekolah/Kecamatan/Subrayon selalu dari relasi profil_sekolah_id
 * (lihat migration add_subrayon_dan_kode_upb_to_profil_sekolah_table),
 * Triwulan dari kolom "triwulan" di bawah, No dari nomor urut tampilan.
 *
 * Kolom 8-27 (Saldo Awal s.d. Saldo Kas Tunai) SEMUA field MANUAL/bisa
 * diedit langsung pada putaran ini, sesuai permintaan eksplisit user
 * "untuk rumus-rumus nya nanti menyusul" - TERMASUK kolom yang
 * namanya "Total ..."/"Sisa ..." (kolom 10, 20, 23, 24, 25) yang pada
 * gambar contoh terlihat seperti hasil penjumlahan, tapi SENGAJA belum
 * dibuatkan rumusnya di putaran ini - JANGAN menebak rumus apapun untuk
 * kolom-kolom ini sampai user menentukan sendiri.
 *
 * Kolom 28 (label pada gambar: "Kolom 26 harus sama jumlahnya dengan
 * kolom 23" - dikoreksi user lewat jawaban AskUserQuestion 2026-09-17)
 * dan kolom 29 (VERIFIKASI SALDO) ADALAH PENGECUALIAN - rumusnya
 * DITENTUKAN EKSPLISIT oleh user pada jawaban yang sama (BUKAN
 * ditunda), lihat App\Models\LaporanRealisasiBosp::hitungVerifikasiSaldo():
 * - Kolom 28 (verifikasi_jumlah) = Kolom 26 (Saldo Rekening/Kas Bank) +
 *   Kolom 27 (Saldo Kas Tunai) - HASIL RUMUS, tidak pernah diedit manual.
 * - Kolom 29 (verifikasi_saldo) = "SAMA" (ditampilkan Biru BOLD) kalau
 *   Kolom 25 (Sisa Dana BOS) SAMA DENGAN Kolom 28, atau "TIDAK SAMA"
 *   (ditampilkan Merah BOLD) kalau berbeda - HASIL RUMUS, tidak pernah
 *   diedit manual.
 *
 * Kedua kolom hasil rumus (28, 29) TETAP DISIMPAN sebagai kolom di
 * tabel ini (bukan dihitung dinamis saat render seperti Jumlah/Saldo
 * PajakBospReguler) - mengikuti pola TERBARU "saldo_twN" pada
 * DanaBospTahap (Part 31): supaya konsisten dipakai bareng oleh
 * Livewire, Export Excel, & Export PDF nantinya tanpa dobel-hitung.
 *
 * Unique constraint gabungan (profil_sekolah_id, tahun, triwulan): 1
 * baris tetap per sekolah+tahun+triwulan, sama seperti PajakBospReguler.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_realisasi_bosp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('triwulan');

            // Kolom 8-10.
            $table->bigInteger('saldo_awal_dana_bosp')->nullable();
            $table->bigInteger('penerimaan_dana_bos')->nullable();
            $table->bigInteger('total_penerimaan')->nullable();

            // Kolom 11-19 (Belanja Barang dan Jasa) + kolom 20 (Total).
            $table->bigInteger('belanja_barang_pakai_habis_persediaan')->nullable();
            $table->bigInteger('jasa_tenaga_pendidik_dan_kependidikan')->nullable();
            $table->bigInteger('daya_dan_jasa')->nullable();
            $table->bigInteger('pemeliharaan')->nullable();
            $table->bigInteger('upah_pemeliharaan')->nullable();
            $table->bigInteger('biaya_pendaftaran_lomba_bimtek_workshop')->nullable();
            $table->bigInteger('honor_kegiatan')->nullable();
            $table->bigInteger('makan_dan_minum_kegiatan')->nullable();
            $table->bigInteger('perjalanan_dinas')->nullable();
            $table->bigInteger('total_belanja_barang_dan_jasa')->nullable();

            // Kolom 21-22 (Belanja Modal) + kolom 23 (Total).
            $table->bigInteger('peralatan_dan_mesin_kib_b')->nullable();
            $table->bigInteger('aset_tetap_lainnya_kib_e')->nullable();
            $table->bigInteger('total_belanja_modal')->nullable();

            // Kolom 24-27.
            $table->bigInteger('total_realisasi_dana_bos')->nullable();
            $table->bigInteger('sisa_dana_bos')->nullable();
            $table->bigInteger('saldo_rekening_kas_bank')->nullable();
            $table->bigInteger('saldo_kas_tunai')->nullable();

            // Kolom 28-29 (HASIL RUMUS - lihat App\Models\LaporanRealisasiBosp::hitungVerifikasiSaldo()).
            $table->bigInteger('verifikasi_jumlah')->nullable();
            $table->string('verifikasi_saldo')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['profil_sekolah_id', 'tahun', 'triwulan']);
            $table->index(['tahun', 'triwulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_realisasi_bosp');
    }
};
