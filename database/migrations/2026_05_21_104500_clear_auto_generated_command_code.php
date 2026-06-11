<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bot_commands') || ! Schema::hasColumn('bot_commands', 'code')) {
            return;
        }

        DB::table('bot_commands')
            ->where('code', 'await ctx.reply("Hello from this command");')
            ->update(['code' => null]);
    }

    public function down(): void
    {
        //
    }
};
