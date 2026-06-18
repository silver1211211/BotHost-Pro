<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_file_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->string('file_hash', 80)->unique();
            $table->text('file_id');
            $table->string('file_unique_id')->nullable();
            $table->string('file_path', 512);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->index(['bot_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_file_references');
    }
};
