<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris = satu kali dokumen PDF diunduh dalam mode "Dengan Tanda
 * Tangan & Cap berupa QR Code" (Fase 2 fitur Tanda Tangan Kepala Sekolah
 * & Cap Sekolah). Dipakai oleh halaman verifikasi publik
 * (VerifikasiDokumenController) untuk menampilkan info dokumen saat QR
 * Code di PDF-nya di-scan. Lihat docblock migration
 * 2026_11_14_020000_create_verifikasi_dokumen_qr_table utk keputusan
 * desain lengkap (snapshot nama, nullOnDelete, dst).
 */
class VerifikasiDokumenQr extends Model
{
    use HasFactory;

    protected $table = 'verifikasi_dokumen_qr';

    protected $fillable = [
        'uuid',
        'jenis_dokumen',
        'profil_sekolah_id',
        'nama_sekolah',
        'nama_kepala_sekolah',
        'tahun',
        'dibuat_oleh',
    ];

    public function profilSekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class, 'profil_sekolah_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
