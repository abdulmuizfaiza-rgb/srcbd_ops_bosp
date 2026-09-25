<?php

namespace App\Livewire\Dashboard;

use App\Models\Lampiran2a;
use App\Models\Lampiran2b;
use App\Models\Lampiran2c;
use App\Models\PendataanBosp;
use App\Models\PendataanOps;
use App\Models\ProfilSekolah;
use App\Models\User;
use App\Models\VervalRealisasiBosp;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Dashboard - visualisasi data ringkasan pendataan OPS & BOSP, dibedakan
 * per peran:
 * - Superadmin: rekap seluruh sekolah (widget LAMA tidak diubah round
 *   ketiga belas; round KEEMPAT BELAS MENAMBAHKAN widget baru di bagian
 *   bawah, lihat docblock dataSuperadmin() di bawah - TIDAK ADA widget
 *   lama yang dihapus/diubah).
 * - Admin OPS: "Dashboard Pendataan OPS" - rekap SEMUA sekolah (bukan
 *   cuma sekolahnya sendiri, lihat catatan round ketiga belas di bawah).
 * - Admin BOSP: "Dashboard Pendataan BOSP" - rekap SEMUA sekolah juga.
 *
 * ROUND KEEMPAT BELAS (permintaan user 2026-09-24): "pada login
 * superadmin pada dashboard saya ingin tambahkan data admin OPS dan
 * admin BOSP yang sudah regsitrasi dan belum registrasi. yang sudah
 * registrasi diberi warna biru dan yang belum diberi warna merah ...
 * tambahkan Data Sekolah yang sudah Validasi Pendataan BOSP atau
 * pendataan OPS dalam bentuk list dimana yang sudah diberi warna biru
 * yang belum diberi warna merah." Keputusan bisnis yang SENGAJA
 * ditanyakan lewat AskUserQuestion (2026-09-24) sebelum menulis kode
 * ini:
 * - "Bentuk Registrasi" -> "Ringkasan angka + daftar sekolah
 *   (Recommended)": kartu jumlah sudah/belum PER Admin OPS & Admin
 *   BOSP, DITAMBAH 1 daftar sekolah gabungan (2 badge/sekolah: status
 *   OPS & status BOSP).
 * - "Arti Validasi OPS" -> "Pakai definisi 'selesai pendataan OPS'
 *   (Recommended)": aplikasi ini BELUM punya fitur validasi/verval
 *   khusus OPS (yang ADA cuma utk BOSP, lihat VervalRealisasiBosp) -
 *   "Validasi Pendataan OPS" pada dashboard Superadmin memakai definisi
 *   "selesai OPS" yang SUDAH ADA sejak round ketiga belas
 *   (idSekolahSelesaiOps() - Lampiran 2a+2b+2c lengkap), BUKAN alur
 *   approve/reject baru.
 * - "Gabung/Pisah List" -> "2 daftar terpisah": daftar Validasi BOSP &
 *   daftar Validasi OPS ("selesai") adalah 2 WIDGET terpisah (bukan 1
 *   list gabungan) - TAPI masing-masing widget-nya SENDIRI berisi 1
 *   list utuh (SEMUA sekolah), dibedakan lewat badge warna biru
 *   (sudah)/merah (belum) per baris - BUKAN dipecah jadi 2 kolom
 *   sudah/belum seperti pola daftar-sekolah.blade.php dashboard Admin
 *   OPS/BOSP (pola itu TIDAK dipakai di sini krn warnanya beda &
 *   permintaannya eksplisit "dalam bentuk list ... warna biru/merah").
 * - "Cakupan Triwulan" -> "Tambahkan pemilih Tahun & Triwulan
 *   (Recommended)": dashboard Superadmin SEBELUMNYA tidak punya
 *   selector Tahun/Triwulan sama sekali (property $tahun/$triwulan yang
 *   sudah ada sejak round ketiga belas dipakai ulang di sini, TIDAK
 *   perlu property baru) - HANYA memengaruhi 2 widget BARU (Validasi
 *   BOSP & Validasi OPS); widget LAMA "Jumlah Data Lampiran per
 *   Triwulan" & lainnya TETAP menampilkan SEMUA 4 triwulan sekaligus
 *   seperti sebelumnya, TIDAK terpengaruh selector baru ini. Registrasi
 *   Admin OPS/BOSP TIDAK bergantung triwulan (akun-nya sendiri tidak
 *   dispesifikkan per triwulan), jadi TIDAK dipengaruhi selector ini.
 *
 * ROUND KETIGA BELAS (permintaan user 2026-09-24): "saya ingin kamu
 * buatkan saya dashboard seperti template gambar yang saya upload ...
 * Dashboard pendataan OPS memuat informasi Pendataan OPS pertriwulan,
 * daftar sekolah yang sudah mengerjakan pendataan dan yang belum. jumlah
 * data sekolah berdasarkan Negeri dan swasta, Data OPS yang sudah
 * registrasi berdasarkan status Negeri dan swasta ... Dasboard pendatann
 * BOSP memuat informasi data sekolah yang sudah mengerjakan pendataan dan
 * belum melakukan pendataan berdasarkan triwulan, data admin bosp yang
 * sudah registrasi atau belum berdasarkan status negeri dan swasta, data
 * sekolah yang sudah melakukan validasi atau belum berdasarkan negeri dan
 * swasta."
 *
 * Ini MENGGANTIKAN TOTAL isi cabang admin_ops & admin_bosp yang lama
 * (yang sebelumnya hanya rekap sekolah sendiri) - jawaban AskUserQuestion
 * "Ganti Dashboard Lama" -> "Ya, ganti isi dashboard Admin OPS/BOSP
 * (Recommended)": halaman/route /dashboard yang sama dipakai lagi (TIDAK
 * ada menu/link baru), cabang Superadmin (dataSuperadmin() di bawah & isi
 * blade-nya) SAMA SEKALI TIDAK disentuh.
 *
 * Keputusan bisnis lain yang SENGAJA ditanyakan lewat AskUserQuestion
 * (2026-09-24) sebelum menulis kode ini - lihat juga docblock method
 * masing-masing di bawah:
 * - "Cakupan Dashboard" -> "Semua sekolah (Global)": kedua dashboard baru
 *   ini menampilkan rekap SEMUA sekolah, BUKAN cuma sekolah Admin
 *   OPS/Admin BOSP yang login (beda dari perilaku lama & dari
 *   dataSuperadmin() yang memang sudah global sejak awal).
 * - "Selesai OPS" -> "Isi SEMUA Lampiran 2a, 2b, dan 2c": 1 sekolah
 *   dianggap "sudah mengerjakan pendataan OPS" utk 1 triwulan HANYA kalau
 *   KETIGA lampiran itu sudah ada datanya utk triwulan tsb (lihat
 *   idSekolahSelesaiOps()).
 * - "Selesai BOSP" -> pakai definisi yang sudah ada di fitur Validasi:
 *   VervalRealisasiBosp::idSekolahAdaDataUntukTriwulan() (versi borongan
 *   dari adaDataUntukTriwulan() yang sudah ada, ditambahkan khusus utk
 *   dashboard ini, lihat docblock method itu).
 * - "Registrasi Akun" -> "Sudah disetujui Superadmin": 1 sekolah dianggap
 *   "sudah registrasi" Admin OPS/BOSP HANYA kalau ADA user dgn level
 *   terkait DAN is_approved=true.
 * - "Definisi Validasi" -> "Hanya status Sesuai yang dihitung sudah": 1
 *   sekolah dianggap "sudah validasi" utk 1 triwulan HANYA kalau status
 *   verval-nya `VervalRealisasiBosp::STATUS_SESUAI` (status "Belum
 *   Sesuai" TETAP dihitung "belum").
 *
 * Tahun+Triwulan dipilih lewat property publik $tahun/$triwulan (pola
 * sama seperti App\Livewire\TimelinePekerjaan\Index &
 * App\Livewire\PendataanBosp\LaporanRealisasiBosp\Index) - default
 * SELALU Triwulan 1 (konsisten dgn keputusan round kedua belas utk
 * Timeline Pekerjaan). TIDAK butuh updatedTahun()/updatedTriwulan() -
 * beda dari TimelinePekerjaan yang punya cache `$tanggal` yang perlu
 * dimuat ulang manual, di sini render() SUDAH menghitung ulang semuanya
 * dari database setiap kali dipanggil, jadi Livewire otomatis re-render
 * dengan data baru begitu $tahun/$triwulan berubah, tanpa hook tambahan.
 */
#[Layout('layouts.app')]
#[Title('Dashboard')]
class Index extends Component
{
    use WithPagination;

    public int $tahun;

    public int $triwulan;

    public function mount(): void
    {
        $this->tahun = now()->year;
        $this->triwulan = 1;
    }

    public function render()
    {
        $user = auth()->user();

        $data = match (true) {
            $user->isSuperadmin() => $this->dataSuperadmin(),
            $user->isAdminOps() => $this->dataAdminOps(),
            $user->isAdminBosp() => $this->dataAdminBosp(),
            default => ['peran' => null],
        };

        return view('livewire.dashboard.index', $data);
    }

    private function dataSuperadmin(): array
    {
        $daftarSekolah = ProfilSekolah::all();
        $totalSekolah = $daftarSekolah->count();

        $totalNegeri = $daftarSekolah->where('status', ProfilSekolah::STATUS_NEGERI)->count();
        $totalSwasta = $daftarSekolah->where('status', ProfilSekolah::STATUS_SWASTA)->count();

        $profilLengkap = $daftarSekolah->filter->isLengkap()->count();
        $profilBelum = $totalSekolah - $profilLengkap;

        $idOpsIsi = PendataanOps::count();
        $idOpsBelum = max($totalSekolah - $idOpsIsi, 0);

        $idBospIsi = PendataanBosp::count();
        $idBospBelum = max($totalSekolah - $idBospIsi, 0);

        $lampiranPerTriwulan = collect(range(1, 4))->map(fn ($triwulan) => [
            'triwulan' => $triwulan,
            'lampiran_2a' => Lampiran2a::where('triwulan', $triwulan)->count(),
            'lampiran_2b' => Lampiran2b::where('triwulan', $triwulan)->count(),
            'lampiran_2c' => Lampiran2c::where('triwulan', $triwulan)->count(),
        ]);

        $sekolahPerKecamatan = $daftarSekolah
            ->groupBy(fn (ProfilSekolah $s) => $s->kecamatan ?: 'Belum diisi')
            ->map->count()
            ->sortDesc();

        // ============ ROUND KEEMPAT BELAS (widget BARU di bawah) ============
        // Lihat docblock kelas di atas utk detail keputusan bisnis
        // (jawaban AskUserQuestion 2026-09-24). Widget di ATAS (KPI
        // cards, 3 donut Kelengkapan, 2 bar chart) SAMA SEKALI TIDAK
        // disentuh/dihitung ulang - bagian ini MURNI TAMBAHAN.
        //
        // ROUND KELIMA BELAS (2026-09-24): permintaan user "dibuat dalam
        // bentuk Sistem Paginasi (Pagination) ... supaya tidak terlalu
        // panjang ke bawah" utk 3 daftar sekolah di bawah (Registrasi,
        // Validasi BOSP, Validasi OPS) - MURNI perubahan teknis/UI (bukan
        // aturan bisnis), jadi TIDAK lewat AskUserQuestion. Dipilih
        // "Pagination" (bukan "Load More") krn itu pola yang SUDAH ADA &
        // konsisten di aplikasi ini (Livewire\WithPagination, lihat
        // App\Livewire\Pengguna\Index & App\Livewire\PendataanOps\Lampiran2a\Index).
        // 3 daftar dipaginasi TERPISAH (masing2 $pageName sendiri) krn
        // ketiganya tampil BERSAMAAN di 1 halaman.
        $tahun = $this->tahun;
        $triwulanAktif = $this->triwulan;

        $perHalamanDaftarSekolah = 10;

        // "Registrasi Admin OPS/BOSP" - definisi "sudah registrasi" SAMA
        // PERSIS dgn dashboard Admin OPS/BOSP (idSekolahSudahRegistrasi(),
        // sudah ada sejak round ketiga belas, TIDAK diubah): akun ADA &
        // is_approved = true. TIDAK bergantung triwulan. Hitungan
        // sudah/belum dihitung LANGSUNG dari jumlah id (BUKAN dari daftar
        // sekolah yang di-paginate) supaya angkanya tetap akurat utk
        // SEMUA sekolah, bukan cuma yang tampil di halaman aktif.
        $idSekolahRegistrasiOps = $this->idSekolahSudahRegistrasi(User::LEVEL_ADMIN_OPS);
        $idSekolahRegistrasiBosp = $this->idSekolahSudahRegistrasi(User::LEVEL_ADMIN_BOSP);

        $registrasiOpsSudah = $idSekolahRegistrasiOps->count();
        $registrasiBospSudah = $idSekolahRegistrasiBosp->count();

        $halamanRegistrasi = ProfilSekolah::orderBy('nama_sekolah')
            ->paginate($perHalamanDaftarSekolah, ['*'], 'halamanRegistrasi')
            ->through(fn (ProfilSekolah $s) => [
                'sekolah' => $s,
                'opsSudah' => $idSekolahRegistrasiOps->contains($s->id),
                'bospSudah' => $idSekolahRegistrasiBosp->contains($s->id),
            ]);

        // "Validasi Pendataan BOSP" - status verval "Sesuai" (definisi
        // SAMA dgn dashboard Admin BOSP), utk Tahun+Triwulan aktif yang
        // dipilih lewat selector baru.
        $idSekolahValidasiBospAktif = VervalRealisasiBosp::query()
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulanAktif)
            ->where('status', VervalRealisasiBosp::STATUS_SESUAI)
            ->distinct()
            ->pluck('profil_sekolah_id');

        $halamanValidasiBosp = ProfilSekolah::orderBy('nama_sekolah')
            ->paginate($perHalamanDaftarSekolah, ['*'], 'halamanValidasiBosp')
            ->through(fn (ProfilSekolah $s) => [
                'sekolah' => $s,
                'sudah' => $idSekolahValidasiBospAktif->contains($s->id),
            ]);

        // "Validasi Pendataan OPS" - PAKAI definisi "selesai OPS" yang
        // SUDAH ADA (idSekolahSelesaiOps(), TIDAK diubah): Lampiran
        // 2a+2b+2c SEMUA lengkap - lihat jawaban AskUserQuestion "Arti
        // Validasi OPS" di docblock kelas.
        $idSekolahSelesaiOpsAktif = $this->idSekolahSelesaiOps($tahun, $triwulanAktif);

        $halamanValidasiOps = ProfilSekolah::orderBy('nama_sekolah')
            ->paginate($perHalamanDaftarSekolah, ['*'], 'halamanValidasiOps')
            ->through(fn (ProfilSekolah $s) => [
                'sekolah' => $s,
                'sudah' => $idSekolahSelesaiOpsAktif->contains($s->id),
            ]);

        return [
            'peran' => 'superadmin',
            'totalSekolah' => $totalSekolah,
            'totalNegeri' => $totalNegeri,
            'totalSwasta' => $totalSwasta,
            'totalPengguna' => User::count(),
            'profilLengkap' => $profilLengkap,
            'profilBelum' => $profilBelum,
            'idOpsIsi' => $idOpsIsi,
            'idOpsBelum' => $idOpsBelum,
            'idBospIsi' => $idBospIsi,
            'idBospBelum' => $idBospBelum,
            'lampiranPerTriwulan' => $lampiranPerTriwulan,
            'sekolahPerKecamatan' => $sekolahPerKecamatan,

            // Round keempat belas (widget baru):
            'tahun' => $tahun,
            'tahunOptions' => $this->tahunOptions(),
            'triwulanAktif' => $triwulanAktif,
            'registrasiOpsSudah' => $registrasiOpsSudah,
            'registrasiOpsBelum' => max($totalSekolah - $registrasiOpsSudah, 0),
            'registrasiBospSudah' => $registrasiBospSudah,
            'registrasiBospBelum' => max($totalSekolah - $registrasiBospSudah, 0),
            'halamanRegistrasi' => $halamanRegistrasi,
            'validasiBospSudah' => $idSekolahValidasiBospAktif->count(),
            'validasiBospBelum' => max($totalSekolah - $idSekolahValidasiBospAktif->count(), 0),
            'halamanValidasiBosp' => $halamanValidasiBosp,
            'validasiOpsSudah' => $idSekolahSelesaiOpsAktif->count(),
            'validasiOpsBelum' => max($totalSekolah - $idSekolahSelesaiOpsAktif->count(), 0),
            'halamanValidasiOps' => $halamanValidasiOps,
        ];
    }

    /**
     * Pilihan Tahun pada selector Dashboard Pendataan OPS/BOSP - pola
     * SAMA PERSIS seperti App\Livewire\TimelinePekerjaan\Index::render().
     *
     * @return array<int, int>
     */
    private function tahunOptions(): array
    {
        return array_reverse(range(now()->year - 2, now()->year + 1));
    }

    /**
     * profil_sekolah_id yang SEMUA (Lampiran 2a DAN 2b DAN 2c) sudah ada
     * datanya utk 1 tahun+triwulan - definisi "sudah mengerjakan
     * pendataan OPS" (jawaban AskUserQuestion 2026-09-24 "Selesai OPS" ->
     * "Isi SEMUA Lampiran 2a, 2b, dan 2c"). Ditulis lewat 3 query
     * borongan (bukan per sekolah) supaya murah dipanggil 4x (1x per
     * triwulan) di dataAdminOps() di bawah.
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
     * profil_sekolah_id yang SUDAH punya user Admin OPS/Admin BOSP yang
     * SUDAH disetujui Superadmin (`is_approved` = true) - definisi
     * "sudah registrasi" (jawaban AskUserQuestion 2026-09-24 "Registrasi
     * Akun" -> "Sudah disetujui Superadmin"). Akun yang masih menunggu
     * persetujuan (is_approved = false) ATAU sekolah tanpa akun sama
     * sekali dianggap "belum".
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
     * Hitung "sudah" vs "total" utk 2 kelompok (Negeri/Swasta) sekaligus -
     * dipakai bersama oleh dataAdminOps() (widget Registrasi Admin OPS) &
     * dataAdminBosp() (widget Registrasi Admin BOSP & Validasi Sekolah) -
     * ketiganya sama-sama berbentuk "dari sekian sekolah Negeri/Swasta,
     * berapa yang sudah 'X'".
     *
     * @param  Collection<int, ProfilSekolah>  $daftarSekolah
     * @param  Collection<int, int>  $idSudah  profil_sekolah_id yang dianggap "sudah"
     * @return array{negeri: array{sudah: int, total: int}, swasta: array{sudah: int, total: int}}
     */
    private function rekapNegeriSwasta(Collection $daftarSekolah, Collection $idSudah): array
    {
        $buatBaris = fn (string $status) => [
            'sudah' => $daftarSekolah->where('status', $status)->filter(fn (ProfilSekolah $s) => $idSudah->contains($s->id))->count(),
            'total' => $daftarSekolah->where('status', $status)->count(),
        ];

        return [
            'negeri' => $buatBaris(ProfilSekolah::STATUS_NEGERI),
            'swasta' => $buatBaris(ProfilSekolah::STATUS_SWASTA),
        ];
    }

    /**
     * "Dashboard Pendataan OPS" (Admin OPS) - rekap SEMUA sekolah (lihat
     * docblock kelas di atas utk detail keputusan bisnisnya).
     */
    private function dataAdminOps(): array
    {
        $tahun = $this->tahun;
        $triwulanAktif = $this->triwulan;

        $daftarSekolah = ProfilSekolah::orderBy('nama_sekolah')->get();
        $totalSekolah = $daftarSekolah->count();
        $totalNegeri = $daftarSekolah->where('status', ProfilSekolah::STATUS_NEGERI)->count();
        $totalSwasta = $daftarSekolah->where('status', ProfilSekolah::STATUS_SWASTA)->count();

        // "Pendataan OPS pertriwulan" - SEMUA 4 triwulan sekaligus (grafik
        // tren), dihitung 1x lalu dipakai ulang utk daftar sekolah
        // triwulan aktif di bawah (hindari hitung 2x utk triwulan yang
        // sama).
        $idSelesaiPerTriwulan = collect(range(1, 4))
            ->mapWithKeys(fn (int $tw) => [$tw => $this->idSekolahSelesaiOps($tahun, $tw)]);

        $opsPerTriwulan = $idSelesaiPerTriwulan->map(fn (Collection $ids, int $tw) => [
            'triwulan' => $tw,
            'selesai' => $ids->count(),
            'belum' => max($totalSekolah - $ids->count(), 0),
        ])->values();

        $idSelesaiAktif = $idSelesaiPerTriwulan[$triwulanAktif] ?? collect();

        return [
            'peran' => 'admin_ops',
            'tahun' => $tahun,
            'tahunOptions' => $this->tahunOptions(),
            'triwulanAktif' => $triwulanAktif,
            'totalSekolah' => $totalSekolah,
            'totalNegeri' => $totalNegeri,
            'totalSwasta' => $totalSwasta,
            'opsPerTriwulan' => $opsPerTriwulan,
            'persenSelesaiAktif' => $totalSekolah > 0 ? (int) round(($idSelesaiAktif->count() / $totalSekolah) * 100) : 0,
            'sekolahSudahAktif' => $daftarSekolah->filter(fn (ProfilSekolah $s) => $idSelesaiAktif->contains($s->id))->values(),
            'sekolahBelumAktif' => $daftarSekolah->reject(fn (ProfilSekolah $s) => $idSelesaiAktif->contains($s->id))->values(),
            'registrasiOps' => $this->rekapNegeriSwasta($daftarSekolah, $this->idSekolahSudahRegistrasi(User::LEVEL_ADMIN_OPS)),
        ];
    }

    /**
     * "Dashboard Pendataan BOSP" (Admin BOSP) - rekap SEMUA sekolah
     * (lihat docblock kelas di atas utk detail keputusan bisnisnya).
     */
    private function dataAdminBosp(): array
    {
        $tahun = $this->tahun;
        $triwulanAktif = $this->triwulan;

        $daftarSekolah = ProfilSekolah::orderBy('nama_sekolah')->get();
        $totalSekolah = $daftarSekolah->count();

        // "sekolah sudah/belum mengerjakan pendataan BOSP berdasarkan
        // triwulan" - SEMUA 4 triwulan sekaligus (grafik tren), dihitung
        // 1x lalu dipakai ulang utk daftar sekolah triwulan aktif di
        // bawah.
        $idSelesaiPerTriwulan = collect(range(1, 4))
            ->mapWithKeys(fn (int $tw) => [$tw => VervalRealisasiBosp::idSekolahAdaDataUntukTriwulan($tahun, $tw)]);

        $bospPerTriwulan = $idSelesaiPerTriwulan->map(fn (Collection $ids, int $tw) => [
            'triwulan' => $tw,
            'selesai' => $ids->count(),
            'belum' => max($totalSekolah - $ids->count(), 0),
        ])->values();

        $idSelesaiAktif = $idSelesaiPerTriwulan[$triwulanAktif] ?? collect();

        // "sudah melakukan validasi" - HANYA status Sesuai yang dihitung
        // (jawaban AskUserQuestion 2026-09-24 "Definisi Validasi").
        $idSudahValidasiAktif = VervalRealisasiBosp::query()
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulanAktif)
            ->where('status', VervalRealisasiBosp::STATUS_SESUAI)
            ->distinct()
            ->pluck('profil_sekolah_id');

        return [
            'peran' => 'admin_bosp',
            'tahun' => $tahun,
            'tahunOptions' => $this->tahunOptions(),
            'triwulanAktif' => $triwulanAktif,
            'totalSekolah' => $totalSekolah,
            'bospPerTriwulan' => $bospPerTriwulan,
            'persenSelesaiAktif' => $totalSekolah > 0 ? (int) round(($idSelesaiAktif->count() / $totalSekolah) * 100) : 0,
            'sekolahSudahAktif' => $daftarSekolah->filter(fn (ProfilSekolah $s) => $idSelesaiAktif->contains($s->id))->values(),
            'sekolahBelumAktif' => $daftarSekolah->reject(fn (ProfilSekolah $s) => $idSelesaiAktif->contains($s->id))->values(),
            'registrasiBosp' => $this->rekapNegeriSwasta($daftarSekolah, $this->idSekolahSudahRegistrasi(User::LEVEL_ADMIN_BOSP)),
            'validasiBosp' => $this->rekapNegeriSwasta($daftarSekolah, $idSudahValidasiAktif),
        ];
    }
}
