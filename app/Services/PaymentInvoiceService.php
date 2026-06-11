<?php

namespace App\Services;

use App\Models\BotTemplate;
use App\Models\PaymentInvoice;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;

class PaymentInvoiceService
{
    public function __construct(private readonly BotTemplatePurchaseService $templatePurchases) {}

    public function applyProviderStatus(PaymentInvoice $invoice, array $status): PaymentInvoice
    {
        return DB::transaction(function () use ($invoice, $status): PaymentInvoice {
            $invoice->refresh();
            $newStatus = $status['status'] ?? $invoice->status;

            $invoice->forceFill(array_filter([
                'track_id'        => $invoice->track_id ?: ($status['track_id'] ?? null),
                'payment_url'     => $invoice->payment_url ?: ($status['payment_url'] ?? null),
                'payment_address' => $invoice->payment_address ?: ($status['payment_address'] ?? null),
                'pay_amount'      => $invoice->pay_amount ?: ($status['pay_amount'] ?? null),
                'qr_code'         => $invoice->qr_code ?: ($status['qr_code'] ?? null),
                'expires_at'      => $invoice->expires_at ?: ($status['expires_at'] ?? null),
                'status'          => $newStatus,
                'raw_response'    => $status['raw'] ?? $status,
            ], fn (mixed $value) => $value !== null))->save();

            if ($newStatus === 'paid' && ! $invoice->paid_at) {
                $invoice->forceFill(['paid_at' => now(), 'status' => 'paid'])->save();
                $this->completePaidInvoice($invoice->fresh());
            }

            return $invoice->fresh();
        });
    }

    public function completePaidInvoice(PaymentInvoice $invoice): void
    {
        if ($invoice->type === PaymentInvoice::TYPE_TEMPLATE_PURCHASE) {
            $this->templatePurchases->unlockFromPaymentInvoice($invoice);

            return;
        }

        if ($invoice->type === PaymentInvoice::TYPE_SUBSCRIPTION_UPGRADE) {
            $this->upgradeSubscription($invoice);
        }
    }

    private function upgradeSubscription(PaymentInvoice $invoice): void
    {
        $plan = SubscriptionPlan::query()
            ->whereKey($invoice->reference_id)
            ->where('slug', '!=', 'free')
            ->first();

        if (! $plan) {
            return;
        }

        $invoice->loadMissing('user');
        $user = $invoice->user;

        $user->forceFill([
            'subscription_plan' => $plan->slug,
            'subscription_status' => 'active',
            'subscription_started_at' => $user->subscription_started_at ?: now(),
            'subscription_expires_at' => now()->addMonth(),
            'plan_upgraded_at' => now(),
        ])->save();

        SubscriptionPayment::query()->firstOrCreate(
            ['payment_invoice_id' => $invoice->id],
            [
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'amount' => $invoice->amount,
                'currency' => $invoice->currency,
                'status' => 'completed',
                'paid_at' => $invoice->paid_at ?: now(),
                'metadata' => [
                    'track_id' => $invoice->track_id,
                    'order_id' => $invoice->order_id,
                ],
            ],
        );
    }
}
