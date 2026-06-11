<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotCommand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class CommandRuntimeCacheService
{
    public function __construct(private readonly RuntimeSettingsService $settings) {}

    public function findMatchingCommand(Bot $bot, string $text, string $trigger): ?BotCommand
    {
        return $this->matchCommand($bot, $text, $trigger);
    }

    public function findMatchingCommandForUpdate(Bot $bot, string $text, string $trigger, ?string $callbackData = null, bool $includeDirectMessageHandler = true): ?BotCommand
    {
        return $this->matchCommand($bot, $text, $trigger, $callbackData, $includeDirectMessageHandler);
    }

    public function findDirectMessageHandler(Bot $bot): ?BotCommand
    {
        $handler = $this->directMessageHandler($this->activeCommands($bot));

        return $handler ? $this->hydrateCommand($handler) : null;
    }

    private function matchCommand(Bot $bot, string $text, string $trigger, ?string $callbackData = null, bool $includeDirectMessageHandler = true): ?BotCommand
    {
        $started = microtime(true);
        $commands = $this->activeCommands($bot);
        $safe = fn (?string $value): ?string => is_string($value) && $value !== ''
            ? str($value)->limit(80, '')->toString()
            : null;

        Log::debug('[BotHost] command_match_start', [
            'bot_id' => $bot->id,
            'text_preview' => $safe($text),
            'callback_data_preview' => $safe($callbackData),
            'trigger' => $safe($trigger),
        ]);

        if ($callbackData !== null && $callbackData !== '') {
            // If callback_data looks like a slash command, route to the matching slash command.
            if (str_starts_with(trim($callbackData), '/')) {
                $slashParts = preg_split('/\s+/u', trim($callbackData)) ?: [];
                $slashTrigger = $slashParts[0] ?? '';
                $slashArgs = array_values(array_filter(array_slice($slashParts, 1), fn ($arg) => $arg !== ''));
                $slashCandidates = array_values(array_unique(array_filter([$trigger, $slashTrigger], fn ($value) => is_string($value) && $value !== '')));

                Log::info('[BotHost] callback_command_detected', [
                    'bot_id' => $bot->id,
                    'callback_data' => $safe($callbackData),
                    'command_name' => $safe($slashCandidates[0] ?? $slashTrigger),
                    'args' => $slashArgs,
                ]);

                if ($slashCandidates !== []) {
                    $slashMatch = $this->firstMatch($commands, $slashCandidates, ['slash'], allowAliases: true);

                    if ($slashMatch) {
                        Log::info('[BotHost] callback_command_match_found', [
                            'bot_id' => $bot->id,
                            'command_id' => $slashMatch['id'] ?? null,
                            'callback_data' => $safe($callbackData),
                            'command_name' => $slashMatch['command_name'] ?? null,
                            'args' => $slashArgs,
                        ]);

                        return $this->hydrateCommand($slashMatch);
                    }
                }

                Log::info('[BotHost] callback_command_match_not_found', [
                    'bot_id' => $bot->id,
                    'callback_data' => $safe($callbackData),
                    'command_name' => $safe($slashCandidates[0] ?? $slashTrigger),
                    'args' => $slashArgs,
                ]);
            }

            $callback = $this->firstMatch($commands, [$callbackData], ['callback', 'text'], allowAliases: true);

            if ($callback) {
                Log::debug('[BotHost] command_callback_match_found', [
                    'bot_id' => $bot->id,
                    'command_id' => $callback['id'] ?? null,
                    'trigger_type' => $this->triggerType($callback),
                    'callback_data_preview' => $safe($callbackData),
                    'matched_command' => $callback['command_name'] ?? null,
                ]);

                return $this->hydrateCommand($callback);
            }

            Log::debug('[BotHost] command_not_matched_checking_direct_message_handler', [
                'bot_id' => $bot->id,
                'callback_data_preview' => $safe($callbackData),
            ]);

            return null;
        }

        $trimmed = trim($text);

        if (str_starts_with($trimmed, '/')) {
            $slash = $this->firstMatch($commands, [$trigger], ['slash'], allowAliases: true);

            if ($slash) {
                Log::debug('[BotHost] command_exact_match_found', [
                    'bot_id' => $bot->id,
                    'command_id' => $slash['id'] ?? null,
                    'trigger_type' => $this->triggerType($slash),
                    'text_preview' => $safe($text),
                    'matched_command' => $slash['command_name'] ?? null,
                ]);

                return $this->hydrateCommand($slash);
            }
        }

        $textMatch = $this->firstMatch($commands, [$text], ['text', 'slash'], allowAliases: true);

        if ($textMatch) {
            Log::debug('[BotHost] command_exact_match_found', [
                'bot_id' => $bot->id,
                'command_id' => $textMatch['id'] ?? null,
                'trigger_type' => $this->triggerType($textMatch),
                'text_preview' => $safe($text),
                'matched_command' => $textMatch['command_name'] ?? null,
            ]);

            return $this->hydrateCommand($textMatch);
        }

        Log::debug('[BotHost] command_not_matched_checking_direct_message_handler', [
            'bot_id' => $bot->id,
            'text_preview' => $safe($text),
        ]);

        if (! $includeDirectMessageHandler) {
            Log::debug('[BotHost] direct_message_handler_deferred', [
                'bot_id' => $bot->id,
                'text_preview' => $safe($text),
            ]);

            return null;
        }

        $handler = $this->directMessageHandler($commands);

        if ($handler) {
            Log::debug('[BotHost] direct_message_handler_found', [
                'bot_id' => $bot->id,
                'command_id' => $handler['id'] ?? null,
                'trigger_type' => $this->triggerType($handler),
                'text_preview' => $safe($text),
            ]);

            return $this->hydrateCommand($handler);
        }

        Log::debug('[BotHost] direct_message_handler_not_found', [
            'bot_id' => $bot->id,
            'text_preview' => $safe($text),
        ]);

        return null;
    }

    public function clearBot(Bot|int $bot): void
    {
        $botId = $bot instanceof Bot ? $bot->id : $bot;

        foreach (["bot:{$botId}:command_triggers", "bot:{$botId}:runtime_config"] as $key) {
            try {
                Cache::forget($key);
            } catch (Throwable $exception) {
                if ($this->settings->boolean('log_redis_errors', true)) {
                    Log::warning('Failed to clear bot runtime cache.', [
                        'bot_id' => $botId,
                        'cache_key' => $key,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }
    }

    private function activeCommands(Bot $bot): array
    {
        $key = "bot:{$bot->id}:command_triggers";

        try {
            return Cache::remember($key, 300, fn () => $this->loadActiveCommands($bot));
        } catch (Throwable $exception) {
            if ($this->settings->boolean('log_redis_errors', true)) {
                Log::warning('Command cache unavailable; falling back to database.', [
                    'bot_id' => $bot->id,
                    'error' => $exception->getMessage(),
                ]);
            }

            return $this->loadActiveCommands($bot);
        }
    }

    private function loadActiveCommands(Bot $bot): array
    {
        $columns = [
            'id',
            'bot_id',
            'command_name',
            'display_name',
            'code',
            'response_text',
            'response_type',
            'status',
            'aliases',
            'last_used_at',
            'last_error_at',
            'execution_count',
            'error_count',
            'created_at',
            'updated_at',
        ];

        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('bot_commands', 'trigger_type')) {
                $columns[] = 'trigger_type';
            }
        } catch (Throwable) {
            //
        }

        return BotCommand::query()
            ->where('bot_id', $bot->id)
            ->where('status', 'active')
            ->get($columns)
            ->map(fn (BotCommand $command) => $command->toArray())
            ->all();
    }

    private function firstMatch(array $commands, array $candidates, array $triggerTypes, bool $allowAliases): ?array
    {
        $candidates = array_values(array_unique(array_filter($candidates, fn ($value) => is_string($value) && $value !== '')));

        foreach ($candidates as $candidate) {
            foreach ($commands as $command) {
                if (! in_array($this->triggerType($command), $triggerTypes, true)) {
                    continue;
                }

                if (($command['command_name'] ?? null) === $candidate) {
                    return $command;
                }
            }
        }

        if (! $allowAliases) {
            return null;
        }

        foreach ($candidates as $candidate) {
            foreach ($commands as $command) {
                if (! in_array($this->triggerType($command), $triggerTypes, true)) {
                    continue;
                }

                $aliases = is_array($command['aliases'] ?? null) ? $command['aliases'] : [];

                if (in_array($candidate, $aliases, true)) {
                    return $command;
                }
            }
        }

        return null;
    }

    private function directMessageHandler(array $commands): ?array
    {
        foreach ($commands as $command) {
            if ($this->triggerType($command) === 'direct_message') {
                return $command;
            }
        }

        return null;
    }

    private function triggerType(array $command): string
    {
        $type = $command['trigger_type'] ?? null;

        if (in_array($type, BotCommand::TRIGGER_TYPES, true)) {
            return $type;
        }

        return str_starts_with((string) ($command['command_name'] ?? ''), '/') ? 'slash' : 'text';
    }

    private function hydrateCommand(array $attributes): BotCommand
    {
        $command = new BotCommand();
        $command->setRawAttributes($attributes, true);
        $command->exists = true;

        return $command;
    }
}
