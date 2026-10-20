<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom `keterangan` pada tabel `data_ptk` - permintaan user
 * 2026-10-20 poin 5: "pada menu PTK tambahkan field Keterangan yang
 * sifatnya wajib di input secara manual apabila inputan field Status
 * Keaktifan pilihannya Tidak Aktif. apabila pilihannya Aktif maka field
 * keterangan boleh dikosongkan." Posisi kolom setelah `status_keaktifan`
 * (field terakhir pada tabel ini) - dikonfirmasi lewat AskUserQuestion
 * ("Tepat setelah Status Keaktifan").
 *
 * Nullable (wajib/tidaknya DITERAPKAN lewat validasi
 * 'required_if:status_keaktifan,Tidak Aktif' di App\Livewire\
 * ProfilSekolah\Index::simpanPtk() & App\Imports\DataPtkImport, BUKAN
 * lewat NOT NULL di skema database - konsisten dengan pola field lain
 * yang wajib-bersyarat di tabel ini, mis. field rincian sertifikasi
 * yang wajib hanya saat Status Sertifikasi = Sudah).
 *
 * Dipakai juga sebagai sumber otomatis field "Keterangan" pada
 * Lampiran 2b (permintaan user 2026-10-20 poin 6) - lihat
 * App\Livewire\PendataanOps\Lampiran2b\Index::isiOtomatisNrgNuptk().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_ptk', function (Blueprint $table) {
            $table->string('keterangan')->nullable()->after('status_keaktifan');
        });
    }

    public function down(): void
    {
        Schema::table('data_ptk', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
};
