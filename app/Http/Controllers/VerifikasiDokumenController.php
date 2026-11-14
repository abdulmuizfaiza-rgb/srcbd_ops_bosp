<?php

namespace App\Http\Controllers;

use App\Models\VerifikasiDokumenQr;
use Illuminate\View\View;

/**
 * Halaman verifikasi publik (TANPA login) untuk dokumen PDF yang diunduh
 * dalam mode "Dengan Tanda Tangan & Cap berupa QR Code" (Fase 2 fitur
 * Tanda Tangan Kepala Sekolah & Cap Sekolah, permintaan user 2026-11-14).
 * Saat QR Code di dokumen di-scan, halaman ini mencari baris
 * verifikasi_dokumen_qr berdasarkan {uuid} dan menampilkan info dasarnya
 * (nama sekolah, jenis dokumen, nama Kepala Sekolah, tahun, tanggal
 * diterbitkan, status "Dokumen Sah/Terverifikasi") - kalau uuid tidak
 * ditemukan, tampilkan status "tidak dikenali" (BUKAN 404 polos, supaya
 * pesannya jelas bagi orang awam yang scan QR dari kertas fisik).
 */
class VerifikasiDokumenController extends Controller
{
    public function show(string $uuid): View
    {
        $dokumen = VerifikasiDokumenQr::query()->where('uuid', $uuid)->first();

        return view('verifikasi-dokumen.show', ['dokumen' => $dokumen]);
    }
}
