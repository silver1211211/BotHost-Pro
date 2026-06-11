<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_feature_access', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_feature_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->boolean('visible_on_upgrade')->default(true);
            $table->string('label_override')->nullable();
            $table->timestamps();

            $table->unique(['subscription_plan_id', 'plan_feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_feature_access');
    }
};
