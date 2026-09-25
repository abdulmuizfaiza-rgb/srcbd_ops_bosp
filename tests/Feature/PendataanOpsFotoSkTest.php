<?php

namespace Tests\Feature;

use App\Livewire\PendataanOps\Index;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menguji field Photo OPS (auto-crop ke ukuran standar 2x3) dan Upload SK
 * OPS Terbaru (PDF, maks 1 MB, ditolak kalau lebih besar) pada menu
 * Identitas OPS.
 */
class PendataanOpsFotoSkTest extends TestCase
{
    use RefreshDatabase;

    protected function dataLengkap(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'Budi Operator',
            'jk' => 'L',
            'status_kepegawaian' => 'PNS',
            'pendidikan_terakhir' => 'SMA',
            'no_whatsapp' => '081234567890',
        ], $overrides);
    }

    public function test_foto_ops_otomatis_di_crop_ke_ukuran_standar_2x3(): void
    {
        Storage::fake('public');

        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // Upload foto persegi 800x800 (rasio sama sekali tidak 2x3) supaya
        // benar-benar menguji sistem melakukan crop otomatis, bukan cuma
        // menyimpan apa adanya.
        $fotoAsli = UploadedFile::fake()->image('foto.jpg', 800, 800);

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('isi', $sekolah->id);

        foreach ($this->dataLengkap() as $field => $value) {
            $component->set($field, $value);
        }

        $component->set('fotoOpsBaru', $fotoAsli)
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pendataan_ops', ['profil_sekolah_id' => $sekolah->id]);

        $identitas = \App\Models\PendataanOps::where('profil_sekolah_id', $sekolah->id)->firstOrFail();

        $this->assertNotNull($identitas->foto_ops);
        Storage::disk('public')->assertExists($identitas->foto_ops);

        $ukuran = getimagesize(Storage::disk('public')->path($identitas->foto_ops));
        $this->assertSame(236, $ukuran[0], 'Lebar foto hasil crop harus persis 236px.');
        $this->assertSame(354, $ukuran[1], 'Tinggi foto hasil crop harus persis 354px (rasio 2x3).');
    }

    public function test_upload_sk_ops_lebih_dari_1mb_ditolak(): void
    {
        Storage::fake('public');

        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // 1500 KB > 1 MB (1024 KB) - harus ditolak.
        $pdfTerlaluBesar = UploadedFile::fake()->create('sk-ops.pdf', 1500, 'application/pdf');

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('isi', $sekolah->id);

        foreach ($this->dataLengkap() as $field => $value) {
            $component->set($field, $value);
        }

        $component->set('skOpsBaru', $pdfTerlaluBesar)
            ->call('simpan')
            ->assertHasErrors(['skOpsBaru' => 'max']);

        $this->assertDatabaseMissing('pendataan_ops', ['profil_sekolah_id' => $sekolah->id]);
    }

    public function test_upload_sk_ops_dibawah_1mb_berhasil_tersimpan(): void
    {
        Storage::fake('public');

        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $pdfValid = UploadedFile::fake()->create('sk-ops.pdf', 500, 'application/pdf');

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('isi', $sekolah->id);

        foreach ($this->dataLengkap() as $field => $value) {
            $component->set($field, $value);
        }

        $component->set('skOpsBaru', $pdfValid)
            ->call('simpan')
            ->assertHasNoErrors();

        $identitas = \App\Models\PendataanOps::where('profil_sekolah_id', $sekolah->id)->firstOrFail();

        $this->assertNotNull($identitas->sk_ops);
        Storage::disk('public')->assertExists($identitas->sk_ops);
    }

    /**
     * PENTING (2026-09-05, lanjutan): Photo OPS & SK OPS sebelumnya "tidak
     * muncul" karena URL-nya dibangun lewat Storage::disk('public')->url()
     * yang bergantung APP_URL statis - sama persis akar masalah bug
     * background Tampilan yang sudah pernah diperbaiki (lihat
     * TampilanBackgroundController). Perbaikannya: sajikan file lewat route
     * aplikasi sendiri (host+port otomatis ikut request yang sedang
     * berjalan), BUKAN lewat URL storage statis. Test ini mengunci bahwa
     * accessor foto_ops_url/sk_ops_url benar-benar memakai route itu, dan
     * route-nya benar-benar bisa diakses HTTP sungguhan (bukan cuma lewat
     * harness testing Livewire yang tidak melewati middleware/routing).
     */
    public function test_foto_ops_url_dan_sk_ops_url_memakai_route_bukan_storage_url_statis(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('foto-ops/contoh.jpg', 'isi-foto');
        Storage::disk('public')->put('sk-ops/contoh.pdf', 'isi-pdf');

        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $identitas = \App\Models\PendataanOps::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'foto_ops' => 'foto-ops/contoh.jpg',
            'sk_ops' => 'sk-ops/contoh.pdf',
        ]);

        $this->assertStringContainsString('/pendataan-ops/file/foto/', $identitas->foto_ops_url);
        $this->assertStringContainsString('/pendataan-ops/file/sk/', $identitas->sk_ops_url);

        // Pemilik sekolah yang sama BISA mengakses kedua file lewat HTTP
        // sungguhan (bukan Livewire::test, supaya middleware & routing
        // benar-benar dilewati).
        $this->actingAs($adminOps)->get($identitas->foto_ops_url)->assertOk();
        $this->actingAs($adminOps)->get($identitas->sk_ops_url)->assertOk();
    }

    public function test_admin_ops_sekolah_lain_tidak_bisa_akses_file_identitas_ops_orang_lain(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('foto-ops/contoh.jpg', 'isi-foto');

        $sekolah = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminOpsLain = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahLain->id,
        ]);

        // Identitas OPS milik sekolah-nya sendiri harus sudah lengkap dulu,
        // supaya EnsureOnboardingComplete tidak keburu mengalihkan
        // $adminOpsLain ke menu Identitas OPS sebelum sempat mengakses route
        // file yang sedang diuji (bukan itu yang mau diuji di sini - yang
        // mau diuji murni batas otorisasi antar sekolah pada file-nya).
        \App\Models\PendataanOps::factory()->create(['profil_sekolah_id' => $sekolahLain->id]);

        $identitas = \App\Models\PendataanOps::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'foto_ops' => 'foto-ops/contoh.jpg',
        ]);

        $this->actingAs($adminOpsLain)->get($identitas->foto_ops_url)->assertForbidden();
    }

    public function test_superadmin_bisa_akses_file_identitas_ops_sekolah_manapun(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('foto-ops/contoh.jpg', 'isi-foto');

        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $identitas = \App\Models\PendataanOps::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'foto_ops' => 'foto-ops/contoh.jpg',
        ]);

        $this->actingAs($superadmin)->get($identitas->foto_ops_url)->assertOk();
    }
}
