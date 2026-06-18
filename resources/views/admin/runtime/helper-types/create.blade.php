<x-admin-layout title="Create Helper Type" subtitle="Add a runtime helper type.">
    @include('admin.runtime.helper-types._form', [
        'action' => route('admin.runtime.helper-types.store'),
        'method' => 'POST',
        'button' => 'Create Helper Type',
    ])
</x-admin-layout>
