<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bot_logs')) {
            Schema::create('bot_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
                $table->string('type')->default('info')->index();
                $table->string('title')->nullable();
                $table->text('message');
                $table->json('context')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('bot_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('bot_logs', 'title')) {
                $table->string('title')->nullable()->after('type');
            }

            if (! Schema::hasColumn('bot_logs', 'context')) {
                $table->json('context')->nullable()->after('message');
            }

            if (! Schema::hasColumn('bot_logs', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bot_logs')) {
            return;
        }

        Schema::table('bot_logs', function (Blueprint $table) {
            if (Schema::hasColumn('bot_logs', 'context')) {
                $table->dropColumn('context');
            }

            if (Schema::hasColumn('bot_logs', 'title')) {
                $table->dropColumn('title');
            }

            if (Schema::hasColumn('bot_logs', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
