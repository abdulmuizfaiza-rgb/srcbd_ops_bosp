<?php

namespace App\Livewire\ProfilSekolah;

use App\Models\AksesDataLog;
use App\Exports\DataPtkExport;
use App\Exports\DataPtkLaporanExport;
use App\Exports\ProfilSekolahExport;
use App\Imports\DataPtkImport;
use App\Imports\ProfilSekolahImport;
use App\Livewire\Concerns\HasZoomTampilan;
use App\Models\DataPtk;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Validation\Rule;
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

    public string $nama_bendahara = '';

    public string $nip_bendahara = '';

    public string $status_kepegawaian_bendahara = '';

    public string $alamat_sekolah = '';

    public bool $showForm = false;

    public ?int $confirmingDeleteId = null;

    public ?string $errorHapus = null;

    public $fileImport = null;

    public ?string $errorImport = null;

    public ?string $errorExport = null;

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
        $this->nama_bendahara = (string) $sekolah->nama_bendahara;
        $this->nip_bendahara = (string) $sekolah->nip_bendahara;
        $this->status_kepegawaian_bendahara = (string) $sekolah->status_kepegawaian_bendahara;
        $this->alamat_sekolah = (string) $sekolah->alamat_sekolah;
        $this->showForm = true;
        $this->dispatch('open-modal', 'sekolah-form');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'npsn', 'kode_upb', 'nama_sekolah', 'status', 'kecamatan', 'subrayon',
            'nama_kepala_sekolah', 'nip_kepala_sekolah', 'no_whatsapp_kepala_sekolah', 'status_kepegawaian_kepsek',
            'nama_pengawas', 'nip_pengawas',
            'nama_bendahara', 'nip_bendahara', 'status_kepegawaian_bendahara',
            'alamat_sekolah',
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
            'status_kepegawaian_kepsek' => ['nullable', Rule::in(array_keys(ProfilSekolah::statusKepegawaianOptions()))],
            'nama_pengawas' => ['nullable', 'string', 'max:255'],
            'nip_pengawas' => ['nullable', 'string', 'max:50'],
            'nama_bendahara' => ['nullable', 'string', 'max:255'],
            'nip_bendahara' => $bendaharaAsn
                ? ['nullable', 'regex:/^[0-9]{18}$/']
                : ['nullable', 'in:-'],
            'status_kepegawaian_bendahara' => ['nullable', Rule::in(array_keys(ProfilSekolah::statusKepegawaianOptions()))],
            'alamat_sekolah' => ['nullable', 'string', 'max:1000'],
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
            'nama_bendahara' => $validated['nama_bendahara'] ?: null,
            'nip_bendahara' => $validated['nip_bendahara'] ?: null,
            'status_kepegawaian_bendahara' => $validated['status_kepegawaian_bendahara'] ?: null,
            'alamat_sekolah' => $validated['alamat_sekolah'] ?: null,
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

    public string $status_sertifikasi = '';

    public string $bidang_studi_sertifikasi = '';

    public string $tahun_lulus_sertifikasi = '';

    public string $nomor_sertifikat_sertifikasi = '';

    public string $nomor_registrasi_guru = '';

    public string $nomor_peserta_sertifikasi = '';

    public string $status_dapodik = '';

    public string $status_keaktifan = '';

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
     * langsung terlihat terisi tanda "-" di layar. Aturan yang
     * SEBENARNYA mengikat tetap diterapkan ulang di simpanPtk() lewat
     * DataPtk::terapkanAturanSertifikasi() (sumber kebenaran tunggal).
     */
    public function updatedStatusSertifikasi(): void
    {
        if ($this->status_sertifikasi === DataPtk::STATUS_SERTIFIKASI_BELUM) {
            foreach (DataPtk::FIELD_RINCIAN_SERTIFIKASI as $field) {
                $this->{$field} = DataPtk::TANDA_KOSONG;
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
        $this->status_sertifikasi = (string) $baris->status_sertifikasi;
        $this->bidang_studi_sertifikasi = (string) $baris->bidang_studi_sertifikasi;
        $this->tahun_lulus_sertifikasi = (string) $baris->tahun_lulus_sertifikasi;
        $this->nomor_sertifikat_sertifikasi = (string) $baris->nomor_sertifikat_sertifikasi;
        $this->nomor_registrasi_guru = (string) $baris->nomor_registrasi_guru;
        $this->nomor_peserta_sertifikasi = (string) $baris->nomor_peserta_sertifikasi;
        $this->status_dapodik = (string) $baris->status_dapodik;
        $this->status_keaktifan = (string) $baris->status_keaktifan;
        $this->showFormPtk = true;
        $this->dispatch('open-modal', 'data-ptk-form');
    }

    public function resetFormPtk(): void
    {
        $this->reset([
            'editingPtkId', 'profil_sekolah_id', 'nik', 'nuptk', 'nip', 'nama_ptk',
            'tempat_lahir', 'tanggal_lahir', 'jabatan', 'pangkat_golongan',
            'status_kepegawaian', 'jenis_ptk', 'tmt_sekolah_induk', 'pendidikan_terakhir',
            'jurusan_prodi', 'tahun_lulus_ijazah', 'status_sertifikasi',
            'bidang_studi_sertifikasi', 'tahun_lulus_sertifikasi', 'nomor_sertifikat_sertifikasi',
            'nomor_registrasi_guru', 'nomor_peserta_sertifikasi', 'status_dapodik', 'status_keaktifan',
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

        $rincianSertifikasiWajib = $this->status_sertifikasi !== DataPtk::STATUS_SERTIFIKASI_BELUM;

        $validated = $this->validate([
            'profil_sekolah_id' => ['required', Rule::exists('profil_sekolah', 'id')],
            'nik' => [
                'required', 'regex:/^[0-9]{16}$/',
                Rule::unique('data_ptk', 'nik')->ignore($this->editingPtkId),
            ],
            'nuptk' => ['nullable', 'regex:/^[0-9]{16}$/'],
            'nip' => ['nullable', 'regex:/^[0-9]{18}$/'],
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
            'status_sertifikasi' => ['required', Rule::in(array_keys(DataPtk::statusSertifikasiOptions()))],
            'bidang_studi_sertifikasi' => $rincianSertifikasiWajib ? ['nullable', 'string', 'max:255'] : ['nullable'],
            'tahun_lulus_sertifikasi' => $rincianSertifikasiWajib ? ['nullable', 'regex:/^([0-9]{4}|-)$/'] : ['nullable'],
            'nomor_sertifikat_sertifikasi' => ['nullable', 'string', 'max:255'],
            'nomor_registrasi_guru' => ['nullable', 'string', 'max:255'],
            'nomor_peserta_sertifikasi' => ['nullable', 'string', 'max:255'],
            'status_dapodik' => ['required', Rule::in(array_keys(DataPtk::statusDapodikOptions()))],
            'status_keaktifan' => ['required', Rule::in(array_keys(DataPtk::statusKeaktifanOptions()))],
        ], [
            'nik.regex' => 'NIK harus berupa 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar pada data PTK lain.',
            'nuptk.regex' => 'NUPTK harus berupa 16 digit angka.',
            'nip.regex' => 'NIP harus berupa 18 digit angka.',
            'tahun_lulus_ijazah.regex' => 'Tahun Lulus Ijazah harus berupa 4 digit angka tahun.',
            'tahun_lulus_sertifikasi.regex' => 'Tahun Lulus Sertifikasi harus berupa 4 digit angka tahun.',
        ]);

        // Sumber kebenaran tunggal aturan "Belum -> otomatis '-'" supaya
        // tidak bisa "dibengkokkan" lewat devtools (validasi di atas hanya
        // longgar utk field ini, aturan SEBENARNYA diterapkan di sini).
        $validated = DataPtk::terapkanAturanSertifikasi($validated);

        foreach (['nuptk', 'nip', 'tempat_lahir', 'jurusan_prodi', 'pangkat_golongan', 'tanggal_lahir', 'tmt_sekolah_induk', 'tahun_lulus_ijazah', 'nomor_sertifikat_sertifikasi', 'nomor_registrasi_guru', 'nomor_peserta_sertifikasi'] as $fieldOpsional) {
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
        $idHalamanIni = $this->queryDasarPtk()->latest()->paginate($this->perPagePtk, ['*'], 'ptkPage')->pluck('id')->all();
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
        $query = DataPtk::query()->with('profilSekolah');

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

    /**
     * "Unduh" - laporan rapi berjudul "DATA PTK (nama sekolah)" dengan
     * garis tabel, untuk 1 sekolah (jawaban AskUserQuestion "Dua fungsi
     * berbeda" - Superadmin wajib memilih 1 sekolah dulu di filter,
     * Admin OPS otomatis terkunci ke sekolahnya sendiri - pola sama
     * seperti export() pada Lampiran2a).
     */
    public function unduhPtk()
    {
        $this->errorExportPtk = null;

        $sekolahId = $this->bolehKelolaDataPtkSemua() ? $this->filterSekolahPtk : $this->sekolahSayaId();

        if (! $sekolahId) {
            $this->errorExportPtk = 'Pilih salah satu sekolah pada filter di atas terlebih dahulu sebelum Unduh, karena judul laporan "DATA PTK" hanya berlaku untuk 1 sekolah.';

            return null;
        }

        $sekolah = ProfilSekolah::findOrFail($sekolahId);

        AksesDataLog::catat(AksesDataLog::JENIS_UNDUH, 'Data PTK', 'Excel (Laporan)');

        return Excel::download(
            new DataPtkLaporanExport(
                DataPtk::where('profil_sekolah_id', $sekolahId)->orderBy('nama_ptk')->get(),
                $sekolah
            ),
            'data-ptk-'.\Illuminate\Support\Str::slug($sekolah->nama_sekolah).'.xlsx'
        );
    }

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

        $query = ProfilSekolah::query();

        if (! $kelolaSemua) {
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

        $daftarSekolah = $query
            ->urutStandar()
            ->get();

        $daftarPtk = $this->queryDasarPtk()->latest()->paginate($this->perPagePtk, ['*'], 'ptkPage');

        $idHalamanIniPtk = $daftarPtk->pluck('id')->all();
        $this->dipilihPtk = array_values(array_intersect($this->dipilihPtk, $idHalamanIniPtk));
        $semuaTerpilihPtk = count($idHalamanIniPtk) > 0 && count(array_diff($idHalamanIniPtk, $this->dipilihPtk)) === 0;

        return view('livewire.profil-sekolah.index', [
            'daftarSekolah' => $daftarSekolah,
            'statusOptions' => ProfilSekolah::statusOptions(),
            'statusKepegawaianOptions' => ProfilSekolah::statusKepegawaianOptions(),
            'sekolahSayaId' => auth()->user()->profil_sekolah_id,
            'bolehKelolaSemua' => $kelolaSemua,
            'filterNamaSekolahOptions' => $kelolaSemua
                ? ProfilSekolah::urutStandar()->pluck('nama_sekolah', 'id')
                : collect(),
            'filterKecamatanOptions' => $kelolaSemua
                ? ProfilSekolah::whereNotNull('kecamatan')->where('kecamatan', '!=', '')->distinct()->orderBy('kecamatan')->pluck('kecamatan', 'kecamatan')
                : collect(),

            // Tab Data PTK
            'daftarPtk' => $daftarPtk,
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
