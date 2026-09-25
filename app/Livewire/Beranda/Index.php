<?php

namespace App\Livewire\Beranda;

use App\Models\Lampiran2a;
use App\Models\Lampiran2b;
use App\Models\Lampiran2c;
use App\Models\ProfilSekolah;
use App\Models\VervalRealisasiBosp;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Landing page PUBLIK (belum login) - ROUND KEENAM BELAS (2026-09-24,
 * bagian 2), permintaan user (verbatim): "saya ingin kamu buatkan
 * landing page yang muncul sebelum halaman form login. dengan gaya
 * animasi dan warna yang modern. pada landingpage tersebut memuat
 * informasi Daftar Sekolah yang sudah dan belum melakukan pendataan
 * OPS/Pendataan BOSP berdasarkan triwulan dan tahun sebagai informasi
 * awal yang muncul secara realtime sebelum login. agar daftar sekolah
 * tersebut tidak terlalu panjang ke bawah maka daftar sekolah tersebut
 * berupa dalam bentuk Sistem Paginasi (Pagination) atau 'Load More'.
 * kemudian di pojok kanan atas muncul tombol untuk masuk ke halaman
 * login nya."
 *
 * PENTING - istilah "landing page" di sini BERBEDA dari "landing page"
 * yang SUDAH ADA sebelumnya di
 * resources/views/livewire/pages/auth/login.blade.php (LANGKAH 1 di
 * DALAM halaman /login: pilihan jenis akses Superadmin/Admin
 * OPS/Admin BOSP). Halaman INI adalah halaman BARU & TERPISAH, tampil
 * pada route "/" (nama route: "beranda"), SEBELUM pengunjung masuk ke
 * /login sama sekali - sesuai permintaan eksplisit "landing page yang
 * muncul SEBELUM halaman form login".
 *
 * Keputusan bisnis yang SENGAJA ditanyakan lewat AskUserQuestion
 * (2026-09-24) sebelum menulis kode ini - semua jawaban user memilih
 * opsi yang direkomendasikan:
 * - "Definisi Data" -> "Pakai definisi yang sudah ada (Recommended)":
 *   "sudah pendataan OPS" pakai definisi "selesai OPS" yang SAMA PERSIS
 *   dgn App\Livewire\Dashboard\Index::idSekolahSelesaiOps() (Lampiran
 *   2a+2b+2c SEMUA lengkap utk 1 tahun+triwulan, sejak round 13).
 *   Method itu SENGAJA DIDUPLIKASI (bukan diekstrak ke helper/trait
 *   bersama) di bawah supaya App\Livewire\Dashboard\Index yang sudah
 *   berjalan & teruji SAMA SEKALI TIDAK disentuh (prinsip "jangan
 *   merubah yang sudah berfungsi") - duplikasi kecil (13 baris) dipilih
 *   drpd refactor lintas kelas yang berisiko. "Sudah pendataan BOSP"
 *   pakai VervalRealisasiBosp::idSekolahAdaDataUntukTriwulan() (method
 *   PUBLIC STATIC yang SUDAH ADA sejak round 13, dipakai ulang APA
 *   ADANYA - ada data di SALAH SATU dari 11 menu BOSP per triwulan).
 * - "Struktur List" -> "2 list terpisah (Recommended)": daftar Sekolah
 *   Pendataan OPS & daftar Sekolah Pendataan BOSP adalah 2 WIDGET
 *   terpisah (bukan 1 list gabungan 2 badge/sekolah), masing-masing 1
 *   badge status/baris & pagination SENDIRI-SENDIRI (pageName beda:
 *   'halamanOps' & 'halamanBosp'). Partial Blade YANG SUDAH ADA
 *   (resources/views/livewire/dashboard/partials/daftar-status-sekolah.blade.php,
 *   dipakai dashboard Superadmin utk widget Validasi BOSP/Validasi OPS)
 *   DIPAKAI ULANG APA ADANYA di sini - TIDAK ada partial baru yang
 *   dibuat khusus utk list-nya, supaya konsisten & otomatis ikut
 *   mewarisi fix "scrollTo => false" round ini (lihat
 *   daftar-status-sekolah.blade.php).
 * - "Kontrol Periode" -> "Tetap/fixed ke periode berjalan
 *   (Recommended)": TIDAK ada selector Tahun/Triwulan yang bisa diubah
 *   pengunjung (beda dari dashboard admin yang punya selector) - Tahun
 *   & Triwulan SELALU dihitung otomatis dari tanggal hari ini lewat
 *   PajakBospReguler::triwulanDariBulan(now()->month) (pemetaan
 *   bulan->triwulan yang SUDAH ADA sejak round kesembilan, dipakai
 *   ulang - BUKAN logika baru).
 * - "Realtime" -> "Cukup data terkini saat dibuka (Recommended)": TIDAK
 *   ada polling/auto-refresh berkala (tanpa wire:poll) - data selalu
 *   dihitung ulang langsung dari database setiap kali halaman
 *   dibuka/pindah halaman pagination, sama seperti render() Livewire
 *   standar pada dashboard admin.
 *
 * ROUND KETUJUH BELAS (2026-09-24) - permintaan user (verbatim): "pada
 * halaman landing page triwulannya default ke triwulan 1 bukan
 * triwulan 3. dan tambahkan tombol Tahun dan pilihan triwulan pada
 * halaman landingpage awal. untuk mempermudah menampilkan data nya."
 * Ini SECARA EKSPLISIT membalik keputusan "Kontrol Periode" round
 * sebelumnya di atas (yang TIDAK punya selector & auto dari tanggal
 * hari ini) - instruksi baru ini jelas & tidak ambigu, jadi TIDAK lewat
 * AskUserQuestion lagi:
 * - Default `$triwulan` diganti dari
 *   `PajakBospReguler::triwulanDariBulan(now()->month)` (otomatis dari
 *   bulan berjalan - waktu ditulis 2026-09-24/bulan 9 = Triwulan 3)
 *   MENJADI SELALU `1` (Triwulan 1) - PERSIS konvensi default yang
 *   SUDAH ADA di App\Livewire\Dashboard\Index::mount() &
 *   App\Livewire\TimelinePekerjaan\Index (keputusan round kedua belas:
 *   "TW-1 default").
 * - Selector Tahun & Triwulan DITAMBAHKAN, memakai ULANG APA ADANYA
 *   partial yang SUDAH ADA & TERUJI
 *   (resources/views/livewire/dashboard/partials/selector-triwulan.blade.php,
 *   dipakai dashboard Admin OPS/BOSP) - method `tahunOptions()` di
 *   bawah juga SENGAJA DIDUPLIKASI (bukan diekstrak) dari
 *   App\Livewire\Dashboard\Index::tahunOptions() dgn alasan yang SAMA
 *   dgn idSekolahSelesaiOps() (jangan menyentuh kelas yang sudah
 *   berjalan). TIDAK butuh method updatedTahun()/updatedTriwulan() -
 *   partial ini men-set property publik `$tahun`/`$triwulan` langsung
 *   (wire:model.live & wire:click $set), lalu render() Livewire standar
 *   SUDAH menghitung ulang semuanya dari database tiap kali properti
 *   itu berubah (pola SAMA PERSIS dgn dashboard admin).
 *
 * Route "/" (nama route "beranda") dibungkus middleware bawaan Laravel
 * "guest" (alias standar, SUDAH DIPAKAI utk /login & /register di
 * routes/auth.php) - efeknya: user yang SUDAH login otomatis diarahkan
 * ke /dashboard (perilaku ini BUKAN kode baru, murni bawaan
 * Illuminate\Auth\Middleware\RedirectIfAuthenticated yang sudah dipakai
 * di aplikasi ini) - PERSIS menggantikan `Route::redirect('/',
 * '/dashboard')` yang sebelumnya UNCONDITIONAL utk SEMUA pengunjung;
 * user yang sudah login TETAP diarahkan ke /dashboard seperti
 * sebelumnya (tidak ada perubahan perilaku utk mereka), hanya
 * pengunjung yang BELUM login yang sekarang melihat halaman baru ini.
 */
#[Layout('layouts.beranda')]
#[Title('Beranda')]
class Index extends Component
{
    use WithPagination;

    public int $tahun;

    public int $triwulan;

    public function mount(): void
    {
        $this->tahun = now()->year;
        // Round ketujuh belas: default SELALU Triwulan 1 (permintaan
        // user eksplisit), BUKAN lagi otomatis dari bulan berjalan -
        // lihat docblock kelas di atas. Pengunjung sekarang BISA
        // mengubahnya sendiri lewat selector (lihat render()/view).
        $this->triwulan = 1;
    }

    public function render()
    {
        $tahun = $this->tahun;
        $triwulan = $this->triwulan;

        $perHalaman = 10;

        $totalSekolah = ProfilSekolah::count();

        $idSekolahSelesaiOps = $this->idSekolahSelesaiOps($tahun, $triwulan);

        $halamanOps = ProfilSekolah::orderBy('nama_sekolah')
            ->paginate($perHalaman, ['*'], 'halamanOps')
            ->through(fn (ProfilSekolah $s) => [
                'sekolah' => $s,
                'sudah' => $idSekolahSelesaiOps->contains($s->id),
            ]);

        $idSekolahSelesaiBosp = VervalRealisasiBosp::idSekolahAdaDataUntukTriwulan($tahun, $triwulan);

        $halamanBosp = ProfilSekolah::orderBy('nama_sekolah')
            ->paginate($perHalaman, ['*'], 'halamanBosp')
            ->through(fn (ProfilSekolah $s) => [
                'sekolah' => $s,
                'sudah' => $idSekolahSelesaiBosp->contains($s->id),
            ]);

        $opsSudah = $idSekolahSelesaiOps->count();
        $bospSudah = $idSekolahSelesaiBosp->count();

        return view('livewire.beranda.index', [
            'tahun' => $tahun,
            'tahunOptions' => $this->tahunOptions(),
            'triwulan' => $triwulan,
            'triwulanAktif' => $triwulan,
            'totalSekolah' => $totalSekolah,
            'opsSudah' => $opsSudah,
            'opsBelum' => max($totalSekolah - $opsSudah, 0),
            'opsPersen' => $totalSekolah > 0 ? (int) round(($opsSudah / $totalSekolah) * 100) : 0,
            'halamanOps' => $halamanOps,
            'bospSudah' => $bospSudah,
            'bospBelum' => max($totalSekolah - $bospSudah, 0),
            'bospPersen' => $totalSekolah > 0 ? (int) round(($bospSudah / $totalSekolah) * 100) : 0,
            'halamanBosp' => $halamanBosp,
        ]);
    }

    /**
     * Sama persis dgn
     * App\Livewire\Dashboard\Index::idSekolahSelesaiOps() (definisi
     * "selesai OPS": Lampiran 2a+2b+2c SEMUA lengkap) - SENGAJA
     * diduplikasi, lihat docblock kelas di atas.
     *
     * @return Collection<int, int>
     */
    private function idSekolahSelesaiOps(int $tahun, int $triwulan): Collection
    {
        $punya2a = Lampiran2a::where('tahun', $tahun)->where('triwulan', $triwulan)->distinct()->pluck('profil_sekolah_id');
        $punya2b = Lampiran2b::where('tahun', $tahun)->where('triwulan', $triwulan)->distinct()->pluck('profil_sekolah_id');
        $punya2c = Lampiran2c::where('tahun', $tahun)->where('triwulan', $triwulan)->distinct()->pluck('profil_sekolah_id');

        return $punya2a->intersect($punya2b)->intersect($punya2c)->values();
    }

    /**
     * Sama persis dgn App\Livewire\Dashboard\Index::tahunOptions() -
     * SENGAJA diduplikasi (bukan diekstrak ke helper bersama), lihat
     * docblock kelas di atas (round ketujuh belas).
     *
     * @return array<int, int>
     */
    private function tahunOptions(): array
    {
        return array_reverse(range(now()->year - 2, now()->year + 1));
    }
}
