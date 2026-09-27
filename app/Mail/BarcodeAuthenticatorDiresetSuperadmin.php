<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email berisi barcode (QR code) & kunci manual Google Authenticator BARU,
 * dikirim ke pengguna setelah Superadmin me-reset Authenticator akun mereka
 * lewat menu Pengguna > tab Authenticator
 * (App\Livewire\Pengguna\Index::resetAuthenticator()) - baik karena pengguna
 * menekan "Lupa Authenticator?" saat login, maupun diinisiasi langsung oleh
 * Superadmin.
 *
 * $svgBarcodeDataUri dibuat lewat App\Services\GoogleAuthenticatorService -
 * berupa data URI (data:image/svg+xml;base64,...) supaya bisa langsung
 * ditampilkan sebagai <img> di email tanpa perlu menyimpan file gambar
 * terpisah di server.
 */
class BarcodeAuthenticatorDiresetSuperadmin extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $svgBarcodeDataUri,
        public string $kunciManual,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Barcode Google Authenticator Baru - '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.barcode-authenticator-direset',
        );
    }
}
