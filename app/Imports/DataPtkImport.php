<?php

namespace App\Imports;

use App\Models\DataPtk;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Import Data PTK dari Excel/CSV (menu Data Sekolah > tab Data PTK).
 *
 * Kolom yang diharapkan (sesuai hasil "Export Excel" - lihat
 * DataPtkExport): NIK, NUPTK, NIP, Nama PTK, Tempat Lahir, Tanggal
 * Lahir, Jabatan, Pangkat / Golongan, Status Kepegawaian, Jenis PTK,
 * TMT Di Sekolah Induk, Pendidikan Terakhir, Jurusan / Prodi Sesuai
 * Ijazah Terakhir, Tahun Lulus Ijazah, Status Sertifikasi, Bidang Studi
 * Sertifikasi, Tahun Lulus Sertifikasi, Nomor Sertifikat Sertifikasi,
 * Nomor Registrasi Guru, Nomor Peserta Sertifikasi, Nama Sekolah,
 * Status Dapodik, Status Keaktifan.
 *
 * NIK dipakai sebagai KUNCI: baris dengan NIK yang SUDAH ADA di database
 * akan MENIMPA/memperbarui data lama (bukan menambah duplikat) - sesuai
 * jawaban AskUserQuestion 2026-10-01 ("Timpa/update data lama"). Baris
 * dengan NIK baru akan menambah data PTK baru. Pola "simpan manual lalu
 * return null" mengikuti precedent StockOpnameBarangPersediaanImport
 * (supaya bisa dihitung terpisah jumlah ditambah vs diperbarui lewat
 * $jumlahDibuat/$jumlahDiperbarui/$jumlahDilewati).
 *
 * Untuk Admin OPS (bukan Superadmin), data selalu dikaitkan ke
 * sekolahnya sendiri - kolom Nama Sekolah pada file diabaikan, supaya
 * satu sekolah tidak bisa menyelipkan data untuk sekolah lain lewat
 * import (pola sama seperti Lampiran2aImport).
 *
 * Aturan "Status Sertifikasi = Belum -> field rincian sertifikasi
 * otomatis '-'" diterapkan ulang di sini lewat
 * DataPtk::terapkanAturanSertifikasi() - sumber kebenaran tunggal yang
 * sama dipakai oleh Livewire component saat simpan manual.
 */
class DataPtkImport implements ToModel, WithHeadingRow, WithValidation
{
    use Importable;

    public int $jumlahDibuat = 0;

    public int $jumlahDiperbarui = 0;

    public int $jumlahDilewati = 0;

    public function __construct(
        protected ?int $createdBy,
        protected ?int $sekolahDiperbolehkan,
    ) {}

    protected function resolveProfilSekolahId(array $row): ?int
    {
        if ($this->sekolahDiperbolehkan !== null) {
            return $this->sekolahDiperbolehkan;
        }

        $sekolah = ProfilSekolah::whereRaw('LOWER(nama_sekolah) = ?', [strtolower(trim((string) ($row['nama_sekolah'] ?? '')))])->first();

        return $sekolah?->id;
    }

    protected function parseTanggal(mixed $nilai): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        if ($nilai instanceof \DateTimeInterface) {
            return $nilai->format('Y-m-d');
        }

        if (is_numeric($nilai)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $nilai)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $nilai)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function kosongkanJikaKosong(mixed $nilai): ?string
    {
        return $nilai !== null && trim((string) $nilai) !== '' ? trim((string) $nilai) : null;
    }

    public function model(array $row): Model|array|null
    {
        $profilSekolahId = $this->resolveProfilSekolahId($row);

        if (! $profilSekolahId) {
            $this->jumlahDilewati++;

            return null;
        }

        $data = [
            'profil_sekolah_id' => $profilSekolahId,
            'nik' => (string) ($row['nik'] ?? ''),
            'nuptk' => $this->kosongkanJikaKosong($row['nuptk'] ?? null),
            'nip' => $this->kosongkanJikaKosong($row['nip'] ?? null),
            'nama_ptk' => (string) ($row['nama_ptk'] ?? ''),
            'tempat_lahir' => $this->kosongkanJikaKosong($row['tempat_lahir'] ?? null),
            'tanggal_lahir' => $this->parseTanggal($row['tanggal_lahir'] ?? null),
            'jabatan' => (string) ($row['jabatan'] ?? ''),
            'pangkat_golongan' => $this->kosongkanJikaKosong($row['pangkat_golongan'] ?? null),
            'status_kepegawaian' => (string) ($row['status_kepegawaian'] ?? ''),
            'jenis_ptk' => (string) ($row['jenis_ptk'] ?? ''),
            'tmt_sekolah_induk' => $this->parseTanggal($row['tmt_di_sekolah_induk'] ?? null),
            'pendidikan_terakhir' => (string) ($row['pendidikan_terakhir'] ?? ''),
            'jurusan_prodi' => $this->kosongkanJikaKosong($row['jurusan_prodi_sesuai_ijazah_terakhir'] ?? null),
            'tahun_lulus_ijazah' => $this->kosongkanJikaKosong($row['tahun_lulus_ijazah'] ?? null),
            'status_sertifikasi' => (string) ($row['status_sertifikasi'] ?? ''),
            'bidang_studi_sertifikasi' => $this->kosongkanJikaKosong($row['bidang_studi_sertifikasi'] ?? null),
            'tahun_lulus_sertifikasi' => $this->kosongkanJikaKosong($row['tahun_lulus_sertifikasi'] ?? null),
            'nomor_sertifikat_sertifikasi' => $this->kosongkanJikaKosong($row['nomor_sertifikat_sertifikasi'] ?? null),
            'nomor_registrasi_guru' => $this->kosongkanJikaKosong($row['nomor_registrasi_guru'] ?? null),
            'nomor_peserta_sertifikasi' => $this->kosongkanJikaKosong($row['nomor_peserta_sertifikasi'] ?? null),
            'status_dapodik' => (string) ($row['status_dapodik'] ?? ''),
            'status_keaktifan' => (string) ($row['status_keaktifan'] ?? ''),
        ];

        $data = DataPtk::terapkanAturanSertifikasi($data);

        $existing = DataPtk::where('nik', $data['nik'])->first();

        if ($existing) {
            $existing->update($data);
            $this->jumlahDiperbarui++;
        } else {
            $data['created_by'] = $this->createdBy;
            DataPtk::create($data);
            $this->jumlahDibuat++;
        }

        // Sudah disimpan manual di atas (supaya bisa dihitung terpisah
        // ditambah vs diperbarui) - return null supaya Maatwebsite TIDAK
        // mencoba create() lagi.
        return null;
    }

    public function rules(): array
    {
        return [
            'nik' => ['required', 'regex:/^[0-9]{16}$/'],
            'nuptk' => ['nullable', 'regex:/^[0-9]{16}$/'],
            'nip' => ['nullable', 'regex:/^[0-9]{18}$/'],
            'nama_ptk' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', Rule::in(array_keys(DataPtk::jabatanOptions()))],
            'pangkat_golongan' => ['nullable', Rule::in(array_keys(DataPtk::pangkatGolonganOptions()))],
            'status_kepegawaian' => ['required', Rule::in(array_keys(DataPtk::statusKepegawaianOptions()))],
            'jenis_ptk' => ['required', Rule::in(array_keys(DataPtk::jenisPtkOptions()))],
            'pendidikan_terakhir' => ['required', Rule::in(array_keys(DataPtk::pendidikanTerakhirOptions()))],
            'tahun_lulus_ijazah' => ['nullable', 'regex:/^[0-9]{4}$/'],
            'status_sertifikasi' => ['required', Rule::in(array_keys(DataPtk::statusSertifikasiOptions()))],
            'tahun_lulus_sertifikasi' => ['nullable', 'regex:/^([0-9]{4}|-)$/'],
            'nama_sekolah' => $this->sekolahDiperbolehkan === null
                ? ['required', 'string', Rule::exists('profil_sekolah', 'nama_sekolah')]
                : ['nullable'],
            'status_dapodik' => ['required', Rule::in(array_keys(DataPtk::statusDapodikOptions()))],
            'status_keaktifan' => ['required', Rule::in(array_keys(DataPtk::statusKeaktifanOptions()))],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nik.required' => 'NIK wajib diisi.',
            'nik.regex' => 'NIK harus berupa 16 digit angka.',
            'nuptk.regex' => 'NUPTK harus berupa 16 digit angka.',
            'nip.regex' => 'NIP harus berupa 18 digit angka.',
            'tahun_lulus_ijazah.regex' => 'Tahun Lulus Ijazah harus berupa 4 digit angka tahun.',
            'tahun_lulus_sertifikasi.regex' => 'Tahun Lulus Sertifikasi harus berupa 4 digit angka tahun.',
            'nama_sekolah.exists' => 'Nama Sekolah tidak ditemukan di menu Data Sekolah.',
            'jabatan.in' => 'Jabatan harus dipilih dari daftar pilihan yang tersedia.',
            'pangkat_golongan.in' => 'Pangkat / Golongan harus dipilih dari daftar pilihan yang tersedia.',
            'status_kepegawaian.in' => 'Status Kepegawaian harus dipilih dari daftar pilihan yang tersedia.',
            'jenis_ptk.in' => 'Jenis PTK harus dipilih dari daftar pilihan yang tersedia.',
            'pendidikan_terakhir.in' => 'Pendidikan Terakhir harus dipilih dari daftar pilihan yang tersedia.',
            'status_sertifikasi.in' => 'Status Sertifikasi harus dipilih dari daftar pilihan yang tersedia.',
            'status_dapodik.in' => 'Status Dapodik harus dipilih dari daftar pilihan yang tersedia.',
            'status_keaktifan.in' => 'Status Keaktifan harus dipilih dari daftar pilihan yang tersedia.',
        ];
    }
}
