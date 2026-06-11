<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\AuditLogService;
use App\Services\OxaPayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentSettingsController extends Controller
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function edit(OxaPayService $oxaPay): View
    {
        return view('admin.deposits.settings', [
            'maskedApiKey' => PlatformSetting::maskedValue('oxapay_merchant_api_key'),
            'baseUrl' => $oxaPay->baseUrl(),
            'enabled' => $oxaPay->enabled(),
            'feePaidByUser' => $oxaPay->feePaidByUser(),
            'sandbox' => $oxaPay->sandbox(),
            'invoiceLifetime' => $oxaPay->invoiceLifetime(),
            'underPaidCoverage' => PlatformSetting::getValue('oxapay_under_paid_coverage', ''),
            'publicCallbackBaseUrl' => $oxaPay->publicCallbackBaseUrl(),
            'webhookUrl' => $oxaPay->webhookUrl(),
            'providerConfigured' => filled($oxaPay->merchantApiKey()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'merchant_api_key' => ['nullable', 'string', 'max:500'],
            'base_url' => ['required', 'url', 'max:255', 'starts_with:https://'],
            'fee_paid_by_user' => ['nullable', 'boolean'],
            'oxapay_enabled' => ['nullable', 'boolean'],
            'sandbox' => ['nullable', 'boolean'],
            'invoice_lifetime' => ['nullable', 'integer', 'min:15', 'max:2880'],
            'under_paid_coverage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if (filled($data['merchant_api_key'] ?? null)) {
            PlatformSetting::setValue('oxapay_merchant_api_key', $data['merchant_api_key'], true);
        }

        PlatformSetting::setValue('oxapay_base_url', $data['base_url']);
        PlatformSetting::setValue('oxapay_fee_paid_by_user', $request->boolean('fee_paid_by_user') ? '1' : '0');
        PlatformSetting::setValue('oxapay_enabled', $request->boolean('oxapay_enabled') ? '1' : '0');
        PlatformSetting::setValue('oxapay_sandbox', $request->boolean('sandbox') ? '1' : '0');
        PlatformSetting::setValue('oxapay_invoice_lifetime', filled($data['invoice_lifetime'] ?? null) ? (string) $data['invoice_lifetime'] : '60');

        if (filled($data['under_paid_coverage'] ?? null)) {
            PlatformSetting::setValue('oxapay_under_paid_coverage', (string) $data['under_paid_coverage']);
        } else {
            PlatformSetting::setValue('oxapay_under_paid_coverage', null);
        }

        $this->audit->log('payment', 'payment_settings.updated', 'Payment settings updated.', [
            'base_url' => $data['base_url'],
            'merchant_api_key_updated' => filled($data['merchant_api_key'] ?? null),
            'oxapay_enabled' => $request->boolean('oxapay_enabled'),
            'sandbox' => $request->boolean('sandbox'),
        ], $request->user());

        return back()->with('status', 'Payment settings saved.');
    }
}
