<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanda Tangan Kepala Sekolah & Cap Sekolah - permintaan user 2026-11-14
 * ("tambahkan field Tanda Tangan Kepala Sekolah dan field Cap Sekolah").
 *
 * KEPUTUSAN PENTING (dikoreksi user setelah sempat salah paham - jangan
 * diubah lagi tanpa persetujuan user): KEDUA field ini murni field UPLOAD
 * biasa, SAMA PERSIS pola-nya dengan `logo_sekolah` yang sudah ada -
 * Admin OPS/Admin BOSP yang mengupload gambar tanda tangan & cap/stempel
 * sekolah MASING-MASING yang SUDAH ADA secara fisik (scan/foto). Sistem
 * (aplikasi ini) TIDAK membuat/menggambar cap sekolah secara otomatis -
 * sistem hanya MENGATUR UKURAN tampilan/cetaknya supaya nanti saat
 * diunduh/dicetak hasilnya sesuai aturan ukuran resmi (SE Kadisdik
 * Sukabumi 15 Januari 2024: diameter luar cap 4 cm) - lihat
 * App\Livewire\ProfilSekolah\Index::CAP_SEKOLAH_LEBAR_PX/TINGGI_PX.
 *
 * `cap_sekolah` di-crop PERSEGI (cover 600x600px, pola sama seperti
 * `logo_sekolah` tapi resolusi lebih tinggi supaya tetap tajam saat
 * dicetak di ukuran fisik 4cm) karena stempel lazimnya difoto/discan
 * dalam bingkai persegi. `tanda_tangan_kepala_sekolah` SENGAJA TIDAK
 * di-crop persegi (hanya dibatasi lebar maksimum, rasio asli
 * dipertahankan) karena tanda tangan lazimnya memanjang (landscape),
 * crop persegi akan memotong bagian tanda tangan - lihat
 * App\Livewire\ProfilSekolah\Index::simpanTandaTanganKepalaSekolah().
 * Keduanya disimpan sebagai PNG (bukan JPEG seperti logo_sekolah) supaya
 * latar belakang transparan pada file yang diupload (kalau ada) tetap
 * terjaga, tidak dipaksa jadi putih.
 *
 * Disimpan ke disk `public` (pola sama seperti `logo_sekolah`/
 * `kop_surat`), disajikan ke layar lewat controller khusus (pola sama
 * seperti App\Http\Controllers\LogoSekolahFileController) - lihat
 * App\Http\Controllers\TandaTanganKepalaSekolahFileController &
 * App\Http\Controllers\CapSekolahFileController.
 *
 * BELUM dipasang otomatis ke dokumen PDF manapun di round ini (Fase 1) -
 * pemasangan ke dokumen PDF (Surat TPG, Formulir BOS K7, dll, dengan
 * pilihan "tanpa tanda tangan / dengan tanda tangan & cap / dengan QR
 * Code") adalah permintaan Fase 2 yang terpisah & BELUM dikerjakan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->string('tanda_tangan_kepala_sekolah')->nullable()->after('logo_sekolah');
            $table->string('cap_sekolah')->nullable()->after('tanda_tangan_kepala_sekolah');
        });
    }

    public function down(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->dropColumn(['tanda_tangan_kepala_sekolah', 'cap_sekolah']);
        });
    }
};
