<?php

namespace App\Livewire\PendataanBosp\RekapRkas;

use App\Exports\RekapRkasExport;
use App\Livewire\Concerns\HasZoomTampilan;
use App\Models\DanaBospTahap;
use App\Models\ProfilSekolah;
use App\Models\RekapRkas;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Rekap RKAS Awal-Perubahan - Pendataan BOSP.
 *
 * Beda dengan menu "Identitas Admin BOSP" (1 data tetap per sekolah),
 * data di menu ini dilacak PER TAHUN (sesuai jawaban AskUserQuestion
 * 2026-09-09 poin 1) - 1 baris per sekolah PER TAHUN, dengan dropdown
 * Tahun untuk berpindah antar tahun (tahun berjalan sebagai default).
 *
 * PENTING (2026-09-09 lanjutan ke-4 & ke-5): SEMUA kolom "hasil rumus"
 * sekarang sudah punya rumus & dihitung otomatis oleh sistem - TIDAK ADA
 * LAGI kolom rumus yang manual:
 * - Jml Sesudah & Selisih tiap kategori Belanja (5 kategori, lihat
 *   RekapRkas::KATEGORI_RUMUS) - dihitung di updated()/hitungUlangRumus()
 *   & simpan().
 * - Sebelum/Sesudah/Selisih pada baris JUMLAH - dihitung di
 *   updated()/hitungUlangJumlahBaris() & simpan(), lewat
 *   RekapRkas::hitungJumlahBaris().
 * Lihat App\Models\RekapRkas untuk rumus lengkapnya. JANGAN
 * menebak/menerapkan rumus apapun di luar yang sudah ditentukan user.
 *
 * - Superadmin: melihat seluruh sekolah untuk tahun yang dipilih, bisa
 *   mengisi/mengedit Rekap RKAS sekolah manapun.
 * - Admin BOSP: hanya melihat & bisa mengisi/mengedit Rekap RKAS
 *   sekolahnya sendiri.
 *
 * PENTING (2026-09-09 lanjutan ke-3): baris "Jumlah" di baris paling
 * bawah tabel (lihat $totalBaris pada render()) BEDA dari kolom-kolom
 * rumus per-baris di atas - baris ini SELALU dihitung otomatis oleh
 * sistem (sum tiap kolom dari seluruh baris sekolah yang sedang tampil),
 * sesuai jawaban AskUserQuestion "Otomatis dihitung sistem (sum kolom)" -
 * BUKAN kotak yang bisa diketik/diedit manual seperti kolom-kolom rumus
 * per-baris di atas yang masih menunggu rumus ditentukan user.
 *
 * PENTING (permintaan user 2026-09-23, jawaban AskUserQuestion "Blokir
 * seluruh akses tab"): kolom "Anggaran BOSP {tahun}" sekarang OTOMATIS
 * (lihat RekapRkas::anggaranBospOtomatis(), BUKAN lagi kotak input manual
 * di tabel maupun form modal) - diambil dari Total Penerimaan BOSP
 * Setahun pada menu Dana BOSP Tahap 1 & 2 - Penerimaan BOSP sekolah+tahun
 * yang sama. SELAMA Dana BOSP Tahap - Penerimaan BOSP sekolah itu BELUM
 * diisi (jumlah_siswa & jumlah_dana_bosp_per_tahun kosong), SELURUH baris
 * Rekap RKAS sekolah+tahun itu TERKUNCI - tidak bisa diisi/diedit sampai
 * Dana BOSP Tahap diisi dulu (lihat danaBospTahapTerisi() & tampilan
 * terkunci di index.blade.php). Ini TIDAK berlaku sebagai gate global 1
 * menu untuk semua sekolah - tiap baris sekolah dicek sendiri-sendiri,
 * supaya Superadmin tetap bisa mengelola sekolah lain yang datanya sudah
 * lengkap walau ada sekolah lain yang belum.
 */
#[Layout('layouts.app')]
#[Title('Rekap RKAS Awal-Perubahan')]
class Index extends Component
{
    use HasZoomTampilan;

    public int $tahun;

    public ?int $sekolahId = null;

    public ?int $editingId = null;

    /**
     * Data untuk input LANGSUNG di tiap kotak/kolom tabel (di luar form
     * modal "Isi/Edit" yang sudah ada) - array 2 dimensi [sekolahId][field]
     * => nilai, diisi ulang tiap render() dari data ter-terbaru di database
     * (lihat render()). wire:model.blur pada tiap kotak di tabel mengikat
     * ke sini - begitu kotak kehilangan fokus (pindah kotak), Livewire
     * mengirim nilainya, ditangkap di updated() di bawah, lalu langsung
     * disimpan ke database (auto-save per kotak, sesuai jawaban
     * AskUserQuestion 2026-09-09 lanjutan: "Otomatis saat pindah kotak").
     *
     * @var array<int, array<string, string>>
     */
    public array $baris = [];

    /**
     * Dinaikkan setiap kali form Tambah/Edit dibuka - dipakai sebagai
     * wire:key pada kotak-kotak input Rupiah (yang wire:ignore) supaya
     * kotaknya selalu ter-refresh ke nilai yang benar.
     */
    public int $formInstance = 0;

    /**
     * Dinaikkan PER SEKOLAH (bukan global) setiap kali ada input langsung
     * di kotak tabel sekolah itu yang ditolak (bukan angka) - dipakai
     * sebagai bagian wire:key pada kotak Rupiah baris tsb (2026-09-09
     * lanjutan ke-2: kotak tabel sekarang diformat Rupiah & pakai
     * wire:ignore, jadi TIDAK otomatis ikut diperbarui Livewire seperti
     * sebelumnya) - memaksa Livewire membuat ulang kotak-kotak baris itu
     * dari nilai database yang sebenarnya, supaya perilaku "kembali ke
     * nilai semula kalau input tidak valid" dari round sebelumnya tetap
     * berfungsi walau kotaknya sekarang wire:ignore.
     *
     * @var array<int, int>
     */
    public array $revisiBaris = [];

    public string $anggaran_bosp = '';

    public string $pegawai_sebelum = '';

    public string $pegawai_realisasi_tahap1 = '';

    public string $pegawai_perubahan_tahap2 = '';

    public string $pegawai_jml_sesudah = '';

    public string $pegawai_selisih = '';

    public string $pemeliharaan_sebelum = '';

    public string $pemeliharaan_realisasi_tahap1 = '';

    public string $pemeliharaan_perubahan_tahap2 = '';

    public string $pemeliharaan_jml_sesudah = '';

    public string $pemeliharaan_selisih = '';

    public string $barjas_sebelum = '';

    public string $barjas_realisasi_tahap1 = '';

    public string $barjas_perubahan_tahap2 = '';

    public string $barjas_jml_sesudah = '';

    public string $barjas_selisih = '';

    public string $peralatan_mesin_sebelum = '';

    public string $peralatan_mesin_realisasi_tahap1 = '';

    public string $peralatan_mesin_perubahan_tahap2 = '';

    public string $peralatan_mesin_jml_sesudah = '';

    public string $peralatan_mesin_selisih = '';

    public string $aset_lainnya_sebelum = '';

    public string $aset_lainnya_realisasi_tahap1 = '';

    public string $aset_lainnya_perubahan_tahap2 = '';

    public string $aset_lainnya_jml_sesudah = '';

    public string $aset_lainnya_selisih = '';

    public string $jumlah_sebelum = '';

    public string $jumlah_sesudah = '';

    public string $jumlah_selisih = '';

    public bool $showForm = false;

    public function mount(): void
    {
        $this->tahun = now()->year;
    }

    /**
     * Menangkap perubahan pada kotak input LANGSUNG di tabel (property
     * "baris.{sekolahId}.{field}", diikat lewat wire:model.blur pada view)
     * - begitu 1 kotak kehilangan fokus, method bawaan Livewire ini
     * otomatis dipanggil, lalu nilainya divalidasi & langsung disimpan ke
     * database untuk sekolah+tahun terkait (updateOrCreate, sama seperti
     * simpan() lewat modal - baris otomatis dibuat kalau belum ada).
     *
     * Kalau nilainya tidak valid (bukan angka), TIDAK disimpan - render()
     * berikutnya akan mengisi ulang $baris dari data database yang
     * sebenarnya, sehingga kotaknya otomatis kembali ke nilai semula.
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

        if (! in_array($field, $this->daftarFieldAngka(), true)) {
            return;
        }

        abort_unless($this->bolehEdit($sekolahId), 403);

        // Gate (permintaan user 2026-09-23): selama Dana BOSP Tahap -
        // Penerimaan BOSP sekolah ini belum diisi, baris Rekap RKAS-nya
        // terkunci - tolak diam-diam (index.blade.php juga sudah tidak
        // merender kotak input untuk baris terkunci, ini jaga-jaga kalau
        // ada percobaan set langsung di luar UI normal).
        if (! $this->danaBospTahapTerisi($sekolahId)) {
            return;
        }

        // Kolom Jml Sesudah & Selisih pada kategori yang rumusnya sudah
        // ditentukan (KATEGORI_RUMUS) BUKAN kotak input manual lagi (lihat
        // index.blade.php - tidak ada <x-rekap-rkas-cell> untuk kolom ini)
        // - ditolak di sini juga sebagai jaga-jaga kalau ada percobaan
        // set langsung lewat Livewire di luar UI normal, supaya nilainya
        // TETAP hasil hitung, tidak bisa ditimpa manual dari jalur manapun.
        // Otorisasi (abort_unless di atas) tetap dicek DULU supaya percobaan
        // dari sekolah yang bukan haknya tetap ditolak 403, bukan cuma
        // diam-diam diabaikan.
        foreach (RekapRkas::KATEGORI_RUMUS as $kategori) {
            if ($field === $kategori.'_jml_sesudah' || $field === $kategori.'_selisih') {
                return;
            }
        }

        // Kolom Sebelum/Sesudah/Selisih pada baris JUMLAH (lihat
        // RekapRkas::hitungJumlahBaris()) BUKAN kotak input manual lagi
        // juga (2026-09-09 lanjutan ke-5) - alasan sama seperti guard di
        // atas.
        if (in_array($field, ['jumlah_sebelum', 'jumlah_sesudah', 'jumlah_selisih'], true)) {
            return;
        }

        $validator = Validator::make(['nilai' => $value], ['nilai' => ['nullable', 'integer']]);

        if ($validator->fails()) {
            $this->addError($name, 'Harus berupa angka.');
            $this->revisiBaris[$sekolahId] = ($this->revisiBaris[$sekolahId] ?? 0) + 1;

            return;
        }

        $nilai = $value === '' || $value === null ? null : (int) $value;

        $data = [$field => $nilai];

        // Kalau field yang baru diedit adalah Sebelum/Realisasi Tahap 1/
        // Perubahan Tahap 2 milik salah satu KATEGORI_RUMUS - Jml Sesudah
        // & Selisih kategori itu ikut dihitung ulang & disimpan sekalian
        // di update yang sama (lihat hitungUlangRumus()), begitu juga
        // baris JUMLAH (Sebelum/Sesudah/Selisih) yang ikut bergantung pada
        // Sebelum & Jml Sesudah kategori ini (lihat hitungUlangJumlahBaris()).
        foreach (RekapRkas::KATEGORI_RUMUS as $kategori) {
            foreach (['sebelum', 'realisasi_tahap1', 'perubahan_tahap2'] as $sub) {
                if ($field === $kategori.'_'.$sub) {
                    $data = array_merge($data, $this->hitungUlangRumus($sekolahId, $kategori, $field, $nilai));
                    $data = array_merge($data, $this->hitungUlangJumlahBaris($sekolahId, $field, $nilai, $data));
                }
            }
        }

        RekapRkas::updateOrCreate(
            ['profil_sekolah_id' => $sekolahId, 'tahun' => $this->tahun],
            array_merge($data, ['created_by' => auth()->id()])
        );
    }

    /**
     * Menghitung ulang Jml Sesudah & Selisih 1 kategori (KATEGORI_RUMUS)
     * begitu salah satu dari Sebelum/Realisasi Tahap 1/Perubahan Tahap 2
     * kategori itu diedit LANGSUNG di kotak tabel (bukan lewat modal) -
     * field yang baru diedit dipakai nilai barunya ($nilaiBaru), 2 field
     * lain kategori itu diambil dari data rekap yang sudah tersimpan di
     * database (belum termasuk perubahan yang sedang diproses saat ini).
     *
     * @return array<string, int>
     */
    protected function hitungUlangRumus(int $sekolahId, string $kategori, string $fieldYangDiedit, ?int $nilaiBaru): array
    {
        $rekap = RekapRkas::where('profil_sekolah_id', $sekolahId)
            ->where('tahun', $this->tahun)
            ->first();

        $nilaiSebelum = $fieldYangDiedit === $kategori.'_sebelum' ? $nilaiBaru : $rekap?->{$kategori.'_sebelum'};
        $nilaiRealisasi = $fieldYangDiedit === $kategori.'_realisasi_tahap1' ? $nilaiBaru : $rekap?->{$kategori.'_realisasi_tahap1'};
        $nilaiPerubahan = $fieldYangDiedit === $kategori.'_perubahan_tahap2' ? $nilaiBaru : $rekap?->{$kategori.'_perubahan_tahap2'};

        [$jmlSesudah, $selisih] = RekapRkas::hitungJmlSesudahDanSelisih($nilaiSebelum, $nilaiRealisasi, $nilaiPerubahan);

        return [
            $kategori.'_jml_sesudah' => $jmlSesudah,
            $kategori.'_selisih' => $selisih,
        ];
    }

    /**
     * Menghitung ulang Sebelum/Sesudah/Selisih baris JUMLAH (lihat
     * RekapRkas::hitungJumlahBaris()) begitu Sebelum/Realisasi Tahap 1/
     * Perubahan Tahap 2 salah satu kategori diedit LANGSUNG di kotak
     * tabel - untuk kategori yang baru saja diedit, dipakai nilai yang
     * baru saja dihitung ulang di $dataKategoriTerbaru (hitungUlangRumus()
     * pada field itu, dipanggil tepat sebelum method ini); untuk 4
     * kategori lainnya, dipakai nilai yang sudah tersimpan di database
     * (belum termasuk perubahan yang sedang diproses saat ini).
     *
     * @param  array<string, int>  $dataKategoriTerbaru  hasil hitungUlangRumus() untuk kategori yang baru diedit (kunci: "{kategori}_jml_sesudah")
     * @return array<string, int>
     */
    protected function hitungUlangJumlahBaris(int $sekolahId, string $fieldYangDiedit, ?int $nilaiBaru, array $dataKategoriTerbaru): array
    {
        $rekap = RekapRkas::where('profil_sekolah_id', $sekolahId)
            ->where('tahun', $this->tahun)
            ->first();

        $sebelumPerKategori = [];
        $jmlSesudahPerKategori = [];

        foreach (array_keys(RekapRkas::KATEGORI) as $kategori) {
            $sebelumPerKategori[$kategori] = $fieldYangDiedit === $kategori.'_sebelum'
                ? $nilaiBaru
                : $rekap?->{$kategori.'_sebelum'};

            $jmlSesudahPerKategori[$kategori] = $dataKategoriTerbaru[$kategori.'_jml_sesudah']
                ?? $rekap?->{$kategori.'_jml_sesudah'};
        }

        [$jumlahSebelum, $jumlahSesudah, $jumlahSelisih] = RekapRkas::hitungJumlahBaris($sebelumPerKategori, $jmlSesudahPerKategori);

        return [
            'jumlah_sebelum' => $jumlahSebelum,
            'jumlah_sesudah' => $jumlahSesudah,
            'jumlah_selisih' => $jumlahSelisih,
        ];
    }

    /**
     * Nama-nama field angka (29 kolom Rupiah pada tabel ini) yang MASIH
     * bisa diedit manual/lewat rumus otomatis di sini, dipakai bareng
     * untuk reset(), validasi, dan mengisi form dari data lama - supaya
     * urutannya konsisten & tidak perlu diketik berulang.
     *
     * "anggaran_bosp" SENGAJA TIDAK ADA di sini lagi (permintaan user
     * 2026-09-23) - kolom itu sekarang otomatis dari Dana BOSP Tahap,
     * ditangani terpisah lewat anggaranBospUntukSekolah() &
     * RekapRkas::anggaranBospOtomatis(), BUKAN bagian dari alur
     * edit/validasi manual generik di method ini lagi (lihat docblock
     * kelas di atas).
     */
    protected function daftarFieldAngka(): array
    {
        $fields = [];

        foreach (array_keys(RekapRkas::KATEGORI) as $kategori) {
            foreach (['sebelum', 'realisasi_tahap1', 'perubahan_tahap2', 'jml_sesudah', 'selisih'] as $sub) {
                $fields[] = $kategori.'_'.$sub;
            }
        }

        $fields[] = 'jumlah_sebelum';
        $fields[] = 'jumlah_sesudah';
        $fields[] = 'jumlah_selisih';

        return $fields;
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
     * Mengambil baris Dana BOSP Tahap sekolah+tahun yang sedang aktif -
     * dipakai bareng oleh danaBospTahapTerisi() & anggaranBospUntukSekolah()
     * supaya query-nya konsisten dari 1 tempat (dipanggil di luar
     * render(), jadi query langsung per-panggilan, BUKAN dari hasil eager
     * load render() - lihat render() untuk versi yang dieager-load demi
     * menghindari N+1 saat menampilkan seluruh baris tabel).
     */
    protected function danaBospTahapUntukSekolah(int $profilSekolahId): ?DanaBospTahap
    {
        return DanaBospTahap::where('profil_sekolah_id', $profilSekolahId)
            ->where('tahun', $this->tahun)
            ->first();
    }

    /**
     * Gate (permintaan user 2026-09-23): true kalau sekolah ini SUDAH
     * mengisi Dana BOSP Tahap 1 & 2 - Penerimaan BOSP (field manual
     * jumlah_siswa & jumlah_dana_bosp_per_tahun, sumber rumus Total
     * Penerimaan BOSP Setahun) untuk tahun yang sedang aktif - selama
     * belum, baris Rekap RKAS sekolah ini TERKUNCI (lihat isi(),
     * updated(), simpan(), & tampilan terkunci di index.blade.php).
     */
    protected function danaBospTahapTerisi(int $profilSekolahId): bool
    {
        return $this->danaBospTahapTerisiDari($this->danaBospTahapUntukSekolah($profilSekolahId));
    }

    /**
     * Versi danaBospTahapTerisi() yang menerima baris DanaBospTahap yang
     * SUDAH diambil sebelumnya (lihat render(), yang meng-eager-load
     * relasi danaBospTahap untuk SELURUH sekolah sekaligus supaya tidak
     * query 1-per-1/N+1 saat menampilkan tabel).
     */
    protected function danaBospTahapTerisiDari(?DanaBospTahap $danaBospTahap): bool
    {
        return $danaBospTahap !== null
            && $danaBospTahap->jumlah_siswa !== null
            && $danaBospTahap->jumlah_dana_bosp_per_tahun !== null;
    }

    public function isi(int $profilSekolahId): void
    {
        abort_unless($this->bolehEdit($profilSekolahId), 403);

        $danaBospTahap = $this->danaBospTahapUntukSekolah($profilSekolahId);

        // Gate: kalau Dana BOSP Tahap - Penerimaan BOSP sekolah ini belum
        // diisi, form Rekap RKAS tidak boleh dibuka sama sekali (tombol
        // "Isi/Edit" juga sudah diganti jadi link ke Dana BOSP Tahap di
        // index.blade.php untuk baris ini - guard di sini jaga-jaga kalau
        // ada percobaan panggil langsung di luar UI normal).
        if (! $this->danaBospTahapTerisiDari($danaBospTahap)) {
            return;
        }

        $rekap = RekapRkas::where('profil_sekolah_id', $profilSekolahId)
            ->where('tahun', $this->tahun)
            ->first();

        $this->sekolahId = $profilSekolahId;
        $this->editingId = $rekap?->id;

        foreach ($this->daftarFieldAngka() as $field) {
            $this->{$field} = $rekap?->{$field} !== null ? (string) $rekap->{$field} : '';
        }

        // Anggaran BOSP {tahun} OTOMATIS (bukan bagian daftarFieldAngka()
        // lagi) - selalu diambil langsung dari Dana BOSP Tahap TERBARU
        // (bukan dari kolom rekap_rkas.anggaran_bosp yang tersimpan),
        // supaya form selalu menampilkan nilai paling mutakhir.
        $this->anggaran_bosp = (string) RekapRkas::anggaranBospOtomatis($danaBospTahap->total_penerimaan_setahun);

        $this->formInstance++;
        $this->showForm = true;
        $this->dispatch('open-modal', 'rekap-rkas-form');
    }

    public function resetForm(): void
    {
        $this->reset(array_merge(['sekolahId', 'editingId', 'anggaran_bosp'], $this->daftarFieldAngka()));
        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'rekap-rkas-form');
    }

    public function simpan(): void
    {
        abort_unless($this->sekolahId && $this->bolehEdit($this->sekolahId), 403);

        $danaBospTahap = $this->danaBospTahapUntukSekolah($this->sekolahId);

        // Gate (permintaan user 2026-09-23) - lihat isi()/updated().
        abort_unless($this->danaBospTahapTerisiDari($danaBospTahap), 403);

        $rules = [];
        foreach ($this->daftarFieldAngka() as $field) {
            $rules[$field] = ['nullable', 'integer'];
        }

        $validated = $this->validate($rules);

        foreach ($validated as $field => $nilai) {
            $validated[$field] = $nilai === '' || $nilai === null ? null : (int) $nilai;
        }

        // Jml Sesudah & Selisih untuk kategori yang rumusnya sudah
        // ditentukan (KATEGORI_RUMUS) SELALU dihitung ulang di sini,
        // MENIMPA apapun yang mungkin masih ada di $validated untuk kedua
        // field itu (property Livewire-nya sudah tidak punya kotak input
        // lagi di form - lihat index.blade.php) - supaya rumus konsisten
        // dipakai baik dari jalur modal ini maupun dari jalur input
        // langsung di kotak tabel (lihat updated()/hitungUlangRumus()).
        foreach (RekapRkas::KATEGORI_RUMUS as $kategori) {
            [$jmlSesudah, $selisih] = RekapRkas::hitungJmlSesudahDanSelisih(
                $validated[$kategori.'_sebelum'],
                $validated[$kategori.'_realisasi_tahap1'],
                $validated[$kategori.'_perubahan_tahap2'],
            );
            $validated[$kategori.'_jml_sesudah'] = $jmlSesudah;
            $validated[$kategori.'_selisih'] = $selisih;
        }

        // Sebelum/Sesudah/Selisih pada baris JUMLAH SELALU dihitung ulang
        // di sini juga, MENIMPA apapun yang mungkin masih ada di
        // $validated (property Livewire-nya sudah tidak punya kotak input
        // lagi di form - lihat index.blade.php) - dipakai nilai dari
        // $validated yang di atas SUDAH final (Sebelum asli + Jml Sesudah
        // hasil hitung ulang untuk seluruh 5 kategori), supaya rumus baris
        // JUMLAH konsisten dengan jalur input langsung di kotak tabel
        // (lihat updated()/hitungUlangJumlahBaris()).
        $sebelumPerKategori = [];
        $jmlSesudahPerKategori = [];
        foreach (array_keys(RekapRkas::KATEGORI) as $kategori) {
            $sebelumPerKategori[$kategori] = $validated[$kategori.'_sebelum'];
            $jmlSesudahPerKategori[$kategori] = $validated[$kategori.'_jml_sesudah'];
        }
        [$validated['jumlah_sebelum'], $validated['jumlah_sesudah'], $validated['jumlah_selisih']] =
            RekapRkas::hitungJumlahBaris($sebelumPerKategori, $jmlSesudahPerKategori);

        // Anggaran BOSP {tahun} OTOMATIS SELALU dihitung ulang di sini
        // juga (bukan bagian $rules/$validated lagi - form-nya sudah
        // tidak punya kotak input untuk field ini, lihat index.blade.php)
        // - write-through dari Dana BOSP Tahap TERBARU, konsisten dengan
        // jalur isi() & updated().
        $validated['anggaran_bosp'] = RekapRkas::anggaranBospOtomatis($danaBospTahap->total_penerimaan_setahun);

        $validated['created_by'] = auth()->id();

        RekapRkas::updateOrCreate(
            ['profil_sekolah_id' => $this->sekolahId, 'tahun' => $this->tahun],
            $validated
        );

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'rekap-rkas-form');
        session()->flash('status', 'Data Rekap RKAS Awal-Perubahan berhasil disimpan.');
    }

    /**
     * Ambil daftar sekolah (sudah di-map dengan rekapRkasTahunIni,
     * danaBospTahunIni, & anggaranBospOtomatis) sesuai peran & tahun yang
     * aktif - dipakai bareng oleh render() (tampilan tabel) & unduhExcel()/
     * unduhPdf() (permintaan user 2026-09-26) supaya query & urutan
     * sekolahnya SELALU identik antara yang ditampilkan di layar & yang
     * diunduh ("field yang sama dengan di aplikasi").
     */
    private function daftarSekolahDenganRekap()
    {
        $query = ProfilSekolah::with([
            'rekapRkas' => function ($q) {
                $q->where('tahun', $this->tahun);
            },
            // Eager-load Dana BOSP Tahap sekolah+tahun yang sama sekaligus
            // untuk SELURUH sekolah (permintaan user 2026-09-23: gate +
            // Anggaran BOSP otomatis) - supaya tidak query 1-per-1/N+1
            // saat menampilkan seluruh baris tabel (lihat map() di bawah).
            'danaBospTahap' => function ($q) {
                $q->where('tahun', $this->tahun);
            },
        ]);

        if (! $this->bolehKelolaSemua()) {
            $query->where('id', auth()->user()->profil_sekolah_id);
        }

        return $query
            ->orderByRaw("CASE WHEN status = 'negeri' THEN 0 WHEN status = 'swasta' THEN 1 ELSE 2 END")
            ->orderByRaw('kecamatan IS NULL')
            ->orderBy('kecamatan')
            ->orderBy('nama_sekolah')
            ->get()
            ->map(function ($sekolah) {
                $sekolah->rekapRkasTahunIni = $sekolah->rekapRkas->first();
                $sekolah->danaBospTahunIni = $sekolah->danaBospTahap->first();
                $sekolah->danaBospTahapTerisi = $this->danaBospTahapTerisiDari($sekolah->danaBospTahunIni);
                $sekolah->anggaranBospOtomatis = RekapRkas::anggaranBospOtomatis($sekolah->danaBospTahunIni?->total_penerimaan_setahun);

                // PENTING (perbaikan 2026-09-26, ditemukan user lewat hasil
                // Unduh Excel Rekap RKAS): selama sekolah BELUM mengisi Dana
                // BOSP Tahap 1&2 - Penerimaan BOSP untuk tahun aktif, baris
                // Rekap RKAS-nya TERKUNCI di layar (lihat index.blade.php -
                // seluruh kolom diganti pesan terkunci, BUKAN menampilkan
                // data). Tapi kalau sekolah itu KEBETULAN masih menyimpan
                // data lama di tabel rekap_rkas (mis. diisi sebelum dikunci,
                // lalu Dana BOSP Tahap-nya dikosongkan lagi belakangan),
                // data lama itu tetap ada di $sekolah->rekapRkas & akan
                // "bocor" ke konsumen lain method ini (Unduh PDF/Excel,
                // baris JUMLAH di hitungTotalBaris()) walau di layar sudah
                // disembunyikan. Paksa jadi null di sini (jawaban user:
                // "harusnya datanya nol/tidak ada, kecuali baris JUMLAH
                // [total keseluruhan] yang tetap ada datanya") supaya SEMUA
                // konsumen menganggap baris terkunci = kosong (0), dan baris
                // JUMLAH otomatis ikut benar (tidak lagi kemasukan data lama
                // sekolah yang terkunci). Data asli di database TIDAK
                // disentuh - murni representasi in-memory untuk request ini;
                // begitu Dana BOSP Tahap-nya diisi lagi, data lama itu akan
                // otomatis muncul kembali seperti semula.
                if (! $sekolah->danaBospTahapTerisi) {
                    $sekolah->rekapRkasTahunIni = null;
                }

                return $sekolah;
            });
    }

    /**
     * Baris "Jumlah" (total) - penjumlahan seluruh baris sekolah pada
     * $daftarSekolah yang diberikan (sesuai scope peran: seluruh sekolah
     * untuk Superadmin, hanya sekolah sendiri untuk Admin BOSP). Dipakai
     * bareng oleh render(), unduhExcel(), & unduhPdf() supaya angka
     * totalnya selalu konsisten.
     *
     * @return array<string, int>
     */
    private function hitungTotalBaris($daftarSekolah): array
    {
        $totalBaris = [];
        foreach ($this->daftarFieldAngka() as $field) {
            $totalBaris[$field] = $daftarSekolah->sum(function ($sekolah) use ($field) {
                return (int) ($sekolah->rekapRkasTahunIni?->{$field} ?? 0);
            });
        }

        $totalBaris['anggaran_bosp'] = $daftarSekolah->sum(fn ($sekolah) => (int) ($sekolah->anggaranBospOtomatis ?? 0));

        return $totalBaris;
    }

    /**
     * Unduh Excel Rekap RKAS Awal-Perubahan untuk tahun yang sedang aktif
     * (permintaan user 2026-09-26, "field yang sama dengan di aplikasi...
     * file yang sudah rapih tidak perlu diedit kembali") - Superadmin:
     * seluruh sekolah, Admin BOSP: sekolah sendiri saja (sama seperti
     * scope $daftarSekolah di layar).
     */
    public function unduhExcel()
    {
        $daftarSekolah = $this->daftarSekolahDenganRekap();
        $totalBaris = $this->hitungTotalBaris($daftarSekolah);

        return Excel::download(
            new RekapRkasExport($daftarSekolah, $this->tahun, $totalBaris),
            'rekap-rkas-'.$this->tahun.'.xlsx'
        );
    }

    /**
     * Unduh PDF Rekap RKAS Awal-Perubahan untuk tahun yang sedang aktif
     * (permintaan user 2026-09-26) - kertas A3 landscape (bukan A4 seperti
     * menu lain) karena tabelnya jauh lebih lebar (31 kolom data).
     */
    public function unduhPdf()
    {
        $daftarSekolah = $this->daftarSekolahDenganRekap();
        $totalBaris = $this->hitungTotalBaris($daftarSekolah);

        $pdf = Pdf::loadView('pdf.rekap-rkas', [
            'daftarSekolah' => $daftarSekolah,
            'kategori' => RekapRkas::KATEGORI,
            'tahun' => $this->tahun,
            'totalBaris' => $totalBaris,
        ])->setPaper('a3', 'landscape');

        // PENTING: Pdf::download() bawaan mengembalikan Illuminate\Http\Response
        // BIASA (bukan BinaryFileResponse/StreamedResponse) - Livewire hanya
        // mengenali unduhan file dari method komponen kalau responsnya salah
        // satu dari 2 jenis itu, jadi kalau langsung di-return apa adanya,
        // isi PDF (biner) malah dicoba di-encode sebagai JSON oleh Livewire
        // dan gagal ("Malformed UTF-8 characters"). Solusinya (mengikuti
        // pola yang sudah dipakai di PendataanOps\Unduhan\Index::unduhPdf()):
        // simpan dulu ke file sementara, lalu pakai response()->download()
        // bawaan Laravel yang menghasilkan BinaryFileResponse.
        $namaFile = 'rekap-rkas-'.$this->tahun.'.pdf';
        $pathSementara = tempnam(sys_get_temp_dir(), 'rekap-rkas-').'.pdf';
        file_put_contents($pathSementara, $pdf->output());

        return response()->download($pathSementara, $namaFile)->deleteFileAfterSend(true);
    }

    public function render()
    {
        $daftarSekolah = $this->daftarSekolahDenganRekap();

        // Isi ulang $baris (data untuk kotak input langsung di tabel) dari
        // data ter-terbaru database setiap kali render() dipanggil - ini
        // juga yang membuat kotak otomatis kembali ke nilai semula kalau
        // updated() di atas menolak input yang tidak valid (lihat komentar
        // di sana).
        foreach ($daftarSekolah as $sekolah) {
            $rekap = $sekolah->rekapRkasTahunIni;
            $this->baris[$sekolah->id] = [];

            foreach ($this->daftarFieldAngka() as $field) {
                $this->baris[$sekolah->id][$field] = $rekap?->{$field} !== null ? (string) $rekap->{$field} : '';
            }

            // Anggaran BOSP {tahun} OTOMATIS (permintaan user 2026-09-23) -
            // SENGAJA diambil langsung dari Dana BOSP Tahap ter-eager-load
            // di atas ($sekolah->anggaranBospOtomatis), BUKAN dari kolom
            // rekap_rkas.anggaran_bosp yang tersimpan - supaya tabel selalu
            // menampilkan nilai paling mutakhir walau baris Rekap RKAS-nya
            // belum pernah disimpan ulang sejak Dana BOSP Tahap terakhir
            // diubah.
            $this->baris[$sekolah->id]['anggaran_bosp'] = $sekolah->anggaranBospOtomatis !== null
                ? (string) $sekolah->anggaranBospOtomatis
                : '';
        }

        $tahunOptions = range(now()->year - 2, now()->year + 1);

        // Baris "Jumlah" (total) di bawah tabel - lihat hitungTotalBaris()
        // (diekstrak 2026-09-26 supaya dipakai bareng oleh unduhExcel()/
        // unduhPdf() juga, sesuai jawaban AskUserQuestion 2026-09-09
        // lanjutan ke-3: "Otomatis dihitung sistem (sum kolom)").
        $totalBaris = $this->hitungTotalBaris($daftarSekolah);

        return view('livewire.pendataan-bosp.rekap-rkas.index', [
            'daftarSekolah' => $daftarSekolah,
            'kategori' => RekapRkas::KATEGORI,
            'tahunOptions' => array_reverse($tahunOptions),
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            'totalBaris' => $totalBaris,
        ]);
    }
}
