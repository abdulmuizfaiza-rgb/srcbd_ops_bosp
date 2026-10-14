<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Permintaan user 2026-10-14 poin 3 (Sinkronisasi dan Tarik Data):
 * tambah kolom `sync_uuid` ke 12 modul yang TIDAK punya kunci unik
 * alami (baris-nya murni daftar item, boleh duplikat - lihat
 * App\Support\RegistriModulSekolah::tabelDisinkronkan()).
 *
 * Kenapa perlu kolom baru: Lokal dan Online adalah 2 database yang
 * BERBEDA dan TERPISAH sejak awal - ID auto-increment bawaan setiap
 * tabel SAMA SEKALI TIDAK NYAMBUNG antara keduanya (baris nomor 5 di
 * Lokal belum tentu baris yang sama dengan nomor 5 di Online). Untuk
 * modul yang SUDAH punya kunci unik alami (misal Rekap RKAS = kombinasi
 * profil_sekolah_id+tahun), kunci itu dipakai langsung untuk mencocokkan
 * baris yang sama di 2 database. Untuk modul TANPA kunci unik alami
 * (misal Langganan Daya dan Jasa - 1 sekolah boleh punya banyak baris
 * "Listrik" yang sengaja boleh duplikat), SATU-SATUNYA cara memastikan
 * sinkronisasi mencocokkan "baris yang sama, bukan baris baru yang
 * mirip" adalah dengan ID unik buatan sendiri (UUID) yang dibuat SAAT
 * baris itu pertama kali disimpan dan IKUT DIKIRIM saat sinkronisasi -
 * jawaban AskUserQuestion 2026-10-14: "Ya, boleh (Recommended)".
 *
 * Kolom ini TIDAK ditampilkan di mana pun di antarmuka aplikasi (murni
 * dipakai di belakang layar oleh App\Services\SyncEngine &
 * App\Models\Concerns\Syncable) dan TIDAK ADA di #[Fillable] model
 * manapun (tidak boleh diisi lewat input pengguna).
 */
return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    protected array $tabel = [
        'lampiran_2a',
        'lampiran_2b',
        'lampiran_2c',
        'langganan_daya_jasa',
        'rincian_pemeliharaan',
        'rincian_pemeliharaan_pc',
        'biaya_pendaftaran_lomba',
        'belanja_honor_kegiatan',
        'rincian_belanja_modal',
        'rincian_belanja_modal_bmd',
        'rincian_belanja_barang_habis_pakai',
        'stock_opname_barang_persediaan',
    ];

    public function up(): void
    {
        foreach ($this->tabel as $nama) {
            Schema::table($nama, function (Blueprint $table) {
                $table->string('sync_uuid', 36)->nullable()->unique()->after('id');
            });
        }

        // Backfill baris yang SUDAH ADA sebelum update ini dipasang -
        // tiap baris lama dapat 1 UUID baru (baris lama belum pernah
        // disinkronkan sebelumnya, jadi UUID baru di sini tidak masalah
        // - akan jadi identitas tetapnya mulai sekarang).
        foreach ($this->tabel as $nama) {
            DB::table($nama)->whereNull('sync_uuid')->orderBy('id')->chunkById(500, function ($baris) use ($nama) {
                foreach ($baris as $satu) {
                    DB::table($nama)->where('id', $satu->id)->update(['sync_uuid' => (string) Str::uuid()]);
                }
            });
        }

        foreach ($this->tabel as $nama) {
            Schema::table($nama, function (Blueprint $table) {
                $table->string('sync_uuid', 36)->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tabel as $nama) {
            Schema::table($nama, function (Blueprint $table) {
                $table->dropColumn('sync_uuid');
            });
        }
    }
};
