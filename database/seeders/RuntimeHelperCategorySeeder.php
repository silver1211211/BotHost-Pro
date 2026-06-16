<?php

namespace Database\Seeders;

use App\Models\RuntimeHelperCategory;
use Illuminate\Database\Seeder;

class RuntimeHelperCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Telegram',
                'slug' => 'telegram',
                'helper_type' => 'telegram_bridge',
                'allowed_domains' => [],
                'default_timeout_ms' => 10000,
                'permission_level' => 0,
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Storage',
                'slug' => 'storage',
                'helper_type' => 'storage',
                'allowed_domains' => [],
                'default_timeout_ms' => 2000,
                'permission_level' => 0,
                'sort_order' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Payment',
                'slug' => 'payment',
                'helper_type' => 'payment',
                'allowed_domains' => [],
                'default_timeout_ms' => 10000,
                'permission_level' => 0,
                'sort_order' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'FaucetPay',
                'slug' => 'faucetpay',
                'helper_type' => 'payment',
                'allowed_domains' => ['faucetpay.io', 'api.faucetpay.io'],
                'default_timeout_ms' => 10000,
                'permission_level' => 0,
                'sort_order' => 40,
                'is_active' => true,
            ],
            [
                'name' => 'OxaPay',
                'slug' => 'oxapay',
                'helper_type' => 'payment',
                'allowed_domains' => ['oxapay.com', 'api.oxapay.com'],
                'default_timeout_ms' => 10000,
                'permission_level' => 0,
                'sort_order' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'User Balance',
                'slug' => 'user-balance',
                'helper_type' => 'utility',
                'allowed_domains' => [],
                'default_timeout_ms' => 3000,
                'permission_level' => 0,
                'sort_order' => 60,
                'is_active' => true,
            ],
            [
                'name' => 'Referral',
                'slug' => 'referral',
                'helper_type' => 'utility',
                'allowed_domains' => [],
                'default_timeout_ms' => 3000,
                'permission_level' => 0,
                'sort_order' => 70,
                'is_active' => true,
            ],
            [
                'name' => 'Admin Control',
                'slug' => 'admin-control',
                'helper_type' => 'utility',
                'allowed_domains' => [],
                'default_timeout_ms' => 3000,
                'permission_level' => 1,
                'sort_order' => 80,
                'is_active' => true,
            ],
            [
                'name' => 'Utility',
                'slug' => 'utility',
                'helper_type' => 'utility',
                'allowed_domains' => [],
                'default_timeout_ms' => 5000,
                'permission_level' => 0,
                'sort_order' => 90,
                'is_active' => true,
            ],
            [
                'name' => 'External API',
                'slug' => 'external-api',
                'helper_type' => 'external_api',
                'allowed_domains' => [],
                'default_timeout_ms' => 8000,
                'permission_level' => 0,
                'sort_order' => 100,
                'is_active' => true,
            ],
            [
                'name' => 'UI / Keyboard',
                'slug' => 'ui-keyboard',
                'helper_type' => 'keyboard',
                'allowed_domains' => [],
                'default_timeout_ms' => 2000,
                'permission_level' => 0,
                'sort_order' => 110,
                'is_active' => true,
            ],
            [
                'name' => 'Formatting',
                'slug' => 'formatting',
                'helper_type' => 'formatting',
                'allowed_domains' => [],
                'default_timeout_ms' => 1000,
                'permission_level' => 0,
                'sort_order' => 120,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            RuntimeHelperCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }
    }
}
