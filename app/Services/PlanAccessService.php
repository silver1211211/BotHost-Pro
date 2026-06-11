<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotTemplatePurchase;
use App\Models\PlanFeature;
use App\Models\PlanFeatureAccess;
use App\Models\PlanLimit;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PlanAccessService
{
    private const CACHE_TTL = 300; // 5 minutes

    /** @return array<string, bool> feature key => enabled */
    public function featuresForPlan(string $planSlug): array
    {
        return Cache::remember("plan_features_{$planSlug}", self::CACHE_TTL, function () use ($planSlug): array {
            $plan = SubscriptionPlan::query()->where('slug', $planSlug)->first();

            if (! $plan) {
                return [];
            }

            return PlanFeatureAccess::query()
                ->where('subscription_plan_id', $plan->id)
                ->whereHas('feature', fn ($q) => $q->where('is_active', true))
                ->with('feature')
                ->get()
                ->mapWithKeys(fn (PlanFeatureAccess $a) => [$a->feature->key => (bool) $a->enabled])
                ->all();
        });
    }

    /** @return array<string, array{value: string|null, is_unlimited: bool, unit: string|null}> */
    public function limitsForPlan(string $planSlug): array
    {
        return Cache::remember("plan_limits_{$planSlug}", self::CACHE_TTL, function () use ($planSlug): array {
            $plan = SubscriptionPlan::query()->where('slug', $planSlug)->first();

            if (! $plan) {
                return [];
            }

            return PlanLimit::query()
                ->where('subscription_plan_id', $plan->id)
                ->get()
                ->mapWithKeys(fn (PlanLimit $l) => [$l->key => [
                    'value' => $l->value,
                    'is_unlimited' => (bool) $l->is_unlimited,
                    'unit' => $l->unit,
                ]])
                ->all();
        });
    }

    public function userHasFeature(User $user, string $featureKey): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $slug = strtolower((string) ($user->subscription_plan ?: 'free'));
        $features = $this->featuresForPlan($slug);

        // If no features are configured at all, default to allowing access
        if (empty($features)) {
            return true;
        }

        // If the feature key doesn't exist in the matrix, default to allowing access
        if (! array_key_exists($featureKey, $features)) {
            return true;
        }

        return (bool) $features[$featureKey];
    }

    public function userLimit(User $user, string $limitKey, mixed $default = null): mixed
    {
        if ($user->isAdmin()) {
            return 'unlimited';
        }

        $slug = strtolower((string) ($user->subscription_plan ?: 'free'));
        $limits = $this->limitsForPlan($slug);

        if (! isset($limits[$limitKey])) {
            return $default;
        }

        return $limits[$limitKey]['is_unlimited'] ? 'unlimited' : $limits[$limitKey]['value'];
    }

    public function isUnlimited(User $user, string $limitKey): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $slug = strtolower((string) ($user->subscription_plan ?: 'free'));
        $limits = $this->limitsForPlan($slug);

        return (bool) ($limits[$limitKey]['is_unlimited'] ?? false);
    }

    public function canSendBroadcast(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $this->userHasFeature($user, 'broadcasts')) {
            return false;
        }

        $limit = $this->userLimit($user, 'broadcasts_per_month');

        if ($limit === null || $limit === 'unlimited') {
            return true;
        }

        $used = \App\Models\BotBroadcast::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['scheduled', 'queued', 'running', 'sending', 'completed', 'failed'])
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        return $used < (int) $limit;
    }

    public function canTrackBotUser(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $limit = $this->userLimit($user, 'bot_users_tracked');

        if ($limit === null || $limit === 'unlimited') {
            return true;
        }

        $count = \App\Models\BotUser::query()
            ->whereIn('bot_id', \App\Models\Bot::query()->where('user_id', $user->id)->select('id'))
            ->count();

        return $count < (int) $limit;
    }

    public function canCreateBot(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($this->isUnlimited($user, 'bots_allowed')) {
            return true;
        }

        $limit = (int) ($this->userLimit($user, 'bots_allowed', 1));
        $current = $user->bots()->count();

        return $current < $limit;
    }

    public function canCreateCommand(User $user, Bot $bot): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($this->isUnlimited($user, 'commands_per_bot')) {
            return true;
        }

        $limit = (int) ($this->userLimit($user, 'commands_per_bot', 10));
        $current = $bot->commands()->count();

        return $current < $limit;
    }

    /** Returns integer cap or 'unlimited' */
    public function broadcastRecipientLimit(User $user): int|string
    {
        if ($user->isAdmin()) {
            return 'unlimited';
        }

        $slug = strtolower((string) ($user->subscription_plan ?: 'free'));
        $limits = $this->limitsForPlan($slug);

        if (! isset($limits['broadcast_recipients_per_send'])) {
            // Fall back to config defaults
            $cfg = config("broadcasts.limits.{$slug}");

            return $cfg === 'unlimited' ? 'unlimited' : (int) ($cfg ?? 20000);
        }

        if ($limits['broadcast_recipients_per_send']['is_unlimited']) {
            return 'unlimited';
        }

        return (int) ($limits['broadcast_recipients_per_send']['value'] ?? 20000);
    }

    public function freeTemplateUnlockLimit(User $user): int|string
    {
        if ($user->isAdmin()) {
            return 'unlimited';
        }

        $slug = strtolower((string) ($user->subscription_plan ?: 'free'));
        $limits = $this->limitsForPlan($slug);

        if (! isset($limits['free_templates_unlocked_per_month'])) {
            return 'unlimited';
        }

        if ($limits['free_templates_unlocked_per_month']['is_unlimited']) {
            return 'unlimited';
        }

        return (int) ($limits['free_templates_unlocked_per_month']['value'] ?? 0);
    }

    public function paidTemplatePurchaseLimit(User $user): int|string
    {
        if ($user->isAdmin()) {
            return 'unlimited';
        }

        $slug = strtolower((string) ($user->subscription_plan ?: 'free'));
        $limits = $this->limitsForPlan($slug);

        if (! isset($limits['paid_templates_purchase_limit'])) {
            return 'unlimited';
        }

        if ($limits['paid_templates_purchase_limit']['is_unlimited']) {
            return 'unlimited';
        }

        return (int) ($limits['paid_templates_purchase_limit']['value'] ?? 0);
    }

    public function canUnlockFreeTemplate(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $limit = $this->freeTemplateUnlockLimit($user);

        if ($limit === 'unlimited') {
            return true;
        }

        $usedThisMonth = BotTemplatePurchase::query()
            ->where('user_id', $user->id)
            ->where('amount', 0)
            ->where('status', 'completed')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        return $usedThisMonth < $limit;
    }

    public function canPurchasePaidTemplate(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $limit = $this->paidTemplatePurchaseLimit($user);

        if ($limit === 'unlimited') {
            return true;
        }

        $used = BotTemplatePurchase::query()
            ->where('user_id', $user->id)
            ->where('amount', '>', 0)
            ->where('status', 'completed')
            ->count();

        return $used < $limit;
    }

    /** Returns all active plans with dynamic display data for the upgrade page */
    public function upgradePagePlans(): Collection
    {
        return SubscriptionPlan::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get()
            ->each(function (SubscriptionPlan $plan): void {
                $plan->setRelation('_displayFeatures', collect($plan->displayFeatures()));
                $plan->setRelation('_displayLimits', collect($plan->displayLimits()));
            });
    }

    public function clearCache(string $planSlug): void
    {
        Cache::forget("plan_features_{$planSlug}");
        Cache::forget("plan_limits_{$planSlug}");
    }

    public function clearAllCache(): void
    {
        foreach (['free', 'pro', 'business'] as $slug) {
            $this->clearCache($slug);
        }
    }
}
