<?php

namespace Tests\Feature;

use App\Livewire\PendataanBosp\BiayaPendaftaranLomba\Index;
use App\Models\BelanjaHonorKegiatan;
use App\Models\BiayaPendaftaranLomba;
use App\Models\PendataanBosp;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Menguji menu Biaya Pendaftaran Lomba/Bimtek/Workshop - Pendataan BOSP
 * (2026-09-11): CRUD lewat modal Tambah/Edit, input langsung di kotak
 * tabel (auto-save per kotak, sama seperti Penerimaan Honor PTK), rumus
 * Jumlah (Volume x Tarif Harga), RBAC per sekolah, Export/Import Excel,
 * dan - sama seperti Langganan Daya Jasa - Uraian BOLEH DUPLIKAT (sesuai
 * jawaban AskUserQuestion 2026-09-11 "Boleh duplikat"), jadi tidak ada
 * pengujian validasi keunikan di sini.
 */
class BiayaPendaftaranLombaTest extends TestCase
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
            'NPSN', 'Nama Sekolah', 'Uraian', 'Volume',
            'Satuan', 'Tarif Harga', 'Jumlah', 'Tanggal',
        ], null, 'A1');
        $sheet->fromArray($baris, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'biaya-lomba').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return UploadedFile::fake()->createWithContent('biaya-lomba.xlsx', file_get_contents($path));
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
            ->set('uraian', 'Listrik')
            ->set('volume', '3')
            ->set('satuan', 'Bulan')
            ->set('tarif_harga', '500000')
            ->set('tanggal', '2026-09-10')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('biaya_pendaftaran_lomba', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'uraian' => 'Listrik',
            'volume' => 3,
            'tarif_harga' => 500000,
            'jumlah' => 1500000,
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
            ->set('uraian', 'Internet')
            ->call('simpan');

        // profil_sekolah_id dikunci ulang ke sekolah sendiri di simpan(),
        // jadi baris tetap tersimpan tapi untuk sekolahnya sendiri, bukan
        // sekolah lain yang dicoba dipilih.
        $this->assertDatabaseHas('biaya_pendaftaran_lomba', [
            'profil_sekolah_id' => $sekolahSaya->id,
            'uraian' => 'Internet',
        ]);
        $this->assertDatabaseMissing('biaya_pendaftaran_lomba', [
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
            ->set('uraian', 'Air PDAM')
            ->set('volume', '2')
            ->set('tarif_harga', '250000')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('biaya_pendaftaran_lomba', [
            'profil_sekolah_id' => $sekolah->id,
            'uraian' => 'Air PDAM',
            'jumlah' => 500000,
        ]);
    }

    public function test_uraian_wajib_diisi(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('tambah')
            ->set('uraian', '')
            ->call('simpan')
            ->assertHasErrors(['uraian']);
    }

    public function test_uraian_boleh_duplikat_pada_sekolah_tahun_triwulan_yang_sama(): void
    {
        // BEDA dengan NUPTK di Penerimaan Honor PTK - sesuai jawaban
        // AskUserQuestion 2026-09-10 ("Boleh duplikat"), TIDAK ada
        // validasi keunikan sama sekali pada kolom Uraian.
        $sekolah = ProfilSekolah::factory()->create();
        BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'uraian' => 'Listrik',
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('tambah')
            ->set('uraian', 'Listrik')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('biaya_pendaftaran_lomba', 2);
    }

    public function test_edit_baris_yang_sudah_ada_lewat_modal(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $baris = BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'uraian' => 'Sebelum Edit',
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('edit', $baris->id)
            ->set('uraian', 'Sesudah Edit')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('biaya_pendaftaran_lomba', [
            'id' => $baris->id,
            'uraian' => 'Sesudah Edit',
        ]);
        $this->assertDatabaseCount('biaya_pendaftaran_lomba', 1);
    }

    public function test_admin_bosp_tidak_bisa_mengedit_baris_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $baris = BiayaPendaftaranLomba::factory()->create(['profil_sekolah_id' => $sekolahLain->id]);
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('edit', $baris->id)
            ->assertForbidden();
    }

    public function test_hapus_baris(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $baris = BiayaPendaftaranLomba::factory()->create(['profil_sekolah_id' => $sekolah->id]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('konfirmasiHapus', $baris->id)
            ->call('hapus');

        $this->assertDatabaseMissing('biaya_pendaftaran_lomba', ['id' => $baris->id]);
    }

    public function test_admin_bosp_tidak_bisa_menghapus_baris_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $baris = BiayaPendaftaranLomba::factory()->create(['profil_sekolah_id' => $sekolahLain->id]);
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('konfirmasiHapus', $baris->id)
            ->assertForbidden();

        $this->assertDatabaseHas('biaya_pendaftaran_lomba', ['id' => $baris->id]);
    }

    public function test_input_langsung_di_kotak_tabel_tersimpan_otomatis_dan_hitung_ulang_jumlah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $baris = BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 2,
            'tarif_harga' => 100000,
            'jumlah' => 200000,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$baris->id}.volume", '5')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('biaya_pendaftaran_lomba', [
            'id' => $baris->id,
            'volume' => 5,
            'tarif_harga' => 100000,
            'jumlah' => 500000, // 5 x 100.000, dihitung ulang otomatis
        ]);
    }

    public function test_admin_bosp_tidak_bisa_input_langsung_di_kotak_tabel_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $baris = BiayaPendaftaranLomba::factory()->create(['profil_sekolah_id' => $sekolahLain->id]);
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$baris->id}.uraian", 'Diselipkan')
            ->assertForbidden();

        $this->assertDatabaseMissing('biaya_pendaftaran_lomba', [
            'id' => $baris->id,
            'uraian' => 'Diselipkan',
        ]);
    }

    public function test_jumlah_tidak_bisa_diset_langsung_lewat_kotak_tabel(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $baris = BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'volume' => 2,
            'tarif_harga' => 100000,
            'jumlah' => 200000,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // "jumlah" sengaja TIDAK ada di daftarFieldEditable() - dikirim
        // lewat updated() tapi harus diabaikan sepenuhnya.
        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$baris->id}.jumlah", '999999999')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('biaya_pendaftaran_lomba', [
            'id' => $baris->id,
            'jumlah' => 200000, // tidak berubah
        ]);
    }

    public function test_admin_bosp_hanya_melihat_baris_sekolahnya_sendiri(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak Saya']);
        $sekolahLain = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak Lain']);
        BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolahSaya->id,
            'uraian' => 'Punya Saya',
        ]);
        BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'uraian' => 'Punya Lain',
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolahSaya->id]);

        $this->actingAs($adminBosp)
            ->get('/pendataan-bosp/biaya-pendaftaran-lomba')
            ->assertOk()
            ->assertSee('Punya Saya')
            ->assertDontSee('Punya Lain');
    }

    public function test_data_terisolasi_per_tahun_dan_triwulan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $triwulan1TahunIni = BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'uraian' => 'Triwulan 1 Tahun Ini',
        ]);
        $triwulan2TahunIni = BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 2,
            'uraian' => 'Triwulan 2 Tahun Ini',
        ]);
        $triwulan1TahunLalu = BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year - 1,
            'triwulan' => 1,
            'uraian' => 'Triwulan 1 Tahun Lalu',
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        $baris = $component->get('baris');
        $this->assertArrayHasKey($triwulan1TahunIni->id, $baris);
        $this->assertArrayNotHasKey($triwulan2TahunIni->id, $baris);
        $this->assertArrayNotHasKey($triwulan1TahunLalu->id, $baris);

        $component->call('pindahTab', 2);
        $baris = $component->get('baris');
        $this->assertArrayHasKey($triwulan2TahunIni->id, $baris);
        $this->assertArrayNotHasKey($triwulan1TahunIni->id, $baris);

        $component->set('tahun', now()->year - 1)->call('pindahTab', 1);
        $baris = $component->get('baris');
        $this->assertArrayHasKey($triwulan1TahunLalu->id, $baris);
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

    public function test_link_biaya_pendaftaran_lomba_muncul_di_sidebar_setelah_identitas_bosp_lengkap(): void
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
            ->assertDontSee('Biaya Pendaftaran Lomba/Bimtek/Workshop');

        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $this->actingAs($adminBosp)
            ->get('/pendataan-bosp')
            ->assertOk()
            ->assertSee('Biaya Pendaftaran Lomba/Bimtek/Workshop');
    }

    public function test_superadmin_wajib_pilih_sekolah_dulu_sebelum_export(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        BiayaPendaftaranLomba::factory()->create(['profil_sekolah_id' => $sekolah->id]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('export')
            ->assertSet('errorExport', fn ($pesan) => ! empty($pesan));
    }

    public function test_superadmin_bisa_export_setelah_memilih_sekolah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        BiayaPendaftaranLomba::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('filterSekolahId', $sekolah->id)
            ->call('export')
            ->assertFileDownloaded('biaya-pendaftaran-lomba-triwulan-1-'.now()->year.'.xlsx');
    }

    public function test_admin_bosp_bisa_export_tanpa_pilih_sekolah_karena_sudah_terkunci(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        BiayaPendaftaranLomba::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('export')
            ->assertFileDownloaded('biaya-pendaftaran-lomba-triwulan-1-'.now()->year.'.xlsx');
    }

    public function test_import_menambah_baris_baru_untuk_superadmin_berdasarkan_npsn(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $file = $this->buatFileExcel([
            [$sekolah->npsn, $sekolah->nama_sekolah, 'Internet', 4, 'Bulan', 200000, 800000, '10-09-2026'],
        ]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('fileImport', $file)
            ->call('import')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('biaya_pendaftaran_lomba', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'uraian' => 'Internet',
            'volume' => 4,
            'tarif_harga' => 200000,
            'jumlah' => 800000, // dihitung ulang sendiri, bukan diambil dari kolom Excel
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
            [$sekolahLain->npsn, $sekolahLain->nama_sekolah, 'Telepon', 2, 'Bulan', 100000, 200000, '10-09-2026'],
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('fileImport', $file)
            ->call('import')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('biaya_pendaftaran_lomba', [
            'profil_sekolah_id' => $sekolahSaya->id,
            'uraian' => 'Telepon',
        ]);
        $this->assertDatabaseMissing('biaya_pendaftaran_lomba', [
            'profil_sekolah_id' => $sekolahLain->id,
        ]);
    }

    public function test_import_selalu_menambah_baris_baru_walau_uraian_sudah_ada(): void
    {
        // BEDA dengan PenerimaanHonorPtkImport (WithUpserts berdasar
        // NUPTK) - import di sini TIDAK punya kunci pencocokan (Uraian
        // Pembayaran boleh duplikat), jadi SETIAP baris pada file yang
        // diimport SELALU masuk sebagai baris baru, tidak pernah menimpa
        // baris yang sudah ada.
        $sekolah = ProfilSekolah::factory()->create();
        BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'uraian' => 'Listrik',
            'volume' => 1,
            'tarif_harga' => 100000,
            'jumlah' => 100000,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $file = $this->buatFileExcel([
            [$sekolah->npsn, $sekolah->nama_sekolah, 'Listrik', 6, 'Bulan', 150000, 900000, '10-09-2026'],
        ]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('fileImport', $file)
            ->call('import')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('biaya_pendaftaran_lomba', 2);
        $this->assertDatabaseHas('biaya_pendaftaran_lomba', ['uraian' => 'Listrik', 'volume' => 1]);
        $this->assertDatabaseHas('biaya_pendaftaran_lomba', ['uraian' => 'Listrik', 'volume' => 6]);
    }

    public function test_baris_placeholder_kosong_selalu_tampil_untuk_setiap_sekolah_superadmin(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        $sekolah2 = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $baris = Livewire::actingAs($superadmin)->test(Index::class)->get('baris');

        $this->assertArrayHasKey(-$sekolah1->id, $baris);
        $this->assertArrayHasKey(-$sekolah2->id, $baris);
        $this->assertSame('', $baris[-$sekolah1->id]['uraian']);
        $this->assertSame('', $baris[-$sekolah2->id]['uraian']);
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
            ->set("baris.{$idPlaceholder}.uraian", 'Dibuat Lewat Placeholder')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('biaya_pendaftaran_lomba', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'uraian' => 'Dibuat Lewat Placeholder',
            'jumlah' => 0,
        ]);

        // Baris placeholder-nya sendiri harus kembali kosong lagi setelah
        // baris baru dibuat (siap dipakai untuk baris berikutnya) - pola
        // sama seperti Penerimaan Honor PTK.
        $baris = $component->get('baris');
        $this->assertSame('', $baris[$idPlaceholder]['uraian']);
    }

    public function test_setelah_baris_placeholder_terisi_bisa_langsung_diisi_lagi_dengan_uraian_sama(): void
    {
        // Sesuai jawaban AskUserQuestion "Boleh duplikat" - baris kedua
        // dengan Uraian SAMA PERSIS dengan baris pertama tetap
        // harus berhasil dibuat, bukan ditolak.
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $idPlaceholder = -$sekolah->id;

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$idPlaceholder}.uraian", 'Listrik')
            ->set("baris.{$idPlaceholder}.uraian", 'Listrik')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('biaya_pendaftaran_lomba', 2);
        $this->assertSame(2, BiayaPendaftaranLomba::where('uraian', 'Listrik')->count());
    }

    public function test_admin_bosp_tidak_bisa_mengisi_placeholder_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);
        $idPlaceholder = -$sekolahLain->id;

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$idPlaceholder}.uraian", 'Diselipkan')
            ->assertForbidden();

        $this->assertDatabaseMissing('biaya_pendaftaran_lomba', [
            'profil_sekolah_id' => $sekolahLain->id,
        ]);
    }

    public function test_daftar_sekolah_diurutkan_negeri_dulu_baru_swasta(): void
    {
        // Pola SAMA PERSIS dengan Penerimaan Honor PTK/ProfilSekolah/
        // PendataanOps/PendataanBosp/RekapRkas - diterapkan sejak awal di
        // menu ini (bukan aturan baru). Nama sekolah sengaja dibuat
        // TERBALIK secara alfabet dari urutan status yang diharapkan.
        $swasta = ProfilSekolah::factory()->create(['nama_sekolah' => 'A SD Swasta', 'status' => ProfilSekolah::STATUS_SWASTA]);
        $negeri = ProfilSekolah::factory()->create(['nama_sekolah' => 'Z SD Negeri', 'status' => ProfilSekolah::STATUS_NEGERI]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $daftarSekolah = Livewire::actingAs($superadmin)->test(Index::class)->viewData('daftarSekolah');

        $this->assertSame([$negeri->id, $swasta->id], $daftarSekolah->pluck('id')->all());
    }

    public function test_tombol_tambah_baris_baru_fokus_ke_kotak_pertama(): void
    {
        // Tombol "+" di kolom Aksi murni Alpine sisi klien (fokus kotak
        // pertama baris, tanpa request ke server) - tidak ada method
        // Livewire khusus untuk ini, jadi cukup dipastikan markup-nya ada
        // & tidak memicu error apapun saat halaman dirender.
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $html = Livewire::actingAs($superadmin)->test(Index::class)->html();

        $this->assertStringContainsString("x-on:click=\"\$el.closest('tr').querySelector('input,select')?.focus()\"", $html);
    }

    /**
     * Pengujian tabel diringkas per-sekolah dengan simbol +/- (permintaan
     * user 2026-09-10, Part 17 poin 1) - pola SAMA PERSIS seperti pengujian
     * yang ditambahkan untuk Penerimaan Honor PTK (Part 16).
     */
    public function test_ringkasan_per_sekolah_menampilkan_jumlah_data_yang_sudah_diinput(): void
    {
        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SD Uji Ringkasan']);
        BiayaPendaftaranLomba::factory()->count(3)->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->assertSee('3 data sudah diinput')
            ->assertSee('SD Uji Ringkasan');
    }

    public function test_sekolah_tanpa_data_daya_jasa_menampilkan_ringkasan_nol_data(): void
    {
        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SD Kosong Uji']);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->assertSee('0 data sudah diinput')
            ->assertSee('SD Kosong Uji');
    }

    /**
     * Baris detail (baris daya jasa sungguhan + baris placeholder kosong
     * siap-isi) harus tersembunyi secara default - disembunyikan lewat
     * Alpine x-show="terbuka" dengan x-data awal { terbuka: false } pada
     * <tbody> per sekolah, BUKAN dihapus dari HTML.
     */
    public function test_baris_detail_disembunyikan_secara_default_lewat_x_show(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $html = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->html();

        $this->assertStringContainsString('x-data="{ terbuka: false }"', $html);
        $this->assertStringContainsString('x-show="terbuka"', $html);
    }

    public function test_edit_dan_hapus_tetap_berfungsi_untuk_baris_yang_sudah_ada_setelah_tabel_diringkas(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $baris = BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'uraian' => 'Untuk Diedit',
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('edit', $baris->id)
            ->set('uraian', 'Sesudah Diedit Dari Tabel Ringkas')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('biaya_pendaftaran_lomba', [
            'id' => $baris->id,
            'uraian' => 'Sesudah Diedit Dari Tabel Ringkas',
        ]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('konfirmasiHapus', $baris->id)
            ->call('hapus');

        $this->assertDatabaseMissing('biaya_pendaftaran_lomba', ['id' => $baris->id]);
    }

    /**
     * Menu ini punya tabel database SENDIRI (biaya_pendaftaran_lomba),
     * TERPISAH dari belanja_honor_kegiatan (menu Belanja Honor Kegiatan)
     * walau field-nya identik - kedua menu ini diminta terpisah oleh
     * user ("tambahkan menu baru" 2x untuk 2 nama menu berbeda,
     * 2026-09-11). Data di 1 menu tidak boleh muncul di menu lainnya
     * walau sekolah/tahun/triwulan sama.
     */
    public function test_menu_lomba_dan_menu_honor_kegiatan_tidak_saling_tercampur(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'uraian' => 'Honor Panitia (menu Honor Kegiatan)',
        ]);
        BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'uraian' => 'Pendaftaran Lomba (menu Lomba)',
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $baris = Livewire::actingAs($superadmin)->test(Index::class)->get('baris');
        $uraianDitampilkan = collect($baris)->pluck('uraian')->all();

        $this->assertContains('Pendaftaran Lomba (menu Lomba)', $uraianDitampilkan);
        $this->assertNotContains('Honor Panitia (menu Honor Kegiatan)', $uraianDitampilkan);
    }

    /**
     * Baris Jumlah Biaya Pendaftaran Lomba/Bimtek/Workshop PER SEKOLAH
     * hanya menjumlahkan baris milik sekolah itu sendiri, TIDAK tercampur
     * dengan sekolah lain - permintaan user 2026-09-16, jawaban
     * AskUserQuestion "Per sekolah + Total seluruh sekolah".
     */
    public function test_baris_jumlah_per_sekolah_menjumlahkan_hanya_baris_sekolah_itu_sendiri(): void
    {
        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SD Jumlah Per Sekolah Lomba']);
        BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 1,
            'tarif_harga' => 100000,
            'jumlah' => 100000,
        ]);
        BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 1,
            'tarif_harga' => 250000,
            'jumlah' => 250000,
        ]);

        // Sekolah lain - jumlahnya TIDAK BOLEH ikut ke total sekolah di atas.
        $sekolahLain = ProfilSekolah::factory()->create(['nama_sekolah' => 'SD Lain Pembanding Lomba']);
        BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 1,
            'tarif_harga' => 999000,
            'jumlah' => 999000,
        ]);

        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $html = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->html();

        $this->assertStringContainsString('Jumlah Biaya Pendaftaran Lomba/Bimtek/Workshop', $html);
        // 100.000 + 250.000 = 350.000 (total sekolah pertama, BUKAN
        // tercampur dengan 999.000 milik sekolah lain).
        $this->assertStringContainsString('Rp 350.000', $html);
    }

    /**
     * Baris Jumlah per sekolah HARUS ikut tersembunyi/tampil bersama detail
     * baris lain (x-show="terbuka", hanya terlihat saat grup dibuka lewat
     * simbol "+"). Kolom 1 s.d. 7 digabung jadi satu sel (colspan="7")
     * sesuai permintaan "kolom 1 sampai kolom 7 di merge cell".
     */
    public function test_baris_jumlah_per_sekolah_ikut_x_show_terbuka_dan_merge_kolom_1_sampai_7(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $html = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->html();

        $this->assertStringContainsString(
            'x-show="terbuka" x-cloak>'."\n".'                                        <td colspan="7" class="px-2 py-2 text-right">Jumlah Biaya Pendaftaran Lomba/Bimtek/Workshop</td>',
            $html
        );
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
        BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah1->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 200000,
        ]);

        $sekolah2 = ProfilSekolah::factory()->create();
        BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah2->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 300000,
        ]);

        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $html = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->html();

        $this->assertStringContainsString('Jumlah Biaya Pendaftaran Lomba/Bimtek/Workshop Seluruh Sekolah', $html);
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
        BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $html = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->html();

        $this->assertStringContainsString(
            '<tr class="bg-slate-200 font-bold text-slate-800 border-t-2 border-slate-400">'."\n".'                                        <td colspan="7" class="px-2 py-2.5 text-right">Jumlah Biaya Pendaftaran Lomba/Bimtek/Workshop Seluruh Sekolah</td>',
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
        ProfilSekolah::factory()->create(['nama_sekolah' => 'SD Tidak Akan Dicari Lomba']);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $html = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('search', 'Nama Sekolah Yang Tidak Pernah Ada Sama Sekali')
            ->html();

        $this->assertStringNotContainsString('Jumlah Biaya Pendaftaran Lomba/Bimtek/Workshop Seluruh Sekolah', $html);
    }
}
