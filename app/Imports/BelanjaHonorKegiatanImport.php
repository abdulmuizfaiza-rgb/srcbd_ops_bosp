<?php

namespace App\Imports;

use App\Models\BelanjaHonorKegiatan;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Import data Belanja Honor Kegiatan/Belanja Makan & Minum/Belanja
 * Perjalanan Dinas dari Excel/CSV untuk satu jenis (tab utama) + satu
 * tahun + satu triwulan - pola disalin PERSIS dari LanggananDayaJasaImport
 * (TIDAK memakai WithUpserts/uniqueBy(), sesuai jawaban AskUserQuestion
 * 2026-09-11 "Boleh duplikat" - setiap baris pada file yang diimport
 * SELALU masuk sebagai baris/data baru). Baris yang diimport SELALU
 * masuk dengan jenis sesuai tab utama yang sedang aktif saat import
 * dijalankan (sama seperti pola RincianPemeliharaanImport, Part 17/20).
 *
 * Kolom yang diharapkan (sesuai hasil export): NPSN, Nama Sekolah,
 * Uraian, Volume, Satuan, Tarif Harga, Tanggal. Kolom "Jumlah" diabaikan
 * kalau ada di file - SELALU dihitung ulang sendiri (Volume x Tarif
 * Harga).
 *
 * Untuk Admin BOSP (bukan Superadmin), data selalu dikaitkan ke
 * sekolahnya sendiri - kolom NPSN pada file diabaikan.
 */
/**
 * SkipsEmptyRows (perbaikan bug 2026-10-26, kelanjutan dari perbaikan
 * headingRow() hari sebelumnya - pola IDENTIK dengan yang sudah
 * diterapkan di RincianBelanjaModalBmdImport 2026-10-16): file hasil
 * "Export Excel"/"Unduh Excel" BelanjaHonorKegiatanExport menulis beberapa baris
 * kosong (border tabel) di BAWAH baris data asli, supaya tampilannya
 * rapi saat dicetak (lihat BelanjaHonorKegiatanExport::BARIS_KOSONG_SEBELUM_TANDA_TANGAN).
 * Tanpa SkipsEmptyRows, baris-baris kosong ini ikut divalidasi sebagai
 * baris data sungguhan (field wajib dianggap kosong) - gagal pada baris
 * pertama setelah data asli & membatalkan sisa proses import.
 * SkipsEmptyRows membuat baris yang BENAR-BENAR kosong (tanpa isi sama
 * sekali) dilewati otomatis.
 */
class BelanjaHonorKegiatanImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    use Importable;

    public function __construct(
        protected string $jenis,
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

        return new BelanjaHonorKegiatan([
            'profil_sekolah_id' => $profilSekolahId,
            'jenis' => $this->jenis,
            'tahun' => $this->tahun,
            'triwulan' => $this->triwulan,
            'uraian' => (string) $row['uraian'],
            'volume' => $volume,
            'satuan' => $row['satuan'] !== null && $row['satuan'] !== '' ? (string) $row['satuan'] : null,
            'tarif_harga' => $tarifHarga,
            'jumlah' => BelanjaHonorKegiatan::hitungJumlah($volume, $tarifHarga),
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

    /**
     * BARU (perbaikan bug 2026-10-26, permintaan user: import file hasil
     * export sendiri selalu gagal "field is required"): tanpa ini,
     * WithHeadingRow membaca baris 1 sebagai header (default Maatwebsite
     * Excel), padahal file yang DIHASILKAN oleh BelanjaHonorKegiatanExport (satu-satunya
     * file yang wajar diupload user ke sini) menaruh judul di baris 1-2,
     * baris kosong di 3-4, dan header tabel SEBENARNYA di baris
     * 5 (lihat BelanjaHonorKegiatanExport::BARIS_HEADER_TABEL). Angka ini HARUS
     * selalu sama dengan BARIS_HEADER_TABEL di kelas Export berpasangan.
     */
    public function headingRow(): int
    {
        return 5;
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
