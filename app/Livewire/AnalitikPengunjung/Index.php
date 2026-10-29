<?php

namespace App\Livewire\AnalitikPengunjung;

use App\Models\AksesDataLog;
use App\Models\LoginHistory;
use App\Models\User;
use App\Support\InfoPerangkat;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Menu "Analitik Pengunjung" - BARU, permintaan user 2026-10-29: "buatkan
 * analytics visitor untuk mengetahui siapa saja pengunjung aplikasi dan
 * berasal darimana asal nya dan apa saja yang di lihat dalam aplikasi
 * tersebut".
 *
 * PENTING - ini BUKAN tabel/pencatatan data baru. Data mentahnya SUDAH
 * ada & sudah tercatat otomatis sejak lama lewat 2 tabel yang sudah ada:
 * - login_histories (lihat App\Models\LoginHistory) - siapa login, kapan,
 *   dari sekolah mana, IP Address, & titik koordinat (kalau izin lokasi
 *   browser diberikan saat login).
 * - akses_data_logs (lihat App\Models\AksesDataLog) - menu apa saja yang
 *   DIBUKA atau DIUNDUH, oleh siapa, lengkap dengan User-Agent (info
 *   perangkat/browser) & titik koordinat (dipakai ulang dari sesi login
 *   yang sedang aktif, lihat catatan di kelas tsb).
 *
 * Kedua tabel itu SUDAH punya tampilan sendiri (daftar mentah baris-per-
 * baris, untuk kontrol keamanan/audit): menu "Pengguna" > tab "Riwayat
 * Login", dan menu "Cek Database dan Aplikasi" > tab "Log Akses Data".
 * Halaman INI tidak menduplikasi data/tampilan itu - khusus merangkum
 * data yang SAMA jadi kartu ringkasan & grafik, supaya mudah dibaca
 * sekilas. Untuk detail baris-per-baris (termasuk hapus log lama), tetap
 * pakai 2 menu di atas.
 *
 * Keputusan scope lewat AskUserQuestion (2026-10-29):
 * 1. Bentuk: dashboard ringkasan baru (bukan sekadar perbaikan tampilan
 *    2 menu lama).
 * 2. Arti "asal": SEMUA 3 jenis diminta - (a) asal sekolah/instansi
 *    (paling relevan, aplikasi ini internal per sekolah), (b) asal
 *    lokasi geografis (titik koordinat yang sudah tersimpan), (c) asal
 *    perangkat/browser (diuraikan dari User-Agent yang sudah tersimpan,
 *    lihat App\Support\InfoPerangkat).
 * 3. Hak akses: HANYA Superadmin (Gate 'akses-analitik-pengunjung', lihat
 *    App\Providers\AppServiceProvider) - sama seperti pola gate-gate
 *    menu lain yang bersifat lintas-sekolah/lintas-pengguna.
 *
 * Catatan desain (bukan aturan bisnis, murni pilihan teknis - lihat
 * panduan update untuk penjelasan lebih lengkap):
 * - Filter periode dibatasi 3 pilihan (7/30/90 hari terakhir, default 30)
 *   supaya grafik tren harian tidak membengkak tak terbatas.
 * - Titik lokasi geografis DIBULATKAN ke 3 angka desimal (± 100 meter)
 *   sebelum dikelompokkan, lalu masing-masing dikasih tautan buka di
 *   Google Maps (tanpa API key/pustaka peta baru - konsisten dengan
 *   kebijakan "tidak menambah dependency baru" di proyek ini).
 * - Grafik memakai Chart.js yang SUDAH terpasang (resources/js/app.js),
 *   pola markup disalin persis dari menu Dashboard (wire:ignore + Alpine
 *   x-data + <canvas x-ref>) - tidak ada paket/asset baru.
 * - Superadmin dikeluarkan dari daftar "Asal Sekolah" (tidak terikat 1
 *   sekolah tertentu) - bukan berarti login Superadmin diabaikan, tetap
 *   dihitung di kartu ringkasan & breakdown per level akses.
 */
#[Layout('layouts.app')]
#[Title('Analitik Pengunjung')]
class Index extends Component
{
    #[Url(as: 'periode')]
    public int $periode = 30;

    public function updatedPeriode(): void
    {
        if (! in_array($this->periode, [7, 30, 90], true)) {
            $this->periode = 30;
        }
    }

    private function sejakTanggal(): Carbon
    {
        return now()->startOfDay()->subDays($this->periode - 1);
    }

    /**
     * Kartu ringkasan: total login, pengunjung unik (akun berbeda), total
     * aktivitas (buka halaman + unduh), dan rata-rata login per hari -
     * semua dihitung dalam rentang periode yang dipilih.
     */
    private function ringkasan(): array
    {
        $sejak = $this->sejakTanggal();

        $totalLogin = LoginHistory::where('login_at', '>=', $sejak)->count();
        $pengunjungUnik = LoginHistory::where('login_at', '>=', $sejak)->distinct('user_id')->count('user_id');
        $totalAktivitas = AksesDataLog::where('created_at', '>=', $sejak)->count();

        return [
            'totalLogin' => $totalLogin,
            'pengunjungUnik' => $pengunjungUnik,
            'totalAktivitas' => $totalAktivitas,
            'rataRataLoginPerHari' => $this->periode > 0 ? round($totalLogin / $this->periode, 1) : 0,
        ];
    }

    /**
     * Tren jumlah login per hari sepanjang periode - hari yang tidak ada
     * login SENGAJA tetap dimunculkan dengan jumlah 0 (bukan dilompati),
     * supaya grafik garis/batang tidak terlihat seperti data hilang.
     */
    private function trendLoginHarian(): array
    {
        $sejak = $this->sejakTanggal();

        $perHari = LoginHistory::where('login_at', '>=', $sejak)
            ->selectRaw('DATE(login_at) as tanggal, COUNT(*) as jumlah')
            ->groupBy('tanggal')
            ->pluck('jumlah', 'tanggal');

        $hasil = [];
        for ($i = 0; $i < $this->periode; $i++) {
            $tgl = $sejak->copy()->addDays($i);
            $kunci = $tgl->format('Y-m-d');
            $hasil[] = [
                'label' => $tgl->translatedFormat('d M'),
                'jumlah' => (int) ($perHari[$kunci] ?? 0),
            ];
        }

        return $hasil;
    }

    /**
     * Breakdown jumlah login per level akses (Superadmin/Admin OPS/Admin
     * BOSP) dalam periode - dipakai utk grafik donut "Siapa yang Login".
     */
    private function loginPerLevel(): array
    {
        $sejak = $this->sejakTanggal();
        $warna = [
            User::LEVEL_SUPERADMIN => '#6366f1',
            User::LEVEL_ADMIN_OPS => '#10b981',
            User::LEVEL_ADMIN_BOSP => '#f59e0b',
        ];

        $labelLevel = User::levelAksesOptions();

        return LoginHistory::where('login_at', '>=', $sejak)
            ->selectRaw('level_akses, COUNT(*) as jumlah')
            ->groupBy('level_akses')
            ->get()
            ->map(fn ($b) => [
                'label' => $labelLevel[$b->level_akses] ?? $b->level_akses,
                'jumlah' => (int) $b->jumlah,
                'warna' => $warna[$b->level_akses] ?? '#94a3b8',
            ])
            ->values()
            ->all();
    }

    /**
     * Top 10 sekolah dengan login terbanyak dalam periode ("asal
     * sekolah/instansi") - Superadmin dikeluarkan (tidak terikat 1
     * sekolah). nama_sekolah dibaca dari KOLOM SNAPSHOT di login_histories
     * sendiri (bukan join ke tabel users), sama seperti pola tab "Riwayat
     * Login" di menu Pengguna.
     */
    private function topSekolah(): array
    {
        $sejak = $this->sejakTanggal();

        return LoginHistory::where('login_at', '>=', $sejak)
            ->where('level_akses', '!=', User::LEVEL_SUPERADMIN)
            ->whereNotNull('nama_sekolah')
            ->selectRaw('nama_sekolah, COUNT(*) as jumlah')
            ->groupBy('nama_sekolah')
            ->orderByDesc('jumlah')
            ->limit(10)
            ->get()
            ->map(fn ($b) => ['nama_sekolah' => $b->nama_sekolah, 'jumlah' => (int) $b->jumlah])
            ->all();
    }

    /**
     * Titik lokasi login (dibulatkan 3 desimal, ± 100 meter) dengan
     * jumlah login dari titik tsb dalam periode ("asal lokasi geografis")
     * - diambil dari login_histories (titik koordinat Browser Geolocation
     * API saat login). Baris yang tidak punya izin lokasi (NULL) tidak
     * disertakan - bukan dihitung sebagai 1 titik "tidak diketahui",
     * supaya daftar titik tetap murni koordinat asli.
     */
    private function titikLokasi(): array
    {
        $sejak = $this->sejakTanggal();

        return LoginHistory::where('login_at', '>=', $sejak)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw('ROUND(latitude, 3) as lat_r, ROUND(longitude, 3) as lng_r, COUNT(*) as jumlah')
            ->groupBy('lat_r', 'lng_r')
            ->orderByDesc('jumlah')
            ->limit(15)
            ->get()
            ->map(fn ($b) => [
                'latitude' => (float) $b->lat_r,
                'longitude' => (float) $b->lng_r,
                'jumlah' => (int) $b->jumlah,
            ])
            ->all();
    }

    /**
     * Berapa persen login dalam periode yang punya titik lokasi vs yang
     * tidak (user menolak izin lokasi browser, atau browsernya tidak
     * mendukung) - konteks tambahan supaya "Asal Lokasi Geografis" tidak
     * disalahpahami sebagai mencakup SEMUA login.
     */
    private function cakupanLokasi(): array
    {
        $sejak = $this->sejakTanggal();
        $total = LoginHistory::where('login_at', '>=', $sejak)->count();
        $adaLokasi = LoginHistory::where('login_at', '>=', $sejak)->whereNotNull('latitude')->whereNotNull('longitude')->count();

        return [
            'total' => $total,
            'adaLokasi' => $adaLokasi,
            'persen' => $total > 0 ? round(($adaLokasi / $total) * 100) : 0,
        ];
    }

    /**
     * Breakdown jenis perangkat/browser ("asal perangkat/browser") -
     * diuraikan dari kolom user_agent di akses_data_logs lewat
     * App\Support\InfoPerangkat (TIDAK mengurai ulang per baris satu-
     * satu di PHP untuk setiap baris: dikelompokkan dulu berdasarkan
     * teks User-Agent MENTAH di level database untuk efisiensi, baru
     * label manusiawinya dihitung ulang & digabung di PHP).
     */
    private function perangkat(): array
    {
        $sejak = $this->sejakTanggal();

        return AksesDataLog::where('created_at', '>=', $sejak)
            ->whereNotNull('user_agent')
            ->selectRaw('user_agent, COUNT(*) as jumlah')
            ->groupBy('user_agent')
            ->get()
            ->groupBy(fn ($b) => InfoPerangkat::label($b->user_agent))
            ->map(fn ($grup, $label) => ['label' => $label, 'jumlah' => $grup->sum('jumlah')])
            ->sortByDesc('jumlah')
            ->take(8)
            ->values()
            ->all();
    }

    /**
     * Top 10 menu/halaman paling sering DIBUKA dalam periode ("apa yang
     * dilihat" - bagian tampil halaman).
     */
    private function topMenuDibuka(): array
    {
        return $this->topMenu(AksesDataLog::JENIS_BUKA_HALAMAN);
    }

    /**
     * Top 10 menu/data paling sering DIUNDUH dalam periode ("apa yang
     * dilihat" - bagian unduh data, dipisah dari sekadar membuka halaman
     * karena bobot/maknanya berbeda).
     */
    private function topMenuDiunduh(): array
    {
        return $this->topMenu(AksesDataLog::JENIS_UNDUH);
    }

    private function topMenu(string $jenisAksi): array
    {
        $sejak = $this->sejakTanggal();

        return AksesDataLog::where('created_at', '>=', $sejak)
            ->where('jenis_aksi', $jenisAksi)
            ->selectRaw('nama_menu, COUNT(*) as jumlah')
            ->groupBy('nama_menu')
            ->orderByDesc('jumlah')
            ->limit(10)
            ->get()
            ->map(fn ($b) => ['nama_menu' => $b->nama_menu, 'jumlah' => (int) $b->jumlah])
            ->all();
    }

    public function render()
    {
        return view('livewire.analitik-pengunjung.index', [
            'ringkasan' => $this->ringkasan(),
            'trendLoginHarian' => $this->trendLoginHarian(),
            'loginPerLevel' => $this->loginPerLevel(),
            'topSekolah' => $this->topSekolah(),
            'titikLokasi' => $this->titikLokasi(),
            'cakupanLokasi' => $this->cakupanLokasi(),
            'perangkat' => $this->perangkat(),
            'topMenuDibuka' => $this->topMenuDibuka(),
            'topMenuDiunduh' => $this->topMenuDiunduh(),
        ]);
    }
}
