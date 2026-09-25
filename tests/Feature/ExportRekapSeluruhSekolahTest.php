<?php

namespace Tests\Feature;

use App\Exports\Lampiran2aExport;
use App\Exports\Lampiran2bExport;
use App\Exports\Lampiran2cExport;
use App\Models\Lampiran2a;
use App\Models\Lampiran2b;
use App\Models\Lampiran2c;
use App\Models\ProfilSekolah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Export Lampiran 2a/2b/2c dengan $sekolah = null (rekap gabungan seluruh
 * sekolah, dipakai menu Unduhan khusus Superadmin - lihat Part 6) harus:
 * - Menampilkan keterangan "REKAP SELURUH SEKOLAH" pada judul.
 * - TIDAK menampilkan lembar tanda tangan Pengawas/Kepala Sekolah (karena
 *   1 lembar tanda tangan tidak bisa mewakili banyak sekolah sekaligus).
 *
 * Sebaliknya, export dengan $sekolah diisi (perilaku LAMA, satu sekolah)
 * harus tetap menampilkan lembar tanda tangan seperti sebelumnya - supaya
 * perubahan nullable-$sekolah ini tidak mengubah perilaku export per-
 * sekolah yang sudah ada.
 */
class ExportRekapSeluruhSekolahTest extends TestCase
{
    use RefreshDatabase;

    protected function isiSheet($sheet): string
    {
        $teks = [];

        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                if (is_string($cell->getValue())) {
                    $teks[] = $cell->getValue();
                }
            }
        }

        return implode(' | ', $teks);
    }

    public function test_lampiran2a_dengan_sekolah_null_tampilkan_rekap_dan_lewati_tanda_tangan(): void
    {
        Storage::fake('local');

        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SDN Contoh']);
        $baris = Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1]);

        Excel::store(new Lampiran2aExport(collect([$baris]), 1, 2026, null), 'rekap-2a.xlsx', 'local');

        $sheet = IOFactory::load(Storage::disk('local')->path('rekap-2a.xlsx'))->getActiveSheet();
        $isi = $this->isiSheet($sheet);

        $this->assertStringContainsString('REKAP SELURUH SEKOLAH', $isi);
        $this->assertStringNotContainsString('Mengetahui/Menyetujui', $isi);
    }

    public function test_lampiran2a_dengan_sekolah_tetap_tampilkan_tanda_tangan(): void
    {
        Storage::fake('local');

        $sekolah = ProfilSekolah::factory()->create([
            'nama_sekolah' => 'SDN Contoh',
            'nama_pengawas' => 'Pengawas A',
            'nama_kepala_sekolah' => 'Kepsek A',
        ]);
        $baris = Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1]);

        Excel::store(new Lampiran2aExport(collect([$baris]), 1, 2026, $sekolah), 'single-2a.xlsx', 'local');

        $sheet = IOFactory::load(Storage::disk('local')->path('single-2a.xlsx'))->getActiveSheet();
        $isi = $this->isiSheet($sheet);

        $this->assertStringNotContainsString('REKAP SELURUH SEKOLAH', $isi);
        $this->assertStringContainsString('Mengetahui/Menyetujui', $isi);
        $this->assertStringContainsString('Pengawas A', $isi);
        $this->assertStringContainsString('Kepsek A', $isi);
    }

    public function test_lampiran2b_dengan_sekolah_null_tampilkan_rekap_dan_lewati_tanda_tangan(): void
    {
        Storage::fake('local');

        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SDN Contoh']);
        $baris = Lampiran2b::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1]);

        Excel::store(new Lampiran2bExport(collect([$baris]), 1, 2026, null), 'rekap-2b.xlsx', 'local');

        $sheet = IOFactory::load(Storage::disk('local')->path('rekap-2b.xlsx'))->getActiveSheet();
        $isi = $this->isiSheet($sheet);

        $this->assertStringContainsString('REKAP SELURUH SEKOLAH', $isi);
        $this->assertStringNotContainsString('Mengetahui/Menyetujui', $isi);
    }

    public function test_lampiran2c_dengan_sekolah_null_tampilkan_rekap_dan_lewati_tanda_tangan(): void
    {
        Storage::fake('local');

        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SDN Contoh']);
        $baris = Lampiran2c::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1, 'kecamatan' => 'Cibadak']);

        Excel::store(new Lampiran2cExport(collect([$baris]), 1, 2026, null), 'rekap-2c.xlsx', 'local');

        $sheet = IOFactory::load(Storage::disk('local')->path('rekap-2c.xlsx'))->getActiveSheet();
        $isi = $this->isiSheet($sheet);

        $this->assertStringContainsString('REKAP SELURUH SEKOLAH', $isi);
        $this->assertStringNotContainsString('KECAMATAN : Cibadak', $isi);
        $this->assertStringNotContainsString('Mengetahui/Menyetujui', $isi);
    }

    public function test_lampiran2c_dengan_sekolah_tetap_tampilkan_kecamatan_dan_tanda_tangan(): void
    {
        Storage::fake('local');

        $sekolah = ProfilSekolah::factory()->create([
            'nama_sekolah' => 'SDN Contoh',
            'nama_pengawas' => 'Pengawas B',
            'nama_kepala_sekolah' => 'Kepsek B',
        ]);
        $baris = Lampiran2c::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1, 'kecamatan' => 'Cibadak']);

        Excel::store(new Lampiran2cExport(collect([$baris]), 1, 2026, $sekolah), 'single-2c.xlsx', 'local');

        $sheet = IOFactory::load(Storage::disk('local')->path('single-2c.xlsx'))->getActiveSheet();
        $isi = $this->isiSheet($sheet);

        $this->assertStringContainsString('KECAMATAN : Cibadak', $isi);
        $this->assertStringContainsString('Mengetahui/Menyetujui', $isi);
    }
}
