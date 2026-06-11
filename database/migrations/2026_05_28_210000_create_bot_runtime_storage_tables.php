<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bot_runtime_data')) {
            Schema::create('bot_runtime_data', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
                $table->string('key');
                $table->text('value')->nullable();
                $table->timestamps();

                $table->unique(['bot_id', 'key']);
            });
        }

        if (! Schema::hasTable('bot_user_runtime_data')) {
            Schema::create('bot_user_runtime_data', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
                $table->string('telegram_user_id');
                $table->string('key');
                $table->text('value')->nullable();
                $table->timestamps();

                $table->unique(['bot_id', 'telegram_user_id', 'key'], 'bot_user_runtime_data_scope_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_user_runtime_data');
        Schema::dropIfExists('bot_runtime_data');
    }
};
