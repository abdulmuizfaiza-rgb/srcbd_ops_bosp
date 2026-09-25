<?php

namespace App\Livewire\PendataanOps\SuratTpg;

use App\Livewire\Concerns\HasZoomTampilan;
use App\Models\ProfilSekolah;
use App\Models\SuratTpg;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Menu "Format Surat Rekomendasi & Pembatalan TPG" (Pendataan OPS,
 * permintaan user 2026-09-24, round kedelapan belas, 2 gambar contoh
 * format surat diupload user).
 *
 * 2 tab, KEDUANYA "dibuat berdasarkan tahun dan triwulan" (permintaan
 * user eksplisit) - triwulan di sini TIDAK dipetakan dari bulan (beda
 * dgn Formulir BOS K7 yang berbasis bulan), user langsung memilih
 * triwulan lewat selector, default triwulan=1 (mengikuti konvensi baku
 * "TW-1 default" yang sudah dipakai di menu2 lain seperti Landing Page &
 * Lampiran 2a/2b/2c).
 * - Tab "rekomendasi": Surat Rekomendasi TPG (gambar 1) - 4 kondisi baku.
 * - Tab "penghentian": Surat Penghentian TPG (gambar 2) - 10 alasan baku.
 *
 * Field Nama/NIP Kepala Sekolah, Tempat Tugas (nama sekolah), Nama/NIP
 * Pengawas Pembina SEMUANYA diambil OTOMATIS & LANGSUNG dari
 * App\Models\ProfilSekolah setiap render (TIDAK disalin ke tabel lain) -
 * permintaan user eksplisit ("field nya di ambil otomatis dari menu
 * profil sekolah"). HANYA Nomor Surat & Tanggal Surat yang benar2
 * diketik user & disimpan, ke tabel `surat_tpg` (lihat
 * App\Models\SuratTpg docblock utk detail lengkap keputusan desain &
 * jawaban 4 pertanyaan AskUserQuestion 2026-09-24 terkait fitur ini).
 *
 * Pengaturan cetak (Jenis Kertas/Setting Margin), kontrol Zoom, & pola
 * multi-sekolah (bolehKelolaSemua/sekolahSayaId/bolehKelola/
 * sekolahAktifId) SEMUANYA meniru App\Livewire\PendataanBosp\
 * FormulirBosK7\Index apa adanya (pola baku yang sudah mapan di
 * aplikasi ini) - TIDAK memakai MenolakEditJikaTerkunciVerval (kuncian
 * per-triwulan verval BOSP) krn menu ini bukan bagian dari alur
 * pendataan/verval realisasi BOSP, tidak diminta user, & mengunci menu
 * ini akan menjadi aturan bisnis yang tidak diminta.
 *
 * Perbaikan 2026-09-24 (round kesembilan belas, permintaan user poin 2 &
 * 6): Kop Surat (placeholder "LOGO"+"KOP SEKOLAH" round sebelumnya)
 * diganti gambar yang diupload MANUAL oleh Admin OPS/Superadmin lewat
 * kotak upload pada kop surat itu sendiri ($kopSuratBaru, WithFileUploads) -
 * disimpan per SEKOLAH (App\Models\ProfilSekolah::kop_surat, BUKAN per
 * tahun/triwulan/jenis surat), jadi berlaku otomatis untuk KEDUA tab.
 *
 * Perbaikan 2026-09-24 (round kedua puluh, permintaan user 5 poin): Nomor
 * Surat kini rata kiri, garis bawah di atas nama Pengawas/Kepsek
 * dihapus, blok tanda tangan digeser ke kanan (lihat
 * pdf.partials.surat-tpg-ttd), isi Surat Rekomendasi TPG memakai spasi
 * baris 1,5, & upload Kop Surat sekarang divalidasi wajib landscape +
 * lebar minimal 800px (lihat updatedKopSuratBaru()).
 *
 * Perbaikan 2026-09-24 (round kedua puluh satu): tambah TAB 3 "Surat
 * Pernyataan" (SuratTpg::JENIS_PERNYATAAN) - konsep SAMA seperti 2 tab
 * lain (berdasarkan tahun+triwulan, field Nama/Unit Kerja/Alamat Kantor
 * Kepala Sekolah otomatis dari ProfilSekolah), HANYA field
 * `tahunPelajaran` yang diketik manual & tersimpan otomatis (mirip
 * `nomorSurat` pada 2 tab lain). Tab ini SENGAJA memakai kotak bergaris
 * hitam (beda dgn 2 tab lain yg sudah polos sejak round 19) krn user
 * eksplisit minta "format field seperti gambar yang saya upload" - lihat
 * pdf.partials.surat-tpg-pernyataan-isi docblock.
 *
 * (round kedua puluh satu) Sempat ditambah fitur "Cetak Semua"/"Unduh PDF
 * Semua"/"Unduh Word Semua" di halaman ini juga - permintaan user "surat
 * rekomendasi, Surat Penghentian TPG dan Surat Pernyataan di buat dalam 1
 * halaman supaya lebih irit kertas" (diklarifikasi via AskUserQuestion:
 * "1 halaman" berarti 1 dokumen/1 kali proses gabungan, BUKAN benar2
 * dipepetkan jadi 1 lembar fisik).
 *
 * DIPINDAHKAN round kedua puluh dua (2026-09-24, permintaan user poin 3):
 * fitur "Cetak"/"Unduh PDF"/"Unduh Word" gabungan ketiga surat itu
 * SEKARANG SEPENUHNYA ada di menu Unduhan (App\Livewire\PendataanOps\
 * Unduhan\Index), TIDAK LAGI di halaman ini - method urlCetakSemua()/
 * dataSuratGabungan()/exportPdfSemua()/exportWordSemua() & toolbar
 * "Gabungan ketiga surat (irit kertas)" pada Blade view DIHAPUS dari
 * class ini (logika datanya dipindah ke App\Support\SuratTpgGabunganData
 * supaya dipakai bersama tanpa duplikasi). Tombol Cetak/Unduh PDF/Word
 * PER-TAB yang sudah ada sejak round 18 (urlCetak()/exportPdf()/
 * exportWord()) TIDAK berubah sama sekali.
 *
 * Perbaikan lain round kedua puluh dua (poin 1 & 2, tab Surat Pernyataan
 * saja): kotak bergaris hitam yang round 21 sengaja dipertahankan
 * DIHAPUS (sekarang polos spt 2 tab lain), tulisan "Materai" digeser ke
 * bawah/tengah tepat sebelum nama Kepala Sekolah, Kop Surat TIDAK LAGI
 * ditampilkan pada tab ini, & isi surat memakai spasi baris 1,5 - lihat
 * docblock pdf.partials.surat-tpg-pernyataan-isi utk detail lengkap.
 */
#[Layout('layouts.app')]
#[Title('Format Surat Rekomendasi & Pembatalan TPG')]
class Index extends Component
{
    use HasZoomTampilan, WithFileUploads;

    public int $tahun;

    public int $triwulan = 1;

    #[Url(as: 'tab')]
    public string $tabAktif = 'rekomendasi';

    public ?int $profil_sekolah_id = null;

    public ?string $nomorSurat = null;

    public ?string $tanggalSurat = null;

    /** Tahun Pelajaran ("2025/2026" dst) - HANYA dipakai tab Surat Pernyataan, diketik manual & tersimpan otomatis (round kedua puluh satu). */
    public ?string $tahunPelajaran = null;

    /** File kop surat yang baru dipilih di kotak upload (belum disimpan sampai updatedKopSuratBaru() jalan). */
    public $kopSuratBaru = null;

    /**
     * Pengaturan cetak (Jenis Kertas & Setting Margin) - meniru
     * App\Livewire\PendataanBosp\FormulirBosK7\Index apa adanya, termasuk
     * default margin (Left 2.5cm, Right 2.5cm, Top 3cm, Bottom 2.5cm).
     * Sengaja TIDAK disimpan ke database - hanya state tampilan per
     * sesi/kunjungan, sama seperti kontrol Zoom (HasZoomTampilan).
     */
    public string $jenisKertas = 'a4';

    public float $marginKiri = 2.5;

    public float $marginKanan = 2.5;

    public float $marginAtas = 3.0;

    public float $marginBawah = 2.5;

    public bool $tampilSettingMargin = false;

    public function mount(): void
    {
        $this->tahun = now()->year;

        if (! $this->bolehKelolaSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        $this->muatSurat();
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

    protected function sekolahAktifId(): ?int
    {
        return $this->bolehKelolaSemua() ? $this->profil_sekolah_id : $this->sekolahSayaId();
    }

    public function pindahTab(string $tab): void
    {
        if (! in_array($tab, [SuratTpg::JENIS_REKOMENDASI, SuratTpg::JENIS_PENGHENTIAN, SuratTpg::JENIS_PERNYATAAN], true)) {
            return;
        }

        $this->tabAktif = $tab;
        $this->muatSurat();
    }

    public function pilihSekolah(?int $id): void
    {
        if (! $this->bolehKelolaSemua()) {
            return;
        }

        if ($id !== null) {
            abort_unless(ProfilSekolah::where('id', $id)->exists(), 404);
        }

        $this->profil_sekolah_id = $id;
        $this->resetErrorBag();
        $this->muatSurat();
    }

    public function updatedTahun(): void
    {
        $this->muatSurat();
    }

    public function updatedTriwulan(): void
    {
        $this->muatSurat();
    }

    /** @return array<string, string> */
    public function daftarKertasOptions(): array
    {
        return [
            'a4' => 'Kertas A4',
            'f4' => 'Kertas HVS/Legal/F4',
        ];
    }

    public function toggleSettingMargin(): void
    {
        $this->tampilSettingMargin = ! $this->tampilSettingMargin;
    }

    /** Batasi margin ke rentang wajar (0.5cm - 5cm) supaya input aneh tidak merusak hasil cetak. */
    protected function batasMargin(float $cm): float
    {
        return max(0.5, min(5, $cm));
    }

    public function updatedMarginKiri(): void
    {
        $this->marginKiri = $this->batasMargin($this->marginKiri);
    }

    public function updatedMarginKanan(): void
    {
        $this->marginKanan = $this->batasMargin($this->marginKanan);
    }

    public function updatedMarginAtas(): void
    {
        $this->marginAtas = $this->batasMargin($this->marginAtas);
    }

    public function updatedMarginBawah(): void
    {
        $this->marginBawah = $this->batasMargin($this->marginBawah);
    }

    /**
     * @return array{kertas: string, margin: array{kiri: float, kanan: float, atas: float, bawah: float}}
     */
    protected function pengaturanCetak(): array
    {
        return [
            'kertas' => $this->jenisKertas,
            'margin' => [
                'kiri' => $this->batasMargin($this->marginKiri),
                'kanan' => $this->batasMargin($this->marginKanan),
                'atas' => $this->batasMargin($this->marginAtas),
                'bawah' => $this->batasMargin($this->marginBawah),
            ],
        ];
    }

    /**
     * URL tombol "Cetak" (preview PDF, dibuka lewat target="_blank" di
     * Blade - BUKAN wire:click, krn Livewire tidak bisa membuka tab baru).
     */
    public function urlCetak(): ?string
    {
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId) {
            return null;
        }

        $pengaturan = $this->pengaturanCetak();

        return route('pendataan-ops.surat-tpg.cetak', [
            'jenis' => $this->tabAktif,
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

    /**
     * Memuat Nomor Surat & Tanggal Surat yang sudah tersimpan untuk
     * kombinasi sekolah+tahun+triwulan+tab aktif saat ini (kosong kalau
     * belum pernah diisi) - dipanggil dari mount() & setiap kali salah
     * satu dari keempatnya berubah (pindahTab/pilihSekolah/
     * updatedTahun/updatedTriwulan).
     */
    protected function muatSurat(): void
    {
        $sekolahId = $this->sekolahAktifId();

        $surat = $sekolahId
            ? SuratTpg::query()
                ->where('profil_sekolah_id', $sekolahId)
                ->where('tahun', $this->tahun)
                ->where('triwulan', $this->triwulan)
                ->where('jenis', $this->tabAktif)
                ->first()
            : null;

        $this->nomorSurat = $surat->nomor_surat ?? null;
        $this->tanggalSurat = $surat?->tanggal_surat?->format('Y-m-d');
        $this->tahunPelajaran = $surat->tahun_pelajaran ?? null;
    }

    public function updatedNomorSurat(): void
    {
        $this->simpanSurat('nomor_surat', $this->nomorSurat === '' ? null : $this->nomorSurat, 'nomorSurat');
    }

    /** Tahun Pelajaran (Surat Pernyataan) - auto-save begitu selesai diketik, mirip updatedNomorSurat(). */
    public function updatedTahunPelajaran(): void
    {
        $this->simpanSurat('tahun_pelajaran', $this->tahunPelajaran === '' ? null : $this->tahunPelajaran, 'tahunPelajaran');
    }

    public function updatedTanggalSurat(): void
    {
        $nilai = $this->tanggalSurat === '' ? null : $this->tanggalSurat;

        $validator = Validator::make(['nilai' => $nilai], ['nilai' => ['nullable', 'date']], [], ['nilai' => 'Tanggal Surat']);

        if ($validator->fails()) {
            $this->addError('tanggalSurat', $validator->errors()->first('nilai'));

            return;
        }

        $this->simpanSurat('tanggal_surat', $nilai, 'tanggalSurat');
    }

    protected function simpanSurat(string $field, mixed $nilai, string $namaErrorField): void
    {
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId) {
            return;
        }

        abort_unless($this->bolehKelola($sekolahId), 403);

        $surat = SuratTpg::where('profil_sekolah_id', $sekolahId)
            ->where('tahun', $this->tahun)
            ->where('triwulan', $this->triwulan)
            ->where('jenis', $this->tabAktif)
            ->first();

        if ($surat) {
            $surat->update([$field => $nilai]);
        } else {
            SuratTpg::create([
                'profil_sekolah_id' => $sekolahId,
                'tahun' => $this->tahun,
                'triwulan' => $this->triwulan,
                'jenis' => $this->tabAktif,
                $field => $nilai,
            ]);
        }
    }

    /**
     * Upload/ganti Kop Surat (permintaan user 2026-09-24, round
     * kesembilan belas, poin 2 & 6) - langsung tersimpan begitu file
     * dipilih (auto-save, konsisten dgn Nomor Surat/Tanggal Surat), tanpa
     * tombol "Simpan" terpisah. Disimpan ke App\Models\ProfilSekolah::
     * kop_surat (per SEKOLAH, bukan per surat) - berlaku otomatis untuk
     * KEDUA tab begitu diupload dari tab manapun.
     *
     * Perbaikan 2026-09-24 (round kedua puluh, permintaan user poin 5):
     * ditambah verifikasi otomatis bentuk gambar - kop surat/letterhead
     * WAJIB landscape (lebar > tinggi) dgn lebar minimal 800px, supaya
     * hasil upload rapih & proporsional saat ditampilkan (bukan gambar
     * potret/persegi yang akan tampil kecil/terpotong). Jawaban
     * AskUserQuestion 2026-09-24 ("Wajib orientasi landscape/lebar").
     * Validasi dimensi dicek TERPISAH sesudah validasi tipe/ukuran file
     * lolos (pakai getimagesize() thd file sementara upload), krn Laravel
     * "dimensions" rule tidak mendukung "lebih lebar dari tinggi" secara
     * langsung (hanya rasio pasti/rentang lebar-tinggi terpisah).
     */
    public function updatedKopSuratBaru(): void
    {
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId || ! $this->bolehKelola($sekolahId)) {
            $this->kopSuratBaru = null;

            return;
        }

        $validator = Validator::make(
            ['kopSuratBaru' => $this->kopSuratBaru],
            ['kopSuratBaru' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']],
            [],
            ['kopSuratBaru' => 'Kop Surat']
        );

        if ($validator->fails()) {
            $this->addError('kopSuratBaru', $validator->errors()->first('kopSuratBaru'));
            $this->kopSuratBaru = null;

            return;
        }

        $ukuranGambar = @getimagesize($this->kopSuratBaru->getRealPath());

        if (! $ukuranGambar) {
            $this->addError('kopSuratBaru', 'File yang diupload bukan gambar yang valid.');
            $this->kopSuratBaru = null;

            return;
        }

        [$lebar, $tinggi] = $ukuranGambar;

        if ($lebar <= $tinggi) {
            $this->addError('kopSuratBaru', 'Kop Surat harus berbentuk landscape/memanjang (lebar gambar harus lebih besar dari tingginya), sesuai proporsi kop surat/letterhead pada umumnya.');
            $this->kopSuratBaru = null;

            return;
        }

        if ($lebar < 800) {
            $this->addError('kopSuratBaru', 'Lebar gambar Kop Surat terlalu kecil - minimal 800 piksel supaya hasil cetak tetap rapih.');
            $this->kopSuratBaru = null;

            return;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);
        $pathLama = $sekolah->kop_surat;

        $pathBaru = $this->kopSuratBaru->store('kop-surat', 'public');
        $sekolah->update(['kop_surat' => $pathBaru]);

        if ($pathLama) {
            Storage::disk('public')->delete($pathLama);
        }

        $this->kopSuratBaru = null;
        $this->resetErrorBag('kopSuratBaru');
    }

    /**
     * Menyusun seluruh data tampilan (dipakai bersama render(), exportPdf(),
     * exportWord()) - Nama/NIP Kepsek, Tempat Tugas, Nama/NIP Pengawas
     * SELALU diambil langsung dari ProfilSekolah (bukan dari $this->nomorSurat/
     * $this->tanggalSurat yang cuma 2 field tersimpan itu).
     *
     * `kopSuratSrc` di sini memakai route terautentikasi (KopSuratFileController)
     * krn dipakai untuk preview LAYAR (dibuka lewat browser dengan sesi
     * login) - exportPdf()/exportWord() menimpanya dengan data URI base64
     * (App\Models\ProfilSekolah::kopSuratDataUri()) sesudah memanggil
     * method ini, krn kedua output itu dirender di server tanpa sesi
     * browser (lihat catatan lengkap di ProfilSekolah::kopSuratDataUri()).
     */
    protected function dataSurat(): array
    {
        $sekolahId = $this->sekolahAktifId();
        $sekolah = $sekolahId ? ProfilSekolah::find($sekolahId) : null;

        return [
            'editable' => true,
            'nomorSurat' => $this->nomorSurat,
            'tanggalSurat' => $this->tanggalSurat ? Carbon::parse($this->tanggalSurat) : null,
            'triwulan' => $this->triwulan,
            'tahun' => $this->tahun,
            'namaKepsek' => $sekolah?->nama_kepala_sekolah,
            'nipKepsek' => $sekolah?->nip_kepala_sekolah,
            'namaSekolah' => $sekolah?->nama_sekolah,
            'alamatSekolah' => $sekolah?->alamat_sekolah,
            'namaPengawas' => $sekolah?->nama_pengawas,
            'nipPengawas' => $sekolah?->nip_pengawas,
            'tahunPelajaran' => $this->tahunPelajaran,
            'kopSuratSrc' => $sekolah && $sekolah->kop_surat
                ? route('pendataan-ops.surat-tpg.kop-surat', $sekolah->id)
                : null,
            'sekolah' => $sekolah,
        ];
    }

    /** @return array<string, string> Peta jenis surat -> nama view PDF/Word, dipakai exportPdf()/exportWord(). */
    private function viewSuratPerJenis(): array
    {
        return [
            SuratTpg::JENIS_REKOMENDASI => 'surat-tpg-rekomendasi',
            SuratTpg::JENIS_PENGHENTIAN => 'surat-tpg-penghentian',
            SuratTpg::JENIS_PERNYATAAN => 'surat-tpg-pernyataan',
        ];
    }

    public function exportPdf()
    {
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId) {
            $this->addError('umum', 'Pilih salah satu sekolah pada filter di atas terlebih dahulu sebelum mengunduh.');

            return null;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);
        $data = $this->dataSurat();
        $data['editable'] = false;
        $data['kopSuratSrc'] = $sekolah->kopSuratDataUri();

        $pengaturan = $this->pengaturanCetak();
        $data['margin'] = $pengaturan['margin'];
        $ukuranKertas = $pengaturan['kertas'] === 'f4' ? 'folio' : 'a4';

        $view = 'pdf.'.($this->viewSuratPerJenis()[$this->tabAktif] ?? 'surat-tpg-rekomendasi');

        $pdf = Pdf::loadView($view, $data)->setPaper($ukuranKertas, 'portrait');

        $namaFile = 'surat-tpg-'.$this->tabAktif.'-'.Str::slug($sekolah->nama_sekolah).'-tw'.$this->triwulan.'-'.$this->tahun.'.pdf';
        $pathSementara = tempnam(sys_get_temp_dir(), 'surat-tpg-').'.pdf';
        file_put_contents($pathSementara, $pdf->output());

        return response()->download($pathSementara, $namaFile)->deleteFileAfterSend(true);
    }

    /**
     * "Unduh Word" - HTML disimpan dengan ekstensi .doc & Content-Type
     * application/msword (jawaban AskUserQuestion 2026-09-24: BUKAN docx
     * biner sungguhan, supaya tidak perlu menambah dependency Composer
     * baru phpoffice/phpword) - lihat catatan lengkap di
     * resources/views/word/surat-tpg-rekomendasi.blade.php.
     */
    public function exportWord()
    {
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId) {
            $this->addError('umum', 'Pilih salah satu sekolah pada filter di atas terlebih dahulu sebelum mengunduh.');

            return null;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);
        $data = $this->dataSurat();
        $data['editable'] = false;
        $data['kopSuratSrc'] = $sekolah->kopSuratDataUri();
        $data['margin'] = $this->pengaturanCetak()['margin'];

        $view = 'word.'.($this->viewSuratPerJenis()[$this->tabAktif] ?? 'surat-tpg-rekomendasi');

        $html = view($view, $data)->render();
        $namaFile = 'surat-tpg-'.$this->tabAktif.'-'.Str::slug($sekolah->nama_sekolah).'-tw'.$this->triwulan.'-'.$this->tahun.'.doc';
        $pathSementara = tempnam(sys_get_temp_dir(), 'surat-tpg-').'.doc';
        file_put_contents($pathSementara, $html);

        return response()->download($pathSementara, $namaFile, [
            'Content-Type' => 'application/msword',
        ])->deleteFileAfterSend(true);
    }

    public function render()
    {
        $data = $this->dataSurat();

        return view('livewire.pendataan-ops.surat-tpg.index', array_merge($data, [
            'tabAktif' => $this->tabAktif,
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            'tahunOptions' => $this->tahunOptions(),
            'triwulanOptions' => [1 => 'Triwulan I', 2 => 'Triwulan II', 3 => 'Triwulan III', 4 => 'Triwulan IV'],
            'sekolahOptions' => ProfilSekolah::orderBy('nama_sekolah')->get(['id', 'nama_sekolah']),
            'kertasOptions' => $this->daftarKertasOptions(),
            'urlCetak' => $this->urlCetak(),
            'labelTriwulan' => SuratTpg::labelTriwulan($this->triwulan),
        ]));
    }

    /** @return array<int, int> */
    private function tahunOptions(): array
    {
        return array_reverse(range(now()->year - 2, now()->year + 1));
    }
}
