{{--
    Blok tanda tangan Pengawas Pembina (kiri) & Kepala Sekolah (kanan) -
    dipakai bersama oleh KEDUA jenis Surat TPG, permintaan user: "untuk
    bagian Tanda tangan Pengawas pembina dan Tanda tangan Kepala Sekolah
    field nya di ambil otomatis dari menu profil sekolah" - $namaPengawas/
    $nipPengawas/$namaKepsek/$nipKepsek SELALU diisi otomatis dari
    App\Models\ProfilSekolah (nama_pengawas/nip_pengawas/
    nama_kepala_sekolah/nip_kepala_sekolah), TIDAK ADA input manual di
    sini pada mode $editable sekalipun - yang editable HANYA Tanggal
    Surat (tanggal pada baris "Sukabumi, ...").

    Variabel: $namaPengawas, $nipPengawas, $namaKepsek, $nipKepsek,
    $tanggalSurat (Carbon|null), $editable (bool, default false).

    Perbaikan 2026-09-24 (round kedua puluh, permintaan user poin 2 & 3):
    - Garis bawah (border-bottom) di atas nama Pengawas Pembina & Kepala
      Sekolah DIHAPUS ("tidak ada garis bawah hilangkan garis bawah nya").
    - Seluruh blok tanda tangan (label+spasi kosong+nama+NIP) digeser ke
      kanan supaya lebih rapih/simetris ("digeser ke sebelah kanan 5
      langkah") - keputusan teknis murni (bukan aturan bisnis): "5
      langkah" diterjemahkan sebagai margin-left 40px (kira-kira setara
      5 karakter pada ukuran font 11pt), diterapkan SAMA pada kedua
      kolom (Pengawas Pembina & Kepala Sekolah) supaya tetap simetris.
--}}
@php($editable = $editable ?? false)
<table style="width:100%;border-collapse:collapse;margin-top:34px;font-size:11pt;">
    <tr>
        <td style="width:50%;vertical-align:top;padding-right:14px;">
            <div style="margin-left:40px;">
                Mengetahui/menyetujui :<br>
                Pengawas Pembina
                <div style="height:64px;"></div>
                {{ $namaPengawas ?: '............................' }}<br>
                NIP. {{ $nipPengawas ?: '............................' }}
            </div>
        </td>
        <td style="width:50%;vertical-align:top;padding-left:14px;">
            <div style="margin-left:40px;">
                Sukabumi,
                @if ($editable)
                    <input type="date" wire:model.live="tanggalSurat" class="border-0 border-b border-slate-400 bg-transparent text-sm focus:ring-0 focus:border-blue-500 px-1 py-0" style="display:inline-block;width:150px;">
                @else
                    {{ $tanggalSurat ? $tanggalSurat->translatedFormat('d F Y') : '..........................' }}.
                @endif
                <br>
                Kepala Sekolah,
                <div style="height:64px;"></div>
                {{ $namaKepsek ?: '............................' }}<br>
                NIP. {{ $nipKepsek ?: '............................' }}
            </div>
        </td>
    </tr>
</table>
