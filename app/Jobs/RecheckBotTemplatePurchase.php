<?php

namespace App\Jobs;

use App\Models\BotTemplate;
use App\Models\PaymentInvoice;
use App\Models\TemplatePaymentInvoice;
use App\Models\User;
use App\Services\BotTemplatePurchaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecheckBotTemplatePurchase implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public function __construct(
        public readonly int $userId,
        public readonly int $templateId,
    ) {}

    public function handle(BotTemplatePurchaseService $purchases): void
    {
        $user = User::query()->find($this->userId);
        $template = BotTemplate::query()->find($this->templateId);

        if (! $user || ! $template || $template->isPurchasedBy($user)) {
            return;
        }

        $invoice = PaymentInvoice::query()
            ->where('user_id', $user->id)
            ->where('type', PaymentInvoice::TYPE_TEMPLATE_PURCHASE)
            ->where('reference_id', $template->id)
            ->whereIn('status', ['paid', 'completed', 'confirmed'])
            ->latest('paid_at')
            ->first();

        if ($invoice) {
            $purchases->unlockFromPaymentInvoice($invoice);

            return;
        }

        $legacyInvoice = TemplatePaymentInvoice::query()
            ->where('user_id', $user->id)
            ->where('bot_template_id', $template->id)
            ->whereIn('status', ['paid', 'completed', 'confirmed'])
            ->latest('paid_at')
            ->first();

        if ($legacyInvoice) {
            $purchases->unlockFromInvoice($legacyInvoice);
        }
    }
}
