<?php

namespace App\Livewire\PendataanOps\Lampiran2a;

use App\Models\DataPtk;
use App\Models\Lampiran2a;
use App\Models\ProfilSekolah;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Lampiran 2a - riwayat gaji pokok PTK per sekolah, per triwulan.
 *
 * - Superadmin: melihat & mengelola data semua sekolah, bisa memilih
 *   sekolah manapun saat menambah data, dan memfilter tabel per sekolah.
 * - Admin OPS: hanya melihat & mengelola data sekolahnya sendiri (field
 *   Nama Sekolah otomatis terkunci ke sekolahnya).
 *
 * Sejak permintaan user 2026-10-01: field Nama PTK pada form Tambah/Edit
 * sekarang dipilih dari dropdown $dataPtkId (bukan diketik bebas lagi),
 * bersumber dari App\Models\DataPtk (tab "Data PTK" pada menu "Data
 * Sekolah") - HANYA menampilkan PTK pada sekolah yang sama dengan baris
 * ini (dikonfirmasi AskUserQuestion: "Hanya PTK dari sekolah yang sama"),
 * berstatus Sertifikasi "Sudah" & Keaktifan "Aktif", diurutkan Jenis PTK
 * -> Jabatan -> Status Kepegawaian -> Pangkat/Golongan (lihat
 * DataPtk::jenisPtkUrutan()/jabatanUrutan()/statusKepegawaianUrutan()/
 * pangkatGolonganUrutan(), dikonfirmasi AskUserQuestion "Kepsek dulu,
 * jabatan fungsional tertinggi dulu"). Memilih PTK otomatis mengisi NRG
 * (dari Nomor Registrasi Guru Data PTK) & NUPTK (dari NUPTK Data PTK) -
 * lihat updatedDataPtkId().
 *
 * KOMPATIBILITAS DATA LAMA (keputusan desain, belum dikonfirmasi user -
 * mohon dikoreksi kalau kurang sesuai): tabel `data_ptk` MASIH KOSONG
 * sampai masing-masing sekolah selesai mengisi Data PTK-nya, jadi field
 * Nama PTK/NRG/NUPTK TIDAK dibuat wajib terhubung ke Data PTK - kalau
 * $dataPtkId tidak dipilih (tidak ada PTK yang cocok/qualifying di Data
 * PTK), ketiga field itu TETAP bisa diisi bebas seperti sebelumnya
 * (fallback), supaya data lama & sekolah yang belum sempat mengisi Data
 * PTK tidak langsung tidak bisa dipakai. Saat edit data lama, dicoba
 * dicocokkan otomatis ke Data PTK lewat Nama PTK + sekolah yang sama
 * (lihat edit()) - kalau ketemu, dropdown otomatis terisi; kalau tidak,
 * form kembali ke mode ketik bebas.
 *
 * SEJAK permintaan user 2026-10-03: tombol "Import Excel" & "Export
 * Excel" (beserta properti $fileImport/$errorImport/$errorExport &
 * method import()/export()) DIHAPUS dari tab ini - karena Nama PTK
 * sekarang bersumber dari tab Data PTK (lihat paragraf di atas), fitur
 * export-lalu-reimport data Lampiran 2a sudah tidak relevan lagi &
 * berisiko menimbulkan data ganda/konflik. Kelas Lampiran2aExport &
 * Lampiran2aImport TIDAK dihapus dari disk (tidak lagi dipakai di sini)
 * - tombol "Unduh" pada menu Unduhan (App\Livewire\PendataanOps\Unduhan)
 * TETAP ADA & TIDAK terpengaruh, karena memakai App\Exports\
 * UnduhanLampiranExport yang sepenuhnya terpisah.
 */
#[Layout('layouts.app')]
#[Title('Lampiran 2a')]
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
     * PTK yang cocok di Data PTK untuk sekolah ini).
     */
    public ?int $dataPtkId = null;

    public string $nrg = '';

    public string $nuptk = '';

    public string $nama_ptk = '';

    public string $status_kepegawaian = '';

    public string $gaji_pokok_januari = '';

    public string $npwp = '';

    public bool $showForm = false;

    public ?int $confirmingDeleteId = null;

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

    public function updatedPerPage(): void
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

        // Coba cocokkan otomatis ke Data PTK (sekolah sama + Nama PTK
        // sama persis) supaya dropdown PTK terisi saat edit data lama -
        // lihat catatan "KOMPATIBILITAS DATA LAMA" di docblock class.
        // Sengaja TIDAK dibatasi status_sertifikasi/status_keaktifan di
        // sini (beda dengan daftarPtkUntukPilihan()) supaya data lama
        // tetap bisa tercocok walau status PTK-nya sekarang sudah
        // berubah (mis. sudah Tidak Aktif).
        $this->dataPtkId = DataPtk::where('profil_sekolah_id', $baris->profil_sekolah_id)
            ->where('nama_ptk', $baris->nama_ptk)
            ->value('id');

        $this->showForm = true;
        $this->dispatch('open-modal', 'lampiran-2a-form');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'profil_sekolah_id', 'dataPtkId', 'nrg', 'nuptk', 'nama_ptk',
            'status_kepegawaian', 'gaji_pokok_januari', 'npwp',
        ]);
        $this->resetErrorBag();
    }

    /**
     * Saat Superadmin mengganti sekolah di form (wire:model.live), PTK
     * terpilih sebelumnya pasti sudah tidak relevan (daftar PTK berbeda
     * per sekolah) - dikosongkan supaya tidak ada NRG/NUPTK dari sekolah
     * lain yang kebawa.
     */
    public function updatedProfilSekolahId(): void
    {
        // PTK & NRG/NUPTK/Nama PTK yang sebelumnya terisi pasti milik
        // sekolah LAMA (daftar PTK berbeda per sekolah) - dikosongkan
        // semua supaya tidak ada data PTK sekolah lain yang kebawa ke
        // sekolah baru.
        $this->dataPtkId = null;
        $this->nrg = '';
        $this->nuptk = '';
        $this->nama_ptk = '';
    }

    /**
     * Memilih PTK dari dropdown Data PTK otomatis mengisi NRG (Nomor
     * Registrasi Guru) & NUPTK dari data PTK tsb (permintaan user
     * 2026-10-01) - Nama PTK ikut terisi lewat label pilihan yang sama,
     * ditulis ulang di sini juga supaya nilai yang TERSIMPAN (bukan
     * cuma yang tertampil) selalu konsisten dengan Data PTK.
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
    }

    /**
     * Daftar PTK yang bisa dipilih pada dropdown Nama PTK - hanya dari
     * sekolah yang sama dengan form ini, berstatus Sertifikasi "Sudah" &
     * Keaktifan "Aktif", diurutkan Jenis PTK -> Jabatan -> Status
     * Kepegawaian -> Pangkat/Golongan (tertinggi ke terendah). Lihat
     * docblock class untuk rincian keputusan ini.
     *
     * @return \Illuminate\Support\Collection<int, DataPtk>
     */
    protected function daftarPtkUntukPilihan(): \Illuminate\Support\Collection
    {
        if (! $this->profil_sekolah_id) {
            return collect();
        }

        $jenisUrutan = DataPtk::jenisPtkUrutan();
        $jabatanUrutan = DataPtk::jabatanUrutan();
        $statusUrutan = DataPtk::statusKepegawaianUrutan();
        $pangkatUrutan = DataPtk::pangkatGolonganUrutan();

        return DataPtk::query()
            ->where('profil_sekolah_id', $this->profil_sekolah_id)
            ->where('status_sertifikasi', DataPtk::STATUS_SERTIFIKASI_SUDAH)
            ->where('status_keaktifan', 'Aktif')
            ->get()
            ->sort(function (DataPtk $a, DataPtk $b) use ($jenisUrutan, $jabatanUrutan, $statusUrutan, $pangkatUrutan) {
                $cmp = ($jenisUrutan[$a->jenis_ptk] ?? 99) <=> ($jenisUrutan[$b->jenis_ptk] ?? 99);
                if ($cmp !== 0) {
                    return $cmp;
                }

                $cmp = ($jabatanUrutan[$a->jabatan] ?? 99) <=> ($jabatanUrutan[$b->jabatan] ?? 99);
                if ($cmp !== 0) {
                    return $cmp;
                }

                $cmp = ($statusUrutan[$a->status_kepegawaian] ?? 99) <=> ($statusUrutan[$b->status_kepegawaian] ?? 99);
                if ($cmp !== 0) {
                    return $cmp;
                }

                return ($pangkatUrutan[$a->pangkat_golongan] ?? 99) <=> ($pangkatUrutan[$b->pangkat_golongan] ?? 99);
            })
            ->values();
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
        $idHalamanIni = $this->queryDasar()->latest()->paginate($this->perPage)->pluck('id')->all();
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

    public function render()
    {
        $daftar = $this->queryDasar()->latest()->paginate($this->perPage);

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
            'sekolahOptions' => ProfilSekolah::urutStandar()->get(['id', 'nama_sekolah']),
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            'tahunSekarang' => now()->year,
            'semuaTerpilih' => $semuaTerpilih,
            'daftarPtkOptions' => $this->daftarPtkUntukPilihan(),
        ]);
    }
}
