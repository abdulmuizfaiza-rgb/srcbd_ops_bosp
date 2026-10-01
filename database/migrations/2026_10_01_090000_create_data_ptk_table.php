<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menu "Data Sekolah" (dulu "Profil Sekolah") - Tab 2 "Data PTK"
 * (permintaan user 2026-10-01).
 *
 * Tabel BARU (biodata lengkap PTK per sekolah), TERPISAH dari
 * `lampiran_2a`/`lampiran_2b`/`lampiran_2c` (yang hanya berisi data gaji
 * pokok PTK per triwulan untuk kebutuhan Lampiran 2A/2B/2C) - tidak ada
 * relasi otomatis antara keduanya, murni kebetulan beberapa nama kolom
 * mirip (nama_ptk, nuptk).
 *
 * Satu sekolah bisa punya BANYAK baris PTK (1 baris = 1 orang PTK),
 * ditampilkan GABUNGAN semua sekolah dalam 1 tabel (bisa dicari/filter
 * per Nama Sekolah) - pola sama seperti Lampiran 2A/2B/2C, BUKAN halaman
 * detail per sekolah (jawaban AskUserQuestion "Gabungan semua sekolah").
 *
 * CATATAN TAFSIRAN (mohon dikoreksi kalau kurang sesuai):
 * - NIK dibuat WAJIB & UNIK (dipakai sebagai kunci saat Import Excel
 *   menimpa/update data lama - jawaban AskUserQuestion "Timpa/update data
 *   lama"), karena NIK adalah identitas kependudukan yang semestinya
 *   tidak boleh sama antar 2 PTK berbeda. NUPTK/NIP TIDAK dibuat unik
 *   (NIP misalnya bisa kosong "-" untuk Non-ASN).
 * - NIK/NUPTK disimpan sebagai STRING persis 16 digit, NIP persis 18
 *   digit (mengikuti standar baku NIK/NUPTK/NIP Indonesia, bukan
 *   "sampai dengan" 16/18 digit) - konsisten dengan validasi NRG/NUPTK/
 *   NPWP pada Lampiran 2A/2B/2C & NIP Bendahara pada Profil Sekolah yang
 *   juga memakai panjang digit PASTI (regex tetap), bukan rentang.
 * - Field yang WAJIB diisi: NIK, Nama PTK, Jabatan, Status Kepegawaian,
 *   Jenis PTK, Pendidikan Terakhir, Status Sertifikasi, Status Dapodik,
 *   Status Keaktifan. Sisanya (NUPTK, NIP, Tempat/Tanggal Lahir, Pangkat/
 *   Golongan, TMT Sekolah Induk, Jurusan/Prodi, Tahun Lulus Ijazah, &
 *   rincian sertifikasi) OPSIONAL - mengikuti pola Pendataan OPS (field
 *   identitas inti wajib, detail riwayat opsional).
 * - Tahun Lulus Ijazah & Tahun Lulus Sertifikasi disimpan sebagai STRING
 *   4 digit (bukan integer) - supaya kolom tetap kosong-bisa ('') tanpa
 *   perlu nullable-int, konsisten dengan pola field "tahun" bertipe teks
 *   singkat di tabel lain aplikasi ini saat tidak dipakai untuk hitungan
 *   matematis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_ptk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_sekolah_id')->constrained('profil_sekolah')->restrictOnDelete();

            $table->string('nik', 16)->unique();
            $table->string('nuptk', 16)->nullable();
            $table->string('nip', 18)->nullable();
            $table->string('nama_ptk');
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();

            $table->string('jabatan');
            $table->string('pangkat_golongan')->nullable();
            $table->string('status_kepegawaian');
            $table->string('jenis_ptk');
            $table->date('tmt_sekolah_induk')->nullable();

            $table->string('pendidikan_terakhir');
            $table->string('jurusan_prodi')->nullable();
            $table->string('tahun_lulus_ijazah', 4)->nullable();

            $table->string('status_sertifikasi');
            $table->string('bidang_studi_sertifikasi')->nullable();
            $table->string('tahun_lulus_sertifikasi', 4)->nullable();
            $table->string('nomor_sertifikat_sertifikasi')->nullable();
            $table->string('nomor_registrasi_guru')->nullable();
            $table->string('nomor_peserta_sertifikasi')->nullable();

            $table->string('status_dapodik');
            $table->string('status_keaktifan');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('nama_ptk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_ptk');
    }
};
