<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'description',
    'helper_type',
    'allowed_domains',
    'default_timeout_ms',
    'permission_level',
    'sort_order',
    'is_active',
    'created_by',
    'updated_by',
])]
class RuntimeHelperCategory extends Model
{
    protected function casts(): array
    {
        return [
            'allowed_domains' => 'array',
            'is_active' => 'boolean',
            'default_timeout_ms' => 'integer',
            'permission_level' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function helpers(): HasMany
    {
        return $this->hasMany(RuntimeHelper::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
