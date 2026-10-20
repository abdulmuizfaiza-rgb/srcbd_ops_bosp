<?php

namespace App\Support;

use App\Models\DataPtk;
use App\Models\Lampiran2a;
use App\Models\ProfilSekolah;
use Carbon\Carbon;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Urutan baris roster PTK untuk tab "Lembar Validasi TPG" & "Data
 * Cair/Belum TPG" (menu Format Surat Rekomendasi & Pembatalan TPG,
 * TERMASUK PDF/Word-nya - lihat App\Livewire\PendataanOps\SuratTpg\
 * Index::dataSurat() yang memakai $daftarPtk/$daftarPtkCairTpg hasil
 * method2 di bawah -, + sheet Excel senama pada menu Unduhan) -
 * permintaan user 2026-10-20, dikonfirmasi lewat AskUserQuestion
 * (4 pertanyaan, semua dijawab sekaligus):
 *
 * 1. Kepala Sekolah (DataPtk::jenis_ptk === "Kepala Sekolah", lihat
 *    DataPtk::jenisPtkOptions()) SELALU baris PERTAMA pada tiap
 *    sekolah.
 * 2. Sisanya ditentukan oleh STATUS SEKOLAH (BUKAN Status Kepegawaian
 *    per-PTK - jawaban klarifikasi: "Status sekolah (Negeri/Swasta)"):
 *    - Sekolah Negeri ("ASN"): Pangkat/Golongan TERTINGGI->TERENDAH
 *      (DataPtk::pangkatGolonganUrutan(), jawaban "Tertinggi ke
 *      terendah"), lalu TMT Di Sekolah Induk PALING LAMA/SENIOR dulu
 *      (jawaban "Paling lama/senior duluan" - tanggal lebih awal
 *      duluan) sbg tie-breaker kedua.
 *    - Sekolah Swasta ("PTK SMP Swasta"): ABJAD Nama PTK (A-Z), lalu
 *      TMT Di Sekolah Induk paling lama dulu sbg tie-breaker kedua
 *      (tie-breaker SAMA dgn Negeri).
 * 3. PTK pada roster (Lampiran 2a) yang datanya TIDAK DITEMUKAN di
 *    Data PTK (NUPTK kosong/"-"/tidak cocok, jadi Jenis PTK, Pangkat/
 *    Golongan, & TMT-nya tidak diketahui) ditaruh PALING AKHIR
 *    (jawaban "Ditaruh paling akhir"), TIDAK ikut pengurutan di atas -
 *    antar-sesama PTK "tidak ditemukan" ini sendiri tetap memakai
 *    urutan asal (insertion order/urutan Lampiran 2a tersimpan).
 *    ASUMSI tambahan (bukan ditanyakan eksplisit, perluasan wajar dari
 *    aturan #3 di atas): PTK yang KETEMU datanya di Data PTK tapi TMT
 *    Di Sekolah Induk-nya sendiri masih kosong (belum diisi) tetap
 *    ikut diurutkan normal lewat Kepala Sekolah/Pangkat-Golongan/
 *    Abjad, HANYA pada tie-breaker TMT dia ditaruh paling akhir DI
 *    DALAM kelompoknya sendiri (bukan ikut ke bucket "tidak
 *    ditemukan" di atas).
 *
 * SATU SUMBER KEBENARAN dipakai bersama oleh:
 * - App\Livewire\PendataanOps\SuratTpg\Index::muatLembarValidasi()/
 *   muatRekapCairTpg() lewat urutkanSatuSekolah() (scope method2 itu
 *   SELALU 1 sekolah, lihat sekolahAktifId()).
 * - App\Support\LembarValidasiTpgUnduhanData/RekapCairTpgUnduhanData
 *   lewat urutkanSatuSekolah() (mode 1 sekolah) ATAU urutkanGabungan()
 *   (mode "Semua Sekolah" - roster SUDAH di-group per sekolah secara
 *   berurutan oleh query pemanggil, method ini HANYA mengurutkan ULANG
 *   baris PTK DI DALAM masing2 kelompok sekolah, urutan ANTAR sekolah
 *   TIDAK diubah).
 */
class UrutanRosterTpg
{
    /**
     * @param  SupportCollection<int, Lampiran2a>  $roster  harus punya kolom nuptk & nama_ptk terisi (profil_sekolah_id boleh tidak ada, dioper lewat parameter)
     * @return SupportCollection<int, Lampiran2a>
     */
    public static function urutkanSatuSekolah(SupportCollection $roster, int $profilSekolahId, ?string $statusSekolah): SupportCollection
    {
        if ($roster->count() <= 1) {
            return $roster->values();
        }

        $nuptkValid = $roster->pluck('nuptk')
            ->reject(fn ($nuptk) => $nuptk === null || $nuptk === '' || $nuptk === DataPtk::TANDA_KOSONG)
            ->unique()
            ->values();

        $dataPtkPerNuptk = DataPtk::query()
            ->where('profil_sekolah_id', $profilSekolahId)
            ->whereIn('nuptk', $nuptkValid)
            ->get(['nuptk', 'jenis_ptk', 'pangkat_golongan', 'tmt_sekolah_induk'])
            ->keyBy('nuptk');

        $adaDataPtk = fn (Lampiran2a $ptk): bool => $ptk->nuptk
            && $ptk->nuptk !== DataPtk::TANDA_KOSONG
            && $dataPtkPerNuptk->has($ptk->nuptk);

        $ditemukan = $roster->filter($adaDataPtk)->values();
        $tidakDitemukan = $roster->reject($adaDataPtk)->values();

        $pangkatUrutan = DataPtk::pangkatGolonganUrutan();

        $terurut = $ditemukan->sort(function (Lampiran2a $a, Lampiran2a $b) use ($dataPtkPerNuptk, $pangkatUrutan, $statusSekolah) {
            $dataA = $dataPtkPerNuptk->get($a->nuptk);
            $dataB = $dataPtkPerNuptk->get($b->nuptk);

            // 1. Kepala Sekolah selalu paling atas.
            $kepsekA = $dataA->jenis_ptk === 'Kepala Sekolah' ? 0 : 1;
            $kepsekB = $dataB->jenis_ptk === 'Kepala Sekolah' ? 0 : 1;

            $cmp = $kepsekA <=> $kepsekB;
            if ($cmp !== 0) {
                return $cmp;
            }

            // Kedua-nya Kepala Sekolah (data ganda, jarang terjadi) -
            // langsung ke tie-breaker TMT supaya tetap deterministik,
            // tidak perlu ikut cabang Pangkat/Golongan atau Abjad.
            if ($kepsekA === 1) {
                // 2. Sisanya (bukan Kepala Sekolah) tergantung status sekolah.
                if ($statusSekolah === ProfilSekolah::STATUS_NEGERI) {
                    $cmp = ($pangkatUrutan[$dataA->pangkat_golongan] ?? 999) <=> ($pangkatUrutan[$dataB->pangkat_golongan] ?? 999);
                } else {
                    $cmp = strcasecmp((string) $a->nama_ptk, (string) $b->nama_ptk);
                }

                if ($cmp !== 0) {
                    return $cmp;
                }
            }

            return self::bandingkanTmt($dataA->tmt_sekolah_induk, $dataB->tmt_sekolah_induk);
        })->values();

        return $terurut->concat($tidakDitemukan)->values();
    }

    /**
     * @param  SupportCollection<int, Lampiran2a>  $roster  harus punya kolom profil_sekolah_id, nuptk, & nama_ptk terisi (hasil select('lampiran_2a.*') penuh)
     * @return SupportCollection<int, Lampiran2a>
     */
    public static function urutkanGabungan(SupportCollection $roster): SupportCollection
    {
        if ($roster->isEmpty()) {
            return $roster;
        }

        $statusPerSekolah = ProfilSekolah::query()
            ->whereIn('id', $roster->pluck('profil_sekolah_id')->unique())
            ->pluck('status', 'id');

        return $roster
            ->groupBy('profil_sekolah_id')
            ->flatMap(fn (SupportCollection $grup, int $profilSekolahId) => self::urutkanSatuSekolah(
                $grup->values(),
                $profilSekolahId,
                $statusPerSekolah->get($profilSekolahId)
            ))
            ->values();
    }

    /**
     * null (TMT belum diisi) ditaruh PALING AKHIR di antara sesama
     * kelompoknya sendiri - lihat ASUMSI pada docblock class di atas.
     */
    private static function bandingkanTmt(?Carbon $tmtA, ?Carbon $tmtB): int
    {
        if ($tmtA === null && $tmtB === null) {
            return 0;
        }

        if ($tmtA === null) {
            return 1;
        }

        if ($tmtB === null) {
            return -1;
        }

        return $tmtA->getTimestamp() <=> $tmtB->getTimestamp();
    }
}
