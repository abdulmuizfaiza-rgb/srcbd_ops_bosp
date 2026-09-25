<?php

namespace App\Models;

use Database\Factories\BackupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat satu paket backup (round DUA PULUH LIMA, 2026-09-24, poin 2,
 * permintaan user "buatkan menu backup berdasarkan tahun... yang terdiri
 * dari Aplikasi nya dan database nya yang terbaru"). Dibuat oleh
 * App\Services\BackupService, HANYA bisa dibuat/dilihat/diunduh/dihapus
 * Superadmin (Gate 'akses-backup' di App\Providers\AppServiceProvider) -
 * lihat docblock migration create_backups_table utk keputusan bisnis
 * lengkap (jawaban AskUserQuestion).
 */
#[Fillable(['tahun', 'nama_file', 'path', 'ukuran', 'dibuat_oleh_id'])]
class Backup extends Model
{
    /** @use HasFactory<BackupFactory> */
    use HasFactory;

    protected $table = 'backups';

    /** @return BelongsTo<User, $this> */
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh_id');
    }

    /** Ukuran paket backup ini dalam bentuk yang mudah dibaca manusia ("120.5 MB" dst). */
    public function ukuranManusiawi(): ?string
    {
        if (! $this->ukuran) {
            return null;
        }

        $satuan = ['B', 'KB', 'MB', 'GB'];
        $ukuran = (float) $this->ukuran;
        $i = 0;

        while ($ukuran >= 1024 && $i < count($satuan) - 1) {
            $ukuran /= 1024;
            $i++;
        }

        return round($ukuran, $ukuran < 10 && $i > 0 ? 1 : 0).' '.$satuan[$i];
    }
}
