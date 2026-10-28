<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Audit 2026-10-04: command BACA-SAJA (tidak mengubah/menghapus apa pun)
 * untuk membantu Superadmin mengidentifikasi akun/data mana yang data
 * uji coba vs data asli, sebagai langkah awal sebelum pembersihan data
 * produksi (lihat laporan audit "Pembersihan Data"). Jalankan dengan:
 *   php artisan audit:lihat-data-ringkasan
 */
Artisan::command('audit:lihat-data-ringkasan', function () {
    $this->info('=== Ringkasan Data untuk Audit (HANYA MELIHAT, TIDAK mengubah/menghapus apa pun) ===');

    $this->newLine();
    $this->info('--- Akun Pengguna (tabel users) ---');
    \App\Models\User::query()
        ->select('id', 'username', 'email', 'level_akses', 'nama_sekolah', 'jabatan', 'is_approved', 'created_at')
        ->orderBy('id')
        ->get()
        ->each(function ($u) {
            $this->line(sprintf(
                '#%d | username: %s | email: %s | level: %s | sekolah: %s | jabatan: %s | disetujui: %s | dibuat: %s',
                $u->id,
                $u->username,
                $u->email ?? '-',
                $u->level_akses,
                $u->nama_sekolah ?? '-',
                $u->jabatan ?? '-',
                $u->is_approved ? 'ya' : 'belum',
                $u->created_at?->format('Y-m-d H:i') ?? '-'
            ));
        });

    $this->newLine();
    $this->info('--- Profil Sekolah (tabel profil_sekolah) ---');
    \App\Models\ProfilSekolah::query()
        ->select('id', 'npsn', 'nama_sekolah', 'created_at')
        ->orderBy('id')
        ->get()
        ->each(function ($p) {
            $this->line(sprintf(
                '#%d | NPSN: %s | nama: %s | dibuat: %s',
                $p->id,
                $p->npsn ?? '-',
                $p->nama_sekolah ?? '-',
                $p->created_at?->format('Y-m-d H:i') ?? '-'
            ));
        });

    $this->newLine();
    $this->info('--- Jumlah Baris per Tabel Transaksi (gambaran skala data, bukan isinya) ---');
    $tabelTransaksi = [
        'pendataan_ops', 'pendataan_bosp', 'rekap_rkas', 'dana_bosp_tahap',
        'penerimaan_honor_ptk', 'langganan_daya_jasa', 'rincian_pemeliharaan',
        'rincian_pemeliharaan_pc', 'biaya_pendaftaran_lomba', 'belanja_honor_kegiatan',
        'rincian_belanja_modal', 'rincian_belanja_barang_habis_pakai',
        'rincian_belanja_modal_bmd', 'pajak_bosp_reguler', 'laporan_realisasi_bosp',
        'formulir_bos_k7', 'verval_realisasi_bosp', 'stock_opname_barang_persediaan',
        'surat_tpg', 'data_ptk', 'panduan_aplikasi', 'backups',
    ];
    foreach ($tabelTransaksi as $tabel) {
        if (\Illuminate\Support\Facades\Schema::hasTable($tabel)) {
            $jumlah = \Illuminate\Support\Facades\DB::table($tabel)->count();
            $this->line(sprintf('%s: %d baris', $tabel, $jumlah));
        }
    }

    $this->newLine();
    $this->comment('Command ini TIDAK mengubah atau menghapus data apa pun - cuma menampilkan.');
    $this->comment('Gunakan daftar di atas untuk menentukan akun/data mana yang menurut Anda data uji coba, lalu beri tahu ID/username-nya ke Claude.');
})->purpose('Audit: tampilkan ringkasan data users & profil_sekolah (BACA SAJA) untuk membantu identifikasi data uji coba');

/**
 * Audit 2026-10-04 (lanjutan "Pembersihan Data Uji Coba") - user sudah
 * mengonfirmasi data uji coba yang dimaksud: User dgn email
 * "abdulmuizfaiza2@gmail.com" (level admin_bosp) & Sekolah dgn NPSN
 * "12345678" ("SMPS UJI COBA" - NPSN ini jelas angka dummy berurutan,
 * BEDA dgn 60 sekolah lain yg semua punya NPSN resmi 8 digit asli dari
 * data Kemendikbud & dibuat bersamaan 2026-09-04).
 *
 * Tabel lain yg terhubung ke sekolah ini (lewat kolom profil_sekolah_id)
 * DITEMUKAN OTOMATIS lewat information_schema - BUKAN daftar manual -
 * supaya tidak ada tabel yg kelewat/lupa (termasuk yg tidak ada di
 * daftar command audit:lihat-data-ringkasan sebelumnya, misalnya
 * lampiran_2a/2b/2c & deadline_pekerjaan).
 *
 * SAFETY: tabel 'users' SENGAJA tidak ikut dihapus lewat loop generik -
 * hanya user yg emailnya persis cocok (ada di $emailUjiCobaList) yg
 * dihapus. Kalau ternyata ADA user LAIN (di luar daftar ini) yg juga
 * terhubung ke sekolah "uji coba" ini (di luar dugaan), command
 * BERHENTI tanpa menghapus apa pun & minta dicek manual dulu - supaya
 * tidak ada akun asli yang ikut terhapus.
 *
 * UPDATE 2026-10-28 (audit ulang menyeluruh atas permintaan user "hapus
 * semua data dummy, jangan sampai ada yang salah"): ditemukan akun
 * KEDUA yang juga data uji coba, HANYA ada di ONLINE (tidak ada di
 * Lokal) - abdulmuizfaiza3@gmail.com (level admin_bosp), dibuat
 * 2026-10-23, terhubung ke sekolah dummy yang SAMA (NPSN 12345678).
 * User sudah mengonfirmasi akun ini juga boleh dihapus. Command
 * digeneralisasi dari 1 email hardcoded ($emailUjiCoba) menjadi DAFTAR
 * email ($emailUjiCobaList) supaya kedua akun tertangani otomatis -
 * pengecekan keamanan "user lain terhubung" disesuaikan supaya TIDAK
 * menganggap sesama email di daftar ini sebagai "user lain yang tidak
 * terduga" (sebelum perubahan ini, command akan membatalkan diri
 * sendiri karena menganggap abdulmuizfaiza3@gmail.com sebagai akun
 * asing - sudah terbukti saat preview ONLINE 2026-10-28).
 *
 * DEFAULT (tanpa flag) = HANYA PREVIEW, tidak mengubah/menghapus apa
 * pun. Untuk benar-benar menghapus, WAJIB pakai flag --hapus-sungguhan
 * DAN menjawab "ya" pada konfirmasi interaktif.
 *
 * Jalankan:
 *   php artisan audit:bersihkan-data-uji-coba                    (preview, aman)
 *   php artisan audit:bersihkan-data-uji-coba --hapus-sungguhan  (hapus beneran)
 */
Artisan::command('audit:bersihkan-data-uji-coba {--hapus-sungguhan : Benar-benar menghapus, bukan cuma preview}', function () {
    $emailUjiCobaList = ['abdulmuizfaiza2@gmail.com', 'abdulmuizfaiza3@gmail.com'];
    $npsnUjiCoba = '12345678';

    $users = \App\Models\User::whereIn('email', $emailUjiCobaList)->get();
    $sekolah = \App\Models\ProfilSekolah::where('npsn', $npsnUjiCoba)->first();

    if ($users->isEmpty() && ! $sekolah) {
        $this->error('Tidak ditemukan User dgn email uji coba manapun ('.implode(', ', $emailUjiCobaList).") maupun Sekolah dgn NPSN {$npsnUjiCoba}. Mungkin sudah pernah dihapus sebelumnya, atau datanya sudah berubah - tidak ada tindakan yang dilakukan.");

        return;
    }

    $this->info('=== Data Uji Coba yang Terdeteksi ===');
    if ($users->isNotEmpty()) {
        $users->each(function ($u) {
            $this->line("User: #{$u->id} | username: {$u->username} | email: {$u->email} | level: {$u->level_akses}");
        });
        $emailDitemukan = $users->pluck('email')->all();
        foreach ($emailUjiCobaList as $email) {
            if (! in_array($email, $emailDitemukan, true)) {
                $this->comment("User dgn email {$email} TIDAK ditemukan (mungkin sudah dihapus sebelumnya).");
            }
        }
    } else {
        $this->comment('Tidak ada User dgn email uji coba manapun yang ditemukan (mungkin sudah dihapus sebelumnya).');
    }
    if ($sekolah) {
        $this->line("Sekolah: #{$sekolah->id} | NPSN: {$sekolah->npsn} | nama: {$sekolah->nama_sekolah}");
    } else {
        $this->comment("Sekolah dgn NPSN {$npsnUjiCoba} TIDAK ditemukan (mungkin sudah dihapus sebelumnya).");
    }

    $tabelDenganProfilSekolahId = collect(\Illuminate\Support\Facades\DB::select(
        "select table_name from information_schema.columns where column_name = 'profil_sekolah_id' and table_schema = 'public' order by table_name"
    ))->pluck('table_name')->reject(fn ($t) => in_array($t, ['profil_sekolah', 'users']))->values();

    $jumlahPerTabel = [];
    $userLainTerhubung = collect();

    if ($sekolah) {
        $this->newLine();
        $this->info('--- Baris di Tabel Lain yang Terhubung ke Sekolah Ini (otomatis ditemukan lewat profil_sekolah_id) ---');
        foreach ($tabelDenganProfilSekolahId as $tabel) {
            $jumlah = \Illuminate\Support\Facades\DB::table($tabel)->where('profil_sekolah_id', $sekolah->id)->count();
            if ($jumlah > 0) {
                $jumlahPerTabel[$tabel] = $jumlah;
                $this->line("{$tabel}: {$jumlah} baris");
            }
        }
        if (empty($jumlahPerTabel)) {
            $this->line('(tidak ada baris lain yang terhubung)');
        }

        $this->newLine();
        $this->info('--- Pengecekan Keamanan: User LAIN yang Juga Terhubung ke Sekolah Ini ---');
        $idUserDitemukan = $users->pluck('id')->all();
        $userLainTerhubung = \App\Models\User::where('profil_sekolah_id', $sekolah->id)
            ->whereNotIn('id', $idUserDitemukan)
            ->get(['id', 'username', 'email', 'level_akses']);
        if ($userLainTerhubung->isEmpty()) {
            $this->line('(tidak ada user lain yang terhubung ke sekolah ini - aman)');
        } else {
            $userLainTerhubung->each(function ($u) {
                $this->warn("PERHATIAN: User LAIN juga terhubung ke sekolah ini -> #{$u->id} | {$u->username} | {$u->email} | {$u->level_akses}");
            });
        }
    }

    if (! $this->option('hapus-sungguhan')) {
        $this->newLine();
        $this->comment('Ini BARU PREVIEW - belum ada yang dihapus. Jalankan ulang dengan --hapus-sungguhan untuk benar-benar menghapus (akan ada konfirmasi interaktif lagi sebelum eksekusi).');

        return;
    }

    if ($userLainTerhubung->isNotEmpty()) {
        $this->newLine();
        $this->error('DIBATALKAN: ada user LAIN (di luar dugaan) yang juga terhubung ke sekolah ini (lihat daftar di atas). Tidak ada yang dihapus, supaya akun lain tidak ikut terhapus tanpa sengaja. Mohon cek manual dulu & beri tahu Claude.');

        return;
    }

    $this->newLine();
    $this->warn('MODE HAPUS SUNGGUHAN. Pastikan Anda SUDAH membuat backup penuh lewat menu Backup aplikasi sebelum lanjut.');
    if (! $this->confirm('Yakin mau menghapus data di atas? Tindakan ini TIDAK BISA dibatalkan.', false)) {
        $this->comment('Dibatalkan, tidak ada yang dihapus.');

        return;
    }

    \Illuminate\Support\Facades\DB::transaction(function () use ($users, $sekolah, $jumlahPerTabel) {
        if ($sekolah) {
            foreach (array_keys($jumlahPerTabel) as $tabel) {
                \Illuminate\Support\Facades\DB::table($tabel)->where('profil_sekolah_id', $sekolah->id)->delete();
            }
        }
        foreach ($users as $u) {
            $u->delete();
        }
        if ($sekolah) {
            $sekolah->delete();
        }
    });

    $this->info('Selesai. Data uji coba (user, sekolah, & data transaksi terkait) sudah dihapus.');
})->purpose('Hapus data uji coba yang sudah dikonfirmasi user (daftar email abdulmuizfaiza2@gmail.com & abdulmuizfaiza3@gmail.com + sekolah NPSN 12345678) - default PREVIEW, pakai --hapus-sungguhan utk eksekusi');

/**
 * Permintaan user 2026-10-24 (fitur Tahun Anggaran - rollover otomatis):
 * Tahun Anggaran Aktif naik OTOMATIS begitu masuk tanggal 1 Januari
 * pukul 00:00 WIB. Tahun sebelumnya otomatis "terkunci" lewat
 * mekanisme yang SUDAH ADA sejak Fase 1 (lihat App\Models\
 * PermintaanAksesTahun & EnsureTahunSesiDipilih) - begitu tahun_aktif
 * berubah, Admin OPS/Admin BOSP yang session tahun-nya masih tahun
 * lama otomatis tidak lagi punya akses ke tahun lama itu, KECUALI
 * sudah mengajukan & disetujui Superadmin lewat menu Tahun Anggaran
 * (permanen per akun+tahun, sama seperti alur manual yang sudah ada).
 *
 * DISENGAJA mengecek & menaikkan tahun_aktif ke TAHUN WIB SAAT INI
 * (bukan cuma "+1") supaya IDEMPOTEN & BISA "MENYUSUL" (self-healing)
 * - command ini aman dijalankan berkali-kali kapan pun, TIDAK AKAN
 * PERNAH menaikkan tahun 2x untuk tahun yang sama (kalau tahun_aktif
 * sudah >= tahun WIB saat ini, command tidak melakukan apa pun).
 * Dijadwalkan jalan SETIAP HARI pukul 00:05 WIB (bukan cuma sekali
 * setahun pas tengah malam 1 Januari) supaya kalau scheduler VPS
 * sempat mati/terlewat tepat di momen itu (server mati, maintenance,
 * dll), begitu scheduler jalan lagi di hari-hari berikutnya
 * tahun_aktif akan otomatis "menyusul" sendiri tanpa perlu campur
 * tangan Superadmin - 364 hari lainnya command ini hanya mengecek &
 * tidak melakukan apa pun (no-op, aman & murah, tidak membebani server).
 *
 * PENTING: fitur ini HANYA berjalan kalau scheduler Laravel aktif di
 * server (lihat panduan ONLINE terkait fitur ini - perlu 1x setup
 * crontab VPS: `* * * * * php artisan schedule:run`). Jalankan manual
 * kapan saja utk tes (aman, idempoten):
 *   php artisan tahun-anggaran:naikkan-otomatis
 */
Artisan::command('tahun-anggaran:naikkan-otomatis', function () {
    $setting = \App\Models\TahunAnggaranSetting::current();
    $tahunWibSaatIni = now('Asia/Jakarta')->year;

    if ($setting->tahun_aktif >= $tahunWibSaatIni) {
        $this->comment("Tidak ada perubahan - Tahun Anggaran aktif sudah {$setting->tahun_aktif} (tahun WIB saat ini: {$tahunWibSaatIni}).");

        return;
    }

    $tahunLama = $setting->tahun_aktif;
    $setting->update(['tahun_aktif' => $tahunWibSaatIni]);

    $this->info("Tahun Anggaran Aktif otomatis naik dari {$tahunLama} ke {$tahunWibSaatIni}.");
    \Illuminate\Support\Facades\Log::info("Tahun Anggaran Aktif otomatis naik dari {$tahunLama} ke {$tahunWibSaatIni} (rollover otomatis, dipicu scheduler).");
})->purpose('Naikkan Tahun Anggaran Aktif otomatis kalau sudah masuk tahun baru (WIB) - dijadwalkan harian, aman dijalankan berkali-kali (idempoten)');

Schedule::command('tahun-anggaran:naikkan-otomatis')
    ->dailyAt('00:05')
    ->timezone('Asia/Jakarta');
