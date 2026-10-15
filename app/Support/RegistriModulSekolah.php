<?php

namespace App\Support;

use App\Models\BelanjaHonorKegiatan;
use App\Models\BiayaPendaftaranLomba;
use App\Models\DanaBospTahap;
use App\Models\DataPtk;
use App\Models\FormulirBosK7;
use App\Models\Lampiran2a;
use App\Models\Lampiran2b;
use App\Models\Lampiran2c;
use App\Models\LanggananDayaJasa;
use App\Models\LaporanRealisasiBosp;
use App\Models\LembarValidasiTpg;
use App\Models\LembarValidasiTpgCeklist;
use App\Models\PajakBospReguler;
use App\Models\PendataanBosp;
use App\Models\PendataanOps;
use App\Models\PenerimaanHonorPtk;
use App\Models\ProfilSekolah;
use App\Models\RekapRkas;
use App\Models\RincianBelanjaBarangHabisPakai;
use App\Models\RincianBelanjaModal;
use App\Models\RincianBelanjaModalBmd;
use App\Models\RincianPemeliharaan;
use App\Models\RincianPemeliharaanPc;
use App\Models\StockOpnameBarangPersediaan;
use App\Models\SuratTpg;
use App\Models\VervalRealisasiBosp;

/**
 * Registry TUNGGAL berisi semua modul data yang terikat ke satu sekolah
 * (profil_sekolah_id) - diekstrak dari App\Livewire\CekDatabaseAplikasi\
 * Index (menu "Cek Database dan Aplikasi", permintaan user 2026-09-27)
 * supaya BISA DIPAKAI BERSAMA oleh menu baru "Sinkronisasi dan Tarik
 * Data" (permintaan user 2026-10-14 poin 3) - refactor MURNI, PERILAKU
 * CekDatabaseAplikasi TIDAK BERUBAH SAMA SEKALI.
 *
 * Kenapa digabung jadi 1 sumber: kalau ada menu/tabel baru ditambahkan
 * di masa depan, cukup diupdate di SATU tempat ini - supaya menu "Cek
 * Database dan Aplikasi" & "Sinkronisasi dan Tarik Data" TIDAK PERNAH
 * tidak-sinkron satu sama lain soal modul apa saja yang ada.
 */
class RegistriModulSekolah
{
    /**
     * Semua modul yang datanya terikat ke satu sekolah (profil_sekolah_id).
     * Label di sini SENGAJA disamakan dengan nama menu di sidebar supaya
     * mudah dikenali Superadmin.
     *
     * @return array<string, class-string>
     */
    public static function semua(): array
    {
        return [
            'Lampiran 2A' => Lampiran2a::class,
            'Lampiran 2B' => Lampiran2b::class,
            'Lampiran 2C' => Lampiran2c::class,
            'Surat TPG' => SuratTpg::class,
            'Lembar Validasi TPG' => LembarValidasiTpg::class,
            'Lembar Validasi TPG Ceklist' => LembarValidasiTpgCeklist::class,
            'Rekap RKAS' => RekapRkas::class,
            'Dana BOSP Tahap' => DanaBospTahap::class,
            'Penerimaan Honor PTK' => PenerimaanHonorPtk::class,
            'Langganan Daya dan Jasa' => LanggananDayaJasa::class,
            'Belanja Pemeliharaan Bangunan' => RincianPemeliharaan::class,
            'Belanja Pemeliharaan PC' => RincianPemeliharaanPc::class,
            'Biaya Pendaftaran Lomba' => BiayaPendaftaranLomba::class,
            'Belanja Honor Kegiatan' => BelanjaHonorKegiatan::class,
            'Rincian Belanja Modal' => RincianBelanjaModal::class,
            'Rincian Belanja Modal (BMD)' => RincianBelanjaModalBmd::class,
            'Rincian Belanja Barang Habis Pakai' => RincianBelanjaBarangHabisPakai::class,
            'Pajak BOSP Reguler' => PajakBospReguler::class,
            'Laporan Realisasi BOSP' => LaporanRealisasiBosp::class,
            'Stock Opname Barang Persediaan' => StockOpnameBarangPersediaan::class,
            'Formulir BOS K7' => FormulirBosK7::class,
            'Verval Realisasi BOSP' => VervalRealisasiBosp::class,
            'Pendataan OPS (Identitas)' => PendataanOps::class,
            'Pendataan BOSP (Identitas)' => PendataanBosp::class,
            'Data PTK' => DataPtk::class,
        ];
    }

    /**
     * Modul yang punya kunci unik alami per-kombinasi kolom (sesuai
     * unique constraint di database).
     *
     * @return array<string, array{0: class-string, 1: array<int, string>}>
     */
    public static function kunciUnik(): array
    {
        return [
            'Rekap RKAS' => [RekapRkas::class, ['profil_sekolah_id', 'tahun']],
            'Dana BOSP Tahap' => [DanaBospTahap::class, ['profil_sekolah_id', 'tahun']],
            'Penerimaan Honor PTK' => [PenerimaanHonorPtk::class, ['profil_sekolah_id', 'tahun', 'triwulan', 'nuptk']],
            'Pajak BOSP Reguler' => [PajakBospReguler::class, ['profil_sekolah_id', 'tahun', 'bulan']],
            'Laporan Realisasi BOSP' => [LaporanRealisasiBosp::class, ['profil_sekolah_id', 'tahun', 'triwulan']],
            'Formulir BOS K7' => [FormulirBosK7::class, ['profil_sekolah_id', 'tahun', 'bulan']],
            'Verval Realisasi BOSP' => [VervalRealisasiBosp::class, ['profil_sekolah_id', 'tahun', 'triwulan']],
            'Surat TPG' => [SuratTpg::class, ['profil_sekolah_id', 'tahun', 'triwulan', 'jenis']],
            'Lembar Validasi TPG' => [LembarValidasiTpg::class, ['profil_sekolah_id', 'tahun', 'triwulan']],
            'Lembar Validasi TPG Ceklist' => [LembarValidasiTpgCeklist::class, ['profil_sekolah_id', 'tahun', 'triwulan', 'nuptk']],
            'Pendataan OPS (Identitas)' => [PendataanOps::class, ['profil_sekolah_id']],
            'Pendataan BOSP (Identitas)' => [PendataanBosp::class, ['profil_sekolah_id']],
            'Data PTK' => [DataPtk::class, ['nik']],
        ];
    }

    /**
     * Daftar LENGKAP modul yang disinkronkan oleh menu "Sinkronisasi dan
     * Tarik Data" (permintaan user 2026-10-14 poin 3) - SEMUA modul dari
     * semua() DITAMBAH Profil Sekolah itu sendiri (yang bukan bagian
     * dari semua() karena semua() khusus modul yang "terikat KE" sekolah,
     * bukan sekolahnya sendiri).
     *
     * Setiap entri: nama tabel => [model, kunci_alami (kolom-kolom unique
     * constraint asli) ATAU null kalau modul itu TIDAK punya kunci unik
     * alami (murni daftar item, lihat semua() yang tidak muncul di
     * kunciUnik() - kunci unik alami SENGAJA tidak ada karena baris boleh
     * duplikat) - untuk modul yang null, pencocokan antar 2 database
     * terpisah (Lokal/Online) dilakukan lewat kolom `sync_uuid` (lihat
     * App\Models\Concerns\Syncable & migration
     * add_sync_uuid_ke_tabel_tanpa_kunci_unik).
     *
     * @return array<string, array{model: class-string, kunci: array<int, string>|null, kolom_dikecualikan: array<int, string>}>
     */
    public static function tabelDisinkronkan(): array
    {
        $kunciUnik = self::kunciUnik();
        $hasil = [];

        foreach (self::semua() as $label => $model) {
            $kunci = $kunciUnik[$label][1] ?? null;
            $hasil[(new $model())->getTable()] = [
                'label' => $label,
                'model' => $model,
                'kunci' => $kunci,
                'kolom_dikecualikan' => [],
            ];
        }

        // Profil Sekolah sendiri - kunci alami = npsn (unique constraint
        // asli, lihat migration add_status_kecamatan_unique_npsn).
        // logo_sekolah & logo_pemda DIKECUALIKAN dari sinkronisasi (file
        // gambar tidak ikut dikirim, hanya path-nya - mengirim path akan
        // menghasilkan path rusak/tidak ada filenya di sisi tujuan,
        // lihat docblock App\Services\SyncEngine).
        $hasil['profil_sekolah'] = [
            'label' => 'Profil Sekolah',
            'model' => ProfilSekolah::class,
            'kunci' => ['npsn'],
            'kolom_dikecualikan' => ['logo_sekolah', 'logo_pemda'],
        ];

        // foto_ops & sk_ops (Pendataan OPS/Identitas) sama-sama file,
        // dikecualikan dengan alasan yang sama.
        $hasil['pendataan_ops']['kolom_dikecualikan'] = ['foto_ops', 'sk_ops'];

        return $hasil;
    }
}
