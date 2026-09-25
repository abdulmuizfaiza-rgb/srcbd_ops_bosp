<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        {{-- dompdf: margin halaman diatur lewat @page, mengikuti pola
             formulir-bos-k7b.blade.php - $margin dikirim dari tombol
             "Cetak"/"Unduh PDF"/"Unduh Word" & panel "Setting Margin",
             pakai default kalau tidak dikirim. --}}
        @php
            $m = $margin ?? ['atas' => 3, 'kanan' => 2.5, 'bawah' => 2.5, 'kiri' => 2.5];
        @endphp
        @page { margin: {{ $m['atas'] }}cm {{ $m['kanan'] }}cm {{ $m['bawah'] }}cm {{ $m['kiri'] }}cm; }
        body { font-family: sans-serif; font-size: 10.5px; color: #000; }
    </style>
</head>
<body>
    @include('pdf.partials.surat-tpg-kop')
    @include('pdf.partials.surat-tpg-rekomendasi-isi', ['editable' => false])
</body>
</html>
