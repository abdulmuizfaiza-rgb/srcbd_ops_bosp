<?php

namespace Tests\Feature;

use App\Livewire\PendataanBosp\PenerimaanHonorPtk\Index;
use App\Models\PendataanBosp;
use App\Models\PenerimaanHonorPtk;
use App\Models\ProfilSekolah;
use App\Models\User;
use App\Models\VervalRealisasiBosp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Menguji menu Penerimaan Honor PTK - Pendataan BOSP (2026-09-10):
 * CRUD lewat modal Tambah/Edit, input langsung di kotak tabel (auto-save
 * per kotak, sama seperti Rekap RKAS), rumus Jumlah Honor (Volume x Tarif
 * Harga), keunikan NUPTK per sekolah+tahun+triwulan, RBAC per sekolah, dan
 * Export/Import Excel.
 */
class PenerimaanHonorPtkTest extends TestCase
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
            'NPSN', 'Nama Sekolah', 'NUPTK', 'Nama Penerima', 'Volume',
            'Satuan', 'Tarif Harga', 'Jumlah Honor Yang Diterima', 'Tanggal Bayar',
        ], null, 'A1');
        $sheet->fromArray($baris, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'honor-ptk').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return UploadedFile::fake()->createWithContent('honor-ptk.xlsx', file_get_contents($path));
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
            ->set('nuptk', '1234567890123456')
            ->set('nama_penerima', 'Budi Santoso')
            ->set('volume', '3')
            ->set('satuan', 'OB')
            ->set('tarif_harga', '500000')
            ->set('tanggal_bayar', '2026-09-10')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('penerimaan_honor_ptk', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nuptk' => '1234567890123456',
            'nama_penerima' => 'Budi Santoso',
            'volume' => 3,
            'tarif_harga' => 500000,
            'jumlah_honor' => 1500000,
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
            ->set('nama_penerima', 'Siti')
            ->call('simpan');

        // profil_sekolah_id dikunci ulang ke sekolah sendiri di simpan(),
        // jadi baris tetap tersimpan tapi untuk sekolahnya sendiri, bukan
        // sekolah lain yang dicoba dipilih.
        $this->assertDatabaseHas('penerimaan_honor_ptk', [
            'profil_sekolah_id' => $sekolahSaya->id,
            'nama_penerima' => 'Siti',
        ]);
        $this->assertDatabaseMissing('penerimaan_honor_ptk', [
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
            ->set('nama_penerima', 'Ani')
            ->set('volume', '2')
            ->set('tarif_harga', '250000')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('penerimaan_honor_ptk', [
            'profil_sekolah_id' => $sekolah->id,
            'nama_penerima' => 'Ani',
            'jumlah_honor' => 500000,
        ]);
    }

    public function test_nuptk_wajib_maksimal_16_digit_angka(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('tambah')
            ->set('nuptk', 'ABCD')
            ->set('nama_penerima', 'Budi')
            ->call('simpan')
            ->assertHasErrors(['nuptk']);
    }

    public function test_nuptk_boleh_kurang_dari_16_digit(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('tambah')
            ->set('nuptk', '123')
            ->set('nama_penerima', 'Budi')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('penerimaan_honor_ptk', ['nuptk' => '123']);
    }

    public function test_nuptk_tidak_boleh_duplikat_pada_sekolah_tahun_triwulan_yang_sama(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nuptk' => '1111111111111111',
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('tambah')
            ->set('nuptk', '1111111111111111')
            ->set('nama_penerima', 'Duplikat')
            ->call('simpan')
            ->assertHasErrors(['nuptk']);
    }

    public function test_nuptk_boleh_sama_pada_triwulan_yang_berbeda(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nuptk' => '2222222222222222',
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('pindahTab', 2)
            ->call('tambah')
            ->set('nuptk', '2222222222222222')
            ->set('nama_penerima', 'Boleh Triwulan Lain')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('penerimaan_honor_ptk', 2);
    }

    public function test_beberapa_baris_boleh_tanpa_nuptk_sekaligus(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        $component->call('tambah')->set('nama_penerima', 'Tanpa NUPTK 1')->call('simpan')->assertHasNoErrors();
        $component->call('tambah')->set('nama_penerima', 'Tanpa NUPTK 2')->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseCount('penerimaan_honor_ptk', 2);
    }

    public function test_edit_baris_yang_sudah_ada_lewat_modal(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $baris = PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nama_penerima' => 'Sebelum Edit',
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('edit', $baris->id)
            ->set('nama_penerima', 'Sesudah Edit')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('penerimaan_honor_ptk', [
            'id' => $baris->id,
            'nama_penerima' => 'Sesudah Edit',
        ]);
        $this->assertDatabaseCount('penerimaan_honor_ptk', 1);
    }

    public function test_admin_bosp_tidak_bisa_mengedit_baris_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $baris = PenerimaanHonorPtk::factory()->create(['profil_sekolah_id' => $sekolahLain->id]);
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('edit', $baris->id)
            ->assertForbidden();
    }

    public function test_hapus_baris(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $baris = PenerimaanHonorPtk::factory()->create(['profil_sekolah_id' => $sekolah->id]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('konfirmasiHapus', $baris->id)
            ->call('hapus');

        $this->assertDatabaseMissing('penerimaan_honor_ptk', ['id' => $baris->id]);
    }

    public function test_admin_bosp_tidak_bisa_menghapus_baris_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $baris = PenerimaanHonorPtk::factory()->create(['profil_sekolah_id' => $sekolahLain->id]);
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('konfirmasiHapus', $baris->id)
            ->assertForbidden();

        $this->assertDatabaseHas('penerimaan_honor_ptk', ['id' => $baris->id]);
    }

    public function test_input_langsung_di_kotak_tabel_tersimpan_otomatis_dan_hitung_ulang_jumlah_honor(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $baris = PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 2,
            'tarif_harga' => 100000,
            'jumlah_honor' => 200000,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$baris->id}.volume", '5')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('penerimaan_honor_ptk', [
            'id' => $baris->id,
            'volume' => 5,
            'tarif_harga' => 100000,
            'jumlah_honor' => 500000, // 5 x 100.000, dihitung ulang otomatis
        ]);
    }

    public function test_admin_bosp_tidak_bisa_input_langsung_di_kotak_tabel_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $baris = PenerimaanHonorPtk::factory()->create(['profil_sekolah_id' => $sekolahLain->id]);
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$baris->id}.nama_penerima", 'Diselipkan')
            ->assertForbidden();

        $this->assertDatabaseMissing('penerimaan_honor_ptk', [
            'id' => $baris->id,
            'nama_penerima' => 'Diselipkan',
        ]);
    }

    public function test_input_langsung_nuptk_duplikat_di_kotak_tabel_ditolak(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nuptk' => '3333333333333333',
        ]);
        $barisLain = PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nuptk' => '4444444444444444',
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$barisLain->id}.nuptk", '3333333333333333');

        $component->assertHasErrors(["baris.{$barisLain->id}.nuptk"]);

        $this->assertDatabaseHas('penerimaan_honor_ptk', [
            'id' => $barisLain->id,
            'nuptk' => '4444444444444444', // tidak berubah
        ]);
    }

    public function test_input_langsung_nuptk_tidak_valid_tidak_tersimpan_dan_kotak_kembali_semula(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $baris = PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'nuptk' => '5555555555555555',
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$baris->id}.nuptk", 'bukan-angka');

        $component->assertHasErrors(["baris.{$baris->id}.nuptk"]);

        $this->assertDatabaseHas('penerimaan_honor_ptk', [
            'id' => $baris->id,
            'nuptk' => '5555555555555555',
        ]);
        $this->assertSame('5555555555555555', $component->get("baris.{$baris->id}.nuptk"));
    }

    public function test_jumlah_honor_tidak_bisa_diset_langsung_lewat_kotak_tabel(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $baris = PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'volume' => 2,
            'tarif_harga' => 100000,
            'jumlah_honor' => 200000,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // "jumlah_honor" sengaja TIDAK ada di daftarFieldEditable() -
        // dikirim lewat updated() tapi harus diabaikan sepenuhnya.
        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$baris->id}.jumlah_honor", '999999999')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('penerimaan_honor_ptk', [
            'id' => $baris->id,
            'jumlah_honor' => 200000, // tidak berubah
        ]);
    }

    public function test_admin_bosp_hanya_melihat_baris_sekolahnya_sendiri(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak Saya']);
        $sekolahLain = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak Lain']);
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolahSaya->id,
            'nama_penerima' => 'Punya Saya',
        ]);
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'nama_penerima' => 'Punya Lain',
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolahSaya->id]);

        $this->actingAs($adminBosp)
            ->get('/pendataan-bosp/penerimaan-honor-ptk')
            ->assertOk()
            ->assertSee('Punya Saya')
            ->assertDontSee('Punya Lain');
    }

    public function test_data_terisolasi_per_tahun_dan_triwulan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $triwulan1TahunIni = PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nama_penerima' => 'Triwulan 1 Tahun Ini',
        ]);
        $triwulan2TahunIni = PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 2,
            'nama_penerima' => 'Triwulan 2 Tahun Ini',
        ]);
        $triwulan1TahunLalu = PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year - 1,
            'triwulan' => 1,
            'nama_penerima' => 'Triwulan 1 Tahun Lalu',
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // Field-field editable-di-tabel (termasuk nama_penerima) diikat lewat
        // wire:model, bukan teks polos - jadi diperiksa lewat property
        // "baris" (array [rowId => data], hanya berisi baris yg sedang
        // tampil) bukan assertSee, sama seperti pola pengujian Rekap RKAS.
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

    public function test_link_penerimaan_honor_ptk_muncul_di_sidebar_setelah_identitas_bosp_lengkap(): void
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
            ->assertDontSee('Penerimaan Honor PTK');

        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $this->actingAs($adminBosp)
            ->get('/pendataan-bosp')
            ->assertOk()
            ->assertSee('Penerimaan Honor PTK');
    }

    public function test_superadmin_wajib_pilih_sekolah_dulu_sebelum_export(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PenerimaanHonorPtk::factory()->create(['profil_sekolah_id' => $sekolah->id]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('export')
            ->assertSet('errorExport', fn ($pesan) => ! empty($pesan));
    }

    public function test_superadmin_bisa_export_setelah_memilih_sekolah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PenerimaanHonorPtk::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('filterSekolahId', $sekolah->id)
            ->call('export')
            ->assertFileDownloaded('penerimaan-honor-ptk-triwulan-1-'.now()->year.'.xlsx');
    }

    public function test_admin_bosp_bisa_export_tanpa_pilih_sekolah_karena_sudah_terkunci(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PenerimaanHonorPtk::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('export')
            ->assertFileDownloaded('penerimaan-honor-ptk-triwulan-1-'.now()->year.'.xlsx');
    }

    public function test_import_menambah_baris_baru_untuk_superadmin_berdasarkan_npsn(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $file = $this->buatFileExcel([
            [$sekolah->npsn, $sekolah->nama_sekolah, '6666666666666666', 'Rina', 4, 'OB', 200000, 800000, '10-09-2026'],
        ]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('fileImport', $file)
            ->call('import')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('penerimaan_honor_ptk', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nuptk' => '6666666666666666',
            'nama_penerima' => 'Rina',
            'volume' => 4,
            'tarif_harga' => 200000,
            'jumlah_honor' => 800000, // dihitung ulang sendiri, bukan diambil dari kolom Excel
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
            [$sekolahLain->npsn, $sekolahLain->nama_sekolah, '7777777777777777', 'Joko', 2, 'OB', 100000, 200000, '10-09-2026'],
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('fileImport', $file)
            ->call('import')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('penerimaan_honor_ptk', [
            'profil_sekolah_id' => $sekolahSaya->id,
            'nuptk' => '7777777777777777',
        ]);
        $this->assertDatabaseMissing('penerimaan_honor_ptk', [
            'profil_sekolah_id' => $sekolahLain->id,
        ]);
    }

    public function test_import_ulang_memperbarui_baris_yang_sudah_ada_berdasarkan_nuptk(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nuptk' => '8888888888888888',
            'nama_penerima' => 'Nama Lama',
            'volume' => 1,
            'tarif_harga' => 100000,
            'jumlah_honor' => 100000,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $file = $this->buatFileExcel([
            [$sekolah->npsn, $sekolah->nama_sekolah, '8888888888888888', 'Nama Baru', 6, 'OB', 150000, 900000, '10-09-2026'],
        ]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('fileImport', $file)
            ->call('import')
            ->assertHasNoErrors();

        // Baris yang sama (kunci profil_sekolah_id+tahun+triwulan+nuptk)
        // ter-UPDATE, bukan menjadi baris baru/duplikat.
        $this->assertDatabaseCount('penerimaan_honor_ptk', 1);
        $this->assertDatabaseHas('penerimaan_honor_ptk', [
            'profil_sekolah_id' => $sekolah->id,
            'nuptk' => '8888888888888888',
            'nama_penerima' => 'Nama Baru',
            'volume' => 6,
            'tarif_harga' => 150000,
            'jumlah_honor' => 900000,
        ]);
    }

    public function test_baris_placeholder_kosong_selalu_tampil_untuk_setiap_sekolah_superadmin(): void
    {
        // Sesuai jawaban AskUserQuestion 2026-09-10 ("Ya, seperti itu") -
        // tabel Superadmin selalu menampilkan 1 baris kosong siap-isi
        // untuk SETIAP sekolah, walau belum ada data PTK sama sekali.
        $sekolah1 = ProfilSekolah::factory()->create();
        $sekolah2 = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $baris = Livewire::actingAs($superadmin)->test(Index::class)->get('baris');

        $this->assertArrayHasKey(-$sekolah1->id, $baris);
        $this->assertArrayHasKey(-$sekolah2->id, $baris);
        $this->assertSame('', $baris[-$sekolah1->id]['nama_penerima']);
        $this->assertSame('', $baris[-$sekolah2->id]['nama_penerima']);
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

    public function test_mengisi_kotak_placeholder_membuat_baris_ptk_baru(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $idPlaceholder = -$sekolah->id;

        $component = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$idPlaceholder}.nama_penerima", 'Dibuat Lewat Placeholder')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('penerimaan_honor_ptk', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nama_penerima' => 'Dibuat Lewat Placeholder',
            'jumlah_honor' => 0,
        ]);

        // Baris placeholder-nya sendiri harus kembali kosong lagi setelah
        // baris baru dibuat (siap dipakai untuk PTK berikutnya), sesuai
        // jawaban AskUserQuestion "Otomatis muncul baris kosong baru lagi".
        $baris = $component->get('baris');
        $this->assertSame('', $baris[$idPlaceholder]['nama_penerima']);
    }

    public function test_setelah_baris_placeholder_terisi_bisa_langsung_diisi_lagi_untuk_ptk_berikutnya(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $idPlaceholder = -$sekolah->id;

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$idPlaceholder}.nama_penerima", 'PTK Pertama')
            ->set("baris.{$idPlaceholder}.nama_penerima", 'PTK Kedua')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('penerimaan_honor_ptk', 2);
        $this->assertDatabaseHas('penerimaan_honor_ptk', ['nama_penerima' => 'PTK Pertama']);
        $this->assertDatabaseHas('penerimaan_honor_ptk', ['nama_penerima' => 'PTK Kedua']);
    }

    public function test_admin_bosp_tidak_bisa_mengisi_placeholder_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);
        $idPlaceholder = -$sekolahLain->id;

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$idPlaceholder}.nama_penerima", 'Diselipkan')
            ->assertForbidden();

        $this->assertDatabaseMissing('penerimaan_honor_ptk', [
            'profil_sekolah_id' => $sekolahLain->id,
        ]);
    }

    public function test_placeholder_nuptk_duplikat_ditolak_dan_tidak_membuat_baris(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nuptk' => '9999999999999999',
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $idPlaceholder = -$sekolah->id;

        $component = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$idPlaceholder}.nuptk", '9999999999999999');

        $component->assertHasErrors(["baris.{$idPlaceholder}.nuptk"]);
        $this->assertDatabaseCount('penerimaan_honor_ptk', 1);
    }

    public function test_placeholder_nuptk_tidak_valid_tidak_membuat_baris(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $idPlaceholder = -$sekolah->id;

        $component = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$idPlaceholder}.nuptk", 'bukan-angka');

        $component->assertHasErrors(["baris.{$idPlaceholder}.nuptk"]);
        $this->assertDatabaseCount('penerimaan_honor_ptk', 0);
    }

    public function test_daftar_sekolah_diurutkan_negeri_dulu_baru_swasta(): void
    {
        // Permintaan user 2026-09-10 poin 3: "daftar sekolahnya urutkan
        // sesuai status Negeri terus swasta" - pola SAMA PERSIS dengan
        // ProfilSekolah::Index/PendataanOps::Index/PendataanBosp::Index/
        // RekapRkas::Index (bukan aturan baru, disamakan supaya konsisten).
        // Nama sekolah sengaja dibuat TERBALIK secara alfabet dari urutan
        // status yang diharapkan (swasta "A..." dibuat lebih dulu, negeri
        // "Z..." belakangan) supaya test ini gagal kalau urutan sebenarnya
        // masih berdasar nama_sekolah semata.
        $swasta = ProfilSekolah::factory()->create(['nama_sekolah' => 'A SD Swasta', 'status' => ProfilSekolah::STATUS_SWASTA]);
        $negeri = ProfilSekolah::factory()->create(['nama_sekolah' => 'Z SD Negeri', 'status' => ProfilSekolah::STATUS_NEGERI]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $daftarSekolah = Livewire::actingAs($superadmin)->test(Index::class)->viewData('daftarSekolah');

        $this->assertSame([$negeri->id, $swasta->id], $daftarSekolah->pluck('id')->all());
    }

    public function test_import_baris_tanpa_nuptk_selalu_masuk_sebagai_baris_baru(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nuptk' => null,
            'nama_penerima' => 'Sudah Ada Tanpa NUPTK',
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $file = $this->buatFileExcel([
            [$sekolah->npsn, $sekolah->nama_sekolah, '', 'Baru Tanpa NUPTK', 2, 'OB', 100000, 200000, '10-09-2026'],
        ]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('fileImport', $file)
            ->call('import')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('penerimaan_honor_ptk', 2);
        $this->assertDatabaseHas('penerimaan_honor_ptk', ['nama_penerima' => 'Baru Tanpa NUPTK']);
    }

    /**
     * Sesuai permintaan user 2026-09-10 poin 3 & jawaban AskUserQuestion
     * "Tabel diringkas per sekolah, + untuk buka/tutup (Recommended)":
     * tabel sekarang menampilkan 1 baris ringkasan per sekolah (dengan
     * jumlah data PTK yang sudah diinput), bukan lagi menampilkan semua
     * baris PTK langsung. Baris ringkasan ini memakai <tbody x-data> +
     * simbol +/- (bukan lagi simbol + polos di Kolom No seperti dulu).
     */
    public function test_tabel_menampilkan_ringkasan_jumlah_data_ptk_per_sekolah(): void
    {
        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SD Ringkasan Uji']);
        PenerimaanHonorPtk::factory()->count(2)->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->assertSee('2 data PTK sudah diinput')
            ->assertSee('SD Ringkasan Uji');
    }

    public function test_sekolah_tanpa_data_ptk_menampilkan_ringkasan_nol_data(): void
    {
        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SD Kosong Uji']);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->assertSee('0 data PTK sudah diinput')
            ->assertSee('SD Kosong Uji');
    }

    /**
     * Baris detail (baris PTK sungguhan + baris placeholder kosong siap-isi)
     * harus tersembunyi secara default - disembunyikan lewat Alpine
     * x-show="terbuka" dengan x-data awal { terbuka: false } pada <tbody>
     * per sekolah, BUKAN dihapus dari HTML (supaya data tetap ada & bisa
     * langsung dibuka tanpa request tambahan ke server).
     */
    public function test_baris_detail_ptk_disembunyikan_secara_default_lewat_x_show(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PenerimaanHonorPtk::factory()->create([
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
        // Permintaan user 2026-09-10 poin 4: tombol aksi Edit/Hapus untuk
        // baris data yang SUDAH ADA isinya harus tetap berfungsi walau
        // sekarang disembunyikan di balik ringkasan per sekolah.
        $sekolah = ProfilSekolah::factory()->create();
        $baris = PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'nama_penerima' => 'PTK Untuk Diedit',
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('edit', $baris->id)
            ->assertSet('editingId', $baris->id)
            ->assertSet('nama_penerima', 'PTK Untuk Diedit')
            ->call('konfirmasiHapus', $baris->id)
            ->assertSet('confirmingDeleteId', $baris->id)
            ->call('hapus');

        $this->assertDatabaseMissing('penerimaan_honor_ptk', ['id' => $baris->id]);
    }

    /**
     * Permintaan user: "tambahkan baris Total Jumlah Honor Yang Diterima
     * (merge cell dari kolom 1 sampai kolom 8) pada kolom Jumlah Honor
     * Yang Diterima dari setiap sekolah dan untuk seluruh sekolah."
     *
     * Baris Total PER SEKOLAH menjumlahkan HANYA baris PTK milik sekolah
     * itu sendiri (tahun+triwulan yang sedang aktif), bukan gabungan
     * semua sekolah.
     */
    public function test_baris_total_per_sekolah_menjumlahkan_hanya_baris_sekolah_itu_sendiri(): void
    {
        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SD Total Per Sekolah Uji']);
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 1,
            'tarif_harga' => 100000,
            'jumlah_honor' => 100000,
        ]);
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 1,
            'tarif_harga' => 250000,
            'jumlah_honor' => 250000,
        ]);

        // Sekolah lain - jumlahnya TIDAK BOLEH ikut ke total sekolah di atas.
        $sekolahLain = ProfilSekolah::factory()->create(['nama_sekolah' => 'SD Lain Pembanding']);
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 1,
            'tarif_harga' => 999000,
            'jumlah_honor' => 999000,
        ]);

        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $html = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->html();

        $this->assertStringContainsString('Total Jumlah Honor Yang Diterima', $html);
        // 100.000 + 250.000 = 350.000 (total sekolah pertama, BUKAN tercampur
        // dengan 999.000 milik sekolah lain).
        $this->assertStringContainsString('Rp 350.000', $html);
    }

    /**
     * Baris Total per sekolah HARUS ikut tersembunyi/tampil bersama detail
     * baris lain (x-show="terbuka", hanya terlihat saat grup dibuka lewat
     * simbol "+") - jawaban AskUserQuestion "Hanya saat grup dibuka" -
     * BUKAN selalu tampil di baris ringkasan yang masih tertutup. Kolom
     * 1 s.d. 8 digabung jadi satu sel (colspan="8") sesuai permintaan
     * "merge cell dari kolom 1 sampai kolom 8".
     */
    public function test_baris_total_per_sekolah_ikut_x_show_terbuka_dan_merge_kolom_1_sampai_8(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $html = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->html();

        $this->assertStringContainsString(
            'x-show="terbuka" x-cloak>'."\n".'                                        <td colspan="8" class="px-2 py-2 text-right">Total Jumlah Honor Yang Diterima</td>',
            $html
        );
    }

    /**
     * Baris Total UNTUK SELURUH SEKOLAH menjumlahkan gabungan seluruh
     * sekolah yang SEDANG DITAMPILKAN, dan SELALU terlihat (bukan
     * bagian dari grup yang bisa ditutup) - beda dengan baris Total
     * per sekolah di atas.
     */
    public function test_baris_total_keseluruhan_menjumlahkan_semua_sekolah_yang_ditampilkan(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah1->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah_honor' => 200000,
        ]);

        $sekolah2 = ProfilSekolah::factory()->create();
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah2->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah_honor' => 300000,
        ]);

        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $html = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->html();

        $this->assertStringContainsString('Total Jumlah Honor Yang Diterima Seluruh Sekolah', $html);
        // 200.000 + 300.000 = 500.000.
        $this->assertStringContainsString('Rp 500.000', $html);
    }

    /**
     * Baris Total keseluruhan HARUS selalu tampil (TIDAK ikut x-show
     * "terbuka" seperti baris Total per sekolah) - dicek lewat memastikan
     * baris ini TIDAK berada di dalam <tr> yang memakai atribut x-show.
     */
    public function test_baris_total_keseluruhan_selalu_tampil_tidak_ikut_x_show(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $html = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->html();

        $this->assertStringContainsString(
            '<tr class="bg-slate-200 font-bold text-slate-800 border-t-2 border-slate-400">'."\n".'                                        <td colspan="8" class="px-2 py-2.5 text-right">Total Jumlah Honor Yang Diterima Seluruh Sekolah</td>',
            $html
        );
    }

    /**
     * Admin BOSP hanya melihat sekolahnya sendiri - baris Total keseluruhan
     * pun HARUS hanya menjumlahkan sekolahnya sendiri, TIDAK ikut sekolah
     * lain yang tidak berhak dilihatnya (konsisten dengan RBAC menu ini).
     */
    public function test_baris_total_keseluruhan_admin_bosp_hanya_menghitung_sekolahnya_sendiri(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolahSaya->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah_honor' => 150000,
        ]);

        $sekolahLain = ProfilSekolah::factory()->create();
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah_honor' => 999000,
        ]);

        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        $html = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->html();

        $this->assertStringContainsString('Rp 150.000', $html);
        $this->assertStringNotContainsString('Rp 999.000', $html);
        $this->assertStringNotContainsString('Rp 1.149.000', $html);
    }

    // ------------------------------------------------------------------
    // Kuncian UI setelah Validasi Hasil Entry Data BOSP "Sesuai" -
    // permintaan user 2026-09-23 (round kesepuluh, poin 1b). Menu ini
    // dipakai sebagai menu REPRESENTATIF untuk menguji
    // App\Livewire\Concerns\MenolakEditJikaTerkunciVerval::
    // terkunciVervalUntukTampilan() - kesembilan menu sumber lain (+
    // FormulirBosK7 & PajakBospReguler) memakai method yang SAMA persis
    // jadi tidak diulang satu-satu di sini.
    // ------------------------------------------------------------------

    public function test_kotak_dan_tombol_terkunci_setelah_triwulan_divalidasi_sesuai(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

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
            ->set('tahun', $tahun)
            ->set('triwulan', 1)
            ->assertViewHas('terkunciTriwulanIni', true)
            ->assertSee('sudah divalidasi')
            ->assertSee('terkunci permanen');

        // Tombol Tambah & Import harus punya atribut HTML disabled asli
        // (bukan cuma dikunci lewat pointer-events-none di kotaknya) -
        // lihat :disabled="$terkunciTriwulanIni" di Blade-nya.
        $this->assertMatchesRegularExpression(
            '/wire:click="tambah"[^>]*disabled/',
            $component->html()
        );

        // Tombol Tambah dipanggil langsung (bukan lewat klik tombol UI)
        // tetap ditolak backend - dua lapis pertahanan (UI + backend)
        // tetap konsisten setelah kuncian UI ditambahkan.
        $component->call('tambah')
            ->set('nuptk', '1234567890123456')
            ->set('nama_penerima', 'Budi Santoso')
            ->set('volume', '3')
            ->set('satuan', 'OB')
            ->set('tarif_harga', '500000')
            ->set('tanggal_bayar', '2026-09-10')
            ->call('simpan')
            ->assertForbidden();
    }

    public function test_kotak_tidak_terkunci_sebelum_triwulan_divalidasi(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('triwulan', 1)
            ->assertViewHas('terkunciTriwulanIni', false)
            ->assertDontSee('sudah divalidasi');
    }

    public function test_kotak_tidak_terkunci_untuk_triwulan_yang_belum_sesuai(): void
    {
        // "Belum Sesuai" BEDA dari "Sesuai" - hanya "Sesuai" yang
        // mengunci data (konsisten dengan abortJikaTerkunciVerval() yang
        // sudah ada sejak sebelum round kesepuluh).
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => $tahun,
            'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_BELUM_SESUAI,
            'diverval_oleh' => $adminBosp->id,
            'diverval_pada' => now(),
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('tahun', $tahun)
            ->set('triwulan', 1)
            ->assertViewHas('terkunciTriwulanIni', false)
            ->assertDontSee('sudah divalidasi');
    }

    public function test_triwulan_lain_tidak_ikut_terkunci(): void
    {
        // Kuncian PER TRIWULAN, bukan per sekolah - TW1 sesuai tidak
        // boleh ikut mengunci TW2.
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => $tahun,
            'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => $adminBosp->id,
            'diverval_pada' => now(),
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('tahun', $tahun)
            ->set('triwulan', 2)
            ->assertViewHas('terkunciTriwulanIni', false)
            ->assertDontSee('sudah divalidasi');
    }

    public function test_superadmin_tidak_pernah_terkunci_walau_triwulan_sudah_sesuai(): void
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

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('tahun', $tahun)
            ->set('triwulan', 1)
            ->set('filterSekolahId', $sekolah->id)
            ->assertViewHas('terkunciTriwulanIni', false)
            ->assertDontSee('sudah divalidasi');
    }
}
