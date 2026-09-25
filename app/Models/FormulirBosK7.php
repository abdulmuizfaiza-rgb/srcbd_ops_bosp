<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\FormulirBosK7Factory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Formulir BOS K7b & K7c - satu baris = 1 sekolah + 1 tahun + 1 bulan
 * (Januari-Desember), dipakai bersama oleh KEDUA tab menu ("Formulir BOS
 * K7b" - Register Penutupan Kas, & "Formulir BOS K7c" - Berita Acara
 * Pemeriksaan Kas) karena keduanya menampilkan angka penutupan-kas
 * bulanan yang SAMA, hanya beda presentasi (tabel rincian vs narasi).
 * Lihat migration `create_formulir_bos_k7_table` untuk konteks lengkap
 * (permintaan user 2026-09-23).
 *
 * SENGAJA TIDAK menyimpan kolom Rp per baris pecahan uang, Sub Jumlah
 * (1)/(2), Saldo Kas Tunai, A/B/Perbedaan, maupun tanggal penutupan kas -
 * semua SELALU dihitung dinamis lewat method static di bawah (pola sama
 * seperti App\Models\PajakBospReguler), dipakai konsisten oleh Livewire,
 * Export Excel, & Export PDF, supaya rumus tidak pernah dobel-tulis.
 */
#[Fillable([
    'profil_sekolah_id',
    'tahun',
    'bulan',
    'lembar_100000',
    'lembar_50000',
    'lembar_20000',
    'lembar_10000',
    'lembar_5000',
    'lembar_2000',
    'lembar_1000',
    'keping_1000',
    'keping_500',
    'keping_200',
    'keping_100',
    'saldo_rekening_bank',
    'saldo_kas_tunai_manual',
    'jumlah_total_penerimaan_bku',
    'jumlah_total_pengeluaran_bku',
    'penjelasan_perbedaan',
    'no_sk_kepala_sekolah',
    'tanggal_sk_kepala_sekolah',
    'no_sk_bendahara',
    'tanggal_sk_bendahara',
    'created_by',
])]
class FormulirBosK7 extends Model
{
    /** @use HasFactory<FormulirBosK7Factory> */
    use HasFactory;

    protected $table = 'formulir_bos_k7';

    public const BULAN_OPTIONS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    /** 7 pecahan lembaran uang kertas, urutan besar->kecil sesuai gambar contoh. */
    public const NOMINAL_UANG_KERTAS = [100000, 50000, 20000, 10000, 5000, 2000, 1000];

    /** 4 pecahan keping uang logam, urutan besar->kecil sesuai gambar contoh. */
    public const NOMINAL_UANG_LOGAM = [1000, 500, 200, 100];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'bulan' => 'integer',
            'lembar_100000' => 'integer',
            'lembar_50000' => 'integer',
            'lembar_20000' => 'integer',
            'lembar_10000' => 'integer',
            'lembar_5000' => 'integer',
            'lembar_2000' => 'integer',
            'lembar_1000' => 'integer',
            'keping_1000' => 'integer',
            'keping_500' => 'integer',
            'keping_200' => 'integer',
            'keping_100' => 'integer',
            'saldo_rekening_bank' => 'integer',
            'saldo_kas_tunai_manual' => 'integer',
            'jumlah_total_penerimaan_bku' => 'integer',
            'jumlah_total_pengeluaran_bku' => 'integer',
            'tanggal_sk_kepala_sekolah' => 'date',
            'tanggal_sk_bendahara' => 'date',
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

    /** Nama field kolom "lembar_{nominal}" untuk 1 pecahan uang kertas. */
    public static function fieldLembar(int $nominal): string
    {
        return 'lembar_'.$nominal;
    }

    /** Nama field kolom "keping_{nominal}" untuk 1 pecahan uang logam. */
    public static function fieldKeping(int $nominal): string
    {
        return 'keping_'.$nominal;
    }

    /**
     * Rp 1 baris pecahan uang kertas = nominal x jumlah lembar.
     */
    public static function hitungRpLembar(array|self $baris, int $nominal): int
    {
        $data = $baris instanceof self ? $baris->toArray() : $baris;
        $jumlahLembar = (int) ($data[self::fieldLembar($nominal)] ?? 0);

        return $nominal * $jumlahLembar;
    }

    /**
     * Rp 1 baris pecahan uang logam = nominal x jumlah keping.
     */
    public static function hitungRpKeping(array|self $baris, int $nominal): int
    {
        $data = $baris instanceof self ? $baris->toArray() : $baris;
        $jumlahKeping = (int) ($data[self::fieldKeping($nominal)] ?? 0);

        return $nominal * $jumlahKeping;
    }

    /**
     * Sub Jumlah Lembar uang kertas (1) = total Rp ke-7 pecahan lembaran.
     */
    public static function hitungSubJumlahUangKertas(array|self $baris): int
    {
        $total = 0;
        foreach (self::NOMINAL_UANG_KERTAS as $nominal) {
            $total += self::hitungRpLembar($baris, $nominal);
        }

        return $total;
    }

    /**
     * Sub Jumlah Keping uang logam (2) = total Rp ke-4 pecahan keping.
     */
    public static function hitungSubJumlahUangLogam(array|self $baris): int
    {
        $total = 0;
        foreach (self::NOMINAL_UANG_LOGAM as $nominal) {
            $total += self::hitungRpKeping($baris, $nominal);
        }

        return $total;
    }

    /**
     * "Saldo Kas Tunai" (header K7b) / "a. Saldo KAS (Uang kertas dan
     * uang logam)" (K7c) = Sub Jumlah (1) + Sub Jumlah (2), SELAMA rincian
     * pecahan uang itu diisi (totalnya > 0).
     *
     * Kalau rincian pecahan uang masih kosong (total = 0), dipakai nilai
     * fallback dari kolom `saldo_kas_tunai_manual` (input manual) -
     * keputusan sesuai jawaban AskUserQuestion 2026-09-23 (round kedua):
     * "Tetap dari rincian, manual cuma cadangan". Method ini dipakai
     * konsisten oleh Livewire, Export Excel, & Export PDF supaya kedua
     * form (K7b & K7c) maupun rumus turunannya (B, Perbedaan) selalu
     * melihat 1 nilai efektif yang sama.
     */
    public static function hitungSaldoKasTunai(array|self $baris): int
    {
        $dariRincian = self::hitungSubJumlahUangKertas($baris) + self::hitungSubJumlahUangLogam($baris);

        if ($dariRincian > 0) {
            return $dariRincian;
        }

        $data = $baris instanceof self ? $baris->toArray() : $baris;

        return (int) ($data['saldo_kas_tunai_manual'] ?? 0);
    }

    /**
     * "A. Saldo Buku Kas Umum (A=D-K)" (K7b) / "Saldo menurut Buku Kas
     * Umum (BKU)" (K7c) = Jumlah Total Penerimaan BKU (D) - Jumlah Total
     * Pengeluaran BKU (K).
     */
    public static function hitungSaldoBku(array|self $baris): int
    {
        $data = $baris instanceof self ? $baris->toArray() : $baris;

        return (int) ($data['jumlah_total_penerimaan_bku'] ?? 0) - (int) ($data['jumlah_total_pengeluaran_bku'] ?? 0);
    }

    /**
     * "B. Jumlah (1+2+3)" (K7b) / "Jumlah" a+b (K7c) = Sub Jumlah (1) +
     * Sub Jumlah (2) + Saldo Rekening Bank (3) - SAMA PERSIS dengan Saldo
     * Kas Tunai + Saldo Bank, sesuai gambar contoh kedua formulir.
     */
    public static function hitungJumlahB(array|self $baris): int
    {
        $data = $baris instanceof self ? $baris->toArray() : $baris;

        return self::hitungSaldoKasTunai($baris) + (int) ($data['saldo_rekening_bank'] ?? 0);
    }

    /**
     * "Perbedaan (A-B)" (K7b) / "Perbedaan Antara Saldo KAS dan Kas Umum"
     * (K7c) = A - B.
     */
    public static function hitungPerbedaan(array|self $baris): int
    {
        return self::hitungSaldoBku($baris) - self::hitungJumlahB($baris);
    }

    /**
     * "Tanggal Penutupan Kas Bulan ini" = akhir bulan dari kombinasi
     * tahun+bulan baris ini (keputusan format tanggal, sesuai contoh
     * gambar "31 January 2025" untuk baris bulan=1/tahun=2025).
     */
    public static function tanggalPenutupanKas(int $tahun, int $bulan): Carbon
    {
        return Carbon::create($tahun, $bulan, 1)->endOfMonth()->startOfDay();
    }

    /**
     * "Tanggal Penutupan KAS Bulan Lalu" = 1 hari sebelum awal bulan ini
     * (otomatis akhir bulan sebelumnya, termasuk lintas tahun untuk
     * bulan=1).
     */
    public static function tanggalPenutupanKasBulanLalu(int $tahun, int $bulan): Carbon
    {
        return Carbon::create($tahun, $bulan, 1)->subDay()->startOfDay();
    }

    /**
     * Narasi tanggal berbahasa Indonesia untuk paragraf pembuka Formulir
     * K7c, mis. "Jum'at, tanggal Tiga puluh satu Bulan Januari Tahun Dua
     * ribu dua puluh lima" (sesuai gambar contoh - nama hari & tanggal
     * dieja dalam kata, BUKAN angka).
     */
    public static function terbilangTanggalNarasi(Carbon $tanggal): string
    {
        $namaHari = [
            0 => "Minggu", 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => "Jum'at", 6 => 'Sabtu',
        ];
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $hari = $namaHari[(int) $tanggal->format('w')];
        $tanggalTerbilang = ucfirst(self::angkaKeKata($tanggal->day));
        $bulan = $namaBulan[$tanggal->month];
        $tahunTerbilang = ucfirst(self::angkaKeKata($tanggal->year));

        return "{$hari}, tanggal {$tanggalTerbilang} Bulan {$bulan} Tahun {$tahunTerbilang}";
    }

    /**
     * Konversi angka non-negatif (dipakai di sini untuk tanggal 1-31 &
     * tahun 4 digit) menjadi ejaan kata Bahasa Indonesia, mis. 31 =>
     * "Tiga puluh satu", 2025 => "Dua ribu dua puluh lima". Rekursif,
     * cukup untuk rentang yang dipakai formulir ini (tanggal & tahun).
     */
    public static function angkaKeKata(int $angka): string
    {
        $satuan = [
            '', 'satu', 'dua', 'tiga', 'empat', 'lima',
            'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh',
            'sebelas',
        ];

        if ($angka < 12) {
            return $satuan[$angka];
        }

        if ($angka < 20) {
            return trim(self::angkaKeKata($angka - 10).' belas');
        }

        if ($angka < 100) {
            $puluh = intdiv($angka, 10);
            $sisa = $angka % 10;
            $depan = $puluh === 1 ? 'sepuluh' : trim(self::angkaKeKata($puluh).' puluh');

            return trim($depan.($sisa > 0 ? ' '.self::angkaKeKata($sisa) : ''));
        }

        if ($angka < 200) {
            $sisa = $angka - 100;

            return trim('seratus'.($sisa > 0 ? ' '.self::angkaKeKata($sisa) : ''));
        }

        if ($angka < 1000) {
            $ratus = intdiv($angka, 100);
            $sisa = $angka % 100;

            return trim(self::angkaKeKata($ratus).' ratus'.($sisa > 0 ? ' '.self::angkaKeKata($sisa) : ''));
        }

        if ($angka < 2000) {
            $sisa = $angka - 1000;

            return trim('seribu'.($sisa > 0 ? ' '.self::angkaKeKata($sisa) : ''));
        }

        if ($angka < 1000000) {
            $ribu = intdiv($angka, 1000);
            $sisa = $angka % 1000;

            return trim(self::angkaKeKata($ribu).' ribu'.($sisa > 0 ? ' '.self::angkaKeKata($sisa) : ''));
        }

        // Rentang di luar tanggal/tahun kalender wajar - fallback aman.
        return (string) $angka;
    }
}
