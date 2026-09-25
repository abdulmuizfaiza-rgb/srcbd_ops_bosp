<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        {{-- dompdf: CSS sederhana, meniru layout gambar contoh "FORMULIR
             BOS K7c - BERITA ACARA PEMERIKSAAN KAS" yang diupload user
             2026-09-23 sedekat mungkin. --}}
        {{-- Perbaikan 2026-09-23 (round ketiga): margin halaman diatur
             lewat @page (default sesuai jawaban AskUserQuestion: Left 2.5cm,
             Right 2.5cm, Top 3cm, Bottom 2.5cm) - $margin dikirim dari tombol
             "Cetak"/"PDF" & pengaturan "Setting Margin", pakai default kalau
             tidak dikirim (mis. dipanggil dari tempat lain / test lama). --}}
        @php
            $m = $margin ?? ['atas' => 3, 'kanan' => 2.5, 'bawah' => 2.5, 'kiri' => 2.5];
        @endphp
        @page { margin: {{ $m['atas'] }}cm {{ $m['kanan'] }}cm {{ $m['bawah'] }}cm {{ $m['kiri'] }}cm; }
        body { font-family: sans-serif; font-size: 10px; color: #1e293b; line-height: 1.5; }
        .kotak-referensi { float: right; border: 1px solid #1e293b; padding: 4px 10px; font-weight: bold; font-size: 10px; }
        .clear { clear: both; }
        .judul { text-align: center; font-weight: bold; font-size: 13px; margin: 4px 0 2px; }
        .sub-judul { text-align: center; font-weight: bold; font-size: 11px; margin-bottom: 14px; }
        p.narasi { text-align: justify; text-indent: 34px; margin: 10px 0; }
        table.identitas { border-collapse: collapse; margin: 6px 0 6px 34px; }
        table.identitas td { padding: 1px 4px; vertical-align: top; }
        table.identitas td.label { width: 60px; }
        table.identitas td.titik-dua { width: 12px; }
        {{-- Perbaikan 2026-09-23 (round keempat): lebar kolom rincian diubah
             supaya huruf+label+titik-dua totalnya TEPAT 50% - persis sama
             dengan titik mulai kolom Kepala Sekolah di tabel tanda tangan
             (kolom ke-4 dari 6 kolom, lihat catatan di tabel tanda tangan)
             - supaya kolom "Rp" sungguh-sungguh sejajar dengan tanda
             tangan Kepala Sekolah. Sebelumnya total hanya 60% (huruf 4% +
             label 54% + titik-dua 2%). --}}
        table.rincian { width: 100%; border-collapse: collapse; margin: 10px 0; table-layout: fixed; }
        table.rincian td { padding: 2px 4px; }
        table.rincian td.huruf { width: 4%; }
        table.rincian td.label { width: 42%; }
        table.rincian td.titik-dua { width: 4%; }
        table.rincian td.rp-label { width: 12%; }
        table.rincian td.rp-nilai { text-align: right; width: 38%; }
        tr.tebal td { font-weight: bold; }
        tr.garis-atas td.rp-label, tr.garis-atas td.rp-nilai { border-top: 1px solid #1e293b; padding-top: 3px; }
        table.ttd { width: 100%; border-collapse: collapse; margin-top: 26px; }
        table.ttd td { padding: 2px 4px; vertical-align: top; }
        table.ttd col.spacer, table.ttd col.bendahara, table.ttd col.kepsek { width: 16.6667%; }
        {{-- Perbaikan 2026-09-23 (round kedelapan): sama seperti
             formulir-bos-k7b.blade.php - dompdf tidak konsisten menghormati
             `height` pada <tr> kosong tanpa konten, jadi `height` dipindah
             ke <td> (bukan cuma <tr>) DITAMBAH `line-height` yang sama +
             tiap <td> kosong diisi `&nbsp;` (lihat markup tabel ttd di
             bawah) supaya ruang kosong tanda tangan benar-benar 20px x 4
             baris di PDF, sama persis dengan tampilan di aplikasi. --}}
        .ruang-ttd td { height: 20px; line-height: 20px; }
        .nama-ttd { font-weight: bold; text-decoration: underline; }
        {{-- Perbaikan 2026-09-23 (round kelima): teks tanda tangan Bendahara
             & Kepala Sekolah rata TENGAH ("posisi di tengah-tengah"),
             sebelumnya rata kiri (round keempat). --}}
        .tengah { text-align: center; }
    </style>
</head>
<body>
    <div class="kotak-referensi">FORMULIR BOS K7c</div>
    <div class="clear"></div>

    <div class="judul">BERITA ACARA PEMERIKSAAN KAS</div>
    <div class="sub-judul">PRIODE : {{ $labelBulan }} {{ $tahun }}</div>

    <p class="narasi">
        Pada hari ini {{ $narasiTanggalK7c }} yang bertanda tangan di bawah ini, Saya Kepala Sekolah yang
        ditunjuk berdasarkan Surat Keputusan Nomor : {{ $noSkKepalaSekolah ?: '_______________' }}
        tanggal {{ $tanggalSkKepalaSekolah ? $tanggalSkKepalaSekolah->translatedFormat('d F Y') : '_______________' }}.
    </p>

    <table class="identitas">
        <tr><td class="label">Nama</td><td class="titik-dua">:</td><td>{{ $sekolah->nama_kepala_sekolah }}</td></tr>
        <tr><td class="label">Jabatan</td><td class="titik-dua">:</td><td>Kepala Sekolah</td></tr>
    </table>

    <p class="narasi" style="text-indent:0; margin-left:34px;">Melakukan pemeriksaan KAS kepada :</p>

    <table class="identitas">
        <tr><td class="label">Nama</td><td class="titik-dua">:</td><td>{{ $sekolah->nama_bendahara }}</td></tr>
        <tr><td class="label">Jabatan</td><td class="titik-dua">:</td><td>Bendahara BOS / Pemegang KAS</td></tr>
    </table>

    <p class="narasi">
        Yang berdasarkan Surat Keputusan Nomor : {{ $noSkBendahara ?: '_______________' }}
        tanggal {{ $tanggalSkBendahara ? $tanggalSkBendahara->translatedFormat('d F Y') : '_______________' }}
        ditugaskan dengan pengurusan uang BOSP. Berdasarkan pemeriksaan kas serta bukti-bukti dalam
        pengurusan itu, kami menemui kenyataan sebagai berikut :
    </p>

    <p style="margin: 12px 0 4px;">Jumlah uang yang dihitung dihadapan Bendahara/ Pemegang Kas adalah :</p>

    <table class="rincian">
        <tr>
            <td class="huruf">a</td>
            <td class="label">Saldo KAS (Uang kertas dan uang logam)</td>
            <td class="titik-dua">:</td>
            <td class="rp-label">Rp</td>
            <td class="rp-nilai">{{ number_format($saldoKasTunai, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="huruf">b</td>
            <td class="label">Saldo Bank</td>
            <td class="titik-dua">:</td>
            <td class="rp-label">Rp</td>
            <td class="rp-nilai">{{ number_format((int) ($formulir->saldo_rekening_bank ?? 0), 0, ',', '.') }}</td>
        </tr>
        <tr class="tebal garis-atas">
            <td colspan="2">Jumlah</td>
            <td class="titik-dua">:</td>
            <td class="rp-label">Rp</td>
            <td class="rp-nilai">{{ number_format($jumlahB, 0, ',', '.') }}</td>
        </tr>
        <tr><td colspan="5" style="height:8px;"></td></tr>
        <tr>
            <td colspan="2">Saldo menurut Buku Kas Umum (BKU)</td>
            <td class="titik-dua">:</td>
            <td class="rp-label">Rp</td>
            <td class="rp-nilai">{{ number_format($saldoBku, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="2">Perbedaan Antara Saldo KAS dan Kas Umum</td>
            <td class="titik-dua">:</td>
            <td class="rp-label">Rp</td>
            <td class="rp-nilai">{{ number_format($perbedaan, 0, ',', '.') }}</td>
        </tr>
    </table>

    {{-- Perbaikan 2026-09-23 (round kelima): tanda tangan Bendahara &
         Kepala Sekolah SEBELUMNYA rata KIRI di kolomnya masing-masing
         (round keempat - supaya sejajar persis dengan kolom "Rp" di
         tabel rincian). Sekarang diubah jadi rata TENGAH (class="tengah")
         sesuai permintaan baru. Kolom Kepala Sekolah SENDIRI tidak
         dipindah (tetap kolom ke-4, sama seperti round ketiga) -
         Bendahara mulai kolom ke-2 (colspan 2), tidak berubah dari round
         ketiga. Ruang tanda tangan diperbesar lagi dari 3 baris kosong
         menjadi 4 baris kosong (`.ruang-ttd` @ 20px x 4 baris). --}}
    <table class="ttd">
        <colgroup>
            <col class="spacer"><col class="bendahara"><col class="bendahara">
            <col class="kepsek"><col class="kepsek"><col class="kepsek">
        </colgroup>
        <tr>
            <td></td>
            <td colspan="2" class="tengah">Bendahara / Pemegang KAS</td>
            <td colspan="3" class="tengah">Kepala Sekolah</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="2"></td>
            <td colspan="3" class="tengah">{{ $sekolah->nama_sekolah }}</td>
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
