<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->index('created_at', 'audit_logs_created_at_index');
            $table->index('ip_address', 'audit_logs_ip_address_index');
            $table->index(['category', 'status', 'created_at'], 'audit_logs_category_status_created_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex('audit_logs_created_at_index');
            $table->dropIndex('audit_logs_ip_address_index');
            $table->dropIndex('audit_logs_category_status_created_index');
        });
    }
};
