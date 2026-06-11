<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectSetting;
use App\Models\Template;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ProjectWorkspaceService
{
    private const DISK = 'project_workspaces';

    public const DEFAULT_FILES = [
        'bot.js' => <<<'JS'
const { Telegraf } = require('telegraf');
const config = require('./config');

const bot = new Telegraf(config.botToken);

bot.start((ctx) => ctx.reply('BotHost starter bot is ready.'));
bot.command('status', (ctx) => ctx.reply('Workspace configured. Runtime comes later.'));

module.exports = bot;
JS,
        'config.js' => <<<'JS'
module.exports = {
  botToken: process.env.BOT_TOKEN || '',
  adminId: process.env.ADMIN_ID || '',
};
JS,
        'package.json' => <<<'JSON'
{
  "name": "bothost-project",
  "version": "1.0.0",
  "private": true,
  "main": "bot.js",
  "dependencies": {
    "telegraf": "^4.16.3"
  }
}
JSON,
        '.env' => <<<'ENV'
BOT_TOKEN=
ADMIN_ID=
OXAPAY_KEY=
ENV,
    ];

    public function ensureWorkspace(Project $project, ?Template $template = null): void
    {
        Storage::disk(self::DISK)->makeDirectory($this->projectDirectory($project));

        if (! $project->files()->exists()) {
            $this->createStarterFiles($project, $template);
        }

        ProjectSetting::firstOrCreate(['project_id' => $project->id]);
    }

    public function createStarterFiles(Project $project, ?Template $template = null): void
    {
        $files = $template?->files ?: self::DEFAULT_FILES;

        foreach ($files as $path => $content) {
            $this->createOrUpdateFile($project, $path, $content);
        }
    }

    public function createFile(Project $project, string $path, string $content = ''): ProjectFile
    {
        $path = $this->sanitizeRelativePath($path);

        if ($project->files()->where('relative_path', $path)->exists()) {
            throw new InvalidArgumentException('A file with this path already exists.');
        }

        return $this->createOrUpdateFile($project, $path, $content);
    }

    public function createOrUpdateFile(Project $project, string $path, string $content): ProjectFile
    {
        $path = $this->sanitizeRelativePath($path);
        $fullPath = $this->storagePath($project, $path);

        Storage::disk(self::DISK)->put($fullPath, $content);

        return ProjectFile::updateOrCreate(
            ['project_id' => $project->id, 'relative_path' => $path],
            [
                'name' => basename($path),
                'mime_type' => $this->mimeType($path),
                'size' => strlen($content),
            ],
        );
    }

    public function readFile(Project $project, ProjectFile $file): string
    {
        $this->assertFileBelongsToProject($project, $file);

        return Storage::disk(self::DISK)->get($this->storagePath($project, $file->relative_path));
    }

    public function saveFile(Project $project, ProjectFile $file, string $content): ProjectFile
    {
        $this->assertFileBelongsToProject($project, $file);

        return $this->createOrUpdateFile($project, $file->relative_path, $content);
    }

    public function renameFile(Project $project, ProjectFile $file, string $path): ProjectFile
    {
        $this->assertFileBelongsToProject($project, $file);
        $path = $this->sanitizeRelativePath($path);

        if ($project->files()->where('relative_path', $path)->whereKeyNot($file->id)->exists()) {
            throw new InvalidArgumentException('A file with this path already exists.');
        }

        $oldPath = $this->storagePath($project, $file->relative_path);
        $newPath = $this->storagePath($project, $path);

        Storage::disk(self::DISK)->move($oldPath, $newPath);

        $file->update([
            'name' => basename($path),
            'relative_path' => $path,
            'mime_type' => $this->mimeType($path),
        ]);

        return $file->refresh();
    }

    public function deleteFile(Project $project, ProjectFile $file): void
    {
        $this->assertFileBelongsToProject($project, $file);

        Storage::disk(self::DISK)->delete($this->storagePath($project, $file->relative_path));
        $file->delete();
    }

    public function projectDirectory(Project $project): string
    {
        return 'projects/'.$project->id;
    }

    public function sanitizeRelativePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');

        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/')) {
            throw new InvalidArgumentException('Invalid file path.');
        }

        if (! preg_match('/^[A-Za-z0-9._\/-]+$/', $path)) {
            throw new InvalidArgumentException('File names may only contain letters, numbers, dots, dashes, underscores, and folders.');
        }

        if (substr_count($path, '/') > 4 || strlen($path) > 160) {
            throw new InvalidArgumentException('File path is too deep or too long.');
        }

        $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));
        $allowedExact = ['.env'];
        $allowedExtensions = ['js', 'json', 'md', 'txt', 'env'];

        if (! in_array($path, $allowedExact, true) && ! in_array($extension, $allowedExtensions, true)) {
            throw new InvalidArgumentException('This file type is not allowed in the MVP workspace.');
        }

        return $path;
    }

    private function storagePath(Project $project, string $relativePath): string
    {
        return $this->projectDirectory($project).'/'.$this->sanitizeRelativePath($relativePath);
    }

    private function assertFileBelongsToProject(Project $project, ProjectFile $file): void
    {
        abort_unless($file->project_id === $project->id, 403);
    }

    private function mimeType(string $path): string
    {
        return match (Str::lower(pathinfo($path, PATHINFO_EXTENSION))) {
            'js' => 'application/javascript',
            'json' => 'application/json',
            'md' => 'text/markdown',
            default => 'text/plain',
        };
    }
}
