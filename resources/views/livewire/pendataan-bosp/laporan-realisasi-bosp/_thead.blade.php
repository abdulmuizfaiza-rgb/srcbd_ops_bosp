{{--
    Header tabel wide Laporan Realisasi BOSP (Form BPK) - 29 kolom sesuai
    gambar contoh, dikelompokkan: REALISASI DANA BOS PUSAT > Belanja
    Barang dan Jasa (9 item + Total) / Belanja Modal (2 item + Total),
    lalu "Keterangan Sisa Dana BOS" (kolom 26-27) & "Verifikasi" (kolom
    28-29, HASIL RUMUS). Dipakai HANYA pada tab TW1-4 (tab "rekap" punya
    tabel per-sekolah sendiri, lihat index.blade.php).

    Urutan kolom di header ini HARUS SAMA PERSIS dengan urutan
    App\Models\LaporanRealisasiBosp::FIELD_MANUAL (dipakai tbody untuk
    me-render kotak input satu-per-satu lewat @foreach) - lihat kolom 8-27
    di migration create_laporan_realisasi_bosp_table untuk urutan resminya.

    Kolom 11 (Belanja Barang Pakai Habis/Persediaan), kolom 12 (Jasa
    Tenaga Pendidik dan Kependidikan), kolom 13 (Daya dan Jasa), kolom 14
    (Pemeliharaan), kolom 15 (Upah Pemeliharaan), kolom 16 (Biaya
    Pendaftaran Lomba/Bimtek/Workshop), kolom 17 (Honor Kegiatan), kolom
    18 (Makan dan Minum Kegiatan), & kolom 19 (Perjalanan Dinas) diberi
    warna kuning (bg-yellow-50) SAMA seperti kolom 28-29 - sejak
    permintaan user 2026-09-17 (lanjutan Part 32, lanjutan Part 32
    kedua, lanjutan Part 32 ketiga, & lanjutan Part 32 keempat) seluruh
    9 kolom di grup "Belanja Barang dan Jasa" ini sudah READ-ONLY,
    diambil otomatis dari menu sumber masing-masing (lihat
    App\Models\LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN).

    Lanjutan permintaan user 2026-09-17 (ronde ke-5): kolom 20 (Total
    Belanja Barang dan Jasa), kolom 21 (Peralatan dan Mesin KIB B),
    kolom 22 (Aset Tetap Lainnya KIB E), & kolom 23 (Total Belanja
    Modal) JUGA sudah READ-ONLY (ikut bg-yellow-50) - kolom 20 = rumus
    gabungan (SUM kolom 12-19, kolom 11 SENGAJA TIDAK diikutkan di
    kolom 20, lihat catatan lengkap di Model), kolom 21 & 22 diambil
    dari total_harga tab Belanja Modal KIB B/KIB E per triwulan, kolom
    23 = kolom 21 + kolom 22. Kolom 24 (Total Realisasi Dana BOS) =
    kolom 11 + kolom 20 + kolom 23, & kolom 25 (Sisa Dana BOS) = kolom
    10 (Total Penerimaan, tetap manual) - kolom 24 - keduanya juga
    sudah READ-ONLY sejak ronde ini (lihat FIELD_KOMPUTASI_RINCIAN).

    Lanjutan permintaan user 2026-09-17 (ronde ke-6): kolom 26 (Saldo
    Rekening/Kas Bank) & kolom 27 (Saldo Kas Tunai) JUGA sudah READ-ONLY
    (ikut bg-yellow-50, sebelumnya bg-sky-50/manual) - diambil OTOMATIS
    dari App\Models\DanaBospTahap tab "Tarik Tunai BOSP" (lihat
    FIELD_KOMPUTASI_RINCIAN). Grup header "Keterangan Sisa Dana BOS" itu
    sendiri TETAP bg-sky-50 (label kelompok, bukan kolom data) - pola
    sama seperti grup "REALISASI DANA BOS PUSAT"/"Belanja Barang dan
    Jasa"/"Belanja Modal" yang tetap bg-emerald-50 walau seluruh kolom
    di dalamnya sudah bg-yellow-50.

    Keterangan kecil "(otomatis dari ...)"/"(otomatis: ...)" di bawah
    tiap judul kolom DIHAPUS sejak permintaan user 2026-09-17 (ronde
    ke-6, poin 2) - dianggap mengganggu (terutama yang isinya duplikat
    dengan judul kolom sendiri). Rincian sumber/rumus tiap kolom TETAP
    didokumentasikan lengkap di alert banner keterangan collapsible
    pada index.blade.php (poin 1), bukan lagi di header tabel ini.

    Lanjutan permintaan user 2026-09-17 (ronde ke-7): kolom 8 (Saldo
    Awal Dana BOSP), kolom 9 (Penerimaan Dana BOS), & kolom 10 (Total
    Penerimaan) JUGA sudah READ-ONLY (ikut bg-yellow-50) - INI ADALAH
    KOLOM MANUAL TERAKHIR di menu ini, sehingga SELURUH tabel Laporan
    Realisasi BOSP sudah otomatis/read-only sejak ronde ini (lihat
    App\Models\LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN). Label
    kolom 8 diubah dari "Saldo Awal Dana BOSP Tahun Anggaran {{ tahun -
    1 }}" menjadi "Saldo Awal Dana BOSP" (poin 3, suffix tahun dihapus
    karena kolom ini sekarang berlaku utk 4 triwulan tahun berjalan,
    bukan cuma saldo carry-over dari tahun sebelumnya).
--}}
<thead class="sticky top-0 z-10 bg-blue-50">
    <tr class="text-center text-slate-700">
        <th rowspan="3" class="px-2 py-2 align-middle border-2 border-blue-300">No</th>
        <th rowspan="3" class="px-2 py-2 align-middle border-2 border-blue-300">Kode UPB</th>
        <th rowspan="3" class="px-2 py-2 align-middle border-2 border-blue-300">NPSN</th>
        <th rowspan="3" class="px-2 py-2 align-middle border-2 border-blue-300">Nama Sekolah</th>
        <th rowspan="3" class="px-2 py-2 align-middle border-2 border-blue-300">Kecamatan</th>
        <th rowspan="3" class="px-2 py-2 align-middle border-2 border-blue-300">Subrayon</th>
        <th rowspan="3" class="px-2 py-2 align-middle border-2 border-blue-300">Triwulan</th>
        <th rowspan="3" class="px-2 py-2 align-middle border-2 border-blue-300 bg-yellow-50 whitespace-nowrap">Saldo Awal Dana BOSP</th>
        <th rowspan="3" class="px-2 py-2 align-middle border-2 border-blue-300 bg-yellow-50 whitespace-nowrap">Penerimaan Dana BOS</th>
        <th rowspan="3" class="px-2 py-2 align-middle border-2 border-blue-300 bg-yellow-50 whitespace-nowrap">Total Penerimaan</th>
        <th colspan="13" class="px-2 py-2 border-2 border-blue-300 bg-emerald-50 font-bold">REALISASI DANA BOS PUSAT</th>
        <th rowspan="3" class="px-2 py-2 align-middle border-2 border-blue-300 bg-yellow-50 whitespace-nowrap">Total Realisasi Dana BOS</th>
        <th rowspan="3" class="px-2 py-2 align-middle border-2 border-blue-300 bg-yellow-50 whitespace-nowrap">Sisa Dana BOS</th>
        <th colspan="2" class="px-2 py-2 border-2 border-blue-300 bg-sky-50 font-bold whitespace-nowrap">Keterangan Sisa Dana BOS</th>
        <th colspan="2" class="px-2 py-2 border-2 border-blue-300 bg-yellow-100 font-bold">Verifikasi</th>
    </tr>
    <tr class="text-center text-slate-700">
        <th colspan="10" class="px-2 py-2 border-2 border-blue-300 bg-emerald-50 font-bold">Belanja Barang dan Jasa</th>
        <th colspan="3" class="px-2 py-2 border-2 border-blue-300 bg-emerald-50 font-bold">Belanja Modal</th>
        <th rowspan="2" class="px-2 py-2 align-middle border-2 border-blue-300 bg-yellow-50 whitespace-nowrap">Saldo Rekening/Kas Bank</th>
        <th rowspan="2" class="px-2 py-2 align-middle border-2 border-blue-300 bg-yellow-50 whitespace-nowrap">Saldo Kas Tunai</th>
        <th rowspan="2" class="px-2 py-2 align-middle border-2 border-blue-300 bg-yellow-100 whitespace-nowrap">Jumlah</th>
        <th rowspan="2" class="px-2 py-2 align-middle border-2 border-blue-300 bg-yellow-100 whitespace-nowrap">Verifikasi Saldo</th>
    </tr>
    <tr class="text-center text-slate-700">
        <th class="px-2 py-2 border-2 border-blue-300 bg-yellow-50 whitespace-nowrap">Belanja Barang Pakai Habis/Persediaan</th>
        <th class="px-2 py-2 border-2 border-blue-300 bg-yellow-50 whitespace-nowrap">Jasa Tenaga Pendidik dan Kependidikan</th>
        <th class="px-2 py-2 border-2 border-blue-300 bg-yellow-50 whitespace-nowrap">Daya dan Jasa</th>
        <th class="px-2 py-2 border-2 border-blue-300 bg-yellow-50 whitespace-nowrap">Pemeliharaan</th>
        <th class="px-2 py-2 border-2 border-blue-300 bg-yellow-50 whitespace-nowrap">Upah Pemeliharaan</th>
        <th class="px-2 py-2 border-2 border-blue-300 bg-yellow-50 whitespace-nowrap">Biaya Pendaftaran Lomba/Bimtek/Workshop</th>
        <th class="px-2 py-2 border-2 border-blue-300 bg-yellow-50 whitespace-nowrap">Honor Kegiatan</th>
        <th class="px-2 py-2 border-2 border-blue-300 bg-yellow-50 whitespace-nowrap">Makan dan Minum Kegiatan</th>
        <th class="px-2 py-2 border-2 border-blue-300 bg-yellow-50 whitespace-nowrap">Perjalanan Dinas</th>
        <th class="px-2 py-2 border-2 border-blue-300 bg-yellow-50 font-bold whitespace-nowrap">Total Belanja Barang dan Jasa</th>
        <th class="px-2 py-2 border-2 border-blue-300 bg-yellow-50 whitespace-nowrap">Peralatan dan Mesin KIB B</th>
        <th class="px-2 py-2 border-2 border-blue-300 bg-yellow-50 whitespace-nowrap">Aset Tetap Lainnya KIB E</th>
        <th class="px-2 py-2 border-2 border-blue-300 bg-yellow-50 font-bold whitespace-nowrap">Total Belanja Modal</th>
    </tr>
</thead>
