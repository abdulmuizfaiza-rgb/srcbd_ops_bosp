<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        {{-- dompdf: margin halaman diatur lewat @page, mengikuti pola
             pdf/surat-tpg-lembar-validasi.blade.php. Tidak ada "size"
             eksplisit di sini - orientasi landscape diatur lewat
             setPaper() di App\Livewire\PendataanOps\SuratTpg\Index::
             exportPdf() & App\Http\Controllers\SuratTpgCetakController. --}}
        @php
            $m = $margin ?? ['atas' => 3, 'kanan' => 2.5, 'bawah' => 2.5, 'kiri' => 2.5];
        @endphp
        @page { margin: {{ $m['atas'] }}cm {{ $m['kanan'] }}cm {{ $m['bawah'] }}cm {{ $m['kiri'] }}cm; }
        body { font-family: sans-serif; font-size: 10.5px; color: #000; }
    </style>
</head>
<body>
    @include('pdf.partials.surat-tpg-kop')
    @include('pdf.partials.surat-tpg-rekap-cair-isi', ['editable' => false])
</body>
</html>
