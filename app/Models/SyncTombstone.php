<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tabel', 'kunci', 'dihapus_pada', 'dihapus_oleh_id'])]
class SyncTombstone extends Model
{
    protected $table = 'sync_tombstones';

    protected function casts(): array
    {
        return [
            'kunci' => 'array',
            'dihapus_pada' => 'datetime',
        ];
    }

    public function dihapusOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dihapus_oleh_id');
    }
}
