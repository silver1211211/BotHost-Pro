<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_logs') && ! Schema::hasColumn('activity_logs', 'description')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->text('description')->nullable()->after('action');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('activity_logs') && Schema::hasColumn('activity_logs', 'description')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
