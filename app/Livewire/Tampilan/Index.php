<?php

namespace App\Livewire\Tampilan;

use App\Models\PengaturanTampilan;
use App\Support\ImageCropper;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Menu Tampilan (khusus Superadmin) - mengatur tampilan aplikasi untuk
 * sisi Superadmin, Admin OPS, dan Admin BOSP: gambar latar halaman
 * pemilihan akses & form login, warna halaman, warna menu, serta ukuran
 * dan jenis huruf halaman/menu.
 */
#[Layout('layouts.app')]
#[Title('Tampilan')]
class Index extends Component
{
    use WithFileUploads;

    /**
     * Lebar/tinggi target (px) untuk gambar latar - gambar yang diunggah
     * otomatis di-crop ke ukuran ini apabila dimensinya lebih besar.
     */
    private const TARGET_LEBAR = 1920;

    private const TARGET_TINGGI = 1080;

    public $backgroundLandingBaru = null;

    public $backgroundLoginBaru = null;

    public string $warnaHalaman = '#f1f5f9';

    public string $warnaMenu = '#0f172a';

    public string $warnaHurufMenu = '#cbd5e1';

    public string $warnaHurufLanding = '#334155';

    public string $warnaHurufLogin = '#334155';

    public string $warnaHurufRegistrasi = '#334155';

    public string $ukuranHurufHalaman = 'sedang';

    public string $ukuranHurufMenu = 'sedang';

    public string $ukuranHurufRegistrasi = 'sedang';

    public string $jenisHurufHalaman = 'Figtree';

    public string $jenisHurufMenu = 'Figtree';

    public string $jenisHurufRegistrasi = 'Figtree';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('akses-tampilan'), 403);

        $tampilan = PengaturanTampilan::current();

        $this->warnaHalaman = $tampilan->warna_halaman;
        $this->warnaMenu = $tampilan->warna_menu;
        $this->warnaHurufMenu = $tampilan->warna_huruf_menu;
        $this->warnaHurufLanding = $tampilan->warna_huruf_landing;
        $this->warnaHurufLogin = $tampilan->warna_huruf_login;
        $this->warnaHurufRegistrasi = $tampilan->warna_huruf_registrasi;
        $this->ukuranHurufHalaman = $tampilan->ukuran_huruf_halaman;
        $this->ukuranHurufMenu = $tampilan->ukuran_huruf_menu;
        $this->ukuranHurufRegistrasi = $tampilan->ukuran_huruf_registrasi;
        $this->jenisHurufHalaman = $tampilan->jenis_huruf_halaman;
        $this->jenisHurufMenu = $tampilan->jenis_huruf_menu;
        $this->jenisHurufRegistrasi = $tampilan->jenis_huruf_registrasi;
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()->can('akses-tampilan'), 403);

        $this->validate([
            'backgroundLandingBaru' => ['nullable', 'image', 'max:5120'],
            'backgroundLoginBaru' => ['nullable', 'image', 'max:5120'],
            'warnaHalaman' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'warnaMenu' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'warnaHurufMenu' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'warnaHurufLanding' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'warnaHurufLogin' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'warnaHurufRegistrasi' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'ukuranHurufHalaman' => ['required', 'in:kecil,sedang,besar'],
            'ukuranHurufMenu' => ['required', 'in:kecil,sedang,besar'],
            'ukuranHurufRegistrasi' => ['required', 'in:kecil,sedang,besar'],
            'jenisHurufHalaman' => ['required', 'in:'.implode(',', array_keys(PengaturanTampilan::JENIS_HURUF_OPTIONS))],
            'jenisHurufMenu' => ['required', 'in:'.implode(',', array_keys(PengaturanTampilan::JENIS_HURUF_OPTIONS))],
            'jenisHurufRegistrasi' => ['required', 'in:'.implode(',', array_keys(PengaturanTampilan::JENIS_HURUF_OPTIONS))],
        ]);

        $tampilan = PengaturanTampilan::current();

        $data = [
            'warna_halaman' => $this->warnaHalaman,
            'warna_menu' => $this->warnaMenu,
            'warna_huruf_menu' => $this->warnaHurufMenu,
            'warna_huruf_landing' => $this->warnaHurufLanding,
            'warna_huruf_login' => $this->warnaHurufLogin,
            'warna_huruf_registrasi' => $this->warnaHurufRegistrasi,
            'ukuran_huruf_halaman' => $this->ukuranHurufHalaman,
            'ukuran_huruf_menu' => $this->ukuranHurufMenu,
            'ukuran_huruf_registrasi' => $this->ukuranHurufRegistrasi,
            'jenis_huruf_halaman' => $this->jenisHurufHalaman,
            'jenis_huruf_menu' => $this->jenisHurufMenu,
            'jenis_huruf_registrasi' => $this->jenisHurufRegistrasi,
        ];

        if ($this->backgroundLandingBaru) {
            $data['background_landing'] = $this->simpanGambar($this->backgroundLandingBaru, 'tampilan', $tampilan->background_landing);
        }

        if ($this->backgroundLoginBaru) {
            $data['background_login'] = $this->simpanGambar($this->backgroundLoginBaru, 'tampilan', $tampilan->background_login);
        }

        $tampilan->update($data);
        PengaturanTampilan::lupakanCache();

        $this->backgroundLandingBaru = null;
        $this->backgroundLoginBaru = null;

        session()->flash('status', 'Pengaturan tampilan berhasil disimpan.');
    }

    /**
     * Simpan file upload ke disk publik, meng-crop otomatis apabila
     * dimensinya melebihi ukuran target, lalu hapus file lama (jika ada).
     */
    private function simpanGambar($upload, string $folder, ?string $lamaPath): string
    {
        $hasilCrop = ImageCropper::cropIfTooLarge($upload->getRealPath(), self::TARGET_LEBAR, self::TARGET_TINGGI);

        if ($hasilCrop !== null) {
            $namaFile = $folder.'/'.uniqid('bg_', true).'.jpg';
            Storage::disk('public')->put($namaFile, $hasilCrop);
            $path = $namaFile;
        } else {
            $path = $upload->store($folder, 'public');
        }

        if ($lamaPath) {
            Storage::disk('public')->delete($lamaPath);
        }

        return $path;
    }

    public function render()
    {
        return view('livewire.tampilan.index', [
            'tampilan' => PengaturanTampilan::current(),
            'ukuranOptions' => PengaturanTampilan::ukuranOptions(),
            'jenisHurufOptions' => PengaturanTampilan::JENIS_HURUF_OPTIONS,
        ]);
    }
}
