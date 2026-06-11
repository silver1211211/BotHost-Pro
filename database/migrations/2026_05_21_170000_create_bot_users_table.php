<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bot_users')) {
            return;
        }

        Schema::create('bot_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->string('telegram_user_id');
            $table->string('telegram_username')->nullable();
            $table->string('telegram_first_name')->nullable();
            $table->string('telegram_last_name')->nullable();
            $table->string('telegram_language_code')->nullable();
            $table->boolean('is_bot')->default(false);
            $table->string('status')->default('active')->index();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_active_at')->nullable()->index();
            $table->unsignedBigInteger('message_count')->default(0);
            $table->unsignedBigInteger('command_count')->default(0);
            $table->timestamp('blocked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['bot_id', 'telegram_user_id']);
            $table->index('bot_id');
            $table->index('telegram_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_users');
    }
};
