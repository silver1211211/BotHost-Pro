<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RuntimeHelperCategory;
use App\Models\RuntimeHelperType;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RuntimeHelperCategoryController extends Controller
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $categories = RuntimeHelperCategory::query()
            ->withCount('helpers')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('helper_type', 'like', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.runtime.helper-categories.index', [
            'categories' => $categories,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): View
    {
        return view('admin.runtime.helper-categories.create', [
            'category' => new RuntimeHelperCategory(),
            'helperTypes' => $this->helperTypesForSelect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['allowed_domains'] = $this->allowedDomains($request->input('allowed_domains'));
        $data['is_active'] = $request->boolean('is_active');
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $category = RuntimeHelperCategory::query()->create($data);

        $this->audit->log('runtime', 'runtime_helper_category.created', 'Runtime helper category created.', [
            'category_id' => $category->id,
            'slug' => $category->slug,
            'helper_type' => $category->helper_type,
        ], $request->user(), 'success', $category);

        return redirect()->route('admin.runtime.helper-categories.edit', $category)
            ->with('status', 'Helper category created.');
    }

    public function edit(RuntimeHelperCategory $category): View
    {
        return view('admin.runtime.helper-categories.edit', [
            'category' => $category,
            'helperTypes' => $this->helperTypesForSelect($category->helper_type),
        ]);
    }

    public function update(Request $request, RuntimeHelperCategory $category): RedirectResponse
    {
        $data = $this->validated($request, $category);
        $data['allowed_domains'] = $this->allowedDomains($request->input('allowed_domains'));
        $data['is_active'] = $request->boolean('is_active');
        $data['updated_by'] = $request->user()->id;

        $category->update($data);

        $this->audit->log('runtime', 'runtime_helper_category.updated', 'Runtime helper category updated.', [
            'category_id' => $category->id,
            'slug' => $category->slug,
            'helper_type' => $category->helper_type,
        ], $request->user(), 'success', $category);

        return back()->with('status', 'Helper category updated.');
    }

    public function toggle(Request $request, RuntimeHelperCategory $category): RedirectResponse
    {
        $category->update([
            'is_active' => ! $category->is_active,
            'updated_by' => $request->user()->id,
        ]);

        $this->audit->log('runtime', 'runtime_helper_category.toggled', 'Runtime helper category toggled.', [
            'category_id' => $category->id,
            'slug' => $category->slug,
            'is_active' => $category->is_active,
        ], $request->user(), 'success', $category);

        return back()->with('status', $category->is_active ? 'Helper category activated.' : 'Helper category deactivated.');
    }

    public function destroy(Request $request, RuntimeHelperCategory $category): RedirectResponse
    {
        if ($category->helpers()->exists()) {
            return back()->with('error', 'Cannot delete category because helpers exist under it.');
        }

        $categoryId = $category->id;
        $slug = $category->slug;
        $category->delete();

        $this->audit->log('runtime', 'runtime_helper_category.deleted', 'Runtime helper category deleted.', [
            'category_id' => $categoryId,
            'slug' => $slug,
        ], $request->user());

        return redirect()->route('admin.runtime.helper-categories.index')
            ->with('status', 'Helper category deleted.');
    }

    private function validated(Request $request, ?RuntimeHelperCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', Rule::unique('runtime_helper_categories', 'slug')->ignore($category?->id)],
            'description' => ['nullable', 'string'],
            'helper_type' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::exists('runtime_helper_types', 'slug'),
            ],
            'allowed_domains' => ['nullable'],
            'default_timeout_ms' => ['required', 'integer', 'min:100', 'max:60000'],
            'permission_level' => ['required', 'integer', 'min:0', 'max:2'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ]);
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
