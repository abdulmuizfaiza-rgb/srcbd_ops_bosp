<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Deadline pekerjaan per tahap kerja (menu utama) + tahun + triwulan -
 * fondasi fitur "Timeline Pekerjaan" (round kedelapan, bagian B). Lihat
 * migration `create_deadline_pekerjaan_table` untuk konteks lengkap
 * permintaan user & jawaban AskUserQuestion terkait.
 *
 * Dimensi `triwulan` (1-4) ditambahkan round kesembilan bagian B
 * (permintaan user 2026-09-23, poin 3) - lihat migration
 * `add_triwulan_to_deadline_pekerjaan_table` untuk konteks lengkap.
 * SETIAP dari 16 tahap kerja (App\Models\DeadlinePekerjaan::daftarTahapKerja())
 * sekarang bisa punya SAMPAI 4 baris deadline berbeda per tahun (1 per
 * triwulan) - jawaban AskUserQuestion "Cakupan triwulan": "Semua 16
 * tahap kerja (Recommended)".
 */
#[Fillable(['tahun', 'kunci_menu', 'triwulan', 'tanggal_deadline', 'diatur_oleh'])]
class DeadlinePekerjaan extends Model
{
    protected $table = 'deadline_pekerjaan';

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'triwulan' => 'integer',
            'tanggal_deadline' => 'date',
        ];
    }

    public function pengatur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diatur_oleh');
    }

    /**
     * Registry SEMUA tahap kerja yang bisa diberi deadline - 3 dari
     * Pendataan OPS + 13 dari Pendataan BOSP (16 menu). SENGAJA
     * mengecualikan "Identitas OPS" & "Identitas Admin BOSP" (menu
     * onboarding, cuma diisi sekali di awal - jawaban AskUserQuestion
     * 2026-09-23 "Kecualikan menu identitas") dan "Unduhan" (murni
     * export/cetak hasil Lampiran, bukan pekerjaan input data).
     *
     * `kunci_menu` DISENGAJA sama persis dengan nama route menu terkait
     * (lihat routes/web.php) - dipakai baik sebagai kunci unik tabel
     * `deadline_pekerjaan` MAUPUN sebagai tautan langsung ke menu itu
     * dari halaman Timeline Pekerjaan (route($kunciMenu)).
     *
     * @return array<string, array{kategori: string, label: string}>
     */
    public static function daftarTahapKerja(): array
    {
        return [
            // Pendataan OPS (3 menu)
            'pendataan-ops.lampiran-2a' => ['kategori' => 'ops', 'label' => 'Lampiran 2a'],
            'pendataan-ops.lampiran-2b' => ['kategori' => 'ops', 'label' => 'Lampiran 2b'],
            'pendataan-ops.lampiran-2c' => ['kategori' => 'ops', 'label' => 'Lampiran 2c'],

            // Pendataan BOSP (13 menu)
            'pendataan-bosp.rekap-rkas' => ['kategori' => 'bosp', 'label' => 'Rekap RKAS Awal-Perubahan'],
            'pendataan-bosp.dana-bosp-tahap' => ['kategori' => 'bosp', 'label' => 'Dana BOSP Tahap 1 & 2'],
            'pendataan-bosp.penerimaan-honor-ptk' => ['kategori' => 'bosp', 'label' => 'Penerimaan Honor PTK'],
            'pendataan-bosp.langganan-daya-jasa' => ['kategori' => 'bosp', 'label' => 'Langganan Daya dan Jasa'],
            'pendataan-bosp.belanja-pemeliharaan-bangunan' => ['kategori' => 'bosp', 'label' => 'Belanja Pemeliharaan & Jasa Pemeliharaan Bangunan'],
            'pendataan-bosp.belanja-pemeliharaan-pc' => ['kategori' => 'bosp', 'label' => 'Belanja Pemeliharaan PC Komputer-Laptop-Printer dll'],
            'pendataan-bosp.biaya-pendaftaran-lomba' => ['kategori' => 'bosp', 'label' => 'Biaya Pendaftaran Lomba/Bimtek/Workshop'],
            'pendataan-bosp.belanja-honor-kegiatan' => ['kategori' => 'bosp', 'label' => 'Belanja Honor Kegiatan & Makan Minum Kegiatan & Perjalanan Dinas'],
            'pendataan-bosp.rincian-belanja-modal' => ['kategori' => 'bosp', 'label' => 'Rincian Belanja Modal & BMD'],
            'pendataan-bosp.rincian-belanja-barang-habis-pakai' => ['kategori' => 'bosp', 'label' => 'Rincian Belanja Barang Habis Pakai & Stock Opname'],
            'pendataan-bosp.pajak-bosp-reguler' => ['kategori' => 'bosp', 'label' => 'Pajak BOSP Reguler'],
            'pendataan-bosp.laporan-realisasi-bosp' => ['kategori' => 'bosp', 'label' => 'Laporan Realisasi BOSP (Form BPK)'],
            'pendataan-bosp.formulir-bos-k7' => ['kategori' => 'bosp', 'label' => 'Formulir BOS K7b & K7c'],
        ];
    }

    public static function untuk(string $kunciMenu, int $tahun, int $triwulan): ?self
    {
        return self::query()
            ->where('kunci_menu', $kunciMenu)
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->first();
    }

    /**
     * TRUE kalau tahap kerja+triwulan ini punya deadline TERATUR untuk
     * tahun ini DAN tanggalnya sudah lewat (sebelum hari ini - deadline
     * HARI INI SENDIRI belum dianggap lewat, masih boleh dikerjakan
     * sampai akhir hari). Belum diatur (tidak ada baris sama sekali) =
     * TIDAK dianggap lewat/tidak memblokir apapun - blokir HANYA aktif
     * kalau Superadmin memang mengatur deadline untuk
     * tahap kerja+tahun+triwulan itu.
     *
     * Dipakai sebagai guard penolakan input di 16 menu terkait (lihat
     * App\Livewire\Concerns\MenolakEditJikaLewatDeadline) MAUPUN untuk
     * badge status "Terlambat" di halaman Timeline Pekerjaan sendiri.
     */
    public static function sudahLewat(string $kunciMenu, int $tahun, int $triwulan): bool
    {
        $deadline = self::untuk($kunciMenu, $tahun, $triwulan);

        return $deadline !== null && $deadline->tanggal_deadline->lt(Carbon::today());
    }

    /**
     * Set/ubah tanggal deadline 1 tahap kerja untuk 1 tahun+triwulan -
     * HANYA boleh dipanggil oleh Superadmin (guard peran ada di
     * App\Livewire\TimelinePekerjaan\Index::simpanDeadline()).
     */
    public static function aturDeadline(string $kunciMenu, int $tahun, int $triwulan, string $tanggal, ?int $diaturOleh): self
    {
        return self::updateOrCreate(
            ['kunci_menu' => $kunciMenu, 'tahun' => $tahun, 'triwulan' => $triwulan],
            ['tanggal_deadline' => $tanggal, 'diatur_oleh' => $diaturOleh]
        );
    }

    /**
     * Reset/kosongkan deadline 1 tahap kerja untuk 1 tahun+triwulan -
     * menghapus barisnya sama sekali (bukan sekadar mengosongkan
     * tanggalnya) supaya sudahLewat() otomatis kembali FALSE & menu
     * terkait langsung terbuka lagi, sama seperti pola
     * VervalRealisasiBosp::resetTriwulan(). HANYA boleh dipanggil oleh
     * Superadmin (guard peran ada di
     * App\Livewire\TimelinePekerjaan\Index::hapusDeadline()).
     */
    public static function hapusDeadline(string $kunciMenu, int $tahun, int $triwulan): void
    {
        self::query()
            ->where('kunci_menu', $kunciMenu)
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->delete();
    }

    /**
     * Ringkasan tahap kerja yang SUDAH LEWAT deadline (1 kategori -
     * 'ops' atau 'bosp' - tahun berjalan, SEMUA triwulan 1-4 sekaligus,
     * BUKAN cuma triwulan yang sedang aktif) - dipakai popup info
     * timeline saat Admin OPS/Admin BOSP berhasil login (Round 9 Bagian
     * C, permintaan user 2026-09-23 poin 4: "informasi timeline ini
     * muncul... ketika admin bosp/admin OPS berhasil login").
     *
     * Definisi "lewat deadline" SENGAJA dibuat SAMA PERSIS dengan badge
     * status "Terlambat" yang sudah ada di halaman Timeline Pekerjaan
     * sendiri (App\Livewire\TimelinePekerjaan\Index::render()) - murni
     * tanggal_deadline < hari ini, TIDAK mengecek apakah datanya sudah
     * diisi/selesai atau belum. Ini KEPUTUSAN TEKNIS supaya konsisten
     * dengan konsep yang sudah ada di aplikasi (bukan aturan bisnis
     * baru) - TIDAK ada konsep hari/jangka waktu "mendekati deadline"
     * di manapun di aplikasi ini, jadi itu SENGAJA tidak dipakai di
     * sini juga supaya tidak menciptakan aturan baru yang belum pernah
     * diminta user. Semua triwulan (bukan cuma triwulan aktif) dicek
     * sekaligus supaya deadline TW1/TW2 yang terlewat tidak "hilang"
     * dari ringkasan begitu triwulan berjalan pindah ke TW3/TW4.
     *
     * NULL kalau tidak ada satupun yang terlambat - popup DILEWATI SAJA
     * (jawaban AskUserQuestion "Kondisi kosong" -> "Dilewati saja
     * (Recommended)"). Dipanggil lewat ringkasanPopupLoginUntukUserSaatIni()
     * di bawah - lihat docblock method itu untuk alur lengkapnya.
     *
     * "Paling mendesak" = yang PALING LAMA terlambat (tanggal deadline
     * paling awal) di antara yang terlambat, bukan yang paling baru -
     * tafsiran wajar untuk "paling mendesak" (paling lama menunggak).
     *
     * @return array{jumlah: int, labelPalingMendesak: string, triwulanPalingMendesak: int, tanggalPalingMendesak: string}|null
     */
    public static function ringkasanTerlambatUntukPopupLogin(string $kategori): ?array
    {
        $kunciKategoriIni = collect(self::daftarTahapKerja())
            ->filter(fn (array $info): bool => $info['kategori'] === $kategori)
            ->keys();

        $terlambat = self::query()
            ->whereIn('kunci_menu', $kunciKategoriIni)
            ->where('tahun', now()->year)
            ->whereDate('tanggal_deadline', '<', Carbon::today())
            ->orderBy('tanggal_deadline')
            ->get();

        if ($terlambat->isEmpty()) {
            return null;
        }

        $palingMendesak = $terlambat->first();
        $labelPalingMendesak = self::daftarTahapKerja()[$palingMendesak->kunci_menu]['label'];

        return [
            'jumlah' => $terlambat->count(),
            'labelPalingMendesak' => $labelPalingMendesak,
            'triwulanPalingMendesak' => $palingMendesak->triwulan,
            'tanggalPalingMendesak' => $palingMendesak->tanggal_deadline->translatedFormat('d F Y'),
        ];
    }

    /**
     * Pembungkus ringkasanTerlambatUntukPopupLogin() untuk user yang
     * SEDANG login saat ini - dipanggil dari
     * resources/views/layouts/app.blade.php lewat SATU baris
     * "@php(...)" (bentuk pendek 1 baris, BUKAN blok "@php ... @endphp")
     * supaya tidak bentrok dengan baris "@php($tampilanHalaman = ...)"
     * yang sudah ada lebih dulu di file yang sama - kalau bentuk pendek
     * (parentheses) dicampur dengan bentuk blok pada file Blade yang
     * sama, compiler Blade salah mengenali pasangan pembuka/penutupnya
     * (terbukti lewat pengujian isolasi terpisah, bukan asumsi) sehingga
     * bagian di antara keduanya - termasuk komentar Blade - ikut
     * "tertelan" mentah-mentah dan merusak seluruh halaman (500 error).
     * Logika multi-baris (cek login, cek flag session, cek
     * kategori peran) makanya dipindah ke sini (1 method, 1 pemanggilan)
     * dan bukan ditulis langsung di Blade.
     *
     * "Baru saja login" ditandai lewat session flash
     * 'tampilkan_popup_timeline_login' (diset di
     * resources/views/livewire/pages/auth/login.blade.php::login()) -
     * session()->pull() di sini membaca SEKALIGUS menghapusnya supaya
     * popup benar-benar cuma tampil 1x tepat setelah login, bukan di
     * setiap kunjungan halaman berikutnya (layout ini dirender ulang
     * penuh dari server pada setiap navigasi, termasuk lewat
     * wire:navigate).
     *
     * Superadmin SENGAJA tidak dicek (permintaan eksplisit hanya "admin
     * bosp/admin OPS").
     */
    public static function ringkasanPopupLoginUntukUserSaatIni(): ?array
    {
        if (! auth()->check() || ! session()->pull('tampilkan_popup_timeline_login', false)) {
            return null;
        }

        $user = auth()->user();

        $kategori = match (true) {
            $user->isAdminOps() => 'ops',
            $user->isAdminBosp() => 'bosp',
            default => null,
        };

        return $kategori !== null ? self::ringkasanTerlambatUntukPopupLogin($kategori) : null;
    }
}
