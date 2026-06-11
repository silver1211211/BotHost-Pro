<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AuditLogService
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'bot_token',
        'telegram_token',
        'api_key',
        'secret',
        'webhook_secret',
        'authorization',
        'bearer',
        'private_key',
        'app_key',
        'session',
        'remember_token',
        'token_hash',
    ];

    public function log(
        string $category,
        string $action,
        ?string $description = null,
        array $metadata = [],
        ?User $actor = null,
        string $status = 'success',
        mixed $target = null,
        ?int $legacyTargetId = null,
    ): void {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        try {
            $request = request();
            [$targetType, $targetId] = $this->targetParts($target, $legacyTargetId);

            AuditLog::create([
                'actor_id' => $actor?->id ?? $request?->user()?->id,
                'actor_type' => 'user',
                'target_type' => $targetType,
                'target_id' => $targetId,
                'category' => $category,
                'action' => $action,
                'description' => $description,
                'status' => $status,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'metadata' => $this->maskSecrets($metadata),
            ]);
        } catch (Throwable) {
            // Audit logging must never break the user-facing flow.
        }
    }

    public function safeMetadata(?array $metadata): array
    {
        return $this->maskSecrets($metadata ?? []);
    }

    public function maskSecrets(array $metadata): array
    {
        foreach ($metadata as $key => $value) {
            $normalized = strtolower((string) $key);

            if ($this->isSensitiveKey($normalized)) {
                $metadata[$key] = $this->maskedValueFor($normalized, $value);
                continue;
            }

            if (is_array($value)) {
                $metadata[$key] = $this->maskSecrets($value);
                continue;
            }

            if (is_string($value) && strlen($value) > 2000) {
                $metadata[$key] = Str::limit($value, 2000, '...[truncated]');
            }
        }

        return $metadata;
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if ($key === $sensitive || str_contains($key, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    private function maskedValueFor(string $key, mixed $value): string
    {
        if (str_contains($key, 'password')
            || str_contains($key, 'authorization')
            || str_contains($key, 'bearer')
            || str_contains($key, 'session')
            || str_contains($key, 'remember_token')
            || str_contains($key, 'private_key')
            || str_contains($key, 'app_key')
            || str_contains($key, 'token_hash')) {
            return '[hidden]';
        }

        if (! is_scalar($value)) {
            return '[hidden]';
        }

        $string = (string) $value;

        if ($string === '') {
            return '[hidden]';
        }

        if ((str_contains($key, 'bot_token') || str_contains($key, 'telegram_token') || $key === 'token')
            && str_contains($string, ':')) {
            [$prefix] = explode(':', $string, 2);

            return $prefix.':********';
        }

        if (strlen($string) <= 4) {
            return '********';
        }

        return '******'.substr($string, -4);
    }

    private function targetParts(mixed $target, ?int $legacyTargetId): array
    {
        if ($target instanceof Model) {
            return [$target::class, (int) $target->getKey()];
        }

        if (is_string($target) && $legacyTargetId !== null) {
            return [$target, $legacyTargetId];
        }

        return [null, null];
    }
}
