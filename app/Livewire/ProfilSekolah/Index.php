<?php

namespace App\Livewire\ProfilSekolah;

use App\Models\AksesDataLog;
use App\Exports\DataPtkExport;
use App\Exports\ProfilSekolahExport;
use App\Exports\ProfilSekolahLaporanExport;
use App\Imports\DataPtkImport;
use App\Imports\ProfilSekolahImport;
use App\Livewire\Concerns\HasZoomTampilan;
use App\Models\DataPtk;
use App\Models\ProfilSekolah;
use App\Models\User;
use App\Support\UrutanDaftarPtkKecamatan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

/**
 * Menu "Data Sekolah" (dulu "Profil Sekolah" - diganti nama permintaan
 * user 2026-10-01). 2 tab:
 * - Tab "profil" (Profil Sekolah): SAMA PERSIS seperti sebelumnya, tidak
 *   ada perubahan logika sama sekali - hanya dipindah ke dalam tab.
 * - Tab "data_ptk" (Data PTK, BARU 2026-10-01): biodata lengkap PTK,
 *   gabungan semua sekolah dalam 1 tabel (jawaban AskUserQuestion
 *   "Gabungan semua sekolah"), pola CRUD/search/pagination/akses-per-
 *   sekolah disalin dari App\Livewire\PendataanOps\Lampiran2a\Index.
 *   Akses tambah/ubah/hapus: Superadmin + Admin OPS (jawaban
 *   AskUserQuestion "Admin OPS + Superadmin") - Admin BOSP & sekolah lain
 *   hanya bisa melihat (otomatis terkunci ke sekolahnya sendiri, sama
 *   seperti tab Profil Sekolah). Tombol "Export Excel" (format polos utk
 *   re-import) & "Unduh" (laporan rapi berjudul "DATA PTK (nama
 *   sekolah)") sengaja 2 fungsi TERPISAH (jawaban AskUserQuestion "Dua
 *   fungsi berbeda"). Import Excel: NIK yang sudah ada datanya DITIMPA/
 *   diperbarui (jawaban AskUserQuestion "Timpa/update data lama").
 *
 * CATATAN TAFSIRAN field wajib/opsional & format NIK/NUPTK/NIP - lihat
 * App\Models\DataPtk & migration create_data_ptk_table.
 */
#[Layout('layouts.app')]
#[Title('Data Sekolah')]
class Index extends Component
{
    use HasZoomTampilan, WithFileUploads, WithPagination;

    public const TAB_PROFIL = 'profil';

    public const TAB_DATA_PTK = 'data_ptk';

    /**
     * Logo Sekolah yang diupload OTOMATIS di-crop (center-crop, pola sama
     * persis dengan App\Livewire\PendataanOps\Index::simpanFotoOps())
     * supaya persis 450x450 px persegi, berapapun ukuran/rasio asli
     * gambar yang diupload - permintaan user 2026-10-15, dipilih metode
     * "crop ke tengah" (bukan tambah latar putih) lewat AskUserQuestion.
     */
    public const LOGO_SEKOLAH_LEBAR_PX = 450;

    public const LOGO_SEKOLAH_TINGGI_PX = 450;

    #[Url(as: 'tab')]
    public string $tabUtama = self::TAB_PROFIL;

    public function pindahTabUtama(string $tab): void
    {
        if (! in_array($tab, [self::TAB_PROFIL, self::TAB_DATA_PTK], true)) {
            return;
        }

        $this->tabUtama = $tab;
        $this->resetPage('ptkPage');
    }

    // =====================================================================
    // TAB "Profil Sekolah" - TIDAK ADA PERUBAHAN LOGIKA dari sebelumnya,
    // hanya dipindah ke dalam tab. Properti & method di bawah ini PERSIS
    // seperti versi sebelum fitur Data PTK ditambahkan.
    // =====================================================================

    public ?int $editingId = null;

    // Filter tabel (khusus Superadmin - Admin OPS/BOSP hanya lihat 1 baris).
    public string $filterNamaSekolah = '';

    public string $filterStatus = '';

    public string $filterKecamatan = '';

    // Field data induk (hanya diisi/diubah oleh Superadmin)
    public string $npsn = '';

    public string $kode_upb = '';

    public string $nama_sekolah = '';

    public string $status = '';

    public string $kecamatan = '';

    public string $subrayon = '';

    // Field data operasional (bisa diubah Superadmin ataupun pemilik sekolah)
    public string $nama_kepala_sekolah = '';

    public string $nip_kepala_sekolah = '';

    public string $no_whatsapp_kepala_sekolah = '';

    public string $status_kepegawaian_kepsek = '';

    public string $nama_pengawas = '';

    public string $nip_pengawas = '';

    public string $nama_kasie_kpml = '';

    public string $nip_kasie_kpml = '';

    public string $nama_bendahara = '';

    public string $nip_bendahara = '';

    public string $status_kepegawaian_bendahara = '';

    public string $alamat_sekolah = '';

    public string $nama_yayasan = '';

    /**
     * Upload Logo Sekolah (permintaan user 2026-10-15) - auto-save begitu
     * file dipilih (pola sama seperti kopSuratBaru di
     * App\Livewire\PendataanOps\SuratTpg\Index), TIDAK ikut tombol
     * "Simpan" form utama. Disimpan ke App\Models\ProfilSekolah::
     * logo_sekolah, dipakai otomatis di tab "Cover SMPN"/"Cover SMP-S"
     * menu Format Surat Rekomendasi & Pembatalan TPG.
     */
    public $logoSekolahBaru = null;

    public bool $showForm = false;

    public ?int $confirmingDeleteId = null;

    public ?string $errorHapus = null;

    public $fileImport = null;

    public ?string $errorImport = null;

    public ?string $errorExport = null;

    /**
     * Jenis kertas untuk "Unduh PDF" (permintaan user 2026-10-16:
     * "tambahkan juga tombol jenis kertas supaya bisa atur kertas nya" -
     * laporan Profil Sekolah berisi banyak kolom (20 kolom) & sebelumnya
     * terpotong di PDF A4 landscape) - lihat daftarKertasOptionsLaporan().
     */
    public string $jenisKertas = 'a4';

    /**
     * Popup wajib "profil sekolah belum lengkap" - hanya untuk Admin
     * OPS/Admin BOSP yang profil sekolahnya sendiri belum lengkap, supaya
     * langsung disadari & segera dilengkapi. Muncul setiap kali halaman
     * ini dibuka selama belum lengkap (bukan cuma sekali).
     */
    public bool $tampilkanPeringatanBelumLengkap = false;

    public function mount(): void
    {
        $user = auth()->user();

        if ($user->isSuperadmin()) {
            return;
        }

        $sekolah = $user->profilSekolah()->first();

        if (! $sekolah || ! $sekolah->isLengkap()) {
            $this->tampilkanPeringatanBelumLengkap = true;
            $this->dispatch('open-modal', 'profil-belum-lengkap');
        }
    }

    public function tutupPeringatanBelumLengkap(): void
    {
        $this->tampilkanPeringatanBelumLengkap = false;
        $this->dispatch('close-modal', 'profil-belum-lengkap');
    }

    protected function bolehKelolaSemua(): bool
    {
        return auth()->user()->isSuperadmin();
    }

    /**
     * Query dasar tabel Profil Sekolah (tab "profil") - dipakai bersama
     * oleh render() & tombol "Unduh Excel"/"Unduh PDF" (permintaan user
     * 2026-10-16), supaya hasil unduhan SELALU mengikuti filter/pencarian
     * yang sedang aktif pada tabel (jawaban AskUserQuestion "Ikuti
     * filter/pencarian yang sedang aktif") - diekstrak dari render() TANPA
     * mengubah logikanya sama sekali.
     */
    protected function queryDasarSekolah()
    {
        $query = ProfilSekolah::query();

        if (! $this->bolehKelolaSemua()) {
            // Admin OPS/Admin BOSP hanya melihat data sekolahnya sendiri.
            $query->where('id', auth()->user()->profil_sekolah_id);
        } else {
            if ($this->filterNamaSekolah !== '') {
                $query->where('id', $this->filterNamaSekolah);
            }
            if ($this->filterStatus !== '') {
                $query->where('status', $this->filterStatus);
            }
            if ($this->filterKecamatan !== '') {
                $query->where('kecamatan', $this->filterKecamatan);
            }
        }

        return $query;
    }

    protected function bolehEdit(ProfilSekolah $sekolah): bool
    {
        return $this->bolehKelolaSemua() || auth()->user()->profil_sekolah_id === $sekolah->id;
    }

    public function tambah(): void
    {
        abort_unless($this->bolehKelolaSemua(), 403);

        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('open-modal', 'sekolah-form');
    }

    public function edit(int $id): void
    {
        $sekolah = ProfilSekolah::findOrFail($id);

        abort_unless($this->bolehEdit($sekolah), 403);

        $this->editingId = $sekolah->id;
        $this->npsn = (string) $sekolah->npsn;
        $this->kode_upb = (string) $sekolah->kode_upb;
        $this->nama_sekolah = (string) $sekolah->nama_sekolah;
        $this->status = (string) $sekolah->status;
        $this->kecamatan = (string) $sekolah->kecamatan;
        $this->subrayon = (string) $sekolah->subrayon;
        $this->nama_kepala_sekolah = (string) $sekolah->nama_kepala_sekolah;
        $this->nip_kepala_sekolah = (string) $sekolah->nip_kepala_sekolah;
        $this->no_whatsapp_kepala_sekolah = (string) $sekolah->no_whatsapp_kepala_sekolah;
        $this->status_kepegawaian_kepsek = (string) $sekolah->status_kepegawaian_kepsek;
        $this->nama_pengawas = (string) $sekolah->nama_pengawas;
        $this->nip_pengawas = (string) $sekolah->nip_pengawas;
        $this->nama_kasie_kpml = (string) $sekolah->nama_kasie_kpml;
        $this->nip_kasie_kpml = (string) $sekolah->nip_kasie_kpml;
        $this->nama_bendahara = (string) $sekolah->nama_bendahara;
        $this->nip_bendahara = (string) $sekolah->nip_bendahara;
        $this->status_kepegawaian_bendahara = (string) $sekolah->status_kepegawaian_bendahara;
        $this->alamat_sekolah = (string) $sekolah->alamat_sekolah;
        $this->nama_yayasan = (string) $sekolah->nama_yayasan;
        $this->showForm = true;
        $this->dispatch('open-modal', 'sekolah-form');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'npsn', 'kode_upb', 'nama_sekolah', 'status', 'kecamatan', 'subrayon',
            'nama_kepala_sekolah', 'nip_kepala_sekolah', 'no_whatsapp_kepala_sekolah', 'status_kepegawaian_kepsek',
            'nama_pengawas', 'nip_pengawas',
            'nama_kasie_kpml', 'nip_kasie_kpml',
            'nama_bendahara', 'nip_bendahara', 'status_kepegawaian_bendahara',
            'alamat_sekolah', 'nama_yayasan', 'logoSekolahBaru',
        ]);
        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'sekolah-form');
    }

    public function simpan(): void
    {
        $sekolah = $this->editingId ? ProfilSekolah::findOrFail($this->editingId) : null;

        if ($sekolah) {
            abort_unless($this->bolehEdit($sekolah), 403);
        } else {
            abort_unless($this->bolehKelolaSemua(), 403);
        }

        $kelolaSemua = $this->bolehKelolaSemua();

        // Dicek SEBELUM data baru disimpan, supaya nanti bisa dibandingkan
        // dengan status kelengkapan SESUDAH disimpan - dipakai untuk
        // mendeteksi momen profil sekolah "baru saja" menjadi lengkap
        // (lihat pengecekan redirect otomatis di akhir method ini).
        $sudahLengkapSebelumnya = $sekolah?->isLengkap() ?? false;

        $bendaharaAsn = in_array($this->status_kepegawaian_bendahara, ['PNS', 'ASN PPPK', 'ASN PPPK-PW'], true);

        $rules = [
            'nama_kepala_sekolah' => ['nullable', 'string', 'max:255'],
            'nip_kepala_sekolah' => ['nullable', 'string', 'max:50'],
            'no_whatsapp_kepala_sekolah' => ['nullable', 'regex:/^08[0-9]{8,11}$/'],
            'status_kepegawaian_kepsek' => ['nullable', Rule::in(array_keys(ProfilSekolah::statusKepegawaianKepsekOptions()))],
            'nama_pengawas' => ['nullable', 'string', 'max:255'],
            'nip_pengawas' => ['nullable', 'string', 'max:50'],
            'nama_kasie_kpml' => ['nullable', 'string', 'max:255'],
            'nip_kasie_kpml' => ['nullable', 'string', 'max:50'],
            'nama_bendahara' => ['nullable', 'string', 'max:255'],
            'nip_bendahara' => $bendaharaAsn
                ? ['nullable', 'regex:/^[0-9]{18}$/']
                : ['nullable', 'in:-'],
            'status_kepegawaian_bendahara' => ['nullable', Rule::in(array_keys(ProfilSekolah::statusKepegawaianOptions()))],
            'alamat_sekolah' => ['nullable', 'string', 'max:1000'],
            'nama_yayasan' => ['nullable', 'string', 'max:255'],
        ];

        if ($kelolaSemua) {
            $rules = array_merge($rules, [
                'npsn' => [
                    'required', 'string', 'max:20',
                    Rule::unique('profil_sekolah', 'npsn')->ignore($this->editingId),
                ],
                'kode_upb' => ['nullable', 'string', 'max:255'],
                'nama_sekolah' => ['required', 'string', 'max:255'],
                'status' => ['required', Rule::in(array_keys(ProfilSekolah::statusOptions()))],
                'kecamatan' => ['required', 'string', 'max:255'],
                'subrayon' => ['nullable', 'string', 'max:255'],
            ]);
        }

        $validated = $this->validate($rules, [
            'no_whatsapp_kepala_sekolah.regex' => 'No Whatsapp Kepala Sekolah harus format nomor WhatsApp Indonesia yang benar: diawali "08" dan total 10-13 digit angka (contoh: 081234567890). Silakan periksa dan ketik ulang.',
            'nip_bendahara.regex' => 'NIP Bendahara harus berupa 18 digit angka untuk status kepegawaian ASN (PNS/ASN PPPK/ASN PPPK-PW).',
            'nip_bendahara.in' => 'NIP Bendahara untuk status kepegawaian Non-ASN (Honorer) harus diisi tanda "-".',
        ]);

        $data = [
            'nama_kepala_sekolah' => $validated['nama_kepala_sekolah'] ?: null,
            'nip_kepala_sekolah' => $validated['nip_kepala_sekolah'] ?: null,
            'no_whatsapp_kepala_sekolah' => $validated['no_whatsapp_kepala_sekolah'] ?: null,
            'status_kepegawaian_kepsek' => $validated['status_kepegawaian_kepsek'] ?: null,
            'nama_pengawas' => $validated['nama_pengawas'] ?: null,
            'nip_pengawas' => $validated['nip_pengawas'] ?: null,
            'nama_kasie_kpml' => $validated['nama_kasie_kpml'] ?: null,
            'nip_kasie_kpml' => $validated['nip_kasie_kpml'] ?: null,
            'nama_bendahara' => $validated['nama_bendahara'] ?: null,
            'nip_bendahara' => $validated['nip_bendahara'] ?: null,
            'status_kepegawaian_bendahara' => $validated['status_kepegawaian_bendahara'] ?: null,
            'alamat_sekolah' => $validated['alamat_sekolah'] ?: null,
            'nama_yayasan' => $validated['nama_yayasan'] ?: null,
        ];

        if ($kelolaSemua) {
            $data['npsn'] = $validated['npsn'];
            $data['kode_upb'] = $validated['kode_upb'] ?: null;
            $data['nama_sekolah'] = $validated['nama_sekolah'];
            $data['status'] = $validated['status'];
            $data['kecamatan'] = $validated['kecamatan'];
            $data['subrayon'] = $validated['subrayon'] ?: null;
        }

        if ($sekolah) {
            $sekolah->update($data);
        } else {
            $sekolah = ProfilSekolah::create($data);
        }

        // Logo Sekolah yang dipilih di mode Tambah Sekolah (belum ada ID
        // sekolah saat file dipilih, jadi ditahan dulu oleh
        // updatedLogoSekolahBaru() - lihat docblock method itu) BARU
        // diproses & disimpan di sini, setelah $sekolah pasti sudah punya
        // ID (baru dibuat / sudah ada krn mode Edit). Di mode Edit normal,
        // $this->logoSekolahBaru SUDAH kosong duluan (auto-save saat
        // dipilih), jadi blok ini aman TIDAK menimpa logo yang sudah
        // tersimpan kecuali memang ada file baru yang masih pending.
        if ($this->logoSekolahBaru) {
            $pathLama = $sekolah->logo_sekolah;
            $pathBaru = $this->simpanLogoSekolah($this->logoSekolahBaru);
            $sekolah->update(['logo_sekolah' => $pathBaru]);

            if ($pathLama && Storage::disk('public')->exists($pathLama)) {
                Storage::disk('public')->delete($pathLama);
            }
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'sekolah-form');
        // Supaya menu di sidebar (mis. "Pendataan OPS - Identitas OPS")
        // langsung muncul kalau Profil Sekolah baru saja jadi "Lengkap",
        // tanpa perlu F5/refresh manual.
        $this->dispatch('kelengkapan-diperbarui');
        session()->flash('status', 'Data sekolah berhasil disimpan.');

        // Auto-redirect ke menu Identitas OPS/Identitas Admin BOSP TEPAT
        // SAAT profil sekolah milik Admin OPS/Admin BOSP yang sedang login
        // baru saja menjadi lengkap (sebelumnya belum lengkap) - supaya
        // menu-menu lain langsung terbuka tanpa perlu refresh manual.
        // Sengaja HANYA dipicu saat transisi belum-lengkap -> lengkap
        // (bukan tiap kali profil yang sudah lengkap diedit ulang), dan
        // HANYA untuk profil sekolah milik AKUN INI SENDIRI (bukan saat
        // Superadmin mengedit profil sekolah lain).
        //
        // Popup sukses di halaman tujuan sengaja ditampilkan lewat session
        // flash (bukan query string) - sesuai jawaban klarifikasi user:
        // muncul SEKALI SAJA tepat saat redirect ini terjadi, tidak
        // muncul lagi walau halaman itu dibuka ulang/di-refresh setelahnya.
        $user = auth()->user();

        if (
            ! $user->isSuperadmin()
            && $user->profil_sekolah_id === $sekolah->id
            && ! $sudahLengkapSebelumnya
            && $sekolah->fresh()->isLengkap()
        ) {
            session()->flash('profil_baru_lengkap', true);

            if ($user->isAdminOps()) {
                $this->redirect(route('pendataan-ops.index'), navigate: true);
            } elseif ($user->isAdminBosp()) {
                $this->redirect(route('pendataan-bosp.index'), navigate: true);
            }
        }
    }

    /**
     * Upload/ganti Logo Sekolah - perilaku beda tergantung mode form
     * (permintaan user 2026-10-15, diperluas 2026-10-15 sore supaya juga
     * bisa dipakai di mode Tambah Sekolah, sebelumnya hanya mode Edit):
     *
     * - Mode EDIT ($editingId ada): tetap PERSIS seperti sebelumnya -
     *   langsung tersimpan begitu file dipilih (auto-save, pola sama
     *   persis dgn App\Livewire\PendataanOps\SuratTpg\Index::
     *   updatedKopSuratBaru()), tanpa tombol "Simpan" terpisah.
     * - Mode TAMBAH SEKOLAH ($editingId belum ada, sekolah belum
     *   tersimpan jadi belum ada ID utk dikaitkan): file HANYA
     *   divalidasi & ditahan dulu di $this->logoSekolahBaru (preview
     *   pakai ->temporaryUrl(), lihat blade) - baru benar-benar diproses
     *   & disimpan saat tombol "Simpan" ditekan, lihat simpan().
     *
     * Disimpan ke App\Models\ProfilSekolah::logo_sekolah.
     *
     * TIDAK ada pengecekan orientasi landscape seperti Kop Surat (logo
     * sekolah lazimnya persegi/potret, bukan memanjang seperti
     * letterhead) - hanya validasi tipe & ukuran file gambar biasa.
     */
    public function updatedLogoSekolahBaru(): void
    {
        if (! $this->logoSekolahBaru) {
            return;
        }

        if ($this->editingId) {
            $sekolah = ProfilSekolah::findOrFail($this->editingId);
            abort_unless($this->bolehEdit($sekolah), 403);
        } else {
            abort_unless($this->bolehKelolaSemua(), 403);
        }

        $validator = Validator::make(
            ['logoSekolahBaru' => $this->logoSekolahBaru],
            ['logoSekolahBaru' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']],
            [],
            ['logoSekolahBaru' => 'Logo Sekolah']
        );

        if ($validator->fails()) {
            $this->addError('logoSekolahBaru', $validator->errors()->first('logoSekolahBaru'));
            $this->logoSekolahBaru = null;

            return;
        }

        if (! $this->editingId) {
            // Mode Tambah Sekolah: file sudah valid, tapi TAHAN dulu -
            // belum ada ID sekolah utk disimpan. Diproses nanti di
            // simpan() setelah ProfilSekolah::create() berhasil.
            $this->resetErrorBag('logoSekolahBaru');

            return;
        }

        $pathLama = $sekolah->logo_sekolah;
        $pathBaru = $this->simpanLogoSekolah($this->logoSekolahBaru);
        $sekolah->update(['logo_sekolah' => $pathBaru]);

        if ($pathLama && Storage::disk('public')->exists($pathLama)) {
            Storage::disk('public')->delete($pathLama);
        }

        $this->logoSekolahBaru = null;
        $this->resetErrorBag('logoSekolahBaru');
        session()->flash('status', 'Logo Sekolah berhasil diperbarui.');
    }

    /**
     * Crop & resize Logo Sekolah ke 450x450 px persegi (center-crop via
     * Intervention Image, pola sama persis dengan
     * App\Livewire\PendataanOps\Index::simpanFotoOps()) - permintaan user
     * 2026-10-15, supaya logo selalu tampil rapi di tab Cover SMPN/Cover
     * SMP-S berapapun ukuran/rasio asli gambar yang diupload admin.
     */
    private function simpanLogoSekolah($fileBaru): string
    {
        $manager = new ImageManager(new Driver);

        $gambar = $manager->read($fileBaru->getRealPath())
            ->cover(self::LOGO_SEKOLAH_LEBAR_PX, self::LOGO_SEKOLAH_TINGGI_PX);

        $namaFile = 'logo-sekolah/'.Str::random(40).'.jpg';
        Storage::disk('public')->put($namaFile, (string) $gambar->toJpeg(90));

        return $namaFile;
    }

    /**
     * Hapus Logo Sekolah (permintaan user 2026-10-15, pola sama dgn
     * App\Livewire\PendataanOps\SuratTpg\Index::hapusKopSurat()) -
     * supaya bisa dikosongkan dulu sebelum ganti, tanpa harus langsung
     * menimpa dengan upload baru.
     */
    public function hapusLogoSekolah(): void
    {
        abort_unless($this->editingId, 403);
        $sekolah = ProfilSekolah::findOrFail($this->editingId);
        abort_unless($this->bolehEdit($sekolah), 403);

        $pathLama = $sekolah->logo_sekolah;

        if ($pathLama && Storage::disk('public')->exists($pathLama)) {
            Storage::disk('public')->delete($pathLama);
        }

        $sekolah->update(['logo_sekolah' => null]);
        session()->flash('status', 'Logo Sekolah berhasil dihapus.');
    }

    public function konfirmasiHapus(int $id): void
    {
        abort_unless($this->bolehKelolaSemua(), 403);

        $this->errorHapus = null;
        $this->confirmingDeleteId = $id;
        $this->dispatch('open-modal', 'sekolah-hapus');
    }

    public function batalHapus(): void
    {
        $this->confirmingDeleteId = null;
        $this->errorHapus = null;
        $this->dispatch('close-modal', 'sekolah-hapus');
    }

    public function hapus(): void
    {
        abort_unless($this->bolehKelolaSemua(), 403);

        $sekolah = ProfilSekolah::findOrFail($this->confirmingDeleteId);

        if (User::where('profil_sekolah_id', $sekolah->id)->exists()) {
            $this->errorHapus = 'Sekolah ini masih memiliki akun Admin OPS/Admin BOSP. Hapus akun penggunanya terlebih dahulu di menu Pengguna.';

            return;
        }

        $sekolah->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'sekolah-hapus');
        session()->flash('status', 'Data sekolah berhasil dihapus.');
    }

    public function export()
    {
        abort_unless($this->bolehKelolaSemua(), 403);

        AksesDataLog::catat(AksesDataLog::JENIS_UNDUH, 'Profil Sekolah', 'Excel');
        $this->errorExport = null;

        $daftarSekolah = ProfilSekolah::urutStandar()->get();

        return Excel::download(new ProfilSekolahExport($daftarSekolah), 'profil-sekolah.xlsx');
    }

    /**
     * Pilihan "Jenis Kertas" untuk "Unduh PDF" (permintaan user
     * 2026-10-16) - A3 ditambahkan (berbeda dari dropdown kertas A4/F4
     * yang sudah ada di menu Formulir BOS K7/Surat TPG, karena laporan
     * ini landscape berkolom banyak - A3 lebih lebar, pola yang sama
     * sudah dipakai di menu Rekap RKAS untuk masalah serupa).
     */
    public function daftarKertasOptionsLaporan(): array
    {
        return [
            'a4' => 'Kertas A4',
            'f4' => 'Kertas HVS/Legal/F4',
            'a3' => 'Kertas A3',
        ];
    }

    /**
     * dompdf mengenal nama kertas F4 sebagai 'folio' (lihat pola yang
     * sama di FormulirBosK7\Index::cetak()/SuratTpg\Index::cetak()).
     */
    private function ukuranKertasDompdf(): string
    {
        return $this->jenisKertas === 'f4' ? 'folio' : $this->jenisKertas;
    }

    /**
     * Tombol "Unduh Excel" (permintaan user 2026-10-16) - laporan RAPI
     * berisi SEMUA field Profil Sekolah, bergaris tiap kolom & baris -
     * BERBEDA dari export()/tombol "Export Excel" di atas (hanya 4 kolom,
     * khusus re-import, khusus Superadmin). Tombol ini terbuka untuk
     * SEMUA role (Admin OPS ikut bisa mengunduh data sekolahnya sendiri),
     * sama seperti pola tombol "Unduh" Data PTK - lihat unduhPtk().
     * Hasilnya SELALU mengikuti filter/pencarian yang sedang aktif (lihat
     * queryDasarSekolah()).
     */
    public function unduhExcel()
    {
        AksesDataLog::catat(AksesDataLog::JENIS_UNDUH, 'Profil Sekolah', 'Excel (Laporan)');

        $daftarSekolah = $this->queryDasarSekolah()->urutStandar()->get();

        return Excel::download(new ProfilSekolahLaporanExport($daftarSekolah), 'profil-sekolah-laporan.xlsx');
    }

    /**
     * Tombol "Unduh PDF" (permintaan user 2026-10-16) - sama persis
     * dengan unduhExcel() di atas, hanya bentuk file berbeda. Pola
     * simpan-ke-file-sementara mengikuti Unduhan\Index::unduhPdf()
     * (Livewire tidak bisa langsung mengirim response PDF biner dari
     * method komponen tanpa ini).
     */
    public function unduhPdf()
    {
        AksesDataLog::catat(AksesDataLog::JENIS_UNDUH, 'Profil Sekolah', 'PDF');

        $daftarSekolah = $this->queryDasarSekolah()->urutStandar()->get();

        $pdf = Pdf::loadView('pdf.profil-sekolah-laporan', [
            'daftarSekolah' => $daftarSekolah,
        ])->setPaper($this->ukuranKertasDompdf(), 'landscape');

        $pathSementara = tempnam(sys_get_temp_dir(), 'profil-sekolah-pdf-').'.pdf';
        file_put_contents($pathSementara, $pdf->output());

        return response()->download($pathSementara, 'profil-sekolah-laporan.pdf')->deleteFileAfterSend(true);
    }

    public function import(): void
    {
        abort_unless($this->bolehKelolaSemua(), 403);

        $this->errorImport = null;

        $this->validate([
            'fileImport' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        try {
            $import = new ProfilSekolahImport;

            Excel::import($import, $this->fileImport->getRealPath());

            $this->fileImport = null;

            $pesan = "Import Profil Sekolah berhasil: {$import->jumlahDibuat} sekolah baru ditambahkan.";
            if ($import->jumlahDilewati > 0) {
                $pesan .= " {$import->jumlahDilewati} baris dilewati karena NPSN sudah terdaftar.";
            }
            session()->flash('status', $pesan);
        } catch (ValidationException $e) {
            $pesan = [];
            foreach ($e->failures() as $failure) {
                $pesan[] = 'Baris '.$failure->row().': '.implode(', ', $failure->errors());
            }
            $this->errorImport = implode(' | ', $pesan);
        }
    }

    // =====================================================================
    // TAB "Data PTK" (BARU, 2026-10-01)
    // =====================================================================

    public ?int $editingPtkId = null;

    /**
     * Dinaikkan setiap kali form Tambah/Edit PTK dibuka - pola sama
     * seperti $formInstance pada Lampiran2a (dipakai sbg wire:key kotak
     * tanggal supaya selalu ter-refresh).
     */
    public int $formInstancePtk = 0;

    public ?int $profil_sekolah_id = null;

    public string $nik = '';

    public string $nuptk = '';

    public string $nip = '';

    public string $nama_ptk = '';

    public string $tempat_lahir = '';

    public string $tanggal_lahir = '';

    public string $jabatan = '';

    public string $pangkat_golongan = '';

    public string $status_kepegawaian = '';

    public string $jenis_ptk = '';

    public string $tmt_sekolah_induk = '';

    public string $pendidikan_terakhir = '';

    public string $jurusan_prodi = '';

    public string $tahun_lulus_ijazah = '';

    public string $npwp = '';

    public string $status_sertifikasi = '';

    public string $bidang_studi_sertifikasi = '';

    public string $tahun_lulus_sertifikasi = '';

    public string $nomor_sertifikat_sertifikasi = '';

    public string $nomor_registrasi_guru = '';

    public string $nomor_peserta_sertifikasi = '';

    public string $status_dapodik = '';

    public string $status_keaktifan = '';

    /**
     * Keterangan (BARU 2026-10-20, permintaan user poin 5) - wajib
     * diisi manual saat Status Keaktifan = "Tidak Aktif", boleh kosong
     * saat "Aktif" (lihat validasi di simpanPtk()). Dipakai juga
     * sebagai sumber otomatis field Keterangan pada Lampiran 2b (poin
     * 6, lihat App\Livewire\PendataanOps\Lampiran2b\Index).
     */
    public string $keterangan = '';

    /**
     * Daftar PTK + field yang belum lengkap untuk 1 sekolah yang
     * sedang dilihat detailnya (BARU 2026-10-20, permintaan user poin
     * 1) - null berarti tidak sedang menampilkan modal detail. Diisi
     * lewat lihatDetailBelumLengkapPtk(), dipakai di render()/blade
     * (modal "data-ptk-belum-lengkap-detail").
     */
    public ?string $sekolahBelumLengkapDipilih = null;

    public bool $showFormPtk = false;

    public ?int $confirmingDeletePtkId = null;

    public string $searchPtk = '';

    public ?int $filterSekolahPtk = null;

    public string $filterStatusSertifikasiPtk = '';

    public string $filterStatusKeaktifanPtk = '';

    public string $filterStatusDapodikPtk = '';

    public int $perPagePtk = 10;

    public $fileImportPtk = null;

    public ?string $errorImportPtk = null;

    public ?string $errorExportPtk = null;

    /**
     * Hapus massal (checkbox pilih baris + tombol "Hapus Terpilih") -
     * pola sama seperti Lampiran2a: "pilih semua" hanya memilih baris
     * yang SEDANG TAMPIL di halaman aktif.
     */
    public array $dipilihPtk = [];

    public bool $confirmingHapusTerpilihPtk = false;

    protected function sekolahSayaId(): ?int
    {
        return auth()->user()->profil_sekolah_id;
    }

    /**
     * Siapa yang boleh menambah/mengubah/menghapus Data PTK - Superadmin
     * boleh untuk semua sekolah, Admin OPS HANYA untuk sekolahnya sendiri.
     * Admin BOSP TIDAK termasuk (hanya bisa melihat) - jawaban
     * AskUserQuestion "Admin OPS + Superadmin".
     */
    protected function bolehKelolaDataPtkSemua(): bool
    {
        return auth()->user()->isSuperadmin();
    }

    protected function bolehTambahDataPtk(): bool
    {
        $user = auth()->user();

        return $user->isSuperadmin() || $user->isAdminOps();
    }

    protected function bolehKelolaDataPtk(int $profilSekolahId): bool
    {
        if ($this->bolehKelolaDataPtkSemua()) {
            return true;
        }

        return auth()->user()->isAdminOps() && $this->sekolahSayaId() === $profilSekolahId;
    }

    public function updatedSearchPtk(): void
    {
        $this->resetPage('ptkPage');
    }

    public function updatedPerPagePtk(): void
    {
        $this->resetPage('ptkPage');
    }

    public function updatedFilterSekolahPtk(): void
    {
        $this->resetPage('ptkPage');
    }

    public function updatedFilterStatusSertifikasiPtk(): void
    {
        $this->resetPage('ptkPage');
    }

    public function updatedFilterStatusKeaktifanPtk(): void
    {
        $this->resetPage('ptkPage');
    }

    public function updatedFilterStatusDapodikPtk(): void
    {
        $this->resetPage('ptkPage');
    }

    /**
     * Umpan balik langsung di form (sebelum simpan) - saat Status
     * Sertifikasi diubah jadi "Belum", field rincian sertifikasi
     * langsung terlihat terisi tanda "-" DAN dinonaktifkan (lihat
     * @disabled($rincianSertifikasiTerkunci) di blade). Saat diubah
     * balik jadi "Sudah", field itu diaktifkan kembali & tanda "-"
     * yang tersisa dari sebelumnya dikosongkan lagi (permintaan user
     * 2026-10-01) supaya PTK bisa langsung mengetik data baru, bukan
     * malah harus menghapus "-" manual dulu - nilai yang SUDAH diisi
     * manual sebelumnya (bukan "-") TIDAK ikut dikosongkan. Aturan yang
     * SEBENARNYA mengikat tetap diterapkan ulang di simpanPtk() lewat
     * DataPtk::terapkanAturanSertifikasi() (sumber kebenaran tunggal).
     */
    public function updatedStatusSertifikasi(): void
    {
        if ($this->status_sertifikasi === DataPtk::STATUS_SERTIFIKASI_BELUM) {
            foreach (DataPtk::FIELD_RINCIAN_SERTIFIKASI as $field) {
                $this->{$field} = DataPtk::TANDA_KOSONG;
            }
        } elseif ($this->status_sertifikasi === DataPtk::STATUS_SERTIFIKASI_SUDAH) {
            foreach (DataPtk::FIELD_RINCIAN_SERTIFIKASI as $field) {
                if ($this->{$field} === DataPtk::TANDA_KOSONG) {
                    $this->{$field} = '';
                }
            }
        }
    }

    /**
     * Umpan balik langsung di form (sebelum simpan) - saat Jabatan
     * diubah jadi Guru Honorer/GTY/GTT/PTT, Pangkat/Golongan DAN NIP
     * langsung terlihat terisi tanda "-" (Pangkat/Golongan: permintaan
     * user 2026-10-15; NIP ditambahkan ke aturan yang sama 2026-10-16)
     * supaya PTK tidak perlu mengetik sendiri. Saat diubah ke Jabatan
     * "Guru Ahli Pertama/Muda/Madya/Utama" (fungsional ASN/PPPK), field
     * NIP sebaliknya langsung TERBUKA (field Pangkat/Golongan memang
     * tetap berupa pilihan dropdown, bukan dikunci - hanya NIP yang
     * dikunci/dibuka, lihat :disabled="$nipTerkunci" di blade) dan
     * harus diisi manual. Tanda "-" yang tersisa dari Jabatan
     * sebelumnya dikosongkan lagi saat pindah ke Jabatan yang TIDAK
     * memicu aturan ini - nilai yang SUDAH diisi manual sebelumnya
     * (bukan "-") TIDAK ikut dikosongkan. Aturan yang SEBENARNYA
     * mengikat tetap diterapkan ulang di simpanPtk() lewat
     * DataPtk::terapkanAturanPangkatGolongan() (sumber kebenaran
     * tunggal) - pola yang sama persis dengan updatedStatusSertifikasi()
     * di atas.
     */
    public function updatedJabatan(): void
    {
        if (in_array($this->jabatan, DataPtk::jabatanTanpaPangkatGolongan(), true)) {
            $this->pangkat_golongan = DataPtk::TANDA_KOSONG;
            $this->nip = DataPtk::TANDA_KOSONG;
        } else {
            if ($this->pangkat_golongan === DataPtk::TANDA_KOSONG) {
                $this->pangkat_golongan = '';
            }

            if ($this->nip === DataPtk::TANDA_KOSONG) {
                $this->nip = '';
            }
        }
    }

    public function tambahPtk(): void
    {
        abort_unless($this->bolehTambahDataPtk(), 403);

        $this->resetFormPtk();
        $this->formInstancePtk++;

        if (! $this->bolehKelolaDataPtkSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        $this->showFormPtk = true;
        $this->dispatch('open-modal', 'data-ptk-form');
    }

    public function editPtk(int $id): void
    {
        $baris = DataPtk::findOrFail($id);

        abort_unless($this->bolehKelolaDataPtk($baris->profil_sekolah_id), 403);

        $this->formInstancePtk++;
        $this->editingPtkId = $baris->id;
        $this->profil_sekolah_id = $baris->profil_sekolah_id;
        $this->nik = (string) $baris->nik;
        $this->nuptk = (string) $baris->nuptk;
        $this->nip = (string) $baris->nip;
        $this->nama_ptk = (string) $baris->nama_ptk;
        $this->tempat_lahir = (string) $baris->tempat_lahir;
        $this->tanggal_lahir = $baris->tanggal_lahir?->format('Y-m-d') ?? '';
        $this->jabatan = (string) $baris->jabatan;
        $this->pangkat_golongan = (string) $baris->pangkat_golongan;
        $this->status_kepegawaian = (string) $baris->status_kepegawaian;
        $this->jenis_ptk = (string) $baris->jenis_ptk;
        $this->tmt_sekolah_induk = $baris->tmt_sekolah_induk?->format('Y-m-d') ?? '';
        $this->pendidikan_terakhir = (string) $baris->pendidikan_terakhir;
        $this->jurusan_prodi = (string) $baris->jurusan_prodi;
        $this->tahun_lulus_ijazah = (string) $baris->tahun_lulus_ijazah;
        $this->npwp = (string) $baris->npwp;
        $this->status_sertifikasi = (string) $baris->status_sertifikasi;
        $this->bidang_studi_sertifikasi = (string) $baris->bidang_studi_sertifikasi;
        $this->tahun_lulus_sertifikasi = (string) $baris->tahun_lulus_sertifikasi;
        $this->nomor_sertifikat_sertifikasi = (string) $baris->nomor_sertifikat_sertifikasi;
        $this->nomor_registrasi_guru = (string) $baris->nomor_registrasi_guru;
        $this->nomor_peserta_sertifikasi = (string) $baris->nomor_peserta_sertifikasi;
        $this->status_dapodik = (string) $baris->status_dapodik;
        $this->status_keaktifan = (string) $baris->status_keaktifan;
        $this->keterangan = (string) $baris->keterangan;
        $this->showFormPtk = true;
        $this->dispatch('open-modal', 'data-ptk-form');
    }

    public function resetFormPtk(): void
    {
        $this->reset([
            'editingPtkId', 'profil_sekolah_id', 'nik', 'nuptk', 'nip', 'nama_ptk',
            'tempat_lahir', 'tanggal_lahir', 'jabatan', 'pangkat_golongan',
            'status_kepegawaian', 'jenis_ptk', 'tmt_sekolah_induk', 'pendidikan_terakhir',
            'jurusan_prodi', 'tahun_lulus_ijazah', 'npwp', 'status_sertifikasi',
            'bidang_studi_sertifikasi', 'tahun_lulus_sertifikasi', 'nomor_sertifikat_sertifikasi',
            'nomor_registrasi_guru', 'nomor_peserta_sertifikasi', 'status_dapodik', 'status_keaktifan',
            'keterangan',
        ]);
        $this->resetErrorBag();
    }

    public function batalPtk(): void
    {
        $this->showFormPtk = false;
        $this->resetFormPtk();
        $this->dispatch('close-modal', 'data-ptk-form');
    }

    public function simpanPtk(): void
    {
        if (! $this->bolehKelolaDataPtkSemua()) {
            $this->profil_sekolah_id = $this->sekolahSayaId();
        }

        abort_unless($this->bolehTambahDataPtk(), 403);
        abort_unless($this->profil_sekolah_id && $this->bolehKelolaDataPtk($this->profil_sekolah_id), 403);

        // DIPERBAIKI 2026-10-20 (permintaan user poin 4, dikonfirmasi lewat
        // AskUserQuestion): SEBELUMNYA kondisi ini "true KECUALI Belum"
        // (jadi ikut true saat status_sertifikasi masih kosong/belum
        // dipilih) & field rincian sertifikasi di bawah TETAP selalu
        // 'nullable' apapun hasilnya (jadi TIDAK PERNAH benar-benar
        // wajib walau namanya "Wajib") - sekarang kondisinya persis
        // "Sudah" SAJA, dan field rincian sertifikasi SUNGGUHAN memakai
        // 'required' saat kondisi ini true (lihat di bawah).
        $rincianSertifikasiWajib = $this->status_sertifikasi === DataPtk::STATUS_SERTIFIKASI_SUDAH;

        // BARU 2026-10-20 (permintaan user poin 5): Keterangan wajib
        // diisi manual hanya saat Status Keaktifan = "Tidak Aktif".
        $keteranganWajib = $this->status_keaktifan === 'Tidak Aktif';

        $validated = $this->validate([
            'profil_sekolah_id' => ['required', Rule::exists('profil_sekolah', 'id')],
            'nik' => [
                'required', 'regex:/^[0-9]{16}$/',
                Rule::unique('data_ptk', 'nik')->ignore($this->editingPtkId),
            ],
            'nuptk' => ['nullable', 'regex:/^([0-9]{16}|-)$/'],
            'nip' => ['nullable', 'regex:/^([0-9]{18}|-)$/'],
            'nama_ptk' => ['required', 'string', 'max:255'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jabatan' => ['required', Rule::in(array_keys(DataPtk::jabatanOptions()))],
            'pangkat_golongan' => ['nullable', Rule::in(array_keys(DataPtk::pangkatGolonganOptions()))],
            'status_kepegawaian' => ['required', Rule::in(array_keys(DataPtk::statusKepegawaianOptions()))],
            'jenis_ptk' => ['required', Rule::in(array_keys(DataPtk::jenisPtkOptions()))],
            'tmt_sekolah_induk' => ['nullable', 'date'],
            'pendidikan_terakhir' => ['required', Rule::in(array_keys(DataPtk::pendidikanTerakhirOptions()))],
            'jurusan_prodi' => ['nullable', 'string', 'max:255'],
            'tahun_lulus_ijazah' => ['nullable', 'regex:/^[0-9]{4}$/'],
            'npwp' => ['nullable', 'regex:/^([0-9]+|-)$/'],
            'status_sertifikasi' => ['required', Rule::in(array_keys(DataPtk::statusSertifikasiOptions()))],
            // SEJAK 2026-10-20 (permintaan user poin 4): WAJIB diisi
            // (bukan lagi sekadar 'nullable') saat Status Sertifikasi =
            // "Sudah". Tanda "-" pada tahun_lulus_sertifikasi/
            // nomor_registrasi_guru HANYA diperbolehkan saat TIDAK
            // wajib (Status Sertifikasi "Belum") - regex-nya langsung
            // diperketat jadi angka murni (tanpa "-") saat wajib,
            // dikonfirmasi lewat AskUserQuestion "Harus angka asli,
            // tanda - tidak dianggap terisi".
            'bidang_studi_sertifikasi' => $rincianSertifikasiWajib ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'],
            'tahun_lulus_sertifikasi' => $rincianSertifikasiWajib ? ['required', 'regex:/^[0-9]{4}$/'] : ['nullable', 'regex:/^([0-9]{4}|-)$/'],
            'nomor_sertifikat_sertifikasi' => $rincianSertifikasiWajib ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'],
            'nomor_registrasi_guru' => $rincianSertifikasiWajib ? ['required', 'regex:/^[0-9]{1,12}$/'] : ['nullable', 'regex:/^([0-9]{1,12}|-)$/'],
            'nomor_peserta_sertifikasi' => $rincianSertifikasiWajib ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'],
            'status_dapodik' => ['required', Rule::in(array_keys(DataPtk::statusDapodikOptions()))],
            'status_keaktifan' => ['required', Rule::in(array_keys(DataPtk::statusKeaktifanOptions()))],
            // BARU 2026-10-20 (permintaan user poin 5).
            'keterangan' => $keteranganWajib ? ['required', 'string', 'max:1000'] : ['nullable', 'string', 'max:1000'],
        ], [
            'nik.regex' => 'NIK harus berupa 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar pada data PTK lain.',
            'nuptk.regex' => 'NUPTK harus berupa 16 digit angka, atau tanda "-" apabila belum memiliki.',
            'nip.regex' => 'NIP harus berupa 18 digit angka.',
            'tahun_lulus_ijazah.regex' => 'Tahun Lulus Ijazah harus berupa 4 digit angka tahun.',
            'npwp.regex' => 'NPWP harus berupa angka, atau tanda "-" apabila belum memiliki.',
            'bidang_studi_sertifikasi.required' => 'Bidang Studi Sertifikasi wajib diisi karena Status Sertifikasi "Sudah".',
            'tahun_lulus_sertifikasi.required' => 'Tahun Lulus Sertifikasi wajib diisi karena Status Sertifikasi "Sudah".',
            'tahun_lulus_sertifikasi.regex' => $rincianSertifikasiWajib
                ? 'Tahun Lulus Sertifikasi harus berupa 4 digit angka tahun (tidak boleh tanda "-" karena Status Sertifikasi "Sudah").'
                : 'Tahun Lulus Sertifikasi harus berupa 4 digit angka tahun, atau tanda "-".',
            'nomor_sertifikat_sertifikasi.required' => 'Nomor Sertifikat Sertifikasi wajib diisi karena Status Sertifikasi "Sudah".',
            'nomor_registrasi_guru.required' => 'Nomor Registrasi Guru wajib diisi karena Status Sertifikasi "Sudah".',
            'nomor_registrasi_guru.regex' => $rincianSertifikasiWajib
                ? 'Nomor Registrasi Guru harus berupa angka, maksimal 12 digit (tidak boleh tanda "-" karena Status Sertifikasi "Sudah").'
                : 'Nomor Registrasi Guru harus berupa angka (maksimal 12 digit), atau tanda "-".',
            'nomor_peserta_sertifikasi.required' => 'Nomor Peserta Sertifikasi wajib diisi karena Status Sertifikasi "Sudah".',
            'keterangan.required' => 'Keterangan wajib diisi karena Status Keaktifan "Tidak Aktif".',
        ]);

        // Sumber kebenaran tunggal aturan "Belum -> otomatis '-'" supaya
        // tidak bisa "dibengkokkan" lewat devtools (validasi di atas hanya
        // longgar utk field ini, aturan SEBENARNYA diterapkan di sini).
        $validated = DataPtk::terapkanAturanSertifikasi($validated);
        $validated = DataPtk::terapkanAturanPangkatGolongan($validated);

        foreach (['nuptk', 'nip', 'tempat_lahir', 'jurusan_prodi', 'pangkat_golongan', 'tanggal_lahir', 'tmt_sekolah_induk', 'tahun_lulus_ijazah', 'npwp', 'nomor_sertifikat_sertifikasi', 'nomor_registrasi_guru', 'nomor_peserta_sertifikasi', 'keterangan'] as $fieldOpsional) {
            if (($validated[$fieldOpsional] ?? '') === '') {
                $validated[$fieldOpsional] = null;
            }
        }

        if ($this->editingPtkId) {
            $baris = DataPtk::findOrFail($this->editingPtkId);
            abort_unless($this->bolehKelolaDataPtk($baris->profil_sekolah_id), 403);
            $baris->update($validated);
        } else {
            $validated['created_by'] = auth()->id();
            DataPtk::create($validated);
        }

        $this->showFormPtk = false;
        $this->resetFormPtk();
        $this->dispatch('close-modal', 'data-ptk-form');
        session()->flash('status', 'Data PTK berhasil disimpan.');
    }

    public function konfirmasiHapusPtk(int $id): void
    {
        $baris = DataPtk::findOrFail($id);
        abort_unless($this->bolehKelolaDataPtk($baris->profil_sekolah_id), 403);

        $this->confirmingDeletePtkId = $id;
        $this->dispatch('open-modal', 'data-ptk-hapus');
    }

    public function batalHapusPtk(): void
    {
        $this->confirmingDeletePtkId = null;
        $this->dispatch('close-modal', 'data-ptk-hapus');
    }

    public function hapusPtk(): void
    {
        $baris = DataPtk::findOrFail($this->confirmingDeletePtkId);
        abort_unless($this->bolehKelolaDataPtk($baris->profil_sekolah_id), 403);

        $baris->delete();
        $this->confirmingDeletePtkId = null;
        $this->dispatch('close-modal', 'data-ptk-hapus');
        session()->flash('status', 'Data PTK berhasil dihapus.');
    }

    public function togglePtkSemua(): void
    {
        $idHalamanIni = $this->daftarPtkTerurut()->pluck('id')->all();
        if (count($idHalamanIni) > 0 && count(array_diff($idHalamanIni, $this->dipilihPtk)) === 0) {
            $this->dipilihPtk = array_values(array_diff($this->dipilihPtk, $idHalamanIni));
        } else {
            $this->dipilihPtk = array_values(array_unique(array_merge($this->dipilihPtk, $idHalamanIni)));
        }
    }

    public function konfirmasiHapusTerpilihPtk(): void
    {
        if (empty($this->dipilihPtk)) {
            return;
        }
        $this->confirmingHapusTerpilihPtk = true;
        $this->dispatch('open-modal', 'data-ptk-hapus-terpilih');
    }

    public function batalHapusTerpilihPtk(): void
    {
        $this->confirmingHapusTerpilihPtk = false;
        $this->dispatch('close-modal', 'data-ptk-hapus-terpilih');
    }

    public function hapusTerpilihPtk(): void
    {
        $barisTerpilih = DataPtk::whereIn('id', $this->dipilihPtk)->get();
        foreach ($barisTerpilih as $baris) {
            abort_unless($this->bolehKelolaDataPtk($baris->profil_sekolah_id), 403);
        }

        $jumlah = $barisTerpilih->count();
        DataPtk::whereIn('id', $barisTerpilih->pluck('id'))->delete();

        $this->dipilihPtk = [];
        $this->confirmingHapusTerpilihPtk = false;
        $this->dispatch('close-modal', 'data-ptk-hapus-terpilih');
        session()->flash('status', "Berhasil menghapus {$jumlah} data PTK sekaligus.");
    }

    protected function queryDasarPtk()
    {
        $query = DataPtk::query()->with(['profilSekolah', 'creator']);

        if (! $this->bolehKelolaDataPtkSemua()) {
            $query->where('profil_sekolah_id', $this->sekolahSayaId());
        } elseif ($this->filterSekolahPtk) {
            $query->where('profil_sekolah_id', $this->filterSekolahPtk);
        }

        if ($this->filterStatusSertifikasiPtk !== '') {
            $query->where('status_sertifikasi', $this->filterStatusSertifikasiPtk);
        }

        if ($this->filterStatusKeaktifanPtk !== '') {
            $query->where('status_keaktifan', $this->filterStatusKeaktifanPtk);
        }

        if ($this->filterStatusDapodikPtk !== '') {
            $query->where('status_dapodik', $this->filterStatusDapodikPtk);
        }

        if ($this->searchPtk !== '') {
            $query->where(function ($q) {
                $q->where('nama_ptk', 'like', "%{$this->searchPtk}%")
                    ->orWhere('nik', 'like', "%{$this->searchPtk}%")
                    ->orWhere('nuptk', 'like', "%{$this->searchPtk}%")
                    ->orWhere('nip', 'like', "%{$this->searchPtk}%")
                    ->orWhereHas('profilSekolah', function ($qs) {
                        $qs->where('nama_sekolah', 'like', "%{$this->searchPtk}%");
                    });
            });
        }

        return $query;
    }

    /**
     * BARU 2026-10-20 (permintaan user, MENGGANTI urutan "semua
     * sekolah" sebelumnya - lihat App\Support\UrutanDaftarPtkKecamatan
     * utk detail lengkap aturan & jawaban AskUserQuestion): urutan
     * SEKARANG SELALU Kecamatan (custom)->Nama Sekolah->Status (Negeri
     * dulu, lalu Swasta - susulan permintaan user)->Nama PTK->Pangkat/
     * Golongan->TMT Di Sekolah Induk->Tahun Lulus Sertifikasi -
     * berlaku baik saat menampilkan semua sekolah MAUPUN saat terfilter
     * ke 1 sekolah (sebelumnya ->latest()). Diimplementasikan sebagai
     * sort di level PHP (bukan SQL ORDER BY) - lihat
     * UrutanDaftarPtkKecamatan::urutkanDataPtk() (field Pangkat/
     * Golongan, TMT Di Sekolah Induk, & Tahun Lulus Sertifikasi semua
     * native di tabel Data PTK sendiri, TIDAK perlu JOIN NUPTK seperti
     * urutkanLampiran() yang dipakai menu Lampiran 2a/2b/2c) - makanya
     * paginasi JUGA dilakukan manual lewat LengthAwarePaginator, BUKAN
     * lewat Eloquent ->paginate() seperti sebelumnya.
     */
    protected function daftarPtkTerurut(): LengthAwarePaginator
    {
        $semuaBaris = UrutanDaftarPtkKecamatan::urutkanDataPtk($this->queryDasarPtk()->get());
        $halaman = $this->getPage('ptkPage');

        return new LengthAwarePaginator(
            $semuaBaris->forPage($halaman, $this->perPagePtk)->values(),
            $semuaBaris->count(),
            $this->perPagePtk,
            $halaman,
            ['pageName' => 'ptkPage', 'path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Ringkasan jumlah data PTK tersimpan (permintaan user 2026-10-16),
     * dirinci per status sekolah (Negeri/Swasta) - HANYA dihitung &
     * ditampilkan saat tampilan "semua sekolah" Superadmin aktif (sama
     * dgn kondisi di daftarPtkTerurut() di atas), krn kalau sedang
     * difilter ke 1 sekolah rinciannya tidak relevan (pasti 100% salah
     * satu status saja). Menghitung dari query dasar yang SAMA (semua
     * filter lain yang sedang aktif ikut terhitung), bukan cuma 1
     * halaman yang sedang ditampilkan.
     */
    protected function ringkasanPtk(): ?array
    {
        if (! $this->bolehKelolaDataPtkSemua() || $this->filterSekolahPtk) {
            return null;
        }

        $jumlahNegeri = $this->queryDasarPtk()
            ->whereHas('profilSekolah', fn ($q) => $q->where('status', ProfilSekolah::STATUS_NEGERI))
            ->count();
        $jumlahSwasta = $this->queryDasarPtk()
            ->whereHas('profilSekolah', fn ($q) => $q->where('status', ProfilSekolah::STATUS_SWASTA))
            ->count();

        // BARU 2026-10-18 (permintaan user): rincian jumlah berdasarkan
        // Status Sertifikasi, dihitung dari queryDasarPtk() yang sama
        // (ikut filter yang sedang aktif - pola sama persis dgn
        // negeri/swasta di atas).
        $jumlahSudahSertifikasi = $this->queryDasarPtk()
            ->where('status_sertifikasi', DataPtk::STATUS_SERTIFIKASI_SUDAH)
            ->count();
        $jumlahBelumSertifikasi = $this->queryDasarPtk()
            ->where('status_sertifikasi', DataPtk::STATUS_SERTIFIKASI_BELUM)
            ->count();

        return [
            'negeri' => $jumlahNegeri,
            'swasta' => $jumlahSwasta,
            'total' => $jumlahNegeri + $jumlahSwasta,
            'sudahSertifikasi' => $jumlahSudahSertifikasi,
            'belumSertifikasi' => $jumlahBelumSertifikasi,
            // BARU 2026-10-20 (permintaan user poin 3, "pada bagian Jumlah
            // data tersimpan tambahkan berapa sekolah yang sudah input &
            // belum input berdasarkan sekolah selain berdasarkan inputan
            // PTK nya") - beda dgn negeri/swasta/sertifikasi di atas yang
            // menghitung BARIS Data PTK, ini menghitung SEKOLAH (berapa
            // sekolah yang punya minimal 1 baris Data PTK vs 0 sama
            // sekali). SENGAJA dihitung dari SEMUA sekolah (bukan
            // queryDasarPtk(), tidak ikut filter pencarian/status yang
            // sedang aktif) - dikonfirmasi lewat AskUserQuestion, pola
            // sama dgn belumLengkap di bawah supaya jadi alat pantau
            // menyeluruh yg tidak berubah saat admin sedang memfilter.
            'sekolahSudahInputPtk' => $this->jumlahSekolahSudahInputPtk(),
            'sekolahBelumInputPtk' => ProfilSekolah::count() - $this->jumlahSekolahSudahInputPtk(),
            // BARU 2026-10-18 (permintaan user) - lihat daftarBelumLengkapPtk()
            // di bawah. SENGAJA TIDAK memakai queryDasarPtk() (mencakup
            // SELURUH data PTK, bukan hanya yang sedang terfilter) supaya
            // daftar ini tetap jadi alat pantau menyeluruh, tidak ikut
            // hilang/berubah saat admin sedang mencari/memfilter sesuatu
            // yang lain di tabel.
            'belumLengkap' => $this->daftarBelumLengkapPtk(),
        ];
    }

    /**
     * Jumlah sekolah yang punya minimal 1 baris Data PTK (dari SEMUA
     * sekolah, bukan terfilter) - dipakai ringkasanPtk() di atas.
     */
    protected function jumlahSekolahSudahInputPtk(): int
    {
        return DataPtk::query()->distinct()->count('profil_sekolah_id');
    }

    /**
     * Daftar sekolah yang punya Data PTK "belum lengkap" (permintaan
     * user 2026-10-18, dikonfirmasi lewat AskUserQuestion) - sebuah
     * baris Data PTK dianggap belum lengkap kalau salah satu field di
     * DataPtk::FIELD_BELUM_LENGKAP masih NULL/kosong (bukan '-' - nilai
     * '-' berarti SUDAH lengkap tapi memang sengaja ditandai tidak
     * berlaku lewat aturan bisnis lain, lihat docblock konstanta itu).
     * Hanya sekolah dengan jumlah > 0 yang dikembalikan (jawaban
     * AskUserQuestion "Daftar ringkas, hanya sekolah yang punya data
     * belum lengkap"), diurutkan dari yang paling banyak.
     *
     * @return \Illuminate\Support\Collection<int, object{nama_sekolah: string, jumlah: int}>
     */
    protected function daftarBelumLengkapPtk(): \Illuminate\Support\Collection
    {
        // PERBAIKAN BUG 2026-10-18 (error 500 di ONLINE, log Postgres:
        // "invalid input syntax for type date"): 2 dari 7 field di
        // DataPtk::FIELD_BELUM_LENGKAP (tanggal_lahir, tmt_sekolah_induk)
        // bertipe DATE di database (lihat $casts di DataPtk) - TIDAK
        // PERNAH bisa bernilai string kosong '', hanya NULL kalau kosong.
        // Membandingkan kolom DATE dengan '' ditolak PostgreSQL. Field
        // tanggal dideteksi OTOMATIS dari $casts model (bukan daftar
        // hardcode terpisah) supaya kalau FIELD_BELUM_LENGKAP nanti
        // nambah field tanggal baru, otomatis ikut aman, tidak perlu
        // diingat-ingat lagi.
        $fieldTanggal = array_keys(array_filter((new DataPtk())->getCasts(), fn ($tipe) => $tipe === 'date'));

        return DataPtk::query()
            ->join('profil_sekolah', 'profil_sekolah.id', '=', 'data_ptk.profil_sekolah_id')
            ->where(function ($query) use ($fieldTanggal) {
                foreach (DataPtk::FIELD_BELUM_LENGKAP as $i => $field) {
                    $kolom = 'data_ptk.'.$field;
                    $kondisi = in_array($field, $fieldTanggal, true)
                        ? fn ($q) => $q->whereNull($kolom)
                        : fn ($q) => $q->whereNull($kolom)->orWhere($kolom, '');
                    $i === 0 ? $query->where($kondisi) : $query->orWhere($kondisi);
                }
            })
            ->selectRaw('profil_sekolah.nama_sekolah as nama_sekolah, count(*) as jumlah')
            ->groupBy('profil_sekolah.nama_sekolah')
            ->orderByDesc('jumlah')
            ->orderBy('profil_sekolah.nama_sekolah')
            ->get();
    }

    /**
     * Buka modal detail "Data PTK belum lengkap" untuk 1 sekolah
     * (permintaan user 2026-10-20 poin 1: "ketika di klik Nama
     * Sekolah nya muncul daftar PTK yang belum lengkap nya dan apa
     * yang belum lengkap nya") - dipanggil dari badge nama sekolah
     * pada komponen <x-ringkasan-negeri-swasta>, lihat
     * resources/views/components/ringkasan-negeri-swasta.blade.php.
     */
    public function lihatDetailBelumLengkapPtk(string $namaSekolah): void
    {
        $this->sekolahBelumLengkapDipilih = $namaSekolah;
        $this->dispatch('open-modal', 'data-ptk-belum-lengkap-detail');
    }

    public function tutupDetailBelumLengkapPtk(): void
    {
        $this->sekolahBelumLengkapDipilih = null;
        $this->dispatch('close-modal', 'data-ptk-belum-lengkap-detail');
    }

    /**
     * Daftar baris Data PTK "belum lengkap" untuk 1 sekolah tertentu,
     * BESERTA nama field yang belum lengkap per baris (permintaan user
     * 2026-10-20 poin 1) - dipakai untuk isi modal detail yang dibuka
     * lewat lihatDetailBelumLengkapPtk(). Logika "belum lengkap"-nya
     * SAMA PERSIS dengan daftarBelumLengkapPtk() di atas (field DATE
     * dicek NULL, field lain dicek NULL/'' - lihat catatan perbaikan
     * bug 2026-10-18 di method itu), supaya jumlah di badge ringkasan &
     * jumlah baris pada modal detail ini selalu konsisten.
     *
     * @return \Illuminate\Support\Collection<int, object{nama_ptk: string, fieldBelumLengkap: array<int, string>}>
     */
    public function detailBelumLengkapPtk(string $namaSekolah): \Illuminate\Support\Collection
    {
        $fieldTanggal = array_keys(array_filter((new DataPtk())->getCasts(), fn ($tipe) => $tipe === 'date'));
        $labelField = DataPtk::labelFieldBelumLengkap();

        return DataPtk::query()
            ->whereHas('profilSekolah', fn ($q) => $q->where('nama_sekolah', $namaSekolah))
            ->orderBy('nama_ptk')
            ->get()
            ->map(function (DataPtk $ptk) use ($fieldTanggal, $labelField) {
                $fieldBelumLengkap = [];

                foreach (DataPtk::FIELD_BELUM_LENGKAP as $field) {
                    $nilai = $ptk->{$field};
                    $kosong = in_array($field, $fieldTanggal, true) ? $nilai === null : ($nilai === null || $nilai === '');

                    if ($kosong) {
                        $fieldBelumLengkap[] = $labelField[$field] ?? $field;
                    }
                }

                return (object) [
                    'nama_ptk' => $ptk->nama_ptk,
                    'fieldBelumLengkap' => $fieldBelumLengkap,
                ];
            })
            ->filter(fn ($baris) => count($baris->fieldBelumLengkap) > 0)
            ->values();
    }

    /**
     * "Export Excel" - file polos sesuai urutan field aplikasi, untuk
     * diedit lalu diimport kembali (jawaban AskUserQuestion "Dua fungsi
     * berbeda" - berbeda dari unduhPtk() di bawah).
     */
    public function exportPtk()
    {
        AksesDataLog::catat(AksesDataLog::JENIS_UNDUH, 'Data PTK', 'Excel (Export)');
        $this->errorExportPtk = null;

        return Excel::download(
            new DataPtkExport($this->queryDasarPtk()->orderBy('nama_ptk')->get()),
            'data-ptk-export.xlsx'
        );
    }

    // "Unduh" (laporan rapi dengan garis tabel) DIPINDAHKAN ke menu
    // Unduhan (App\Livewire\PendataanOps\Unduhan\Index::unduhDataPtk())
    // - permintaan user 2026-10-20 poin 8: "pada menu PTK tombol unduh
    // pindahkan ke menu Unduhan pada menu pendataan ops". Tombol
    // "Export Excel" (exportPtk() di atas, format POLOS untuk
    // diedit-lalu-diimport-kembali) TIDAK ikut dipindah - tetap di sini
    // (fungsi beda, jawaban AskUserQuestion lama "Dua fungsi berbeda").

    public function importPtk(): void
    {
        abort_unless($this->bolehTambahDataPtk(), 403);

        $this->errorImportPtk = null;

        $this->validate([
            'fileImportPtk' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        try {
            $sekolahDiperbolehkan = $this->bolehKelolaDataPtkSemua()
                ? null
                : $this->sekolahSayaId();

            $import = new DataPtkImport(auth()->id(), $sekolahDiperbolehkan);

            Excel::import($import, $this->fileImportPtk->getRealPath());

            $this->fileImportPtk = null;

            $pesan = "Import Data PTK berhasil: {$import->jumlahDibuat} baru ditambahkan, {$import->jumlahDiperbarui} data lama diperbarui.";
            if ($import->jumlahDilewati > 0) {
                $pesan .= " {$import->jumlahDilewati} baris dilewati (sekolah tidak ditemukan/tidak diizinkan).";
            }
            session()->flash('status', $pesan);
        } catch (ValidationException $e) {
            $pesan = [];
            foreach ($e->failures() as $failure) {
                $pesan[] = 'Baris '.$failure->row().': '.implode(', ', $failure->errors());
            }
            $this->errorImportPtk = implode(' | ', $pesan);
        }
    }

    public function render()
    {
        $kelolaSemua = $this->bolehKelolaSemua();

        $daftarSekolah = $this->queryDasarSekolah()
            ->urutStandar()
            ->get();

        $daftarPtk = $this->daftarPtkTerurut();

        $idHalamanIniPtk = $daftarPtk->pluck('id')->all();
        $this->dipilihPtk = array_values(array_intersect($this->dipilihPtk, $idHalamanIniPtk));
        $semuaTerpilihPtk = count($idHalamanIniPtk) > 0 && count(array_diff($idHalamanIniPtk, $this->dipilihPtk)) === 0;

        // Logo Sekolah yang sedang diedit (permintaan user 2026-10-15) -
        // dicari langsung dari $editingId (bukan dari $daftarSekolah,
        // krn saat Superadmin memfilter daftar, sekolah yang sedang
        // diedit belum tentu ada di koleksi yang sama) supaya kotak
        // upload/ganti/hapus di modal form bisa menampilkan logo yang
        // sudah ada.
        $sekolahSedangDiedit = $this->editingId ? ProfilSekolah::find($this->editingId) : null;

        return view('livewire.profil-sekolah.index', [
            'daftarSekolah' => $daftarSekolah,
            // 2026-10-18: ditambah query string "?v=..." (cache-buster) -
            // lihat docblock App\Http\Controllers\LogoSekolahFileController -
            // controller itu sengaja menyetel header Cache-Control
            // "max-age=3600" (1 jam) utk performa, TAPI URL route ini
            // HANYA berisi ID sekolah (bukan nama file logo-nya), jadi
            // URL-nya TIDAK PERNAH berubah walau logo diganti/dihapus -
            // browser akan terus menampilkan gambar lama dari cache-nya
            // sampai 1 jam berlalu (laporan user "diganti logo baru tidak
            // berhasil, masih logo lama yang muncul"). Nama file logo
            // SUDAH unik acak tiap kali diunggah (lihat
            // simpanLogoSekolah()), jadi dipakai langsung sbg penanda
            // versi - browser otomatis minta ulang gambarnya begitu
            // logo berganti, TETAP memanfaatkan cache 1 jam selama
            // logonya belum berubah.
            'logoSekolahSrc' => ($sekolahSedangDiedit && $sekolahSedangDiedit->logo_sekolah)
                ? route('profil-sekolah.logo-sekolah', $sekolahSedangDiedit->id).'?v='.urlencode(basename($sekolahSedangDiedit->logo_sekolah))
                : null,
            'statusOptions' => ProfilSekolah::statusOptions(),
            'statusKepegawaianOptions' => ProfilSekolah::statusKepegawaianOptions(),
            'statusKepegawaianKepsekOptions' => ProfilSekolah::statusKepegawaianKepsekOptions(),
            'sekolahSayaId' => auth()->user()->profil_sekolah_id,
            'bolehKelolaSemua' => $kelolaSemua,
            'kertasOptionsLaporan' => $this->daftarKertasOptionsLaporan(),
            'filterNamaSekolahOptions' => $kelolaSemua
                ? ProfilSekolah::urutStandar()->pluck('nama_sekolah', 'id')
                : collect(),
            'filterKecamatanOptions' => $kelolaSemua
                ? ProfilSekolah::whereNotNull('kecamatan')->where('kecamatan', '!=', '')->distinct()->orderBy('kecamatan')->pluck('kecamatan', 'kecamatan')
                : collect(),

            // Tab Data PTK
            'daftarPtk' => $daftarPtk,
            'ringkasanPtk' => $this->ringkasanPtk(),
            // BARU 2026-10-20 (permintaan user poin 1) - detail PTK
            // belum lengkap untuk sekolah yang sedang dipilih (modal
            // "data-ptk-belum-lengkap-detail"), lihat
            // lihatDetailBelumLengkapPtk().
            'sekolahBelumLengkapDipilih' => $this->sekolahBelumLengkapDipilih,
            'detailBelumLengkapPtk' => $this->sekolahBelumLengkapDipilih
                ? $this->detailBelumLengkapPtk($this->sekolahBelumLengkapDipilih)
                : collect(),
            'semuaTerpilihPtk' => $semuaTerpilihPtk,
            'bolehKelolaDataPtkSemua' => $this->bolehKelolaDataPtkSemua(),
            'bolehTambahDataPtk' => $this->bolehTambahDataPtk(),
            'sekolahOptionsPtk' => ProfilSekolah::urutStandar()->get(['id', 'nama_sekolah']),
            'jabatanOptions' => DataPtk::jabatanOptions(),
            'pangkatGolonganOptions' => DataPtk::pangkatGolonganOptions(),
            'statusKepegawaianPtkOptions' => DataPtk::statusKepegawaianOptions(),
            'jenisPtkOptions' => DataPtk::jenisPtkOptions(),
            'pendidikanTerakhirOptions' => DataPtk::pendidikanTerakhirOptions(),
            'statusSertifikasiOptions' => DataPtk::statusSertifikasiOptions(),
            'statusDapodikOptions' => DataPtk::statusDapodikOptions(),
            'statusKeaktifanOptions' => DataPtk::statusKeaktifanOptions(),
        ]);
    }
}
