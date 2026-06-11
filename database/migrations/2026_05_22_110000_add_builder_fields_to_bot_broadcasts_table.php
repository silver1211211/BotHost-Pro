<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_broadcasts', function (Blueprint $table) {
            if (! Schema::hasColumn('bot_broadcasts', 'message_type')) {
                $table->string('message_type')->default('text')->after('message');
            }

            if (! Schema::hasColumn('bot_broadcasts', 'image_path')) {
                $table->string('image_path')->nullable()->after('message_type');
            }

            if (! Schema::hasColumn('bot_broadcasts', 'image_original_name')) {
                $table->string('image_original_name')->nullable()->after('image_path');
            }

            if (! Schema::hasColumn('bot_broadcasts', 'image_mime')) {
                $table->string('image_mime')->nullable()->after('image_original_name');
            }

            if (! Schema::hasColumn('bot_broadcasts', 'image_size')) {
                $table->unsignedBigInteger('image_size')->nullable()->after('image_mime');
            }

            if (! Schema::hasColumn('bot_broadcasts', 'cta_text')) {
                $table->string('cta_text')->nullable()->after('image_size');
            }

            if (! Schema::hasColumn('bot_broadcasts', 'cta_url')) {
                $table->string('cta_url', 2048)->nullable()->after('cta_text');
            }

            if (! Schema::hasColumn('bot_broadcasts', 'parse_mode')) {
                $table->string('parse_mode')->nullable()->after('cta_url');
            }

            if (! Schema::hasColumn('bot_broadcasts', 'disable_web_page_preview')) {
                $table->boolean('disable_web_page_preview')->default(false)->after('parse_mode');
            }

            if (! Schema::hasColumn('bot_broadcasts', 'estimated_seconds')) {
                $table->unsignedInteger('estimated_seconds')->nullable()->after('disable_web_page_preview');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bot_broadcasts', function (Blueprint $table) {
            foreach ([
                'message_type',
                'image_path',
                'image_original_name',
                'image_mime',
                'image_size',
                'cta_text',
                'cta_url',
                'parse_mode',
                'disable_web_page_preview',
                'estimated_seconds',
            ] as $column) {
                if (Schema::hasColumn('bot_broadcasts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
