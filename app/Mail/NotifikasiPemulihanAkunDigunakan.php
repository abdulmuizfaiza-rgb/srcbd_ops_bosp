<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Email pemberitahuan yang dikirim ke akun Superadmin tsb setiap kali
 * jalur "Pemulihan Akun" BERHASIL dipakai untuk login (lihat
 * App\Livewire\Forms\LoginForm::pemulihan()) - audit keamanan 2026-10-14.
 *
 * Tujuannya supaya Superadmin asli SELALU tahu kalau jalur pemulihan
 * baru saja dipakai (baik oleh dirinya sendiri yang lupa password,
 * maupun kalau ada pihak lain yang berhasil memakainya) - sebelumnya
 * tidak ada pemberitahuan apa pun untuk kejadian ini.
 */
class NotifikasiPemulihanAkunDigunakan extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?string $ipAddress,
        public Carbon $waktu,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Peringatan: Akun Anda Baru Saja Masuk Lewat Pemulihan Akun - '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.notifikasi-pemulihan-akun-digunakan',
        );
    }
}
