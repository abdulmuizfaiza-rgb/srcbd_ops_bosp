<?php

namespace App\Livewire\PendataanBosp\DanaBospTahap;

use App\Livewire\Concerns\HasZoomTampilan;
use App\Models\DanaBospTahap;
use App\Models\ProfilSekolah;
use App\Models\RekapRkas;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Dana BOSP Tahap 1 & 2 - Pendataan BOSP (permintaan user 2026-09-16,
 * Part 30). Posisi sidebar: sesudah "Rekap RKAS Awal-Perubahan".
 *
 * 1 baris per sekolah PER TAHUN (pola sama seperti RekapRkas), dengan 2
 * tab UI yang menampilkan KELOMPOK FIELD BERBEDA dari 1 record yang sama
 * (BEDA dari tab BelanjaHonorKegiatan/RincianBelanjaModal yang memfilter
 * BANYAK BARIS berbeda per jenis - di sini cuma 1 baris per sekolah,
 * tabnya murni pengelompokan tampilan field).
 *
 * Tab "penerimaan" (Penerimaan BOSP): tabel biasa, 1 baris per sekolah,
 * SEMUA role (Superadmin: seluruh sekolah, Admin BOSP: sekolah sendiri
 * saja) - sama seperti RekapRkas. 3 kolom manual + 3 kolom hasil rumus
 * (lihat App\Models\DanaBospTahap::hitungRumusTab1()).
 *
 * Tab "tarik_tunai" (Tarik Tunai BOSP) - LAYOUT BEDA PER ROLE (jawaban
 * AskUserQuestion 2026-09-16):
 * - Admin BOSP: form VERTIKAL (field berbaris ke bawah sejajar kiri)
 *   untuk sekolahnya sendiri saja, BUKAN tabel.
 * - Superadmin: tabel multi-sekolah dengan tanda +/- di kolom No (pola
 *   "tabel diringkas per sekolah" yang sudah ada di aplikasi ini, mis.
 *   RincianBelanjaModal) - expand menampilkan field yang sama berbaris
 *   ke bawah untuk sekolah itu.
 * Kedua layout memakai kotak input yang sama (wire:model.blur ke
 * "baris.{sekolahId}.{field}"), cuma disusun beda secara visual.
 *
 * Sejak permintaan user 2026-09-16 (Part 31), tiap baris "Tarik Tunai TW
 * N" (N=1-4) ditambah grup "Saldo TW N" (Saldo Kas Bank + Saldo Kas
 * Tunai -> Saldo TW = hasil jumlah keduanya, lihat
 * App\Models\DanaBospTahap::hitungSaldoTw()) yang tampil SEJAJAR DI
 * SEBELAH KANAN Tarik Tunai TW N yang bersangkutan (bukan baris
 * terpisah di bawahnya) - lihat blade view untuk perulangan grup TW 1-4.
 *
 * Semua kotak input (Tab 1 & Tab 2) memakai pola "input langsung di
 * kotak, auto-save begitu pindah kotak" (lihat updated()) - TIDAK ADA
 * modal "Isi/Edit" terpisah (field-nya sedikit, cukup diedit langsung),
 * mengikuti pola terbaru PajakBospReguler - BUKAN pola modal RekapRkas
 * yang lebih lama.
 */
#[Layout('layouts.app')]
#[Title('Dana BOSP Tahap 1 & 2')]
class Index extends Component
{
    use HasZoomTampilan;

    public int $tahun;

    #[Url(as: 'tab')]
    public string $tabAktif = 'penerimaan';

    /**
     * Data untuk input LANGSUNG di tiap kotak (Tab 1 & Tab 2, kedua tab
     * berbagi array yang sama karena berasal dari 1 record per sekolah)
     * - array 2 dimensi [sekolahId][field] => nilai, diisi ulang tiap
     * render() dari data terbaru di database.
     *
     * @var array<int, array<string, string>>
     */
    public array $baris = [];

    /**
     * Dinaikkan PER SEKOLAH setiap kali ada input langsung yang ditolak
     * validasi - dipakai sebagai bagian wire:key kotak (wire:ignore)
     * sekolah itu, supaya kotaknya dipaksa kembali ke nilai database yang
     * benar (pola sama seperti RekapRkas/PajakBospReguler).
     *
     * @var array<int, int>
     */
    public array $revisiBaris = [];

    /**
     * ID sekolah (profil_sekolah_id) yang sedang dikonfirmasi untuk
     * dihapus SELURUH baris Dana BOSP Tahap-nya (tahun aktif) - lihat
     * konfirmasiHapus()/hapus(), pola sama seperti
     * PajakBospReguler::$confirmingHapusBulan.
     */
    public ?int $confirmingHapusSekolah = null;

    /**
     * BARU 2026-10-23 (permintaan user: "pada menu Dana BOSP Tahap 1 &
     * 2-Penerimaan BOSP-Tarik Tunai BOSP munculkan data sekolah yang
     * sudah lengkap inputannya dan yang belum lengkap inputannya dengan
     * cara klik nama sekolah nya") - diisi lewat
     * lihatDetailLengkapDanaBosp(), dipakai render()/blade (modal
     * "dana-bosp-lengkap-detail"). $kelompokLengkapDanaBospDipilih
     * menandai rincian field dari tab mana ('penerimaan'/'tarik_tunai')
     * yang sedang ditampilkan - KEDUA tab dicek TERPISAH (field manual
     * Tab 1 beda dari Tab 2), jadi 1 sekolah bisa "lengkap" di Tab 1
     * tapi "belum lengkap" di Tab 2, atau sebaliknya.
     */
    public ?string $sekolahLengkapDanaBospDipilih = null;

    public ?string $kelompokLengkapDanaBospDipilih = null;

    public function mount(): void
    {
        // Permintaan user 2026-10-24 (fitur Tahun Anggaran): Superadmin
        // TETAP bebas (tahun berjalan seperti sebelumnya, dropdown Tahun
        // di Blade tetap bisa diganti). Admin OPS/Admin BOSP TERKUNCI ke
        // tahun yang dipilih saat login (session 'tahun_sesi', diisi di
        // resources/views/livewire/pages/auth/pilih-tahun.blade.php) -
        // dropdown Tahun disembunyikan total untuk mereka di Blade (lihat
        // variabel $bolehKelolaSemua yang sudah dikirim ke view), sesuai
        // jawaban user lewat AskUserQuestion: "Dihapus/dikunci total ke
        // tahun sesi login". Fallback ke TahunAnggaranSetting jaga-jaga
        // kalau 'tahun_sesi' entah bagaimana belum terisi.
        $this->tahun = $this->bolehKelolaSemua()
            ? now()->year
            : (int) session('tahun_sesi', \App\Models\TahunAnggaranSetting::current()->tahun_aktif);
    }

    protected function bolehKelolaSemua(): bool
    {
        return auth()->user()->isSuperadmin();
    }

    protected function bolehEdit(int $profilSekolahId): bool
    {
        return $this->bolehKelolaSemua() || auth()->user()->profil_sekolah_id === $profilSekolahId;
    }

    /**
     * Label field manual Tab 1 "Penerimaan BOSP" (3 field, lihat
     * DanaBospTahap::FIELD_MANUAL_TAB1) - dipakai utk rincian field yang
     * belum diisi (badge klik nama sekolah, permintaan user 2026-10-23).
     *
     * @return array<string, string>
     */
    protected function labelFieldManualTab1(): array
    {
        return [
            'saldo_bosp_tahun_sebelumnya' => 'Saldo BOSP Tahun Sebelumnya',
            'jumlah_siswa' => 'Jumlah Siswa',
            'jumlah_dana_bosp_per_tahun' => 'Jumlah Dana BOSP Per Tahun',
        ];
    }

    /**
     * Label field manual Tab 2 "Tarik Tunai BOSP" (13 field, lihat
     * DanaBospTahap::FIELD_MANUAL_TAB2) - urutan HARUS sama dgn
     * FIELD_MANUAL_TAB2 supaya rincian field belum lengkap tampil runut
     * (Saldo BOSP TW 4 Tahun Sebelumnya, lalu TW 1-4: Tarik Tunai/Saldo
     * Kas Bank/Saldo Kas Tunai).
     *
     * @return array<string, string>
     */
    protected function labelFieldManualTab2(): array
    {
        $label = DanaBospTahap::LABEL_TAB2_TUNGGAL;

        foreach ([1, 2, 3, 4] as $tw) {
            $label["tarik_tunai_tw{$tw}"] = "Tarik Tunai TW {$tw}";
            $label["saldo_kas_bank_tw{$tw}"] = "Saldo Kas Bank TW {$tw}";
            $label["saldo_kas_tunai_tw{$tw}"] = "Saldo Kas Tunai TW {$tw}";
        }

        return $label;
    }

    /**
     * Ringkasan status "lengkap/belum lengkap" Dana BOSP Tahap per
     * sekolah UNTUK TAHUN YANG SEDANG DIPILIH ($this->tahun) - dipakai
     * utk badge nama sekolah yang bisa diklik (permintaan user
     * 2026-10-23). "Lengkap" Tab 1 = SEMUA field FIELD_MANUAL_TAB1
     * terisi; "Lengkap" Tab 2 = SEMUA field FIELD_MANUAL_TAB2 terisi -
     * DICEK TERPISAH (field hasil rumus TIDAK ikut disyaratkan, krn
     * otomatis dihitung sistem begitu field manualnya lengkap). Pola
     * gating sama dgn ProfilSekolah\Index::ringkasanProfilLengkap()
     * (Superadmin: SEMUA sekolah, Admin BOSP: HANYA sekolahnya sendiri).
     *
     * @return \Illuminate\Support\Collection<int, object{nama_sekolah: string, lengkapPenerimaan: bool, lengkapTarikTunai: bool}>
     */
    protected function ringkasanDanaBospLengkap(): \Illuminate\Support\Collection
    {
        $query = ProfilSekolah::with(['danaBospTahap' => function ($q) {
            $q->where('tahun', $this->tahun);
        }])->urutStandar();

        if (! $this->bolehKelolaSemua()) {
            $profilSekolahId = auth()->user()->profil_sekolah_id;

            if (! $profilSekolahId) {
                return collect();
            }

            $query->where('id', $profilSekolahId);
        }

        return $query->get()->map(function (ProfilSekolah $sekolah) {
            $data = $sekolah->danaBospTahap->first();

            $lengkapPenerimaan = true;
            foreach (DanaBospTahap::FIELD_MANUAL_TAB1 as $field) {
                $nilai = $data?->{$field};
                if ($nilai === null || $nilai === '') {
                    $lengkapPenerimaan = false;
                    break;
                }
            }

            $lengkapTarikTunai = true;
            foreach (DanaBospTahap::FIELD_MANUAL_TAB2 as $field) {
                $nilai = $data?->{$field};
                if ($nilai === null || $nilai === '') {
                    $lengkapTarikTunai = false;
                    break;
                }
            }

            return (object) [
                'nama_sekolah' => $sekolah->nama_sekolah,
                'lengkapPenerimaan' => $lengkapPenerimaan,
                'lengkapTarikTunai' => $lengkapTarikTunai,
            ];
        });
    }

    /**
     * Daftar nama field Dana BOSP Tahap yang BELUM diisi untuk 1
     * sekolah, dari kelompok ('penerimaan'/'tarik_tunai') yang dipilih -
     * dipakai utk isi modal detail yang dibuka lewat
     * lihatDetailLengkapDanaBosp().
     *
     * @return array<int, string>
     */
    public function detailFieldBelumLengkapDanaBosp(string $namaSekolah, string $kelompok): array
    {
        $sekolah = ProfilSekolah::with(['danaBospTahap' => function ($q) {
            $q->where('tahun', $this->tahun);
        }])->where('nama_sekolah', $namaSekolah)->first();

        if (! $sekolah) {
            return [];
        }

        $data = $sekolah->danaBospTahap->first();

        $daftarField = $kelompok === 'tarik_tunai' ? DanaBospTahap::FIELD_MANUAL_TAB2 : DanaBospTahap::FIELD_MANUAL_TAB1;
        $label = $kelompok === 'tarik_tunai' ? $this->labelFieldManualTab2() : $this->labelFieldManualTab1();

        $fieldBelumLengkap = [];

        foreach ($daftarField as $field) {
            $nilai = $data?->{$field};
            if ($nilai === null || $nilai === '') {
                $fieldBelumLengkap[] = $label[$field] ?? $field;
            }
        }

        return $fieldBelumLengkap;
    }

    public function lihatDetailLengkapDanaBosp(string $namaSekolah, string $kelompok): void
    {
        $this->sekolahLengkapDanaBospDipilih = $namaSekolah;
        $this->kelompokLengkapDanaBospDipilih = $kelompok;
        $this->dispatch('open-modal', 'dana-bosp-lengkap-detail');
    }

    public function tutupDetailLengkapDanaBosp(): void
    {
        $this->sekolahLengkapDanaBospDipilih = null;
        $this->kelompokLengkapDanaBospDipilih = null;
        $this->dispatch('close-modal', 'dana-bosp-lengkap-detail');
    }

    public function pindahTab(string $tab): void
    {
        if (! in_array($tab, ['penerimaan', 'tarik_tunai'], true)) {
            return;
        }

        $this->tabAktif = $tab;
    }

    /**
     * Field yang boleh diedit LANGSUNG lewat kotak (3 manual Tab 1 + 13
     * manual Tab 2) - TIDAK termasuk 3 field hasil rumus Tab 1
     * (total_penerimaan_setahun, penerimaan_tahap_1, penerimaan_tahap_2)
     * maupun 4 field hasil rumus Tab 2 (saldo_tw1-4, Part 31), yang selalu
     * dihitung otomatis lewat DanaBospTahap::hitungRumusTab1()/hitungSaldoTw().
     */
    protected function daftarFieldManual(): array
    {
        return array_merge(DanaBospTahap::FIELD_MANUAL_TAB1, DanaBospTahap::FIELD_MANUAL_TAB2);
    }

    /**
     * SELURUH field (manual + hasil rumus) - dipakai untuk mengisi $baris
     * supaya kolom hasil rumus Tab 1 & Tab 2 juga tampil (walau read-only).
     */
    protected function daftarFieldSemua(): array
    {
        return array_merge(
            DanaBospTahap::FIELD_MANUAL_TAB1,
            DanaBospTahap::FIELD_RUMUS_TAB1,
            DanaBospTahap::FIELD_MANUAL_TAB2,
            DanaBospTahap::FIELD_RUMUS_TAB2,
        );
    }

    /**
     * Menangkap perubahan pada kotak input langsung (property
     * "baris.{sekolahId}.{field}") - begitu 1 kotak kehilangan fokus,
     * nilainya divalidasi & langsung disimpan (updateOrCreate, baris
     * otomatis dibuat kalau belum ada untuk sekolah+tahun ini).
     */
    public function updated(string $name, mixed $value): void
    {
        if (! str_starts_with($name, 'baris.')) {
            return;
        }

        $bagian = explode('.', $name);

        if (count($bagian) !== 3) {
            return;
        }

        [, $sekolahIdMentah, $field] = $bagian;
        $sekolahId = (int) $sekolahIdMentah;

        if (! in_array($field, $this->daftarFieldManual(), true)) {
            // Termasuk menolak percobaan set langsung ke 3 field hasil
            // rumus Tab 1 (total_penerimaan_setahun, penerimaan_tahap_1,
            // penerimaan_tahap_2) dari jalur manapun di luar UI normal -
            // nilainya SELALU hasil hitung, tidak bisa ditimpa manual.
            return;
        }

        abort_unless($this->bolehEdit($sekolahId), 403);

        $validator = Validator::make(['nilai' => $value], ['nilai' => ['nullable', 'integer', 'min:0']]);

        if ($validator->fails()) {
            $this->addError($name, 'Harus berupa angka.');
            $this->revisiBaris[$sekolahId] = ($this->revisiBaris[$sekolahId] ?? 0) + 1;

            return;
        }

        $nilai = $value === '' || $value === null ? null : (int) $value;

        $data = [$field => $nilai];

        // Kalau field yang diedit adalah Jumlah Siswa atau Jumlah Dana
        // BOSP Per Tahun (2 dari 3 sumber rumus Tab 1), rumus Total/
        // Tahap 1/Tahap 2 ikut dihitung ulang & disimpan sekalian di
        // update yang sama (lihat DanaBospTahap::hitungRumusTab1()).
        if (in_array($field, ['jumlah_siswa', 'jumlah_dana_bosp_per_tahun'], true)) {
            $existing = DanaBospTahap::where('profil_sekolah_id', $sekolahId)
                ->where('tahun', $this->tahun)
                ->first();

            $jumlahSiswa = $field === 'jumlah_siswa' ? $nilai : $existing?->jumlah_siswa;
            $jumlahDana = $field === 'jumlah_dana_bosp_per_tahun' ? $nilai : $existing?->jumlah_dana_bosp_per_tahun;

            [$total, $tahap1, $tahap2] = DanaBospTahap::hitungRumusTab1($jumlahSiswa, $jumlahDana);

            $data['total_penerimaan_setahun'] = $total;
            $data['penerimaan_tahap_1'] = $tahap1;
            $data['penerimaan_tahap_2'] = $tahap2;

            // "Terisi" di sini SAMA PERSIS dengan syarat yang dipakai menu
            // Rekap RKAS untuk membuka/mengunci baris sekolah ini (lihat
            // Livewire\PendataanBosp\RekapRkas\Index::danaBospTahapTerisiDari()) -
            // WAJIB dijaga sama supaya database selalu konsisten dengan
            // status terkunci/terbuka yang tampil di layar menu itu.
            $danaBospTahapTerisi = $jumlahSiswa !== null && $jumlahDana !== null;

            if ($danaBospTahapTerisi) {
                // Sinkronkan kolom rekap_rkas.anggaran_bosp (permintaan user
                // 2026-09-23: field itu sekarang otomatis mengikuti Total
                // Penerimaan BOSP Setahun di sini) KALAU sekolah ini sudah
                // punya baris Rekap RKAS untuk tahun yang sama - sengaja TIDAK
                // membuat baris baru di sini (updateOrCreate) supaya gate
                // "wajib isi Dana BOSP Tahap dulu" di menu Rekap RKAS
                // tidak diam-diam dilewati dari jalur ini.
                RekapRkas::where('profil_sekolah_id', $sekolahId)
                    ->where('tahun', $this->tahun)
                    ->update(['anggaran_bosp' => RekapRkas::anggaranBospOtomatis($total)]);
            } else {
                // PENTING (permintaan user 2026-09-26, ditemukan lewat hasil
                // Unduh Excel Rekap RKAS yang menampilkan data lama sekolah
                // yang sudah terkunci): begitu Jumlah Siswa atau Jumlah Dana
                // BOSP Per Tahun DIKOSONGKAN LAGI (sekolah ini jadi TIDAK
                // terisi/terkunci lagi di menu Rekap RKAS), baris Rekap RKAS
                // sekolah+tahun ini HARUS ikut dihapus TOTAL dari database
                // (jawaban AskUserQuestion: "Ya, otomatis ikut dibersihkan" +
                // "Hapus barisnya total") - BUKAN cuma disembunyikan di layar
                // seperti sebelumnya, supaya data di database selalu sama
                // dengan yang terlihat di aplikasi (tidak ada data "nyangkut"
                // dari sekolah yang sudah terkunci). Kalau sekolah ini nanti
                // mengisi Dana BOSP Tahap lagi, Rekap RKAS-nya mulai dari nol
                // lagi (bukan muncul kembali data lama).
                RekapRkas::where('profil_sekolah_id', $sekolahId)
                    ->where('tahun', $this->tahun)
                    ->delete();
            }
        }

        // Kalau field yang diedit adalah Saldo Kas Bank atau Saldo Kas
        // Tunai TW manapun (Tab 2, Part 31), Saldo TW terkait ikut
        // dihitung ulang & disimpan sekalian di update yang sama (pola
        // sama seperti rumus Tab 1 di atas - lihat DanaBospTahap::hitungSaldoTw()).
        if (preg_match('/^saldo_kas_(bank|tunai)_tw([1-4])$/', $field, $cocok) === 1) {
            $tw = $cocok[2];

            $existing = DanaBospTahap::where('profil_sekolah_id', $sekolahId)
                ->where('tahun', $this->tahun)
                ->first();

            $kasBank = $field === "saldo_kas_bank_tw{$tw}" ? $nilai : $existing?->{"saldo_kas_bank_tw{$tw}"};
            $kasTunai = $field === "saldo_kas_tunai_tw{$tw}" ? $nilai : $existing?->{"saldo_kas_tunai_tw{$tw}"};

            $data["saldo_tw{$tw}"] = DanaBospTahap::hitungSaldoTw($kasBank, $kasTunai);
        }

        $baris = DanaBospTahap::updateOrCreate(
            ['profil_sekolah_id' => $sekolahId, 'tahun' => $this->tahun],
            array_merge($data, ['created_by' => auth()->id()])
        );

        // Baris "kosong total" (semua 23 field data kembali null - mis.
        // user sempat mengetik lalu menghapus lagi) tidak boleh ikut
        // tertinggal di database, karena masih terhitung "sekolah ini
        // sudah punya data" pada laporan/pengecekan konsistensi lain
        // (permintaan user 2026-09-27).
        if ($baris->semuaFieldDataKosong()) {
            $baris->delete();
        }

        $this->revisiBaris[$sekolahId] = ($this->revisiBaris[$sekolahId] ?? 0) + 1;
    }

    /**
     * Konfirmasi hapus SELURUH baris Dana BOSP Tahap sekolah aktif+tahun
     * aktif (Delete penuh, bukan cuma mengosongkan 1 kotak) - menyediakan
     * operasi "Delete" yang eksplisit selain auto-hapus baris kosong di
     * atas, mengikuti pola PajakBospReguler::konfirmasiHapusBulan().
     */
    public function konfirmasiHapus(int $sekolahId): void
    {
        if (! $this->bolehEdit($sekolahId)) {
            return;
        }

        $this->confirmingHapusSekolah = $sekolahId;
        $this->dispatch('open-modal', 'dana-bosp-tahap-hapus');
    }

    public function batalHapus(): void
    {
        $this->confirmingHapusSekolah = null;
        $this->dispatch('close-modal', 'dana-bosp-tahap-hapus');
    }

    public function hapus(): void
    {
        $sekolahId = $this->confirmingHapusSekolah;

        if ($sekolahId && $this->bolehEdit($sekolahId)) {
            DanaBospTahap::where('profil_sekolah_id', $sekolahId)
                ->where('tahun', $this->tahun)
                ->delete();

            // Dana BOSP Tahap kosong -> Rekap RKAS ikut terhapus (aturan
            // sama seperti updated() di atas, permintaan user 2026-09-26)
            // - dijaga konsisten di sini supaya hapus manual juga
            // membersihkan Rekap RKAS terkait, bukan hanya lewat
            // pengosongan kotak Jumlah Siswa/Jumlah Dana BOSP Per Tahun.
            RekapRkas::where('profil_sekolah_id', $sekolahId)
                ->where('tahun', $this->tahun)
                ->delete();

            $this->revisiBaris[$sekolahId] = ($this->revisiBaris[$sekolahId] ?? 0) + 1;
            session()->flash('status', 'Data Dana BOSP Tahap sekolah ini berhasil dihapus.');
        }

        $this->confirmingHapusSekolah = null;
        $this->dispatch('close-modal', 'dana-bosp-tahap-hapus');
    }

    public function render()
    {
        $query = ProfilSekolah::with(['danaBospTahap' => function ($q) {
            $q->where('tahun', $this->tahun);
        }]);

        if (! $this->bolehKelolaSemua()) {
            $query->where('id', auth()->user()->profil_sekolah_id);
        }

        $daftarSekolah = $query
            ->urutStandar()
            ->get()
            ->map(function ($sekolah) {
                $sekolah->danaBospTahunIni = $sekolah->danaBospTahap->first();

                return $sekolah;
            });

        // Isi ulang $baris dari data ter-terbaru database setiap kali
        // render() dipanggil - juga membuat kotak kembali ke nilai
        // semula kalau updated() di atas menolak input tidak valid.
        foreach ($daftarSekolah as $sekolah) {
            $data = $sekolah->danaBospTahunIni;
            $this->baris[$sekolah->id] = [];

            foreach ($this->daftarFieldSemua() as $field) {
                $this->baris[$sekolah->id][$field] = $data?->{$field} !== null ? (string) $data->{$field} : '';
            }
        }

        $tahunOptions = range(now()->year - 2, now()->year + 1);

        // Baris "Jumlah" (total) Tab 1 - sum seluruh sekolah yang sedang
        // tampil, KECUALI "jumlah_dana_bosp_per_tahun" (angka per siswa/
        // tahun, BUKAN kuantitas yang bisa dijumlahkan antar sekolah -
        // sama seperti kolom "Nama Sekolah" tidak pernah dijumlahkan).
        $totalTab1 = [];
        foreach (array_merge(DanaBospTahap::FIELD_MANUAL_TAB1, DanaBospTahap::FIELD_RUMUS_TAB1) as $field) {
            if ($field === 'jumlah_dana_bosp_per_tahun') {
                continue;
            }
            $totalTab1[$field] = $daftarSekolah->sum(function ($sekolah) use ($field) {
                return (int) ($sekolah->danaBospTahunIni?->{$field} ?? 0);
            });
        }

        // Baris "Jumlah" Tab 2 - sum seluruh sekolah (semua field Tab 2,
        // termasuk Saldo Kas Bank/Tunai/Saldo TW 1-4 Part 31, memang
        // kuantitas Rupiah yang bisa dijumlahkan).
        $totalTab2 = [];
        foreach (array_merge(DanaBospTahap::FIELD_MANUAL_TAB2, DanaBospTahap::FIELD_RUMUS_TAB2) as $field) {
            $totalTab2[$field] = $daftarSekolah->sum(function ($sekolah) use ($field) {
                return (int) ($sekolah->danaBospTahunIni?->{$field} ?? 0);
            });
        }

        return view('livewire.pendataan-bosp.dana-bosp-tahap.index', [
            'daftarSekolah' => $daftarSekolah,
            // BARU 2026-10-23 (permintaan user) - ringkasan & rincian
            // "lengkap/belum lengkap" Dana BOSP Tahap, lihat
            // ringkasanDanaBospLengkap()/lihatDetailLengkapDanaBosp() di atas.
            'ringkasanDanaBospLengkap' => $this->ringkasanDanaBospLengkap(),
            'sekolahLengkapDanaBospDipilih' => $this->sekolahLengkapDanaBospDipilih,
            'kelompokLengkapDanaBospDipilih' => $this->kelompokLengkapDanaBospDipilih,
            'detailFieldBelumLengkapDanaBosp' => $this->sekolahLengkapDanaBospDipilih
                ? $this->detailFieldBelumLengkapDanaBosp($this->sekolahLengkapDanaBospDipilih, $this->kelompokLengkapDanaBospDipilih)
                : [],
            'tahunOptions' => array_reverse($tahunOptions),
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            'totalTab1' => $totalTab1,
            'totalTab2' => $totalTab2,
            'labelTab2Tunggal' => DanaBospTahap::LABEL_TAB2_TUNGGAL,
        ]);
    }
}
