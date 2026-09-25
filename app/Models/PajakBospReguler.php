<?php

namespace App\Models;

use Database\Factories\PajakBospRegulerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pajak BOSP Reguler - data pajak reguler & pajak daerah per sekolah, per
 * tahun, 12 baris tetap (1 baris per bulan Januari-Desember) - lihat
 * migration `create_pajak_bosp_reguler_table` untuk konteks lengkap
 * (permintaan user 2026-09-11, Part 23).
 *
 * SENGAJA TIDAK menyimpan kolom "Jumlah Debit"/"Jumlah Kredit"/"Saldo" -
 * ketiganya SELALU dihitung dinamis lewat method static di bawah, dipakai
 * konsisten oleh Livewire (tabel bulanan & tabel Triwulan), Export Excel,
 * & Export PDF, supaya rumus TIDAK pernah dobel-tulis di beberapa tempat.
 *
 * Rumus (sesuai jawaban AskUserQuestion 2026-09-11):
 * - Jumlah (Debit/Kredit) per bulan = jumlah 5 kolom pajak sisi itu.
 * - Saldo = KUMULATIF, carry-over antar bulan dalam tahun yang sama
 *   (Saldo bulan ini = Saldo bulan sebelumnya + Jumlah Debit bulan ini -
 *   Jumlah Kredit bulan ini) - seperti buku kas berjalan.
 * - Saldo RESET ke 0 di awal setiap tahun (Januari) - TIDAK dibawa lintas
 *   tahun - mengikuti judul tabel pada gambar contoh "PERIODE
 *   JANUARI-DESEMBER TAHUN ANGGARAN {tahun}" yang menyiratkan periode
 *   tertutup per tahun anggaran. Ini keputusan teknis/pemformatan
 *   (bukan aturan bisnis baru yang perlu ditanyakan), didokumentasikan
 *   di sini secara eksplisit sebagai asumsi yang diambil.
 * - Triwulan (Tabel 2 pada gambar) = OTOMATIS dari data bulanan (jawaban
 *   AskUserQuestion "Otomatis dari data bulanan"): Jumlah Debit/Kredit
 *   Triwulan = total 3 bulan terkait; Saldo Triwulan = saldo kumulatif
 *   pada bulan TERAKHIR triwulan itu (karena Saldo memang sudah bersifat
 *   kumulatif).
 *
 * NPSN & Nama Sekolah TIDAK disimpan sebagai kolom - selalu dari relasi
 * profilSekolah().
 */
#[Fillable([
    'profil_sekolah_id',
    'tahun',
    'bulan',
    'ppn_debit',
    'pph21_debit',
    'pph23_debit',
    'pph4_debit',
    'sspd_debit',
    'ppn_kredit',
    'pph21_kredit',
    'pph23_kredit',
    'pph4_kredit',
    'sspd_kredit',
    'created_by',
])]
class PajakBospReguler extends Model
{
    /** @use HasFactory<PajakBospRegulerFactory> */
    use HasFactory;

    protected $table = 'pajak_bosp_reguler';

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

    /**
     * Pemetaan Triwulan -> daftar bulan anggotanya, dipakai untuk
     * agregasi otomatis tabel Triwulan (Tabel 2 pada gambar contoh).
     */
    public const TRIWULAN_BULAN = [
        1 => [1, 2, 3],
        2 => [4, 5, 6],
        3 => [7, 8, 9],
        4 => [10, 11, 12],
    ];

    public const TRIWULAN_OPTIONS = [
        1 => 'Triwulan 1',
        2 => 'Triwulan 2',
        3 => 'Triwulan 3',
        4 => 'Triwulan 4',
    ];

    /**
     * Kebalikan dari TRIWULAN_BULAN - bulan (1-12) -> nomor triwulan
     * (1-4) yang memuatnya. Dipakai sejak round kesembilan (permintaan
     * user 2026-09-23, poin 2) oleh App\Livewire\PendataanBosp\PajakBospReguler\Index
     * & App\Livewire\PendataanBosp\FormulirBosK7\Index untuk menentukan
     * triwulan mana yang harus dicek App\Livewire\Concerns\MenolakEditJikaTerkunciVerval
     * saat menyimpan data bulanan (kedua menu itu berbasis bulan, bukan
     * triwulan langsung).
     */
    public static function triwulanDariBulan(int $bulan): int
    {
        return (int) intdiv(max(1, min(12, $bulan)) - 1, 3) + 1;
    }

    /** 5 kolom sisi PENERIMAAN/DEBIT, urutan sesuai gambar contoh. */
    public const FIELD_DEBIT = [
        'ppn_debit',
        'pph21_debit',
        'pph23_debit',
        'pph4_debit',
        'sspd_debit',
    ];

    /** 5 kolom sisi PENGELUARAN/KREDIT, urutan sesuai gambar contoh. */
    public const FIELD_KREDIT = [
        'ppn_kredit',
        'pph21_kredit',
        'pph23_kredit',
        'pph4_kredit',
        'sspd_kredit',
    ];

    /** Label kolom pajak (dipakai bersama utk sisi debit maupun kredit), urutan sesuai gambar. */
    public const LABEL_PAJAK = [
        'ppn' => 'PPN',
        'pph21' => 'PPh 21',
        'pph23' => 'PPh 23',
        'pph4' => 'PPh 4',
        'sspd' => 'SSPD',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'bulan' => 'integer',
            'ppn_debit' => 'integer',
            'pph21_debit' => 'integer',
            'pph23_debit' => 'integer',
            'pph4_debit' => 'integer',
            'sspd_debit' => 'integer',
            'ppn_kredit' => 'integer',
            'pph21_kredit' => 'integer',
            'pph23_kredit' => 'integer',
            'pph4_kredit' => 'integer',
            'sspd_kredit' => 'integer',
        ];
    }

    /**
     * Jumlah Debit 1 baris (bulan) = total 5 kolom sisi Debit.
     * Menerima array asosiatif (mis. dari input langsung tabel yang
     * belum tersimpan) ATAU instance model - supaya bisa dipakai untuk
     * pratinjau live sebelum disimpan maupun data yang sudah tersimpan.
     */
    public static function hitungJumlahDebit(array|self $baris): int
    {
        $data = $baris instanceof self ? $baris->toArray() : $baris;

        $total = 0;
        foreach (self::FIELD_DEBIT as $field) {
            $total += (int) ($data[$field] ?? 0);
        }

        return $total;
    }

    /**
     * Jumlah Kredit 1 baris (bulan) = total 5 kolom sisi Kredit.
     */
    public static function hitungJumlahKredit(array|self $baris): int
    {
        $data = $baris instanceof self ? $baris->toArray() : $baris;

        $total = 0;
        foreach (self::FIELD_KREDIT as $field) {
            $total += (int) ($data[$field] ?? 0);
        }

        return $total;
    }

    /**
     * Menghitung Saldo kumulatif berjalan untuk 1 tahun penuh (bulan 1
     * s.d. 12, iterasi berurutan, Saldo RESET 0 di awal tahun/Januari).
     *
     * @param  array<int, array{debit:int, kredit:int}>  $jumlahPerBulan  Kunci = bulan (1-12), tidak harus lengkap 12 - bulan yang tidak ada datanya dianggap 0.
     * @return array<int, int> Kunci = bulan (1-12), nilai = saldo kumulatif SETELAH bulan itu.
     */
    public static function hitungSaldoBerjalan(array $jumlahPerBulan): array
    {
        $saldo = 0;
        $hasil = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $debit = (int) ($jumlahPerBulan[$bulan]['debit'] ?? 0);
            $kredit = (int) ($jumlahPerBulan[$bulan]['kredit'] ?? 0);
            $saldo = $saldo + $debit - $kredit;
            $hasil[$bulan] = $saldo;
        }

        return $hasil;
    }

    /**
     * Agregat otomatis tabel Triwulan dari data bulanan (Tabel 2 pada
     * gambar contoh) - Jumlah Debit/Kredit Triwulan = total 3 bulan
     * anggotanya; Saldo Triwulan = saldo kumulatif pada bulan TERAKHIR
     * triwulan tsb (karena Saldo sudah bersifat kumulatif berjalan).
     *
     * Sejak permintaan user 2026-09-15, hasilnya juga menyertakan kunci
     * 'rincian' berisi total per 10 kolom pajak mentah (5 Debit + 5
     * Kredit) untuk triwulan itu (jumlah 3 bulan anggotanya per kolom) -
     * dipakai tabel "Rekapitulasi per Triwulan" (Tab 1) supaya kolom
     * Penerimaan (Debit)/Pengeluaran (Kredit) bisa dirinci per jenis
     * pajak, bukan cuma total gabungan.
     *
     * @param  array<int, array{debit:int, kredit:int}>  $jumlahPerBulan
     * @param  array<int, int>  $saldoPerBulan  Hasil dari hitungSaldoBerjalan().
     * @param  array<int, array<string, int>>  $rincianPerBulan  Kunci = bulan (1-12), nilai = [field => nilai mentah bulan itu]. Opsional - kalau dikosongkan, 'rincian' pada hasil akan berisi semua 0.
     * @return array<int, array{debit:int, kredit:int, saldo:int, rincian: array<string, int>}> Kunci = nomor Triwulan (1-4).
     */
    public static function hitungTriwulan(array $jumlahPerBulan, array $saldoPerBulan, array $rincianPerBulan = []): array
    {
        $hasil = [];
        $semuaField = array_merge(self::FIELD_DEBIT, self::FIELD_KREDIT);

        foreach (self::TRIWULAN_BULAN as $triwulan => $daftarBulan) {
            $debit = 0;
            $kredit = 0;
            $rincian = array_fill_keys($semuaField, 0);

            foreach ($daftarBulan as $bulan) {
                $debit += (int) ($jumlahPerBulan[$bulan]['debit'] ?? 0);
                $kredit += (int) ($jumlahPerBulan[$bulan]['kredit'] ?? 0);

                foreach ($semuaField as $field) {
                    $rincian[$field] += (int) ($rincianPerBulan[$bulan][$field] ?? 0);
                }
            }

            $bulanTerakhir = end($daftarBulan);

            $hasil[$triwulan] = [
                'debit' => $debit,
                'kredit' => $kredit,
                'saldo' => $saldoPerBulan[$bulanTerakhir] ?? 0,
                'rincian' => $rincian,
            ];
        }

        return $hasil;
    }

    /**
     * Total 10 kolom pajak mentah (5 Debit + 5 Kredit) dijumlahkan dari
     * SEKUMPULAN baris (biasanya 12 baris/bulan milik 1 sekolah dalam 1
     * tahun) - permintaan user 2026-09-15, dipakai Tab 2 (Rekapitulasi
     * Pajak Seluruh Sekolah) supaya kolom "Total Debit/Kredit Setahun"
     * bisa dirinci per jenis pajak. Menerima Eloquent Collection ATAUpun
     * array biasa, isinya instance model ATAU array asosiatif per baris.
     *
     * @param  iterable<array|self>  $rows
     * @return array<string, int> Kunci = nama field (mis. 'ppn_debit'), nilai = total setahun.
     */
    public static function hitungTotalRincian(iterable $rows): array
    {
        $hasil = array_fill_keys(array_merge(self::FIELD_DEBIT, self::FIELD_KREDIT), 0);

        foreach ($rows as $row) {
            $data = $row instanceof self ? $row->toArray() : $row;

            foreach ($hasil as $field => $nilai) {
                $hasil[$field] += (int) ($data[$field] ?? 0);
            }
        }

        return $hasil;
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
