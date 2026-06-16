<x-admin-layout title="Create Helper Category" subtitle="Add a runtime helper category.">
<div class="mx-auto max-w-3xl">
    @include('admin.runtime.helper-categories._form', [
        'action' => route('admin.runtime.helper-categories.store'),
        'method' => 'POST',
        'button' => 'Create Category',
    ])
</div>
</x-admin-layout>
