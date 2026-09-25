<?php

namespace App\Livewire\PendataanOps\Unduhan;

use App\Exports\UnduhanLampiranExport;
use App\Models\Lampiran2a;
use App\Models\Lampiran2b;
use App\Models\Lampiran2c;
use App\Models\ProfilSekolah;
use App\Support\SuratTpgGabunganData;
use App\Support\UnduhanLampiranData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Menu Unduhan (Pendataan OPS > Unduhan) - Superadmin/Admin OPS memilih
 * Triwulan (tab) & Tahun, lalu bisa mengunduh gabungan Lampiran 2a+2b+2c
 * untuk pilihan itu, dalam bentuk Excel (.xlsx, 3 sheet) ATAU PDF.
 *
 * - Admin OPS: data yang diunduh selalu hanya sekolahnya sendiri.
 * - Superadmin: data yang diunduh SELALU gabungan SEMUA sekolah yang
 *   terdaftar (diurutkan Status -> Kecamatan -> Nama Sekolah, meniru
 *   urutan menu Profil Sekolah), dengan file bernama "Semua Sekolah_...".
 *   Lembar tanda tangan Kepala Sekolah/Pengawas TIDAK disertakan pada
 *   rekap gabungan ini karena tidak ada satupun Kepala Sekolah/Pengawas
 *   tunggal yang bisa mewakili semua sekolah sekaligus.
 *
 * Perbaikan 2026-09-24 (round kedua puluh dua, permintaan user poin 3 &
 * 4): ditambah bagian BARU "Cetak Surat Rekomendasi, Surat Penghentian
 * TPG dan Surat Pernyataan" - fitur "Cetak"/"Unduh PDF"/"Unduh Word"
 * gabungan ketiga surat TPG (sebelumnya ada di menu "Format Surat
 * Rekomendasi & Pembatalan TPG" round kedua puluh satu, DIPINDAHKAN ke
 * sini: "untuk cetak Gabungan ketiga surat (irit kertas) di pindah ke
 * menu Unduhan berdasarkan triwulan dan tahun") - memakai Triwulan/Tahun
 * yang SAMA dengan filter Lampiran 2a/2b/2c di atas (satu filter
 * dipakai bersama).
 *
 * PENTING - beda pola pemilihan sekolah dgn bagian Lampiran 2a/2b/2c:
 * Surat Rekomendasi/Penghentian TPG/Pernyataan itu SATU surat = SATU
 * sekolah tertentu (ada nama & tanda tangan Kepala Sekolah spesifik per
 * surat), TIDAK bisa "digabung semua sekolah sekaligus" seperti rekap
 * Lampiran. Makanya bagian ini py DROPDOWN PILIH SEKOLAH SENDIRI khusus
 * utk Superadmin ($suratTpgProfilSekolahId, wajib dipilih dulu sebelum
 * Cetak/Unduh) - keputusan diambil via AskUserQuestion 2026-09-24
 * (jawaban: "Tambah dropdown pilih sekolah khusus di bagian ini").
 * Admin OPS TIDAK melihat dropdown ini (otomatis sekolahnya sendiri,
 * sama seperti bagian Lampiran).
 *
 * Data ketiga surat diambil lewat App\Support\SuratTpgGabunganData
 * (diekstrak dari App\Livewire\PendataanOps\SuratTpg\Index supaya bisa
 * dipakai bersama tanpa duplikasi).
 *
 * Perbaikan 2026-09-24 (round kedua puluh tiga, permintaan user poin 2:
 * "harus sama persis posisi suratnya" dgn tab Surat Rekomendasi TPG/Surat
 * Penghentian TPG): round kedua puluh dua SEMPAT memakai kertas/margin
 * default tetap (A4, 2.5/2.5/3/2.5cm) di bagian ini tanpa kontrol apapun,
 * padahal Jenis Kertas/Setting Margin pada menu "Format Surat Rekomendasi
 * & Pembatalan TPG" adalah state tampilan PER SESI (sengaja TIDAK
 * disimpan ke database - lihat docblock App\Livewire\PendataanOps\
 * SuratTpg\Index) sehingga TIDAK bisa otomatis "ikut" ke halaman lain.
 * Supaya user bisa memastikan hasil cetak/unduh di sini benar-benar sama
 * persis dgn menu asalnya, bagian ini SEKARANG punya kontrol Jenis
 * Kertas/Setting Margin sendiri ($suratTpgJenisKertas/$suratTpgMargin*),
 * meniru pola yg sama persis dgn SuratTpg\Index (nilai default identik:
 * A4, Left/Right 2.5cm, Top 3cm, Bottom 2.5cm) - user tinggal
 * menyesuaikan di sini kalau sebelumnya memakai kertas/margin lain pada
 * menu asal.
 */
#[Layout('layouts.app')]
#[Title('Unduhan')]
class Index extends Component
{
    #[Url(as: 'triwulan')]
    public int $triwulan = 1;

    public int $tahun;

    /**
     * Pilihan sekolah KHUSUS utk bagian "Cetak Surat Rekomendasi, Surat
     * Penghentian TPG dan Surat Pernyataan" (round kedua puluh dua) -
     * HANYA dipakai/ditampilkan utk Superadmin (Admin OPS selalu otomatis
     * sekolahnya sendiri, lihat sekolahIdUntukSuratTpg()). TERPISAH dari
     * pemilihan sekolah bagian Lampiran 2a/2b/2c di atas (yang memang
     * tidak punya pemilihan sekolah utk Superadmin).
     */
    public ?int $suratTpgProfilSekolahId = null;

    /**
     * Pengaturan cetak (Jenis Kertas & Setting Margin) KHUSUS bagian
     * "Cetak Surat Rekomendasi, Surat Penghentian TPG dan Surat
     * Pernyataan" (round kedua puluh tiga) - meniru App\Livewire\
     * PendataanOps\SuratTpg\Index apa adanya (nilai default identik),
     * supaya user bisa menyamakan hasil cetak di sini dgn menu asalnya.
     * Sengaja TIDAK disimpan ke database, sama seperti menu asalnya.
     */
    public string $suratTpgJenisKertas = 'a4';

    public float $suratTpgMarginKiri = 2.5;

    public float $suratTpgMarginKanan = 2.5;

    public float $suratTpgMarginAtas = 3.0;

    public float $suratTpgMarginBawah = 2.5;

    public bool $suratTpgTampilSettingMargin = false;

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

    public function pindahTab(int $triwulan): void
    {
        $this->triwulan = $triwulan;
    }

    /**
     * null berarti gabungan SEMUA sekolah (khusus Superadmin) - menu
     * Unduhan ini TIDAK punya filter per-sekolah untuk Superadmin, sesuai
     * permintaan (Superadmin selalu mengunduh data seluruh sekolah).
     */
    protected function sekolahIdUntukUnduhan(): ?int
    {
        return $this->bolehKelolaSemua() ? null : $this->sekolahSayaId();
    }

    protected function sekolahUntukExport(): ?ProfilSekolah
    {
        $id = $this->sekolahIdUntukUnduhan();

        return $id ? ProfilSekolah::find($id) : null;
    }

    /**
     * Nama file: "{Nama Sekolah|Semua Sekolah}_Triwulan {N}_{Tahun}" -
     * dibersihkan dari karakter yang tidak valid untuk nama file Windows.
     */
    protected function namaUntukFile(): string
    {
        $sekolah = $this->sekolahUntukExport();
        $nama = $sekolah ? $sekolah->nama_sekolah : 'Semua Sekolah';
        $nama .= '_Triwulan '.$this->triwulan.'_'.$this->tahun;

        return trim(preg_replace('/[\\\\\/:*?"<>|]/', '-', $nama));
    }

    /**
     * @return array{0: Collection, 1: Collection, 2: Collection}
     */
    protected function ambilData(): array
    {
        $sekolahId = $this->sekolahIdUntukUnduhan();

        return [
            UnduhanLampiranData::ambil(Lampiran2a::query(), 'lampiran_2a', $this->triwulan, $this->tahun, $sekolahId),
            UnduhanLampiranData::ambil(Lampiran2b::query(), 'lampiran_2b', $this->triwulan, $this->tahun, $sekolahId),
            UnduhanLampiranData::ambil(Lampiran2c::query(), 'lampiran_2c', $this->triwulan, $this->tahun, $sekolahId),
        ];
    }

    public function unduhExcel()
    {
        [$baris2a, $baris2b, $baris2c] = $this->ambilData();

        return Excel::download(
            new UnduhanLampiranExport($baris2a, $baris2b, $baris2c, $this->triwulan, $this->tahun, $this->sekolahUntukExport()),
            $this->namaUntukFile().'.xlsx'
        );
    }

    public function unduhPdf()
    {
        [$baris2a, $baris2b, $baris2c] = $this->ambilData();

        $pdf = Pdf::loadView('pdf.unduhan-lampiran', [
            'baris2a' => $baris2a,
            'baris2b' => $baris2b,
            'baris2c' => $baris2c,
            'triwulan' => $this->triwulan,
            'tahun' => $this->tahun,
            'sekolah' => $this->sekolahUntukExport(),
        ])->setPaper('a4', 'landscape');

        // PENTING: Pdf::download() bawaan mengembalikan Illuminate\Http\Response
        // BIASA (bukan BinaryFileResponse/StreamedResponse) - Livewire hanya
        // mengenali unduhan file dari method komponen kalau responsnya salah
        // satu dari 2 jenis itu (lihat SupportFileDownloads::valueIsntAFileResponse()),
        // jadi kalau langsung di-return apa adanya, isi PDF (biner) malah
        // dicoba di-encode sebagai JSON oleh Livewire dan gagal. Solusinya:
        // simpan dulu ke file sementara, lalu pakai response()->download()
        // bawaan Laravel yang menghasilkan BinaryFileResponse.
        $namaFile = $this->namaUntukFile().'.pdf';
        $pathSementara = tempnam(sys_get_temp_dir(), 'unduhan-pdf-').'.pdf';
        file_put_contents($pathSementara, $pdf->output());

        return response()->download($pathSementara, $namaFile)->deleteFileAfterSend(true);
    }

    /**
     * Pilihan Tahun pada dropdown: tahun sekarang selalu tersedia, plus
     * semua tahun yang sudah ada datanya (Lampiran 2a/2b/2c manapun).
     */
    protected function tahunOptions(): Collection
    {
        return collect()
            ->merge(Lampiran2a::query()->whereNotNull('tahun')->distinct()->pluck('tahun'))
            ->merge(Lampiran2b::query()->whereNotNull('tahun')->distinct()->pluck('tahun'))
            ->merge(Lampiran2c::query()->whereNotNull('tahun')->distinct()->pluck('tahun'))
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();
    }

    /**
     * Sekolah yang dipakai utk bagian "Cetak Surat Rekomendasi, Surat
     * Penghentian TPG dan Surat Pernyataan" - Admin OPS selalu sekolahnya
     * sendiri, Superadmin WAJIB pilih dulu lewat $suratTpgProfilSekolahId
     * (dropdown khusus, lihat docblock kelas ini).
     */
    protected function sekolahIdUntukSuratTpg(): ?int
    {
        return $this->bolehKelolaSemua() ? $this->suratTpgProfilSekolahId : $this->sekolahSayaId();
    }

    public function pilihSekolahSuratTpg(?int $id): void
    {
        if (! $this->bolehKelolaSemua()) {
            return;
        }

        if ($id !== null) {
            abort_unless(ProfilSekolah::where('id', $id)->exists(), 404);
        }

        $this->suratTpgProfilSekolahId = $id;
    }

    /** Nama file: "surat-tpg-gabungan-{slug nama sekolah}-tw{N}-{tahun}" (pola sama dgn menu asalnya). */
    protected function namaUntukFileSuratTpg(string $namaSekolah): string
    {
        return 'surat-tpg-gabungan-'.Str::slug($namaSekolah).'-tw'.$this->triwulan.'-'.$this->tahun;
    }

    /** @return array<string, string> */
    public function daftarKertasOptionsSuratTpg(): array
    {
        return [
            'a4' => 'Kertas A4',
            'f4' => 'Kertas HVS/Legal/F4',
        ];
    }

    public function toggleSuratTpgSettingMargin(): void
    {
        $this->suratTpgTampilSettingMargin = ! $this->suratTpgTampilSettingMargin;
    }

    /** Batasi margin ke rentang wajar (0.5cm - 5cm), sama seperti menu asalnya. */
    protected function batasMargin(float $cm): float
    {
        return max(0.5, min(5, $cm));
    }

    public function updatedSuratTpgMarginKiri(): void
    {
        $this->suratTpgMarginKiri = $this->batasMargin($this->suratTpgMarginKiri);
    }

    public function updatedSuratTpgMarginKanan(): void
    {
        $this->suratTpgMarginKanan = $this->batasMargin($this->suratTpgMarginKanan);
    }

    public function updatedSuratTpgMarginAtas(): void
    {
        $this->suratTpgMarginAtas = $this->batasMargin($this->suratTpgMarginAtas);
    }

    public function updatedSuratTpgMarginBawah(): void
    {
        $this->suratTpgMarginBawah = $this->batasMargin($this->suratTpgMarginBawah);
    }

    /**
     * @return array{kertas: string, margin: array{kiri: float, kanan: float, atas: float, bawah: float}}
     */
    protected function pengaturanCetakSuratTpg(): array
    {
        return [
            'kertas' => $this->suratTpgJenisKertas,
            'margin' => [
                'kiri' => $this->batasMargin($this->suratTpgMarginKiri),
                'kanan' => $this->batasMargin($this->suratTpgMarginKanan),
                'atas' => $this->batasMargin($this->suratTpgMarginAtas),
                'bawah' => $this->batasMargin($this->suratTpgMarginBawah),
            ],
        ];
    }

    /**
     * URL tombol "Cetak" gabungan ketiga surat TPG (preview PDF, dibuka
     * lewat target="_blank" - sama seperti pola urlCetak() pada menu
     * asalnya sebelum dipindah). Round kedua puluh tiga: sekarang
     * menyertakan kertas/margin_* sesuai pengaturan yg dipilih user di
     * bagian ini, supaya bisa disamakan persis dgn menu asalnya.
     */
    public function urlCetakSuratTpgGabungan(): ?string
    {
        $sekolahId = $this->sekolahIdUntukSuratTpg();

        if (! $sekolahId) {
            return null;
        }

        $pengaturan = $this->pengaturanCetakSuratTpg();

        return route('pendataan-ops.surat-tpg.cetak-semua', [
            'tahun' => $this->tahun,
            'triwulan' => $this->triwulan,
            'profil_sekolah_id' => $sekolahId,
            'kertas' => $pengaturan['kertas'],
            'margin_kiri' => $pengaturan['margin']['kiri'],
            'margin_kanan' => $pengaturan['margin']['kanan'],
            'margin_atas' => $pengaturan['margin']['atas'],
            'margin_bawah' => $pengaturan['margin']['bawah'],
        ]);
    }

    /** "Unduh PDF" - 1 file PDF berisi ketiga surat TPG berurutan (page-break diantaranya), lihat App\Support\SuratTpgGabunganData. */
    public function unduhSuratTpgGabunganPdf()
    {
        $sekolahId = $this->sekolahIdUntukSuratTpg();

        if (! $sekolahId) {
            $this->addError('suratTpgUmum', 'Pilih salah satu sekolah terlebih dahulu sebelum mencetak/mengunduh.');

            return null;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);
        $pengaturan = $this->pengaturanCetakSuratTpg();
        $data = SuratTpgGabunganData::ambil($sekolahId, $this->tahun, $this->triwulan);
        $data['margin'] = $pengaturan['margin'];
        $ukuranKertas = $pengaturan['kertas'] === 'f4' ? 'folio' : 'a4';

        $pdf = Pdf::loadView('pdf.surat-tpg-gabungan', $data)->setPaper($ukuranKertas, 'portrait');

        $namaFile = $this->namaUntukFileSuratTpg($sekolah->nama_sekolah).'.pdf';
        $pathSementara = tempnam(sys_get_temp_dir(), 'surat-tpg-').'.pdf';
        file_put_contents($pathSementara, $pdf->output());

        return response()->download($pathSementara, $namaFile)->deleteFileAfterSend(true);
    }

    /** "Unduh Word" - 1 file .doc berisi ketiga surat TPG berurutan, lihat App\Support\SuratTpgGabunganData. */
    public function unduhSuratTpgGabunganWord()
    {
        $sekolahId = $this->sekolahIdUntukSuratTpg();

        if (! $sekolahId) {
            $this->addError('suratTpgUmum', 'Pilih salah satu sekolah terlebih dahulu sebelum mencetak/mengunduh.');

            return null;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);
        $data = SuratTpgGabunganData::ambil($sekolahId, $this->tahun, $this->triwulan);
        $data['margin'] = $this->pengaturanCetakSuratTpg()['margin'];

        $html = view('word.surat-tpg-gabungan', $data)->render();
        $namaFile = $this->namaUntukFileSuratTpg($sekolah->nama_sekolah).'.doc';
        $pathSementara = tempnam(sys_get_temp_dir(), 'surat-tpg-').'.doc';
        file_put_contents($pathSementara, $html);

        return response()->download($pathSementara, $namaFile, [
            'Content-Type' => 'application/msword',
        ])->deleteFileAfterSend(true);
    }

    public function render()
    {
        [$baris2a, $baris2b, $baris2c] = $this->ambilData();

        return view('livewire.pendataan-ops.unduhan.index', [
            'triwulanOptions' => Lampiran2a::TRIWULAN_OPTIONS,
            'tahunOptions' => $this->tahunOptions(),
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            'namaSekolahTampil' => $this->sekolahUntukExport()?->nama_sekolah ?? 'Semua Sekolah',
            'jumlah2a' => $baris2a->count(),
            'jumlah2b' => $baris2b->count(),
            'jumlah2c' => $baris2c->count(),
            'sekolahOptionsSuratTpg' => $this->bolehKelolaSemua() ? ProfilSekolah::orderBy('nama_sekolah')->get(['id', 'nama_sekolah']) : null,
            'suratTpgProfilSekolahId' => $this->suratTpgProfilSekolahId,
            'urlCetakSuratTpgGabungan' => $this->urlCetakSuratTpgGabungan(),
            'kertasOptionsSuratTpg' => $this->daftarKertasOptionsSuratTpg(),
        ]);
    }
}
