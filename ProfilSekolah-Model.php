<?php

namespace App\Models;

use Database\Factories\ProfilSekolahFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'npsn',
    'nama_sekolah',
    'status',
    'kecamatan',
    'nama_kepala_sekolah',
    'nip_kepala_sekolah',
    'no_whatsapp_kepala_sekolah',
    'status_kepegawaian_kepsek',
    'nama_pengawas',
    'nip_pengawas',
    'nama_bendahara',
    'nip_bendahara',
    'status_kepegawaian_bendahara',
    'alamat_sekolah',
])]
class ProfilSekolah extends Model
{
    /** @use HasFactory<ProfilSekolahFactory> */
    use HasFactory;

    protected $table = 'profil_sekolah';

    public const STATUS_NEGERI = 'negeri';

    public const STATUS_SWASTA = 'swasta';

    /**
     * Daftar status sekolah yang tersedia beserta labelnya.
     *
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_NEGERI => 'Negeri',
            self::STATUS_SWASTA => 'Swasta',
        ];
    }

    /**
     * Pilihan status kepegawaian untuk Kepala Sekolah & Bendahara.
     *
     * @return array<string, string>
     */
    public static function statusKepegawaianOptions(): array
    {
        return [
            'PNS' => 'PNS',
            'ASN PPPK' => 'ASN PPPK',
            'ASN PPPK-PW' => 'ASN PPPK-PW',
            'Honorer' => 'Honorer',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusOptions()[$this->status] ?? '-';
    }

    /**
     * Field yang WAJIB dilengkapi oleh Admin OPS/Admin BOSP (di luar
     * NPSN/Nama Sekolah/Status/Kecamatan yang diisi Superadmin) supaya
     * profil sekolah dianggap lengkap/updated - dipakai untuk gate
     * onboarding: Admin OPS/Admin BOSP wajib melengkapi ini dulu sebelum
     * bisa membuka menu lain.
     */
    public function isLengkap(): bool
    {
        $wajibDiisi = [
            $this->nama_kepala_sekolah,
            $this->nip_kepala_sekolah,
            $this->no_whatsapp_kepala_sekolah,
            $this->status_kepegawaian_kepsek,
            $this->nama_pengawas,
            $this->nip_pengawas,
            $this->nama_bendahara,
            $this->nip_bendahara,
            $this->status_kepegawaian_bendahara,
            $this->alamat_sekolah,
        ];

        foreach ($wajibDiisi as $nilai) {
            if ($nilai === null || $nilai === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Akun Admin OPS/Admin BOSP yang terhubung ke sekolah ini.
     *
     * @return HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Data Identitas OPS sekolah ini (jika sudah diisi).
     *
     * @return HasOne<PendataanOps>
     */
    public function identitasOps(): HasOne
    {
        return $this->hasOne(PendataanOps::class);
    }

    /**
     * Data Identitas Admin BOSP sekolah ini (jika sudah diisi).
     *
     * @return HasOne<PendataanBosp>
     */
    public function identitasBosp(): HasOne
    {
        return $this->hasOne(PendataanBosp::class);
    }

    /**
     * Data Rekap RKAS Awal-Perubahan sekolah ini, lintas seluruh tahun
     * (satu baris per tahun) - beda dengan identitasOps/identitasBosp yang
     * cuma 1 data per sekolah tanpa dimensi tahun.
     *
     * @return HasMany<RekapRkas>
     */
    public function rekapRkas(): HasMany
    {
        return $this->hasMany(RekapRkas::class);
    }

    /**
     * Data Penerimaan Honor PTK sekolah ini, lintas seluruh tahun &
     * triwulan (1 sekolah bisa punya BANYAK baris - beda dengan
     * rekapRkas() yang cuma 1 baris per tahun).
     *
     * @return HasMany<PenerimaanHonorPtk>
     */
    public function penerimaanHonorPtk(): HasMany
    {
        return $this->hasMany(PenerimaanHonorPtk::class);
    }

    /**
     * Data Langganan Daya dan Jasa sekolah ini, lintas seluruh tahun &
     * triwulan (1 sekolah bisa punya BANYAK baris, pola sama seperti
     * penerimaanHonorPtk()).
     *
     * @return HasMany<LanggananDayaJasa>
     */
    public function langgananDayaJasa(): HasMany
    {
        return $this->hasMany(LanggananDayaJasa::class);
    }

    /**
     * Data Rincian Pemeliharaan (Belanja Pemeliharaan Bangunan) sekolah
     * ini, lintas seluruh tahun/triwulan/jenis ('barang'/'jasa') - pola
     * sama seperti langgananDayaJasa(). Query pemanggil bertanggung
     * jawab menyaring kolom "jenis" sesuai tab utama yang aktif (lihat
     * App\Livewire\PendataanBosp\BelanjaPemeliharaanBangunan\Index).
     *
     * @return HasMany<RincianPemeliharaan>
     */
    public function rincianPemeliharaan(): HasMany
    {
        return $this->hasMany(RincianPemeliharaan::class);
    }

    /**
     * Relasi ke menu "Belanja Pemeliharaan PC Komputer-Laptop-Printer
     * dll" (Part 18) - tabel TERPISAH dari rincian_pemeliharaan (menu
     * Bangunan), sama seperti rincianPemeliharaan() di atas, kolom
     * "jenis" membedakan tab "barang"/"jasa" - lihat
     * App\Livewire\PendataanBosp\BelanjaPemeliharaanPc\Index.
     *
     * @return HasMany<RincianPemeliharaanPc>
     */
    public function rincianPemeliharaanPc(): HasMany
    {
        return $this->hasMany(RincianPemeliharaanPc::class);
    }

    /**
     * Relasi ke menu baru "Biaya Pendaftaran Lomba/Bimtek/Workshop"
     * (2026-09-11) - tabel SENDIRI (biaya_pendaftaran_lomba), terpisah
     * dari menu-menu lain - lihat
     * App\Livewire\PendataanBosp\BiayaPendaftaranLomba\Index.
     *
     * @return HasMany<BiayaPendaftaranLomba>
     */
    public function biayaPendaftaranLomba(): HasMany
    {
        return $this->hasMany(BiayaPendaftaranLomba::class);
    }

    /**
     * Relasi ke menu baru "Belanja Honor Kegiatan" (2026-09-11) - tabel
     * SENDIRI (belanja_honor_kegiatan), terpisah dari menu-menu lain
     * (termasuk biayaPendaftaranLomba() di atas, walau field-nya
     * identik) - lihat
     * App\Livewire\PendataanBosp\BelanjaHonorKegiatan\Index.
     *
     * @return HasMany<BelanjaHonorKegiatan>
     */
    public function belanjaHonorKegiatan(): HasMany
    {
        return $this->hasMany(BelanjaHonorKegiatan::class);
    }

    /**
     * Relasi ke menu baru "Rincian Belanja Modal" (2026-09-11, Part 21) -
     * tabel SENDIRI (rincian_belanja_modal), 2 tab utama ("peralatan_mesin"
     * / "aset_tetap_lainnya") dibedakan lewat kolom "jenis", sama seperti
     * rincianPemeliharaan() di atas - lihat
     * App\Livewire\PendataanBosp\RincianBelanjaModal\Index.
     *
     * @return HasMany<RincianBelanjaModal>
     */
    public function rincianBelanjaModal(): HasMany
    {
        return $this->hasMany(RincianBelanjaModal::class);
    }

    /**
     * Relasi ke menu baru "Rincian Belanja Barang Habis Pakai"
     * (2026-09-11, Part 21) - tabel SENDIRI (rincian_belanja_barang_habis_
     * pakai), TIDAK ADA tab utama/kolom "jenis" (menu ini hanya SATU
     * jenis) - lihat
     * App\Livewire\PendataanBosp\RincianBelanjaBarangHabisPakai\Index.
     *
     * @return HasMany<RincianBelanjaBarangHabisPakai>
     */
    public function rincianBelanjaBarangHabisPakai(): HasMany
    {
        return $this->hasMany(RincianBelanjaBarangHabisPakai::class);
    }

    /**
     * Pajak BOSP Reguler (Part 23) - 12 baris tetap per tahun (1 bulan =
     * 1 baris), lihat App\Models\PajakBospReguler untuk detail rumus
     * Jumlah/Saldo/Triwulan yang dihitung dinamis (tidak disimpan).
     */
    public function pajakBospReguler(): HasMany
    {
        return $this->hasMany(PajakBospReguler::class);
    }

    /**
     * Dana BOSP Tahap 1 & 2 (Part 30) - 1 baris per sekolah per tahun,
     * pola sama seperti rekapRkas() (Tab 1 & Tab 2 pada menu ini berasal
     * dari 1 record yang sama, lihat App\Models\DanaBospTahap).
     *
     * @return HasMany<DanaBospTahap>
     */
    public function danaBospTahap(): HasMany
    {
        return $this->hasMany(DanaBospTahap::class);
    }
}
