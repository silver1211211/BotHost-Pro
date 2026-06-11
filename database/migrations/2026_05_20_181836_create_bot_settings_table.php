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
        if (Schema::hasTable('bot_settings')) {
            return;
        }

        Schema::create('bot_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->boolean('auto_restart')->default(true);
            $table->unsignedSmallInteger('ram_limit')->default(256);
            $table->decimal('cpu_limit', 3, 1)->default(0.5);
            $table->string('timezone')->default('UTC');
            $table->timestamps();

            $table->unique('bot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_settings');
    }
};
