<x-mail::message>
# Barcode Google Authenticator Baru

Halo {{ $user->display_name }} ({{ $user->level_akses_label }}),

Authenticator akun Anda di **{{ config('app.name') }}** baru saja di-reset oleh Superadmin. Untuk bisa login kembali, aktifkan ulang Google Authenticator dengan barcode baru di bawah ini.

<x-mail::panel>
<div style="text-align:center;">
<img src="{{ $svgBarcodeDataUri }}" alt="Barcode Google Authenticator" width="200" height="200" style="width:200px;height:200px;">
</div>
<div style="text-align:center; margin-top:8px;">
Tidak bisa scan? Masukkan kunci manual ini di aplikasi Authenticator:<br>
<span style="font-family:monospace; letter-spacing:2px; font-weight:bold;">{{ $kunciManual }}</span>
</div>
</x-mail::panel>

Buka aplikasi **Google Authenticator** di HP Anda, pilih "Scan barcode", lalu arahkan kamera ke gambar di atas. Setelah itu, login seperti biasa di {{ config('app.name') }} dan masukkan kode 6 digit yang muncul untuk menyelesaikan aktivasi.

Kalau Anda tidak merasa meminta reset Authenticator ini, segera hubungi Superadmin.

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
