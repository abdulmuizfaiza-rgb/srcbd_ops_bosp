<?php

namespace App\Livewire\Concerns;

/**
 * Fitur zoom in/zoom out KHUSUS TABEL data (lewat CSS zoom pada kotak
 * pembungkus tabel, bukan pada seluruh halaman) - dipakai di menu-menu
 * bertabel padat data (mis. Profil Sekolah, Identitas OPS) supaya pengguna
 * bisa memperbesar/memperkecil tampilan tabelnya saja tanpa ikut membesarkan
 * toolbar/filter/judul di atasnya, dan tanpa mengandalkan zoom bawaan
 * browser.
 */
trait HasZoomTampilan
{
    public int $zoomPercent = 100;

    private const ZOOM_MIN = 70;

    private const ZOOM_MAX = 150;

    private const ZOOM_STEP = 10;

    public function zoomIn(): void
    {
        $this->zoomPercent = min(self::ZOOM_MAX, $this->zoomPercent + self::ZOOM_STEP);
    }

    public function zoomOut(): void
    {
        $this->zoomPercent = max(self::ZOOM_MIN, $this->zoomPercent - self::ZOOM_STEP);
    }

    public function zoomReset(): void
    {
        $this->zoomPercent = 100;
    }
}
