<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_broadcasts')) {
            Schema::create('admin_broadcasts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('campaign_name')->nullable();
                $table->string('title');
                $table->text('message');
                $table->string('campaign_type')->nullable();
                $table->string('message_type')->default('text');
                $table->string('priority')->default('normal')->index();
                $table->json('channels');
                $table->string('target_type')->default('all_users')->index();
                $table->foreignId('target_bot_id')->nullable()->constrained('bots')->nullOnDelete();
                $table->string('status')->default('queued')->index();
                $table->unsignedInteger('total_recipients')->default(0);
                $table->unsignedInteger('sent_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->unsignedInteger('skipped_count')->default(0);
                $table->unsignedInteger('batch_size')->default(500);
                $table->unsignedInteger('batch_delay_seconds')->default(5);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('admin_broadcast_deliveries')) {
            Schema::create('admin_broadcast_deliveries', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('admin_broadcast_id')->constrained('admin_broadcasts')->cascadeOnDelete();
                $table->string('channel')->index();
                $table->string('recipient_type')->nullable();
                $table->unsignedBigInteger('recipient_id')->nullable();
                $table->string('recipient_email')->nullable();
                $table->string('telegram_chat_id')->nullable();
                $table->string('status')->default('pending')->index();
                $table->unsignedInteger('attempts')->default(0);
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['recipient_type', 'recipient_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_broadcast_deliveries');
        Schema::dropIfExists('admin_broadcasts');
    }
};
