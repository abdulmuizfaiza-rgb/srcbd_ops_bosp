<?php

namespace App\Models;

use Database\Factories\LaporanRealisasiBospFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Laporan Realisasi BOSP (Form BPK) - 1 baris per sekolah, per tahun,
 * per triwulan (4 baris tetap: Triwulan 1-4) - lihat migration
 * `create_laporan_realisasi_bosp_table` untuk konteks lengkap
 * (permintaan user 2026-09-17, Part 32).
 */
#[Fillable([
    'profil_sekolah_id',
    'tahun',
    'triwulan',
    'saldo_awal_dana_bosp',
    'penerimaan_dana_bos',
    'total_penerimaan',
    'belanja_barang_pakai_habis_persediaan',
    'jasa_tenaga_pendidik_dan_kependidikan',
    'daya_dan_jasa',
    'pemeliharaan',
    'upah_pemeliharaan',
    'biaya_pendaftaran_lomba_bimtek_workshop',
    'honor_kegiatan',
    'makan_dan_minum_kegiatan',
    'perjalanan_dinas',
    'total_belanja_barang_dan_jasa',
    'peralatan_dan_mesin_kib_b',
    'aset_tetap_lainnya_kib_e',
    'total_belanja_modal',
    'total_realisasi_dana_bos',
    'sisa_dana_bos',
    'saldo_rekening_kas_bank',
    'saldo_kas_tunai',
    'verifikasi_jumlah',
    'verifikasi_saldo',
    'created_by',
])]
class LaporanRealisasiBosp extends Model
{
    /** @use HasFactory<LaporanRealisasiBospFactory> */
    use HasFactory;

    protected $table = 'laporan_realisasi_bosp';

    public const TRIWULAN_OPTIONS = [
        1 => 'Triwulan 1',
        2 => 'Triwulan 2',
        3 => 'Triwulan 3',
        4 => 'Triwulan 4',
    ];

    /**
     * Kolom 8-10 - Saldo Awal & Penerimaan. SELURUH TIGA kolom ini SUDAH
     * JADI HASIL RUMUS/read-only sejak permintaan user 2026-09-17
     * (lanjutan Part 32 ketujuh) - lihat catatan lengkap di
     * FIELD_KOMPUTASI_RINCIAN di bawah. Array ini TETAP dipertahankan
     * (bukan dihapus) karena masih dipakai untuk urutan kolom di
     * FIELD_MANUAL (pola sama dengan FIELD_BELANJA_BARANG_JASA/
     * FIELD_BELANJA_MODAL/FIELD_LAIN yang juga tetap ada walau seluruh
     * isinya sudah pindah ke FIELD_KOMPUTASI_RINCIAN).
     */
    public const FIELD_PENERIMAAN = [
        'saldo_awal_dana_bosp',
        'penerimaan_dana_bos',
        'total_penerimaan',
    ];

    /**
     * Kolom 11-19 - Belanja Barang dan Jasa, urutan sesuai gambar
     * contoh (BUKAN termasuk kolom 20 "Total" - lihat
     * FIELD_TOTAL_BELANJA_BARANG_JASA).
     */
    public const FIELD_BELANJA_BARANG_JASA = [
        'belanja_barang_pakai_habis_persediaan',
        'jasa_tenaga_pendidik_dan_kependidikan',
        'daya_dan_jasa',
        'pemeliharaan',
        'upah_pemeliharaan',
        'biaya_pendaftaran_lomba_bimtek_workshop',
        'honor_kegiatan',
        'makan_dan_minum_kegiatan',
        'perjalanan_dinas',
    ];

    /**
     * Kolom 11 (belanja_barang_pakai_habis_persediaan) - PENGECUALIAN
     * KEDUA dari "rumus-rumus nya nanti menyusul" (setelah kolom 28/29),
     * ditentukan eksplisit oleh user (permintaan 2026-09-17 lanjutan
     * Part 32): nilainya diambil dari total SUM kolom "Total Harga" pada
     * menu Rincian Belanja Barang Habis Pakai (App\Models\RincianBelanjaBarangHabisPakai),
     * dikelompokkan per sekolah+tahun+triwulan yang SAMA dengan baris
     * Laporan Realisasi BOSP ini.
     *
     * Kolom 12 (jasa_tenaga_pendidik_dan_kependidikan) & kolom 13
     * (daya_dan_jasa) - PENGECUALIAN KETIGA & KEEMPAT, permintaan user
     * 2026-09-17 (lanjutan Part 32 kedua): kolom 12 diambil dari total
     * SUM kolom "Jumlah Honor Yang Diterima" (App\Models\PenerimaanHonorPtk::jumlah_honor)
     * pada menu Penerimaan Honor PTK, kolom 13 diambil dari total SUM
     * kolom "Jumlah" (App\Models\LanggananDayaJasa::jumlah) pada menu
     * Daya & Jasa - keduanya dikelompokkan per sekolah+tahun+triwulan
     * yang SAMA, pola PERSIS sama dengan kolom 11 di atas.
     *
     * Kolom 14 (pemeliharaan) & kolom 15 (upah_pemeliharaan) -
     * PENGECUALIAN KELIMA & KEENAM, permintaan user 2026-09-17
     * (lanjutan Part 32 ketiga): BEDA dari kolom 11-13 di atas, kedua
     * kolom ini masing-masing dijumlah dari DUA menu sumber sekaligus
     * (ditambahkan/dijumlahkan, bukan salah satu saja):
     * - Kolom 14 (pemeliharaan) = SUM "Total Harga"
     *   (App\Models\RincianPemeliharaan::total_harga, WHERE jenis =
     *   RincianPemeliharaan::JENIS_BARANG) + SUM "Total Harga"
     *   (App\Models\RincianPemeliharaanPc::total_harga, WHERE jenis =
     *   RincianPemeliharaanPc::JENIS_BARANG).
     * - Kolom 15 (upah_pemeliharaan) = SUM "Total Harga"
     *   (App\Models\RincianPemeliharaan::total_harga, WHERE jenis =
     *   RincianPemeliharaan::JENIS_JASA) + SUM "Total Harga"
     *   (App\Models\RincianPemeliharaanPc::total_harga, WHERE jenis =
     *   RincianPemeliharaanPc::JENIS_JASA).
     * Keduanya tetap dikelompokkan per sekolah+tahun+triwulan yang SAMA
     * dengan baris Laporan Realisasi BOSP ini.
     *
     * Kolom 16 (biaya_pendaftaran_lomba_bimtek_workshop), kolom 17
     * (honor_kegiatan), kolom 18 (makan_dan_minum_kegiatan), & kolom 19
     * (perjalanan_dinas) - PENGECUALIAN KETUJUH s.d. KESEPULUH,
     * permintaan user 2026-09-17 (lanjutan Part 32 keempat): pola SATU
     * menu sumber (seperti kolom 11-13, BUKAN pola dijumlah 2 menu
     * seperti kolom 14/15):
     * - Kolom 16 (biaya_pendaftaran_lomba_bimtek_workshop) = SUM
     *   "Jumlah" (App\Models\BiayaPendaftaranLomba::jumlah) pada menu
     *   Biaya Pendaftaran Lomba/Bimtek/Workshop.
     * - Kolom 17 (honor_kegiatan) = SUM "Jumlah"
     *   (App\Models\BelanjaHonorKegiatan::jumlah, WHERE jenis =
     *   BelanjaHonorKegiatan::JENIS_HONOR_KEGIATAN) pada menu Belanja
     *   Honor Kegiatan & Makan Minum Kegiatan & Perjalanan Dinas, tab
     *   utama "Honor Kegiatan".
     * - Kolom 18 (makan_dan_minum_kegiatan) = SUM "Jumlah"
     *   (App\Models\BelanjaHonorKegiatan::jumlah, WHERE jenis =
     *   BelanjaHonorKegiatan::JENIS_MAKAN_MINUM) pada menu yang SAMA,
     *   tab utama "Belanja Makan & Minum".
     * - Kolom 19 (perjalanan_dinas) = SUM "Jumlah"
     *   (App\Models\BelanjaHonorKegiatan::jumlah, WHERE jenis =
     *   BelanjaHonorKegiatan::JENIS_PERJALANAN_DINAS) pada menu yang
     *   SAMA, tab utama "Belanja Perjalanan Dinas".
     * Kolom 17/18/19 BEDA dari kolom 16 (menu tunggal): ketiganya
     * berasal dari SATU model/tabel yang sama (`belanja_honor_kegiatan`)
     * dibedakan lewat kolom `jenis` (pola identik Rincian Pemeliharaan
     * pada kolom 14/15) - TAPI di sini TIDAK dijumlah bersama, masing-
     * masing kolom Laporan Realisasi hanya mengambil SATU nilai `jenis`
     * tertentu (bukan menjumlah seluruh `jenis` sekaligus). Keempat
     * kolom tetap dikelompokkan per sekolah+tahun+triwulan yang SAMA
     * dengan baris Laporan Realisasi BOSP ini.
     *
     * Kolom 20 (total_belanja_barang_dan_jasa), kolom 21
     * (peralatan_dan_mesin_kib_b), kolom 22 (aset_tetap_lainnya_kib_e),
     * kolom 23 (total_belanja_modal), kolom 24 (total_realisasi_dana_bos),
     * & kolom 25 (sisa_dana_bos) - PENGECUALIAN KESEBELAS s.d.
     * KEENAM BELAS, permintaan user 2026-09-17 (lanjutan Part 32
     * kelima). BEDA dari kolom 11-19/21/22 (yang masing-masing dari 1
     * atau 2 menu sumber EKSTERNAL), kolom 20/23/24/25 adalah RUMUS
     * GABUNGAN dari kolom-kolom LAIN yang sudah dihitung lebih dulu
     * (baik dari FIELD_KOMPUTASI_RINCIAN lain maupun dari kolom manual
     * pada baris yang sama):
     * - Kolom 21 (peralatan_dan_mesin_kib_b) = SUM "Total Harga"
     *   (App\Models\RincianBelanjaModal::total_harga, WHERE jenis =
     *   RincianBelanjaModal::JENIS_PERALATAN_MESIN) pada menu Rincian
     *   Belanja Modal, tab utama "Belanja Modal Peralatan & Mesin (KIB
     *   B)". Kolom 22 (aset_tetap_lainnya_kib_e) = pola sama, WHERE
     *   jenis = JENIS_ASET_TETAP_LAINNYA, tab utama "Belanja Modal Aset
     *   Tetap Lainnya (KIB E)". Keduanya menu sumber TUNGGAL, sama
     *   persis pola kolom 11-13/16 (bukan 2 menu dijumlah seperti kolom
     *   14/15).
     * - Kolom 20 (total_belanja_barang_dan_jasa) = kolom 12 + 13 + 14 +
     *   15 + 16 + 17 + 18 + 19 (jawaban user verbatim: "kolom Jasa
     *   Tenaga Pendidik dan Kependidikan ditambah kolom Daya dan Jasa
     *   ditambah kolom Pemeliharaan ditambah kolom Upah Pemeliharaan
     *   ditambah kolom Biaya Pendaftaran Lomba/Bimtek/Workshop ditambah
     *   kolom Honor Kegiatan ditambah kolom Makan dan Minum Kegiatan
     *   ditambah kolom Perjalanan Dinas"). PENTING: kolom 11 (Belanja
     *   Barang Pakai Habis/Persediaan) SENGAJA TIDAK ikut dijumlah di
     *   sini - user TIDAK menyebutkannya pada rumus kolom 20 ini secara
     *   eksplisit, JANGAN ditambahkan sendiri walau namanya "Total
     *   Belanja Barang dan Jasa" terkesan mencakup seluruh 9 item grup
     *   itu. Kolom 11 baru digabungkan kembali secara terpisah pada
     *   rumus kolom 24 di bawah - sesuai instruksi literal user.
     * - Kolom 23 (total_belanja_modal) = kolom 21 + kolom 22.
     * - Kolom 24 (total_realisasi_dana_bos) = kolom 11 + kolom 20 +
     *   kolom 23.
     * - Kolom 25 (sisa_dana_bos) = kolom 10 (total_penerimaan, MASIH
     *   MANUAL - lihat FIELD_PENERIMAAN) DIKURANGI kolom 24. BEDA dari
     *   seluruh kolom lain di FIELD_KOMPUTASI_RINCIAN: kolom 10 di sini
     *   BUKAN dari menu LAIN, melainkan kolom manual pada tabel
     *   `laporan_realisasi_bosp` ITU SENDIRI (baris yang sama persis) -
     *   lihat ambilTotalSisaDanaBos() di bawah.
     * Kolom 20/23/24/25 dihitung dengan memanggil ULANG
     * ambilTotalKomputasiRincian() untuk kolom-kolom sumbernya (BUKAN
     * query baru ke tabel lain) - single source of truth, supaya rumus
     * gabungan ini otomatis tetap benar walau cara menghitung salah
     * satu kolom sumbernya berubah di masa depan.
     *
     * Jawaban AskUserQuestion 2026-09-17 (berlaku untuk KESELURUH BELAS
     * kolom di atas, kolom 11-19 & kolom 20-25): kotak kolom ini
     * READ-ONLY (tidak bisa diedit manual dari kotak tabel manapun -
     * SAMA seperti kolom 28/29) & nilainya DIHITUNG REAL-TIME setiap
     * dibuka (TIDAK disimpan/di-cache ke kolom database masing-masing -
     * kolom itu tetap ada di tabel karena bagian dari FIELD_MANUAL di
     * bawah untuk keperluan urutan & jumlah kolom tabel, TAPI TIDAK
     * PERNAH ditulis lagi lewat jalur manapun sejak perubahan ini -
     * lihat ambilTotalKomputasiRincian()/ambilTotalKomputasiRincianSemuaTriwulan()
     * & method ambilTotal...() masing-masing kolom di bawah, dipakai
     * oleh Livewire\PendataanBosp\LaporanRealisasiBosp\Index untuk
     * menimpa nilai tampilan kolom-kolom ini setiap render()).
     *
     * Kolom 26 (saldo_rekening_kas_bank) & kolom 27 (saldo_kas_tunai) -
     * ditambahkan ke daftar ini sejak permintaan user 2026-09-17
     * (lanjutan Part 32 keenam): SEBELUMNYA manual (bagian dari
     * FIELD_LAIN), SEKARANG READ-ONLY & real-time juga, diambil
     * OTOMATIS dari App\Models\DanaBospTahap (lihat catatan lengkap di
     * FIELD_LAIN di atas & ambilTotalSaldoRekeningKasBank()/
     * ambilTotalSaldoKasTunai() di bawah) - PENTING: kolom 28/29
     * (Jumlah/Verifikasi Saldo) yang sumbernya kolom 25/26/27 ikut
     * berubah otomatis akurat karena ketiganya sekarang sama-sama hasil
     * rumus real-time (lihat ambilVerifikasiSaldo() di bawah, yang juga
     * ikut diubah supaya tidak lagi membaca kolom 26/27 dari kolom
     * database `laporan_realisasi_bosp` yang sudah vestigial).
     *
     * Kolom 8 (saldo_awal_dana_bosp), kolom 9 (penerimaan_dana_bos), &
     * kolom 10 (total_penerimaan) - ditambahkan ke daftar ini sejak
     * permintaan user 2026-09-17 (lanjutan Part 32 ketujuh) - INI ADALAH
     * KOLOM MANUAL TERAKHIR yang tersisa di seluruh menu ini, sehingga
     * sejak perubahan ini SELURUH tabel `laporan_realisasi_bosp` sudah
     * READ-ONLY/otomatis (tidak ada lagi jalur `updateOrCreate()` yang
     * akan pernah dijalankan pada model ini - lihat catatan
     * Livewire\PendataanBosp\LaporanRealisasiBosp\Index::updated()).
     * BEDA dari kolom-kolom komputasi lain di atas: kolom 8-10 ini adalah
     * SATU-SATUNYA yang rumusnya MEREKURSI ANTAR TRIWULAN (bukan hanya
     * antar kolom pada triwulan yang sama):
     * - Kolom 8 (saldo_awal_dana_bosp) TW 1 = field
     *   `saldo_bosp_tw4_tahun_sebelumnya` pada App\Models\DanaBospTahap
     *   (menu Dana BOSP Tahap 1 & 2, tab "Tarik Tunai BOSP") - SATU-
     *   SATUNYA triwulan yang sumbernya dari MENU LAIN (eksternal).
     * - Kolom 8 (saldo_awal_dana_bosp) TW 2/3/4 = kolom 25 (Sisa Dana
     *   BOS) pada TRIWULAN SEBELUMNYA (TW 1/2/3 secara berurutan) -
     *   BUKAN triwulan yang sama seperti kolom komputasi lain manapun di
     *   atas. Rekursi ini BERHENTI di TW 1 (sumber eksternal di atas),
     *   TIDAK PERNAH infinite loop.
     * - Kolom 9 (penerimaan_dana_bos) TW 1 = field `penerimaan_tahap_1`
     *   pada DanaBospTahap (menu Dana BOSP Tahap 1 & 2, tab "Penerimaan
     *   BOSP"). TW 3 = field `penerimaan_tahap_2` pada menu yang SAMA.
     *   TW 2 & TW 4 TIDAK PERNAH ada penerimaan Dana BOSP (jawaban user
     *   verbatim) - SELALU 0 (kotak non-aktif, tetap tampil "Rp 0" gaya
     *   sama seperti kolom otomatis lain, jawaban AskUserQuestion
     *   "Tampilkan 'Rp 0' (Recommended)").
     * - Kolom 10 (total_penerimaan) = kolom 8 (Saldo Awal Dana BOSP TW
     *   ybs) + kolom 9 (Penerimaan Dana BOS TW ybs), UNTUK SETIAP
     *   triwulan (TW1/2/3/4 rumusnya identik, hanya sumber kolom 8/9
     *   yang berbeda per TW seperti di atas).
     * Karena kolom 25 (Sisa Dana BOS) = kolom 10 (Total Penerimaan) -
     * kolom 24 (Total Realisasi Dana BOS), sedangkan kolom 10 kini
     * tergantung kolom 8 yang (untuk TW>1) tergantung kolom 25 TW
     * SEBELUMNYA, seluruh rantai (kolom 8/9/10/25 utk 4 triwulan)
     * DIHITUNG BERSAMA dalam SATU method privat
     * (hitungRantaiPenerimaanDanSisaSemuaTriwulan()) supaya tidak ada
     * pemanggilan dispatcher yang saling merekursi tanpa henti - lihat
     * catatan lengkap di method tsb & ambilTotalSaldoAwalDanaBosp() dst
     * di bawah, serta ambilTotalSisaDanaBos()/...SemuaTriwulan() yang
     * SEKARANG ikut dipindah memakai method privat yang sama (SEBELUMNYA
     * membaca kolom `total_penerimaan` manual langsung dari database).
     */
    public const FIELD_KOMPUTASI_RINCIAN = [
        'saldo_awal_dana_bosp',
        'penerimaan_dana_bos',
        'total_penerimaan',
        'belanja_barang_pakai_habis_persediaan',
        'jasa_tenaga_pendidik_dan_kependidikan',
        'daya_dan_jasa',
        'pemeliharaan',
        'upah_pemeliharaan',
        'biaya_pendaftaran_lomba_bimtek_workshop',
        'honor_kegiatan',
        'makan_dan_minum_kegiatan',
        'perjalanan_dinas',
        'total_belanja_barang_dan_jasa',
        'peralatan_dan_mesin_kib_b',
        'aset_tetap_lainnya_kib_e',
        'total_belanja_modal',
        'total_realisasi_dana_bos',
        'sisa_dana_bos',
        'saldo_rekening_kas_bank',
        'saldo_kas_tunai',
    ];

    /**
     * Kolom 20 - SUDAH JADI HASIL RUMUS (bukan manual lagi) sejak
     * permintaan user 2026-09-17 (lanjutan Part 32 kelima) - lihat
     * catatan lengkap di FIELD_KOMPUTASI_RINCIAN di atas.
     */
    public const FIELD_TOTAL_BELANJA_BARANG_JASA = 'total_belanja_barang_dan_jasa';

    /**
     * Kolom 21-22 - Belanja Modal (BUKAN termasuk kolom 23 "Total").
     * SUDAH JADI HASIL RUMUS/read-only (bukan manual lagi) sejak
     * permintaan user 2026-09-17 (lanjutan Part 32 kelima) - lihat
     * catatan lengkap di FIELD_KOMPUTASI_RINCIAN di atas.
     */
    public const FIELD_BELANJA_MODAL = [
        'peralatan_dan_mesin_kib_b',
        'aset_tetap_lainnya_kib_e',
    ];

    /**
     * Kolom 23 - SUDAH JADI HASIL RUMUS (bukan manual lagi) sejak
     * permintaan user 2026-09-17 (lanjutan Part 32 kelima) - lihat
     * catatan lengkap di FIELD_KOMPUTASI_RINCIAN di atas.
     */
    public const FIELD_TOTAL_BELANJA_MODAL = 'total_belanja_modal';

    /**
     * Kolom 24-27 - Total Realisasi, Sisa Dana BOS, Saldo Rekening/Kas
     * Bank, Saldo Kas Tunai. SELURUH EMPAT kolom ini SUDAH JADI HASIL
     * RUMUS/otomatis (bukan manual lagi): kolom 24 & 25 sejak permintaan
     * user 2026-09-17 (lanjutan Part 32 kelima), kolom 26 & 27 sejak
     * permintaan user 2026-09-17 (lanjutan Part 32 keenam) - lihat
     * catatan lengkap di FIELD_KOMPUTASI_RINCIAN di atas.
     *
     * Kolom 26 (saldo_rekening_kas_bank) & kolom 27 (saldo_kas_tunai) -
     * PENGECUALIAN KETUJUH BELAS & KEDELAPAN BELAS, permintaan user
     * 2026-09-17 (lanjutan Part 32 keenam): nilainya diambil OTOMATIS
     * dari App\Models\DanaBospTahap, field `saldo_kas_bank_tw{triwulan}`
     * (kolom 26) & `saldo_kas_tunai_tw{triwulan}` (kolom 27) pada menu
     * Dana BOSP Tahap 1 & 2, tab "Tarik Tunai BOSP" - dicocokkan per
     * sekolah+tahun yang SAMA (DanaBospTahap 1 baris per sekolah PER
     * TAHUN, field TW1-4 sudah terpisah per kolom, BUKAN per triwulan
     * seperti tabel `laporan_realisasi_bosp` ini, jadi TIDAK perlu
     * filter triwulan di query - cukup pilih kolom `_tw{triwulan}` yang
     * sesuai). Pola BEDA dari seluruh kolom komputasi lain (kolom
     * 11-13/16/21/22 dll) yang sumbernya SELALU difilter `tahun` DAN
     * `triwulan` yang sama pada tabel sumbernya - di sini sumbernya
     * (DanaBospTahap) TIDAK punya kolom `triwulan` sama sekali (lihat
     * ambilTotalSaldoRekeningKasBank()/ambilTotalSaldoKasTunai() di
     * bawah).
     */
    public const FIELD_LAIN = [
        'total_realisasi_dana_bos',
        'sisa_dana_bos',
        'saldo_rekening_kas_bank',
        'saldo_kas_tunai',
    ];

    /**
     * SELURUH field pada kolom 8-27 - dipakai untuk urutan & jumlah
     * kolom tabel (thead, colspan, loop tbody) di seluruh blade menu
     * ini. TIDAK SEMUA field di sini benar-benar bisa diedit manual lagi
     * lewat kotak tabel - kolom 11-25 (lihat FIELD_KOMPUTASI_RINCIAN di
     * atas, sekarang berjumlah 15 field) TETAP ada di array ini (supaya
     * kolom tabelnya tidak hilang/urutan tidak berantakan), TAPI sudah
     * read-only sejak permintaan user 2026-09-17 (lanjutan Part 32, Part
     * 32 kedua, ketiga, keempat, & kelima) -
     * Livewire\PendataanBosp\LaporanRealisasiBosp\Index::updated() &
     * blade tbody WAJIB mengecek FIELD_KOMPUTASI_RINCIAN secara
     * terpisah, JANGAN anggap "ada di FIELD_MANUAL" = "boleh diedit".
     * Hanya kolom 8-10 (FIELD_PENERIMAAN) yang MASIH BENAR-BENAR MANUAL
     * sejak permintaan user 2026-09-17 (lanjutan Part 32 keenam) - kolom
     * 26-27 (bagian dari FIELD_LAIN) SUDAH ikut pindah ke
     * FIELD_KOMPUTASI_RINCIAN pada ronde ini (lihat catatan lengkap di
     * FIELD_LAIN & FIELD_KOMPUTASI_RINCIAN di atas). TIDAK termasuk
     * kolom 28 & 29 (FIELD_RUMUS di bawah), yang SELALU dihitung ulang
     * REAL-TIME dan tidak pernah bisa ditimpa manual.
     */
    public const FIELD_MANUAL = [
        ...self::FIELD_PENERIMAAN,
        ...self::FIELD_BELANJA_BARANG_JASA,
        self::FIELD_TOTAL_BELANJA_BARANG_JASA,
        ...self::FIELD_BELANJA_MODAL,
        self::FIELD_TOTAL_BELANJA_MODAL,
        ...self::FIELD_LAIN,
    ];

    /**
     * Kolom 28 & 29 - HASIL RUMUS, TIDAK PERNAH bisa diedit manual dari
     * jalur manapun (kotak tabel maupun lainnya). Rumusnya ditentukan
     * eksplisit oleh user pada jawaban AskUserQuestion 2026-09-17 (lihat
     * migration create_laporan_realisasi_bosp_table & hitungVerifikasiSaldo()
     * di bawah) - PENGECUALIAN dari "rumus-rumus nanti menyusul" sejak
     * Part 32 awal.
     *
     * BERUBAH sejak permintaan user 2026-09-17 (lanjutan Part 32 kelima,
     * jawaban AskUserQuestion "Real-time setiap render (Recommended)"):
     * SEBELUMNYA kolom ini disimpan/dimaterialize ke database & hanya
     * dihitung ulang saat kolom 25/26/27 diedit manual - SEKARANG SELALU
     * dihitung ulang REAL-TIME setiap render lewat ambilVerifikasiSaldo()/
     * ambilVerifikasiSaldoSemuaTriwulan() di bawah, TIDAK PERNAH disimpan
     * lagi ke kolom database `verifikasi_jumlah`/`verifikasi_saldo`
     * (kolom itu tetap ada di skema tapi jadi vestigial sejak sekarang,
     * sama seperti kolom-kolom di FIELD_KOMPUTASI_RINCIAN) - KARENA kolom
     * 25 (Sisa Dana BOS, salah satu input rumus ini) sendiri sekarang
     * jadi hasil rumus otomatis (lihat ambilTotalSisaDanaBos()) yang
     * tidak lagi pernah disimpan lewat edit manual, sehingga
     * membandingkan kolom 25 dengan nilai LAMA/basi di database tidak
     * lagi valid.
     */
    public const FIELD_RUMUS = [
        'verifikasi_jumlah',
        'verifikasi_saldo',
    ];

    /** Label kolom (nomor sesuai urutan gambar contoh), dipakai bareng oleh blade & export. */
    public const LABEL_KOLOM = [
        'saldo_awal_dana_bosp' => 'Saldo Awal Dana BOSP',
        'penerimaan_dana_bos' => 'Penerimaan Dana BOS',
        'total_penerimaan' => 'Total Penerimaan',
        'belanja_barang_pakai_habis_persediaan' => 'Belanja Barang Pakai Habis/Persediaan',
        'jasa_tenaga_pendidik_dan_kependidikan' => 'Jasa Tenaga Pendidik dan Kependidikan',
        'daya_dan_jasa' => 'Daya dan Jasa',
        'pemeliharaan' => 'Pemeliharaan',
        'upah_pemeliharaan' => 'Upah Pemeliharaan',
        'biaya_pendaftaran_lomba_bimtek_workshop' => 'Biaya Pendaftaran Lomba/Bimtek/Workshop',
        'honor_kegiatan' => 'Honor Kegiatan',
        'makan_dan_minum_kegiatan' => 'Makan dan Minum Kegiatan',
        'perjalanan_dinas' => 'Perjalanan Dinas',
        'total_belanja_barang_dan_jasa' => 'Total Belanja Barang dan Jasa',
        'peralatan_dan_mesin_kib_b' => 'Peralatan dan Mesin KIB B',
        'aset_tetap_lainnya_kib_e' => 'Aset Tetap Lainnya KIB E',
        'total_belanja_modal' => 'Total Belanja Modal',
        'total_realisasi_dana_bos' => 'Total Realisasi Dana BOS',
        'sisa_dana_bos' => 'Sisa Dana BOS',
        'saldo_rekening_kas_bank' => 'Saldo Rekening/Kas Bank',
        'saldo_kas_tunai' => 'Saldo Kas Tunai',
        'verifikasi_jumlah' => 'Jumlah (Saldo Rekening/Kas Bank + Saldo Kas Tunai)',
        'verifikasi_saldo' => 'Verifikasi Saldo',
    ];

    /** Hasil teks kolom 29 kalau kolom 25 SAMA DENGAN kolom 28. */
    public const VERIFIKASI_SAMA = 'SAMA';

    /** Hasil teks kolom 29 kalau kolom 25 BERBEDA DENGAN kolom 28. */
    public const VERIFIKASI_TIDAK_SAMA = 'TIDAK SAMA';

    /**
     * Menghitung kolom 28 (Jumlah) & kolom 29 (Verifikasi Saldo) - dipakai
     * oleh ambilVerifikasiSaldo()/ambilVerifikasiSaldoSemuaTriwulan() di
     * bawah (sejak permintaan user 2026-09-17 lanjutan Part 32 kelima,
     * kolom ini dihitung REAL-TIME setiap render, TIDAK PERNAH lagi
     * lewat input langsung di kotak tabel - lihat catatan FIELD_RUMUS di
     * atas) supaya rumusnya konsisten dihitung dari 1 tempat saja.
     *
     * Rumus (ditentukan EKSPLISIT oleh user 2026-09-17, jawaban
     * AskUserQuestion - lihat migration create_laporan_realisasi_bosp_table):
     * - Kolom 28 (verifikasi_jumlah) = Kolom 26 (Saldo Rekening/Kas
     *   Bank) + Kolom 27 (Saldo Kas Tunai).
     * - Kolom 29 (verifikasi_saldo) = "SAMA" kalau Kolom 25 (Sisa Dana
     *   BOS) SAMA DENGAN Kolom 28, atau "TIDAK SAMA" kalau berbeda.
     *
     * Nilai yang belum diisi (null) dianggap 0 untuk kolom 28 (SELALU
     * berupa angka, tidak pernah null) - TAPI kolom 25 yang null tetap
     * dibandingkan APA ADANYA (dianggap 0 juga, bukan "belum diisi
     * berarti belum bisa diverifikasi") supaya perbandingan konsisten &
     * sederhana sesuai rumus yang diberikan user secara harfiah.
     *
     * BERUBAH sejak permintaan user 2026-09-17 (lanjutan Part 32
     * ketujuh, poin 1, jawaban AskUserQuestion "Saldo Kas Bank & Kas
     * Tunai TW itu saja (Recommended)"): kata "SAMA"/"TIDAK SAMA" pada
     * kolom 29 (verifikasi_saldo) SEKARANG BELUM DITAMPILKAN (nilainya
     * `null`, bukan string) selama kolom 26 (Saldo Rekening/Kas Bank)
     * ATAU kolom 27 (Saldo Kas Tunai) MASIH `null` (belum tersimpan
     * datanya di DanaBospTahap untuk triwulan ybs) - blade menampilkan
     * "-" untuk kondisi ini (lihat index.blade.php). Kolom 28
     * (verifikasi_jumlah) TETAP SELALU berupa angka (tidak pernah null,
     * null dianggap 0 seperti sebelumnya) - HANYA kolom 29 yang jadi
     * nullable. Definisi "data belum tersimpan" ini SENGAJA HANYA
     * berdasarkan kolom 26/27 (BUKAN kolom 25/Sisa Dana BOS, yang sejak
     * ronde ini SELALU berupa angka karena kolom 8-10 sumbernya sudah
     * otomatis semua - lihat FIELD_KOMPUTASI_RINCIAN).
     *
     * @return array{0: int, 1: ?string} [verifikasiJumlah, verifikasiSaldo]
     */
    public static function hitungVerifikasiSaldo(?int $sisaDanaBos, ?int $saldoRekeningKasBank, ?int $saldoKasTunai): array
    {
        $verifikasiJumlah = (int) $saldoRekeningKasBank + (int) $saldoKasTunai;

        if ($saldoRekeningKasBank === null || $saldoKasTunai === null) {
            return [$verifikasiJumlah, null];
        }

        $verifikasiSaldo = (int) $sisaDanaBos === $verifikasiJumlah
            ? self::VERIFIKASI_SAMA
            : self::VERIFIKASI_TIDAK_SAMA;

        return [$verifikasiJumlah, $verifikasiSaldo];
    }

    /**
     * Method privat: menghitung SEKALIGUS kolom 8 (saldo_awal_dana_bosp),
     * kolom 9 (penerimaan_dana_bos), kolom 10 (total_penerimaan), & kolom
     * 25 (sisa_dana_bos) untuk SELURUH sekolah & SELURUH triwulan (1-4)
     * dalam SATU pass - permintaan user 2026-09-17 (lanjutan Part 32
     * ketujuh). WAJIB dihitung bersama dalam 1 pass BERURUTAN (bukan 4
     * method terpisah yang saling memanggil dispatcher "SemuaTriwulan")
     * KARENA kolom 8 pada TW>1 = kolom 25 (Sisa Dana BOS) pada TRIWULAN
     * SEBELUMNYA (lihat catatan lengkap di FIELD_KOMPUTASI_RINCIAN di
     * atas) - kalau ditulis lewat pemanggilan dispatcher biasa yang
     * saling memanggil ambilTotalKomputasiRincianSemuaTriwulan() satu
     * sama lain, hasilnya SALING REKURSI TANPA HENTI karena
     * kedua-duanya sama-sama mencoba menghitung ULANG SELURUH 4 triwulan
     * sekaligus. Method ini MENGHINDARI itu dengan menghitung triwulan
     * SATU-PER-SATU secara BERURUTAN (TW1 dulu, baru TW2 memakai hasil
     * Sisa Dana BOS TW1, dst, TW4 terakhir) - HANYA memanggil dispatcher
     * untuk kolom LAIN yang TIDAK ikut rantai rekursi ini
     * (total_realisasi_dana_bos, kolom 24, aman dipanggil karena
     * sumbernya sendiri TIDAK bergantung kolom 8/9/10/25) & query
     * langsung ke App\Models\DanaBospTahap untuk 2 sumber eksternal
     * (`saldo_bosp_tw4_tahun_sebelumnya` utk kolom 8 TW1, &
     * `penerimaan_tahap_1`/`penerimaan_tahap_2` utk kolom 9 TW1/TW3).
     * Dipakai bersama oleh ambilTotalSaldoAwalDanaBosp()/
     * ambilTotalPenerimaanDanaBos()/ambilTotalPenerimaan()/
     * ambilTotalSisaDanaBos() (& masing-masing versi SemuaTriwulan-nya)
     * di bawah supaya rumusnya konsisten dihitung dari 1 tempat saja
     * (single source of truth, sama seperti pola rumus gabungan lain di
     * file ini).
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, array{saldo_awal: int, penerimaan: int, total_penerimaan: int, sisa: int}>> [profil_sekolah_id => [triwulan => [...]]]
     */
    private static function hitungRantaiPenerimaanDanSisaSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        $saldoAwalTw1 = DanaBospTahap::query()
            ->where('tahun', $tahun)
            ->pluck('saldo_bosp_tw4_tahun_sebelumnya', 'profil_sekolah_id');

        $penerimaanTahap1 = DanaBospTahap::query()
            ->where('tahun', $tahun)
            ->pluck('penerimaan_tahap_1', 'profil_sekolah_id');

        $penerimaanTahap2 = DanaBospTahap::query()
            ->where('tahun', $tahun)
            ->pluck('penerimaan_tahap_2', 'profil_sekolah_id');

        $totalRealisasiSemuaTw = self::ambilTotalKomputasiRincianSemuaTriwulan('total_realisasi_dana_bos', $tahun);

        $sekolahIds = $saldoAwalTw1->keys()
            ->merge($penerimaanTahap1->keys())
            ->merge($penerimaanTahap2->keys())
            ->merge($totalRealisasiSemuaTw->keys())
            ->unique();

        return $sekolahIds->mapWithKeys(function ($sekolahId) use ($saldoAwalTw1, $penerimaanTahap1, $penerimaanTahap2, $totalRealisasiSemuaTw) {
            $sisaTriwulanSebelumnya = 0;
            $perTriwulan = collect();

            foreach ([1, 2, 3, 4] as $triwulan) {
                $penerimaan = match ($triwulan) {
                    1 => (int) ($penerimaanTahap1[$sekolahId] ?? 0),
                    3 => (int) ($penerimaanTahap2[$sekolahId] ?? 0),
                    default => 0,
                };

                $saldoAwal = $triwulan === 1
                    ? (int) ($saldoAwalTw1[$sekolahId] ?? 0)
                    : (int) $sisaTriwulanSebelumnya;

                $totalPenerimaan = $saldoAwal + $penerimaan;
                $totalRealisasi = (int) ($totalRealisasiSemuaTw[$sekolahId][$triwulan] ?? 0);
                $sisa = $totalPenerimaan - $totalRealisasi;

                $perTriwulan[$triwulan] = [
                    'saldo_awal' => $saldoAwal,
                    'penerimaan' => $penerimaan,
                    'total_penerimaan' => $totalPenerimaan,
                    'sisa' => $sisa,
                ];

                $sisaTriwulanSebelumnya = $sisa;
            }

            return [$sekolahId => $perTriwulan];
        });
    }

    /**
     * Kolom 8 (saldo_awal_dana_bosp) untuk 1 kombinasi tahun+triwulan,
     * dikelompokkan per sekolah - permintaan user 2026-09-17 (lanjutan
     * Part 32 ketujuh). Lihat catatan lengkap rumus (BEDA per triwulan) &
     * alasan dihitung lewat method privat bersama di
     * hitungRantaiPenerimaanDanSisaSemuaTriwulan() & FIELD_KOMPUTASI_RINCIAN
     * di atas.
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalSaldoAwalDanaBosp(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        return self::hitungRantaiPenerimaanDanSisaSemuaTriwulan($tahun)
            ->map(fn ($perTriwulan) => $perTriwulan[$triwulan]['saldo_awal']);
    }

    /** Sama seperti ambilTotalSaldoAwalDanaBosp(), tapi untuk SELURUH triwulan (dipakai tab "rekap"). */
    public static function ambilTotalSaldoAwalDanaBospSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return self::hitungRantaiPenerimaanDanSisaSemuaTriwulan($tahun)
            ->map(fn ($perTriwulan) => $perTriwulan->map(fn ($nilai) => $nilai['saldo_awal']));
    }

    /**
     * Kolom 9 (penerimaan_dana_bos) untuk 1 kombinasi tahun+triwulan,
     * dikelompokkan per sekolah - permintaan user 2026-09-17 (lanjutan
     * Part 32 ketujuh). TW1/TW3 diambil dari DanaBospTahap
     * (`penerimaan_tahap_1`/`penerimaan_tahap_2`), TW2/TW4 SELALU 0
     * (tidak ada penerimaan Dana BOSP, jawaban user verbatim) - lihat
     * catatan lengkap di hitungRantaiPenerimaanDanSisaSemuaTriwulan() &
     * FIELD_KOMPUTASI_RINCIAN di atas.
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalPenerimaanDanaBos(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        return self::hitungRantaiPenerimaanDanSisaSemuaTriwulan($tahun)
            ->map(fn ($perTriwulan) => $perTriwulan[$triwulan]['penerimaan']);
    }

    /** Sama seperti ambilTotalPenerimaanDanaBos(), tapi untuk SELURUH triwulan (dipakai tab "rekap"). */
    public static function ambilTotalPenerimaanDanaBosSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return self::hitungRantaiPenerimaanDanSisaSemuaTriwulan($tahun)
            ->map(fn ($perTriwulan) => $perTriwulan->map(fn ($nilai) => $nilai['penerimaan']));
    }

    /**
     * Kolom 10 (total_penerimaan) = kolom 8 (Saldo Awal Dana BOSP) +
     * kolom 9 (Penerimaan Dana BOS) pada triwulan yang SAMA - permintaan
     * user 2026-09-17 (lanjutan Part 32 ketujuh). Lihat catatan lengkap
     * di hitungRantaiPenerimaanDanSisaSemuaTriwulan() & FIELD_KOMPUTASI_RINCIAN
     * di atas.
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalPenerimaan(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        return self::hitungRantaiPenerimaanDanSisaSemuaTriwulan($tahun)
            ->map(fn ($perTriwulan) => $perTriwulan[$triwulan]['total_penerimaan']);
    }

    /** Sama seperti ambilTotalPenerimaan(), tapi untuk SELURUH triwulan (dipakai tab "rekap"). */
    public static function ambilTotalPenerimaanSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return self::hitungRantaiPenerimaanDanSisaSemuaTriwulan($tahun)
            ->map(fn ($perTriwulan) => $perTriwulan->map(fn ($nilai) => $nilai['total_penerimaan']));
    }

    /**
     * Total SUM kolom "Total Harga" (RincianBelanjaBarangHabisPakai::total_harga)
     * untuk 1 kombinasi tahun+triwulan, dikelompokkan per sekolah -
     * sumber nilai kolom 11 (belanja_barang_pakai_habis_persediaan,
     * lihat FIELD_KOMPUTASI_RINCIAN) untuk tab TW1-TW4. Dipakai supaya
     * Livewire\PendataanBosp\LaporanRealisasiBosp\Index::renderTabTriwulan()
     * cukup 1 query per tab (bukan query per sekolah).
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalBelanjaBarangPakaiHabisPersediaan(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        return RincianBelanjaBarangHabisPakai::query()
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->selectRaw('profil_sekolah_id, SUM(total_harga) as total')
            ->groupBy('profil_sekolah_id')
            ->pluck('total', 'profil_sekolah_id');
    }

    /**
     * Sama seperti ambilTotalBelanjaBarangPakaiHabisPersediaan(), tapi
     * untuk SELURUH triwulan (1-4) sekaligus dalam 1 query - dipakai
     * oleh tab "rekap" (renderTabRekap()) supaya tidak perlu 4 query
     * terpisah per triwulan.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, int>> [profil_sekolah_id => [triwulan => total]]
     */
    public static function ambilTotalBelanjaBarangPakaiHabisPersediaanSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return RincianBelanjaBarangHabisPakai::query()
            ->where('tahun', $tahun)
            ->selectRaw('profil_sekolah_id, triwulan, SUM(total_harga) as total')
            ->groupBy('profil_sekolah_id', 'triwulan')
            ->get()
            ->groupBy('profil_sekolah_id')
            ->map(fn ($baris) => $baris->pluck('total', 'triwulan'));
    }

    /**
     * Total SUM kolom "Jumlah Honor Yang Diterima"
     * (PenerimaanHonorPtk::jumlah_honor) untuk 1 kombinasi tahun+
     * triwulan, dikelompokkan per sekolah - sumber nilai kolom 12
     * (jasa_tenaga_pendidik_dan_kependidikan, lihat
     * FIELD_KOMPUTASI_RINCIAN) untuk tab TW1-TW4. Pola sama persis
     * dengan ambilTotalBelanjaBarangPakaiHabisPersediaan() di atas.
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalJasaTenagaPendidikDanKependidikan(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        return PenerimaanHonorPtk::query()
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->selectRaw('profil_sekolah_id, SUM(jumlah_honor) as total')
            ->groupBy('profil_sekolah_id')
            ->pluck('total', 'profil_sekolah_id');
    }

    /**
     * Sama seperti ambilTotalJasaTenagaPendidikDanKependidikan(), tapi
     * untuk SELURUH triwulan (1-4) sekaligus - dipakai tab "rekap".
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, int>> [profil_sekolah_id => [triwulan => total]]
     */
    public static function ambilTotalJasaTenagaPendidikDanKependidikanSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return PenerimaanHonorPtk::query()
            ->where('tahun', $tahun)
            ->selectRaw('profil_sekolah_id, triwulan, SUM(jumlah_honor) as total')
            ->groupBy('profil_sekolah_id', 'triwulan')
            ->get()
            ->groupBy('profil_sekolah_id')
            ->map(fn ($baris) => $baris->pluck('total', 'triwulan'));
    }

    /**
     * Total SUM kolom "Jumlah" (LanggananDayaJasa::jumlah) untuk 1
     * kombinasi tahun+triwulan, dikelompokkan per sekolah - sumber
     * nilai kolom 13 (daya_dan_jasa, lihat FIELD_KOMPUTASI_RINCIAN)
     * untuk tab TW1-TW4. Pola sama persis dengan
     * ambilTotalBelanjaBarangPakaiHabisPersediaan() di atas.
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalDayaDanJasa(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        return LanggananDayaJasa::query()
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->selectRaw('profil_sekolah_id, SUM(jumlah) as total')
            ->groupBy('profil_sekolah_id')
            ->pluck('total', 'profil_sekolah_id');
    }

    /**
     * Sama seperti ambilTotalDayaDanJasa(), tapi untuk SELURUH triwulan
     * (1-4) sekaligus - dipakai tab "rekap".
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, int>> [profil_sekolah_id => [triwulan => total]]
     */
    public static function ambilTotalDayaDanJasaSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return LanggananDayaJasa::query()
            ->where('tahun', $tahun)
            ->selectRaw('profil_sekolah_id, triwulan, SUM(jumlah) as total')
            ->groupBy('profil_sekolah_id', 'triwulan')
            ->get()
            ->groupBy('profil_sekolah_id')
            ->map(fn ($baris) => $baris->pluck('total', 'triwulan'));
    }

    /**
     * Total SUM kolom "Total Harga" untuk jenis "barang" dari DUA menu
     * sumber sekaligus (App\Models\RincianPemeliharaan &
     * App\Models\RincianPemeliharaanPc), dijumlahkan per sekolah - sumber
     * nilai kolom 14 (pemeliharaan, lihat FIELD_KOMPUTASI_RINCIAN) untuk
     * tab TW1-TW4 (permintaan user 2026-09-17, lanjutan Part 32 ketiga).
     * Hasil kedua query digabung (ditambahkan) per profil_sekolah_id
     * memakai Collection::mergeRecursive() lalu di-sum tiap sekolah,
     * supaya sekolah yang cuma punya data di salah satu dari 2 menu
     * sumber tetap ikut terhitung benar (bukan 0 begitu saja).
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalPemeliharaan(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        $dariBangunan = RincianPemeliharaan::query()
            ->where('jenis', RincianPemeliharaan::JENIS_BARANG)
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->selectRaw('profil_sekolah_id, SUM(total_harga) as total')
            ->groupBy('profil_sekolah_id')
            ->pluck('total', 'profil_sekolah_id');

        $dariPc = RincianPemeliharaanPc::query()
            ->where('jenis', RincianPemeliharaanPc::JENIS_BARANG)
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->selectRaw('profil_sekolah_id, SUM(total_harga) as total')
            ->groupBy('profil_sekolah_id')
            ->pluck('total', 'profil_sekolah_id');

        return $dariBangunan->union($dariPc)->keys()
            ->mapWithKeys(fn ($sekolahId) => [
                $sekolahId => (int) ($dariBangunan[$sekolahId] ?? 0) + (int) ($dariPc[$sekolahId] ?? 0),
            ]);
    }

    /**
     * Sama seperti ambilTotalPemeliharaan(), tapi untuk SELURUH triwulan
     * (1-4) sekaligus - dipakai tab "rekap".
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, int>> [profil_sekolah_id => [triwulan => total]]
     */
    public static function ambilTotalPemeliharaanSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        $dariBangunan = RincianPemeliharaan::query()
            ->where('jenis', RincianPemeliharaan::JENIS_BARANG)
            ->where('tahun', $tahun)
            ->selectRaw('profil_sekolah_id, triwulan, SUM(total_harga) as total')
            ->groupBy('profil_sekolah_id', 'triwulan')
            ->get()
            ->groupBy('profil_sekolah_id')
            ->map(fn ($baris) => $baris->pluck('total', 'triwulan'));

        $dariPc = RincianPemeliharaanPc::query()
            ->where('jenis', RincianPemeliharaanPc::JENIS_BARANG)
            ->where('tahun', $tahun)
            ->selectRaw('profil_sekolah_id, triwulan, SUM(total_harga) as total')
            ->groupBy('profil_sekolah_id', 'triwulan')
            ->get()
            ->groupBy('profil_sekolah_id')
            ->map(fn ($baris) => $baris->pluck('total', 'triwulan'));

        $sekolahIds = $dariBangunan->keys()->merge($dariPc->keys())->unique();

        return $sekolahIds->mapWithKeys(function ($sekolahId) use ($dariBangunan, $dariPc) {
            $perTriwulan = collect([1, 2, 3, 4])->mapWithKeys(function ($triwulan) use ($sekolahId, $dariBangunan, $dariPc) {
                $total = (int) ($dariBangunan[$sekolahId][$triwulan] ?? 0) + (int) ($dariPc[$sekolahId][$triwulan] ?? 0);

                return [$triwulan => $total];
            });

            return [$sekolahId => $perTriwulan];
        });
    }

    /**
     * Total SUM kolom "Total Harga" untuk jenis "jasa" dari DUA menu
     * sumber sekaligus (App\Models\RincianPemeliharaan &
     * App\Models\RincianPemeliharaanPc), dijumlahkan per sekolah - sumber
     * nilai kolom 15 (upah_pemeliharaan, lihat FIELD_KOMPUTASI_RINCIAN)
     * untuk tab TW1-TW4 (permintaan user 2026-09-17, lanjutan Part 32
     * ketiga). Pola PERSIS sama dengan ambilTotalPemeliharaan() di atas,
     * bedanya HANYA filter jenis (JENIS_JASA, bukan JENIS_BARANG).
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalUpahPemeliharaan(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        $dariBangunan = RincianPemeliharaan::query()
            ->where('jenis', RincianPemeliharaan::JENIS_JASA)
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->selectRaw('profil_sekolah_id, SUM(total_harga) as total')
            ->groupBy('profil_sekolah_id')
            ->pluck('total', 'profil_sekolah_id');

        $dariPc = RincianPemeliharaanPc::query()
            ->where('jenis', RincianPemeliharaanPc::JENIS_JASA)
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->selectRaw('profil_sekolah_id, SUM(total_harga) as total')
            ->groupBy('profil_sekolah_id')
            ->pluck('total', 'profil_sekolah_id');

        return $dariBangunan->union($dariPc)->keys()
            ->mapWithKeys(fn ($sekolahId) => [
                $sekolahId => (int) ($dariBangunan[$sekolahId] ?? 0) + (int) ($dariPc[$sekolahId] ?? 0),
            ]);
    }

    /**
     * Sama seperti ambilTotalUpahPemeliharaan(), tapi untuk SELURUH
     * triwulan (1-4) sekaligus - dipakai tab "rekap".
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, int>> [profil_sekolah_id => [triwulan => total]]
     */
    public static function ambilTotalUpahPemeliharaanSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        $dariBangunan = RincianPemeliharaan::query()
            ->where('jenis', RincianPemeliharaan::JENIS_JASA)
            ->where('tahun', $tahun)
            ->selectRaw('profil_sekolah_id, triwulan, SUM(total_harga) as total')
            ->groupBy('profil_sekolah_id', 'triwulan')
            ->get()
            ->groupBy('profil_sekolah_id')
            ->map(fn ($baris) => $baris->pluck('total', 'triwulan'));

        $dariPc = RincianPemeliharaanPc::query()
            ->where('jenis', RincianPemeliharaanPc::JENIS_JASA)
            ->where('tahun', $tahun)
            ->selectRaw('profil_sekolah_id, triwulan, SUM(total_harga) as total')
            ->groupBy('profil_sekolah_id', 'triwulan')
            ->get()
            ->groupBy('profil_sekolah_id')
            ->map(fn ($baris) => $baris->pluck('total', 'triwulan'));

        $sekolahIds = $dariBangunan->keys()->merge($dariPc->keys())->unique();

        return $sekolahIds->mapWithKeys(function ($sekolahId) use ($dariBangunan, $dariPc) {
            $perTriwulan = collect([1, 2, 3, 4])->mapWithKeys(function ($triwulan) use ($sekolahId, $dariBangunan, $dariPc) {
                $total = (int) ($dariBangunan[$sekolahId][$triwulan] ?? 0) + (int) ($dariPc[$sekolahId][$triwulan] ?? 0);

                return [$triwulan => $total];
            });

            return [$sekolahId => $perTriwulan];
        });
    }

    /**
     * 4 method di bawah (permintaan user 2026-09-23, round keenam, poin
     * 3) - PECAHAN dari ambilTotalPemeliharaan()/ambilTotalUpahPemeliharaan()
     * di atas, HANYA untuk SATU menu sumber tunggal (bukan digabung
     * bangunan+PC seperti kolom 14/15 di FIELD_KOMPUTASI_RINCIAN).
     * Dipakai KHUSUS oleh halaman validasi/Verval (gambar contoh
     * "VALIDASI HASIL ENTRY DATA BOSP") yang menampilkan "Rincian
     * Pemeliharaan Bangunan", "Rincian Jasa Pemeliharaan", "Rincian
     * Pemeliharaan PC dll", & "Rincian Jasa Pemeliharaan PC dll" sebagai
     * 4 BARIS TERPISAH (BUKAN 2 baris gabungan seperti tampilan kolom
     * 14/15 pada tab TW1-4 biasa) - lihat
     * App\Livewire\PendataanBosp\LaporanRealisasiBosp\Index::daftarUraianValidasi().
     * HANYA versi "SemuaTriwulan" yang dibuat (halaman Verval selalu
     * menampilkan ke-4 triwulan sekaligus dalam 1 tabel, tidak pernah
     * butuh 1 triwulan saja seperti tab TW1-4) - TIDAK ada versi
     * per-triwulan tunggal untuk 4 method ini (beda dari pola
     * ambilTotalX()/ambilTotalXSemuaTriwulan() berpasangan di atas).
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, int>> [profil_sekolah_id => [triwulan => total]]
     */
    public static function ambilTotalPemeliharaanBangunanSajaSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return RincianPemeliharaan::query()
            ->where('jenis', RincianPemeliharaan::JENIS_BARANG)
            ->where('tahun', $tahun)
            ->selectRaw('profil_sekolah_id, triwulan, SUM(total_harga) as total')
            ->groupBy('profil_sekolah_id', 'triwulan')
            ->get()
            ->groupBy('profil_sekolah_id')
            ->map(fn ($baris) => $baris->pluck('total', 'triwulan'));
    }

    /** Sama seperti ambilTotalPemeliharaanBangunanSajaSemuaTriwulan(), tapi filter `jenis` = RincianPemeliharaan::JENIS_JASA ("Rincian Jasa Pemeliharaan"). */
    public static function ambilTotalJasaPemeliharaanBangunanSajaSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return RincianPemeliharaan::query()
            ->where('jenis', RincianPemeliharaan::JENIS_JASA)
            ->where('tahun', $tahun)
            ->selectRaw('profil_sekolah_id, triwulan, SUM(total_harga) as total')
            ->groupBy('profil_sekolah_id', 'triwulan')
            ->get()
            ->groupBy('profil_sekolah_id')
            ->map(fn ($baris) => $baris->pluck('total', 'triwulan'));
    }

    /** Sama seperti ambilTotalPemeliharaanBangunanSajaSemuaTriwulan(), tapi dari App\Models\RincianPemeliharaanPc (jenis barang) - "Rincian Pemeliharaan PC Komputer-Laptop-Printer dll". */
    public static function ambilTotalPemeliharaanPcSajaSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return RincianPemeliharaanPc::query()
            ->where('jenis', RincianPemeliharaanPc::JENIS_BARANG)
            ->where('tahun', $tahun)
            ->selectRaw('profil_sekolah_id, triwulan, SUM(total_harga) as total')
            ->groupBy('profil_sekolah_id', 'triwulan')
            ->get()
            ->groupBy('profil_sekolah_id')
            ->map(fn ($baris) => $baris->pluck('total', 'triwulan'));
    }

    /** Sama seperti ambilTotalPemeliharaanPcSajaSemuaTriwulan(), tapi filter `jenis` = RincianPemeliharaanPc::JENIS_JASA - "Rincian Jasa Pemeliharaan PC-Laptop-Printer dll". */
    public static function ambilTotalJasaPemeliharaanPcSajaSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return RincianPemeliharaanPc::query()
            ->where('jenis', RincianPemeliharaanPc::JENIS_JASA)
            ->where('tahun', $tahun)
            ->selectRaw('profil_sekolah_id, triwulan, SUM(total_harga) as total')
            ->groupBy('profil_sekolah_id', 'triwulan')
            ->get()
            ->groupBy('profil_sekolah_id')
            ->map(fn ($baris) => $baris->pluck('total', 'triwulan'));
    }

    /**
     * Total SUM kolom "Jumlah" (BiayaPendaftaranLomba::jumlah) untuk 1
     * kombinasi tahun+triwulan, dikelompokkan per sekolah - sumber
     * nilai kolom 16 (biaya_pendaftaran_lomba_bimtek_workshop, lihat
     * FIELD_KOMPUTASI_RINCIAN) untuk tab TW1-TW4. Pola sama persis
     * dengan ambilTotalBelanjaBarangPakaiHabisPersediaan() (kolom 11) -
     * SATU menu sumber tunggal, tidak dijumlah dari 2 menu seperti
     * kolom 14/15.
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalBiayaPendaftaranLombaBimtekWorkshop(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        return BiayaPendaftaranLomba::query()
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->selectRaw('profil_sekolah_id, SUM(jumlah) as total')
            ->groupBy('profil_sekolah_id')
            ->pluck('total', 'profil_sekolah_id');
    }

    /** Sama seperti ambilTotalBiayaPendaftaranLombaBimtekWorkshop(), tapi untuk SELURUH triwulan (dipakai tab "rekap"). */
    public static function ambilTotalBiayaPendaftaranLombaBimtekWorkshopSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return BiayaPendaftaranLomba::query()
            ->where('tahun', $tahun)
            ->selectRaw('profil_sekolah_id, triwulan, SUM(jumlah) as total')
            ->groupBy('profil_sekolah_id', 'triwulan')
            ->get()
            ->groupBy('profil_sekolah_id')
            ->map(fn ($baris) => $baris->pluck('total', 'triwulan'));
    }

    /**
     * Total SUM kolom "Jumlah" (BelanjaHonorKegiatan::jumlah, WHERE
     * jenis = BelanjaHonorKegiatan::JENIS_HONOR_KEGIATAN) untuk 1
     * kombinasi tahun+triwulan, dikelompokkan per sekolah - sumber
     * nilai kolom 17 (honor_kegiatan, lihat FIELD_KOMPUTASI_RINCIAN).
     * Menu sumber SAMA dengan kolom 18/19 (satu tabel dibedakan
     * `jenis`), TAPI di sini HANYA mengambil `jenis` "honor_kegiatan"
     * saja (tidak dijumlah dengan `jenis` lain).
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalHonorKegiatan(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        return BelanjaHonorKegiatan::query()
            ->where('jenis', BelanjaHonorKegiatan::JENIS_HONOR_KEGIATAN)
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->selectRaw('profil_sekolah_id, SUM(jumlah) as total')
            ->groupBy('profil_sekolah_id')
            ->pluck('total', 'profil_sekolah_id');
    }

    /** Sama seperti ambilTotalHonorKegiatan(), tapi untuk SELURUH triwulan (dipakai tab "rekap"). */
    public static function ambilTotalHonorKegiatanSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return BelanjaHonorKegiatan::query()
            ->where('jenis', BelanjaHonorKegiatan::JENIS_HONOR_KEGIATAN)
            ->where('tahun', $tahun)
            ->selectRaw('profil_sekolah_id, triwulan, SUM(jumlah) as total')
            ->groupBy('profil_sekolah_id', 'triwulan')
            ->get()
            ->groupBy('profil_sekolah_id')
            ->map(fn ($baris) => $baris->pluck('total', 'triwulan'));
    }

    /**
     * Sama seperti ambilTotalHonorKegiatan(), tapi filter `jenis` =
     * BelanjaHonorKegiatan::JENIS_MAKAN_MINUM - sumber nilai kolom 18
     * (makan_dan_minum_kegiatan).
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalMakanDanMinumKegiatan(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        return BelanjaHonorKegiatan::query()
            ->where('jenis', BelanjaHonorKegiatan::JENIS_MAKAN_MINUM)
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->selectRaw('profil_sekolah_id, SUM(jumlah) as total')
            ->groupBy('profil_sekolah_id')
            ->pluck('total', 'profil_sekolah_id');
    }

    /** Sama seperti ambilTotalMakanDanMinumKegiatan(), tapi untuk SELURUH triwulan (dipakai tab "rekap"). */
    public static function ambilTotalMakanDanMinumKegiatanSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return BelanjaHonorKegiatan::query()
            ->where('jenis', BelanjaHonorKegiatan::JENIS_MAKAN_MINUM)
            ->where('tahun', $tahun)
            ->selectRaw('profil_sekolah_id, triwulan, SUM(jumlah) as total')
            ->groupBy('profil_sekolah_id', 'triwulan')
            ->get()
            ->groupBy('profil_sekolah_id')
            ->map(fn ($baris) => $baris->pluck('total', 'triwulan'));
    }

    /**
     * Sama seperti ambilTotalHonorKegiatan(), tapi filter `jenis` =
     * BelanjaHonorKegiatan::JENIS_PERJALANAN_DINAS - sumber nilai kolom
     * 19 (perjalanan_dinas).
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalPerjalananDinas(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        return BelanjaHonorKegiatan::query()
            ->where('jenis', BelanjaHonorKegiatan::JENIS_PERJALANAN_DINAS)
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->selectRaw('profil_sekolah_id, SUM(jumlah) as total')
            ->groupBy('profil_sekolah_id')
            ->pluck('total', 'profil_sekolah_id');
    }

    /** Sama seperti ambilTotalPerjalananDinas(), tapi untuk SELURUH triwulan (dipakai tab "rekap"). */
    public static function ambilTotalPerjalananDinasSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return BelanjaHonorKegiatan::query()
            ->where('jenis', BelanjaHonorKegiatan::JENIS_PERJALANAN_DINAS)
            ->where('tahun', $tahun)
            ->selectRaw('profil_sekolah_id, triwulan, SUM(jumlah) as total')
            ->groupBy('profil_sekolah_id', 'triwulan')
            ->get()
            ->groupBy('profil_sekolah_id')
            ->map(fn ($baris) => $baris->pluck('total', 'triwulan'));
    }

    /**
     * Total SUM kolom "Total Harga" (RincianBelanjaModal::total_harga,
     * WHERE jenis = RincianBelanjaModal::JENIS_PERALATAN_MESIN) untuk 1
     * kombinasi tahun+triwulan, dikelompokkan per sekolah - sumber nilai
     * kolom 21 (peralatan_dan_mesin_kib_b, lihat FIELD_KOMPUTASI_RINCIAN),
     * permintaan user 2026-09-17 (lanjutan Part 32 kelima). Pola sama
     * persis dengan ambilTotalBelanjaBarangPakaiHabisPersediaan() (kolom
     * 11) - SATU menu sumber tunggal, difilter `jenis`.
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalPeralatanDanMesinKibB(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        return RincianBelanjaModal::query()
            ->where('jenis', RincianBelanjaModal::JENIS_PERALATAN_MESIN)
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->selectRaw('profil_sekolah_id, SUM(total_harga) as total')
            ->groupBy('profil_sekolah_id')
            ->pluck('total', 'profil_sekolah_id');
    }

    /** Sama seperti ambilTotalPeralatanDanMesinKibB(), tapi untuk SELURUH triwulan (dipakai tab "rekap"). */
    public static function ambilTotalPeralatanDanMesinKibBSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return RincianBelanjaModal::query()
            ->where('jenis', RincianBelanjaModal::JENIS_PERALATAN_MESIN)
            ->where('tahun', $tahun)
            ->selectRaw('profil_sekolah_id, triwulan, SUM(total_harga) as total')
            ->groupBy('profil_sekolah_id', 'triwulan')
            ->get()
            ->groupBy('profil_sekolah_id')
            ->map(fn ($baris) => $baris->pluck('total', 'triwulan'));
    }

    /**
     * Sama seperti ambilTotalPeralatanDanMesinKibB(), tapi filter
     * `jenis` = RincianBelanjaModal::JENIS_ASET_TETAP_LAINNYA - sumber
     * nilai kolom 22 (aset_tetap_lainnya_kib_e).
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalAsetTetapLainnyaKibE(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        return RincianBelanjaModal::query()
            ->where('jenis', RincianBelanjaModal::JENIS_ASET_TETAP_LAINNYA)
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->selectRaw('profil_sekolah_id, SUM(total_harga) as total')
            ->groupBy('profil_sekolah_id')
            ->pluck('total', 'profil_sekolah_id');
    }

    /** Sama seperti ambilTotalAsetTetapLainnyaKibE(), tapi untuk SELURUH triwulan (dipakai tab "rekap"). */
    public static function ambilTotalAsetTetapLainnyaKibESemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return RincianBelanjaModal::query()
            ->where('jenis', RincianBelanjaModal::JENIS_ASET_TETAP_LAINNYA)
            ->where('tahun', $tahun)
            ->selectRaw('profil_sekolah_id, triwulan, SUM(total_harga) as total')
            ->groupBy('profil_sekolah_id', 'triwulan')
            ->get()
            ->groupBy('profil_sekolah_id')
            ->map(fn ($baris) => $baris->pluck('total', 'triwulan'));
    }

    /**
     * Kolom 20 (total_belanja_barang_dan_jasa) - RUMUS GABUNGAN (bukan
     * dari 1 menu sumber eksternal seperti method-method di atas,
     * melainkan PENJUMLAHAN 8 kolom LAIN yang sudah dihitung lebih
     * dulu), permintaan user 2026-09-17 (lanjutan Part 32 kelima) -
     * lihat catatan lengkap & PENTING soal kolom 11 yang SENGAJA tidak
     * ikut dijumlah di sini pada FIELD_KOMPUTASI_RINCIAN di atas.
     * Dihitung dengan memanggil ULANG ambilTotalKomputasiRincian() untuk
     * masing-masing dari 8 kolom sumber (BUKAN query baru ke tabel
     * eksternal) - single source of truth, supaya rumus ini otomatis
     * tetap benar walau cara menghitung salah satu dari 8 kolom
     * sumbernya berubah di masa depan.
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalBelanjaBarangDanJasa(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        $fieldSumber = [
            'jasa_tenaga_pendidik_dan_kependidikan',
            'daya_dan_jasa',
            'pemeliharaan',
            'upah_pemeliharaan',
            'biaya_pendaftaran_lomba_bimtek_workshop',
            'honor_kegiatan',
            'makan_dan_minum_kegiatan',
            'perjalanan_dinas',
        ];

        $totalPerField = collect($fieldSumber)->map(fn ($field) => self::ambilTotalKomputasiRincian($field, $tahun, $triwulan));
        $sekolahIds = $totalPerField->flatMap(fn ($total) => $total->keys())->unique();

        return $sekolahIds->mapWithKeys(fn ($sekolahId) => [
            $sekolahId => $totalPerField->sum(fn ($total) => (int) ($total[$sekolahId] ?? 0)),
        ]);
    }

    /** Sama seperti ambilTotalBelanjaBarangDanJasa(), tapi untuk SELURUH triwulan (dipakai tab "rekap"). */
    public static function ambilTotalBelanjaBarangDanJasaSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        $fieldSumber = [
            'jasa_tenaga_pendidik_dan_kependidikan',
            'daya_dan_jasa',
            'pemeliharaan',
            'upah_pemeliharaan',
            'biaya_pendaftaran_lomba_bimtek_workshop',
            'honor_kegiatan',
            'makan_dan_minum_kegiatan',
            'perjalanan_dinas',
        ];

        $totalPerField = collect($fieldSumber)->map(fn ($field) => self::ambilTotalKomputasiRincianSemuaTriwulan($field, $tahun));
        $sekolahIds = $totalPerField->flatMap(fn ($total) => $total->keys())->unique();

        return $sekolahIds->mapWithKeys(function ($sekolahId) use ($totalPerField) {
            $perTriwulan = collect([1, 2, 3, 4])->mapWithKeys(function ($triwulan) use ($sekolahId, $totalPerField) {
                $total = $totalPerField->sum(fn ($t) => (int) ($t[$sekolahId][$triwulan] ?? 0));

                return [$triwulan => $total];
            });

            return [$sekolahId => $perTriwulan];
        });
    }

    /**
     * Kolom 23 (total_belanja_modal) = kolom 21 (Peralatan dan Mesin
     * KIB B) + kolom 22 (Aset Tetap Lainnya KIB E) - permintaan user
     * 2026-09-17 (lanjutan Part 32 kelima). Sama seperti
     * ambilTotalBelanjaBarangDanJasa() di atas, dihitung memanggil ulang
     * ambilTotalKomputasiRincian() untuk kedua kolom sumbernya, bukan
     * query baru.
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalBelanjaModal(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        $peralatanMesin = self::ambilTotalKomputasiRincian('peralatan_dan_mesin_kib_b', $tahun, $triwulan);
        $asetTetapLainnya = self::ambilTotalKomputasiRincian('aset_tetap_lainnya_kib_e', $tahun, $triwulan);

        $sekolahIds = $peralatanMesin->keys()->merge($asetTetapLainnya->keys())->unique();

        return $sekolahIds->mapWithKeys(fn ($sekolahId) => [
            $sekolahId => (int) ($peralatanMesin[$sekolahId] ?? 0) + (int) ($asetTetapLainnya[$sekolahId] ?? 0),
        ]);
    }

    /** Sama seperti ambilTotalBelanjaModal(), tapi untuk SELURUH triwulan (dipakai tab "rekap"). */
    public static function ambilTotalBelanjaModalSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        $peralatanMesin = self::ambilTotalKomputasiRincianSemuaTriwulan('peralatan_dan_mesin_kib_b', $tahun);
        $asetTetapLainnya = self::ambilTotalKomputasiRincianSemuaTriwulan('aset_tetap_lainnya_kib_e', $tahun);

        $sekolahIds = $peralatanMesin->keys()->merge($asetTetapLainnya->keys())->unique();

        return $sekolahIds->mapWithKeys(function ($sekolahId) use ($peralatanMesin, $asetTetapLainnya) {
            $perTriwulan = collect([1, 2, 3, 4])->mapWithKeys(function ($triwulan) use ($sekolahId, $peralatanMesin, $asetTetapLainnya) {
                $total = (int) ($peralatanMesin[$sekolahId][$triwulan] ?? 0) + (int) ($asetTetapLainnya[$sekolahId][$triwulan] ?? 0);

                return [$triwulan => $total];
            });

            return [$sekolahId => $perTriwulan];
        });
    }

    /**
     * Kolom 24 (total_realisasi_dana_bos) = kolom 11 (Belanja Barang
     * Pakai Habis/Persediaan) + kolom 20 (Total Belanja Barang dan
     * Jasa) + kolom 23 (Total Belanja Modal) - permintaan user
     * 2026-09-17 (lanjutan Part 32 kelima). Kolom 11 yang SENGAJA TIDAK
     * diikutsertakan di rumus kolom 20 (lihat catatan
     * ambilTotalBelanjaBarangDanJasa() di atas) kembali digabungkan di
     * sini secara terpisah, sesuai instruksi literal user.
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalRealisasiDanaBos(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        $belanjaBarangHabisPakai = self::ambilTotalKomputasiRincian('belanja_barang_pakai_habis_persediaan', $tahun, $triwulan);
        $totalBelanjaBarangDanJasa = self::ambilTotalKomputasiRincian('total_belanja_barang_dan_jasa', $tahun, $triwulan);
        $totalBelanjaModal = self::ambilTotalKomputasiRincian('total_belanja_modal', $tahun, $triwulan);

        $sekolahIds = $belanjaBarangHabisPakai->keys()
            ->merge($totalBelanjaBarangDanJasa->keys())
            ->merge($totalBelanjaModal->keys())
            ->unique();

        return $sekolahIds->mapWithKeys(fn ($sekolahId) => [
            $sekolahId => (int) ($belanjaBarangHabisPakai[$sekolahId] ?? 0)
                + (int) ($totalBelanjaBarangDanJasa[$sekolahId] ?? 0)
                + (int) ($totalBelanjaModal[$sekolahId] ?? 0),
        ]);
    }

    /** Sama seperti ambilTotalRealisasiDanaBos(), tapi untuk SELURUH triwulan (dipakai tab "rekap"). */
    public static function ambilTotalRealisasiDanaBosSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        $belanjaBarangHabisPakai = self::ambilTotalKomputasiRincianSemuaTriwulan('belanja_barang_pakai_habis_persediaan', $tahun);
        $totalBelanjaBarangDanJasa = self::ambilTotalKomputasiRincianSemuaTriwulan('total_belanja_barang_dan_jasa', $tahun);
        $totalBelanjaModal = self::ambilTotalKomputasiRincianSemuaTriwulan('total_belanja_modal', $tahun);

        $sekolahIds = $belanjaBarangHabisPakai->keys()
            ->merge($totalBelanjaBarangDanJasa->keys())
            ->merge($totalBelanjaModal->keys())
            ->unique();

        return $sekolahIds->mapWithKeys(function ($sekolahId) use ($belanjaBarangHabisPakai, $totalBelanjaBarangDanJasa, $totalBelanjaModal) {
            $perTriwulan = collect([1, 2, 3, 4])->mapWithKeys(function ($triwulan) use ($sekolahId, $belanjaBarangHabisPakai, $totalBelanjaBarangDanJasa, $totalBelanjaModal) {
                $total = (int) ($belanjaBarangHabisPakai[$sekolahId][$triwulan] ?? 0)
                    + (int) ($totalBelanjaBarangDanJasa[$sekolahId][$triwulan] ?? 0)
                    + (int) ($totalBelanjaModal[$sekolahId][$triwulan] ?? 0);

                return [$triwulan => $total];
            });

            return [$sekolahId => $perTriwulan];
        });
    }

    /**
     * Kolom 25 (sisa_dana_bos) = kolom 10 (Total Penerimaan) DIKURANGI
     * kolom 24 (Total Realisasi Dana BOS) - permintaan user 2026-09-17
     * (lanjutan Part 32 kelima).
     *
     * BERUBAH sejak permintaan user 2026-09-17 (lanjutan Part 32
     * ketujuh): kolom 10 (total_penerimaan) SEKARANG SUDAH JADI HASIL
     * RUMUS juga (SEBELUMNYA manual, dibaca langsung dari kolom database
     * `total_penerimaan` lewat self::query()) - method ini SEKARANG
     * dihitung lewat hitungRantaiPenerimaanDanSisaSemuaTriwulan() (lihat
     * catatan lengkap di method tsb & FIELD_KOMPUTASI_RINCIAN di atas)
     * supaya konsisten 1 sumber kebenaran dengan kolom 8/9/10 yang
     * rumusnya SALING BERGANTUNG dengan kolom 25 ini (kolom 8 pada TW>1
     * = kolom 25 TW sebelumnya).
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalSisaDanaBos(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        return self::hitungRantaiPenerimaanDanSisaSemuaTriwulan($tahun)
            ->map(fn ($perTriwulan) => $perTriwulan[$triwulan]['sisa']);
    }

    /** Sama seperti ambilTotalSisaDanaBos(), tapi untuk SELURUH triwulan (dipakai tab "rekap"). */
    public static function ambilTotalSisaDanaBosSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return self::hitungRantaiPenerimaanDanSisaSemuaTriwulan($tahun)
            ->map(fn ($perTriwulan) => $perTriwulan->map(fn ($nilai) => $nilai['sisa']));
    }

    /**
     * Kolom 26 (saldo_rekening_kas_bank) - permintaan user 2026-09-17
     * (lanjutan Part 32 keenam): diambil OTOMATIS dari field
     * `saldo_kas_bank_tw{triwulan}` pada App\Models\DanaBospTahap (menu
     * Dana BOSP Tahap 1 & 2, tab "Tarik Tunai BOSP"), per sekolah untuk
     * tahun yang SAMA. BEDA dari seluruh method ambilTotal...() lain di
     * atas: DanaBospTahap TIDAK punya kolom `triwulan` (1 baris per
     * sekolah PER TAHUN, field TW1-4 sudah terpisah jadi 4 kolom
     * berbeda) - jadi di sini TIDAK ada `->where('triwulan', ...)`,
     * melainkan memilih NAMA KOLOM `_tw{triwulan}` yang sesuai lewat
     * `$namaKolom` (aman - dibentuk dari nilai integer 1-4 yang sudah
     * divalidasi TAB_TRIWULAN di Livewire\...\Index, BUKAN dari input
     * user langsung).
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalSaldoRekeningKasBank(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        $namaKolom = 'saldo_kas_bank_tw'.$triwulan;

        return DanaBospTahap::query()
            ->where('tahun', $tahun)
            ->pluck($namaKolom, 'profil_sekolah_id');
    }

    /** Sama seperti ambilTotalSaldoRekeningKasBank(), tapi untuk SELURUH triwulan (dipakai tab "rekap"). */
    public static function ambilTotalSaldoRekeningKasBankSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return DanaBospTahap::query()
            ->where('tahun', $tahun)
            ->get(['profil_sekolah_id', 'saldo_kas_bank_tw1', 'saldo_kas_bank_tw2', 'saldo_kas_bank_tw3', 'saldo_kas_bank_tw4'])
            ->keyBy('profil_sekolah_id')
            ->map(fn ($baris) => collect([1, 2, 3, 4])->mapWithKeys(fn ($triwulan) => [
                $triwulan => (int) $baris->{'saldo_kas_bank_tw'.$triwulan},
            ]));
    }

    /**
     * Kolom 27 (saldo_kas_tunai) - permintaan user 2026-09-17 (lanjutan
     * Part 32 keenam): pola PERSIS sama dengan
     * ambilTotalSaldoRekeningKasBank() di atas, bedanya HANYA nama
     * kolom sumber (`saldo_kas_tunai_tw{triwulan}`, bukan
     * `saldo_kas_bank_tw{triwulan}`).
     *
     * @return \Illuminate\Support\Collection<int, int> [profil_sekolah_id => total]
     */
    public static function ambilTotalSaldoKasTunai(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        $namaKolom = 'saldo_kas_tunai_tw'.$triwulan;

        return DanaBospTahap::query()
            ->where('tahun', $tahun)
            ->pluck($namaKolom, 'profil_sekolah_id');
    }

    /** Sama seperti ambilTotalSaldoKasTunai(), tapi untuk SELURUH triwulan (dipakai tab "rekap"). */
    public static function ambilTotalSaldoKasTunaiSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        return DanaBospTahap::query()
            ->where('tahun', $tahun)
            ->get(['profil_sekolah_id', 'saldo_kas_tunai_tw1', 'saldo_kas_tunai_tw2', 'saldo_kas_tunai_tw3', 'saldo_kas_tunai_tw4'])
            ->keyBy('profil_sekolah_id')
            ->map(fn ($baris) => collect([1, 2, 3, 4])->mapWithKeys(fn ($triwulan) => [
                $triwulan => (int) $baris->{'saldo_kas_tunai_tw'.$triwulan},
            ]));
    }

    /**
     * Dispatcher generik atas ambilTotal...() per nama field - dipakai
     * oleh Livewire\PendataanBosp\LaporanRealisasiBosp\Index supaya loop
     * generik atas FIELD_KOMPUTASI_RINCIAN tidak perlu tahu nama method
     * spesifik satu-per-satu (menambah kolom komputasi baru di masa
     * depan cukup menambah 1 baris `match` di sini + 2 method
     * ambilTotal.../...SemuaTriwulan() baru, TANPA mengubah
     * Livewire\...\Index sama sekali).
     */
    public static function ambilTotalKomputasiRincian(string $field, int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        return match ($field) {
            'saldo_awal_dana_bosp' => self::ambilTotalSaldoAwalDanaBosp($tahun, $triwulan),
            'penerimaan_dana_bos' => self::ambilTotalPenerimaanDanaBos($tahun, $triwulan),
            'total_penerimaan' => self::ambilTotalPenerimaan($tahun, $triwulan),
            'belanja_barang_pakai_habis_persediaan' => self::ambilTotalBelanjaBarangPakaiHabisPersediaan($tahun, $triwulan),
            'jasa_tenaga_pendidik_dan_kependidikan' => self::ambilTotalJasaTenagaPendidikDanKependidikan($tahun, $triwulan),
            'daya_dan_jasa' => self::ambilTotalDayaDanJasa($tahun, $triwulan),
            'pemeliharaan' => self::ambilTotalPemeliharaan($tahun, $triwulan),
            'upah_pemeliharaan' => self::ambilTotalUpahPemeliharaan($tahun, $triwulan),
            'biaya_pendaftaran_lomba_bimtek_workshop' => self::ambilTotalBiayaPendaftaranLombaBimtekWorkshop($tahun, $triwulan),
            'honor_kegiatan' => self::ambilTotalHonorKegiatan($tahun, $triwulan),
            'makan_dan_minum_kegiatan' => self::ambilTotalMakanDanMinumKegiatan($tahun, $triwulan),
            'perjalanan_dinas' => self::ambilTotalPerjalananDinas($tahun, $triwulan),
            'total_belanja_barang_dan_jasa' => self::ambilTotalBelanjaBarangDanJasa($tahun, $triwulan),
            'peralatan_dan_mesin_kib_b' => self::ambilTotalPeralatanDanMesinKibB($tahun, $triwulan),
            'aset_tetap_lainnya_kib_e' => self::ambilTotalAsetTetapLainnyaKibE($tahun, $triwulan),
            'total_belanja_modal' => self::ambilTotalBelanjaModal($tahun, $triwulan),
            'total_realisasi_dana_bos' => self::ambilTotalRealisasiDanaBos($tahun, $triwulan),
            'sisa_dana_bos' => self::ambilTotalSisaDanaBos($tahun, $triwulan),
            'saldo_rekening_kas_bank' => self::ambilTotalSaldoRekeningKasBank($tahun, $triwulan),
            'saldo_kas_tunai' => self::ambilTotalSaldoKasTunai($tahun, $triwulan),
        };
    }

    /** Sama seperti ambilTotalKomputasiRincian(), tapi untuk SELURUH triwulan (dipakai tab "rekap"). */
    public static function ambilTotalKomputasiRincianSemuaTriwulan(string $field, int $tahun): \Illuminate\Support\Collection
    {
        return match ($field) {
            'saldo_awal_dana_bosp' => self::ambilTotalSaldoAwalDanaBospSemuaTriwulan($tahun),
            'penerimaan_dana_bos' => self::ambilTotalPenerimaanDanaBosSemuaTriwulan($tahun),
            'total_penerimaan' => self::ambilTotalPenerimaanSemuaTriwulan($tahun),
            'belanja_barang_pakai_habis_persediaan' => self::ambilTotalBelanjaBarangPakaiHabisPersediaanSemuaTriwulan($tahun),
            'jasa_tenaga_pendidik_dan_kependidikan' => self::ambilTotalJasaTenagaPendidikDanKependidikanSemuaTriwulan($tahun),
            'daya_dan_jasa' => self::ambilTotalDayaDanJasaSemuaTriwulan($tahun),
            'pemeliharaan' => self::ambilTotalPemeliharaanSemuaTriwulan($tahun),
            'upah_pemeliharaan' => self::ambilTotalUpahPemeliharaanSemuaTriwulan($tahun),
            'biaya_pendaftaran_lomba_bimtek_workshop' => self::ambilTotalBiayaPendaftaranLombaBimtekWorkshopSemuaTriwulan($tahun),
            'honor_kegiatan' => self::ambilTotalHonorKegiatanSemuaTriwulan($tahun),
            'makan_dan_minum_kegiatan' => self::ambilTotalMakanDanMinumKegiatanSemuaTriwulan($tahun),
            'perjalanan_dinas' => self::ambilTotalPerjalananDinasSemuaTriwulan($tahun),
            'total_belanja_barang_dan_jasa' => self::ambilTotalBelanjaBarangDanJasaSemuaTriwulan($tahun),
            'peralatan_dan_mesin_kib_b' => self::ambilTotalPeralatanDanMesinKibBSemuaTriwulan($tahun),
            'aset_tetap_lainnya_kib_e' => self::ambilTotalAsetTetapLainnyaKibESemuaTriwulan($tahun),
            'total_belanja_modal' => self::ambilTotalBelanjaModalSemuaTriwulan($tahun),
            'total_realisasi_dana_bos' => self::ambilTotalRealisasiDanaBosSemuaTriwulan($tahun),
            'sisa_dana_bos' => self::ambilTotalSisaDanaBosSemuaTriwulan($tahun),
            'saldo_rekening_kas_bank' => self::ambilTotalSaldoRekeningKasBankSemuaTriwulan($tahun),
            'saldo_kas_tunai' => self::ambilTotalSaldoKasTunaiSemuaTriwulan($tahun),
        };
    }

    /**
     * Kolom 28 (Jumlah) & kolom 29 (Verifikasi Saldo) untuk SELURUH
     * sekolah dalam 1 kombinasi tahun+triwulan sekaligus - dipakai
     * Livewire\...\Index::renderTabTriwulan() supaya tidak perlu N+1
     * query per sekolah. Lihat catatan lengkap perubahan perilaku pada
     * FIELD_RUMUS di atas (permintaan user 2026-09-17, lanjutan Part 32
     * kelima, jawaban AskUserQuestion "Real-time setiap render").
     *
     * Kolom 26 (saldo_rekening_kas_bank) & kolom 27 (saldo_kas_tunai) -
     * BERUBAH sejak permintaan user 2026-09-17 (lanjutan Part 32
     * keenam): SEBELUMNYA diambil langsung dari kolom manual pada tabel
     * ini sendiri, SEKARANG diambil lewat ambilTotalKomputasiRincian()
     * (sumbernya App\Models\DanaBospTahap - lihat FIELD_LAIN &
     * ambilTotalSaldoRekeningKasBank()/ambilTotalSaldoKasTunai() di
     * atas), KARENA kolom 26/27 sendiri sekarang jadi hasil rumus
     * otomatis yang tidak lagi pernah ditulis ke kolom database
     * `saldo_rekening_kas_bank`/`saldo_kas_tunai` (kolom itu jadi
     * vestigial, sama seperti kolom 11-25).
     *
     * @return \Illuminate\Support\Collection<int, array{verifikasi_jumlah: int, verifikasi_saldo: string}> [profil_sekolah_id => [...]]
     */
    public static function ambilVerifikasiSaldo(int $tahun, int $triwulan): \Illuminate\Support\Collection
    {
        $sisaDanaBos = self::ambilTotalKomputasiRincian('sisa_dana_bos', $tahun, $triwulan);
        $saldoRekeningKasBank = self::ambilTotalKomputasiRincian('saldo_rekening_kas_bank', $tahun, $triwulan);
        $saldoKasTunai = self::ambilTotalKomputasiRincian('saldo_kas_tunai', $tahun, $triwulan);

        $sekolahIds = $sisaDanaBos->keys()
            ->merge($saldoRekeningKasBank->keys())
            ->merge($saldoKasTunai->keys())
            ->unique();

        return $sekolahIds->mapWithKeys(function ($sekolahId) use ($sisaDanaBos, $saldoRekeningKasBank, $saldoKasTunai) {
            [$verifikasiJumlah, $verifikasiSaldo] = self::hitungVerifikasiSaldo(
                (int) ($sisaDanaBos[$sekolahId] ?? 0),
                $saldoRekeningKasBank[$sekolahId] ?? null,
                $saldoKasTunai[$sekolahId] ?? null
            );

            return [$sekolahId => ['verifikasi_jumlah' => $verifikasiJumlah, 'verifikasi_saldo' => $verifikasiSaldo]];
        });
    }

    /** Sama seperti ambilVerifikasiSaldo(), tapi untuk SELURUH triwulan (dipakai tab "rekap"). */
    public static function ambilVerifikasiSaldoSemuaTriwulan(int $tahun): \Illuminate\Support\Collection
    {
        $sisaDanaBosSemuaTw = self::ambilTotalKomputasiRincianSemuaTriwulan('sisa_dana_bos', $tahun);
        $saldoRekeningKasBankSemuaTw = self::ambilTotalKomputasiRincianSemuaTriwulan('saldo_rekening_kas_bank', $tahun);
        $saldoKasTunaiSemuaTw = self::ambilTotalKomputasiRincianSemuaTriwulan('saldo_kas_tunai', $tahun);

        $sekolahIds = $sisaDanaBosSemuaTw->keys()
            ->merge($saldoRekeningKasBankSemuaTw->keys())
            ->merge($saldoKasTunaiSemuaTw->keys())
            ->unique();

        return $sekolahIds->mapWithKeys(function ($sekolahId) use ($sisaDanaBosSemuaTw, $saldoRekeningKasBankSemuaTw, $saldoKasTunaiSemuaTw) {
            $perTriwulan = collect([1, 2, 3, 4])->mapWithKeys(function ($triwulan) use ($sekolahId, $sisaDanaBosSemuaTw, $saldoRekeningKasBankSemuaTw, $saldoKasTunaiSemuaTw) {
                [$verifikasiJumlah, $verifikasiSaldo] = self::hitungVerifikasiSaldo(
                    (int) ($sisaDanaBosSemuaTw[$sekolahId][$triwulan] ?? 0),
                    $saldoRekeningKasBankSemuaTw[$sekolahId][$triwulan] ?? null,
                    $saldoKasTunaiSemuaTw[$sekolahId][$triwulan] ?? null
                );

                return [$triwulan => ['verifikasi_jumlah' => $verifikasiJumlah, 'verifikasi_saldo' => $verifikasiSaldo]];
            });

            return [$sekolahId => $perTriwulan];
        });
    }

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'triwulan' => 'integer',
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
