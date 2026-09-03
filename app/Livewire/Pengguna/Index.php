<?php

namespace App\Livewire\Pengguna;

use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Pengguna')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterLevel = '';

    // State form modal
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $username = '';

    public string $password = '';

    public string $level_akses = '';

    public string $nama_sekolah = '';

    public string $jabatan = '';

    // State konfirmasi hapus
    public ?int $confirmingDeleteId = null;

    protected function jabatanOtomatis(string $level): string
    {
        return match ($level) {
            User::LEVEL_ADMIN_OPS => 'Operator Sekolah',
            User::LEVEL_ADMIN_BOSP => 'Admin BOSP',
            default => '',
        };
    }

    public function updatedLevelAkses(string $value): void
    {
        $this->jabatan = $this->jabatanOtomatis($value);

        if (in_array($value, [User::LEVEL_ADMIN_OPS, User::LEVEL_ADMIN_BOSP], true)) {
            if ($this->nama_sekolah === '') {
                $this->nama_sekolah = (string) (ProfilSekolah::first()->nama_sekolah ?? '');
            }
            if ($this->username === '') {
                $this->username = (string) (ProfilSekolah::first()->npsn ?? '');
            }
        } else {
            $this->nama_sekolah = '';
        }
    }

    public function tambah(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('open-modal', 'pengguna-form');
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);

        $this->editingId = $user->id;
        $this->username = $user->username;
        $this->password = '';
        $this->level_akses = $user->level_akses;
        $this->nama_sekolah = (string) $user->nama_sekolah;
        $this->jabatan = (string) $user->jabatan;
        $this->showForm = true;
        $this->dispatch('open-modal', 'pengguna-form');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'username', 'password', 'level_akses', 'nama_sekolah', 'jabatan']);
        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'pengguna-form');
    }

    public function simpan(): void
    {
        $rules = [
            'level_akses' => ['required', Rule::in([
                User::LEVEL_SUPERADMIN,
                User::LEVEL_ADMIN_OPS,
                User::LEVEL_ADMIN_BOSP,
            ])],
            'username' => [
                'required', 'string', 'max:255',
                Rule::unique('users', 'username')->ignore($this->editingId),
            ],
            'nama_sekolah' => [
                Rule::requiredIf(in_array($this->level_akses, [User::LEVEL_ADMIN_OPS, User::LEVEL_ADMIN_BOSP], true)),
                'nullable', 'string', 'max:255',
            ],
        ];

        $rules['password'] = $this->editingId
            ? ['nullable', 'string', 'min:6']
            : ['required', 'string', 'min:6'];

        $validated = $this->validate($rules);

        $data = [
            'username' => $validated['username'],
            'level_akses' => $validated['level_akses'],
            'nama_sekolah' => in_array($validated['level_akses'], [User::LEVEL_ADMIN_OPS, User::LEVEL_ADMIN_BOSP], true)
                ? $validated['nama_sekolah']
                : null,
            'jabatan' => $this->jabatanOtomatis($validated['level_akses']) ?: null,
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ($this->editingId) {
            User::findOrFail($this->editingId)->update($data);
            session()->flash('status', 'Pengguna berhasil diperbarui.');
        } else {
            $data['password'] = Hash::make($validated['password']);
            User::create($data);
            session()->flash('status', 'Pengguna berhasil ditambahkan.');
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'pengguna-form');
    }

    public function konfirmasiHapus(int $id): void
    {
        $this->confirmingDeleteId = $id;
        $this->dispatch('open-modal', 'pengguna-hapus');
    }

    public function batalHapus(): void
    {
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'pengguna-hapus');
    }

    public function hapus(): void
    {
        if ($this->confirmingDeleteId === auth()->id()) {
            $this->batalHapus();

            return;
        }

        User::whereKey($this->confirmingDeleteId)->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'pengguna-hapus');
        session()->flash('status', 'Pengguna berhasil dihapus.');
    }

    public function render()
    {
        $pengguna = User::query()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('username', 'like', "%{$this->search}%")
                    ->orWhere('nama_sekolah', 'like', "%{$this->search}%");
            }))
            ->when($this->filterLevel, fn ($q) => $q->where('level_akses', $this->filterLevel))
            ->orderBy('level_akses')
            ->orderBy('username')
            ->paginate(10);

        return view('livewire.pengguna.index', [
            'pengguna' => $pengguna,
            'levelOptions' => User::levelAksesOptions(),
        ]);
    }
}
