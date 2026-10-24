<?php

namespace App\Livewire\PendataanOps\SuratTpg;

use App\Models\AksesDataLog;
use App\Livewire\Concerns\HasZoomTampilan;
use App\Models\DataPtk;
use App\Models\Lampiran2a;
use App\Models\LembarValidasiTpg;
use App\Models\LembarValidasiTpgCeklist;
use App\Models\ProfilSekolah;
use App\Models\RekapCairTpg;
use App\Models\RekapCairTpgBulan;
use App\Models\SuratTpg;
use App\Support\UrutanRosterTpg;
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

    /**
     * Tab "Lembar Validasi TPG" (BARU 2026-10-15/16, permintaan user,
     * format sesuai gambar contoh yang diupload) - 2 field header
     * (tanggal verifikasi & catatan) diketik manual & tersimpan otomatis
     * ke App\Models\LembarValidasiTpg (mirip $nomorSurat/$tahunPelajaran
     * di atas), BEDA dgn $daftarPtk di bawah yang berisi ceklist 8
     * kriteria PER-PTK (diisi dari Lampiran 2a, tersimpan ke
     * App\Models\LembarValidasiTpgCeklist - lihat muatLembarValidasi()).
     */
    public ?string $tanggalVerifikasi = null;

    public ?string $catatanHasilVerifikasi = null;

    /**
     * Daftar PTK (dari Lampiran 2a sekolah+tahun+triwulan aktif) beserta
     * ceklist 8 kriterianya, dikunci (keyed) oleh NUPTK (BUKAN id baris
     * Lampiran2a - lihat alasan lengkap di docblock App\Models\
     * LembarValidasiTpgCeklist) - tiap elemen: ['nama_ptk' => string] +
     * 8 boolean (lihat App\Models\LembarValidasiTpgCeklist::KOLOM_CEKLIST).
     * wire:model.live pada checkbox Blade memakai dot-notation
     * "daftarPtk.{nuptk}.{kolom}" - ditangkap lewat hook updated() di
     * bawah (bukan method updated<Properti>() per kolom, krn kuncinya
     * dinamis/banyak).
     *
     * @var array<string, array<string, mixed>>
     */
    public array $daftarPtk = [];

    /**
     * Tab "Data Cair/Belum TPG" (BARU 2026-10-16, permintaan user,
     * format sesuai gambar contoh "REKAPITULASI DATA PTK YANG SUDAH /
     * BELUM CAIR TPG") - Catatan Hasil Verifikasi diketik manual &
     * tersimpan otomatis ke App\Models\RekapCairTpg (properti terpisah
     * dari $catatanHasilVerifikasi di atas, krn beda tabel/model & tab).
     */
    public ?string $catatanCairTpg = null;

    /**
     * Daftar PTK utk tab "Data Cair/Belum TPG", dikunci (keyed) oleh
     * NUPTK - tiap elemen: ['nama_ptk' => string, 'nip' => string|null]
     * + 12 kolom status bulan (lihat App\Models\RekapCairTpgBulan::
     * KOLOM_BULAN), nilai null/string sesuai App\Models\
     * RekapCairTpgBulan::STATUS_OPTIONS. Dikonfirmasi AskUserQuestion
     * 2026-10-16: daftar PTK = GABUNGAN Lampiran 2a SEMUA Triwulan 1-4
     * dalam Tahun aktif, TAPI isian 12 bulan tetap tersimpan TERPISAH
     * per Triwulan yang sedang difilter - lihat muatRekapCairTpg().
     * wire:model.live pada <select> Blade memakai dot-notation
     * "daftarPtkCairTpg.{nuptk}.{kolom}" - ditangkap lewat hook
     * updated() di bawah (pola sama dgn $daftarPtk/Lembar Validasi TPG).
     *
     * @var array<string, array<string, mixed>>
     */
    public array $daftarPtkCairTpg = [];

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

    /**
     * Konfirmasi hapus manual surat aktif (sekolah+tahun+triwulan+jenis
     * tab) - permintaan user 2026-09-27, meniru pola
     * App\Livewire\PendataanBosp\PajakBospReguler\Index::
     * konfirmasiHapusBulan()/batalHapusBulan()/hapusBulan().
     */
    public bool $confirmingHapusSurat = false;

    public function mount(): void
    {
        // Permintaan user 2026-10-24 (fitur Tahun Anggaran, Fase 2):
        // Superadmin TETAP bebas (tahun berjalan seperti sebelumnya).
        // Admin OPS/Admin BOSP TERKUNCI ke tahun yang dipilih saat login
        // (session 'tahun_sesi') - dropdown Tahun disembunyikan total
        // untuk mereka di Blade, sama seperti pola yang sudah diterapkan
        // di Dana BOSP Tahap & Rekap RKAS (Fase 1).
        $this->tahun = $this->bolehKelolaSemua()
            ? now()->year
            : (int) session('tahun_sesi', \App\Models\TahunAnggaranSetting::current()->tahun_aktif);

        if (! $this->bolehKelolaSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        $this->muatSurat();
        $this->muatLembarValidasi();
        $this->muatRekapCairTpg();
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
        $tabValid = [
            SuratTpg::JENIS_REKOMENDASI, SuratTpg::JENIS_PENGHENTIAN, SuratTpg::JENIS_PERNYATAAN,
            SuratTpg::JENIS_COVER_SMPN, SuratTpg::JENIS_COVER_SMP_S, SuratTpg::JENIS_LEMBAR_VALIDASI,
            SuratTpg::JENIS_REKAP_CAIR_TPG,
        ];

        if (! in_array($tab, $tabValid, true)) {
            return;
        }

        // Tab Cover SMPN/Cover SMP-S (permintaan user 2026-10-15) HANYA
        // boleh dibuka sesuai Status sekolah yang sedang aktif (Negeri ->
        // cover_smpn, Swasta -> cover_smp_s) - tombol tab yang salah
        // memang tidak dirender di Blade utk status tsb, ini jaga-jaga
        // tambahan (defense in depth) supaya tidak bisa "dipaksa" lewat
        // wire:click manual/devtools.
        if (in_array($tab, [SuratTpg::JENIS_COVER_SMPN, SuratTpg::JENIS_COVER_SMP_S], true)) {
            $sekolahId = $this->sekolahAktifId();
            $sekolah = $sekolahId ? ProfilSekolah::find($sekolahId) : null;

            if (! $sekolah) {
                return;
            }

            $tabSesuaiStatus = $sekolah->status === ProfilSekolah::STATUS_NEGERI
                ? SuratTpg::JENIS_COVER_SMPN
                : SuratTpg::JENIS_COVER_SMP_S;

            if ($tab !== $tabSesuaiStatus) {
                return;
            }
        }

        $this->tabAktif = $tab;
        $this->muatSurat();
        $this->muatLembarValidasi();
        $this->muatRekapCairTpg();
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
        $this->sinkronkanTabCoverDenganStatusSekolah();
        $this->muatSurat();
        $this->muatLembarValidasi();
        $this->muatRekapCairTpg();
    }

    /**
     * Permintaan user 2026-10-15: begitu Superadmin ganti sekolah filter
     * sementara tab Cover SMPN/Cover SMP-S sedang aktif, tab-nya otomatis
     * disesuaikan ke jenis cover yang benar utk sekolah BARU (atau
     * kembali ke tab "rekomendasi" kalau sekolah dikosongkan) - supaya
     * tidak "nyangkut" di tab yang tombolnya sudah tidak ditampilkan lagi
     * di Blade untuk status sekolah yang baru.
     */
    protected function sinkronkanTabCoverDenganStatusSekolah(): void
    {
        if (! in_array($this->tabAktif, [SuratTpg::JENIS_COVER_SMPN, SuratTpg::JENIS_COVER_SMP_S], true)) {
            return;
        }

        $sekolahId = $this->sekolahAktifId();
        $sekolah = $sekolahId ? ProfilSekolah::find($sekolahId) : null;

        $this->tabAktif = match (true) {
            ! $sekolah => SuratTpg::JENIS_REKOMENDASI,
            $sekolah->status === ProfilSekolah::STATUS_NEGERI => SuratTpg::JENIS_COVER_SMPN,
            default => SuratTpg::JENIS_COVER_SMP_S,
        };
    }

    public function updatedTahun(): void
    {
        $this->muatSurat();
        $this->muatLembarValidasi();
        $this->muatRekapCairTpg();
    }

    public function updatedTriwulan(): void
    {
        $this->muatSurat();
        $this->muatLembarValidasi();
        $this->muatRekapCairTpg();
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

        // Auto-hapus baris "kosong" sisa auto-save per keystroke (mis.
        // user isi lalu hapus lagi seluruh Nomor Surat/Tahun Pelajaran/
        // Tanggal Surat) - permintaan user 2026-09-27, supaya baris tsb
        // tidak nyangkut sbg baris "kosong" di laporan/cek kecocokan data.
        $suratTerbaru = SuratTpg::where('profil_sekolah_id', $sekolahId)
            ->where('tahun', $this->tahun)
            ->where('triwulan', $this->triwulan)
            ->where('jenis', $this->tabAktif)
            ->first();

        if ($suratTerbaru && $suratTerbaru->semuaFieldDataKosong()) {
            $suratTerbaru->delete();
        }
    }

    /**
     * Daftar nama kolom ceklist (8 kriteria) - alias langsung ke
     * App\Models\LembarValidasiTpgCeklist::KOLOM_CEKLIST, dipakai utk
     * memvalidasi nama kolom yang datang dari updated()/centangSemuaKolom()
     * sebelum disimpan (jaga-jaga thd manipulasi wire:model manual lewat
     * devtools - nama kolom yang tidak dikenal diabaikan).
     *
     * @return array<int, string>
     */
    protected function daftarKolomCeklist(): array
    {
        return LembarValidasiTpgCeklist::KOLOM_CEKLIST;
    }

    /** @return array<int, string> 12 nama kolom bulan (Januari-Desember) utk tab "Data Cair/Belum TPG" - lihat App\Models\RekapCairTpgBulan::KOLOM_BULAN. */
    protected function daftarKolomBulan(): array
    {
        return RekapCairTpgBulan::KOLOM_BULAN;
    }

    /**
     * Memuat Tanggal Verifikasi & Validasi + Catatan Hasil Verifikasi
     * (App\Models\LembarValidasiTpg) + daftar PTK dari Lampiran 2a
     * beserta ceklist 8 kriterianya (App\Models\LembarValidasiTpgCeklist,
     * dicocokkan lewat NUPTK - lihat docblock model tsb) untuk kombinasi
     * sekolah+tahun+triwulan aktif saat ini - dipanggil dari mount() &
     * setiap kali salah satu dari ketiganya berubah (pindahTab/
     * pilihSekolah/updatedTahun/updatedTriwulan), SAMA seperti pola
     * muatSurat() di atas. Baris App\Models\Lampiran2a diurutkan
     * `orderBy('id')` (urutan input, BUKAN `->latest()` spt layar
     * Lampiran 2a sendiri - urutan terbalik tidak cocok utk daftar
     * bernomor pada dokumen resmi).
     */
    protected function muatLembarValidasi(): void
    {
        $sekolahId = $this->sekolahAktifId();

        $header = $sekolahId
            ? LembarValidasiTpg::query()
                ->where('profil_sekolah_id', $sekolahId)
                ->where('tahun', $this->tahun)
                ->where('triwulan', $this->triwulan)
                ->first()
            : null;

        $this->tanggalVerifikasi = $header?->tanggal_verifikasi?->format('Y-m-d');
        $this->catatanHasilVerifikasi = $header->catatan_hasil_verifikasi ?? null;

        $this->daftarPtk = [];

        if (! $sekolahId) {
            return;
        }

        $daftarLampiran = Lampiran2a::query()
            ->where('profil_sekolah_id', $sekolahId)
            ->where('tahun', $this->tahun)
            ->where('triwulan', $this->triwulan)
            ->orderBy('id')
            ->get(['nuptk', 'nama_ptk']);

        if ($daftarLampiran->isEmpty()) {
            return;
        }

        // BARU 2026-10-20 (permintaan user) - urutan baris roster PTK
        // (Kepala Sekolah dulu, lalu Pangkat/Golongan utk sekolah
        // Negeri atau Abjad utk sekolah Swasta, + TMT Di Sekolah
        // Induk), lihat docblock App\Support\UrutanRosterTpg utk
        // rincian lengkap aturan & jawaban klarifikasinya.
        $daftarLampiran = UrutanRosterTpg::urutkanSatuSekolah(
            $daftarLampiran,
            $sekolahId,
            ProfilSekolah::where('id', $sekolahId)->value('status')
        );

        $ceklistTersimpan = LembarValidasiTpgCeklist::query()
            ->where('profil_sekolah_id', $sekolahId)
            ->where('tahun', $this->tahun)
            ->where('triwulan', $this->triwulan)
            ->whereIn('nuptk', $daftarLampiran->pluck('nuptk'))
            ->get()
            ->keyBy('nuptk');

        foreach ($daftarLampiran as $ptk) {
            $ceklist = $ceklistTersimpan->get($ptk->nuptk);

            $baris = ['nama_ptk' => $ptk->nama_ptk];

            foreach ($this->daftarKolomCeklist() as $kolom) {
                $baris[$kolom] = (bool) ($ceklist->{$kolom} ?? false);
            }

            $this->daftarPtk[$ptk->nuptk] = $baris;
        }
    }

    /**
     * Memuat Catatan Hasil Verifikasi (App\Models\RekapCairTpg, scoped
     * sekolah+tahun+triwulan aktif) + daftar PTK utk tab "Data Cair/
     * Belum TPG" - BEDA dgn muatLembarValidasi(): daftar PTK di sini
     * adalah GABUNGAN Lampiran 2a dari SEMUA Triwulan (1-4) dalam Tahun
     * aktif (dikonfirmasi AskUserQuestion 2026-10-16, krn tabel ini
     * merekap 12 bulan penuh = 1 tahun), TAPI ceklist status 12 bulan
     * yang ditampilkan/disimpan tetap scoped ke Triwulan yang sedang
     * difilter di atas (sama seperti tab2 lain di menu ini - laporan
     * diperbarui tiap checkpoint Triwulan). NIP PTK diambil dari
     * App\Models\DataPtk (dicocokkan lewat NUPTK, bukan dari Lampiran2a
     * yang tidak punya field NIP - dikonfirmasi AskUserQuestion
     * 2026-10-16).
     */
    protected function muatRekapCairTpg(): void
    {
        $sekolahId = $this->sekolahAktifId();

        $header = $sekolahId
            ? RekapCairTpg::query()
                ->where('profil_sekolah_id', $sekolahId)
                ->where('tahun', $this->tahun)
                ->where('triwulan', $this->triwulan)
                ->first()
            : null;

        $this->catatanCairTpg = $header->catatan_hasil_verifikasi ?? null;

        $this->daftarPtkCairTpg = [];

        if (! $sekolahId) {
            return;
        }

        // Gabungan Lampiran 2a SEMUA Triwulan (1-4) dalam Tahun aktif -
        // PTK yang sama (NUPTK sama) di triwulan berbeda digabung jadi 1
        // baris; kalau nama_ptk-nya beda antar triwulan (jarang terjadi),
        // yang dipakai adalah ejaan dari triwulan TERAKHIR (orderBy
        // triwulan ascending, baris berikutnya menimpa array $namaPerNuptk).
        $daftarLampiran = Lampiran2a::query()
            ->where('profil_sekolah_id', $sekolahId)
            ->where('tahun', $this->tahun)
            ->orderBy('triwulan')
            ->orderBy('id')
            ->get(['nuptk', 'nama_ptk']);

        if ($daftarLampiran->isEmpty()) {
            return;
        }

        $namaPerNuptk = [];

        foreach ($daftarLampiran as $ptk) {
            $namaPerNuptk[$ptk->nuptk] = $ptk->nama_ptk;
        }

        // BARU 2026-10-20 (permintaan user) - urutan baris roster PTK
        // (Kepala Sekolah dulu, lalu Pangkat/Golongan utk sekolah
        // Negeri atau Abjad utk sekolah Swasta, + TMT Di Sekolah
        // Induk), lihat docblock App\Support\UrutanRosterTpg utk
        // rincian lengkap aturan & jawaban klarifikasinya. Dibungkus
        // jadi koleksi model Lampiran2a sementara (BUKAN disimpan ke
        // database) supaya bisa dipakai lewat helper yang sama dgn
        // muatLembarValidasi() di atas - urutan "ejaan dari triwulan
        // TERAKHIR" pada $namaPerNuptk TETAP dipertahankan (lihat
        // komentar di atas), hanya urutan BARISNYA yang berubah.
        $rosterUntukUrutan = collect($namaPerNuptk)
            ->map(fn ($namaPtk, $nuptk) => new Lampiran2a(['nuptk' => $nuptk, 'nama_ptk' => $namaPtk]))
            ->values();

        $rosterTerurut = UrutanRosterTpg::urutkanSatuSekolah(
            $rosterUntukUrutan,
            $sekolahId,
            ProfilSekolah::where('id', $sekolahId)->value('status')
        );

        $nipPerNuptk = DataPtk::query()
            ->where('profil_sekolah_id', $sekolahId)
            ->whereIn('nuptk', array_keys($namaPerNuptk))
            ->whereNotNull('nuptk')
            ->get(['nuptk', 'nip'])
            ->keyBy('nuptk')
            ->map(fn ($ptk) => $ptk->nip);

        $bulanTersimpan = RekapCairTpgBulan::query()
            ->where('profil_sekolah_id', $sekolahId)
            ->where('tahun', $this->tahun)
            ->where('triwulan', $this->triwulan)
            ->whereIn('nuptk', array_keys($namaPerNuptk))
            ->get()
            ->keyBy('nuptk');

        foreach ($rosterTerurut as $ptk) {
            $nuptk = $ptk->nuptk;
            $namaPtk = $ptk->nama_ptk;
            $dataBulan = $bulanTersimpan->get($nuptk);

            $baris = [
                'nama_ptk' => $namaPtk,
                'nip' => $nipPerNuptk->get($nuptk),
            ];

            foreach ($this->daftarKolomBulan() as $kolom) {
                $baris[$kolom] = $dataBulan->{$kolom} ?? null;
            }

            $this->daftarPtkCairTpg[$nuptk] = $baris;
        }
    }

    /**
     * Hook Livewire 3 generik yang terpanggil SETIAP properti publik
     * berubah (termasuk path bertitik pada array, mis.
     * "daftarPtk.1234567890123456.memiliki_sertifikat_pendidik") -
     * dipakai KHUSUS utk menangkap toggle checkbox ceklist per-PTK
     * (wire:model.live pada Blade memakai path dinamis per NUPTK,
     * sehingga tidak bisa dipakai method updated<Properti>() biasa yang
     * namanya tetap). Properti lain (nomorSurat dkk) TETAP memakai
     * method updated<Properti>() masing-masing seperti biasa (hook ini
     * tidak mengganggunya - Livewire memanggil KEDUANYA).
     */
    public function updated(string $name, mixed $value): void
    {
        if (str_starts_with($name, 'daftarPtk.')) {
            $bagian = explode('.', $name);

            if (count($bagian) !== 3) {
                return;
            }

            [, $nuptk, $kolom] = $bagian;

            if (! in_array($kolom, $this->daftarKolomCeklist(), true)) {
                return;
            }

            $this->simpanCeklist($nuptk, $kolom, (bool) $value);

            return;
        }

        // Tab "Data Cair/Belum TPG" (BARU 2026-10-16) - path dinamis
        // "daftarPtkCairTpg.{nuptk}.{kolom}" dari <select> status cair
        // per bulan (pola sama dgn daftarPtk./Lembar Validasi TPG di
        // atas, TIDAK bentrok krn awalan "daftarPtk." vs
        // "daftarPtkCairTpg." beda persis pada karakter setelah
        // "daftarPtk").
        if (str_starts_with($name, 'daftarPtkCairTpg.')) {
            $bagian = explode('.', $name);

            if (count($bagian) !== 3) {
                return;
            }

            [, $nuptk, $kolom] = $bagian;

            if (! in_array($kolom, $this->daftarKolomBulan(), true)) {
                return;
            }

            $this->simpanBulanCairTpg($nuptk, $kolom, $value === '' ? null : (string) $value);
        }
    }

    /**
     * Simpan 1 nilai status cair TPG bulan tertentu (find-or-create
     * baris RekapCairTpgBulan utk kombinasi sekolah+tahun+triwulan+
     * nuptk aktif) - dipanggil dari updated() saat <select> bulan
     * diganti. Pola sama persis dgn simpanCeklist() di atas.
     */
    protected function simpanBulanCairTpg(string $nuptk, string $kolom, ?string $nilai): void
    {
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId || ! $this->bolehKelola($sekolahId)) {
            return;
        }

        // Jaga-jaga: pastikan NUPTK ini memang ada di Lampiran 2a
        // (triwulan manapun) utk sekolah+tahun aktif sebelum menyimpan
        // (daftar PTK tab ini = gabungan semua triwulan, bukan hanya
        // triwulan yang sedang difilter).
        $adaDiLampiran2a = Lampiran2a::query()
            ->where('profil_sekolah_id', $sekolahId)
            ->where('tahun', $this->tahun)
            ->where('nuptk', $nuptk)
            ->exists();

        if (! $adaDiLampiran2a) {
            return;
        }

        if ($nilai !== null && ! array_key_exists($nilai, RekapCairTpgBulan::STATUS_OPTIONS)) {
            return;
        }

        RekapCairTpgBulan::updateOrCreate(
            [
                'profil_sekolah_id' => $sekolahId,
                'tahun' => $this->tahun,
                'triwulan' => $this->triwulan,
                'nuptk' => $nuptk,
            ],
            [$kolom => $nilai]
        );
    }

    /**
     * Simpan 1 nilai ceklist (find-or-create baris
     * LembarValidasiTpgCeklist utk kombinasi sekolah+tahun+triwulan+
     * nuptk aktif) - dipanggil dari updated() (toggle 1 checkbox) &
     * centangSemuaKolom() (centang 1 kolom utk semua PTK sekaligus).
     */
    protected function simpanCeklist(string $nuptk, string $kolom, bool $nilai): void
    {
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId || ! $this->bolehKelola($sekolahId)) {
            return;
        }

        // Jaga-jaga: pastikan NUPTK ini memang ada di Lampiran 2a utk
        // sekolah+tahun+triwulan aktif (thd manipulasi wire:model manual
        // lewat devtools) sebelum menyimpan.
        $adaDiLampiran2a = Lampiran2a::query()
            ->where('profil_sekolah_id', $sekolahId)
            ->where('tahun', $this->tahun)
            ->where('triwulan', $this->triwulan)
            ->where('nuptk', $nuptk)
            ->exists();

        if (! $adaDiLampiran2a) {
            return;
        }

        LembarValidasiTpgCeklist::updateOrCreate(
            [
                'profil_sekolah_id' => $sekolahId,
                'tahun' => $this->tahun,
                'triwulan' => $this->triwulan,
                'nuptk' => $nuptk,
            ],
            [$kolom => $nilai]
        );
    }

    /**
     * Tombol "Centang Semua" PER KOLOM (jawaban AskUserQuestion
     * 2026-10-15: "1 tombol per kolom") - mencentang 1 kriteria utk
     * SEMUA PTK yang sedang tampil sekaligus (admin tetap bisa
     * meng-uncheck manual per-PTK sesudahnya kalau ada pengecualian).
     */
    public function centangSemuaKolom(string $kolom): void
    {
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId || ! $this->bolehKelola($sekolahId) || ! in_array($kolom, $this->daftarKolomCeklist(), true)) {
            return;
        }

        foreach (array_keys($this->daftarPtk) as $nuptk) {
            $this->daftarPtk[$nuptk][$kolom] = true;
            $this->simpanCeklist($nuptk, $kolom, true);
        }
    }

    public function updatedTanggalVerifikasi(): void
    {
        $nilai = $this->tanggalVerifikasi === '' ? null : $this->tanggalVerifikasi;

        $validator = Validator::make(['nilai' => $nilai], ['nilai' => ['nullable', 'date']], [], ['nilai' => 'Tanggal Verifikasi dan Validasi']);

        if ($validator->fails()) {
            $this->addError('tanggalVerifikasi', $validator->errors()->first('nilai'));

            return;
        }

        $this->simpanLembarValidasi('tanggal_verifikasi', $nilai);
    }

    public function updatedCatatanHasilVerifikasi(): void
    {
        $this->simpanLembarValidasi('catatan_hasil_verifikasi', $this->catatanHasilVerifikasi === '' ? null : $this->catatanHasilVerifikasi);
    }

    /** Simpan 1 field header Lembar Validasi TPG (tanggal_verifikasi/catatan_hasil_verifikasi) - pola sama persis dgn simpanSurat() di atas. */
    protected function simpanLembarValidasi(string $field, mixed $nilai): void
    {
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId) {
            return;
        }

        abort_unless($this->bolehKelola($sekolahId), 403);

        $header = LembarValidasiTpg::where('profil_sekolah_id', $sekolahId)
            ->where('tahun', $this->tahun)
            ->where('triwulan', $this->triwulan)
            ->first();

        if ($header) {
            $header->update([$field => $nilai]);
        } else {
            LembarValidasiTpg::create([
                'profil_sekolah_id' => $sekolahId,
                'tahun' => $this->tahun,
                'triwulan' => $this->triwulan,
                $field => $nilai,
            ]);
        }

        $headerTerbaru = LembarValidasiTpg::where('profil_sekolah_id', $sekolahId)
            ->where('tahun', $this->tahun)
            ->where('triwulan', $this->triwulan)
            ->first();

        if ($headerTerbaru && $headerTerbaru->semuaFieldDataKosong()) {
            $headerTerbaru->delete();
        }
    }

    public function updatedCatatanCairTpg(): void
    {
        $this->simpanRekapCairTpg($this->catatanCairTpg === '' ? null : $this->catatanCairTpg);
    }

    /** Simpan Catatan Hasil Verifikasi tab "Data Cair/Belum TPG" - pola sama persis dgn simpanLembarValidasi() di atas. */
    protected function simpanRekapCairTpg(?string $catatan): void
    {
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId) {
            return;
        }

        abort_unless($this->bolehKelola($sekolahId), 403);

        $header = RekapCairTpg::where('profil_sekolah_id', $sekolahId)
            ->where('tahun', $this->tahun)
            ->where('triwulan', $this->triwulan)
            ->first();

        if ($header) {
            $header->update(['catatan_hasil_verifikasi' => $catatan]);
        } else {
            RekapCairTpg::create([
                'profil_sekolah_id' => $sekolahId,
                'tahun' => $this->tahun,
                'triwulan' => $this->triwulan,
                'catatan_hasil_verifikasi' => $catatan,
            ]);
        }

        $headerTerbaru = RekapCairTpg::where('profil_sekolah_id', $sekolahId)
            ->where('tahun', $this->tahun)
            ->where('triwulan', $this->triwulan)
            ->first();

        if ($headerTerbaru && $headerTerbaru->semuaFieldDataKosong()) {
            $headerTerbaru->delete();
        }
    }

    /**
     * Konfirmasi hapus manual SELURUH data surat aktif (sekolah+tahun+
     * triwulan+jenis tab yang sedang dibuka) - permintaan user
     * 2026-09-27, meniru App\Livewire\PendataanBosp\PajakBospReguler\
     * Index::konfirmasiHapusBulan().
     */
    public function konfirmasiHapusSurat(): void
    {
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId || ! $this->bolehKelola($sekolahId)) {
            return;
        }

        $this->confirmingHapusSurat = true;
        $this->dispatch('open-modal', 'surat-tpg-hapus');
    }

    public function batalHapusSurat(): void
    {
        $this->confirmingHapusSurat = false;
        $this->dispatch('close-modal', 'surat-tpg-hapus');
    }

    public function hapusSurat(): void
    {
        $sekolahId = $this->sekolahAktifId();

        if ($sekolahId && $this->bolehKelola($sekolahId)) {
            // Tab "Lembar Validasi TPG" (BARU 2026-10-15/16) datanya
            // BUKAN di tabel surat_tpg (lihat SuratTpg::JENIS_LEMBAR_VALIDASI
            // docblock) - hapus header + SELURUH baris ceklist PTK utk
            // sekolah+tahun+triwulan aktif dari tabel terpisahnya sendiri.
            if ($this->tabAktif === SuratTpg::JENIS_LEMBAR_VALIDASI) {
                LembarValidasiTpg::where('profil_sekolah_id', $sekolahId)
                    ->where('tahun', $this->tahun)
                    ->where('triwulan', $this->triwulan)
                    ->delete();

                LembarValidasiTpgCeklist::where('profil_sekolah_id', $sekolahId)
                    ->where('tahun', $this->tahun)
                    ->where('triwulan', $this->triwulan)
                    ->delete();

                $this->muatLembarValidasi();
            } elseif ($this->tabAktif === SuratTpg::JENIS_REKAP_CAIR_TPG) {
                // Tab "Data Cair/Belum TPG" (BARU 2026-10-16) - datanya
                // BUKAN di tabel surat_tpg (lihat SuratTpg::
                // JENIS_REKAP_CAIR_TPG docblock) - hapus header +
                // SELURUH baris ceklist bulan utk sekolah+tahun+triwulan
                // aktif SAJA (bukan seluruh tahun - daftar PTK memang
                // gabungan 1 tahun, tapi isiannya tetap per-triwulan,
                // lihat muatRekapCairTpg()).
                RekapCairTpg::where('profil_sekolah_id', $sekolahId)
                    ->where('tahun', $this->tahun)
                    ->where('triwulan', $this->triwulan)
                    ->delete();

                RekapCairTpgBulan::where('profil_sekolah_id', $sekolahId)
                    ->where('tahun', $this->tahun)
                    ->where('triwulan', $this->triwulan)
                    ->delete();

                $this->muatRekapCairTpg();
            } else {
                SuratTpg::where('profil_sekolah_id', $sekolahId)
                    ->where('tahun', $this->tahun)
                    ->where('triwulan', $this->triwulan)
                    ->where('jenis', $this->tabAktif)
                    ->delete();

                $this->muatSurat();
            }

            session()->flash('status', 'Data surat berhasil dihapus.');
        }

        $this->confirmingHapusSurat = false;
        $this->dispatch('close-modal', 'surat-tpg-hapus');
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
     * Hapus Kop Surat yang sedang tersimpan (permintaan user 2026-10-15:
     * tombol "Hapus" di samping "Ganti Kop Surat", supaya Admin OPS/BOSP
     * bisa mengosongkan kop surat lama dulu - misalnya salah upload -
     * SEBELUM upload gambar baru, tanpa harus langsung menimpa). Sama
     * seperti updatedKopSuratBaru(): per SEKOLAH (App\Models\ProfilSekolah::
     * kop_surat), berlaku otomatis untuk KEDUA tab (Rekomendasi &
     * Penghentian) begitu dihapus dari tab manapun. File lama ikut
     * dihapus dari storage (pola sama seperti $pathLama di
     * updatedKopSuratBaru()).
     */
    public function hapusKopSurat(): void
    {
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId || ! $this->bolehKelola($sekolahId)) {
            return;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);
        $pathLama = $sekolah->kop_surat;

        if (! $pathLama) {
            return;
        }

        $sekolah->update(['kop_surat' => null]);

        Storage::disk('public')->delete($pathLama);
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
            // Logo Sekolah (permintaan user 2026-10-15, tab Cover
            // SMPN/Cover SMP-S) - pola sama persis dgn kopSuratSrc di
            // atas: route terautentikasi utk preview layar, ditimpa data
            // URI base64 di exportPdf()/exportWord() (lihat di bawah).
            // 2026-10-18: ditambah query string "?v=..." (cache-buster) -
            // lihat docblock App\Http\Controllers\LogoSekolahFileController
            // & perbaikan yang sama di App\Livewire\ProfilSekolah\
            // Index::render() - URL route ini hanya berisi ID sekolah
            // (bukan nama file logo-nya) & disajikan dengan Cache-Control
            // 1 jam, jadi tanpa penanda versi ini browser akan terus
            // menampilkan logo LAMA dari cache-nya sampai 1 jam berlalu
            // walau logo sudah diganti/dihapus di menu Profil Sekolah.
            'logoSekolahSrc' => $sekolah && $sekolah->logo_sekolah
                ? route('profil-sekolah.logo-sekolah', $sekolah->id).'?v='.urlencode(basename($sekolah->logo_sekolah))
                : null,
            'sekolah' => $sekolah,
            // Tab "Lembar Validasi TPG" (BARU 2026-10-15/16) - SELALU
            // disertakan di sini (pola sama dgn tahunPelajaran/
            // logoSekolahSrc di atas yang juga selalu dihitung terlepas
            // dari tab mana yang sedang aktif), supaya exportPdf()/
            // exportWord() (yang memakai dataSurat() ini) langsung dapat
            // data lengkapnya tanpa perlu logika tambahan per-jenis.
            'tanggalVerifikasi' => $this->tanggalVerifikasi ? Carbon::parse($this->tanggalVerifikasi) : null,
            'catatanHasilVerifikasi' => $this->catatanHasilVerifikasi,
            'daftarPtkValidasi' => $this->daftarPtk,
            'namaKasieKpml' => $sekolah?->nama_kasie_kpml,
            'nipKasieKpml' => $sekolah?->nip_kasie_kpml,
            // Label kuning "TPGASN"/"TPGNON-ASN" (jawaban AskUserQuestion
            // 2026-10-15: "Mengikuti status sekolah" -> Negeri = TPGASN,
            // Swasta = TPGNON-ASN) - konsisten dgn pola Negeri=ASN/
            // Swasta=Non-ASN pada tab Cover SMPN/Cover SMP-S.
            'labelTpgStatus' => $sekolah
                ? ($sekolah->status === ProfilSekolah::STATUS_NEGERI ? 'TPGASN' : 'TPGNON-ASN')
                : null,
            // Tab "Data Cair/Belum TPG" (BARU 2026-10-16) - SELALU
            // disertakan juga di sini, pola sama dgn tanggalVerifikasi/
            // daftarPtkValidasi di atas.
            'catatanCairTpg' => $this->catatanCairTpg,
            'daftarPtkCairTpg' => $this->daftarPtkCairTpg,
        ];
    }

    /** @return array<string, string> Peta jenis surat -> nama view PDF/Word, dipakai exportPdf()/exportWord(). */
    private function viewSuratPerJenis(): array
    {
        return [
            SuratTpg::JENIS_REKOMENDASI => 'surat-tpg-rekomendasi',
            SuratTpg::JENIS_PENGHENTIAN => 'surat-tpg-penghentian',
            SuratTpg::JENIS_PERNYATAAN => 'surat-tpg-pernyataan',
            SuratTpg::JENIS_COVER_SMPN => 'surat-tpg-cover-smpn',
            SuratTpg::JENIS_COVER_SMP_S => 'surat-tpg-cover-smp-s',
            SuratTpg::JENIS_LEMBAR_VALIDASI => 'surat-tpg-lembar-validasi',
            SuratTpg::JENIS_REKAP_CAIR_TPG => 'surat-tpg-rekap-cair',
        ];
    }

    public function exportPdf()
    {
        AksesDataLog::catat(AksesDataLog::JENIS_UNDUH, 'Surat TPG', 'PDF');
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId) {
            $this->addError('umum', 'Pilih salah satu sekolah pada filter di atas terlebih dahulu sebelum mengunduh.');

            return null;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);
        $data = $this->dataSurat();
        $data['editable'] = false;
        $data['kopSuratSrc'] = $sekolah->kopSuratDataUri();
        $data['logoSekolahSrc'] = $sekolah->logoSekolahDataUri();

        $pengaturan = $this->pengaturanCetak();
        $data['margin'] = $pengaturan['margin'];
        $ukuranKertas = $pengaturan['kertas'] === 'f4' ? 'folio' : 'a4';

        $view = 'pdf.'.($this->viewSuratPerJenis()[$this->tabAktif] ?? 'surat-tpg-rekomendasi');

        // Lembar Validasi TPG dicetak LANDSCAPE (permintaan user
        // 2026-10-16) - jenis/tab lainnya tetap portrait.
        $orientasiKertas = in_array($this->tabAktif, [SuratTpg::JENIS_LEMBAR_VALIDASI, SuratTpg::JENIS_REKAP_CAIR_TPG], true) ? 'landscape' : 'portrait';

        $pdf = Pdf::loadView($view, $data)->setPaper($ukuranKertas, $orientasiKertas);

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
        AksesDataLog::catat(AksesDataLog::JENIS_UNDUH, 'Surat TPG', 'Word');
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId) {
            $this->addError('umum', 'Pilih salah satu sekolah pada filter di atas terlebih dahulu sebelum mengunduh.');

            return null;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);
        $data = $this->dataSurat();
        $data['editable'] = false;
        $data['kopSuratSrc'] = $sekolah->kopSuratDataUri();
        $data['logoSekolahSrc'] = $sekolah->logoSekolahDataUri();
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
            'sekolahOptions' => ProfilSekolah::urutStandar()->get(['id', 'nama_sekolah']),
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
