<?php

namespace App\Livewire\PendataanBosp\LaporanRealisasiBosp;

use App\Livewire\Concerns\HasZoomTampilan;
use App\Models\DanaBospTahap;
use App\Models\LaporanRealisasiBosp;
use App\Models\ProfilSekolah;
use App\Models\VervalRealisasiBosp;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Laporan Realisasi BOSP (Form BPK) - Pendataan BOSP (permintaan user
 * 2026-09-17, Part 32). Posisi sidebar: paling akhir, sesudah "Pajak
 * BOSP Reguler".
 *
 * 5 tab UI:
 * - Tab "tw1"-"tw4" (Laporan Realisasi TW 1-4): tabel wide biasa, 1
 *   baris per sekolah PER TRIWULAN itu (pola sama seperti RekapRkas -
 *   "input langsung di kotak, auto-save begitu pindah kotak", TIDAK ADA
 *   modal terpisah). Superadmin: seluruh sekolah. Admin BOSP: sekolah
 *   sendiri saja.
 * - Tab "rekap" (Rekapitulasi Laporan Realisasi Tahun Anggaran,
 *   otomatis): READ-ONLY, menampilkan RINCIAN 4 TW + baris "Jumlah" PER
 *   SEKOLAH (jawaban AskUserQuestion 2026-09-17 "Rincian 4 TW + Jumlah
 *   per sekolah") - dihitung otomatis dari 4 baris TW yang sudah ada di
 *   tab 1-4, TIDAK menyimpan data sendiri & tidak ada kotak edit.
 *
 * PENTING - field manual vs hasil rumus (lihat App\Models\LaporanRealisasiBosp):
 * SELURUH kolom 8-29 SEKARANG SUDAH JADI HASIL RUMUS/read-only sejak
 * permintaan user 2026-09-17 (lanjutan Part 32 ketujuh, yang terakhir
 * menyusul kolom 8-10) - TIDAK ADA LAGI kolom manapun yang bisa diedit
 * manual dari kotak tabel pada menu ini. Kolom 8 (Saldo Awal Dana BOSP),
 * kolom 9 (Penerimaan Dana BOS), & kolom 10 (Total Penerimaan) - yang
 * SEBELUMNYA "menunggu instruksi rumus selanjutnya" - SEKARANG rumusnya
 * SUDAH ditentukan lengkap oleh user (lihat rincian lengkap di
 * App\Models\LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN &
 * hitungRantaiPenerimaanDanSisaSemuaTriwulan()): kolom 8 SATU-SATUNYA
 * kolom yang rumusnya MEREKURSI ANTAR TRIWULAN (Saldo Awal TW n = Sisa
 * Dana BOS TW n-1, kecuali TW1 dari App\Models\DanaBospTahap), kolom 9
 * dari DanaBospTahap (Penerimaan Tahap 1/2, TW2 & TW4 SELALU 0 - TIDAK
 * ADA penerimaan Dana BOSP pada TW itu), kolom 10 = kolom 8 + kolom 9.
 * KARENA method updated() di bawah mengecek FIELD_KOMPUTASI_RINCIAN
 * SEBELUM menyimpan (lihat method tsb), method ini SEKARANG TIDAK PERNAH
 * lagi benar-benar memanggil `LaporanRealisasiBosp::updateOrCreate()` -
 * kode itu TETAP dipertahankan (bukan dihapus) sebagai infrastruktur
 * vestigial, jaga-jaga kalau ada kolom manual baru ditambahkan lagi di
 * masa depan pada tabel ini.
 *
 * Kolom 11-27 (Belanja Barang Pakai Habis/Persediaan s.d. Saldo Kas
 * Tunai) & kolom 28-29 (Jumlah, Verifikasi Saldo) SUDAH SEMUA jadi HASIL
 * RUMUS/read-only sejak permintaan user 2026-09-17 (lanjutan Part 32,
 * Part 32 kedua, ketiga, keempat, kelima, & keenam) - lihat rincian
 * lengkap di bawah & di App\Models\LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN.
 * TIDAK PERNAH bisa diedit manual dari jalur manapun. Kolom 26-27 (Saldo
 * Rekening/Kas Bank, Saldo Kas Tunai) BARU pindah jadi otomatis pada
 * ronde ini (lanjutan Part 32 keenam) - diambil dari
 * App\Models\DanaBospTahap (menu Dana BOSP Tahap 1 & 2, tab "Tarik
 * Tunai BOSP"), BUKAN dari SUM menu rincian seperti kolom 11-22.
 *
 * Kolom 29 (Verifikasi Saldo) - BERUBAH sejak permintaan user 2026-09-17
 * (lanjutan Part 32 ketujuh, poin 1): kata "SAMA"/"TIDAK SAMA" BELUM
 * ditampilkan (nilainya `null`, blade menampilkan "-") selama kolom 26
 * (Saldo Rekening/Kas Bank) ATAU kolom 27 (Saldo Kas Tunai) masih
 * `null` (belum tersimpan) UNTUK TRIWULAN/SEKOLAH YBS - lihat
 * App\Models\LaporanRealisasiBosp::hitungVerifikasiSaldo(). Jawaban
 * AskUserQuestion 2026-09-17 (poin 1, pertanyaan ke-2): aturan
 * sembunyikan ini IKUT DICASCADE ke baris "JUMLAH" (footer tab TW1-4),
 * baris "Jumlah" per sekolah (tab rekap), & baris total 1 tahun anggaran
 * (poin 2 di bawah) - kalau ADA SATU SAJA triwulan/sekolah yang datanya
 * belum lengkap dalam suatu baris agregat, verifikasi_saldo baris
 * agregat itu JUGA ikut disembunyikan (null), TIDAK dihitung dari hasil
 * SUM begitu saja (SUM akan salah menganggap null sebagai 0) - lihat
 * renderTabTriwulan()/renderTabRekap() di bawah untuk implementasi
 * deteksi "ada yang belum lengkap" ini.
 *
 * Baris total 1 tahun anggaran (Rekapitulasi Tahun Anggaran otomatis,
 * tab "rekap") - kolom baru ditambahkan SETELAH baris terakhir tabel,
 * SELALU tampil (tidak collapsible seperti baris per-sekolah), kolom
 * 1-7 di-merge jadi 1 sel label "JUMLAH TAHUN ANGGARAN {tahun}" -
 * permintaan user 2026-09-17 (lanjutan Part 32 ketujuh, poin 2). Dihitung
 * dari SUM baris "Jumlah" SETIAP sekolah (bukan query baru) - lihat
 * renderTabRekap() di bawah (`$totalTahunAnggaran`, dikembalikan bareng
 * `$hasil` & diteruskan ke view lewat render()).
 *
 * Kolom 28 (Jumlah = Saldo Rekening/Kas Bank + Saldo Kas Tunai) & kolom
 * 29 (Verifikasi Saldo: "SAMA"/"TIDAK SAMA" dibanding Sisa Dana BOS) -
 * rumusnya sudah ditentukan eksplisit oleh user (jawaban AskUserQuestion
 * 2026-09-17, Part 32 awal). BERUBAH sejak permintaan user 2026-09-17
 * (lanjutan Part 32 kelima, jawaban AskUserQuestion "Real-time setiap
 * render"): SEBELUMNYA disimpan/dimaterialize ke database & hanya
 * dihitung ulang saat kolom 25/26/27 diedit manual - SEKARANG SELALU
 * dihitung ulang REAL-TIME setiap render lewat
 * LaporanRealisasiBosp::ambilVerifikasiSaldo()/...SemuaTriwulan() (TIDAK
 * PERNAH disimpan lagi ke database), KARENA kolom 25 (Sisa Dana BOS,
 * salah satu inputnya) sendiri sekarang jadi hasil rumus otomatis yang
 * tidak lagi pernah diedit manual - lihat catatan lengkap di
 * App\Models\LaporanRealisasiBosp::FIELD_RUMUS.
 *
 * Baris "Jumlah" pada tab "rekap" untuk kolom 28/29 dihitung dari HASIL
 * PENJUMLAHAN kolom 25/26/27 keempat TW terlebih dahulu, baru
 * dibandingkan lewat rumus yang sama (hitungVerifikasiSaldo) - keputusan
 * teknis (bukan aturan bisnis baru) supaya baris Jumlah tetap konsisten
 * dengan rumus kolom 28/29 yang sudah ditentukan user, didokumentasikan
 * eksplisit di sini sebagai asumsi yang diambil.
 *
 * Kolom 11 (Belanja Barang Pakai Habis/Persediaan) s.d. kolom 19
 * (Perjalanan Dinas) - PENGECUALIAN KETIGA s.d. KESEBELAS dari
 * "rumus-rumus nya nanti menyusul" (permintaan user 2026-09-17 lanjutan
 * Part 32, lanjutan Part 32 kedua, lanjutan Part 32 ketiga, & lanjutan
 * Part 32 keempat): nilainya diambil dari total SUM kolom sumber pada
 * menu masing-masing (kolom 11 <- Rincian Belanja Barang Habis Pakai
 * "Total Harga", kolom 12 <- Penerimaan Honor PTK "Jumlah Honor Yang
 * Diterima", kolom 13 <- Daya & Jasa "Jumlah", kolom 14 <- Rincian
 * Pemeliharaan "Total Harga" jenis barang + Rincian Pemeliharaan PC dll
 * "Total Harga" jenis barang DIJUMLAHKAN, kolom 15 <- pola sama kolom 14
 * tapi jenis jasa, kolom 16 <- Biaya Pendaftaran Lomba/Bimtek/Workshop
 * "Jumlah", kolom 17/18/19 <- Belanja Honor Kegiatan & Makan Minum
 * Kegiatan & Perjalanan Dinas "Jumlah" difilter `jenis` masing-masing
 * tab utama, SATU menu sumber TIDAK dijumlah lintas `jenis`), per
 * sekolah+tahun+triwulan yang sama.
 *
 * Kolom 20 (Total Belanja Barang dan Jasa), kolom 21 (Peralatan dan
 * Mesin KIB B), kolom 22 (Aset Tetap Lainnya KIB E), kolom 23 (Total
 * Belanja Modal), kolom 24 (Total Realisasi Dana BOS), & kolom 25 (Sisa
 * Dana BOS) - PENGECUALIAN KESEBELAS s.d. KEENAM BELAS, permintaan user
 * 2026-09-17 (lanjutan Part 32 kelima): kolom 21/22 dari 1 menu sumber
 * eksternal (Rincian Belanja Modal, difilter `jenis`), kolom
 * 20/23/24/25 dari RUMUS GABUNGAN kolom-kolom lain (lihat rincian
 * lengkap di App\Models\LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN -
 * PENTING: kolom 20 SENGAJA TIDAK mengikutsertakan kolom 11).
 *
 * Kolom 26 (Saldo Rekening/Kas Bank) & kolom 27 (Saldo Kas Tunai) -
 * PENGECUALIAN KESEMBILAN BELAS & KEDUA PULUH, permintaan user
 * 2026-09-17 (lanjutan Part 32 keenam): diambil OTOMATIS dari
 * App\Models\DanaBospTahap (field saldo_kas_bank_tw{n}/
 * saldo_kas_tunai_tw{n}, menu Dana BOSP Tahap 1 & 2 tab "Tarik Tunai
 * BOSP"), per sekolah+tahun yang SAMA (DanaBospTahap tidak punya kolom
 * triwulan - lihat App\Models\LaporanRealisasiBosp::ambilTotalSaldoRekeningKasBank()/
 * ambilTotalSaldoKasTunai()). SEBELUMNYA kedua kolom ini manual - jadi
 * otomatis BARU pada ronde ini.
 *
 * SELURUH kolom 11-27 di atas (lihat
 * App\Models\LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN &
 * ambilTotalKomputasiRincian()/...SemuaTriwulan()) READ-ONLY (tidak
 * bisa diedit manual, jawaban AskUserQuestion) & DIHITUNG REAL-TIME
 * setiap render (TIDAK disimpan ke kolom database - jawaban
 * AskUserQuestion) - jadi otomatis ikut berubah begitu data di menu
 * sumber terkait ATAUPUN kolom lain yang jadi inputnya
 * ditambah/diubah/dihapus, tanpa perlu logic tambahan apapun di menu
 * sumber itu maupun di Livewire component ini (loop generik atas
 * FIELD_KOMPUTASI_RINCIAN di renderTabTriwulan()/renderTabRekap() di
 * bawah TIDAK PERNAH berubah untuk menambah kolom baru manapun sejak
 * lanjutan Part 32 kedua - hanya App\Models\LaporanRealisasiBosp yang
 * bertambah).
 */
#[Layout('layouts.app')]
#[Title('Laporan Realisasi BOSP (Form BPK)')]
class Index extends Component
{
    use HasZoomTampilan;

    public int $tahun;

    #[Url(as: 'tab')]
    public string $tabAktif = 'tw1';

    /**
     * Pemetaan tab TW -> nomor triwulan yang difilter pada tab itu.
     */
    public const TAB_TRIWULAN = [
        'tw1' => 1,
        'tw2' => 2,
        'tw3' => 3,
        'tw4' => 4,
    ];

    /**
     * Data untuk input LANGSUNG di tiap kotak (tab tw1-tw4 saja - tab
     * "rekap" read-only tidak memakai property ini) - array 2 dimensi
     * [sekolahId][field] => nilai, diisi ulang tiap render() dari data
     * ter-terbaru di database.
     *
     * @var array<int, array<string, string>>
     */
    public array $baris = [];

    /**
     * Dinaikkan PER SEKOLAH setiap kali ada input langsung yang ditolak
     * validasi - dipakai sebagai bagian wire:key kotak (wire:ignore)
     * sekolah itu, supaya kotaknya dipaksa kembali ke nilai database
     * yang benar (pola sama seperti RekapRkas/DanaBospTahap).
     *
     * @var array<int, int>
     */
    public array $revisiBaris = [];

    public function mount(): void
    {
        $this->tahun = now()->year;

        if ($this->tarikTunaiBospBelumLengkap()) {
            $this->dispatch('tarik-tunai-bosp-belum-lengkap');
        }
    }

    /**
     * Permintaan user 2026-09-23 (item #8 dari batch 8 permintaan): popup
     * notifikasi mengingatkan Admin BOSP kalau field Saldo Rekening/Kas
     * Bank ATAU Saldo Kas Tunai (kolom 26/27, diambil otomatis dari
     * App\Models\DanaBospTahap tab "Tarik Tunai BOSP" - lihat docblock
     * kelas di atas) MASIH BELUM diisi untuk SALAH SATU dari 4 triwulan
     * tahun berjalan, pada sekolah Admin BOSP itu sendiri.
     *
     * HANYA dicek untuk Admin BOSP (Superadmin tidak punya "sekolah
     * sendiri" & melihat seluruh sekolah, jadi popup ini tidak relevan
     * baginya - pesannya sendiri eksplisit ditujukan "Admin BOSP").
     * HANYA dipanggil dari mount() (jawaban AskUserQuestion 2026-09-23
     * "Sekali saat tab dibuka") - TIDAK dicek ulang tiap kali komponen
     * re-render (mis. saat pindah tab TW/pindah tahun), supaya popup
     * tidak muncul berulang-ulang mengganggu.
     */
    protected function tarikTunaiBospBelumLengkap(): bool
    {
        if ($this->bolehKelolaSemua()) {
            return false;
        }

        $profilSekolahId = auth()->user()->profil_sekolah_id;

        if ($profilSekolahId === null) {
            return false;
        }

        $danaBospTahap = DanaBospTahap::where('profil_sekolah_id', $profilSekolahId)
            ->where('tahun', $this->tahun)
            ->first();

        if ($danaBospTahap === null) {
            return true;
        }

        foreach ([1, 2, 3, 4] as $triwulan) {
            if ($danaBospTahap->{'saldo_kas_bank_tw'.$triwulan} === null
                || $danaBospTahap->{'saldo_kas_tunai_tw'.$triwulan} === null) {
                return true;
            }
        }

        return false;
    }

    protected function bolehKelolaSemua(): bool
    {
        return auth()->user()->isSuperadmin();
    }

    protected function bolehEdit(int $profilSekolahId): bool
    {
        return $this->bolehKelolaSemua() || auth()->user()->profil_sekolah_id === $profilSekolahId;
    }

    public function pindahTab(string $tab): void
    {
        if (! in_array($tab, ['tw1', 'tw2', 'tw3', 'tw4', 'rekap'], true)) {
            return;
        }

        $this->tabAktif = $tab;
    }

    /**
     * Menangkap perubahan pada kotak input langsung (property
     * "baris.{sekolahId}.{field}") - begitu 1 kotak kehilangan fokus,
     * nilainya divalidasi & langsung disimpan (updateOrCreate, baris
     * otomatis dibuat kalau belum ada untuk sekolah+tahun+triwulan ini).
     * Hanya berlaku pada tab tw1-tw4 (tab "rekap" tidak punya kotak
     * input, lihat blade view).
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

        if (! in_array($field, LaporanRealisasiBosp::FIELD_MANUAL, true)) {
            // Termasuk menolak percobaan set langsung ke kolom 28/29
            // (verifikasi_jumlah, verifikasi_saldo) dari jalur manapun
            // di luar UI normal - nilainya SELALU hasil hitung.
            return;
        }

        if (in_array($field, LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN, true)) {
            // Kolom 8-10 (saldo_awal_dana_bosp, penerimaan_dana_bos,
            // total_penerimaan - BARU ikut ke sini sejak permintaan user
            // 2026-09-17 lanjutan Part 32 ketujuh, INI ADALAH KOLOM MANUAL
            // TERAKHIR di menu ini) & kolom 11-27
            // (belanja_barang_pakai_habis_persediaan,
            // jasa_tenaga_pendidik_dan_kependidikan, daya_dan_jasa,
            // pemeliharaan, upah_pemeliharaan, biaya_pendaftaran_lomba_bimtek_workshop,
            // honor_kegiatan, makan_dan_minum_kegiatan, perjalanan_dinas,
            // total_belanja_barang_dan_jasa, peralatan_dan_mesin_kib_b,
            // aset_tetap_lainnya_kib_e, total_belanja_modal,
            // total_realisasi_dana_bos, sisa_dana_bos, saldo_rekening_kas_bank,
            // saldo_kas_tunai) -
            // masih "terdaftar" di FIELD_MANUAL demi urutan/jumlah kolom
            // tabel, TAPI sudah read-only (nilainya diambil/dihitung real-time
            // dari menu sumber masing-masing atau dari kolom komputasi lain -
            // lihat daftar lengkap & rumus masing-masing pada catatan
            // FIELD_KOMPUTASI_RINCIAN di App\Models\LaporanRealisasiBosp).
            // Tolak percobaan set langsung dari jalur manapun. SEJAK
            // SELURUH FIELD_PENERIMAAN ikut pindah ke sini, kondisi ini
            // SELALU true untuk SETIAP anggota FIELD_MANUAL - baris
            // updateOrCreate() di bawah jadi TIDAK PERNAH lagi tereksekusi
            // (vestigial, TETAP dipertahankan jaga-jaga kolom manual baru
            // ditambahkan lagi di masa depan).
            return;
        }

        $triwulan = self::TAB_TRIWULAN[$this->tabAktif] ?? null;

        if ($triwulan === null) {
            // Tab "rekap" tidak seharusnya mengirim event ini (tidak ada
            // kotak input di blade-nya) - jaga-jaga saja.
            return;
        }

        abort_unless($this->bolehEdit($sekolahId), 403);

        $validator = Validator::make(['nilai' => $value], ['nilai' => ['nullable', 'integer']]);

        if ($validator->fails()) {
            $this->addError($name, 'Harus berupa angka.');
            $this->revisiBaris[$sekolahId] = ($this->revisiBaris[$sekolahId] ?? 0) + 1;

            return;
        }

        $nilai = $value === '' || $value === null ? null : (int) $value;

        $data = [$field => $nilai];

        // Kolom 28/29 (verifikasi_jumlah/verifikasi_saldo) TIDAK LAGI
        // dihitung & disimpan di sini sejak permintaan user 2026-09-17
        // (lanjutan Part 32 kelima) - kolom 25 (sisa_dana_bos, salah
        // satu input rumusnya) sekarang jadi hasil rumus otomatis
        // (FIELD_KOMPUTASI_RINCIAN), sehingga kolom 28/29 ikut dihitung
        // REAL-TIME setiap render lewat
        // LaporanRealisasiBosp::ambilVerifikasiSaldo()/...SemuaTriwulan()
        // (lihat renderTabTriwulan()/renderTabRekap() di bawah), TIDAK
        // PERNAH lagi ditulis ke database dari jalur manapun.
        LaporanRealisasiBosp::updateOrCreate(
            ['profil_sekolah_id' => $sekolahId, 'tahun' => $this->tahun, 'triwulan' => $triwulan],
            array_merge($data, ['created_by' => auth()->id()])
        );

        $this->revisiBaris[$sekolahId] = ($this->revisiBaris[$sekolahId] ?? 0) + 1;
    }

    /**
     * SELURUH field (manual + hasil rumus) - dipakai untuk mengisi
     * $baris supaya kolom 28/29 juga tampil (walau read-only).
     */
    protected function daftarFieldSemua(): array
    {
        return array_merge(LaporanRealisasiBosp::FIELD_MANUAL, LaporanRealisasiBosp::FIELD_RUMUS);
    }

    /**
     * Data untuk tab tw1-tw4 - 1 baris per sekolah, difilter ke triwulan
     * tab yang aktif.
     */
    protected function renderTabTriwulan(int $triwulan): array
    {
        $query = ProfilSekolah::with(['laporanRealisasiBosp' => function ($q) use ($triwulan) {
            $q->where('tahun', $this->tahun)->where('triwulan', $triwulan);
        }]);

        if (! $this->bolehKelolaSemua()) {
            $query->where('id', auth()->user()->profil_sekolah_id);
        }

        $daftarSekolah = $query
            ->orderByRaw("CASE WHEN status = 'negeri' THEN 0 WHEN status = 'swasta' THEN 1 ELSE 2 END")
            ->orderByRaw('kecamatan IS NULL')
            ->orderBy('kecamatan')
            ->orderBy('nama_sekolah')
            ->get()
            ->map(function ($sekolah) {
                $sekolah->laporanTriwulanIni = $sekolah->laporanRealisasiBosp->first();

                return $sekolah;
            });

        // Kolom 11-27 (belanja_barang_pakai_habis_persediaan,
        // jasa_tenaga_pendidik_dan_kependidikan, daya_dan_jasa,
        // pemeliharaan, upah_pemeliharaan, biaya_pendaftaran_lomba_bimtek_workshop,
        // honor_kegiatan, makan_dan_minum_kegiatan, perjalanan_dinas,
        // total_belanja_barang_dan_jasa, peralatan_dan_mesin_kib_b,
        // aset_tetap_lainnya_kib_e, total_belanja_modal,
        // total_realisasi_dana_bos, sisa_dana_bos, saldo_rekening_kas_bank,
        // saldo_kas_tunai) -
        // diambil/dihitung REAL-TIME: sebagian dari total SUM kolom sumber
        // masing-masing menu, sebagian lagi rumus gabungan dari kolom
        // komputasi lain, & kolom 26-27 dari App\Models\DanaBospTahap
        // (lihat daftar lengkap & rumus masing-masing pada catatan
        // FIELD_KOMPUTASI_RINCIAN di App\Models\LaporanRealisasiBosp),
        // 1 pemanggilan PER FIELD dikelompokkan per sekolah untuk seluruh
        // sekolah yang sedang tampil - BUKAN dari kolom database yang
        // sudah tidak dipakai lagi sejak permintaan user 2026-09-17
        // (lanjutan Part 32 & seterusnya).
        $totalKomputasiRincian = [];
        foreach (LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN as $fieldKomputasi) {
            $totalKomputasiRincian[$fieldKomputasi] = LaporanRealisasiBosp::ambilTotalKomputasiRincian($fieldKomputasi, $this->tahun, $triwulan);
        }

        // Kolom 28/29 (verifikasi_jumlah/verifikasi_saldo) - dihitung
        // REAL-TIME sekaligus untuk seluruh sekolah yang sedang tampil
        // (bukan lagi dari kolom database yang sudah tidak ditulis lagi
        // sejak permintaan user 2026-09-17 lanjutan Part 32 kelima -
        // lihat catatan LaporanRealisasiBosp::ambilVerifikasiSaldo()).
        $verifikasiPerSekolah = LaporanRealisasiBosp::ambilVerifikasiSaldo($this->tahun, $triwulan);

        foreach ($daftarSekolah as $sekolah) {
            $data = $sekolah->laporanTriwulanIni;
            $this->baris[$sekolah->id] = [];

            foreach ($this->daftarFieldSemua() as $field) {
                if (in_array($field, LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN, true)) {
                    $this->baris[$sekolah->id][$field] = (string) (int) ($totalKomputasiRincian[$field][$sekolah->id] ?? 0);

                    continue;
                }

                if ($field === 'verifikasi_jumlah') {
                    $this->baris[$sekolah->id][$field] = (string) (int) ($verifikasiPerSekolah[$sekolah->id]['verifikasi_jumlah'] ?? 0);

                    continue;
                }

                if ($field === 'verifikasi_saldo') {
                    $this->baris[$sekolah->id][$field] = (string) ($verifikasiPerSekolah[$sekolah->id]['verifikasi_saldo'] ?? '');

                    continue;
                }

                $this->baris[$sekolah->id][$field] = $data?->{$field} !== null ? (string) $data->{$field} : '';
            }
        }

        // Baris "Jumlah" (total) - sum seluruh sekolah yang sedang
        // tampil untuk kolom numerik (kolom 8-28), KECUALI kolom 29
        // (verifikasi_saldo, teks) yang dihitung ulang dari hasil
        // penjumlahan kolom 25/26/27 di bawah, bukan disamakan/di-sum
        // begitu saja (tidak masuk akal menjumlahkan teks "SAMA").
        $totalNumerik = [];
        foreach (LaporanRealisasiBosp::FIELD_MANUAL as $field) {
            if (in_array($field, LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN, true)) {
                // Sum dari hasil real-time yang sudah dihitung di atas,
                // BUKAN dari kolom database (lihat catatan di atas).
                $totalNumerik[$field] = $daftarSekolah->sum(function ($sekolah) use ($totalKomputasiRincian, $field) {
                    return (int) ($totalKomputasiRincian[$field][$sekolah->id] ?? 0);
                });

                continue;
            }

            $totalNumerik[$field] = $daftarSekolah->sum(function ($sekolah) use ($field) {
                return (int) ($sekolah->laporanTriwulanIni?->{$field} ?? 0);
            });
        }
        $totalNumerik['verifikasi_jumlah'] = $daftarSekolah->sum(function ($sekolah) use ($verifikasiPerSekolah) {
            return (int) ($verifikasiPerSekolah[$sekolah->id]['verifikasi_jumlah'] ?? 0);
        });

        // Deteksi "ada yang belum lengkap" untuk baris "JUMLAH" (footer) -
        // permintaan user 2026-09-17 (lanjutan Part 32 ketujuh, poin 1,
        // jawaban AskUserQuestion "ikut disembunyikan"): kalau ADA SATU
        // SAJA sekolah yang kolom 26 (Saldo Rekening/Kas Bank) ATAU kolom
        // 27 (Saldo Kas Tunai)-nya masih null (belum tersimpan) untuk
        // triwulan ini, verifikasi_saldo baris JUMLAH JUGA ikut
        // disembunyikan (null) - dicek dari koleksi MENTAH SEBELUM
        // di-cor ke (int) di $totalNumerik di atas (yang sudah menganggap
        // null sebagai 0, sehingga tidak bisa lagi dibedakan dari "memang
        // isinya 0").
        $bankLengkapSemuaSekolah = ! $daftarSekolah->contains(
            fn ($sekolah) => is_null($totalKomputasiRincian['saldo_rekening_kas_bank'][$sekolah->id] ?? null)
        );
        $tunaiLengkapSemuaSekolah = ! $daftarSekolah->contains(
            fn ($sekolah) => is_null($totalKomputasiRincian['saldo_kas_tunai'][$sekolah->id] ?? null)
        );

        [, $totalVerifikasiSaldo] = LaporanRealisasiBosp::hitungVerifikasiSaldo(
            $totalNumerik['sisa_dana_bos'],
            $bankLengkapSemuaSekolah ? $totalNumerik['saldo_rekening_kas_bank'] : null,
            $tunaiLengkapSemuaSekolah ? $totalNumerik['saldo_kas_tunai'] : null
        );
        $totalNumerik['verifikasi_saldo'] = $totalVerifikasiSaldo;

        return [$daftarSekolah, $totalNumerik];
    }

    /**
     * Data untuk tab "rekap" (Rekapitulasi Laporan Realisasi Tahun
     * Anggaran, otomatis) - PER SEKOLAH, rincian 4 baris TW + 1 baris
     * "Jumlah" (jawaban AskUserQuestion 2026-09-17). READ-ONLY, tidak
     * ada kotak input.
     *
     * BERTAMBAH sejak permintaan user 2026-09-17 (lanjutan Part 32
     * ketujuh, poin 2): sekarang JUGA mengembalikan
     * `$totalTahunAnggaran` - baris total SATU TAHUN ANGGARAN (SUM
     * baris "Jumlah" seluruh sekolah di atas), ditampilkan SELALU
     * (tidak collapsible) setelah baris terakhir tabel rekap.
     *
     * @return array{0: array<int, array{sekolah: ProfilSekolah, perTriwulan: array<int, array<string, int|string|null>>, jumlah: array<string, int|string|null>}>, 1: array<string, int|string|null>}
     */
    protected function renderTabRekap(): array
    {
        $query = ProfilSekolah::with(['laporanRealisasiBosp' => function ($q) {
            $q->where('tahun', $this->tahun);
        }]);

        if (! $this->bolehKelolaSemua()) {
            $query->where('id', auth()->user()->profil_sekolah_id);
        }

        $daftarSekolah = $query
            ->orderByRaw("CASE WHEN status = 'negeri' THEN 0 WHEN status = 'swasta' THEN 1 ELSE 2 END")
            ->orderByRaw('kecamatan IS NULL')
            ->orderBy('kecamatan')
            ->orderBy('nama_sekolah')
            ->get();

        // Kolom 11-27 untuk SELURUH triwulan sekaligus - lihat catatan
        // sama di renderTabTriwulan().
        $totalKomputasiRincianSemuaTw = [];
        foreach (LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN as $fieldKomputasi) {
            $totalKomputasiRincianSemuaTw[$fieldKomputasi] = LaporanRealisasiBosp::ambilTotalKomputasiRincianSemuaTriwulan($fieldKomputasi, $this->tahun);
        }

        // Kolom 28/29 untuk SELURUH triwulan sekaligus - lihat catatan
        // sama di renderTabTriwulan().
        $verifikasiSemuaTw = LaporanRealisasiBosp::ambilVerifikasiSaldoSemuaTriwulan($this->tahun);

        $hasil = [];
        $adaSekolahBelumLengkap = false;

        foreach ($daftarSekolah as $sekolah) {
            $perTriwulan = [];
            $bankLengkapSemuaTw = true;
            $tunaiLengkapSemuaTw = true;

            foreach ([1, 2, 3, 4] as $triwulan) {
                $data = $sekolah->laporanRealisasiBosp->firstWhere('triwulan', $triwulan);
                $baris = [];
                foreach ($this->daftarFieldSemua() as $field) {
                    if (in_array($field, LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN, true)) {
                        $baris[$field] = (int) ($totalKomputasiRincianSemuaTw[$field][$sekolah->id][$triwulan] ?? 0);

                        continue;
                    }

                    if ($field === 'verifikasi_jumlah') {
                        $baris[$field] = (int) ($verifikasiSemuaTw[$sekolah->id][$triwulan]['verifikasi_jumlah'] ?? 0);

                        continue;
                    }

                    if ($field === 'verifikasi_saldo') {
                        $baris[$field] = $verifikasiSemuaTw[$sekolah->id][$triwulan]['verifikasi_saldo'] ?? null;

                        continue;
                    }

                    $baris[$field] = $data?->{$field};
                }
                $perTriwulan[$triwulan] = $baris;

                // Deteksi "ada TW yang belum lengkap" untuk baris "Jumlah"
                // per sekolah - permintaan user 2026-09-17 (lanjutan Part
                // 32 ketujuh, poin 1, jawaban AskUserQuestion "ikut
                // disembunyikan"). Dicek dari koleksi MENTAH SEBELUM
                // di-cor ke (int) pada $baris di atas (lihat catatan sama
                // pada renderTabTriwulan()).
                if (is_null($totalKomputasiRincianSemuaTw['saldo_rekening_kas_bank'][$sekolah->id][$triwulan] ?? null)) {
                    $bankLengkapSemuaTw = false;
                }
                if (is_null($totalKomputasiRincianSemuaTw['saldo_kas_tunai'][$sekolah->id][$triwulan] ?? null)) {
                    $tunaiLengkapSemuaTw = false;
                }
            }

            // Baris "Jumlah" - sum 4 TW untuk kolom numerik (8-28),
            // kolom 29 dihitung ulang dari hasil sum kolom 25/26/27
            // (lihat catatan kelas di atas).
            $jumlah = [];
            foreach (LaporanRealisasiBosp::FIELD_MANUAL as $field) {
                $jumlah[$field] = collect($perTriwulan)->sum(fn ($baris) => (int) ($baris[$field] ?? 0));
            }
            $jumlah['verifikasi_jumlah'] = collect($perTriwulan)->sum(fn ($baris) => (int) ($baris['verifikasi_jumlah'] ?? 0));

            $sekolahLengkap = $bankLengkapSemuaTw && $tunaiLengkapSemuaTw;
            if (! $sekolahLengkap) {
                $adaSekolahBelumLengkap = true;
            }

            [, $jumlahVerifikasiSaldo] = LaporanRealisasiBosp::hitungVerifikasiSaldo(
                $jumlah['sisa_dana_bos'],
                $sekolahLengkap ? $jumlah['saldo_rekening_kas_bank'] : null,
                $sekolahLengkap ? $jumlah['saldo_kas_tunai'] : null
            );
            $jumlah['verifikasi_saldo'] = $jumlahVerifikasiSaldo;

            $hasil[] = [
                'sekolah' => $sekolah,
                'perTriwulan' => $perTriwulan,
                'jumlah' => $jumlah,
            ];
        }

        // Baris total SATU TAHUN ANGGARAN (poin 2, permintaan user
        // 2026-09-17 lanjutan Part 32 ketujuh) - SUM baris "Jumlah"
        // SETIAP sekolah di atas (bukan query baru ke database), SELALU
        // tampil (tidak collapsible) setelah baris terakhir tabel rekap.
        $totalTahunAnggaran = [];
        foreach (LaporanRealisasiBosp::FIELD_MANUAL as $field) {
            $totalTahunAnggaran[$field] = collect($hasil)->sum(fn ($baris) => (int) ($baris['jumlah'][$field] ?? 0));
        }
        $totalTahunAnggaran['verifikasi_jumlah'] = collect($hasil)->sum(fn ($baris) => (int) ($baris['jumlah']['verifikasi_jumlah'] ?? 0));

        [, $totalVerifikasiSaldoTahun] = LaporanRealisasiBosp::hitungVerifikasiSaldo(
            $totalTahunAnggaran['sisa_dana_bos'],
            $adaSekolahBelumLengkap ? null : $totalTahunAnggaran['saldo_rekening_kas_bank'],
            $adaSekolahBelumLengkap ? null : $totalTahunAnggaran['saldo_kas_tunai']
        );
        $totalTahunAnggaran['verifikasi_saldo'] = $totalVerifikasiSaldoTahun;

        return [$hasil, $totalTahunAnggaran];
    }

    /**
     * 13 baris "Uraian" halaman validasi (Verval) - permintaan user
     * 2026-09-23 (round keenam, poin 3, gambar contoh "VALIDASI HASIL
     * ENTRY DATA BOSP"). Urutan & label PERSIS sama seperti permintaan
     * user & konstanta JENIS_OPTIONS pada masing-masing model sumber.
     * 9 dari 13 baris memakai method ambilTotalXSemuaTriwulan() yang
     * SUDAH ADA di App\Models\LaporanRealisasiBosp (sumber yang sama
     * persis dengan kolom 12-22 pada tab TW1-4 biasa - lihat docblock
     * kelas ini); 4 baris "Rincian Pemeliharaan"/"Rincian Jasa
     * Pemeliharaan" (bangunan & PC, terpisah) memakai 4 method BARU
     * (ambilTotalXSajaSemuaTriwulan()) karena kolom 14/15 pada tab TW1-4
     * MENGGABUNGKAN bangunan+PC jadi 1 angka, sedangkan gambar contoh
     * user menampilkan keduanya sebagai baris TERPISAH.
     *
     * @return array<int, array{label: string, ambil: callable(int): \Illuminate\Support\Collection}>
     */
    protected function daftarUraianValidasi(): array
    {
        return [
            ['label' => 'Penerimaan Honor PTK', 'ambil' => fn (int $tahun) => LaporanRealisasiBosp::ambilTotalJasaTenagaPendidikDanKependidikanSemuaTriwulan($tahun)],
            ['label' => 'Langganan Daya dan Jasa', 'ambil' => fn (int $tahun) => LaporanRealisasiBosp::ambilTotalDayaDanJasaSemuaTriwulan($tahun)],
            ['label' => 'Rincian Pemeliharaan Bangunan', 'ambil' => fn (int $tahun) => LaporanRealisasiBosp::ambilTotalPemeliharaanBangunanSajaSemuaTriwulan($tahun)],
            ['label' => 'Rincian Jasa Pemeliharaan', 'ambil' => fn (int $tahun) => LaporanRealisasiBosp::ambilTotalJasaPemeliharaanBangunanSajaSemuaTriwulan($tahun)],
            ['label' => 'Rincian Pemeliharaan PC Komputer-Laptop-Printer dll', 'ambil' => fn (int $tahun) => LaporanRealisasiBosp::ambilTotalPemeliharaanPcSajaSemuaTriwulan($tahun)],
            ['label' => 'Rincian Jasa Pemeliharaan PC-Laptop-Printer dll', 'ambil' => fn (int $tahun) => LaporanRealisasiBosp::ambilTotalJasaPemeliharaanPcSajaSemuaTriwulan($tahun)],
            ['label' => 'Biaya Pendaftaran Lomba/Bimtek/Workshop', 'ambil' => fn (int $tahun) => LaporanRealisasiBosp::ambilTotalBiayaPendaftaranLombaBimtekWorkshopSemuaTriwulan($tahun)],
            ['label' => 'Honor Kegiatan', 'ambil' => fn (int $tahun) => LaporanRealisasiBosp::ambilTotalHonorKegiatanSemuaTriwulan($tahun)],
            ['label' => 'Belanja Makan & Minum', 'ambil' => fn (int $tahun) => LaporanRealisasiBosp::ambilTotalMakanDanMinumKegiatanSemuaTriwulan($tahun)],
            ['label' => 'Belanja Perjalanan Dinas', 'ambil' => fn (int $tahun) => LaporanRealisasiBosp::ambilTotalPerjalananDinasSemuaTriwulan($tahun)],
            ['label' => 'Rincian Belanja Modal Peralatan & Mesin (KIB B)', 'ambil' => fn (int $tahun) => LaporanRealisasiBosp::ambilTotalPeralatanDanMesinKibBSemuaTriwulan($tahun)],
            ['label' => 'Belanja Modal Aset Tetap Lainnya (KIB E)', 'ambil' => fn (int $tahun) => LaporanRealisasiBosp::ambilTotalAsetTetapLainnyaKibESemuaTriwulan($tahun)],
            ['label' => 'Rincian Belanja Barang Habis Pakai', 'ambil' => fn (int $tahun) => LaporanRealisasiBosp::ambilTotalBelanjaBarangPakaiHabisPersediaanSemuaTriwulan($tahun)],
        ];
    }

    /**
     * Susun 13 baris x 4 kolom Triwulan untuk 1 sekolah+tahun aktif -
     * dipakai halaman validasi (Verval).
     *
     * @return array<int, array{label: string, nilai: array<int, int>}>
     */
    protected function dataValidasi(int $sekolahId): array
    {
        $baris = [];

        foreach ($this->daftarUraianValidasi() as $item) {
            $perSekolah = ($item['ambil'])($this->tahun);
            $perTriwulan = $perSekolah[$sekolahId] ?? collect();

            $baris[] = [
                'label' => $item['label'],
                'nilai' => [
                    1 => (int) ($perTriwulan[1] ?? 0),
                    2 => (int) ($perTriwulan[2] ?? 0),
                    3 => (int) ($perTriwulan[3] ?? 0),
                    4 => (int) ($perTriwulan[4] ?? 0),
                ],
            ];
        }

        return $baris;
    }

    /**
     * Status Verval KE-4 triwulan tahun aktif untuk SELURUH sekolah -
     * permintaan user 2026-09-23 (round ketujuh, jawaban AskUserQuestion
     * "Tombol Reset di halaman Laporan Realisasi BOSP"). HANYA dipakai
     * untuk panel "Status Verval & Reset Kuncian (Superadmin)" pada
     * index.blade.php - lihat render(), dipanggil HANYA saat
     * bolehKelolaSemua() supaya Admin BOSP tidak menanggung query
     * tambahan untuk data yang tidak pernah mereka lihat. Independen dari
     * tab (tw1-4/rekap) yang sedang aktif - Superadmin bisa reset triwulan
     * manapun dari tab manapun.
     *
     * @return \Illuminate\Support\Collection<int, array{sekolah: ProfilSekolah, status: \Illuminate\Support\Collection}>
     */
    protected function statusVervalSemuaSekolah(): \Illuminate\Support\Collection
    {
        return ProfilSekolah::query()
            ->orderByRaw("CASE WHEN status = 'negeri' THEN 0 WHEN status = 'swasta' THEN 1 ELSE 2 END")
            ->orderByRaw('kecamatan IS NULL')
            ->orderBy('kecamatan')
            ->orderBy('nama_sekolah')
            ->get()
            ->map(fn ($sekolah) => [
                'sekolah' => $sekolah,
                'status' => VervalRealisasiBosp::ambilStatus($sekolah->id, $this->tahun),
            ]);
    }

    /**
     * Aksi ceklist Verval (dipanggil dari tombol "Sesuai"/"Belum Sesuai"
     * per triwulan pada halaman validasi) - permintaan user 2026-09-23
     * (round keenam, poin 3). HANYA Admin BOSP (sekolah sendiri) yang
     * boleh memanggil ini (jawaban AskUserQuestion: "Hanya Admin BOSP"),
     * & triwulan yang SUDAH berstatus "sesuai" terkunci PERMANEN - tidak
     * bisa diubah lagi lewat jalur manapun (jawaban AskUserQuestion:
     * "otomatis terkunci dan data sudah dinyatakan sesuai dan valid").
     * "Belum Sesuai" TIDAK mengunci apapun - admin BOSP bisa ganti
     * pilihan bolak-balik antara "Sesuai"/"Belum Sesuai" SELAMA belum
     * pernah memilih "Sesuai".
     */
    public function setVerval(int $triwulan, string $status): void
    {
        if (! in_array($triwulan, [1, 2, 3, 4], true)) {
            return;
        }

        if (! in_array($status, [VervalRealisasiBosp::STATUS_SESUAI, VervalRealisasiBosp::STATUS_BELUM_SESUAI], true)) {
            return;
        }

        if ($this->bolehKelolaSemua()) {
            // Superadmin tidak melihat halaman validasi ini sama sekali
            // (lihat render()) - guard dipertahankan jaga-jaga panggilan
            // langsung dari luar alur normal.
            return;
        }

        $sekolahId = auth()->user()->profil_sekolah_id;

        if (! $sekolahId) {
            return;
        }

        if (VervalRealisasiBosp::triwulanSudahSesuai($sekolahId, $this->tahun, $triwulan)) {
            return;
        }

        VervalRealisasiBosp::updateOrCreate(
            ['profil_sekolah_id' => $sekolahId, 'tahun' => $this->tahun, 'triwulan' => $triwulan],
            ['status' => $status, 'diverval_oleh' => auth()->id(), 'diverval_pada' => now()]
        );

        // Permintaan user 2026-09-23 (round ketujuh, jawaban AskUserQuestion
        // "Tiap klik Sesuai/Belum Sesuai, reload halaman penuh"): setiap kali
        // Admin BOSP memilih Sesuai/Belum Sesuai untuk 1 triwulan, browser
        // harus melakukan RELOAD PENUH (bukan sekadar re-render Livewire
        // biasa) supaya halaman Laporan Realisasi BOSP (Form BPK) langsung
        // terbuka dengan status terbaru. Listener JS-nya ada di
        // resources/views/livewire/pendataan-bosp/laporan-realisasi-bosp/validasi.blade.php
        $this->dispatch('verval-diperbarui');
    }

    /**
     * Reset PENUH kuncian verval 1 triwulan milik 1 sekolah - permintaan
     * user 2026-09-23 (round ketujuh, jawaban AskUserQuestion "Tombol Reset
     * di halaman Laporan Realisasi BOSP" & "Reset penuh"). HANYA Superadmin
     * yang boleh memanggil ini - Admin BOSP yang butuh triwulannya dibuka
     * kembali harus meminta Superadmin melakukan reset lewat halaman ini,
     * sesuai permintaan asli user: "apabila mau di buka kembali maka admin
     * BOSP harus meminta reset ke superadmin kuncian validasi tersebut."
     * Menghapus baris verval-nya (lihat VervalRealisasiBosp::resetTriwulan())
     * otomatis membuka KEMBALI ceklist verval triwulan itu MAUPUN data
     * isian triwulan itu di 8 menu sumber sekaligus.
     */
    public function resetVerval(int $profilSekolahId, int $triwulan): void
    {
        abort_unless($this->bolehKelolaSemua(), 403);

        if (! in_array($triwulan, [1, 2, 3, 4], true)) {
            return;
        }

        VervalRealisasiBosp::resetTriwulan($profilSekolahId, $this->tahun, $triwulan);
    }

    public function render()
    {
        $tahunOptions = array_reverse(range(now()->year - 2, now()->year + 1));
        $bolehKelolaSemua = $this->bolehKelolaSemua();
        $sekolahIdSendiri = ! $bolehKelolaSemua ? auth()->user()->profil_sekolah_id : null;

        // Gerbang Verval - DIREVISI 2026-09-23 (round kedelapan, laporan
        // bug atas Round 7 dari user: "menu Laporan Realisasi BOSP (Form
        // BPK) di validasi per triwulan bukan menunggu sampai validasi
        // triwulan 4 ... ketika klik verval Sesuai/belum Sesuai halaman
        // langsung otomatis membuka menu Laporan Realisasi BOSP"). Aturan
        // LAMA (round keenam, "SEMUA 4 triwulan harus 'sesuai' dulu")
        // DIGANTI: gerbang HANYA tampil selama BELUM ADA SATU PUN
        // triwulan yang pernah diverval (Sesuai MAUPUN Belum Sesuai) -
        // lihat VervalRealisasiBosp::adaTriwulanSudahDiverval(). Begitu
        // triwulan MANAPUN diklik (setVerval() mendispatch
        // 'verval-diperbarui' -> reload penuh, lihat setVerval()), gerbang
        // ini TIDAK tampil lagi & halaman laporan biasa (index) langsung
        // aktif - lengkap dengan panel "Validasi Hasil Entry Data BOSP"
        // yang tetap ada TERTANAM di halaman itu (lihat $panelValidasiSendiri
        // di bawah) supaya Admin BOSP tetap bisa memverval triwulan yang
        // belum diklik TANPA balik ke gerbang ini. Superadmin TIDAK
        // PERNAH melihat gerbang ini sama sekali (tidak berubah dari
        // round keenam).
        if ($sekolahIdSendiri && ! VervalRealisasiBosp::adaTriwulanSudahDiverval($sekolahIdSendiri, $this->tahun)) {
            // PENTING: kunci view data SENGAJA "daftarValidasi", BUKAN
            // "baris" - nama itu sudah dipakai properti publik
            // $this->baris (array tabel tab TW1-4 biasa, lihat atas).
            // Livewire menimpa data view eksplisit dengan properti
            // publik bernama sama saat merender, jadi kalau dipakai
            // nama yang sama di sini, isi tabel Verval akan ikut
            // tertimpa/rusak begitu $this->baris pernah diisi (mis.
            // lewat updated()/tab TW1-4 lain) - lihat regresi round
            // keenam yang ditemukan test_..._tidak_bisa_diedit_manual dkk.
            return view('livewire.pendataan-bosp.laporan-realisasi-bosp.validasi', [
                'tahunOptions' => $tahunOptions,
                'sekolah' => ProfilSekolah::find($sekolahIdSendiri),
                'daftarValidasi' => $this->dataValidasi($sekolahIdSendiri),
                'statusVerval' => VervalRealisasiBosp::ambilStatus($sekolahIdSendiri, $this->tahun),
            ]);
        }

        // Panel "Status Verval & Reset Kuncian (Superadmin)" - lihat
        // catatan statusVervalSemuaSekolah() di atas. HANYA dihitung untuk
        // Superadmin (Admin BOSP tidak pernah melihat panel ini).
        $statusVervalSuperadmin = $bolehKelolaSemua ? $this->statusVervalSemuaSekolah() : null;

        // Panel "Validasi Hasil Entry Data BOSP" TERTANAM di halaman
        // laporan biasa untuk Admin BOSP sendiri (round kedelapan) - SAMA
        // PERSIS datanya dengan yang gerbang validasi.php pakai di atas
        // (dataValidasi() & ambilStatus()), supaya Admin BOSP bisa
        // melanjutkan verval triwulan yang belum diklik tanpa pindah
        // halaman. null untuk Superadmin (mereka pakai panel terpisah di
        // atas).
        //
        // DIREVISI 2026-09-23 (round kesembilan, poin 1): SEBELUMNYA panel
        // ini SELALU tampil begitu gerbang di atas sudah pernah terbuka
        // sekali (permanen sejak round kedelapan). SEKARANG tampil/hilang
        // otomatis MENGIKUTI VervalRealisasiBosp::triwulanAktifValidasi()
        // - HANYA tampil kalau ADA triwulan yang sudah mulai diisi
        // datanya TAPI BELUM diverval sama sekali (lihat docblock method
        // itu) - supaya tidak mengganggu tampilan begitu triwulan yang
        // aktif sudah divalidasi & triwulan berikutnya belum mulai
        // dikerjakan (jawaban AskUserQuestion "Trigger panel muncul/
        // hilang").
        $triwulanAktifValidasi = $sekolahIdSendiri
            ? VervalRealisasiBosp::triwulanAktifValidasi($sekolahIdSendiri, $this->tahun)
            : null;

        $panelValidasiSendiri = $triwulanAktifValidasi !== null ? [
            'sekolah' => ProfilSekolah::find($sekolahIdSendiri),
            'daftarValidasi' => $this->dataValidasi($sekolahIdSendiri),
            'statusVerval' => VervalRealisasiBosp::ambilStatus($sekolahIdSendiri, $this->tahun),
        ] : null;

        if ($this->tabAktif === 'rekap') {
            [$rekapPerSekolah, $totalTahunAnggaran] = $this->renderTabRekap();

            return view('livewire.pendataan-bosp.laporan-realisasi-bosp.index', [
                'tahunOptions' => $tahunOptions,
                'bolehKelolaSemua' => $bolehKelolaSemua,
                'rekapPerSekolah' => $rekapPerSekolah,
                'totalTahunAnggaran' => $totalTahunAnggaran,
                'statusVervalSuperadmin' => $statusVervalSuperadmin,
                'panelValidasiSendiri' => $panelValidasiSendiri,
            ]);
        }

        $triwulan = self::TAB_TRIWULAN[$this->tabAktif] ?? 1;
        [$daftarSekolah, $totalBaris] = $this->renderTabTriwulan($triwulan);

        return view('livewire.pendataan-bosp.laporan-realisasi-bosp.index', [
            'tahunOptions' => $tahunOptions,
            'bolehKelolaSemua' => $bolehKelolaSemua,
            'daftarSekolah' => $daftarSekolah,
            'totalBaris' => $totalBaris,
            'statusVervalSuperadmin' => $statusVervalSuperadmin,
            'panelValidasiSendiri' => $panelValidasiSendiri,
        ]);
    }
}
