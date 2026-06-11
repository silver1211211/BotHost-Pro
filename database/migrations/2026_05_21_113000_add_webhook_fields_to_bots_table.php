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
            if (! Schema::hasColumn('bots', 'webhook_url')) {
                $table->text('webhook_url')->nullable()->after('token_verified_at');
            }

            if (! Schema::hasColumn('bots', 'webhook_secret')) {
                $table->string('webhook_secret')->nullable()->after('webhook_url');
            }

            if (! Schema::hasColumn('bots', 'webhook_set_at')) {
                $table->timestamp('webhook_set_at')->nullable()->after('webhook_secret');
            }

            if (! Schema::hasColumn('bots', 'webhook_status')) {
                $table->string('webhook_status')->default('not_set')->after('webhook_set_at');
            }

            if (! Schema::hasColumn('bots', 'webhook_last_error')) {
                $table->text('webhook_last_error')->nullable()->after('webhook_status');
            }

            if (! Schema::hasColumn('bots', 'last_webhook_update_at')) {
                $table->timestamp('last_webhook_update_at')->nullable()->after('webhook_last_error');
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
                'last_webhook_update_at',
                'webhook_last_error',
                'webhook_status',
                'webhook_set_at',
                'webhook_secret',
                'webhook_url',
            ] as $column) {
                if (Schema::hasColumn('bots', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
