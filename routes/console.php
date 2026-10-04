<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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
