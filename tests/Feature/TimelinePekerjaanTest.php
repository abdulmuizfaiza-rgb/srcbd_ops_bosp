<?php

namespace Tests\Feature;

use App\Livewire\TimelinePekerjaan\Index;
use App\Models\DeadlinePekerjaan;
use App\Models\PendataanBosp;
use App\Models\PendataanOps;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menguji fitur "Timeline Pekerjaan" (round kedelapan, bagian B - fondasi;
 * round kesembilan, bagian B - dimensi triwulan) - permintaan user
 * 2026-09-23: "saya juga ingin dibuatkan menu Timeline pekerjaan baik
 * untuk pendataan OPS dan pendataan BOSP supaya lebih disiplin dari sisi
 * deadline pekerjaannya. dimana superadmin bisa mengatur nya untuk
 * deadline pendataan OPS dan deadline pendataan BOSP." + "pada menu
 * Timeline Pekerjaan tambahkan pilihan triwulan, jadi pengaturan timeline
 * berdasarkan triwulan. dan ketika superadmin pilih tanggal Deadline ada
 * pilihan untuk semua Tahap Kerja (Menu) langsung apabila tanggal
 * deadline nya sama."
 *
 * Scoping lengkap lewat AskUserQuestion (lihat migration
 * `create_deadline_pekerjaan_table` & `add_triwulan_to_deadline_pekerjaan_table`
 * + App\Models\DeadlinePekerjaan untuk detail jawaban): 16 tahap kerja (3
 * OPS + 13 BOSP), deadline SAMA untuk semua sekolah, per tahun+triwulan
 * (SEMUA 16 tahap kerja ikut dapat dimensi triwulan, bukan hanya yang
 * "alami" per triwulan), Superadmin kelola, Admin OPS & Admin BOSP lihat
 * saja. PENTING: round ini BARU fondasi (atur + lihat deadline) - efek
 * "memblokir input" ke 16 menu terkait BELUM diuji/diterapkan di sini
 * (lihat App\Livewire\Concerns\MenolakEditJikaLewatDeadline).
 *
 * SENGAJA memakai triwulan TETAP (bukan mengikuti bulan "now" seperti
 * default mount()) di sebagian besar test di bawah, supaya test tidak
 * rapuh terhadap tanggal saat test dijalankan - default mount() sendiri
 * diuji terpisah lewat test_triwulan_default_mengikuti_bulan_berjalan().
 */
class TimelinePekerjaanTest extends TestCase
{
    use RefreshDatabase;

    private const TRIWULAN_UJI = 2;

    private function superadmin(): User
    {
        return User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
    }

    private function adminBosp(): User
    {
        $sekolah = ProfilSekolah::factory()->create();

        return User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    private function adminOps(): User
    {
        $sekolah = ProfilSekolah::factory()->create();

        return User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    // ------------------------------------------------------------------
    // Model: registry & helper deadline
    // ------------------------------------------------------------------

    public function test_daftar_tahap_kerja_berisi_16_menu_3_ops_13_bosp(): void
    {
        $daftar = DeadlinePekerjaan::daftarTahapKerja();

        $this->assertCount(16, $daftar);
        $this->assertCount(3, array_filter($daftar, fn ($info) => $info['kategori'] === 'ops'));
        $this->assertCount(13, array_filter($daftar, fn ($info) => $info['kategori'] === 'bosp'));

        // Menu identitas/onboarding & Unduhan SENGAJA tidak ada di daftar.
        $this->assertArrayNotHasKey('pendataan-ops.index', $daftar);
        $this->assertArrayNotHasKey('pendataan-bosp.index', $daftar);
        $this->assertArrayNotHasKey('pendataan-ops.unduhan', $daftar);
    }

    public function test_setiap_kunci_menu_di_registry_adalah_nama_route_yang_valid(): void
    {
        foreach (array_keys(DeadlinePekerjaan::daftarTahapKerja()) as $kunciMenu) {
            $this->assertTrue(Route::has($kunciMenu), "Route '{$kunciMenu}' tidak ditemukan.");
        }
    }

    public function test_belum_ada_baris_deadline_dianggap_tidak_lewat(): void
    {
        $this->assertFalse(DeadlinePekerjaan::sudahLewat('pendataan-bosp.rekap-rkas', 2026, self::TRIWULAN_UJI));
    }

    public function test_deadline_hari_ini_belum_dianggap_lewat(): void
    {
        DeadlinePekerjaan::aturDeadline('pendataan-bosp.rekap-rkas', 2026, self::TRIWULAN_UJI, now()->toDateString(), null);

        $this->assertFalse(DeadlinePekerjaan::sudahLewat('pendataan-bosp.rekap-rkas', 2026, self::TRIWULAN_UJI));
    }

    public function test_deadline_kemarin_dianggap_sudah_lewat(): void
    {
        DeadlinePekerjaan::aturDeadline('pendataan-bosp.rekap-rkas', 2026, self::TRIWULAN_UJI, now()->subDay()->toDateString(), null);

        $this->assertTrue(DeadlinePekerjaan::sudahLewat('pendataan-bosp.rekap-rkas', 2026, self::TRIWULAN_UJI));
    }

    public function test_deadline_besok_belum_dianggap_lewat(): void
    {
        DeadlinePekerjaan::aturDeadline('pendataan-bosp.rekap-rkas', 2026, self::TRIWULAN_UJI, now()->addDay()->toDateString(), null);

        $this->assertFalse(DeadlinePekerjaan::sudahLewat('pendataan-bosp.rekap-rkas', 2026, self::TRIWULAN_UJI));
    }

    public function test_hapus_deadline_membuat_sudah_lewat_kembali_false(): void
    {
        DeadlinePekerjaan::aturDeadline('pendataan-bosp.rekap-rkas', 2026, self::TRIWULAN_UJI, now()->subDay()->toDateString(), null);
        $this->assertTrue(DeadlinePekerjaan::sudahLewat('pendataan-bosp.rekap-rkas', 2026, self::TRIWULAN_UJI));

        DeadlinePekerjaan::hapusDeadline('pendataan-bosp.rekap-rkas', 2026, self::TRIWULAN_UJI);

        $this->assertFalse(DeadlinePekerjaan::sudahLewat('pendataan-bosp.rekap-rkas', 2026, self::TRIWULAN_UJI));
    }

    public function test_deadline_terikat_ke_tahun_tertentu_tidak_ikut_ke_tahun_lain(): void
    {
        DeadlinePekerjaan::aturDeadline('pendataan-bosp.rekap-rkas', 2025, self::TRIWULAN_UJI, now()->subDay()->toDateString(), null);

        $this->assertTrue(DeadlinePekerjaan::sudahLewat('pendataan-bosp.rekap-rkas', 2025, self::TRIWULAN_UJI));
        $this->assertFalse(DeadlinePekerjaan::sudahLewat('pendataan-bosp.rekap-rkas', 2026, self::TRIWULAN_UJI));
    }

    public function test_deadline_terikat_ke_triwulan_tertentu_tidak_ikut_ke_triwulan_lain(): void
    {
        DeadlinePekerjaan::aturDeadline('pendataan-bosp.rekap-rkas', 2026, 1, now()->subDay()->toDateString(), null);

        $this->assertTrue(DeadlinePekerjaan::sudahLewat('pendataan-bosp.rekap-rkas', 2026, 1));
        $this->assertFalse(DeadlinePekerjaan::sudahLewat('pendataan-bosp.rekap-rkas', 2026, 2));
        $this->assertFalse(DeadlinePekerjaan::sudahLewat('pendataan-bosp.rekap-rkas', 2026, 3));
        $this->assertFalse(DeadlinePekerjaan::sudahLewat('pendataan-bosp.rekap-rkas', 2026, 4));
    }

    public function test_menu_yang_sama_bisa_punya_4_deadline_berbeda_untuk_4_triwulan(): void
    {
        foreach ([1, 2, 3, 4] as $triwulan) {
            DeadlinePekerjaan::aturDeadline('pendataan-ops.lampiran-2a', 2026, $triwulan, "2026-0{$triwulan}-15", null);
        }

        $this->assertSame(4, DeadlinePekerjaan::where('kunci_menu', 'pendataan-ops.lampiran-2a')->where('tahun', 2026)->count());
        $this->assertSame('2026-03-15', DeadlinePekerjaan::untuk('pendataan-ops.lampiran-2a', 2026, 3)->tanggal_deadline->format('Y-m-d'));
    }

    // ------------------------------------------------------------------
    // Akses halaman (Gate) - DIBALIK 2026-09-23 (round kesepuluh, poin 2):
    // SEBELUMNYA (round kedelapan bagian B) ketiga peran sama-sama bisa
    // membuka menu ini. Permintaan user round kesepuluh membalik
    // keputusan itu: "menu Timeline pekerjaan hanya muncul di
    // superadmin, di admin ops dan admin bosp tidak di munculkan" -
    // SEKARANG HANYA Superadmin yang bisa (baik lewat sidebar MAUPUN
    // akses URL langsung, karena keduanya sama-sama dijaga Gate
    // 'akses-timeline-pekerjaan' - lihat App\Providers\AppServiceProvider).
    // ------------------------------------------------------------------

    public function test_superadmin_bisa_membuka_halaman_timeline_pekerjaan(): void
    {
        $this->actingAs($this->superadmin())
            ->get(route('timeline-pekerjaan.index'))
            ->assertOk()
            ->assertSeeText('Timeline Pekerjaan');
    }

    public function test_admin_bosp_tidak_bisa_membuka_halaman_timeline_pekerjaan(): void
    {
        $admin = $this->adminBosp();
        $admin->profilSekolah->update([
            'nama_kepala_sekolah' => 'Kepsek', 'nip_kepala_sekolah' => '1', 'no_whatsapp_kepala_sekolah' => '0812',
            'status_kepegawaian_kepsek' => 'PNS', 'nama_pengawas' => 'P', 'nip_pengawas' => '2',
            'nama_bendahara' => 'B', 'nip_bendahara' => '3', 'status_kepegawaian_bendahara' => 'PNS',
            'alamat_sekolah' => 'Jl. X',
        ]);
        PendataanBosp::factory()->create(['profil_sekolah_id' => $admin->profil_sekolah_id]);

        $this->actingAs($admin)
            ->get(route('timeline-pekerjaan.index'))
            ->assertForbidden();
    }

    public function test_admin_ops_tidak_bisa_membuka_halaman_timeline_pekerjaan(): void
    {
        $admin = $this->adminOps();
        $admin->profilSekolah->update([
            'nama_kepala_sekolah' => 'Kepsek', 'nip_kepala_sekolah' => '1', 'no_whatsapp_kepala_sekolah' => '0812',
            'status_kepegawaian_kepsek' => 'PNS', 'nama_pengawas' => 'P', 'nip_pengawas' => '2',
            'nama_bendahara' => 'B', 'nip_bendahara' => '3', 'status_kepegawaian_bendahara' => 'PNS',
            'alamat_sekolah' => 'Jl. X',
        ]);
        PendataanOps::factory()->create(['profil_sekolah_id' => $admin->profil_sekolah_id]);

        $this->actingAs($admin)
            ->get(route('timeline-pekerjaan.index'))
            ->assertForbidden();
    }

    public function test_guest_tidak_bisa_membuka_halaman_timeline_pekerjaan(): void
    {
        $this->get(route('timeline-pekerjaan.index'))->assertRedirect(route('login'));
    }

    public function test_menu_timeline_pekerjaan_hanya_tampil_di_sidebar_superadmin(): void
    {
        // permintaan user 2026-09-23 (round kesepuluh, poin 2) - link
        // sidebar-nya sendiri (bukan cuma akses URL) harus ikut hilang
        // untuk Admin OPS/Admin BOSP. Dicek lewat halaman Dashboard
        // (semua peran bisa membukanya) supaya murni menguji tampil/
        // tidaknya link, bukan tercampur gate halaman Timeline Pekerjaan
        // itu sendiri.
        //
        // CATATAN: sengaja TIDAK memakai assertSeeText/assertDontSeeText
        // dgn teks "Timeline Pekerjaan" polos - frasa itu JUGA muncul di
        // halaman lain yg tidak terkait sidebar (popup "Info Timeline
        // Pekerjaan" dari Round 9C bagian C, muncul di SEMUA peran).
        // Makanya dicek lewat URL route link sidebar-nya
        // (route('timeline-pekerjaan.index')) yang unik utk link menu ini.
        $urlMenu = route('timeline-pekerjaan.index');

        $this->actingAs($this->superadmin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($urlMenu, false);

        $adminBosp = $this->adminBosp();
        $adminBosp->profilSekolah->update([
            'nama_kepala_sekolah' => 'Kepsek', 'nip_kepala_sekolah' => '1', 'no_whatsapp_kepala_sekolah' => '0812',
            'status_kepegawaian_kepsek' => 'PNS', 'nama_pengawas' => 'P', 'nip_pengawas' => '2',
            'nama_bendahara' => 'B', 'nip_bendahara' => '3', 'status_kepegawaian_bendahara' => 'PNS',
            'alamat_sekolah' => 'Jl. X',
        ]);
        PendataanBosp::factory()->create(['profil_sekolah_id' => $adminBosp->profil_sekolah_id]);

        $this->actingAs($adminBosp)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee($urlMenu, false);

        $adminOps = $this->adminOps();
        $adminOps->profilSekolah->update([
            'nama_kepala_sekolah' => 'Kepsek', 'nip_kepala_sekolah' => '1', 'no_whatsapp_kepala_sekolah' => '0812',
            'status_kepegawaian_kepsek' => 'PNS', 'nama_pengawas' => 'P', 'nip_pengawas' => '2',
            'nama_bendahara' => 'B', 'nip_bendahara' => '3', 'status_kepegawaian_bendahara' => 'PNS',
            'alamat_sekolah' => 'Jl. X',
        ]);
        PendataanOps::factory()->create(['profil_sekolah_id' => $adminOps->profil_sekolah_id]);

        $this->actingAs($adminOps)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee($urlMenu, false);
    }

    // ------------------------------------------------------------------
    // Triwulan - selector & reload data
    // ------------------------------------------------------------------

    public function test_triwulan_default_adalah_tw1(): void
    {
        // Round kedua belas (permintaan user 2026-09-24): "tetatp
        // berdasarkan triwulan masing-masing dengan TW 1 sebagai default
        // nya" - SEBELUMNYA default mengikuti bulan berjalan lewat
        // PajakBospReguler::triwulanDariBulan(), SEKARANG selalu TW-1
        // apa pun bulan saat halaman dibuka.
        Livewire::actingAs($this->superadmin())
            ->test(Index::class)
            ->assertSet('triwulan', 1);
    }

    public function test_pindah_triwulan_memuat_ulang_tanggal_triwulan_itu_tidak_tercampur(): void
    {
        $tahun = now()->year;
        DeadlinePekerjaan::aturDeadline('pendataan-bosp.rekap-rkas', $tahun, 1, '2026-01-31', null);
        DeadlinePekerjaan::aturDeadline('pendataan-bosp.rekap-rkas', $tahun, 2, '2026-04-30', null);

        $component = Livewire::actingAs($this->superadmin())
            ->test(Index::class)
            ->set('triwulan', 1)
            ->assertSet('tanggal.pendataan-bosp__rekap-rkas', '2026-01-31');

        $component->set('triwulan', 2)
            ->assertSet('tanggal.pendataan-bosp__rekap-rkas', '2026-04-30');

        $component->set('triwulan', 3)
            ->assertSet('tanggal.pendataan-bosp__rekap-rkas', null);
    }

    // ------------------------------------------------------------------
    // Kelola deadline (Superadmin)
    // ------------------------------------------------------------------

    public function test_superadmin_bisa_mengatur_deadline_lewat_komponen(): void
    {
        $superadmin = $this->superadmin();

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('triwulan', self::TRIWULAN_UJI)
            ->set('tanggal.pendataan-bosp__rekap-rkas', '2026-12-31')
            ->call('simpanDeadline', 'pendataan-bosp.rekap-rkas')
            ->assertHasNoErrors();

        $this->assertSame(
            '2026-12-31',
            DeadlinePekerjaan::untuk('pendataan-bosp.rekap-rkas', now()->year, self::TRIWULAN_UJI)->tanggal_deadline->format('Y-m-d')
        );
    }

    public function test_mengosongkan_tanggal_menghapus_baris_deadline(): void
    {
        $superadmin = $this->superadmin();
        DeadlinePekerjaan::aturDeadline('pendataan-bosp.rekap-rkas', now()->year, self::TRIWULAN_UJI, '2026-12-31', $superadmin->id);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('triwulan', self::TRIWULAN_UJI)
            ->set('tanggal.pendataan-bosp__rekap-rkas', '')
            ->call('simpanDeadline', 'pendataan-bosp.rekap-rkas');

        $this->assertNull(DeadlinePekerjaan::untuk('pendataan-bosp.rekap-rkas', now()->year, self::TRIWULAN_UJI));
    }

    public function test_tombol_reset_menghapus_baris_deadline(): void
    {
        $superadmin = $this->superadmin();
        DeadlinePekerjaan::aturDeadline('pendataan-bosp.rekap-rkas', now()->year, self::TRIWULAN_UJI, now()->subDay()->toDateString(), $superadmin->id);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('triwulan', self::TRIWULAN_UJI)
            ->call('hapusDeadline', 'pendataan-bosp.rekap-rkas')
            ->assertSet('tanggal.pendataan-bosp__rekap-rkas', null);

        $this->assertNull(DeadlinePekerjaan::untuk('pendataan-bosp.rekap-rkas', now()->year, self::TRIWULAN_UJI));
    }

    public function test_kunci_menu_tidak_valid_ditolak_404(): void
    {
        Livewire::actingAs($this->superadmin())
            ->test(Index::class)
            ->call('simpanDeadline', 'menu-tidak-ada')
            ->assertNotFound();
    }

    public function test_tanggal_tidak_valid_ditolak_dengan_pesan_error(): void
    {
        Livewire::actingAs($this->superadmin())
            ->test(Index::class)
            ->set('tanggal.pendataan-bosp__rekap-rkas', 'bukan-tanggal')
            ->call('simpanDeadline', 'pendataan-bosp.rekap-rkas')
            ->assertHasErrors(['tanggal.pendataan-bosp__rekap-rkas' => 'date']);
    }

    // ------------------------------------------------------------------
    // "Terapkan ke Semua" PER KATEGORI (round kesembilan bagian B;
    // round kedua belas - permintaan user 2026-09-24: "saya ingin
    // tombol Terapkan ke Semua antara Pendataan OPS dan Pendataan BOSP
    // di pisahkan tidak di gabungkan jadi di buat per pendataan")
    // ------------------------------------------------------------------

    public function test_terapkan_semua_ops_hanya_menyimpan_ke_3_tahap_kerja_ops(): void
    {
        $superadmin = $this->superadmin();

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('triwulan', self::TRIWULAN_UJI)
            ->set('tanggalTerapkanSemua.ops', '2026-11-20')
            ->call('terapkanSemua', 'ops')
            ->assertHasNoErrors();

        // Perbandingan string langsung pada `where('tanggal_deadline', ...)`
        // TIDAK dipakai di sini - cast 'date' pada model bisa menyimpan
        // format berbeda antar driver DB (mis. dengan jam 00:00:00 pada
        // sebagian driver), jadi dicek lewat whereDate() (aman lintas
        // driver) + 1 baris dicek lewat untuk()->tanggal_deadline->format().
        $this->assertSame(
            3,
            DeadlinePekerjaan::where('tahun', now()->year)
                ->where('triwulan', self::TRIWULAN_UJI)
                ->whereDate('tanggal_deadline', '2026-11-20')
                ->count()
        );
        $this->assertSame(
            '2026-11-20',
            DeadlinePekerjaan::untuk('pendataan-ops.lampiran-2a', now()->year, self::TRIWULAN_UJI)->tanggal_deadline->format('Y-m-d')
        );
        // Kategori BOSP SAMA SEKALI tidak tersentuh.
        $this->assertNull(DeadlinePekerjaan::untuk('pendataan-bosp.rekap-rkas', now()->year, self::TRIWULAN_UJI));
    }

    public function test_terapkan_semua_bosp_hanya_menyimpan_ke_13_tahap_kerja_bosp(): void
    {
        $superadmin = $this->superadmin();

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('triwulan', self::TRIWULAN_UJI)
            ->set('tanggalTerapkanSemua.bosp', '2026-11-20')
            ->call('terapkanSemua', 'bosp')
            ->assertHasNoErrors();

        $this->assertSame(
            13,
            DeadlinePekerjaan::where('tahun', now()->year)
                ->where('triwulan', self::TRIWULAN_UJI)
                ->whereDate('tanggal_deadline', '2026-11-20')
                ->count()
        );
        $this->assertSame(
            '2026-11-20',
            DeadlinePekerjaan::untuk('pendataan-bosp.rekap-rkas', now()->year, self::TRIWULAN_UJI)->tanggal_deadline->format('Y-m-d')
        );
        // Kategori OPS SAMA SEKALI tidak tersentuh.
        $this->assertNull(DeadlinePekerjaan::untuk('pendataan-ops.lampiran-2a', now()->year, self::TRIWULAN_UJI));
    }

    public function test_terapkan_semua_hanya_mempengaruhi_triwulan_yang_aktif(): void
    {
        $superadmin = $this->superadmin();

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('triwulan', 1)
            ->set('tanggalTerapkanSemua.bosp', '2026-03-01')
            ->call('terapkanSemua', 'bosp');

        // Triwulan 2 SAMA SEKALI belum tersentuh.
        $this->assertSame(0, DeadlinePekerjaan::where('tahun', now()->year)->where('triwulan', 2)->count());
        $this->assertSame(13, DeadlinePekerjaan::where('tahun', now()->year)->where('triwulan', 1)->count());
    }

    public function test_terapkan_semua_menimpa_tanggal_yang_sudah_ada_sebelumnya(): void
    {
        $superadmin = $this->superadmin();
        DeadlinePekerjaan::aturDeadline('pendataan-bosp.rekap-rkas', now()->year, self::TRIWULAN_UJI, '2026-01-01', null);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('triwulan', self::TRIWULAN_UJI)
            ->set('tanggalTerapkanSemua.bosp', '2026-05-05')
            ->call('terapkanSemua', 'bosp');

        $this->assertSame(
            '2026-05-05',
            DeadlinePekerjaan::untuk('pendataan-bosp.rekap-rkas', now()->year, self::TRIWULAN_UJI)->tanggal_deadline->format('Y-m-d')
        );
    }

    public function test_terapkan_semua_tanggal_kosong_ditolak_validasi(): void
    {
        Livewire::actingAs($this->superadmin())
            ->test(Index::class)
            ->set('tanggalTerapkanSemua.bosp', '')
            ->call('terapkanSemua', 'bosp')
            ->assertHasErrors(['tanggalTerapkanSemua.bosp' => 'required']);

        $this->assertSame(0, DeadlinePekerjaan::where('tahun', now()->year)->count());
    }

    public function test_terapkan_semua_kategori_tidak_valid_ditolak_404(): void
    {
        Livewire::actingAs($this->superadmin())
            ->test(Index::class)
            ->set('tanggalTerapkanSemua.ops', '2026-05-05')
            ->call('terapkanSemua', 'bukan-kategori')
            ->assertNotFound();
    }

    public function test_admin_bosp_ditolak_403_saat_coba_terapkan_semua(): void
    {
        Livewire::actingAs($this->adminBosp())
            ->test(Index::class)
            ->set('tanggalTerapkanSemua.bosp', '2026-05-05')
            ->call('terapkanSemua', 'bosp')
            ->assertForbidden();
    }

    // ------------------------------------------------------------------
    // "Reset Semua" PER KATEGORI (round kedua belas - permintaan user
    // 2026-09-24: "buatkan option tombol reset untuk semua apabila
    // admin OPS/admin BOSP nya mau reset" - jawaban AskUserQuestion
    // "Akses Reset Semua" -> "Hanya Superadmin (Recommended)": tombol
    // ini TETAP hanya bisa dipakai Superadmin, Admin OPS/Admin BOSP
    // TIDAK mendapat akses baru apa pun lewat perubahan ini)
    // ------------------------------------------------------------------

    public function test_reset_semua_bosp_menghapus_seluruh_deadline_kategori_bosp_triwulan_aktif(): void
    {
        $superadmin = $this->superadmin();
        foreach (array_keys(DeadlinePekerjaan::daftarTahapKerja()) as $kunciMenu) {
            DeadlinePekerjaan::aturDeadline($kunciMenu, now()->year, self::TRIWULAN_UJI, '2026-05-05', $superadmin->id);
        }

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('triwulan', self::TRIWULAN_UJI)
            ->call('resetSemua', 'bosp')
            ->assertHasNoErrors();

        // Seluruh 13 baris BOSP terhapus, 3 baris OPS (triwulan sama)
        // SAMA SEKALI tidak tersentuh.
        $this->assertSame(3, DeadlinePekerjaan::where('tahun', now()->year)->where('triwulan', self::TRIWULAN_UJI)->count());
        $this->assertNull(DeadlinePekerjaan::untuk('pendataan-bosp.rekap-rkas', now()->year, self::TRIWULAN_UJI));
        $this->assertNotNull(DeadlinePekerjaan::untuk('pendataan-ops.lampiran-2a', now()->year, self::TRIWULAN_UJI));
    }

    public function test_reset_semua_ops_menghapus_seluruh_deadline_kategori_ops_triwulan_aktif(): void
    {
        $superadmin = $this->superadmin();
        foreach (array_keys(DeadlinePekerjaan::daftarTahapKerja()) as $kunciMenu) {
            DeadlinePekerjaan::aturDeadline($kunciMenu, now()->year, self::TRIWULAN_UJI, '2026-05-05', $superadmin->id);
        }

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('triwulan', self::TRIWULAN_UJI)
            ->call('resetSemua', 'ops')
            ->assertHasNoErrors();

        $this->assertSame(13, DeadlinePekerjaan::where('tahun', now()->year)->where('triwulan', self::TRIWULAN_UJI)->count());
        $this->assertNull(DeadlinePekerjaan::untuk('pendataan-ops.lampiran-2a', now()->year, self::TRIWULAN_UJI));
        $this->assertNotNull(DeadlinePekerjaan::untuk('pendataan-bosp.rekap-rkas', now()->year, self::TRIWULAN_UJI));
    }

    public function test_reset_semua_hanya_mempengaruhi_triwulan_yang_aktif(): void
    {
        $superadmin = $this->superadmin();
        DeadlinePekerjaan::aturDeadline('pendataan-bosp.rekap-rkas', now()->year, 1, '2026-05-05', $superadmin->id);
        DeadlinePekerjaan::aturDeadline('pendataan-bosp.rekap-rkas', now()->year, 2, '2026-05-05', $superadmin->id);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('triwulan', 1)
            ->call('resetSemua', 'bosp');

        $this->assertNull(DeadlinePekerjaan::untuk('pendataan-bosp.rekap-rkas', now()->year, 1));
        $this->assertNotNull(DeadlinePekerjaan::untuk('pendataan-bosp.rekap-rkas', now()->year, 2));
    }

    public function test_reset_semua_menghapus_tanpa_perlu_validasi_tanggal(): void
    {
        // Berbeda dari terapkanSemua() - resetSemua() tidak butuh input
        // tanggal apa pun, jadi tidak boleh gagal validasi kalau kotak
        // tanggalTerapkanSemua kosong.
        $superadmin = $this->superadmin();
        DeadlinePekerjaan::aturDeadline('pendataan-bosp.rekap-rkas', now()->year, self::TRIWULAN_UJI, '2026-05-05', $superadmin->id);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('triwulan', self::TRIWULAN_UJI)
            ->call('resetSemua', 'bosp')
            ->assertHasNoErrors();

        $this->assertNull(DeadlinePekerjaan::untuk('pendataan-bosp.rekap-rkas', now()->year, self::TRIWULAN_UJI));
    }

    public function test_reset_semua_kategori_tidak_valid_ditolak_404(): void
    {
        Livewire::actingAs($this->superadmin())
            ->test(Index::class)
            ->call('resetSemua', 'bukan-kategori')
            ->assertNotFound();
    }

    public function test_admin_bosp_ditolak_403_saat_coba_reset_semua(): void
    {
        Livewire::actingAs($this->adminBosp())
            ->test(Index::class)
            ->call('resetSemua', 'bosp')
            ->assertForbidden();
    }

    public function test_admin_ops_ditolak_403_saat_coba_reset_semua(): void
    {
        Livewire::actingAs($this->adminOps())
            ->test(Index::class)
            ->call('resetSemua', 'ops')
            ->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Admin OPS/BOSP hanya boleh lihat, tidak boleh mengatur
    // ------------------------------------------------------------------

    public function test_admin_bosp_ditolak_403_saat_coba_mengatur_deadline(): void
    {
        Livewire::actingAs($this->adminBosp())
            ->test(Index::class)
            ->set('tanggal.pendataan-bosp__rekap-rkas', '2026-12-31')
            ->call('simpanDeadline', 'pendataan-bosp.rekap-rkas')
            ->assertForbidden();
    }

    public function test_admin_ops_ditolak_403_saat_coba_mengatur_deadline(): void
    {
        Livewire::actingAs($this->adminOps())
            ->test(Index::class)
            ->set('tanggal.pendataan-bosp__rekap-rkas', '2026-12-31')
            ->call('simpanDeadline', 'pendataan-bosp.rekap-rkas')
            ->assertForbidden();
    }

    public function test_admin_bosp_ditolak_403_saat_coba_hapus_deadline(): void
    {
        Livewire::actingAs($this->adminBosp())
            ->test(Index::class)
            ->call('hapusDeadline', 'pendataan-bosp.rekap-rkas')
            ->assertForbidden();
    }

    public function test_tombol_kelola_tidak_tampil_untuk_admin_bosp(): void
    {
        $admin = $this->adminBosp();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertSet('tanggal', fn ($tanggal) => is_array($tanggal))
            ->assertViewHas('bolehKelola', false);
    }

    public function test_tombol_kelola_tampil_untuk_superadmin(): void
    {
        Livewire::actingAs($this->superadmin())
            ->test(Index::class)
            ->assertViewHas('bolehKelola', true);
    }

    // ------------------------------------------------------------------
    // Status badge (belum_diatur / berjalan / terlambat)
    // ------------------------------------------------------------------

    public function test_status_belum_diatur_saat_belum_ada_deadline(): void
    {
        Livewire::actingAs($this->superadmin())
            ->test(Index::class)
            ->assertViewHas('status', fn ($status) => $status['pendataan-bosp.rekap-rkas'] === 'belum_diatur');
    }

    public function test_status_berjalan_saat_deadline_masih_di_masa_depan(): void
    {
        // Triwulan 1 dipakai di sini (BUKAN dihitung dari bulan berjalan
        // lewat PajakBospReguler) karena mount() sekarang SELALU default
        // ke TW-1 (round kedua belas).
        $tahun = now()->year;
        DeadlinePekerjaan::aturDeadline('pendataan-bosp.rekap-rkas', $tahun, 1, now()->addWeek()->toDateString(), null);

        Livewire::actingAs($this->superadmin())
            ->test(Index::class)
            ->assertViewHas('status', fn ($status) => $status['pendataan-bosp.rekap-rkas'] === 'berjalan');
    }

    public function test_status_terlambat_saat_deadline_sudah_lewat(): void
    {
        $tahun = now()->year;
        DeadlinePekerjaan::aturDeadline('pendataan-bosp.rekap-rkas', $tahun, 1, now()->subWeek()->toDateString(), null);

        Livewire::actingAs($this->superadmin())
            ->test(Index::class)
            ->assertViewHas('status', fn ($status) => $status['pendataan-bosp.rekap-rkas'] === 'terlambat');
    }

    // ------------------------------------------------------------------
    // Sidebar
    // ------------------------------------------------------------------

    public function test_menu_timeline_pekerjaan_muncul_di_sidebar_superadmin(): void
    {
        $this->actingAs($this->superadmin())
            ->get(route('dashboard'))
            ->assertSeeText('Timeline Pekerjaan');
    }
}
