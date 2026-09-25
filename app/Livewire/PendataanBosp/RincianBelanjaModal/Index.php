<?php

namespace App\Livewire\PendataanBosp\RincianBelanjaModal;

use App\Exports\RincianBelanjaModalBmdExport;
use App\Exports\RincianBelanjaModalBmdRekapExport;
use App\Exports\RincianBelanjaModalExport;
use App\Imports\RincianBelanjaModalBmdImport;
use App\Imports\RincianBelanjaModalImport;
use App\Livewire\Concerns\HasZoomTampilan;
use App\Livewire\Concerns\MenolakEditJikaTerkunciVerval;
use App\Models\ProfilSekolah;
use App\Models\RincianBelanjaModal;
use App\Models\RincianBelanjaModalBmd;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

/**
 * Rincian Belanja Modal - Pendataan BOSP (permintaan user 2026-09-11,
 * Part 21 poin 2).
 *
 * Struktur & pola KELAS INI disalin PERSIS dari
 * BelanjaPemeliharaanBangunan\Index (2 lapis tab: tab UTAMA
 * $tabUtama = 'peralatan_mesin'/'aset_tetap_lainnya', lihat
 * RincianBelanjaModal::JENIS_OPTIONS, DI ATAS tab Triwulan 1-4) - HANYA
 * nama Model/tabel/namespace/modal yang diganti, field & logic-nya
 * PERSIS SAMA (Kode UPB, Nama Barang, Nama Merk Barang, Volume, Satuan,
 * Harga Satuan, Total Harga, Asal Usul, Tanggal, Keterangan - sesuai
 * gambar contoh tabel yang diupload user).
 *
 * Pindah tab utama TIDAK mereset Triwulan yang sedang aktif.
 *
 * Sesuai jawaban AskUserQuestion 2026-09-11: 1 sekolah bisa punya BANYAK
 * baris per triwulan per jenis (banyak barang berbeda), dan Nama Barang
 * BOLEH DUPLIKAT - TIDAK ADA logic "xxxSudahDipakai()" untuk field ini.
 *
 * NPSN & Nama Sekolah SELALU tampilan read-only (otomatis dari relasi
 * profilSekolah), Total Harga SELALU hasil rumus otomatis (Volume x
 * Harga Satuan - lihat RincianBelanjaModal::hitungTotalHarga()), keduanya
 * TIDAK BISA diedit dari jalur manapun.
 *
 * - Superadmin: melihat & mengelola data semua sekolah, bisa memilih
 *   sekolah manapun saat menambah/mengedit data, dan memfilter tabel per
 *   sekolah.
 * - Admin BOSP: hanya melihat & mengelola data sekolahnya sendiri (field
 *   Sekolah otomatis terkunci ke sekolahnya).
 */
#[Layout('layouts.app')]
#[Title('Rincian Belanja Modal')]
class Index extends Component
{
    use HasZoomTampilan;
    use MenolakEditJikaTerkunciVerval;
    use WithFileUploads;

    #[Url(as: 'tab')]
    public string $tabUtama = RincianBelanjaModal::JENIS_PERALATAN_MESIN;

    public int $tahun;

    #[Url(as: 'triwulan')]
    public int $triwulan = 1;

    public string $search = '';

    public ?int $filterSekolahId = null;

    public ?int $editingId = null;

    /**
     * Dinaikkan setiap kali form Tambah/Edit dibuka - dipakai sebagai
     * wire:key pada kotak input Rupiah (Harga Satuan, yang wire:ignore)
     * supaya kotaknya selalu ter-refresh.
     */
    public int $formInstance = 0;

    /**
     * Data untuk input LANGSUNG di tiap kotak/kolom tabel (di luar form
     * modal Tambah/Edit) - array 2 dimensi [rowId][field] => nilai, diisi
     * ulang tiap render() dari data ter-terbaru di database. Kunci
     * NEGATIF (-$sekolah->id) = baris placeholder kosong siap-isi
     * (lihat komentar kelas & render()).
     *
     * @var array<int, array<string, string>>
     */
    public array $baris = [];

    /**
     * Dinaikkan PER BARIS setiap kali ada input langsung di kotak Harga
     * Satuan baris itu yang ditolak - dipakai sebagai bagian wire:key
     * kotak Harga Satuan (wire:ignore) baris tsb.
     *
     * @var array<int, int>
     */
    public array $revisiBaris = [];

    public ?int $profil_sekolah_id = null;

    public string $kode_upb = '';

    public string $nama_barang = '';

    public string $nama_merk_barang = '';

    public string $volume = '';

    public string $satuan = '';

    public string $harga_satuan = '';

    public string $asal_usul = '';

    public string $tanggal = '';

    public string $keterangan = '';

    public bool $showForm = false;

    public ?int $confirmingDeleteId = null;

    public $fileImport = null;

    public ?string $errorImport = null;

    public ?string $errorExport = null;

    /**
     * Seluruh state untuk tab utama KETIGA "BMD" (permintaan user
     * 2026-09-22) - array/property TERPISAH dari $baris & properti form
     * di atas (dipakai tab "jenis" Peralatan & Mesin/Aset Tetap Lainnya),
     * karena BMD memakai tabel TERPISAH (App\Models\RincianBelanjaModalBmd)
     * dengan field yang SAMA SEKALI berbeda - pola sama seperti
     * $barisOpname pada App\Livewire\PendataanBosp\RincianBelanjaBarangHabisPakai\Index.
     * $profil_sekolah_id & $formInstance TETAP dipakai bersama (shared)
     * dengan form KIB di atas, mengikuti pola yang sama.
     *
     * @var array<int, array<string, string>>
     */
    public array $barisBmd = [];

    /**
     * @var array<int, int>
     */
    public array $revisiBarisBmd = [];

    public ?int $editingBmdId = null;

    public bool $showFormBmd = false;

    public ?int $confirmingDeleteBmdId = null;

    public int $formInstanceBmd = 0;

    public string $bmd_bentuk_kontrak = '';

    public string $bmd_atribusi = '';

    public string $bmd_jumlah_termin = '';

    public string $bmd_nomor_dokumen = '';

    public string $bmd_tanggal_perolehan = '';

    public string $bmd_penyedia = '';

    public string $bmd_kode_belanja = '';

    public string $bmd_rekening_belanja = '';

    public string $bmd_sub_sub_rincian_objek = '';

    public string $bmd_jumlah = '';

    public string $bmd_satuan = '';

    public string $bmd_harga_satuan = '';

    public string $bmd_no_bast = '';

    public string $bmd_tanggal_bast = '';

    public string $bmd_nomor_surat_pernyataan = '';

    public string $bmd_tanggal_surat_pernyataan = '';

    public string $bmd_nama_pengurus_barang = '';

    public string $bmd_jabatan = '';

    public string $bmd_pejabat_penata_usaha = '';

    public string $bmd_nama_barang = '';

    public string $bmd_spesifikasi_nama_barang = '';

    public string $bmd_spesifikasi_lain = '';

    public string $bmd_merk_pengarang = '';

    /**
     * Sub-tab KELIMA tab utama "BMD" - "Rekap BMD Tahun Anggaran {tahun}"
     * (permintaan user 2026-09-22, jawaban AskUserQuestion "Hanya tampil
     * & export (Recommended)") - menggabungkan data BMD TW-1 s.d. TW-4
     * tahun berjalan dalam SATU tabel read-only (TIDAK bisa
     * ditambah/diedit/dihapus dari tab ini, HANYA lihat & Export Excel).
     * SENGAJA property BOOLEAN terpisah (BUKAN menambah nilai ke
     * $triwulan, mis. $triwulan = 5) supaya $triwulan tetap murni
     * menyimpan Triwulan 1-4 yang valid untuk RincianBelanjaModalBmd::
     * TRIWULAN_OPTIONS, & supaya query/tampilan tab "jenis" (yang berbagi
     * $triwulan) SAMA SEKALI TIDAK TERPENGARUH. Sengaja TIDAK direset
     * saat pindahTabUtama() (pola sama seperti $triwulan yang juga TIDAK
     * direset) - HANYA direset ke false lewat pindahTab() (pilih salah
     * satu Triwulan) supaya user selalu balik ke tampilan per-Triwulan
     * yang bisa diedit begitu memilih Triwulan manapun secara eksplisit.
     */
    public bool $tampilRekapBmd = false;

    public function mount(): void
    {
        $this->tahun = now()->year;
    }

    protected function bolehKelolaSemua(): bool
    {
        return auth()->user()->isSuperadmin();
    }

    protected function sekolahSayaId(): ?int
    {
        return auth()->user()->profil_sekolah_id;
    }

    protected function bolehKelola(int $profilSekolahId): bool
    {
        return $this->bolehKelolaSemua() || $this->sekolahSayaId() === $profilSekolahId;
    }

    /**
     * Field-field yang bisa diedit LANGSUNG di kotak tabel (di luar NPSN/
     * Nama Sekolah yang selalu read-only otomatis, dan Total Harga yang
     * selalu hasil rumus).
     */
    protected function daftarFieldEditable(): array
    {
        return [
            'kode_upb', 'nama_barang', 'nama_merk_barang', 'volume',
            'satuan', 'harga_satuan', 'asal_usul', 'tanggal', 'keterangan',
        ];
    }

    /**
     * Field-field yang bisa diedit LANGSUNG di kotak tabel tab "BMD" (di
     * luar NPSN/Lokasi/Subrayon yang selalu read-only otomatis dari
     * Profil Sekolah; Program/Kegiatan/Kode Sub Kegiatan/Nama Sub
     * Kegiatan yang selalu konstan - lihat RincianBelanjaModalBmd; Jenis
     * Aset (KIB)/Total/kedua Keterangan yang selalu hasil rumus/relasi -
     * lihat RincianBelanjaModalBmd::hitungSemuaOtomatis(); & PPK yang
     * SEJAK 2026-09-22 juga otomatis dari Nama Kepala Sekolah Profil
     * Sekolah - lihat RincianBelanjaModalBmd::ppkOtomatis()).
     */
    protected function daftarFieldEditableBmd(): array
    {
        return [
            'bentuk_kontrak', 'atribusi', 'jumlah_termin', 'nomor_dokumen',
            'tanggal_perolehan', 'penyedia', 'kode_belanja', 'rekening_belanja',
            'sub_sub_rincian_objek', 'jumlah', 'satuan', 'harga_satuan',
            'no_bast', 'tanggal_bast', 'nomor_surat_pernyataan', 'tanggal_surat_pernyataan',
            'nama_pengurus_barang', 'jabatan', 'pejabat_penata_usaha', 'nama_barang',
            'spesifikasi_nama_barang', 'spesifikasi_lain', 'merk_pengarang',
        ];
    }

    /**
     * Field-field angka pada tab "BMD" (dipusatkan di sini karena dipakai
     * berulang oleh prosesPerubahanBarisBmd()/aturanFieldBmd() untuk
     * menentukan cast (int) & aturan validasi). "jumlah_termin" SENGAJA
     * TIDAK termasuk (teks bebas, mis. "3x", bukan murni angka - sesuai
     * permintaan user "field Jumlah Termin ... di isi manual" tanpa
     * format khusus).
     */
    protected function daftarFieldAngkaBmd(): array
    {
        return ['jumlah', 'harga_satuan'];
    }

    /**
     * Field-field yang begitu berubah memicu perhitungan ULANG seluruh
     * field otomatis tab "BMD" (Jenis Aset (KIB) & Total - lihat
     * RincianBelanjaModalBmd::hitungSemuaOtomatis()).
     */
    protected function daftarFieldPemicuRumusBmd(): array
    {
        return ['rekening_belanja', 'jumlah', 'harga_satuan'];
    }

    /**
     * Pindah tab UTAMA (Rincian Belanja Modal Peralatan & Mesin (KIB B) /
     * Rincian Belanja Modal Aset Tetap Lainnya (KIB E) / BMD - permintaan
     * user 2026-09-22 menambahkan tab ketiga "BMD"). Triwulan aktif
     * TIDAK direset - kalau user sedang di TW-2 lalu pindah tab utama,
     * tetap di TW-2 pada tab utama yang baru. Divalidasi terhadap
     * TAB_UTAMA_OPTIONS (BUKAN JENIS_OPTIONS lagi) supaya tab "BMD" ikut
     * diterima - lihat catatan konstanta tsb.
     */
    public function pindahTabUtama(string $tab): void
    {
        if (! array_key_exists($tab, RincianBelanjaModal::TAB_UTAMA_OPTIONS)) {
            return;
        }

        $this->tabUtama = $tab;
    }

    public function pindahTab(int $triwulan): void
    {
        $this->triwulan = $triwulan;
        // Memilih Triwulan manapun secara eksplisit SELALU keluar dari
        // tampilan Rekap (kalau sedang aktif) - balik ke tampilan
        // per-Triwulan yang bisa diedit.
        $this->tampilRekapBmd = false;
    }

    /**
     * Pindah ke sub-tab "Rekap BMD Tahun Anggaran {tahun}" (HANYA
     * berlaku/terlihat pada tab utama BMD - lihat komentar properti
     * $tampilRekapBmd & renderRekapBmd()).
     */
    public function pindahKeRekapBmd(): void
    {
        $this->tampilRekapBmd = true;
    }

    /**
     * Menangkap perubahan pada kotak input LANGSUNG di tabel (property
     * "baris.{rowId}.{field}"). $rowId NEGATIF berarti baris PLACEHOLDER
     * (lihat komentar kelas & render()) - ditangani terpisah oleh
     * updatedBarisBaru() karena belum ada baris sungguhan di database.
     */
    public function updated(string $name, mixed $value): void
    {
        if (str_starts_with($name, 'barisBmd.')) {
            $this->prosesPerubahanBarisBmd($name, $value);

            return;
        }

        if (! str_starts_with($name, 'baris.')) {
            return;
        }

        $bagian = explode('.', $name);

        if (count($bagian) !== 3) {
            return;
        }

        [, $rowIdMentah, $field] = $bagian;
        $rowId = (int) $rowIdMentah;

        if (! in_array($field, $this->daftarFieldEditable(), true)) {
            return;
        }

        if ($rowId < 0) {
            $this->updatedBarisBaru($name, $rowId, $field, $value);

            return;
        }

        $baris = RincianBelanjaModal::find($rowId);

        if (! $baris) {
            // Baris sudah dihapus (mis. dari tab/sesi lain) - abaikan saja,
            // render() berikutnya akan menghilangkan baris ini dari tabel.
            return;
        }

        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
        $this->abortJikaTerkunciVerval($baris->profil_sekolah_id, $this->tahun, $baris->triwulan);

        $nilai = $value === '' || $value === null ? null : $value;

        $rules = $this->aturanField($field);

        $validator = Validator::make(['nilai' => $nilai], ['nilai' => $rules], [], ['nilai' => 'Nilai']);

        if ($validator->fails()) {
            $this->addError($name, $validator->errors()->first('nilai'));
            $this->revisiBaris[$rowId] = ($this->revisiBaris[$rowId] ?? 0) + 1;

            return;
        }

        $data = [$field => in_array($field, ['volume', 'harga_satuan'], true) && $nilai !== null ? (int) $nilai : $nilai];

        if (in_array($field, ['volume', 'harga_satuan'], true)) {
            $volumeBaru = $field === 'volume' ? $data['volume'] : $baris->volume;
            $hargaBaru = $field === 'harga_satuan' ? $data['harga_satuan'] : $baris->harga_satuan;
            $data['total_harga'] = RincianBelanjaModal::hitungTotalHarga($volumeBaru, $hargaBaru);
        }

        $baris->update($data);
    }

    /**
     * Menangani input langsung pada BARIS PLACEHOLDER (baris kosong
     * siap-isi yang SELALU ditampilkan di akhir daftar tiap sekolah).
     * Kuncinya NEGATIF ($idPlaceholder = -$sekolahId). Baris ini BELUM
     * ADA di database - begitu SALAH SATU kotaknya diisi, baris baru
     * langsung DIBUAT untuk sekolah+tahun+triwulan+jenis ini, lalu
     * placeholder-nya sendiri otomatis kosong lagi pada render()
     * berikutnya - TIDAK ADA pengecekan duplikat apapun pada Nama Barang
     * (sesuai jawaban AskUserQuestion "boleh duplikat").
     */
    protected function updatedBarisBaru(string $name, int $idPlaceholder, string $field, mixed $value): void
    {
        $sekolahId = abs($idPlaceholder);

        abort_unless($this->bolehKelola($sekolahId), 403);
        $this->abortJikaTerkunciVerval($sekolahId, $this->tahun, $this->triwulan);

        $nilai = $value === '' || $value === null ? null : $value;

        if ($nilai === null) {
            return;
        }

        // BEDA dengan baris yang SUDAH ADA - baris placeholder ini bisa
        // mulai diisi dari kotak MANAPUN (mis. Volume duluan), jadi semua
        // field dianggap 'nullable' di sini meski field itu 'required'
        // pada aturanField() biasa.
        $rules = match ($field) {
            'volume' => ['nullable', 'integer', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'harga_satuan' => ['nullable', 'integer', 'min:0'],
            'tanggal' => ['nullable', 'date'],
            'keterangan' => ['nullable', 'string'],
            default => ['nullable', 'string', 'max:255'],
        };

        $validator = Validator::make(['nilai' => $nilai], ['nilai' => $rules], [], ['nilai' => 'Nilai']);

        if ($validator->fails()) {
            $this->addError($name, $validator->errors()->first('nilai'));
            $this->revisiBaris[$idPlaceholder] = ($this->revisiBaris[$idPlaceholder] ?? 0) + 1;

            return;
        }

        $data = [$field => in_array($field, ['volume', 'harga_satuan'], true) ? (int) $nilai : $nilai];

        $data['total_harga'] = RincianBelanjaModal::hitungTotalHarga(
            $field === 'volume' ? $data['volume'] : null,
            $field === 'harga_satuan' ? $data['harga_satuan'] : null,
        );

        $data['profil_sekolah_id'] = $sekolahId;
        $data['jenis'] = $this->tabUtama;
        $data['tahun'] = $this->tahun;
        $data['triwulan'] = $this->triwulan;
        $data['created_by'] = auth()->id();

        RincianBelanjaModal::create($data);

        $this->revisiBaris[$idPlaceholder] = ($this->revisiBaris[$idPlaceholder] ?? 0) + 1;
    }

    /**
     * Menangkap perubahan pada kotak input LANGSUNG di tabel tab "BMD"
     * (property "barisBmd.{rowId}.{field}") - pola SAMA PERSIS seperti
     * updated()/updatedBarisBaru() di atas untuk tab "jenis", HANYA nama
     * model/property yang diganti. $rowId NEGATIF = baris placeholder
     * (belum ada di database), ditangani updatedBarisBmdBaru().
     */
    protected function prosesPerubahanBarisBmd(string $name, mixed $value): void
    {
        $bagian = explode('.', $name);

        if (count($bagian) !== 3) {
            return;
        }

        [, $rowIdMentah, $field] = $bagian;
        $rowId = (int) $rowIdMentah;

        if (! in_array($field, $this->daftarFieldEditableBmd(), true)) {
            return;
        }

        if ($rowId < 0) {
            $this->updatedBarisBmdBaru($name, $rowId, $field, $value);

            return;
        }

        $baris = RincianBelanjaModalBmd::find($rowId);

        if (! $baris) {
            // Baris sudah dihapus (mis. dari tab/sesi lain) - abaikan saja,
            // render() berikutnya akan menghilangkan baris ini dari tabel.
            return;
        }

        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $nilai = $value === '' || $value === null ? null : $value;

        $rules = $this->aturanFieldBmd($field);

        $validator = Validator::make(['nilai' => $nilai], ['nilai' => $rules], [], ['nilai' => 'Nilai']);

        if ($validator->fails()) {
            $this->addError($name, $validator->errors()->first('nilai'));
            $this->revisiBarisBmd[$rowId] = ($this->revisiBarisBmd[$rowId] ?? 0) + 1;

            return;
        }

        $fieldAngka = $this->daftarFieldAngkaBmd();

        $data = [$field => in_array($field, $fieldAngka, true) && $nilai !== null ? (int) $nilai : $nilai];

        $baris->update($data);

        if (in_array($field, $this->daftarFieldPemicuRumusBmd(), true)) {
            $baris->refresh();
            $baris->update(array_merge(
                RincianBelanjaModalBmd::hitungSemuaOtomatis(
                    $baris->rekening_belanja,
                    $baris->jumlah,
                    $baris->harga_satuan,
                    $baris->triwulan,
                    $baris->tahun,
                ),
                // PPK ikut disegarkan (self-healing) supaya tetap sinkron
                // kalau Nama Kepala Sekolah pada Profil Sekolah sempat
                // berubah setelah baris ini dibuat.
                ['ppk' => RincianBelanjaModalBmd::ppkOtomatis($baris->profilSekolah?->nama_kepala_sekolah)],
            ));
        }
    }

    /**
     * Menangani input langsung pada BARIS PLACEHOLDER tab "BMD" - pola
     * SAMA PERSIS seperti updatedBarisBaru() di atas untuk tab "jenis".
     */
    protected function updatedBarisBmdBaru(string $name, int $idPlaceholder, string $field, mixed $value): void
    {
        $sekolahId = abs($idPlaceholder);

        abort_unless($this->bolehKelola($sekolahId), 403);

        $nilai = $value === '' || $value === null ? null : $value;

        if ($nilai === null) {
            return;
        }

        $rules = match ($field) {
            'jumlah', 'harga_satuan' => ['nullable', 'integer', 'min:0'],
            'tanggal_perolehan', 'tanggal_bast', 'tanggal_surat_pernyataan' => ['nullable', 'date'],
            'bentuk_kontrak' => ['nullable', Rule::in(array_keys(RincianBelanjaModalBmd::BENTUK_KONTRAK_OPTIONS))],
            'rekening_belanja' => ['nullable', Rule::in(array_keys(RincianBelanjaModalBmd::REKENING_BELANJA_OPTIONS))],
            default => ['nullable', 'string', 'max:255'],
        };

        $validator = Validator::make(['nilai' => $nilai], ['nilai' => $rules], [], ['nilai' => 'Nilai']);

        if ($validator->fails()) {
            $this->addError($name, $validator->errors()->first('nilai'));
            $this->revisiBarisBmd[$idPlaceholder] = ($this->revisiBarisBmd[$idPlaceholder] ?? 0) + 1;

            return;
        }

        $fieldAngka = $this->daftarFieldAngkaBmd();

        $data = [$field => in_array($field, $fieldAngka, true) ? (int) $nilai : $nilai];
        $data['profil_sekolah_id'] = $sekolahId;
        $data['tahun'] = $this->tahun;
        $data['triwulan'] = $this->triwulan;
        $data['created_by'] = auth()->id();

        $data = array_merge($data, RincianBelanjaModalBmd::hitungSemuaOtomatis(
            $field === 'rekening_belanja' ? $data['rekening_belanja'] : null,
            $field === 'jumlah' ? $data['jumlah'] : null,
            $field === 'harga_satuan' ? $data['harga_satuan'] : null,
            $this->triwulan,
            $this->tahun,
        ));

        // PPK OTOMATIS dari Nama Kepala Sekolah Profil Sekolah baris ini
        // (jawaban AskUserQuestion 2026-09-22) - dihitung sekali saat
        // baris baru dibuat, sama seperti field otomatis lainnya di atas.
        $data['ppk'] = RincianBelanjaModalBmd::ppkOtomatis(
            ProfilSekolah::find($sekolahId)?->nama_kepala_sekolah
        );

        RincianBelanjaModalBmd::create($data);

        $this->revisiBarisBmd[$idPlaceholder] = ($this->revisiBarisBmd[$idPlaceholder] ?? 0) + 1;
    }

    /**
     * Aturan validasi standar per field tab "BMD", dipakai baris yang
     * SUDAH ADA (prosesPerubahanBarisBmd()), form modal Tambah/Edit
     * (simpanBmd()), & Import Excel (RincianBelanjaModalBmdImport).
     */
    protected function aturanFieldBmd(string $field): array
    {
        if (in_array($field, $this->daftarFieldAngkaBmd(), true)) {
            return ['nullable', 'integer', 'min:0'];
        }

        return match ($field) {
            'bentuk_kontrak' => ['nullable', Rule::in(array_keys(RincianBelanjaModalBmd::BENTUK_KONTRAK_OPTIONS))],
            'rekening_belanja' => ['nullable', Rule::in(array_keys(RincianBelanjaModalBmd::REKENING_BELANJA_OPTIONS))],
            'tanggal_perolehan', 'tanggal_bast', 'tanggal_surat_pernyataan' => ['nullable', 'date'],
            'nama_barang' => ['required', 'string', 'max:255'],
            default => ['nullable', 'string', 'max:255'],
        };
    }

    /**
     * Aturan validasi standar per field, dipakai baris yang SUDAH ADA
     * (updated()) & form modal Tambah/Edit (simpan()).
     */
    protected function aturanField(string $field): array
    {
        return match ($field) {
            'kode_upb' => ['nullable', 'string', 'max:255'],
            'nama_barang' => ['required', 'string', 'max:255'],
            'nama_merk_barang' => ['nullable', 'string', 'max:255'],
            'volume' => ['nullable', 'integer', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'harga_satuan' => ['nullable', 'integer', 'min:0'],
            'asal_usul' => ['nullable', 'string', 'max:255'],
            'tanggal' => ['nullable', 'date'],
            'keterangan' => ['nullable', 'string'],
            default => [],
        };
    }

    public function tambah(): void
    {
        $this->resetForm();
        $this->formInstance++;

        if (! $this->bolehKelolaSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        $this->showForm = true;
        $this->dispatch('open-modal', 'rincian-belanja-modal-form');
    }

    public function edit(int $id): void
    {
        $baris = RincianBelanjaModal::findOrFail($id);

        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $this->formInstance++;
        $this->editingId = $baris->id;
        $this->profil_sekolah_id = $baris->profil_sekolah_id;
        $this->kode_upb = (string) $baris->kode_upb;
        $this->nama_barang = (string) $baris->nama_barang;
        $this->nama_merk_barang = (string) $baris->nama_merk_barang;
        $this->volume = $baris->volume !== null ? (string) $baris->volume : '';
        $this->satuan = (string) $baris->satuan;
        $this->harga_satuan = $baris->harga_satuan !== null ? (string) $baris->harga_satuan : '';
        $this->asal_usul = (string) $baris->asal_usul;
        $this->tanggal = $baris->tanggal?->format('Y-m-d') ?? '';
        $this->keterangan = (string) $baris->keterangan;
        $this->showForm = true;
        $this->dispatch('open-modal', 'rincian-belanja-modal-form');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'profil_sekolah_id', 'kode_upb', 'nama_barang',
            'nama_merk_barang', 'volume', 'satuan', 'harga_satuan',
            'asal_usul', 'tanggal', 'keterangan',
        ]);
        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'rincian-belanja-modal-form');
    }

    public function simpan(): void
    {
        if (! $this->bolehKelolaSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        abort_unless($this->profil_sekolah_id && $this->bolehKelola($this->profil_sekolah_id), 403);
        $this->abortJikaTerkunciVerval($this->profil_sekolah_id, $this->tahun, $this->triwulan);

        $validated = $this->validate([
            'profil_sekolah_id' => ['required', Rule::exists('profil_sekolah', 'id')],
            'kode_upb' => $this->aturanField('kode_upb'),
            'nama_barang' => $this->aturanField('nama_barang'),
            'nama_merk_barang' => $this->aturanField('nama_merk_barang'),
            'volume' => $this->aturanField('volume'),
            'satuan' => $this->aturanField('satuan'),
            'harga_satuan' => $this->aturanField('harga_satuan'),
            'asal_usul' => $this->aturanField('asal_usul'),
            'tanggal' => $this->aturanField('tanggal'),
            'keterangan' => $this->aturanField('keterangan'),
        ]);

        foreach (['volume', 'harga_satuan'] as $field) {
            $validated[$field] = $validated[$field] === null ? null : (int) $validated[$field];
        }

        // PostgreSQL menolak string kosong untuk kolom bertipe date, jadi
        // disamakan eksplisit jadi null kalau kosong (rule 'nullable'
        // Laravel tidak melakukan ini otomatis).
        $validated['tanggal'] = $validated['tanggal'] !== null && trim((string) $validated['tanggal']) !== ''
            ? $validated['tanggal']
            : null;

        $validated['total_harga'] = RincianBelanjaModal::hitungTotalHarga($validated['volume'], $validated['harga_satuan']);
        $validated['jenis'] = $this->tabUtama;
        $validated['tahun'] = $this->tahun;
        $validated['triwulan'] = $this->triwulan;

        if ($this->editingId) {
            $baris = RincianBelanjaModal::findOrFail($this->editingId);
            abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
            $baris->update($validated);
        } else {
            $validated['created_by'] = auth()->id();
            RincianBelanjaModal::create($validated);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'rincian-belanja-modal-form');
        session()->flash('status', 'Data Rincian Belanja Modal berhasil disimpan.');
    }

    public function konfirmasiHapus(int $id): void
    {
        $baris = RincianBelanjaModal::findOrFail($id);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $this->confirmingDeleteId = $id;
        $this->dispatch('open-modal', 'rincian-belanja-modal-hapus');
    }

    public function batalHapus(): void
    {
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'rincian-belanja-modal-hapus');
    }

    public function hapus(): void
    {
        $baris = RincianBelanjaModal::findOrFail($this->confirmingDeleteId);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
        $this->abortJikaTerkunciVerval($baris->profil_sekolah_id, $this->tahun, $baris->triwulan);

        $baris->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'rincian-belanja-modal-hapus');
        session()->flash('status', 'Data Rincian Belanja Modal berhasil dihapus.');
    }

    /**
     * CRUD modal Tambah/Edit/Hapus untuk tab "BMD" (permintaan user
     * 2026-09-22, jawaban AskUserQuestion "Modal + kotak isi langsung di
     * tabel") - pola SAMA PERSIS seperti tambah()/edit()/resetForm()/
     * batal()/simpan()/konfirmasiHapus()/batalHapus()/hapus() di atas
     * untuk tab "jenis", HANYA nama Model/property/nama modal yang
     * diganti supaya TIDAK bentrok dengan modal tab "jenis" (nama modal
     * 'rincian-belanja-modal-bmd-form'/'rincian-belanja-modal-bmd-hapus').
     */
    public function tambahBmd(): void
    {
        $this->resetFormBmd();
        $this->formInstanceBmd++;

        if (! $this->bolehKelolaSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        $this->showFormBmd = true;
        $this->dispatch('open-modal', 'rincian-belanja-modal-bmd-form');
    }

    public function editBmd(int $id): void
    {
        $baris = RincianBelanjaModalBmd::findOrFail($id);

        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $this->formInstanceBmd++;
        $this->editingBmdId = $baris->id;
        $this->profil_sekolah_id = $baris->profil_sekolah_id;
        $this->bmd_bentuk_kontrak = (string) $baris->bentuk_kontrak;
        $this->bmd_atribusi = (string) $baris->atribusi;
        $this->bmd_jumlah_termin = (string) $baris->jumlah_termin;
        $this->bmd_nomor_dokumen = (string) $baris->nomor_dokumen;
        $this->bmd_tanggal_perolehan = $baris->tanggal_perolehan?->format('Y-m-d') ?? '';
        $this->bmd_penyedia = (string) $baris->penyedia;
        $this->bmd_kode_belanja = (string) $baris->kode_belanja;
        $this->bmd_rekening_belanja = (string) $baris->rekening_belanja;
        $this->bmd_sub_sub_rincian_objek = (string) $baris->sub_sub_rincian_objek;
        $this->bmd_jumlah = $baris->jumlah !== null ? (string) $baris->jumlah : '';
        $this->bmd_satuan = (string) $baris->satuan;
        $this->bmd_harga_satuan = $baris->harga_satuan !== null ? (string) $baris->harga_satuan : '';
        $this->bmd_no_bast = (string) $baris->no_bast;
        $this->bmd_tanggal_bast = $baris->tanggal_bast?->format('Y-m-d') ?? '';
        $this->bmd_nomor_surat_pernyataan = (string) $baris->nomor_surat_pernyataan;
        $this->bmd_tanggal_surat_pernyataan = $baris->tanggal_surat_pernyataan?->format('Y-m-d') ?? '';
        $this->bmd_nama_pengurus_barang = (string) $baris->nama_pengurus_barang;
        $this->bmd_jabatan = (string) $baris->jabatan;
        $this->bmd_pejabat_penata_usaha = (string) $baris->pejabat_penata_usaha;
        $this->bmd_nama_barang = (string) $baris->nama_barang;
        $this->bmd_spesifikasi_nama_barang = (string) $baris->spesifikasi_nama_barang;
        $this->bmd_spesifikasi_lain = (string) $baris->spesifikasi_lain;
        $this->bmd_merk_pengarang = (string) $baris->merk_pengarang;
        $this->showFormBmd = true;
        $this->dispatch('open-modal', 'rincian-belanja-modal-bmd-form');
    }

    public function resetFormBmd(): void
    {
        $this->reset([
            'editingBmdId', 'profil_sekolah_id', 'bmd_bentuk_kontrak', 'bmd_atribusi',
            'bmd_jumlah_termin', 'bmd_nomor_dokumen', 'bmd_tanggal_perolehan',
            'bmd_penyedia', 'bmd_kode_belanja', 'bmd_rekening_belanja',
            'bmd_sub_sub_rincian_objek', 'bmd_jumlah', 'bmd_satuan', 'bmd_harga_satuan',
            'bmd_no_bast', 'bmd_tanggal_bast', 'bmd_nomor_surat_pernyataan',
            'bmd_tanggal_surat_pernyataan', 'bmd_nama_pengurus_barang', 'bmd_jabatan',
            'bmd_pejabat_penata_usaha', 'bmd_nama_barang', 'bmd_spesifikasi_nama_barang',
            'bmd_spesifikasi_lain', 'bmd_merk_pengarang',
        ]);
        $this->resetErrorBag();
    }

    public function batalBmd(): void
    {
        $this->showFormBmd = false;
        $this->resetFormBmd();
        $this->dispatch('close-modal', 'rincian-belanja-modal-bmd-form');
    }

    public function simpanBmd(): void
    {
        if (! $this->bolehKelolaSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        abort_unless($this->profil_sekolah_id && $this->bolehKelola($this->profil_sekolah_id), 403);

        $validated = $this->validate([
            'profil_sekolah_id' => ['required', Rule::exists('profil_sekolah', 'id')],
            'bmd_bentuk_kontrak' => $this->aturanFieldBmd('bentuk_kontrak'),
            'bmd_atribusi' => $this->aturanFieldBmd('atribusi'),
            'bmd_jumlah_termin' => $this->aturanFieldBmd('jumlah_termin'),
            'bmd_nomor_dokumen' => $this->aturanFieldBmd('nomor_dokumen'),
            'bmd_tanggal_perolehan' => $this->aturanFieldBmd('tanggal_perolehan'),
            'bmd_penyedia' => $this->aturanFieldBmd('penyedia'),
            'bmd_kode_belanja' => $this->aturanFieldBmd('kode_belanja'),
            'bmd_rekening_belanja' => $this->aturanFieldBmd('rekening_belanja'),
            'bmd_sub_sub_rincian_objek' => $this->aturanFieldBmd('sub_sub_rincian_objek'),
            'bmd_jumlah' => $this->aturanFieldBmd('jumlah'),
            'bmd_satuan' => $this->aturanFieldBmd('satuan'),
            'bmd_harga_satuan' => $this->aturanFieldBmd('harga_satuan'),
            'bmd_no_bast' => $this->aturanFieldBmd('no_bast'),
            'bmd_tanggal_bast' => $this->aturanFieldBmd('tanggal_bast'),
            'bmd_nomor_surat_pernyataan' => $this->aturanFieldBmd('nomor_surat_pernyataan'),
            'bmd_tanggal_surat_pernyataan' => $this->aturanFieldBmd('tanggal_surat_pernyataan'),
            'bmd_nama_pengurus_barang' => $this->aturanFieldBmd('nama_pengurus_barang'),
            'bmd_jabatan' => $this->aturanFieldBmd('jabatan'),
            'bmd_pejabat_penata_usaha' => $this->aturanFieldBmd('pejabat_penata_usaha'),
            'bmd_nama_barang' => $this->aturanFieldBmd('nama_barang'),
            'bmd_spesifikasi_nama_barang' => $this->aturanFieldBmd('spesifikasi_nama_barang'),
            'bmd_spesifikasi_lain' => $this->aturanFieldBmd('spesifikasi_lain'),
            'bmd_merk_pengarang' => $this->aturanFieldBmd('merk_pengarang'),
        ]);

        // Buang prefix "bmd_" dari tiap kunci supaya sesuai nama kolom
        // tabel rincian_belanja_modal_bmd.
        $data = ['profil_sekolah_id' => $validated['profil_sekolah_id']];
        foreach ($validated as $key => $nilai) {
            if ($key === 'profil_sekolah_id') {
                continue;
            }
            $data[substr($key, 4)] = $nilai;
        }

        foreach ($this->daftarFieldAngkaBmd() as $field) {
            $data[$field] = $data[$field] === null ? null : (int) $data[$field];
        }

        // PostgreSQL menolak string kosong untuk kolom bertipe date.
        foreach (['tanggal_perolehan', 'tanggal_bast', 'tanggal_surat_pernyataan'] as $field) {
            $data[$field] = $data[$field] !== null && trim((string) $data[$field]) !== '' ? $data[$field] : null;
        }

        $data['tahun'] = $this->tahun;
        $data['triwulan'] = $this->triwulan;
        $data = array_merge($data, RincianBelanjaModalBmd::hitungSemuaOtomatis(
            $data['rekening_belanja'],
            $data['jumlah'],
            $data['harga_satuan'],
            $this->triwulan,
            $this->tahun,
        ));

        // PPK OTOMATIS dari Nama Kepala Sekolah Profil Sekolah yang
        // dipilih (jawaban AskUserQuestion 2026-09-22) - bukan lagi
        // input manual bmd_ppk.
        $data['ppk'] = RincianBelanjaModalBmd::ppkOtomatis(
            ProfilSekolah::find($data['profil_sekolah_id'])?->nama_kepala_sekolah
        );

        if ($this->editingBmdId) {
            $baris = RincianBelanjaModalBmd::findOrFail($this->editingBmdId);
            abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
            $baris->update($data);
        } else {
            $data['created_by'] = auth()->id();
            RincianBelanjaModalBmd::create($data);
        }

        $this->showFormBmd = false;
        $this->resetFormBmd();
        $this->dispatch('close-modal', 'rincian-belanja-modal-bmd-form');
        session()->flash('status', 'Data BMD berhasil disimpan.');
    }

    public function konfirmasiHapusBmd(int $id): void
    {
        $baris = RincianBelanjaModalBmd::findOrFail($id);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $this->confirmingDeleteBmdId = $id;
        $this->dispatch('open-modal', 'rincian-belanja-modal-bmd-hapus');
    }

    public function batalHapusBmd(): void
    {
        $this->confirmingDeleteBmdId = null;
        $this->dispatch('close-modal', 'rincian-belanja-modal-bmd-hapus');
    }

    public function hapusBmd(): void
    {
        $baris = RincianBelanjaModalBmd::findOrFail($this->confirmingDeleteBmdId);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $baris->delete();
        $this->confirmingDeleteBmdId = null;
        $this->dispatch('close-modal', 'rincian-belanja-modal-bmd-hapus');
        session()->flash('status', 'Data BMD berhasil dihapus.');
    }

    /**
     * Query flat (bukan per-sekolah) - dipakai HANYA oleh export(), sama
     * seperti pola queryDasar() pada RincianPemeliharaan. Disaring juga
     * oleh kolom "jenis" sesuai tab utama yang aktif.
     */
    protected function queryDasar()
    {
        $query = RincianBelanjaModal::query()
            ->with('profilSekolah')
            ->join('profil_sekolah', 'profil_sekolah.id', '=', 'rincian_belanja_modal.profil_sekolah_id')
            ->where('rincian_belanja_modal.jenis', $this->tabUtama)
            ->where('rincian_belanja_modal.tahun', $this->tahun)
            ->where('rincian_belanja_modal.triwulan', $this->triwulan)
            ->select('rincian_belanja_modal.*');

        if (! $this->bolehKelolaSemua()) {
            $query->where('rincian_belanja_modal.profil_sekolah_id', $this->sekolahSayaId());
        } elseif ($this->filterSekolahId) {
            $query->where('rincian_belanja_modal.profil_sekolah_id', $this->filterSekolahId);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('rincian_belanja_modal.nama_barang', 'like', "%{$this->search}%")
                    ->orWhere('rincian_belanja_modal.kode_upb', 'like', "%{$this->search}%")
                    ->orWhere('profil_sekolah.nama_sekolah', 'like', "%{$this->search}%")
                    ->orWhere('profil_sekolah.npsn', 'like', "%{$this->search}%");
            });
        }

        return $query->orderBy('profil_sekolah.nama_sekolah')->orderBy('rincian_belanja_modal.nama_barang');
    }

    /**
     * Query flat (bukan per-sekolah) untuk tab "BMD" - dipakai HANYA oleh
     * export(), pola SAMA PERSIS seperti queryDasar() di atas untuk tab
     * "jenis", HANYA nama Model/tabel yang diganti (tabel
     * rincian_belanja_modal_bmd TIDAK punya kolom "jenis" jadi tidak ada
     * filter itu di sini).
     */
    protected function queryDasarBmd()
    {
        $query = RincianBelanjaModalBmd::query()
            ->with('profilSekolah')
            ->join('profil_sekolah', 'profil_sekolah.id', '=', 'rincian_belanja_modal_bmd.profil_sekolah_id')
            ->where('rincian_belanja_modal_bmd.tahun', $this->tahun)
            ->where('rincian_belanja_modal_bmd.triwulan', $this->triwulan)
            ->select('rincian_belanja_modal_bmd.*');

        if (! $this->bolehKelolaSemua()) {
            $query->where('rincian_belanja_modal_bmd.profil_sekolah_id', $this->sekolahSayaId());
        } elseif ($this->filterSekolahId) {
            $query->where('rincian_belanja_modal_bmd.profil_sekolah_id', $this->filterSekolahId);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('rincian_belanja_modal_bmd.nama_barang', 'like', "%{$this->search}%")
                    ->orWhere('profil_sekolah.nama_sekolah', 'like', "%{$this->search}%")
                    ->orWhere('profil_sekolah.npsn', 'like', "%{$this->search}%");
            });
        }

        return $query->orderBy('profil_sekolah.nama_sekolah')->orderBy('rincian_belanja_modal_bmd.nama_barang');
    }

    public function export()
    {
        $this->errorExport = null;

        $sekolahId = $this->bolehKelolaSemua() ? $this->filterSekolahId : $this->sekolahSayaId();

        // Tab "BMD" TIDAK LAGI mewajibkan 1 sekolah dipilih dulu (sejak
        // 2026-09-22) - syarat ini sebelumnya HANYA ada karena lembar
        // tanda tangan Kepala Sekolah & Bendahara BOSP pada hasil export
        // BMD hanya berlaku untuk 1 sekolah; lembar tanda tangan itu
        // sendiri sudah DIHAPUS dari hasil export BMD (permintaan user
        // 2026-09-22 "untuk tanda tangan kepala sekolah dan bendahara di
        // hapus pada hasil format excel nya"), jadi syaratnya jadi tidak
        // relevan lagi khusus utk BMD - $sekolah jadi opsional/nullable
        // (RincianBelanjaModalBmdExport sudah mendukung $sekolah = null,
        // lihat judul "REKAP SELURUH SEKOLAH"-nya). Tab KIB (Peralatan &
        // Mesin/Aset Tetap Lainnya) TIDAK diubah - export-nya masih punya
        // lembar tanda tangan sendiri, jadi syarat 1 sekolah TETAP wajib
        // di sana (lihat blok di bawah).
        if ($this->tabUtama === RincianBelanjaModal::TAB_BMD) {
            // Sub-tab "Rekap BMD Tahun Anggaran {tahun}" (permintaan user
            // 2026-09-22) dilayani Export class TERPISAH/mandiri
            // (RincianBelanjaModalBmdRekapExport, kolom "Triwulan" di
            // paling awal) - lihat docblock class itu.
            if ($this->tampilRekapBmd) {
                return Excel::download(
                    new RincianBelanjaModalBmdRekapExport(
                        $this->queryDasarRekapBmd()->get(),
                        $this->tahun,
                    ),
                    'rincian-belanja-modal-bmd-rekap-'.$this->tahun.'.xlsx'
                );
            }

            $sekolah = $sekolahId ? ProfilSekolah::findOrFail($sekolahId) : null;

            return Excel::download(
                new RincianBelanjaModalBmdExport(
                    $this->queryDasarBmd()->get(),
                    $this->triwulan,
                    $this->tahun,
                    $sekolah
                ),
                'rincian-belanja-modal-bmd-triwulan-'.$this->triwulan.'-'.$this->tahun.'.xlsx'
            );
        }

        if (! $sekolahId) {
            $this->errorExport = 'Pilih salah satu sekolah pada filter di atas terlebih dahulu sebelum Export Excel, karena lembar tanda tangan Kepala Sekolah & Bendahara BOSP pada hasil export hanya berlaku untuk 1 sekolah.';

            return null;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);

        $namaFileJenis = $this->tabUtama === RincianBelanjaModal::JENIS_ASET_TETAP_LAINNYA ? 'aset-tetap-lainnya-kib-e' : 'peralatan-mesin-kib-b';

        return Excel::download(
            new RincianBelanjaModalExport(
                $this->queryDasar()->get(),
                $this->tabUtama,
                $this->triwulan,
                $this->tahun,
                $sekolah
            ),
            'rincian-belanja-modal-'.$namaFileJenis.'-triwulan-'.$this->triwulan.'-'.$this->tahun.'.xlsx'
        );
    }

    public function import(): void
    {
        $this->errorImport = null;

        $this->validate([
            'fileImport' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        try {
            $sekolahDiperbolehkan = $this->bolehKelolaSemua()
                ? null
                : $this->sekolahSayaId();

            if ($this->tabUtama === RincianBelanjaModal::TAB_BMD) {
                Excel::import(
                    new RincianBelanjaModalBmdImport($this->tahun, $this->triwulan, auth()->id(), $sekolahDiperbolehkan),
                    $this->fileImport->getRealPath()
                );
            } else {
                if ($sekolahDiperbolehkan) {
                    $this->abortJikaTerkunciVerval($sekolahDiperbolehkan, $this->tahun, $this->triwulan);
                }

                Excel::import(
                    new RincianBelanjaModalImport($this->tabUtama, $this->tahun, $this->triwulan, auth()->id(), $sekolahDiperbolehkan),
                    $this->fileImport->getRealPath()
                );
            }

            $this->fileImport = null;
            session()->flash('status', 'Import Rincian Belanja Modal berhasil.');
        } catch (ValidationException $e) {
            $pesan = [];
            foreach ($e->failures() as $failure) {
                $pesan[] = 'Baris '.$failure->row().': '.implode(', ', $failure->errors());
            }
            $this->errorImport = implode(' | ', $pesan);
        }
    }

    public function render()
    {
        // Tab "BMD" memakai query & tampilan yang SAMA SEKALI TERPISAH
        // (lihat renderBmd() di bawah) - TIDAK menyentuh $this->baris atau
        // query RincianBelanjaModal manapun, supaya 2 tab "jenis" yang
        // sudah berjalan (Peralatan & Mesin KIB B / Aset Tetap Lainnya
        // KIB E) TIDAK TERPENGARUH SAMA SEKALI oleh penambahan tab ini
        // (permintaan user 2026-09-22: "jangan merubah yang sudah
        // berfungsi dan sudah berjalan").
        if ($this->tabUtama === RincianBelanjaModal::TAB_BMD) {
            return $this->tampilRekapBmd ? $this->renderRekapBmd() : $this->renderBmd();
        }

        $query = ProfilSekolah::with(['rincianBelanjaModal' => function ($q) {
            $q->where('jenis', $this->tabUtama)
                ->where('tahun', $this->tahun)
                ->where('triwulan', $this->triwulan)
                ->orderBy('nama_barang');
        }]);

        if (! $this->bolehKelolaSemua()) {
            $query->where('id', $this->sekolahSayaId());
        } elseif ($this->filterSekolahId) {
            $query->where('id', $this->filterSekolahId);
        }

        if ($this->search !== '') {
            $cari = $this->search;
            $query->where(function ($q) use ($cari) {
                $q->where('nama_sekolah', 'like', "%{$cari}%")
                    ->orWhere('npsn', 'like', "%{$cari}%")
                    ->orWhereHas('rincianBelanjaModal', function ($q2) use ($cari) {
                        $q2->where('jenis', $this->tabUtama)
                            ->where('tahun', $this->tahun)
                            ->where('triwulan', $this->triwulan)
                            ->where(function ($q3) use ($cari) {
                                $q3->where('nama_barang', 'like', "%{$cari}%")
                                    ->orWhere('kode_upb', 'like', "%{$cari}%");
                            });
                    });
            });
        }

        // Urutan Negeri dulu baru Swasta (lalu kecamatan & nama sekolah) -
        // pola sama seperti menu Pendataan BOSP lainnya.
        $daftarSekolah = $query
            ->orderByRaw("CASE WHEN status = 'negeri' THEN 0 WHEN status = 'swasta' THEN 1 ELSE 2 END")
            ->orderByRaw('kecamatan IS NULL')
            ->orderBy('kecamatan')
            ->orderBy('nama_sekolah')
            ->get();

        $this->baris = [];

        foreach ($daftarSekolah as $sekolah) {
            foreach ($sekolah->rincianBelanjaModal as $barisData) {
                $this->baris[$barisData->id] = [
                    'kode_upb' => (string) $barisData->kode_upb,
                    'nama_barang' => (string) $barisData->nama_barang,
                    'nama_merk_barang' => (string) $barisData->nama_merk_barang,
                    'volume' => $barisData->volume !== null ? (string) $barisData->volume : '',
                    'satuan' => (string) $barisData->satuan,
                    'harga_satuan' => $barisData->harga_satuan !== null ? (string) $barisData->harga_satuan : '',
                    'asal_usul' => (string) $barisData->asal_usul,
                    'tanggal' => $barisData->tanggal?->format('Y-m-d') ?? '',
                    'keterangan' => (string) $barisData->keterangan,
                ];
            }

            $this->baris[-$sekolah->id] = [
                'kode_upb' => '', 'nama_barang' => '', 'nama_merk_barang' => '',
                'volume' => '', 'satuan' => '', 'harga_satuan' => '',
                'asal_usul' => '', 'tanggal' => '', 'keterangan' => '',
            ];
        }

        $tahunOptions = range(now()->year - 2, now()->year + 1);

        // Jumlah Total Harga UNTUK SELURUH SEKOLAH yang SEDANG DITAMPILKAN
        // (permintaan user 2026-09-16, Part 29: "tambahkan baris Jumlah
        // ... dan kolom Total Harga di total kan"; jawaban AskUserQuestion:
        // "Per sekolah + Total seluruh sekolah", sama seperti Belanja
        // Pemeliharaan Bangunan/PC di Part 28) - dihitung dari koleksi
        // $daftarSekolah yang SAMA dengan yang dirender ke tabel, jadi
        // otomatis ikut scope peran & filter yang sedang aktif, DAN
        // otomatis ikut tab utama ($tabUtama) yang aktif karena relasi
        // rincianBelanjaModal yang di-eager-load di atas sudah disaring
        // per jenis. Total PER SEKOLAH dihitung langsung di view lewat
        // $sekolah->rincianBelanjaModal->sum('total_harga') (koleksi sudah
        // di-eager-load, jadi tidak ada query tambahan).
        $totalHargaKeseluruhan = $daftarSekolah->sum(
            fn (ProfilSekolah $sekolah) => $sekolah->rincianBelanjaModal->sum('total_harga')
        );

        return view('livewire.pendataan-bosp.rincian-belanja-modal.index', [
            'daftarSekolah' => $daftarSekolah,
            'jenisOptions' => RincianBelanjaModal::JENIS_OPTIONS,
            'tabUtamaOptions' => RincianBelanjaModal::TAB_UTAMA_OPTIONS,
            'triwulanOptions' => RincianBelanjaModal::TRIWULAN_OPTIONS,
            'sekolahOptions' => ProfilSekolah::orderBy('nama_sekolah')->get(['id', 'nama_sekolah']),
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            // Kuncian UI - permintaan user 2026-09-23 (round kesepuluh,
            // poin 1) - lihat docblock lengkap App\Livewire\Concerns\
            // MenolakEditJikaTerkunciVerval::terkunciVervalUntukTampilan().
            // SAMA dipakai KEDUA tab (jenis KIB B/E maupun BMD) - kuncian
            // berlaku per triwulan yang sama ($this->triwulan), TIDAK
            // dibedakan per tabUtama.
            'terkunciTriwulanIni' => $this->terkunciVervalUntukTampilan($this->triwulan),
            'tahunOptions' => array_reverse($tahunOptions),
            'totalHargaKeseluruhan' => $totalHargaKeseluruhan,
            // Variabel di bawah ini KHUSUS dipakai oleh modal BMD
            // (resources/views/.../_form-bmd.blade.php, di-@include SELALU
            // dari index.blade.php terlepas dari tab utama yang aktif -
            // lihat renderBmd() di bawah) - TETAP disediakan di sini
            // (murni konstanta, tidak ada query tambahan) supaya modal itu
            // TIDAK error "Undefined variable" saat sedang di tab "jenis".
            'bentukKontrakOptions' => RincianBelanjaModalBmd::BENTUK_KONTRAK_OPTIONS,
            'rekeningBelanjaOptions' => RincianBelanjaModalBmd::REKENING_BELANJA_OPTIONS,
            'programBmd' => RincianBelanjaModalBmd::PROGRAM,
            'kegiatanBmd' => RincianBelanjaModalBmd::KEGIATAN,
            'kodeSubKegiatanBmd' => RincianBelanjaModalBmd::KODE_SUB_KEGIATAN,
            'namaSubKegiatanBmd' => RincianBelanjaModalBmd::NAMA_SUB_KEGIATAN,
            // Peta id Profil Sekolah => Nama Kepala Sekolah, dipakai modal
            // BMD (_form-bmd.blade.php) untuk menampilkan PPK OTOMATIS
            // secara live lewat Alpine.js begitu Sekolah dipilih (jawaban
            // AskUserQuestion 2026-09-22) - disediakan di SINI juga (bukan
            // hanya di renderBmd() di bawah) karena modal itu selalu
            // di-@include terlepas dari tab utama yang aktif.
            'sekolahNamaKepalaSekolah' => ProfilSekolah::pluck('nama_kepala_sekolah', 'id'),
        ]);
    }

    /**
     * render() KHUSUS tab "BMD" - dipanggil dari render() di atas saat
     * $tabUtama === RincianBelanjaModal::TAB_BMD. SENGAJA dipisah jadi
     * method sendiri (bukan ditambahkan sebagai if/else di tengah
     * render() yang sudah ada) supaya query & pembentukan data tab
     * "jenis" di atas TIDAK PERNAH dieksekusi sama sekali saat sedang di
     * tab "BMD", dan sebaliknya - isolasi penuh sesuai permintaan user
     * "jangan merubah yang sudah berfungsi dan sudah berjalan".
     */
    protected function renderBmd()
    {
        $query = ProfilSekolah::with(['rincianBelanjaModalBmd' => function ($q) {
            $q->where('tahun', $this->tahun)
                ->where('triwulan', $this->triwulan)
                ->orderBy('nama_barang');
        }]);

        if (! $this->bolehKelolaSemua()) {
            $query->where('id', $this->sekolahSayaId());
        } elseif ($this->filterSekolahId) {
            $query->where('id', $this->filterSekolahId);
        }

        if ($this->search !== '') {
            $cari = $this->search;
            $query->where(function ($q) use ($cari) {
                $q->where('nama_sekolah', 'like', "%{$cari}%")
                    ->orWhere('npsn', 'like', "%{$cari}%")
                    ->orWhereHas('rincianBelanjaModalBmd', function ($q2) use ($cari) {
                        $q2->where('tahun', $this->tahun)
                            ->where('triwulan', $this->triwulan)
                            ->where('nama_barang', 'like', "%{$cari}%");
                    });
            });
        }

        // Urutan Negeri dulu baru Swasta (lalu kecamatan & nama sekolah) -
        // pola sama seperti render() di atas untuk tab "jenis".
        $daftarSekolah = $query
            ->orderByRaw("CASE WHEN status = 'negeri' THEN 0 WHEN status = 'swasta' THEN 1 ELSE 2 END")
            ->orderByRaw('kecamatan IS NULL')
            ->orderBy('kecamatan')
            ->orderBy('nama_sekolah')
            ->get();

        $this->barisBmd = [];

        $kosongBmd = [
            'bentuk_kontrak' => '', 'atribusi' => '', 'jumlah_termin' => '',
            'nomor_dokumen' => '', 'tanggal_perolehan' => '',
            'penyedia' => '', 'kode_belanja' => '', 'rekening_belanja' => '',
            'jenis_aset' => '', 'sub_sub_rincian_objek' => '', 'jumlah' => '',
            'satuan' => '', 'harga_satuan' => '', 'total' => '0',
            'no_bast' => '', 'tanggal_bast' => '',
            'nomor_surat_pernyataan' => '', 'tanggal_surat_pernyataan' => '',
            'nama_pengurus_barang' => '', 'jabatan' => '', 'pejabat_penata_usaha' => '',
            'nama_barang' => '', 'spesifikasi_nama_barang' => '', 'spesifikasi_lain' => '',
            'merk_pengarang' => '',
        ];

        foreach ($daftarSekolah as $sekolah) {
            foreach ($sekolah->rincianBelanjaModalBmd as $barisData) {
                $this->barisBmd[$barisData->id] = [
                    'bentuk_kontrak' => (string) $barisData->bentuk_kontrak,
                    'atribusi' => (string) $barisData->atribusi,
                    'jumlah_termin' => (string) $barisData->jumlah_termin,
                    'nomor_dokumen' => (string) $barisData->nomor_dokumen,
                    'tanggal_perolehan' => $barisData->tanggal_perolehan?->format('Y-m-d') ?? '',
                    'penyedia' => (string) $barisData->penyedia,
                    'kode_belanja' => (string) $barisData->kode_belanja,
                    'rekening_belanja' => (string) $barisData->rekening_belanja,
                    'jenis_aset' => (string) $barisData->jenis_aset,
                    'sub_sub_rincian_objek' => (string) $barisData->sub_sub_rincian_objek,
                    'jumlah' => $barisData->jumlah !== null ? (string) $barisData->jumlah : '',
                    'satuan' => (string) $barisData->satuan,
                    'harga_satuan' => $barisData->harga_satuan !== null ? (string) $barisData->harga_satuan : '',
                    'total' => $barisData->total !== null ? (string) $barisData->total : '0',
                    'no_bast' => (string) $barisData->no_bast,
                    'tanggal_bast' => $barisData->tanggal_bast?->format('Y-m-d') ?? '',
                    'keterangan_bos' => (string) $barisData->keterangan_bos,
                    'nomor_surat_pernyataan' => (string) $barisData->nomor_surat_pernyataan,
                    'tanggal_surat_pernyataan' => $barisData->tanggal_surat_pernyataan?->format('Y-m-d') ?? '',
                    'nama_pengurus_barang' => (string) $barisData->nama_pengurus_barang,
                    'jabatan' => (string) $barisData->jabatan,
                    'pejabat_penata_usaha' => (string) $barisData->pejabat_penata_usaha,
                    'nama_barang' => (string) $barisData->nama_barang,
                    'spesifikasi_nama_barang' => (string) $barisData->spesifikasi_nama_barang,
                    'spesifikasi_lain' => (string) $barisData->spesifikasi_lain,
                    'merk_pengarang' => (string) $barisData->merk_pengarang,
                    'keterangan_bosp' => (string) $barisData->keterangan_bosp,
                ];
            }

            $this->barisBmd[-$sekolah->id] = $kosongBmd + [
                'keterangan_bos' => RincianBelanjaModalBmd::keteranganBosOtomatis($this->triwulan, $this->tahun),
                'keterangan_bosp' => RincianBelanjaModalBmd::keteranganBospOtomatis($this->triwulan, $this->tahun),
            ];
        }

        $tahunOptions = range(now()->year - 2, now()->year + 1);

        // Jumlah Total UNTUK SELURUH SEKOLAH yang SEDANG DITAMPILKAN - pola
        // sama seperti $totalHargaKeseluruhan pada render() di atas untuk
        // tab "jenis".
        $totalBmdKeseluruhan = $daftarSekolah->sum(
            fn (ProfilSekolah $sekolah) => $sekolah->rincianBelanjaModalBmd->sum('total')
        );

        return view('livewire.pendataan-bosp.rincian-belanja-modal.index', [
            'daftarSekolah' => $daftarSekolah,
            'jenisOptions' => RincianBelanjaModal::JENIS_OPTIONS,
            'tabUtamaOptions' => RincianBelanjaModal::TAB_UTAMA_OPTIONS,
            'triwulanOptions' => RincianBelanjaModalBmd::TRIWULAN_OPTIONS,
            'sekolahOptions' => ProfilSekolah::orderBy('nama_sekolah')->get(['id', 'nama_sekolah']),
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            // Kuncian UI - permintaan user 2026-09-23 (round kesepuluh,
            // poin 1) - lihat docblock lengkap App\Livewire\Concerns\
            // MenolakEditJikaTerkunciVerval::terkunciVervalUntukTampilan().
            // SAMA dipakai KEDUA tab (jenis KIB B/E maupun BMD) - kuncian
            // berlaku per triwulan yang sama ($this->triwulan), TIDAK
            // dibedakan per tabUtama.
            'terkunciTriwulanIni' => $this->terkunciVervalUntukTampilan($this->triwulan),
            'tahunOptions' => array_reverse($tahunOptions),
            'totalHargaKeseluruhan' => 0,
            'bentukKontrakOptions' => RincianBelanjaModalBmd::BENTUK_KONTRAK_OPTIONS,
            'rekeningBelanjaOptions' => RincianBelanjaModalBmd::REKENING_BELANJA_OPTIONS,
            'totalBmdKeseluruhan' => $totalBmdKeseluruhan,
            'programBmd' => RincianBelanjaModalBmd::PROGRAM,
            'kegiatanBmd' => RincianBelanjaModalBmd::KEGIATAN,
            'kodeSubKegiatanBmd' => RincianBelanjaModalBmd::KODE_SUB_KEGIATAN,
            'namaSubKegiatanBmd' => RincianBelanjaModalBmd::NAMA_SUB_KEGIATAN,
            'sekolahNamaKepalaSekolah' => ProfilSekolah::pluck('nama_kepala_sekolah', 'id'),
        ]);
    }

    /**
     * render() KHUSUS sub-tab "Rekap BMD Tahun Anggaran {tahun}" -
     * dipanggil dari render() di atas saat $tabUtama === TAB_BMD DAN
     * $tampilRekapBmd true (lihat komentar properti $tampilRekapBmd).
     * SENGAJA dipisah jadi method sendiri (bukan menambah cabang di
     * dalam renderBmd()) supaya renderBmd() (per-Triwulan, bisa diedit)
     * TIDAK PERNAH tersentuh sama sekali oleh tambahan sub-tab ini -
     * isolasi penuh, pola sama seperti alasan renderBmd() dipisah dari
     * render() tab "jenis" di atas.
     *
     * BEDA UTAMA dengan renderBmd(): (1) query FLAT langsung terhadap
     * RincianBelanjaModalBmd (bukan dikelompokkan per-ProfilSekolah lewat
     * with()), karena tampilannya juga flat (tidak ada tbody-per-sekolah
     * yang bisa dibuka/tutup - lihat _tabel-rekap-bmd.blade.php); (2)
     * TIDAK difilter per-Triwulan - mencakup TW-1 s.d. TW-4 tahun
     * berjalan sekaligus (jawaban AskUserQuestion 2026-09-22: "data nya
     * otomatis diambil dari data BMD TW-1 sampai BMD TW-4"); (3) urutan
     * Triwulan -> Negeri/Swasta -> Nama Sekolah (A-Z) -> Tgl. Perolehan
     * (jawaban AskUserQuestion 2026-09-22 "Triwulan -> Negeri/Swasta ->
     * Nama Sekolah (A-Z) -> Tgl. Perolehan (Recommended)"); (4) HANYA
     * lihat & Export - TIDAK ada $barisBmd/placeholder/form apapun yang
     * dibentuk di sini (jawaban AskUserQuestion "Hanya tampil & export").
     */
    /**
     * Query flat untuk sub-tab "Rekap BMD Tahun Anggaran {tahun}" -
     * dipakai BERSAMA oleh renderRekapBmd() (tampilan) & export() (Excel)
     * supaya urutan baris SELALU identik di kedua tempat (pola sama
     * seperti queryDasar()/queryDasarBmd() di atas, HANYA saja di sini
     * juga dipakai langsung oleh render() - BUKAN cuma export() - karena
     * tampilan Rekap memang read-only/flat, sama persis dengan bentuk
     * query utk export).
     */
    protected function queryDasarRekapBmd()
    {
        $query = RincianBelanjaModalBmd::query()
            ->with('profilSekolah')
            ->join('profil_sekolah', 'profil_sekolah.id', '=', 'rincian_belanja_modal_bmd.profil_sekolah_id')
            ->where('rincian_belanja_modal_bmd.tahun', $this->tahun)
            ->select('rincian_belanja_modal_bmd.*');

        if (! $this->bolehKelolaSemua()) {
            $query->where('rincian_belanja_modal_bmd.profil_sekolah_id', $this->sekolahSayaId());
        } elseif ($this->filterSekolahId) {
            $query->where('rincian_belanja_modal_bmd.profil_sekolah_id', $this->filterSekolahId);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('rincian_belanja_modal_bmd.nama_barang', 'like', "%{$this->search}%")
                    ->orWhere('profil_sekolah.nama_sekolah', 'like', "%{$this->search}%")
                    ->orWhere('profil_sekolah.npsn', 'like', "%{$this->search}%");
            });
        }

        // Urutan sesuai jawaban AskUserQuestion 2026-09-22: Triwulan ->
        // Negeri/Swasta -> Nama Sekolah (A-Z) -> Tgl. Perolehan.
        return $query
            ->orderBy('rincian_belanja_modal_bmd.triwulan')
            ->orderByRaw("CASE WHEN profil_sekolah.status = 'negeri' THEN 0 WHEN profil_sekolah.status = 'swasta' THEN 1 ELSE 2 END")
            ->orderBy('profil_sekolah.nama_sekolah')
            ->orderBy('rincian_belanja_modal_bmd.tanggal_perolehan');
    }

    protected function renderRekapBmd()
    {
        $daftarRekapBmd = $this->queryDasarRekapBmd()->get();

        $tahunOptions = range(now()->year - 2, now()->year + 1);

        return view('livewire.pendataan-bosp.rincian-belanja-modal.index', [
            'daftarSekolah' => collect(),
            'daftarRekapBmd' => $daftarRekapBmd,
            'jenisOptions' => RincianBelanjaModal::JENIS_OPTIONS,
            'tabUtamaOptions' => RincianBelanjaModal::TAB_UTAMA_OPTIONS,
            'triwulanOptions' => RincianBelanjaModalBmd::TRIWULAN_OPTIONS,
            'sekolahOptions' => ProfilSekolah::orderBy('nama_sekolah')->get(['id', 'nama_sekolah']),
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            // Kuncian UI - permintaan user 2026-09-23 (round kesepuluh,
            // poin 1) - lihat docblock lengkap App\Livewire\Concerns\
            // MenolakEditJikaTerkunciVerval::terkunciVervalUntukTampilan().
            // SAMA dipakai KEDUA tab (jenis KIB B/E maupun BMD) - kuncian
            // berlaku per triwulan yang sama ($this->triwulan), TIDAK
            // dibedakan per tabUtama.
            'terkunciTriwulanIni' => $this->terkunciVervalUntukTampilan($this->triwulan),
            'tahunOptions' => array_reverse($tahunOptions),
            'totalHargaKeseluruhan' => 0,
            'bentukKontrakOptions' => RincianBelanjaModalBmd::BENTUK_KONTRAK_OPTIONS,
            'rekeningBelanjaOptions' => RincianBelanjaModalBmd::REKENING_BELANJA_OPTIONS,
            'totalBmdKeseluruhan' => 0,
            'programBmd' => RincianBelanjaModalBmd::PROGRAM,
            'kegiatanBmd' => RincianBelanjaModalBmd::KEGIATAN,
            'kodeSubKegiatanBmd' => RincianBelanjaModalBmd::KODE_SUB_KEGIATAN,
            'namaSubKegiatanBmd' => RincianBelanjaModalBmd::NAMA_SUB_KEGIATAN,
            'sekolahNamaKepalaSekolah' => ProfilSekolah::pluck('nama_kepala_sekolah', 'id'),
        ]);
    }
}
