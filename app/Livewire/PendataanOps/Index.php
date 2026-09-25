<?php

namespace App\Livewire\PendataanOps;

use App\Livewire\Concerns\HasZoomTampilan;
use App\Models\PendataanOps;
use App\Models\ProfilSekolah;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Identitas OPS - satu data per sekolah.
 *
 * - Superadmin: melihat seluruh sekolah, bisa mengisi/mengedit Identitas
 *   OPS sekolah manapun.
 * - Admin OPS: hanya melihat & bisa mengisi/mengedit Identitas OPS
 *   sekolahnya sendiri.
 */
#[Layout('layouts.app')]
#[Title('Identitas OPS')]
class Index extends Component
{
    use HasZoomTampilan, WithFileUploads;

    // Filter tabel (khusus Superadmin - Admin OPS hanya lihat 1 baris).
    public string $filterNamaSekolah = '';

    public string $filterNamaOps = '';

    /**
     * Ukuran standar foto OPS (2x3 cm dicetak pada ~300 DPI). Foto yang
     * diupload OTOMATIS di-crop (center-crop) & diperkecil/diperbesar
     * oleh sistem supaya persis pas ukuran ini, berapapun ukuran asli
     * foto yang diupload - admin OPS tidak perlu mengatur apapun.
     */
    private const FOTO_OPS_LEBAR_PX = 236;

    private const FOTO_OPS_TINGGI_PX = 354;

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

    public ?string $foto_ops = null;

    public ?string $sk_ops = null;

    public $fotoOpsBaru = null;

    public $skOpsBaru = null;

    public bool $showForm = false;

    /**
     * Popup sukses "Profil Sekolah baru saja lengkap" - HANYA muncul
     * sekali, tepat saat auto-redirect dari ProfilSekolah\Index terjadi
     * (dibaca dari session flash, bukan dari database), sesuai jawaban
     * klarifikasi user. Diprioritaskan di atas popup peringatan "Identitas
     * OPS belum lengkap" supaya tidak muncul berbarengan pada kunjungan
     * pertama ini.
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
            $this->dispatch('open-modal', 'identitas-ops-sukses');

            return;
        }

        $sekolah = $user->profilSekolah()->first();
        $sudahIsiIdentitas = $sekolah && PendataanOps::where('profil_sekolah_id', $sekolah->id)->exists();

        if ($sekolah && $sekolah->isLengkap() && ! $sudahIsiIdentitas) {
            $this->dispatch('open-modal', 'identitas-ops-belum-lengkap');
        }
    }

    public function tutupSuksesProfilLengkap(): void
    {
        $this->tampilkanSuksesProfilLengkap = false;
        $this->dispatch('close-modal', 'identitas-ops-sukses');
    }

    public function tutupPeringatanIdentitasBelumLengkap(): void
    {
        $this->dispatch('close-modal', 'identitas-ops-belum-lengkap');
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

        $identitas = PendataanOps::where('profil_sekolah_id', $profilSekolahId)->first();

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
        $this->foto_ops = $identitas?->foto_ops;
        $this->sk_ops = $identitas?->sk_ops;
        $this->fotoOpsBaru = null;
        $this->skOpsBaru = null;
        $this->showForm = true;
        $this->dispatch('open-modal', 'identitas-ops-form');
    }

    public function updatedPendidikanTerakhir(): void
    {
        if (! PendataanOps::butuhJurusan($this->pendidikan_terakhir)) {
            $this->jurusan = '';
            $this->nama_perguruan_tinggi = '';
        }
    }

    public function resetForm(): void
    {
        $this->reset([
            'sekolahId', 'editingId', 'nuptk', 'nama', 'nip', 'jk', 'tempat_lahir',
            'tanggal_lahir', 'status_kepegawaian', 'pendidikan_terakhir', 'jurusan',
            'nama_perguruan_tinggi', 'no_whatsapp', 'foto_ops', 'sk_ops',
            'fotoOpsBaru', 'skOpsBaru',
        ]);
        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'identitas-ops-form');
    }

    public function simpan(): void
    {
        abort_unless($this->sekolahId && $this->bolehEdit($this->sekolahId), 403);

        $butuhJurusan = PendataanOps::butuhJurusan($this->pendidikan_terakhir);

        // NIP: wajib 18 digit angka untuk status kepegawaian ASN
        // (PNS/ASN PPPK/ASN PPPK-PW), atau wajib diisi tanda "-" untuk
        // status Non-ASN (Honorer/PTT Yayasan) - meniru pola validasi NIP
        // Bendahara yang sudah baku di ProfilSekolah\Index.
        $statusAsn = in_array($this->status_kepegawaian, ['PNS', 'ASN PPPK', 'ASN PPPK-PW'], true);

        $validated = $this->validate([
            'nuptk' => ['nullable', 'regex:/^[0-9]{16}$/'],
            'nama' => ['required', 'string', 'max:255'],
            'nip' => $statusAsn
                ? ['nullable', 'regex:/^[0-9]{18}$/']
                : ['nullable', 'in:-'],
            'jk' => ['required', Rule::in(array_keys(PendataanOps::JK_OPTIONS))],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'status_kepegawaian' => ['required', Rule::in(array_keys(PendataanOps::STATUS_KEPEGAWAIAN_OPTIONS))],
            'pendidikan_terakhir' => ['required', Rule::in(array_keys(PendataanOps::PENDIDIKAN_OPTIONS))],
            'jurusan' => [$butuhJurusan ? 'required' : 'nullable', 'string', 'max:255'],
            'nama_perguruan_tinggi' => [$butuhJurusan ? 'required' : 'nullable', 'string', 'max:255'],
            'no_whatsapp' => ['required', 'regex:/^[0-9]{1,12}$/'],
            'fotoOpsBaru' => ['nullable', 'image', 'max:5120'],
            'skOpsBaru' => ['nullable', 'file', 'mimes:pdf', 'max:1024'],
        ], [
            'nuptk.regex' => 'NUPTK harus berupa 16 digit angka. Silakan periksa dan ketik ulang.',
            'nip.regex' => 'NIP harus berupa 18 digit angka untuk status kepegawaian PNS/ASN PPPK/ASN PPPK-PW. Silakan periksa dan ketik ulang.',
            'nip.in' => 'NIP untuk status kepegawaian Honorer/PTT Yayasan harus diisi tanda "-".',
            'no_whatsapp.regex' => 'No Whatsapp harus berupa angka, maksimal 12 digit.',
            'fotoOpsBaru.image' => 'Photo OPS harus berupa file gambar (JPG/PNG).',
            'skOpsBaru.mimes' => 'Upload SK OPS harus berupa file PDF.',
            'skOpsBaru.max' => 'Ukuran file SK OPS maksimal 1 MB. Silakan upload ulang dengan ukuran file yang lebih kecil (misalnya hasil scan dengan resolusi lebih rendah).',
        ]);

        if (! $butuhJurusan) {
            $validated['jurusan'] = null;
            $validated['nama_perguruan_tinggi'] = null;
        }

        foreach (['nuptk', 'nip', 'tempat_lahir', 'tanggal_lahir'] as $field) {
            $validated[$field] = $validated[$field] ?: null;
        }

        unset($validated['fotoOpsBaru'], $validated['skOpsBaru']);

        $identitasLama = $this->editingId ? PendataanOps::find($this->editingId) : null;

        if ($this->fotoOpsBaru) {
            $validated['foto_ops'] = $this->simpanFotoOps($this->fotoOpsBaru, $identitasLama?->foto_ops);
        }

        if ($this->skOpsBaru) {
            $validated['sk_ops'] = $this->skOpsBaru->store('sk-ops', 'public');
            if ($identitasLama?->sk_ops) {
                Storage::disk('public')->delete($identitasLama->sk_ops);
            }
        }

        $validated['created_by'] = auth()->id();

        PendataanOps::updateOrCreate(
            ['profil_sekolah_id' => $this->sekolahId],
            $validated
        );

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'identitas-ops-form');
        // Supaya menu di sidebar (mis. "Lampiran 2a/2b/2c") langsung
        // muncul kalau Identitas OPS baru saja selesai diisi, tanpa perlu
        // F5/refresh manual.
        $this->dispatch('kelengkapan-diperbarui');
        session()->flash('status', 'Identitas OPS berhasil disimpan.');
    }

    /**
     * Foto OPS yang diupload OTOMATIS di-crop (center-crop) & diperkecil/
     * diperbesar oleh sistem supaya persis ukuran standar 2x3
     * (236x354 px, setara 2x3 cm pada ~300 DPI), berapapun ukuran/rasio
     * asli foto yang diupload - admin OPS tidak perlu mengatur apapun.
     */
    private function simpanFotoOps($fotoBaru, ?string $fotoLama): string
    {
        $manager = new ImageManager(new Driver);

        $gambar = $manager->read($fotoBaru->getRealPath())
            ->cover(self::FOTO_OPS_LEBAR_PX, self::FOTO_OPS_TINGGI_PX);

        $namaFile = 'foto-ops/'.Str::random(40).'.jpg';
        Storage::disk('public')->put($namaFile, (string) $gambar->toJpeg(90));

        if ($fotoLama) {
            Storage::disk('public')->delete($fotoLama);
        }

        return $namaFile;
    }

    public function render()
    {
        $kelolaSemua = $this->bolehKelolaSemua();

        $query = ProfilSekolah::with('identitasOps');

        if (! $kelolaSemua) {
            $query->where('id', auth()->user()->profil_sekolah_id);
        } else {
            if ($this->filterNamaSekolah !== '') {
                $query->where('id', $this->filterNamaSekolah);
            }
            if ($this->filterNamaOps !== '') {
                $query->whereHas('identitasOps', fn ($q) => $q->where('nama', $this->filterNamaOps));
            }
        }

        $daftarSekolah = $query
            ->orderByRaw("CASE WHEN status = 'negeri' THEN 0 WHEN status = 'swasta' THEN 1 ELSE 2 END")
            ->orderByRaw('kecamatan IS NULL')
            ->orderBy('kecamatan')
            ->orderBy('nama_sekolah')
            ->get();

        return view('livewire.pendataan-ops.index', [
            'daftarSekolah' => $daftarSekolah,
            'jkOptions' => PendataanOps::JK_OPTIONS,
            'statusKepegawaianOptions' => PendataanOps::STATUS_KEPEGAWAIAN_OPTIONS,
            'pendidikanOptions' => PendataanOps::PENDIDIKAN_OPTIONS,
            'bolehKelolaSemua' => $kelolaSemua,
            'filterNamaSekolahOptions' => $kelolaSemua
                ? ProfilSekolah::orderBy('nama_sekolah')->pluck('nama_sekolah', 'id')
                : collect(),
            'filterNamaOpsOptions' => $kelolaSemua
                ? PendataanOps::whereNotNull('nama')->where('nama', '!=', '')->distinct()->orderBy('nama')->pluck('nama', 'nama')
                : collect(),
            'butuhJurusan' => PendataanOps::butuhJurusan($this->pendidikan_terakhir),
        ]);
    }
}
