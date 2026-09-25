<?php

namespace App\Models;

use Database\Factories\PendataanBospFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Identitas Admin BOSP (satu data per sekolah, dikelola oleh Admin BOSP
 * sekolah tersebut atau oleh Superadmin).
 */
#[Fillable([
    'profil_sekolah_id',
    'nuptk',
    'nama',
    'nip',
    'jk',
    'tempat_lahir',
    'tanggal_lahir',
    'status_kepegawaian',
    'pendidikan_terakhir',
    'jurusan',
    'nama_perguruan_tinggi',
    'no_whatsapp',
    'created_by',
])]
class PendataanBosp extends Model
{
    /** @use HasFactory<PendataanBospFactory> */
    use HasFactory;

    protected $table = 'pendataan_bosp';

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    public const JK_OPTIONS = [
        'L' => 'Laki-Laki',
        'P' => 'Perempuan',
    ];

    public const STATUS_KEPEGAWAIAN_OPTIONS = [
        'PNS' => 'PNS',
        'ASN PPPK' => 'ASN PPPK',
        'ASN PPPK-PW' => 'ASN PPPK-PW',
        'Honorer' => 'Honorer',
        'PTT Yayasan' => 'PTT Yayasan',
    ];

    public const PENDIDIKAN_OPTIONS = [
        'SMA' => 'SMA',
        'SMK' => 'SMK',
        'MA' => 'MA',
        'D1' => 'D1',
        'D2' => 'D2',
        'D3' => 'D3',
        'S1' => 'S1',
        'S2' => 'S2',
        'S3' => 'S3',
    ];

    /**
     * Jenjang pendidikan yang mengaktifkan field Jurusan & Nama Perguruan
     * Tinggi (nonaktif untuk SMA/SMK/MA).
     */
    public const PENDIDIKAN_BUTUH_JURUSAN = ['S1', 'S2', 'S3'];

    public static function butuhJurusan(?string $pendidikan): bool
    {
        return in_array($pendidikan, self::PENDIDIKAN_BUTUH_JURUSAN, true);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function profilSekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class);
    }

    public function getJkLabelAttribute(): string
    {
        return self::JK_OPTIONS[$this->jk] ?? '-';
    }
}
