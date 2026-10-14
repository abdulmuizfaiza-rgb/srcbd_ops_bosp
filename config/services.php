<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Drive (unggah otomatis backup - permintaan user 2026-10-14)
    |--------------------------------------------------------------------------
    |
    | GANTI ARSITEKTUR (2026-10-14, sore) dari Service Account (kolom
    | 'credentials_path' lama, file JSON di server) menjadi OAuth 2.0
    | "Login dengan Google" - lihat docblock lengkap App\Services\
    | GoogleDriveService untuk alasan (Service Account tidak bisa dipakai
    | utk akun Gmail pribadi) & panduan update utk cara membuat OAuth
    | Client ID di Google Cloud Console. client_id boleh diketahui publik
    | (tampil di URL login Google), client_secret tetap rahasia - KEDUANYA
    | hanya disimpan di .env server (TIDAK ikut ter-commit / ter-zip ke
    | backup).
    |
    */
    'google_drive' => [
        'oauth_client_id' => env('GOOGLE_DRIVE_OAUTH_CLIENT_ID'),
        'oauth_client_secret' => env('GOOGLE_DRIVE_OAUTH_CLIENT_SECRET'),
    ],

];
