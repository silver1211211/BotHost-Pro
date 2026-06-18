<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RuntimeHelper;
use App\Models\RuntimeHelperCategory;
use App\Models\RuntimeHelperType;
use App\Models\RuntimeHelperVersion;
use App\Services\AuditLogService;
use App\Services\RuntimeHelperSafetyScanner;
use App\Support\RuntimeHelperProtectedNames;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RuntimeHelperController extends Controller
{
    public function __construct(
        private readonly RuntimeHelperSafetyScanner $scanner,
        private readonly AuditLogService $audit,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'category_id', 'helper_type', 'status', 'last_test_status', 'requires_runtime_reload']);

        $helpers = RuntimeHelper::query()
            ->with(['category', 'type', 'activeVersion'])
            ->withMax('versions', 'version_number')
            ->when(filled($filters['search'] ?? null), fn ($query) => $query->where(function ($query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->when(filled($filters['category_id'] ?? null), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when(filled($filters['helper_type'] ?? null), fn ($query) => $query->where('helper_type', $filters['helper_type']))
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->when(filled($filters['last_test_status'] ?? null), fn ($query) => $query->where('last_test_status', $filters['last_test_status']))
            ->when(($filters['requires_runtime_reload'] ?? '') !== '', fn ($query) => $query->where('requires_runtime_reload', (bool) $filters['requires_runtime_reload']))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.runtime.helpers.index', [
            'helpers' => $helpers,
            'categories' => RuntimeHelperCategory::query()->orderBy('name')->get(),
            'helperTypes' => $this->helperTypesForSelect(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.runtime.helpers.create', [
            'helper' => new RuntimeHelper(),
            'categories' => $this->activeCategories(),
            'helperTypes' => $this->helperTypesForSelect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if (RuntimeHelper::query()->where('name', $data['name'])->exists()) {
            return back()->withInput()->withErrors([
                'name' => 'A helper with this name already exists. Edit the existing helper and save a new version instead.',
            ]);
        }

        $this->scanner->assertValidHelperName($data['name']);
        $this->scanner->assertSafe($data['code']);
        $this->scanner->assertSyntaxValid($data['code']);

        $data = $this->normalizedPayload($request, $data);

        $helper = DB::transaction(function () use ($request, $data): RuntimeHelper {
            $helper = RuntimeHelper::query()->create([
                ...$this->helperAttributes($data),
                'name' => $data['name'],
                'code' => $data['code'],
                'status' => 'draft',
                'is_system' => false,
                'is_protected' => false,
                'requires_runtime_reload' => false,
                'last_test_status' => null,
                'active_version_id' => null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            RuntimeHelperVersion::query()->create([
                ...$this->versionAttributes($data),
                'helper_id' => $helper->id,
                'version_number' => 1,
                'safety_scan_status' => 'passed',
                'syntax_check_status' => 'passed',
                'test_status' => 'not_run',
                'status' => 'draft',
                'created_by' => $request->user()->id,
            ]);

            return $helper;
        });

        $this->audit->log('runtime', 'runtime_helper.created', 'Runtime helper created.', [
            'helper_id' => $helper->id,
            'name' => $helper->name,
            'status' => $helper->status,
            'category_id' => $helper->category_id,
            'version_number' => 1,
        ], $request->user(), 'success', $helper);

        return redirect()->route('admin.runtime.helpers.edit', $helper)
            ->with('status', 'Helper saved as draft. Run tests before activation.');
    }

    public function edit(RuntimeHelper $helper): View
    {
        $helper->load(['category', 'activeVersion', 'versions' => fn ($query) => $query->latest('version_number')]);

        return view('admin.runtime.helpers.edit', [
            'helper' => $helper,
            'categories' => $this->activeCategories(),
            'helperTypes' => $this->helperTypesForSelect($helper->helper_type),
            'draftVersion' => $helper->versions->first(),
        ]);
    }

    public function update(Request $request, RuntimeHelper $helper): RedirectResponse
    {
        $data = $this->validated($request, $helper);

        if ($data['name'] !== $helper->name) {
            throw ValidationException::withMessages(['name' => 'Helper names cannot be changed. Create a new helper for a new runtime name.']);
        }

        $this->scanner->assertValidHelperName($data['name']);
        $this->scanner->assertSafe($data['code']);
        $this->scanner->assertSyntaxValid($data['code']);

        $data = $this->normalizedPayload($request, $data);

        $version = DB::transaction(function () use ($request, $helper, $data): RuntimeHelperVersion {
            $nextVersion = ((int) $helper->versions()->max('version_number')) + 1;

            $helper->update([
                ...$this->helperAttributes($data),
                'updated_by' => $request->user()->id,
            ]);

            return RuntimeHelperVersion::query()->create([
                ...$this->versionAttributes($data),
                'helper_id' => $helper->id,
                'version_number' => $nextVersion,
                'safety_scan_status' => 'passed',
                'syntax_check_status' => 'passed',
                'test_status' => 'not_run',
                'status' => 'draft',
                'created_by' => $request->user()->id,
            ]);
        });

        $this->audit->log('runtime', 'runtime_helper.updated', 'Runtime helper draft version created.', [
            'helper_id' => $helper->id,
            'name' => $helper->name,
            'version_number' => $version->version_number,
            'status' => $helper->fresh()->status,
            'category_id' => $helper->category_id,
        ], $request->user(), 'success', $helper);

        return back()->with('status', 'New helper version saved as draft.');
    }

    public function activate(Request $request, RuntimeHelper $helper): RedirectResponse
    {
        $helper->load(['category', 'activeVersion']);

        if ($helper->is_protected || RuntimeHelperProtectedNames::isProtected($helper->name)) {
            return back()->with('error', 'Protected runtime helpers cannot be activated from admin helpers.');
        }

        if (! $helper->category?->is_active) {
            return back()->with('error', 'Activate the helper category before activating this helper.');
        }

        if ($helper->helper_type === 'system_bridge') {
            return back()->with('error', 'System bridge helpers cannot be activated here.');
        }

        $version = $helper->versions()
            ->where('status', 'draft')
            ->orderByDesc('version_number')
            ->first();

        if (! $version) {
            return back()->with('error', 'No draft version is available for activation.');
        }

        if ($version->safety_scan_status !== 'passed' || $version->syntax_check_status !== 'passed') {
            return back()->with('error', 'The selected draft version must pass safety and syntax checks before activation.');
        }

        DB::transaction(function () use ($request, $helper, $version): void {
            if ($helper->activeVersion) {
                $helper->activeVersion->update(['status' => 'archived']);
            }

            $version->update(['status' => 'active']);
            $helper->update([
                'active_version_id' => $version->id,
                'status' => 'active',
                'requires_runtime_reload' => true,
                'updated_by' => $request->user()->id,
            ]);
        });

        $this->audit->log('runtime', 'runtime_helper.activated', 'Runtime helper activated.', [
            'helper_id' => $helper->id,
            'name' => $helper->name,
            'version_number' => $version->version_number,
            'status' => 'active',
            'category_id' => $helper->category_id,
        ], $request->user(), 'success', $helper);

        return back()->with('status', 'Helper activated. Publish & Apply Helpers to make it live in bot containers.');
    }

    public function deactivate(Request $request, RuntimeHelper $helper): RedirectResponse
    {
        $helper->update([
            'status' => 'disabled',
            'requires_runtime_reload' => true,
            'updated_by' => $request->user()->id,
        ]);

        $this->audit->log('runtime', 'runtime_helper.deactivated', 'Runtime helper deactivated.', [
            'helper_id' => $helper->id,
            'name' => $helper->name,
            'status' => 'disabled',
            'category_id' => $helper->category_id,
        ], $request->user(), 'success', $helper);

        return back()->with('status', 'Helper disabled. Runtime reload required.');
    }

    public function destroy(Request $request, RuntimeHelper $helper): RedirectResponse
    {
        if ($helper->is_system || $helper->is_protected) {
            return back()->with('error', 'System or protected helpers cannot be deleted.');
        }

        $wasActive = $helper->status === 'active';
        $metadata = [
            'helper_id' => $helper->id,
            'name' => $helper->name,
            'status' => $helper->status,
            'category_id' => $helper->category_id,
        ];

        $helper->delete();

        $this->audit->log('runtime', 'runtime_helper.deleted', 'Runtime helper deleted.', $metadata, $request->user());

        return redirect()->route('admin.runtime.helpers.index')
            ->with($wasActive ? 'error' : 'status', $wasActive ? 'Helper deleted. Runtime reload will be required later.' : 'Helper deleted.');
    }

    private function validated(Request $request, ?RuntimeHelper $helper = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:runtime_helper_categories,id'],
            'name' => array_filter(['required', 'string', 'max:100', $helper ? Rule::in([$helper->name]) : null]),
            'label' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'helper_type' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::exists('runtime_helper_types', 'slug'),
            ],
            'code' => ['required', 'string'],
            'parameters_schema' => ['nullable', 'string'],
            'return_schema' => ['nullable', 'string'],
            'allowed_domains' => ['nullable'],
            'timeout_ms' => ['required', 'integer', 'min:100', 'max:60000'],
            'permission_level' => ['required', 'integer', 'min:0', 'max:2'],
            'expose_to_bot_code' => ['nullable', 'boolean'],
            'show_in_helper_list' => ['nullable', 'boolean'],
            'change_summary' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function normalizedPayload(Request $request, array $data): array
    {
        $data['parameters_schema'] = $this->jsonSchema($data['parameters_schema'] ?? null, 'parameters_schema');
        $data['return_schema'] = $this->jsonSchema($data['return_schema'] ?? null, 'return_schema');
        $data['allowed_domains'] = $this->allowedDomains($request->input('allowed_domains'));
        $data['expose_to_bot_code'] = $request->boolean('expose_to_bot_code');
        $data['show_in_helper_list'] = $request->boolean('show_in_helper_list');

        return $data;
    }

    private function helperAttributes(array $data): array
    {
        return [
            'category_id' => $data['category_id'],
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'helper_type' => $data['helper_type'],
            'parameters_schema' => $data['parameters_schema'],
            'return_schema' => $data['return_schema'],
            'allowed_domains' => $data['allowed_domains'],
            'timeout_ms' => $data['timeout_ms'],
            'permission_level' => $data['permission_level'],
            'expose_to_bot_code' => $data['expose_to_bot_code'],
            'show_in_helper_list' => $data['show_in_helper_list'],
        ];
    }

    private function versionAttributes(array $data): array
    {
        return [
            'code' => $data['code'],
            'parameters_schema' => $data['parameters_schema'],
            'return_schema' => $data['return_schema'],
            'allowed_domains' => $data['allowed_domains'],
            'timeout_ms' => $data['timeout_ms'],
            'permission_level' => $data['permission_level'],
            'change_summary' => $data['change_summary'] ?? null,
        ];
    }

    private function jsonSchema(?string $value, string $field): ?array
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

    private function activeCategories()
    {
        return RuntimeHelperCategory::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    }

    private function helperTypesForSelect(?string $includeSlug = null)
    {
        $types = RuntimeHelperType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if (filled($includeSlug) && ! $types->contains('slug', $includeSlug)) {
            $fallback = RuntimeHelperType::query()->where('slug', $includeSlug)->first();
            if ($fallback) {
                $types->push($fallback);
            } else {
                $types->push(new RuntimeHelperType([
                    'name' => str($includeSlug)->replace('_', ' ')->title()->toString(),
                    'slug' => $includeSlug,
                    'is_active' => false,
                ]));
            }
        }

        return $types;
    }
}
