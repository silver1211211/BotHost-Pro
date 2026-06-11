<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('plan_limits')
            ->where('key', 'templates_unlocked_per_month')
            ->update([
                'key'  => 'free_templates_unlocked_per_month',
                'name' => 'Free Templates Unlocked Per Month',
            ]);
    }

    public function down(): void
    {
        DB::table('plan_limits')
            ->where('key', 'free_templates_unlocked_per_month')
            ->update([
                'key'  => 'templates_unlocked_per_month',
                'name' => 'Templates Unlocked Per Month',
            ]);
    }
};
