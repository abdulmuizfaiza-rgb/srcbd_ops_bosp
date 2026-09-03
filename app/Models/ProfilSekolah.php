<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'npsn',
    'nama_sekolah',
    'nama_kepala_sekolah',
    'nip_kepala_sekolah',
    'alamat_sekolah',
    'logo_sekolah',
    'logo_pemda',
])]
class ProfilSekolah extends Model
{
    protected $table = 'profil_sekolah';
}
