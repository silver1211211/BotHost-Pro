<?php

namespace App\Services;

use App\Support\RuntimeHelperProtectedNames;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\Process\Process;

class RuntimeHelperSafetyScanner
{
    private const BLOCKED_PATTERNS = [
        'process' => '/\bprocess\b/',
        'require(' => '/\brequire\s*\(/',
        'import' => '/\bimport\b/',
        'import(' => '/\bimport\s*\(/',
        'eval(' => '/\beval\s*\(/',
        'Function(' => '/\bFunction\s*\(/',
        'new Function(' => '/\bnew\s+Function\s*\(/',
        'global' => '/\bglobal\b/',
        'globalThis' => '/\bglobalThis\b/',
        'child_process' => '/\bchild_process\b/',
        'fs' => '/\bfs\b/',
        '__dirname' => '/\b__dirname\b/',
        '__filename' => '/\b__filename\b/',
        'module' => '/\bmodule\b/',
        'exports' => '/\bexports\b/',
        'Buffer' => '/\bBuffer\b/',
        'WebAssembly' => '/\bWebAssembly\b/',
        'internalRuntimePost' => '/\binternalRuntimePost\b/',
        'telegramRuntimeAction' => '/\btelegramRuntimeAction\b/',
        'storageRuntimeGet' => '/\bstorageRuntimeGet\b/',
        'storageRuntimeSet' => '/\bstorageRuntimeSet\b/',
        'storageRuntimeFindUser' => '/\bstorageRuntimeFindUser\b/',
        'paymentRuntimeAction' => '/\bpaymentRuntimeAction\b/',
        'oxapayRuntimeAction' => '/\boxapayRuntimeAction\b/',
        'runtimeSecret' => '/\bruntimeSecret\b/',
        'telegramBridgeSecret' => '/\btelegramBridgeSecret\b/',
        'storageBridgeSecret' => '/\bstorageBridgeSecret\b/',
        'oxapayBridgeSecret' => '/\boxapayBridgeSecret\b/',
        'telegramBridgeUrl' => '/\btelegramBridgeUrl\b/',
        'storageBridgeUrl' => '/\bstorageBridgeUrl\b/',
        'oxapayBridgeUrl' => '/\boxapayBridgeUrl\b/',
        'this[' => '/\bthis\s*\[/',
        'globalThis[' => '/\bglobalThis\s*\[/',
        '["constructor"]' => '/\[\s*"constructor"\s*\]/',
        "['constructor']" => "/\[\s*'constructor'\s*\]/",
        '["process"]' => '/\[\s*"process"\s*\]/',
        "['process']" => "/\[\s*'process'\s*\]/",
        '["global"]' => '/\[\s*"global"\s*\]/',
        "['global']" => "/\[\s*'global'\s*\]/",
        'constructor.constructor' => '/\bconstructor\s*\.\s*constructor\b/',
        '.constructor' => '/\.constructor\b/',
        '__proto__' => '/__proto__/',
        '.prototype' => '/\.prototype\b/',
        '["prototype"]' => '/\[\s*"prototype"\s*\]/',
        "['prototype']" => "/\[\s*'prototype'\s*\]/",
        'setInterval(' => '/\bsetInterval\s*\(/',
        'setImmediate(' => '/\bsetImmediate\s*\(/',
        'clearInterval(' => '/\bclearInterval\s*\(/',
    ];

    public function scan(string $code): array
    {
        foreach (self::BLOCKED_PATTERNS as $blocked => $pattern) {
            if (preg_match($pattern, $code) === 1) {
                return [
                    'passed' => false,
                    'blocked' => $blocked,
                    'message' => "Helper code cannot use {$blocked}.",
                    'pattern' => $pattern,
                ];
            }
        }

        return [
            'passed' => true,
            'blocked' => null,
            'message' => null,
            'pattern' => null,
        ];
    }

    public function assertSafe(string $code): void
    {
        $result = $this->scan($code);

        if (! $result['passed']) {
            throw ValidationException::withMessages([
                'code' => $result['message'] ?? 'Helper code contains a blocked runtime pattern.',
            ]);
        }
    }

    public function syntaxCheck(string $code): array
    {
        $path = tempnam(sys_get_temp_dir(), 'bothost_helper_check_');

        if ($path === false) {
            throw new RuntimeException('Could not create a temporary syntax check file.');
        }

        $jsPath = $path.'.js';
        rename($path, $jsPath);

        try {
            file_put_contents($jsPath, $this->wrapForSyntaxCheck($code));

            $process = new Process([$this->nodeBinary(), '--check', $jsPath]);
            $process->setTimeout(5);
            $process->run();

            $error = trim($process->getErrorOutput()) ?: trim($process->getOutput());

            return [
                'passed' => $process->isSuccessful(),
                'error' => $process->isSuccessful() ? null : Str::limit($error, 2000, ''),
                'exit_code' => $process->getExitCode(),
            ];
        } finally {
            if (is_file($jsPath)) {
                @unlink($jsPath);
            }
        }
    }

    public function assertSyntaxValid(string $code): void
    {
        $result = $this->syntaxCheck($code);

        if (! $result['passed']) {
            throw ValidationException::withMessages([
                'code' => 'Helper code has invalid JavaScript syntax. '.($result['error'] ?? ''),
            ]);
        }
    }

    public function validateHelperName(string $name): array
    {
        $name = RuntimeHelperProtectedNames::normalize($name);

        if (! preg_match('/^[A-Za-z_$][A-Za-z0-9_$]{1,99}$/', $name)) {
            return [
                'passed' => false,
                'message' => 'Helper name must be a valid JavaScript identifier between 2 and 100 characters.',
            ];
        }

        if (RuntimeHelperProtectedNames::isProtected($name)) {
            return [
                'passed' => false,
                'message' => "The helper name {$name} is reserved by the runtime.",
            ];
        }

        return [
            'passed' => true,
            'message' => null,
        ];
    }

    public function assertValidHelperName(string $name): void
    {
        $result = $this->validateHelperName($name);

        if (! $result['passed']) {
            throw ValidationException::withMessages([
                'name' => $result['message'] ?? 'Helper name is invalid.',
            ]);
        }
    }

    private function wrapForSyntaxCheck(string $code): string
    {
        return "'use strict';\n"
            ."module.exports = {\n"
            ."buildAdminHelpers: function(systemHelpers) {\n"
            ."const _adminHelpers = {};\n"
            ."_adminHelpers.__testHelper = async function(params) {\n"
            .$code."\n"
            ."};\n"
            ."return _adminHelpers;\n"
            ."}\n"
            ."};\n";
    }

    private function nodeBinary(): string
    {
        $binary = (string) config('runtime.node_binary', 'node');

        if ($binary !== 'node' || DIRECTORY_SEPARATOR !== '\\') {
            return $binary;
        }

        $paths = [];
        @exec('where node 2>NUL', $paths);

        usort($paths, fn (string $a, string $b): int => (int) str_contains($a, ' ') <=> (int) str_contains($b, ' '));

        foreach ($paths as $path) {
            $path = trim($path);

            if ($path !== '' && str_ends_with(strtolower($path), '.exe') && is_file($path)) {
                return $path;
            }
        }

        return $binary;
    }
}
