<?php

namespace App\Livewire\TahunAnggaran;

use App\Models\PermintaanAksesTahun;
use App\Models\TahunAnggaranSetting;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Menu baru "Tahun Anggaran" (permintaan user 2026-10-24, dikonfirmasi
 * lewat AskUserQuestion: menu baru di sidebar, bukan tab di menu
 * Pengguna). Isinya BERBEDA TOTAL menurut level akses (lihat
 * bolehKelolaSemua()):
 *
 * - Superadmin: atur Tahun Anggaran Aktif (App\Models\TahunAnggaranSetting)
 *   + daftar SEMUA permintaan akses tahun dari Admin OPS/Admin BOSP
 *   (setujui/tolak/cabut).
 * - Admin OPS/Admin BOSP: lihat Tahun Anggaran Aktif saat ini & tahun
 *   yang sedang mereka akses (session 'tahun_sesi', diisi saat login -
 *   lihat resources/views/livewire/pages/auth/pilih-tahun.blade.php) +
 *   riwayat permintaan mereka sendiri + form ajukan permintaan baru.
 *
 * Diakses lewat Gate 'akses-tahun-anggaran' (lihat AppServiceProvider,
 * SENGAJA true untuk SEMUA level - beda dari 'akses-pengguna' yang
 * Superadmin-only - karena Admin OPS/Admin BOSP juga perlu membuka menu
 * ini untuk mengajukan permintaan).
 */
#[Layout('layouts.app')]
#[Title('Tahun Anggaran')]
class Index extends Component
{
    /**
     * Superadmin: nilai baru Tahun Anggaran Aktif yang sedang diedit di
     * form (diisi dari TahunAnggaranSetting::current() saat mount()).
     */
    public int $tahunAktifBaru;

    /**
     * Superadmin: filter status pada daftar permintaan ('menunggu',
     * 'disetujui', 'ditolak', atau 'semua').
     */
    public string $filterStatus = PermintaanAksesTahun::STATUS_MENUNGGU;

    /**
     * Admin OPS/Admin BOSP: tahun yang dipilih di form "Ajukan
     * Permintaan Akses Tahun Sebelumnya".
     */
    public ?int $tahunDiajukan = null;

    public function mount(): void
    {
        $this->tahunAktifBaru = TahunAnggaranSetting::current()->tahun_aktif;
    }

    protected function bolehKelolaSemua(): bool
    {
        return auth()->user()->isSuperadmin();
    }

    /**
     * Superadmin: simpan Tahun Anggaran Aktif yang baru. Perubahan ini
     * HANYA berlaku untuk login BARU setelah ini - akun yang sedang
     * login (session 'tahun_sesi' sudah terbentuk) TIDAK ikut berubah
     * sampai mereka logout & login ulang (sesuai desain "dikunci total
     * ke tahun sesi login" yang sudah dikonfirmasi user).
     */
    public function simpanTahunAktif(): void
    {
        if (! $this->bolehKelolaSemua()) {
            return;
        }

        $this->validate([
            'tahunAktifBaru' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        TahunAnggaranSetting::current()->update(['tahun_aktif' => $this->tahunAktifBaru]);

        session()->flash('status', 'Tahun Anggaran Aktif berhasil diubah menjadi '.$this->tahunAktifBaru.'. Perubahan ini berlaku untuk LOGIN BARU setelah ini - akun yang sedang login saat ini belum ikut berubah sampai logout & login ulang.');
    }

    /**
     * Superadmin: setujui 1 permintaan (status jadi 'disetujui', berlaku
     * PERMANEN sampai dicabut lewat tolakAtauCabut() di bawah).
     */
    public function setujui(int $permintaanId): void
    {
        if (! $this->bolehKelolaSemua()) {
            return;
        }

        PermintaanAksesTahun::whereKey($permintaanId)->update([
            'status' => PermintaanAksesTahun::STATUS_DISETUJUI,
            'diproses_oleh' => auth()->id(),
            'diproses_pada' => now(),
        ]);
    }

    /**
     * Superadmin: tolak permintaan yang masih 'menunggu', ATAU cabut
     * izin yang sebelumnya 'disetujui' - keduanya memakai status yang
     * sama ('ditolak', lihat docblock migration create_permintaan_akses_
     * tahun_table untuk alasannya) sehingga 1 tombol & 1 method ini
     * cukup untuk kedua kasus (tombolnya di Blade berganti label
     * "Tolak"/"Cabut Izin" tergantung status SAAT INI, tapi actionnya
     * sama).
     */
    public function tolakAtauCabut(int $permintaanId): void
    {
        if (! $this->bolehKelolaSemua()) {
            return;
        }

        PermintaanAksesTahun::whereKey($permintaanId)->update([
            'status' => PermintaanAksesTahun::STATUS_DITOLAK,
            'diproses_oleh' => auth()->id(),
            'diproses_pada' => now(),
        ]);
    }

    /**
     * Admin OPS/Admin BOSP: ajukan permintaan akses tahun sebelumnya.
     * Kalau sebelumnya pernah mengajukan tahun yang SAMA & ditolak,
     * baris yang SAMA dipakai ulang (status kembali ke 'menunggu') -
     * bukan membuat baris baru (konstrain unique user_id+tahun di
     * migration juga menegakkan ini).
     */
    public function ajukanPermintaan(): void
    {
        $user = auth()->user();

        if (! in_array($user->level_akses, [User::LEVEL_ADMIN_OPS, User::LEVEL_ADMIN_BOSP], true)) {
            return;
        }

        $this->validate([
            'tahunDiajukan' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $tahunAktif = TahunAnggaranSetting::current()->tahun_aktif;

        if ($this->tahunDiajukan === $tahunAktif) {
            $this->addError('tahunDiajukan', 'Tahun ini adalah Tahun Anggaran Aktif - sudah otomatis bisa diakses tanpa perlu izin.');

            return;
        }

        $sudahDisetujui = PermintaanAksesTahun::where('user_id', $user->id)
            ->where('tahun', $this->tahunDiajukan)
            ->where('status', PermintaanAksesTahun::STATUS_DISETUJUI)
            ->exists();

        if ($sudahDisetujui) {
            $this->addError('tahunDiajukan', 'Anda sudah memiliki izin untuk tahun ini.');

            return;
        }

        PermintaanAksesTahun::updateOrCreate(
            ['user_id' => $user->id, 'tahun' => $this->tahunDiajukan],
            [
                'status' => PermintaanAksesTahun::STATUS_MENUNGGU,
                'diminta_pada' => now(),
                'diproses_oleh' => null,
                'diproses_pada' => null,
            ]
        );

        $this->tahunDiajukan = null;

        session()->flash('status', 'Permintaan berhasil diajukan, menunggu persetujuan Superadmin. Tahun ini akan muncul sebagai pilihan di form login setelah disetujui.');
    }

    public function render()
    {
        $user = auth()->user();
        $bolehKelolaSemua = $this->bolehKelolaSemua();
        $tahunAktif = TahunAnggaranSetting::current()->tahun_aktif;

        $daftarPermintaan = collect();
        $riwayatSaya = collect();
        $tahunOpsiAjukan = [];

        if ($bolehKelolaSemua) {
            $query = PermintaanAksesTahun::with(['user.profilSekolah'])
                // Baris 'menunggu' selalu tampil paling atas - aman utk
                // PostgreSQL (satu-satunya database yang dipakai aplikasi
                // ini): hasil "status = 'menunggu'" adalah boolean, dan
                // PostgreSQL mengurutkan true > false, jadi DESC
                // menempatkan baris 'menunggu' (true) lebih dulu.
                ->orderByRaw("(status = 'menunggu') desc")
                ->orderByDesc('diminta_pada');

            if ($this->filterStatus !== 'semua') {
                $query->where('status', $this->filterStatus);
            }

            $daftarPermintaan = $query->get();
        } else {
            $riwayatSaya = PermintaanAksesTahun::where('user_id', $user->id)
                ->orderByDesc('diminta_pada')
                ->get();

            // Tahun yang TIDAK BOLEH diajukan lagi lewat dropdown = yang
            // sedang 'menunggu' atau sudah 'disetujui' - tahun 'ditolak'
            // SENGAJA tetap ditawarkan lagi (supaya bisa diajukan ulang,
            // lihat docblock ajukanPermintaan() di atas).
            $tahunTidakBisaDiajukanLagi = $riwayatSaya
                ->where('status', '!=', PermintaanAksesTahun::STATUS_DITOLAK)
                ->pluck('tahun')
                ->map(fn ($tahun) => (int) $tahun)
                ->all();

            foreach (range($tahunAktif - 1, $tahunAktif - 3) as $tahunOpsi) {
                if (! in_array($tahunOpsi, $tahunTidakBisaDiajukanLagi, true)) {
                    $tahunOpsiAjukan[] = $tahunOpsi;
                }
            }
        }

        return view('livewire.tahun-anggaran.index', [
            'bolehKelolaSemua' => $bolehKelolaSemua,
            'tahunAktif' => $tahunAktif,
            'tahunSesiSaya' => session('tahun_sesi'),
            'daftarPermintaan' => $daftarPermintaan,
            'riwayatSaya' => $riwayatSaya,
            'tahunOpsiAjukan' => $tahunOpsiAjukan,
        ]);
    }
}
