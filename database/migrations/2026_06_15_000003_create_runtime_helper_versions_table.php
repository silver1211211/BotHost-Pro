<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('runtime_helper_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('helper_id')->constrained('runtime_helpers')->cascadeOnDelete();
            $table->unsignedSmallInteger('version_number');
            $table->longText('code');
            $table->json('parameters_schema')->nullable();
            $table->json('return_schema')->nullable();
            $table->json('allowed_domains')->nullable();
            $table->unsignedInteger('timeout_ms')->default(5000);
            $table->unsignedTinyInteger('permission_level')->default(0);
            $table->text('change_summary')->nullable();
            $table->string('safety_scan_status', 20)->default('pending');
            $table->text('safety_scan_error')->nullable();
            $table->string('syntax_check_status', 20)->default('pending');
            $table->text('syntax_check_error')->nullable();
            $table->string('test_status', 20)->nullable();
            $table->text('test_error')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('helper_id');
            $table->index('status');
            $table->index('safety_scan_status');
            $table->unique(['helper_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('runtime_helper_versions');
    }
};
