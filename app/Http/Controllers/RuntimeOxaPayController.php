<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Services\FaucetPayService;
use App\Services\OxaPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RuntimeOxaPayController extends Controller
{
    public function __invoke(Request $request, OxaPayService $oxapay, FaucetPayService $faucetPay): JsonResponse
    {
        $secret = (string) config('services.node_runtime.secret', '');

        if ($secret === '' || ! hash_equals($secret, (string) $request->header('X-Runtime-Secret', ''))) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized runtime OxaPay request.'], 401);
        }

        $bot = Bot::query()->find($request->integer('bot_id'));

        if (! $bot) {
            return response()->json(['ok' => false, 'error' => 'Bot not found.'], 404);
        }

        $action = (string) $request->input('action', '');
        $options = $request->input('options', []);
        $options = is_array($options) ? $options : [];

        try {
            $result = match ($action) {
                'secret.mask' => ['ok' => true, 'masked' => $this->maskSecret((string) ($options['value'] ?? ''))],
                'secret.status' => $this->secretStatus($bot, (string) ($options['key'] ?? '')),
                'faucetpay.keyStatus' => $faucetPay->keyStatus($bot),
                'faucetpay.balance' => $faucetPay->balance($bot, isset($options['currency']) ? (string) $options['currency'] : null),
                'faucetpay.getBalance' => $faucetPay->getBalanceWithKey(
                    isset($options['api_key']) ? (string) $options['api_key'] : null,
                    isset($options['currency']) ? (string) $options['currency'] : 'USDT',
                ),
                'faucetpay.send' => $faucetPay->send($bot, $options),
                'faucetpay.validateKey' => $faucetPay->validateKey($bot, isset($options['api_key']) ? (string) $options['api_key'] : null),
                'faucetpay.checkEmail' => $faucetPay->checkEmail($bot, (string) ($options['email'] ?? ''), isset($options['currency']) ? (string) $options['currency'] : null),
                'faucetpay.checkAddress' => $faucetPay->checkAddress($bot, (string) ($options['currency'] ?? 'USDT'), (string) ($options['address'] ?? '')),
                'faucetpay.supportedCurrencies' => ['ok' => true, 'currencies' => $faucetPay->supportedCurrencies()],
                'faucetpay.getCurrencies' => ['ok' => true, 'currencies' => $faucetPay->supportedCurrencies()],
                'faucetpay.isCurrencySupported' => [
                    'ok' => true,
                    'currency' => strtoupper((string) ($options['currency'] ?? '')),
                    'supported' => $faucetPay->isSupportedCurrency((string) ($options['currency'] ?? '')),
                ],
                'oxapay.createInvoice' => $oxapay->createInvoice($bot, $options),
                'oxapay.createWhiteLabel' => $oxapay->createWhiteLabel($bot, $options),
                'oxapay.createStaticAddress' => $oxapay->createStaticAddress($bot, $options),
                'oxapay.getPayment' => $oxapay->getPayment($bot, (string) ($request->input('track_id') ?? $options['track_id'] ?? $options['trackId'] ?? '')),
                'oxapay.payout' => $oxapay->payout($bot, $options),
                'oxapay.validateWebhook' => $oxapay->validateWebhook($bot, $options['payload'] ?? [], is_array($options['headers'] ?? null) ? $options['headers'] : []),
                'oxapay.validateKeys' => [
                    'ok' => true,
                    'merchant_configured' => filled($this->botRuntimeValue($bot, 'oxapay_merchant_api_key')),
                    'payout_configured' => filled($this->botRuntimeValue($bot, 'oxapay_payout_api_key')),
                ],
                'oxapay.supportedCurrencies' => ['ok' => true, 'currencies' => array_keys(OxaPayService::payCurrencyOptions())],
                default => ['ok' => false, 'error' => 'Unsupported OxaPay runtime action.'],
            };
        } catch (Throwable $exception) {
            Log::warning('[BotHost] OxaPay runtime bridge failed', [
                'bot_id' => $bot->id,
                'action' => $action,
                'error' => str($exception->getMessage())
                    ->replaceMatches('/(api[_-]?key|secret|token|password)=\S+/i', '$1=[redacted]')
                    ->limit(500, '')
                    ->toString(),
            ]);

            $result = ['ok' => false, 'error' => $exception->getMessage() ?: 'OxaPay request failed.'];
        }

        return response()->json($result);
    }

    private function secretStatus(Bot $bot, string $key): array
    {
        if (! in_array($key, ['faucetpay_api_key', 'oxapay_merchant_api_key', 'oxapay_payout_api_key'], true)) {
            return ['ok' => false, 'error' => 'Unsupported secret key'];
        }

        $masked = $this->botRuntimeValue($bot, $key.'_masked');
        $value = $this->botRuntimeValue($bot, $key);

        return [
            'ok' => true,
            'key' => $key,
            'configured' => filled($value),
            'masked' => filled($masked) ? (string) $masked : (filled($value) ? $this->maskSecret((string) $value) : null),
        ];
    }

    private function botRuntimeValue(Bot $bot, string $key): mixed
    {
        try {
            $row = \App\Models\BotRuntimeData::query()
                ->where('bot_id', $bot->id)
                ->where('key', $key)
                ->first(['value']);

            return $row ? $row->value : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function maskSecret(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (strlen($value) <= 8) {
            return substr($value, 0, 2).'***';
        }

        return substr($value, 0, 5).'***'.substr($value, -3);
    }
}
