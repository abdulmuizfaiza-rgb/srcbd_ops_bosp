<?php

namespace App\Services\Concerns;

/**
 * Cari lokasi binary command-line eksternal (pg_dump/mysqldump/psql dst)
 * dengan urutan: (1) path yang dikonfigurasi user lewat .env kalau
 * file-nya memang ada, (2) pola glob ke lokasi umum install di Windows
 * (versi TERBARU yang ketemu dipakai kalau ada lebih dari satu), (3)
 * fallback ke nama command polos (mengandalkan PATH sistem).
 *
 * Diekstrak dari App\Services\BackupService (round DUA PULUH TUJUH,
 * 2026-09-24) menjadi trait terpisah supaya bisa dipakai bersama oleh
 * App\Services\RestoreService (fitur Restore, permintaan user
 * 2026-10-14 poin 2) - PERILAKU method ini TIDAK berubah sama sekali
 * dari sebelumnya, murni pemindahan lokasi kode (DRY) supaya tidak
 * ditulis ulang 2x.
 */
trait ResolvesExternalBinary
{
    /** @param  string[]  $polaGlobFallback */
    protected function resolveBinary(string $namaBinary, ?string $pathTerkonfigurasi, array $polaGlobFallback = []): string
    {
        if ($pathTerkonfigurasi !== null && $pathTerkonfigurasi !== '' && is_file($pathTerkonfigurasi)) {
            return $pathTerkonfigurasi;
        }

        foreach ($polaGlobFallback as $pola) {
            $kandidat = glob($pola) ?: [];

            if ($kandidat !== []) {
                rsort($kandidat);

                return $kandidat[0];
            }
        }

        return $namaBinary;
    }
}
