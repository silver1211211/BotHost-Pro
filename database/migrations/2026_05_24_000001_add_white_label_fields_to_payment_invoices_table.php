<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('payment_invoices', 'pay_amount')) {
                $table->string('pay_amount')->nullable()->after('pay_currency');
            }
            if (! Schema::hasColumn('payment_invoices', 'qr_code')) {
                $table->text('qr_code')->nullable()->after('payment_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table): void {
            $table->dropColumnIfExists('pay_amount');
            $table->dropColumnIfExists('qr_code');
        });
    }
};
