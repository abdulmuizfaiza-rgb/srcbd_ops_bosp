<?php

namespace App\Livewire\TimelinePekerjaan;

use App\Livewire\Concerns\HasZoomTampilan;
use App\Models\DeadlinePekerjaan;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Halaman "Timeline Pekerjaan" (round kedelapan, bagian B) - permintaan
 * user 2026-09-23: "saya juga ingin dibuatkan menu Timeline pekerjaan
 * baik untuk pendataan OPS dan pendataan BOSP supaya lebih disiplin dari
 * sisi deadline pekerjaannya. dimana superadmin bisa mengatur nya untuk
 * deadline pendataan OPS dan deadline pendataan BOSP."
 *
 * Superadmin: atur/ubah/kosongkan tanggal deadline tiap tahap kerja
 * (lihat DeadlinePekerjaan::daftarTahapKerja()) untuk tahun+triwulan
 * yang dipilih - jawaban AskUserQuestion "Akses lihat": "Superadmin
 * kelola". Admin OPS & Admin BOSP: HANYA bisa lihat tabel yang sama
 * (read-only, termasuk status terlambat/tidak) - jawaban yang sama:
 * "Admin OPS/BOSP lihat saja". Ketiga peran melihat SEMUA 16 baris (OPS
 * maupun BOSP sekaligus, tidak difilter per kategori) - jawaban
 * AskUserQuestion "Akses lihat" -> "Semua bisa lihat" TIDAK menyebutkan
 * pembatasan per kategori, jadi ditafsirkan semua peran melihat
 * gambaran lengkap.
 *
 * DIMENSI TRIWULAN ditambahkan round kesembilan bagian B (permintaan
 * user 2026-09-23, poin 3): "pada menu Timeline Pekerjaan tambahkan
 * pilihan triwulan, jadi pengaturan timeline berdasarkan triwulan."
 * Jawaban AskUserQuestion "Cakupan triwulan" -> "Semua 16 tahap kerja
 * (Recommended)": SEMUA 16 tahap kerja (termasuk yang sebelumnya
 * tahunan saja) sekarang punya deadline terpisah per triwulan (1-4),
 * dipilih lewat selector $triwulan (pola sama seperti $tahun) - lihat
 * juga terapkanSemua() di bawah untuk fitur "terapkan tanggal yang sama
 * ke semua tahap kerja" (keputusan desain TEKNIS, TIDAK ditanyakan
 * eksplisit lewat AskUserQuestion - lihat PETUNJUK.txt paket update
 * ini).
 *
 * PENTING: halaman ini BARU mengatur/menampilkan deadline - efek
 * "memblokir input" di 16 menu terkait BELUM diterapkan di round ini
 * (lihat catatan di App\Livewire\Concerns\MenolakEditJikaLewatDeadline).
 *
 * ROUND KEDUA BELAS (permintaan user 2026-09-24): "saya ingin tombol
 * Terapkan ke Semua antara Pendataan OPS dan Pendataan BOSP di pisahkan
 * tidak di gabungkan jadi di buat per pendataan. kemudian buatkan option
 * tombol reset untuk semua apabila admin OPS/admin BOSP nya mau reset.
 * dan tetatp berdasarkan triwulan masing-masing dengan TW 1 sebagai
 * default nya." Perubahan:
 * 1) "Terapkan ke Semua" DIPISAH per kategori - lihat terapkanSemua()
 *    (sekarang menerima parameter $kategori, hanya menyentuh tahap
 *    kerja kategori itu) & property $tanggalTerapkanSemua (sekarang
 *    array ['ops' => ..., 'bosp' => ...], BUKAN 1 string gabungan).
 * 2) Tombol BARU "Reset Semua" per kategori - lihat resetSemua().
 *    Jawaban AskUserQuestion "Akses Reset Semua" -> "Hanya Superadmin
 *    (Recommended)": tombol ini TETAP hanya bisa dipakai Superadmin,
 *    SAMA seperti kelola deadline lainnya - frasa "apabila admin
 *    OPS/admin BOSP nya mau reset" pada permintaan user ditafsirkan
 *    sebagai "untuk kategori Pendataan OPS/Pendataan BOSP", BUKAN
 *    pemberian akses baru ke role Admin OPS/Admin BOSP (yang tetap
 *    read-only, tidak berubah).
 * 3) Default $triwulan pada mount() diubah jadi SELALU 1 (TW-1) -
 *    SEBELUMNYA mengikuti bulan berjalan lewat
 *    PajakBospReguler::triwulanDariBulan().
 */
#[Layout('layouts.app')]
#[Title('Timeline Pekerjaan')]
class Index extends Component
{
    use HasZoomTampilan;

    public int $tahun;

    public int $triwulan;

    /**
     * kunci_menu => 'Y-m-d' atau null (belum diatur), UNTUK triwulan
     * yang sedang aktif ($this->triwulan) saja - diisi ulang tiap
     * updatedTahun()/updatedTriwulan() dari data ter-terbaru di
     * database. KEY di array ini SENGAJA memakai kunciAman() (titik
     * diganti "__"), BUKAN kunci_menu mentah - kunci_menu mentah
     * berbentuk nama route (mis. "pendataan-bosp.rekap-rkas") yang
     * MENGANDUNG titik, dan Livewire menafsirkan titik pada nama
     * property sebagai path array bersarang (`wire:model="tanggal.a.b"`
     * dibaca sebagai $tanggal['a']['b'], BUKAN $tanggal['a.b']) - kalau
     * key aslinya dipakai langsung, wire:model/set()/assertSet() diam-
     * diam menyimpan ke path bersarang yang salah dan datanya tidak
     * pernah benar-benar tersimpan.
     *
     * @var array<string, string|null>
     */
    public array $tanggal = [];

    /**
     * Tanggal yang dipilih pada kotak "Terapkan ke Semua" - round
     * kesembilan bagian B: TIDAK auto-save seperti kotak per baris,
     * HANYA disimpan begitu tombol "Terapkan ke Semua" kategori terkait
     * diklik (lihat terapkanSemua()) supaya tidak ada risiko tidak
     * sengaja menimpa banyak tahap kerja sekaligus dari 1 kali ketik.
     *
     * Round kedua belas: diubah dari 1 string gabungan menjadi array
     * per KATEGORI (['ops' => 'Y-m-d'|'', 'bosp' => 'Y-m-d'|'']) supaya
     * kotak & tombol "Terapkan ke Semua" Pendataan OPS terpisah total
     * dari Pendataan BOSP (permintaan user 2026-09-24).
     *
     * @var array<string, string>
     */
    public array $tanggalTerapkanSemua = ['ops' => '', 'bosp' => ''];

    public function mount(): void
    {
        $this->tahun = now()->year;

        // Round kedua belas (permintaan user 2026-09-24): default TETAP
        // TW-1, TIDAK LAGI mengikuti bulan berjalan (sebelumnya lewat
        // PajakBospReguler::triwulanDariBulan(now()->month)).
        $this->triwulan = 1;

        $this->muatTanggal();
    }

    public function updatedTahun(): void
    {
        $this->muatTanggal();
    }

    public function updatedTriwulan(): void
    {
        $this->muatTanggal();
    }

    /**
     * Ganti titik pada kunci_menu (nama route) dengan "__" supaya aman
     * dipakai sebagai key array pada property Livewire - lihat docblock
     * `$tanggal` di atas.
     */
    public static function kunciAman(string $kunciMenu): string
    {
        return str_replace('.', '__', $kunciMenu);
    }

    protected function muatTanggal(): void
    {
        $existing = DeadlinePekerjaan::query()
            ->where('tahun', $this->tahun)
            ->where('triwulan', $this->triwulan)
            ->get()
            ->keyBy('kunci_menu');

        $this->tanggal = [];
        foreach (DeadlinePekerjaan::daftarTahapKerja() as $kunciMenu => $info) {
            $baris = $existing[$kunciMenu] ?? null;
            $this->tanggal[self::kunciAman($kunciMenu)] = $baris?->tanggal_deadline?->format('Y-m-d');
        }

        $this->tanggalTerapkanSemua = ['ops' => '', 'bosp' => ''];
    }

    public function simpanDeadline(string $kunciMenu): void
    {
        abort_unless(auth()->user()->isSuperadmin(), 403);
        abort_unless(array_key_exists($kunciMenu, DeadlinePekerjaan::daftarTahapKerja()), 404);

        $kunciAman = self::kunciAman($kunciMenu);
        $nilai = $this->tanggal[$kunciAman] ?? null;

        if (blank($nilai)) {
            $this->hapusDeadline($kunciMenu);

            return;
        }

        $this->validate([
            "tanggal.{$kunciAman}" => ['date'],
        ]);

        DeadlinePekerjaan::aturDeadline($kunciMenu, $this->tahun, $this->triwulan, $nilai, auth()->id());

        session()->flash('status', 'Deadline "'.DeadlinePekerjaan::daftarTahapKerja()[$kunciMenu]['label'].'" Triwulan '.$this->triwulan.' berhasil disimpan.');
    }

    public function hapusDeadline(string $kunciMenu): void
    {
        abort_unless(auth()->user()->isSuperadmin(), 403);
        abort_unless(array_key_exists($kunciMenu, DeadlinePekerjaan::daftarTahapKerja()), 404);

        DeadlinePekerjaan::hapusDeadline($kunciMenu, $this->tahun, $this->triwulan);
        $this->tanggal[self::kunciAman($kunciMenu)] = null;

        session()->flash('status', 'Deadline "'.DeadlinePekerjaan::daftarTahapKerja()[$kunciMenu]['label'].'" Triwulan '.$this->triwulan.' berhasil dikosongkan/direset.');
    }

    /**
     * Label kategori utk pesan flash ("Pendataan OPS"/"Pendataan BOSP") -
     * dipakai bersama oleh terapkanSemua() & resetSemua() (round kedua
     * belas) supaya konsisten dgn label yang tampil di Blade
     * ($kategoriLabel pada index.blade.php).
     */
    private static function labelKategori(string $kategori): string
    {
        return match ($kategori) {
            'ops' => 'Pendataan OPS',
            'bosp' => 'Pendataan BOSP',
            default => $kategori,
        };
    }

    /**
     * "Terapkan ke Semua" (round kesembilan bagian B, permintaan user
     * poin 3: "ketika superadmin pilih tanggal Deadline ada pilihan
     * untuk semua Tahap Kerja (Menu) langsung apabila tanggal deadline
     * nya sama") - menyalin SATU tanggal yang sama, HANYA untuk triwulan
     * yang sedang aktif ($this->triwulan). Aksi eksplisit (tombol
     * terpisah, bukan efek samping dari mengetik 1 kotak baris) supaya
     * Superadmin tidak tidak sengaja menimpa banyak baris sekaligus.
     *
     * Round kedua belas (permintaan user 2026-09-24): DIPISAH per
     * $kategori ('ops'/'bosp') - HANYA menyentuh tahap kerja kategori
     * itu, kategori lain (& triwulan lain) sama sekali tidak tersentuh.
     */
    public function terapkanSemua(string $kategori): void
    {
        abort_unless(auth()->user()->isSuperadmin(), 403);
        abort_unless(in_array($kategori, ['ops', 'bosp'], true), 404);

        $this->validate([
            "tanggalTerapkanSemua.{$kategori}" => ['required', 'date'],
        ], [], ["tanggalTerapkanSemua.{$kategori}" => 'Tanggal']);

        $tanggal = $this->tanggalTerapkanSemua[$kategori];
        $jumlah = 0;

        foreach (DeadlinePekerjaan::daftarTahapKerja() as $kunciMenu => $info) {
            if ($info['kategori'] !== $kategori) {
                continue;
            }

            DeadlinePekerjaan::aturDeadline($kunciMenu, $this->tahun, $this->triwulan, $tanggal, auth()->id());
            $this->tanggal[self::kunciAman($kunciMenu)] = $tanggal;
            $jumlah++;
        }

        session()->flash('status', 'Tanggal '.Carbon::parse($tanggal)->translatedFormat('d F Y')." berhasil diterapkan ke SEMUA {$jumlah} tahap kerja ".self::labelKategori($kategori).' untuk Triwulan '.$this->triwulan.'.');
    }

    /**
     * "Reset Semua" per kategori (round kedua belas, permintaan user
     * 2026-09-24: "buatkan option tombol reset untuk semua apabila admin
     * OPS/admin BOSP nya mau reset") - mengosongkan/menghapus SELURUH
     * deadline 1 kategori ('ops' ATAU 'bosp') sekaligus, HANYA untuk
     * triwulan yang sedang aktif. Kebalikan dari terapkanSemua() (hapus,
     * bukan isi), pola sama seperti hapusDeadline() per baris.
     *
     * Jawaban AskUserQuestion "Akses Reset Semua" -> "Hanya Superadmin
     * (Recommended)": tombol ini TETAP hanya bisa dipakai Superadmin,
     * SAMA seperti kelola deadline lainnya di halaman ini - Admin
     * OPS/Admin BOSP TIDAK mendapat akses baru apa pun lewat perubahan
     * ini (tetap read-only).
     */
    public function resetSemua(string $kategori): void
    {
        abort_unless(auth()->user()->isSuperadmin(), 403);
        abort_unless(in_array($kategori, ['ops', 'bosp'], true), 404);

        foreach (DeadlinePekerjaan::daftarTahapKerja() as $kunciMenu => $info) {
            if ($info['kategori'] !== $kategori) {
                continue;
            }

            DeadlinePekerjaan::hapusDeadline($kunciMenu, $this->tahun, $this->triwulan);
            $this->tanggal[self::kunciAman($kunciMenu)] = null;
        }

        session()->flash('status', 'Semua deadline '.self::labelKategori($kategori).' untuk Triwulan '.$this->triwulan.' berhasil dikosongkan/direset.');
    }

    public function render()
    {
        $tahunOptions = array_reverse(range(now()->year - 2, now()->year + 1));

        $hariIni = Carbon::today();

        // Status per tahap kerja untuk ditampilkan sebagai badge - dihitung
        // di sini (bukan lewat DeadlinePekerjaan::sudahLewat() berulang)
        // supaya tidak query database 16x per render.
        $status = [];
        foreach (DeadlinePekerjaan::daftarTahapKerja() as $kunciMenu => $info) {
            $tanggal = $this->tanggal[self::kunciAman($kunciMenu)] ?? null;

            if (blank($tanggal)) {
                $status[$kunciMenu] = 'belum_diatur';

                continue;
            }

            // Property $tanggal bisa saja SEMENTARA berisi teks yang belum
            // tervalidasi/tersimpan (mis. saat wire:model mengisi nilai baru
            // tapi simpanDeadline()/validate() belum/gagal berjalan) - Carbon
            // dibungkus try/catch supaya render() tidak ikut error kalau
            // isinya bukan tanggal valid, badge cukup dianggap "berjalan"
            // sementara (bukan krusial, murni tampilan badge status).
            try {
                $terlambat = Carbon::parse($tanggal)->lt($hariIni);
            } catch (\Throwable) {
                $terlambat = false;
            }

            $status[$kunciMenu] = $terlambat ? 'terlambat' : 'berjalan';
        }

        return view('livewire.timeline-pekerjaan.index', [
            'tahunOptions' => $tahunOptions,
            'tahapKerja' => DeadlinePekerjaan::daftarTahapKerja(),
            'status' => $status,
            'bolehKelola' => auth()->user()->isSuperadmin(),
        ]);
    }
}
