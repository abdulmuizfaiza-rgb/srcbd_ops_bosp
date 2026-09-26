{{--
    Modal Tambah/Edit/Hapus tab "BMD" (permintaan user 2026-09-22, jawaban
    AskUserQuestion "Modal + kotak isi langsung di tabel") - di-@include
    dari index.blade.php SELALU (bukan hanya saat tab BMD aktif), sama
    seperti modal tab "jenis" - hanya TERLIHAT saat $showFormBmd/
    $confirmingDeleteBmdId di-set oleh tambahBmd()/editBmd()/
    konfirmasiHapusBmd() (yang HANYA dipanggil dari tabel BMD), jadi
    AMAN berdampingan dengan modal 'rincian-belanja-modal-form'/
    'rincian-belanja-modal-hapus' milik tab "jenis" di atas.
--}}
<x-modal name="rincian-belanja-modal-bmd-form" :show="$showFormBmd" maxWidth="4xl">
    <form wire:submit="simpanBmd" class="p-6">
        <h2 class="text-lg font-medium text-slate-900 mb-1">
            {{ $editingBmdId ? 'Edit Data BMD' : 'Tambah Data BMD' }}
        </h2>
        <p class="text-sm text-slate-500 mb-4">Tahun {{ $tahun }} - {{ $triwulanOptions[$triwulan] ?? 'Triwulan '.$triwulan }}</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="sm:col-span-2 lg:col-span-3">
                <x-input-label for="profil_sekolah_id_bmd" value="Sekolah" />
                <select wire:model="profil_sekolah_id" id="profil_sekolah_id_bmd" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full" @disabled(! $bolehKelolaSemua)>
                    <option value="">-- Pilih Sekolah --</option>
                    @foreach ($sekolahOptions as $sekolah)
                        <option value="{{ $sekolah->id }}">{{ $sekolah->nama_sekolah }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-400 mt-1">NPSN, Lokasi, & Subrayon otomatis mengikuti data pada menu Profil Sekolah.</p>
                <x-input-error :messages="$errors->get('profil_sekolah_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label value="Program" />
                <div class="mt-1 block w-full rounded-md border border-amber-200 bg-amber-50 text-slate-700 text-sm px-3 py-2">{{ $programBmd }}</div>
            </div>

            <div>
                <x-input-label value="Kegiatan" />
                <div class="mt-1 block w-full rounded-md border border-amber-200 bg-amber-50 text-slate-700 text-sm px-3 py-2">{{ $kegiatanBmd }}</div>
            </div>

            <div>
                <x-input-label value="Kode Sub Kegiatan" />
                <div class="mt-1 block w-full rounded-md border border-amber-200 bg-amber-50 text-slate-700 text-sm px-3 py-2">{{ $kodeSubKegiatanBmd }}</div>
            </div>

            <div class="sm:col-span-2 lg:col-span-3">
                <x-input-label value="Nama Sub Kegiatan" />
                <div class="mt-1 block w-full rounded-md border border-amber-200 bg-amber-50 text-slate-700 text-sm px-3 py-2">{{ $namaSubKegiatanBmd }}</div>
            </div>

            <div>
                <x-input-label for="bmd_bentuk_kontrak" value="Bentuk Kontrak / Transaksi" />
                <select wire:model="bmd_bentuk_kontrak" id="bmd_bentuk_kontrak" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                    <option value="">-- Pilih --</option>
                    @foreach ($bentukKontrakOptions as $val => $lbl)
                        <option value="{{ $val }}">{{ $lbl }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('bmd_bentuk_kontrak')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="bmd_atribusi" value="Atribusi" />
                <x-text-input wire:model="bmd_atribusi" id="bmd_atribusi" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('bmd_atribusi')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="bmd_jumlah_termin" value="Jumlah Termin" />
                <x-text-input wire:model="bmd_jumlah_termin" id="bmd_jumlah_termin" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('bmd_jumlah_termin')" class="mt-2" />
            </div>

            <div
                x-data
                x-init="
                    sekolahKepalaSekolah = @js($sekolahNamaKepalaSekolah);
                    hitungPpk = () => sekolahKepalaSekolah[$wire.profil_sekolah_id] ?? '-';
                "
            >
                <x-input-label value="PPK (Nama Kepala Sekolah)" />
                <div class="mt-1 block w-full rounded-md border border-amber-200 bg-amber-50 text-slate-700 text-sm px-3 py-2" x-text="hitungPpk()"></div>
                <p class="text-xs text-slate-400 mt-1">Otomatis mengikuti Nama Kepala Sekolah pada Profil Sekolah yang dipilih.</p>
            </div>

            <div>
                <x-input-label for="bmd_nomor_dokumen" value="Nomor Dokumen" />
                <x-text-input wire:model="bmd_nomor_dokumen" id="bmd_nomor_dokumen" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('bmd_nomor_dokumen')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="bmd_tanggal_perolehan" value="Tgl. Perolehan" />
                <x-text-input wire:model="bmd_tanggal_perolehan" id="bmd_tanggal_perolehan" class="block mt-1 w-full" type="date" />
                <x-input-error :messages="$errors->get('bmd_tanggal_perolehan')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="bmd_penyedia" value="Penyedia" />
                <x-text-input wire:model="bmd_penyedia" id="bmd_penyedia" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('bmd_penyedia')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="bmd_kode_belanja" value="Kode Belanja" />
                <x-text-input wire:model="bmd_kode_belanja" id="bmd_kode_belanja" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('bmd_kode_belanja')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="bmd_rekening_belanja" value="Rekening Belanja" />
                <select wire:model="bmd_rekening_belanja" id="bmd_rekening_belanja" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                    <option value="">-- Pilih --</option>
                    @foreach ($rekeningBelanjaOptions as $val => $lbl)
                        <option value="{{ $val }}">{{ $val }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('bmd_rekening_belanja')" class="mt-2" />
            </div>

            <div
                x-data
                x-init="
                    rekeningJenisAset = @js($rekeningBelanjaOptions);
                    hitungJenisAset = () => rekeningJenisAset[$wire.bmd_rekening_belanja] ?? '-';
                "
            >
                <x-input-label value="Jenis Aset (KIB)" />
                <div class="mt-1 block w-full rounded-md border border-amber-200 bg-amber-50 text-slate-700 text-sm px-3 py-2" x-text="hitungJenisAset()"></div>
                <p class="text-xs text-slate-400 mt-1">Otomatis mengikuti Rekening Belanja (dihitung ulang saat disimpan).</p>
            </div>

            <div class="sm:col-span-2 lg:col-span-3">
                <x-input-label for="bmd_sub_sub_rincian_objek" value="Sub Sub Rincian Objek" />
                <x-text-input wire:model="bmd_sub_sub_rincian_objek" id="bmd_sub_sub_rincian_objek" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('bmd_sub_sub_rincian_objek')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="bmd_jumlah" value="Jumlah" />
                <x-text-input wire:model="bmd_jumlah" id="bmd_jumlah" class="block mt-1 w-full" type="text" inputmode="numeric" />
                <x-input-error :messages="$errors->get('bmd_jumlah')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="bmd_satuan" value="Satuan" />
                <x-text-input wire:model="bmd_satuan" id="bmd_satuan" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('bmd_satuan')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="bmd_harga_satuan" value="Harga Satuan" />
                <x-currency-input name="bmd_harga_satuan" :value="$bmd_harga_satuan" :reset-key="$formInstanceBmd" />
                <x-input-error :messages="$errors->get('bmd_harga_satuan')" class="mt-2" />
            </div>

            <div
                x-data
                x-init="
                    hitungTotalBmd = () => {
                        const j = parseInt(String($wire.bmd_jumlah ?? '').replace(/\D/g, '')) || 0;
                        const h = parseInt(String($wire.bmd_harga_satuan ?? '').replace(/\D/g, '')) || 0;
                        return new Intl.NumberFormat('id-ID').format(j * h);
                    }
                "
            >
                <x-input-label value="Total" />
                <div class="mt-1 block w-full rounded-md border border-amber-200 bg-amber-50 text-slate-700 text-sm px-3 py-2 text-right" x-text="'Rp ' + hitungTotalBmd()"></div>
                <p class="text-xs text-slate-400 mt-1">Otomatis: Jumlah x Harga Satuan (dihitung ulang saat disimpan).</p>
            </div>

            <div>
                <x-input-label for="bmd_no_bast" value="No BAST" />
                <x-text-input wire:model="bmd_no_bast" id="bmd_no_bast" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('bmd_no_bast')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="bmd_tanggal_bast" value="Tgl BAST" />
                <x-text-input wire:model="bmd_tanggal_bast" id="bmd_tanggal_bast" class="block mt-1 w-full" type="date" />
                <x-input-error :messages="$errors->get('bmd_tanggal_bast')" class="mt-2" />
            </div>

            <div>
                <x-input-label value="Keterangan" />
                <div class="mt-1 block w-full rounded-md border border-amber-200 bg-amber-50 text-slate-700 text-sm px-3 py-2">{{ \App\Models\RincianBelanjaModalBmd::keteranganBosOtomatis($triwulan, $tahun) }}</div>
                <p class="text-xs text-slate-400 mt-1">Otomatis mengikuti Triwulan & Tahun yang sedang aktif.</p>
            </div>

            <div>
                <x-input-label for="bmd_nomor_surat_pernyataan" value="Nomor Surat Pernyataan" />
                <x-text-input wire:model="bmd_nomor_surat_pernyataan" id="bmd_nomor_surat_pernyataan" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('bmd_nomor_surat_pernyataan')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="bmd_tanggal_surat_pernyataan" value="Tgl. Surat Pernyataan" />
                <x-text-input wire:model="bmd_tanggal_surat_pernyataan" id="bmd_tanggal_surat_pernyataan" class="block mt-1 w-full" type="date" />
                <x-input-error :messages="$errors->get('bmd_tanggal_surat_pernyataan')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="bmd_nama_pengurus_barang" value="Nama Pengurus Barang" />
                <x-text-input wire:model="bmd_nama_pengurus_barang" id="bmd_nama_pengurus_barang" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('bmd_nama_pengurus_barang')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="bmd_jabatan" value="Jabatan" />
                <x-text-input wire:model="bmd_jabatan" id="bmd_jabatan" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('bmd_jabatan')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="bmd_pejabat_penata_usaha" value="Pejabat Penata Usaha" />
                <x-text-input wire:model="bmd_pejabat_penata_usaha" id="bmd_pejabat_penata_usaha" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('bmd_pejabat_penata_usaha')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="bmd_nama_barang" value="Nama Barang" />
                <x-text-input wire:model="bmd_nama_barang" id="bmd_nama_barang" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('bmd_nama_barang')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="bmd_spesifikasi_nama_barang" value="Spesifikasi Nama Barang" />
                <x-text-input wire:model="bmd_spesifikasi_nama_barang" id="bmd_spesifikasi_nama_barang" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('bmd_spesifikasi_nama_barang')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="bmd_spesifikasi_lain" value="Spesifikasi Lain (Serial Nomor)" />
                <x-text-input wire:model="bmd_spesifikasi_lain" id="bmd_spesifikasi_lain" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('bmd_spesifikasi_lain')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="bmd_merk_pengarang" value="Merk/Pengarang" />
                <x-text-input wire:model="bmd_merk_pengarang" id="bmd_merk_pengarang" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('bmd_merk_pengarang')" class="mt-2" />
            </div>

            <div>
                <x-input-label value="Keterangan" />
                <div class="mt-1 block w-full rounded-md border border-amber-200 bg-amber-50 text-slate-700 text-sm px-3 py-2">{{ \App\Models\RincianBelanjaModalBmd::keteranganBospOtomatis($triwulan, $tahun) }}</div>
                <p class="text-xs text-slate-400 mt-1">Otomatis mengikuti Triwulan & Tahun yang sedang aktif.</p>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <x-secondary-button type="button" wire:click="batalBmd" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
            <x-primary-button class="!px-3 !py-1.5 !text-[10px]"><x-icon name="check" class="w-3.5 h-3.5 mr-1" />Simpan</x-primary-button>
        </div>
    </form>
</x-modal>

{{-- Modal Konfirmasi Hapus BMD --}}
<x-modal name="rincian-belanja-modal-bmd-hapus" :show="$confirmingDeleteBmdId !== null" maxWidth="md">
    <div class="p-6">
        <h2 class="text-lg font-medium text-slate-900">Hapus data ini?</h2>
        <p class="mt-1 text-sm text-slate-600">Tindakan ini tidak dapat dibatalkan.</p>

        <div class="mt-6 flex justify-end gap-3">
            <x-secondary-button wire:click="batalHapusBmd" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
            <x-danger-button wire:click="hapusBmd" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="trash" class="w-3.5 h-3.5 mr-1" />Hapus</x-danger-button>
        </div>
    </div>
</x-modal>

{{-- Modal Konfirmasi Hapus Terpilih BMD (hapus massal) - permintaan
     user 2026-09-26, pola sama seperti Penerimaan Honor PTK. --}}
<x-modal name="rincian-belanja-modal-bmd-hapus-terpilih" :show="$confirmingHapusTerpilihBmd" maxWidth="md">
    <div class="p-6">
        <h2 class="text-lg font-medium text-slate-900">Hapus {{ count($dipilihBmd) }} data terpilih?</h2>
        <p class="mt-1 text-sm text-slate-600">Semua baris yang dicentang akan dihapus sekaligus. Tindakan ini tidak dapat dibatalkan.</p>

        <div class="mt-6 flex justify-end gap-3">
            <x-secondary-button wire:click="batalHapusTerpilihBmd" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
            <x-danger-button wire:click="hapusTerpilihBmd" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="trash" class="w-3.5 h-3.5 mr-1" />Hapus Semua Terpilih</x-danger-button>
        </div>
    </div>
</x-modal>
