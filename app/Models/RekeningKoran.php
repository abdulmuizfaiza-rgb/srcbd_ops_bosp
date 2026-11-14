<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Upload Rekening Koran - menu baru Pendataan BOSP (permintaan user
 * 2026-11-14). 1 baris per sekolah PER TAHUN, 4 kolom file (path disk
 * `public`) untuk rekening koran Triwulan 1-4. Lihat docblock migration
 * create_rekening_koran_table untuk penjelasan lengkap desainnya.
 */
#[Fillable([
    'profil_sekolah_id',
    'tahun',
    'rekening_koran_tw1',
    'rekening_koran_tw2',
    'rekening_koran_tw3',
    'rekening_koran_tw4',
    'created_by',
])]
class RekeningKoran extends Model
{
    use Syncable;

    protected $table = 'rekening_koran';

    /**
     * Nama kolom file untuk tiap nomor Triwulan (1-4) - dipakai bersama
     * oleh Livewire\PendataanBosp\RekeningKoran\Index supaya pemetaan
     * "nomor TW -> nama kolom" hanya ditulis di SATU tempat.
     */
    public const KOLOM_TW = [
        1 => 'rekening_koran_tw1',
        2 => 'rekening_koran_tw2',
        3 => 'rekening_koran_tw3',
        4 => 'rekening_koran_tw4',
    ];

    public function profilSekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Nama kolom file untuk nomor Triwulan tertentu (1-4), atau null kalau nomor tidak valid. */
    public static function kolomTw(int $tw): ?string
    {
        return self::KOLOM_TW[$tw] ?? null;
    }

    /** true kalau KEEMPAT Triwulan (TW1-4) sudah ada file-nya - dipakai Superadmin saat menggabungkan/mengunduh. */
    public function lengkap4Tw(): bool
    {
        foreach (self::KOLOM_TW as $kolom) {
            if (empty($this->{$kolom})) {
                return false;
            }
        }

        return true;
    }

    /**
     * Path file TW1-4 BERURUTAN (array kosong kalau sama sekali belum
     * ada yang diupload) - dipakai App\Services\RekeningKoranMergeService.
     *
     * @return array<int, string>
     */
    public function pathTw1ke4(): array
    {
        $paths = [];
        foreach (self::KOLOM_TW as $kolom) {
            if (! empty($this->{$kolom})) {
                $paths[] = $this->{$kolom};
            }
        }

        return $paths;
    }

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
        ];
    }
}
