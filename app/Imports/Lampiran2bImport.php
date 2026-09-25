<?php

namespace App\Imports;

use App\Models\Lampiran2a;
use App\Models\Lampiran2b;
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
 * Import data Lampiran 2b dari Excel/CSV untuk satu triwulan.
 *
 * Kolom yang diharapkan (sesuai hasil export): NRG, NUPTK, Nama Sekolah,
 * Nama PTK, Keterangan, TMT.
 *
 * NRG & NUPTK pada file SELALU diabaikan dan dihitung ulang dari data
 * Lampiran 2a (dicocokkan lewat Nama PTK, sekolah, & triwulan yang sama) -
 * sama seperti pada form tambah/edit, supaya selalu konsisten. Kalau Nama
 * PTK belum ada di Lampiran 2a triwulan ini untuk sekolah tersebut, baris
 * itu dianggap tidak valid.
 *
 * Untuk Admin OPS (bukan Superadmin), data selalu dikaitkan ke sekolahnya
 * sendiri - kolom Nama Sekolah pada file diabaikan.
 */
class Lampiran2bImport implements ToModel, WithHeadingRow, WithValidation
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

        $sekolah = ProfilSekolah::whereRaw('LOWER(nama_sekolah) = ?', [strtolower(trim((string) ($row['nama_sekolah'] ?? '')))])->first();

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

    public function model(array $row): Model|array|null
    {
        $profilSekolahId = $this->resolveProfilSekolahId($row);

        if (! $profilSekolahId) {
            return null;
        }

        $sumber = Lampiran2a::query()
            ->where('profil_sekolah_id', $profilSekolahId)
            ->where('triwulan', $this->triwulan)
            ->where('nama_ptk', trim((string) ($row['nama_ptk'] ?? '')))
            ->first();

        if (! $sumber) {
            return null;
        }

        $tmt = $this->parseTanggal($row['tmt'] ?? null);

        if (! $tmt) {
            return null;
        }

        return new Lampiran2b([
            'profil_sekolah_id' => $profilSekolahId,
            'triwulan' => $this->triwulan,
            'tahun' => now()->year,
            'nrg' => $sumber->nrg,
            'nuptk' => $sumber->nuptk,
            'nama_ptk' => $sumber->nama_ptk,
            'keterangan' => (string) ($row['keterangan'] ?? ''),
            'tmt' => $tmt,
            'created_by' => $this->createdBy,
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_sekolah' => $this->sekolahDiperbolehkan === null
                ? ['required', 'string', Rule::exists('profil_sekolah', 'nama_sekolah')]
                : ['nullable'],
            'nama_ptk' => ['required', 'string', 'max:255'],
            'keterangan' => ['required', 'string'],
            'tmt' => ['required'],
        ];
    }

    /**
     * Validasi tambahan yang butuh gabungan beberapa kolom sekaligus: Nama
     * PTK harus benar-benar ada di Lampiran 2a (sekolah & triwulan yang
     * sama), dan TMT harus bisa dibaca sebagai tanggal.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $baris = $validator->getData();

            foreach ($baris as $indeks => $data) {
                if (! is_array($data)) {
                    continue;
                }

                $namaPtk = trim((string) ($data['nama_ptk'] ?? ''));

                if ($namaPtk !== '') {
                    $profilSekolahId = $this->resolveProfilSekolahId($data);

                    if ($profilSekolahId && ! Lampiran2a::query()
                        ->where('profil_sekolah_id', $profilSekolahId)
                        ->where('triwulan', $this->triwulan)
                        ->where('nama_ptk', $namaPtk)
                        ->exists()) {
                        $validator->errors()->add(
                            $indeks.'.nama_ptk',
                            'Nama PTK "'.$namaPtk.'" tidak ditemukan di Lampiran 2a triwulan ini untuk sekolah tersebut.'
                        );
                    }
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
            'nama_sekolah.exists' => 'Nama Sekolah tidak ditemukan di menu Profil Sekolah.',
        ];
    }
}
