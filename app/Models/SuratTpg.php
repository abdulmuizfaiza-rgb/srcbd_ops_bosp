<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nomor Surat & Tanggal Surat (data yang DISIMPAN) untuk menu "Format
 * Surat Rekomendasi & Pembatalan TPG" (Pendataan OPS) - permintaan user
 * 2026-09-24 (round kedelapan belas). Lihat migration
 * `create_surat_tpg_table` & docblock App\Livewire\PendataanOps\SuratTpg\Index
 * untuk konteks lengkap & jawaban AskUserQuestion terkait.
 *
 * Seluruh field LAIN pada surat (Nama/NIP/Tempat Tugas Kepala Sekolah,
 * Nama/NIP Pengawas Pembina, daftar 4 kondisi/10 alasan baku, dst)
 * SENGAJA TIDAK disimpan di sini - semuanya dihitung/diambil langsung
 * dari App\Models\ProfilSekolah (Nama/NIP Kepsek & Pengawas) atau berupa
 * teks baku tetap (kondisi/alasan) setiap kali surat ditampilkan/dicetak,
 * BUKAN disalin/di-duplikasi ke tabel ini - supaya kalau data
 * ProfilSekolah diperbarui nanti, surat yang dicetak berikutnya otomatis
 * ikut ter-update tanpa perlu menyunting ulang tiap baris surat_tpg.
 *
 * Perbaikan 2026-09-24 (round kedua puluh satu, tambahan tab 3 "Surat
 * Pernyataan"): kolom `tahun_pelajaran` (mis. "2025/2026") ditambahkan -
 * HANYA dipakai jenis JENIS_PERNYATAAN, diisi manual Admin OPS &
 * tersimpan otomatis (mirip `nomor_surat`). Field lain pada Surat
 * Pernyataan (Nama/Unit Kerja/Alamat Kantor Kepala Sekolah) SAMA seperti
 * 2 jenis surat lain - diambil langsung dari ProfilSekolah, TIDAK
 * disimpan duplikat di sini.
 */
#[Fillable([
    'profil_sekolah_id',
    'tahun',
    'triwulan',
    'jenis',
    'nomor_surat',
    'tahun_pelajaran',
    'tanggal_surat',
])]
class SuratTpg extends Model
{
    use HasFactory;

    protected $table = 'surat_tpg';

    public const JENIS_REKOMENDASI = 'rekomendasi';

    public const JENIS_PENGHENTIAN = 'penghentian';

    public const JENIS_PERNYATAAN = 'pernyataan';

    /** Triwulan (angka) -> angka Romawi, dipakai pada teks badan surat ("Triwulan II ..."). */
    private const TRIWULAN_ROMAWI = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'];

    /** Triwulan (angka) -> kata dalam kurung ("... (dua) ..."), dipakai bersama TRIWULAN_ROMAWI di atas. */
    private const TRIWULAN_KATA = [1 => 'satu', 2 => 'dua', 3 => 'tiga', 4 => 'empat'];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
        ];
    }

    public function profilSekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class);
    }

    /**
     * Label "Triwulan II (dua)" dst - dipakai pada badan kedua surat.
     * SENGAJA konsisten memakai kata "Triwulan" pada KEDUA jenis surat
     * (Rekomendasi maupun Penghentian), MESKIPUN gambar contoh Surat
     * Penghentian yang diupload user menuliskan "Semester I (Satu)" -
     * user secara eksplisit menyatakan "2 tab tersebut dibuat
     * berdasarkan tahun dan triwulan" (bukan semester), jadi kata
     * "Triwulan" dipakai apa adanya utk kedua surat supaya konsisten
     * dengan triwulan yang sedang dipilih pada tab - BUKAN mengonversi
     * ke istilah "Semester" yang tidak diminta. Disebutkan eksplisit di
     * sini krn ini satu-satunya penyimpangan dari gambar contoh yang
     * diupload user (lihat juga PETUNJUK-UPDATE & progress log terkait).
     */
    public static function labelTriwulan(int $triwulan): string
    {
        $romawi = self::TRIWULAN_ROMAWI[$triwulan] ?? (string) $triwulan;
        $kata = self::TRIWULAN_KATA[$triwulan] ?? '';

        return trim("Triwulan {$romawi} ({$kata})");
    }

    /**
     * "Triwulan II" (angka Romawi SAJA, tanpa "(dua)") - dipakai pada
     * badan Surat Pernyataan (round kedua puluh satu), sesuai gambar
     * contoh yang diupload user yang menuliskan "Triwulan II" polos
     * (BEDA dgn labelTriwulan() di atas yang dipakai Surat
     * Rekomendasi/Penghentian & menyertakan "(dua)").
     */
    public static function labelTriwulanRomawi(int $triwulan): string
    {
        $romawi = self::TRIWULAN_ROMAWI[$triwulan] ?? (string) $triwulan;

        return "Triwulan {$romawi}";
    }
}
