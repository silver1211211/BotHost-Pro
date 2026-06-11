<?php

namespace App\Services;

use App\Models\BotTemplate;
use App\Models\BotTemplatePurchase;
use App\Models\PaymentInvoice;
use App\Models\TemplatePaymentInvoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BotTemplatePurchaseService
{
    public function __construct(private readonly WalletService $wallet) {}

    public function purchase(User $user, BotTemplate $template): BotTemplatePurchase
    {
        if (! $template->isPublished() || ! $template->isPaid() || (float) $template->price <= 0) {
            throw ValidationException::withMessages(['template' => 'This template is not available for purchase.']);
        }

        $existing = BotTemplatePurchase::query()
            ->where('user_id', $user->id)
            ->where('bot_template_id', $template->id)
            ->where('status', 'completed')
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($user, $template): BotTemplatePurchase {
            $price = (float) $template->price;
            $transaction = $this->wallet->debit(
                $user,
                $price,
                'Template purchase: '.$template->name,
                BotTemplate::class,
                $template->id,
                ['template_slug' => $template->slug],
            );

            $purchase = BotTemplatePurchase::create([
                'user_id' => $user->id,
                'bot_template_id' => $template->id,
                'amount' => number_format($price, 2, '.', ''),
                'currency' => $template->currency ?: 'USD',
                'status' => 'completed',
                'purchased_at' => now(),
                'metadata' => ['wallet_transaction_id' => $transaction->id],
            ]);

            $template->increment('sales_count');
            $template->forceFill([
                'revenue_total' => number_format(((float) $template->revenue_total) + $price, 2, '.', ''),
            ])->save();

            return $purchase;
        });
    }

    public function unlockFree(User $user, BotTemplate $template): BotTemplatePurchase
    {
        if (! $template->isPublished() || (! $template->isFree() && ! $template->isIncludedFor($user))) {
            throw ValidationException::withMessages(['template' => 'This template is not available for free unlock.']);
        }

        return $this->recordUnlock($user, $template, '0.00', $template->currency ?: 'USD', null, [
            'unlock_type' => $template->isIncludedFor($user) && ! $template->isFree() ? 'plan' : 'free_or_plan',
            'included_plan' => $template->included_plan,
        ]);
    }

    public function unlockFromInvoice(TemplatePaymentInvoice $invoice): BotTemplatePurchase
    {
        $invoice->loadMissing(['user', 'template']);

        return $this->recordUnlock(
            $invoice->user,
            $invoice->template,
            $invoice->amount,
            $invoice->currency,
            $invoice,
            [
                'invoice_id' => $invoice->id,
                'provider' => $invoice->provider,
                'track_id' => $invoice->track_id,
                'order_id' => $invoice->order_id,
            ],
        );
    }

    public function unlockFromPaymentInvoice(PaymentInvoice $invoice): BotTemplatePurchase
    {
        $invoice->loadMissing('user');
        $template = BotTemplate::findOrFail($invoice->reference_id);

        return $this->recordUnlock(
            $invoice->user,
            $template,
            $invoice->amount,
            $invoice->currency,
            null,
            [
                'payment_invoice_id' => $invoice->id,
                'provider' => $invoice->provider,
                'track_id' => $invoice->track_id,
                'order_id' => $invoice->order_id,
            ],
        );
    }

    private function recordUnlock(User $user, BotTemplate $template, float|string $amount, string $currency, ?TemplatePaymentInvoice $invoice = null, array $metadata = []): BotTemplatePurchase
    {
        return DB::transaction(function () use ($user, $template, $amount, $currency, $invoice, $metadata): BotTemplatePurchase {
            $existing = BotTemplatePurchase::query()
                ->where('user_id', $user->id)
                ->where('bot_template_id', $template->id)
                ->where('status', 'completed')
                ->first();

            if ($existing) {
                if ($invoice && ! $invoice->bot_template_purchase_id) {
                    $invoice->forceFill(['bot_template_purchase_id' => $existing->id])->save();
                }

                return $existing;
            }

            $purchase = BotTemplatePurchase::create([
                'user_id' => $user->id,
                'bot_template_id' => $template->id,
                'amount' => number_format((float) $amount, 2, '.', ''),
                'currency' => $currency,
                'status' => 'completed',
                'purchased_at' => now(),
                'metadata' => $metadata,
            ]);

            if ((float) $amount > 0) {
                $template->increment('sales_count');
                $template->forceFill([
                    'revenue_total' => number_format(((float) $template->revenue_total) + (float) $amount, 2, '.', ''),
                ])->save();
            }

            if ($invoice) {
                $invoice->forceFill(['bot_template_purchase_id' => $purchase->id])->save();
            }

            return $purchase;
        });
    }
}
