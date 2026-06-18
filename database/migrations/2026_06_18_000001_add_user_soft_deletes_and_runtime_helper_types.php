<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }

        Schema::create('runtime_helper_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 50)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            ['name' => 'Telegram', 'slug' => 'telegram', 'description' => 'Telegram messaging and callback helpers.', 'sort_order' => 10],
            ['name' => 'Telegram Bridge', 'slug' => 'telegram_bridge', 'description' => 'Runtime Telegram bridge helpers.', 'sort_order' => 20],
            ['name' => 'Data', 'slug' => 'data', 'description' => 'Data lookup and persistence helpers.', 'sort_order' => 30],
            ['name' => 'Payment', 'slug' => 'payment', 'description' => 'Payment and invoice helpers.', 'sort_order' => 40],
            ['name' => 'Storage', 'slug' => 'storage', 'description' => 'Bot and user storage helpers.', 'sort_order' => 50],
            ['name' => 'Formatting', 'slug' => 'formatting', 'description' => 'Text, number, and display formatting helpers.', 'sort_order' => 60],
            ['name' => 'External API', 'slug' => 'external_api', 'description' => 'External HTTP/API integration helpers.', 'sort_order' => 70],
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Admin workflow helpers.', 'sort_order' => 80],
            ['name' => 'Utility', 'slug' => 'utility', 'description' => 'General-purpose utility helpers.', 'sort_order' => 90],
            ['name' => 'Validation', 'slug' => 'validation', 'description' => 'Validation helpers.', 'sort_order' => 100],
            ['name' => 'Security', 'slug' => 'security', 'description' => 'Security and verification helpers.', 'sort_order' => 110],
            ['name' => 'Keyboard', 'slug' => 'keyboard', 'description' => 'Keyboard and button helpers.', 'sort_order' => 120],
            ['name' => 'FaucetPay', 'slug' => 'faucetpay', 'description' => 'FaucetPay integration helpers.', 'sort_order' => 130],
        ];

        foreach ($defaults as $type) {
            DB::table('runtime_helper_types')->insert([
                ...$type,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (['runtime_helper_categories', 'runtime_helpers'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'helper_type')) {
                continue;
            }

            DB::table($table)
                ->select('helper_type')
                ->whereNotNull('helper_type')
                ->distinct()
                ->orderBy('helper_type')
                ->pluck('helper_type')
                ->filter()
                ->each(function (string $slug) use ($now): void {
                    $slug = strtolower(trim($slug));
                    if ($slug === '' || ! preg_match('/^[a-z][a-z0-9_]*$/', $slug)) {
                        return;
                    }

                    DB::table('runtime_helper_types')->updateOrInsert(
                        ['slug' => $slug],
                        [
                            'name' => str($slug)->replace('_', ' ')->title()->toString(),
                            'description' => 'Migrated from existing helper type usage.',
                            'is_active' => true,
                            'sort_order' => 500,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ],
                    );
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('runtime_helper_types');

        if (Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
