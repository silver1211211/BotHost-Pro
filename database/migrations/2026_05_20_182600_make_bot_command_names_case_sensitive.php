<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bot_commands') && DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE bot_commands MODIFY command_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bot_commands') && DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE bot_commands MODIFY command_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        }
    }
};
