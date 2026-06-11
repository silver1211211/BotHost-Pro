<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bot_command_logs')) {
            return;
        }

        Schema::create('bot_command_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_command_id')->nullable()->constrained()->nullOnDelete();
            $table->string('telegram_user_id')->nullable();
            $table->string('telegram_username')->nullable();
            $table->string('telegram_first_name')->nullable();
            $table->string('chat_id')->nullable();
            $table->text('message_text')->nullable();
            $table->string('status')->index();
            $table->unsignedInteger('reply_count')->default(0);
            $table->string('execution_id')->nullable()->index();
            $table->unsignedInteger('execution_time_ms')->nullable();
            $table->string('error_type')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_command_logs');
    }
};
