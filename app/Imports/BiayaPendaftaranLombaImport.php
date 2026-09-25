<?php

namespace App\Imports;

use App\Models\BiayaPendaftaranLomba;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Import data Biaya Pendaftaran Lomba/Bimtek/Workshop dari Excel/CSV
 * untuk satu tahun + satu triwulan - pola disalin PERSIS dari
 * LanggananDayaJasaImport (TIDAK memakai WithUpserts/uniqueBy(), sesuai
 * jawaban AskUserQuestion 2026-09-11 "Boleh duplikat" - setiap baris
 * pada file yang diimport SELALU masuk sebagai baris/data baru).
 *
 * Kolom yang diharapkan (sesuai hasil export): NPSN, Nama Sekolah,
 * Uraian, Volume, Satuan, Tarif Harga, Tanggal. Kolom "Jumlah" diabaikan
 * kalau ada di file - SELALU dihitung ulang sendiri (Volume x Tarif
 * Harga).
 *
 * Untuk Admin BOSP (bukan Superadmin), data selalu dikaitkan ke
 * sekolahnya sendiri - kolom NPSN pada file diabaikan.
 */
class BiayaPendaftaranLombaImport implements ToModel, WithHeadingRow, WithValidation
{
    use Importable;

    public function __construct(
        protected int $tahun,
        protected int $triwulan,
        protected ?int $createdBy,
        protected ?int $sekolahDiperbolehkan,
    ) {}

    public function model(array $row): Model|array|null
    {
        $profilSekolahId = $this->sekolahDiperbolehkan;

        if ($profilSekolahId === null) {
            $sekolah = ProfilSekolah::where('npsn', trim((string) ($row['npsn'] ?? '')))->first();
            $profilSekolahId = $sekolah?->id;
        }

        if (! $profilSekolahId) {
            return null;
        }

        $volume = $row['volume'] !== null && $row['volume'] !== ''
            ? (int) preg_replace('/\D/', '', (string) $row['volume'])
            : null;
        $tarifHarga = $row['tarif_harga'] !== null && $row['tarif_harga'] !== ''
            ? (int) preg_replace('/\D/', '', (string) $row['tarif_harga'])
            : null;

        return new BiayaPendaftaranLomba([
            'profil_sekolah_id' => $profilSekolahId,
            'tahun' => $this->tahun,
            'triwulan' => $this->triwulan,
            'uraian' => (string) $row['uraian'],
            'volume' => $volume,
            'satuan' => $row['satuan'] !== null && $row['satuan'] !== '' ? (string) $row['satuan'] : null,
            'tarif_harga' => $tarifHarga,
            'jumlah' => BiayaPendaftaranLomba::hitungJumlah($volume, $tarifHarga),
            'tanggal' => $this->parseTanggal($row['tanggal'] ?? null),
            'created_by' => $this->createdBy,
        ]);
    }

    private function parseTanggal(mixed $nilai): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        if (is_numeric($nilai)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($nilai)->format('Y-m-d');
        }

        try {
            return \Carbon\Carbon::parse((string) $nilai)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'npsn' => $this->sekolahDiperbolehkan === null
                ? ['required', 'regex:/^[0-9]{1,20}$/', Rule::exists('profil_sekolah', 'npsn')]
                : ['nullable'],
            'uraian' => ['required', 'string', 'max:255'],
            'volume' => ['nullable'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'tarif_harga' => ['nullable'],
            'tanggal' => ['nullable'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'npsn.regex' => 'NPSN harus berupa angka, maksimal 20 digit.',
            'npsn.exists' => 'NPSN tidak ditemukan di menu Profil Sekolah.',
            'uraian.required' => 'Uraian wajib diisi.',
        ];
    }
}
