<?php

namespace App\Livewire\Concerns;

use App\Models\DeadlinePekerjaan;

/**
 * Fondasi penguncian input fitur "Timeline Pekerjaan" (round kedelapan,
 * bagian B, jawaban AskUserQuestion "Efek terlambat" -> "Ikut memblokir
 * input"). BEDA dari App\Livewire\Concerns\MenolakEditJikaTerkunciVerval
 * (yang terkait status Verval per SEKOLAH+tahun+triwulan) - trait ini
 * terkait deadline GLOBAL per kunci_menu+tahun (SAMA untuk semua
 * sekolah, lihat App\Models\DeadlinePekerjaan).
 *
 * TRAIT INI DIBUAT DI ROUND KEDELAPAN TAPI BELUM DITERAPKAN ke 16 menu
 * terkait (lihat App\Models\DeadlinePekerjaan::daftarTahapKerja()) -
 * penerapannya menyusul di round berikutnya setelah fondasi tabel &
 * halaman Timeline Pekerjaan teruji baik (standing rule "jangan merubah
 * yang sudah berfungsi dan sudah berjalan" - penerapan ke banyak menu
 * sekaligus sengaja dipisah supaya tidak berisiko merusak menu yang
 * sudah berjalan dalam 1 perubahan besar).
 *
 * Cara pakai nanti (sama seperti MenolakEditJikaTerkunciVerval): setiap
 * method simpan/tambah/hapus/import pada komponen terkait memanggil
 * abortJikaLewatDeadline($kunciMenuSaya, $tahun, $triwulan) di awal
 * (SEBELUM validasi/penyimpanan apapun).
 *
 * Parameter $triwulan ditambahkan round kesembilan bagian B (permintaan
 * user 2026-09-23, poin 3) mengikuti App\Models\DeadlinePekerjaan yang
 * sekarang punya dimensi triwulan - lihat migration
 * `add_triwulan_to_deadline_pekerjaan_table`.
 */
trait MenolakEditJikaLewatDeadline
{
    protected function lewatDeadline(string $kunciMenu, int $tahun, int $triwulan): bool
    {
        return DeadlinePekerjaan::sudahLewat($kunciMenu, $tahun, $triwulan);
    }

    protected function abortJikaLewatDeadline(string $kunciMenu, int $tahun, int $triwulan): void
    {
        abort_if(
            $this->lewatDeadline($kunciMenu, $tahun, $triwulan),
            403,
            "Batas waktu (deadline) pengerjaan menu ini untuk Triwulan {$triwulan} tahun {$tahun} sudah lewat - data tidak bisa diubah lagi. Hubungi Superadmin untuk reset/perpanjangan deadline di menu Timeline Pekerjaan."
        );
    }
}
