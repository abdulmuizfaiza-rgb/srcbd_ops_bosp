<?php

namespace Tests\Feature;

use App\Livewire\PendataanOps\SuratTpg\Index;
use App\Models\PendataanBosp;
use App\Models\PendataanOps;
use App\Models\ProfilSekolah;
use App\Models\SuratTpg;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menguji menu baru "Format Surat Rekomendasi & Pembatalan TPG" -
 * Pendataan OPS (permintaan user 2026-09-24, round kedelapan belas, 2
 * gambar contoh diupload user).
 *
 * Ringkasan keputusan (lihat docblock App\Models\SuratTpg &
 * App\Livewire\PendataanOps\SuratTpg\Index untuk detail lengkap):
 * - 2 tab (rekomendasi/penghentian) berdasarkan tahun+triwulan (BUKAN
 *   bulan), default triwulan=1.
 * - Nomor Surat & Tanggal Surat DISIMPAN ke tabel surat_tpg (jawaban
 *   AskUserQuestion 2026-09-24); field lain (Nama/NIP Kepsek, Tempat
 *   Tugas, Nama/NIP Pengawas) SELALU diambil langsung dari ProfilSekolah,
 *   TIDAK disimpan duplikat.
 * - Kata "Triwulan" (bukan "Semester") dipakai konsisten di KEDUA jenis
 *   surat.
 */
class SuratTpgTest extends TestCase
{
    use RefreshDatabase;

    public function test_label_triwulan_sesuai_format_romawi_dan_kata(): void
    {
        $this->assertSame('Triwulan I (satu)', SuratTpg::labelTriwulan(1));
        $this->assertSame('Triwulan II (dua)', SuratTpg::labelTriwulan(2));
        $this->assertSame('Triwulan III (tiga)', SuratTpg::labelTriwulan(3));
        $this->assertSame('Triwulan IV (empat)', SuratTpg::labelTriwulan(4));
    }

    public function test_admin_ops_otomatis_terkunci_ke_sekolahnya_sendiri(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class);

        $component->assertSet('profil_sekolah_id', $sekolah->id);
        $this->assertSame($sekolah->id, $component->viewData('sekolah')->id);
        $this->assertSame('rekomendasi', $component->get('tabAktif'));
        $this->assertSame(1, $component->get('triwulan'));
    }

    /**
     * Field Nama/NIP Kepsek & Pengawas (blok tanda tangan) muncul di
     * KEDUA tab - "Tempat Tugas" (nama sekolah) HANYA muncul di tab
     * Penghentian, sesuai gambar contoh 2 yang diupload user (gambar
     * contoh 1/Rekomendasi TIDAK punya blok identitas "Tempat Tugas"
     * sama sekali, hanya blok tanda tangan).
     */
    public function test_field_nama_nip_dan_tempat_tugas_otomatis_dari_profil_sekolah(): void
    {
        $sekolah = ProfilSekolah::factory()->create([
            'nama_kepala_sekolah' => 'Drs. Contoh Kepsek',
            'nip_kepala_sekolah' => '19700101 199003 1 001',
            'nama_sekolah' => 'SDN Contoh 1',
            'nama_pengawas' => 'Dra. Contoh Pengawas',
            'nip_pengawas' => '19650101 198903 2 002',
        ]);
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $rekomendasi = Livewire::actingAs($adminOps)->test(Index::class);
        $rekomendasi->assertSee('Drs. Contoh Kepsek');
        $rekomendasi->assertSee('19700101 199003 1 001');
        $rekomendasi->assertSee('Dra. Contoh Pengawas');
        $rekomendasi->assertSee('19650101 198903 2 002');

        $penghentian = Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('pindahTab', 'penghentian');
        $penghentian->assertSee('Drs. Contoh Kepsek');
        $penghentian->assertSee('19700101 199003 1 001');
        $penghentian->assertSee('SDN Contoh 1');
        $penghentian->assertSee('Dra. Contoh Pengawas');
        $penghentian->assertSee('19650101 198903 2 002');
    }

    public function test_mengetik_nomor_surat_membuat_baris_baru(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->set('tahun', 2026)
            ->set('triwulan', 2)
            ->set('nomorSurat', '123/SR-CBD/2026')
            ->set('tanggalSurat', '2026-09-24');

        $this->assertDatabaseHas('surat_tpg', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => 2026,
            'triwulan' => 2,
            'jenis' => SuratTpg::JENIS_REKOMENDASI,
            'nomor_surat' => '123/SR-CBD/2026',
        ]);

        $surat = SuratTpg::where('profil_sekolah_id', $sekolah->id)->firstOrFail();
        $this->assertSame('2026-09-24', $surat->tanggal_surat->format('Y-m-d'));
    }

    public function test_mengetik_ulang_nomor_surat_melakukan_update_bukan_duplikat(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        SuratTpg::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jenis' => SuratTpg::JENIS_REKOMENDASI,
            'nomor_surat' => '001/LAMA',
        ]);
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->set('nomorSurat', '002/BARU');

        $this->assertDatabaseCount('surat_tpg', 1);
        $this->assertDatabaseHas('surat_tpg', ['profil_sekolah_id' => $sekolah->id, 'nomor_surat' => '002/BARU']);
    }

    public function test_dua_tab_punya_nomor_surat_terpisah_walau_tahun_triwulan_sama(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class);

        $component->set('nomorSurat', 'REKOMENDASI-001');

        $component->call('pindahTab', 'penghentian')
            ->assertSet('nomorSurat', null)
            ->set('nomorSurat', 'PENGHENTIAN-001');

        $this->assertDatabaseHas('surat_tpg', ['jenis' => SuratTpg::JENIS_REKOMENDASI, 'nomor_surat' => 'REKOMENDASI-001']);
        $this->assertDatabaseHas('surat_tpg', ['jenis' => SuratTpg::JENIS_PENGHENTIAN, 'nomor_surat' => 'PENGHENTIAN-001']);
        $this->assertDatabaseCount('surat_tpg', 2);

        // Balik ke tab rekomendasi - nomor surat harus tetap ada, bukan tertimpa.
        $component->call('pindahTab', 'rekomendasi')->assertSet('nomorSurat', 'REKOMENDASI-001');
    }

    public function test_pindah_tab_hanya_menerima_rekomendasi_atau_penghentian(): void
    {
        $adminOps = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('pindahTab', 'tidak-valid')
            ->assertSet('tabAktif', 'rekomendasi')
            ->call('pindahTab', 'penghentian')
            ->assertSet('tabAktif', 'penghentian');
    }

    public function test_superadmin_wajib_pilih_sekolah_dulu(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $component->assertSet('profil_sekolah_id', null);
        $this->assertNull($component->viewData('sekolah'));
    }

    public function test_superadmin_bisa_memilih_sekolah_lalu_surat_terisi(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('pilihSekolah', $sekolah->id);

        $component->assertSet('profil_sekolah_id', $sekolah->id);
        $this->assertSame($sekolah->id, $component->viewData('sekolah')->id);
    }

    public function test_pilih_sekolah_dengan_id_tidak_valid_dapat_404(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('pilihSekolah', 999999)
            ->assertNotFound();
    }

    public function test_admin_ops_memanggil_pilih_sekolah_tidak_berpengaruh(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('pilihSekolah', $sekolahLain->id)
            ->assertSet('profil_sekolah_id', $sekolahSaya->id);
    }

    public function test_superadmin_belum_pilih_sekolah_tidak_bisa_mengisi_nomor_surat(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('nomorSurat', '001/TEST');

        $this->assertDatabaseCount('surat_tpg', 0);
    }

    public function test_zoom_in_dan_zoom_out(): void
    {
        $adminOps = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->assertSet('zoomPercent', 100)
            ->call('zoomIn')
            ->assertSet('zoomPercent', 110)
            ->call('zoomOut')
            ->call('zoomOut')
            ->assertSet('zoomPercent', 90);
    }

    public function test_pengaturan_kertas_dan_margin_default(): void
    {
        $adminOps = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);

        $component = Livewire::actingAs($adminOps)->test(Index::class);

        $component->assertSet('jenisKertas', 'a4');
        $component->assertSet('marginKiri', 2.5);
        $component->assertSet('marginKanan', 2.5);
        $component->assertSet('marginAtas', 3.0);
        $component->assertSet('marginBawah', 2.5);
        $component->assertSet('tampilSettingMargin', false);
    }

    public function test_setting_margin_bisa_dibuka_dan_ditutup(): void
    {
        $adminOps = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('toggleSettingMargin')
            ->assertSet('tampilSettingMargin', true)
            ->call('toggleSettingMargin')
            ->assertSet('tampilSettingMargin', false);
    }

    public function test_margin_dibatasi_ke_rentang_wajar(): void
    {
        $adminOps = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->set('marginKiri', 99)
            ->assertSet('marginKiri', 5.0)
            ->set('marginKanan', 0.01)
            ->assertSet('marginKanan', 0.5);
    }

    public function test_url_cetak_kosong_tanpa_pilih_sekolah_superadmin(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertNull($component->instance()->urlCetak());
    }

    public function test_url_cetak_berisi_parameter_lengkap(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->set('tahun', 2026)
            ->set('triwulan', 3)
            ->set('jenisKertas', 'f4');

        $url = $component->instance()->urlCetak();

        $this->assertNotNull($url);
        $this->assertStringContainsString('/pendataan-ops/surat-tpg/cetak', $url);
        $this->assertStringContainsString('jenis=rekomendasi', $url);
        $this->assertStringContainsString('tahun=2026', $url);
        $this->assertStringContainsString('triwulan=3', $url);
        $this->assertStringContainsString('profil_sekolah_id='.$sekolah->id, $url);
        $this->assertStringContainsString('kertas=f4', $url);
    }

    public function test_route_cetak_menampilkan_pdf_inline_untuk_pemilik_sekolah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanOps::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $response = $this->actingAs($adminOps)->get('/pendataan-ops/surat-tpg/cetak?'.http_build_query([
            'jenis' => 'penghentian',
            'tahun' => 2026,
            'triwulan' => 1,
            'profil_sekolah_id' => $sekolah->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
    }

    public function test_route_cetak_ditolak_untuk_sekolah_lain(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);
        PendataanOps::factory()->create(['profil_sekolah_id' => $sekolahSaya->id]);

        $this->actingAs($adminOps)
            ->get('/pendataan-ops/surat-tpg/cetak?'.http_build_query([
                'jenis' => 'rekomendasi',
                'tahun' => 2026,
                'triwulan' => 1,
                'profil_sekolah_id' => $sekolahLain->id,
            ]))
            ->assertForbidden();
    }

    public function test_export_pdf_berhasil_untuk_kedua_tab(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->set('tahun', 2026)
            ->set('triwulan', 1)
            ->call('exportPdf')
            ->assertFileDownloaded();

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->set('tahun', 2026)
            ->set('triwulan', 1)
            ->call('pindahTab', 'penghentian')
            ->call('exportPdf')
            ->assertFileDownloaded();
    }

    public function test_export_word_berhasil_untuk_kedua_tab(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('exportWord')
            ->assertFileDownloaded();

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('pindahTab', 'penghentian')
            ->call('exportWord')
            ->assertFileDownloaded();
    }

    public function test_export_gagal_tanpa_pilih_sekolah_superadmin(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('exportPdf')
            ->assertHasErrors('umum');

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('exportWord')
            ->assertHasErrors('umum');
    }

    public function test_route_surat_tpg_bisa_diakses_admin_ops_dan_superadmin(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanOps::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $this->actingAs($adminOps)
            ->get('/pendataan-ops/surat-tpg')
            ->assertOk();

        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $this->actingAs($superadmin)
            ->get('/pendataan-ops/surat-tpg')
            ->assertOk();
    }

    public function test_route_surat_tpg_ditolak_untuk_admin_bosp(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        // Lengkapi onboarding Admin BOSP-nya sendiri (isi Identitas Admin
        // BOSP) supaya EnsureOnboardingComplete TIDAK mengalihkan request
        // ke halaman onboarding duluan - baru begitu bisa menguji gate
        // "akses-pendataan-ops" murni menolaknya dengan 403.
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $this->actingAs($adminBosp)
            ->get('/pendataan-ops/surat-tpg')
            ->assertForbidden();
    }

    public function test_link_surat_tpg_muncul_di_sidebar(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanOps::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $response = $this->actingAs($adminOps)->get('/pendataan-ops');

        $response->assertOk()->assertSee('Format Surat Rekomendasi &amp; Pembatalan TPG', false);
    }

    public function test_isi_surat_rekomendasi_memuat_4_kondisi_baku(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class);

        $component->assertSee('REKOMENDASI');
        $component->assertSee('Melaksanakan tugas dengan baik sesuai peraturan perundang-undangan');
        $component->assertSee('Memenuhi jam tatap muka minimal');
        $component->assertSee('Memiliki hasil nilai (PK) Guru dengan sebutan baik pada tahun sebelumnya');
        $component->assertSee('Tidak sedang menerima sanksi hukum baik disiplin pegawai maupun administrasi');
    }

    public function test_isi_surat_penghentian_memuat_10_alasan_baku(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('pindahTab', 'penghentian');

        $component->assertSee('PENGHENTIAN TUNJANGAN PROFESI GURU');
        $component->assertSee('Tidak melaksanakan tugas dengan baik sesuai peraturan perundang-undangan');
        $component->assertSee('Tidak memenuhi jam tatap muka minimal 24 jam/minggu');
        $component->assertSee('Memiliki hasil nilai (PK) Guru dengan sebutan kurang baik pada tahun sebelumnya');
        $component->assertSee('Tidak memiliki surat keputusan tunjangan profesi (SKTP) yang dikeluarkan oleh Kementerian Pendidikan dan Kebudayaan');
        $component->assertSee('Sedang menerima sanksi hukum, baik disiplin pegawai maupun administrasi');
        $component->assertSee('Sudah memasuki usia 60 (enam puluh) tahun');
        $component->assertSee('Sedang melaksanakan tugas belajar');
        $component->assertSee('Meninggal Dunia');
        $component->assertSee('Cuti');
        $component->assertSee('Mutasi ke struktural');
    }

    public function test_label_triwulan_bukan_semester_tampil_di_kedua_surat(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->set('triwulan', 2);

        $component->assertSee('Triwulan II (dua)');
        $component->assertDontSee('Semester');

        $component->call('pindahTab', 'penghentian');
        $component->assertSee('Triwulan II (dua)');
        $component->assertDontSee('Semester');
    }

    /**
     * Tes untuk permintaan user 2026-09-24 (round kesembilan belas):
     * poin 1, 2, 4, 5 & 6 - upload Kop Surat manual per sekolah (berlaku
     * utk KEDUA tab) & penghapusan styling kotak berbaris hitam pada isi
     * surat. Lihat docblock App\Models\ProfilSekolah::kopSuratDataUri(),
     * App\Http\Controllers\KopSuratFileController &
     * App\Livewire\PendataanOps\SuratTpg\Index::updatedKopSuratBaru().
     */
    public function test_upload_kop_surat_valid_tersimpan_dan_lama_dihapus(): void
    {
        Storage::fake('public');

        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // Upload pertama.
        $fileLama = UploadedFile::fake()->image('kop-lama.jpg', 800, 200);
        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->set('kopSuratBaru', $fileLama)
            ->assertHasNoErrors('kopSuratBaru');

        $sekolah->refresh();
        $pathLama = $sekolah->kop_surat;
        $this->assertNotNull($pathLama);
        Storage::disk('public')->assertExists($pathLama);

        // Upload kedua (ganti) - file lama harus terhapus, file baru tersimpan.
        $fileBaru = UploadedFile::fake()->image('kop-baru.png', 900, 220);
        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->set('kopSuratBaru', $fileBaru)
            ->assertHasNoErrors('kopSuratBaru');

        $sekolah->refresh();
        $this->assertNotNull($sekolah->kop_surat);
        $this->assertNotSame($pathLama, $sekolah->kop_surat);
        Storage::disk('public')->assertExists($sekolah->kop_surat);
        Storage::disk('public')->assertMissing($pathLama);
    }

    public function test_upload_kop_surat_tipe_file_tidak_valid_ditolak(): void
    {
        Storage::fake('public');

        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $fileTidakValid = UploadedFile::fake()->create('kop-surat.pdf', 100, 'application/pdf');

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->set('kopSuratBaru', $fileTidakValid)
            ->assertHasErrors('kopSuratBaru');

        $this->assertNull($sekolah->fresh()->kop_surat);
    }

    public function test_upload_kop_surat_ukuran_terlalu_besar_ditolak(): void
    {
        Storage::fake('public');

        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // Lebih besar dari batas max:2048 (KB).
        $fileTerlaluBesar = UploadedFile::fake()->image('kop-besar.jpg')->size(3000);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->set('kopSuratBaru', $fileTerlaluBesar)
            ->assertHasErrors('kopSuratBaru');

        $this->assertNull($sekolah->fresh()->kop_surat);
    }

    public function test_upload_kop_surat_superadmin_tanpa_pilih_sekolah_tidak_tersimpan(): void
    {
        Storage::fake('public');

        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $file = UploadedFile::fake()->image('kop.jpg', 800, 200);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('kopSuratBaru', $file);

        Storage::disk('public')->assertDirectoryEmpty('kop-surat');
    }

    public function test_upload_kop_surat_berlaku_otomatis_untuk_kedua_tab(): void
    {
        Storage::fake('public');

        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $file = UploadedFile::fake()->image('kop.jpg', 800, 200);

        // Upload dari tab rekomendasi.
        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->set('kopSuratBaru', $file)
            ->assertHasNoErrors('kopSuratBaru');

        $sekolah->refresh();
        $this->assertNotNull($sekolah->kop_surat);

        // Buka lagi (component baru, mensimulasikan kunjungan lain) langsung ke tab penghentian.
        $penghentian = Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('pindahTab', 'penghentian');

        $penghentian->assertSee(route('pendataan-ops.surat-tpg.kop-surat', $sekolah->id), false);
    }

    public function test_kop_surat_src_di_layar_memakai_route_terautentikasi(): void
    {
        Storage::fake('public');

        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $sekolah->update(['kop_surat' => UploadedFile::fake()->image('kop.jpg', 800, 200)->store('kop-surat', 'public')]);

        $component = Livewire::actingAs($adminOps)->test(Index::class);

        $component->assertSee(route('pendataan-ops.surat-tpg.kop-surat', $sekolah->id), false);
    }

    public function test_route_kop_surat_ditolak_untuk_sekolah_lain(): void
    {
        Storage::fake('public');

        $sekolahSaya = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create([
            'kop_surat' => UploadedFile::fake()->image('kop.jpg', 800, 200)->store('kop-surat', 'public'),
        ]);
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);
        PendataanOps::factory()->create(['profil_sekolah_id' => $sekolahSaya->id]);

        $this->actingAs($adminOps)
            ->get(route('pendataan-ops.surat-tpg.kop-surat', $sekolahLain->id))
            ->assertForbidden();
    }

    public function test_route_kop_surat_404_jika_belum_diupload(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanOps::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $this->actingAs($adminOps)
            ->get(route('pendataan-ops.surat-tpg.kop-surat', $sekolah->id))
            ->assertNotFound();
    }

    /**
     * Regresi permintaan user poin 1 & 4 ("isi surat nya tidak perlu
     * pakai garis hitam") - memastikan kotak berbaris hitam
     * (border:1px solid #000) yang dipakai round kedelapan belas sudah
     * tidak ada lagi di isi surat KEDUA tab.
     */
    public function test_isi_surat_tidak_lagi_memakai_kotak_garis_hitam(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $rekomendasi = Livewire::actingAs($adminOps)->test(Index::class);
        $rekomendasi->assertDontSee('border:1px solid #000', false);
        $rekomendasi->assertDontSeeHtml('KOP SEKOLAH');

        $penghentian = Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('pindahTab', 'penghentian');
        $penghentian->assertDontSee('border:1px solid #000', false);
        $penghentian->assertDontSeeHtml('KOP SEKOLAH');
    }

    /**
     * Tes untuk permintaan user 2026-09-24 (round kedua puluh), 5 poin
     * perbaikan lanjutan menu Format Surat Rekomendasi & Pembatalan TPG.
     */
    public function test_input_nomor_surat_rata_kiri_bukan_rata_tengah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $rekomendasi = Livewire::actingAs($adminOps)->test(Index::class);
        $rekomendasi->assertSee('text-left', false);
        $rekomendasi->assertDontSee('px-1 py-0 text-center', false);

        $penghentian = Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('pindahTab', 'penghentian');
        $penghentian->assertSee('text-left', false);
        $penghentian->assertDontSee('px-1 py-0 text-center', false);
    }

    public function test_garis_bawah_di_atas_nama_pengawas_dan_kepsek_sudah_dihapus(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class);

        $component->assertDontSee('border-bottom:1px solid #000', false);
    }

    public function test_isi_surat_rekomendasi_memakai_spasi_baris_1_5(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $rekomendasi = Livewire::actingAs($adminOps)->test(Index::class);
        $rekomendasi->assertSee('line-height:1.5', false);

        // Tab Penghentian TIDAK diminta memakai spasi 1,5 (hanya poin 4 utk tab Rekomendasi).
        $penghentian = Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('pindahTab', 'penghentian');
        $penghentian->assertDontSee('line-height:1.5', false);
    }

    public function test_upload_kop_surat_portrait_ditolak_wajib_landscape(): void
    {
        Storage::fake('public');

        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $fotoPortrait = UploadedFile::fake()->image('kop-portrait.jpg', 600, 900);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->set('kopSuratBaru', $fotoPortrait)
            ->assertHasErrors('kopSuratBaru');

        $this->assertNull($sekolah->fresh()->kop_surat);
    }

    public function test_upload_kop_surat_persegi_ditolak_wajib_landscape(): void
    {
        Storage::fake('public');

        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $fotoPersegi = UploadedFile::fake()->image('kop-persegi.jpg', 900, 900);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->set('kopSuratBaru', $fotoPersegi)
            ->assertHasErrors('kopSuratBaru');

        $this->assertNull($sekolah->fresh()->kop_surat);
    }

    public function test_upload_kop_surat_landscape_tapi_lebar_kurang_dari_800px_ditolak(): void
    {
        Storage::fake('public');

        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $fotoSempit = UploadedFile::fake()->image('kop-sempit.jpg', 500, 150);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->set('kopSuratBaru', $fotoSempit)
            ->assertHasErrors('kopSuratBaru');

        $this->assertNull($sekolah->fresh()->kop_surat);
    }

    public function test_upload_kop_surat_landscape_lebar_cukup_diterima(): void
    {
        Storage::fake('public');

        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $fotoValid = UploadedFile::fake()->image('kop-valid.jpg', 1200, 300);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->set('kopSuratBaru', $fotoValid)
            ->assertHasNoErrors('kopSuratBaru');

        $this->assertNotNull($sekolah->fresh()->kop_surat);
    }

    public function test_kop_surat_tetap_ditampilkan_center(): void
    {
        Storage::fake('public');

        $sekolah = ProfilSekolah::factory()->create([
            'kop_surat' => UploadedFile::fake()->image('kop.jpg', 1200, 300)->store('kop-surat', 'public'),
        ]);
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class);

        $component->assertSeeHtmlInOrder([
            'text-align:center;margin-bottom:8px;',
            'img',
        ]);
    }

    /**
     * Tes untuk permintaan user 2026-09-24 (round kedua puluh satu) -
     * tab 3 BARU "Surat Pernyataan" & fitur gabungan "Cetak Semua"/
     * "Unduh PDF Semua"/"Unduh Word Semua".
     */
    public function test_tab_pernyataan_bisa_diakses_dan_field_otomatis_dari_profil_sekolah(): void
    {
        $sekolah = ProfilSekolah::factory()->create([
            'nama_kepala_sekolah' => 'Drs. Contoh Kepsek',
            'nip_kepala_sekolah' => '19700101 199003 1 001',
            'nama_sekolah' => 'SDN Contoh 1',
            'alamat_sekolah' => 'Jl. Contoh Alamat No. 1',
        ]);
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('pindahTab', 'pernyataan');

        $component->assertSet('tabAktif', 'pernyataan');
        $component->assertSee('SURAT PERNYATAAN');
        $component->assertSee('Drs. Contoh Kepsek');
        $component->assertSee('19700101 199003 1 001');
        $component->assertSee('SDN Contoh 1'); // Unit Kerja
        $component->assertSee('Jl. Contoh Alamat No. 1'); // Alamat Kantor
        $component->assertSee('Kepala Sekolah'); // Jabatan (teks tetap)
    }

    public function test_tab_pernyataan_pindah_tab_lewat_pindahtab(): void
    {
        $adminOps = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('pindahTab', 'pernyataan')
            ->assertSet('tabAktif', 'pernyataan');
    }

    public function test_tahun_pelajaran_diisi_manual_dan_tersimpan_otomatis(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('pindahTab', 'pernyataan')
            ->set('tahun', 2026)
            ->set('triwulan', 2)
            ->set('tahunPelajaran', '2025/2026');

        $this->assertDatabaseHas('surat_tpg', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => 2026,
            'triwulan' => 2,
            'jenis' => SuratTpg::JENIS_PERNYATAAN,
            'tahun_pelajaran' => '2025/2026',
        ]);
    }

    public function test_tahun_pelajaran_tidak_bentrok_dengan_nomor_surat_tab_lain(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class);

        $component->set('nomorSurat', 'REKOMENDASI-001');

        $component->call('pindahTab', 'pernyataan')
            ->assertSet('tahunPelajaran', null)
            ->set('tahunPelajaran', '2025/2026');

        $this->assertDatabaseHas('surat_tpg', ['jenis' => SuratTpg::JENIS_REKOMENDASI, 'nomor_surat' => 'REKOMENDASI-001']);
        $this->assertDatabaseHas('surat_tpg', ['jenis' => SuratTpg::JENIS_PERNYATAAN, 'tahun_pelajaran' => '2025/2026']);
        $this->assertDatabaseCount('surat_tpg', 2);
    }

    public function test_triwulan_romawi_tanpa_kata_dalam_kurung_tampil_di_pernyataan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('pindahTab', 'pernyataan')
            ->set('triwulan', 2);

        $component->assertSee('Triwulan II');
        $component->assertDontSee('Triwulan II (dua)');
    }

    public function test_isi_pernyataan_memuat_7_poin_pernyataan_baku(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('pindahTab', 'pernyataan');

        $component->assertSee('Telah mendokumentasikan dengan baik seluruh instrumen hasil penilaian kinerja guru');
        $component->assertSee('Telah melakukan pengawasan dan memastikan terhadap kinerja operator sekolah');
        $component->assertSee('Telah melakukan verifikasi terhadap keabsahan dokumen calon penerima Tunjangan Profesi Guru (TPG)');
        $component->assertSee('Telah melakukan verifikasi terhadap absensi bulanan calon penerima TPG');
        $component->assertSee('Telah melakukan verifikasi terhadap data guru binaan yang masuk kategori penghentian TPG');
        $component->assertSee('Telah mengumpulkan surat rekomendasi yang dibuat dan di tandatangani oleh kepala sekolah');
        $component->assertSee('Telah mengirimkan dokumen lampiran yang tertera pada poin (e) dan (f) Kepala Dinas Pendidikan Kabupaten Sukabumi melalui bidang PTK.');
        $component->assertSee('Materai');
    }

    public function test_export_pdf_dan_word_berhasil_untuk_tab_pernyataan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('pindahTab', 'pernyataan')
            ->call('exportPdf')
            ->assertFileDownloaded();

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('pindahTab', 'pernyataan')
            ->call('exportWord')
            ->assertFileDownloaded();
    }

    public function test_route_cetak_mendukung_jenis_pernyataan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanOps::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $response = $this->actingAs($adminOps)->get('/pendataan-ops/surat-tpg/cetak?'.http_build_query([
            'jenis' => 'pernyataan',
            'tahun' => 2026,
            'triwulan' => 1,
            'profil_sekolah_id' => $sekolah->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_route_cetak_semua_menampilkan_pdf_gabungan_untuk_pemilik_sekolah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanOps::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $response = $this->actingAs($adminOps)->get('/pendataan-ops/surat-tpg/cetak-semua?'.http_build_query([
            'tahun' => 2026,
            'triwulan' => 1,
            'profil_sekolah_id' => $sekolah->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
    }

    public function test_route_cetak_semua_ditolak_untuk_sekolah_lain(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);
        PendataanOps::factory()->create(['profil_sekolah_id' => $sekolahSaya->id]);

        $this->actingAs($adminOps)
            ->get('/pendataan-ops/surat-tpg/cetak-semua?'.http_build_query([
                'tahun' => 2026,
                'triwulan' => 1,
                'profil_sekolah_id' => $sekolahLain->id,
            ]))
            ->assertForbidden();
    }

    /**
     * Regresi: gabungan PDF/Word HARUS memuat konten ketiga surat
     * sekaligus (judul REKOMENDASI, PENGHENTIAN TUNJANGAN PROFESI GURU, &
     * SURAT PERNYATAAN), masing2 punya Nomor Surat/Tahun Pelajaran
     * SENDIRI-SENDIRI (bukan tertukar/kosong semua).
     */
    public function test_gabungan_pdf_dan_word_memuat_konten_ketiga_surat_dengan_data_masing_masing(): void
    {
        $sekolah = ProfilSekolah::factory()->create([
            'nama_kepala_sekolah' => 'Drs. Contoh Kepsek',
        ]);
        $data = [
            'editable' => false,
            'triwulan' => 1,
            'tahun' => now()->year,
            'namaKepsek' => $sekolah->nama_kepala_sekolah,
            'nipKepsek' => $sekolah->nip_kepala_sekolah,
            'namaSekolah' => $sekolah->nama_sekolah,
            'alamatSekolah' => $sekolah->alamat_sekolah,
            'namaPengawas' => $sekolah->nama_pengawas,
            'nipPengawas' => $sekolah->nip_pengawas,
            'kopSuratSrc' => null,
            'margin' => ['atas' => 3, 'kanan' => 2.5, 'bawah' => 2.5, 'kiri' => 2.5],
            SuratTpg::JENIS_REKOMENDASI => ['nomorSurat' => 'NOMOR-REKOMENDASI-001', 'tanggalSurat' => null, 'tahunPelajaran' => null],
            SuratTpg::JENIS_PENGHENTIAN => ['nomorSurat' => 'NOMOR-PENGHENTIAN-002', 'tanggalSurat' => null, 'tahunPelajaran' => null],
            SuratTpg::JENIS_PERNYATAAN => ['nomorSurat' => null, 'tanggalSurat' => null, 'tahunPelajaran' => '2025/2026'],
        ];

        $html = view('pdf.surat-tpg-gabungan', $data)->render();

        $this->assertStringContainsString('REKOMENDASI', $html);
        $this->assertStringContainsString('PENGHENTIAN TUNJANGAN PROFESI GURU', $html);
        $this->assertStringContainsString('SURAT PERNYATAAN', $html);
        $this->assertStringContainsString('NOMOR-REKOMENDASI-001', $html);
        $this->assertStringContainsString('NOMOR-PENGHENTIAN-002', $html);
        $this->assertStringContainsString('2025/2026', $html);
        $this->assertStringContainsString('Drs. Contoh Kepsek', $html);
        $this->assertStringContainsString('page-break-after: always;', $html);

        $htmlWord = view('word.surat-tpg-gabungan', $data)->render();
        $this->assertStringContainsString('REKOMENDASI', $htmlWord);
        $this->assertStringContainsString('PENGHENTIAN TUNJANGAN PROFESI GURU', $htmlWord);
        $this->assertStringContainsString('SURAT PERNYATAAN', $htmlWord);
    }

    /**
     * Round kedua puluh dua: toolbar "Cetak Semua"/"Unduh PDF Semua"/
     * "Unduh Word Semua" DIPINDAHKAN ke menu Unduhan - halaman ini
     * SEHARUSNYA TIDAK LAGI menampilkan toolbar itu (lihat
     * tests/Feature/UnduhanTest.php utk pengujian versi barunya).
     */
    public function test_toolbar_cetak_semua_tidak_lagi_tampil_di_halaman_ini(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class);

        $component->assertDontSee('Cetak Semua');
        $component->assertDontSee('Unduh PDF Semua');
        $component->assertDontSee('Unduh Word Semua');
        // Tombol per-tab lama TETAP ada (tidak dihapus/diganti).
        $component->assertSee('Unduh PDF');
        $component->assertSee('Unduh Word');
    }

    /**
     * Round kedua puluh dua, poin 1: kotak bergaris hitam yang round
     * kedua puluh satu sengaja dipertahankan pada tab Pernyataan sekarang
     * DIHAPUS - polos sama seperti tab Rekomendasi/Penghentian.
     */
    public function test_isi_pernyataan_tidak_lagi_pakai_kotak_bergaris(): void
    {
        $html = view('pdf.partials.surat-tpg-pernyataan-isi', [
            'editable' => false,
            'tahunPelajaran' => '2025/2026',
            'tanggalSurat' => null,
            'triwulan' => 2,
            'tahun' => 2026,
            'namaKepsek' => 'Contoh Kepsek',
            'nipKepsek' => '123456',
            'namaSekolah' => 'SDN Contoh',
            'alamatSekolah' => 'Jl. Contoh No. 1',
        ])->render();

        $this->assertStringNotContainsString('border:1px solid #000', $html);
    }

    /** Round kedua puluh dua, poin 2: isi Surat Pernyataan memakai spasi baris 1,5. */
    public function test_isi_pernyataan_memakai_spasi_1_5(): void
    {
        $html = view('pdf.partials.surat-tpg-pernyataan-isi', [
            'editable' => false,
            'tahunPelajaran' => '2025/2026',
            'tanggalSurat' => null,
            'triwulan' => 2,
            'tahun' => 2026,
            'namaKepsek' => 'Contoh Kepsek',
            'nipKepsek' => '123456',
            'namaSekolah' => 'SDN Contoh',
            'alamatSekolah' => 'Jl. Contoh No. 1',
        ])->render();

        $this->assertStringContainsString('line-height:1.5', $html);
    }

    /**
     * Round kedua puluh dua, poin 1: tulisan "Materai" digeser ke bawah/
     * tengah, TEPAT SEBELUM baris nama Kepala Sekolah (urutan: "Yang
     * membuat pernyataan" -> ruang tanda tangan -> "Materai" (center) ->
     * Nama -> NIP).
     */
    public function test_materai_diposisikan_tepat_sebelum_nama_kepsek(): void
    {
        $html = view('pdf.partials.surat-tpg-pernyataan-isi', [
            'editable' => false,
            'tahunPelajaran' => '2025/2026',
            'tanggalSurat' => null,
            'triwulan' => 2,
            'tahun' => 2026,
            'namaKepsek' => 'Contoh Kepsek Unik',
            'nipKepsek' => '123456',
            'namaSekolah' => 'SDN Contoh',
            'alamatSekolah' => 'Jl. Contoh No. 1',
        ])->render();

        // Round kedua puluh empat: "Materai" sekarang punya margin-left:-40px
        // tambahan (digeser ke kiri 2 langkah) - lihat
        // test_materai_digeser_ke_kiri_tanpa_pengaruhi_baris_lain().
        $this->assertStringContainsString('text-align:center;margin-left:-40px;">Materai', $html);

        // "Contoh Kepsek Unik" muncul DUA kali (identitas atas & blok
        // tanda tangan bawah) - pakai kemunculan TERAKHIR (strrpos) krn
        // yang diuji posisinya di sini adalah baris nama pada blok tanda
        // tangan, bukan identitas atas.
        $posisiPernyataan = strpos($html, 'Yang membuat pernyataan');
        $posisiMaterai = strpos($html, 'Materai');
        $posisiNama = strrpos($html, 'Contoh Kepsek Unik');

        $this->assertNotFalse($posisiPernyataan);
        $this->assertNotFalse($posisiMaterai);
        $this->assertNotFalse($posisiNama);
        $this->assertTrue($posisiPernyataan < $posisiMaterai);
        $this->assertTrue($posisiMaterai < $posisiNama);
    }

    /**
     * Round kedua puluh tiga, poin 1: tulisan "Materai" sekarang punya
     * ruang kosong DI ATAS *dan* DI BAWAH tulisannya (sebelumnya round 22
     * hanya 1 ruang kosong sebelum "Materai", langsung disusul Nama tanpa
     * jarak) - supaya posisinya benar-benar di tengah blok tanda tangan
     * (bukan menempel di bawah), & Nama/NIP Kepala Sekolah tergeser turun
     * sedikit memberi ruang tempel materai fisik.
     */
    public function test_materai_memiliki_ruang_kosong_sebelum_dan_sesudah(): void
    {
        $html = view('pdf.partials.surat-tpg-pernyataan-isi', [
            'editable' => false,
            'tahunPelajaran' => '2025/2026',
            'tanggalSurat' => null,
            'triwulan' => 2,
            'tahun' => 2026,
            'namaKepsek' => 'Contoh Kepsek Unik',
            'nipKepsek' => '123456',
            'namaSekolah' => 'SDN Contoh',
            'alamatSekolah' => 'Jl. Contoh No. 1',
        ])->render();

        $posisiPernyataan = strpos($html, 'Yang membuat pernyataan');
        $posisiGapSebelum = strpos($html, 'height:28px');
        $posisiMaterai = strpos($html, 'text-align:center;margin-left:-40px;">Materai');
        $posisiGapSesudah = strpos($html, 'height:106px');
        $posisiNama = strrpos($html, 'Contoh Kepsek Unik');

        $this->assertNotFalse($posisiGapSebelum);
        $this->assertNotFalse($posisiGapSesudah);
        $this->assertTrue($posisiPernyataan < $posisiGapSebelum);
        $this->assertTrue($posisiGapSebelum < $posisiMaterai);
        $this->assertTrue($posisiMaterai < $posisiGapSesudah);
        $this->assertTrue($posisiGapSesudah < $posisiNama);
    }

    /**
     * Round kedua puluh empat, poin 1: tulisan "Materai" digeser ke KIRI
     * 2 langkah (margin-left:-40px pada baris "Materai" itu sendiri),
     * TANPA ikut menggeser baris tanggal/Nama/NIP lain pada blok yang
     * sama (baris-baris itu tetap memakai margin-left:40px milik div
     * pembungkus, tidak diberi margin-left negatif).
     */
    public function test_materai_digeser_ke_kiri_tanpa_pengaruhi_baris_lain(): void
    {
        $html = view('pdf.partials.surat-tpg-pernyataan-isi', [
            'editable' => false,
            'tahunPelajaran' => '2025/2026',
            'tanggalSurat' => null,
            'triwulan' => 2,
            'tahun' => 2026,
            'namaKepsek' => 'Contoh Kepsek Geser',
            'nipKepsek' => '654321',
            'namaSekolah' => 'SDN Contoh',
            'alamatSekolah' => 'Jl. Contoh No. 1',
        ])->render();

        $this->assertStringContainsString('text-align:center;margin-left:-40px;">Materai', $html);

        // Baris Nama Kepala Sekolah pada blok tanda tangan TIDAK diberi margin-left:-40px sendiri.
        $posisiNama = strrpos($html, 'Contoh Kepsek Geser');
        $potongan = substr($html, max(0, $posisiNama - 60), 60);
        $this->assertStringNotContainsString('margin-left:-40px', $potongan);
    }

    /**
     * Round kedua puluh dua, poin 2: Kop Surat TIDAK LAGI ditampilkan
     * pada tab Surat Pernyataan (preview layar), meski sekolah sudah
     * mengupload kop surat - berbeda dgn tab Rekomendasi/Penghentian yang
     * TETAP menampilkannya.
     */
    public function test_kop_surat_tidak_tampil_pada_tab_pernyataan(): void
    {
        $sekolah = ProfilSekolah::factory()->create(['kop_surat' => 'kop-surat/contoh.png']);
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('pindahTab', 'pernyataan');

        $component->assertDontSee('pendataan-ops/surat-tpg/kop-surat', false);
        $component->assertDontSee('Ganti Kop Surat');

        // Tab Rekomendasi (lainnya) TETAP menampilkan kop surat.
        $component->call('pindahTab', 'rekomendasi');
        $component->assertSee('pendataan-ops/surat-tpg/kop-surat', false);
    }

    /**
     * Regresi: dokumen gabungan (dipindah ke menu Unduhan, tapi view-nya
     * SAMA) - Kop Surat HANYA muncul utk Rekomendasi & Penghentian, TIDAK
     * utk bagian Pernyataan.
     */
    public function test_gabungan_kop_surat_hanya_utk_rekomendasi_dan_penghentian(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $data = [
            'editable' => false,
            'triwulan' => 1,
            'tahun' => now()->year,
            'namaKepsek' => $sekolah->nama_kepala_sekolah,
            'nipKepsek' => $sekolah->nip_kepala_sekolah,
            'namaSekolah' => $sekolah->nama_sekolah,
            'alamatSekolah' => $sekolah->alamat_sekolah,
            'namaPengawas' => $sekolah->nama_pengawas,
            'nipPengawas' => $sekolah->nip_pengawas,
            'kopSuratSrc' => 'data:image/png;base64,contohbase64',
            'margin' => ['atas' => 3, 'kanan' => 2.5, 'bawah' => 2.5, 'kiri' => 2.5],
            SuratTpg::JENIS_REKOMENDASI => ['nomorSurat' => 'NOMOR-001', 'tanggalSurat' => null, 'tahunPelajaran' => null],
            SuratTpg::JENIS_PENGHENTIAN => ['nomorSurat' => 'NOMOR-002', 'tanggalSurat' => null, 'tahunPelajaran' => null],
            SuratTpg::JENIS_PERNYATAAN => ['nomorSurat' => null, 'tanggalSurat' => null, 'tahunPelajaran' => '2025/2026'],
        ];

        $html = view('pdf.surat-tpg-gabungan', $data)->render();

        // Muncul PERSIS 2 kali (Rekomendasi & Penghentian), TIDAK utk Pernyataan.
        $this->assertSame(2, substr_count($html, 'data:image/png;base64,contohbase64'));
    }
}
