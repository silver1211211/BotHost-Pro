<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bot_commands')) {
            return;
        }

        Schema::table('bot_commands', function (Blueprint $table) {
            if (Schema::hasColumn('bot_commands', 'response_text')) {
                $table->text('response_text')->nullable()->change();
            }

            if (! Schema::hasColumn('bot_commands', 'code')) {
                $table->longText('code')->nullable()->after('command_name');
            }

            if (! Schema::hasColumn('bot_commands', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('status');
            }

            if (! Schema::hasColumn('bot_commands', 'admin_only')) {
                $table->boolean('admin_only')->default(false)->after('is_pinned');
            }

            if (! Schema::hasColumn('bot_commands', 'aliases')) {
                $table->json('aliases')->nullable()->after('admin_only');
            }

            if (! Schema::hasColumn('bot_commands', 'folder')) {
                $table->string('folder')->nullable()->after('aliases');
            }
        });

        DB::table('bot_commands')
            ->whereNull('code')
            ->update([
                'code' => 'await ctx.reply("Hello from this command");',
                'response_type' => 'code',
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('bot_commands')) {
            return;
        }

        Schema::table('bot_commands', function (Blueprint $table) {
            foreach (['folder', 'aliases', 'admin_only', 'is_pinned', 'code'] as $column) {
                if (Schema::hasColumn('bot_commands', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
