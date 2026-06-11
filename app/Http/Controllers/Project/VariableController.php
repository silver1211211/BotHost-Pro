<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectVariable;
use App\Services\ProjectAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VariableController extends Controller
{
    public function __construct(private readonly ProjectAccessService $access) {}

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->access->authorize($request, $project);
        $data = $request->validate([
            'key' => ['required', 'string', 'max:80', 'regex:/^[A-Z][A-Z0-9_]*$/', Rule::unique('project_variables')->where('project_id', $project->id)],
            'value' => ['nullable', 'string', 'max:5000'],
            'is_secret' => ['nullable', 'boolean'],
        ]);

        $project->variables()->create([
            'key' => $data['key'],
            'value' => $data['value'] ?? null,
            'is_secret' => $request->boolean('is_secret'),
        ]);

        $this->log($request, 'Created variable: '.$data['key']);

        return redirect()->route('projects.show', $project)->with('status', 'Variable created.');
    }

    public function update(Request $request, Project $project, ProjectVariable $variable): RedirectResponse
    {
        $this->access->authorize($request, $project);
        abort_unless($variable->project_id === $project->id, 403);

        $data = $request->validate([
            'key' => ['required', 'string', 'max:80', 'regex:/^[A-Z][A-Z0-9_]*$/', Rule::unique('project_variables')->where('project_id', $project->id)->ignore($variable->id)],
            'value' => ['nullable', 'string', 'max:5000'],
            'is_secret' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'key' => $data['key'],
            'is_secret' => $request->boolean('is_secret'),
        ];

        if ($request->filled('value')) {
            $payload['value'] = $data['value'];
        }

        $variable->update($payload);
        $this->log($request, 'Updated variable: '.$variable->key);

        return redirect()->route('projects.show', $project)->with('status', 'Variable updated.');
    }

    public function destroy(Request $request, Project $project, ProjectVariable $variable): RedirectResponse
    {
        $this->access->authorize($request, $project);
        abort_unless($variable->project_id === $project->id, 403);

        $key = $variable->key;
        $variable->delete();
        $this->log($request, 'Deleted variable: '.$key);

        return redirect()->route('projects.show', $project)->with('status', 'Variable deleted.');
    }

    private function log(Request $request, string $action): void
    {
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
