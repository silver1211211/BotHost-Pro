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
            if (! Schema::hasColumn('bot_settings', 'user_webhook_enabled')) {
                $table->boolean('user_webhook_enabled')->default(false)->after('timezone');
            }

            if (! Schema::hasColumn('bot_settings', 'user_webhook_secret')) {
                $table->string('user_webhook_secret', 64)->nullable()->after('user_webhook_enabled');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bot_settings')) {
            return;
        }

        Schema::table('bot_settings', function (Blueprint $table) {
            foreach (['user_webhook_secret', 'user_webhook_enabled'] as $column) {
                if (Schema::hasColumn('bot_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
