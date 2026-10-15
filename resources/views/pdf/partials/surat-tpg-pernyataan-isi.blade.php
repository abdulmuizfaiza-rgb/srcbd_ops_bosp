{{--
    Isi Surat Pernyataan (Tab 3, BARU round kedua puluh satu, 2026-09-24) -
    format sesuai gambar contoh yang diupload user ("dengan format field
    seperti gambar yang saya upload").

    Dipakai bersama (SATU partial) di 3 tempat: preview layar (Livewire,
    $editable=true), PDF (dompdf, $editable=false), Word/.doc
    ($editable=false) - sama seperti 2 partial isi surat lainnya.

    Field Nama/Jabatan/Unit Kerja/Alamat Kantor SELALU diambil otomatis.

    UPDATE 2026-10-15 (permintaan user eksplisit): identitas penanda
    tangan pada tab ini DIGANTI dari Kepala Sekolah menjadi Pengawas
    Pembina - Nama <- ProfilSekolah::nama_pengawas (sebelumnya
    nama_kepala_sekolah), Jabatan SELALU teks tetap "Pengawas Pembina"
    (sebelumnya "Kepala Sekolah"), Unit Kerja SELALU teks tetap "Dinas
    Pendidikan Kabupaten Sukabumi" (sebelumnya <- nama_sekolah, data per
    sekolah), Alamat Kantor SELALU teks tetap "Jl. Pelabuhan II Km. 22
    Cikembang-Cikembar Kab. Sukabumi" (sebelumnya <- alamat_sekolah, data
    per sekolah) - Unit Kerja & Alamat Kantor SEKARANG teks tetap (bukan
    lagi data per sekolah) krn ini alamat kantor Dinas, sama utk semua
    pengawas/sekolah. $namaPengawas SUDAH tersedia di partial ini tanpa
    perlu ubah controller/komponen apa pun - variabel ini sudah dikirim
    dari App\Http\Controllers\SuratTpgCetakController,
    App\Livewire\PendataanOps\SuratTpg\Index, &
    App\Support\SuratTpgGabunganData sejak sebelumnya (dipakai partial
    pdf.partials.surat-tpg-ttd untuk blok tanda tangan Pengawas pada tab
    Rekomendasi/Penghentian) - TIDAK ADA perubahan di file manapun selain
    partial ini. Tab Rekomendasi & Penghentian (surat-tpg-rekomendasi-isi,
    surat-tpg-penghentian-isi) TIDAK ikut berubah - identitas Kepala
    Sekolah di kedua tab itu TETAP seperti semula, permintaan user HANYA
    untuk tab Surat Pernyataan ini.

    Tahun Pelajaran ("2025/2026" dst) diketik MANUAL oleh Admin OPS &
    tersimpan otomatis ($tahunPelajaran, App\Models\SuratTpg::tahun_pelajaran) -
    permintaan user eksplisit. Triwulan & Tahun (Anggaran) OTOMATIS dari
    filter tahun/triwulan yang sama dgn 2 tab lain - dipakai di sini
    dengan App\Models\SuratTpg::labelTriwulanRomawi() ("Triwulan II" tanpa
    "(dua)"), BUKAN labelTriwulan(), krn gambar contoh menuliskan polos
    "Triwulan II" tanpa kata dalam kurung.

    Perbaikan 2026-09-24 (round kedua puluh dua, permintaan user poin 1 &
    2):
    - SEMUA kotak bergaris hitam (border:1px solid #000) yang round
      kedua puluh satu sengaja dipertahankan (meniru gambar contoh)
      SEKARANG DIHAPUS - "hilangkan garis nya sama seperti tab surat
      lainnya" - sekarang polos tanpa kotak, konsisten dgn
      surat-tpg-rekomendasi-isi.blade.php & surat-tpg-penghentian-isi.blade.php.
    - Isi surat memakai line-height 1.5 - "spasi buat isi surat nya
      menjadi 1,5" (pola sama dgn tab Surat Rekomendasi TPG round kedua
      puluh, lihat surat-tpg-rekomendasi-isi.blade.php).
    - Tulisan "Materai" digeser ke bawah, posisi tengah (center), tepat
      SEBELUM baris nama Kepala Sekolah - sebelumnya berupa kotak
      berlabel "Materai" di atas ruang kosong tanda tangan, sekarang
      urutannya: ruang kosong tanda tangan dulu, baru "Materai"
      (center), baru Nama+NIP.
    - Kop Surat TIDAK LAGI disertakan pada tab ini (permintaan user
      "hapus kop surat nya") - @include('pdf.partials.surat-tpg-kop')
      dihapus dari SEMUA tempat yang merender tab ini (wrapper PDF/Word
      tunggal & bagian Pernyataan pada dokumen gabungan) - lihat
      resources/views/pdf/surat-tpg-pernyataan.blade.php,
      resources/views/word/surat-tpg-pernyataan.blade.php,
      resources/views/pdf/surat-tpg-gabungan.blade.php,
      resources/views/word/surat-tpg-gabungan.blade.php, &
      resources/views/livewire/pendataan-ops/surat-tpg/index.blade.php.
      TIDAK berlaku utk tab Rekomendasi/Penghentian (kop surat tetap
      tampil di kedua tab itu).

    Blok tanda tangan (single kolom) - Nama & NIP SAMA persis dgn di
    identitas atas, SEKARANG Nama & NIP Pengawas (lihat UPDATE 2026-10-15
    di atas) - sebelumnya (s.d. 2026-09-24): "untuk tanda tangan Kepala
    Sekolah dan NIp kepala Sekolah diambil dari field Nama Kepala Sekolah
    dan NIP kepala Sekolah".

    Perbaikan 2026-09-24 (round kedua puluh tiga, poin 1): "Materai"
    diberi ruang kosong DI ATAS *dan* DI BAWAH tulisannya (sebelumnya
    hanya di atas) supaya posisinya di tengah blok tanda tangan, bukan
    menempel di bawah dekat Nama.

    Perbaikan 2026-09-24 (round kedua puluh empat, poin 1): Nama & NIP
    Kepala Sekolah digeser turun 3 baris LAGI dari posisi round 23 (ruang
    kosong sesudah "Materai" ditambah dari 40px -> 106px, +/- 3 baris
    teks 11pt/line-height 1.5), & tulisan "Materai" itu sendiri digeser
    ke KIRI 2 langkah (margin-left:-40px KHUSUS pada baris "Materai",
    menetralkan margin-left:40px pada div pembungkus blok tanda tangan -
    baris tanggal/Nama/NIP lain TIDAK ikut bergeser, hanya "Materai").

    Variabel yang diharapkan:
    - $editable (bool)
    - $tahunPelajaran (string|null)
    - $tanggalSurat (\Illuminate\Support\Carbon|null)
    - $triwulan (int), $tahun (int)
    - $namaPengawas, $nipPengawas (string|null) - sejak 2026-10-15, dipakai
      utk identitas atas & blok tanda tangan (lihat UPDATE di atas)
    - $namaKepsek, $nipKepsek, $namaSekolah, $alamatSekolah (string|null) -
      TIDAK LAGI dipakai di partial ini sejak 2026-10-15, dibiarkan di
      sini krn variabel ini tetap dikirim oleh pemanggil (dipakai tab
      Rekomendasi/Penghentian lain), bukan error kalau tidak terpakai
--}}
@php($editable = $editable ?? false)
@php($romawiTriwulan = \App\Models\SuratTpg::labelTriwulanRomawi($triwulan))

<div style="text-align:center;margin-bottom:10px;">
    <span style="font-weight:bold;font-size:14pt;text-decoration:underline;">SURAT PERNYATAAN</span>
</div>

<table style="width:100%;border-collapse:collapse;font-size:11pt;line-height:1.5;margin-bottom:8px;">
    <tr>
        <td style="padding:2px 6px 2px 0;width:150px;vertical-align:top;">Yang bertanda tangan dibawah ini</td>
        <td style="padding:2px 6px;width:14px;vertical-align:top;">:</td>
        <td style="padding:2px 0;"></td>
    </tr>
    <tr>
        <td style="padding:2px 6px 2px 20px;vertical-align:top;">Nama</td>
        <td style="padding:2px 6px;vertical-align:top;">:</td>
        <td style="padding:2px 0;vertical-align:top;">{{ $namaPengawas ?: '............................' }}</td>
    </tr>
    <tr>
        <td style="padding:2px 6px 2px 20px;vertical-align:top;">Jabatan</td>
        <td style="padding:2px 6px;vertical-align:top;">:</td>
        <td style="padding:2px 0;vertical-align:top;">Pengawas Pembina</td>
    </tr>
    <tr>
        <td style="padding:2px 6px 2px 20px;vertical-align:top;">Unit Kerja</td>
        <td style="padding:2px 6px;vertical-align:top;">:</td>
        <td style="padding:2px 0;vertical-align:top;">Dinas Pendidikan Kabupaten Sukabumi</td>
    </tr>
    <tr>
        <td style="padding:2px 6px 2px 20px;vertical-align:top;">Alamat Kantor</td>
        <td style="padding:2px 6px;vertical-align:top;">:</td>
        <td style="padding:2px 0;vertical-align:top;">Jl. Pelabuhan II Km. 22 Cikembang-Cikembar Kab. Sukabumi</td>
    </tr>
</table>

<p style="font-size:11pt;line-height:1.5;margin:8px 0 4px;">
    Dengan ini menyatakan bahwa
</p>

<table style="width:100%;border-collapse:collapse;font-size:11pt;line-height:1.5;margin-bottom:8px;">
    <tr>
        <td style="padding:2px 6px 2px 20px;width:24px;vertical-align:top;">a.</td>
        <td style="padding:2px 0;text-align:justify;">
            Telah mendokumentasikan dengan baik seluruh instrumen hasil penilaian kinerja guru dan Kepala Sekolah Tahun Pelajaran
            @if ($editable)
                <input type="text" wire:model.live="tahunPelajaran" placeholder="2025/2026" class="border-0 border-b border-slate-400 bg-transparent text-sm focus:ring-0 focus:border-blue-500 px-1 py-0 text-left" style="display:inline-block;width:100px;">
            @else
                {{ $tahunPelajaran ?: '............' }}
            @endif
            {{ $romawiTriwulan }}
        </td>
    </tr>
    <tr>
        <td style="padding:2px 6px 2px 20px;vertical-align:top;">b.</td>
        <td style="padding:2px 0;text-align:justify;">Telah melakukan pengawasan dan memastikan terhadap kinerja operator sekolah dalam proses entri secara on line DAPODIK sesuai dengan data sebenarnya</td>
    </tr>
    <tr>
        <td style="padding:2px 6px 2px 20px;vertical-align:top;">c.</td>
        <td style="padding:2px 0;text-align:justify;">Telah melakukan verifikasi terhadap keabsahan dokumen calon penerima Tunjangan Profesi Guru (TPG)</td>
    </tr>
    <tr>
        <td style="padding:2px 6px 2px 20px;vertical-align:top;">d.</td>
        <td style="padding:2px 0;text-align:justify;">Telah melakukan verifikasi terhadap absensi bulanan calon penerima TPG</td>
    </tr>
    <tr>
        <td style="padding:2px 6px 2px 20px;vertical-align:top;">e.</td>
        <td style="padding:2px 0;text-align:justify;">Telah melakukan verifikasi terhadap data guru binaan yang masuk kategori penghentian TPG</td>
    </tr>
    <tr>
        <td style="padding:2px 6px 2px 20px;vertical-align:top;">f.</td>
        <td style="padding:2px 0;text-align:justify;">Telah mengumpulkan surat rekomendasi yang dibuat dan di tandatangani oleh kepala sekolah tentang usulan pembayaran TPG {{ $romawiTriwulan }} sesuai sekolah/guru binaan dengan dokumen berupa surat rekomendasi sebagaimana terlampir</td>
    </tr>
    <tr>
        <td style="padding:2px 6px 2px 20px;vertical-align:top;">g.</td>
        <td style="padding:2px 0;text-align:justify;">Telah mengirimkan dokumen lampiran yang tertera pada poin (e) dan (f) Kepala Dinas Pendidikan Kabupaten Sukabumi melalui bidang PTK.</td>
    </tr>
</table>

<p style="font-size:11pt;line-height:1.5;text-align:justify;margin:8px 0 16px;">
    Demikian surat pernyataan ini dibuat dengan sesungguhnya untuk dipergunakan sebagaimana mestinya dan bila terdapat kekeliruan dikemudian hari, saya bersedia menerima sanksi sesuai peraturan perundang-undangan yang berlaku.
</p>

<table style="width:100%;border-collapse:collapse;font-size:11pt;">
    <tr>
        <td style="width:55%;"></td>
        <td style="width:45%;vertical-align:top;">
            <div style="margin-left:40px;">
                Sukabumi,
                @if ($editable)
                    <input type="date" wire:model.live="tanggalSurat" class="border-0 border-b border-slate-400 bg-transparent text-sm focus:ring-0 focus:border-blue-500 px-1 py-0" style="display:inline-block;width:140px;">
                    <button type="button" wire:click="$set('tanggalSurat', null)" class="text-[11px] text-slate-500 hover:text-red-600 underline align-middle" style="display:inline-block;">Kosongkan</button>
                @else
                    {{ $tanggalSurat ? $tanggalSurat->translatedFormat('d F Y') : '..........................' }}.
                @endif
                <br>
                Yang membuat pernyataan
                <div style="height:28px;"></div>
                {{-- Round kedua puluh empat, poin 1: digeser ke kiri 2 langkah (margin-left:-40px, menetralkan margin-left:40px pada div pembungkus di atas, KHUSUS baris ini saja - baris tanggal/Nama/NIP lain tidak ikut bergeser). --}}
                <div style="text-align:center;margin-left:-40px;">Materai</div>
                {{-- Round kedua puluh empat, poin 1: Nama & NIP Kepala Sekolah digeser ke bawah 3 baris tambahan dari posisi round 23 (40px -> 106px, ekuivalen +/- 3 baris teks 11pt/line-height 1.5 = 3 x ~22px = 66px). --}}
                <div style="height:106px;"></div>
                {{ $namaPengawas ?: '............................' }}<br>
                NIP. {{ $nipPengawas ?: '............................' }}
            </div>
        </td>
    </tr>
</table>
