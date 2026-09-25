{{--
    "Unduh Word Semua" - lihat catatan lengkap di pdf/surat-tpg-gabungan.blade.php,
    pola sama persis (page-break antar surat via CSS, data per-jenis dari
    App\Support\SuratTpgGabunganData::ambil()), format HTML-sebagai-.doc
    yang sama seperti word/surat-tpg-rekomendasi.blade.php. Kop Surat
    SENGAJA tidak disertakan pada bagian Pernyataan (round kedua puluh
    dua). Fitur ini sendiri DIPINDAHKAN ke menu Unduhan round kedua puluh
    dua - lihat App\Livewire\PendataanOps\Unduhan\Index.
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
        .surat-tpg-gabungan-halaman { page-break-after: always; }
        .surat-tpg-gabungan-halaman:last-child { page-break-after: auto; }
    </style>
</head>
<body>
    <div class="Section1">
        <div class="surat-tpg-gabungan-halaman">
            @include('pdf.partials.surat-tpg-kop')
            @include('pdf.partials.surat-tpg-rekomendasi-isi', array_merge(['editable' => false], $rekomendasi))
        </div>
        <div class="surat-tpg-gabungan-halaman">
            @include('pdf.partials.surat-tpg-kop')
            @include('pdf.partials.surat-tpg-penghentian-isi', array_merge(['editable' => false], $penghentian))
        </div>
        <div class="surat-tpg-gabungan-halaman">
            @include('pdf.partials.surat-tpg-pernyataan-isi', array_merge(['editable' => false], $pernyataan))
        </div>
    </div>
</body>
</html>
