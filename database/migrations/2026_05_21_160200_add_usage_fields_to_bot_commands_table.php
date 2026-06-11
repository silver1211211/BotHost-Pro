<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bot_commands')) {
            return;
        }

        Schema::table('bot_commands', function (Blueprint $table) {
            if (! Schema::hasColumn('bot_commands', 'last_used_at')) {
                $table->timestamp('last_used_at')->nullable()->after('folder');
            }

            if (! Schema::hasColumn('bot_commands', 'last_error_at')) {
                $table->timestamp('last_error_at')->nullable()->after('last_used_at');
            }

            if (! Schema::hasColumn('bot_commands', 'execution_count')) {
                $table->unsignedBigInteger('execution_count')->default(0)->after('last_error_at');
            }

            if (! Schema::hasColumn('bot_commands', 'error_count')) {
                $table->unsignedBigInteger('error_count')->default(0)->after('execution_count');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bot_commands')) {
            return;
        }

        Schema::table('bot_commands', function (Blueprint $table) {
            foreach (['error_count', 'execution_count', 'last_error_at', 'last_used_at'] as $column) {
                if (Schema::hasColumn('bot_commands', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
