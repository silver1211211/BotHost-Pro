<x-dashboard-layout title="Create Project">
    @include('projects.partials.form', [
        'action' => route('projects.store'),
        'method' => 'POST',
        'project' => null,
        'button' => 'Create project',
        'templates' => $templates,
    ])
</x-dashboard-layout>
