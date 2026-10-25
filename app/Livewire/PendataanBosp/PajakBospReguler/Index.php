<?php

namespace App\Livewire\PendataanBosp\PajakBospReguler;

use App\Models\AksesDataLog;
use App\Exports\PajakBospRegulerExport;
use App\Exports\PajakBospRegulerRekapExport;
use App\Livewire\Concerns\HasZoomTampilan;
use App\Livewire\Concerns\MenolakEditJikaTerkunciVerval;
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
 * Pajak BOSP Reguler - Pendataan BOSP (permintaan user 2026-09-11, Part
 * 23), disertai 2 gambar contoh template Excel "REKAPITULASI PAJAK
 * REGULER DAN PAJAK DAERAH - DANA BANTUAN OPERASIONAL SEKOLAH (BOS)".
 *
 * BEDA STRUKTUR dari kebanyakan menu Pendataan BOSP lain di aplikasi ini
 * (yang biasanya "banyak baris bebas per sekolah", mis. Penerimaan Honor
 * PTK/Rincian Belanja Modal): menu ini SELALU tepat 12 baris TETAP per
 * sekolah per tahun (1 baris = 1 bulan, Januari-Desember) - baris bukan
 * ditambah/dihapus user, hanya DIISI per kotak, sama seperti pola
 * RekapRkas (baris tetap per sekolah) tapi di sini barisnya adalah BULAN,
 * bukan sekolah.
 *
 * Dua tab (BEDA dari 2-lapis-tab-utama+triwulan menu lain):
 * - Tab 1 "per_sekolah": tabel 12 bulan (Debit 5 kolom + Kredit 5 kolom,
 *   Jumlah & Saldo read-only hasil rumus) UNTUK SATU SEKOLAH YANG SEDANG
 *   DIPILIH, + tabel Triwulan (4 baris, SELURUHNYA read-only, agregat
 *   otomatis dari 12 bulan - lihat PajakBospReguler::hitungTriwulan()).
 *   Superadmin WAJIB memilih 1 sekolah dulu lewat dropdown sebelum tabel
 *   terisi; Admin BOSP otomatis terkunci ke sekolahnya sendiri (tidak ada
 *   dropdown).
 * - Tab 2 "rekap" (HANYA Superadmin - sesuai jawaban AskUserQuestion "1
 *   baris per sekolah, total setahun"): daftar SEMUA sekolah, masing-
 *   masing 1 baris menampilkan total Debit/Kredit setahun & Saldo akhir
 *   (Desember) untuk tahun yang dipilih.
 *
 * Rumus Jumlah (per bulan) & Saldo (kumulatif, carry-over antar bulan,
 * RESET tiap awal tahun) & Triwulan (otomatis) SAMA SEKALI TIDAK disimpan
 * ke database - selalu dihitung dinamis dari 10 kolom pajak mentah lewat
 * method static di App\Models\PajakBospReguler, supaya rumus TIDAK dobel-
 * tulis di Livewire/Export Excel/Export PDF manapun.
 *
 * TERKUNCI PER TRIWULAN sejak round kesembilan (permintaan user
 * 2026-09-23, poin 2, jawaban AskUserQuestion "Cakupan kuncian" - lihat
 * App\Livewire\Concerns\MenolakEditJikaTerkunciVerval): menu ini
 * berbasis BULAN, jadi triwulan yang dicek dipetakan dari bulan ybs
 * lewat App\Models\PajakBospReguler::triwulanDariBulan().
 */
#[Layout('layouts.app')]
#[Title('Pajak BOSP Reguler')]
class Index extends Component
{
    use HasZoomTampilan;
    use MenolakEditJikaTerkunciVerval;

    public int $tahun;

    #[Url(as: 'tab')]
    public string $tab = 'per_sekolah';

    public ?int $profil_sekolah_id = null;

    /**
     * Status modal detail "Sekolah Sudah/Belum Input" (ringkasan jumlah
     * sekolah yang sudah mengisi menu ini untuk tahun aktif) - permintaan
     * user 2026-10-25. Pola SAMA seperti $statusInputDipilih pada
     * App\Livewire\PendataanOps\Lampiran2a\Index, TAPI di sini TIDAK
     * dibatasi Superadmin - Admin BOSP ikut melihat ringkasan SELURUH
     * sekolah (jawaban AskUserQuestion 2026-10-25 "Sama persis seperti
     * Superadmin").
     */
    public ?string $statusInputDipilih = null;

    /**
     * Data input LANGSUNG di kotak tabel bulan (Tab 1 saja) - kunci =
     * nomor bulan (1-12), diisi ulang tiap render() dari data terbaru di
     * database untuk sekolah+tahun yang sedang aktif.
     *
     * @var array<int, array<string, string>>
     */
    public array $baris = [];

    /**
     * Dinaikkan PER BULAN setiap kali ada input yang ditolak validasi -
     * dipakai sebagai bagian wire:key kotak (wire:ignore) bulan tsb.
     *
     * @var array<int, int>
     */
    public array $revisiBaris = [];

    public ?string $errorExport = null;

    /**
     * Bulan (1-12) yang sedang dikonfirmasi untuk dihapus datanya - lihat
     * konfirmasiHapusBulan()/hapus(). Menyediakan operasi "Delete" yang
     * eksplisit (selain Create/Update lewat kotak tabel & Read lewat
     * tabel itu sendiri) sesuai permintaan user "dilengkapi CRUD".
     */
    public ?int $confirmingHapusBulan = null;

    /**
     * Status modal konfirmasi "Hapus Semua" (seluruh 12 bulan sekolah+
     * tahun aktif sekaligus) - permintaan user 2026-10-25, property
     * TERPISAH dari $confirmingHapusBulan di atas (per-bulan) supaya 2
     * modal konfirmasi tidak saling tumpang tindih.
     */
    public bool $confirmingHapusSemua = false;

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
    }

    protected function bolehKelolaSemua(): bool
    {
        return auth()->user()->isSuperadmin();
    }

    /**
     * Ringkasan jumlah SEKOLAH yang sudah/belum punya data pada menu
     * ini untuk tahun aktif (permintaan user 2026-10-25: "tambahkan
     * jumlah total dari setiap menu dari semua sekolah yang sudah
     * input data ... dan munculkan juga di akun admin bosp nya") -
     * dihitung per TAHUN (bukan triwulan/jenis/bulan tertentu - jawaban
     * AskUserQuestion 2026-10-25 "Semua 12 bulan ... tahun yang sedang
     * aktif") & SELALU tampil utk Superadmin MAUPUN Admin BOSP (parity
     * penuh, bukan Superadmin-only - jawaban AskUserQuestion 2026-10-25
     * "Sama persis seperti Superadmin"). SELALU tampil (menu
     * ini tidak punya filter "1 sekolah" pada tampilan SEMUA
     * sekolah, jadi tidak ada state yang perlu disembunyikan).
     */
    protected function ringkasan(): ?array
    {
        $idSudahInput = PajakBospReguler::where('tahun', $this->tahun)->distinct()->pluck('profil_sekolah_id');

        return [
            'sekolahSudahInput' => $idSudahInput->count(),
            'sekolahBelumInput' => ProfilSekolah::count() - $idSudahInput->count(),
        ];
    }

    /**
     * Daftar NAMA SEKOLAH (+ status Negeri/Swasta) yang "sudah"/"belum"
     * punya data pada menu ini untuk tahun aktif - sumber data SAMA
     * dengan ringkasan() di atas, dipakai mengisi modal detail yang
     * dibuka lewat lihatDetailSekolahInput(). Dikelompokkan Negeri/
     * Swasta langsung di Blade lewat
     * <x-daftar-sekolah-input-negeri-swasta>.
     *
     * @param  'sudah'|'belum'  $status
     * @return \Illuminate\Support\Collection<int, \App\Models\ProfilSekolah>
     */
    protected function daftarSekolahInput(string $status): \Illuminate\Support\Collection
    {
        $idSudahInput = PajakBospReguler::where('tahun', $this->tahun)->distinct()->pluck('profil_sekolah_id');

        $query = ProfilSekolah::query()->urutStandar();

        return $status === 'sudah'
            ? $query->whereIn('id', $idSudahInput)->get(['id', 'nama_sekolah', 'status'])
            : $query->whereNotIn('id', $idSudahInput)->get(['id', 'nama_sekolah', 'status']);
    }

    /**
     * Buka modal detail daftar sekolah "sudah input"/"belum input"
     * (permintaan user 2026-10-25) - dipanggil dari badge pada
     * komponen <x-ringkasan-negeri-swasta>.
     *
     * @param  'sudah'|'belum'  $status
     */
    public function lihatDetailSekolahInput(string $status): void
    {
        $this->statusInputDipilih = $status;
        $this->dispatch('open-modal', 'pajak-bosp-reguler-sekolah-input-detail');
    }

    public function tutupDetailSekolahInput(): void
    {
        $this->statusInputDipilih = null;
        $this->dispatch('close-modal', 'pajak-bosp-reguler-sekolah-input-detail');
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
     * ID sekolah yang datanya SEDANG DITAMPILKAN pada Tab 1 - untuk
     * Superadmin adalah sekolah yang dipilih lewat dropdown (bisa null
     * kalau belum memilih), untuk Admin BOSP SELALU sekolahnya sendiri.
     */
    protected function sekolahAktifId(): ?int
    {
        return $this->bolehKelolaSemua() ? $this->profil_sekolah_id : $this->sekolahSayaId();
    }

    /**
     * 10 kolom pajak (5 Debit + 5 Kredit) yang bisa diedit langsung di
     * kotak tabel - Jumlah & Saldo TIDAK pernah masuk daftar ini karena
     * keduanya SELALU hasil rumus, tidak bisa diedit dari jalur manapun.
     */
    protected function daftarField(): array
    {
        return array_merge(PajakBospReguler::FIELD_DEBIT, PajakBospReguler::FIELD_KREDIT);
    }

    public function pindahTab(string $tab): void
    {
        if (! in_array($tab, ['per_sekolah', 'rekap'], true)) {
            return;
        }

        if ($tab === 'rekap' && ! $this->bolehKelolaSemua()) {
            return;
        }

        $this->tab = $tab;
        $this->errorExport = null;
    }

    /**
     * Pilih sekolah aktif pada Tab 1 - HANYA Superadmin (Admin BOSP
     * sudah otomatis terkunci ke sekolahnya sejak mount(), tidak lewat
     * jalur ini).
     */
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

    /**
     * Menangkap input langsung di kotak tabel bulan (property
     * "baris.{bulan}.{field}"). Karena barisnya TETAP (bulan 1-12, bukan
     * baris bebas), tidak ada konsep "baris placeholder" seperti menu
     * lain - kalau baris untuk bulan itu belum ada di database, langsung
     * dibuat (create); kalau sudah ada, diperbarui (update).
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

        [, $bulanMentah, $field] = $bagian;
        $bulan = (int) $bulanMentah;

        if ($bulan < 1 || $bulan > 12) {
            return;
        }

        if (! in_array($field, $this->daftarField(), true)) {
            return;
        }

        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId) {
            // Superadmin belum memilih sekolah - seharusnya tabel tidak
            // bisa diketik sama sekali dalam kondisi ini (lihat view),
            // tapi dijaga juga di sini untuk keamanan.
            return;
        }

        abort_unless($this->bolehKelola($sekolahId), 403);
        $this->abortJikaTerkunciVerval($sekolahId, $this->tahun, PajakBospReguler::triwulanDariBulan($bulan));

        $nilai = $value === '' || $value === null ? null : $value;

        $validator = Validator::make(
            ['nilai' => $nilai],
            ['nilai' => ['nullable', 'integer', 'min:0']],
            [],
            ['nilai' => 'Nilai']
        );

        if ($validator->fails()) {
            $this->addError($name, $validator->errors()->first('nilai'));
            $this->revisiBaris[$bulan] = ($this->revisiBaris[$bulan] ?? 0) + 1;

            return;
        }

        $nilaiInt = $nilai === null ? null : (int) $nilai;

        $baris = PajakBospReguler::where('profil_sekolah_id', $sekolahId)
            ->where('tahun', $this->tahun)
            ->where('bulan', $bulan)
            ->first();

        if ($baris) {
            $baris->update([$field => $nilaiInt]);
        } else {
            PajakBospReguler::create([
                'profil_sekolah_id' => $sekolahId,
                'tahun' => $this->tahun,
                'bulan' => $bulan,
                $field => $nilaiInt,
                'created_by' => auth()->id(),
            ]);
        }

        $this->revisiBaris[$bulan] = ($this->revisiBaris[$bulan] ?? 0) + 1;
    }

    /**
     * Konfirmasi hapus SELURUH data pajak bulan tertentu (Delete penuh,
     * bukan cuma mengosongkan 1 kotak) untuk sekolah aktif+tahun aktif.
     */
    public function konfirmasiHapusBulan(int $bulan): void
    {
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId || ! $this->bolehKelola($sekolahId)) {
            return;
        }

        $this->confirmingHapusBulan = $bulan;
        $this->dispatch('open-modal', 'pajak-bosp-reguler-hapus');
    }

    public function batalHapusBulan(): void
    {
        $this->confirmingHapusBulan = null;
        $this->dispatch('close-modal', 'pajak-bosp-reguler-hapus');
    }

    public function hapusBulan(): void
    {
        $sekolahId = $this->sekolahAktifId();
        $bulan = $this->confirmingHapusBulan;

        if ($sekolahId && $bulan && $this->bolehKelola($sekolahId)) {
            $this->abortJikaTerkunciVerval($sekolahId, $this->tahun, PajakBospReguler::triwulanDariBulan($bulan));

            PajakBospReguler::where('profil_sekolah_id', $sekolahId)
                ->where('tahun', $this->tahun)
                ->where('bulan', $bulan)
                ->delete();

            $this->revisiBaris[$bulan] = ($this->revisiBaris[$bulan] ?? 0) + 1;
            session()->flash('status', 'Data Pajak BOSP Reguler bulan '.(PajakBospReguler::BULAN_OPTIONS[$bulan] ?? $bulan).' berhasil dihapus.');
        }

        $this->confirmingHapusBulan = null;
        $this->dispatch('close-modal', 'pajak-bosp-reguler-hapus');
    }

    /**
     * Konfirmasi hapus SELURUH data pajak (12 bulan) sekolah+tahun aktif
     * sekaligus - permintaan user 2026-10-25 ("tambahkan tombol hapus
     * pada menu pajak Pajak BOSP Reguler dengan pilihan semua pajak di
     * hapus"), pola modal konfirmasi SAMA seperti konfirmasiHapusBulan()
     * di atas (BEDA property supaya 2 modal tidak saling tumpang tindih).
     */
    public function konfirmasiHapusSemua(): void
    {
        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId || ! $this->bolehKelola($sekolahId)) {
            return;
        }

        $this->confirmingHapusSemua = true;
        $this->dispatch('open-modal', 'pajak-bosp-reguler-hapus-semua');
    }

    public function batalHapusSemua(): void
    {
        $this->confirmingHapusSemua = false;
        $this->dispatch('close-modal', 'pajak-bosp-reguler-hapus-semua');
    }

    /**
     * Hapus SELURUH 12 bulan data pajak sekolah+tahun aktif sekaligus.
     * Mengecek kuncian Verval SELURUH triwulan (1-4) TERLEBIH DAHULU
     * sebelum menghapus apa pun - supaya tidak terjadi hapus sebagian
     * (partial delete) kalau ternyata ada 1 triwulan yang sudah di-Verval
     * "Sesuai" (lihat abortJikaTerkunciVerval()), BEDA dari hapusBulan()
     * di atas yang hanya mengecek 1 triwulan punya bulan itu sendiri.
     */
    public function hapusSemua(): void
    {
        $sekolahId = $this->sekolahAktifId();

        if ($sekolahId && $this->bolehKelola($sekolahId)) {
            foreach ([1, 2, 3, 4] as $triwulan) {
                $this->abortJikaTerkunciVerval($sekolahId, $this->tahun, $triwulan);
            }

            PajakBospReguler::where('profil_sekolah_id', $sekolahId)
                ->where('tahun', $this->tahun)
                ->delete();

            for ($bulan = 1; $bulan <= 12; $bulan++) {
                $this->revisiBaris[$bulan] = ($this->revisiBaris[$bulan] ?? 0) + 1;
            }

            session()->flash('status', 'Seluruh data Pajak BOSP Reguler tahun '.$this->tahun.' untuk sekolah ini berhasil dihapus.');
        }

        $this->confirmingHapusSemua = false;
        $this->dispatch('close-modal', 'pajak-bosp-reguler-hapus-semua');
    }

    /**
     * Menyusun data Tab 1: 12 baris bulan (mentah + Jumlah Debit/Kredit
     * hasil rumus) untuk sekolah aktif, DITAMBAH agregat Triwulan
     * otomatis. Kalau belum ada sekolah aktif (Superadmin belum memilih),
     * seluruh kotak tetap ditampilkan KOSONG & Jumlah/Saldo/Triwulan
     * semuanya nol - tabel tidak bisa diisi (dicegah juga di updated()).
     */
    protected function dataPerSekolah(): array
    {
        $sekolahId = $this->sekolahAktifId();
        $sekolah = $sekolahId ? ProfilSekolah::find($sekolahId) : null;

        $rows = $sekolah
            ? PajakBospReguler::where('profil_sekolah_id', $sekolah->id)
                ->where('tahun', $this->tahun)
                ->get()
                ->keyBy('bulan')
            : collect();

        $this->baris = [];
        $jumlahPerBulan = [];
        $rincianPerBulan = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $row = $rows->get($bulan);

            $data = [];
            foreach ($this->daftarField() as $field) {
                $data[$field] = $row && $row->{$field} !== null ? (string) $row->{$field} : '';
            }
            $this->baris[$bulan] = $data;

            $jumlahPerBulan[$bulan] = [
                'debit' => $row ? PajakBospReguler::hitungJumlahDebit($row) : 0,
                'kredit' => $row ? PajakBospReguler::hitungJumlahKredit($row) : 0,
            ];

            $rincianPerBulan[$bulan] = [];
            foreach ($this->daftarField() as $field) {
                $rincianPerBulan[$bulan][$field] = (int) ($data[$field] !== '' ? $data[$field] : 0);
            }
        }

        $saldoPerBulan = PajakBospReguler::hitungSaldoBerjalan($jumlahPerBulan);
        $triwulanData = PajakBospReguler::hitungTriwulan($jumlahPerBulan, $saldoPerBulan, $rincianPerBulan);

        // Kuncian UI PER BULAN - permintaan user 2026-09-23 (round
        // kesepuluh, poin 1) - lihat docblock lengkap App\Livewire\
        // Concerns\MenolakEditJikaTerkunciVerval::terkunciVervalUntukTampilan().
        // BEDA dari 9 menu sumber lain yang hanya punya 1 triwulan aktif
        // sekaligus (1 flag boolean cukup) - menu ini menampilkan
        // KESELURUHAN 12 bulan (4 triwulan) SEKALIGUS dalam 1 tabel, jadi
        // kuncian dihitung PER BULAN (dipetakan ke triwulan masing2 lewat
        // PajakBospReguler::triwulanDariBulan(), sama seperti
        // abortJikaTerkunciVerval() di updated()/hapusBulan() di atas).
        $terkunciPerBulan = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $terkunciPerBulan[$bulan] = $this->terkunciVervalUntukTampilan(PajakBospReguler::triwulanDariBulan($bulan));
        }

        // Baris "Jumlah" paling bawah tabel bulanan - total setahun per
        // kolom mentah (10 kolom pajak) + total Jumlah Debit/Kredit +
        // Saldo akhir (Desember) - pola sama seperti baris Jumlah pada
        // Rekap RKAS/Penerimaan Honor PTK.
        $totalRaw = [];
        foreach ($this->daftarField() as $field) {
            $totalRaw[$field] = 0;
            for ($bulan = 1; $bulan <= 12; $bulan++) {
                $totalRaw[$field] += (int) ($this->baris[$bulan][$field] !== '' ? $this->baris[$bulan][$field] : 0);
            }
        }

        return [
            'sekolah' => $sekolah,
            'jumlahPerBulan' => $jumlahPerBulan,
            'saldoPerBulan' => $saldoPerBulan,
            'triwulanData' => $triwulanData,
            'totalRaw' => $totalRaw,
            'totalJumlahDebit' => array_sum(array_column($jumlahPerBulan, 'debit')),
            'totalJumlahKredit' => array_sum(array_column($jumlahPerBulan, 'kredit')),
            'saldoAkhir' => $saldoPerBulan[12],
            'rekapSekolah' => null,
            'rekapTotal' => null,
            'terkunciPerBulan' => $terkunciPerBulan,
        ];
    }

    /**
     * Menyusun data Tab 2 (Superadmin saja): 1 baris per sekolah,
     * total Debit/Kredit setahun & Saldo akhir (Desember) untuk tahun
     * yang dipilih.
     */
    protected function dataRekap()
    {
        $daftarSekolah = ProfilSekolah::with(['pajakBospReguler' => function ($q) {
            $q->where('tahun', $this->tahun);
        }])
            ->urutStandar()
            ->get();

        $rekapSekolah = $daftarSekolah->map(function (ProfilSekolah $sekolah) {
            $rows = $sekolah->pajakBospReguler->keyBy('bulan');
            $jumlahPerBulan = [];

            for ($bulan = 1; $bulan <= 12; $bulan++) {
                $row = $rows->get($bulan);
                $jumlahPerBulan[$bulan] = [
                    'debit' => $row ? PajakBospReguler::hitungJumlahDebit($row) : 0,
                    'kredit' => $row ? PajakBospReguler::hitungJumlahKredit($row) : 0,
                ];
            }

            $saldoPerBulan = PajakBospReguler::hitungSaldoBerjalan($jumlahPerBulan);

            return [
                'sekolah' => $sekolah,
                'total_debit' => array_sum(array_column($jumlahPerBulan, 'debit')),
                'total_kredit' => array_sum(array_column($jumlahPerBulan, 'kredit')),
                'saldo_akhir' => $saldoPerBulan[12],
                'rincian' => PajakBospReguler::hitungTotalRincian($rows),
            ];
        });

        // Baris "Jumlah" total seluruh sekolah pada tabel Tab 2 (tampilan
        // aplikasi) - permintaan user 2026-09-15 (Part 25), sebelumnya
        // baris ini keliru hanya ditambahkan pada Export Excel-nya saja,
        // padahal diminta juga pada tabel di menu/aplikasinya sendiri.
        $rekapTotal = [
            'rincian' => [],
            'total_debit' => $rekapSekolah->sum('total_debit'),
            'total_kredit' => $rekapSekolah->sum('total_kredit'),
            'saldo_akhir' => $rekapSekolah->sum('saldo_akhir'),
        ];
        foreach (array_merge(PajakBospReguler::FIELD_DEBIT, PajakBospReguler::FIELD_KREDIT) as $field) {
            $rekapTotal['rincian'][$field] = $rekapSekolah->sum(fn (array $rekap) => $rekap['rincian'][$field] ?? 0);
        }

        return [
            'sekolah' => null,
            'jumlahPerBulan' => null,
            'saldoPerBulan' => null,
            'triwulanData' => null,
            'totalRaw' => null,
            'totalJumlahDebit' => null,
            'totalJumlahKredit' => null,
            'saldoAkhir' => null,
            'rekapSekolah' => $rekapSekolah,
            'rekapTotal' => $rekapTotal,
            // Tab 2 (rekap) HANYA Superadmin & READ-ONLY (tidak ada kotak
            // input sama sekali) - kuncian UI tidak relevan di sini, tapi
            // key ini TETAP disediakan (array kosong per bulan) supaya
            // index.blade.php TIDAK error "Undefined variable" saat tab
            // rekap yang aktif - lihat catatan sama pada dataPerSekolah().
            'terkunciPerBulan' => array_fill(1, 12, false),
        ];
    }

    /**
     * Export Excel Tab 1 (per sekolah) - Superadmin WAJIB memilih 1
     * sekolah dulu (sama seperti pola "wajib pilih sekolah sebelum
     * Export" pada Lampiran2a/RincianBelanjaModal), Admin BOSP otomatis
     * pakai sekolahnya sendiri.
     */
    public function exportExcel()
    {
        $this->errorExport = null;
        AksesDataLog::catat(AksesDataLog::JENIS_UNDUH, 'Pajak BOSP Reguler', 'Excel');

        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId) {
            $this->errorExport = 'Pilih salah satu sekolah pada filter di atas terlebih dahulu sebelum Export, karena lembar tanda tangan Kepala Sekolah & Bendahara BOSP pada hasil export hanya berlaku untuk 1 sekolah.';

            return null;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);
        $data = $this->dataPerSekolah();

        return Excel::download(
            new PajakBospRegulerExport(
                $sekolah,
                $this->tahun,
                $this->baris,
                $data['jumlahPerBulan'],
                $data['saldoPerBulan'],
                $data['triwulanData'],
                $data['totalRaw'],
                $data['totalJumlahDebit'],
                $data['totalJumlahKredit'],
                $data['saldoAkhir'],
            ),
            'pajak-bosp-reguler-'.Str::slug($sekolah->nama_sekolah).'-'.$this->tahun.'.xlsx'
        );
    }

    /**
     * Export Excel Tab 2 (rekap seluruh sekolah) - Superadmin saja.
     */
    public function exportExcelRekap()
    {
        if (! $this->bolehKelolaSemua()) {
            return null;
        }
        AksesDataLog::catat(AksesDataLog::JENIS_UNDUH, 'Pajak BOSP Reguler', 'Excel (Rekap)');

        $data = $this->dataRekap();

        return Excel::download(
            new PajakBospRegulerRekapExport($data['rekapSekolah'], $this->tahun),
            'rekapitulasi-pajak-bosp-reguler-seluruh-sekolah-'.$this->tahun.'.xlsx'
        );
    }

    /**
     * Export PDF Tab 1 (per sekolah) - pola temp-file+response()->download()
     * sama seperti Unduhan\Index (barryvdh/laravel-dompdf, SUDAH terpasang
     * & dipakai di aplikasi ini sebelumnya, BUKAN pustaka baru).
     */
    public function exportPdf()
    {
        $this->errorExport = null;
        AksesDataLog::catat(AksesDataLog::JENIS_UNDUH, 'Pajak BOSP Reguler', 'PDF');

        $sekolahId = $this->sekolahAktifId();

        if (! $sekolahId) {
            $this->errorExport = 'Pilih salah satu sekolah pada filter di atas terlebih dahulu sebelum Export.';

            return null;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);
        $data = $this->dataPerSekolah();

        $pdf = Pdf::loadView('pdf.pajak-bosp-reguler', [
            'sekolah' => $sekolah,
            'tahun' => $this->tahun,
            'baris' => $this->baris,
            'jumlahPerBulan' => $data['jumlahPerBulan'],
            'saldoPerBulan' => $data['saldoPerBulan'],
            'triwulanData' => $data['triwulanData'],
            'totalRaw' => $data['totalRaw'],
            'totalJumlahDebit' => $data['totalJumlahDebit'],
            'totalJumlahKredit' => $data['totalJumlahKredit'],
            'saldoAkhir' => $data['saldoAkhir'],
            'bulanOptions' => PajakBospReguler::BULAN_OPTIONS,
            'triwulanOptions' => PajakBospReguler::TRIWULAN_OPTIONS,
            'labelPajak' => array_values(PajakBospReguler::LABEL_PAJAK),
            'fieldDebit' => PajakBospReguler::FIELD_DEBIT,
            'fieldKredit' => PajakBospReguler::FIELD_KREDIT,
        ])->setPaper('a4', 'landscape');

        $namaFile = 'pajak-bosp-reguler-'.Str::slug($sekolah->nama_sekolah).'-'.$this->tahun.'.pdf';
        $pathSementara = tempnam(sys_get_temp_dir(), 'pajak-bosp-reguler-').'.pdf';
        file_put_contents($pathSementara, $pdf->output());

        return response()->download($pathSementara, $namaFile)->deleteFileAfterSend(true);
    }

    /**
     * Export PDF Tab 2 (rekap seluruh sekolah) - Superadmin saja.
     */
    public function exportPdfRekap()
    {
        if (! $this->bolehKelolaSemua()) {
            return null;
        }
        AksesDataLog::catat(AksesDataLog::JENIS_UNDUH, 'Pajak BOSP Reguler', 'PDF (Rekap)');

        $data = $this->dataRekap();

        $pdf = Pdf::loadView('pdf.pajak-bosp-reguler-rekap', [
            'rekapSekolah' => $data['rekapSekolah'],
            'tahun' => $this->tahun,
            'labelPajak' => array_values(PajakBospReguler::LABEL_PAJAK),
            'fieldDebit' => PajakBospReguler::FIELD_DEBIT,
            'fieldKredit' => PajakBospReguler::FIELD_KREDIT,
        ])->setPaper('a4', 'landscape');

        $namaFile = 'rekapitulasi-pajak-bosp-reguler-seluruh-sekolah-'.$this->tahun.'.pdf';
        $pathSementara = tempnam(sys_get_temp_dir(), 'pajak-bosp-reguler-rekap-').'.pdf';
        file_put_contents($pathSementara, $pdf->output());

        return response()->download($pathSementara, $namaFile)->deleteFileAfterSend(true);
    }

    public function render()
    {
        if ($this->tab === 'rekap' && ! $this->bolehKelolaSemua()) {
            $this->tab = 'per_sekolah';
        }

        $data = $this->tab === 'rekap'
            ? $this->dataRekap()
            : $this->dataPerSekolah();

        return view('livewire.pendataan-bosp.pajak-bosp-reguler.index', array_merge($data, [
            'tab' => $this->tab,
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            'ringkasan' => $this->ringkasan(),
            'statusInputDipilih' => $this->statusInputDipilih,
            'daftarSekolahInput' => $this->statusInputDipilih
                ? $this->daftarSekolahInput($this->statusInputDipilih)
                : collect(),
            'bulanOptions' => PajakBospReguler::BULAN_OPTIONS,
            'triwulanOptions' => PajakBospReguler::TRIWULAN_OPTIONS,
            'tahunOptions' => array_reverse(range(now()->year - 2, now()->year + 1)),
            'sekolahOptions' => ProfilSekolah::urutStandar()->get(['id', 'nama_sekolah']),
        ]));
    }
}
