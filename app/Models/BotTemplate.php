<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'description',
    'short_description',
    'category',
    'level',
    'status',
    'access_type',
    'price',
    'currency',
    'marketplace_status',
    'is_featured',
    'thumbnail_path',
    'template_zip_path',
    'included_plan',
    'preview_images',
    'demo_url',
    'requirements',
    'features',
    'tags',
    'created_by',
    'import_count',
    'sales_count',
    'revenue_total',
    'commands_count',
    'metadata',
    'published_at',
])]
class BotTemplate extends Model
{
    public const STATUSES = ['draft', 'published', 'archived'];

    public const LEVELS = ['beginner', 'intermediate', 'advanced'];

    public const INCLUDED_PLANS = [null, 'free', 'pro', 'business'];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'price' => 'decimal:2',
            'revenue_total' => 'decimal:2',
            'preview_images' => 'array',
            'requirements' => 'array',
            'features' => 'array',
            'tags' => 'array',
            'metadata' => 'array',
            'published_at' => 'datetime',
            'import_count' => 'integer',
            'sales_count' => 'integer',
            'commands_count' => 'integer',
        ];
    }

    public function commands(): HasMany
    {
        return $this->hasMany(BotTemplateCommand::class)->orderBy('sort_order')->orderBy('command_name');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function imports(): HasMany
    {
        return $this->hasMany(BotTemplateImport::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(BotTemplatePurchase::class);
    }

    public function paymentInvoices(): HasMany
    {
        return $this->hasMany(PaymentInvoice::class, 'reference_id')
            ->where('reference_type', self::class);
    }

    public function isFree(): bool
    {
        return $this->access_type !== 'paid' || (float) $this->price <= 0;
    }

    public function isPaid(): bool
    {
        return $this->access_type === 'paid';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isPurchasedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->purchases()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->exists();
    }

    public function canBeImportedBy(?User $user): bool
    {
        if (! $this->isPublished()) {
            return false;
        }

        return $this->isPurchasedBy($user);
    }

    public function isIncludedFor(?User $user): bool
    {
        if (! $user || ! $this->included_plan) {
            return false;
        }

        return $user->hasPlanAtLeast($this->included_plan);
    }

    public function includedPlanLabel(): ?string
    {
        return match ($this->included_plan) {
            'free' => 'Included for Everyone',
            'pro' => 'Included for Pro & Business',
            'business' => 'Included for Business',
            default => null,
        };
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return filled($this->thumbnail_path) ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->thumbnail_path) : null;
    }

    public function getFormattedPriceAttribute(): string
    {
        $amount = number_format((float) $this->price, 2);

        return ($this->currency ?: 'USD') === 'USD'
            ? '$'.$amount
            : ($this->currency ?: 'USD').' '.$amount;
    }
}
