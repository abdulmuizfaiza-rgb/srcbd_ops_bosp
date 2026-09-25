<?php

namespace Tests\Feature;

use App\Livewire\Beranda\Index;
use App\Models\Lampiran2a;
use App\Models\Lampiran2b;
use App\Models\Lampiran2c;
use App\Models\PenerimaanHonorPtk;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menguji landing page publik (route "/", nama route "beranda") - ROUND
 * KEENAM BELAS (2026-09-24, bagian 2), DISESUAIKAN round KETUJUH BELAS
 * (2026-09-24, permintaan user "triwulannya default ke triwulan 1 ...
 * tambahkan tombol Tahun dan pilihan triwulan"). Lihat docblock
 * App\Livewire\Beranda\Index utk detail keputusan bisnis yang diuji di
 * sini.
 */
class BerandaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengunjung_belum_login_bisa_melihat_halaman_beranda(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSeeLivewire(Index::class);
    }

    public function test_route_akar_bernama_beranda(): void
    {
        $this->assertSame('/', route('beranda', absolute: false));
    }

    public function test_user_yang_sudah_login_diarahkan_ke_dashboard_bukan_beranda(): void
    {
        // Perilaku LAMA `Route::redirect('/', '/dashboard')` HARUS tetap
        // sama utk user yang sudah login - hanya pengunjung belum login
        // yang melihat halaman baru ini (lihat docblock kelas Index).
        $user = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_tombol_masuk_mengarah_ke_halaman_login(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('login'), false);
    }

    public function test_sekolah_selesai_pendataan_ops_pakai_definisi_lampiran_2a_2b_2c_lengkap(): void
    {
        $tahun = now()->year;
        $triwulan = 1; // default landing page sejak round ketujuh belas

        $sekolahLengkap = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR OPS Lengkap']);
        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolahLengkap->id, 'tahun' => $tahun, 'triwulan' => $triwulan]);
        Lampiran2b::factory()->create(['profil_sekolah_id' => $sekolahLengkap->id, 'tahun' => $tahun, 'triwulan' => $triwulan]);
        Lampiran2c::factory()->create(['profil_sekolah_id' => $sekolahLengkap->id, 'tahun' => $tahun, 'triwulan' => $triwulan]);

        $sekolahKurangSatu = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR OPS Kurang Satu']);
        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolahKurangSatu->id, 'tahun' => $tahun, 'triwulan' => $triwulan]);
        Lampiran2b::factory()->create(['profil_sekolah_id' => $sekolahKurangSatu->id, 'tahun' => $tahun, 'triwulan' => $triwulan]);
        // Lampiran 2c SENGAJA tidak diisi - harus dihitung "belum".

        $component = Livewire::test(Index::class);

        $this->assertSame(1, $component->viewData('opsSudah'));
        $this->assertSame(1, $component->viewData('opsBelum'));

        $halamanOps = $component->viewData('halamanOps');
        $baris = collect($halamanOps->items())->keyBy(fn ($b) => $b['sekolah']->id);

        $this->assertTrue($baris[$sekolahLengkap->id]['sudah']);
        $this->assertFalse($baris[$sekolahKurangSatu->id]['sudah']);
    }

    public function test_sekolah_selesai_pendataan_bosp_pakai_definisi_ada_data_di_salah_satu_menu(): void
    {
        $tahun = now()->year;
        $triwulan = 1; // default landing page sejak round ketujuh belas

        $sekolahSudah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR BOSP Sudah Isi']);
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolahSudah->id, 'tahun' => $tahun, 'triwulan' => $triwulan,
        ]);

        $sekolahBelum = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR BOSP Belum Isi']);

        $component = Livewire::test(Index::class);

        $this->assertSame(1, $component->viewData('bospSudah'));
        $this->assertSame(1, $component->viewData('bospBelum'));

        $halamanBosp = $component->viewData('halamanBosp');
        $baris = collect($halamanBosp->items())->keyBy(fn ($b) => $b['sekolah']->id);

        $this->assertTrue($baris[$sekolahSudah->id]['sudah']);
        $this->assertFalse($baris[$sekolahBelum->id]['sudah']);
    }

    public function test_default_tahun_berjalan_dan_triwulan_1(): void
    {
        // Round ketujuh belas (permintaan user eksplisit): default
        // SELALU Triwulan 1, BUKAN lagi otomatis dari bulan berjalan -
        // sama seperti konvensi default App\Livewire\Dashboard\Index &
        // App\Livewire\TimelinePekerjaan\Index.
        $component = Livewire::test(Index::class);

        $this->assertSame(now()->year, $component->viewData('tahun'));
        $this->assertSame(1, $component->viewData('triwulan'));
    }

    public function test_pengunjung_bisa_mengubah_tahun_dan_triwulan_lewat_selector(): void
    {
        // Round ketujuh belas (permintaan user eksplisit "tambahkan
        // tombol Tahun dan pilihan triwulan") - selector men-set
        // properti publik $tahun/$triwulan langsung (wire:model.live &
        // wire:click $set, lihat partial selector-triwulan), lalu
        // render() menghitung ulang data utk Tahun/Triwulan yang baru.
        $tahunLain = now()->year - 1;

        $sekolah = ProfilSekolah::factory()->create();
        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolah->id, 'tahun' => $tahunLain, 'triwulan' => 3]);
        Lampiran2b::factory()->create(['profil_sekolah_id' => $sekolah->id, 'tahun' => $tahunLain, 'triwulan' => 3]);
        Lampiran2c::factory()->create(['profil_sekolah_id' => $sekolah->id, 'tahun' => $tahunLain, 'triwulan' => 3]);

        $component = Livewire::test(Index::class);

        // Default (Tahun berjalan, Triwulan 1) - sekolah ini belum
        // dihitung "sudah" krn datanya utk tahun/triwulan lain.
        $this->assertSame(0, $component->viewData('opsSudah'));

        $component->set('tahun', $tahunLain)->set('triwulan', 3);

        $this->assertSame($tahunLain, $component->viewData('tahun'));
        $this->assertSame(3, $component->viewData('triwulan'));
        $this->assertSame(1, $component->viewData('opsSudah'));
    }

    public function test_daftar_ops_dan_bosp_dipaginasi_10_per_halaman_independen(): void
    {
        ProfilSekolah::factory()->count(15)->create();

        $component = Livewire::test(Index::class);

        $this->assertCount(10, $component->viewData('halamanOps')->items());
        $this->assertCount(10, $component->viewData('halamanBosp')->items());
        $this->assertSame(15, $component->viewData('halamanOps')->total());
        $this->assertSame(15, $component->viewData('halamanBosp')->total());

        // Pindah halaman daftar OPS TIDAK mengubah halaman aktif daftar
        // BOSP (pageName independen: 'halamanOps' vs 'halamanBosp') -
        // sama seperti pola independensi 3 paginator dashboard Superadmin
        // (round kelima belas).
        $component->call('gotoPage', 2, 'halamanOps');

        $this->assertSame(2, $component->viewData('halamanOps')->currentPage());
        $this->assertSame(1, $component->viewData('halamanBosp')->currentPage());
    }
}
