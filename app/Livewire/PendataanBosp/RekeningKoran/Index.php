<?php

namespace App\Livewire\PendataanBosp\RekeningKoran;

use App\Models\AksesDataLog;
use App\Models\ProfilSekolah;
use App\Models\RekeningKoran;
use App\Models\TahunAnggaranSetting;
use App\Services\RekeningKoranMergeService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use ZipArchive;

/**
 * Upload Rekening Koran - menu baru Pendataan BOSP (permintaan user
 * 2026-11-14). 1 baris per sekolah PER TAHUN (pola sama seperti Dana
 * BOSP Tahap/Pajak BOSP Reguler), 4 field file (Rekening Koran TW1-4,
 * PDF maks 1 MB - validasi SAMA PERSIS pola `sk_ops` di
 * App\Livewire\PendataanOps\Index).
 *
 * ATURAN "1 kolom aktif" (dikonfirmasi lewat AskUserQuestion
 * 2026-11-14, jawaban "Pilih dulu, baru 1 kolom aktif"): sekolah HARUS
 * memilih dulu "Upload untuk Triwulan berapa?" ($triwulanDipilih) -
 * begitu dipilih, HANYA kotak upload TW itu yang aktif/muncul, 3
 * lainnya tetap berupa kartu status biasa (tidak bisa diklik upload).
 * Setelah berhasil upload (atau dibatalkan), $triwulanDipilih kembali
 * null - sekolah bebas memilih TW lain kapan saja sesudahnya untuk
 * giliran upload berikutnya. Ini MURNI logika tampilan, bukan kuncian
 * permanen - keempat TW tetap bisa diisi berurutan satu per satu.
 *
 * Khusus Superadmin (tab "Semua Sekolah", permintaan user "khusus
 * superadmin"): tabel status TW1-4 seluruh sekolah untuk tahun aktif,
 * tombol "Gabungkan" (menggabungkan TW1-4 tiap sekolah yang SUDAH
 * LENGKAP 4 Triwulan menjadi 1 file PDF per sekolah lewat
 * App\Services\RekeningKoranMergeService, disimpan ke disk `public`)
 * dan tombol "Unduh ZIP" (mengemas SELURUH file gabungan itu jadi 1
 * file .zip). Sekolah yang BELUM lengkap 4 TW DILEWATI dari kedua
 * proses ini (dikonfirmasi AskUserQuestion 2026-11-14, jawaban "Lewati
 * sekolah itu") - daftar sekolah yang dilewati ditampilkan ke
 * Superadmin supaya tahu sekolah mana yang masih perlu ditagih.
 *
 * Proses gabung SELALU dibuat ULANG dari file TW1-4 yang ADA SAAT ITU
 * JUGA (baik saat tombol "Gabungkan" ditekan MAUPUN tombol "Unduh ZIP"
 * ditekan) - TIDAK PERNAH mengandalkan hasil gabungan basi dari
 * percobaan sebelumnya, supaya tidak terulang masalah data usang/stale
 * seperti yang pernah terjadi pada fitur lain di aplikasi ini.
 */
#[Layout('layouts.app')]
#[Title('Upload Rekening Koran')]
class Index extends Component
{
    use WithFileUploads;

    public int $tahun;

    #[Url(as: 'tab')]
    public string $tab = 'input';

    public ?int $profil_sekolah_id = null;

    /** Nomor Triwulan (1-4) yang kotak uploadnya SEDANG aktif - null berarti belum ada yang dipilih (semua kartu dalam status "pilih untuk upload"). */
    public ?int $triwulanDipilih = null;

    public $fileBaru = null;

    public ?int $confirmingHapusTw = null;

    public bool $confirmingGabungkan = false;

    public ?string $pesanGabung = null;

    public ?string $errorUnduh = null;

    public function mount(): void
    {
        // Pola sama seperti menu lain Pendataan BOSP (fitur Tahun
        // Anggaran, Fase 2, 2026-10-24): Superadmin tetap bebas (tahun
        // berjalan), Admin BOSP terkunci ke tahun yang dipilih saat
        // login (session 'tahun_sesi').
        $this->tahun = $this->bolehKelolaSemua()
            ? now()->year
            : (int) session('tahun_sesi', TahunAnggaranSetting::current()->tahun_aktif);

        if (! $this->bolehKelolaSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }
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

    /** ID sekolah yang datanya sedang ditampilkan/dikelola - Superadmin lewat dropdown, Admin BOSP selalu sekolahnya sendiri. */
    protected function sekolahAktifId(): ?int
    {
        return $this->bolehKelolaSemua() ? $this->profil_sekolah_id : $this->sekolahSayaId();
    }

    public function pindahTab(string $tab): void
    {
        if (! in_array($tab, ['input', 'semua'], true)) {
            return;
        }

        if ($tab === 'semua' && ! $this->bolehKelolaSemua()) {
            return;
        }

        $this->tab = $tab;
        $this->pesanGabung = null;
        $this->errorUnduh = null;
    }

    /** Pilih sekolah aktif pada tab Input - HANYA Superadmin (Admin BOSP sudah otomatis terkunci sejak mount()). */
    public function pilihSekolah(?int $id): void
    {
        if (! $this->bolehKelolaSemua()) {
            return;
        }

        if ($id !== null) {
            abort_unless(ProfilSekolah::where('id', $id)->exists(), 404);
        }

        $this->profil_sekolah_id = $id;
        $this->triwulanDipilih = null;
        $this->fileBaru = null;
        $this->resetErrorBag();
    }

    /** Buka kotak upload untuk 1 Triwulan tertentu - 3 Triwulan lain otomatis nonaktif selama ini (lihat docblock kelas). */
    public function pilihTriwulanUpload(int $tw): void
    {
        if (! in_array($tw, [1, 2, 3, 4], true)) {
            return;
        }

        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId || ! $this->bolehKelola($sekolahId)) {
            return;
        }

        $this->triwulanDipilih = $tw;
        $this->fileBaru = null;
        $this->resetErrorBag('fileBaru');
    }

    public function batalPilihTriwulanUpload(): void
    {
        $this->triwulanDipilih = null;
        $this->fileBaru = null;
        $this->resetErrorBag('fileBaru');
    }

    /**
     * Upload instan begitu file dipilih (pola sama seperti
     * updatedLogoSekolahBaru() di App\Livewire\ProfilSekolah\Index) -
     * validasi mimes:pdf max:1024 SAMA PERSIS pola `sk_ops`.
     */
    public function updatedFileBaru(): void
    {
        if (! $this->fileBaru || ! $this->triwulanDipilih) {
            return;
        }

        $sekolahId = $this->sekolahAktifId();
        $tw = $this->triwulanDipilih;

        if (! $sekolahId || ! $this->bolehKelola($sekolahId)) {
            $this->fileBaru = null;

            return;
        }

        $validator = Validator::make(
            ['fileBaru' => $this->fileBaru],
            ['fileBaru' => ['required', 'file', 'mimes:pdf', 'max:1024']],
            [
                'fileBaru.mimes' => 'Upload Rekening Koran harus berupa file PDF.',
                'fileBaru.max' => 'Ukuran file Rekening Koran maksimal 1 MB. Silakan upload ulang dengan ukuran file yang lebih kecil (misalnya hasil scan dengan resolusi lebih rendah).',
            ],
            ['fileBaru' => 'Rekening Koran']
        );

        if ($validator->fails()) {
            $this->addError('fileBaru', $validator->errors()->first('fileBaru'));
            $this->fileBaru = null;

            return;
        }

        $kolom = RekeningKoran::kolomTw($tw);

        $baris = RekeningKoran::firstOrNew([
            'profil_sekolah_id' => $sekolahId,
            'tahun' => $this->tahun,
        ]);

        $pathLama = $baris->{$kolom};
        $pathBaru = $this->fileBaru->store('rekening-koran/tw'.$tw, 'public');

        $baris->{$kolom} = $pathBaru;
        if (! $baris->exists) {
            $baris->created_by = auth()->id();
        }
        $baris->save();

        if ($pathLama && Storage::disk('public')->exists($pathLama)) {
            Storage::disk('public')->delete($pathLama);
        }

        $this->fileBaru = null;
        $this->triwulanDipilih = null;
        $this->resetErrorBag('fileBaru');
        session()->flash('status', 'Rekening Koran Triwulan '.$tw.' berhasil diupload.');
    }

    public function konfirmasiHapusTw(int $tw): void
    {
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId || ! $this->bolehKelola($sekolahId)) {
            return;
        }

        $this->confirmingHapusTw = $tw;
        $this->dispatch('open-modal', 'rekening-koran-hapus');
    }

    public function batalHapusTw(): void
    {
        $this->confirmingHapusTw = null;
        $this->dispatch('close-modal', 'rekening-koran-hapus');
    }

    public function hapusTw(): void
    {
        $sekolahId = $this->sekolahAktifId();
        $tw = $this->confirmingHapusTw;

        if ($sekolahId && $tw && $this->bolehKelola($sekolahId)) {
            $kolom = RekeningKoran::kolomTw($tw);
            $baris = RekeningKoran::where('profil_sekolah_id', $sekolahId)
                ->where('tahun', $this->tahun)
                ->first();

            if ($baris && $baris->{$kolom}) {
                if (Storage::disk('public')->exists($baris->{$kolom})) {
                    Storage::disk('public')->delete($baris->{$kolom});
                }

                $baris->update([$kolom => null]);
                session()->flash('status', 'Rekening Koran Triwulan '.$tw.' berhasil dihapus.');
            }
        }

        $this->confirmingHapusTw = null;
        $this->dispatch('close-modal', 'rekening-koran-hapus');
    }

    protected function rekeningKoranAktif(): ?RekeningKoran
    {
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId) {
            return null;
        }

        return RekeningKoran::where('profil_sekolah_id', $sekolahId)
            ->where('tahun', $this->tahun)
            ->first();
    }

    /**
     * Status TW1-4 seluruh sekolah untuk tahun aktif (tab "Semua
     * Sekolah", Superadmin saja) - dipakai mengisi tabel status SEKALIGUS
     * dasar daftar "sekolah dilewati" saat Gabungkan/Unduh ZIP.
     *
     * @return \Illuminate\Support\Collection<int, array{sekolah: ProfilSekolah, baris: ?RekeningKoran, lengkap: bool}>
     */
    protected function statusSemuaSekolah(): \Illuminate\Support\Collection
    {
        $daftarSekolah = ProfilSekolah::urutStandar()->get();
        $semuaBaris = RekeningKoran::where('tahun', $this->tahun)->get()->keyBy('profil_sekolah_id');

        return $daftarSekolah->map(function (ProfilSekolah $sekolah) use ($semuaBaris) {
            $baris = $semuaBaris->get($sekolah->id);

            return [
                'sekolah' => $sekolah,
                'baris' => $baris,
                'lengkap' => $baris ? $baris->lengkap4Tw() : false,
            ];
        });
    }

    /** Nama file aman (buang karakter yang tidak boleh ada di nama file Windows/Linux), spasi & huruf besar/kecil TETAP dipertahankan apa adanya (permintaan user: nama file harus memuat Nama Sekolah yang mudah dibaca). */
    private function namaFileAman(string $nama): string
    {
        $nama = preg_replace('/[\/\\\\:\*\?"<>\|]/', '-', $nama);

        return trim($nama ?? '');
    }

    /**
     * Inti proses gabung - dipanggil ULANG dari awal baik oleh
     * gabungkanSemua() (tombol "Gabungkan") maupun unduhZipSemua()
     * (tombol "Unduh ZIP", supaya isi zip SELALU sinkron dengan file
     * TW1-4 yang ada SAAT ITU, bukan hasil gabungan basi sebelumnya).
     *
     * Menyimpan tiap file gabungan ke disk `public`, folder
     * `rekening-koran-gabungan/{tahun}/`, nama file "{Nama Sekolah}-Rek
     * Koran TW 1-4-{Tahun}.pdf" (persis permintaan user).
     *
     * @return array{berhasil: array<int, string>, dilewati: array<int, string>, pathTersimpan: array<int, string>}
     */
    private function prosesGabungSemuaSekolah(): array
    {
        $layanan = new RekeningKoranMergeService;
        $status = $this->statusSemuaSekolah();

        $berhasil = [];
        $dilewati = [];
        $pathTersimpan = [];

        $folder = 'rekening-koran-gabungan/'.$this->tahun;
        Storage::disk('public')->makeDirectory($folder);

        foreach ($status as $item) {
            /** @var ProfilSekolah $sekolah */
            $sekolah = $item['sekolah'];
            /** @var ?RekeningKoran $baris */
            $baris = $item['baris'];

            if (! $item['lengkap']) {
                $dilewati[] = $sekolah->nama_sekolah;

                continue;
            }

            $pathAbsolut = array_map(
                fn (string $path) => Storage::disk('public')->path($path),
                $baris->pathTw1ke4()
            );

            $isiPdf = $layanan->gabungkan($pathAbsolut);

            $namaFile = $this->namaFileAman($sekolah->nama_sekolah).'-Rek Koran TW 1-4-'.$this->tahun.'.pdf';
            $pathSimpan = $folder.'/'.$namaFile;

            Storage::disk('public')->put($pathSimpan, $isiPdf);

            $berhasil[] = $sekolah->nama_sekolah;
            $pathTersimpan[] = $pathSimpan;
        }

        return [
            'berhasil' => $berhasil,
            'dilewati' => $dilewati,
            'pathTersimpan' => $pathTersimpan,
        ];
    }

    /** Tombol "Gabungkan" - khusus Superadmin, 1 kali klik untuk SEMUA sekolah (dikonfirmasi AskUserQuestion 2026-11-14). */
    public function gabungkanSemua(): void
    {
        if (! $this->bolehKelolaSemua()) {
            return;
        }

        $hasil = $this->prosesGabungSemuaSekolah();

        AksesDataLog::catat(AksesDataLog::JENIS_UNDUH, 'Rekening Koran', 'Gabungkan ('.count($hasil['berhasil']).' sekolah)');

        $pesan = count($hasil['berhasil']).' sekolah berhasil digabungkan (Rekening Koran TW1-4 '.$this->tahun.').';
        if (! empty($hasil['dilewati'])) {
            $pesan .= ' '.count($hasil['dilewati']).' sekolah DILEWATI karena belum lengkap 4 Triwulan: '.implode(', ', $hasil['dilewati']).'.';
        }

        $this->pesanGabung = $pesan;
        $this->confirmingGabungkan = false;
        $this->dispatch('close-modal', 'rekening-koran-gabungkan');
    }

    public function konfirmasiGabungkanSemua(): void
    {
        if (! $this->bolehKelolaSemua()) {
            return;
        }

        $this->confirmingGabungkan = true;
        $this->dispatch('open-modal', 'rekening-koran-gabungkan');
    }

    public function batalGabungkanSemua(): void
    {
        $this->confirmingGabungkan = false;
        $this->dispatch('close-modal', 'rekening-koran-gabungkan');
    }

    /**
     * Tombol "Unduh ZIP" - khusus Superadmin. Menjalankan ULANG proses
     * gabung dari awal (lihat docblock prosesGabungSemuaSekolah()) lalu
     * mengemas SELURUH hasilnya jadi 1 file .zip, nama "Rekening koran
     * SR Cibadak-TW1-4-{Tahun}.zip" (persis permintaan user - WinRAR
     * tidak tersedia secara native di PHP, ZIP dipilih karena user
     * menyebut WinZIP sebagai alternatif yang sama-sama bisa dipakai).
     */
    public function unduhZipSemua()
    {
        $this->errorUnduh = null;

        if (! $this->bolehKelolaSemua()) {
            return null;
        }

        $hasil = $this->prosesGabungSemuaSekolah();

        if (empty($hasil['pathTersimpan'])) {
            $this->errorUnduh = 'Belum ada sekolah yang datanya lengkap 4 Triwulan untuk tahun '.$this->tahun.' - tidak ada file yang bisa diunduh.';

            return null;
        }

        AksesDataLog::catat(AksesDataLog::JENIS_UNDUH, 'Rekening Koran', 'ZIP Gabungan ('.count($hasil['pathTersimpan']).' sekolah)');

        $namaZip = 'Rekening koran SR Cibadak-TW1-4-'.$this->tahun.'.zip';
        $pathSementara = storage_path('app/private/tmp/rekening-koran-'.$this->tahun.'-'.uniqid().'.zip');

        File::ensureDirectoryExists(dirname($pathSementara));

        $zip = new ZipArchive;
        abort_unless($zip->open($pathSementara, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500);

        foreach ($hasil['pathTersimpan'] as $path) {
            $zip->addFile(Storage::disk('public')->path($path), basename($path));
        }

        $zip->close();

        return response()->download($pathSementara, $namaZip)->deleteFileAfterSend(true);
    }

    public function render()
    {
        if ($this->tab === 'semua' && ! $this->bolehKelolaSemua()) {
            $this->tab = 'input';
        }

        return view('livewire.pendataan-bosp.rekening-koran.index', [
            'tab' => $this->tab,
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            'sekolahOptions' => ProfilSekolah::urutStandar()->get(['id', 'nama_sekolah']),
            'tahunOptions' => array_reverse(range(now()->year - 2, now()->year + 1)),
            'sekolahAktif' => $this->sekolahAktifId() ? ProfilSekolah::find($this->sekolahAktifId()) : null,
            'rekeningKoranAktif' => $this->rekeningKoranAktif(),
            'statusSemuaSekolah' => $this->tab === 'semua' ? $this->statusSemuaSekolah() : collect(),
        ]);
    }
}
