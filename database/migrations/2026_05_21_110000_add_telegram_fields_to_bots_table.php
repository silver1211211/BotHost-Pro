<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bots')) {
            return;
        }

        Schema::table('bots', function (Blueprint $table) {
            if (! Schema::hasColumn('bots', 'telegram_bot_id')) {
                $table->string('telegram_bot_id')->nullable()->after('token_encrypted');
            }

            if (! Schema::hasColumn('bots', 'telegram_username')) {
                $table->string('telegram_username')->nullable()->after('telegram_bot_id');
            }

            if (! Schema::hasColumn('bots', 'telegram_first_name')) {
                $table->string('telegram_first_name')->nullable()->after('telegram_username');
            }

            if (! Schema::hasColumn('bots', 'telegram_can_join_groups')) {
                $table->boolean('telegram_can_join_groups')->nullable()->after('telegram_first_name');
            }

            if (! Schema::hasColumn('bots', 'telegram_can_read_all_group_messages')) {
                $table->boolean('telegram_can_read_all_group_messages')->nullable()->after('telegram_can_join_groups');
            }

            if (! Schema::hasColumn('bots', 'telegram_supports_inline_queries')) {
                $table->boolean('telegram_supports_inline_queries')->nullable()->after('telegram_can_read_all_group_messages');
            }

            if (! Schema::hasColumn('bots', 'token_verified_at')) {
                $table->timestamp('token_verified_at')->nullable()->after('telegram_supports_inline_queries');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bots')) {
            return;
        }

        Schema::table('bots', function (Blueprint $table) {
            foreach ([
                'token_verified_at',
                'telegram_supports_inline_queries',
                'telegram_can_read_all_group_messages',
                'telegram_can_join_groups',
                'telegram_first_name',
                'telegram_username',
                'telegram_bot_id',
            ] as $column) {
                if (Schema::hasColumn('bots', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
