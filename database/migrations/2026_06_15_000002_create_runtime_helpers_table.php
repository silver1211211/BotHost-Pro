<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('runtime_helpers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('runtime_helper_categories')->cascadeOnDelete();
            $table->string('name', 100)->unique();
            $table->string('label', 150);
            $table->text('description')->nullable();
            $table->string('helper_type', 50)->default('utility');
            $table->longText('code');
            $table->json('parameters_schema')->nullable();
            $table->json('return_schema')->nullable();
            $table->json('allowed_domains')->nullable();
            $table->unsignedInteger('timeout_ms')->default(5000);
            $table->unsignedTinyInteger('permission_level')->default(0);
            $table->boolean('expose_to_bot_code')->default(true);
            $table->boolean('show_in_helper_list')->default(true);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_protected')->default(false);
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('active_version_id')->nullable();
            $table->string('last_test_status', 20)->nullable();
            $table->text('last_test_error')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->boolean('requires_runtime_reload')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('category_id');
            $table->index('helper_type');
            $table->index('active_version_id');
            $table->index('requires_runtime_reload');
            $table->index('is_system');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('runtime_helpers');
    }
};
