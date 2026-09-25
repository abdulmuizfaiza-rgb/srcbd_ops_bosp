<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Laporan Realisasi BOSP (Form BPK)') }}
    </h2>
</x-slot>

{{-- Halaman validasi (Verval) - permintaan user 2026-09-23 (round
     keenam, poin 3, gambar contoh "VALIDASI HASIL ENTRY DATA BOSP").
     Ditampilkan SEBAGAI GANTI halaman Laporan Realisasi BOSP (Form BPK)
     yang biasa (lihat Livewire\PendataanBosp\LaporanRealisasiBosp\Index::render())
     HANYA untuk Admin BOSP (Superadmin tidak pernah melihat halaman ini).

     DIREVISI 2026-09-23 (round kedelapan, laporan bug atas Round 7):
     SEBELUMNYA gerbang ini tampil sampai KEEMPAT triwulan berstatus
     "Sesuai" - SEKARANG gerbang ini HANYA tampil selama BELUM ADA SATU
     PUN triwulan yang pernah diverval (Sesuai MAUPUN Belum Sesuai) untuk
     tahun aktif (lihat VervalRealisasiBosp::adaTriwulanSudahDiverval()).
     Begitu triwulan MANAPUN diklik (Sesuai/Belum Sesuai), halaman laporan
     biasa aktif otomatis (reload penuh, lihat @script di bawah) - tabel
     verval di bawah ini TETAP ADA di halaman laporan biasa itu (panel
     "Validasi Hasil Entry Data BOSP" tertanam, lihat index.blade.php)
     supaya Admin BOSP bisa melanjutkan verval triwulan yang tersisa
     TANPA balik ke halaman ini lagi. --}}
<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="flex flex-col xl:flex-row xl:flex-wrap xl:items-start xl:justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-lg font-medium text-slate-900">Validasi Hasil Entry Data BOSP</h3>
                        <p class="text-sm text-slate-500">
                            Sebelum halaman "Laporan Realisasi BOSP (Form BPK)" bisa dibuka, silakan periksa rincian
                            data salah satu triwulan di bawah ini, lalu pilih <strong>Sesuai</strong> atau
                            <strong>Belum Sesuai</strong>. Halaman laporan akan <strong>langsung aktif otomatis</strong>
                            begitu triwulan MANAPUN sudah diverval - tidak perlu menunggu keempat triwulan sekaligus.
                            Triwulan lain yang belum diverval bisa dilanjutkan kapan saja dari halaman laporan itu
                            sendiri.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <label for="tahun" class="text-sm text-slate-600">Tahun</label>
                        <select wire:model.live="tahun" id="tahun" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                            @foreach ($tahunOptions as $opsiTahun)
                                <option value="{{ $opsiTahun }}">{{ $opsiTahun }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if (! $sekolah)
                    <p class="text-sm text-slate-500 italic">Profil sekolah Anda belum ditemukan - hubungi Superadmin.</p>
                @else
                    <div class="mb-4 p-3 rounded-lg border border-amber-200 bg-amber-50 text-xs text-amber-800">
                        Triwulan yang sudah dipilih <strong>"Sesuai"</strong> akan <strong>TERKUNCI PERMANEN</strong>
                        (tidak bisa diubah lagi) - data isian triwulan itu pada menu-menu sumber (Penerimaan Honor
                        PTK, Daya &amp; Jasa, Rincian Pemeliharaan, Biaya Pendaftaran Lomba/Bimtek/Workshop, Honor
                        Kegiatan, Belanja Makan &amp; Minum, Belanja Perjalanan Dinas, Rincian Belanja Modal, &amp;
                        Rincian Belanja Barang Habis Pakai) ikut menjadi tidak bisa diedit lagi untuk triwulan itu.
                        Pastikan data sudah benar sebelum memilih "Sesuai". Memilih "Belum Sesuai" TIDAK mengunci
                        apapun - Anda masih bisa berganti pilihan sampai memilih "Sesuai". Kalau triwulan yang sudah
                        terkunci perlu dibuka kembali, hubungi Superadmin untuk me-reset kuncian tsb dari halaman
                        Laporan Realisasi BOSP (Form BPK).
                    </div>

                    @include('livewire.pendataan-bosp.laporan-realisasi-bosp._tabel-validasi', ['daftarValidasi' => $daftarValidasi, 'statusVerval' => $statusVerval])
                @endif
            </div>
        </div>
    </div>

    {{-- Permintaan user 2026-09-23 (round ketujuh, jawaban AskUserQuestion
         "Tiap klik Sesuai/Belum Sesuai, reload halaman penuh"): setiap kali
         Livewire\PendataanBosp\LaporanRealisasiBosp\Index::setVerval()
         berhasil menyimpan pilihan Sesuai/Belum Sesuai, method itu
         mem-dispatch event browser "verval-diperbarui" - listener di bawah
         ini menangkapnya lalu me-reload SELURUH halaman (bukan sekadar
         re-render Livewire biasa) supaya halaman langsung menampilkan
         status/gerbang terbaru. --}}
    @script
    <script>
        $wire.on('verval-diperbarui', () => {
            window.location.reload();
        });
    </script>
    @endscript
</div>
