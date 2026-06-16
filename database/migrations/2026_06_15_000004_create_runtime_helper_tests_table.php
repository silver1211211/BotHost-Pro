<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('runtime_helper_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('helper_id')->constrained('runtime_helpers')->cascadeOnDelete();
            $table->foreignId('version_id')->nullable()->constrained('runtime_helper_versions')->nullOnDelete();
            $table->string('test_name', 150)->default('Default Test');
            $table->json('input_payload')->nullable();
            $table->json('expected_output')->nullable();
            $table->json('actual_output')->nullable();
            $table->string('status', 20)->default('not_run');
            $table->text('error')->nullable();
            $table->unsignedInteger('execution_ms')->nullable();
            $table->boolean('dry_run')->default(true);
            $table->foreignId('run_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ran_at')->nullable();
            $table->timestamps();

            $table->index('helper_id');
            $table->index('version_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('runtime_helper_tests');
    }
};
