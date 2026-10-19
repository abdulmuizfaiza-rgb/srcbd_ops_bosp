<?php

namespace App\Imports;

use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Import data induk Profil Sekolah (NPSN, Nama Sekolah, Status,
 * Kecamatan) dari Excel/CSV untuk pendataan awal oleh Superadmin.
 *
 * Kolom yang diharapkan (sesuai hasil export): NPSN, Nama Sekolah,
 * Status, Kecamatan.
 *
 * Jika NPSN pada suatu baris SUDAH ADA di database, baris tersebut
 * DILEWATI (tidak menimbulkan error, tidak mengubah data sekolah yang
 * sudah ada) - hanya baris dengan NPSN baru yang akan menambah data
 * sekolah baru. Jumlah baris yang berhasil ditambah/dilewati bisa dibaca
 * lewat properti $jumlahDibuat/$jumlahDilewati setelah import selesai.
 */
/**
 * SkipsEmptyRows (2026-10-16, perbaikan bug "Baris 11 wajib diisi" yang
 * dilaporkan user): file hasil "Export Excel" template tsb (lihat Export
 * berpasangan) sengaja menulis dropdown Data Validation ratusan baris di
 * BAWAH data asli (buffer kosong supaya admin OPS bisa menambah baris
 * baru sebelum diimport ulang) - akibatnya Excel mencatat baris-baris
 * buffer itu sebagai "terpakai" walau TIDAK ADA isinya sama sekali.
 * Tanpa SkipsEmptyRows, Laravel Excel ikut memvalidasi baris-baris
 * kosong tsb sebagai baris data sungguhan (SEMUA field wajib dianggap
 * kosong) - gagal pada baris PERTAMA setelah data asli & membatalkan
 * sisa proses import (krn tidak WithBatchInserts, validasi per-baris
 * langsung melempar exception begitu 1 baris gagal). SkipsEmptyRows
 * membuat baris yang BENAR-BENAR kosong (tanpa isi sama sekali)
 * dilewati otomatis, tidak dianggap baris data yang gagal.
 */
class ProfilSekolahImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    use Importable;

    /**
     * SEJAK 2026-10-18, ProfilSekolahExport menulis baris keterangan
     * (kolom kuning) di baris 1 & menggeser baris header tabel yang
     * sebenarnya ke baris 2 (lihat ProfilSekolahExport::
     * BARIS_HEADER_TABEL/startCell()) - TANPA perbaikan ini,
     * WithHeadingRow (default membaca baris 1 sebagai header) salah
     * membaca baris keterangan sebagai header, sehingga baris 2
     * (header tabel asli: "NPSN", "Nama Sekolah", dst) malah dibaca
     * sebagai BARIS DATA PERTAMA & gagal validasi - bug yang sama
     * persis dengan DataPtkImport (dilaporkan user 2026-10-19 utk
     * Data PTK, pola import sama jadi ikut diperbaiki di sini
     * sekaligus supaya tidak menyusul jadi laporan terpisah).
     * headingRow() di bawah mengarahkan WithHeadingRow ke baris header
     * tabel yang benar.
     */
    public function headingRow(): int
    {
        return 2;
    }

    public int $jumlahDibuat = 0;

    public int $jumlahDilewati = 0;

    public function model(array $row): Model|array|null
    {
        $npsn = trim((string) ($row['npsn'] ?? ''));

        if (ProfilSekolah::where('npsn', $npsn)->exists()) {
            $this->jumlahDilewati++;

            return null;
        }

        $labelKeStatus = array_flip(ProfilSekolah::statusOptions());
        $status = trim((string) ($row['status'] ?? ''));

        $this->jumlahDibuat++;

        return new ProfilSekolah([
            'npsn' => $npsn,
            'nama_sekolah' => trim((string) ($row['nama_sekolah'] ?? '')),
            'status' => $labelKeStatus[$status] ?? null,
            'kecamatan' => trim((string) ($row['kecamatan'] ?? '')),
        ]);
    }

    public function rules(): array
    {
        return [
            // NPSN sengaja divalidasi dengan regex (bukan 'string'/'max')
            // karena kalau sel Excel-nya berformat angka biasa (bukan
            // Text), Maatwebsite Excel akan membaca nilainya sebagai
            // int/float, bukan string - sama seperti NRG/NUPTK/NPWP pada
            // import Lampiran 2a/2b/2c.
            'npsn' => ['required', 'regex:/^[0-9]{1,20}$/'],
            'nama_sekolah' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_values(ProfilSekolah::statusOptions()))],
            'kecamatan' => ['required', 'string', 'max:255'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'npsn.required' => 'NPSN wajib diisi.',
            'npsn.regex' => 'NPSN harus berupa angka, maksimal 20 digit.',
            'nama_sekolah.required' => 'Nama Sekolah wajib diisi.',
            'status.in' => 'Status harus dipilih dari dropdown yang tersedia (Negeri/Swasta).',
            'kecamatan.required' => 'Kecamatan wajib diisi.',
        ];
    }
}
