<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bot_broadcasts') || Schema::hasColumn('bot_broadcasts', 'cancelled_at')) {
            return;
        }

        Schema::table('bot_broadcasts', function (Blueprint $table): void {
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bot_broadcasts') || ! Schema::hasColumn('bot_broadcasts', 'cancelled_at')) {
            return;
        }

        Schema::table('bot_broadcasts', function (Blueprint $table): void {
            $table->dropColumn('cancelled_at');
        });
    }
};
