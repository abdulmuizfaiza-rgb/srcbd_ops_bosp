<?php

namespace App\Support;

use App\Models\DataPtk;
use App\Models\Lampiran2a;
use App\Models\ProfilSekolah;
use App\Models\RekapCairTpg;
use App\Models\RekapCairTpgBulan;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Data roster PTK + 12 kolom bulan + header (Catatan Hasil Verifikasi)
 * utk sheet "Data Cair-Belum TPG" pada Excel menu Unduhan (BARU
 * 2026-10-16, permintaan user: tambah sheet ini sbg SHEET TERAKHIR,
 * setelah Lampiran 2a/2b/2c).
 *
 * Pola scoping SAMA PERSIS dgn tab "Data Cair/Belum TPG" pada menu
 * Format Surat Rekomendasi & Pembatalan TPG (lihat App\Livewire\
 * PendataanOps\SuratTpg\Index::muatRekapCairTpg()): roster PTK = Lampiran
 * 2a GABUNGAN SEMUA TRIWULAN (1-4) dlm Tahun yg difilter (per sekolah),
 * TAPI nilai 12 kolom bulan yang ditampilkan tetap scoped ke Triwulan yg
 * sedang difilter. NIP PTK diambil dari App\Models\DataPtk (dicocokkan
 * lewat NUPTK, Lampiran 2a tidak punya field NIP).
 *
 * $profilSekolahId null = rekap gabungan semua sekolah - lihat docblock
 * App\Support\LembarValidasiTpgUnduhanData utk penjelasan lengkap pola
 * ini (kolom "Nama Sekolah" tambahan, Catatan Hasil Verifikasi
 * dikosongkan krn data header 1-sekolah).
 */
class RekapCairTpgUnduhanData
{
    /**
     * @return array{baris: SupportCollection<int, array<string, mixed>>, catatanHasilVerifikasi: ?string}
     */
    public static function ambil(int $triwulan, int $tahun, ?int $profilSekolahId): array
    {
        $queryLampiran = Lampiran2a::query()
            ->with('profilSekolah')
            ->where('tahun', $tahun);

        if ($profilSekolahId !== null) {
            $queryLampiran->where('profil_sekolah_id', $profilSekolahId)
                ->orderBy('triwulan')
                ->orderBy('id');
        } else {
            $queryLampiran->join('profil_sekolah', 'profil_sekolah.id', '=', 'lampiran_2a.profil_sekolah_id')
                ->select('lampiran_2a.*')
                ->orderByRaw("CASE WHEN profil_sekolah.status = 'negeri' THEN 0 WHEN profil_sekolah.status = 'swasta' THEN 1 ELSE 2 END")
                ->orderByRaw('profil_sekolah.kecamatan IS NULL')
                ->orderBy('profil_sekolah.kecamatan')
                ->orderBy('profil_sekolah.nama_sekolah')
                ->orderBy('lampiran_2a.triwulan')
                ->orderBy('lampiran_2a.id');
        }

        $daftarLampiran = $queryLampiran->get();

        // Dedup per (profil_sekolah_id, nuptk) - roster gabungan 4 triwulan
        // bisa mencatat PTK yang sama di lebih dari 1 triwulan (sama pola
        // dedup dgn App\Livewire\PendataanOps\SuratTpg\Index::muatRekapCairTpg()).
        $rosterUnik = $daftarLampiran
            ->unique(fn (Lampiran2a $ptk) => $ptk->profil_sekolah_id.'|'.$ptk->nuptk)
            ->values();

        // BARU 2026-10-20 (permintaan user) - urutan baris roster PTK
        // (Kepala Sekolah dulu, lalu Pangkat/Golongan utk sekolah
        // Negeri atau Abjad utk sekolah Swasta, + TMT Di Sekolah
        // Induk), lihat docblock App\Support\UrutanRosterTpg utk
        // rincian lengkap aturan & jawaban klarifikasinya. Mode "Semua
        // Sekolah" (urutkanGabungan()) HANYA mengurutkan ULANG baris
        // PTK DI DALAM masing2 kelompok sekolah - urutan ANTAR sekolah
        // (Status -> Kecamatan -> Nama Sekolah di atas) TIDAK berubah.
        $rosterUnik = $profilSekolahId !== null
            ? UrutanRosterTpg::urutkanSatuSekolah($rosterUnik, $profilSekolahId, ProfilSekolah::where('id', $profilSekolahId)->value('status'))
            : UrutanRosterTpg::urutkanGabungan($rosterUnik);

        $nipPerKunci = DataPtk::query()
            ->whereIn('profil_sekolah_id', $rosterUnik->pluck('profil_sekolah_id')->unique())
            ->whereIn('nuptk', $rosterUnik->pluck('nuptk')->unique())
            ->whereNotNull('nuptk')
            ->get(['profil_sekolah_id', 'nuptk', 'nip'])
            ->keyBy(fn ($ptk) => $ptk->profil_sekolah_id.'|'.$ptk->nuptk);

        $bulanTersimpan = RekapCairTpgBulan::query()
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->when($profilSekolahId !== null, fn ($q) => $q->where('profil_sekolah_id', $profilSekolahId))
            ->whereIn('nuptk', $rosterUnik->pluck('nuptk')->unique())
            ->get()
            ->keyBy(fn ($baris) => $baris->profil_sekolah_id.'|'.$baris->nuptk);

        $baris = $rosterUnik->map(function (Lampiran2a $ptk) use ($nipPerKunci, $bulanTersimpan) {
            $kunci = $ptk->profil_sekolah_id.'|'.$ptk->nuptk;
            $dataBulan = $bulanTersimpan->get($kunci);

            $data = [
                'nama_sekolah' => $ptk->profilSekolah->nama_sekolah ?? null,
                'nama_ptk' => $ptk->nama_ptk,
                'nip' => $nipPerKunci->get($kunci)?->nip,
            ];

            foreach (RekapCairTpgBulan::KOLOM_BULAN as $kolom) {
                $data[$kolom] = $dataBulan->{$kolom} ?? null;
            }

            return $data;
        })->values();

        $header = $profilSekolahId
            ? RekapCairTpg::query()
                ->where('profil_sekolah_id', $profilSekolahId)
                ->where('tahun', $tahun)
                ->where('triwulan', $triwulan)
                ->first()
            : null;

        return [
            'baris' => $baris,
            'catatanHasilVerifikasi' => $header->catatan_hasil_verifikasi ?? null,
        ];
    }
}
