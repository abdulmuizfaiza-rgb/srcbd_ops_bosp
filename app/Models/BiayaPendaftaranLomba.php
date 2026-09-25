<?php

namespace App\Models;

use Database\Factories\BiayaPendaftaranLombaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Biaya Pendaftaran Lomba/Bimtek/Workshop - data biaya pendaftaran
 * lomba/bimbingan teknis/workshop per sekolah, per tahun, per triwulan
 * (1-4).
 *
 * Sama seperti Langganan Daya dan Jasa: 1 sekolah bisa punya BANYAK
 * baris di menu ini - sesuai jawaban AskUserQuestion 2026-09-11
 * ("Banyak baris per sekolah"). Kolom "Uraian" SENGAJA TIDAK diberi
 * unique constraint - boleh duplikat.
 *
 * NPSN & Nama Sekolah TIDAK disimpan sebagai kolom di tabel ini -
 * selalu diambil langsung dari relasi profilSekolah() saat ditampilkan.
 */
#[Fillable([
    'profil_sekolah_id',
    'tahun',
    'triwulan',
    'uraian',
    'volume',
    'satuan',
    'tarif_harga',
    'jumlah',
    'tanggal',
    'created_by',
])]
class BiayaPendaftaranLomba extends Model
{
    /** @use HasFactory<BiayaPendaftaranLombaFactory> */
    use HasFactory;

    protected $table = 'biaya_pendaftaran_lomba';

    /**
     * 4 Tab Triwulan - teks tab persis sesuai permintaan user 2026-09-11
     * ("Biaya Pendaftaran Lomba/Bimtek/Workshop TW-1" dst) ditulis
     * langsung di view (lihat index.blade.php), bukan dari konstanta ini.
     * Label di sini dipakai untuk subjudul modal Tambah/Edit.
     */
    public const TRIWULAN_OPTIONS = [
        1 => 'Triwulan 1',
        2 => 'Triwulan 2',
        3 => 'Triwulan 3',
        4 => 'Triwulan 4',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'triwulan' => 'integer',
            'volume' => 'integer',
            'tarif_harga' => 'integer',
            'jumlah' => 'integer',
            'tanggal' => 'date',
        ];
    }

    /**
     * Jumlah = Volume x Tarif Harga - SELALU dihitung otomatis oleh
     * sistem, dipusatkan di sini supaya dipakai konsisten oleh input
     * langsung di kotak tabel, form modal Tambah/Edit, dan import Excel.
     * Nilai kosong (null) dianggap 0, hasilnya SELALU angka pasti.
     */
    public static function hitungJumlah(?int $volume, ?int $tarifHarga): int
    {
        return (int) $volume * (int) $tarifHarga;
    }

    public function profilSekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
