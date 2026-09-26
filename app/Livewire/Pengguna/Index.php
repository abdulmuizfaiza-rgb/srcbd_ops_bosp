<?php

namespace App\Livewire\Pengguna;

use App\Livewire\Concerns\HasZoomTampilan;
use App\Mail\PasswordDiresetSuperadmin;
use App\Models\LoginHistory;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Pengguna')]
class Index extends Component
{
    use WithPagination;
    use HasZoomTampilan;

    #[Url(as: 'tab')]
    public string $tab = User::LEVEL_SUPERADMIN;

    public string $search = '';

    public ?int $filterSekolahId = null;

    /**
     * State untuk tab "Riwayat Login" (permintaan user 2026-09-26) - sub-tab
     * (Superadmin/Admin OPS/Admin BOSP) dan pencarian berdasarkan nama
     * sekolah/alamat email, TERPISAH dari $tab & $search milik tab
     * kelola-akun di atas supaya kedua tab tidak saling mempengaruhi.
     */
    public string $subTabRiwayat = User::LEVEL_SUPERADMIN;

    public string $searchRiwayat = '';

    // State form modal
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $level_akses = '';

    public ?int $profil_sekolah_id = null;

    public string $jabatan = '';

    // State konfirmasi hapus
    public ?int $confirmingDeleteId = null;

    // State konfirmasi reset password (khusus Admin OPS/Admin BOSP - lihat
    // resetPassword() di bawah)
    public ?int $confirmingResetId = null;

    protected function jabatanOtomatis(string $level): string
    {
        return match ($level) {
            User::LEVEL_ADMIN_OPS => 'Operator Sekolah',
            User::LEVEL_ADMIN_BOSP => 'Admin BOSP',
            default => '',
        };
    }

    public function pindahTab(string $tab): void
    {
        $this->tab = $tab;
        $this->filterSekolahId = null;
        $this->resetPage();
    }

    public function pindahTabRiwayat(string $subTab): void
    {
        $this->subTabRiwayat = $subTab;
        $this->searchRiwayat = '';
        $this->resetPage('riwayatPage');
    }

    public function updatedSearchRiwayat(): void
    {
        $this->resetPage('riwayatPage');
    }

    public function updatedFilterSekolahId(): void
    {
        $this->resetPage();
    }

    public function updatedLevelAkses(string $value): void
    {
        $this->jabatan = $this->jabatanOtomatis($value);

        if (! in_array($value, [User::LEVEL_ADMIN_OPS, User::LEVEL_ADMIN_BOSP], true)) {
            $this->profil_sekolah_id = null;
        }
    }

    public function tambah(): void
    {
        $this->resetForm();
        $this->level_akses = $this->tab;
        $this->jabatan = $this->jabatanOtomatis($this->tab);
        $this->showForm = true;
        $this->dispatch('open-modal', 'pengguna-form');
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);

        $this->editingId = $user->id;
        $this->username = $user->username;
        $this->email = (string) $user->email;
        $this->password = '';
        $this->level_akses = $user->level_akses;
        $this->profil_sekolah_id = $user->profil_sekolah_id;
        $this->jabatan = (string) $user->jabatan;
        $this->showForm = true;
        $this->dispatch('open-modal', 'pengguna-form');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'username', 'email', 'password', 'level_akses', 'profil_sekolah_id', 'jabatan']);
        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'pengguna-form');
    }

    public function simpan(): void
    {
        $butuhSekolah = in_array($this->level_akses, [User::LEVEL_ADMIN_OPS, User::LEVEL_ADMIN_BOSP], true);

        $rules = [
            'level_akses' => ['required', Rule::in([
                User::LEVEL_SUPERADMIN,
                User::LEVEL_ADMIN_OPS,
                User::LEVEL_ADMIN_BOSP,
            ])],
            'username' => array_filter([
                'required', 'string', 'max:255',
                $butuhSekolah ? 'email' : null,
                Rule::unique('users', 'username')->ignore($this->editingId),
            ]),
            'profil_sekolah_id' => [
                Rule::requiredIf($butuhSekolah),
                'nullable',
                Rule::exists('profil_sekolah', 'id'),
            ],
            // Email KHUSUS dikumpulkan lewat field terpisah utk Superadmin
            // (username Superadmin adalah string 'superadmin', bukan email -
            // lihat migration add_email_to_users_table). Utk Admin OPS/Admin
            // BOSP, email diturunkan otomatis dari username di bawah (lihat
            // $data['email']), karena username mereka SUDAH divalidasi wajib
            // berupa email di atas - jadi tidak perlu field terpisah lagi di
            // sini, konsisten dgn jalur registrasi mandiri (register.blade.php).
            'email' => [
                Rule::requiredIf($this->level_akses === User::LEVEL_SUPERADMIN),
                'nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
        ];

        $rules['password'] = $this->editingId
            ? ['nullable', 'string', 'min:6']
            : ['required', 'string', 'min:6'];

        $validated = $this->validate($rules);

        // Aturan: satu sekolah hanya boleh punya 1 Admin OPS dan 1 Admin BOSP.
        if ($butuhSekolah) {
            $sudahAda = User::where('profil_sekolah_id', $validated['profil_sekolah_id'])
                ->where('level_akses', $this->level_akses)
                ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
                ->exists();

            if ($sudahAda) {
                $labelLevel = User::levelAksesOptions()[$this->level_akses];
                $this->addError('profil_sekolah_id', "Sekolah ini sudah memiliki akun {$labelLevel}.");

                return;
            }
        }

        $data = [
            'username' => $validated['username'],
            // Admin OPS/Admin BOSP: email = username (username mereka SUDAH
            // wajib berupa email dari validasi di atas) - dibuat otomatis di
            // sini SUPAYA akun yg ditambahkan Superadmin langsung lewat menu
            // ini (bukan lewat registrasi mandiri) tetap bisa lolos gerbang
            // Verifikasi Akses, konsisten dgn register.blade.php.
            // Superadmin: pakai nilai field Email terpisah di atas.
            'email' => $butuhSekolah ? $validated['username'] : ($validated['email'] ?? null),
            'level_akses' => $validated['level_akses'],
            'profil_sekolah_id' => $butuhSekolah ? $validated['profil_sekolah_id'] : null,
            'nama_sekolah' => $butuhSekolah
                ? ProfilSekolah::find($validated['profil_sekolah_id'])?->nama_sekolah
                : null,
            'jabatan' => $this->jabatanOtomatis($validated['level_akses']) ?: null,
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ($this->editingId) {
            User::findOrFail($this->editingId)->update($data);
            session()->flash('status', 'Pengguna berhasil diperbarui.');
        } else {
            // Akun yang dibuat langsung oleh Superadmin di sini otomatis disetujui.
            $data['password'] = Hash::make($validated['password']);
            $data['is_approved'] = true;
            User::create($data);
            session()->flash('status', 'Pengguna berhasil ditambahkan.');
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'pengguna-form');
    }

    public function setujui(int $id): void
    {
        User::whereKey($id)->update(['is_approved' => true]);
        session()->flash('status', 'Akun berhasil disetujui dan sekarang bisa digunakan untuk login.');
    }

    public function konfirmasiHapus(int $id): void
    {
        $this->confirmingDeleteId = $id;
        $this->dispatch('open-modal', 'pengguna-hapus');
    }

    public function batalHapus(): void
    {
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'pengguna-hapus');
    }

    public function hapus(): void
    {
        if ($this->confirmingDeleteId === auth()->id()) {
            $this->batalHapus();

            return;
        }

        User::whereKey($this->confirmingDeleteId)->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'pengguna-hapus');
        session()->flash('status', 'Pengguna berhasil dihapus.');
    }

    /**
     * Tampilkan modal konfirmasi reset password - HANYA untuk Admin
     * OPS/Admin BOSP (2026-09-05). Sejak jalur pemulihan mandiri
     * dinonaktifkan (lihat LoginForm::pemulihan()), ini satu-satunya cara
     * kedua level ini reset password kalau lupa. Superadmin tetap pakai
     * jalur pemulihan sendiri di form login, jadi tidak butuh tombol ini.
     */
    public function konfirmasiReset(int $id): void
    {
        $user = User::findOrFail($id);
        abort_if($user->isSuperadmin(), 403);

        $this->confirmingResetId = $id;
        $this->dispatch('open-modal', 'pengguna-reset');
    }

    public function batalReset(): void
    {
        $this->confirmingResetId = null;
        $this->dispatch('close-modal', 'pengguna-reset');
    }

    /**
     * Buat password baru acak 6 karakter (kombinasi huruf & angka) untuk
     * akun ini, wajibkan ganti password saat login berikutnya (memakai
     * mekanisme must_change_password yang sudah ada - sama seperti jalur
     * pemulihan lama), lalu kirim ke email akun tersebut (username Admin
     * OPS/Admin BOSP WAJIB berupa alamat email - lihat validasi di
     * simpan() & pages/auth/register.blade.php, jadi tidak perlu kolom
     * "email" terpisah).
     *
     * Passwordnya juga ditampilkan di pesan status sebagai CADANGAN kalau
     * email ternyata tidak diterima (mis. SMTP di server belum
     * dikonfigurasi) - lihat PETUNJUK.txt untuk cara mengisi SMTP asli.
     *
     * PENTING (2026-09-05, lanjutan): pengiriman email dibungkus try/catch
     * secara eksplisit - password akun SUDAH TERLANJUR diganti di database
     * di atas (harus tetap begitu, supaya Admin OPS/BOSP tetap bisa login
     * pakai password baru walau emailnya gagal terkirim), jadi kalau
     * Mail::send() melempar exception (mis. SMTP di .env server masih
     * default/salah/timeout - lihat PETUNJUK.txt), proses TIDAK BOLEH ikut
     * gagal total (sebelumnya: exception ini tidak ditangkap sama sekali,
     * jadi Livewire menampilkan error generik ke Superadmin dan pesan
     * status + password cadangan yang seharusnya tampil di layar malah
     * TIDAK PERNAH muncul - walau password akun sebenarnya sudah berhasil
     * diganti). Sekarang: kegagalan kirim dicatat ke log aplikasi dan
     * Superadmin tetap diberi tahu lewat pesan status yang jelas
     * membedakan "email terkirim" vs "email GAGAL terkirim" (dengan
     * password cadangan tetap ditampilkan di kedua kasus).
     */
    public function resetPassword(): void
    {
        $user = User::findOrFail($this->confirmingResetId);
        abort_if($user->isSuperadmin(), 403);

        $passwordBaru = $this->buatPasswordSementara();

        $user->forceFill([
            'password' => Hash::make($passwordBaru),
            'must_change_password' => true,
        ])->save();

        $emailTerkirim = true;

        try {
            Mail::to($user->username)->send(new PasswordDiresetSuperadmin($user, $passwordBaru));
        } catch (Throwable $e) {
            $emailTerkirim = false;

            Log::error('Gagal mengirim email reset password ke '.$user->username.': '.$e->getMessage());
        }

        $this->confirmingResetId = null;
        $this->dispatch('close-modal', 'pengguna-reset');

        if ($emailTerkirim) {
            session()->flash('status', "Password baru untuk {$user->username} berhasil dibuat & dikirim ke email tersebut. Password: {$passwordBaru} (catat sebagai cadangan kalau email belum diterima).");
        } else {
            session()->flash('status', "Password baru untuk {$user->username} berhasil dibuat, TAPI email GAGAL terkirim (kemungkinan SMTP di server belum/salah dikonfigurasi - lihat PETUNJUK.txt). Password: {$passwordBaru} - sampaikan password ini secara manual ke yang bersangkutan.");
        }
    }

    /**
     * Password sementara 6 karakter, kombinasi huruf & angka (dijamin
     * mengandung minimal 1 huruf DAN 1 angka, bukan cuma acak murni yang
     * bisa saja kebetulan semua huruf/semua angka). Karakter yang mirip
     * satu sama lain (I, l, O, o, 0, 1) sengaja dikeluarkan dari daftar
     * supaya lebih mudah dibaca & diketik ulang oleh Admin OPS/BOSP dari
     * email - bukan aturan bisnis, murni pertimbangan keterbacaan.
     */
    private function buatPasswordSementara(): string
    {
        $huruf = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz';
        $angka = '23456789';
        $semua = $huruf.$angka;

        $karakter = [
            $huruf[random_int(0, strlen($huruf) - 1)],
            $angka[random_int(0, strlen($angka) - 1)],
        ];

        while (count($karakter) < 6) {
            $karakter[] = $semua[random_int(0, strlen($semua) - 1)];
        }

        shuffle($karakter);

        return implode('', $karakter);
    }

    public function render()
    {
        $pengguna = null;
        $riwayatLogin = null;

        if ($this->tab === 'riwayat_login') {
            // Tab "Riwayat Login" (permintaan user 2026-09-26) - query
            // TERPISAH dari tab kelola-akun di bawah, memakai kolom
            // snapshot nama_sekolah/email yang tersimpan langsung di baris
            // login_histories (lihat model LoginHistory), bukan join ke
            // tabel users, supaya riwayat lama tetap benar walau data akun
            // berubah/dihapus belakangan.
            $riwayatLogin = LoginHistory::query()
                ->where('level_akses', $this->subTabRiwayat)
                ->when($this->searchRiwayat, fn ($q) => $q->where(function ($q) {
                    $q->where('nama_sekolah', 'like', "%{$this->searchRiwayat}%")
                        ->orWhere('email', 'like', "%{$this->searchRiwayat}%");
                }))
                ->orderByDesc('login_at')
                ->paginate(10, ['*'], 'riwayatPage');
        } else {
            $pengguna = User::query()
                ->where('level_akses', $this->tab)
                ->when($this->search, fn ($q) => $q->where(function ($q) {
                    $q->where('username', 'like', "%{$this->search}%")
                        ->orWhere('nama_sekolah', 'like', "%{$this->search}%");
                }))
                ->when($this->filterSekolahId, fn ($q) => $q->where('profil_sekolah_id', $this->filterSekolahId))
                ->orderBy('username')
                ->paginate(10);
        }

        return view('livewire.pengguna.index', [
            'pengguna' => $pengguna,
            'riwayatLogin' => $riwayatLogin,
            'levelOptions' => User::levelAksesOptions(),
            'sekolahOptions' => ProfilSekolah::orderBy('nama_sekolah')->get(),
            'jumlahMenunggu' => [
                User::LEVEL_ADMIN_OPS => User::where('level_akses', User::LEVEL_ADMIN_OPS)->where('is_approved', false)->count(),
                User::LEVEL_ADMIN_BOSP => User::where('level_akses', User::LEVEL_ADMIN_BOSP)->where('is_approved', false)->count(),
            ],
            'penggunaDireset' => $this->confirmingResetId ? User::find($this->confirmingResetId) : null,
        ]);
    }
}
