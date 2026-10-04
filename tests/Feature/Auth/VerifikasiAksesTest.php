<?php

namespace Tests\Feature\Auth;

use App\Mail\TokenVerifikasiAkses;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Test untuk gerbang verifikasi akses email + token OTP 6 digit
 * (resources/views/livewire/pages/auth/verifikasi-akses.blade.php) -
 * ditambahkan lewat Audit Aplikasi 2026-10-04, yang menemukan fitur ini
 * BELUM punya test otomatis sama sekali walau sudah berjalan di produksi
 * sejak 2026-09-26. Lihat docblock komponen Volt tsb untuk keputusan
 * bisnis lengkap (AskUserQuestion 2026-09-26).
 */
class VerifikasiAksesTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_verifikasi_akses_bisa_dibuka(): void
    {
        $this->get('/verifikasi-akses')
            ->assertOk()
            ->assertSeeVolt('pages.auth.verifikasi-akses');
    }

    public function test_email_tidak_terdaftar_diarahkan_ke_registrasi(): void
    {
        Volt::test('pages.auth.verifikasi-akses')
            ->set('email', 'tidak-ada@contoh.sch.id')
            ->call('lanjut')
            ->assertRedirect(route('register', absolute: false));
    }

    public function test_akun_belum_disetujui_tidak_dikirim_token(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'belum-disetujui@contoh.sch.id',
            'is_approved' => false,
        ]);

        Volt::test('pages.auth.verifikasi-akses')
            ->set('email', $user->email)
            ->call('lanjut')
            ->assertHasErrors('email');

        Mail::assertNothingSent();
    }

    public function test_email_terdaftar_dan_disetujui_mengirim_token_dan_pindah_ke_step_token(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'operator@contoh.sch.id',
            'is_approved' => true,
        ]);

        Volt::test('pages.auth.verifikasi-akses')
            ->set('email', $user->email)
            ->call('lanjut')
            ->assertHasNoErrors()
            ->assertSet('step', 'token')
            ->assertSet('userId', $user->id);

        Mail::assertSent(TokenVerifikasiAkses::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->user->is($user);
        });
    }

    public function test_token_benar_berhasil_set_sesi_gerbang_dan_redirect_ke_login(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'operator2@contoh.sch.id',
            'is_approved' => true,
        ]);

        $component = Volt::test('pages.auth.verifikasi-akses')
            ->set('email', $user->email)
            ->call('lanjut');

        // Timpa Cache dengan token yang KITA ketahui (meniru persis apa
        // yang buatDanKirimToken() lakukan) - supaya tidak perlu
        // mengandalkan isi email asli yang sudah di-fake Mail::fake().
        $tokenDiketahui = '123456';
        Cache::put("otp-login:token:{$user->id}", [
            'hash' => Hash::make($tokenDiketahui),
            'expires_at' => now()->addMinutes(15)->timestamp,
        ], now()->addMinutes(15));

        $component
            ->set('token', $tokenDiketahui)
            ->call('verifikasi')
            ->assertHasNoErrors()
            ->assertRedirect(route('login', absolute: false));

        $this->assertEquals($user->id, session('gerbang_akses_login_user_id'));
        $this->assertNull(Cache::get("otp-login:token:{$user->id}"), 'Token harus dihapus dari Cache setelah berhasil dipakai (sekali pakai).');
    }

    public function test_token_salah_menampilkan_error_dan_tidak_meloloskan_gerbang(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'operator3@contoh.sch.id',
            'is_approved' => true,
        ]);

        Volt::test('pages.auth.verifikasi-akses')
            ->set('email', $user->email)
            ->call('lanjut')
            ->set('token', '000000')
            ->call('verifikasi')
            ->assertHasErrors('token');

        $this->assertNull(session('gerbang_akses_login_user_id'));
    }

    public function test_token_salah_5_kali_memblokir_percobaan_berikutnya(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'operator4@contoh.sch.id',
            'is_approved' => true,
        ]);

        $component = Volt::test('pages.auth.verifikasi-akses')
            ->set('email', $user->email)
            ->call('lanjut');

        // 5x percobaan token salah berturut-turut (batas MAKS_PERCOBAAN_TOKEN).
        for ($i = 0; $i < 5; $i++) {
            $component->set('token', '000000')->call('verifikasi');
        }

        // Percobaan ke-6 - bahkan dengan token yang BENAR sekalipun - harus
        // tetap ditolak karena sudah melewati batas percobaan.
        $tokenDiketahui = '654321';
        Cache::put("otp-login:token:{$user->id}", [
            'hash' => Hash::make($tokenDiketahui),
            'expires_at' => now()->addMinutes(15)->timestamp,
        ], now()->addMinutes(15));

        $component
            ->set('token', $tokenDiketahui)
            ->call('verifikasi')
            ->assertHasErrors('token')
            ->assertSee('Terlalu banyak percobaan', false);

        $this->assertNull(session('gerbang_akses_login_user_id'));
    }

    public function test_token_kadaluarsa_ditolak(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'operator5@contoh.sch.id',
            'is_approved' => true,
        ]);

        $component = Volt::test('pages.auth.verifikasi-akses')
            ->set('email', $user->email)
            ->call('lanjut');

        // Simulasikan token yang SUDAH lewat masa berlakunya (expires_at di
        // masa lalu) - skenario orang yang menunda verifikasi lebih dari 15
        // menit setelah token dikirim.
        $tokenDiketahui = '111222';
        Cache::put("otp-login:token:{$user->id}", [
            'hash' => Hash::make($tokenDiketahui),
            'expires_at' => now()->subMinute()->timestamp,
        ], now()->addMinute());

        $component
            ->set('token', $tokenDiketahui)
            ->call('verifikasi')
            ->assertHasErrors('token')
            ->assertSee('kadaluarsa', false);
    }

    public function test_kirim_ulang_sebelum_jeda_selesai_ditolak(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'operator6@contoh.sch.id',
            'is_approved' => true,
        ]);

        Volt::test('pages.auth.verifikasi-akses')
            ->set('email', $user->email)
            ->call('lanjut')
            ->call('kirimUlang')
            ->assertHasErrors('token');

        // Cuma 1 email yang seharusnya terkirim (dari lanjut()) - permintaan
        // kirim ulang yang kena jeda TIDAK boleh ikut mengirim email baru.
        Mail::assertSent(TokenVerifikasiAkses::class, 1);
    }

    public function test_kirim_ulang_setelah_jeda_selesai_berhasil_mengirim_token_baru(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'operator7@contoh.sch.id',
            'is_approved' => true,
        ]);

        $component = Volt::test('pages.auth.verifikasi-akses')
            ->set('email', $user->email)
            ->call('lanjut');

        // Lewati jeda 60 detik (JEDA_KIRIM_ULANG_DETIK) dengan menghapus
        // langsung cache resend-nya - setara dengan menunggu jeda selesai.
        Cache::forget("otp-login:resend:{$user->id}");

        $component
            ->call('kirimUlang')
            ->assertHasNoErrors();

        Mail::assertSent(TokenVerifikasiAkses::class, 2);
    }
}
