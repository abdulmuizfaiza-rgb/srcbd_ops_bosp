<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('verifikasi-akses', 'pages.auth.verifikasi-akses')
        ->name('verifikasi-akses');

    Volt::route('login', 'pages.auth.login')
        ->name('login');

    Volt::route('register', 'pages.auth.register')
        ->name('register');
});

Route::middleware('auth')->group(function () {
    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');

    // Google Authenticator (2FA) - permintaan user 2026-09-27. Rute ini
    // SENGAJA di bawah middleware 'auth' saja (bukan 'guest') karena
    // pengguna harus SUDAH lolos username+password dulu sebelum sampai
    // di sini. Middleware EnsureGoogleAuthenticatorVerified (lihat
    // bootstrap/app.php) yang mengarahkan pengguna ke sini secara paksa.
    Volt::route('authenticator/aktivasi', 'pages.auth.authenticator-aktivasi')
        ->name('authenticator.aktivasi');

    Volt::route('authenticator/verifikasi', 'pages.auth.authenticator-verifikasi')
        ->name('authenticator.verifikasi');
});
