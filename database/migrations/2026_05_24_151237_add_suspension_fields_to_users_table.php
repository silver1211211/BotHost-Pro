<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_until')->nullable()->after('status');
            $table->string('suspension_message', 500)->nullable()->after('suspended_until');
            $table->string('suspension_cta_label', 60)->nullable()->after('suspension_message');
            $table->string('suspension_cta_url', 500)->nullable()->after('suspension_cta_label');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['suspended_until', 'suspension_message', 'suspension_cta_label', 'suspension_cta_url']);
        });
    }
};
