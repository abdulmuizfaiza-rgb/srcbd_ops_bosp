<x-mail::message>
# Password Baru Akun Anda

Halo {{ $user->display_name }} ({{ $user->level_akses_label }}),

Password akun Anda di **{{ config('app.name') }}** baru saja di-reset oleh Superadmin. Berikut password baru (sementara) untuk login:

<x-mail::panel>
Username: **{{ $user->username }}**<br>
Password baru: **{{ $passwordBaru }}**
</x-mail::panel>

Setelah berhasil login memakai password baru di atas, Anda akan diminta untuk langsung mengganti password tersebut dengan password pilihan Anda sendiri sebelum bisa membuka menu lain.

Kalau Anda tidak merasa meminta reset password ini, segera hubungi Superadmin.

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
