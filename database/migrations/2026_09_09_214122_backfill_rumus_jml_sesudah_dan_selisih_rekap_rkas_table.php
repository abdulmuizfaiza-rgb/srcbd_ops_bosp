<?php

use App\Models\RekapRkas;
use Illuminate\Database\Migrations\Migration;

/**
 * Data migration (BUKAN migration skema - tidak ada kolom yang
 * ditambah/diubah) - menghitung ulang kolom Jml Sesudah & Selisih untuk
 * SEMUA 5 kategori Belanja (Pegawai, Pemeliharaan, Barang dan Jasa,
 * Peralatan & Mesin, Aset Lainnya - lihat App\Models\RekapRkas::
 * KATEGORI_RUMUS), serta Sebelum/Sesudah/Selisih pada baris JUMLAH
 * (lihat App\Models\RekapRkas::hitungJumlahBaris()), pada SEMUA data
 * Rekap RKAS yang sudah ada di database, supaya langsung konsisten
 * dengan rumus baru begitu update ini dipasang - sesuai jawaban
 * AskUserQuestion 2026-09-09 lanjutan ke-4: "Hitung ulang sekarang
 * (backfill)" (rumus awal untuk 4 kategori pertama), yang berlaku juga
 * untuk cakupan tambahan (kategori Aset Lainnya & baris JUMLAH) yang
 * ditentukan user pada lanjutan ke-5 di request/percakapan yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        RekapRkas::query()->chunkById(200, function ($daftarRekap) {
            foreach ($daftarRekap as $rekap) {
                $data = [];
                $sebelumPerKategori = [];
                $jmlSesudahPerKategori = [];

                foreach (RekapRkas::KATEGORI_RUMUS as $kategori) {
                    [$jmlSesudah, $selisih] = RekapRkas::hitungJmlSesudahDanSelisih(
                        $rekap->{$kategori.'_sebelum'},
                        $rekap->{$kategori.'_realisasi_tahap1'},
                        $rekap->{$kategori.'_perubahan_tahap2'},
                    );

                    $data[$kategori.'_jml_sesudah'] = $jmlSesudah;
                    $data[$kategori.'_selisih'] = $selisih;

                    $sebelumPerKategori[$kategori] = $rekap->{$kategori.'_sebelum'};
                    $jmlSesudahPerKategori[$kategori] = $jmlSesudah;
                }

                [$data['jumlah_sebelum'], $data['jumlah_sesudah'], $data['jumlah_selisih']] =
                    RekapRkas::hitungJumlahBaris($sebelumPerKategori, $jmlSesudahPerKategori);

                $rekap->update($data);
            }
        });
    }

    public function down(): void
    {
        // Data migration murni (menghitung ulang nilai, bukan mengubah
        // skema) - nilai manual sebelumnya sudah ditimpa begitu migration
        // ini berjalan, jadi tidak ada yang bermakna untuk "dikembalikan"
        // di sini. Nilai-nilai manual tsb memang cuma placeholder
        // sementara (lihat catatan di migration create_rekap_rkas_table)
        // sampai rumus ini ditentukan.
    }
};
