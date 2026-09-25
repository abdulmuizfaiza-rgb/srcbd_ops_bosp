<?php

use App\Models\ProfilSekolah;
use App\Models\RincianBelanjaModalBmd;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill PPK tab "BMD" (permintaan user 2026-09-22 - perubahan
 * keputusan, PPK yang sebelumnya manual sekarang OTOMATIS diambil dari
 * field "Nama Kepala Sekolah" pada Profil Sekolah, jawaban
 * AskUserQuestion 2026-09-22 "otomatis berdasarkan field Nama Kepala
 * Sekolah pada profil sekolah (berlaku untuk di aplikasi dan hasil file
 * excel nya)" - lihat App\Models\RincianBelanjaModalBmd::ppkOtomatis()).
 *
 * Migration ini HANYA menimpa kolom `ppk` pada baris yang SUDAH ADA
 * dengan nilai Nama Kepala Sekolah dari relasi profilSekolah() -
 * seluruh baris BARU sejak update ini otomatis terisi lewat
 * ppkOtomatis() di jalur Livewire Index/Import, migration ini murni
 * utk data LAMA yang sudah tersimpan sebelum keputusan ini berubah.
 *
 * down() TIDAK mengembalikan nilai PPK manual lama (nilai lama sudah
 * tertimpa & tidak disimpan di tempat lain) - sesuai pola precedent
 * backfill migration lain di codebase ini (mis. 2026_09_19_070000_...),
 * backfill migration bersifat satu arah/best-effort, bukan reversible
 * penuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        RincianBelanjaModalBmd::with('profilSekolah')
            ->chunkById(200, function ($baris) {
                foreach ($baris as $satu) {
                    $namaKepalaSekolah = $satu->profilSekolah?->nama_kepala_sekolah;
                    $ppkOtomatis = RincianBelanjaModalBmd::ppkOtomatis($namaKepalaSekolah);

                    if ($satu->ppk !== $ppkOtomatis) {
                        $satu->updateQuietly(['ppk' => $ppkOtomatis]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Sengaja tidak reversible - lihat docblock di atas.
    }
};
