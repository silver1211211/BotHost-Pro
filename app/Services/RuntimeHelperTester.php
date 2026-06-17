<?php

namespace App\Services;

use App\Models\RuntimeHelper;
use App\Models\RuntimeHelperTest;
use App\Models\RuntimeHelperVersion;
use App\Models\User;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class RuntimeHelperTester
{
    public function __construct(private readonly RuntimeHelperSafetyScanner $scanner) {}

    public function run(
        string $code,
        array $params = [],
        bool $dryRun = true,
        ?RuntimeHelper $helper = null,
        ?RuntimeHelperVersion $version = null,
        ?array $expectedOutput = null,
        ?User $runner = null,
        ?string $helperType = null,
        ?array $allowedDomains = null,
    ): array {
        $started = microtime(true);

        $blocked = $this->preflight($code, $dryRun, $helper, $helperType, $allowedDomains);
        if ($blocked !== null) {
            return $this->finish($blocked, $started, $helper, $version, $params, $expectedOutput, $dryRun, $runner);
        }

        $path = tempnam(sys_get_temp_dir(), 'bothost_helper_test_');
        if ($path === false) {
            return $this->finish($this->failure('Could not create temporary helper test file.'), $started, $helper, $version, $params, $expectedOutput, $dryRun, $runner);
        }

        $jsPath = $path.'.js';
        rename($path, $jsPath);

        try {
            file_put_contents($jsPath, $this->script($code, $params));

            $process = new Process([$this->nodeBinary(), $jsPath]);
            $process->setTimeout(8);
            $process->run();

            $output = trim($process->getOutput());
            $errorOutput = trim($process->getErrorOutput());

            if (! $process->isSuccessful()) {
                $result = $this->failure(Str::limit($errorOutput ?: $output ?: 'Helper test execution failed.', 2000, ''));
            } else {
                $result = $this->parseNodeResult($output);
            }

            if (($result['ok'] ?? false) && $expectedOutput !== null && $result['actual_output'] !== $expectedOutput) {
                $result = [
                    'ok' => false,
                    'status' => 'failed',
                    'actual_output' => $result['actual_output'] ?? null,
                    'error' => 'Actual output did not match expected output.',
                ];
            }

            return $this->finish($result, $started, $helper, $version, $params, $expectedOutput, $dryRun, $runner);
        } finally {
            if (is_file($jsPath)) {
                @unlink($jsPath);
            }
        }
    }

    private function preflight(string $code, bool $dryRun, ?RuntimeHelper $helper, ?string $helperType, ?array $allowedDomains): ?array
    {
        $type = strtolower((string) ($helperType ?: $helper?->helper_type));

        if (in_array($type, ['payment', 'faucetpay', 'oxapay'], true) && ! $dryRun) {
            return $this->failure('Real payment tests are disabled in private beta.');
        }

        $domains = $allowedDomains ?? ($helper?->allowed_domains ?? []);

        if ($type === 'external_api' && $domains === []) {
            return $this->failure('External API helpers must define allowed domains before testing.');
        }

        $scan = $this->scanner->scan($code);
        if (! ($scan['passed'] ?? false)) {
            return $this->failure('Safety scan failed: '.($scan['blocked'] ?? 'blocked pattern').' is not allowed.');
        }

        $syntax = $this->scanner->syntaxCheck($code);
        if (! ($syntax['passed'] ?? false)) {
            return $this->failure('Syntax check failed: '.($syntax['error'] ?? 'Invalid JavaScript syntax.'));
        }

        return null;
    }

    private function finish(array $result, float $started, ?RuntimeHelper $helper, ?RuntimeHelperVersion $version, array $params, ?array $expectedOutput, bool $dryRun, ?User $runner): array
    {
        $executionMs = (int) round((microtime(true) - $started) * 1000);
        $result['execution_ms'] = $executionMs;
        $result['status'] = ($result['ok'] ?? false) ? 'passed' : 'failed';
        $result['error'] = $result['error'] ?? null;
        $result['actual_output'] = $result['actual_output'] ?? null;

        if ($helper !== null) {
            RuntimeHelperTest::query()->create([
                'helper_id' => $helper->id,
                'version_id' => $version?->id,
                'test_name' => 'Default Test',
                'input_payload' => $params,
                'expected_output' => $expectedOutput,
                'actual_output' => $result['actual_output'],
                'status' => $result['status'],
                'error' => $result['error'],
                'execution_ms' => $executionMs,
                'dry_run' => $dryRun,
                'run_by' => $runner?->id,
                'ran_at' => now(),
            ]);
        }

        return $result;
    }

    private function parseNodeResult(string $output): array
    {
        foreach (array_reverse(explode("\n", $output)) as $line) {
            $line = trim($line);
            if (! str_starts_with($line, '__BOTHOST_RESULT__')) {
                continue;
            }

            $decoded = json_decode(substr($line, strlen('__BOTHOST_RESULT__')), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $this->failure('Helper test did not return a readable result.');
    }

    private function failure(string $error): array
    {
        return [
            'ok' => false,
            'status' => 'failed',
            'actual_output' => null,
            'error' => $error,
        ];
    }

    private function script(string $code, array $params): string
    {
        $paramsJson = json_encode($params, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return "'use strict';\n"
            ."const __testParams = {$paramsJson};\n"
            ."const __botData = new Map(Object.entries({ balance: 1000, referral_reward: 100 }));\n"
            ."const __userData = new Map(Object.entries({ balance: 500, banned: false }));\n"
            ."const __sent = [];\n"
            ."const __ok = (action, payload = {}) => ({ ok: true, dry_run: true, action, ...payload });\n"
            ."const sendMessage = async (chatIdOrText, textOrOptions = undefined, options = {}) => { const explicit = typeof textOrOptions === 'string'; const text = explicit ? textOrOptions : chatIdOrText; const opts = explicit ? options : (textOrOptions || {}); __sent.push({ type: 'message', chat_id: explicit ? chatIdOrText : null, text, options: opts }); return __ok('sendMessage', { text, options: opts }); };\n"
            ."const sendPhoto = async (chatId, photo, options = {}) => { __sent.push({ type: 'photo', chat_id: chatId, photo, options }); return __ok('sendPhoto', { chat_id: chatId, photo, options }); };\n"
            ."const editMessageText = async (...args) => { __sent.push({ type: 'edit_message_text', args }); return __ok('editMessageText', { args }); };\n"
            ."const answerCallbackQuery = async (text = '', options = {}) => __ok('answerCallbackQuery', { text, options });\n"
            ."const notifyUser = async (userId, text, options = {}) => { __sent.push({ type: 'notify_user', user_id: userId, text, options }); return __ok('notifyUser', { user_id: userId, text, options }); };\n"
            ."const reply = async (text, options = {}) => sendMessage(text, options);\n"
            ."const getUserData = async (key, fallback = null) => __userData.has(String(key)) ? __userData.get(String(key)) : fallback;\n"
            ."const setUserData = async (key, value) => { __userData.set(String(key), value); return __ok('setUserData', { key, value }); };\n"
            ."const getBotData = async (key, fallback = null) => __botData.has(String(key)) ? __botData.get(String(key)) : fallback;\n"
            ."const setBotData = async (key, value) => { __botData.set(String(key), value); return __ok('setBotData', { key, value }); };\n"
            ."const getBalance = async () => Number(await getUserData('balance', 0));\n"
            ."const setBalance = async (value) => setUserData('balance', Number(value));\n"
            ."const addBalance = async (amount) => { const next = Number(await getUserData('balance', 0)) + Number(amount || 0); await setUserData('balance', next); return next; };\n"
            ."const removeBalance = async (amount) => { const next = Number(await getUserData('balance', 0)) - Number(amount || 0); await setUserData('balance', next); return next; };\n"
            ."const faucetPayValidateKey = async () => __ok('faucetPayValidateKey', { valid: true });\n"
            ."const faucetPayCheckEmail = async (email) => __ok('faucetPayCheckEmail', { email, valid: true });\n"
            ."const faucetPaySend = async () => __ok('faucetPaySend', { skipped: true });\n"
            ."const oxapayPayout = async () => __ok('oxapayPayout', { skipped: true });\n"
            ."const sendWithdrawalChannelNotice = async () => __ok('sendWithdrawalChannelNotice', { skipped: true });\n"
            ."const delay = async () => true;\n"
            ."const now = () => '2026-06-15T00:00:00.000Z';\n"
            ."const nowMs = () => 1781481600000;\n"
            ."const formatNumber = (value, decimals = 0) => Number(value || 0).toLocaleString('en-US', { maximumFractionDigits: decimals });\n"
            ."const formatMoney = (value, currency = 'USD') => formatNumber(value, 2) + ' ' + currency;\n"
            ."const formatCryptoAmount = (value, currency = 'USDT') => formatNumber(value, 8) + ' ' + currency;\n"
            ."async function __helperUnderTest(params) {\n"
            .$code."\n"
            ."}\n"
            ."(async () => {\n"
            ."  try {\n"
            ."    const __testResult = await __helperUnderTest(__testParams);\n"
            ."    console.log('__BOTHOST_RESULT__' + JSON.stringify({ ok: true, status: 'passed', actual_output: { ok: true, helper_result: __testResult }, error: null }));\n"
            ."  } catch (err) {\n"
            ."    console.log('__BOTHOST_RESULT__' + JSON.stringify({ ok: false, status: 'failed', actual_output: null, error: String((err && err.message) || err) }));\n"
            ."  }\n"
            ."})();\n";
    }

    private function nodeBinary(): string
    {
        $binary = (string) config('runtime.node_binary', 'node');

        if ($binary !== 'node' || DIRECTORY_SEPARATOR !== '\\') {
            return $binary;
        }

        $paths = [];
        @exec('where node 2>NUL', $paths);

        usort($paths, fn (string $a, string $b): int => (int) str_contains($a, ' ') <=> (int) str_contains($b, ' '));

        foreach ($paths as $path) {
            $path = trim($path);

            if ($path !== '' && str_ends_with(strtolower($path), '.exe') && is_file($path)) {
                return $path;
            }
        }

        return $binary;
    }
}
