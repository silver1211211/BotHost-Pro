<x-admin-layout title="Edit Runtime Helper" subtitle="Create a new draft version.">
<div class="mx-auto max-w-5xl">
    @if($helper->requires_runtime_reload)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#F59E0B]/30 bg-[#F59E0B]/10 px-4 py-3 text-sm text-[#F59E0B]">
            <span>Helper bundle publish required. Some activated helpers are not compiled into the generated bundle yet.</span>
            <a href="{{ route('admin.runtime.reload.index') }}" class="rounded-lg border border-[#F59E0B]/30 px-3 py-1.5 text-xs font-black text-[#F59E0B]">Publish Helper Bundle</a>
        </div>
    @endif
    <div class="mb-4 rounded-xl border border-[#F59E0B]/30 bg-[#F59E0B]/10 px-4 py-3 text-sm text-[#F59E0B]">If this helper has not passed a test, test it before activation.</div>
    @include('admin.runtime.helpers._form', [
        'action' => route('admin.runtime.helpers.update', $helper),
        'method' => 'PATCH',
        'button' => 'Save New Draft Version',
        'version' => $draftVersion,
    ])
</div>
</x-admin-layout>
