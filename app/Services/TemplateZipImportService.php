<?php

namespace App\Services;

use App\Models\BotTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class TemplateZipImportService
{
    private const ALLOWED_EXTENSIONS = ['json', 'js', 'txt', 'md'];
    private const BLOCKED_EXTENSIONS = ['php', 'exe', 'bat', 'sh', 'cmd', 'html', 'svg', 'env', 'config', 'sql'];

    public function storeZip(UploadedFile $file, ?string $oldPath = null): string
    {
        return $this->storeTemplateFile($file, $oldPath);
    }

    public function storeTemplateFile(UploadedFile $file, ?string $oldPath = null): string
    {
        $path = $file->store('templates/zips', 'local');

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('local')->delete($oldPath);
        }

        return $path;
    }

    public function parse(UploadedFile|string $file): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (! $path || ! is_readable($path)) {
            throw ValidationException::withMessages(['template_zip' => 'Template file could not be opened.']);
        }

        if ($this->looksLikeJsonFile($file, $path)) {
            return $this->parseJsonExport((string) file_get_contents($path), $this->sourceName($file));
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['template_zip' => 'Template file must be a valid ZIP or JSON export.']);
        }

        try {
            $files = $this->readSafeFiles($zip);
            $commands = $this->commandsFromFiles($files);
        } finally {
            $zip->close();
        }

        if ($commands === []) {
            throw ValidationException::withMessages(['template_zip' => 'Template file must contain at least one valid command.']);
        }

        return [
            'commands' => $commands,
            'summary' => [
                'detected' => count($commands),
                'imported' => count($commands),
                'skipped' => 0,
                'skipped_duplicates' => [],
                'errors' => [],
                'source' => 'zip',
            ],
        ];
    }

    private function looksLikeJsonFile(UploadedFile|string $file, string $path): bool
    {
        $extension = strtolower(pathinfo($this->sourceName($file), PATHINFO_EXTENSION));
        $mime = $file instanceof UploadedFile ? strtolower((string) $file->getMimeType()) : '';

        if ($extension === 'json' || str_contains($mime, 'json')) {
            return true;
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        $prefix = ltrim((string) fread($handle, 64));
        fclose($handle);

        return str_starts_with($prefix, '{') || str_starts_with($prefix, '[');
    }

    private function sourceName(UploadedFile|string $file): string
    {
        return $file instanceof UploadedFile
            ? (string) ($file->getClientOriginalName() ?: 'template.json')
            : basename($file);
    }

    private function parseJsonExport(string $content, string $name): array
    {
        $payload = $this->decodeJson($content, $name, 'Template JSON export');
        $definitions = $this->commandDefinitionsFromJson($payload);
        $commands = [];

        foreach ($definitions as $definition) {
            if (is_array($definition)) {
                $commands[] = $this->normalizeCommand($definition, [$name => $content], 'json');
            }
        }

        $commands = array_values(array_filter($commands));

        if ($commands === []) {
            throw ValidationException::withMessages(['template_zip' => 'Template JSON export must contain at least one valid command.']);
        }

        return [
            'commands' => $commands,
            'summary' => [
                'detected' => count($commands),
                'imported' => count($commands),
                'skipped' => 0,
                'skipped_duplicates' => [],
                'errors' => [],
                'source' => 'json',
            ],
        ];
    }

    private function commandDefinitionsFromJson(array $payload): array
    {
        foreach ([
            $payload['commands'] ?? null,
            $payload['template']['commands'] ?? null,
            $payload['bot']['commands'] ?? null,
            $payload['data']['commands'] ?? null,
        ] as $commands) {
            if (is_array($commands)) {
                return $commands;
            }
        }

        return array_key_exists('command_name', $payload) ? [$payload] : [];
    }

    public function replaceTemplateCommands(BotTemplate $template, array $parsed): void
    {
        $template->commands()->delete();
        $seen = [];
        $imported = 0;
        $skippedDuplicates = [];

        foreach ($parsed['commands'] as $index => $command) {
            if (isset($seen[$command['command_name']])) {
                $skippedDuplicates[] = $command['command_name'];
                continue;
            }

            $seen[$command['command_name']] = true;
            $template->commands()->create([
                ...$command,
                'sort_order' => $index,
            ]);
            $imported++;
        }

        $summary = [
            ...($parsed['summary'] ?? []),
            'imported' => $imported,
            'skipped' => count($skippedDuplicates),
            'skipped_duplicates' => $skippedDuplicates,
        ];

        $metadata = $template->metadata ?: [];
        $metadata['zip_parse'] = $summary;

        $template->forceFill([
            'commands_count' => $template->commands()->count(),
            'metadata' => $metadata,
        ])->save();
    }

    private function readSafeFiles(ZipArchive $zip): array
    {
        $files = [];
        $totalSize = 0;
        $maxBytes = (int) config('templates.zip_max_kb', 51200) * 1024;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = str_replace('\\', '/', (string) ($stat['name'] ?? ''));

            if ($name === '' || str_ends_with($name, '/')) {
                continue;
            }

            $this->assertSafePath($name);
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (in_array($extension, self::BLOCKED_EXTENSIONS, true) || ! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                throw ValidationException::withMessages(['template_zip' => 'Template ZIP contains unsupported file types.']);
            }

            $totalSize += (int) ($stat['size'] ?? 0);

            if ($totalSize > $maxBytes) {
                throw ValidationException::withMessages(['template_zip' => 'Template ZIP contents are too large.']);
            }

            $content = $zip->getFromIndex($i);

            if ($content === false) {
                throw ValidationException::withMessages(['template_zip' => 'Template ZIP contains unreadable files.']);
            }

            $files[$name] = $content;
        }

        return $files;
    }

    private function commandsFromFiles(array $files): array
    {
        $commands = [];

        if (isset($files['template.json'])) {
            $manifest = $this->decodeJson($files['template.json'], 'template.json', 'Template ZIP');

            foreach (($manifest['commands'] ?? []) as $definition) {
                if (is_array($definition)) {
                    $commands[] = $this->normalizeCommand($definition, $files);
                }
            }
        }

        foreach ($files as $name => $content) {
            if (! str_starts_with($name, 'commands/') || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'json') {
                continue;
            }

            $commands[] = $this->normalizeCommand($this->decodeJson($content, $name, 'Template ZIP'), $files);
        }

        return array_values(array_filter($commands));
    }

    private function normalizeCommand(array $definition, array $files, string $source = 'zip'): ?array
    {
        $triggerType = $this->triggerTypeFromDefinition($definition);
        $commandName = BotTemplateImporter::validateCommandName($this->commandNameFromDefinition($definition));

        if (! $commandName) {
            return null;
        }

        $code = $definition['code'] ?? null;
        $file = isset($definition['file']) ? str_replace('\\', '/', (string) $definition['file']) : null;

        if ($file) {
            $this->assertSafePath($file);

            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'js' && isset($files[$file])) {
                $code = $files[$file];
            }
        }

        $responseText = $definition['response_text'] ?? null;

        if (! filled($code) && ! filled($responseText)) {
            return null;
        }

        $status = in_array($definition['status'] ?? 'active', ['active', 'paused', 'disabled'], true)
            ? $definition['status']
            : 'active';

        return [
            'command_name' => $commandName,
            'trigger_type' => $triggerType,
            'description' => Str::limit((string) ($definition['description'] ?? ''), 500, ''),
            'code' => filled($code) ? (string) $code : null,
            'response_text' => filled($responseText) ? (string) $responseText : null,
            'aliases' => $this->normalizeAliases($definition['aliases'] ?? []),
            'folder' => filled($definition['folder'] ?? null) ? Str::limit((string) $definition['folder'], 100, '') : null,
            'status' => $status,
            'runtime' => 'node',
            'language' => 'javascript',
            'metadata' => ['source' => $source],
        ];
    }

    private function normalizeAliases(mixed $aliases): ?array
    {
        if (! is_array($aliases)) {
            return null;
        }

        $normalized = collect($aliases)
            ->map(fn (mixed $alias) => BotTemplateImporter::validateCommandName(is_string($alias) ? $alias : null))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $normalized === [] ? null : $normalized;
    }

    private function commandNameFromDefinition(array $definition): ?string
    {
        foreach (['command_name', 'trigger', 'callback_data', 'handler', 'name', 'display_name'] as $field) {
            if (filled($definition[$field] ?? null)) {
                return (string) $definition[$field];
            }
        }

        $type = (string) ($definition['trigger_type'] ?? $definition['type'] ?? '');

        return match ($type) {
            'direct_message', 'message', 'dm' => 'direct_message',
            'callback', 'callback_query' => filled($definition['callback'] ?? null) ? (string) $definition['callback'] : 'callback_query',
            'menu' => filled($definition['menu_key'] ?? null) ? (string) $definition['menu_key'] : 'menu',
            default => null,
        };
    }

    private function triggerTypeFromDefinition(array $definition): ?string
    {
        $type = strtolower((string) ($definition['trigger_type'] ?? $definition['type'] ?? ''));

        return match ($type) {
            'direct_message', 'message', 'dm' => 'direct_message',
            'callback', 'callback_query', 'menu' => 'callback',
            'slash', 'command' => 'slash',
            'text' => 'text',
            default => null,
        };
    }

    private function decodeJson(string $content, string $name, string $context): array
    {
        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages(['template_zip' => "{$context} has invalid JSON in {$name}."]);
        }

        return $decoded;
    }

    private function assertSafePath(string $name): void
    {
        if (
            str_starts_with($name, '/')
            || preg_match('/^[A-Za-z]:\//', $name)
            || str_contains($name, '../')
            || str_contains($name, '/..')
            || str_starts_with(basename($name), '.')
        ) {
            throw ValidationException::withMessages(['template_zip' => 'Template ZIP contains unsafe paths.']);
        }
    }
}
