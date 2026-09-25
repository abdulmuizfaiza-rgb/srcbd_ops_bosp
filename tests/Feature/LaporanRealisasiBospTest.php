<?php

namespace Tests\Feature;

use App\Livewire\PendataanBosp\LaporanRealisasiBosp\Index;
use App\Models\BelanjaHonorKegiatan;
use App\Models\BiayaPendaftaranLomba;
use App\Models\DanaBospTahap;
use App\Models\LanggananDayaJasa;
use App\Models\LaporanRealisasiBosp;
use App\Models\PajakBospReguler;
use App\Models\PendataanBosp;
use App\Models\PendataanOps;
use App\Models\PenerimaanHonorPtk;
use App\Models\ProfilSekolah;
use App\Models\RincianBelanjaBarangHabisPakai;
use App\Models\RincianBelanjaModal;
use App\Models\RincianPemeliharaan;
use App\Models\RincianPemeliharaanPc;
use App\Models\User;
use App\Models\VervalRealisasiBosp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menguji menu Laporan Realisasi BOSP / Form BPK - Pendataan BOSP
 * (permintaan user 2026-09-17, Part 32): 1 baris per sekolah per tahun
 * per triwulan (4 baris tetap - pola PajakBospReguler), 5 tab (TW1-4
 * input + Rekapitulasi otomatis read-only).
 *
 * Status kolom TERKINI (lanjutan Part 32 s.d. ketujuh - lihat
 * App\Models\LaporanRealisasiBosp untuk rincian lengkap): SELURUH kolom
 * 8-29 SUDAH read-only/otomatis, TIDAK ADA LAGI kolom manual satupun di
 * menu ini. Kolom 8 (Saldo Awal Dana BOSP) TW1 dari DanaBospTahap
 * (`saldo_bosp_tw4_tahun_sebelumnya`), TW2/3/4 dari Sisa Dana BOS (kolom
 * 25) triwulan SEBELUMNYA (rekursi antar triwulan). Kolom 9 (Penerimaan
 * Dana BOS) TW1 dari DanaBospTahap `penerimaan_tahap_1`, TW3 dari
 * `penerimaan_tahap_2`, TW2 & TW4 SELALU 0. Kolom 10 (Total Penerimaan)
 * = kolom 8 + kolom 9 triwulan yang sama. Kolom 11-27 sebagian dari
 * total SUM menu rincian terkait (kolom 11-19/21/22), sebagian rumus
 * gabungan dari kolom lain (kolom 20/23/24/25), & kolom 26-27 (Saldo
 * Rekening/Kas Bank, Saldo Kas Tunai) diambil OTOMATIS dari
 * App\Models\DanaBospTahap (menu Dana BOSP Tahap 1 & 2, tab "Tarik
 * Tunai BOSP"). Kolom 28-29 (Verifikasi Saldo) HASIL RUMUS eksplisit
 * dari user, dihitung REAL-TIME setiap render dari kolom 25-27 - kolom
 * 29 (kata "SAMA"/"TIDAK SAMA") BARU tampil kalau kolom 26 & 27 SUDAH
 * tersimpan (kalau salah satu/keduanya belum, nilainya null/"-"), aturan
 * ini ikut dicascade ke baris agregat manapun (JUMLAH footer TW1-4,
 * Jumlah per sekolah tab rekap, & JUMLAH TAHUN ANGGARAN) - lihat
 * App\Models\LaporanRealisasiBosp::hitungVerifikasiSaldo() &
 * Livewire\...\Index::renderTabTriwulan()/renderTabRekap(). Subrayon &
 * Kode UPB di Profil Sekolah, route & sidebar turut diuji di file ini.
 */
class LaporanRealisasiBospTest extends TestCase
{
    use RefreshDatabase;

    public function test_saldo_awal_penerimaan_dana_bos_dan_total_penerimaan_tidak_bisa_diedit_manual(): void
    {
        // Sejak permintaan user 2026-09-17 (lanjutan Part 32 ketujuh),
        // kolom 8-10 - KOLOM MANUAL TERAKHIR di menu ini - SUDAH read-only
        // juga (lihat App\Models\LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN)
        // sehingga SELURUH tabel `laporan_realisasi_bosp` sekarang
        // read-only. Percobaan set langsung ke kolom manapun HARUS
        // ditolak (tidak tersimpan sama sekali) - pola sama seperti test
        // "tidak_bisa_diedit_manual" untuk kolom 11-27 lain di file ini.
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.saldo_awal_dana_bosp", '1000000')
            ->set("baris.{$sekolah->id}.penerimaan_dana_bos", '20000000')
            ->set("baris.{$sekolah->id}.total_penerimaan", '21000000')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('laporan_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_saldo_awal_dana_bosp_tw1_dari_dana_bosp_tahap_dan_tw2_3_4_dari_sisa_dana_bos_triwulan_sebelumnya(): void
    {
        // Rumus eksplisit user 2026-09-17 (lanjutan Part 32 ketujuh, poin
        // 4-7): kolom 8 (Saldo Awal Dana BOSP) TW1 = Saldo BOSP TW4 Tahun
        // Sebelumnya (DanaBospTahap), TW2/3/4 = Sisa Dana BOS (kolom 25)
        // TRIWULAN SEBELUMNYA - SATU-SATUNYA kolom yang rumusnya
        // merekursi ANTAR triwulan (lihat
        // App\Models\LaporanRealisasiBosp::hitungRantaiPenerimaanDanSisaSemuaTriwulan()).
        // Tidak ada data realisasi/penerimaan sama sekali di test ini
        // supaya rantainya mudah ditelusuri: TW1 saldo_awal=2jt (dari
        // DanaBospTahap), penerimaan=0, total=2jt, sisa=2jt (realisasi
        // 0). TW2 saldo_awal=2jt (dari sisa TW1), dst TETAP 2jt di setiap
        // TW berikutnya (karena penerimaan TW2/4 selalu 0 & tidak ada
        // realisasi).
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'saldo_bosp_tw4_tahun_sebelumnya' => 2000000,
            'penerimaan_tahap_1' => 0,
            'penerimaan_tahap_2' => 0,
        ]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertSame('2000000', $component->get("baris.{$sekolah->id}.saldo_awal_dana_bosp"));
        $this->assertSame('2000000', $component->get("baris.{$sekolah->id}.sisa_dana_bos"));

        $component->call('pindahTab', 'tw2');
        $this->assertSame('2000000', $component->get("baris.{$sekolah->id}.saldo_awal_dana_bosp"));

        $component->call('pindahTab', 'tw3');
        $this->assertSame('2000000', $component->get("baris.{$sekolah->id}.saldo_awal_dana_bosp"));

        $component->call('pindahTab', 'tw4');
        $this->assertSame('2000000', $component->get("baris.{$sekolah->id}.saldo_awal_dana_bosp"));
    }

    public function test_saldo_awal_dana_bosp_ikut_berubah_ketika_ada_realisasi_pada_triwulan_sebelumnya(): void
    {
        // Membuktikan rantai kolom 8 TW2 BENAR-BENAR mengikuti kolom 25
        // (Sisa Dana BOS) TW1 SETELAH dikurangi realisasi - bukan cuma
        // menyalin kolom 8 TW1 begitu saja.
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'saldo_bosp_tw4_tahun_sebelumnya' => 5000000,
            'penerimaan_tahap_1' => 0,
            'penerimaan_tahap_2' => 0,
        ]);

        // Realisasi TW1 = 1.000.000 (kolom 11 -> kolom 24) -> Sisa Dana
        // BOS TW1 = 5.000.000 - 1.000.000 = 4.000.000.
        RincianBelanjaBarangHabisPakai::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 1000000,
        ]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);
        $this->assertSame('4000000', $component->get("baris.{$sekolah->id}.sisa_dana_bos"));

        $component->call('pindahTab', 'tw2');
        $this->assertSame('4000000', $component->get("baris.{$sekolah->id}.saldo_awal_dana_bosp"));
        $this->assertSame('4000000', $component->get("baris.{$sekolah->id}.total_penerimaan"));
    }

    public function test_belanja_barang_pakai_habis_persediaan_tidak_bisa_diedit_manual(): void
    {
        // Sejak permintaan user 2026-09-17 (lanjutan Part 32), kolom ini
        // READ-ONLY - diambil otomatis dari menu Rincian Belanja Barang
        // Habis Pakai, BUKAN lagi diisi manual (lihat
        // App\Models\LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN).
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.belanja_barang_pakai_habis_persediaan", '500000')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('laporan_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_belanja_barang_pakai_habis_persediaan_diambil_dari_total_rincian_belanja_barang_habis_pakai_tw_yang_sama(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        // 2 baris Rincian TW1 tahun ini untuk sekolah ini - harus dijumlah.
        RincianBelanjaBarangHabisPakai::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 10,
            'harga_satuan' => 5000,
            'total_harga' => 50000,
        ]);
        RincianBelanjaBarangHabisPakai::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 4,
            'harga_satuan' => 25000,
            'total_harga' => 100000,
        ]);

        // Baris TW2 tahun ini untuk sekolah yang sama - TIDAK BOLEH ikut
        // terhitung di tab TW1.
        RincianBelanjaBarangHabisPakai::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 2,
            'total_harga' => 999999,
        ]);

        // Baris TW1 tahun ini untuk sekolah LAIN - TIDAK BOLEH ikut
        // terhitung di baris sekolah ini.
        RincianBelanjaBarangHabisPakai::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 777777,
        ]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertSame('150000', $component->get("baris.{$sekolah->id}.belanja_barang_pakai_habis_persediaan"));
        $this->assertSame('777777', $component->get("baris.{$sekolahLain->id}.belanja_barang_pakai_habis_persediaan"));

        // Baris JUMLAH (total footer) - sum kedua sekolah = 150.000 + 777.777.
        $totalBaris = $component->viewData('totalBaris');
        $this->assertSame(927777, $totalBaris['belanja_barang_pakai_habis_persediaan']);

        // Pindah ke TW2 - nilai sekolah ini harus 999.999 (baris TW2),
        // BUKAN 150.000 lagi (bukti terisolasi per triwulan).
        $component->call('pindahTab', 'tw2');
        $this->assertSame('999999', $component->get("baris.{$sekolah->id}.belanja_barang_pakai_habis_persediaan"));
    }

    public function test_belanja_barang_pakai_habis_persediaan_nol_kalau_belum_ada_data_rincian(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertSame('0', $component->get("baris.{$sekolah->id}.belanja_barang_pakai_habis_persediaan"));
    }

    public function test_tab_rekap_menampilkan_belanja_barang_pakai_habis_persediaan_per_tw_dan_jumlahnya(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        foreach ([1, 2, 3, 4] as $tw) {
            RincianBelanjaBarangHabisPakai::factory()->create([
                'profil_sekolah_id' => $sekolah->id,
                'tahun' => now()->year,
                'triwulan' => $tw,
                'volume' => 1,
                'harga_satuan' => 100000 * $tw,
                'total_harga' => 100000 * $tw,
            ]);
        }
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('pindahTab', 'rekap');

        $rekap = $component->viewData('rekapPerSekolah');
        $blok = $rekap[0];

        $this->assertSame(100000, $blok['perTriwulan'][1]['belanja_barang_pakai_habis_persediaan']);
        $this->assertSame(400000, $blok['perTriwulan'][4]['belanja_barang_pakai_habis_persediaan']);
        // Jumlah 4 TW = 100rb+200rb+300rb+400rb = 1jt.
        $this->assertSame(1000000, $blok['jumlah']['belanja_barang_pakai_habis_persediaan']);
    }

    public function test_jasa_tenaga_pendidik_dan_kependidikan_tidak_bisa_diedit_manual(): void
    {
        // Sejak permintaan user 2026-09-17 (lanjutan Part 32 kedua),
        // kolom ini READ-ONLY - diambil otomatis dari menu Penerimaan
        // Honor PTK, BUKAN lagi diisi manual (lihat
        // App\Models\LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN).
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.jasa_tenaga_pendidik_dan_kependidikan", '500000')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('laporan_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_jasa_tenaga_pendidik_dan_kependidikan_diambil_dari_total_penerimaan_honor_ptk_tw_yang_sama(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        // 2 baris Penerimaan Honor PTK TW1 tahun ini untuk sekolah ini - harus dijumlah.
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 10,
            'tarif_harga' => 5000,
            'jumlah_honor' => 50000,
        ]);
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 4,
            'tarif_harga' => 25000,
            'jumlah_honor' => 100000,
        ]);

        // Baris TW2 tahun ini untuk sekolah yang sama - TIDAK BOLEH ikut
        // terhitung di tab TW1.
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 2,
            'jumlah_honor' => 999999,
        ]);

        // Baris TW1 tahun ini untuk sekolah LAIN - TIDAK BOLEH ikut
        // terhitung di baris sekolah ini.
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah_honor' => 777777,
        ]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertSame('150000', $component->get("baris.{$sekolah->id}.jasa_tenaga_pendidik_dan_kependidikan"));
        $this->assertSame('777777', $component->get("baris.{$sekolahLain->id}.jasa_tenaga_pendidik_dan_kependidikan"));

        // Baris JUMLAH (total footer) - sum kedua sekolah = 150.000 + 777.777.
        $totalBaris = $component->viewData('totalBaris');
        $this->assertSame(927777, $totalBaris['jasa_tenaga_pendidik_dan_kependidikan']);

        // Pindah ke TW2 - nilai sekolah ini harus 999.999 (baris TW2),
        // BUKAN 150.000 lagi (bukti terisolasi per triwulan).
        $component->call('pindahTab', 'tw2');
        $this->assertSame('999999', $component->get("baris.{$sekolah->id}.jasa_tenaga_pendidik_dan_kependidikan"));
    }

    public function test_jasa_tenaga_pendidik_dan_kependidikan_nol_kalau_belum_ada_data_penerimaan_honor_ptk(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertSame('0', $component->get("baris.{$sekolah->id}.jasa_tenaga_pendidik_dan_kependidikan"));
    }

    public function test_tab_rekap_menampilkan_jasa_tenaga_pendidik_dan_kependidikan_per_tw_dan_jumlahnya(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        foreach ([1, 2, 3, 4] as $tw) {
            PenerimaanHonorPtk::factory()->create([
                'profil_sekolah_id' => $sekolah->id,
                'tahun' => now()->year,
                'triwulan' => $tw,
                'volume' => 1,
                'tarif_harga' => 100000 * $tw,
                'jumlah_honor' => 100000 * $tw,
            ]);
        }
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('pindahTab', 'rekap');

        $rekap = $component->viewData('rekapPerSekolah');
        $blok = $rekap[0];

        $this->assertSame(100000, $blok['perTriwulan'][1]['jasa_tenaga_pendidik_dan_kependidikan']);
        $this->assertSame(400000, $blok['perTriwulan'][4]['jasa_tenaga_pendidik_dan_kependidikan']);
        // Jumlah 4 TW = 100rb+200rb+300rb+400rb = 1jt.
        $this->assertSame(1000000, $blok['jumlah']['jasa_tenaga_pendidik_dan_kependidikan']);
    }

    public function test_daya_dan_jasa_tidak_bisa_diedit_manual(): void
    {
        // Sejak permintaan user 2026-09-17 (lanjutan Part 32 kedua),
        // kolom ini READ-ONLY - diambil otomatis dari menu Daya & Jasa,
        // BUKAN lagi diisi manual (lihat
        // App\Models\LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN).
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.daya_dan_jasa", '500000')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('laporan_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_daya_dan_jasa_diambil_dari_total_langganan_daya_jasa_tw_yang_sama(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        // 2 baris Daya & Jasa TW1 tahun ini untuk sekolah ini - harus dijumlah.
        LanggananDayaJasa::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 10,
            'tarif_harga' => 5000,
            'jumlah' => 50000,
        ]);
        LanggananDayaJasa::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 4,
            'tarif_harga' => 25000,
            'jumlah' => 100000,
        ]);

        // Baris TW2 tahun ini untuk sekolah yang sama - TIDAK BOLEH ikut
        // terhitung di tab TW1.
        LanggananDayaJasa::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 2,
            'jumlah' => 999999,
        ]);

        // Baris TW1 tahun ini untuk sekolah LAIN - TIDAK BOLEH ikut
        // terhitung di baris sekolah ini.
        LanggananDayaJasa::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 777777,
        ]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertSame('150000', $component->get("baris.{$sekolah->id}.daya_dan_jasa"));
        $this->assertSame('777777', $component->get("baris.{$sekolahLain->id}.daya_dan_jasa"));

        // Baris JUMLAH (total footer) - sum kedua sekolah = 150.000 + 777.777.
        $totalBaris = $component->viewData('totalBaris');
        $this->assertSame(927777, $totalBaris['daya_dan_jasa']);

        // Pindah ke TW2 - nilai sekolah ini harus 999.999 (baris TW2),
        // BUKAN 150.000 lagi (bukti terisolasi per triwulan).
        $component->call('pindahTab', 'tw2');
        $this->assertSame('999999', $component->get("baris.{$sekolah->id}.daya_dan_jasa"));
    }

    public function test_daya_dan_jasa_nol_kalau_belum_ada_data_langganan_daya_jasa(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertSame('0', $component->get("baris.{$sekolah->id}.daya_dan_jasa"));
    }

    public function test_tab_rekap_menampilkan_daya_dan_jasa_per_tw_dan_jumlahnya(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        foreach ([1, 2, 3, 4] as $tw) {
            LanggananDayaJasa::factory()->create([
                'profil_sekolah_id' => $sekolah->id,
                'tahun' => now()->year,
                'triwulan' => $tw,
                'volume' => 1,
                'tarif_harga' => 100000 * $tw,
                'jumlah' => 100000 * $tw,
            ]);
        }
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('pindahTab', 'rekap');

        $rekap = $component->viewData('rekapPerSekolah');
        $blok = $rekap[0];

        $this->assertSame(100000, $blok['perTriwulan'][1]['daya_dan_jasa']);
        $this->assertSame(400000, $blok['perTriwulan'][4]['daya_dan_jasa']);
        // Jumlah 4 TW = 100rb+200rb+300rb+400rb = 1jt.
        $this->assertSame(1000000, $blok['jumlah']['daya_dan_jasa']);
    }

    public function test_pemeliharaan_tidak_bisa_diedit_manual(): void
    {
        // Sejak permintaan user 2026-09-17 (lanjutan Part 32 ketiga),
        // kolom ini READ-ONLY - diambil otomatis dari menu Rincian
        // Pemeliharaan + Rincian Pemeliharaan PC dll (jenis barang),
        // BUKAN lagi diisi manual (lihat
        // App\Models\LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN).
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.pemeliharaan", '500000')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('laporan_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_pemeliharaan_diambil_dari_total_rincian_pemeliharaan_dan_pc_jenis_barang_tw_yang_sama(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        // Rincian Pemeliharaan (bangunan) jenis barang TW1 tahun ini -
        // harus ikut dijumlah.
        RincianPemeliharaan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianPemeliharaan::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 50000,
        ]);

        // Rincian Pemeliharaan PC dll jenis barang TW1 tahun ini - harus
        // DITAMBAHKAN ke total di atas (dari menu SUMBER LAIN).
        RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianPemeliharaanPc::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 30000,
        ]);

        // Baris jenis JASA di kedua menu sumber, TW & sekolah yang sama -
        // TIDAK BOLEH ikut kolom "pemeliharaan" (ini sumber kolom "upah
        // pemeliharaan", diuji terpisah di bawah).
        RincianPemeliharaan::factory()->jasa()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 999999,
        ]);
        RincianPemeliharaanPc::factory()->jasa()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 999999,
        ]);

        // Baris TW2 tahun ini untuk sekolah yang sama - TIDAK BOLEH ikut
        // terhitung di tab TW1.
        RincianPemeliharaan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianPemeliharaan::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 2,
            'total_harga' => 777777,
        ]);

        // Baris TW1 tahun ini untuk sekolah LAIN - TIDAK BOLEH ikut
        // terhitung di baris sekolah ini.
        RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'jenis' => RincianPemeliharaanPc::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 123456,
        ]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        // 50.000 (Rincian Pemeliharaan) + 30.000 (Rincian Pemeliharaan PC dll) = 80.000.
        $this->assertSame('80000', $component->get("baris.{$sekolah->id}.pemeliharaan"));
        $this->assertSame('123456', $component->get("baris.{$sekolahLain->id}.pemeliharaan"));

        // Baris JUMLAH (total footer) - sum kedua sekolah = 80.000 + 123.456.
        $totalBaris = $component->viewData('totalBaris');
        $this->assertSame(203456, $totalBaris['pemeliharaan']);

        // Pindah ke TW2 - nilai sekolah ini harus 777.777 (baris TW2),
        // BUKAN 80.000 lagi (bukti terisolasi per triwulan).
        $component->call('pindahTab', 'tw2');
        $this->assertSame('777777', $component->get("baris.{$sekolah->id}.pemeliharaan"));
    }

    public function test_pemeliharaan_nol_kalau_belum_ada_data_rincian_pemeliharaan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertSame('0', $component->get("baris.{$sekolah->id}.pemeliharaan"));
    }

    public function test_tab_rekap_menampilkan_pemeliharaan_per_tw_dan_jumlahnya(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        foreach ([1, 2, 3, 4] as $tw) {
            RincianPemeliharaan::factory()->create([
                'profil_sekolah_id' => $sekolah->id,
                'jenis' => RincianPemeliharaan::JENIS_BARANG,
                'tahun' => now()->year,
                'triwulan' => $tw,
                'total_harga' => 100000 * $tw,
            ]);
            RincianPemeliharaanPc::factory()->create([
                'profil_sekolah_id' => $sekolah->id,
                'jenis' => RincianPemeliharaanPc::JENIS_BARANG,
                'tahun' => now()->year,
                'triwulan' => $tw,
                'total_harga' => 10000 * $tw,
            ]);
        }
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('pindahTab', 'rekap');

        $rekap = $component->viewData('rekapPerSekolah');
        $blok = $rekap[0];

        // TW1 = 100.000 + 10.000 = 110.000, TW4 = 400.000 + 40.000 = 440.000.
        $this->assertSame(110000, $blok['perTriwulan'][1]['pemeliharaan']);
        $this->assertSame(440000, $blok['perTriwulan'][4]['pemeliharaan']);
        // Jumlah 4 TW = 110rb+220rb+330rb+440rb = 1,1jt.
        $this->assertSame(1100000, $blok['jumlah']['pemeliharaan']);
    }

    public function test_upah_pemeliharaan_tidak_bisa_diedit_manual(): void
    {
        // Sejak permintaan user 2026-09-17 (lanjutan Part 32 ketiga),
        // kolom ini READ-ONLY - diambil otomatis dari menu Rincian Jasa
        // Pemeliharaan + Rincian Jasa Pemeliharaan PC dll (jenis jasa),
        // BUKAN lagi diisi manual (lihat
        // App\Models\LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN).
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.upah_pemeliharaan", '500000')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('laporan_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_upah_pemeliharaan_diambil_dari_total_rincian_jasa_pemeliharaan_dan_pc_jenis_jasa_tw_yang_sama(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        // Rincian Jasa Pemeliharaan (bangunan) TW1 tahun ini - harus
        // ikut dijumlah.
        RincianPemeliharaan::factory()->jasa()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 60000,
        ]);

        // Rincian Jasa Pemeliharaan PC dll TW1 tahun ini - harus
        // DITAMBAHKAN ke total di atas (dari menu SUMBER LAIN).
        RincianPemeliharaanPc::factory()->jasa()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 40000,
        ]);

        // Baris jenis BARANG di kedua menu sumber, TW & sekolah yang
        // sama - TIDAK BOLEH ikut kolom "upah_pemeliharaan" (ini sumber
        // kolom "pemeliharaan", sudah diuji terpisah di atas).
        RincianPemeliharaan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianPemeliharaan::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 999999,
        ]);
        RincianPemeliharaanPc::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianPemeliharaanPc::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 999999,
        ]);

        // Baris TW2 tahun ini untuk sekolah yang sama - TIDAK BOLEH ikut
        // terhitung di tab TW1.
        RincianPemeliharaan::factory()->jasa()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 2,
            'total_harga' => 888888,
        ]);

        // Baris TW1 tahun ini untuk sekolah LAIN - TIDAK BOLEH ikut
        // terhitung di baris sekolah ini.
        RincianPemeliharaanPc::factory()->jasa()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 654321,
        ]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        // 60.000 (Rincian Jasa Pemeliharaan) + 40.000 (Rincian Jasa Pemeliharaan PC dll) = 100.000.
        $this->assertSame('100000', $component->get("baris.{$sekolah->id}.upah_pemeliharaan"));
        $this->assertSame('654321', $component->get("baris.{$sekolahLain->id}.upah_pemeliharaan"));

        // Baris JUMLAH (total footer) - sum kedua sekolah = 100.000 + 654.321.
        $totalBaris = $component->viewData('totalBaris');
        $this->assertSame(754321, $totalBaris['upah_pemeliharaan']);

        // Pindah ke TW2 - nilai sekolah ini harus 888.888 (baris TW2),
        // BUKAN 100.000 lagi (bukti terisolasi per triwulan).
        $component->call('pindahTab', 'tw2');
        $this->assertSame('888888', $component->get("baris.{$sekolah->id}.upah_pemeliharaan"));
    }

    public function test_upah_pemeliharaan_nol_kalau_belum_ada_data_rincian_jasa_pemeliharaan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertSame('0', $component->get("baris.{$sekolah->id}.upah_pemeliharaan"));
    }

    public function test_tab_rekap_menampilkan_upah_pemeliharaan_per_tw_dan_jumlahnya(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        foreach ([1, 2, 3, 4] as $tw) {
            RincianPemeliharaan::factory()->jasa()->create([
                'profil_sekolah_id' => $sekolah->id,
                'tahun' => now()->year,
                'triwulan' => $tw,
                'total_harga' => 100000 * $tw,
            ]);
            RincianPemeliharaanPc::factory()->jasa()->create([
                'profil_sekolah_id' => $sekolah->id,
                'tahun' => now()->year,
                'triwulan' => $tw,
                'total_harga' => 10000 * $tw,
            ]);
        }
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('pindahTab', 'rekap');

        $rekap = $component->viewData('rekapPerSekolah');
        $blok = $rekap[0];

        // TW1 = 100.000 + 10.000 = 110.000, TW4 = 400.000 + 40.000 = 440.000.
        $this->assertSame(110000, $blok['perTriwulan'][1]['upah_pemeliharaan']);
        $this->assertSame(440000, $blok['perTriwulan'][4]['upah_pemeliharaan']);
        // Jumlah 4 TW = 110rb+220rb+330rb+440rb = 1,1jt.
        $this->assertSame(1100000, $blok['jumlah']['upah_pemeliharaan']);
    }

    public function test_biaya_pendaftaran_lomba_bimtek_workshop_tidak_bisa_diedit_manual(): void
    {
        // Sejak permintaan user 2026-09-17 (lanjutan Part 32 keempat),
        // kolom ini READ-ONLY - diambil otomatis dari menu Biaya
        // Pendaftaran Lomba/Bimtek/Workshop, BUKAN lagi diisi manual
        // (lihat App\Models\LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN).
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.biaya_pendaftaran_lomba_bimtek_workshop", '500000')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('laporan_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_biaya_pendaftaran_lomba_bimtek_workshop_diambil_dari_total_biaya_pendaftaran_lomba_tw_yang_sama(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        // 2 baris Biaya Pendaftaran Lomba TW1 tahun ini untuk sekolah ini - harus dijumlah.
        BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 2,
            'tarif_harga' => 100000,
            'jumlah' => 200000,
        ]);
        BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 1,
            'tarif_harga' => 150000,
            'jumlah' => 150000,
        ]);

        // Baris TW2 tahun ini untuk sekolah yang sama - TIDAK BOLEH ikut
        // terhitung di tab TW1.
        BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 2,
            'jumlah' => 999999,
        ]);

        // Baris TW1 tahun ini untuk sekolah LAIN - TIDAK BOLEH ikut
        // terhitung di baris sekolah ini.
        BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 777777,
        ]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        // 200.000 + 150.000 = 350.000.
        $this->assertSame('350000', $component->get("baris.{$sekolah->id}.biaya_pendaftaran_lomba_bimtek_workshop"));
        $this->assertSame('777777', $component->get("baris.{$sekolahLain->id}.biaya_pendaftaran_lomba_bimtek_workshop"));

        // Baris JUMLAH (total footer) - sum kedua sekolah = 350.000 + 777.777.
        $totalBaris = $component->viewData('totalBaris');
        $this->assertSame(1127777, $totalBaris['biaya_pendaftaran_lomba_bimtek_workshop']);

        // Pindah ke TW2 - nilai sekolah ini harus 999.999 (baris TW2),
        // BUKAN 350.000 lagi (bukti terisolasi per triwulan).
        $component->call('pindahTab', 'tw2');
        $this->assertSame('999999', $component->get("baris.{$sekolah->id}.biaya_pendaftaran_lomba_bimtek_workshop"));
    }

    public function test_biaya_pendaftaran_lomba_bimtek_workshop_nol_kalau_belum_ada_data(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertSame('0', $component->get("baris.{$sekolah->id}.biaya_pendaftaran_lomba_bimtek_workshop"));
    }

    public function test_tab_rekap_menampilkan_biaya_pendaftaran_lomba_bimtek_workshop_per_tw_dan_jumlahnya(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        foreach ([1, 2, 3, 4] as $tw) {
            BiayaPendaftaranLomba::factory()->create([
                'profil_sekolah_id' => $sekolah->id,
                'tahun' => now()->year,
                'triwulan' => $tw,
                'volume' => 1,
                'tarif_harga' => 100000 * $tw,
                'jumlah' => 100000 * $tw,
            ]);
        }
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('pindahTab', 'rekap');

        $rekap = $component->viewData('rekapPerSekolah');
        $blok = $rekap[0];

        $this->assertSame(100000, $blok['perTriwulan'][1]['biaya_pendaftaran_lomba_bimtek_workshop']);
        $this->assertSame(400000, $blok['perTriwulan'][4]['biaya_pendaftaran_lomba_bimtek_workshop']);
        // Jumlah 4 TW = 100rb+200rb+300rb+400rb = 1jt.
        $this->assertSame(1000000, $blok['jumlah']['biaya_pendaftaran_lomba_bimtek_workshop']);
    }

    public function test_honor_kegiatan_tidak_bisa_diedit_manual(): void
    {
        // Sejak permintaan user 2026-09-17 (lanjutan Part 32 keempat),
        // kolom ini READ-ONLY - diambil otomatis dari menu Belanja
        // Honor Kegiatan (tab utama "Honor Kegiatan"), BUKAN lagi
        // diisi manual (lihat
        // App\Models\LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN).
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.honor_kegiatan", '500000')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('laporan_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_honor_kegiatan_diambil_dari_total_belanja_honor_kegiatan_jenis_honor_kegiatan_tw_yang_sama(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        // 2 baris Belanja Honor Kegiatan (jenis honor_kegiatan) TW1
        // tahun ini untuk sekolah ini - harus dijumlah.
        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_HONOR_KEGIATAN,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 200000,
        ]);
        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_HONOR_KEGIATAN,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 150000,
        ]);

        // Baris jenis LAIN (Belanja Makan & Minum / Belanja Perjalanan
        // Dinas), TW & sekolah yang sama - TIDAK BOLEH ikut kolom
        // "honor_kegiatan" (masing-masing sumber kolom lain, diuji
        // terpisah di bawah).
        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_MAKAN_MINUM,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 999999,
        ]);
        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_PERJALANAN_DINAS,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 888888,
        ]);

        // Baris TW2 tahun ini untuk sekolah yang sama - TIDAK BOLEH ikut
        // terhitung di tab TW1.
        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_HONOR_KEGIATAN,
            'tahun' => now()->year,
            'triwulan' => 2,
            'jumlah' => 777777,
        ]);

        // Baris TW1 tahun ini untuk sekolah LAIN - TIDAK BOLEH ikut
        // terhitung di baris sekolah ini.
        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_HONOR_KEGIATAN,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 654321,
        ]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        // 200.000 + 150.000 = 350.000.
        $this->assertSame('350000', $component->get("baris.{$sekolah->id}.honor_kegiatan"));
        $this->assertSame('654321', $component->get("baris.{$sekolahLain->id}.honor_kegiatan"));

        // Baris JUMLAH (total footer) - sum kedua sekolah = 350.000 + 654.321.
        $totalBaris = $component->viewData('totalBaris');
        $this->assertSame(1004321, $totalBaris['honor_kegiatan']);

        // Pindah ke TW2 - nilai sekolah ini harus 777.777 (baris TW2),
        // BUKAN 350.000 lagi (bukti terisolasi per triwulan).
        $component->call('pindahTab', 'tw2');
        $this->assertSame('777777', $component->get("baris.{$sekolah->id}.honor_kegiatan"));
    }

    public function test_honor_kegiatan_nol_kalau_belum_ada_data(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertSame('0', $component->get("baris.{$sekolah->id}.honor_kegiatan"));
    }

    public function test_tab_rekap_menampilkan_honor_kegiatan_per_tw_dan_jumlahnya(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        foreach ([1, 2, 3, 4] as $tw) {
            BelanjaHonorKegiatan::factory()->create([
                'profil_sekolah_id' => $sekolah->id,
                'jenis' => BelanjaHonorKegiatan::JENIS_HONOR_KEGIATAN,
                'tahun' => now()->year,
                'triwulan' => $tw,
                'jumlah' => 100000 * $tw,
            ]);
        }
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('pindahTab', 'rekap');

        $rekap = $component->viewData('rekapPerSekolah');
        $blok = $rekap[0];

        $this->assertSame(100000, $blok['perTriwulan'][1]['honor_kegiatan']);
        $this->assertSame(400000, $blok['perTriwulan'][4]['honor_kegiatan']);
        // Jumlah 4 TW = 100rb+200rb+300rb+400rb = 1jt.
        $this->assertSame(1000000, $blok['jumlah']['honor_kegiatan']);
    }

    public function test_makan_dan_minum_kegiatan_tidak_bisa_diedit_manual(): void
    {
        // Sejak permintaan user 2026-09-17 (lanjutan Part 32 keempat),
        // kolom ini READ-ONLY - diambil otomatis dari menu Belanja
        // Honor Kegiatan (tab utama "Belanja Makan & Minum"), BUKAN
        // lagi diisi manual (lihat
        // App\Models\LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN).
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.makan_dan_minum_kegiatan", '500000')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('laporan_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_makan_dan_minum_kegiatan_diambil_dari_total_belanja_honor_kegiatan_jenis_makan_minum_tw_yang_sama(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_MAKAN_MINUM,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 120000,
        ]);
        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_MAKAN_MINUM,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 80000,
        ]);

        // Baris jenis LAIN, TW & sekolah yang sama - TIDAK BOLEH ikut
        // kolom "makan_dan_minum_kegiatan".
        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_HONOR_KEGIATAN,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 999999,
        ]);
        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_PERJALANAN_DINAS,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 888888,
        ]);

        // Baris TW2 tahun ini untuk sekolah yang sama - TIDAK BOLEH ikut
        // terhitung di tab TW1.
        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_MAKAN_MINUM,
            'tahun' => now()->year,
            'triwulan' => 2,
            'jumlah' => 666666,
        ]);

        // Baris TW1 tahun ini untuk sekolah LAIN - TIDAK BOLEH ikut
        // terhitung di baris sekolah ini.
        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_MAKAN_MINUM,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 321321,
        ]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        // 120.000 + 80.000 = 200.000.
        $this->assertSame('200000', $component->get("baris.{$sekolah->id}.makan_dan_minum_kegiatan"));
        $this->assertSame('321321', $component->get("baris.{$sekolahLain->id}.makan_dan_minum_kegiatan"));

        $totalBaris = $component->viewData('totalBaris');
        $this->assertSame(521321, $totalBaris['makan_dan_minum_kegiatan']);

        $component->call('pindahTab', 'tw2');
        $this->assertSame('666666', $component->get("baris.{$sekolah->id}.makan_dan_minum_kegiatan"));
    }

    public function test_makan_dan_minum_kegiatan_nol_kalau_belum_ada_data(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertSame('0', $component->get("baris.{$sekolah->id}.makan_dan_minum_kegiatan"));
    }

    public function test_tab_rekap_menampilkan_makan_dan_minum_kegiatan_per_tw_dan_jumlahnya(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        foreach ([1, 2, 3, 4] as $tw) {
            BelanjaHonorKegiatan::factory()->create([
                'profil_sekolah_id' => $sekolah->id,
                'jenis' => BelanjaHonorKegiatan::JENIS_MAKAN_MINUM,
                'tahun' => now()->year,
                'triwulan' => $tw,
                'jumlah' => 100000 * $tw,
            ]);
        }
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('pindahTab', 'rekap');

        $rekap = $component->viewData('rekapPerSekolah');
        $blok = $rekap[0];

        $this->assertSame(100000, $blok['perTriwulan'][1]['makan_dan_minum_kegiatan']);
        $this->assertSame(400000, $blok['perTriwulan'][4]['makan_dan_minum_kegiatan']);
        $this->assertSame(1000000, $blok['jumlah']['makan_dan_minum_kegiatan']);
    }

    public function test_perjalanan_dinas_tidak_bisa_diedit_manual(): void
    {
        // Sejak permintaan user 2026-09-17 (lanjutan Part 32 keempat),
        // kolom ini READ-ONLY - diambil otomatis dari menu Belanja
        // Honor Kegiatan (tab utama "Belanja Perjalanan Dinas"), BUKAN
        // lagi diisi manual (lihat
        // App\Models\LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN).
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.perjalanan_dinas", '500000')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('laporan_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_perjalanan_dinas_diambil_dari_total_belanja_honor_kegiatan_jenis_perjalanan_dinas_tw_yang_sama(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_PERJALANAN_DINAS,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 300000,
        ]);
        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_PERJALANAN_DINAS,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 200000,
        ]);

        // Baris jenis LAIN, TW & sekolah yang sama - TIDAK BOLEH ikut
        // kolom "perjalanan_dinas".
        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_HONOR_KEGIATAN,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 999999,
        ]);
        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_MAKAN_MINUM,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 888888,
        ]);

        // Baris TW2 tahun ini untuk sekolah yang sama - TIDAK BOLEH ikut
        // terhitung di tab TW1.
        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_PERJALANAN_DINAS,
            'tahun' => now()->year,
            'triwulan' => 2,
            'jumlah' => 555555,
        ]);

        // Baris TW1 tahun ini untuk sekolah LAIN - TIDAK BOLEH ikut
        // terhitung di baris sekolah ini.
        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_PERJALANAN_DINAS,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 111111,
        ]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        // 300.000 + 200.000 = 500.000.
        $this->assertSame('500000', $component->get("baris.{$sekolah->id}.perjalanan_dinas"));
        $this->assertSame('111111', $component->get("baris.{$sekolahLain->id}.perjalanan_dinas"));

        $totalBaris = $component->viewData('totalBaris');
        $this->assertSame(611111, $totalBaris['perjalanan_dinas']);

        $component->call('pindahTab', 'tw2');
        $this->assertSame('555555', $component->get("baris.{$sekolah->id}.perjalanan_dinas"));
    }

    public function test_perjalanan_dinas_nol_kalau_belum_ada_data(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertSame('0', $component->get("baris.{$sekolah->id}.perjalanan_dinas"));
    }

    public function test_tab_rekap_menampilkan_perjalanan_dinas_per_tw_dan_jumlahnya(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        foreach ([1, 2, 3, 4] as $tw) {
            BelanjaHonorKegiatan::factory()->create([
                'profil_sekolah_id' => $sekolah->id,
                'jenis' => BelanjaHonorKegiatan::JENIS_PERJALANAN_DINAS,
                'tahun' => now()->year,
                'triwulan' => $tw,
                'jumlah' => 100000 * $tw,
            ]);
        }
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('pindahTab', 'rekap');

        $rekap = $component->viewData('rekapPerSekolah');
        $blok = $rekap[0];

        $this->assertSame(100000, $blok['perTriwulan'][1]['perjalanan_dinas']);
        $this->assertSame(400000, $blok['perTriwulan'][4]['perjalanan_dinas']);
        $this->assertSame(1000000, $blok['jumlah']['perjalanan_dinas']);
    }

    public function test_kolom_8_10_dan_20_25_semuanya_sudah_read_only(): void
    {
        // Kolom 8-10 (Saldo Awal Dana BOSP, Penerimaan Dana BOS, Total
        // Penerimaan) - KOLOM MANUAL TERAKHIR di menu ini - JUGA sudah
        // read-only sejak permintaan user 2026-09-17 (lanjutan Part 32
        // ketujuh), menyusul kolom 20-25 (Total Belanja Barang dan Jasa,
        // Peralatan dan Mesin KIB B, Aset Tetap Lainnya KIB E, Total
        // Belanja Modal, Total Realisasi Dana BOS, Sisa Dana BOS) yang
        // sudah read-only sejak lanjutan Part 32 kelima - percobaan set
        // langsung ke kolom manapun di sini HARUS ditolak, TIDAK ADA
        // baris SAMA SEKALI yang tersimpan (BEDA dari sebelumnya, saat
        // kolom 10 masih bisa tersimpan sendirian).
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.saldo_awal_dana_bosp", '11111')
            ->set("baris.{$sekolah->id}.penerimaan_dana_bos", '22222')
            ->set("baris.{$sekolah->id}.total_penerimaan", '12345')
            ->set("baris.{$sekolah->id}.total_belanja_barang_dan_jasa", '23456')
            ->set("baris.{$sekolah->id}.peralatan_dan_mesin_kib_b", '11111')
            ->set("baris.{$sekolah->id}.aset_tetap_lainnya_kib_e", '22222')
            ->set("baris.{$sekolah->id}.total_belanja_modal", '34567')
            ->set("baris.{$sekolah->id}.total_realisasi_dana_bos", '45678')
            ->set("baris.{$sekolah->id}.sisa_dana_bos", '56789')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('laporan_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_total_belanja_barang_dan_jasa_dijumlah_dari_8_kolom_sumber_tidak_termasuk_belanja_barang_habis_pakai(): void
    {
        // Rumus eksplisit user (permintaan 2026-09-17, lanjutan Part 32
        // kelima): kolom 20 = kolom 12+13+14+15+16+17+18+19, SENGAJA TIDAK
        // mengikutsertakan kolom 11 (belanja_barang_pakai_habis_persediaan).
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        // Kolom 11 - HARUS TIDAK ikut dijumlah ke kolom 20.
        RincianBelanjaBarangHabisPakai::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 999999,
        ]);

        // Kolom 12 (jasa_tenaga_pendidik_dan_kependidikan).
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah_honor' => 100000,
        ]);
        // Kolom 13 (daya_dan_jasa).
        LanggananDayaJasa::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 200000,
        ]);
        // Kolom 16 (biaya_pendaftaran_lomba_bimtek_workshop).
        BiayaPendaftaranLomba::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 300000,
        ]);
        // Kolom 17 (honor_kegiatan).
        BelanjaHonorKegiatan::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => BelanjaHonorKegiatan::JENIS_HONOR_KEGIATAN,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 400000,
        ]);
        // Kolom 14, 15, 18, 19 sengaja dibiarkan kosong (0).

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        // Kolom 11 tetap tampil 999.999 apa adanya (dihitung sendiri).
        $this->assertSame('999999', $component->get("baris.{$sekolah->id}.belanja_barang_pakai_habis_persediaan"));

        // Kolom 20 = 100rb+200rb+300rb+400rb = 1jt (BUKAN 1jt+999.999).
        $this->assertSame('1000000', $component->get("baris.{$sekolah->id}.total_belanja_barang_dan_jasa"));
    }

    public function test_peralatan_dan_mesin_kib_b_dan_aset_tetap_lainnya_kib_e_diambil_dari_total_harga_rincian_belanja_modal_tw_yang_sama(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        RincianBelanjaModal::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianBelanjaModal::JENIS_PERALATAN_MESIN,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 2,
            'harga_satuan' => 1000000,
            'total_harga' => 2000000,
        ]);
        RincianBelanjaModal::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianBelanjaModal::JENIS_ASET_TETAP_LAINNYA,
            'tahun' => now()->year,
            'triwulan' => 1,
            'volume' => 3,
            'harga_satuan' => 500000,
            'total_harga' => 1500000,
        ]);

        // Baris TW2 tahun ini - TIDAK BOLEH ikut terhitung di tab TW1.
        RincianBelanjaModal::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianBelanjaModal::JENIS_PERALATAN_MESIN,
            'tahun' => now()->year,
            'triwulan' => 2,
            'total_harga' => 999999,
        ]);

        // Baris TW1 tahun ini untuk sekolah LAIN - TIDAK BOLEH ikut.
        RincianBelanjaModal::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'jenis' => RincianBelanjaModal::JENIS_PERALATAN_MESIN,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 777777,
        ]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertSame('2000000', $component->get("baris.{$sekolah->id}.peralatan_dan_mesin_kib_b"));
        $this->assertSame('1500000', $component->get("baris.{$sekolah->id}.aset_tetap_lainnya_kib_e"));
        $this->assertSame('777777', $component->get("baris.{$sekolahLain->id}.peralatan_dan_mesin_kib_b"));

        // Pindah ke TW2 - bukti terisolasi per triwulan.
        $component->call('pindahTab', 'tw2');
        $this->assertSame('999999', $component->get("baris.{$sekolah->id}.peralatan_dan_mesin_kib_b"));
    }

    public function test_total_belanja_modal_dijumlah_dari_peralatan_dan_mesin_ditambah_aset_tetap_lainnya(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        RincianBelanjaModal::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianBelanjaModal::JENIS_PERALATAN_MESIN,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 2000000,
        ]);
        RincianBelanjaModal::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianBelanjaModal::JENIS_ASET_TETAP_LAINNYA,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 1500000,
        ]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertSame('3500000', $component->get("baris.{$sekolah->id}.total_belanja_modal"));
    }

    public function test_total_realisasi_dana_bos_dijumlah_dari_belanja_habis_pakai_ditambah_total_belanja_barang_jasa_ditambah_total_belanja_modal(): void
    {
        // Rumus eksplisit user: kolom 24 = kolom 11 + kolom 20 + kolom 23
        // - kolom 11 yang SENGAJA TIDAK diikutkan di kolom 20 (lihat test
        // di atas) kembali digabungkan terpisah di sini.
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        // Kolom 11 = 500.000.
        RincianBelanjaBarangHabisPakai::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 500000,
        ]);
        // Kolom 13 (daya_dan_jasa, salah satu sumber kolom 20) = 1.000.000.
        LanggananDayaJasa::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'jumlah' => 1000000,
        ]);
        // Kolom 21 (peralatan_dan_mesin_kib_b, salah satu sumber kolom 23) = 700.000.
        RincianBelanjaModal::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianBelanjaModal::JENIS_PERALATAN_MESIN,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 700000,
        ]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        // Kolom 20 = 1.000.000 (hanya dari daya_dan_jasa). Kolom 23 =
        // 700.000 (hanya dari peralatan_dan_mesin_kib_b). Kolom 24 =
        // 500.000 + 1.000.000 + 700.000 = 2.200.000.
        $this->assertSame('1000000', $component->get("baris.{$sekolah->id}.total_belanja_barang_dan_jasa"));
        $this->assertSame('700000', $component->get("baris.{$sekolah->id}.total_belanja_modal"));
        $this->assertSame('2200000', $component->get("baris.{$sekolah->id}.total_realisasi_dana_bos"));
    }

    public function test_sisa_dana_bos_dihitung_dari_total_penerimaan_dikurangi_total_realisasi_dana_bos(): void
    {
        // Rumus eksplisit user: kolom 25 = kolom 10 (Total Penerimaan)
        // dikurangi kolom 24 (Total Realisasi Dana BOS). Sejak permintaan
        // user 2026-09-17 (lanjutan Part 32 ketujuh) kolom 10 SUDAH JADI
        // HASIL RUMUS juga (kolom 8 + kolom 9) - TW1 diambil dari
        // DanaBospTahap (`saldo_bosp_tw4_tahun_sebelumnya` &
        // `penerimaan_tahap_1`), BUKAN lagi lewat set() langsung.
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // Kolom 8 (Saldo Awal Dana BOSP) TW1 = 500.000, kolom 9
        // (Penerimaan Dana BOS) TW1 = 2.500.000 -> kolom 10 (Total
        // Penerimaan) = 3.000.000.
        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'saldo_bosp_tw4_tahun_sebelumnya' => 500000,
            'penerimaan_tahap_1' => 2500000,
        ]);

        // Kolom 11 = 500.000 -> ikut ke kolom 24 (Total Realisasi Dana BOS).
        RincianBelanjaBarangHabisPakai::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 500000,
        ]);

        $this->bukaGerbangVerval($sekolah, now()->year);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        // Kolom 10 = 500.000 + 2.500.000 = 3.000.000. Kolom 24 = 500.000
        // (kolom 20 & 23 = 0 karena tidak ada data sumber lain). Kolom 25
        // = 3.000.000 - 500.000 = 2.500.000.
        $this->assertSame('3000000', $component->get("baris.{$sekolah->id}.total_penerimaan"));
        $this->assertSame('500000', $component->get("baris.{$sekolah->id}.total_realisasi_dana_bos"));
        $this->assertSame('2500000', $component->get("baris.{$sekolah->id}.sisa_dana_bos"));
    }

    public function test_tab_rekap_menampilkan_total_belanja_modal_per_tw_dan_jumlahnya(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        foreach ([1, 2, 3, 4] as $tw) {
            RincianBelanjaModal::factory()->create([
                'profil_sekolah_id' => $sekolah->id,
                'jenis' => RincianBelanjaModal::JENIS_PERALATAN_MESIN,
                'tahun' => now()->year,
                'triwulan' => $tw,
                'total_harga' => 100000 * $tw,
            ]);
            RincianBelanjaModal::factory()->create([
                'profil_sekolah_id' => $sekolah->id,
                'jenis' => RincianBelanjaModal::JENIS_ASET_TETAP_LAINNYA,
                'tahun' => now()->year,
                'triwulan' => $tw,
                'total_harga' => 50000 * $tw,
            ]);
        }
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('pindahTab', 'rekap');

        $rekap = $component->viewData('rekapPerSekolah');
        $blok = $rekap[0];

        // TW1: peralatan 100rb + aset 50rb = 150rb. TW4: 400rb+200rb=600rb.
        $this->assertSame(150000, $blok['perTriwulan'][1]['total_belanja_modal']);
        $this->assertSame(600000, $blok['perTriwulan'][4]['total_belanja_modal']);
        // Jumlah 4 TW = 150rb+300rb+450rb+600rb = 1.500.000.
        $this->assertSame(1500000, $blok['jumlah']['total_belanja_modal']);
    }

    public function test_saldo_rekening_kas_bank_tidak_bisa_diedit_manual(): void
    {
        // Sejak permintaan user 2026-09-17 (lanjutan Part 32 keenam),
        // kolom ini READ-ONLY - diambil otomatis dari menu Dana BOSP
        // Tahap 1 & 2 (tab "Tarik Tunai BOSP"), BUKAN lagi diisi manual
        // (lihat App\Models\LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN).
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.saldo_rekening_kas_bank", '500000')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('laporan_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_saldo_kas_tunai_tidak_bisa_diedit_manual(): void
    {
        // Sama seperti test_saldo_rekening_kas_bank_tidak_bisa_diedit_manual()
        // di atas, untuk kolom 27 (saldo_kas_tunai).
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.saldo_kas_tunai", '500000')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('laporan_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_saldo_rekening_kas_bank_diambil_otomatis_dari_dana_bosp_tahap_tw_yang_sama(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        // DanaBospTahap sekolah ini, tahun ini - kolom TW2 harus dipakai
        // untuk tab TW2, TW1/TW3 TIDAK BOLEH ikut (DanaBospTahap 1 baris
        // per sekolah per TAHUN, kolom TW1-4 sudah terpisah per kolom).
        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'saldo_kas_bank_tw1' => 111111,
            'saldo_kas_bank_tw2' => 222222,
            'saldo_kas_bank_tw3' => 333333,
        ]);

        // DanaBospTahap sekolah LAIN, tahun ini, TW2 - TIDAK BOLEH ikut
        // terhitung di baris sekolah di atas.
        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'tahun' => now()->year,
            'saldo_kas_bank_tw2' => 777777,
        ]);

        // DanaBospTahap sekolah ini, TAHUN LAIN, TW2 - TIDAK BOLEH ikut
        // terhitung (bukti isolasi tahun).
        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year - 1,
            'saldo_kas_bank_tw2' => 999999,
        ]);

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('pindahTab', 'tw2');

        $this->assertSame('222222', $component->get("baris.{$sekolah->id}.saldo_rekening_kas_bank"));
        $this->assertSame('777777', $component->get("baris.{$sekolahLain->id}.saldo_rekening_kas_bank"));
    }

    public function test_saldo_kas_tunai_diambil_otomatis_dari_dana_bosp_tahap_tw_yang_sama(): void
    {
        // Pola PERSIS sama dengan
        // test_saldo_rekening_kas_bank_diambil_otomatis_dari_dana_bosp_tahap_tw_yang_sama()
        // di atas, bedanya HANYA nama kolom sumber (saldo_kas_tunai_tw{n}),
        // & diuji pada tab TW3 supaya bukti isolasi kolomnya berbeda.
        $sekolah = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'saldo_kas_tunai_tw1' => 111111,
            'saldo_kas_tunai_tw3' => 333333,
            'saldo_kas_tunai_tw4' => 444444,
        ]);

        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolahLain->id,
            'tahun' => now()->year,
            'saldo_kas_tunai_tw3' => 888888,
        ]);

        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year - 1,
            'saldo_kas_tunai_tw3' => 555555,
        ]);

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('pindahTab', 'tw3');

        $this->assertSame('333333', $component->get("baris.{$sekolah->id}.saldo_kas_tunai"));
        $this->assertSame('888888', $component->get("baris.{$sekolahLain->id}.saldo_kas_tunai"));
    }

    public function test_saldo_rekening_kas_bank_dan_saldo_kas_tunai_nol_kalau_belum_ada_data_dana_bosp_tahap(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertSame('0', $component->get("baris.{$sekolah->id}.saldo_rekening_kas_bank"));
        $this->assertSame('0', $component->get("baris.{$sekolah->id}.saldo_kas_tunai"));
    }

    public function test_saldo_rekening_kas_bank_dan_saldo_kas_tunai_berubah_otomatis_saat_dana_bosp_tahap_berubah(): void
    {
        // Kolom 26/27 dihitung REAL-TIME setiap render (TIDAK PERNAH
        // disimpan/di-cache) - begitu kolom sumbernya di DanaBospTahap
        // diubah, nilai yang tampil di Laporan Realisasi BOSP ikut
        // berubah pada render BERIKUTNYA, TANPA perlu reload/reopen
        // halaman ini sendiri (pola sama dengan kolom 11-25 lain).
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $danaBospTahap = DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'saldo_kas_bank_tw1' => 1000000,
            'saldo_kas_tunai_tw1' => 500000,
        ]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $this->assertSame('1000000', $component->get("baris.{$sekolah->id}.saldo_rekening_kas_bank"));
        $this->assertSame('500000', $component->get("baris.{$sekolah->id}.saldo_kas_tunai"));

        // Ubah data sumbernya LANGSUNG di DanaBospTahap (bukan lewat
        // Livewire component ini sama sekali).
        $danaBospTahap->update([
            'saldo_kas_bank_tw1' => 9000000,
            'saldo_kas_tunai_tw1' => 4500000,
        ]);

        // Paksa render ulang (pola sama seperti test isolasi triwulan
        // lain di file ini, mis. test_data_terisolasi_per_triwulan) -
        // TIDAK ADA aksi apapun yang menyentuh kolom 26/27 secara
        // langsung, murni efek dari data sumber yang berubah.
        $component->call('pindahTab', 'tw1');

        $this->assertSame('9000000', $component->get("baris.{$sekolah->id}.saldo_rekening_kas_bank"));
        $this->assertSame('4500000', $component->get("baris.{$sekolah->id}.saldo_kas_tunai"));
    }

    public function test_tab_rekap_menampilkan_saldo_rekening_kas_bank_dan_saldo_kas_tunai_per_tw_dan_jumlahnya(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'saldo_kas_bank_tw1' => 100000,
            'saldo_kas_bank_tw2' => 200000,
            'saldo_kas_bank_tw3' => 300000,
            'saldo_kas_bank_tw4' => 400000,
            'saldo_kas_tunai_tw1' => 10000,
            'saldo_kas_tunai_tw2' => 20000,
            'saldo_kas_tunai_tw3' => 30000,
            'saldo_kas_tunai_tw4' => 40000,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('pindahTab', 'rekap');

        $rekap = $component->viewData('rekapPerSekolah');
        $blok = $rekap[0];

        $this->assertSame(100000, $blok['perTriwulan'][1]['saldo_rekening_kas_bank']);
        $this->assertSame(400000, $blok['perTriwulan'][4]['saldo_rekening_kas_bank']);
        $this->assertSame(10000, $blok['perTriwulan'][1]['saldo_kas_tunai']);
        $this->assertSame(40000, $blok['perTriwulan'][4]['saldo_kas_tunai']);

        // Jumlah 4 TW: saldo_rekening_kas_bank = 100rb+200rb+300rb+400rb = 1jt.
        $this->assertSame(1000000, $blok['jumlah']['saldo_rekening_kas_bank']);
        // Jumlah 4 TW: saldo_kas_tunai = 10rb+20rb+30rb+40rb = 100rb.
        $this->assertSame(100000, $blok['jumlah']['saldo_kas_tunai']);
    }

    public function test_verifikasi_jumlah_dan_verifikasi_saldo_sama_saat_sisa_dana_bos_cocok(): void
    {
        // Rumus eksplisit user (jawaban AskUserQuestion 2026-09-17):
        // Kolom 28 = Kolom 26 + Kolom 27; Kolom 29 = "SAMA" kalau Kolom 25
        // == Kolom 28. Kolom 25 (Sisa Dana BOS) = Total Penerimaan - Total
        // Realisasi Dana BOS - karena belum ada data realisasi apapun,
        // Total Realisasi Dana BOS = 0, sehingga Sisa Dana BOS = Total
        // Penerimaan persis. Sejak permintaan user 2026-09-17 (lanjutan
        // Part 32 ketujuh), Total Penerimaan (kolom 10) SENDIRI sudah
        // hasil rumus (kolom 8 + kolom 9, TW1 dari DanaBospTahap
        // `saldo_bosp_tw4_tahun_sebelumnya`/`penerimaan_tahap_1`) - BUKAN
        // lagi bisa di-`set()` langsung, sama seperti kolom 26/27 (lihat
        // test_saldo_rekening_kas_bank_tidak_bisa_diedit_manual()/
        // test_saldo_kas_tunai_tidak_bisa_diedit_manual() di atas).
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'saldo_bosp_tw4_tahun_sebelumnya' => 0,
            'penerimaan_tahap_1' => 10000000,
            'saldo_kas_bank_tw1' => 7000000,
            'saldo_kas_tunai_tw1' => 3000000,
        ]);

        $this->bukaGerbangVerval($sekolah, now()->year);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        $this->assertSame('10000000', $component->get("baris.{$sekolah->id}.total_penerimaan"));
        $this->assertSame('10000000', $component->get("baris.{$sekolah->id}.sisa_dana_bos"));
        $this->assertSame('7000000', $component->get("baris.{$sekolah->id}.saldo_rekening_kas_bank"));
        $this->assertSame('3000000', $component->get("baris.{$sekolah->id}.saldo_kas_tunai"));
        $this->assertSame('10000000', $component->get("baris.{$sekolah->id}.verifikasi_jumlah"));
        $this->assertSame(LaporanRealisasiBosp::VERIFIKASI_SAMA, $component->get("baris.{$sekolah->id}.verifikasi_saldo"));
    }

    public function test_verifikasi_saldo_tidak_sama_saat_sisa_dana_bos_berbeda(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'saldo_bosp_tw4_tahun_sebelumnya' => 0,
            'penerimaan_tahap_1' => 9999999,
            'saldo_kas_bank_tw1' => 7000000,
            'saldo_kas_tunai_tw1' => 3000000,
        ]);

        $this->bukaGerbangVerval($sekolah, now()->year);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        $this->assertSame('9999999', $component->get("baris.{$sekolah->id}.total_penerimaan"));
        $this->assertSame('9999999', $component->get("baris.{$sekolah->id}.sisa_dana_bos"));
        $this->assertSame('10000000', $component->get("baris.{$sekolah->id}.verifikasi_jumlah"));
        $this->assertSame(LaporanRealisasiBosp::VERIFIKASI_TIDAK_SAMA, $component->get("baris.{$sekolah->id}.verifikasi_saldo"));
    }

    public function test_verifikasi_saldo_tersembunyi_sampai_saldo_bank_dan_tunai_tersimpan_lalu_otomatis_konsisten(): void
    {
        // Jawaban AskUserQuestion 2026-09-17 (poin 1, "Saldo Kas Bank &
        // Kas Tunai TW itu saja (Recommended)"): kata "SAMA"/"TIDAK SAMA"
        // pada kolom 29 BELUM tampil (null) selama kolom 26/27 belum
        // tersimpan sama sekali - BUKAN cuma soal urutan pengisian antar
        // menu (yang sebelumnya diuji test ini sebelum kolom 10 ikut jadi
        // read-only), TAPI juga cerminan langsung dari aturan hide-until-
        // complete yang baru.
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $this->bukaGerbangVerval($sekolah, now()->year);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        // Belum ada DanaBospTahap sama sekali - kolom 8/9/10/25 dianggap 0
        // (bukan null, karena kolom 24 total_realisasi_dana_bos JUGA 0),
        // TAPI kolom 26/27 BELUM tersimpan (null) -> kolom 29 disembunyikan.
        $this->assertSame('0', $component->get("baris.{$sekolah->id}.total_penerimaan"));
        $this->assertSame('0', $component->get("baris.{$sekolah->id}.sisa_dana_bos"));
        $this->assertSame('0', $component->get("baris.{$sekolah->id}.saldo_rekening_kas_bank"));
        $this->assertSame('0', $component->get("baris.{$sekolah->id}.saldo_kas_tunai"));
        $this->assertSame('0', $component->get("baris.{$sekolah->id}.verifikasi_jumlah"));
        $this->assertSame('', $component->get("baris.{$sekolah->id}.verifikasi_saldo"));

        // Baru buat baris DanaBospTahap (menu LAIN, BUKAN lewat Livewire
        // component ini sama sekali) - SEKALIGUS mengisi kolom 8 (Saldo
        // Awal, dari saldo_bosp_tw4_tahun_sebelumnya) & kolom 26/27 (Saldo
        // Kas Bank/Tunai) - kolom 29 otomatis TERUNGKAP begitu render
        // berikutnya, TANPA perlu logic materialisasi apapun.
        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'saldo_bosp_tw4_tahun_sebelumnya' => 5000000,
            'penerimaan_tahap_1' => 0,
            'saldo_kas_bank_tw1' => 4000000,
            'saldo_kas_tunai_tw1' => 1000000,
        ]);
        $component->call('pindahTab', 'tw1');

        $this->assertSame('5000000', $component->get("baris.{$sekolah->id}.total_penerimaan"));
        $this->assertSame('5000000', $component->get("baris.{$sekolah->id}.sisa_dana_bos"));
        $this->assertSame('4000000', $component->get("baris.{$sekolah->id}.saldo_rekening_kas_bank"));
        $this->assertSame('1000000', $component->get("baris.{$sekolah->id}.saldo_kas_tunai"));
        $this->assertSame('5000000', $component->get("baris.{$sekolah->id}.verifikasi_jumlah"));
        $this->assertSame(LaporanRealisasiBosp::VERIFIKASI_SAMA, $component->get("baris.{$sekolah->id}.verifikasi_saldo"));
    }

    public function test_verifikasi_saldo_menggabungkan_sisa_dana_bos_dan_saldo_dari_dana_bosp_tahap_end_to_end(): void
    {
        // Uji end-to-end rantai penuh kolom 8/9/10/24/25/26/27/28/29:
        // kolom 8/9/10 dari DanaBospTahap, kolom 24 (Total Realisasi Dana
        // BOS) dari data Rincian Belanja Modal, kolom 25 (Sisa Dana BOS)
        // = Total Penerimaan - kolom 24, kolom 26/27 dari
        // App\Models\DanaBospTahap, kolom 28/29 dari kombinasi kolom
        // 25/26/27 - membuktikan seluruh rantai rumus (bukan cuma 1
        // kolom) bekerja benar bersama-sama.
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // Kolom 21 (peralatan_dan_mesin_kib_b) = 2.000.000 -> ikut ke
        // kolom 23 (Total Belanja Modal) -> ikut ke kolom 24 (Total
        // Realisasi Dana BOS).
        RincianBelanjaModal::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'jenis' => RincianBelanjaModal::JENIS_PERALATAN_MESIN,
            'tahun' => now()->year,
            'triwulan' => 1,
            'total_harga' => 2000000,
        ]);

        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'saldo_bosp_tw4_tahun_sebelumnya' => 0,
            'penerimaan_tahap_1' => 10000000,
            'saldo_kas_bank_tw1' => 5500000,
            'saldo_kas_tunai_tw1' => 2500000,
        ]);

        $this->bukaGerbangVerval($sekolah, now()->year);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        $this->assertSame('10000000', $component->get("baris.{$sekolah->id}.total_penerimaan"));

        // Kolom 24 = 2.000.000 (hanya dari peralatan_dan_mesin_kib_b,
        // kolom 11/20 lain 0). Kolom 25 = 10.000.000 - 2.000.000 =
        // 8.000.000.
        $this->assertSame('2000000', $component->get("baris.{$sekolah->id}.total_realisasi_dana_bos"));
        $this->assertSame('8000000', $component->get("baris.{$sekolah->id}.sisa_dana_bos"));

        // Kolom 26/27 dari DanaBospTahap TW1: 5.500.000 + 2.500.000 =
        // 8.000.000.
        $this->assertSame('5500000', $component->get("baris.{$sekolah->id}.saldo_rekening_kas_bank"));
        $this->assertSame('2500000', $component->get("baris.{$sekolah->id}.saldo_kas_tunai"));

        // Kolom 28 = 8.000.000, SAMA DENGAN kolom 25 -> kolom 29 = "SAMA".
        $this->assertSame('8000000', $component->get("baris.{$sekolah->id}.verifikasi_jumlah"));
        $this->assertSame(LaporanRealisasiBosp::VERIFIKASI_SAMA, $component->get("baris.{$sekolah->id}.verifikasi_saldo"));
    }

    public function test_field_hasil_rumus_verifikasi_tidak_bisa_diset_langsung(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.verifikasi_jumlah", '999999999')
            ->set("baris.{$sekolah->id}.verifikasi_saldo", 'SAMA');

        $this->assertDatabaseMissing('laporan_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_admin_bosp_tidak_bisa_mengisi_sekolah_lain(): void
    {
        // BERUBAH sejak permintaan user 2026-09-17 (lanjutan Part 32
        // ketujuh): karena penerimaan_dana_bos SEKARANG masuk
        // FIELD_KOMPUTASI_RINCIAN, Index::updated() menolaknya lewat
        // pengecekan read-only SEBELUM sempat mengecek
        // bolehEdit()/abort_unless(403) - jadi percobaan ini TIDAK LAGI
        // menghasilkan 403 Forbidden (kode itu jadi TIDAK PERNAH
        // tereksekusi lagi untuk field manapun, lihat catatan
        // Index::updated()), TAPI hasil akhirnya SAMA: tidak ada apapun
        // yang tersimpan untuk sekolah lain itu.
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolahLain->id}.penerimaan_dana_bos", '1000000')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('laporan_realisasi_bosp', [
            'profil_sekolah_id' => $sekolahLain->id,
        ]);
    }

    public function test_superadmin_juga_tidak_bisa_mengisi_manapun_karena_seluruh_kolom_sudah_read_only(): void
    {
        // BERUBAH sejak permintaan user 2026-09-17 (lanjutan Part 32
        // ketujuh): SEBELUMNYA superadmin bisa mengisi kolom 8-10 manual
        // untuk sekolah manapun - SEKARANG kolom itu sudah read-only
        // untuk SIAPAPUN (termasuk superadmin), karena rumusnya sudah
        // ditentukan lengkap (lihat FIELD_KOMPUTASI_RINCIAN).
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.penerimaan_dana_bos", '5000000')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('laporan_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_input_apapun_ke_kolom_read_only_diabaikan_tanpa_error(): void
    {
        // BERUBAH sejak permintaan user 2026-09-17 (lanjutan Part 32
        // ketujuh): SEBELUMNYA kolom 8-10 memvalidasi input (harus angka)
        // - SEKARANG kolom-kolom itu sudah read-only, sehingga
        // Index::updated() menolaknya SEBELUM validasi apapun dijalankan
        // (tidak ada error, bukan lagi "harus berupa angka"). Test ini
        // memastikan input non-angka ke kotak read-only tidak membuat
        // error maupun crash - murni diabaikan.
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $this->bukaGerbangVerval($sekolah, now()->year);

        $component = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set("baris.{$sekolah->id}.penerimaan_dana_bos", 'bukan angka')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('laporan_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // Kotak tetap menampilkan nilai read-only yang benar (0, karena
        // belum ada data DanaBospTahap), BUKAN teks "bukan angka" yang
        // dicoba di-set (percobaan set() ditolak sebelum sempat mengubah
        // $this->baris sama sekali).
        $this->assertSame('0', $component->get("baris.{$sekolah->id}.penerimaan_dana_bos"));
    }

    public function test_pindah_tab_hanya_menerima_lima_nilai_valid(): void
    {
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->assertSet('tabAktif', 'tw1')
            ->call('pindahTab', 'tw3')
            ->assertSet('tabAktif', 'tw3')
            ->call('pindahTab', 'rekap')
            ->assertSet('tabAktif', 'rekap')
            ->call('pindahTab', 'tab-tidak-dikenal')
            ->assertSet('tabAktif', 'rekap');
    }

    public function test_penerimaan_dana_bos_kolom_9_terisolasi_per_triwulan_tw1_tahap1_tw3_tahap2_tw2_tw4_selalu_nol(): void
    {
        // BERUBAH sejak permintaan user 2026-09-17 (lanjutan Part 32
        // ketujuh): penerimaan_dana_bos SUDAH read-only, sehingga
        // "isolasi per triwulan" SEKARANG dibuktikan lewat SUMBER
        // otomatisnya (DanaBospTahap `penerimaan_tahap_1` untuk TW1,
        // `penerimaan_tahap_2` untuk TW3), BUKAN lagi lewat input manual
        // per triwulan seperti sebelumnya. TW2 & TW4 SELALU 0 (tidak ada
        // penerimaan Dana BOSP pada triwulan itu, jawaban user verbatim).
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'penerimaan_tahap_1' => 1000000,
            'penerimaan_tahap_2' => 2000000,
        ]);

        $this->bukaGerbangVerval($sekolah, now()->year);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        // TW1 (default tab) - dari penerimaan_tahap_1.
        $this->assertSame('1000000', $component->get("baris.{$sekolah->id}.penerimaan_dana_bos"));

        // TW2 - SELALU 0.
        $component->call('pindahTab', 'tw2');
        $this->assertSame('0', $component->get("baris.{$sekolah->id}.penerimaan_dana_bos"));

        // TW3 - dari penerimaan_tahap_2 (BUKAN penerimaan_tahap_1).
        $component->call('pindahTab', 'tw3');
        $this->assertSame('2000000', $component->get("baris.{$sekolah->id}.penerimaan_dana_bos"));

        // TW4 - SELALU 0.
        $component->call('pindahTab', 'tw4');
        $this->assertSame('0', $component->get("baris.{$sekolah->id}.penerimaan_dana_bos"));
    }

    public function test_data_terisolasi_per_tahun(): void
    {
        // BERUBAH sejak permintaan user 2026-09-17 (lanjutan Part 32
        // ketujuh): penerimaan_dana_bos SUDAH read-only (sumbernya
        // App\Models\DanaBospTahap, yang JUGA punya kolom `tahun`) -
        // isolasi per tahun SEKARANG dibuktikan lewat baris DanaBospTahap
        // TAHUN LAIN, BUKAN lagi lewat baris laporan_realisasi_bosp
        // manual seperti sebelumnya.
        $sekolah = ProfilSekolah::factory()->create();
        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year - 1,
            'penerimaan_tahap_1' => 999,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        // Tahun berjalan (default) - belum ada DanaBospTahap tahun ini.
        $this->assertSame('0', $component->get("baris.{$sekolah->id}.penerimaan_dana_bos"));

        $component->set('tahun', now()->year - 1);
        $this->assertSame('999', $component->get("baris.{$sekolah->id}.penerimaan_dana_bos"));
    }

    public function test_admin_bosp_hanya_melihat_sekolahnya_sendiri(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        $this->bukaGerbangVerval($sekolahSaya, now()->year);

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

    public function test_tab_rekap_menampilkan_rincian_4_tw_dan_baris_jumlah_per_sekolah(): void
    {
        // Kolom 8-10 (Saldo Awal, Penerimaan Dana BOS, Total Penerimaan,
        // sejak lanjutan Part 32 ketujuh), kolom 25 (sisa_dana_bos),
        // kolom 26/27 (saldo_rekening_kas_bank/saldo_kas_tunai, sejak
        // lanjutan Part 32 keenam), & kolom 28/29 (verifikasi) sekarang
        // SEMUA HASIL RUMUS/otomatis real-time (lihat catatan
        // FIELD_KOMPUTASI_RINCIAN & FIELD_RUMUS di Model) - TIDAK BISA
        // lagi diisi langsung lewat factory pada kolom laporan_realisasi_bosp
        // seperti sebelumnya (kolom itu semua sudah vestigial, TIDAK
        // PERNAH dibaca lagi - nilainya sekarang WAJIB berasal dari
        // App\Models\DanaBospTahap & menu rincian lain). Test ini HANYA
        // memakai DanaBospTahap (1 baris per sekolah PER TAHUN) sebagai
        // satu-satunya sumber data.
        $sekolah = ProfilSekolah::factory()->create();

        // Kolom 8 TW1 = 0, kolom 9 TW1 (penerimaan_tahap_1) = 1jt, kolom 9
        // TW3 (penerimaan_tahap_2) = 1jt (TW2/4 selalu 0) -> rantai kolom
        // 8/10/25: TW1 saldo_awal=0,total=1jt,sisa=1jt; TW2
        // saldo_awal=1jt(dari sisa TW1),total=1jt,sisa=1jt; TW3
        // saldo_awal=1jt(dari sisa TW2),penerimaan=1jt,total=2jt,sisa=2jt;
        // TW4 saldo_awal=2jt(dari sisa TW3),total=2jt,sisa=2jt.
        $danaBospTahap = [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'saldo_bosp_tw4_tahun_sebelumnya' => 0,
            'penerimaan_tahap_1' => 1000000,
            'penerimaan_tahap_2' => 1000000,
        ];
        foreach ([1, 2, 3, 4] as $tw) {
            // Saldo Kas Bank/Tunai FLAT 1jt+500rb=1,5jt per TW supaya
            // JUMLAH 4 TW (6jt) SAMA DENGAN JUMLAH Sisa Dana BOS 4 TW
            // (1jt+1jt+2jt+2jt=6jt) - walau per TW-nya sendiri BEDA
            // (membuktikan agregasi "Jumlah" dihitung dari SUM, bukan
            // dibandingkan per TW dulu baru dijumlah).
            $danaBospTahap["saldo_kas_bank_tw{$tw}"] = 1000000;
            $danaBospTahap["saldo_kas_tunai_tw{$tw}"] = 500000;
        }
        DanaBospTahap::factory()->create($danaBospTahap);

        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('pindahTab', 'rekap');

        $rekap = $component->viewData('rekapPerSekolah');
        $this->assertCount(1, $rekap);

        $blok = $rekap[0];
        $this->assertSame($sekolah->id, $blok['sekolah']->id);

        // Rincian 4 TW per sekolah (jawaban AskUserQuestion "Rincian 4 TW +
        // Jumlah per sekolah"). Kolom 9 (penerimaan_dana_bos): TW1 & TW3
        // terisi, TW2 & TW4 SELALU 0.
        $this->assertSame(1000000, $blok['perTriwulan'][1]['penerimaan_dana_bos']);
        $this->assertSame(0, $blok['perTriwulan'][2]['penerimaan_dana_bos']);
        $this->assertSame(1000000, $blok['perTriwulan'][3]['penerimaan_dana_bos']);
        $this->assertSame(0, $blok['perTriwulan'][4]['penerimaan_dana_bos']);
        $this->assertSame(1000000, $blok['perTriwulan'][1]['sisa_dana_bos']);
        $this->assertSame(2000000, $blok['perTriwulan'][4]['sisa_dana_bos']);

        // Baris "Jumlah" = sum 4 TW: 1jt+0+1jt+0 = 2jt.
        $this->assertSame(2000000, $blok['jumlah']['penerimaan_dana_bos']);
        // Sisa Dana BOS jumlah = 1jt+1jt+2jt+2jt = 6jt, SAMA DENGAN
        // Verifikasi Jumlah (dari DanaBospTahap) = (1jt+500rb) x 4 = 6jt
        // -> SAMA (walau TIAP TW-nya sendiri TIDAK SAMA - lihat komentar
        // di atas).
        $this->assertSame(6000000, $blok['jumlah']['sisa_dana_bos']);
        $this->assertSame(6000000, $blok['jumlah']['verifikasi_jumlah']);
        $this->assertSame(LaporanRealisasiBosp::VERIFIKASI_SAMA, $blok['jumlah']['verifikasi_saldo']);
    }

    public function test_verifikasi_saldo_baris_jumlah_footer_tw_disembunyikan_kalau_ada_sekolah_yang_belum_lengkap(): void
    {
        // Jawaban AskUserQuestion 2026-09-17 (poin 1, pertanyaan ke-2,
        // "Ya, ikut disembunyikan kalau ada TW yang datanya belum
        // lengkap (Recommended)"): baris "JUMLAH" (footer tab TW1-4)
        // harus IKUT menyembunyikan kolom 29 kalau ADA SATU SAJA sekolah
        // yang kolom 26/27-nya belum tersimpan - WALAUPUN sekolah lain
        // sudah lengkap & SAMA per baris masing-masing.
        $sekolahLengkap = ProfilSekolah::factory()->create();
        $sekolahBelumLengkap = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        // Sekolah lengkap: kolom 26+27 = 1jt, sisa_dana_bos = 1jt -> SAMA
        // per baris sendiri.
        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolahLengkap->id,
            'tahun' => now()->year,
            'saldo_bosp_tw4_tahun_sebelumnya' => 1000000,
            'penerimaan_tahap_1' => 0,
            'saldo_kas_bank_tw1' => 700000,
            'saldo_kas_tunai_tw1' => 300000,
        ]);

        // Sekolah belum lengkap: TIDAK ADA baris DanaBospTahap sama
        // sekali -> kolom 26/27 masih null (belum tersimpan).
        // ($sekolahBelumLengkap sengaja tidak diberi DanaBospTahap.)

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        // Baris sekolah lengkap sendiri TETAP menampilkan "SAMA" (aturan
        // hide hanya berlaku per baris ybs, tidak memengaruhi baris LAIN
        // yang datanya sendiri sudah lengkap).
        $this->assertSame(LaporanRealisasiBosp::VERIFIKASI_SAMA, $component->get("baris.{$sekolahLengkap->id}.verifikasi_saldo"));
        $this->assertSame('', $component->get("baris.{$sekolahBelumLengkap->id}.verifikasi_saldo"));

        // TAPI baris "JUMLAH" (footer, gabungan SEMUA sekolah yang
        // tampil) HARUS disembunyikan (null/'-') karena ADA SATU sekolah
        // (sekolahBelumLengkap) yang belum lengkap.
        $this->assertSame('', $component->viewData('totalBaris')['verifikasi_saldo'] ?? '');
    }

    public function test_rekap_jumlah_tahun_anggaran_menjumlah_baris_jumlah_seluruh_sekolah(): void
    {
        // Permintaan user 2026-09-17 (lanjutan Part 32 ketujuh, poin 2):
        // baris "JUMLAH TAHUN ANGGARAN" pada tab Rekapitulasi = SUM baris
        // "Jumlah" SETIAP sekolah yang tampil.
        $sekolahA = ProfilSekolah::factory()->create();
        $sekolahB = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        foreach ([$sekolahA, $sekolahB] as $sekolah) {
            DanaBospTahap::factory()->create([
                'profil_sekolah_id' => $sekolah->id,
                'tahun' => now()->year,
                'saldo_bosp_tw4_tahun_sebelumnya' => 1000000,
                'penerimaan_tahap_1' => 0,
                'penerimaan_tahap_2' => 0,
                'saldo_kas_bank_tw1' => 700000,
                'saldo_kas_tunai_tw1' => 300000,
                'saldo_kas_bank_tw2' => 0,
                'saldo_kas_tunai_tw2' => 0,
                'saldo_kas_bank_tw3' => 0,
                'saldo_kas_tunai_tw3' => 0,
                'saldo_kas_bank_tw4' => 0,
                'saldo_kas_tunai_tw4' => 0,
            ]);
        }

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('pindahTab', 'rekap');

        $rekap = $component->viewData('rekapPerSekolah');
        $this->assertCount(2, $rekap);

        // Tiap sekolah: saldo_awal TW1=1jt, sisa TW1=1jt (tidak ada
        // realisasi), sisa TW2/3/4 TETAP 1jt (rantai saldo_awal berulang,
        // penerimaan & realisasi 0 terus) -> jumlah sisa_dana_bos per
        // sekolah = 1jt x 4 = 4jt.
        $jumlahPerSekolah = collect($rekap)->sum(fn ($blok) => $blok['jumlah']['sisa_dana_bos']);
        $this->assertSame(8000000, $jumlahPerSekolah); // 4jt x 2 sekolah

        $totalTahunAnggaran = $component->viewData('totalTahunAnggaran');
        $this->assertSame(8000000, $totalTahunAnggaran['sisa_dana_bos']);

        // Verifikasi Jumlah tahun = (700rb+300rb) TW1 x 2 sekolah = 2jt
        // (TW2-4 kas bank/tunai sengaja 0 di test ini).
        $this->assertSame(2000000, $totalTahunAnggaran['verifikasi_jumlah']);
    }

    public function test_rekap_jumlah_tahun_anggaran_verifikasi_saldo_disembunyikan_kalau_ada_sekolah_belum_lengkap(): void
    {
        // Sama seperti test_verifikasi_saldo_baris_jumlah_footer_tw_disembunyikan...
        // di atas, tapi untuk baris "JUMLAH TAHUN ANGGARAN" pada tab
        // Rekapitulasi (poin 2) - kalau ADA SATU SAJA sekolah yang datanya
        // belum lengkap DI TRIWULAN MANAPUN, verifikasi_saldo baris total
        // 1 tahun anggaran JUGA disembunyikan.
        $sekolahLengkap = ProfilSekolah::factory()->create();
        $sekolahBelumLengkap = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolahLengkap->id,
            'tahun' => now()->year,
            'saldo_bosp_tw4_tahun_sebelumnya' => 0,
            'penerimaan_tahap_1' => 0,
            'penerimaan_tahap_2' => 0,
            'saldo_kas_bank_tw1' => 0,
            'saldo_kas_tunai_tw1' => 0,
            'saldo_kas_bank_tw2' => 0,
            'saldo_kas_tunai_tw2' => 0,
            'saldo_kas_bank_tw3' => 0,
            'saldo_kas_tunai_tw3' => 0,
            'saldo_kas_bank_tw4' => 0,
            'saldo_kas_tunai_tw4' => 0,
        ]);
        // $sekolahBelumLengkap sengaja TIDAK diberi DanaBospTahap sama
        // sekali -> kolom 26/27 keempat triwulannya null.

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('pindahTab', 'rekap');

        $totalTahunAnggaran = $component->viewData('totalTahunAnggaran');
        $this->assertSame('', $totalTahunAnggaran['verifikasi_saldo'] ?? '');
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

    public function test_link_laporan_realisasi_bosp_muncul_di_sidebar_sesudah_pajak_bosp_reguler(): void
    {
        $sekolah = $this->buatSekolahLengkap();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $response = $this->actingAs($adminBosp)->get('/pendataan-bosp');

        $response->assertOk()->assertSee('Laporan Realisasi BOSP (Form BPK)');

        $html = $response->getContent();
        $posisiPajak = strpos($html, 'Pajak BOSP Reguler');
        $posisiLaporanRealisasi = strpos($html, 'Laporan Realisasi BOSP');

        $this->assertNotFalse($posisiPajak);
        $this->assertNotFalse($posisiLaporanRealisasi);
        $this->assertGreaterThan($posisiPajak, $posisiLaporanRealisasi);
    }

    public function test_subrayon_dan_kode_upb_bisa_disimpan_di_profil_sekolah(): void
    {
        // Field baru (permintaan user 2026-09-17, jawaban AskUserQuestion
        // "Tambahkan ke Profil Sekolah") - dibutuhkan sebagai kolom 2 & 6
        // pada menu ini.
        $sekolah = ProfilSekolah::factory()->create([
            'kode_upb' => 'UPB-001',
            'subrayon' => 'Subrayon 1',
        ]);

        $this->assertDatabaseHas('profil_sekolah', [
            'id' => $sekolah->id,
            'kode_upb' => 'UPB-001',
            'subrayon' => 'Subrayon 1',
        ]);
    }

    /**
     * Membuka gerbang Verval (round keenam, poin 3) supaya Admin BOSP
     * bisa melihat halaman Laporan Realisasi BOSP (Form BPK) yang biasa
     * (bukan halaman validasi) - dipakai test-test LAMA di file ini yang
     * menguji tab TW1-4/rekap untuk Admin BOSP, ditulis SEBELUM fitur
     * Verval ada (lihat App\Models\VervalRealisasiBosp::semuaTriwulanSesuai()).
     */
    private function bukaGerbangVerval(ProfilSekolah $sekolah, int $tahun): void
    {
        foreach ([1, 2, 3, 4] as $tw) {
            VervalRealisasiBosp::create([
                'profil_sekolah_id' => $sekolah->id,
                'tahun' => $tahun,
                'triwulan' => $tw,
                'status' => VervalRealisasiBosp::STATUS_SESUAI,
                'diverval_oleh' => null,
                'diverval_pada' => now(),
            ]);
        }
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

    public function test_halaman_laporan_realisasi_bosp_bisa_diakses(): void
    {
        $sekolah = $this->buatSekolahLengkap();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $this->actingAs($adminBosp)
            ->get(route('pendataan-bosp.laporan-realisasi-bosp'))
            ->assertOk()
            ->assertSeeLivewire(Index::class);
    }

    public function test_admin_ops_tidak_bisa_mengakses_menu_ini(): void
    {
        $sekolah = $this->buatSekolahLengkap();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanOps::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $this->actingAs($adminOps)
            ->get(route('pendataan-bosp.laporan-realisasi-bosp'))
            ->assertForbidden();
    }

    /**
     * Popup notifikasi Tarik Tunai BOSP belum lengkap (permintaan user
     * 2026-09-23, item #8) - lihat
     * Livewire\PendataanBosp\LaporanRealisasiBosp\Index::tarikTunaiBospBelumLengkap().
     */
    public function test_popup_tarik_tunai_muncul_untuk_admin_bosp_kalau_belum_ada_data_dana_bosp_tahap_sama_sekali(): void
    {
        $sekolah = $this->buatSekolahLengkap();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        // TIDAK ADA DanaBospTahap::factory() sama sekali untuk sekolah ini.
        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->assertDispatched('tarik-tunai-bosp-belum-lengkap');
    }

    public function test_popup_tarik_tunai_muncul_untuk_admin_bosp_kalau_salah_satu_triwulan_belum_diisi(): void
    {
        $sekolah = $this->buatSekolahLengkap();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        // TW1-3 lengkap (nilai default factory), TW4 saldo_kas_tunai_tw4
        // sengaja dikosongkan (belum diisi Admin BOSP).
        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'saldo_kas_tunai_tw4' => null,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->assertDispatched('tarik-tunai-bosp-belum-lengkap');
    }

    public function test_popup_tarik_tunai_tidak_muncul_untuk_admin_bosp_kalau_keempat_triwulan_sudah_lengkap(): void
    {
        $sekolah = $this->buatSekolahLengkap();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        // Nilai default factory sudah mengisi Saldo Kas Bank/Tunai keempat
        // triwulan (tidak null) - lihat DanaBospTahapFactory.
        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->assertNotDispatched('tarik-tunai-bosp-belum-lengkap');
    }

    public function test_popup_tarik_tunai_tidak_pernah_muncul_untuk_superadmin(): void
    {
        // Superadmin tidak punya "sekolah sendiri" & popup ini eksplisit
        // ditujukan ke Admin BOSP - HARUS tidak muncul sama sekali untuk
        // Superadmin, walau ADA sekolah lain yang datanya belum lengkap.
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        // Sekolah ini SENGAJA tidak punya DanaBospTahap sama sekali.
        $this->assertDatabaseMissing('dana_bosp_tahap', ['profil_sekolah_id' => $sekolah->id]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->assertNotDispatched('tarik-tunai-bosp-belum-lengkap');
    }

    public function test_popup_tarik_tunai_mengecek_tahun_yang_sedang_aktif_saat_mount(): void
    {
        // Popup mengecek DanaBospTahap tahun BERJALAN (now()->year, lihat
        // Index::mount()) - data lengkap di TAHUN LAIN tidak mencegah
        // popup muncul untuk tahun berjalan yang belum lengkap.
        $sekolah = $this->buatSekolahLengkap();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        // Data LENGKAP tapi untuk tahun SEBELUMNYA, bukan tahun berjalan.
        DanaBospTahap::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year - 1,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->assertDispatched('tarik-tunai-bosp-belum-lengkap');
    }

    // ===== Round keenam 2026-09-23 poin 3: halaman validasi (Verval)
    // "VALIDASI HASIL ENTRY DATA BOSP" muncul SEBAGAI GANTI halaman
    // Laporan Realisasi BOSP (Form BPK) biasa untuk Admin BOSP - HANYA
    // Admin BOSP yang melihat gerbang ini (Superadmin langsung ke laporan
    // biasa, tidak berubah dari round keenam).
    //
    // DIREVISI 2026-09-23 (round kedelapan, laporan bug user atas Round
    // 7): SEBELUMNYA gerbang tampil sampai KEEMPAT triwulan "Sesuai" -
    // SEKARANG gerbang HANYA tampil selama BELUM ADA SATU PUN triwulan
    // yang pernah diverval (Sesuai MAUPUN Belum Sesuai). Begitu triwulan
    // MANAPUN diklik, halaman laporan biasa langsung aktif - tabel verval
    // (partial _tabel-validasi.blade.php) tetap tertanam di halaman itu
    // (panel "Validasi Hasil Entry Data BOSP", lihat $panelValidasiSendiri
    // di Index::render()) supaya triwulan yang tersisa bisa terus
    // diverval dari sana. Text "Laporan Realisasi TW 1" (label tab) dipakai
    // sebagai penanda unik halaman laporan biasa - BUKAN teks judul
    // "Validasi Hasil Entry Data BOSP" saja, karena judul itu SEKARANG
    // muncul juga di panel tertanam pada halaman laporan biasa. =====

    public function test_admin_bosp_melihat_halaman_validasi_sebelum_ada_triwulan_yang_diverval(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->assertSee('Validasi Hasil Entry Data BOSP')
            ->assertDontSee('Laporan Realisasi TW 1');
    }

    public function test_superadmin_tidak_pernah_melihat_halaman_validasi(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->assertDontSee('Validasi Hasil Entry Data BOSP');
    }

    public function test_halaman_laporan_aktif_kembali_setelah_keempat_tw_berstatus_sesuai(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        foreach ([1, 2, 3, 4] as $tw) {
            VervalRealisasiBosp::create([
                'profil_sekolah_id' => $sekolah->id,
                'tahun' => $tahun,
                'triwulan' => $tw,
                'status' => VervalRealisasiBosp::STATUS_SESUAI,
                'diverval_oleh' => $adminBosp->id,
                'diverval_pada' => now(),
            ]);
        }

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->assertSee('Laporan Realisasi TW 1');
    }

    public function test_baru_1_dari_4_tw_diverval_halaman_laporan_sudah_langsung_terbuka(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => $adminBosp->id, 'diverval_pada' => now(),
        ]);

        // TW2 sudah mulai diisi (belum diverval) - lihat
        // VervalRealisasiBosp::triwulanAktifValidasi() (round kesembilan) -
        // supaya panel "Validasi Hasil Entry Data BOSP" punya sesuatu
        // untuk ditampilkan (sejak round kesembilan panel TIDAK LAGI
        // selalu tertanam permanen, hanya tampil kalau ADA triwulan yang
        // sudah ada datanya tapi belum diverval sama sekali).
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 2,
        ]);

        // Halaman laporan biasa (BUKAN gerbang) HARUS langsung terbuka
        // dengan BARU 1 dari 4 triwulan diverval - jawaban user: "ketika
        // klik verval Sesuai/belum Sesuai halaman langsung otomatis
        // membuka menu Laporan Realisasi BOSP (Form BPK)". Panel
        // "Validasi Hasil Entry Data BOSP" tertanam (judulnya masih
        // kelihatan) karena TW2 sudah ada datanya & belum diverval.
        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->assertSee('Laporan Realisasi TW 1')
            ->assertSee('Validasi Hasil Entry Data BOSP');
    }

    public function test_baru_1_tw_belum_sesuai_diverval_halaman_laporan_juga_langsung_terbuka(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        // "Belum Sesuai" juga menghitung sebagai "sudah diverval" untuk
        // gerbang (BEDA dari kuncian data yang HANYA berlaku untuk
        // "Sesuai") - jawaban user eksplisit menyebut Sesuai MAUPUN Belum
        // Sesuai sama-sama langsung membuka halaman laporan.
        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_BELUM_SESUAI,
            'diverval_oleh' => $adminBosp->id, 'diverval_pada' => now(),
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->assertSee('Laporan Realisasi TW 1');
    }

    public function test_gerbang_muncul_lagi_setelah_superadmin_reset_satu_satunya_triwulan_yang_diverval(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => $adminBosp->id, 'diverval_pada' => now(),
        ]);

        // Sebelum reset: gerbang sudah terbuka (report page) karena TW1
        // sudah diverval.
        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('tahun', $tahun)
            ->assertSee('Laporan Realisasi TW 1');

        // Superadmin reset TW1 - itu satu-satunya triwulan yang pernah
        // diverval, jadi begitu dihapus, adaTriwulanSudahDiverval() kembali
        // FALSE & gerbang HARUS muncul lagi untuk Admin BOSP.
        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('tahun', $tahun)
            ->call('resetVerval', $sekolah->id, 1);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('tahun', $tahun)
            ->assertSee('Validasi Hasil Entry Data BOSP')
            ->assertDontSee('Laporan Realisasi TW 1');
    }

    public function test_panel_validasi_tertanam_tampil_di_halaman_laporan_dengan_tombol_untuk_tw_yang_belum_diverval(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => $adminBosp->id, 'diverval_pada' => now(),
        ]);

        // TW2 sudah mulai diisi (belum diverval) - jadi "triwulan aktif"
        // (VervalRealisasiBosp::triwulanAktifValidasi(), round kesembilan)
        // = TW2, panel harus tampil dengan tombolnya.
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 2,
        ]);

        // TW1 terkunci (badge "Sesuai (Terkunci)"), TW2 sudah ada datanya
        // tapi belum diverval - tombol "Sesuai"/"Belum Sesuai" HARUS ada
        // di panel supaya Admin BOSP bisa lanjut memverval tanpa balik ke
        // gerbang.
        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('tahun', $tahun)
            ->assertSee('Laporan Realisasi TW 1')
            ->assertSee('Sesuai (Terkunci)')
            ->assertSeeHtml('wire:click="setVerval(2, \'belum_sesuai\')"');
    }

    public function test_superadmin_tidak_melihat_panel_validasi_tertanam_di_halaman_laporan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        // Superadmin langsung ke laporan biasa (0 verval - tidak ada
        // gerbang untuk Superadmin) & TIDAK melihat panel "Validasi Hasil
        // Entry Data BOSP" tertanam (itu murni untuk Admin BOSP sendiri -
        // panel Superadmin sendiri adalah "Status Verval & Reset Kuncian").
        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->assertSee('Laporan Realisasi TW 1')
            ->assertDontSee('Validasi Hasil Entry Data BOSP');
    }

    public function test_set_verval_sesuai_menyimpan_status_dan_mengunci_triwulan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('setVerval', 1, VervalRealisasiBosp::STATUS_SESUAI);

        $this->assertDatabaseHas('verval_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => $tahun,
            'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => $adminBosp->id,
        ]);
        $this->assertTrue(VervalRealisasiBosp::triwulanSudahSesuai($sekolah->id, $tahun, 1));
    }

    public function test_set_verval_belum_sesuai_tidak_mengunci_dan_bisa_diganti_ke_sesuai(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        $component = Livewire::actingAs($adminBosp)->test(Index::class);
        $component->call('setVerval', 2, VervalRealisasiBosp::STATUS_BELUM_SESUAI);

        $this->assertFalse(VervalRealisasiBosp::triwulanSudahSesuai($sekolah->id, $tahun, 2));
        $this->assertDatabaseHas('verval_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 2,
            'status' => VervalRealisasiBosp::STATUS_BELUM_SESUAI,
        ]);

        // Boleh diganti bolak-balik selama belum pernah "sesuai".
        $component->call('setVerval', 2, VervalRealisasiBosp::STATUS_SESUAI);
        $this->assertTrue(VervalRealisasiBosp::triwulanSudahSesuai($sekolah->id, $tahun, 2));
    }

    public function test_set_verval_menolak_mengubah_triwulan_yang_sudah_sesuai(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 3,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => $adminBosp->id, 'diverval_pada' => now(),
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('setVerval', 3, VervalRealisasiBosp::STATUS_BELUM_SESUAI);

        // Percobaan mengubah triwulan yang sudah "sesuai" HARUS diabaikan -
        // status tetap "sesuai", tidak berubah jadi "belum_sesuai".
        $this->assertDatabaseHas('verval_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 3,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
        ]);
    }

    public function test_set_verval_ditolak_untuk_superadmin(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $tahun = now()->year;

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('setVerval', 1, VervalRealisasiBosp::STATUS_SESUAI);

        $this->assertDatabaseMissing('verval_realisasi_bosp', ['tahun' => $tahun]);
    }

    // ===== Round ketujuh (2026-09-23): (a) setVerval() harus mem-dispatch
    // event browser "verval-diperbarui" supaya validasi.blade.php melakukan
    // RELOAD PENUH halaman (jawaban AskUserQuestion "Tiap klik Sesuai/Belum
    // Sesuai, reload halaman penuh"); (b) Superadmin bisa me-reset PENUH
    // kuncian 1 triwulan lewat resetVerval() (jawaban AskUserQuestion
    // "Tombol Reset di halaman Laporan Realisasi BOSP" & "Reset penuh") -
    // Admin BOSP DITOLAK memanggilnya, & reset membuka KEMBALI ceklist
    // verval MAUPUN data isian triwulan itu di menu sumber sekaligus.

    public function test_set_verval_mendispatch_event_reload_penuh(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('setVerval', 1, VervalRealisasiBosp::STATUS_SESUAI)
            ->assertDispatched('verval-diperbarui');
    }

    public function test_reset_verval_oleh_superadmin_menghapus_kuncian_dan_membuka_ceklist_kembali(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => $adminBosp->id, 'diverval_pada' => now(),
        ]);
        $this->assertTrue(VervalRealisasiBosp::triwulanSudahSesuai($sekolah->id, $tahun, 1));

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('tahun', $tahun)
            ->call('resetVerval', $sekolah->id, 1);

        $this->assertDatabaseMissing('verval_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
        ]);
        $this->assertFalse(VervalRealisasiBosp::triwulanSudahSesuai($sekolah->id, $tahun, 1));

        // Admin BOSP boleh memverval ulang dari awal (Sesuai/Belum Sesuai)
        // begitu Superadmin sudah mereset - ceklist BENAR-BENAR terbuka
        // kembali, bukan sekadar "belum_sesuai" tersisa.
        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('tahun', $tahun)
            ->call('setVerval', 1, VervalRealisasiBosp::STATUS_BELUM_SESUAI);
        $this->assertDatabaseHas('verval_realisasi_bosp', [
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_BELUM_SESUAI,
        ]);
    }

    public function test_reset_verval_ditolak_untuk_admin_bosp(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => $adminBosp->id, 'diverval_pada' => now(),
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('resetVerval', $sekolah->id, 1)
            ->assertForbidden();

        // Ditolak (403) - baris verval TETAP ada / tidak terhapus.
        $this->assertTrue(VervalRealisasiBosp::triwulanSudahSesuai($sekolah->id, $tahun, 1));
    }

    public function test_reset_verval_triwulan_yang_belum_terkunci_tidak_error(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $tahun = now()->year;

        // Tidak ada baris verval sama sekali untuk triwulan 1 - reset harus
        // tetap aman (tidak error), efeknya tidak ada apa-apa untuk dihapus.
        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('tahun', $tahun)
            ->call('resetVerval', $sekolah->id, 1)
            ->assertOk();

        $this->assertFalse(VervalRealisasiBosp::triwulanSudahSesuai($sekolah->id, $tahun, 1));
    }

    /**
     * Regresi bug laporan user 2026-09-24: kolom "Penerimaan Dana BOS" (&
     * kolom komputasi lain) tampil Rp 0 di tabel TW1-4 utk SUPERADMIN
     * padahal datanya ada di Dana BOSP Tahap & baris JUMLAH (total) sudah
     * benar - penyebabnya variabel Blade "$baris" pada loop panel "Status
     * Verval & Reset Kuncian (Superadmin)" (@forelse ($statusVervalSuperadmin
     * as $baris)) menimpa variabel "$baris" milik tabel utama (properti
     * publik $this->baris) karena Blade tidak menscope variabel loop -
     * HANYA muncul kalau panel itu py tampil (Superadmin & minimal 1 baris
     * verval ada), makanya Admin BOSP tidak pernah kena. Test ini
     * memastikan NILAI YANG DIRENDER DI HTML benar (bukan cuma properti
     * $this->baris-nya saja yang bisa saja benar padahal HTML salah).
     */
    public function test_superadmin_melihat_angka_penerimaan_dana_bos_yang_benar_walau_ada_panel_status_verval(): void
    {
        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SMPS UJI COBA REGRESI']);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $tahun = now()->year;

        // Data Dana BOSP Tahap supaya "Penerimaan Dana BOS" TW1 seharusnya
        // Rp 5.500.000 (penerimaan_tahap_1) - sama seperti kasus user.
        DanaBospTahap::create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => $tahun,
            'jumlah_siswa' => 10,
            'jumlah_dana_bosp_per_tahun' => 1_100_000,
            'total_penerimaan_setahun' => 11_000_000,
            'penerimaan_tahap_1' => 5_500_000,
            'penerimaan_tahap_2' => 5_500_000,
        ]);

        // Minimal 1 baris verval supaya panel "Status Verval & Reset
        // Kuncian (Superadmin)" BENAR-BENAR dirender (loop-nya harus
        // benar-benar jalan minimal 1 kali supaya bug ini muncul - kalau
        // $statusVervalSuperadmin kosong, @forelse jatuh ke @empty & tidak
        // pernah menimpa $baris).
        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_BELUM_SESUAI,
            'diverval_oleh' => $superadmin->id, 'diverval_pada' => now(),
        ]);

        $test = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('tahun', $tahun)
            ->set('tabAktif', 'tw1');

        // Properti komponen HARUS benar (rumusnya sendiri sudah teruji di
        // tempat lain) ...
        $this->assertSame('5500000', $test->get('baris')[$sekolah->id]['penerimaan_dana_bos'] ?? null);

        // ... dan yang PALING PENTING, HTML yang benar-benar dikirim ke
        // browser juga harus menampilkan angka yang sama untuk baris
        // sekolah ini secara spesifik (bukan cuma ada di baris JUMLAH).
        $html = $test->html();
        $posBaris = strpos($html, 'laporan-realisasi-bosp-baris-'.$sekolah->id);
        $this->assertNotFalse($posBaris, 'Baris tabel utk sekolah ini tidak ditemukan di HTML.');

        $potongan = substr($html, $posBaris, 3000);
        $this->assertStringContainsString('5.500.000', $potongan);
    }

    public function test_reset_verval_membuka_kembali_data_isian_di_menu_sumber(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => $adminBosp->id, 'diverval_pada' => now(),
        ]);

        // Sebelum reset: menu sumber (Penerimaan Honor PTK) menolak simpan()
        // triwulan 1 dengan 403, sesuai perilaku round keenam.
        Livewire::actingAs($adminBosp)
            ->test(\App\Livewire\PendataanBosp\PenerimaanHonorPtk\Index::class)
            ->set('tahun', $tahun)
            ->set('triwulan', 1)
            ->call('simpan')
            ->assertForbidden();

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('tahun', $tahun)
            ->call('resetVerval', $sekolah->id, 1);

        // Setelah reset: guard verval tidak lagi memblokir (gagal validasi
        // field kosong biasa, BUKAN 403) - pola SAMA PERSIS dengan
        // test_penerimaan_honor_ptk_tetap_bisa_diedit_pada_triwulan_yang_belum_diverval.
        Livewire::actingAs($adminBosp)
            ->test(\App\Livewire\PendataanBosp\PenerimaanHonorPtk\Index::class)
            ->set('tahun', $tahun)
            ->set('triwulan', 1)
            ->call('simpan')
            ->assertHasErrors();
    }

    public function test_verval_lain_sekolah_tidak_saling_mempengaruhi(): void
    {
        $sekolahA = ProfilSekolah::factory()->create();
        $sekolahB = ProfilSekolah::factory()->create();
        $adminBospA = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahA->id,
        ]);
        $tahun = now()->year;

        foreach ([1, 2, 3, 4] as $tw) {
            VervalRealisasiBosp::create([
                'profil_sekolah_id' => $sekolahA->id, 'tahun' => $tahun, 'triwulan' => $tw,
                'status' => VervalRealisasiBosp::STATUS_SESUAI,
                'diverval_oleh' => $adminBospA->id, 'diverval_pada' => now(),
            ]);
        }

        // Sekolah B belum di-verval sama sekali - harus TETAP false meski
        // sekolah A sudah lengkap ke-4 TW nya.
        $this->assertTrue(VervalRealisasiBosp::semuaTriwulanSesuai($sekolahA->id, $tahun));
        $this->assertFalse(VervalRealisasiBosp::semuaTriwulanSesuai($sekolahB->id, $tahun));
    }

    // ===== Round keenam poin 3 (lanjutan): triwulan yang sudah
    // "sesuai" mengunci BUKAN HANYA ceklistnya, TAPI JUGA data isian di
    // 8 menu sumber terkait (jawaban AskUserQuestion "Ceklist + data
    // isian triwulan itu") - diuji lewat trait
    // App\Livewire\Concerns\MenolakEditJikaTerkunciVerval yang dipakai
    // 8 komponen sumber (PenerimaanHonorPtk, LanggananDayaJasa,
    // BelanjaPemeliharaanBangunan, BelanjaPemeliharaanPc,
    // BiayaPendaftaranLomba, BelanjaHonorKegiatan,
    // RincianBelanjaBarangHabisPakai, RincianBelanjaModal). Representasi
    // 1 contoh di bawah (PenerimaanHonorPtk) sudah cukup menguji
    // trait-nya sendiri secara langsung - tiap komponen memakai method
    // guard yang SAMA PERSIS (abortJikaTerkunciVerval()).

    public function test_verval_realisasi_bosp_model_mendeteksi_triwulan_terkunci_dengan_benar(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $tahun = now()->year;

        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => null, 'diverval_pada' => now(),
        ]);

        $this->assertTrue(VervalRealisasiBosp::triwulanSudahSesuai($sekolah->id, $tahun, 1));
        $this->assertFalse(VervalRealisasiBosp::triwulanSudahSesuai($sekolah->id, $tahun, 2));
    }

    public function test_penerimaan_honor_ptk_menolak_edit_pada_triwulan_yang_sudah_diverval_sesuai(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => $adminBosp->id, 'diverval_pada' => now(),
        ]);

        Livewire::actingAs($adminBosp)
            ->test(\App\Livewire\PendataanBosp\PenerimaanHonorPtk\Index::class)
            ->set('tahun', $tahun)
            ->set('triwulan', 1)
            ->call('simpan')
            ->assertForbidden();
    }

    public function test_penerimaan_honor_ptk_tetap_bisa_diedit_pada_triwulan_yang_belum_diverval(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        // Triwulan 1 terkunci, triwulan 2 TIDAK - percobaan simpan()
        // gagal validasi (field kosong) tapi TIDAK abort 403, membuktikan
        // guard verval tidak ikut memblokir triwulan yang belum diverval.
        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => $adminBosp->id, 'diverval_pada' => now(),
        ]);

        Livewire::actingAs($adminBosp)
            ->test(\App\Livewire\PendataanBosp\PenerimaanHonorPtk\Index::class)
            ->set('tahun', $tahun)
            ->set('triwulan', 2)
            ->call('simpan')
            ->assertHasErrors();
    }

    // ================================================================
    // ROUND KESEMBILAN (permintaan user 2026-09-23, poin 1 & 2)
    // ================================================================

    public function test_superadmin_tidak_terkunci_bisa_edit_triwulan_yang_sudah_sesuai(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $tahun = now()->year;

        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => null, 'diverval_pada' => now(),
        ]);

        // DIREVISI 2026-09-23 (round kesembilan, poin 2): "kecuali di
        // login superadmin semua triwulan tetap terbuka" - Superadmin
        // SEKARANG dikecualikan dari kuncian verval (SEBELUMNYA, round
        // keenam, ikut terkunci seperti Admin BOSP). Kalau abort 403
        // masih terjadi, call() di bawah akan melempar exception & test
        // ini gagal - assertHasNoErrors() + assertDatabaseHas() membuktikan
        // simpan() benar-benar berjalan sampai selesai tanpa ditolak.
        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\PendataanBosp\PenerimaanHonorPtk\Index::class)
            ->set('tahun', $tahun)
            ->set('triwulan', 1)
            ->set('profil_sekolah_id', $sekolah->id)
            ->set('nama_penerima', 'Contoh Guru')
            ->set('volume', 1)
            ->set('tarif_harga', 1000)
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('penerimaan_honor_ptk', [
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1, 'nama_penerima' => 'Contoh Guru',
        ]);
    }

    public function test_pajak_bosp_reguler_menolak_edit_pada_bulan_yang_triwulannya_sudah_diverval_sesuai(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        // TW1 = bulan 1-3 (App\Models\PajakBospReguler::TRIWULAN_BULAN).
        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => $adminBosp->id, 'diverval_pada' => now(),
        ]);

        Livewire::actingAs($adminBosp)
            ->test(\App\Livewire\PendataanBosp\PajakBospReguler\Index::class)
            ->set('tahun', $tahun)
            ->set('baris.2.ppn_debit', '250000')
            ->assertForbidden();
    }

    public function test_pajak_bosp_reguler_tetap_bisa_diedit_pada_bulan_yang_triwulannya_belum_diverval(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => $adminBosp->id, 'diverval_pada' => now(),
        ]);

        // Bulan 5 = TW2, belum diverval - harus tetap bisa diedit.
        Livewire::actingAs($adminBosp)
            ->test(\App\Livewire\PendataanBosp\PajakBospReguler\Index::class)
            ->set('tahun', $tahun)
            ->set('baris.5.ppn_debit', '250000')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pajak_bosp_reguler', [
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'bulan' => 5, 'ppn_debit' => 250000,
        ]);
    }

    public function test_formulir_bos_k7_menolak_edit_pada_bulan_yang_triwulannya_sudah_diverval_sesuai(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        // TW1 = bulan 1-3.
        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_SESUAI,
            'diverval_oleh' => $adminBosp->id, 'diverval_pada' => now(),
        ]);

        Livewire::actingAs($adminBosp)
            ->test(\App\Livewire\PendataanBosp\FormulirBosK7\Index::class)
            ->set('tahun', $tahun)
            ->set('bulan', 2)
            ->set('baris.data.saldo_rekening_bank', '1000000')
            ->assertForbidden();
    }

    public function test_ada_data_untuk_triwulan_mendeteksi_data_di_berbagai_model_sumber(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $tahun = now()->year;

        // Belum ada data sama sekali untuk TW1.
        $this->assertFalse(VervalRealisasiBosp::adaDataUntukTriwulan($sekolah->id, $tahun, 1));

        // Data di salah satu dari 11 menu (triwulan langsung) -> TRUE.
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
        ]);
        $this->assertTrue(VervalRealisasiBosp::adaDataUntukTriwulan($sekolah->id, $tahun, 1));

        // Triwulan lain (belum ada data apapun) tetap FALSE.
        $this->assertFalse(VervalRealisasiBosp::adaDataUntukTriwulan($sekolah->id, $tahun, 2));

        // Data di menu berbasis BULAN (Pajak BOSP Reguler, bulan 5 = TW2).
        PajakBospReguler::create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'bulan' => 5, 'ppn_debit' => 1000,
        ]);
        $this->assertTrue(VervalRealisasiBosp::adaDataUntukTriwulan($sekolah->id, $tahun, 2));
    }

    public function test_triwulan_aktif_validasi_mengikuti_siklus_isi_data_lalu_verval(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $tahun = now()->year;

        // Belum ada data apapun - tidak ada triwulan aktif.
        $this->assertNull(VervalRealisasiBosp::triwulanAktifValidasi($sekolah->id, $tahun));

        // Admin mulai mengerjakan TW1 - TW1 jadi triwulan aktif.
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
        ]);
        $this->assertSame(1, VervalRealisasiBosp::triwulanAktifValidasi($sekolah->id, $tahun));

        // TW1 diverval (Belum Sesuai pun cukup) - panel untuk TW1 hilang,
        // & karena TW2 belum ada datanya, TIDAK ada triwulan aktif lagi.
        VervalRealisasiBosp::create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 1,
            'status' => VervalRealisasiBosp::STATUS_BELUM_SESUAI,
            'diverval_oleh' => null, 'diverval_pada' => now(),
        ]);
        $this->assertNull(VervalRealisasiBosp::triwulanAktifValidasi($sekolah->id, $tahun));

        // Admin mulai mengerjakan TW2 - panel muncul lagi untuk TW2.
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id, 'tahun' => $tahun, 'triwulan' => 2,
        ]);
        $this->assertSame(2, VervalRealisasiBosp::triwulanAktifValidasi($sekolah->id, $tahun));
    }

    /**
     * Method borongan `idSekolahAdaDataUntukTriwulan()` (ditambahkan
     * round ketiga belas, 2026-09-24, khusus utk Dashboard Pendataan
     * BOSP) HARUS menghasilkan daftar profil_sekolah_id yang PERSIS sama
     * dengan memanggil `adaDataUntukTriwulan()` (method per-sekolah yang
     * SUDAH ADA sebelumnya & TIDAK diubah) satu-per-satu utk tiap
     * sekolah - baik lewat data model triwulan-langsung (PenerimaanHonorPtk)
     * maupun model per-bulan (PajakBospReguler).
     */
    public function test_id_sekolah_ada_data_untuk_triwulan_konsisten_dengan_ada_data_untuk_triwulan_per_sekolah(): void
    {
        $tahun = now()->year;

        $sekolahTriwulanLangsung = ProfilSekolah::factory()->create();
        PenerimaanHonorPtk::factory()->create([
            'profil_sekolah_id' => $sekolahTriwulanLangsung->id, 'tahun' => $tahun, 'triwulan' => 1,
        ]);

        $sekolahPerBulan = ProfilSekolah::factory()->create();
        PajakBospReguler::create([
            'profil_sekolah_id' => $sekolahPerBulan->id, 'tahun' => $tahun, 'bulan' => 5, 'ppn_debit' => 1000,
        ]);

        $sekolahBelumIsi = ProfilSekolah::factory()->create();

        $idSekolahTw1 = VervalRealisasiBosp::idSekolahAdaDataUntukTriwulan($tahun, 1);
        $idSekolahTw2 = VervalRealisasiBosp::idSekolahAdaDataUntukTriwulan($tahun, 2);

        // TW1: hanya sekolah dgn data model triwulan-langsung yang masuk.
        $this->assertTrue($idSekolahTw1->contains($sekolahTriwulanLangsung->id));
        $this->assertFalse($idSekolahTw1->contains($sekolahPerBulan->id));
        $this->assertFalse($idSekolahTw1->contains($sekolahBelumIsi->id));

        // TW2 (bulan 5 = TW2 di PajakBospReguler::TRIWULAN_BULAN): hanya
        // sekolah dgn data model per-bulan yang masuk.
        $this->assertFalse($idSekolahTw2->contains($sekolahTriwulanLangsung->id));
        $this->assertTrue($idSekolahTw2->contains($sekolahPerBulan->id));
        $this->assertFalse($idSekolahTw2->contains($sekolahBelumIsi->id));

        // Konsistensi silang: cocokkan dgn adaDataUntukTriwulan() per
        // sekolah utk ketiga sekolah x kedua triwulan (6 kombinasi).
        foreach ([$sekolahTriwulanLangsung, $sekolahPerBulan, $sekolahBelumIsi] as $sekolah) {
            foreach ([1, 2] as $triwulan) {
                $viaBorongan = VervalRealisasiBosp::idSekolahAdaDataUntukTriwulan($tahun, $triwulan)->contains($sekolah->id);
                $viaPerSekolah = VervalRealisasiBosp::adaDataUntukTriwulan($sekolah->id, $tahun, $triwulan);

                $this->assertSame($viaPerSekolah, $viaBorongan, "Mismatch utk sekolah #{$sekolah->id} triwulan {$triwulan}");
            }
        }
    }
}
