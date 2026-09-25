<?php

namespace App\Services;

use Illuminate\Contracts\Process\ProcessResult;
use RuntimeException;

/**
 * Round DUA PULUH TUJUH (lanjutan/bagian 7, 2026-09-24) - dipakai HANYA
 * lewat BackupService::jalankanViaRedirectFile() (khusus Windows): hasil
 * proses yang stdout/stderr-nya dibaca dari FILE sementara (output
 * di-redirect lewat shell ">"/"2>"), bukan dari pipe Symfony Process
 * langsung - lihat penjelasan lengkap di BackupService::jalankanPgDump().
 * Mengimplementasikan kontrak ProcessResult yang sama spt hasil
 * Process::run() biasa, supaya kode pemanggil (dumpPgsql()) tidak perlu
 * tahu bedanya sama sekali.
 */
final class HasilProsesRedirectFile implements ProcessResult
{
    public function __construct(
        private readonly string $command,
        private readonly ?int $exitCode,
        private readonly string $output,
        private readonly string $errorOutput,
    ) {}

    public function command()
    {
        return $this->command;
    }

    public function successful()
    {
        return $this->exitCode === 0;
    }

    public function failed()
    {
        return ! $this->successful();
    }

    public function exitCode()
    {
        return $this->exitCode;
    }

    public function output()
    {
        return $this->output;
    }

    public function seeInOutput(string $output)
    {
        return str_contains($this->output, $output);
    }

    public function errorOutput()
    {
        return $this->errorOutput;
    }

    public function seeInErrorOutput(string $output)
    {
        return str_contains($this->errorOutput, $output);
    }

    public function throw(?callable $callback = null)
    {
        if ($this->successful()) {
            return $this;
        }

        $exception = new RuntimeException('Proses gagal (exit code '.($this->exitCode ?? '?').'): '.$this->command);

        if ($callback) {
            $callback($this, $exception);
        }

        throw $exception;
    }

    public function throwIf(bool $condition, ?callable $callback = null)
    {
        if ($condition) {
            return $this->throw($callback);
        }

        return $this;
    }
}
