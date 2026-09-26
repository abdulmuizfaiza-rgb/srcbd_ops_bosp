<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email berisi token verifikasi akses (6 digit) yang dikirim SEBELUM
 * pengguna bisa membuka form login (lihat
 * resources/views/livewire/pages/auth/verifikasi-akses.blade.php) - fitur
 * "gerbang" tambahan supaya tidak semua orang bisa sembarangan mengakses
 * halaman login, diminta user 2026-09-26.
 *
 * Dikirim ke $user->email (kolom baru, TERPISAH dari username) supaya
 * Superadmin (yang username-nya bukan email) juga bisa menerima token ini.
 * Masa berlaku token (15 menit) dicek lewat Cache di komponen Volt di
 * atas, bukan di kelas ini.
 */
class TokenVerifikasiAkses extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Token Verifikasi Akses '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.token-verifikasi-akses',
        );
    }
}
