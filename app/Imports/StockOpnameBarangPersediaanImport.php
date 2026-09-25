<?php

namespace App\Imports;

use App\Models\ProfilSekolah;
use App\Models\RincianBelanjaBarangHabisPakai;
use App\Models\StockOpnameBarangPersediaan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Import data Stock Opname (Rincian Barang Persediaan BOSP) dari
 * Excel/CSV untuk satu tahun + satu triwulan.
 *
 * BERUBAH TOTAL sejak permintaan user 2026-09-19 (jawaban AskUserQuestion
 * "Baris otomatis mengikuti RBBHP"): baris Stock Opname TIDAK LAGI bisa
 * dibuat bebas dari Import (nama barang & jumlah barisnya SELALU
 * mengikuti Rincian Belanja Barang Habis Pakai, lihat
 * RincianBelanjaBarangHabisPakai::booted()) - jadi Import ini SEKARANG
 * HANYA MENGISI FIELD MANUAL (Satuan, Harga, & ketiga Kuantitas Saldo
 * Awal/Penerimaan/Pengeluaran - lihat
 * StockOpnameBarangPersediaan::FIELD_KUANTITAS_MANUAL) pada baris Stock
 * Opname yang SUDAH ADA (dicocokkan lewat NPSN + Nama Barang Persediaan
 * + Tahun + Triwulan yang SAMA terhadap RincianBelanjaBarangHabisPakai),
 * BUKAN membuat baris baru. Kolom Nama Barang Persediaan pada file HANYA
 * dipakai utk MENCARI baris RBBHP yang cocok (bukan disimpan) - baris
 * yang namanya TIDAK ditemukan di RBBHP triwulan yang sama GAGAL
 * (failure, bukan exception) dengan pesan jelas. Kolom Jumlah (Rp) &
 * Saldo Akhir Kuantitas pada file DIABAIKAN (dihitung ULANG otomatis
 * dari Harga & ketiga Kuantitas manual yang baru diimport - lihat
 * StockOpnameBarangPersediaan::hitungSemuaRumus()), begitu juga kolom
 * Keterangan (SELALU "BOSP Triwulan {triwulan} Tahun {tahun}" otomatis,
 * lihat StockOpnameBarangPersediaan::keteranganOtomatis() - TIDAK
 * pernah diambil dari file).
 *
 * Untuk Admin BOSP (bukan Superadmin), data selalu dikaitkan ke
 * sekolahnya sendiri - kolom NPSN pada file diabaikan.
 */
class StockOpnameBarangPersediaanImport implements ToModel, WithHeadingRow, WithValidation
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

        $namaBarang = trim((string) ($row['nama_barang_persediaan'] ?? ''));

        $rincian = RincianBelanjaBarangHabisPakai::query()
            ->where('profil_sekolah_id', $profilSekolahId)
            ->where('tahun', $this->tahun)
            ->where('triwulan', $this->triwulan)
            ->where('nama_barang', $namaBarang)
            ->first();

        if (! $rincian) {
            // Baris ini SENGAJA "gagal" (bukan exception) supaya user
            // melihat pesan jelas per baris (pola sama seperti pesan
            // failure lain di aplikasi ini) - lihat
            // onFailure()/rules()/customValidationMessages() di bawah,
            // TIDAK BISA dilakukan lewat rules() biasa karena butuh
            // kombinasi profil_sekolah_id (yang belum tentu dari kolom
            // NPSN utk Admin BOSP) + tahun + triwulan sekaligus.
            $this->tambahanGagal[] = new Failure(
                0,
                'nama_barang_persediaan',
                ['Nama Barang Persediaan "'.$namaBarang.'" tidak ditemukan di Rincian Belanja Barang Habis Pakai Triwulan '.$this->triwulan.' untuk sekolah ini - Stock Opname hanya bisa diisi utk barang yang sudah ada di Rincian Belanja Barang Habis Pakai.'],
                $row
            );

            return null;
        }

        $stockOpname = StockOpnameBarangPersediaan::firstOrCreate(
            ['rincian_belanja_barang_habis_pakai_id' => $rincian->id],
            [
                'profil_sekolah_id' => $rincian->profil_sekolah_id,
                'tahun' => $rincian->tahun,
                'triwulan' => $rincian->triwulan,
                'keterangan' => StockOpnameBarangPersediaan::keteranganOtomatis($rincian->triwulan, $rincian->tahun),
                'created_by' => $this->createdBy,
            ]
        );

        $harga = $this->angka($row['harga'] ?? null);
        $saldoAwalKuantitas = $this->angka($row['saldo_awal_kuantitas'] ?? null);
        $penerimaanKuantitas = $this->angka($row['penerimaan_kuantitas'] ?? null);
        $pengeluaranKuantitas = $this->angka($row['pengeluaran_kuantitas'] ?? null);

        $stockOpname->fill(array_merge(
            [
                'satuan' => $this->kosongkanJikaKosong($row['satuan_unit'] ?? null),
                'harga' => $harga,
                'saldo_awal_kuantitas' => $saldoAwalKuantitas,
                'penerimaan_kuantitas' => $penerimaanKuantitas,
                'pengeluaran_kuantitas' => $pengeluaranKuantitas,
            ],
            StockOpnameBarangPersediaan::hitungSemuaRumus($harga, $saldoAwalKuantitas, $penerimaanKuantitas, $pengeluaranKuantitas),
        ));
        $stockOpname->save();

        // Sudah disimpan manual di atas (butuh rumus turunan sekaligus) -
        // return null supaya Maatwebsite TIDAK mencoba create() lagi.
        return null;
    }

    /**
     * Kegagalan TAMBAHAN (di luar mekanisme WithValidation biasa) - lihat
     * catatan model() di atas. Dibaca oleh
     * Index::import()/StockOpnameBarangPersediaanImport pemanggil lewat
     * failures() bawaan Maatwebsite (Importable trait) SETELAH
     * Excel::import() selesai - lihat customValidationMessages() TIDAK
     * dipakai di sini karena bukan lewat rules().
     *
     * @var array<int, Failure>
     */
    public array $tambahanGagal = [];

    private function angka(mixed $nilai): ?int
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        return (int) preg_replace('/\D/', '', (string) $nilai);
    }

    private function kosongkanJikaKosong(mixed $nilai): ?string
    {
        return $nilai !== null && $nilai !== '' ? (string) $nilai : null;
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
            'nama_barang_persediaan' => ['required', 'string', 'max:255'],
            'satuan_unit' => ['nullable', 'string', 'max:50'],
            'harga' => ['nullable'],
            'saldo_awal_kuantitas' => ['nullable'],
            'penerimaan_kuantitas' => ['nullable'],
            'pengeluaran_kuantitas' => ['nullable'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'npsn.regex' => 'NPSN harus berupa angka, maksimal 20 digit.',
            'npsn.exists' => 'NPSN tidak ditemukan di menu Profil Sekolah.',
            'nama_barang_persediaan.required' => 'Nama Barang Persediaan wajib diisi.',
        ];
    }
}
