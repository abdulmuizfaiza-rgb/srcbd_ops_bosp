<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Dokumen — {{ config('app.name', 'Aplikasi') }}</title>
    @vite(['resources/css/app.css'])
</head>
{{--
    Halaman verifikasi publik (TANPA login) untuk dokumen PDF yang
    diunduh dalam mode "Dengan Tanda Tangan & Cap berupa QR Code" (Fase 2
    fitur Tanda Tangan Kepala Sekolah & Cap Sekolah, permintaan user
    2026-11-14). Dibuat sebagai halaman MANDIRI (bukan pakai
    layouts.guest yang berat/berhias untuk form login) karena ini cuma
    menampilkan hasil verifikasi singkat, sering dibuka dari HP setelah
    scan QR Code di kertas fisik - desain SENGAJA ringan & sederhana.

    $dokumen: App\Models\VerifikasiDokumenQr|null (null = uuid tidak
    ditemukan di database, dianggap QR tidak valid/dokumen tidak
    dikenal).
--}}
<body class="font-sans antialiased bg-slate-100 min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg ring-1 ring-slate-200 overflow-hidden">
        @if ($dokumen)
            <div class="bg-emerald-600 px-6 py-5 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-white/20 flex items-center justify-center mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                    </svg>
                </div>
                <p class="text-white font-semibold text-lg">Dokumen Sah &amp; Terverifikasi</p>
            </div>
            <div class="px-6 py-5 space-y-3 text-sm">
                <div>
                    <p class="text-slate-400">Jenis Dokumen</p>
                    <p class="text-slate-800 font-medium">{{ $dokumen->jenis_dokumen }}</p>
                </div>
                <div>
                    <p class="text-slate-400">Nama Sekolah</p>
                    <p class="text-slate-800 font-medium">{{ $dokumen->nama_sekolah }}</p>
                </div>
                @if ($dokumen->nama_kepala_sekolah)
                    <div>
                        <p class="text-slate-400">Ditandatangani oleh (Kepala Sekolah)</p>
                        <p class="text-slate-800 font-medium">{{ $dokumen->nama_kepala_sekolah }}</p>
                    </div>
                @endif
                @if ($dokumen->tahun)
                    <div>
                        <p class="text-slate-400">Tahun Anggaran</p>
                        <p class="text-slate-800 font-medium">{{ $dokumen->tahun }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-slate-400">Tanggal Diterbitkan</p>
                    <p class="text-slate-800 font-medium">{{ $dokumen->created_at->translatedFormat('d F Y, H:i') }} WIB</p>
                </div>
            </div>
        @else
            <div class="bg-red-600 px-6 py-5 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-white/20 flex items-center justify-center mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <p class="text-white font-semibold text-lg">QR Code Tidak Dikenali</p>
            </div>
            <div class="px-6 py-5 text-sm text-slate-600">
                Kode ini tidak ditemukan di sistem kami. Dokumen mungkin tidak berasal dari {{ config('app.name', 'aplikasi ini') }}, atau tautannya tidak lengkap/rusak.
            </div>
        @endif
        <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 text-center text-xs text-slate-400">
            Halaman verifikasi resmi {{ config('app.name', 'Aplikasi') }}
        </div>
    </div>
</body>
</html>
