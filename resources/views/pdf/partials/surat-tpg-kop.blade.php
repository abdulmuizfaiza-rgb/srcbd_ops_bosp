{{--
    Kepala surat (kop) bersama - dipakai oleh KEDUA jenis Surat TPG
    (Rekomendasi & Penghentian), pada ketiga tempat surat ini ditampilkan
    (preview di layar/Livewire, PDF via dompdf, & Word).

    Perbaikan 2026-09-24 (round kesembilan belas, permintaan user poin 2 &
    6): placeholder "LOGO"+"KOP SEKOLAH" round sebelumnya DIGANTI TOTAL -
    kop surat sekarang gambar yang DIUPLOAD MANUAL oleh Admin OPS/
    Superadmin (lewat kotak upload di bawah, muncul HANYA saat
    $editable=true, yaitu di preview layar Livewire) - berlaku SAMA untuk
    kedua tab (Rekomendasi & Penghentian) krn partial ini dipakai bersama
    keduanya & disimpan per SEKOLAH (App\Models\ProfilSekolah::kop_surat),
    bukan per tahun/triwulan/jenis surat.

    Variabel:
    - $kopSuratSrc (string|null): src siap pakai untuk tag <img> - route
      terautentikasi (preview layar) ATAU data URI base64 (PDF/Word),
      lihat App\Models\ProfilSekolah::kopSuratDataUri() &
      App\Http\Controllers\KopSuratFileController.
    - $editable (bool, default false): true HANYA saat dirender di preview
      layar Livewire (App\Livewire\PendataanOps\SuratTpg\Index) - kotak
      upload/ganti HANYA muncul saat true, krn PDF/Word tidak interaktif.

    SENGAJA ditulis dengan tabel/div HTML polos (BUKAN flexbox/grid, & TANPA
    garis kotak hitam pada gambar kop-nya sendiri - permintaan user poin 4
    "isi surat nya tidak perlu pakai garis hitam") supaya tampil identik di
    ketiganya, krn dompdf (PDF) tidak mendukung flexbox/grid.
--}}
@php($editable = $editable ?? false)
@php($kopSuratSrc = $kopSuratSrc ?? null)

@if ($kopSuratSrc)
    <div style="text-align:center;margin-bottom:8px;">
        <img src="{{ $kopSuratSrc }}" style="max-width:100%;max-height:130px;">
    </div>
    <div style="border-bottom:2px solid #334155;margin-bottom:16px;"></div>
    @if ($editable)
        <div wire:loading.remove wire:target="kopSuratBaru" class="mb-4 -mt-2 text-center">
            <label class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-700 cursor-pointer">
                <x-icon name="upload" class="w-3.5 h-3.5" />
                Ganti Kop Surat
                <input type="file" wire:model="kopSuratBaru" accept="image/*" class="hidden">
            </label>
        </div>
        <div wire:loading wire:target="kopSuratBaru" class="mb-4 -mt-2 text-center text-xs text-slate-400">Mengunggah kop surat...</div>
        <x-input-error :messages="$errors->get('kopSuratBaru')" class="mb-4 -mt-2 text-center" />
    @endif
@elseif ($editable)
    <div wire:loading.remove wire:target="kopSuratBaru" class="mb-4">
        <label class="flex flex-col items-center justify-center gap-1.5 border border-dashed border-slate-300 rounded-lg py-5 text-slate-400 hover:text-slate-600 hover:border-slate-400 cursor-pointer text-xs">
            <x-icon name="upload" class="w-5 h-5" />
            Kop surat belum diupload - klik untuk upload gambar kop surat
            <input type="file" wire:model="kopSuratBaru" accept="image/*" class="hidden">
        </label>
    </div>
    <div wire:loading wire:target="kopSuratBaru" class="mb-4 text-center text-xs text-slate-400 border border-dashed border-slate-300 rounded-lg py-5">Mengunggah kop surat...</div>
    <x-input-error :messages="$errors->get('kopSuratBaru')" class="mb-4 -mt-2" />
@endif
