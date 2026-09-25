<?php

namespace App\Models;

use Database\Factories\PanduanAplikasiLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu link Google Drive di bawah satu Judul Book Manual (App\Models\
 * PanduanAplikasi::links()) - round DUA PULUH LIMA (2026-09-24), poin 1,
 * memisahkan tabel lama `panduan_aplikasi` (round 24) supaya SATU Judul
 * bisa punya BANYAK link sekaligus. Lihat docblock migration
 * restructure_panduan_aplikasi_untuk_multi_file_dan_link & App\Livewire\
 * PanduanAplikasi\Index utk detail lengkap.
 */
#[Fillable(['panduan_aplikasi_id', 'link_drive'])]
class PanduanAplikasiLink extends Model
{
    /** @use HasFactory<PanduanAplikasiLinkFactory> */
    use HasFactory;

    protected $table = 'panduan_aplikasi_link';

    /** @return BelongsTo<PanduanAplikasi, $this> */
    public function panduanAplikasi(): BelongsTo
    {
        return $this->belongsTo(PanduanAplikasi::class);
    }
}
