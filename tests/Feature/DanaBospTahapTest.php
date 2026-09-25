<?php

namespace Tests\Feature;

use App\Livewire\PendataanBosp\DanaBospTahap\Index;
use App\Models\DanaBospTahap;
use App\Models\PendataanBosp;
use App\Models\PendataanOps;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menguji menu Dana BOSP Tahap 1 & 2 - Pendataan BOSP (permintaan user
 * 2026-09-16, Part 30): 1 baris per sekolah per tahun (SATU tabel untuk
 * kedua tab, lihat App\Models\DanaBospTahap), input langsung di kotak
 * (tanpa modal, pola PajakBospReguler), rumus Tab 1 (Total Penerimaan
 * Setahun/Tahap 1/Tahap 2 - dibulatkan ke bawah tanpa desimal sesuai
 * jawaban AskUserQuestion), Tab 2 seluruhnya manual, layout Tab 2 beda
 * per role (Admin BOSP: form vertikal 1 sekolah; Superadmin: tabel
 * +/- expand banyak sekolah), baris Jumlah kedua tab, zoom, route &
 * sidebar.
 */
class DanaBospTahapTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bosp_bisa_mengisi_tab1_penerimaan_sekolahnya_sendiri(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.saldo_bosp_tahun_sebelumnya", '5000000')
            ->set("baris.{$sekolah->id}.jumlah_siswa", '200')
            ->set("baris.{$sekolah->id}.jumlah_dana_bosp_per_tahun", '900000')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('dana_bosp_tahap', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'saldo_bosp_tahun_sebelumnya' => 5000000,
            'jumlah_siswa' => 200,
            'jumlah_dana_bosp_per_tahun' => 900000,
            // Total = 200 x 900.000 = 180.000.000, genap -> Tahap 1 = Tahap 2 = 90.000.000.
            'total_penerimaan_setahun' => 180000000,
            'penerimaan_tahap_1' => 90000000,
            'penerimaan_tahap_2' => 90000000,
        ]);
    }

    public function test_rumus_tahap_1_dibulatkan_ke_bawah_saat_total_ganjil(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // Total = 101 x 1 = 101 (ganjil) -> Tahap 1 = intdiv(101,2) = 50,
        // Tahap 2 = 101 - 50 = 51 (jawaban AskUserQuestion 2026-09-16
        // "jangan ada desimal" - TIDAK ADA pecahan sama sekali).
        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.jumlah_siswa", '101')
            ->set("baris.{$sekolah->id}.jumlah_dana_bosp_per_tahun", '1')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('dana_bosp_tahap', [
            'profil_sekolah_id' => $sekolah->id,
            'total_penerimaan_setahun' => 101,
            'penerimaan_tahap_1' => 50,
            'penerimaan_tahap_2' => 51,
        ]);
    }

    public function test_rumus_tab1_terhitung_ulang_walau_dua_field_sumber_diisi_terpisah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        // Isi jumlah_siswa dulu (jumlah_dana_bosp_per_tahun masih kosong -
        // dianggap 0, jadi rumus 0 dulu).
        $component->set("baris.{$sekolah->id}.jumlah_siswa", '150')->assertHasNoErrors();

        $this->assertDatabaseHas('dana_bosp_tahap', [
            'profil_sekolah_id' => $sekolah->id,
            'jumlah_siswa' => 150,
            'total_penerimaan_setahun' => 0,
        ]);

        // Baru isi jumlah_dana_bosp_per_tahun - rumus harus dihitung ulang
        // memakai jumlah_siswa yang SUDAH tersimpan sebelumnya (150), BUKAN
        // dianggap 0 lagi.
        $component->set("baris.{$sekolah->id}.jumlah_dana_bosp_per_tahun", '1000000')->assertHasNoErrors();

        $this->assertDatabaseHas('dana_bosp_tahap', [
            'profil_sekolah_id' => $sekolah->id,
            'jumlah_siswa' => 150,
            'jumlah_dana_bosp_per_tahun' => 1000000,
            'total_penerimaan_setahun' => 150000000,
            'penerimaan_tahap_1' => 75000000,
            'penerimaan_tahap_2' => 75000000,
        ]);
    }

    public function test_field_hasil_rumus_tab1_tidak_bisa_diset_langsung(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // Percobaan set langsung ke 3 kolom hasil rumus Tab 1 - HARUS
        // diabaikan sepenuhnya (tidak ada baris baru dibuat sama sekali,
        // karena tidak ada field manual lain yang ikut diisi).
        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.total_penerimaan_setahun", '999999999')
            ->set("baris.{$sekolah->id}.penerimaan_tahap_1", '999999999')
            ->set("baris.{$sekolah->id}.penerimaan_tahap_2", '999999999');

        $this->assertDatabaseMissing('dana_bosp_tahap', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_admin_bosp_tidak_bisa_mengisi_tab1_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolahLain->id}.jumlah_siswa", '100')
            ->assertForbidden();

        $this->assertDatabaseMissing('dana_bosp_tahap', [
            'profil_sekolah_id' => $sekolahLain->id,
        ]);
    }

    public function test_superadmin_bisa_mengisi_tab1_sekolah_manapun(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.jumlah_siswa", '80')
            ->set("baris.{$sekolah->id}.jumlah_dana_bosp_per_tahun", '1000000')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('dana_bosp_tahap', [
            'profil_sekolah_id' => $sekolah->id,
            'total_penerimaan_setahun' => 80000000,
        ]);
    }

    public function test_input_bukan_angka_tidak_tersimpan_dan_kotak_kembali_kosong(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.jumlah_siswa", 'bukan angka');

        $component->assertHasErrors(["baris.{$sekolah->id}.jumlah_siswa"]);

        $this->assertDatabaseMissing('dana_bosp_tahap', [
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $this->assertSame('', $component->get("baris.{$sekolah->id}.jumlah_siswa"));
    }

    public function test_input_nilai_negatif_ditolak(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.jumlah_siswa", '-5')
            ->assertHasErrors(["baris.{$sekolah->id}.jumlah_siswa"]);

        $this->assertDatabaseMissing('dana_bosp_tahap', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_tab2_tarik_tunai_semua_field_manual_tersimpan_tanpa_rumus(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.saldo_bosp_tw4_tahun_sebelumnya", '1000000')
            ->set("baris.{$sekolah->id}.tarik_tunai_tw1", '20000000')
            ->set("baris.{$sekolah->id}.tarik_tunai_tw2", '25000000')
            ->set("baris.{$sekolah->id}.tarik_tunai_tw3", '30000000')
            ->set("baris.{$sekolah->id}.tarik_tunai_tw4", '35000000')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('dana_bosp_tahap', [
            'profil_sekolah_id' => $sekolah->id,
            'saldo_bosp_tw4_tahun_sebelumnya' => 1000000,
            'tarik_tunai_tw1' => 20000000,
            'tarik_tunai_tw2' => 25000000,
            'tarik_tunai_tw3' => 30000000,
            'tarik_tunai_tw4' => 35000000,
        ]);
    }

    public function test_saldo_tw_dihitung_dari_saldo_kas_bank_dan_saldo_kas_tunai(): void
    {
        // Saldo TW N = Saldo Kas Bank TW N + Saldo Kas Tunai TW N,
        // permintaan user 2026-09-16 (Part 31), untuk TW 1-4.
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        foreach ([1, 2, 3, 4] as $tw) {
            $component
                ->set("baris.{$sekolah->id}.saldo_kas_bank_tw{$tw}", (string) (10000000 * $tw))
                ->set("baris.{$sekolah->id}.saldo_kas_tunai_tw{$tw}", (string) (1000000 * $tw))
                ->assertHasNoErrors();
        }

        $this->assertDatabaseHas('dana_bosp_tahap', [
            'profil_sekolah_id' => $sekolah->id,
            'saldo_kas_bank_tw1' => 10000000,
            'saldo_kas_tunai_tw1' => 1000000,
            'saldo_tw1' => 11000000,
            'saldo_kas_bank_tw2' => 20000000,
            'saldo_kas_tunai_tw2' => 2000000,
            'saldo_tw2' => 22000000,
            'saldo_kas_bank_tw3' => 30000000,
            'saldo_kas_tunai_tw3' => 3000000,
            'saldo_tw3' => 33000000,
            'saldo_kas_bank_tw4' => 40000000,
            'saldo_kas_tunai_tw4' => 4000000,
            'saldo_tw4' => 44000000,
        ]);
    }

    public function test_saldo_tw_terhitung_ulang_walau_kas_bank_dan_kas_tunai_diisi_terpisah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        // Isi Saldo Kas Bank TW 1 dulu (Kas Tunai masih kosong -> dianggap
        // 0, jadi Saldo TW 1 = kas bank saja dulu).
        $component->set("baris.{$sekolah->id}.saldo_kas_bank_tw1", '7000000')->assertHasNoErrors();

        $this->assertDatabaseHas('dana_bosp_tahap', [
            'profil_sekolah_id' => $sekolah->id,
            'saldo_kas_bank_tw1' => 7000000,
            'saldo_tw1' => 7000000,
        ]);

        // Baru isi Saldo Kas Tunai TW 1 - Saldo TW 1 harus dihitung ulang
        // memakai Saldo Kas Bank TW 1 yang SUDAH tersimpan sebelumnya
        // (7.000.000), BUKAN dianggap 0 lagi.
        $component->set("baris.{$sekolah->id}.saldo_kas_tunai_tw1", '2500000')->assertHasNoErrors();

        $this->assertDatabaseHas('dana_bosp_tahap', [
            'profil_sekolah_id' => $sekolah->id,
            'saldo_kas_bank_tw1' => 7000000,
            'saldo_kas_tunai_tw1' => 2500000,
            'saldo_tw1' => 9500000,
        ]);
    }

    public function test_field_hasil_rumus_saldo_tw_tidak_bisa_diset_langsung(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // Percobaan set langsung ke 4 kolom hasil rumus Saldo TW - HARUS
        // diabaikan sepenuhnya (tidak ada baris baru dibuat sama sekali,
        // karena tidak ada field manual lain yang ikut diisi).
        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.saldo_tw1", '999999999')
            ->set("baris.{$sekolah->id}.saldo_tw2", '999999999')
            ->set("baris.{$sekolah->id}.saldo_tw3", '999999999')
            ->set("baris.{$sekolah->id}.saldo_tw4", '999999999');

        $this->assertDatabaseMissing('dana_bosp_tahap', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_pindah_tab_hanya_menerima_dua_nilai_valid(): void
    {
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->assertSet('tabAktif', 'penerimaan')
            ->call('pindahTab', 'tarik_tunai')
            ->assertSet('tabAktif', 'tarik_tunai')
            ->call('pindahTab', 'tab-tidak-dikenal')
            ->assertSet('tabAktif', 'tarik_tunai');
    }

    public function test_admin_bosp_hanya_melihat_sekolahnya_sendiri(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        $daftarSekolah = $component->viewData('daftarSekolah');

        $this->assertCount(1, $daftarSekolah);
        $this->assertSame($sekolahSaya->id, $daftarSekolah->first()->id);
        $this->assertFalse($component->viewData('bolehKelolaSemua'));
    }

    public function test_superadmin_melihat_seluruh_sekolah(): void
    {
        ProfilSekolah::factory()->count(3)->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertCount(3, $component->viewData('daftarSekolah'));
        $this->assertTrue($component->viewData('bolehKelolaSemua'));
    }

    public function test_data_terisolasi_per_tahun(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year - 1,
            'jumlah_siswa' => 999,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        // Tahun berjalan (default) belum ada data tahun lalu.
        $this->assertSame('', $component->get("baris.{$sekolah->id}.jumlah_siswa"));

        $component->set('tahun', now()->year - 1);
        $this->assertSame('999', $component->get("baris.{$sekolah->id}.jumlah_siswa"));
    }

    public function test_baris_jumlah_tab1_menjumlahkan_seluruh_sekolah_kecuali_dana_per_tahun(): void
    {
        $sekolahA = ProfilSekolah::factory()->create();
        $sekolahB = ProfilSekolah::factory()->create();
        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolahA->id,
            'tahun' => now()->year,
            'saldo_bosp_tahun_sebelumnya' => 1000000,
            'jumlah_siswa' => 100,
            'jumlah_dana_bosp_per_tahun' => 900000,
            'total_penerimaan_setahun' => 90000000,
            'penerimaan_tahap_1' => 45000000,
            'penerimaan_tahap_2' => 45000000,
        ]);
        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolahB->id,
            'tahun' => now()->year,
            'saldo_bosp_tahun_sebelumnya' => 2000000,
            'jumlah_siswa' => 50,
            'jumlah_dana_bosp_per_tahun' => 900000,
            'total_penerimaan_setahun' => 45000000,
            'penerimaan_tahap_1' => 22500000,
            'penerimaan_tahap_2' => 22500000,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);
        $totalTab1 = $component->viewData('totalTab1');

        $this->assertSame(3000000, $totalTab1['saldo_bosp_tahun_sebelumnya']);
        $this->assertSame(150, $totalTab1['jumlah_siswa']);
        $this->assertSame(135000000, $totalTab1['total_penerimaan_setahun']);
        $this->assertSame(67500000, $totalTab1['penerimaan_tahap_1']);
        $this->assertSame(67500000, $totalTab1['penerimaan_tahap_2']);
        // "jumlah_dana_bosp_per_tahun" (angka per-siswa/tahun) SENGAJA
        // tidak ada di baris Jumlah - bukan kuantitas yang bisa dijumlahkan.
        $this->assertArrayNotHasKey('jumlah_dana_bosp_per_tahun', $totalTab1);
    }

    public function test_baris_jumlah_tab2_menjumlahkan_seluruh_sekolah(): void
    {
        $sekolahA = ProfilSekolah::factory()->create();
        $sekolahB = ProfilSekolah::factory()->create();
        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolahA->id,
            'tahun' => now()->year,
            'tarik_tunai_tw1' => 10000000,
            'tarik_tunai_tw2' => 10000000,
            'tarik_tunai_tw3' => 10000000,
            'tarik_tunai_tw4' => 10000000,
            'saldo_kas_bank_tw1' => 6000000,
            'saldo_kas_tunai_tw1' => 1000000,
            'saldo_tw1' => 7000000,
        ]);
        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolahB->id,
            'tahun' => now()->year,
            'tarik_tunai_tw1' => 5000000,
            'tarik_tunai_tw2' => 5000000,
            'tarik_tunai_tw3' => 5000000,
            'tarik_tunai_tw4' => 5000000,
            'saldo_kas_bank_tw1' => 4000000,
            'saldo_kas_tunai_tw1' => 500000,
            'saldo_tw1' => 4500000,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);
        $totalTab2 = $component->viewData('totalTab2');

        $this->assertSame(15000000, $totalTab2['tarik_tunai_tw1']);
        $this->assertSame(15000000, $totalTab2['tarik_tunai_tw2']);
        $this->assertSame(15000000, $totalTab2['tarik_tunai_tw3']);
        $this->assertSame(15000000, $totalTab2['tarik_tunai_tw4']);
        // Saldo Kas Bank/Tunai/Saldo TW 1 (Part 31) juga ikut dijumlahkan.
        $this->assertSame(10000000, $totalTab2['saldo_kas_bank_tw1']);
        $this->assertSame(1500000, $totalTab2['saldo_kas_tunai_tw1']);
        $this->assertSame(11500000, $totalTab2['saldo_tw1']);
    }

    public function test_zoom_in_dan_zoom_out_mengubah_persentase_zoom(): void
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

    public function test_link_dana_bosp_tahap_muncul_di_sidebar_sesudah_rekap_rkas(): void
    {
        $sekolah = ProfilSekolah::factory()->create([
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
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        // Link menu ini hanya muncul sesudah Identitas Admin BOSP lengkap
        // (sama seperti pola Rekap RKAS - lihat navigation.blade.php).
        $response = $this->actingAs($adminBosp)->get('/pendataan-bosp');

        $response->assertOk()->assertSee('Dana BOSP Tahap 1 & 2');

        // Dicari tanpa karakter "&" (Blade meng-escape jadi "&amp;" pada
        // HTML sungguhan - lihat catatan bug serupa di test Part 29) supaya
        // pencarian posisi teksnya tidak ikut gagal karena escaping.
        //
        // Urutan sidebar DIBALIK sesuai permintaan user 2026-09-23: "Dana
        // BOSP Tahap 1 & 2" sekarang tampil LANGSUNG SESUDAH "Identitas
        // Admin BOSP", MENDAHULUI "Rekap RKAS Awal-Perubahan" (sebelumnya
        // urutannya terbalik).
        $html = $response->getContent();
        $posisiDanaBosp = strpos($html, 'Dana BOSP Tahap 1');
        $posisiRekapRkas = strpos($html, 'Rekap RKAS Awal-Perubahan');

        $this->assertNotFalse($posisiDanaBosp);
        $this->assertNotFalse($posisiRekapRkas);
        $this->assertGreaterThan($posisiDanaBosp, $posisiRekapRkas);
    }

    private function buatSekolahLengkap(): ProfilSekolah
    {
        return ProfilSekolah::factory()->create([
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

    public function test_halaman_dana_bosp_tahap_bisa_diakses(): void
    {
        // Onboarding (Profil Sekolah lengkap + Identitas Admin BOSP sudah
        // diisi) WAJIB dilengkapi dulu - kalau tidak, middleware
        // EnsureOnboardingComplete akan redirect (302) ke menu Identitas
        // Admin BOSP sebelum sempat mencapai halaman ini.
        $sekolah = $this->buatSekolahLengkap();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $this->actingAs($adminBosp)
            ->get(route('pendataan-bosp.dana-bosp-tahap'))
            ->assertOk()
            ->assertSeeLivewire(Index::class);
    }

    public function test_admin_ops_tidak_bisa_mengakses_menu_ini(): void
    {
        // Onboarding Admin OPS (Profil Sekolah lengkap + Identitas OPS
        // sudah diisi) juga dilengkapi dulu, supaya 403 yang diuji di sini
        // BENAR-BENAR berasal dari gate "akses-pendataan-bosp" (Admin OPS
        // memang tidak diizinkan), bukan cuma keburu redirect onboarding.
        $sekolah = $this->buatSekolahLengkap();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanOps::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $this->actingAs($adminOps)
            ->get(route('pendataan-bosp.dana-bosp-tahap'))
            ->assertForbidden();
    }
}
