<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bot_commands') || ! Schema::hasColumn('bot_commands', 'response_type')) {
            return;
        }

        DB::table('bot_commands')
            ->whereNull('response_type')
            ->update(['response_type' => 'code']);

        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement("ALTER TABLE bot_commands MODIFY response_type VARCHAR(255) NOT NULL DEFAULT 'code'"),
            'pgsql' => DB::statement("ALTER TABLE bot_commands ALTER COLUMN response_type SET DEFAULT 'code'"),
            'sqlite' => null,
            default => null,
        };
    }

    public function down(): void
    {
        if (! Schema::hasTable('bot_commands') || ! Schema::hasColumn('bot_commands', 'response_type')) {
            return;
        }

        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement("ALTER TABLE bot_commands MODIFY response_type VARCHAR(255) NOT NULL DEFAULT 'text'"),
            'pgsql' => DB::statement("ALTER TABLE bot_commands ALTER COLUMN response_type SET DEFAULT 'text'"),
            'sqlite' => null,
            default => null,
        };
    }
};
