<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Integrasi Google Drive lewat Login Google (OAuth 2.0 Authorization Code
 * Flow, akun Google milik Superadmin sendiri) - permintaan user 2026-10-14
 * "file backup Lokal maupun Online bisa disimpan ke Google Drive".
 *
 * GANTI ARSITEKTUR (2026-10-14, sore, ditemukan lewat pengujian LANGSUNG
 * ke server Google): desain AWAL memakai Service Account (jawaban
 * AskUserQuestion "Service Account (Recommended)") TERNYATA TIDAK BISA
 * dipakai sama sekali untuk folder yang pemiliknya akun Gmail pribadi
 * biasa (bukan Google Workspace) - Google SENGAJA tidak memberi kuota
 * penyimpanan APA PUN ke Service Account di luar Shared Drive (fitur
 * khusus Workspace berbayar), jadi SETIAP upload SELALU ditolak dengan
 * pesan error resmi Google "Service Accounts do not have storage quota".
 * Dikonfirmasi lewat AskUserQuestion: akun Drive yang dipakai
 * (komisariat.cibadak@gmail.com) adalah Gmail pribadi, dan user memilih
 * pindah ke OAuth (gratis, tanpa berlangganan Google Workspace) - SELURUH
 * kelas ini ditulis ULANG dari Service Account/JWT Bearer Flow menjadi
 * OAuth 2.0 Authorization Code Flow biasa ("Login dengan Google" standar).
 * Alur baru: Superadmin klik "Hubungkan Google Drive" (lihat App\Http\
 * Controllers\GoogleDriveOAuthController), login via Google SEKALI, lalu
 * backup otomatis terunggah pakai kuota Drive akun Superadmin itu sendiri.
 *
 * SENGAJA TETAP TANPA paket Composer tambahan (google/apiclient dkk) -
 * konsisten dengan prinsip proyek ini: cukup Http facade (Guzzle) bawaan
 * Laravel utk panggilan REST ke endpoint OAuth & Drive API v3 resmi
 * Google.
 *
 * KEAMANAN: refresh_token (kunci jangka panjang ke akun Google Drive
 * Superadmin) disimpan di database kolom App\Models\GoogleDriveSetting::
 * refresh_token, TERENKRIPSI OTOMATIS lewat cast Eloquent 'encrypted'
 * (pakai APP_KEY aplikasi, bukan disimpan polos) - TIDAK PERNAH
 * ditampilkan di UI aplikasi mana pun dalam bentuk apa pun. client_id &
 * client_secret OAuth (didapat SEKALI dari Google Cloud Console, BUKAN
 * per-user) disimpan di .env server (GOOGLE_DRIVE_OAUTH_CLIENT_ID /
 * _SECRET), TIDAK ikut ter-commit (.env sudah dikecualikan git sejak awal
 * proyek) maupun ikut ter-zip ke dalam backup.
 *
 * Scope yang dipakai: 'drive.file' (least-privilege, SAMA seperti desain
 * awal - aplikasi HANYA bisa mengakses file yang DIBUAT lewat aplikasi
 * ini sendiri, BUKAN seluruh isi Drive Superadmin) + 'userinfo.email'
 * (HANYA utk menampilkan "Terhubung sebagai ..." di UI).
 */
class GoogleDriveService
{
    protected const SCOPE = 'https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/userinfo.email';

    protected const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    protected const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    protected const USERINFO_URL = 'https://www.googleapis.com/oauth2/v2/userinfo';

    protected const UPLOAD_URL = 'https://www.googleapis.com/upload/drive/v3/files';

    protected const FILES_URL = 'https://www.googleapis.com/drive/v3/files';

    /** client_id & client_secret OAuth sudah diisi di .env server ini? (dicek SEBELUM mulai alur "Hubungkan"). */
    public function terkonfigurasi(): bool
    {
        return filled(config('services.google_drive.oauth_client_id'))
            && filled(config('services.google_drive.oauth_client_secret'));
    }

    /**
     * Bangun URL ke halaman izin Google ("Login dengan Google"), tempat
     * Superadmin login & menyetujui akses. $state dipakai App\Http\
     * Controllers\GoogleDriveOAuthController utk mencegah CSRF pada route
     * callback (dicocokkan lagi saat Google mengarahkan balik).
     */
    public function urlOtorisasi(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'client_id' => config('services.google_drive.oauth_client_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            // prompt=consent DIPAKSA supaya Google SELALU mengirim
            // refresh_token baru (Google hanya mengirim refresh_token pada
            // persetujuan PERTAMA kecuali prompt=consent dipaksa) - juga
            // penting utk alur "Putuskan" lalu "Hubungkan" ulang dgn akun
            // yang sama/berbeda.
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return self::AUTH_URL.'?'.$query;
    }

    /**
     * Tukar authorization code (dari query ?code=... pada route callback)
     * menjadi refresh_token + email akun yang baru saja login. Dipanggil
     * SEKALI saja saat proses "Hubungkan" selesai - refresh_token hasilnya
     * yang disimpan jangka panjang oleh pemanggil.
     */
    public function tukarKodeOtorisasi(string $code, string $redirectUri): array
    {
        $respons = Http::asForm()->timeout(30)->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => config('services.google_drive.oauth_client_id'),
            'client_secret' => config('services.google_drive.oauth_client_secret'),
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if ($respons->failed()) {
            throw new RuntimeException(
                'Google menolak proses login (coba ulangi "Hubungkan Google Drive"): '
                .($respons->json('error_description') ?? ('HTTP '.$respons->status()))
            );
        }

        $refreshToken = $respons->json('refresh_token');
        $accessToken = $respons->json('access_token');

        if (empty($refreshToken)) {
            throw new RuntimeException('Google tidak mengirim refresh token. Buka myaccount.google.com/permissions, cabut akses aplikasi ini, lalu ulangi "Hubungkan Google Drive".');
        }

        $email = null;

        if ($accessToken) {
            $responsUser = Http::withToken($accessToken)->timeout(15)->get(self::USERINFO_URL);

            if ($responsUser->successful()) {
                $email = $responsUser->json('email');
            }
        }

        return [
            'refresh_token' => $refreshToken,
            'email' => $email,
        ];
    }

    /**
     * Unggah SATU file ke folder Google Drive tujuan, memakai akun yang
     * terhubung lewat $refreshToken. Melempar RuntimeException kalau
     * gagal - PEMANGGIL (App\Services\BackupService) SENGAJA tidak
     * meneruskan exception ini sampai membatalkan pembuatan backup lokal
     * yang sudah berhasil.
     */
    public function unggahFile(string $pathFileLokal, string $namaFile, string $folderId, string $refreshToken): void
    {
        if (! is_file($pathFileLokal)) {
            throw new RuntimeException('File backup tidak ditemukan untuk diunggah: '.$pathFileLokal);
        }

        $token = $this->dapatkanAccessToken($refreshToken);

        $metadata = json_encode([
            'name' => $namaFile,
            'parents' => [$folderId],
        ]);

        $respons = Http::withToken($token)
            ->timeout(300)
            ->attach('metadata', $metadata, 'metadata.json', ['Content-Type' => 'application/json; charset=UTF-8'])
            ->attach('file', fopen($pathFileLokal, 'r'), $namaFile)
            ->post(self::UPLOAD_URL.'?uploadType=multipart');

        if ($respons->failed()) {
            throw new RuntimeException(
                'Google Drive menolak unggahan (pastikan folder tujuan masih ada & koneksi akun Google masih aktif): '
                .($respons->json('error.message') ?? ('HTTP '.$respons->status()))
            );
        }
    }

    /**
     * Tempel nomor urut (4 digit, nol di depan) ke DEPAN nama file asli,
     * mis. namaBernomor(7, 'backup-2026-...zip') => "0007 - backup-2026-...zip".
     * Dipakai baik saat unggah file BARU maupun saat urutkanUlangSemuaFile()
     * menata ulang file yang SUDAH ada di Drive (permintaan user 2026-10-14).
     */
    public function namaBernomor(int $nomor, string $namaAsli): string
    {
        return sprintf('%04d - %s', $nomor, $namaAsli);
    }

    /**
     * Kebalikan dari namaBernomor() - buang prefix "0007 - " kalau ada,
     * supaya urutkanUlangSemuaFile() IDEMPOTEN (aman dijalankan berkali-kali,
     * tidak menumpuk prefix lama di depan prefix baru).
     */
    public function stripNomorPrefix(string $nama): string
    {
        return preg_replace('/^\d{4} - /', '', $nama);
    }

    /**
     * Ambil daftar SEMUA file di dalam $folderId (tidak termasuk yang sudah
     * dihapus/di-trash), diurutkan dari yang PALING LAMA dibuat - supaya
     * urutkanUlangSemuaFile() menomori sesuai urutan waktu backup dibuat.
     * Mengembalikan array of ['id' => ..., 'name' => ...].
     */
    public function daftarFileDiFolder(string $folderId, string $refreshToken): array
    {
        $token = $this->dapatkanAccessToken($refreshToken);

        $daftar = [];
        $pageToken = null;

        do {
            $query = [
                'q' => sprintf("'%s' in parents and trashed = false", $folderId),
                'orderBy' => 'createdTime',
                'fields' => 'nextPageToken, files(id, name)',
                'pageSize' => 1000,
            ];

            if ($pageToken) {
                $query['pageToken'] = $pageToken;
            }

            $respons = Http::withToken($token)->timeout(60)->get(self::FILES_URL, $query);

            if ($respons->failed()) {
                throw new RuntimeException(
                    'Gagal mengambil daftar file dari Google Drive: '
                    .($respons->json('error.message') ?? ('HTTP '.$respons->status()))
                );
            }

            foreach ((array) $respons->json('files', []) as $file) {
                $daftar[] = ['id' => $file['id'], 'name' => $file['name']];
            }

            $pageToken = $respons->json('nextPageToken');
        } while ($pageToken);

        return $daftar;
    }

    /**
     * Ganti nama SATU file di Google Drive (dipakai urutkanUlangSemuaFile()
     * utk menata ulang nomor urut file yang sudah terunggah sebelumnya).
     */
    public function gantiNamaFile(string $fileId, string $namaBaru, string $refreshToken): void
    {
        $token = $this->dapatkanAccessToken($refreshToken);

        $respons = Http::withToken($token)
            ->timeout(30)
            ->patch(self::FILES_URL.'/'.$fileId, ['name' => $namaBaru]);

        if ($respons->failed()) {
            throw new RuntimeException(
                'Gagal mengganti nama file di Google Drive: '
                .($respons->json('error.message') ?? ('HTTP '.$respons->status()))
            );
        }
    }

    /**
     * Tukar refresh_token menjadi access_token aktif (berlaku 1 jam,
     * dicache 45 menit PER refresh_token - supaya Lokal & Online yang
     * masing-masing punya refresh_token sendiri tidak saling tertukar
     * cache-nya - dan supaya tidak boros memanggil Google di setiap
     * unggahan).
     */
    protected function dapatkanAccessToken(string $refreshToken): string
    {
        return Cache::remember('google-drive-access-token-'.md5($refreshToken), 2700, function () use ($refreshToken) {
            $respons = Http::asForm()->timeout(30)->post(self::TOKEN_URL, [
                'refresh_token' => $refreshToken,
                'client_id' => config('services.google_drive.oauth_client_id'),
                'client_secret' => config('services.google_drive.oauth_client_secret'),
                'grant_type' => 'refresh_token',
            ]);

            if ($respons->failed()) {
                throw new RuntimeException(
                    'Gagal memperbarui akses ke Google Drive (akun mungkin sudah mencabut akses aplikasi ini di myaccount.google.com/permissions - coba "Putuskan Koneksi" lalu "Hubungkan Google Drive" ulang): '
                    .($respons->json('error_description') ?? ('HTTP '.$respons->status()))
                );
            }

            return (string) $respons->json('access_token');
        });
    }

    /**
     * Ekstrak Folder ID dari berbagai format link folder Google Drive yang
     * umum ditempel user, mis.:
     * - https://drive.google.com/drive/folders/<ID>?usp=sharing
     * - https://drive.google.com/drive/u/0/folders/<ID>
     * - atau ID folder polos saja (tanpa link utuh).
     * Mengembalikan null kalau formatnya tidak dikenali sama sekali.
     */
    public function ekstrakFolderId(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (preg_match('#/folders/([a-zA-Z0-9_-]+)#', $url, $cocok)) {
            return $cocok[1];
        }

        if (preg_match('#^[a-zA-Z0-9_-]{10,}$#', $url)) {
            return $url;
        }

        return null;
    }
}
