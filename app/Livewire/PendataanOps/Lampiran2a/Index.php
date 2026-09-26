<?php

namespace App\Livewire\PendataanOps\Lampiran2a;

use App\Exports\Lampiran2aExport;
use App\Imports\Lampiran2aImport;
use App\Models\Lampiran2a;
use App\Models\ProfilSekolah;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Lampiran 2a - riwayat gaji pokok PTK per sekolah, per triwulan.
 *
 * - Superadmin: melihat & mengelola data semua sekolah, bisa memilih
 *   sekolah manapun saat menambah data, dan memfilter tabel per sekolah.
 * - Admin OPS: hanya melihat & mengelola data sekolahnya sendiri (field
 *   Nama Sekolah otomatis terkunci ke sekolahnya).
 */
#[Layout('layouts.app')]
#[Title('Lampiran 2a')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Url(as: 'triwulan')]
    public int $triwulan = 1;

    public string $search = '';

    public ?int $filterSekolahId = null;

    public ?int $editingId = null;

    /**
     * Dinaikkan setiap kali form Tambah/Edit dibuka - dipakai sebagai
     * wire:key pada kotak input Rupiah (yang wire:ignore) supaya kotaknya
     * selalu ter-refresh, termasuk saat klik Tambah berkali-kali berturut-
     * turut (bukan cuma saat pindah antar data yang berbeda).
     */
    public int $formInstance = 0;

    public ?int $profil_sekolah_id = null;

    public string $nrg = '';

    public string $nuptk = '';

    public string $nama_ptk = '';

    public string $status_kepegawaian = '';

    public string $gaji_pokok_januari = '';

    public string $npwp = '';

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

    public function tambah(): void
    {
        $this->resetForm();
        $this->formInstance++;

        if (! $this->bolehKelolaSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        $this->showForm = true;
        $this->dispatch('open-modal', 'lampiran-2a-form');
    }

    public function edit(int $id): void
    {
        $baris = Lampiran2a::findOrFail($id);

        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $this->formInstance++;
        $this->editingId = $baris->id;
        $this->profil_sekolah_id = $baris->profil_sekolah_id;
        $this->nrg = $baris->nrg;
        $this->nuptk = $baris->nuptk;
        $this->nama_ptk = $baris->nama_ptk;
        $this->status_kepegawaian = $baris->status_kepegawaian;
        $this->gaji_pokok_januari = (string) $baris->gaji_pokok_januari;
        $this->npwp = $baris->npwp;
        $this->showForm = true;
        $this->dispatch('open-modal', 'lampiran-2a-form');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'profil_sekolah_id', 'nrg', 'nuptk', 'nama_ptk',
            'status_kepegawaian', 'gaji_pokok_januari', 'npwp',
        ]);
        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'lampiran-2a-form');
    }

    public function simpan(): void
    {
        if (! $this->bolehKelolaSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        abort_unless($this->profil_sekolah_id && $this->bolehKelola($this->profil_sekolah_id), 403);

        $validated = $this->validate([
            'profil_sekolah_id' => ['required', Rule::exists('profil_sekolah', 'id')],
            'nrg' => ['required', 'regex:/^[0-9]{12}$/'],
            'nuptk' => ['required', 'regex:/^[0-9]{16}$/'],
            'nama_ptk' => ['required', 'string', 'max:255'],
            'status_kepegawaian' => ['required', Rule::in(array_keys(Lampiran2a::STATUS_KEPEGAWAIAN_OPTIONS))],
            'gaji_pokok_januari' => ['required', 'integer', 'min:0'],
            'npwp' => ['required', 'regex:/^[0-9]{15,16}$/'],
        ], [
            'nrg.regex' => 'NRG harus berupa 12 digit angka.',
            'nuptk.regex' => 'NUPTK harus berupa 16 digit angka.',
            'npwp.regex' => 'NPWP harus berupa 15-16 digit angka.',
        ]);

        $validated['triwulan'] = $this->triwulan;
        $validated['created_by'] = auth()->id();

        if ($this->editingId) {
            $baris = Lampiran2a::findOrFail($this->editingId);
            abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
            unset($validated['created_by']);
            $baris->update($validated);
        } else {
            $validated['tahun'] = now()->year;
            Lampiran2a::create($validated);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'lampiran-2a-form');
        session()->flash('status', 'Data Lampiran 2a berhasil disimpan.');
    }

    public function konfirmasiHapus(int $id): void
    {
        $baris = Lampiran2a::findOrFail($id);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $this->confirmingDeleteId = $id;
        $this->dispatch('open-modal', 'lampiran-2a-hapus');
    }

    public function batalHapus(): void
    {
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'lampiran-2a-hapus');
    }

    /**
     * Hapus massal (checkbox pilih baris + tombol "Hapus Terpilih") -
     * permintaan user 2026-09-26. Menu ini pakai daftar BERHALAMAN
     * (WithPagination, 10/halaman) bukan pola "tbody per sekolah" seperti
     * menu BOSP - "pilih semua" di sini HANYA memilih baris yang SEDANG
     * TAMPIL di halaman aktif (bukan seluruh data di semua halaman),
     * supaya perilakunya jelas & tidak menghapus data yang tidak terlihat
     * user.
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
        $this->dispatch('open-modal', 'lampiran-2a-hapus-terpilih');
    }

    public function batalHapusTerpilih(): void
    {
        $this->confirmingHapusTerpilih = false;
        $this->dispatch('close-modal', 'lampiran-2a-hapus-terpilih');
    }

    public function hapusTerpilih(): void
    {
        $barisTerpilih = Lampiran2a::whereIn('id', $this->dipilih)->get();
        foreach ($barisTerpilih as $baris) {
            abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
        }

        $jumlah = $barisTerpilih->count();
        Lampiran2a::whereIn('id', $barisTerpilih->pluck('id'))->delete();

        $this->dipilih = [];
        $this->confirmingHapusTerpilih = false;
        $this->dispatch('close-modal', 'lampiran-2a-hapus-terpilih');
        session()->flash('status', "Berhasil menghapus {$jumlah} data Lampiran 2a sekaligus.");
    }

    public function hapus(): void
    {
        $baris = Lampiran2a::findOrFail($this->confirmingDeleteId);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $baris->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'lampiran-2a-hapus');
        session()->flash('status', 'Data Lampiran 2a berhasil dihapus.');
    }

    protected function queryDasar()
    {
        $query = Lampiran2a::query()->with('profilSekolah')->where('triwulan', $this->triwulan);

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
            new Lampiran2aExport(
                $this->queryDasar()->orderBy('nama_ptk')->get(),
                $this->triwulan,
                now()->year,
                $sekolah
            ),
            'lampiran-2a-triwulan-'.$this->triwulan.'.xlsx'
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
                new Lampiran2aImport($this->triwulan, auth()->id(), $sekolahDiperbolehkan),
                $this->fileImport->getRealPath()
            );

            $this->fileImport = null;
            session()->flash('status', 'Import Lampiran 2a berhasil.');
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

        // Filter $dipilih supaya hanya berisi id yang masih valid di
        // halaman yang SEDANG ditampilkan - pola sama seperti $dipilih
        // pada menu BOSP (lihat catatan di toggleSemua() di atas).
        $idHalamanIni = $daftar->pluck('id')->all();
        $this->dipilih = array_values(array_intersect($this->dipilih, $idHalamanIni));
        $semuaTerpilih = count($idHalamanIni) > 0 && count(array_diff($idHalamanIni, $this->dipilih)) === 0;

        return view('livewire.pendataan-ops.lampiran2a.index', [
            'daftar' => $daftar,
            'triwulanOptions' => Lampiran2a::TRIWULAN_OPTIONS,
            'statusKepegawaianOptions' => Lampiran2a::STATUS_KEPEGAWAIAN_OPTIONS,
            'sekolahOptions' => ProfilSekolah::orderBy('nama_sekolah')->get(['id', 'nama_sekolah']),
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            'tahunSekarang' => now()->year,
            'semuaTerpilih' => $semuaTerpilih,
        ]);
    }
}
