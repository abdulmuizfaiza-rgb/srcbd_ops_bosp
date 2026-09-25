<?php

namespace Tests\Feature;

use App\Mail\PasswordDiresetSuperadmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fitur baru (2026-09-05): tombol "Reset Password" milik Superadmin di
 * menu Kelola Pengguna, khusus utk akun Admin OPS/Admin BOSP - satu-
 * satunya cara akun ini reset password sejak jalur pemulihan mandiri
 * dinonaktifkan (lihat tests/Feature/Auth/PemulihanAkunTest.php).
 */
class PenggunaResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function superadmin(): User
    {
        return User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
    }

    public function test_superadmin_bisa_reset_password_admin_ops_dan_email_terkirim(): void
    {
        Mail::fake();

        $passwordLama = Hash::make('password-lama');
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'username' => 'ops-sekolah-a@contoh.sch.id',
            'password' => $passwordLama,
            'must_change_password' => false,
        ]);

        Livewire::actingAs($this->superadmin())
            ->test('pengguna.index')
            ->call('konfirmasiReset', $adminOps->id)
            ->call('resetPassword');

        $adminOps->refresh();

        $this->assertNotEquals($passwordLama, $adminOps->password);
        $this->assertTrue($adminOps->must_change_password);

        Mail::assertSent(PasswordDiresetSuperadmin::class, function ($mail) use ($adminOps) {
            return $mail->hasTo($adminOps->username) && $mail->user->is($adminOps);
        });
    }

    public function test_superadmin_bisa_reset_password_admin_bosp(): void
    {
        Mail::fake();

        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'username' => 'bosp-sekolah-b@contoh.sch.id',
            'must_change_password' => false,
        ]);

        Livewire::actingAs($this->superadmin())
            ->test('pengguna.index')
            ->call('konfirmasiReset', $adminBosp->id)
            ->call('resetPassword');

        $this->assertTrue($adminBosp->fresh()->must_change_password);
        Mail::assertSent(PasswordDiresetSuperadmin::class);
    }

    public function test_password_baru_yang_dibuat_6_karakter_kombinasi_huruf_dan_angka(): void
    {
        Mail::fake();

        $adminOps = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);

        Mail::fake();
        Livewire::actingAs($this->superadmin())
            ->test('pengguna.index')
            ->call('konfirmasiReset', $adminOps->id)
            ->call('resetPassword');

        Mail::assertSent(PasswordDiresetSuperadmin::class, function ($mail) {
            return strlen($mail->passwordBaru) === 6
                && preg_match('/[A-Za-z]/', $mail->passwordBaru) === 1
                && preg_match('/[0-9]/', $mail->passwordBaru) === 1
                && preg_match('/^[A-Za-z0-9]+$/', $mail->passwordBaru) === 1;
        });
    }

    /**
     * PENTING (2026-09-05, lanjutan): sebelumnya kalau Mail::send() gagal
     * (mis. SMTP di .env server masih default/salah/timeout - kasus nyata
     * yang dilaporkan user, lihat catatan di resetPassword()), exception-
     * nya TIDAK ditangkap sama sekali - password akun sebenarnya SUDAH
     * terlanjur diganti di database tapi Superadmin tidak pernah melihat
     * pesan status/password cadangan karena request-nya ikut gagal total.
     * Test ini mengunci perbaikannya: password TETAP berhasil diganti &
     * must_change_password TETAP true walau pengiriman email gagal, dan
     * pesan status yang tampil ke Superadmin membedakan dengan jelas
     * kondisi "email GAGAL terkirim" (bukan pesan sukses biasa) supaya
     * Superadmin tahu harus menyampaikan password itu secara manual.
     */
    public function test_reset_password_tetap_berhasil_walau_email_gagal_terkirim(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->andReturnSelf();
        Mail::shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('SMTP tidak bisa dihubungi (simulasi test).'));

        $passwordLama = Hash::make('password-lama');
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'username' => 'ops-gagal-email@contoh.sch.id',
            'password' => $passwordLama,
            'must_change_password' => false,
        ]);

        Livewire::actingAs($this->superadmin())
            ->test('pengguna.index')
            ->call('konfirmasiReset', $adminOps->id)
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertSee('GAGAL terkirim');

        $adminOps->refresh();

        $this->assertNotEquals($passwordLama, $adminOps->password);
        $this->assertTrue($adminOps->must_change_password);
    }

    public function test_reset_password_tidak_bisa_dipakai_untuk_akun_superadmin(): void
    {
        Mail::fake();
        $this->withoutExceptionHandling();

        $superadminLain = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        Livewire::actingAs($this->superadmin())
            ->test('pengguna.index')
            ->call('konfirmasiReset', $superadminLain->id);
    }

    public function test_tombol_reset_password_muncul_utk_tab_admin_ops(): void
    {
        User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);
        $superadmin = $this->superadmin();

        $component = Livewire::actingAs($superadmin)->test('pengguna.index');
        $component->call('pindahTab', User::LEVEL_ADMIN_OPS);

        $this->assertStringContainsString('Reset Password', $component->html());
    }

    public function test_tombol_reset_password_tidak_muncul_utk_tab_superadmin(): void
    {
        $superadmin = $this->superadmin();

        $component = Livewire::actingAs($superadmin)->test('pengguna.index');
        $component->call('pindahTab', User::LEVEL_SUPERADMIN);

        // Catatan: tidak bisa assertStringNotContainsString('Reset Password', ...)
        // di sini, karena label statis "Reset Password" tetap ada di HTML lewat
        // tombol submit modal konfirmasi (<x-modal name="pengguna-reset"> selalu
        // dirender Livewire ke DOM, hanya disembunyikan lewat Alpine x-show, tidak
        // dihapus dari server-side). Yang perlu dipastikan tidak ada di tab
        // superadmin adalah tombol PEMICU per-baris (wire:click="konfirmasiReset(...)"),
        // yang memang hanya dirender Blade @if untuk baris non-Superadmin.
        $this->assertStringNotContainsString('konfirmasiReset(', $component->html());
    }
}
