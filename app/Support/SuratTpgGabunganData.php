<?php

namespace App\Support;

use App\Models\ProfilSekolah;
use App\Models\SuratTpg;

/**
 * Data gabungan ketiga jenis Surat TPG (Rekomendasi/Penghentian/
 * Pernyataan) utk 1 dokumen PDF/Word gabungan ("Cetak Semua"/"Unduh PDF
 * Semua"/"Unduh Word Semua") - method static supaya bisa dipakai bersama
 * dari lebih dari satu tempat tanpa duplikasi logika.
 *
 * DIEKSTRAK round kedua puluh dua (2026-09-24) dari method
 * dataSuratGabungan() yang sebelumnya ada di
 * App\Livewire\PendataanOps\SuratTpg\Index - fitur "Cetak Semua"/"Unduh
 * Semua" itu sendiri DIPINDAHKAN ke menu Unduhan (permintaan user poin 3:
 * "untuk cetak Gabungan ketiga surat (irit kertas) di pindah ke menu
 * Unduhan berdasarkan triwulan dan tahun"), jadi logika pengambilan
 * datanya dipindah ke class static tersendiri supaya bisa dipakai
 * bersama oleh App\Livewire\PendataanOps\Unduhan\Index (tombol "Cetak"/
 * "Unduh PDF"/"Unduh Word") DAN App\Http\Controllers\SuratTpgCetakSemuaController
 * (link "Cetak" yang dibuka target="_blank") tanpa duplikasi.
 *
 * Nama/NIP Kepsek, Unit Kerja, Alamat Kantor, Nama/NIP Pengawas, & Kop
 * Surat SELALU diambil langsung dari App\Models\ProfilSekolah (sama
 * seperti App\Livewire\PendataanOps\SuratTpg\Index::dataSurat()) - HANYA
 * Nomor Surat/Tanggal Surat/Tahun Pelajaran yang berbeda per jenis surat
 * (1 baris `surat_tpg` = 1 sekolah+tahun+triwulan+JENIS).
 */
class SuratTpgGabunganData
{
    /**
     * @return array<string, mixed>
     */
    public static function ambil(?int $sekolahId, int $tahun, int $triwulan): array
    {
        $sekolah = $sekolahId ? ProfilSekolah::find($sekolahId) : null;

        $data = [
            'editable' => false,
            'triwulan' => $triwulan,
            'tahun' => $tahun,
            'namaKepsek' => $sekolah?->nama_kepala_sekolah,
            'nipKepsek' => $sekolah?->nip_kepala_sekolah,
            'namaSekolah' => $sekolah?->nama_sekolah,
            'alamatSekolah' => $sekolah?->alamat_sekolah,
            'namaPengawas' => $sekolah?->nama_pengawas,
            'nipPengawas' => $sekolah?->nip_pengawas,
            'kopSuratSrc' => $sekolah?->kopSuratDataUri(),
        ];

        foreach ([SuratTpg::JENIS_REKOMENDASI, SuratTpg::JENIS_PENGHENTIAN, SuratTpg::JENIS_PERNYATAAN] as $jenis) {
            $surat = $sekolahId
                ? SuratTpg::query()
                    ->where('profil_sekolah_id', $sekolahId)
                    ->where('tahun', $tahun)
                    ->where('triwulan', $triwulan)
                    ->where('jenis', $jenis)
                    ->first()
                : null;

            $data[$jenis] = [
                'nomorSurat' => $surat->nomor_surat ?? null,
                'tanggalSurat' => $surat?->tanggal_surat,
                'tahunPelajaran' => $surat->tahun_pelajaran ?? null,
            ];
        }

        return $data;
    }
}
