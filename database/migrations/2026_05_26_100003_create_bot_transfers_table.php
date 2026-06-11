<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('receiver_id')->nullable();
            $table->string('receiver_email');
            $table->unsignedBigInteger('source_bot_id');
            $table->string('bot_name');
            // JSON payload of commands/settings — no token
            $table->longText('payload');
            // pending | imported | cancelled | expired
            $table->string('status', 20)->default('pending')->index();
            $table->text('note')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('receiver_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('source_bot_id')->references('id')->on('bots')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_transfers');
    }
};
