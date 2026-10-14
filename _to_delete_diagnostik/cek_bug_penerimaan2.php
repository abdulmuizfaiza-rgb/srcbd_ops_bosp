<?php

use App\Livewire\PendataanBosp\LaporanRealisasiBosp\Index;
use App\Models\User;

echo "===== Cari user superadmin utk simulasi login =====\n";
$user = User::where('level_akses', 'superadmin')->first();
if (! $user) {
    echo "TIDAK ADA user dgn level_akses=superadmin, coba cari yg lain...\n";
    $user = User::first();
}
echo "Login sbg: username={$user->username}, level_akses={$user->level_akses}\n";

auth()->login($user);

echo "\n===== Panggil LANGSUNG method renderTabTriwulan() milik komponen Livewire asli =====\n";
$component = new Index();
$component->tahun = 2026;
$component->tabAktif = 'tw1';

$reflection = new ReflectionMethod($component, 'renderTabTriwulan');
$reflection->setAccessible(true);
[$daftarSekolah, $totalNumerik] = $reflection->invoke($component, 1);

echo "Jumlah sekolah di \$daftarSekolah: " . $daftarSekolah->count() . "\n";
echo "totalNumerik['penerimaan_dana_bos'] = " . var_export($totalNumerik['penerimaan_dana_bos'] ?? 'TIDAK ADA', true) . "\n";

echo "\n===== Isi \$component->baris utk sekolah 'UJI COBA' =====\n";
$sekolahUjiCoba = $daftarSekolah->first(fn ($s) => str_contains($s->nama_sekolah, 'UJI COBA'));
if ($sekolahUjiCoba) {
    echo "Ditemukan di \$daftarSekolah: id={$sekolahUjiCoba->id}, nama={$sekolahUjiCoba->nama_sekolah}\n";
    echo "component->baris[{$sekolahUjiCoba->id}]['penerimaan_dana_bos'] = " . var_export($component->baris[$sekolahUjiCoba->id]['penerimaan_dana_bos'] ?? 'TIDAK ADA', true) . "\n";
    echo "Seluruh isi baris utk sekolah ini:\n";
    print_r($component->baris[$sekolahUjiCoba->id] ?? []);
} else {
    echo "TIDAK ditemukan sekolah 'UJI COBA' di \$daftarSekolah hasil query renderTabTriwulan()!\n";
    echo "Daftar 5 terakhir yg ADA:\n";
    $daftarSekolah->slice(-5)->each(fn ($s) => print("id={$s->id} nama={$s->nama_sekolah}\n"));
}

echo "\n===== Cek auth()->user() & bolehKelolaSemua() =====\n";
echo "auth()->user()->id = " . auth()->user()->id . "\n";
echo "auth()->user()->level_akses = " . auth()->user()->level_akses . "\n";
$refMethod = new ReflectionMethod($component, 'bolehKelolaSemua');
$refMethod->setAccessible(true);
echo "bolehKelolaSemua() = " . var_export($refMethod->invoke($component), true) . "\n";
