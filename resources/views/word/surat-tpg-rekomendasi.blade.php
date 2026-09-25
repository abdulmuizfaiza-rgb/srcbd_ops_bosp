{{--
    "Unduh Word" (jawaban AskUserQuestion 2026-09-24: HTML disimpan
    dengan ekstensi .doc, Content-Type application/msword - BUKAN docx
    biner sungguhan - supaya tidak perlu menambah dependency Composer
    baru phpoffice/phpword yang mengharuskan user menjalankan `composer
    install` sendiri di komputernya). MS Word bisa membuka & mengedit
    file HTML semacam ini secara langsung berkat namespace xmlns:o/xmlns:w
    di bawah - SENGAJA TIDAK memakai prolog `<?xml ?>`/`<?mso-application?>`
    (itu format WordProcessingML 2003 yang berbeda & lebih ketat) supaya
    Word membukanya sebagai "Web Page, Filtered" HTML biasa, bukan
    memicu galat format.

    Margin halaman Word diatur lewat @page bergaya mso (Word membaca
    properti mso-page-* pada @page), mengikuti nilai margin yang sama
    dgn PDF/preview layar.
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
        @include('pdf.partials.surat-tpg-kop')
        @include('pdf.partials.surat-tpg-rekomendasi-isi', ['editable' => false])
    </div>
</body>
</html>
