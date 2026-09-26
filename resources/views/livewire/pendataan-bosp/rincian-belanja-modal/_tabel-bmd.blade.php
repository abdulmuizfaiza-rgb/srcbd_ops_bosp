{{--
    Tabel tab UTAMA "BMD" (permintaan user 2026-09-22) - di-@include dari
    index.blade.php HANYA saat $tabUtama === \App\Models\RincianBelanjaModal::TAB_BMD.
    Pola tbody-per-sekolah + baris placeholder + baris Jumlah SAMA PERSIS
    seperti tabel tab "jenis" di index.blade.php, HANYA field & jumlah
    kolom yang jauh lebih banyak (35 kolom data, sesuai gambar acuan
    "DAFTAR BELANJA MODAL TAHUN ANGGARAN 2026"). Kolom bg-yellow-50 =
    otomatis/read-only (NPSN, Lokasi, Subrayon, Program, Kegiatan, Kode
    Sub Kegiatan, Nama Sub Kegiatan, Jenis Aset (KIB), Total, & kedua
    Keterangan).
--}}
<div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg{{ $terkunciTriwulanIni ? ' pointer-events-none opacity-60 select-none' : '' }}" style="max-height: 32rem; zoom: {{ $zoomPercent }}%;">
    <table class="min-w-full divide-y divide-slate-200 text-xs">
        <thead class="sticky top-0 z-10 bg-slate-50">
            <tr class="text-left text-slate-500">
                <th class="px-2 py-2.5">
                    <input type="checkbox" wire:click="toggleSemuaBmd" @checked($semuaTerpilihBmd) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" title="Pilih/batal pilih semua baris yang tampil">
                </th>
                <th class="px-2 py-2.5">No</th>
                <th class="px-2 py-2.5">NPSN</th>
                <th class="px-2 py-2.5">Lokasi</th>
                <th class="px-2 py-2.5">Subrayon</th>
                <th class="px-2 py-2.5">Bentuk Kontrak / Transaksi</th>
                <th class="px-2 py-2.5">Program</th>
                <th class="px-2 py-2.5">Kegiatan</th>
                <th class="px-2 py-2.5">Kode Sub Kegiatan</th>
                <th class="px-2 py-2.5">Nama Sub Kegiatan</th>
                <th class="px-2 py-2.5">Atribusi</th>
                <th class="px-2 py-2.5">Jumlah Termin</th>
                <th class="px-2 py-2.5">PPK</th>
                <th class="px-2 py-2.5">Nomor Dokumen</th>
                <th class="px-2 py-2.5">Tgl. Perolehan</th>
                <th class="px-2 py-2.5">Penyedia</th>
                <th class="px-2 py-2.5">Kode Belanja</th>
                <th class="px-2 py-2.5">Rekening Belanja</th>
                <th class="px-2 py-2.5">Jenis Aset (KIB)</th>
                <th class="px-2 py-2.5">Sub Sub Rincian Objek</th>
                <th class="px-2 py-2.5">Jumlah</th>
                <th class="px-2 py-2.5">Satuan</th>
                <th class="px-2 py-2.5">Harga Satuan</th>
                <th class="px-2 py-2.5">Total</th>
                <th class="px-2 py-2.5">No BAST</th>
                <th class="px-2 py-2.5">Tgl BAST</th>
                <th class="px-2 py-2.5">Keterangan</th>
                <th class="px-2 py-2.5">Nomor Surat Pernyataan</th>
                <th class="px-2 py-2.5">Tgl. Surat Pernyataan</th>
                <th class="px-2 py-2.5">Nama Pengurus Barang</th>
                <th class="px-2 py-2.5">Jabatan</th>
                <th class="px-2 py-2.5">Pejabat Penata Usaha</th>
                <th class="px-2 py-2.5">Nama Barang</th>
                <th class="px-2 py-2.5">Spesifikasi Nama Barang</th>
                <th class="px-2 py-2.5">Spesifikasi Lain (Serial Nomor)</th>
                <th class="px-2 py-2.5">Merk/Pengarang</th>
                <th class="px-2 py-2.5">Keterangan</th>
                <th class="px-2 py-2.5 text-right">Aksi</th>
            </tr>
        </thead>
        @php $nomorSekolahBmd = 0; @endphp
        @forelse ($daftarSekolah as $sekolah)
            @php
                $nomorSekolahBmd++;
                $jumlahBarisBmd = $sekolah->rincianBelanjaModalBmd->count();
                $nomorBarisBmd = 0;
            @endphp
            <tbody wire:key="rincian-belanja-modal-bmd-grup-{{ $sekolah->id }}" x-data="{ terbuka: false }" class="divide-y divide-slate-100 border-b-2 border-slate-200">
                <tr class="bg-slate-50/70 hover:bg-slate-100 cursor-pointer select-none" x-on:click="terbuka = ! terbuka">
                    <td class="px-2 py-2" x-on:click.stop></td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-600 font-semibold">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded border border-slate-300 bg-white text-slate-500 shrink-0">
                                <x-icon name="plus" class="w-2.5 h-2.5" x-show="! terbuka" x-cloak />
                                <x-icon name="minus" class="w-2.5 h-2.5" x-show="terbuka" x-cloak />
                            </span>
                            {{ $nomorSekolahBmd }}
                        </span>
                    </td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->npsn ?? '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800">{{ $sekolah->nama_sekolah ?? '-' }}</td>
                    <td colspan="33" class="px-2 py-2 whitespace-nowrap text-slate-400 italic">
                        {{ $jumlahBarisBmd }} data sudah diinput - klik untuk <span x-text="terbuka ? 'menutup' : 'melihat'"></span>
                    </td>
                    <td class="px-2 py-2"></td>
                </tr>

                @foreach ($sekolah->rincianBelanjaModalBmd as $row)
                    @php $nomorBarisBmd++; $revisi = $revisiBarisBmd[$row->id] ?? 0; $d = $barisBmd[$row->id] ?? []; @endphp
                    <tr wire:key="rincian-belanja-modal-bmd-baris-{{ $row->id }}" x-show="terbuka" x-cloak>
                        <td class="px-2 py-2">
                            <input type="checkbox" wire:model="dipilihBmd" value="{{ $row->id }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap text-slate-400 text-right">{{ $nomorSekolahBmd }}.{{ $nomorBarisBmd }}</td>
                        <td class="px-2 py-2 whitespace-nowrap text-slate-600 bg-yellow-50">{{ $sekolah->npsn ?? '-' }}</td>
                        <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800 bg-yellow-50">{{ $sekolah->nama_sekolah ?? '-' }}</td>
                        <td class="px-2 py-2 whitespace-nowrap text-slate-600 bg-yellow-50">{{ $sekolah->subrayon ?? '-' }}</td>
                        <td class="px-1 py-1.5 whitespace-nowrap">
                            <select wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-bentuk_kontrak" wire:model="barisBmd.{{ $row->id }}.bentuk_kontrak" class="w-32 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('barisBmd.'.$row->id.'.bentuk_kontrak') ? 'border-red-400' : 'border-blue-300' }} focus:ring-1 focus:outline-none">
                                <option value="">-</option>
                                @foreach ($bentukKontrakOptions as $val => $lbl)
                                    <option value="{{ $val }}" @selected(($d['bentuk_kontrak'] ?? '') === $val)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap text-slate-500 bg-yellow-50">{{ $programBmd }}</td>
                        <td class="px-2 py-2 whitespace-nowrap text-slate-500 bg-yellow-50">{{ $kegiatanBmd }}</td>
                        <td class="px-2 py-2 whitespace-nowrap text-slate-500 bg-yellow-50">{{ $kodeSubKegiatanBmd }}</td>
                        <td class="px-2 py-2 whitespace-nowrap text-slate-500 bg-yellow-50">{{ $namaSubKegiatanBmd }}</td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-atribusi" wire:model.blur="barisBmd.{{ $row->id }}.atribusi" class="w-24 text-xs rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-jumlah_termin" wire:model.blur="barisBmd.{{ $row->id }}.jumlah_termin" class="w-16 text-xs rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-2 py-2 whitespace-nowrap text-slate-600 bg-yellow-50">{{ $sekolah->nama_kepala_sekolah ?? '-' }}</td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-nomor_dokumen" wire:model.blur="barisBmd.{{ $row->id }}.nomor_dokumen" class="w-24 text-xs rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="date" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-tanggal_perolehan" wire:model.blur="barisBmd.{{ $row->id }}.tanggal_perolehan" class="text-xs rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-penyedia" wire:model.blur="barisBmd.{{ $row->id }}.penyedia" class="w-28 text-xs rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-kode_belanja" wire:model.blur="barisBmd.{{ $row->id }}.kode_belanja" class="w-24 text-xs rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-1 py-1.5 whitespace-nowrap">
                            <select wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-rekening_belanja" wire:model="barisBmd.{{ $row->id }}.rekening_belanja" class="w-40 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('barisBmd.'.$row->id.'.rekening_belanja') ? 'border-red-400' : 'border-blue-300' }} focus:ring-1 focus:outline-none">
                                <option value="">-</option>
                                @foreach ($rekeningBelanjaOptions as $val => $lbl)
                                    <option value="{{ $val }}" @selected(($d['rekening_belanja'] ?? '') === $val)>{{ $val }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap text-slate-500 bg-yellow-50">{{ $d['jenis_aset'] ?? '-' }}</td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-sub_sub_rincian_objek" wire:model.blur="barisBmd.{{ $row->id }}.sub_sub_rincian_objek" class="w-32 text-xs rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" inputmode="numeric" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-jumlah" wire:model.blur="barisBmd.{{ $row->id }}.jumlah" class="w-16 text-xs text-right rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-satuan" wire:model.blur="barisBmd.{{ $row->id }}.satuan" class="w-16 text-xs rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-1 py-1.5 whitespace-nowrap">
                            <x-bmd-tarif-cell :row-id="$row->id" field="harga_satuan" :value="$d['harga_satuan'] ?? ''" :revisi="$revisi" />
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap text-right font-bold text-slate-700 bg-yellow-50">Rp {{ number_format((int) ($d['total'] ?? 0), 0, ',', '.') }}</td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-no_bast" wire:model.blur="barisBmd.{{ $row->id }}.no_bast" class="w-24 text-xs rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="date" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-tanggal_bast" wire:model.blur="barisBmd.{{ $row->id }}.tanggal_bast" class="text-xs rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-2 py-2 whitespace-nowrap text-slate-500 bg-yellow-50">{{ $d['keterangan_bos'] ?? '-' }}</td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-nomor_surat_pernyataan" wire:model.blur="barisBmd.{{ $row->id }}.nomor_surat_pernyataan" class="w-24 text-xs rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="date" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-tanggal_surat_pernyataan" wire:model.blur="barisBmd.{{ $row->id }}.tanggal_surat_pernyataan" class="text-xs rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-nama_pengurus_barang" wire:model.blur="barisBmd.{{ $row->id }}.nama_pengurus_barang" class="w-28 text-xs rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-jabatan" wire:model.blur="barisBmd.{{ $row->id }}.jabatan" class="w-24 text-xs rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-pejabat_penata_usaha" wire:model.blur="barisBmd.{{ $row->id }}.pejabat_penata_usaha" class="w-28 text-xs rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-1 py-1.5 whitespace-nowrap">
                            <input type="text" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-nama_barang" wire:model.blur="barisBmd.{{ $row->id }}.nama_barang" class="w-32 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('barisBmd.'.$row->id.'.nama_barang') ? 'border-red-400' : 'border-blue-300' }} focus:ring-1 focus:outline-none">
                            @error('barisBmd.'.$row->id.'.nama_barang')
                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                            @enderror
                        </td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-spesifikasi_nama_barang" wire:model.blur="barisBmd.{{ $row->id }}.spesifikasi_nama_barang" class="w-28 text-xs rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-spesifikasi_lain" wire:model.blur="barisBmd.{{ $row->id }}.spesifikasi_lain" class="w-28 text-xs rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-{{ $row->id }}-merk_pengarang" wire:model.blur="barisBmd.{{ $row->id }}.merk_pengarang" class="w-28 text-xs rounded px-1.5 py-1.5 border border-blue-300 focus:ring-1 focus:outline-none"></td>
                        <td class="px-2 py-2 whitespace-nowrap text-slate-500 bg-yellow-50">{{ $d['keterangan_bosp'] ?? '-' }}</td>
                        <td class="px-2 py-2 whitespace-nowrap text-right space-x-2">
                            <button wire:click="editBmd({{ $row->id }})" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline"><x-icon name="pencil" class="w-3 h-3" />Edit</button>
                            <button wire:click="konfirmasiHapusBmd({{ $row->id }})" class="inline-flex items-center gap-1 text-xs text-red-600 hover:underline"><x-icon name="trash" class="w-3 h-3" />Hapus</button>
                        </td>
                    </tr>
                @endforeach

                {{-- Baris placeholder kosong siap-isi - pola sama seperti tabel tab
                     "jenis". Kunci NEGATIF (-ID sekolah). --}}
                @php $idBaruBmd = -$sekolah->id; $revisiBaruBmd = $revisiBarisBmd[$idBaruBmd] ?? 0; $dBaru = $barisBmd[$idBaruBmd] ?? []; @endphp
                <tr wire:key="rincian-belanja-modal-bmd-baru-{{ $sekolah->id }}" class="bg-blue-50/40" x-show="terbuka" x-cloak>
                    <td class="px-2 py-2"></td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-300">&nbsp;</td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-600 bg-yellow-50">{{ $sekolah->npsn ?? '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800 bg-yellow-50">{{ $sekolah->nama_sekolah ?? '-' }}</td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-600 bg-yellow-50">{{ $sekolah->subrayon ?? '-' }}</td>
                    <td class="px-1 py-1.5 whitespace-nowrap">
                        <select wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-bentuk_kontrak" wire:model="barisBmd.{{ $idBaruBmd }}.bentuk_kontrak" class="w-32 text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none">
                            <option value="">-</option>
                            @foreach ($bentukKontrakOptions as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-400 italic bg-yellow-50">{{ $programBmd }}</td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-400 italic bg-yellow-50">{{ $kegiatanBmd }}</td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-400 italic bg-yellow-50">{{ $kodeSubKegiatanBmd }}</td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-400 italic bg-yellow-50">{{ $namaSubKegiatanBmd }}</td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-atribusi" wire:model.blur="barisBmd.{{ $idBaruBmd }}.atribusi" placeholder="Atribusi" class="w-24 text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-jumlah_termin" wire:model.blur="barisBmd.{{ $idBaruBmd }}.jumlah_termin" placeholder="Termin" class="w-16 text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-600 bg-yellow-50">{{ $sekolah->nama_kepala_sekolah ?? '-' }}</td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-nomor_dokumen" wire:model.blur="barisBmd.{{ $idBaruBmd }}.nomor_dokumen" placeholder="No. Dokumen" class="w-24 text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="date" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-tanggal_perolehan" wire:model.blur="barisBmd.{{ $idBaruBmd }}.tanggal_perolehan" class="text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-penyedia" wire:model.blur="barisBmd.{{ $idBaruBmd }}.penyedia" placeholder="Penyedia" class="w-28 text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-kode_belanja" wire:model.blur="barisBmd.{{ $idBaruBmd }}.kode_belanja" placeholder="Kode Belanja" class="w-24 text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-1 py-1.5 whitespace-nowrap">
                        <select wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-rekening_belanja" wire:model="barisBmd.{{ $idBaruBmd }}.rekening_belanja" class="w-40 text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none">
                            <option value="">-</option>
                            @foreach ($rekeningBelanjaOptions as $val => $lbl)
                                <option value="{{ $val }}">{{ $val }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-400 italic bg-yellow-50">{{ $dBaru['jenis_aset'] ?? '-' }}</td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-sub_sub_rincian_objek" wire:model.blur="barisBmd.{{ $idBaruBmd }}.sub_sub_rincian_objek" placeholder="Sub Sub Rincian Objek" class="w-32 text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" inputmode="numeric" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-jumlah" wire:model.blur="barisBmd.{{ $idBaruBmd }}.jumlah" placeholder="0" class="w-16 text-xs text-right rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-satuan" wire:model.blur="barisBmd.{{ $idBaruBmd }}.satuan" placeholder="Satuan" class="w-16 text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-1 py-1.5 whitespace-nowrap">
                        <x-bmd-tarif-cell :row-id="$idBaruBmd" field="harga_satuan" :value="$dBaru['harga_satuan'] ?? ''" :revisi="$revisiBaruBmd" />
                    </td>
                    <td class="px-2 py-2 whitespace-nowrap text-right font-bold text-slate-400 bg-yellow-50">Rp 0</td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-no_bast" wire:model.blur="barisBmd.{{ $idBaruBmd }}.no_bast" placeholder="No BAST" class="w-24 text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="date" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-tanggal_bast" wire:model.blur="barisBmd.{{ $idBaruBmd }}.tanggal_bast" class="text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-400 italic bg-yellow-50">{{ $dBaru['keterangan_bos'] ?? \App\Models\RincianBelanjaModalBmd::keteranganBosOtomatis($triwulan, $tahun) }}</td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-nomor_surat_pernyataan" wire:model.blur="barisBmd.{{ $idBaruBmd }}.nomor_surat_pernyataan" placeholder="No. Surat" class="w-24 text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="date" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-tanggal_surat_pernyataan" wire:model.blur="barisBmd.{{ $idBaruBmd }}.tanggal_surat_pernyataan" class="text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-nama_pengurus_barang" wire:model.blur="barisBmd.{{ $idBaruBmd }}.nama_pengurus_barang" placeholder="Nama Pengurus Barang" class="w-28 text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-jabatan" wire:model.blur="barisBmd.{{ $idBaruBmd }}.jabatan" placeholder="Jabatan" class="w-24 text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-pejabat_penata_usaha" wire:model.blur="barisBmd.{{ $idBaruBmd }}.pejabat_penata_usaha" placeholder="Pejabat Penata Usaha" class="w-28 text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-1 py-1.5 whitespace-nowrap">
                        <input type="text" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-nama_barang" wire:model.blur="barisBmd.{{ $idBaruBmd }}.nama_barang" placeholder="Nama Barang" class="w-32 text-xs rounded px-1.5 py-1.5 border {{ $errors->has('barisBmd.'.$idBaruBmd.'.nama_barang') ? 'border-red-400' : 'border-dashed border-blue-300' }} focus:ring-1 focus:outline-none">
                        @error('barisBmd.'.$idBaruBmd.'.nama_barang')
                            <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                        @enderror
                    </td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-spesifikasi_nama_barang" wire:model.blur="barisBmd.{{ $idBaruBmd }}.spesifikasi_nama_barang" placeholder="Spesifikasi" class="w-28 text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-spesifikasi_lain" wire:model.blur="barisBmd.{{ $idBaruBmd }}.spesifikasi_lain" placeholder="Serial Nomor" class="w-28 text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-1 py-1.5 whitespace-nowrap"><input type="text" wire:key="rincian-belanja-modal-bmd-input-baru-{{ $sekolah->id }}-merk_pengarang" wire:model.blur="barisBmd.{{ $idBaruBmd }}.merk_pengarang" placeholder="Merk/Pengarang" class="w-28 text-xs rounded px-1.5 py-1.5 border border-dashed border-blue-300 focus:ring-1 focus:outline-none"></td>
                    <td class="px-2 py-2 whitespace-nowrap text-slate-400 italic bg-yellow-50">{{ $dBaru['keterangan_bosp'] ?? \App\Models\RincianBelanjaModalBmd::keteranganBospOtomatis($triwulan, $tahun) }}</td>
                    <td class="px-2 py-2 whitespace-nowrap text-right">
                        <button type="button" x-on:click="$el.closest('tr').querySelector('input,select')?.focus()" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline">
                            <x-icon name="plus" class="w-3 h-3" />Baris baru
                        </button>
                    </td>
                </tr>

                {{-- Baris Jumlah PER SEKOLAH - pola sama seperti tabel tab "jenis". --}}
                <tr wire:key="rincian-belanja-modal-bmd-total-sekolah-{{ $sekolah->id }}" class="bg-slate-100 font-bold text-slate-700 border-t-2 border-slate-300" x-show="terbuka" x-cloak>
                    <td colspan="23" class="px-2 py-2 text-right">Jumlah BMD</td>
                    <td class="px-2 py-2 whitespace-nowrap text-right">Rp {{ number_format((int) $sekolah->rincianBelanjaModalBmd->sum('total'), 0, ',', '.') }}</td>
                    <td colspan="14"></td>
                </tr>
            </tbody>
        @empty
            <tbody>
                <tr>
                    <td colspan="38" class="px-3 py-6 text-center text-slate-400">Tidak ada sekolah yang bisa ditampilkan.</td>
                </tr>
            </tbody>
        @endforelse
        @if ($daftarSekolah->isNotEmpty())
            <tbody>
                <tr class="bg-slate-200 font-bold text-slate-800 border-t-2 border-slate-400">
                    <td colspan="23" class="px-2 py-2.5 text-right">Jumlah BMD Seluruh Sekolah</td>
                    <td class="px-2 py-2.5 whitespace-nowrap text-right">Rp {{ number_format((int) $totalBmdKeseluruhan, 0, ',', '.') }}</td>
                    <td colspan="14"></td>
                </tr>
            </tbody>
        @endif
    </table>
</div>
