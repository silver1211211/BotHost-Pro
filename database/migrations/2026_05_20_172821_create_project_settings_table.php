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
        Schema::create('project_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->boolean('auto_restart')->default(false);
            $table->unsignedSmallInteger('ram_limit')->default(256);
            $table->decimal('cpu_limit', 3, 1)->default(0.5);
            $table->boolean('webhook_enabled')->default(true);
            $table->string('timezone')->default('UTC');
            $table->string('bot_token')->nullable();
            $table->string('admin_id')->nullable();
            $table->string('oxapay_api_key')->nullable();
            $table->json('external_apis')->nullable();
            $table->timestamps();

            $table->unique('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_settings');
    }
};
