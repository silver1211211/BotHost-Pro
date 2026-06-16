<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminPassword = env('SEED_ADMIN_PASSWORD');

        if (blank($adminPassword)) {
            throw new RuntimeException('SEED_ADMIN_PASSWORD must be set before seeding the admin account.');
        }

        User::firstOrCreate(['email' => env('SEED_ADMIN_EMAIL', 'admin@example.com')], [
            'name' => 'Platform Admin',
            'username' => env('SEED_ADMIN_USERNAME', 'admin'),
            'password' => Hash::make($adminPassword),
            'role' => 'admin',
            'status' => 'active',
            'subscription_plan' => 'business',
            'ai_requests_remaining' => 500,
        ]);

        User::firstOrCreate(['email' => 'demo@example.com'], [
            'name' => 'Demo User',
            'username' => 'demo',
            'password' => Hash::make('password'),
            'role' => 'user',
            'status' => 'active',
            'subscription_plan' => 'free',
            'ai_requests_remaining' => 10,
        ]);

        User::factory(8)->create();

        $this->call([
            PlanSeeder::class,
            RuntimeHelperCategorySeeder::class,
        ]);
    }
}
