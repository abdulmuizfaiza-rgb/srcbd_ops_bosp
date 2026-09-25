<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\Index;
use App\Models\Lampiran2a;
use App\Models\Lampiran2b;
use App\Models\Lampiran2c;
use App\Models\PenerimaanHonorPtk;
use App\Models\ProfilSekolah;
use App\Models\User;
use App\Models\VervalRealisasiBosp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menguji dashboard visualisasi data - konten & data yang ditampilkan
 * harus sesuai peran (Superadmin/Admin OPS/Admin BOSP).
 *
 * ROUND KETIGA BELAS (2026-09-24): dashboard Admin OPS & Admin BOSP
 * diganti TOTAL (lihat docblock App\Livewire\Dashboard\Index) - keduanya
 * sekarang menampilkan rekap SEMUA sekolah (Global), BUKAN cuma sekolah
 * milik akun yang login seperti sebelumnya. Test lama yang menguji
 * perilaku "hanya sekolah sendiri" DIGANTI dengan test yang menguji
 * cakupan global & 3 definisi bisnis baru yang dikonfirmasi lewat
 * AskUserQuestion: "selesai OPS" (SEMUA Lampiran 2a+2b+2c), "registrasi"
 * (is_approved = true), dan "validasi BOSP" (status Sesuai saja).
 *
 * ROUND KEEMPAT BELAS (2026-09-24): dashboard Superadmin MENDAPAT
 * TAMBAHAN widget (widget lama TIDAK diubah, test lama di atas TETAP
 * berlaku apa adanya): rekap+daftar Registrasi Admin OPS/BOSP, & 2
 * daftar Validasi (BOSP - status Sesuai; OPS - pakai definisi "selesai
 * OPS" yang sudah ada, BUKAN fitur validasi baru - lihat docblock
 * dataSuperadmin()).
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function profilLengkap(array $overrides = []): array
    {
        return array_merge([
            'nama_kepala_sekolah' => 'Kepsek A',
            'nip_kepala_sekolah' => '111',
            'no_whatsapp_kepala_sekolah' => '081200000001',
            'status_kepegawaian_kepsek' => 'PNS',
            'nama_pengawas' => 'Pengawas A',
            'nip_pengawas' => '222',
            'nama_bendahara' => 'Bendahara A',
            'nip_bendahara' => '333',
            'status_kepegawaian_bendahara' => 'PNS',
            'alamat_sekolah' => 'Jl. Contoh No. 1',
        ], $overrides);
    }

    public function test_superadmin_melihat_dashboard_rekap_seluruh_sekolah(): void
    {
        ProfilSekolah::factory()->create(array_merge(['status' => 'negeri', 'kecamatan' => 'Cibadak'], $this->profilLengkap()));
        $sekolahBelum = ProfilSekolah::factory()->create(['status' => 'swasta', 'kecamatan' => 'Nagrak']);

        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->assertSee('Total Sekolah')
            ->assertSee('2') // total sekolah
            ->assertSee('Kelengkapan Profil Sekolah')
            ->assertSee('Jumlah Data Lampiran per Triwulan')
            ->assertSee('Sebaran Sekolah per Kecamatan')
            ->assertSee('Cibadak')
            ->assertSee('Nagrak');
    }

    public function test_halaman_dashboard_bisa_diakses_lewat_route(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $this->actingAs($superadmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeLivewire(Index::class);
    }

    public function test_admin_ops_melihat_dashboard_rekap_semua_sekolah_bukan_hanya_sekolahnya_sendiri(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak 1', 'status' => 'negeri']);
        $sekolahLain = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Lain', 'status' => 'swasta']);

        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->assertSee('Dashboard Pendataan OPS')
            ->assertSee('Total Sekolah')
            ->assertSee('SR Cibadak 1')
            ->assertSee('SR Lain')
            ->assertViewHas('totalSekolah', 2)
            ->assertViewHas('totalNegeri', 1)
            ->assertViewHas('totalSwasta', 1);
    }

    public function test_admin_ops_sekolah_dianggap_selesai_hanya_jika_ketiga_lampiran_lengkap(): void
    {
        $tahun = now()->year;

        $sekolahLengkap = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Lengkap 2a2b2c']);
        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolahLengkap->id, 'tahun' => $tahun, 'triwulan' => 1]);
        Lampiran2b::factory()->create(['profil_sekolah_id' => $sekolahLengkap->id, 'tahun' => $tahun, 'triwulan' => 1]);
        Lampiran2c::factory()->create(['profil_sekolah_id' => $sekolahLengkap->id, 'tahun' => $tahun, 'triwulan' => 1]);

        // Sengaja HANYA 2a & 2b, Lampiran 2c TIDAK diisi - harus TETAP
        // dihitung "belum" walau 2 dari 3 lampiran sudah ada (jawaban
        // AskUserQuestion "Selesai OPS" -> harus SEMUA 3 lampiran).
        $sekolahKurangSatu = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Kurang Lampiran 2c']);
        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolahKurangSatu->id, 'tahun' => $tahun, 'triwulan' => 1]);
        Lampiran2b::factory()->create(['profil_sekolah_id' => $sekolahKurangSatu->id, 'tahun' => $tahun, 'triwulan' => 1]);

        $sekolahBelumSamaSekali = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Belum Isi Apapun']);

        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahLengkap->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class);

        $sudah = $component->viewData('sekolahSudahAktif');
        $belum = $component->viewData('sekolahBelumAktif');

        $this->assertTrue($sudah->contains('id', $sekolahLengkap->id));
        $this->assertFalse($sudah->contains('id', $sekolahKurangSatu->id));
        $this->assertFalse($sudah->contains('id', $sekolahBelumSamaSekali->id));

        $this->assertTrue($belum->contains('id', $sekolahKurangSatu->id));
        $this->assertTrue($belum->contains('id', $sekolahBelumSamaSekali->id));
        $this->assertFalse($belum->contains('id', $sekolahLengkap->id));
    }

    public function test_admin_ops_registrasi_hanya_hitung_akun_yang_sudah_disetujui(): void
    {
        $sekolahDisetujui = ProfilSekolah::factory()->create(['status' => 'negeri']);
        User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahDisetujui->id,
            'is_approved' => true,
        ]);

        // Akun ADA tapi belum disetujui Superadmin - harus TETAP dihitung
        // "belum registrasi" (jawaban AskUserQuestion "Registrasi Akun").
        $sekolahPending = ProfilSekolah::factory()->create(['status' => 'negeri']);
        User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahPending->id,
            'is_approved' => false,
        ]);

        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahDisetujui->id,
            'is_approved' => true,
        ]);

        $registrasiOps = Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->viewData('registrasiOps');

        // 3 sekolah Negeri total (disetujui, pending, + akun adminOps
        // sendiri berbagi sekolah yang sama dgn $sekolahDisetujui), tapi
        // HANYA yang is_approved=true yang dihitung "sudah".
        $this->assertSame(1, $registrasiOps['negeri']['sudah']);
        $this->assertSame(2, $registrasiOps['negeri']['total']);
    }

    public function test_admin_bosp_melihat_dashboard_rekap_semua_sekolah(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR BOSP Saya']);
        $sekolahLain = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR BOSP Lain']);

        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->assertSee('Dashboard Pendataan BOSP')
            ->assertSee('Total Sekolah')
            ->assertSee('SR BOSP Saya')
            ->assertSee('SR BOSP Lain')
            ->assertViewHas('totalSekolah', 2);
    }

    public function test_admin_bosp_selesai_pakai_definisi_data_di_salah_satu_menu(): void
    {
        $tahun = now()->year;

        $sekolahSudah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR BOSP Sudah Isi']);
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolahSudah->id, 'tahun' => $tahun, 'triwulan' => 1,
        ]);

        $sekolahBelum = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR BOSP Belum Isi']);

        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahSudah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        $sudah = $component->viewData('sekolahSudahAktif');
        $belum = $component->viewData('sekolahBelumAktif');

        $this->assertTrue($sudah->contains('id', $sekolahSudah->id));
        $this->assertFalse($sudah->contains('id', $sekolahBelum->id));
        $this->assertTrue($belum->contains('id', $sekolahBelum->id));
    }

    public function test_admin_bosp_validasi_hanya_status_sesuai_dihitung_sudah(): void
    {
        $tahun = now()->year;

        $sekolahSesuai = ProfilSekolah::factory()->create(['status' => 'negeri']);
        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolahSesuai->id,
            'tahun' => $tahun,
            'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => null,
            'diverval_pada' => now(),
        ]);

        // Sudah diverval TAPI statusnya "Belum Sesuai" - harus TETAP
        // dihitung "belum validasi" (jawaban AskUserQuestion "Definisi
        // Validasi": hanya status Sesuai yang dihitung sudah).
        $sekolahBelumSesuai = ProfilSekolah::factory()->create(['status' => 'negeri']);
        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolahBelumSesuai->id,
            'tahun' => $tahun,
            'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_BELUM_SESUAI,
            'diverval_oleh' => null,
            'diverval_pada' => now(),
        ]);

        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahSesuai->id,
        ]);

        $validasiBosp = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->viewData('validasiBosp');

        $this->assertSame(1, $validasiBosp['negeri']['sudah']);
        $this->assertSame(2, $validasiBosp['negeri']['total']);
    }

    public function test_admin_bosp_registrasi_hanya_hitung_akun_yang_sudah_disetujui(): void
    {
        $sekolahDisetujui = ProfilSekolah::factory()->create(['status' => 'swasta']);
        User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahDisetujui->id,
            'is_approved' => true,
        ]);

        $sekolahPending = ProfilSekolah::factory()->create(['status' => 'swasta']);
        User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahPending->id,
            'is_approved' => false,
        ]);

        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahDisetujui->id,
            'is_approved' => true,
        ]);

        $registrasiBosp = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->viewData('registrasiBosp');

        $this->assertSame(1, $registrasiBosp['swasta']['sudah']);
        $this->assertSame(2, $registrasiBosp['swasta']['total']);
    }

    public function test_superadmin_melihat_registrasi_admin_ops_dan_bosp_sudah_belum_dgn_warna(): void
    {
        $sekolahOpsRegistrasi = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Admin OPS Terdaftar']);
        User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahOpsRegistrasi->id,
            'is_approved' => true,
        ]);

        // Sekolah lain SAMA SEKALI tidak punya akun Admin OPS/BOSP -
        // harus dihitung "belum registrasi" utk KEDUANYA.
        $sekolahBelumAdaAkun = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Belum Ada Akun']);

        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->assertSee('Registrasi Admin OPS & Admin BOSP per Sekolah', false)
            ->assertSee('SR Admin OPS Terdaftar')
            ->assertSee('SR Belum Ada Akun');

        $this->assertSame(1, $component->viewData('registrasiOpsSudah'));
        $this->assertSame(1, $component->viewData('registrasiOpsBelum'));
        $this->assertSame(0, $component->viewData('registrasiBospSudah'));
        $this->assertSame(2, $component->viewData('registrasiBospBelum'));

        $daftar = $component->viewData('halamanRegistrasi');
        $barisTerdaftar = $daftar->firstWhere('sekolah.id', $sekolahOpsRegistrasi->id);
        $barisBelum = $daftar->firstWhere('sekolah.id', $sekolahBelumAdaAkun->id);

        $this->assertTrue($barisTerdaftar['opsSudah']);
        $this->assertFalse($barisTerdaftar['bospSudah']);
        $this->assertFalse($barisBelum['opsSudah']);
        $this->assertFalse($barisBelum['bospSudah']);
    }

    public function test_superadmin_melihat_validasi_bosp_hanya_status_sesuai_dihitung_sudah(): void
    {
        $tahun = now()->year;

        $sekolahSesuai = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR BOSP Sesuai']);
        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolahSesuai->id,
            'tahun' => $tahun,
            'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => null,
            'diverval_pada' => now(),
        ]);

        // Sudah diverval TAPI "Belum Sesuai" - HARUS TETAP dihitung
        // "belum validasi" (definisi SAMA dgn dashboard Admin BOSP).
        $sekolahBelumSesuai = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR BOSP Belum Sesuai']);
        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolahBelumSesuai->id,
            'tahun' => $tahun,
            'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_BELUM_SESUAI,
            'diverval_oleh' => null,
            'diverval_pada' => now(),
        ]);

        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->assertSee('Validasi Pendataan BOSP')
            ->assertSee('SR BOSP Sesuai')
            ->assertSee('SR BOSP Belum Sesuai');

        $this->assertSame(1, $component->viewData('validasiBospSudah'));
        $this->assertSame(1, $component->viewData('validasiBospBelum'));

        $daftar = $component->viewData('halamanValidasiBosp');
        $this->assertTrue($daftar->firstWhere('sekolah.id', $sekolahSesuai->id)['sudah']);
        $this->assertFalse($daftar->firstWhere('sekolah.id', $sekolahBelumSesuai->id)['sudah']);
    }

    public function test_superadmin_melihat_validasi_ops_pakai_definisi_selesai_lampiran_lengkap(): void
    {
        $tahun = now()->year;

        $sekolahLengkap = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR OPS Lengkap Semua']);
        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolahLengkap->id, 'tahun' => $tahun, 'triwulan' => 1]);
        Lampiran2b::factory()->create(['profil_sekolah_id' => $sekolahLengkap->id, 'tahun' => $tahun, 'triwulan' => 1]);
        Lampiran2c::factory()->create(['profil_sekolah_id' => $sekolahLengkap->id, 'tahun' => $tahun, 'triwulan' => 1]);

        // Baru 2 dari 3 Lampiran - dashboard Superadmin memakai definisi
        // "selesai OPS" yang SAMA (bukan fitur validasi terpisah), jadi
        // HARUS TETAP dihitung "belum".
        $sekolahKurangSatu = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR OPS Kurang Satu Lampiran']);
        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolahKurangSatu->id, 'tahun' => $tahun, 'triwulan' => 1]);
        Lampiran2b::factory()->create(['profil_sekolah_id' => $sekolahKurangSatu->id, 'tahun' => $tahun, 'triwulan' => 1]);

        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->assertSee('Validasi Pendataan OPS')
            ->assertSee('SR OPS Lengkap Semua')
            ->assertSee('SR OPS Kurang Satu Lampiran');

        $this->assertSame(1, $component->viewData('validasiOpsSudah'));
        $this->assertSame(1, $component->viewData('validasiOpsBelum'));

        $daftar = $component->viewData('halamanValidasiOps');
        $this->assertTrue($daftar->firstWhere('sekolah.id', $sekolahLengkap->id)['sudah']);
        $this->assertFalse($daftar->firstWhere('sekolah.id', $sekolahKurangSatu->id)['sudah']);
    }

    public function test_superadmin_bisa_ganti_triwulan_utk_widget_validasi_baru(): void
    {
        $tahun = now()->year;
        $sekolah = ProfilSekolah::factory()->create();

        // Data OPS lengkap HANYA utk Triwulan 2.
        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 2]);
        Lampiran2b::factory()->create(['profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 2]);
        Lampiran2c::factory()->create(['profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 2]);

        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        // Default TW-1 - belum selesai (data ada di TW2).
        $this->assertSame(0, $component->viewData('validasiOpsSudah'));

        // Pindah ke TW-2 - baru dihitung "sudah".
        $component->set('triwulan', 2);
        $this->assertSame(1, $component->viewData('validasiOpsSudah'));
    }

    /**
     * ROUND KELIMA BELAS (2026-09-24): permintaan user "dibuat dalam
     * bentuk Sistem Paginasi ... supaya tidak terlalu panjang ke
     * bawah" - 3 daftar sekolah (Registrasi, Validasi BOSP, Validasi
     * OPS) di dashboard Superadmin sekarang dipaginasi 10 per halaman,
     * masing-masing lewat pageName SENDIRI (`halamanRegistrasi`,
     * `halamanValidasiBosp`, `halamanValidasiOps`) supaya bisa
     * di-pindah halaman SECARA INDEPENDEN walau ketiganya tampil
     * bersamaan di 1 halaman dashboard.
     */
    public function test_superadmin_daftar_registrasi_dan_validasi_dipaginasi_10_per_halaman_independen(): void
    {
        ProfilSekolah::factory()->count(15)->create();

        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $halamanRegistrasi1 = $component->viewData('halamanRegistrasi');
        $this->assertCount(10, $halamanRegistrasi1->items());
        $this->assertSame(15, $halamanRegistrasi1->total());

        $halamanValidasiBosp1 = $component->viewData('halamanValidasiBosp');
        $this->assertCount(10, $halamanValidasiBosp1->items());

        // Pindah HANYA paginator "Registrasi" ke halaman 2 - paginator
        // "Validasi BOSP" & "Validasi OPS" HARUS TETAP di halaman 1
        // (pageName independen, tidak saling memengaruhi).
        $component->call('gotoPage', 2, 'halamanRegistrasi');

        $halamanRegistrasi2 = $component->viewData('halamanRegistrasi');
        $this->assertCount(5, $halamanRegistrasi2->items());
        $this->assertSame(2, $halamanRegistrasi2->currentPage());

        $halamanValidasiBospTetap = $component->viewData('halamanValidasiBosp');
        $this->assertSame(1, $halamanValidasiBospTetap->currentPage());

        $halamanValidasiOpsTetap = $component->viewData('halamanValidasiOps');
        $this->assertSame(1, $halamanValidasiOpsTetap->currentPage());
    }
}
