<?php

namespace App\Services;

use App\Models\RuntimeHelper;
use App\Support\RuntimeHelperProtectedNames;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

class RuntimeHelperBundleGenerator
{
    private const INTERNAL_RESERVED_NAMES = [
        'internalRuntimePost',
        'telegramRuntimeAction',
        'storageRuntimeGet',
        'storageRuntimeSet',
        'storageRuntimeFindUser',
        'paymentRuntimeAction',
        'oxapayRuntimeAction',
        'buildRuntimeHelpers',
        'buildHelpers',
        'runtimeSecret',
        'telegramBridgeSecret',
        'storageBridgeSecret',
        'oxapayBridgeSecret',
        'telegramBridgeUrl',
        'storageBridgeUrl',
        'oxapayBridgeUrl',
        '__storageMutations',
    ];

    public function __construct(
        private readonly RuntimeHelperSafetyScanner $scanner,
        private readonly ?string $livePathOverride = null,
        private readonly ?string $tempPathOverride = null,
    ) {}

    public function livePath(): string
    {
        return $this->livePathOverride ?: base_path('runtime-node/admin-helpers-generated.js');
    }

    public function tempPath(): string
    {
        return $this->tempPathOverride ?: storage_path('runtime/admin-helpers-generated.tmp.js');
    }

    public function generateContent(): array
    {
        $helpers = $this->activeHelpers();
        $compiled = [];
        $skipped = [];

        foreach ($helpers as $helper) {
            $name = (string) $helper->name;
            $code = (string) $helper->activeVersion->code;

            if (RuntimeHelperProtectedNames::isProtected($name)) {
                $skipped[] = [
                    'name' => $name,
                    'reason' => 'Protected helper name is reserved by the runtime.',
                ];
                continue;
            }

            $safety = $this->scanner->scan($code);
            if (! ($safety['passed'] ?? false)) {
                $blocked = $safety['blocked'] ?? 'blocked pattern';
                $skipped[] = [
                    'name' => $name,
                    'reason' => 'Safety scan failed: '.$blocked.' is not allowed.',
                ];
                continue;
            }

            $syntax = $this->scanner->syntaxCheck($code);
            if (! ($syntax['passed'] ?? false)) {
                $skipped[] = [
                    'name' => $name,
                    'reason' => 'Syntax check failed: '.Str::limit((string) ($syntax['error'] ?? 'Invalid JavaScript syntax.'), 500, ''),
                ];
                continue;
            }

            $compiled[] = [
                'id' => $helper->id,
                'name' => $name,
                'code' => $code,
            ];
        }

        $content = $compiled === []
            ? $this->emptyBundleContent()
            : $this->bundleContent($compiled);

        return [
            'ok' => true,
            'helpers_total' => $helpers->count(),
            'helpers_compiled' => count($compiled),
            'helpers_skipped' => count($skipped),
            'compiled' => collect($compiled)
                ->map(fn (array $helper) => ['id' => $helper['id'], 'name' => $helper['name']])
                ->values()
                ->all(),
            'skipped' => $skipped,
            'content' => $content,
        ];
    }

    public function writeTemp(string $content): string
    {
        $path = $this->tempPath();
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, $content);

        return $path;
    }

    public function checkBundleSyntax(string $path): array
    {
        $process = new Process([$this->nodeBinary(), '--check', $path]);
        $process->setTimeout(5);
        $process->run();

        $error = trim($process->getErrorOutput()) ?: trim($process->getOutput());

        return [
            'passed' => $process->isSuccessful(),
            'error' => $process->isSuccessful() ? null : Str::limit($error, 2000, ''),
            'exit_code' => $process->getExitCode(),
        ];
    }

    public function activateTemp(string $tempPath): void
    {
        $livePath = $this->livePath();
        $directory = dirname($livePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (! @rename($tempPath, $livePath)) {
            throw new \RuntimeException('Could not activate generated runtime helper bundle.');
        }
    }

    public function generateToTemp(): array
    {
        $report = $this->generateContent();
        $report['temp_path'] = $this->writeTemp((string) $report['content']);

        return $report;
    }

    public function publish(): array
    {
        try {
            $report = $this->generateToTemp();
            $tempPath = (string) $report['temp_path'];
            $syntax = $this->checkBundleSyntax($tempPath);

            if (! ($syntax['passed'] ?? false)) {
                if (is_file($tempPath)) {
                    @unlink($tempPath);
                }

                return [
                    ...$report,
                    'ok' => false,
                    'error' => 'Generated bundle syntax check failed. '.($syntax['error'] ?? ''),
                    'syntax' => $syntax,
                ];
            }

            $this->activateTemp($tempPath);

            return [
                ...$report,
                'ok' => true,
                'live_path' => $this->livePath(),
                'syntax' => $syntax,
            ];
        } catch (Throwable $exception) {
            $tempPath = $this->tempPath();
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }

            return [
                'ok' => false,
                'helpers_total' => 0,
                'helpers_compiled' => 0,
                'helpers_skipped' => 0,
                'skipped' => [],
                'content' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function activeHelpers()
    {
        return RuntimeHelper::query()
            ->with(['activeVersion', 'category'])
            ->where('status', 'active')
            ->where('expose_to_bot_code', true)
            ->whereNotNull('active_version_id')
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get()
            ->filter(fn (RuntimeHelper $helper) => $helper->activeVersion !== null)
            ->values();
    }

    private function bundleContent(array $helpers): string
    {
        $lines = [
            "'use strict';",
            '',
            '// This file is generated by BotHost Pro.',
            '// Do not edit manually.',
            '',
            'module.exports = {',
            'buildAdminHelpers: function(systemHelpers) {',
            'const _adminHelpers = {};',
            '',
            ...$this->systemHelperAssignments(),
            '',
        ];

        foreach ($helpers as $helper) {
            $helperName = $this->jsString($helper['name']);

            $lines[] = 'try {';
            $lines[] = "  _adminHelpers[{$helperName}] = async function(params) {";
            foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", rtrim((string) $helper['code']))) as $codeLine) {
                $lines[] = '    '.$codeLine;
            }
            $lines[] = '  };';
            $lines[] = '} catch (_err) {';
            $lines[] = "  console.error('[BotHost] admin helper init failed: {$helper['name']}', _err && _err.message);";
            $lines[] = '}';
            $lines[] = '';
        }

        $lines[] = 'return _adminHelpers;';
        $lines[] = '}';
        $lines[] = '};';
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function emptyBundleContent(): string
    {
        return "'use strict';\n\n"
            ."module.exports = {\n"
            ."buildAdminHelpers: function(systemHelpers) {\n"
            ."return {};\n"
            ."}\n"
            ."};\n";
    }

    private function systemHelperAssignments(): array
    {
        return collect(RuntimeHelperProtectedNames::all())
            ->reject(fn (string $name) => in_array($name, self::INTERNAL_RESERVED_NAMES, true))
            ->filter(fn (string $name) => preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*$/', $name) === 1)
            ->unique()
            ->sort()
            ->map(fn (string $name) => "const {$name} = systemHelpers.{$name};")
            ->values()
            ->all();
    }

    private function jsString(string $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
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
