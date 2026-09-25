<?php

namespace App\Imports;

use App\Models\Lampiran2c;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Import data Lampiran 2c dari Excel/CSV untuk satu triwulan.
 *
 * Kolom yang diharapkan (sesuai hasil export): NRG, NUPTK, Nama PTK, Tempat
 * Tugas, Kecamatan, Jenis Kepangkatan, Golongan, Masa Kerja,
 * Pangkat/Berkala, TMT, Gaji Pokok Lama, Gaji Pokok Baru, Keterangan.
 *
 * Perhatian: kolom "Pangkat/Berkala" pada file Excel dikenali sistem
 * sebagai "pangkatberkala" (tanda "/" tidak ikut jadi pemisah).
 *
 * Untuk Admin OPS (bukan Superadmin), data selalu dikaitkan ke sekolahnya
 * sendiri - kolom Tempat Tugas pada file diabaikan.
 */
class Lampiran2cImport implements ToModel, WithHeadingRow, WithValidation
{
    use Importable;

    public function __construct(
        protected int $triwulan,
        protected ?int $createdBy,
        protected ?int $sekolahDiperbolehkan,
    ) {}

    protected function resolveProfilSekolahId(array $row): ?int
    {
        if ($this->sekolahDiperbolehkan !== null) {
            return $this->sekolahDiperbolehkan;
        }

        $sekolah = ProfilSekolah::whereRaw('LOWER(nama_sekolah) = ?', [strtolower(trim((string) ($row['tempat_tugas'] ?? '')))])->first();

        return $sekolah?->id;
    }

    protected function parseTanggal(mixed $nilai): ?string
    {
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

    protected function angka(mixed $nilai): int
    {
        return (int) preg_replace('/[^0-9]/', '', (string) $nilai);
    }

    public function model(array $row): Model|array|null
    {
        $profilSekolahId = $this->resolveProfilSekolahId($row);

        if (! $profilSekolahId) {
            return null;
        }

        $tmt = $this->parseTanggal($row['tmt'] ?? null);

        if (! $tmt) {
            return null;
        }

        return new Lampiran2c([
            'profil_sekolah_id' => $profilSekolahId,
            'triwulan' => $this->triwulan,
            'tahun' => now()->year,
            'nrg' => trim((string) ($row['nrg'] ?? '')),
            'nuptk' => trim((string) ($row['nuptk'] ?? '')),
            'nama_ptk' => trim((string) ($row['nama_ptk'] ?? '')),
            'kecamatan' => trim((string) ($row['kecamatan'] ?? '')),
            'jenis_kepangkatan' => trim((string) ($row['jenis_kepangkatan'] ?? '')),
            'golongan' => trim((string) ($row['golongan'] ?? '')),
            'masa_kerja' => trim((string) ($row['masa_kerja'] ?? '')),
            'pangkat_berkala' => trim((string) ($row['pangkatberkala'] ?? '')),
            'tmt' => $tmt,
            'gaji_pokok_lama' => $this->angka($row['gaji_pokok_lama'] ?? 0),
            'gaji_pokok_baru' => $this->angka($row['gaji_pokok_baru'] ?? 0),
            'keterangan' => (string) ($row['keterangan'] ?? ''),
            'created_by' => $this->createdBy,
        ]);
    }

    public function rules(): array
    {
        return [
            'tempat_tugas' => $this->sekolahDiperbolehkan === null
                ? ['required', 'string', Rule::exists('profil_sekolah', 'nama_sekolah')]
                : ['nullable'],
            'nrg' => ['required', 'digits:12'],
            'nuptk' => ['required', 'digits:16'],
            'nama_ptk' => ['required', 'string', 'max:255'],
            'kecamatan' => ['required', Rule::in(array_keys(Lampiran2c::KECAMATAN_OPTIONS))],
            'jenis_kepangkatan' => ['required', Rule::in(array_keys(Lampiran2c::JENIS_KEPANGKATAN_OPTIONS))],
            'golongan' => ['required', Rule::in(array_keys(Lampiran2c::GOLONGAN_OPTIONS))],
            'masa_kerja' => ['required', 'string', 'max:50'],
            'pangkatberkala' => ['required', Rule::in(array_keys(Lampiran2c::PANGKAT_BERKALA_OPTIONS))],
            'tmt' => ['required'],
            'gaji_pokok_lama' => ['required'],
            'gaji_pokok_baru' => ['required'],
            'keterangan' => ['required', 'string'],
        ];
    }

    /**
     * Validasi tambahan: TMT harus bisa dibaca sebagai tanggal.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $baris = $validator->getData();

            foreach ($baris as $indeks => $data) {
                if (! is_array($data)) {
                    continue;
                }

                if (array_key_exists('tmt', $data) && $this->parseTanggal($data['tmt']) === null) {
                    $validator->errors()->add($indeks.'.tmt', 'TMT harus berupa tanggal yang valid.');
                }
            }
        });
    }

    public function customValidationMessages(): array
    {
        return [
            'tempat_tugas.exists' => 'Tempat Tugas tidak ditemukan di menu Profil Sekolah.',
            'kecamatan.in' => 'Kecamatan harus salah satu dari: Caringin, Cicantayan, Cibadak, Nagrak, Cikidang.',
            'jenis_kepangkatan.in' => 'Jenis Kepangkatan harus Pangkat atau KGB.',
            'golongan.in' => 'Golongan harus salah satu pilihan yang tersedia di aplikasi.',
            'pangkatberkala.in' => 'Pangkat/Berkala harus Pangkat atau KGB.',
        ];
    }
}
