<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_notifications')) {
            Schema::create('user_notifications', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('admin_broadcast_id')->nullable()->constrained('admin_broadcasts')->nullOnDelete();
                $table->string('title');
                $table->text('message');
                $table->string('type')->nullable()->index();
                $table->string('priority')->nullable()->index();
                $table->string('status')->default('unread')->index();
                $table->timestamp('read_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        if (! Schema::hasTable('platform_announcements')) {
            Schema::create('platform_announcements', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('admin_broadcast_id')->nullable()->constrained('admin_broadcasts')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title');
                $table->text('message');
                $table->string('type')->nullable();
                $table->string('priority')->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('dismissible')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('platform_announcement_reads')) {
            Schema::create('platform_announcement_reads', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('platform_announcement_id')->constrained('platform_announcements')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamp('dismissed_at')->nullable();
                $table->timestamps();

                $table->unique(['platform_announcement_id', 'user_id'], 'announcement_user_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_announcement_reads');
        Schema::dropIfExists('platform_announcements');
        Schema::dropIfExists('user_notifications');
    }
};
