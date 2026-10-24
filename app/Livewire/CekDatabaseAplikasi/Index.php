<?php

namespace App\Livewire\CekDatabaseAplikasi;

use App\Livewire\Concerns\HasZoomTampilan;
use App\Models\AksesDataLog;
use App\Models\BelanjaHonorKegiatan;
use App\Models\BiayaPendaftaranLomba;
use App\Models\DanaBospTahap;
use App\Models\DataPtk;
use App\Models\FailedLoginAttempt;
use App\Models\FormulirBosK7;
use App\Models\Lampiran2a;
use App\Models\Lampiran2b;
use App\Models\Lampiran2c;
use App\Models\LanggananDayaJasa;
use App\Models\LanggananVpsSetting;
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
use App\Services\SyncClient;
use App\Support\RegistriModulSekolah;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Menu "Cek Database dan Aplikasi" - BARU, permintaan user 2026-09-27.
 *
 * 5 tab:
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
 * 4. Cek Kecocokan Data: lihat cekKecocokanData() & cekUlangSuperadmin().
 * 5. Storage & Langganan VPS (BARU, permintaan user 2026-10-24): (a)
 *    kapasitas disk VPS keseluruhan terpakai/tersisa (lihat
 *    infoStorageVps() - HANYA akurat saat aplikasi berjalan ONLINE di
 *    VPS Linux, di LOKAL/Windows akan menunjukkan drive komputer Lokal
 *    sendiri, bukan VPS), (b) ukuran database PostgreSQL aplikasi ini
 *    (lihat ukuranDatabaseBytes(), cepat, dihitung otomatis), (c) ukuran
 *    total folder aplikasi (lihat hitungUkuranAplikasi(), BISA LAMBAT,
 *    SENGAJA hanya dihitung lewat tombol manual, bukan otomatis), dan
 *    (d) tanggal jatuh tempo langganan VPS idcloudhost - DIISI MANUAL
 *    oleh Superadmin (dikonfirmasi lewat AskUserQuestion, BUKAN ditarik
 *    otomatis lewat API idcloudhost - tidak ada kredensial yang
 *    disimpan di aplikasi ini).
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

    public function mount(): void
    {
        // Permintaan user 2026-10-24 (tab baru "Storage & Langganan VPS")
        // - muat tanggal jatuh tempo & catatan yang SUDAH tersimpan
        // (kalau ada) ke properti form, supaya form langsung terisi
        // begitu Superadmin membuka halaman ini (tidak perlu pindah tab
        // dulu - field ini ringan, 1 baris singleton, aman dimuat di
        // mount()).
        $langganan = LanggananVpsSetting::current();
        $this->tanggalJatuhTempo = $langganan->tanggal_jatuh_tempo?->format('Y-m-d');
        $this->catatanLangganan = $langganan->catatan ?? '';
    }

    // --- State tab Integritas Data ---
    public ?string $confirmingBersihkan = null;

    // --- State tab Percobaan Login Gagal ---
    public string $searchLoginGagal = '';

    public int $perPageLoginGagal = 15;

    /**
     * State tombol Hapus tab Percobaan Login Gagal (permintaan user
     * 2026-10-03) - $dipilihLoginGagal diisi ulang via array_intersect()
     * di render() setiap kali (mengikuti pola yang sama dipakai
     * Lampiran2a/Index.php) supaya ID dari halaman/filter sebelumnya
     * tidak "nyangkut" kalau Superadmin pindah halaman/ubah pencarian.
     *
     * @var array<int, int>
     */
    public array $dipilihLoginGagal = [];

    public bool $confirmingHapusTerpilihLoginGagal = false;

    public bool $confirmingHapusSemuaLoginGagal = false;

    // --- State tab Log Akses Data ---
    public string $searchAksesData = '';

    public string $filterJenisAksi = '';

    public int $perPageAksesData = 15;

    /**
     * State tombol Hapus tab Log Akses Data (permintaan user 2026-10-03)
     * - lihat catatan $dipilihLoginGagal di atas, pola yang sama.
     *
     * @var array<int, int>
     */
    public array $dipilihAksesData = [];

    public bool $confirmingHapusTerpilihAksesData = false;

    public bool $confirmingHapusSemuaAksesData = false;

    // --- State tab Cek Kecocokan Data (permintaan user 2026-09-27) ---
    public string $searchKecocokan = '';

    /**
     * BARU (permintaan user 2026-10-14, klarifikasi ulang): tombol "Cek
     * Ulang" eksplisit untuk tabel kecocokan data PER SEKOLAH (Aplikasi vs
     * Database MENTAH, 1 instalasi yang sama - BUKAN Lokal vs Online).
     * Catatan: cekKecocokanData() di bawah SUDAH otomatis dihitung ulang
     * dari database setiap kali render() dipanggil (lihat render()) - jadi
     * datanya sebenarnya SELALU fresh. Properti ini HANYA menyimpan waktu
     * "terakhir diperiksa" supaya user merasa yakin datanya baru, diisi
     * otomatis saat pindah ke tab ini DAN saat tombol "Cek Ulang" diklik.
     */
    public ?string $kecocokanDataDiperiksaPada = null;

    /**
     * Hasil perbandingan akun Superadmin Lokal vs Online (permintaan
     * user 2026-10-14, lanjutan - AskUserQuestion: "Hanya data akun
     * Superadmin sendiri", dipicu HANYA manual lewat tombol "Cek Ulang",
     * BUKAN otomatis setiap tab dibuka). Null = belum pernah dicek sejak
     * halaman ini dibuka (state Livewire murni, SENGAJA tidak disimpan
     * ke database - hasil basi/kadaluarsa tidak berguna, selalu harus
     * "Cek Ulang" lagi untuk info terbaru).
     *
     * @var array<int, array{email: string, lokal: array{username: string, email: string, is_approved: bool}|null, online: array{username: string, email: string, is_approved: bool}|null, cocok: bool}>|null
     */
    public ?array $kecocokanSuperadmin = null;

    public ?string $kecocokanSuperadminError = null;

    public ?string $kecocokanSuperadminDiperiksaPada = null;

    // --- State tab Storage & Langganan VPS (permintaan user 2026-10-24) ---

    public ?string $tanggalJatuhTempo = null;

    public string $catatanLangganan = '';

    /**
     * Ukuran total folder aplikasi (base_path(), termasuk vendor/,
     * storage/, .git/ kalau ada) - null berarti BELUM PERNAH dihitung
     * sejak halaman ini dibuka. SENGAJA TIDAK dihitung otomatis (lihat
     * hitungUkuranAplikasi()), hanya lewat tombol manual "Hitung Ukuran
     * Aplikasi" karena menelusuri seluruh folder bisa lambat (ribuan
     * file di vendor/).
     */
    public ?int $ukuranFolderAplikasiBytes = null;

    public ?string $ukuranAplikasiDihitungPada = null;

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
        return RegistriModulSekolah::semua();
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
        return RegistriModulSekolah::kunciUnik();
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

    /**
     * BARU: dipicu tombol "Cek Ulang" (dan otomatis saat pindah ke tab ini
     * lewat pindahTab()) - hanya memperbarui cap waktu "terakhir diperiksa",
     * KARENA cekKecocokanData() di atas sudah otomatis fresh tiap render().
     */
    public function cekUlangKecocokanData(): void
    {
        $this->kecocokanDataDiperiksaPada = now()->keWaktuLokal()->translatedFormat('d M Y H:i');
    }

    /**
     * Tombol "Cek Ulang" - permintaan user 2026-10-14 ("tambahkan untuk
     * kecocokan data pada login superadmin untuk cek data di lokal sama
     * yang di online cocok atau tidak"), dikonfirmasi lewat
     * AskUserQuestion: HANYA membandingkan akun Superadmin (username,
     * email, status disetujui), BUKAN seluruh data modul sekolah -
     * karena akun Pengguna TIDAK ikut lingkup Sinkronisasi poin 3 (lihat
     * App\Http\Controllers\Api\SuperadminCheckController).
     *
     * HANYA berarti/aktif di sisi LOKAL (sama seperti seluruh tombol aksi
     * menu Sinkronisasi - Online tidak bisa memulai koneksi balik ke
     * Lokal), dan HANYA dijalankan saat tombol diklik manual (dikonfirmasi
     * AskUserQuestion - supaya tab ini tetap cepat dibuka, tidak otomatis
     * memanggil API ke Online setiap kali tab "Cek Kecocokan Data" dibuka).
     * Memakai URL + Token API yang SAMA dengan menu Sinkronisasi (menu
     * itu yang mengisi App\Models\SyncSetting) - tidak ada pengaturan
     * token terpisah di sini.
     */
    public function cekUlangSuperadmin(SyncClient $client): void
    {
        abort_unless(auth()->user()->isSuperadmin(), 403);

        if (config('sync.peran') !== 'lokal') {
            return;
        }

        $this->kecocokanSuperadminError = null;

        try {
            $daftarOnline = collect($client->cekSuperadmin())->keyBy('email');

            $daftarLokal = User::query()
                ->where('level_akses', User::LEVEL_SUPERADMIN)
                ->orderBy('email')
                ->get(['username', 'email', 'is_approved'])
                ->map(fn (User $u) => ['username' => $u->username, 'email' => $u->email, 'is_approved' => (bool) $u->is_approved])
                ->keyBy('email');

            $semuaEmail = $daftarLokal->keys()->merge($daftarOnline->keys())->unique()->sort()->values();

            $this->kecocokanSuperadmin = $semuaEmail->map(function ($email) use ($daftarLokal, $daftarOnline) {
                $lokal = $daftarLokal->get($email);
                $online = $daftarOnline->get($email);

                return [
                    'email' => $email,
                    'lokal' => $lokal,
                    'online' => $online,
                    'cocok' => $lokal !== null && $online !== null
                        && $lokal['username'] === $online['username']
                        && $lokal['is_approved'] === $online['is_approved'],
                ];
            })->values()->all();

            $this->kecocokanSuperadminDiperiksaPada = now()->keWaktuLokal()->translatedFormat('d M Y H:i');
        } catch (\Throwable $e) {
            report($e);
            $this->kecocokanSuperadmin = null;
            $this->kecocokanSuperadminError = 'Gagal memeriksa kecocokan akun Superadmin: '.$e->getMessage();
        }
    }

    /**
     * Simpan tanggal jatuh tempo + catatan langganan VPS (tab "Storage &
     * Langganan VPS", permintaan user 2026-10-24). Diisi MANUAL oleh
     * Superadmin - tidak ada pengambilan data otomatis dari idcloudhost.
     */
    public function simpanLangganan(): void
    {
        $validated = $this->validate([
            'tanggalJatuhTempo' => ['nullable', 'date'],
            'catatanLangganan' => ['nullable', 'string', 'max:1000'],
        ]);

        LanggananVpsSetting::current()->update([
            'tanggal_jatuh_tempo' => $validated['tanggalJatuhTempo'],
            'catatan' => $validated['catatanLangganan'],
        ]);

        session()->flash('status', 'Tanggal jatuh tempo langganan VPS berhasil disimpan.');
    }

    /**
     * Kapasitas disk VPS keseluruhan (terpakai/tersisa) - dibaca
     * langsung dari OS tempat aplikasi ini berjalan lewat fungsi native
     * PHP disk_total_space()/disk_free_space() (CEPAT, aman dihitung
     * otomatis setiap tab "storage" dibuka, lihat render()).
     *
     * PENTING: HANYA akurat sebagai "kapasitas VPS" saat aplikasi
     * berjalan ONLINE di server Linux. Saat dijalankan LOKAL di
     * komputer Windows, fungsi ini akan membaca kapasitas drive
     * komputer Lokal itu sendiri (BUKAN VPS) - ditandai lewat
     * 'adalahWindows' supaya blade bisa menampilkan peringatan yang
     * jujur, bukan angka yang menyesatkan.
     *
     * @return array{tersedia: bool, adalahWindows: bool, totalBytes?: int, terpakaiBytes?: int, bebasBytes?: int, persenTerpakai?: float}
     */
    public function infoStorageVps(): array
    {
        $path = base_path();
        $total = @disk_total_space($path);
        $bebas = @disk_free_space($path);

        if ($total === false || $bebas === false) {
            return [
                'tersedia' => false,
                'adalahWindows' => PHP_OS_FAMILY === 'Windows',
            ];
        }

        $terpakai = $total - $bebas;

        return [
            'tersedia' => true,
            'adalahWindows' => PHP_OS_FAMILY === 'Windows',
            'totalBytes' => (int) $total,
            'terpakaiBytes' => (int) $terpakai,
            'bebasBytes' => (int) $bebas,
            'persenTerpakai' => $total > 0 ? round(($terpakai / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * Ukuran database PostgreSQL yang dipakai aplikasi ini - 1 query
     * bawaan PostgreSQL (pg_database_size), CEPAT, aman dihitung
     * otomatis setiap tab "storage" dibuka (lihat render()), TIDAK
     * seperti ukuran folder aplikasi di bawah yang harus tombol manual.
     */
    public function ukuranDatabaseBytes(): ?int
    {
        try {
            $baris = DB::selectOne('select pg_database_size(current_database()) as ukuran');

            return $baris ? (int) $baris->ukuran : null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Ukuran total folder aplikasi (base_path(), termasuk vendor/,
     * storage/, .git/ kalau ada) - menelusuri SELURUH file lewat PHP
     * (RecursiveDirectoryIterator), BISA LAMBAT kalau folder besar
     * (ribuan file di vendor/). SENGAJA TIDAK dihitung otomatis setiap
     * tab dibuka/render() - hanya dipanggil lewat tombol manual "Hitung
     * Ukuran Aplikasi" di blade, pola sama seperti tombol "Cek Ulang"
     * manual lain di halaman ini (mis. cekUlangKecocokanData()).
     */
    public function hitungUkuranAplikasi(): void
    {
        $total = 0;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(base_path(), \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $total += $file->getSize();
                }
            }

            $this->ukuranFolderAplikasiBytes = $total;
        } catch (\Throwable $e) {
            report($e);
            $this->ukuranFolderAplikasiBytes = null;
        }

        $this->ukuranAplikasiDihitungPada = now()->keWaktuLokal()->translatedFormat('d M Y H:i');
    }

    /**
     * Status visual (sisa hari + tingkat kedesakan) tanggal jatuh tempo
     * langganan VPS - dihitung dari properti form $tanggalJatuhTempo yang
     * SEDANG ditampilkan (bukan query ulang ke database), supaya kalau
     * Superadmin mengetik tanggal baru di form, pratinjau status ikut
     * berubah SEBELUM tombol "Simpan" ditekan.
     *
     * @return array{sisaHari: int, level: string}|null null kalau tanggal belum diisi/tidak valid
     */
    public function statusJatuhTempo(): ?array
    {
        if (! $this->tanggalJatuhTempo) {
            return null;
        }

        try {
            $tanggal = \Carbon\Carbon::parse($this->tanggalJatuhTempo, 'Asia/Jakarta')->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }

        // Dihitung manual (selisih absolut + tanda eksplisit lewat
        // perbandingan), BUKAN memakai diffInDays($tanggal, false) secara
        // langsung - konvensi tanda hasil "signed" Carbon untuk diffInDays
        // pernah berubah antar versi & gampang salah arah (lewat tempo
        // malah ditampilkan sebagai "akan datang" atau sebaliknya), jadi
        // sengaja dihitung eksplisit supaya pasti benar.
        $hariIni = now('Asia/Jakarta')->startOfDay();
        $sisaHari = (int) $hariIni->diffInDays($tanggal, true);
        if ($tanggal->lessThan($hariIni)) {
            $sisaHari = -$sisaHari;
        }

        $level = match (true) {
            $sisaHari < 0 => 'lewat',
            $sisaHari <= 3 => 'mendesak',
            $sisaHari <= 7 => 'perhatian',
            default => 'aman',
        };

        return [
            'sisaHari' => $sisaHari,
            'level' => $level,
        ];
    }

    /**
     * Format jumlah byte jadi satuan yang mudah dibaca (KB/MB/GB/TB) -
     * dipakai bareng oleh tampilan kapasitas VPS, ukuran database, &
     * ukuran folder aplikasi di blade (tab "Storage & Langganan VPS").
     */
    public function formatBytes(?int $bytes): string
    {
        if ($bytes === null) {
            return '-';
        }

        $unit = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $i = min($i, count($unit) - 1);

        return number_format($bytes / (1024 ** $i), 2).' '.$unit[$i];
    }

    public function pindahTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage('loginGagalPage');
        $this->resetPage('aksesDataPage');
        $this->dipilihLoginGagal = [];
        $this->dipilihAksesData = [];

        if ($tab === 'kecocokan') {
            $this->cekUlangKecocokanData();
        }
    }

    /**
     * Query dasar tab Percobaan Login Gagal (sudah termasuk filter
     * pencarian aktif, TANPA pagination & TANPA urutan) - dipakai bareng
     * oleh render() (paginate), toggleSemuaLoginGagal(), dan
     * hapusSemuaLoginGagal() (permintaan user 2026-10-03), supaya
     * logika filter HANYA ditulis 1 kali di 1 tempat.
     */
    private function queryLoginGagal()
    {
        return FailedLoginAttempt::query()
            ->when($this->searchLoginGagal, fn ($q) => $q->where(function ($q) {
                $q->where('username_dicoba', 'like', "%{$this->searchLoginGagal}%")
                    ->orWhere('ip_address', 'like', "%{$this->searchLoginGagal}%");
            }));
    }

    /**
     * Query dasar tab Log Akses Data (sudah termasuk filter jenis aksi &
     * pencarian aktif, TANPA pagination & TANPA urutan) - lihat
     * penjelasan queryLoginGagal() di atas, pola yang sama.
     */
    private function queryAksesData()
    {
        return AksesDataLog::query()
            ->when($this->filterJenisAksi, fn ($q) => $q->where('jenis_aksi', $this->filterJenisAksi))
            ->when($this->searchAksesData, fn ($q) => $q->where(function ($q) {
                $q->where('username_snapshot', 'like', "%{$this->searchAksesData}%")
                    ->orWhere('nama_menu', 'like', "%{$this->searchAksesData}%");
            }));
    }

    /**
     * Centang/batal-centang SEMUA baris di HALAMAN INI SAJA (bukan semua
     * baris di seluruh tabel) - pola yang sama dengan
     * Lampiran2a::toggleSemua(). Permintaan user 2026-10-03.
     */
    public function toggleSemuaLoginGagal(): void
    {
        $idHalamanIni = $this->queryLoginGagal()->orderByDesc('created_at')
            ->paginate($this->perPageLoginGagal, ['id'], 'loginGagalPage')->pluck('id')->all();

        if (count($idHalamanIni) > 0 && count(array_diff($idHalamanIni, $this->dipilihLoginGagal)) === 0) {
            $this->dipilihLoginGagal = array_values(array_diff($this->dipilihLoginGagal, $idHalamanIni));
        } else {
            $this->dipilihLoginGagal = array_values(array_unique(array_merge($this->dipilihLoginGagal, $idHalamanIni)));
        }
    }

    public function konfirmasiHapusTerpilihLoginGagal(): void
    {
        if (empty($this->dipilihLoginGagal)) {
            return;
        }

        $this->confirmingHapusTerpilihLoginGagal = true;
        $this->dispatch('open-modal', 'cek-database-login-gagal-hapus-terpilih');
    }

    public function batalHapusTerpilihLoginGagal(): void
    {
        $this->confirmingHapusTerpilihLoginGagal = false;
        $this->dispatch('close-modal', 'cek-database-login-gagal-hapus-terpilih');
    }

    public function hapusTerpilihLoginGagal(): void
    {
        $jumlah = FailedLoginAttempt::whereIn('id', $this->dipilihLoginGagal)->count();
        FailedLoginAttempt::whereIn('id', $this->dipilihLoginGagal)->delete();

        $this->dipilihLoginGagal = [];
        $this->confirmingHapusTerpilihLoginGagal = false;
        $this->dispatch('close-modal', 'cek-database-login-gagal-hapus-terpilih');
        $this->resetPage('loginGagalPage');
        session()->flash('status', "Berhasil menghapus {$jumlah} catatan percobaan login gagal yang dipilih.");
    }

    public function konfirmasiHapusSemuaLoginGagal(): void
    {
        $this->confirmingHapusSemuaLoginGagal = true;
        $this->dispatch('open-modal', 'cek-database-login-gagal-hapus-semua');
    }

    public function batalHapusSemuaLoginGagal(): void
    {
        $this->confirmingHapusSemuaLoginGagal = false;
        $this->dispatch('close-modal', 'cek-database-login-gagal-hapus-semua');
    }

    /**
     * Hapus SEMUA baris Percobaan Login Gagal yang cocok dengan
     * pencarian AKTIF saat ini (bukan hanya yang tampil di halaman ini -
     * kalau tidak ada pencarian aktif, berarti benar-benar semua baris).
     * Permintaan user 2026-10-03, dikonfirmasi lewat AskUserQuestion.
     *
     * reorder() dipasang sebelum delete() - DELETE tidak mendukung
     * ORDER BY di SQL standar, jadi urutan dari queryLoginGagal() (kalau
     * ada) harus dibuang dulu sebelum menghapus.
     */
    public function hapusSemuaLoginGagal(): void
    {
        $jumlah = $this->queryLoginGagal()->count();
        $this->queryLoginGagal()->reorder()->delete();

        $this->dipilihLoginGagal = [];
        $this->confirmingHapusSemuaLoginGagal = false;
        $this->dispatch('close-modal', 'cek-database-login-gagal-hapus-semua');
        $this->resetPage('loginGagalPage');
        session()->flash('status', "Berhasil menghapus {$jumlah} catatan percobaan login gagal.");
    }

    public function toggleSemuaAksesData(): void
    {
        $idHalamanIni = $this->queryAksesData()->orderByDesc('created_at')
            ->paginate($this->perPageAksesData, ['id'], 'aksesDataPage')->pluck('id')->all();

        if (count($idHalamanIni) > 0 && count(array_diff($idHalamanIni, $this->dipilihAksesData)) === 0) {
            $this->dipilihAksesData = array_values(array_diff($this->dipilihAksesData, $idHalamanIni));
        } else {
            $this->dipilihAksesData = array_values(array_unique(array_merge($this->dipilihAksesData, $idHalamanIni)));
        }
    }

    public function konfirmasiHapusTerpilihAksesData(): void
    {
        if (empty($this->dipilihAksesData)) {
            return;
        }

        $this->confirmingHapusTerpilihAksesData = true;
        $this->dispatch('open-modal', 'cek-database-akses-data-hapus-terpilih');
    }

    public function batalHapusTerpilihAksesData(): void
    {
        $this->confirmingHapusTerpilihAksesData = false;
        $this->dispatch('close-modal', 'cek-database-akses-data-hapus-terpilih');
    }

    public function hapusTerpilihAksesData(): void
    {
        $jumlah = AksesDataLog::whereIn('id', $this->dipilihAksesData)->count();
        AksesDataLog::whereIn('id', $this->dipilihAksesData)->delete();

        $this->dipilihAksesData = [];
        $this->confirmingHapusTerpilihAksesData = false;
        $this->dispatch('close-modal', 'cek-database-akses-data-hapus-terpilih');
        $this->resetPage('aksesDataPage');
        session()->flash('status', "Berhasil menghapus {$jumlah} log akses data yang dipilih.");
    }

    public function konfirmasiHapusSemuaAksesData(): void
    {
        $this->confirmingHapusSemuaAksesData = true;
        $this->dispatch('open-modal', 'cek-database-akses-data-hapus-semua');
    }

    public function batalHapusSemuaAksesData(): void
    {
        $this->confirmingHapusSemuaAksesData = false;
        $this->dispatch('close-modal', 'cek-database-akses-data-hapus-semua');
    }

    /**
     * Hapus SEMUA baris Log Akses Data yang cocok dengan filter/pencarian
     * AKTIF saat ini - lihat penjelasan hapusSemuaLoginGagal() di atas,
     * pola yang sama. Permintaan user 2026-10-03.
     */
    public function hapusSemuaAksesData(): void
    {
        $jumlah = $this->queryAksesData()->count();
        $this->queryAksesData()->reorder()->delete();

        $this->dipilihAksesData = [];
        $this->confirmingHapusSemuaAksesData = false;
        $this->dispatch('close-modal', 'cek-database-akses-data-hapus-semua');
        $this->resetPage('aksesDataPage');
        session()->flash('status', "Berhasil menghapus {$jumlah} log akses data.");
    }

    public function updatedSearchLoginGagal(): void
    {
        $this->resetPage('loginGagalPage');
    }

    public function updatedPerPageLoginGagal(): void
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

    public function updatedPerPageAksesData(): void
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
        $semuaTerpilihLoginGagal = false;
        $semuaTerpilihAksesData = false;

        if ($this->tab === 'login_gagal') {
            $loginGagal = $this->queryLoginGagal()
                ->orderByDesc('created_at')
                ->paginate($this->perPageLoginGagal, ['*'], 'loginGagalPage');

            // Permintaan user 2026-10-03: hapus terpilih (checkbox) - ID
            // yang sudah tidak ada di halaman ini (pindah halaman/ubah
            // pencarian) otomatis disaring di sini, mengikuti pola yang
            // sama dengan Lampiran2a::render().
            $idHalamanIni = $loginGagal->pluck('id')->all();
            $this->dipilihLoginGagal = array_values(array_intersect($this->dipilihLoginGagal, $idHalamanIni));
            $semuaTerpilihLoginGagal = count($idHalamanIni) > 0 && count(array_diff($idHalamanIni, $this->dipilihLoginGagal)) === 0;
        } elseif ($this->tab === 'akses_data') {
            $aksesData = $this->queryAksesData()
                ->orderByDesc('created_at')
                ->paginate($this->perPageAksesData, ['*'], 'aksesDataPage');

            $idHalamanIni = $aksesData->pluck('id')->all();
            $this->dipilihAksesData = array_values(array_intersect($this->dipilihAksesData, $idHalamanIni));
            $semuaTerpilihAksesData = count($idHalamanIni) > 0 && count(array_diff($idHalamanIni, $this->dipilihAksesData)) === 0;
        }

        return view('livewire.cek-database-aplikasi.index', [
            'temuanYatim' => $this->tab === 'integritas' ? $this->cekDataYatim() : [],
            'temuanTerkunci' => $this->tab === 'integritas' ? $this->cekDataTerkunciTersimpan() : [],
            'temuanDuplikat' => $this->tab === 'integritas' ? $this->cekDataDuplikat() : [],
            'loginGagal' => $loginGagal,
            'aksesData' => $aksesData,
            'semuaTerpilihLoginGagal' => $semuaTerpilihLoginGagal,
            'semuaTerpilihAksesData' => $semuaTerpilihAksesData,
            'kecocokanData' => $this->tab === 'kecocokan' ? $this->cekKecocokanData() : [],
            'infoStorageVps' => $this->tab === 'storage' ? $this->infoStorageVps() : null,
            'ukuranDatabaseBytes' => $this->tab === 'storage' ? $this->ukuranDatabaseBytes() : null,
            'statusJatuhTempo' => $this->tab === 'storage' ? $this->statusJatuhTempo() : null,
        ]);
    }
}
