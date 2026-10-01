<?php

namespace App\Models;

use Database\Factories\DataPtkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Data PTK (biodata lengkap Pendidik & Tenaga Kependidikan per sekolah) -
 * Tab 2 pada menu "Data Sekolah" (dulu "Profil Sekolah"), permintaan user
 * 2026-10-01. Lihat catatan lengkap pada migration
 * `create_data_ptk_table` untuk tafsiran field wajib/opsional & format
 * NIK/NUPTK/NIP.
 */
#[Fillable([
    'profil_sekolah_id',
    'nik',
    'nuptk',
    'nip',
    'nama_ptk',
    'tempat_lahir',
    'tanggal_lahir',
    'jabatan',
    'pangkat_golongan',
    'status_kepegawaian',
    'jenis_ptk',
    'tmt_sekolah_induk',
    'pendidikan_terakhir',
    'jurusan_prodi',
    'tahun_lulus_ijazah',
    'status_sertifikasi',
    'bidang_studi_sertifikasi',
    'tahun_lulus_sertifikasi',
    'nomor_sertifikat_sertifikasi',
    'nomor_registrasi_guru',
    'nomor_peserta_sertifikasi',
    'status_dapodik',
    'status_keaktifan',
    'created_by',
])]
class DataPtk extends Model
{
    /** @use HasFactory<DataPtkFactory> */
    use HasFactory;

    protected $table = 'data_ptk';

    /**
     * Tanda isian kosong untuk field rincian sertifikasi saat Status
     * Sertifikasi = Belum (permintaan user eksplisit).
     */
    public const TANDA_KOSONG = '-';

    public const STATUS_SERTIFIKASI_SUDAH = 'Sudah';

    public const STATUS_SERTIFIKASI_BELUM = 'Belum';

    /**
     * Field rincian sertifikasi yang otomatis diisi TANDA_KOSONG saat
     * Status Sertifikasi = Belum - dipakai bersama oleh Livewire
     * component (saat simpan manual) & Import Excel, supaya aturannya
     * tidak ditulis 2 kali di 2 tempat berbeda.
     */
    public const FIELD_RINCIAN_SERTIFIKASI = [
        'bidang_studi_sertifikasi',
        'tahun_lulus_sertifikasi',
        'nomor_sertifikat_sertifikasi',
        'nomor_registrasi_guru',
        'nomor_peserta_sertifikasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tmt_sekolah_induk' => 'date',
        ];
    }

    public static function jabatanOptions(): array
    {
        return [
            'Guru Honorer' => 'Guru Honorer',
            'Guru Ahli Pertama' => 'Guru Ahli Pertama',
            'Guru Ahli Muda' => 'Guru Ahli Muda',
            'Guru Ahli Madya' => 'Guru Ahli Madya',
            'Guru Ahli Utama' => 'Guru Ahli Utama',
        ];
    }

    public static function pangkatGolonganOptions(): array
    {
        return [
            'Penata Muda III/a' => 'Penata Muda III/a',
            'Penata Muda Tk. I III/b' => 'Penata Muda Tk. I III/b',
            'Penata III/c' => 'Penata III/c',
            'Penata Tingkat I III/d' => 'Penata Tingkat I III/d',
            'Pembina IV/a' => 'Pembina IV/a',
            'Pembina Tk. I IV/b' => 'Pembina Tk. I IV/b',
            'Pembina Utama Muda IV/c' => 'Pembina Utama Muda IV/c',
            'Pembina Utama Madya IV/d' => 'Pembina Utama Madya IV/d',
            'Pembina Utama IV/e' => 'Pembina Utama IV/e',
        ];
    }

    public static function statusKepegawaianOptions(): array
    {
        return [
            'PNS' => 'PNS',
            'PPPK' => 'PPPK',
            'PPPK Paruh Waktu' => 'PPPK Paruh Waktu',
            'Honorer Sekolah Negeri' => 'Honorer Sekolah Negeri',
            'Honorer Sekolah Swasta' => 'Honorer Sekolah Swasta',
        ];
    }

    public static function jenisPtkOptions(): array
    {
        return [
            'Kepala Sekolah' => 'Kepala Sekolah',
            'Guru' => 'Guru',
        ];
    }

    public static function pendidikanTerakhirOptions(): array
    {
        return [
            'S1' => 'S1',
            'S2' => 'S2',
            'S3' => 'S3',
        ];
    }

    public static function statusSertifikasiOptions(): array
    {
        return [
            self::STATUS_SERTIFIKASI_SUDAH => 'Sudah',
            self::STATUS_SERTIFIKASI_BELUM => 'Belum',
        ];
    }

    public static function statusDapodikOptions(): array
    {
        return [
            'Sudah Masuk Dapodik' => 'Sudah Masuk Dapodik',
            'Belum Masuk Dapodik' => 'Belum Masuk Dapodik',
        ];
    }

    public static function statusKeaktifanOptions(): array
    {
        return [
            'Aktif' => 'Aktif',
            'Tidak Aktif' => 'Tidak Aktif',
        ];
    }

    /**
     * Terapkan aturan "Status Sertifikasi = Belum -> field rincian
     * sertifikasi otomatis diisi tanda '-'" pada sebuah array data
     * (dipakai bersama oleh Livewire component & Import Excel supaya
     * aturannya satu sumber kebenaran).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function terapkanAturanSertifikasi(array $data): array
    {
        if (($data['status_sertifikasi'] ?? null) === self::STATUS_SERTIFIKASI_BELUM) {
            foreach (self::FIELD_RINCIAN_SERTIFIKASI as $field) {
                $data[$field] = self::TANDA_KOSONG;
            }
        }

        return $data;
    }

    public function profilSekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class);
    }

    /**
     * Pembuat baris (untuk panel detail "+" pada tabel Data PTK -
     * permintaan user 2026-10-01).
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Urutan Jenis PTK untuk daftar pilihan PTK pada Lampiran 2a
     * (permintaan user 2026-10-01, dikonfirmasi via AskUserQuestion:
     * "Kepsek dulu, jabatan fungsional tertinggi dulu"). Angka lebih
     * kecil = tampil lebih dulu.
     *
     * @return array<string, int>
     */
    public static function jenisPtkUrutan(): array
    {
        return [
            'Kepala Sekolah' => 0,
            'Guru' => 1,
        ];
    }

    /**
     * Urutan Jabatan (fungsional guru) tertinggi ke terendah, dikonfirmasi
     * user 2026-10-01 - mengikuti jenjang jabatan fungsional guru baku
     * (Ahli Utama > Ahli Madya > Ahli Muda > Ahli Pertama), Guru Honorer
     * (bukan jabatan fungsional ASN) ditempatkan paling akhir.
     *
     * @return array<string, int>
     */
    public static function jabatanUrutan(): array
    {
        return [
            'Guru Ahli Utama' => 0,
            'Guru Ahli Madya' => 1,
            'Guru Ahli Muda' => 2,
            'Guru Ahli Pertama' => 3,
            'Guru Honorer' => 4,
        ];
    }

    /**
     * Urutan Status Kepegawaian tertinggi ke terendah, dikonfirmasi user
     * 2026-10-01 - mengikuti jenjang kepegawaian ASN baku (PNS > PPPK >
     * PPPK Paruh Waktu), Honorer Sekolah Negeri ditempatkan di atas
     * Honorer Sekolah Swasta.
     *
     * @return array<string, int>
     */
    public static function statusKepegawaianUrutan(): array
    {
        return [
            'PNS' => 0,
            'PPPK' => 1,
            'PPPK Paruh Waktu' => 2,
            'Honorer Sekolah Negeri' => 3,
            'Honorer Sekolah Swasta' => 4,
        ];
    }

    /**
     * Urutan Pangkat/Golongan TERTINGGI ke TERENDAH (kebalikan dari
     * pangkatGolonganOptions() yang disusun terendah ke tertinggi untuk
     * tampilan dropdown) - dipakai untuk pengurutan daftar pilihan PTK
     * pada Lampiran 2a (permintaan user 2026-10-01).
     *
     * @return array<string, int>
     */
    public static function pangkatGolonganUrutan(): array
    {
        return array_flip(array_reverse(array_values(self::pangkatGolonganOptions())));
    }
}
