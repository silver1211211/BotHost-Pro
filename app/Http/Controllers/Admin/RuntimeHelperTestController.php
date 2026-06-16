<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RuntimeHelper;
use App\Models\RuntimeHelperVersion;
use App\Services\AuditLogService;
use App\Services\RuntimeHelperTester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RuntimeHelperTestController extends Controller
{
    public function __construct(
        private readonly RuntimeHelperTester $tester,
        private readonly AuditLogService $audit,
    ) {}

    public function run(Request $request): JsonResponse
    {
        $data = $request->validate([
            'helper_id' => ['nullable', 'exists:runtime_helpers,id'],
            'version_id' => ['nullable', 'exists:runtime_helper_versions,id'],
            'name' => ['nullable', 'string', 'max:100'],
            'helper_type' => ['required', 'string', 'max:50'],
            'code' => ['required', 'string'],
            'params' => ['nullable', 'string'],
            'expected_output' => ['nullable', 'string'],
            'allowed_domains' => ['nullable'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $helper = isset($data['helper_id']) ? RuntimeHelper::query()->find($data['helper_id']) : null;
        $version = isset($data['version_id']) ? RuntimeHelperVersion::query()->find($data['version_id']) : null;

        if ($helper && ! $version) {
            $version = $helper->versions()->where('status', 'draft')->latest('version_number')->first()
                ?: $helper->activeVersion;
        }

        if ($version && $helper && (int) $version->helper_id !== (int) $helper->id) {
            throw ValidationException::withMessages(['version_id' => 'Selected version does not belong to this helper.']);
        }

        $params = $this->jsonInput($data['params'] ?? null, 'params') ?? [];
        $expected = $this->jsonInput($data['expected_output'] ?? null, 'expected_output');
        $dryRun = $request->boolean('dry_run', true);

        $result = $this->tester->run(
            code: $data['code'],
            params: $params,
            dryRun: $dryRun,
            helper: $helper,
            version: $version,
            expectedOutput: $expected,
            runner: $request->user(),
            helperType: $data['helper_type'],
            allowedDomains: $this->allowedDomains($request->input('allowed_domains')),
        );

        if ($helper) {
            $helper->update([
                'last_test_status' => $result['status'],
                'last_test_error' => $result['error'],
                'last_tested_at' => now(),
                'updated_by' => $request->user()->id,
            ]);
        }

        if ($version) {
            $version->update([
                'test_status' => $result['status'],
                'test_error' => $result['error'],
            ]);
        }

        $this->audit->log('runtime', 'runtime.helper.tested', 'Runtime helper tested.', [
            'helper_id' => $helper?->id,
            'version_id' => $version?->id,
            'helper_name' => $helper?->name ?? ($data['name'] ?? null),
            'status' => $result['status'],
            'dry_run' => $dryRun,
            'execution_ms' => $result['execution_ms'] ?? null,
        ], $request->user(), ($result['ok'] ?? false) ? 'success' : 'failed', $helper);

        return response()->json([
            'ok' => (bool) ($result['ok'] ?? false),
            'status' => $result['status'],
            'actual_output' => $result['actual_output'],
            'error' => $result['error'],
            'execution_ms' => $result['execution_ms'],
        ]);
    }

    private function jsonInput(?string $value, string $field): ?array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw ValidationException::withMessages([$field => 'Enter valid JSON.']);
        }

        return $decoded;
    }

    private function allowedDomains(mixed $value): array
    {
        if (is_array($value)) {
            $items = $value;
        } else {
            $text = trim((string) $value);
            $decoded = str_starts_with($text, '[') ? json_decode($text, true) : null;
            $items = is_array($decoded) ? $decoded : preg_split('/[\r\n,]+/', $text);
        }

        return collect($items ?: [])
            ->map(fn ($domain) => strtolower(trim((string) $domain)))
            ->map(fn ($domain) => preg_replace('#^https?://#', '', $domain))
            ->map(fn ($domain) => rtrim((string) $domain, '/'))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
