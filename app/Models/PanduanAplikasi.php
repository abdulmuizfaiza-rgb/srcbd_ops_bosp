<?php

namespace App\Models;

use Database\Factories\PanduanAplikasiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Menu "Panduan Aplikasi" (round 24, poin 6) - daftar Book Manual/panduan
 * penggunaan aplikasi, dikelola HANYA oleh Superadmin (Gate
 * 'akses-panduan-aplikasi' di App\Providers\AppServiceProvider).
 *
 * Round DUA PULUH LIMA (2026-09-24), poin 1: SATU Judul Book Manual
 * sekarang boleh punya BANYAK file (files()) & BANYAK link Google Drive
 * (links()) sekaligus - lihat docblock migration
 * restructure_panduan_aplikasi_untuk_multi_file_dan_link utk keputusan
 * bisnis lengkap (jawaban AskUserQuestion). `link_drive`/`file_path` dst
 * yang dulu ada LANGSUNG di tabel ini (round 24) SUDAH DIPINDAH ke tabel
 * anak `panduan_aplikasi_file`/`panduan_aplikasi_link` oleh migration
 * tersebut - method ukuranFileManusiawi() ikut pindah ke
 * App\Models\PanduanAplikasiFile (dihitung PER FILE, bukan per Judul lagi;
 * total ukuran gabungan dihitung di App\Livewire\PanduanAplikasi\Index).
 *
 * Validasi "minimal 1 file ATAU 1 link utk keseluruhan Judul" TETAP
 * dilakukan di level Livewire (App\Livewire\PanduanAplikasi\Index), BUKAN
 * constraint database, persis seperti round 24 - hanya sekarang dihitung
 * dari TOTAL files()->count() + links()->count(), bukan dari 2 kolom
 * tunggal.
 */
#[Fillable(['judul', 'deskripsi'])]
class PanduanAplikasi extends Model
{
    /** @use HasFactory<PanduanAplikasiFactory> */
    use HasFactory;

    protected $table = 'panduan_aplikasi';

    /**
     * Ekstensi file yang diizinkan diupload - permintaan user eksplisit
     * "berupa file PDF, ZIP, RAR, DOC/DOCX, XLS/XLSX, PNG, JPG, HTML".
     * "jpg" ditambah pasangannya "jpeg" (format file yang sama, ekstensi
     * berbeda) supaya tidak menjebak user yang filenya kebetulan
     * berekstensi .jpeg - perluasan teknis kecil, bukan aturan bisnis
     * baru.
     *
     * @var list<string>
     */
    public const EKSTENSI_DIIZINKAN = ['pdf', 'zip', 'rar', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'html'];

    /** Ukuran maksimal file yang diupload dalam KILOBYTE (dipakai aturan validasi Livewire "max:") - "maksimal 20 mb" permintaan user. */
    public const UKURAN_MAKS_KB = 20 * 1024;

    /** @return HasMany<PanduanAplikasiFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(PanduanAplikasiFile::class);
    }

    /** @return HasMany<PanduanAplikasiLink, $this> */
    public function links(): HasMany
    {
        return $this->hasMany(PanduanAplikasiLink::class);
    }

    /** Ukuran gabungan SEMUA file di bawah Judul ini dlm bentuk yang mudah dibaca manusia ("1.2 MB" dst) untuk kolom "Ukuran" pada tabel - null kalau Judul ini tidak punya file sama sekali (hanya link). */
    public function ukuranTotalManusiawi(): ?string
    {
        $total = $this->files->sum('file_ukuran');

        return $total > 0 ? PanduanAplikasiFile::formatUkuran((int) $total) : null;
    }
}
