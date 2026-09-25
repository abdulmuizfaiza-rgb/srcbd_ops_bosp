<?php

namespace App\Models;

use Database\Factories\RekapRkasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rekap RKAS Awal-Perubahan - 1 baris per sekolah PER TAHUN (beda dengan
 * PendataanBosp/PendataanOps yang cuma 1 data tetap per sekolah).
 *
 * SEMUA kolom "hasil rumus" sekarang sudah punya rumus (ditentukan user
 * 2026-09-09 lanjutan ke-4 untuk 4 kategori pertama, lanjutan ke-5 untuk
 * kategori Aset Lainnya & baris JUMLAH) & dihitung otomatis oleh sistem -
 * TIDAK ADA LAGI kolom rumus yang manual:
 * - Jml Sesudah & Selisih tiap kategori (lihat KATEGORI_RUMUS) -
 *   hitungJmlSesudahDanSelisih().
 * - Sebelum/Sesudah/Selisih pada baris JUMLAH - hitungJumlahBaris().
 *
 * PENTING (permintaan user 2026-09-23): kolom "anggaran_bosp" SEKARANG
 * OTOMATIS juga - diambil dari "Total Penerimaan BOSP Setahun" pada menu
 * Dana BOSP Tahap 1 & 2 - Penerimaan BOSP (App\Models\DanaBospTahap,
 * kolom total_penerimaan_setahun) untuk sekolah+tahun yang sama, BUKAN
 * input manual lagi (lihat anggaranBospOtomatis() di bawah). Kolom ini
 * TETAP disimpan (write-through, bukan accessor virtual, konsisten dengan
 * pola PPK pada RincianBelanjaModalBmd) supaya tetap 1 sumber kebenaran
 * yang bisa dibaca langsung dari database. Selama Dana BOSP Tahap -
 * Penerimaan BOSP sekolah tsb BELUM diisi (jumlah_siswa/
 * jumlah_dana_bosp_per_tahun masih kosong), menu Rekap RKAS Awal-
 * Perubahan untuk sekolah+tahun itu TERKUNCI (lihat
 * Livewire\PendataanBosp\RekapRkas\Index::danaBospTahapTerisi()) - Admin
 * BOSP wajib mengisi Dana BOSP Tahap dulu sebelum bisa mengisi Rekap RKAS.
 */
#[Fillable([
    'profil_sekolah_id',
    'tahun',
    'anggaran_bosp',
    'pegawai_sebelum',
    'pegawai_realisasi_tahap1',
    'pegawai_perubahan_tahap2',
    'pegawai_jml_sesudah',
    'pegawai_selisih',
    'pemeliharaan_sebelum',
    'pemeliharaan_realisasi_tahap1',
    'pemeliharaan_perubahan_tahap2',
    'pemeliharaan_jml_sesudah',
    'pemeliharaan_selisih',
    'barjas_sebelum',
    'barjas_realisasi_tahap1',
    'barjas_perubahan_tahap2',
    'barjas_jml_sesudah',
    'barjas_selisih',
    'peralatan_mesin_sebelum',
    'peralatan_mesin_realisasi_tahap1',
    'peralatan_mesin_perubahan_tahap2',
    'peralatan_mesin_jml_sesudah',
    'peralatan_mesin_selisih',
    'aset_lainnya_sebelum',
    'aset_lainnya_realisasi_tahap1',
    'aset_lainnya_perubahan_tahap2',
    'aset_lainnya_jml_sesudah',
    'aset_lainnya_selisih',
    'jumlah_sebelum',
    'jumlah_sesudah',
    'jumlah_selisih',
    'created_by',
])]
class RekapRkas extends Model
{
    /** @use HasFactory<RekapRkasFactory> */
    use HasFactory;

    protected $table = 'rekap_rkas';

    /**
     * 5 kategori Belanja pada tabel Rekap RKAS, dalam urutan tampil sesuai
     * file yang diunggah user - masing-masing punya 5 kolom yang sama:
     * {kategori}_sebelum, _realisasi_tahap1, _perubahan_tahap2,
     * _jml_sesudah, _selisih.
     *
     * 'sebelum' - teks tambahan pada judul kolom "Sebelum" tiap kategori
     * (mis. "Sebelum B.Peg"), dan 'warna' - class Tailwind warna latar
     * kolom kategori tsb pada tabel (permintaan user 2026-09-09 lanjutan
     * ke-2, supaya posisi kolom lebih mudah dibedakan secara visual).
     */
    public const KATEGORI = [
        'pegawai' => ['label' => 'Belanja Pegawai', 'singkatan' => 'Bpeg', 'sebelum' => 'B.Peg', 'warna' => 'bg-yellow-100'],
        'pemeliharaan' => ['label' => 'Belanja Pemeliharaan', 'singkatan' => 'Bpem', 'sebelum' => 'B.Pem', 'warna' => 'bg-blue-100'],
        'barjas' => ['label' => 'Belanja Barang dan Jasa', 'singkatan' => 'B.Barjas', 'sebelum' => 'B.Barjas', 'warna' => 'bg-green-100'],
        'peralatan_mesin' => ['label' => 'BELANJA MODAL PERALATAN & MESIN', 'singkatan' => 'BPM', 'sebelum' => 'BPM', 'warna' => 'bg-orange-100'],
        'aset_lainnya' => ['label' => 'BELANJA MODAL ASET LAINNYA', 'singkatan' => 'BAL', 'sebelum' => 'BAL', 'warna' => 'bg-purple-100'],
    ];

    /**
     * Warna latar kolom 29-31 (baris JUMLAH: Sebelum/Sesudah/Selisih
     * Belanja) - "Kuning" (bukan "Kuning Muda"), sengaja beda tingkat
     * dari bg-yellow-100 milik kategori Belanja Pegawai supaya kedua
     * kelompok kolom kuning tetap bisa dibedakan.
     */
    public const WARNA_JUMLAH = 'bg-yellow-300';

    /**
     * Kategori yang Jml Sesudah & Selisih-nya sudah punya rumus (ditentukan
     * user 2026-09-09 lanjutan ke-4 & ke-5) - kolom-kolom ini TIDAK LAGI
     * kotak input manual, sekarang selalu dihitung otomatis (lihat
     * hitungJmlSesudahDanSelisih()) baik lewat input langsung di kotak
     * tabel maupun lewat form modal "Isi/Edit".
     *
     * SEMUA 5 kategori (termasuk 'aset_lainnya', ditambahkan lanjutan
     * ke-5) kini punya rumus yang sama - dibuat dari array_keys(KATEGORI)
     * supaya otomatis ikut kalau suatu saat ada kategori baru yang rumusnya
     * SUDAH ditentukan user sejak awal. JANGAN mengecualikan kategori
     * manapun dari sini kecuali user eksplisit menyatakan kategori itu
     * belum punya rumus / masih manual.
     */
    public const KATEGORI_RUMUS = ['pegawai', 'pemeliharaan', 'barjas', 'peralatan_mesin', 'aset_lainnya'];

    /**
     * Menghitung Jml Sesudah & Selisih untuk kategori yang rumusnya sudah
     * ditentukan (lihat KATEGORI_RUMUS) - dipakai bareng oleh input
     * langsung di kotak tabel (lihat Livewire\PendataanBosp\RekapRkas\
     * Index::updated()), form modal "Isi/Edit" (lihat method simpan() di
     * komponen yang sama), dan migration backfill data lama, supaya
     * rumusnya konsisten dihitung dari 1 tempat saja.
     *
     * Rumus (ditentukan user 2026-09-09 lanjutan ke-4, berlaku untuk
     * semua kategori di KATEGORI_RUMUS):
     * - Jml Sesudah = Realisasi Tahap 1 + Perubahan Tahap 2
     * - Selisih     = Sebelum - Jml Sesudah
     *
     * Nilai yang belum diisi (null) dianggap 0 untuk perhitungan ini -
     * hasilnya SELALU berupa angka (integer), tidak pernah null, karena
     * kedua kolom ini sekarang murni hasil hitung, bukan input manual lagi.
     *
     * @return array{0: int, 1: int} [jmlSesudah, selisih]
     */
    public static function hitungJmlSesudahDanSelisih(?int $sebelum, ?int $realisasiTahap1, ?int $perubahanTahap2): array
    {
        $jmlSesudah = (int) $realisasiTahap1 + (int) $perubahanTahap2;
        $selisih = (int) $sebelum - $jmlSesudah;

        return [$jmlSesudah, $selisih];
    }

    /**
     * Menghitung Sebelum/Sesudah/Selisih pada baris JUMLAH (kolom
     * jumlah_sebelum/jumlah_sesudah/jumlah_selisih) - rumus ditentukan
     * user 2026-09-09 lanjutan ke-5:
     * - Sebelum = total Sebelum SELURUH kategori Belanja (5 kategori)
     * - Sesudah = total Jml Sesudah SELURUH kategori Belanja (5 kategori)
     * - Selisih = Sebelum - Sesudah
     *
     * Dipakai bareng oleh input langsung di kotak tabel, form modal
     * "Isi/Edit", dan migration backfill data lama.
     *
     * @param  array<string, int|null>  $sebelumPerKategori  kunci = nama kategori (KATEGORI), nilai = {kategori}_sebelum
     * @param  array<string, int|null>  $jmlSesudahPerKategori  kunci = nama kategori (KATEGORI), nilai = {kategori}_jml_sesudah
     * @return array{0: int, 1: int, 2: int} [jumlahSebelum, jumlahSesudah, jumlahSelisih]
     */
    public static function hitungJumlahBaris(array $sebelumPerKategori, array $jmlSesudahPerKategori): array
    {
        $jumlahSebelum = array_sum(array_map(fn ($nilai) => (int) $nilai, $sebelumPerKategori));
        $jumlahSesudah = array_sum(array_map(fn ($nilai) => (int) $nilai, $jmlSesudahPerKategori));
        $jumlahSelisih = $jumlahSebelum - $jumlahSesudah;

        return [$jumlahSebelum, $jumlahSesudah, $jumlahSelisih];
    }

    /**
     * Anggaran BOSP {tahun} OTOMATIS = Total Penerimaan BOSP Setahun pada
     * Dana BOSP Tahap 1 & 2 - Penerimaan BOSP sekolah+tahun yang sama
     * (permintaan user 2026-09-23, MENGGANTI keputusan sebelumnya yang
     * manual). Null kalau Dana BOSP Tahap sekolah itu belum ada/belum
     * diisi - lihat docblock kelas di atas & Livewire\PendataanBosp\
     * RekapRkas\Index::danaBospTahapTerisi() untuk gate-nya.
     */
    public static function anggaranBospOtomatis(?int $totalPenerimaanSetahun): ?int
    {
        return $totalPenerimaanSetahun;
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
