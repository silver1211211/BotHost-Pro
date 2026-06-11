<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_payment_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_template_id')->constrained('bot_templates')->cascadeOnDelete();
            $table->foreignId('bot_template_purchase_id')->nullable()->constrained('bot_template_purchases')->nullOnDelete();
            $table->string('provider')->default('oxapay');
            $table->string('track_id')->nullable()->unique();
            $table->string('order_id')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('pay_currency')->nullable();
            $table->string('network')->nullable();
            $table->decimal('provider_fee', 12, 2)->nullable();
            $table->boolean('user_pays_fee')->default(true);
            $table->string('status')->default('pending');
            $table->text('invoice_url')->nullable();
            $table->text('payment_address')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_payment_invoices');
    }
};
