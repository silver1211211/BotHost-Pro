<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'username', 'email', 'password', 'email_verified_at', 'role', 'status', 'subscription_plan', 'subscription_status', 'subscription_started_at', 'subscription_expires_at', 'plan_upgraded_at', 'ai_requests_remaining', 'wallet_balance', 'wallet_currency', 'suspended_until', 'suspension_message', 'suspension_cta_label', 'suspension_cta_url'])]
#[Hidden(['password', 'remember_token', 'email_verification_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_token_created_at' => 'datetime',
            'password' => 'hashed',
            'wallet_balance' => 'decimal:2',
            'subscription_started_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
            'plan_upgraded_at'        => 'datetime',
            'suspended_until'         => 'datetime',
        ];
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan', 'slug');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function bots(): HasMany
    {
        return $this->hasMany(Bot::class);
    }

    public function botBroadcasts(): HasMany
    {
        return $this->hasMany(BotBroadcast::class);
    }

    public function botTemplates(): HasMany
    {
        return $this->hasMany(BotTemplate::class, 'created_by');
    }

    public function templateImports(): HasMany
    {
        return $this->hasMany(BotTemplateImport::class);
    }

    public function templatePurchases(): HasMany
    {
        return $this->hasMany(BotTemplatePurchase::class);
    }

    public function purchasedTemplates()
    {
        return $this->belongsToMany(BotTemplate::class, 'bot_template_purchases')
            ->withPivot(['amount', 'currency', 'status', 'purchased_at'])
            ->withTimestamps();
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function templatePaymentInvoices(): HasMany
    {
        return $this->hasMany(TemplatePaymentInvoice::class);
    }

    public function paymentInvoices(): HasMany
    {
        return $this->hasMany(PaymentInvoice::class);
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $token = Str::random(64);

        $this->forceFill([
            'email_verification_token' => hash('sha256', $token),
            'email_verification_token_created_at' => now(),
        ])->saveQuietly();

        $this->notify(new VerifyEmailNotification($token));
    }

    public function isBanned(): bool
    {
        return $this->status === 'banned';
    }

    public function suspensionExpired(): bool
    {
        return $this->suspended_until !== null && $this->suspended_until->isPast();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isFree(): bool
    {
        return $this->currentPlanSlug() === 'free';
    }

    public function isPro(): bool
    {
        return $this->currentPlanSlug() === 'pro';
    }

    public function isBusiness(): bool
    {
        return $this->isAdmin() || $this->currentPlanSlug() === 'business';
    }

    public function hasPlanAtLeast(string $plan): bool
    {
        $rank = ['free' => 1, 'pro' => 2, 'business' => 3];
        $current = $this->isAdmin() ? 'business' : $this->currentPlanSlug();

        return ($rank[$current] ?? 1) >= ($rank[strtolower($plan)] ?? 1);
    }

    public function hasFeature(string $key): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $plan = SubscriptionPlan::query()->where('slug', $this->currentPlanSlug())->first();

        return $plan ? $plan->hasFeature($key) : false;
    }

    public function getPlanLimit(string $key, mixed $default = null): mixed
    {
        if ($this->isAdmin()) {
            return 'unlimited';
        }

        $plan = SubscriptionPlan::query()->where('slug', $this->currentPlanSlug())->first();

        return $plan ? $plan->getLimit($key, $default) : $default;
    }

    public function planLimitReached(string $key, int $currentCount): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        $limit = $this->getPlanLimit($key);

        if ($limit === null || $limit === 'unlimited') {
            return false;
        }

        return $currentCount >= (int) $limit;
    }

    private function currentPlanSlug(): string
    {
        return strtolower((string) ($this->subscription_plan ?: 'free'));
    }
}
