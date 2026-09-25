<?php

namespace App\Livewire\PendataanBosp\FormulirBosK7;

use App\Exports\FormulirBosK7Export;
use App\Livewire\Concerns\HasZoomTampilan;
use App\Livewire\Concerns\MenolakEditJikaTerkunciVerval;
use App\Models\FormulirBosK7;
use App\Models\PajakBospReguler;
use App\Models\ProfilSekolah;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Formulir BOS K7b & K7c - menu baru Pendataan BOSP (permintaan user
 * 2026-09-23), ditempatkan di sidebar tepat sesudah "Laporan Realisasi
 * BOSP (Form BPK)".
 *
 * BEDA STRUKTUR dari kebanyakan menu lain: dibuat PER BULAN (bukan
 * triwulan/tahunan) - user memilih 1 bulan (dropdown Januari-Desember,
 * default bulan berjalan) + 1 tahun (default tahun berjalan) untuk
 * menampilkan/mengisi SATU formulir penutupan-kas bulan itu, mirip
 * mengisi 1 lembar kertas fisik per bulan (BUKAN tabel 12 baris
 * sekaligus seperti Pajak BOSP Reguler - karena rincian pecahan uang
 * terlalu padat untuk ditampilkan 12x sekaligus & fidelitas visual harus
 * sama persis dengan gambar contoh, yang memang satu formulir = satu
 * bulan).
 *
 * Dua tab, KEDUANYA membaca/menulis baris FormulirBosK7 yang SAMA (sekolah
 * aktif + tahun + bulan yang dipilih) - jawaban AskUserQuestion 2026-09-23
 * mengonfirmasi kedua formulir berasal dari data yang sama:
 * - Tab "k7b": Register Penutupan Kas - rincian pecahan uang kertas/logam,
 *   Saldo Rekening Bank, Jumlah Total Penerimaan/Pengeluaran BKU, semua
 *   input manual (jawaban user "Input manual per bulan" untuk Saldo
 *   Bank/Tunai - TIDAK diambil dari Dana BOSP Tahap yang per-triwulan).
 * - Tab "k7c": Berita Acara Pemeriksaan Kas - narasi otomatis + Nomor/
 *   Tanggal SK Kepala Sekolah & Bendahara (jawaban user "diisi ulang tiap
 *   bulan, langsung di form K7c") - satu-satunya field yang HANYA ada di
 *   tab ini, tetap disimpan di baris yang sama.
 *
 * Semua rumus (Sub Jumlah 1/2, Saldo Kas Tunai, A, B, Perbedaan) SAMA
 * SEKALI TIDAK disimpan - selalu dihitung dinamis lewat method static di
 * App\Models\FormulirBosK7, dipakai konsisten di sini, Export Excel, &
 * Export PDF.
 *
 * TERKUNCI PER TRIWULAN sejak round kesembilan (permintaan user
 * 2026-09-23, poin 2, jawaban AskUserQuestion "Cakupan kuncian" - lihat
 * App\Livewire\Concerns\MenolakEditJikaTerkunciVerval): menu ini
 * berbasis BULAN, jadi triwulan yang dicek dipetakan dari bulan ybs
 * lewat App\Models\PajakBospReguler::triwulanDariBulan() (dipakai ulang
 * dari menu Pajak BOSP Reguler, BUKAN duplikat baru).
 */
#[Layout('layouts.app')]
#[Title('Formulir BOS K7b & K7c')]
class Index extends Component
{
    use HasZoomTampilan;
    use MenolakEditJikaTerkunciVerval;

    public int $tahun;

    public int $bulan;

    #[Url(as: 'tab')]
    public string $tabAktif = 'k7b';

    public ?int $profil_sekolah_id = null;

    /**
     * Data input langsung di kotak formulir - HANYA 1 "baris" aktif
     * (kunci tetap 'data', mengikuti pola x-honor-ptk-tarif-cell yang
     * butuh {rowId}.{field}, walau di sini rowId selalu sama karena
     * formulir ini menampilkan 1 bulan pada satu waktu, bukan tabel
     * banyak baris).
     *
     * @var array<string, string>
     */
    public array $baris = [];

    /** Dinaikkan setiap kali ada input yang ditolak validasi - bagian wire:key kotak (wire:ignore). */
    public int $revisiBaris = 0;

    public ?string $errorExport = null;

    /**
     * Pengaturan cetak (permintaan user 2026-09-23, round ketiga): "Jenis
     * Kertas" (A4 / HVS-Legal-F4) & "Setting Margin", dipakai bersama oleh
     * tombol Excel, PDF (download), DAN Cetak (preview) yang baru -
     * jawaban AskUserQuestion 2026-09-23 mengonfirmasi berlaku untuk
     * "PDF/Cetak DAN Excel" (bukan hanya PDF/Cetak). Default margin sesuai
     * jawaban user: Left 2.5cm, Right 2.5cm, Top 3cm, Bottom 2.5cm.
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
        $this->bulan = now()->month;

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

    protected function sekolahAktifId(): ?int
    {
        return $this->bolehKelolaSemua() ? $this->profil_sekolah_id : $this->sekolahSayaId();
    }

    /** Field angka Rupiah/lembar/keping yang bisa diedit langsung di kotak formulir Tab K7b. */
    protected function daftarFieldK7b(): array
    {
        $field = ['jumlah_total_penerimaan_bku', 'jumlah_total_pengeluaran_bku'];

        foreach (FormulirBosK7::NOMINAL_UANG_KERTAS as $nominal) {
            $field[] = FormulirBosK7::fieldLembar($nominal);
        }
        foreach (FormulirBosK7::NOMINAL_UANG_LOGAM as $nominal) {
            $field[] = FormulirBosK7::fieldKeping($nominal);
        }
        $field[] = 'saldo_rekening_bank';
        $field[] = 'saldo_kas_tunai_manual';

        return $field;
    }

    /**
     * Semua field yang bisa diedit langsung di kotak formulir (K7b + K7c
     * sekaligus, satu baris data yang sama) beserta aturan validasinya -
     * dipakai bersama oleh updated() supaya 1 property array $baris
     * cukup untuk seluruh form (pola sama seperti PajakBospReguler,
     * hanya field-nya campuran angka/teks/tanggal di sini).
     *
     * @return array<string, array<int, string>>
     */
    protected function aturanField(): array
    {
        $aturan = [];
        foreach ($this->daftarFieldK7b() as $field) {
            $aturan[$field] = ['nullable', 'integer', 'min:0'];
        }
        $aturan['penjelasan_perbedaan'] = ['nullable', 'string', 'max:2000'];
        $aturan['no_sk_kepala_sekolah'] = ['nullable', 'string', 'max:255'];
        $aturan['tanggal_sk_kepala_sekolah'] = ['nullable', 'date'];
        $aturan['no_sk_bendahara'] = ['nullable', 'string', 'max:255'];
        $aturan['tanggal_sk_bendahara'] = ['nullable', 'date'];

        return $aturan;
    }

    public function pindahTab(string $tab): void
    {
        if (! in_array($tab, ['k7b', 'k7c'], true)) {
            return;
        }

        $this->tabAktif = $tab;
        $this->errorExport = null;
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
     * URL tombol "Cetak" (preview PDF sebelum print, dibuka lewat
     * target="_blank" di Blade - BUKAN wire:click, karena Livewire tidak
     * bisa membuka tab baru langsung). Membawa jenis kertas & margin yang
     * sedang dipilih supaya preview sama persis dengan pengaturan aktif.
     */
    public function urlCetak(): ?string
    {
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId) {
            return null;
        }

        $pengaturan = $this->pengaturanCetak();

        return route('pendataan-bosp.formulir-bos-k7.cetak', [
            'tab' => $this->tabAktif,
            'tahun' => $this->tahun,
            'bulan' => $this->bulan,
            'profil_sekolah_id' => $sekolahId,
            'kertas' => $pengaturan['kertas'],
            'margin_kiri' => $pengaturan['margin']['kiri'],
            'margin_kanan' => $pengaturan['margin']['kanan'],
            'margin_atas' => $pengaturan['margin']['atas'],
            'margin_bawah' => $pengaturan['margin']['bawah'],
        ]);
    }

    /**
     * Menangkap input langsung di kotak formulir (property
     * "baris.data.{field}", SATU baris aktif dipakai bersama Tab K7b &
     * K7c) - kalau baris untuk bulan+tahun aktif belum ada di database,
     * langsung dibuat (create); kalau sudah ada, diperbarui (update).
     */
    public function updated(string $name, mixed $value): void
    {
        if (! str_starts_with($name, 'baris.data.')) {
            return;
        }

        $field = substr($name, strlen('baris.data.'));
        $aturan = $this->aturanField();

        if (! array_key_exists($field, $aturan)) {
            return;
        }

        $this->simpanField($field, $value, $aturan[$field], $name);
    }

    protected function simpanField(string $field, mixed $value, array $rules, string $namaErrorField): void
    {
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId) {
            return;
        }

        abort_unless($this->bolehKelola($sekolahId), 403);
        $this->abortJikaTerkunciVerval($sekolahId, $this->tahun, PajakBospReguler::triwulanDariBulan($this->bulan));

        $nilai = $value === '' || $value === null ? null : $value;

        $validator = Validator::make(['nilai' => $nilai], ['nilai' => $rules], [], ['nilai' => 'Nilai']);

        if ($validator->fails()) {
            $this->addError($namaErrorField, $validator->errors()->first('nilai'));
            $this->revisiBaris++;

            return;
        }

        // BUG DITEMUKAN 2026-09-23 (round kedua): kolom angka/lembar/keping
        // di database TIDAK NULLABLE (default 0, lihat migration
        // create_formulir_bos_k7_table) - tapi x-honor-ptk-tarif-cell
        // mengirim string kosong saat kotak dikosongkan pengguna, yang di
        // atas ($nilai) sudah jadi null (lolos validasi "nullable").
        // Kalau null itu langsung disimpan ke kolom NOT NULL, Postgres
        // menolak dengan QueryException 23502 (Internal Server Error) -
        // pengguna melihat ini sebagai "data hilang" karena request gagal
        // total & tidak ada yang tersimpan. Kotak dikosongkan berarti
        // nilainya 0 (konsisten dengan default kolom), BUKAN null.
        if ($nilai === null && in_array($field, $this->daftarFieldK7b(), true)) {
            $nilai = 0;
        }

        $formulir = FormulirBosK7::where('profil_sekolah_id', $sekolahId)
            ->where('tahun', $this->tahun)
            ->where('bulan', $this->bulan)
            ->first();

        if ($formulir) {
            $formulir->update([$field => $nilai]);
        } else {
            FormulirBosK7::create([
                'profil_sekolah_id' => $sekolahId,
                'tahun' => $this->tahun,
                'bulan' => $this->bulan,
                $field => $nilai,
                'created_by' => auth()->id(),
            ]);
        }

        $this->revisiBaris++;
    }

    /**
     * Menyusun seluruh data tampilan (Tab K7b & K7c sekaligus, karena
     * keduanya membaca baris yang sama) untuk sekolah+tahun+bulan aktif.
     */
    protected function dataFormulir(): array
    {
        $sekolahId = $this->sekolahAktifId();
        $sekolah = $sekolahId ? ProfilSekolah::find($sekolahId) : null;

        $formulir = $sekolah
            ? FormulirBosK7::where('profil_sekolah_id', $sekolah->id)
                ->where('tahun', $this->tahun)
                ->where('bulan', $this->bulan)
                ->first()
            : null;

        $this->baris = [];
        foreach (array_keys($this->aturanField()) as $field) {
            $nilai = $formulir?->{$field};

            if ($nilai instanceof \Illuminate\Support\Carbon) {
                $this->baris[$field] = $nilai->format('Y-m-d');
            } else {
                $this->baris[$field] = $nilai !== null ? (string) $nilai : '';
            }
        }

        $rincian = $formulir ? $formulir->toArray() : array_fill_keys($this->daftarFieldK7b(), 0);

        $tanggalPenutupan = FormulirBosK7::tanggalPenutupanKas($this->tahun, $this->bulan);
        $tanggalPenutupanLalu = FormulirBosK7::tanggalPenutupanKasBulanLalu($this->tahun, $this->bulan);

        return [
            'sekolah' => $sekolah,
            'formulir' => $formulir,
            'tanggalPenutupan' => $tanggalPenutupan,
            'tanggalPenutupanLalu' => $tanggalPenutupanLalu,
            'subJumlahKertas' => FormulirBosK7::hitungSubJumlahUangKertas($rincian),
            'subJumlahLogam' => FormulirBosK7::hitungSubJumlahUangLogam($rincian),
            'saldoKasTunai' => FormulirBosK7::hitungSaldoKasTunai($rincian),
            'saldoBku' => FormulirBosK7::hitungSaldoBku($rincian),
            'jumlahB' => FormulirBosK7::hitungJumlahB($rincian),
            'perbedaan' => FormulirBosK7::hitungPerbedaan($rincian),
            'penjelasanPerbedaan' => $formulir->penjelasan_perbedaan ?? '',
            'noSkKepalaSekolah' => $formulir->no_sk_kepala_sekolah ?? '',
            'tanggalSkKepalaSekolah' => $formulir->tanggal_sk_kepala_sekolah ?? null,
            'noSkBendahara' => $formulir->no_sk_bendahara ?? '',
            'tanggalSkBendahara' => $formulir->tanggal_sk_bendahara ?? null,
            'narasiTanggalK7c' => FormulirBosK7::terbilangTanggalNarasi($tanggalPenutupan),
        ];
    }

    public function exportExcel()
    {
        $this->errorExport = null;

        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId) {
            $this->errorExport = 'Pilih salah satu sekolah pada filter di atas terlebih dahulu sebelum Export, karena lembar tanda tangan Kepala Sekolah & Bendahara BOSP pada hasil export hanya berlaku untuk 1 sekolah.';

            return null;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);
        $data = $this->dataFormulir();

        return Excel::download(
            new FormulirBosK7Export($sekolah, $this->tahun, $this->bulan, $data, $this->pengaturanCetak()),
            'formulir-bos-k7-'.Str::slug($sekolah->nama_sekolah).'-'.FormulirBosK7::BULAN_OPTIONS[$this->bulan].'-'.$this->tahun.'.xlsx'
        );
    }

    public function exportPdf()
    {
        $this->errorExport = null;

        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId) {
            $this->errorExport = 'Pilih salah satu sekolah pada filter di atas terlebih dahulu sebelum Export.';

            return null;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);
        $data = $this->dataFormulir();

        $view = $this->tabAktif === 'k7c' ? 'pdf.formulir-bos-k7c' : 'pdf.formulir-bos-k7b';
        $pengaturan = $this->pengaturanCetak();
        $ukuranKertas = $pengaturan['kertas'] === 'f4' ? 'folio' : 'a4';

        $pdf = Pdf::loadView($view, array_merge($data, [
            'sekolah' => $sekolah,
            'tahun' => $this->tahun,
            'bulan' => $this->bulan,
            'labelBulan' => FormulirBosK7::BULAN_OPTIONS[$this->bulan],
            'margin' => $pengaturan['margin'],
        ]))->setPaper($ukuranKertas, 'portrait');

        $kodeForm = $this->tabAktif === 'k7c' ? 'k7c' : 'k7b';
        $namaFile = 'formulir-bos-'.$kodeForm.'-'.Str::slug($sekolah->nama_sekolah).'-'.FormulirBosK7::BULAN_OPTIONS[$this->bulan].'-'.$this->tahun.'.pdf';
        $pathSementara = tempnam(sys_get_temp_dir(), 'formulir-bos-k7-').'.pdf';
        file_put_contents($pathSementara, $pdf->output());

        return response()->download($pathSementara, $namaFile)->deleteFileAfterSend(true);
    }

    public function render()
    {
        $data = $this->dataFormulir();

        return view('livewire.pendataan-bosp.formulir-bos-k7.index', array_merge($data, [
            'tabAktif' => $this->tabAktif,
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            'bulanOptions' => FormulirBosK7::BULAN_OPTIONS,
            'tahunOptions' => array_reverse(range(now()->year - 2, now()->year + 1)),
            'sekolahOptions' => ProfilSekolah::orderBy('nama_sekolah')->get(['id', 'nama_sekolah']),
            'kertasOptions' => $this->daftarKertasOptions(),
            'urlCetak' => $this->urlCetak(),
            // Kuncian UI - permintaan user 2026-09-23 (round kesepuluh,
            // poin 1) - lihat docblock lengkap App\Livewire\Concerns\
            // MenolakEditJikaTerkunciVerval::terkunciVervalUntukTampilan().
            // Menu ini berbasis BULAN, triwulan dipetakan lewat
            // PajakBospReguler::triwulanDariBulan() (dipakai ulang, sama
            // seperti abortJikaTerkunciVerval() di atas).
            'terkunciTriwulanIni' => $this->terkunciVervalUntukTampilan(PajakBospReguler::triwulanDariBulan($this->bulan)),
        ]));
    }
}
