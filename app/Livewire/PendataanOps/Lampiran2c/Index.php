<?php

namespace App\Livewire\PendataanOps\Lampiran2c;

use App\Models\DataPtk;
use App\Models\Lampiran2c;
use App\Models\ProfilSekolah;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Lampiran 2c - Daftar Penyesuaian Gaji Pokok PTK, per triwulan (1-4).
 *
 * SEJAK permintaan user 2026-10-03: field Nama PTK pada form Tambah/Edit
 * dipilih dari dropdown $dataPtkId (bukan diketik bebas lagi), bersumber
 * dari App\Models\DataPtk (tab "Data PTK" pada menu "Data Sekolah") -
 * HANYA PTK berstatus Keaktifan "Aktif" (jawaban AskUserQuestion: "Nama
 * PTK ... diambil dari tab data ptk dengan status keaktifan AKTIF ...
 * Nama PTK yang Status Keaktifannya TIDAK AKTIF jangan dimunculkan") &
 * Status Sertifikasi "Sudah" (jawaban AskUserQuestion "Tidak usah muncul
 * - hanya PTK Sudah Sertifikasi", supaya NRG yang otomatis terisi selalu
 * valid 12 digit). BERBEDA dengan Lampiran 2a (sekolah dipilih DULU, baru
 * daftar PTK muncul): di sini urutannya DIBALIK - Nama PTK dipilih DULU,
 * lalu NRG, NUPTK, dan Tempat Tugas (Nama Sekolah) otomatis terisi dari
 * PTK yang dipilih (jawaban AskUserQuestion "untuk NRG, NUPTK dan Nama
 * Sekolah otomatis muncul ketika admin ops pilih nama ptk"). Lihat
 * updatedDataPtkId() & daftarPtkUntukPilihan().
 *
 * Cakupan dropdown Nama PTK (dikonfirmasi AskUserQuestion 2026-10-03):
 * - Admin OPS: HANYA PTK dari sekolahnya sendiri (konsisten dengan Admin
 *   OPS yang memang selalu terkunci ke sekolahnya sendiri di semua menu
 *   lain - menghindari risiko salah sekolah, karena Tempat Tugas Admin
 *   OPS selalu dipaksa ke sekolahnya sendiri saat simpan()).
 * - Superadmin: SEMUA PTK dari SEMUA sekolah (tidak ada sekolah yang
 *   dipilih duluan di sini, beda dengan Lampiran 2a) - label pilihan
 *   menyertakan nama sekolah supaya mudah dibedakan.
 *
 * KOMPATIBILITAS DATA LAMA: field Nama PTK/NRG/NUPTK/Tempat Tugas TIDAK
 * dibuat wajib terhubung ke Data PTK - kalau $dataPtkId tidak dipilih
 * (PTK belum terdata/belum qualifying di Data PTK), form tetap bisa diisi
 * manual seperti sebelumnya (pola sama seperti Lampiran 2a). Saat edit
 * data lama, dicoba dicocokkan otomatis ke Data PTK lewat Nama PTK +
 * sekolah yang sama (lihat edit()).
 *
 * - Superadmin: melihat & mengelola data semua sekolah, bisa memilih
 *   sekolah manapun (Tempat Tugas) saat menambah data, dan memfilter
 *   tabel per sekolah.
 * - Admin OPS: hanya melihat & mengelola data sekolahnya sendiri (field
 *   Tempat Tugas otomatis terkunci ke sekolahnya).
 *
 * SEJAK permintaan user 2026-10-03: tombol "Import Excel" & "Export
 * Excel" (beserta properti $fileImport/$errorImport/$errorExport &
 * method import()/export()) DIHAPUS dari tab ini - karena Nama PTK
 * sekarang bersumber dari tab Data PTK (lihat paragraf di atas), fitur
 * export-lalu-reimport data Lampiran 2c sudah tidak relevan lagi &
 * berisiko menimbulkan data ganda/konflik. Kelas Lampiran2cExport &
 * Lampiran2cImport TIDAK dihapus dari disk (tidak lagi dipakai di sini)
 * - tombol "Unduh" pada menu Unduhan (App\Livewire\PendataanOps\Unduhan)
 * TETAP ADA & TIDAK terpengaruh, karena memakai App\Exports\
 * UnduhanLampiranExport yang sepenuhnya terpisah.
 */
#[Layout('layouts.app')]
#[Title('Lampiran 2c')]
class Index extends Component
{
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

    /**
     * PTK yang dipilih dari Data PTK (lihat docblock class di atas) -
     * null berarti form dalam mode ketik bebas (data lama / belum ada
     * PTK yang cocok di Data PTK).
     */
    public ?int $dataPtkId = null;

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

    public int $perPage = 10;

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

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * Memilih PTK dari dropdown Data PTK otomatis mengisi Nama PTK, NRG,
     * NUPTK, DAN Tempat Tugas (Nama Sekolah) dari data PTK tsb (permintaan
     * user 2026-10-03, urutan DIBALIK dari Lampiran 2a - lihat docblock
     * class). Untuk Admin OPS, Tempat Tugas tetap dipaksa ke sekolahnya
     * sendiri saat simpan() terlepas dari nilai ini (lihat simpan()) -
     * pengisian profil_sekolah_id di sini TIDAK berbahaya untuk Admin OPS
     * karena daftarPtkUntukPilihan() sudah membatasi pilihannya hanya ke
     * sekolahnya sendiri.
     */
    public function updatedDataPtkId(): void
    {
        if (! $this->dataPtkId) {
            return;
        }

        $ptk = DataPtk::find($this->dataPtkId);

        if (! $ptk) {
            $this->dataPtkId = null;

            return;
        }

        $this->nama_ptk = $ptk->nama_ptk;
        $this->nrg = (string) ($ptk->nomor_registrasi_guru ?: '');
        $this->nuptk = (string) ($ptk->nuptk ?: '');
        $this->profil_sekolah_id = $ptk->profil_sekolah_id;
    }

    /**
     * Daftar PTK yang bisa dipilih pada dropdown Nama PTK - berstatus
     * Keaktifan "Aktif" & Sertifikasi "Sudah" (lihat docblock class).
     * Admin OPS hanya melihat PTK sekolahnya sendiri; Superadmin melihat
     * PTK dari SEMUA sekolah (tidak ada sekolah yang dipilih dulu di
     * form ini), diurutkan Nama Sekolah lalu Nama PTK supaya PTK dari
     * sekolah yang sama berkelompok.
     *
     * @return \Illuminate\Support\Collection<int, DataPtk>
     */
    protected function daftarPtkUntukPilihan(): \Illuminate\Support\Collection
    {
        return DataPtk::query()
            ->where('status_keaktifan', 'Aktif')
            ->where('status_sertifikasi', DataPtk::STATUS_SERTIFIKASI_SUDAH)
            ->when(! $this->bolehKelolaSemua(), function ($q) {
                $q->where('profil_sekolah_id', $this->sekolahSayaId());
            })
            ->with('profilSekolah')
            ->get()
            ->sort(function (DataPtk $a, DataPtk $b) {
                $cmp = ($a->profilSekolah->nama_sekolah ?? '') <=> ($b->profilSekolah->nama_sekolah ?? '');
                if ($cmp !== 0) {
                    return $cmp;
                }

                return $a->nama_ptk <=> $b->nama_ptk;
            })
            ->values();
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

        // Coba cocokkan otomatis ke Data PTK (sekolah sama + Nama PTK
        // sama persis) supaya dropdown PTK terisi saat edit data lama -
        // lihat catatan "KOMPATIBILITAS DATA LAMA" di docblock class.
        // Sengaja TIDAK dibatasi status_keaktifan/status_sertifikasi di
        // sini (beda dengan daftarPtkUntukPilihan()) supaya data lama
        // tetap bisa tercocok walau status PTK-nya sekarang sudah
        // berubah (pola sama seperti Lampiran2a::edit()).
        $this->dataPtkId = DataPtk::where('profil_sekolah_id', $baris->profil_sekolah_id)
            ->where('nama_ptk', $baris->nama_ptk)
            ->value('id');

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
            'editingId', 'profil_sekolah_id', 'dataPtkId', 'nrg', 'nuptk', 'nama_ptk', 'kecamatan',
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
        $idHalamanIni = $this->queryDasar()->latest()->paginate($this->perPage)->pluck('id')->all();

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

    public function render()
    {
        $daftar = $this->queryDasar()->latest()->paginate($this->perPage);

        $idHalamanIni = $daftar->pluck('id')->all();
        $this->dipilih = array_values(array_intersect($this->dipilih, $idHalamanIni));
        $semuaTerpilih = count($idHalamanIni) > 0 && count(array_diff($idHalamanIni, $this->dipilih)) === 0;

        return view('livewire.pendataan-ops.lampiran2c.index', [
            'daftar' => $daftar,
            'triwulanOptions' => Lampiran2c::TRIWULAN_OPTIONS,
            'sekolahOptions' => ProfilSekolah::urutStandar()->get(['id', 'nama_sekolah']),
            'kecamatanOptions' => Lampiran2c::KECAMATAN_OPTIONS,
            'jenisKepangkatanOptions' => Lampiran2c::JENIS_KEPANGKATAN_OPTIONS,
            'golonganOptions' => Lampiran2c::GOLONGAN_OPTIONS,
            'pangkatBerkalaOptions' => Lampiran2c::PANGKAT_BERKALA_OPTIONS,
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            'semuaTerpilih' => $semuaTerpilih,
            'daftarPtkOptions' => $this->daftarPtkUntukPilihan(),
        ]);
    }
}
