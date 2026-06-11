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
            ->where('response_type', 'text')
            ->update(['response_type' => 'code']);
    }

    public function down(): void
    {
        //
    }
};
