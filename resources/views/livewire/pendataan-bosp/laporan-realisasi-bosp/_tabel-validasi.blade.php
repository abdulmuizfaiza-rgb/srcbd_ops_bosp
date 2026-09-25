{{-- Partial tabel "Validasi Hasil Entry Data BOSP" (13 baris Uraian x 4
     kolom Triwulan, tombol Sesuai/Belum Sesuai per triwulan) - diekstrak
     2026-09-23 (round kedelapan) dari validasi.blade.php SUPAYA bisa
     dipakai ULANG di 2 tempat: (1) validasi.blade.php sendiri (gerbang
     awal, SELAMA belum ada triwulan yang diverval sama sekali), DAN (2)
     index.blade.php (panel "Validasi Hasil Entry Data BOSP" yang
     TERTANAM di halaman laporan biasa untuk Admin BOSP, supaya mereka
     bisa melanjutkan verval triwulan yang belum diklik TANPA balik ke
     gerbang - permintaan user: "untuk verval triwulan berikutnya itu
     sesuai jadwal deadline pekerjaan nya yang ditentukan oleh
     superadmin" - lihat rencana menu Timeline pekerjaan, round kedelapan
     bagian B). Markup & logic SAMA PERSIS dengan sebelum diekstrak -
     TIDAK ada perubahan tampilan/perilaku dari ekstraksi ini sendiri.

     Variabel yang WAJIB dikirim lewat @include(..., [...]):
       - $daftarValidasi : array dari Index::dataValidasi($sekolahId)
       - $statusVerval   : Collection dari VervalRealisasiBosp::ambilStatus(...)
     wire:click="setVerval(...)" memanggil method pada Livewire\...\Index -
     SAH dipanggil dari kedua tempat karena keduanya dirender oleh
     instance komponen Index yang SAMA. --}}
<div class="overflow-auto scrollbar-modern border border-blue-200 rounded-lg">
    <table class="min-w-full divide-y divide-blue-100 text-xs">
        <thead>
            <tr>
                <th rowspan="2" class="px-2 py-2 border border-blue-100 bg-slate-100 text-left align-middle whitespace-nowrap">Uraian</th>
                @foreach ([1 => 'bg-yellow-100', 2 => 'bg-green-100', 3 => 'bg-blue-100', 4 => 'bg-orange-100'] as $tw => $warna)
                    <th colspan="2" class="px-2 py-2 border border-blue-100 {{ $warna }} text-center whitespace-nowrap">Triwulan {{ $tw }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach ([1 => 'bg-yellow-50', 2 => 'bg-green-50', 3 => 'bg-blue-50', 4 => 'bg-orange-50'] as $tw => $warna)
                    @php $statusTw = $statusVerval[$tw]->status ?? null; @endphp
                    <th class="px-2 py-1.5 border border-blue-100 {{ $warna }} text-right whitespace-nowrap">Jumlah (Rp)</th>
                    <th class="px-2 py-1.5 border border-blue-100 {{ $warna }} text-center whitespace-nowrap" style="min-width: 9rem;">
                        <div class="mb-1">Verval</div>
                        @if ($statusTw === \App\Models\VervalRealisasiBosp::STATUS_SESUAI)
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-600 text-white px-2 py-0.5 text-[10px] font-semibold">
                                <x-icon name="check" class="w-2.5 h-2.5" /> Sesuai (Terkunci)
                            </span>
                        @else
                            <div class="flex items-center justify-center gap-1">
                                <button
                                    type="button"
                                    wire:click="setVerval({{ $tw }}, '{{ \App\Models\VervalRealisasiBosp::STATUS_SESUAI }}')"
                                    wire:confirm="Yakin triwulan {{ $tw }} sudah SESUAI? Setelah ini triwulan {{ $tw }} akan terkunci permanen & tidak bisa diubah lagi."
                                    class="px-1.5 py-0.5 rounded text-[10px] font-medium border border-emerald-300 text-emerald-700 bg-white hover:bg-emerald-50"
                                >Sesuai</button>
                                <button
                                    type="button"
                                    wire:click="setVerval({{ $tw }}, '{{ \App\Models\VervalRealisasiBosp::STATUS_BELUM_SESUAI }}')"
                                    class="px-1.5 py-0.5 rounded text-[10px] font-medium border {{ $statusTw === \App\Models\VervalRealisasiBosp::STATUS_BELUM_SESUAI ? 'border-red-400 text-red-700 bg-red-50' : 'border-slate-300 text-slate-600 bg-white hover:bg-slate-50' }}"
                                >Belum Sesuai</button>
                            </div>
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-blue-100">
            @foreach ($daftarValidasi as $item)
                <tr wire:key="validasi-baris-{{ $loop->index }}">
                    <td class="px-2 py-2 border border-blue-50 whitespace-nowrap">{{ $item['label'] }}</td>
                    @foreach ([1, 2, 3, 4] as $tw)
                        @php $statusTw = $statusVerval[$tw]->status ?? null; @endphp
                        <td class="px-2 py-2 border border-blue-50 text-right whitespace-nowrap">{{ number_format($item['nilai'][$tw], 0, ',', '.') }}</td>
                        <td class="px-2 py-2 border border-blue-50 text-center whitespace-nowrap">
                            @if ($statusTw === \App\Models\VervalRealisasiBosp::STATUS_SESUAI)
                                <span class="text-emerald-600 font-semibold">Sesuai</span>
                            @elseif ($statusTw === \App\Models\VervalRealisasiBosp::STATUS_BELUM_SESUAI)
                                <span class="text-red-500">Belum Sesuai</span>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
