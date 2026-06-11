<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bots') && Schema::hasColumn('bots', 'token_encrypted')) {
            Schema::table('bots', function (Blueprint $table) {
                $table->text('token_encrypted')->nullable()->change();
            });
        }

        if (! Schema::hasTable('bots') || ! Schema::hasColumn('bots', 'token_hash')) {
            return;
        }

        DB::table('bots')
            ->whereNull('token_hash')
            ->whereNotNull('token_encrypted')
            ->orderBy('id')
            ->select(['id', 'token_encrypted'])
            ->chunkById(100, function ($bots): void {
                foreach ($bots as $bot) {
                    try {
                        $token = Crypt::decryptString($bot->token_encrypted);
                    } catch (Throwable) {
                        continue;
                    }

                    try {
                        DB::table('bots')
                            ->where('id', $bot->id)
                            ->whereNull('token_hash')
                            ->update(['token_hash' => hash('sha256', trim($token))]);
                    } catch (Throwable) {
                        continue;
                    }
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('bots') && Schema::hasColumn('bots', 'token_encrypted')) {
            Schema::table('bots', function (Blueprint $table) {
                $table->text('token_encrypted')->nullable(false)->change();
            });
        }
    }
};
