<?php

namespace App\Livewire\PendataanOps\Lampiran2b;

use App\Models\DataPtk;
use App\Models\Lampiran2b;
use App\Models\ProfilSekolah;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Lampiran 2b - keterangan & TMT per PTK, per sekolah, per triwulan.
 *
 * SEJAK permintaan user 2026-10-03: Nama PTK (+ NRG & NUPTK otomatis)
 * diambil dari App\Models\DataPtk (tab "Data PTK" pada menu "Data
 * Sekolah"), BUKAN dari Lampiran 2a lagi seperti sebelumnya - field Nama
 * PTK di sini TETAP berupa pilihan (bukan ketik bebas), hanya sumber
 * datanya yang berubah. Dropdown HANYA menampilkan PTK pada sekolah yang
 * sama dengan form ini, dengan Status Keaktifan "Tidak Aktif" (jawaban
 * AskUserQuestion: "Nama PTK ... diambil dari tab data PTK dengan status
 * Keaktifan TIDAK AKTIF ... Nama PTK yang status keaktifan nya AKTIF
 * jangan dimunculkan") DAN Status Sertifikasi "Sudah" (jawaban
 * AskUserQuestion "Tidak usah muncul - hanya PTK Sudah Sertifikasi",
 * supaya NRG yang otomatis terisi selalu valid 12 digit, tidak pernah
 * "-"). Lihat namaPtkOptions() & cariSumberDataPtk().
 *
 * KEPUTUSAN PENTING (dikonfirmasi AskUserQuestion 2026-10-03): syarat
 * lama "Nama PTK harus ada di Lampiran 2a" DIHAPUS - kalau tetap
 * dipertahankan, Lampiran 2b TIDAK AKAN PERNAH bisa disimpan (karena
 * Lampiran 2a sendiri hanya menampilkan PTK berstatus Keaktifan "Aktif",
 * sehingga PTK "Tidak Aktif" yang dipilih di sini pasti tidak akan
 * pernah cocok dengan Lampiran 2a). simpan() sekarang memvalidasi ulang
 * ke DataPtk (bukan Lampiran2a) sebagai jaring pengaman sisi server.
 *
 * - Superadmin: melihat & mengelola data semua sekolah, bisa memilih
 *   sekolah manapun saat menambah data, dan memfilter tabel per sekolah.
 * - Admin OPS: hanya melihat & mengelola data sekolahnya sendiri (field
 *   Nama Sekolah otomatis terkunci ke sekolahnya).
 *
 * SEJAK permintaan user 2026-10-03: tombol "Import Excel" & "Export
 * Excel" (beserta properti $fileImport/$errorImport/$errorExport &
 * method import()/export()) DIHAPUS dari tab ini - karena Nama PTK
 * sekarang bersumber dari tab Data PTK (lihat paragraf di atas), fitur
 * export-lalu-reimport data Lampiran 2b sudah tidak relevan lagi &
 * berisiko menimbulkan data ganda/konflik. Kelas Lampiran2bExport &
 * Lampiran2bImport TIDAK dihapus dari disk (tidak lagi dipakai di sini)
 * - tombol "Unduh" pada menu Unduhan (App\Livewire\PendataanOps\Unduhan)
 * TETAP ADA & TIDAK terpengaruh, karena memakai App\Exports\
 * UnduhanLampiranExport yang sepenuhnya terpisah.
 */
#[Layout('layouts.app')]
#[Title('Lampiran 2b')]
class Index extends Component
{
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

    /**
     * Nama PTK yang terdeteksi SUDAH tersimpan di Lampiran 2b pada
     * triwulan yang sama (permintaan user 2026-10-16) - dipakai untuk
     * menampilkan pop-up peringatan, lihat updatedNamaPtk() & simpan().
     */
    public string $namaPtkSudahAda = '';

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

    public function updatedFilterSekolahId(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
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
     * Nama PTK dipilih pada form - isi otomatis NRG & NUPTK dari Data PTK
     * (sekolah yang sama) - lihat docblock class di atas.
     *
     * Sejak permintaan user 2026-10-16: kalau NUPTK hasil isi otomatis
     * ini SUDAH tersimpan di Lampiran 2b pada triwulan yang sama, pilihan
     * dibatalkan lagi & pop-up peringatan ditampilkan (pola sama dengan
     * Lampiran 2a) - lihat juga simpan() untuk penegakan sisi server.
     */
    public function updatedNamaPtk(): void
    {
        $this->isiOtomatisNrgNuptk();

        if ($this->nuptk === '') {
            return;
        }

        $sudahAda = Lampiran2b::where('profil_sekolah_id', $this->profil_sekolah_id)
            ->where('triwulan', $this->triwulan)
            ->where('tahun', now()->year)
            ->where('nuptk', $this->nuptk)
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->exists();

        if ($sudahAda) {
            $this->namaPtkSudahAda = $this->nama_ptk;
            $this->nama_ptk = '';
            $this->nrg = '';
            $this->nuptk = '';
            $this->dispatch('open-modal', 'lampiran-2b-ptk-sudah-ada');
        }
    }

    protected function isiOtomatisNrgNuptk(): void
    {
        $sumber = $this->cariSumberDataPtk($this->profil_sekolah_id, $this->nama_ptk);

        $this->nrg = $sumber->nomor_registrasi_guru ?? '';
        $this->nuptk = $sumber->nuptk ?? '';
    }

    /**
     * Cari baris Data PTK sumber NRG/NUPTK (dicocokkan lewat sekolah &
     * Nama PTK yang persis sama) - SENGAJA TIDAK dibatasi lagi di sini
     * oleh status_keaktifan/status_sertifikasi (beda dengan
     * namaPtkOptions() yang membatasi pilihan dropdown) supaya tetap bisa
     * dicocokkan walau status PTK-nya berubah setelah baris Lampiran 2b
     * ini pernah disimpan (pola sama seperti Lampiran2a::edit()).
     */
    protected function cariSumberDataPtk(?int $profilSekolahId, string $namaPtk): ?DataPtk
    {
        if (! $profilSekolahId || $namaPtk === '') {
            return null;
        }

        return DataPtk::query()
            ->where('profil_sekolah_id', $profilSekolahId)
            ->where('nama_ptk', $namaPtk)
            ->first();
    }

    /**
     * Daftar Nama PTK dari Data PTK (sekolah pada form saat ini) untuk
     * pilihan dropdown Nama PTK - HANYA status Keaktifan "Tidak Aktif" &
     * status Sertifikasi "Sudah" (lihat docblock class di atas).
     */
    protected function namaPtkOptions(): Collection
    {
        if (! $this->profil_sekolah_id) {
            return collect();
        }

        return DataPtk::query()
            ->where('profil_sekolah_id', $this->profil_sekolah_id)
            ->where('status_keaktifan', 'Tidak Aktif')
            ->where('status_sertifikasi', DataPtk::STATUS_SERTIFIKASI_SUDAH)
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
            'namaPtkSudahAda',
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

        // NRG & NUPTK selalu dihitung ulang di server dari Data PTK (tidak
        // dipercaya begitu saja dari input client) supaya selalu konsisten
        // dengan Nama PTK yang benar-benar dipilih. Jaring pengaman ini
        // SENGAJA memakai filter yang SAMA PERSIS dengan namaPtkOptions()
        // (Tidak Aktif & Sudah Sertifikasi) - bukan cariSumberDataPtk()
        // yang longgar - supaya tidak ada Nama PTK yang lolos tersimpan
        // kalau sebenarnya sudah tidak memenuhi syarat dropdown saat ini.
        $sumber = DataPtk::query()
            ->where('profil_sekolah_id', $validated['profil_sekolah_id'])
            ->where('nama_ptk', $validated['nama_ptk'])
            ->where('status_keaktifan', 'Tidak Aktif')
            ->where('status_sertifikasi', DataPtk::STATUS_SERTIFIKASI_SUDAH)
            ->first();

        if (! $sumber) {
            $this->addError('nama_ptk', 'Nama PTK tersebut tidak ditemukan di Data PTK dengan Status Keaktifan "Tidak Aktif" & Status Sertifikasi "Sudah" untuk sekolah ini. Silakan lengkapi/periksa kembali Data PTK terlebih dahulu.');

            return;
        }

        // Sumber kebenaran tunggal aturan "1 PTK tidak bisa tersimpan 2x
        // di triwulan yang sama" (permintaan user 2026-10-16) - dicek
        // ulang di sini (bukan cuma di updatedNamaPtk()) supaya tidak
        // bisa "dibengkokkan" lewat devtools.
        $sudahAda = Lampiran2b::where('profil_sekolah_id', $validated['profil_sekolah_id'])
            ->where('triwulan', $this->triwulan)
            ->where('tahun', now()->year)
            ->where('nuptk', $sumber->nuptk)
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->exists();

        if ($sudahAda) {
            $this->namaPtkSudahAda = $validated['nama_ptk'];
            $this->nama_ptk = '';
            $this->nrg = '';
            $this->nuptk = '';
            $this->dispatch('open-modal', 'lampiran-2b-ptk-sudah-ada');

            return;
        }

        $validated['nrg'] = $sumber->nomor_registrasi_guru;
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
        $idHalamanIni = $this->terapkanUrutan($this->queryDasar())->paginate($this->perPage)->pluck('id')->all();
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

    /**
     * Urutan KHUSUS utk tampilan "semua sekolah" (Superadmin, TANPA
     * filter ke 1 sekolah tertentu) - permintaan user 2026-10-16:
     * Status (Negeri dulu, Swasta sesudah) -> Nama Sekolah (A-Z) ->
     * Kecamatan (A-Z). SENGAJA BEDA dari urutan baku aplikasi
     * ProfilSekolah::scopeUrutStandar() (Status -> Kecamatan -> Nama
     * Sekolah) - dikonfirmasi lewat AskUserQuestion bahwa user memang
     * ingin urutan Nama Sekolah didahulukan dari Kecamatan KHUSUS di
     * sini, jadi TIDAK memakai/mengubah scope yang sudah ada tsb.
     * Kalau sedang memfilter ke 1 sekolah tertentu, urutan ini tidak
     * relevan - tetap pakai urutan terbaru (->latest()) spt sebelumnya.
     */
    protected function terapkanUrutan($query)
    {
        if ($this->bolehKelolaSemua() && ! $this->filterSekolahId) {
            return $query
                ->join('profil_sekolah', 'lampiran_2b.profil_sekolah_id', '=', 'profil_sekolah.id')
                ->select('lampiran_2b.*')
                ->orderByRaw('CASE WHEN profil_sekolah.status = ? THEN 0 ELSE 1 END', [ProfilSekolah::STATUS_NEGERI])
                ->orderBy('profil_sekolah.nama_sekolah')
                ->orderBy('profil_sekolah.kecamatan')
                ->orderBy('lampiran_2b.created_at', 'desc');
        }

        return $query->latest();
    }

    /**
     * Ringkasan jumlah data tersimpan (permintaan user 2026-10-16),
     * dirinci per status sekolah (Negeri/Swasta) - HANYA dihitung &
     * ditampilkan saat tampilan "semua sekolah" aktif (sama dgn kondisi
     * di terapkanUrutan() di atas), krn kalau sedang difilter ke 1
     * sekolah rinciannya tidak relevan (pasti 100% salah satu status
     * saja). Menghitung dari query dasar yang SAMA (semua filter lain
     * yang sedang aktif ikut terhitung), bukan cuma 1 halaman yang
     * sedang ditampilkan.
     */
    protected function ringkasan(): ?array
    {
        if (! $this->bolehKelolaSemua() || $this->filterSekolahId) {
            return null;
        }

        $jumlahNegeri = $this->queryDasar()
            ->whereHas('profilSekolah', fn ($q) => $q->where('status', ProfilSekolah::STATUS_NEGERI))
            ->count();
        $jumlahSwasta = $this->queryDasar()
            ->whereHas('profilSekolah', fn ($q) => $q->where('status', ProfilSekolah::STATUS_SWASTA))
            ->count();

        return [
            'negeri' => $jumlahNegeri,
            'swasta' => $jumlahSwasta,
            'total' => $jumlahNegeri + $jumlahSwasta,
        ];
    }

    public function render()
    {
        $daftar = $this->terapkanUrutan($this->queryDasar())->paginate($this->perPage);

        $idHalamanIni = $daftar->pluck('id')->all();
        $this->dipilih = array_values(array_intersect($this->dipilih, $idHalamanIni));
        $semuaTerpilih = count($idHalamanIni) > 0 && count(array_diff($idHalamanIni, $this->dipilih)) === 0;

        return view('livewire.pendataan-ops.lampiran2b.index', [
            'daftar' => $daftar,
            'ringkasan' => $this->ringkasan(),
            'triwulanOptions' => Lampiran2b::TRIWULAN_OPTIONS,
            'sekolahOptions' => ProfilSekolah::urutStandar()->get(['id', 'nama_sekolah']),
            'namaPtkOptions' => $this->namaPtkOptions(),
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            'semuaTerpilih' => $semuaTerpilih,
        ]);
    }
}
