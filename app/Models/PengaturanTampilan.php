<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Pengaturan tampilan aplikasi (satu baris/singleton).
 *
 * Dikelola khusus oleh Superadmin lewat menu Tampilan, dan menentukan:
 * - Gambar latar belakang halaman pemilihan akses (landing) & form login.
 * - Warna halaman & warna menu (sisi Superadmin, Admin OPS, Admin BOSP).
 * - Ukuran & jenis huruf untuk teks halaman dan teks menu.
 */
#[Fillable([
    'background_landing',
    'background_login',
    'warna_halaman',
    'warna_menu',
    'warna_huruf_menu',
    'warna_huruf_landing',
    'warna_huruf_login',
    'warna_huruf_registrasi',
    'ukuran_huruf_halaman',
    'ukuran_huruf_menu',
    'ukuran_huruf_registrasi',
    'jenis_huruf_halaman',
    'jenis_huruf_menu',
    'jenis_huruf_registrasi',
])]
class PengaturanTampilan extends Model
{
    protected $table = 'pengaturan_tampilan';

    public const ID_SINGLETON = 1;

    /**
     * Pilihan ukuran huruf beserta nilai px untuk teks halaman.
     */
    public const UKURAN_HALAMAN_PX = [
        'kecil' => '14px',
        'sedang' => '16px',
        'besar' => '18px',
    ];

    /**
     * Pilihan ukuran huruf beserta nilai px untuk teks menu.
     */
    public const UKURAN_MENU_PX = [
        'kecil' => '13px',
        'sedang' => '14px',
        'besar' => '16px',
    ];

    /**
     * Pilihan ukuran huruf beserta nilai px untuk teks form Registrasi.
     */
    public const UKURAN_REGISTRASI_PX = [
        'kecil' => '13px',
        'sedang' => '14px',
        'besar' => '16px',
    ];

    /**
     * Pilihan jenis huruf yang tersedia (semua sudah dimuat dari Bunny Fonts).
     */
    public const JENIS_HURUF_OPTIONS = [
        'Figtree' => 'Figtree',
        'Inter' => 'Inter',
        'Poppins' => 'Poppins',
        'Roboto' => 'Roboto',
        'Nunito' => 'Nunito',
    ];

    public static function ukuranOptions(): array
    {
        return [
            'kecil' => 'Kecil',
            'sedang' => 'Sedang',
            'besar' => 'Besar',
        ];
    }

    private const CACHE_KEY = 'pengaturan-tampilan';

    /**
     * Disimpan sekali per request supaya layout (app/guest) dan navigasi
     * yang sama-sama memanggil current() tidak melakukan query berulang.
     */
    protected static ?self $dimuat = null;

    /**
     * Ambil (atau buat dengan nilai bawaan) baris pengaturan tampilan
     * tunggal. Di-cache (per request & lintas request) supaya tidak
     * membebani setiap halaman dengan query database berulang.
     *
     * Yang disimpan di cache sengaja hanya array atribut biasa (bukan
     * objek Model utuh) - supaya cache lama tidak pernah menjadi rusak
     * (__PHP_Incomplete_Class) saat aplikasi di-update dan struktur
     * class-nya berubah antar deployment.
     */
    public static function current(): self
    {
        if (static::$dimuat !== null) {
            return static::$dimuat;
        }

        $atribut = Cache::rememberForever(self::CACHE_KEY, function () {
            return static::query()->firstOrCreate(
                ['id' => self::ID_SINGLETON],
                [
                    'warna_halaman' => '#f1f5f9',
                    'warna_menu' => '#0f172a',
                    'warna_huruf_menu' => '#cbd5e1',
                    'warna_huruf_landing' => '#334155',
                    'warna_huruf_login' => '#334155',
                    'warna_huruf_registrasi' => '#334155',
                    'ukuran_huruf_halaman' => 'sedang',
                    'ukuran_huruf_menu' => 'sedang',
                    'ukuran_huruf_registrasi' => 'sedang',
                    'jenis_huruf_halaman' => 'Figtree',
                    'jenis_huruf_menu' => 'Figtree',
                    'jenis_huruf_registrasi' => 'Figtree',
                ]
            )->getAttributes();
        });

        $model = new static();
        $model->setRawAttributes(is_array($atribut) ? $atribut : [], true);
        $model->exists = true;

        return static::$dimuat = $model;
    }

    /**
     * Bersihkan cache pengaturan tampilan. Wajib dipanggil setiap kali
     * baris ini diubah (lihat Livewire\Tampilan\Index::simpan()).
     */
    public static function lupakanCache(): void
    {
        static::$dimuat = null;
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Disajikan lewat route aplikasi (bukan URL statis symlink
     * "public/storage" yang dibangun dari config APP_URL) - lihat
     * penjelasan lengkap di TampilanBackgroundController. Parameter
     * query "v" (versi, dari updated_at) sengaja ditambahkan supaya
     * browser tidak menyimpan cache gambar LAMA setelah Superadmin
     * mengganti background (URL-nya jadi "baru" tiap kali baris ini
     * diupdate, walau path route-nya sama).
     */
    public function getBackgroundLandingUrlAttribute(): ?string
    {
        return $this->background_landing
            ? route('tampilan.background', ['jenis' => 'landing', 'v' => $this->updated_at?->timestamp])
            : null;
    }

    public function getBackgroundLoginUrlAttribute(): ?string
    {
        return $this->background_login
            ? route('tampilan.background', ['jenis' => 'login', 'v' => $this->updated_at?->timestamp])
            : null;
    }

    public function getUkuranHurufHalamanPxAttribute(): string
    {
        return self::UKURAN_HALAMAN_PX[$this->ukuran_huruf_halaman] ?? self::UKURAN_HALAMAN_PX['sedang'];
    }

    public function getUkuranHurufMenuPxAttribute(): string
    {
        return self::UKURAN_MENU_PX[$this->ukuran_huruf_menu] ?? self::UKURAN_MENU_PX['sedang'];
    }

    public function getUkuranHurufRegistrasiPxAttribute(): string
    {
        return self::UKURAN_REGISTRASI_PX[$this->ukuran_huruf_registrasi] ?? self::UKURAN_REGISTRASI_PX['sedang'];
    }

    /**
     * Query Bunny Fonts hanya untuk jenis huruf yang sedang benar-benar
     * dipakai (halaman & menu, tanpa duplikat) - supaya halaman tidak
     * memuat semua pilihan font sekaligus dan terasa lebih cepat.
     */
    public function getFontsQueryAttribute(): string
    {
        $dipakai = collect([$this->jenis_huruf_halaman, $this->jenis_huruf_menu, $this->jenis_huruf_registrasi])
            ->filter()
            ->unique()
            ->map(fn (string $font) => strtolower($font).':400,500,600');

        return $dipakai->isEmpty() ? 'figtree:400,500,600' : $dipakai->implode('|');
    }
}
