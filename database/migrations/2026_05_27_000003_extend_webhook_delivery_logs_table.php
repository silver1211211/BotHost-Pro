<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('webhook_delivery_logs')) {
            return;
        }

        Schema::table('webhook_delivery_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('webhook_delivery_logs', 'direction')) {
                $table->string('direction', 20)->default('incoming')->after('event')->index();
            }

            if (! Schema::hasColumn('webhook_delivery_logs', 'headers')) {
                $table->json('headers')->nullable()->after('payload');
            }

            if (! Schema::hasColumn('webhook_delivery_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('duration_ms');
            }

            if (! Schema::hasColumn('webhook_delivery_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('webhook_delivery_logs')) {
            return;
        }

        Schema::table('webhook_delivery_logs', function (Blueprint $table) {
            foreach (['user_agent', 'ip_address', 'headers', 'direction'] as $column) {
                if (Schema::hasColumn('webhook_delivery_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
