<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['subscription_plan_id', 'plan_feature_id', 'enabled', 'visible_on_upgrade', 'label_override'])]
class PlanFeatureAccess extends Model
{
    protected $table = 'plan_feature_access';

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'visible_on_upgrade' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(PlanFeature::class, 'plan_feature_id');
    }
}
