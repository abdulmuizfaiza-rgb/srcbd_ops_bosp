<?php

namespace App\Livewire\CekDatabaseAplikasi;

use App\Livewire\Concerns\HasZoomTampilan;
use App\Models\AksesDataLog;
use App\Models\BelanjaHonorKegiatan;
use App\Models\BiayaPendaftaranLomba;
use App\Models\DanaBospTahap;
use App\Models\FailedLoginAttempt;
use App\Models\FormulirBosK7;
use App\Models\Lampiran2a;
use App\Models\Lampiran2b;
use App\Models\Lampiran2c;
use App\Models\LanggananDayaJasa;
use App\Models\LaporanRealisasiBosp;
use App\Models\PajakBospReguler;
use App\Models\PendataanBosp;
use App\Models\PendataanOps;
use App\Models\PenerimaanHonorPtk;
use App\Models\ProfilSekolah;
use App\Models\RekapRkas;
use App\Models\RincianBelanjaBarangHabisPakai;
use App\Models\RincianBelanjaModal;
use App\Models\RincianBelanjaModalBmd;
use App\Models\RincianPemeliharaan;
use App\Models\RincianPemeliharaanPc;
use App\Models\StockOpnameBarangPersediaan;
use App\Models\SuratTpg;
use App\Models\User;
use App\Models\VervalRealisasiBosp;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Menu "Cek Database dan Aplikasi" - BARU, permintaan user 2026-09-27.
 *
 * 3 tab:
 * 1. Integritas Data: cocokkan data yang tampil di aplikasi dengan yang
 *    tersimpan di database - cari (a) data yatim (induk sekolah sudah
 *    dihapus), (b) data "terkunci" di layar tapi masih tersimpan di
 *    database (pola yang sama seperti kasus Rekap RKAS 2026-09-26), dan
 *    (c) data duplikat pada kolom yang seharusnya unik per sekolah+tahun.
 *    Setiap temuan bisa dibersihkan otomatis lewat tombol per kategori
 *    (dengan konfirmasi dulu).
 * 2. Percobaan Login Gagal: daftar percobaan login yang GAGAL (lihat
 *    App\Models\FailedLoginAttempt, dicatat dari LoginForm).
 * 3. Log Akses Data: daftar setiap kali akun membuka halaman data atau
 *    mengunduh data (lihat App\Models\AksesDataLog).
 *
 * SEMUA pengecekan integritas di sini bersifat BACA DULU baru HAPUS -
 * tombol "Bersihkan Otomatis" selalu meminta konfirmasi dulu (pola sama
 * seperti tombol "Hapus Terpilih" di modul lain), dan sebelum benar-benar
 * menghapus, query temuannya DIULANG LAGI (fresh) supaya tidak menghapus
 * baris yang mungkin sudah berubah/diperbaiki sejak halaman ini dibuka.
 */
#[Layout('layouts.app')]
#[Title('Cek Database dan Aplikasi')]
class Index extends Component
{
    use WithPagination;
    use HasZoomTampilan;

    #[Url(as: 'tab')]
    public string $tab = 'integritas';

    // --- State tab Integritas Data ---
    public ?string $confirmingBersihkan = null;

    // --- State tab Percobaan Login Gagal ---
    public string $searchLoginGagal = '';

    // --- State tab Log Akses Data ---
    public string $searchAksesData = '';

    public string $filterJenisAksi = '';

    // --- State tab Cek Kecocokan Data (permintaan user 2026-09-27) ---
    public string $searchKecocokan = '';

    /**
     * Daftar modul yang datanya terikat ke satu sekolah (profil_sekolah_id)
     * - dipakai bareng oleh cekDataYatim() & cekDataDuplikat(). Label di
     * sini SENGAJA disamakan dengan nama menu di sidebar supaya mudah
     * dikenali Superadmin.
     *
     * @return array<string, class-string>
     */
    private function modulTerikatSekolah(): array
    {
        return [
            'Lampiran 2A' => Lampiran2a::class,
            'Lampiran 2B' => Lampiran2b::class,
            'Lampiran 2C' => Lampiran2c::class,
            'Surat TPG' => SuratTpg::class,
            'Rekap RKAS' => RekapRkas::class,
            'Dana BOSP Tahap' => DanaBospTahap::class,
            'Penerimaan Honor PTK' => PenerimaanHonorPtk::class,
            'Langganan Daya dan Jasa' => LanggananDayaJasa::class,
            'Belanja Pemeliharaan Bangunan' => RincianPemeliharaan::class,
            'Belanja Pemeliharaan PC' => RincianPemeliharaanPc::class,
            'Biaya Pendaftaran Lomba' => BiayaPendaftaranLomba::class,
            'Belanja Honor Kegiatan' => BelanjaHonorKegiatan::class,
            'Rincian Belanja Modal' => RincianBelanjaModal::class,
            'Rincian Belanja Modal (BMD)' => RincianBelanjaModalBmd::class,
            'Rincian Belanja Barang Habis Pakai' => RincianBelanjaBarangHabisPakai::class,
            'Pajak BOSP Reguler' => PajakBospReguler::class,
            'Laporan Realisasi BOSP' => LaporanRealisasiBosp::class,
            'Stock Opname Barang Persediaan' => StockOpnameBarangPersediaan::class,
            'Formulir BOS K7' => FormulirBosK7::class,
            'Verval Realisasi BOSP' => VervalRealisasiBosp::class,
            'Pendataan OPS (Identitas)' => PendataanOps::class,
            'Pendataan BOSP (Identitas)' => PendataanBosp::class,
        ];
    }

    /**
     * Modul yang punya kunci unik per-kombinasi kolom (sesuai unique
     * constraint di database) - dipakai oleh cekDataDuplikat() sebagai
     * jaring pengaman tambahan (idealnya selalu kosong karena sudah
     * dicegah oleh database, tapi tetap dicek untuk memastikan
     * kecocokan data).
     *
     * @return array<string, array{0: class-string, 1: array<int, string>}>
     */
    private function modulKunciUnik(): array
    {
        return [
            'Rekap RKAS' => [RekapRkas::class, ['profil_sekolah_id', 'tahun']],
            'Dana BOSP Tahap' => [DanaBospTahap::class, ['profil_sekolah_id', 'tahun']],
            'Penerimaan Honor PTK' => [PenerimaanHonorPtk::class, ['profil_sekolah_id', 'tahun', 'triwulan', 'nuptk']],
            'Pajak BOSP Reguler' => [PajakBospReguler::class, ['profil_sekolah_id', 'tahun', 'bulan']],
            'Laporan Realisasi BOSP' => [LaporanRealisasiBosp::class, ['profil_sekolah_id', 'tahun', 'triwulan']],
            'Formulir BOS K7' => [FormulirBosK7::class, ['profil_sekolah_id', 'tahun', 'bulan']],
            'Verval Realisasi BOSP' => [VervalRealisasiBosp::class, ['profil_sekolah_id', 'tahun', 'triwulan']],
            'Surat TPG' => [SuratTpg::class, ['profil_sekolah_id', 'tahun', 'triwulan', 'jenis']],
            'Pendataan OPS (Identitas)' => [PendataanOps::class, ['profil_sekolah_id']],
            'Pendataan BOSP (Identitas)' => [PendataanBosp::class, ['profil_sekolah_id']],
        ];
    }

    /**
     * Kategori 1: data yatim - baris yang profil_sekolah_id-nya menunjuk
     * ke sekolah yang SUDAH TIDAK ADA. Dalam kondisi normal daftar ini
     * SELALU KOSONG (semua foreign key profil_sekolah_id sudah memakai
     * cascadeOnDelete sejak awal dibuat) - pengecekan ini murni jaring
     * pengaman tambahan.
     *
     * @return array<int, array{modul: string, keterangan: string, jumlah_baris: int, ids: array<int, int>, model: class-string}>
     */
    public function cekDataYatim(): array
    {
        $idSekolahAda = ProfilSekolah::pluck('id');
        $temuan = [];

        foreach ($this->modulTerikatSekolah() as $label => $class) {
            $barisYatim = $class::query()
                ->whereNotNull('profil_sekolah_id')
                ->whereNotIn('profil_sekolah_id', $idSekolahAda)
                ->get(['id', 'profil_sekolah_id']);

            if ($barisYatim->isEmpty()) {
                continue;
            }

            $temuan[] = [
                'modul' => $label,
                'keterangan' => 'Induk sekolah sudah tidak ada (profil_sekolah_id: '.$barisYatim->pluck('profil_sekolah_id')->unique()->implode(', ').')',
                'jumlah_baris' => $barisYatim->count(),
                'ids' => $barisYatim->pluck('id')->all(),
                'model' => $class,
            ];
        }

        return $temuan;
    }

    /**
     * Kategori 2: data "terkunci" di layar tapi masih tersimpan di
     * database - persis pola kasus Rekap RKAS 2026-09-26 (lihat
     * App\Livewire\PendataanBosp\RekapRkas\Index::danaBospTahapTerisiDari()).
     * Baris Rekap RKAS untuk sekolah+tahun yang Dana BOSP Tahap-nya
     * belum/tidak terisi (jumlah_siswa & jumlah_dana_bosp_per_tahun)
     * seharusnya sudah otomatis terhapus sejak perbaikan kode
     * 2026-09-26 - pengecekan ini memantau supaya tidak muncul lagi.
     *
     * @return array<int, array{modul: string, keterangan: string, jumlah_baris: int, ids: array<int, int>, model: class-string}>
     */
    public function cekDataTerkunciTersimpan(): array
    {
        $rekapList = RekapRkas::with('profilSekolah')->get();
        $danaTerisi = DanaBospTahap::query()
            ->whereNotNull('jumlah_siswa')
            ->whereNotNull('jumlah_dana_bosp_per_tahun')
            ->get(['profil_sekolah_id', 'tahun'])
            ->map(fn ($d) => $d->profil_sekolah_id.'|'.$d->tahun)
            ->flip();

        $bermasalah = $rekapList->filter(function ($rekap) use ($danaTerisi) {
            return ! isset($danaTerisi[$rekap->profil_sekolah_id.'|'.$rekap->tahun]);
        });

        if ($bermasalah->isEmpty()) {
            return [];
        }

        return [[
            'modul' => 'Rekap RKAS',
            'keterangan' => 'Dana BOSP Tahap belum/tidak terisi, tapi baris Rekap RKAS masih tersimpan: '.
                $bermasalah->map(fn ($r) => ($r->profilSekolah->nama_sekolah ?? 'sekolah #'.$r->profil_sekolah_id).' ('.$r->tahun.')')->implode('; '),
            'jumlah_baris' => $bermasalah->count(),
            'ids' => $bermasalah->pluck('id')->all(),
            'model' => RekapRkas::class,
        ]];
    }

    /**
     * Kategori 3: data duplikat - lebih dari 1 baris untuk kombinasi
     * kolom yang seharusnya unik per sekolah (+tahun/triwulan/dst, lihat
     * modulKunciUnik()). Dalam kondisi normal daftar ini SELALU KOSONG
     * (sudah dicegah unique constraint di database) - jaring pengaman
     * tambahan untuk memastikan kecocokan data.
     *
     * @return array<int, array{modul: string, keterangan: string, jumlah_baris: int, ids: array<int, int>, model: class-string}>
     */
    public function cekDataDuplikat(): array
    {
        $temuan = [];

        foreach ($this->modulKunciUnik() as $label => [$class, $kolomKunci]) {
            $grup = $class::query()
                ->select($kolomKunci)
                ->selectRaw('COUNT(*) as jumlah_duplikat')
                ->whereNotNull('profil_sekolah_id')
                ->groupBy($kolomKunci)
                ->havingRaw('COUNT(*) > 1')
                ->get();

            if ($grup->isEmpty()) {
                continue;
            }

            $ids = [];
            $keterangan = [];

            foreach ($grup as $satuGrup) {
                $query = $class::query();
                foreach ($kolomKunci as $kolom) {
                    $query->where($kolom, $satuGrup->{$kolom});
                }

                // Simpan baris TERTUA (id terkecil), sisanya masuk daftar
                // untuk dibersihkan - supaya data asli/pertama tetap
                // dipertahankan kalau nanti tombol Bersihkan Otomatis
                // dipakai.
                $barisGrup = $query->orderBy('id')->pluck('id');
                $ids = array_merge($ids, $barisGrup->slice(1)->all());

                $kunciTeks = collect($kolomKunci)
                    ->map(fn ($k) => $k.'='.$satuGrup->{$k})
                    ->implode(', ');
                $keterangan[] = "{$kunciTeks} ({$satuGrup->jumlah_duplikat} baris)";
            }

            if (empty($ids)) {
                continue;
            }

            $temuan[] = [
                'modul' => $label,
                'keterangan' => 'Duplikat pada: '.implode('; ', $keterangan).' - baris pertama (paling lama) dipertahankan.',
                'jumlah_baris' => count($ids),
                'ids' => $ids,
                'model' => $class,
            ];
        }

        return $temuan;
    }

    /**
     * Tab BARU "Cek Kecocokan Data" (permintaan user 2026-09-27, DIROMBAK
     * 2026-09-27 sore jadi per-sekolah atas permintaan user) - TIDAK sama
     * dengan tab Integritas Data di atas (yang mencari baris bermasalah
     * spesifik). Tab ini membandingkan JUMLAH baris per jenis data, DAN
     * dikelompokkan per sekolah (Superadmin klik simbol + untuk membuka
     * rincian jenis data sekolah tersebut):
     * - "Jumlah Data di Aplikasi": Model::where('profil_sekolah_id', ...)
     *   ->count() - lewat Eloquent, mengikuti aturan/scope yang dipakai
     *   aplikasi (kalau ada).
     * - "Jumlah Data di Database": DB::table(...)->where('profil_sekolah_id', ...)
     *   ->count() - COUNT(*) mentah langsung ke tabel, TANPA lewat
     *   Eloquent sama sekali.
     *
     * Cakupan tetap sama seperti sebelumnya (Profil Sekolah + Pengguna +
     * semua menu Pendataan OPS/BOSP dari modulTerikatSekolah()) - hanya
     * cara menampilkannya yang berubah dari 1 tabel datar jadi
     * dikelompokkan per sekolah.
     *
     * "Profil Sekolah" per baris sekolah SELALU jumlahnya 1/1 (baris
     * sekolah itu sendiri) - tetap ditampilkan supaya daftar jenis data
     * konsisten dengan tab Integritas Data. "Pengguna" dihitung dari akun
     * yang profil_sekolah_id-nya menunjuk ke sekolah tsb (Admin OPS/Admin
     * BOSP) - akun Superadmin (profil_sekolah_id null) tidak masuk ke
     * sekolah manapun, itu wajar bukan bug.
     *
     * Pencarian (searchKecocokan) menyaring berdasarkan NAMA SEKOLAH.
     *
     * Kedua angka per jenis data SEHARUSNYA selalu sama persis - saat ini
     * tidak ada model di aplikasi ini yang memakai soft delete atau global
     * scope tersembunyi. Kalau suatu saat angkanya berbeda, itu tanda ada
     * scope/filter tersembunyi yang membuat salah satu angka tidak lagi
     * mencerminkan kondisi database yang sebenarnya - sinyal untuk
     * diperiksa lebih lanjut, sama seperti filosofi tab Integritas Data.
     *
     * @return array<int, array{sekolah_id: int, nama_sekolah: string, semua_cocok: bool, total_jumlah_aplikasi: int, total_jumlah_database: int, rincian: array<int, array{jenis_data: string, jumlah_aplikasi: int, jumlah_database: int, cocok: bool}>}>
     */
    public function cekKecocokanData(): array
    {
        $sekolahList = ProfilSekolah::urutStandar()->get(['id', 'nama_sekolah']);

        if ($this->searchKecocokan) {
            $sekolahList = $sekolahList
                ->filter(fn ($s) => str_contains(strtolower($s->nama_sekolah), strtolower($this->searchKecocokan)))
                ->values();
        }

        $modul = $this->modulTerikatSekolah();

        $hasil = [];

        foreach ($sekolahList as $sekolah) {
            $rincian = [
                [
                    'jenis_data' => 'Profil Sekolah',
                    'jumlah_aplikasi' => ProfilSekolah::where('id', $sekolah->id)->count(),
                    'jumlah_database' => DB::table('profil_sekolah')->where('id', $sekolah->id)->count(),
                ],
                [
                    'jenis_data' => 'Pengguna',
                    'jumlah_aplikasi' => User::where('profil_sekolah_id', $sekolah->id)->count(),
                    'jumlah_database' => DB::table('users')->where('profil_sekolah_id', $sekolah->id)->count(),
                ],
            ];

            foreach ($modul as $label => $class) {
                $rincian[] = [
                    'jenis_data' => $label,
                    'jumlah_aplikasi' => $class::where('profil_sekolah_id', $sekolah->id)->count(),
                    'jumlah_database' => DB::table((new $class())->getTable())->where('profil_sekolah_id', $sekolah->id)->count(),
                ];
            }

            foreach ($rincian as $i => $satu) {
                $rincian[$i]['cocok'] = $satu['jumlah_aplikasi'] === $satu['jumlah_database'];
            }

            $hasil[] = [
                'sekolah_id' => $sekolah->id,
                'nama_sekolah' => $sekolah->nama_sekolah,
                'semua_cocok' => collect($rincian)->every(fn ($r) => $r['cocok']),
                'total_jumlah_aplikasi' => (int) collect($rincian)->sum('jumlah_aplikasi'),
                'total_jumlah_database' => (int) collect($rincian)->sum('jumlah_database'),
                'rincian' => $rincian,
            ];
        }

        return $hasil;
    }

    public function pindahTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage('loginGagalPage');
        $this->resetPage('aksesDataPage');
    }

    public function updatedSearchLoginGagal(): void
    {
        $this->resetPage('loginGagalPage');
    }

    public function updatedSearchAksesData(): void
    {
        $this->resetPage('aksesDataPage');
    }

    public function updatedFilterJenisAksi(): void
    {
        $this->resetPage('aksesDataPage');
    }

    public function konfirmasiBersihkan(string $kategori): void
    {
        $this->confirmingBersihkan = $kategori;
        $this->dispatch('open-modal', 'cek-database-bersihkan');
    }

    public function batalBersihkan(): void
    {
        $this->confirmingBersihkan = null;
        $this->dispatch('close-modal', 'cek-database-bersihkan');
    }

    /**
     * Menjalankan pembersihan untuk 1 kategori temuan ('yatim', 'terkunci',
     * atau 'duplikat'). Temuan DIQUERY ULANG (fresh, bukan memakai data
     * yang sudah ditampilkan di layar) tepat sebelum menghapus, supaya
     * tidak ada baris yang terhapus kalau kondisinya sudah berubah sejak
     * halaman ini dibuka. Setiap kategori dihapus dalam 1 transaksi
     * database (semua berhasil atau semua dibatalkan).
     */
    public function bersihkanOtomatis(): void
    {
        $kategori = $this->confirmingBersihkan;

        $temuanTerbaru = match ($kategori) {
            'yatim' => $this->cekDataYatim(),
            'terkunci' => $this->cekDataTerkunciTersimpan(),
            'duplikat' => $this->cekDataDuplikat(),
            default => [],
        };

        $totalDihapus = 0;

        DB::transaction(function () use ($temuanTerbaru, &$totalDihapus) {
            foreach ($temuanTerbaru as $satu) {
                $class = $satu['model'];
                $totalDihapus += $class::whereIn('id', $satu['ids'])->delete();
            }
        });

        $this->confirmingBersihkan = null;
        $this->dispatch('close-modal', 'cek-database-bersihkan');
        session()->flash('status', "Berhasil membersihkan {$totalDihapus} baris data bermasalah.");
    }

    public function render()
    {
        $loginGagal = null;
        $aksesData = null;

        if ($this->tab === 'login_gagal') {
            $loginGagal = FailedLoginAttempt::query()
                ->when($this->searchLoginGagal, fn ($q) => $q->where(function ($q) {
                    $q->where('username_dicoba', 'like', "%{$this->searchLoginGagal}%")
                        ->orWhere('ip_address', 'like', "%{$this->searchLoginGagal}%");
                }))
                ->orderByDesc('created_at')
                ->paginate(15, ['*'], 'loginGagalPage');
        } elseif ($this->tab === 'akses_data') {
            $aksesData = AksesDataLog::query()
                ->when($this->filterJenisAksi, fn ($q) => $q->where('jenis_aksi', $this->filterJenisAksi))
                ->when($this->searchAksesData, fn ($q) => $q->where(function ($q) {
                    $q->where('username_snapshot', 'like', "%{$this->searchAksesData}%")
                        ->orWhere('nama_menu', 'like', "%{$this->searchAksesData}%");
                }))
                ->orderByDesc('created_at')
                ->paginate(15, ['*'], 'aksesDataPage');
        }

        return view('livewire.cek-database-aplikasi.index', [
            'temuanYatim' => $this->tab === 'integritas' ? $this->cekDataYatim() : [],
            'temuanTerkunci' => $this->tab === 'integritas' ? $this->cekDataTerkunciTersimpan() : [],
            'temuanDuplikat' => $this->tab === 'integritas' ? $this->cekDataDuplikat() : [],
            'loginGagal' => $loginGagal,
            'aksesData' => $aksesData,
            'kecocokanData' => $this->tab === 'kecocokan' ? $this->cekKecocokanData() : [],
        ]);
    }
}
