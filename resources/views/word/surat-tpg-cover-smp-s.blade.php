{{--
    "Unduh Word" Cover SMP-S (BARU 2026-10-15) - HTML disimpan dengan
    ekstensi .doc & Content-Type application/msword, pola SAMA PERSIS
    dengan word/surat-tpg-rekomendasi.blade.php (lihat komentar lengkap
    di sana untuk penjelasan format).
--}}
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="utf-8">
    <meta name="ProgId" content="Word.Document">
    <!--[if gte mso 9]>
    <xml>
        <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>100</w:Zoom>
        </w:WordDocument>
    </xml>
    <![endif]-->
    <style>
        @php
            $m = $margin ?? ['atas' => 3, 'kanan' => 2.5, 'bawah' => 2.5, 'kiri' => 2.5];
        @endphp
        @page Section1 {
            size: 21cm 29.7cm;
            margin: {{ $m['atas'] }}cm {{ $m['kanan'] }}cm {{ $m['bawah'] }}cm {{ $m['kiri'] }}cm;
            mso-page-orientation: portrait;
        }
        div.Section1 { page: Section1; }
        body { font-family: 'Calibri', sans-serif; font-size: 11pt; color: #000; }
    </style>
</head>
<body>
    <div class="Section1">
        @include('pdf.partials.surat-tpg-cover-isi')
    </div>
</body>
</html>
