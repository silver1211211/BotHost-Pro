<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bots', function (Blueprint $table): void {
            if (! Schema::hasColumn('bots', 'runtime_mode')) {
                $table->string('runtime_mode', 20)->default('local')->after('status');
            }

            if (! Schema::hasColumn('bots', 'runtime_status')) {
                $table->string('runtime_status', 40)->nullable()->after('runtime_mode');
            }

            if (! Schema::hasColumn('bots', 'container_name')) {
                $table->string('container_name')->nullable()->after('runtime_status');
            }

            if (! Schema::hasColumn('bots', 'container_status')) {
                $table->string('container_status', 80)->nullable()->after('container_name');
            }

            if (! Schema::hasColumn('bots', 'runtime_http_port')) {
                $table->unsignedInteger('runtime_http_port')->nullable()->after('container_status');
            }

            if (! Schema::hasColumn('bots', 'last_runtime_heartbeat_at')) {
                $table->timestamp('last_runtime_heartbeat_at')->nullable()->after('runtime_http_port');
            }

            if (! Schema::hasColumn('bots', 'last_runtime_error')) {
                $table->text('last_runtime_error')->nullable()->after('last_runtime_heartbeat_at');
            }

            if (! Schema::hasColumn('bots', 'runtime_started_at')) {
                $table->timestamp('runtime_started_at')->nullable()->after('last_runtime_error');
            }

            if (! Schema::hasColumn('bots', 'runtime_restarted_at')) {
                $table->timestamp('runtime_restarted_at')->nullable()->after('runtime_started_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bots', function (Blueprint $table): void {
            foreach ([
                'runtime_restarted_at',
                'runtime_started_at',
                'last_runtime_error',
                'last_runtime_heartbeat_at',
                'runtime_http_port',
                'container_status',
                'container_name',
                'runtime_status',
                'runtime_mode',
            ] as $column) {
                if (Schema::hasColumn('bots', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
