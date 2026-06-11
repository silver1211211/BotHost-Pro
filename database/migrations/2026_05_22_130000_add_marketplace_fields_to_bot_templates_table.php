<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('bot_templates', 'access_type')) {
                $table->string('access_type')->default('free')->after('status');
            }
            if (! Schema::hasColumn('bot_templates', 'price')) {
                $table->decimal('price', 12, 2)->default(0)->after('access_type');
            }
            if (! Schema::hasColumn('bot_templates', 'currency')) {
                $table->string('currency', 10)->default('USD')->after('price');
            }
            if (! Schema::hasColumn('bot_templates', 'marketplace_status')) {
                $table->string('marketplace_status')->default('listed')->index()->after('currency');
            }
            if (! Schema::hasColumn('bot_templates', 'sales_count')) {
                $table->unsignedBigInteger('sales_count')->default(0)->after('import_count');
            }
            if (! Schema::hasColumn('bot_templates', 'revenue_total')) {
                $table->decimal('revenue_total', 12, 2)->default(0)->after('sales_count');
            }
            if (! Schema::hasColumn('bot_templates', 'preview_images')) {
                $table->json('preview_images')->nullable()->after('thumbnail_path');
            }
            if (! Schema::hasColumn('bot_templates', 'demo_url')) {
                $table->string('demo_url')->nullable()->after('preview_images');
            }
            if (! Schema::hasColumn('bot_templates', 'requirements')) {
                $table->json('requirements')->nullable()->after('demo_url');
            }
            if (! Schema::hasColumn('bot_templates', 'features')) {
                $table->json('features')->nullable()->after('requirements');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bot_templates', function (Blueprint $table) {
            foreach (['features', 'requirements', 'demo_url', 'preview_images', 'revenue_total', 'sales_count', 'marketplace_status', 'currency', 'price', 'access_type'] as $column) {
                if (Schema::hasColumn('bot_templates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
