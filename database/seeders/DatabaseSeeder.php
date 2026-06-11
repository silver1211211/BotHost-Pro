<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Platform Admin',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('1234'),
            'role' => 'admin',
            'subscription_plan' => 'business',
            'ai_requests_remaining' => 500,
        ]);

        User::factory()->create([
            'name' => 'Demo User',
            'username' => 'demo',
            'email' => 'demo@example.com',
        ]);

        User::factory(8)->create();

        $this->call(PlanSeeder::class);
    }
}
