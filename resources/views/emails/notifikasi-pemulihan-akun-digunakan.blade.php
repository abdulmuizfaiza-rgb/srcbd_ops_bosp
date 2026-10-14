<x-mail::message>
# Peringatan Keamanan

Halo {{ $user->display_name }} ({{ $user->level_akses_label }}),

Akun Anda di **{{ config('app.name') }}** baru saja berhasil masuk (login) memakai **Pemulihan Akun** (bukan password biasa), pada:

<x-mail::panel>
Waktu: {{ $waktu->translatedFormat('d F Y, H:i') }} WIB<br>
Alamat IP: {{ $ipAddress ?: 'tidak terdeteksi' }}
</x-mail::panel>

Setelah ini, 2FA (Google Authenticator) akun Anda otomatis di-reset (wajib aktivasi/scan barcode ulang), dan kata sandi pemulihan yang baru saja dipakai sudah TIDAK BERLAKU LAGI (sekali pakai) - Anda perlu membuat yang baru lagi lewat menu Profil kalau sewaktu-waktu membutuhkannya.

**Kalau ini memang Anda** yang lupa password, tidak perlu melakukan apa pun lagi - cukup lanjutkan login seperti biasa (ganti password & aktivasi ulang Authenticator).

**Kalau ini BUKAN Anda**, segera lakukan ini SEKARANG:
1. Login & segera ganti password akun ini ke yang baru.
2. Aktivasi ulang Google Authenticator dari HP Anda sendiri.
3. Amankan akun email ini ({{ $user->email }}) - kemungkinan ada pihak lain yang bisa membacanya.
4. Hubungi Superadmin lain (kalau ada) untuk memeriksa aktivitas akun ini lebih lanjut.

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
