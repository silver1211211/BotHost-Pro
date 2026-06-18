<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RuntimeHelperType;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RuntimeHelperTypeController extends Controller
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $types = RuntimeHelperType::query()
            ->withCount(['helpers', 'categories'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.runtime.helper-types.index', [
            'types' => $types,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): View
    {
        return view('admin.runtime.helper-types.create', [
            'type' => new RuntimeHelperType(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $type = RuntimeHelperType::query()->create($data);

        $this->audit->log('runtime', 'runtime_helper_type.created', 'Runtime helper type created.', [
            'type_id' => $type->id,
            'slug' => $type->slug,
        ], $request->user(), 'success', $type);

        return redirect()->route('admin.runtime.helper-types.edit', $type)
            ->with('status', 'Helper type created.');
    }

    public function edit(RuntimeHelperType $type): View
    {
        return view('admin.runtime.helper-types.edit', [
            'type' => $type,
        ]);
    }

    public function update(Request $request, RuntimeHelperType $type): RedirectResponse
    {
        $data = $this->validated($request, $type);
        $data['is_active'] = $request->boolean('is_active');
        $data['updated_by'] = $request->user()->id;

        $type->update($data);

        $this->audit->log('runtime', 'runtime_helper_type.updated', 'Runtime helper type updated.', [
            'type_id' => $type->id,
            'slug' => $type->slug,
        ], $request->user(), 'success', $type);

        return back()->with('status', 'Helper type updated.');
    }

    public function toggle(Request $request, RuntimeHelperType $type): RedirectResponse
    {
        $type->update([
            'is_active' => ! $type->is_active,
            'updated_by' => $request->user()->id,
        ]);

        $this->audit->log('runtime', 'runtime_helper_type.toggled', 'Runtime helper type toggled.', [
            'type_id' => $type->id,
            'slug' => $type->slug,
            'is_active' => $type->is_active,
        ], $request->user(), 'success', $type);

        return back()->with('status', $type->is_active ? 'Helper type activated.' : 'Helper type deactivated.');
    }

    public function destroy(Request $request, RuntimeHelperType $type): RedirectResponse
    {
        if ($type->helpers()->exists() || $type->categories()->exists()) {
            return back()->with('error', 'Cannot delete helper type because helpers or categories are using it.');
        }

        $typeId = $type->id;
        $slug = $type->slug;
        $type->delete();

        $this->audit->log('runtime', 'runtime_helper_type.deleted', 'Runtime helper type deleted.', [
            'type_id' => $typeId,
            'slug' => $slug,
        ], $request->user());

        return redirect()->route('admin.runtime.helper-types.index')
            ->with('status', 'Helper type deleted.');
    }

    private function validated(Request $request, ?RuntimeHelperType $type = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('runtime_helper_types', 'slug')->ignore($type?->id),
            ],
            'description' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
