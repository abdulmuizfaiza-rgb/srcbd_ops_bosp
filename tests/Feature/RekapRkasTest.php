<?php

namespace Tests\Feature;

use App\Livewire\PendataanBosp\DanaBospTahap\Index as DanaBospTahapIndex;
use App\Livewire\PendataanBosp\RekapRkas\Index;
use App\Models\DanaBospTahap;
use App\Models\PendataanBosp;
use App\Models\ProfilSekolah;
use App\Models\RekapRkas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RekapRkasTest extends TestCase
{
    use RefreshDatabase;

    protected function dataAngka(array $overrides = []): array
    {
        $data = [];

        foreach (array_keys(RekapRkas::KATEGORI) as $kategori) {
            $data[$kategori.'_sebelum'] = '10000000';
            $data[$kategori.'_realisasi_tahap1'] = '4000000';
            $data[$kategori.'_perubahan_tahap2'] = '6000000';
            $data[$kategori.'_jml_sesudah'] = '10000000';
            $data[$kategori.'_selisih'] = '0';
        }

        $data['jumlah_sebelum'] = '50000000';
        $data['jumlah_sesudah'] = '50000000';
        $data['jumlah_selisih'] = '0';

        return array_merge($data, $overrides);
    }

    /**
     * Membuat baris Dana BOSP Tahap - Penerimaan BOSP yang SUDAH terisi
     * (jumlah_siswa & jumlah_dana_bosp_per_tahun) untuk sekolah+tahun
     * tertentu - dipakai bareng oleh test-test yang butuh melewati gate
     * baru pada RekapRkas\Index (permintaan user 2026-09-23: Rekap RKAS
     * terkunci sampai Dana BOSP Tahap - Penerimaan BOSP diisi dulu).
     *
     * $totalPenerimaan sengaja dijadikan jumlah_dana_bosp_per_tahun dengan
     * jumlah_siswa = 1, supaya total_penerimaan_setahun = $totalPenerimaan
     * persis (memudahkan angka pada assertion tetap bulat & gampang dibaca).
     */
    protected function buatDanaBospTahapTerisi(ProfilSekolah $sekolah, ?int $tahun = null, int $totalPenerimaan = 500000000): DanaBospTahap
    {
        [$total, $tahap1, $tahap2] = DanaBospTahap::hitungRumusTab1(1, $totalPenerimaan);

        return DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => $tahun ?? now()->year,
            'jumlah_siswa' => 1,
            'jumlah_dana_bosp_per_tahun' => $totalPenerimaan,
            'total_penerimaan_setahun' => $total,
            'penerimaan_tahap_1' => $tahap1,
            'penerimaan_tahap_2' => $tahap2,
        ]);
    }

    public function test_admin_bosp_bisa_mengisi_rekap_rkas_sekolahnya_sendiri(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $this->buatDanaBospTahapTerisi($sekolah, totalPenerimaan: 500000000);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class)->call('isi', $sekolah->id);

        foreach ($this->dataAngka() as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseHas('rekap_rkas', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            // Anggaran BOSP OTOMATIS (permintaan user 2026-09-23) - ikut
            // Total Penerimaan BOSP Setahun Dana BOSP Tahap di atas, BUKAN
            // input manual lagi.
            'anggaran_bosp' => 500000000,
        ]);
    }

    public function test_admin_bosp_tidak_bisa_mengisi_rekap_rkas_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('isi', $sekolahLain->id)
            ->assertForbidden();
    }

    public function test_superadmin_bisa_mengedit_rekap_rkas_sekolah_manapun(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $this->buatDanaBospTahapTerisi($sekolah, totalPenerimaan: 999000000);
        RekapRkas::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'anggaran_bosp' => 999000000,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('isi', $sekolah->id);

        foreach ($this->dataAngka() as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseHas('rekap_rkas', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'anggaran_bosp' => 999000000,
        ]);

        $this->assertDatabaseCount('rekap_rkas', 1);
    }

    public function test_admin_bosp_hanya_melihat_sekolahnya_sendiri(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak Saya']);
        $sekolahLain = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak Lain']);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);
        // Identitas Admin BOSP wajib sudah diisi dulu, kalau belum
        // EnsureOnboardingComplete akan mengalihkan ke pendataan-bosp.index.
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolahSaya->id]);

        $this->actingAs($adminBosp)
            ->get('/pendataan-bosp/rekap-rkas')
            ->assertOk()
            ->assertSee('SR Cibadak Saya')
            ->assertDontSee('SR Cibadak Lain');
    }

    public function test_data_rekap_rkas_terisolasi_per_tahun(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $this->buatDanaBospTahapTerisi($sekolah, now()->year, 999000000);
        $this->buatDanaBospTahapTerisi($sekolah, now()->year - 1, 111000000);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        RekapRkas::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year - 1,
            'anggaran_bosp' => 111000000,
            'pegawai_sebelum' => 5000000,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        // Tahun berjalan (default): belum ada baris Rekap RKAS untuk tahun
        // ini, tapi Anggaran BOSP tetap tampil OTOMATIS (langsung dari
        // Dana BOSP Tahap tahun ini) - field lain (mis. pegawai_sebelum)
        // masih kosong karena memang belum pernah diisi.
        $component->call('isi', $sekolah->id);
        $this->assertSame('999000000', $component->get('anggaran_bosp'));
        $this->assertSame('', $component->get('pegawai_sebelum'));
        $component->call('batal');

        // Pindah ke tahun sebelumnya: data yang sudah ada untuk tahun itu muncul.
        $component->set('tahun', now()->year - 1);
        $component->call('isi', $sekolah->id);
        $this->assertSame('111000000', $component->get('anggaran_bosp'));
        $this->assertSame('5000000', $component->get('pegawai_sebelum'));

        // Menyimpan di tahun sebelumnya tidak mengubah/menimpa data tahun berjalan.
        $component->set('pegawai_sebelum', '222000000');
        $component->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseHas('rekap_rkas', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year - 1,
            'pegawai_sebelum' => 222000000,
            'anggaran_bosp' => 111000000,
        ]);
        $this->assertDatabaseMissing('rekap_rkas', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
        ]);
        $this->assertDatabaseCount('rekap_rkas', 1);
    }

    public function test_rekap_rkas_wajib_angka_bulat(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $this->buatDanaBospTahapTerisi($sekolah);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('isi', $sekolah->id)
            ->set('pegawai_sebelum', 'bukan angka')
            ->call('simpan')
            ->assertHasErrors(['pegawai_sebelum']);
    }

    public function test_admin_bosp_bisa_input_langsung_di_kotak_tabel_tersimpan_otomatis(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $this->buatDanaBospTahapTerisi($sekolah);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // Simulasikan kotak "pegawai_sebelum" di tabel kehilangan fokus
        // (wire:model.blur) - harus langsung tersimpan tanpa lewat modal
        // Isi/Edit maupun tombol Simpan terpisah.
        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.pegawai_sebelum", '777000000')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('rekap_rkas', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'pegawai_sebelum' => 777000000,
        ]);
    }

    public function test_admin_bosp_tidak_bisa_input_langsung_di_kotak_tabel_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolahLain->id}.pegawai_sebelum", '777000000')
            ->assertForbidden();

        $this->assertDatabaseMissing('rekap_rkas', [
            'profil_sekolah_id' => $sekolahLain->id,
        ]);
    }

    public function test_input_langsung_di_kotak_tabel_yang_bukan_angka_tidak_tersimpan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $this->buatDanaBospTahapTerisi($sekolah);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.pegawai_sebelum", 'bukan angka');

        $component->assertHasErrors(["baris.{$sekolah->id}.pegawai_sebelum"]);

        $this->assertDatabaseMissing('rekap_rkas', [
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // Kotaknya kembali ke nilai semula (kosong) pada render berikutnya.
        $this->assertSame('', $component->get("baris.{$sekolah->id}.pegawai_sebelum"));
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

    public function test_link_rekap_rkas_muncul_di_sidebar_setelah_identitas_bosp_lengkap(): void
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

        // Sebelum Identitas Admin BOSP diisi: link Rekap RKAS belum muncul.
        $this->actingAs($adminBosp)
            ->get('/pendataan-bosp')
            ->assertOk()
            ->assertDontSee('Rekap RKAS Awal-Perubahan');

        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        // Setelah Identitas Admin BOSP diisi: link Rekap RKAS muncul.
        $this->actingAs($adminBosp)
            ->get('/pendataan-bosp')
            ->assertOk()
            ->assertSee('Rekap RKAS Awal-Perubahan');
    }

    public function test_baris_jumlah_menjumlahkan_seluruh_sekolah_untuk_superadmin(): void
    {
        $sekolahA = ProfilSekolah::factory()->create();
        $sekolahB = ProfilSekolah::factory()->create();
        $this->buatDanaBospTahapTerisi($sekolahA, totalPenerimaan: 100000000);
        $this->buatDanaBospTahapTerisi($sekolahB, totalPenerimaan: 250000000);
        RekapRkas::factory()->create([
            'profil_sekolah_id' => $sekolahA->id,
            'tahun' => now()->year,
            'pegawai_sebelum' => 5000000,
        ]);
        RekapRkas::factory()->create([
            'profil_sekolah_id' => $sekolahB->id,
            'tahun' => now()->year,
            'pegawai_sebelum' => 7000000,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->assertViewHas('totalBaris', function ($total) {
                // anggaran_bosp OTOMATIS - sum dari Dana BOSP Tahap kedua
                // sekolah (100jt + 250jt), BUKAN dari kolom
                // rekap_rkas.anggaran_bosp yang tidak diisi manual lagi.
                return $total['anggaran_bosp'] === 350000000
                    && $total['pegawai_sebelum'] === 12000000;
            })
            ->assertSee('Jumlah');
    }

    public function test_baris_jumlah_admin_bosp_hanya_menghitung_sekolahnya_sendiri(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $this->buatDanaBospTahapTerisi($sekolahSaya, totalPenerimaan: 120000000);
        $this->buatDanaBospTahapTerisi($sekolahLain, totalPenerimaan: 900000000);
        RekapRkas::factory()->create([
            'profil_sekolah_id' => $sekolahSaya->id,
            'tahun' => now()->year,
        ]);
        RekapRkas::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'tahun' => now()->year,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->assertViewHas('totalBaris', function ($total) {
                return $total['anggaran_bosp'] === 120000000;
            });
    }

    public function test_baris_jumlah_anggaran_bosp_otomatis_ikut_terupdate_setelah_dana_bosp_tahap_diisi(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // Belum ada Dana BOSP Tahap sama sekali - Anggaran BOSP & baris
        // Jumlah harus 0 (bukan error/null), bukan lewat kotak Rekap RKAS
        // (kolom itu sudah bukan input lagi).
        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->assertViewHas('totalBaris', fn ($total) => $total['anggaran_bosp'] === 0);

        // Isi Dana BOSP Tahap - Penerimaan BOSP sekolah ini lewat komponen
        // aslinya (DanaBospTahap\Index), BUKAN lewat Rekap RKAS.
        Livewire::actingAs($adminBosp)
            ->test(DanaBospTahapIndex::class)
            ->set("baris.{$sekolah->id}.jumlah_siswa", '100')
            ->set("baris.{$sekolah->id}.jumlah_dana_bosp_per_tahun", '4440000');

        // Pada render RekapRkas berikutnya, Anggaran BOSP & baris Jumlah
        // harus otomatis ikut ter-update (100 x 4.440.000 = 444.000.000),
        // walau Rekap RKAS sekolah ini sendiri belum pernah disentuh.
        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->assertViewHas('totalBaris', fn ($total) => $total['anggaran_bosp'] === 444000000);
    }

    public function test_rumus_jml_sesudah_dan_selisih_terhitung_saat_input_langsung_di_tabel(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $this->buatDanaBospTahapTerisi($sekolah);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        // Isi Sebelum, lalu Realisasi Tahap 1, lalu Perubahan Tahap 2 satu
        // per satu (persis seperti mengetik & pindah kotak di browser) -
        // Jml Sesudah & Selisih harus ikut terhitung ulang tiap kali,
        // memakai nilai kolom lain yang SUDAH tersimpan di database.
        $component->set("baris.{$sekolah->id}.pegawai_sebelum", '10000000');
        $component->set("baris.{$sekolah->id}.pegawai_realisasi_tahap1", '4000000');
        $component->set("baris.{$sekolah->id}.pegawai_perubahan_tahap2", '6000000');

        $this->assertDatabaseHas('rekap_rkas', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'pegawai_sebelum' => 10000000,
            'pegawai_realisasi_tahap1' => 4000000,
            'pegawai_perubahan_tahap2' => 6000000,
            'pegawai_jml_sesudah' => 10000000, // 4jt + 6jt
            'pegawai_selisih' => 0, // 10jt - 10jt
        ]);
    }

    public function test_rumus_menganggap_nilai_kosong_sebagai_nol(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $this->buatDanaBospTahapTerisi($sekolah);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // Hanya isi Sebelum - Realisasi & Perubahan masih kosong (belum
        // pernah diisi sama sekali) - Jml Sesudah harus 0 (bukan null/
        // error), Selisih harus sama dengan Sebelum (10jt - 0).
        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.pemeliharaan_sebelum", '10000000');

        $this->assertDatabaseHas('rekap_rkas', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'pemeliharaan_sebelum' => 10000000,
            'pemeliharaan_jml_sesudah' => 0,
            'pemeliharaan_selisih' => 10000000,
        ]);
    }

    public function test_kolom_jml_sesudah_dan_selisih_yang_sudah_ada_rumus_tidak_bisa_diset_langsung(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $this->buatDanaBospTahapTerisi($sekolah);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // Percobaan set langsung ke kolom Jml Sesudah/Selisih (kolom ini
        // sudah tidak punya kotak input lagi di UI, tapi dijaga juga di
        // server) - HARUS diabaikan, tidak tersimpan sama sekali.
        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.barjas_jml_sesudah", '999000000')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('rekap_rkas', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_kategori_aset_lainnya_juga_mengikuti_rumus(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $this->buatDanaBospTahapTerisi($sekolah);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // Aset Lainnya sekarang juga sudah punya rumus (2026-09-09 lanjutan
        // ke-5) - Jml Sesudah & Selisih HARUS ikut dihitung otomatis persis
        // seperti 4 kategori lainnya, TIDAK bisa lagi diisi manual.
        $component = Livewire::actingAs($adminBosp)->test(Index::class);
        $component->set("baris.{$sekolah->id}.aset_lainnya_sebelum", '10000000');
        $component->set("baris.{$sekolah->id}.aset_lainnya_realisasi_tahap1", '4000000');
        $component->set("baris.{$sekolah->id}.aset_lainnya_perubahan_tahap2", '6000000');

        $this->assertDatabaseHas('rekap_rkas', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'aset_lainnya_sebelum' => 10000000,
            'aset_lainnya_realisasi_tahap1' => 4000000,
            'aset_lainnya_perubahan_tahap2' => 6000000,
            'aset_lainnya_jml_sesudah' => 10000000, // 4jt + 6jt
            'aset_lainnya_selisih' => 0, // 10jt - 10jt
        ]);

        // Kolom Jml Sesudah/Selisih-nya juga sudah tidak bisa diset
        // langsung (sama seperti kategori lain).
        $component->set("baris.{$sekolah->id}.aset_lainnya_jml_sesudah", '999000000')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('rekap_rkas', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'aset_lainnya_jml_sesudah' => 10000000, // tidak berubah, tetap hasil rumus
        ]);
    }

    public function test_baris_jumlah_kolom_ikut_terhitung_saat_input_langsung_di_tabel(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $this->buatDanaBospTahapTerisi($sekolah);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // Sebelum ada input apapun: baris JUMLAH (kolom) untuk sekolah ini
        // harus 0 (belum ada rekap sama sekali).
        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        // Isi Sebelum + Realisasi + Perubahan untuk kategori Pegawai saja -
        // baris JUMLAH (kolom Sebelum/Sesudah/Selisih) harus ikut terhitung
        // dari kategori ini walau 4 kategori lain masih kosong (dianggap 0).
        $component->set("baris.{$sekolah->id}.pegawai_sebelum", '10000000');
        $component->set("baris.{$sekolah->id}.pegawai_realisasi_tahap1", '4000000');
        $component->set("baris.{$sekolah->id}.pegawai_perubahan_tahap2", '6000000');

        $this->assertDatabaseHas('rekap_rkas', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'jumlah_sebelum' => 10000000, // 10jt + 0 + 0 + 0 + 0
            'jumlah_sesudah' => 10000000, // 10jt + 0 + 0 + 0 + 0
            'jumlah_selisih' => 0, // 10jt - 10jt
        ]);

        // Tambah kategori Pemeliharaan - baris JUMLAH (kolom) harus ikut
        // bertambah, memakai nilai Pegawai yang SUDAH tersimpan sebelumnya.
        $component->set("baris.{$sekolah->id}.pemeliharaan_sebelum", '20000000');
        $component->set("baris.{$sekolah->id}.pemeliharaan_realisasi_tahap1", '8000000');
        $component->set("baris.{$sekolah->id}.pemeliharaan_perubahan_tahap2", '5000000');

        $this->assertDatabaseHas('rekap_rkas', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'pemeliharaan_jml_sesudah' => 13000000, // 8jt + 5jt
            'pemeliharaan_selisih' => 7000000, // 20jt - 13jt
            'jumlah_sebelum' => 30000000, // 10jt + 20jt
            'jumlah_sesudah' => 23000000, // 10jt + 13jt
            'jumlah_selisih' => 7000000, // 30jt - 23jt
        ]);

        // Kolom baris JUMLAH juga sudah tidak bisa diset langsung.
        $component->set("baris.{$sekolah->id}.jumlah_sebelum", '1')->assertHasNoErrors();

        $this->assertDatabaseHas('rekap_rkas', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'jumlah_sebelum' => 30000000, // tidak berubah, tetap hasil rumus
        ]);
    }

    public function test_baris_jumlah_kolom_terhitung_saat_simpan_lewat_modal_isi_edit(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $this->buatDanaBospTahapTerisi($sekolah);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class)->call('isi', $sekolah->id);

        foreach ($this->dataAngka([
            'pegawai_sebelum' => '10000000',
            'pegawai_realisasi_tahap1' => '4000000',
            'pegawai_perubahan_tahap2' => '6000000',
            'pemeliharaan_sebelum' => '20000000',
            'pemeliharaan_realisasi_tahap1' => '8000000',
            'pemeliharaan_perubahan_tahap2' => '5000000',
            'barjas_sebelum' => '15000000',
            'barjas_realisasi_tahap1' => '6000000',
            'barjas_perubahan_tahap2' => '6000000',
            'peralatan_mesin_sebelum' => '25000000',
            'peralatan_mesin_realisasi_tahap1' => '10000000',
            'peralatan_mesin_perubahan_tahap2' => '10000000',
            'aset_lainnya_sebelum' => '30000000',
            'aset_lainnya_realisasi_tahap1' => '12000000',
            'aset_lainnya_perubahan_tahap2' => '8000000',
            // Nilai ini SENGAJA diisi salah - harus ditimpa hasil hitung
            // yang benar oleh simpan(), BUKAN tersimpan apa adanya.
            'jumlah_sebelum' => '1',
            'jumlah_sesudah' => '2',
            'jumlah_selisih' => '3',
        ]) as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseHas('rekap_rkas', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'jumlah_sebelum' => 100000000, // 10+20+15+25+30 jt
            'jumlah_sesudah' => 75000000, // (4+6)+(8+5)+(6+6)+(10+10)+(12+8) jt
            'jumlah_selisih' => 25000000, // 100jt - 75jt
        ]);
    }

    public function test_rumus_terhitung_saat_simpan_lewat_modal_isi_edit(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $this->buatDanaBospTahapTerisi($sekolah);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class)->call('isi', $sekolah->id);

        foreach ($this->dataAngka([
            'peralatan_mesin_sebelum' => '20000000',
            'peralatan_mesin_realisasi_tahap1' => '5000000',
            'peralatan_mesin_perubahan_tahap2' => '9000000',
            // Nilai ini SENGAJA diisi salah/berbeda dari rumus - harus
            // ditimpa hasil hitung yang benar oleh simpan(), BUKAN
            // tersimpan apa adanya seperti yang diketik di sini.
            'peralatan_mesin_jml_sesudah' => '1',
            'peralatan_mesin_selisih' => '2',
        ]) as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseHas('rekap_rkas', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'peralatan_mesin_sebelum' => 20000000,
            'peralatan_mesin_realisasi_tahap1' => 5000000,
            'peralatan_mesin_perubahan_tahap2' => 9000000,
            'peralatan_mesin_jml_sesudah' => 14000000, // 5jt + 9jt
            'peralatan_mesin_selisih' => 6000000, // 20jt - 14jt
        ]);
    }

    public function test_migration_backfill_menghitung_ulang_data_lama(): void
    {
        $sekolah = ProfilSekolah::factory()->create();

        // Data lama gaya sebelum rumus ditentukan - Jml Sesudah & Selisih
        // tiap kategori, serta baris JUMLAH, diisi manual dengan nilai yang
        // TIDAK sesuai rumus baru (untuk SEMUA 5 kategori - rumusnya
        // sekarang berlaku untuk semuanya, lihat KATEGORI_RUMUS).
        RekapRkas::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'pegawai_sebelum' => 10000000,
            'pegawai_realisasi_tahap1' => 4000000,
            'pegawai_perubahan_tahap2' => 6000000,
            'pegawai_jml_sesudah' => 1, // manual lama, tidak sesuai rumus
            'pegawai_selisih' => 2, // manual lama, tidak sesuai rumus
            'pemeliharaan_sebelum' => 20000000,
            'pemeliharaan_realisasi_tahap1' => 8000000,
            'pemeliharaan_perubahan_tahap2' => 5000000,
            'pemeliharaan_jml_sesudah' => 1,
            'pemeliharaan_selisih' => 2,
            'barjas_sebelum' => 15000000,
            'barjas_realisasi_tahap1' => 6000000,
            'barjas_perubahan_tahap2' => 6000000,
            'barjas_jml_sesudah' => 1,
            'barjas_selisih' => 2,
            'peralatan_mesin_sebelum' => 25000000,
            'peralatan_mesin_realisasi_tahap1' => 10000000,
            'peralatan_mesin_perubahan_tahap2' => 10000000,
            'peralatan_mesin_jml_sesudah' => 1,
            'peralatan_mesin_selisih' => 2,
            'aset_lainnya_sebelum' => 30000000,
            'aset_lainnya_realisasi_tahap1' => 12000000,
            'aset_lainnya_perubahan_tahap2' => 8000000,
            'aset_lainnya_jml_sesudah' => 777000000, // manual lama, tidak sesuai rumus
            'aset_lainnya_selisih' => 123000000, // manual lama, tidak sesuai rumus
            'jumlah_sebelum' => 999999999, // manual lama, tidak sesuai rumus
            'jumlah_sesudah' => 999999999, // manual lama, tidak sesuai rumus
            'jumlah_selisih' => 999999999, // manual lama, tidak sesuai rumus
        ]);

        // RefreshDatabase sudah menjalankan SELURUH migration (termasuk
        // migration backfill ini) sebelum data di atas dibuat, jadi
        // artisan migrate biasa tidak akan menjalankannya ulang (statusnya
        // sudah tercatat "Ran") - panggil langsung up()-nya di sini supaya
        // logic backfill-nya benar-benar teruji terhadap data di atas.
        (require database_path('migrations/2026_09_09_214122_backfill_rumus_jml_sesudah_dan_selisih_rekap_rkas_table.php'))->up();

        $this->assertDatabaseHas('rekap_rkas', [
            'profil_sekolah_id' => $sekolah->id,
            'pegawai_jml_sesudah' => 10000000, // 4jt + 6jt, dihitung ulang
            'pegawai_selisih' => 0, // 10jt - 10jt, dihitung ulang
            'pemeliharaan_jml_sesudah' => 13000000, // 8jt + 5jt
            'pemeliharaan_selisih' => 7000000, // 20jt - 13jt
            'barjas_jml_sesudah' => 12000000, // 6jt + 6jt
            'barjas_selisih' => 3000000, // 15jt - 12jt
            'peralatan_mesin_jml_sesudah' => 20000000, // 10jt + 10jt
            'peralatan_mesin_selisih' => 5000000, // 25jt - 20jt
            'aset_lainnya_jml_sesudah' => 20000000, // 12jt + 8jt, dihitung ulang (sekarang ikut rumus)
            'aset_lainnya_selisih' => 10000000, // 30jt - 20jt, dihitung ulang
            'jumlah_sebelum' => 100000000, // 10+20+15+25+30 jt, dihitung ulang
            'jumlah_sesudah' => 75000000, // 10+13+12+20+20 jt, dihitung ulang
            'jumlah_selisih' => 25000000, // 100jt - 75jt, dihitung ulang
        ]);
    }

    public function test_migration_backfill_anggaran_bosp_otomatis_mengikuti_dana_bosp_tahap(): void
    {
        $sekolahAda = ProfilSekolah::factory()->create();
        $sekolahBelum = ProfilSekolah::factory()->create();

        // Sekolah A: sudah punya Dana BOSP Tahap - anggaran_bosp lama
        // (manual/basi) harus ditimpa mengikuti total_penerimaan_setahun.
        $this->buatDanaBospTahapTerisi($sekolahAda, totalPenerimaan: 321000000);
        RekapRkas::factory()->create([
            'profil_sekolah_id' => $sekolahAda->id,
            'tahun' => now()->year,
            'anggaran_bosp' => 1, // manual lama/basi, tidak sesuai Dana BOSP Tahap
        ]);

        // Sekolah B: belum ada Dana BOSP Tahap sama sekali untuk tahun ini -
        // anggaran_bosp harus ditimpa jadi null.
        RekapRkas::factory()->create([
            'profil_sekolah_id' => $sekolahBelum->id,
            'tahun' => now()->year,
            'anggaran_bosp' => 999000000, // manual lama/basi
        ]);

        (require database_path('migrations/2026_09_23_010000_backfill_anggaran_bosp_otomatis_rekap_rkas.php'))->up();

        $this->assertDatabaseHas('rekap_rkas', [
            'profil_sekolah_id' => $sekolahAda->id,
            'anggaran_bosp' => 321000000,
        ]);
        $this->assertDatabaseHas('rekap_rkas', [
            'profil_sekolah_id' => $sekolahBelum->id,
            'anggaran_bosp' => null,
        ]);
    }

    public function test_rekap_rkas_terkunci_sampai_dana_bosp_tahap_penerimaan_diisi(): void
    {
        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak Terkunci']);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        // Identitas Admin BOSP wajib sudah diisi dulu, kalau belum
        // EnsureOnboardingComplete akan mengalihkan ke pendataan-bosp.index.
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        // Belum ada Dana BOSP Tahap - Penerimaan BOSP sama sekali - tabel
        // harus menampilkan pesan terkunci & link ke Dana BOSP Tahap,
        // BUKAN kotak input.
        $this->actingAs($adminBosp)
            ->get('/pendataan-bosp/rekap-rkas')
            ->assertOk()
            ->assertSee('belum mengisi')
            ->assertSee('Isi Dana BOSP Tahap');

        // isi() tidak boleh membuka form modal selama masih terkunci.
        $component = Livewire::actingAs($adminBosp)->test(Index::class)->call('isi', $sekolah->id);
        $component->assertSet('showForm', false);

        // Percobaan input langsung di kotak tabel juga harus ditolak diam-
        // diam (tidak tersimpan) selama masih terkunci.
        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.pegawai_sebelum", '10000000')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('rekap_rkas', ['profil_sekolah_id' => $sekolah->id]);

        // Setelah Dana BOSP Tahap - Penerimaan BOSP diisi: baris terbuka
        // normal, isi()/simpan() berfungsi seperti biasa.
        $this->buatDanaBospTahapTerisi($sekolah, totalPenerimaan: 250000000);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('isi', $sekolah->id)
            ->assertSet('showForm', true)
            ->assertSet('anggaran_bosp', '250000000');
    }

    public function test_superadmin_sekolah_lain_yang_belum_isi_dana_bosp_tahap_tidak_menghalangi_sekolah_lain(): void
    {
        $sekolahTerkunci = ProfilSekolah::factory()->create();
        $sekolahSiap = ProfilSekolah::factory()->create();
        $this->buatDanaBospTahapTerisi($sekolahSiap, totalPenerimaan: 150000000);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        // Sekolah yang belum isi Dana BOSP Tahap - isi() tidak boleh
        // membuka form.
        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('isi', $sekolahTerkunci->id)
            ->assertSet('showForm', false);

        // Sekolah lain yang SUDAH isi Dana BOSP Tahap tetap bisa dikelola
        // Superadmin seperti biasa, walau ada sekolah lain yang masih
        // terkunci.
        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('isi', $sekolahSiap->id)
            ->assertSet('showForm', true)
            ->assertSet('anggaran_bosp', '150000000');
    }
}
