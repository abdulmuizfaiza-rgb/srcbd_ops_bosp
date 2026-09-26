<?php

namespace App\Livewire\PendataanBosp;

use App\Livewire\Concerns\HasZoomTampilan;
use App\Models\PendataanBosp;
use App\Models\ProfilSekolah;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Identitas Admin BOSP - satu data per sekolah.
 *
 * - Superadmin: melihat seluruh sekolah, bisa mengisi/mengedit Identitas
 *   Admin BOSP sekolah manapun.
 * - Admin BOSP: hanya melihat & bisa mengisi/mengedit Identitas Admin
 *   BOSP sekolahnya sendiri.
 */
#[Layout('layouts.app')]
#[Title('Identitas Admin BOSP')]
class Index extends Component
{
    use HasZoomTampilan;

    /**
     * Pencarian gabungan Nama Sekolah ATAU Nama Admin BOSP (permintaan
     * user 2026-09-26) - satu kotak pencarian, mencocokkan salah satu
     * dari kedua kolom (mengikuti pola search-box tunggal yang sudah
     * dipakai di menu lain, mis. Lampiran 2c).
     */
    public string $search = '';

    public ?int $sekolahId = null;

    public ?int $editingId = null;

    public string $nuptk = '';

    public string $nama = '';

    public string $nip = '';

    public string $jk = '';

    public string $tempat_lahir = '';

    public string $tanggal_lahir = '';

    public string $status_kepegawaian = '';

    public string $pendidikan_terakhir = '';

    public string $jurusan = '';

    public string $nama_perguruan_tinggi = '';

    public string $no_whatsapp = '';

    public bool $showForm = false;

    /**
     * Popup sukses "Profil Sekolah baru saja lengkap" - HANYA muncul
     * sekali, tepat saat auto-redirect dari ProfilSekolah\Index terjadi
     * (dibaca dari session flash, bukan dari database), sesuai jawaban
     * klarifikasi user. Diprioritaskan di atas popup peringatan "Identitas
     * Admin BOSP belum lengkap" supaya tidak muncul berbarengan pada
     * kunjungan pertama ini.
     */
    public bool $tampilkanSuksesProfilLengkap = false;

    public function mount(): void
    {
        $user = auth()->user();

        if ($user->isSuperadmin()) {
            return;
        }

        if (session('profil_baru_lengkap')) {
            $this->tampilkanSuksesProfilLengkap = true;
            $this->dispatch('open-modal', 'identitas-bosp-sukses');

            return;
        }

        $sekolah = $user->profilSekolah()->first();
        $sudahIsiIdentitas = $sekolah && PendataanBosp::where('profil_sekolah_id', $sekolah->id)->exists();

        if ($sekolah && $sekolah->isLengkap() && ! $sudahIsiIdentitas) {
            $this->dispatch('open-modal', 'identitas-bosp-belum-lengkap');
        }
    }

    public function tutupSuksesProfilLengkap(): void
    {
        $this->tampilkanSuksesProfilLengkap = false;
        $this->dispatch('close-modal', 'identitas-bosp-sukses');
    }

    public function tutupPeringatanIdentitasBelumLengkap(): void
    {
        $this->dispatch('close-modal', 'identitas-bosp-belum-lengkap');
    }

    protected function bolehKelolaSemua(): bool
    {
        return auth()->user()->isSuperadmin();
    }

    protected function bolehEdit(int $profilSekolahId): bool
    {
        return $this->bolehKelolaSemua() || auth()->user()->profil_sekolah_id === $profilSekolahId;
    }

    public function isi(int $profilSekolahId): void
    {
        abort_unless($this->bolehEdit($profilSekolahId), 403);

        $identitas = PendataanBosp::where('profil_sekolah_id', $profilSekolahId)->first();

        $this->sekolahId = $profilSekolahId;
        $this->editingId = $identitas?->id;
        $this->nuptk = (string) $identitas?->nuptk;
        $this->nama = (string) $identitas?->nama;
        $this->nip = (string) $identitas?->nip;
        $this->jk = (string) $identitas?->jk;
        $this->tempat_lahir = (string) $identitas?->tempat_lahir;
        $this->tanggal_lahir = $identitas?->tanggal_lahir?->format('Y-m-d') ?? '';
        $this->status_kepegawaian = (string) $identitas?->status_kepegawaian;
        $this->pendidikan_terakhir = (string) $identitas?->pendidikan_terakhir;
        $this->jurusan = (string) $identitas?->jurusan;
        $this->nama_perguruan_tinggi = (string) $identitas?->nama_perguruan_tinggi;
        $this->no_whatsapp = (string) $identitas?->no_whatsapp;
        $this->showForm = true;
        $this->dispatch('open-modal', 'identitas-bosp-form');
    }

    public function updatedPendidikanTerakhir(): void
    {
        if (! PendataanBosp::butuhJurusan($this->pendidikan_terakhir)) {
            $this->jurusan = '';
            $this->nama_perguruan_tinggi = '';
        }
    }

    public function resetForm(): void
    {
        $this->reset([
            'sekolahId', 'editingId', 'nuptk', 'nama', 'nip', 'jk', 'tempat_lahir',
            'tanggal_lahir', 'status_kepegawaian', 'pendidikan_terakhir', 'jurusan',
            'nama_perguruan_tinggi', 'no_whatsapp',
        ]);
        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'identitas-bosp-form');
    }

    public function simpan(): void
    {
        abort_unless($this->sekolahId && $this->bolehEdit($this->sekolahId), 403);

        $butuhJurusan = PendataanBosp::butuhJurusan($this->pendidikan_terakhir);

        $validated = $this->validate([
            'nuptk' => ['nullable', 'string', 'max:30'],
            'nama' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:30'],
            'jk' => ['required', Rule::in(array_keys(PendataanBosp::JK_OPTIONS))],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'status_kepegawaian' => ['required', Rule::in(array_keys(PendataanBosp::STATUS_KEPEGAWAIAN_OPTIONS))],
            'pendidikan_terakhir' => ['required', Rule::in(array_keys(PendataanBosp::PENDIDIKAN_OPTIONS))],
            'jurusan' => [$butuhJurusan ? 'required' : 'nullable', 'string', 'max:255'],
            'nama_perguruan_tinggi' => [$butuhJurusan ? 'required' : 'nullable', 'string', 'max:255'],
            'no_whatsapp' => ['required', 'regex:/^[0-9]{1,12}$/'],
        ], [
            'no_whatsapp.regex' => 'No Whatsapp harus berupa angka, maksimal 12 digit.',
        ]);

        if (! $butuhJurusan) {
            $validated['jurusan'] = null;
            $validated['nama_perguruan_tinggi'] = null;
        }

        foreach (['nuptk', 'nip', 'tempat_lahir', 'tanggal_lahir'] as $field) {
            $validated[$field] = $validated[$field] ?: null;
        }

        $validated['created_by'] = auth()->id();

        PendataanBosp::updateOrCreate(
            ['profil_sekolah_id' => $this->sekolahId],
            $validated
        );

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'identitas-bosp-form');
        // Supaya menu di sidebar langsung update kalau Identitas Admin
        // BOSP baru saja selesai diisi, tanpa perlu F5/refresh manual.
        $this->dispatch('kelengkapan-diperbarui');
        session()->flash('status', 'Identitas Admin BOSP berhasil disimpan.');
    }

    public function render()
    {
        $query = ProfilSekolah::with('identitasBosp');

        if (! $this->bolehKelolaSemua()) {
            $query->where('id', auth()->user()->profil_sekolah_id);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('nama_sekolah', 'like', "%{$this->search}%")
                    ->orWhereHas('identitasBosp', function ($q2) {
                        $q2->where('nama', 'like', "%{$this->search}%");
                    });
            });
        }

        $daftarSekolah = $query
            ->orderByRaw("CASE WHEN status = 'negeri' THEN 0 WHEN status = 'swasta' THEN 1 ELSE 2 END")
            ->orderByRaw('kecamatan IS NULL')
            ->orderBy('kecamatan')
            ->orderBy('nama_sekolah')
            ->get();

        return view('livewire.pendataan-bosp.index', [
            'daftarSekolah' => $daftarSekolah,
            'jkOptions' => PendataanBosp::JK_OPTIONS,
            'statusKepegawaianOptions' => PendataanBosp::STATUS_KEPEGAWAIAN_OPTIONS,
            'pendidikanOptions' => PendataanBosp::PENDIDIKAN_OPTIONS,
            'butuhJurusan' => PendataanBosp::butuhJurusan($this->pendidikan_terakhir),
        ]);
    }
}
