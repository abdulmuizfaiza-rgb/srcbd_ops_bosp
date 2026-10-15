<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tab "Data Cair/Belum TPG" (BARU 2026-10-16, permintaan user, format
 * sesuai gambar contoh "REKAPITULASI DATA PTK YANG SUDAH / BELUM CAIR
 * TPG") pada menu "Format Surat Rekomendasi & Pembatalan TPG" - lihat
 * App\Models\SuratTpg::JENIS_REKAP_CAIR_TPG & App\Livewire\PendataanOps\
 * SuratTpg\Index::muatRekapCairTpg() untuk konteks lengkap.
 *
 * Tabel HEADER (1 baris per sekolah+tahun+triwulan) - hanya menyimpan
 * Catatan Hasil Verifikasi. Data ceklist 12 bulan per-PTK ada di tabel
 * terpisah `rekap_cair_tpg_bulan` (lihat migration
 * create_rekap_cair_tpg_bulan_table).
 *
 * CATATAN PENTING (dikonfirmasi AskUserQuestion 2026-10-16): daftar
 * NAMA PTK pada tab ini adalah GABUNGAN dari Lampiran 2a SEMUA Triwulan
 * (1-4) dalam 1 Tahun (supaya PTK yang baru masuk di triwulan manapun
 * tetap muncul, krn tabel ini merekap 12 bulan penuh) - TAPI isian data
 * (ceklist 12 bulan & Catatan Hasil Verifikasi di sini) tetap disimpan
 * TERPISAH per Triwulan yang sedang difilter, sama seperti tab2 lain di
 * menu ini (laporan diperbarui tiap checkpoint Triwulan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_cair_tpg', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('triwulan');
            $table->text('catatan_hasil_verifikasi')->nullable();
            $table->timestamps();

            $table->unique(['profil_sekolah_id', 'tahun', 'triwulan'], 'rekap_cair_tpg_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_cair_tpg');
    }
};
