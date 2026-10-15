<?php

namespace App\Livewire\Beranda;

use App\Models\Lampiran2a;
use App\Models\Lampiran2b;
use App\Models\Lampiran2c;
use App\Models\ProfilSekolah;
use App\Models\User;
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
 *   >>> DIBALIK 2026-09-27 (permintaan user, lihat bawah) - sekarang
 *   PAKAI wire:poll, lihat catatan "ROUND KEDELAPAN BELAS" di bawah. <<<
 *
 * ROUND KEDELAPAN BELAS (2026-09-27) - permintaan user (verbatim):
 * "untuk data yang landingpage (beranda) harus terupdate otomatis
 * apabila di aplikasi ada perubahan data baik secara lokal maupun
 * secara online." Dikonfirmasi lewat AskUserQuestion ("Update Beranda")
 * -> user memilih "Auto-refresh berkala selagi halaman terbuka" (BUKAN
 * opsi yang direkomendasikan "cukup data terkini saat dibuka") - ini
 * SECARA EKSPLISIT membalik keputusan "Realtime" round sebelumnya di
 * atas.
 *
 * Implementasi: `wire:poll.30s.visible` pada elemen pembungkus utama di
 * index.blade.php - Livewire otomatis memanggil ulang render() (jadi
 * query database di atas otomatis terhitung ulang) setiap 30 detik
 * SELAMA tab/halaman ini sedang aktif dilihat pengunjung (modifier
 * `.visible` - bawaan Livewire - otomatis MENGHENTIKAN polling saat tab
 * browser diminimalkan/pindah tab, supaya tidak membebani server tanpa
 * guna, lalu otomatis lanjut lagi begitu pengunjung kembali melihat tab
 * ini). Interval 30 detik adalah KEPUTUSAN TEKNIS (bukan aturan bisnis)
 * - dipilih sebagai keseimbangan antara data yang selalu terkini vs
 * beban query database dari halaman PUBLIK ini (tidak perlu login,
 * berpotensi dibuka banyak pengunjung sekaligus) - bisa diubah kalau
 * user ingin interval lain.
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
 *
 * ROUND KESEMBILAN BELAS (2026-10-16) - permintaan user (verbatim): "pada
 * landingpage beranda tambahkan rekapan admin ops dan admin BOSP yang
 * sudah dan belum registrasi ke aplikasi."
 *
 * Widget "Registrasi Admin OPS & Admin BOSP" ini SUDAH ADA sebelumnya di
 * dashboard Superadmin (App\Livewire\Dashboard\Index, round keempat
 * belas/2026-09-24, jawaban AskUserQuestion "Registrasi Akun" ->
 * "Sudah disetujui Superadmin") - definisi "sudah registrasi" (1 sekolah
 * dianggap "sudah registrasi" Admin OPS/BOSP HANYA kalau ADA user dgn
 * level terkait DAN is_approved = true) DIPAKAI ULANG APA ADANYA di sini
 * (konsisten dgn prinsip "Pakai definisi yang sudah ada" yang sudah
 * berlaku utk widget Pendataan OPS/BOSP di halaman ini) - method
 * idSekolahSudahRegistrasi() di bawah SENGAJA DIDUPLIKASI (bukan
 * diekstrak ke helper bersama) dari
 * App\Livewire\Dashboard\Index::idSekolahSudahRegistrasi(), dgn alasan
 * yang SAMA dgn idSekolahSelesaiOps()/tahunOptions() di atas (jangan
 * menyentuh kelas Dashboard yang sudah berjalan & teruji).
 *
 * Partial Blade yang dipakai utk daftar sekolahnya JUGA dipakai ulang
 * APA ADANYA dari dashboard
 * (resources/views/livewire/dashboard/partials/daftar-registrasi-admin.blade.php,
 * 1 daftar gabungan dgn 2 badge status/sekolah - OPS & BOSP) - BUKAN
 * mengikuti pola "2 daftar terpisah" yang dipakai widget Pendataan
 * OPS/BOSP di halaman ini, karena widget Registrasi ini adalah
 * TRANSPLANTASI LANGSUNG dari widget yang sudah ada & disetujui user
 * apa adanya (bukan desain UI baru), jadi bentuk aslinya (1 daftar
 * gabungan) dipertahankan apa adanya demi konsistensi dgn versi
 * dashboard & menghindari membuat partial baru yang menduplikasi.
 *
 * Status "sudah registrasi" TIDAK bergantung Tahun/Triwulan (sama
 * seperti di dashboard) - jadi TIDAK ikut berubah saat pengunjung
 * mengganti selector Tahun/Triwulan di halaman ini (selector itu HANYA
 * memengaruhi 2 widget Pendataan OPS/BOSP yang sudah ada).
 *
 * ROUND KEDUA PULUH (2026-10-16) - permintaan user (verbatim): "saya
 * ingin ada pilihan kirim rekapan yang ada di beranda ke whatsapp."
 *
 * Keputusan bisnis via AskUserQuestion (3 pertanyaan, krn menyangkut
 * bentuk & isi pesan yang dikirim keluar aplikasi - standing constraint
 * "jangan menebak aturan bisnis"):
 * - "Isi Pesan" -> "Semua 4 rekap jadi 1 pesan": 1 tombol "Kirim Rekap
 *   ke WhatsApp" (BUKAN 4 tombol terpisah per kartu), isi pesan
 *   menggabungkan ke-4 rekap (Pendataan OPS, Pendataan BOSP, Registrasi
 *   Admin OPS, Registrasi Admin BOSP) sekaligus.
 * - "Nomor Tujuan" -> "Pengunjung pilih sendiri kontak/grup tujuan":
 *   tombol membuka WhatsApp (app di HP / WhatsApp Web di desktop) lewat
 *   `https://api.whatsapp.com/send?text=...` (TANPA nomor tujuan di
 *   URL) dengan teks pesan sudah terisi otomatis - pengunjung sendiri
 *   yang memilih mau dikirim ke siapa/grup mana (pola "share" standar,
 *   TIDAK butuh WhatsApp Business API berbayar/kredensial apa pun).
 * - "Detail Pesan" -> user menjawab "no 1 dan no 2" = KEDUANYA: pesan
 *   berisi ringkasan angka+persen DAN SEKALIGUS daftar nama sekolah
 *   yang belum, utk tiap 1 dari ke-4 rekap.
 *
 * Keputusan TEKNIS (bukan aturan bisnis, jadi TIDAK lewat
 * AskUserQuestion): daftar nama sekolah yang belum pada tiap rekap
 * DIBATASI maksimal 10 nama (lihat `daftarNamaBelum()`) lalu ditutup
 * "dan N sekolah lainnya" kalau lebih - supaya URL `wa.me`/
 * `api.whatsapp.com` TIDAK terlalu panjang/berisiko terpotong oleh
 * browser (terutama di HP) kalau jumlah sekolah yang belum banyak.
 *
 * Bagian Pendataan OPS/BOSP pada pesan mengikuti Tahun/Triwulan yang
 * SEDANG DIPILIH pengunjung di selector (sama seperti 2 kartu & daftar
 * Pendataan OPS/BOSP yang sudah ada) - bagian Registrasi Admin OPS/BOSP
 * TIDAK bergantung Tahun/Triwulan (sama seperti kartu Registrasi di
 * atas).
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

        $halamanOps = ProfilSekolah::urutStandar()
            ->paginate($perHalaman, ['*'], 'halamanOps')
            ->through(fn (ProfilSekolah $s) => [
                'sekolah' => $s,
                'sudah' => $idSekolahSelesaiOps->contains($s->id),
            ]);

        $idSekolahSelesaiBosp = VervalRealisasiBosp::idSekolahAdaDataUntukTriwulan($tahun, $triwulan);

        $halamanBosp = ProfilSekolah::urutStandar()
            ->paginate($perHalaman, ['*'], 'halamanBosp')
            ->through(fn (ProfilSekolah $s) => [
                'sekolah' => $s,
                'sudah' => $idSekolahSelesaiBosp->contains($s->id),
            ]);

        $opsSudah = $idSekolahSelesaiOps->count();
        $opsBelum = max($totalSekolah - $opsSudah, 0);
        $opsPersen = $totalSekolah > 0 ? (int) round(($opsSudah / $totalSekolah) * 100) : 0;

        $bospSudah = $idSekolahSelesaiBosp->count();
        $bospBelum = max($totalSekolah - $bospSudah, 0);
        $bospPersen = $totalSekolah > 0 ? (int) round(($bospSudah / $totalSekolah) * 100) : 0;

        // Round kesembilan belas: widget "Registrasi Admin OPS & Admin
        // BOSP" - lihat docblock kelas di atas. TIDAK memakai $tahun/$triwulan
        // (status registrasi tidak bergantung periode).
        $idSekolahRegistrasiOps = $this->idSekolahSudahRegistrasi(User::LEVEL_ADMIN_OPS);
        $idSekolahRegistrasiBosp = $this->idSekolahSudahRegistrasi(User::LEVEL_ADMIN_BOSP);

        $registrasiOpsSudah = $idSekolahRegistrasiOps->count();
        $registrasiOpsBelum = max($totalSekolah - $registrasiOpsSudah, 0);
        $registrasiOpsPersen = $totalSekolah > 0 ? (int) round(($registrasiOpsSudah / $totalSekolah) * 100) : 0;

        $registrasiBospSudah = $idSekolahRegistrasiBosp->count();
        $registrasiBospBelum = max($totalSekolah - $registrasiBospSudah, 0);
        $registrasiBospPersen = $totalSekolah > 0 ? (int) round(($registrasiBospSudah / $totalSekolah) * 100) : 0;

        $halamanRegistrasi = ProfilSekolah::urutStandar()
            ->paginate($perHalaman, ['*'], 'halamanRegistrasi')
            ->through(fn (ProfilSekolah $s) => [
                'sekolah' => $s,
                'opsSudah' => $idSekolahRegistrasiOps->contains($s->id),
                'bospSudah' => $idSekolahRegistrasiBosp->contains($s->id),
            ]);

        // Round kedua puluh: tombol "Kirim Rekap ke WhatsApp" - lihat
        // docblock kelas di atas utk detail keputusan bisnis.
        $semuaSekolah = ProfilSekolah::urutStandar()->get(['id', 'nama_sekolah']);

        $pesanWhatsapp = implode("\n\n", [
            "*Rekap Pendataan & Registrasi SR CBD*\nTriwulan {$triwulan}, Tahun {$tahun}\nSumber: ".url('/'),
            $this->barisRekapWhatsapp('Pendataan OPS', $opsSudah, $opsBelum, $opsPersen, $this->daftarNamaBelum($semuaSekolah, $idSekolahSelesaiOps)),
            $this->barisRekapWhatsapp('Pendataan BOSP', $bospSudah, $bospBelum, $bospPersen, $this->daftarNamaBelum($semuaSekolah, $idSekolahSelesaiBosp)),
            $this->barisRekapWhatsapp('Registrasi Admin OPS', $registrasiOpsSudah, $registrasiOpsBelum, $registrasiOpsPersen, $this->daftarNamaBelum($semuaSekolah, $idSekolahRegistrasiOps)),
            $this->barisRekapWhatsapp('Registrasi Admin BOSP', $registrasiBospSudah, $registrasiBospBelum, $registrasiBospPersen, $this->daftarNamaBelum($semuaSekolah, $idSekolahRegistrasiBosp)),
        ]);

        $tautanWhatsapp = 'https://api.whatsapp.com/send?text='.rawurlencode($pesanWhatsapp);

        return view('livewire.beranda.index', [
            'tahun' => $tahun,
            'tahunOptions' => $this->tahunOptions(),
            'triwulan' => $triwulan,
            'triwulanAktif' => $triwulan,
            'totalSekolah' => $totalSekolah,
            'opsSudah' => $opsSudah,
            'opsBelum' => $opsBelum,
            'opsPersen' => $opsPersen,
            'halamanOps' => $halamanOps,
            'bospSudah' => $bospSudah,
            'bospBelum' => $bospBelum,
            'bospPersen' => $bospPersen,
            'halamanBosp' => $halamanBosp,
            'registrasiOpsSudah' => $registrasiOpsSudah,
            'registrasiOpsBelum' => $registrasiOpsBelum,
            'registrasiOpsPersen' => $registrasiOpsPersen,
            'registrasiBospSudah' => $registrasiBospSudah,
            'registrasiBospBelum' => $registrasiBospBelum,
            'registrasiBospPersen' => $registrasiBospPersen,
            'halamanRegistrasi' => $halamanRegistrasi,
            'tautanWhatsapp' => $tautanWhatsapp,
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

    /**
     * Sama persis dgn
     * App\Livewire\Dashboard\Index::idSekolahSudahRegistrasi() (definisi
     * "sudah registrasi": akun ADA & is_approved = true) - SENGAJA
     * diduplikasi, lihat docblock kelas di atas (round kesembilan belas).
     *
     * @return Collection<int, int>
     */
    private function idSekolahSudahRegistrasi(string $levelAkses): Collection
    {
        return User::query()
            ->where('level_akses', $levelAkses)
            ->where('is_approved', true)
            ->whereNotNull('profil_sekolah_id')
            ->distinct()
            ->pluck('profil_sekolah_id');
    }

    /**
     * Satu baris rekap utk pesan WhatsApp (round kedua puluh) - lihat
     * docblock kelas di atas.
     */
    private function barisRekapWhatsapp(string $label, int $sudah, int $belum, int $persen, string $daftarBelum): string
    {
        return "*{$label}*: {$persen}% ({$sudah} sudah, {$belum} belum)\nBelum: {$daftarBelum}";
    }

    /**
     * Daftar nama sekolah yang BELUM "sudah" (relatif thd $idSudah),
     * dibatasi maksimal $batas nama lalu ditutup "dan N sekolah lainnya"
     * - round kedua puluh, pembatasan ini keputusan TEKNIS (lihat
     * docblock kelas di atas), bukan aturan bisnis.
     *
     * @param  Collection<int, ProfilSekolah>  $semuaSekolah
     * @param  Collection<int, int>  $idSudah
     */
    private function daftarNamaBelum(Collection $semuaSekolah, Collection $idSudah, int $batas = 10): string
    {
        $namaBelum = $semuaSekolah
            ->reject(fn (ProfilSekolah $s) => $idSudah->contains($s->id))
            ->pluck('nama_sekolah');

        if ($namaBelum->isEmpty()) {
            return 'Tidak ada (semua sudah).';
        }

        if ($namaBelum->count() <= $batas) {
            return $namaBelum->implode(', ');
        }

        $sisa = $namaBelum->count() - $batas;

        return $namaBelum->take($batas)->implode(', ').", dan {$sisa} sekolah lainnya.";
    }
}
