<?php

namespace App\Livewire\Concerns;

use App\Models\VervalRealisasiBosp;

/**
 * Dipakai oleh 10 menu sumber data Laporan Realisasi BOSP - 8 menu asli
 * (Penerimaan Honor PTK, Langganan Daya & Jasa, Rincian Pemeliharaan
 * (Bangunan & PC), Biaya Pendaftaran Lomba/Bimtek/Workshop, Belanja
 * Honor Kegiatan (Honor Kegiatan/Makan Minum/Perjalanan Dinas), Rincian
 * Belanja Modal (KIB B/KIB E), & Rincian Belanja Barang Habis Pakai) +
 * 2 menu TAMBAHAN sejak round kesembilan (Pajak BOSP Reguler & Formulir
 * BOS K7b/K7c) - permintaan user 2026-09-23 (round keenam, poin 3,
 * jawaban AskUserQuestion: "Ceklist + data isian triwulan itu" ikut
 * jadi read-only begitu triwulan itu di-Verval "Sesuai" pada halaman
 * validasi Laporan Realisasi BOSP - lihat
 * App\Livewire\PendataanBosp\LaporanRealisasiBosp\Index::setVerval() &
 * App\Models\VervalRealisasiBosp).
 *
 * CATATAN: Laporan Realisasi BOSP sendiri (menu ke-11 dari "11 menu
 * yang punya data per triwulan/bulan", jawaban AskUserQuestion round
 * kesembilan "Cakupan kuncian") SENGAJA TIDAK memakai trait ini -
 * SELURUH kolomnya sudah jadi hasil rumus/read-only (tidak ada input
 * manual sama sekali di menu itu sejak permintaan user 2026-09-17,
 * lihat docblock App\Livewire\PendataanBosp\LaporanRealisasiBosp\Index),
 * jadi datanya otomatis "ikut terkunci" begitu SEMUA menu sumbernya
 * (termasuk 2 menu baru di atas) terkunci - tidak perlu guard terpisah.
 *
 * DIREVISI 2026-09-23 (round kesembilan, poin 2): SEBELUMNYA (round
 * keenam) kuncian ini SENGAJA berlaku untuk KEDUA peran (Admin BOSP
 * MAUPUN Superadmin). Permintaan user round kesembilan MEMBALIK
 * keputusan itu secara eksplisit: "kecuali di login superadmin semua
 * triwulan tetap terbuka" - jadi SEKARANG Superadmin DIKECUALIKAN
 * (bebas mengedit triwulan manapun kapan saja), HANYA Admin BOSP yang
 * tetap tertahan kuncian ini. Lihat abortJikaTerkunciVerval() di bawah.
 *
 * SETIAP method simpan/tambah/hapus/import pada komponen-komponen di
 * atas memanggil abortJikaTerkunciVerval($sekolahId, $tahun, $triwulan)
 * di awal (SEBELUM validasi/penyimpanan apapun) - menolak (403) dengan
 * pesan jelas kalau triwulan itu sudah terkunci (untuk Admin BOSP),
 * TIDAK mengubah perilaku SAMA SEKALI kalau triwulan itu belum/tidak
 * terkunci ATAU kalau pemanggilnya Superadmin (aturan standing "jangan
 * merubah yang sudah berfungsi dan sudah berjalan").
 */
trait MenolakEditJikaTerkunciVerval
{
    protected function terkunciVerval(int $profilSekolahId, int $tahun, int $triwulan): bool
    {
        return VervalRealisasiBosp::triwulanSudahSesuai($profilSekolahId, $tahun, $triwulan);
    }

    protected function abortJikaTerkunciVerval(int $profilSekolahId, int $tahun, int $triwulan): void
    {
        // Superadmin DIKECUALIKAN dari kuncian ini sejak round kesembilan
        // (lihat docblock trait di atas) - "kecuali di login superadmin
        // semua triwulan tetap terbuka".
        if (auth()->user()?->isSuperadmin()) {
            return;
        }

        abort_if(
            $this->terkunciVerval($profilSekolahId, $tahun, $triwulan),
            403,
            'Triwulan ini sudah di-Verval "Sesuai" pada halaman Laporan Realisasi BOSP (Form BPK) - data sudah dinyatakan valid & tidak bisa diubah lagi.'
        );
    }

    /**
     * Status kuncian UNTUK TAMPILAN (kotak input readonly/disabled, tombol
     * Tambah/Edit/Hapus/Import disembunyikan, banner kuncian) - BEDA dari
     * abortJikaTerkunciVerval() di atas yang hanya menolak PENYIMPANAN di
     * backend. Ditambahkan permintaan user 2026-09-23 (round kesepuluh,
     * poin 1): "setelah melakukan Validasi Hasil Entry Data BOSP menu-menu
     * triwulan yang di pilih masih tetap terbuka seharusnya terkunci" -
     * SEBELUM perbaikan ini, abortJikaTerkunciVerval() SUDAH benar menolak
     * penyimpanan (403) untuk triwulan yang sudah "Sesuai", TAPI kotak
     * input di tampilan (Blade) tidak pernah ikut disembunyikan/dikunci
     * secara visual - Admin BOSP masih melihat kotak seolah bisa diedit
     * padahal percobaan simpannya akan selalu gagal diam2 di belakang
     * layar. Method ini menyatukan logikanya supaya tampilan & backend
     * konsisten.
     *
     * Superadmin SELALU dianggap TIDAK terkunci di sini juga (konsisten
     * dengan pengecualian yang sama pada abortJikaTerkunciVerval() di
     * atas - "kecuali di login superadmin semua triwulan tetap terbuka").
     *
     * Dipanggil dari render() 10 komponen menu sumber yang memakai trait
     * ini - MENGASUMSIKAN komponen pemanggil sudah punya method
     * bolehKelolaSemua() & sekolahSayaId() dengan makna PERSIS SAMA
     * seperti yang dipakai abortJikaTerkunciVerval() di komponen2 itu
     * (sudah dicek konsisten di semua 10 komponen sebelum method ini
     * ditambahkan - lihat progress-log.md round kesepuluh).
     */
    protected function terkunciVervalUntukTampilan(int $triwulan): bool
    {
        if (auth()->user()?->isSuperadmin()) {
            return false;
        }

        $profilSekolahId = $this->sekolahSayaId();

        if ($profilSekolahId === null) {
            return false;
        }

        return $this->terkunciVerval($profilSekolahId, $this->tahun, $triwulan);
    }
}
