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
        if (Schema::hasTable('bot_commands')) {
            return;
        }

        Schema::create('bot_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->string('command_name');
            $table->text('response_text');
            $table->string('response_type')->default('text');
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->unique(['bot_id', 'command_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_commands');
    }
};
