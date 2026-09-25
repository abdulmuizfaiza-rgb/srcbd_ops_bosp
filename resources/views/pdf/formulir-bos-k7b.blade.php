<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        {{-- dompdf: CSS sederhana, meniru layout gambar contoh "FORMULIR
             BOS-K7b - REGISTER PENUTUPAN KAS" yang diupload user
             2026-09-23 sedekat mungkin (kotak referensi pojok kanan atas,
             blok header D/K/A, rincian pecahan uang, blok tanda tangan). --}}
        {{-- Perbaikan 2026-09-23 (round ketiga): margin halaman diatur
             lewat @page (default sesuai jawaban AskUserQuestion: Left 2.5cm,
             Right 2.5cm, Top 3cm, Bottom 2.5cm) - $margin dikirim dari tombol
             "Cetak"/"PDF" & pengaturan "Setting Margin", pakai default kalau
             tidak dikirim (mis. dipanggil dari tempat lain / test lama). --}}
        @php
            $m = $margin ?? ['atas' => 3, 'kanan' => 2.5, 'bawah' => 2.5, 'kiri' => 2.5];
        @endphp
        @page { margin: {{ $m['atas'] }}cm {{ $m['kanan'] }}cm {{ $m['bawah'] }}cm {{ $m['kiri'] }}cm; }
        body { font-family: sans-serif; font-size: 10px; color: #1e293b; }
        .kotak-referensi { float: right; border: 1px solid #1e293b; padding: 4px 10px; font-weight: bold; font-size: 10px; }
        .clear { clear: both; }
        .judul { text-align: center; font-weight: bold; font-size: 13px; margin: 14px 0 16px; }
        table.header-info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.header-info td { padding: 1.5px 2px; vertical-align: top; }
        table.header-info td.label { width: 46%; }
        table.header-info td.titik-dua { width: 2%; }
        table.header-info td.nilai { text-align: left; }
        .tebal { font-weight: bold; }
        .garis-bawah { border-bottom: 1px solid #1e293b; display: inline-block; min-width: 140px; text-align: right; padding-bottom: 1px; }
        table.pecahan { width: 100%; border-collapse: collapse; margin: 4px 0 10px; }
        table.pecahan td { padding: 1.5px 3px; }
        table.pecahan td.nominal { width: 12%; }
        table.pecahan td.rp-label { width: 6%; }
        table.pecahan td.jumlah { width: 16%; }
        table.pecahan td.rp-nilai { text-align: right; width: 22%; border-bottom: 1px solid #1e293b; }
        tr.sub-jumlah td { font-weight: bold; padding-top: 4px; }
        .baris-3 td.rp-nilai, .baris-b td.rp-nilai, .baris-perbedaan td.rp-nilai { border-bottom: 1px solid #1e293b; }
        .kotak-penjelasan { border: 1px solid #1e293b; padding: 5px; min-height: 32px; margin-top: 4px; }
        table.ttd { width: 100%; border-collapse: collapse; margin-top: 24px; }
        table.ttd td { padding: 2px 4px; vertical-align: top; }
        table.ttd col.spacer, table.ttd col.bendahara, table.ttd col.kepsek { width: 16.6667%; }
        {{-- Perbaikan 2026-09-23 (round kedelapan): laporan user - ruang
             kosong tanda tangan pada hasil unduh/cetak PDF TIDAK sama
             persis dengan yang tampil di aplikasi (kotak preview di layar
             pakai Tailwind `h-5` = 20px PADA <td>, sedangkan PDF ini
             sebelumnya cuma set `height: 20px` pada <tr> tanpa isi apapun
             di dalam <td>-nya). dompdf TIDAK konsisten menghormati
             `height` pada <tr> kosong tanpa konten - baris jadi menciut
             jauh lebih pendek dari 20px yang diminta (terbukti lewat
             render PDF sungguhan & diukur). Diperbaiki dengan cara SAMA
             seperti trik baku dompdf: `height` DIPINDAH ke <td> (bukan
             cuma <tr>) DITAMBAH `line-height` yang sama, DAN tiap <td>
             kosong diisi `&nbsp;` (lihat markup tabel ttd di bawah) supaya
             dompdf punya "konten" nyata untuk dialokasikan tingginya -
             sekarang benar-benar 20px x 4 baris, PERSIS sama dengan
             tampilan di aplikasi. --}}
        .ruang-ttd td { height: 20px; line-height: 20px; }
        .nama-ttd { font-weight: bold; text-decoration: underline; }
        {{-- Perbaikan 2026-09-23 (round kelima): teks tanda tangan Bendahara
             & Kepala Sekolah rata TENGAH ("posisi di tengah-tengah"),
             sebelumnya rata kiri (round keempat). --}}
        .tengah { text-align: center; }
    </style>
</head>
<body>
    <div class="kotak-referensi">FORMULIR BOS-K7b</div>
    <div class="clear"></div>

    <div class="judul">REGISTER PENUTUPAN KAS</div>

    <table class="header-info">
        <tr>
            <td class="label">Tanggal Penutupan Kas Bulan ini</td>
            <td class="titik-dua">:</td>
            <td class="nilai">{{ $tanggalPenutupan->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Nama Penutup KAS (Pemegang KAS)</td>
            <td class="titik-dua">:</td>
            <td class="nilai">{{ $sekolah->nama_bendahara }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Penutupan KAS Bulan Lalu</td>
            <td class="titik-dua">:</td>
            <td class="nilai">{{ $tanggalPenutupanLalu->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah Total Penerimaan BKU (D)</td>
            <td class="titik-dua">:</td>
            <td class="nilai">Rp. {{ number_format((int) ($formulir->jumlah_total_penerimaan_bku ?? 0), 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah Total Pengeluaran BKU (K)</td>
            <td class="titik-dua">:</td>
            <td class="nilai">Rp. {{ number_format((int) ($formulir->jumlah_total_pengeluaran_bku ?? 0), 0, ',', '.') }}</td>
        </tr>
        <tr class="tebal">
            <td class="label">A.&nbsp;&nbsp;Saldo Buku Kas Umum (A=D-K)</td>
            <td class="titik-dua">:</td>
            <td class="nilai">Rp. {{ number_format($saldoBku, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Saldo Kas Tunai</td>
            <td class="titik-dua">:</td>
            <td class="nilai"><span class="garis-bawah">Rp. {{ number_format($saldoKasTunai, 0, ',', '.') }}</span></td>
        </tr>
    </table>

    <table class="pecahan">
        <tr><td colspan="5" class="tebal">1.&nbsp;&nbsp;Lembaran uang kertas</td></tr>
        @foreach (\App\Models\FormulirBosK7::NOMINAL_UANG_KERTAS as $nominal)
            @php $jumlahLembar = (int) ($formulir->{\App\Models\FormulirBosK7::fieldLembar($nominal)} ?? 0); @endphp
            <tr>
                <td>Lembaran uang kertas</td>
                <td class="nominal">Rp {{ number_format($nominal, 0, ',', '.') }}</td>
                <td class="jumlah">{{ $jumlahLembar }} Lembar</td>
                <td class="rp-label">Rp.</td>
                <td class="rp-nilai">{{ number_format($nominal * $jumlahLembar, 0, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr class="sub-jumlah">
            <td colspan="3">Sub Jumlah Lembar uang kertas (1)</td>
            <td class="rp-label">Rp.</td>
            <td class="rp-nilai">{{ number_format($subJumlahKertas, 0, ',', '.') }}</td>
        </tr>

        <tr><td colspan="5" style="height:6px;"></td></tr>
        <tr><td colspan="5" class="tebal">2.&nbsp;&nbsp;Keping uang logam</td></tr>
        @foreach (\App\Models\FormulirBosK7::NOMINAL_UANG_LOGAM as $nominal)
            @php $jumlahKeping = (int) ($formulir->{\App\Models\FormulirBosK7::fieldKeping($nominal)} ?? 0); @endphp
            <tr>
                <td>Keping uang logam</td>
                <td class="nominal">Rp {{ number_format($nominal, 0, ',', '.') }}</td>
                <td class="jumlah">{{ $jumlahKeping }} Keping</td>
                <td class="rp-label">Rp.</td>
                <td class="rp-nilai">{{ number_format($nominal * $jumlahKeping, 0, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr class="sub-jumlah">
            <td colspan="3">Sub Jumlah Keping uang logam (2)</td>
            <td class="rp-label">Rp.</td>
            <td class="rp-nilai">{{ number_format($subJumlahLogam, 0, ',', '.') }}</td>
        </tr>

        <tr><td colspan="5" style="height:6px;"></td></tr>
        <tr class="baris-3">
            <td colspan="3">3.&nbsp;&nbsp;Saldo Rekening Bank &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Sub Jumlah (3)</td>
            <td class="rp-label">Rp.</td>
            <td class="rp-nilai">{{ number_format((int) ($formulir->saldo_rekening_bank ?? 0), 0, ',', '.') }}</td>
        </tr>

        <tr><td colspan="5" style="height:6px;"></td></tr>
        <tr class="sub-jumlah baris-b">
            <td colspan="3">B.&nbsp;&nbsp;Jumlah (1+2+3)</td>
            <td class="rp-label">Rp.</td>
            <td class="rp-nilai">{{ number_format($jumlahB, 0, ',', '.') }}</td>
        </tr>

        <tr><td colspan="5" style="height:8px;"></td></tr>
        <tr class="baris-perbedaan">
            <td colspan="3">Perbedaan (A-B)</td>
            <td class="rp-label">Rp.</td>
            <td class="rp-nilai">{{ number_format($perbedaan, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div>Penjelasan Perbedaan</div>
    <div class="kotak-penjelasan">{{ $penjelasanPerbedaan ?: '-' }}</div>

    {{-- Perbaikan 2026-09-23 (round kelima): tanda tangan Bendahara &
         Kepala Sekolah SEBELUMNYA rata KIRI di kolomnya masing-masing
         (round keempat - supaya sejajar persis dengan baris "Tanggal").
         Sekarang diubah jadi rata TENGAH (class="tengah") sesuai
         permintaan baru, termasuk baris "Tanggal" (supaya keduanya tetap
         sama-sama di tengah kolomnya, tetap "sejajar" satu sama lain -
         cuma titik acuannya sekarang tengah kolom, bukan kiri kolom).
         Kolom Kepala Sekolah SENDIRI tidak dipindah (tetap kolom ke-4,
         sama seperti round ketiga) - Bendahara mulai kolom ke-2 (colspan
         2), tidak berubah dari round ketiga. Ruang tanda tangan
         diperbesar lagi dari 3 baris kosong menjadi 4 baris kosong
         (`.ruang-ttd` @ 20px x 4 baris). --}}
    <table class="ttd">
        <colgroup>
            <col class="spacer"><col class="bendahara"><col class="bendahara">
            <col class="kepsek"><col class="kepsek"><col class="kepsek">
        </colgroup>
        <tr>
            <td></td>
            <td colspan="2"></td>
            <td colspan="3" class="tengah">Tanggal, {{ $tanggalPenutupan->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="2" class="tengah">Yang diperiksa,</td>
            <td colspan="3" class="tengah">Yang Memeriksa,</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="2" class="tengah">Bendahara</td>
            <td colspan="3" class="tengah">Kepala Sekolah<br>{{ $sekolah->nama_sekolah }}</td>
        </tr>
        <tr class="ruang-ttd"><td>&nbsp;</td><td colspan="2">&nbsp;</td><td colspan="3">&nbsp;</td></tr>
        <tr class="ruang-ttd"><td>&nbsp;</td><td colspan="2">&nbsp;</td><td colspan="3">&nbsp;</td></tr>
        <tr class="ruang-ttd"><td>&nbsp;</td><td colspan="2">&nbsp;</td><td colspan="3">&nbsp;</td></tr>
        <tr class="ruang-ttd"><td>&nbsp;</td><td colspan="2">&nbsp;</td><td colspan="3">&nbsp;</td></tr>
        <tr>
            <td></td>
            <td colspan="2" class="nama-ttd tengah">{{ $sekolah->nama_bendahara }}</td>
            <td colspan="3" class="nama-ttd tengah">{{ $sekolah->nama_kepala_sekolah }}</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="2" class="tengah">NIP. {{ $sekolah->nip_bendahara }}</td>
            <td colspan="3" class="tengah">NIP. {{ $sekolah->nip_kepala_sekolah }}</td>
        </tr>
    </table>
</body>
</html>
