<?php

namespace App\Models;

use Database\Factories\BelanjaHonorKegiatanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Belanja Honor Kegiatan - data honor kegiatan (mis. honor panitia,
 * honor narasumber kegiatan sekolah), Belanja Makan & Minum Kegiatan,
 * dan Belanja Perjalanan Dinas per sekolah, per tahun, per triwulan
 * (1-4) - terbagi 3 jenis (TAB UTAMA), lihat konstanta
 * JENIS_HONOR_KEGIATAN/JENIS_MAKAN_MINUM/JENIS_PERJALANAN_DINAS.
 *
 * Awalnya (Part 19) menu ini cuma 1 tab ("Belanja Honor Kegiatan", 4
 * triwulan saja). Permintaan user 2026-09-11 (Part 20) menambah 2 tab
 * baru ("Belanja Makan & Minum" & "Belanja Perjalanan Dinas") dengan
 * field yang PERSIS SAMA (dikonfirmasi lewat AskUserQuestion, ketiga
 * gambar contoh tabel yang diupload user identik) - karena itu dipakai
 * SATU tabel dengan kolom "jenis" BARU sebagai pembeda tab utama, bukan
 * 3 model/tabel terpisah - sesuai jawaban AskUserQuestion 2026-09-11
 * ("1 tabel dengan kolom pembeda"), sama seperti pola RincianPemeliharaan
 * (Part 17). Lihat migration add_jenis_to_belanja_honor_kegiatan_table
 * untuk catatan backfill data lama (Part 19) ke jenis='honor_kegiatan'.
 *
 * Sama seperti Langganan Daya dan Jasa/Biaya Pendaftaran Lomba: 1
 * sekolah bisa punya BANYAK baris di menu ini - sesuai jawaban
 * AskUserQuestion 2026-09-11 ("Banyak baris per sekolah"). Kolom
 * "Uraian" SENGAJA TIDAK diberi unique constraint - boleh duplikat.
 *
 * NPSN & Nama Sekolah TIDAK disimpan sebagai kolom di tabel ini -
 * selalu diambil langsung dari relasi profilSekolah() saat ditampilkan.
 */
#[Fillable([
    'profil_sekolah_id',
    'jenis',
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
class BelanjaHonorKegiatan extends Model
{
    /** @use HasFactory<BelanjaHonorKegiatanFactory> */
    use HasFactory;

    protected $table = 'belanja_honor_kegiatan';

    public const JENIS_HONOR_KEGIATAN = 'honor_kegiatan';

    public const JENIS_MAKAN_MINUM = 'makan_minum';

    public const JENIS_PERJALANAN_DINAS = 'perjalanan_dinas';

    /**
     * Label tab UTAMA (lapis tab paling atas, lihat index.blade.php) -
     * urutan sesuai urutan diminta user 2026-09-11: Honor Kegiatan,
     * Belanja Makan & Minum, Belanja Perjalanan Dinas.
     */
    public const JENIS_OPTIONS = [
        self::JENIS_HONOR_KEGIATAN => 'Honor Kegiatan',
        self::JENIS_MAKAN_MINUM => 'Belanja Makan & Minum',
        self::JENIS_PERJALANAN_DINAS => 'Belanja Perjalanan Dinas',
    ];

    /**
     * 4 Tab Triwulan (di bawah tab utama) - teks tab lengkap berubah
     * sesuai tab utama yang aktif ("Belanja Honor Kegiatan TW-1" /
     * "Belanja Makan & Minum TW-1" / "Belanja Perjalanan Dinas TW-1"
     * dst, ditulis langsung di view lewat $labelTriwulan, bukan dari
     * konstanta ini). Label di sini dipakai untuk subjudul modal
     * Tambah/Edit.
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
