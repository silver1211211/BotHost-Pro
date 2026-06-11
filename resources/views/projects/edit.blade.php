<x-dashboard-layout title="Edit Project">
    @include('projects.partials.form', [
        'action' => route('projects.update', $project),
        'method' => 'PATCH',
        'project' => $project,
        'button' => 'Save changes',
        'templates' => collect(),
    ])
</x-dashboard-layout>
