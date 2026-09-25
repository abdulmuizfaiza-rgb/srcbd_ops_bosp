<?php

namespace App\Livewire\ProfilSekolah;

use App\Exports\ProfilSekolahExport;
use App\Imports\ProfilSekolahImport;
use App\Livewire\Concerns\HasZoomTampilan;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

/**
 * Tabel Profil Sekolah (semua sekolah).
 *
 * - Superadmin: bisa menambah sekolah baru, mengedit seluruh data sekolah
 *   manapun (termasuk NPSN, Kode UPB, nama sekolah, status, kecamatan,
 *   subrayon), menghapus sekolah (selama sekolah itu belum punya akun
 *   Admin OPS/Admin BOSP), dan export/import data induk sekolah (NPSN,
 *   Nama Sekolah, Status, Kecamatan) lewat Excel untuk pendataan awal -
 *   sisanya (kepala sekolah, pengawas, bendahara, alamat) dilengkapi
 *   sendiri oleh Admin OPS/Admin BOSP masing-masing sekolah.
 * - Admin OPS / Admin BOSP: hanya bisa melihat tabel seluruh sekolah, dan
 *   hanya bisa mengedit data operasional (kepala sekolah, pengawas,
 *   bendahara, alamat) pada sekolahnya sendiri. NPSN/Kode UPB/nama
 *   sekolah/status/kecamatan/subrayon adalah data induk yang hanya
 *   diubah oleh Superadmin.
 *
 * Kode UPB & Subrayon (permintaan user 2026-09-17, Part 32) ditambahkan
 * sebagai data induk baru - dibutuhkan sebagai 2 dari 29 kolom menu
 * "Laporan Realisasi BOSP (Form BPK)" - lihat
 * App\Livewire\PendataanBosp\LaporanRealisasiBosp\Index.
 */
#[Layout('layouts.app')]
#[Title('Profil Sekolah')]
class Index extends Component
{
    use HasZoomTampilan, WithFileUploads;

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

        $this->errorExport = null;

        $daftarSekolah = ProfilSekolah::orderBy('nama_sekolah')->get();

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
            ->orderByRaw("CASE WHEN status = 'negeri' THEN 0 WHEN status = 'swasta' THEN 1 ELSE 2 END")
            ->orderByRaw('kecamatan IS NULL')
            ->orderBy('kecamatan')
            ->orderBy('nama_sekolah')
            ->get();

        return view('livewire.profil-sekolah.index', [
            'daftarSekolah' => $daftarSekolah,
            'statusOptions' => ProfilSekolah::statusOptions(),
            'statusKepegawaianOptions' => ProfilSekolah::statusKepegawaianOptions(),
            'sekolahSayaId' => auth()->user()->profil_sekolah_id,
            'bolehKelolaSemua' => $kelolaSemua,
            'filterNamaSekolahOptions' => $kelolaSemua
                ? ProfilSekolah::orderBy('nama_sekolah')->pluck('nama_sekolah', 'id')
                : collect(),
            'filterKecamatanOptions' => $kelolaSemua
                ? ProfilSekolah::whereNotNull('kecamatan')->where('kecamatan', '!=', '')->distinct()->orderBy('kecamatan')->pluck('kecamatan', 'kecamatan')
                : collect(),
        ]);
    }
}
