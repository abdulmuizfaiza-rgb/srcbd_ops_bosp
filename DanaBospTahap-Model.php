<?php

namespace App\Models;

use Database\Factories\DanaBospTahapFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dana BOSP Tahap 1 & 2 - 1 baris per sekolah PER TAHUN (permintaan user
 * 2026-09-16, Part 30). Lihat migration create_dana_bosp_tahap_table
 * untuk penjelasan lengkap kenapa Tab 1 & Tab 2 disatukan dalam 1 tabel.
 */
#[Fillable([
    'profil_sekolah_id',
    'tahun',
    'saldo_bosp_tahun_sebelumnya',
    'jumlah_siswa',
    'jumlah_dana_bosp_per_tahun',
    'total_penerimaan_setahun',
    'penerimaan_tahap_1',
    'penerimaan_tahap_2',
    'saldo_bosp_tw4_tahun_sebelumnya',
    'tarik_tunai_tw1',
    'tarik_tunai_tw2',
    'tarik_tunai_tw3',
    'tarik_tunai_tw4',
    'created_by',
])]
class DanaBospTahap extends Model
{
    /** @use HasFactory<DanaBospTahapFactory> */
    use HasFactory;

    protected $table = 'dana_bosp_tahap';

    /**
     * Field manual Tab 1 "Penerimaan BOSP" - dipakai untuk mengetahui
     * field mana yang boleh diedit LANGSUNG lewat kotak tabel (beda dari
     * field hasil rumus di FIELD_RUMUS_TAB1 di bawah).
     */
    public const FIELD_MANUAL_TAB1 = [
        'saldo_bosp_tahun_sebelumnya',
        'jumlah_siswa',
        'jumlah_dana_bosp_per_tahun',
    ];

    /**
     * Field hasil rumus Tab 1 - TIDAK PERNAH bisa diedit manual dari
     * jalur manapun (kotak tabel maupun lainnya), selalu dihitung ulang
     * lewat hitungRumusTab1().
     */
    public const FIELD_RUMUS_TAB1 = [
        'total_penerimaan_setahun',
        'penerimaan_tahap_1',
        'penerimaan_tahap_2',
    ];

    /**
     * Field Tab 2 "Tarik Tunai BOSP" - SEMUA manual, tidak ada rumus
     * (beda dari Tab 1).
     */
    public const FIELD_TAB2 = [
        'saldo_bosp_tw4_tahun_sebelumnya',
        'tarik_tunai_tw1',
        'tarik_tunai_tw2',
        'tarik_tunai_tw3',
        'tarik_tunai_tw4',
    ];

    /**
     * Label tampilan untuk tiap field Tab 2, dipakai pada form vertikal
     * Admin BOSP maupun blok vertikal per sekolah (expand) milik
     * Superadmin, supaya labelnya konsisten di 1 tempat saja.
     */
    public const LABEL_TAB2 = [
        'saldo_bosp_tw4_tahun_sebelumnya' => 'Saldo BOSP TW 4 Tahun Sebelumnya',
        'tarik_tunai_tw1' => 'Tarik Tunai TW 1',
        'tarik_tunai_tw2' => 'Tarik Tunai TW 2',
        'tarik_tunai_tw3' => 'Tarik Tunai TW 3',
        'tarik_tunai_tw4' => 'Tarik Tunai TW 4',
    ];

    /**
     * Menghitung Total Penerimaan BOSP Setahun & Penerimaan Tahap 1/2
     * (Tab 1) - dipakai bareng oleh input langsung di kotak tabel (lihat
     * Livewire\PendataanBosp\DanaBospTahap\Index::updated()) supaya
     * rumusnya konsisten dihitung dari 1 tempat saja.
     *
     * Rumus (ditentukan user 2026-09-16):
     * - Total Penerimaan BOSP Setahun = Jumlah Siswa x Jumlah Dana BOSP Per Tahun
     * - Penerimaan BOSP Tahap 1       = intdiv(Total, 2) -> dibulatkan ke
     *   bawah, TANPA desimal (jawaban AskUserQuestion "jangan ada desimal")
     * - Penerimaan BOSP Tahap 2       = Total - Penerimaan BOSP Tahap 1
     *   (otomatis menyerap sisa 1 rupiah kalau Total-nya ganjil)
     *
     * Nilai yang belum diisi (null) dianggap 0 untuk perhitungan ini -
     * hasilnya SELALU berupa angka (integer), tidak pernah null.
     *
     * @return array{0: int, 1: int, 2: int} [totalSetahun, tahap1, tahap2]
     */
    public static function hitungRumusTab1(?int $jumlahSiswa, ?int $jumlahDanaPerTahun): array
    {
        $total = (int) $jumlahSiswa * (int) $jumlahDanaPerTahun;
        $tahap1 = intdiv($total, 2);
        $tahap2 = $total - $tahap1;

        return [$total, $tahap1, $tahap2];
    }

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
        ];
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
