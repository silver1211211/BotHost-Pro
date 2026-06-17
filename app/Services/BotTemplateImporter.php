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
use Illuminate\Validation\ValidationException;
use Throwable;

class BotTemplateImporter
{
    public function conflicts(Bot $bot, BotTemplate $template): array
    {
        $existing = $bot->commands()->pluck('command_name')->all();

        return $template->commands()
            ->get()
            ->reject(fn (BotTemplateCommand $command) => $command->isDirectMessageHandler())
            ->map(fn (BotTemplateCommand $command) => self::validateCommandName($command->command_name))
            ->toBase()
            ->filter()
            ->intersect($existing)
            ->values()
            ->all();
    }

    public function import(Bot $bot, BotTemplate $template, User $user, string $conflictStrategy = 'skip'): BotTemplateImport
    {
        if (! $template->canBeImportedBy($user)) {
            throw ValidationException::withMessages([
                'template' => 'You must unlock or purchase this template before importing.',
            ]);
        }

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
                    if ($templateCommand->isDirectMessageHandler()) {
                        $this->upsertDirectMessageHandler($bot, $template, $templateCommand, $purchase?->id);
                        $imported++;
                        $summary['imported'][] = BotTemplateCommand::DIRECT_MESSAGE_LABEL;
                        continue;
                    }

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

                    if ($exists && $conflictStrategy === 'replace') {
                        $bot->commands()->where('command_name', $finalName)->get()->each->forceDelete();
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
        return $bot->commands()->where('command_name', $commandName)->exists();
    }

    private function commandConflictStatus(Bot $bot, string $commandName): ?string
    {
        $command = $bot->commands()
            ->where('command_name', $commandName)
            ->first();

        return $command ? 'active' : null;
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
        $triggerType = $templateCommand->effectiveTriggerType();
        $payload = [
            'bot_id' => $bot->id,
            'command_name' => $commandName,
            'trigger_type' => in_array($triggerType, BotCommand::TRIGGER_TYPES, true) ? $triggerType : null,
            'status' => $templateCommand->status === 'active' ? 'active' : 'inactive',
        ];

        $columns = Schema::getColumnListing('bot_commands');

        foreach ([
            'code' => $templateCommand->code,
            'display_name' => $triggerType === 'direct_message' ? BotTemplateCommand::DIRECT_MESSAGE_LABEL : $commandName,
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

    private function upsertDirectMessageHandler(Bot $bot, BotTemplate $template, BotTemplateCommand $templateCommand, ?int $purchaseId): BotCommand
    {
        $existing = $bot->commands()
            ->where('trigger_type', 'direct_message')
            ->first();

        if (! $existing) {
            $existing = $bot->commands()
                ->get()
                ->first(fn (BotCommand $command) => BotCommand::isDirectMessageMarker($command->command_name));
        }

        $commandName = $existing?->command_name ?: $this->newDirectMessageCommandName();
        $payload = $this->botCommandPayload($bot, $template, $templateCommand, $commandName, $purchaseId);
        $payload['trigger_type'] = 'direct_message';
        $payload['display_name'] = BotTemplateCommand::DIRECT_MESSAGE_LABEL;

        if ($existing) {
            $existing->forceFill($payload)->save();

            return $existing;
        }

        return BotCommand::create($payload);
    }

    private function newDirectMessageCommandName(): string
    {
        return BotCommand::DIRECT_MESSAGE_COMMAND_PREFIX.Str::lower(Str::random(10));
    }
}
