{{--
    Isi tab "Lembar Validasi TPG" (BARU 2026-10-15/16, permintaan user,
    format sesuai gambar contoh yang diupload: "Lembar Verifikasi
    Persyaratan Calon Penerima Tunjangan Profesi Guru Dan Kepala
    Sekolah") pada menu "Format Surat Rekomendasi & Pembatalan TPG" -
    dipakai bersama (SATU partial) di 3 tempat: preview layar (Livewire,
    $editable=true), PDF (dompdf, $editable=false), Word/.doc
    ($editable=false), pola sama persis dgn partial isi surat lainnya di
    menu ini.

    Variabel yang diharapkan (semua disediakan App\Livewire\PendataanOps\
    SuratTpg\Index::dataSurat() & App\Http\Controllers\SuratTpgCetakController):
    - $editable (bool)
    - $triwulan, $tahun (int)
    - $tanggalVerifikasi (Carbon|null) - field manual, tersimpan otomatis.
    - $catatanHasilVerifikasi (string|null) - field manual, tersimpan otomatis.
    - $daftarPtkValidasi (array<string nuptk, array{nama_ptk:string, ...8 boolean}>)
      - daftar PTK dari Lampiran 2a sekolah+tahun+triwulan aktif BESERTA
        ceklist 8 kriterianya - lihat App\Models\LembarValidasiTpgCeklist::KOLOM_CEKLIST
        utk daftar lengkap nama kolom booleannya.
    - $namaKasieKpml, $nipKasieKpml (string|null) - dari Profil Sekolah
      (field Nama/NIP Kasie KPML, permintaan user 2026-10-15 sebelumnya).
    - $namaPengawas, $nipPengawas, $namaKepsek, $nipKepsek, $namaSekolah (string|null).
    - $labelTpgStatus (string|null) - "TPGASN" (sekolah Negeri) atau
      "TPGNON-ASN" (Swasta), jawaban AskUserQuestion 2026-10-15:
      "Mengikuti status sekolah".

    Pada mode $editable (preview layar), checkbox ceklist terikat lewat
    wire:model.live="daftarPtk.{nuptk}.{kolom}" (properti ASLI pada
    komponen Livewire - BUKAN $daftarPtkValidasi yang dipakai di sini
    HANYA utk looping/tampilan, krn variabel itulah yang tersedia di
    KETIGA tempat tsb, sedangkan properti komponen hanya ada saat
    benar2 dirender Livewire) - ditangkap lewat App\Livewire\PendataanOps\
    SuratTpg\Index::updated() (hook generik, bukan method updated<Properti>()
    biasa, krn path-nya dinamis per NUPTK). Tombol "Centang Semua" per
    kolom (jawaban AskUserQuestion 2026-10-15: "1 tombol per kolom")
    HANYA tampil saat $editable (bukan bagian dari hasil cetak).
--}}
@php
    $editable = $editable ?? false;

    // Peta nama kolom ceklist -> label singkat (dipakai header tabel,
    // teks label mengikuti gambar contoh yang diupload user apa adanya
    // - termasuk kolom "Berstatus Guru ASN" yang SENGAJA tidak diubah
    // mengikuti konteks Non-ASN menu ini, krn kriteria ini justru
    // relevan dipakai utk MEMASTIKAN PTK ybs BUKAN berstatus ASN -
    // dibiarkan apa adanya sesuai permintaan user "tambahkan format
    // gambar ini").
    $labelKolom = [
        'memiliki_sertifikat_pendidik' => 'Memiliki sertifikat pendidik',
        'berstatus_guru_asn' => 'Berstatus Guru ASN',
        'aktif_melaksanakan_tugas_dapodik' => 'Aktif Melaksanakan tugas dan tercatat pada dapodik',
        'memiliki_nuptk_nrg' => 'Memiliki NUPTK & NRG',
        'memenuhi_beban_kerja' => 'Memenuhi beban kerja',
        'mengajar_linier_sertifikat' => 'Mengajar Linier sesuai dengan Sertifikat Pendidik',
        'bukan_pegawai_tetap_instansi_lain' => 'Bukan Pegawai Tetap Instansi lain',
        'berstatus_valid_info_gtk' => 'Berstatus valid dalam Info GTK',
    ];
@endphp

{{-- Baris judul + label status (TPGASN/TPGNON-ASN) - pakai table 2 kolom
     polos (tanpa border) supaya label bisa rata kanan sejajar judul,
     dompdf/Word tidak mendukung flexbox. --}}
<table style="width:100%;border-collapse:collapse;margin-bottom:10px;">
    <tr>
        <td style="vertical-align:top;text-align:center;">
            <div style="font-weight:bold;font-size:12pt;line-height:1.4;">
                Lembar Verifikasi Persyaratan Calon Penerima Tunjangan Profesi Guru Dan Kepala Sekolah
            </div>
            <div style="font-size:10.5pt;margin-top:4px;">
                Tanggal Verifikasi dan Validasi :
                @if ($editable)
                    <input type="date" wire:model.live="tanggalVerifikasi" class="border-0 border-b border-slate-400 bg-transparent text-sm focus:ring-0 focus:border-blue-500 px-1 py-0" style="display:inline-block;width:150px;">
                @else
                    {{ $tanggalVerifikasi ? $tanggalVerifikasi->translatedFormat('d F Y') : '..........................' }}
                @endif
            </div>
        </td>
        <td style="width:90px;vertical-align:top;text-align:right;">
            @if ($labelTpgStatus)
                <span style="display:inline-block;background:#fde047;border:1px solid #ca8a04;border-radius:4px;padding:3px 10px;font-weight:bold;font-size:10pt;">
                    {{ $labelTpgStatus }}
                </span>
            @endif
        </td>
    </tr>
</table>

{{-- Tabel daftar PTK + ceklist 8 kriteria --}}
{{-- <thead>/<tbody> (BARU 2026-10-16, permintaan user: halaman
     landscape + otomatis nambah halaman kalau data PTK banyak) - dompdf
     mengulang otomatis isi <thead> di setiap halaman saat <tbody>
     terpotong ke halaman berikutnya, supaya header kolom & tombol
     "Centang Semua" (mode layar) tetap terlihat di setiap halaman. --}}
<table style="width:100%;border-collapse:collapse;font-size:7pt;">
    <thead>
    <tr>
        <td style="border:1px solid #000;padding:3px;text-align:center;font-weight:bold;width:3%;">No</td>
        <td style="border:1px solid #000;padding:3px;text-align:center;font-weight:bold;width:16%;">Nama</td>
        @foreach ($labelKolom as $kolom => $label)
            <td style="border:1px solid #000;padding:3px;text-align:center;font-weight:bold;">
                {{ $label }}
                @if ($editable)
                    <br><button type="button" wire:click="centangSemuaKolom('{{ $kolom }}')" class="text-[8px] text-indigo-600 hover:text-indigo-800 underline">Centang Semua</button>
                @endif
            </td>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @forelse ($daftarPtkValidasi as $nuptk => $ptk)
        <tr>
            <td style="border:1px solid #000;padding:3px;text-align:center;">{{ $loop->iteration }}</td>
            <td style="border:1px solid #000;padding:3px;">{{ $ptk['nama_ptk'] }}</td>
            @foreach ($labelKolom as $kolom => $label)
                <td style="border:1px solid #000;padding:3px;text-align:center;">
                    @if ($editable)
                        <input type="checkbox" wire:model.live="daftarPtk.{{ $nuptk }}.{{ $kolom }}" @checked($ptk[$kolom])>
                    @else
                        {{ $ptk[$kolom] ? 'V' : '' }}
                    @endif
                </td>
            @endforeach
        </tr>
    @empty
        <tr>
            <td colspan="10" style="border:1px solid #000;padding:8px;text-align:center;color:#94a3b8;">
                Belum ada data PTK pada Lampiran 2a untuk Triwulan {{ $triwulan }} Tahun {{ $tahun }}.
            </td>
        </tr>
    @endforelse
    </tbody>
</table>

{{-- CATATAN HASIL VERIFIKASI + Blok Tanda Tangan (BARU 2026-10-16,
     koreksi ke-2 dari permintaan user: "garis dari isi Catatan sampai
     tanda tangan secara full" - dikonfirmasi lewat AskUserQuestion:
     "Seluruh blok Catatan+TTD dibingkai garis kotak penuh") - SEKARANG
     1 TABEL UTUH dgn garis kotak PENUH (border:1px solid #000) di
     SETIAP sel, meniru gaya grid pada gambar referensi Excel awal -
     BUKAN LAGI 2 blok terpisah (div Catatan + table Tanda Tangan)
     dengan garis parsial spt sebelumnya (horizontal 3-potong + vertikal
     kiri/tengah/kanan saja). Placeholder "4 baris titik-titik kosong"
     utk Catatan yg masih kosong (versi sebelumnya) DIHAPUS, diganti 1
     sel kosong bergaris kotak dgn tinggi cukup utk ditulis tangan -
     konsisten dgn gaya kotak baru ini. --}}
<table style="width:100%;border-collapse:collapse;margin-top:14px;font-size:10.5pt;">
    <tr>
        <td colspan="3" style="border:1px solid #000;padding:4px 6px;font-weight:bold;">CATATAN HASIL VERIFIKASI :</td>
    </tr>
    <tr>
        <td colspan="3" style="border:1px solid #000;padding:4px 6px;height:70px;vertical-align:top;">
            @if ($editable)
                <textarea wire:model.live="catatanHasilVerifikasi" rows="4" placeholder="(opsional)" class="block w-full border-0 bg-transparent text-sm focus:ring-0 px-0 py-0" style="resize:vertical;"></textarea>
            @else
                @if ($catatanHasilVerifikasi)
                    <div style="white-space:pre-line;">{{ $catatanHasilVerifikasi }}</div>
                @endif
            @endif
        </td>
    </tr>
    <tr>
        <td style="border:1px solid #000;padding:4px 6px;vertical-align:top;text-align:center;">Mengetahui / Menyetujui,</td>
        <td colspan="2" style="border:1px solid #000;padding:4px 6px;vertical-align:top;text-align:center;">VERIFIKATOR :</td>
    </tr>
    <tr>
        <td style="border:1px solid #000;padding:4px 6px;vertical-align:top;text-align:center;">Kepala Seksi Tata Kelola Kelembagaan dan Ketenagaan</td>
        <td style="width:33%;border:1px solid #000;padding:4px 6px;vertical-align:top;text-align:center;">Pengawas,</td>
        <td style="width:33%;border:1px solid #000;padding:4px 6px;vertical-align:top;text-align:center;">Kepala {{ $namaSekolah ?: '............................' }}</td>
    </tr>
    <tr>
        <td style="border:1px solid #000;height:56px;"></td>
        <td style="border:1px solid #000;height:56px;"></td>
        <td style="border:1px solid #000;height:56px;"></td>
    </tr>
    <tr>
        <td style="border:1px solid #000;padding:4px 6px;vertical-align:top;text-align:center;">
            {{ $namaKasieKpml ?: '............................' }}<br>
            NIP. {{ $nipKasieKpml ?: '............................' }}
        </td>
        <td style="border:1px solid #000;padding:4px 6px;vertical-align:top;text-align:center;">
            {{ $namaPengawas ?: '............................' }}<br>
            NIP. {{ $nipPengawas ?: '............................' }}
        </td>
        <td style="border:1px solid #000;padding:4px 6px;vertical-align:top;text-align:center;">
            {{ $namaKepsek ?: '............................' }}<br>
            NIP. {{ $nipKepsek ?: '............................' }}
        </td>
    </tr>
</table>
