<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     *
     * ROUND KEENAM BELAS (2026-09-24, bagian 2): sebelumnya route "/"
     * SELALU redirect ke "/dashboard" (`Route::redirect('/',
     * '/dashboard')`, UNCONDITIONAL utk siapapun). Sejak permintaan user
     * "landing page yang muncul sebelum halaman form login", route "/"
     * SEKARANG menampilkan landing page publik (App\Livewire\Beranda\Index)
     * utk pengunjung yang BELUM login - assert diganti dari "redirect ke
     * /dashboard" jadi "berhasil dimuat (200)" utk mencerminkan perilaku
     * baru ini (test perilaku "user sudah login tetap diarahkan ke
     * /dashboard" ada di
     * tests/Feature/BerandaTest.php::test_user_yang_sudah_login_diarahkan_ke_dashboard_bukan_beranda).
     * `RefreshDatabase` ditambahkan krn halaman ini sekarang membaca data
     * dari database (jumlah sekolah, dst.) - test skeleton bawaan Laravel
     * ini sebelumnya tidak butuh DB sama sekali (murni redirect).
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }
}
