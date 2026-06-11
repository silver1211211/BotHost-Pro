<?php

namespace App\Http\Controllers;

use App\Models\PaymentInvoice;
use App\Models\TemplatePaymentInvoice;
use App\Services\BotTemplatePurchaseService;
use App\Services\OxaPayService;
use App\Services\PaymentInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class OxaPayWebhookController extends Controller
{
    public function handle(Request $request, OxaPayService $oxaPay, PaymentInvoiceService $payments, BotTemplatePurchaseService $templatePurchases): Response
    {
        try {
            if (! $oxaPay->verifyWebhookSignature($request)) {
                return response('invalid signature', 403);
            }

            $payload = $request->json()->all() ?: $request->all();
            $normalized = $oxaPay->normalizeWebhook($payload);

            $invoice = PaymentInvoice::query()
                ->when($normalized['track_id'] ?? null, fn ($query, $trackId) => $query->where('track_id', $trackId))
                ->when(! ($normalized['track_id'] ?? null) && ($normalized['order_id'] ?? null), fn ($query) => $query->where('order_id', $normalized['order_id']))
                ->first();

            if ($invoice) {
                $payments->applyProviderStatus($invoice, $normalized);
            } else {
                $legacyInvoice = TemplatePaymentInvoice::query()
                    ->when($normalized['track_id'] ?? null, fn ($query, $trackId) => $query->where('track_id', $trackId))
                    ->when(! ($normalized['track_id'] ?? null) && ($normalized['order_id'] ?? null), fn ($query) => $query->where('order_id', $normalized['order_id']))
                    ->first();

                if ($legacyInvoice) {
                    $status = $normalized['status'] ?? 'pending';
                    $legacyInvoice->forceFill(['status' => $status, 'raw_response' => $payload])->save();

                    if ($status === 'paid' && ! $legacyInvoice->paid_at) {
                        $legacyInvoice->forceFill(['paid_at' => now()])->save();
                        $templatePurchases->unlockFromInvoice($legacyInvoice->fresh());
                    }
                }
            }
        } catch (Throwable $exception) {
            Log::warning('OxaPay webhook failed', ['error' => $exception->getMessage()]);
        }

        return response('ok', 200);
    }
}
