<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('runtime_reload_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('trigger_type', 30)->default('manual');
            $table->string('status', 30)->default('pending');
            $table->string('mode', 20)->nullable();
            $table->string('current_step', 150)->nullable();
            $table->unsignedTinyInteger('steps_total')->nullable();
            $table->unsignedTinyInteger('steps_completed')->default(0);
            $table->unsignedSmallInteger('helpers_compiled')->nullable();
            $table->unsignedSmallInteger('containers_affected')->nullable();
            $table->unsignedSmallInteger('containers_ok')->nullable();
            $table->unsignedSmallInteger('containers_failed')->nullable();
            $table->mediumText('output')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('trigger_type');
            $table->index('triggered_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('runtime_reload_logs');
    }
};
