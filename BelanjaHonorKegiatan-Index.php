<?php

namespace App\Livewire\PendataanBosp\BelanjaHonorKegiatan;

use App\Exports\BelanjaHonorKegiatanExport;
use App\Imports\BelanjaHonorKegiatanImport;
use App\Livewire\Concerns\HasZoomTampilan;
use App\Models\BelanjaHonorKegiatan;
use App\Models\ProfilSekolah;
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
 * Belanja Honor Kegiatan & Makan Minum Kegiatan - Pendataan BOSP
 * (2026-09-11).
 *
 * Struktur & pola KELAS INI awalnya (Part 19) disalin PERSIS dari
 * LanggananDayaJasa\Index (tabel diringkas per sekolah + baris
 * placeholder, pelajaran dari Part 16), dengan 1 tab utama saja (4 Tab
 * Triwulan, SATU LAPIS). Permintaan user 2026-09-11 (Part 20) menambah
 * 2 tab utama baru ("Belanja Makan & Minum" & "Belanja Perjalanan
 * Dinas") dengan field yang PERSIS SAMA - kelas ini di-retrofit
 * mengikuti pola 2-LAPIS TAB persis seperti BelanjaPemeliharaanBangunan\
 * Index (Part 17): tab UTAMA ($tabUtama, di atas) + tab Triwulan (di
 * bawahnya, TIDAK direset saat pindah tab utama).
 *
 * Sesuai jawaban AskUserQuestion 2026-09-11: 1 sekolah bisa punya BANYAK
 * baris ("Banyak baris per sekolah"), dan Uraian BOLEH DUPLIKAT - jadi
 * TIDAK ADA logic "xxxSudahDipakai()" untuk field ini. Sesuai jawaban
 * AskUserQuestion 2026-09-11 (Part 20): SATU tabel dengan kolom "jenis"
 * sebagai pembeda 3 tab utama, bukan 3 tabel terpisah.
 *
 * NPSN & Nama Sekolah SELALU tampilan read-only (otomatis dari relasi
 * profilSekolah), Jumlah SELALU hasil rumus otomatis (Volume x Tarif
 * Harga - lihat BelanjaHonorKegiatan::hitungJumlah()).
 *
 * - Superadmin: melihat & mengelola data semua sekolah, bisa memilih
 *   sekolah manapun saat menambah/mengedit data, dan memfilter tabel per
 *   sekolah.
 * - Admin BOSP: hanya melihat & mengelola data sekolahnya sendiri (field
 *   Sekolah otomatis terkunci ke sekolahnya).
 */
#[Layout('layouts.app')]
#[Title('Belanja Honor Kegiatan & Makan Minum Kegiatan & Perjalanan Dinas')]
class Index extends Component
{
    use HasZoomTampilan;
    use WithFileUploads;

    public int $tahun;

    #[Url(as: 'tab')]
    public string $tabUtama = BelanjaHonorKegiatan::JENIS_HONOR_KEGIATAN;

    #[Url(as: 'triwulan')]
    public int $triwulan = 1;

    public string $search = '';

    public ?int $filterSekolahId = null;

    public ?int $editingId = null;

    public int $formInstance = 0;

    /**
     * @var array<int, array<string, string>>
     */
    public array $baris = [];

    /**
     * @var array<int, int>
     */
    public array $revisiBaris = [];

    public ?int $profil_sekolah_id = null;

    public string $uraian = '';

    public string $volume = '';

    public string $satuan = '';

    public string $tarif_harga = '';

    public string $tanggal = '';

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

    protected function daftarFieldEditable(): array
    {
        return ['uraian', 'volume', 'satuan', 'tarif_harga', 'tanggal'];
    }

    /**
     * Pindah tab UTAMA (Honor Kegiatan / Belanja Makan & Minum / Belanja
     * Perjalanan Dinas). Triwulan aktif TIDAK direset - kalau user
     * sedang di TW-2 lalu pindah tab utama, tetap di TW-2 pada tab utama
     * yang baru.
     */
    public function pindahTabUtama(string $jenis): void
    {
        if (! array_key_exists($jenis, BelanjaHonorKegiatan::JENIS_OPTIONS)) {
            return;
        }

        $this->tabUtama = $jenis;
    }

    public function pindahTab(int $triwulan): void
    {
        $this->triwulan = $triwulan;
    }

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

        $baris = BelanjaHonorKegiatan::find($rowId);

        if (! $baris) {
            return;
        }

        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $nilai = $value === '' || $value === null ? null : $value;

        $rules = match ($field) {
            'uraian' => ['required', 'string', 'max:255'],
            'volume' => ['nullable', 'integer', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'tarif_harga' => ['nullable', 'integer', 'min:0'],
            'tanggal' => ['nullable', 'date'],
            default => [],
        };

        $validator = Validator::make(['nilai' => $nilai], ['nilai' => $rules], [], ['nilai' => 'Nilai']);

        if ($validator->fails()) {
            $this->addError($name, $validator->errors()->first('nilai'));
            $this->revisiBaris[$rowId] = ($this->revisiBaris[$rowId] ?? 0) + 1;

            return;
        }

        $data = [$field => in_array($field, ['volume', 'tarif_harga'], true) && $nilai !== null ? (int) $nilai : $nilai];

        if (in_array($field, ['volume', 'tarif_harga'], true)) {
            $volumeBaru = $field === 'volume' ? $data['volume'] : $baris->volume;
            $tarifBaru = $field === 'tarif_harga' ? $data['tarif_harga'] : $baris->tarif_harga;
            $data['jumlah'] = BelanjaHonorKegiatan::hitungJumlah($volumeBaru, $tarifBaru);
        }

        $baris->update($data);
    }

    protected function updatedBarisBaru(string $name, int $idPlaceholder, string $field, mixed $value): void
    {
        $sekolahId = abs($idPlaceholder);

        abort_unless($this->bolehKelola($sekolahId), 403);

        $nilai = $value === '' || $value === null ? null : $value;

        if ($nilai === null) {
            return;
        }

        $rules = match ($field) {
            'uraian' => ['nullable', 'string', 'max:255'],
            'volume' => ['nullable', 'integer', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'tarif_harga' => ['nullable', 'integer', 'min:0'],
            'tanggal' => ['nullable', 'date'],
            default => [],
        };

        $validator = Validator::make(['nilai' => $nilai], ['nilai' => $rules], [], ['nilai' => 'Nilai']);

        if ($validator->fails()) {
            $this->addError($name, $validator->errors()->first('nilai'));
            $this->revisiBaris[$idPlaceholder] = ($this->revisiBaris[$idPlaceholder] ?? 0) + 1;

            return;
        }

        $data = [$field => in_array($field, ['volume', 'tarif_harga'], true) ? (int) $nilai : $nilai];

        $data['jumlah'] = BelanjaHonorKegiatan::hitungJumlah(
            $field === 'volume' ? $data['volume'] : null,
            $field === 'tarif_harga' ? $data['tarif_harga'] : null,
        );

        $data['profil_sekolah_id'] = $sekolahId;
        $data['jenis'] = $this->tabUtama;
        $data['tahun'] = $this->tahun;
        $data['triwulan'] = $this->triwulan;
        $data['created_by'] = auth()->id();

        BelanjaHonorKegiatan::create($data);

        $this->revisiBaris[$idPlaceholder] = ($this->revisiBaris[$idPlaceholder] ?? 0) + 1;
    }

    public function tambah(): void
    {
        $this->resetForm();
        $this->formInstance++;

        if (! $this->bolehKelolaSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        $this->showForm = true;
        $this->dispatch('open-modal', 'belanja-honor-kegiatan-form');
    }

    public function edit(int $id): void
    {
        $baris = BelanjaHonorKegiatan::findOrFail($id);

        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $this->formInstance++;
        $this->editingId = $baris->id;
        $this->profil_sekolah_id = $baris->profil_sekolah_id;
        $this->uraian = (string) $baris->uraian;
        $this->volume = $baris->volume !== null ? (string) $baris->volume : '';
        $this->satuan = (string) $baris->satuan;
        $this->tarif_harga = $baris->tarif_harga !== null ? (string) $baris->tarif_harga : '';
        $this->tanggal = $baris->tanggal?->format('Y-m-d') ?? '';
        $this->showForm = true;
        $this->dispatch('open-modal', 'belanja-honor-kegiatan-form');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'profil_sekolah_id', 'uraian',
            'volume', 'satuan', 'tarif_harga', 'tanggal',
        ]);
        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'belanja-honor-kegiatan-form');
    }

    public function simpan(): void
    {
        if (! $this->bolehKelolaSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        abort_unless($this->profil_sekolah_id && $this->bolehKelola($this->profil_sekolah_id), 403);

        $validated = $this->validate([
            'profil_sekolah_id' => ['required', Rule::exists('profil_sekolah', 'id')],
            'uraian' => ['required', 'string', 'max:255'],
            'volume' => ['nullable', 'integer', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'tarif_harga' => ['nullable', 'integer', 'min:0'],
            'tanggal' => ['nullable', 'date'],
        ]);

        foreach (['volume', 'tarif_harga'] as $field) {
            $validated[$field] = $validated[$field] === null ? null : (int) $validated[$field];
        }

        $validated['tanggal'] = $validated['tanggal'] !== null && trim((string) $validated['tanggal']) !== ''
            ? $validated['tanggal']
            : null;

        $validated['jumlah'] = BelanjaHonorKegiatan::hitungJumlah($validated['volume'], $validated['tarif_harga']);
        $validated['jenis'] = $this->tabUtama;
        $validated['tahun'] = $this->tahun;
        $validated['triwulan'] = $this->triwulan;

        if ($this->editingId) {
            $baris = BelanjaHonorKegiatan::findOrFail($this->editingId);
            abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
            $baris->update($validated);
        } else {
            $validated['created_by'] = auth()->id();
            BelanjaHonorKegiatan::create($validated);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'belanja-honor-kegiatan-form');
        session()->flash('status', 'Data Belanja Honor Kegiatan berhasil disimpan.');
    }

    public function konfirmasiHapus(int $id): void
    {
        $baris = BelanjaHonorKegiatan::findOrFail($id);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $this->confirmingDeleteId = $id;
        $this->dispatch('open-modal', 'belanja-honor-kegiatan-hapus');
    }

    public function batalHapus(): void
    {
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'belanja-honor-kegiatan-hapus');
    }

    public function hapus(): void
    {
        $baris = BelanjaHonorKegiatan::findOrFail($this->confirmingDeleteId);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $baris->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'belanja-honor-kegiatan-hapus');
        session()->flash('status', 'Data Belanja Honor Kegiatan berhasil dihapus.');
    }

    protected function queryDasar()
    {
        $query = BelanjaHonorKegiatan::query()
            ->with('profilSekolah')
            ->join('profil_sekolah', 'profil_sekolah.id', '=', 'belanja_honor_kegiatan.profil_sekolah_id')
            ->where('belanja_honor_kegiatan.jenis', $this->tabUtama)
            ->where('belanja_honor_kegiatan.tahun', $this->tahun)
            ->where('belanja_honor_kegiatan.triwulan', $this->triwulan)
            ->select('belanja_honor_kegiatan.*');

        if (! $this->bolehKelolaSemua()) {
            $query->where('belanja_honor_kegiatan.profil_sekolah_id', $this->sekolahSayaId());
        } elseif ($this->filterSekolahId) {
            $query->where('belanja_honor_kegiatan.profil_sekolah_id', $this->filterSekolahId);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('belanja_honor_kegiatan.uraian', 'like', "%{$this->search}%")
                    ->orWhere('profil_sekolah.nama_sekolah', 'like', "%{$this->search}%")
                    ->orWhere('profil_sekolah.npsn', 'like', "%{$this->search}%");
            });
        }

        return $query->orderBy('profil_sekolah.nama_sekolah')->orderBy('belanja_honor_kegiatan.uraian');
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

        // Nama file "belanja-honor-kegiatan-..." untuk jenis
        // JENIS_HONOR_KEGIATAN SENGAJA dipertahankan PERSIS sama seperti
        // sebelum Part 20 (kompatibilitas dengan file yang sudah pernah
        // diexport user).
        $namaFileJenis = match ($this->tabUtama) {
            BelanjaHonorKegiatan::JENIS_MAKAN_MINUM => 'belanja-makan-minum-kegiatan',
            BelanjaHonorKegiatan::JENIS_PERJALANAN_DINAS => 'belanja-perjalanan-dinas',
            default => 'belanja-honor-kegiatan',
        };

        return Excel::download(
            new BelanjaHonorKegiatanExport(
                $this->queryDasar()->get(),
                $this->tabUtama,
                $this->triwulan,
                $this->tahun,
                $sekolah
            ),
            $namaFileJenis.'-triwulan-'.$this->triwulan.'-'.$this->tahun.'.xlsx'
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
                new BelanjaHonorKegiatanImport($this->tabUtama, $this->tahun, $this->triwulan, auth()->id(), $sekolahDiperbolehkan),
                $this->fileImport->getRealPath()
            );

            $this->fileImport = null;
            session()->flash('status', 'Import Belanja Honor Kegiatan berhasil.');
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
        $query = ProfilSekolah::with(['belanjaHonorKegiatan' => function ($q) {
            $q->where('jenis', $this->tabUtama)
                ->where('tahun', $this->tahun)
                ->where('triwulan', $this->triwulan)
                ->orderBy('uraian');
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
                    ->orWhereHas('belanjaHonorKegiatan', function ($q2) use ($cari) {
                        $q2->where('jenis', $this->tabUtama)
                            ->where('tahun', $this->tahun)
                            ->where('triwulan', $this->triwulan)
                            ->where('uraian', 'like', "%{$cari}%");
                    });
            });
        }

        $daftarSekolah = $query
            ->orderByRaw("CASE WHEN status = 'negeri' THEN 0 WHEN status = 'swasta' THEN 1 ELSE 2 END")
            ->orderByRaw('kecamatan IS NULL')
            ->orderBy('kecamatan')
            ->orderBy('nama_sekolah')
            ->get();

        $this->baris = [];

        foreach ($daftarSekolah as $sekolah) {
            foreach ($sekolah->belanjaHonorKegiatan as $barisData) {
                $this->baris[$barisData->id] = [
                    'uraian' => (string) $barisData->uraian,
                    'volume' => $barisData->volume !== null ? (string) $barisData->volume : '',
                    'satuan' => (string) $barisData->satuan,
                    'tarif_harga' => $barisData->tarif_harga !== null ? (string) $barisData->tarif_harga : '',
                    'tanggal' => $barisData->tanggal?->format('Y-m-d') ?? '',
                ];
            }

            $this->baris[-$sekolah->id] = [
                'uraian' => '', 'volume' => '',
                'satuan' => '', 'tarif_harga' => '', 'tanggal' => '',
            ];
        }

        $tahunOptions = range(now()->year - 2, now()->year + 1);

        // Jumlah UNTUK SELURUH SEKOLAH yang SEDANG DITAMPILKAN (permintaan
        // user 2026-09-16, Part 29: "tambahkan baris Jumlah ... dan kolom
        // Jumlah di total kan"; jawaban AskUserQuestion: "Per sekolah +
        // Total seluruh sekolah", sama seperti Langganan Daya Jasa/
        // Penerimaan Honor PTK/Part 28) - dihitung dari koleksi
        // $daftarSekolah yang SAMA dengan yang dirender ke tabel, jadi
        // otomatis ikut scope peran & filter yang sedang aktif, DAN
        // otomatis ikut tab utama ($tabUtama) yang aktif karena relasi
        // belanjaHonorKegiatan yang di-eager-load di atas sudah disaring
        // per jenis. Total PER SEKOLAH dihitung langsung di view lewat
        // $sekolah->belanjaHonorKegiatan->sum('jumlah') (koleksi sudah
        // di-eager-load, jadi tidak ada query tambahan).
        $totalJumlahKeseluruhan = $daftarSekolah->sum(
            fn (ProfilSekolah $sekolah) => $sekolah->belanjaHonorKegiatan->sum('jumlah')
        );

        return view('livewire.pendataan-bosp.belanja-honor-kegiatan.index', [
            'daftarSekolah' => $daftarSekolah,
            'jenisOptions' => BelanjaHonorKegiatan::JENIS_OPTIONS,
            'triwulanOptions' => BelanjaHonorKegiatan::TRIWULAN_OPTIONS,
            'sekolahOptions' => ProfilSekolah::orderBy('nama_sekolah')->get(['id', 'nama_sekolah']),
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            'tahunOptions' => array_reverse($tahunOptions),
            'totalJumlahKeseluruhan' => $totalJumlahKeseluruhan,
        ]);
    }
}
