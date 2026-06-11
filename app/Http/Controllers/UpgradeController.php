<?php

namespace App\Http\Controllers;

use App\Models\PaymentInvoice;
use App\Models\SubscriptionPlan;
use App\Services\OxaPayService;
use App\Services\PlanAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UpgradeController extends Controller
{
    public function index(Request $request, PlanAccessService $planAccess): View
    {
        $plans = SubscriptionPlan::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        // Eager-load display features and limits for each plan
        foreach ($plans as $plan) {
            $plan->_displayFeatures = $plan->displayFeatures();
            $plan->_displayLimits   = $plan->displayLimits();
        }

        return view('upgrade.index', [
            'plans'           => $plans,
            'currentPlan'     => strtolower((string) ($request->user()->subscription_plan ?: 'free')),
            'currencyOptions' => OxaPayService::payCurrencyOptions(),
        ]);
    }

    public function createCryptoInvoice(Request $request, SubscriptionPlan $plan, OxaPayService $oxaPay): RedirectResponse
    {
        abort_unless($plan->status === 'active' && in_array($plan->slug, ['pro', 'business'], true), 404);

        if ($request->user()->hasPlanAtLeast($plan->slug)) {
            return back()->with('status', 'You already have this plan or higher.');
        }

        $invoice = PaymentInvoice::create([
            'user_id'        => $request->user()->id,
            'type'           => PaymentInvoice::TYPE_SUBSCRIPTION_UPGRADE,
            'reference_type' => SubscriptionPlan::class,
            'reference_id'   => $plan->id,
            'order_id'       => 'sub_'.$plan->slug.'_user_'.$request->user()->id.'_'.Str::lower(Str::random(12)),
            'amount'         => $plan->price,
            'currency'       => $plan->currency ?: 'USD',
            'pay_currency'   => null,
            'user_pays_fee'  => $oxaPay->feePaidByUser(),
            'status'         => 'pending',
        ]);

        return redirect()->route('dashboard.payments.show', $invoice);
    }
}
