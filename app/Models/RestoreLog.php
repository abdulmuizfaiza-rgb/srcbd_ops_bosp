<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat/audit trail setiap kali fitur Restore dijalankan - lihat
 * migration `create_restore_logs_table` untuk konteks lengkap. Dibuat
 * oleh App\Services\RestoreService::restore(), BERHASIL maupun GAGAL.
 */
#[Fillable(['backup_id', 'backup_pengaman_id', 'dilakukan_oleh_id', 'status', 'pesan_error'])]
class RestoreLog extends Model
{
    protected $table = 'restore_logs';

    public const STATUS_BERHASIL = 'berhasil';

    public const STATUS_GAGAL = 'gagal';

    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }

    public function backupPengaman(): BelongsTo
    {
        return $this->belongsTo(Backup::class, 'backup_pengaman_id');
    }

    public function dilakukanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dilakukan_oleh_id');
    }
}
