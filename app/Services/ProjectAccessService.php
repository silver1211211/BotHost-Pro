<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectAccessService
{
    public function authorize(Request $request, Project $project): void
    {
        abort_unless($project->user_id === $request->user()->id, 403);
    }
}
