<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'helper_id',
    'version_id',
    'test_name',
    'input_payload',
    'expected_output',
    'actual_output',
    'status',
    'error',
    'execution_ms',
    'dry_run',
    'run_by',
    'ran_at',
])]
class RuntimeHelperTest extends Model
{
    protected function casts(): array
    {
        return [
            'input_payload' => 'array',
            'expected_output' => 'array',
            'actual_output' => 'array',
            'dry_run' => 'boolean',
            'execution_ms' => 'integer',
            'ran_at' => 'datetime',
        ];
    }

    public function helper(): BelongsTo
    {
        return $this->belongsTo(RuntimeHelper::class, 'helper_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(RuntimeHelperVersion::class, 'version_id');
    }

    public function runner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'run_by');
    }
}
