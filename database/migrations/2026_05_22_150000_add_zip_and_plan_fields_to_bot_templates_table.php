<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('bot_templates', 'template_zip_path')) {
                $table->string('template_zip_path')->nullable()->after('thumbnail_path');
            }

            if (! Schema::hasColumn('bot_templates', 'included_plan')) {
                $table->string('included_plan')->nullable()->after('access_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bot_templates', function (Blueprint $table) {
            foreach (['included_plan', 'template_zip_path'] as $column) {
                if (Schema::hasColumn('bot_templates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
