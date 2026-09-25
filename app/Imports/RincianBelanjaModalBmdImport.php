<?php

namespace App\Imports;

use App\Models\ProfilSekolah;
use App\Models\RincianBelanjaModalBmd;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Import data tab "BMD" dari Excel/CSV untuk satu tahun + satu triwulan -
 * pola SAMA PERSIS seperti RincianBelanjaModalImport (tab "jenis"), HANYA
 * kolom yang jauh lebih banyak (lihat App\Exports\RincianBelanjaModalBmdExport
 * untuk urutan & nama kolom yang diharapkan).
 *
 * Kolom NPSN/Lokasi/Subrayon SELALU diabaikan untuk penyimpanan (sama
 * seperti tab "jenis": hanya dipakai NPSN untuk mencari sekolah tujuan,
 * Lokasi & Subrayon TIDAK PERNAH disimpan - selalu dari relasi
 * profilSekolah()). Kolom Program/Kegiatan/Kode Sub Kegiatan/Nama Sub
 * Kegiatan JUGA SELALU diabaikan - nilainya konstan (lihat
 * RincianBelanjaModalBmd::PROGRAM dst.), TIDAK ADA kolom database untuk
 * ini. Kolom Jenis Aset (KIB), Total, & KEDUA kolom "Keterangan" JUGA
 * SELALU diabaikan meski ada isinya di file - field ini SELALU dihitung
 * ULANG otomatis dari Rekening Belanja/Jumlah/Harga Satuan/Triwulan/Tahun
 * (RincianBelanjaModalBmd::hitungSemuaOtomatis()), sesuai permintaan user
 * eksplisit 2026-09-22: "untuk field yang otomatis hasil perhitungan juga
 * harus otomatis". Kolom PPK JUGA SELALU diabaikan meski ada isinya di
 * file (SEJAK 2026-09-22, perubahan keputusan) - SELALU dihitung ULANG
 * otomatis dari Nama Kepala Sekolah pada Profil Sekolah tujuan baris ini
 * (RincianBelanjaModalBmd::ppkOtomatis()), pola sama seperti Jenis Aset/
 * Total/Keterangan di atas. (Kedua kolom "Keterangan" pada file bahkan akan
 * bentrok jadi SATU kunci "keterangan" saja oleh WithHeadingRow karena
 * judulnya sama persis - tidak masalah karena keduanya tidak pernah
 * dibaca dari file.)
 *
 * Untuk Admin BOSP (bukan Superadmin), data selalu dikaitkan ke
 * sekolahnya sendiri - kolom NPSN pada file diabaikan.
 *
 * TIDAK memakai WithUpserts/uniqueBy() - Nama Barang boleh berulang, pola
 * sama seperti RincianBelanjaModalImport. Setiap baris pada file SELALU
 * masuk sebagai baris/data baru.
 */
class RincianBelanjaModalBmdImport implements ToModel, WithHeadingRow, WithValidation
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

        // PPK OTOMATIS dari Nama Kepala Sekolah Profil Sekolah tujuan
        // (SELALU dihitung ulang, TIDAK PERNAH dibaca dari kolom "ppk"
        // pada file - lihat docblock kelas di atas).
        $ppkOtomatis = RincianBelanjaModalBmd::ppkOtomatis(
            ($sekolah ?? ProfilSekolah::find($profilSekolahId))?->nama_kepala_sekolah
        );

        $jumlah = ($row['jumlah'] ?? null) !== null && $row['jumlah'] !== ''
            ? (int) preg_replace('/\D/', '', (string) $row['jumlah'])
            : null;
        $hargaSatuan = ($row['harga_satuan'] ?? null) !== null && $row['harga_satuan'] !== ''
            ? (int) preg_replace('/\D/', '', (string) $row['harga_satuan'])
            : null;
        $rekeningBelanja = ($row['rekening_belanja'] ?? null) !== null && $row['rekening_belanja'] !== ''
            ? (string) $row['rekening_belanja']
            : null;

        $otomatis = RincianBelanjaModalBmd::hitungSemuaOtomatis(
            $rekeningBelanja,
            $jumlah,
            $hargaSatuan,
            $this->triwulan,
            $this->tahun,
        );

        return new RincianBelanjaModalBmd([
            'profil_sekolah_id' => $profilSekolahId,
            'tahun' => $this->tahun,
            'triwulan' => $this->triwulan,
            'bentuk_kontrak' => $this->nilai($row, 'bentuk_kontrak_transaksi'),
            'atribusi' => $this->nilai($row, 'atribusi'),
            'jumlah_termin' => $this->nilai($row, 'jumlah_termin'),
            'ppk' => $ppkOtomatis,
            'nomor_dokumen' => $this->nilai($row, 'nomor_dokumen'),
            'tanggal_perolehan' => $this->parseTanggal($row['tgl_perolehan'] ?? null),
            'penyedia' => $this->nilai($row, 'penyedia'),
            'kode_belanja' => $this->nilai($row, 'kode_belanja'),
            'rekening_belanja' => $rekeningBelanja,
            'jenis_aset' => $otomatis['jenis_aset'],
            'sub_sub_rincian_objek' => $this->nilai($row, 'sub_sub_rincian_objek'),
            'jumlah' => $jumlah,
            'satuan' => $this->nilai($row, 'satuan'),
            'harga_satuan' => $hargaSatuan,
            'total' => $otomatis['total'],
            'no_bast' => $this->nilai($row, 'no_bast'),
            'tanggal_bast' => $this->parseTanggal($row['tgl_bast'] ?? null),
            'keterangan_bos' => $otomatis['keterangan_bos'],
            'nomor_surat_pernyataan' => $this->nilai($row, 'nomor_surat_pernyataan'),
            'tanggal_surat_pernyataan' => $this->parseTanggal($row['tgl_surat_pernyataan'] ?? null),
            'nama_pengurus_barang' => $this->nilai($row, 'nama_pengurus_barang'),
            'jabatan' => $this->nilai($row, 'jabatan'),
            'pejabat_penata_usaha' => $this->nilai($row, 'pejabat_penata_usaha'),
            'nama_barang' => (string) ($row['nama_barang'] ?? ''),
            'spesifikasi_nama_barang' => $this->nilai($row, 'spesifikasi_nama_barang'),
            'spesifikasi_lain' => $this->nilai($row, 'spesifikasi_lain_serial_nomor'),
            'merk_pengarang' => $this->nilai($row, 'merk_pengarang'),
            'keterangan_bosp' => $otomatis['keterangan_bosp'],
            'created_by' => $this->createdBy,
        ]);
    }

    private function nilai(array $row, string $kunci): ?string
    {
        $nilai = $row[$kunci] ?? null;

        return $nilai !== null && $nilai !== '' ? (string) $nilai : null;
    }

    private function parseTanggal(mixed $nilai): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        if (is_numeric($nilai)) {
            // Tanggal Excel (serial number) - dikonversi ke tanggal biasa.
            return Date::excelToDateTimeObject($nilai)->format('Y-m-d');
        }

        try {
            return Carbon::parse((string) $nilai)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
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
            'nama_barang' => ['required', 'string', 'max:255'],
            'bentuk_kontrak_transaksi' => ['nullable', Rule::in(array_keys(RincianBelanjaModalBmd::BENTUK_KONTRAK_OPTIONS))],
            'rekening_belanja' => ['nullable', Rule::in(array_keys(RincianBelanjaModalBmd::REKENING_BELANJA_OPTIONS))],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'npsn.regex' => 'NPSN harus berupa angka, maksimal 20 digit.',
            'npsn.exists' => 'NPSN tidak ditemukan di menu Profil Sekolah.',
            'nama_barang.required' => 'Nama Barang wajib diisi.',
            'bentuk_kontrak_transaksi.in' => 'Bentuk Kontrak / Transaksi harus salah satu pilihan yang tersedia.',
            'rekening_belanja.in' => 'Rekening Belanja harus salah satu pilihan yang tersedia.',
        ];
    }
}
