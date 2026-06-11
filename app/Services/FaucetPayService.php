<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotRuntimeData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FaucetPayService
{
    private const BASE_URL = 'https://faucetpay.io/api/v1';

    private const SUPPORTED_CURRENCIES = [
        'BTC', 'ETH', 'DOGE', 'LTC', 'BCH', 'DASH', 'DGB', 'TRX', 'USDT',
        'FEY', 'ZEC', 'BNB', 'SOL', 'XRP', 'MATIC', 'ADA', 'TON', 'USDC',
    ];

    public function balance(Bot $bot, ?string $currency = null): array
    {
        $apiKey = $this->apiKey($bot);

        if (! filled($apiKey)) {
            return ['ok' => false, 'error' => 'FaucetPay API key not configured'];
        }

        $symbol = $currency !== null ? $this->normalizeCurrency($currency) : null;
        if ($symbol !== null && ! $this->isSupportedCurrency($symbol)) {
            return ['ok' => false, 'error' => 'Unsupported FaucetPay currency', 'currency' => $symbol];
        }

        $raw = $this->post('/getbalance', array_filter([
            'api_key' => $apiKey,
            'currency' => $symbol,
        ]));

        if (! ($raw['ok'] ?? false)) {
            return $raw;
        }

        $balance = $this->parseBalance($raw['raw'] ?? [], $symbol);

        return array_filter([
            'ok' => true,
            'status' => $raw['raw']['status'] ?? 200,
            'message' => $raw['raw']['message'] ?? '',
            'currency' => $symbol,
            'balance' => $balance,
            'raw' => $this->safeRaw($raw['raw'] ?? []),
        ], fn (mixed $value) => $value !== null);
    }

    public function send(Bot $bot, array $options): array
    {
        $apiKey = $this->apiKey($bot);

        if (! filled($apiKey)) {
            return ['ok' => false, 'error' => 'FaucetPay API key not configured'];
        }

        $currency = $this->normalizeCurrency($options['currency'] ?? '');
        if (! $this->isSupportedCurrency($currency)) {
            return ['ok' => false, 'error' => 'Unsupported FaucetPay currency', 'currency' => $currency];
        }

        $to = trim((string) ($options['to'] ?? $options['email'] ?? $options['address'] ?? ''));
        if ($to === '') {
            return ['ok' => false, 'error' => 'FaucetPay recipient is required'];
        }

        $amount = $this->normalizeDecimalAmount($options['amount'] ?? null);
        $smallest = $this->toSmallestUnit($amount, 8);
        $raw = $this->post('/send', [
            'api_key' => $apiKey,
            'to' => $to,
            'amount' => $smallest,
            'currency' => $currency,
        ]);

        if (! ($raw['ok'] ?? false)) {
            return $raw;
        }

        $response = $raw['raw'] ?? [];
        $ok = (int) ($response['status'] ?? 0) === 200;

        return [
            'ok' => $ok,
            'status' => $response['status'] ?? 0,
            'message' => $response['message'] ?? ($ok ? 'OK' : 'FaucetPay request failed'),
            'currency' => $currency,
            'amount' => $amount,
            'amount_smallest_unit' => $smallest,
            'raw' => $this->safeRaw($response),
        ];
    }

    public function checkEmail(Bot $bot, string $email, ?string $currency = null): array
    {
        return $this->checkAddress($bot, $currency ?: 'USDT', $email);
    }

    public function checkAddress(Bot $bot, string $currency, string $address): array
    {
        $apiKey = $this->apiKey($bot);

        if (! filled($apiKey)) {
            return ['ok' => false, 'error' => 'FaucetPay API key not configured'];
        }

        $symbol = $this->normalizeCurrency($currency);
        if (! $this->isSupportedCurrency($symbol)) {
            return ['ok' => false, 'error' => 'Unsupported FaucetPay currency', 'currency' => $symbol];
        }

        $raw = $this->post('/checkaddress', [
            'api_key' => $apiKey,
            'currency' => $symbol,
            'address' => trim($address),
        ]);

        if (! ($raw['ok'] ?? false)) {
            return $raw;
        }

        $response = $raw['raw'] ?? [];
        $ok = (int) ($response['status'] ?? 0) === 200;

        return [
            'ok' => $ok,
            'status' => $response['status'] ?? 0,
            'message' => $response['message'] ?? ($ok ? 'OK' : 'FaucetPay request failed'),
            'currency' => $symbol,
            'raw' => $this->safeRaw($response),
        ];
    }

    public function validateKey(Bot $bot, ?string $apiKey = null): array
    {
        $key = filled($apiKey) ? (string) $apiKey : $this->apiKey($bot);

        if (! filled($key)) {
            return ['ok' => false, 'valid' => false, 'error' => 'FaucetPay API key not configured'];
        }

        $raw = $this->post('/getbalance', ['api_key' => $key, 'currency' => 'USDT']);
        $response = $raw['raw'] ?? [];
        $valid = (bool) ($raw['ok'] ?? false) && (int) ($response['status'] ?? 0) === 200;

        return [
            'ok' => true,
            'valid' => $valid,
            'status' => $response['status'] ?? 0,
            'message' => $valid ? 'FaucetPay API key is valid' : ($response['message'] ?? 'FaucetPay API key validation failed'),
        ];
    }

    public function keyStatus(Bot $bot): array
    {
        $masked = $this->runtimeValue($bot, 'faucetpay_api_key_masked');

        return [
            'ok' => true,
            'configured' => filled($this->apiKey($bot)),
            'masked' => filled($masked) ? (string) $masked : null,
        ];
    }

    public function supportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    public function isSupportedCurrency(string $currency): bool
    {
        return in_array($this->normalizeCurrency($currency), self::SUPPORTED_CURRENCIES, true);
    }

    public function toSmallestUnit(string|int|float $amount, int $decimals = 8): string
    {
        $decimal = $this->normalizeDecimalAmount($amount, allowZero: true);
        [$whole, $fraction] = array_pad(explode('.', $decimal, 2), 2, '');
        $fraction = str_pad(substr($fraction, 0, $decimals), $decimals, '0');

        return ltrim($whole.$fraction, '0') ?: '0';
    }

    public function fromSmallestUnit(string|int|float $amount, int $decimals = 8): string
    {
        $digits = preg_replace('/\D/', '', (string) $amount) ?: '0';
        $digits = str_pad($digits, $decimals + 1, '0', STR_PAD_LEFT);
        $whole = substr($digits, 0, -$decimals) ?: '0';
        $fraction = rtrim(substr($digits, -$decimals), '0');

        return $fraction === '' ? $whole : $whole.'.'.$fraction;
    }

    private function apiKey(Bot $bot): ?string
    {
        $value = $this->runtimeValue($bot, 'faucetpay_api_key');

        return filled($value) ? (string) $value : null;
    }

    private function runtimeValue(Bot $bot, string $key): mixed
    {
        try {
            $row = BotRuntimeData::query()
                ->where('bot_id', $bot->id)
                ->where('key', $key)
                ->first(['value']);

            return $row ? $row->value : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function post(string $path, array $form): array
    {
        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(20)
                ->post(self::BASE_URL.$path, $form);

            return ['ok' => $response->successful(), 'raw' => $response->json() ?: []];
        } catch (Throwable $exception) {
            Log::warning('[BotHost] FaucetPay backend request failed', [
                'path' => $path,
                'error' => $this->safeMessage($exception->getMessage()),
            ]);

            return ['ok' => false, 'error' => 'Unable to contact FaucetPay right now'];
        }
    }

    private function parseBalance(array $raw, ?string $currency): float
    {
        $symbol = $currency ?: '';
        $candidates = [
            $raw['balance'] ?? null,
            $raw['balances'][$symbol] ?? null,
            $raw['data']['balance'] ?? null,
            $raw['data']['balances'][$symbol] ?? null,
            $raw[$symbol] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }

            if (is_array($candidate)) {
                $candidate = $candidate['balance'] ?? $candidate['available'] ?? $candidate['amount'] ?? null;
            }

            if ($candidate !== null && is_numeric($candidate)) {
                return (float) $candidate;
            }
        }

        return 0.0;
    }

    private function normalizeCurrency(mixed $currency): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $currency));
    }

    private function normalizeDecimalAmount(mixed $value, bool $allowZero = false): string
    {
        $input = trim(str_replace(',', '', (string) ($value ?? '')));

        if ($input === '' || ! preg_match('/^\+?(?:\d+|\d*\.\d+)$/', $input)) {
            throw new \InvalidArgumentException('Invalid amount.');
        }

        $input = ltrim($input, '+');
        [$whole, $fraction] = array_pad(explode('.', $input, 2), 2, '');
        $whole = ltrim($whole, '0') ?: '0';
        $fraction = rtrim($fraction, '0');

        if (! $allowZero && $whole === '0' && $fraction === '') {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }

        return $fraction !== '' ? $whole.'.'.$fraction : $whole;
    }

    private function safeRaw(array $raw): array
    {
        foreach ($raw as $key => $value) {
            if (preg_match('/(api[_-]?key|secret|token|password|private)/i', (string) $key)) {
                $raw[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $raw[$key] = $this->safeRaw($value);
            }
        }

        return $raw;
    }

    private function safeMessage(string $message): string
    {
        return str($message)
            ->replaceMatches('/(api[_-]?key|secret|token|password)=\S+/i', '$1=[redacted]')
            ->limit(500, '')
            ->toString();
    }
}
