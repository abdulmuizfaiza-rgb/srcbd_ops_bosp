<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\Syncable;

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
    use Syncable;

    use HasFactory;

    protected $table = 'surat_tpg';

    public const JENIS_REKOMENDASI = 'rekomendasi';

    public const JENIS_PENGHENTIAN = 'penghentian';

    public const JENIS_PERNYATAAN = 'pernyataan';

    /**
     * Tab "Cover SMPN"/"Cover SMP-S" (permintaan user 2026-10-15) - TIDAK
     * PERNAH punya baris `surat_tpg` tersendiri (tidak ada Nomor Surat/
     * Tanggal Surat/Tahun Pelajaran yang diisi manual - seluruh isinya
     * otomatis dari Profil Sekolah + filter Tahun/Triwulan yang sudah
     * ada). Konstanta ini HANYA dipakai sebagai identitas tab pada
     * App\Livewire\PendataanOps\SuratTpg\Index::pindahTab()/
     * viewSuratPerJenis() & App\Http\Controllers\SuratTpgCetakController,
     * konsisten dengan 3 jenis surat di atas, bukan nilai kolom `jenis`
     * yang pernah benar-benar tersimpan di tabel ini.
     */
    public const JENIS_COVER_SMPN = 'cover_smpn';

    public const JENIS_COVER_SMP_S = 'cover_smp_s';

    /**
     * Tab "Lembar Validasi TPG" (BARU 2026-10-15/16, permintaan user,
     * format sesuai gambar contoh yang diupload) - SAMA seperti
     * JENIS_COVER_SMPN/JENIS_COVER_SMP_S di atas: konstanta ini HANYA
     * dipakai sbg identitas tab (pindahTab()/viewSuratPerJenis()/
     * SuratTpgCetakController), BUKAN nilai kolom `jenis` pada tabel
     * `surat_tpg` - data tab ini (Tanggal Verifikasi & Validasi, Catatan
     * Hasil Verifikasi, ceklist per-PTK) tersimpan di tabel TERPISAH
     * `lembar_validasi_tpg`/`lembar_validasi_tpg_ceklist` (lihat
     * App\Models\LembarValidasiTpg/LembarValidasiTpgCeklist), krn field
     * yang disimpan beda sama sekali dgn 3 jenis surat di atas (bukan
     * nomor_surat/tanggal_surat/tahun_pelajaran).
     */
    public const JENIS_LEMBAR_VALIDASI = 'lembar_validasi_tpg';

    /**
     * Tab "Data Cair/Belum TPG" (BARU 2026-10-16, permintaan user,
     * format sesuai gambar contoh "REKAPITULASI DATA PTK YANG SUDAH /
     * BELUM CAIR TPG") - SAMA pola dgn JENIS_LEMBAR_VALIDASI di atas:
     * konstanta ini HANYA identitas tab, BUKAN nilai kolom `jenis` pada
     * `surat_tpg` - data tab ini (ceklist status cair 12 bulan per-PTK,
     * Catatan Hasil Verifikasi) tersimpan di tabel TERPISAH
     * `rekap_cair_tpg`/`rekap_cair_tpg_bulan` (lihat App\Models\
     * RekapCairTpg/RekapCairTpgBulan).
     */
    public const JENIS_REKAP_CAIR_TPG = 'rekap_cair_tpg';

    /**
     * Daftar kolom "data" surat_tpg - dipakai semuaFieldDataKosong() utk
     * mendeteksi baris yang seluruh field datanya sudah kosong (mis.
     * user isi lalu hapus lagi lewat auto-save per keystroke), supaya
     * baris tsb otomatis dihapus & tidak nyangkut sbg baris "kosong" di
     * laporan/cek kecocokan data (permintaan user 2026-09-27).
     */
    public const FIELD_DATA = ['nomor_surat', 'tahun_pelajaran', 'tanggal_surat'];

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
     * True jika SELURUH field data (FIELD_DATA) baris ini null - dipakai
     * simpanSurat() utk auto-hapus baris "kosong" sisa auto-save per
     * keystroke (permintaan user 2026-09-27).
     */
    public function semuaFieldDataKosong(): bool
    {
        return collect(self::FIELD_DATA)->every(fn (string $field) => $this->{$field} === null);
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
