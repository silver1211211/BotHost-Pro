<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'subscription_plan_id',
    'key',
    'name',
    'value',
    'unit',
    'is_unlimited',
    'visible_on_upgrade',
    'description',
    'sort_order',
])]
class PlanLimit extends Model
{
    protected function casts(): array
    {
        return [
            'is_unlimited' => 'boolean',
            'visible_on_upgrade' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function displayValue(): string
    {
        if ($this->is_unlimited) {
            return 'Unlimited';
        }

        $n    = (int) ($this->value ?? 0);
        $unit = trim((string) ($this->unit ?? ''));

        if ($unit === '') {
            return number_format($n);
        }

        // Singular/plural unit
        $singular = rtrim($unit, 's');
        $label    = $n === 1 ? $singular : $unit;

        return number_format($n).' '.$label;
    }
}
