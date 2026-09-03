<?php

namespace App\Livewire\ProfilSekolah;

use App\Models\ProfilSekolah;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Profil Sekolah')]
class Index extends Component
{
    use WithFileUploads;

    public ?ProfilSekolah $profil = null;

    public string $npsn = '';

    public string $nama_sekolah = '';

    public string $nama_kepala_sekolah = '';

    public string $nip_kepala_sekolah = '';

    public string $alamat_sekolah = '';

    public $logo_sekolah_baru = null;

    public $logo_pemda_baru = null;

    public bool $editing = false;

    public function mount(): void
    {
        $this->profil = ProfilSekolah::first();

        if ($this->profil) {
            $this->npsn = (string) $this->profil->npsn;
            $this->nama_sekolah = (string) $this->profil->nama_sekolah;
            $this->nama_kepala_sekolah = (string) $this->profil->nama_kepala_sekolah;
            $this->nip_kepala_sekolah = (string) $this->profil->nip_kepala_sekolah;
            $this->alamat_sekolah = (string) $this->profil->alamat_sekolah;
        } else {
            // Belum ada data sama sekali -> langsung tampilkan form pengisian.
            $this->editing = true;
        }
    }

    public function edit(): void
    {
        $this->editing = true;
    }

    public function batal(): void
    {
        $this->mount();
        $this->editing = (bool) ! $this->profil;
    }

    public function simpan(): void
    {
        $validated = $this->validate([
            'npsn' => ['nullable', 'string', 'max:20'],
            'nama_sekolah' => ['required', 'string', 'max:255'],
            'nama_kepala_sekolah' => ['nullable', 'string', 'max:255'],
            'nip_kepala_sekolah' => ['nullable', 'string', 'max:50'],
            'alamat_sekolah' => ['nullable', 'string', 'max:1000'],
            'logo_sekolah_baru' => ['nullable', 'image', 'max:2048'],
            'logo_pemda_baru' => ['nullable', 'image', 'max:2048'],
        ]);

        $data = [
            'npsn' => $validated['npsn'] ?: null,
            'nama_sekolah' => $validated['nama_sekolah'],
            'nama_kepala_sekolah' => $validated['nama_kepala_sekolah'] ?: null,
            'nip_kepala_sekolah' => $validated['nip_kepala_sekolah'] ?: null,
            'alamat_sekolah' => $validated['alamat_sekolah'] ?: null,
        ];

        $disk = config('filesystems.default');

        if ($this->logo_sekolah_baru) {
            $data['logo_sekolah'] = $this->logo_sekolah_baru->store('logo-sekolah', $disk);
            if ($this->profil?->logo_sekolah) {
                Storage::disk($disk)->delete($this->profil->logo_sekolah);
            }
        }

        if ($this->logo_pemda_baru) {
            $data['logo_pemda'] = $this->logo_pemda_baru->store('logo-pemda', $disk);
            if ($this->profil?->logo_pemda) {
                Storage::disk($disk)->delete($this->profil->logo_pemda);
            }
        }

        if ($this->profil) {
            $this->profil->update($data);
        } else {
            $this->profil = ProfilSekolah::create($data);
        }

        $this->logo_sekolah_baru = null;
        $this->logo_pemda_baru = null;
        $this->editing = false;

        session()->flash('status', 'Profil sekolah berhasil disimpan.');
        $this->mount();
    }

    public function render()
    {
        return view('livewire.profil-sekolah.index');
    }
}
