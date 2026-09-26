<?php

namespace App\Livewire\Pengumuman;

use App\Models\Pengumuman;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Menu "Pengumuman" (BARU, 2026-09-26, permintaan user) - CRUD sederhana
 * khusus Superadmin (Gate 'akses-pengumuman'). Isinya ditampilkan sebagai
 * running text di landing page (beranda) - lihat
 * resources/views/layouts/beranda.blade.php - selama tanggal hari ini ada
 * di antara Tanggal Aktif & Tanggal Non Aktif (lihat
 * App\Models\Pengumuman::scopeAktifSaatIni()). TIDAK ada toggle
 * aktif/nonaktif terpisah - murni dari kedua tanggal ini, sesuai
 * permintaan eksplisit user.
 */
#[Layout('layouts.app')]
#[Title('Pengumuman')]
class Index extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $judul = '';

    public string $isi = '';

    public string $tanggal_aktif = '';

    public string $tanggal_nonaktif = '';

    public ?int $confirmingDeleteId = null;

    public function tambah(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('open-modal', 'pengumuman-form');
    }

    public function edit(int $id): void
    {
        $pengumuman = Pengumuman::findOrFail($id);

        $this->editingId = $pengumuman->id;
        $this->judul = $pengumuman->judul;
        $this->isi = $pengumuman->isi;
        $this->tanggal_aktif = $pengumuman->tanggal_aktif->toDateString();
        $this->tanggal_nonaktif = $pengumuman->tanggal_nonaktif->toDateString();
        $this->showForm = true;
        $this->dispatch('open-modal', 'pengumuman-form');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'judul', 'isi', 'tanggal_aktif', 'tanggal_nonaktif']);
        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'pengumuman-form');
    }

    public function simpan(): void
    {
        $validated = $this->validate([
            'judul' => ['required', 'string', 'max:255'],
            'isi' => ['required', 'string'],
            'tanggal_aktif' => ['required', 'date'],
            'tanggal_nonaktif' => ['required', 'date', 'after_or_equal:tanggal_aktif'],
        ], [
            'tanggal_nonaktif.after_or_equal' => 'Tanggal Non Aktif tidak boleh sebelum Tanggal Aktif.',
        ]);

        if ($this->editingId) {
            Pengumuman::findOrFail($this->editingId)->update($validated);
            session()->flash('status', 'Pengumuman berhasil diperbarui.');
        } else {
            Pengumuman::create($validated);
            session()->flash('status', 'Pengumuman berhasil ditambahkan.');
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'pengumuman-form');
    }

    public function konfirmasiHapus(int $id): void
    {
        $this->confirmingDeleteId = $id;
        $this->dispatch('open-modal', 'pengumuman-hapus');
    }

    public function batalHapus(): void
    {
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'pengumuman-hapus');
    }

    public function hapus(): void
    {
        Pengumuman::whereKey($this->confirmingDeleteId)->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'pengumuman-hapus');
        session()->flash('status', 'Pengumuman berhasil dihapus.');
    }

    public function render()
    {
        $pengumuman = Pengumuman::query()
            ->orderByDesc('tanggal_aktif')
            ->paginate(10);

        return view('livewire.pengumuman.index', [
            'pengumuman' => $pengumuman,
        ]);
    }
}
