<x-admin-layout title="Create Runtime Helper" subtitle="Save a helper as a draft version.">
<div class="mx-auto max-w-5xl">
    @include('admin.runtime.helpers._form', [
        'action' => route('admin.runtime.helpers.store'),
        'method' => 'POST',
        'button' => 'Save Draft Helper',
        'version' => null,
    ])
</div>
</x-admin-layout>
