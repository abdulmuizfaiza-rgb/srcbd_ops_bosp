<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Laporan Realisasi BOSP (Form BPK)') }}
    </h2>
</x-slot>

<div>
    {{-- Popup notifikasi "Saldo Rekening/Kas Bank & Saldo Kas Tunai belum
         diisi" (permintaan user 2026-09-23, item #8) - dipicu SEKALI dari
         Livewire\PendataanBosp\LaporanRealisasiBosp\Index::mount() (event
         "tarik-tunai-bosp-belum-lengkap", lihat docblock method
         tarikTunaiBospBelumLengkap() di sana), bukan setiap re-render.
         Pojok kanan atas, tulisan merah tebal, hilang otomatis 5 detik -
         jawaban AskUserQuestion 2026-09-23 ("Sekali saat tab dibuka").
         Pola Alpine `@this.on(...)` sama dengan x-components.action-message.
         Harus di DALAM elemen root tunggal komponen ini (bukan sibling
         sebelum <div> pembungkus) - Livewire 3 menolak lebih dari 1 root
         element per komponen. --}}
    <div x-data="{ tampil: false, timeout: null }"
         x-init="@this.on('tarik-tunai-bosp-belum-lengkap', () => { clearTimeout(timeout); tampil = true; timeout = setTimeout(() => { tampil = false }, 5000); })"
         x-show="tampil"
         x-transition
         style="display: none;"
         class="fixed top-4 right-4 z-50 max-w-sm rounded-lg border border-red-300 bg-red-50 px-4 py-3 shadow-lg">
        <p class="text-sm font-bold text-red-700">
            Silahkan Admin BOSP input dulu Tarik Tunai BOSP pada menu Dana BOSP Tahap 1 & 2 - Tarik Tunai BOSP
        </p>
    </div>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="flex flex-col xl:flex-row xl:flex-wrap xl:items-start xl:justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-lg font-medium text-slate-900">Laporan Realisasi BOSP (Form BPK)</h3>
                        <p class="text-sm text-slate-500">Laporan Realisasi BOSP per sekolah, per triwulan, per tahun anggaran.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <label for="tahun" class="text-sm text-slate-600">Tahun</label>
                        <select wire:model.live="tahun" id="tahun" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                            @foreach ($tahunOptions as $opsiTahun)
                                <option value="{{ $opsiTahun }}">{{ $opsiTahun }}</option>
                            @endforeach
                        </select>

                        <x-zoom-controls :zoom="$zoomPercent" />
                    </div>
                </div>

                {{--
                    5 tab (permintaan user 2026-09-17, Part 32): TW1-4 (input) +
                    Rekapitulasi (otomatis, read-only).

                    Perbaikan 2026-09-24 (round kedua puluh empat, poin 4): setiap
                    tab diberi warna aktif BERBEDA (sebelumnya semua biru) -
                    TW1=blue, TW2=emerald, TW3=amber, TW4=rose,
                    Rekapitulasi=violet - lewat peta warna $warnaTabRealisasi di
                    bawah supaya class Tailwind-nya tetap literal (bisa
                    ter-detect saat "npm run build").
                --}}
                @php
                    $warnaTabRealisasi = [
                        'tw1' => 'border-blue-600 text-blue-700',
                        'tw2' => 'border-emerald-600 text-emerald-700',
                        'tw3' => 'border-amber-600 text-amber-700',
                        'tw4' => 'border-rose-600 text-rose-700',
                        'rekap' => 'border-violet-600 text-violet-700',
                    ];
                @endphp
                <div class="border-b border-slate-200 mb-4">
                    <nav class="flex flex-wrap gap-3">
                        @foreach (['tw1' => 'Laporan Realisasi TW 1', 'tw2' => 'Laporan Realisasi TW 2', 'tw3' => 'Laporan Realisasi TW 3', 'tw4' => 'Laporan Realisasi TW 4', 'rekap' => 'Rekapitulasi Tahun Anggaran (otomatis)'] as $tabKey => $tabLabel)
                            <button
                                type="button"
                                wire:click="pindahTab('{{ $tabKey }}')"
                                class="px-3 py-2 text-sm font-medium border-b-2 -mb-px {{ $tabAktif === $tabKey ? $warnaTabRealisasi[$tabKey] : 'border-transparent text-slate-500 hover:text-slate-700' }}"
                            >
                                {{ $tabLabel }}
                            </button>
                        @endforeach
                    </nav>
                </div>

                @if ($bolehKelolaSemua && $statusVervalSuperadmin)
                    {{-- ============ Panel "Status Verval & Reset Kuncian" - HANYA
                         Superadmin (permintaan user 2026-09-23, round ketujuh,
                         jawaban AskUserQuestion "Tombol Reset di halaman Laporan
                         Realisasi BOSP" & "Reset penuh"). Tampil di SEMUA tab
                         (tw1-4 maupun rekap) - independen dari tab yang sedang
                         aktif, karena isinya rekap ke-4 triwulan sekaligus.
                         Admin BOSP yang butuh 1 triwulan dibuka kembali harus
                         meminta Superadmin melakukan reset lewat panel ini -
                         menghapus baris verval-nya (lihat
                         VervalRealisasiBosp::resetTriwulan()) otomatis membuka
                         KEMBALI ceklist verval MAUPUN data isian triwulan itu di
                         8 menu sumber sekaligus ("Reset penuh"). Panel terpisah
                         (BUKAN kolom tambahan pada tabel TW1-4 di bawah) supaya
                         tidak perlu mengubah struktur rowspan/colspan header
                         tabel yang sudah kompleks & sudah berjalan. --}}
                    <div x-data="{ terbuka: false }" class="mb-4 border border-purple-200 rounded-lg bg-purple-50/60 overflow-hidden">
                        <button type="button" x-on:click="terbuka = ! terbuka" class="w-full flex items-center gap-2 px-3 py-2 text-left hover:bg-purple-100/50">
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded border border-purple-300 bg-white text-purple-600 shrink-0">
                                <x-icon name="plus" class="w-2.5 h-2.5" x-show="! terbuka" x-cloak />
                                <x-icon name="minus" class="w-2.5 h-2.5" x-show="terbuka" x-cloak />
                            </span>
                            <x-icon name="clipboard-check" class="w-3.5 h-3.5 text-purple-500 shrink-0" />
                            <span class="text-xs font-medium text-purple-700">Status Verval &amp; Reset Kuncian (Superadmin) - Tahun {{ $tahun }}</span>
                            <span class="text-[10px] text-purple-400 ml-1" x-show="! terbuka" x-cloak>(klik untuk buka)</span>
                        </button>
                        <div x-show="terbuka" x-cloak class="px-3 pb-3">
                            <p class="text-xs text-slate-500 mb-2">
                                Triwulan berstatus <strong>"Sesuai"</strong> terkunci permanen bagi Admin BOSP - klik
                                <strong>"Reset"</strong> untuk membuka kembali ceklist verval MAUPUN data isian triwulan itu
                                di menu-menu sumber terkait untuk sekolah tsb.
                            </p>
                            <div class="overflow-auto scrollbar-modern border border-purple-100 rounded-lg max-h-80">
                                <table class="min-w-full divide-y divide-purple-100 text-xs">
                                    <thead class="bg-purple-100/70 sticky top-0">
                                        <tr>
                                            <th class="px-2 py-1.5 border border-purple-100 text-left whitespace-nowrap">Sekolah</th>
                                            @foreach ([1, 2, 3, 4] as $tw)
                                                <th class="px-2 py-1.5 border border-purple-100 text-center whitespace-nowrap">Triwulan {{ $tw }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-purple-50 bg-white">
                                        @forelse ($statusVervalSuperadmin as $barisVerval)
                                            {{-- PENTING: variabel loop SENGAJA "$barisVerval", BUKAN
                                                 "$baris" - nama "$baris" sudah dipakai properti publik
                                                 $this->baris (array tabel utama tab TW1-4 di bawah).
                                                 Blade TIDAK menscope variabel @forelse/@foreach ke dalam
                                                 blok-nya saja - begitu loop ini selesai, variabel akan
                                                 TETAP ada memegang nilai iterasi TERAKHIR, menimpa/menutupi
                                                 $baris (array [sekolahId => [field => nilai]]) yang
                                                 dipakai tabel utama di bawah, sehingga SELURUH sel tabel
                                                 utama ikut tampil 0/kosong utk Superadmin saja (panel ini
                                                 HANYA tampil utk Superadmin - Admin BOSP tidak terdampak).
                                                 Ditemukan & diperbaiki 2026-09-24 (laporan bug user: kolom
                                                 "Penerimaan Dana BOS" & kolom lain kosong di tabel TW1-4
                                                 utk Superadmin, padahal datanya ada & baris JUMLAH benar). --}}
                                            <tr wire:key="verval-status-{{ $barisVerval['sekolah']->id }}">
                                                <td class="px-2 py-1.5 border border-purple-50 whitespace-nowrap font-medium text-slate-700">{{ $barisVerval['sekolah']->nama_sekolah }}</td>
                                                @foreach ([1, 2, 3, 4] as $tw)
                                                    @php $statusTw = $barisVerval['status'][$tw]->status ?? null; @endphp
                                                    <td class="px-2 py-1.5 border border-purple-50 text-center whitespace-nowrap">
                                                        @if ($statusTw === \App\Models\VervalRealisasiBosp::STATUS_SESUAI)
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-600 text-white px-2 py-0.5 text-[10px] font-semibold mr-1">
                                                                <x-icon name="check" class="w-2.5 h-2.5" /> Sesuai
                                                            </span>
                                                            <button
                                                                type="button"
                                                                wire:click="resetVerval({{ $barisVerval['sekolah']->id }}, {{ $tw }})"
                                                                wire:confirm="Yakin reset kuncian Verval Triwulan {{ $tw }} untuk {{ $barisVerval['sekolah']->nama_sekolah }}? Ceklist verval & data isian triwulan ini di menu-menu sumber akan terbuka kembali."
                                                                class="px-1.5 py-0.5 rounded text-[10px] font-medium border border-red-300 text-red-700 bg-white hover:bg-red-50"
                                                            >Reset</button>
                                                        @elseif ($statusTw === \App\Models\VervalRealisasiBosp::STATUS_BELUM_SESUAI)
                                                            <span class="text-red-500">Belum Sesuai</span>
                                                        @else
                                                            <span class="text-slate-300">-</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-3 py-4 text-center text-slate-400">Belum ada data sekolah.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($panelValidasiSendiri)
                    {{-- ============ Panel "Validasi Hasil Entry Data BOSP" TERTANAM
                         - HANYA Admin BOSP (permintaan user 2026-09-23, round
                         kedelapan, laporan bug atas Round 7: gerbang validasi
                         SEBELUMNYA menahan akses sampai ke-4 triwulan "Sesuai" -
                         SEKARANG halaman laporan ini langsung aktif begitu
                         triwulan MANAPUN sudah diverval sekali (lihat
                         VervalRealisasiBosp::adaTriwulanSudahDiverval() &
                         Index::render()). Panel ini (SAMA PERSIS isinya dengan
                         gerbang awal di validasi.blade.php, lihat partial
                         _tabel-validasi.blade.php yang dipakai bersama) tetap
                         ada di sini supaya Admin BOSP bisa melanjutkan verval
                         triwulan yang belum diklik TANPA balik ke halaman
                         gerbang. Default TERBUKA (beda dari panel Superadmin di
                         atas yang default tertutup) karena panel ini yang jadi
                         alat kerja utama Admin BOSP, bukan sekadar alat darurat
                         seperti tombol Reset Superadmin. ============ --}}
                    <div x-data="{ terbuka: true }" class="mb-4 border border-emerald-200 rounded-lg bg-emerald-50/60 overflow-hidden">
                        <button type="button" x-on:click="terbuka = ! terbuka" class="w-full flex items-center gap-2 px-3 py-2 text-left hover:bg-emerald-100/50">
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded border border-emerald-300 bg-white text-emerald-600 shrink-0">
                                <x-icon name="plus" class="w-2.5 h-2.5" x-show="! terbuka" x-cloak />
                                <x-icon name="minus" class="w-2.5 h-2.5" x-show="terbuka" x-cloak />
                            </span>
                            <x-icon name="clipboard-check" class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                            <span class="text-xs font-medium text-emerald-700">Validasi Hasil Entry Data BOSP - Tahun {{ $tahun }}</span>
                            <span class="text-[10px] text-emerald-500 ml-1" x-show="! terbuka" x-cloak>(klik untuk buka)</span>
                        </button>
                        <div x-show="terbuka" x-cloak class="px-3 pb-3">
                            <p class="text-xs text-slate-500 mb-2">
                                Triwulan berstatus <strong>"Sesuai"</strong> terkunci permanen - data isian triwulan itu di
                                menu-menu sumber ikut tidak bisa diedit lagi. Kalau perlu dibuka kembali, hubungi
                                Superadmin untuk me-reset kuncian tsb dari halaman ini juga. Memilih <strong>"Belum
                                Sesuai"</strong> tidak mengunci apapun - bisa diganti bolak-balik sampai memilih "Sesuai".
                            </p>
                            @include('livewire.pendataan-bosp.laporan-realisasi-bosp._tabel-validasi', ['daftarValidasi' => $panelValidasiSendiri['daftarValidasi'], 'statusVerval' => $panelValidasiSendiri['statusVerval']])
                        </div>
                    </div>
                @endif

                @if ($tabAktif !== 'rekap')
                    {{-- ============ TAB TW1-4: tabel wide biasa, SELURUH kolom 8-29 hasil rumus/read-only (permintaan user 2026-09-17, lanjutan Part 32 ketujuh) ============ --}}
                    {{-- Alert banner collapsible (permintaan user 2026-09-17, ronde ke-6
                         poin 1) - menggantikan paragraf panjang biasa supaya halaman tidak
                         terlalu panjang. Default TERTUTUP (x-data terbuka: false) - pola
                         Alpine plus/minus SAMA PERSIS dengan fitur Buka-Tutup baris per
                         sekolah di menu lain (Penerimaan Honor PTK dkk), hanya dipakai untuk
                         1 blok info di sini (bukan per baris data). --}}
                    <div x-data="{ terbuka: false }" class="mb-2 border border-blue-200 rounded-lg bg-blue-50/60 overflow-hidden">
                        <button type="button" x-on:click="terbuka = ! terbuka" class="w-full flex items-center gap-2 px-3 py-2 text-left hover:bg-blue-100/50">
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded border border-blue-300 bg-white text-blue-600 shrink-0">
                                <x-icon name="plus" class="w-2.5 h-2.5" x-show="! terbuka" x-cloak />
                                <x-icon name="minus" class="w-2.5 h-2.5" x-show="terbuka" x-cloak />
                            </span>
                            <x-icon name="alert-circle" class="w-3.5 h-3.5 text-blue-500 shrink-0" />
                            <span class="text-xs font-medium text-blue-700">Keterangan kolom otomatis & rumus</span>
                            <span class="text-[10px] text-blue-400 ml-1" x-show="! terbuka" x-cloak>(klik untuk buka)</span>
                        </button>
                        <p class="text-xs text-slate-500 px-3 pb-2.5" x-show="terbuka" x-cloak>Seluruh kotak pada tabel ini SUDAH OTOMATIS (read-only) - tidak ada lagi yang bisa diketik manual. Kolom "Saldo Awal Dana BOSP" TW 1 diambil otomatis dari "Saldo BOSP TW 4 Tahun Sebelumnya" (menu Dana BOSP Tahap 1 & 2, tab Tarik Tunai BOSP), sedangkan TW 2/3/4 diambil dari "Sisa Dana BOS" triwulan SEBELUMNYA (TW 1/2/3) pada tabel ini sendiri. Kolom "Penerimaan Dana BOS" TW 1 diambil dari "Penerimaan BOSP Tahap 1" & TW 3 dari "Penerimaan BOSP Tahap 2" (menu Dana BOSP Tahap 1 & 2, tab Penerimaan BOSP) - TW 2 & TW 4 SELALU Rp 0 (tidak ada penerimaan Dana BOSP pada triwulan tsb). Kolom "Total Penerimaan" = Saldo Awal Dana BOSP + Penerimaan Dana BOS pada triwulan yang sama. Kolom "Belanja Barang Pakai Habis/Persediaan" diambil otomatis dari total menu Rincian Belanja Barang Habis Pakai TW yang sama, kolom "Jasa Tenaga Pendidik dan Kependidikan" dari total menu Penerimaan Honor PTK TW yang sama, kolom "Daya dan Jasa" dari total menu Daya & Jasa TW yang sama, kolom "Pemeliharaan" dari total menu Rincian Pemeliharaan + Rincian Pemeliharaan PC-Laptop-Printer dll (jenis barang) TW yang sama, kolom "Upah Pemeliharaan" dari total menu Rincian Jasa Pemeliharaan + Rincian Jasa Pemeliharaan PC-Laptop-Printer dll (jenis jasa) TW yang sama, kolom "Biaya Pendaftaran Lomba/Bimtek/Workshop" dari total menu Biaya Pendaftaran Lomba/Bimtek/Workshop TW yang sama, dan kolom "Honor Kegiatan"/"Makan dan Minum Kegiatan"/"Perjalanan Dinas" masing-masing dari total menu Belanja Honor Kegiatan & Makan Minum Kegiatan & Perjalanan Dinas (tab utama Honor Kegiatan/Belanja Makan & Minum/Belanja Perjalanan Dinas) TW yang sama. Kolom "Total Belanja Barang dan Jasa" = jumlah 8 kolom di atas MULAI dari "Jasa Tenaga Pendidik dan Kependidikan" (kolom "Belanja Barang Pakai Habis/Persediaan" TIDAK diikutkan di kolom ini, tapi tetap diikutkan di "Total Realisasi Dana BOS"). Kolom "Peralatan dan Mesin KIB B" & "Aset Tetap Lainnya KIB E" diambil otomatis dari Total Harga tab Belanja Modal KIB B/KIB E TW yang sama, dan kolom "Total Belanja Modal" = jumlah keduanya. Kolom "Total Realisasi Dana BOS" = Belanja Barang Pakai Habis/Persediaan + Total Belanja Barang dan Jasa + Total Belanja Modal, dan kolom "Sisa Dana BOS" = Total Penerimaan − Total Realisasi Dana BOS. Kolom "Saldo Rekening/Kas Bank" & "Saldo Kas Tunai" diambil otomatis dari field Saldo Kas Bank/Saldo Kas Tunai TW yang sama pada menu Dana BOSP Tahap 1 & 2, tab Tarik Tunai BOSP. Kolom "Jumlah" & "Verifikasi Saldo" dihitung otomatis oleh sistem secara real-time - kata "SAMA"/"TIDAK SAMA" pada "Verifikasi Saldo" BARU tampil setelah Saldo Rekening/Kas Bank & Saldo Kas Tunai TW ybs SUDAH tersimpan (kalau salah satu atau keduanya belum diisi, kotak menampilkan "-").</p>
                    </div>

                    <div class="overflow-auto scrollbar-modern border border-blue-200 rounded-lg" style="max-height: 34rem; zoom: {{ $zoomPercent }}%;">
                        <table class="min-w-full divide-y divide-blue-100 text-xs">
                            @include('livewire.pendataan-bosp.laporan-realisasi-bosp._thead', ['tahun' => $tahun])
                            <tbody class="divide-y divide-blue-100">
                                @forelse ($daftarSekolah as $sekolah)
                                    @php $revisi = $revisiBaris[$sekolah->id] ?? 0; @endphp
                                    <tr wire:key="laporan-realisasi-bosp-baris-{{ $sekolah->id }}">
                                        <td class="px-2 py-2 whitespace-nowrap text-center text-slate-500 border border-blue-100">{{ $loop->iteration }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap text-slate-600 border border-blue-100">{{ $sekolah->kode_upb ?: '-' }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap text-slate-600 border border-blue-100">{{ $sekolah->npsn ?: '-' }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800 border border-blue-100">{{ $sekolah->nama_sekolah }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap text-slate-600 border border-blue-100">{{ $sekolah->kecamatan ?: '-' }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap text-slate-600 border border-blue-100">{{ $sekolah->subrayon ?: '-' }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap text-center text-slate-600 border border-blue-100">{{ \App\Livewire\PendataanBosp\LaporanRealisasiBosp\Index::TAB_TRIWULAN[$tabAktif] }}</td>

                                        @foreach (\App\Models\LaporanRealisasiBosp::FIELD_MANUAL as $field)
                                            @if (in_array($field, \App\Models\LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN, true))
                                                {{-- Kolom 11-25 (Belanja Barang Pakai Habis/Persediaan,
                                                     Jasa Tenaga Pendidik dan Kependidikan, Daya dan Jasa,
                                                     Pemeliharaan, Upah Pemeliharaan, Biaya Pendaftaran
                                                     Lomba/Bimtek/Workshop, Honor Kegiatan, Makan dan Minum
                                                     Kegiatan, Perjalanan Dinas, Total Belanja Barang dan
                                                     Jasa, Peralatan dan Mesin KIB B, Aset Tetap Lainnya
                                                     KIB E, Total Belanja Modal, Total Realisasi Dana BOS,
                                                     Sisa Dana BOS) - READ-ONLY, diambil/dihitung otomatis
                                                     secara real-time dari menu sumber masing-masing TW
                                                     yang sama atau dari kolom komputasi lain (lihat
                                                     catatan Index::renderTabTriwulan() &
                                                     LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN). --}}
                                                <td class="px-2 py-2 whitespace-nowrap text-right font-bold text-slate-700 border border-blue-100 bg-yellow-50">
                                                    Rp {{ number_format((int) ($baris[$sekolah->id][$field] ?? 0), 0, ',', '.') }}
                                                </td>
                                            @else
                                                {{-- Sejak permintaan user 2026-09-17 (lanjutan Part 32 ketujuh),
                                                     kolom 8-10 (Saldo Awal Dana BOSP, Penerimaan Dana BOS, Total
                                                     Penerimaan) - KOLOM MANUAL TERAKHIR di menu ini - JUGA sudah
                                                     pindah ke cabang FIELD_KOMPUTASI_RINCIAN di atas, sehingga
                                                     cabang @else INI SEKARANG TIDAK PERNAH TEREKSEKUSI LAGI
                                                     (SELURUH anggota FIELD_MANUAL sudah masuk
                                                     FIELD_KOMPUTASI_RINCIAN). TETAP dipertahankan (bukan dihapus)
                                                     sebagai infrastruktur vestigial, jaga-jaga kalau ada kolom
                                                     manual baru ditambahkan lagi di masa depan pada tabel ini. --}}
                                                <td class="px-1 py-1.5 whitespace-nowrap border border-blue-100">
                                                    <x-honor-ptk-tarif-cell :row-id="$sekolah->id" :field="$field" :value="$baris[$sekolah->id][$field] ?? ''" :revisi="$revisi" placeholder="0" />
                                                </td>
                                            @endif
                                        @endforeach

                                        {{-- Kolom 28 & 29 - HASIL RUMUS (lihat LaporanRealisasiBosp::hitungVerifikasiSaldo()), TIDAK PERNAH kotak input. --}}
                                        <td class="px-2 py-2 whitespace-nowrap text-right font-bold text-slate-700 border border-blue-100 bg-yellow-50">
                                            Rp {{ number_format((int) ($baris[$sekolah->id]['verifikasi_jumlah'] ?? 0), 0, ',', '.') }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-center border border-blue-100 bg-yellow-50">
                                            @php $verifikasi = $baris[$sekolah->id]['verifikasi_saldo'] ?? ''; @endphp
                                            @if ($verifikasi === \App\Models\LaporanRealisasiBosp::VERIFIKASI_SAMA)
                                                <span class="font-bold text-blue-700">SAMA</span>
                                            @elseif ($verifikasi === \App\Models\LaporanRealisasiBosp::VERIFIKASI_TIDAK_SAMA)
                                                <span class="font-bold text-red-600">TIDAK SAMA</span>
                                            @else
                                                <span class="text-slate-300">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="29" class="px-3 py-6 text-center text-slate-400">Belum ada data sekolah.</td>
                                    </tr>
                                @endforelse

                                @if ($daftarSekolah->isNotEmpty())
                                    <tr class="bg-slate-100 font-bold">
                                        <td colspan="7" class="px-2 py-2 whitespace-nowrap text-center text-slate-800 border border-blue-100">JUMLAH</td>
                                        @foreach (\App\Models\LaporanRealisasiBosp::FIELD_MANUAL as $field)
                                            <td class="px-1 py-1.5 whitespace-nowrap text-right border border-blue-100">Rp {{ number_format($totalBaris[$field] ?? 0, 0, ',', '.') }}</td>
                                        @endforeach
                                        <td class="px-2 py-2 whitespace-nowrap text-right border border-blue-100 bg-yellow-100">Rp {{ number_format($totalBaris['verifikasi_jumlah'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap text-center border border-blue-100 bg-yellow-100">
                                            @if (($totalBaris['verifikasi_saldo'] ?? '') === \App\Models\LaporanRealisasiBosp::VERIFIKASI_SAMA)
                                                <span class="font-bold text-blue-700">SAMA</span>
                                            @else
                                                <span class="font-bold text-red-600">TIDAK SAMA</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                @else
                    {{-- ============ TAB 5: REKAPITULASI (otomatis, read-only) ============
                         Rincian 4 TW + baris "Jumlah" PER SEKOLAH (jawaban AskUserQuestion
                         2026-09-17), tidak ada kotak input di tab ini.

                         Sejak permintaan user 2026-09-17 (ronde ke-6, poin 7): DIROMBAK dari
                         "1 <table> terpisah per sekolah" jadi SATU <table> dengan header
                         SAMA PERSIS seperti tab TW1-4 (reuse partial _thead), lalu 1 <tbody>
                         per sekolah dengan Alpine x-data="{ terbuka: false }" - pola Buka-
                         Tutup (+/-) SAMA PERSIS dengan kolom "No" di menu Penerimaan Honor
                         PTK/Daya & Jasa/Biaya Pendaftaran Lomba/dll. Baris ringkasan (selalu
                         tampil) berisi No + identitas sekolah, klik untuk buka 4 baris TW +
                         baris Jumlah. Default TERTUTUP supaya halaman ringkas kalau sekolah
                         banyak - "supaya lebih rapih" (permintaan user verbatim). --}}
                    <div x-data="{ terbuka: false }" class="mb-2 border border-blue-200 rounded-lg bg-blue-50/60 overflow-hidden">
                        <button type="button" x-on:click="terbuka = ! terbuka" class="w-full flex items-center gap-2 px-3 py-2 text-left hover:bg-blue-100/50">
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded border border-blue-300 bg-white text-blue-600 shrink-0">
                                <x-icon name="plus" class="w-2.5 h-2.5" x-show="! terbuka" x-cloak />
                                <x-icon name="minus" class="w-2.5 h-2.5" x-show="terbuka" x-cloak />
                            </span>
                            <x-icon name="alert-circle" class="w-3.5 h-3.5 text-blue-500 shrink-0" />
                            <span class="text-xs font-medium text-blue-700">Keterangan</span>
                            <span class="text-[10px] text-blue-400 ml-1" x-show="! terbuka" x-cloak>(klik untuk buka)</span>
                        </button>
                        <p class="text-xs text-slate-500 px-3 pb-2.5" x-show="terbuka" x-cloak>Rekapitulasi otomatis dari data 4 Triwulan pada tab sebelumnya - rincian per triwulan + baris "Jumlah" untuk tiap sekolah. Tidak ada input di tab ini. Klik baris sekolah (icon + di kolom No) untuk membuka/menutup rincian 4 triwulannya. Baris "JUMLAH TAHUN ANGGARAN" di paling bawah tabel (selalu tampil, tidak perlu diklik) adalah total SATU TAHUN ANGGARAN dari SELURUH sekolah yang tampil. Kata "SAMA"/"TIDAK SAMA" pada kolom Verifikasi Saldo (baris per-TW, "Jumlah" per sekolah, maupun "JUMLAH TAHUN ANGGARAN") BARU tampil kalau Saldo Rekening/Kas Bank & Saldo Kas Tunai SUDAH tersimpan lengkap untuk seluruh triwulan/sekolah terkait - kalau belum, kotak menampilkan "-".</p>
                    </div>

                    <div class="overflow-auto scrollbar-modern border border-blue-200 rounded-lg" style="max-height: 34rem; zoom: {{ $zoomPercent }}%;">
                        <table class="min-w-full divide-y divide-blue-100 text-xs">
                            @include('livewire.pendataan-bosp.laporan-realisasi-bosp._thead', ['tahun' => $tahun])
                            @forelse ($rekapPerSekolah as $blok)
                                @php $sekolah = $blok['sekolah']; @endphp
                                <tbody wire:key="laporan-realisasi-bosp-rekap-{{ $sekolah->id }}" x-data="{ terbuka: false }" class="divide-y divide-blue-100 border-b-2 border-blue-200">
                                    {{-- Baris ringkasan - selalu tampil, klik untuk buka/tutup 4 baris TW + Jumlah di bawahnya. --}}
                                    <tr class="bg-blue-50/70 hover:bg-blue-100/60 cursor-pointer select-none" x-on:click="terbuka = ! terbuka">
                                        <td class="px-2 py-2 whitespace-nowrap text-center text-slate-600 font-semibold border border-blue-100">
                                            <span class="inline-flex items-center gap-1.5">
                                                <span class="inline-flex items-center justify-center w-4 h-4 rounded border border-blue-300 bg-white text-blue-600 shrink-0">
                                                    <x-icon name="plus" class="w-2.5 h-2.5" x-show="! terbuka" x-cloak />
                                                    <x-icon name="minus" class="w-2.5 h-2.5" x-show="terbuka" x-cloak />
                                                </span>
                                                {{ $loop->iteration }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-slate-600 border border-blue-100">{{ $sekolah->kode_upb ?: '-' }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap text-slate-600 border border-blue-100">{{ $sekolah->npsn ?: '-' }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800 border border-blue-100">{{ $sekolah->nama_sekolah }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap text-slate-600 border border-blue-100">{{ $sekolah->kecamatan ?: '-' }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap text-slate-600 border border-blue-100">{{ $sekolah->subrayon ?: '-' }}</td>
                                        <td colspan="23" class="px-2 py-2 whitespace-nowrap text-slate-400 italic border border-blue-100">
                                            Rincian 4 triwulan - klik untuk <span x-text="terbuka ? 'menutup' : 'melihat'"></span>
                                        </td>
                                    </tr>

                                    @foreach ([1, 2, 3, 4] as $triwulan)
                                        @php $baris = $blok['perTriwulan'][$triwulan]; @endphp
                                        <tr wire:key="laporan-realisasi-bosp-rekap-{{ $sekolah->id }}-tw{{ $triwulan }}" x-show="terbuka" x-cloak>
                                            <td class="px-2 py-2 border border-blue-100"></td>
                                            <td class="px-2 py-2 border border-blue-100"></td>
                                            <td class="px-2 py-2 border border-blue-100"></td>
                                            <td class="px-2 py-2 border border-blue-100"></td>
                                            <td class="px-2 py-2 border border-blue-100"></td>
                                            <td class="px-2 py-2 border border-blue-100"></td>
                                            <td class="px-2 py-2 whitespace-nowrap text-center font-medium text-slate-700 border border-blue-100">TW {{ $triwulan }}</td>
                                            @foreach (\App\Models\LaporanRealisasiBosp::FIELD_MANUAL as $field)
                                                <td class="px-2 py-2 whitespace-nowrap text-right text-slate-600 border border-blue-100">Rp {{ number_format((int) ($baris[$field] ?? 0), 0, ',', '.') }}</td>
                                            @endforeach
                                            <td class="px-2 py-2 whitespace-nowrap text-right font-bold text-slate-700 border border-blue-100 bg-yellow-50">Rp {{ number_format((int) ($baris['verifikasi_jumlah'] ?? 0), 0, ',', '.') }}</td>
                                            <td class="px-2 py-2 whitespace-nowrap text-center border border-blue-100 bg-yellow-50">
                                                @if (($baris['verifikasi_saldo'] ?? '') === \App\Models\LaporanRealisasiBosp::VERIFIKASI_SAMA)
                                                    <span class="font-bold text-blue-700">SAMA</span>
                                                @elseif (($baris['verifikasi_saldo'] ?? '') === \App\Models\LaporanRealisasiBosp::VERIFIKASI_TIDAK_SAMA)
                                                    <span class="font-bold text-red-600">TIDAK SAMA</span>
                                                @else
                                                    <span class="text-slate-300">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach

                                    {{-- Baris "Jumlah" - sum 4 TW di atas (lihat catatan Index::renderTabRekap()). Ikut x-show="terbuka" + x-cloak sama seperti baris detail lain. --}}
                                    <tr class="bg-slate-100 font-bold" x-show="terbuka" x-cloak>
                                        <td colspan="7" class="px-2 py-2 whitespace-nowrap text-center text-slate-800 border border-blue-100">JUMLAH</td>
                                        @foreach (\App\Models\LaporanRealisasiBosp::FIELD_MANUAL as $field)
                                            <td class="px-2 py-2 whitespace-nowrap text-right border border-blue-100">Rp {{ number_format($blok['jumlah'][$field] ?? 0, 0, ',', '.') }}</td>
                                        @endforeach
                                        <td class="px-2 py-2 whitespace-nowrap text-right border border-blue-100 bg-yellow-100">Rp {{ number_format($blok['jumlah']['verifikasi_jumlah'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap text-center border border-blue-100 bg-yellow-100">
                                            @if (($blok['jumlah']['verifikasi_saldo'] ?? '') === \App\Models\LaporanRealisasiBosp::VERIFIKASI_SAMA)
                                                <span class="font-bold text-blue-700">SAMA</span>
                                            @else
                                                <span class="font-bold text-red-600">TIDAK SAMA</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            @empty
                                <tbody>
                                    <tr>
                                        <td colspan="29" class="px-3 py-6 text-center text-slate-400">Belum ada data sekolah.</td>
                                    </tr>
                                </tbody>
                            @endforelse

                            @if (count($rekapPerSekolah) > 0)
                                {{-- Baris total SATU TAHUN ANGGARAN - SELALU tampil (BUKAN
                                     collapsible seperti baris "Jumlah" per sekolah di atas),
                                     kolom 1-7 di-merge jadi 1 sel label - permintaan user
                                     2026-09-17 (lanjutan Part 32 ketujuh, poin 2). Dihitung dari
                                     SUM baris "Jumlah" seluruh sekolah (lihat
                                     Index::renderTabRekap(), $totalTahunAnggaran). --}}
                                <tbody class="divide-y divide-blue-100 border-t-4 border-blue-300">
                                    <tr class="bg-slate-200 font-bold">
                                        <td colspan="7" class="px-2 py-2 whitespace-nowrap text-center text-slate-900 border border-blue-100">JUMLAH TAHUN ANGGARAN {{ $tahun }}</td>
                                        @foreach (\App\Models\LaporanRealisasiBosp::FIELD_MANUAL as $field)
                                            <td class="px-2 py-2 whitespace-nowrap text-right border border-blue-100">Rp {{ number_format($totalTahunAnggaran[$field] ?? 0, 0, ',', '.') }}</td>
                                        @endforeach
                                        <td class="px-2 py-2 whitespace-nowrap text-right border border-blue-100 bg-yellow-100">Rp {{ number_format($totalTahunAnggaran['verifikasi_jumlah'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap text-center border border-blue-100 bg-yellow-100">
                                            @if (($totalTahunAnggaran['verifikasi_saldo'] ?? '') === \App\Models\LaporanRealisasiBosp::VERIFIKASI_SAMA)
                                                <span class="font-bold text-blue-700">SAMA</span>
                                            @elseif (($totalTahunAnggaran['verifikasi_saldo'] ?? '') === \App\Models\LaporanRealisasiBosp::VERIFIKASI_TIDAK_SAMA)
                                                <span class="font-bold text-red-600">TIDAK SAMA</span>
                                            @else
                                                <span class="text-slate-300">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            @endif
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Permintaan user 2026-09-23 (round ketujuh, dipertahankan di round
         kedelapan karena panel "Validasi Hasil Entry Data BOSP" Admin BOSP
         sekarang TERTANAM di halaman INI juga, bukan cuma di
         validasi.blade.php): setiap kali Index::setVerval() berhasil
         menyimpan pilihan Sesuai/Belum Sesuai (dipanggil dari panel di
         atas), method itu mem-dispatch event browser "verval-diperbarui" -
         listener ini menangkapnya lalu me-reload SELURUH halaman supaya
         status verval & kuncian data terbaru langsung terlihat. Aman
         dipasang di sini juga untuk Superadmin (mereka tidak pernah
         memanggil setVerval() - lihat guard bolehKelolaSemua() di
         dalamnya - jadi listener ini praktis tidak pernah terpicu untuk
         mereka). --}}
    @script
    <script>
        $wire.on('verval-diperbarui', () => {
            window.location.reload();
        });
    </script>
    @endscript
</div>
