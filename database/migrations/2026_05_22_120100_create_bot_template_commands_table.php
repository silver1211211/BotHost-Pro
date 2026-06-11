<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_template_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_template_id')->constrained('bot_templates')->cascadeOnDelete();
            $table->string('command_name')->index();
            $table->text('description')->nullable();
            $table->longText('code')->nullable();
            $table->longText('response_text')->nullable();
            $table->json('aliases')->nullable();
            $table->string('folder')->nullable();
            $table->string('status')->default('active')->index();
            $table->string('runtime')->default('node');
            $table->string('language')->default('javascript');
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('bot_template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_template_commands');
    }
};
