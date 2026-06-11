<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'description',
    'price',
    'currency',
    'billing_period',
    'status',
    'features',
    'limits',
    'sort_order',
])]
class SubscriptionPlan extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'features' => 'array',
            'limits' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function featureAccess(): HasMany
    {
        return $this->hasMany(PlanFeatureAccess::class);
    }

    public function planLimits(): HasMany
    {
        return $this->hasMany(PlanLimit::class);
    }

    public function formattedPrice(): string
    {
        if ((float) $this->price === 0.0) {
            return 'Free';
        }

        return ($this->currency ?: 'USD') === 'USD'
            ? '$'.number_format((float) $this->price, 2)
            : ($this->currency ?: 'USD').' '.number_format((float) $this->price, 2);
    }

    public function hasFeature(string $key): bool
    {
        return $this->featureAccess()
            ->whereHas('feature', fn ($q) => $q->where('key', $key)->where('is_active', true))
            ->where('enabled', true)
            ->exists();
    }

    public function getLimit(string $key, mixed $default = null): mixed
    {
        $limit = $this->planLimits()->where('key', $key)->first();

        if (! $limit) {
            return $default;
        }

        if ($limit->is_unlimited) {
            return 'unlimited';
        }

        return $limit->value;
    }

    public function isUnlimited(string $key): bool
    {
        return $this->planLimits()->where('key', $key)->where('is_unlimited', true)->exists();
    }

    /** @return array<int, array{label: string, enabled: bool}> */
    public function displayFeatures(): array
    {
        return $this->featureAccess()
            ->with('feature')
            ->where('enabled', true)
            ->where('visible_on_upgrade', true)
            ->whereHas('feature', fn ($q) => $q->where('is_active', true)->where('is_visible', true))
            ->get()
            ->map(fn (PlanFeatureAccess $a) => [
                'label' => $a->label_override ?: $a->feature->name,
            ])
            ->all();
    }

    /** @return array<int, array{name: string, display: string, unlimited: bool}> */
    public function displayLimits(): array
    {
        return $this->planLimits()
            ->where('visible_on_upgrade', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (PlanLimit $l) => [
                'name'      => $l->name,
                'display'   => $l->displayValue(),
                'unlimited' => (bool) $l->is_unlimited,
            ])
            ->all();
    }
}
