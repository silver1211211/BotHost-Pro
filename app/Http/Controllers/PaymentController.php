<?php

namespace App\Http\Controllers;

use App\Models\BotTemplate;
use App\Models\PaymentInvoice;
use App\Models\SubscriptionPlan;
use App\Models\TemplatePaymentInvoice;
use App\Services\CryptoNetworkOptions;
use App\Services\OxaPayService;
use App\Services\PaymentInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    private static function allowedPayCurrencies(): array
    {
        return CryptoNetworkOptions::values();
    }

    public function show(Request $request, PaymentInvoice $invoice): View
    {
        $this->authorizeInvoice($request, $invoice);

        $reference = $invoice->type === PaymentInvoice::TYPE_TEMPLATE_PURCHASE
            ? BotTemplate::find($invoice->reference_id)
            : SubscriptionPlan::find($invoice->reference_id);

        return view('payments.show', [
            'invoice'         => $invoice,
            'product'         => $this->productFor($invoice),
            'currencyOptions' => CryptoNetworkOptions::options(),
            'reference'       => $reference,
        ]);
    }

    public function generate(Request $request, PaymentInvoice $invoice, OxaPayService $oxaPay): RedirectResponse
    {
        $this->authorizeInvoice($request, $invoice);

        if ($invoice->status === 'paid') {
            return back()->with('status', 'This invoice has already been paid.');
        }

        if ($invoice->hasActiveAddress()) {
            return back()->with('status', 'Invoice already active. Send payment to the address shown.');
        }

        $data = $request->validate([
            'pay_currency' => ['required', 'string', Rule::in(self::allowedPayCurrencies())],
        ], [
            'pay_currency.required' => 'Please select a payment network.',
            'pay_currency.in'       => 'Selected payment network is invalid.',
        ]);

        $selectedCurrency = $data['pay_currency'];
        $normalized       = CryptoNetworkOptions::normalize($selectedCurrency);

        Log::info('CryptoInvoice: generate started', [
            'invoice_id'            => $invoice->id,
            'user_id'               => $request->user()?->id,
            'selected_pay_currency' => $selectedCurrency,
            'amount'                => $invoice->amount,
            'type'                  => $invoice->type,
        ]);

        if ($invoice->type === PaymentInvoice::TYPE_SUBSCRIPTION_UPGRADE) {
            $plan = SubscriptionPlan::find($invoice->reference_id);

            if (! $plan) {
                return back()->withErrors(['invoice' => 'Subscription plan not found.']);
            }

            $result = $oxaPay->createSubscriptionPayment($request->user(), $plan, $invoice, $selectedCurrency);
        } else {
            $template = BotTemplate::find($invoice->reference_id);

            if (! $template) {
                return back()->withErrors(['invoice' => 'Template not found.']);
            }

            $result = $oxaPay->createTemplatePayment($request->user(), $template, $invoice, $selectedCurrency);
        }

        Log::info('CryptoInvoice: OxaPay response', [
            'invoice_id' => $invoice->id,
            'ok'         => $result['ok'],
            'track_id'   => $result['track_id'] ?? null,
            'has_address'=> filled($result['payment_address'] ?? null),
            'message'    => $result['message'] ?? null,
        ]);

        if (! $result['ok']) {
            $msg = $result['message'] ?? 'Unable to generate invoice. Please try again.';

            if (str_contains(strtolower($msg), 'not configured') || str_contains(strtolower($msg), 'api key')) {
                $msg = 'Payment provider is not configured. Please contact support.';
            }

            return back()->withErrors(['invoice' => $msg]);
        }

        $invoice->update([
            'pay_currency'    => $normalized['pay_currency'],
            'network'         => $result['network'] ?? $normalized['network'],
            'track_id'        => $result['track_id'] ?? null,
            'payment_address' => $result['payment_address'] ?? null,
            'pay_amount'      => $result['pay_amount'] ?? null,
            'qr_code'         => $result['qr_code'] ?? null,
            'expires_at'      => $result['expires_at'] ?? null,
            'status'          => $result['status'] ?? 'waiting',
            'raw_response'    => $result['raw'] ?? null,
            'metadata'        => array_merge($invoice->metadata ?? [], [
                'selected_payment_option' => $selectedCurrency,
                'selected_payment_label'  => $normalized['label'],
            ]),
        ]);

        if ($invoice->type === PaymentInvoice::TYPE_TEMPLATE_PURCHASE) {
            TemplatePaymentInvoice::where('order_id', $invoice->order_id)->update([
                'track_id'        => $result['track_id'] ?? null,
                'payment_address' => $result['payment_address'] ?? null,
                'expires_at'      => $result['expires_at'] ?? null,
                'status'          => $result['status'] ?? 'waiting',
                'raw_response'    => $result['raw'] ?? null,
            ]);
        }

        return redirect()->route('dashboard.payments.show', $invoice)
            ->with('status', 'Invoice created. Send the exact amount to complete your payment.');
    }

    public function check(Request $request, PaymentInvoice $invoice, OxaPayService $oxaPay, PaymentInvoiceService $payments): RedirectResponse
    {
        $this->authorizeInvoice($request, $invoice);

        if (! $invoice->track_id) {
            return back()->with('status', 'Invoice not generated yet. Generate invoice first.');
        }

        $result = $oxaPay->getPaymentStatus($invoice->track_id);

        if (! $result['ok']) {
            return back()->withErrors(['invoice' => $result['message'] ?? 'Unable to check payment status.']);
        }

        $payments->applyProviderStatus($invoice, $result);

        $fresh = $invoice->fresh();

        if ($fresh->status === 'paid') {
            return back()
                ->with('status', $fresh->type === PaymentInvoice::TYPE_SUBSCRIPTION_UPGRADE ? 'Plan upgraded successfully.' : 'Template unlocked successfully.')
                ->with('just_paid', true);
        }

        return back()->with('status', 'Payment status: '.ucfirst($fresh->status).'.');
    }

    public function poll(Request $request, PaymentInvoice $invoice, OxaPayService $oxaPay, PaymentInvoiceService $payments): JsonResponse
    {
        $this->authorizeInvoice($request, $invoice);

        if (filled($invoice->track_id) && in_array($invoice->status, ['waiting', 'paying', 'confirming'], true)) {
            $result = $oxaPay->getPaymentStatus($invoice->track_id);
            if ($result['ok']) {
                $payments->applyProviderStatus($invoice, $result);
                $invoice->refresh();
            }
        }

        return response()->json([
            'status'  => $invoice->status,
            'paid'    => $invoice->status === 'paid',
            'expired' => $invoice->status === 'expired',
        ]);
    }

    public function cancel(Request $request, PaymentInvoice $invoice): RedirectResponse
    {
        $this->authorizeInvoice($request, $invoice);

        if (! $invoice->isPendingLike()) {
            return redirect()->route('dashboard.templates.index')
                ->with('status', 'Invoice is no longer active.');
        }

        $invoice->update(['status' => 'cancelled']);

        if ($invoice->type === PaymentInvoice::TYPE_TEMPLATE_PURCHASE) {
            TemplatePaymentInvoice::where('order_id', $invoice->order_id)
                ->update(['status' => 'cancelled']);
        }

        return redirect()->route('dashboard.templates.index')
            ->with('status', 'Purchase cancelled.');
    }

    public function keepActive(Request $request, PaymentInvoice $invoice): RedirectResponse
    {
        $this->authorizeInvoice($request, $invoice);

        return redirect()->route('dashboard.templates.index');
    }

    private function authorizeInvoice(Request $request, PaymentInvoice $invoice): void
    {
        abort_unless($request->user()?->isAdmin() || $invoice->user_id === $request->user()?->id, 403);
    }

    private function productFor(PaymentInvoice $invoice): array
    {
        if ($invoice->type === PaymentInvoice::TYPE_TEMPLATE_PURCHASE) {
            $template = BotTemplate::find($invoice->reference_id);

            return [
                'name'       => $template?->name ?? 'Template purchase',
                'type'       => 'Template Purchase',
                'return_url' => $template
                    ? route('dashboard.templates.show', $template)
                    : route('dashboard.templates.index'),
            ];
        }

        $plan = SubscriptionPlan::find($invoice->reference_id);

        return [
            'name'       => $plan?->name ?? 'Plan upgrade',
            'type'       => 'Plan Upgrade',
            'return_url' => route('dashboard.upgrade'),
        ];
    }
}
