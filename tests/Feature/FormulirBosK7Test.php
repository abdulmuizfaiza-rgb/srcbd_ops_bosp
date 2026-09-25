<?php

namespace Tests\Feature;

use App\Livewire\PendataanBosp\FormulirBosK7\Index;
use App\Models\FormulirBosK7;
use App\Models\PajakBospReguler;
use App\Models\PendataanBosp;
use App\Models\ProfilSekolah;
use App\Models\User;
use App\Models\VervalRealisasiBosp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menguji menu baru "Formulir BOS K7b & K7c" - Pendataan BOSP (permintaan
 * user 2026-09-23), ditempatkan di sidebar tepat sesudah "Laporan
 * Realisasi BOSP (Form BPK)".
 *
 * Struktur: 1 baris FormulirBosK7 (kunci profil_sekolah_id+tahun+bulan)
 * dipakai BERSAMA oleh 2 tab (K7b/K7c) - jawaban AskUserQuestion
 * 2026-09-23: Saldo Bank/Tunai input manual per bulan (BUKAN dari Dana
 * BOSP Tahap), Nomor/Tanggal SK diisi ulang tiap bulan langsung di K7c,
 * Export Excel & PDF tersedia. Rumus (Sub Jumlah 1/2, Saldo Kas Tunai, A,
 * B, Perbedaan) diverifikasi terhadap angka PERSIS dari gambar contoh
 * yang diupload user: 23 lembar Rp100.000 + 1 lembar Rp1.000 = Sub
 * Jumlah Kertas 2.301.000; 1 keping Rp100 = Sub Jumlah Logam 100; Saldo
 * Rekening Bank 346.739.300; B = 2.301.000+100+346.739.300 = 349.040.400;
 * Penerimaan BKU 394.350.000 - Pengeluaran BKU 45.309.650 = A
 * 349.040.350; Perbedaan (A-B) = -50.
 */
class FormulirBosK7Test extends TestCase
{
    use RefreshDatabase;

    /** Angka contoh persis dari gambar "FORMULIR BOS-K7b"/"FORMULIR BOS K7c" yang diupload user 2026-09-23. */
    private function isiContohGambar(): array
    {
        return [
            'lembar_100000' => 23,
            'lembar_50000' => 0,
            'lembar_20000' => 0,
            'lembar_10000' => 0,
            'lembar_5000' => 0,
            'lembar_2000' => 0,
            'lembar_1000' => 1,
            'keping_1000' => 0,
            'keping_500' => 0,
            'keping_200' => 0,
            'keping_100' => 1,
            'saldo_rekening_bank' => 346739300,
            'jumlah_total_penerimaan_bku' => 394350000,
            'jumlah_total_pengeluaran_bku' => 45309650,
        ];
    }

    public function test_rumus_sesuai_angka_contoh_gambar(): void
    {
        $baris = $this->isiContohGambar();

        $this->assertSame(2301000, FormulirBosK7::hitungSubJumlahUangKertas($baris));
        $this->assertSame(100, FormulirBosK7::hitungSubJumlahUangLogam($baris));
        $this->assertSame(2301100, FormulirBosK7::hitungSaldoKasTunai($baris));
        $this->assertSame(349040350, FormulirBosK7::hitungSaldoBku($baris));
        $this->assertSame(349040400, FormulirBosK7::hitungJumlahB($baris));
        $this->assertSame(-50, FormulirBosK7::hitungPerbedaan($baris));
    }

    /**
     * Regresi permintaan user 2026-09-23 (round kedua) - jawaban
     * AskUserQuestion "Tetap dari rincian, manual cuma cadangan": Saldo
     * Kas Tunai tetap dari rincian pecahan uang SELAMA totalnya > 0;
     * kolom `saldo_kas_tunai_manual` hanya dipakai kalau rincian kosong.
     */
    public function test_saldo_kas_tunai_dari_rincian_mengalahkan_manual_kalau_rincian_diisi(): void
    {
        $baris = array_merge($this->isiContohGambar(), ['saldo_kas_tunai_manual' => 999999999]);

        // Rincian (2.301.000 + 100 = 2.301.100) HARUS tetap dipakai, bukan nilai manual.
        $this->assertSame(2301100, FormulirBosK7::hitungSaldoKasTunai($baris));
    }

    public function test_saldo_kas_tunai_manual_dipakai_kalau_rincian_kosong(): void
    {
        $baris = [
            'lembar_100000' => 0, 'lembar_50000' => 0, 'lembar_20000' => 0, 'lembar_10000' => 0,
            'lembar_5000' => 0, 'lembar_2000' => 0, 'lembar_1000' => 0,
            'keping_1000' => 0, 'keping_500' => 0, 'keping_200' => 0, 'keping_100' => 0,
            'saldo_kas_tunai_manual' => 750000,
        ];

        $this->assertSame(750000, FormulirBosK7::hitungSaldoKasTunai($baris));
    }

    public function test_tanggal_penutupan_kas_otomatis_akhir_bulan(): void
    {
        $this->assertSame('31 January 2025', FormulirBosK7::tanggalPenutupanKas(2025, 1)->format('d F Y'));
        $this->assertSame('31 December 2024', FormulirBosK7::tanggalPenutupanKasBulanLalu(2025, 1)->format('d F Y'));
    }

    public function test_terbilang_tanggal_narasi_k7c_sesuai_contoh_gambar(): void
    {
        $narasi = FormulirBosK7::terbilangTanggalNarasi(FormulirBosK7::tanggalPenutupanKas(2025, 1));

        $this->assertSame("Jum'at, tanggal Tiga puluh satu Bulan Januari Tahun Dua ribu dua puluh lima", $narasi);
    }

    public function test_admin_bosp_otomatis_terkunci_ke_sekolahnya_sendiri(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        $component->assertSet('profil_sekolah_id', $sekolah->id);
        $this->assertSame($sekolah->id, $component->viewData('sekolah')->id);
        $this->assertSame('k7b', $component->get('tabAktif'));
    }

    // ------------------------------------------------------------------
    // Kuncian UI setelah Validasi Hasil Entry Data BOSP "Sesuai" -
    // permintaan user 2026-09-23 (round kesepuluh, poin 1b). Menu ini
    // BERBASIS BULAN (bukan langsung triwulan seperti menu-menu lain),
    // dipetakan lewat PajakBospReguler::triwulanDariBulan() - diuji
    // terpisah dari PenerimaanHonorPtkTest (menu representatif utama)
    // KARENA pemetaan bulan->triwulan ini kode BARU/khusus menu ini.
    // ------------------------------------------------------------------

    public function test_formulir_terkunci_untuk_bulan_di_triwulan_yang_sudah_sesuai(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        // Bulan 3 (Maret) = Triwulan 1 lewat PajakBospReguler::triwulanDariBulan().
        $this->assertSame(1, PajakBospReguler::triwulanDariBulan(3));

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
            ->set('bulan', 3)
            ->assertViewHas('terkunciTriwulanIni', true)
            ->assertSee('sudah divalidasi');
    }

    public function test_formulir_tidak_terkunci_untuk_bulan_di_triwulan_lain(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        $tahun = now()->year;

        // Bulan 6 (Juni) = Triwulan 2, BEDA dari TW1 yang sudah divalidasi.
        $this->assertSame(2, PajakBospReguler::triwulanDariBulan(6));

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
            ->set('bulan', 6)
            ->assertViewHas('terkunciTriwulanIni', false)
            ->assertDontSee('sudah divalidasi');
    }

    public function test_superadmin_wajib_pilih_sekolah_dulu_sebelum_formulir_terisi(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        FormulirBosK7::factory()->create(['profil_sekolah_id' => $sekolah->id, 'bulan' => now()->month]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class);

        $component->assertSet('profil_sekolah_id', null);
        $this->assertNull($component->viewData('sekolah'));
    }

    public function test_superadmin_bisa_memilih_sekolah_lalu_formulir_terisi(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        FormulirBosK7::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'bulan' => now()->month,
            'saldo_rekening_bank' => 5000000,
        ]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('pilihSekolah', $sekolah->id);

        $component->assertSet('profil_sekolah_id', $sekolah->id);
        $this->assertSame($sekolah->id, $component->viewData('sekolah')->id);
        $this->assertSame('5000000', $component->get('baris')['saldo_rekening_bank']);
    }

    public function test_pilih_sekolah_dengan_id_tidak_valid_dapat_404(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('pilihSekolah', 999999)
            ->assertNotFound();
    }

    public function test_admin_bosp_memanggil_pilih_sekolah_tidak_berpengaruh(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('pilihSekolah', $sekolahLain->id)
            ->assertSet('profil_sekolah_id', $sekolahSaya->id);
    }

    public function test_mengetik_kotak_field_k7b_membuat_baris_baru(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('bulan', 3)
            ->set('tahun', 2026)
            ->set('baris.data.lembar_100000', '23')
            ->set('baris.data.lembar_1000', '1')
            ->set('baris.data.keping_100', '1')
            ->set('baris.data.saldo_rekening_bank', '346739300')
            ->set('baris.data.jumlah_total_penerimaan_bku', '394350000')
            ->set('baris.data.jumlah_total_pengeluaran_bku', '45309650');

        $this->assertDatabaseHas('formulir_bos_k7', [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => 2026,
            'bulan' => 3,
            'lembar_100000' => 23,
            'lembar_1000' => 1,
            'keping_100' => 1,
            'saldo_rekening_bank' => 346739300,
            'jumlah_total_penerimaan_bku' => 394350000,
            'jumlah_total_pengeluaran_bku' => 45309650,
        ]);

        $formulir = FormulirBosK7::where('profil_sekolah_id', $sekolah->id)->where('tahun', 2026)->where('bulan', 3)->firstOrFail();
        $this->assertSame(2301000, FormulirBosK7::hitungSubJumlahUangKertas($formulir));
        $this->assertSame(349040400, FormulirBosK7::hitungJumlahB($formulir));
        $this->assertSame(-50, FormulirBosK7::hitungPerbedaan($formulir));
    }

    public function test_mengetik_field_yang_sudah_ada_melakukan_update_bukan_duplikat(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $formulir = FormulirBosK7::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'bulan' => now()->month,
            'saldo_rekening_bank' => 1000000,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('baris.data.saldo_rekening_bank', '9999999');

        $this->assertDatabaseCount('formulir_bos_k7', 1);
        $this->assertSame(9999999, $formulir->fresh()->saldo_rekening_bank);
    }

    /**
     * Regresi BUG 2026-09-23 (round kedua): mengosongkan kotak kembali ke
     * '' mengirim $wire.set(..., '') dari x-honor-ptk-tarif-cell - kolom
     * DB-nya NOT NULL (default 0), jadi kalau nilainya disimpan sebagai
     * null (bukan 0) Postgres menolak dengan QueryException 23502 & seluruh
     * request gagal (pengguna melihat Internal Server Error, dilaporkan
     * sebagai "data hilang" karena tidak ada yang tersimpan). Kotak
     * dikosongkan sekarang HARUS tersimpan sebagai 0, tidak boleh crash.
     */
    public function test_mengosongkan_kotak_angka_tersimpan_sebagai_nol_bukan_crash(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $formulir = FormulirBosK7::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => 2026,
            'bulan' => 6,
            'jumlah_total_pengeluaran_bku' => 45309650,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('tahun', 2026)
            ->set('bulan', 6)
            ->set('baris.data.jumlah_total_pengeluaran_bku', '')
            ->assertHasNoErrors('baris.data.jumlah_total_pengeluaran_bku');

        $this->assertSame(0, $formulir->fresh()->jumlah_total_pengeluaran_bku);
    }

    /**
     * Regresi permintaan user 2026-09-23 (round kedua) poin 4: data bulan
     * lain TIDAK boleh hilang/tertimpa saat pindah bulan+tahun (akar
     * masalah yang dilaporkan user adalah crash di atas menghentikan
     * request sebelum sempat menyimpan - dites di sini lewat 2 bulan
     * berbeda diisi bergantian, lalu dicek keduanya tetap utuh).
     */
    public function test_data_bulan_lain_tidak_hilang_saat_pindah_bulan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        $component->set('tahun', 2026)
            ->set('bulan', 5)
            ->set('baris.data.saldo_rekening_bank', '1000000');

        $component->set('tahun', 2026)
            ->set('bulan', 6)
            ->set('baris.data.saldo_rekening_bank', '2000000');

        $this->assertDatabaseHas('formulir_bos_k7', [
            'profil_sekolah_id' => $sekolah->id, 'tahun' => 2026, 'bulan' => 5, 'saldo_rekening_bank' => 1000000,
        ]);
        $this->assertDatabaseHas('formulir_bos_k7', [
            'profil_sekolah_id' => $sekolah->id, 'tahun' => 2026, 'bulan' => 6, 'saldo_rekening_bank' => 2000000,
        ]);
        $this->assertDatabaseCount('formulir_bos_k7', 2);

        // Balik lagi ke bulan 5 - datanya harus masih ada, bukan kosong/tertimpa.
        $baris = $component->set('tahun', 2026)->set('bulan', 5)->get('baris');
        $this->assertSame('1000000', $baris['saldo_rekening_bank']);
    }

    public function test_saldo_kas_tunai_manual_bisa_diisi_lewat_kotak_formulir(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('baris.data.saldo_kas_tunai_manual', '750000');

        $this->assertDatabaseHas('formulir_bos_k7', [
            'profil_sekolah_id' => $sekolah->id,
            'saldo_kas_tunai_manual' => 750000,
        ]);
    }

    public function test_input_negatif_ditolak_validasi(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('baris.data.saldo_rekening_bank', '-500')
            ->assertHasErrors('baris.data.saldo_rekening_bank');

        $this->assertDatabaseCount('formulir_bos_k7', 0);
    }

    public function test_superadmin_belum_pilih_sekolah_tidak_bisa_mengisi_kotak(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('baris.data.saldo_rekening_bank', '1000000');

        $this->assertDatabaseCount('formulir_bos_k7', 0);
    }

    public function test_tab_k7c_menyimpan_nomor_dan_tanggal_sk(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('pindahTab', 'k7c')
            ->assertSet('tabAktif', 'k7c')
            ->set('baris.data.no_sk_kepala_sekolah', '824/Kep.1127-SEKRET/2021')
            ->set('baris.data.tanggal_sk_kepala_sekolah', '2021-09-30')
            ->set('baris.data.no_sk_bendahara', '400.3.5.5/203-SMPN/2024')
            ->set('baris.data.tanggal_sk_bendahara', '2024-01-02');

        $this->assertDatabaseHas('formulir_bos_k7', [
            'profil_sekolah_id' => $sekolah->id,
            'no_sk_kepala_sekolah' => '824/Kep.1127-SEKRET/2021',
            'no_sk_bendahara' => '400.3.5.5/203-SMPN/2024',
        ]);

        $formulir = FormulirBosK7::where('profil_sekolah_id', $sekolah->id)->firstOrFail();
        $this->assertSame('2021-09-30', $formulir->tanggal_sk_kepala_sekolah->format('Y-m-d'));
        $this->assertSame('2024-01-02', $formulir->tanggal_sk_bendahara->format('Y-m-d'));
    }

    public function test_penjelasan_perbedaan_bisa_diisi(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('baris.data.penjelasan_perbedaan', 'Rp.050 dibulatkan menjadi Rp.100');

        $this->assertDatabaseHas('formulir_bos_k7', [
            'profil_sekolah_id' => $sekolah->id,
            'penjelasan_perbedaan' => 'Rp.050 dibulatkan menjadi Rp.100',
        ]);
    }

    public function test_pindah_tab_hanya_menerima_k7b_atau_k7c(): void
    {
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('pindahTab', 'tidak-valid')
            ->assertSet('tabAktif', 'k7b')
            ->call('pindahTab', 'k7c')
            ->assertSet('tabAktif', 'k7c');
    }

    public function test_export_excel_gagal_tanpa_pilih_sekolah_superadmin(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('exportExcel')
            ->assertSet('errorExport', fn ($v) => ! empty($v));
    }

    public function test_export_excel_berhasil_untuk_admin_bosp(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        FormulirBosK7::factory()->create(array_merge($this->isiContohGambar(), [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => 2025,
            'bulan' => 1,
        ]));
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('tahun', 2025)
            ->set('bulan', 1)
            ->call('exportExcel')
            ->assertFileDownloaded('formulir-bos-k7-'.Str::slug($sekolah->nama_sekolah).'-Januari-2025.xlsx');
    }

    public function test_export_pdf_berhasil_untuk_kedua_tab(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        FormulirBosK7::factory()->create(array_merge($this->isiContohGambar(), [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => 2025,
            'bulan' => 1,
        ]));
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('tahun', 2025)
            ->set('bulan', 1)
            ->call('exportPdf')
            ->assertFileDownloaded('formulir-bos-k7b-'.Str::slug($sekolah->nama_sekolah).'-Januari-2025.pdf');

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('tahun', 2025)
            ->set('bulan', 1)
            ->call('pindahTab', 'k7c')
            ->call('exportPdf')
            ->assertFileDownloaded('formulir-bos-k7c-'.Str::slug($sekolah->nama_sekolah).'-Januari-2025.pdf');
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

    public function test_route_formulir_bos_k7_bisa_diakses_admin_bosp_dan_superadmin(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $this->actingAs($adminBosp)
            ->get('/pendataan-bosp/formulir-bos-k7')
            ->assertOk();

        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $this->actingAs($superadmin)
            ->get('/pendataan-bosp/formulir-bos-k7')
            ->assertOk();
    }

    public function test_link_formulir_bos_k7_muncul_di_sidebar_setelah_laporan_realisasi_bosp(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        $response = $this->actingAs($adminBosp)->get('/pendataan-bosp');

        $response->assertOk()->assertSee('Formulir BOS K7b &amp; K7c', false);

        $posisiLaporanRealisasi = strpos($response->getContent(), 'Laporan Realisasi BOSP (Form BPK)');
        $posisiFormulirK7 = strpos($response->getContent(), 'Formulir BOS K7b &amp; K7c');

        $this->assertNotFalse($posisiLaporanRealisasi);
        $this->assertNotFalse($posisiFormulirK7);
        $this->assertGreaterThan($posisiLaporanRealisasi, $posisiFormulirK7);
    }

    public function test_data_formulir_bos_k7_tidak_bercampur_dengan_menu_lain(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        FormulirBosK7::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => now()->year,
            'bulan' => 1,
            'saldo_rekening_bank' => 12345,
        ]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $baris = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('bulan', 1)
            ->get('baris');

        $this->assertSame('12345', $baris['saldo_rekening_bank']);
        $this->assertDatabaseCount('formulir_bos_k7', 1);
    }

    // ====================================================================
    // Round ketiga 2026-09-23: posisi tanda tangan sejajar kolom Excel
    // (6 kolom), garis "Jumlah" K7c hanya di kolom Rp, & tombol Jenis
    // Kertas/Cetak/Setting Margin.
    // ====================================================================

    public function test_pengaturan_kertas_dan_margin_default_sesuai_jawaban_user(): void
    {
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        $component->assertSet('jenisKertas', 'a4');
        $component->assertSet('marginKiri', 2.5);
        $component->assertSet('marginKanan', 2.5);
        $component->assertSet('marginAtas', 3.0);
        $component->assertSet('marginBawah', 2.5);
        $component->assertSet('tampilSettingMargin', false);
    }

    public function test_setting_margin_bisa_dibuka_dan_ditutup(): void
    {
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('toggleSettingMargin')
            ->assertSet('tampilSettingMargin', true)
            ->call('toggleSettingMargin')
            ->assertSet('tampilSettingMargin', false);
    }

    public function test_margin_dibatasi_ke_rentang_wajar(): void
    {
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
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
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('tahun', 2025)
            ->set('bulan', 1)
            ->set('jenisKertas', 'f4');

        $url = $component->instance()->urlCetak();

        $this->assertNotNull($url);
        $this->assertStringContainsString('/pendataan-bosp/formulir-bos-k7/cetak', $url);
        $this->assertStringContainsString('tab=k7b', $url);
        $this->assertStringContainsString('tahun=2025', $url);
        $this->assertStringContainsString('bulan=1', $url);
        $this->assertStringContainsString('profil_sekolah_id='.$sekolah->id, $url);
        $this->assertStringContainsString('kertas=f4', $url);
        $this->assertStringContainsString('margin_atas=3', $url);
    }

    public function test_route_cetak_menampilkan_pdf_inline_untuk_pemilik_sekolah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);
        FormulirBosK7::factory()->create(array_merge($this->isiContohGambar(), [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => 2025,
            'bulan' => 1,
        ]));
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $response = $this->actingAs($adminBosp)->get('/pendataan-bosp/formulir-bos-k7/cetak?'.http_build_query([
            'tab' => 'k7b',
            'tahun' => 2025,
            'bulan' => 1,
            'profil_sekolah_id' => $sekolah->id,
            'kertas' => 'f4',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
    }

    public function test_route_cetak_ditolak_untuk_sekolah_lain(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolahSaya->id]);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        $this->actingAs($adminBosp)
            ->get('/pendataan-bosp/formulir-bos-k7/cetak?'.http_build_query([
                'tab' => 'k7b',
                'tahun' => 2025,
                'bulan' => 1,
                'profil_sekolah_id' => $sekolahLain->id,
            ]))
            ->assertForbidden();
    }

    public function test_export_excel_dan_pdf_tetap_berhasil_dengan_kertas_f4_dan_margin_kustom(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        FormulirBosK7::factory()->create(array_merge($this->isiContohGambar(), [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => 2025,
            'bulan' => 1,
        ]));
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('tahun', 2025)
            ->set('bulan', 1)
            ->set('jenisKertas', 'f4')
            ->set('marginAtas', 1.5)
            ->call('exportExcel')
            ->assertFileDownloaded('formulir-bos-k7-'.Str::slug($sekolah->nama_sekolah).'-Januari-2025.xlsx');

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('tahun', 2025)
            ->set('bulan', 1)
            ->set('jenisKertas', 'f4')
            ->call('exportPdf')
            ->assertFileDownloaded('formulir-bos-k7b-'.Str::slug($sekolah->nama_sekolah).'-Januari-2025.pdf');
    }

    public function test_ttd_k7b_kepala_sekolah_sejajar_kolom_bendahara_di_kolom_2(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        // Bendahara mulai kolom ke-2 (colspan 2), Kepala Sekolah mulai
        // kolom ke-4 (colspan 3) - grid 6 kolom meniru A-F di Excel (tidak
        // berubah dari round ketiga; round kelima hanya menambah
        // class="text-center", tidak memindah colspan/kolom).
        $component->assertSeeHtml('colspan="2" class="text-center">Bendahara</td>');
        $component->assertSeeHtml('colspan="3" class="text-center">Kepala Sekolah');
    }

    public function test_ttd_k7c_bendahara_kolom_2_kepsek_sebelum_3_kolom_terakhir(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('pindahTab', 'k7c');

        $component->assertSeeHtml('colspan="2" class="text-center">Bendahara / Pemegang KAS');
        $component->assertSeeHtml('colspan="3" class="text-center">Kepala Sekolah');
    }

    public function test_garis_baris_jumlah_k7c_hanya_di_kolom_rp(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('pindahTab', 'k7c');

        // Baris "Jumlah" TIDAK LAGI border-t di <tr>-nya (dulu di seluruh
        // baris) - sekarang border-t hanya di 2 <td> terakhir (Rp + nilai).
        $component->assertDontSeeHtml('<tr class="font-bold border-t border-slate-300">');
        $component->assertSeeHtml('border-t border-slate-300">Rp</td>');
    }

    // ===== Round keempat 2026-09-23: baris "Tanggal, ..." dipindah masuk
    // ke tabel tanda tangan (sejajar kolom Kepala Sekolah), kolom "Rp" di
    // rincian K7c diatur supaya sejajar dgn kolom Kepala Sekolah, & ruang
    // tanda tangan diperbesar jadi 3 baris kosong (K7b & K7c). =====

    public function test_k7b_tanggal_pindah_ke_dalam_tabel_ttd_sejajar_kepala_sekolah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        // "Tanggal, ..." sekarang jadi sel tabel colspan="3" (kolom yang
        // SAMA dengan Kepala Sekolah), BUKAN lagi <div> rata-kanan bebas.
        $component->assertSeeHtml('colspan="3" class="text-center">Tanggal,');
        $component->assertDontSeeHtml('class="text-right text-xs mt-6">Tanggal,');
    }

    public function test_k7c_kolom_rp_rincian_sejajar_kolom_kepala_sekolah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('pindahTab', 'k7c');

        // Tabel rincian pakai colgroup eksplisit (huruf 4% + label 42% +
        // titik-dua 4% = 50%, persis kolom ke-4/50% tempat Kepala Sekolah
        // mulai di tabel ttd) supaya kolom "Rp" sungguh-sungguh sejajar.
        $component->assertSeeHtml('<col style="width:4%"><col style="width:42%"><col style="width:4%">');
        $component->assertSeeHtml('table-layout:fixed');
    }

    public function test_k7b_dan_k7c_ruang_tanda_tangan_4_baris_kosong(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        // Round kelima: diperbesar lagi dari 3 baris kosong (round keempat)
        // jadi 4 baris kosong, sesuai permintaan user.
        $componentK7b = Livewire::actingAs($adminBosp)->test(Index::class);
        $jumlahBarisKosongK7b = substr_count($componentK7b->html(), '<tr><td class="h-5"></td><td colspan="2"></td><td colspan="3"></td></tr>');
        $this->assertSame(4, $jumlahBarisKosongK7b, 'K7b harus punya 4 baris kosong (h-5) di ruang tanda tangan.');

        $componentK7c = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('pindahTab', 'k7c');
        $jumlahBarisKosongK7c = substr_count($componentK7c->html(), '<tr><td class="h-5"></td><td colspan="2"></td><td colspan="3"></td></tr>');
        $this->assertSame(4, $jumlahBarisKosongK7c, 'K7c harus punya 4 baris kosong (h-5) di ruang tanda tangan.');
    }

    // ===== Round kelima 2026-09-23: tanda tangan Bendahara & Kepala
    // Sekolah rata TENGAH (sebelumnya rata kiri di round keempat), ruang
    // tanda tangan 4 baris kosong (di atas), & Excel Bendahara pindah ke
    // kolom 1 dengan teks rata tengah. =====

    public function test_k7b_tanda_tangan_bendahara_dan_kepsek_rata_tengah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        $component->assertSeeHtml('colspan="3" class="text-center">Tanggal,');
        $component->assertSeeHtml('colspan="2" class="text-center">Bendahara</td>');
        $component->assertSeeHtml('colspan="3" class="text-center">Kepala Sekolah');
        $component->assertSeeHtml('colspan="2" class="text-center">'.$sekolah->nama_bendahara.'</td>');
        $component->assertSeeHtml('colspan="3" class="text-center">'.$sekolah->nama_kepala_sekolah.'</td>');
    }

    public function test_k7c_tanda_tangan_bendahara_dan_kepsek_rata_tengah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('pindahTab', 'k7c');

        $component->assertSeeHtml('colspan="2" class="text-center">Bendahara / Pemegang KAS</td>');
        $component->assertSeeHtml('colspan="3" class="text-center">Kepala Sekolah</td>');
        $component->assertSeeHtml('colspan="2" class="text-center">'.$sekolah->nama_bendahara.'</td>');
        $component->assertSeeHtml('colspan="3" class="text-center">'.$sekolah->nama_kepala_sekolah.'</td>');
    }

    public function test_excel_k7b_bendahara_pindah_ke_kolom_a_rata_tengah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $data = $this->dataUntukExport($sekolah);

        $export = new \App\Exports\FormulirBosK7bSheetExport($sekolah, 2025, 1, $data);
        $spreadsheet = $this->bacaSpreadsheetDariExport($export);
        $sheet = $spreadsheet->getActiveSheet();

        [$barisBendahara, $barisNip] = $this->cariBarisTtd($sheet, 'Bendahara', $sekolah->nama_bendahara);

        // Round keenam: jarak label "Bendahara" ke baris Nama Bendahara
        // bertambah 2 baris (dari -4 jadi -6) karena ruang kosong tanda
        // tangan diperbesar (baris 47/48 -> 49/50, lihat FormulirBosK7bSheetExport).
        $this->assertSame('Bendahara', $sheet->getCell('A'.($barisBendahara - 6))->getValue());
        $this->assertSame($sekolah->nama_bendahara, $sheet->getCell('A'.$barisBendahara)->getValue());
        $this->assertSame('NIP. '.$sekolah->nip_bendahara, $sheet->getCell('A'.$barisNip)->getValue());
        // Kolom B TIDAK lagi dipakai untuk Bendahara (round ketiga) -
        // sekarang kembali ke kolom A ("kolom ke 1"), rata tengah.
        $this->assertSame('', (string) $sheet->getCell('B'.$barisBendahara)->getValue());
        $this->assertSame(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            $sheet->getStyle('A'.$barisBendahara)->getAlignment()->getHorizontal()
        );
    }

    public function test_excel_k7c_bendahara_pindah_ke_kolom_a_rata_tengah(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $data = $this->dataUntukExport($sekolah);

        $export = new \App\Exports\FormulirBosK7cSheetExport($sekolah, 2025, 1, $data);
        $spreadsheet = $this->bacaSpreadsheetDariExport($export);
        $sheet = $spreadsheet->getActiveSheet();

        [$barisBendahara, $barisNip] = $this->cariBarisTtd($sheet, 'Bendahara / Pemegang KAS', $sekolah->nama_bendahara);

        // Round keenam: jarak label ke baris Nama Bendahara bertambah 2
        // baris (dari -4 jadi -6), sama seperti K7b (baris 33/34 -> 35/36).
        $this->assertSame('Bendahara / Pemegang KAS', $sheet->getCell('A'.($barisBendahara - 6))->getValue());
        $this->assertSame($sekolah->nama_bendahara, $sheet->getCell('A'.$barisBendahara)->getValue());
        $this->assertSame('NIP. '.$sekolah->nip_bendahara, $sheet->getCell('A'.$barisNip)->getValue());
        $this->assertSame('', (string) $sheet->getCell('B'.$barisBendahara)->getValue());
        $this->assertSame(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            $sheet->getStyle('A'.$barisBendahara)->getAlignment()->getHorizontal()
        );
    }

    /** Data siap pakai untuk memanggil *SheetExport langsung (tanpa lewat Livewire), dipakai test Excel round kelima. */
    private function dataUntukExport(ProfilSekolah $sekolah): array
    {
        FormulirBosK7::factory()->create(array_merge($this->isiContohGambar(), [
            'profil_sekolah_id' => $sekolah->id,
            'tahun' => 2025,
            'bulan' => 1,
        ]));

        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        return Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->set('tahun', 2025)
            ->set('bulan', 1)
            ->invade()
            ->dataFormulir();
    }

    /** @return \PhpOffice\PhpSpreadsheet\Spreadsheet */
    private function bacaSpreadsheetDariExport($export)
    {
        $isiXlsx = \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        $tmp = tempnam(sys_get_temp_dir(), 'k7-export-test-').'.xlsx';
        file_put_contents($tmp, $isiXlsx);
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);
        unlink($tmp);

        return $spreadsheet;
    }

    /** Cari baris nama Bendahara (bold+underline) & baris NIP tepat setelahnya, berdasarkan label kolom D di baris yang sama (Kepala Sekolah) sebagai penanda blok ttd. */
    private function cariBarisTtd($sheet, string $labelBendahara, string $namaBendahara): array
    {
        $baris = 1;
        foreach ($sheet->getRowIterator() as $row) {
            $nilaiA = (string) $sheet->getCell('A'.$row->getRowIndex())->getValue();
            if ($nilaiA === $namaBendahara) {
                $baris = $row->getRowIndex();
                break;
            }
        }

        return [$baris, $baris + 1];
    }

    // ===== Round keenam 2026-09-23 poin 1: field "Saldo Kas Tunai
    // (manual)" DIHAPUS dari tampilan Formulir BOS K7b - kolom DB
    // `saldo_kas_tunai_manual` & logic fallback-nya SENGAJA tetap ada
    // (tidak disentuh), jadi test lama di atas (yang set lewat properti
    // Livewire langsung, bukan lewat kotak di layar) tetap valid untuk
    // backend-nya. =====

    public function test_kotak_saldo_kas_tunai_manual_tidak_lagi_tampil_di_formulir_k7b(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class);

        $component->assertDontSee('Saldo Kas Tunai (manual)');
        $component->assertDontSeeHtml('wire:model="baris.data.saldo_kas_tunai_manual"');
    }

    // ===== Round keenam 2026-09-23 poin 2: Nama Bendahara+NIP &
    // Nama Kepala Sekolah+NIP pada Excel harus persis di baris ke
    // 49/50 (K7b) & 35/36 (K7c). =====

    public function test_excel_k7b_nama_bendahara_dan_nip_tepat_di_baris_49_dan_50(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $data = $this->dataUntukExport($sekolah);

        $export = new \App\Exports\FormulirBosK7bSheetExport($sekolah, 2025, 1, $data);
        $spreadsheet = $this->bacaSpreadsheetDariExport($export);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame($sekolah->nama_bendahara, $sheet->getCell('A49')->getValue());
        $this->assertSame('NIP. '.$sekolah->nip_bendahara, $sheet->getCell('A50')->getValue());
        $this->assertSame($sekolah->nama_kepala_sekolah, $sheet->getCell('D49')->getValue());
        $this->assertSame('NIP. '.$sekolah->nip_kepala_sekolah, $sheet->getCell('D50')->getValue());
    }

    public function test_excel_k7c_nama_bendahara_dan_nip_tepat_di_baris_35_dan_36(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $data = $this->dataUntukExport($sekolah);

        $export = new \App\Exports\FormulirBosK7cSheetExport($sekolah, 2025, 1, $data);
        $spreadsheet = $this->bacaSpreadsheetDariExport($export);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame($sekolah->nama_bendahara, $sheet->getCell('A35')->getValue());
        $this->assertSame('NIP. '.$sekolah->nip_bendahara, $sheet->getCell('A36')->getValue());
        $this->assertSame($sekolah->nama_kepala_sekolah, $sheet->getCell('D35')->getValue());
        $this->assertSame('NIP. '.$sekolah->nip_kepala_sekolah, $sheet->getCell('D36')->getValue());
    }
}
