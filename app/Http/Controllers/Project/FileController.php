<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Services\ProjectAccessService;
use App\Services\ProjectWorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class FileController extends Controller
{
    public function __construct(
        private readonly ProjectWorkspaceService $workspace,
        private readonly ProjectAccessService $access,
    ) {}

    public function show(Request $request, Project $project, ProjectFile $file): JsonResponse
    {
        $this->access->authorize($request, $project);

        return response()->json([
            'id' => $file->id,
            'name' => $file->name,
            'path' => $file->relative_path,
            'content' => $this->workspace->readFile($project, $file),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->access->authorize($request, $project);
        $data = $request->validate(['path' => ['required', 'string', 'max:160']]);

        try {
            $file = $this->workspace->createFile($project, $data['path']);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['path' => $exception->getMessage()]);
        }

        $this->log($request, 'Created file: '.$file->relative_path);

        return redirect()->route('projects.show', $project)->with('status', 'File created.');
    }

    public function update(Request $request, Project $project, ProjectFile $file): JsonResponse
    {
        $this->access->authorize($request, $project);
        $data = $request->validate(['content' => ['nullable', 'string', 'max:262144']]);

        $file = $this->workspace->saveFile($project, $file, $data['content'] ?? '');

        return response()->json([
            'status' => 'saved',
            'file' => [
                'id' => $file->id,
                'size' => $file->size,
                'updated_at' => $file->updated_at->toIso8601String(),
            ],
        ]);
    }

    public function rename(Request $request, Project $project, ProjectFile $file): RedirectResponse
    {
        $this->access->authorize($request, $project);
        $data = $request->validate(['path' => ['required', 'string', 'max:160']]);

        try {
            $file = $this->workspace->renameFile($project, $file, $data['path']);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['path' => $exception->getMessage()]);
        }

        $this->log($request, 'Renamed file: '.$file->relative_path);

        return redirect()->route('projects.show', $project)->with('status', 'File renamed.');
    }

    public function destroy(Request $request, Project $project, ProjectFile $file): RedirectResponse
    {
        $this->access->authorize($request, $project);
        $path = $file->relative_path;

        $this->workspace->deleteFile($project, $file);
        $this->log($request, 'Deleted file: '.$path);

        return redirect()->route('projects.show', $project)->with('status', 'File deleted.');
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
