<?php

namespace App\Livewire\PendataanBosp\LanggananDayaJasa;

use App\Exports\LanggananDayaJasaExport;
use App\Imports\LanggananDayaJasaImport;
use App\Livewire\Concerns\HasZoomTampilan;
use App\Livewire\Concerns\MenolakEditJikaTerkunciVerval;
use App\Models\LanggananDayaJasa;
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
 * Langganan Daya dan Jasa - Pendataan BOSP.
 *
 * Dilacak per Tahun & per Triwulan (4 Tab, pola sama seperti Penerimaan
 * Honor PTK), tabel disusun PER SEKOLAH dengan baris placeholder kosong
 * siap-isi di akhir tiap sekolah (kunci NEGATIF -$sekolah->id) - sesuai
 * jawaban AskUserQuestion 2026-09-10, arsitektur ini disamakan SEJAK
 * AWAL dengan Penerimaan Honor PTK versi TERBARU (bukan versi awal
 * Part 14 yang masih flat+paginasi) supaya menu baru ini tidak perlu
 * lagi round koreksi seperti yang dialami Penerimaan Honor PTK.
 *
 * BEDA dengan Penerimaan Honor PTK: hanya ADA SATU field identitas baris
 * ("Uraian Pembayaran", teks bebas) - bukan dua (NUPTK+Nama Penerima) -
 * dan field ini SENGAJA TIDAK diberi validasi keunikan (boleh duplikat,
 * sesuai jawaban AskUserQuestion), jadi TIDAK ADA logic
 * "xxxSudahDipakai()" seperti nuptkSudahDipakai() pada Honor PTK.
 *
 * NPSN & Nama Sekolah SELALU tampilan read-only (otomatis dari relasi
 * profilSekolah), Jumlah SELALU hasil rumus otomatis (Volume x Tarif
 * Harga - lihat LanggananDayaJasa::hitungJumlah()), keduanya TIDAK BISA
 * diedit dari jalur manapun.
 *
 * - Superadmin: melihat & mengelola data semua sekolah, bisa memilih
 *   sekolah manapun saat menambah/mengedit data, dan memfilter tabel per
 *   sekolah.
 * - Admin BOSP: hanya melihat & mengelola data sekolahnya sendiri (field
 *   Sekolah otomatis terkunci ke sekolahnya).
 */
#[Layout('layouts.app')]
#[Title('Langganan Daya dan Jasa')]
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
     * ulang tiap render() dari data ter-terbaru di database. Kunci
     * NEGATIF (-$sekolah->id) = baris placeholder kosong siap-isi
     * (lihat komentar kelas & render()).
     *
     * @var array<int, array<string, string>>
     */
    public array $baris = [];

    /**
     * Dinaikkan PER BARIS setiap kali ada input langsung di kotak Tarif
     * Harga baris itu yang ditolak - dipakai sebagai bagian wire:key
     * kotak Tarif Harga (wire:ignore) baris tsb.
     *
     * @var array<int, int>
     */
    public array $revisiBaris = [];

    public ?int $profil_sekolah_id = null;

    public string $uraian_pembayaran = '';

    public string $volume = '';

    public string $satuan = '';

    public string $tarif_harga = '';

    public string $tanggal_bayar = '';

    public bool $showForm = false;

    public ?int $confirmingDeleteId = null;

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
     * Nama Sekolah yang selalu read-only otomatis, dan Jumlah yang
     * selalu hasil rumus).
     */
    protected function daftarFieldEditable(): array
    {
        return ['uraian_pembayaran', 'volume', 'satuan', 'tarif_harga', 'tanggal_bayar'];
    }

    public function pindahTab(int $triwulan): void
    {
        $this->triwulan = $triwulan;
    }

    /**
     * Menangkap perubahan pada kotak input LANGSUNG di tabel (property
     * "baris.{rowId}.{field}"). $rowId NEGATIF berarti baris PLACEHOLDER
     * (lihat komentar kelas & render()) - ditangani terpisah oleh
     * updatedBarisBaru() karena belum ada baris sungguhan di database.
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

        $baris = LanggananDayaJasa::find($rowId);

        if (! $baris) {
            // Baris sudah dihapus (mis. dari tab/sesi lain) - abaikan saja,
            // render() berikutnya akan menghilangkan baris ini dari tabel.
            return;
        }

        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
        $this->abortJikaTerkunciVerval($baris->profil_sekolah_id, $this->tahun, $baris->triwulan);

        $nilai = $value === '' || $value === null ? null : $value;

        $rules = match ($field) {
            'uraian_pembayaran' => ['required', 'string', 'max:255'],
            'volume' => ['nullable', 'integer', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'tarif_harga' => ['nullable', 'integer', 'min:0'],
            'tanggal_bayar' => ['nullable', 'date'],
            default => [],
        };

        $validator = Validator::make(['nilai' => $nilai], ['nilai' => $rules], [], ['nilai' => 'Nilai']);

        if ($validator->fails()) {
            $this->addError($name, $validator->errors()->first('nilai'));
            $this->revisiBaris[$rowId] = ($this->revisiBaris[$rowId] ?? 0) + 1;

            return;
        }

        $data = [$field => in_array($field, ['volume', 'tarif_harga'], true) && $nilai !== null ? (int) $nilai : $nilai];

        if (in_array($field, ['volume', 'tarif_harga'], true)) {
            $volumeBaru = $field === 'volume' ? $data['volume'] : $baris->volume;
            $tarifBaru = $field === 'tarif_harga' ? $data['tarif_harga'] : $baris->tarif_harga;
            $data['jumlah'] = LanggananDayaJasa::hitungJumlah($volumeBaru, $tarifBaru);
        }

        $baris->update($data);
    }

    /**
     * Menangani input langsung pada BARIS PLACEHOLDER (baris kosong
     * siap-isi yang SELALU ditampilkan di akhir daftar tiap sekolah).
     * Kuncinya NEGATIF ($idPlaceholder = -$sekolahId). Baris ini BELUM
     * ADA di database - begitu SALAH SATU kotaknya diisi, baris baru
     * langsung DIBUAT untuk sekolah+tahun+triwulan ini, lalu
     * placeholder-nya sendiri otomatis kosong lagi pada render()
     * berikutnya - pola identik updatedBarisBaru() Penerimaan Honor PTK,
     * HANYA SAJA di sini TIDAK ADA pengecekan duplikat apapun (sesuai
     * jawaban AskUserQuestion "Uraian Pembayaran boleh duplikat").
     */
    protected function updatedBarisBaru(string $name, int $idPlaceholder, string $field, mixed $value): void
    {
        $sekolahId = abs($idPlaceholder);

        abort_unless($this->bolehKelola($sekolahId), 403);
        $this->abortJikaTerkunciVerval($sekolahId, $this->tahun, $this->triwulan);

        $nilai = $value === '' || $value === null ? null : $value;

        if ($nilai === null) {
            return;
        }

        $rules = match ($field) {
            // BEDA dengan baris yang SUDAH ADA (uraian_pembayaran
            // 'required' di updated() di atas) - baris placeholder ini
            // bisa mulai diisi dari kotak MANAPUN (mis. Volume duluan),
            // jadi tidak dipaksa wajib di sini.
            'uraian_pembayaran' => ['nullable', 'string', 'max:255'],
            'volume' => ['nullable', 'integer', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'tarif_harga' => ['nullable', 'integer', 'min:0'],
            'tanggal_bayar' => ['nullable', 'date'],
            default => [],
        };

        $validator = Validator::make(['nilai' => $nilai], ['nilai' => $rules], [], ['nilai' => 'Nilai']);

        if ($validator->fails()) {
            $this->addError($name, $validator->errors()->first('nilai'));
            $this->revisiBaris[$idPlaceholder] = ($this->revisiBaris[$idPlaceholder] ?? 0) + 1;

            return;
        }

        $data = [$field => in_array($field, ['volume', 'tarif_harga'], true) ? (int) $nilai : $nilai];

        $data['jumlah'] = LanggananDayaJasa::hitungJumlah(
            $field === 'volume' ? $data['volume'] : null,
            $field === 'tarif_harga' ? $data['tarif_harga'] : null,
        );

        $data['profil_sekolah_id'] = $sekolahId;
        $data['tahun'] = $this->tahun;
        $data['triwulan'] = $this->triwulan;
        $data['created_by'] = auth()->id();

        LanggananDayaJasa::create($data);

        $this->revisiBaris[$idPlaceholder] = ($this->revisiBaris[$idPlaceholder] ?? 0) + 1;
    }

    public function tambah(): void
    {
        $this->resetForm();
        $this->formInstance++;

        if (! $this->bolehKelolaSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        $this->showForm = true;
        $this->dispatch('open-modal', 'langganan-daya-jasa-form');
    }

    public function edit(int $id): void
    {
        $baris = LanggananDayaJasa::findOrFail($id);

        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $this->formInstance++;
        $this->editingId = $baris->id;
        $this->profil_sekolah_id = $baris->profil_sekolah_id;
        $this->uraian_pembayaran = (string) $baris->uraian_pembayaran;
        $this->volume = $baris->volume !== null ? (string) $baris->volume : '';
        $this->satuan = (string) $baris->satuan;
        $this->tarif_harga = $baris->tarif_harga !== null ? (string) $baris->tarif_harga : '';
        $this->tanggal_bayar = $baris->tanggal_bayar?->format('Y-m-d') ?? '';
        $this->showForm = true;
        $this->dispatch('open-modal', 'langganan-daya-jasa-form');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'profil_sekolah_id', 'uraian_pembayaran',
            'volume', 'satuan', 'tarif_harga', 'tanggal_bayar',
        ]);
        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'langganan-daya-jasa-form');
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
            'uraian_pembayaran' => ['required', 'string', 'max:255'],
            'volume' => ['nullable', 'integer', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'tarif_harga' => ['nullable', 'integer', 'min:0'],
            'tanggal_bayar' => ['nullable', 'date'],
        ]);

        foreach (['volume', 'tarif_harga'] as $field) {
            $validated[$field] = $validated[$field] === null ? null : (int) $validated[$field];
        }

        // Sama seperti Penerimaan Honor PTK - PostgreSQL menolak string
        // kosong untuk kolom bertipe date, jadi disamakan eksplisit jadi
        // null kalau kosong (rule 'nullable' Laravel tidak melakukan ini
        // otomatis).
        $validated['tanggal_bayar'] = $validated['tanggal_bayar'] !== null && trim((string) $validated['tanggal_bayar']) !== ''
            ? $validated['tanggal_bayar']
            : null;

        $validated['jumlah'] = LanggananDayaJasa::hitungJumlah($validated['volume'], $validated['tarif_harga']);
        $validated['tahun'] = $this->tahun;
        $validated['triwulan'] = $this->triwulan;

        if ($this->editingId) {
            $baris = LanggananDayaJasa::findOrFail($this->editingId);
            abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
            $baris->update($validated);
        } else {
            $validated['created_by'] = auth()->id();
            LanggananDayaJasa::create($validated);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'langganan-daya-jasa-form');
        session()->flash('status', 'Data Langganan Daya dan Jasa berhasil disimpan.');
    }

    public function konfirmasiHapus(int $id): void
    {
        $baris = LanggananDayaJasa::findOrFail($id);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);

        $this->confirmingDeleteId = $id;
        $this->dispatch('open-modal', 'langganan-daya-jasa-hapus');
    }

    public function batalHapus(): void
    {
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'langganan-daya-jasa-hapus');
    }

    public function hapus(): void
    {
        $baris = LanggananDayaJasa::findOrFail($this->confirmingDeleteId);
        abort_unless($this->bolehKelola($baris->profil_sekolah_id), 403);
        $this->abortJikaTerkunciVerval($baris->profil_sekolah_id, $this->tahun, $baris->triwulan);

        $baris->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'langganan-daya-jasa-hapus');
        session()->flash('status', 'Data Langganan Daya dan Jasa berhasil dihapus.');
    }

    /**
     * Query flat (bukan per-sekolah) - dipakai HANYA oleh export(), sama
     * seperti pola queryDasar() pada Penerimaan Honor PTK.
     */
    protected function queryDasar()
    {
        $query = LanggananDayaJasa::query()
            ->with('profilSekolah')
            ->join('profil_sekolah', 'profil_sekolah.id', '=', 'langganan_daya_jasa.profil_sekolah_id')
            ->where('langganan_daya_jasa.tahun', $this->tahun)
            ->where('langganan_daya_jasa.triwulan', $this->triwulan)
            ->select('langganan_daya_jasa.*');

        if (! $this->bolehKelolaSemua()) {
            $query->where('langganan_daya_jasa.profil_sekolah_id', $this->sekolahSayaId());
        } elseif ($this->filterSekolahId) {
            $query->where('langganan_daya_jasa.profil_sekolah_id', $this->filterSekolahId);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('langganan_daya_jasa.uraian_pembayaran', 'like', "%{$this->search}%")
                    ->orWhere('profil_sekolah.nama_sekolah', 'like', "%{$this->search}%")
                    ->orWhere('profil_sekolah.npsn', 'like', "%{$this->search}%");
            });
        }

        return $query->orderBy('profil_sekolah.nama_sekolah')->orderBy('langganan_daya_jasa.uraian_pembayaran');
    }

    public function export()
    {
        $this->errorExport = null;

        $sekolahId = $this->bolehKelolaSemua() ? $this->filterSekolahId : $this->sekolahSayaId();

        if (! $sekolahId) {
            $this->errorExport = 'Pilih salah satu sekolah pada filter di atas terlebih dahulu sebelum Export Excel, karena lembar tanda tangan Kepala Sekolah & Bendahara BOSP pada hasil export hanya berlaku untuk 1 sekolah.';

            return null;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);

        return Excel::download(
            new LanggananDayaJasaExport(
                $this->queryDasar()->get(),
                $this->triwulan,
                $this->tahun,
                $sekolah
            ),
            'langganan-daya-jasa-triwulan-'.$this->triwulan.'-'.$this->tahun.'.xlsx'
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

            if ($sekolahDiperbolehkan) {
                $this->abortJikaTerkunciVerval($sekolahDiperbolehkan, $this->tahun, $this->triwulan);
            }

            Excel::import(
                new LanggananDayaJasaImport($this->tahun, $this->triwulan, auth()->id(), $sekolahDiperbolehkan),
                $this->fileImport->getRealPath()
            );

            $this->fileImport = null;
            session()->flash('status', 'Import Langganan Daya dan Jasa berhasil.');
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
        $query = ProfilSekolah::with(['langgananDayaJasa' => function ($q) {
            $q->where('tahun', $this->tahun)
                ->where('triwulan', $this->triwulan)
                ->orderBy('uraian_pembayaran');
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
                    ->orWhereHas('langgananDayaJasa', function ($q2) use ($cari) {
                        $q2->where('tahun', $this->tahun)
                            ->where('triwulan', $this->triwulan)
                            ->where('uraian_pembayaran', 'like', "%{$cari}%");
                    });
            });
        }

        // Urutan Negeri dulu baru Swasta (lalu kecamatan & nama sekolah) -
        // pola sama seperti ProfilSekolah::Index/PendataanOps::Index/
        // PendataanBosp::Index/RekapRkas::Index/PenerimaanHonorPtk::Index
        // (diterapkan sejak awal di menu ini, tidak menunggu koreksi).
        $daftarSekolah = $query
            ->orderByRaw("CASE WHEN status = 'negeri' THEN 0 WHEN status = 'swasta' THEN 1 ELSE 2 END")
            ->orderByRaw('kecamatan IS NULL')
            ->orderBy('kecamatan')
            ->orderBy('nama_sekolah')
            ->get();

        $this->baris = [];

        foreach ($daftarSekolah as $sekolah) {
            foreach ($sekolah->langgananDayaJasa as $barisData) {
                $this->baris[$barisData->id] = [
                    'uraian_pembayaran' => (string) $barisData->uraian_pembayaran,
                    'volume' => $barisData->volume !== null ? (string) $barisData->volume : '',
                    'satuan' => (string) $barisData->satuan,
                    'tarif_harga' => $barisData->tarif_harga !== null ? (string) $barisData->tarif_harga : '',
                    'tanggal_bayar' => $barisData->tanggal_bayar?->format('Y-m-d') ?? '',
                ];
            }

            $this->baris[-$sekolah->id] = [
                'uraian_pembayaran' => '', 'volume' => '',
                'satuan' => '', 'tarif_harga' => '', 'tanggal_bayar' => '',
            ];
        }

        $tahunOptions = range(now()->year - 2, now()->year + 1);

        // Jumlah Langganan Daya Jasa UNTUK SELURUH SEKOLAH yang SEDANG
        // DITAMPILKAN (permintaan user 2026-09-16: "tambahkan baris
        // Jumlah Langganan Daya Jasa ... dan kolom Jumlah di total
        // kan"; jawaban AskUserQuestion: "Per sekolah + Total seluruh
        // sekolah") - dihitung dari koleksi $daftarSekolah yang SAMA
        // dengan yang dirender ke tabel, jadi otomatis ikut scope peran
        // (Admin BOSP: sekolahnya sendiri saja) & filter yang sedang
        // aktif - persis pola $totalHonorKeseluruhan pada Penerimaan
        // Honor PTK. Total PER SEKOLAH dihitung langsung di view lewat
        // $sekolah->langgananDayaJasa->sum('jumlah') (koleksi sudah
        // di-eager-load di atas, jadi tidak ada query tambahan).
        $totalDayaJasaKeseluruhan = $daftarSekolah->sum(
            fn (ProfilSekolah $sekolah) => $sekolah->langgananDayaJasa->sum('jumlah')
        );

        return view('livewire.pendataan-bosp.langganan-daya-jasa.index', [
            'daftarSekolah' => $daftarSekolah,
            'triwulanOptions' => LanggananDayaJasa::TRIWULAN_OPTIONS,
            'sekolahOptions' => ProfilSekolah::orderBy('nama_sekolah')->get(['id', 'nama_sekolah']),
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            // Kuncian UI - permintaan user 2026-09-23 (round kesepuluh,
            // poin 1) - lihat docblock lengkap App\Livewire\Concerns\
            // MenolakEditJikaTerkunciVerval::terkunciVervalUntukTampilan().
            'terkunciTriwulanIni' => $this->terkunciVervalUntukTampilan($this->triwulan),
            'tahunOptions' => array_reverse($tahunOptions),
            'totalDayaJasaKeseluruhan' => $totalDayaJasaKeseluruhan,
        ]);
    }
}
