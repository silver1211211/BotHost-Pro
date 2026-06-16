<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'helper_id',
    'version_number',
    'code',
    'parameters_schema',
    'return_schema',
    'allowed_domains',
    'timeout_ms',
    'permission_level',
    'change_summary',
    'safety_scan_status',
    'safety_scan_error',
    'syntax_check_status',
    'syntax_check_error',
    'test_status',
    'test_error',
    'status',
    'created_by',
])]
class RuntimeHelperVersion extends Model
{
    protected function casts(): array
    {
        return [
            'parameters_schema' => 'array',
            'return_schema' => 'array',
            'allowed_domains' => 'array',
            'timeout_ms' => 'integer',
            'permission_level' => 'integer',
        ];
    }

    public function helper(): BelongsTo
    {
        return $this->belongsTo(RuntimeHelper::class, 'helper_id');
    }

    public function tests(): HasMany
    {
        return $this->hasMany(RuntimeHelperTest::class, 'version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function passedSafetyScan(): bool
    {
        return $this->safety_scan_status === 'passed';
    }

    public function passedSyntaxCheck(): bool
    {
        return $this->syntax_check_status === 'passed';
    }
}
