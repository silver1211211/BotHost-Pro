<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Template;
use App\Services\ProjectAccessService;
use App\Services\ProjectWorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectWorkspaceService $workspace,
        private readonly ProjectAccessService $access,
    ) {}

    public function index(Request $request): View
    {
        return view('projects.index', [
            'projects' => $request->user()->projects()->latest()->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('projects.create', [
            'templates' => Template::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'language' => ['required', Rule::in(Project::LANGUAGES)],
            'template_id' => ['nullable', 'exists:templates,id'],
        ]);

        $project = $request->user()->projects()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'language' => $data['language'],
            'template_id' => $data['template_id'] ?? null,
            'slug' => $this->uniqueSlug($request->user()->id, $data['name']),
            'status' => 'stopped',
        ]);

        $template = isset($data['template_id'])
            ? Template::query()->where('is_active', true)->find($data['template_id'])
            : null;

        $this->workspace->ensureWorkspace($project, $template);
        $this->log($request, 'Created project: '.$project->name);

        return redirect()->route('projects.show', $project)->with('status', 'Project created.');
    }

    public function show(Request $request, Project $project): View
    {
        $this->access->authorize($request, $project);
        $this->workspace->ensureWorkspace($project);

        $project->load(['files', 'variables', 'setting', 'template']);
        $activeFile = $project->files->firstWhere('relative_path', 'bot.js') ?: $project->files->first();

        return view('projects.show', [
            'project' => $project,
            'files' => $project->files,
            'variables' => $project->variables,
            'setting' => $project->setting,
            'activeFile' => $activeFile,
            'activeContent' => $activeFile ? $this->workspace->readFile($project, $activeFile) : '',
        ]);
    }

    public function edit(Request $request, Project $project): View
    {
        $this->access->authorize($request, $project);

        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->access->authorize($request, $project);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(Project::STATUSES)],
            'language' => ['required', Rule::in(Project::LANGUAGES)],
        ]);

        if ($project->name !== $data['name']) {
            $data['slug'] = $this->uniqueSlug($request->user()->id, $data['name'], $project->id);
        }

        $project->update($data);
        $this->log($request, 'Updated project: '.$project->name);

        return redirect()->route('projects.show', $project)->with('status', 'Project updated.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->access->authorize($request, $project);

        $name = $project->name;
        $project->delete();
        $this->log($request, 'Deleted project: '.$name);

        return redirect()->route('projects.index')->with('status', 'Project deleted.');
    }

    private function uniqueSlug(int $userId, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'project';
        $slug = $base;
        $count = 2;

        while (Project::query()
            ->where('user_id', $userId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$count++;
        }

        return $slug;
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
