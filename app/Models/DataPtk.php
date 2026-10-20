<?php

namespace App\Models;

use Database\Factories\DataPtkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\Syncable;

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
    'npwp',
    'status_sertifikasi',
    'bidang_studi_sertifikasi',
    'tahun_lulus_sertifikasi',
    'nomor_sertifikat_sertifikasi',
    'nomor_registrasi_guru',
    'nomor_peserta_sertifikasi',
    'status_dapodik',
    'status_keaktifan',
    'keterangan',
    'created_by',
])]
class DataPtk extends Model
{
    use Syncable;

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

    /**
     * Field yang dipakai untuk menentukan "Data PTK belum lengkap"
     * (permintaan user 2026-10-18, dikonfirmasi lewat AskUserQuestion) -
     * HANYA field yang memang boleh kosong & TIDAK punya aturan bisnis
     * "sengaja ditandai '-'" (berbeda dari pangkat_golongan/nip yang
     * bisa '-' krn Jabatan tertentu, atau field rincian sertifikasi di
     * atas yang bisa '-' krn Status Sertifikasi = Belum) - sebuah baris
     * dianggap "belum lengkap" kalau salah satu dari field ini masih
     * NULL/kosong (bukan '-'), lihat ProfilSekolah\Index::
     * daftarBelumLengkapPtk().
     */
    public const FIELD_BELUM_LENGKAP = [
        'nuptk',
        'tempat_lahir',
        'tanggal_lahir',
        'tmt_sekolah_induk',
        'jurusan_prodi',
        'tahun_lulus_ijazah',
        'npwp',
    ];

    /**
     * Label tampilan (Indonesia) untuk setiap field pada
     * FIELD_BELUM_LENGKAP - dipakai bersama oleh ProfilSekolah\Index
     * (daftar "belum lengkap" per sekolah, Superadmin) & Dashboard\Index
     * (widget sekolah sendiri, Admin OPS) supaya detail field yang
     * belum diisi bisa ditampilkan dengan nama yang manusiawi, bukan
     * nama kolom database mentah (permintaan user 2026-10-20 poin 1).
     *
     * @return array<string, string>
     */
    public static function labelFieldBelumLengkap(): array
    {
        return [
            'nuptk' => 'NUPTK',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'tmt_sekolah_induk' => 'TMT Di Sekolah Induk',
            'jurusan_prodi' => 'Jurusan / Prodi',
            'tahun_lulus_ijazah' => 'Tahun Lulus Ijazah',
            'npwp' => 'NPWP',
        ];
    }

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tmt_sekolah_induk' => 'date',
        ];
    }

    /**
     * GTY, GTT, PTT ditambahkan 2026-10-15 (permintaan user) - dipakai
     * juga sebagai pemicu auto-isi "-" pada Pangkat/Golongan, bersama
     * "Guru Honorer" (lihat jabatanTanpaPangkatGolongan() &
     * terapkanAturanPangkatGolongan() di bawah).
     */
    public static function jabatanOptions(): array
    {
        return [
            'Guru Honorer' => 'Guru Honorer',
            'Guru Ahli Pertama' => 'Guru Ahli Pertama',
            'Guru Ahli Muda' => 'Guru Ahli Muda',
            'Guru Ahli Madya' => 'Guru Ahli Madya',
            'Guru Ahli Utama' => 'Guru Ahli Utama',
            'GTY' => 'GTY',
            'GTT' => 'GTT',
            'PTT' => 'PTT',
        ];
    }

    /**
     * Daftar Jabatan yang TIDAK memiliki jenjang Pangkat/Golongan (PTK
     * non-ASN) - dipakai sebagai pemicu aturan "Pangkat/Golongan
     * otomatis diisi tanda '-'" (permintaan user 2026-10-15, lihat
     * terapkanAturanPangkatGolongan() di bawah). Jabatan "Guru Ahli
     * ..." (fungsional ASN/PPPK) TIDAK termasuk di sini karena jabatan
     * itu MEMANG punya jenjang Pangkat/Golongan.
     *
     * @return array<int, string>
     */
    public static function jabatanTanpaPangkatGolongan(): array
    {
        return [
            'Guru Honorer',
            'GTY',
            'GTT',
            'PTT',
        ];
    }

    /**
     * "-" ditambahkan 2026-10-15 (permintaan user) sebagai pilihan
     * untuk PTK yang Jabatannya tidak memiliki jenjang Pangkat/Golongan
     * (lihat jabatanTanpaPangkatGolongan() & terapkanAturanPangkatGolongan()).
     *
     * "Guru Ahli Pertama IX" ditambahkan 2026-10-16 (permintaan user) -
     * golongan skema baru (PPPK/jabatan fungsional pakai angka romawi,
     * BUKAN gol. PNS lama III/a-IV/e) - dikonfirmasi AskUserQuestion
     * posisinya PALING RENDAH, di atas "-" & di bawah "Penata Muda
     * III/a" (lihat pangkatGolonganUrutan() - urutan di sini otomatis
     * menentukan urutan tertinggi-terendah utk Lampiran 2a).
     *
     * "Guru Ahli Pertama" (tanpa angka romawi) ditambahkan 2026-10-18
     * (permintaan user) sebagai pilihan TERPISAH dari "Guru Ahli
     * Pertama IX" - dikonfirmasi AskUserQuestion posisinya tepat di
     * atas "Guru Ahli Pertama IX" (lebih tinggi dari IX, lebih rendah
     * dari "Penata Muda III/a"). Sebelumnya field ini sempat diisi
     * OTOMATIS ke "Guru Ahli Pertama IX" saat Status Kepegawaian =
     * "PPPK Paruh Waktu", namun aturan auto-isi itu DIHAPUS (permintaan
     * user) - kedua pilihan "Guru Ahli Pertama ..." ini sekarang
     * murni pilihan manual, sama seperti pilihan lain.
     */
    public static function pangkatGolonganOptions(): array
    {
        return [
            '-' => '-',
            'Guru Ahli Pertama IX' => 'Guru Ahli Pertama IX',
            'Guru Ahli Pertama' => 'Guru Ahli Pertama',
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

    /**
     * Pilihan status kepegawaian untuk Data PTK. PTY, GTY, GTT ditambahkan
     * 2026-10-15 (permintaan user) - PTY/GTY = Pegawai/Guru Tetap Yayasan,
     * GTT = Guru Tidak Tetap, dipakai untuk PTK sekolah swasta yang
     * statusnya bukan PNS/PPPK/Honorer.
     */
    public static function statusKepegawaianOptions(): array
    {
        return [
            'PNS' => 'PNS',
            'PPPK' => 'PPPK',
            'PPPK Paruh Waktu' => 'PPPK Paruh Waktu',
            'Honorer Sekolah Negeri' => 'Honorer Sekolah Negeri',
            'Honorer Sekolah Swasta' => 'Honorer Sekolah Swasta',
            'PTY' => 'PTY',
            'GTY' => 'GTY',
            'GTT' => 'GTT',
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

    /**
     * Terapkan aturan "Jabatan = Guru Honorer/GTY/GTT/PTT -> Pangkat/
     * Golongan DAN NIP otomatis diisi tanda '-'" pada sebuah array data
     * (dipakai bersama oleh Livewire component & Import Excel supaya
     * aturannya satu sumber kebenaran - permintaan user 2026-10-15).
     * Daftar Jabatan pemicunya lihat jabatanTanpaPangkatGolongan().
     *
     * NIP ditambahkan ke aturan ini 2026-10-16 (permintaan user) - PTK
     * non-ASN (Guru Honorer/GTY/GTT/PTT) TIDAK memiliki NIP, sama
     * seperti tidak memiliki jenjang Pangkat/Golongan. Sebaliknya,
     * utk Jabatan "Guru Ahli Pertama/Muda/Madya/Utama" (fungsional
     * ASN/PPPK), NIP WAJIB diisi manual (lihat validasi 'nip' di
     * app/Livewire/ProfilSekolah/Index.php & DataPtkImport, yang
     * SEKARANG menerima nilai "-" sbg pengecualian - pola sama persis
     * dengan tahun_lulus_sertifikasi).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function terapkanAturanPangkatGolongan(array $data): array
    {
        if (in_array($data['jabatan'] ?? null, self::jabatanTanpaPangkatGolongan(), true)) {
            $data['pangkat_golongan'] = self::TANDA_KOSONG;
            $data['nip'] = self::TANDA_KOSONG;
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
