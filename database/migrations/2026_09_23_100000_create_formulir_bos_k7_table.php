<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Formulir BOS K7b & K7c - menu baru Pendataan BOSP (permintaan user
 * 2026-09-23, disertai 2 gambar contoh: "FORMULIR BOS-K7b - REGISTER
 * PENUTUPAN KAS" & "FORMULIR BOS K7c - BERITA ACARA PEMERIKSAAN KAS"),
 * ditempatkan di sidebar tepat sesudah menu "Laporan Realisasi BOSP
 * (Form BPK)".
 *
 * BEDA STRUKTUR dari hampir semua menu Pendataan BOSP lain (yang berbasis
 * TRIWULAN/tahunan): menu ini dibuat PER BULAN (Januari-Desember, 12
 * baris/tahun per sekolah) - sesuai permintaan eksplisit user "dibuat per
 * bulan". Mengikuti pola baris-tetap-per-bulan yang SAMA PERSIS seperti
 * `pajak_bosp_reguler` (profil_sekolah_id + tahun + bulan sebagai kunci
 * unik), BUKAN pola "banyak baris bebas" seperti menu lain.
 *
 * SATU baris di tabel ini dipakai untuk KEDUA tab (K7b & K7c) - jawaban
 * user pada AskUserQuestion 2026-09-23 mengonfirmasi kedua formulir
 * memang berasal dari data penutupan-kas bulanan yang SAMA (Saldo Kas
 * Tunai, Saldo Bank, Saldo BKU, Perbedaan identik di kedua formulir pada
 * gambar contoh) - K7b menampilkan rincian pecahan uang + kotak
 * penjelasan, K7c menampilkan narasi berita acara + nomor SK. Field milik
 * K7c-saja (nomor & tanggal SK Kepala Sekolah/Bendahara) tetap disimpan
 * di tabel yang sama supaya tidak perlu join/relasi tambahan.
 *
 * Field-field berikut SENGAJA TIDAK disimpan sebagai kolom (dihitung
 * dinamis lewat method static di App\Models\FormulirBosK7 - pola sama
 * seperti pajak_bosp_reguler, supaya rumus tidak dobel-tulis di
 * Livewire/Export Excel/Export PDF):
 * - Rp tiap baris pecahan uang (nominal x jumlah lembar/keping).
 * - Sub Jumlah Lembar uang kertas (1) & Sub Jumlah Keping uang logam (2).
 * - Saldo Kas Tunai (jumlah Sub Jumlah 1 + Sub Jumlah 2).
 * - A. Saldo Buku Kas Umum (A=D-K), B. Jumlah (1+2+3), Perbedaan (A-B).
 * - Tanggal Penutupan Kas Bulan ini/Bulan Lalu (SELALU akhir bulan
 *   berjalan/bulan sebelumnya dari kombinasi tahun+bulan baris ini -
 *   keputusan format tanggal, bukan aturan bisnis baru).
 * - Nama Penutup KAS (Pemegang KAS) & nama/NIP Kepala Sekolah/Bendahara
 *   pada blok tanda tangan - SELALU diambil live dari relasi
 *   profil_sekolah_id (nama_bendahara, nama_kepala_sekolah, dst),
 *   konsisten dengan pola menu lain.
 *
 * Field YANG disimpan sebagai kolom (input manual, sesuai jawaban
 * AskUserQuestion 2026-09-23):
 * - Jumlah Total Penerimaan BKU (D) & Jumlah Total Pengeluaran BKU (K):
 *   tidak ada sumber data lain di aplikasi ini pada level bulanan,
 *   sehingga wajib input manual.
 * - 7 kolom lembar uang kertas (jumlah lembar per pecahan) & 4 kolom
 *   keping uang logam (jumlah keping per pecahan): tidak ada precedent
 *   di aplikasi ini, input manual (dihitung Bendahara secara fisik).
 * - Saldo Rekening Bank: jawaban user "Input manual per bulan" (BUKAN
 *   otomatis dari Dana BOSP Tahap yang per-triwulan), supaya tidak perlu
 *   aturan pembagian triwulan->bulan yang belum tentu benar.
 * - Penjelasan Perbedaan: kotak teks bebas pada gambar contoh K7b.
 * - Nomor SK & Tanggal SK Kepala Sekolah, Nomor SK & Tanggal SK
 *   Bendahara: jawaban user "Diisi ulang tiap bulan, langsung di form
 *   K7c" - field baru, belum ada di mana pun pada aplikasi ini
 *   sebelumnya (dicek eksplisit, tidak ditemukan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formulir_bos_k7', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan');

            // 1. Lembaran uang kertas (jumlah lembar per pecahan).
            $table->unsignedInteger('lembar_100000')->default(0);
            $table->unsignedInteger('lembar_50000')->default(0);
            $table->unsignedInteger('lembar_20000')->default(0);
            $table->unsignedInteger('lembar_10000')->default(0);
            $table->unsignedInteger('lembar_5000')->default(0);
            $table->unsignedInteger('lembar_2000')->default(0);
            $table->unsignedInteger('lembar_1000')->default(0);

            // 2. Keping uang logam (jumlah keping per pecahan).
            $table->unsignedInteger('keping_1000')->default(0);
            $table->unsignedInteger('keping_500')->default(0);
            $table->unsignedInteger('keping_200')->default(0);
            $table->unsignedInteger('keping_100')->default(0);

            // 3. Saldo Rekening Bank (input manual per bulan).
            $table->bigInteger('saldo_rekening_bank')->default(0);

            // Jumlah Total Penerimaan/Pengeluaran BKU (D/K) - dasar rumus A.
            $table->bigInteger('jumlah_total_penerimaan_bku')->default(0);
            $table->bigInteger('jumlah_total_pengeluaran_bku')->default(0);

            $table->text('penjelasan_perbedaan')->nullable();

            // Khusus Formulir K7c - Surat Keputusan (SK) Kepala Sekolah & Bendahara.
            $table->string('no_sk_kepala_sekolah')->nullable();
            $table->date('tanggal_sk_kepala_sekolah')->nullable();
            $table->string('no_sk_bendahara')->nullable();
            $table->date('tanggal_sk_bendahara')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['profil_sekolah_id', 'tahun', 'bulan']);
            $table->index(['tahun', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formulir_bos_k7');
    }
};
