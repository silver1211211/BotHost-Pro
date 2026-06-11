<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_commands', function (Blueprint $table) {
            if (! Schema::hasColumn('bot_commands', 'source')) {
                $table->string('source')->nullable()->after('folder');
            }

            if (! Schema::hasColumn('bot_commands', 'source_template_id')) {
                $table->foreignId('source_template_id')->nullable()->after('source')->constrained('bot_templates')->nullOnDelete();
            }

            if (! Schema::hasColumn('bot_commands', 'source_template_purchase_id')) {
                $table->foreignId('source_template_purchase_id')->nullable()->after('source_template_id')->constrained('bot_template_purchases')->nullOnDelete();
            }

            if (! Schema::hasColumn('bot_commands', 'license_locked')) {
                $table->boolean('license_locked')->default(false)->after('source_template_purchase_id');
            }

            if (! Schema::hasColumn('bot_commands', 'duplicate_count_used')) {
                $table->unsignedInteger('duplicate_count_used')->default(0)->after('license_locked');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bot_commands', function (Blueprint $table) {
            foreach (['duplicate_count_used', 'license_locked', 'source_template_purchase_id', 'source_template_id', 'source'] as $column) {
                if (Schema::hasColumn('bot_commands', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
