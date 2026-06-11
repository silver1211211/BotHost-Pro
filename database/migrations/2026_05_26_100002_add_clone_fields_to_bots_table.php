<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->unsignedBigInteger('cloned_from_bot_id')->nullable()->after('template_id');
            // clone | import | transfer_import
            $table->string('source_type', 30)->nullable()->after('cloned_from_bot_id');
        });
    }

    public function down(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->dropColumn(['cloned_from_bot_id', 'source_type']);
        });
    }
};
