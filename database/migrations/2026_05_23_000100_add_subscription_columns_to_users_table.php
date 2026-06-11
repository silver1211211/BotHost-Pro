<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'subscription_status')) {
                $table->string('subscription_status')->default('active')->after('subscription_plan');
            }

            if (! Schema::hasColumn('users', 'subscription_started_at')) {
                $table->timestamp('subscription_started_at')->nullable()->after('subscription_status');
            }

            if (! Schema::hasColumn('users', 'subscription_expires_at')) {
                $table->timestamp('subscription_expires_at')->nullable()->after('subscription_started_at');
            }

            if (! Schema::hasColumn('users', 'plan_upgraded_at')) {
                $table->timestamp('plan_upgraded_at')->nullable()->after('subscription_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach (['plan_upgraded_at', 'subscription_expires_at', 'subscription_started_at', 'subscription_status'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
