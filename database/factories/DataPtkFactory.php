<?php

namespace Database\Factories;

use App\Models\DataPtk;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Dibuat 2026-10-03 (sebelumnya belum ada, padahal App\Models\DataPtk
 * sudah memakai #[HasFactory] sejak awal dibuat 2026-10-01) - dibutuhkan
 * supaya test Lampiran2b/Lampiran2c bisa menyiapkan baris Data PTK
 * sebagai sumber Nama PTK/NRG/NUPTK (lihat Lampiran2bTest/Lampiran2cTest).
 *
 * @extends Factory<DataPtk>
 */
class DataPtkFactory extends Factory
{
    protected $model = DataPtk::class;

    public function definition(): array
    {
        return [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'nik' => $this->faker->unique()->numerify('################'),
            'nuptk' => $this->faker->numerify('################'),
            'nip' => null,
            'nama_ptk' => $this->faker->name(),
            'tempat_lahir' => $this->faker->city(),
            'tanggal_lahir' => $this->faker->date(),
            'jabatan' => 'Guru Ahli Pertama',
            'pangkat_golongan' => 'Penata Muda III/a',
            'status_kepegawaian' => 'PNS',
            'jenis_ptk' => 'Guru',
            'tmt_sekolah_induk' => $this->faker->date(),
            'pendidikan_terakhir' => 'S1',
            'jurusan_prodi' => $this->faker->word(),
            'tahun_lulus_ijazah' => '2010',
            'status_sertifikasi' => DataPtk::STATUS_SERTIFIKASI_SUDAH,
            'bidang_studi_sertifikasi' => $this->faker->word(),
            'tahun_lulus_sertifikasi' => '2015',
            'nomor_sertifikat_sertifikasi' => $this->faker->numerify('##########'),
            'nomor_registrasi_guru' => $this->faker->numerify('############'),
            'nomor_peserta_sertifikasi' => $this->faker->numerify('##########'),
            'status_dapodik' => 'Sudah Masuk Dapodik',
            'status_keaktifan' => 'Aktif',
        ];
    }
}
