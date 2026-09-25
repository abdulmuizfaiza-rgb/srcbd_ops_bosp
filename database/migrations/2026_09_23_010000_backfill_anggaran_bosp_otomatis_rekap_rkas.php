<?php

use App\Models\DanaBospTahap;
use App\Models\RekapRkas;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill kolom rekap_rkas.anggaran_bosp mengikuti Total Penerimaan BOSP
 * Setahun (DanaBospTahap::total_penerimaan_setahun) sekolah+tahun yang
 * sama, karena field ini sekarang otomatis (permintaan user 2026-09-23,
 * lihat docblock App\Models\RekapRkas). Kalau sekolah itu belum punya
 * data Dana BOSP Tahap untuk tahun bersangkutan, anggaran_bosp diisi null
 * (mengikuti gate baru - lihat Livewire\PendataanBosp\RekapRkas\
 * Index::danaBospTahapTerisi()).
 *
 * Sengaja TIDAK reversible (mengikuti pola migration backfill lain di
 * kodebase ini).
 */
return new class extends Migration
{
    public function up(): void
    {
        RekapRkas::chunkById(200, function ($baris) {
            foreach ($baris as $satu) {
                $danaBosp = DanaBospTahap::where('profil_sekolah_id', $satu->profil_sekolah_id)
                    ->where('tahun', $satu->tahun)
                    ->first();

                $anggaranBospBaru = RekapRkas::anggaranBospOtomatis($danaBosp?->total_penerimaan_setahun);

                if ($satu->anggaran_bosp !== $anggaranBospBaru) {
                    $satu->updateQuietly(['anggaran_bosp' => $anggaranBospBaru]);
                }
            }
        });
    }

    public function down(): void
    {
        // Sengaja tidak reversible - lihat docblock di atas.
    }
};
