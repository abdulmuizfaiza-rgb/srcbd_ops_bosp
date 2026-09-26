<?php

namespace App\Livewire\PendataanBosp\BiayaPendaftaranLomba;

use App\Exports\BiayaPendaftaranLombaExport;
use App\Imports\BiayaPendaftaranLombaImport;
use App\Livewire\Concerns\HasZoomTampilan;
use App\Livewire\Concerns\MenolakEditJikaTerkunciVerval;
use App\Models\BiayaPendaftaranLomba;
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
 * Biaya Pendaftaran Lomba/Bimtek/Workshop - Pendataan BOSP (2026-09-11).
 *
 * Struktur & pola KELAS INI disalin PERSIS dari LanggananDayaJasa\Index
 * (yang sudah dibangun sejak awal dengan tabel diringkas per sekolah +
 * baris placeholder, pelajaran dari Part 16) - HANYA nama Model/tabel/
 * field ("uraian_pembayaran"->"uraian", "tanggal_bayar"->"tanggal") dan
 * namespace/modal yang diganti. Dilacak per Tahun & per Triwulan (4 Tab,
 * SATU LAPIS - beda dengan Belanja Pemeliharaan Bangunan/PC yang punya 2
 * lapis tab, karena menu ini TIDAK diminta ada tab utama tambahan).
 *
 * Sesuai jawaban AskUserQuestion 2026-09-11: 1 sekolah bisa punya BANYAK
 * baris ("Banyak baris per sekolah"), dan Uraian BOLEH DUPLIKAT - jadi
 * TIDAK ADA logic "xxxSudahDipakai()" untuk field ini.
 *
 * NPSN & Nama Sekolah SELALU tampilan read-only (otomatis dari relasi
 * profilSekolah), Jumlah SELALU hasil rumus otomatis (Volume x Tarif
 * Harga - lihat BiayaPendaftaranLomba::hitungJumlah()).
 *
 * - Superadmin: melihat & mengelola data semua sekolah, bisa memilih
 *   sekolah manapun saat menambah/mengedit data, dan memfilter tabel per
 *   sekolah.
 * - Admin BOSP: hanya melihat & mengelola data sekolahnya sendiri (field
 *   Sekolah otomatis terkunci ke sekolahnya).
 */
#[Layout('layouts.app')]
#[Title('Biaya Pendaftaran Lomba/Bimtek/Workshop')]
class Index extends Component
{
    use HasZoomTampilan;
    use MenolakEditJikaTerkunciVerval;
    use WithFileUploads;

    public int $tahun;

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

        $baris = BiayaPendaftaranLomba::find($rowId);

        if (! $baris) {
            return;
        }

        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
        $this->abortJikaTerkunciVerval($baris->profil_sekolah_id, $this->tahun, $baris->triwulan);

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
            $data['jumlah'] = BiayaPendaftaranLomba::hitungJumlah($volumeBaru, $tarifBaru);
        }

        $baris->update($data);
    }

    protected function updatedBarisBaru(string $name, int $idPlaceholder, string $field, mixed $value): void
    {
        $sekolahId = abs($idPlaceholder);

        abort_unless($this->bolehKelola($sekolahId), 403);
        $this->abortJikaTerkunciVerval($sekolahId, $this->tahun, $this->triwulan);

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

        $data['jumlah'] = BiayaPendaftaranLomba::hitungJumlah(
            $field === 'volume' ? $data['volume'] : null,
            $field === 'tarif_harga' ? $data['tarif_harga'] : null,
        );

        $data['profil_sekolah_id'] = $sekolahId;
        $data['tahun'] = $this->tahun;
        $data['triwulan'] = $this->triwulan;
        $data['created_by'] = auth()->id();

        BiayaPendaftaranLomba::create($data);

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
        $this->dispatch('open-modal', 'biaya-pendaftaran-lomba-form');
    }

    public function edit(int $id): void
    {
        $baris = BiayaPendaftaranLomba::findOrFail($id);

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
        $this->dispatch('open-modal', 'biaya-pendaftaran-lomba-form');
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
        $this->dispatch('close-modal', 'biaya-pendaftaran-lomba-form');
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

        $validated['jumlah'] = BiayaPendaftaranLomba::hitungJumlah($validated['volume'], $validated['tarif_harga']);
        $validated['tahun'] = $this->tahun;
        $validated['triwulan'] = $this->triwulan;

        if ($this->editingId) {
            $baris = BiayaPendaftaranLomba::findOrFail($this->editingId);
            abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
            $baris->update($validated);
        } else {
            $validated['created_by'] = auth()->id();
            BiayaPendaftaranLomba::create($validated);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'biaya-pendaftaran-lomba-form');
        session()->flash('status', 'Data Biaya Pendaftaran Lomba/Bimtek/Workshop berhasil disimpan.');
    }

    public function konfirmasiHapus(int $id): void
    {
        $baris = BiayaPendaftaranLomba::findOrFail($id);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $this->confirmingDeleteId = $id;
        $this->dispatch('open-modal', 'biaya-pendaftaran-lomba-hapus');
    }

    public function batalHapus(): void
    {
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'biaya-pendaftaran-lomba-hapus');
    }

    /**
     * Hapus massal (checkbox pilih baris + tombol "Hapus Terpilih") -
     * permintaan user 2026-09-26 supaya admin bisa hapus banyak data
     * sekaligus tanpa hapus satu-satu. Pola identik dengan Langganan
     * Daya Jasa.
     */
    public array $dipilih = [];

    public bool $confirmingHapusTerpilih = false;

    public function toggleSemua(): void
    {
        $idSemua = collect($this->baris)->keys()->filter(fn ($id) => $id > 0)->values()->all();
        if (count($idSemua) > 0 && count(array_diff($idSemua, $this->dipilih)) === 0) {
            $this->dipilih = [];
        } else {
            $this->dipilih = $idSemua;
        }
    }

    public function konfirmasiHapusTerpilih(): void
    {
        if (empty($this->dipilih)) {
            return;
        }
        $this->confirmingHapusTerpilih = true;
        $this->dispatch('open-modal', 'biaya-pendaftaran-lomba-hapus-terpilih');
    }

    public function batalHapusTerpilih(): void
    {
        $this->confirmingHapusTerpilih = false;
        $this->dispatch('close-modal', 'biaya-pendaftaran-lomba-hapus-terpilih');
    }

    public function hapusTerpilih(): void
    {
        $barisTerpilih = BiayaPendaftaranLomba::whereIn('id', $this->dipilih)->get();
        foreach ($barisTerpilih as $baris) {
            abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
            $this->abortJikaTerkunciVerval($baris->profil_sekolah_id, $this->tahun, $baris->triwulan);
        }

        $jumlah = $barisTerpilih->count();
        BiayaPendaftaranLomba::whereIn('id', $barisTerpilih->pluck('id'))->delete();

        $this->dipilih = [];
        $this->confirmingHapusTerpilih = false;
        $this->dispatch('close-modal', 'biaya-pendaftaran-lomba-hapus-terpilih');
        session()->flash('status', "Berhasil menghapus {$jumlah} data Biaya Pendaftaran Lomba/Bimtek/Workshop sekaligus.");
    }

    public function hapus(): void
    {
        $baris = BiayaPendaftaranLomba::findOrFail($this->confirmingDeleteId);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
        $this->abortJikaTerkunciVerval($baris->profil_sekolah_id, $this->tahun, $baris->triwulan);

        $baris->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'biaya-pendaftaran-lomba-hapus');
        session()->flash('status', 'Data Biaya Pendaftaran Lomba/Bimtek/Workshop berhasil dihapus.');
    }

    protected function queryDasar()
    {
        $query = BiayaPendaftaranLomba::query()
            ->with('profilSekolah')
            ->join('profil_sekolah', 'profil_sekolah.id', '=', 'biaya_pendaftaran_lomba.profil_sekolah_id')
            ->where('biaya_pendaftaran_lomba.tahun', $this->tahun)
            ->where('biaya_pendaftaran_lomba.triwulan', $this->triwulan)
            ->select('biaya_pendaftaran_lomba.*');

        if (! $this->bolehKelolaSemua()) {
            $query->where('biaya_pendaftaran_lomba.profil_sekolah_id', $this->sekolahSayaId());
        } elseif ($this->filterSekolahId) {
            $query->where('biaya_pendaftaran_lomba.profil_sekolah_id', $this->filterSekolahId);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('biaya_pendaftaran_lomba.uraian', 'like', "%{$this->search}%")
                    ->orWhere('profil_sekolah.nama_sekolah', 'like', "%{$this->search}%")
                    ->orWhere('profil_sekolah.npsn', 'like', "%{$this->search}%");
            });
        }

        return $query->orderBy('profil_sekolah.nama_sekolah')->orderBy('biaya_pendaftaran_lomba.uraian');
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
            new BiayaPendaftaranLombaExport(
                $this->queryDasar()->get(),
                $this->triwulan,
                $this->tahun,
                $sekolah
            ),
            'biaya-pendaftaran-lomba-triwulan-'.$this->triwulan.'-'.$this->tahun.'.xlsx'
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

            if ($sekolahDiperbolehkan) {
                $this->abortJikaTerkunciVerval($sekolahDiperbolehkan, $this->tahun, $this->triwulan);
            }

            Excel::import(
                new BiayaPendaftaranLombaImport($this->tahun, $this->triwulan, auth()->id(), $sekolahDiperbolehkan),
                $this->fileImport->getRealPath()
            );

            $this->fileImport = null;
            session()->flash('status', 'Import Biaya Pendaftaran Lomba/Bimtek/Workshop berhasil.');
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
        $query = ProfilSekolah::with(['biayaPendaftaranLomba' => function ($q) {
            $q->where('tahun', $this->tahun)
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
                    ->orWhereHas('biayaPendaftaranLomba', function ($q2) use ($cari) {
                        $q2->where('tahun', $this->tahun)
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
            foreach ($sekolah->biayaPendaftaranLomba as $barisData) {
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

        $idRealBaris = collect($this->baris)->keys()->filter(fn ($id) => $id > 0)->values()->all();
        $this->dipilih = array_values(array_intersect($this->dipilih, $idRealBaris));
        $semuaTerpilih = count($idRealBaris) > 0 && count(array_diff($idRealBaris, $this->dipilih)) === 0;

        $tahunOptions = range(now()->year - 2, now()->year + 1);

        // Jumlah Biaya Pendaftaran Lomba/Bimtek/Workshop UNTUK SELURUH
        // SEKOLAH yang SEDANG DITAMPILKAN (permintaan user 2026-09-16:
        // "tambahkan baris Jumlah ... dan kolom Jumlah di total kan";
        // jawaban AskUserQuestion: "Per sekolah + Total seluruh sekolah",
        // pola sama seperti Langganan Daya Jasa) - dihitung dari koleksi
        // $daftarSekolah yang SAMA dengan yang dirender ke tabel, jadi
        // otomatis ikut scope peran & filter yang sedang aktif. Total PER
        // SEKOLAH dihitung langsung di view lewat
        // $sekolah->biayaPendaftaranLomba->sum('jumlah') (koleksi sudah
        // di-eager-load, jadi tidak ada query tambahan).
        $totalBiayaKeseluruhan = $daftarSekolah->sum(
            fn (ProfilSekolah $sekolah) => $sekolah->biayaPendaftaranLomba->sum('jumlah')
        );

        return view('livewire.pendataan-bosp.biaya-pendaftaran-lomba.index', [
            'daftarSekolah' => $daftarSekolah,
            'triwulanOptions' => BiayaPendaftaranLomba::TRIWULAN_OPTIONS,
            'sekolahOptions' => ProfilSekolah::orderBy('nama_sekolah')->get(['id', 'nama_sekolah']),
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            // Kuncian UI - permintaan user 2026-09-23 (round kesepuluh,
            // poin 1) - lihat docblock lengkap App\Livewire\Concerns\
            // MenolakEditJikaTerkunciVerval::terkunciVervalUntukTampilan().
            'terkunciTriwulanIni' => $this->terkunciVervalUntukTampilan($this->triwulan),
            'tahunOptions' => array_reverse($tahunOptions),
            'totalBiayaKeseluruhan' => $totalBiayaKeseluruhan,
            'semuaTerpilih' => $semuaTerpilih,
        ]);
    }
}
