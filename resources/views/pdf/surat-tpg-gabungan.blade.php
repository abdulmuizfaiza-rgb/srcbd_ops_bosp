<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        {{--
            "Cetak Semua"/"Unduh PDF Semua" (round kedua puluh satu,
            permintaan user "surat rekomendasi, Surat Penghentian TPG dan
            Surat Pernyataan di buat dalam 1 halaman supaya lebih irit
            kertas") - ketiga surat digabung dalam SATU file PDF/1 kali
            proses cetak, BERURUTAN (Rekomendasi, Penghentian, lalu
            Pernyataan), masing2 tetap 1 halaman PENUH sendiri dipisah
            page-break (BUKAN dipepetkan jadi benar2 1 lembar fisik - lihat
            docblock lengkap App\Livewire\PendataanOps\SuratTpg\Index &
            progress-log terkait interpretasi "1 halaman" ini).

            Data per-jenis surat ($rekomendasi/$penghentian/$pernyataan,
            masing2 array ['nomorSurat'=>,'tanggalSurat'=>,'tahunPelajaran'=>])
            disiapkan oleh App\Support\SuratTpgGabunganData::ambil() - data
            yang SAMA (Nama/NIP Kepsek, Unit Kerja, Alamat Kantor, Nama/NIP
            Pengawas) dipakai bersama ketiganya lewat variabel di scope
            utama ($namaKepsek dst) yang otomatis terbawa ke tiap @include
            di bawah. Kop Surat ($kopSuratSrc) HANYA dipakai eksplisit
            pada halaman Rekomendasi & Penghentian (round kedua puluh dua:
            "hapus kop surat nya" pada tab Pernyataan).

            DIPINDAHKAN round kedua puluh dua (2026-09-24) dari toolbar
            menu "Format Surat Rekomendasi & Pembatalan TPG" KE menu
            Unduhan (permintaan user poin 3: "untuk cetak Gabungan ketiga
            surat (irit kertas) di pindah ke menu Unduhan berdasarkan
            triwulan dan tahun") - lihat
            App\Livewire\PendataanOps\Unduhan\Index &
            App\Http\Controllers\SuratTpgCetakSemuaController. View ini
            sendiri (struktur & isi PDF gabungan) TIDAK berubah, hanya
            SUMBER pemanggilannya yang pindah tempat.
        --}}
        @php
            $m = $margin ?? ['atas' => 3, 'kanan' => 2.5, 'bawah' => 2.5, 'kiri' => 2.5];
        @endphp
        @page { margin: {{ $m['atas'] }}cm {{ $m['kanan'] }}cm {{ $m['bawah'] }}cm {{ $m['kiri'] }}cm; }
        body { font-family: sans-serif; font-size: 10.5px; color: #000; }
        .surat-tpg-gabungan-halaman { page-break-after: always; }
        .surat-tpg-gabungan-halaman:last-child { page-break-after: auto; }
    </style>
</head>
<body>
    <div class="surat-tpg-gabungan-halaman">
        @include('pdf.partials.surat-tpg-kop')
        @include('pdf.partials.surat-tpg-rekomendasi-isi', array_merge(['editable' => false], $rekomendasi))
    </div>
    <div class="surat-tpg-gabungan-halaman">
        @include('pdf.partials.surat-tpg-kop')
        @include('pdf.partials.surat-tpg-penghentian-isi', array_merge(['editable' => false], $penghentian))
    </div>
    <div class="surat-tpg-gabungan-halaman">
        {{-- Kop Surat SENGAJA tidak disertakan utk bagian Pernyataan ini
             (round kedua puluh dua, permintaan user "hapus kop surat nya"
             KHUSUS tab Pernyataan - tab Rekomendasi & Penghentian di atas
             TETAP menampilkan kop surat). --}}
        @include('pdf.partials.surat-tpg-pernyataan-isi', array_merge(['editable' => false], $pernyataan))
    </div>
</body>
</html>
