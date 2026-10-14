<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Peran instalasi ini - "lokal" atau "online"
    |--------------------------------------------------------------------------
    |
    | Permintaan user 2026-10-14 poin 3 (Sinkronisasi dan Tarik Data):
    | menentukan tampilan & tombol apa yang AKTIF di menu "Sinkronisasi
    | dan Tarik Data" pada instalasi ini.
    | - 'lokal'  : tombol Sinkronisasi (kirim) & Tarik Data (ambil) AKTIF,
    |              dan ada form untuk mengisi URL + Token API Online.
    | - 'online' : tombol-tombol itu TAMPIL TAPI NONAKTIF (hanya status/
    |              riwayat) - Online tidak bisa memulai koneksi balik ke
    |              Lokal (biasanya di jaringan rumah/kantor tanpa alamat
    |              publik) - jawaban AskUserQuestion sebelumnya.
    |
    | SENGAJA default ke 'online' (sisi yang lebih "aman"/pasif) kalau
    | SYNC_PERAN belum diisi di .env - supaya instalasi yang lupa mengisi
    | variabel ini TIDAK SECARA TIDAK SENGAJA mengaktifkan tombol yang
    | bisa menimpa/menghapus data.
    |
    | WAJIB diisi manual di .env masing-masing instalasi:
    |   .env LOKAL   -> SYNC_PERAN=lokal
    |   .env ONLINE  -> SYNC_PERAN=online
    | (lalu `php artisan config:clear`).
    |
    */
    'peran' => env('SYNC_PERAN', 'online'),

    /*
    |--------------------------------------------------------------------------
    | Batas waktu (detik) saat Lokal menghubungi API Online
    |--------------------------------------------------------------------------
    */
    'timeout_detik' => (int) env('SYNC_TIMEOUT_DETIK', 120),
];
