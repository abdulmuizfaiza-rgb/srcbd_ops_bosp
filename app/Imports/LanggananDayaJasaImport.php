<?php

namespace App\Imports;

use App\Models\LanggananDayaJasa;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Import data Langganan Daya dan Jasa dari Excel/CSV untuk satu tahun +
 * satu triwulan.
 *
 * Kolom yang diharapkan (sesuai hasil export): NPSN, Nama Sekolah,
 * Uraian Pembayaran, Volume, Satuan, Tarif Harga, Tanggal Bayar. Kolom
 * "Jumlah" diabaikan kalau ada di file - SELALU dihitung ulang sendiri
 * (Volume x Tarif Harga, lihat LanggananDayaJasa::hitungJumlah()), sama
 * seperti kolom rumus lain di aplikasi ini yang tidak pernah diambil
 * mentah dari input.
 *
 * Untuk Admin BOSP (bukan Superadmin), data selalu dikaitkan ke
 * sekolahnya sendiri - kolom NPSN pada file diabaikan, supaya satu
 * sekolah tidak bisa menyelipkan data untuk sekolah lain lewat import.
 *
 * BEDA dengan PenerimaanHonorPtkImport: import di sini TIDAK memakai
 * WithUpserts/uniqueBy() - sesuai jawaban AskUserQuestion 2026-09-10
 * ("Boleh duplikat"), kolom Uraian Pembayaran boleh berulang (mis. 2
 * baris "Listrik" untuk bulan berbeda dalam 1 triwulan yang sama),
 * sehingga tidak ada kombinasi kolom yang bisa dijadikan kunci
 * pencocokan baris lama vs baru. Setiap baris pada file yang diimport
 * SELALU masuk sebagai baris/data baru.
 */
class LanggananDayaJasaImport implements ToModel, WithHeadingRow, WithValidation
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

        return new LanggananDayaJasa([
            'profil_sekolah_id' => $profilSekolahId,
            'tahun' => $this->tahun,
            'triwulan' => $this->triwulan,
            'uraian_pembayaran' => (string) $row['uraian_pembayaran'],
            'volume' => $volume,
            'satuan' => $row['satuan'] !== null && $row['satuan'] !== '' ? (string) $row['satuan'] : null,
            'tarif_harga' => $tarifHarga,
            'jumlah' => LanggananDayaJasa::hitungJumlah($volume, $tarifHarga),
            'tanggal_bayar' => $this->parseTanggal($row['tanggal_bayar'] ?? null),
            'created_by' => $this->createdBy,
        ]);
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

    public function rules(): array
    {
        return [
            // Regex angka (bukan "string") - kolom NPSN di file Excel sering
            // otomatis terbaca sebagai angka oleh PhpSpreadsheet (bukan
            // teks), sama seperti pola ProfilSekolahImport &
            // PenerimaanHonorPtkImport.
            'npsn' => $this->sekolahDiperbolehkan === null
                ? ['required', 'regex:/^[0-9]{1,20}$/', Rule::exists('profil_sekolah', 'npsn')]
                : ['nullable'],
            'uraian_pembayaran' => ['required', 'string', 'max:255'],
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
            'uraian_pembayaran.required' => 'Uraian Pembayaran wajib diisi.',
        ];
    }
}
