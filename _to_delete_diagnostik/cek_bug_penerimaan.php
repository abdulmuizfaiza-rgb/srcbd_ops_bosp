<?php

use App\Models\ProfilSekolah;
use App\Models\DanaBospTahap;
use App\Models\LaporanRealisasiBosp;

echo "===== 1. Cari sekolah 'UJI COBA' =====\n";
$sekolahList = ProfilSekolah::where('nama_sekolah', 'like', '%UJI COBA%')->get();
foreach ($sekolahList as $s) {
    echo "id={$s->id} | npsn={$s->npsn} | nama={$s->nama_sekolah} | status={$s->status} | kecamatan={$s->kecamatan}\n";
}

if ($sekolahList->isEmpty()) {
    echo "TIDAK DITEMUKAN sekolah dengan nama mengandung 'UJI COBA'.\n";
    exit;
}

foreach ($sekolahList as $sekolah) {
    $id = $sekolah->id;
    echo "\n===== 2. Data DanaBospTahap utk id={$id}, tahun=2026 =====\n";
    $dbt = DanaBospTahap::where('profil_sekolah_id', $id)->where('tahun', 2026)->first();
    if (! $dbt) {
        echo "TIDAK ADA baris DanaBospTahap utk sekolah ini di tahun 2026.\n";
    } else {
        echo "id_baris={$dbt->id}\n";
        echo "jumlah_siswa=" . var_export($dbt->jumlah_siswa, true) . "\n";
        echo "jumlah_dana_bosp_per_tahun=" . var_export($dbt->jumlah_dana_bosp_per_tahun, true) . "\n";
        echo "total_penerimaan_setahun=" . var_export($dbt->total_penerimaan_setahun, true) . "\n";
        echo "penerimaan_tahap_1=" . var_export($dbt->penerimaan_tahap_1, true) . "\n";
        echo "penerimaan_tahap_2=" . var_export($dbt->penerimaan_tahap_2, true) . "\n";
        echo "profil_sekolah_id (raw)=" . var_export($dbt->profil_sekolah_id, true) . " (tipe: " . gettype($dbt->profil_sekolah_id) . ")\n";
    }

    echo "\n===== 3. Hasil fungsi ambilTotalPenerimaanDanaBos(2026, 1) =====\n";
    $hasil = LaporanRealisasiBosp::ambilTotalPenerimaanDanaBos(2026, 1);
    echo "Jumlah sekolah dlm hasil: " . $hasil->count() . "\n";
    echo "Value utk id={$id} (int key): " . var_export($hasil[$id] ?? 'TIDAK ADA KEY', true) . "\n";
    echo "Value utk id='{$id}' (string key): " . var_export($hasil[(string) $id] ?? 'TIDAK ADA KEY', true) . "\n";
    echo "Semua key yg ada (tipe & isi): ";
    foreach ($hasil->keys() as $k) {
        echo "[{$k} (" . gettype($k) . ")] ";
    }
    echo "\n";

    echo "\n===== 4. profil_sekolah_id sekolah ini (tipe & isi) =====\n";
    echo "sekolah->id=" . var_export($id, true) . " (tipe: " . gettype($id) . ")\n";

    echo "\n===== 5. Apakah ada baris LaporanRealisasiBosp lama utk sekolah ini (tahun 2026, triwulan 1)? =====\n";
    $lap = LaporanRealisasiBosp::where('profil_sekolah_id', $id)->where('tahun', 2026)->where('triwulan', 1)->first();
    if ($lap) {
        echo "ADA baris lama - id={$lap->id}, penerimaan_dana_bos (kolom DB, seharusnya tidak dipakai lagi)=" . var_export($lap->penerimaan_dana_bos, true) . "\n";
    } else {
        echo "Tidak ada baris LaporanRealisasiBosp tersimpan utk kombinasi ini (normal, kolom ini sudah dihitung real-time).\n";
    }
}
