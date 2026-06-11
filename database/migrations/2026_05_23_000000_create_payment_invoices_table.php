<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_invoices')) {
            return;
        }

        Schema::create('payment_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('provider')->default('oxapay');
            $table->string('track_id')->nullable()->unique();
            $table->string('order_id')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('USD');
            $table->string('pay_currency')->nullable();
            $table->string('network')->nullable();
            $table->text('payment_url')->nullable();
            $table->text('payment_address')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('status')->default('pending')->index();
            $table->decimal('provider_fee', 12, 2)->nullable();
            $table->boolean('user_pays_fee')->default(true);
            $table->json('metadata')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_invoices');
    }
};
