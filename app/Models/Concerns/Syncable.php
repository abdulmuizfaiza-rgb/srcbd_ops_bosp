<?php

namespace App\Models\Concerns;

use App\Models\SyncTombstone;
use App\Support\RegistriModulSekolah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Trait untuk SEMUA model yang datanya ikut disinkronkan lewat menu
 * "Sinkronisasi dan Tarik Data" (permintaan user 2026-10-14 poin 3).
 * Dipasang di 24 model (23 dari RegistriModulSekolah::semua() + Profil
 * Sekolah) - lihat panduan update untuk daftar lengkapnya.
 *
 * 2 tanggung jawab, KEDUANYA HANYA relevan untuk pemakaian NORMAL lewat
 * menu-menu aplikasi (CRUD Livewire biasa) - App\Services\SyncEngine
 * SENDIRI tidak lewat Eloquent create()/update()/delete() saat
 * menerapkan data sinkronisasi (pakai query builder DB::table()
 * langsung, lihat docblock SyncEngine) justru supaya timestamp asli
 * dari sisi pengirim tidak tertimpa otomatis oleh Eloquent - jadi event
 * creating/deleted di bawah ini TIDAK IKUT TERPICU selagi sinkronisasi
 * berjalan, tidak perlu flag penekan apapun:
 *
 * 1. Mengisi otomatis kolom `sync_uuid` (kalau model punya kolom itu -
 *    HANYA 12 modul yang tidak punya kunci unik alami, lihat
 *    RegistriModulSekolah::tabelDisinkronkan()) saat baris baru dibuat -
 *    dipakai sebagai identitas lintas-database (Lokal vs Online punya
 *    ID auto-increment SENDIRI-SENDIRI yang tidak nyambung, jadi ID
 *    bawaan database TIDAK BISA dipakai sebagai kunci pencocokan saat
 *    sinkronisasi).
 * 2. Mencatat "batu nisan" (App\Models\SyncTombstone) tiap kali 1 baris
 *    DIHAPUS lewat menu aplikasi biasa - supaya penghapusan ini BISA
 *    ikut disinkronkan ke sisi lain nanti (jawaban AskUserQuestion
 *    2026-10-14: "Penghapusan ikut disinkronkan (Recommended)").
 */
trait Syncable
{
    public static function bootSyncable(): void
    {
        static::creating(function ($model): void {
            $konfigurasi = RegistriModulSekolah::tabelDisinkronkan()[$model->getTable()] ?? null;

            // Hanya modul TANPA kunci unik alami (kunci === null) yang
            // perlu sync_uuid - modul lain sudah punya kunci alami
            // sendiri (profil_sekolah_id+tahun, npsn, nik, dst).
            if ($konfigurasi === null || $konfigurasi['kunci'] !== null) {
                return;
            }

            if (empty($model->sync_uuid)) {
                $model->sync_uuid = (string) Str::uuid();
            }
        });

        static::deleted(function ($model): void {
            $konfigurasi = RegistriModulSekolah::tabelDisinkronkan()[$model->getTable()] ?? null;
            if ($konfigurasi === null) {
                return;
            }

            $kolomKunci = $konfigurasi['kunci'] ?? ['sync_uuid'];
            $kunci = [];
            foreach ($kolomKunci as $kolom) {
                // profil_sekolah_id adalah ID auto-increment LOKAL pada
                // database ini - TIDAK BERMAKNA di sisi lain. Ganti
                // dengan NPSN sekolahnya (identitas yang sama di kedua
                // sisi) supaya App\Services\SyncEngine bisa menerjemahkan
                // balik ke profil_sekolah_id versi SISI LAIN saat
                // menerapkan tombstone ini (lihat SyncEngine::
                // terapkanTombstone()).
                if ($kolom === 'profil_sekolah_id') {
                    $kunci['_sekolah_npsn'] = DB::table('profil_sekolah')
                        ->where('id', $model->getAttribute('profil_sekolah_id'))
                        ->value('npsn');

                    continue;
                }

                $kunci[$kolom] = $model->getAttribute($kolom);
            }

            // Kalau kunci kosong/null semua, lewati - tidak ada cara
            // aman untuk mencocokkan baris ini di sisi lain nanti.
            if (collect($kunci)->filter(fn ($v) => $v !== null)->isEmpty()) {
                return;
            }

            SyncTombstone::create([
                'tabel' => $model->getTable(),
                'kunci' => $kunci,
                'dihapus_pada' => now(),
                'dihapus_oleh_id' => auth()->id(),
            ]);
        });
    }
}
