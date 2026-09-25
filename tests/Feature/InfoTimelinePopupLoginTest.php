<?php

namespace Tests\Feature;

use App\Models\DeadlinePekerjaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Round 9 Bagian C (permintaan user 2026-09-23, poin 4): "informasi
 * timeline ini muncul di bagian tengah berupa halaman pop-up berukuran
 * kecil ketika admin bosp/admin OPS berhasil login dan muncul selama 5
 * detik dengan pop-up bergaya Animasi dan berwarna menarik. kemudian
 * setelah 5 detik info timeline ini muncul sebagai running text di
 * bagian atas judul menu yang muncul dari sebelah kanan yang berjalan
 * sampai tulisan judul huruf terakhir."
 *
 * Jawaban AskUserQuestion yang dipakai:
 * - "Isi info" -> "Ringkasan jumlah + yang paling mendesak (Recommended)"
 * - "Kondisi kosong" -> "Dilewati saja (Recommended)"
 * - "Perilaku marquee" -> "Berputar terus (looping)"
 *
 * Alur: App\Models\DeadlinePekerjaan::ringkasanTerlambatUntukPopupLogin()
 * menghitung ringkasan -> resources/views/livewire/pages/auth/login.blade.php
 * ::login() menandai "baru saja login" lewat session flash -> layout
 * resources/views/layouts/app.blade.php membaca flag itu (session()->pull(),
 * sekali pakai) SAAT DIRENDER DI SERVER lalu men-seed langsung ke x-data
 * Alpine kalau ringkasannya tidak kosong.
 *
 * CATATAN DESAIN: pendekatan AWAL memakai $this->dispatch() dari
 * layout.navigation::mount() + window.Livewire.on() di sisi client, TAPI
 * terbukti lewat pengujian Playwright manual bahwa event yang dipicu SAAT
 * halaman pertama kali dimuat (bukan dari klik/interaksi user) datang
 * SEBELUM listener Alpine x-init sempat terpasang (race condition timing
 * browser, BUKAN bug logika) - popup tidak pernah muncul. Diganti jadi
 * dihitung & di-seed langsung di server (@php di layouts/app.blade.php)
 * yang sepenuhnya menghindari race itu - lihat docblock lengkap di sana.
 *
 * Test di file ini menguji lapisan backend (model) + hasil render HTML
 * (lewat HTTP GET biasa ke halaman yang berlayout "app", BUKAN
 * Livewire::test() yang tidak melewati resolusi layout Blade) - animasi/
 * transisi/marquee CSS sudah diverifikasi terpisah lewat Blade compile
 * check + tinjauan visual Playwright manual (lihat progress-log.md Round
 * 9 Bagian C).
 */
class InfoTimelinePopupLoginTest extends TestCase
{
    use RefreshDatabase;

    private function aturTerlambat(string $kunciMenu, int $triwulan, int $hariLalu): void
    {
        DeadlinePekerjaan::aturDeadline(
            $kunciMenu,
            now()->year,
            $triwulan,
            now()->subDays($hariLalu)->toDateString(),
            null,
        );
    }

    private function aturBelumLewat(string $kunciMenu, int $triwulan, int $hariLagi): void
    {
        DeadlinePekerjaan::aturDeadline(
            $kunciMenu,
            now()->year,
            $triwulan,
            now()->addDays($hariLagi)->toDateString(),
            null,
        );
    }

    // --- Model: DeadlinePekerjaan::ringkasanTerlambatUntukPopupLogin() ---

    public function test_ringkasan_null_kalau_belum_ada_deadline_sama_sekali(): void
    {
        $this->assertNull(DeadlinePekerjaan::ringkasanTerlambatUntukPopupLogin('bosp'));
        $this->assertNull(DeadlinePekerjaan::ringkasanTerlambatUntukPopupLogin('ops'));
    }

    public function test_ringkasan_null_kalau_deadline_ada_tapi_semua_belum_lewat(): void
    {
        $this->aturBelumLewat('pendataan-bosp.pajak-bosp-reguler', 3, 10);

        $this->assertNull(DeadlinePekerjaan::ringkasanTerlambatUntukPopupLogin('bosp'));
    }

    public function test_ringkasan_menghitung_jumlah_dan_yang_paling_lama_terlambat(): void
    {
        // Paling lama terlambat (30 hari) - ini yang harus jadi "paling mendesak".
        $this->aturTerlambat('pendataan-bosp.pajak-bosp-reguler', 2, 30);
        $this->aturTerlambat('pendataan-bosp.penerimaan-honor-ptk', 2, 5);
        $this->aturBelumLewat('pendataan-bosp.langganan-daya-jasa', 3, 15); // belum lewat, tidak dihitung

        $ringkasan = DeadlinePekerjaan::ringkasanTerlambatUntukPopupLogin('bosp');

        $this->assertNotNull($ringkasan);
        $this->assertSame(2, $ringkasan['jumlah']);
        $this->assertSame('Pajak BOSP Reguler', $ringkasan['labelPalingMendesak']);
        $this->assertSame(2, $ringkasan['triwulanPalingMendesak']);
    }

    public function test_ringkasan_per_kategori_tidak_tercampur(): void
    {
        $this->aturTerlambat('pendataan-bosp.pajak-bosp-reguler', 1, 10);
        $this->aturTerlambat('pendataan-ops.lampiran-2a', 1, 10);

        $ringkasanBosp = DeadlinePekerjaan::ringkasanTerlambatUntukPopupLogin('bosp');
        $ringkasanOps = DeadlinePekerjaan::ringkasanTerlambatUntukPopupLogin('ops');

        $this->assertSame(1, $ringkasanBosp['jumlah']);
        $this->assertSame('Pajak BOSP Reguler', $ringkasanBosp['labelPalingMendesak']);
        $this->assertSame(1, $ringkasanOps['jumlah']);
        $this->assertSame('Lampiran 2a', $ringkasanOps['labelPalingMendesak']);
    }

    public function test_ringkasan_mencakup_semua_triwulan_bukan_cuma_triwulan_aktif(): void
    {
        // Deadline TW1 yang lewat harus tetap terhitung walau triwulan
        // "berjalan" sekarang sudah bukan TW1 lagi.
        $this->aturTerlambat('pendataan-bosp.rekap-rkas', 1, 200);

        $ringkasan = DeadlinePekerjaan::ringkasanTerlambatUntukPopupLogin('bosp');

        $this->assertNotNull($ringkasan);
        $this->assertSame(1, $ringkasan['triwulanPalingMendesak']);
    }

    // --- Integration: render layouts/app.blade.php sungguhan lewat HTTP GET ---

    public function test_admin_bosp_melihat_popup_setelah_login_kalau_ada_yang_terlambat(): void
    {
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);
        $this->aturTerlambat('pendataan-bosp.pajak-bosp-reguler', 1, 10);

        session(['tampilkan_popup_timeline_login' => true]);

        $response = $this->actingAs($adminBosp)->get(route('profil-sekolah.index'));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('tampilPopup: true', $html);
        $this->assertStringContainsString('Pajak BOSP Reguler', $html);
    }

    public function test_admin_ops_melihat_popup_setelah_login_kalau_ada_yang_terlambat(): void
    {
        $adminOps = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);
        $this->aturTerlambat('pendataan-ops.lampiran-2b', 1, 10);

        session(['tampilkan_popup_timeline_login' => true]);

        $response = $this->actingAs($adminOps)->get(route('profil-sekolah.index'));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('tampilPopup: true', $html);
        $this->assertStringContainsString('Lampiran 2b', $html);
    }

    public function test_tidak_ada_popup_kalau_tidak_sedang_login_baru(): void
    {
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);
        $this->aturTerlambat('pendataan-bosp.pajak-bosp-reguler', 1, 10);

        // Sengaja TIDAK set session flag 'tampilkan_popup_timeline_login'
        // - mensimulasikan kunjungan halaman biasa (bukan tepat setelah login).

        $response = $this->actingAs($adminBosp)->get(route('profil-sekolah.index'));

        $response->assertOk();
        $this->assertStringContainsString('tampilPopup: false', $response->getContent());
    }

    public function test_tidak_ada_popup_kalau_tidak_ada_yang_terlambat_walau_baru_login(): void
    {
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);
        // Tidak ada deadline terlambat sama sekali.

        session(['tampilkan_popup_timeline_login' => true]);

        $response = $this->actingAs($adminBosp)->get(route('profil-sekolah.index'));

        $response->assertOk();
        $this->assertStringContainsString('tampilPopup: false', $response->getContent());
    }

    public function test_superadmin_tidak_pernah_melihat_popup(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $this->aturTerlambat('pendataan-bosp.pajak-bosp-reguler', 1, 10);

        session(['tampilkan_popup_timeline_login' => true]);

        $response = $this->actingAs($superadmin)->get(route('dashboard'));

        $response->assertOk();
        $this->assertStringContainsString('tampilPopup: false', $response->getContent());
    }

    public function test_admin_ops_tidak_melihat_popup_dari_keterlambatan_kategori_bosp(): void
    {
        $adminOps = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);
        $this->aturTerlambat('pendataan-bosp.pajak-bosp-reguler', 1, 10); // kategori bosp, bukan ops

        session(['tampilkan_popup_timeline_login' => true]);

        $response = $this->actingAs($adminOps)->get(route('profil-sekolah.index'));

        $response->assertOk();
        $this->assertStringContainsString('tampilPopup: false', $response->getContent());
    }

    public function test_flag_session_hanya_terpakai_1_kali(): void
    {
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);
        $this->aturTerlambat('pendataan-bosp.pajak-bosp-reguler', 1, 10);

        session(['tampilkan_popup_timeline_login' => true]);

        // Kunjungan pertama (tepat setelah login) - popup muncul.
        $response1 = $this->actingAs($adminBosp)->get(route('profil-sekolah.index'));
        $this->assertStringContainsString('tampilPopup: true', $response1->getContent());

        // Kunjungan kedua (pindah halaman lain, flag sudah dikonsumsi) -
        // TIDAK boleh muncul lagi walau tahap kerjanya masih sama-sama
        // terlambat. Sengaja masih ke halaman yang sama (profil-sekolah,
        // dikecualikan dari gate onboarding) supaya test ini murni
        // menguji "flag sudah terpakai", bukan tercampur efek redirect
        // gate onboarding.
        $response2 = $this->actingAs($adminBosp)->get(route('profil-sekolah.index'));
        $this->assertStringContainsString('tampilPopup: false', $response2->getContent());
    }

    // --- Login page: penandaan session flag ---

    public function test_login_berhasil_menandai_session_flag_popup_timeline(): void
    {
        $user = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Volt::test('pages.auth.login')
            ->set('form.username', $user->username)
            ->set('form.password', 'password')
            ->call('login')
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertTrue((bool) session('tampilkan_popup_timeline_login'));
    }

    public function test_login_gagal_tidak_menandai_session_flag_popup_timeline(): void
    {
        $user = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Volt::test('pages.auth.login')
            ->set('form.username', $user->username)
            ->set('form.password', 'salah')
            ->call('login');

        $this->assertFalse((bool) session('tampilkan_popup_timeline_login'));
    }
}
