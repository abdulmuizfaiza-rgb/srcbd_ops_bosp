<?php

namespace App\Livewire\PendataanOps\Lampiran2c;

use App\Exports\Lampiran2cExport;
use App\Imports\Lampiran2cImport;
use App\Models\Lampiran2c;
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
 * Lampiran 2c - Daftar Penyesuaian Gaji Pokok PTK, per triwulan (1-4).
 *
 * - Superadmin: melihat & mengelola data semua sekolah, bisa memilih
 *   sekolah manapun (Tempat Tugas) saat menambah data, dan memfilter
 *   tabel per sekolah.
 * - Admin OPS: hanya melihat & mengelola data sekolahnya sendiri (field
 *   Tempat Tugas otomatis terkunci ke sekolahnya).
 */
#[Layout('layouts.app')]
#[Title('Lampiran 2c')]
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

    public string $kecamatan = '';

    public string $jenis_kepangkatan = '';

    public string $golongan = '';

    public string $masa_kerja = '';

    public string $pangkat_berkala = '';

    public string $tmt = '';

    public string $gaji_pokok_lama = '';

    public string $gaji_pokok_baru = '';

    public string $keterangan = '';

    public bool $showForm = false;

    public ?int $confirmingDeleteId = null;

    public array $dipilih = [];

    public bool $confirmingHapusTerpilih = false;

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
        $this->dispatch('open-modal', 'lampiran-2c-form');
    }

    public function edit(int $id): void
    {
        $baris = Lampiran2c::findOrFail($id);

        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $this->formInstance++;
        $this->editingId = $baris->id;
        $this->profil_sekolah_id = $baris->profil_sekolah_id;
        $this->nrg = $baris->nrg;
        $this->nuptk = $baris->nuptk;
        $this->nama_ptk = $baris->nama_ptk;
        $this->kecamatan = $baris->kecamatan;
        $this->jenis_kepangkatan = $baris->jenis_kepangkatan;
        $this->golongan = $baris->golongan;
        $this->masa_kerja = $baris->masa_kerja;
        $this->pangkat_berkala = $baris->pangkat_berkala;
        $this->tmt = optional($baris->tmt)->format('Y-m-d') ?? '';
        $this->gaji_pokok_lama = (string) $baris->gaji_pokok_lama;
        $this->gaji_pokok_baru = (string) $baris->gaji_pokok_baru;
        $this->keterangan = $baris->keterangan;
        $this->showForm = true;
        $this->dispatch('open-modal', 'lampiran-2c-form');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'profil_sekolah_id', 'nrg', 'nuptk', 'nama_ptk', 'kecamatan',
            'jenis_kepangkatan', 'golongan', 'masa_kerja', 'pangkat_berkala', 'tmt',
            'gaji_pokok_lama', 'gaji_pokok_baru', 'keterangan',
        ]);
        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'lampiran-2c-form');
    }

    public function simpan(): void
    {
        if (! $this->bolehKelolaSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        abort_unless($this->profil_sekolah_id && $this->bolehKelola($this->profil_sekolah_id), 403);

        $validated = $this->validate([
            'profil_sekolah_id' => ['required', Rule::exists('profil_sekolah', 'id')],
            'nrg' => ['required', 'digits:12'],
            'nuptk' => ['required', 'digits:16'],
            'nama_ptk' => ['required', 'string', 'max:255'],
            'kecamatan' => ['required', Rule::in(array_keys(Lampiran2c::KECAMATAN_OPTIONS))],
            'jenis_kepangkatan' => ['required', Rule::in(array_keys(Lampiran2c::JENIS_KEPANGKATAN_OPTIONS))],
            'golongan' => ['required', Rule::in(array_keys(Lampiran2c::GOLONGAN_OPTIONS))],
            'masa_kerja' => ['required', 'string', 'max:50'],
            'pangkat_berkala' => ['required', Rule::in(array_keys(Lampiran2c::PANGKAT_BERKALA_OPTIONS))],
            'tmt' => ['required', 'date'],
            'gaji_pokok_lama' => ['required', 'integer', 'min:0'],
            'gaji_pokok_baru' => ['required', 'integer', 'min:0'],
            'keterangan' => ['required', 'string'],
        ]);

        $validated['triwulan'] = $this->triwulan;
        $validated['created_by'] = auth()->id();

        if ($this->editingId) {
            $baris = Lampiran2c::findOrFail($this->editingId);
            abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
            unset($validated['created_by']);
            $baris->update($validated);
        } else {
            $validated['tahun'] = now()->year;
            Lampiran2c::create($validated);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'lampiran-2c-form');
        session()->flash('status', 'Data Lampiran 2c berhasil disimpan.');
    }

    public function konfirmasiHapus(int $id): void
    {
        $baris = Lampiran2c::findOrFail($id);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $this->confirmingDeleteId = $id;
        $this->dispatch('open-modal', 'lampiran-2c-hapus');
    }

    public function batalHapus(): void
    {
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'lampiran-2c-hapus');
    }

    public function hapus(): void
    {
        $baris = Lampiran2c::findOrFail($this->confirmingDeleteId);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $baris->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'lampiran-2c-hapus');
        session()->flash('status', 'Data Lampiran 2c berhasil dihapus.');
    }

    /**
     * Hapus massal (checkbox pilih baris) - permintaan user 2026-09-26,
     * pola sama seperti Lampiran2a/2b. Hanya menghapus baris pada
     * halaman yang sedang tampil (lihat toggleSemua()).
     */
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
        if (count($this->dipilih) === 0) {
            return;
        }

        $this->confirmingHapusTerpilih = true;
        $this->dispatch('open-modal', 'lampiran-2c-hapus-terpilih');
    }

    public function batalHapusTerpilih(): void
    {
        $this->confirmingHapusTerpilih = false;
        $this->dispatch('close-modal', 'lampiran-2c-hapus-terpilih');
    }

    public function hapusTerpilih(): void
    {
        $barisTerpilih = Lampiran2c::whereIn('id', $this->dipilih)->get();

        foreach ($barisTerpilih as $baris) {
            abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
        }

        $jumlah = $barisTerpilih->count();

        Lampiran2c::whereIn('id', $barisTerpilih->pluck('id'))->delete();

        $this->dipilih = [];
        $this->confirmingHapusTerpilih = false;
        $this->dispatch('close-modal', 'lampiran-2c-hapus-terpilih');
        session()->flash('status', "Berhasil menghapus {$jumlah} data Lampiran 2c.");
    }

    protected function queryDasar()
    {
        $query = Lampiran2c::query()->with('profilSekolah')->where('triwulan', $this->triwulan);

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

        // Header KECAMATAN & lembar tanda tangan Kepala Sekolah pada hasil
        // export hanya berlaku untuk 1 sekolah, jadi Superadmin wajib
        // memfilter ke 1 sekolah dulu (Admin OPS otomatis sudah terkunci).
        $sekolahId = $this->bolehKelolaSemua() ? $this->filterSekolahId : $this->sekolahSayaId();

        if (! $sekolahId) {
            $this->errorExport = 'Pilih salah satu sekolah pada filter di atas terlebih dahulu sebelum Export Excel, karena header KECAMATAN dan lembar tanda tangan Kepala Sekolah pada hasil export hanya berlaku untuk 1 sekolah.';

            return null;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);

        return Excel::download(
            new Lampiran2cExport(
                $this->queryDasar()->orderBy('nama_ptk')->get(),
                $this->triwulan,
                now()->year,
                $sekolah
            ),
            'lampiran-2c-triwulan-'.$this->triwulan.'.xlsx'
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
                new Lampiran2cImport($this->triwulan, auth()->id(), $sekolahDiperbolehkan),
                $this->fileImport->getRealPath()
            );

            $this->fileImport = null;
            session()->flash('status', 'Import Lampiran 2c berhasil.');
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

        return view('livewire.pendataan-ops.lampiran2c.index', [
            'daftar' => $daftar,
            'triwulanOptions' => Lampiran2c::TRIWULAN_OPTIONS,
            'sekolahOptions' => ProfilSekolah::orderBy('nama_sekolah')->get(['id', 'nama_sekolah']),
            'kecamatanOptions' => Lampiran2c::KECAMATAN_OPTIONS,
            'jenisKepangkatanOptions' => Lampiran2c::JENIS_KEPANGKATAN_OPTIONS,
            'golonganOptions' => Lampiran2c::GOLONGAN_OPTIONS,
            'pangkatBerkalaOptions' => Lampiran2c::PANGKAT_BERKALA_OPTIONS,
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            'semuaTerpilih' => $semuaTerpilih,
        ]);
    }
}
