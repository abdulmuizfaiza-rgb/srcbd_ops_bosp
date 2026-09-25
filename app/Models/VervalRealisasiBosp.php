<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

// Catatan: BelanjaHonorKegiatan, BiayaPendaftaranLomba, FormulirBosK7,
// LanggananDayaJasa, PajakBospReguler, PenerimaanHonorPtk,
// RincianBelanjaBarangHabisPakai, RincianBelanjaModal,
// RincianBelanjaModalBmd, RincianPemeliharaan, & RincianPemeliharaanPc
// SENGAJA TIDAK diimport lewat `use` - kelas-kelas itu SUDAH berada di
// namespace App\Models yang sama dengan file ini, jadi bisa langsung
// dipakai memakai nama pendeknya saja (lihat adaDataUntukTriwulan() &
// triwulanAktifValidasi() di bawah).

/**
 * Verval (verifikasi & validasi) Laporan Realisasi BOSP (Form BPK) per
 * sekolah+tahun+triwulan - permintaan user 2026-09-23 (round keenam,
 * poin 3). Lihat migration `create_verval_realisasi_bosp_table` untuk
 * konteks lengkap & jawaban AskUserQuestion terkait.
 */
#[Fillable([
    'profil_sekolah_id',
    'tahun',
    'triwulan',
    'status',
    'diverval_oleh',
    'diverval_pada',
])]
class VervalRealisasiBosp extends Model
{
    protected $table = 'verval_realisasi_bosp';

    public const STATUS_SESUAI = 'sesuai';

    public const STATUS_BELUM_SESUAI = 'belum_sesuai';

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'triwulan' => 'integer',
            'diverval_pada' => 'datetime',
        ];
    }

    public function profilSekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class);
    }

    public function pemverval(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverval_oleh');
    }

    /**
     * Status verval ke-4 triwulan 1 sekolah+tahun, dikelompokkan per
     * nomor triwulan (1-4) - triwulan yang belum pernah disentuh Admin
     * BOSP TIDAK ADA di collection ini (bukan `null` di dalamnya).
     *
     * @return Collection<int, self>
     */
    public static function ambilStatus(int $profilSekolahId, int $tahun): Collection
    {
        return self::query()
            ->where('profil_sekolah_id', $profilSekolahId)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy('triwulan');
    }

    /**
     * TRUE hanya kalau KEEMPAT triwulan (1-4) sudah berstatus `sesuai` -
     * jawaban AskUserQuestion 2026-09-23 (round keenam): "Keempat TW
     * independen ... Halaman Laporan Realisasi BOSP baru aktif kalau
     * SEMUA 4 triwulan sudah di-verval". Dipakai sebagai gerbang
     * (gate) sebelum halaman laporan ditampilkan ke Admin BOSP - lihat
     * App\Livewire\PendataanBosp\LaporanRealisasiBosp\Index::render().
     */
    public static function semuaTriwulanSesuai(int $profilSekolahId, int $tahun): bool
    {
        $status = self::ambilStatus($profilSekolahId, $tahun);

        foreach ([1, 2, 3, 4] as $triwulan) {
            if (($status[$triwulan]->status ?? null) !== self::STATUS_SESUAI) {
                return false;
            }
        }

        return true;
    }

    /**
     * TRUE kalau triwulan INI SAJA sudah berstatus `sesuai` (terkunci
     * permanen) - dipakai untuk menolak edit pada 8 menu sumber data
     * (Penerimaan Honor PTK, Daya & Jasa, dst, lihat
     * App\Livewire\Concerns\MenolakEditJikaTerkunciVerval) MAUPUN untuk
     * menolak toggle ulang ceklist verval triwulan itu sendiri.
     */
    public static function triwulanSudahSesuai(int $profilSekolahId, int $tahun, int $triwulan): bool
    {
        return self::query()
            ->where('profil_sekolah_id', $profilSekolahId)
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->where('status', self::STATUS_SESUAI)
            ->exists();
    }

    /**
     * TRUE kalau MINIMAL 1 triwulan (apapun statusnya - "sesuai" MAUPUN
     * "belum_sesuai") sudah pernah diverval untuk sekolah+tahun ini -
     * permintaan user 2026-09-23 (round kedelapan, laporan bug atas Round
     * 7: "menu Laporan Realisasi BOSP (Form BPK) di validasi per triwulan
     * bukan menunggu sampai validasi triwulan 4 ... ketika klik verval
     * Sesuai/belum Sesuai halaman langsung otomatis membuka menu Laporan
     * Realisasi BOSP"). MENGGANTIKAN semuaTriwulanSesuai() sebagai syarat
     * gerbang di App\Livewire\PendataanBosp\LaporanRealisasiBosp\Index::render()
     * - semuaTriwulanSesuai() sendiri TETAP ADA (tidak dihapus, tidak lagi
     * dipakai gerbang, tapi valid dipakai lagi nanti kalau perlu).
     */
    public static function adaTriwulanSudahDiverval(int $profilSekolahId, int $tahun): bool
    {
        return self::query()
            ->where('profil_sekolah_id', $profilSekolahId)
            ->where('tahun', $tahun)
            ->exists();
    }

    /**
     * Reset PENUH kuncian verval 1 triwulan - permintaan user 2026-09-23
     * (round ketujuh, jawaban AskUserQuestion "Reset penuh (Recommended)"):
     * menghapus baris verval-nya sama sekali (bukan sekadar mengosongkan
     * `status`) supaya `triwulanSudahSesuai()` langsung kembali FALSE -
     * ini otomatis membuka KEDUA hal sekaligus: ceklist Verval triwulan
     * itu (admin BOSP bisa pilih ulang Sesuai/Belum Sesuai dari awal) DAN
     * data isian triwulan itu di 8 menu sumber (lihat
     * App\Livewire\Concerns\MenolakEditJikaTerkunciVerval - guard-nya
     * memanggil triwulanSudahSesuai(), jadi begitu barisnya tidak ada
     * lagi, guard otomatis tidak lagi menolak). HANYA boleh dipanggil
     * oleh Superadmin - lihat guard di
     * App\Livewire\PendataanBosp\LaporanRealisasiBosp\Index::resetVerval().
     */
    public static function resetTriwulan(int $profilSekolahId, int $tahun, int $triwulan): void
    {
        self::query()
            ->where('profil_sekolah_id', $profilSekolahId)
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->delete();
    }

    /**
     * 9 model yang datanya langsung punya kolom `triwulan` (1-4) - dipakai
     * baik oleh adaDataUntukTriwulan() di bawah MAUPUN (untuk 8 di
     * antaranya, TIDAK termasuk LaporanRealisasiBosp sendiri) oleh
     * App\Livewire\Concerns\MenolakEditJikaTerkunciVerval sejak round
     * keenam. RincianBelanjaModalBmd & LaporanRealisasiBosp ditambahkan ke
     * daftar INI (bukan ke trait kuncian - lihat catatan trait) round
     * kesembilan (permintaan user 2026-09-23, poin 1 & 2).
     *
     * @return array<int, class-string<Model>>
     */
    protected static function modelTriwulanLangsung(): array
    {
        return [
            PenerimaanHonorPtk::class,
            LanggananDayaJasa::class,
            RincianPemeliharaan::class,
            RincianPemeliharaanPc::class,
            BiayaPendaftaranLomba::class,
            BelanjaHonorKegiatan::class,
            RincianBelanjaModal::class,
            RincianBelanjaModalBmd::class,
            RincianBelanjaBarangHabisPakai::class,
        ];
    }

    /**
     * 2 model yang datanya per BULAN (bukan langsung per triwulan) - bulan
     * dipetakan ke triwulan lewat PajakBospReguler::TRIWULAN_BULAN (satu-
     * satunya pemetaan bulan->triwulan yang sudah ada di aplikasi, dipakai
     * ulang di sini - BUKAN duplikat baru - supaya konsisten dengan
     * App\Models\PajakBospReguler::hitungTriwulan()).
     *
     * @return array<int, class-string<Model>>
     */
    protected static function modelPerBulan(): array
    {
        return [
            PajakBospReguler::class,
            FormulirBosK7::class,
        ];
    }

    /**
     * TRUE kalau sekolah+tahun ini SUDAH punya data (apapun isinya) pada
     * SALAH SATU dari 11 menu BOSP yang datanya per triwulan/bulan, untuk
     * triwulan tertentu - permintaan user 2026-09-23 (round kesembilan,
     * poin 1, jawaban AskUserQuestion "Trigger panel muncul": "Ada data di
     * SALAH SATU menu (Recommended)"). Dipakai oleh
     * triwulanAktifValidasi() di bawah untuk menentukan kapan panel
     * "Validasi Hasil Entry Data BOSP" tampil pada
     * App\Livewire\PendataanBosp\LaporanRealisasiBosp\Index::render().
     *
     * SENGAJA TIDAK menghitung Rekap RKAS & Dana BOSP Tahap (datanya
     * per TAHUN saja, tidak ada kolom triwulan/bulan - jawaban
     * AskUserQuestion 2026-09-23 "Cakupan kuncian": "11 menu yang punya
     * data per triwulan/bulan (Recommended)").
     */
    public static function adaDataUntukTriwulan(int $profilSekolahId, int $tahun, int $triwulan): bool
    {
        foreach (self::modelTriwulanLangsung() as $model) {
            $ada = $model::query()
                ->where('profil_sekolah_id', $profilSekolahId)
                ->where('tahun', $tahun)
                ->where('triwulan', $triwulan)
                ->exists();

            if ($ada) {
                return true;
            }
        }

        $bulanTriwulanIni = PajakBospReguler::TRIWULAN_BULAN[$triwulan] ?? [];

        foreach (self::modelPerBulan() as $model) {
            $ada = $model::query()
                ->where('profil_sekolah_id', $profilSekolahId)
                ->where('tahun', $tahun)
                ->whereIn('bulan', $bulanTriwulanIni)
                ->exists();

            if ($ada) {
                return true;
            }
        }

        return false;
    }

    /**
     * Triwulan (1-4) PALING KECIL yang sudah ADA datanya (lihat
     * adaDataUntukTriwulan() di atas) TAPI BELUM PERNAH diverval sama
     * sekali (Sesuai MAUPUN Belum Sesuai) - `null` kalau tidak ada
     * triwulan seperti itu (semua triwulan yang sudah ada datanya juga
     * sudah diverval, ATAU belum ada data isian sama sekali). Ini "triwulan
     * aktif" yang menentukan tampil/hilangnya panel "Validasi Hasil Entry
     * Data BOSP" - permintaan user 2026-09-23 (round kesembilan, poin 1):
     * "muncul setiap admin bosp melakukan input data triwulan ...
     * ketika data triwulan 1 nya sudah di validasi maka form validasi ...
     * hilang otomatis dan muncul kembali saat admin mulai mengerjakan data
     * triwulan 2". Jawaban AskUserQuestion "Trigger panel hilang": "Saat
     * Sesuai ATAU Belum Sesuai" - jadi cukup ADA baris verval (status
     * apapun) untuk triwulan itu, tidak harus "Sesuai".
     */
    public static function triwulanAktifValidasi(int $profilSekolahId, int $tahun): ?int
    {
        $sudahDiverval = self::ambilStatus($profilSekolahId, $tahun);

        foreach ([1, 2, 3, 4] as $triwulan) {
            if (isset($sudahDiverval[$triwulan])) {
                continue;
            }

            if (self::adaDataUntukTriwulan($profilSekolahId, $tahun, $triwulan)) {
                return $triwulan;
            }
        }

        return null;
    }

    /**
     * Versi BORONGAN (bulk) dari adaDataUntukTriwulan() di atas -
     * mengembalikan SELURUH profil_sekolah_id yang "sudah ada data BOSP"
     * (definisi SAMA PERSIS: salah satu dari 11 menu per-triwulan/bulan,
     * TIDAK termasuk Rekap RKAS & Dana BOSP Tahap) utk 1 tahun+triwulan
     * SEKALIGUS, lewat 11 query total (bukan 11 query PER SEKOLAH seperti
     * kalau adaDataUntukTriwulan() dipanggil satu-satu di dalam loop).
     *
     * Dibutuhkan oleh Dashboard Pendataan BOSP (round ketiga belas,
     * permintaan user 2026-09-24) yang harus merekap SEMUA sekolah
     * sekaligus - memanggil adaDataUntukTriwulan() di dalam loop per
     * sekolah akan menghasilkan puluhan/ratusan query per triwulan kalau
     * jumlah sekolahnya banyak. Method INI BARU (murni tambahan) -
     * adaDataUntukTriwulan() & seluruh pemakainya yang sudah ada
     * (App\Livewire\Concerns\MenolakEditJikaTerkunciVerval,
     * triwulanAktifValidasi(), dst) SAMA SEKALI TIDAK disentuh/diubah.
     *
     * @return Collection<int, int>
     */
    public static function idSekolahAdaDataUntukTriwulan(int $tahun, int $triwulan): Collection
    {
        $idSekolah = collect();

        foreach (self::modelTriwulanLangsung() as $model) {
            $idSekolah = $idSekolah->merge(
                $model::query()
                    ->where('tahun', $tahun)
                    ->where('triwulan', $triwulan)
                    ->distinct()
                    ->pluck('profil_sekolah_id')
            );
        }

        $bulanTriwulanIni = PajakBospReguler::TRIWULAN_BULAN[$triwulan] ?? [];

        foreach (self::modelPerBulan() as $model) {
            $idSekolah = $idSekolah->merge(
                $model::query()
                    ->where('tahun', $tahun)
                    ->whereIn('bulan', $bulanTriwulanIni)
                    ->distinct()
                    ->pluck('profil_sekolah_id')
            );
        }

        return $idSekolah->unique()->values();
    }
}
