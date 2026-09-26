<?php

namespace App\Livewire\PendataanOps\Lampiran2b;

use App\Exports\Lampiran2bExport;
use App\Imports\Lampiran2bImport;
use App\Models\Lampiran2a;
use App\Models\Lampiran2b;
use App\Models\ProfilSekolah;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Lampiran 2b - keterangan & TMT per PTK, per sekolah, per triwulan.
 *
 * NRG & NUPTK selalu diambil otomatis dari Lampiran 2a (dicocokkan lewat
 * Nama PTK, sekolah, & triwulan yang sama) - field Nama PTK di sini
 * berupa pilihan dari data Lampiran 2a, bukan ketik bebas.
 *
 * - Superadmin: melihat & mengelola data semua sekolah, bisa memilih
 *   sekolah manapun saat menambah data, dan memfilter tabel per sekolah.
 * - Admin OPS: hanya melihat & mengelola data sekolahnya sendiri (field
 *   Nama Sekolah otomatis terkunci ke sekolahnya).
 */
#[Layout('layouts.app')]
#[Title('Lampiran 2b')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Url(as: 'triwulan')]
    public int $triwulan = 1;

    public string $search = '';

    public ?int $filterSekolahId = null;

    public ?int $editingId = null;

    public ?int $profil_sekolah_id = null;

    public string $nrg = '';

    public string $nuptk = '';

    public string $nama_ptk = '';

    public string $keterangan = '';

    public string $tmt = '';

    public bool $showForm = false;

    public ?int $confirmingDeleteId = null;

    public $fileImport = null;

    public ?string $errorImport = null;

    public ?string $errorExport = null;

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

    public function pindahTab(int $triwulan): void
    {
        $this->triwulan = $triwulan;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSekolahId(): void
    {
        $this->resetPage();
    }

    /**
     * Sekolah pada FORM tambah/edit berubah (khusus Superadmin) - Nama PTK
     * yang sebelumnya dipilih sudah tidak relevan untuk sekolah baru,
     * jadi dikosongkan lagi supaya tidak salah kaitan NRG/NUPTK.
     */
    public function updatedProfilSekolahId(): void
    {
        $this->nama_ptk = '';
        $this->nrg = '';
        $this->nuptk = '';
    }

    /**
     * Nama PTK dipilih pada form - isi otomatis NRG & NUPTK dari data
     * Lampiran 2a (sekolah & triwulan yang sama).
     */
    public function updatedNamaPtk(): void
    {
        $this->isiOtomatisNrgNuptk();
    }

    protected function isiOtomatisNrgNuptk(): void
    {
        $sumber = $this->cariSumberLampiran2a($this->profil_sekolah_id, $this->nama_ptk);

        $this->nrg = $sumber->nrg ?? '';
        $this->nuptk = $sumber->nuptk ?? '';
    }

    protected function cariSumberLampiran2a(?int $profilSekolahId, string $namaPtk): ?Lampiran2a
    {
        if (! $profilSekolahId || $namaPtk === '') {
            return null;
        }

        return Lampiran2a::query()
            ->where('profil_sekolah_id', $profilSekolahId)
            ->where('triwulan', $this->triwulan)
            ->where('nama_ptk', $namaPtk)
            ->first();
    }

    /**
     * Daftar Nama PTK dari Lampiran 2a (sekolah pada form saat ini &
     * triwulan aktif) untuk pilihan dropdown Nama PTK.
     */
    protected function namaPtkOptions(): Collection
    {
        if (! $this->profil_sekolah_id) {
            return collect();
        }

        return Lampiran2a::query()
            ->where('profil_sekolah_id', $this->profil_sekolah_id)
            ->where('triwulan', $this->triwulan)
            ->orderBy('nama_ptk')
            ->pluck('nama_ptk')
            ->unique()
            ->values();
    }

    public function tambah(): void
    {
        $this->resetForm();

        if (! $this->bolehKelolaSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        $this->showForm = true;
        $this->dispatch('open-modal', 'lampiran-2b-form');
    }

    public function edit(int $id): void
    {
        $baris = Lampiran2b::findOrFail($id);

        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $this->editingId = $baris->id;
        $this->profil_sekolah_id = $baris->profil_sekolah_id;
        $this->nrg = $baris->nrg;
        $this->nuptk = $baris->nuptk;
        $this->nama_ptk = $baris->nama_ptk;
        $this->keterangan = $baris->keterangan;
        $this->tmt = optional($baris->tmt)->format('Y-m-d') ?? '';
        $this->showForm = true;
        $this->dispatch('open-modal', 'lampiran-2b-form');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'profil_sekolah_id', 'nrg', 'nuptk', 'nama_ptk', 'keterangan', 'tmt',
        ]);
        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'lampiran-2b-form');
    }

    public function simpan(): void
    {
        if (! $this->bolehKelolaSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        abort_unless($this->profil_sekolah_id && $this->bolehKelola($this->profil_sekolah_id), 403);

        $validated = $this->validate([
            'profil_sekolah_id' => ['required', Rule::exists('profil_sekolah', 'id')],
            'nama_ptk' => ['required', 'string', 'max:255'],
            'keterangan' => ['required', 'string'],
            'tmt' => ['required', 'date'],
        ]);

        // NRG & NUPTK selalu dihitung ulang di server dari data Lampiran 2a
        // (tidak dipercaya begitu saja dari input client) supaya selalu
        // konsisten dengan Nama PTK yang benar-benar dipilih.
        $sumber = $this->cariSumberLampiran2a($validated['profil_sekolah_id'], $validated['nama_ptk']);

        if (! $sumber) {
            $this->addError('nama_ptk', 'Nama PTK tersebut tidak ditemukan di Lampiran 2a '.Lampiran2b::TRIWULAN_OPTIONS[$this->triwulan].' untuk sekolah ini. Silakan isi Lampiran 2a terlebih dahulu.');

            return;
        }

        $validated['nrg'] = $sumber->nrg;
        $validated['nuptk'] = $sumber->nuptk;
        $validated['triwulan'] = $this->triwulan;
        $validated['created_by'] = auth()->id();

        if ($this->editingId) {
            $baris = Lampiran2b::findOrFail($this->editingId);
            abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
            unset($validated['created_by']);
            $baris->update($validated);
        } else {
            $validated['tahun'] = now()->year;
            Lampiran2b::create($validated);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'lampiran-2b-form');
        session()->flash('status', 'Data Lampiran 2b berhasil disimpan.');
    }

    public function konfirmasiHapus(int $id): void
    {
        $baris = Lampiran2b::findOrFail($id);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $this->confirmingDeleteId = $id;
        $this->dispatch('open-modal', 'lampiran-2b-hapus');
    }

    public function batalHapus(): void
    {
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'lampiran-2b-hapus');
    }

    /**
     * Hapus massal (checkbox pilih baris + tombol "Hapus Terpilih") -
     * permintaan user 2026-09-26. Pola sama seperti Lampiran2a - "pilih
     * semua" HANYA memilih baris di halaman yang sedang tampil.
     */
    public array $dipilih = [];

    public bool $confirmingHapusTerpilih = false;

    public function toggleSemua(): void
    {
        $idHalamanIni = $this->queryDasar()->latest()->paginate(10)->pluck('id')->all();
        if (count($idHalamanIni) > 0 && count(array_diff($idHalamanIni, $this->dipilih)) === 0) {
            $this->dipilih = array_values(array_diff($this->dipilih, $idHalamanIni));
        } else {
            $this->dipilih = array_values(array_unique(array_merge($this->dipilih, $idHalamanIni)));
        }
    }

    public function konfirmasiHapusTerpilih(): void
    {
        if (empty($this->dipilih)) {
            return;
        }
        $this->confirmingHapusTerpilih = true;
        $this->dispatch('open-modal', 'lampiran-2b-hapus-terpilih');
    }

    public function batalHapusTerpilih(): void
    {
        $this->confirmingHapusTerpilih = false;
        $this->dispatch('close-modal', 'lampiran-2b-hapus-terpilih');
    }

    public function hapusTerpilih(): void
    {
        $barisTerpilih = Lampiran2b::whereIn('id', $this->dipilih)->get();
        foreach ($barisTerpilih as $baris) {
            abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
        }

        $jumlah = $barisTerpilih->count();
        Lampiran2b::whereIn('id', $barisTerpilih->pluck('id'))->delete();

        $this->dipilih = [];
        $this->confirmingHapusTerpilih = false;
        $this->dispatch('close-modal', 'lampiran-2b-hapus-terpilih');
        session()->flash('status', "Berhasil menghapus {$jumlah} data Lampiran 2b sekaligus.");
    }

    public function hapus(): void
    {
        $baris = Lampiran2b::findOrFail($this->confirmingDeleteId);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $baris->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'lampiran-2b-hapus');
        session()->flash('status', 'Data Lampiran 2b berhasil dihapus.');
    }

    protected function queryDasar()
    {
        $query = Lampiran2b::query()->with('profilSekolah')->where('triwulan', $this->triwulan);

        if (! $this->bolehKelolaSemua()) {
            $query->where('profil_sekolah_id', $this->sekolahSayaId());
        } elseif ($this->filterSekolahId) {
            $query->where('profil_sekolah_id', $this->filterSekolahId);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('nama_ptk', 'like', "%{$this->search}%")
                    ->orWhere('nrg', 'like', "%{$this->search}%")
                    ->orWhere('nuptk', 'like', "%{$this->search}%");
            });
        }

        return $query;
    }

    public function export()
    {
        $this->errorExport = null;

        // Lembar tanda tangan Kepala Sekolah pada hasil export hanya berlaku
        // untuk 1 sekolah, jadi Superadmin wajib memfilter ke 1 sekolah dulu
        // (Admin OPS otomatis sudah terkunci ke sekolahnya sendiri).
        $sekolahId = $this->bolehKelolaSemua() ? $this->filterSekolahId : $this->sekolahSayaId();

        if (! $sekolahId) {
            $this->errorExport = 'Pilih salah satu sekolah pada filter di atas terlebih dahulu sebelum Export Excel, karena lembar tanda tangan Kepala Sekolah pada hasil export hanya berlaku untuk 1 sekolah.';

            return null;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);

        return Excel::download(
            new Lampiran2bExport(
                $this->queryDasar()->orderBy('nama_ptk')->get(),
                $this->triwulan,
                now()->year,
                $sekolah
            ),
            'lampiran-2b-triwulan-'.$this->triwulan.'.xlsx'
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
                new Lampiran2bImport($this->triwulan, auth()->id(), $sekolahDiperbolehkan),
                $this->fileImport->getRealPath()
            );

            $this->fileImport = null;
            session()->flash('status', 'Import Lampiran 2b berhasil.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $pesan = [];
            foreach ($e->failures() as $failure) {
                $pesan[] = 'Baris '.$failure->row().': '.implode(', ', $failure->errors());
            }
            $this->errorImport = implode(' | ', $pesan);
        }
    }

    public function render()
    {
        $daftar = $this->queryDasar()->latest()->paginate(10);

        $idHalamanIni = $daftar->pluck('id')->all();
        $this->dipilih = array_values(array_intersect($this->dipilih, $idHalamanIni));
        $semuaTerpilih = count($idHalamanIni) > 0 && count(array_diff($idHalamanIni, $this->dipilih)) === 0;

        return view('livewire.pendataan-ops.lampiran2b.index', [
            'daftar' => $daftar,
            'triwulanOptions' => Lampiran2b::TRIWULAN_OPTIONS,
            'sekolahOptions' => ProfilSekolah::orderBy('nama_sekolah')->get(['id', 'nama_sekolah']),
            'namaPtkOptions' => $this->namaPtkOptions(),
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            'semuaTerpilih' => $semuaTerpilih,
        ]);
    }
}
