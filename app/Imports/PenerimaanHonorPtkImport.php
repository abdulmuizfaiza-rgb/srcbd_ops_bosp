<?php

namespace App\Imports;

use App\Models\PenerimaanHonorPtk;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpsertColumns;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Import data Penerimaan Honor PTK dari Excel/CSV untuk satu tahun +
 * satu triwulan.
 *
 * Kolom yang diharapkan (sesuai hasil export): NPSN, Nama Sekolah,
 * NUPTK, Nama Penerima, Volume, Satuan, Tarif Harga, Tanggal Bayar.
 * Kolom "Jumlah Honor Yang Diterima" diabaikan kalau ada di file -
 * SELALU dihitung ulang sendiri (Volume x Tarif Harga, lihat
 * PenerimaanHonorPtk::hitungJumlahHonor()), sama seperti kolom rumus
 * lain di aplikasi ini yang tidak pernah diambil mentah dari input.
 *
 * Untuk Admin BOSP (bukan Superadmin), data selalu dikaitkan ke
 * sekolahnya sendiri - kolom NPSN pada file diabaikan, supaya satu
 * sekolah tidak bisa menyelipkan data untuk sekolah lain lewat import.
 *
 * Import ulang (WithUpserts, kunci: profil_sekolah_id+tahun+triwulan+
 * nuptk - sama seperti unique constraint tabel) akan MENIMPA/mengupdate
 * baris yang sudah ada, bukan menambah duplikat - sesuai jawaban
 * AskUserQuestion 2026-09-10 ("Update baris yang sudah ada"). Baris
 * tanpa NUPTK (kolom kosong) TIDAK ikut tertimpa lewat kunci ini (NULL
 * selalu dianggap beda) - selalu masuk sebagai baris baru, karena tidak
 * ada kunci pencocokan yang bisa diandalkan tanpa NUPTK.
 */
class PenerimaanHonorPtkImport implements ToModel, WithHeadingRow, WithUpsertColumns, WithUpserts, WithValidation
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

        $model = new PenerimaanHonorPtk([
            'profil_sekolah_id' => $profilSekolahId,
            'tahun' => $this->tahun,
            'triwulan' => $this->triwulan,
            'nuptk' => $row['nuptk'] !== null && $row['nuptk'] !== '' ? (string) $row['nuptk'] : null,
            'nama_penerima' => (string) $row['nama_penerima'],
            'volume' => $volume,
            'satuan' => $row['satuan'] !== null && $row['satuan'] !== '' ? (string) $row['satuan'] : null,
            'tarif_harga' => $tarifHarga,
            'jumlah_honor' => PenerimaanHonorPtk::hitungJumlahHonor($volume, $tarifHarga),
            'tanggal_bayar' => $this->parseTanggal($row['tanggal_bayar'] ?? null),
            'created_by' => $this->createdBy,
        ]);

        $model->created_at = now();
        $model->updated_at = now();

        return $model;
    }

    private function parseTanggal(mixed $nilai): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        if (is_numeric($nilai)) {
            // Tanggal Excel (serial number) - dikonversi ke tanggal biasa.
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($nilai)->format('Y-m-d');
        }

        try {
            return \Carbon\Carbon::parse((string) $nilai)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function uniqueBy(): array
    {
        return ['profil_sekolah_id', 'tahun', 'triwulan', 'nuptk'];
    }

    public function upsertColumns(): array
    {
        return ['nama_penerima', 'volume', 'satuan', 'tarif_harga', 'jumlah_honor', 'tanggal_bayar', 'updated_at'];
    }

    public function rules(): array
    {
        return [
            // Regex angka (bukan "string") - kolom NPSN di file Excel sering
            // otomatis terbaca sebagai angka oleh PhpSpreadsheet (bukan
            // teks), sama seperti pola ProfilSekolahImport.
            'npsn' => $this->sekolahDiperbolehkan === null
                ? ['required', 'regex:/^[0-9]{1,20}$/', Rule::exists('profil_sekolah', 'npsn')]
                : ['nullable'],
            'nuptk' => ['nullable', 'regex:/^[0-9]{1,16}$/'],
            'nama_penerima' => ['required', 'string', 'max:255'],
            'volume' => ['nullable'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'tarif_harga' => ['nullable'],
            'tanggal_bayar' => ['nullable'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'npsn.regex' => 'NPSN harus berupa angka, maksimal 20 digit.',
            'npsn.exists' => 'NPSN tidak ditemukan di menu Profil Sekolah.',
            'nuptk.regex' => 'NUPTK harus berupa angka, maksimal 16 digit.',
        ];
    }
}
