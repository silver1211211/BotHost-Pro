<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bot_settings')) {
            return;
        }

        Schema::table('bot_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('bot_settings', 'custom_webhook_url')) {
                $table->string('custom_webhook_url', 500)->nullable()->after('user_webhook_secret');
            }

            if (! Schema::hasColumn('bot_settings', 'custom_webhook_events')) {
                $table->json('custom_webhook_events')->nullable()->after('custom_webhook_url');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bot_settings')) {
            return;
        }

        Schema::table('bot_settings', function (Blueprint $table) {
            foreach (['custom_webhook_events', 'custom_webhook_url'] as $column) {
                if (Schema::hasColumn('bot_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
