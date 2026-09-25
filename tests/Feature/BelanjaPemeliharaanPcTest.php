<?php

namespace Tests\Feature;

use App\Livewire\PendataanBosp\BelanjaPemeliharaanPc\Index;
use App\Models\PendataanBosp;
use App\Models\ProfilSekolah;
use App\Models\RincianPemeliharaan;
use App\Models\RincianPemeliharaanPc;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Menguji menu Belanja Pemeliharaan PC Komputer-Laptop-Printer dll -
 * Pendataan BOSP (2026-09-10, Part 18): CRUD lewat modal Tambah/Edit,
 * input langsung di kotak tabel (auto-save per kotak, sama seperti
 * Belanja Pemeliharaan Bangunan), rumus Total Harga (Volume x Harga
 * Satuan), RBAC per sekolah, Export/Import Excel, tab UTAMA
 * (barang/jasa) di atas tab Triwulan, dan - sesuai jawaban
 * AskUserQuestion 2026-09-10 - Nama Barang BOLEH DUPLIKAT (tidak ada
 * validasi keunikan). Menu ini punya tabel database SENDIRI
 * (rincian_pemeliharaan_pc), TERPISAH dari menu Belanja Pemeliharaan
 * Bangunan (rincian_pemeliharaan).
 */
class BelanjaPemeliharaanPcTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, array<int, string>>  $baris
     */
    private function buatFileExcel(array $baris): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            'Kode UPB', 'NPSN', 'Nama Sekolah', 'Nama Barang', 'Nama Merk Barang',
            'Volume', 'Satuan', 'Harga Satuan', 'Total Harga', 'Asal Usul', 'Tanggal', 'Keterangan',
        ], null, 'A1');
        $sheet->fromArray($baris, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'rincian-pemeliharaan-pc').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return UploadedFile::fake()->createWithContent('rincian-pemeliharaan-pc.xlsx', file_get_contents($path));
    }

    public function test_admin_bosp_bisa_menambah_baris_untuk_sekolahnya_sendiri(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('tambah')
            ->set('kode_upb', 'UPB-001')
            ->set('nama_barang', 'Laptop')
            ->set('nama_merk_barang', 'Asus')
            ->set('volume', '10')
            ->set('satuan', 'Unit')
            ->set('harga_satuan', '75000')
            ->set('asal_usul', 'Toko Komputer')
            ->set('tanggal', '2026-09-10')
            ->set('keterangan', 'Untuk lab komputer')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('rincian_pemeliharaan_pc', [
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianPemeliharaanPc::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 1,
            'kode_upb' => 'UPB-001',
            'nama_barang' => 'Laptop',
            'volume' => 10,
            'harga_satuan' => 75000,
            'total_harga' => 750000,
        ]);
    }

    public function test_admin_bosp_tidak_bisa_menambah_baris_untuk_sekolah_lain(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('tambah')
            ->set('profil_sekolah_id', $sekolahLain->id)
            ->set('nama_barang', 'Printer')
            ->call('simpan');

        $this->assertDatabaseHas('rincian_pemeliharaan_pc', [
            'profil_sekolah_id' => $sekolahSaya->id,
            'nama_barang' => 'Printer',
        ]);
        $this->assertDatabaseMissing('rincian_pemeliharaan_pc', [
            'profil_sekolah_id' => $sekolahLain->id,
        ]);
    }

    public function test_superadmin_bisa_menambah_baris_untuk_sekolah_manapun(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('tambah')
            ->set('profil_sekolah_id', $sekolah->id)
            ->set('nama_barang', 'Mouse')
            ->set('volume', '5')
            ->set('harga_satuan', '200000')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('rincian_pemeliharaan_pc', [
            'profil_sekolah_id' => $sekolah->id,
            'nama_barang' => 'Mouse',
            'total_harga' => 1000000,
        ]);
    }

    public function test_nama_barang_wajib_diisi(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('tambah')
            ->set('nama_barang', '')
            ->call('simpan')
            ->assertHasErrors(['nama_barang']);
    }

    public function test_nama_barang_boleh_duplikat_pada_sekolah_tahun_triwulan_jenis_yang_sama(): void
    {
        // Sesuai jawaban AskUserQuestion 2026-09-10 ("Boleh duplikat") -
        // TIDAK ada validasi keunikan sama sekali pada kolom Nama Barang.
        $sekolah = ProfilSekolah::factory()->create();
        RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianPemeliharaanPc::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nama_barang' => 'Laptop',
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('tambah')
            ->set('nama_barang', 'Laptop')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('rincian_pemeliharaan_pc', 2);
    }

    public function test_edit_baris_yang_sudah_ada_lewat_modal(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $baris = RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nama_barang' => 'Sebelum Edit',
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('edit', $baris->id)
            ->set('nama_barang', 'Sesudah Edit')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('rincian_pemeliharaan_pc', [
            'id' => $baris->id,
            'nama_barang' => 'Sesudah Edit',
        ]);
        $this->assertDatabaseCount('rincian_pemeliharaan_pc', 1);
    }

    public function test_admin_bosp_tidak_bisa_mengedit_baris_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $baris = RincianPemeliharaanPc::factory()->create(['profil_sekolah_id' => $sekolahLain->id]);
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('edit', $baris->id)
            ->assertForbidden();
    }

    public function test_hapus_baris(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $baris = RincianPemeliharaanPc::factory()->create(['profil_sekolah_id' => $sekolah->id]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('konfirmasiHapus', $baris->id)
            ->call('hapus');

        $this->assertDatabaseMissing('rincian_pemeliharaan_pc', ['id' => $baris->id]);
    }

    public function test_admin_bosp_tidak_bisa_menghapus_baris_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $baris = RincianPemeliharaanPc::factory()->create(['profil_sekolah_id' => $sekolahLain->id]);
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('konfirmasiHapus', $baris->id)
            ->assertForbidden();

        $this->assertDatabaseHas('rincian_pemeliharaan_pc', ['id' => $baris->id]);
    }

    public function test_input_langsung_di_kotak_tabel_tersimpan_otomatis_dan_hitung_ulang_total_harga(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $baris = RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jenis' => RincianPemeliharaanPc::JENIS_BARANG,
            'volume' => 2,
            'harga_satuan' => 100000,
            'total_harga' => 200000,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$baris->id}.volume", '5')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('rincian_pemeliharaan_pc', [
            'id' => $baris->id,
            'volume' => 5,
            'harga_satuan' => 100000,
            'total_harga' => 500000, // 5 x 100.000, dihitung ulang otomatis
        ]);
    }

    public function test_admin_bosp_tidak_bisa_input_langsung_di_kotak_tabel_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $baris = RincianPemeliharaanPc::factory()->create(['profil_sekolah_id' => $sekolahLain->id]);
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$baris->id}.nama_barang", 'Diselipkan')
            ->assertForbidden();

        $this->assertDatabaseMissing('rincian_pemeliharaan_pc', [
            'id' => $baris->id,
            'nama_barang' => 'Diselipkan',
        ]);
    }

    public function test_total_harga_tidak_bisa_diset_langsung_lewat_kotak_tabel(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $baris = RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'volume' => 2,
            'harga_satuan' => 100000,
            'total_harga' => 200000,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // "total_harga" sengaja TIDAK ada di daftarFieldEditable() - dikirim
        // lewat updated() tapi harus diabaikan sepenuhnya.
        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$baris->id}.total_harga", '999999999')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('rincian_pemeliharaan_pc', [
            'id' => $baris->id,
            'total_harga' => 200000, // tidak berubah
        ]);
    }

    public function test_admin_bosp_hanya_melihat_baris_sekolahnya_sendiri(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak Saya']);
        $sekolahLain = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak Lain']);
        RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolahSaya->id,
            'nama_barang' => 'Punya Saya',
        ]);
        RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'nama_barang' => 'Punya Lain',
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolahSaya->id]);

        $this->actingAs($adminBosp)
            ->get('/pendataan-bosp/belanja-pemeliharaan-pc')
            ->assertOk()
            ->assertSee('Punya Saya')
            ->assertDontSee('Punya Lain');
    }

    public function test_data_terisolasi_per_tahun_triwulan_dan_jenis(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $barangTw1TahunIni = RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianPemeliharaanPc::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nama_barang' => 'Barang TW1 Tahun Ini',
        ]);
        $barangTw2TahunIni = RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianPemeliharaanPc::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 2,
            'nama_barang' => 'Barang TW2 Tahun Ini',
        ]);
        $jasaTw1TahunIni = RincianPemeliharaanPc::factory()->jasa()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nama_barang' => 'Jasa TW1 Tahun Ini',
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        // Default: tab utama 'barang', triwulan 1.
        $baris = $component->get('baris');
        $this->assertArrayHasKey($barangTw1TahunIni->id, $baris);
        $this->assertArrayNotHasKey($barangTw2TahunIni->id, $baris);
        $this->assertArrayNotHasKey($jasaTw1TahunIni->id, $baris);

        $component->call('pindahTab', 2);
        $baris = $component->get('baris');
        $this->assertArrayHasKey($barangTw2TahunIni->id, $baris);
        $this->assertArrayNotHasKey($barangTw1TahunIni->id, $baris);

        // Pindah ke tab utama 'jasa', TW-1 - baris jasa harus muncul,
        // baris barang tidak boleh ikut tercampur meski triwulan sama.
        $component->call('pindahTab', 1)->call('pindahTabUtama', RincianPemeliharaanPc::JENIS_JASA);
        $baris = $component->get('baris');
        $this->assertArrayHasKey($jasaTw1TahunIni->id, $baris);
        $this->assertArrayNotHasKey($barangTw1TahunIni->id, $baris);
    }

    public function test_pindah_tab_utama_tidak_mereset_triwulan_aktif(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('pindahTab', 3)
            ->call('pindahTabUtama', RincianPemeliharaanPc::JENIS_JASA)
            ->assertSet('triwulan', 3)
            ->assertSet('tabUtama', RincianPemeliharaanPc::JENIS_JASA);
    }

    public function test_pindah_tab_utama_menolak_nilai_jenis_yang_tidak_dikenal(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('pindahTabUtama', 'jenis-tidak-valid')
            ->assertSet('tabUtama', RincianPemeliharaanPc::JENIS_BARANG);
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

    public function test_link_belanja_pemeliharaan_pc_muncul_di_sidebar_setelah_identitas_bosp_lengkap(): void
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

        $this->actingAs($adminBosp)
            ->get('/pendataan-bosp')
            ->assertOk()
            ->assertDontSee('Belanja Pemeliharaan PC Komputer-Laptop-Printer dll');

        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $this->actingAs($adminBosp)
            ->get('/pendataan-bosp')
            ->assertOk()
            ->assertSee('Belanja Pemeliharaan PC Komputer-Laptop-Printer dll');
    }

    public function test_superadmin_wajib_pilih_sekolah_dulu_sebelum_export(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        RincianPemeliharaanPc::factory()->create(['profil_sekolah_id' => $sekolah->id]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('export')
            ->assertSet('errorExport', fn ($pesan) => ! empty($pesan));
    }

    public function test_superadmin_bisa_export_setelah_memilih_sekolah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        RincianPemeliharaanPc::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('filterSekolahId', $sekolah->id)
            ->call('export')
            ->assertFileDownloaded('rincian-pemeliharaan-pc-triwulan-1-'.now()->year.'.xlsx');
    }

    public function test_export_nama_file_berubah_mengikuti_tab_utama_jasa(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        RincianPemeliharaanPc::factory()->jasa()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('pindahTabUtama', RincianPemeliharaanPc::JENIS_JASA)
            ->set('filterSekolahId', $sekolah->id)
            ->call('export')
            ->assertFileDownloaded('rincian-jasa-pemeliharaan-pc-triwulan-1-'.now()->year.'.xlsx');
    }

    public function test_admin_bosp_bisa_export_tanpa_pilih_sekolah_karena_sudah_terkunci(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        RincianPemeliharaanPc::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('export')
            ->assertFileDownloaded('rincian-pemeliharaan-pc-triwulan-1-'.now()->year.'.xlsx');
    }

    public function test_import_menambah_baris_baru_untuk_superadmin_berdasarkan_npsn(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $file = $this->buatFileExcel([
            ['UPB-100', $sekolah->npsn, $sekolah->nama_sekolah, 'Printer', 'Epson', 20, 'Unit', 85000, 1700000, 'Toko A', '10-09-2026', 'Lab komputer'],
        ]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('fileImport', $file)
            ->call('import')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('rincian_pemeliharaan_pc', [
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianPemeliharaanPc::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 1,
            'kode_upb' => 'UPB-100',
            'nama_barang' => 'Printer',
            'volume' => 20,
            'harga_satuan' => 85000,
            'total_harga' => 1700000, // dihitung ulang sendiri, bukan diambil dari kolom Excel
        ]);
    }

    public function test_import_admin_bosp_selalu_terkunci_ke_sekolahnya_sendiri(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        // NPSN pada file sengaja punya sekolah lain - harus diabaikan,
        // baris tetap masuk ke sekolah Admin BOSP yang login.
        $file = $this->buatFileExcel([
            ['UPB-200', $sekolahLain->npsn, $sekolahLain->nama_sekolah, 'Toner', 'HP', 10, 'Unit', 45000, 450000, 'Toko B', '10-09-2026', ''],
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('fileImport', $file)
            ->call('import')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('rincian_pemeliharaan_pc', [
            'profil_sekolah_id' => $sekolahSaya->id,
            'nama_barang' => 'Toner',
        ]);
        $this->assertDatabaseMissing('rincian_pemeliharaan_pc', [
            'profil_sekolah_id' => $sekolahLain->id,
        ]);
    }

    public function test_import_selalu_menambah_baris_baru_walau_nama_barang_sudah_ada(): void
    {
        // TIDAK ada kunci pencocokan (Nama Barang boleh duplikat), jadi
        // SETIAP baris pada file yang diimport SELALU masuk sebagai baris
        // baru, tidak pernah menimpa baris yang sudah ada.
        $sekolah = ProfilSekolah::factory()->create();
        RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nama_barang' => 'Laptop',
            'volume' => 1,
            'harga_satuan' => 75000,
            'total_harga' => 75000,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $file = $this->buatFileExcel([
            ['UPB-300', $sekolah->npsn, $sekolah->nama_sekolah, 'Laptop', 'Asus', 10, 'Unit', 75000, 750000, 'Toko C', '10-09-2026', ''],
        ]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('fileImport', $file)
            ->call('import')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('rincian_pemeliharaan_pc', 2);
        $this->assertDatabaseHas('rincian_pemeliharaan_pc', ['nama_barang' => 'Laptop', 'volume' => 1]);
        $this->assertDatabaseHas('rincian_pemeliharaan_pc', ['nama_barang' => 'Laptop', 'volume' => 10]);
    }

    public function test_baris_placeholder_kosong_selalu_tampil_untuk_setiap_sekolah_superadmin(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        $sekolah2 = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $baris = Livewire::actingAs($superadmin)->test(Index::class)->get('baris');

        $this->assertArrayHasKey(-$sekolah1->id, $baris);
        $this->assertArrayHasKey(-$sekolah2->id, $baris);
        $this->assertSame('', $baris[-$sekolah1->id]['nama_barang']);
        $this->assertSame('', $baris[-$sekolah2->id]['nama_barang']);
    }

    public function test_admin_bosp_hanya_melihat_placeholder_sekolahnya_sendiri(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        $baris = Livewire::actingAs($adminBosp)->test(Index::class)->get('baris');

        $this->assertArrayHasKey(-$sekolahSaya->id, $baris);
        $this->assertArrayNotHasKey(-$sekolahLain->id, $baris);
    }

    public function test_mengisi_kotak_placeholder_membuat_baris_baru(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $idPlaceholder = -$sekolah->id;

        $component = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$idPlaceholder}.nama_barang", 'Dibuat Lewat Placeholder')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('rincian_pemeliharaan_pc', [
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianPemeliharaanPc::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nama_barang' => 'Dibuat Lewat Placeholder',
            'total_harga' => 0,
        ]);

        // Baris placeholder-nya sendiri harus kembali kosong lagi setelah
        // baris baru dibuat (siap dipakai untuk baris berikutnya).
        $baris = $component->get('baris');
        $this->assertSame('', $baris[$idPlaceholder]['nama_barang']);
    }

    public function test_mengisi_placeholder_pada_tab_jasa_membuat_baris_dengan_jenis_jasa(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $idPlaceholder = -$sekolah->id;

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('pindahTabUtama', RincianPemeliharaanPc::JENIS_JASA)
            ->set("baris.{$idPlaceholder}.nama_barang", 'Jasa Service Laptop')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('rincian_pemeliharaan_pc', [
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianPemeliharaanPc::JENIS_JASA,
            'nama_barang' => 'Jasa Service Laptop',
        ]);
    }

    public function test_setelah_baris_placeholder_terisi_bisa_langsung_diisi_lagi_dengan_nama_barang_sama(): void
    {
        // Sesuai jawaban AskUserQuestion "Boleh duplikat" - baris kedua
        // dengan Nama Barang SAMA PERSIS dengan baris pertama tetap harus
        // berhasil dibuat, bukan ditolak.
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $idPlaceholder = -$sekolah->id;

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$idPlaceholder}.nama_barang", 'Laptop')
            ->set("baris.{$idPlaceholder}.nama_barang", 'Laptop')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('rincian_pemeliharaan_pc', 2);
        $this->assertSame(2, RincianPemeliharaanPc::where('nama_barang', 'Laptop')->count());
    }

    public function test_admin_bosp_tidak_bisa_mengisi_placeholder_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);
        $idPlaceholder = -$sekolahLain->id;

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$idPlaceholder}.nama_barang", 'Diselipkan')
            ->assertForbidden();

        $this->assertDatabaseMissing('rincian_pemeliharaan_pc', [
            'profil_sekolah_id' => $sekolahLain->id,
        ]);
    }

    public function test_daftar_sekolah_diurutkan_negeri_dulu_baru_swasta(): void
    {
        $swasta = ProfilSekolah::factory()->create(['nama_sekolah' => 'A SD Swasta', 'status' => ProfilSekolah::STATUS_SWASTA]);
        $negeri = ProfilSekolah::factory()->create(['nama_sekolah' => 'Z SD Negeri', 'status' => ProfilSekolah::STATUS_NEGERI]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $daftarSekolah = Livewire::actingAs($superadmin)->test(Index::class)->viewData('daftarSekolah');

        $this->assertSame([$negeri->id, $swasta->id], $daftarSekolah->pluck('id')->all());
    }

    public function test_tombol_tambah_baris_baru_fokus_ke_kotak_pertama(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $html = Livewire::actingAs($superadmin)->test(Index::class)->html();

        $this->assertStringContainsString("x-on:click=\"\$el.closest('tr').querySelector('input,select')?.focus()\"", $html);
    }

    public function test_menu_pc_dan_menu_bangunan_tidak_saling_tercampur(): void
    {
        // Menu ini punya tabel database SENDIRI (rincian_pemeliharaan_pc),
        // TERPISAH dari rincian_pemeliharaan (menu Belanja Pemeliharaan
        // Bangunan) - sesuai jawaban AskUserQuestion 2026-09-10 ("Menu &
        // tabel terpisah dari Bangunan"). Data di 1 menu tidak boleh
        // muncul di menu lainnya walau sekolah/tahun/triwulan/jenis sama.
        $sekolah = ProfilSekolah::factory()->create();
        RincianPemeliharaan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nama_barang' => 'Semen (menu Bangunan)',
        ]);
        RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nama_barang' => 'Laptop (menu PC)',
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $baris = Livewire::actingAs($superadmin)->test(Index::class)->get('baris');

        $namaBarangDitampilkan = collect($baris)->pluck('nama_barang')->all();
        $this->assertContains('Laptop (menu PC)', $namaBarangDitampilkan);
        $this->assertNotContains('Semen (menu Bangunan)', $namaBarangDitampilkan);
    }

    /**
     * Baris Jumlah PER SEKOLAH (tab "barang"/Rincian Pemeliharaan PC) hanya
     * menjumlahkan baris milik sekolah itu sendiri, TIDAK tercampur dengan
     * sekolah lain - permintaan user 2026-09-16, jawaban AskUserQuestion
     * "Per sekolah + Total seluruh sekolah".
     */
    public function test_baris_jumlah_per_sekolah_menjumlahkan_hanya_baris_sekolah_itu_sendiri(): void
    {
        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SD Jumlah Per Sekolah PC']);
        RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianPemeliharaanPc::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 1,
            'harga_satuan' => 100000,
            'total_harga' => 100000,
        ]);
        RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianPemeliharaanPc::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 1,
            'harga_satuan' => 250000,
            'total_harga' => 250000,
        ]);

        // Sekolah lain - jumlahnya TIDAK BOLEH ikut ke total sekolah di atas.
        $sekolahLain = ProfilSekolah::factory()->create(['nama_sekolah' => 'SD Lain Pembanding PC']);
        RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'jenis' => RincianPemeliharaanPc::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 1,
            'harga_satuan' => 999000,
            'total_harga' => 999000,
        ]);

        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $html = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->html();

        $this->assertStringContainsString('Jumlah Rincian Pemeliharaan PC Komputer-Laptop-Printer dll', $html);
        // 100.000 + 250.000 = 350.000 (total sekolah pertama, BUKAN
        // tercampur dengan 999.000 milik sekolah lain).
        $this->assertStringContainsString('Rp 350.000', $html);
    }

    /**
     * Baris Jumlah per sekolah HARUS ikut tersembunyi/tampil bersama detail
     * baris lain (x-show="terbuka", hanya terlihat saat grup dibuka lewat
     * simbol "+"). Kolom 1 s.d. 9 digabung jadi satu sel (colspan="9")
     * sesuai permintaan "kolom 1 sampai kolom 9 di merge cell".
     */
    public function test_baris_jumlah_per_sekolah_ikut_x_show_terbuka_dan_merge_kolom_1_sampai_9(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianPemeliharaanPc::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 1,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $html = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->html();

        $this->assertStringContainsString(
            'x-show="terbuka" x-cloak>'."\n".'                                        <td colspan="9" class="px-2 py-2 text-right">Jumlah Rincian Pemeliharaan PC Komputer-Laptop-Printer dll</td>',
            $html
        );
    }

    /**
     * Label baris Jumlah (per sekolah maupun keseluruhan) HARUS berubah
     * mengikuti tab utama yang aktif - "Jumlah Rincian Jasa Pemeliharaan
     * PC-Laptop-Printer dll" saat tab jasa dipilih.
     */
    public function test_baris_jumlah_label_berubah_jadi_jasa_saat_tab_jasa_aktif(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        RincianPemeliharaanPc::factory()->jasa()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 1,
            'harga_satuan' => 150000,
            'total_harga' => 150000,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $html = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('pindahTabUtama', RincianPemeliharaanPc::JENIS_JASA)
            ->html();

        $this->assertStringContainsString('Jumlah Rincian Jasa Pemeliharaan PC-Laptop-Printer dll', $html);
        $this->assertStringContainsString('Jumlah Rincian Jasa Pemeliharaan PC-Laptop-Printer dll Seluruh Sekolah', $html);
        $this->assertStringNotContainsString('Jumlah Rincian Pemeliharaan PC Komputer-Laptop-Printer dll Seluruh Sekolah', $html);
        $this->assertStringContainsString('Rp 150.000', $html);
    }

    /**
     * Baris Jumlah UNTUK SELURUH SEKOLAH menjumlahkan gabungan seluruh
     * sekolah yang SEDANG DITAMPILKAN, dan SELALU terlihat (bukan bagian
     * dari grup yang bisa ditutup) - beda dengan baris Jumlah per sekolah
     * di atas.
     */
    public function test_baris_jumlah_keseluruhan_menjumlahkan_semua_sekolah_yang_ditampilkan(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolah1->id,
            'jenis' => RincianPemeliharaanPc::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 1,
            'harga_satuan' => 200000,
            'total_harga' => 200000,
        ]);

        $sekolah2 = ProfilSekolah::factory()->create();
        RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolah2->id,
            'jenis' => RincianPemeliharaanPc::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 1,
            'harga_satuan' => 300000,
            'total_harga' => 300000,
        ]);

        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $html = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->html();

        $this->assertStringContainsString('Jumlah Rincian Pemeliharaan PC Komputer-Laptop-Printer dll Seluruh Sekolah', $html);
        // 200.000 + 300.000 = 500.000.
        $this->assertStringContainsString('Rp 500.000', $html);
    }

    /**
     * Baris Jumlah keseluruhan HARUS selalu tampil (TIDAK ikut x-show
     * "terbuka" seperti baris Jumlah per sekolah) - dicek lewat memastikan
     * baris ini muncul di luar blok yang memakai atribut x-show.
     */
    public function test_baris_jumlah_keseluruhan_selalu_tampil_tidak_ikut_x_show(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianPemeliharaanPc::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 1,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $html = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->html();

        $this->assertStringContainsString(
            '<tr class="bg-slate-200 font-bold text-slate-800 border-t-2 border-slate-400">'."\n".'                                        <td colspan="9" class="px-2 py-2.5 text-right">Jumlah Rincian Pemeliharaan PC Komputer-Laptop-Printer dll Seluruh Sekolah</td>',
            $html
        );
    }

    /**
     * Baris Jumlah keseluruhan HARUS TIDAK ditampilkan kalau tidak ada
     * sekolah yang bisa ditampilkan sama sekali - dicek eksplisit karena
     * dibungkus @if ($daftarSekolah->isNotEmpty()).
     */
    public function test_baris_jumlah_keseluruhan_tidak_muncul_kalau_tidak_ada_sekolah(): void
    {
        ProfilSekolah::factory()->create(['nama_sekolah' => 'SD Tidak Akan Dicari PC']);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $html = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('search', 'Nama Sekolah Yang Tidak Pernah Ada Sama Sekali')
            ->html();

        $this->assertStringNotContainsString('Jumlah Rincian Pemeliharaan PC Komputer-Laptop-Printer dll Seluruh Sekolah', $html);
    }
}
