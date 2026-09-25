<?php

namespace App\Livewire\PanduanAplikasi;

use App\Models\PanduanAplikasi;
use App\Models\PanduanAplikasiFile;
use App\Models\PanduanAplikasiLink;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Menu "Panduan Aplikasi" (round 24, poin 6; direstrukturisasi round DUA
 * PULUH LIMA / 2026-09-24, poin 1, permintaan user "upload file lebih dari
 * 1 dengan Judul Book Manual yang sama... termasuk juga untuk link google
 * drive nya juga").
 *
 * Filter "Book Manual" (judul), "Deskripsi", & "Tanggal Upload". CRUD
 * (Tambah/Edit/Hapus) memakai pola modal yang sama dgn App\Livewire\
 * Pengguna\Index.
 *
 * Keputusan bisnis round DUA PULUH LIMA via AskUserQuestion (2026-09-24) -
 * lihat juga docblock migration
 * restructure_panduan_aplikasi_untuk_multi_file_dan_link & App\Models\
 * PanduanAplikasi:
 * - "Struktur Data" -> "Satu Judul = banyak file & link (Recommended)":
 *   Judul & Deskripsi diisi SEKALI, lalu boleh menambahkan BEBERAPA file
 *   (fileBaruList, multi-upload) & BEBERAPA link (linkBaruList, baris
 *   dinamis - tambahLinkBaru()/hapusLinkBaruBaris()) sekaligus di bawah
 *   Judul yang sama. Setiap file/link yang SUDAH tersimpan bisa
 *   diunduh/dibuka & DIHAPUS SATU PER SATU (hapusFile()/hapusLink()) tanpa
 *   menghapus Judul atau file/link lainnya.
 * - "Validasi Wajib Isi" -> "Minimal 1 (file ATAU link) untuk keseluruhan
 *   Judul (Recommended)": SATU Judul boleh disimpan asal TOTAL
 *   keseluruhan (file yang sudah ada + file baru + link yang sudah ada +
 *   link baru) minimal 1 - dicek di simpan(). Aturan yang SAMA berlaku
 *   saat menghapus file/link satu per satu (hapusFile()/hapusLink()
 *   MENOLAK penghapusan kalau itu akan membuat Judul jadi 0 file & 0 link
 *   sekaligus - user harus hapus seluruh Judul lewat hapus() kalau memang
 *   ingin mengosongkannya).
 * - HANYA Superadmin yang bisa membuka menu ini SAMA SEKALI - TIDAK
 *   berubah dari round 24 (Gate 'akses-panduan-aplikasi').
 */
#[Layout('layouts.app')]
#[Title('Panduan Aplikasi')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    /** Filter "Book Manual" (cari di kolom judul). */
    public string $search = '';

    /** Filter "Deskripsi". */
    public string $searchDeskripsi = '';

    /** Filter "Tanggal Upload" (dibandingkan dgn tanggal created_at). */
    public string $filterTanggal = '';

    // State form modal
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $judul = '';

    public string $deskripsi = '';

    /** Beberapa file baru sekaligus (round 25, poin 1) - WithFileUploads array property, input multi-select. */
    public array $fileBaruList = [];

    /** Beberapa baris link Google Drive baru sekaligus (round 25, poin 1) - baris kosong dibuang otomatis saat simpan(). */
    public array $linkBaruList = [''];

    // State konfirmasi hapus (hapus SELURUH Judul, bukan satu file/link)
    public ?int $confirmingDeleteId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSearchDeskripsi(): void
    {
        $this->resetPage();
    }

    public function updatedFilterTanggal(): void
    {
        $this->resetPage();
    }

    public function tambah(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('open-modal', 'panduan-form');
    }

    public function edit(int $id): void
    {
        $panduan = PanduanAplikasi::findOrFail($id);

        $this->editingId = $panduan->id;
        $this->judul = $panduan->judul;
        $this->deskripsi = (string) $panduan->deskripsi;
        $this->fileBaruList = [];
        $this->linkBaruList = [''];
        $this->showForm = true;
        $this->dispatch('open-modal', 'panduan-form');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'judul', 'deskripsi', 'fileBaruList']);
        $this->linkBaruList = [''];
        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'panduan-form');
    }

    /** Tambah satu baris input link Google Drive kosong (round 25, poin 1). */
    public function tambahLinkBaru(): void
    {
        $this->linkBaruList[] = '';
    }

    /** Hapus satu baris input link Google Drive yang BELUM disimpan (round 25, poin 1) - bukan link yang sudah ada di database, itu lewat hapusLink(). */
    public function hapusLinkBaruBaris(int $index): void
    {
        unset($this->linkBaruList[$index]);
        $this->linkBaruList = array_values($this->linkBaruList);

        if ($this->linkBaruList === []) {
            $this->linkBaruList = [''];
        }
    }

    public function simpan(): void
    {
        $validated = $this->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'fileBaruList' => ['array'],
            'fileBaruList.*' => ['nullable', 'file', 'max:'.PanduanAplikasi::UKURAN_MAKS_KB, 'mimes:'.implode(',', PanduanAplikasi::EKSTENSI_DIIZINKAN)],
            'linkBaruList' => ['array'],
            'linkBaruList.*' => ['nullable', 'url', 'max:500'],
        ], [], [
            'judul' => 'Judul Book Manual',
            'deskripsi' => 'Deskripsi',
            'fileBaruList.*' => 'Upload File',
            'linkBaruList.*' => 'Link Google Drive',
        ]);

        $fileBaruValid = collect($this->fileBaruList)->filter();
        $linkBaruValid = collect($validated['linkBaruList'])->map(fn ($l) => trim((string) $l))->filter();

        $panduanLama = $this->editingId ? PanduanAplikasi::find($this->editingId) : null;
        $totalFile = ($panduanLama?->files()->count() ?? 0) + $fileBaruValid->count();
        $totalLink = ($panduanLama?->links()->count() ?? 0) + $linkBaruValid->count();

        // Aturan (jawaban AskUserQuestion round 25 "Minimal 1 (file ATAU
        // link) untuk keseluruhan Judul"): TOTAL file + link (yang sudah
        // ada + yang baru diisi) tidak boleh 0.
        if ($totalFile === 0 && $totalLink === 0) {
            $this->addError('linkBaruList.0', 'Isi minimal salah satu: Link Google Drive atau Upload File.');

            return;
        }

        if ($this->editingId) {
            $panduanLama->update(['judul' => $validated['judul'], 'deskripsi' => $validated['deskripsi']]);
            $panduan = $panduanLama;
        } else {
            $panduan = PanduanAplikasi::create(['judul' => $validated['judul'], 'deskripsi' => $validated['deskripsi']]);
        }

        foreach ($fileBaruValid as $file) {
            $panduan->files()->create([
                'file_path' => $file->store('panduan-aplikasi', 'public'),
                'file_nama_asli' => $file->getClientOriginalName(),
                'file_ukuran' => $file->getSize(),
                'file_mime' => $file->getClientMimeType(),
            ]);
        }

        foreach ($linkBaruValid as $link) {
            $panduan->links()->create(['link_drive' => $link]);
        }

        session()->flash('status', $this->editingId ? 'Panduan berhasil diperbarui.' : 'Panduan berhasil ditambahkan.');

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'panduan-form');
    }

    /** Hapus SATU file yang sudah tersimpan tanpa mempengaruhi Judul atau file/link lain (round 25, poin 1). */
    public function hapusFile(int $fileId): void
    {
        $file = PanduanAplikasiFile::find($fileId);

        if (! $file) {
            return;
        }

        $panduan = $file->panduanAplikasi;

        if ($panduan->files()->count() + $panduan->links()->count() <= 1) {
            session()->flash('errorPanduan', 'Tidak bisa menghapus - minimal harus ada 1 file atau link untuk setiap Judul Book Manual. Hapus seluruh Judul ini jika memang ingin menghapusnya.');

            return;
        }

        Storage::disk('public')->delete($file->file_path);
        $file->delete();
        session()->flash('status', 'File berhasil dihapus.');
    }

    /** Hapus SATU link yang sudah tersimpan tanpa mempengaruhi Judul atau file/link lain (round 25, poin 1). */
    public function hapusLink(int $linkId): void
    {
        $link = PanduanAplikasiLink::find($linkId);

        if (! $link) {
            return;
        }

        $panduan = $link->panduanAplikasi;

        if ($panduan->files()->count() + $panduan->links()->count() <= 1) {
            session()->flash('errorPanduan', 'Tidak bisa menghapus - minimal harus ada 1 file atau link untuk setiap Judul Book Manual. Hapus seluruh Judul ini jika memang ingin menghapusnya.');

            return;
        }

        $link->delete();
        session()->flash('status', 'Link berhasil dihapus.');
    }

    public function konfirmasiHapus(int $id): void
    {
        $this->confirmingDeleteId = $id;
        $this->dispatch('open-modal', 'panduan-hapus');
    }

    public function batalHapus(): void
    {
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'panduan-hapus');
    }

    public function hapus(): void
    {
        $panduan = PanduanAplikasi::with('files')->find($this->confirmingDeleteId);

        foreach ($panduan?->files ?? [] as $file) {
            Storage::disk('public')->delete($file->file_path);
        }

        $panduan?->delete();

        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'panduan-hapus');
        session()->flash('status', 'Panduan berhasil dihapus.');
    }

    public function render()
    {
        $panduan = PanduanAplikasi::query()
            ->with(['files', 'links'])
            ->when($this->search, fn ($q) => $q->where('judul', 'like', '%'.$this->search.'%'))
            ->when($this->searchDeskripsi, fn ($q) => $q->where('deskripsi', 'like', '%'.$this->searchDeskripsi.'%'))
            ->when($this->filterTanggal, fn ($q) => $q->whereDate('created_at', $this->filterTanggal))
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.panduan-aplikasi.index', [
            'panduan' => $panduan,
            'panduanDiedit' => $this->editingId ? PanduanAplikasi::with(['files', 'links'])->find($this->editingId) : null,
        ]);
    }
}
