<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email berisi password baru (sementara) yang dikirim ke Admin OPS/Admin
 * BOSP setelah Superadmin me-reset password akun mereka lewat menu Kelola
 * Pengguna (App\Livewire\Pengguna\Index::resetPassword()).
 *
 * Dikirim ke $user->username, yang untuk kedua level ini WAJIB berupa
 * alamat email (divalidasi 'email' sejak pendaftaran/dibuat Superadmin -
 * lihat app/Livewire/Pengguna/Index.php & pages/auth/register.blade.php),
 * jadi tidak perlu kolom "email" terpisah di tabel users.
 */
class PasswordDiresetSuperadmin extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $passwordBaru,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Baru Akun '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.password-direset',
        );
    }
}
