<?php

namespace App\Models;

use Database\Factories\ProfilSekolahFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use App\Models\Concerns\Syncable;

#[Fillable([
    'npsn',
    'kode_upb',
    'nama_sekolah',
    'status',
    'kecamatan',
    'subrayon',
    'nama_kepala_sekolah',
    'nip_kepala_sekolah',
    'no_whatsapp_kepala_sekolah',
    'status_kepegawaian_kepsek',
    'nama_pengawas',
    'nip_pengawas',
    'nama_kasie_kpml',
    'nip_kasie_kpml',
    'nama_bendahara',
    'nip_bendahara',
    'status_kepegawaian_bendahara',
    'alamat_sekolah',
    'kop_surat',
    'nama_yayasan',
    'logo_sekolah',
])]
class ProfilSekolah extends Model
{
    use Syncable;

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
     * Pilihan status kepegawaian untuk Bendahara. SENGAJA independen dari
     * statusKepegawaianKepsekOptions() di bawah (TIDAK saling mewarisi
     * lewat array_merge lagi sejak 2026-10-15) - supaya penambahan pilihan
     * di salah satu field (Bendahara ATAU Kepala Sekolah) tidak ikut
     * "bocor"/muncul di field yang lain. PTY, GTY, PTT ditambahkan
     * 2026-10-15 (permintaan user, KHUSUS Bendahara).
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
            'PTY' => 'PTY',
            'GTY' => 'GTY',
            'PTT' => 'PTT',
        ];
    }

    /**
     * Pilihan status kepegawaian KHUSUS untuk Kepala Sekolah - daftar
     * independen sendiri (lihat catatan "SENGAJA independen" di
     * statusKepegawaianOptions() di atas), isinya sama persis dengan
     * pilihan Bendahara yang lama (PNS/ASN PPPK/ASN PPPK-PW/Honorer)
     * ditambah GTT & GTY (permintaan user 2026-10-15, KHUSUS field Status
     * Kepegawaian Kepala Sekolah pada menu Profil Sekolah).
     *
     * @return array<string, string>
     */
    public static function statusKepegawaianKepsekOptions(): array
    {
        return [
            'PNS' => 'PNS',
            'ASN PPPK' => 'ASN PPPK',
            'ASN PPPK-PW' => 'ASN PPPK-PW',
            'Honorer' => 'Honorer',
            'GTT' => 'GTT',
            'GTY' => 'GTY',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusOptions()[$this->status] ?? '-';
    }

    /**
     * Kop Surat (gambar kop/kepala surat, diupload manual oleh Admin OPS -
     * permintaan user 2026-09-24, round kesembilan belas) sebagai data URI
     * base64 - dipakai HANYA untuk output yang dirender di server tanpa
     * lewat browser (PDF via dompdf & file Word/.doc), supaya gambarnya
     * ikut terbawa TANPA bergantung pada koneksi HTTP/APP_URL sama sekali.
     *
     * Untuk tampilan di layar (Livewire, dibuka lewat browser dengan sesi
     * login), pakai route `pendataan-ops.surat-tpg.kop-surat` (App\Http\
     * Controllers\KopSuratFileController) SEBAGAI GANTI method ini -
     * mengikuti pola baku yang sudah dipakai TampilanBackgroundController &
     * PendataanOpsFileController (menyajikan file lewat route aplikasi,
     * BUKAN Storage::url(), supaya tidak kena bug lama APP_URL tidak sesuai
     * host+port yang benar-benar dipakai browser).
     */
    public function kopSuratDataUri(): ?string
    {
        if (! $this->kop_surat || ! Storage::disk('public')->exists($this->kop_surat)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($this->kop_surat) ?: 'image/png';
        $isi = Storage::disk('public')->get($this->kop_surat);

        return 'data:'.$mime.';base64,'.base64_encode($isi);
    }

    /**
     * Data URI base64 untuk gambar Logo Sekolah (permintaan user
     * 2026-10-15) - dipakai di tab "Cover SMPN"/"Cover SMP-S" menu
     * Format Surat Rekomendasi & Pembatalan TPG, HANYA untuk PDF
     * (dompdf) & Word (.doc) - sama persis polanya dengan
     * kopSuratDataUri() di atas. Untuk tampilan layar (preview Livewire),
     * lihat App\Http\Controllers\LogoSekolahFileController.
     */
    public function logoSekolahDataUri(): ?string
    {
        if (! $this->logo_sekolah || ! Storage::disk('public')->exists($this->logo_sekolah)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($this->logo_sekolah) ?: 'image/png';
        $isi = Storage::disk('public')->get($this->logo_sekolah);

        return 'data:'.$mime.';base64,'.base64_encode($isi);
    }

    /**
     * Field yang WAJIB dilengkapi oleh Admin OPS/Admin BOSP (di luar
     * NPSN/Nama Sekolah/Status/Kecamatan yang diisi Superadmin) supaya
     * profil sekolah dianggap lengkap/updated - dipakai untuk gate
     * onboarding: Admin OPS/Admin BOSP wajib melengkapi ini dulu sebelum
     * bisa membuka menu lain.
     */
    /**
     * Daftar nama field yang wajib diisi supaya Profil Sekolah dianggap
     * "lengkap" (dipakai isLengkap() di bawah) - DIEKSTRAK 2026-10-23
     * dari isLengkap() (permintaan user: tampilkan rincian field yang
     * belum lengkap per sekolah, klik nama sekolah di menu Data
     * Sekolah-Profil Sekolah) supaya daftar field-nya bisa dipakai
     * ulang untuk menyusun pesan rincian, sama seperti pola
     * DataPtk::FIELD_BELUM_LENGKAP / labelFieldBelumLengkap(). TIDAK
     * mengubah daftar field wajib yang sudah ada sebelumnya.
     *
     * @var array<int, string>
     */
    public const FIELD_WAJIB_LENGKAP = [
        'nama_kepala_sekolah',
        'nip_kepala_sekolah',
        'no_whatsapp_kepala_sekolah',
        'status_kepegawaian_kepsek',
        'nama_pengawas',
        'nip_pengawas',
        'nama_kasie_kpml',
        'nip_kasie_kpml',
        'nama_bendahara',
        'nip_bendahara',
        'status_kepegawaian_bendahara',
        'alamat_sekolah',
    ];

    /**
     * Label tampilan (Indonesia) untuk setiap field pada
     * FIELD_WAJIB_LENGKAP - dipakai App\Livewire\ProfilSekolah\Index
     * untuk menyusun daftar rincian field yang belum diisi per sekolah
     * (modal "profil-sekolah-belum-lengkap-detail"), pola sama persis
     * dgn DataPtk::labelFieldBelumLengkap().
     *
     * @return array<string, string>
     */
    public static function labelFieldWajibLengkap(): array
    {
        return [
            'nama_kepala_sekolah' => 'Nama Kepala Sekolah',
            'nip_kepala_sekolah' => 'NIP Kepala Sekolah',
            'no_whatsapp_kepala_sekolah' => 'No WhatsApp Kepala Sekolah',
            'status_kepegawaian_kepsek' => 'Status Kepegawaian Kepala Sekolah',
            'nama_pengawas' => 'Nama Pengawas',
            'nip_pengawas' => 'NIP Pengawas',
            'nama_kasie_kpml' => 'Nama Kasie KPML',
            'nip_kasie_kpml' => 'NIP Kasie KPML',
            'nama_bendahara' => 'Nama Bendahara',
            'nip_bendahara' => 'NIP Bendahara',
            'status_kepegawaian_bendahara' => 'Status Kepegawaian Bendahara',
            'alamat_sekolah' => 'Alamat Sekolah',
        ];
    }

    public function isLengkap(): bool
    {
        foreach (self::FIELD_WAJIB_LENGKAP as $field) {
            $nilai = $this->{$field};
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
     * Relasi ke tab "BMD" (2026-09-22) - tabel TERPISAH
     * (rincian_belanja_modal_bmd), tab utama ketiga pada menu Rincian
     * Belanja Modal - lihat App\Models\RincianBelanjaModalBmd.
     *
     * @return HasMany<RincianBelanjaModalBmd>
     */
    public function rincianBelanjaModalBmd(): HasMany
    {
        return $this->hasMany(RincianBelanjaModalBmd::class);
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
     * Relasi ke tab "Stock Opname" (2026-09-18) - tabel TERPISAH
     * (stock_opname_barang_persediaan), tab utama kedua di menu Rincian
     * Belanja Barang Habis Pakai - lihat
     * App\Models\StockOpnameBarangPersediaan.
     *
     * @return HasMany<StockOpnameBarangPersediaan>
     */
    public function stockOpnameBarangPersediaan(): HasMany
    {
        return $this->hasMany(StockOpnameBarangPersediaan::class);
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

    /**
     * Laporan Realisasi BOSP / Form BPK (Part 32) - 4 baris tetap per
     * sekolah per tahun (Triwulan 1-4), pola sama seperti
     * pajakBospReguler() (1 baris tetap per periode), lihat
     * App\Models\LaporanRealisasiBosp.
     *
     * @return HasMany<LaporanRealisasiBosp>
     */
    public function laporanRealisasiBosp(): HasMany
    {
        return $this->hasMany(LaporanRealisasiBosp::class);
    }

    /**
     * Formulir BOS K7b & K7c (permintaan user 2026-09-23) - 12 baris
     * tetap per sekolah per tahun (Januari-Desember), 1 baris dipakai
     * bersama oleh kedua tab (K7b & K7c), pola sama seperti
     * pajakBospReguler() (1 baris tetap per periode, kunci = bulan
     * bukan triwulan), lihat App\Models\FormulirBosK7.
     *
     * @return HasMany<FormulirBosK7>
     */
    public function formulirBosK7(): HasMany
    {
        return $this->hasMany(FormulirBosK7::class);
    }

    /**
     * Verval (verifikasi & validasi) Laporan Realisasi BOSP (permintaan
     * user 2026-09-23, round keenam) - sampai dengan 4 baris tetap per
     * sekolah per tahun (Triwulan 1-4), lihat App\Models\VervalRealisasiBosp.
     *
     * @return HasMany<VervalRealisasiBosp>
     */
    public function vervalRealisasiBosp(): HasMany
    {
        return $this->hasMany(VervalRealisasiBosp::class);
    }

    /**
     * Urutan baku daftar sekolah di SELURUH aplikasi (permintaan user
     * 2026-09-27): Status dulu (Negeri sebelum Swasta), lalu Kecamatan
     * (A-Z), lalu Nama Sekolah (A-Z) - dipusatkan di sini (bukan diulang
     * manual di tiap query) supaya urutannya konsisten kalau nanti perlu
     * diubah lagi cukup di 1 tempat.
     */
    public function scopeUrutStandar(Builder $query): Builder
    {
        return $query
            ->orderByRaw("CASE WHEN status = ? THEN 0 ELSE 1 END", [self::STATUS_NEGERI])
            ->orderBy('kecamatan')
            ->orderBy('nama_sekolah');
    }

    /**
     * Data PTK (biodata lengkap PTK) sekolah ini - Tab 2 pada menu "Data
     * Sekolah" (permintaan user 2026-10-01). Satu sekolah bisa punya
     * BANYAK baris PTK - lihat App\Models\DataPtk.
     *
     * @return HasMany<DataPtk>
     */
    public function dataPtk(): HasMany
    {
        return $this->hasMany(DataPtk::class);
    }
}
