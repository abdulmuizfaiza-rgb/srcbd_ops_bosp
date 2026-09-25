<?php

namespace App\Support;

use RuntimeException;

/**
 * Utilitas sederhana berbasis GD untuk meng-crop gambar upload secara
 * otomatis ketika ukurannya (dimensi) melebihi ukuran target, supaya
 * gambar latar (background) selalu pas tanpa distorsi.
 *
 * Jika gambar yang diunggah sudah lebih kecil atau sama dengan target,
 * gambar TIDAK diubah (dikembalikan apa adanya) - hanya di-crop apabila
 * "terlalu besar", sesuai permintaan.
 */
class ImageCropper
{
    /**
     * Crop (cover-fit, dipotong dari tengah) isi file gambar di $path agar
     * pas pada kotak $targetWidth x $targetHeight, HANYA jika salah satu
     * dimensi aslinya melebihi target. Mengembalikan data biner gambar
     * (JPEG) hasil crop, atau null jika tidak perlu di-crop.
     */
    public static function cropIfTooLarge(string $path, int $targetWidth, int $targetHeight): ?string
    {
        $info = @getimagesize($path);

        if ($info === false) {
            throw new RuntimeException('File yang diunggah bukan gambar yang valid.');
        }

        [$width, $height, $type] = $info;

        if ($width <= $targetWidth && $height <= $targetHeight) {
            return null;
        }

        $source = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            default => throw new RuntimeException('Format gambar tidak didukung (gunakan JPG, PNG, WEBP, atau GIF).'),
        };

        if ($source === false) {
            throw new RuntimeException('Gagal membaca gambar yang diunggah.');
        }

        $targetRatio = $targetWidth / $targetHeight;
        $sourceRatio = $width / $height;

        if ($sourceRatio > $targetRatio) {
            $cropHeight = $height;
            $cropWidth = (int) round($height * $targetRatio);
            $cropX = (int) round(($width - $cropWidth) / 2);
            $cropY = 0;
        } else {
            $cropWidth = $width;
            $cropHeight = (int) round($width / $targetRatio);
            $cropX = 0;
            $cropY = (int) round(($height - $cropHeight) / 2);
        }

        $destination = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled(
            $destination,
            $source,
            0,
            0,
            $cropX,
            $cropY,
            $targetWidth,
            $targetHeight,
            $cropWidth,
            $cropHeight
        );

        ob_start();
        imagejpeg($destination, null, 90);
        $binary = ob_get_clean();

        imagedestroy($source);
        imagedestroy($destination);

        return $binary ?: null;
    }
}
