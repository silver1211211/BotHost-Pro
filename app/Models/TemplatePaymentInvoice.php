<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'bot_template_id',
    'bot_template_purchase_id',
    'provider',
    'track_id',
    'order_id',
    'amount',
    'currency',
    'pay_currency',
    'network',
    'provider_fee',
    'user_pays_fee',
    'status',
    'invoice_url',
    'payment_address',
    'expires_at',
    'paid_at',
    'metadata',
    'raw_response',
])]
class TemplatePaymentInvoice extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'provider_fee' => 'decimal:2',
            'user_pays_fee' => 'boolean',
            'metadata' => 'array',
            'raw_response' => 'array',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(BotTemplate::class, 'bot_template_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(BotTemplatePurchase::class, 'bot_template_purchase_id');
    }

    public function formattedAmount(): Attribute
    {
        return Attribute::get(fn () => ($this->currency ?: 'USD').' $'.number_format((float) $this->amount, 2));
    }
}
