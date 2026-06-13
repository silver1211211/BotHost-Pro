<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;
use App\Support\PublicCallbackUrl;
use App\Services\BotRuntimeCacheService;
use App\Services\CommandRuntimeCacheService;
use App\Services\DockerRuntimeService;

#[Fillable([
    'user_id',
    'name',
    'slug',
    'token_encrypted',
    'token_hash',
    'status',
    'runtime_mode',
    'runtime_status',
    'container_name',
    'container_status',
    'runtime_http_port',
    'last_runtime_heartbeat_at',
    'last_runtime_error',
    'runtime_started_at',
    'runtime_restarted_at',
    'language',
    'setup_type',
    'template_id',
    'cloned_from_bot_id',
    'source_type',
    'last_active_at',
    'telegram_bot_id',
    'telegram_username',
    'telegram_first_name',
    'telegram_can_join_groups',
    'telegram_can_read_all_group_messages',
    'telegram_supports_inline_queries',
    'token_verified_at',
    'webhook_url',
    'webhook_secret',
    'webhook_set_at',
    'webhook_status',
    'webhook_last_error',
    'last_webhook_update_at',
])]
class Bot extends Model
{
    use SoftDeletes;

    public const STATUSES = ['running', 'paused', 'stopped', 'crashed', 'suspended'];

    public const SETUP_TYPES = ['custom', 'template'];

    protected function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
            'last_runtime_heartbeat_at' => 'datetime',
            'runtime_started_at' => 'datetime',
            'runtime_restarted_at' => 'datetime',
            'telegram_can_join_groups' => 'boolean',
            'telegram_can_read_all_group_messages' => 'boolean',
            'telegram_supports_inline_queries' => 'boolean',
            'token_verified_at' => 'datetime',
            'webhook_set_at' => 'datetime',
            'last_webhook_update_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $clear = function (Bot $bot): void {
            if ($bot->exists && ! $bot->wasRecentlyCreated && ! $bot->wasChanged([
                'token_encrypted',
                'token_hash',
                'status',
                'language',
                'webhook_secret',
                'telegram_username',
                'telegram_first_name',
            ])) {
                return;
            }

            try {
                app(BotRuntimeCacheService::class)->clearBot($bot);
                app(CommandRuntimeCacheService::class)->clearBot($bot);
            } catch (Throwable) {
                // Cache clearing should never block bot saves.
            }
        };

        static::saved($clear);
        static::deleted(function (Bot $bot) use ($clear): void {
            $clear($bot);

            try {
                if ($bot->isForceDeleting()) {
                    app(DockerRuntimeService::class)->removeBotContainer($bot);
                } elseif ($bot->runtime_mode === 'docker') {
                    app(DockerRuntimeService::class)->stopBotContainer($bot);
                }
            } catch (Throwable) {
                // Runtime cleanup should never block bot deletion.
            }
        });

        static::restored(function (Bot $bot) use ($clear): void {
            $clear($bot);

            try {
                if ($bot->status === 'running' && $bot->runtime_mode === 'docker') {
                    app(DockerRuntimeService::class)->startBotContainer($bot);
                }
            } catch (Throwable) {
                // Runtime warmup should never block bot restoration.
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(BotCommand::class)->latest();
    }

    public function setting(): HasOne
    {
        return $this->hasOne(BotSetting::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BotLog::class)->latest('created_at');
    }

    public function commandLogs(): HasMany
    {
        return $this->hasMany(BotCommandLog::class)->latest('created_at');
    }

    public function botUsers(): HasMany
    {
        return $this->hasMany(BotUser::class);
    }

    public function broadcasts(): HasMany
    {
        return $this->hasMany(BotBroadcast::class)->latest('created_at');
    }

    public function broadcastRecipients(): HasMany
    {
        return $this->hasMany(BotBroadcastRecipient::class);
    }

    public function templateImports(): HasMany
    {
        return $this->hasMany(BotTemplateImport::class);
    }

    protected function tokenEncrypted(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (! $value) {
                    return null;
                }

                try {
                    return Crypt::decryptString($value);
                } catch (Throwable) {
                    return null;
                }
            },
            set: fn (?string $value) => filled($value)
                ? [
                    'token_encrypted' => Crypt::encryptString(trim($value)),
                    'token_hash' => self::tokenHash($value),
                ]
                : [
                    'token_encrypted' => null,
                    'token_hash' => null,
                ],
        );
    }

    public static function tokenHash(string $token): string
    {
        return hash('sha256', trim($token));
    }

    public static function tokenInUse(string $token, ?int $ignoreBotId = null): bool
    {
        $token = trim($token);
        $hash = self::tokenHash($token);

        if (self::query()
            ->where('token_hash', $hash)
            ->when($ignoreBotId, fn ($query) => $query->whereKeyNot($ignoreBotId))
            ->exists()) {
            return true;
        }

        self::query()
            ->whereNull('token_hash')
            ->whereNotNull('token_encrypted')
            ->when($ignoreBotId, fn ($query) => $query->whereKeyNot($ignoreBotId))
            ->select(['id', 'token_encrypted'])
            ->cursor()
            ->each(function (Bot $bot) use ($token, $hash): void {
                try {
                    $storedToken = Crypt::decryptString($bot->getRawOriginal('token_encrypted'));
                } catch (Throwable) {
                    return;
                }

                try {
                    $storedToken = trim($storedToken);

                    if (hash_equals($token, $storedToken)) {
                        $bot->forceFill(['token_hash' => $hash])->saveQuietly();
                    } else {
                        $bot->forceFill(['token_hash' => self::tokenHash($storedToken)])->saveQuietly();
                    }
                } catch (Throwable) {
                    return;
                }
            });

        return self::query()
            ->where('token_hash', $hash)
            ->when($ignoreBotId, fn ($query) => $query->whereKeyNot($ignoreBotId))
            ->exists();
    }

    public function maskedToken(): string
    {
        $token = (string) $this->token_encrypted;

        if ($token === '') {
            return 'Not set';
        }

        if (Str::contains($token, ':')) {
            [$prefix, $secret] = explode(':', $token, 2);

            return $prefix.':'.Str::substr($secret, 0, 2).str_repeat('*', 14);
        }

        return Str::substr($token, 0, 4).str_repeat('*', 14);
    }

    public function tokenStatusLabel(): string
    {
        return $this->token_verified_at ? 'Verified' : 'Not verified';
    }

    public function telegramUsernameLabel(): string
    {
        return $this->telegram_username ? '@'.$this->telegram_username : 'Username not verified';
    }

    public function getWebhookEndpointAttribute(): ?string
    {
        if (! $this->webhook_secret) {
            return null;
        }

        return PublicCallbackUrl::to('/telegram/webhook/'.$this->id.'/'.$this->webhook_secret);
    }
}
