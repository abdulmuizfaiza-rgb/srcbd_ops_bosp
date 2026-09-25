<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Helper query bersama untuk menu Unduhan (Pendataan OPS > Unduhan) -
 * dipakai baik oleh proses Export Excel maupun pembuatan PDF, supaya
 * logika filter Triwulan+Tahun & urutan datanya konsisten di kedua jalur.
 *
 * - Admin OPS ($profilSekolahId diisi): hanya data sekolahnya sendiri,
 *   diurutkan memakai kolom urutan bawaan tiap lampiran (mis. Nama PTK).
 * - Superadmin ($profilSekolahId null): data SEMUA sekolah yang
 *   terdaftar, diurutkan Status (Negeri lebih dulu, baru Swasta) ->
 *   Kecamatan -> Nama Sekolah - meniru persis urutan yang sudah dipakai
 *   di menu Profil Sekolah (lihat App\Livewire\ProfilSekolah\Index) -
 *   lalu kolom urutan bawaan lampiran sebagai pengurutan berikutnya.
 */
class UnduhanLampiranData
{
    /**
     * @return Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    public static function ambil(
        Builder $query,
        string $table,
        int $triwulan,
        int $tahun,
        ?int $profilSekolahId,
        string $urutanBawaan = 'nama_ptk'
    ): Collection {
        $query->with('profilSekolah')
            ->where($table.'.triwulan', $triwulan)
            ->where($table.'.tahun', $tahun);

        if ($profilSekolahId !== null) {
            return $query
                ->where($table.'.profil_sekolah_id', $profilSekolahId)
                ->orderBy($table.'.'.$urutanBawaan)
                ->get();
        }

        return $query
            ->join('profil_sekolah', 'profil_sekolah.id', '=', $table.'.profil_sekolah_id')
            ->select($table.'.*')
            ->orderByRaw("CASE WHEN profil_sekolah.status = 'negeri' THEN 0 WHEN profil_sekolah.status = 'swasta' THEN 1 ELSE 2 END")
            ->orderByRaw('profil_sekolah.kecamatan IS NULL')
            ->orderBy('profil_sekolah.kecamatan')
            ->orderBy('profil_sekolah.nama_sekolah')
            ->orderBy($table.'.'.$urutanBawaan)
            ->get();
    }
}
