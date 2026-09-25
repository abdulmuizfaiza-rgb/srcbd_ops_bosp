<?php

namespace Tests;

use App\Models\PengaturanTampilan;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // PengaturanTampilan::current() di-cache per proses (lihat model)
        // supaya cepat di request sungguhan, tapi PHPUnit menjalankan semua
        // test dalam satu proses PHP - reset di sini supaya tiap test mulai
        // dari kondisi bersih, tidak membawa data cache dari test sebelumnya.
        PengaturanTampilan::lupakanCache();
    }
}
