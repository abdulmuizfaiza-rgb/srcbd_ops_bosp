<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\ProfilSekolah;
use App\Models\SyncTombstone;
use App\Support\RegistriModulSekolah;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Inti fitur "Sinkronisasi dan Tarik Data" (permintaan user 2026-10-14
 * poin 3). SATU class ini dipakai untuk KEDUA arah & KEDUA peran:
 * - LOKAL mengirim (Sinkronisasi): panggil kumpulkanDataKeluar() di
 *   Lokal -> kirim lewat HTTP (App\Services\SyncClient) -> ONLINE
 *   menerima lewat App\Http\Controllers\Api\SyncPushController ->
 *   panggil terapkanDataMasuk() di Online.
 * - LOKAL menarik (Tarik Data): ONLINE melayani lewat App\Http\
 *   Controllers\Api\SyncPullController -> panggil kumpulkanDataKeluar()
 *   di Online -> kirim sebagai response JSON -> LOKAL menerima lewat
 *   App\Services\SyncClient -> panggil terapkanDataMasuk() di Lokal.
 *
 * KEPUTUSAN DESAIN PENTING - dibaca sebelum mengubah apa pun di sini:
 *
 * 1. SENGAJA memakai query builder DB::table() langsung (BUKAN Eloquent
 *    create()/update()/delete()) untuk SEMUA operasi tulis ke 24 tabel
 *    yang disinkronkan. Alasan: (a) timestamp created_at/updated_at
 *    ASLI dari sisi pengirim HARUS dipertahankan apa adanya (bukan
 *    ditimpa otomatis oleh Eloquent jadi "sekarang") - updated_at
 *    JUSTRU adalah dasar pembanding "mana yang lebih baru" untuk
 *    sinkronisasi berikutnya (jawaban AskUserQuestion: "gabung otomatis
 *    berdasarkan waktu update"), (b) otomatis TIDAK memicu event
 *    creating/deleted di App\Models\Concerns\Syncable (tidak perlu
 *    pengisian sync_uuid baru atau pencatatan tombstone baru untuk
 *    data yang JUSTRU DATANG dari sinkronisasi itu sendiri).
 *
 * 2. profil_sekolah_id (ada di hampir semua tabel kecuali profil_sekolah
 *    sendiri) TIDAK BISA dikirim mentah - ID itu auto-increment LOKAL
 *    pada database masing-masing, sekolah yang sama bisa punya ID
 *    berbeda di Lokal vs Online. Solusi: kumpulkanDataKeluar() SELALU
 *    mengganti profil_sekolah_id dengan kolom sementara `_sekolah_npsn`
 *    (NPSN sekolah itu, dari JOIN ke profil_sekolah) - lalu
 *    terapkanDataMasuk() MENCARI BALIK profil_sekolah_id versi dirinya
 *    SENDIRI dari NPSN itu. Supaya ini selalu berhasil, tabel
 *    'profil_sekolah' SELALU diterapkan LEBIH DULU, sebelum 23 tabel
 *    lain, di terapkanDataMasuk().
 *
 * 3. Kolom apa pun yang namanya berakhiran '_by', '_oleh', atau
 *    '_oleh_id' (misal created_by, diverval_oleh) SELALU dibuang dari
 *    payload - kolom ini menunjuk ke users.id, yang JUGA auto-increment
 *    lokal yang tidak nyambung antar instalasi, dan sekadar metadata
 *    "siapa yang input", bukan data bisnis inti - dibuang saja daripada
 *    berisiko salah atribusi ke orang yang salah atau gagal karena FK
 *    constraint (user itu tidak ada di sisi lain).
 *
 * 4. Kolom file (logo_sekolah, logo_pemda, foto_ops, sk_ops - lihat
 *    RegistriModulSekolah::tabelDisinkronkan()[...]['kolom_dikecualikan'])
 *    SELALU dibuang - file fisiknya TIDAK ikut dikirim (di luar lingkup
 *    permintaan user), mengirim path-nya saja akan menghasilkan path
 *    rusak/menunjuk ke file yang tidak ada di sisi tujuan.
 *
 * 5. SEBELUM menerapkan apa pun yang masuk, SELALU membuat 1 backup
 *    otomatis dulu dari kondisi database SAAT INI (jaring pengaman,
 *    pola identik dengan App\Services\RestoreService) - supaya kalau
 *    ternyata hasilnya tidak sesuai harapan, Superadmin bisa Restore
 *    balik ke kondisi sebelum sinkronisasi/tarik data itu dijalankan.
 *
 * 6. Satu kali jalan = SELALU kirim/tarik SEMUA data (bukan hanya yang
 *    berubah sejak terakhir kali) - lebih sederhana & lebih aman untuk
 *    dipastikan benar dibanding sinkronisasi bertahap/inkremental,
 *    dengan konsekuensi semakin banyak data semakin lama prosesnya
 *    (wajar untuk aplikasi 1 wilayah/kecamatan, bukan skala nasional).
 *
 * 7. PENTING: jam di server Lokal & server Online HARUS kurang lebih
 *    sama (aktifkan sinkronisasi waktu/NTP di kedua sisi) - seluruh
 *    logika "mana yang lebih baru" di sini membandingkan updated_at
 *    APA ADANYA, tanpa toleransi selisih jam.
 *
 * 8. "Tarik Data Pengguna" (BARU 2026-10-16, permintaan user: "saya
 *    ingin bisa menarik data pengguna ... baik sebagai superadmin,
 *    admin ops, admin bosp") adalah jalur TERPISAH dari 7 poin di atas
 *    (kumpulkanPenggunaKeluar()/terapkanPenggunaMasuk()/
 *    terapkanTabelPengguna() di bagian bawah file ini) - SATU ARAH
 *    SAJA (Online -> Lokal, TIDAK ikut terkirim saat tombol
 *    "Sinkronisasi" ditekan - dikonfirmasi AskUserQuestion), tabel
 *    `users` SENGAJA TIDAK didaftarkan di App\Support\RegistriModulSekolah
 *    supaya kumpulkanDataKeluar()/terapkanDataMasuk() generik di atas
 *    (dipakai KEDUA arah) tidak pernah menyentuhnya. Kolom Google
 *    Authenticator (terenkripsi per-instalasi) & remember_token SELALU
 *    dibuang, kata sandi (hash bcrypt, bukan terenkripsi APP_KEY) BOLEH
 *    ikut tertimpa - lihat docblock terapkanTabelPengguna() utk alasan
 *    lengkap tiap keputusan.
 */
class SyncEngine
{
    public function __construct(protected BackupService $backupService) {}

    /**
     * Kolom yang SELALU dibuang dari payload (lihat poin 3 & 4 docblock
     * class), dihitung per tabel dari konfigurasi + pola nama kolom.
     */
    protected function kolomDibuang(array $konfigurasiTabel, array $semuaKolom): array
    {
        $dibuang = $konfigurasiTabel['kolom_dikecualikan'];

        foreach ($semuaKolom as $kolom) {
            if ($kolom === 'created_by' || str_ends_with($kolom, '_oleh') || str_ends_with($kolom, '_oleh_id')) {
                $dibuang[] = $kolom;
            }
        }

        return array_unique($dibuang);
    }

    /**
     * Kumpulkan SELURUH data (24 tabel + tombstone) dari instalasi ini
     * untuk dikirim (Sinkronisasi) atau disajikan (dipanggil saat
     * dipanggil lewat Tarik Data oleh sisi lain).
     */
    public function kumpulkanDataKeluar(): array
    {
        $payload = ['tabel' => [], 'tombstones' => []];

        foreach (RegistriModulSekolah::tabelDisinkronkan() as $namaTabel => $konfigurasi) {
            if ($namaTabel === 'profil_sekolah') {
                $baris = DB::table('profil_sekolah')->get()->map(fn ($r) => (array) $r)->all();
            } else {
                $baris = DB::table($namaTabel)
                    ->leftJoin('profil_sekolah', 'profil_sekolah.id', '=', $namaTabel.'.profil_sekolah_id')
                    ->select($namaTabel.'.*', 'profil_sekolah.npsn as _sekolah_npsn')
                    ->get()
                    ->map(fn ($r) => (array) $r)
                    ->all();
            }

            $kolomDibuang = $baris === [] ? $konfigurasi['kolom_dikecualikan'] : $this->kolomDibuang($konfigurasi, array_keys($baris[0]));

            $payload['tabel'][$namaTabel] = array_map(function (array $satu) use ($kolomDibuang, $namaTabel) {
                foreach ($kolomDibuang as $kolom) {
                    unset($satu[$kolom]);
                }
                if ($namaTabel !== 'profil_sekolah') {
                    unset($satu['profil_sekolah_id']);
                }

                return $satu;
            }, $baris);
        }

        $payload['tombstones'] = SyncTombstone::query()->get()
            ->map(fn ($t) => [
                'tabel' => $t->tabel,
                'kunci' => $t->kunci,
                'dihapus_pada' => $t->dihapus_pada->toIso8601String(),
            ])
            ->all();

        return $payload;
    }

    /**
     * Terapkan payload masuk (dari Sinkronisasi yang dikirim Lokal, ATAU
     * dari Tarik Data yang diambil dari Online) ke database INSTALASI
     * INI. Selalu membuat backup pengaman dulu (lihat poin 5 docblock
     * class). Mengembalikan ringkasan jumlah baris yang berubah per
     * tabel + id backup pengaman yang dibuat.
     */
    public function terapkanDataMasuk(array $payload, ?int $pelakuId, string $labelUntukBackup): array
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $backupPengaman = $this->backupService->buat($pelakuId);
        $backupPengaman->forceFill([
            'catatan' => 'Backup otomatis (jaring pengaman) sebelum '.$labelUntukBackup.'.',
        ])->save();

        $ringkasan = [];

        try {
            DB::transaction(function () use ($payload, &$ringkasan) {
                $konfigurasiSemua = RegistriModulSekolah::tabelDisinkronkan();

                // 1. Profil Sekolah SELALU lebih dulu (lihat poin 2
                // docblock class - sekolah baru harus sudah ada di sisi
                // ini sebelum baris anak-anaknya diterapkan).
                if (isset($payload['tabel']['profil_sekolah'])) {
                    $ringkasan['profil_sekolah'] = $this->terapkanTabelProfilSekolah($payload['tabel']['profil_sekolah']);
                }

                $pemetaanNpsn = $this->pemetaanNpsnKeId();

                foreach ($payload['tabel'] ?? [] as $namaTabel => $daftarBaris) {
                    if ($namaTabel === 'profil_sekolah') {
                        continue;
                    }

                    $konfigurasi = $konfigurasiSemua[$namaTabel] ?? null;
                    if ($konfigurasi === null) {
                        continue;
                    }

                    $ringkasan[$namaTabel] = $this->terapkanTabelAnak($namaTabel, $konfigurasi, $daftarBaris, $pemetaanNpsn);
                }

                // 2. Tombstone (penghapusan yang ikut disinkronkan).
                foreach ($this->terapkanTombstone($payload['tombstones'] ?? [], $konfigurasiSemua, $pemetaanNpsn) as $namaTabel => $jumlahDihapus) {
                    $ringkasan[$namaTabel]['dihapus'] = ($ringkasan[$namaTabel]['dihapus'] ?? 0) + $jumlahDihapus;
                }
            });
        } catch (Throwable $e) {
            throw new RuntimeException('Gagal menerapkan data sinkronisasi: '.$e->getMessage(), previous: $e);
        }

        return ['ringkasan' => $ringkasan, 'backup_pengaman_id' => $backupPengaman->id];
    }

    protected function pemetaanNpsnKeId(): array
    {
        return DB::table('profil_sekolah')->whereNotNull('npsn')->pluck('id', 'npsn')->all();
    }

    protected function terapkanTabelProfilSekolah(array $daftarBaris): array
    {
        $ditambah = 0;
        $diupdate = 0;
        $dilewati = 0;

        foreach ($daftarBaris as $baris) {
            unset($baris['logo_sekolah'], $baris['logo_pemda'], $baris['id']);

            if (empty($baris['npsn'])) {
                $dilewati++;

                continue;
            }

            $existing = DB::table('profil_sekolah')->where('npsn', $baris['npsn'])->first();

            if ($existing === null) {
                DB::table('profil_sekolah')->insert($baris);
                $ditambah++;
            } elseif ($this->lebihBaru($baris, $existing)) {
                DB::table('profil_sekolah')->where('npsn', $baris['npsn'])->update($baris);
                $diupdate++;
            } else {
                $dilewati++;
            }
        }

        return ['ditambah' => $ditambah, 'diupdate' => $diupdate, 'dilewati' => $dilewati];
    }

    protected function terapkanTabelAnak(string $namaTabel, array $konfigurasi, array $daftarBaris, array $pemetaanNpsn): array
    {
        $kolomKunci = $konfigurasi['kunci'] ?? ['sync_uuid'];
        $ditambah = 0;
        $diupdate = 0;
        $dilewati = 0;

        foreach ($daftarBaris as $baris) {
            unset($baris['id']);

            foreach ($konfigurasi['kolom_dikecualikan'] as $kolom) {
                unset($baris[$kolom]);
            }

            $npsn = $baris['_sekolah_npsn'] ?? null;
            unset($baris['_sekolah_npsn']);

            if ($npsn === null || ! isset($pemetaanNpsn[$npsn])) {
                // Sekolahnya tidak dikenal di sisi ini (NPSN kosong/tidak
                // cocok dengan sekolah mana pun) - lewati baris ini demi
                // keamanan, jangan menebak-nebak sekolah mana yang
                // dimaksud.
                $dilewati++;

                continue;
            }

            $baris['profil_sekolah_id'] = $pemetaanNpsn[$npsn];

            $nilaiKunci = [];
            $kunciLengkap = true;
            foreach ($kolomKunci as $kolom) {
                if (! array_key_exists($kolom, $baris) || $baris[$kolom] === null) {
                    $kunciLengkap = false;

                    break;
                }
                $nilaiKunci[$kolom] = $baris[$kolom];
            }

            if (! $kunciLengkap) {
                $dilewati++;

                continue;
            }

            $existing = DB::table($namaTabel)->where($nilaiKunci)->first();

            if ($existing === null) {
                DB::table($namaTabel)->insert($baris);
                $ditambah++;
            } elseif ($this->lebihBaru($baris, $existing)) {
                DB::table($namaTabel)->where($nilaiKunci)->update($baris);
                $diupdate++;
            } else {
                $dilewati++;
            }
        }

        return ['ditambah' => $ditambah, 'diupdate' => $diupdate, 'dilewati' => $dilewati];
    }

    /**
     * Terapkan tombstone - cari & hapus baris yang cocok (kalau ADA)
     * di tabel masing-masing. Untuk tabel yang kunci alaminya mengandung
     * profil_sekolah_id, App\Models\Concerns\Syncable SUDAH mengganti
     * profil_sekolah_id dengan `_sekolah_npsn` saat tombstone dibuat -
     * di sini diterjemahkan balik ke profil_sekolah_id versi SISI INI
     * lewat $pemetaanNpsn (sama seperti terapkanTabelAnak()). Kalau
     * NPSN-nya tidak dikenal sama sekali di sisi ini, tombstone itu
     * dilewati (lebih aman melewatkan daripada menghapus baris yang
     * salah/menebak-nebak).
     *
     * @return array<string, int>
     */
    protected function terapkanTombstone(array $tombstones, array $konfigurasiSemua, array $pemetaanNpsn): array
    {
        $dihapusPerTabel = [];

        foreach ($tombstones as $t) {
            $namaTabel = $t['tabel'] ?? null;
            $kunci = $t['kunci'] ?? [];
            if ($namaTabel === null || $kunci === [] || ! isset($konfigurasiSemua[$namaTabel])) {
                continue;
            }

            if (array_key_exists('_sekolah_npsn', $kunci)) {
                $npsn = $kunci['_sekolah_npsn'];
                unset($kunci['_sekolah_npsn']);

                if ($npsn === null || ! isset($pemetaanNpsn[$npsn])) {
                    continue;
                }

                $kunci['profil_sekolah_id'] = $pemetaanNpsn[$npsn];
            }

            $dihapus = DB::table($namaTabel)->where($kunci)->delete();
            if ($dihapus > 0) {
                $dihapusPerTabel[$namaTabel] = ($dihapusPerTabel[$namaTabel] ?? 0) + $dihapus;
            }
        }

        return $dihapusPerTabel;
    }

    /**
     * Bandingkan updated_at baris masuk vs baris yang sudah ada -
     * TRUE kalau baris masuk LEBIH BARU (harus menimpa).
     */
    protected function lebihBaru(array $barisMasuk, object $existing): bool
    {
        $updatedMasuk = $barisMasuk['updated_at'] ?? null;
        $updatedAda = $existing->updated_at ?? null;

        if ($updatedMasuk === null) {
            return false;
        }
        if ($updatedAda === null) {
            return true;
        }

        return Carbon::parse($updatedMasuk)->gt(Carbon::parse($updatedAda));
    }

    /**
     * Kumpulkan SELURUH akun Pengguna (Superadmin/Admin OPS/Admin BOSP)
     * dari instalasi ini, utk disajikan saat Lokal menekan "Tarik Data
     * Pengguna" (BARU 2026-10-16) - lihat poin 8 docblock class.
     * profil_sekolah_id diganti `_sekolah_npsn` (sama pola dgn
     * kumpulkanDataKeluar(), null utk akun Superadmin yg tidak terikat
     * sekolah manapun). Kolom Google Authenticator & remember_token
     * SELALU dibuang - lihat docblock terapkanTabelPengguna().
     */
    public function kumpulkanPenggunaKeluar(): array
    {
        $baris = DB::table('users')
            ->leftJoin('profil_sekolah', 'profil_sekolah.id', '=', 'users.profil_sekolah_id')
            ->select('users.*', 'profil_sekolah.npsn as _sekolah_npsn')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        $kolomDibuang = [
            'id',
            'profil_sekolah_id',
            'remember_token',
            'google2fa_secret',
            'google2fa_aktif_at',
            'google2fa_reset_diminta_at',
        ];

        return [
            'pengguna' => array_map(function (array $satu) use ($kolomDibuang) {
                foreach ($kolomDibuang as $kolom) {
                    unset($satu[$kolom]);
                }

                return $satu;
            }, $baris),
        ];
    }

    /**
     * Terapkan payload akun Pengguna yang ditarik dari Online ke
     * database Lokal ini (BARU 2026-10-16) - selalu membuat backup
     * pengaman dulu (pola sama dgn terapkanDataMasuk()), TAPI terpisah
     * dari transaksi/ringkasan 24 tabel data sekolah (lihat poin 8
     * docblock class: "Tarik Data Pengguna" adalah tombol & aksi
     * TERSENDIRI, bukan bagian dari tombol "Tarik Data" yang sudah ada).
     */
    public function terapkanPenggunaMasuk(array $daftarPengguna, ?int $pelakuId, string $labelUntukBackup): array
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $backupPengaman = $this->backupService->buat($pelakuId);
        $backupPengaman->forceFill([
            'catatan' => 'Backup otomatis (jaring pengaman) sebelum '.$labelUntukBackup.'.',
        ])->save();

        $ringkasan = [];

        try {
            DB::transaction(function () use ($daftarPengguna, &$ringkasan) {
                $ringkasan['users'] = $this->terapkanTabelPengguna($daftarPengguna, $this->pemetaanNpsnKeId());
            });
        } catch (Throwable $e) {
            throw new RuntimeException('Gagal menerapkan data akun Pengguna: '.$e->getMessage(), previous: $e);
        }

        return ['ringkasan' => $ringkasan, 'backup_pengaman_id' => $backupPengaman->id];
    }

    /**
     * Terapkan payload akun Pengguna (username/email/password/
     * level_akses/dst) dari Online ke tabel `users` Lokal - method
     * KHUSUS (bukan lewat terapkanTabelAnak() generik seperti 23 tabel
     * RegistriModulSekolah lain), karena:
     *
     * - Dicocokkan lewat `username` (kolom unik & SELALU terisi utk
     *   SEMUA jenis akun - Superadmin/Admin OPS/Admin BOSP - BUKAN
     *   sync_uuid/kunci komposit spt tabel lain).
     * - profil_sekolah_id BOLEH kosong (akun Superadmin tidak terikat
     *   sekolah manapun) - terapkanTabelAnak() generik justru MELEWATI
     *   baris yang NPSN-nya kosong, yang kalau dipakai apa adanya akan
     *   membuang SEMUA akun Superadmin (bertentangan dgn permintaan
     *   user: "baik sebagai superadmin, admin ops, admin bosp").
     * - Kolom Google Authenticator (google2fa_secret dkk) & remember_token
     *   SENGAJA TIDAK PERNAH ada di payload sama sekali (sudah dibuang
     *   sejak kumpulkanPenggunaKeluar()) - dikonfirmasi AskUserQuestion
     *   2026-10-16: "tiap instalasi atur 2FA sendiri-sendiri", krn
     *   google2fa_secret dienkripsi pakai APP_KEY yang BEDA tiap
     *   instalasi - kalau disalin mentah, status 2FA akun itu akan
     *   GAGAL dibaca (exception) di sisi penerima.
     *
     * `password` & `kata_sandi_pemulihan_hash` BOLEH ikut tertimpa
     * (dikonfirmasi AskUserQuestion 2026-10-16: "Timpa dengan data
     * Online, data yg lebih baru menang") - AMAN krn kedua kolom itu
     * di-hash pakai algoritma hashing bawaan Laravel (bcrypt, cast
     * 'hashed' di App\Models\User), BUKAN dienkripsi pakai APP_KEY -
     * hash-nya tetap valid dipakai login di instalasi manapun.
     */
    protected function terapkanTabelPengguna(array $daftarBaris, array $pemetaanNpsn): array
    {
        $ditambah = 0;
        $diupdate = 0;
        $dilewati = 0;

        foreach ($daftarBaris as $baris) {
            unset(
                $baris['id'],
                $baris['remember_token'],
                $baris['google2fa_secret'],
                $baris['google2fa_aktif_at'],
                $baris['google2fa_reset_diminta_at'],
            );

            if (empty($baris['username'])) {
                $dilewati++;

                continue;
            }

            $npsn = $baris['_sekolah_npsn'] ?? null;
            unset($baris['_sekolah_npsn']);

            if ($npsn !== null) {
                if (! isset($pemetaanNpsn[$npsn])) {
                    // Sekolah akun ini tidak dikenal di sisi Lokal - lewati
                    // demi keamanan, jangan menebak-nebak sekolah mana yang
                    // dimaksud (sama pola dgn terapkanTabelAnak()).
                    $dilewati++;

                    continue;
                }
                $baris['profil_sekolah_id'] = $pemetaanNpsn[$npsn];
            } else {
                // Akun Superadmin (tidak terikat sekolah manapun).
                $baris['profil_sekolah_id'] = null;
            }

            $existing = DB::table('users')->where('username', $baris['username'])->first();

            if ($existing === null) {
                DB::table('users')->insert($baris);
                $ditambah++;
            } elseif ($this->lebihBaru($baris, $existing)) {
                DB::table('users')->where('username', $baris['username'])->update($baris);
                $diupdate++;
            } else {
                $dilewati++;
            }
        }

        return ['ditambah' => $ditambah, 'diupdate' => $diupdate, 'dilewati' => $dilewati];
    }
}
