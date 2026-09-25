{{--
    Isi Surat Rekomendasi TPG (Tab 1) - format sesuai gambar 1 yang
    diupload user (2026-09-24, round kedelapan belas). Dipakai bersama
    (SATU partial) di 3 tempat: preview layar (Livewire, $editable=true),
    PDF (dompdf, $editable=false), Word/.doc ($editable=false) - supaya
    WYSIWYG: apa yang diedit/dilihat user di layar = apa yang tercetak.

    Perbaikan 2026-09-24 (round kesembilan belas, permintaan user poin 1 &
    4): SEMUA kotak bergaris hitam (border:1px solid #000) pada round
    kedelapan belas DIHAPUS - "isi surat nya tidak perlu pakai garis
    hitam" & "menu format surat itu seperti menu formulir pada pendataan
    bosp" - sekarang berupa paragraf/daftar polos tanpa kotak, meniru gaya
    formulir-bos-k7c.blade.php (paragraf rata kiri-kanan biasa, daftar
    bernomor polos) - BUKAN lagi meniru tampilan surat resmi berkotak
    pada gambar contoh aslinya (kontennya/urutannya tetap sama persis,
    HANYA gaya visual kotaknya yang dihapus).

    Kutipan paragraf penutup yang tampil DUA KALI (paragraf utama + baris
    terpisah persis di bawahnya) tetap direplikasi apa adanya sesuai
    gambar 1 yang diupload user (bukan kesalahan yang perlu "diperbaiki") -
    hanya kotaknya yang dihapus, isinya tidak berubah.

    Perbaikan 2026-09-24 (round kedua puluh, permintaan user poin 1 & 4):
    - Input No. Surat sekarang rata kiri (bukan rata tengah) supaya teks
      yang diketik langsung menempel setelah tulisan "No." - "di isi
      dengan posisi sebelah kiri setelah tulisan No. / Nomor".
    - Isi surat (paragraf intro, 4 kondisi, & 2 paragraf penutup) memakai
      line-height 1.5 - "untuk tab Surat Rekomendasi TPG untuk spasi isi
      surat nya 1,5" (HANYA tab ini, TIDAK berlaku utk tab Penghentian).

    Variabel yang diharapkan:
    - $editable (bool)
    - $nomorSurat (string|null)
    - $tanggalSurat (\Illuminate\Support\Carbon|null)
    - $triwulan (int), $tahun (int)
    - $namaKepsek, $nipKepsek, $namaSekolah (string|null) - tidak semua dipakai langsung di isi ini, tempat tugas/nama/nip guru bukan bagian dari surat ini (surat ditujukan ke Pengawas, bukan ke guru perorangan)
    - $namaPengawas, $nipPengawas (string|null) - dipakai di partial ttd
--}}
@php($editable = $editable ?? false)
@php($labelTriwulan = \App\Models\SuratTpg::labelTriwulan($triwulan))

<div style="text-align:center;margin-bottom:10px;">
    <span style="font-weight:bold;font-size:13pt;text-decoration:underline;">REKOMENDASI</span><br>
    <span style="font-size:11pt;">
        No.
        @if ($editable)
            <input type="text" wire:model.live="nomorSurat" placeholder="-----------------------------" class="border-0 border-b border-slate-400 bg-transparent text-sm focus:ring-0 focus:border-blue-500 px-1 py-0 text-left" style="display:inline-block;width:220px;">
        @else
            {{ $nomorSurat ?: '-----------------------------' }}
        @endif
    </span>
</div>

<p style="font-size:11pt;line-height:1.5;margin:10px 0 4px;">
    Berdasarkan hasil verifikasi dan validasi serta penilaian terhadap kinerja guru yang terdiri dari aspek :
</p>

<div style="font-size:11pt;line-height:1.5;margin:2px 0;">1. Melaksanakan tugas dengan baik sesuai peraturan perundang-undangan;</div>
<div style="font-size:11pt;line-height:1.5;margin:2px 0;">2. Memenuhi jam tatap muka minimal;</div>
<div style="font-size:11pt;line-height:1.5;margin:2px 0;">3. Memiliki hasil nilai (PK) Guru dengan sebutan baik pada tahun sebelumnya;</div>
<div style="font-size:11pt;line-height:1.5;margin:2px 0 10px;">4. Tidak sedang menerima sanksi hukum baik disiplin pegawai maupun administrasi.</div>

<p style="font-size:11pt;line-height:1.5;text-align:justify;margin:8px 0;">
    Pada dasarnya kami tidak berkeberatan sekaligus merekomendasikan kepada nama-nama sebagaimana tercantum pada lampiran ini untuk diusulkan sebagai Penerima Tunjangan Profesi Guru {{ $labelTriwulan }} Tahun Anggaran {{ $tahun }} setelah mendapat persetujuan Pengawas sesuai dengan binaan masing-masing. Demikian rekomendasi ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.
</p>

{{-- Kalimat penutup yang tampil dua kali pada gambar contoh asli - lihat catatan di atas, direplikasi apa adanya (hanya kotaknya yang dihapus) --}}
<p style="font-size:11pt;line-height:1.5;text-align:justify;margin:8px 0;">
    Demikian rekomendasi ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.
</p>

@include('pdf.partials.surat-tpg-ttd', [
    'namaPengawas' => $namaPengawas ?? null,
    'nipPengawas' => $nipPengawas ?? null,
    'namaKepsek' => $namaKepsek ?? null,
    'nipKepsek' => $nipKepsek ?? null,
    'tanggalSurat' => $tanggalSurat ?? null,
    'editable' => $editable,
])
