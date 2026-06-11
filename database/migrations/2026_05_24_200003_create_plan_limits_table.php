<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_limits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('name');
            $table->string('value')->nullable();
            $table->string('unit')->nullable();
            $table->boolean('is_unlimited')->default(false);
            $table->boolean('visible_on_upgrade')->default(true);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['subscription_plan_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_limits');
    }
};
