<?php

namespace App\Livewire\PendataanBosp\DanaBospTahap;

use App\Livewire\Concerns\HasZoomTampilan;
use App\Models\DanaBospTahap;
use App\Models\ProfilSekolah;
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
 * - Admin BOSP: form VERTIKAL (5 field berbaris ke bawah sejajar kiri)
 *   untuk sekolahnya sendiri saja, BUKAN tabel.
 * - Superadmin: tabel multi-sekolah dengan tanda +/- di kolom No (pola
 *   "tabel diringkas per sekolah" yang sudah ada di aplikasi ini, mis.
 *   RincianBelanjaModal) - expand menampilkan 5 field yang sama berbaris
 *   ke bawah untuk sekolah itu.
 * Kedua layout memakai kotak input yang sama (wire:model.blur ke
 * "baris.{sekolahId}.{field}"), cuma disusun beda secara visual.
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

    public function mount(): void
    {
        $this->tahun = now()->year;
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
        if (! in_array($tab, ['penerimaan', 'tarik_tunai'], true)) {
            return;
        }

        $this->tabAktif = $tab;
    }

    /**
     * Field yang boleh diedit LANGSUNG lewat kotak (3 manual Tab 1 + 5
     * field Tab 2) - TIDAK termasuk 3 field hasil rumus Tab 1
     * (total_penerimaan_setahun, penerimaan_tahap_1, penerimaan_tahap_2),
     * yang selalu dihitung otomatis lewat DanaBospTahap::hitungRumusTab1().
     */
    protected function daftarFieldManual(): array
    {
        return array_merge(DanaBospTahap::FIELD_MANUAL_TAB1, DanaBospTahap::FIELD_TAB2);
    }

    /**
     * SELURUH field (manual + hasil rumus) - dipakai untuk mengisi $baris
     * supaya kolom hasil rumus Tab 1 juga tampil (walau read-only).
     */
    protected function daftarFieldSemua(): array
    {
        return array_merge(DanaBospTahap::FIELD_MANUAL_TAB1, DanaBospTahap::FIELD_RUMUS_TAB1, DanaBospTahap::FIELD_TAB2);
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
        }

        DanaBospTahap::updateOrCreate(
            ['profil_sekolah_id' => $sekolahId, 'tahun' => $this->tahun],
            array_merge($data, ['created_by' => auth()->id()])
        );

        $this->revisiBaris[$sekolahId] = ($this->revisiBaris[$sekolahId] ?? 0) + 1;
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
            ->orderByRaw("CASE WHEN status = 'negeri' THEN 0 WHEN status = 'swasta' THEN 1 ELSE 2 END")
            ->orderByRaw('kecamatan IS NULL')
            ->orderBy('kecamatan')
            ->orderBy('nama_sekolah')
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

        // Baris "Jumlah" Tab 2 - sum seluruh sekolah (semua field Tab 2
        // memang kuantitas Rupiah yang bisa dijumlahkan).
        $totalTab2 = [];
        foreach (DanaBospTahap::FIELD_TAB2 as $field) {
            $totalTab2[$field] = $daftarSekolah->sum(function ($sekolah) use ($field) {
                return (int) ($sekolah->danaBospTahunIni?->{$field} ?? 0);
            });
        }

        return view('livewire.pendataan-bosp.dana-bosp-tahap.index', [
            'daftarSekolah' => $daftarSekolah,
            'tahunOptions' => array_reverse($tahunOptions),
            'bolehKelolaSemua' => $this->bolehKelolaSemua(),
            'totalTab1' => $totalTab1,
            'totalTab2' => $totalTab2,
            'labelTab2' => DanaBospTahap::LABEL_TAB2,
        ]);
    }
}
