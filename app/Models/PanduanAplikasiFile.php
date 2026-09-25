<?php

namespace App\Models;

use Database\Factories\PanduanAplikasiFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu file yang diupload di bawah satu Judul Book Manual (App\Models\
 * PanduanAplikasi::files()) - round DUA PULUH LIMA (2026-09-24), poin 1,
 * memisahkan tabel lama `panduan_aplikasi` (round 24) supaya SATU Judul
 * bisa punya BANYAK file sekaligus. Lihat docblock migration
 * restructure_panduan_aplikasi_untuk_multi_file_dan_link & App\Livewire\
 * PanduanAplikasi\Index utk detail lengkap.
 */
#[Fillable(['panduan_aplikasi_id', 'file_path', 'file_nama_asli', 'file_ukuran', 'file_mime'])]
class PanduanAplikasiFile extends Model
{
    /** @use HasFactory<PanduanAplikasiFileFactory> */
    use HasFactory;

    protected $table = 'panduan_aplikasi_file';

    /** @return BelongsTo<PanduanAplikasi, $this> */
    public function panduanAplikasi(): BelongsTo
    {
        return $this->belongsTo(PanduanAplikasi::class);
    }

    /** Ukuran file ini dalam bentuk yang mudah dibaca manusia ("1.2 MB" dst). */
    public function ukuranManusiawi(): ?string
    {
        return $this->file_ukuran ? self::formatUkuran((int) $this->file_ukuran) : null;
    }

    public static function formatUkuran(int $bytes): string
    {
        $satuan = ['B', 'KB', 'MB', 'GB'];
        $ukuran = (float) $bytes;
        $i = 0;

        while ($ukuran >= 1024 && $i < count($satuan) - 1) {
            $ukuran /= 1024;
            $i++;
        }

        return round($ukuran, $ukuran < 10 && $i > 0 ? 1 : 0).' '.$satuan[$i];
    }
}
