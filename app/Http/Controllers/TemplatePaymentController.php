<?php

namespace App\Http\Controllers;

use App\Models\BotTemplate;
use App\Models\PaymentInvoice;
use App\Models\TemplatePaymentInvoice;
use App\Services\BotTemplatePurchaseService;
use App\Services\OxaPayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TemplatePaymentController extends Controller
{
    public function createCryptoInvoice(Request $request, BotTemplate $template, OxaPayService $oxaPay): RedirectResponse
    {
        abort_unless($template->status === 'published' && $template->isPaid(), 404);

        if ($template->isPurchasedBy($request->user()) || $template->isIncludedFor($request->user())) {
            return back()->with('status', 'Template already unlocked.');
        }

        $existing = PaymentInvoice::where('user_id', $request->user()->id)
            ->where('type', PaymentInvoice::TYPE_TEMPLATE_PURCHASE)
            ->where('reference_id', $template->id)
            ->whereIn('status', ['pending', 'waiting', 'paying', 'confirming'])
            ->latest()
            ->first();

        if ($existing) {
            return redirect()->route('dashboard.payments.show', $existing);
        }

        $orderId = 'tpl_'.$template->id.'_user_'.$request->user()->id.'_'.Str::lower(Str::random(12));

        $invoice = PaymentInvoice::create([
            'user_id'        => $request->user()->id,
            'type'           => PaymentInvoice::TYPE_TEMPLATE_PURCHASE,
            'reference_type' => BotTemplate::class,
            'reference_id'   => $template->id,
            'order_id'       => $orderId,
            'amount'         => $template->price,
            'currency'       => $template->currency ?: 'USD',
            'pay_currency'   => null,
            'user_pays_fee'  => $oxaPay->feePaidByUser(),
            'status'         => 'pending',
        ]);

        TemplatePaymentInvoice::create([
            'user_id'        => $request->user()->id,
            'bot_template_id'=> $template->id,
            'order_id'       => $orderId,
            'amount'         => $template->price,
            'currency'       => $template->currency ?: 'USD',
            'pay_currency'   => null,
            'user_pays_fee'  => $oxaPay->feePaidByUser(),
            'status'         => 'pending',
            'metadata'       => ['payment_invoice_id' => $invoice->id],
        ]);

        return redirect()->route('dashboard.payments.show', $invoice);
    }

    public function show(Request $request, TemplatePaymentInvoice $invoice): View|RedirectResponse
    {
        abort_unless($request->user()?->isAdmin() || $invoice->user_id === $request->user()?->id, 403);

        // New invoices always have a linked PaymentInvoice — redirect to the unified premium page
        $paymentInvoiceId = data_get($invoice->metadata, 'payment_invoice_id');
        if ($paymentInvoiceId) {
            $paymentInvoice = PaymentInvoice::find($paymentInvoiceId);
            if ($paymentInvoice) {
                return redirect()->route('dashboard.payments.show', $paymentInvoice);
            }
        }

        $invoice->load('template');

        return view('templates.invoices.show', compact('invoice'));
    }

    public function check(Request $request, TemplatePaymentInvoice $invoice, OxaPayService $oxaPay, BotTemplatePurchaseService $purchases): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin() || $invoice->user_id === $request->user()?->id, 403);

        if (! $invoice->track_id) {
            return back()->withErrors(['invoice' => 'Invoice not generated yet. Click Generate Invoice first.']);
        }

        $result = $oxaPay->getPaymentStatus($invoice->track_id);

        if (! $result['ok']) {
            return back()->withErrors(['invoice' => $result['message'] ?? 'Unable to check payment status.']);
        }

        $status = $result['status'] ?? 'pending';
        $invoice->forceFill(['status' => $status, 'raw_response' => $result['raw'] ?? $result])->save();

        if ($status === 'paid' && ! $invoice->paid_at) {
            $invoice->forceFill(['paid_at' => now()])->save();
            $purchases->unlockFromInvoice($invoice->fresh());
        }

        return $invoice->fresh()->status === 'paid'
            ? redirect()->route('dashboard.templates.show', $invoice->bot_template_id)->with('status', 'Template unlocked. You can now import it.')
            : back()->with('status', 'Payment status updated: '.$invoice->fresh()->status.'.');
    }
}
