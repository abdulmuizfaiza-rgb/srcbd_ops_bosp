<?php

namespace App\Support;

use App\Models\Lampiran2a;
use App\Models\LembarValidasiTpg;
use App\Models\LembarValidasiTpgCeklist;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Data roster PTK + ceklist 8 kriteria + header (Tanggal Verifikasi &
 * Catatan Hasil Verifikasi) utk sheet "Lembar Validasi TPG" pada Excel
 * menu Unduhan (Pendataan OPS > Unduhan, BARU 2026-10-16, permintaan
 * user: tambah sheet ini sbg SHEET PERTAMA, sebelum Lampiran 2a/2b/2c).
 *
 * Pola scoping SAMA PERSIS dgn tab "Lembar Validasi TPG" pada menu
 * Format Surat Rekomendasi & Pembatalan TPG (lihat App\Livewire\
 * PendataanOps\SuratTpg\Index::muatLembarValidasi()): roster PTK dari
 * Lampiran 2a utk 1 Triwulan+Tahun yang sedang difilter (BUKAN gabungan
 * seluruh triwulan - beda dgn App\Support\RekapCairTpgUnduhanData),
 * ceklist dicocokkan lewat NUPTK.
 *
 * $profilSekolahId null berarti rekap gabungan SEMUA sekolah (khusus
 * Superadmin, pola query sama persis dgn App\Support\UnduhanLampiranData)
 * - setiap baris diberi tambahan 'nama_sekolah', diurutkan Status ->
 * Kecamatan -> Nama Sekolah, dan Tanggal Verifikasi/Catatan Hasil
 * Verifikasi (data header 1-sekolah) dikosongkan - tidak ada satupun
 * nilai tunggal yang mewakili semua sekolah sekaligus, sama alasannya
 * dgn lembar tanda tangan yang dilewati pada App\Exports\
 * Lampiran2aExport::tulisLembarTandaTangan() saat rekap gabungan.
 */
class LembarValidasiTpgUnduhanData
{
    /**
     * @return array{baris: SupportCollection<int, array<string, mixed>>, tanggalVerifikasi: ?string, catatanHasilVerifikasi: ?string}
     */
    public static function ambil(int $triwulan, int $tahun, ?int $profilSekolahId): array
    {
        $queryLampiran = Lampiran2a::query()
            ->with('profilSekolah')
            ->where('triwulan', $triwulan)
            ->where('tahun', $tahun);

        if ($profilSekolahId !== null) {
            $queryLampiran->where('profil_sekolah_id', $profilSekolahId)->orderBy('id');
        } else {
            $queryLampiran->join('profil_sekolah', 'profil_sekolah.id', '=', 'lampiran_2a.profil_sekolah_id')
                ->select('lampiran_2a.*')
                ->orderByRaw("CASE WHEN profil_sekolah.status = 'negeri' THEN 0 WHEN profil_sekolah.status = 'swasta' THEN 1 ELSE 2 END")
                ->orderByRaw('profil_sekolah.kecamatan IS NULL')
                ->orderBy('profil_sekolah.kecamatan')
                ->orderBy('profil_sekolah.nama_sekolah')
                ->orderBy('lampiran_2a.id');
        }

        $daftarLampiran = $queryLampiran->get();

        $ceklistTersimpan = LembarValidasiTpgCeklist::query()
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->when($profilSekolahId !== null, fn ($q) => $q->where('profil_sekolah_id', $profilSekolahId))
            ->whereIn('nuptk', $daftarLampiran->pluck('nuptk'))
            ->get()
            ->keyBy(fn ($ceklist) => $ceklist->profil_sekolah_id.'|'.$ceklist->nuptk);

        $baris = $daftarLampiran->map(function (Lampiran2a $ptk) use ($ceklistTersimpan) {
            $ceklist = $ceklistTersimpan->get($ptk->profil_sekolah_id.'|'.$ptk->nuptk);

            $data = [
                'nama_sekolah' => $ptk->profilSekolah->nama_sekolah ?? null,
                'nama_ptk' => $ptk->nama_ptk,
            ];

            foreach (LembarValidasiTpgCeklist::KOLOM_CEKLIST as $kolom) {
                $data[$kolom] = (bool) ($ceklist->{$kolom} ?? false);
            }

            return $data;
        })->values();

        $header = $profilSekolahId
            ? LembarValidasiTpg::query()
                ->where('profil_sekolah_id', $profilSekolahId)
                ->where('tahun', $tahun)
                ->where('triwulan', $triwulan)
                ->first()
            : null;

        return [
            'baris' => $baris,
            'tanggalVerifikasi' => $header?->tanggal_verifikasi?->format('d-m-Y'),
            'catatanHasilVerifikasi' => $header->catatan_hasil_verifikasi ?? null,
        ];
    }
}
