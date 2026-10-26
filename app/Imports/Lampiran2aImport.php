<?php

namespace App\Imports;

use App\Models\Lampiran2a;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Import data Lampiran 2a dari Excel/CSV untuk satu triwulan.
 *
 * Kolom yang diharapkan (sesuai hasil export): Nama Sekolah, NRG, NUPTK,
 * Nama PTK, Status Kepegawaian, Gaji Pokok Bulan Januari, NPWP.
 *
 * Untuk Admin OPS (bukan Superadmin), data selalu dikaitkan ke
 * sekolahnya sendiri - kolom Nama Sekolah pada file diabaikan, supaya
 * satu sekolah tidak bisa menyelipkan data untuk sekolah lain lewat
 * import. Jika ada baris yang tidak valid, seluruh import dibatalkan
 * dan daftar error ditampilkan supaya file bisa diperbaiki dan
 * diunggah ulang.
 */
/**
 * SkipsEmptyRows (perbaikan bug 2026-10-26, kelanjutan dari perbaikan
 * headingRow() hari sebelumnya - pola IDENTIK dengan yang sudah
 * diterapkan di RincianBelanjaModalBmdImport 2026-10-16): file hasil
 * "Export Excel"/"Unduh Excel" Lampiran2aExport menulis beberapa baris
 * kosong (border tabel) di BAWAH baris data asli, supaya tampilannya
 * rapi saat dicetak (lihat Lampiran2aExport::BARIS_KOSONG_SEBELUM_TANDA_TANGAN).
 * Tanpa SkipsEmptyRows, baris-baris kosong ini ikut divalidasi sebagai
 * baris data sungguhan (field wajib dianggap kosong) - gagal pada baris
 * pertama setelah data asli & membatalkan sisa proses import.
 * SkipsEmptyRows membuat baris yang BENAR-BENAR kosong (tanpa isi sama
 * sekali) dilewati otomatis.
 */
class Lampiran2aImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    use Importable;

    public function __construct(
        protected int $triwulan,
        protected ?int $createdBy,
        protected ?int $sekolahDiperbolehkan,
    ) {}

    public function model(array $row): Model|array|null
    {
        $profilSekolahId = $this->sekolahDiperbolehkan;

        if ($profilSekolahId === null) {
            $sekolah = ProfilSekolah::whereRaw('LOWER(nama_sekolah) = ?', [strtolower(trim((string) ($row['nama_sekolah'] ?? '')))])->first();
            $profilSekolahId = $sekolah?->id;
        }

        if (! $profilSekolahId) {
            return null;
        }

        return new Lampiran2a([
            'profil_sekolah_id' => $profilSekolahId,
            'triwulan' => $this->triwulan,
            'tahun' => now()->year,
            'nrg' => (string) $row['nrg'],
            'nuptk' => (string) $row['nuptk'],
            'nama_ptk' => (string) $row['nama_ptk'],
            'status_kepegawaian' => (string) $row['status_kepegawaian'],
            'gaji_pokok_januari' => (int) preg_replace('/\D/', '', (string) $row['gaji_pokok_bulan_januari']),
            'npwp' => (string) $row['npwp'],
            'created_by' => $this->createdBy,
        ]);
    }

    /**
     * BARU (perbaikan bug 2026-10-26, permintaan user: import file hasil
     * export sendiri selalu gagal "field is required"): tanpa ini,
     * WithHeadingRow membaca baris 1 sebagai header (default Maatwebsite
     * Excel), padahal file yang DIHASILKAN oleh Lampiran2aExport (satu-satunya
     * file yang wajar diupload user ke sini) menaruh judul di baris 1-2,
     * baris kosong di 3-4, dan header tabel SEBENARNYA di baris
     * 5 (lihat Lampiran2aExport::BARIS_HEADER_TABEL). Angka ini HARUS
     * selalu sama dengan BARIS_HEADER_TABEL di kelas Export berpasangan.
     */
    public function headingRow(): int
    {
        return 5;
    }

    public function rules(): array
    {
        return [
            'nama_sekolah' => $this->sekolahDiperbolehkan === null
                ? ['required', 'string', Rule::exists('profil_sekolah', 'nama_sekolah')]
                : ['nullable'],
            'nrg' => ['required', 'regex:/^[0-9]{12}$/'],
            'nuptk' => ['required', 'regex:/^[0-9]{16}$/'],
            'nama_ptk' => ['required', 'string', 'max:255'],
            'status_kepegawaian' => ['required', Rule::in(array_keys(Lampiran2a::STATUS_KEPEGAWAIAN_OPTIONS))],
            'gaji_pokok_bulan_januari' => ['required'],
            'npwp' => ['required', 'regex:/^[0-9]{15,16}$/'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nama_sekolah.exists' => 'Nama Sekolah tidak ditemukan di menu Profil Sekolah.',
            'nrg.regex' => 'NRG harus 12 digit angka.',
            'nuptk.regex' => 'NUPTK harus 16 digit angka.',
            'npwp.regex' => 'NPWP harus 15-16 digit angka.',
        ];
    }
}
