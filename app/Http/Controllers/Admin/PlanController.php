<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlanFeature;
use App\Models\PlanFeatureAccess;
use App\Models\PlanLimit;
use App\Models\SubscriptionPlan;
use App\Services\PlanAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function __construct(private readonly PlanAccessService $planAccess) {}

    public function index(): View
    {
        $plans = SubscriptionPlan::query()->orderBy('sort_order')->get();
        $features = PlanFeature::query()->orderBy('sort_order')->get();

        // Build feature access matrix: [plan_id][feature_id] => PlanFeatureAccess
        $accessMatrix = PlanFeatureAccess::query()
            ->whereIn('subscription_plan_id', $plans->pluck('id'))
            ->get()
            ->groupBy('subscription_plan_id');

        // Build limits matrix: [plan_id][key] => PlanLimit
        $limitsMatrix = PlanLimit::query()
            ->whereIn('subscription_plan_id', $plans->pluck('id'))
            ->get()
            ->groupBy('subscription_plan_id');

        $limitKeys = [
            'bots_allowed'                  => 'Bots Allowed',
            'commands_per_bot'              => 'Commands Per Bot',
            'broadcast_recipients_per_send' => 'Broadcast Recipients Per Send',
            'broadcasts_per_month'               => 'Broadcasts Per Month',
            'free_templates_unlocked_per_month'  => 'Free Templates Unlocked Per Month',
            'paid_templates_purchase_limit'      => 'Paid Templates',
            'storage_mb'                         => 'Storage (MB)',
            'team_members'                       => 'Team Members',
            'api_requests_per_month'             => 'API Requests Per Month',
            'bot_users_tracked'                  => 'Bot Users Tracked',
            'logs_retention_days'                => 'Logs Retention (Days)',
        ];

        return view('admin.plans.index', compact(
            'plans',
            'features',
            'accessMatrix',
            'limitsMatrix',
            'limitKeys',
        ));
    }

    public function update(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'description'    => ['nullable', 'string', 'max:3000'],
            'price'          => ['required', 'numeric', 'min:0'],
            'currency'       => ['required', 'string', 'max:10'],
            'billing_period' => ['required', Rule::in(['monthly', 'yearly'])],
            'status'         => ['required', Rule::in(['active', 'inactive'])],
            'sort_order'     => ['nullable', 'integer', 'min:0'],
        ]);

        $plan->update($data);
        $this->planAccess->clearCache($plan->slug);

        return redirect()->route('admin.plans.index', ['tab' => 'plans'])->with('status', "Plan \"{$plan->name}\" updated.");
    }

    public function updateFeatures(Request $request): RedirectResponse
    {
        $plans = SubscriptionPlan::query()->get()->keyBy('id');
        $features = PlanFeature::query()->get()->keyBy('id');

        // enabled[plan_id][feature_id] = 1
        // visible[plan_id][feature_id] = 1
        $enabledMatrix  = $request->input('enabled', []);
        $visibleMatrix  = $request->input('visible', []);

        foreach ($plans as $planId => $plan) {
            foreach ($features as $featureId => $feature) {
                $enabled = isset($enabledMatrix[$planId][$featureId]);
                $visible = isset($visibleMatrix[$planId][$featureId]);

                PlanFeatureAccess::query()->updateOrCreate(
                    ['subscription_plan_id' => $planId, 'plan_feature_id' => $featureId],
                    ['enabled' => $enabled, 'visible_on_upgrade' => $visible],
                );
            }
        }

        $this->planAccess->clearAllCache();

        return redirect()->route('admin.plans.index', ['tab' => 'features'])->with('status', 'Feature access updated.');
    }

    public function updateLimits(Request $request): RedirectResponse
    {
        $plans = SubscriptionPlan::query()->get()->keyBy('id');
        $posted = $request->input('limits', []);

        // limits[plan_id][key][value|unlimited|visible] = ...
        $limitNames = [
            'bots_allowed'                  => ['name' => 'Bots Allowed',                  'unit' => 'bots',       'sort' => 1],
            'commands_per_bot'              => ['name' => 'Commands Per Bot',              'unit' => 'commands',   'sort' => 2],
            'broadcast_recipients_per_send' => ['name' => 'Broadcast Recipients Per Send', 'unit' => 'recipients', 'sort' => 3],
            'broadcasts_per_month'               => ['name' => 'Broadcasts Per Month',               'unit' => 'broadcasts', 'sort' => 4],
            'free_templates_unlocked_per_month'  => ['name' => 'Free Templates Unlocked Per Month', 'unit' => 'templates',  'sort' => 5],
            'paid_templates_purchase_limit'      => ['name' => 'Paid Templates',                    'unit' => 'templates',  'sort' => 6],
            'storage_mb'                         => ['name' => 'Storage (MB)',                       'unit' => 'MB',         'sort' => 7],
            'team_members'                       => ['name' => 'Team Members',                       'unit' => 'members',    'sort' => 8],
            'api_requests_per_month'             => ['name' => 'API Requests Per Month',             'unit' => 'requests',   'sort' => 9],
            'bot_users_tracked'                  => ['name' => 'Bot Users Tracked',                  'unit' => 'users',      'sort' => 10],
            'logs_retention_days'                => ['name' => 'Logs Retention',                     'unit' => 'days',       'sort' => 11],
        ];

        foreach ($plans as $planId => $plan) {
            $planLimits = $posted[$planId] ?? [];

            foreach ($limitNames as $key => $meta) {
                $row = $planLimits[$key] ?? [];
                $isUnlimited = isset($row['unlimited']);
                $value = $isUnlimited ? null : (isset($row['value']) ? (string) $row['value'] : '0');
                $visible = isset($row['visible']);

                PlanLimit::query()->updateOrCreate(
                    ['subscription_plan_id' => $planId, 'key' => $key],
                    [
                        'name'               => $meta['name'],
                        'value'              => $value,
                        'unit'               => $meta['unit'],
                        'is_unlimited'       => $isUnlimited,
                        'visible_on_upgrade' => $visible,
                        'sort_order'         => $meta['sort'],
                    ],
                );
            }
        }

        $this->planAccess->clearAllCache();

        return redirect()->route('admin.plans.index', ['tab' => 'limits'])->with('status', 'Plan limits updated.');
    }

    public function updateTemplateAccess(Request $request): RedirectResponse
    {
        // Template access is controlled via feature access keys:
        // template_marketplace, paid_templates, pro_templates, business_templates
        // Reuse the feature update logic for these specific keys.
        $plans = SubscriptionPlan::query()->get()->keyBy('id');
        $templateFeatureKeys = ['template_marketplace', 'paid_templates', 'pro_templates', 'business_templates'];
        $features = PlanFeature::query()->whereIn('key', $templateFeatureKeys)->get()->keyBy('id');

        $enabledMatrix = $request->input('enabled', []);
        $visibleMatrix = $request->input('visible', []);

        foreach ($plans as $planId => $plan) {
            foreach ($features as $featureId => $feature) {
                $enabled = isset($enabledMatrix[$planId][$featureId]);
                $visible = isset($visibleMatrix[$planId][$featureId]);

                PlanFeatureAccess::query()->updateOrCreate(
                    ['subscription_plan_id' => $planId, 'plan_feature_id' => $featureId],
                    ['enabled' => $enabled, 'visible_on_upgrade' => $visible],
                );
            }
        }

        // Sync template-specific limits from posted data
        $limitData = $request->input('limits', []);
        $templateLimitDefs = [
            'free_templates_unlocked_per_month' => ['name' => 'Free Templates Unlocked Per Month', 'sort' => 5],
            'paid_templates_purchase_limit'     => ['name' => 'Paid Templates',                    'sort' => 6],
        ];

        foreach ($plans as $planId => $plan) {
            foreach ($templateLimitDefs as $limitKey => $limitMeta) {
                $row = $limitData[$planId][$limitKey] ?? null;

                if ($row !== null) {
                    $isUnlimited = isset($row['unlimited']);
                    $value = $isUnlimited ? null : ((string) ($row['value'] ?? '0'));

                    PlanLimit::query()->updateOrCreate(
                        ['subscription_plan_id' => $planId, 'key' => $limitKey],
                        [
                            'name'               => $limitMeta['name'],
                            'value'              => $value,
                            'unit'               => 'templates',
                            'is_unlimited'       => $isUnlimited,
                            'visible_on_upgrade' => true,
                            'sort_order'         => $limitMeta['sort'],
                        ],
                    );
                }
            }
        }

        $this->planAccess->clearAllCache();

        return redirect()->route('admin.plans.index', ['tab' => 'template-access'])->with('status', 'Template access updated.');
    }

    public function updateBroadcastLimits(Request $request): RedirectResponse
    {
        $plans = SubscriptionPlan::query()->get()->keyBy('id');
        $posted = $request->input('limits', []);

        $broadcastLimitKeys = [
            'broadcast_recipients_per_send' => ['name' => 'Broadcast Recipients Per Send', 'unit' => 'recipients', 'sort' => 3],
            'broadcasts_per_month'          => ['name' => 'Broadcasts Per Month',          'unit' => 'broadcasts', 'sort' => 4],
        ];

        foreach ($plans as $planId => $plan) {
            $planLimits = $posted[$planId] ?? [];

            foreach ($broadcastLimitKeys as $key => $meta) {
                $row = $planLimits[$key] ?? [];
                $isUnlimited = isset($row['unlimited']);
                $value = $isUnlimited ? null : ((string) ($row['value'] ?? '0'));
                $visible = isset($row['visible']);

                PlanLimit::query()->updateOrCreate(
                    ['subscription_plan_id' => $planId, 'key' => $key],
                    [
                        'name'               => $meta['name'],
                        'value'              => $value,
                        'unit'               => $meta['unit'],
                        'is_unlimited'       => $isUnlimited,
                        'visible_on_upgrade' => $visible,
                        'sort_order'         => $meta['sort'],
                    ],
                );
            }
        }

        $this->planAccess->clearAllCache();

        return redirect()->route('admin.plans.index', ['tab' => 'broadcast-limits'])->with('status', 'Broadcast limits updated.');
    }
}
