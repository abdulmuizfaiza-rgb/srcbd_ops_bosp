<?php

namespace App\Support;

use App\Models\DataPtk;
use App\Models\ProfilSekolah;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Urutan baku untuk menu Lampiran 2a/2b/2c & Data PTK (termasuk
 * unduhan Excel/PDF Lampiran 2a/2b/2c di menu Unduhan) - permintaan
 * awal user 2026-10-20: "diurutkan berdasarkan Kecamatan (urutan
 * kecamatannya Caringin-Cicantayan-Cibadak-Cikidang-Nagrak)-Nama
 * Sekolah, Nama PTK, Pangkat/Golongan-TMT Disekolah Induk-Tahun Lulus
 * Sertifikasi", DITAMBAH susulan (user lupa menyebutkan): "berdasarkan
 * Negeri kemudian swasta".
 *
 * RIWAYAT posisi Status vs Kecamatan (BANYAK kali bolak-balik,
 * SEKARANG SUDAH FINAL - permintaan terakhir SANGAT eksplisit &
 * tidak ambigu, lihat poin 5):
 * (1) Kecamatan->Status->Nama Sekolah (jawaban AskUserQuestion
 * pertama); (2) user koreksi literal "Kecamatan-Nama Sekolah-Status"
 * -> Status dipindah SETELAH Nama Sekolah; (3) user laporkan "Negeri
 * dulu kemudian swasta belum berubah" - diselidiki: dgn Status
 * SETELAH Nama Sekolah, Status PRAKTIS TIDAK PERNAH berpengaruh
 * (Nama Sekolah sudah jadi pembeda unik antar sekolah) -> AskUserQuestion
 * -> user balik ke Kecamatan->Status->Nama Sekolah; (4) user LAPORKAN
 * LAGI "masih swasta paling atas, bahkan beda antara online & lokal" ->
 * diinvestigasi TUNTAS pakai data ASLI (script diagnosa dijalankan
 * user di KEDUA environment, lihat Kecamatan Caringin yg kebetulan
 * baru ada data Swasta) -> TERBUKTI kode SUDAH benar sesuai Kecamatan
 * sbg prioritas utama (bukan bug) -> ditanya via AskUserQuestion mau
 * dipertahankan (Kecamatan dulu) atau diganti total (Status dulu) ->
 * user PERTAMA pilih "pertahankan Kecamatan dulu"; (5) TAPI LANGSUNG
 * SETELAHNYA user mengoreksi lagi dgn kalimat SANGAT eksplisit: "saya
 * ingin SMP Negeri dulu semua per kecamatan kemudian baru setelah SMP
 * Negeri baru SMP Swasta Per kecamatan" - ini BERARTI Status jadi
 * KRITERIA PERTAMA/TERATAS (SEMUA sekolah Negeri lintas kecamatan utuh
 * sbg 1 blok besar duluan, BARU setelah blok Negeri habis baru blok
 * Swasta, keduanya tetap urut Kecamatan DI DALAM blok masing-masing) -
 * KEBALIKAN TOTAL dari posisi (1)-(4) di atas (yg semuanya Kecamatan
 * sbg kriteria pertama). INI URUTAN FINAL (Status->Kecamatan), jangan
 * diubah lagi tanpa permintaan eksplisit baru yg jelas bedanya.
 *
 * Klarifikasi (AskUserQuestion) yang masih berlaku: arah Pangkat/
 * Golongan = tertinggi ke terendah; arah TMT Di Sekolah Induk = paling
 * lama/senior dulu; arah Tahun Lulus Sertifikasi = paling lama (tahun
 * kecil) dulu - SEMUA konsisten "yang lebih senior/lama duluan".
 *
 * Urutan final: Status (Negeri dulu, lalu Swasta - SEMUA kecamatan,
 * jadi 2 blok besar: blok Negeri lengkap duluan, baru blok Swasta) ->
 * Kecamatan (custom, lihat URUTAN_KECAMATAN, berlaku DI DALAM
 * masing-masing blok Status) -> Nama Sekolah (A-Z) -> Nama PTK (A-Z)
 * -> Pangkat/Golongan (tertinggi dulu) -> TMT Di Sekolah Induk (paling
 * lama dulu) -> Tahun Lulus Sertifikasi (paling lama/tahun kecil dulu).
 *
 * BEDA dari urutan baku lain yang SUDAH ADA di aplikasi
 * (ProfilSekolah::scopeUrutStandar() = Status->Kecamatan->Nama Sekolah
 * - SAMA PERSIS 2 kriteria pertamanya dgn urutan final di sini,
 * kebetulan konvergen lagi ke pola yg sama; dan urutan "semua sekolah"
 * KHUSUS Lampiran 2a/2b/2c/Data PTK yang SEBELUMNYA PERNAH ada di
 * terapkanUrutan()/terapkanUrutanPtk() = Status->Nama Sekolah->
 * Kecamatan, permintaan user 2026-10-16) - bedanya dgn scopeUrutStandar():
 * Kecamatan di sini pakai urutan KHUSUS/custom (bukan abjad). Berlaku
 * juga pada tampilan 1 sekolah terfilter (sebelumnya ->latest()) -
 * level Nama PTK/Pangkat-Golongan/TMT/Tahun Lulus Sertifikasi tetap
 * relevan di sana (Status/Kecamatan/Nama Sekolah otomatis jadi tidak
 * berpengaruh krn semua baris berasal dari 1 sekolah yang sama).
 *
 * Diimplementasikan sebagai SORT DI LEVEL PHP (bukan SQL ORDER BY) -
 * pola SAMA PERSIS dgn App\Support\UrutanRosterTpg (round sebelumnya,
 * 2026-10-20) - karena Pangkat/Golongan, TMT Di Sekolah Induk, & Tahun
 * Lulus Sertifikasi TIDAK ada di tabel Lampiran 2a/2b/2c itu sendiri,
 * harus di-JOIN lewat NUPTK ke tabel Data PTK.
 */
class UrutanDaftarPtkKecamatan
{
    private const URUTAN_KECAMATAN = ['Caringin', 'Cicantayan', 'Cibadak', 'Cikidang', 'Nagrak'];

    /**
     * Untuk Lampiran 2a/2b/2c (model punya profil_sekolah_id, nuptk, &
     * nama_ptk - Pangkat/Golongan, TMT Di Sekolah Induk, & Tahun Lulus
     * Sertifikasi diambil lewat JOIN NUPTK -> Data PTK, per sekolah
     * supaya NUPTK yang sama di sekolah berbeda tidak tertukar). Baris
     * yang NUPTK-nya kosong/"-"/tidak ditemukan di Data PTK TETAP ikut
     * diurutkan normal lewat Nama PTK - hanya Pangkat/Golongan, TMT, &
     * Tahun Lulus Sertifikasi-nya yang dianggap kosong (ditaruh akhir DI
     * DALAM kelompok Nama PTK yang sama, lihat bandingkanAtribut()).
     *
     * @param  SupportCollection<int, \Illuminate\Database\Eloquent\Model>  $roster  harus punya profil_sekolah_id, nuptk, & nama_ptk terisi
     * @return SupportCollection<int, \Illuminate\Database\Eloquent\Model>
     */
    public static function urutkanLampiran(SupportCollection $roster): SupportCollection
    {
        if ($roster->count() <= 1) {
            return $roster->values();
        }

        $idSekolah = $roster->pluck('profil_sekolah_id')->unique()->values();

        $sekolahPerId = ProfilSekolah::query()
            ->whereIn('id', $idSekolah)
            ->get(['id', 'kecamatan', 'nama_sekolah', 'status'])
            ->keyBy('id');

        $nuptkValid = $roster->pluck('nuptk')
            ->reject(fn ($nuptk) => $nuptk === null || $nuptk === '' || $nuptk === DataPtk::TANDA_KOSONG)
            ->unique()
            ->values();

        $dataPtkPerKunci = DataPtk::query()
            ->whereIn('profil_sekolah_id', $idSekolah)
            ->whereIn('nuptk', $nuptkValid)
            ->get(['profil_sekolah_id', 'nuptk', 'pangkat_golongan', 'tmt_sekolah_induk', 'tahun_lulus_sertifikasi'])
            ->keyBy(fn (DataPtk $d) => $d->profil_sekolah_id.'|'.$d->nuptk);

        return $roster->sort(function ($a, $b) use ($sekolahPerId, $dataPtkPerKunci) {
            $sekolahA = $sekolahPerId->get($a->profil_sekolah_id);
            $sekolahB = $sekolahPerId->get($b->profil_sekolah_id);

            $cmp = self::indeksStatus($sekolahA?->status) <=> self::indeksStatus($sekolahB?->status);
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = self::indeksKecamatan($sekolahA?->kecamatan) <=> self::indeksKecamatan($sekolahB?->kecamatan);
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = strcasecmp((string) $sekolahA?->nama_sekolah, (string) $sekolahB?->nama_sekolah);
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = strcasecmp((string) $a->nama_ptk, (string) $b->nama_ptk);
            if ($cmp !== 0) {
                return $cmp;
            }

            $dataA = $a->nuptk ? $dataPtkPerKunci->get($a->profil_sekolah_id.'|'.$a->nuptk) : null;
            $dataB = $b->nuptk ? $dataPtkPerKunci->get($b->profil_sekolah_id.'|'.$b->nuptk) : null;

            return self::bandingkanAtribut($dataA, $dataB);
        })->values();
    }

    /**
     * Untuk Data PTK (model DataPtk sendiri - semua atribut native,
     * TIDAK perlu JOIN NUPTK seperti urutkanLampiran()).
     *
     * @param  SupportCollection<int, DataPtk>  $roster
     * @return SupportCollection<int, DataPtk>
     */
    public static function urutkanDataPtk(SupportCollection $roster): SupportCollection
    {
        if ($roster->count() <= 1) {
            return $roster->values();
        }

        $idSekolah = $roster->pluck('profil_sekolah_id')->unique()->values();

        $sekolahPerId = ProfilSekolah::query()
            ->whereIn('id', $idSekolah)
            ->get(['id', 'kecamatan', 'nama_sekolah', 'status'])
            ->keyBy('id');

        return $roster->sort(function (DataPtk $a, DataPtk $b) use ($sekolahPerId) {
            $sekolahA = $sekolahPerId->get($a->profil_sekolah_id);
            $sekolahB = $sekolahPerId->get($b->profil_sekolah_id);

            $cmp = self::indeksStatus($sekolahA?->status) <=> self::indeksStatus($sekolahB?->status);
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = self::indeksKecamatan($sekolahA?->kecamatan) <=> self::indeksKecamatan($sekolahB?->kecamatan);
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = strcasecmp((string) $sekolahA?->nama_sekolah, (string) $sekolahB?->nama_sekolah);
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = strcasecmp((string) $a->nama_ptk, (string) $b->nama_ptk);
            if ($cmp !== 0) {
                return $cmp;
            }

            return self::bandingkanAtribut($a, $b);
        })->values();
    }

    /**
     * Indeks Kecamatan sesuai urutan custom yang diminta user (BUKAN
     * abjad) - Kecamatan di luar daftar (data lama/tidak standar)
     * ditaruh paling akhir (perluasan wajar, bukan ditanyakan eksplisit).
     */
    private static function indeksKecamatan(?string $kecamatan): int
    {
        $indeks = array_search($kecamatan, self::URUTAN_KECAMATAN, true);

        return $indeks === false ? count(self::URUTAN_KECAMATAN) : $indeks;
    }

    /**
     * Status Negeri didahulukan dari Swasta (& status lain/kosong yang
     * tidak dikenal) - pola SAMA PERSIS dgn
     * ProfilSekolah::scopeUrutStandar() (CASE WHEN status = Negeri THEN
     * 0 ELSE 1). Posisi FINAL: kriteria PERTAMA/TERATAS (SEBELUM
     * Kecamatan) - permintaan eksplisit user "SMP Negeri dulu semua...
     * baru setelah SMP Negeri baru SMP Swasta" (lihat riwayat lengkap
     * posisi Status vs Kecamatan di docblock kelas di atas, poin 5).
     */
    private static function indeksStatus(?string $status): int
    {
        return $status === ProfilSekolah::STATUS_NEGERI ? 0 : 1;
    }

    /**
     * Pangkat/Golongan (tertinggi->terendah, DataPtk::pangkatGolonganUrutan()
     * - dipakai ulang, SAMA dgn App\Support\UrutanRosterTpg) -> TMT Di
     * Sekolah Induk (paling lama/senior dulu) -> Tahun Lulus
     * Sertifikasi (paling lama/tahun kecil dulu) - jawaban
     * AskUserQuestion 2026-10-20. $a/$b boleh null (Data PTK tidak
     * ditemukan via NUPTK pada urutkanLampiran()) - diperlakukan sama
     * seperti field-nya sendiri kosong (ditaruh akhir).
     */
    private static function bandingkanAtribut(?DataPtk $a, ?DataPtk $b): int
    {
        $pangkatUrutan = DataPtk::pangkatGolonganUrutan();

        $cmp = ($pangkatUrutan[$a?->pangkat_golongan] ?? 999) <=> ($pangkatUrutan[$b?->pangkat_golongan] ?? 999);
        if ($cmp !== 0) {
            return $cmp;
        }

        $cmp = self::bandingkanTmt($a?->tmt_sekolah_induk, $b?->tmt_sekolah_induk);
        if ($cmp !== 0) {
            return $cmp;
        }

        return self::bandingkanTahunLulusSertifikasi($a?->tahun_lulus_sertifikasi, $b?->tahun_lulus_sertifikasi);
    }

    /** null (TMT belum diisi, atau Data PTK tidak ditemukan) ditaruh PALING AKHIR. */
    private static function bandingkanTmt($tmtA, $tmtB): int
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

    /**
     * Tahun Lulus Sertifikasi bisa berisi angka (string numerik), NULL,
     * atau tanda "-" (DataPtk::TANDA_KOSONG, saat Status Sertifikasi =
     * Belum) - ketiga bentuk "kosong" ini ditaruh PALING AKHIR.
     */
    private static function bandingkanTahunLulusSertifikasi(?string $a, ?string $b): int
    {
        $angkaA = ctype_digit((string) $a) ? (int) $a : null;
        $angkaB = ctype_digit((string) $b) ? (int) $b : null;

        if ($angkaA === null && $angkaB === null) {
            return 0;
        }

        if ($angkaA === null) {
            return 1;
        }

        if ($angkaB === null) {
            return -1;
        }

        return $angkaA <=> $angkaB;
    }
}
