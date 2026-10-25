<?php

namespace App\Livewire\PendataanBosp\UnduhanLpjBosp;

use App\Exports\LpjBospExport;
use App\Models\AksesDataLog;
use App\Models\LaporanRealisasiBosp;
use App\Models\ProfilSekolah;
use App\Models\TahunAnggaranSetting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Menu baru "Unduhan LPJ BOSP" (grup Pendataan BOSP, permintaan user
 * 2026-10-25) - tombol unduhan 1 file Excel gabungan (19 sheet, lihat
 * App\Exports\LpjBospExport) berdasarkan Triwulan+Tahun yang dipilih:
 *
 * - Admin BOSP: selalu otomatis sekolahnya sendiri (tanpa dropdown
 *   pilihan sekolah, sama seperti pola menu lain di grup Pendataan
 *   BOSP).
 * - Superadmin: boleh pilih SATU sekolah tertentu (dropdown, pola sama
 *   seperti App\Livewire\PendataanOps\Unduhan\Index::$filterSekolahUnduhan)
 *   ATAU biarkan kosong untuk "Semua Sekolah" (gabungan seluruh sekolah
 *   - sheet Formulir BOS K7b/K7c otomatis DILEWATI pada mode ini,
 *   dikonfirmasi lewat AskUserQuestion 2026-10-25, lihat docblock
 *   App\Exports\LpjBospExport).
 *
 * Triwulan/Tahun TIDAK dibagikan dengan menu Pendataan BOSP lain
 * (filter sendiri - jawaban AskUserQuestion 2026-10-25: menu baru
 * berdiri sendiri di grup "Pendataan BOSP", direkomendasikan).
 */
#[Layout('layouts.app')]
#[Title('Unduhan LPJ BOSP')]
class Index extends Component
{
    #[Url(as: 'triwulan')]
    public int $triwulan = 1;

    public int $tahun;

    /**
     * Pilihan sekolah KHUSUS Superadmin (null = "Semua Sekolah", 1 file
     * gabungan - lihat docblock App\Exports\LpjBospExport). Admin BOSP
     * selalu otomatis sekolahnya sendiri, lihat sekolahIdUntukUnduhan().
     */
    public ?int $filterSekolahLpj = null;

    public function mount(): void
    {
        // Pola sama seperti menu lain Pendataan BOSP (fitur Tahun
        // Anggaran, Fase 2, 2026-10-24): Superadmin tetap bebas (tahun
        // berjalan), Admin BOSP terkunci ke tahun yang dipilih saat
        // login (session 'tahun_sesi') - dropdown Tahun disembunyikan
        // total untuk mereka di Blade.
        $this->tahun = $this->bolehKelolaSemua()
            ? now()->year
            : (int) session('tahun_sesi', TahunAnggaranSetting::current()->tahun_aktif);
    }

    protected function bolehKelolaSemua(): bool
    {
        return auth()->user()->isSuperadmin();
    }

    protected function sekolahSayaId(): ?int
    {
        return auth()->user()->profil_sekolah_id;
    }

    public function pindahTab(int $triwulan): void
    {
        $this->triwulan = $triwulan;
    }

    /** null berarti "Semua Sekolah". Admin BOSP selalu otomatis sekolahnya sendiri. */
    protected function sekolahIdUntukUnduhan(): ?int
    {
        return $this->bolehKelolaSemua() ? $this->filterSekolahLpj : $this->sekolahSayaId();
    }

    protected function sekolahUntukExport(): ?ProfilSekolah
    {
        $id = $this->sekolahIdUntukUnduhan();

        return $id ? ProfilSekolah::find($id) : null;
    }

    /**
     * Nama file SESUAI PERMINTAAN USER PERSIS: "File LPJ BOSP Triwulan
     * [otomatis] Tahun [otomatis]_SMP [otomatis]" - dibersihkan dari
     * karakter yang tidak valid untuk nama file Windows (pola sama
     * seperti App\Livewire\PendataanOps\Unduhan\Index::namaUntukFile()).
     */
    protected function namaUntukFile(): string
    {
        $sekolah = $this->sekolahUntukExport();
        $namaSekolah = $sekolah ? $sekolah->nama_sekolah : 'Semua Sekolah';
        $nama = 'File LPJ BOSP Triwulan '.$this->triwulan.' Tahun '.$this->tahun.'_'.$namaSekolah;

        return trim(preg_replace('/[\\\\\/:*?"<>|]/', '-', $nama));
    }

    public function unduhExcel()
    {
        AksesDataLog::catat(AksesDataLog::JENIS_UNDUH, 'LPJ BOSP (Unduhan Gabungan)', 'Excel');

        return Excel::download(
            new LpjBospExport($this->triwulan, $this->tahun, $this->sekolahUntukExport()),
            $this->namaUntukFile().'.xlsx'
        );
    }

    protected function tahunOptions(): array
    {
        return array_reverse(range(now()->year - 2, now()->year + 1));
    }

    public function render()
    {
        return view('livewire.pendataan-bosp.unduhan-lpj-bosp.index', [
            'triwulanOptions' => LaporanRealisasiBosp::TRIWULAN_OPTIONS,
            'tahunOptions' => $this->tahunOptions(),
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            'sekolahOptions' => $this->bolehKelolaSemua() ? ProfilSekolah::urutStandar()->get(['id', 'nama_sekolah']) : null,
            'namaSekolahTampil' => $this->sekolahUntukExport()?->nama_sekolah ?? 'Semua Sekolah',
        ]);
    }
}
