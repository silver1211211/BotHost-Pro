<x-admin-layout title="Edit Helper Category" subtitle="Update runtime helper category metadata.">
<div class="mx-auto max-w-3xl">
    @include('admin.runtime.helper-categories._form', [
        'action' => route('admin.runtime.helper-categories.update', $category),
        'method' => 'PATCH',
        'button' => 'Save Category',
    ])
</div>
</x-admin-layout>
