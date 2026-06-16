<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id',
    'name',
    'label',
    'description',
    'helper_type',
    'code',
    'parameters_schema',
    'return_schema',
    'allowed_domains',
    'timeout_ms',
    'permission_level',
    'expose_to_bot_code',
    'show_in_helper_list',
    'is_system',
    'is_protected',
    'status',
    'active_version_id',
    'last_test_status',
    'last_test_error',
    'last_tested_at',
    'requires_runtime_reload',
    'created_by',
    'updated_by',
])]
class RuntimeHelper extends Model
{
    protected function casts(): array
    {
        return [
            'parameters_schema' => 'array',
            'return_schema' => 'array',
            'allowed_domains' => 'array',
            'expose_to_bot_code' => 'boolean',
            'show_in_helper_list' => 'boolean',
            'is_system' => 'boolean',
            'is_protected' => 'boolean',
            'requires_runtime_reload' => 'boolean',
            'timeout_ms' => 'integer',
            'permission_level' => 'integer',
            'last_tested_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RuntimeHelperCategory::class, 'category_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(RuntimeHelperVersion::class, 'helper_id');
    }

    public function tests(): HasMany
    {
        return $this->hasMany(RuntimeHelperTest::class, 'helper_id');
    }

    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(RuntimeHelperVersion::class, 'active_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isDisabled(): bool
    {
        return $this->status === 'disabled';
    }

    public function requiresReload(): bool
    {
        return (bool) $this->requires_runtime_reload;
    }

    public function markReloadRequired(): bool
    {
        return $this->forceFill(['requires_runtime_reload' => true])->save();
    }

    public function clearReloadRequired(): bool
    {
        return $this->forceFill(['requires_runtime_reload' => false])->save();
    }
}
