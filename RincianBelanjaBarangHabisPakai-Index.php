<?php

namespace App\Livewire\PendataanBosp\RincianBelanjaBarangHabisPakai;

use App\Exports\RincianBelanjaBarangHabisPakaiExport;
use App\Imports\RincianBelanjaBarangHabisPakaiImport;
use App\Livewire\Concerns\HasZoomTampilan;
use App\Models\ProfilSekolah;
use App\Models\RincianBelanjaBarangHabisPakai;
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
 * Rincian Belanja Barang Habis Pakai - Pendataan BOSP (permintaan user
 * 2026-09-11, Part 21 poin 3).
 *
 * Struktur & pola KELAS INI disalin PERSIS dari
 * BelanjaPemeliharaanBangunan\Index (field sama: Kode UPB, Nama Barang,
 * Nama Merk Barang, Volume, Satuan, Harga Satuan, Total Harga, Asal
 * Usul, Tanggal, Keterangan), TAPI SATU LAPIS tab saja (4 tab Triwulan
 * LANGSUNG, TIDAK ADA tab utama tambahan/kolom "jenis" - menu ini hanya
 * SATU jenis) - pola SATU LAPIS tab sama seperti BiayaPendaftaranLomba
 * (Part 19).
 *
 * Sesuai jawaban AskUserQuestion 2026-09-11: 1 sekolah bisa punya BANYAK
 * baris per triwulan (banyak barang berbeda), dan Nama Barang BOLEH
 * DUPLIKAT - TIDAK ADA logic "xxxSudahDipakai()" untuk field ini.
 *
 * NPSN & Nama Sekolah SELALU tampilan read-only (otomatis dari relasi
 * profilSekolah), Total Harga SELALU hasil rumus otomatis (Volume x
 * Harga Satuan - lihat RincianBelanjaBarangHabisPakai::hitungTotalHarga()),
 * keduanya TIDAK BISA diedit dari jalur manapun.
 *
 * - Superadmin: melihat & mengelola data semua sekolah, bisa memilih
 *   sekolah manapun saat menambah/mengedit data, dan memfilter tabel per
 *   sekolah.
 * - Admin BOSP: hanya melihat & mengelola data sekolahnya sendiri (field
 *   Sekolah otomatis terkunci ke sekolahnya).
 */
#[Layout('layouts.app')]
#[Title('Rincian Belanja Barang Habis Pakai')]
class Index extends Component
{
    use HasZoomTampilan;
    use WithFileUploads;

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

    public function pindahTab(int $triwulan): void
    {
        $this->triwulan = $triwulan;
    }

    /**
     * Menangkap perubahan pada kotak input LANGSUNG di tabel (property
     * "baris.{rowId}.{field}"). $rowId NEGATIF berarti baris PLACEHOLDER
     * (lihat komentar kelas & render()) - ditangani terpisah oleh
     * updatedBarisBaru() karena belum ada baris sungguhan di database.
     */
    public function updated(string $name, mixed $value): void
    {
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

        $baris = RincianBelanjaBarangHabisPakai::find($rowId);

        if (! $baris) {
            // Baris sudah dihapus (mis. dari tab/sesi lain) - abaikan saja,
            // render() berikutnya akan menghilangkan baris ini dari tabel.
            return;
        }

        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

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
            $data['total_harga'] = RincianBelanjaBarangHabisPakai::hitungTotalHarga($volumeBaru, $hargaBaru);
        }

        $baris->update($data);
    }

    /**
     * Menangani input langsung pada BARIS PLACEHOLDER (baris kosong
     * siap-isi yang SELALU ditampilkan di akhir daftar tiap sekolah).
     * Kuncinya NEGATIF ($idPlaceholder = -$sekolahId). Baris ini BELUM
     * ADA di database - begitu SALAH SATU kotaknya diisi, baris baru
     * langsung DIBUAT untuk sekolah+tahun+triwulan ini, lalu
     * placeholder-nya sendiri otomatis kosong lagi pada render()
     * berikutnya - TIDAK ADA pengecekan duplikat apapun pada Nama Barang
     * (sesuai jawaban AskUserQuestion "boleh duplikat").
     */
    protected function updatedBarisBaru(string $name, int $idPlaceholder, string $field, mixed $value): void
    {
        $sekolahId = abs($idPlaceholder);

        abort_unless($this->bolehKelola($sekolahId), 403);

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

        $data['total_harga'] = RincianBelanjaBarangHabisPakai::hitungTotalHarga(
            $field === 'volume' ? $data['volume'] : null,
            $field === 'harga_satuan' ? $data['harga_satuan'] : null,
        );

        $data['profil_sekolah_id'] = $sekolahId;
        $data['tahun'] = $this->tahun;
        $data['triwulan'] = $this->triwulan;
        $data['created_by'] = auth()->id();

        RincianBelanjaBarangHabisPakai::create($data);

        $this->revisiBaris[$idPlaceholder] = ($this->revisiBaris[$idPlaceholder] ?? 0) + 1;
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
        $this->dispatch('open-modal', 'rincian-belanja-barang-habis-pakai-form');
    }

    public function edit(int $id): void
    {
        $baris = RincianBelanjaBarangHabisPakai::findOrFail($id);

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
        $this->dispatch('open-modal', 'rincian-belanja-barang-habis-pakai-form');
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
        $this->dispatch('close-modal', 'rincian-belanja-barang-habis-pakai-form');
    }

    public function simpan(): void
    {
        if (! $this->bolehKelolaSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        abort_unless($this->profil_sekolah_id && $this->bolehKelola($this->profil_sekolah_id), 403);

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

        $validated['total_harga'] = RincianBelanjaBarangHabisPakai::hitungTotalHarga($validated['volume'], $validated['harga_satuan']);
        $validated['tahun'] = $this->tahun;
        $validated['triwulan'] = $this->triwulan;

        if ($this->editingId) {
            $baris = RincianBelanjaBarangHabisPakai::findOrFail($this->editingId);
            abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
            $baris->update($validated);
        } else {
            $validated['created_by'] = auth()->id();
            RincianBelanjaBarangHabisPakai::create($validated);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'rincian-belanja-barang-habis-pakai-form');
        session()->flash('status', 'Data Rincian Belanja Barang Habis Pakai berhasil disimpan.');
    }

    public function konfirmasiHapus(int $id): void
    {
        $baris = RincianBelanjaBarangHabisPakai::findOrFail($id);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $this->confirmingDeleteId = $id;
        $this->dispatch('open-modal', 'rincian-belanja-barang-habis-pakai-hapus');
    }

    public function batalHapus(): void
    {
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'rincian-belanja-barang-habis-pakai-hapus');
    }

    public function hapus(): void
    {
        $baris = RincianBelanjaBarangHabisPakai::findOrFail($this->confirmingDeleteId);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $baris->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'rincian-belanja-barang-habis-pakai-hapus');
        session()->flash('status', 'Data Rincian Belanja Barang Habis Pakai berhasil dihapus.');
    }

    /**
     * Query flat (bukan per-sekolah) - dipakai HANYA oleh export(), sama
     * seperti pola queryDasar() pada menu lain.
     */
    protected function queryDasar()
    {
        $query = RincianBelanjaBarangHabisPakai::query()
            ->with('profilSekolah')
            ->join('profil_sekolah', 'profil_sekolah.id', '=', 'rincian_belanja_barang_habis_pakai.profil_sekolah_id')
            ->where('rincian_belanja_barang_habis_pakai.tahun', $this->tahun)
            ->where('rincian_belanja_barang_habis_pakai.triwulan', $this->triwulan)
            ->select('rincian_belanja_barang_habis_pakai.*');

        if (! $this->bolehKelolaSemua()) {
            $query->where('rincian_belanja_barang_habis_pakai.profil_sekolah_id', $this->sekolahSayaId());
        } elseif ($this->filterSekolahId) {
            $query->where('rincian_belanja_barang_habis_pakai.profil_sekolah_id', $this->filterSekolahId);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('rincian_belanja_barang_habis_pakai.nama_barang', 'like', "%{$this->search}%")
                    ->orWhere('rincian_belanja_barang_habis_pakai.kode_upb', 'like', "%{$this->search}%")
                    ->orWhere('profil_sekolah.nama_sekolah', 'like', "%{$this->search}%")
                    ->orWhere('profil_sekolah.npsn', 'like', "%{$this->search}%");
            });
        }

        return $query->orderBy('profil_sekolah.nama_sekolah')->orderBy('rincian_belanja_barang_habis_pakai.nama_barang');
    }

    public function export()
    {
        $this->errorExport = null;

        $sekolahId = $this->bolehKelolaSemua() ? $this->filterSekolahId : $this->sekolahSayaId();

        if (! $sekolahId) {
            $this->errorExport = 'Pilih salah satu sekolah pada filter di atas terlebih dahulu sebelum Export Excel, karena lembar tanda tangan Kepala Sekolah & Bendahara BOSP pada hasil export hanya berlaku untuk 1 sekolah.';

            return null;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);

        return Excel::download(
            new RincianBelanjaBarangHabisPakaiExport(
                $this->queryDasar()->get(),
                $this->triwulan,
                $this->tahun,
                $sekolah
            ),
            'rincian-belanja-barang-habis-pakai-triwulan-'.$this->triwulan.'-'.$this->tahun.'.xlsx'
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

            Excel::import(
                new RincianBelanjaBarangHabisPakaiImport($this->tahun, $this->triwulan, auth()->id(), $sekolahDiperbolehkan),
                $this->fileImport->getRealPath()
            );

            $this->fileImport = null;
            session()->flash('status', 'Import Rincian Belanja Barang Habis Pakai berhasil.');
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
        $query = ProfilSekolah::with(['rincianBelanjaBarangHabisPakai' => function ($q) {
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
                    ->orWhereHas('rincianBelanjaBarangHabisPakai', function ($q2) use ($cari) {
                        $q2->where('tahun', $this->tahun)
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
            foreach ($sekolah->rincianBelanjaBarangHabisPakai as $barisData) {
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
        // "Per sekolah + Total seluruh sekolah", sama seperti menu-menu
        // lain di Part 22/27/28) - dihitung dari koleksi $daftarSekolah
        // yang SAMA dengan yang dirender ke tabel, jadi otomatis ikut
        // scope peran & filter yang sedang aktif. Menu ini SATU tab saja
        // (tanpa "jenis") jadi TIDAK PERLU label tab-conditional. Total
        // PER SEKOLAH dihitung langsung di view lewat
        // $sekolah->rincianBelanjaBarangHabisPakai->sum('total_harga')
        // (koleksi sudah di-eager-load, jadi tidak ada query tambahan).
        $totalHargaKeseluruhan = $daftarSekolah->sum(
            fn (ProfilSekolah $sekolah) => $sekolah->rincianBelanjaBarangHabisPakai->sum('total_harga')
        );

        return view('livewire.pendataan-bosp.rincian-belanja-barang-habis-pakai.index', [
            'daftarSekolah' => $daftarSekolah,
            'triwulanOptions' => RincianBelanjaBarangHabisPakai::TRIWULAN_OPTIONS,
            'sekolahOptions' => ProfilSekolah::orderBy('nama_sekolah')->get(['id', 'nama_sekolah']),
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            'tahunOptions' => array_reverse($tahunOptions),
            'totalHargaKeseluruhan' => $totalHargaKeseluruhan,
        ]);
    }
}
