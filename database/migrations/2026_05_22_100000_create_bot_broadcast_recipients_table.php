<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_broadcast_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_broadcast_id')->constrained('bot_broadcasts')->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_user_id')->nullable()->constrained('bot_users')->nullOnDelete();
            $table->string('telegram_user_id');
            $table->string('chat_id')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->string('telegram_message_id')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamps();

            $table->index('bot_broadcast_id');
            $table->index('bot_id');
            $table->index('bot_user_id');
            $table->index('telegram_user_id');
            $table->index('status');
            $table->unique(['bot_broadcast_id', 'telegram_user_id'], 'broadcast_recipient_unique_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_broadcast_recipients');
    }
};
