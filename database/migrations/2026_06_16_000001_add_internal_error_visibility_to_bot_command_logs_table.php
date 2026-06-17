<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bot_command_logs')) {
            return;
        }

        Schema::table('bot_command_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('bot_command_logs', 'command_name')) {
                $table->string('command_name')->nullable()->after('bot_command_id');
            }

            if (! Schema::hasColumn('bot_command_logs', 'public_error_message')) {
                $table->text('public_error_message')->nullable()->after('execution_time_ms');
            }

            if (! Schema::hasColumn('bot_command_logs', 'internal_error_type')) {
                $table->string('internal_error_type')->nullable()->after('public_error_message');
            }

            if (! Schema::hasColumn('bot_command_logs', 'internal_error_message')) {
                $table->text('internal_error_message')->nullable()->after('internal_error_type');
            }

            if (! Schema::hasColumn('bot_command_logs', 'internal_error_stack')) {
                $table->text('internal_error_stack')->nullable()->after('internal_error_message');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bot_command_logs')) {
            return;
        }

        Schema::table('bot_command_logs', function (Blueprint $table) {
            foreach (['internal_error_stack', 'internal_error_message', 'internal_error_type', 'public_error_message', 'command_name'] as $column) {
                if (Schema::hasColumn('bot_command_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
