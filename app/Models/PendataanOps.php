<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Kerangka awal Pendataan OPS.
 * Field detail akan ditambahkan sesuai instruksi berikutnya.
 */
#[Fillable(['created_by'])]
class PendataanOps extends Model
{
    protected $table = 'pendataan_ops';

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
