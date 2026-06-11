<?php

namespace Database\Seeders;

use App\Models\PlanFeature;
use App\Models\PlanFeatureAccess;
use App\Models\PlanLimit;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPlans();
        $this->seedFeatures();
        $this->seedFeatureAccess();
        $this->seedLimits();
    }

    private function seedPlans(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Get started with Telegram bot building.',
                'price' => '0.00',
                'sort_order' => 0,
            ],
            [
                'name' => 'Pro Monthly',
                'slug' => 'pro',
                'description' => 'For growing bot workspaces.',
                'price' => '10.00',
                'sort_order' => 10,
            ],
            [
                'name' => 'Business Monthly',
                'slug' => 'business',
                'description' => 'For high-volume teams and serious bot operations.',
                'price' => '29.00',
                'sort_order' => 20,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                array_merge($plan, [
                    'currency' => 'USD',
                    'billing_period' => 'monthly',
                    'status' => 'active',
                ]),
            );
        }
    }

    private function seedFeatures(): void
    {
        $features = [
            ['key' => 'bot_creation',         'name' => 'Bot Creation',             'category' => 'core',       'sort_order' => 1],
            ['key' => 'command_editor',        'name' => 'Command Editor',           'category' => 'core',       'sort_order' => 2],
            ['key' => 'node_runtime',          'name' => 'Node.js Runtime',          'category' => 'core',       'sort_order' => 3],
            ['key' => 'template_marketplace',  'name' => 'Template Marketplace',     'category' => 'templates',  'sort_order' => 4],
            ['key' => 'paid_templates',        'name' => 'Paid Templates',           'category' => 'templates',  'sort_order' => 5],
            ['key' => 'pro_templates',         'name' => 'Pro Templates',            'category' => 'templates',  'sort_order' => 6],
            ['key' => 'business_templates',    'name' => 'Business Templates',       'category' => 'templates',  'sort_order' => 7],
            ['key' => 'broadcasts',            'name' => 'Broadcasts',               'category' => 'messaging',  'sort_order' => 8],
            ['key' => 'advanced_broadcasts',   'name' => 'Advanced Broadcasts',      'category' => 'messaging',  'sort_order' => 9],
            ['key' => 'bot_user_tracking',     'name' => 'Bot User Tracking',        'category' => 'analytics',  'sort_order' => 10],
            ['key' => 'analytics',             'name' => 'Analytics',                'category' => 'analytics',  'sort_order' => 11],
            ['key' => 'error_logs',            'name' => 'Logs & Errors',            'category' => 'analytics',  'sort_order' => 12],
            ['key' => 'export_import',         'name' => 'External Export / Import',  'category' => 'tools',      'sort_order' => 13, 'description' => 'File-based bot export and import only. Does not affect template marketplace.'],
            ['key' => 'custom_webhooks',       'name' => 'Custom Webhooks',          'category' => 'tools',      'sort_order' => 14],
            ['key' => 'priority_support',      'name' => 'Priority Support',         'category' => 'support',    'sort_order' => 15],
            ['key' => 'remove_branding',       'name' => 'Remove Branding',          'category' => 'advanced',   'sort_order' => 16],
            ['key' => 'team_access',           'name' => 'Team Access',              'category' => 'advanced',   'sort_order' => 17],
            ['key' => 'api_access',            'name' => 'API Access',               'category' => 'advanced',   'sort_order' => 18],
            ['key' => 'advanced_security',     'name' => 'Advanced Security',        'category' => 'advanced',   'sort_order' => 19],
            ['key' => 'ai_tools',              'name' => 'AI Tools',                 'category' => 'advanced',   'sort_order' => 20],
        ];

        foreach ($features as $feature) {
            PlanFeature::query()->updateOrCreate(
                ['key' => $feature['key']],
                array_merge($feature, ['is_active' => true, 'is_visible' => true]),
            );
        }
    }

    private function seedFeatureAccess(): void
    {
        $plans = SubscriptionPlan::query()->get()->keyBy('slug');

        // free => [feature_key => enabled]
        $matrix = [
            'free' => [
                'bot_creation'        => true,
                'command_editor'      => true,
                'node_runtime'        => true,
                'template_marketplace'=> true,
                'paid_templates'      => true,
                'pro_templates'       => false,
                'business_templates'  => false,
                'broadcasts'          => true,
                'advanced_broadcasts' => false,
                'bot_user_tracking'   => true,
                'analytics'           => false,
                'error_logs'          => true,
                'export_import'       => false,
                'custom_webhooks'     => false,
                'priority_support'    => false,
                'remove_branding'     => false,
                'team_access'         => false,
                'api_access'          => false,
                'advanced_security'   => false,
                'ai_tools'            => false,
            ],
            'pro' => [
                'bot_creation'        => true,
                'command_editor'      => true,
                'node_runtime'        => true,
                'template_marketplace'=> true,
                'paid_templates'      => true,
                'pro_templates'       => true,
                'business_templates'  => false,
                'broadcasts'          => true,
                'advanced_broadcasts' => false,
                'bot_user_tracking'   => true,
                'analytics'           => true,
                'error_logs'          => true,
                'export_import'       => true,
                'custom_webhooks'     => false,
                'priority_support'    => false,
                'remove_branding'     => false,
                'team_access'         => false,
                'api_access'          => false,
                'advanced_security'   => false,
                'ai_tools'            => false,
            ],
            'business' => [
                'bot_creation'        => true,
                'command_editor'      => true,
                'node_runtime'        => true,
                'template_marketplace'=> true,
                'paid_templates'      => true,
                'pro_templates'       => true,
                'business_templates'  => true,
                'broadcasts'          => true,
                'advanced_broadcasts' => true,
                'bot_user_tracking'   => true,
                'analytics'           => true,
                'error_logs'          => true,
                'export_import'       => true,
                'custom_webhooks'     => true,
                'priority_support'    => true,
                'remove_branding'     => false,
                'team_access'         => false,
                'api_access'          => false,
                'advanced_security'   => true,
                'ai_tools'            => false,
            ],
        ];

        // Visibility overrides: some features are not visible on upgrade card even when enabled
        $hiddenOnUpgrade = ['bot_creation', 'command_editor', 'node_runtime', 'error_logs'];

        $features = PlanFeature::query()->get()->keyBy('key');

        foreach ($matrix as $planSlug => $featureMap) {
            $plan = $plans->get($planSlug);

            if (! $plan) {
                continue;
            }

            foreach ($featureMap as $featureKey => $enabled) {
                $feature = $features->get($featureKey);

                if (! $feature) {
                    continue;
                }

                PlanFeatureAccess::query()->updateOrCreate(
                    [
                        'subscription_plan_id' => $plan->id,
                        'plan_feature_id'      => $feature->id,
                    ],
                    [
                        'enabled'           => $enabled,
                        'visible_on_upgrade' => ! in_array($featureKey, $hiddenOnUpgrade, true),
                    ],
                );
            }
        }
    }

    private function seedLimits(): void
    {
        $plans = SubscriptionPlan::query()->get()->keyBy('slug');

        $definitions = [
            ['key' => 'bots_allowed',                    'name' => 'Bots Allowed',                   'unit' => 'bots',       'sort_order' => 1],
            ['key' => 'commands_per_bot',                'name' => 'Commands Per Bot',               'unit' => 'commands',   'sort_order' => 2],
            ['key' => 'broadcast_recipients_per_send',   'name' => 'Broadcast Recipients Per Send',  'unit' => 'recipients', 'sort_order' => 3],
            ['key' => 'broadcasts_per_month',               'name' => 'Broadcasts Per Month',                'unit' => 'broadcasts', 'sort_order' => 4],
            ['key' => 'free_templates_unlocked_per_month', 'name' => 'Free Templates Unlocked Per Month',  'unit' => 'templates',  'sort_order' => 5],
            ['key' => 'paid_templates_purchase_limit',   'name' => 'Paid Templates',                  'unit' => 'templates',  'sort_order' => 6],
            ['key' => 'storage_mb',                      'name' => 'Storage',                         'unit' => 'MB',         'sort_order' => 7],
            ['key' => 'team_members',                    'name' => 'Team Members',                    'unit' => 'members',    'sort_order' => 8, 'visible' => false],
            ['key' => 'api_requests_per_month',          'name' => 'API Requests Per Month',          'unit' => 'requests',   'sort_order' => 9, 'visible' => false],
            ['key' => 'bot_users_tracked',               'name' => 'Bot Users Tracked',               'unit' => 'users',      'sort_order' => 10, 'visible' => false],
            ['key' => 'logs_retention_days',             'name' => 'Logs Retention',                  'unit' => 'days',       'sort_order' => 11],
        ];

        // [plan_slug][limit_key] => [value, is_unlimited]
        $limitMatrix = [
            'free' => [
                'bots_allowed'                  => ['value' => '1',      'unlimited' => false],
                'commands_per_bot'              => ['value' => '10',     'unlimited' => false],
                'broadcast_recipients_per_send' => ['value' => '20000',  'unlimited' => false],
                'broadcasts_per_month'               => ['value' => '1',   'unlimited' => false],
                'free_templates_unlocked_per_month'  => ['value' => '3',   'unlimited' => false],
                'paid_templates_purchase_limit'      => ['value' => null,  'unlimited' => true],
                'storage_mb'                         => ['value' => '100', 'unlimited' => false],
                'team_members'                  => ['value' => '1',      'unlimited' => false],
                'api_requests_per_month'        => ['value' => '0',      'unlimited' => false],
                'bot_users_tracked'             => ['value' => '1000',   'unlimited' => false],
                'logs_retention_days'           => ['value' => '7',      'unlimited' => false],
            ],
            'pro' => [
                'bots_allowed'                  => ['value' => '10',     'unlimited' => false],
                'commands_per_bot'              => ['value' => '100',    'unlimited' => false],
                'broadcast_recipients_per_send' => ['value' => '100000', 'unlimited' => false],
                'broadcasts_per_month'               => ['value' => '20',   'unlimited' => false],
                'free_templates_unlocked_per_month'  => ['value' => '20',  'unlimited' => false],
                'paid_templates_purchase_limit'      => ['value' => null,  'unlimited' => true],
                'storage_mb'                         => ['value' => '1000','unlimited' => false],
                'team_members'                  => ['value' => '3',      'unlimited' => false],
                'api_requests_per_month'        => ['value' => '10000',  'unlimited' => false],
                'bot_users_tracked'             => ['value' => null,     'unlimited' => true],
                'logs_retention_days'           => ['value' => '30',     'unlimited' => false],
            ],
            'business' => [
                'bots_allowed'                  => ['value' => null, 'unlimited' => true],
                'commands_per_bot'              => ['value' => null, 'unlimited' => true],
                'broadcast_recipients_per_send' => ['value' => null, 'unlimited' => true],
                'broadcasts_per_month'               => ['value' => null, 'unlimited' => true],
                'free_templates_unlocked_per_month'  => ['value' => null, 'unlimited' => true],
                'paid_templates_purchase_limit'      => ['value' => null, 'unlimited' => true],
                'storage_mb'                         => ['value' => null, 'unlimited' => true],
                'team_members'                  => ['value' => null, 'unlimited' => true],
                'api_requests_per_month'        => ['value' => null, 'unlimited' => true],
                'bot_users_tracked'             => ['value' => null, 'unlimited' => true],
                'logs_retention_days'           => ['value' => '365', 'unlimited' => false],
            ],
        ];

        $defsByKey = collect($definitions)->keyBy('key');

        foreach ($limitMatrix as $planSlug => $limits) {
            $plan = $plans->get($planSlug);

            if (! $plan) {
                continue;
            }

            foreach ($limits as $key => $data) {
                $def = $defsByKey->get($key);

                if (! $def) {
                    continue;
                }

                PlanLimit::query()->updateOrCreate(
                    [
                        'subscription_plan_id' => $plan->id,
                        'key'                  => $key,
                    ],
                    [
                        'name'              => $def['name'],
                        'value'             => $data['value'],
                        'unit'              => $def['unit'],
                        'is_unlimited'      => $data['unlimited'],
                        'visible_on_upgrade' => $def['visible'] ?? true,
                        'sort_order'        => $def['sort_order'],
                    ],
                );
            }
        }
    }
}
