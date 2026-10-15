{{--
    Isi tab "Data Cair/Belum TPG" (BARU 2026-10-16, permintaan user,
    format sesuai gambar contoh "REKAPITULASI DATA PTK YANG SUDAH /
    BELUM CAIR TPG") pada menu "Format Surat Rekomendasi & Pembatalan
    TPG" - dipakai bersama (SATU partial) di 3 tempat: preview layar
    (Livewire, $editable=true), PDF (dompdf, $editable=false), Word/.doc
    ($editable=false), pola SAMA PERSIS dgn partial surat-tpg-lembar-
    validasi-isi.blade.php (termasuk blok Catatan Hasil Verifikasi +
    Tanda Tangan, "sama dengan konsep lembar validasi" - permintaan user
    verbatim).

    Variabel yang diharapkan (semua disediakan App\Livewire\PendataanOps\
    SuratTpg\Index::dataSurat() & App\Http\Controllers\SuratTpgCetakController):
    - $editable (bool)
    - $triwulan, $tahun (int)
    - $catatanCairTpg (string|null) - field manual, tersimpan otomatis.
      TIDAK ADA field "Tanggal Verifikasi" di tab ini (tidak ada pada
      gambar contoh, beda dgn tab Lembar Validasi TPG).
    - $daftarPtkCairTpg (array<string nuptk, array{nama_ptk:string, nip:string|null, ...12 string|null}>)
      - daftar PTK = GABUNGAN Lampiran 2a SEMUA Triwulan (1-4) dalam
        Tahun aktif (dikonfirmasi AskUserQuestion 2026-10-16 - BEDA dgn
        tab Lembar Validasi TPG yang hanya 1 triwulan), tapi nilai 12
        kolom bulannya tetap scoped ke Triwulan yang sedang difilter -
        lihat App\Models\RekapCairTpgBulan::KOLOM_BULAN & STATUS_OPTIONS
        utk daftar lengkap nama kolom & 3 pilihan nilainya. NIP diambil
        dari App\Models\DataPtk (dicocokkan lewat NUPTK).
    - $namaKasieKpml, $nipKasieKpml (string|null) - dari Profil Sekolah.
    - $namaPengawas, $nipPengawas, $namaKepsek, $nipKepsek, $namaSekolah (string|null).

    Pada mode $editable (preview layar), <select> per sel terikat lewat
    wire:model.live="daftarPtkCairTpg.{nuptk}.{kolom}" (properti ASLI
    pada komponen Livewire - BUKAN $daftarPtkCairTpg yang dipakai di
    sini HANYA utk looping/tampilan) - ditangkap lewat App\Livewire\
    PendataanOps\SuratTpg\Index::updated() (hook generik, pola sama dgn
    tab Lembar Validasi TPG, awalan path "daftarPtkCairTpg." BUKAN
    "daftarPtk." jadi tidak bentrok dgn tab itu).

    Kertas LANDSCAPE (dikonfirmasi AskUserQuestion 2026-10-16, krn 15
    kolom total - lihat App\Livewire\PendataanOps\SuratTpg\Index::
    exportPdf() & App\Http\Controllers\SuratTpgCetakController utk
    pengaturan orientasi; wrapper PDF/Word tab ini jg disesuaikan).
--}}
@php
    $editable = $editable ?? false;

    // Peta nama kolom bulan -> label singkat (header tabel) - URUT
    // Januari->Desember, ejaan "Pebruari"/"Nopember" mengikuti PERSIS
    // ejaan pada gambar contoh user (bukan typo).
    $labelBulan = [
        'bulan_januari' => 'Januari',
        'bulan_pebruari' => 'Pebruari',
        'bulan_maret' => 'Maret',
        'bulan_april' => 'April',
        'bulan_mei' => 'Mei',
        'bulan_juni' => 'Juni',
        'bulan_juli' => 'Juli',
        'bulan_agustus' => 'Agustus',
        'bulan_september' => 'September',
        'bulan_oktober' => 'Oktober',
        'bulan_nopember' => 'Nopember',
        'bulan_desember' => 'Desember',
    ];

    $opsiStatus = \App\Models\RekapCairTpgBulan::STATUS_OPTIONS;
@endphp

<div style="text-align:center;font-weight:bold;font-size:12pt;margin-bottom:10px;">
    REKAPITULASI DATA PTK YANG SUDAH / BELUM CAIR TPG
</div>

{{-- <thead>/<tbody> (sama pola dgn tab Lembar Validasi TPG) - dompdf
     mengulang otomatis isi <thead> di setiap halaman saat <tbody>
     terpotong ke halaman berikutnya, supaya header kolom tetap
     terlihat di setiap halaman kalau data PTK banyak. --}}
<table style="width:100%;border-collapse:collapse;font-size:6pt;">
    <thead>
    <tr>
        <td style="border:1px solid #000;padding:2px;text-align:center;font-weight:bold;width:2.5%;">No</td>
        <td style="border:1px solid #000;padding:2px;text-align:center;font-weight:bold;width:11%;">Nama PTK</td>
        <td style="border:1px solid #000;padding:2px;text-align:center;font-weight:bold;width:9%;">NIP PTK</td>
        @foreach ($labelBulan as $kolom => $label)
            <td style="border:1px solid #000;padding:2px;text-align:center;font-weight:bold;">Bulan {{ $label }}</td>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @forelse ($daftarPtkCairTpg as $nuptk => $ptk)
        <tr>
            <td style="border:1px solid #000;padding:2px;text-align:center;">{{ $loop->iteration }}</td>
            <td style="border:1px solid #000;padding:2px;">{{ $ptk['nama_ptk'] }}</td>
            <td style="border:1px solid #000;padding:2px;">{{ $ptk['nip'] ?: '-' }}</td>
            @foreach ($labelBulan as $kolom => $label)
                <td style="border:1px solid #000;padding:2px;text-align:center;">
                    @if ($editable)
                        <select wire:model.live="daftarPtkCairTpg.{{ $nuptk }}.{{ $kolom }}" style="width:100%;font-size:6pt;border:none;background:transparent;">
                            <option value="">-</option>
                            @foreach ($opsiStatus as $nilai => $labelOpsi)
                                <option value="{{ $nilai }}" @selected($ptk[$kolom] === $nilai)>{{ $labelOpsi }}</option>
                            @endforeach
                        </select>
                    @else
                        {{ $ptk[$kolom] ? ($opsiStatus[$ptk[$kolom]] ?? '') : '' }}
                    @endif
                </td>
            @endforeach
        </tr>
    @empty
        <tr>
            <td colspan="15" style="border:1px solid #000;padding:8px;text-align:center;color:#94a3b8;">
                Belum ada data PTK pada Lampiran 2a untuk Tahun {{ $tahun }}.
            </td>
        </tr>
    @endforelse
    </tbody>
</table>

{{-- CATATAN HASIL VERIFIKASI + Blok Tanda Tangan - SAMA PERSIS dgn
     partial surat-tpg-lembar-validasi-isi.blade.php ("sama dengan
     konsep lembar validasi", permintaan user verbatim): 1 tabel utuh
     bergaris kotak penuh (border:1px solid #000 di setiap sel), kolom
     pertama (Kepala Seksi Tata Kelola Kelembagaan dan Ketenagaan) rata
     tengah sama seperti kolom Pengawas & Kepala Sekolah. --}}
<table style="width:100%;border-collapse:collapse;margin-top:14px;font-size:10.5pt;">
    <tr>
        <td colspan="3" style="border:1px solid #000;padding:4px 6px;font-weight:bold;">CATATAN HASIL VERIFIKASI :</td>
    </tr>
    <tr>
        <td colspan="3" style="border:1px solid #000;padding:4px 6px;height:70px;vertical-align:top;">
            @if ($editable)
                <textarea wire:model.live="catatanCairTpg" rows="4" placeholder="(opsional)" class="block w-full border-0 bg-transparent text-sm focus:ring-0 px-0 py-0" style="resize:vertical;"></textarea>
            @else
                @if ($catatanCairTpg)
                    <div style="white-space:pre-line;">{{ $catatanCairTpg }}</div>
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
