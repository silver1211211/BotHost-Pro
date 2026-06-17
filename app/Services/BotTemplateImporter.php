<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotCommand;
use App\Models\BotTemplate;
use App\Models\BotTemplateCommand;
use App\Models\BotTemplateImport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class BotTemplateImporter
{
    public function conflicts(Bot $bot, BotTemplate $template): array
    {
        $existing = $bot->commands()->withTrashed()->pluck('command_name')->all();

        return $template->commands()
            ->pluck('command_name')
            ->map(fn (?string $name) => self::validateCommandName($name))
            ->filter()
            ->intersect($existing)
            ->values()
            ->all();
    }

    public function import(Bot $bot, BotTemplate $template, User $user, string $conflictStrategy = 'skip'): BotTemplateImport
    {
        $conflictStrategy = in_array($conflictStrategy, ['skip', 'rename', 'replace', 'replace_all'], true) ? $conflictStrategy : 'skip';

        return DB::transaction(function () use ($bot, $template, $user, $conflictStrategy): BotTemplateImport {
            $purchase = $template->purchases()
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->first();

            $summary = [
                'imported'    => [],
                'skipped'     => [],
                'renamed'     => [],
                'replaced'    => [],
                'errors'      => [],
                'cleared_all' => false,
            ];

            if ($conflictStrategy === 'replace_all') {
                $bot->commands()->withTrashed()->get()->each->forceDelete();
                $summary['cleared_all'] = true;
            }

            $imported = 0;
            $skipped = 0;

            try {
                $template->loadMissing('commands');

                foreach ($template->commands as $templateCommand) {
                    $commandName = self::validateCommandName($templateCommand->command_name);

                    if ($commandName === null) {
                        $skipped++;
                        $summary['errors'][] = [
                            'command_name' => $templateCommand->command_name,
                            'message' => 'Invalid command name.',
                        ];
                        continue;
                    }

                    $finalName = $commandName;
                    $conflict = $this->commandConflictStatus($bot, $finalName);
                    $exists = $conflict !== null;

                    if ($exists && $conflictStrategy === 'skip') {
                        $skipped++;
                        $summary['skipped'][] = $finalName;
                        continue;
                    }

                    if ($conflict === 'trashed') {
                        $skipped++;
                        $summary['skipped'][] = $finalName;
                        $summary['errors'][] = [
                            'command_name' => $finalName,
                            'message' => 'Command exists in recycle bin. Restore it or permanently delete it before importing this command.',
                        ];
                        continue;
                    }

                    if ($exists && $conflictStrategy === 'replace') {
                        $bot->commands()->where('command_name', $finalName)->delete();
                        $summary['replaced'][] = $finalName;
                    }

                    if ($exists && $conflictStrategy === 'rename') {
                        $finalName = $this->nextAvailableCommandName($bot, $finalName);
                        $summary['renamed'][] = ['from' => $commandName, 'to' => $finalName];
                    }

                    BotCommand::create($this->botCommandPayload($bot, $template, $templateCommand, $finalName, $purchase?->id));
                    $imported++;
                    $summary['imported'][] = $finalName;
                }

                $status = $imported > 0 && $skipped > 0 ? 'partial' : 'completed';
                if ($imported === 0 && $skipped > 0) {
                    $status = 'partial';
                }

                $template->increment('import_count');
                $template->forceFill(['commands_count' => $template->commands()->count()])->save();

                return BotTemplateImport::create([
                    'bot_id' => $bot->id,
                    'bot_template_id' => $template->id,
                    'user_id' => $user->id,
                    'status' => $status,
                    'imported_commands_count' => $imported,
                    'skipped_commands_count' => $skipped,
                    'conflict_strategy' => $conflictStrategy,
                    'summary' => $summary,
                ]);
            } catch (Throwable $exception) {
                $summary['errors'][] = ['message' => 'Template import failed.'];

                return BotTemplateImport::create([
                    'bot_id' => $bot->id,
                    'bot_template_id' => $template->id,
                    'user_id' => $user->id,
                    'status' => 'failed',
                    'imported_commands_count' => $imported,
                    'skipped_commands_count' => $skipped,
                    'conflict_strategy' => $conflictStrategy,
                    'summary' => $summary,
                ]);
            }
        });
    }

    public static function normalizeCommandName(?string $value): ?string
    {
        return self::validateCommandName($value);
    }

    public static function validateCommandName(?string $value): ?string
    {
        $value = (string) $value;

        if ($value === '') {
            return null;
        }

        return mb_strlen($value) <= 64 ? $value : null;
    }

    private function commandExists(Bot $bot, string $commandName): bool
    {
        return $bot->commands()->withTrashed()->where('command_name', $commandName)->exists();
    }

    private function commandConflictStatus(Bot $bot, string $commandName): ?string
    {
        $command = $bot->commands()
            ->withTrashed()
            ->where('command_name', $commandName)
            ->first();

        if (! $command) {
            return null;
        }

        return $command->trashed() ? 'trashed' : 'active';
    }

    private function nextAvailableCommandName(Bot $bot, string $commandName): string
    {
        $base = substr($commandName, 0, 61);

        for ($i = 2; $i < 1000; $i++) {
            $candidate = $base.'_'.$i;

            if (strlen($candidate) <= 64 && ! $this->commandExists($bot, $candidate)) {
                return $candidate;
            }
        }

        return '/imported_'.Str::lower(Str::random(10));
    }

    private function botCommandPayload(Bot $bot, BotTemplate $template, BotTemplateCommand $templateCommand, string $commandName, ?int $purchaseId): array
    {
        $payload = [
            'bot_id' => $bot->id,
            'command_name' => $commandName,
            'trigger_type' => in_array($templateCommand->trigger_type, BotCommand::TRIGGER_TYPES, true) ? $templateCommand->trigger_type : null,
            'status' => $templateCommand->status === 'active' ? 'active' : 'inactive',
        ];

        $columns = Schema::getColumnListing('bot_commands');

        foreach ([
            'code' => $templateCommand->code,
            'display_name' => $templateCommand->trigger_type === 'direct_message' ? ($templateCommand->description ?: 'Direct Message Handler') : $commandName,
            'response_text' => $templateCommand->response_text,
            'aliases' => $templateCommand->aliases,
            'folder' => $templateCommand->folder,
            'source' => 'marketplace',
            'source_template_id' => $template->id,
            'source_template_purchase_id' => $purchaseId,
            'license_locked' => true,
            'duplicate_count_used' => 0,
        ] as $column => $value) {
            if (in_array($column, $columns, true)) {
                $payload[$column] = $value;
            }
        }

        if (in_array('response_type', $columns, true)) {
            $payload['response_type'] = filled($templateCommand->code) ? 'code' : 'text';
        }

        return $payload;
    }
}
