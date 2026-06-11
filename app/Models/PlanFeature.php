<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['key', 'name', 'description', 'category', 'is_active', 'is_visible', 'sort_order'])]
class PlanFeature extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function accessEntries(): HasMany
    {
        return $this->hasMany(PlanFeatureAccess::class);
    }
}
