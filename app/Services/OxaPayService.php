<?php

namespace App\Services;

use App\Models\BotTemplate;
use App\Models\Bot;
use App\Models\BotRuntimeData;
use App\Models\PaymentInvoice;
use App\Models\PlatformSetting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Support\PublicCallbackUrl;

class OxaPayService
{
    private const INVOICE_PATH = '/v1/payment/invoice';
    private const WHITE_LABEL_PATH = '/v1/payment/white-label';
    private const STATIC_ADDRESS_PATH = '/v1/payment/static-address';
    private const STATUS_PATH = '/v1/payment/';
    private const PAYOUT_PATH = '/v1/payout';

    private const RUNTIME_SECRET_KEYS = [
        'oxapay_merchant_api_key',
        'oxapay_payout_api_key',
    ];

    // ─────────────────────────────────────────
    // Payment creation — white-label only
    // ─────────────────────────────────────────

    public function createWhiteLabelPayment(array $payload): array
    {
        $apiKey = $this->merchantApiKey();

        if (! $apiKey || ! $this->enabled()) {
            return ['ok' => false, 'message' => 'Crypto payments are not configured.'];
        }

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->asJson()
                ->withHeaders(['merchant_api_key' => $apiKey])
                ->post($this->baseUrl().self::WHITE_LABEL_PATH, $payload);

            $raw = $response->json() ?: [];
        } catch (Throwable $exception) {
            Log::warning('OxaPay white-label payment creation failed', ['error' => $exception->getMessage()]);

            return ['ok' => false, 'message' => 'Unable to create payment right now. Please try again.'];
        }

        if (! $response->successful() || (($raw['status'] ?? null) === false)) {
            return ['ok' => false, 'message' => $raw['message'] ?? 'Unable to create payment.', 'raw' => $raw];
        }

        return [
            'ok' => true,
            ...$this->normalizeWhiteLabelResponse($raw),
            'raw' => $raw,
        ];
    }

    public function createTemplatePayment(User $user, BotTemplate $template, PaymentInvoice $invoice, string $payCurrency): array
    {
        [$apiPayCurrency, $apiNetwork] = $this->resolvePayCurrencyAndNetwork($payCurrency);

        return $this->createWhiteLabelPayment(array_filter([
            'pay_currency'       => $apiPayCurrency,
            'network'            => $apiNetwork,
            'amount'             => (float) $template->price,
            'currency'           => $template->currency ?: 'USD',
            'lifetime'           => $this->invoiceLifetime(),
            'fee_paid_by_payer'  => $this->feePaidByUser() ? 1 : 0,
            'under_paid_coverage'=> $this->underPaidCoverage(),
            'callback_url'       => $this->publicUrl('/webhooks/oxapay'),
            'email'              => filled($user->email) ? $user->email : null,
            'order_id'           => $invoice->order_id,
            'description'        => 'Template purchase: '.$template->name,
        ], fn (mixed $v) => $v !== null && $v !== ''));
    }

    public function createSubscriptionPayment(User $user, SubscriptionPlan $plan, PaymentInvoice $invoice, string $payCurrency): array
    {
        [$apiPayCurrency, $apiNetwork] = $this->resolvePayCurrencyAndNetwork($payCurrency);

        return $this->createWhiteLabelPayment(array_filter([
            'pay_currency'       => $apiPayCurrency,
            'network'            => $apiNetwork,
            'amount'             => (float) $plan->price,
            'currency'           => $plan->currency ?: 'USD',
            'lifetime'           => $this->invoiceLifetime(),
            'fee_paid_by_payer'  => $this->feePaidByUser() ? 1 : 0,
            'under_paid_coverage'=> $this->underPaidCoverage(),
            'callback_url'       => $this->publicUrl('/webhooks/oxapay'),
            'email'              => filled($user->email) ? $user->email : null,
            'order_id'           => $invoice->order_id,
            'description'        => 'Plan upgrade: '.$plan->name,
        ], fn (mixed $v) => $v !== null && $v !== ''));
    }

    // ─────────────────────────────────────────
    // Status check
    // ─────────────────────────────────────────

    public function getPaymentStatus(string $trackId): array
    {
        $apiKey = $this->merchantApiKey();

        if (! $apiKey || ! $this->enabled()) {
            return ['ok' => false, 'message' => 'Crypto payments are not configured.'];
        }

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->withHeaders(['merchant_api_key' => $apiKey])
                ->get($this->baseUrl().self::STATUS_PATH.$trackId);

            $raw = $response->json() ?: [];
        } catch (Throwable $exception) {
            Log::warning('OxaPay status check failed', ['error' => $exception->getMessage()]);

            return ['ok' => false, 'message' => 'Unable to check payment status right now.'];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'message' => $raw['message'] ?? 'Unable to check payment status.', 'raw' => $raw];
        }

        return ['ok' => true, ...$this->normalizeWebhook($raw), 'raw' => $raw];
    }

    public function createInvoice(Bot $bot, array $options): array
    {
        return $this->merchantRequestForBot($bot, self::INVOICE_PATH, $this->normalizePaymentOptions($bot, $options), 'invoice');
    }

    public function createWhiteLabel(Bot $bot, array $options): array
    {
        return $this->merchantRequestForBot($bot, self::WHITE_LABEL_PATH, $this->normalizePaymentOptions($bot, $options), 'white_label');
    }

    public function createStaticAddress(Bot $bot, array $options): array
    {
        $payload = $this->cleanPayload([
            'currency' => strtoupper((string) ($options['currency'] ?? $this->botRuntimeValue($bot, 'oxapay_default_pay_currency', 'USDT'))),
            'network' => $options['network'] ?? null,
            'track_id' => $options['track_id'] ?? $options['trackId'] ?? null,
            'callback_url' => $options['callback_url'] ?? $this->botRuntimeValue($bot, 'oxapay_callback_url'),
        ]);

        return $this->merchantRequestForBot($bot, self::STATIC_ADDRESS_PATH, $payload, 'static_address');
    }

    public function getPayment(Bot $bot, string $trackId): array
    {
        $trackId = trim($trackId);

        if ($trackId === '') {
            return ['ok' => false, 'error' => 'track_id is required'];
        }

        return $this->merchantRequestForBot($bot, self::STATUS_PATH.rawurlencode($trackId), [], 'payment_info', 'GET');
    }

    public function payout(Bot $bot, array $options): array
    {
        $apiKey = $this->botRuntimeValue($bot, 'oxapay_payout_api_key');

        if (! filled($apiKey)) {
            return ['ok' => false, 'error' => 'OxaPay payout key not configured'];
        }

        $payload = $this->cleanPayload([
            'address' => $options['address'] ?? null,
            'currency' => strtoupper((string) ($options['currency'] ?? '')),
            'amount' => $this->normalizeDecimalAmount($options['amount'] ?? null),
            'network' => $options['network'] ?? null,
            'callback_url' => $options['callback_url'] ?? $this->botRuntimeValue($bot, 'oxapay_callback_url'),
            'memo' => $options['memo'] ?? null,
            'description' => $options['description'] ?? null,
        ]);

        return $this->postToOxaPay(self::PAYOUT_PATH, $payload, ['payout_api_key' => (string) $apiKey], 'payout');
    }

    public function validateWebhook(Bot $bot, array|string $payload, array $headers = []): array
    {
        $signature = $headers['HMAC']
            ?? $headers['hmac']
            ?? $headers['X-OxaPay-Signature']
            ?? $headers['x-oxapay-signature']
            ?? $headers['Oxapay-Signature']
            ?? $headers['oxapay-signature']
            ?? null;

        if (! $signature) {
            return [
                'ok' => true,
                'valid' => true,
                'verified' => false,
                'message' => 'No OxaPay signature header was provided; payload was not cryptographically verified.',
            ];
        }

        $apiKey = $this->botRuntimeValue($bot, 'oxapay_merchant_api_key');

        if (! filled($apiKey)) {
            return ['ok' => false, 'valid' => false, 'error' => 'OxaPay merchant API key not configured'];
        }

        $body = is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_SLASHES);
        $valid = hash_equals(hash_hmac('sha512', (string) $body, (string) $apiKey), (string) $signature)
            || hash_equals(hash_hmac('sha256', (string) $body, (string) $apiKey), (string) $signature);

        return ['ok' => true, 'valid' => $valid, 'verified' => true];
    }

    // ─────────────────────────────────────────
    // Webhook
    // ─────────────────────────────────────────

    public function normalizeWebhook(array $payload): array
    {
        $status = strtolower((string) (
            $payload['status']
            ?? $payload['payment_status']
            ?? $payload['paymentStatus']
            ?? data_get($payload, 'data.status')
            ?? data_get($payload, 'data.payment_status')
            ?? 'pending'
        ));

        return [
            'track_id'       => $payload['track_id'] ?? $payload['trackId'] ?? data_get($payload, 'data.track_id') ?? data_get($payload, 'data.trackId'),
            'order_id'       => $payload['order_id'] ?? $payload['orderId'] ?? data_get($payload, 'data.order_id') ?? data_get($payload, 'data.orderId'),
            'status'         => $this->normalizeStatus($status),
            'amount'         => $payload['amount'] ?? data_get($payload, 'data.amount'),
            'currency'       => $payload['currency'] ?? data_get($payload, 'data.currency'),
            'pay_currency'   => $payload['pay_currency'] ?? $payload['payCurrency'] ?? data_get($payload, 'data.pay_currency'),
            'pay_amount'     => $payload['pay_amount'] ?? data_get($payload, 'data.pay_amount'),
            'payment_address'=> $payload['address'] ?? $payload['payment_address'] ?? data_get($payload, 'data.address') ?? data_get($payload, 'data.payment_address'),
            'qr_code'        => $payload['qr_code'] ?? data_get($payload, 'data.qr_code'),
            'expires_at'     => $this->parseExpiration(
                $payload['expires_at'] ?? $payload['expired_at'] ?? $payload['expiration']
                ?? data_get($payload, 'data.expires_at') ?? data_get($payload, 'data.expired_at') ?? null
            ),
            'raw' => $payload,
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('HMAC')
            ?: $request->header('X-OxaPay-Signature')
            ?: $request->header('Oxapay-Signature');

        if (! $signature) {
            return true;
        }

        $apiKey = $this->merchantApiKey();

        if (! $apiKey) {
            Log::warning('OxaPay webhook HMAC header present but merchant API key is not configured.');

            return false;
        }

        $body = $request->getContent();

        return hash_equals(hash_hmac('sha512', $body, $apiKey), (string) $signature)
            || hash_equals(hash_hmac('sha256', $body, $apiKey), (string) $signature);
    }

    // ─────────────────────────────────────────
    // URL helpers
    // ─────────────────────────────────────────

    public function webhookUrl(): string
    {
        return $this->publicUrl('/webhooks/oxapay');
    }

    public function publicCallbackBaseUrl(): string
    {
        return PublicCallbackUrl::base();
    }

    // ─────────────────────────────────────────
    // Config getters
    // ─────────────────────────────────────────

    public static function payCurrencyOptions(): array
    {
        return CryptoNetworkOptions::options();
    }

    public function merchantApiKey(): ?string
    {
        $value = PlatformSetting::getValue('oxapay_merchant_api_key', config('oxapay.merchant_api_key'));

        return filled($value) ? (string) $value : null;
    }

    public function enabled(): bool
    {
        return filter_var(PlatformSetting::getValue('oxapay_enabled', config('oxapay.enabled', true)), FILTER_VALIDATE_BOOLEAN);
    }

    public function feePaidByUser(): bool
    {
        return filter_var(
            PlatformSetting::getValue('oxapay_fee_paid_by_user', config('oxapay.fee_paid_by_user', true)),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    public function sandbox(): bool
    {
        return filter_var(
            PlatformSetting::getValue('oxapay_sandbox', config('oxapay.sandbox', false)),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    public function invoiceLifetime(): int
    {
        $lifetime = (int) PlatformSetting::getValue('oxapay_invoice_lifetime', config('oxapay.invoice_lifetime', 60));

        return max(15, min(2880, $lifetime ?: 60));
    }

    public function underPaidCoverage(): ?float
    {
        $value = PlatformSetting::getValue('oxapay_under_paid_coverage', null);

        return filled($value) ? (float) $value : null;
    }

    public function baseUrl(): string
    {
        $url = rtrim((string) PlatformSetting::getValue('oxapay_base_url', config('oxapay.base_url', 'https://api.oxapay.com')), '/');

        if (! str_starts_with(strtolower($url), 'https://')) {
            Log::warning('[BotHost] Unsafe OxaPay base URL blocked; falling back to official HTTPS endpoint.', [
                'configured_scheme' => parse_url($url, PHP_URL_SCHEME),
            ]);

            return 'https://api.oxapay.com';
        }

        return $url;
    }

    // ─────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────

    private function merchantRequestForBot(Bot $bot, string $path, array $payload, string $type, string $method = 'POST'): array
    {
        if (! $this->botOxaPayEnabled($bot)) {
            return ['ok' => false, 'error' => 'OxaPay is not enabled for this bot'];
        }

        $apiKey = $this->botRuntimeValue($bot, 'oxapay_merchant_api_key');

        if (! filled($apiKey)) {
            return ['ok' => false, 'error' => 'OxaPay merchant API key not configured'];
        }

        return $method === 'GET'
            ? $this->getFromOxaPay($path, ['merchant_api_key' => (string) $apiKey], $type)
            : $this->postToOxaPay($path, $payload, ['merchant_api_key' => (string) $apiKey], $type);
    }

    private function postToOxaPay(string $path, array $payload, array $headers, string $type): array
    {
        try {
            Log::info('[BotHost] oxapay_runtime_request', [
                'type' => $type,
                'path' => $path,
                'amount' => $payload['amount'] ?? null,
                'currency' => $payload['currency'] ?? null,
                'pay_currency' => $payload['pay_currency'] ?? null,
            ]);

            $response = Http::timeout(20)
                ->acceptJson()
                ->asJson()
                ->withHeaders($headers)
                ->post($this->baseUrl().$path, $payload);

            return $this->normalizeRuntimeResponse($response->successful(), $response->json() ?: [], $type);
        } catch (Throwable $exception) {
            Log::warning('[BotHost] OxaPay runtime request failed', [
                'type' => $type,
                'error' => $this->safeLogMessage($exception->getMessage()),
            ]);

            return ['ok' => false, 'error' => 'Unable to contact OxaPay right now'];
        }
    }

    private function getFromOxaPay(string $path, array $headers, string $type): array
    {
        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->withHeaders($headers)
                ->get($this->baseUrl().$path);

            return $this->normalizeRuntimeResponse($response->successful(), $response->json() ?: [], $type);
        } catch (Throwable $exception) {
            Log::warning('[BotHost] OxaPay runtime status request failed', [
                'type' => $type,
                'error' => $this->safeLogMessage($exception->getMessage()),
            ]);

            return ['ok' => false, 'error' => 'Unable to contact OxaPay right now'];
        }
    }

    private function normalizeRuntimeResponse(bool $httpOk, array $raw, string $type): array
    {
        $data = is_array($raw['data'] ?? null) ? $raw['data'] : $raw;
        $status = $raw['status'] ?? null;
        $ok = $httpOk && ! (($raw['status'] ?? null) === false);

        if (is_numeric($status)) {
            $ok = $ok && ((int) $status >= 200 && (int) $status < 300);
        }

        $base = [
            'ok' => $ok,
            'message' => $raw['message'] ?? null,
            'error' => $ok ? null : ($raw['message'] ?? data_get($raw, 'error.message') ?? 'OxaPay request failed'),
            'track_id' => $data['track_id'] ?? $data['trackId'] ?? null,
            'order_id' => $data['order_id'] ?? $data['orderId'] ?? null,
            'status' => isset($data['status']) ? $this->normalizeStatus((string) $data['status']) : null,
            'amount' => filled($data['amount'] ?? null) ? (string) $data['amount'] : null,
            'currency' => $data['currency'] ?? null,
            'pay_amount' => filled($data['pay_amount'] ?? null) ? (string) $data['pay_amount'] : null,
            'pay_currency' => $data['pay_currency'] ?? null,
            'network' => $data['network'] ?? null,
            'address' => $data['address'] ?? $data['payment_address'] ?? null,
            'payment_address' => $data['payment_address'] ?? $data['address'] ?? null,
            'payment_url' => $data['payment_url'] ?? $data['paymentUrl'] ?? $data['invoice_url'] ?? $data['url'] ?? $raw['payment_url'] ?? $raw['url'] ?? null,
            'expired_at' => $this->parseExpiration($data['expired_at'] ?? $data['expires_at'] ?? null),
            'expires_at' => $this->parseExpiration($data['expires_at'] ?? $data['expired_at'] ?? null),
            'tx_hash' => $data['tx_hash'] ?? null,
            'fee' => filled($data['fee'] ?? null) ? (string) $data['fee'] : null,
            'raw' => $this->safeRaw($raw),
        ];

        return array_filter($base, fn (mixed $value) => $value !== null);
    }

    private function normalizePaymentOptions(Bot $bot, array $options): array
    {
        return $this->cleanPayload([
            'amount' => $this->normalizeDecimalAmount($options['amount'] ?? null),
            'currency' => strtoupper((string) ($options['currency'] ?? $this->botRuntimeValue($bot, 'oxapay_default_currency', 'USD'))),
            'pay_currency' => strtoupper((string) ($options['pay_currency'] ?? $options['payCurrency'] ?? $this->botRuntimeValue($bot, 'oxapay_default_pay_currency', 'USDT'))),
            'network' => $options['network'] ?? null,
            'order_id' => $options['order_id'] ?? $options['orderId'] ?? null,
            'description' => $options['description'] ?? null,
            'callback_url' => $options['callback_url'] ?? $this->botRuntimeValue($bot, 'oxapay_callback_url'),
            'success_url' => $options['success_url'] ?? $this->botRuntimeValue($bot, 'oxapay_success_url'),
            'return_url' => $options['return_url'] ?? $this->botRuntimeValue($bot, 'oxapay_return_url'),
            'email' => $options['email'] ?? null,
            'lifetime' => $options['lifetime'] ?? null,
            'fee_paid_by_payer' => $options['fee_paid_by_payer'] ?? $options['feePaidByPayer'] ?? null,
            'under_paid_coverage' => $options['under_paid_coverage'] ?? $options['underPaidCoverage'] ?? null,
            'to_currency' => $options['to_currency'] ?? $options['toCurrency'] ?? null,
            'auto_withdrawal' => $options['auto_withdrawal'] ?? $options['autoWithdrawal'] ?? null,
        ]);
    }

    private function normalizeDecimalAmount(mixed $value): string
    {
        $input = trim(str_replace(',', '', (string) ($value ?? '')));

        if ($input === '') {
            throw new \InvalidArgumentException('Amount is required.');
        }

        if (! preg_match('/^\+?(?:\d+|\d*\.\d+)$/', $input)) {
            throw new \InvalidArgumentException('Invalid amount.');
        }

        $input = ltrim($input, '+');
        [$whole, $fraction] = array_pad(explode('.', $input, 2), 2, '');
        $whole = ltrim($whole, '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = rtrim($fraction, '0');

        if ($whole === '0' && $fraction === '') {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }

        return $fraction !== '' ? $whole.'.'.$fraction : $whole;
    }

    private function cleanPayload(array $payload): array
    {
        return array_filter($payload, fn (mixed $value) => $value !== null && $value !== '');
    }

    private function botOxaPayEnabled(Bot $bot): bool
    {
        return filter_var($this->botRuntimeValue($bot, 'oxapay_enabled', true), FILTER_VALIDATE_BOOLEAN);
    }

    private function botRuntimeValue(Bot $bot, string $key, mixed $default = null): mixed
    {
        try {
            $row = BotRuntimeData::query()
                ->where('bot_id', $bot->id)
                ->where('key', $key)
                ->first(['value']);

            return $row ? $row->value : $default;
        } catch (Throwable) {
            return $default;
        }
    }

    private function safeRaw(array $raw): array
    {
        $clean = [];

        foreach ($raw as $key => $value) {
            if (preg_match('/(api[_-]?key|secret|token|password|private)/i', (string) $key)) {
                $clean[$key] = '[redacted]';
                continue;
            }

            $clean[$key] = is_array($value) ? $this->safeRaw($value) : $value;
        }

        return $clean;
    }

    private function safeLogMessage(string $message): string
    {
        return str($message)
            ->replaceMatches('/(merchant_api_key|payout_api_key|api[_-]?key|secret|token)=\S+/i', '$1=[redacted]')
            ->limit(500, '')
            ->toString();
    }

    private function normalizeWhiteLabelResponse(array $raw): array
    {
        $data = is_array($raw['data'] ?? null) ? $raw['data'] : $raw;

        return [
            'track_id'        => $data['track_id'] ?? $data['trackId'] ?? null,
            'order_id'        => $data['order_id'] ?? $data['orderId'] ?? null,
            'payment_address' => $data['address'] ?? $data['payment_address'] ?? null,
            'pay_amount'      => filled($data['pay_amount'] ?? null) ? (string) $data['pay_amount'] : null,
            'pay_currency'    => $data['pay_currency'] ?? null,
            'network'         => $data['network'] ?? null,
            'qr_code'         => $data['qr_code'] ?? null,
            // top-level status is HTTP code; data.status is the payment status
            'status'          => $this->normalizeStatus((string) ($data['status'] ?? 'waiting')),
            'expires_at'      => $this->parseExpiration($data['expired_at'] ?? $data['expires_at'] ?? null),
            'rate'            => $data['rate'] ?? null,
        ];
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower($status);

        return match ($status) {
            'paid', 'completed', 'complete', 'finished', 'success', 'successful' => 'paid',
            'confirming', 'confirmed' => 'confirming',
            'paying', 'waiting', 'pending' => $status,
            'expired' => 'expired',
            'failed' => 'failed',
            'cancelled', 'canceled', 'cancel' => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * Resolve a UI key into the [pay_currency, network] pair
     * expected by the OxaPay white-label API.
     *
     * @return array{0: string, 1: string|null}
     */
    private function resolvePayCurrencyAndNetwork(string $payCurrency): array
    {
        $resolved = CryptoNetworkOptions::normalize($payCurrency);

        return [$resolved['pay_currency'], $resolved['network']];
    }

    private function parseExpiration(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return now()->setTimestamp((int) $value)->toDateTimeString();
        }

        try {
            return \Carbon\Carbon::parse((string) $value)->toDateTimeString();
        } catch (Throwable) {
            return null;
        }
    }

    private function publicUrl(string $path): string
    {
        return PublicCallbackUrl::to($path);
    }

    private function appUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }
}
