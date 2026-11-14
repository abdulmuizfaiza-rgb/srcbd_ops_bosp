<?php

namespace App\Services;

use App\Models\ProfilSekolah;
use App\Models\VerifikasiDokumenQr;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Str;

/**
 * Melayani mode "Dengan Tanda Tangan & Cap berupa QR Code" pada fitur
 * Unduh PDF (Fase 2 Tanda Tangan Kepala Sekolah & Cap Sekolah, permintaan
 * user 2026-11-14 - lihat progress-log.md utk konteks lengkap Fase 1 &
 * Fase 2), serta helper ukuran proporsional yang dipakai bareng oleh mode
 * "gambar" (tanda tangan + cap) supaya ukuran gambar di PDF dompdf akurat.
 *
 * PERBAIKAN BESAR (2026-11-14, laporan user "yang barcode ... ketika di
 * klik tombol Cetak/Unduh PDF kosong tidak ada"): versi SEBELUMNYA
 * men-generate QR Code sebagai markup <svg> inline (lewat
 * bacon/bacon-qr-code SvgImageBackEnd) karena diasumsikan dompdf
 * mendukung <svg> inline dengan baik. Setelah diuji LANGSUNG dengan
 * dompdf di lingkungan terpisah (bukan tebakan): QR Code TETAP tidak
 * muncul sama sekali di PDF sungguhan (walau sudah tampil benar di
 * pratinjau browser biasa) - dompdf ternyata TIDAK bisa diandalkan utk
 * elemen <svg> kompleks (path dengan banyak titik + <g transform>
 * bertingkat seperti yang dihasilkan bacon-qr-code). Diganti total ke
 * PNG mentah hasil gambar manual pakai GD (ekstensi PHP standar, SELALU
 * ada di hosting manapun, TIDAK perlu Imagick/library tambahan) -
 * ditempel sebagai <img src="data:image/png;base64,...">, pola yang
 * SUDAH TERBUKTI jalan di dompdf utk tanda tangan & cap sekolah. Matriks
 * modul QR mentah diambil langsung dari BaconQrCode\Encoder\Encoder -
 * bacon-qr-code tetap dipakai utk ENCODING QR-nya (bikin matriks modul
 * benar sesuai standar QR), hanya cara MENGGAMBARnya yang diganti dari
 * SVG ke GD. Hasil sudah diuji terbaca oleh pemindai QR sungguhan
 * (pyzbar) sebelum dikirim.
 */
class TandaTanganDigitalService
{
    /**
     * Catat satu baris riwayat baru (satu kali unduhan dokumen mode QR
     * Code) lalu kembalikan ['gambar' => <data URI PNG QR>, 'url' =>
     * <url verifikasi>, 'dokumen' => VerifikasiDokumenQr]. $tahun boleh
     * null untuk dokumen yang tidak terikat Tahun Anggaran tertentu.
     */
    public function siapkanQrUntukDokumen(ProfilSekolah $sekolah, string $jenisDokumen, ?int $tahun = null): array
    {
        $dokumen = VerifikasiDokumenQr::create([
            'uuid' => (string) Str::uuid(),
            'jenis_dokumen' => $jenisDokumen,
            'profil_sekolah_id' => $sekolah->id,
            'nama_sekolah' => $sekolah->nama_sekolah,
            'nama_kepala_sekolah' => $sekolah->nama_kepala_sekolah,
            'tahun' => $tahun,
            'dibuat_oleh' => auth()->id(),
        ]);

        $url = route('verifikasi-dokumen.show', $dokumen->uuid);

        return [
            'gambar' => $this->gambarQr($url),
            'url' => $url,
            'dokumen' => $dokumen,
        ];
    }

    /**
     * Gambar QR Code sebagai data URI PNG (bukan SVG lagi - lihat
     * docblock class di atas) yang meng-encode $isi (biasanya URL
     * verifikasi). $ukuranPx = resolusi gambar PNG yang di-generate
     * (DIGAMBAR besar lalu di-skalakan turun ke ukuran tampil lewat
     * ukuranProporsional() supaya tetap tajam saat dicetak).
     */
    public function gambarQr(string $isi, int $ukuranPx = 300): string
    {
        $qrCode = Encoder::encode($isi, ErrorCorrectionLevel::M());
        $matrix = $qrCode->getMatrix();
        $jumlahModul = $matrix->getWidth();

        // Quiet zone standar QR Code (margin modul kosong di sekeliling)
        // supaya tetap mudah dipindai - sama seperti default bacon-qr-code.
        $marginModul = 4;
        $totalModul = $jumlahModul + ($marginModul * 2);
        $ukuranModulPx = max(1, (int) floor($ukuranPx / $totalModul));
        $ukuranGambar = $ukuranModulPx * $totalModul;

        $img = imagecreatetruecolor($ukuranGambar, $ukuranGambar);
        $putih = imagecolorallocate($img, 255, 255, 255);
        $hitam = imagecolorallocate($img, 0, 0, 0);
        imagefilledrectangle($img, 0, 0, $ukuranGambar, $ukuranGambar, $putih);

        for ($y = 0; $y < $jumlahModul; $y++) {
            for ($x = 0; $x < $jumlahModul; $x++) {
                // 1 = modul gelap/hitam pada matriks bacon-qr-code.
                if ($matrix->get($x, $y) === 1) {
                    $px = ($x + $marginModul) * $ukuranModulPx;
                    $py = ($y + $marginModul) * $ukuranModulPx;
                    imagefilledrectangle(
                        $img, $px, $py,
                        $px + $ukuranModulPx - 1, $py + $ukuranModulPx - 1,
                        $hitam
                    );
                }
            }
        }

        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,'.base64_encode($data);
    }

    /**
     * Hitung lebar & tinggi PIKSEL EKSPLISIT (bukan CSS max-width/
     * max-height) supaya gambar ($dataUri, data URI base64) pas di
     * dalam kotak $maxLebar x $maxTinggi TANPA mengubah rasio aslinya.
     *
     * PERBAIKAN (2026-11-14, laporan user "posisi dan ukuran nya belum
     * benar"): versi SEBELUMNYA mengandalkan CSS `max-width`/
     * `max-height` pada tag <img> supaya ukurannya otomatis menyesuaikan
     * - TERBUKTI (lewat uji dompdf langsung pakai gambar tanda tangan &
     * cap sekolah ASLI milik user, bukan tebakan) dompdf TIDAK
     * menghitung ukuran gambar itu dengan benar SEBELUM tata letak
     * `text-align:center`-nya dihitung, akibatnya gambar jadi tidak
     * benar-benar center (nempel ke kiri) DAN ukurannya tidak presisi.
     * Begitu diganti ke atribut `width`/`height` EKSPLISIT (piksel
     * pasti, dihitung manual di sini dari ukuran asli file gambarnya),
     * posisi center & ukurannya langsung benar di dompdf.
     *
     * @return array{width: int, height: int}
     */
    public function ukuranProporsional(?string $dataUri, int $maxLebar, int $maxTinggi): array
    {
        $cadangan = ['width' => max(1, $maxLebar), 'height' => max(1, $maxTinggi)];

        if (empty($dataUri)) {
            return $cadangan;
        }

        $posisiKoma = strpos($dataUri, ',');
        $base64 = $posisiKoma !== false ? substr($dataUri, $posisiKoma + 1) : $dataUri;
        $biner = base64_decode($base64, true);
        $info = $biner !== false ? @getimagesizefromstring($biner) : false;

        if (! $info || $info[0] <= 0 || $info[1] <= 0) {
            return $cadangan;
        }

        [$lebarAsli, $tinggiAsli] = $info;
        $skala = min($maxLebar / $lebarAsli, $maxTinggi / $tinggiAsli);

        return [
            'width' => max(1, (int) round($lebarAsli * $skala)),
            'height' => max(1, (int) round($tinggiAsli * $skala)),
        ];
    }
}
