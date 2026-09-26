<x-mail::message>
# Token Verifikasi Akses

Halo {{ $user->display_name }} ({{ $user->level_akses_label }}),

Berikut token verifikasi akses untuk membuka halaman login **{{ config('app.name') }}**:

<x-mail::panel>
<div style="font-size:28px; letter-spacing:6px; text-align:center; font-weight:bold;">{{ $token }}</div>
</x-mail::panel>

Token ini berlaku selama **15 menit**. Jangan bagikan token ini ke siapa pun.

Kalau Anda tidak merasa meminta token ini, abaikan saja email ini.

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
