<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bot_commands') || Schema::hasColumn('bot_commands', 'display_name')) {
            return;
        }

        Schema::table('bot_commands', function (Blueprint $table): void {
            $table->string('display_name')->nullable()->after('command_name');
        });

        DB::table('bot_commands')
            ->whereNull('display_name')
            ->update(['display_name' => DB::raw('command_name')]);

        DB::table('bot_commands')
            ->where('trigger_type', 'direct_message')
            ->update(['display_name' => 'Direct Message Handler']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('bot_commands') || ! Schema::hasColumn('bot_commands', 'display_name')) {
            return;
        }

        Schema::table('bot_commands', function (Blueprint $table): void {
            $table->dropColumn('display_name');
        });
    }
};
