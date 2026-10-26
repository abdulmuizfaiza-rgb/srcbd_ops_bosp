<?php

namespace App\Imports;

use App\Models\ProfilSekolah;
use App\Models\RincianBelanjaModal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Import data Rincian Belanja Modal dari Excel/CSV untuk satu jenis
 * ('peralatan_mesin'/'aset_tetap_lainnya') + satu tahun + satu triwulan.
 *
 * Kolom yang diharapkan (sesuai hasil export): Kode UPB, NPSN, Nama
 * Sekolah, Nama Barang, Nama Merk Barang, Volume, Satuan, Harga Satuan,
 * Asal Usul, Tanggal, Keterangan. Kolom "Total Harga" diabaikan kalau
 * ada di file - SELALU dihitung ulang sendiri (Volume x Harga Satuan,
 * lihat RincianBelanjaModal::hitungTotalHarga()).
 *
 * Untuk Admin BOSP (bukan Superadmin), data selalu dikaitkan ke
 * sekolahnya sendiri - kolom NPSN pada file diabaikan, supaya satu
 * sekolah tidak bisa menyelipkan data untuk sekolah lain lewat import.
 *
 * Sama seperti RincianPemeliharaanImport: TIDAK memakai WithUpserts/
 * uniqueBy() - sesuai jawaban AskUserQuestion 2026-09-11 ("Boleh
 * duplikat"), kolom Nama Barang boleh berulang, sehingga tidak ada
 * kombinasi kolom yang bisa dijadikan kunci pencocokan baris lama vs
 * baru. Setiap baris pada file yang diimport SELALU masuk sebagai
 * baris/data baru.
 */
/**
 * SkipsEmptyRows (perbaikan bug 2026-10-26, kelanjutan dari perbaikan
 * headingRow() hari sebelumnya - pola IDENTIK dengan yang sudah
 * diterapkan di RincianBelanjaModalBmdImport 2026-10-16): file hasil
 * "Export Excel"/"Unduh Excel" RincianBelanjaModalExport menulis beberapa baris
 * kosong (border tabel) di BAWAH baris data asli, supaya tampilannya
 * rapi saat dicetak (lihat RincianBelanjaModalExport::BARIS_KOSONG_SEBELUM_TANDA_TANGAN).
 * Tanpa SkipsEmptyRows, baris-baris kosong ini ikut divalidasi sebagai
 * baris data sungguhan (field wajib dianggap kosong) - gagal pada baris
 * pertama setelah data asli & membatalkan sisa proses import.
 * SkipsEmptyRows membuat baris yang BENAR-BENAR kosong (tanpa isi sama
 * sekali) dilewati otomatis.
 */
class RincianBelanjaModalImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
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
        $hargaSatuan = $row['harga_satuan'] !== null && $row['harga_satuan'] !== ''
            ? (int) preg_replace('/\D/', '', (string) $row['harga_satuan'])
            : null;

        return new RincianBelanjaModal([
            'profil_sekolah_id' => $profilSekolahId,
            'jenis' => $this->jenis,
            'tahun' => $this->tahun,
            'triwulan' => $this->triwulan,
            'kode_upb' => $row['kode_upb'] !== null && $row['kode_upb'] !== '' ? (string) $row['kode_upb'] : null,
            'nama_barang' => (string) $row['nama_barang'],
            'nama_merk_barang' => $row['nama_merk_barang'] !== null && $row['nama_merk_barang'] !== '' ? (string) $row['nama_merk_barang'] : null,
            'volume' => $volume,
            'satuan' => $row['satuan'] !== null && $row['satuan'] !== '' ? (string) $row['satuan'] : null,
            'harga_satuan' => $hargaSatuan,
            'total_harga' => RincianBelanjaModal::hitungTotalHarga($volume, $hargaSatuan),
            'asal_usul' => $row['asal_usul'] !== null && $row['asal_usul'] !== '' ? (string) $row['asal_usul'] : null,
            'tanggal' => $this->parseTanggal($row['tanggal'] ?? null),
            'keterangan' => $row['keterangan'] !== null && $row['keterangan'] !== '' ? (string) $row['keterangan'] : null,
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

    /**
     * BARU (perbaikan bug 2026-10-26, permintaan user: import file hasil
     * export sendiri selalu gagal "field is required"): tanpa ini,
     * WithHeadingRow membaca baris 1 sebagai header (default Maatwebsite
     * Excel), padahal file yang DIHASILKAN oleh RincianBelanjaModalExport (satu-satunya
     * file yang wajar diupload user ke sini) menaruh judul di baris 1-2,
     * baris kosong di 3-4, dan header tabel SEBENARNYA di baris
     * 5 (lihat RincianBelanjaModalExport::BARIS_HEADER_TABEL). Angka ini HARUS
     * selalu sama dengan BARIS_HEADER_TABEL di kelas Export berpasangan.
     */
    public function headingRow(): int
    {
        return 5;
    }

    public function rules(): array
    {
        return [
            // Regex angka (bukan "string") - kolom NPSN di file Excel sering
            // otomatis terbaca sebagai angka oleh PhpSpreadsheet (bukan
            // teks), sama seperti pola import menu lain.
            'npsn' => $this->sekolahDiperbolehkan === null
                ? ['required', 'regex:/^[0-9]{1,20}$/', Rule::exists('profil_sekolah', 'npsn')]
                : ['nullable'],
            'kode_upb' => ['nullable', 'string', 'max:255'],
            'nama_barang' => ['required', 'string', 'max:255'],
            'nama_merk_barang' => ['nullable', 'string', 'max:255'],
            'volume' => ['nullable'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'harga_satuan' => ['nullable'],
            'asal_usul' => ['nullable', 'string', 'max:255'],
            'tanggal' => ['nullable'],
            'keterangan' => ['nullable', 'string'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'npsn.regex' => 'NPSN harus berupa angka, maksimal 20 digit.',
            'npsn.exists' => 'NPSN tidak ditemukan di menu Profil Sekolah.',
            'nama_barang.required' => 'Nama Barang wajib diisi.',
        ];
    }
}
