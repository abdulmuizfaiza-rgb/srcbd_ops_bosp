<?php

namespace Tests\Feature;

use App\Livewire\PendataanOps\Unduhan\Index;
use App\Models\Lampiran2a;
use App\Models\ProfilSekolah;
use App\Models\User;
use App\Support\UnduhanLampiranData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menu Unduhan (Pendataan OPS > Unduhan, 2026-09-05) - unduhan gabungan
 * Lampiran 2a/2b/2c per Triwulan+Tahun dalam bentuk Excel (3 sheet) & PDF.
 *
 * Perbaikan 2026-09-24 (round kedua puluh dua, permintaan user poin 3 &
 * 4): ditambah pengujian bagian "Cetak Surat Rekomendasi, Surat
 * Penghentian TPG dan Surat Pernyataan" - fitur "Cetak"/"Unduh PDF"/
 * "Unduh Word" gabungan ketiga surat TPG yang DIPINDAHKAN ke menu ini
 * dari menu "Format Surat Rekomendasi & Pembatalan TPG" (lihat
 * App\Support\SuratTpgGabunganData & docblock kelas Index).
 *
 * Perbaikan 2026-09-24 (round kedua puluh tiga, permintaan user poin 2):
 * ditambah pengujian kontrol Jenis Kertas/Setting Margin KHUSUS bagian
 * "Cetak Surat Rekomendasi, dst" (nilai default sama persis dgn menu
 * "Format Surat Rekomendasi & Pembatalan TPG", & ikut disertakan pada URL
 * "Cetak" supaya user bisa menyamakan hasil cetak/unduh di sini dgn menu
 * asalnya).
 */
class UnduhanTest extends TestCase
{
    use RefreshDatabase;

    public function test_tahun_default_ke_tahun_sekarang(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->assertSet('tahun', now()->year)
            ->assertSet('triwulan', 1);
    }

    public function test_admin_ops_hanya_melihat_data_sekolahnya_sendiri(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolahSaya->id, 'triwulan' => 1, 'tahun' => now()->year]);
        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolahLain->id, 'triwulan' => 1, 'tahun' => now()->year]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->assertViewHas('jumlah2a', 1)
            ->assertViewHas('namaSekolahTampil', $sekolahSaya->nama_sekolah);
    }

    public function test_superadmin_melihat_gabungan_data_semua_sekolah(): void
    {
        $sekolahA = ProfilSekolah::factory()->create();
        $sekolahB = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolahA->id, 'triwulan' => 1, 'tahun' => now()->year]);
        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolahB->id, 'triwulan' => 1, 'tahun' => now()->year]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->assertViewHas('jumlah2a', 2)
            ->assertViewHas('namaSekolahTampil', 'Semua Sekolah');
    }

    public function test_data_terpisah_per_triwulan_dan_tahun(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1, 'tahun' => now()->year]);
        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 2, 'tahun' => now()->year]);
        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1, 'tahun' => now()->year - 1]);

        $component = Livewire::actingAs($adminOps)->test(Index::class);

        $component->assertViewHas('jumlah2a', 1);

        $component->call('pindahTab', 2)->assertViewHas('jumlah2a', 1);

        $component->set('tahun', now()->year - 1)
            ->call('pindahTab', 1)
            ->assertViewHas('jumlah2a', 1);
    }

    public function test_urutan_rekap_superadmin_negeri_dulu_lalu_kecamatan_lalu_nama_sekolah(): void
    {
        $swastaZ = ProfilSekolah::factory()->create(['status' => 'swasta', 'kecamatan' => 'Cibadak', 'nama_sekolah' => 'SMP Swasta Z']);
        $negeriB = ProfilSekolah::factory()->create(['status' => 'negeri', 'kecamatan' => 'Caringin', 'nama_sekolah' => 'SDN B']);
        $negeriA = ProfilSekolah::factory()->create(['status' => 'negeri', 'kecamatan' => 'Caringin', 'nama_sekolah' => 'SDN A']);

        Lampiran2a::factory()->create(['profil_sekolah_id' => $swastaZ->id, 'triwulan' => 1, 'tahun' => 2026, 'nama_ptk' => 'PTK Swasta Z']);
        Lampiran2a::factory()->create(['profil_sekolah_id' => $negeriB->id, 'triwulan' => 1, 'tahun' => 2026, 'nama_ptk' => 'PTK Negeri B']);
        Lampiran2a::factory()->create(['profil_sekolah_id' => $negeriA->id, 'triwulan' => 1, 'tahun' => 2026, 'nama_ptk' => 'PTK Negeri A']);

        $hasil = UnduhanLampiranData::ambil(Lampiran2a::query(), 'lampiran_2a', 1, 2026, null);

        // Negeri (Caringin: SDN A, SDN B) harus mendahului Swasta (Cibadak:
        // SMP Swasta Z) - meniru urutan yang sudah dipakai di menu Profil
        // Sekolah (Status -> Kecamatan -> Nama Sekolah).
        $this->assertSame(
            ['PTK Negeri A', 'PTK Negeri B', 'PTK Swasta Z'],
            $hasil->pluck('nama_ptk')->all()
        );
    }

    public function test_admin_ops_unduh_excel_dengan_nama_file_sesuai_sekolah(): void
    {
        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SDN Contoh']);
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1, 'tahun' => now()->year]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('unduhExcel')
            ->assertFileDownloaded('SDN Contoh_Triwulan 1_'.now()->year.'.xlsx');
    }

    public function test_superadmin_unduh_excel_dengan_nama_semua_sekolah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1, 'tahun' => now()->year]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('unduhExcel')
            ->assertFileDownloaded('Semua Sekolah_Triwulan 1_'.now()->year.'.xlsx');
    }

    public function test_admin_ops_unduh_pdf(): void
    {
        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SDN Contoh']);
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1, 'tahun' => now()->year]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('unduhPdf')
            ->assertFileDownloaded('SDN Contoh_Triwulan 1_'.now()->year.'.pdf');
    }

    public function test_label_cetak_surat_tpg_gabungan_tampil_di_menu_ini(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->assertSee('Cetak Surat Rekomendasi, Surat Penghentian TPG dan Surat Pernyataan');
    }

    public function test_admin_ops_bisa_cetak_dan_unduh_surat_tpg_gabungan_sekolahnya_sendiri(): void
    {
        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SDN Contoh']);
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class);

        $url = $component->instance()->urlCetakSuratTpgGabungan();
        $this->assertNotNull($url);
        $this->assertStringContainsString('/pendataan-ops/surat-tpg/cetak-semua', $url);
        $this->assertStringContainsString('profil_sekolah_id='.$sekolah->id, $url);

        $component->call('unduhSuratTpgGabunganPdf')
            ->assertFileDownloaded('surat-tpg-gabungan-sdn-contoh-tw1-'.now()->year.'.pdf');

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('unduhSuratTpgGabunganWord')
            ->assertFileDownloaded('surat-tpg-gabungan-sdn-contoh-tw1-'.now()->year.'.doc');
    }

    /** Admin OPS TIDAK melihat dropdown pilih sekolah (khusus Superadmin). */
    public function test_admin_ops_tidak_melihat_dropdown_pilih_sekolah_surat_tpg(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->assertDontSee('id="surat_tpg_sekolah"', false);
    }

    /**
     * Superadmin WAJIB pilih sekolah dulu lewat dropdown khusus bagian
     * ini (surat TPG = satu surat per satu sekolah, TIDAK bisa "semua
     * sekolah sekaligus" seperti rekap Lampiran) - keputusan
     * AskUserQuestion 2026-09-24 round kedua puluh dua.
     */
    public function test_superadmin_wajib_pilih_sekolah_dulu_sebelum_cetak_unduh_surat_tpg(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertNull($component->instance()->urlCetakSuratTpgGabungan());

        $component->call('unduhSuratTpgGabunganPdf')->assertHasErrors('suratTpgUmum');

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('unduhSuratTpgGabunganWord')
            ->assertHasErrors('suratTpgUmum');
    }

    public function test_superadmin_bisa_pilih_sekolah_lalu_cetak_unduh_surat_tpg_gabungan(): void
    {
        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SDN Superadmin']);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('pilihSekolahSuratTpg', $sekolah->id);

        $url = $component->instance()->urlCetakSuratTpgGabungan();
        $this->assertNotNull($url);
        $this->assertStringContainsString('profil_sekolah_id='.$sekolah->id, $url);

        $component->call('unduhSuratTpgGabunganPdf')
            ->assertFileDownloaded('surat-tpg-gabungan-sdn-superadmin-tw1-'.now()->year.'.pdf');
    }

    public function test_dropdown_pilih_sekolah_surat_tpg_ditolak_utk_id_yang_tidak_ada(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('pilihSekolahSuratTpg', 999999)
            ->assertNotFound();
    }

    /**
     * Round kedua puluh tiga: default Jenis Kertas/Margin bagian ini
     * harus sama persis dgn menu "Format Surat Rekomendasi & Pembatalan
     * TPG" (A4, Left/Right 2.5cm, Top 3cm, Bottom 2.5cm), & URL "Cetak"
     * harus menyertakan nilai2 itu supaya hasilnya bisa sama persis.
     */
    public function test_url_cetak_surat_tpg_gabungan_menyertakan_kertas_dan_margin_default(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class);

        $this->assertSame('a4', $component->get('suratTpgJenisKertas'));
        $this->assertSame(2.5, $component->get('suratTpgMarginKiri'));
        $this->assertSame(2.5, $component->get('suratTpgMarginKanan'));
        $this->assertSame(3.0, $component->get('suratTpgMarginAtas'));
        $this->assertSame(2.5, $component->get('suratTpgMarginBawah'));

        $url = $component->instance()->urlCetakSuratTpgGabungan();
        $this->assertStringContainsString('kertas=a4', $url);
        $this->assertStringContainsString('margin_kiri=2.5', $url);
        $this->assertStringContainsString('margin_kanan=2.5', $url);
        $this->assertStringContainsString('margin_atas=3', $url);
        $this->assertStringContainsString('margin_bawah=2.5', $url);
    }

    /** Mengganti Jenis Kertas ke F4 harus ikut berubah pada URL "Cetak" & berhasil diunduh (tidak error). */
    public function test_ganti_jenis_kertas_surat_tpg_gabungan_ke_f4(): void
    {
        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SDN F4']);
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->set('suratTpgJenisKertas', 'f4');

        $url = $component->instance()->urlCetakSuratTpgGabungan();
        $this->assertStringContainsString('kertas=f4', $url);

        $component->call('unduhSuratTpgGabunganPdf')
            ->assertFileDownloaded('surat-tpg-gabungan-sdn-f4-tw1-'.now()->year.'.pdf');
    }

    /** Margin yang diketik ikut dipakai (dibatasi 0.5-5cm sama seperti menu asal) & muncul pada URL "Cetak". */
    public function test_ganti_margin_surat_tpg_gabungan_dibatasi_dan_dipakai(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->set('suratTpgMarginKiri', 1.5)
            ->set('suratTpgMarginAtas', 99);

        // Dibatasi maksimal 5cm.
        $this->assertSame(5.0, $component->get('suratTpgMarginAtas'));

        $url = $component->instance()->urlCetakSuratTpgGabungan();
        $this->assertStringContainsString('margin_kiri=1.5', $url);
        $this->assertStringContainsString('margin_atas=5', $url);
    }

    /** Tombol "Setting Margin" menampilkan/menyembunyikan panel margin, sama seperti menu asal. */
    public function test_toggle_setting_margin_surat_tpg_gabungan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->assertDontSee('id="surat_tpg_margin_kiri"', false)
            ->call('toggleSuratTpgSettingMargin')
            ->assertSee('id="surat_tpg_margin_kiri"', false)
            ->call('toggleSuratTpgSettingMargin')
            ->assertDontSee('id="surat_tpg_margin_kiri"', false);
    }
}
