<?php

namespace Tests\Feature;

use App\Livewire\PendataanBosp\PajakBospReguler\Index;
use App\Models\PajakBospReguler;
use App\Models\PendataanBosp;
use App\Models\ProfilSekolah;
use App\Models\RincianBelanjaModal;
use App\Models\User;
use App\Models\VervalRealisasiBosp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menguji menu Pajak BOSP Reguler - Pendataan BOSP (permintaan user
 * 2026-09-11, Part 23): 12 baris TETAP per sekolah per tahun (1 baris =
 * 1 bulan), input langsung di kotak tabel (CRUD lengkap - Create/Update
 * lewat kotak, Read lewat tabel, Delete eksplisit per bulan), rumus
 * Jumlah (Debit/Kredit) & Saldo (kumulatif, reset tiap tahun) & Triwulan
 * (otomatis) SEMUA dihitung dinamis (tidak disimpan ke DB), Tab 2
 * Rekapitulasi (Superadmin saja, 1 baris per sekolah total setahun),
 * Export Excel/PDF kedua tab, zoom, route & sidebar.
 *
 * Beda dengan pola RBAC "baris bebas per sekolah" pada menu lain: di
 * sini kotak tabel dikunci lewat sekolahAktifId() (SELALU sekolah sendiri
 * untuk Admin BOSP), jadi tidak ada jalur untuk "menyelipkan" data ke
 * sekolah lain lewat nama property baris.{bulan}.{field} - keamanan lintas
 * sekolah diuji lewat jalur pilihSekolah() (Superadmin-only) & lewat
 * kondisi "Superadmin belum memilih sekolah" (kotak tidak bisa diisi).
 */
class PajakBospRegulerTest extends TestCase
{
    use RefreshDatabase;

    private function lengkapiIdentitasBosp(ProfilSekolah $sekolah): void
    {
        $sekolah->update([
            'nama_kepala_sekolah' => 'Kepsek',
            'nip_kepala_sekolah' => '123',
            'no_whatsapp_kepala_sekolah' => '081200000000',
            'status_kepegawaian_kepsek' => 'PNS',
            'nama_pengawas' => 'Pengawas',
            'nip_pengawas' => '456',
            'nama_bendahara' => 'Bendahara',
            'nip_bendahara' => '789',
            'status_kepegawaian_bendahara' => 'PNS',
            'alamat_sekolah' => 'Jl. Contoh',
        ]);
    }

    public function test_admin_bosp_otomatis_terkunci_ke_sekolahnya_sendiri(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        $component->assertSet('profil_sekolah_id', $sekolah->id);
        $this->assertSame($sekolah->id, $component->viewData('sekolah')->id);
    }

    // ------------------------------------------------------------------
    // Kuncian UI setelah Validasi Hasil Entry Data BOSP "Sesuai" -
    // permintaan user 2026-09-23 (round kesepuluh, poin 1b). Menu ini
    // BEDA dari 9 menu sumber lain: KESELURUHAN 12 bulan (4 triwulan)
    // ditampilkan SEKALIGUS dalam 1 tabel, jadi kuncian dihitung PER
    // BULAN lewat array $terkunciPerBulan - diuji terpisah karena
    // struktur data ini khusus/baru untuk menu ini.
    // ------------------------------------------------------------------

    public function test_baris_bulan_di_triwulan_yang_sudah_sesuai_terkunci_bulan_lain_tidak(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        // TW1 (bulan 1-3) sesuai - TW2-4 (bulan 4-12) tidak.
        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => $tahun,
            'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => $adminBosp->id,
            'diverval_pada' => now(),
        ]);

        $component = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('tahun', $tahun);

        $terkunciPerBulan = $component->viewData('terkunciPerBulan');

        foreach ([1, 2, 3] as $bulan) {
            $this->assertTrue($terkunciPerBulan[$bulan], "Bulan {$bulan} (TW1) seharusnya terkunci");
        }
        foreach ([4, 5, 6, 7, 8, 9, 10, 11, 12] as $bulan) {
            $this->assertFalse($terkunciPerBulan[$bulan], "Bulan {$bulan} seharusnya TIDAK terkunci");
        }

        // Menu ini TIDAK punya banner kuncian selebar halaman (beda dari
        // menu-menu lain) - kuncian ditandai PER BARIS lewat ikon gembok
        // kecil di samping nama bulan (lihat Blade-nya). $name di
        // <x-icon> tidak ikut dicetak ke HTML, jadi dicek lewat markah
        // SVG unik ikon "lock-closed" itu sendiri (lihat
        // resources/views/components/icon.blade.php).
        $component->assertSee('M8 11V7a4 4 0 0 1 8 0v4', false);
    }

    public function test_superadmin_tidak_pernah_terkunci_di_tabel_pajak_bosp_reguler(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $tahun = now()->year;

        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => $tahun,
            'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => $superadmin->id,
            'diverval_pada' => now(),
        ]);

        $component = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('tahun', $tahun)
            ->set('profil_sekolah_id', $sekolah->id);

        $terkunciPerBulan = $component->viewData('terkunciPerBulan');

        foreach (range(1, 12) as $bulan) {
            $this->assertFalse($terkunciPerBulan[$bulan], "Superadmin - bulan {$bulan} tidak boleh terkunci");
        }
    }

    public function test_superadmin_wajib_pilih_sekolah_dulu_sebelum_tabel_terisi(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PajakBospReguler::factory()->create(['profil_sekolah_id' => $sekolah->id, 'bulan' => 1]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $component->assertSet('profil_sekolah_id', null);
        $this->assertNull($component->viewData('sekolah'));

        $baris = $component->get('baris');
        foreach (range(1, 12) as $bulan) {
            $this->assertSame('', $baris[$bulan]['ppn_debit']);
        }
    }

    public function test_superadmin_bisa_memilih_sekolah_lalu_tabel_terisi(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'bulan' => 1,
            'ppn_debit' => 500000,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('pilihSekolah', $sekolah->id);

        $component->assertSet('profil_sekolah_id', $sekolah->id);
        $this->assertSame($sekolah->id, $component->viewData('sekolah')->id);
        $this->assertSame('500000', $component->get('baris')[1]['ppn_debit']);
    }

    public function test_pilih_sekolah_dengan_id_tidak_valid_dapat_404(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('pilihSekolah', 999999)
            ->assertNotFound();
    }

    public function test_admin_bosp_memanggil_pilih_sekolah_tidak_berpengaruh(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('pilihSekolah', $sekolahLain->id)
            ->assertSet('profil_sekolah_id', $sekolahSaya->id);
    }

    public function test_input_kotak_bulan_membuat_baris_baru(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('baris.3.ppn_debit', '250000')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pajak_bosp_reguler', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'bulan' => 3,
            'ppn_debit' => 250000,
        ]);
    }

    public function test_input_kotak_bulan_memperbarui_baris_yang_sudah_ada(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $baris = PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'bulan' => 5,
            'ppn_debit' => 100000,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('baris.5.ppn_debit', '750000')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('pajak_bosp_reguler', 1);
        $this->assertDatabaseHas('pajak_bosp_reguler', [
            'id' => $baris->id,
            'ppn_debit' => 750000,
        ]);
    }

    public function test_input_kotak_bulan_menolak_nilai_negatif(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('baris.1.ppn_debit', '-5000')
            ->assertHasErrors(['baris.1.ppn_debit']);

        $this->assertDatabaseMissing('pajak_bosp_reguler', [
            'profil_sekolah_id' => $sekolah->id,
            'bulan' => 1,
        ]);
    }

    public function test_input_kotak_bulan_menolak_nilai_bukan_angka(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('baris.1.ppn_debit', 'abc')
            ->assertHasErrors(['baris.1.ppn_debit']);

        $this->assertDatabaseMissing('pajak_bosp_reguler', [
            'profil_sekolah_id' => $sekolah->id,
            'bulan' => 1,
        ]);
    }

    public function test_mengosongkan_kotak_menyimpan_nilai_null(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $baris = PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'bulan' => 2,
            'ppn_debit' => 100000,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('baris.2.ppn_debit', '')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pajak_bosp_reguler', [
            'id' => $baris->id,
            'ppn_debit' => null,
        ]);
    }

    public function test_superadmin_yang_belum_pilih_sekolah_tidak_bisa_mengisi_kotak(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('baris.1.ppn_debit', '100000')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('pajak_bosp_reguler', 0);
    }

    public function test_konfirmasi_hapus_bulan_membuka_modal(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PajakBospReguler::factory()->create(['profil_sekolah_id' => $sekolah->id, 'bulan' => 4]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('konfirmasiHapusBulan', 4)
            ->assertSet('confirmingHapusBulan', 4)
            ->assertDispatched('open-modal');
    }

    public function test_batal_hapus_bulan_tidak_menghapus_data(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $baris = PajakBospReguler::factory()->create(['profil_sekolah_id' => $sekolah->id, 'bulan' => 4]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('konfirmasiHapusBulan', 4)
            ->call('batalHapusBulan')
            ->assertSet('confirmingHapusBulan', null);

        $this->assertDatabaseHas('pajak_bosp_reguler', ['id' => $baris->id]);
    }

    public function test_hapus_bulan_menghapus_seluruh_data_bulan_itu(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $baris = PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'bulan' => 6,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('konfirmasiHapusBulan', 6)
            ->call('hapusBulan')
            ->assertSet('confirmingHapusBulan', null);

        $this->assertDatabaseMissing('pajak_bosp_reguler', ['id' => $baris->id]);
    }

    public function test_jumlah_debit_dan_kredit_dihitung_otomatis(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'bulan' => 1,
            'ppn_debit' => 100000,
            'pph21_debit' => 200000,
            'pph23_debit' => 0,
            'pph4_debit' => 0,
            'sspd_debit' => 0,
            'ppn_kredit' => 50000,
            'pph21_kredit' => 0,
            'pph23_kredit' => 0,
            'pph4_kredit' => 0,
            'sspd_kredit' => 0,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);
        $jumlahPerBulan = $component->viewData('jumlahPerBulan');

        $this->assertSame(300000, $jumlahPerBulan[1]['debit']);
        $this->assertSame(50000, $jumlahPerBulan[1]['kredit']);
    }

    public function test_saldo_kumulatif_carry_over_antar_bulan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => now()->year, 'bulan' => 1,
            'ppn_debit' => 1000, 'pph21_debit' => 0, 'pph23_debit' => 0, 'pph4_debit' => 0, 'sspd_debit' => 0,
            'ppn_kredit' => 200, 'pph21_kredit' => 0, 'pph23_kredit' => 0, 'pph4_kredit' => 0, 'sspd_kredit' => 0,
        ]);
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => now()->year, 'bulan' => 2,
            'ppn_debit' => 500, 'pph21_debit' => 0, 'pph23_debit' => 0, 'pph4_debit' => 0, 'sspd_debit' => 0,
            'ppn_kredit' => 100, 'pph21_kredit' => 0, 'pph23_kredit' => 0, 'pph4_kredit' => 0, 'sspd_kredit' => 0,
        ]);
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => now()->year, 'bulan' => 3,
            'ppn_debit' => 0, 'pph21_debit' => 0, 'pph23_debit' => 0, 'pph4_debit' => 0, 'sspd_debit' => 0,
            'ppn_kredit' => 300, 'pph21_kredit' => 0, 'pph23_kredit' => 0, 'pph4_kredit' => 0, 'sspd_kredit' => 0,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $saldoPerBulan = Livewire::actingAs($adminBosp)->test(Index::class)->viewData('saldoPerBulan');

        $this->assertSame(800, $saldoPerBulan[1]);
        $this->assertSame(1200, $saldoPerBulan[2]);
        $this->assertSame(900, $saldoPerBulan[3]);
        // Bulan 4 belum ada data - saldo tetap terbawa dari bulan 3 (tidak berubah).
        $this->assertSame(900, $saldoPerBulan[4]);
    }

    public function test_saldo_reset_di_awal_tahun_baru_tidak_terbawa_dari_tahun_sebelumnya(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year - 1,
            'bulan' => 12,
            'ppn_debit' => 9999999,
            'pph21_debit' => 0, 'pph23_debit' => 0, 'pph4_debit' => 0, 'sspd_debit' => 0,
            'ppn_kredit' => 0, 'pph21_kredit' => 0, 'pph23_kredit' => 0, 'pph4_kredit' => 0, 'sspd_kredit' => 0,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $saldoPerBulan = Livewire::actingAs($adminBosp)->test(Index::class)->viewData('saldoPerBulan');

        $this->assertSame(0, $saldoPerBulan[1]);
    }

    public function test_triwulan_dihitung_otomatis_dari_data_bulanan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => now()->year, 'bulan' => 1,
            'ppn_debit' => 1000, 'pph21_debit' => 0, 'pph23_debit' => 0, 'pph4_debit' => 0, 'sspd_debit' => 0,
            'ppn_kredit' => 200, 'pph21_kredit' => 0, 'pph23_kredit' => 0, 'pph4_kredit' => 0, 'sspd_kredit' => 0,
        ]);
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => now()->year, 'bulan' => 2,
            'ppn_debit' => 500, 'pph21_debit' => 0, 'pph23_debit' => 0, 'pph4_debit' => 0, 'sspd_debit' => 0,
            'ppn_kredit' => 100, 'pph21_kredit' => 0, 'pph23_kredit' => 0, 'pph4_kredit' => 0, 'sspd_kredit' => 0,
        ]);
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => now()->year, 'bulan' => 3,
            'ppn_debit' => 0, 'pph21_debit' => 0, 'pph23_debit' => 0, 'pph4_debit' => 0, 'sspd_debit' => 0,
            'ppn_kredit' => 300, 'pph21_kredit' => 0, 'pph23_kredit' => 0, 'pph4_kredit' => 0, 'sspd_kredit' => 0,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $triwulanData = Livewire::actingAs($adminBosp)->test(Index::class)->viewData('triwulanData');

        $this->assertSame(1500, $triwulanData[1]['debit']);
        $this->assertSame(600, $triwulanData[1]['kredit']);
        $this->assertSame(900, $triwulanData[1]['saldo']);
    }

    /**
     * Permintaan user 2026-09-15: kolom Jumlah Debit/Kredit pada tabel
     * Rekapitulasi per Triwulan dirinci per jenis pajak (PPN, PPh 21, PPh
     * 23, PPh Pasal 4 Ayat 2, SSPD) - lihat
     * PajakBospReguler::hitungTriwulan() param $rincianPerBulan baru.
     */
    public function test_triwulan_rincian_per_jenis_pajak(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => now()->year, 'bulan' => 1,
            'ppn_debit' => 1000, 'pph21_debit' => 300, 'pph23_debit' => 0, 'pph4_debit' => 0, 'sspd_debit' => 0,
            'ppn_kredit' => 200, 'pph21_kredit' => 0, 'pph23_kredit' => 0, 'pph4_kredit' => 0, 'sspd_kredit' => 0,
        ]);
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => now()->year, 'bulan' => 2,
            'ppn_debit' => 500, 'pph21_debit' => 100, 'pph23_debit' => 0, 'pph4_debit' => 0, 'sspd_debit' => 0,
            'ppn_kredit' => 0, 'pph21_kredit' => 0, 'pph23_kredit' => 0, 'pph4_kredit' => 0, 'sspd_kredit' => 0,
        ]);
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => now()->year, 'bulan' => 3,
            'ppn_debit' => 0, 'pph21_debit' => 0, 'pph23_debit' => 0, 'pph4_debit' => 0, 'sspd_debit' => 0,
            'ppn_kredit' => 300, 'pph21_kredit' => 50, 'pph23_kredit' => 0, 'pph4_kredit' => 0, 'sspd_kredit' => 0,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $triwulanData = Livewire::actingAs($adminBosp)->test(Index::class)->viewData('triwulanData');

        $this->assertSame(1500, $triwulanData[1]['rincian']['ppn_debit']);
        $this->assertSame(400, $triwulanData[1]['rincian']['pph21_debit']);
        $this->assertSame(0, $triwulanData[1]['rincian']['pph23_debit']);
        $this->assertSame(500, $triwulanData[1]['rincian']['ppn_kredit']);
        $this->assertSame(50, $triwulanData[1]['rincian']['pph21_kredit']);
        // Total gabungan tetap konsisten dengan jumlah rincian per jenis.
        $this->assertSame(1900, $triwulanData[1]['debit']);
        $this->assertSame(550, $triwulanData[1]['kredit']);
    }

    /**
     * Permintaan user 2026-09-15: tabel Rekapitulasi per Triwulan (Tab 1)
     * memakai label "Penerimaan (Debit)"/"Pengeluaran (Kredit)", bukan
     * lagi "Jumlah Debit"/"Jumlah Kredit".
     */
    public function test_tampilan_triwulan_memakai_label_penerimaan_dan_pengeluaran(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->assertSee('Penerimaan (Debit)')
            ->assertSee('Pengeluaran (Kredit)')
            ->assertDontSee('Jumlah Debit')
            ->assertDontSee('Jumlah Kredit');
    }

    public function test_admin_bosp_tidak_bisa_pindah_ke_tab_rekap(): void
    {
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('pindahTab', 'rekap')
            ->assertSet('tab', 'per_sekolah');
    }

    public function test_superadmin_bisa_pindah_ke_tab_rekap(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('pindahTab', 'rekap')
            ->assertSet('tab', 'rekap');
    }

    public function test_tab_rekap_satu_baris_per_sekolah_total_setahun(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create(['status' => ProfilSekolah::STATUS_NEGERI, 'nama_sekolah' => 'Z SD Negeri']);
        $sekolah2 = ProfilSekolah::factory()->create(['status' => ProfilSekolah::STATUS_SWASTA, 'nama_sekolah' => 'A SD Swasta']);
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah1->id, 'tahun' => now()->year, 'bulan' => 1,
            'ppn_debit' => 1000, 'pph21_debit' => 0, 'pph23_debit' => 0, 'pph4_debit' => 0, 'sspd_debit' => 0,
            'ppn_kredit' => 200, 'pph21_kredit' => 0, 'pph23_kredit' => 0, 'pph4_kredit' => 0, 'sspd_kredit' => 0,
        ]);
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah1->id, 'tahun' => now()->year, 'bulan' => 2,
            'ppn_debit' => 500, 'pph21_debit' => 0, 'pph23_debit' => 0, 'pph4_debit' => 0, 'sspd_debit' => 0,
            'ppn_kredit' => 0, 'pph21_kredit' => 0, 'pph23_kredit' => 0, 'pph4_kredit' => 0, 'sspd_kredit' => 0,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $rekapSekolah = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('pindahTab', 'rekap')
            ->viewData('rekapSekolah');

        $this->assertCount(2, $rekapSekolah);
        // Negeri dulu, baru Swasta.
        $this->assertSame($sekolah1->id, $rekapSekolah->first()['sekolah']->id);
        $this->assertSame(1500, $rekapSekolah->first()['total_debit']);
        $this->assertSame(200, $rekapSekolah->first()['total_kredit']);
        $this->assertSame(1300, $rekapSekolah->first()['saldo_akhir']);
        $this->assertSame(0, $rekapSekolah->last()['total_debit']);
    }

    /**
     * Permintaan user 2026-09-15: kolom "Total Debit/Kredit Setahun" pada
     * Tab 2 (Rekapitulasi Pajak Seluruh Sekolah) dirinci per jenis pajak -
     * lihat PajakBospReguler::hitungTotalRincian().
     */
    public function test_tab_rekap_dirinci_per_jenis_pajak(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => now()->year, 'bulan' => 1,
            'ppn_debit' => 1000, 'pph21_debit' => 300, 'pph23_debit' => 0, 'pph4_debit' => 0, 'sspd_debit' => 0,
            'ppn_kredit' => 200, 'pph21_kredit' => 0, 'pph23_kredit' => 0, 'pph4_kredit' => 0, 'sspd_kredit' => 0,
        ]);
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => now()->year, 'bulan' => 2,
            'ppn_debit' => 500, 'pph21_debit' => 0, 'pph23_debit' => 0, 'pph4_debit' => 0, 'sspd_debit' => 0,
            'ppn_kredit' => 0, 'pph21_kredit' => 0, 'pph23_kredit' => 0, 'pph4_kredit' => 0, 'sspd_kredit' => 0,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $rekapSekolah = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('pindahTab', 'rekap')
            ->viewData('rekapSekolah');

        $rincian = $rekapSekolah->first()['rincian'];
        $this->assertSame(1500, $rincian['ppn_debit']);
        $this->assertSame(300, $rincian['pph21_debit']);
        $this->assertSame(0, $rincian['pph23_debit']);
        $this->assertSame(200, $rincian['ppn_kredit']);
        $this->assertSame(0, $rincian['pph21_kredit']);
    }

    /**
     * Permintaan user 2026-09-15 (Part 25, susulan): baris "Jumlah" total
     * seluruh sekolah pada tabel Tab 2 (tampilan aplikasi) - sebelumnya
     * hanya ditambahkan pada Export Excel, padahal juga diminta pada
     * tabel di menu/aplikasinya sendiri ("pada tab ... belum muncul
     * baris jumlah nya").
     */
    public function test_tab_rekap_ada_baris_jumlah_total_seluruh_sekolah(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create(['status' => ProfilSekolah::STATUS_NEGERI, 'nama_sekolah' => 'Z SD Negeri']);
        $sekolah2 = ProfilSekolah::factory()->create(['status' => ProfilSekolah::STATUS_SWASTA, 'nama_sekolah' => 'A SD Swasta']);
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah1->id, 'tahun' => now()->year, 'bulan' => 1,
            'ppn_debit' => 1000, 'pph21_debit' => 300, 'pph23_debit' => 0, 'pph4_debit' => 0, 'sspd_debit' => 0,
            'ppn_kredit' => 200, 'pph21_kredit' => 0, 'pph23_kredit' => 0, 'pph4_kredit' => 0, 'sspd_kredit' => 0,
        ]);
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah2->id, 'tahun' => now()->year, 'bulan' => 1,
            'ppn_debit' => 500, 'pph21_debit' => 0, 'pph23_debit' => 0, 'pph4_debit' => 0, 'sspd_debit' => 0,
            'ppn_kredit' => 0, 'pph21_kredit' => 0, 'pph23_kredit' => 0, 'pph4_kredit' => 0, 'sspd_kredit' => 0,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $test = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('pindahTab', 'rekap');

        $rekapTotal = $test->viewData('rekapTotal');
        $this->assertSame(1500, $rekapTotal['rincian']['ppn_debit']);
        $this->assertSame(300, $rekapTotal['rincian']['pph21_debit']);
        $this->assertSame(200, $rekapTotal['rincian']['ppn_kredit']);
        $this->assertSame(1800, $rekapTotal['total_debit']);
        $this->assertSame(200, $rekapTotal['total_kredit']);
        $this->assertSame(1600, $rekapTotal['saldo_akhir']);

        $test->assertSeeInOrder(['Z SD Negeri', 'A SD Swasta', 'Jumlah']);
    }

    /**
     * Permintaan user 2026-09-15 (Part 25): tulisan "PPh Pasal 4 Ayat 2"
     * diganti menjadi "PPh 4" - label ini dipakai bersama (satu sumber di
     * PajakBospReguler::LABEL_PAJAK) oleh tabel bulanan, Triwulan, Tab 2,
     * Export Excel & PDF, jadi cukup diuji sekali di sini.
     */
    public function test_label_pph4_sudah_disingkat(): void
    {
        $this->assertSame('PPh 4', PajakBospReguler::LABEL_PAJAK['pph4']);
    }

    public function test_export_excel_wajib_pilih_sekolah_dulu_untuk_superadmin(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('exportExcel')
            ->assertSet('errorExport', fn ($pesan) => ! empty($pesan));
    }

    public function test_export_excel_berhasil_setelah_pilih_sekolah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PajakBospReguler::factory()->create(['profil_sekolah_id' => $sekolah->id, 'bulan' => 1]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('pilihSekolah', $sekolah->id)
            ->call('exportExcel')
            ->assertFileDownloaded('pajak-bosp-reguler-'.Str::slug($sekolah->nama_sekolah).'-'.now()->year.'.xlsx');
    }

    public function test_admin_bosp_bisa_export_excel_tanpa_pilih_sekolah_karena_sudah_terkunci(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PajakBospReguler::factory()->create(['profil_sekolah_id' => $sekolah->id, 'bulan' => 1]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('exportExcel')
            ->assertFileDownloaded('pajak-bosp-reguler-'.Str::slug($sekolah->nama_sekolah).'-'.now()->year.'.xlsx');
    }

    public function test_export_excel_rekap_hanya_untuk_superadmin(): void
    {
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('exportExcelRekap')
            ->assertNoFileDownloaded();
    }

    public function test_export_excel_rekap_berhasil_untuk_superadmin(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('exportExcelRekap')
            ->assertFileDownloaded('rekapitulasi-pajak-bosp-reguler-seluruh-sekolah-'.now()->year.'.xlsx');
    }

    public function test_export_pdf_wajib_pilih_sekolah_dulu_untuk_superadmin(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('exportPdf')
            ->assertSet('errorExport', fn ($pesan) => ! empty($pesan));
    }

    public function test_export_pdf_berhasil_setelah_pilih_sekolah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PajakBospReguler::factory()->create(['profil_sekolah_id' => $sekolah->id, 'bulan' => 1]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('exportPdf')
            ->assertFileDownloaded('pajak-bosp-reguler-'.Str::slug($sekolah->nama_sekolah).'-'.now()->year.'.pdf');
    }

    public function test_export_pdf_rekap_berhasil_untuk_superadmin(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('exportPdfRekap')
            ->assertFileDownloaded('rekapitulasi-pajak-bosp-reguler-seluruh-sekolah-'.now()->year.'.pdf');
    }

    public function test_zoom_in_dan_zoom_out(): void
    {
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->assertSet('zoomPercent', 100)
            ->call('zoomIn')
            ->assertSet('zoomPercent', 110)
            ->call('zoomOut')
            ->call('zoomOut')
            ->assertSet('zoomPercent', 90);
    }

    public function test_route_pajak_bosp_reguler_bisa_diakses_admin_bosp(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $this->actingAs($adminBosp)
            ->get('/pendataan-bosp/pajak-bosp-reguler')
            ->assertOk();
    }

    public function test_route_pajak_bosp_reguler_bisa_diakses_superadmin(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $this->actingAs($superadmin)
            ->get('/pendataan-bosp/pajak-bosp-reguler')
            ->assertOk();
    }

    public function test_link_pajak_bosp_reguler_muncul_di_sidebar_setelah_identitas_bosp_lengkap(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $this->lengkapiIdentitasBosp($sekolah);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $this->actingAs($adminBosp)
            ->get('/pendataan-bosp')
            ->assertOk()
            ->assertDontSee('Pajak BOSP Reguler');

        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $this->actingAs($adminBosp)
            ->get('/pendataan-bosp')
            ->assertOk()
            ->assertSee('Pajak BOSP Reguler');
    }

    public function test_data_pajak_bosp_reguler_tidak_bercampur_dengan_menu_lain(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        RincianBelanjaModal::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'nama_barang' => 'Data Menu Lain - Rincian Belanja Modal',
        ]);
        PajakBospReguler::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'bulan' => 1,
            'ppn_debit' => 12345,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $baris = Livewire::actingAs($adminBosp)->test(Index::class)->get('baris');

        $this->assertSame('12345', $baris[1]['ppn_debit']);
        $this->assertDatabaseCount('rincian_belanja_modal', 1);
        $this->assertDatabaseCount('pajak_bosp_reguler', 1);
    }
}
