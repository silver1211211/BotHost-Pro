<?php

namespace App\Services;

use App\Models\RuntimeReloadLog;
use Symfony\Component\Process\Process;

class RuntimeReloadProcessLauncher
{
    public function diagnostics(): array
    {
        $logsPath = storage_path('logs');
        $errors = [];

        if (! is_dir($logsPath)) {
            @mkdir($logsPath, 0755, true);
        }

        $artisanExists = is_file(base_path('artisan'));
        $logsWritable = is_dir($logsPath) && is_writable($logsPath);
        $procOpenAvailable = function_exists('proc_open');
        $phpExecutable = PHP_BINARY !== '' && (@is_file(PHP_BINARY) ? @is_executable(PHP_BINARY) : true);

        if (! $phpExecutable) {
            $errors[] = 'PHP binary is not executable.';
        }

        if (! $artisanExists) {
            $errors[] = 'artisan file was not found.';
        }

        if (! $logsWritable) {
            $errors[] = 'storage/logs is not writable.';
        }

        if (! $procOpenAvailable) {
            $errors[] = 'proc_open is not available.';
        }

        return [
            'ok' => $errors === [],
            'php_binary' => PHP_BINARY,
            'php_executable' => $phpExecutable,
            'artisan_exists' => $artisanExists,
            'logs_writable' => $logsWritable,
            'proc_open_available' => $procOpenAvailable,
            'log_path' => $logsPath,
            'errors' => $errors,
        ];
    }

    public function start(RuntimeReloadLog $log, array $options): void
    {
        $diagnostics = $this->diagnostics();
        if (! ($diagnostics['ok'] ?? false)) {
            throw new \RuntimeException(implode(' ', $diagnostics['errors'] ?? ['Runtime reload process diagnostics failed.']));
        }

        $arguments = [
            PHP_BINARY,
            'artisan',
            'runtime:reload',
            '--log-id='.$log->id,
        ];

        if ($options['publish_bundle'] ?? false) {
            $arguments[] = '--publish-bundle';
        }

        if ($options['docker_refresh'] ?? false) {
            $arguments[] = '--docker-refresh';
            $arguments[] = '--dry-run='.(($options['dry_run'] ?? true) ? '1' : '0');
        }

        if ($options['confirm_live_refresh'] ?? false) {
            $arguments[] = '--confirm-live-refresh';
        }

        $outputPath = storage_path('logs/runtime-reload-'.$log->id.'.log');
        @file_put_contents($outputPath, '['.now()->toDateTimeString()."] Runtime reload process started.\n", FILE_APPEND);

        (new Process($arguments, base_path()))->start(function (string $type, string $buffer) use ($outputPath): void {
            @file_put_contents($outputPath, $buffer, FILE_APPEND);
        });
    }
}
