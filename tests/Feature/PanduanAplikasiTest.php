<?php

namespace Tests\Feature;

use App\Livewire\PanduanAplikasi\Index;
use App\Models\PanduanAplikasi;
use App\Models\PanduanAplikasiFile;
use App\Models\PanduanAplikasiLink;
use App\Models\PendataanOps;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menu "Panduan Aplikasi" (round 24, poin 6; direstrukturisasi round DUA
 * PULUH LIMA / 2026-09-24, poin 1, permintaan user "upload file lebih dari
 * 1 dengan Judul Book Manual yang sama... termasuk juga untuk link google
 * drive nya juga") - HANYA Superadmin (Gate 'akses-panduan-aplikasi'),
 * lihat App\Models\PanduanAplikasi & App\Livewire\PanduanAplikasi\Index
 * utk detail keputusan bisnis (jawaban AskUserQuestion 2026-09-24).
 */
class PanduanAplikasiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin OPS yang sudah melewati EnsureOnboardingComplete (Profil
     * Sekolah lengkap + Identitas OPS sudah diisi) - supaya request ke
     * route yang di-Gate benar-benar sampai ke pengecekan Gate itu
     * sendiri (403), bukan ke-redirect duluan (302) oleh middleware
     * onboarding - pola sama dgn TimelinePekerjaanTest.
     */
    private function adminOpsLengkap(): User
    {
        $sekolah = ProfilSekolah::factory()->create();
        $sekolah->update([
            'nama_kepala_sekolah' => 'Kepsek', 'nip_kepala_sekolah' => '1', 'no_whatsapp_kepala_sekolah' => '0812',
            'status_kepegawaian_kepsek' => 'PNS', 'nama_pengawas' => 'P', 'nip_pengawas' => '2',
            'nama_bendahara' => 'B', 'nip_bendahara' => '3', 'status_kepegawaian_bendahara' => 'PNS',
            'alamat_sekolah' => 'Jl. X',
        ]);
        $admin = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanOps::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        return $admin;
    }

    public function test_hanya_superadmin_bisa_akses_menu_panduan_aplikasi(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $this->actingAs($superadmin)
            ->get(route('panduan-aplikasi.index'))
            ->assertOk();
    }

    public function test_admin_ops_tidak_bisa_akses_menu_panduan_aplikasi(): void
    {
        $adminOps = $this->adminOpsLengkap();

        $this->actingAs($adminOps)->get(route('panduan-aplikasi.index'))->assertForbidden();
    }

    public function test_menu_panduan_aplikasi_tidak_tampil_di_sidebar_utk_admin_ops(): void
    {
        $adminOps = $this->adminOpsLengkap();

        $this->actingAs($adminOps)
            ->get(route('pendataan-ops.index'))
            ->assertOk()
            ->assertDontSee('Panduan Aplikasi');
    }

    public function test_tambah_panduan_dengan_link_saja(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('judul', 'Panduan Input BOS K7')
            ->set('deskripsi', 'Cara mengisi Formulir BOS K7b & K7c.')
            ->set('linkBaruList', ['https://drive.google.com/file/d/contoh'])
            ->call('simpan')
            ->assertHasNoErrors();

        $panduan = PanduanAplikasi::where('judul', 'Panduan Input BOS K7')->firstOrFail();
        $this->assertSame(1, $panduan->links()->count());
        $this->assertSame(0, $panduan->files()->count());
        $this->assertDatabaseHas('panduan_aplikasi_link', [
            'panduan_aplikasi_id' => $panduan->id,
            'link_drive' => 'https://drive.google.com/file/d/contoh',
        ]);
    }

    public function test_tambah_panduan_dengan_banyak_link_sekaligus(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('judul', 'Panduan Banyak Link')
            ->set('linkBaruList', [
                'https://drive.google.com/file/d/satu',
                'https://drive.google.com/file/d/dua',
                '',
            ])
            ->call('simpan')
            ->assertHasNoErrors();

        $panduan = PanduanAplikasi::where('judul', 'Panduan Banyak Link')->firstOrFail();
        $this->assertSame(2, $panduan->links()->count());
    }

    public function test_tambah_panduan_dengan_file_saja(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $file = UploadedFile::fake()->create('panduan.pdf', 500, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('judul', 'Panduan Surat TPG')
            ->set('fileBaruList', [$file])
            ->call('simpan')
            ->assertHasNoErrors();

        $panduan = PanduanAplikasi::where('judul', 'Panduan Surat TPG')->firstOrFail();
        $this->assertSame(1, $panduan->files()->count());
        $fileTersimpan = $panduan->files()->first();
        $this->assertSame('panduan.pdf', $fileTersimpan->file_nama_asli);
        Storage::disk('public')->assertExists($fileTersimpan->file_path);
    }

    public function test_tambah_panduan_dengan_banyak_file_sekaligus(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $fileSatu = UploadedFile::fake()->create('satu.pdf', 200, 'application/pdf');
        $fileDua = UploadedFile::fake()->create('dua.pdf', 300, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('judul', 'Panduan Banyak File')
            ->set('fileBaruList', [$fileSatu, $fileDua])
            ->call('simpan')
            ->assertHasNoErrors();

        $panduan = PanduanAplikasi::where('judul', 'Panduan Banyak File')->firstOrFail();
        $this->assertSame(2, $panduan->files()->count());
    }

    public function test_tambah_panduan_dengan_kombinasi_file_dan_link(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $file = UploadedFile::fake()->create('gabungan.pdf', 200, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('judul', 'Panduan Gabungan')
            ->set('fileBaruList', [$file])
            ->set('linkBaruList', ['https://drive.google.com/file/d/gabungan'])
            ->call('simpan')
            ->assertHasNoErrors();

        $panduan = PanduanAplikasi::where('judul', 'Panduan Gabungan')->firstOrFail();
        $this->assertSame(1, $panduan->files()->count());
        $this->assertSame(1, $panduan->links()->count());
    }

    public function test_tambah_panduan_ditolak_jika_link_dan_file_kosong_dua_duanya(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('judul', 'Panduan Tanpa Apa-apa')
            ->call('simpan')
            ->assertHasErrors('linkBaruList.0');

        $this->assertDatabaseMissing('panduan_aplikasi', ['judul' => 'Panduan Tanpa Apa-apa']);
    }

    public function test_ekstensi_file_di_luar_daftar_ditolak(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $file = UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload');

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('judul', 'Panduan Salah Format')
            ->set('fileBaruList', [$file])
            ->call('simpan')
            ->assertHasErrors('fileBaruList.0');
    }

    public function test_file_lebih_dari_20mb_ditolak(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $file = UploadedFile::fake()->create('besar.pdf', 21 * 1024, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('judul', 'Panduan Terlalu Besar')
            ->set('fileBaruList', [$file])
            ->call('simpan')
            ->assertHasErrors('fileBaruList.0');
    }

    public function test_edit_panduan_ganti_judul_dan_deskripsi(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $panduan = PanduanAplikasi::factory()->create(['judul' => 'Judul Lama']);
        PanduanAplikasiLink::factory()->create(['panduan_aplikasi_id' => $panduan->id]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('edit', $panduan->id)
            ->set('judul', 'Judul Baru')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('panduan_aplikasi', [
            'id' => $panduan->id,
            'judul' => 'Judul Baru',
        ]);
    }

    public function test_tambah_file_baru_ke_judul_yang_sudah_ada_saat_edit(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $panduan = PanduanAplikasi::factory()->create();
        PanduanAplikasiLink::factory()->create(['panduan_aplikasi_id' => $panduan->id]);
        $fileBaru = UploadedFile::fake()->create('tambahan.pdf', 100, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('edit', $panduan->id)
            ->set('fileBaruList', [$fileBaru])
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertSame(1, $panduan->files()->count());
    }

    public function test_tambah_link_baru_ke_judul_yang_sudah_ada_saat_edit(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $panduan = PanduanAplikasi::factory()->create();
        PanduanAplikasiFile::factory()->create(['panduan_aplikasi_id' => $panduan->id, 'file_path' => 'panduan-aplikasi/sudah-ada.pdf']);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('edit', $panduan->id)
            ->set('linkBaruList', ['https://drive.google.com/file/d/baru'])
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertSame(1, $panduan->links()->count());
    }

    public function test_hapus_file_individual_tidak_mempengaruhi_file_atau_link_lain(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $panduan = PanduanAplikasi::factory()->create();
        $path = UploadedFile::fake()->create('dihapus.pdf', 100, 'application/pdf')->store('panduan-aplikasi', 'public');
        $fileDihapus = PanduanAplikasiFile::factory()->create(['panduan_aplikasi_id' => $panduan->id, 'file_path' => $path]);
        $fileDipertahankan = PanduanAplikasiFile::factory()->create(['panduan_aplikasi_id' => $panduan->id, 'file_path' => 'panduan-aplikasi/tetap-ada.pdf']);
        $linkDipertahankan = PanduanAplikasiLink::factory()->create(['panduan_aplikasi_id' => $panduan->id]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('hapusFile', $fileDihapus->id);

        $this->assertDatabaseMissing('panduan_aplikasi_file', ['id' => $fileDihapus->id]);
        $this->assertDatabaseHas('panduan_aplikasi_file', ['id' => $fileDipertahankan->id]);
        $this->assertDatabaseHas('panduan_aplikasi_link', ['id' => $linkDipertahankan->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_hapus_file_ditolak_kalau_itu_satu_satunya_file_dan_link(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $panduan = PanduanAplikasi::factory()->create();
        $path = UploadedFile::fake()->create('satu-satunya.pdf', 100, 'application/pdf')->store('panduan-aplikasi', 'public');
        $file = PanduanAplikasiFile::factory()->create(['panduan_aplikasi_id' => $panduan->id, 'file_path' => $path]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('hapusFile', $file->id);

        $this->assertDatabaseHas('panduan_aplikasi_file', ['id' => $file->id]);
        Storage::disk('public')->assertExists($path);
    }

    public function test_hapus_link_individual_tidak_mempengaruhi_file_atau_link_lain(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $panduan = PanduanAplikasi::factory()->create();
        $linkDihapus = PanduanAplikasiLink::factory()->create(['panduan_aplikasi_id' => $panduan->id]);
        $linkDipertahankan = PanduanAplikasiLink::factory()->create(['panduan_aplikasi_id' => $panduan->id]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('hapusLink', $linkDihapus->id);

        $this->assertDatabaseMissing('panduan_aplikasi_link', ['id' => $linkDihapus->id]);
        $this->assertDatabaseHas('panduan_aplikasi_link', ['id' => $linkDipertahankan->id]);
    }

    public function test_hapus_link_ditolak_kalau_itu_satu_satunya_file_dan_link(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $panduan = PanduanAplikasi::factory()->create();
        $link = PanduanAplikasiLink::factory()->create(['panduan_aplikasi_id' => $panduan->id]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('hapusLink', $link->id);

        $this->assertDatabaseHas('panduan_aplikasi_link', ['id' => $link->id]);
    }

    public function test_tambah_dan_hapus_baris_link_baru_di_form(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('tambahLinkBaru')
            ->call('tambahLinkBaru');

        $this->assertCount(3, $component->get('linkBaruList'));

        $component->call('hapusLinkBaruBaris', 1);
        $this->assertCount(2, $component->get('linkBaruList'));
    }

    public function test_hapus_panduan_ikut_menghapus_semua_file_dari_storage(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $panduan = PanduanAplikasi::factory()->create();
        $pathSatu = UploadedFile::fake()->create('satu.pdf', 100, 'application/pdf')->store('panduan-aplikasi', 'public');
        $pathDua = UploadedFile::fake()->create('dua.pdf', 100, 'application/pdf')->store('panduan-aplikasi', 'public');
        PanduanAplikasiFile::factory()->create(['panduan_aplikasi_id' => $panduan->id, 'file_path' => $pathSatu]);
        PanduanAplikasiFile::factory()->create(['panduan_aplikasi_id' => $panduan->id, 'file_path' => $pathDua]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('konfirmasiHapus', $panduan->id)
            ->call('hapus');

        $this->assertDatabaseMissing('panduan_aplikasi', ['id' => $panduan->id]);
        $this->assertDatabaseMissing('panduan_aplikasi_file', ['panduan_aplikasi_id' => $panduan->id]);
        Storage::disk('public')->assertMissing($pathSatu);
        Storage::disk('public')->assertMissing($pathDua);
    }

    public function test_filter_book_manual_deskripsi_dan_tanggal_upload(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $alpha = PanduanAplikasi::factory()->create(['judul' => 'Panduan Alpha', 'deskripsi' => 'Tentang Alpha']);
        $beta = PanduanAplikasi::factory()->create(['judul' => 'Panduan Beta', 'deskripsi' => 'Tentang Beta']);
        PanduanAplikasiLink::factory()->create(['panduan_aplikasi_id' => $alpha->id]);
        PanduanAplikasiLink::factory()->create(['panduan_aplikasi_id' => $beta->id]);

        $component = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('search', 'Alpha');

        $component->assertSee('Panduan Alpha')->assertDontSee('Panduan Beta');

        $component->set('search', '')->set('searchDeskripsi', 'Beta');
        $component->assertSee('Panduan Beta')->assertDontSee('Panduan Alpha');
    }

    public function test_unduh_file_panduan_berhasil_utk_superadmin(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $panduan = PanduanAplikasi::factory()->create();
        $path = UploadedFile::fake()->create('unduh-ini.pdf', 100, 'application/pdf')->store('panduan-aplikasi', 'public');
        $file = PanduanAplikasiFile::factory()->create(['panduan_aplikasi_id' => $panduan->id, 'file_path' => $path, 'file_nama_asli' => 'unduh-ini.pdf']);

        $this->actingAs($superadmin)
            ->get(route('panduan-aplikasi.file.unduh', $file))
            ->assertOk();
    }

    public function test_unduh_file_panduan_ditolak_utk_admin_ops(): void
    {
        Storage::fake('public');
        $adminOps = $this->adminOpsLengkap();
        $panduan = PanduanAplikasi::factory()->create();
        $path = UploadedFile::fake()->create('rahasia.pdf', 100, 'application/pdf')->store('panduan-aplikasi', 'public');
        $file = PanduanAplikasiFile::factory()->create(['panduan_aplikasi_id' => $panduan->id, 'file_path' => $path, 'file_nama_asli' => 'rahasia.pdf']);

        $this->actingAs($adminOps)
            ->get(route('panduan-aplikasi.file.unduh', $file))
            ->assertForbidden();
    }

    public function test_unduh_file_panduan_yang_tidak_ada_di_storage_menghasilkan_404(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $panduan = PanduanAplikasi::factory()->create();
        $file = PanduanAplikasiFile::factory()->create(['panduan_aplikasi_id' => $panduan->id, 'file_path' => 'panduan-aplikasi/tidak-ada.pdf']);

        $this->actingAs($superadmin)
            ->get(route('panduan-aplikasi.file.unduh', $file))
            ->assertNotFound();
    }

    public function test_unduh_semua_mengunduh_zip_berisi_semua_file(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $panduan = PanduanAplikasi::factory()->create();
        $pathSatu = UploadedFile::fake()->create('satu.pdf', 50, 'application/pdf')->store('panduan-aplikasi', 'public');
        $pathDua = UploadedFile::fake()->create('dua.pdf', 50, 'application/pdf')->store('panduan-aplikasi', 'public');
        PanduanAplikasiFile::factory()->create(['panduan_aplikasi_id' => $panduan->id, 'file_path' => $pathSatu, 'file_nama_asli' => 'satu.pdf']);
        PanduanAplikasiFile::factory()->create(['panduan_aplikasi_id' => $panduan->id, 'file_path' => $pathDua, 'file_nama_asli' => 'dua.pdf']);

        $response = $this->actingAs($superadmin)->get(route('panduan-aplikasi.unduh-semua', $panduan));

        $response->assertOk();

        $path = $response->getFile()->getPathname();
        $zip = new \ZipArchive;
        $zip->open($path);
        $this->assertSame(2, $zip->numFiles);
        $namaDiZip = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $namaDiZip[] = $zip->getNameIndex($i);
        }
        $zip->close();
        sort($namaDiZip);
        $this->assertSame(['dua.pdf', 'satu.pdf'], $namaDiZip);
    }

    public function test_unduh_semua_menghindari_nama_file_sama_saling_menimpa_di_dalam_zip(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $panduan = PanduanAplikasi::factory()->create();
        $pathSatu = UploadedFile::fake()->create('panduan.pdf', 50, 'application/pdf')->store('panduan-aplikasi', 'public');
        $pathDua = UploadedFile::fake()->create('panduan.pdf', 50, 'application/pdf')->store('panduan-aplikasi', 'public');
        PanduanAplikasiFile::factory()->create(['panduan_aplikasi_id' => $panduan->id, 'file_path' => $pathSatu, 'file_nama_asli' => 'panduan.pdf']);
        PanduanAplikasiFile::factory()->create(['panduan_aplikasi_id' => $panduan->id, 'file_path' => $pathDua, 'file_nama_asli' => 'panduan.pdf']);

        $response = $this->actingAs($superadmin)->get(route('panduan-aplikasi.unduh-semua', $panduan));

        $zip = new \ZipArchive;
        $zip->open($response->getFile()->getPathname());
        $this->assertSame(2, $zip->numFiles);
        $namaDiZip = [$zip->getNameIndex(0), $zip->getNameIndex(1)];
        $zip->close();

        $this->assertContains('panduan.pdf', $namaDiZip);
        $this->assertContains('panduan (1).pdf', $namaDiZip);
    }

    public function test_unduh_semua_ditolak_utk_admin_ops(): void
    {
        Storage::fake('public');
        $adminOps = $this->adminOpsLengkap();
        $panduan = PanduanAplikasi::factory()->create();
        $path = UploadedFile::fake()->create('satu.pdf', 50, 'application/pdf')->store('panduan-aplikasi', 'public');
        PanduanAplikasiFile::factory()->create(['panduan_aplikasi_id' => $panduan->id, 'file_path' => $path]);

        $this->actingAs($adminOps)
            ->get(route('panduan-aplikasi.unduh-semua', $panduan))
            ->assertForbidden();
    }

    public function test_unduh_semua_tanpa_file_menghasilkan_404(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $panduan = PanduanAplikasi::factory()->create();
        PanduanAplikasiLink::factory()->create(['panduan_aplikasi_id' => $panduan->id]);

        $this->actingAs($superadmin)
            ->get(route('panduan-aplikasi.unduh-semua', $panduan))
            ->assertNotFound();
    }

    public function test_tombol_unduh_semua_tampil_hanya_jika_ada_2_atau_lebih_file(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $panduanSatuFile = PanduanAplikasi::factory()->create(['judul' => 'Panduan Satu File']);
        PanduanAplikasiFile::factory()->create(['panduan_aplikasi_id' => $panduanSatuFile->id]);
        $panduanDuaFile = PanduanAplikasi::factory()->create(['judul' => 'Panduan Dua File']);
        PanduanAplikasiFile::factory()->count(2)->create(['panduan_aplikasi_id' => $panduanDuaFile->id]);

        $response = $this->actingAs($superadmin)->get(route('panduan-aplikasi.index'));

        $response->assertOk();
        $response->assertSee('Unduh Semua (2 file)');
        $response->assertDontSee('Unduh Semua (1 file)');
    }

    public function test_baca_selengkapnya_tampil_kalau_file_dan_link_lebih_dari_batas_tampil(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $panduan = PanduanAplikasi::factory()->create();
        PanduanAplikasiFile::factory()->count(4)->create(['panduan_aplikasi_id' => $panduan->id]);

        $response = $this->actingAs($superadmin)->get(route('panduan-aplikasi.index'));

        $response->assertOk();
        $response->assertSee('Baca Selengkapnya (1 lagi)');
    }

    public function test_baca_selengkapnya_tidak_tampil_kalau_file_dan_link_3_atau_kurang(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $panduan = PanduanAplikasi::factory()->create();
        PanduanAplikasiFile::factory()->count(3)->create(['panduan_aplikasi_id' => $panduan->id]);

        $response = $this->actingAs($superadmin)->get(route('panduan-aplikasi.index'));

        $response->assertOk();
        $response->assertDontSee('Baca Selengkapnya');
    }
}
