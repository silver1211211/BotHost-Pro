<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Pro Monthly', 'slug' => 'pro', 'description' => 'For growing bot workspaces.', 'price' => '10.00', 'features' => ['More bot capacity', 'Premium marketplace access'], 'sort_order' => 10],
            ['name' => 'Business Monthly', 'slug' => 'business', 'description' => 'For high-volume teams and serious bot operations.', 'price' => '29.00', 'features' => ['Business marketplace access', 'Higher operating limits'], 'sort_order' => 20],
        ] as $plan) {
            SubscriptionPlan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                [
                    ...$plan,
                    'currency' => 'USD',
                    'billing_period' => 'monthly',
                    'status' => 'active',
                ],
            );
        }
    }
}
