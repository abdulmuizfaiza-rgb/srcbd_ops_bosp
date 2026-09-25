<?php

/**
 * Konfigurasi tambahan menu Backup (round DUA PULUH TUJUH, 2026-09-24) -
 * permintaan user memperbaiki error "'pg_dump' is not recognized as an
 * internal or external command" yang muncul di Windows saat PostgreSQL
 * command-line tools (pg_dump.exe) belum terdaftar di PATH sistem.
 *
 * Kalau salah satu env di bawah diisi, App\Services\BackupService memakai
 * path ITU LANGSUNG utk menjalankan dump database - dicek LEBIH DULU
 * sebelum auto-deteksi ke lokasi umum install PostgreSQL/MySQL di
 * Windows, yang pada gilirannya jadi fallback sebelum akhirnya kembali ke
 * perilaku LAMA (memanggil nama command polos "pg_dump"/"mysqldump" yang
 * mengandalkan PATH sistem) kalau semuanya tidak ditemukan - jadi TIDAK
 * ada perubahan perilaku sama sekali bagi server yang PATH-nya sudah
 * benar (mis. server Linux/macOS yang sudah terbiasa berjalan sebelumnya).
 *
 * Isi env ini HANYA kalau pg_dump/mysqldump masih gagal ditemukan setelah
 * auto-deteksi (lihat pesan error di menu Backup - akan menyebutkan env
 * ini kalau binary tidak ditemukan). Contoh isi utk Windows:
 * BACKUP_PG_DUMP_PATH="C:\Program Files\PostgreSQL\16\bin\pg_dump.exe"
 */
return [
    'pg_dump_path' => env('BACKUP_PG_DUMP_PATH'),
    'mysqldump_path' => env('BACKUP_MYSQLDUMP_PATH'),
];
