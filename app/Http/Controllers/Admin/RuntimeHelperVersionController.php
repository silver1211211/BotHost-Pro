<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RuntimeHelper;
use App\Models\RuntimeHelperVersion;
use App\Services\AuditLogService;
use App\Services\RuntimeHelperSafetyScanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RuntimeHelperVersionController extends Controller
{
    public function __construct(
        private readonly RuntimeHelperSafetyScanner $scanner,
        private readonly AuditLogService $audit,
    ) {}

    public function index(RuntimeHelper $helper): View
    {
        $helper->load(['category', 'activeVersion']);

        return view('admin.runtime.helpers.versions.index', [
            'helper' => $helper,
            'versions' => $helper->versions()->with('creator')->latest('version_number')->paginate(25),
        ]);
    }

    public function restore(Request $request, RuntimeHelper $helper, RuntimeHelperVersion $version): RedirectResponse
    {
        if ((int) $version->helper_id !== (int) $helper->id) {
            abort(404);
        }

        $safety = $this->scanner->scan((string) $version->code);
        if (! ($safety['passed'] ?? false)) {
            throw ValidationException::withMessages(['version' => 'The selected version failed safety scan and cannot be restored.']);
        }

        $syntax = $this->scanner->syntaxCheck((string) $version->code);
        if (! ($syntax['passed'] ?? false)) {
            throw ValidationException::withMessages(['version' => 'The selected version has invalid JavaScript syntax and cannot be restored.']);
        }

        $newVersion = RuntimeHelperVersion::query()->create([
            'helper_id' => $helper->id,
            'version_number' => ((int) $helper->versions()->max('version_number')) + 1,
            'code' => $version->code,
            'parameters_schema' => $version->parameters_schema,
            'return_schema' => $version->return_schema,
            'allowed_domains' => $version->allowed_domains,
            'timeout_ms' => $version->timeout_ms,
            'permission_level' => $version->permission_level,
            'change_summary' => 'Restored from version '.$version->version_number.'.',
            'safety_scan_status' => 'passed',
            'syntax_check_status' => 'passed',
            'test_status' => 'not_run',
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        $this->audit->log('runtime', 'runtime_helper_version.restored', 'Runtime helper version restored.', [
            'helper_id' => $helper->id,
            'name' => $helper->name,
            'version_number' => $newVersion->version_number,
            'restored_from_version' => $version->version_number,
            'status' => 'draft',
            'category_id' => $helper->category_id,
        ], $request->user(), 'success', $helper);

        return redirect()->route('admin.runtime.helpers.edit', $helper)
            ->with('status', 'Version restored as a new draft version.');
    }
}
