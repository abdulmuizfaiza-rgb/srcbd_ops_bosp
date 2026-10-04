<?php

namespace Tests;

use App\Models\PengaturanTampilan;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // PengaturanTampilan::current() di-cache per proses (lihat model)
        // supaya cepat di request sungguhan, tapi PHPUnit menjalankan semua
        // test dalam satu proses PHP - reset di sini supaya tiap test mulai
        // dari kondisi bersih, tidak membawa data cache dari test sebelumnya.
        PengaturanTampilan::lupakanCache();
    }

    /**
     * Audit 2026-10-04: override global $this->actingAs() supaya SEMUA
     * test (lama maupun baru) otomatis dianggap sudah lolos verifikasi 2FA
     * Google Authenticator.
     *
     * Sejak middleware EnsureGoogleAuthenticatorVerified ditambahkan
     * 2026-09-27 (berlaku utk SEMUA route tanpa kecuali role, permintaan
     * user saat itu), setiap akun hasil actingAs() yang belum aktivasi
     * 2FA otomatis dialihkan (redirect 302) ke halaman aktivasi/verifikasi
     * 2FA - SEBELUM request test benar-benar sampai ke halaman yang mau
     * diuji. Akibatnya ratusan test lama yang ditulis SEBELUM fitur 2FA
     * ada (assertOk/assertForbidden/assertNotFound ke halaman terproteksi)
     * gagal dengan "Expected 200 tapi diterima 302", padahal tidak ada
     * yang salah di aplikasinya - test-nya saja yang belum diperbarui.
     *
     * Override ini HANYA mengubah kondisi test (menandai 2FA sudah
     * selesai), TIDAK menyentuh kode aplikasi/middleware sama sekali.
     *
     * Kalau nanti ada test yang SENGAJA ingin menguji alur 2FA itu
     * sendiri (mis. halaman aktivasi/verifikasi), panggil
     * parent::actingAs($user, $guard) langsung di test tsb supaya tidak
     * ikut "dilewati" oleh override ini.
     */
    public function actingAs(UserContract $user, $guard = null): static
    {
        if ($user instanceof User && is_null($user->google2fa_aktif_at)) {
            $user->forceFill(['google2fa_aktif_at' => now()])->save();
        }

        $this->withSession(['google2fa_terverifikasi' => true]);

        return parent::actingAs($user, $guard);
    }
}
