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
 * sbg kriteria pertama). INI BAGIAN FINAL (Status->Kecamatan->Nama
 * Sekolah), tidak diubah lagi di round berikutnya.
 *
 * RIWAYAT posisi Pangkat/Golongan vs Nama PTK (BARU, round terpisah
 * dari riwayat Status di atas): awalnya Nama PTK (A-Z) diletakkan
 * SEBELUM Pangkat/Golongan (dalam 1 sekolah yang sama) - user laporkan
 * "untuk urutan pangkat golongan masih ada yang salah ... saya ingin
 * dari pangkat/golongan tertinggi ke bawah per sekolah-per kecamatan
 * nya di atas" - sama persis masalahnya dgn kasus Status vs Nama
 * Sekolah sebelumnya: dgn Nama PTK SEBELUM Pangkat/Golongan, Pangkat/
 * Golongan PRAKTIS TIDAK PERNAH berpengaruh (Nama PTK sudah jadi
 * pembeda unik antar PTK). Dikonfirmasi lewat AskUserQuestion (contoh
 * konkret) -> Pangkat/Golongan dipindah ke SEBELUM Nama PTK supaya
 * benar-benar terurut tertinggi->terendah per sekolah, Nama PTK jadi
 * pembeda HANYA kalau 2 PTK pangkat/golongannya persis sama. INI JUGA
 * FINAL, jangan diubah lagi tanpa permintaan eksplisit baru.
 *
 * Klarifikasi (AskUserQuestion) yang masih berlaku: arah Pangkat/
 * Golongan = tertinggi ke terendah; arah TMT Di Sekolah Induk = paling
 * lama/senior dulu; arah Tahun Lulus Sertifikasi = paling lama (tahun
 * kecil) dulu - SEMUA konsisten "yang lebih senior/lama duluan".
 *
 * Urutan final: Status (Negeri dulu, lalu Swasta - SEMUA kecamatan,
 * jadi 2 blok besar: blok Negeri lengkap duluan, baru blok Swasta) ->
 * Kecamatan (custom, lihat URUTAN_KECAMATAN, berlaku DI DALAM
 * masing-masing blok Status) -> Nama Sekolah (A-Z) -> Pangkat/
 * Golongan (tertinggi dulu, DI DALAM 1 sekolah yang sama) -> Nama PTK
 * (A-Z, pembeda HANYA kalau pangkat/golongan persis sama) -> TMT Di
 * Sekolah Induk (paling lama dulu) -> Tahun Lulus Sertifikasi (paling
 * lama/tahun kecil dulu).
 *
 * BEDA dari urutan baku lain yang SUDAH ADA di aplikasi
 * (ProfilSekolah::scopeUrutStandar() = Status->Kecamatan->Nama Sekolah
 * - SAMA PERSIS 3 kriteria pertamanya dgn urutan final di sini,
 * kebetulan konvergen lagi ke pola yg sama; dan urutan "semua sekolah"
 * KHUSUS Lampiran 2a/2b/2c/Data PTK yang SEBELUMNYA PERNAH ada di
 * terapkanUrutan()/terapkanUrutanPtk() = Status->Nama Sekolah->
 * Kecamatan, permintaan user 2026-10-16) - bedanya dgn scopeUrutStandar():
 * Kecamatan di sini pakai urutan KHUSUS/custom (bukan abjad). Berlaku
 * juga pada tampilan 1 sekolah terfilter (sebelumnya ->latest()) -
 * level Pangkat-Golongan/Nama PTK/TMT/Tahun Lulus Sertifikasi tetap
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

            $dataA = $a->nuptk ? $dataPtkPerKunci->get($a->profil_sekolah_id.'|'.$a->nuptk) : null;
            $dataB = $b->nuptk ? $dataPtkPerKunci->get($b->profil_sekolah_id.'|'.$b->nuptk) : null;

            $cmp = self::indeksPangkatGolongan($dataA) <=> self::indeksPangkatGolongan($dataB);
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = strcasecmp((string) $a->nama_ptk, (string) $b->nama_ptk);
            if ($cmp !== 0) {
                return $cmp;
            }

            return self::bandingkanTmtDanTahunLulus($dataA, $dataB);
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

            $cmp = self::indeksPangkatGolongan($a) <=> self::indeksPangkatGolongan($b);
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = strcasecmp((string) $a->nama_ptk, (string) $b->nama_ptk);
            if ($cmp !== 0) {
                return $cmp;
            }

            return self::bandingkanTmtDanTahunLulus($a, $b);
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
     * Indeks Pangkat/Golongan (tertinggi->terendah,
     * DataPtk::pangkatGolonganUrutan() - dipakai ulang, SAMA dgn
     * App\Support\UrutanRosterTpg) - $data boleh null (Data PTK tidak
     * ditemukan via NUPTK pada urutkanLampiran()), diperlakukan sama
     * seperti pangkat_golongan-nya sendiri kosong (ditaruh akhir).
     * SEJAK 2026-10-20 (permintaan user: "dari pangkat/golongan
     * tertinggi ke bawah per sekolah ... di atas") dibandingkan
     * SEBELUM Nama PTK (lihat urutkanLampiran()/urutkanDataPtk() di
     * atas & riwayat lengkap di docblock kelas) - method ini dipisah
     * dari bandingkanTmtDanTahunLulus() di bawah (dulu 1 method
     * bandingkanAtribut()) justru supaya pemanggilnya bisa menyisipkan
     * perbandingan Nama PTK DI ANTARA Pangkat/Golongan & TMT/Tahun
     * Lulus Sertifikasi.
     */
    private static function indeksPangkatGolongan(?DataPtk $data): int
    {
        $pangkatUrutan = DataPtk::pangkatGolonganUrutan();

        return $pangkatUrutan[$data?->pangkat_golongan] ?? 999;
    }

    /**
     * TMT Di Sekolah Induk (paling lama/senior dulu) -> Tahun Lulus
     * Sertifikasi (paling lama/tahun kecil dulu) - jawaban
     * AskUserQuestion 2026-10-20, tetap jadi pembeda PALING AKHIR
     * (setelah Pangkat/Golongan & Nama PTK). $a/$b boleh null (Data
     * PTK tidak ditemukan via NUPTK pada urutkanLampiran()) -
     * diperlakukan sama seperti field-nya sendiri kosong (ditaruh
     * akhir).
     */
    private static function bandingkanTmtDanTahunLulus(?DataPtk $a, ?DataPtk $b): int
    {
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
