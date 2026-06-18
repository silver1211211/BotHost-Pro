<x-admin-layout title="Edit Helper Type" subtitle="Update runtime helper type metadata.">
    @include('admin.runtime.helper-types._form', [
        'action' => route('admin.runtime.helper-types.update', $type),
        'method' => 'PATCH',
        'button' => 'Save Helper Type',
    ])
</x-admin-layout>
