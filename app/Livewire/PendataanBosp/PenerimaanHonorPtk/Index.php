<?php

namespace App\Livewire\PendataanBosp\PenerimaanHonorPtk;

use App\Exports\PenerimaanHonorPtkExport;
use App\Imports\PenerimaanHonorPtkImport;
use App\Livewire\Concerns\HasZoomTampilan;
use App\Livewire\Concerns\MenolakEditJikaTerkunciVerval;
use App\Models\PenerimaanHonorPtk;
use App\Models\ProfilSekolah;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

/**
 * Penerimaan Honor PTK - Pendataan BOSP.
 *
 * Dilacak per Tahun (dropdown Tahun, sama seperti Rekap RKAS) & per
 * Triwulan (4 Tab berwarna: Biru/Ungu/Kuning/Hijau, sama seperti
 * Lampiran 2a tapi dengan skema warna yang beda - lihat
 * PenerimaanHonorPtk::TRIWULAN_OPTIONS).
 *
 * BEDA dengan Rekap RKAS (1 baris tetap per sekolah): 1 sekolah bisa
 * punya BANYAK baris di sini (1 baris = 1 PTK penerima honor, sesuai
 * jawaban AskUserQuestion 2026-09-09 "Banyak baris per sekolah") - baris
 * baru ditambah lewat tombol "Tambah" (form modal, sama seperti
 * Lampiran 2a) ATAU langsung lewat kotak tabel (lihat "baris placeholder"
 * di bawah, sesuai jawaban AskUserQuestion 2026-09-10), field NUPTK/Nama
 * Penerima/Volume/Satuan/Tarif Harga/Tanggal Bayar pada baris yang SUDAH
 * ADA juga bisa diedit LANGSUNG di kotak tabel & tersimpan otomatis
 * begitu pindah kotak (konsep sama seperti Rekap RKAS, sesuai permintaan
 * user poin 4).
 *
 * Tabel disusun PER SEKOLAH (seperti Rekap RKAS, sesuai jawaban
 * AskUserQuestion 2026-09-10) - render() mengambil daftar SEKOLAH
 * (bukan langsung daftar baris PTK) yang berhak dilihat user (Superadmin:
 * semua sekolah, Admin BOSP: sekolahnya sendiri), lalu untuk tiap sekolah
 * ditampilkan seluruh baris PTK yang sudah ada DIIKUTI 1 "baris
 * placeholder" kosong siap-isi di akhir - kuncinya NEGATIF
 * (-$sekolah->id, tidak pernah bentrok dengan ID baris asli yang selalu
 * positif). Begitu salah satu kotak baris placeholder ini diisi (blur
 * dengan nilai tidak kosong), baris PTK BARU langsung dibuat (lihat
 * updatedBarisBaru()) untuk sekolah itu, lalu placeholder-nya sendiri
 * otomatis kosong lagi pada render berikutnya (karena $baris selalu
 * diisi ulang dari database) - siap dipakai lagi untuk PTK berikutnya di
 * sekolah yang sama tanpa perlu tombol Tambah. Karena tabel sekarang
 * selalu menampilkan SEMUA sekolah (seperti Rekap RKAS), TIDAK ADA
 * paginasi lagi pada menu ini (sebelumnya paginate(15) berdasar baris,
 * sekarang tidak relevan lagi karena struktur berbasis sekolah).
 *
 * NPSN & Nama Sekolah SELALU tampilan read-only (otomatis dari relasi
 * profilSekolah), Jumlah Honor Yang Diterima SELALU hasil rumus
 * otomatis (Volume x Tarif Harga - lihat PenerimaanHonorPtk::
 * hitungJumlahHonor()), keduanya TIDAK BISA diedit dari jalur manapun.
 *
 * - Superadmin: melihat & mengelola data semua sekolah, bisa memilih
 *   sekolah manapun saat menambah/mengedit data, dan memfilter tabel per
 *   sekolah.
 * - Admin BOSP: hanya melihat & mengelola data sekolahnya sendiri (field
 *   Sekolah otomatis terkunci ke sekolahnya).
 */
#[Layout('layouts.app')]
#[Title('Penerimaan Honor PTK')]
class Index extends Component
{
    use HasZoomTampilan;
    use MenolakEditJikaTerkunciVerval;
    use WithFileUploads;

    public int $tahun;

    #[Url(as: 'triwulan')]
    public int $triwulan = 1;

    public string $search = '';

    public ?int $filterSekolahId = null;

    public ?int $editingId = null;

    /**
     * Dinaikkan setiap kali form Tambah/Edit dibuka - dipakai sebagai
     * wire:key pada kotak input Rupiah (Tarif Harga, yang wire:ignore)
     * supaya kotaknya selalu ter-refresh.
     */
    public int $formInstance = 0;

    /**
     * Data untuk input LANGSUNG di tiap kotak/kolom tabel (di luar form
     * modal Tambah/Edit) - array 2 dimensi [rowId][field] => nilai, diisi
     * ulang tiap render() dari data ter-terbaru di database. Field-field
     * biasa (bukan Tarif Harga) diikat wire:model.blur langsung ke sini -
     * begitu kotak kehilangan fokus, Livewire mengirim nilainya, ditangkap
     * di updated() di bawah, lalu langsung disimpan (auto-save per kotak).
     *
     * @var array<int, array<string, string>>
     */
    public array $baris = [];

    /**
     * Dinaikkan PER BARIS (bukan global) setiap kali ada input langsung
     * di kotak Tarif Harga baris itu yang ditolak - dipakai sebagai
     * bagian wire:key kotak Tarif Harga (wire:ignore) baris tsb, sama
     * seperti pola $revisiBaris pada Rekap RKAS.
     *
     * @var array<int, int>
     */
    public array $revisiBaris = [];

    public ?int $profil_sekolah_id = null;

    public string $nuptk = '';

    public string $nama_penerima = '';

    public string $volume = '';

    public string $satuan = '';

    public string $tarif_harga = '';

    public string $tanggal_bayar = '';

    public bool $showForm = false;

    public ?int $confirmingDeleteId = null;

    /**
     * ID baris (nyata, bukan placeholder) yang sedang dicentang lewat
     * checkbox "pilih baris" pada tabel - dipakai tombol "Hapus Terpilih"
     * supaya admin bisa hapus banyak data sekaligus tanpa satu-satu
     * (permintaan user 2026-09-26).
     *
     * @var array<int, int>
     */
    public array $dipilih = [];

    public bool $confirmingHapusTerpilih = false;

    public $fileImport = null;

    public ?string $errorImport = null;

    public ?string $errorExport = null;

    public function mount(): void
    {
        $this->tahun = now()->year;
    }

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

    /**
     * Field-field yang bisa diedit LANGSUNG di kotak tabel (di luar NPSN/
     * Nama Sekolah yang selalu read-only otomatis, dan Jumlah Honor yang
     * selalu hasil rumus).
     */
    protected function daftarFieldEditable(): array
    {
        return ['nuptk', 'nama_penerima', 'volume', 'satuan', 'tarif_harga', 'tanggal_bayar'];
    }

    public function pindahTab(int $triwulan): void
    {
        $this->triwulan = $triwulan;
    }

    /**
     * Menangkap perubahan pada kotak input LANGSUNG di tabel (property
     * "baris.{rowId}.{field}") - begitu 1 kotak kehilangan fokus,
     * nilainya divalidasi & langsung disimpan ke database untuk baris
     * PTK terkait. Kalau nilainya tidak valid, TIDAK disimpan - render()
     * berikutnya akan mengisi ulang $baris dari data database yang
     * sebenarnya, sehingga kotaknya otomatis kembali ke nilai semula.
     *
     * $rowId NEGATIF berarti ini baris PLACEHOLDER (baris kosong siap-isi
     * di akhir tiap sekolah, lihat komentar kelas & render()) - ditangani
     * terpisah oleh updatedBarisBaru() karena belum ada baris sungguhan
     * di database untuk diedit, melainkan harus DIBUAT BARU.
     */
    public function updated(string $name, mixed $value): void
    {
        if (! str_starts_with($name, 'baris.')) {
            return;
        }

        $bagian = explode('.', $name);

        if (count($bagian) !== 3) {
            return;
        }

        [, $rowIdMentah, $field] = $bagian;
        $rowId = (int) $rowIdMentah;

        if (! in_array($field, $this->daftarFieldEditable(), true)) {
            return;
        }

        if ($rowId < 0) {
            $this->updatedBarisBaru($name, $rowId, $field, $value);

            return;
        }

        $baris = PenerimaanHonorPtk::find($rowId);

        if (! $baris) {
            // Baris sudah dihapus (mis. dari tab/sesi lain) - abaikan saja,
            // render() berikutnya akan menghilangkan baris ini dari tabel.
            return;
        }

        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
        $this->abortJikaTerkunciVerval($baris->profil_sekolah_id, $this->tahun, $baris->triwulan);

        $nilai = $value === '' || $value === null ? null : $value;

        $rules = match ($field) {
            'nuptk' => ['nullable', 'regex:/^[0-9]{1,16}$/'],
            'nama_penerima' => ['required', 'string', 'max:255'],
            'volume' => ['nullable', 'integer', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'tarif_harga' => ['nullable', 'integer', 'min:0'],
            'tanggal_bayar' => ['nullable', 'date'],
            default => [],
        };

        $validator = Validator::make(['nilai' => $nilai], ['nilai' => $rules], [], ['nilai' => 'Nilai']);

        // PENTING: jangan panggil $validator->fails()/passes() lagi setelah ini
        // - Illuminate\Validation\Validator membuat ulang MessageBag kosong
        // setiap kali passes() dipanggil, jadi pesan error yang ditambahkan
        // manual di bawah (kasus NUPTK duplikat) akan HILANG kalau fails()
        // dipanggil ulang sesudahnya. Simpan hasil & pesan errornya sekali
        // saja ke variabel biasa.
        $pesanError = null;

        if ($validator->fails()) {
            $pesanError = $validator->errors()->first('nilai');
        } elseif ($field === 'nuptk' && $nilai !== null
            && $this->nuptkSudahDipakai($baris->profil_sekolah_id, $nilai, $rowId)) {
            $pesanError = 'NUPTK ini sudah dipakai baris lain pada sekolah, tahun & triwulan yang sama.';
        }

        if ($pesanError !== null) {
            $this->addError($name, $pesanError);
            $this->revisiBaris[$rowId] = ($this->revisiBaris[$rowId] ?? 0) + 1;

            return;
        }

        $data = [$field => in_array($field, ['volume', 'tarif_harga'], true) && $nilai !== null ? (int) $nilai : $nilai];

        if (in_array($field, ['volume', 'tarif_harga'], true)) {
            $volumeBaru = $field === 'volume' ? $data['volume'] : $baris->volume;
            $tarifBaru = $field === 'tarif_harga' ? $data['tarif_harga'] : $baris->tarif_harga;
            $data['jumlah_honor'] = PenerimaanHonorPtk::hitungJumlahHonor($volumeBaru, $tarifBaru);
        }

        $baris->update($data);
    }

    /**
     * Menangani input langsung pada BARIS PLACEHOLDER (baris kosong
     * siap-isi yang SELALU ditampilkan di akhir daftar tiap sekolah -
     * sesuai jawaban AskUserQuestion 2026-09-10 "Ya, seperti itu (baris
     * kosong per sekolah)" & "Otomatis muncul baris kosong baru lagi").
     * Kuncinya NEGATIF ($idPlaceholder = -$sekolahId, tidak pernah
     * bentrok dengan ID baris asli yang selalu positif).
     *
     * Baris placeholder ini BELUM ADA di database - begitu SALAH SATU
     * kotaknya diisi (blur dengan nilai tidak kosong), baris PTK BARU
     * langsung DIBUAT untuk sekolah+tahun+triwulan ini (field lain
     * dibiarkan kosong, bisa diisi lagi lewat kotak baris asli yang baru
     * muncul). Placeholder-nya SENDIRI tidak pernah benar-benar
     * tersimpan sebagai baris tersendiri - pada render() berikutnya,
     * $baris selalu diisi ulang dari database sehingga placeholder ini
     * otomatis kosong lagi & siap dipakai untuk PTK berikutnya di
     * sekolah yang sama, tanpa perlu tombol Tambah.
     */
    protected function updatedBarisBaru(string $name, int $idPlaceholder, string $field, mixed $value): void
    {
        $sekolahId = abs($idPlaceholder);

        abort_unless($this->bolehKelola($sekolahId), 403);
        $this->abortJikaTerkunciVerval($sekolahId, $this->tahun, $this->triwulan);

        $nilai = $value === '' || $value === null ? null : $value;

        if ($nilai === null) {
            // Baris placeholder yang masih kosong / dikosongkan lagi -
            // tidak ada apapun yang perlu dibuat/disimpan.
            return;
        }

        $rules = match ($field) {
            'nuptk' => ['nullable', 'regex:/^[0-9]{1,16}$/'],
            // BEDA dengan baris yang SUDAH ADA (nama_penerima 'required'
            // di updated() di atas) - baris placeholder ini justru
            // dibuat SEDIKIT DEMI SEDIKIT lewat kotak manapun yang
            // diisi duluan (bisa NUPTK/Volume/dst, bukan harus Nama
            // Penerima), jadi tidak dipaksa wajib di sini. Kolom
            // nama_penerima sendiri memang nullable di database.
            'nama_penerima' => ['nullable', 'string', 'max:255'],
            'volume' => ['nullable', 'integer', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'tarif_harga' => ['nullable', 'integer', 'min:0'],
            'tanggal_bayar' => ['nullable', 'date'],
            default => [],
        };

        $validator = Validator::make(['nilai' => $nilai], ['nilai' => $rules], [], ['nilai' => 'Nilai']);

        // PENTING: sama seperti updated() di atas - jangan panggil
        // fails()/passes() dua kali pada validator yang sama.
        $pesanError = null;

        if ($validator->fails()) {
            $pesanError = $validator->errors()->first('nilai');
        } elseif ($field === 'nuptk' && $this->nuptkSudahDipakai($sekolahId, $nilai)) {
            $pesanError = 'NUPTK ini sudah dipakai baris lain pada sekolah, tahun & triwulan yang sama.';
        }

        if ($pesanError !== null) {
            $this->addError($name, $pesanError);
            $this->revisiBaris[$idPlaceholder] = ($this->revisiBaris[$idPlaceholder] ?? 0) + 1;

            return;
        }

        $data = [$field => in_array($field, ['volume', 'tarif_harga'], true) ? (int) $nilai : $nilai];

        // Baris ini baru dibuat sekarang - field lain di luar $field yang
        // baru diisi masih pasti kosong/null, jadi Jumlah Honor SELALU
        // dihitung ulang di sini (bukan hanya kalau $field yang diedit
        // Volume/Tarif Harga) supaya tidak tersimpan NULL - konsisten
        // dengan simpan() (jalur modal) yang juga selalu menghitungnya.
        $data['jumlah_honor'] = PenerimaanHonorPtk::hitungJumlahHonor(
            $field === 'volume' ? $data['volume'] : null,
            $field === 'tarif_harga' ? $data['tarif_harga'] : null,
        );

        $data['profil_sekolah_id'] = $sekolahId;
        $data['tahun'] = $this->tahun;
        $data['triwulan'] = $this->triwulan;
        $data['created_by'] = auth()->id();

        PenerimaanHonorPtk::create($data);

        // Kotak Tarif Harga placeholder (wire:ignore, lihat
        // honor-ptk-tarif-cell.blade.php) tidak otomatis ikut kosong
        // lewat morphing Livewire biasa - dipaksa refresh (wire:key
        // berubah) lewat kenaikan revisi, sama seperti pola $revisiBaris
        // lain di kelas ini.
        $this->revisiBaris[$idPlaceholder] = ($this->revisiBaris[$idPlaceholder] ?? 0) + 1;
    }

    protected function nuptkSudahDipakai(int $profilSekolahId, string $nuptk, ?int $kecualiId = null): bool
    {
        return PenerimaanHonorPtk::where('profil_sekolah_id', $profilSekolahId)
            ->where('tahun', $this->tahun)
            ->where('triwulan', $this->triwulan)
            ->where('nuptk', $nuptk)
            ->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))
            ->exists();
    }

    public function tambah(): void
    {
        $this->resetForm();
        $this->formInstance++;

        if (! $this->bolehKelolaSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        $this->showForm = true;
        $this->dispatch('open-modal', 'penerimaan-honor-ptk-form');
    }

    public function edit(int $id): void
    {
        $baris = PenerimaanHonorPtk::findOrFail($id);

        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $this->formInstance++;
        $this->editingId = $baris->id;
        $this->profil_sekolah_id = $baris->profil_sekolah_id;
        $this->nuptk = (string) $baris->nuptk;
        $this->nama_penerima = (string) $baris->nama_penerima;
        $this->volume = $baris->volume !== null ? (string) $baris->volume : '';
        $this->satuan = (string) $baris->satuan;
        $this->tarif_harga = $baris->tarif_harga !== null ? (string) $baris->tarif_harga : '';
        $this->tanggal_bayar = $baris->tanggal_bayar?->format('Y-m-d') ?? '';
        $this->showForm = true;
        $this->dispatch('open-modal', 'penerimaan-honor-ptk-form');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'profil_sekolah_id', 'nuptk', 'nama_penerima',
            'volume', 'satuan', 'tarif_harga', 'tanggal_bayar',
        ]);
        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'penerimaan-honor-ptk-form');
    }

    public function simpan(): void
    {
        if (! $this->bolehKelolaSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        abort_unless($this->profil_sekolah_id && $this->bolehKelola($this->profil_sekolah_id), 403);
        $this->abortJikaTerkunciVerval($this->profil_sekolah_id, $this->tahun, $this->triwulan);

        $validated = $this->validate([
            'profil_sekolah_id' => ['required', Rule::exists('profil_sekolah', 'id')],
            'nuptk' => [
                'nullable',
                'regex:/^[0-9]{1,16}$/',
                Rule::unique('penerimaan_honor_ptk', 'nuptk')
                    ->where(fn ($q) => $q->where('profil_sekolah_id', $this->profil_sekolah_id)
                        ->where('tahun', $this->tahun)
                        ->where('triwulan', $this->triwulan))
                    ->ignore($this->editingId),
            ],
            'nama_penerima' => ['required', 'string', 'max:255'],
            'volume' => ['nullable', 'integer', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'tarif_harga' => ['nullable', 'integer', 'min:0'],
            'tanggal_bayar' => ['nullable', 'date'],
        ], [
            'nuptk.regex' => 'NUPTK harus berupa angka, maksimal 16 digit.',
            'nuptk.unique' => 'NUPTK ini sudah dipakai baris lain pada sekolah, tahun & triwulan yang sama.',
        ]);

        foreach (['volume', 'tarif_harga'] as $field) {
            $validated[$field] = $validated[$field] === null ? null : (int) $validated[$field];
        }

        // PENTING: "nullable" pada rule Laravel memperlakukan string kosong
        // sama seperti null (rule lain jadi dilewati), TAPI tidak otomatis
        // mengubah nilainya jadi null - kalau dibiarkan '' tersimpan apa
        // adanya, dua baris tanpa NUPTK sekaligus akan bentrok di unique
        // constraint (constraint hanya menganggap NULL sungguhan sebagai
        // "selalu beda", bukan string kosong). Disamakan jadi null di sini.
        $validated['nuptk'] = $validated['nuptk'] !== null && trim($validated['nuptk']) !== ''
            ? trim($validated['nuptk'])
            : null;

        // Sama seperti NUPTK di atas - kolom "tanggal_bayar" bertipe date,
        // Postgres (beda dengan sqlite yang lebih longgar) menolak string
        // kosong sebagai tanggal ("invalid input syntax for type date").
        $validated['tanggal_bayar'] = $validated['tanggal_bayar'] !== null && trim((string) $validated['tanggal_bayar']) !== ''
            ? $validated['tanggal_bayar']
            : null;

        $validated['jumlah_honor'] = PenerimaanHonorPtk::hitungJumlahHonor($validated['volume'], $validated['tarif_harga']);
        $validated['tahun'] = $this->tahun;
        $validated['triwulan'] = $this->triwulan;

        if ($this->editingId) {
            $baris = PenerimaanHonorPtk::findOrFail($this->editingId);
            abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
            $baris->update($validated);
        } else {
            $validated['created_by'] = auth()->id();
            PenerimaanHonorPtk::create($validated);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'penerimaan-honor-ptk-form');
        session()->flash('status', 'Data Penerimaan Honor PTK berhasil disimpan.');
    }

    public function konfirmasiHapus(int $id): void
    {
        $baris = PenerimaanHonorPtk::findOrFail($id);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $this->confirmingDeleteId = $id;
        $this->dispatch('open-modal', 'penerimaan-honor-ptk-hapus');
    }

    public function batalHapus(): void
    {
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'penerimaan-honor-ptk-hapus');
    }

    public function hapus(): void
    {
        $baris = PenerimaanHonorPtk::findOrFail($this->confirmingDeleteId);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
        $this->abortJikaTerkunciVerval($baris->profil_sekolah_id, $this->tahun, $baris->triwulan);

        $baris->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'penerimaan-honor-ptk-hapus');
        session()->flash('status', 'Data Penerimaan Honor PTK berhasil dihapus.');
    }

    /**
     * Centang/batal-centang SEMUA baris nyata yang sedang tampil (lintas
     * semua sekolah yang sedang di-render, sesuai filter/pencarian yang
     * aktif) - dipanggil dari checkbox di header tabel.
     */
    public function toggleSemua(): void
    {
        $idSemua = collect($this->baris)->keys()->filter(fn ($id) => $id > 0)->values()->all();

        if (count($idSemua) > 0 && count(array_diff($idSemua, $this->dipilih)) === 0) {
            $this->dipilih = [];
        } else {
            $this->dipilih = $idSemua;
        }
    }

    public function konfirmasiHapusTerpilih(): void
    {
        if (empty($this->dipilih)) {
            return;
        }

        $this->confirmingHapusTerpilih = true;
        $this->dispatch('open-modal', 'penerimaan-honor-ptk-hapus-terpilih');
    }

    public function batalHapusTerpilih(): void
    {
        $this->confirmingHapusTerpilih = false;
        $this->dispatch('close-modal', 'penerimaan-honor-ptk-hapus-terpilih');
    }

    /**
     * Menghapus SEMUA baris yang sedang dicentang ($dipilih) sekaligus -
     * permintaan user 2026-09-26 supaya admin tidak perlu hapus satu-satu
     * kalau datanya banyak. Setiap baris tetap dicek hak akses & kuncian
     * verval-nya masing-masing sebelum benar-benar dihapus (sama seperti
     * hapus() satuan), sehingga kalau ada 1 baris yang harusnya tidak
     * boleh dihapus (mis. sekolah lain / triwulan terkunci), SELURUH
     * proses dibatalkan (tidak ada yang terhapus sebagian).
     */
    public function hapusTerpilih(): void
    {
        $barisTerpilih = PenerimaanHonorPtk::whereIn('id', $this->dipilih)->get();

        foreach ($barisTerpilih as $baris) {
            abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
            $this->abortJikaTerkunciVerval($baris->profil_sekolah_id, $this->tahun, $baris->triwulan);
        }

        $jumlah = $barisTerpilih->count();

        PenerimaanHonorPtk::whereIn('id', $barisTerpilih->pluck('id'))->delete();

        $this->dipilih = [];
        $this->confirmingHapusTerpilih = false;
        $this->dispatch('close-modal', 'penerimaan-honor-ptk-hapus-terpilih');
        session()->flash('status', "Berhasil menghapus {$jumlah} data Penerimaan Honor PTK sekaligus.");
    }

    protected function queryDasar()
    {
        $query = PenerimaanHonorPtk::query()
            ->with('profilSekolah')
            ->join('profil_sekolah', 'profil_sekolah.id', '=', 'penerimaan_honor_ptk.profil_sekolah_id')
            ->where('penerimaan_honor_ptk.tahun', $this->tahun)
            ->where('penerimaan_honor_ptk.triwulan', $this->triwulan)
            ->select('penerimaan_honor_ptk.*');

        if (! $this->bolehKelolaSemua()) {
            $query->where('penerimaan_honor_ptk.profil_sekolah_id', $this->sekolahSayaId());
        } elseif ($this->filterSekolahId) {
            $query->where('penerimaan_honor_ptk.profil_sekolah_id', $this->filterSekolahId);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('penerimaan_honor_ptk.nama_penerima', 'like', "%{$this->search}%")
                    ->orWhere('penerimaan_honor_ptk.nuptk', 'like', "%{$this->search}%")
                    ->orWhere('profil_sekolah.nama_sekolah', 'like', "%{$this->search}%")
                    ->orWhere('profil_sekolah.npsn', 'like', "%{$this->search}%");
            });
        }

        return $query->orderBy('profil_sekolah.nama_sekolah')->orderBy('penerimaan_honor_ptk.nama_penerima');
    }

    public function export()
    {
        $this->errorExport = null;

        // Lembar tanda tangan Kepala Sekolah & Bendahara BOSP pada hasil
        // export hanya berlaku untuk 1 sekolah, jadi Superadmin wajib
        // memfilter ke 1 sekolah dulu (Admin BOSP otomatis sudah terkunci
        // ke sekolahnya sendiri) - sama seperti pola export Lampiran 2a.
        $sekolahId = $this->bolehKelolaSemua() ? $this->filterSekolahId : $this->sekolahSayaId();

        if (! $sekolahId) {
            $this->errorExport = 'Pilih salah satu sekolah pada filter di atas terlebih dahulu sebelum Export Excel, karena lembar tanda tangan Kepala Sekolah & Bendahara BOSP pada hasil export hanya berlaku untuk 1 sekolah.';

            return null;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);

        return Excel::download(
            new PenerimaanHonorPtkExport(
                $this->queryDasar()->get(),
                $this->triwulan,
                $this->tahun,
                $sekolah
            ),
            'penerimaan-honor-ptk-triwulan-'.$this->triwulan.'-'.$this->tahun.'.xlsx'
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

            // Guard verval HANYA dicek untuk Admin BOSP (1 sekolah pasti
            // diketahui di sini) - import massal Superadmin (lintas
            // banyak sekolah sekaligus, $sekolahDiperbolehkan null) TIDAK
            // dicek per-baris di sini (di luar cakupan perbaikan round
            // keenam ini, lihat catatan App\Livewire\Concerns\MenolakEditJikaTerkunciVerval).
            if ($sekolahDiperbolehkan) {
                $this->abortJikaTerkunciVerval($sekolahDiperbolehkan, $this->tahun, $this->triwulan);
            }

            Excel::import(
                new PenerimaanHonorPtkImport($this->tahun, $this->triwulan, auth()->id(), $sekolahDiperbolehkan),
                $this->fileImport->getRealPath()
            );

            $this->fileImport = null;
            session()->flash('status', 'Import Penerimaan Honor PTK berhasil.');
        } catch (ValidationException $e) {
            $pesan = [];
            foreach ($e->failures() as $failure) {
                $pesan[] = 'Baris '.$failure->row().': '.implode(', ', $failure->errors());
            }
            $this->errorImport = implode(' | ', $pesan);
        }
    }

    public function render()
    {
        // Daftar SEKOLAH (bukan langsung daftar baris PTK) yang berhak
        // dilihat user - sesuai jawaban AskUserQuestion 2026-09-10, tabel
        // sekarang disusun PER SEKOLAH (seperti Rekap RKAS): Superadmin
        // melihat SEMUA sekolah sekaligus (atau 1 sekolah kalau
        // $filterSekolahId dipilih), Admin BOSP hanya sekolahnya sendiri.
        $query = ProfilSekolah::with(['penerimaanHonorPtk' => function ($q) {
            $q->where('tahun', $this->tahun)
                ->where('triwulan', $this->triwulan)
                ->orderBy('nama_penerima');
        }]);

        if (! $this->bolehKelolaSemua()) {
            $query->where('id', $this->sekolahSayaId());
        } elseif ($this->filterSekolahId) {
            $query->where('id', $this->filterSekolahId);
        }

        if ($this->search !== '') {
            $cari = $this->search;
            $query->where(function ($q) use ($cari) {
                $q->where('nama_sekolah', 'like', "%{$cari}%")
                    ->orWhere('npsn', 'like', "%{$cari}%")
                    ->orWhereHas('penerimaanHonorPtk', function ($q2) use ($cari) {
                        $q2->where('tahun', $this->tahun)
                            ->where('triwulan', $this->triwulan)
                            ->where(function ($q3) use ($cari) {
                                $q3->where('nama_penerima', 'like', "%{$cari}%")
                                    ->orWhere('nuptk', 'like', "%{$cari}%");
                            });
                    });
            });
        }

        // Urutan Negeri dulu baru Swasta (lalu kecamatan & nama sekolah) -
        // BUKAN aturan baru yang ditebak, melainkan pola yang SUDAH DIPAKAI
        // persis sama di ProfilSekolah::Index, PendataanOps::Index,
        // PendataanBosp::Index, & RekapRkas::Index (menu yang jadi acuan
        // permintaan user "seperti Rekap RKAS") - disamakan di sini supaya
        // konsisten dengan menu-menu lain.
        $daftarSekolah = $query
            ->orderByRaw("CASE WHEN status = 'negeri' THEN 0 WHEN status = 'swasta' THEN 1 ELSE 2 END")
            ->orderByRaw('kecamatan IS NULL')
            ->orderBy('kecamatan')
            ->orderBy('nama_sekolah')
            ->get();

        // Diisi ULANG dari kosong tiap render (sama seperti sebelumnya) -
        // untuk tiap sekolah, diisi baris-baris PTK yang SUDAH ADA
        // (kunci = ID baris asli, positif) DITAMBAH 1 "baris placeholder"
        // kosong siap-isi di akhir (kunci = -ID sekolah, negatif, lihat
        // komentar kelas & updatedBarisBaru()).
        $this->baris = [];

        foreach ($daftarSekolah as $sekolah) {
            foreach ($sekolah->penerimaanHonorPtk as $barisPtk) {
                $this->baris[$barisPtk->id] = [
                    'nuptk' => (string) $barisPtk->nuptk,
                    'nama_penerima' => (string) $barisPtk->nama_penerima,
                    'volume' => $barisPtk->volume !== null ? (string) $barisPtk->volume : '',
                    'satuan' => (string) $barisPtk->satuan,
                    'tarif_harga' => $barisPtk->tarif_harga !== null ? (string) $barisPtk->tarif_harga : '',
                    'tanggal_bayar' => $barisPtk->tanggal_bayar?->format('Y-m-d') ?? '',
                ];
            }

            $this->baris[-$sekolah->id] = [
                'nuptk' => '', 'nama_penerima' => '', 'volume' => '',
                'satuan' => '', 'tarif_harga' => '', 'tanggal_bayar' => '',
            ];
        }

        // Buang dari $dipilih ID baris yang sudah tidak ada lagi (mis.
        // dihapus dari tab/sesi lain, atau tidak lagi cocok filter/pencarian
        // yang aktif) - supaya checkbox tidak "menempel" ke baris yang sudah
        // tidak tampil.
        $idRealBaris = collect($this->baris)->keys()->filter(fn ($id) => $id > 0)->values()->all();
        $this->dipilih = array_values(array_intersect($this->dipilih, $idRealBaris));
        $semuaTerpilih = count($idRealBaris) > 0 && count(array_diff($idRealBaris, $this->dipilih)) === 0;

        $tahunOptions = range(now()->year - 2, now()->year + 1);

        // Total Jumlah Honor Yang Diterima UNTUK SELURUH SEKOLAH yang
        // SEDANG DITAMPILKAN (permintaan user: "tambahkan baris Total
        // Jumlah Honor Yang Diterima ... dari setiap sekolah dan untuk
        // seluruh sekolah") - dihitung dari koleksi $daftarSekolah yang
        // SAMA dengan yang dirender ke tabel, jadi otomatis ikut scope
        // peran (Admin BOSP: sekolahnya sendiri saja) & filter yang
        // sedang aktif (pencarian/filter sekolah Superadmin), persis pola
        // baris "Jumlah" (total otomatis) pada Rekap RKAS. Total PER
        // SEKOLAH dihitung langsung di view lewat
        // $sekolah->penerimaanHonorPtk->sum('jumlah_honor') (koleksi
        // sudah di-eager-load di atas, jadi tidak ada query tambahan).
        $totalHonorKeseluruhan = $daftarSekolah->sum(
            fn (ProfilSekolah $sekolah) => $sekolah->penerimaanHonorPtk->sum('jumlah_honor')
        );

        return view('livewire.pendataan-bosp.penerimaan-honor-ptk.index', [
            'daftarSekolah' => $daftarSekolah,
            'triwulanOptions' => PenerimaanHonorPtk::TRIWULAN_OPTIONS,
            'sekolahOptions' => ProfilSekolah::orderBy('nama_sekolah')->get(['id', 'nama_sekolah']),
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            'tahunOptions' => array_reverse($tahunOptions),
            'totalHonorKeseluruhan' => $totalHonorKeseluruhan,
            // Kuncian UI - permintaan user 2026-09-23 (round kesepuluh,
            // poin 1) - lihat docblock lengkap App\Livewire\Concerns\
            // MenolakEditJikaTerkunciVerval::terkunciVervalUntukTampilan().
            'terkunciTriwulanIni' => $this->terkunciVervalUntukTampilan($this->triwulan),
            'semuaTerpilih' => $semuaTerpilih,
        ]);
    }
}
