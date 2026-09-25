<?php

namespace Tests\Feature;

use App\Exports\BelanjaHonorKegiatanExport;
use App\Exports\BiayaPendaftaranLombaExport;
use App\Exports\LanggananDayaJasaExport;
use App\Exports\PajakBospRegulerExport;
use App\Exports\PenerimaanHonorPtkExport;
use App\Exports\RincianBelanjaBarangHabisPakaiExport;
use App\Exports\RincianBelanjaModalExport;
use App\Exports\RincianPemeliharaanExport;
use App\Exports\RincianPemeliharaanPcExport;
use App\Exports\StockOpnameBarangPersediaanExport;
use App\Models\BelanjaHonorKegiatan;
use App\Models\ProfilSekolah;
use App\Models\RincianBelanjaModal;
use App\Models\RincianPemeliharaan;
use App\Models\RincianPemeliharaanPc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Verifikasi permintaan user 2026-09-23 (item #2 dari batch 8 permintaan):
 * lembar tanda tangan "Mengetahui/Menyetujui" Kepala Sekolah/Bendahara
 * BOSP SUDAH DIHAPUS dari hasil export Excel untuk 10 Export di menu
 * sidebar Pendataan BOSP (Lampiran 2a/2b/2c SENGAJA TIDAK termasuk -
 * jawaban AskUserQuestion 2026-09-23: "Hanya menu di sidebar Pendataan
 * BOSP (Recommended)"). Hanya judul tabel (tulisJudul()) yang harus tetap
 * muncul.
 *
 * Setiap test langsung meng-instantiate Export class (bukan lewat
 * Livewire), menulis ke storage fake, lalu membaca ulang sel-selnya lewat
 * PhpSpreadsheet - pola yang sama dengan ExportRekapSeluruhSekolahTest.
 */
class PendataanBospTandaTanganDihapusTest extends TestCase
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

    public function test_rincian_belanja_modal_export_tidak_ada_tanda_tangan(): void
    {
        Storage::fake('local');
        $sekolah = ProfilSekolah::factory()->create();

        Excel::store(new RincianBelanjaModalExport(collect(), RincianBelanjaModal::JENIS_PERALATAN_MESIN, 1, 2026, $sekolah), 'rbm.xlsx', 'local');

        $isi = $this->isiSheet(IOFactory::load(Storage::disk('local')->path('rbm.xlsx'))->getActiveSheet());
        $this->assertStringNotContainsString('Mengetahui/Menyetujui', $isi);
        $this->assertStringContainsString('PERALATAN', $isi);
    }

    public function test_pajak_bosp_reguler_export_tidak_ada_tanda_tangan(): void
    {
        Storage::fake('local');
        $sekolah = ProfilSekolah::factory()->create();
        $bulanKosong = array_fill(1, 12, ['debit' => 0, 'kredit' => 0]);
        $twKosong = array_fill(1, 4, ['debit' => 0, 'kredit' => 0, 'saldo' => 0, 'rincian' => []]);
        $totalRaw = array_fill_keys([
            'ppn_debit', 'pph21_debit', 'pph23_debit', 'pph4_debit', 'sspd_debit',
            'ppn_kredit', 'pph21_kredit', 'pph23_kredit', 'pph4_kredit', 'sspd_kredit',
        ], 0);

        Excel::store(new PajakBospRegulerExport(
            $sekolah,
            2026,
            [],
            $bulanKosong,
            array_fill(1, 12, 0),
            $twKosong,
            $totalRaw,
            0,
            0,
            0,
        ), 'pajak.xlsx', 'local');

        $isi = $this->isiSheet(IOFactory::load(Storage::disk('local')->path('pajak.xlsx'))->getActiveSheet());
        $this->assertStringNotContainsString('Mengetahui/Menyetujui', $isi);
        $this->assertStringContainsString('REKAPITULASI PAJAK REGULER', $isi);
    }

    public function test_stock_opname_export_tidak_ada_tanda_tangan(): void
    {
        Storage::fake('local');
        $sekolah = ProfilSekolah::factory()->create();

        Excel::store(new StockOpnameBarangPersediaanExport(collect(), 1, 2026, $sekolah), 'stock.xlsx', 'local');

        $isi = $this->isiSheet(IOFactory::load(Storage::disk('local')->path('stock.xlsx'))->getActiveSheet());
        $this->assertStringNotContainsString('Mengetahui/Menyetujui', $isi);
        $this->assertStringContainsString('STOCK OPNAME', $isi);
    }

    public function test_rincian_belanja_barang_habis_pakai_export_tidak_ada_tanda_tangan(): void
    {
        Storage::fake('local');
        $sekolah = ProfilSekolah::factory()->create();

        Excel::store(new RincianBelanjaBarangHabisPakaiExport(collect(), 1, 2026, $sekolah), 'bhp.xlsx', 'local');

        $isi = $this->isiSheet(IOFactory::load(Storage::disk('local')->path('bhp.xlsx'))->getActiveSheet());
        $this->assertStringNotContainsString('Mengetahui/Menyetujui', $isi);
        $this->assertStringContainsString('BARANG HABIS PAKAI', $isi);
    }

    public function test_belanja_honor_kegiatan_export_tidak_ada_tanda_tangan(): void
    {
        Storage::fake('local');
        $sekolah = ProfilSekolah::factory()->create();

        Excel::store(new BelanjaHonorKegiatanExport(collect(), BelanjaHonorKegiatan::JENIS_HONOR_KEGIATAN, 1, 2026, $sekolah), 'honor.xlsx', 'local');

        $isi = $this->isiSheet(IOFactory::load(Storage::disk('local')->path('honor.xlsx'))->getActiveSheet());
        $this->assertStringNotContainsString('Mengetahui/Menyetujui', $isi);
        $this->assertStringContainsString('HONOR KEGIATAN', $isi);
    }

    public function test_biaya_pendaftaran_lomba_export_tidak_ada_tanda_tangan(): void
    {
        Storage::fake('local');
        $sekolah = ProfilSekolah::factory()->create();

        Excel::store(new BiayaPendaftaranLombaExport(collect(), 1, 2026, $sekolah), 'lomba.xlsx', 'local');

        $isi = $this->isiSheet(IOFactory::load(Storage::disk('local')->path('lomba.xlsx'))->getActiveSheet());
        $this->assertStringNotContainsString('Mengetahui/Menyetujui', $isi);
        $this->assertStringContainsString('LOMBA', $isi);
    }

    public function test_rincian_pemeliharaan_pc_export_tidak_ada_tanda_tangan(): void
    {
        Storage::fake('local');
        $sekolah = ProfilSekolah::factory()->create();

        Excel::store(new RincianPemeliharaanPcExport(collect(), RincianPemeliharaanPc::JENIS_BARANG, 1, 2026, $sekolah), 'pc.xlsx', 'local');

        $isi = $this->isiSheet(IOFactory::load(Storage::disk('local')->path('pc.xlsx'))->getActiveSheet());
        $this->assertStringNotContainsString('Mengetahui/Menyetujui', $isi);
        $this->assertStringContainsString('PC KOMPUTER', $isi);
    }

    public function test_rincian_pemeliharaan_export_tidak_ada_tanda_tangan(): void
    {
        Storage::fake('local');
        $sekolah = ProfilSekolah::factory()->create();

        Excel::store(new RincianPemeliharaanExport(collect(), RincianPemeliharaan::JENIS_BARANG, 1, 2026, $sekolah), 'pemeliharaan.xlsx', 'local');

        $isi = $this->isiSheet(IOFactory::load(Storage::disk('local')->path('pemeliharaan.xlsx'))->getActiveSheet());
        $this->assertStringNotContainsString('Mengetahui/Menyetujui', $isi);
        $this->assertStringContainsString('PEMELIHARAAN BANGUNAN', $isi);
    }

    public function test_langganan_daya_jasa_export_tidak_ada_tanda_tangan(): void
    {
        Storage::fake('local');
        $sekolah = ProfilSekolah::factory()->create();

        Excel::store(new LanggananDayaJasaExport(collect(), 1, 2026, $sekolah), 'langganan.xlsx', 'local');

        $isi = $this->isiSheet(IOFactory::load(Storage::disk('local')->path('langganan.xlsx'))->getActiveSheet());
        $this->assertStringNotContainsString('Mengetahui/Menyetujui', $isi);
        $this->assertStringContainsString('LANGGANAN DAYA DAN JASA', $isi);
    }

    public function test_penerimaan_honor_ptk_export_tidak_ada_tanda_tangan(): void
    {
        Storage::fake('local');
        $sekolah = ProfilSekolah::factory()->create();

        Excel::store(new PenerimaanHonorPtkExport(collect(), 1, 2026, $sekolah), 'ptk.xlsx', 'local');

        $isi = $this->isiSheet(IOFactory::load(Storage::disk('local')->path('ptk.xlsx'))->getActiveSheet());
        $this->assertStringNotContainsString('Mengetahui/Menyetujui', $isi);
        $this->assertStringContainsString('PENERIMAAN HONOR PENDIDIK', $isi);
    }
}
