<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bot_commands') || Schema::hasColumn('bot_commands', 'trigger_type')) {
            return;
        }

        Schema::table('bot_commands', function (Blueprint $table): void {
            $table->string('trigger_type')->nullable()->after('command_name')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bot_commands') || ! Schema::hasColumn('bot_commands', 'trigger_type')) {
            return;
        }

        Schema::table('bot_commands', function (Blueprint $table): void {
            $table->dropColumn('trigger_type');
        });
    }
};
