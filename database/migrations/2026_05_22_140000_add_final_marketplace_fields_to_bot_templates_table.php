<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('bot_templates', 'short_description')) {
                $table->string('short_description', 300)->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bot_templates', function (Blueprint $table) {
            if (Schema::hasColumn('bot_templates', 'short_description')) {
                $table->dropColumn('short_description');
            }
        });
    }
};
