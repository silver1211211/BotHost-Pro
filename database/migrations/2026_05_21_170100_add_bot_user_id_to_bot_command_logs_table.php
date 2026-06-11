<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bot_command_logs') || ! Schema::hasTable('bot_users')) {
            return;
        }

        Schema::table('bot_command_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('bot_command_logs', 'bot_user_id')) {
                $table->foreignId('bot_user_id')
                    ->nullable()
                    ->after('bot_command_id')
                    ->constrained('bot_users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bot_command_logs') || ! Schema::hasColumn('bot_command_logs', 'bot_user_id')) {
            return;
        }

        Schema::table('bot_command_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bot_user_id');
        });
    }
};
