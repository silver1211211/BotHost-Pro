<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'type',
    'reference_type',
    'reference_id',
    'provider',
    'track_id',
    'order_id',
    'amount',
    'currency',
    'pay_currency',
    'network',
    'pay_amount',
    'payment_url',
    'payment_address',
    'qr_code',
    'expires_at',
    'paid_at',
    'status',
    'provider_fee',
    'user_pays_fee',
    'metadata',
    'raw_response',
])]
class PaymentInvoice extends Model
{
    public const TYPE_TEMPLATE_PURCHASE = 'template_purchase';
    public const TYPE_SUBSCRIPTION_UPGRADE = 'subscription_upgrade';

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
        return $this->belongsTo(BotTemplate::class, 'reference_id');
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'reference_id');
    }

    public function formattedAmount(): Attribute
    {
        return Attribute::get(fn () => ($this->currency ?: 'USD').' '.number_format((float) $this->amount, 2));
    }

    public function isPendingLike(): bool
    {
        return in_array($this->status, ['pending', 'waiting', 'paying', 'confirming'], true);
    }

    public function hasActiveAddress(): bool
    {
        return filled($this->payment_address)
            && in_array($this->status, ['waiting', 'paying', 'confirming'], true);
    }
}
