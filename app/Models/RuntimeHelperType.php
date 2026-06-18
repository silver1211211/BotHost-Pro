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
    'is_active',
    'sort_order',
    'created_by',
    'updated_by',
])]
class RuntimeHelperType extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function helpers(): HasMany
    {
        return $this->hasMany(RuntimeHelper::class, 'helper_type', 'slug');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(RuntimeHelperCategory::class, 'helper_type', 'slug');
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
